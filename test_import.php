<?php
// Test import script without authentication check
require 'config/database.php';
require 'auth-service.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Bypass authentication check for testing
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set dummy session to bypass auth check
$_SESSION['user_id'] = 1;
$_SESSION['role'] = 'admin';

echo "Starting import test...".PHP_EOL;

// Import the functions from import_student_data.php
require 'import_student_data.php';

// Test the import function
$result = importAllStudentData();

echo "Import completed:".PHP_EOL;
echo "Imported: ".$result['imported'].PHP_EOL;
echo "Errors: ".$result['errors'].PHP_EOL;

if (!empty($result['error_details'])) {
    echo "Error details:".PHP_EOL;
    foreach ($result['error_details'] as $error) {
        echo "  - ".$error.PHP_EOL;
    }
}

// Check final count
$conn = getConnection();
$count = $conn->query("SELECT COUNT(*) as cnt FROM students")->fetch_assoc();
echo "Final student count: ".$count['cnt'].PHP_EOL;
?>