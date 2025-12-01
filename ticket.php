<?php
require_once "config/conexion.php";

if (!isset($_GET["pedido_id"])) {
    die("Falta ID de pedido.");
}

$pedido_id = (int)$_GET["pedido_id"];

// Datos del pedido
$stmt = $conexion->prepare("
    SELECT p.id, p.mesa_id, p.total, p.fecha_apertura, p.fecha_cierre, p.nota_mesas,
           m.numero AS mesa_num
    FROM pedidos p
    INNER JOIN mesas m ON p.mesa_id = m.id
    WHERE p.id = ?
");
$stmt->bind_param("i", $pedido_id);
$stmt->execute();
$pedido = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$pedido) {
    die("Pedido no encontrado.");
}

// Detalles
$detalles = [];
$stmt = $conexion->prepare("
    SELECT pr.nombre, d.cantidad, d.precio_unitario, d.subtotal
    FROM detalle_pedido d
    INNER JOIN productos pr ON d.producto_id = pr.id
    WHERE d.pedido_id = ?
");
$stmt->bind_param("i", $pedido_id);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $detalles[] = $row;
}
$stmt->close();

date_default_timezone_set("America/Mexico_City");
$fecha_ticket = date("d/m/Y H:i:s");
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Ticket #<?php echo $pedido_id; ?></title>
<style>
    :root {
        --fondo: #111827;
        --card:  #1f2933;
        --ambar: #f59e0b;
        --ambar-claro: #fbbf24;
        --verde: #15803d;
        --texto: #e5e7eb;
    }
    body {
        margin: 0;
        background: var(--fondo);
        font-family: "Segoe UI", system-ui, -apple-system, BlinkMacSystemFont, sans-serif;
        color: var(--texto);
    }
    .ticket-wrapper {
        display: flex;
        justify-content: center;
        align-items: flex-start;
        padding: 1rem;
    }
    .ticket {
        width: 260px;
        background: var(--card);
        border-radius: 12px;
        padding: 0.8rem 0.9rem 1rem;
        box-shadow: 0 12px 25px rgba(0,0,0,0.5);
    }
    .ticket-header {
        text-align: center;
        margin-bottom: 0.6rem;
    }
    .ticket-header-logo {
        width: 80px;
        height: 80px;
        border-radius: 999px;
        margin: 0 auto 0.4rem;
        background: radial-gradient(circle at 30% 20%, #ffffff, #fef3c7 40%, #78350f 90%);
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
    }
    .ticket-header-logo img {
        width: 70px;
        height: auto;
        object-fit: contain;
    }
    .ticket-header-title {
        font-weight: 700;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        font-size: 0.85rem;
        color: var(--ambar-claro);
    }
    .ticket-header-sub {
        font-size: 0.72rem;
        text-transform: uppercase;
        letter-spacing: 0.16em;
        color: #9ca3af;
    }
    .ticket-meta {
        text-align: center;
        font-size: 0.7rem;
        margin-bottom: 0.4rem;
        color: #d1d5db;
    }
    hr {
        border: none;
        border-top: 1px dashed #4b5563;
        margin: 0.4rem 0;
    }
    .ticket-info {
        font-size: 0.75rem;
        margin-bottom: 0.3rem;
    }
    .ticket-info div { margin-bottom: 0.1rem; }
    .tag {
        display: inline-block;
        padding: 0.05rem 0.35rem;
        border-radius: 999px;
        font-size: 0.65rem;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        background: rgba(21,128,61,0.15);
        color: #bbf7d0;
    }
    table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.72rem;
        margin-top: 0.2rem;
    }
    thead th {
        text-align: left;
        font-weight: 600;
        color: #9ca3af;
        padding-bottom: 0.15rem;
        border-bottom: 1px solid #374151;
    }
    tbody td { padding: 0.1rem 0; }
    tbody tr:nth-child(even) { background: rgba(15,23,42,0.5); }
    .align-right { text-align: right; }
    .ticket-total {
        margin-top: 0.4rem;
        padding-top: 0.35rem;
        border-top: 1px solid #4b5563;
        display: flex;
        justify-content: space-between;
        font-size: 0.85rem;
        font-weight: 700;
        color: var(--ambar-claro);
    }
    .ticket-footer {
        margin-top: 0.5rem;
        text-align: center;
        font-size: 0.7rem;
        color: #9ca3af;
    }
    .ticket-footer span { display:block; }
    @media print {
        body { background:#ffffff; }
        .ticket-wrapper { padding:0; }
        .ticket { box-shadow:none; border-radius:0; width:230px; }
    }
</style>
<script>
function imprimirYVolver() {
    window.print();
    setTimeout(function () {
        window.location.href = 'mesas.php';
    }, 800);
}
</script>
</head>
<body onload="imprimirYVolver()">

<div class="ticket-wrapper">
    <div class="ticket">
        <div class="ticket-header">
            <div class="ticket-header-logo">
                <img src="img/logo.jpeg" alt="Mi Oficina">
            </div>
            <div class="ticket-header-title">Mi Oficina</div>
            <div class="ticket-header-sub">Cervecería</div>
        </div>

        <div class="ticket-meta">
            <div><?php echo $fecha_ticket; ?></div>
            <div>Ticket #<?php echo $pedido_id; ?></div>
        </div>

        <hr>

        <div class="ticket-info">
            <div><strong>Mesa:</strong> <?php echo htmlspecialchars($pedido["mesa_num"]); ?></div>
            <?php if (!empty($pedido["nota_mesas"])): ?>
                <div><strong>Mesas unidas:</strong> <?php echo htmlspecialchars($pedido["nota_mesas"]); ?></div>
            <?php endif; ?>
            <div><strong>Estado:</strong> <span class="tag">Pagado</span></div>
        </div>

        <hr>

        <?php if (count($detalles) === 0): ?>
            <div style="font-size:0.75rem;">No hay productos registrados en este pedido.</div>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Prod.</th>
                        <th>Cant</th>
                        <th class="align-right">Subt.</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($detalles as $d): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($d["nombre"]); ?></td>
                        <td><?php echo (int)$d["cantidad"]; ?></td>
                        <td class="align-right">$<?php echo number_format($d["subtotal"], 2); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <div class="ticket-total">
            <span>Total</span>
            <span>$<?php echo number_format($pedido["total"], 2); ?></span>
        </div>

        <div class="ticket-footer">
            <span>¡Gracias por su visita!</span>
            <span>Mi Oficina · Cervecería</span>
        </div>
    </div>
</div>

</body>
</html>
