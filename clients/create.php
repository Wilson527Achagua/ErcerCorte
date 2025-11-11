<?php
require_once '../config/session.php';
requireLogin();
require_once '../config/database.php';

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db = new Database();
    
    $document = [
        'tipo_documento' => $_POST['tipo_documento'],
        'numero_documento' => $_POST['numero_documento'],
        'nombre_completo' => $_POST['nombre_completo'],
        'numero_contacto' => $_POST['numero_contacto'],
        'correo_electronico' => $_POST['correo_electronico'],
        'fecha_registro' => new MongoDB\BSON\UTCDateTime()
    ];
    
    try {
        $db->insert('clients', $document);
        $success = 'Cliente registrado exitosamente';
    } catch (Exception $e) {
        $error = 'Error al registrar cliente: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nuevo Cliente - Sistema de Inventario</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
    <?php include '../includes/sidebar.php'; ?>
    
    <div class="main-content">
        <?php include '../includes/header.php'; ?>
        
        <div class="dashboard-container">
            <div class="page-header">
                <h1 class="page-title">Registrar Nuevo Cliente</h1>
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
                    <form method="POST" class="form">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="tipo_documento">Tipo de Documento</label>
                                <select id="tipo_documento" name="tipo_documento" required class="form-input">
                                    <option value="">Seleccionar...</option>
                                    <option value="CC">Cédula de Ciudadanía</option>
                                    <option value="CE">Cédula de Extranjería</option>
                                    <option value="NIT">NIT</option>
                                    <option value="Pasaporte">Pasaporte</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="numero_documento">Número de Documento</label>
                                <input type="text" id="numero_documento" name="numero_documento" required class="form-input">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="nombre_completo">Nombre Completo</label>
                            <input type="text" id="nombre_completo" name="nombre_completo" required class="form-input">
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="numero_contacto">Número de Contacto</label>
                                <input type="tel" id="numero_contacto" name="numero_contacto" required class="form-input">
                            </div>
                            
                            <div class="form-group">
                                <label for="correo_electronico">Correo Electrónico</label>
                                <input type="email" id="correo_electronico" name="correo_electronico" required class="form-input">
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Registrar Cliente</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
