<?php
header('Content-Type: text/plain');

// Create missing database users using phpMyAdmin credentials
define('DB_HOST', '127.0.0.1');
define('DB_USER', 'root');
define('DB_PASS', 'ReagaN23#'); // From phpMyAdmin config
define('DB_PORT', 3306);
define('DB_CHARSET', 'utf8mb4');

echo "Creating missing database users...\n";
echo "Host: " . DB_HOST . "\n";
echo "Port: " . DB_PORT . "\n";
echo "User: " . DB_USER . "\n\n";

try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, '', DB_PORT);
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    $conn->set_charset(DB_CHARSET);
    echo "Connected successfully!\n\n";
    
    // Define users and their passwords
    $users = [
        'igangaschoolofl_staffs_db' => [
            'password' => 'AgKzJjZZnT5q58jCahs8',
            'database' => 'igangaschoolofl_staffs_db'
        ],
        'igangaschoolofl_students_db' => [
            'password' => 'hbkKdmMHUfHTHuxWKPRf',
            'database' => 'igangaschoolofl_students_db'
        ],
        'igangaschoolofl_website_db' => [
            'password' => 'AaCH75gXpekcFQj5wPZn',
            'database' => 'igangaschoolofl_website_db'
        ]
    ];
    
    foreach ($users as $username => $info) {
        $password = $info['password'];
        $database = $info['database'];
        
        echo "Processing user: $username\n";
        
        // Check if user already exists
        $check_sql = "SELECT COUNT(*) as user_exists FROM mysql.user WHERE User = '$username' AND Host = 'localhost'";
        $check_result = $conn->query($check_sql);
        if (!$check_result) {
            echo "  Error checking user existence: " . $conn->error . "\n";
            continue;
        }
        $row = $check_result->fetch_assoc();
        $user_exists = $row['user_exists'] > 0;
        $check_result->free();
        
        if ($user_exists) {
            echo "  User already exists, updating password...\n";
            // Update password
            $update_sql = "ALTER USER '$username'@'localhost' IDENTIFIED BY '$password'";
            if ($conn->query($update_sql)) {
                echo "  Password updated successfully.\n";
            } else {
                echo "  Failed to update password with ALTER USER: " . $conn->error . "\n";
                // Try older syntax
                $update_sql = "SET PASSWORD FOR '$username'@'localhost' = PASSWORD('$password')";
                if ($conn->query($update_sql)) {
                    echo "  Password updated with SET PASSWORD.\n";
                } else {
                    echo "  Failed to update password with SET PASSWORD: " . $conn->error . "\n";
                }
            }
        } else {
            echo "  Creating new user...\n";
            // Create user
            $create_sql = "CREATE USER '$username'@'localhost' IDENTIFIED BY '$password'";
            if ($conn->query($create_sql)) {
                echo "  User created successfully.\n";
            } else {
                echo "  Failed to create user: " . $conn->error . "\n";
                continue; // Skip granting privileges if user creation failed
            }
        }
        
        // Grant privileges on the specific database
        echo "  Granting privileges on $database...\n";
        $grant_sql = "GRANT ALL PRIVILEGES ON `$database`.* TO '$username'@'localhost'";
        if ($conn->query($grant_sql)) {
            echo "  Privileges granted successfully.\n";
        } else {
            echo "  Failed to grant privileges: " . $conn->error . "\n";
        }
        
        echo "\n";
    }
    
    // Flush privileges
    echo "Flushing privileges...\n";
    if ($conn->query("FLUSH PRIVILEGES")) {
        echo "Privileges flushed successfully.\n";
    } else {
        echo "Failed to flush privileges: " . $conn->error . "\n";
    }
    
    $conn->close();
    echo "\nDone!\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    if ($conn) {
        $conn->close();
    }
}
?>