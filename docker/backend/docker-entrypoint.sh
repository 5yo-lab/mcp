#!/bin/sh
set -e

echo "Waiting for PostgreSQL..."
until php -r "
  try {
    new PDO(
      'pgsql:host=${DB_HOST};port=${DB_PORT};dbname=${DB_DATABASE}',
      '${DB_USERNAME}',
      '${DB_PASSWORD}'
    );
    exit(0);
  } catch (Throwable \$e) {
    exit(1);
  }
" 2>/dev/null; do
  sleep 1
done
echo "PostgreSQL is ready."

if [ ! -f artisan ]; then
  echo ""
  echo "Laravel is not installed in ./backend yet."
  echo "From the repo root, run:"
  echo "  sh scripts/bootstrap.sh"
  echo "  # or: docker compose run --rm backend composer create-project laravel/laravel . --prefer-dist"
  echo ""
  exec tail -f /dev/null
fi

if [ ! -d vendor ]; then
  composer install --no-interaction --prefer-dist
fi

if [ -z "$APP_KEY" ] || [ "$APP_KEY" = "base64:" ]; then
  if [ -f .env ]; then
    php artisan key:generate --force --no-interaction 2>/dev/null || true
  fi
fi

php artisan migrate --force --no-interaction 2>/dev/null || true

exec php artisan serve --host=0.0.0.0 --port=8000
