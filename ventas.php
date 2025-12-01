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

// Traer historial de ventas (todas), con pedido, mesa y mesero
$sql = "
    SELECT 
        v.id            AS id_venta,
        v.pedido_id     AS id_pedido,
        v.total,
        v.fecha,
        m.numero        AS mesa_numero,
        u.nombre        AS mesero
    FROM ventas v
    INNER JOIN pedidos p   ON v.pedido_id = p.id
    INNER JOIN mesas m     ON p.mesa_id   = m.id
    INNER JOIN usuarios u  ON p.usuario_id = u.id
    ORDER BY v.fecha DESC
";

$result = $conexion->query($sql);
$ventas = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $ventas[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mi Oficina | Ventas</title>
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
    <li><a href="ventas.php" class="activo">Ventas</a></li>
    <li><a href="reporte_ventas.php">Reporte de ventas</a></li>
    <li><a href="estadisticas.php">Estadísticas</a></li>
    <li><a href="perfil.php">Perfil</a></li>
    <li><a href="reabastecer.php">Reabastecer</a></li>
    <li><a href="logout.php">Cerrar sesión</a></li>
</ul>
    </aside>

    <main class="main-content">
        <h2>Historial de ventas</h2>

        <!-- Botón para descargar CSV para Excel -->
        <div style="margin-bottom: 1rem;">
            <a href="exportar_ventas_csv.php"
               class="btn"
               style="
                    background:#e0a123;
                    padding:8px 16px;
                    border-radius:6px;
                    font-weight:bold;
                    text-decoration:none;
                    color:white;
                ">
                Descargar ventas (Excel CSV)
            </a>
        </div>

        <div class="card">
            <?php if (count($ventas) === 0): ?>
                <p>No hay ventas registradas.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID Venta</th>
                            <th>ID Pedido</th>
                            <th>Mesa</th>
                            <th>Mesero</th>
                            <th>Total</th>
                            <th>Fecha</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($ventas as $v): ?>
                        <tr>
                            <td><?php echo (int)$v["id_venta"]; ?></td>
                            <td><?php echo (int)$v["id_pedido"]; ?></td>
                            <td>Mesa <?php echo htmlspecialchars($v["mesa_numero"]); ?></td>
                            <td><?php echo htmlspecialchars($v["mesero"]); ?></td>
                            <td>$<?php echo number_format($v["total"], 2); ?></td>
                            <td><?php echo htmlspecialchars($v["fecha"]); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </main>
</div>

<script src="js/reloj.js"></script>
</body>
</html>
