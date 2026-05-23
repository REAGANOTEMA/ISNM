<?php
/**
 * Test PHP mysqli connection with password from phpMyAdmin config
 */

require_once 'config/database.php';

// But note: the config/database.php uses empty password. Let's override for testing.
$host = '127.0.0.1';
$user = 'root';
$pass = 'ReagaN23#';
$db = ''; // We'll test without selecting a database first

echo "Testing PHP mysqli connection with password...<br>";

$conn = @new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    echo "Failed: " . $conn->connect_error . "<br>";
    echo "Error number: " . $conn->connect_errno . "<br>";
} else {
    echo "Success!<br>";
    // Now try to select a database
    if ($conn->select_db('igangaschoolofl_students_db')) {
        echo "Selected students database successfully.<br>";
    } else {
        echo "Failed to select database: " . $conn->error . "<br>";
    }
    $conn->close();
}
?>