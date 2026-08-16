#!/bin/bash
set -e

echo "Starting AUURA CRM on Railway..."

# Ensure .env exists
if [ ! -f /var/www/html/.env ]; then
    cp /var/www/html/.env.example /var/www/html/.env || true
fi

# Auto-detect Railway native MySQL environment variables
DB_HOST="${DB_HOST:-$MYSQLHOST}"
DB_PORT="${DB_PORT:-$MYSQLPORT}"
DB_DATABASE="${DB_DATABASE:-$MYSQLDATABASE}"
DB_USERNAME="${DB_USERNAME:-$MYSQLUSER}"
DB_PASSWORD="${DB_PASSWORD:-$MYSQLPASSWORD}"
DATABASE_URL="${DATABASE_URL:-$MYSQL_URL}"

# Remove any empty fallback lines from .env
sed -i '/^DB_HOST=$/d' /var/www/html/.env 2>/dev/null || true
sed -i '/^DB_DATABASE=$/d' /var/www/html/.env 2>/dev/null || true
sed -i '/^DB_USERNAME=$/d' /var/www/html/.env 2>/dev/null || true
sed -i '/^DB_PASSWORD=$/d' /var/www/html/.env 2>/dev/null || true

# Forcefully write database credentials into .env if non-empty
[ -n "$DB_HOST" ] && (grep -q "^DB_HOST=" /var/www/html/.env && sed -i "s|^DB_HOST=.*|DB_HOST=${DB_HOST}|g" /var/www/html/.env || echo "DB_HOST=${DB_HOST}" >> /var/www/html/.env) || true
[ -n "$DB_PORT" ] && (grep -q "^DB_PORT=" /var/www/html/.env && sed -i "s|^DB_PORT=.*|DB_PORT=${DB_PORT}|g" /var/www/html/.env || echo "DB_PORT=${DB_PORT}" >> /var/www/html/.env) || true
[ -n "$DB_DATABASE" ] && (grep -q "^DB_DATABASE=" /var/www/html/.env && sed -i "s|^DB_DATABASE=.*|DB_DATABASE=${DB_DATABASE}|g" /var/www/html/.env || echo "DB_DATABASE=${DB_DATABASE}" >> /var/www/html/.env) || true
[ -n "$DB_USERNAME" ] && (grep -q "^DB_USERNAME=" /var/www/html/.env && sed -i "s|^DB_USERNAME=.*|DB_USERNAME=${DB_USERNAME}|g" /var/www/html/.env || echo "DB_USERNAME=${DB_USERNAME}" >> /var/www/html/.env) || true
[ -n "$DB_PASSWORD" ] && (grep -q "^DB_PASSWORD=" /var/www/html/.env && sed -i "s|^DB_PASSWORD=.*|DB_PASSWORD=${DB_PASSWORD}|g" /var/www/html/.env || echo "DB_PASSWORD=${DB_PASSWORD}" >> /var/www/html/.env) || true
[ -n "$DATABASE_URL" ] && (grep -q "^DATABASE_URL=" /var/www/html/.env && sed -i "s|^DATABASE_URL=.*|DATABASE_URL=${DATABASE_URL}|g" /var/www/html/.env || echo "DATABASE_URL=${DATABASE_URL}" >> /var/www/html/.env) || true

[ -n "$APP_URL" ] && (grep -q "^APP_URL=" /var/www/html/.env && sed -i "s|^APP_URL=.*|APP_URL=${APP_URL}|g" /var/www/html/.env || echo "APP_URL=${APP_URL}" >> /var/www/html/.env) || true
[ -n "$WHATSAPP_PHONE_NUMBER_ID" ] && (grep -q "^WHATSAPP_PHONE_NUMBER_ID=" /var/www/html/.env && sed -i "s|^WHATSAPP_PHONE_NUMBER_ID=.*|WHATSAPP_PHONE_NUMBER_ID=${WHATSAPP_PHONE_NUMBER_ID}|g" /var/www/html/.env || echo "WHATSAPP_PHONE_NUMBER_ID=${WHATSAPP_PHONE_NUMBER_ID}" >> /var/www/html/.env) || true
[ -n "$WHATSAPP_ACCESS_TOKEN" ] && (grep -q "^WHATSAPP_ACCESS_TOKEN=" /var/www/html/.env && sed -i "s|^WHATSAPP_ACCESS_TOKEN=.*|WHATSAPP_ACCESS_TOKEN=${WHATSAPP_ACCESS_TOKEN}|g" /var/www/html/.env || echo "WHATSAPP_ACCESS_TOKEN=${WHATSAPP_ACCESS_TOKEN}" >> /var/www/html/.env) || true
[ -n "$WHATSAPP_VERIFY_TOKEN" ] && (grep -q "^WHATSAPP_VERIFY_TOKEN=" /var/www/html/.env && sed -i "s|^WHATSAPP_VERIFY_TOKEN=.*|WHATSAPP_VERIFY_TOKEN=${WHATSAPP_VERIFY_TOKEN}|g" /var/www/html/.env || echo "WHATSAPP_VERIFY_TOKEN=${WHATSAPP_VERIFY_TOKEN}" >> /var/www/html/.env) || true

# Always ensure a valid 32-byte APP_KEY exists
if [ -n "$APP_KEY" ] && [ ${#APP_KEY} -ge 50 ]; then
    grep -q "^APP_KEY=" /var/www/html/.env && sed -i "s|^APP_KEY=.*|APP_KEY=${APP_KEY}|g" /var/www/html/.env || echo "APP_KEY=${APP_KEY}" >> /var/www/html/.env
else
    php artisan key:generate --force || true
fi

php artisan config:clear || true
php artisan cache:clear || true
php artisan storage:link || true

# Mark application as installed so it goes straight to Admin
touch /var/www/html/storage/installed || true
chmod 664 /var/www/html/storage/installed || true

# Run database migrations without overwriting users
if [ -n "$DB_HOST" ] && [ "$DB_HOST" != "127.0.0.1" ]; then
    echo "Running database migrations on ${DB_HOST}..."
    php artisan migrate --force || echo "Warning: Migration already up to date or failed."
fi

PORT="${PORT:-8080}"
echo "AUURA CRM server running on port $PORT..."
exec php artisan serve --host=0.0.0.0 --port="$PORT"
