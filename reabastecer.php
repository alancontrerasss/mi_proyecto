<?php
session_start();
if (!isset($_SESSION["id_usuario"])) {
    header("Location: login.php");
    exit;
}
require_once "config/conexion.php";

if ($_SESSION["rol"] !== "admin") {
    die("Solo el administrador puede reabastecer productos.");
}

$nombre_usuario = $_SESSION["nombre"];
$rol            = $_SESSION["rol"];

$productos = [];
$res = $conexion->query("SELECT id, nombre, stock FROM productos ORDER BY nombre");
while ($row = $res->fetch_assoc()) {
    $productos[] = $row;
}

$mensaje = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $producto_id = intval($_POST["producto_id"]);
    $cantidad    = intval($_POST["cantidad"]);

    if ($producto_id > 0 && $cantidad > 0) {
        $stmt = $conexion->prepare("UPDATE productos SET stock = stock + ? WHERE id = ?");
        $stmt->bind_param("ii", $cantidad, $producto_id);
        if ($stmt->execute()) {
            $mensaje = "Stock actualizado correctamente.";
        } else {
            $mensaje = "Error al actualizar stock.";
        }
        $stmt->close();
    } else {
        $mensaje = "Datos incorrectos.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mi Oficina | Reabastecer</title>
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
    <li><a href="pedidos.php">Pedidos</a></li>
    <li><a href="ventas.php">Ventas</a></li>
    <li><a href="reporte_ventas.php">Reporte de ventas</a></li>
    <li><a href="estadisticas.php">Estadísticas</a></li>
    <li><a href="perfil.php">Perfil</a></li>
    <li><a href="reabastecer.php" class="activo">Reabastecer</a></li>
    <li><a href="logout.php">Cerrar sesión</a></li>
</ul>

    </aside>

    <main class="main-content">
        <h2>Reabastecer stock</h2>

        <?php if ($mensaje): ?>
            <div class="alert"><?php echo htmlspecialchars($mensaje); ?></div>
        <?php endif; ?>

        <div class="card">
            <form method="post">
                <div class="form-group">
                    <label>Producto</label>
                    <select name="producto_id" required>
                        <option value="">Selecciona...</option>
                        <?php foreach ($productos as $p): ?>
                            <option value="<?php echo $p["id"]; ?>">
                                <?php echo htmlspecialchars($p["nombre"]) . " (Stock actual: " . $p["stock"] . ")"; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Cantidad a sumar</label>
                    <input type="number" name="cantidad" min="1" required>
                </div>

                <button class="btn" type="submit">Actualizar</button>
            </form>
        </div>
    </main>
</div>

<script src="js/reloj.js"></script>
</body>
</html>
