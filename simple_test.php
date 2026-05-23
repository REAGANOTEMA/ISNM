<?php
// Simple test - try to connect on different ports with empty password
$ports = [3306, 3307];

foreach ($ports as $port) {
    echo "Trying port $port with empty password...<br>";
    $conn = @new mysqli('127.0.0.1', 'root', '', '', $port);
    if ($conn->connect_error) {
        echo "Failed: " . $conn->connect_error . "<br>";
    } else {
        echo "Success!<br>";
        $conn->close();
        break;
    }
    echo "<br>";
}

// Now try with password from phpMyAdmin
echo "<br>Trying with password from phpMyAdmin...<br>";
foreach ($ports as $port) {
    echo "Trying port $port with password...<br>";
    $conn = @new mysqli('127.0.0.1', 'root', 'ReagaN23#', '', $port);
    if ($conn->connect_error) {
        echo "Failed: " . $conn->connect_error . "<br>";
    } else {
        echo "Success!<br>";
        $conn->close();
        break;
    }
    echo "<br>";
}
?>