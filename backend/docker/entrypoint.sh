#!/bin/sh
set -e

# Wait until the database accepts connections (the db service may still be
# initialising on the very first start).
attempt=0
until php artisan db:show --no-ansi > /dev/null 2>&1; do
    attempt=$((attempt + 1))
    if [ "$attempt" -ge 30 ]; then
        echo "Database is not reachable, giving up." >&2
        exit 1
    fi
    echo "Waiting for database... ($attempt)"
    sleep 2
done

php artisan config:cache
php artisan route:cache
php artisan migrate --force
php artisan db:seed --force

exec "$@"
