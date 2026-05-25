<?php
header('Content-Type: text/plain');

// Try to connect as root to see what databases exist
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', ''); // XAMPP default root password is usually empty
define('DB_PORT', 3307);
define('DB_CHARSET', 'utf8mb4');

echo "Testing connection as root...\n";
echo "Host: " . DB_HOST . "\n";
echo "Port: " . DB_PORT . "\n";
echo "User: " . DB_USER . "\n\n";

try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, '', DB_PORT);
    if ($conn->connect_error) {
        throw new Exception("Root connection failed: " . $conn->connect_error);
    }
    $conn->set_charset(DB_CHARSET);
    echo "Root connection successful!\n\n";
    
    // List databases
    $result = $conn->query("SHOW DATABASES");
    echo "Databases:\n";
    while ($row = $result->fetch_assoc()) {
        echo "- " . $row['Database'] . "\n";
    }
    
    $conn->close();
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>