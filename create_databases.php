<?php
require_once __DIR__ . '/config/database.php';

$connections = [
    'staff' => getStaffConnection(),
    'students' => getStudentsConnection(),
    'website' => getWebsiteConnection(),
    'ict' => getICTConnection(),
];

foreach ($connections as $name => $conn) {
    if (!$conn) {
        echo strtoupper($name) . " DB: connection failed\n";
        continue;
    }

    $tables = $conn->query('SHOW TABLES');
    echo strtoupper($name) . " DB: connected, tables=" . ($tables ? $tables->num_rows : 0) . "\n";
    $conn->close();
}
?>
