<?php
require_once 'config/session.php';
//requireLogin();
require_once 'config/database.php';

$db = new Database();

// Obtener estadísticas
$totalClients = iterator_count($db->executeQuery('clients', []));
$totalProducts = iterator_count($db->executeQuery('products', []));
$totalSales = iterator_count($db->executeQuery('sales', []));

// Obtener productos con bajo stock
$lowStockProducts = $db->executeQuery('products', ['cantidad' => ['$lt' => 10]], ['limit' => 5]);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Sistema de Inventario</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="main-content">
        <?php include 'includes/header.php'; ?>
        
        <div class="dashboard-container">
            <h1 class="page-title">Dashboard</h1>
            
            <div class="stats-grid">
                <div class="stat-card gradient-purple">
                    <div class="stat-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                            <circle cx="9" cy="7" r="4"></circle>
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                        </svg>
                    </div>
                    <div class="stat-content">
                        <h3>Total Clientes</h3>
                        <p class="stat-number"><?php echo $totalClients; ?></p>
                    </div>
                </div>
                
                <div class="stat-card gradient-pink">
                    <div class="stat-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path>
                            <line x1="3" y1="6" x2="21" y2="6"></line>
                            <path d="M16 10a4 4 0 0 1-8 0"></path>
                        </svg>
                    </div>
                    <div class="stat-content">
                        <h3>Total Productos</h3>
                        <p class="stat-number"><?php echo $totalProducts; ?></p>
                    </div>
                </div>
                
                <div class="stat-card gradient-blue">
                    <div class="stat-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="12" y1="1" x2="12" y2="23"></line>
                            <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                        </svg>
                    </div>
                    <div class="stat-content">
                        <h3>Total Ventas</h3>
                        <p class="stat-number"><?php echo $totalSales; ?></p>
                    </div>
                </div>
            </div>
            
            <div class="content-grid">
                <div class="card">
                    <div class="card-header">
                        <h2>Productos con Bajo Stock</h2>
                    </div>
                    <div class="card-body">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Producto</th>
                                    <th>ID</th>
                                    <th>Cantidad</th>
                                    <th>Acción</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($lowStockProducts as $product): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($product->nombre); ?></td>
                                    <td><?php echo htmlspecialchars($product->id_producto); ?></td>
                                    <td><span class="badge badge-warning"><?php echo $product->cantidad; ?></span></td>
                                    <td>
                                        <a href="products/add-stock.php?id=<?php echo $product->_id; ?>" class="btn btn-sm">Agregar Stock</a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
