<?php
session_start();
include 'db.php';

// Verificar si el usuario es un doctor autenticado
if (!isset($_SESSION['id_usuario']) || $_SESSION['tipo'] != 'Doctor') {
    header("Location: login.php");
    exit();
}

$id_doctor = $_SESSION['id_usuario'];

// Obtener nombre del doctor
$sql_doctor = "SELECT nombre FROM Usuario WHERE id_usuario = $id_doctor";
$result_doctor = $conexion->query($sql_doctor);
$doctor = $result_doctor->fetch_assoc();

// Obtener los días ya registrados
$sql_dias_registrados = "SELECT dia FROM HorarioDoctor WHERE id_doctor = $id_doctor";
$result_dias_registrados = $conexion->query($sql_dias_registrados);
$dias_registrados = [];
while ($row = $result_dias_registrados->fetch_assoc()) {
    $dias_registrados[] = $row['dia'];
}

// Obtener citas médicas asignadas al doctor con ID del paciente
$sql_citas = "SELECT c.id_cita, c.id_paciente, u.nombre AS paciente, c.fecha_cita, c.estado 
              FROM Citas c
              JOIN Usuario u ON c.id_paciente = u.id_usuario
              WHERE c.id_doctor = $id_doctor";
$result_citas = $conexion->query($sql_citas);

// Obtener horarios guardados por el doctor
$sql_horarios = "SELECT * FROM HorarioDoctor WHERE id_doctor = $id_doctor";
$result_horarios = $conexion->query($sql_horarios);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel del Doctor</title>
    <style>
        /* Estilos generales */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Roboto', sans-serif;
            background-color: #f5f5f5;
            color: #333;
            line-height: 1.6;
        }

        header {
            background-color: #2c3e50;
            padding: 20px;
            text-align: center;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            color: #ecf0f1;
        }

        header h1 {
            margin: 0;
            font-size: 2rem;
            font-weight: 500;
        }

        #logout {
            margin-top: -40px;
            background-color: #e74c3c;
            color: white;
            border: none;
            padding: 10px 20px;
            cursor: pointer;
            float: right;
            border-radius: 4px;
            font-size: 1rem;
            transition: background-color 0.3s ease;
        }

        #logout:hover {
            background-color: #c0392b;
        }

        main {
            padding: 20px;
            max-width: 1200px;
            margin: 0 auto;
        }

        h2 {
            color: #2c3e50;
            font-size: 1.75rem;
            margin-bottom: 20px;
            font-weight: 500;
            border-bottom: 2px solid #2c3e50;
            padding-bottom: 10px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        table, th, td {
            border: 1px solid #ddd;
        }

        th, td {
            padding: 12px;
            text-align: left;
        }

        th {
            background-color: #2c3e50;
            color: white;
        }

        tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        tr:hover {
            background-color: #f1f1f1;
        }

        select {
            padding: 6px;
            border-radius: 4px;
            border: 1px solid #ddd;
            font-size: 0.9rem;
        }

        button {
            padding: 8px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.9rem;
            transition: background-color 0.3s ease;
        }

        button.primary {
            background-color: #27ae60;
            color: white;
        }

        button.primary:hover {
            background-color: #219653;
        }

        button.secondary {
            background-color: #e74c3c;
            color: white;
        }

        button.secondary:hover {
            background-color: #c0392b;
        }

        form {
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }

        form label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #2c3e50;
        }

        form input[type="text"], form textarea, form input[type="time"], form select {
            width: 100%;
            padding: 12px;
            margin-bottom: 20px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }

        form input[type="text"]:focus, form textarea:focus, form input[type="time"]:focus, form select:focus {
            border-color: #2c3e50;
            outline: none;
        }
        /* Cambiar el estilo de la opción por defecto */
        select option[disabled] {
            color: #888; /* Color gris para la opción deshabilitada */
        }

        /* Estilo para las opciones habilitadas */
        select option {
            color: #333; /* Color negro para las opciones habilitadas */
        }
        @media (max-width: 768px) {
            header h1 {
                font-size: 1.5rem;
            }

            #logout {
                padding: 8px 16px;
                font-size: 0.9rem;
            }

            h2 {
                font-size: 1.5rem;
            }

            table, th, td {
                font-size: 0.9rem;
            }

            button {
                padding: 6px 10px;
                font-size: 0.8rem;
            }
        }
    </style>
</head>
<body>
    <header>
        <h1>Bienvenido, Dr. <?php echo $doctor['nombre']; ?></h1>
        <button id="logout" onclick="window.location.href='index.html'">Cerrar Sesión</button>
    </header>

    <main>
       <!-- Configurar Horario de Atención -->
        <section id="horario">
            <h2>Configurar Horario de Atención</h2>
            <form id="formHorario" action="guardar_horario.php" method="POST">
                <label for="dia">Día de la semana:</label>
                <select id="dia" name="dia" required>
                    <option value="" selected disabled>Seleccione un día</option>
                    <?php
                    $dias_semana = ["Lunes", "Martes", "Miércoles", "Jueves", "Viernes", "Sábado", "Domingo"];
                    foreach ($dias_semana as $dia) {
                        $disabled = in_array($dia, $dias_registrados) ? 'disabled' : '';
                        echo "<option value='$dia' $disabled>$dia</option>";
                    }
                    ?>
                </select>

                <label for="horaInicio">Hora de inicio:</label>
                <input type="time" id="horaInicio" name="hora_inicio" required>

                <label for="horaFin">Hora de fin:</label>
                <input type="time" id="horaFin" name="hora_fin" required>

                <button type="submit" class="primary">Guardar Horario</button>
            </form>
        </section>
        <!-- Mostrar Horario en Tabla -->
        <section id="mostrarHorario">
            <h2>Mi Horario de Atención</h2>
            <table>
                <thead>
                    <tr>
                        <th>Día</th>
                        <th>Hora de Inicio</th>
                        <th>Hora de Fin</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($horario = $result_horarios->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $horario['dia']; ?></td>
                            <td><?php echo $horario['hora_inicio']; ?></td>
                            <td><?php echo $horario['hora_fin']; ?></td>
                            <td>
                                <button class="primary" onclick="editarHorario(<?php echo $horario['id_horario']; ?>)">Editar</button>
                                <button class="secondary" onclick="eliminarHorario(<?php echo $horario['id_horario']; ?>)">Eliminar</button>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                    <?php if ($result_horarios->num_rows == 0): ?>
                        <tr>
                            <td colspan="4">No has configurado horarios.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </section>

       
        <!-- Mis Citas Médicas -->
        <section id="citas">
            <h2>Mis Citas Médicas</h2>
            <table>
                <thead>
                    <tr>
                        <th>ID Paciente</th>
                        <th>Paciente</th>
                        <th>Fecha y Hora</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($cita = $result_citas->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $cita['id_paciente']; ?></td>
                            <td><?php echo $cita['paciente']; ?></td>
                            <td><?php echo $cita['fecha_cita']; ?></td>
                            <td>
                                <select id="estado_<?php echo $cita['id_cita']; ?>" onchange="cambiarEstado(<?php echo $cita['id_cita']; ?>)">
                                    <option value="Pendiente" <?php echo ($cita['estado'] == 'Pendiente') ? 'selected' : ''; ?>>Pendiente</option>
                                    <option value="Confirmada" <?php echo ($cita['estado'] == 'Confirmada') ? 'selected' : ''; ?>>Confirmada</option>
                                    <option value="Cancelada" <?php echo ($cita['estado'] == 'Cancelada') ? 'selected' : ''; ?>>Cancelada</option>
                                    <option value="Completada" <?php echo ($cita['estado'] == 'Completada') ? 'selected' : ''; ?>>Completada</option>
                                </select>
                            </td>
                            <td>
                                <button class="secondary" onclick="eliminarCita(<?php echo $cita['id_cita']; ?>)">Eliminar</button>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                    <?php if ($result_citas->num_rows == 0): ?>
                        <tr>
                            <td colspan="5">No tienes citas programadas.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </section>

      <!-- Registro de Notas Médicas -->
    <section id="notas">
        <h2>Registro de Notas Médicas</h2>
        <form id="formNotas">
            <label for="id_paciente">ID del Paciente:</label>
            <input type="number" id="id_paciente" name="id_paciente" placeholder="Ingrese el ID del paciente" required>

            <label for="diagnostico">Diagnóstico:</label>
            <textarea id="diagnostico" name="diagnostico" placeholder="Escribe el diagnóstico aquí..." required></textarea>

            <label for="tratamiento">Tratamiento:</label>
            <textarea id="tratamiento" name="tratamiento" placeholder="Escribe el tratamiento aquí..." required></textarea>

            <button type="button" onclick="guardarNota()" class="primary">Guardar Nota</button>
        </form>
    </section>
    </main>

    <script>
         // Función para eliminar horario
         function eliminarHorario(id_horario) {
            if (confirm("¿Estás seguro de eliminar este horario?")) {
                fetch(`eliminar_horario.php?id=${id_horario}`, {
                    method: 'DELETE'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        alert(data.message);
                        location.reload();
                    } else {
                        alert(data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                });
            }
        }

        // Función para editar horario
        function editarHorario(id_horario) {
            const nuevoInicio = prompt("Ingresa la nueva hora de inicio (HH:MM):");
            const nuevoFin = prompt("Ingresa la nueva hora de fin (HH:MM):");

            if (nuevoInicio && nuevoFin) {
                fetch(`editar_horario.php?id=${id_horario}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        hora_inicio: nuevoInicio,
                        hora_fin: nuevoFin
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        alert(data.message);
                        location.reload();
                    } else {
                        alert(data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                });
            }
        }
        // Función para cambiar el estado de la cita
        function cambiarEstado(id_cita) {
            const nuevoEstado = document.getElementById(`estado_${id_cita}`).value;

            fetch(`actualizar_estado_cita.php?id=${id_cita}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    estado: nuevoEstado
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    alert(data.message);
                } else {
                    alert(data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
            });
        }

        // Función para eliminar una cita
        function eliminarCita(id_cita) {
            if (confirm("¿Estás seguro de eliminar esta cita?")) {
                fetch(`eliminar_cita.php?id=${id_cita}`, {
                    method: 'DELETE'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        alert(data.message);
                        location.reload();
                    } else {
                        alert(data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                });
            }
        }
       // Función para guardar una nota médica
        function guardarNota() {
            const id_paciente = document.getElementById('id_paciente').value;
            const diagnostico = document.getElementById('diagnostico').value;
            const tratamiento = document.getElementById('tratamiento').value;

            // Verificar que todos los campos estén llenos
            if (!id_paciente || !diagnostico || !tratamiento) {
                alert('Por favor, completa todos los campos.');
                return;
            }

            // Crear el objeto con los datos de la nota médica
            const notaData = {
                id_paciente: id_paciente,
                diagnostico: diagnostico,
                tratamiento: tratamiento
            };

            // Enviar la solicitud al servidor
            fetch('guardar_historial.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(notaData)
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    alert(data.message);
                    location.reload(); // Recargar la página después de guardar
                } else {
                    alert(data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Hubo un error al guardar la nota médica.');
            });
        }
    </script>
</body>
</html>