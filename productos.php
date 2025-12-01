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

/* ============================
   ELIMINAR (DESACTIVAR) PRODUCTO
   ============================ */
if (isset($_GET["accion"], $_GET["id"]) && $_GET["accion"] === "eliminar" && $rol === "admin") {
    $id_eliminar = intval($_GET["id"]);

    $stmt = $conexion->prepare("UPDATE productos SET activo = 0 WHERE id = ?");
    $stmt->bind_param("i", $id_eliminar);
    if ($stmt->execute()) {
        $mensaje = "Producto eliminado (marcado como inactivo).";
    } else {
        $mensaje = "No se pudo eliminar el producto.";
    }
    $stmt->close();
}

/* ============================
   ALTA DE PRODUCTO (SOLO ADMIN)
   ============================ */
if ($_SERVER["REQUEST_METHOD"] === "POST" && $rol === "admin") {
    $nombre      = trim($_POST["nombre"] ?? "");
    $categoria   = $_POST["categoria"] ?? "cerveza";
    $precio      = floatval($_POST["precio"] ?? 0);
    $stock       = intval($_POST["stock"] ?? 0);
    $descripcion = trim($_POST["descripcion"] ?? "");
    $imagen      = trim($_POST["imagen"] ?? "");

    if ($nombre !== "" && $precio > 0 && $stock >= 0) {
        $stmt = $conexion->prepare(
            "INSERT INTO productos (nombre, categoria, precio, stock, descripcion, imagen, activo)
             VALUES (?, ?, ?, ?, ?, ?, TRUE)"
        );
        $stmt->bind_param("ssdiis", $nombre, $categoria, $precio, $stock, $descripcion, $imagen);
        if ($stmt->execute()) {
            $mensaje = "Producto registrado correctamente.";
        } else {
            $mensaje = "Error al registrar el producto.";
        }
        $stmt->close();
    } else {
        $mensaje = "Revisa nombre, precio y stock.";
    }
}

/* ============================
   OBTENER PRODUCTOS ACTIVOS
   ============================ */
$productos = [];
$result = $conexion->query("SELECT * FROM productos WHERE activo = 1 ORDER BY id DESC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $productos[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mi Oficina | Productos</title>
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
    <li><a href="productos.php" class="activo">Productos</a></li>
    <li><a href="mesas.php">Mesas</a></li>
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
        <h2>Productos</h2>

        <?php if ($mensaje): ?>
            <div class="alert" style="margin-bottom:1rem;"><?php echo htmlspecialchars($mensaje); ?></div>
        <?php endif; ?>

        <div class="card">
            <h3 style="margin-bottom:0.8rem;">Listado de productos</h3>
            <?php if (count($productos) === 0): ?>
                <p>No hay productos registrados.</p>
            <?php else: ?>
                <div style="overflow-x:auto;">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nombre</th>
                                <th>Categoría</th>
                                <th>Precio</th>
                                <th>Stock</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($productos as $p): ?>
                            <tr>
                                <td><?php echo $p["id"]; ?></td>
                                <td><?php echo htmlspecialchars($p["nombre"]); ?></td>
                                <td><?php echo htmlspecialchars($p["categoria"]); ?></td>
                                <td>$<?php echo number_format($p["precio"], 2); ?></td>
                                <td>
                                    <?php
                                    $stock = (int)$p["stock"];
                                    if ($stock <= 5) {
                                        echo '<span class="estado-ocupada">Poco: ' . $stock . '</span>';
                                    } elseif ($stock <= 10) {
                                        echo '<span class="tag-estado estado-abierto">Bajo: ' . $stock . '</span>';
                                    } else {
                                        echo $stock;
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php if ($p["activo"]): ?>
                                        <span class="tag-activo">Activo</span>
                                    <?php else: ?>
                                        <span class="tag-inactivo">Inactivo</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($rol === "admin"): ?>
                                        <a class="btn"
                                           style="font-size:0.75rem; padding:0.25rem 0.8rem; background:#b91c1c; color:#fff;"
                                           href="productos.php?accion=eliminar&id=<?php echo $p['id']; ?>"
                                           onclick="return confirm('¿Seguro que quieres eliminar este producto?');">
                                            Eliminar
                                        </a>
                                    <?php else: ?>
                                        —
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <?php if ($rol === "admin"): ?>
        <div class="card">
            <h3 style="margin-bottom:0.8rem;">Agregar producto</h3>
            <form method="post">
                <div class="form-group">
                    <label for="nombre">Nombre del producto</label>
                    <input type="text" id="nombre" name="nombre" required>
                </div>
                <div class="form-group">
                    <label for="categoria">Categoría</label>
                    <select id="categoria" name="categoria">
                        <option value="cerveza">Cerveza</option>
                        <option value="sabina">Sabina</option>
                        <option value="otro">Otro</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="precio">Precio</label>
                    <input type="number" step="0.01" id="precio" name="precio" required>
                </div>
                <div class="form-group">
                    <label for="stock">Stock inicial</label>
                    <input type="number" id="stock" name="stock" required>
                </div>
                <div class="form-group">
                    <label for="descripcion">Descripción</label>
                    <textarea id="descripcion" name="descripcion" rows="3"></textarea>
                </div>
                <div class="form-group">
                    <label for="imagen">Ruta/URL de imagen (opcional)</label>
                    <input type="text" id="imagen" name="imagen">
                </div>
                <button type="submit" class="btn">Guardar producto</button>
            </form>
        </div>
        <?php else: ?>
            <div class="card">
                <p style="font-size:0.9rem;">
                    Tu rol es <strong>empleado</strong>. Solo puedes consultar productos.
                </p>
            </div>
        <?php endif; ?>
    </main>
</div>

<script src="js/reloj.js"></script>
</body>
</html>
