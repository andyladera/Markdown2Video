<?php
namespace Dales\Markdown2video\Config;

use Throwable; 

class ErrorHandler
{
    /**
     * @param int 
     * @param string
     * @param string 
     * @param int 
     * @return bool
     * @throws
     */
    public static function errorHandler(int $errno, string $errstr, string $errfile, int $errline): bool
    {
        if (!(error_reporting() & $errno)) {
            return false;
        }
        throw new \ErrorException($errstr, 0, $errno, $errfile, $errline);
    }

    /**
     *
     * @param 
     */
    public static function exceptionHandler(Throwable $exception): void
    {
        if (ob_get_length()) {
            ob_end_clean();
        }
        $isDevelopment = (defined('ENVIRONMENT') && ENVIRONMENT === 'development');

        if (!headers_sent()) {
            http_response_code(500);
        }

        $logMessage = sprintf(
            "[%s] %s: %s in %s on line %d\nStack trace:\n%s\n",
            date('Y-m-d H:i:s'),
            get_class($exception),
            $exception->getMessage(),
            $exception->getFile(),
            $exception->getLine(),
            $exception->getTraceAsString()
        );

        $logFilePath = defined('ROOT_PATH') ? ROOT_PATH . '/logs/application_errors.log' : 'application_errors.log';
        error_log($logMessage, 3, $logFilePath);

        if ($isDevelopment) {
            echo "<h1>Oops! Algo salió mal.</h1>";
            echo "<p><strong>Tipo:</strong> " . get_class($exception) . "</p>";
            echo "<p><strong>Mensaje:</strong> " . htmlspecialchars($exception->getMessage(), ENT_QUOTES, 'UTF-8') . "</p>";
            echo "<p><strong>Archivo:</strong> " . htmlspecialchars($exception->getFile(), ENT_QUOTES, 'UTF-8') . " (Línea: " . $exception->getLine() . ")</p>";
            echo "<h2>Traza de Pila:</h2>";
            echo "<pre style='background-color: #f0f0f0; padding: 10px; border: 1px solid #ccc; overflow-x: auto;'>" . htmlspecialchars($exception->getTraceAsString(), ENT_QUOTES, 'UTF-8') . "</pre>";
        } else {
            $errorPagePath = defined('VIEWS_PATH') ? VIEWS_PATH . 'error/500.php' : '';
            if (file_exists($errorPagePath)) {
                include $errorPagePath;
            } else {
                echo "<h1>Error del Servidor</h1>";
                echo "<p>Lo sentimos, algo salió mal en nuestros servidores. Estamos trabajando para solucionarlo.</p>";
                echo "<p>Por favor, inténtalo de nuevo más tarde.</p>";
            }
        }

        exit(1); 
    }

    public static function shutdownHandler(): void
    {
        $error = error_get_last();
        if ($error !== null && in_array($error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR, E_RECOVERABLE_ERROR, E_PARSE])) {
            self::exceptionHandler(new \ErrorException($error['message'], 0, $error['type'], $error['file'], $error['line']));
        }
    }

    public static function init(): void
    {
        set_error_handler([self::class, 'errorHandler']);
        set_exception_handler([self::class, 'exceptionHandler']);
        register_shutdown_function([self::class, 'shutdownHandler']);
    }
}