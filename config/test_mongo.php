<?php
require_once __DIR__ . '/Database.php';


$db = new Database();

$result = $db->insert("users", [
    "codigo" => "A001",
    "nombre" => "Laptop Lenovo",
    "precio" => 3200000,
    "stock" => 5
]);

echo "Documento insertado correctamente.";
?>
