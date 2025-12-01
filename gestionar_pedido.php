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

$mesa_id = isset($_GET["mesa_id"]) ? intval($_GET["mesa_id"]) : 0;
$mensaje = "";

// ======================================
// VALIDAR MESA
// ======================================
if ($mesa_id <= 0) {
    die("Mesa no válida.");
}

$stmt = $conexion->prepare("SELECT id, numero, capacidad, estado FROM mesas WHERE id = ?");
$stmt->bind_param("i", $mesa_id);
$stmt->execute();
$result_mesa = $stmt->get_result();
$mesa = $result_mesa->fetch_assoc();
$stmt->close();

if (!$mesa) {
    die("La mesa no existe.");
}

$numero_mesa_principal = (int)$mesa["numero"];

// ======================================
// BUSCAR / CREAR PEDIDO ABIERTO
// ======================================
$stmt = $conexion->prepare("SELECT * FROM pedidos WHERE mesa_id = ? AND estado = 'abierto' LIMIT 1");
$stmt->bind_param("i", $mesa_id);
$stmt->execute();
$res_pedido = $stmt->get_result();
$pedido = $res_pedido->fetch_assoc();
$stmt->close();

if (!$pedido) {
    // Crear pedido nuevo
    $stmt = $conexion->prepare("
        INSERT INTO pedidos (mesa_id, usuario_id, estado, total)
        VALUES (?, ?, 'abierto', 0)
    ");
    $stmt->bind_param("ii", $mesa_id, $id_usuario);
    $stmt->execute();
    $pedido_id = $stmt->insert_id;
    $stmt->close();

    // Marcar mesa principal ocupada
    $estado_mesa = "ocupada";
    $stmt = $conexion->prepare("UPDATE mesas SET estado = ? WHERE id = ?");
    $stmt->bind_param("si", $estado_mesa, $mesa_id);
    $stmt->execute();
    $stmt->close();

    $pedido = [
        "id"         => $pedido_id,
        "total"      => 0,
        "estado"     => "abierto",
        "nota_mesas" => ""
    ];
} else {
    $pedido_id = $pedido["id"];
}

// ======================================
// FUNCIONES AUXILIARES MESAS UNIDAS
// ======================================
function obtener_numeros_mesas_desde_nota(string $nota, int $mesa_principal): array {
    $nums = [];
    if (preg_match_all('/\d+/', $nota, $coincidencias)) {
        foreach ($coincidencias[0] as $n) {
            $n = (int)$n;
            if ($n > 0 && $n !== $mesa_principal) {
                $nums[] = $n;
            }
        }
    }
    return array_values(array_unique($nums));
}

function actualizar_estado_mesas_unidas(mysqli $conexion, array $numeros, string $estado): void {
    if (empty($numeros)) return;
    $stmt = $conexion->prepare("UPDATE mesas SET estado = ? WHERE numero = ?");
    foreach ($numeros as $num) {
        $stmt->bind_param("si", $estado, $num);
        $stmt->execute();
    }
    $stmt->close();
}

// ======================================
// ACCIONES POST
// ======================================
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $accion = $_POST["accion"] ?? "";

    // ----- GUARDAR MESAS UNIDAS -----
    if ($accion === "guardar_nota_mesas") {
        $nota = trim($_POST["nota_mesas"] ?? "");

        // Guardar nota
        $stmt = $conexion->prepare("UPDATE pedidos SET nota_mesas = ? WHERE id = ?");
        $stmt->bind_param("si", $nota, $pedido_id);
        $stmt->execute();
        $stmt->close();

        $pedido["nota_mesas"] = $nota;

        // Marcar mesas unidas como ocupadas
        $mesas_unidas_numeros = obtener_numeros_mesas_desde_nota($nota, $numero_mesa_principal);
        actualizar_estado_mesas_unidas($conexion, $mesas_unidas_numeros, "ocupada");

        $mensaje = "Mesas unidas actualizadas.";
    }

    // ----- AGREGAR PRODUCTO -----
    if ($accion === "agregar") {

        $producto_id = (int)($_POST["producto_id"] ?? 0);
        $cantidad    = (int)($_POST["cantidad"] ?? 0);

        if ($producto_id > 0 && $cantidad > 0) {

            // Precio y stock
            $stmt = $conexion->prepare("SELECT precio, stock FROM productos WHERE id = ? AND activo = 1");
            $stmt->bind_param("i", $producto_id);
            $stmt->execute();
            $stmt->bind_result($precio_unitario, $stock_actual);

            if ($stmt->fetch()) {
                $stmt->close();

                if ($stock_actual < $cantidad) {
                    $mensaje = "No hay suficiente stock. Disponible: $stock_actual";
                } else {
                    $subtotal = $precio_unitario * $cantidad;

                    // Insertar detalle
                    $stmt = $conexion->prepare("
                        INSERT INTO detalle_pedido (pedido_id, producto_id, cantidad, precio_unitario, subtotal)
                        VALUES (?, ?, ?, ?, ?)
                    ");
                    $stmt->bind_param("iiidd", $pedido_id, $producto_id, $cantidad, $precio_unitario, $subtotal);
                    $stmt->execute();
                    $stmt->close();

                    // Descontar stock
                    $stmt = $conexion->prepare("UPDATE productos SET stock = stock - ? WHERE id = ?");
                    $stmt->bind_param("ii", $cantidad, $producto_id);
                    $stmt->execute();
                    $stmt->close();

                    // Recalcular total
                    $stmt = $conexion->prepare("SELECT SUM(subtotal) FROM detalle_pedido WHERE pedido_id = ?");
                    $stmt->bind_param("i", $pedido_id);
                    $stmt->execute();
                    $stmt->bind_result($nuevo_total);
                    $stmt->fetch();
                    $stmt->close();

                    $nuevo_total = $nuevo_total ?? 0;

                    $stmt = $conexion->prepare("UPDATE pedidos SET total = ? WHERE id = ?");
                    $stmt->bind_param("di", $nuevo_total, $pedido_id);
                    $stmt->execute();
                    $stmt->close();

                    $pedido["total"] = $nuevo_total;
                    $mensaje = "Producto agregado.";
                }
            } else {
                $stmt->close();
                $mensaje = "Producto no válido o inactivo.";
            }
        } else {
            $mensaje = "Selecciona un producto y una cantidad válida.";
        }
    }

    // ----- PAGAR -----
    if ($accion === "pagar") {

        $stmt = $conexion->prepare("SELECT total, nota_mesas FROM pedidos WHERE id = ?");
        $stmt->bind_param("i", $pedido_id);
        $stmt->execute();
        $stmt->bind_result($total_pedido, $nota_mesas_actual);
        $stmt->fetch();
        $stmt->close();

        if ($total_pedido <= 0) {
            $mensaje = "No puedes pagar un pedido vacío.";
        } else {
            // Venta
            $stmt = $conexion->prepare("INSERT INTO ventas (pedido_id, total) VALUES (?, ?)");
            $stmt->bind_param("id", $pedido_id, $total_pedido);
            $stmt->execute();
            $stmt->close();

            // Cerrar pedido
            $estado_pedido = "pagado";
            $stmt = $conexion->prepare("
                UPDATE pedidos
                SET estado = ?, fecha_cierre = NOW()
                WHERE id = ?
            ");
            $stmt->bind_param("si", $estado_pedido, $pedido_id);
            $stmt->execute();
            $stmt->close();

            // Liberar mesa principal
            $estado_mesa = "libre";
            $stmt = $conexion->prepare("UPDATE mesas SET estado = ? WHERE id = ?");
            $stmt->bind_param("si", $estado_mesa, $mesa_id);
            $stmt->execute();
            $stmt->close();

            // Liberar mesas unidas
            $mesas_unidas_numeros = obtener_numeros_mesas_desde_nota($nota_mesas_actual ?? "", $numero_mesa_principal);
            actualizar_estado_mesas_unidas($conexion, $mesas_unidas_numeros, "libre");

            // Redirigir al ticket (ticket.php se encarga de imprimir y regresar)
            header("Location: ticket.php?pedido_id=" . $pedido_id);
            exit;
        }
    }
}

// ======================================
// DETALLES DEL PEDIDO
// ======================================
$detalles = [];
$stmt = $conexion->prepare("
    SELECT d.id, pr.nombre, d.cantidad, d.precio_unitario, d.subtotal
    FROM detalle_pedido d
    INNER JOIN productos pr ON d.producto_id = pr.id
    WHERE d.pedido_id = ?
");
$stmt->bind_param("i", $pedido_id);
$stmt->execute();
$res_det = $stmt->get_result();
while ($row = $res_det->fetch_assoc()) {
    $detalles[] = $row;
}
$stmt->close();

// ======================================
// PRODUCTOS ACTIVOS
// ======================================
$lista_productos = [];
$res_prod = $conexion->query("SELECT id, nombre, precio FROM productos WHERE activo = 1 ORDER BY nombre");
if ($res_prod) {
    while ($row = $res_prod->fetch_assoc()) {
        $lista_productos[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mi Oficina | Pedido mesa <?php echo htmlspecialchars($mesa["numero"]); ?></title>
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
            <li><a href="mesas.php" class="activo">Mesas</a></li>
            <li><a href="pedidos.php">Pedidos</a></li>
            <li><a href="ventas.php">Ventas</a></li>
            <li><a href="perfil.php">Perfil</a></li>
            <li><a href="reabastecer.php">Reabastecer</a></li>
            <li><a href="logout.php">Cerrar sesión</a></li>
        </ul>
    </aside>

    <main class="main-content">
        <h2>Pedido de mesa <?php echo htmlspecialchars($mesa["numero"]); ?></h2>

        <?php if ($mensaje): ?>
            <div class="alert"><?php echo htmlspecialchars($mensaje); ?></div>
        <?php endif; ?>

        <div class="card">
            <div class="pedido-info" style="display:flex;flex-wrap:wrap;gap:0.8rem;font-size:0.9rem;">
                <div><strong>ID Pedido:</strong> <?php echo $pedido_id; ?></div>
                <div><strong>Mesa:</strong> <?php echo htmlspecialchars($mesa["numero"]); ?></div>
                <div><strong>Capacidad:</strong> <?php echo htmlspecialchars($mesa["capacidad"]); ?> personas</div>
                <div><strong>Estado pedido:</strong> <?php echo htmlspecialchars($pedido["estado"]); ?></div>
                <div><strong>Total:</strong> $<?php echo number_format($pedido["total"], 2); ?></div>
                <?php if (!empty($pedido["nota_mesas"])): ?>
                    <div><strong>Mesas unidas:</strong> <?php echo htmlspecialchars($pedido["nota_mesas"]); ?></div>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($pedido["estado"] === "abierto"): ?>
        <div class="card">
            <h3>Mesas unidas</h3>
            <form method="post">
                <input type="hidden" name="accion" value="guardar_nota_mesas">
                <div class="form-group">
                    <label>Mesas unidas (ej. 3 y 4)</label>
                    <input type="text" name="nota_mesas"
                           value="<?php echo htmlspecialchars($pedido["nota_mesas"] ?? ""); ?>">
                </div>
                <button class="btn">Guardar</button>
            </form>
        </div>
        <?php endif; ?>

        <div class="card">
            <h3>Detalle del pedido</h3>
            <?php if (count($detalles) === 0): ?>
                <p>No hay productos agregados.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Producto</th>
                            <th>Cant.</th>
                            <th>Precio</th>
                            <th>Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($detalles as $d): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($d["nombre"]); ?></td>
                            <td><?php echo (int)$d["cantidad"]; ?></td>
                            <td>$<?php echo number_format($d["precio_unitario"], 2); ?></td>
                            <td>$<?php echo number_format($d["subtotal"], 2); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <?php if ($pedido["estado"] === "abierto"): ?>
        <div class="card">
            <h3>Agregar producto</h3>
            <form method="post">
                <input type="hidden" name="accion" value="agregar">

                <div class="form-group">
                    <label>Producto</label>
                    <select name="producto_id" required>
                        <option value="">Selecciona...</option>
                        <?php foreach ($lista_productos as $p): ?>
                            <option value="<?php echo $p["id"]; ?>">
                                <?php echo htmlspecialchars($p["nombre"]); ?> ($<?php echo number_format($p["precio"], 2); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Cantidad</label>
                    <input type="number" name="cantidad" min="1" value="1" required>
                </div>

                <button class="btn">Agregar</button>
            </form>
        </div>

        <div class="card">
            <h3>Cobrar pedido</h3>
            <form method="post" onsubmit="return confirm('¿Confirmar pago y generar ticket?');">
                <input type="hidden" name="accion" value="pagar">
                <button class="btn">Pagar y liberar mesa</button>
            </form>
        </div>
        <?php else: ?>
        <div class="card">
            <p>Este pedido ya está en estado <strong><?php echo htmlspecialchars($pedido["estado"]); ?></strong>.</p>
            <a class="btn" href="ticket.php?pedido_id=<?php echo $pedido_id; ?>" target="_blank">
                Ver / imprimir ticket
            </a>
        </div>
        <?php endif; ?>
    </main>
</div>

<script src="js/reloj.js"></script>
</body>
</html>
