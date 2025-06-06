<?php
namespace Dales\Markdown2video\Config; 

use PDO;
use PDOException;
use Exception; 

class Database {
    private string $host;
    private string $db_name;
    private string $username;
    private string $password;
    private string $port;
    private string $charset;
    private array $options;

    private ?PDO $pdoInstance = null; 

    /**

     * @param string|null $host
     * @param string|null $db_name
     * @param string|null $username
     * @param string|null $password
     * @param string|null $port
     * @param string $charset
     * @param array $options Opciones adicionales de PDO
     */
    public function __construct(
        ?string $host = null,
        ?string $db_name = null,
        ?string $username = null,
        ?string $password = null,
        ?string $port = null,
        string $charset = 'utf8mb4',
        array $options = []
    ) {

        $this->host = $host ?? $_ENV['DB_HOST'] ?? 'markdown2video-db.cbeq24kc8xe3.us-east-2.rds.amazonaws.com';
        $this->db_name = $db_name ?? $_ENV['DB_NAME'] ?? 'markdown2video';
        $this->username = $username ?? $_ENV['DB_USER'] ?? 'admin';
        $this->password = $password ?? $_ENV['DB_PASS'] ?? 'admin1234'; 
        $this->port = $port ?? $_ENV['DB_PORT'] ?? '3306';
        $this->charset = $charset;

        $defaultOptions = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        $this->options = array_replace($defaultOptions, $options); 
    }

    /**

     * @return PDO La instancia de PDO.
     * @throws PDOException Si la conexión falla.
     */
    public function getConnection(): PDO {
        if ($this->pdoInstance === null) {
            $dsn = "mysql:host={$this->host};dbname={$this->db_name};port={$this->port};charset={$this->charset}";
            try {
                $this->pdoInstance = new PDO($dsn, $this->username, $this->password, $this->options);
            } catch (PDOException $e) {
                error_log("Error de conexión a la base de datos: " . $e->getMessage() . " (DSN: " . $dsn . ")");
                throw new PDOException("No se pudo conectar al servidor de datos. Por favor, inténtelo más tarde.", (int)$e->getCode(), $e);
            }
        }
        return $this->pdoInstance;
    }

    public function disconnect(): void {
        $this->pdoInstance = null;
    }

    /**
     *
     * @param string 
     * @param array
     * @return 
     * @throws
     */
    public function query(string $sql, array $params = []): \PDOStatement {
        try {
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            error_log("Error en consulta: " . $e->getMessage() . " | SQL: " . $sql . " | Params: " . json_encode($params));
            throw $e; 
        }
    }

    /**
     *
     * @param string $sql
     * @param array $params
     * @return array
     * @throws PDOException
     */
    public function selectAll(string $sql, array $params = []): array {
        return $this->query($sql, $params)->fetchAll();
    }

    /**
     *
     * @param string $sql
     * @param array $params
     * @return mixed La fila como array asociativo, o false si no hay resultados.
     * @throws PDOException
     */
    public function selectOne(string $sql, array $params = []) { // puede devolver array o false
        return $this->query($sql, $params)->fetch();
    }

    /**
     *
     * @param string 
     * @param array 
     * @return string|false 
     * @throws 
     */
    public function insert(string $sql, array $params = []) {
        $this->query($sql, $params);
        return $this->pdoInstance->lastInsertId(); 
    }

    /**
     *
     * @param string 
     * @param array 
     * @return int 
     * @throws 
     */
    public function execute(string $sql, array $params = []): int {
        return $this->query($sql, $params)->rowCount();
    }

    public function beginTransaction(): bool {
        return $this->getConnection()->beginTransaction();
    }

    public function commit(): bool {
        return $this->getConnection()->commit();
    }

    public function rollBack(): bool {
        return $this->getConnection()->rollBack();
    }

    public function inTransaction(): bool {
        return $this->pdoInstance ? $this->pdoInstance->inTransaction() : false;
    }
}