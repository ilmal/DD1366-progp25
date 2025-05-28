FROM php:7.4-apache

   # Install dependencies and PostgreSQL extension
   RUN apt-get update && apt-get install -y \
       libpq-dev \
       && docker-php-ext-install pdo pdo_pgsql \
       && docker-php-ext-enable pdo_pgsql

   # Enable Apache rewrite module
   RUN a2enmod rewrite

   # Set working directory
   WORKDIR /var/www/html