<?php
session_start();
include 'conexion.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $usuario = trim($_POST['usuario']);
    $contrasena = $_POST['contrasena'];

    // Validar campos vacíos
    if (empty($usuario) || empty($contrasena)) {
        header("Location: registro_usuario.php?error=Campos obligatorios");
        exit();
    }

    // Verificar si el usuario ya existe
    $stmt = $conexion->prepare("SELECT id FROM usuarios WHERE nombre = ?");
    $stmt->bind_param("s", $usuario);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        header("Location: registro_usuario.php?error=El usuario ya existe");
        exit();
    }

    // Generar hash de la contraseña
    $hash = password_hash($contrasena, PASSWORD_DEFAULT);

    // Insertar nuevo usuario
    $stmt = $conexion->prepare("INSERT INTO usuarios (nombre, contrasena) VALUES (?, ?)");
    $stmt->bind_param("ss", $usuario, $hash);

    if ($stmt->execute()) {
        header("Location: login.php?exito=Registro exitoso. Inicia sesión");
    } else {
        header("Location: registro_usuario.php?error=Error al registrar");
    }
} else {
    header("Location: registro_usuario.php");
}
?>