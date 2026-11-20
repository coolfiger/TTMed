#!/usr/bin/env bash
# Run as: bash deploy/pull.sh
# Must be executed from the app root as shawn (sudo access required)

set -e

APP="/home/coolfiger/web/ttmed.app/public_html"
PHP="sudo -u coolfiger php"
GIT="sudo -u coolfiger git -C $APP"

echo "→ Pulling latest code..."
$GIT pull origin main

echo "→ Clearing all caches..."
$PHP $APP/artisan optimize:clear

echo "→ Clearing event cache..."
$PHP $APP/artisan event:clear 2>/dev/null || true

echo "→ Clearing compiled files..."
$PHP $APP/artisan clear-compiled 2>/dev/null || true

echo "→ Running migrations..."
$PHP $APP/artisan migrate --force

echo "→ Re-caching config and routes..."
$PHP $APP/artisan config:cache
$PHP $APP/artisan route:cache

echo "→ Pre-compiling views..."
$PHP $APP/artisan view:cache

echo "→ Restarting PHP-FPM to flush OPcache..."
sudo systemctl restart php8.3-fpm

echo "✓ Deploy complete."
