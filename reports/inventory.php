<?php
require_once '../config/session.php';
requireLogin();
require_once '../config/database.php';
require_once '../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

$db = new Database();
$products = $db->executeQuery('products', [], ['sort' => ['nombre' => 1]]);

// 🚨 CORRECCIÓN DE RUTA ABSOLUTA PARA DOMPDF
// Basado en tu diagnóstico, la ruta absoluta es la única confiable.
$nombre_proyecto = 'ErcerSeme'; // Reemplaza si tu carpeta principal tiene otro nombre
$uploads_path_base = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT'] . "/{$nombre_proyecto}/uploads/");


// --- 1. Configuración y Estilos HTML ---

$html = '
<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Reporte de Inventario</title>
    <style>
        body { font-family: sans-serif; margin: 0; padding: 0; }
        .header { background-color: #7c3aed; color: white; padding: 15px; text-align: center; margin-bottom: 20px; }
        .header h1 { margin: 0; font-size: 24px; }
        .product-block { 
            border: 1px solid #ddd; 
            padding: 10px; 
            margin-bottom: 15px; 
            overflow: hidden; 
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
</head>
<body>
    <div class="header">
        <h1>REPORTE DE INVENTARIO</h1>
    </div>
';

// --- 2. Generación de Bloques de Productos ---
foreach ($products as $product) {
    $id_producto = htmlspecialchars($product->id_producto ?? 'N/A');
    $nombre = htmlspecialchars($product->nombre ?? 'Producto Desconocido');
    $descripcion = htmlspecialchars($product->descripcion ?? 'Sin descripción.');
    $cantidad = $product->cantidad ?? 0;
    $valor_unitario = number_format($product->valor_unitario ?? 0, 2);
    $imagen_file = htmlspecialchars($product->imagen ?? '');
    
    // Ruta absoluta forzada para la etiqueta <img>
    $imagen_src = $uploads_path_base . $imagen_file; 

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

$html .= '
</body>
</html>
';

// --- 3. Renderizado del PDF con Dompdf ---

$options = new Options();
// Es vital desactivar isRemoteEnabled cuando se usa una ruta absoluta local (C:/...)
$options->set('isRemoteEnabled', false); 
$options->set('isHtml5ParserEnabled', true); 

$dompdf = new Dompdf($options);

// Establecer el directorio base. Lo establecemos a la carpeta uploads por si acaso.
$dompdf->setBasePath($uploads_path_base); 

$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');

$dompdf->render();

$dompdf->stream('reporte_inventario.pdf', array('Attachment' => 0));

?>