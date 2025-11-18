<?php
// config.php
$DB_HOST = 'srv738.hstgr.io';            // o el host de tu hosting
$DB_NAME = 'u664070856_aplicacion';
$DB_USER = 'u664070856_aplicacion';
$DB_PASS = 'b7@HAuGQqUeLQ7q';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    die('Error de conexión: ' . $e->getMessage());
}
