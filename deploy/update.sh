#!/bin/bash
# STOCK365 — Script de actualización (post-deploy)
# Uso: bash /var/www/stock365/deploy/update.sh
set -e

APP_DIR="/var/www/stock365"
PHP="php8.3"

echo "→ Actualizando STOCK365..."

cd "$APP_DIR"

# Borrar caches antes de pull
$PHP artisan down --quiet

git fetch origin
git reset --hard origin/main

composer install --no-dev --optimize-autoloader --no-interaction --quiet

$PHP artisan migrate --force --quiet

# Recompilar caches
$PHP artisan config:cache --quiet
$PHP artisan route:cache --quiet
$PHP artisan view:cache --quiet

chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

$PHP artisan up --quiet

systemctl restart stock365-worker stock365-scheduler

echo "✅ Actualización completada"
