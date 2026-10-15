# Alpine plutôt que Debian : même PHP 8.4, mais un système réduit au strict nécessaire.
# Analyse Trivy du 15/10/2026 : 2 failles moyennes, contre 2 critiques et 124 hautes pour php:8.4-fpm (D-033).
FROM php:8.4-fpm-alpine

# Outil officieux mais standard pour installer les extensions PHP
COPY --from=mlocati/php-extension-installer:2 /usr/bin/install-php-extensions /usr/local/bin/
# pdo_mysql : MySQL ; intl : Symfony ; gd + exif : redimensionnement et nettoyage des photos (D-015)
RUN install-php-extensions pdo_mysql intl zip opcache apcu gd exif

RUN apk add --no-cache git unzip

# Même identifiant que www-data sous Debian (33) : les volumes vendor/ et var/ déjà créés restent utilisables.
RUN apk add --no-cache --virtual .modif-utilisateur shadow \
    && groupmod -g 33 www-data \
    && usermod -u 33 -g 33 www-data \
    && apk del .modif-utilisateur

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY php.ini /usr/local/etc/php/conf.d/zz-penderie.ini

WORKDIR /var/www/api

# Tout le conteneur tourne en www-data, l'utilisateur de PHP-FPM : les fichiers créés par la ligne
# de commande (cache, photos des tests) restent ainsi modifiables par l'application.
# Les volumes vendor/ et var/ (compose.yaml) héritent de ces droits à leur création.
RUN mkdir -p vendor var && chown -R www-data:www-data /var/www/api
ENV COMPOSER_HOME=/tmp/composer
USER www-data
