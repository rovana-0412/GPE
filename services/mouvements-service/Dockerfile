FROM php:8.2-cli

RUN apt-get update && apt-get install -y --no-install-recommends \
    curl \
    libpq-dev \
    unzip \
    && docker-php-ext-install pdo_pgsql \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader --no-scripts

COPY . .
RUN composer dump-autoload --no-dev --optimize \
    && mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/testing storage/framework/views storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

ENV PHP_CLI_SERVER_WORKERS=4

EXPOSE 8000

USER www-data

CMD ["sh", "-c", "php artisan migrate --force --seed && exec php artisan serve --no-reload --host=0.0.0.0 --port=8000"]
