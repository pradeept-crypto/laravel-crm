#!/bin/bash
set -e

echo "Starting AUURA CRM on Railway..."

# Ensure .env exists
if [ ! -f /var/www/html/.env ]; then
    cp /var/www/html/.env.example /var/www/html/.env || true
fi

# Ensure APP_KEY is generated
if [ -z "$APP_KEY" ]; then
    php artisan key:generate --force || true
fi

php artisan config:clear || true
php artisan storage:link || true

# Run database migrations if DB is configured
if [ -n "$DB_HOST" ]; then
    echo "Running database migrations..."
    php artisan migrate --seed --force || echo "Warning: Migration failed or DB not reachable yet."
fi

PORT="${PORT:-8000}"
echo "AUURA CRM server running on port $PORT..."
exec php artisan serve --host=0.0.0.0 --port="$PORT"
