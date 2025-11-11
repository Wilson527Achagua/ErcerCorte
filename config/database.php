<?php

// Importar las clases necesarias del driver de MongoDB para PHP
// Esto es CRÍTICO para que el código reconozca las clases como Manager, BulkWrite, Query, etc.
use MongoDB\Driver\Manager;
use MongoDB\Driver\BulkWrite;
use MongoDB\Driver\Query;
use MongoDB\Driver\Exception\Exception;
use MongoDB\BSON\ObjectId;

/**
 * Clase para manejar la conexión y operaciones CRUD con MongoDB
 * Utiliza el driver PHP nativo (MongoDB\Driver\Manager).
 */
class Database {
    private $client;
    private $db;
    
    // Configura tu base de datos aquí
    private $databaseName = "inventory_system";
    
    public function __construct() {
        try {
            // Establece la conexión con el servidor de MongoDB
            // Asegúrate de que esta cadena de conexión sea correcta para tu entorno
            $this->client = new Manager("mongodb://localhost:27017");
            $this->db = $this->databaseName;
        } catch (Exception $e) {
            // Detiene la ejecución si la conexión falla
            die("Error de conexión a MongoDB: " . $e->getMessage());
        }
    }
    
    public function getConnection() {
        return $this->client;
    }
    
    public function getDatabaseName() {
        return $this->db;
    }
    
    /**
     * Ejecuta una consulta FIND y devuelve los documentos como un array de PHP.
     * Esta corrección es crucial para que los foreach en PHP (login.php, edit.php) funcionen.
     * * @param string $collection Nombre de la colección (ej. 'clients', 'users').
     * @param array $filter Criterios de búsqueda (ej. ['_id' => $objectId]).
     * @param array $options Opciones de consulta (ej. ['limit' => 1]).
     * @return array Array de documentos encontrados.
     */
    public function executeQuery(string $collection, array $filter = [], array $options = []): array {
        $query = new Query($filter, $options);
        $namespace = $this->db . "." . $collection;
        
        $cursor = $this->client->executeQuery($namespace, $query);
        
        // Convierte el Cursor a un array para facilitar su uso en PHP
        return $cursor->toArray();
    }
    
    /**
     * Inserta un documento en una colección.
     * * @param string $collection Nombre de la colección.
     * @param array $document Documento a insertar.
     * @return MongoDB\Driver\WriteResult Resultado de la operación.
     */
    // Método para insertar documentos
    public function insert(string $collection, $document) {
       $bulk = new MongoDB\Driver\BulkWrite;
    
       // 1. Añadimos el documento a la operación de escritura
       $id = $bulk->insert($document); 
       // CRÍTICO: El método insert() del BulkWrite devuelve el ObjectId generado.
    
       $namespace = $this->db . "." . $collection;
    
       // 2. Ejecutamos la escritura y obtenemos el resultado general
       $writeResult = $this->client->executeBulkWrite($namespace, $bulk);
    
       // 3. Devolvemos el resultado y el ID generado
       return [
           'writeResult' => $writeResult,
           'insertedId' => $id
        ];
    }
    
    /**
     * Actualiza documentos en una colección.
     * * @param string $collection Nombre de la colección.
     * @param array $filter Criterios para seleccionar los documentos.
     * @param array $updateData Datos a actualizar.
     * @param array $options Opciones (ej. 'multi' => true/false).
     * @return MongoDB\Driver\WriteResult Resultado de la operación.
     */
    // En tu archivo '../config/database.php'
public function update(string $collection, array $filter, array $update_operators) {
    $bulk = new MongoDB\Driver\BulkWrite;
    
    // El '['update' => 1]' es el operador de actualización que indica que no es un reemplazo completo.
    // Usamos ['multi' => false, 'upsert' => false] como opciones por defecto.
    $bulk->update($filter, $update_operators, ['multi' => false, 'upsert' => false]); 
    
    $namespace = $this->db . "." . $collection;
    
    return $this->client->executeBulkWrite($namespace, $bulk);
}
    
    /**
     * Elimina documentos de una colección.
     * * @param string $collection Nombre de la colección.
     * @param array $filter Criterios para seleccionar los documentos a eliminar.
     * @param int $limit Límite de documentos a eliminar (1 por defecto para eliminación por ID).
     * @return MongoDB\Driver\WriteResult Resultado de la operación.
     */
    public function delete(string $collection, array $filter, int $limit = 1) {
        $bulk = new BulkWrite;
        
        // Usa el límite para asegurar que solo se borre el documento deseado
        $bulk->delete($filter, ['limit' => $limit]); 
        
        $namespace = $this->db . "." . $collection;
        return $this->client->executeBulkWrite($namespace, $bulk);
    }
}
?>