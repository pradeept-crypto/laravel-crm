#!/bin/bash
set -e

echo "Starting AUURA CRM on Railway..."

# Ensure .env exists
if [ ! -f /var/www/html/.env ]; then
    cp /var/www/html/.env.example /var/www/html/.env || true
fi

# Sync Railway environment variables into .env file
[ -n "$DB_HOST" ] && sed -i "s|^DB_HOST=.*|DB_HOST=${DB_HOST}|g" /var/www/html/.env || true
[ -n "$DB_PORT" ] && sed -i "s|^DB_PORT=.*|DB_PORT=${DB_PORT}|g" /var/www/html/.env || true
[ -n "$DB_DATABASE" ] && sed -i "s|^DB_DATABASE=.*|DB_DATABASE=${DB_DATABASE}|g" /var/www/html/.env || true
[ -n "$DB_USERNAME" ] && sed -i "s|^DB_USERNAME=.*|DB_USERNAME=${DB_USERNAME}|g" /var/www/html/.env || true
[ -n "$DB_PASSWORD" ] && sed -i "s|^DB_PASSWORD=.*|DB_PASSWORD=${DB_PASSWORD}|g" /var/www/html/.env || true
[ -n "$APP_URL" ] && sed -i "s|^APP_URL=.*|APP_URL=${APP_URL}|g" /var/www/html/.env || true
[ -n "$APP_KEY" ] && sed -i "s|^APP_KEY=.*|APP_KEY=${APP_KEY}|g" /var/www/html/.env || true
[ -n "$WHATSAPP_PHONE_NUMBER_ID" ] && sed -i "s|^WHATSAPP_PHONE_NUMBER_ID=.*|WHATSAPP_PHONE_NUMBER_ID=${WHATSAPP_PHONE_NUMBER_ID}|g" /var/www/html/.env || true
[ -n "$WHATSAPP_ACCESS_TOKEN" ] && sed -i "s|^WHATSAPP_ACCESS_TOKEN=.*|WHATSAPP_ACCESS_TOKEN=${WHATSAPP_ACCESS_TOKEN}|g" /var/www/html/.env || true

# Ensure APP_KEY is generated if missing
if ! grep -q "APP_KEY=base64:" /var/www/html/.env; then
    php artisan key:generate --force || true
fi

php artisan config:clear || true
php artisan cache:clear || true
php artisan storage:link || true

# Mark application as installed so it goes straight to Admin
touch /var/www/html/storage/installed || true
chmod 664 /var/www/html/storage/installed || true

# Run database migrations and seed default admin if DB is configured
if [ -n "$DB_HOST" ] && [ "$DB_HOST" != "127.0.0.1" ]; then
    echo "Running database migrations on ${DB_HOST}..."
    php artisan migrate --seed --force || echo "Warning: Migration already up to date or failed."
fi

PORT="${PORT:-8080}"
echo "AUURA CRM server running on port $PORT..."
exec php artisan serve --host=0.0.0.0 --port="$PORT"
