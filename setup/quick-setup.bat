@echo off
REM Script de configuración rápida para Windows
REM Ejecutar con: setup\quick-setup.bat

echo ==========================================
echo   CONFIGURACION RAPIDA - SISTEMA DE VENTAS
echo ==========================================
echo.

REM Verificar MongoDB
echo [1/6] Verificando MongoDB...
where mongosh >nul 2>nul
if %ERRORLEVEL% EQU 0 (
    echo [OK] MongoDB encontrado
) else (
    echo [ERROR] MongoDB no encontrado. Por favor instala MongoDB primero.
    pause
    exit /b 1
)

REM Verificar PHP
echo [2/6] Verificando PHP...
where php >nul 2>nul
if %ERRORLEVEL% EQU 0 (
    echo [OK] PHP encontrado
) else (
    echo [ERROR] PHP no encontrado. Por favor instala PHP primero.
    pause
    exit /b 1
)

REM Verificar extensión MongoDB
echo [3/6] Verificando extension MongoDB para PHP...
php -m | findstr mongodb >nul 2>nul
if %ERRORLEVEL% EQU 0 (
    echo [OK] Extension MongoDB instalada
) else (
    echo [ERROR] Extension MongoDB no encontrada
    echo Instalar con: pecl install mongodb
    echo Luego agregar 'extension=mongodb' a php.ini
    pause
    exit /b 1
)

REM Crear carpetas necesarias
echo [4/6] Creando carpetas necesarias...
if not exist "uploads\products" mkdir uploads\products
if not exist "uploads\invoices" mkdir uploads\invoices
echo [OK] Carpetas creadas

REM Configurar MongoDB
echo [5/6] Configurando base de datos MongoDB...
if exist "setup\mongodb-setup.js" (
    mongosh < setup\mongodb-setup.js
    if %ERRORLEVEL% EQU 0 (
        echo [OK] Base de datos configurada correctamente
    ) else (
        echo [ERROR] Error al configurar la base de datos
        pause
        exit /b 1
    )
) else (
    echo [ERROR] Archivo mongodb-setup.js no encontrado
    pause
    exit /b 1
)

REM Instalar dependencias con Composer
echo [6/6] Instalando dependencias PHP...
where composer >nul 2>nul
if %ERRORLEVEL% EQU 0 (
    composer require tecnickcom/tcpdf
    echo [OK] Dependencias instaladas
) else (
    echo [AVISO] Composer no encontrado. Instala manualmente:
    echo   composer require tecnickcom/tcpdf
)

echo.
echo ==========================================
echo   CONFIGURACION COMPLETADA
echo ==========================================
echo.
echo Para iniciar el servidor de desarrollo:
echo   php -S localhost:8000
echo.
echo Luego accede a: http://localhost:8000
echo.
echo Credenciales de acceso:
echo   Usuario: admin
echo   Contraseña: admin123
echo.
echo ==========================================
pause
