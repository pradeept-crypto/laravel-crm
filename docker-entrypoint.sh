#!/bin/bash

echo "Starting AUURA CRM on Railway..."

# Sync all Railway environment variables into .env cleanly
php /var/www/html/bin/sync-env.php 2>/dev/null || php bin/sync-env.php 2>/dev/null || true

# Ensure APP_KEY exists
if [ -z "$APP_KEY" ]; then
    php artisan key:generate --force 2>/dev/null || true
fi

# Discover packages and clear configuration cache
php artisan package:discover --ansi 2>/dev/null || true
php artisan config:clear 2>/dev/null || true
php artisan cache:clear 2>/dev/null || true
php artisan storage:link 2>/dev/null || true

# Mark application as installed
touch /var/www/html/storage/installed 2>/dev/null || true
chmod 664 /var/www/html/storage/installed 2>/dev/null || true

# Run database migrations
echo "Running database migrations..."
php artisan migrate --force 2>/dev/null || true

# Start background IMAP email fetch worker (checks every 60s)
(while true; do sleep 60; php artisan inbound-emails:process >/dev/null 2>&1 || true; done) &

PORT="${PORT:-8080}"
echo "AUURA CRM server running on port $PORT..."
exec php artisan serve --host=0.0.0.0 --port="$PORT"
