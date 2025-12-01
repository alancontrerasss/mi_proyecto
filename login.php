<?php
session_start();
require_once "config/conexion.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email    = trim($_POST["email"] ?? "");
    $password = trim($_POST["password"] ?? "");

    if ($email === "" || $password === "") {
        $error = "Ingresa correo y contraseña.";
    } else {
        $stmt = $conexion->prepare(
            "SELECT id, nombre, email, password, rol, foto_perfil 
             FROM usuarios 
             WHERE email = ? LIMIT 1"
        );
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $resultado = $stmt->get_result();

        if ($fila = $resultado->fetch_assoc()) {
            // Aquí podrías usar password_verify si luego guardas hash
            if ($password === $fila["password"]) {
                $_SESSION["id_usuario"] = $fila["id"];
                $_SESSION["nombre"]     = $fila["nombre"];
                $_SESSION["email"]      = $fila["email"];
                $_SESSION["rol"]        = $fila["rol"];
                $_SESSION["foto_perfil"] = $fila["foto_perfil"];

                header("Location: menu.php");
                exit;
            } else {
                $error = "Contraseña incorrecta.";
            }
        } else {
            $error = "No existe una cuenta con ese correo.";
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mi Oficina | Iniciar sesión</title>
    <link rel="stylesheet" href="css/estilos.css">
</head>
<body>
<div class="login-wrapper">
    <div class="login-card">
        <img src="img/logo.jpeg" alt="Logo Mi Oficina">
        <h1>Mi Oficina</h1>
        <p>Cervecería · Panel de control</p>

        <?php if ($error): ?>
            <div class="alert"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="post" autocomplete="off">
            <div class="form-group">
                <label for="email">Correo</label>
                <input type="email" id="email" name="email" required>
            </div>

            <div class="form-group">
                <label for="password">Contraseña</label>
                <input type="password" id="password" name="password" required>
            </div>

            <button type="submit" class="btn">Entrar</button>
        </form>

        <p style="margin-top:1rem; font-size:0.8rem; color:#e5e7eb;">
            BD: <strong><?php echo htmlspecialchars($bd); ?></strong><br>
            <span id="reloj-fecha"></span><br>
            <span id="reloj-hora"></span>
        </p>
    </div>
</div>

<script src="js/reloj.js"></script>
</body>
</html>
