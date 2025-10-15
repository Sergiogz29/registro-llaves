<?php
session_start();
if (!isset($_SESSION["s_usuario"])) {
    header("Location: login.php");
    exit();
}

$conexion = new mysqli("localhost", "root", "", "registro_llaves");
$conexion->set_charset("utf8");

// Asegurar columna para reclamo automático (si no existe)
$conexion->query("ALTER TABLE ubicacion ADD COLUMN IF NOT EXISTS requiere_reclamo TINYINT(1) NOT NULL DEFAULT 0");

// Procesar eliminación
if (isset($_GET['eliminar'])) {
    $id = intval($_GET['eliminar']);
    // Verificar dependencias con llaves
    $stmtChk = $conexion->prepare("SELECT COUNT(*) AS total FROM llaves WHERE id_ubicacion = ?");
    if ($stmtChk) {
        $stmtChk->bind_param("i", $id);
        $stmtChk->execute();
        $res = $stmtChk->get_result();
        $total = $res ? intval($res->fetch_assoc()['total'] ?? 0) : 0;
        $stmtChk->close();
        if ($total > 0) {
            $_SESSION['mensaje'] = "No se puede eliminar: hay $total llaves asociadas a esta ubicación.";
            $_SESSION['tipo_mensaje'] = "error";
            header("Location: ubicaciones.php");
            exit();
        }
    }

    $stmtDel = $conexion->prepare("DELETE FROM ubicacion WHERE id_ubicacion = ?");
    if ($stmtDel) {
        $stmtDel->bind_param("i", $id);
        $stmtDel->execute();
        $stmtDel->close();
        $_SESSION['mensaje'] = "Ubicación eliminada correctamente.";
        $_SESSION['tipo_mensaje'] = "success";
    }
    header("Location: ubicaciones.php");
    exit();
}

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre = $conexion->real_escape_string($_POST['nombre']);
    $tipo = strtoupper($_POST['tipo']);
    $requiere = isset($_POST['requiere_reclamo']) ? 1 : 0;

    if (isset($_POST['id'])) {
        $id = intval($_POST['id']);
        $query = "UPDATE ubicacion SET nombre_ubicacion = '$nombre', tipo = '$tipo', requiere_reclamo = $requiere WHERE id_ubicacion = $id";
    } else {
        $query = "INSERT INTO ubicacion (nombre_ubicacion, tipo, requiere_reclamo) VALUES ('$nombre', '$tipo', $requiere)";
    }

    if ($conexion->query($query)) {
        $_SESSION['mensaje'] = isset($_POST['id']) ? 'Ubicación actualizada.' : 'Ubicación creada.';
        $_SESSION['tipo_mensaje'] = 'success';
    } else {
        $_SESSION['mensaje'] = 'Error guardando ubicación: ' . $conexion->error;
        $_SESSION['tipo_mensaje'] = 'error';
    }
    header("Location: ubicaciones.php");
    exit();
}

// Obtener datos para editar
$editar = null;
if (isset($_GET['editar'])) {
    $id = $_GET['editar'];
    $query = "SELECT * FROM ubicacion WHERE id_ubicacion = $id";
    $result = $conexion->query($query);
    $editar = $result->fetch_assoc();
}

// Obtener todas las ubicaciones
$query = "SELECT * FROM ubicacion ORDER BY nombre_ubicacion";
$ubicaciones = $conexion->query($query);
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Gestión de Ubicaciones</title>
    <link rel="icon" type="image/png" href="img/logo1.png?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="css/estilos.css">
</head>

<body>
    <div class="wrapper">
        <header>
            <nav class="menu-container">
                <div class="menu-logo">
                    <a href="index.php" style="text-decoration: none; color: inherit;">Gestión de Ubicaciones</a>
                </div>
                <div class="menu-dropdown">
                    <button class="menu-button" onclick="toggleMenu()">☰ Menú</button>
                    <ul class="menu-list" id="submenu">
                        <li><a href="index.php">Inicio</a></li>
                        <li><a href="registro_llave.php">Registrar llave</a></li>
                        <li class="menu-divider"></li>
                        <li><a href="propietarios.php">Propietarios</a></li>
                        <li><a href="poblaciones.php">Poblaciones</a></li>
                        <li class="active"><a href="ubicaciones.php">Ubicaciones</a></li>
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
            <!-- <h1>Gestión de Ubicaciones</h1> -->
            <div class="container">
                <?php if (isset($_SESSION['mensaje'])): ?>
                    <div class="mensaje mensaje-<?= $_SESSION['tipo_mensaje'] ?>">
                        <?= $_SESSION['mensaje'] ?>
                    </div>
                    <?php unset($_SESSION['mensaje'], $_SESSION['tipo_mensaje']); ?>
                <?php endif; ?>
                <div class="form-container">
                    <h2><?= $editar ? 'Editar Ubicación' : 'Agregar Nueva Ubicación' ?></h2>
                    <form method="POST">
                        <?php if ($editar): ?>
                            <input type="hidden" name="id" value="<?= $editar['id_ubicacion'] ?>">
                        <?php endif; ?>

                        <div class="form-group">
                            <label for="nombre">Nombre de la Ubicación:</label>
                            <input type="text" id="nombre" name="nombre" required
                                value="<?= $editar ? htmlspecialchars($editar['nombre_ubicacion']) : '' ?>">
                        </div>


                        <div class="form-group">
                            <label for="tipo">Tipo:</label>
                            <select name="tipo" id="tipo" required class="form-group-select">
                                <option value="INTERNO" <?= isset($editar) && strtoupper($editar['tipo']) === 'INTERNO' ? 'selected' : '' ?>>INTERNO</option>
                                <option value="EXTERNO" <?= isset($editar) && strtoupper($editar['tipo']) === 'EXTERNO' ? 'selected' : '' ?>>EXTERNO</option>

                            </select>
                        </div>

                        <div class="form-group">
                            <label for="requiere_reclamo">Reclamo de llave:</label>
                            <label class="checkbox-verificado" style="margin-left:0">
                                <input type="checkbox" name="requiere_reclamo" id="requiere_reclamo" class="checkbox-verificado-input"
                                    <?= isset($editar) && isset($editar['requiere_reclamo']) && intval($editar['requiere_reclamo']) === 1 ? 'checked' : '' ?>>
                                <span class="checkbox-text">Enviar reclamo si no se devuelve en 7 días</span>
                            </label>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <?= $editar ? 'Actualizar' : 'Guardar' ?>
                        </button>

                        <?php if ($editar): ?>
                            <a href="ubicaciones.php" class="btn btn-secondary">Cancelar</a>
                        <?php endif; ?>
                    </form>
                </div>
                <div class="table-container">
                    <h2>Lista de Ubicaciones</h2>
                    <input type="text" id="filtroUbicacion" placeholder="Filtrar por nombre..." class="filtro-input" onkeyup="filtrarUbicaciones()">
                    <table class="table-data">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nombre</th>
                                <th>Tipo</th>
                                <th class="col-reclamo" style="text-align:center; width:90px;">Reclamo</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="tablaUbicaciones">
                            <?php while ($row = $ubicaciones->fetch_assoc()): ?>
                                <tr>
                                    <td><?= $row['id_ubicacion'] ?></td>
                                    <td><?= htmlspecialchars($row['nombre_ubicacion']) ?></td>
                                    <td><?= htmlspecialchars($row['tipo']) ?></td>
                                    <td class="col-reclamo" style="text-align:center; width:90px;">
                                        <?= (isset($row['requiere_reclamo']) && intval($row['requiere_reclamo']) === 1) ? 'Sí' : 'No' ?>
                                    </td>
                                    <td class="actions">
                                        <a href="ubicaciones.php?editar=<?= $row['id_ubicacion'] ?>" class="btn-edit" title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="ubicaciones.php?eliminar=<?= $row['id_ubicacion'] ?>" class="btn-delete" title="Eliminar" onclick="return confirm('¿Estás seguro?')">
                                            <i class="fas fa-trash-alt"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </main>

        <footer>
            © <?= date("Y") ?> Registro de Llaves
        </footer>

        <script>
            function toggleMenu() {
                const submenu = document.getElementById('submenu');
                submenu.classList.toggle('show');
            }

            document.addEventListener('click', function(event) {
                const menu = document.querySelector('.menu-dropdown');
                const submenu = document.getElementById('submenu');
                if (!menu.contains(event.target)) {
                    submenu.classList.remove('show');
                }
            });

            // Función de filtro de ubicaciones
            function filtrarUbicaciones() {
                const filtro = document.getElementById('filtroUbicacion').value.toLowerCase();
                const rows = document.querySelectorAll('#tablaUbicaciones tr');

                rows.forEach(row => {
                    const nombre = row.cells[1].textContent.toLowerCase();
                    if (nombre.includes(filtro)) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            }
        </script>
    </div>
</body>

</html>