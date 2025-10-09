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
    <title>Iniciar sesión</title>
    <link rel="stylesheet" href="css/estilos_login.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>

<body>
    <img src="img/logo.png" alt="Logo" class="logo">
    <div class="container-login">
        <div class="wrap-login">
            <form class="login-form" action="procesar_login.php" method="POST">
                <span class="login-form-title">LOGIN</span>

                <div class="wrap-input">
                    <input type="text" name="usuario" required class="input" placeholder="Usuario">
                    <span class="focus-efecto"></span>
                </div>

                <div class="wrap-input">
                    <input id="password" type="password" name="contrasena" required class="input" placeholder="Contraseña">
                    <span class="focus-efecto"></span>
                    <span id="togglePassword" class="hidden-icon" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); cursor: pointer; user-select: none; font-size: 18px; color: #333;">
                        🔒
                    </span>
                </div>

                <div class="container-login-form-btn">
                    <button type="submit" class="login-form-btn">CONECTAR</button>
                </div>

                <?php if (isset($_GET["error"])): ?>
                    <p class="error-msg"><?= htmlspecialchars($_GET["error"]) ?></p>
                <?php endif; ?>
            </form>
        </div>
    </div>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const togglePassword = document.querySelector("#togglePassword");
            const password = document.querySelector("#password");

            function toggleVisibility() {
                if (password.value.trim().length > 0) {
                    togglePassword.style.display = "inline";
                } else {
                    togglePassword.style.display = "none";
                }
            }

            setTimeout(toggleVisibility, 100);

            password.addEventListener("input", toggleVisibility);

            togglePassword.addEventListener("click", () => {
                if (password.type === "password") {
                    password.type = "text";
                    togglePassword.textContent = "🔓";
                } else {
                    password.type = "password";
                    togglePassword.textContent = "🔒";
                }
            });
        });
    </script>

</body>

</html>