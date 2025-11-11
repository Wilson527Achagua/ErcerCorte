<?php
// Archivo: /ErcerSeme/reports/reporte_solo_html.php
// Este script genera el HTML limpio que Node.js leerá.

//require_once '../config/session.php'; // Ajusta la ruta a tu session.php
//requireLogin();
require_once '../config/database.php'; // Ajusta la ruta a tu database.php

$db = new Database();
$products = $db->executeQuery('products', [], ['sort' => ['nombre' => 1]]);

// --- Estilos CSS ---
$css = '
<style>
    body { font-family: sans-serif; margin: 0; padding: 0; }
    .header { background-color: #7c3aed; color: white; padding: 15px; text-align: center; margin-bottom: 20px; }
    .header h1 { margin: 0; font-size: 24px; }
    .product-block { 
        border: 1px solid #ddd; 
        padding: 10px; 
        margin-bottom: 15px; 
        overflow: hidden; 
        page-break-inside: avoid; /* Evita que el bloque se corte entre páginas */
    }
    .product-image {
        float: left;
        width: 80px;
        height: 80px;
        margin-right: 15px;
        object-fit: cover;
    }
    .product-details {
        overflow: hidden;
    }
    .product-details h3 {
        margin: 0 0 5px 0;
        color: #7c3aed;
        font-size: 16px;
    }
    .product-details p {
        margin: 0 0 3px 0;
        font-size: 11px;
    }
</style>
';

// --- Contenido HTML ---
$html = '';

foreach ($products as $product) {
    $id_producto = htmlspecialchars($product->id_producto ?? 'N/A');
    $nombre = htmlspecialchars($product->nombre ?? 'Producto Desconocido');
    $descripcion = htmlspecialchars($product->descripcion ?? 'Sin descripción.');
    $cantidad = $product->cantidad ?? 0;
    $valor_unitario = number_format($product->valor_unitario ?? 0, 2);
    $imagen_file = htmlspecialchars($product->imagen ?? '');
    
    // 🚨 RUTA RELATIVA WEB: El navegador de Puppeteer debe poder acceder a esta ruta.
    $imagen_src = !empty($imagen_file) ? '../uploads/' . $imagen_file : 'placeholder.png'; 

    $html .= '
        <div class="product-block">
            <img class="product-image" src="' . $imagen_src . '" alt="' . $nombre . '">
            
            <div class="product-details">
                <h3>' . $nombre . '</h3>
                <p><strong>ID:</strong> ' . $id_producto . '</p>
                <p><strong>Stock:</strong> ' . $cantidad . ' unidades</p>
                <p><strong>Valor Unitario:</strong> $' . $valor_unitario . '</p>
                <p><strong>Descripción:</strong> ' . $descripcion . '</p>
            </div>
        </div>
    ';
}

// Estructura final
echo '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Reporte de Inventario</title>' . $css . '
</head>
<body>
    <div class="header">
        <h1>REPORTE DE INVENTARIO</h1>
    </div>
    ' . $html . '
</body>
</html>';
?>