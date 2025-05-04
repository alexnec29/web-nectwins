# Foloseşte Apache + PHP 8.1
FROM php:8.1-apache

# Instalăm extensia mysqli (pentru conectare la MySQL)
RUN docker-php-ext-install mysqli

# Copiază tot conţinutul directorului în document root Apache
COPY src/ /var/www/html/

# Pune drepturile corecte pe fişiere
RUN chown -R www-data:www-data /var/www/html

# Expune portul 80
EXPOSE 80
