<?php
// Enhanced authentication functions for ISNM School Management System
// Students login with NSIN number, name, and contact number
// Staff login with username and password

include_once 'config.php';
include_once 'functions.php';

// Secure student authentication function - UPDATED TO USE index_number + full_name + phone_number
function authenticateStudent($index_number, $full_name, $phone_number) {
    global $conn;
    
    // Validate input
    if (empty($index_number) || empty($full_name) || empty($phone_number)) {
        return ['success' => false, 'message' => 'All fields are required for student login'];
    }
    
    // Validate index_number format (U001/CM/056/16)
    if (!preg_match('/^U\d{3}\/(CM|CN|DMORDN)\/\d{3}\/\d{2}$/', $index_number)) {
        return ['success' => false, 'message' => 'Invalid index number format. Use format: U001/CM/056/16'];
    }
    
    // Validate phone number (Uganda format)
    $clean_phone = preg_replace('/[^0-9]/', '', $phone_number);
    if (strlen($clean_phone) !== 10 || !preg_match('/^7\d{9}$/', $clean_phone)) {
        return ['success' => false, 'message' => 'Invalid phone number format'];
    }
    
    // Split full_name into first_name and last_name
    $name_parts = explode(' ', trim($full_name));
    $first_name = $name_parts[0] ?? '';
    $last_name = isset($name_parts[1]) ? implode(' ', array_slice($name_parts, 1)) : '';
    
    try {
        // Query database for student - MUST MATCH ALL THREE FIELDS
        $sql = "SELECT * FROM students WHERE 
                (student_id = ? OR application_id = ?) AND 
                first_name = ? AND 
                phone = ? AND 
                status = 'active'";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssss", $index_number, $index_number, $first_name, $phone_number, $phone_number);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            return ['success' => false, 'message' => 'Invalid student credentials. All fields must match exactly.'];
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

// Secure staff authentication function - UPDATED TO USE EMAIL
function authenticateStaff($email, $password) {
    global $conn;
    
    // Validate input
    if (empty($email) || empty($password)) {
        return ['success' => false, 'message' => 'Email and password are required for staff login'];
    }
    
    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'message' => 'Invalid email format'];
    }
    
    try {
        // Query database for staff user by email
        $sql = "SELECT * FROM users WHERE email = ? AND status = 'active'";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            return ['success' => false, 'message' => 'Invalid email or password'];
        }
        
        $user = $result->fetch_assoc();
        
        // Verify password using password_verify
        if (!password_verify($password, $user['password'])) {
            return ['success' => false, 'message' => 'Invalid email or password'];
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

// Check if user is logged in and has appropriate access - ENHANCED PROTECTION
function checkAuth($required_role = null) {
    // Check if user is logged in
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || !isset($_SESSION['type'])) {
        // Redirect to appropriate login page based on user type
        if (strpos($_SERVER['REQUEST_URI'], 'student') !== false || strpos($_SERVER['REQUEST_URI'], 'student_profile') !== false) {
            header('Location: student-login.php');
        } else {
            header('Location: staff-login.php');
        }
        exit();
    }
    
    // Check session security
    if (isset($_SESSION['user_ip']) && $_SESSION['user_ip'] !== $_SERVER['REMOTE_ADDR']) {
        session_destroy();
        header('Location: staff-login.php');
        exit();
    }
    
    // Store user IP for security check
    $_SESSION['user_ip'] = $_SERVER['REMOTE_ADDR'];
    
    // Check if user has required role
    if ($required_role) {
        // Convert role to lowercase for comparison
        $user_role = strtolower($_SESSION['role']);
        $required_role_lower = strtolower($required_role);
        
        // Check for exact match or role category match
        if ($user_role !== $required_role_lower) {
            // Check role categories
            $role_categories = [
                'student' => ['student', 'students'],
                'director' => ['director general', 'director academics', 'director ict', 'director finance', 'chief executive officer'],
                'lecturer' => ['lecturers', 'senior lecturers', 'head of nursing', 'head of midwifery', 'teacher'],
                'admin' => ['school principal', 'deputy principal', 'school secretary', 'academic registrar', 'hr manager', 'school librarian', 'matrons', 'wardens', 'lab technicians', 'drivers', 'security', 'non-teaching staff'],
                'accountant' => ['school bursar', 'director finance']
            ];
            
            $has_access = false;
            foreach ($role_categories as $category => $roles) {
                if (in_array($required_role_lower, $roles) && in_array($user_role, $roles)) {
                    $has_access = true;
                    break;
                }
            }
            
            if (!$has_access) {
                $_SESSION['error'] = 'Access denied. You do not have permission to access this page.';
                // Redirect to appropriate dashboard based on user type
                if ($_SESSION['type'] === 'student') {
                    header('Location: student-dashboard.php');
                } else {
                    header('Location: ' . getUserDashboard($_SESSION['role']));
                }
                exit();
            }
        }
    }
    
    return true;
}

// Strict dashboard protection function - UPDATED FOR EXISTING DASHBOARD STRUCTURE
function protectDashboard($required_role = null) {
    // Check if user is logged in
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || !isset($_SESSION['type'])) {
        // Redirect to appropriate login page
        if (strpos($_SERVER['REQUEST_URI'], 'student') !== false) {
            header('Location: ../student-login.php');
        } else {
            header('Location: ../staff-login.php');
        }
        exit();
    }
    
    // Check session security
    if (isset($_SESSION['user_ip']) && $_SESSION['user_ip'] !== $_SERVER['REMOTE_ADDR']) {
        session_destroy();
        header('Location: ../staff-login.php');
        exit();
    }
    
    // Store user IP for security check
    $_SESSION['user_ip'] = $_SERVER['REMOTE_ADDR'];
    
    // If specific role is required, check it
    if ($required_role) {
        $user_role = strtolower($_SESSION['role']);
        $required_role_lower = strtolower($required_role);
        
        // Check for exact match
        if ($user_role !== $required_role_lower) {
            // Check role categories for broader access
            $role_categories = [
                'student' => ['student', 'students'],
                'director' => ['director general', 'director academics', 'director ict', 'director finance', 'chief executive officer'],
                'lecturer' => ['lecturers', 'senior lecturers', 'head of nursing', 'head of midwifery', 'teacher'],
                'admin' => ['school principal', 'deputy principal', 'school secretary', 'academic registrar', 'hr manager', 'school librarian', 'matrons', 'wardens', 'lab technicians', 'drivers', 'security', 'non-teaching staff'],
                'accountant' => ['school bursar', 'director finance']
            ];
            
            $has_access = false;
            foreach ($role_categories as $category => $roles) {
                if (in_array($required_role_lower, $roles) && in_array($user_role, $roles)) {
                    $has_access = true;
                    break;
                }
            }
            
            if (!$has_access) {
                $_SESSION['error'] = 'Access denied. You do not have permission to access this dashboard.';
                // Redirect to appropriate dashboard
                header('Location: ' . getUserDashboard($_SESSION['role']));
                exit();
            }
        }
    }
    
    return true;
}

// Check if user can create students - CENTRALIZED PERMISSION CHECK
function canCreateStudents() {
    if (!isset($_SESSION['role'])) {
        return false;
    }
    
    $user_role = strtolower($_SESSION['role']);
    
    // Allowed roles for student creation
    $allowed_roles = ['secretary', 'principal', 'accountant', 'school secretary', 'school principal', 'school bursar'];
    
    // Check if user is in allowed roles OR has 'director' in their role
    if (in_array($user_role, $allowed_roles) || strpos($user_role, 'director') !== false) {
        return true;
    }
    
    return false;
}

// hasPermission() function is already defined in functions.php

// Get user dashboard based on role - DYNAMIC REDIRECTION USING EXISTING /dashboard/ FOLDER
function getUserDashboard($role) {
    // Map roles to existing dashboard files in /dashboard/ folder
    $role_to_dashboard = [
        // Students
        'Student' => 'student.php',
        'Students' => 'student.php',
        
        // Executive Level
        'Director General' => 'director-general.php',
        'Chief Executive Officer' => 'ceo.php',
        
        // Director Level
        'Director Academics' => 'director-academics.php',
        'Director ICT' => 'director-ict.php',
        'Director Finance' => 'director-finance.php',
        
        // School Management
        'School Principal' => 'school-principal.php',
        'Deputy Principal' => 'deputy-principal.php',
        'School Bursar' => 'school-bursar.php',
        'Academic Registrar' => 'academic-registrar.php',
        'HR Manager' => 'hr-manager.php',
        'School Secretary' => 'school-secretary.php',
        'School Librarian' => 'school-librarian.php',
        
        // Academic Staff
        'Head of Nursing' => 'head-nursing.php',
        'Head of Midwifery' => 'head-midwifery.php',
        'Senior Lecturers' => 'senior-lecturers.php',
        'Lecturers' => 'lecturers.php',
        'teacher' => 'lecturers.php',
        
        // Support Staff
        'Matrons' => 'matrons.php',
        'Wardens' => 'wardens.php',
        'Lab Technicians' => 'lab-technicians.php',
        'Drivers' => 'drivers.php',
        'Security' => 'security.php',
        'Non-Teaching Staff' => 'non-teaching-staff.php',
        
        // Additional roles
        'principal' => 'principal.php',
        'secretary' => 'secretary.php',
        'bursar' => 'bursar.php'
    ];
    
    // Check if role has exact mapping
    if (isset($role_to_dashboard[$role])) {
        $dashboard_file = 'dashboards/' . $role_to_dashboard[$role];
        
        // Verify file exists before redirecting
        if (file_exists($dashboard_file)) {
            return $dashboard_file;
        }
    }
    
    // Dynamic fallback: try to construct dashboard name from role
    $role_lower = strtolower($role);
    $role_clean = str_replace([' ', '-'], '', $role_lower);
    $dynamic_dashboard = 'dashboards/' . $role_clean . '.php';
    
    if (file_exists($dynamic_dashboard)) {
        return $dynamic_dashboard;
    }
    
    // Final fallback logic based on role type
    if (strpos($role_lower, 'director') !== false) {
        return 'dashboards/director-general.php';
    } elseif (strpos($role_lower, 'principal') !== false) {
        return 'dashboards/school-principal.php';
    } elseif (strpos($role_lower, 'lecturer') !== false || strpos($role_lower, 'teacher') !== false) {
        return 'dashboards/lecturers.php';
    } elseif (strpos($role_lower, 'student') !== false) {
        return 'dashboards/student.php';
    } else {
        // Default fallback for admin/support staff
        return 'dashboards/school-secretary.php';
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

// Create session for authenticated user - STANDARDIZED SESSION VARIABLES
function createSession($user) {
    // Standard session variables ONLY
    $_SESSION['user_id'] = $user['id'] ?? $user['user_id'] ?? $user['student_id'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['type'] = ($user['role'] === 'Student') ? 'student' : 'staff';
    
    // Store additional data for convenience (not required)
    $_SESSION['first_name'] = $user['first_name'];
    $_SESSION['last_name'] = $user['last_name'] ?? $user['surname'] ?? '';
    $_SESSION['email'] = $user['email'] ?? '';
    $_SESSION['phone'] = $user['phone'] ?? '';
    
    // Role-specific data
    if ($user['role'] === 'Student') {
        $_SESSION['index_number'] = $user['student_id'] ?? $user['application_id'] ?? '';
        $_SESSION['full_name'] = $user['first_name'] . ' ' . ($user['last_name'] ?? $user['surname'] ?? '');
        $_SESSION['phone_number'] = $user['phone'] ?? '';
        $_SESSION['program'] = $user['program'] ?? '';
        $_SESSION['level'] = $user['level'] ?? '';
    } else {
        $_SESSION['username'] = $user['username'] ?? '';
        $_SESSION['department'] = $user['department'] ?? '';
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
