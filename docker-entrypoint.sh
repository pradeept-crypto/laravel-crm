#!/bin/bash

echo "Starting AUURA CRM on Railway..."

# Ensure .env exists
if [ ! -f /var/www/html/.env ]; then
    cp /var/www/html/.env.example /var/www/html/.env 2>/dev/null || true
fi

# Ensure APP_KEY exists
if [ -n "$APP_KEY" ] && [ ${#APP_KEY} -ge 40 ]; then
    grep -q "^APP_KEY=" /var/www/html/.env && sed -i "s|^APP_KEY=.*|APP_KEY=${APP_KEY}|g" /var/www/html/.env || echo "APP_KEY=${APP_KEY}" >> /var/www/html/.env
else
    php artisan key:generate --force 2>/dev/null || true
fi

# Clear configuration cache so all Railway env vars are live
php artisan config:clear 2>/dev/null || true
php artisan cache:clear 2>/dev/null || true
php artisan storage:link 2>/dev/null || true

# Mark application as installed
touch /var/www/html/storage/installed 2>/dev/null || true
chmod 664 /var/www/html/storage/installed 2>/dev/null || true

# Run database migrations
if [ -n "$DB_HOST" ] && [ "$DB_HOST" != "127.0.0.1" ]; then
    echo "Running database migrations..."
    php artisan migrate --force 2>/dev/null || true
fi

PORT="${PORT:-8080}"
echo "AUURA CRM server running on port $PORT..."
exec php artisan serve --host=0.0.0.0 --port="$PORT"
