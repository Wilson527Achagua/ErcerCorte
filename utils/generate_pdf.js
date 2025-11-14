// Archivo: /ErcerSeme/utils/generate_pdf.js

const puppeteer = require('puppeteer');
const fs = require('fs');
const path = require('path');

// Obtener la URL del HTML y la ruta de destino del PDF desde los argumentos
// process.argv[2] será la URL del HTML
// process.argv[3] será la RUTA ABSOLUTA donde guardar el PDF
const [htmlUrl, pdfPath] = process.argv.slice(2);

if (!htmlUrl || !pdfPath) {
    console.error('Uso: node generate_pdf.js <URL_DEL_REPORTE_HTML> <RUTA_DESTINO_PDF>');
    process.exit(1);
}

(async () => {
    let browser;
    try {
        // 1. Lanzar el navegador virtual (headless)
        browser = await puppeteer.launch({
            // 🚨 CORRECCIÓN CLAVE PARA DOCKER/RENDER:
            args: [
                '--no-sandbox', 
                '--disable-setuid-sandbox',
                // Agregar esto para evitar problemas de memoria en entornos limitados
                '--disable-dev-shm-usage', 
                '--single-process'
            ],
            // 🚨 INDICA A PUPPETEER QUE NO DESCARGUE CHROME, USARÁ EL QUE ESTÁ EN EL SISTEMA
            executablePath: '/usr/bin/google-chrome' 
        });
        const page = await browser.newPage();

        // 2. Navegar a la URL del reporte HTML generado por PHP
        // Esperamos a que la red esté inactiva para asegurar que la imagen cargó
        await page.goto(htmlUrl, {waitUntil: 'networkidle0', timeout: 30000}); 

        // 3. Generar el PDF
        await page.pdf({
            path: pdfPath,
            format: 'A4',
            printBackground: true, // Necesario para que el color de fondo y las imágenes aparezcan
            margin: {
                top: '10mm',
                right: '10mm',
                bottom: '10mm',
                left: '10mm',
            }
        });

        await browser.close();
        console.log(`PDF generado con éxito en: ${pdfPath}`);
        
    } catch (e) {
        if (browser) await browser.close();
        // El script Node.js envía el error al PHP
        console.error('ERROR_NODEJS:', e.message); 
        process.exit(1);
    }
})();
