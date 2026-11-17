#!/bin/sh
set -e

php artisan package:discover --ansi
php artisan filament:upgrade
php artisan storage:link 2>/dev/null || true
php artisan migrate --force

exec "$@"