FROM php:8.5.3-apache

WORKDIR /var/www/html

# System
RUN apt-get update && apt-get install -y \
    git unzip curl libzip-dev nano openssh-client \
    nodejs npm \
    && docker-php-ext-install pdo pdo_mysql pcntl zip \
    && pecl install redis \
    && docker-php-ext-enable redis

# Apache
RUN a2enmod rewrite

# Composer
COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

# Copy project
COPY . . 

# Perms
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 775 /var/www/html/storage \
    && chmod -R 775 /var/www/html/bootstrap/cache

COPY docker/000-default.conf /etc/apache2/sites-enabled/000-default.conf

# Laravel
RUN composer install \
    --no-dev \
    --prefer-dist \
    --no-interaction \
    --optimize-autoloader

# Vuej.js
RUN npm install && npm run build

EXPOSE 80