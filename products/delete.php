<?php
require_once '../config/session.php';
requireLogin();
require_once '../config/database.php';

// Asegúrate de incluir la clase ObjectId si no está incluida en database.php
use MongoDB\BSON\ObjectId;

$error = '';
$db = new Database();

// 1. Obtener y validar el ID del documento
if (isset($_GET['id'])) {
    $productId = $_GET['id'];
    
    try {
        $objectId = new ObjectId($productId);
        
        // PRIMERO: Buscar el producto para obtener el nombre de la imagen (si existe)
        $products = $db->executeQuery('products', ['_id' => $objectId], ['limit' => 1]);
        $product = current($products);

        if ($product) {
            // SEGUNDO: Eliminar el documento de la base de datos
            $db->delete('products', ['_id' => $objectId]); 
            
            // TERCERO (Opcional): Eliminar el archivo de imagen del servidor
            if (!empty($product->imagen)) {
                $filePath = '../uploads/' . $product->imagen;
                if (file_exists($filePath)) {
                    @unlink($filePath); // @ para suprimir errores si el archivo no se puede borrar
                }
            }

            // Redirigir con mensaje de éxito (usando una variable de sesión flash, si la tienes)
            $_SESSION['flash_success'] = 'Producto eliminado exitosamente.';
            header('Location: index.php'); // Redirige al listado de productos
            exit();
            
        } else {
            $error = 'El producto a eliminar no fue encontrado.';
        }
        
    } catch (Exception $e) {
        $error = 'Error al eliminar producto: ' . $e->getMessage();
    }
} else {
    $error = 'ID de producto no proporcionado.';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error de Eliminación</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
    <?php include '../includes/sidebar.php'; ?>
    <div class="main-content">
        <?php include '../includes/header.php'; ?>
        <div class="dashboard-container">
            <div class="page-header">
                <h1 class="page-title">Error en la Operación</h1>
                <a href="index.php" class="btn btn-secondary">Volver</a>
            </div>
            
            <?php if ($error): ?>
            <div class="alert alert-error">
                <?php echo htmlspecialchars($error); ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>