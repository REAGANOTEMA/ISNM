<?php
require_once __DIR__ . '/config/database.php';

echo "Testing staff database access...\n\n";

$conn = getStaffConnection();
if (!$conn) {
    echo "✗ Connection failed\n";
    exit;
}

$result = $conn->query('SELECT CURRENT_USER() AS current_user, DATABASE() AS current_database');
if ($result && $row = $result->fetch_assoc()) {
    echo "✓ Connected as: " . $row['current_user'] . "\n";
    echo "✓ Current database: " . $row['current_database'] . "\n";
    $result->free();
} else {
    echo "✗ Could not read current user/database\n";
}

$result = $conn->query('SHOW TABLES');
if ($result) {
    echo "✓ Staff database tables accessible: " . $result->num_rows . "\n";
    $result->free();
} else {
    echo "✗ Staff database tables not accessible\n";
}

$conn->close();
?>
