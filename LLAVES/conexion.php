<?php
// conexion.php
$conexion = new mysqli("localhost", "root", "", "registro_llaves");
if ($conexion->connect_error) {
    // cerrar conexión
    $conexion = null;
    exit();
}
$conexion->set_charset("utf8");
