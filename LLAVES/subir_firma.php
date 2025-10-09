<?php
session_start();
$mensaje = ""; // Variable para los mensajes de confirmación o error

if (!isset($_GET['id'])) {
    die("ID de movimiento no especificado.");
}

$id_movimiento = (int)$_GET['id'];

// Verificar que el usuario esté autenticado
if (!isset($_SESSION["s_usuario"])) {
    die("❌ Error: No hay usuario autenticado.");
}

$subido_por = $_SESSION["s_usuario"];

// Si el valor de la sesión es un nombre, intentar obtener el ID
if (!is_numeric($subido_por)) {
    require('conexion.php');
    $stmt = $conexion->prepare("SELECT id FROM usuarios WHERE nombre = ?");
    $stmt->bind_param("s", $subido_por);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($fila = $result->fetch_assoc()) {
        $subido_por = $fila['id']; // Usar el ID correcto
    } else {
        die("❌ Error: No se pudo obtener el ID del usuario.");
    }
}

$subido_por = (int)$subido_por; // Convertir a número para la inserción

// Procesar el archivo si el formulario ha sido enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require('conexion.php');
    $fecha_subida = date('Y-m-d H:i:s'); // Fecha actual

    // Validar que el movimiento existe
    $checkMovimiento = $conexion->prepare("SELECT id_movimiento FROM movimientos_llaves WHERE id_movimiento = ?");
    $checkMovimiento->bind_param("i", $id_movimiento);
    $checkMovimiento->execute();
    $result = $checkMovimiento->get_result();

    if ($result->num_rows === 0) {
        $mensaje = "❌ Error: El movimiento no existe en la base de datos.";
    } else {
        // Validar que el usuario existe
        $checkUsuario = $conexion->prepare("SELECT id FROM usuarios WHERE id = ?");
        $checkUsuario->bind_param("i", $subido_por);
        $checkUsuario->execute();
        $resultUsuario = $checkUsuario->get_result();

        if ($resultUsuario->num_rows === 0) {
            $mensaje = "❌ Error: El usuario autenticado no existe.";
        } else {
            // Procesar el archivo
            if (isset($_FILES['archivo']) && $_FILES['archivo']['error'] === UPLOAD_ERR_OK) {
                $archivoTmp = $_FILES['archivo']['tmp_name'];
                $nombreOriginal = basename($_FILES['archivo']['name']);
                $extension = strtolower(pathinfo($nombreOriginal, PATHINFO_EXTENSION));

                if ($extension !== 'pdf') {
                    $mensaje = "❌ Error: Solo se permiten archivos PDF.";
                } else {
                    // Crear carpeta si no existe
                    $directorioDestino = __DIR__ . '/firmas/';
                    if (!is_dir($directorioDestino)) {
                        mkdir($directorioDestino, 0777, true);
                    }

                    // Nombre único del archivo
                    $nombreFinal = "movimiento_" . $id_movimiento . "_" . time() . ".pdf";
                    $rutaFinal = $directorioDestino . $nombreFinal;

                    // Mover archivo al directorio destino
                    if (move_uploaded_file($archivoTmp, $rutaFinal)) {
                        // Insertar el registro en la base de datos
                        $stmt = $conexion->prepare("INSERT INTO firmas_llaves (id_movimiento, fichero, fecha_subida, subido_por) VALUES (?, ?, ?, ?)");
                        $stmt->bind_param("isss", $id_movimiento, $rutaFinal, $fecha_subida, $subido_por);

                        if ($stmt->execute()) {
                            $mensaje = "✅ Archivo subido y registrado correctamente.";
                        } else {
                            $mensaje = "❌ Error al registrar el archivo en la base de datos: " . $stmt->error;
                        }
                    } else {
                        $mensaje = "❌ Error al mover el archivo.";
                    }
                }
            } else {
                $mensaje = "❌ Error al subir el archivo.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Subir archivo firmado</title>
    <link rel="stylesheet" href="css/estilos.css">
    <script>
        function toggleMenu() {
            const submenu = document.getElementById('submenu');
            submenu.classList.toggle('show');
        }
    </script>
    <style>
        h2 {
            color: #333;
            text-align: center;
        }

        .upload-form {
            margin: auto;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            background: white;
            padding: 35px;
            border-radius: 8px;
            box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 400px;
        }

        .label {
            font-weight: bold;
            display: block;
            margin-top: 10px;
        }

        .file-input {
            display: block;
            margin-top: 5px;
        }

        .submit-btn {
            background-color: #007bff;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin-top: 15px;
        }

        .submit-btn:hover {
            background-color: #0056b3;
        }

        .mensaje {
            text-align: center;
            margin-top: 20px;
            color: green;
            font-weight: bold;
            width: 100%;
            box-sizing: border-box;
        }

        .error {
            color: red;
        }
    </style>
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
            <h2>Subir PDF firmado del movimiento</h2>
            <div class="container">
                <form action="" method="POST" enctype="multipart/form-data" class="upload-form">
                    <input type="hidden" name="id_movimiento" value="<?= $id_movimiento ?>">
                    <label for="archivo" class="label">Selecciona el archivo PDF:</label>
                    <input type="file" name="archivo" accept="application/pdf" required class="file-input">
                    <button type="submit" class="submit-btn">Subir Archivo</button>
                </form>

                <?php if ($mensaje): ?>
                    <div class="mensaje <?= strpos($mensaje, '❌') !== false ? 'error' : '' ?>">
                        <?= $mensaje ?>
                    </div>
                <?php endif; ?>
            </div>
        </main>

        <footer>
            © <?= date("Y") ?> Registro de Llaves
        </footer>
    </div>
</body>

</html>