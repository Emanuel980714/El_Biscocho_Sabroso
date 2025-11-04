<?php
// api/ventas.php
require_once __DIR__ . '/config.php';

$pdo = db();
$method = $_SERVER['REQUEST_METHOD'];

if ($method !== 'POST') {
  json_out(['error' => 'Sólo POST'], 405);
}

$body = json_decode(file_get_contents('php://input'), true) ?? [];
$items = $body['items'] ?? [];
if (!$items) json_out(['error' => 'items vacío'], 400);

try {
  $pdo->beginTransaction();

  // Calcular total y validar stock
  $total = 0;
  foreach ($items as &$it) {
    $stmt = $pdo->prepare("SELECT precio, stock FROM productos WHERE id = ? FOR UPDATE");
    $stmt->execute([$it['id']]);
    $p = $stmt->fetch();
    if (!$p) throw new Exception("Producto no existe: {$it['id']}");
    if ((int)$p['stock'] < (int)$it['qty']) throw new Exception("Stock insuficiente en {$it['id']}");
    $it['unit_price'] = (float)$p['precio'];
    $it['sub'] = $it['unit_price'] * (int)$it['qty'];
    $total += $it['sub'];
  }

  // Venta
  $folio = $body['folio'] ?? date('Ymd-His');
  $stmt = $pdo->prepare("INSERT INTO ventas (folio, total) VALUES (?, ?)");
  $stmt->execute([$folio, $total]);
  $venta_id = $pdo->lastInsertId();

  // Items + disminución de stock
  $stmtItem = $pdo->prepare("INSERT INTO venta_items (venta_id, producto_id, qty, unit_price, sub) VALUES (?,?,?,?,?)");
  $stmtUpd  = $pdo->prepare("UPDATE productos SET stock = stock - ? WHERE id = ?");
  foreach ($items as $it) {
    $stmtItem->execute([$venta_id, $it['id'], $it['qty'], $it['unit_price'], $it['sub']]);
    $stmtUpd->execute([$it['qty'], $it['id']]);
  }

  $pdo->commit();
  json_out(['ok' => true, 'venta_id' => (int)$venta_id, 'folio' => $folio, 'total' => $total]);
} catch (Throwable $e) {
  $pdo->rollBack();
  json_out(['error' => $e->getMessage()], 500);
}
