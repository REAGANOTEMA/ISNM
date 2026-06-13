<?php
// Test connection with no password, host 127.0.0.1, port 3307
echo "Testing connection with no password, 127.0.0.1:3307<br>";
$conn = @new mysqli('127.0.0.1', 'root', '', '', 3307);
if ($conn->connect_error) {
    echo "Failed: " . $conn->connect_error . "<br>";
} else {
    echo "Success!<br>";
    $conn->close();
}

// Test connection with no password, localhost, port 3307
echo "<br>Testing connection with no password, localhost:3307<br>";
$conn = @new mysqli('localhost', 'root', '', '', 3307);
if ($conn->connect_error) {
    echo "Failed: " . $conn->connect_error . "<br>";
} else {
    echo "Success!<br>";
    $conn->close();
}

// Test connection with password '', 127.0.0.1, port 3307
echo "<br>Testing connection with password '', 127.0.0.1:3307<br>";
$conn = @new mysqli('127.0.0.1', 'root', '', '', 3307);
if ($conn->connect_error) {
    echo "Failed: " . $conn->connect_error . "<br>";
} else {
    echo "Success!<br>";
    $conn->close();
}

// Test connection with password '', localhost, port 3307
echo "<br>Testing connection with password '', localhost:3307<br>";
$conn = @new mysqli('localhost', 'root', '', '', 3307);
if ($conn->connect_error) {
    echo "Failed: " . $conn->connect_error . "<br>";
} else {
    echo "Success!<br>";
    $conn->close();
}
?>