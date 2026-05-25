<?php
header('Content-Type: text/plain');

// Test connection using phpMyAdmin credentials
define('DB_HOST', '127.0.0.1');
define('DB_USER', 'root');
define('DB_PASS', 'ReagaN23#'); // From phpMyAdmin config
define('DB_PORT', 3306);
define('DB_CHARSET', 'utf8mb4');

echo "Testing connection with phpMyAdmin root credentials...\n";
echo "Host: " . DB_HOST . "\n";
echo "Port: " . DB_PORT . "\n";
echo "User: " . DB_USER . "\n\n";

try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, '', DB_PORT);
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    $conn->set_charset(DB_CHARSET);
    echo "Connection successful!\n\n";
    
    // Check the current user
    $result = $conn->query("SELECT USER(), CURRENT_USER()");
    if ($result) {
        $row = $result->fetch_assoc();
        echo "Connected as: " . $row['USER()'] . "\n";
        echo "Current user: " . $row['CURRENT_USER()'] . "\n";
        $result->free();
    }
    
    // List databases
    $result = $conn->query("SHOW DATABASES");
    echo "\nDatabases:\n";
    while ($row = $result->fetch_assoc()) {
        echo "- " . $row['Database'] . "\n";
    }
    $result->free();
    
    // Check if our target databases exist
    $required_dbs = [
        'igangaschoolofl_staffs_db',
        'igangaschoolofl_students_db',
        'igangaschoolofl_website_db'
    ];
    
    echo "\nChecking required databases:\n";
    foreach ($required_dbs as $db) {
        $db_exists = false;
        $db_check = $conn->query("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '$db'");
        if ($db_check && $db_check->num_rows > 0) {
            $db_exists = true;
        }
        echo ($db_exists ? "[EXISTS] " : "[MISSING] ") . $db . "\n";
    }
    
    // Check if our target users exist
    $required_users = [
        'igangaschoolofl_staffs_db',
        'igangaschoolofl_students_db',
        'igangaschoolofl_website_db'
    ];
    
    echo "\nChecking required users:\n";
    foreach ($required_users as $user) {
        $user_exists = false;
        $user_check = $conn->query("SELECT User FROM mysql.user WHERE User = '$user' AND Host = 'localhost'");
        if ($user_check && $user_check->num_rows > 0) {
            $user_exists = true;
        }
        echo ($user_exists ? "[EXISTS] " : "[MISSING] ") . $user . "@localhost\n";
    }
    
    $conn->close();
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>