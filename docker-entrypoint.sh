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

# Mark application as installed so it goes straight to Admin
touch /var/www/html/storage/installed || true
chmod 664 /var/www/html/storage/installed || true

# Run database migrations and seed default admin if DB is configured
if [ -n "$DB_HOST" ]; then
    echo "Running database migrations..."
    php artisan migrate --seed --force || echo "Warning: Migration already up to date."
fi

PORT="${PORT:-8080}"
echo "AUURA CRM server running on port $PORT..."
exec php artisan serve --host=0.0.0.0 --port="$PORT"
