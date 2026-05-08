#!/bin/sh
set -eu

php artisan ops:check-staging-readiness --no-interaction
