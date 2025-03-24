<?php
session_start();
include 'db.php';

if (!isset($_SESSION['id_usuario']) || $_SESSION['tipo'] != 'Doctor') {
    header("Location: login.php");
    exit();
}

$id_doctor = $_SESSION['id_usuario'];
$dia = $_POST['dia'];
$hora_inicio = $_POST['hora_inicio'];
$hora_fin = $_POST['hora_fin'];

$sql = "INSERT INTO HorarioDoctor (id_doctor, dia, hora_inicio, hora_fin) 
        VALUES ('$id_doctor', '$dia', '$hora_inicio', '$hora_fin')";

if ($conexion->query($sql) === TRUE) {
    echo json_encode(["status" => "success", "message" => "Horario guardado exitosamente."]);
} else {
    echo json_encode(["status" => "error", "message" => "Error al guardar el horario."]);
}

$conexion->close();
?>