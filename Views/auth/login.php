<?php
// Este archivo es una VISTA. No debería iniciar sesiones ni tener lógica compleja.
// Se asume que las siguientes variables son pasadas desde AuthController->showLoginForm():
// - $base_url (string): La URL base de tu aplicación (ej. "/mi_proyecto" o "")
// - $csrf_token (string): El token CSRF para el formulario.
// - $error_message (string|null): Mensaje de error de la sesión.
// - $success_message (string|null): Mensaje de éxito de la sesión.

// Asegurarse de que las variables existan para evitar notices, aunque el controlador debería pasarlas.
$base_url = $base_url ?? ''; // Definida en index.php y pasada por el controlador
$csrf_token = $csrf_token ?? ''; // Pasada por el controlador
$error_message = $error_message ?? null; // Pasada por el controlador
$success_message = $success_message ?? null; // Pasada por el controlador
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- 
        Ruta al CSS usando BASE_URL. 
        Asegúrate que tu directorio 'public' sea accesible y .htaccess no lo bloquee para CSS/JS.
        Si tu `index.php` y `.htaccess` están en la raíz, y `public` es un subdirectorio,
        la URL sería $base_url . '/public/css/login.css'
        Si `public` es tu web root (y `index.php` está dentro de `public`),
        entonces sería $base_url . '/css/login.css' (asumiendo que css está en public/css/)
        
        Voy a asumir que 'public' es el directorio accesible desde la web y contiene 'css', 'js', 'index.php'.
        Si `index.php` está en la raíz y `public` es un subdirectorio para assets, entonces
        la URL de CSS sería `<?php echo htmlspecialchars($base_url, ENT_QUOTES, 'UTF-8'); ?>/public/css/login.css`
        
        Viendo tu estructura original, 'public' está al mismo nivel que 'index.php'.
        Entonces, los assets en 'public' son accedidos directamente vía /public/css/...
        El BASE_URL te lleva a la raíz donde está index.php.
    -->
    <link rel="stylesheet" href="<?php echo htmlspecialchars($base_url, ENT_QUOTES, 'UTF-8'); ?>/public/css/login.css">
    <title>Iniciar Sesión</title>
    <style>
        /* Estilos básicos para los mensajes, puedes mejorarlos o ponerlos en tu CSS */
        .message { padding: 10px; margin-bottom: 15px; border-radius: 4px; }
        .error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-form">
            <!-- Mostrar mensajes de error o éxito -->
            <?php if ($error_message): ?>
                <p class="message error"><?php echo htmlspecialchars($error_message, ENT_QUOTES, 'UTF-8'); ?></p>
            <?php endif; ?>
            <?php if ($success_message): ?>
                <p class="message success"><?php echo htmlspecialchars($success_message, ENT_QUOTES, 'UTF-8'); ?></p>
            <?php endif; ?>

            <!-- 
                El 'action' del formulario ahora apunta a una URL "limpia" que tu router (index.php)
                manejará para llamar a AuthController->processLogin().
                Ejemplo: /auth/processlogin o simplemente /login si tu router maneja POST a /login.
                Ajusta esta ruta según cómo definas tu ruteo en index.php.
            -->
            <form class="login-form" action="<?php echo htmlspecialchars($base_url, ENT_QUOTES, 'UTF-8'); ?>/auth/processlogin" method="POST">
                <!-- Campo oculto para el token CSRF -->
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
                
                <!-- El campo 'action' hidden ya no es necesario si la URL define la acción -->
                <!-- <input type="hidden" name="action" value="login"> -->
                
                <div class="input-group">
                    <h2>INICIAR SESIÓN</h2>
                    <label for="email">Correo Electrónico</label>
                    <input type="email" id="email" name="email" placeholder="tu@correo.com" required autofocus>
                </div>
                <div class="input-group">
                    <label for="password">Contraseña</label>
                    <input type="password" id="password" name="password" placeholder="Contraseña" required>
                </div>
                <button type="submit">Ingresar Ahora</button>
                <!-- 
                    El enlace de registro también apunta a una URL limpia.
                    Ejemplo: /auth/register o /register.
                    Ajusta según tu ruteo.
                -->
                <!-- En Views/auth/login.php -->
                <a href="<?php echo htmlspecialchars($base_url, ENT_QUOTES, 'UTF-8'); ?>/auth/register">¿No tienes una cuenta? Regístrate</a>
            </form>
        </div>
        <!-- 
            Ruta a la imagen también con BASE_URL.
            Asumiendo que 'Assets' está al mismo nivel que 'index.php'.
        -->
        <img class="login-image" src="<?php echo htmlspecialchars($base_url, ENT_QUOTES, 'UTF-8'); ?>/Assets/imagen/logo.png" alt="Imagen de Login">
    </div>
</body>
</html>