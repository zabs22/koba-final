ARG PHP_VERSION=8.3
ARG NODE_VERSION=22

# ---------------------------------------------------------------------------
# 1. PHP dependencies
# ---------------------------------------------------------------------------
FROM composer:2 AS vendor
WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install \
        --no-dev \
        --no-interaction \
        --no-progress \
        --no-scripts \
        --no-autoloader \
        --prefer-dist

COPY . .
RUN composer dump-autoload --no-dev --optimize --classmap-authoritative --no-scripts

# ---------------------------------------------------------------------------
# 2. Frontend assets
# ---------------------------------------------------------------------------
FROM node:${NODE_VERSION}-alpine AS assets
WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci --no-audit --no-fund

COPY vite.config.js ./
COPY resources ./resources
COPY public ./public
# Tailwind's @source directives read Blade files from these vendor packages.
COPY --from=vendor /app/vendor/laravel/jetstream ./vendor/laravel/jetstream
COPY --from=vendor /app/vendor/laravel/framework/src/Illuminate/Pagination/resources ./vendor/laravel/framework/src/Illuminate/Pagination/resources

RUN npm run dev

# ---------------------------------------------------------------------------
# 3. Runtime
# ---------------------------------------------------------------------------
FROM php:${PHP_VERSION}-apache AS app

# Extra PHP extensions: opcache (speed), intl (Laravel's Number/locale helpers),
# pdo_mysql (in case you switch from SQLite to MySQL). SQLite support is built in.
COPY --from=mlocati/php-extension-installer:2 /usr/bin/install-php-extensions /usr/local/bin/
RUN install-php-extensions opcache intl pdo_mysql \
    && a2enmod rewrite headers expires

COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf
COPY docker/php.ini /usr/local/etc/php/conf.d/zz-koba.ini
COPY docker/entrypoint.sh /usr/local/bin/koba-entrypoint
RUN chmod +x /usr/local/bin/koba-entrypoint

WORKDIR /var/www/html

COPY --chown=www-data:www-data . .
COPY --chown=www-data:www-data --from=vendor /app/vendor ./vendor
COPY --chown=www-data:www-data --from=assets /app/public/build ./public/build

# Package discovery needs no database or app key; caches that read env vars
# (config, routes, views) are built at container start in the entrypoint.
RUN php artisan package:discover --ansi \
    && mkdir -p /var/www/data storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && chown -R www-data:www-data /var/www/data storage bootstrap/cache

ENV APP_ENV=production \
    APP_DEBUG=false \
    LOG_CHANNEL=stderr \
    DB_CONNECTION=sqlite \
    DB_DATABASE=/var/www/data/database.sqlite

VOLUME ["/var/www/data", "/var/www/html/storage/app"]
EXPOSE 80

HEALTHCHECK --interval=30s --timeout=5s --start-period=30s --retries=3 \
    CMD curl -fsS http://localhost/up || exit 1

ENTRYPOINT ["koba-entrypoint"]
CMD ["apache2-foreground"]
