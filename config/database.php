<?php
/**
 * Database Configuration for ISNM Student Management System
 */

// Database connection parameters
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', 'ReagaN23#');
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

/**
 * Sanitize input to prevent SQL injection and XSS
 * @param string $input
 * @return string
 */
function sanitizeInput($input) {
    $input = trim($input);
    $input = stripslashes($input);
    $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
    return $input;
}

/**
 * Validate email format
 * @param string $email
 * @return bool
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate phone number (Uganda format)
 * @param string $phone
 * @return bool
 */
function validatePhone($phone) {
    // Remove non-numeric characters
    $clean_phone = preg_replace('/[^0-9]/', '', $phone);
    
    // Check if it's a valid Uganda phone number (with or without country code)
    // Accept formats: 0771234567 or 256771234567
    if (strlen($clean_phone) === 10 && preg_match('/^0[7]\d{8}$/', $clean_phone)) {
        return true; // Format: 0771234567
    } elseif (strlen($clean_phone) === 12 && preg_match('/^256[7]\d{8}$/', $clean_phone)) {
        return true; // Format: 256771234567
    }
    
    return false;
}

/**
 * Validate student index number format
 * @param string $index_number
 * @return bool
 */
function validateIndexNumber($index_number) {
    // Format: U001/CM/056/16
    return preg_match('/^U\d{3}\/(CM|CN|DMORDN)\/\d{3}\/\d{2}$/', $index_number);
}
?>
