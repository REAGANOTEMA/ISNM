<?php
/**
 * Check MySQL version and try to alter user with proper syntax.
 */

$host = 'localhost';
$user = 'root';
$pass = ''; // empty password
$port = 3307;
$db = '';

echo "Connecting to MySQL at {$host}:{$port} as {$user} with empty password...<br>";
$conn = @new mysqli($host, $user, $pass, $db, $port);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
echo "Connected successfully!<br><br>";

// Get version
if ($result = $conn->query("SELECT VERSION()")) {
    $row = $result->fetch_row();
    echo "MySQL version: " . htmlspecialchars($row[0]) . "<br>";
    $result->close();
}

// Check the root user accounts
echo "<br>Root user accounts:<br>";
$sql = "SELECT Host, User, plugin FROM mysql.user WHERE User = 'root'";
$result = $conn->query($sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        echo "Host: " . htmlspecialchars($row['Host']) . 
             ", User: " . htmlspecialchars($row['User']) . 
             ", Plugin: " . htmlspecialchars($row['plugin']) . "<br>";
    }
    $result->close();
}

// Try to alter user with the syntax: ALTER USER ... IDENTIFIED WITH mysql_native_password BY ...
$hosts = ['localhost', '127.0.0.1'];
$new_password = 'ReagaN23#';
foreach ($hosts as $h) {
    $check = $conn->query("SELECT COUNT(*) FROM mysql.user WHERE User = '$user' AND Host = '$h'");
    if ($check) {
        $count = $check->fetch_row()[0];
        $check->close();
        if ($count > 0) {
            echo "<br>Trying to alter user '$user'@'$h' with mysql_native_password and password...<br>";
            $sql = "ALTER USER '$user'@'$h' IDENTIFIED WITH mysql_native_password BY ?";
            $stmt = $conn->prepare($sql);
            if ($stmt) {
                $stmt->bind_param("s", $new_password);
                if ($stmt->execute()) {
                    echo "Success!<br>";
                } else {
                    echo "Failed: " . $stmt->error . "<br>";
                    // Try alternative syntax
                    echo "Trying alternative: ALTER USER ... IDENTIFIED BY ...<br>";
                    $sql2 = "ALTER USER '$user'@'$h' IDENTIFIED BY ?";
                    $stmt2 = $conn->prepare($sql2);
                    if ($stmt2) {
                        $stmt2->bind_param("s", $new_password);
                        if ($stmt2->execute()) {
                            echo "  Password changed via IDENTIFIED BY.<br>";
                            // Now try to set the plugin
                            echo "  Trying to set plugin to mysql_native_password...<br>";
                            $sql3 = "ALTER USER '$user'@'$h' IDENTIFIED WITH mysql_native_password;";
                            if ($conn->query($sql3)) {
                                echo "  Plugin set successfully.<br>";
                            } else {
                                echo "  Failed to set plugin: " . $conn->error . "<br>";
                            }
                        } else {
                            echo "  Failed to change password: " . $stmt2->error . "<br>";
                        }
                        $stmt2->close();
                    } else {
                        echo "  Prepare failed for IDENTIFIED BY: " . $conn->error . "<br>";
                    }
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

// Now test connections
echo "<br>Testing connections with new password...<br>";
$test_hosts = ['localhost', '127.0.0.1'];
foreach ($test_hosts as $h) {
    $conn_test = @new mysqli($h, $user, $new_password, $db, $port);
    if ($conn_test->connect_error) {
        echo "Connection from $h failed: " . $conn_test->connect_error . "<br>";
    } else {
        echo "Connection from $h succeeded!<br>";
        $conn_test->close();
    }
}

$conn->close();
?>