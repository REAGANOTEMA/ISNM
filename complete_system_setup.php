<?php
/**
 * ISNM Complete System Setup Script
 * Comprehensive initialization with new database credentials and staff accounts
 * Usage: php complete_system_setup.php [--import-sql] [--seed-staff] [--verbose]
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/credentials_database.php';

class CompleteSystemSetup {
    private $verbose = false;
    private $importSql = false;
    private $seedStaff = false;
    private $adminConn;
    
    public function __construct($args = []) {
        $this->verbose = in_array('--verbose', $args) || in_array('-v', $args);
        $this->importSql = in_array('--import-sql', $args);
        $this->seedStaff = in_array('--seed-staff', $args);
    }
    
    private function log($message, $level = 'INFO') {
        $timestamp = date('Y-m-d H:i:s');
        $prefix = '';
        
        switch ($level) {
            case 'ERROR': $prefix = '❌'; break;
            case 'SUCCESS': $prefix = '✓'; break;
            case 'WARN': $prefix = '⚠'; break;
            case 'INFO': $prefix = 'ℹ'; break;
        }
        
        $formatted = "[$timestamp] $prefix [$level] $message";
        echo $formatted . "\n";
        error_log($formatted);
    }
    
    /**
     * Step 1: Connect as admin with new credentials
     */
    private function step1_AdminConnection() {
        $this->log("=== Step 1: Establishing Admin Connection ===");
        
        // Try with first database credential as admin
        $dbCreds = getAllDBCredentials();
        $staffDbCreds = $dbCreds['igangaschoolofl_staffs_db'];
        
        // Try connecting with the staff database user credentials
        $this->adminConn = @new mysqli(
            'localhost',
            $staffDbCreds['username'],
            $staffDbCreds['password'],
            '',
            3306
        );
        
        if ($this->adminConn->connect_error) {
            $this->log("Admin connection failed: " . $this->adminConn->connect_error, 'ERROR');
            return false;
        }
        
        $this->log("Connected as {$staffDbCreds['username']}", 'SUCCESS');
        return true;
    }
    
    /**
     * Step 2: Verify all databases exist
     */
    private function step2_VerifyDatabases() {
        $this->log("");
        $this->log("=== Step 2: Verifying Databases ===");
        
        if (!$this->adminConn) {
            $this->log("No admin connection available", 'ERROR');
            return false;
        }
        
        $dbCreds = getAllDBCredentials();
        $verified = 0;
        
        foreach (array_keys($dbCreds) as $dbName) {
            $result = $this->adminConn->query("SHOW DATABASES LIKE '$dbName'");
            
            if ($result && $result->num_rows > 0) {
                // Get table count
                $tableResult = $this->adminConn->query(
                    "SELECT COUNT(*) as cnt FROM information_schema.TABLES WHERE TABLE_SCHEMA = '$dbName'"
                );
                $tableRow = $tableResult->fetch_assoc();
                $tableCount = $tableRow['cnt'];
                
                $this->log("✓ Database '$dbName' exists ($tableCount tables)", 'SUCCESS');
                $verified++;
            } else {
                $this->log("✗ Database '$dbName' NOT found", 'WARN');
            }
        }
        
        return $verified === count($dbCreds);
    }
    
    /**
     * Step 3: Verify database user accounts
     */
    private function step3_VerifyDatabaseUsers() {
        $this->log("");
        $this->log("=== Step 3: Verifying Database User Accounts ===");
        
        if (!$this->adminConn) return false;
        
        $dbCreds = getAllDBCredentials();
        $verified = 0;
        
        foreach ($dbCreds as $dbName => $creds) {
            // Check if user has access to their database
            $testConn = @new mysqli(
                $creds['hostname'],
                $creds['username'],
                $creds['password'],
                $dbName,
                $creds['port']
            );
            
            if (!$testConn->connect_error) {
                $this->log("✓ User '{$creds['username']}' can access '$dbName'", 'SUCCESS');
                $testConn->close();
                $verified++;
            } else {
                $this->log("✗ User '{$creds['username']}' cannot access '$dbName'", 'WARN');
            }
        }
        
        return $verified === count($dbCreds);
    }
    
    /**
     * Step 4: Seed staff credentials
     */
    private function step4_SeedStaffCredentials() {
        $this->log("");
        $this->log("=== Step 4: Seeding Staff Credentials ===");
        
        if (!$this->seedStaff) {
            $this->log("Skipping staff credential seeding (--seed-staff not provided)", 'INFO');
            return true;
        }
        
        // Connect to staff database
        $staffConn = getStaffConnection();
        if (!$staffConn || $staffConn->connect_error) {
            $this->log("Cannot connect to staff database", 'ERROR');
            return false;
        }
        
        // Verify staff table exists
        $result = $staffConn->query("SHOW TABLES LIKE 'staff'");
        if ($result->num_rows === 0) {
            $this->log("Staff table does not exist - cannot seed credentials", 'ERROR');
            $staffConn->close();
            return false;
        }
        
        $staffCreds = getAllStaffCredentials();
        $inserted = 0;
        $updated = 0;
        $errors = 0;
        
        foreach ($staffCreds as $email => $creds) {
            // Check if staff exists
            $check = $staffConn->prepare("SELECT id FROM staff WHERE email = ? LIMIT 1");
            $check->bind_param('s', $email);
            $check->execute();
            $checkResult = $check->get_result();
            
            if ($checkResult->num_rows > 0) {
                // Update existing
                $row = $checkResult->fetch_assoc();
                $id = $row['id'];
                $password = password_hash($creds['password'], PASSWORD_BCRYPT);
                
                // Look up role_id from staff_roles
                $role_id_val = null;
                $rq = $staffConn->prepare("SELECT id FROM staff_roles WHERE role_name = ? LIMIT 1");
                if ($rq) {
                    $roleName = $creds['position'];
                    $rq->bind_param('s', $roleName);
                    $rq->execute();
                    $rqResult = $rq->get_result();
                    if ($rqResult && $rqResult->num_rows > 0) {
                        $role_id_val = (int)$rqResult->fetch_assoc()['id'];
                    }
                    $rq->close();
                }
                
                $stmt = $staffConn->prepare(
                    "UPDATE staff SET password = ?, full_name = ?, position = ?, department = ?, role_id = ?, status = 'active' WHERE id = ?"
                );
                
                if ($stmt) {
                    $stmt->bind_param('sssssi', $password, $creds['full_name'], $creds['position'], $creds['department'], $role_id_val, $id);
                    if ($stmt->execute()) {
                        if ($this->verbose) {
                            $this->log("Updated: $email", 'SUCCESS');
                        }
                        $updated++;
                    } else {
                        $this->log("Error updating $email: " . $stmt->error, 'ERROR');
                        $errors++;
                    }
                    $stmt->close();
                }
            } else {
                // Insert new
                $password = password_hash($creds['password'], PASSWORD_BCRYPT);
                $status = 'active';
                $login_attempts = 0;
                
                // Look up role_id from staff_roles
                $role_id_val = null;
                $rq = $staffConn->prepare("SELECT id FROM staff_roles WHERE role_name = ? LIMIT 1");
                if ($rq) {
                    $roleName = $creds['position'];
                    $rq->bind_param('s', $roleName);
                    $rq->execute();
                    $rqResult = $rq->get_result();
                    if ($rqResult && $rqResult->num_rows > 0) {
                        $role_id_val = (int)$rqResult->fetch_assoc()['id'];
                    }
                    $rq->close();
                }
                
                $stmt = $staffConn->prepare(
                    "INSERT INTO staff (email, password, full_name, position, department, role_id, status, login_attempts, created_at) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())"
                );
                
                if ($stmt) {
                    $stmt->bind_param('sssssssi', $email, $password, $creds['full_name'], $creds['position'], $creds['department'], $role_id_val, $status, $login_attempts);
                    if ($stmt->execute()) {
                        if ($this->verbose) {
                            $this->log("Inserted: $email", 'SUCCESS');
                        }
                        $inserted++;
                    } else {
                        $this->log("Error inserting $email: " . $stmt->error, 'ERROR');
                        $errors++;
                    }
                    $stmt->close();
                }
            }
            
            $check->close();
        }
        
        $this->log("Staff Credentials Summary: Inserted=$inserted, Updated=$updated, Errors=$errors", 'INFO');
        $staffConn->close();
        
        return $errors === 0;
    }
    
    /**
     * Step 5: Test all connections
     */
    private function step5_TestConnections() {
        $this->log("");
        $this->log("=== Step 5: Testing All Connections ===");
        
        $connections = [
            'Students' => 'getStudentsConnection',
            'Staff' => 'getStaffConnection',
            'Website' => 'getWebsiteConnection',
            'ICT' => 'getICTConnection',
        ];
        
        $connected = 0;
        
        foreach ($connections as $name => $func) {
            if (function_exists($func)) {
                $conn = @$func();
                if ($conn && !$conn->connect_error) {
                    $dbName = $conn->query("SELECT DATABASE() as db")->fetch_assoc()['db'];
                    $this->log("✓ $name: Connected to $dbName", 'SUCCESS');
                    $conn->close();
                    $connected++;
                } else {
                    $this->log("✗ $name: Connection failed", 'ERROR');
                }
            }
        }
        
        return $connected === count($connections);
    }
    
    /**
     * Run complete setup
     */
    public function run() {
        $this->log("╔════════════════════════════════════════╗");
        $this->log("║  ISNM Complete System Setup            ║");
        $this->log("║  New Database Credentials & Staff Setup║");
        $this->log("╚════════════════════════════════════════╝");
        $this->log("");
        
        // Step 1
        if (!$this->step1_AdminConnection()) {
            $this->log("Setup aborted - cannot establish admin connection", 'ERROR');
            return false;
        }
        
        // Step 2
        $dbsOk = $this->step2_VerifyDatabases();
        
        // Step 3
        $usersOk = $this->step3_VerifyDatabaseUsers();
        
        // Step 4
        $staffOk = $this->step4_SeedStaffCredentials();
        
        // Step 5
        $connsOk = $this->step5_TestConnections();
        
        // Summary
        $this->log("");
        $this->log("=== Setup Summary ===");
        $this->log("Database Verification: " . ($dbsOk ? 'PASS' : 'FAIL'), $dbsOk ? 'SUCCESS' : 'ERROR');
        $this->log("Database Users: " . ($usersOk ? 'PASS' : 'FAIL'), $usersOk ? 'SUCCESS' : 'ERROR');
        $this->log("Staff Credentials: " . ($staffOk ? 'PASS' : 'FAIL'), $staffOk ? 'SUCCESS' : 'ERROR');
        $this->log("Connection Tests: " . ($connsOk ? 'PASS' : 'FAIL'), $connsOk ? 'SUCCESS' : 'ERROR');
        
        if ($dbsOk && $usersOk && $connsOk) {
            $this->log("");
            $this->log("✓ System setup completed successfully!", 'SUCCESS');
            $this->log("You can now login with staff credentials", 'SUCCESS');
            return true;
        } else {
            $this->log("");
            $this->log("✗ Some checks failed - review errors above", 'ERROR');
            return false;
        }
    }
}

// Run setup
$args = isset($argv) ? array_slice($argv, 1) : [];
$setup = new CompleteSystemSetup($args);
$success = $setup->run();

exit($success ? 0 : 1);
?>
