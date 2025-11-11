<?php
require_once '../config/session.php';
requireLogin();
require_once '../config/database.php';

$db = new Database();
$sales = $db->executeQuery('sales', [], ['sort' => ['fecha' => -1]]);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ventas - Sistema de Inventario</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
    <?php include '../includes/sidebar.php'; ?>
    
    <div class="main-content">
        <?php include '../includes/header.php'; ?>
        
        <div class="dashboard-container">
            <div class="page-header">
                <h1 class="page-title">Gestión de Ventas</h1>
                <a href="create.php" class="btn btn-primary">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="12" y1="5" x2="12" y2="19"></line>
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                    </svg>
                    Nueva Venta
                </a>
            </div>
            
            <div class="card">
                <div class="card-body">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Cliente</th>
                                <th>Productos</th>
                                <th>Subtotal</th>
                                <th>IVA (19%)</th>
                                <th>Total</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($sales as $sale): ?>
                            <tr>
                                <td><?php echo date('Y-m-d H:i', $sale->fecha->toDateTime()->getTimestamp()); ?></td>
                                <td><?php echo htmlspecialchars($sale->cliente_nombre); ?></td>
                                <td><?php echo count($sale->productos); ?> producto(s)</td>
                                <td><?php echo number_format($sale->subtotal_base, 2); ?></td>
                                <td>$<?php echo number_format($sale->iva, 2); ?></td>
                                <td><strong>$<?php echo number_format($sale->total, 2); ?></strong></td>
                                <td>
                                    <a href="invoice.php?id=<?php echo $sale->_id; ?>" class="btn btn-sm btn-info" target="_blank">Ver PDF</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
