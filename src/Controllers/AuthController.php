<?php
// 1. NAMESPACE CORRECTO
namespace Dales\Markdown2video\Controllers;

// 2. IMPORTAR CLASES NECESARIAS
use PDO; // Para type hinting de la conexión inyectada

class AuthController {
    private PDO $pdo; // Almacena la conexión PDO inyectada

    /**
     * Constructor que recibe la conexión PDO.
     * @param PDO $pdo La instancia de la conexión PDO.
     */
    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Muestra el formulario de login.
     * Llamado por el router para GET /auth/login (o la ruta raíz '/').
     */
    public function showLoginForm(): void {
        // Generar token CSRF si no existe
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        $csrf_token = $_SESSION['csrf_token'];

        // Variables necesarias para la vista
        $base_url = BASE_URL; // Constante global
        $error_message = $_SESSION['error'] ?? null;
        $success_message = $_SESSION['success'] ?? null;
        unset($_SESSION['error'], $_SESSION['success']); // Limpiar mensajes después de leer

        // Cargar la vista (VIEWS_PATH es constante global)
        require_once VIEWS_PATH . 'auth/login.php';
    }

    /**
     * Procesa el intento de login.
     * Llamado por el router para POST /auth/processlogin.
     */
    public function processLogin(): void {
        // 1. VALIDAR TOKEN CSRF
        if (empty($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
            $_SESSION['error'] = 'Petición inválida o el token ha expirado.';
            // Redirigir a la URL que muestra el formulario de login
            header('Location: ' . BASE_URL . '/auth/login'); // <-- CORREGIDO
            exit();
        }
        // Opcional: unset($_SESSION['csrf_token']); // Invalidar token después de usarlo

        // 2. OBTENER Y VALIDAR ENTRADAS
        $email = trim($_POST['email'] ?? '');
        $password_from_form = $_POST['password'] ?? '';

        if (empty($email) || empty($password_from_form)) {
            $_SESSION['error'] = 'El correo electrónico y la contraseña son obligatorios.';
             // Redirigir a la URL que muestra el formulario de login
            header('Location: ' . BASE_URL . '/auth/login'); // <-- CORREGIDO
            exit();
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['error'] = 'El formato del correo electrónico no es válido.';
             // Redirigir a la URL que muestra el formulario de login
            header('Location: ' . BASE_URL . '/auth/login'); // <-- CORREGIDO
            exit();
        }

        // 3. PROCESAR LOGIN (VERIFICACIÓN DE CREDENCIALES)
        try {
            // Buscar usuario por email (asegúrate que la columna se llame 'password_hash')
            $query = "SELECT id, username, email, password_hash FROM users WHERE email = :email LIMIT 1";
            $stmt = $this->pdo->prepare($query);
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            $user = $stmt->fetch();

            // --- ¡¡PUNTO MÁS PROBABLE DE FALLO SI LAS CREDENCIALES SON INCORRECTAS!! ---
            if ($user && password_verify($password_from_form, $user['password_hash'])) {
                // ¡ÉXITO! Usuario encontrado Y contraseña coincide

                session_regenerate_id(true); // Seguridad: Prevenir fijación de sesión

                // Establecer variables de sesión
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['logged_in'] = true; // Flag para indicar que está logueado

                // ¡REDIRECCIÓN DE ÉXITO!
                header('Location: ' . BASE_URL . '/dashboard'); // <-- AL DASHBOARD
                exit();

            } else {
                // FALLO: Usuario no encontrado O contraseña incorrecta
                $_SESSION['error'] = 'Correo electrónico o contraseña incorrectos.';
                // Redirigir a la URL que muestra el formulario de login
                header('Location: ' . BASE_URL . '/auth/login'); // <-- CORREGIDO
                exit();
            }
            // --- FIN DEL PUNTO MÁS PROBABLE DE FALLO ---

        } catch (\PDOException $e) { // Error de Base de Datos
            error_log("Error de BD en AuthController::processLogin: " . $e->getMessage());
            $_SESSION['error'] = 'Error del servidor al intentar iniciar sesión.';
            // Redirigir a la URL que muestra el formulario de login
            header('Location: ' . BASE_URL . '/auth/login'); // <-- CORREGIDO
            exit();
        } catch (\Exception $e) { // Otro error inesperado
            error_log("Error inesperado en AuthController::processLogin: " . $e->getMessage());
            $_SESSION['error'] = 'Ocurrió un error inesperado.';
             // Redirigir a la URL que muestra el formulario de login
            header('Location: ' . BASE_URL . '/auth/login'); // <-- CORREGIDO
            exit();
        }
    }

    /**
     * Cierra la sesión del usuario.
     * Llamado por el router para GET o POST /auth/logout.
     */
    public function logout(): void {
        $_SESSION = array(); // Limpiar variables

        if (ini_get("session.use_cookies")) { // Invalidar cookie
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }

        session_destroy(); // Destruir sesión

        // Redirigir a la URL que muestra el formulario de login
        header('Location: ' . BASE_URL . '/auth/login'); // <-- CORREGIDO
        exit();
    }

    // --- MÉTODOS PARA REGISTRO (NECESITARÁS IMPLEMENTARLOS) ---
  
    public function showRegisterForm(): void {
        // Similar a showLoginForm, genera CSRF, prepara variables, carga Views/auth/registro.php
        if (empty($_SESSION['csrf_token'])) { $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); }
        $csrf_token = $_SESSION['csrf_token'];
        $base_url = BASE_URL;
        $error_message = $_SESSION['error'] ?? null;
        unset($_SESSION['error']);
        require_once VIEWS_PATH . '/registro.php'; // Asegúrate que esta vista exista
    }
    /*
    public function processRegistration(): void {
        // 1. Validar CSRF
        // 2. Obtener y VALIDAR datos de $_POST (username, email, password, confirm_password, etc.)
        //    - Campos no vacíos
        //    - Formato email
        //    - Contraseña segura (longitud, etc.)
        //    - Contraseñas coinciden (password === confirm_password)
        // 3. Verificar si email o username YA EXISTEN en la BD (usando UserModel->findByEmail, etc.)
        // 4. Si todo es válido y no existen duplicados:
        //    - Hashear contraseña: $hashedPassword = password_hash($_POST['password'], PASSWORD_DEFAULT);
        //    - Crear usuario en BD (usando UserModel->createUser)
        //    - Si se crea bien:
        //        $_SESSION['success'] = '¡Registro exitoso! Ahora puedes iniciar sesión.';
        //        header('Location: ' . BASE_URL . '/auth/login'); // Redirigir al login
        //        exit();
        //    - Si hay error al crear:
        //        $_SESSION['error'] = 'No se pudo completar el registro.';
        //        header('Location: ' . BASE_URL . '/auth/register'); // Volver al registro
        //        exit();
        // 5. Si hay errores de validación o duplicados:
        //    - Guardar error en $_SESSION['error']
        //    - Opcional: guardar datos del formulario (excepto contraseñas) en sesión para repoblar
        //    - header('Location: ' . BASE_URL . '/auth/register'); // Volver al registro
        //    - exit();
    }
    */
}