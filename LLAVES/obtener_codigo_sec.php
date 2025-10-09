<?php
header('Content-Type: application/json');
$conexion = new mysqli("localhost", "root", "", "registro_llaves");

if ($conexion->connect_error) {
    echo json_encode(["error" => "Error de conexión"]);
    exit();
}

$codigo_principal = $conexion->real_escape_string($_GET["codigo_principal"]);
$query = "SELECT codigo_secundario FROM llaves WHERE codigo_principal = '$codigo_principal'";
$result = $conexion->query($query);

if ($result && $row = $result->fetch_assoc()) {
    echo json_encode(["codigo_secundario" => $row["codigo_secundario"]]);
} else {
    echo json_encode(["codigo_secundario" => ""]);
}

$conexion->close();
