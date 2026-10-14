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

# Tout le conteneur tourne en www-data, l'utilisateur de PHP-FPM : les fichiers créés par la ligne
# de commande (cache, photos des tests) restent ainsi modifiables par l'application.
# Les volumes vendor/ et var/ (compose.yaml) héritent de ces droits à leur création.
RUN mkdir -p vendor var && chown -R www-data:www-data /var/www/api
ENV COMPOSER_HOME=/tmp/composer
USER www-data
