<?php
$host = '127.0.0.1';
$user = 'root';
$pass = '';
$port = 3307;
$db = 'igangaschoolofl_staffs_db';

$conn = new mysqli($host, $user, $pass, '', $port); // Connect without selecting database
if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}
echo "Connected to MySQL server<br>";

// Drop the database if it exists and recreate it
if ($conn->query("DROP DATABASE IF EXISTS `$db`")) {
    echo "Database `$db` dropped successfully.<br>";
} else {
    echo "Warning: Could not drop database: " . $conn->error . "<br>";
}

if ($conn->query("CREATE DATABASE `$db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci")) {
    echo "Database `$db` created successfully.<br>";
} else {
    echo "Error creating database: " . $conn->error . "<br>";
    $conn->close();
    exit;
}

// Now select the database
if (!$conn->select_db($db)) {
    echo "Error selecting database: " . $conn->error . "<br>";
    $conn->close();
    exit;
}
echo "Selected database `$db`<br>";

// Read the SQL file
$sql = file_get_contents('sql/staffs/04_final_complete_staffs_database.sql');

// Remove DELIMITER lines
$sql = preg_replace('/^\s*DELIMITER\s+.*\s*$/m', '', $sql);

// Also, we need to change the delimiter inside the procedures to something else? 
// Without the DELIMITER command, the semicolons inside the procedure body will be treated as statement terminators.
// We have to change the semicolons inside the procedure bodies to something else, but that's complex.
// Alternatively, we can split the SQL into statements by semicolon, but we have to ignore semicolons inside stored procedures and triggers.
// Given the time, let's try to run the multi-query on the cleaned SQL and see if it works.
// If it fails, we might have to split the SQL manually.

// Disable foreign key checks to allow tables to be created in any order
if (!$conn->query("SET FOREIGN_KEY_CHECKS=0")) {
    echo "Warning: Could not disable foreign key checks: " . $conn->error . "<br>";
}

// Execute the entire script
if ($conn->multi_query($sql)) {
    echo 'Staffs database tables created successfully<br>';
    do {
        if ($res = $conn->store_result()) {
            $res->free();
        }
    } while ($conn->more_results() && $conn->next_result());
} else {
    echo 'Error creating staffs database tables: ' . $conn->error . "<br>";
    echo "SQL error at position: " . $conn->errno . "<br>";
}

// Re-enable foreign key checks
if (!$conn->query("SET FOREIGN_KEY_CHECKS=1")) {
    echo "Warning: Could not re-enable foreign key checks: " . $conn->error . "<br>";
}

$conn->close();
?>