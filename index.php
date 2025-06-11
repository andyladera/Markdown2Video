<?php
// --- INICIO DE index.php ---

// 0. INCLUIR EL AUTOLOADER DE COMPOSER (¡EL MÁS IMPORTANTE!)
require_once __DIR__ . '/vendor/autoload.php';

// 1. CARGAR VARIABLES DE ENTORNO (SI USAS .env)
try {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->load();
    $dotenv->required(['DB_HOST', 'DB_NAME', 'DB_USER'])->notEmpty();
} catch (Dotenv\Exception\InvalidPathException $e) {
    error_log("Advertencia: Archivo .env no encontrado o no se pudo cargar. Error: " . $e->getMessage());
} catch (Dotenv\Exception\ValidationException $e) {
    error_log("Error Crítico: Faltan variables de entorno requeridas (DB_HOST, DB_NAME, DB_USER). " . $e->getMessage());
    die("Error de configuración del servidor. Por favor, contacte al administrador.");
}

// 2. DEFINIR CONSTANTES IMPORTANTES
define('ROOT_PATH', __DIR__);
define('BASE_URL', rtrim(dirname($_SERVER['SCRIPT_NAME']), '/'));
define('APP_PATH', ROOT_PATH . '/src/');
define('VIEWS_PATH', ROOT_PATH . '/Views/');

// 3. CONFIGURACIÓN DEL ENTORNO
define('ENVIRONMENT', 'development');

if (ENVIRONMENT === 'development') {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    error_reporting(0);
    ini_set('log_errors', 1);
    if (!is_dir(ROOT_PATH . '/logs')) {
        if (!mkdir(ROOT_PATH . '/logs', 0755, true) && !is_dir(ROOT_PATH . '/logs')) {
            error_log("Advertencia: No se pudo crear el directorio de logs: " . ROOT_PATH . '/logs');
        }
    }
    if (is_dir(ROOT_PATH . '/logs') && is_writable(ROOT_PATH . '/logs')) {
        ini_set('error_log', ROOT_PATH . '/logs/phperrors.log');
    } else {
        error_log("Advertencia: El directorio de logs (" . ROOT_PATH . "/logs) no es escribible o no existe.");
    }
}

// 4. MANEJADOR DE ERRORES Y EXCEPCIONES PERSONALIZADO
use Dales\Markdown2video\Config\ErrorHandler;
ErrorHandler::init();

// 5. CONFIGURACIÓN Y INICIO DE SESIÓN SEGURA
session_set_cookie_params([
    'lifetime' => 0,
    'path' => BASE_URL . '/',
    'domain' => $_SERVER['HTTP_HOST'],
    'secure' => (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on'),
    'httponly' => true,
    'samesite' => 'Lax'
]);
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 6. INYECCIÓN DE DEPENDENCIAS
use Dales\Markdown2video\Config\Database;

$pdoConnection = null;
try {
    $database = new Database();
    $pdoConnection = $database->getConnection();
} catch (Exception $e) {
    error_log("Error CRÍTICO al conectar con la base de datos: " . $e->getMessage() . "\nTrace: " . $e->getTraceAsString());
    http_response_code(503);
    if (defined('ENVIRONMENT') && ENVIRONMENT === 'development') {
        echo "<h1>Error de Conexión a Base de Datos</h1><p>" . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . "</p>";
    } else {
        echo "<h1>Error del Servidor</h1><p>No se pudo conectar con la base de datos.</p>";
    }
    exit;
}

// 7. RUTEO DE LA PETICIÓN
$urlParam = $_GET['url'] ?? '';
$urlParam = rtrim($urlParam, '/');
$urlParam = filter_var($urlParam, FILTER_SANITIZE_URL);
$urlSegments = $urlParam ? explode('/', $urlParam) : [];

if (empty($urlSegments)) {
    $controllerNamePart = 'Auth';
    $actionName         = 'showLoginForm';
} else {
    $controllerNamePart = ucfirst(strtolower($urlSegments[0]));
    $actionName         = isset($urlSegments[1]) && !empty($urlSegments[1]) ? strtolower($urlSegments[1]) : 'index';
}

$controllerClassName = "Dales\\Markdown2video\\Controllers\\" . $controllerNamePart . 'Controller';
$params = array_slice($urlSegments, 2);
$methodToCall = $actionName;

// Lógica de ruteo específica
if ($controllerClassName === 'Dales\\Markdown2video\\Controllers\\AuthController') {
    if ($actionName === 'login' && $_SERVER['REQUEST_METHOD'] === 'POST') { $methodToCall = 'processLogin'; }
    elseif ($actionName === 'login' && $_SERVER['REQUEST_METHOD'] === 'GET') { $methodToCall = 'showLoginForm'; }
    elseif ($actionName === 'logout') { $methodToCall = 'logout'; }
    elseif ($actionName === 'register' && $_SERVER['REQUEST_METHOD'] === 'GET') { $methodToCall = 'showRegisterForm'; }
    elseif ($actionName === 'register' && $_SERVER['REQUEST_METHOD'] === 'POST') { $methodToCall = 'processRegistration'; }
} elseif ($controllerClassName === 'Dales\\Markdown2video\\Controllers\\DashboardController') {
    if ($actionName === 'index') { /* 'index' es el default */ }
} elseif ($controllerClassName === 'Dales\\Markdown2video\\Controllers\\MarkdownController') {
    if ($actionName === 'create' && $_SERVER['REQUEST_METHOD'] === 'GET') { 
        $methodToCall = 'create'; 
    } elseif ($actionName === 'marp-editor' && $_SERVER['REQUEST_METHOD'] === 'GET') { 
        $methodToCall = 'showMarpEditor'; 
    } elseif ($actionName === 'render-marp-preview' && $_SERVER['REQUEST_METHOD'] === 'POST') { 
        $methodToCall = 'renderMarpPreview'; 
    } elseif ($actionName === 'generate-pdf-from-html' && $_SERVER['REQUEST_METHOD'] === 'POST') { 
        $methodToCall = 'generatePdfFromHtml'; 
    } elseif ($actionName === 'download-page' && $_SERVER['REQUEST_METHOD'] === 'GET') { 
        $methodToCall = 'showPdfDownloadPage'; 
    } elseif ($actionName === 'force-download-pdf' && $_SERVER['REQUEST_METHOD'] === 'GET') {
        $methodToCall = 'forceDownloadPdf'; 
    } elseif ($actionName === 'generate-marp-file' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $methodToCall = 'generateMarpFile';
    } elseif ($actionName === 'generate-video-from-marp' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $methodToCall = 'generateVideoFromMarp';
    } 
    // --- INICIO DE LA RUTA AÑADIDA ---
    elseif ($actionName === 'download-video' && $_SERVER['REQUEST_METHOD'] === 'GET') {
        $methodToCall = 'showDownloadVideoPage';
    }
    // --- FIN DE LA RUTA AÑADIDA ---
}

// VERIFICAR Y EJECUTAR EL CONTROLADOR
if (class_exists($controllerClassName)) {
    try {
        $controllerInstance = null;
        $controllersRequiringPdo = [
            'Dales\\Markdown2video\\Controllers\\AuthController',
            'Dales\\Markdown2video\\Controllers\\DashboardController',
            'Dales\\Markdown2video\\Controllers\\MarkdownController',
        ];

        if (in_array($controllerClassName, $controllersRequiringPdo)) {
            if ($pdoConnection === null) {
                throw new Exception("La conexión a la base de datos no está disponible para el controlador.");
            }
            $controllerInstance = new $controllerClassName($pdoConnection);
        } else {
            $controllerInstance = new $controllerClassName();
        }

        if (method_exists($controllerInstance, $methodToCall)) {
            call_user_func_array([$controllerInstance, $methodToCall], $params);
        } else {
            http_response_code(404);
            echo "404 - Método o Recurso no encontrado.";
        }
    } catch (Throwable $e) {
        error_log("Error al ejecutar controlador {$controllerClassName}->{$methodToCall}: " . $e->getMessage() . "\nTrace: " . $e->getTraceAsString());
        http_response_code(500);
        if (defined('ENVIRONMENT') && ENVIRONMENT === 'development') {
            echo "<h1>Error en el Controlador</h1><p>" . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . "</p>";
        } else {
            echo "<h1>Error del Servidor</h1><p>Ocurrió un error inesperado.</p>";
        }
    }
} else {
    http_response_code(404);
    echo "404 - Página no encontrada.";
}

// --- FIN DE index.php ---
?>