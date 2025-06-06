<?php
// 1. NAMESPACE
namespace Dales\Markdown2video\Models;

// 2. IMPORTAR CLASES NECESARIAS
use PDO; // Para type hinting y constantes PDO
// No necesitas 'use Dales\Markdown2video\Config\Database;' si solo recibes la conexión PDO
// use Exception; // Si planeas lanzar excepciones personalizadas desde el modelo

class UserModel {
    private PDO $pdo; // Almacena la conexión PDO inyectada

    /**
     * Constructor que recibe la conexión PDO.
     * @param PDO $pdo La instancia de la conexión PDO.
     */
    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Obtiene un usuario por su ID.
     *
     * @param int $userId El ID del usuario.
     * @return array|null Los datos del usuario como array asociativo, o null si no se encuentra.
     */
    public function getUserById(int $userId): ?array { // Especificar tipo de retorno
        // Asegúrate que los nombres de columna sean los correctos en tu tabla 'users'
        // Cambié 'nombre_usuario' a 'username' y 'correo' a 'email' para consistencia con AuthController
        // y 'usuarios' a 'users'. Ajusta según tu esquema real.
        $query = "SELECT id, username, email, telefono, nombre, apellido, fecha_nacimiento, dni, estado 
                  FROM users 
                  WHERE id = :id 
                  LIMIT 1";
        try {
            $stmt = $this->pdo->prepare($query);
            $stmt->bindParam(':id', $userId, PDO::PARAM_INT); // Especificar tipo de parámetro
            $stmt->execute();
            $user = $stmt->fetch(); // Por defecto es PDO::FETCH_ASSOC si se configuró en Database.php
            return $user ?: null; // Devuelve el usuario o null si fetch() devuelve false
        } catch (\PDOException $e) {
            // Loguear el error y/o lanzar una excepción personalizada si es necesario
            error_log("Error en UserModel::getUserById: " . $e->getMessage());
            // Podrías retornar null o lanzar la excepción dependiendo de cómo quieras manejar errores.
            // throw new \RuntimeException("Error al obtener el usuario.", 0, $e);
            return null;
        }
    }

    /**
     * Actualiza los datos de un usuario.
     * ¡IMPORTANTE! La validación de $data debe hacerse ANTES de llamar a este método,
     * usualmente en el controlador o en una capa de servicio.
     *
     * @param int $userId El ID del usuario a actualizar.
     * @param array $data Un array asociativo con los datos a actualizar (ej. ['nombre' => 'Nuevo Nombre']).
     * @return bool True si la actualización fue exitosa (afectó filas), false en caso contrario.
     */
    public function updateUser(int $userId, array $data): bool {
        // Lista de campos permitidos para actualizar para evitar inyecciones de campos no deseados
        $allowedFields = ['username', 'email', 'telefono', 'nombre', 'apellido', 'fecha_nacimiento', 'dni', 'estado'];
        $updateFields = [];
        $params = [':id' => $userId];

        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $data)) { // Usar array_key_exists para permitir valores null o vacíos si son válidos
                $updateFields[] = "$field = :$field";
                $params[":$field"] = $data[$field];
            }
        }

        if (empty($updateFields)) {
            // No hay campos válidos para actualizar
            return false;
        }

        $query = "UPDATE users SET " . implode(', ', $updateFields) . " WHERE id = :id";

        try {
            $stmt = $this->pdo->prepare($query);
            // No necesitas bindParam para cada uno aquí si los pasas directamente a execute,
            // pero si algunos fueran de tipo INT, sería bueno.
            // Para los datos en $params, PDO usualmente maneja bien los tipos en execute().
            $stmt->execute($params);
            return $stmt->rowCount() > 0; // Devuelve true si alguna fila fue afectada
        } catch (\PDOException $e) {
            error_log("Error en UserModel::updateUser: " . $e->getMessage());
            // throw new \RuntimeException("Error al actualizar el usuario.", 0, $e);
            return false;
        }
    }

    /**
     * Crea un nuevo usuario en la base de datos.
     * ¡IMPORTANTE! La validación de datos y la existencia previa de email/username
     * debe hacerse ANTES de llamar a este método.
     * La contraseña DEBE ser la contraseña en texto plano para ser hasheada aquí.
     *
     * @param string $username
     * @param string $email
     * @param string $plainPassword La contraseña en texto plano.
     * @param array $additionalData Otros datos como nombre, apellido, etc.
     * @return string|false El ID del nuevo usuario creado, o false en caso de error.
     */
    public function createUser(string $username, string $email, string $plainPassword, array $additionalData = []) {
        // Hashear la contraseña
        $password_hash = password_hash($plainPassword, PASSWORD_DEFAULT);
        if ($password_hash === false) {
            // Error al hashear, no debería ocurrir con PASSWORD_DEFAULT a menos que haya un problema grave
            error_log("Error al hashear la contraseña para el usuario: " . $email);
            return false;
        }

        $fields = ['username', 'email', 'password_hash'];
        $placeholders = [':username', ':email', ':password_hash'];
        $params = [
            ':username' => $username,
            ':email' => $email,
            ':password_hash' => $password_hash
        ];

        // Añadir campos adicionales permitidos
        $allowedAdditionalFields = ['telefono', 'nombre', 'apellido', 'fecha_nacimiento', 'dni', 'estado'];
        foreach ($allowedAdditionalFields as $field) {
            if (array_key_exists($field, $additionalData)) {
                $fields[] = $field;
                $placeholders[] = ":$field";
                $params[":$field"] = $additionalData[$field];
            }
        }

        $query = "INSERT INTO users (" . implode(', ', $fields) . ") VALUES (" . implode(', ', $placeholders) . ")";

        try {
            $stmt = $this->pdo->prepare($query);
            $stmt->execute($params);
            return $this->pdo->lastInsertId();
        } catch (\PDOException $e) {
            // Verificar si el error es por duplicado de email o username (código SQLSTATE 23000)
            if ($e->getCode() == 23000) { // Código para violación de integridad (ej. UNIQUE constraint)
                error_log("Error de duplicado en UserModel::createUser: " . $e->getMessage());
                // Podrías lanzar una excepción específica para duplicados
                // throw new DuplicateEntryException("El email o nombre de usuario ya existe.");
            } else {
                error_log("Error en UserModel::createUser: " . $e->getMessage());
            }
            // throw new \RuntimeException("Error al crear el usuario.", 0, $e);
            return false;
        }
    }

    /**
     * Elimina un usuario por su ID.
     *
     * @param int $userId El ID del usuario a eliminar.
     * @return bool True si la eliminación fue exitosa, false en caso contrario.
     */
    public function deleteUser(int $userId): bool {
        $query = "DELETE FROM users WHERE id = :id";
        try {
            $stmt = $this->pdo->prepare($query);
            $stmt->bindParam(':id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->rowCount() > 0;
        } catch (\PDOException $e) {
            error_log("Error en UserModel::deleteUser: " . $e->getMessage());
            // throw new \RuntimeException("Error al eliminar el usuario.", 0, $e);
            return false;
        }
    }

    /**
     * Encuentra un usuario por email (útil para verificar existencia antes de registrar).
     *
     * @param string $email
     * @return array|null
     */
    public function findByEmail(string $email): ?array {
        $query = "SELECT id, username, email, password_hash FROM users WHERE email = :email LIMIT 1";
        try {
            $stmt = $this->pdo->prepare($query);
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            $user = $stmt->fetch();
            return $user ?: null;
        } catch (\PDOException $e) {
            error_log("Error en UserModel::findByEmail: " . $e->getMessage());
            return null;
        }
    }

    // Podrías añadir más métodos según necesites:
    // - findByUsername(string $username)
    // - getAllUsers(int $limit, int $offset)
    // - countAllUsers()
}