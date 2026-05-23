<?php
session_start();
require 'config/database.php';
require 'auth-service.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Testing database connection...".PHP_EOL;

function testImport() {
    $conn = getConnection();
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    // Test query
    $result = $conn->query("SELECT 1");
    if ($result) {
        echo "Database connection works".PHP_EOL;
    } else {
        echo "Query failed: " . $conn->error.PHP_EOL;
    }
    
    // Check if students table exists
    $tables = $conn->query("SHOW TABLES LIKE 'students'");
    if ($tables->num_rows > 0) {
        echo "Students table exists".PHP_EOL;
    } else {
        echo "Students table does not exist".PHP_EOL;
    }
    
    // Show table structure
    $desc = $conn->query("DESCRIBE students");
    echo "Table structure:".PHP_EOL;
    while ($row = $desc->fetch_assoc()) {
        echo "  ".$row["Field"]." (".$row["Type"].")".PHP_EOL;
    }
    
    // Check current data
    $count = $conn->query("SELECT COUNT(*) as cnt FROM students")->fetch_assoc();
    echo "Current student count: ".$count['cnt'].PHP_EOL;
}

testImport();
?>