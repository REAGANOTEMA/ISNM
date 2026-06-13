<?php
require_once __DIR__ . '/config/database.php';

header('Content-Type: text/plain');

$tests = [
    'staff' => getStaffConnection(),
    'students' => getStudentsConnection(),
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
        echo "successful\n";
        $result->free();
    } else {
        echo "failed: " . $conn->error . "\n";
    }

    $conn->close();
}
?>
