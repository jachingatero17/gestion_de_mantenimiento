<?php
$host     = "localhost";
$usuario  = "root";
$password = "";
$bd       = "control_mantenimientos";

// Conexión usando PDO (la forma más segura y moderna de PHP)
try {
    $conexion = new PDO("mysql:host=$host;dbname=$bd;charset=utf8", $usuario, $password);
    $conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Si necesitas comprobar que conecta, quita las dos barras a la siguiente línea:
    // echo "¡Conexión exitosa a la base de datos!";
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}
?>