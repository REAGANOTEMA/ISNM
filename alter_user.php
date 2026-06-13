<?php
/**
 * Test connection via socket and attempt to alter user
 */

$socket = "C:/xampp/mysql/mysql.sock";
$user = 'root';
$pass = ''; // From [client] in my.ini, no password

echo "Testing connection via socket: $socket <br>";

$conn = @new mysqli(null, $user, $pass, null, null, $socket);
if ($conn->connect_error) {
    echo "Failed: " . $conn->connect_error . "<br>";
} else {
    echo "Success! Connected via socket.<br>";
    
    // Let's see what user we are
    $result = $conn->query("SELECT USER()");
    if ($result) {
        $row = $result->fetch_row();
        echo "Connected as: " . $row[0] . "<br>";
    }
    
    // Now, try to alter the root user to use mysql_native_password
    // Note: we need to know the host for the root user. Let's check.
    $hosts = $conn->query("SELECT Host FROM mysql.user WHERE User = 'root'");
    echo "Root user hosts: <br>";
    while ($host = $hosts->fetch_row()) {
        echo " - " . $host[0] . "<br>";
    }
    
    // We'll try to alter for localhost and 127.0.0.1 and % if they exist.
    $alter_sql = "ALTER USER 'root'@'localhost' IDENTIFIED WITH mysql_native_password BY ?";
    $stmt = $conn->prepare($alter_sql);
    if ($stmt) {
        $new_pass = '';
        $stmt->bind_param("s", $new_pass);
        if ($stmt->execute()) {
            echo "Altered user root@localhost successfully.<br>";
        } else {
            echo "Failed to alter user: " . $stmt->error . "<br>";
        }
        $stmt->close();
    } else {
        echo "Prepare failed: " . $conn->error . "<br>";
    }
    
    // Also try for 127.0.0.1
    $alter_sql = "ALTER USER 'root'@'127.0.0.1' IDENTIFIED WITH mysql_native_password BY ?";
    $stmt = $conn->prepare($alter_sql);
    if ($stmt) {
        $new_pass = '';
        $stmt->bind_param("s", $new_pass);
        if ($stmt->execute()) {
            echo "Altered user root@127.0.0.1 successfully.<br>";
        } else {
            echo "Failed to alter user: " . $stmt->error . "<br>";
        }
        $stmt->close();
    } else {
        echo "Prepare failed: " . $conn->error . "<br>";
    }
    
    // Flush privileges
    if ($conn->query("FLUSH PRIVILEGES")) {
        echo "Flushed privileges.<br>";
    } else {
        echo "Failed to flush privileges: " . $conn->error . "<br>";
    }
    
    $conn->close();
}
?>