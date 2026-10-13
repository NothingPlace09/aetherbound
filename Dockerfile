# Aetherbound site for Coolify (Build Pack: Dockerfile). Apache + PHP 8.3 with gd, curl, pdo_sqlite.
FROM php:8.3-apache

RUN apt-get update \
 && apt-get install -y --no-install-recommends libpng-dev libjpeg-dev libwebp-dev \
 && docker-php-ext-configure gd --with-jpeg --with-webp \
 && docker-php-ext-install -j"$(nproc)" gd \
 && a2enmod rewrite headers expires deflate \
 && cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
 && rm -rf /var/lib/apt/lists/*

COPY docker/site.conf /etc/apache2/conf-available/aetherbound.conf
RUN a2enconf aetherbound

COPY . /var/www/html/
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 80
ENTRYPOINT ["entrypoint.sh"]
