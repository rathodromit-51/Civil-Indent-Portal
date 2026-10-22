ARG PHP_VERSION=8.2
FROM serversideup/php:${PHP_VERSION}-fpm-nginx

WORKDIR /var/www/html

# Copy application files (Laravel project lives in ./src)
COPY --chown=www-data:www-data ./src /var/www/html

# Production env
RUN if [ -f .env ]; then sed -i 's/APP_ENV=.*/APP_ENV=production/' .env; fi

# Writable dirs for Laravel
RUN mkdir -p storage/framework/views storage/framework/sessions storage/framework/cache storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# The base image already provides the entrypoint and cmd.
