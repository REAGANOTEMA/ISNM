<?php
/**
 * Database Connection Test Script for Staffs Database
 * This script tests the connection to the staffs_db database
 */

// Include database configuration
require_once __DIR__ . '/config/database.php';

echo "<h2>ISNM Database Connection Test</h2>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .success { color: green; font-weight: bold; }
    .error { color: red; font-weight: bold; }
    .info { color: blue; }
    table { border-collapse: collapse; width: 100%; margin-top: 20px; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
    th { background-color: #4CAF50; color: white; }
    tr:nth-child(even) { background-color: #f2f2f2; }
</style>";

// Test 1: Test staffs_db connection
echo "<h3>Test 1: Staffs Database Connection</h3>";
try {
    $staffConn = getStaffConnection();
    echo "<p class='success'>✓ Successfully connected to staffs_db database</p>";
    echo "<p class='info'>Database: " . STAFF_DB_NAME . "</p>";
    echo "<p class='info'>Host: " . STAFF_DB_HOST . "</p>";
    echo "<p class='info'>Charset: " . STAFF_DB_CHARSET . "</p>";
    closeConnection($staffConn);
} catch (Exception $e) {
    echo "<p class='error'>✗ Failed to connect to staffs_db: " . $e->getMessage() . "</p>";
}

// Test 1b: Test students_db connection
echo "<h3>Test 1b: Students Database Connection</h3>";
try {
    $studentsConn = getStudentsConnection();
    echo "<p class='success'>✓ Successfully connected to students_db database</p>";
    echo "<p class='info'>Database: students_db</p>";
    echo "<p class='info'>Host: " . DB_HOST . "</p>";
    closeConnection($studentsConn);
} catch (Exception $e) {
    echo "<p class='error'>✗ Failed to connect to students_db: " . $e->getMessage() . "</p>";
    echo "<p class='info'>Please create the students_db database if it doesn't exist.</p>";
}

// Test 1c: Test website_db connection
echo "<h3>Test 1c: Website Database Connection</h3>";
try {
    $websiteConn = getWebsiteConnection();
    echo "<p class='success'>✓ Successfully connected to website_db database</p>";
    echo "<p class='info'>Database: website_db</p>";
    echo "<p class='info'>Host: " . DB_HOST . "</p>";
    closeConnection($websiteConn);
} catch (Exception $e) {
    echo "<p class='error'>✗ Failed to connect to website_db: " . $e->getMessage() . "</p>";
    echo "<p class='info'>Please create the website_db database if it doesn't exist.</p>";
}

// Test 2: Check if staffs_db exists and list tables
echo "<h3>Test 2: Database Tables Check</h3>";
try {
    $staffConn = getStaffConnection();
    $result = $staffConn->query("SHOW TABLES");
    
    if ($result->num_rows > 0) {
        echo "<p class='success'>✓ Database contains " . $result->num_rows . " tables</p>";
        echo "<table>";
        echo "<tr><th>Table Name</th><th>Rows</th></tr>";
        
        while ($row = $result->fetch_array()) {
            $tableName = $row[0];
            $countResult = $staffConn->query("SELECT COUNT(*) as count FROM `$tableName`");
            $countRow = $countResult->fetch_assoc();
            echo "<tr><td>$tableName</td><td>" . $countRow['count'] . "</td></tr>";
        }
        echo "</table>";
    } else {
        echo "<p class='error'>✗ Database exists but contains no tables</p>";
        echo "<p class='info'>Please import the SQL schema file: sql/staffs/04_final_complete_staffs_database.sql</p>";
    }
    
    closeConnection($staffConn);
} catch (Exception $e) {
    echo "<p class='error'>✗ Error checking tables: " . $e->getMessage() . "</p>";
}

// Test 3: Check critical tables exist
echo "<h3>Test 3: Critical Tables Check</h3>";
$criticalTables = [
    'staff',
    'staff_roles',
    'staff_profiles',
    'staff_departments',
    'staff_permissions',
    'staff_login_sessions',
    'staff_login_attempts',
    'staff_password_resets',
    'staff_activity_log',
    'system_settings',
    'students'
];

try {
    $staffConn = getStaffConnection();
    $missingTables = [];
    
    foreach ($criticalTables as $table) {
        $result = $staffConn->query("SHOW TABLES LIKE '$table'");
        if ($result->num_rows == 0) {
            $missingTables[] = $table;
        }
    }
    
    if (empty($missingTables)) {
        echo "<p class='success'>✓ All " . count($criticalTables) . " critical tables exist</p>";
    } else {
        echo "<p class='error'>✗ Missing " . count($missingTables) . " critical tables:</p>";
        echo "<ul>";
        foreach ($missingTables as $table) {
            echo "<li class='error'>$table</li>";
        }
        echo "</ul>";
        echo "<p class='info'>Please import the SQL schema file to create missing tables.</p>";
    }
    
    closeConnection($staffConn);
} catch (Exception $e) {
    echo "<p class='error'>✗ Error checking critical tables: " . $e->getMessage() . "</p>";
}

// Test 4: Check if default admin user exists
echo "<h3>Test 4: Default Admin User Check</h3>";
try {
    $staffConn = getStaffConnection();
    $result = $staffConn->query("SELECT staff_id, full_name, email, status FROM staff WHERE email = 'administration@isnm.ac'");
    
    if ($result->num_rows > 0) {
        $admin = $result->fetch_assoc();
        echo "<p class='success'>✓ Default admin user exists</p>";
        echo "<table>";
        echo "<tr><th>Staff ID</th><th>Full Name</th><th>Email</th><th>Status</th></tr>";
        echo "<tr><td>" . $admin['staff_id'] . "</td><td>" . $admin['full_name'] . "</td><td>" . $admin['email'] . "</td><td>" . $admin['status'] . "</td></tr>";
        echo "</table>";
        echo "<p class='info'>Default credentials: Email: administration@isnm.ac | Password: 12345678</p>";
    } else {
        echo "<p class='error'>✗ Default admin user not found</p>";
        echo "<p class='info'>The SQL schema should create this automatically. Please re-import the schema.</p>";
    }
    
    closeConnection($staffConn);
} catch (Exception $e) {
    echo "<p class='error'>✗ Error checking admin user: " . $e->getMessage() . "</p>";
}

// Test 5: Check system settings
echo "<h3>Test 5: System Settings Check</h3>";
try {
    $staffConn = getStaffConnection();
    $result = $staffConn->query("SELECT COUNT(*) as count FROM system_settings");
    $count = $result->fetch_assoc()['count'];
    
    if ($count > 0) {
        echo "<p class='success'>✓ System settings table has $count records</p>";
        $result = $staffConn->query("SELECT setting_key, setting_value FROM system_settings WHERE setting_key IN ('school_name', 'academic_year', 'semester')");
        echo "<table>";
        echo "<tr><th>Setting Key</th><th>Setting Value</th></tr>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr><td>" . $row['setting_key'] . "</td><td>" . $row['setting_value'] . "</td></tr>";
        }
        echo "</table>";
    } else {
        echo "<p class='error'>✗ System settings table is empty</p>";
    }
    
    closeConnection($staffConn);
} catch (Exception $e) {
    echo "<p class='error'>✗ Error checking system settings: " . $e->getMessage() . "</p>";
}

// Test 6: Check stored procedures
echo "<h3>Test 6: Stored Procedures Check</h3>";
$procedures = [
    'authenticate_staff',
    'get_dashboard_statistics',
    'log_staff_activity',
    'request_password_reset',
    'reset_password_with_token',
    'change_password'
];

try {
    $staffConn = getStaffConnection();
    $missingProcedures = [];
    
    foreach ($procedures as $proc) {
        $result = $staffConn->query("SHOW PROCEDURE STATUS WHERE Db = '" . STAFF_DB_NAME . "' AND Name = '$proc'");
        if ($result->num_rows == 0) {
            $missingProcedures[] = $proc;
        }
    }
    
    if (empty($missingProcedures)) {
        echo "<p class='success'>✓ All " . count($procedures) . " critical stored procedures exist</p>";
    } else {
        echo "<p class='error'>✗ Missing " . count($missingProcedures) . " stored procedures:</p>";
        echo "<ul>";
        foreach ($missingProcedures as $proc) {
            echo "<li class='error'>$proc</li>";
        }
        echo "</ul>";
    }
    
    closeConnection($staffConn);
} catch (Exception $e) {
    echo "<p class='error'>✗ Error checking stored procedures: " . $e->getMessage() . "</p>";
}

echo "<h3>Summary</h3>";
echo "<p class='info'>If all tests passed, your database is properly configured and connected.</p>";
echo "<p class='info'>If any tests failed, please:</p>";
echo "<ol>";
echo "<li>Ensure MySQL is running on localhost</li>";
echo "<li>Verify the database password in config/database.php matches your MySQL root password</li>";
echo "<li>Import the SQL schema file: sql/staffs/04_final_complete_staffs_database.sql</li>";
echo "<li>Run this test script again to verify</li>";
echo "</ol>";
echo "<p><a href='index.php'>Return to Home</a></p>";
?>
