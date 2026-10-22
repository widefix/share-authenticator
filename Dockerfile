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


# PHP 8.4.27 security release: CVE-2026-109618 and CVE-2026-109619.
FROM dunglas/frankenphp:1-php8.4-bookworm@sha256:49369ff211d81dc30a15890d58c0651d639aef0d9c0a9b91bcc6f387a77bd7a2 AS application

WORKDIR /app

RUN php -r 'exit(version_compare(PHP_VERSION, "8.4.27", ">=") ? 0 : 1);' \
    && cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
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
