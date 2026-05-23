<?php
/**
 * Test connection using port 3307 and explicit protocol
 */

$host = '127.0.0.1';
$port = 3307;
$user = 'root';
$pass = 'ReagaN23#';
$db = '';

echo "Testing connection to {$host}:{$port}...<br>";

// Try with TCP/IP explicitly
$conn = @new mysqli($host, $user, $pass, $db, $port);
if ($conn->connect_error) {
    echo "Failed: " . $conn->connect_error . "<br>";
    echo "Error number: " . $conn->connect_errno . "<br>";
    
    // Try to force older authentication method if it's a plugin issue
    if (strpos($conn->connect_error, 'caching_sha2_password') !== false) {
        echo "Attempting to connect with mysql_native_password...<br>";
        // This is a workaround - we can't directly set the auth method in mysqli constructor
        // But we can try using PDO or see if there's a way
    }
} else {
    echo "Success!<br>";
    
    if (!empty($db)) {
        if ($conn->select_db($db)) {
            echo "Selected database '{$db}' successfully.<br>";
        } else {
            echo "Failed to select database: " . $conn->error . "<br>";
        }
    }
    
    $conn->close();
}
echo "<br>";

// Now test with database name
echo "Testing connection to database igangaschoolofl_students_db...<br>";
$conn2 = @new mysqli($host, $user, $pass, 'igangaschoolofl_students_db', $port);
if ($conn2->connect_error) {
    echo "Failed: " . $conn2->connect_error . "<br>";
} else {
    echo "Success! Connected to students database.<br>";
    $conn2->close();
}
?>