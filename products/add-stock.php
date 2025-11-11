<?php
require_once '../config/session.php';
requireLogin();
require_once '../config/database.php';

$db = new Database();
$success = '';
$error = '';

$productId = $_GET['id'] ?? '';
if (!$productId) {
    header('Location: index.php');
    exit();
}

// Obtener producto
$products = $db->executeQuery('products', ['_id' => new MongoDB\BSON\ObjectId($productId)]);
$product = null;
foreach ($products as $p) {
    $product = $p;
    break;
}

if (!$product) {
    header('Location: index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cantidadAgregar = (int)$_POST['cantidad'];
    
    if ($cantidadAgregar > 0) {
        $nuevaCantidad = $product->cantidad + $cantidadAgregar;
        
        try {
            $db->update(
                'products',
                ['_id' => new MongoDB\BSON\ObjectId($productId)],
                ['$set' => ['cantidad' => $nuevaCantidad]]
            );
            $success = "Se agregaron $cantidadAgregar unidades. Stock actual: $nuevaCantidad";
            $product->cantidad = $nuevaCantidad;
        } catch (Exception $e) {
            $error = 'Error al actualizar stock: ' . $e->getMessage();
        }
    } else {
        $error = 'La cantidad debe ser mayor a 0';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agregar Stock - Sistema de Inventario</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
    <?php include '../includes/sidebar.php'; ?>
    
    <div class="main-content">
        <?php include '../includes/header.php'; ?>
        
        <div class="dashboard-container">
            <div class="page-header">
                <h1 class="page-title">Agregar Stock</h1>
                <a href="index.php" class="btn btn-secondary">Volver</a>
            </div>
            
            <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <div class="card">
                <div class="card-body">
                    <div class="product-info">
                        <h3><?php echo htmlspecialchars($product->nombre); ?></h3>
                        <p>ID: <?php echo htmlspecialchars($product->id_producto); ?></p>
                        <p>Stock Actual: <strong><?php echo $product->cantidad; ?></strong> unidades</p>
                    </div>
                    
                    <form method="POST" class="form">
                        <div class="form-group">
                            <label for="cantidad">Cantidad a Agregar</label>
                            <input type="number" id="cantidad" name="cantidad" required class="form-input" min="1" value="1">
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Agregar al Stock</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
