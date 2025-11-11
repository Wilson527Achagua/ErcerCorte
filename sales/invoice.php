<?php
// Archivo: /ErcerSeme/sales/invoice.php

use MongoDB\BSON\ObjectId;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once '../config/session.php';
requireLogin();
require_once '../config/database.php';
require_once '../vendor/autoload.php'; // Carga TCPDF y PHPMailer

// ---------------------------------------------------------------------
// 1. CONFIGURACIÓN DEL CORREO (AJUSTAR ESTO)
// ---------------------------------------------------------------------
$smtp_config = [
    'host'     => 'smtp.gmail.com',         // 🚨 CAMBIAR: Servidor SMTP (ej: smtp.gmail.com)
    'username' => 'servicesuniversidad@gmail.com',    // 🚨 CAMBIAR: Tu correo (ej: ventas@tuempresa.com)
    'password' => 'nzaf swsk nxjt yuky',   // 🚨 CAMBIAR: Contraseña o Token de Aplicación
    'port'     => 587,                      // Puerto (587 para TLS)
    'secure'   => PHPMailer::ENCRYPTION_STARTTLS 
];
$remitente_nombre = 'Sistema de Inventario ErcerSeme';

// ---------------------------------------------------------------------
// 2. OBTENER DATOS DE VENTA Y DEFINIR ACCIÓN
// ---------------------------------------------------------------------

$saleId = $_GET['id'] ?? '';
$action = $_GET['action'] ?? 'view'; // Acción: 'view' (Mostrar) o 'send' (Enviar Correo)

if (!$saleId) {
    header('Location: index.php');
    exit();
}

$db = new Database();
try {
    $objectId = new ObjectId($saleId);
} catch (Exception $e) {
    header('Location: index.php');
    exit();
}

$sales = $db->executeQuery('sales', ['_id' => $objectId]);
$sale = null;
foreach ($sales as $s) {
    $sale = $s;
    break;
}

if (!$sale) {
    header('Location: index.php');
    exit();
}

// Datos necesarios para el correo
$cliente_email = $sale->cliente_email ?? 'correo_no_registrado@temp.com'; // Asegúrate de que este campo exista en tu DB
$cliente_nombre = $sale->cliente_nombre ?? 'Cliente Desconocido';
$nombre_archivo = 'factura_' . $saleId . '.pdf';
$pdf_path_temp = sys_get_temp_dir() . '/' . $nombre_archivo; // Usar el directorio temporal del sistema

// ---------------------------------------------------------------------
// 3. GENERACIÓN DEL PDF (TU LÓGICA CON TCPDF)
// ---------------------------------------------------------------------

$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
$pdf->SetCreator('Sistema de Inventario');
$pdf->SetAuthor('Sistema de Inventario');
$pdf->SetTitle('Factura de Venta');
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->AddPage();
$pdf->SetFont('helvetica', 'B', 20);
$pdf->Cell(0, 10, 'FACTURA DE VENTA', 0, 1, 'C');
$pdf->Ln(5);
$pdf->SetFont('helvetica', '', 12);
$fecha = isset($sale->fecha) ? date('Y-m-d H:i:s', $sale->fecha->toDateTime()->getTimestamp()) : 'N/A';
$pdf->Cell(0, 8, 'Fecha: ' . $fecha, 0, 1);
$pdf->Cell(0, 8, 'Cliente: ' . htmlspecialchars($sale->cliente_nombre ?? 'N/A'), 0, 1);
$pdf->Cell(0, 8, 'Documento: ' . htmlspecialchars($sale->cliente_documento ?? 'N/A'), 0, 1);
$pdf->Ln(5);

// Tabla de productos
$html = '<table border="1" cellpadding="5">
    <thead>
        <tr style="background-color: #7c3aed; color: white;">
            <th width="40%">Producto</th>
            <th width="15%">Cantidad</th>
            <th width="20%">Valor Unit. (IVA Incl.)</th>
            <th width="25%">Valor Total</th>
        </tr>
    </thead>
    <tbody>';
foreach ($sale->productos as $producto) {
    $nombre = htmlspecialchars($producto->nombre ?? 'Producto Desconocido');
    $cantidad = $producto->cantidad ?? 0;
    $valor_unitario = $producto->valor_unitario ?? 0;
    $valor_total = $producto->valor_total ?? 0;
    
    $html .= '<tr>
        <td>' . $nombre . '</td>
        <td align="center">' . $cantidad . '</td>
        <td align="right">$' . number_format($valor_unitario, 2) . '</td>
        <td align="right">$' . number_format($valor_total, 2) . '</td>
    </tr>';
}
$html .= '</tbody></table>';

$pdf->writeHTML($html, true, false, true, false, '');
$pdf->Ln(5);

$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 8, 'Subtotal (Base): $' . number_format($sale->subtotal_base ?? 0, 2), 0, 1, 'R');
$pdf->Cell(0, 8, 'IVA (19%): $' . number_format($sale->iva ?? 0, 2), 0, 1, 'R');
$pdf->SetFont('helvetica', 'B', 14);
$pdf->Cell(0, 10, 'TOTAL A PAGAR: $' . number_format($sale->total ?? 0, 2), 0, 1, 'R');

$pdf->Output('factura_' . $saleId . '.pdf', 'I');