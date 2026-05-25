<?php
// Create databases if they don't exist
$databases = [
    ['name' => 'igangaschoolofl_staffs_db', 'user' => 'igangaschoolofl_staffs_db', 'pass' => 'AgKzJjZZnT5q58jCahs8'],
    ['name' => 'igangaschoolofl_students_db', 'user' => 'igangaschoolofl_students_db', 'pass' => 'hbkKdmMHUfHTHuxWKPRf'],
    ['name' => 'igangaschoolofl_website_db', 'user' => 'igangaschoolofl_website_db', 'pass' => 'AaCH75gXpekcFQj5wPZn']
];

foreach ($databases as $db) {
    try {
        // Connect to MySQL server
        $conn = new mysqli('localhost', 'root', '');
        if ($conn->connect_error) {
            throw new Exception("MySQL connection failed: " . $conn->connect_error);
        }
        
        // Create database if it doesn't exist
        $dbName = $db['name'];
        $createDbQuery = "CREATE DATABASE IF NOT EXISTS `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
        if ($conn->query($createDbQuery)) {
            echo "Database '" . $db['name'] . "' created or already exists\n";
        } else {
            throw new Exception("Failed to create database '" . $db['name'] . "': " . $conn->error);
        }
        
        // Grant privileges to the database user
        $grantQuery = "GRANT ALL PRIVILEGES ON `$dbName`.* TO '" . $db['user'] . "'@'localhost' IDENTIFIED BY '" . $db['pass'] . "'";
        if ($conn->query($grantQuery)) {
            echo "Privileges granted for user '" . $db['user'] . "' on database '" . $db['name'] . "'\n";
        } else {
            throw new Exception("Failed to grant privileges for user '" . $db['user'] . "': " . $conn->error);
        }
        
        $conn->close();
    } catch (Exception $e) {
        echo 'Error processing database "' . $db['name'] . '": ' . $e->getMessage() . "\n";
    }
}

echo "Database setup completed.\n";
?>