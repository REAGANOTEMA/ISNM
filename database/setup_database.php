<?php
// Database setup script for ISNM School Management System
// This script creates the database and imports the structure

// Database configuration
$host = 'localhost';
$dbname = 'isnm_db';
$username = 'root';
$password = 'ReagaN23#';

try {
    // Connect to MySQL without selecting database
    $conn = new mysqli($host, $username, $password);
    
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    // Create database if it doesn't exist
    $sql = "CREATE DATABASE IF NOT EXISTS $dbname CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
    if ($conn->query($sql)) {
        echo "Database '$dbname' created successfully or already exists.<br>";
    } else {
        echo "Error creating database: " . $conn->error . "<br>";
    }
    
    // Select the database
    $conn->select_db($dbname);
    
    // Read and execute the SQL file
    $sql_file = __DIR__ . '/enhanced_login_system.sql';
    if (file_exists($sql_file)) {
        $sql_content = file_get_contents($sql_file);
        
        // Split SQL into individual statements
        $statements = array_filter(array_map('trim', explode(';', $sql_content)));
        
        foreach ($statements as $statement) {
            if (!empty($statement) && !preg_match('/^--/', $statement)) {
                if ($conn->query($statement)) {
                    echo "Statement executed successfully.<br>";
                } else {
                    echo "Error executing statement: " . $conn->error . "<br>";
                    echo "Statement: " . substr($statement, 0, 100) . "...<br>";
                }
            }
        }
        
        echo "<br><strong>Database setup completed successfully!</strong><br>";
        echo "You can now use the login system with the following credentials:<br><br>";
        
        echo "<h3>Staff Login Credentials:</h3>";
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Role</th><th>Username</th><th>Password</th></tr>";
        echo "<tr><td>Director General</td><td>john.mugisha</td><td>password</td></tr>";
        echo "<tr><td>Chief Executive Officer</td><td>sarah.nakato</td><td>password</td></tr>";
        echo "<tr><td>School Principal</td><td>peter.lutaaya</td><td>password</td></tr>";
        echo "<tr><td>School Secretary</td><td>joy.nabwire</td><td>password</td></tr>";
        echo "<tr><td>Academic Registrar</td><td>henry.mugisha</td><td>password</td></tr>";
        echo "<tr><td>School Bursar</td><td>patience.nabasumba</td><td>password</td></tr>";
        echo "<tr><td>HR Manager</td><td>robert.ssewanyana</td><td>password</td></tr>";
        echo "</table><br>";
        
        echo "<h3>Student Login Credentials:</h3>";
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>NSIN Number</th><th>First Name</th><th>Contact Number</th></tr>";
        echo "<tr><td>CM1234567890123</td><td>Aisha</td><td>256771234567</td></tr>";
        echo "<tr><td>CM1234567890124</td><td>Brian</td><td>256772345678</td></tr>";
        echo "<tr><td>CM1234567890125</td><td>Catherine</td><td>256773456789</td></tr>";
        echo "<tr><td>CM1234567890126</td><td>David</td><td>256774567890</td></tr>";
        echo "<tr><td>CM1234567890127</td><td>Esther</td><td>256775678901</td></tr>";
        echo "</table><br>";
        
        echo "<h3>Access Links:</h3>";
        echo "<a href='../staff-login.php'>Staff Login</a><br>";
        echo "<a href='../student-login.php'>Student Login</a><br>";
        echo "<a href='../organogram.php'>Organogram (with login links)</a><br>";
        
    } else {
        echo "SQL file not found: $sql_file<br>";
    }
    
    $conn->close();
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "<br>";
}
?>
