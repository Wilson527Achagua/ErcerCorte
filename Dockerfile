# 1. IMAGEN BASE: Usamos una imagen que ya trae PHP
FROM php:8.1-apache

# 2. INSTALAR DEPENDENCIAS DE SISTEMA (Ahora incluimos librerías SSL, utilidades y dependencias de Chrome)
# Nota: 'libxshmfence-dev' es necesario para ciertos entornos headless de Puppeteer.
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
    wget \
    gnupg \
    curl \
    && rm -rf /var/lib/apt/lists/* # Limpieza al final

# 3. INSTALAR EXTENSIONES DE PHP: Necesarias para MongoDB y ZIP
RUN docker-php-ext-install soap bcmath pdo_mysql \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# 4. INSTALAR DRIVER DE MONGODB
RUN pecl install mongodb \
    && docker-php-ext-enable mongodb

# 5. INSTALAR COMPOSER GLOBALMENTE
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 6. COPIAR EL CÓDIGO FUENTE
COPY . /var/www/html

# 7. EJECUTAR COMPOSER (Instalar PHPMailer, TCPDF, etc.)
WORKDIR /var/www/html
RUN composer install --no-dev --prefer-dist

# 8. SOLUCIÓN AL ERROR DE PERMISOS DE UPLOADS
RUN mkdir -p /var/www/html/uploads \
    && chown -R www-data:www-data /var/www/html/uploads \
    && chmod -R 775 /var/www/html/uploads
    
# 9. INSTALAR NODE.JS, PUPPETEER Y CHROME (¡La parte clave y corregida!)
# --- Instalar Node.js y NPM
RUN apt-get update && apt-get install -y nodejs npm

# --- Instalar Google Chrome (Método Moderno para Clave GPG)
# 1. Obtener la clave GPG y guardarla en el directorio de confianza
RUN curl -sL https://dl.google.com/linux/linux_signing_key.pub | gpg --dearmor -o /etc/apt/trusted.gpg.d/google-chrome.gpg

# 2. Agregar el repositorio de Chrome
RUN echo "deb [arch=amd64] http://dl.google.com/linux/chrome/deb/ stable main" > /etc/apt/sources.list.d/google-chrome.list

# 3. Actualizar e Instalar Chrome
RUN apt-get update \
    && apt-get install -y google-chrome-stable

# --- Instalar Puppeteer en el directorio correcto
WORKDIR /var/www/html/utils
# Esto asume que tienes un package.json con puppeteer en /utils
RUN npm install

WORKDIR /var/www/html 

# 10. CONFIGURAR APACHE
RUN a2enmod rewrite

# 11. SOBREESCRIBIR LA CONFIGURACIÓN DE PUERTO DE APACHE
ENV PORT 10000
RUN sed -i 's/Listen 80/Listen ${PORT}/g' /etc/apache2/ports.conf /etc/apache2/sites-enabled/*.conf

# 12. EXPOSICIÓN Y ARRANQUE (Apache es el servidor predeterminado)
EXPOSE 80
