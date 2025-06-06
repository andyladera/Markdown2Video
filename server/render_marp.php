<?php
// server/render_marp.php

// Definir ROOT_PATH si no está ya definido (debería estarlo si es incluido por un script que lo define)
if (!defined('ROOT_PATH')) {
    // Esto es un fallback, idealmente el script que llama (MarkdownController) asegura que las constantes estén disponibles.
    define('ROOT_PATH', dirname(__DIR__)); // Asume que server/ está un nivel abajo del root
}

// --- NUEVA LÓGICA PARA GENERACIÓN DE ARCHIVOS (PDF, etc.) ---
// Estas variables globales son establecidas por MarkdownController->generateMarpFile()
if (isset($_MARP_OUTPUT_FORMAT, $_MARP_OUTPUT_FILE_PATH, $_MARP_MARKDOWN_CONTENT)) {
    
    $markdownContent = $_MARP_MARKDOWN_CONTENT;
    $outputFormat    = strtolower($_MARP_OUTPUT_FORMAT); // ej. 'pdf'
    $outputFilePath  = $_MARP_OUTPUT_FILE_PATH;

    if (empty($markdownContent)) {
        error_log("render_marp.php (File Gen Mode): Contenido Markdown vacío.");
        // No enviar JSON aquí, el controlador lo hará. Simplemente no crear el archivo.
        return; 
    }

    $nodeExecutablePath = 'node'; 
    // Usar ROOT_PATH para asegurar que la ruta a node_modules sea correcta
    $marpCliScriptPath  = realpath(ROOT_PATH . '/node_modules/@marp-team/marp-cli/marp-cli.js');

    if ($marpCliScriptPath === false) {
        error_log("render_marp.php (File Gen Mode): Marp CLI no encontrado. Ruta intentada: " . ROOT_PATH . '/node_modules/@marp-team/marp-cli/marp-cli.js');
        return;
    }

    $tmpMdFile = tempnam(sys_get_temp_dir(), 'marp_md_gen_');
    if ($tmpMdFile === false) {
        error_log("render_marp.php (File Gen Mode): Error creando archivo temporal MD.");
        return;
    }

    $tmpMdFileWithExt = $tmpMdFile . '.md';
    if (!rename($tmpMdFile, $tmpMdFileWithExt)) {
        error_log("render_marp.php (File Gen Mode): Advertencia, no se pudo renombrar temp MD a .md: " . $tmpMdFile);
        // Usar $tmpMdFile (sin .md) si rename falla, Marp CLI debería manejarlo
    } else {
        $tmpMdFile = $tmpMdFileWithExt;
    }

    if (file_put_contents($tmpMdFile, $markdownContent) === false) {
        error_log("render_marp.php (File Gen Mode): Error escribiendo en archivo temporal MD: " . $tmpMdFile);
        if (file_exists($tmpMdFile)) unlink($tmpMdFile);
        return;
    }

    $marpArgs = [];
    if ($outputFormat === 'pdf') {
        $marpArgs[] = '--pdf';
    } elseif ($outputFormat === 'pptx') {
        $marpArgs[] = '--pptx';
    } elseif ($outputFormat === 'html') { // Para generar un archivo HTML independiente
        $marpArgs[] = '--html';
    } else {
        error_log("render_marp.php (File Gen Mode): Formato de salida no soportado '{$outputFormat}'.");
        if (file_exists($tmpMdFile)) unlink($tmpMdFile);
        return;
    }
    
    // Comando para generar el archivo de salida
    $command = sprintf(
        '%s "%s" %s %s --allow-local-files --html -o %s',
        escapeshellcmd($nodeExecutablePath),
        $marpCliScriptPath, // Ya es realpath, no necesita escapeshellcmd si confiamos en la ruta
        escapeshellarg($tmpMdFile),
        implode(' ', $marpArgs), // --pdf, --pptx, etc.
        escapeshellarg($outputFilePath)
    );

    // error_log("render_marp.php (File Gen Mode): Ejecutando Comando: " . $command); // Para debug

    $descriptorspec = [ 0 => ["pipe", "r"], 1 => ["pipe", "w"], 2 => ["pipe", "w"] ];
    $pipes = [];
    // Especificar el directorio de trabajo puede ser útil si Marp CLI tiene problemas con rutas relativas para assets
    $cwd = ROOT_PATH; // O el directorio donde están los assets si es diferente
    $process = proc_open($command, $descriptorspec, $pipes, $cwd);

    $cmdOutput = '';
    $cmdErrorOutput = '';

    if (is_resource($process)) {
        fclose($pipes[0]); 
        $cmdOutput = stream_get_contents($pipes[1]); fclose($pipes[1]);
        $cmdErrorOutput = stream_get_contents($pipes[2]); fclose($pipes[2]);
        $return_status = proc_close($process);
    } else {
        $return_status = -1;
        $cmdErrorOutput = "Error al iniciar el proceso de Marp CLI para generación de archivo.";
    }

    if (file_exists($tmpMdFile)) {
        unlink($tmpMdFile);
    }

    if ($return_status !== 0) {
        error_log("render_marp.php (File Gen Mode): Error ejecutando Marp CLI. Código: $return_status. Formato: $outputFormat. Comando: $command. Stderr: $cmdErrorOutput. Stdout: $cmdOutput");
        // El controlador verificará si $outputFilePath existe. Si no, fallará.
    } else {
        // Éxito, el archivo debería estar en $outputFilePath
        // error_log("render_marp.php (File Gen Mode): Archivo {$outputFormat} generado con éxito en {$outputFilePath}");
    }
    
    // No imprimir nada aquí, el controlador se encarga de la respuesta JSON
    return; // Termina la ejecución del script para el modo de generación de archivos.

} // --- FIN DE LA NUEVA LÓGICA PARA GENERACIÓN DE ARCHIVOS ---


// --- LÓGICA EXISTENTE PARA VISTA PREVIA HTML (si no estamos en modo generación de archivo) ---
// Esta parte solo se ejecuta si las variables globales para generación de archivo NO están seteadas.

// Función para enviar respuesta JSON de error y terminar (para el modo vista previa)
function send_json_error_preview(int $statusCode, string $message, ?string $details = null): void {
    if (!headers_sent()) {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
    }
    $response = ['error' => $message];
    if ($details !== null && (defined('ENVIRONMENT') && ENVIRONMENT === 'development')) { // Mostrar detalles solo en desarrollo
        $response['details'] = $details;
    }
    echo json_encode($response);
    // No hacemos exit() aquí si es incluido, el controlador maneja el flujo.
    // Si este script es llamado directamente como un endpoint API, necesitaría un exit() o return explícito.
}


if (!isset($_POST['markdown'])) {
    send_json_error_preview(400, 'No se recibió contenido markdown para la vista previa.');
    return; 
}

$markdownContentPreview = $_POST['markdown'];

$nodeExecutablePathPreview = 'node'; 
$marpCliScriptPathPreview  = realpath(ROOT_PATH . '/node_modules/@marp-team/marp-cli/marp-cli.js');

if ($marpCliScriptPathPreview === false) {
    error_log("render_marp.php (Preview Mode): Marp CLI no encontrado. Ruta: " . ROOT_PATH . '/node_modules/@marp-team/marp-cli/marp-cli.js');
    send_json_error_preview(500, 'Error de configuración del servidor: Marp CLI no encontrado (vista previa).');
    return;
}

$tmpMdFilePreview = tempnam(sys_get_temp_dir(), 'marp_md_preview_');
if ($tmpMdFilePreview === false) {
    error_log("render_marp.php (Preview Mode): Error creando archivo temporal MD para vista previa.");
    send_json_error_preview(500, 'Error interno del servidor al crear archivo temporal (vista previa).');
    return;
}

$tmpMdFilePreviewWithExt = $tmpMdFilePreview . '.md';
if (!rename($tmpMdFilePreview, $tmpMdFilePreviewWithExt)) {
    error_log("render_marp.php (Preview Mode): Advertencia, no se pudo renombrar temp MD a .md: " . $tmpMdFilePreview);
    // Usar $tmpMdFilePreview si rename falla
} else {
    $tmpMdFilePreview = $tmpMdFilePreviewWithExt;
}

if (file_put_contents($tmpMdFilePreview, $markdownContentPreview) === false) {
    error_log("render_marp.php (Preview Mode): Error escribiendo en archivo temporal MD: " . $tmpMdFilePreview);
    if (file_exists($tmpMdFilePreview)) unlink($tmpMdFilePreview);
    send_json_error_preview(500, 'Error interno del servidor al escribir archivo temporal (vista previa).');
    return;
}

// Marp CLI con --html y sin -o (o con -o -) debería imprimir HTML a stdout
$commandPreview = sprintf(
    '%s "%s" %s --html --allow-local-files --html -o -',
    escapeshellcmd($nodeExecutablePathPreview),
    $marpCliScriptPathPreview,
    escapeshellarg($tmpMdFilePreview)
);

// error_log("render_marp.php (Preview Mode): Ejecutando Comando: " . $commandPreview); // Para debug

$descriptorspecPreview = [ 0 => ["pipe", "r"], 1 => ["pipe", "w"], 2 => ["pipe", "w"] ];
$pipesPreview = [];
$cwdPreview = ROOT_PATH; // Especificar CWD para Marp CLI
$processPreview = proc_open($commandPreview, $descriptorspecPreview, $pipesPreview, $cwdPreview);

$htmlOutputPreview = '';
$errorOutputPreview = '';

if (is_resource($processPreview)) {
    fclose($pipesPreview[0]); 
    $htmlOutputPreview = stream_get_contents($pipesPreview[1]); fclose($pipesPreview[1]);
    $errorOutputPreview = stream_get_contents($pipesPreview[2]); fclose($pipesPreview[2]);
    $return_status_preview = proc_close($processPreview);
} else {
    $return_status_preview = -1;
    $errorOutputPreview = "Error al iniciar el proceso de Marp CLI para vista previa.";
}

if (file_exists($tmpMdFilePreview)) {
    unlink($tmpMdFilePreview); 
}

if ($return_status_preview === 0 && !empty($htmlOutputPreview)) {
    if (!headers_sent()) {
        header('Content-Type: text/html; charset=utf-8');
    }
    echo $htmlOutputPreview; 
} else {
    $logMsgPreview = "render_marp.php (Preview Mode): Error ejecutando Marp CLI (HTML). Código: $return_status_preview. Comando: $commandPreview. Stderr: $errorOutputPreview. Stdout (parcial): " . substr($htmlOutputPreview, 0, 200);
    error_log($logMsgPreview);
    send_json_error_preview(500, 'Error al generar la vista previa Marp desde el servidor.', $errorOutputPreview ?: 'No hubo salida de error específica.');
}
// No más exit() aquí si va a ser incluido y el controlador maneja el flujo.
?>