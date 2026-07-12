<?php
/**
 * ISNM Database Initialization and Recovery Tool
 * Initializes all databases, creates tables, and validates schema
 * Usage: php database_init.php [--check-only] [--repair] [--verbose]
 */

require_once __DIR__ . '/config/database.php';

class DatabaseInitializer {
    private $verbose = false;
    private $repairMode = false;
    private $checkOnly = false;
    
    public function __construct($args = []) {
        $this->verbose = in_array('--verbose', $args) || in_array('-v', $args);
        $this->repairMode = in_array('--repair', $args) || in_array('-r', $args);
        $this->checkOnly = in_array('--check-only', $args);
    }
    
    private function log($message, $level = 'INFO') {
        $timestamp = date('Y-m-d H:i:s');
        $formatted = "[$timestamp] [$level] $message";
        
        if (php_sapi_name() === 'cli') {
            echo $formatted . "\n";
        } else {
            echo htmlspecialchars($formatted) . "<br>";
        }
        
        error_log($formatted);
    }
    
    private function logError($message) {
        $this->log($message, 'ERROR');
    }
    
    private function logSuccess($message) {
        $this->log($message, 'SUCCESS');
    }
    
    private function logWarning($message) {
        $this->log($message, 'WARN');
    }
    
    /**
     * Check if all database connections are available
     */
    public function checkConnections() {
        $this->log('Checking database connections...');
        
        $functions = [
            'Students' => ['func' => 'getStudentsConnection', 'db' => 'igangaschool_students'],
            'Staff' => ['func' => 'getStaffConnection', 'db' => 'igangaschool_staffs'],
            'Website' => ['func' => 'getWebsiteConnection', 'db' => 'igangaschool_website'],
            'ICT' => ['func' => 'getICTConnection', 'db' => 'igangaschool_ict'],
        ];
        
        $allConnected = true;
        
        foreach ($functions as $name => $config) {
            $func = $config['func'];
            $db = $config['db'];
            
            if (!function_exists($func)) {
                $this->logError("Function $func not found for $name database");
                $allConnected = false;
                continue;
            }
            
            $conn = @$func();
            
            if ($conn && !$conn->connect_error) {
                $this->logSuccess("âœ“ $name database connected ($db)");
                
                // Check tables
                $result = $conn->query("SELECT COUNT(*) as count FROM information_schema.TABLES WHERE TABLE_SCHEMA = '" . $conn->real_escape_string($db) . "'");
                $row = $result->fetch_assoc();
                $tableCount = $row['count'];
                
                if ($this->verbose) {
                    $this->log("  Tables: $tableCount found", 'INFO');
                }
                
                $conn->close();
            } else {
                $this->logError("âœ— $name database connection failed: " . ($conn ? $conn->connect_error : 'NULL connection'));
                if (isset($GLOBALS['isnm_last_db_error'])) {
                    $this->logError("  Details: " . $GLOBALS['isnm_last_db_error']);
                }
                $allConnected = false;
            }
        }
        
        return $allConnected;
    }
    
    /**
     * Validate database schema
     */
    public function validateSchema() {
        $this->log('Validating database schema...');
        
        // Define required tables for each database
        $requiredTables = [
            'Students' => [
                'getStudentsConnection',
                ['students', 'academic_records', 'student_fee_accounts', 'bursar_users']
            ],
            'Staff' => [
                'getStaffConnection',
                ['staff', 'hr_users', 'payroll', 'staff_activity_log']
            ],
            'Website' => [
                'getWebsiteConnection',
                ['contact_submissions', 'website_announcements', 'news']
            ],
            'ICT' => [
                'getICTConnection',
                ['ict_assets', 'ict_asset_categories', 'asset_assignments']
            ],
        ];
        
        $allValid = true;
        
        foreach ($requiredTables as $dbName => [$connFunc, $tables]) {
            if (!function_exists($connFunc)) {
                $this->logError("Connection function $connFunc not found");
                $allValid = false;
                continue;
            }
            
            $conn = @$connFunc();
            
            if (!$conn || $conn->connect_error) {
                $this->logError("Cannot connect to $dbName database for schema validation");
                $allValid = false;
                continue;
            }
            
            $this->log("Validating $dbName database tables...");
            
            foreach ($tables as $table) {
                $result = $conn->query("SHOW TABLES LIKE '$table'");
                
                if ($result && $result->num_rows > 0) {
                    if ($this->verbose) {
                        $this->logSuccess("  âœ“ Table: $table exists");
                    }
                } else {
                    $this->logWarning("  âœ— Table: $table missing");
                    $allValid = false;
                }
            }
            
            $conn->close();
        }
        
        return $allValid;
    }
    
    /**
     * Repair database connections and permissions
     */
    public function repairDatabases() {
        if ($this->checkOnly) {
            $this->logWarning("Repair mode requested but --check-only is set. Skipping repairs.");
            return false;
        }
        
        $this->log('Attempting to repair database connections...');
        
        // Try to ensure all databases exist and have proper users
        $rootPass = getenv('MYSQL_ROOT_PASSWORD') ?: (getenv('STUDENTS_DB_PASS') ?: '');
        $adminConn = @new mysqli('localhost', 'root', $rootPass, '', 3306);
        
        if (!$adminConn || $adminConn->connect_error) {
            $this->logError("Cannot connect as root to perform repairs");
            return false;
        }
        
        // Create databases if they don't exist
        $databases = [
            'igangaschool_students',
            'igangaschool_staffs',
            'igangaschool_website',
            'igangaschool_ict',
        ];
        
        foreach ($databases as $dbName) {
            $sql = "CREATE DATABASE IF NOT EXISTS `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
            if ($adminConn->query($sql)) {
                $this->logSuccess("âœ“ Database '$dbName' ensured");
            } else {
                $this->logError("âœ— Failed to ensure '$dbName': " . $adminConn->error);
            }
        }
        
        // Ensure root user has privileges
        $grants = "GRANT ALL PRIVILEGES ON *.* TO 'root'@'localhost' WITH GRANT OPTION";
        if ($adminConn->query($grants)) {
            $this->logSuccess("âœ“ Root user privileges granted");
        } else {
            $this->logError("âœ— Failed to grant root privileges");
        }
        
        $adminConn->query("FLUSH PRIVILEGES");
        $adminConn->close();
        
        return true;
    }
    
    /**
     * Run all checks and repairs
     */
    public function run() {
        $this->log('=== ISNM Database Initialization Tool ===');
        $this->log('Environment: ' . (defined('APP_ENV') ? APP_ENV : 'unknown'));
        $this->log('Debug Mode: ' . (defined('APP_DEBUG') && APP_DEBUG ? 'ON' : 'OFF'));
        $this->log('');
        
        // Check connections
        $connectionsOk = $this->checkConnections();
        $this->log('');
        
        // Validate schema
        $schemaOk = $this->validateSchema();
        $this->log('');
        
        // Attempt repairs if needed
        if (!$connectionsOk && !$this->checkOnly) {
            $this->log('Some databases are unavailable. Attempting repairs...');
            $this->repairDatabases();
            $this->log('');
            
            // Re-check after repairs
            $this->log('Re-checking connections after repairs...');
            $connectionsOk = $this->checkConnections();
            $this->log('');
        }
        
        // Summary
        $this->log('=== Summary ===');
        $this->logSuccess('Connections: ' . ($connectionsOk ? 'OK' : 'FAILED'));
        $this->logSuccess('Schema: ' . ($schemaOk ? 'OK' : 'NEEDS ATTENTION'));
        
        if ($connectionsOk && $schemaOk) {
            $this->logSuccess('âœ“ All checks passed!');
            return true;
        } else {
            if (!$connectionsOk) {
                $this->logError('Database connections need attention');
            }
            if (!$schemaOk) {
                $this->logWarning('Some tables are missing - import SQL schema files');
            }
            return false;
        }
    }
}

// Parse command line arguments
$args = isset($argv) ? array_slice($argv, 1) : [];
$init = new DatabaseInitializer($args);
$success = $init->run();

exit($success ? 0 : 1);
?>
