#!/bin/sh
set -e

echo "Generating app key if not set..."
php artisan key:generate --force --no-interaction || true

echo "Running database migrations..."
php artisan migrate --force

echo "Installing Passport keys..."
php artisan passport:keys --force || true

# Fix OAuth key permissions
chmod 600 /var/www/html/storage/oauth-private.key 2>/dev/null || true
chmod 600 /var/www/html/storage/oauth-public.key 2>/dev/null || true

echo "Creating Passport client..."
php artisan passport:client --personal --no-interaction || true

echo "Clearing and caching config..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "Setting permissions..."
chown -R www-data:www-data /var/www/html/storage
chmod -R 755 /var/www/html/storage
chmod 600 /var/www/html/storage/oauth-private.key 2>/dev/null || true
chmod 600 /var/www/html/storage/oauth-public.key 2>/dev/null || true

echo "Starting application..."
exec "$@"
