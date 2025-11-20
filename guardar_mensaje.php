<?php
// ================== CONFIGURACIÓN BD ==================
$host       = "srv738.hstgr.io"; 
$usuario    = "u664070856_aplicacion"; 
$contrasena = "b7@HAuGQqUeLQ7q"; 
$base_datos = "u664070856_aplicacion";

// ================== CONEXIÓN ==================
$conexion = new mysqli($host, $usuario, $contrasena, $base_datos);

if ($conexion->connect_error) {
    $msg = "Error de conexión: " . $conexion->connect_error;
    echo "<script>alert('❌ Error al conectar con la base de datos: " . addslashes($msg) . "'); window.history.back();</script>";
    exit;
}

// Muy importante para acentos
$conexion->set_charset("utf8mb4");

// ================== PROCESAR FORMULARIO ==================
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // Tomar datos del formulario
    $Nombre   = trim($_POST['Nombre']   ?? '');
    $Email    = trim($_POST['Email']    ?? '');
    $Telefono = trim($_POST['Telefono'] ?? '');
    $Asunto   = trim($_POST['subject']  ?? '');
    $Mensaje  = trim($_POST['Mensaje']  ?? '');

    // Validar obligatorios
    if ($Nombre === '' || $Email === '' || $Mensaje === '') {
        echo "<script>alert('⚠ Por favor, completa Nombre, Email y Mensaje.'); window.history.back();</script>";
        $conexion->close();
        exit;
    }

    // INSERT con sentencia preparada
    $sql = "INSERT INTO `contacto` (Nombre, Email, Telefono, Asunto, Mensaje)
            VALUES (?, ?, ?, ?, ?)";

    $stmt = $conexion->prepare($sql);

    if (!$stmt) {
        echo "<script>alert('❌ Error en prepare(): " . addslashes($conexion->error) . "'); window.history.back();</script>";
        $conexion->close();
        exit;
    }

    $stmt->bind_param("sssss", $Nombre, $Email, $Telefono, $Asunto, $Mensaje);

    if ($stmt->execute()) {
        // Éxito: guardado en BD
        echo "<script>alert('✅ Mensaje enviado correctamente'); window.location.href='index.html';</script>";
    } else {
        // Error al ejecutar el INSERT
        echo "<script>alert('❌ Error al guardar: " . addslashes($stmt->error) . "'); window.history.back();</script>";
    }

    $stmt->close();
} else {
    // Llegaron aquí sin usar POST (por ejemplo abriendo el PHP directo)
    echo "<script>alert('⚠ El formulario no se envió correctamente.'); window.history.back();</script>";
}

$conexion->close();
?>
