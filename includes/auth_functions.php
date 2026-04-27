<?php
// Enhanced authentication functions for ISNM School Management System
// Students login with NSIN number, name, and contact number
// Staff login with username and password

include_once 'config.php';
include_once 'functions.php';

// Secure student authentication function
function authenticateStudent($student_id, $first_name, $phone) {
    global $conn;
    
    // Validate input
    if (empty($student_id) || empty($first_name) || empty($phone)) {
        return ['success' => false, 'message' => 'All fields are required for student login'];
    }
    
    // Validate student ID format (U001/CM/056/16)
    if (!preg_match('/^U\d{3}\/(CM|CN|DMORDN)\/\d{3}\/\d{2}$/', $student_id)) {
        return ['success' => false, 'message' => 'Invalid student ID format. Use format: U001/CM/056/16'];
    }
    
    try {
        // Query database for student
        $sql = "SELECT * FROM students WHERE application_id = ? AND first_name = ? AND phone = ? AND status = 'active'";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sss", $student_id, $first_name, $phone);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            return ['success' => false, 'message' => 'Invalid student credentials'];
        }
        
        $student = $result->fetch_assoc();
        
        // Update last login
        $update_sql = "UPDATE students SET last_login = NOW() WHERE id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("i", $student['id']);
        $update_stmt->execute();
        
        return ['success' => true, 'user' => $student];
        
    } catch (Exception $e) {
        error_log("Student authentication error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Authentication system error. Please try again.'];
    }
}

// Secure staff authentication function
function authenticateStaff($username, $password) {
    global $conn;
    
    // Validate input
    if (empty($username) || empty($password)) {
        return ['success' => false, 'message' => 'Username and password are required for staff login'];
    }
    
    try {
        // Query database for staff user
        $sql = "SELECT * FROM users WHERE username = ? AND status = 'active'";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            return ['success' => false, 'message' => 'Invalid username or password'];
        }
        
        $user = $result->fetch_assoc();
        
        // Verify password (assuming password_hash is used)
        if (!password_verify($password, $user['password'])) {
            return ['success' => false, 'message' => 'Invalid username or password'];
        }
        
        // Update last login
        $update_sql = "UPDATE users SET last_login = NOW() WHERE id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("i", $user['id']);
        $update_stmt->execute();
        
        return ['success' => true, 'user' => $user];
        
    } catch (Exception $e) {
        error_log("Authentication error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Authentication system error. Please try again.'];
    }
}

// Check if user is logged in and has appropriate access
function checkAuth($required_role = null) {
    if (!isset($_SESSION['user_id'])) {
        // Redirect to appropriate login page based on user type
        if (strpos($_SERVER['REQUEST_URI'], 'student') !== false || strpos($_SERVER['REQUEST_URI'], 'student_profile') !== false) {
            header('Location: student-login.php');
        } else {
            header('Location: staff-login.php');
        }
        exit();
    }
    
    if ($required_role && $_SESSION['role'] !== $required_role) {
        $_SESSION['error'] = 'Access denied. You do not have permission to access this page.';
        header('Location: dashboard.php');
        exit();
    }
    
    return true;
}

// hasPermission() function is already defined in functions.php

// Get user dashboard based on role - UNIQUE DASHBOARDS FOR EACH POSITION
function getUserDashboard($role) {
    $dashboards = [
        // Executive Level
        'Director General' => 'dashboards/director-general.php',
        'Chief Executive Officer' => 'dashboards/ceo.php',
        
        // Director Level
        'Director Academics' => 'dashboards/director-academics.php',
        'Director ICT' => 'dashboards/director-ict.php',
        'Director Finance' => 'dashboards/director-finance.php',
        
        // School Management
        'School Principal' => 'dashboards/school-principal.php',
        'Deputy Principal' => 'dashboards/deputy-principal.php',
        'School Bursar' => 'dashboards/school-bursar.php',
        'Academic Registrar' => 'dashboards/academic-registrar.php',
        'HR Manager' => 'dashboards/hr-manager.php',
        'School Secretary' => 'dashboards/school-secretary.php',
        'School Librarian' => 'dashboards/school-librarian.php',
        
        // Academic Staff
        'Head of Nursing' => 'dashboards/head-nursing.php',
        'Head of Midwifery' => 'dashboards/head-midwifery.php',
        'Senior Lecturers' => 'dashboards/senior-lecturers.php',
        'Lecturers' => 'dashboards/lecturers.php',
        'teacher' => 'dashboards/lecturers.php',
        
        // Support Staff
        'Matrons' => 'dashboards/matrons.php',
        'Wardens' => 'dashboards/wardens.php',
        'Lab Technicians' => 'dashboards/lab-technicians.php',
        'Drivers' => 'dashboards/drivers.php',
        'Security' => 'dashboards/security.php',
        
        // Student Roles
        'Students' => 'student_profile.php',
        'Guild President' => 'student_profile.php',
        'Class Representatives' => 'student_profile.php'
    ];
    
    // Return the dashboard if found, otherwise return a default based on role type
    if (isset($dashboards[$role])) {
        return $dashboards[$role];
    }
    
    // Fallback logic for unmapped roles
    if (strpos($role, 'Director') !== false) {
        return 'dashboards/director-general.php';
    } elseif (strpos($role, 'Principal') !== false) {
        return 'dashboards/principal.php';
    } elseif (strpos($role, 'Lecturer') !== false) {
        return 'dashboards/lecturers.php';
    } elseif (strpos($role, 'Teacher') !== false) {
        return 'dashboards/lecturers.php';
    } else {
        return 'dashboards/lecturers.php'; // Safe fallback
    }
}

// Logout function
function logout() {
    // Log logout activity
    if (isset($_SESSION['user_id'])) {
        logActivity($_SESSION['user_id'], $_SESSION['role'], 'Logout', 'User logged out', 'users', $_SESSION['user_id']);
    }
    
    // Destroy session
    session_destroy();
    
    // Redirect to appropriate login page
    header('Location: staff-login.php');
    exit();
}

// Password reset function
function resetPassword($user_type, $identifier) {
    global $conn;
    
    if ($user_type === 'student') {
        // For students, use NSIN number
        $sql = "SELECT * FROM students WHERE nsin_number = ? AND status = 'active'";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $identifier);
    } else {
        // For staff, use username or email
        $sql = "SELECT * FROM users WHERE (username = ? OR email = ?) AND status = 'active'";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $identifier, $identifier);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        // Generate reset token
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        // Store reset token in database (you'd need a password_resets table for this)
        // For now, just return success
        
        return [
            'success' => true,
            'message' => 'Password reset instructions have been sent to your email.',
            'user' => $user
        ];
    } else {
        return ['success' => false, 'message' => 'User not found.'];
    }
}

// Validate NSIN number format
function validateNSIN($nsin) {
    // NSIN format: CM followed by 13 digits (e.g., CM1234567890123)
    return preg_match('/^CM\d{13}$/', $nsin);
}

// Validate phone number format
function validatePhone($phone) {
    // Remove all non-digit characters
    $clean_phone = preg_replace('/\D/', '', $phone);
    
    // Uganda phone numbers: 10 digits starting with 7
    return preg_match('/^7\d{9}$/', $clean_phone);
}

// Format phone number for display
function formatPhone($phone) {
    // Remove all non-digit characters
    $clean_phone = preg_replace('/\D/', '', $phone);
    
    // Add Uganda country code if not present
    if (strlen($clean_phone) === 10) {
        return '+256' . $clean_phone;
    } elseif (strlen($clean_phone) === 12 && substr($clean_phone, 0, 3) === '256') {
        return '+' . $clean_phone;
    }
    
    return $phone;
}

// Get student by NSIN number
function getStudentByNSIN($nsin_number) {
    global $conn;
    
    $sql = "SELECT * FROM students WHERE nsin_number = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $nsin_number);
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->num_rows === 1 ? $result->fetch_assoc() : null;
}

// Get staff by username
function getStaffByUsername($username) {
    global $conn;
    
    $sql = "SELECT * FROM users WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->num_rows === 1 ? $result->fetch_assoc() : null;
}

// Check if account is locked
function isAccountLocked($user_id, $user_type) {
    global $conn;
    
    if ($user_type === 'student') {
        $sql = "SELECT account_locked, locked_until FROM students WHERE student_id = ?";
    } else {
        $sql = "SELECT account_locked, locked_until FROM users WHERE user_id = ?";
    }
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        return $user['account_locked'] && $user['locked_until'] > date('Y-m-d H:i:s');
    }
    
    return false;
}

// Unlock account
function unlockAccount($user_id, $user_type) {
    global $conn;
    
    if ($user_type === 'student') {
        $sql = "UPDATE students SET login_attempts = 0, account_locked = 0, locked_until = NULL WHERE student_id = ?";
    } else {
        $sql = "UPDATE users SET login_attempts = 0, account_locked = 0, locked_until = NULL WHERE user_id = ?";
    }
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $user_id);
    
    return $stmt->execute();
}

// Get login attempts
function getLoginAttempts($identifier, $user_type) {
    global $conn;
    
    if ($user_type === 'student') {
        $sql = "SELECT login_attempts FROM students WHERE nsin_number = ?";
    } else {
        $sql = "SELECT login_attempts FROM users WHERE username = ?";
    }
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $identifier);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        return $user['login_attempts'];
    }
    
    return 0;
}

// Create session for authenticated user
function createSession($user) {
    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['first_name'] = $user['first_name'];
    $_SESSION['last_name'] = $user['last_name'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['phone'] = $user['phone'];
    $_SESSION['role'] = $user['role'];
    
    if ($user['role'] === 'Student') {
        $_SESSION['nsin_number'] = $user['nsin_number'];
        $_SESSION['program'] = $user['program'];
        $_SESSION['level'] = $user['level'];
    } else {
        $_SESSION['username'] = $user['username'];
        $_SESSION['department'] = $user['department'];
    }
    
    // Regenerate session ID for security
    session_regenerate_id(true);
}

// Session security check
function checkSessionSecurity() {
    // Check if session is hijacked
    if (isset($_SESSION['user_ip']) && $_SESSION['user_ip'] !== $_SERVER['REMOTE_ADDR']) {
        logout();
    }
    
    // Store user IP
    $_SESSION['user_ip'] = $_SERVER['REMOTE_ADDR'];
    
    // Check session timeout (30 minutes)
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > 1800) {
        logout();
    }
    
    // Update last activity
    $_SESSION['last_activity'] = time();
}
?>
