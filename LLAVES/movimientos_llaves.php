<?php
session_start();

if (!isset($_SESSION["s_usuario"])) {
    header("Location: login.php");
    exit();
}

$conexion = new mysqli("localhost", "root", "", "registro_llaves");
if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}
$conexion->set_charset("utf8");

$codigo_principal = $_GET['codigo_principal'] ?? '';
$id_propietario = $_GET['id_propietario'] ?? '';

// Consulta para obtener todas las ubicaciones disponibles
$query_ubicaciones = "SELECT id_ubicacion, nombre_ubicacion, tipo FROM ubicacion ORDER BY nombre_ubicacion";
$result_ubicaciones = $conexion->query($query_ubicaciones);
$ubicaciones = $result_ubicaciones ? $result_ubicaciones->fetch_all(MYSQLI_ASSOC) : [];

// Procesar eliminación de movimiento
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['eliminar_movimiento'])) {
    $id_movimiento = $_POST['id_movimiento'] ?? '';

    if ($id_movimiento) {
        $stmt = $conexion->prepare("DELETE FROM movimientos_llaves WHERE id_movimiento = ?");
        if ($stmt) {
            $stmt->bind_param("i", $id_movimiento);
            if ($stmt->execute()) {
                $_SESSION['mensaje'] = "✔ Movimiento eliminado correctamente.";
                $_SESSION['tipo_mensaje'] = "success";
            } else {
                $_SESSION['mensaje'] = "⚠️ Error al eliminar movimiento: " . $stmt->error;
                $_SESSION['tipo_mensaje'] = "error";
            }
            $stmt->close();
            header("Location: " . $_SERVER['REQUEST_URI']);
            exit();
        }
    }
}

// Procesar actualización de movimiento
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['actualizar_movimiento'])) {
    $id_movimiento = $_POST['id_movimiento'] ?? '';
    $tipo_movimiento = $_POST['tipo_movimiento'] ?? '';
    $fecha_movimiento = $_POST['fecha_movimiento'] ?? '';

    if ($id_movimiento && $tipo_movimiento && $fecha_movimiento) {
        $stmt = $conexion->prepare("UPDATE movimientos_llaves SET 
                                  tipo_movimiento = ?, 
                                  fecha = ? 
                                  WHERE id_movimiento = ?");
        if ($stmt) {
            $stmt->bind_param("ssi", $tipo_movimiento, $fecha_movimiento, $id_movimiento);
            if ($stmt->execute()) {
                $_SESSION['mensaje'] = "✔ Movimiento actualizado correctamente.";
                $_SESSION['tipo_mensaje'] = "success";
            } else {
                $_SESSION['mensaje'] = "⚠️ Error al actualizar movimiento: " . $stmt->error;
                $_SESSION['tipo_mensaje'] = "error";
            }
            $stmt->close();
            header("Location: " . $_SERVER['REQUEST_URI']);
            exit();
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['actualizar'])) {
    $nueva_direccion = $conexion->real_escape_string($_POST['direccion'] ?? '');
    $nueva_ubicacion_nombre = $conexion->real_escape_string($_POST['ubicacion'] ?? '');
    $nueva_fecha_recepcion = $conexion->real_escape_string($_POST['fecha_recepcion'] ?? '');
    $nueva_observaciones = $conexion->real_escape_string($_POST['observaciones'] ?? '');

    // Obtener datos actuales de la llave
    $stmt = $conexion->prepare("SELECT l.*, u.nombre_ubicacion, u.tipo 
                               FROM llaves l 
                               INNER JOIN ubicacion u ON l.id_ubicacion = u.id_ubicacion 
                               WHERE l.codigo_principal = ? AND l.id_propietario = ?");
    if ($stmt === false) {
        die("Error preparando la consulta: " . $conexion->error);
    }

    if (!$stmt->bind_param("ss", $codigo_principal, $id_propietario)) {
        die("Error vinculando parámetros: " . $stmt->error);
    }

    if (!$stmt->execute()) {
        die("Error ejecutando la consulta: " . $stmt->error);
    }

    $consulta_actual = $stmt->get_result();
    $stmt->close();

    if ($consulta_actual && $consulta_actual->num_rows > 0) {
        $actual = $consulta_actual->fetch_assoc();

        $stmt_ubic = $conexion->prepare("SELECT id_ubicacion, tipo FROM ubicacion WHERE nombre_ubicacion = ?");
        if ($stmt_ubic === false) {
            die("Error preparando consulta de ubicación: " . $conexion->error);
        }

        if (!$stmt_ubic->bind_param("s", $nueva_ubicacion_nombre)) {
            die("Error vinculando parámetros de ubicación: " . $stmt_ubic->error);
        }

        if (!$stmt_ubic->execute()) {
            die("Error ejecutando consulta de ubicación: " . $stmt_ubic->error);
        }

        $res_ubic = $stmt_ubic->get_result();

        if ($res_ubic && $res_ubic->num_rows > 0) {
            $row_ubic = $res_ubic->fetch_assoc();
            $id_ubicacion_nueva = $row_ubic['id_ubicacion'];
            $tipo_ubicacion_nueva = strtolower($row_ubic['tipo']);
        } else {
            echo "<p style='color:red'>⚠️ La ubicación seleccionada no es válida.</p>";
            $stmt_ubic->close();
            exit();
        }
        $stmt_ubic->close();

        // Determinar el nuevo estado basado en el tipo de ubicación
        $nuevo_estado = ($tipo_ubicacion_nueva === 'interna') ? 'entrada' : 'salida';

        // Actualizar llave
        // Asegurar columna baja
        $conexion->query("ALTER TABLE llaves ADD COLUMN IF NOT EXISTS baja TINYINT(1) NOT NULL DEFAULT 0");

        $stmt_update = $conexion->prepare("UPDATE llaves SET 
            direccion = ?, 
            id_ubicacion = ?, 
            fecha_recepcion = ?, 
            observaciones = ?, 
            estado = ? 
            WHERE codigo_principal = ? AND id_propietario = ?");

        if ($stmt_update === false) {
            die("Error preparando actualización: " . $conexion->error);
        }

        if (!$stmt_update->bind_param(
            "sisssss",
            $nueva_direccion,
            $id_ubicacion_nueva,
            $nueva_fecha_recepcion,
            $nueva_observaciones,
            $nuevo_estado,
            $codigo_principal,
            $id_propietario
        )) {
            die("Error vinculando parámetros de actualización: " . $stmt_update->error);
        }

        if ($stmt_update->execute()) {
            $nombre_usuario = $_SESSION['s_usuario'];
            $fecha_mov = date('Y-m-d H:i:s');
            $tipo_movimiento = $nuevo_estado;

            $id_usuario = 1;
            $stmt_user = $conexion->prepare("SELECT id FROM usuarios WHERE nombre = ?");
            if ($stmt_user) {
                $stmt_user->bind_param("s", $nombre_usuario);
                if ($stmt_user->execute()) {
                    $result = $stmt_user->get_result();
                    if ($result->num_rows > 0) {
                        $row = $result->fetch_assoc();
                        $id_usuario = $row['id'];
                    }
                }
                $stmt_user->close();
            }

            $stmt_mov = $conexion->prepare("INSERT INTO movimientos_llaves 
                (codigo_principal, id_propietario, id_ubicacion, tipo_movimiento, fecha, fichero, id_usuario) 
                VALUES (?, ?, ?, ?, ?, '', ?)");

            if ($stmt_mov) {
                if ($stmt_mov->bind_param(
                    "ssisss",
                    $codigo_principal,
                    $id_propietario,
                    $id_ubicacion_nueva,
                    $tipo_movimiento,
                    $fecha_mov,
                    $id_usuario
                )) {
                    if (!$stmt_mov->execute()) {
                        echo "<p style='color:red'>⚠️ Error al insertar movimiento: " . $conexion->error . "</p>";
                    }
                    $stmt_mov->close();
                }
            }

            header("Location: " . $_SERVER['REQUEST_URI']);
            exit();
        } else {
            echo "<p style='color:red'>⚠️ Error al actualizar llave: " . $stmt_update->error . "</p>";
        }
        $stmt_update->close();
    } else {
        echo "<p style='color:red'>⚠️ No se encontró la llave a editar.</p>";
    }
}

// Consulta de datos de la llave
$query_llave = "
    SELECT llaves.*, 
           propietario.nombre_propietario, 
           poblacion.nombre_poblacion, 
           ubicacion.nombre_ubicacion,
           ubicacion.tipo
    FROM llaves
    INNER JOIN propietario ON llaves.id_propietario = propietario.id_propietario
    INNER JOIN poblacion ON llaves.id_poblacion = poblacion.id_poblacion
    INNER JOIN ubicacion ON llaves.id_ubicacion = ubicacion.id_ubicacion
    WHERE llaves.codigo_principal = '$codigo_principal' AND llaves.id_propietario = '$id_propietario'";
$result_llave = $conexion->query($query_llave);
$llave = $result_llave ? $result_llave->fetch_assoc() : null;

// Consulta de movimientos
$query_movimientos = "
    SELECT 
        m.id_movimiento,
        m.codigo_principal,
        p.nombre_propietario,
        u.nombre_ubicacion,
        m.tipo_movimiento,
        m.fecha,
        m.fichero,
        us.nombre as nombre_usuario
    FROM movimientos_llaves m
    LEFT JOIN propietario p ON m.id_propietario = p.id_propietario
    LEFT JOIN ubicacion u ON m.id_ubicacion = u.id_ubicacion
    LEFT JOIN usuarios us ON m.id_usuario = us.id
    WHERE m.codigo_principal = '$codigo_principal'
    ORDER BY m.fecha DESC
";

$movimientos = $conexion->query($query_movimientos);
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Detalles de la Llave</title>
    <link rel="stylesheet" href="css/estilos.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .acciones-movimiento {
            display: flex;
            gap: 5px;
        }

        .btn-editar-movimiento,
        .btn-eliminar-movimiento,
        .btn-actualizar-movimiento,
        .btn-cancelar-movimiento {
            padding: 3px 8px;
            border: none;
            border-radius: 3px;
            cursor: pointer;
        }

        .btn-editar-movimiento {
            background-color: #3498db;
            color: white;
        }

        .btn-eliminar-movimiento {
            background-color: #e74c3c;
            color: white;
        }

        .btn-actualizar-movimiento {
            background-color: #2ecc71;
            color: white;
        }

        .btn-cancelar-movimiento {
            background-color: #f39c12;
            color: white;
        }

        .btn-editar-movimiento:hover,
        .btn-eliminar-movimiento:hover,
        .btn-actualizar-movimiento:hover,
        .btn-cancelar-movimiento:hover {
            opacity: 0.8;
        }

        .edit-select,
        .edit-input {
            width: 100%;
            padding: 5px;
            box-sizing: border-box;
        }

        .mensaje {
            padding: 10px;
            margin: 10px 0;
            border-radius: 4px;
        }

        .mensaje-success {
            background-color: #d4edda;
            color: #155724;
        }

        .mensaje-error {
            background-color: #f8d7da;
            color: #721c24;
        }
    </style>
    <script>
        function cancelarEdicion() {
            if (confirm('¿Estás seguro de que deseas cancelar los cambios?')) {
                location.reload();
            }
        }

        function toggleMenu() {
            const submenu = document.getElementById('submenu');
            submenu.classList.toggle('show');
        }

        function entrarModoEdicionMovimiento(row) {
            // Guardar datos originales
            const datosOriginales = {
                tipo_movimiento: row.querySelector('.tipo-movimiento-cell').textContent.trim(),
                fecha: row.querySelector('.fecha-movimiento-cell').textContent.trim(),
            };
            row.dataset.original = JSON.stringify(datosOriginales);

            // Añadir clase de edición
            row.classList.add('editando');

            // Crear select para tipo de movimiento
            const selectTipo = document.createElement('select');
            selectTipo.className = 'edit-select';
            ['entrada', 'salida'].forEach(tipo => {
                const option = document.createElement('option');
                option.value = tipo;
                option.textContent = tipo.charAt(0).toUpperCase() + tipo.slice(1);
                if (tipo.toLowerCase() === datosOriginales.tipo_movimiento.toLowerCase()) {
                    option.selected = true;
                }
                selectTipo.appendChild(option);
            });

            // Crear input para fecha
            const inputFecha = document.createElement('input');
            inputFecha.type = 'datetime-local';
            inputFecha.className = 'edit-input';

            // Convertir fecha al formato correcto para el input
            const fechaOriginal = new Date(datosOriginales.fecha.replace(/(\d{2})-(\d{2})-(\d{4}) (\d{2}:\d{2}:\d{2})/, '$3-$2-$1T$4'));
            inputFecha.value = fechaOriginal.toISOString().slice(0, 16);

            // Reemplazar celdas con controles de edición
            row.querySelector('.tipo-movimiento-cell').innerHTML = '';
            row.querySelector('.tipo-movimiento-cell').appendChild(selectTipo);

            row.querySelector('.fecha-movimiento-cell').innerHTML = '';
            row.querySelector('.fecha-movimiento-cell').appendChild(inputFecha);

            // Reemplazar botones
            row.querySelector('.acciones-contenedor').innerHTML = `
                <button class="btn-actualizar-movimiento" type="button" onclick="actualizarMovimiento(this.closest('tr'))">
                    <i class="fas fa-check"></i>
                </button>
                <button class="btn-cancelar-movimiento" type="button" onclick="cancelarEdicionMovimiento(this.closest('tr'))">
                    <i class="fas fa-times"></i>
                </button>
            `;
        }

        function cancelarEdicionMovimiento(row) {
            const original = JSON.parse(row.dataset.original);

            // Restaurar valores originales
            row.querySelector('.tipo-movimiento-cell').textContent = original.tipo_movimiento;
            row.querySelector('.fecha-movimiento-cell').textContent = original.fecha;

            // Restaurar botones, incluyendo el de upload
            const idMovimiento = row.dataset.idMovimiento;
            row.querySelector('.acciones-contenedor').innerHTML = generarAccionesHTML(idMovimiento);

            // Quitar clase de edición
            row.classList.remove('editando');
        }

        function generarAccionesHTML(idMovimiento) {
            return `
        <a href="subir_firma.php?id=${idMovimiento}" title="Subir PDF firmado" style="color: #27ae60; font-size: 1.2em;">
            <i class="fas fa-upload"></i>
        </a>
        <button class="btn-editar-movimiento" onclick="entrarModoEdicionMovimiento(this.closest('tr'))">
            <i class="fas fa-pencil-alt"></i>
        </button>
        <button class="btn-eliminar-movimiento" onclick="eliminarMovimiento(this.closest('tr'))">
            <i class="fas fa-trash-alt"></i>
        </button>
    `;
        }


        function actualizarMovimiento(row) {
            const id_movimiento = row.dataset.idMovimiento;
            if (!id_movimiento) {
                alert("ID de movimiento no encontrado.");
                return;
            }

            const tipo_movimiento = row.querySelector('.tipo-movimiento-cell select')?.value;
            const fecha_movimiento = row.querySelector('.fecha-movimiento-cell input')?.value;

            if (!tipo_movimiento || !fecha_movimiento) {
                alert("Faltan datos para actualizar.");
                return;
            }

            // Crear formulario dinámico para enviar los datos
            const form = document.createElement('form');
            form.method = 'POST';
            form.style.display = 'none';

            form.innerHTML = `
                <input type="hidden" name="id_movimiento" value="${id_movimiento}">
                <input type="hidden" name="tipo_movimiento" value="${tipo_movimiento}">
                <input type="hidden" name="fecha_movimiento" value="${fecha_movimiento}">
                <input type="hidden" name="actualizar_movimiento" value="1">
            `;

            document.body.appendChild(form);
            form.submit();
        }

        function eliminarMovimiento(row) {
            if (confirm('¿Estás seguro de que deseas eliminar este movimiento?')) {
                const id_movimiento = row.dataset.idMovimiento;

                // Crear formulario dinámico para enviar los datos
                const form = document.createElement('form');
                form.method = 'POST';
                form.style.display = 'none';

                form.innerHTML = `
                    <input type="hidden" name="id_movimiento" value="${id_movimiento}">
                    <input type="hidden" name="eliminar_movimiento" value="1">
                `;

                document.body.appendChild(form);
                form.submit();
            }
        }

        function toggleBaja(chk) {
            const codigo = chk.getAttribute('data-codigo');
            const idProp = chk.getAttribute('data-idprop');
            const activar = chk.checked;
            const lbl = document.getElementById('lbl_baja_texto');
            fetch(`procesar_entidades.php?tipo=baja&accion=${activar ? 'activar' : 'desactivar'}&codigo=${encodeURIComponent(codigo)}&id_propietario=${encodeURIComponent(idProp)}`)
                .then(r => r.json())
                .then(data => {
                    if (data && data.success) {
                        lbl.textContent = activar ? 'Sí' : 'No';
                    } else {
                        alert('No se pudo actualizar la baja.');
                        chk.checked = !activar;
                    }
                })
                .catch(() => {
                    alert('Error de red');
                    chk.checked = !activar;
                });
        }
    </script>
</head>

<body>
    <div class="wrapper">
        <header>
            <nav class="menu-container">
                <div class="menu-logo">
                    <a href="index.php" style="text-decoration: none; color: inherit;">Gestión de Llaves</a>
                </div>
                <div class="menu-dropdown">
                    <button class="menu-button" onclick="toggleMenu()">☰ Menú</button>
                    <ul class="menu-list" id="submenu">
                        <li><a href="index.php">Inicio</a></li>
                        <li><a href="registro_llave.php">Registrar llave</a></li>
                        <li class="menu-divider"></li>
                        <li><a href="propietarios.php">Propietarios</a></li>
                        <li><a href="poblaciones.php">Poblaciones</a></li>
                        <li><a href="ubicaciones.php">Ubicaciones</a></li>
                        <?php if (isset($_SESSION['es_admin']) && $_SESSION['es_admin']): ?>
                            <li><a href="admin/gestion_usuarios.php">Usuarios</a></li>
                        <?php endif; ?>
                        <li class="menu-divider"></li>
                        <li><a href="logout.php" class="logout-link">Cerrar sesión</a></li>
                    </ul>
                </div>
            </nav>
        </header>

        <main>
            <?php if (isset($_SESSION['mensaje'])): ?>
                <div class="mensaje mensaje-<?= $_SESSION['tipo_mensaje'] ?>">
                    <?= $_SESSION['mensaje'] ?>
                </div>
                <?php
                unset($_SESSION['mensaje']);
                unset($_SESSION['tipo_mensaje']);
                ?>
            <?php endif; ?>

            <div class="llave-detalles">
                <?php if ($llave): ?>
                    <h2>Detalles de la Llave</h2>
                    <form method="post" action="">
                        <p><strong>Código Principal:</strong> <?= htmlspecialchars($llave['codigo_principal']) ?></p>
                        <p><strong>Código secundario:</strong> <?= htmlspecialchars($llave['codigo_secundario']) ?></p>
                        <?php if (isset($llave['tiene_alarma'])): ?>
                            <p><strong>Alarma:</strong> <?= intval($llave['tiene_alarma']) ? 'Sí' : 'No' ?>
                                <?php if (intval($llave['tiene_alarma']) && !empty($llave['codigo_alarma'])): ?>
                                    (<?= htmlspecialchars($llave['codigo_alarma']) ?>)
                                <?php endif; ?>
                            </p>
                        <?php endif; ?>
                        <p><strong>Propietario:</strong> <?= htmlspecialchars($llave['nombre_propietario']) ?></p>
                        <p><strong>Población:</strong> <?= htmlspecialchars($llave['nombre_poblacion']) ?></p>
                        <p><strong>Dirección:</strong> <input type="text" name="direccion" id="direccion" value="<?= htmlspecialchars($llave['direccion']) ?>" /></p>

                        <p><strong>Ubicación:</strong>
                            <select name="ubicacion" id="ubicacion">
                                <?php foreach ($ubicaciones as $ubicacion): ?>
                                    <option value="<?= htmlspecialchars($ubicacion['nombre_ubicacion']) ?>"
                                        <?= ($ubicacion['nombre_ubicacion'] == $llave['nombre_ubicacion']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($ubicacion['nombre_ubicacion']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </p>

                        <p><strong>Fecha de Recepción:</strong> <input type="date" name="fecha_recepcion" id="fecha_recepcion" value="<?= date('Y-m-d', strtotime($llave['fecha_recepcion'])) ?>" /></p>
                        <p><strong>Observaciones:</strong> <input type="text" name="observaciones" id="observaciones" value="<?= htmlspecialchars($llave['observaciones']) ?>" /></p>
                        <p><strong>Baja:</strong> 
                            <label style="margin-left:6px; display:inline-flex; align-items:center; gap:6px;">
                                <input type="checkbox"
                                       id="chk_baja"
                                       data-codigo="<?= htmlspecialchars($llave['codigo_principal']) ?>"
                                       data-idprop="<?= $llave['id_propietario'] ?>"
                                       <?= isset($llave['baja']) && intval($llave['baja']) === 1 ? 'checked' : '' ?>
                                       onchange="toggleBaja(this)">
                                <span id="lbl_baja_texto"><?= (isset($llave['baja']) && intval($llave['baja']) === 1) ? 'Sí' : 'No' ?></span>
                            </label>
                        </p>

                        <div id="botonesEditar">
                            <button class="btn-actualizar" type="submit" name="actualizar">Actualizar</button>
                            <button class="btn-cancelar" type="button" onclick="cancelarEdicion()">Cancelar</button>
                        </div>
                    </form>
                <?php else: ?>
                    <p><strong>Error:</strong> No se encontraron detalles para esta llave.</p>
                <?php endif; ?>
            </div>

            <div class="movimientos">
                <h3>Movimientos de la Llave</h3>
                <?php if ($movimientos && $movimientos->num_rows > 0): ?>
                    <table class="table-compact">
                        <thead>
                            <tr>
                                <th>Código Principal</th>
                                <th>Propietario</th>
                                <th>Ubicación</th>
                                <th>Tipo de Movimiento</th>
                                <th>Fecha</th>
                                <th>Fichero</th>
                                <th>Usuario</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($mov = $movimientos->fetch_assoc()): ?>
                                <tr data-id-movimiento="<?= htmlspecialchars($mov['id_movimiento']) ?>">
                                    <td><?= htmlspecialchars($mov['codigo_principal']) ?></td>
                                    <td><?= htmlspecialchars($mov['nombre_propietario']) ?></td>
                                    <td><?= htmlspecialchars($mov['nombre_ubicacion']) ?></td>
                                    <td class="tipo-movimiento-cell"><?= ucfirst($mov['tipo_movimiento']) ?></td>
                                    <td class="fecha-movimiento-cell"><?= date('d-m-Y H:i:s', strtotime($mov['fecha'])) ?></td>
                                    <td>
                                        <a href="generar_pdf.php?id=<?= $mov['id_movimiento'] ?>" target="_blank" title="Ver PDF" style="color: #e74c3c; font-size: 1.2em;">
                                            <i class="fas fa-file-pdf"></i>
                                        </a>
                                        <?php if ($llave): ?>
                                            <a href="generar_etiqueta.php?codigo_principal=<?= urlencode($llave['codigo_principal']) ?>&id_propietario=<?= urlencode($llave['id_propietario']) ?>"
                                               target="_blank" title="Imprimir etiqueta" style="color: #2ecc71; font-size: 1.2em; margin-left:8px;">
                                                <i class="fas fa-tag"></i>
                                            </a>
                                        <?php endif; ?>
                                    </td>

                                    <td><?= htmlspecialchars($mov['nombre_usuario']) ?></td>
                                    <td>
                                        <div class="acciones-contenedor">
                                            <a href="subir_firma.php?id=<?= $mov['id_movimiento'] ?>" title="Subir PDF firmado" style="color: #27ae60; font-size: 1.2em;">
                                                <i class="fas fa-upload"></i>
                                            </a>
                                            <button class="btn-editar-movimiento" onclick="entrarModoEdicionMovimiento(this.closest('tr'))">
                                                <i class="fas fa-pencil-alt"></i>
                                            </button>
                                            <button class="btn-eliminar-movimiento" onclick="eliminarMovimiento(this.closest('tr'))">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>No hay movimientos para esta llave.</p>
                <?php endif; ?>
            </div>
        </main>
    </div>
    <footer>
        © <?= date("Y") ?> Registro de Llaves
    </footer>
</body>

</html>