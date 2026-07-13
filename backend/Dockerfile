# Usar la imagen oficial de PHP con FPM
FROM php:8.2-fpm

# Instalar dependencias del sistema y extensiones de PHP para Laravel/MySQL
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Directorio de trabajo
WORKDIR /var/www/backend

# Copiar el código de Laravel
COPY . .

# Permisos para las carpetas de Laravel
RUN chown -R www-data:www-data /var/www/backend/storage /var/www/backend/bootstrap/cache