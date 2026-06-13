<?php
require_once __DIR__ . '/config/database.php';

header('Content-Type: text/plain');

$tests = [
    'students' => getStudentsConnection(),
    'staff' => getStaffConnection(),
    'website' => getWebsiteConnection(),
    'ict' => getICTConnection(),
];

foreach ($tests as $name => $conn) {
    echo strtoupper($name) . " DB connection ";
    if (!$conn) {
        echo "failed\n";
        continue;
    }

    $result = $conn->query('SELECT 1');
    if ($result) {
        echo "successful: " . $conn->host_info . "\n";
        $result->free();
    } else {
        echo "failed: " . $conn->error . "\n";
    }

    $conn->close();
}
?>
