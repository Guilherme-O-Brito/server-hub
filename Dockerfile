FROM composer:2.9.8 AS composer-build

WORKDIR /app

COPY composer.json composer.lock ./

# composer dependencies
RUN composer install \
--no-dev \
--prefer-dist \
--no-interaction \
--no-progress \
--no-scripts

COPY . .

RUN composer dump-autoload --optimize --no-dev \
    && composer run-script post-autoload-dump

FROM node:24-bookworm-slim AS frontend

WORKDIR /app

COPY package*.json ./

RUN npm ci

COPY . .

RUN npm run build

FROM dunglas/frankenphp:1.12.3-php8.5.6-bookworm

WORKDIR /app

# php dependencies
RUN install-php-extensions \
    pdo_mysql \
    redis \
    pcntl \
    bcmath \
    zip

# Copy project
COPY . . 
COPY --from=composer-build /app/vendor ./vendor
COPY --from=frontend /app/public/build ./public/build

# Perms
RUN chown -R www-data:www-data /app/storage && chown -R www-data:www-data /app/bootstrap/cache

RUN setcap -r /usr/local/bin/frankenphp

USER www-data

EXPOSE 8080

CMD ["frankenphp", "php-server", "--listen", ":8080", "-r", "public/"]