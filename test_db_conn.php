<?php
require_once __DIR__ . '/config/database.php';

header('Content-Type: text/plain');

$conn = getStaffConnection();
if (!$conn) {
    echo "Connection failed\n";
    exit;
}

echo 'Connection successful';
$conn->close();
?>
