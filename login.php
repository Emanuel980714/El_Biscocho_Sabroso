<?php
// api/login.php - Simple demo login (puedes reemplazarlo por una tabla de usuarios)
require_once __DIR__ . '/config.php';

$body = json_decode(file_get_contents('php://input'), true) ?? [];
$user = $body['user'] ?? '';
$pass = $body['pass'] ?? '';

if ($user === 'admin' && $pass === '1234') {
  json_out(['ok' => true, 'token' => 'demo']);
} else {
  json_out(['ok' => false], 401);
}
