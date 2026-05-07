#!/bin/sh
set -eu

php artisan schedule:run --no-interaction --verbose
