#!/bin/sh

# Fix ownership on bind-mounted files so www-data (php-fpm) can write to them
chown www-data:www-data /var/www/v2board/.env /var/www/v2board/config/v2board.php 2>/dev/null

exec "$@"

