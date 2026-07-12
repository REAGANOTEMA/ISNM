<?php
// ISNM Unified Database Connection System
// Connects to all four databases: staffs_db, students_db, website_db, ict

class DatabaseConnection {
    private static $connections = [];
    private static $configs = null;
    private static $dbMap = null;

    private static function getDbMap() {
        if (self::$dbMap === null) {
            require_once __DIR__ . '/../config/database.php';
            $map = [
                'staffs'   => STAFF_DB_NAME,
                'students' => STUDENTS_DB_NAME,
                'website'  => WEBSITE_DB_NAME,
                'ict'      => ICT_DB_NAME,
            ];
            // Reverse mappings for production name compatibility
            foreach ($map as $short => $actual) {
                $map[$actual] = $actual;
            }
            self::$dbMap = $map;
        }
        return self::$dbMap;
    }

    private static function getConfigs() {
        if (self::$configs === null) {
            require_once __DIR__ . '/../config/database.php';
            $staffCfg = [
                'host' => STAFF_DB_HOST,
                'username' => STAFF_DB_USER,
                'password' => STAFF_DB_PASS,
                'port' => STAFF_DB_PORT,
                'charset' => STAFF_DB_CHARSET,
            ];
            $studentsCfg = [
                'host' => STUDENTS_DB_HOST,
                'username' => STUDENTS_DB_USER,
                'password' => STUDENTS_DB_PASS,
                'port' => STUDENTS_DB_PORT,
                'charset' => STUDENTS_DB_CHARSET,
            ];
            $websiteCfg = [
                'host' => WEBSITE_DB_HOST,
                'username' => WEBSITE_DB_USER,
                'password' => WEBSITE_DB_PASS,
                'port' => WEBSITE_DB_PORT,
                'charset' => WEBSITE_DB_CHARSET,
            ];
            $ictCfg = [
                'host' => ICT_DB_HOST,
                'username' => ICT_DB_USER,
                'password' => ICT_DB_PASS,
                'port' => ICT_DB_PORT,
                'charset' => ICT_DB_CHARSET,
            ];
            self::$configs = [
                STAFF_DB_NAME    => $staffCfg,
                STUDENTS_DB_NAME => $studentsCfg,
                WEBSITE_DB_NAME  => $websiteCfg,
                ICT_DB_NAME      => $ictCfg,
                // Production name aliases for backward compat
                'igangaschool_staffs'   => $staffCfg,
                'igangaschool_students' => $studentsCfg,
                'igangaschool_website'  => $websiteCfg,
                'igangaschool_ict'      => $ictCfg,
            ];
        }
        return self::$configs;
    }

    private static function resolveDatabaseName($database) {
        $map = self::getDbMap();
        return $map[$database] ?? $database;
    }

    public static function getConnection($database) {
        $database = self::resolveDatabaseName($database);
        if (!isset(self::$connections[$database])) {
            mysqli_report(MYSQLI_REPORT_OFF);
            $oldLevel = error_reporting(0);

            $configs = self::getConfigs();
            $cfg = $configs[$database] ?? $configs['igangaschool_students'];
            
            $username = $cfg['username'];
            $password = $cfg['password'];
            $port = $cfg['port'] ?? 3306;

            $hosts = array_values(array_unique(array_filter([$cfg['host'], 'localhost', '127.0.0.1'])));
            $ports = array_values(array_unique(array_filter([$port, 3306, 3307])));

            $isLocalHost = in_array($cfg['host'] ?? '', ['localhost', '127.0.0.1', '::1']);

            // Hardcoded hosting credentials â€” always try first, no .env dependency
            $hostingCreds = [
                'igangaschool_students' => ['user' => 'igangaschool_students', 'pass' => '3i%yHc00=cP^ZXwF'],
                'igangaschool_staffs'   => ['user' => 'igangaschool_staffs',   'pass' => '?e=8Dc^D_1Aq9UQd'],
                'igangaschool_website'  => ['user' => 'igangaschool_website',  'pass' => 'tCB0WPn+5l)4!_rY'],
                'igangaschool_ict'      => ['user' => 'igangaschool_ict',      'pass' => 'R_@CPx%OifDKqGSy'],
            ];

            $credSet = [];
            // 1. Hosting creds first (if we know them)
            if (isset($hostingCreds[$database])) {
                $credSet[] = $hostingCreds[$database];
            }
            // 2. Provided credentials
            $credSet[] = ['user' => $username, 'pass' => $password];
            // 3. Root fallbacks (local dev)
            if ($username === 'root' || $password !== ($hostingCreds[$database]['pass'] ?? '')) {
                $rootPass = getenv('STUDENTS_DB_PASS') ?: (getenv('DB_PASS') ?: '');
                if (!empty($rootPass) && $rootPass !== $password) {
                    $credSet[] = ['user' => 'root', 'pass' => $rootPass];
                }
                $credSet[] = ['user' => 'root', 'pass' => ''];
                $credSet[] = ['user' => 'root', 'pass' => 'root'];
            }

            // Deduplicate
            $seen = [];
            $credSet = array_values(array_filter($credSet, function($c) use (&$seen) {
                $key = $c['user'] . '|' . $c['pass'];
                if (isset($seen[$key])) return false;
                $seen[$key] = true;
                return true;
            }));

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
                        if ($conn) { try { $conn->close(); } catch (\Throwable $_) { /* PHP 8+ throws on failed connections */ } }
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
        return self::getConnection('staffs');
    }

    public static function getStudentsConnection() {
        return self::getConnection('students');
    }

    public static function getWebsiteConnection() {
        return self::getConnection('website');
    }

    public static function getICTConnection() {
        return self::getConnection('ict');
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
        } catch (\Throwable $e) {
            error_log("Connection test failed for {$database}: " . $e->getMessage());
            return false;
        }
    }

    public static function testAllConnections() {
        $results = [];
        $map = self::getDbMap();
        foreach (['staffs', 'students', 'website', 'ict'] as $short) {
            $actual = $map[$short];
            $results[$actual] = self::testConnection($short);
        }
        return $results;
    }

    public static function getConnectionInfo($database) {
        $database = self::resolveDatabaseName($database);
        $conn = self::getConnection($database);
        $configs = self::getConfigs();
        $cfg = $configs[$database] ?? $configs['igangaschool_students'];
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
            
            if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
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
            
            if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
            $insert_id = $conn->insert_id;
            $stmt->close();
            
            return $insert_id;
        } catch (\Throwable $e) {
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
        } catch (\Throwable $e) {
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
