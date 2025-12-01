<?php
session_start();
if (!isset($_SESSION["id_usuario"])) {
    header("Location: login.php");
    exit;
}

require_once "config/conexion.php";

$id_usuario     = $_SESSION["id_usuario"];
$nombre_usuario = $_SESSION["nombre"];
$rol            = $_SESSION["rol"];
$bd             = $conexion->query("SELECT DATABASE()")->fetch_row()[0];

date_default_timezone_set("America/Mexico_City");

// Fecha seleccionada (por defecto hoy)
$fecha = isset($_GET["fecha"]) && $_GET["fecha"] !== ""
    ? $_GET["fecha"]
    : date("Y-m-d");

// Obtener ventas de esa fecha
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

$ventas    = [];
$total_dia = 0;

while ($row = $res->fetch_assoc()) {
    $ventas[]  = $row;
    $total_dia += $row["total"];
}

$stmt->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de ventas | Mi Oficina</title>
    <link rel="stylesheet" href="css/estilos.css">
</head>
<body>

<header class="main-header">
    <div class="container main-header-content">
        <div class="brand">
            <img src="img/logo.jpeg" alt="Logo Mi Oficina">
            <div class="brand-text">
                <span>Mi Oficina</span>
                <span>CERVECERÍA</span>
            </div>
        </div>
        <div class="user-info">
            <div>Usuario: <strong><?php echo htmlspecialchars($nombre_usuario); ?></strong></div>
            <div>Rol: <?php echo ucfirst(htmlspecialchars($rol)); ?></div>
            <div>BD: <strong><?php echo htmlspecialchars($bd); ?></strong></div>
            <div style="margin-top:0.3rem; font-size:0.8rem;">
                <span id="reloj-fecha"></span><br>
                <span id="reloj-hora"></span>
            </div>
        </div>
    </div>
</header>

<div class="layout">
    <aside class="sidebar">
        <h3>Menú</h3>
        <ul class="menu">
    <li><a href="menu.php">Inicio</a></li>
    <li><a href="productos.php">Productos</a></li>
    <li><a href="mesas.php">Mesas</a></li>
    <li><a href="pedidos.php">Pedidos</a></li>
    <li><a href="ventas.php">Ventas</a></li>
    <li><a href="reporte_ventas.php" class="activo">Reporte de ventas</a></li>
    <li><a href="estadisticas.php">Estadísticas</a></li>
    <li><a href="perfil.php">Perfil</a></li>
    <li><a href="reabastecer.php">Reabastecer</a></li>
    <li><a href="logout.php">Cerrar sesión</a></li>
</ul>
    </aside>

    <main class="main-content">
        <h2>Reporte de ventas</h2>

        <div class="card" style="margin-bottom: 1rem;">
            <form method="get" style="display:flex;flex-wrap:wrap;gap:0.5rem;align-items:center;">
                <label for="fecha">Fecha:</label>
                <input
                    type="date"
                    id="fecha"
                    name="fecha"
                    value="<?php echo htmlspecialchars($fecha); ?>"
                >
                <button class="btn">Filtrar</button>

                <!-- Botón para descargar CSV con resumen por día -->
                <a class="btn" href="exportar_ventas_csv.php" style="margin-left:auto;">
                    Descargar ventas (CSV)
                </a>

                <!-- Botón para descargar Excel del día con gráfica -->
                <a class="btn" href="exportar_ventas_excel.php?fecha=<?php echo urlencode($fecha); ?>">
                    Excel del día (con gráfica)
                </a>
            </form>
        </div>

        <div class="card">
            <h3>Ventas del <?php echo date("d/m/Y", strtotime($fecha)); ?></h3>

            <?php if (count($ventas) === 0): ?>
                <p>No hay ventas registradas en esta fecha.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID Venta</th>
                            <th>Hora</th>
                            <th>Mesa</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($ventas as $v): ?>
                        <tr>
                            <td><?php echo (int)$v["id"]; ?></td>
                            <td><?php echo date("H:i:s", strtotime($v["fecha"])); ?></td>
                            <td>Mesa <?php echo htmlspecialchars($v["mesa_num"]); ?></td>
                            <td>$<?php echo number_format($v["total"], 2); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>

                <div style="margin-top:0.8rem;font-weight:bold;font-size:1rem;">
                    Total del día: $<?php echo number_format($total_dia, 2); ?>
                </div>
            <?php endif; ?>
        </div>
    </main>
</div>

<script src="js/reloj.js"></script>
</body>
</html>
