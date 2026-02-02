#!/bin/sh
set -e

echo "Generating app key if not set..."
php artisan key:generate --force --no-interaction || true

echo "Clearing and caching config..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "Setting permissions..."
chown -R www-data:www-data /var/www/html/storage
chmod -R 755 /var/www/html/storage

echo "Starting application..."
exec "$@"
