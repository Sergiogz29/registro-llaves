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
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <link rel="icon" type="image/png" href="img/logo1.png?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/estilos_login.css?v=<?php echo time(); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>

<body>
    <div class="login-layout">
        <aside class="login-sidebar">
            <div class="sidebar-content">
                <div class="logo-section">
                    <img src="img/logo.png" alt="Logo" class="sidebar-logo">
                    <div class="logo-text">
                        <div class="company-name">IDP Gestión de Inmuebles</div>
                        <div class="company-subtitle">Inventario de llaves</div>
                    </div>
                </div>
                <p class="sidebar-description">Accede con tu usuario para gestionar el inventario.</p>
            </div>
        </aside>

        <main class="login-main">
            <div class="login-form-container">
                <h2 class="login-title">Iniciar sesión</h2>
                <form class="login-form" action="procesar_login.php" method="POST">
                    <div class="form-group">
                        <label class="form-label">Usuario</label>
                        <input type="text" name="usuario" required class="form-input" placeholder="Usuario">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Contraseña</label>
                        <div class="password-container">
                            <input id="password" type="password" name="contrasena" required class="form-input" placeholder="Contraseña">
                            <span id="togglePassword" class="password-toggle">
                                🔒
                            </span>
                        </div>
                    </div>

                    <?php if (isset($_GET["error"])): ?>
                        <p class="error-msg"><?= htmlspecialchars($_GET["error"]) ?></p>
                    <?php endif; ?>

                    <button type="submit" class="login-btn">Entrar</button>
                </form>
            </div>
        </main>
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