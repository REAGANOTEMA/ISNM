<?php
require_once __DIR__ . '/config/database.php';

header('Content-Type: text/plain');

$conn = getStaffConnection();
if (!$conn) {
    echo "Staff DB connection failed\n";
    exit;
}

echo 'Staff DB connection successful: ' . $conn->host_info . "\n";
$conn->close();
?>
