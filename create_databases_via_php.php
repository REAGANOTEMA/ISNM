<?php
/**
 * Create the three databases for ISNM system using the working connection.
 */

$host = '127.0.0.1';
$user = 'root';
$pass = '';
$port = 3307;

echo "Connecting to MySQL at {$host}:{$port} as {$user}...<br>";
$conn = @new mysqli($host, $user, $pass, '', $port);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
echo "Connected successfully!<br><br>";

// Databases to create
$databases = [
    'igangaschoolofl_students_db',
    'igangaschoolofl_staffs_db',
    'igangaschoolofl_website_db'
];

foreach ($databases as $db) {
    // Check if database exists
    $check = $conn->query("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '$db'");
    if ($check && $check->num_rows > 0) {
        echo "Database '$db' already exists.<br>";
        $check->close();
    } else {
        // Create database
        if ($conn->query("CREATE DATABASE `$db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci")) {
            echo "Database '$db' created successfully.<br>";
        } else {
            echo "Failed to create database '$db': " . $conn->error . "<br>";
        }
    }
}

// Show the created databases
echo "<br>Listing databases matching 'igangaschoolofl%':<br>";
$result = $conn->query("SHOW DATABASES LIKE 'igangaschoolofl%'");
if ($result) {
    while ($row = $result->fetch_row()) {
        echo " - " . $row[0] . "<br>";
    }
    $result->close();
} else {
    echo "Error: " . $conn->error . "<br>";
}

$conn->close();
?>