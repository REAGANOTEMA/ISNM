<?php
/**
 * Database Configuration for ISNM Student Management System
 */

/**
 * Students Database Connection
 * Hostname: localhost
 * Database: igangaschoolofl_students_db
 * Username: igangaschoolofl_students_db
 * Password: hbkKdmMHUfHTHuxWKPRf
 */
// ── Local XAMPP credentials ──────────────────────────────────────────────────
define('DB_HOST',           '127.0.0.1');
define('DB_PORT',           3307);
define('DB_CHARSET',        'utf8mb4');
define('STUDENTS_DB_NAME',  'igangaschoolofl_students_db');
define('STUDENTS_DB_USER',  'root');
define('STUDENTS_DB_PASS',  '');

define('STAFF_DB_HOST',    '127.0.0.1');
define('STAFF_DB_USER',    'root');
define('STAFF_DB_PASS',    '');
define('STAFF_DB_PORT',    3307);
define('STAFF_DB_NAME',    'igangaschoolofl_staffs_db');
define('STAFF_DB_CHARSET', 'utf8mb4');

define('WEBSITE_DB_HOST',    '127.0.0.1');
define('WEBSITE_DB_USER',    'root');
define('WEBSITE_DB_PASS',    '');
define('WEBSITE_DB_PORT',    3307);
define('WEBSITE_DB_NAME',    'igangaschoolofl_website_db');
define('WEBSITE_DB_CHARSET', 'utf8mb4');

define('ICT_DB_HOST',    '127.0.0.1');
define('ICT_DB_USER',    'root');
define('ICT_DB_PASS',    '');
define('ICT_DB_PORT',    3307);
define('ICT_DB_NAME',    'igangaschoolofl_ict');
define('ICT_DB_CHARSET', 'utf8mb4');

if (!function_exists('getICTConnection')) {
    function getICTConnection() {
        mysqli_report(MYSQLI_REPORT_OFF);
        $conn = new mysqli(ICT_DB_HOST, ICT_DB_USER, ICT_DB_PASS, ICT_DB_NAME, ICT_DB_PORT);
        if ($conn->connect_error) {
            error_log("ICT DB Error: " . $conn->connect_error);
            return null;
        }
        $conn->set_charset(ICT_DB_CHARSET);
        return $conn;
    }
}

/**
 * Legacy compatibility functions with conflict protection
 */
if (!function_exists('getStudentsConnection')) {
    function getStudentsConnection() {
        mysqli_report(MYSQLI_REPORT_OFF);
        $conn = new mysqli(DB_HOST, STUDENTS_DB_USER, STUDENTS_DB_PASS, STUDENTS_DB_NAME, DB_PORT);
        if ($conn->connect_error) {
            error_log("Students DB Error: " . $conn->connect_error);
            return null;
        }
        $conn->set_charset(DB_CHARSET);
        return $conn;
    }
}

if (!function_exists('getStaffConnection')) {
    function getStaffConnection() {
        mysqli_report(MYSQLI_REPORT_OFF);
        $conn = new mysqli(STAFF_DB_HOST, STAFF_DB_USER, STAFF_DB_PASS, STAFF_DB_NAME, STAFF_DB_PORT);
        if ($conn->connect_error) {
            error_log("Staff DB Error: " . $conn->connect_error);
            return null;
        }
        $conn->set_charset(STAFF_DB_CHARSET);
        return $conn;
    }
}

if (!function_exists('getWebsiteConnection')) {
    function getWebsiteConnection() {
        mysqli_report(MYSQLI_REPORT_OFF);
        $conn = new mysqli(WEBSITE_DB_HOST, WEBSITE_DB_USER, WEBSITE_DB_PASS, WEBSITE_DB_NAME, WEBSITE_DB_PORT);
        if ($conn->connect_error) {
            error_log("Website DB Error: " . $conn->connect_error);
            return null;
        }
        $conn->set_charset(WEBSITE_DB_CHARSET);
        return $conn;
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
        // Allow various ISNM formats: U001/..., JUL24/..., STU...
        if (empty($index_number)) return false;
        return strlen($index_number) >= 5;
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
        
        // Accept 9 or 10 digit local numbers, or 12 digit international
        if (strlen($clean_phone) === 10 && preg_match('/^0[7]\d{8}$/', $clean_phone)) {
            return true; // Format: 0771234567
        } elseif (strlen($clean_phone) === 12 && preg_match('/^256[7]\d{8}$/', $clean_phone)) {
            return true; // Format: 256771234567
        } elseif (strlen($clean_phone) === 9 && preg_match('/^7\d{8}$/', $clean_phone)) {
            return true; // Format: 771234567
        }
        
        return false;
    }
}
?>
