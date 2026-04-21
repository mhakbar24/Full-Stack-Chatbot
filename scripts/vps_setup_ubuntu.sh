#!/usr/bin/env bash
set -euo pipefail

# Basic Ubuntu VPS setup for Laravel app
# Run as root: sudo bash scripts/vps_setup_ubuntu.sh

APP_USER="www-data"
APP_DIR="/var/www/chatbot_backend"
PHP_FPM_SERVICE="php8.2-fpm"

echo "[1/7] Updating apt packages"
apt update -y
apt upgrade -y

echo "[2/7] Installing system dependencies"
apt install -y nginx git curl unzip supervisor redis-server \
  php php-cli php-fpm php-mbstring php-xml php-bcmath php-curl php-zip php-intl php-mysql

if ! command -v composer >/dev/null 2>&1; then
  echo "[3/7] Installing Composer"
  EXPECTED_SIG="$(curl -fsSL https://composer.github.io/installer.sig)"
  php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
  ACTUAL_SIG="$(php -r "echo hash_file('sha384', 'composer-setup.php');")"
  if [ "$EXPECTED_SIG" != "$ACTUAL_SIG" ]; then
    echo "ERROR: Invalid Composer installer signature" >&2
    rm -f composer-setup.php
    exit 1
  fi
  php composer-setup.php --install-dir=/usr/local/bin --filename=composer
  rm -f composer-setup.php
fi

echo "[4/7] Ensuring app directory exists"
mkdir -p "$APP_DIR"
chown -R "$APP_USER":"$APP_USER" "$APP_DIR"

# Node.js is optional for first build; remove if assets are built in CI
if ! command -v node >/dev/null 2>&1; then
  echo "[5/7] Installing Node.js LTS"
  curl -fsSL https://deb.nodesource.com/setup_lts.x | bash -
  apt install -y nodejs
fi

echo "[6/7] Enabling services"
systemctl enable nginx
systemctl enable "$PHP_FPM_SERVICE"
systemctl enable supervisor
systemctl enable redis-server

systemctl restart nginx
systemctl restart "$PHP_FPM_SERVICE"
systemctl restart supervisor
systemctl restart redis-server

echo "[7/7] Done"
echo "Next: clone repo into $APP_DIR, copy .env, then run scripts/deploy_vps.sh"
