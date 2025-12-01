<?php
// exportar_ventas_excel.php
session_start();
if (!isset($_SESSION["id_usuario"])) {
    exit("Acceso no autorizado.");
}

require_once "config/conexion.php";
require_once "vendor/autoload.php";

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Chart\Chart;
use PhpOffice\PhpSpreadsheet\Chart\DataSeries;
use PhpOffice\PhpSpreadsheet\Chart\DataSeriesValues;
use PhpOffice\PhpSpreadsheet\Chart\Layout;
use PhpOffice\PhpSpreadsheet\Chart\Legend;
use PhpOffice\PhpSpreadsheet\Chart\PlotArea;
use PhpOffice\PhpSpreadsheet\Chart\Title;

date_default_timezone_set("America/Mexico_City");

// Misma lógica que en reporte_ventas.php
$fecha = isset($_GET["fecha"]) && $_GET["fecha"] !== ""
    ? $_GET["fecha"]
    : date("Y-m-d");

// Traer las ventas de ese día
$stmt = $conexion->prepare("
    SELECT v.id, v.total, v.fecha, p.mesa_id, m.numero AS mesa_num
    FROM ventas v
    INNER JOIN pedidos p ON v.pedido_id = p.id
    INNER JOIN mesas m ON p.mesa_id = m.id
    WHERE DATE(v.fecha) = ?
    ORDER BY v.fecha ASC
");
$stmt->bind_param("s", $fecha);
$stmt->execute();
$res = $stmt->get_result();

$ventas = [];
while ($row = $res->fetch_assoc()) {
    $ventas[] = $row;
}
$stmt->close();

if (count($ventas) === 0) {
    exit("No hay ventas para la fecha seleccionada.");
}

// =======================
// Crear el Excel
// =======================
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Ventas del día');

// Encabezados
$sheet->setCellValue('A1', 'ID Venta');
$sheet->setCellValue('B1', 'Hora');
$sheet->setCellValue('C1', 'Mesa');
$sheet->setCellValue('D1', 'Total');

// Rellenar datos
$fila = 2;
foreach ($ventas as $v) {
    $hora = date('H:i:s', strtotime($v['fecha']));
    $sheet->setCellValue('A' . $fila, $v['id']);
    $sheet->setCellValue('B' . $fila, $hora);
    $sheet->setCellValue('C' . $fila, $v['mesa_num']);
    $sheet->setCellValue('D' . $fila, $v['total']);
    $fila++;
}

// Ajustar ancho de columnas
foreach (['A','B','C','D'] as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

// =======================
// Crear la gráfica
// =======================
$lastRow = $fila - 1;

// Categorías: horas (columna B)
$categories = [
    new DataSeriesValues('String', "'Ventas del día'!B2:B{$lastRow}", null, $lastRow - 1)
];

// Valores: totales (columna D)
$values = [
    new DataSeriesValues('Number', "'Ventas del día'!D2:D{$lastRow}", null, $lastRow - 1)
];

$series = new DataSeries(
    DataSeries::TYPE_BARCHART,           // tipo de gráfica
    DataSeries::GROUPING_CLUSTERED,      // barras agrupadas
    range(0, count($values) - 1),        // plot order
    [],                                  // leyendas de serie (no usamos)
    $categories,
    $values
);
$series->setPlotDirection(DataSeries::DIRECTION_COL);

// Área de la gráfica
$plotArea = new PlotArea(null, [$series]);

$legend = new Legend(Legend::POSITION_RIGHT, null, false);
$title  = new Title('Ventas por hora');
$yTitle = new Title('Monto ($)');

$chart = new Chart(
    'grafica_ventas',
    $title,
    $legend,
    $plotArea,
    true,
    0,
    null,
    $yTitle
);

// Posición de la gráfica dentro de la hoja
$chart->setTopLeftPosition('F2');
$chart->setBottomRightPosition('N20');

$sheet->addChart($chart);

// =======================
// Descargar el archivo
// =======================
$nombreArchivo = 'ventas_' . $fecha . '.xlsx';

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="'.$nombreArchivo.'"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->setIncludeCharts(true);
$writer->save('php://output');
exit;
