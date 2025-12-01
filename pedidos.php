<?php
session_start();
if (!isset($_SESSION["id_usuario"])) {
    header("Location: login.php");
    exit;
}
require_once "config/conexion.php";

$nombre_usuario = $_SESSION["nombre"];
$rol            = $_SESSION["rol"];

$pedidos_abiertos = [];
$pedidos_todos    = [];

// Pedidos abiertos
$sql_abiertos = "
    SELECT p.id, p.estado, p.total, p.fecha_apertura,
           m.numero AS mesa_numero,
           u.nombre AS mesero
    FROM pedidos p
    INNER JOIN mesas m ON p.mesa_id = m.id
    INNER JOIN usuarios u ON p.usuario_id = u.id
    WHERE p.estado = 'abierto'
    ORDER BY p.fecha_apertura DESC
";
$res = $conexion->query($sql_abiertos);
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $pedidos_abiertos[] = $row;
    }
}

// Últimos pedidos
$sql_todos = "
    SELECT p.id, p.estado, p.total, p.fecha_apertura,
           m.numero AS mesa_numero,
           u.nombre AS mesero
    FROM pedidos p
    INNER JOIN mesas m ON p.mesa_id = m.id
    INNER JOIN usuarios u ON p.usuario_id = u.id
    ORDER BY p.fecha_apertura DESC
    LIMIT 20
";
$res2 = $conexion->query($sql_todos);
if ($res2) {
    while ($row = $res2->fetch_assoc()) {
        $pedidos_todos[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mi Oficina | Pedidos</title>
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
            <div>Rol: <?php echo htmlspecialchars(ucfirst($rol)); ?></div>
            <div>BD: <strong><?php echo htmlspecialchars($bd); ?></strong></div>
            <div style="margin-top: 0.3rem; font-size: 0.8rem;">
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
    <li><a href="pedidos.php" class="activo">Pedidos</a></li>
    <li><a href="ventas.php">Ventas</a></li>
    <li><a href="reporte_ventas.php">Reporte de ventas</a></li>
    <li><a href="estadisticas.php">Estadísticas</a></li>
    <li><a href="perfil.php">Perfil</a></li>
    <li><a href="reabastecer.php">Reabastecer</a></li>
    <li><a href="logout.php">Cerrar sesión</a></li>
</ul>
    </aside>

    <main class="main-content">
        <h2>Pedidos</h2>

        <div class="card">
            <h3 style="margin-bottom:0.8rem;">Pedidos abiertos</h3>
            <?php if (count($pedidos_abiertos) === 0): ?>
                <p>No hay pedidos abiertos.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Mesa</th>
                            <th>Mesero</th>
                            <th>Total</th>
                            <th>Estado</th>
                            <th>Fecha apertura</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($pedidos_abiertos as $p): ?>
                        <tr>
                            <td><?php echo $p["id"]; ?></td>
                            <td>Mesa <?php echo $p["mesa_numero"]; ?></td>
                            <td><?php echo htmlspecialchars($p["mesero"]); ?></td>
                            <td>$<?php echo number_format($p["total"], 2); ?></td>
                            <td><span class="tag-estado estado-abierto">Abierto</span></td>
                            <td><?php echo $p["fecha_apertura"]; ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <div class="card">
            <h3 style="margin-bottom:0.8rem;">Últimos pedidos</h3>
            <?php if (count($pedidos_todos) === 0): ?>
                <p>No hay pedidos registrados.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Mesa</th>
                            <th>Mesero</th>
                            <th>Total</th>
                            <th>Estado</th>
                            <th>Fecha apertura</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($pedidos_todos as $p): ?>
                        <tr>
                            <td><?php echo $p["id"]; ?></td>
                            <td>Mesa <?php echo $p["mesa_numero"]; ?></td>
                            <td><?php echo htmlspecialchars($p["mesero"]); ?></td>
                            <td>$<?php echo number_format($p["total"], 2); ?></td>
                            <td>
                                <?php
                                $class = $p["estado"] === "abierto" ? "estado-abierto"
                                        : ($p["estado"] === "cerrado" ? "estado-cerrado" : "estado-pagado");
                                ?>
                                <span class="tag-estado <?php echo $class; ?>">
                                    <?php echo ucfirst($p["estado"]); ?>
                                </span>
                            </td>
                            <td><?php echo $p["fecha_apertura"]; ?></td>
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
