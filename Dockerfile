# Foloseşte Apache + PHP 8.1
FROM php:8.1-apache

# Instalăm extensiile și utilitarele necesare
RUN apt-get update && apt-get install -y \
    libpq-dev \
    git \
    unzip \
    curl \
    && docker-php-ext-install mysqli pdo_pgsql \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Setăm directorul de lucru
WORKDIR /var/www/html

# Instalăm Composer global
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Copiem fișierele proiectului
COPY src/ /var/www/html/

# Instalăm dependențele (după ce avem tot codul)
RUN composer install

# Permisiuni corecte
RUN chown -R www-data:www-data /var/www/html

# Expune portul 80
EXPOSE 80