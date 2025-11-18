<?php
// productos.php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/config.php';

try {
    // OJO: la tabla empieza con número, por eso usamos backticks `
    $sql = "SELECT id, nombre, categoria, precio, stock, imagen
            FROM `10056014`
            ORDER BY nombre";
    $stmt = $pdo->query($sql);
    $rows = $stmt->fetchAll();

    // Convertir tipos numéricos correctamente
    $productos = array_map(function ($r) {
        return [
            'id'       => (string) $r['id'],
            'nombre'   => (string) $r['nombre'],
            'categoria'=> (string) $r['categoria'],
            'precio'   => (float)  $r['precio'],
            'stock'    => (int)    $r['stock'],
            'imagen'   => $r['imagen'] !== null ? (string)$r['imagen'] : ''
        ];
    }, $rows);

    echo json_encode($productos);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
