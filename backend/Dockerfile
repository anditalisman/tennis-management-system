FROM php:8.4-fpm-alpine AS app

RUN apk add --no-cache --virtual .build-deps \
        $PHPIZE_DEPS \
        libpng-dev libzip-dev libjpeg-turbo-dev freetype-dev \
        icu-dev oniguruma-dev libxml2-dev sqlite-dev \
    && apk add --no-cache bash git curl icu-libs libzip libpng libjpeg-turbo freetype tzdata \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" pdo_mysql pdo_sqlite mbstring exif pcntl bcmath gd zip intl opcache \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del .build-deps

ENV TZ=Asia/Makassar

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY docker/php.ini /usr/local/etc/php/conf.d/99-ztcms.ini
COPY docker/opcache.ini /usr/local/etc/php/conf.d/98-opcache.ini
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

WORKDIR /var/www/html

COPY composer.json composer.lock ./
RUN composer install --no-scripts --no-autoloader --prefer-dist --no-interaction

COPY . .
RUN composer dump-autoload --optimize \
    && addgroup -g 1000 www && adduser -G www -u 1000 -D www \
    && chown -R www:www /var/www/html/vendor /var/www/html/storage /var/www/html/bootstrap/cache

USER www

EXPOSE 9000
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["php-fpm"]

# ============================================================
# Nginx image - bakes the webroot in at build time so nginx never depends on
# the "app" stage's live code bind-mount (which production doesn't even use
# anymore, see docker-compose.yml, but this stage still shouldn't need one).
# Reuses "app"'s already composer-installed /var/www/html rather than
# duplicating the COPY + composer steps.
# ============================================================
FROM nginx:1.27-alpine AS nginx

COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf
COPY --from=app /var/www/html /var/www/html

EXPOSE 80
CMD ["nginx", "-g", "daemon off;"]
