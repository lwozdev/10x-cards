FROM php:8.3-fpm-alpine

# Install system dependencies
RUN apk add --no-cache \
    git \
    unzip \
    curl \
    postgresql-dev \
    icu-dev \
    libzip-dev \
    linux-headers \
    $PHPIZE_DEPS

# Install PHP extensions
RUN docker-php-ext-install \
    pdo \
    pdo_pgsql \
    intl \
    zip \
    opcache

# Install and configure Xdebug
RUN pecl install xdebug-3.4.0 && \
    docker-php-ext-enable xdebug

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Configure PHP
RUN mv "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"

# Copy Xdebug configuration
COPY docker/php/xdebug.ini $PHP_INI_DIR/conf.d/docker-php-ext-xdebug.ini

# Create working directory
WORKDIR /var/www/html

# Copy application files
COPY . .

# Install PHP dependencies
RUN composer install --no-interaction --optimize-autoloader

# Set permissions
RUN chown -R www-data:www-data /var/www/html/var

# Expose port 9000 for PHP-FPM
EXPOSE 9000

CMD ["php-fpm"]
