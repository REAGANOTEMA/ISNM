<?php
header('Content-Type: text/plain');

// Try to connect as root via TCP with no password
define('DB_HOST', '127.0.0.1');
define('DB_USER', 'root');
define('DB_PASS', ''); // empty password
define('DB_PORT', 3306);
define('DB_CHARSET', 'utf8mb4');

echo "Testing connection as root via TCP (127.0.0.1) with no password...\n";
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
    $result->free();
    
    // Check if the required databases exist
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
    
    // If we can connect as root, let's try to create the missing users
    // We'll create the users for staff and students databases if they don't exist
    // But first, let's see if we can access the mysql database to check users
    
    echo "\nChecking existing users in mysql.user:\n";
    $users_result = $conn->query("SELECT User, Host, plugin FROM mysql.user WHERE User IN ('root', 'igangaschoolofl_staffs_db', 'igangaschoolofl_students_db', 'igangaschoolofl_website_db')");
    if ($users_result) {
        while ($user = $users_result->fetch_assoc()) {
            echo "User: " . $user['User'] . ", Host: " . $user['Host'] . ", Plugin: " . $user['plugin'] . "\n";
        }
        $users_result->free();
    } else {
        echo "Error querying mysql.user: " . $conn->error . "\n";
    }
    
    $conn->close();
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>