<?php
echo "=== DATABASE CONNECTION DIAGNOSTIC ===\n\n";

// Check if we can connect to MySQL at all using different methods
$methods = [
    ['host' => '127.0.0.1', 'port' => 3306, 'protocol' => 'tcp'],
    ['host' => 'localhost', 'port' => 3306, 'protocol' => 'tcp'],
    ['host' => '127.0.0.1', 'port' => 3307, 'protocol' => 'tcp'], // From my.ini
    ['host' => 'localhost', 'port' => 3307, 'protocol' => 'tcp'], // From my.ini
];

$users = [
    ['user' => 'root', 'pass' => ''],
    ['user' => 'igangaschoolofl_staffs_db', 'pass' => 'AgKzJjZZnT5q58jCahs8'],
];

foreach ($methods as $method) {
    echo "Testing connection method: {$method['host']}:{$method['port']} ({$method['protocol']})\n";
    
    foreach ($users as $user) {
        try {
            $conn = new mysqli(
                $method['host'], 
                $user['user'], 
                $user['pass'], 
                '', // No database initially
                $method['port']
            );
            
            if ($conn->connect_error) {
                echo "  ✗ {$user['user']}: Connection failed - {$conn->connect_error}\n";
            } else {
                echo "  ✓ {$user['user']}: Connected successfully!\n";
                
                // Try to see what databases we can access
                $db_result = $conn->query("SHOW DATABASES");
                if ($db_result) {
                    $dbs = [];
                    while ($row = $db_result->fetch_assoc()) {
                        $dbs[] = $row['Database'];
                    }
                    echo "    Available databases: " . implode(', ', $dbs) . "\n";
                    $db_result->free();
                }
                
                $conn->close();
                break 2; // Success, exit loops
            }
        } catch (Exception $e) {
            echo "  ✗ {$user['user']}: Connection failed - {$e->getMessage()}\n";
        }
    }
    echo "\n";
}

// If we got here, we didn't connect successfully
echo "Attempting to get more detailed error information...\n\n";

// Try to connect and get the specific error
try {
    $conn = new mysqli('127.0.0.1', 'igangaschoolofl_staffs_db', 'AgKzJjZZnT5q58jCahs8', '', 3306);
    if ($conn->connect_error) {
        $error = $conn->connect_error;
        echo "Connection error: {$error}\n";
        
        // Parse the error to understand what's wrong
        if (strpos($error, 'Access denied') !== false) {
            echo "\nThis means:\n";
            echo "1. The username 'igangaschoolofl_staffs_db' is incorrect, OR\n";
            echo "2. The password 'AgKzJjZZnT5q58jCahs8' is incorrect, OR\n";
            echo "3. The user doesn't have permission to connect from 'localhost', OR\n";
            echo "4. The user account is locked or disabled\n";
        } elseif (strpos($error, 'Unknown database') !== false) {
            echo "\nThis means the database 'igangaschoolofl_staffs_db' doesn't exist.\n";
        }
    } else {
        echo "Connected successfully! Now checking database access...\n";
        if ($conn->select_db('igangaschoolofl_staffs_db')) {
            echo "✓ Successfully selected database 'igangaschoolofl_staffs_db'\n";
        } else {
            echo "✗ Failed to select database: {$conn->error}\n";
        }
        $conn->close();
    }
} catch (Exception $e) {
    echo "Exception: {$e->getMessage()}\n";
}
?>