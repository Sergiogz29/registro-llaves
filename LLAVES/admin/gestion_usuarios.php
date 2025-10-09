<?php

session_start();
if (!isset($_SESSION["s_usuario"]) || $_SESSION["rol"] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

$conexion = new mysqli("localhost", "root", "", "registro_llaves");
$conexion->set_charset("utf8");

// Comprobar si la conexión es correcta
if ($conexion->connect_error) {
    die("Conexión fallida: " . $conexion->connect_error);
}

// Procesar eliminación
if (isset($_GET['eliminar'])) {
    $id = intval($_GET['eliminar']);
    if ($conexion->query("DELETE FROM usuarios WHERE id = $id") === TRUE) {
        // Redirigir a la misma página después de la eliminación
        header("Location: gestion_usuarios.php");
        exit();
    } else {
        echo "Error al eliminar el usuario: " . $conexion->error;
    }
}

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre = trim($_POST['nombre']);
    $rol = $_POST['rol'];
    $error = '';

    // Validar campos
    if (empty($nombre)) {
        $error = "El nombre de usuario es requerido";
    }

    // Si es nuevo usuario o se está cambiando la contraseña
    $cambiar_password = !isset($_POST['id']) || !empty($_POST['password']);

    if ($cambiar_password) {
        if (empty($_POST['password'])) {
            $error = "La contraseña es requerida para nuevos usuarios";
        } elseif (strlen($_POST['password']) < 4) {
            $error = "La contraseña debe tener al menos 4 caracteres";
        } elseif ($_POST['password'] !== $_POST['confirm_password']) {
            $error = "Las contraseñas no coinciden";
        }
    }

    if (empty($error)) {
        if (isset($_POST['id'])) {
            // Actualización de usuario
            $id = intval($_POST['id']);

            if ($cambiar_password) {
                // Actualizar con nueva contraseña
                $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                $query = $conexion->prepare("UPDATE usuarios SET nombre = ?, contrasena = ?, rol = ? WHERE id = ?");
                $query->bind_param("sssi", $nombre, $password, $rol, $id);
            } else {
                // Actualizar sin cambiar contraseña
                $query = $conexion->prepare("UPDATE usuarios SET nombre = ?, rol = ? WHERE id = ?");
                $query->bind_param("ssi", $nombre, $rol, $id);
            }
        } else {
            // Creación de nuevo usuario
            $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $query = $conexion->prepare("INSERT INTO usuarios (nombre, contrasena, rol) VALUES (?, ?, ?)");
            $query->bind_param("sss", $nombre, $password, $rol);
        }

        if ($query->execute()) {
            header("Location: gestion_usuarios.php");
            exit();
        } else {
            $error = "Error al procesar el formulario: " . $query->error;
        }
    }

    if (!empty($error)) {
        echo "<script>alert('$error');</script>";
    }
}

// Obtener datos para editar
$editar = null;
if (isset($_GET['editar'])) {
    $id = intval($_GET['editar']);
    $result = $conexion->query("SELECT * FROM usuarios WHERE id = $id");
    $editar = $result->fetch_assoc();
}

// Obtener todos los usuarios
$usuarios = $conexion->query("SELECT * FROM usuarios ORDER BY nombre");
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Gestión de Usuarios</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="../css/estilos.css">

</head>

<body>
    <div class="wrapper">

        <header>
            <nav class="menu-container">
                <a href="../index.php" class="menu-logo">Gestión de Usuarios</a>
                <div class="menu-dropdown">
                    <button class="menu-button" onclick="toggleMenu()">☰ Menú</button>
                    <ul class="menu-list" id="submenu">
                        <li><a href="../index.php">Inicio</a></li>
                        <li><a href="../registro_llave.php">Registrar llave</a></li>
                        <li class="menu-divider"></li>
                        <li><a href="../propietarios.php">Propietarios</a></li>
                        <li><a href="../poblaciones.php">Poblaciones</a></li>
                        <li><a href="../ubicaciones.php">Ubicaciones</a></li>
                        <li class="active"><a href="gestion_usuarios.php">Usuarios</a></li>
                        <li class="menu-divider"></li>
                        <li><a href="../logout.php" class="logout-link">Cerrar sesión</a></li>
                    </ul>
                </div>
            </nav>
        </header>

        <main>
            <div class="container">
                <div class="form-container">
                    <h2><?= $editar ? 'Editar Usuario' : 'Agregar Nuevo Usuario' ?></h2>
                    <form method="POST">
                        <?php if ($editar): ?>
                            <input type="hidden" name="id" value="<?= $editar['id'] ?>">
                        <?php endif; ?>

                        <div class="form-group">
                            <label for="nombre">Nombre del Usuario:</label>
                            <input type="text" id="nombre" name="nombre" required
                                value="<?= $editar ? htmlspecialchars($editar['nombre']) : '' ?>">
                        </div>

                        <?php if (!$editar): ?>
                            <!-- Campos de contraseña para nuevo usuario -->
                            <div class="form-group">
                                <label for="password">Contraseña:</label>
                                <div class="input-icon-wrapper">
                                    <input type="password" id="password" name="password" required>
                                    <span class="toggle-icon hidden-icon" onclick="togglePassword('password', this)">🔒</span>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="confirm_password">Confirmar Contraseña:</label>
                                <div class="input-icon-wrapper">
                                    <input type="password" id="confirm_password" name="confirm_password" required>
                                    <span class="toggle-icon hidden-icon" onclick="togglePassword('confirm_password', this)">🔒</span>
                                </div>
                            </div>
                        <?php else: ?>
                            <!-- Campos para edición -->
                            <div class="form-group">
                                <label for="password">Nueva Contraseña (dejar vacío para no cambiar):</label>
                                <div class="input-icon-wrapper">
                                    <input type="password" id="password" name="password">
                                    <span class="toggle-icon hidden-icon" onclick="togglePassword('password', this)">🔒</span>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="confirm_password">Confirmar Nueva Contraseña:</label>
                                <div class="input-icon-wrapper">
                                    <input type="password" id="confirm_password" name="confirm_password">
                                    <span class="toggle-icon hidden-icon" onclick="togglePassword('confirm_password', this)">🔒</span>
                                </div>
                            </div>
                        <?php endif; ?>

                        <div class="form-group">
                            <label for="rol">Rol:</label>
                            <select name="rol" id="rol" required class="form-group-select">
                                <option value="usuario" <?= $editar && $editar['rol'] === 'usuario' ? 'selected' : '' ?>>Usuario</option>
                                <option value="admin" <?= $editar && $editar['rol'] === 'admin' ? 'selected' : '' ?>>Administrador</option>
                            </select>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <?= $editar ? 'Actualizar' : 'Guardar' ?>
                        </button>

                        <?php if ($editar): ?>
                            <a href="gestion_usuarios.php" class="btn btn-secondary">Cancelar</a>
                        <?php endif; ?>
                    </form>
                </div>
                <div class="table-container">
                    <h2>Lista de Usuarios</h2>
                    <input type="text" id="filtroUsuario" placeholder="Filtrar por nombre..." class="filtro-input" onkeyup="filtrarUsuarios()">
                    <table class="table-data">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nombre</th>
                                <th>Rol</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $usuarios->fetch_assoc()): ?>
                                <tr>
                                    <td><?= $row['id'] ?></td>
                                    <td><?= htmlspecialchars($row['nombre']) ?></td>
                                    <td><?= $row['rol'] === 'admin' ? 'Administrador' : 'Usuario' ?></td>
                                    <td class="actions">
                                        <a href="gestion_usuarios.php?editar=<?= $row['id'] ?>" class="btn-edit" title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="gestion_usuarios.php?eliminar=<?= $row['id'] ?>" class="btn-delete" title="Eliminar"
                                            onclick="return confirm('¿Estás seguro que deseas eliminar este usuario?')">
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

            function filtrarUsuarios() {
                const filtro = document.getElementById('filtroUsuario').value.toLowerCase();
                const filas = document.querySelectorAll('.table-data tbody tr');

                filas.forEach(fila => {
                    const nombre = fila.cells[1].textContent.toLowerCase();
                    fila.style.display = nombre.includes(filtro) ? '' : 'none';
                });
            }
            document.addEventListener("DOMContentLoaded", function() {
                const inputs = document.querySelectorAll(".input-icon-wrapper input");

                inputs.forEach(input => {
                    const icon = input.parentElement.querySelector(".toggle-icon");

                    const toggleVisibility = () => {
                        if (input.value.trim().length > 0) {
                            icon.classList.remove("hidden-icon");
                        } else {
                            icon.classList.add("hidden-icon");
                        }
                    };

                    toggleVisibility();
                    input.addEventListener("input", toggleVisibility);
                });
            });

            function togglePassword(inputId, iconElement) {
                const input = document.getElementById(inputId);
                if (input.type === 'password') {
                    input.type = 'text';
                    iconElement.textContent = '🔓';
                } else {
                    input.type = 'password';
                    iconElement.textContent = '🔒';
                }
            }
        </script>

    </div>
</body>

</html>