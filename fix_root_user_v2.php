<?php
/**
 * Script to fix MySQL root user authentication by setting password and plugin.
 */

$host = 'localhost';
$user = 'root';
$pass = ''; // empty password works for localhost
$port = 3307;
$db = '';
$new_password = 'ReagaN23#';

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

// Now, let's set the root user (for each host) to use the new password and then update the plugin to mysql_native_password.
$hosts = ['localhost', '127.0.0.1', '::1'];
foreach ($hosts as $h) {
    // Check if this host exists for root
    $check = $conn->query("SELECT COUNT(*) FROM mysql.user WHERE User = '$user' AND Host = '$h'");
    if ($check) {
        $count = $check->fetch_row()[0];
        $check->close();
        if ($count > 0) {
            echo "<br>Processing root@{$h}...<br>";
            
            // Step 1: Change the password
            $safe_pass = mysqli_real_escape_string($conn, $new_password);
            $sql = "ALTER USER '$user'@'$h' IDENTIFIED BY '$safe_pass'";
            if ($conn->query($sql)) {
                echo "  Password changed successfully.<br>";
            } else {
                echo "  Failed to change password: " . $conn->error . "<br>";
            }
            
            // Step 2: Update the plugin to mysql_native_password
            $sql = "UPDATE mysql.user SET plugin='mysql_native_password' WHERE User='$user' AND Host='$h'";
            if ($conn->query($sql)) {
                echo "  Plugin updated to mysql_native_password.<br>";
            } else {
                echo "  Failed to update plugin: " . $conn->error . "<br>";
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

// Now, test the connection with the new password from localhost
echo "<br>Testing connection from localhost with new password...<br>";
$conn->close(); // Close the old connection

$conn = @new mysqli($host, $user, $new_password, $db, $port);
if ($conn->connect_error) {
    echo "Connection failed from localhost: " . $conn->connect_error . "<br>";
} else {
    echo "Success! Connected from localhost with password.<br>";
    $conn->close();
}

// Test from 127.0.0.1
echo "<br>Testing connection from 127.0.0.1 with new password...<br>";
$conn = @new mysqli('127.0.0.1', $user, $new_password, $db, $port);
if ($conn->connect_error) {
    echo "Connection failed from 127.0.0.1: " . $conn->connect_error . "<br>";
} else {
    echo "Success! Connected from 127.0.0.1 with password.<br>";
    $conn->close();
}

// Test from ::1 (if needed)
echo "<br>Testing connection from ::1 with new password...<br>";
$conn = @new mysqli('::1', $user, $new_password, $db, $port);
if ($conn->connect_error) {
    echo "Connection failed from ::1: " . $conn->connect_error . "<br>";
} else {
    echo "Success! Connected from ::1 with password.<br>";
    $conn->close();
}
?>