<?php
// api/config.php
// 1) Rellena estas constantes con tus credenciales reales de MariaDB/MySQL.
// 2) Sube toda la carpeta 'biscocho_api' a tu hosting (public_html/biscocho_api).
// 3) Importa init.sql desde HeidiSQL antes del primer uso.

const DB_HOST = 'localhost';          // o el host remoto de tu proveedor (ej. srv738.hstgr.io)
const DB_NAME = 'uXXXXX_aplicacion';  // cambia por tu base de datos
const DB_USER = 'uXXXXX_usuario';     // cambia por tu usuario
const DB_PASS = 'TU_PASSWORD';        // cambia por tu contraseña
const DB_CHARSET = 'utf8mb4';

function db() {
  static $pdo = null;
  if ($pdo === null) {
    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
    $opt = [
      PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
      PDO::ATTR_EMULATE_PREPARES => false,
    ];
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $opt);
  }
  return $pdo;
}

function json_out($data, $code = 200) {
  http_response_code($code);
  header('Content-Type: application/json; charset=utf-8');
  header('Access-Control-Allow-Origin: *');
  header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
  header('Access-Control-Allow-Headers: Content-Type');
  echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
  json_out(['ok' => true]);
}
