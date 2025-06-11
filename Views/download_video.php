<?php
// --- Views/download_video.php ---
// Variables pasadas por MarkdownController->showVideoDownloadPage():
// $base_url, $pageTitle, $downloadLink, $actual_filename
$base_url = $base_url ?? '';
$pageTitle = $pageTitle ?? 'Descargar Video MP4';
$downloadLink = $downloadLink ?? '#'; // Enlace para la descarga real
$actual_filename = $actual_filename ?? 'video.mp4'; // Nombre del archivo a mostrar
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8'); ?></title>
    <link rel="stylesheet" href="<?php echo htmlspecialchars($base_url, ENT_QUOTES, 'UTF-8'); ?>/public/css/header.css">
    <style>
        body { font-family: Arial, sans-serif; display: flex; flex-direction: column; align-items: center; justify-content: flex-start; min-height: 100vh; margin: 0; background-color: #f4f7f9; }
        .page-container { width: 100%; } /* Para que el header ocupe todo el ancho */
        .download-wrapper { margin-top: 50px; } /* Espacio después del header */
        .download-container { background-color: #fff; padding: 30px 40px; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); text-align: center; }
        .download-container h2 { margin-top: 0; color: #333; }
        .download-btn { display: inline-block; background-color: #007bff; color: white; padding: 12px 25px; text-decoration: none; border-radius: 5px; font-size: 1.1em; margin-top: 15px; margin-bottom:20px; transition: background-color 0.2s ease; }
        .download-btn:hover { background-color: #0056b3; }
        .filename-display { font-weight: bold; margin-top:10px; display: block; color: #555; font-size: 0.95em; }
        .actions-links { margin-top: 25px; font-size: 0.9em; }
        .actions-links a { color: #007bff; text-decoration: none; margin: 0 10px; }
        .actions-links a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="page-container">
        <?php 
        // Incluir el header común si existe
        // Asegúrate de que la ruta al header es correcta o ajústala
        $headerPath = __DIR__ . '/../partials/header.php';
        if (file_exists($headerPath)) {
            include $headerPath;
        } else {
            echo "<!-- Header no encontrado en: ".htmlspecialchars($headerPath, ENT_QUOTES, 'UTF-8')." -->";
        }
        ?>
    </div>

    <div class="download-wrapper">
        <div class="download-container">
            <h2><?php echo htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8'); ?></h2>
            <p>Tu video MP4 está listo para descargar.</p>
            <a href="<?php echo htmlspecialchars($downloadLink, ENT_QUOTES, 'UTF-8'); ?>" class="download-btn" download="<?php echo htmlspecialchars($actual_filename, ENT_QUOTES, 'UTF-8'); ?>">Descargar Video</a>
            <span class="filename-display">Nombre del archivo: <?php echo htmlspecialchars($actual_filename, ENT_QUOTES, 'UTF-8'); ?></span>
            <div class="actions-links">
                <a href="<?php echo htmlspecialchars($base_url, ENT_QUOTES, 'UTF-8'); ?>/marp/editor">Volver al Editor Marp</a>
                <a href="<?php echo htmlspecialchars($base_url, ENT_QUOTES, 'UTF-8'); ?>/dashboard">Ir al Dashboard</a>
            </div>
        </div>
    </div>
</body>
</html>
