# syntax=docker/dockerfile:1

# Composer binary only; pinned so builds are reproducible.
FROM composer:2.10.3 AS composer

# FrankenPHP (Caddy + PHP 8.3) serves Laravel directly, replacing
# `php artisan serve` and the PHP-FPM + Nginx combination.
FROM dunglas/frankenphp:1.13.2-php8.3-bookworm

RUN install-php-extensions \
        pdo_mysql \
        intl \
        zip \
        opcache \
        pcntl

COPY --from=composer /usr/bin/composer /usr/bin/composer

WORKDIR /app

ENV COMPOSER_ALLOW_SUPERUSER=1

# Install dependencies before copying the application code so this layer
# stays cached as long as composer.json and composer.lock do not change.
COPY composer.json composer.lock ./
RUN composer install \
        --no-dev \
        --no-scripts \
        --no-autoloader \
        --prefer-dist \
        --no-interaction \
        --no-progress

COPY . .

RUN composer dump-autoload --optimize --classmap-authoritative --no-dev \
    && php artisan package:discover --ansi \
    && chmod +x docker/entrypoint.sh \
    && mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache

COPY docker/php.ini $PHP_INI_DIR/conf.d/zz-app.ini
COPY docker/Caddyfile /etc/frankenphp/Caddyfile

# Secrets such as APP_KEY and DB_PASSWORD are supplied at runtime through
# environment variables; none are baked into the image.
ENV APP_ENV=production \
    APP_DEBUG=false \
    LOG_CHANNEL=stderr

EXPOSE 8000

ENTRYPOINT ["docker/entrypoint.sh"]
CMD ["frankenphp", "run", "--config", "/etc/frankenphp/Caddyfile"]
