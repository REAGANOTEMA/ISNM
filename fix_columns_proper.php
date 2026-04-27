<?php
// Fix missing columns in students table with proper MySQL syntax
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
    
    // Check if columns exist before adding them
    $check_columns = [
        'login_attempts' => "ALTER TABLE students ADD COLUMN login_attempts int(11) DEFAULT 0",
        'account_locked' => "ALTER TABLE students ADD COLUMN account_locked tinyint(1) DEFAULT 0", 
        'locked_until' => "ALTER TABLE students ADD COLUMN locked_until datetime DEFAULT NULL",
        'last_login' => "ALTER TABLE students ADD COLUMN last_login datetime DEFAULT NULL"
    ];
    
    foreach ($check_columns as $column_name => $alter_sql) {
        // Check if column exists first
        $check_sql = "SHOW COLUMNS FROM students LIKE '$column_name'";
        $result = $conn->query($check_sql);
        
        if ($result && $result->num_rows == 0) {
            // Column doesn't exist, add it
            if ($conn->query($alter_sql)) {
                echo "Column '$column_name' added successfully.<br>";
            } else {
                echo "Error adding column '$column_name': " . $conn->error . "<br>";
            }
        } else {
            echo "Column '$column_name' already exists.<br>";
        }
    }
    
    echo "<br><strong>Database columns updated successfully!</strong><br>";
    echo "<a href='student-login.php'>Test Student Login</a>";
    
    $conn->close();
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
