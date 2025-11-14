<?php
// Archivo: /ErcerSeme/reports/generar_pdf_final.php
// Este script ejecuta el proceso Node.js (Puppeteer) y MUESTRA el PDF en el navegador.

require_once '../config/session.php';
requireLogin();

// --- Definición de Rutas ---

// 1. RUTA ABSOLUTA CORREGIDA DEL SCRIPT NODE.JS
$node_script = dirname(__DIR__) . '/utils/generate_pdf.js';
$node_script = str_replace('\\', '/', $node_script);

// 2. URL ACCESIBLE DEL HTML GENERADO POR PHP
// 🚨 CORRECCIÓN CLAVE: Usamos la URL pública del entorno Render, no localhost.
$base_url = getenv('RENDER_EXTERNAL_URL'); 
if (empty($base_url)) {
    // Si no estamos en Render, usamos tu dominio local de prueba
    $base_url = 'http://ercercorte.onrender.com'; 
}

// 🚨 La URL que Puppeteer visitará DEBE ser pública y completa.
$html_url = $base_url . '/reports/reporte_solo_html.php'; 

// 3. RUTA ABSOLUTA DONDE SE GUARDARÁ EL PDF TEMPORAL (Usando la carpeta uploads)
$pdf_path_temp = dirname(__DIR__) . '/uploads/reporte_' . uniqid() . '.pdf';
$pdf_path_temp = str_replace('\\', '/', $pdf_path_temp); 

// 4. RUTA ABSOLUTA AL EJECUTABLE DE NODE.JS (ELIMINADA)
// En el contenedor de Docker, 'node' está en el PATH global. Usamos 'node'.
$node_exe_path = 'node'; 

// 5. Comando a ejecutar 
$command = escapeshellarg($node_exe_path) . 
           " " . escapeshellarg($node_script) . 
           " " . escapeshellarg($html_url) . 
           " " . escapeshellarg($pdf_path_temp);

// --- Ejecución y Verificación ---
$output_array = [];
$return_var = 0;

exec($command, $output_array, $return_var);
$output = implode("\n", $output_array);


// --- Bloque Final: Descarga o Diagnóstico de Fallo ---

if ($return_var === 0 && file_exists($pdf_path_temp)) {
    // 1. ÉXITO: Forzar la DESCARGA
    header('Content-Description: File Transfer');
    header('Content-Type: application/pdf');
    
    // 🚨 MODIFICADO: Cambiar 'attachment' por 'inline' para mostrar en el navegador
    header('Content-Disposition: inline; filename="reporte_inventario_final.pdf"'); 
    
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize($pdf_path_temp));
    
    // 2. Enviar el archivo
    readfile($pdf_path_temp);
    
    // 3. Limpiar el archivo temporal de la carpeta uploads
    unlink($pdf_path_temp); 
    
    exit;
} else {
    // FALLO: Mostrar el diagnóstico completo para ver el error de Node.js

    echo "<h1>Error Fatal en la Generación del PDF (Node.js)</h1>";
    echo "<p>No se pudo crear el archivo PDF. El proceso Node.js falló.</p>";
    
    echo "<h2>1. Comando Ejecutado:</h2>";
    echo "<pre>" . htmlspecialchars($command) . "</pre>";
    echo "<h2>2. Código de Retorno (Debe ser 0):</h2>";
    echo "<pre>" . $return_var . "</pre>";
    echo "<h2>3. Salida Completa de Node.js:</h2>";
    echo "<pre>" . htmlspecialchars($output) . "</pre>";
    echo "<h2>4. Ruta Temporal Esperada:</h2>";
    echo "<pre>" . htmlspecialchars($pdf_path_temp) . "</pre>";

    if ($return_var !== 0 && empty($output)) {
        echo "<h3 style='color: red;'>🛑 PISTA: El error es de ejecución del comando Node.js.</h3>";
        echo "<p>Verifica que el comando 'node' esté instalado y en el PATH.</p>";
    }
}
?>
