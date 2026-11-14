FROM node:22-alpine AS assets

WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci

COPY vite.config.js ./
COPY resources ./resources
COPY public ./public
RUN npm run build

FROM php:8.2-cli

RUN apt-get update && apt-get install -y --no-install-recommends \
    curl \
    unzip \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader --no-scripts

COPY . .
COPY --from=assets /app/public/build ./public/build
RUN composer dump-autoload --no-dev --optimize \
    && mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/testing storage/framework/views storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

ENV PHP_CLI_SERVER_WORKERS=4

EXPOSE 8000

USER www-data

CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
