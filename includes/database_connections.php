<?php
// ISNM Unified Database Connection System
// Connects to all three databases: staffs_db, students_db, website_db

class DatabaseConnection {
    private static $connections = [];
    private static $config = null;

    private static function getConfig() {
        if (self::$config === null) {
            require_once __DIR__ . '/../config/database.php';
            self::$config = [
                'host' => DB_HOST,
                'username' => DB_USER,
                'password' => DB_PASS,
                'charset' => DB_CHARSET,
            ];
        }
        return self::$config;
    }

    public static function getConnection($database) {
        if (!isset(self::$connections[$database])) {
            try {
                $cfg = self::getConfig();
                $conn = new mysqli(
                    $cfg['host'],
                    $cfg['username'],
                    $cfg['password'],
                    $database
                );

                if ($conn->connect_error) {
                    throw new Exception("Connection to {$database} failed: " . $conn->connect_error);
                }

                $conn->set_charset($cfg['charset']);
                self::$connections[$database] = $conn;

                // Log successful connection
                error_log("Successfully connected to {$database} database");
                
            } catch (Exception $e) {
                error_log("Database connection error: " . $e->getMessage());
                throw $e;
            }
        }
        
        return self::$connections[$database];
    }

    public static function getStaffConnection() {
        return self::getConnection('staffs_db');
    }

    public static function getStudentsConnection() {
        return self::getConnection('students_db');
    }

    public static function getWebsiteConnection() {
        return self::getConnection('website_db');
    }

    public static function closeConnection($database) {
        if (isset(self::$connections[$database])) {
            self::$connections[$database]->close();
            unset(self::$connections[$database]);
            error_log("Closed connection to {$database} database");
        }
    }

    public static function closeAllConnections() {
        foreach (self::$connections as $database => $connection) {
            $connection->close();
            error_log("Closed connection to {$database} database");
        }
        self::$connections = [];
    }

    public static function testConnection($database) {
        try {
            $conn = self::getConnection($database);
            $result = $conn->query("SELECT 1");
            $conn->close();
            return $result !== false;
        } catch (Exception $e) {
            error_log("Connection test failed for {$database}: " . $e->getMessage());
            return false;
        }
    }

    public static function testAllConnections() {
        $results = [];
        $databases = ['staffs_db', 'students_db', 'website_db'];
        
        foreach ($databases as $database) {
            $results[$database] = self::testConnection($database);
        }
        
        return $results;
    }

    public static function getConnectionInfo($database) {
        $conn = self::getConnection($database);
        return [
            'host' => self::$config['host'],
            'database' => $database,
            'charset' => self::$config['charset'],
            'connected' => $conn->ping(),
            'server_info' => $conn->get_server_info(),
            'client_info' => $conn->get_client_info()
        ];
    }

    public static function executeQuery($database, $sql, $params = [], $types = '') {
        try {
            $conn = self::getConnection($database);
            $stmt = $conn->prepare($sql);
            
            if (!empty($params) && !empty($types)) {
                $stmt->bind_param($types, ...$params);
            }
            
            $stmt->execute();
            $result = $stmt->get_result();
            $stmt->close();
            
            return $result;
        } catch (Exception $e) {
            error_log("Query execution error in {$database}: " . $e->getMessage());
            return false;
        }
    }

    public static function executeInsert($database, $sql, $params = [], $types = '') {
        try {
            $conn = self::getConnection($database);
            $stmt = $conn->prepare($sql);
            
            if (!empty($params) && !empty($types)) {
                $stmt->bind_param($types, ...$params);
            }
            
            $stmt->execute();
            $insert_id = $conn->insert_id;
            $stmt->close();
            
            return $insert_id;
        } catch (Exception $e) {
            error_log("Insert execution error in {$database}: " . $e->getMessage());
            return false;
        }
    }

    public static function executeUpdate($database, $sql, $params = [], $types = '') {
        try {
            $conn = self::getConnection($database);
            $stmt = $conn->prepare($sql);
            
            if (!empty($params) && !empty($types)) {
                $stmt->bind_param($types, ...$params);
            }
            
            $result = $stmt->execute();
            $affected_rows = $conn->affected_rows;
            $stmt->close();
            
            return $affected_rows;
        } catch (Exception $e) {
            error_log("Update execution error in {$database}: " . $e->getMessage());
            return false;
        }
    }

    public static function sanitizeInput($input) {
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }

    public static function beginTransaction($database) {
        $conn = self::getConnection($database);
        $conn->begin_transaction();
    }

    public static function commitTransaction($database) {
        $conn = self::getConnection($database);
        $conn->commit();
    }

    public static function rollbackTransaction($database) {
        $conn = self::getConnection($database);
        $conn->rollback();
    }

    public static function escapeString($database, $string) {
        $conn = self::getConnection($database);
        return $conn->real_escape_string($string);
    }
}

// Legacy compatibility functions
if (!function_exists('getStaffConnection')) {
    function getStaffConnection() {
        return DatabaseConnection::getStaffConnection();
    }
}

if (!function_exists('getStudentsConnection')) {
    function getStudentsConnection() {
        return DatabaseConnection::getStudentsConnection();
    }
}

if (!function_exists('getWebsiteConnection')) {
    function getWebsiteConnection() {
        return DatabaseConnection::getWebsiteConnection();
    }
}

if (!function_exists('executeQuery')) {
    function executeQuery($database, $sql, $params = [], $types = '') {
        return DatabaseConnection::executeQuery($database, $sql, $params, $types);
    }
}

if (!function_exists('executeInsert')) {
    function executeInsert($database, $sql, $params = [], $types = '') {
        return DatabaseConnection::executeInsert($database, $sql, $params, $types);
    }
}

if (!function_exists('executeUpdate')) {
    function executeUpdate($database, $sql, $params = [], $types = '') {
        return DatabaseConnection::executeUpdate($database, $sql, $params, $types);
    }
}

if (!function_exists('sanitizeInput')) {
    function sanitizeInput($input) {
        return DatabaseConnection::sanitizeInput($input);
    }
}

if (!function_exists('escapeString')) {
    function escapeString($database, $string) {
        return DatabaseConnection::escapeString($database, $string);
    }
}

// Test all connections on include
if (defined('TEST_CONNECTIONS') && TEST_CONNECTIONS) {
    $test_results = DatabaseConnection::testAllConnections();
    echo "<h3>Database Connection Test Results</h3>";
    echo "<pre>";
    print_r($test_results);
    echo "</pre>";
}
?>
