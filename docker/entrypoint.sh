#!/bin/sh

# Fix ownership on bind-mounted files so www-data (php-fpm) can write to them
chown www-data:www-data /var/www/v2board/.env 2>/dev/null
chown www-data:www-data /var/www/v2board/config/v2board.php 2>/dev/null

# Ensure storage directories exist and are writable
chown -R www-data:www-data /var/www/v2board/storage 2>/dev/null
chmod -R 775 /var/www/v2board/storage 2>/dev/null
chown -R www-data:www-data /var/www/v2board/bootstrap/cache 2>/dev/null

exec "$@"

