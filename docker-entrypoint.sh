#!/usr/bin/env bash
set -e

# Wait for db connection
echo "==> Waiting for database connection..."
MAX_TRIES=30
TRIES=0

until php -r "try { new PDO('mysql:host=' . getenv('DB_HOST') . ';port=' . getenv('DB_PORT'), getenv('DB_USERNAME'), getenv('DB_PASSWORD')); exit(0); } catch (Exception \$e) { exit(1); }" || [ $TRIES -eq $MAX_TRIES ]; do
    TRIES=$((TRIES+1))
    echo "    Attempt $TRIES/$MAX_TRIES: Database not ready, retrying in 2s..."
    sleep 2
done

if [ $TRIES -eq $MAX_TRIES ]; then
    echo "ERROR: Database connection timed out after $MAX_TRIES attempts."
    exit 1
fi

echo "==> Database connection established."

# Fix storage permissions FIRST (before artisan commands write any temp files)
echo "==> Fixing storage permissions..."
mkdir -p /var/www/html/storage/framework/cache/data \
         /var/www/html/storage/framework/sessions \
         /var/www/html/storage/framework/views \
         /var/www/html/storage/logs \
         /var/www/html/bootstrap/cache
chmod -R 777 /var/www/html/storage /var/www/html/bootstrap/cache

# Warm up Laravel configuration cache
echo "==> Optimizing application configuration..."
php artisan config:cache --no-ansi
php artisan route:cache --no-ansi
php artisan view:cache --no-ansi

# Publish Telescope migrations only on first boot (avoids duplicate migration on restart)
if [ ! -f /var/www/html/config/telescope.php ]; then
    echo "==> Publishing Telescope assets (first boot)..."
    php artisan telescope:install --no-ansi
else
    echo "==> Telescope already installed, skipping."
fi

# Run pending migrations
echo "==> Running database migrations..."
php artisan migrate --force --no-ansi

# Make sure JWT secret is set
if [ -z "$JWT_SECRET" ]; then
    echo "==> Generating JWT secret key..."
    php artisan jwt:secret --force --no-ansi
else
    echo "==> JWT secret is already configured."
fi

# Start php-fpm
echo "==> Starting php-fpm..."
exec php-fpm
