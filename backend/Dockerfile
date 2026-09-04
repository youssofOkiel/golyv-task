FROM php:8.4-fpm-bookworm

RUN apt-get update && apt-get install -y --no-install-recommends \
    git unzip libpq-dev libzip-dev \
    && docker-php-ext-install pdo_pgsql pgsql zip \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY composer.json composer.lock* ./
RUN if [ -f composer.json ]; then composer install --no-interaction --prefer-dist --no-scripts --no-autoloader || true; fi

COPY . .

RUN chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R ug+rwx storage bootstrap/cache

EXPOSE 9000

CMD ["php-fpm"]
