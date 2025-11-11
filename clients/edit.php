<?php
require_once '../config/session.php';
requireLogin();
require_once '../config/database.php';

$success = '';
$error = '';
$client = null;

// 1. Obtener y validar el ID del documento
if (isset($_GET['id'])) {
    $clientId = $_GET['id'];
    $db = new Database();
    
    try {
        // Convertir el ID de string a ObjectId para la búsqueda en MongoDB
        $objectId = new MongoDB\BSON\ObjectId($clientId);
        
        // Buscar el cliente actual
        // Ejecuta la consulta para obtener los datos del cliente con ese _id
        $clients = $db->executeQuery('clients', ['_id' => $objectId]);
        
        if (!empty($clients)) {
            // Se obtienen los datos del cliente
            $client = (array)current($clients);
        } else {
            $error = 'Cliente no encontrado.';
        }
    } catch (Exception $e) {
        $error = 'ID inválido o error de conexión: ' . $e->getMessage();
    }
} else {
    $error = 'ID de cliente no proporcionado.';
}

// 2. Procesar la actualización del formulario (si se envió)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $client) {
    
    $updateData = [
        // Aquí se toman los datos del POST
        'tipo_documento' => $_POST['tipo_documento'],
        'numero_documento' => $_POST['numero_documento'],
        'nombre_completo' => $_POST['nombre_completo'],
        'numero_contacto' => $_POST['numero_contacto'],
        'correo_electronico' => $_POST['correo_electronico'],
    ];
    
    try {
        // Ejecutar la actualización en la base de datos
        $db->update('clients', ['_id' => $client['_id']], $updateData); 
        
        // Recargar la variable $client con los datos nuevos para que se muestren en el formulario
        $client = array_merge($client, $updateData);
        
        $success = 'Cliente actualizado exitosamente.';
    } catch (Exception $e) {
        $error = 'Error al actualizar cliente: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Cliente - Sistema de Inventario</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
    <?php include '../includes/sidebar.php'; ?>
    
    <div class="main-content">
        <?php include '../includes/header.php'; ?>
        
        <div class="dashboard-container">
            <div class="page-header">
                <h1 class="page-title">Editar Cliente: <?php echo htmlspecialchars($client['nombre_completo'] ?? 'ID Desconocido'); ?></h1>
                <a href="index.php" class="btn btn-secondary">Volver al Listado</a>
            </div>
            
            <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php if ($client): ?>
            <div class="card">
                <div class="card-body">
                    <form method="POST" class="form">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="tipo_documento">Tipo de Documento</label>
                                <select id="tipo_documento" name="tipo_documento" required class="form-input">
                                    <?php $currentType = $client['tipo_documento'] ?? ''; ?>
                                    <option value="CC" <?php echo ($currentType == 'CC') ? 'selected' : ''; ?>>Cédula de Ciudadanía</option>
                                    <option value="CE" <?php echo ($currentType == 'CE') ? 'selected' : ''; ?>>Cédula de Extranjería</option>
                                    <option value="NIT" <?php echo ($currentType == 'NIT') ? 'selected' : ''; ?>>NIT</option>
                                    <option value="Pasaporte" <?php echo ($currentType == 'Pasaporte') ? 'selected' : ''; ?>>Pasaporte</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="numero_documento">Número de Documento</label>
                                <input type="text" id="numero_documento" name="numero_documento" required class="form-input"
                                       value="<?php echo htmlspecialchars($client['numero_documento'] ?? ''); ?>">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="nombre_completo">Nombre Completo</label>
                            <input type="text" id="nombre_completo" name="nombre_completo" required class="form-input"
                                   value="<?php echo htmlspecialchars($client['nombre_completo'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="numero_contacto">Número de Contacto</label>
                                <input type="tel" id="numero_contacto" name="numero_contacto" required class="form-input"
                                       value="<?php echo htmlspecialchars($client['numero_contacto'] ?? ''); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="correo_electronico">Correo Electrónico</label>
                                <input type="email" id="correo_electronico" name="correo_electronico" required class="form-input"
                                       value="<?php echo htmlspecialchars($client['correo_electronico'] ?? ''); ?>">
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                    </form>
                </div>
            </div>
            <?php endif; // Fin de if ($client) ?>
        </div>
    </div>
</body>
</html>