<?php
session_start();
if (!isset($_SESSION["id_usuario"])) {
    exit("Acceso no autorizado.");
}

require_once "config/conexion.php";

// Indicamos al navegador que esto es un archivo CSV descargable
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="ventas_por_dia.csv"');

// Abrimos la salida estándar como si fuera un archivo
$output = fopen('php://output', 'w');

// Escribimos la fila de encabezados
fputcsv($output, ['Fecha', 'Total_del_dia']);

// Consulta: ventas agrupadas por día
$sql = "
    SELECT DATE(fecha) AS fecha, SUM(total) AS total_dia
    FROM ventas
    GROUP BY DATE(fecha)
    ORDER BY DATE(fecha)
";
$result = $conexion->query($sql);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        // Cada fila del CSV: fecha, total_dia
        fputcsv($output, [
            $row['fecha'],
            $row['total_dia']
        ]);
    }
}

fclose($output);
exit;
