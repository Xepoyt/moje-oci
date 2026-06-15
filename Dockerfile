FROM php:8.3-apache

# 1. Instalace systémových nástrojů a PHP rozšíření, která Nette vyžaduje
RUN apt-get update && apt-get install -y \
    libzip-dev \
    zip \
    && docker-php-ext-install zip pdo pdo_mysql

# 2. Zapnutí mod_rewrite v Apache (nutné pro hezká URL v Nette)
RUN a2enmod rewrite

# 3. Nastavení Apache tak, aby směřoval do složky app/www (veřejný adresář Nette)
ENV APACHE_DOCUMENT_ROOT /var/www/html/app/www
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

WORKDIR /var/www/html