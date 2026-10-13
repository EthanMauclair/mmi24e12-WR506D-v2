FROM php:8.4-fpm

# Outil officieux mais standard pour installer les extensions PHP
COPY --from=mlocati/php-extension-installer:2 /usr/bin/install-php-extensions /usr/local/bin/
# pdo_mysql : MySQL ; intl : Symfony ; gd + exif : redimensionnement et nettoyage des photos (D-015)
RUN install-php-extensions pdo_mysql intl zip opcache apcu gd exif

RUN apt-get update \
    && apt-get install -y --no-install-recommends git unzip \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY php.ini /usr/local/etc/php/conf.d/zz-penderie.ini

WORKDIR /var/www/api
