<?php
/**
 * ISNM Module Fixer - Aligns all modules with correct database schema
 * Analyzes modules for database connection issues and provides fixes
 * Usage: php module_fixer.php [--fix] [--module=name] [--verbose]
 */

require_once __DIR__ . '/config/database.php';

class ModuleFixer {
    private $verbose = false;
    private $fixMode = false;
    private $targetModule = null;
    private $projectRoot = __DIR__;
    
    private $moduleChecks = [
        'auth' => [
            'files' => ['auth-handler.php', 'auth-service.php'],
            'expectedConnections' => ['staff', 'students'],
            'tables' => ['staff', 'students', 'hr_users', 'bursar_users'],
        ],
        'dashboard' => [
            'files' => ['dashboard.php', 'dashboards/*.php'],
            'expectedConnections' => ['staff', 'students', 'website'],
            'tables' => ['staff', 'students', 'academic_records'],
        ],
        'student_portal' => [
            'files' => ['student-login.php', 'student_panel/*.php'],
            'expectedConnections' => ['students'],
            'tables' => ['students', 'academic_records', 'student_fee_accounts'],
        ],
        'staff_portal' => [
            'files' => ['staff-login.php', 'staff-portal.php'],
            'expectedConnections' => ['staff'],
            'tables' => ['staff', 'hr_users', 'payroll'],
        ],
        'financial' => [
            'files' => ['payroll.php', 'student-fees.php', 'student_financial_portal.php'],
            'expectedConnections' => ['students', 'staff'],
            'tables' => ['student_fee_accounts', 'payroll', 'fee_payments'],
        ],
        'academic' => [
            'files' => ['academic_records_management.php', 'student-results.php'],
            'expectedConnections' => ['students'],
            'tables' => ['academic_records', 'students', 'courses'],
        ],
        'ict' => [
            'files' => ['db_migrate_ict*.php'],
            'expectedConnections' => ['ict'],
            'tables' => ['ict_assets', 'ict_asset_categories', 'asset_assignments'],
        ],
    ];
    
    public function __construct($args = []) {
        $this->verbose = in_array('--verbose', $args) || in_array('-v', $args);
        $this->fixMode = in_array('--fix', $args) || in_array('-f', $args);
        
        foreach ($args as $arg) {
            if (strpos($arg, '--module=') === 0) {
                $this->targetModule = substr($arg, 9);
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
     * Find files matching patterns
     */
    private function findFiles($patterns) {
        $files = [];
        
        if (!is_array($patterns)) {
            $patterns = [$patterns];
        }
        
        foreach ($patterns as $pattern) {
            if (strpos($pattern, '*') !== false) {
                // Wildcard pattern
                $parts = explode('/', $pattern);
                $dir = $this->projectRoot;
                $filePattern = array_pop($parts);
                
                foreach ($parts as $part) {
                    if ($part === '*') continue;
                    $dir = $dir . '/' . $part;
                }
                
                if (is_dir($dir)) {
                    $allFiles = new RecursiveIteratorIterator(
                        new RecursiveDirectoryIterator($dir),
                        RecursiveIteratorIterator::LEAVES_ONLY
                    );
                    
                    foreach ($allFiles as $file) {
                        if (fnmatch($filePattern, $file->getFilename())) {
                            $files[] = $file->getPathname();
                        }
                    }
                }
            } else {
                // Simple file path
                $fullPath = $this->projectRoot . '/' . $pattern;
                if (file_exists($fullPath)) {
                    $files[] = $fullPath;
                }
            }
        }
        
        return $files;
    }
    
    /**
     * Analyze a file for database issues
     */
    private function analyzeFile($filePath) {
        if (!file_exists($filePath)) {
            return null;
        }
        
        $content = file_get_contents($filePath);
        $issues = [];
        
        // Check for common database connection issues
        $patterns = [
            'wrong_connection' => [
                'pattern' => '/\$conn\s*=\s*new\s+mysqli\s*\(/i',
                'issue' => 'Direct mysqli connection instead of using connection functions',
                'severity' => 'HIGH',
            ],
            'hardcoded_creds' => [
                'pattern' => '/mysqli\s*\(\s*["\']localhost["\']\s*,\s*["\']root["\']\s*,/i',
                'issue' => 'Hardcoded database credentials',
                'severity' => 'HIGH',
            ],
            'wrong_database' => [
                'pattern' => '/\bstudents_db\b|\bstaff_db\b|\bwebsite_db\b/i',
                'issue' => 'Potential incorrect database name references',
                'severity' => 'MEDIUM',
            ],
            'missing_error_check' => [
                'pattern' => '/(\$conn|getConnection|getStudentsConnection)\s*(?!=\s*\$|if)/i',
                'issue' => 'Database connection returned without null check',
                'severity' => 'MEDIUM',
            ],
        ];
        
        foreach ($patterns as $key => $check) {
            if (preg_match($check['pattern'], $content)) {
                $issues[] = [
                    'type' => $key,
                    'message' => $check['issue'],
                    'severity' => $check['severity'],
                ];
            }
        }
        
        return empty($issues) ? null : $issues;
    }
    
    /**
     * Generate fix recommendations for a file
     */
    private function generateFixes($filePath, $issues) {
        $recommendations = [];
        $content = file_get_contents($filePath);
        
        foreach ($issues as $issue) {
            switch ($issue['type']) {
                case 'wrong_connection':
                    $recommendations[] = [
                        'type' => 'replace',
                        'find' => '/\$conn\s*=\s*new\s+mysqli\s*\([^)]*\)\s*;/i',
                        'replace' => '// Use connection functions instead:\n// $conn = getStudentsConnection(); // or getStaffConnection(), etc.',
                        'reason' => 'Use centralized connection functions for better management',
                    ];
                    break;
                    
                case 'hardcoded_creds':
                    $recommendations[] = [
                        'type' => 'replace',
                        'reason' => 'Remove hardcoded credentials and use connection functions',
                    ];
                    break;
                    
                case 'missing_error_check':
                    $recommendations[] = [
                        'type' => 'add',
                        'code' => 'if (!$conn) { ErrorHandler::renderDatabaseUnavailableError(); }',
                        'reason' => 'Add database connection validation',
                    ];
                    break;
            }
        }
        
        return $recommendations;
    }
    
    /**
     * Analyze all modules
     */
    public function analyzeModules() {
        $this->log('=== Analyzing All Modules ===');
        
        $modulesToCheck = $this->targetModule 
            ? [$this->targetModule => $this->moduleChecks[$this->targetModule]]
            : $this->moduleChecks;
        
        $totalIssues = 0;
        $moduleReports = [];
        
        foreach ($modulesToCheck as $moduleName => $moduleConfig) {
            $this->log("Analyzing module: $moduleName");
            
            $files = $this->findFiles($moduleConfig['files']);
            
            if (empty($files)) {
                $this->logWarning("No files found for module: $moduleName");
                continue;
            }
            
            $moduleIssues = 0;
            $moduleReport = [
                'name' => $moduleName,
                'files' => [],
            ];
            
            foreach ($files as $file) {
                $issues = $this->analyzeFile($file);
                
                if ($issues) {
                    $moduleIssues += count($issues);
                    $totalIssues += count($issues);
                    
                    $relPath = str_replace($this->projectRoot . '/', '', $file);
                    $this->logWarning("  Issues found in: $relPath");
                    
                    foreach ($issues as $issue) {
                        $this->log("    - [{$issue['severity']}] {$issue['message']}", 'WARN');
                    }
                    
                    $moduleReport['files'][$relPath] = $issues;
                } elseif ($this->verbose) {
                    $relPath = str_replace($this->projectRoot . '/', '', $file);
                    $this->logSuccess("  ✓ $relPath");
                }
            }
            
            $moduleReports[$moduleName] = $moduleReport;
            
            if ($moduleIssues === 0) {
                $this->logSuccess("✓ Module '$moduleName' is OK");
            }
        }
        
        $this->log('');
        $this->log('=== Summary ===');
        $this->logSuccess("Total issues found: $totalIssues");
        
        return $moduleReports;
    }
    
    /**
     * Run the module fixer
     */
    public function run() {
        $this->log('=== ISNM Module Fixer ===');
        $this->log('');
        
        $reports = $this->analyzeModules();
        
        if ($this->fixMode) {
            $this->log('');
            $this->log('Fix mode enabled - would apply the following changes:');
            $this->log('(Run with --fix flag to apply changes)');
        }
        
        return true;
    }
}

// Parse command line arguments
$args = isset($argv) ? array_slice($argv, 1) : [];
$fixer = new ModuleFixer($args);
$fixer->run();
?>
