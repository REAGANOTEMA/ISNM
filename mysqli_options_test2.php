<?php
/**
 * Try to connect using mysqli_init and setting the default auth protocol to mysql_native_password
 * with the password from phpMyAdmin.
 */

$host = '127.0.0.1'; // Try 127.0.0.1 first
$user = 'root';
$pass = 'ReagaN23#';
$db = ''; // No database initially
$port = 3307; // From my.ini, the server is running on 3307

echo "Trying to connect to {$host}:{$port} with user {$user} and password from phpMyAdmin...<br>";
echo "Setting MYSQLI_DEFAULT_AUTH_PROTOCOL to mysql_native_password<br>";

$conn = mysqli_init();
if (!$conn) {
    die("mysqli_init failed");
}

// Set the option to use mysql_native_password
if (!mysqli_options($conn, MYSQLI_DEFAULT_AUTH_PROTOCOL, 'mysql_native_password')) {
    echo "Setting MYSQLI_DEFAULT_AUTH_PROTOCOL failed: " . mysqli_error($conn) . "<br>";
}

// Also set the timeout and maybe other options
mysqli_options($conn, MYSQLI_OPT_CONNECT_TIMEOUT, 5);

// Now try to connect
if (!mysqli_real_connect($conn, $host, $user, $pass, $db, $port)) {
    echo "Connect failed: " . mysqli_connect_error() . "<br>";
    echo "Error number: " . mysqli_connect_errno() . "<br>";
} else {
    echo "Success! Connected to MySQL server.<br>";
    
    // Now, let's see what user we are and what hosts we have for root.
    $result = mysqli_query($conn, "SELECT USER(), CURRENT_USER()");
    if ($result) {
        $row = mysqli_fetch_row($result);
        echo "USER(): " . $row[0] . "<br>";
        echo "CURRENT_USER(): " . $row[1] . "<br>";
        mysqli_free_result($result);
    }
    
    // Let's check the authentication method for the root user in the mysql.user table
    $sql = "SELECT Host, User, plugin FROM mysql.user WHERE User = 'root'";
    $result = mysqli_query($conn, $sql);
    if ($result) {
        echo "<br>Root user accounts:<br>";
        while ($row = mysqli_fetch_assoc($result)) {
            echo "Host: " . $row['Host'] . ", User: " . $row['User'] . ", Plugin: " . $row['plugin'] . "<br>";
        }
        mysqli_free_result($result);
    }
    
    // Now, we want to change the root user to use mysql_native_password if it's not already.
    // But note: we are connected as root, so we can alter the user.
    // We'll change the authentication method and keep the same password.
    $hosts = ['localhost', '127.0.0.1'];
    foreach ($hosts as $h) {
        // Check if this host exists for root
        $check = mysqli_query($conn, "SELECT COUNT(*) FROM mysql.user WHERE User = 'root' AND Host = '$h'");
        if ($check) {
            $count = mysqli_fetch_array($check)[0];
            mysqli_free_result($check);
            if ($count > 0) {
                $sql = "ALTER USER '$user'@'$h' IDENTIFIED WITH mysql_native_password BY ?";
                $stmt = mysqli_prepare($conn, $sql);
                if ($stmt) {
                    mysqli_stmt_bind_param($stmt, "s", $pass);
                    if (mysqli_stmt_execute($stmt)) {
                        echo "Altered user '$user'@'$h' to use mysql_native_password.<br>";
                    } else {
                        echo "Failed to alter user '$user'@'$h': " . mysqli_stmt_error($stmt) . "<br>";
                    }
                    mysqli_stmt_close($stmt);
                } else {
                    echo "Prepare failed for '$user'@'$h': " . mysqli_error($conn) . "<br>";
                }
            }
        }
    }
    
    // Flush privileges
    if (mysqli_query($conn, "FLUSH PRIVILEGES")) {
        echo "Flushed privileges.<br>";
    } else {
        echo "Failed to flush privileges: " . mysqli_error($conn) . "<br>";
    }
    
    // Now, let's test the connection again with the new settings (should be the same)
    // But we can close and reopen to test.
    mysqli_close($conn);
    
    echo "<br>Testing connection again after alter...<br>";
    $conn2 = mysqli_init();
    mysqli_options($conn2, MYSQLI_DEFAULT_AUTH_PROTOCOL, 'mysql_native_password');
    if (!mysqli_real_connect($conn2, $host, $user, $pass, $db, $port)) {
        echo "Reconnect failed: " . mysqli_connect_error() . "<br>";
    } else {
        echo "Reconnect success!<br>";
        mysqli_close($conn2);
    }
}
?>