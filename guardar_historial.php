<?php
session_start();
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Obtener los datos del cuerpo de la solicitud
    $data = json_decode(file_get_contents('php://input'), true);

    $id_paciente = $data['id_paciente'];
    $diagnostico = $data['diagnostico'];
    $tratamiento = $data['tratamiento'];

    // Validar que los datos no estén vacíos
    if (empty($id_paciente) || empty($diagnostico) || empty($tratamiento)) {
        echo json_encode(['status' => 'error', 'message' => 'Todos los campos son obligatorios.']);
        exit();
    }

    // Insertar la nota médica en la base de datos
    $sql = "INSERT INTO HistorialMedico (id_paciente, diagnostico, tratamiento) VALUES (?, ?, ?)";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param('iss', $id_paciente, $diagnostico, $tratamiento);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Nota médica guardada correctamente.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Error al guardar la nota médica.']);
    }

    $stmt->close();
} else {
    echo json_encode(['status' => 'error', 'message' => 'Método no permitido.']);
}
?>