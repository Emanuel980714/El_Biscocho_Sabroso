<?php
// api/productos.php
require_once __DIR__ . '/config.php';

$pdo = db();
$method = $_SERVER['REQUEST_METHOD'];
$id = $_GET['id'] ?? null;

try {
  if ($method === 'GET') {
    if ($id) {
      $stmt = $pdo->prepare("SELECT * FROM productos WHERE id = ?");
      $stmt->execute([$id]);
      $row = $stmt->fetch();
      json_out($row ?: []);
    } else {
      $rows = $pdo->query("SELECT * FROM productos ORDER BY nombre")->fetchAll();
      json_out($rows);
    }
  }

  $body = json_decode(file_get_contents('php://input'), true) ?? [];

  if ($method === 'POST') {
    // Crear producto
    $stmt = $pdo->prepare("INSERT INTO productos (id, nombre, categoria, precio, stock, imagen) VALUES (?,?,?,?,?,?)");
    $stmt->execute([
      $body['id'], $body['nombre'], $body['categoria'],
      $body['precio'], $body['stock'], $body['imagen'] ?? null
    ]);
    json_out(['ok' => true, 'id' => $body['id']], 201);
  }

  if ($method === 'PUT' || $method === 'PATCH') {
    if (!$id) json_out(['error' => 'Falta ?id'], 400);
    $fields = [];
    $values = [];
    foreach (['nombre','categoria','precio','stock','imagen'] as $f) {
      if (array_key_exists($f, $body)) {
        $fields[] = "$f = ?"; $values[] = $body[$f];
      }
    }
    if (!$fields) json_out(['error' => 'Nada que actualizar'], 400);
    $values[] = $id;
    $stmt = $pdo->prepare("UPDATE productos SET " . implode(',', $fields) . " WHERE id = ?");
    $stmt->execute($values);
    json_out(['ok' => true]);
  }

  if ($method === 'DELETE') {
    if (!$id) json_out(['error' => 'Falta ?id'], 400);
    $stmt = $pdo->prepare("DELETE FROM productos WHERE id = ?");
    $stmt->execute([$id]);
    json_out(['ok' => true]);
  }

  json_out(['error' => 'Método no soportado'], 405);
} catch (Throwable $e) {
  json_out(['error' => $e->getMessage()], 500);
}
