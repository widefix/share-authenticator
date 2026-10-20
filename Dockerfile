FROM node:22-alpine AS frontend

WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci

COPY resources ./resources
COPY vite.config.js ./
RUN npm run build


FROM composer:2 AS dependencies

WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --no-interaction \
    --no-progress \
    --no-scripts \
    --prefer-dist

COPY . .
RUN composer dump-autoload \
    --classmap-authoritative \
    --no-dev \
    --no-interaction


FROM dunglas/frankenphp:1-php8.4-bookworm AS application

WORKDIR /app

RUN cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
    && install-php-extensions intl opcache pdo_pgsql

COPY --from=dependencies /app /app
COPY --from=frontend /app/public/build /app/public/build
COPY docker/start-app /usr/local/bin/start-app

RUN chmod +x /usr/local/bin/start-app \
    && mkdir -p \
        storage/app/private \
        storage/app/public \
        storage/framework/cache/data \
        storage/framework/sessions \
        storage/framework/views \
        storage/logs \
        bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

ENV APP_ENV=production \
    SERVER_NAME=:80 \
    SERVER_ROOT=public/

EXPOSE 80

ENTRYPOINT ["start-app"]
