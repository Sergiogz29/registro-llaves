<?php
session_start();
if (isset($_SESSION["s_usuario"])) {
    header("Location: index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Registro de Usuario</title>
    <link rel="icon" type="image/png" href="img/logo1.png?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/estilos_login.css">
</head>

<body>
    <div class="container-login">
        <div class="wrap-login">
            <form class="login-form" action="procesar_registro.php" method="POST">
                <span class="login-form-title">REGISTRO</span>

                <?php if (isset($_GET["error"])): ?>
                    <p class="error-msg"><?= htmlspecialchars($_GET["error"]) ?></p>
                <?php endif; ?>

                <div class="wrap-input">
                    <input type="text" name="usuario" required class="input" placeholder="Usuario">
                    <span class="focus-efecto"></span>
                </div>

                <div class="wrap-input">
                    <input type="password" name="contrasena" required class="input" placeholder="Contraseña">
                    <span class="focus-efecto"></span>
                </div>

                <div class="wrap-input">
                    <input type="text" name="correo" class="input" placeholder="Correo (para avisos)">
                    <span class="focus-efecto"></span>
                </div>

                <div class="container-login-form-btn">
                    <button type="submit" class="login-form-btn">REGISTRARSE</button>
                </div>

                <div class="text-center">
                    <a href="login.php">¿Ya tienes cuenta? Inicia sesión</a>
                </div>
            </form>
        </div>
    </div>
</body>

</html>