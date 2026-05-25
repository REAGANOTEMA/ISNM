<?php
header('Content-Type: text/plain');

// Test connection via socket
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', ''); // empty password
define('DB_SOCKET', 'C:/xampp/mysql/mysql.sock');
define('DB_CHARSET', 'utf8mb4');

echo "Testing connection via socket...\n";
echo "Socket: " . DB_SOCKET . "\n";
echo "User: " . DB_USER . "\n\n";

try {
    // For socket connection, we set the host to localhost and specify the socket
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, null, 3306, DB_SOCKET);
    if ($conn->connect_error) {
        throw new Exception("Socket connection failed: " . $conn->connect_error);
    }
    $conn->set_charset(DB_CHARSET);
    echo "Socket connection successful!\n\n";
    
    // Check the current user and plugin
    $result = $conn->query("SELECT USER(), CURRENT_USER()");
    if ($result) {
        $row = $result->fetch_assoc();
        echo "Connected as: " . $row['USER()'] . "\n";
        echo "Current user: " . $row['CURRENT_USER()'] . "\n";
        $result->free();
    }
    
    // Check the plugin for root user
    $result = $conn->query("SELECT plugin FROM mysql.user WHERE User='root' AND Host='localhost'");
    if ($result) {
        $row = $result->fetch_assoc();
        echo "Root user plugin: " . $row['plugin'] . "\n";
        $result->free();
    }
    
    // List databases
    $result = $conn->query("SHOW DATABASES");
    echo "\nDatabases:\n";
    while ($row = $result->fetch_assoc()) {
        echo "- " . $row['Database'] . "\n";
    }
    $result->free();
    
    $conn->close();
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>