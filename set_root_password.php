<?php
/**
 * Set password for root users and optionally set plugin to mysql_native_password.
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

// Get version
if ($result = $conn->query("SELECT VERSION()")) {
    $row = $result->fetch_row();
    echo "MySQL version: " . htmlspecialchars($row[0]) . "<br>";
    $result->close();
}

// Check the root user accounts
echo "<br>Root user accounts before changes:<br>";
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

// Hosts to update
$hosts = ['localhost', '127.0.0.1', '::1'];

foreach ($hosts as $h) {
    // Check if this host exists for root
    $check = $conn->query("SELECT COUNT(*) FROM mysql.user WHERE User = '$user' AND Host = '$h'");
    if ($check) {
        $count = $check->fetch_row()[0];
        $check->close();
        if ($count > 0) {
            echo "<br>Processing root@{$h}...<br>";
            
            // Try to set password and plugin to mysql_native_password
            $safe_pass = mysqli_real_escape_string($conn, $new_password);
            $sql = "ALTER USER '$user'@'$h' IDENTIFIED WITH mysql_native_password BY '$safe_pass'";
            if ($conn->query($sql)) {
                echo "  Successfully set password and plugin to mysql_native_password.<br>";
            } else {
                echo "  Failed to set password and plugin: " . $conn->error . "<br>";
                
                // Fallback: set password only (let it use default plugin)
                $sql2 = "ALTER USER '$user'@'$h' IDENTIFIED BY '$safe_pass'";
                if ($conn->query($sql2)) {
                    echo "  Fallback: Set password only (default plugin).<br>";
                } else {
                    echo "  Fallback also failed: " . $conn->error . "<br>";
                }
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

// Check the root user accounts after changes
echo "<br>Root user accounts after changes:<br>";
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

// Now, test the connection with the new password from each host
echo "<br>Testing connections with new password...<br>";
$test_hosts = ['localhost', '127.0.0.1', '::1'];
foreach ($test_hosts as $h) {
    $conn_test = @new mysqli($h, $user, $new_password, $db, $port);
    if ($conn_test->connect_error) {
        echo "Connection from $h failed: " . $conn_test->connect_error . "<br>";
    } else {
        echo "Connection from $h succeeded!<br>";
        $conn_test->close();
    }
}

// Also test with empty password (should fail now if we set a password)
echo "<br>Testing connections with empty password (should fail if password set)...<br>";
foreach ($test_hosts as $h) {
    $conn_test = @new mysqli($h, $user, '', $db, $port);
    if ($conn_test->connect_error) {
        echo "Connection from $h failed as expected: " . $conn_test->connect_error . "<br>";
    } else {
        echo "Connection from $h succeeded with empty password (unexpected)!<br>";
        $conn_test->close();
    }
}

$conn->close();
?>