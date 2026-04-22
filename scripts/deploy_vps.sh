#!/usr/bin/env bash
set -euo pipefail

# Laravel VPS deploy script
# Usage:
#   APP_DIR=/var/www/chatbot_backend BRANCH=main bash scripts/deploy_vps.sh

APP_DIR="${APP_DIR:-/var/www/chatbot_backend}"
BRANCH="${BRANCH:-main}"
PHP_BIN="${PHP_BIN:-php}"
COMPOSER_BIN="${COMPOSER_BIN:-composer}"
PHP_FPM_SERVICE="${PHP_FPM_SERVICE:-php8.2-fpm}"
SUPERVISOR_PROGRAM="${SUPERVISOR_PROGRAM:-laravel-worker:*}"

cd "$APP_DIR"

echo "[1/10] Maintenance mode"
$PHP_BIN artisan down || true

echo "[2/10] Pull latest source"
git fetch origin "$BRANCH"
git reset --hard "origin/$BRANCH"

echo "[3/10] Install PHP dependencies"
$COMPOSER_BIN install --no-interaction --prefer-dist --no-dev --optimize-autoloader

echo "[4/10] Install/build frontend assets"
if [ -f package-lock.json ]; then
  npm ci
  npm run build
elif [ -f package.json ]; then
  npm install
  npm run build
else
  echo "No package.json found, skipping frontend build"
fi

echo "[5/10] Fix runtime permissions"
chown -R www-data:www-data "$APP_DIR/storage" "$APP_DIR/bootstrap/cache" "$APP_DIR/public"
chmod -R ug+rwx "$APP_DIR/storage" "$APP_DIR/bootstrap/cache"

echo "[6/10] Ensure storage symlink"
$PHP_BIN artisan storage:link || true
if [ ! -L "$APP_DIR/public/storage" ]; then
  ln -sfn "$APP_DIR/storage/app/public" "$APP_DIR/public/storage"
fi

echo "[7/10] Run database migration"
$PHP_BIN artisan migrate --force

echo "[8/10] Optimize caches"
$PHP_BIN artisan optimize:clear
$PHP_BIN artisan config:cache
$PHP_BIN artisan route:cache
$PHP_BIN artisan view:cache

echo "[9/10] Restart services"
systemctl restart "$PHP_FPM_SERVICE"
supervisorctl reread || true
supervisorctl update || true
supervisorctl restart "$SUPERVISOR_PROGRAM" || true

echo "[10/10] App up"
$PHP_BIN artisan up

echo "Deploy finished successfully"
