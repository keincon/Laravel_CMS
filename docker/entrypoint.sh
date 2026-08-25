#!/usr/bin/env bash
set -euo pipefail

cd /var/www/html

mkdir -p \
  storage/framework/{cache,sessions,views} \
  storage/logs \
  storage/app/cms \
  bootstrap/cache

chmod -R ug+rwx storage bootstrap/cache || true

if [ ! -f .env ] && [ -f .env.example ]; then
  cp .env.example .env
fi

if [ -z "${APP_KEY:-}" ] || [ "${APP_KEY}" = "" ]; then
  if ! grep -q '^APP_KEY=base64:' .env 2>/dev/null; then
    php artisan key:generate --force --no-interaction || true
  fi
fi

# Wait for PostgreSQL when DB_HOST is set (compose network).
if [ -n "${DB_HOST:-}" ]; then
  echo "Waiting for PostgreSQL at ${DB_HOST}:${DB_PORT:-5432}..."
  for i in $(seq 1 60); do
    if php -r "
      try {
        new PDO(
          'pgsql:host=' . getenv('DB_HOST') . ';port=' . (getenv('DB_PORT') ?: '5432') . ';dbname=' . getenv('DB_DATABASE'),
          getenv('DB_USERNAME'),
          getenv('DB_PASSWORD') ?: ''
        );
        exit(0);
      } catch (Throwable \$e) {
        exit(1);
      }
    "; then
      echo "PostgreSQL is ready."
      break
    fi
    sleep 1
    if [ "$i" -eq 60 ]; then
      echo "PostgreSQL did not become ready in time (setup wizard can still configure DB)."
    fi
  done
fi

exec "$@"
