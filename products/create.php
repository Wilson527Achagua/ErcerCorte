<?php
require_once '../config/session.php';
requireLogin();
require_once '../config/database.php';

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db = new Database();
    
    // Manejar la carga de imagen
    $imageName = '';
    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === 0) {
        $uploadDir = '../uploads/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        $imageName = uniqid() . '_' . basename($_FILES['imagen']['name']);
        $uploadFile = $uploadDir . $imageName;
        
        if (!move_uploaded_file($_FILES['imagen']['tmp_name'], $uploadFile)) {
            $error = 'Error al subir la imagen';
        }
    }
    
    if (!$error) {
        $document = [
            'id_producto' => $_POST['id_producto'],
            'nombre' => $_POST['nombre'],
            'descripcion' => $_POST['descripcion'],
            'cantidad' => (int)$_POST['cantidad'],
            'valor_unitario' => (float)$_POST['valor_unitario'],
            'imagen' => $imageName,
            'fecha_registro' => new MongoDB\BSON\UTCDateTime()
        ];
        
        try {
            $db->insert('products', $document);
            $success = 'Producto registrado exitosamente';
        } catch (Exception $e) {
            $error = 'Error al registrar producto: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nuevo Producto - Sistema de Inventario</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
    <?php include '../includes/sidebar.php'; ?>
    
    <div class="main-content">
        <?php include '../includes/header.php'; ?>
        
        <div class="dashboard-container">
            <div class="page-header">
                <h1 class="page-title">Registrar Nuevo Producto</h1>
                <a href="/ErcerSeme/products/index.php" class="btn btn-secondary">Volver</a>
            </div>
            
            <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <div class="card">
                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data" class="form">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="id_producto">ID del Producto</label>
                                <input type="text" id="id_producto" name="id_producto" required class="form-input">
                            </div>
                            
                            <div class="form-group">
                                <label for="nombre">Nombre del Producto</label>
                                <input type="text" id="nombre" name="nombre" required class="form-input">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="descripcion">Descripción</label>
                            <textarea id="descripcion" name="descripcion" required class="form-input" rows="3"></textarea>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="cantidad">Cantidad</label>
                                <input type="number" id="cantidad" name="cantidad" required class="form-input" min="0">
                            </div>
                            
                            <div class="form-group">
                                <label for="valor_unitario">Valor Unitario</label>
                                <input type="number" id="valor_unitario" name="valor_unitario" required class="form-input" step="0.01" min="0">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="imagen">Imagen del Producto</label>
                            <input type="file" id="imagen" name="imagen" accept="image/*" class="form-input">
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Registrar Producto</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
