#!/usr/bin/env bash
# Run as: bash deploy/pull.sh
# Must be executed from the app root as shawn (sudo access required)

set -e

APP="/home/coolfiger/web/ttmed.app/public_html"
PHP="sudo -u coolfiger php"
GIT="sudo -u coolfiger git -C $APP"

echo "→ Pulling latest code..."
$GIT pull origin main

echo "→ Clearing compiled views..."
$PHP $APP/artisan view:clear

echo "→ Clearing application cache..."
$PHP $APP/artisan cache:clear

echo "→ Clearing route cache..."
$PHP $APP/artisan route:clear

echo "→ Clearing config cache..."
$PHP $APP/artisan config:clear

echo "→ Running migrations..."
$PHP $APP/artisan migrate --force

echo "✓ Deploy complete."
