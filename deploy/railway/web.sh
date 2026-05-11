#!/bin/sh
set -eu

# Ensure Laravel runtime directories are writable for compiled views/cache.
mkdir -p storage/framework/views storage/framework/cache storage/framework/sessions bootstrap/cache
chmod -R ug+rwX storage bootstrap/cache || true

php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan serve --host=0.0.0.0 --port="${PORT:-8080}"
