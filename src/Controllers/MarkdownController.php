<?php
namespace Dales\Markdown2video\Controllers;

use PDO;
use Dompdf\Dompdf;
use Dompdf\Options;
use FFMpeg\FFMpeg;
use FFMpeg\Format\Video\X264;
use FFMpeg\Coordinate\FrameRate;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class MarkdownController {
    private ?PDO $pdo;

    public function __construct(?PDO $pdo = null) {
        $this->pdo = $pdo;
        if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
            header('Location: ' . BASE_URL . '/auth/login'); 
            exit();
        }
    }

    /**
     */
    public function create(): void {
        $base_url = BASE_URL;
        $pageTitle = "Editor de Presentación (Markdown)";
        
        // Token CSRF específico para acciones en esta página, como generar PDF
        if (empty($_SESSION['csrf_token_generate_pdf'])) { 
            $_SESSION['csrf_token_generate_pdf'] = bin2hex(random_bytes(32)); 
        }
        $csrf_token_generate_pdf = $_SESSION['csrf_token_generate_pdf'];

        $viewPath = VIEWS_PATH . 'base_markdown.php'; // Asume que es Views/base_markdown.php
        if (file_exists($viewPath)) {
            // Las variables $base_url, $pageTitle, $csrf_token_generate_pdf estarán disponibles
            require_once $viewPath;
        } else {
            $this->showErrorPage("Vista del editor Markdown no encontrada: " . $viewPath);
        }
    }

    /**
     * Muestra el editor para Marp.
     * Ruta: GET /markdown/marp-editor
     */
    public function showMarpEditor(): void {
        $base_url = BASE_URL;
        $pageTitle = "Editor de Presentación (Marp)";
        if (empty($_SESSION['csrf_token_marp_generate'])) { // Token diferente si es necesario
            $_SESSION['csrf_token_marp_generate'] = bin2hex(random_bytes(32)); 
        }
        $csrf_token_marp_generate = $_SESSION['csrf_token_marp_generate'];
        
        $viewPath = VIEWS_PATH . 'base_marp.php'; // Asume que es Views/base_marp.php
        if (file_exists($viewPath)) {
            require_once $viewPath;
        } else {
            $this->showErrorPage("Vista del editor Marp no encontrada: " . $viewPath);
        }
    }
    
    /**
     */
    public function renderMarpPreview(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['markdown'])) {
            http_response_code(400); header('Content-Type: application/json');
            echo json_encode(['error' => 'Petición incorrecta o falta contenido markdown.']);
            exit;
        }
        ob_start();
        $renderScriptPath = ROOT_PATH . '/server/render_marp.php';
        if (file_exists($renderScriptPath)) {
            include $renderScriptPath;
        } else {
            error_log("Script render_marp.php no encontrado: " . $renderScriptPath);
            if (!headers_sent()) { http_response_code(500); header('Content-Type: application/json'); }
            echo json_encode(['error' => 'Error interno (script de renderizado no encontrado).']);
        }
        $output = ob_get_clean();
        echo $output; 
        exit;
    }

    /**
     */
    public function generatePdfFromHtml(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['html_content'])) {
            http_response_code(400); header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Petición incorrecta o falta contenido HTML.']);
            exit;
        }

        // VALIDAR TOKEN CSRF (si lo estás usando para esta acción)
        // El nombre del token en $_POST debe ser 'csrf_token_generate_pdf'
        if (empty($_POST['csrf_token_generate_pdf']) || !hash_equals($_SESSION['csrf_token_generate_pdf'] ?? '', $_POST['csrf_token_generate_pdf'])) {
            http_response_code(403); header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Token CSRF inválido o faltante.']);
            exit;
        }

        $htmlContent = $_POST['html_content'];

        $clean_html = $htmlContent; 

        $userIdForPath = $_SESSION['user_id'] ?? 'guest_' . substr(session_id(), 0, 8);
        $userTempDir = ROOT_PATH . '/public/temp_files/pdfs/' . $userIdForPath . '/';
        if (!is_dir($userTempDir)) { if (!mkdir($userTempDir, 0775, true) && !is_dir($userTempDir)) { /* ... error ... */ exit; } }

        $pdfFileName = 'preview_md_' . time() . '_' . bin2hex(random_bytes(3)) . '.pdf';
        $outputPdfFile = $userTempDir . $pdfFileName;

        try {
            $options = new Options();
            $options->set('isHtml5ParserEnabled', true);
            $options->set('isRemoteEnabled', true); 

            $dompdf = new Dompdf($options);
            
            $cssBaseMarkdown = file_exists(ROOT_PATH . '/public/css/base_markdown.css') ? file_get_contents(ROOT_PATH . '/public/css/base_markdown.css') : '';
            $cssHeader = file_exists(ROOT_PATH . '/public/css/header.css') ? file_get_contents(ROOT_PATH . '/public/css/header.css') : '';

            $fullHtml = '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title>Documento</title>';
            $fullHtml .= '<style>' . $cssBaseMarkdown . $cssHeader . ' body { font-family: sans-serif; margin: 20px; } #ppt-preview { border: none!important; padding:0!important; background:transparent!important; } /* Ajustes para preview dentro del PDF */ </style>';
            $fullHtml .= '</head><body><div class="preview-container"><div class="preview-body"><div id="ppt-preview" class="ppt-preview">' . $clean_html . '</div></div></div></body></html>';

            $dompdf->loadHtml($fullHtml);
            $dompdf->setPaper('A4', 'landscape'); 
            $dompdf->render();
            
            if (file_put_contents($outputPdfFile, $dompdf->output()) === false) {
                throw new \Exception("No se pudo guardar el archivo PDF generado.");
            }

            $_SESSION['pdf_download_file'] = $pdfFileName;
            $_SESSION['pdf_download_full_path'] = $outputPdfFile;

            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'PDF generado desde preview. Abriendo página de descarga...',
                'downloadPageUrl' => '/markdown/download-page/' . urlencode($pdfFileName)
            ]);
            exit;

        } catch (\Exception $e) {
            error_log("Error generando PDF con Dompdf: " . $e->getMessage() . "\n" . $e->getTraceAsString());
            http_response_code(500); header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Error al generar el archivo PDF.', 'debug' => (ENVIRONMENT === 'development' ? $e->getMessage() : 'Error interno.')]);
            if (file_exists($outputPdfFile)) unlink($outputPdfFile);
            exit;
        }
    }

    public function showPdfDownloadPage(string $filenameFromUrl): void {
        $filename = basename(urldecode($filenameFromUrl));
        $userIdForPath = $_SESSION['user_id'] ?? 'guest_' . substr(session_id(), 0, 8);
        $expectedSessionFile = $_SESSION['pdf_download_file'] ?? null;
        $expectedSessionPath = $_SESSION['pdf_download_full_path'] ?? null;
        $currentExpectedDiskPath = ROOT_PATH . '/public/temp_files/pdfs/' . $userIdForPath . '/' . $filename;

        if ($expectedSessionFile === $filename && $expectedSessionPath === $currentExpectedDiskPath && file_exists($currentExpectedDiskPath)) {
            $base_url = BASE_URL;
            $pageTitle = "Descargar PDF: " . htmlspecialchars($filename, ENT_QUOTES, 'UTF-8');
            $downloadLink = BASE_URL . '/markdown/force-download-pdf/' . urlencode($filename);
            $actual_filename = $filename;
            require_once VIEWS_PATH . '/download_pdf.php';
        } else { /* ... manejo de error ... */ exit; }
    }
    
    public function forceDownloadPdf(string $filenameFromUrl): void {
        $filename = basename(urldecode($filenameFromUrl));
        $userIdForPath = $_SESSION['user_id'] ?? 'guest_' . substr(session_id(), 0, 8);
        $expectedSessionPath = $_SESSION['pdf_download_full_path'] ?? null;
        $currentDiskPath = ROOT_PATH . '/public/temp_files/pdfs/' . $userIdForPath . '/' . $filename;

        if ($expectedSessionPath === $currentDiskPath && file_exists($currentDiskPath)) {
            header('Content-Description: File Transfer'); /* ... (resto de headers para descarga) ... */
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($currentDiskPath));
            flush(); readfile($currentDiskPath);
            unlink($currentDiskPath);
            unset($_SESSION['pdf_download_file'], $_SESSION['pdf_download_full_path']);
            exit;
        } else { exit; }
    }

    private function showErrorPage(string $logMessage, string $userMessage = "Error."): void { /* ... */ }

    public function generateMarpFile(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405); // Method Not Allowed
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Método no permitido. Se esperaba POST.']);
            exit;
        }

        $markdownContent = $_POST['markdown'] ?? null;
        $format = $_POST['format'] ?? null;

        if (empty($markdownContent) || empty($format)) {
            http_response_code(400); // Bad Request
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Faltan datos: markdown o formato.']);
            exit;
        }

        // Por ahora, solo implementamos PDF. Otros formatos se pueden añadir después.
        if ($format !== 'pdf') {
            http_response_code(400);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => "Formato '{$format}' no soportado actualmente para la generación Marp."]);
            exit;
        }

        // Preparar para llamar al script de renderizado/generación de Marp
        // Este script necesitará ser adaptado para manejar diferentes formatos y devolver la ruta del archivo.
        $renderScriptPath = ROOT_PATH . '/server/render_marp.php';
        if (!file_exists($renderScriptPath)) {
            error_log("Script render_marp.php no encontrado: " . $renderScriptPath);
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Error interno del servidor (script de generación no encontrado).']);
            exit;
        }

        // Crear un nombre de archivo único para el PDF
        $userIdForPath = $_SESSION['user_id'] ?? 'guest_' . substr(session_id(), 0, 8);
        $outputDir = ROOT_PATH . '/public/temp_files/pdfs/' . $userIdForPath;
        if (!is_dir($outputDir)) {
            if (!mkdir($outputDir, 0775, true) && !is_dir($outputDir)) {
                error_log("No se pudo crear el directorio temporal para PDFs Marp: " . $outputDir);
                http_response_code(500);
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'Error interno al crear directorio temporal.']);
                exit;
            }
        }

        $pdfFileName = 'marp_presentation_' . time() . '_' . bin2hex(random_bytes(4)) . '.pdf';
        $outputPdfFile = $outputDir . '/' . $pdfFileName;

        // Pasar datos al script render_marp.php. Podríamos usar variables de entorno o POST simulado si el script es un endpoint.
        // Por simplicidad, si render_marp.php es un script PHP que podemos incluir y que usa variables globales:
        $_MARP_MARKDOWN_CONTENT = $markdownContent;
        $_MARP_OUTPUT_FORMAT = $format; // 'pdf'
        $_MARP_OUTPUT_FILE_PATH = $outputPdfFile;

        ob_start();
        include $renderScriptPath; // Este script debería generar el archivo en $_MARP_OUTPUT_FILE_PATH
        $scriptOutput = ob_get_clean(); // Capturar cualquier salida del script (debería ser JSON o nada)

        unset($_MARP_MARKDOWN_CONTENT, $_MARP_OUTPUT_FORMAT, $_MARP_OUTPUT_FILE_PATH); // Limpiar variables globales

        // El script render_marp.php debería indicar éxito o error.
        // Asumimos que si el archivo PDF existe, fue exitoso.
        // Una mejor implementación sería que render_marp.php devuelva JSON.
        if (file_exists($outputPdfFile) && filesize($outputPdfFile) > 0) {
            $_SESSION['pdf_download_file'] = $pdfFileName;
            $_SESSION['pdf_download_full_path'] = $outputPdfFile;

            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Archivo PDF generado desde Marp con éxito.',
                'downloadPageUrl' => '/markdown/download-page/' . urlencode($pdfFileName)
            ]);
            exit;
        } else {
            error_log("render_marp.php no generó el archivo PDF esperado o el archivo está vacío. Ruta: {$outputPdfFile}. Salida del script: {$scriptOutput}");
            if (file_exists($outputPdfFile)) unlink($outputPdfFile); // Limpiar archivo vacío si existe
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Error al generar el archivo PDF desde Marp.', 'debug_script_output' => (ENVIRONMENT === 'development' ? $scriptOutput : null)]);
            exit;
        }
    }

    public function generateVideoFromMarp(): void {
        header('Content-Type: application/json');

        $markdownContent = $_POST['markdown'] ?? null;
        if (empty($markdownContent)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Falta contenido markdown.']);
            exit;
        }

        // 1. Crear un directorio temporal único
        $userIdForPath = $_SESSION['user_id'] ?? 'guest_' . substr(session_id(), 0, 8);
        $uniqueJobId = time() . '_' . bin2hex(random_bytes(4));
        $tempDir = ROOT_PATH . '/public/temp_files/videos/' . $userIdForPath . '/' . $uniqueJobId;

        if (!is_dir($tempDir) && !mkdir($tempDir, 0775, true)) {
            error_log("No se pudo crear el directorio temporal para el video: " . $tempDir);
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Error interno al crear directorio temporal.']);
            exit;
        }

        // 2. Guardar el Markdown en un archivo temporal
        $markdownFilePath = $tempDir . '/input.md';
        if (file_put_contents($markdownFilePath, $markdownContent) === false) {
            error_log("No se pudo escribir el archivo markdown temporal en: " . $markdownFilePath);
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Error interno al guardar el archivo markdown.']);
            exit;
        }

        // 3. Ejecutar Marp CLI para generar las imágenes
        $marpCliPath = ROOT_PATH . '/node_modules/.bin/marp';
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $marpCliPath .= '.cmd'; // En Windows, se usa el script .cmd
        }

        // Guardar el directorio actual y cambiar al temporal para que Marp funcione de forma predecible
        $original_cwd = getcwd();
        chdir($tempDir);

        // El nombre del archivo de entrada ahora es relativo al directorio temporal
        $markdownFileName = 'input.md'; 
        $escapedMarkdownFile = escapeshellarg($markdownFileName);

        // Comando simplificado: Marp usará el CWD (que es $tempDir) para la salida
        // Generará input.html, input.001.png, input.002.png, etc.
        $command = escapeshellarg($marpCliPath) . " --html --images png {$escapedMarkdownFile}";

        $exec_output = null;
        $exec_return_code = null;
        exec($command . ' 2>&1', $exec_output, $exec_return_code);

        // Restaurar el directorio de trabajo original
        chdir($original_cwd);

        // 4. Comprobar si el comando tuvo éxito
        if ($exec_return_code !== 0) {
            error_log("Marp CLI falló con código {$exec_return_code}. Comando: {$command}. Salida: " . implode("\n", $exec_output));
            http_response_code(500);
            echo json_encode([
                'success' => false, 
                'error' => 'Error al convertir diapositivas a imágenes.',
                'debug' => (ENVIRONMENT === 'development' ? $exec_output : null)
            ]);
            exit;
        }

        // 5. Contar las imágenes generadas para verificar
        $imageFiles = glob($tempDir . '/*.png');
        $imageCount = count($imageFiles);

        if ($imageCount === 0) {
            error_log("Marp CLI se ejecutó pero no se encontraron imágenes PNG en {$tempDir}. Salida: " . implode("\n", $exec_output));
            http_response_code(500);
            echo json_encode([
                'success' => false, 
                'error' => 'La conversión se completó pero no se generaron imágenes.',
                'debug' => (ENVIRONMENT === 'development' ? $exec_output : null)
            ]);
            exit;
        }

        // 6. Configurar el logger para FFmpeg
        // Usar el directorio temporal del sistema para evitar problemas de permisos.
        $logFile = sys_get_temp_dir() . '/ffmpeg.log';
        // Limpiar el log anterior en cada ejecución para tener información fresca.
        if (file_exists($logFile)) { unlink($logFile); }
        $logger = new Logger('ffmpeg');
        $logger->pushHandler(new StreamHandler($logFile, Logger::DEBUG));

        // 7. Instanciar FFmpeg con las rutas y el logger
        $ffmpeg = FFMpeg::create([
            'ffmpeg.binaries'  => 'C:/ffmpeg/bin/ffmpeg.exe',
            'ffprobe.binaries' => 'C:/ffmpeg/bin/ffprobe.exe',
            'timeout'          => 3600, // Tiempo máximo de espera para el proceso
            'ffmpeg.threads'   => 12,   // Hilos a usar por FFMpeg
        ], $logger);

        // 8. Preparar para la creación del video
        $videoPath = $tempDir . '/output.mp4';
        $durationPerSlide = 3; // 3 segundos por diapositiva
        $inputFramerate = 1 / $durationPerSlide;

        // El patrón de las imágenes generadas por Marp es 'input.001.png', etc.
        $imagePattern = $tempDir . '/input.%03d.png';

        try {
            // La API de alto nivel de php-ffmpeg (open, openAdvanced) tiene problemas para establecer
            // el framerate de entrada para secuencias de imágenes, que es la única forma de controlar
            // la duración de cada diapositiva.
            // La solución más robusta es usar el 'driver' de bajo nivel para construir y ejecutar
            // el comando de FFmpeg directamente, dándonos control total sobre los parámetros.

            $ffmpeg->getFFMpegDriver()->command([
                '-framerate', strval($inputFramerate), // Opción de ENTRADA: duración de cada imagen
                '-i', $imagePattern,                   // Archivos de entrada
                '-c:v', 'libx264',                     // Codec de video
                '-r', '25',                            // Opción de SALIDA: framerate del video final
                '-pix_fmt', 'yuv420p',                 // Formato de píxeles para máxima compatibilidad
                $videoPath                             // Archivo de salida
            ]);

        } catch (\FFMpeg\Exception\ExceptionInterface $e) {
            // Usamos nuestro logger para guardar el error detallado
            if (isset($logger)) {
                $logger->error("FFmpeg falló al crear el video.", ['exception' => $e]);
            } else {
                error_log("FFmpeg falló al crear el video: " . $e->getMessage() . "\n" . $e->getTraceAsString());
            }
            
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'La codificación del video falló. Revisa el archivo de registro para más detalles.',
                'log_file' => $logFile // Devolver la ruta completa al archivo de log
            ]);
            exit;
        }

        // 9. Devolver la URL del video si se creó correctamente
        if (file_exists($videoPath)) {
            // 10. Guardar información del video en la sesión para la página de descarga
            $_SESSION['video_info'] = [
                'url' => str_replace(ROOT_PATH, '', $videoPath), // URL relativa para el navegador
                'filename' => 'video_' . date('Ymd_His') . '.mp4',
                'path' => $videoPath // Ruta absoluta para limpieza futura si es necesario
            ];

            // Limpiar las imágenes PNG y el archivo markdown temporal
            foreach ($imageFiles as $file) {
                if (file_exists($file)) unlink($file);
            }
            if (file_exists($markdownFilePath)) unlink($markdownFilePath);

            // 11. Devolver una respuesta JSON con la URL de redirección
            echo json_encode([
                'success' => true,
                'redirectUrl' => '/markdown/download-video' // La nueva ruta que crearemos
            ]);

        } else {
            error_log("El video no fue encontrado en la ruta esperada después de la ejecución de FFmpeg: " . $videoPath);
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Error interno: el archivo de video no se pudo crear.']);
        }

        exit;
    }

    public function showDownloadVideoPage()
    {
        // La vista 'download_video.php' se encarga de verificar la sesión y obtener los datos.
        require_once ROOT_PATH . '/Views/download_video.php';
    }
}