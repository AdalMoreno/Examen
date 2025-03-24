<?php
require 'db.php';
session_start();

header('Content-Type: application/json');

$response = ["status" => "error", "message" => "", "redirect" => ""];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST["email"]);
    $contrasena = trim($_POST["contrasena"]);

    $sql = "SELECT id_usuario, contrasena, tipo, nombre FROM Usuario WHERE email = ?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado->num_rows > 0) {
        $usuario = $resultado->fetch_assoc();
        
        if (password_verify($contrasena, $usuario["contrasena"])) {
            $_SESSION["id_usuario"] = $usuario["id_usuario"];
            $_SESSION["tipo"] = $usuario["tipo"];
            $_SESSION["nombre"] = $usuario["nombre"];

            $response["status"] = "success";
            $response["message"] = "Inicio de sesión exitoso";
            $response["redirect"] = $usuario["tipo"] == "Paciente" ? "paciente_dashboard.php" : "doctor_dashboard.php";
        } else {
            $response["message"] = "Contraseña incorrecta";
        }
    } else {
        $response["message"] = "Usuario no encontrado";
    }
    $stmt->close();
} else {
    $response["message"] = "Método de solicitud no válido";
}

echo json_encode($response);
?>