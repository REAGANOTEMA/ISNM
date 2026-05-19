<?php
/**
 * Database Configuration for ISNM Student Management System
 */

// Database connection parameters
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', 'ReagaN23#');
define('DB_NAME', 'students_db');
define('DB_CHARSET', 'utf8mb4');
define('STUDENTS_DB_NAME', 'students_db');
define('WEBSITE_DB_NAME', 'website_db');

// Staff database connection parameters
define('STAFF_DB_HOST', 'localhost');
define('STAFF_DB_USER', 'root');
define('STAFF_DB_PASS', 'ReagaN23#');
define('STAFF_DB_NAME', 'staffs_db');
define('STAFF_DB_CHARSET', 'utf8mb4');

// Legacy compatibility functions with conflict protection
if (!function_exists('getStudentsConnection')) {
    function getStudentsConnection() {
        try {
            $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, STUDENTS_DB_NAME);
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
}

if (!function_exists('getStaffConnection')) {
    // Create staff database connection (for staff authentication)
    function getStaffConnection() {
        try {
            $conn = new mysqli(STAFF_DB_HOST, STAFF_DB_USER, STAFF_DB_PASS, STAFF_DB_NAME);
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
}

if (!function_exists('getWebsiteConnection')) {
    // Create website database connection
    function getWebsiteConnection() {
        try {
            $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, WEBSITE_DB_NAME);
            $conn->set_charset(DB_CHARSET);
            
            if ($conn->connect_error) {
                throw new Exception("Website database connection failed: " . $conn->connect_error);
            }
            
            return $conn;
        } catch (Exception $e) {
            error_log("Website Database Error: " . $e->getMessage());
            die("Website database connection failed. Please contact administrator.");
        }
    }
}

if (!function_exists('getConnection')) {
    // Default connection — students_db (legacy name kept for compatibility)
    function getConnection() {
        return getStudentsConnection();
    }
}

if (!function_exists('closeConnection')) {
    // Close database connection
    function closeConnection($conn) {
        if ($conn) {
            $conn->close();
        }
    }
}

if (!function_exists('executePrepared')) {
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
}

if (!function_exists('validateIndexNumber')) {
    function validateIndexNumber($index_number) {
        // Format: U001/CM/056/16
        return preg_match('/^U\d{3}\/(CM|CN|DMORDN)\/\d{3}\/\d{2}$/', $index_number);
    }
}

if (!function_exists('studentExistsByIndexNumber')) {
    function studentExistsByIndexNumber($indexNumber) {
        $conn = getConnection();
        
        $stmt = $conn->prepare("SELECT id FROM users WHERE index_number = ? AND role = 'student'");
        $stmt->bind_param("s", $indexNumber);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->num_rows > 0;
    }
}

if (!function_exists('userExistsByEmail')) {
    function userExistsByEmail($email) {
        $conn = getConnection();
        
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->num_rows > 0;
    }
}

/**
 * Sanitize input to prevent SQL injection and XSS
 * @param string $input
 * @return string
 */
if (!function_exists('sanitizeInput')) {
    function sanitizeInput($input) {
        $input = trim($input);
        $input = stripslashes($input);
        $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
        return $input;
    }
}

/**
 * Validate email format
 * @param string $email
 * @return bool
 */
if (!function_exists('validateEmail')) {
    function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
}

/**
 * Validate phone number (Uganda format)
 * @param string $phone
 * @return bool
 */
if (!function_exists('validatePhone')) {
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
}
?>
