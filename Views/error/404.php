<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Página no encontrada</title>
    <link rel="stylesheet" href="/public/css/error.css">
</head>
<body>
    <div class="error-container">
        <h1 class="error-code">404</h1>
        <h2 class="error-title">Página no encontrada</h2>
        <p class="error-message">
            Lo sentimos, la página que estás buscando no existe o ha sido movida.
            <br>
            Por favor, verifica la URL o regresa al inicio.
        </p>
        <a href="/" class="error-button">Volver al inicio</a>
    </div>

    <script>
        // Opcional: Registrar el error 404 en consola para debugging
        console.log('Error 404: ' + window.location.pathname);
    </script>
</body>
</html>