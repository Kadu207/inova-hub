#!/bin/bash
set -e

cd /var/www/html

if [ ! -f artisan ]; then
  echo "entrypoint: Laravel app not mounted at /var/www/html" >&2
  exit 1
fi

# vendor incompleto (ex.: pscp parcial) — reinstala
if [ ! -f vendor/autoload.php ] || [ ! -f vendor/symfony/deprecation-contracts/function.php ]; then
  echo "entrypoint: running composer install..."
  composer install --no-interaction --prefer-dist --optimize-autoloader
fi

mkdir -p storage/framework/{cache,sessions,views} storage/logs bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true
chmod -R ug+rwx storage bootstrap/cache 2>/dev/null || true

if [ -z "${APP_KEY:-}" ] && [ -f .env ]; then
  # se APP_KEY vazio no .env, gera um (só local/dev; em prod defina no .env)
  grep -q '^APP_KEY=$' .env 2>/dev/null && php artisan key:generate --force || true
fi

exec apache2-foreground
