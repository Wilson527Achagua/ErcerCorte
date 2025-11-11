<?php
require_once '../config/session.php';
requireLogin();
require_once '../config/database.php';

use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;

$db = new Database();
// Obtener clientes y productos en stock
$clients = $db->executeQuery('clients', [], ['sort' => ['nombre_completo' => 1]]);
$products = $db->executeQuery('products', ['cantidad' => ['$gt' => 0]], ['sort' => ['nombre' => 1]]);

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $clientId = $_POST['cliente_id'];
    $productosVenta = json_decode($_POST['productos_json'], true);
    
    if (empty($productosVenta)) {
        $error = 'Debe agregar al menos un producto.';
    } else {
        // Obtener datos del cliente
        $clientData = $db->executeQuery('clients', ['_id' => new ObjectId($clientId)]);
        $cliente = null;
        // Asumiendo que executeQuery devuelve un array iterable y el primer elemento es el cliente
        $cliente = current($clientData); 
        
        // Si el cliente no se encuentra
        if (!$cliente) {
             $error = "Error: Cliente no encontrado.";
        }

        $totalBruto = 0; // Total de la venta, con IVA incluido
        $productosParaVenta = [];
        
        // Procesar productos y actualizar stock
        foreach ($productosVenta as $pv) {
            // ... (Lógica de obtención y validación de producto, se mantiene igual) ...
            $productData = $db->executeQuery('products', ['_id' => new ObjectId($pv['id'])]);
            $producto = null;
            $producto = current($productData); 
            
            // 🚨 Validación de Stock 
            if (!$producto || $producto->cantidad < $pv['cantidad']) {
                 $error = "Error: Stock insuficiente para el producto " . ($producto->nombre ?? $pv['id']);
                 break; // Detener el proceso si hay error de stock
            }
            
            $valorTotal = $producto->valor_unitario * $pv['cantidad'];
            $totalBruto += $valorTotal; // Acumulando el total con IVA
            
            $productosParaVenta[] = [
                'producto_id' => $pv['id'],
                'nombre' => $producto->nombre,
                'cantidad' => $pv['cantidad'],
                'valor_unitario' => $producto->valor_unitario, // Precio unitario CON IVA
                'valor_total' => $valorTotal
            ];

            // Actualizar stock
            $nuevaCantidad = $producto->cantidad - $pv['cantidad'];
            $db->update(
              'products',
              ['_id' => new ObjectId($pv['id'])],
              ['$set' => ['cantidad' => $nuevaCantidad]] 
            );
        }
        
        if (!$error && $cliente) {
            $factorIVA = 1.19;
            $subtotalBase = $totalBruto / $factorIVA; // Valor Base (sin IVA)
            $iva = $totalBruto - $subtotalBase; // Valor del IVA
            $total = $totalBruto; // Total final

            // 🚨 CAMBIO 1: Asegurarse de que el correo del cliente se guarde en la venta
            $clienteEmail = $cliente->correo_electronico ?? $cliente->correo ?? ''; // Usar 'email' o 'correo' según tu DB
            
            // Registrar venta
            $ventaDocument = [
                'cliente_id' => $clientId,
                'cliente_nombre' => $cliente->nombre_completo,
                'cliente_documento' => $cliente->numero_documento,
                'cliente_email' => $clienteEmail, // 🚨 Nuevo campo para el envío automático
                'productos' => $productosParaVenta,
                'subtotal_base' => round($subtotalBase, 2), // Valor base sin IVA
                'iva' => round($iva, 2), // Valor del IVA
                'total' => round($total, 2), // Total con IVA incluido
                'fecha' => new UTCDateTime(),
                'usuario_id' => getUserId()
            ];
            
            try {
                $result = $db->insert('sales', $ventaDocument); 
                $insertedId = $result['insertedId'];
                
                // ---------------------------------------------------------------------
                // 🚨 CAMBIO 2: INVOCAR EL ENVÍO AUTOMÁTICO
                // ---------------------------------------------------------------------
                require_once '../utils/InvoiceSenderService.php'; 
                $resultadoEnvio = sendInvoiceAutomatically($insertedId); 
                
                $redirectionMessage = $resultadoEnvio['message'];
                
                // Redirigir al índice de ventas con el mensaje de estado
                header('Location: index.php?message=' . urlencode($redirectionMessage));
                exit();
                
            } catch (Exception $e) {
                $error = 'Error al registrar venta: ' . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nueva Venta - Sistema de Inventario</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
    <?php include '../includes/sidebar.php'; ?>
    
    <div class="main-content">
        <?php include '../includes/header.php'; ?>
        
        <div class="dashboard-container">
            <div class="page-header">
                <h1 class="page-title">Registrar Nueva Venta</h1>
                <a href="index.php" class="btn btn-secondary">Volver</a>
            </div>
            
            <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <div class="card">
                <div class="card-body">
                    <form method="POST" id="ventaForm" class="form">
                        <div class="form-group">
                            <label for="cliente_id">Cliente</label>
                            <select id="cliente_id" name="cliente_id" required class="form-input">
                                <option value="">Seleccionar cliente...</option>
                                <?php foreach ($clients as $client): ?>
                                <option value="<?php echo $client->_id; ?>">
                                    <?php echo htmlspecialchars($client->nombre_completo . ' - ' . $client->numero_documento); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Productos</label>
                            <div class="product-selector">
                                <select id="producto_select" class="form-input">
                                    <option value="">Seleccionar producto...</option>
                                    <?php foreach ($products as $product): ?>
                                    <option value="<?php echo $product->_id; ?>" 
                                            data-nombre="<?php echo htmlspecialchars($product->nombre); ?>"
                                            data-precio="<?php echo $product->valor_unitario; ?>"
                                            data-stock="<?php echo $product->cantidad; ?>">
                                        <?php echo htmlspecialchars($product->nombre . ' - Stock: ' . $product->cantidad . ' - $' . number_format($product->valor_unitario, 2)); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                                <input type="number" id="cantidad_input" class="form-input" placeholder="Cantidad" min="1" value="1">
                                <button type="button" id="agregarProducto" class="btn btn-secondary">Agregar</button>
                            </div>
                        </div>
                        
                        <div id="productosAgregados" class="productos-lista"></div>
                        
                        <div class="venta-totales">
                            <div class="total-row">
                                <span>Subtotal (Base sin IVA):</span>
                                <span id="subtotal">$0.00</span>
                            </div>
                            <div class="total-row">
                                <span>IVA (19% Incluido):</span>
                                <span id="iva">$0.00</span>
                            </div>
                            <div class="total-row total-final">
                                <span>Total (IVA Incluido):</span>
                                <span id="total">$0.00</span>
                            </div>
                        </div>
                        
                        <input type="hidden" name="productos_json" id="productos_json">
                        <button type="submit" class="btn btn-primary">Realizar Venta</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        let productosVenta = [];
        
        document.getElementById('agregarProducto').addEventListener('click', function() {
            const select = document.getElementById('producto_select');
            const cantidad = parseInt(document.getElementById('cantidad_input').value);
            
            if (!select.value || cantidad < 1) {
                alert('Seleccione un producto y cantidad válida');
                return;
            }
            
            const option = select.options[select.selectedIndex];
            const stock = parseInt(option.dataset.stock);
            
            if (cantidad > stock) {
                alert('Cantidad excede el stock disponible');
                return;
            }
            
            // 🚨 Producto se agrega con el precio que ya incluye IVA
            const producto = {
                id: select.value,
                nombre: option.dataset.nombre,
                precio: parseFloat(option.dataset.precio),
                cantidad: cantidad
            };
            
            // Revisar si el producto ya está en la lista para sumarle la cantidad
            let existente = productosVenta.find(p => p.id === producto.id);

            if (existente) {
                // Verificar stock total si se suma
                if (existente.cantidad + producto.cantidad > stock) {
                    alert('La cantidad total excede el stock disponible');
                    return;
                }
                existente.cantidad += producto.cantidad;
            } else {
                productosVenta.push(producto);
            }
            
            actualizarLista();
            select.value = '';
            document.getElementById('cantidad_input').value = 1;
        });
        
        function actualizarLista() {
            const lista = document.getElementById('productosAgregados');
            let html = '<table class="data-table"><thead><tr><th>Producto</th><th>Cantidad</th><th>Precio Unit.</th><th>Total</th><th>Acción</th></tr></thead><tbody>';
            
            let totalBruto = 0; // Total de la venta (IVA Incluido)
            const factorIVA = 1.19;
            
            productosVenta.forEach((p, index) => {
                const total = p.precio * p.cantidad;
                totalBruto += total;
                html += `<tr>
                    <td>${p.nombre}</td>
                    <td>${p.cantidad}</td>
                    <td>$${p.precio.toFixed(2)}</td>
                    <td>$${total.toFixed(2)}</td>
                    <td><button type="button" class="btn btn-sm btn-danger" onclick="eliminarProducto(${index})">Eliminar</button></td>
                </tr>`;
            });
            
            html += '</tbody></table>';
            lista.innerHTML = html;
            
            // Cálculos para desglose
            const subtotalBase = totalBruto / factorIVA; // Base sin IVA
            const iva = totalBruto - subtotalBase; // Solo el IVA
            const totalFinal = totalBruto; // Total final CON IVA
            
            // Actualizar la vista
            document.getElementById('subtotal').textContent = '$' + subtotalBase.toFixed(2);
            document.getElementById('iva').textContent = '$' + iva.toFixed(2);
            document.getElementById('total').textContent = '$' + totalFinal.toFixed(2);
            
            // Enviar datos al PHP
            document.getElementById('productos_json').value = JSON.stringify(productosVenta);
        }
        
        function eliminarProducto(index) {
            productosVenta.splice(index, 1);
            actualizarLista();
        }
        
        // Inicializar la lista al cargar la página (opcional)
        actualizarLista(); 
    </script>
</body>
</html>