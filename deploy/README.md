# TTMed Deployment Guide

## Prerequisites

- Ubuntu 22.04 LTS / Debian 12
- PHP 8.3 + php8.3-fpm + required extensions (bcmath, ctype, curl, gd, mbstring, mysql, redis, xml, zip)
- MySQL 8.0
- Redis 7+
- Nginx
- Supervisor
- Certbot (Let's Encrypt wildcard cert for `*.ttmed.app`)
- Node 20+ (build only)

---

## 1. TLS Certificate

```bash
certbot certonly --dns-cloudflare \
  --dns-cloudflare-credentials /etc/cloudflare.ini \
  -d ttmed.app \
  -d "*.ttmed.app"
```

---

## 2. Nginx

```bash
cp deploy/nginx/ttmed.conf /etc/nginx/sites-available/ttmed.conf
ln -s /etc/nginx/sites-available/ttmed.conf /etc/nginx/sites-enabled/
nginx -t && systemctl reload nginx
```

---

## 3. Application

```bash
cd /var/www/ttmed
composer install --no-dev --optimize-autoloader
npm ci && npm run build
cp .env.example .env
php artisan key:generate
# Edit .env with your credentials before continuing
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## 4. Database

```bash
# Run platform migrations
php artisan migrate --database=platform --path=database/migrations/platform --force

# Seed the platform (creates the super admin user)
php artisan db:seed --class=PlatformSeeder --database=platform --force
```

Tenant databases are provisioned automatically when a new tenant is created through `platform.ttmed.app/platform/tenants/create`.

---

## 5. Supervisor (Queue Workers + Scheduler)

```bash
cp deploy/supervisor/ttmed-workers.conf /etc/supervisor/conf.d/ttmed-workers.conf
supervisorctl reread
supervisorctl update
supervisorctl start ttmed:*
```

---

## 6. File Storage

```bash
mkdir -p /var/ttmed-storage
chown -R www-data:www-data /var/ttmed-storage
chmod 750 /var/ttmed-storage
```

Set `TENANT_STORAGE_ROOT=/var/ttmed-storage` in `.env`.

---

## 7. Scheduled Backups

Backups run daily at 02:00 via the scheduler. To run manually:

```bash
php artisan tenants:backup --keep=7
# or for one tenant:
php artisan tenants:backup --tenant=medsouth --keep=7
```

---

## 8. Error Monitoring

1. Create a project in [sentry.io](https://sentry.io)
2. Copy the DSN into `.env` as `SENTRY_LARAVEL_DSN=https://...@sentry.io/...`
3. Test: `php artisan sentry:test`

---

## 9. Health Check

```bash
php artisan tenants:health
```

---

## Zero-downtime deploys

```bash
php artisan down --retry=5
git pull origin main
composer install --no-dev --optimize-autoloader
php artisan migrate --force        # platform migrations
php artisan config:cache
php artisan route:cache
php artisan view:cache
supervisorctl restart ttmed:*
php artisan up
```
