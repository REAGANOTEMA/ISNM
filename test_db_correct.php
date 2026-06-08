<?php
header('Content-Type: text/plain');

// Test staff database connection with port 3306 (actual running port)
define('STAFF_DB_HOST', 'localhost');
define('STAFF_DB_USER', 'igangaschoolofl_staffs_db');
define('STAFF_DB_PASS', 'AgKzJjZZnT5q58jCahs8');
define('STAFF_DB_NAME', 'igangaschoolofl_staffs_db');
define('STAFF_DB_PORT', 3306);
define('STAFF_DB_CHARSET', 'utf8mb4');

echo "Testing staff database connection (port 3306)...\n";
echo "Host: " . STAFF_DB_HOST . "\n";
echo "Port: " . STAFF_DB_PORT . "\n";
echo "User: " . STAFF_DB_USER . "\n";
echo "Database: " . STAFF_DB_NAME . "\n\n";

try {
    $conn = new mysqli(STAFF_DB_HOST, STAFF_DB_USER, STAFF_DB_PASS, STAFF_DB_NAME, STAFF_DB_PORT);
    if ($conn->connect_error) {
        throw new Exception("Unable to connect to the staff database. Please contact your system administrator. (Error Reference: STAFF_DB_001) " . $conn->connect_error);
    }
    $conn->set_charset(STAFF_DB_CHARSET);
    echo "Staff database connection successful!\n";
    
    // Test a simple query
    $result = $conn->query("SELECT 1");
    if ($result) {
        echo "Query test successful!\n";
        $result->free();
    }
    
    $conn->close();
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

// Test students database connection with port 3306
define('DB_HOST', 'localhost');
define('DB_USER', 'igangaschoolofl_students_db');
define('DB_PASS', 'hbkKdmMHUfHTHuxWKPRf');
define('DB_NAME', 'igangaschoolofl_students_db');
define('DB_PORT', 3306);
define('DB_CHARSET', 'utf8mb4');

echo "\nTesting students database connection (port 3306)...\n";
echo "Host: " . DB_HOST . "\n";
echo "Port: " . DB_PORT . "\n";
echo "User: " . DB_USER . "\n";
echo "Database: " . DB_NAME . "\n\n";

try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
    if ($conn->connect_error) {
        throw new Exception("Unable to connect to the students database. Please contact your system administrator. (Error Reference: STUDENTS_DB_001) " . $conn->connect_error);
    }
    $conn->set_charset(DB_CHARSET);
    echo "Students database connection successful!\n";
    
    // Test a simple query
    $result = $conn->query("SELECT 1");
    if ($result) {
        echo "Query test successful!\n";
        $result->free();
    }
    
    $conn->close();
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>