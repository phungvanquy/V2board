#!/bin/sh

# Fix ownership on bind-mounted files so www-data (php-fpm) can write to them
chown www-data:www-data /var/www/v2board/.env 2>/dev/null
chown www-data:www-data /var/www/v2board/config/v2board.php 2>/dev/null

# Ensure storage directories exist (important when using named volumes)
mkdir -p /var/www/v2board/storage/framework/cache/data
mkdir -p /var/www/v2board/storage/framework/sessions
mkdir -p /var/www/v2board/storage/framework/views
mkdir -p /var/www/v2board/storage/logs
mkdir -p /var/www/v2board/storage/views

# Ensure storage and bootstrap/cache are writable by www-data
chown -R www-data:www-data /var/www/v2board/storage 2>/dev/null
chmod -R 775 /var/www/v2board/storage 2>/dev/null
chown -R www-data:www-data /var/www/v2board/bootstrap/cache 2>/dev/null
chmod -R 775 /var/www/v2board/bootstrap/cache 2>/dev/null

# Clear cached config so .env changes take effect on restart
php /var/www/v2board/artisan config:clear 2>/dev/null
php /var/www/v2board/artisan view:clear 2>/dev/null

# Fix hardcoded API host in frontend theme env.js — use APP_URL from .env or empty for relative
APP_URL=$(grep -oP '^APP_URL=\K.*' /var/www/v2board/.env 2>/dev/null | tr -d '\r')
if [ "$APP_URL" = "http://localhost" ] || [ "$APP_URL" = "" ]; then
  HOST_VALUE=""
else
  HOST_VALUE="$APP_URL"
fi
# Patch user theme env.js
THEME_ENV="/var/www/v2board/public/theme/default/assets/env.js"
if [ -f "$THEME_ENV" ]; then
  sed -i "s|host: '.*'|host: '${HOST_VALUE}'|" "$THEME_ENV"
fi

exec "$@"

