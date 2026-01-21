<?php
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['usuario'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$conexion = new mysqli("localhost", "root", "", "123");
if ($conexion->connect_error) {
    die("Conexión fallida: " . $conexion->connect_error);
}

$formulario = $_GET['formulario'] ?? '';
if (!in_array($formulario, ['cfe', 'unete', 'general'])) {
    die("Formulario inválido");
}

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=export_' . $formulario . '.csv');

$output = fopen('php://output', 'w');

// Obtener todas las preguntas del formulario
$stmt = $conexion->prepare("SELECT id, texto FROM preguntas WHERE formulario = ? ORDER BY id");
$stmt->bind_param("s", $formulario);
$stmt->execute();
$preguntas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Cabecera del CSV: escuela, pregunta, respuesta, fecha
fputcsv($output, ['Escuela (CCT)', 'Pregunta', 'Respuesta', 'Fecha']);

// Para cada pregunta, obtener todas las respuestas
foreach ($preguntas as $pregunta) {
    $stmt = $conexion->prepare("SELECT cct, respuesta, fecha FROM respuestas WHERE pregunta_id = ?");
    $stmt->bind_param("i", $pregunta['id']);
    $stmt->execute();
    $respuestas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    foreach ($respuestas as $resp) {
        fputcsv($output, [
            $resp['cct'],
            $pregunta['texto'],
            $resp['respuesta'] ?: 'Sin respuesta',
            $resp['fecha']
        ]);
    }
}

fclose($output);
exit();
