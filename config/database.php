<?php
/**
 * Database Configuration for ISNM Student Management System
 */

// Database connection parameters
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'isnm_db');
define('DB_CHARSET', 'utf8mb4');

// Create database connection
function getConnection() {
    try {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
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

// Close database connection
function closeConnection($conn) {
    if ($conn) {
        $conn->close();
    }
}

// Execute prepared statement safely
function executePrepared($conn, $query, $types, $params) {
    try {
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        
        if (!$stmt->execute()) {
            throw new Exception("Execute failed: " . $stmt->error);
        }
        
        return $stmt;
    } catch (Exception $e) {
        error_log("Query Error: " . $e->getMessage());
        throw $e;
    }
}
?>
