<?php
// config/conexion.php
date_default_timezone_set('America/Mexico_City');

$host = "localhost";
$usuario = "root";
$clave = "alan2004";
$bd = "oficina";

$conexion = new mysqli($host, $usuario, $clave, $bd);

if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

$conexion->set_charset("utf8mb4");
?>
