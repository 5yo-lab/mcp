#!/usr/bin/env sh
# Run from repo root:  sh scripts/bootstrap.sh   (or: ./scripts/bootstrap.sh)
set -eu

ROOT="$(CDPATH= cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"

if [ ! -f .env ]; then
  cp .env.example .env
  echo "Created .env from .env.example"
fi

echo "Building images..."
docker compose build

echo "Starting PostgreSQL..."
docker compose up -d postgres

echo "Waiting for postgres health..."
deadline=$(( $(date +%s) + 120 ))
health=""
while [ "$(date +%s)" -lt "$deadline" ]; do
  cid="$(docker compose ps -q postgres 2>/dev/null || true)"
  if [ -n "$cid" ]; then
    health="$(docker inspect --format '{{.State.Health.Status}}' "$cid" 2>/dev/null || true)"
    if [ "$health" = "healthy" ]; then
      break
    fi
  fi
  sleep 2
done
if [ "$health" != "healthy" ]; then
  echo "PostgreSQL did not become healthy in time." >&2
  exit 1
fi

if [ ! -f backend/artisan ]; then
  echo "Installing Laravel into backend/..."
  # create-project requires an empty dir; backend/ may contain .gitkeep
  docker compose run --rm --no-deps --entrypoint sh backend -c '
    set -e
    staging=/tmp/laravel-staging
    rm -rf "$staging"
    composer create-project laravel/laravel "$staging" --prefer-dist --no-interaction
    cp -a "$staging"/. /var/www/html/
    rm -rf "$staging"
  '
  echo "Set backend/.env DB_HOST=postgres and DB_* to match root .env / compose."
  echo "Next: install Sanctum and API routes inside the backend container."
else
  echo "backend/ already has Laravel (artisan present)."
fi

if [ ! -f frontend/package.json ]; then
  echo "Installing Vite + React into frontend/ (scaffold only — no dev server)..."
  # -T: no TTY; CI/npm yes: skip prompts; never run npm run dev here
  docker compose run --rm --no-deps -T --entrypoint sh frontend -c '
    set -e
    export CI=1 NPM_CONFIG_YES=true
    # Scaffold outside the bind-mounted /app (create-vite may treat paths as relative to cwd)
    staging=/var/vite-staging
    rm -rf "$staging" /app/tmp
    cd /
    npm exec --yes create-vite@latest "$staging" -- --template react-ts
    if [ ! -d "$staging" ]; then
      echo "Expected scaffold at $staging" >&2
      ls -la /var /app/tmp 2>/dev/null || true
      exit 1
    fi
    cp -a "$staging"/. /app/
    rm -rf "$staging" /app/tmp
    cd /app && npm install
  '
else
  echo "frontend/ already has package.json."
fi

echo ""
echo "Done. Start the stack (background recommended):"
echo "  docker compose up -d"
echo "  docker compose logs -f backend frontend   # optional"
echo "  docker compose down                         # stop (instead of Ctrl+C)"
echo ""
echo "MCP (interactive, for Cursor):"
echo "  docker compose --profile mcp run --rm -i mcp"
