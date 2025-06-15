# Foloseşte Apache + PHP 8.1
FROM php:8.1-apache

# 1) Instalăm extensiile și utilitarele necesare
RUN apt-get update && apt-get install -y \
    libpq-dev \
    git \
    unzip \
    curl \
    dos2unix \
    && docker-php-ext-install mysqli pdo_pgsql \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# 2) Setăm directorul de lucru
WORKDIR /var/www/html

# 3) Instalăm Composer global
RUN curl -sS https://getcomposer.org/installer \
    | php -- --install-dir=/usr/local/bin --filename=composer

# 4) Copiem codul aplicației
COPY src/ /var/www/html/
COPY assets/ /var/www/html/assets/

# 5) Copiem entrypoint-ul în rădăcina containerului
COPY entrypoint.sh /entrypoint.sh

# 6) Convertim CRLF→LF (în caz că ai editat în Windows) și marcăm ca executabil
RUN dos2unix /entrypoint.sh \
    && chmod +x /entrypoint.sh

# 7) Instalăm dependențele PHP (optimizat pentru producție)
RUN composer install --no-dev --optimize-autoloader

# 8) Corectăm permisiunile pe cod
RUN chown -R www-data:www-data /var/www/html

# 9) Expunem portul HTTP
EXPOSE 80

# 10) Definim entrypoint-ul
ENTRYPOINT ["/entrypoint.sh"]
