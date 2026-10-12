# Full-stack API + React + MCP

Laravel JSON API, standalone React SPA, Python MCP — **API tokens**, **PostgreSQL**, all via **Docker**.

## Prerequisites

- [Docker Desktop](https://www.docker.com/products/docker-desktop/) (Compose v2)
- No local PHP, Node, or Python required for day-to-day dev
- **Bootstrap**: POSIX `sh` (Git Bash on Windows, or WSL). Optional: `.\scripts\bootstrap.ps1` delegates to `sh`.

## Verify Docker (test flow)

1. Start **Docker Desktop** and wait until it is running.
2. From repo root:

```powershell
cd C:\Users\petyo\Projects\mcp
docker version
docker compose version
copy .env.example .env
docker compose build
docker compose up -d postgres
docker compose ps
```

3. Postgres should show **healthy**. Then bootstrap apps (first time only):

```powershell
sh scripts/bootstrap.sh
```

4. Start the full stack:

```powershell
docker compose up -d
docker compose ps
docker compose logs -f backend frontend
```

**Stopping:** use `docker compose down`. If you run `docker compose up` in the foreground (no `-d`), **Ctrl+C** stops the dev servers (Vite + `artisan serve`) — that is normal, but Compose may exit with code 130 and show a “fix this compose error” hint from Docker Desktop. That is **not** a failed bootstrap; use detached mode or `docker compose down` to avoid it.

5. **Checks**

| Check | Command / action | Expected |
|-------|------------------|----------|
| Postgres | `docker compose exec postgres pg_isready -U app -d app` | `accepting connections` |
| Backend | Open http://localhost:8000 | Laravel welcome (after bootstrap) |
| Frontend | Open http://localhost:5173 | Vite React page |
| Backend logs | `docker compose logs backend --tail 50` | `Server running on http://0.0.0.0:8000` |
| MCP image | `docker compose --profile mcp build mcp` | Build succeeds |
| MCP tool | `docker compose --profile mcp run --rm -i mcp` | Server starts (stdio; Ctrl+C to exit) |

6. If shell entrypoints fail with `$'\r'`, re-normalize line endings after pulling `.gitattributes`:

```powershell
git add --renormalize .
git status
```

## Quick start

```powershell
cd C:\Users\petyo\Projects\mcp
copy .env.example .env
sh scripts/bootstrap.sh
docker compose up
```

| Service   | URL |
|-----------|-----|
| API       | http://localhost:8000 |
| React     | http://localhost:5173 |
| Postgres  | `localhost:5432` (user/db from `.env`) |

After Laravel is installed, set `backend/.env` `DB_*` to match Compose (`DB_HOST=postgres` when running in container; use `postgres` not `127.0.0.1` inside backend container).

## Commands

```powershell
# Build / rebuild images
docker compose build

# Stack (postgres + backend + frontend)
docker compose up -d
docker compose logs -f

# Backend one-off (artisan, composer, tests)
docker compose run --rm backend php artisan migrate
docker compose run --rm backend composer require laravel/sanctum

# MCP stdio (Cursor) — see mcp/README.md
docker compose --profile mcp run --rm -i mcp

# Tear down (keep DB volume)
docker compose down

# Tear down including DB data
docker compose down -v
```

## Layout

```text
backend/          Laravel (created by bootstrap)
frontend/         Vite + React (created by bootstrap)
mcp/              Python MCP server
docker/           Dockerfiles and entrypoints for backend & frontend
docker-compose.yml
AGENTS.md         Agent / architecture guide
```

## Next steps after Docker

1. Sanctum + `/api/v1` routes in Laravel (inside container)
2. React API client + Bearer token
3. Issue service token → `MCP_LARAVEL_API_TOKEN` in root `.env`

See `AGENTS.md` and `.cursor/skills/`.
