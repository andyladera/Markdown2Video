<?php
// Define el namespace correcto basado en tu estructura y configuración de composer.json
namespace Dales\Markdown2video\Controllers;

// Importa PDO si necesitas interactuar directamente con la BD aquí
// (Aunque es mejor usar Modelos para eso)
use PDO;

// Puedes importar Modelos si los necesitas para obtener datos para el dashboard
// use Dales\Markdown2video\Models\UserModel;
// use Dales\Markdown2video\Models\HistoryModel; // Ejemplo

class DashboardController {

    private ?PDO $pdo; // Propiedad para almacenar la conexión PDO (puede ser null si no siempre se necesita)

    /**
     * Constructor para DashboardController.
     * Se inyecta la conexión PDO (opcionalmente).
     * ¡CRÍTICO: Verifica si el usuario está autenticado!
     *
     * @param PDO|null $pdo La instancia de la conexión PDO (inyectada desde index.php).
     */
    public function __construct(?PDO $pdo = null) {
        $this->pdo = $pdo; // Almacena la conexión PDO si se pasó

        // --- VERIFICACIÓN DE AUTENTICACIÓN ---
        // session_start() ya se llamó en index.php
        // Usamos el flag 'logged_in' que establecimos en AuthController::processLogin
        if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
            // Si el usuario NO está logueado:
            // 1. Opcional: Limpiar cualquier dato de sesión residual por si acaso.
            //    session_unset();
            //    session_destroy();
            // 2. Redirigir a la página de login.
            //    BASE_URL es la constante global definida en index.php.
            //    Asegúrate que la ruta '/auth/login' sea manejada por tu router.
            header('Location: ' . BASE_URL . '/auth/login');
            exit(); // ¡Importante! Detener la ejecución del script aquí.
        }
        // Si llegamos aquí, el usuario está logueado.
        // --------------------------------------

        // Opcional: Cargar datos del usuario si son necesarios en múltiples métodos
        // if ($this->pdo && isset($_SESSION['user_id'])) {
        //     $userModel = new UserModel($this->pdo);
        //     $this->currentUser = $userModel->getUserById($_SESSION['user_id']);
        //     if (!$this->currentUser) {
        //         // Manejar el caso raro donde el ID de sesión existe pero el usuario no está en la BD
        //         $this->logoutAndRedirect(); // Necesitarías un método helper o llamar a AuthController
        //     }
        // }
    }

    /**
     * Método que se ejecuta para la ruta principal del dashboard (ej. /dashboard).
     * Obtiene datos y carga la vista principal del dashboard.
     */
    public function index(): void {
        // 1. Obtener datos necesarios para la vista
        //    - Datos del usuario desde la sesión (ya verificamos que está logueado).
        $userId = $_SESSION['user_id'] ?? 0; // Obtener ID por si se necesita para otras consultas
        $username = $_SESSION['username'] ?? 'Usuario'; // Nombre para mostrar saludo

        //    - Datos específicos del dashboard (ej. historial reciente, estadísticas)
        //      Aquí es donde usarías tus Modelos si los necesitas.
        $historicalData = []; // Array vacío como placeholder
        // if ($this->pdo) { // Verificar si tenemos conexión PDO
        //     try {
        //         $historyModel = new HistoryModel($this->pdo); // Ejemplo
        //         $historicalData = $historyModel->getRecentHistory($userId, 5); // Obtener últimos 5
        //     } catch (\Exception $e) {
        //         error_log("Error obteniendo historial para dashboard: " . $e->getMessage());
        //         // No necesariamente detener la ejecución, el dashboard puede mostrarse sin historial
        //     }
        // }

        // 2. Preparar variables que la vista necesitará
        $pageTitle = "Dashboard Principal"; // Título para la etiqueta <title>
        $welcomeMessage = "¡Bienvenido de nuevo, " . htmlspecialchars($username, ENT_QUOTES, 'UTF-8') . "!";
        $base_url = BASE_URL; // Pasar la URL base a la vista

        // 3. Cargar la vista del dashboard
        //    VIEWS_PATH es la constante global definida en index.php
        $viewPath = VIEWS_PATH . 'dashboard.php';

        if (file_exists($viewPath)) {
            // Las variables definidas aquí ($pageTitle, $welcomeMessage, etc.)
            // estarán disponibles dentro del archivo de la vista.
            require_once $viewPath;
        } else {
            // Si la vista no existe, loguear el error y mostrar un mensaje genérico.
            error_log("Error Crítico: Archivo de vista no encontrado: " . $viewPath);
            // Considera incluir una vista de error 500 aquí.
            if (defined('VIEWS_PATH') && file_exists(VIEWS_PATH . 'error/500.php')) {
                 http_response_code(500);
                 include VIEWS_PATH . 'error/500.php';
            } else {
                http_response_code(500);
                echo "Error interno del servidor: no se pudo cargar la interfaz del dashboard.";
            }
        }
    }

    // --- Otros métodos para acciones del Dashboard ---
    // Podrías tener métodos para manejar diferentes secciones o acciones AJAX.

    // Ejemplo:
    // public function settings(): void {
    //     // Verificar permisos si es necesario
    //     $pageTitle = "Configuración";
    //     $base_url = BASE_URL;
    //     // Cargar modelo de configuración si existe...
    //     // Cargar vista de configuración
    //     require_once VIEWS_PATH . 'settings/index.php'; // Asumiendo Views/settings/index.php
    // }

    // Ejemplo de método helper privado para redirigir en caso de error de autenticación
    // private function logoutAndRedirect(): void {
    //     $_SESSION = array();
    //     if (ini_get("session.use_cookies")) { /* ... código para borrar cookie ... */ }
    //     session_destroy();
    //     header('Location: ' . BASE_URL . '/auth/login');
    //     exit();
    // }
}