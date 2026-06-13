<?php
/**
 * Try to connect using mysqli_init and setting the default auth protocol to mysql_native_password
 */

$host = 'localhost';
$user = 'root';
$pass = '';
$db = '';
$port = 3306;

echo "Trying to connect with mysqli_init and MYSQLI_DEFAULT_AUTH_PROTOCOL...<br>";

$conn = mysqli_init();
if (!$conn) {
    die("mysqli_init failed");
}

// Set the option to use mysql_native_password
if (!mysqli_options($conn, MYSQLI_DEFAULT_AUTH_PROTOCOL, 'mysql_native_password')) {
    echo "Setting MYSQLI_DEFAULT_AUTH_PROTOCOL failed: " . mysqli_error($conn) . "<br>";
}

// Now try to connect
if (!mysqli_real_connect($conn, $host, $user, $pass, $db, $port)) {
    echo "Connect failed: " . mysqli_connect_error() . "<br>";
} else {
    echo "Success!<br>";
    
    // Now, let's try to alter the user to use mysql_native_password (if not already)
    // We'll do this for localhost and 127.0.0.1
    $hosts = ['localhost', '127.0.0.1'];
    foreach ($hosts as $h) {
        $sql = "ALTER USER '$user'@'$h' IDENTIFIED WITH mysql_native_password BY ?";
        $stmt = mysqli_prepare($conn, $sql);
        if ($stmt) {
            $new_pass = ''; // We are trying to set to empty? Actually, we want to keep the password as empty? 
            // But the user in phpMyAdmin uses ''. Let's try to set the password to empty and then we can change it later.
            // However, we are connected with empty password, so let's try to set the authentication method and keep the empty password.
            // But note: the ALTER USER command requires the current password? Actually, no, we are changing the authentication method and setting a new password.
            // We want to set the authentication method to mysql_native_password and keep the password as empty? 
            // But the error says we are getting access denied with empty password, so maybe the password is not empty? 
            // Let's check the phpMyAdmin config: it says password = ''
            // So we should set the password to '' and the authentication method to mysql_native_password.
            $new_pass = '';
            mysqli_stmt_bind_param($stmt, "s", $new_pass);
            if (mysqli_stmt_execute($stmt)) {
                echo "Altered user '$user'@'$h' successfully.<br>";
            } else {
                echo "Failed to alter user '$user'@'$h': " . mysqli_stmt_error($stmt) . "<br>";
            }
            mysqli_stmt_close($stmt);
        } else {
            echo "Prepare failed for '$user'@'$h': " . mysqli_error($conn) . "<br>";
        }
    }
    
    // Flush privileges
    if (mysqli_query($conn, "FLUSH PRIVILEGES")) {
        echo "Flushed privileges.<br>";
    } else {
        echo "Failed to flush privileges: " . mysqli_error($conn) . "<br>";
    }
    
    mysqli_close($conn);
}
?>