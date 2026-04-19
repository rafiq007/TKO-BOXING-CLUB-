<?php
/**
 * Database Connection Handler
 * Uses PDO with prepared statements for security
 * PHP 8.2+ Compatible
 */

// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'u788418452_gym_management');
define('DB_USER', 'u788418452_af');  // Change this
define('DB_PASS', '71gfG:cO');  // Change this
define('DB_CHARSET', 'utf8mb4');

// Error reporting for development (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

class Database {
    private static ?PDO $instance = null;
    private static array $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::ATTR_PERSISTENT         => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
    ];

    /**
     * Private constructor to prevent direct instantiation
     */
    private function __construct() {}

    /**
     * Prevent cloning of the instance
     */
    private function __clone() {}

    /**
     * Prevent unserializing of the instance
     */
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }

    /**
     * Get database connection instance (Singleton pattern)
     * 
     * @return PDO
     * @throws PDOException
     */
    public static function getConnection(): PDO {
        if (self::$instance === null) {
            try {
                $dsn = sprintf(
                    "mysql:host=%s;dbname=%s;charset=%s",
                    DB_HOST,
                    DB_NAME,
                    DB_CHARSET
                );
                
                self::$instance = new PDO($dsn, DB_USER, DB_PASS, self::$options);
                
            } catch (PDOException $e) {
                // Log error in production, display in development
                error_log("Database connection failed: " . $e->getMessage());
                throw new PDOException("Database connection failed. Please contact support.");
            }
        }
        
        return self::$instance;
    }

    /**
     * Close database connection
     */
    public static function closeConnection(): void {
        self::$instance = null;
    }

    /**
     * Execute a query and return results
     * 
     * @param string $query SQL query with placeholders
     * @param array $params Parameters to bind
     * @return array|false
     */
    public static function query(string $query, array $params = []): array|false {
        try {
            $pdo = self::getConnection();
            $stmt = $pdo->prepare($query);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Query failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Execute a query and return single row
     * 
     * @param string $query SQL query with placeholders
     * @param array $params Parameters to bind
     * @return array|false
     */
    public static function querySingle(string $query, array $params = []): array|false {
        try {
            $pdo = self::getConnection();
            $stmt = $pdo->prepare($query);
            $stmt->execute($params);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Query failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Execute insert/update/delete query
     * 
     * @param string $query SQL query with placeholders
     * @param array $params Parameters to bind
     * @return bool
     */
    public static function execute(string $query, array $params = []): bool {
        try {
            $pdo = self::getConnection();
            $stmt = $pdo->prepare($query);
            return $stmt->execute($params);
        } catch (PDOException $e) {
            error_log("Execute failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get last insert ID
     * 
     * @return string
     */
    public static function lastInsertId(): string {
        return self::getConnection()->lastInsertId();
    }

    /**
     * Begin transaction
     */
    public static function beginTransaction(): bool {
        return self::getConnection()->beginTransaction();
    }

    /**
     * Commit transaction
     */
    public static function commit(): bool {
        return self::getConnection()->commit();
    }

    /**
     * Rollback transaction
     */
    public static function rollback(): bool {
        return self::getConnection()->rollBack();
    }
}

/**
 * Helper function to get database connection
 * 
 * @return PDO
 */
function getDB(): PDO {
    return Database::getConnection();
}

/**
 * Sanitize input data
 * 
 * @param mixed $data
 * @return mixed
 */
function sanitize_input(mixed $data): mixed {
    if (is_array($data)) {
        return array_map('sanitize_input', $data);
    }
    
    if (is_string($data)) {
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    }
    
    return $data;
}

/**
 * Generate CSRF token
 * 
 * @return string
 */
function generate_csrf_token(): string {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 * 
 * @param string $token
 * @return bool
 */
function verify_csrf_token(string $token): bool {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Hash password using BCRYPT
 * 
 * @param string $password
 * @return string
 */
function hash_password(string $password): string {
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
}

/**
 * Verify password
 * 
 * @param string $password
 * @param string $hash
 * @return bool
 */
function verify_password(string $password, string $hash): bool {
    return password_verify($password, $hash);
}

/**
 * Log activity
 * 
 * @param int|null $userId
 * @param string $action
 * @param string|null $tableName
 * @param int|null $recordId
 * @param string|null $description
 */
function log_activity(?int $userId, string $action, ?string $tableName = null, ?int $recordId = null, ?string $description = null): void {
    try {
        $query = "INSERT INTO activity_log (user_id, action_type, table_name, record_id, description, ip_address, user_agent) 
                  VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        Database::execute($query, [
            $userId,
            $action,
            $tableName,
            $recordId,
            $description,
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);
    } catch (Exception $e) {
        error_log("Failed to log activity: " . $e->getMessage());
    }
}

/**
 * Get system setting
 * 
 * @param string $key
 * @param mixed $default
 * @return mixed
 */
function get_setting(string $key, mixed $default = null): mixed {
    $result = Database::querySingle(
        "SELECT setting_value FROM system_settings WHERE setting_key = ?",
        [$key]
    );
    
    return $result ? $result['setting_value'] : $default;
}

// Initialize session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_httponly' => true,
        'cookie_secure' => isset($_SERVER['HTTPS']),
        'cookie_samesite' => 'Strict'
    ]);
}

?>
