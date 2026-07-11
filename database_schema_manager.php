<?php
/**
 * ISNM Database Schema Manager
 * Validates, repairs, and synchronizes database schema with SQL files
 * Usage: php database_schema_manager.php [--repair] [--sync] [--verbose] [--database=name]
 */

require_once __DIR__ . '/config/database.php';

class DatabaseSchemaManager {
    private $verbose = false;
    private $repairMode = false;
    private $syncMode = false;
    private $targetDatabase = null;
    private $sqlDir = __DIR__ . '/sql';
    
    private $databases = [
        'students' => [
            'func' => 'getStudentsConnection',
            'name' => 'igangaschool_students',
            'sqlFile' => 'students/igangaschool_students.sql',
            'requiredTables' => ['students', 'academic_records', 'student_fee_accounts', 'bursar_users'],
        ],
        'staffs' => [
            'func' => 'getStaffConnection',
            'name' => 'igangaschool_staffs',
            'sqlFile' => 'staffs/igangaschool_staffs.sql',
            'requiredTables' => ['staff', 'hr_users', 'payroll', 'staff_activity_log'],
        ],
        'website' => [
            'func' => 'getWebsiteConnection',
            'name' => 'igangaschool_website',
            'sqlFile' => 'website/igangaschool_website.sql',
            'requiredTables' => ['contact_submissions', 'website_announcements', 'news'],
        ],
        'ict' => [
            'func' => 'getICTConnection',
            'name' => 'igangaschool_ict',
            'sqlFile' => 'ict/igangaschool_ict.sql',
            'requiredTables' => ['ict_assets', 'ict_asset_categories', 'asset_assignments'],
        ],
    ];
    
    public function __construct($args = []) {
        $this->verbose = in_array('--verbose', $args) || in_array('-v', $args);
        $this->repairMode = in_array('--repair', $args) || in_array('-r', $args);
        $this->syncMode = in_array('--sync', $args) || in_array('-s', $args);
        
        // Check for --database=name
        foreach ($args as $arg) {
            if (strpos($arg, '--database=') === 0) {
                $this->targetDatabase = substr($arg, 11);
            }
        }
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
     * Get tables present in a specific database
     */
    private function getExistingTables($conn, $dbName) {
        $result = $conn->query("SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = '" . $conn->real_escape_string($dbName) . "'");
        $tables = [];
        while ($row = $result->fetch_assoc()) {
            $tables[] = $row['TABLE_NAME'];
        }
        return $tables;
    }
    
    /**
     * Get columns for a specific table
     */
    private function getTableColumns($conn, $dbName, $tableName) {
        $result = $conn->query("SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, COLUMN_KEY, COLUMN_DEFAULT FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = '" . $conn->real_escape_string($dbName) . "' AND TABLE_NAME = '" . $conn->real_escape_string($tableName) . "'");
        $columns = [];
        while ($row = $result->fetch_assoc()) {
            $columns[$row['COLUMN_NAME']] = $row;
        }
        return $columns;
    }
    
    /**
     * Validate schema for a specific database
     */
    public function validateSchema($dbKey = null) {
        $dbsToCheck = $dbKey ? [$dbKey => $this->databases[$dbKey]] : $this->databases;
        
        $this->log('=== Schema Validation ===');
        
        $issuesFound = 0;
        
        foreach ($dbsToCheck as $key => $dbConfig) {
            $this->log("Checking {$dbConfig['name']}...");
            
            $func = $dbConfig['func'];
            if (!function_exists($func)) {
                $this->logError("Connection function {$func} not found");
                $issuesFound++;
                continue;
            }
            
            $conn = @$func();
            if (!$conn || $conn->connect_error) {
                $this->logError("Cannot connect to {$dbConfig['name']}");
                $issuesFound++;
                continue;
            }
            
            // Check required tables
            $existingTables = $this->getExistingTables($conn, $dbConfig['name']);
            
            foreach ($dbConfig['requiredTables'] as $requiredTable) {
                if (in_array($requiredTable, $existingTables)) {
                    if ($this->verbose) {
                        $this->logSuccess("  âœ“ Table: $requiredTable");
                    }
                } else {
                    $this->logWarning("  âœ— Missing table: $requiredTable");
                    $issuesFound++;
                }
            }
            
            // Check for orphaned tables
            $orphanedTables = array_diff($existingTables, $dbConfig['requiredTables']);
            if (!empty($orphanedTables) && $this->verbose) {
                $this->log("  Additional tables: " . implode(', ', $orphanedTables));
            }
            
            $conn->close();
        }
        
        return $issuesFound === 0;
    }
    
    /**
     * Load SQL file and extract CREATE TABLE statements
     */
    private function extractCreateTableStatements($sqlFile) {
        if (!file_exists($sqlFile)) {
            $this->logError("SQL file not found: $sqlFile");
            return [];
        }
        
        $content = file_get_contents($sqlFile);
        $statements = [];
        
        // Parse CREATE TABLE statements
        $pattern = '/CREATE TABLE\s+`?(\w+)`?\s*\((.*?)\)\s*ENGINE/is';
        
        if (preg_match_all($pattern, $content, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $tableName = $match[1];
                $tableDefinition = $match[2];
                $statements[$tableName] = $tableDefinition;
            }
        }
        
        return $statements;
    }
    
    /**
     * Sync schema: create missing tables from SQL files
     */
    public function syncSchema($dbKey = null) {
        if (!$this->syncMode && !$this->repairMode) {
            $this->log('Sync mode not enabled. Use --sync or --repair flag');
            return false;
        }
        
        $dbsToSync = $dbKey ? [$dbKey => $this->databases[$dbKey]] : $this->databases;
        
        $this->log('=== Schema Synchronization ===');
        
        foreach ($dbsToSync as $key => $dbConfig) {
            $this->log("Syncing {$dbConfig['name']}...");
            
            $sqlFile = $this->sqlDir . '/' . $dbConfig['sqlFile'];
            if (!file_exists($sqlFile)) {
                $this->logWarning("SQL file not found: $sqlFile");
                continue;
            }
            
            $func = $dbConfig['func'];
            if (!function_exists($func)) {
                $this->logError("Connection function {$func} not found");
                continue;
            }
            
            $conn = @$func();
            if (!$conn || $conn->connect_error) {
                $this->logError("Cannot connect to {$dbConfig['name']}");
                continue;
            }
            
            // Get existing tables
            $existingTables = $this->getExistingTables($conn, $dbConfig['name']);
            
            // Check for missing tables and create them
            foreach ($dbConfig['requiredTables'] as $requiredTable) {
                if (!in_array($requiredTable, $existingTables)) {
                    $this->log("Creating missing table: $requiredTable");
                    
                    // Extract SQL for this table from the SQL file
                    $sql = file_get_contents($sqlFile);
                    
                    // Extract the CREATE TABLE statement for this table
                    $pattern = '/CREATE TABLE\s+`?' . preg_quote($requiredTable) . '`?\s*\((.*?)\)\s*ENGINE[^;]*;/is';
                    
                    if (preg_match($pattern, $sql, $matches)) {
                        // Extract the full statement
                        $startPos = strpos($sql, 'CREATE TABLE');
                        $endPos = strpos($sql, ';', $startPos) + 1;
                        $createStmt = substr($sql, $startPos, $endPos - $startPos);
                        
                        if ($conn->query($createStmt)) {
                            $this->logSuccess("âœ“ Created table: $requiredTable");
                        } else {
                            $this->logError("âœ— Failed to create table $requiredTable: " . $conn->error);
                        }
                    } else {
                        $this->logWarning("Could not find CREATE TABLE statement for $requiredTable in SQL file");
                    }
                }
            }
            
            $conn->close();
        }
        
        return true;
    }
    
    /**
     * Generate a schema report
     */
    public function generateReport() {
        $report = [];
        
        foreach ($this->databases as $key => $dbConfig) {
            $func = $dbConfig['func'];
            $conn = function_exists($func) ? @$func() : null;
            
            $report[$key] = [
                'name' => $dbConfig['name'],
                'connected' => $conn && !$conn->connect_error,
                'tables' => [],
            ];
            
            if ($conn && !$conn->connect_error) {
                $existingTables = $this->getExistingTables($conn, $dbConfig['name']);
                $report[$key]['tables'] = [
                    'existing' => $existingTables,
                    'required' => $dbConfig['requiredTables'],
                    'missing' => array_diff($dbConfig['requiredTables'], $existingTables),
                ];
                $conn->close();
            }
        }
        
        return $report;
    }
    
    /**
     * Run the schema manager
     */
    public function run() {
        $this->log('=== ISNM Database Schema Manager ===');
        $this->log('Environment: ' . (defined('APP_ENV') ? APP_ENV : 'unknown'));
        $this->log('');
        
        // Validate schema
        $schemaValid = $this->validateSchema($this->targetDatabase);
        $this->log('');
        
        // Generate report
        $report = $this->generateReport();
        
        if ($this->verbose) {
            $this->log('Detailed Report:');
            echo "<pre>" . json_encode($report, JSON_PRETTY_PRINT) . "</pre>";
        }
        
        // Attempt sync if in sync/repair mode
        if (($this->syncMode || $this->repairMode) && !$schemaValid) {
            $this->log('Attempting to sync schema...');
            $this->syncSchema($this->targetDatabase);
            $this->log('');
            
            // Re-validate
            $this->log('Re-validating schema...');
            $schemaValid = $this->validateSchema($this->targetDatabase);
        }
        
        $this->log('=== Summary ===');
        $this->logSuccess('Schema validation: ' . ($schemaValid ? 'PASSED' : 'NEEDS ATTENTION'));
        
        return $schemaValid;
    }
}

// Parse command line arguments
$args = isset($argv) ? array_slice($argv, 1) : [];
$manager = new DatabaseSchemaManager($args);
$success = $manager->run();

exit($success ? 0 : 1);
?>
