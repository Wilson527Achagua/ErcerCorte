# 1. IMAGEN BASE: Usamos una imagen que ya trae PHP
FROM php:8.1-apache

# 2. INSTALAR DEPENDENCIAS DE SISTEMA (Ahora incluimos librerías SSL y utilidades)
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libxml2-dev \
    libssl-dev \
    openssl \
    libgbm-dev \
    libfontconfig1 \
    libgtk-3-0 \
    libsecret-1-0 \
    libnss3 \
    libxshmfence-dev \
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
    
# 9. INSTALAR NODE.JS (PARA GENERACIÓN DE PDF) Y PUPPETEER
# Instalamos la última versión de Node.js y NPM
RUN apt-get update && apt-get install -y \
    nodejs \
    npm

# Instalar Puppeteer y sus dependencias de Chrome
WORKDIR /var/www/html/utils
# NOTA: Debes tener un package.json con puppeteer en utils
# Asumimos que lo tienes o lo instalamos directamente si no existe.
# Si tu generate_pdf.js está en /var/www/html/utils/, este es el lugar correcto.

# Si tienes un package.json en /utils, usa:
# RUN npm install

# Si no tienes package.json, instala solo puppeteer aquí:
RUN npm install puppeteer

WORKDIR /var/www/html 

# 10. CONFIGURAR APACHE
# Se asegura de que las reescrituras de URL funcionen si usas .htaccess
RUN a2enmod rewrite

# 11. SOBREESCRIBIR LA CONFIGURACIÓN DE PUERTO DE APACHE
# Esta línea le dice a Apache que escuche en el puerto que Render le pasa a través de la variable de entorno $PORT
ENV PORT 10000
RUN sed -i 's/Listen 80/Listen ${PORT}/g' /etc/apache2/ports.conf /etc/apache2/sites-enabled/*.conf

# 12. EXPOSICIÓN Y ARRANQUE (Apache es el servidor predeterminado)
EXPOSE 80
