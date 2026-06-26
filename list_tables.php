<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "ISNM";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$sql = "SHOW TABLES";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        foreach ($row as $table_name) {
            echo $table_name . "\n";
        }
    }
} else {
    echo "0 results";
}
$conn->close();
?>
