<?php
// Archivo: /ErcerSeme/utils/InvoiceSenderService.php

use MongoDB\BSON\ObjectId;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Asegurar la carga de clases
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../vendor/autoload.php'; // Carga TCPDF y PHPMailer

/**
 * Genera la factura PDF de una venta y la envía por correo electrónico.
 * @param string $saleId El ID de la venta a enviar.
 * @return array Devuelve ['success' => bool, 'message' => string]
 */
function sendInvoiceAutomatically(string $saleId): array
{
    // ---------------------------------------------------------------------
    // 🚨 1. CONFIGURACIÓN DEL CORREO (USAR TUS CREDENCIALES DE GMAIL)
    // ---------------------------------------------------------------------
    $smtp_config = [
        'host'     => 'smtp.gmail.com',
        'username' => 'servicesuniversidad@gmail.com',    
        'password' => 'uenh gojc ntar bqcy',   // Contraseña de Aplicación de 16 caracteres
        'port'     => 587,                      
        'secure'   => PHPMailer::ENCRYPTION_STARTTLS 
    ];
    $remitente_nombre = 'Sistema de Inventario ErcerSeme';

    // ---------------------------------------------------------------------
    // 2. OBTENER DATOS DE VENTA
    // ---------------------------------------------------------------------
    $db = new Database();
    try {
        $objectId = new ObjectId($saleId);
    } catch (Exception $e) {
        return ['success' => false, 'message' => "Error: ID de venta inválido."];
    }

    $sales = $db->executeQuery('sales', ['_id' => $objectId]);
    $sale = current($sales);

    if (!$sale) {
        return ['success' => false, 'message' => "Error: Venta no encontrada."];
    }
    
    // 🚨 ASUMIMOS QUE LA VENTA YA TIENE EL CAMPO cliente_email
    $cliente_email = $sale->cliente_email ?? '';
    $cliente_nombre = $sale->cliente_nombre ?? 'Cliente Desconocido';
    
    if ($cliente_email === 'correo_no_registrado@temp.com' || empty($cliente_email)) {
        return ['success' => false, 'message' => "Error: Correo del cliente no registrado. No se pudo enviar."];
    }

    $nombre_archivo = 'factura_' . $saleId . '.pdf';
    $pdf_path_temp = sys_get_temp_dir() . '/' . $nombre_archivo; // Ruta temporal para guardar el PDF

    // ---------------------------------------------------------------------
    // 3. GENERACIÓN DEL PDF (TU LÓGICA TCPDF)
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
    
    // Guarda el PDF en el archivo temporal ('F' de File)
    $pdf->Output($pdf_path_temp, 'F');
    
    // ---------------------------------------------------------------------
    // 4. ENVÍO DEL CORREO
    // ---------------------------------------------------------------------

    $resultado = ['success' => false, 'message' => "Error: No se pudo crear el archivo PDF temporal."];
    
    if (file_exists($pdf_path_temp)) {
        try {
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host       = $smtp_config['host'];
            $mail->SMTPAuth   = true;
            $mail->Username   = $smtp_config['username'];
            $mail->Password   = $smtp_config['password'];
            $mail->SMTPSecure = $smtp_config['secure'];
            $mail->Port       = $smtp_config['port'];
            $mail->CharSet    = 'UTF-8';

            $mail->setFrom($smtp_config['username'], $remitente_nombre);
            $mail->addAddress($cliente_email, $cliente_nombre);
            $mail->addAttachment($pdf_path_temp, $nombre_archivo);

            $mail->isHTML(true);
            $mail->Subject = "Factura de Compra - {$saleId}";
            $mail->Body    = "Estimado/a {$cliente_nombre},<br><br>Adjuntamos la factura #{$saleId} correspondiente a su compra.<br><br>Gracias por preferirnos.<br>";

            $mail->send();
            $resultado = ['success' => true, 'message' => "Venta registrada y factura enviada a {$cliente_email}."];

        } catch (Exception $e) {
            $resultado = ['success' => false, 'message' => "Venta registrada, pero fallo al enviar la factura. Error: {$mail->ErrorInfo}"];
        }
    }

    // Limpiar el archivo PDF temporal
    if (file_exists($pdf_path_temp)) {
        unlink($pdf_path_temp);
    }
    
    return $resultado;
}
// Fin del archivo utils/InvoiceSenderService.php