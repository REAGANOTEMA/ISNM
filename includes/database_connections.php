<?php
// ISNM Unified Database Connection System
// Connects to all four databases: staffs_db, students_db, website_db, ict

class DatabaseConnection {
    private static $connections = [];
    private static $configs = null;
    private static $dbMap = [
        'staffs'   => 'igangaschoolofl_staffs_db',
        'students' => 'igangaschoolofl_students_db',
        'website'  => 'igangaschoolofl_website_db',
        'ict'      => 'igangaschoolofl_ict',
    ];

    private static function getConfigs() {
        if (self::$configs === null) {
            require_once __DIR__ . '/../config/database.php';
            self::$configs = [
                'igangaschoolofl_staffs_db' => [
                    'host' => STAFF_DB_HOST,
                    'username' => STAFF_DB_USER,
                    'password' => STAFF_DB_PASS,
                    'port' => STAFF_DB_PORT,
                    'charset' => STAFF_DB_CHARSET,
                ],
'igangaschoolofl_students_db' => [
                     'host' => STUDENTS_DB_HOST,
                     'username' => STUDENTS_DB_USER,
                     'password' => STUDENTS_DB_PASS,
                     'port' => STUDENTS_DB_PORT,
                     'charset' => DB_CHARSET,
                 ],
                'igangaschoolofl_website_db' => [
                    'host' => WEBSITE_DB_HOST,
                    'username' => WEBSITE_DB_USER,
                    'password' => WEBSITE_DB_PASS,
                    'port' => WEBSITE_DB_PORT,
                    'charset' => WEBSITE_DB_CHARSET,
                ],
'igangaschoolofl_ict' => [
                     'host' => ICT_DB_HOST,
                     'username' => ICT_DB_USER,
                     'password' => ICT_DB_PASS,
                     'port' => ICT_DB_PORT,
                     'charset' => ICT_DB_CHARSET,
                 ],
            ];
        }
        return self::$configs;
    }

    private static function resolveDatabaseName($database) {
        return self::$dbMap[$database] ?? $database;
    }

    public static function getConnection($database) {
        $database = self::resolveDatabaseName($database);
        if (!isset(self::$connections[$database])) {
            $oldLevel = error_reporting(0);

            $configs = self::getConfigs();
            $cfg = $configs[$database] ?? $configs['igangaschoolofl_students_db'];
            
            $username = $cfg['username'];
            $password = $cfg['password'];
            $port = $cfg['port'] ?? 3306;

            $hosts = array_values(array_unique(array_filter([$cfg['host'], 'localhost', '127.0.0.1'])));
            $ports = array_values(array_unique(array_filter([$port, 3306, 3307])));

            // Hosting fallback credentials (used when .env missing/overridden)
            $knownCreds = [
                'igangaschoolofl_staffs_db'   => ['user'=>'igangaschoolofl_staffs_db',   'pass'=>''],
                'igangaschoolofl_students_db' => ['user'=>'igangaschoolofl_students_db', 'pass'=>''],
                'igangaschoolofl_website_db'  => ['user'=>'igangaschoolofl_website_db',  'pass'=>''],
                'igangaschoolofl_ict'         => ['user'=>'igangaschoolofl_ict',          'pass'=>''],
            ];
            $credSet = [['user' => $username, 'pass' => $password]];
            if (isset($knownCreds[$database]) && $knownCreds[$database]['user'] !== $username) {
                array_unshift($credSet, $knownCreds[$database]);
            }

            foreach ($credSet as $cred) {
                $u = $cred['user'];
                $p = $cred['pass'];
                foreach ($hosts as $h) {
                    foreach ($ports as $pt) {
                        $pt = (int)$pt;
                        $conn = @new mysqli($h, $u, $p, $database, $pt);
                        if ($conn && !$conn->connect_error) {
                            $conn->set_charset($cfg['charset']);
                            self::$connections[$database] = $conn;
                            error_reporting($oldLevel);
                            return self::$connections[$database];
                        }
                        if ($conn) $conn->close();
                        $conn = null;
                    }
                }
            }
            
            error_reporting($oldLevel);
            $err = "Connection to {$database} failed - no viable credentials";
            error_log("Database connection error: " . $err);
            return null;
        }
        
        return self::$connections[$database];
    }

    public static function getStaffConnection() {
        return self::getConnection('igangaschoolofl_staffs_db');
    }

    public static function getStudentsConnection() {
        return self::getConnection('igangaschoolofl_students_db');
    }

    public static function getWebsiteConnection() {
        return self::getConnection('igangaschoolofl_website_db');
    }

    public static function getICTConnection() {
        return self::getConnection('igangaschoolofl_ict');
    }

    public static function closeConnection($database) {
        $database = self::resolveDatabaseName($database);
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
        $database = self::resolveDatabaseName($database);
        try {
            $conn = self::getConnection($database);
            $result = $conn->query("SELECT 1");
            return $result !== false;
        } catch (Exception $e) {
            error_log("Connection test failed for {$database}: " . $e->getMessage());
            return false;
        }
    }

    public static function testAllConnections() {
        $results = [];
        $databases = ['igangaschoolofl_staffs_db', 'igangaschoolofl_students_db', 'igangaschoolofl_website_db', 'igangaschoolofl_ict'];
        
        foreach ($databases as $database) {
            $results[$database] = self::testConnection($database);
        }
        
        return $results;
    }

    public static function getConnectionInfo($database) {
        $database = self::resolveDatabaseName($database);
        $conn = self::getConnection($database);
        $configs = self::getConfigs();
        $cfg = $configs[$database] ?? $configs['igangaschoolofl_students_db'];
        return [
            'host' => $cfg['host'],
            'database' => $database,
            'charset' => $cfg['charset'],
            'connected' => $conn->ping(),
            'server_info' => $conn->get_server_info(),
            'client_info' => $conn->get_client_info()
        ];
    }

    public static function executeQuery($database, $sql, $params = [], $types = '') {
        $database = self::resolveDatabaseName($database);
        try {
            $conn = self::getConnection($database);
            $stmt = $conn->prepare($sql);
            
            if (!$stmt) {
                error_log("Query prepare failed in {$database}: " . $conn->error);
                return false;
            }
            
            if (!empty($params) && !empty($types)) {
                $stmt->bind_param($types, ...$params);
            }
            
            $stmt->execute();
            $result = $stmt->get_result();
            $stmt->close();
            
            return $result;
        } catch (Throwable $e) {
            error_log("Query execution error in {$database}: " . $e->getMessage());
            return false;
        }
    }

    public static function executeInsert($database, $sql, $params = [], $types = '') {
        $database = self::resolveDatabaseName($database);
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
        $database = self::resolveDatabaseName($database);
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
        $database = self::resolveDatabaseName($database);
        $conn = self::getConnection($database);
        $conn->begin_transaction();
    }

    public static function commitTransaction($database) {
        $database = self::resolveDatabaseName($database);
        $conn = self::getConnection($database);
        $conn->commit();
    }

    public static function rollbackTransaction($database) {
        $database = self::resolveDatabaseName($database);
        $conn = self::getConnection($database);
        $conn->rollback();
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

// Test all connections on include
if (defined('TEST_CONNECTIONS') && TEST_CONNECTIONS) {
    $test_results = DatabaseConnection::testAllConnections();
    echo "<h3>Database Connection Test Results</h3>";
    echo "<pre>";
    print_r($test_results);
    echo "</pre>";
}
?>
