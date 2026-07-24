# Basis-Image: PHP 8.2 mit Apache
FROM php:8.2-apache

# Abhängigkeiten installieren (GD-Library Anforderungen ergänzt)
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libzip-dev \
    libpq-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd pdo pdo_mysql zip

# Composer installieren
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Symfony CLI installieren
RUN curl -sS https://get.symfony.com/cli/installer | bash

# Arbeitsverzeichnis setzen
WORKDIR /var/www/html

# PHP-Konfiguration kopieren
COPY docker/php/php.ini /usr/local/etc/php/conf.d/custom.ini

# Composer Abhängigkeiten BEIM BUILD installieren
COPY composer.* ./
RUN composer install --no-scripts --no-autoloader --no-interaction

# Symfony-Projekt kopieren
COPY . .
RUN composer dump-autoload --optimize

# Berechtigungen für das Serializer-Verzeichnis und Symfony-Cache-Ordner anpassen
RUN mkdir -p /var/www/html/var/cache /var/www/html/var/log \
    && chown -R www-data:www-data /var/www/html/var/cache /var/www/html/var/log /var/www/html/vendor/ezyang/htmlpurifier/library/HTMLPurifier/DefinitionCache/Serializer

# Apache-Konfiguration für Symfony anpassen
RUN sed -i 's/DocumentRoot \/var\/www\/html/DocumentRoot \/var\/www\/html\/public/' /etc/apache2/sites-enabled/000-default.conf
RUN a2enmod rewrite

# Port freigeben
EXPOSE 80