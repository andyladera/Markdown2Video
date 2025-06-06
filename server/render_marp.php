<?php
// server/render_marp.php
// Este script ahora es llamado/incluido por MarkdownController->renderMarpPreview()
// Asume que $_POST['markdown'] está disponible.

// No es necesario ini_set aquí si el controlador ya lo maneja, pero no hace daño.
// ini_set('display_errors', 0); // Errores deben ser logueados y manejados
// error_reporting(E_ALL);

// Función para enviar respuesta JSON de error y terminar
function send_json_error(int $statusCode, string $message, ?string $details = null): void {
    if (!headers_sent()) {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
    }
    $response = ['error' => $message];
    if ($details !== null) {
        $response['details'] = $details;
    }
    echo json_encode($response);
    // No hacemos exit() aquí, dejamos que el controlador lo maneje si es un include.
    // Si este script se llamara DIRECTAMENTE como un endpoint API, necesitaría exit().
}

if (!isset($_POST['markdown'])) {
    send_json_error(400, 'No se recibió contenido markdown.');
    return; // Si es un include, retornar. Si es un endpoint directo, sería exit.
}

$markdownContent = $_POST['markdown'];

$nodeExecutablePath = 'node'; // O la ruta completa si 'node' no está en el PATH del servidor
$marpCliScriptPath  = realpath(__DIR__ . '/../node_modules/@marp-team/marp-cli/marp-cli.js');

if ($marpCliScriptPath === false) {
    error_log("Error Crítico: No se pudo encontrar Marp CLI. Ruta intentada: " . __DIR__ . '/../node_modules/@marp-team/marp-cli/marp-cli.js');
    send_json_error(500, 'Error de configuración del servidor: Marp CLI no encontrado.');
    return;
}

$tmpMdFile = tempnam(sys_get_temp_dir(), 'marp_md_');
if ($tmpMdFile === false) {
    error_log("Error creando archivo temporal para Markdown.");
    send_json_error(500, 'Error interno del servidor al crear archivo temporal.');
    return;
}

// Añadir extensión .md para que Marp lo reconozca mejor
$tmpMdFileWithExt = $tmpMdFile . '.md';
if (!rename($tmpMdFile, $tmpMdFileWithExt)) {
    // Si rename falla (ej. permisos), usar el nombre original sin extensión
    // Marp podría aun así funcionar, pero es mejor con la extensión.
    error_log("Advertencia: No se pudo renombrar el archivo temporal a .md: " . $tmpMdFile);
    // Continuar con $tmpMdFile (el que no tiene .md) si $tmpMdFileWithExt falló
    // O podrías decidir fallar aquí si la extensión es crítica para tu Marp CLI.
    // $tmpMdFileWithExt = $tmpMdFile; // Esto es redundante si rename falló y no cambiaste $tmpMdFile.
    // Si rename falló, $tmpMdFile es el que existe.
} else {
    $tmpMdFile = $tmpMdFileWithExt; // Usar el archivo renombrado
}


if (file_put_contents($tmpMdFile, $markdownContent) === false) {
    error_log("Error escribiendo en archivo temporal Markdown: " . $tmpMdFile);
    if (file_exists($tmpMdFile)) unlink($tmpMdFile); // Limpiar
    send_json_error(500, 'Error interno del servidor al escribir archivo temporal.');
    return;
}

// Marp CLI con --html y sin -o (o con -o -) debería imprimir HTML a stdout
$command = sprintf(
    '%s "%s" %s --html --allow-local-files -o -',
    escapeshellcmd($nodeExecutablePath), // Escapar el comando node
    $marpCliScriptPath,                  // Ruta al script JS de Marp, no necesita escapeshellcmd si es una ruta fija y confiable
    escapeshellarg($tmpMdFile)           // Argumento de archivo, siempre escapar
);

// error_log("Ejecutando Comando para Marp HTML: " . $command); // Para debug

$descriptorspec = [ 0 => ["pipe", "r"], 1 => ["pipe", "w"], 2 => ["pipe", "w"] ];
$pipes = [];
$process = proc_open($command, $descriptorspec, $pipes, sys_get_temp_dir());

$htmlOutput = '';
$errorOutput = '';

if (is_resource($process)) {
    fclose($pipes[0]); // Cerramos stdin
    $htmlOutput = stream_get_contents($pipes[1]); fclose($pipes[1]);
    $errorOutput = stream_get_contents($pipes[2]); fclose($pipes[2]);
    $return_status = proc_close($process);
} else {
    $return_status = -1;
    $errorOutput = "Error al iniciar el proceso de Marp CLI.";
}

if (file_exists($tmpMdFile)) {
    unlink($tmpMdFile); // Limpiar siempre el archivo temporal de entrada
}

if ($return_status === 0 && !empty($htmlOutput)) {
    if (!headers_sent()) {
        header('Content-Type: text/html; charset=utf-8');
    }
    echo $htmlOutput; // Enviar el HTML renderizado
} else {
    $logMsg = "Error ejecutando Marp CLI (HTML). Código: $return_status. Comando: $command. Stderr: $errorOutput. Stdout (parcial): " . substr($htmlOutput, 0, 200);
    error_log($logMsg);
    // Si el controlador incluye este script y queremos que el controlador maneje el error HTTP:
    // throw new \RuntimeException("Error al generar vista previa Marp: " . $errorOutput);
    // O si este script debe responder directamente como API:
    send_json_error(500, 'Error al generar la vista previa Marp desde el servidor.', $errorOutput ?: 'No hubo salida de error específica.');
}
// No más exit() aquí si va a ser incluido y el controlador maneja el flujo.
// Si este script es el endpoint final, aquí iría un exit().
?>