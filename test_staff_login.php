<?php
/**
 * Diagnostic Test for Staff Login Page
 * This will help identify what's preventing staff-login.php from loading
 */

echo "<h1>Staff Login Diagnostic Test</h1>";
echo "<hr>";

// Test 1: PHP is working
echo "<h2>✓ Test 1: PHP is working</h2>";
echo "<p>PHP Version: " . phpversion() . "</p>";
echo "<hr>";

// Test 2: Session
echo "<h2>Test 2: Session Support</h2>";
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (session_status() === PHP_SESSION_ACTIVE) {
    echo "<p>✓ Session is working</p>";
} else {
    echo "<p>✗ Session failed to start</p>";
}
echo "<hr>";

// Test 3: Required files exist
echo "<h2>Test 3: Required Files</h2>";
$required_files = [
    'config/database.php',
    'includes/functions.php',
    'auth-service.php',
    'staff-login.php'
];

foreach ($required_files as $file) {
    if (file_exists(__DIR__ . '/' . $file)) {
        echo "<p>✓ $file exists</p>";
    } else {
        echo "<p style='color:red;'>✗ $file NOT FOUND</p>";
    }
}
echo "<hr>";

// Test 4: Try to include config files
echo "<h2>Test 4: Include Configuration Files</h2>";
try {
    require_once __DIR__ . '/config/database.php';
    echo "<p>✓ config/database.php included successfully</p>";
} catch (Exception $e) {
    echo "<p style='color:red;'>✗ Error including config/database.php: " . $e->getMessage() . "</p>";
}

try {
    require_once __DIR__ . '/includes/functions.php';
    echo "<p>✓ includes/functions.php included successfully</p>";
} catch (Exception $e) {
    echo "<p style='color:red;'>✗ Error including includes/functions.php: " . $e->getMessage() . "</p>";
}

try {
    require_once __DIR__ . '/auth-service.php';
    echo "<p>✓ auth-service.php included successfully</p>";
} catch (Exception $e) {
    echo "<p style='color:red;'>✗ Error including auth-service.php: " . $e->getMessage() . "</p>";
}
echo "<hr>";

// Test 5: Database connections
echo "<h2>Test 5: Database Connections</h2>";
try {
    if (function_exists('getConnection')) {
        $conn = getConnection();
        if ($conn && $conn->ping()) {
            echo "<p>✓ Main database connection successful</p>";
        } else {
            echo "<p style='color:red;'>✗ Main database connection failed</p>";
        }
    } else {
        echo "<p style='color:red;'>✗ getConnection() function not found</p>";
    }
} catch (Exception $e) {
    echo "<p style='color:red;'>✗ Database error: " . $e->getMessage() . "</p>";
}

try {
    if (function_exists('getStaffConnection')) {
        $conn = getStaffConnection();
        if ($conn && $conn->ping()) {
            echo "<p>✓ Staff database connection successful</p>";
        } else {
            echo "<p style='color:red;'>✗ Staff database connection failed</p>";
        }
    } else {
        echo "<p style='color:red;'>✗ getStaffConnection() function not found</p>";
    }
} catch (Exception $e) {
    echo "<p style='color:red;'>✗ Staff database error: " . $e->getMessage() . "</p>";
}
echo "<hr>";

// Test 6: URL parameters
echo "<h2>Test 6: Test URL with Position Parameter</h2>";
$test_url = "staff-login.php?position=" . urlencode("School Bursar");
echo "<p>Test link: <a href='$test_url' target='_blank'>Click here to test staff-login.php with position parameter</a></p>";
echo "<p>If this link fails, check server error logs for PHP errors</p>";
echo "<hr>";

// Test 7: Error reporting
echo "<h2>Test 7: PHP Error Reporting Status</h2>";
echo "<p>Display Errors: " . (ini_get('display_errors') ? 'ON' : 'OFF') . "</p>";
echo "<p>Error Reporting Level: " . error_reporting() . "</p>";
echo "<p>Note: If display_errors is OFF, check server error logs for PHP errors</p>";
echo "<hr>";

echo "<h2>Next Steps:</h2>";
echo "<ol>";
echo "<li>Review any red ✗ marks above</li>";
echo "<li>Click the test link above to see if staff-login.php loads</li>";
echo "<li>If it still fails, ask your hosting provider to check error logs at: /var/log/php_errors.log or similar</li>";
echo "<li>Common issues: Missing database credentials, incorrect file permissions, PHP extensions not enabled</li>";
echo "</ol>";
?>
