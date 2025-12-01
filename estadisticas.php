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

/* =============================
   1) PRODUCTOS MÁS VENDIDOS
   ============================= */
$sqlTopProductos = "
    SELECT 
        pr.id,
        pr.nombre,
        pr.categoria,
        SUM(dp.cantidad) AS total_unidades,
        SUM(dp.subtotal) AS total_ingresos
    FROM detalle_pedido dp
    INNER JOIN productos pr ON dp.producto_id = pr.id
    INNER JOIN pedidos p ON dp.pedido_id = p.id
    INNER JOIN ventas v ON v.pedido_id = p.id
    GROUP BY pr.id, pr.nombre, pr.categoria
    ORDER BY total_unidades DESC
    LIMIT 10
";
$resTop = $conexion->query($sqlTopProductos);

$topProductos = [];
$maxUnidades = 0;

if ($resTop) {
    while ($row = $resTop->fetch_assoc()) {
        $topProductos[] = $row;
        if ($row['total_unidades'] > $maxUnidades) {
            $maxUnidades = $row['total_unidades'];
        }
    }
}

/* =============================
   2) VENTAS POR MES
   ============================= */
$sqlVentasMes = "
    SELECT 
        DATE_FORMAT(v.fecha, '%Y-%m') AS periodo,
        YEAR(v.fecha) AS anio,
        MONTH(v.fecha) AS mes,
        SUM(v.total) AS total_mes,
        COUNT(*) AS num_ventas
    FROM ventas v
    GROUP BY YEAR(v.fecha), MONTH(v.fecha)
    ORDER BY YEAR(v.fecha), MONTH(v.fecha)
";
$resMes = $conexion->query($sqlVentasMes);

$ventasMes = [];
$maxTotalMes = 0;

if ($resMes) {
    while ($row = $resMes->fetch_assoc()) {
        $ventasMes[] = $row;
        if ($row['total_mes'] > $maxTotalMes) {
            $maxTotalMes = $row['total_mes'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Panel de estadísticas | Mi Oficina</title>
    <link rel="stylesheet" href="css/estilos.css">
    <style>
        .stats-grid {
            display: grid;
            grid-template-columns: minmax(0, 1fr);
            gap: 1rem;
        }
        @media (min-width: 900px) {
            .stats-grid {
                grid-template-columns: 1.2fr 1fr;
            }
        }
        .bar-row {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0.35rem;
        }
        .bar-label {
            flex: 0 0 45%;
            font-size: 0.85rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .bar-track {
            flex: 1;
            background: #f0f0f0;
            border-radius: 999px;
            height: 10px;
            overflow: hidden;
        }
        .bar-fill {
            height: 100%;
            border-radius: 999px;
            background: linear-gradient(90deg, #c1731a, #f0b13a);
        }
        .bar-value {
            flex: 0 0 auto;
            font-size: 0.8rem;
            text-align: right;
            min-width: 3.5rem;
        }
        .tag {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 999px;
            font-size: 0.7rem;
            background: #eee;
        }
        .tag.cerveza { background:#fde68a; }
        .tag.sabina  { background:#bbf7d0; }
        .tag.otro    { background:#e0e7ff; }
    </style>
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
            <li><a href="reporte_ventas.php">Reporte de ventas</a></li>
            <li><a href="estadisticas.php" class="activo">Estadísticas</a></li>
            <li><a href="perfil.php">Perfil</a></li>
            <li><a href="reabastecer.php">Reabastecer</a></li>
            <li><a href="logout.php">Cerrar sesión</a></li>
        </ul>
    </aside>

    <main class="main-content">
        <h2>Panel de estadísticas</h2>

        <div class="stats-grid">

            <!-- =========================================
                 TARJETA: PRODUCTOS MÁS VENDIDOS
                 ========================================= -->
            <div class="card">
                <h3>Top 10 productos más vendidos</h3>

                <?php if (count($topProductos) === 0): ?>
                    <p>No hay datos suficientes de ventas para calcular el top de productos.</p>
                <?php else: ?>
                    <div style="margin-bottom:0.75rem;font-size:0.8rem;color:#666;">
                        Basado en el histórico de ventas registradas.
                    </div>

                    <div>
                        <?php foreach ($topProductos as $p): 
                            $porc = $maxUnidades > 0 
                                ? round(($p['total_unidades'] / $maxUnidades) * 100)
                                : 0;
                            $cat = strtolower($p['categoria']);
                            ?>
                            <div class="bar-row">
                                <div class="bar-label" title="<?php echo htmlspecialchars($p['nombre']); ?>">
                                    <?php echo htmlspecialchars($p['nombre']); ?>
                                    <?php if ($cat): ?>
                                        <span class="tag <?php echo htmlspecialchars($cat); ?>">
                                            <?php echo htmlspecialchars($p['categoria']); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <div class="bar-track">
                                    <div class="bar-fill" style="width: <?php echo max($porc, 5); ?>%;"></div>
                                </div>
                                <div class="bar-value">
                                    <?php echo (int)$p['total_unidades']; ?> u.
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <hr style="margin:0.8rem 0;">

                    <table>
                        <thead>
                            <tr>
                                <th>Producto</th>
                                <th>Categoría</th>
                                <th>Unidades vendidas</th>
                                <th>Ingresos ($)</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($topProductos as $p): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($p['nombre']); ?></td>
                                <td><?php echo htmlspecialchars(ucfirst($p['categoria'])); ?></td>
                                <td><?php echo (int)$p['total_unidades']; ?></td>
                                <td>$<?php echo number_format($p['total_ingresos'], 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>

            <!-- =========================================
                 TARJETA: VENTAS POR MES
                 ========================================= -->
            <div class="card">
                <h3>Ventas por mes</h3>

                <?php if (count($ventasMes) === 0): ?>
                    <p>No hay ventas registradas para mostrar por mes.</p>
                <?php else: ?>
                    <div style="margin-bottom:0.75rem;font-size:0.8rem;color:#666;">
                        Total de ventas por mes (histórico).
                    </div>

                    <div>
                        <?php foreach ($ventasMes as $m): 
                            $labelMes = $m['periodo']; // YYYY-MM
                            $porcMes = $maxTotalMes > 0
                                ? round(($m['total_mes'] / $maxTotalMes) * 100)
                                : 0;
                            ?>
                            <div class="bar-row">
                                <div class="bar-label">
                                    <?php echo htmlspecialchars($labelMes); ?>
                                </div>
                                <div class="bar-track">
                                    <div class="bar-fill" style="width: <?php echo max($porcMes, 5); ?>%;"></div>
                                </div>
                                <div class="bar-value">
                                    $<?php echo number_format($m['total_mes'], 0); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <hr style="margin:0.8rem 0;">

                    <table>
                        <thead>
                            <tr>
                                <th>Mes</th>
                                <th>Total ventas ($)</th>
                                <th>Núm. de ventas</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($ventasMes as $m): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($m['periodo']); ?></td>
                                <td>$<?php echo number_format($m['total_mes'], 2); ?></td>
                                <td><?php echo (int)$m['num_ventas']; ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>

        </div>
    </main>
</div>

<script src="js/reloj.js"></script>
</body>
</html>
