FROM php:8.3-fpm

RUN docker compose up -d --force-recreate app-get update && apt-get install -y --no-install-recommends \
    git curl zip unzip \
    libpng-dev libonig-dev libxml2-dev libicu-dev libzip-dev \
 && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-install -j$(nproc) pdo_mysql mbstring exif pcntl bcmath gd intl zip

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-scripts --prefer-dist

COPY . .

RUN composer dump-autoload --optimize --no-dev \
 && mkdir -p storage/logs storage/framework/cache storage/framework/sessions storage/framework/views \
 && chown -R www-data:www-data storage bootstrap/cache

EXPOSE 9000
CMD ["php-fpm"]