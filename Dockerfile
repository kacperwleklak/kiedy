FROM php:8.2-apache

RUN apt-get update && apt-get install -y \
    libpq-dev \
    && docker-php-ext-install pdo pdo_mysql

RUN a2enmod rewrite headers

# Update the default apache site with the config we want
COPY apache-config.conf /etc/apache2/sites-available/000-default.conf
