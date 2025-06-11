<?php
session_start();

// Verificar si el usuario está autenticado
if (!isset($_SESSION['user_id'])) {
    header('Location: /auth/login');
    exit;
}

// Verificar si la información del video está en la sesión
if (!isset($_SESSION['video_info'])) {
    // Redirigir si no hay información del video, por ejemplo, al dashboard
    header('Location: /dashboard');
    exit;
}

$videoInfo = $_SESSION['video_info'];
$videoUrl = $videoInfo['url'];
$videoFilename = $videoInfo['filename'];

// Limpiar la información de la sesión para que no se reutilice
unset($_SESSION['video_info']);

$page_title = "Video Generado - Markdown2Video";
?>

<?php include 'header.php'; ?>

<div class="container mt-5">
    <div class="card">
        <div class="card-header text-white" style="background-color: #003366;">
            <h1 class="h4 mb-0">¡Tu video se ha generado con éxito!</h1>
        </div>
        <div class="card-body">
            <p class="card-text">A continuación, puedes previsualizar tu video. Si estás satisfecho, puedes descargarlo usando el botón de abajo.</p>

            <!-- Previsualización del Video -->
            <div class="video-preview-container mb-4" style="border: 1px solid #ddd; padding: 10px; background-color: #000;">
                <video controls width="100%" style="display: block;">
                    <source src="<?= htmlspecialchars($videoUrl) ?>" type="video/mp4">
                    Tu navegador no soporta la etiqueta de video.
                </video>
            </div>

            <!-- Botón de Descarga -->
            <a href="<?= htmlspecialchars($videoUrl) ?>" download="<?= htmlspecialchars($videoFilename) ?>" class="btn btn-lg btn-success">
                <i class="fas fa-download"></i> Descargar Video MP4
            </a>
            <a href="/dashboard" class="btn btn-lg btn-secondary">
                <i class="fas fa-arrow-left"></i> Volver al Dashboard
            </a>
        </div>
        <div class="card-footer text-muted">
            El archivo estará disponible para su descarga. Recuerda guardarlo en un lugar seguro.
        </div>
    </div>
</div>

<?php include 'footer.php'; // Asumiendo que tienes un footer.php ?>
