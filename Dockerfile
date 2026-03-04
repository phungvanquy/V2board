FROM php:8.1-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    libsodium-dev \
    zip \
    unzip \
    nginx \
    supervisor \
    && docker-php-ext-install \
        pdo_mysql \
        mbstring \
        exif \
        pcntl \
        bcmath \
        gd \
        zip \
        sodium \
    && pecl install redis igbinary \
    && docker-php-ext-enable redis igbinary \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/v2board

# Copy project files
COPY . /var/www/v2board

# Remove .env if it leaked through (should be excluded by .dockerignore)
RUN rm -f /var/www/v2board/.env

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Install adapterman for PHP 8+
RUN composer require joanhey/adapterman \
    && composer require cedar2025/http-foundation:5.4.x-dev

# Set permissions
RUN chown -R www-data:www-data /var/www/v2board \
    && chmod -R 775 /var/www/v2board/storage \
    && chmod -R 775 /var/www/v2board/bootstrap/cache

# Copy Nginx configuration
COPY docker/nginx.conf /etc/nginx/sites-available/default

# Copy Supervisor configuration
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Copy entrypoint script
COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

# Expose port
EXPOSE 80

# Fix bind-mount permissions at startup, then start Supervisor
ENTRYPOINT ["/entrypoint.sh"]
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]

