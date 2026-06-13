<?php
// Test connection using phpMyAdmin settings
$host = '127.0.0.1';
$user = 'root';
$pass = '';
$port = 3307; // from my.ini

echo "Testing connection to {$host}:{$port} with user {$user} and password from phpMyAdmin...<br>";

$conn = @new mysqli($host, $user, $pass, '', $port);
if ($conn->connect_error) {
    echo "Failed: " . $conn->connect_error . "<br>";
    echo "Error number: " . $conn->connect_errno . "<br>";
} else {
    echo "Success!<br>";
    // Now, let's see what databases exist
    $result = $conn->query("SHOW DATABASES LIKE 'igangaschoolofl%'");
    if ($result) {
        echo "Found matching databases:<br>";
        while ($row = $result->fetch_row()) {
            echo " - " . $row[0] . "<br>";
        }
        $result->close();
    } else {
        echo "Error showing databases: " . $conn->error . "<br>";
    }
    $conn->close();
}
?>