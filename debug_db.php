<?php
require_once __DIR__ . '/config/database.php';

header('Content-Type: text/plain');

echo "Testing configured database connections...\n\n";

$tests = [
    'staff' => getStaffConnection(),
    'students' => getStudentsConnection(),
    'website' => getWebsiteConnection(),
    'ict' => getICTConnection(),
];

foreach ($tests as $name => $conn) {
    echo strtoupper($name) . ": ";
    if (!$conn) {
        echo "failed\n";
        continue;
    }

    $result = $conn->query('SELECT 1');
    if ($result) {
        echo "connected - " . $conn->host_info . "\n";
        $result->free();
    } else {
        echo "query failed: " . $conn->error . "\n";
    }

    $conn->close();
}

echo "\nDone.\n";
?>
