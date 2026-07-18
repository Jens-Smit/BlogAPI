# Basis-Image: PHP 8.2 mit Apache
FROM php:8.2-apache

# Abhängigkeiten installieren
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libzip-dev \
    libpq-dev \
    && docker-php-ext-install pdo pdo_mysql zip

# Composer installieren
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Symfony CLI installieren
RUN curl -sS https://get.symfony.com/cli/installer | bash

# Arbeitsverzeichnis setzen
WORKDIR /var/www/html

# PHP-Konfiguration kopieren
COPY docker/php/php.ini /usr/local/etc/php/conf.d/custom.ini

# Symfony-Projekt kopieren (wird durch Volume überschrieben)
COPY . .

# Apache-Konfiguration für Symfony anpassen
RUN sed -i 's/DocumentRoot \/var\/www\/html/DocumentRoot \/var\/www\/html\/public/' /etc/apache2/sites-enabled/000-default.conf
RUN a2enmod rewrite

# Port freigeben
EXPOSE 8000