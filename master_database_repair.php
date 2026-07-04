<?php
/**
 * ISNM Master Database Repair & Initialization Script
 * Comprehensive tool to initialize, repair, and validate all databases
 * This is THE script to run when encountering database unavailability errors
 * 
 * Usage: php master_database_repair.php [--import-sql] [--skip-validation] [--verbose]
 */

require_once __DIR__ . '/config/database.php';

class MasterDatabaseRepair {
    private $verbose = false;
    private $importSql = false;
    private $skipValidation = false;
    private $sqlDir = __DIR__ . '/sql';
    private $allSuccess = true;
    
    private $databases = [
        'igangaschoolofl_students_db' => [
            'func' => 'getStudentsConnection',
            'user' => 'root',
            'pass' => 'ReagaN23#',
            'sqlFile' => 'students/igangaschoolofl_students_db.sql',
            'tables' => [
                'students' => 'Student profiles and enrollment',
                'academic_records' => 'Academic performance records',
                'student_fee_accounts' => 'Student financial accounts',
                'bursar_users' => 'Finance staff accounts',
                'courses' => 'Course definitions',
            ],
        ],
        'igangaschoolofl_staffs_db' => [
            'func' => 'getStaffConnection',
            'user' => 'root',
            'pass' => 'ReagaN23#',
            'sqlFile' => 'staffs/igangaschoolofl_staffs_db.sql',
            'tables' => [
                'staff' => 'Staff profiles',
                'hr_users' => 'HR staff accounts',
                'payroll' => 'Salary and compensation',
                'staff_activity_log' => 'Activity audit trail',
                'departments' => 'Organizational departments',
            ],
        ],
        'igangaschoolofl_website_db' => [
            'func' => 'getWebsiteConnection',
            'user' => 'root',
            'pass' => 'ReagaN23#',
            'sqlFile' => 'website/igangaschoolofl_website_db.sql',
            'tables' => [
                'contact_submissions' => 'Website contact form submissions',
                'website_announcements' => 'Website announcements and news',
                'news' => 'News articles',
            ],
        ],
        'igangaschoolofl_ict' => [
            'func' => 'getICTConnection',
            'user' => 'root',
            'pass' => 'ReagaN23#',
            'sqlFile' => 'ict/igangaschoolofl_ict.sql',
            'tables' => [
                'ict_assets' => 'IT equipment inventory',
                'ict_asset_categories' => 'Asset categories',
                'asset_assignments' => 'Equipment assignments',
                'lab_computers' => 'Computer lab inventory',
            ],
        ],
    ];
    
    public function __construct($args = []) {
        $this->verbose = in_array('--verbose', $args) || in_array('-v', $args);
        $this->importSql = in_array('--import-sql', $args);
        $this->skipValidation = in_array('--skip-validation', $args);
    }
    
    private function log($message, $level = 'INFO') {
        $timestamp = date('Y-m-d H:i:s');
        $prefix = '';
        $color = '';
        
        switch ($level) {
            case 'ERROR':
                $prefix = '❌';
                break;
            case 'SUCCESS':
                $prefix = '✓';
                break;
            case 'WARN':
                $prefix = '⚠';
                break;
            case 'INFO':
                $prefix = 'ℹ';
                break;
        }
        
        $formatted = "[$timestamp] $prefix [$level] $message";
        
        if (php_sapi_name() === 'cli') {
            echo $formatted . "\n";
        } else {
            echo htmlspecialchars($formatted) . "<br>";
        }
        
        error_log($formatted);
    }
    
    private function logError($message) {
        $this->allSuccess = false;
        $this->log($message, 'ERROR');
    }
    
    private function logSuccess($message) {
        $this->log($message, 'SUCCESS');
    }
    
    private function logWarning($message) {
        $this->log($message, 'WARN');
    }
    
    private function logInfo($message) {
        $this->log($message, 'INFO');
    }
    
    /**
     * Step 1: Establish admin connection for database creation
     */
    private function step1_EstablishAdminConnection() {
        $this->logInfo('=== Step 1: Establishing Admin Connection ===');
        
        $adminConn = @new mysqli('localhost', 'root', 'ReagaN23#', '', 3306);
        
        if (!$adminConn || $adminConn->connect_error) {
            $this->logError("Cannot connect as root user: " . ($adminConn ? $adminConn->connect_error : 'Unknown error'));
            $this->logInfo('Ensure MySQL is running and root password is correct');
            return null;
        }
        
        $this->logSuccess('Connected as root user');
        $this->logInfo('MySQL Version: ' . $adminConn->get_server_info());
        
        return $adminConn;
    }
    
    /**
     * Step 2: Create all databases if they don't exist
     */
    private function step2_CreateDatabases($adminConn) {
        $this->logInfo('');
        $this->logInfo('=== Step 2: Creating Databases ===');
        
        if (!$adminConn) return false;
        
        $created = 0;
        
        foreach (array_keys($this->databases) as $dbName) {
            $sql = "CREATE DATABASE IF NOT EXISTS `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
            
            if ($adminConn->query($sql)) {
                $this->logSuccess("Database '$dbName' ready");
                $created++;
            } else {
                $this->logError("Failed to create database '$dbName': " . $adminConn->error);
            }
        }
        
        $this->logInfo("Total: $created databases processed");
        return true;
    }
    
    /**
     * Step 3: Grant permissions
     */
    private function step3_GrantPermissions($adminConn) {
        $this->logInfo('');
        $this->logInfo('=== Step 3: Granting Permissions ===');
        
        if (!$adminConn) return false;
        
        // Grant root full privileges
        $grants = [
            "GRANT ALL PRIVILEGES ON *.* TO 'root'@'localhost' WITH GRANT OPTION",
            "GRANT ALL PRIVILEGES ON *.* TO 'root'@'127.0.0.1' WITH GRANT OPTION",
        ];
        
        foreach ($grants as $grant) {
            if ($adminConn->query($grant)) {
                $this->logSuccess("Privileges granted");
            } else {
                $this->logWarning("Could not grant privileges: " . $adminConn->error);
            }
        }
        
        if ($adminConn->query("FLUSH PRIVILEGES")) {
            $this->logSuccess("Privileges flushed");
        }
        
        return true;
    }
    
    /**
     * Step 4: Import SQL schemas
     */
    private function step4_ImportSchemas($adminConn) {
        $this->logInfo('');
        $this->logInfo('=== Step 4: Importing Database Schemas ===');
        
        if (!$adminConn) return false;
        
        $imported = 0;
        
        foreach ($this->databases as $dbName => $config) {
            $sqlFile = $this->sqlDir . '/' . $config['sqlFile'];
            
            if (!file_exists($sqlFile)) {
                $this->logWarning("SQL file not found: $sqlFile");
                continue;
            }
            
            $this->logInfo("Reading schema from: " . $config['sqlFile']);
            
            // Read SQL file
            $sql = file_get_contents($sqlFile);
            
            // Split by statements
            $statements = preg_split('/;(?=(?:[^\']*\'[^\']*\')*[^\']*$)/', $sql);
            
            $adminConn->select_db($dbName);
            $stmtCount = 0;
            
            foreach ($statements as $statement) {
                $statement = trim($statement);
                
                if (empty($statement) || strpos($statement, '--') === 0) {
                    continue;
                }
                
                if ($adminConn->query($statement)) {
                    $stmtCount++;
                } else {
                    $this->logWarning("Error executing statement in $dbName: " . $adminConn->error);
                }
            }
            
            $this->logSuccess("Database '$dbName': $stmtCount statements executed");
            $imported++;
        }
        
        $this->logInfo("Total: $imported databases imported");
        return true;
    }
    
    /**
     * Step 5: Validate connections
     */
    private function step5_ValidateConnections() {
        $this->logInfo('');
        $this->logInfo('=== Step 5: Validating Database Connections ===');
        
        $connected = 0;
        
        foreach ($this->databases as $dbName => $config) {
            $func = $config['func'];
            
            if (!function_exists($func)) {
                $this->logError("Connection function '$func' not found");
                continue;
            }
            
            $conn = @$func();
            
            if ($conn && !$conn->connect_error) {
                $currentDb = $conn->query("SELECT DATABASE() as db")->fetch_assoc()['db'];
                $tableCount = $conn->query("SELECT COUNT(*) as cnt FROM information_schema.TABLES WHERE TABLE_SCHEMA = '$dbName'")->fetch_assoc()['cnt'];
                
                $this->logSuccess("$dbName - Connected ($tableCount tables)");
                
                if ($this->verbose) {
                    $tables = $conn->query("SHOW TABLES");
                    $tableList = [];
                    while ($row = $tables->fetch_row()) {
                        $tableList[] = $row[0];
                    }
                    $this->logInfo("  Tables: " . implode(', ', $tableList));
                }
                
                $conn->close();
                $connected++;
            } else {
                $this->logError("$dbName - Connection failed: " . ($conn ? $conn->connect_error : 'NULL'));
            }
        }
        
        $this->logInfo("Total: $connected databases connected");
        return $connected === count($this->databases);
    }
    
    /**
     * Step 6: Validate schema tables
     */
    private function step6_ValidateSchema() {
        $this->logInfo('');
        $this->logInfo('=== Step 6: Validating Database Schema ===');
        
        if ($this->skipValidation) {
            $this->logWarning('Validation skipped (--skip-validation flag)');
            return true;
        }
        
        $allValid = true;
        
        foreach ($this->databases as $dbName => $config) {
            $func = $config['func'];
            $conn = function_exists($func) ? @$func() : null;
            
            if (!$conn || $conn->connect_error) {
                $this->logError("Cannot validate $dbName - connection failed");
                $allValid = false;
                continue;
            }
            
            $this->logInfo("Validating $dbName...");
            
            foreach ($config['tables'] as $tableName => $description) {
                $result = $conn->query("SHOW TABLES LIKE '$tableName'");
                
                if ($result && $result->num_rows > 0) {
                    $this->logSuccess("  ✓ $tableName - $description");
                } else {
                    $this->logWarning("  ✗ $tableName - MISSING ($description)");
                    $allValid = false;
                }
            }
            
            $conn->close();
        }
        
        return $allValid;
    }
    
    /**
     * Run complete repair process
     */
    public function run() {
        $this->logInfo('╔════════════════════════════════════════════╗');
        $this->logInfo('║  ISNM Master Database Repair & Initialize  ║');
        $this->logInfo('║  This tool will fix database unavailability ║');
        $this->logInfo('╚════════════════════════════════════════════╝');
        $this->logInfo('');
        $this->logInfo('Environment: ' . (defined('APP_ENV') ? APP_ENV : 'unknown'));
        $this->logInfo('Debug: ' . (defined('APP_DEBUG') && APP_DEBUG ? 'ON' : 'OFF'));
        $this->logInfo('');
        
        // Step 1: Admin connection
        $adminConn = $this->step1_EstablishAdminConnection();
        if (!$adminConn) {
            $this->logError('Cannot proceed without admin connection');
            return false;
        }
        
        // Step 2: Create databases
        $this->step2_CreateDatabases($adminConn);
        
        // Step 3: Grant permissions
        $this->step3_GrantPermissions($adminConn);
        
        // Step 4: Import schemas
        if ($this->importSql) {
            $this->step4_ImportSchemas($adminConn);
        } else {
            $this->logWarning('Skipping SQL import. Use --import-sql to import schemas');
        }
        
        $adminConn->close();
        
        // Step 5: Validate connections
        $this->step5_ValidateConnections();
        
        // Step 6: Validate schema
        $this->step6_ValidateSchema();
        
        $this->logInfo('');
        $this->logInfo('=== Final Result ===');
        
        if ($this->allSuccess) {
            $this->logSuccess('All checks passed! Database is ready to use.');
            $this->logInfo('You can now access the system normally.');
        } else {
            $this->logError('Some issues remain. Please review the errors above.');
            $this->logInfo('For detailed troubleshooting, run with --verbose flag');
        }
        
        return $this->allSuccess;
    }
}

// Parse command line arguments
$args = isset($argv) ? array_slice($argv, 1) : [];
$repair = new MasterDatabaseRepair($args);
$success = $repair->run();

exit($success ? 0 : 1);
?>
