<?php
$conexion = new mysqli("localhost", "root", "", "registro_llaves");
$conexion->set_charset("utf8");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $codigo_principal = $_POST['codigo_principal'];
    $propietario = $_POST['propietario'];
    $poblacion = $_POST['poblacion'];
    $direccion = $_POST['direccion'];
    $ubicacion = $_POST['ubicacion'];

    $query = "UPDATE llaves SET nombre_propietario = ?, nombre_poblacion = ?, direccion = ?, nombre_ubicacion = ? WHERE codigo_principal = ?";
    $stmt = $conexion->prepare($query);
    $stmt->bind_param("sssss", $propietario, $poblacion, $direccion, $ubicacion, $codigo_principal);

    if ($stmt->execute()) {
        echo "Actualización exitosa";
    } else {
        echo "Error al actualizar los datos";
    }
    $stmt->close();
}
