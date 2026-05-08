#!/bin/sh
set -eu

php artisan queue:work redis --queue=default --sleep=1 --tries=3 --timeout=120 --max-time=3600 --no-interaction
