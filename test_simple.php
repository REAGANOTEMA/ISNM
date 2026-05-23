<?php
// Test import without HTML output
require 'config/database.php';
require 'auth-service.php';

// Set up a dummy session to bypass auth check
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$_SESSION['user_id'] = 1;
$_SESSION['role'] = 'admin';

// Import the functions
require 'import_student_data.php';

// Run import
$result = importAllStudentData();

echo "Import Results:\n";
echo "Imported: " . $result['imported'] . "\n";
echo "Errors: " . $result['errors'] . "\n";

if (!empty($result['error_details'])) {
    echo "\nFirst 5 errors:\n";
    for ($i = 0; $i < min(5, count($result['error_details'])); $i++) {
        echo ($i+1) . ". " . $result['error_details'][$i] . "\n";
    }
}

// Check final count
$conn = getConnection();
$count = $conn->query("SELECT COUNT(*) as cnt FROM students")->fetch_assoc();
echo "\nFinal student count: " . $count['cnt'] . "\n";
?>