#!/bin/bash

if [ -f /var/www/html/composer.json ]; then
  echo "Verific dependențele Composer..."
  cd /var/www/html
  composer install --no-interaction --prefer-dist
fi

exec apache2-foreground