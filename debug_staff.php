<?php
require_once __DIR__ . '/../config/database.php';

echo "Staff database diagnostic<br>";

$conn = getStaffConnection();
if (!$conn) {
    echo "Connection failed<br>";
    exit;
}

$result = $conn->query('SHOW TABLES');
if ($result) {
    echo "Connected successfully. Tables:<br><ul>";
    while ($row = $result->fetch_row()) {
        echo '<li>' . htmlspecialchars($row[0]) . '</li>';
    }
    echo '</ul>';
    $result->free();
} else {
    echo 'SHOW TABLES failed: ' . htmlspecialchars($conn->error);
}

$conn->close();
?>
