# 1. IMAGEN BASE: Usamos una imagen que ya trae PHP
FROM php:8.1-apache

# 2. INSTALAR DEPENDENCIAS DE SISTEMA (Ahora incluimos librerías SSL)
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libxml2-dev \
    libssl-dev \
    openssl \
    && rm -rf /var/lib/apt/lists/* # Limpieza al final

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

# 8. SOLUCIÓN AL ERROR DE PERMISOS DE UPLOADS
# Crea la carpeta 'uploads' si no existe y le da permisos de escritura al usuario web.
RUN mkdir -p /var/www/html/uploads \
    && chown -R www-data:www-data /var/www/html/uploads \
    && chmod -R 775 /var/www/html/uploads


# 9. CONFIGURAR APACHE
# Se asegura de que las reescrituras de URL funcionen si usas .htaccess
RUN a2enmod rewrite

# 10. SOBREESCRIBIR LA CONFIGURACIÓN DE PUERTO DE APACHE
# Esta línea le dice a Apache que escuche en el puerto que Render le pasa a través de la variable de entorno $PORT
ENV PORT 10000
RUN sed -i 's/Listen 80/Listen ${PORT}/g' /etc/apache2/ports.conf /etc/apache2/sites-enabled/*.conf

# 11. EXPOSICIÓN Y ARRANQUE (Apache es el servidor predeterminado)
EXPOSE 80
