#!/bin/sh
# Prepares the Laravel app at container start, then runs the given command (Apache by default).
set -e

cd /var/www/html

if [ -z "$APP_KEY" ]; then
    echo "ERROR: APP_KEY is not set. Generate one with:  php artisan key:generate --show" >&2
    exit 1
fi

# SQLite: create the database file on first start (it lives on the /var/www/data volume).
if [ "${DB_CONNECTION:-sqlite}" = "sqlite" ]; then
    mkdir -p "$(dirname "$DB_DATABASE")"
    [ -f "$DB_DATABASE" ] || touch "$DB_DATABASE"
    chown -R www-data:www-data "$(dirname "$DB_DATABASE")"
fi

# Volumes may arrive owned by root.
chown -R www-data:www-data storage bootstrap/cache

if [ "${RUN_MIGRATIONS:-true}" = "true" ]; then
    php artisan migrate --force --no-interaction
fi

php artisan storage:link --force >/dev/null 2>&1 || true
php artisan optimize

exec "$@"
