#!/usr/bin/env bash
echo "Running composer"
composer install --no-dev --working-dir=/var/www/html

echo "Caching config..."
php artisan config:cache

echo "Caching routes..."
php artisan route:cache

echo "Running migrations..."
php artisan migrate:fresh --force

echo "Seeding to db..."
php artisan db:seed --class=DatabaseSeeder --force

#echo "Linking storage to public..."
#php artisan storage:link
