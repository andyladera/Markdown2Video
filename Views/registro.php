<?php
// --- Views/auth/registro.php ---
// Variables pasadas por AuthController->showRegisterForm():
// - $base_url
// - $pageTitle
// - $csrf_token
// - $error_message (opcional)
// - $formData (opcional, para repoblar el formulario)

$base_url = $base_url ?? '';
$pageTitle = $pageTitle ?? 'Registro de Usuario';
$csrf_token = $csrf_token ?? ''; // Viene de showRegisterForm con nombre específico (ej. csrf_token_register)
$error_message = $error_message ?? null;
// $formData = $formData ?? []; // Para repoblar, ej. htmlspecialchars($formData['username'] ?? '', ENT_QUOTES, 'UTF-8')
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8'); ?></title>
    <!-- Ruta CORRECTA al CSS del registro -->
    <link rel="stylesheet" href="<?php echo htmlspecialchars($base_url, ENT_QUOTES, 'UTF-8'); ?>/public/css/registro.css">
    <!-- Puedes incluir header.css si el header se usa aquí también -->
    <link rel="stylesheet" href="<?php echo htmlspecialchars($base_url, ENT_QUOTES, 'UTF-8'); ?>/public/css/header.css">
    <style> /* Estilos para mensajes (igual que en login.php) */
        .message { padding: 10px; margin-bottom: 15px; border-radius: 4px; }
        .error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
    </style>
</head>
<body>
    <?php
        // Incluir el header si esta página lo usa
        // if (defined('VIEWS_PATH') && file_exists(VIEWS_PATH . 'header.php')) {
        //     include VIEWS_PATH . 'header.php';
        // }
    ?>
    <div class="login-container"> <!-- Puedes renombrar esta clase a register-container en tu CSS si prefieres -->
        <div class="login-form">    <!-- Puedes renombrar esta clase a register-form -->
            
            <?php if ($error_message): ?>
                <p class="message error"><?php echo $error_message; // Ya debería estar escapado o ser seguro si viene de tus mensajes ?></p>
            <?php endif; ?>

            <!-- El action ahora apunta a la ruta limpia que procesa el registro -->
            <form class="login-form" action="<?php echo htmlspecialchars($base_url, ENT_QUOTES, 'UTF-8'); ?>/auth/register" method="POST">
                <!-- Token CSRF específico para el registro -->
                <input type="hidden" name="csrf_token_register" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
                
                <div class="input-group">
                    <h2>Regístrate</h2>
                    <!-- Campo Nombre de Usuario (Ejemplo) -->
                    <label for="username">Nombre de Usuario</label>
                    <input type="text" id="username" name="username" placeholder="Elige un nombre de usuario" required 
                           value="<?php /* echo htmlspecialchars($formData['username'] ?? '', ENT_QUOTES, 'UTF-8'); */ ?>">

                    <label for="email">Correo Electrónico</label>
                    <input type="email" id="email" name="email" placeholder="tu@correo.com" required
                           value="<?php /* echo htmlspecialchars($formData['email'] ?? '', ENT_QUOTES, 'UTF-8'); */ ?>">
                </div>
                <div class="input-group">
                    <label for="password">Contraseña</label>
                    <input type="password" id="password" name="password" placeholder="Crea una contraseña" required>
                </div>
                <div class="input-group">
                    <label for="password_confirm">Confirmar Contraseña</label>
                    <input type="password" id="password_confirm" name="password_confirm" placeholder="Confirma tu contraseña" required>
                </div>
                <button type="submit">Registrarse Ahora</button>
                <!-- Enlace para ir al login -->
                <a href="<?php echo htmlspecialchars($base_url, ENT_QUOTES, 'UTF-8'); ?>/auth/login">¿Ya tienes una cuenta? Inicia sesión</a>
            </form>
        </div>
        <!-- Podrías tener una imagen diferente para la página de registro -->
        <!-- <img class="register-image" src="..." alt="Imagen de Registro"> -->
    </div>
</body>
</html>