<?php
require 'db.php';
header('Content-Type: application/json');

$response = ["status" => "error", "message" => ""];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Verificar conexión
    if (!$conexion) {
        $response["message"] = "Error de conexión a la base de datos";
        echo json_encode($response);
        exit();
    }

    // Verificar campos obligatorios
    if (!isset($_POST["nombre"], $_POST["email"], $_POST["contrasena"], $_POST["role"])) {
        $response["message"] = "Faltan campos obligatorios";
        echo json_encode($response);
        exit();
    }

    // Recibir datos
    $nombre = trim($_POST["nombre"]);
    $email = trim($_POST["email"]);
    $contrasena = trim($_POST["contrasena"]);
    $tipo = trim($_POST["role"]);

    // Validar la contraseña
    $errors = [];
    if (strlen($contrasena) < 4) {
        $errors[] = "La contraseña debe tener al menos 4 caracteres.";
    }
    if (!preg_match("/[a-z]/", $contrasena) || !preg_match("/[A-Z]/", $contrasena)) {
        $errors[] = "La contraseña debe contener mayúsculas y minúsculas.";
    }
    if (!preg_match("/[0-9]/", $contrasena)) {
        $errors[] = "La contraseña debe contener al menos un número.";
    }
    if (!preg_match("/[\W_]/", $contrasena)) {
        $errors[] = "La contraseña debe contener al menos un carácter especial.";
    }
    if (strpos($contrasena, ' ') !== false) {
        $errors[] = "La contraseña no puede contener espacios.";
    }

    // Si hay errores, devolverlos
    if (!empty($errors)) {
        $response["message"] = implode(" ", $errors);
        echo json_encode($response);
        exit();
    }

    // Hash de la contraseña
    $contrasenaHash = password_hash($contrasena, PASSWORD_BCRYPT);

    // Insertar usuario en la tabla Usuario
    $sql = "INSERT INTO Usuario (nombre, email, contrasena, tipo) VALUES (?, ?, ?, ?)";
    $stmt = $conexion->prepare($sql);

    if (!$stmt) {
        $response["message"] = "Error en la preparación de la consulta: " . $conexion->error;
        echo json_encode($response);
        exit();
    }

    $stmt->bind_param("ssss", $nombre, $email, $contrasenaHash, $tipo);

    if ($stmt->execute()) {
        $id_usuario = $stmt->insert_id;

        // Si es Paciente, insertar en la tabla Paciente
        if ($tipo == "Paciente" && isset($_POST["fecha_nacimiento"], $_POST["telefono"], $_POST["direccion"])) {
            $fecha_nacimiento = $_POST["fecha_nacimiento"];
            $telefono = $_POST["telefono"];
            $direccion = $_POST["direccion"];

            $sql_paciente = "INSERT INTO Paciente (id_paciente, fecha_nacimiento, telefono, direccion) VALUES (?, ?, ?, ?)";
            $stmt_paciente = $conexion->prepare($sql_paciente);

            if ($stmt_paciente) {
                $stmt_paciente->bind_param("isss", $id_usuario, $fecha_nacimiento, $telefono, $direccion);
                $stmt_paciente->execute();
                $stmt_paciente->close();
            }
        } 
        // Si es Doctor, insertar en la tabla Doctor
        else if ($tipo == "Doctor" && isset($_POST["especialidad"], $_POST["telefono"], $_POST["direccion"])) {
            $especialidad = $_POST["especialidad"];
            $telefono = $_POST["telefono"];
            $direccion = $_POST["direccion"];

            $sql_doctor = "INSERT INTO Doctor (id_doctor, especialidad, telefono, direccion) VALUES (?, ?, ?, ?)";
            $stmt_doctor = $conexion->prepare($sql_doctor);

            if ($stmt_doctor) {
                $stmt_doctor->bind_param("isss", $id_usuario, $especialidad, $telefono, $direccion);
                $stmt_doctor->execute();
                $stmt_doctor->close();
            }
        }

        // Registro exitoso
        $response["status"] = "success";
        $response["message"] = "Registro exitoso";
    } else {
        // Error en la ejecución de la consulta
        $response["message"] = "Error al registrar: " . $stmt->error;
    }

    $stmt->close();
    $conexion->close();
} else {
    $response["message"] = "Método de solicitud no válido";
}

echo json_encode($response);
?>