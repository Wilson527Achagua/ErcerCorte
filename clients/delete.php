<?php
require_once '../config/session.php';
requireLogin();
require_once '../config/database.php';

$error = '';

// 1. Obtener y validar el ID del documento
if (isset($_GET['id'])) {
    $clientId = $_GET['id'];
    $db = new Database();
    
    try {
        // Convertir el ID de string a ObjectId para la eliminación
        $objectId = new MongoDB\BSON\ObjectId($clientId);
        
        // 2. Llamar al método de eliminación (asume que tu clase Database tiene un método 'delete')
        $db->delete('clients', ['_id' => $objectId]); 
        
        // 3. Redirigir con mensaje de éxito (usando una variable de sesión flash)
        $_SESSION['flash_success'] = 'Cliente eliminado exitosamente.';
        header('Location: index.php'); // Redirige al listado de clientes
        exit();
        
    } catch (MongoDB\Driver\Exception\InvalidArgumentException $e) {
        // Esto captura errores si el ID no es un ObjectId válido (ej. una cadena de 10 caracteres)
        $error = 'Error: El ID proporcionado no es válido.';
    } catch (Exception $e) {
        $error = 'Error al eliminar cliente: ' . $e->getMessage();
    }
} else {
    $error = 'ID de cliente no proporcionado.';
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