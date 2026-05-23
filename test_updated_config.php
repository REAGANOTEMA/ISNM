<?php
/**
 * Test connection with the parameters from database.php but with port 3307
 */

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'igangaschoolofl_students_db');
define('DB_CHARSET', 'utf8mb4');
define('STUDENTS_DB_NAME', 'igangaschoolofl_students_db');

define('STAFF_DB_HOST', 'localhost');
define('STAFF_DB_USER', 'root');
define('STAFF_DB_PASS', '');
define('STAFF_DB_NAME', 'igangaschoolofl_staffs_db');
define('STAFF_DB_CHARSET', 'utf8mb4');

define('WEBSITE_DB_HOST', 'localhost');
define('WEBSITE_DB_USER', 'root');
define('WEBSITE_DB_PASS', '');
define('WEBSITE_DB_NAME', 'igangaschoolofl_website_db');
define('WEBSITE_DB_CHARSET', 'utf8mb4');

function getStudentsConnection() {
    try {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, STUDENTS_DB_NAME, 3307);
        $conn->set_charset(DB_CHARSET);
        
        if ($conn->connect_error) {
            throw new Exception("Database connection failed: " . $conn->connect_error);
        }
        
        return $conn;
    } catch (Exception $e) {
        error_log("Database Error: " . $e->getMessage());
        die("Database connection failed. Please contact administrator.");
    }
}

function getStaffConnection() {
    try {
        $conn = new mysqli(STAFF_DB_HOST, STAFF_DB_USER, STAFF_DB_PASS, STAFF_DB_NAME, 3307);
        $conn->set_charset(STAFF_DB_CHARSET);
        
        if ($conn->connect_error) {
            throw new Exception("Staff database connection failed: " . $conn->connect_error);
        }
        
        return $conn;
    } catch (Exception $e) {
        error_log("Staff Database Error: " . $e->getMessage());
        die("Staff database connection failed. Please contact administrator.");
    }
}

function getWebsiteConnection() {
    try {
        $conn = new mysqli(WEBSITE_DB_HOST, WEBSITE_DB_USER, WEBSITE_DB_PASS, WEBSITE_DB_NAME, 3307);
        $conn->set_charset(WEBSITE_DB_CHARSET);
        
        if ($conn->connect_error) {
            throw new Exception("Website database connection failed: " . $conn->connect_error);
        }
        
        return $conn;
    } catch (Exception $e) {
        error_log("Website Database Error: " . $e->getMessage());
        die("Website database connection failed. Please contact administrator.");
    }
}

// Test the connections
echo "Testing students connection...<br>";
$conn = getStudentsConnection();
if ($conn) {
    echo "Success!<br>";
    $result = $conn->query("SELECT VERSION()");
    if ($result) {
        $row = $result->fetch_row();
        echo "MySQL version: " . $row[0] . "<br>";
        $result->close();
    }
    $conn->close();
}

echo "<br>Testing staff connection...<br>";
$conn = getStaffConnection();
if ($conn) {
    echo "Success!<br>";
    $conn->close();
}

echo "<br>Testing website connection...<br>";
$conn = getWebsiteConnection();
if ($conn) {
    echo "Success!<br>";
    $conn->close();
}
?>