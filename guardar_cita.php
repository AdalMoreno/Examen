<?php
session_start();
include 'db.php';

if (!isset($_SESSION['id_usuario']) || $_SESSION['tipo'] != 'Paciente') {
    header("Location: login.php");
    exit();
}

$id_paciente = $_SESSION['id_usuario'];
$id_doctor = $_POST['id_doctor'];

// Obtener el horario del doctor
$sql_horario = "SELECT dia, hora_inicio, hora_fin FROM HorarioDoctor WHERE id_doctor = $id_doctor";
$result_horario = $conexion->query($sql_horario);
$horarios = $result_horario->fetch_all(MYSQLI_ASSOC);

if (empty($horarios)) {
    echo "El doctor no tiene horarios configurados.";
    exit();
}

// Obtener las citas agendadas del doctor
$sql_citas = "SELECT fecha_cita FROM Citas WHERE id_doctor = $id_doctor ORDER BY fecha_cita";
$result_citas = $conexion->query($sql_citas);
$citas = $result_citas->fetch_all(MYSQLI_ASSOC);

// Función para generar una fecha disponible
function generarFechaDisponible($horarios, $citas) {
    $fecha_actual = new DateTime(); // Fecha y hora actual
    $dia_semana_actual = $fecha_actual->format('N'); // 1 (Lunes) - 7 (Domingo)

    // Si es fin de semana (Sábado o Domingo), programar para el próximo Lunes
    if ($dia_semana_actual >= 6) {
        $fecha_actual->modify('next Monday');
    } else {
        // Si es entre semana, programar para la siguiente semana
        $fecha_actual->modify('next week');
    }

    // Buscar un horario disponible
    foreach ($horarios as $horario) {
        $dia_horario = $horario['dia']; // Ejemplo: "Lunes"
        $hora_inicio = $horario['hora_inicio']; // Ejemplo: "14:00:00"

        // Convertir el día del horario a un número (1 = Lunes, 7 = Domingo)
        $dias_semana = [
            'Lunes' => 1,
            'Martes' => 2,
            'Miércoles' => 3,
            'Jueves' => 4,
            'Viernes' => 5,
            'Sábado' => 6,
            'Domingo' => 7,
        ];
        $dia_numero = $dias_semana[$dia_horario];

        // Calcular la fecha del horario
        $fecha_horario = clone $fecha_actual;
        $fecha_horario->modify('+' . ($dia_numero - $fecha_actual->format('N')) . ' days');

        // Combinar fecha y hora
        $fecha_cita = $fecha_horario->format('Y-m-d') . ' ' . $hora_inicio;

        // Verificar si la cita ya está agendada
        $cita_existe = false;
        foreach ($citas as $cita) {
            if ($cita['fecha_cita'] == $fecha_cita) {
                $cita_existe = true;
                break;
            }
        }

        // Si la cita no existe, devolver la fecha
        if (!$cita_existe) {
            return $fecha_cita;
        }
    }

    // Si no se encontró una fecha disponible, devolver null
    return null;
}

// Generar una fecha disponible
$fecha_cita = generarFechaDisponible($horarios, $citas);

if (!$fecha_cita) {
    echo "No hay horarios disponibles para agendar una cita.";
    exit();
}

// Insertar la cita en la base de datos
$sql = "INSERT INTO Citas (id_paciente, id_doctor, fecha_cita) VALUES ($id_paciente, $id_doctor, '$fecha_cita')";
if ($conexion->query($sql)) {
    echo "Cita agendada con éxito para el día: " . $fecha_cita;
} else {
    echo "Error al agendar la cita: " . $conexion->error;
}
?>