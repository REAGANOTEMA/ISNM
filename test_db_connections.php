<?php
/**
 * Quick database connection test
 */
require_once __DIR__ . '/config/database.php';

echo "Testing database connections...\n\n";

$connections = [
    'Students' => function() { return getStudentsConnection(); },
    'Staff' => function() { return getStaffConnection(); },
    'Website' => function() { return getWebsiteConnection(); },
    'ICT' => function() { return getICTConnection(); },
];

foreach ($connections as $name => $func) {
    echo "Testing $name database...\n";
    $conn = $func();
    
    if ($conn) {
        $db = $conn->query("SELECT DATABASE() as db");
        $result = $db->fetch_assoc();
        echo "  ✓ Connected to: " . $result['db'] . "\n";
        $conn->close();
    } else {
        echo "  ✗ Connection failed\n";
        echo "  Last error: " . ($GLOBALS['isnm_last_db_error'] ?? 'Unknown') . "\n";
    }
    echo "\n";
}
?>
