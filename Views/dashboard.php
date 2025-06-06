<?php
// --- Views/dashboard.php ---
// Esta vista asume que las siguientes variables son pasadas desde DashboardController->index():
// - $base_url (string)
// - $pageTitle (string)
// - $username (string) - El nombre del usuario logueado
// - $historicalData (array) - Placeholder, puedes pasar datos reales
// - $welcomeMessage (string) - Ya no es tan necesaria si usas el header

// Asegurarse de que las variables esperadas existan con valores por defecto
$base_url = $base_url ?? '';
$pageTitle = $pageTitle ?? 'Dashboard'; // El controlador debería establecer esto
// $username ya debería estar disponible en la sesión si el header lo usa.
// Si no, el controlador debe pasarla.
// $historicalData = $historicalData ?? []; // El controlador debería pasar esto
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8'); ?></title>
    
    <!-- CSS específico para el Dashboard -->
    <link rel="stylesheet" href="<?php echo htmlspecialchars($base_url, ENT_QUOTES, 'UTF-8'); ?>/public/css/dashboard.css">
    
    <!-- CSS específico para el Header -->
    <link rel="stylesheet" href="<?php echo htmlspecialchars($base_url, ENT_QUOTES, 'UTF-8'); ?>/public/css/header.css">
</head>
<body>

    <?php
        // Incluir el header.php.
        // VIEWS_PATH es una constante global definida en index.php
        // Asumiendo que header.php está en la raíz de Views/
        if (defined('VIEWS_PATH') && file_exists(VIEWS_PATH . 'header.php')) {
            include VIEWS_PATH . 'header.php';
        } else {
            echo "<!-- Advertencia: No se encontró Views/header.php. Usando header básico. -->";
            echo "<header style='background-color: #333; color: white; padding: 10px 20px; display: flex; justify-content: space-between; align-items: center;'>";
            echo "  <div class='navbar-left'><h1>" . htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') . "</h1></div>";
            echo "  <div class='navbar-right'>";
            if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
                echo "    <span>Bienvenido, " . htmlspecialchars($_SESSION['username'] ?? 'Usuario', ENT_QUOTES, 'UTF-8') . "</span> | ";
                echo "    <a href='" . htmlspecialchars($base_url, ENT_QUOTES, 'UTF-8') . "/auth/logout' style='color: white;'>Cerrar Sesión</a>";
            }
            echo "  </div>";
            echo "</header>";
        }
    ?>

    <div class="dashboard-container">
        <!-- Botón para Crear (antes historical-btn) -->
        <div class="historical-btn"> <!-- Mantengo la clase si tu CSS depende de ella -->
            <!--
                El enlace ahora apunta a una URL limpia que tu router debe manejar.
                Ej. '/markdown/create' o '/create-markdown'
                Ajusta esta URL según tu sistema de ruteo.
            -->
            <a href="<?php echo htmlspecialchars($base_url, ENT_QUOTES, 'UTF-8'); ?>/markdown/create">
                <button class="btn-historical">Crear +</button> <!-- Mantengo la clase si tu CSS depende de ella -->
            </a>
        </div>

        <!-- Contenido del Dashboard -->
        <div class="dashboard-content">
            <div class="historical"> <!-- Mantengo la clase si tu CSS depende de ella -->
                <h3>Historial</h3>
                <!-- Aquí podrías poner una tabla o cualquier otro contenido relacionado al historial -->
                <div class="historical-content"> <!-- Mantengo la clase si tu CSS depende de ella -->
                    <?php if (!empty($historicalData)): ?>
                        <ul>
                            <?php foreach ($historicalData as $item): ?>
                                <li>
                                    <strong><?php echo htmlspecialchars($item['title'] ?? 'Sin título', ENT_QUOTES, 'UTF-8'); ?></strong>
                                    <span> - Creado el: <?php echo htmlspecialchars(date("d/m/Y H:i", strtotime($item['created_at'] ?? 'now')), ENT_QUOTES, 'UTF-8'); ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p>El historial aparecerá aquí...</p> <!-- Mensaje por defecto -->
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Puedes incluir un footer si lo tienes -->
    <?php
        // if (defined('VIEWS_PATH') && file_exists(VIEWS_PATH . 'footer.php')) {
        //     include VIEWS_PATH . 'footer.php';
        // }
    ?>

    <!-- Scripts JS -->
    <!-- <script src="<?php echo htmlspecialchars($base_url, ENT_QUOTES, 'UTF-8'); ?>/public/js/main.js"></script> -->
</body>
</html>