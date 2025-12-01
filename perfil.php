<?php
session_start();
if (!isset($_SESSION["id_usuario"])) {
    header("Location: login.php");
    exit;
}
require_once "config/conexion.php";

$id_usuario = $_SESSION["id_usuario"];
$nombre     = $_SESSION["nombre"];
$email      = $_SESSION["email"];
$rol        = $_SESSION["rol"];

// Foto actual
$stmt = $conexion->prepare("SELECT foto_perfil FROM usuarios WHERE id = ?");
$stmt->bind_param("i", $id_usuario);
$stmt->execute();
$stmt->bind_result($foto_perfil);
$stmt->fetch();
$stmt->close();

// Subir foto
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_FILES["foto"])) {
    if ($_FILES["foto"]["error"] === UPLOAD_ERR_OK) {
        $carpeta = "uploads/fotos/";
        if (!is_dir($carpeta)) {
            mkdir($carpeta, 0777, true);
        }
        $extension = pathinfo($_FILES["foto"]["name"], PATHINFO_EXTENSION);
        if ($extension === "") $extension = "jpg";
        $nombre_archivo = "user_" . $id_usuario . "_" . time() . "." . $extension;
        $ruta_destino   = $carpeta . $nombre_archivo;

        if (move_uploaded_file($_FILES["foto"]["tmp_name"], $ruta_destino)) {
            $stmt = $conexion->prepare("UPDATE usuarios SET foto_perfil = ? WHERE id = ?");
            $stmt->bind_param("si", $ruta_destino, $id_usuario);
            $stmt->execute();
            $stmt->close();

            $_SESSION["foto_perfil"] = $ruta_destino;
            $foto_perfil = $ruta_destino;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mi Oficina | Perfil</title>
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
            <div>Usuario: <strong><?php echo htmlspecialchars($nombre); ?></strong></div>
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
    <li><a href="perfil.php" class="activo">Perfil</a></li>
    <li><a href="reabastecer.php">Reabastecer</a></li>
    <li><a href="logout.php">Cerrar sesión</a></li>
</ul>
    </aside>

    <main class="main-content">
        <h2>Perfil de usuario</h2>

        <div class="card">
            <div class="perfil-header">
                <div class="perfil-foto"
                     style="<?php echo $foto_perfil ? 'background-image:url('.htmlspecialchars($foto_perfil).');' : ''; ?>">
                </div>
                <div>
                    <h3><?php echo htmlspecialchars($nombre); ?></h3>
                    <p><?php echo htmlspecialchars($email); ?></p>
                    <p>Rol: <?php echo htmlspecialchars(ucfirst($rol)); ?></p>
                </div>
            </div>

            <form method="post" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="foto">Cambiar foto de perfil</label>
                    <input type="file" id="foto" name="foto" accept="image/*">
                </div>
                <button type="submit" class="btn">Guardar cambios</button>
            </form>
        </div>
    </main>
</div>

<script src="js/reloj.js"></script>
</body>
</html>
