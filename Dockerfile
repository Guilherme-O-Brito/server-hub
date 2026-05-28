FROM composer:2.9.8 AS composer-build

WORKDIR /app

COPY . .
# composer dependencies
RUN composer install \
    --no-dev \
    --prefer-dist \
    --no-interaction \
    --optimize-autoloader

FROM node:lts-bookworm AS frontend

WORKDIR /app

COPY package*.json ./

RUN npm install

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
RUN chown -R www-data:www-data /app/storage && chown -R www-data:www-data /app/bootstrap

RUN setcap -r /usr/local/bin/frankenphp

USER www-data


EXPOSE 8080

CMD ["frankenphp", "php-server", "--listen", ":8080", "-r", "public/"]