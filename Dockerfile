FROM php:8.2-cli

LABEL maintainer="ticket-ai"

WORKDIR /var/www/html

# Dependencias del sistema necesarias para Laravel + PostgreSQL
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
       git \
       unzip \
       libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql \
    && rm -rf /var/lib/apt/lists/*

# Instalar Composer
RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" \
    && php composer-setup.php --install-dir=/usr/local/bin --filename=composer \
    && php -r "unlink('composer-setup.php');"

# Copiamos composer.* primero para aprovechar cache de dependencias
COPY composer.json composer.lock ./

RUN composer install --no-interaction --no-progress --prefer-dist

# Copiamos el resto del código de la aplicación
COPY . .

# Puerto por defecto de php artisan serve
EXPOSE 8000

# Comando por defecto: lanzar el servidor de desarrollo de Laravel
CMD php artisan serve --host=0.0.0.0 --port=8000


