<?php
require_once __DIR__ . '/config/database.php';

header('Content-Type: text/plain');

echo "Database user management should be completed in the hosting control panel.\n";
echo "The application now reads hosting credentials from .env via config/database.php.\n\n";

$connections = [
    'staff' => getStaffConnection(),
    'students' => getStudentsConnection(),
    'website' => getWebsiteConnection(),
    'ict' => getICTConnection(),
];

foreach ($connections as $name => $conn) {
    echo strtoupper($name) . " DB: " . ($conn ? 'connected' : 'connection failed') . "\n";
    if ($conn) $conn->close();
}
?>
