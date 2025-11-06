<?php
require_once __DIR__ . '/config.php';
$pdo = db();

$method = $_SERVER['REQUEST_METHOD'];
$id = $_GET['id'] ?? null;

if ($method === 'GET') {
  if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM `10056014` WHERE id=?");
    $stmt->execute([$id]);
    json_out($stmt->fetch() ?: []);
  } else {
    $rows = $pdo->query("SELECT * FROM `10056014` ORDER BY nombre")->fetchAll();
    json_out($rows);
  }
}
?>
