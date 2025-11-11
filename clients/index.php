<?php
require_once '../config/session.php';
requireLogin();
require_once '../config/database.php';

$db = new Database();
$clients = $db->executeQuery('clients', [], ['sort' => ['nombre_completo' => 1]]);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clientes - Sistema de Inventario</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
    <?php include '../includes/sidebar.php'; ?>
    
    <div class="main-content">
        <?php include '../includes/header.php'; ?>
        
        <div class="dashboard-container">
            <div class="page-header">
                <h1 class="page-title">Gestión de Clientes</h1>
                <a href="create.php" class="btn btn-primary">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="12" y1="5" x2="12" y2="19"></line>
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                    </svg>
                    Nuevo Cliente
                </a>
            </div>
            
            <div class="card">
                <div class="card-body">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Tipo Doc.</th>
                                <th>Número Doc.</th>
                                <th>Nombre Completo</th>
                                <th>Contacto</th>
                                <th>Email</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($clients as $client): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($client->tipo_documento); ?></td>
                                <td><?php echo htmlspecialchars($client->numero_documento); ?></td>
                                <td><?php echo htmlspecialchars($client->nombre_completo); ?></td>
                                <td><?php echo htmlspecialchars($client->numero_contacto); ?></td>
                                <td><?php echo htmlspecialchars($client->correo_electronico); ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="edit.php?id=<?php echo $client->_id; ?>" class="btn btn-sm btn-secondary">Editar</a>
                                        <a href="delete.php?id=<?php echo $client->_id; ?>" class="btn btn-sm btn-danger" onclick="return confirm('¿Estás seguro?')">Eliminar</a>
                                    </div>
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
