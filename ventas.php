<?php
require_once __DIR__ . '/config.php';
$pdo = db();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_out(['error' => 'Solo POST'], 405);

$data = json_decode(file_get_contents('php://input'), true);
$items = $data['items'] ?? [];

try {
  $pdo->beginTransaction();
  foreach ($items as $it) {
    $id = $it['id'];
    $qty = (int)$it['qty'];
    $pdo->query("UPDATE `10056014` SET stock = stock - $qty WHERE id = '$id'");
  }
  $pdo->commit();
  json_out(['ok' => true]);
} catch (Exception $e) {
  $pdo->rollBack();
  json_out(['error' => $e->getMessage()], 500);
}
?>
