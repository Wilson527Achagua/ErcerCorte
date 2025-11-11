# 1. IMAGEN BASE: Usamos una imagen que ya trae PHP
FROM php:8.1-apache

# 2. INSTALAR DEPENDENCIAS DE SISTEMA (para librerías como PHPMailer y MongoDB)
# Instalamos git, unzip (para composer) y las extensiones necesarias
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libxml2-dev

# 3. INSTALAR EXTENSIONES DE PHP: Necesarias para MongoDB y ZIP
RUN docker-php-ext-install soap bcmath pdo_mysql \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# 4. INSTALAR DRIVER DE MONGODB
# Necesitas estas dos líneas para que PHP sepa cómo hablar con MongoDB
RUN pecl install mongodb \
    && docker-php-ext-enable mongodb

# 5. INSTALAR COMPOSER GLOBALMENTE
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 6. COPIAR EL CÓDIGO FUENTE
# El directorio /var/www/html es donde Apache busca los archivos
COPY . /var/www/html

# 7. EJECUTAR COMPOSER (Instalar PHPMailer, TCPDF, etc.)
# Se ejecuta en el directorio /var/www/html que ahora tiene composer.json
WORKDIR /var/www/html
RUN composer install --no-dev --prefer-dist

# 8. CONFIGURAR APACHE
# Se asegura de que las reescrituras de URL funcionen si usas .htaccess
RUN a2enmod rewrite

# 9. EXPOSICIÓN Y ARRANQUE (Apache es el servidor predeterminado)
EXPOSE 80
