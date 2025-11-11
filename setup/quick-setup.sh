#!/bin/bash

# Script de configuración rápida para Linux/Mac
# Ejecutar con: bash setup/quick-setup.sh

echo "=========================================="
echo "  CONFIGURACIÓN RÁPIDA - SISTEMA DE VENTAS"
echo "=========================================="
echo ""

# Colores para output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# Verificar MongoDB
echo -e "${YELLOW}[1/6] Verificando MongoDB...${NC}"
if command -v mongosh &> /dev/null; then
    echo -e "${GREEN}✓ MongoDB encontrado${NC}"
else
    echo -e "${RED}✗ MongoDB no encontrado. Por favor instala MongoDB primero.${NC}"
    exit 1
fi

# Verificar PHP
echo -e "${YELLOW}[2/6] Verificando PHP...${NC}"
if command -v php &> /dev/null; then
    PHP_VERSION=$(php -v | head -n 1 | cut -d " " -f 2 | cut -d "." -f 1,2)
    echo -e "${GREEN}✓ PHP $PHP_VERSION encontrado${NC}"
else
    echo -e "${RED}✗ PHP no encontrado. Por favor instala PHP primero.${NC}"
    exit 1
fi

# Verificar extensión MongoDB
echo -e "${YELLOW}[3/6] Verificando extensión MongoDB para PHP...${NC}"
if php -m | grep -q mongodb; then
    echo -e "${GREEN}✓ Extensión MongoDB instalada${NC}"
else
    echo -e "${RED}✗ Extensión MongoDB no encontrada${NC}"
    echo "Instalar con: pecl install mongodb"
    echo "Luego agregar 'extension=mongodb.so' a php.ini"
    exit 1
fi

# Crear carpetas necesarias
echo -e "${YELLOW}[4/6] Creando carpetas necesarias...${NC}"
mkdir -p uploads/products
mkdir -p uploads/invoices
chmod 755 uploads/products
chmod 755 uploads/invoices
echo -e "${GREEN}✓ Carpetas creadas${NC}"

# Configurar MongoDB
echo -e "${YELLOW}[5/6] Configurando base de datos MongoDB...${NC}"
if [ -f "setup/mongodb-setup.js" ]; then
    mongosh < setup/mongodb-setup.js
    if [ $? -eq 0 ]; then
        echo -e "${GREEN}✓ Base de datos configurada correctamente${NC}"
    else
        echo -e "${RED}✗ Error al configurar la base de datos${NC}"
        exit 1
    fi
else
    echo -e "${RED}✗ Archivo mongodb-setup.js no encontrado${NC}"
    exit 1
fi

# Instalar dependencias con Composer
echo -e "${YELLOW}[6/6] Instalando dependencias PHP...${NC}"
if command -v composer &> /dev/null; then
    composer require tecnickcom/tcpdf
    echo -e "${GREEN}✓ Dependencias instaladas${NC}"
else
    echo -e "${YELLOW}⚠ Composer no encontrado. Instala manualmente:${NC}"
    echo "  composer require tecnickcom/tcpdf"
fi

echo ""
echo "=========================================="
echo -e "${GREEN}  ✓ CONFIGURACIÓN COMPLETADA${NC}"
echo "=========================================="
echo ""
echo "Para iniciar el servidor de desarrollo:"
echo "  php -S localhost:8000"
echo ""
echo "Luego accede a: http://localhost:8000"
echo ""
echo "Credenciales de acceso:"
echo "  Usuario: admin"
echo "  Contraseña: admin123"
echo ""
echo "=========================================="
