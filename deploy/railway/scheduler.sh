#!/bin/sh
set -eu

php artisan schedule-monitor:sync --no-interaction
php artisan schedule:run --no-interaction --verbose
