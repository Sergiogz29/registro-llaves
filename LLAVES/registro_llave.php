<?php
session_start();
if (!isset($_SESSION["s_usuario"])) {
    header("Location: login.php");
    exit();
}

$conexion = new mysqli("localhost", "root", "", "registro_llaves");
$conexion->set_charset("utf8");

// Obtener datos
$propietarios = $conexion->query("SELECT * FROM propietario ORDER BY nombre_propietario ASC");
$poblaciones = $conexion->query("SELECT * FROM poblacion ORDER BY nombre_poblacion ASC");
$ubicaciones = $conexion->query("SELECT * FROM ubicacion ORDER BY nombre_ubicacion ASC");
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Registrar Nueva Llave</title>
    <link rel="stylesheet" href="css/estilos.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

</head>

<body>
    <div class="wrapper">
        <header class="header">
            <div class="menu-container">
                <div class="menu-logo">
                    <a href="index.php" style="text-decoration: none; color: inherit;">Registrar Nueva Llave</a>
                </div>
                <div class="menu-dropdown">
                    <button class="menu-button" onclick="toggleMenu()">☰ Menú</button>
                    <ul class="menu-list" id="submenu">
                        <li><a href="index.php">Inicio</a></li>
                        <li class="active"><a href="registro_llave.php">Registrar llave</a></li>
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
            </div>
        </header>

        <main>
            <form action="procesar_registro_llave.php" method="POST" class="formulario">
                <div class="form-grid">
                    <!-- Sección Propietarios -->
                    <div class="form-section">
                        <h3>Propietarios</h3>
                        <div class="select-container">
                            <select name="id_propietario" id="id_propietario" class="select2" required>
                                <option value="">-- Seleccionar --</option>
                                <?php while ($row = $propietarios->fetch_assoc()): ?>
                                    <option value="<?= $row['id_propietario'] ?>" data-nombre="<?= htmlspecialchars($row['nombre_propietario']) ?>">
                                        <?= $row['nombre_propietario'] ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                            <button type="button" class="btn-accion btn-agregar" onclick="agregarEntidad('propietario')">
                                + Nuevo
                            </button>
                        </div>
                        <div id="seleccion-propietario" class="registro-seleccionado" style="display: none;"></div>
                    </div>

                    <!-- Sección Poblaciones -->
                    <div class="form-section">
                        <h3>Poblaciones</h3>
                        <div class="select-container">
                            <select name="id_poblacion" id="id_poblacion" class="select2">
                                <option value="">-- Seleccionar --</option>
                                <?php while ($row = $poblaciones->fetch_assoc()): ?>
                                    <option value="<?= $row['id_poblacion'] ?>" data-nombre="<?= htmlspecialchars($row['nombre_poblacion']) ?>">
                                        <?= $row['nombre_poblacion'] ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                            <button type="button" class="btn-accion btn-agregar" onclick="agregarEntidad('poblacion')">
                                + Nuevo
                            </button>
                        </div>
                        <div id="seleccion-poblacion" class="registro-seleccionado" style="display: none;"></div>
                    </div>

                    <!-- Sección Ubicaciones -->
                    <div class="form-section">
                        <h3>Ubicaciones</h3>
                        <div class="select-container">
                            <select name="id_ubicacion" id="id_ubicacion" class="select2" required>
                                <option value="">-- Seleccionar --</option>
                                <?php while ($row = $ubicaciones->fetch_assoc()): ?>
                                    <option value="<?= $row['id_ubicacion'] ?>" data-nombre="<?= htmlspecialchars($row['nombre_ubicacion']) ?>">
                                        <?= $row['nombre_ubicacion'] ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                            <button type="button" class="btn-accion btn-agregar" onclick="agregarEntidad('ubicacion')">
                                + Nuevo
                            </button>
                        </div>
                        <div id="seleccion-ubicacion" class="registro-seleccionado" style="display: none;"></div>
                    </div>
                </div>

                <div class="campos">
                    <input type="text" name="codigo_principal" placeholder="Código Principal *" required>
                    <input type="text" name="codigo_secundario" placeholder="Código Secundario">
                    <input type="text" name="direccion" placeholder="Dirección" required>
                    <input type="date" name="fecha_recepcion" required>
                    <div style="display:flex; align-items:center; gap:10px;">
                        <label style="display:flex; align-items:center; gap:6px;">
                            <input type="checkbox" name="tiene_alarma" id="tiene_alarma">
                            Tiene alarma
                        </label>
                        <input type="text" name="codigo_alarma" id="codigo_alarma" placeholder="Código de alarma" disabled>
                        <label style="display:flex; align-items:center; gap:6px; margin-left:12px;">
                            <input type="checkbox" name="baja" id="baja">
                            Baja
                        </label>
                    </div>
                    <textarea name="observaciones" placeholder="Observaciones" rows="3"></textarea>
                    <button type="submit" class="btn-guardar">Guardar Llave</button>
                </div>
            </form>
        </main>

        <footer>
            © <?= date("Y") ?> Registro de Llaves
        </footer>

        <!-- jQuery y Select2 JS -->
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
        <script>
            // Inicializar Select2
            $(document).ready(function() {
                $('.select2').select2({
                    placeholder: "-- Seleccionar --",
                    allowClear: false,
                    width: '100%',
                    language: {
                        noResults: function() {
                            return "No se encontraron resultados";
                        }
                    }
                });

                // Evento cuando se selecciona un elemento
                $('.select2').on('select2:select', function(e) {
                    const tipo = this.id.replace('id_', '');
                    mostrarSeleccionado(tipo);
                });
            });

            // Función para mostrar el elemento seleccionado
            function mostrarSeleccionado(tipo) {
                const select = document.getElementById(`id_${tipo}`);
                const contenedor = document.getElementById(`seleccion-${tipo}`);
                const selectedOption = select.options[select.selectedIndex];

                if (select.value) {
                    const nombre = selectedOption.getAttribute('data-nombre');
                    contenedor.innerHTML = `
            <span>${nombre}</span>
            <div class="botones-accion">
                <button class="btn-editar" onclick="editarEntidad('${tipo}', ${select.value}, '${nombre.replace(/'/g, "\\'")}')">
                    Editar
                </button>
                <button class="btn-eliminar" onclick="eliminarEntidad('${tipo}', ${select.value})">
                    Eliminar
                </button>
            </div>
        `;
                    contenedor.style.display = 'flex';
                } else {
                    contenedor.style.display = 'none';
                }
            }

            // Función para agregar elementos
            function agregarEntidad(tipo) {
                const nombre = prompt(`Ingrese nuevo(a) ${tipo}:`);
                if (nombre) {
                    fetch(`procesar_entidades.php?tipo=${tipo}&accion=agregar&valor=${encodeURIComponent(nombre)}`)
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                // Actualizar el select
                                const select = $(`#id_${tipo}`);
                                const newOption = new Option(nombre, data.id, true, true);
                                newOption.setAttribute('data-nombre', nombre);
                                select.append(newOption).trigger('change');

                                // Mostrar el nuevo elemento
                                mostrarSeleccionado(tipo);
                            }
                        });
                }
            }

            // Función para eliminar elementos
            function eliminarEntidad(tipo, id) {
                if (confirm('¿Seguro que deseas eliminar este elemento?')) {
                    fetch(`procesar_entidades.php?tipo=${tipo}&accion=eliminar&id=${id}`)
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                $(`#id_${tipo} option[value="${id}"]`).remove();
                                document.getElementById(`seleccion-${tipo}`).style.display = 'none';
                            }
                        });
                }
            }

            // Función para editar elementos
            function editarEntidad(tipo, id, nombreActual) {
                const nuevoNombre = prompt(`Editar ${tipo}:`, nombreActual);
                if (nuevoNombre && nuevoNombre !== nombreActual) {
                    fetch(`procesar_entidades.php?tipo=${tipo}&accion=editar&id=${id}&valor=${encodeURIComponent(nuevoNombre)}`)
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                // Actualizar el select
                                $(`#id_${tipo} option[value="${id}"]`).text(nuevoNombre).attr('data-nombre', nuevoNombre);
                                // Actualizar el elemento mostrado
                                mostrarSeleccionado(tipo);
                            }
                        });
                }
            }
        </script>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const chk = document.getElementById('tiene_alarma');
                const inputCodigo = document.getElementById('codigo_alarma');
                function syncAlarma() {
                    inputCodigo.disabled = !chk.checked;
                    if (!chk.checked) inputCodigo.value = '';
                }
                chk.addEventListener('change', syncAlarma);
                syncAlarma();
            });
        </script>
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
        </script>

    </div>
</body>

</html>