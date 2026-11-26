#!/bin/sh
set -e

php artisan package:discover --ansi
php artisan filament:upgrade
php artisan migrate --force

exec "$@"