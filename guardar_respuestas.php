<?php
session_start();
if (!isset($_SESSION['cct'])) {
    header("Location: login.php");
    exit();
}

$conexion = new mysqli("localhost", "root", "", "123");
if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

$formulario = $_POST['formulario'];
$cct = $_SESSION['cct'];

// Borrar respuestas previas para no duplicar
$stmtDelete = $conexion->prepare("DELETE FROM respuestas WHERE cct = ? AND formulario = ?");
$stmtDelete->bind_param("ss", $cct, $formulario);
$stmtDelete->execute();
$stmtDelete->close();

// Preparar la inserción de respuestas
$stmtInsert = $conexion->prepare("
    INSERT INTO respuestas (cct, formulario, pregunta_id, respuesta) 
    VALUES (?, ?, ?, ?)
");

foreach ($_POST as $key => $value) {
    if (strpos($key, 'pregunta_') === 0) {
        $pregunta_id = intval(str_replace('pregunta_', '', $key));
        $respuesta = $value;

        $stmtInsert->bind_param("ssis", $cct, $formulario, $pregunta_id, $respuesta);
        $stmtInsert->execute();
    }
}

$stmtInsert->close();
$conexion->close();

// Redirigir a perfil con mensaje de éxito
header("Location: perfil.php?formulario=ok");
exit();
?>
