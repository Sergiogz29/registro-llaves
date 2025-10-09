<?php
session_start();
if (!isset($_SESSION["s_usuario"])) {
    header("Location: login.php");
    exit();
}

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Conexión a la base de datos
$conexion = new mysqli("localhost", "root", "", "registro_llaves");
if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}
$conexion->set_charset("utf8");

// Obtener ubicaciones
$ubicaciones = [];
$result_ubic = $conexion->query("SELECT id_ubicacion, nombre_ubicacion, tipo FROM ubicacion");
if ($result_ubic) {
    $ubicaciones = $result_ubic->fetch_all(MYSQLI_ASSOC);
}

// Parámetros de búsqueda
$limite = 25;
$pagina = isset($_GET['pagina']) ? max(1, intval($_GET['pagina'])) : 1;
$inicio = ($pagina - 1) * $limite;

// Parámetros de búsqueda, limpiando espacios con trim
$codigo_principal = isset($_GET['codigo_principal']) ? trim($conexion->real_escape_string($_GET['codigo_principal'])) : '';
$propietario = isset($_GET['propietario']) ? trim($conexion->real_escape_string($_GET['propietario'])) : '';
$poblacion = isset($_GET['poblacion']) ? trim($conexion->real_escape_string($_GET['poblacion'])) : '';
$ubicacion = isset($_GET['ubicacion']) ? trim($conexion->real_escape_string($_GET['ubicacion'])) : '';
$ocultar_vendidos = isset($_GET['ocultar_vendidos']) && $_GET['ocultar_vendidos'] == '1';

// Condiciones WHERE
$condiciones = [];
if ($codigo_principal) {
    $condiciones[] = "llaves.codigo_principal LIKE '%$codigo_principal%'";
}
if ($propietario) {
    $condiciones[] = "propietario.nombre_propietario LIKE '%$propietario%'";
}
if ($poblacion) {
    $condiciones[] = "poblacion.nombre_poblacion LIKE '%$poblacion%'";
}
if ($ubicacion) {
    $condiciones[] = "ubicacion.nombre_ubicacion LIKE '%$ubicacion%'";
}
if ($ocultar_vendidos) {
    $condiciones[] = "LOWER(TRIM(ubicacion.nombre_ubicacion)) NOT REGEXP 'vendid[oa]s?'";
}
$where = count($condiciones) ? "WHERE " . implode(" AND ", $condiciones) : "";

// Consulta principal
$query = "SELECT 
            llaves.*,
            propietario.nombre_propietario,
            poblacion.nombre_poblacion,
            ubicacion.nombre_ubicacion,
            ubicacion.tipo,
            ubicacion.id_ubicacion
          FROM llaves
          INNER JOIN propietario ON llaves.id_propietario = propietario.id_propietario
          INNER JOIN poblacion ON llaves.id_poblacion = poblacion.id_poblacion
          INNER JOIN ubicacion ON llaves.id_ubicacion = ubicacion.id_ubicacion
          $where
          LIMIT $inicio, $limite";

$result = $conexion->query($query);
if (!$result) {
    die("Error en consulta: " . $conexion->error);
}
$registros = $result->fetch_all(MYSQLI_ASSOC);

// Consulta para total de registros
$total_query = "SELECT COUNT(*) AS total 
                FROM llaves
                INNER JOIN propietario ON llaves.id_propietario = propietario.id_propietario
                INNER JOIN poblacion ON llaves.id_poblacion = poblacion.id_poblacion
                INNER JOIN ubicacion ON llaves.id_ubicacion = ubicacion.id_ubicacion
                $where";
$result_total = $conexion->query($total_query);
$total_registros = $result_total->fetch_assoc()['total'];
$total_paginas = ceil($total_registros / $limite);

// AJAX
if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest') {
    ob_start(); ?>
    <tbody>
        <?php foreach ($registros as $row): ?>
            <tr data-codigo-principal="<?= htmlspecialchars($row['codigo_principal']) ?>"
                data-id-propietario="<?= $row['id_propietario'] ?>"
                data-id-ubicacion="<?= $row['id_ubicacion'] ?>">
                <td><?= htmlspecialchars($row['codigo_principal']) ?></td>
                <td><?= htmlspecialchars($row['nombre_propietario']) ?></td>
                <td><?= htmlspecialchars($row['nombre_poblacion']) ?></td>
                <td class="direccion-cell"><?= htmlspecialchars($row['direccion']) ?></td>
                <td class="ubicacion-cell"><?= htmlspecialchars($row['nombre_ubicacion']) ?></td>
                <td class="fecha-recepcion-cell"><?= date('d-m-Y', strtotime($row['fecha_recepcion'])) ?></td>
                <td class="alarma-cell">
                    <?php
                        $textoAlarma = '-';
                        if (isset($row['tiene_alarma'])) {
                            $tieneAlarma = intval($row['tiene_alarma']) === 1;
                            if ($tieneAlarma) {
                                $textoAlarma = 'Sí';
                                if (!empty($row['codigo_alarma'])) {
                                    $textoAlarma .= ' (' . htmlspecialchars($row['codigo_alarma']) . ')';
                                }
                            } else {
                                $textoAlarma = 'No';
                            }
                        }
                        echo $textoAlarma;
                    ?>
                </td>
                <td class="observaciones-cell"><?= htmlspecialchars($row['observaciones'] ?? '') ?></td>
                <td class="acciones-cell">
                    <div class="acciones-contenedor">
                        <input type="checkbox" class="estado-toggle"
                            data-codigo="<?= htmlspecialchars($row['codigo_principal']) ?>"
                            data-propietario="<?= $row['id_propietario'] ?>"
                            <?= strtolower($row['tipo']) === 'interno' ? 'checked' : '' ?>
                            disabled>
                        <a class="btn-etiqueta" target="_blank"
                            href="generar_etiqueta.php?codigo_principal=<?= urlencode($row['codigo_principal']) ?>&id_propietario=<?= urlencode($row['id_propietario']) ?>"
                            title="Imprimir etiqueta">
                            <i class="fas fa-tag"></i>
                        </a>
                        <a class="btn-detalle"
                            href="movimientos_llaves.php?codigo_principal=<?= htmlspecialchars($row['codigo_principal']) ?>&id_propietario=<?= $row['id_propietario'] ?>"
                            title="Ver detalles">
                            <i class="fas fa-search"></i>
                        </a>
                        <div class="editar-cell">
                            <a class="btn-editar-llave" href="#" title="Editar">
                                <i class="fas fa-pencil-alt"></i>
                            </a>
                        </div>
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
    <?php
    $tbody = ob_get_clean();

    ob_start(); ?>
    <div class="paginacion">
        <?php
        $paginas_mostradas = 10; // Número de páginas a mostrar
        $mitad = floor($paginas_mostradas / 2);

        // Calcula el rango de páginas a mostrar
        $inicio = max(1, $pagina - $mitad);
        $fin = min($total_paginas, $inicio + $paginas_mostradas - 1);

        // Ajusta si estamos cerca del final
        if ($fin - $inicio < $paginas_mostradas - 1) {
            $inicio = max(1, $fin - $paginas_mostradas + 1);
        }

        // Construye los parámetros de filtro
        $params = [
            'codigo_principal' => $codigo_principal,
            'propietario' => $propietario,
            'poblacion' => $poblacion,
            'ubicacion' => $ubicacion,
            'ocultar_vendidos' => $ocultar_vendidos ? '1' : '0'
        ];
        $query_string = http_build_query($params);
        ?>

        <!-- Primera página y anterior -->
        <?php if ($pagina > 1): ?>
            <a href="index.php?pagina=1&<?= $query_string ?>" class="pagina" title="Primera página">&laquo;&laquo;</a>
            <a href="index.php?pagina=<?= $pagina - 1 ?>&<?= $query_string ?>" class="pagina" title="Página anterior">&laquo;</a>
        <?php endif; ?>

        <!-- Páginas numeradas -->
        <?php for ($i = $inicio; $i <= $fin; $i++): ?>
            <a href="index.php?pagina=<?= $i ?>&<?= $query_string ?>"
                class="pagina <?= $i == $pagina ? 'active' : '' ?>">
                <?= $i ?>
            </a>
        <?php endfor; ?>

        <!-- Siguiente y última página -->
        <?php if ($pagina < $total_paginas): ?>
            <a href="index.php?pagina=<?= $pagina + 1 ?>&<?= $query_string ?>" class="pagina" title="Página siguiente">&raquo;</a>
            <a href="index.php?pagina=<?= $total_paginas ?>&<?= $query_string ?>" class="pagina" title="Última página">&raquo;&raquo;</a>
        <?php endif; ?>
    </div>
<?php
    $pagination = ob_get_clean();

    echo $tbody . '<!--PAGINACION-->' . $pagination;
    exit();
}

?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Registro de Llaves</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="css/estilos.css">
    <script>
        const ubicaciones = <?= json_encode($ubicaciones) ?>;
    </script>
</head>

<body>
    <!-- Header y menú  -->
    <div class="wrapper">

        <header>
            <nav class="menu-container">
                <div class="menu-logo">Gestión de Llaves</div>
                <div class="menu-dropdown">
                    <button class="menu-button" onclick="toggleMenu()">☰ Menú</button>
                    <ul class="menu-list" id="submenu">
                        <li class="active"><a href="index.php">Inicio</a></li>
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

            <div class="filtros">
                <input id="codigo_principal" type="text" placeholder="Buscar por código principal"
                    value="<?= htmlspecialchars($codigo_principal) ?>" onkeyup="filtrar()">
                <input id="propietario" type="text" placeholder="Buscar por propietario"
                    value="<?= htmlspecialchars($propietario) ?>" onkeyup="filtrar()">
                <input id="poblacion" type="text" placeholder="Buscar por población"
                    value="<?= htmlspecialchars($poblacion) ?>" onkeyup="filtrar()">
                <input id="ubicacion" type="text" placeholder="Buscar por ubicación"
                    value="<?= htmlspecialchars($ubicacion) ?>" onkeyup="filtrar()">
                <label class="checkbox-verificado">
                    <input type="checkbox" name="ocultar_vendidos" value="1" class="checkbox-verificado-input" <?= $ocultar_vendidos ? 'checked' : '' ?>>
                    <span class="checkbox-text">Vendido</span>
                </label>
            </div>

            <div id="resultado">
                <table class="table-compact">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Propietario</th>
                            <th>Población</th>
                            <th>Dirección</th>
                            <th>Ubicación</th>
                            <th>Fecha Recepción</th>
                            <th>Alarma</th>
                            <th>Observaciones</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="tabla-datos">
                        <?php foreach ($registros as $row): ?>
                            <tr data-codigo-principal="<?= htmlspecialchars($row['codigo_principal']) ?>"
                                data-id-propietario="<?= $row['id_propietario'] ?>"
                                data-id-ubicacion="<?= $row['id_ubicacion'] ?>">
                                <td><?= $row['codigo_principal'] ?></td>
                                <td><?= $row['nombre_propietario'] ?></td>
                                <td><?= $row['nombre_poblacion'] ?></td>
                                <td class="direccion-cell"><?= $row['direccion'] ?></td>
                                <td class="ubicacion-cell"><?= $row['nombre_ubicacion'] ?></td>
                                <td class="fecha-recepcion-cell"><?= date('d-m-Y', strtotime($row['fecha_recepcion'])) ?></td>
                                <td class="alarma-cell">
                                    <?php
                                        $textoAlarma = '-';
                                        if (isset($row['tiene_alarma'])) {
                                            $tieneAlarma = intval($row['tiene_alarma']) === 1;
                                            if ($tieneAlarma) {
                                                $textoAlarma = 'Sí';
                                                if (!empty($row['codigo_alarma'])) {
                                                    $textoAlarma .= ' (' . htmlspecialchars($row['codigo_alarma']) . ')';
                                                }
                                            } else {
                                                $textoAlarma = 'No';
                                            }
                                        }
                                        echo $textoAlarma;
                                    ?>
                                </td>
                                <td class="observaciones-cell"><?= htmlspecialchars($row['observaciones'] ?? '') ?></td>
                                <td class="acciones-cell">
                                    <div class="acciones-contenedor">
                                        <input type="checkbox" class="estado-toggle"
                                            data-codigo="<?= htmlspecialchars($row['codigo_principal']) ?>"
                                            data-propietario="<?= $row['id_propietario'] ?>"
                                            <?= strtolower($row['tipo']) === 'interno' ? 'checked' : '' ?>
                                            disabled>
                                        <a class="btn-etiqueta" target="_blank"
                                            href="generar_etiqueta.php?codigo_principal=<?= urlencode($row['codigo_principal']) ?>&id_propietario=<?= urlencode($row['id_propietario']) ?>"
                                            title="Imprimir etiqueta">
                                            <i class="fas fa-tag"></i>
                                        </a>
                                        <a class="btn-detalle"
                                            href="movimientos_llaves.php?codigo_principal=<?= htmlspecialchars($row['codigo_principal']) ?>&id_propietario=<?= $row['id_propietario'] ?>"
                                            title="Ver detalles">
                                            <i class="fas fa-search"></i>
                                        </a>
                                        <div class="editar-cell">
                                            <a class="btn-editar-llave" href="#" title="Editar">
                                                <i class="fas fa-pencil-alt"></i>
                                            </a>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <!-- Paginación -->
                <div class="paginacion">
                    <?php
                    $paginas_mostradas = 10; // Número de páginas a mostrar
                    $mitad = floor($paginas_mostradas / 2);

                    // Calcula el rango de páginas a mostrar
                    $inicio = max(1, $pagina - $mitad);
                    $fin = min($total_paginas, $inicio + $paginas_mostradas - 1);

                    // Ajusta si estamos cerca del final
                    if ($fin - $inicio < $paginas_mostradas - 1) {
                        $inicio = max(1, $fin - $paginas_mostradas + 1);
                    }

                    // Construye los parámetros de filtro
                    $params = [
                        'codigo_principal' => $codigo_principal,
                        'propietario' => $propietario,
                        'poblacion' => $poblacion,
                        'ubicacion' => $ubicacion,
                        'ocultar_vendidos' => $ocultar_vendidos ? '1' : '0'
                    ];
                    $query_string = http_build_query($params);
                    ?>

                    <!-- Primera página y anterior -->
                    <?php if ($pagina > 1): ?>
                        <a href="index.php?pagina=1&<?= $query_string ?>" class="pagina" title="Primera página">&laquo;&laquo;</a>
                        <a href="index.php?pagina=<?= $pagina - 1 ?>&<?= $query_string ?>" class="pagina" title="Página anterior">&laquo;</a>
                    <?php endif; ?>

                    <!-- Páginas numeradas -->
                    <?php for ($i = $inicio; $i <= $fin; $i++): ?>
                        <a href="index.php?pagina=<?= $i ?>&<?= $query_string ?>"
                            class="pagina <?= $i == $pagina ? 'active' : '' ?>">
                            <?= $i ?>
                        </a>
                    <?php endfor; ?>

                    <!-- Siguiente y última página -->
                    <?php if ($pagina < $total_paginas): ?>
                        <a href="index.php?pagina=<?= $pagina + 1 ?>&<?= $query_string ?>" class="pagina" title="Página siguiente">&raquo;</a>
                        <a href="index.php?pagina=<?= $total_paginas ?>&<?= $query_string ?>" class="pagina" title="Última página">&raquo;&raquo;</a>
                    <?php endif; ?>
                </div>
            </div>
    </div>
    <footer>
        © <?= date("Y") ?> Registro de Llaves
    </footer>

    <script>
        // Función de filtrado
        function filtrar() {
            const params = new URLSearchParams({
                codigo_principal: document.getElementById('codigo_principal').value,
                propietario: document.getElementById('propietario').value,
                poblacion: document.getElementById('poblacion').value,
                ubicacion: document.getElementById('ubicacion').value,
                ocultar_vendidos: document.querySelector('.checkbox-verificado-input')?.checked ? 1 : 0,
                pagina: 1,
                _: new Date().getTime()
            });

            fetch('index.php?' + params.toString(), {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Cache-Control': 'no-cache'
                    }
                })
                .then(response => response.text())
                .then(data => {
                    const [tbody, pagination] = data.split('<!--PAGINACION-->');
                    const tabla = document.getElementById('tabla-datos');
                    const paginacion = document.querySelector('.paginacion');
                    const activeElem = document.activeElement;
                    let caretPos = null;
                    const filtrosIds = ['codigo_principal', 'propietario', 'poblacion', 'ubicacion'];

                    if (activeElem && filtrosIds.includes(activeElem.id)) {
                        caretPos = activeElem.selectionStart;
                    }

                    tabla.innerHTML = tbody;
                    paginacion.innerHTML = pagination;
                    tabla.offsetHeight;
                    if (activeElem && caretPos !== null) {
                        activeElem.focus();
                        activeElem.setSelectionRange(caretPos, caretPos);
                    }
                })
                .catch(error => {
                    console.error('Error en el filtrado:', error);
                });
        }

        document.getElementById('tabla-datos').addEventListener('click', function(event) {
            let boton = event.target.closest('.btn-detalle');
            if (boton) {
                window.location.href = boton.href;
            }
        });

        ['#codigo_principal', '#propietario', '#poblacion', '#ubicacion'].forEach(id => {
            const el = document.querySelector(id);
            if (el) el.addEventListener('change', filtrar);
        });

        document.addEventListener('DOMContentLoaded', function() {
            const checkbox = document.querySelector('.checkbox-verificado-input');
            const inputCodigo = document.getElementById('codigo_principal');
            const inputPropietario = document.getElementById('propietario');
            const inputPoblacion = document.getElementById('poblacion');
            const inputUbicacion = document.getElementById('ubicacion');

            const codigoPrincipal = inputCodigo.value || '';
            const propietario = inputPropietario.value || '';
            const poblacion = inputPoblacion.value || '';
            const ubicacion = inputUbicacion.value || '';
            const ocultarVendidos = checkbox.checked ? 1 : 0;

            const urlParams = new URLSearchParams(window.location.search);
            const pagina = urlParams.get('pagina') || 1;

            const params = new URLSearchParams({
                codigo_principal: codigoPrincipal,
                propietario: propietario,
                poblacion: poblacion,
                ubicacion: ubicacion,
                ocultar_vendidos: ocultarVendidos,
                pagina: pagina,
                _: new Date().getTime()
            });

            fetch('index.php?' + params.toString(), {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Cache-Control': 'no-cache'
                    }
                })
                .then(response => response.text())
                .then(data => {
                    const [tbody, pagination] = data.split('<!--PAGINACION-->');
                    const tabla = document.getElementById('tabla-datos');
                    const paginacion = document.querySelector('.paginacion');
                    const activeElem = document.activeElement;
                    let caretPos = null;
                    const filtrosIds = ['codigo_principal', 'propietario', 'poblacion', 'ubicacion'];

                    if (activeElem && filtrosIds.includes(activeElem.id)) {
                        caretPos = activeElem.selectionStart;
                    }

                    tabla.innerHTML = tbody;
                    paginacion.innerHTML = pagination;
                    tabla.offsetHeight;
                    if (activeElem && caretPos !== null) {
                        activeElem.focus();
                        activeElem.setSelectionRange(caretPos, caretPos);
                    }
                });


            checkbox.addEventListener('change', function() {
                const urlParams = new URLSearchParams(window.location.search);
                const paginaActual = urlParams.get('pagina') || 1;

                const params = new URLSearchParams({
                    codigo_principal: inputCodigo.value || '',
                    propietario: inputPropietario.value || '',
                    poblacion: inputPoblacion.value || '',
                    ubicacion: inputUbicacion.value || '',
                    ocultar_vendidos: this.checked ? 1 : 0,
                    pagina: paginaActual,
                    _: new Date().getTime()
                });

                fetch('index.php?' + params.toString(), {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Cache-Control': 'no-cache'
                        }
                    })
                    .then(response => response.text())
                    .then(data => {
                        const [tbody, pagination] = data.split('<!--PAGINACION-->');
                        const tabla = document.getElementById('tabla-datos');
                        const paginacion = document.querySelector('.paginacion');
                        const activeElem = document.activeElement;
                        const filtrosIds = ['codigo_principal', 'propietario', 'poblacion', 'ubicacion'];
                        let caretPos = null;

                        if (activeElem && filtrosIds.includes(activeElem.id)) {
                            caretPos = activeElem.selectionStart;
                        }

                        tabla.innerHTML = tbody;
                        paginacion.innerHTML = pagination;
                        tabla.offsetHeight;
                        if (activeElem && caretPos !== null) {
                            activeElem.focus();
                            activeElem.setSelectionRange(caretPos, caretPos);
                        }
                    });

            });

            [inputCodigo, inputPropietario, inputPoblacion, inputUbicacion].forEach(input => {
                input.addEventListener('input', function() {
                    const params = new URLSearchParams({
                        codigo_principal: inputCodigo.value || '',
                        propietario: inputPropietario.value || '',
                        poblacion: inputPoblacion.value || '',
                        ubicacion: inputUbicacion.value || '',
                        ocultar_vendidos: checkbox.checked ? 1 : 0,
                        pagina: 1,
                        _: new Date().getTime()
                    });

                    fetch('index.php?' + params.toString(), {
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'Cache-Control': 'no-cache'
                            }
                        })
                        .then(response => response.text())
                        .then(data => {
                            const [tbody, pagination] = data.split('<!--PAGINACION-->');
                            const tabla = document.getElementById('tabla-datos');
                            const paginacion = document.querySelector('.paginacion');
                            const activeElem = document.activeElement;
                            const filtrosIds = ['codigo_principal', 'propietario', 'poblacion', 'ubicacion'];
                            let caretPos = null;

                            if (activeElem && filtrosIds.includes(activeElem.id)) {
                                caretPos = activeElem.selectionStart;
                            }

                            tabla.innerHTML = tbody;
                            paginacion.innerHTML = pagination;
                            tabla.offsetHeight;

                            if (activeElem && caretPos !== null) {
                                activeElem.focus();
                                activeElem.setSelectionRange(caretPos, caretPos);
                            }
                        });

                });
            });

            document.addEventListener('click', function(e) {
                const link = e.target.closest('.paginacion a');
                if (link) {
                    e.preventDefault();
                    const url = new URL(link.href);
                    const params = new URLSearchParams({
                        codigo_principal: document.getElementById('codigo_principal').value || '',
                        propietario: document.getElementById('propietario').value || '',
                        poblacion: document.getElementById('poblacion').value || '',
                        ubicacion: document.getElementById('ubicacion').value || '',
                        ocultar_vendidos: document.querySelector('.checkbox-verificado-input')?.checked ? 1 : 0,
                        pagina: url.searchParams.get('pagina') || 1,
                        _: new Date().getTime()
                    });

                    fetch('index.php?' + params.toString(), {
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'Cache-Control': 'no-cache'
                            }
                        })
                        .then(response => response.text())
                        .then(data => {
                            const [tbody, pagination] = data.split('<!--PAGINACION-->');
                            const tabla = document.getElementById('tabla-datos');
                            const paginacion = document.querySelector('.paginacion');
                            const activeElem = document.activeElement;
                            const filtrosIds = ['codigo_principal', 'propietario', 'poblacion', 'ubicacion'];
                            let caretPos = null;

                            if (activeElem && filtrosIds.includes(activeElem.id)) {
                                caretPos = activeElem.selectionStart;
                            }

                            tabla.innerHTML = tbody;
                            paginacion.innerHTML = pagination;

                            tabla.offsetHeight;
                            if (activeElem && caretPos !== null) {
                                activeElem.focus();
                                activeElem.setSelectionRange(caretPos, caretPos);
                            }

                            history.pushState({}, '', 'index.php?' + params.toString());
                        });

                }
            });
            document.addEventListener('click', function(e) {
                const boton = e.target.closest('.btn-detalle');
                if (boton) {
                    e.preventDefault();
                    setTimeout(() => {
                        window.location.href = boton.href;
                    }, 0);
                }
            });


        });

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

        // Manejo de edición
        document.addEventListener('click', function(e) {
            if (e.target.closest('.btn-editar-llave')) {
                e.preventDefault();
                const row = e.target.closest('tr');
                entrarModoEdicion(row);
            }

            if (e.target.closest('.btn-actualizar')) {
                actualizarRegistro(e.target.closest('tr'));
            }

            if (e.target.closest('.btn-cancelar')) {
                cancelarEdicion(e.target.closest('tr'));
            }
        });

        function entrarModoEdicion(row) {
            const datosOriginales = {
                direccion: row.querySelector('.direccion-cell').textContent,
                ubicacion: row.querySelector('.ubicacion-cell').textContent,
                fecha: row.querySelector('.fecha-recepcion-cell').textContent,
                id_ubicacion: row.dataset.idUbicacion
            };
            row.dataset.original = JSON.stringify(datosOriginales);

            // Crear inputs
            row.querySelector('.direccion-cell').innerHTML =
                `<input type="text" class="edit-input" value="${datosOriginales.direccion}">`;

            const select = document.createElement('select');
            select.className = 'edit-select';
            ubicaciones.forEach(ubicacion => {
                const option = document.createElement('option');
                option.value = ubicacion.id_ubicacion;
                option.textContent = ubicacion.nombre_ubicacion;
                option.dataset.tipo = ubicacion.tipo;
                if (ubicacion.id_ubicacion == datosOriginales.id_ubicacion) option.selected = true;
                select.appendChild(option);
            });
            row.querySelector('.ubicacion-cell').innerHTML = '';
            row.querySelector('.ubicacion-cell').appendChild(select);

            const [dia, mes, anio] = datosOriginales.fecha.split('-');
            row.querySelector('.fecha-recepcion-cell').innerHTML =
                `<input type="date" class="edit-input" value="${anio}-${mes.padStart(2, '0')}-${dia.padStart(2, '0')}">`;

            row.querySelector('.editar-cell').innerHTML = `
                <button class="btn-actualizar">Actualizar</button>
                <button class="btn-cancelar">Cancelar</button>
            `;
        }

        async function actualizarRegistro(row) {
            const codigoPrincipal = row.dataset.codigoPrincipal;
            const idPropietario = row.dataset.idPropietario;
            const direccion = row.querySelector('.direccion-cell input').value;
            const idUbicacion = row.querySelector('.ubicacion-cell select').value;
            const fechaRecepcion = row.querySelector('.fecha-recepcion-cell input').value;

            // Validación en el cliente
            if (!codigoPrincipal || !idPropietario || !direccion || !idUbicacion || !fechaRecepcion) {
                alert('Por favor complete todos los campos requeridos');
                return;
            }

            try {
                const response = await fetch('actualizar_llave.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: new URLSearchParams({
                        codigo_principal: codigoPrincipal,
                        id_propietario: idPropietario,
                        direccion: direccion,
                        id_ubicacion: idUbicacion,
                        fecha_recepcion: fechaRecepcion
                    })
                });

                // Capturar respuesta
                const text = await response.text();
                console.log("Respuesta del servidor:", text);
                const data = JSON.parse(text);
                if (!response.ok || !data.success) {
                    throw new Error(data.message || 'Error en la actualización');
                }

                // Actualizar la interfaz
                const select = row.querySelector('.ubicacion-cell select');
                const selectedOption = select.options[select.selectedIndex];
                const nombreUbicacion = selectedOption.textContent;
                const tipoUbicacion = selectedOption.dataset.tipo;

                row.querySelector('.direccion-cell').textContent = direccion;
                row.querySelector('.ubicacion-cell').textContent = nombreUbicacion;

                // Formatear fecha correctamente
                const fechaFormateada = new Date(fechaRecepcion).toLocaleDateString('es-ES', {
                    day: '2-digit',
                    month: '2-digit',
                    year: 'numeric'
                }).replace(/\//g, '-');

                row.querySelector('.fecha-recepcion-cell').textContent = fechaFormateada;

                // Actualizar el checkbox según el tipo de ubicación
                const checkbox = row.querySelector('.estado-toggle');
                checkbox.checked = tipoUbicacion.toLowerCase() === 'interno';

                row.dataset.idUbicacion = idUbicacion;

                row.querySelector('.editar-cell').innerHTML = `
            <a class="btn-editar-llave" href="#" title="Editar">
                <i class="fas fa-pencil-alt"></i>
            </a>
        `;

                alert('Registro actualizado correctamente');
            } catch (error) {
                console.error('Error:', error);
                alert('Error al actualizar: ' + error.message);
                cancelarEdicion(row);
            }
        }

        function cancelarEdicion(row) {
            const original = JSON.parse(row.dataset.original);
            row.querySelector('.direccion-cell').textContent = original.direccion;
            row.querySelector('.ubicacion-cell').textContent = original.ubicacion;
            row.querySelector('.fecha-recepcion-cell').textContent = original.fecha;
            row.querySelector('.editar-cell').innerHTML = `
                <a class="btn-editar-llave" href="#" title="Editar">
                    <i class="fas fa-pencil-alt"></i>
                </a>
            `;
        }
    </script>
</body>

</html>