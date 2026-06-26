FROM composer:2.2 AS vendor_builder

WORKDIR /app

COPY composer.json composer.lock ./

RUN composer install \
    --no-interaction \
    --no-dev \
    --no-scripts \
    --optimize-autoloader \
    --ignore-platform-reqs

FROM php:8.3-fpm-alpine

LABEL author="DevkhaledWaleed"

WORKDIR /var/www/html

RUN apk add --no-cache \
    bash \
    libzip-dev \
    libxml2-dev \
    icu-dev \
    sqlite-dev \
    && docker-php-ext-configure intl \
    && docker-php-ext-install \
        pdo_mysql \
        pdo_sqlite \
        bcmath \
        zip \
        opcache \
        intl \
        pcntl


COPY --from=vendor_builder /app/vendor ./vendor
COPY . .

COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint
RUN chmod +x /usr/local/bin/docker-entrypoint

RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

EXPOSE 9000

ENTRYPOINT ["docker-entrypoint"]
