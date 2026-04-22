# Deploy Laravel to VPS (Ubuntu + Nginx)

## 1) Initial server setup (once)
Run as root on VPS:

```bash
cd /var/www
# clone repo or copy project first if not yet available
# git clone <YOUR_REPO_URL> chatbot_backend

cd /var/www/chatbot_backend
sudo bash scripts/vps_setup_ubuntu.sh
```

## 2) Configure environment
Copy env and edit:

```bash
cd /var/www/chatbot_backend
cp .env.example .env
nano .env
```

Minimum recommended production values:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_db
DB_USERNAME=your_user
DB_PASSWORD=your_password

QUEUE_CONNECTION=database
CACHE_STORE=database
SESSION_DRIVER=database
FILESYSTEM_DISK=public

GEMINI_API_KEY=your_gemini_key
GEMINI_MODEL=gemini-2.5-flash
GEMINI_TIMEOUT=60
GEMINI_RETRY_TIMES=1
GEMINI_RETRY_SLEEP_MS=500
```

Generate app key:

```bash
php artisan key:generate
```

## 3) Install Nginx site config

```bash
sudo cp deploy/nginx/chatbot_backend.conf /etc/nginx/sites-available/chatbot_backend
sudo ln -s /etc/nginx/sites-available/chatbot_backend /etc/nginx/sites-enabled/chatbot_backend
sudo nginx -t
sudo systemctl reload nginx
```

## 4) Enable queue worker (recommended)

```bash
sudo cp deploy/supervisor/laravel-worker.conf /etc/supervisor/conf.d/laravel-worker.conf
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start laravel-worker:*
```

## 5) Run deployment

```bash
cd /var/www/chatbot_backend
sudo APP_DIR=/var/www/chatbot_backend BRANCH=main bash scripts/deploy_vps.sh
```

## 6) Optional SSL with Let's Encrypt

```bash
sudo apt install -y certbot python3-certbot-nginx
sudo certbot --nginx -d your-domain.com -d www.your-domain.com
```

## 7) Update deployment (next releases)

```bash
cd /var/www/chatbot_backend
sudo APP_DIR=/var/www/chatbot_backend BRANCH=main bash scripts/deploy_vps.sh
```

## Troubleshooting quick checks

```bash
php -v
php artisan about
php artisan migrate:status
sudo systemctl status nginx
sudo systemctl status php8.2-fpm
sudo supervisorctl status
ls -la storage/logs
ls -la public | grep storage
ls -la public/storage
stat -c "%U:%G %a %n" storage bootstrap/cache
```
