<?php
session_start();
if (!isset($_SESSION["s_usuario"])) {
    header("Location: login.php");
    exit();
}

$conexion = new mysqli("localhost", "root", "", "registro_llaves");
$conexion->set_charset("utf8");

// Procesar eliminación
if (isset($_GET['eliminar'])) {
    $id = $_GET['eliminar'];
    $query = "DELETE FROM ubicacion WHERE id_ubicacion = $id";
    $conexion->query($query);
    header("Location: ubicaciones.php");
    exit();
}

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre = $_POST['nombre'];
    $tipo = strtoupper($_POST['tipo']);

    if (isset($_POST['id'])) {
        $id = $_POST['id'];
        $query = "UPDATE ubicacion SET nombre_ubicacion = '$nombre', tipo = '$tipo' WHERE id_ubicacion = $id";
    } else {
        $query = "INSERT INTO ubicacion (nombre_ubicacion, tipo) VALUES ('$nombre', '$tipo')";
    }

    $conexion->query($query);
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
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="tablaUbicaciones">
                            <?php while ($row = $ubicaciones->fetch_assoc()): ?>
                                <tr>
                                    <td><?= $row['id_ubicacion'] ?></td>
                                    <td><?= htmlspecialchars($row['nombre_ubicacion']) ?></td>
                                    <td><?= htmlspecialchars($row['tipo']) ?></td>
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