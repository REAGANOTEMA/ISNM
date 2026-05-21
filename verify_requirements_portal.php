<?php
/**
 * Requirements Portal - Quick Validation Script
 * Checks that all components are properly installed
 */

require_once 'config/database.php';

echo "=== Requirements Portal Installation Verification ===\n\n";

$errors = [];
$warnings = [];
$success = [];

// 1. Check database connections
echo "1. Checking Database Connections...\n";
try {
    $staffConn = getStaffConnection();
    $success[] = "✓ Staff database connection OK";
} catch (Exception $e) {
    $errors[] = "✗ Staff database connection failed: " . $e->getMessage();
    $staffConn = null;
}

try {
    $studentsConn = getStudentsConnection();
    $success[] = "✓ Students database connection OK";
} catch (Exception $e) {
    $errors[] = "✗ Students database connection failed: " . $e->getMessage();
    $studentsConn = null;
}

// 2. Check if tables exist
if ($staffConn) {
    echo "\n2. Checking Required Tables...\n";
    
    $result = $staffConn->query("SHOW TABLES LIKE 'requirement_items'");
    if ($result && $result->num_rows > 0) {
        $success[] = "✓ requirement_items table exists";
        
        // Count items
        $count = $staffConn->query("SELECT COUNT(*) as cnt FROM requirement_items")->fetch_assoc();
        if ($count['cnt'] == 20) {
            $success[] = "✓ All 20 requirement items loaded";
        } elseif ($count['cnt'] > 0) {
            $warnings[] = "⚠ Found " . $count['cnt'] . " items (expected 20)";
        } else {
            $errors[] = "✗ No requirement items found";
        }
    } else {
        $errors[] = "✗ requirement_items table not found";
    }
    
    $result = $staffConn->query("SHOW TABLES LIKE 'student_requirements'");
    if ($result && $result->num_rows > 0) {
        $success[] = "✓ student_requirements table exists";
    } else {
        $errors[] = "✗ student_requirements table not found";
    }
}

// 3. Check for staff role
if ($staffConn) {
    echo "\n3. Checking Staff Roles...\n";
    
    $result = $staffConn->query("SELECT id FROM staff_roles WHERE role_name = 'Director of Requirements'");
    if ($result && $result->num_rows > 0) {
        $success[] = "✓ Director of Requirements role exists";
    } else {
        $warnings[] = "⚠ Director of Requirements role not found in staff_roles";
    }
}

// 4. Check if required files exist
echo "\n4. Checking Required Files...\n";

$files = [
    'dashboards/requirements-director.php' => 'Main Dashboard',
    'includes/requirements_functions.php' => 'Helper Functions',
    'auth-handler.php' => 'API Handler',
];

foreach ($files as $file => $desc) {
    if (file_exists($file)) {
        $success[] = "✓ $desc ($file) exists";
    } else {
        $errors[] = "✗ $desc ($file) not found";
    }
}

// 5. Test students count
if ($studentsConn) {
    echo "\n5. Checking Student Data...\n";
    
    $result = $studentsConn->query("SELECT COUNT(*) as cnt FROM users WHERE role = 'student' AND is_active = 1");
    if ($result) {
        $count = $result->fetch_assoc();
        if ($count['cnt'] > 0) {
            $success[] = "✓ Found " . $count['cnt'] . " active students";
        } else {
            $warnings[] = "⚠ No active students found in system";
        }
    }
}

// Print results
echo "\n" . str_repeat("=", 50) . "\n";
echo "VERIFICATION RESULTS\n";
echo str_repeat("=", 50) . "\n\n";

if (count($success) > 0) {
    echo "✓ SUCCESS (" . count($success) . " checks passed):\n";
    foreach ($success as $msg) {
        echo "  $msg\n";
    }
}

if (count($warnings) > 0) {
    echo "\n⚠ WARNINGS (" . count($warnings) . " items):\n";
    foreach ($warnings as $msg) {
        echo "  $msg\n";
    }
}

if (count($errors) > 0) {
    echo "\n✗ ERRORS (" . count($errors) . " issues):\n";
    foreach ($errors as $msg) {
        echo "  $msg\n";
    }
}

echo "\n" . str_repeat("=", 50) . "\n";

if (count($errors) === 0 && count($warnings) === 0) {
    echo "✓ ALL CHECKS PASSED - Portal is ready to use!\n";
    echo "\nAccess the dashboard at: dashboards/requirements-director.php\n";
} elseif (count($errors) === 0) {
    echo "⚠ All errors fixed, but address the warnings above\n";
    echo "\nSetup script: setup_requirements_portal.php\n";
} else {
    echo "✗ Please fix the errors above before using the portal\n";
    echo "\nSetup script: setup_requirements_portal.php\n";
}

if ($staffConn) $staffConn->close();
if ($studentsConn) $studentsConn->close();
?>
