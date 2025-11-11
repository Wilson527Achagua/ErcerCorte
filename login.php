<?php
// login.php

// Inclusión de archivos necesarios
require_once 'config/session.php';
require_once 'config/database.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanear y limpiar las entradas
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? ''); // ¡La contraseña no se hashea!
    
    if ($username && $password) {
        $db = new Database();
        
        // Buscar el usuario por nombre de usuario
        $users = $db->executeQuery('users', ['username' => $username]);
        
        $authenticated = false;

        // Asegurarse de que $users sea iterable
        foreach ($users as $user) {
            // Convertir a array por si el documento viene como objeto BSON
            $user = (array)$user;

            // ⛔ LÓGICA INSEGURA: Comparación directa de la contraseña
            // Esto solo funciona si la columna 'password' en MongoDB tiene la contraseña en texto plano.
            if (isset($user['password']) && $password === $user['password']) {
                // Autenticación exitosa
                $_SESSION['user_id'] = (string)$user['_id'];
                $_SESSION['username'] = $user['username'];
                $authenticated = true;
                
                // Redirigir al panel principal
                header('Location: index.php');
                exit();
            }
        }
        
        // Si no se autenticó después de revisar todos los usuarios
        if (!$authenticated) {
            $error = 'Usuario o contraseña incorrectos';
        }
    } else {
        $error = 'Por favor, completa todos los campos.';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistema de Inventario</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body class="login-page">
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <h1>Sistema de Inventario</h1>
                <p>Ingresa tus credenciales</p>
            </div>
            
            <?php if ($error): ?>
            <div class="alert alert-error">
                <?php echo htmlspecialchars($error); ?>
            </div>
            <?php endif; ?>
            
            <form method="POST" class="login-form">
                <div class="form-group">
                    <label for="username">Usuario</label>
                    <input type="text" id="username" name="username" required class="form-input"
                           value="<?php echo htmlspecialchars($username ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label for="password">Contraseña</label>
                    <input type="password" id="password" name="password" required class="form-input">
                </div>
                
                <button type="submit" class="btn btn-primary btn-block">Iniciar Sesión</button>
            </form>
        </div>
    </div>
</body>
</html>