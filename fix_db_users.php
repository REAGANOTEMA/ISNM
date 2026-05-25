<?php
header('Content-Type: text/plain');

// Try multiple connection methods for Windows MySQL
define('DB_HOST', '127.0.0.1');
define('DB_USER', 'root');
define('DB_PASS', ''); // Try empty password
define('DB_PORT', 3306);
define('DB_CHARSET', 'utf8mb4');

$methods = [
    ['host' => '127.0.0.1', 'port' => 3306, 'socket' => null, 'desc' => 'TCP/IP 127.0.0.1:3306'],
    ['host' => 'localhost', 'port' => 3306, 'socket' => null, 'desc' => 'TCP/IP localhost:3306'],
    ['host' => '.', 'port' => 3306, 'socket' => null, 'desc' => 'TCP/IP .:3306 (named pipes alternative)'],
];

foreach ($methods as $method) {
    echo "Trying: " . $method['desc'] . "\n";
    try {
        $conn = new mysqli(
            $method['host'], 
            $method['user'] ?? DB_USER, 
            $method['pass'] ?? DB_PASS, 
            '', 
            $method['port'], 
            $method['socket']
        );
        
        if ($conn->connect_error) {
            echo "  Failed: " . $conn->connect_error . "\n";
            continue;
        }
        
        $conn->set_charset(DB_CHARSET);
        echo "  SUCCESS!\n";
        
        // Test querying
        $result = $conn->query("SELECT USER()");
        if ($result) {
            $row = $result->fetch_assoc();
            echo "  Connected as: " . $row['USER()'] . "\n";
            $result->free();
        }
        
        $conn->close();
        break; // Success, exit loop
    } catch (Exception $e) {
        echo "  Error: " . $e->getMessage() . "\n";
    }
    echo "\n";
}

// Now try to check if we can create the required users
echo "\n" . str_repeat("=", 50) . "\n";
echo "ATTTEMPTING TO CREATE REQUIRED USERS\n";
echo str_repeat("=", 50) . "\n";

// Try one more time with root
$conn = null;
try {
    $conn = new mysqli('127.0.0.1', 'root', '', '', 3306);
    if ($conn->connect_error) {
        throw new Exception("Cannot connect as root: " . $conn->connect_error);
    }
    $conn->set_charset(DB_CHARSET);
    echo "Connected as root successfully!\n\n";
    
    // Check if our target users exist
    $users_to_check = [
        'igangaschoolofl_staffs_db',
        'igangaschoolofl_students_db',
        'igangaschoolofl_website_db'
    ];
    
    foreach ($users_to_check as $user) {
        $result = $conn->query("SELECT COUNT(*) as exists FROM mysql.user WHERE User = '$user' AND Host = 'localhost'");
        if ($result) {
            $row = $result->fetch_assoc();
            $exists = $row['exists'] > 0;
            echo "User '$user' exists: " . ($exists ? 'YES' : 'NO') . "\n";
            $result->free();
        }
    }
    
    echo "\n";
    
    // Try to create the users if they don't exist
    foreach ($users_to_check as $user) {
        // Determine password based on username
        $password = '';
        if ($user === 'igangaschoolofl_staffs_db') {
            $password = 'AgKzJjZZnT5q58jCahs8';
        } elseif ($user === 'igangaschoolofl_students_db') {
            $password = 'hbkKdmMHUfHTHuxWKPRf';
        } elseif ($user === 'igangaschoolofl_website_db') {
            $password = 'AaCH75gXpekcFQj5wPZn';
        }
        
        // Check if user exists
        $result = $conn->query("SELECT COUNT(*) as exists FROM mysql.user WHERE User = '$user' AND Host = 'localhost'");
        $row = $result->fetch_assoc();
        $exists = $row['exists'] > 0;
        $result->free();
        
        if (!$exists) {
            echo "Creating user '$user'...\n";
            // Create user
            $sql = "CREATE USER '$user'@'localhost' IDENTIFIED BY '$password'";
            if ($conn->query($sql)) {
                echo "  User created successfully.\n";
                
                // Grant privileges on respective database
                $db_name = '';
                if ($user === 'igangaschoolofl_staffs_db') {
                    $db_name = 'igangaschoolofl_staffs_db';
                } elseif ($user === 'igangaschoolofl_students_db') {
                    $db_name = 'igangaschoolofl_students_db';
                } elseif ($user === 'igangaschoolofl_website_db') {
                    $db_name = 'igangaschoolofl_website_db';
                }
                
                if ($db_name) {
                    $grant_sql = "GRANT ALL PRIVILEGES ON `$db_name`.* TO '$user'@'localhost'";
                    if ($conn->query($grant_sql)) {
                        echo "  Privileges granted on $db_name.\n";
                    } else {
                        echo "  Failed to grant privileges: " . $conn->error . "\n";
                    }
                    
                    // Flush privileges
                    $conn->query("FLUSH PRIVILEGES");
                }
            } else {
                echo "  Failed to create user: " . $conn->error . "\n";
            }
        } else {
            echo "User '$user' already exists, checking/setting password...\n";
            // Update password
            $password = '';
            if ($user === 'igangaschoolofl_staffs_db') {
                $password = 'AgKzJjZZnT5q58jCahs8';
            } elseif ($user === 'igangaschoolofl_students_db') {
                $password = 'hbkKdmMHUfHTHuxWKPRf';
            } elseif ($user === 'igangaschoolofl_website_db') {
                $password = 'AaCH75gXpekcFQj5wPZn';
            }
            
            $sql = "ALTER USER '$user'@'localhost' IDENTIFIED BY '$password'";
            if ($conn->query($sql)) {
                echo "  Password updated successfully.\n";
            } else {
                echo "  Failed to update password: " . $conn->error . "\n";
                // Try older syntax for MySQL < 5.7
                $sql = "SET PASSWORD FOR '$user'@'localhost' = PASSWORD('$password')";
                if ($conn->query($sql)) {
                    echo "  Password updated with SET PASSWORD syntax.\n";
                } else {
                    echo "  Failed to update password with SET PASSWORD: " . $conn->error . "\n";
                }
            }
        }
    }
    
    $conn->close();
} catch (Exception $e) {
    echo "Failed to connect as root: " . $e->getMessage() . "\n";
    if ($conn) {
        $conn->close();
    }
}
?>