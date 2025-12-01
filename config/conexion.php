<?php
// config/conexion.php

date_default_timezone_set('America/Mexico_City');

$host    = getenv('MYSQLHOST') ?: '127.0.0.1';
$usuario = getenv('MYSQLUSER') ?: 'root';
$clave   = getenv('MYSQLPASSWORD') ?: '';
$bd      = getenv('MYSQLDATABASE') ?: 'oficina';
$port    = getenv('MYSQLPORT') ?: 3306;

$conexion = new mysqli($host, $usuario, $clave, $bd, (int)$port);

if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

$conexion->set_charset("utf8mb4");
?>
