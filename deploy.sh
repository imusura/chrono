#!/bin/bash
set -e

cd /var/www/chrono

git pull origin main
composer install --no-dev --optimize-autoloader --no-interaction
npm ci && npx vite build

php artisan migrate --force

php artisan config:cache
php artisan route:cache
php artisan view:cache
