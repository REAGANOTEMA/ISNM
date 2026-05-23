<?php
/**
 * Script to examine and fix MySQL root user authentication.
 */

$host = 'localhost';
$user = 'root';
$pass = ''; // empty password works for localhost
$port = 3307;
$db = '';

echo "Connecting to MySQL at {$host}:{$port} as {$user} with empty password...<br>";
$conn = @new mysqli($host, $user, $pass, $db, $port);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
echo "Connected successfully!<br><br>";

// Check the current user and host
$result = $conn->query("SELECT USER(), CURRENT_USER()");
if ($result) {
    $row = $result->fetch_row();
    echo "USER(): " . htmlspecialchars($row[0]) . "<br>";
    echo "CURRENT_USER(): " . htmlspecialchars($row[1]) . "<br>";
    $result->close();
}

// Check the root user accounts in the mysql.user table
echo "<br>Checking root user accounts:<br>";
$sql = "SELECT Host, User, plugin, authentication_string FROM mysql.user WHERE User = 'root'";
$result = $conn->query($sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        echo "Host: " . htmlspecialchars($row['Host']) . 
             ", User: " . htmlspecialchars($row['User']) . 
             ", Plugin: " . htmlspecialchars($row['plugin']) . 
             ", Auth string length: " . strlen($row['authentication_string']) . "<br>";
    }
    $result->close();
} else {
    echo "Error: " . $conn->error . "<br>";
}

// Now, let's set the root user (for localhost) to use mysql_native_password with password 'ReagaN23#'
// We'll do it for both localhost and 127.0.0.1 if they exist.
$hosts = ['localhost', '127.0.0.1'];
foreach ($hosts as $h) {
    // Check if this host exists for root
    $check = $conn->query("SELECT COUNT(*) FROM mysql.user WHERE User = 'root' AND Host = '$h'");
    if ($check) {
        $count = $check->fetch_row()[0];
        $check->close();
        if ($count > 0) {
            echo "<br>Updating root@{$h} to use mysql_native_password with password 'ReagaN23#'...<br>";
            // Note: We cannot use prepared statements for ALTER USER with parameters for the auth method? 
            // Actually, we can for the password. We'll do:
            // ALTER USER 'root'@'localhost' IDENTIFIED WITH mysql_native_password BY 'ReagaN23#';
            $sql = "ALTER USER '$user'@'$h' IDENTIFIED WITH mysql_native_password BY ?";
            $stmt = $conn->prepare($sql);
            if ($stmt) {
                $stmt->bind_param("s", $pass = 'ReagaN23#');
                if ($stmt->execute()) {
                    echo "Successfully updated root@{$h}.<br>";
                } else {
                    echo "Failed to update root@{$h}: " . $stmt->error . "<br>";
                }
                $stmt->close();
            } else {
                echo "Prepare failed: " . $conn->error . "<br>";
            }
        }
    }
}

// Flush privileges
echo "<br>Flushing privileges...<br>";
if ($conn->query("FLUSH PRIVILEGES")) {
    echo "Privileges flushed successfully.<br>";
} else {
    echo "Failed to flush privileges: " . $conn->error . "<br>";
}

// Now, test the connection with the new password
echo "<br>Testing connection with new password...<br>";
$conn->close(); // Close the old connection

$conn = @new mysqli($host, $user, 'ReagaN23#', $db, $port);
if ($conn->connect_error) {
    echo "Connection failed with new password: " . $conn->connect_error . "<br>";
} else {
    echo "Success! Connected with password 'ReagaN23#'.<br>";
    $conn->close();
}

// Also test from 127.0.0.1
echo "<br>Testing connection from 127.0.0.1 with password...<br>";
$conn = @new mysqli('127.0.0.1', $user, 'ReagaN23#', $db, $port);
if ($conn->connect_error) {
    echo "Connection failed from 127.0.0.1: " . $conn->connect_error . "<br>";
} else {
    echo "Success! Connected from 127.0.0.1 with password.<br>";
    $conn->close();
}
?>