FROM php:8.4-apache

WORKDIR /var/www/html

# System
RUN apt-get update && apt-get install -y \
    git unzip curl libzip-dev nano openssh-client \
    nodejs npm \
    && docker-php-ext-install pdo pdo_mysql pcntl zip \
    && pecl install redis \
    && docker-php-ext-enable redis

# ssh dir
RUN mkdir -p /var/www/.ssh && \
    chmod 700 /var/www/.ssh

# Apache
RUN a2enmod rewrite

# Composer
COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

# Copy project
COPY . . 

# Perms
RUN chown -R www-data:www-data /var/www/html
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
RUN chown -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

COPY docker/000-default.conf /etc/apache2/sites-enabled/000-default.conf

# Laravel
RUN composer install \
    --no-dev \
    --prefer-dist \
    --no-interaction \
    --optimize-autoloader

# Vuej.js
RUN npm install && npm run build

#COPY docker/entrypoint.sh /entrypoint.sh
#RUN chmod +x /entrypoint.sh

#ENTRYPOINT ["/entrypoint.sh"]

EXPOSE 80