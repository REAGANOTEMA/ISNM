<?php
// Fix missing columns in students table
$host = 'localhost';
$dbname = 'isnm_db';
$username = 'root';
$password = 'ReagaN23#';

try {
    $conn = new mysqli($host, $username, $password, $dbname);
    
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    echo "Connected to database successfully.<br>";
    
    // Add missing columns to students table
    $alter_sqls = [
        "ALTER TABLE students ADD COLUMN IF NOT EXISTS login_attempts int(11) DEFAULT 0",
        "ALTER TABLE students ADD COLUMN IF NOT EXISTS account_locked tinyint(1) DEFAULT 0",
        "ALTER TABLE students ADD COLUMN IF NOT EXISTS locked_until datetime DEFAULT NULL",
        "ALTER TABLE students ADD COLUMN IF NOT EXISTS last_login datetime DEFAULT NULL"
    ];
    
    foreach ($alter_sqls as $sql) {
        if ($conn->query($sql)) {
            echo "Column added successfully: " . $sql . "<br>";
        } else {
            echo "Error adding column: " . $conn->error . "<br>";
        }
    }
    
    echo "<br><strong>Database columns updated successfully!</strong><br>";
    echo "<a href='student-login.php'>Test Student Login</a>";
    
    $conn->close();
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
