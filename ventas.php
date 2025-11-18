<?php
// ventas.php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/config.php';

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

if (!$data || !isset($data['folio'], $data['items']) || !is_array($data['items'])) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'JSON inválido']);
    exit;
}

$folio = $data['folio'];
$items = $data['items'];

try {
    $pdo->beginTransaction();

    // calcular total
    $total = 0;
    foreach ($items as $it) {
        $id  = $it['id'];
        $qty = (int)$it['qty'];

        // obtener precio actual desde BD
        $stmt = $pdo->prepare("SELECT precio FROM `10056014` WHERE id = ?");
        $stmt->execute([$id]);
        $prod = $stmt->fetch();
        if (!$prod) throw new Exception("Producto $id no encontrado");

        $precio = (float)$prod['precio'];
        $total += $precio * $qty;
    }

    // insertar venta
    $stmt = $pdo->prepare("INSERT INTO ventas (folio, total) VALUES (?, ?)");
    $stmt->execute([$folio, $total]);
    $ventaId = $pdo->lastInsertId();

    // insertar detalle y actualizar stock
    foreach ($items as $it) {
        $id  = $it['id'];
        $qty = (int)$it['qty'];

        // precio actual
        $stmt = $pdo->prepare("SELECT precio, stock FROM `10056014` WHERE id = ? FOR UPDATE");
        $stmt->execute([$id]);
        $prod = $stmt->fetch();
        if (!$prod) throw new Exception("Producto $id no encontrado");

        $precio = (float)$prod['precio'];
        $stock  = (int)$prod['stock'];

        if ($stock < $qty) {
            throw new Exception("Stock insuficiente para el producto $id");
        }

        $sub = $precio * $qty;

        // detalle
        $stmt = $pdo->prepare("
            INSERT INTO ventas_detalle (venta_id, producto_id, cantidad, precio, subtotal)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$ventaId, $id, $qty, $precio, $sub]);

        // actualizar stock
        $stmt = $pdo->prepare("UPDATE `10056014` SET stock = stock - ? WHERE id = ?");
        $stmt->execute([$qty, $id]);
    }

    $pdo->commit();

    echo json_encode(['ok' => true, 'venta_id' => $ventaId, 'total' => $total]);
} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
