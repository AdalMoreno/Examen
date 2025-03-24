<?php
session_start();
include 'db.php';

// Verificar si el usuario está autenticado y es paciente
if (!isset($_SESSION['id_usuario']) || $_SESSION['tipo'] != 'Paciente') {
    header("Location: login.php");
    exit();
}

$id_paciente = $_SESSION['id_usuario'];

// Obtener nombre del paciente
$sql_paciente = "SELECT nombre FROM Usuario WHERE id_usuario = $id_paciente";
$result_paciente = $conexion->query($sql_paciente);
$paciente = $result_paciente->fetch_assoc();

// Obtener citas médicas del paciente
$sql_citas = "SELECT c.id_cita, d.especialidad, u.nombre AS doctor, c.fecha_cita 
              FROM Citas c
              JOIN Doctor d ON c.id_doctor = d.id_doctor
              JOIN Usuario u ON d.id_doctor = u.id_usuario
              WHERE c.id_paciente = $id_paciente";
$result_citas = $conexion->query($sql_citas);

// Obtener los horarios de los doctores
$sql_doctores = "SELECT d.id_doctor, d.especialidad, h.dia, h.hora_inicio, h.hora_fin 
                 FROM Doctor d
                 LEFT JOIN HorarioDoctor h ON d.id_doctor = h.id_doctor";
$result_doctores = $conexion->query($sql_doctores);
$doctores = [];
while ($doctor = $result_doctores->fetch_assoc()) {
    $doctores[$doctor['id_doctor']][] = $doctor;
}

// Obtener historial médico del paciente
$sql_historial = "SELECT * FROM HistorialMedico WHERE id_paciente = $id_paciente ORDER BY fecha_registro DESC LIMIT 1";
$result_historial = $conexion->query($sql_historial);
$historial = $result_historial->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel del Paciente</title>
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f0f2f5;
            margin: 0;
            padding: 0;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #4CAF50;
            color: white;
            padding: 20px;
            font-size: 20px;
            font-weight: bold;
            box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
        }
        .logout {
            background: #ff4d4d;
            padding: 10px 15px;
            border-radius: 5px;
            cursor: pointer;
            transition: background 0.3s;
            color: white;
            text-decoration: none;
        }
        .logout:hover {
            background: #cc0000;
        }
        .container {
            display: flex;
            justify-content: center;
            align-items: center;
            flex-direction: column;
            padding: 40px 20px;
        }
        .content {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            width: 90%;
            max-width: 1000px;
        }
        .section {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s;
            cursor: pointer;
        }
        .section:hover {
            transform: translateY(-5px);
        }
        .section h3 {
            color: #4CAF50;
            margin-bottom: 10px;
        }
        .section p {
            color: #555;
            line-height: 1.5;
        }
        .hidden {
            display: none;
        }
        .appointments, .schedule, .medical-history {
            margin-top: 20px;
            padding: 20px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body>
    <div class="header">
        <div>Bienvenido, <?php echo $paciente['nombre']; ?></div>
        <a href="index.html" class="logout">Cerrar Sesión</a>
    </div>
    
    <div class="container">
        <div class="content">
            <div class="section" onclick="showSection('appointments')">
                <h3>📅 Ver Citas Médicas</h3>
                <p>Visualiza todas tus citas médicas programadas, incluyendo la fecha, hora, especialidad y el nombre del doctor asignado.</p>
            </div>
            
            <div class="section" onclick="showSection('schedule')">
                <h3>📝 Agendar Citas</h3>
                <p>Solicita y agenda nuevas citas médicas, eligiendo la especialidad, el doctor y la fecha disponible que más te convenga.</p>
            </div>
            
            <div class="section" onclick="showSection('medical-history')">
                <h3>📖 Ver Historial Médico</h3>
                <p>Accede a tu historial médico, incluyendo diagnósticos previos, tratamientos, medicamentos recetados y resultados de exámenes.</p>
            </div>
        </div>
        
        <div id="appointments" class="appointments hidden">
            <h3>📅 Tus Citas Médicas</h3>
            <ul>
                <?php while ($cita = $result_citas->fetch_assoc()): ?>
                    <li><strong>Fecha:</strong> <?php echo $cita['fecha_cita']; ?> | <strong>Especialidad:</strong> <?php echo $cita['especialidad']; ?> | <strong>Doctor:</strong> <?php echo $cita['doctor']; ?></li>
                <?php endwhile; ?>
                <?php if ($result_citas->num_rows == 0): ?>
                    <li>No tienes citas programadas.</li>
                <?php endif; ?>
            </ul>
        </div>
        
        <div id="schedule" class="schedule hidden">
            <h3>📝 Agendar una Nueva Cita</h3>
            <form action="guardar_cita.php" method="POST">
                <label for="id_doctor">Seleccionar Doctor:</label>
                <select name="id_doctor" id="id_doctor" required>
                    <option value="" disabled selected>Seleccione un doctor</option>
                    <?php foreach ($doctores as $id_doctor => $horarios): ?>
                        <option value="<?php echo $id_doctor; ?>"><?php echo $horarios[0]['especialidad']; ?></option>
                    <?php endforeach; ?>
                </select>

                <button type="submit">Agendar Cita</button>
            </form>
        </div>
        
        <div id="medical-history" class="medical-history hidden">
            <h3>📖 Tu Historial Médico</h3>
            <?php if ($historial): ?>
                <p>Última consulta: <?php echo $historial['fecha_registro']; ?> - Diagnóstico: <?php echo $historial['diagnostico']; ?></p>
            <?php else: ?>
                <p>No tienes historial médico registrado.</p>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function showSection(sectionId) {
            var section = document.getElementById(sectionId);
            
            // Verifica si la sección ya está visible
            var isVisible = !section.classList.contains('hidden');

            // Oculta todas las secciones
            document.querySelectorAll('.appointments, .schedule, .medical-history').forEach(function(sec) {
                sec.classList.add('hidden');
            });

            // Si la sección no estaba visible, la muestra; si ya estaba visible, la oculta
            if (!isVisible) {
                section.classList.remove('hidden');
            }
        }
    </script>
</body>
</html>