<?php
/**
 * Unified Database Connection for ISNM School Management System
 * Single database connection for all authentication and user management
 */

// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'isnm_db'); // Updated to correct database name
define('DB_USER', 'root'); // Adjust to your actual database user
define('DB_PASS', 'ReagaN23#'); // Updated database password

/**
 * Get database connection
 * @return mysqli
 */
function getDatabaseConnection() {
    static $conn = null;
    
    if ($conn === null) {
        try {
            $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
            
            if ($conn->connect_error) {
                throw new Exception("Database connection failed: " . $conn->connect_error);
            }
            
            // Set charset to utf8mb4 for full Unicode support
            $conn->set_charset("utf8mb4");
            
        } catch (Exception $e) {
            // Log error and display user-friendly message
            error_log("Database connection error: " . $e->getMessage());
            die("Database connection failed. Please contact system administrator.");
        }
    }
    
    return $conn;
}

/**
 * Sanitize input to prevent SQL injection and XSS
 * @param string $input
 * @return string
 */
function sanitizeInput($input) {
    $conn = getDatabaseConnection();
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
    
    // Check if it's a valid Uganda phone number (10 digits starting with 7)
    return (strlen($clean_phone) === 10 && preg_match('/^7\d{9}$/', $clean_phone));
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

/**
 * Check if user exists by email
 * @param string $email
 * @return bool
 */
function userExistsByEmail($email) {
    $conn = getDatabaseConnection();
    $email = sanitizeInput($email);
    
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->num_rows > 0;
}

/**
 * Check if student exists by index number
 * @param string $index_number
 * @return bool
 */
function studentExistsByIndexNumber($index_number) {
    $conn = getDatabaseConnection();
    $index_number = sanitizeInput($index_number);
    
    $stmt = $conn->prepare("SELECT id FROM users WHERE index_number = ? AND type = 'student'");
    $stmt->bind_param("s", $index_number);
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->num_rows > 0;
}

/**
 * Create users table if it doesn't exist
 */
function createUsersTableIfNotExists() {
    $conn = getDatabaseConnection();
    
    $sql = "CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        index_number VARCHAR(50) UNIQUE,
        full_name VARCHAR(255) NOT NULL,
        email VARCHAR(255) UNIQUE,
        phone VARCHAR(20),
        password VARCHAR(255),
        role VARCHAR(50) NOT NULL,
        type ENUM('student', 'staff') NOT NULL,
        status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        last_login TIMESTAMP NULL,
        login_attempts INT DEFAULT 0,
        locked_until TIMESTAMP NULL,
        INDEX (email),
        INDEX (index_number),
        INDEX (role),
        INDEX (type),
        INDEX (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    if (!$conn->query($sql)) {
        throw new Exception("Error creating users table: " . $conn->error);
    }
}

/**
 * Initialize database (create tables if needed)
 */
function initializeDatabase() {
    try {
        createUsersTableIfNotExists();
    } catch (Exception $e) {
        error_log("Database initialization error: " . $e->getMessage());
        throw $e;
    }
}

// Auto-initialize database when this file is included
try {
    initializeDatabase();
} catch (Exception $e) {
    // Don't die here, let the calling code handle the error
    error_log("Database initialization failed: " . $e->getMessage());
}

?>
