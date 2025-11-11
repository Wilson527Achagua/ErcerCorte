<?php
require_once '../config/session.php';
requireLogin();
require_once '../config/database.php';

// Asegúrate de incluir la clase ObjectId si no está incluida en database.php
use MongoDB\BSON\ObjectId;

$success = '';
$error = '';
$product = null;

// 1. Obtener y validar el ID del documento
if (isset($_GET['id'])) {
    $productId = $_GET['id'];
    $db = new Database();
    
    try {
        // Convertir el ID de string a ObjectId para la búsqueda en MongoDB
        $objectId = new ObjectId($productId);
        
        // Buscar el producto actual
        $products = $db->executeQuery('products', ['_id' => $objectId]);
        
        if (!empty($products)) {
            // Se obtienen los datos del producto (objeto)
            $product = current($products); 
        } else {
            $error = 'Producto no encontrado.';
        }
    } catch (Exception $e) {
        $error = 'ID inválido o error de conexión: ' . $e->getMessage();
    }
} else {
    $error = 'ID de producto no proporcionado.';
}

// 2. Procesar la actualización del formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $product) {
    
    // Preparar datos para la actualización
    $updateData = [
        'id_producto'     => trim($_POST['id_producto']),
        'nombre'          => trim($_POST['nombre']),
        'descripcion'     => trim($_POST['descripcion']),
        'cantidad'        => (int)$_POST['cantidad'],
        'valor_unitario'  => (float)$_POST['valor_unitario'],
        // La imagen se mantiene si no se sube una nueva
        'imagen'          => $product->imagen ?? null, 
    ];
    
    // Lógica opcional para manejar la subida de una nueva imagen
    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../uploads/';
        $fileName = basename($_FILES['imagen']['name']);
        $targetFilePath = $uploadDir . $fileName;
        $fileType = strtolower(pathinfo($targetFilePath, PATHINFO_EXTENSION));

        // Validación básica
        if (in_array($fileType, ['jpg', 'jpeg', 'png', 'gif']) && $_FILES['imagen']['size'] < 5000000) {
            if (move_uploaded_file($_FILES['imagen']['tmp_name'], $targetFilePath)) {
                // Si la subida fue exitosa, actualiza el nombre del archivo en la DB
                $updateData['imagen'] = $fileName;
                
                // Opcional: Eliminar imagen anterior si existe y es diferente
                if (!empty($product->imagen) && $product->imagen !== $fileName) {
                    @unlink($uploadDir . $product->imagen);
                }
            } else {
                $error = 'Error al subir la nueva imagen.';
            }
        } else {
            $error = 'Tipo de archivo de imagen no permitido o es demasiado grande (máx 5MB).';
        }
    }

    if (!$error) {
        try {
            // Llamar al método de actualización
            $db->update('products', ['_id' => $product->_id], $updateData); 
            
            // Recargar el objeto $product con los datos actualizados
            // Esto es crucial para rellenar los campos después de una edición exitosa
            foreach ($updateData as $key => $value) {
                $product->{$key} = $value;
            }
            
            $success = 'Producto actualizado exitosamente.';
        } catch (Exception $e) {
            $error = 'Error al actualizar producto: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Producto - Sistema de Inventario</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
    <?php include '../includes/sidebar.php'; ?>
    
    <div class="main-content">
        <?php include '../includes/header.php'; ?>
        
        <div class="dashboard-container">
            <div class="page-header">
                <h1 class="page-title">Editar Producto: <?php echo htmlspecialchars($product->nombre ?? 'ID Desconocido'); ?></h1>
                <a href="index.php" class="btn btn-secondary">Volver al Listado</a>
            </div>
            
            <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php if ($product): ?>
            <div class="card">
                <div class="card-body">
                    <form method="POST" class="form" enctype="multipart/form-data">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="id_producto">ID Producto</label>
                                <input type="text" id="id_producto" name="id_producto" required class="form-input"
                                       value="<?php echo htmlspecialchars($product->id_producto ?? ''); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="nombre">Nombre</label>
                                <input type="text" id="nombre" name="nombre" required class="form-input"
                                       value="<?php echo htmlspecialchars($product->nombre ?? ''); ?>">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="descripcion">Descripción</label>
                            <textarea id="descripcion" name="descripcion" required class="form-input"><?php echo htmlspecialchars($product->descripcion ?? ''); ?></textarea>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="cantidad">Cantidad en Stock</label>
                                <input type="number" id="cantidad" name="cantidad" required class="form-input"
                                       value="<?php echo htmlspecialchars($product->cantidad ?? 0); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="valor_unitario">Valor Unitario ($)</label>
                                <input type="number" step="0.01" id="valor_unitario" name="valor_unitario" required class="form-input"
                                       value="<?php echo htmlspecialchars($product->valor_unitario ?? 0.00); ?>">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="imagen">Cambiar Imagen</label>
                            <input type="file" id="imagen" name="imagen" class="form-input">
                            
                            <?php if (!empty($product->imagen)): ?>
                                <p>Imagen actual:</p>
                                <img src="../uploads/<?php echo htmlspecialchars($product->imagen); ?>" alt="Imagen actual" style="max-width: 150px; margin-top: 10px; border: 1px solid #ccc;">
                            <?php endif; ?>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                    </form>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>