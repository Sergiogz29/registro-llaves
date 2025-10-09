<?php
session_start();
include 'conexion.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $usuario = $_POST['usuario'];
    $contrasena = $_POST['contrasena'];

    $stmt = $conexion->prepare("SELECT * FROM usuarios WHERE nombre = ?");
    $stmt->bind_param("s", $usuario);
    $stmt->execute();
    $result = $stmt->get_result();
    $usuario_db = $result->fetch_assoc();

    if ($usuario_db && password_verify($contrasena, $usuario_db['contrasena'])) {
        $_SESSION['s_usuario'] = $usuario_db['nombre'];
        $_SESSION['rol'] = $usuario_db['rol']; // guardar el rol en sesión
        $_SESSION['es_admin'] = ($usuario_db['rol'] === 'admin'); // verdadero si el rol es 'admin'

        header("Location: index.php");
        exit();
    } else {
        $error_message = "Usuario o contraseña incorrectos";
        header("Location: login.php?error=" . urlencode($error_message));
        exit();
    }
} else {
    header("Location: login.php");
    exit();
}
