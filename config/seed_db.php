<?php
// Incluye tu conexión a la base de datos
require_once 'database.php'; 

// Define el usuario administrador inicial
$adminUser = [
    'username' => 'admin',
    'password' => password_hash('password123', PASSWORD_DEFAULT), // ¡IMPORTANTE: Usa una contraseña segura!
    'role' => 'admin',
    'email' => 'admin@sistema.com'
    // Agrega cualquier otro campo requerido
];

try {
    $db = new Database();

    // 1. Verificar si la colección 'users' está vacía
    // Usamos el Manager de MongoDB directamente para contar
    $manager = $db->getManager(); // Asumiendo que agregaste un getManager() a tu clase Database

    // Nombre de la base de datos y colección
    $dbName = 'inventory_system'; // Asegúrate de que coincida con tu $dbName
    $collectionName = 'users';

    $command = new MongoDB\Driver\Command([
        'count' => $collectionName,
        'query' => (object)[] // Cuenta todos los documentos
    ]);

    $cursor = $manager->executeCommand($dbName, $command);
    $count = $cursor->toArray()[0]->n;

    if ($count == 0) {
        // 2. Si la colección está vacía, inserta el usuario administrador
        $bulk = new MongoDB\Driver\BulkWrite;
        $bulk->insert($adminUser);
        $manager->executeBulkWrite($dbName . '.' . $collectionName, $bulk);
        
        echo "✅ Usuario administrador 'admin' creado con éxito. Contraseña: password123\n";
    } else {
        echo "Colección 'users' ya contiene datos. No se requiere inicialización.\n";
    }

} catch (Exception $e) {
    echo "❌ Error al inicializar la base de datos: " . $e->getMessage() . "\n";
}
?>
