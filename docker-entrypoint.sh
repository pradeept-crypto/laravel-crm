#!/bin/bash
set -e

echo "Starting AUURA CRM..."

# Generate app key if missing
if [ -z "$APP_KEY" ]; then
    php artisan key:generate --force || true
fi

php artisan config:clear || true
php artisan storage:link || true

# Attempt migrations safely if DB_HOST is configured
if [ -n "$DB_HOST" ]; then
    echo "Running database migrations..."
    php artisan migrate --force || echo "Warning: Migration failed or DB not reachable yet."
fi

echo "Starting Apache web server..."
exec apache2-foreground
