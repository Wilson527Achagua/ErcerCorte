<?php
require 'vendor/autoload.php';
require 'C:/xampp/htdocs/ErcerSeme/
config/database.php';

$db = new Database();

$result = $db->insert("productos", [
    "codigo" => "A001",
    "nombre" => "Laptop Lenovo",
    "precio" => 3200000,
    "stock" => 5
]);

echo "Documento insertado correctamente.";
?>
