<?php
// exportar_estadisticas_excel.php
session_start();
if (!isset($_SESSION["id_usuario"])) {
    exit("Acceso no autorizado.");
}

require_once "config/conexion.php";
require_once "vendor/autoload.php";

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

date_default_timezone_set("America/Mexico_City");

/* =============================
   1) PRODUCTOS MÁS VENDIDOS
   ============================= */
$sqlTopProductos = "
    SELECT 
        pr.id,
        pr.nombre,
        pr.categoria,
        SUM(dp.cantidad)    AS total_unidades,
        SUM(dp.subtotal)    AS total_ingresos
    FROM detalle_pedido dp
    INNER JOIN productos pr ON dp.producto_id = pr.id
    INNER JOIN pedidos p    ON dp.pedido_id = p.id
    INNER JOIN ventas v     ON v.pedido_id   = p.id
    GROUP BY pr.id, pr.nombre, pr.categoria
    ORDER BY total_unidades DESC
    LIMIT 10
";
$resTop = $conexion->query($sqlTopProductos);

$topProductos = [];
if ($resTop) {
    while ($row = $resTop->fetch_assoc()) {
        $topProductos[] = $row;
    }
}

/* =============================
   2) VENTAS POR MES
   ============================= */
$sqlVentasMes = "
    SELECT 
        DATE_FORMAT(v.fecha, '%Y-%m') AS periodo,
        YEAR(v.fecha)  AS anio,
        MONTH(v.fecha) AS mes,
        SUM(v.total)   AS total_mes,
        COUNT(*)       AS num_ventas
    FROM ventas v
    GROUP BY YEAR(v.fecha), MONTH(v.fecha)
    ORDER BY YEAR(v.fecha), MONTH(v.fecha)
";
$resMes = $conexion->query($sqlVentasMes);

$ventasMes = [];
if ($resMes) {
    while ($row = $resMes->fetch_assoc()) {
        $ventasMes[] = $row;
    }
}

/* =============================
   CREAR ARCHIVO EXCEL
   ============================= */
$spreadsheet = new Spreadsheet();

/* ---------- Hoja 1: Top productos ---------- */
$sheet1 = $spreadsheet->getActiveSheet();
$sheet1->setTitle('Top productos');

// Encabezados
$sheet1->setCellValue('A1', 'ID producto');
$sheet1->setCellValue('B1', 'Nombre');
$sheet1->setCellValue('C1', 'Categoría');
$sheet1->setCellValue('D1', 'Unidades vendidas');
$sheet1->setCellValue('E1', 'Ingresos ($)');

// Datos
$fila = 2;
foreach ($topProductos as $p) {
    $sheet1->setCellValue("A{$fila}", $p['id']);
    $sheet1->setCellValue("B{$fila}", $p['nombre']);
    $sheet1->setCellValue("C{$fila}", $p['categoria']);
    $sheet1->setCellValue("D{$fila}", $p['total_unidades']);
    $sheet1->setCellValue("E{$fila}", $p['total_ingresos']);
    $fila++;
}

// Ajustar ancho
foreach (['A','B','C','D','E'] as $col) {
    $sheet1->getColumnDimension($col)->setAutoSize(true);
}

/* ---------- Hoja 2: Ventas por mes ---------- */
$sheet2 = $spreadsheet->createSheet();
$sheet2->setTitle('Ventas por mes');

// Encabezados
$sheet2->setCellValue('A1', 'Periodo (YYYY-MM)');
$sheet2->setCellValue('B1', 'Total ventas ($)');
$sheet2->setCellValue('C1', 'Número de ventas');

$fila = 2;
foreach ($ventasMes as $m) {
    $sheet2->setCellValue("A{$fila}", $m['periodo']);
    $sheet2->setCellValue("B{$fila}", $m['total_mes']);
    $sheet2->setCellValue("C{$fila}", $m['num_ventas']);
    $fila++;
}

// Ajustar ancho
foreach (['A','B','C'] as $col) {
    $sheet2->getColumnDimension($col)->setAutoSize(true);
}

/* =============================
   ENVIAR AL NAVEGADOR
   ============================= */
$nombreArchivo = 'estadisticas_' . date('Ymd_His') . '.xlsx';

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="'.$nombreArchivo.'"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
