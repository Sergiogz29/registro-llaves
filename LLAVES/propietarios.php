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
    $id = intval($_GET['eliminar']);
    $query = "DELETE FROM propietario WHERE id_propietario = $id";
    $conexion->query($query);
    header("Location: propietarios.php");
    exit();
}

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre = $conexion->real_escape_string($_POST['nombre']);

    if (isset($_POST['id'])) {
        $id = intval($_POST['id']);
        $query = "UPDATE propietario SET nombre_propietario = '$nombre' WHERE id_propietario = $id";
    } else {
        $query = "INSERT INTO propietario (nombre_propietario) VALUES ('$nombre')";
    }

    $conexion->query($query);
    header("Location: propietarios.php");
    exit();
}

// Filtro por nombre (AJAX)
$filtro = isset($_GET['filtro']) ? $conexion->real_escape_string($_GET['filtro']) : '';
$condicion = $filtro ? "WHERE nombre_propietario LIKE '%$filtro%'" : "";

// Obtener todos los propietarios
$query = "SELECT * FROM propietario $condicion ORDER BY nombre_propietario";
$propietarios = $conexion->query($query);

// AJAX
if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
    ob_start();
    while ($row = $propietarios->fetch_assoc()) {
        echo "<tr>
                <td>{$row['id_propietario']}</td>
                <td>" . htmlspecialchars($row['nombre_propietario']) . "</td>
                <td class='actions'>
                    <a href='propietarios.php?editar={$row['id_propietario']}' class='btn-edit' title='Editar'>
                        <i class='fas fa-edit'></i>
                    </a>
                    <a href='propietarios.php?eliminar={$row['id_propietario']}' class='btn-delete' title='Eliminar' onclick='return confirm(\"¿Estás seguro?\")'>
                        <i class='fas fa-trash-alt'></i>
                    </a>
                </td>
            </tr>";
    }
    echo ob_get_clean();
    exit();
}


$editar = null;
if (isset($_GET['editar'])) {
    $id = intval($_GET['editar']);
    $query = "SELECT * FROM propietario WHERE id_propietario = $id";
    $result = $conexion->query($query);
    $editar = $result->fetch_assoc();
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Gestión de Propietarios</title>
    <link rel="icon" type="image/png" href="img/logo1.png?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="css/estilos.css">
</head>

<body>
    <div class="wrapper">

        <header>
            <nav class="menu-container">
                <div class="menu-logo">
                    <a href="index.php" style="text-decoration: none; color: inherit;">Gestión de Propietarios</a>
                </div>
                <div class="menu-dropdown">
                    <button class="menu-button" onclick="toggleMenu()">☰ Menú</button>
                    <ul class="menu-list" id="submenu">
                        <li><a href="index.php">Inicio</a></li>
                        <li><a href="registro_llave.php">Registrar llave</a></li>
                        <li class="menu-divider"></li>
                        <li class="active"><a href="propietarios.php">Propietarios</a></li>
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
            <!-- <h1>Gestión de Propietarios</h1> -->
            <div class="container">
                <div class="form-container">
                    <h2><?= $editar ? 'Editar Propietario' : 'Agregar Nuevo Propietario' ?></h2>
                    <form method="POST">
                        <?php if ($editar): ?>
                            <input type="hidden" name="id" value="<?= $editar['id_propietario'] ?>">
                        <?php endif; ?>

                        <div class="form-group">
                            <label for="nombre">Nombre del Propietario:</label>
                            <input type="text" id="nombre" name="nombre" required
                                value="<?= $editar ? htmlspecialchars($editar['nombre_propietario']) : '' ?>">
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <?= $editar ? 'Actualizar' : 'Guardar' ?>
                        </button>

                        <?php if ($editar): ?>
                            <a href="propietarios.php" class="btn btn-secondary">Cancelar</a>
                        <?php endif; ?>
                    </form>
                </div>

                <div class="table-container">
                    <h2>Lista de Propietarios</h2>
                    <input type="text" id="filtroPropietario" placeholder="Filtrar por nombre..." class="filtro-input" onkeyup="filtrarPropietarios()">
                    <table class="table-data">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nombre</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="tabla-propietarios">
                            <?php while ($row = $propietarios->fetch_assoc()): ?>
                                <tr>
                                    <td><?= $row['id_propietario'] ?></td>
                                    <td><?= htmlspecialchars($row['nombre_propietario']) ?></td>
                                    <td class="actions">
                                        <a href="propietarios.php?editar=<?= $row['id_propietario'] ?>" class="btn-edit" title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="propietarios.php?eliminar=<?= $row['id_propietario'] ?>" class="btn-delete" title="Eliminar" onclick="return confirm('¿Estás seguro?')">
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

            function filtrarPropietarios() {
                const filtro = document.getElementById('filtroPropietario').value;

                const xhr = new XMLHttpRequest();
                xhr.open('GET', 'propietarios.php?filtro=' + encodeURIComponent(filtro), true);
                xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
                xhr.onload = function() {
                    if (xhr.status === 200) {
                        document.getElementById('tabla-propietarios').innerHTML = xhr.responseText;
                    }
                };
                xhr.send();
            }
        </script>
    </div>


</body>

</html>