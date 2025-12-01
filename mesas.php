<?php
session_start();
if (!isset($_SESSION["id_usuario"])) {
    header("Location: login.php");
    exit;
}
require_once "config/conexion.php";

$nombre_usuario = $_SESSION["nombre"];
$rol            = $_SESSION["rol"];

$mensaje = "";

// Cambiar estado de mesa
if (isset($_GET["accion"], $_GET["id"], $_GET["estado"]) && $_GET["accion"] === "cambiar") {
    $id_mesa = intval($_GET["id"]);
    $estado  = $_GET["estado"] === "ocupada" ? "ocupada" : "libre";

    $stmt = $conexion->prepare("UPDATE mesas SET estado = ? WHERE id = ?");
    $stmt->bind_param("si", $estado, $id_mesa);
    if ($stmt->execute()) {
        $mensaje = "Estado de mesa actualizado.";
    } else {
        $mensaje = "No se pudo actualizar el estado de la mesa.";
    }
    $stmt->close();
}

// Crear nueva mesa (solo admin)
if ($_SERVER["REQUEST_METHOD"] === "POST" && $rol === "admin") {
    $numero    = intval($_POST["numero"] ?? 0);
    $capacidad = intval($_POST["capacidad"] ?? 0);

    if ($numero > 0 && $capacidad > 0) {
        $stmt = $conexion->prepare(
            "INSERT INTO mesas (numero, capacidad, estado) VALUES (?, ?, 'libre')"
        );
        $stmt->bind_param("ii", $numero, $capacidad);
        if ($stmt->execute()) {
            $mensaje = "Mesa registrada correctamente.";
        } else {
            $mensaje = "Error al registrar la mesa (¿número repetido?).";
        }
        $stmt->close();
    } else {
        $mensaje = "Número y capacidad deben ser mayores a cero.";
    }
}

// Listar mesas
$mesas = [];
$result = $conexion->query("SELECT * FROM mesas ORDER BY numero ASC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $mesas[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mi Oficina | Mesas</title>
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
    <li><a href="mesas.php" class="activo">Mesas</a></li>
    <li><a href="pedidos.php">Pedidos</a></li>
    <li><a href="ventas.php">Ventas</a></li>
    <li><a href="reporte_ventas.php">Reporte de ventas</a></li>
    <li><a href="estadisticas.php">Estadísticas</a></li>
    <li><a href="perfil.php">Perfil</a></li>
    <li><a href="reabastecer.php">Reabastecer</a></li>
    <li><a href="logout.php">Cerrar sesión</a></li>
</ul>
    </aside>

    <main class="main-content">
        <h2>Mesas</h2>

        <?php if ($mensaje): ?>
            <div class="alert" style="margin-bottom:1rem;"><?php echo htmlspecialchars($mensaje); ?></div>
        <?php endif; ?>

        <div class="card">
            <h3 style="margin-bottom:0.8rem;">Listado de mesas</h3>
            <?php if (count($mesas) === 0): ?>
                <p>No hay mesas registradas.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Número</th>
                            <th>Capacidad</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($mesas as $m): ?>
                        <tr>
                            <td><?php echo $m["numero"]; ?></td>
                            <td><?php echo $m["capacidad"]; ?></td>
                            <td>
                                <?php if ($m["estado"] === "libre"): ?>
                                    <span class="estado-libre">Libre</span>
                                <?php else: ?>
                                    <span class="estado-ocupada">Ocupada</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($m["estado"] === "libre"): ?>
                                    <a class="btn" style="font-size:0.75rem; padding:0.3rem 0.8rem;"
                                       href="mesas.php?accion=cambiar&id=<?php echo $m["id"]; ?>&estado=ocupada">
                                        Marcar como ocupada
                                    </a>
                                <?php else: ?>
                                    <a class="btn" style="font-size:0.75rem; padding:0.3rem 0.8rem;"
                                       href="mesas.php?accion=cambiar&id=<?php echo $m["id"]; ?>&estado=libre">
                                        Marcar como libre
                                    </a>
                                <?php endif; ?>

                                <a class="btn" style="font-size:0.75rem; padding:0.3rem 0.8rem; margin-left:0.3rem; background:#f97316; color:#111;"
                                   href="gestionar_pedido.php?mesa_id=<?php echo $m["id"]; ?>">
                                    Abrir / ver pedido
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <?php if ($rol === "admin"): ?>
        <div class="card">
            <h3 style="margin-bottom:0.8rem;">Registrar nueva mesa</h3>
            <form method="post">
                <div class="form-group">
                    <label for="numero">Número de mesa</label>
                    <input type="number" id="numero" name="numero" required>
                </div>
                <div class="form-group">
                    <label for="capacidad">Capacidad</label>
                    <input type="number" id="capacidad" name="capacidad" required>
                </div>
                <button type="submit" class="btn">Guardar mesa</button>
            </form>
        </div>
        <?php endif; ?>
    </main>
</div>

<script src="js/reloj.js"></script>
</body>
</html>
