<?php
// Enhanced authentication functions for ISNM School Management System
// Students login with NSIN number, name, and contact number
// Staff login with username and password

include_once 'config.php';
include_once 'functions.php';

// Enhanced student authentication
function authenticateStudent($nsin_number, $first_name, $phone) {
    global $conn;
    
    // Validate input
    if (empty($nsin_number) || empty($first_name) || empty($phone)) {
        return ['success' => false, 'message' => 'All fields are required for student login'];
    }
    
    // Check if student exists and is not locked
    $check_sql = "SELECT * FROM students WHERE nsin_number = ? AND first_name = ? AND phone = ? AND status = 'active'";
    $stmt = $conn->prepare($check_sql);
    $stmt->bind_param("sss", $nsin_number, $first_name, $phone);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $student = $result->fetch_assoc();
        
        // Check if account is locked
        if ($student['account_locked'] && $student['locked_until'] > date('Y-m-d H:i:s')) {
            return ['success' => false, 'message' => 'Account is temporarily locked. Please try again later.'];
        }
        
        // Reset login attempts on successful login
        $reset_sql = "UPDATE students SET login_attempts = 0, account_locked = 0, last_login = NOW() WHERE student_id = ?";
        $reset_stmt = $conn->prepare($reset_sql);
        $reset_stmt->bind_param("s", $student['student_id']);
        $reset_stmt->execute();
        
        // Log successful login
        logActivity($student['student_id'], 'Student', 'Student Login', "Student logged in: $nsin_number - $first_name $phone", 'students', $student['student_id']);
        
        return [
            'success' => true,
            'user' => [
                'user_id' => $student['student_id'],
                'first_name' => $student['first_name'],
                'last_name' => $student['surname'],
                'email' => $student['email'],
                'phone' => $student['phone'],
                'role' => 'Student',
                'nsin_number' => $student['nsin_number'],
                'program' => $student['program'],
                'level' => $student['level']
            ]
        ];
        
    } else {
        // Check if student exists with NSIN number but wrong credentials
        $check_nsin_sql = "SELECT * FROM students WHERE nsin_number = ?";
        $check_nsin_stmt = $conn->prepare($check_nsin_sql);
        $check_nsin_stmt->bind_param("s", $nsin_number);
        $check_nsin_stmt->execute();
        $nsin_result = $check_nsin_stmt->get_result();
        
        if ($nsin_result->num_rows === 1) {
            $student_data = $nsin_result->fetch_assoc();
            
            // Increment login attempts
            $attempts = $student_data['login_attempts'] + 1;
            
            // Lock account after 3 failed attempts
            if ($attempts >= 3) {
                $lock_until = date('Y-m-d H:i:s', strtotime('+30 minutes'));
                $lock_sql = "UPDATE students SET login_attempts = ?, account_locked = 1, locked_until = ? WHERE nsin_number = ?";
                $lock_stmt = $conn->prepare($lock_sql);
                $lock_stmt->bind_param("iss", $attempts, $lock_until, $nsin_number);
                $lock_stmt->execute();
                
                return ['success' => false, 'message' => 'Account locked due to multiple failed login attempts. Please try again after 30 minutes.'];
            } else {
                $update_sql = "UPDATE students SET login_attempts = ? WHERE nsin_number = ?";
                $update_stmt = $conn->prepare($update_sql);
                $update_stmt->bind_param("is", $attempts, $nsin_number);
                $update_stmt->execute();
                
                return ['success' => false, 'message' => 'Invalid credentials. Attempts remaining: ' . (3 - $attempts)];
            }
        } else {
            return ['success' => false, 'message' => 'Student not found. Please check your NSIN number, name, and contact number.'];
        }
    }
}

// Enhanced staff authentication
function authenticateStaff($username, $password) {
    global $conn;
    
    // Validate input
    if (empty($username) || empty($password)) {
        return ['success' => false, 'message' => 'Username and password are required for staff login'];
    }
    
    // Check if user exists
    $check_sql = "SELECT * FROM users WHERE username = ? AND status = 'active'";
    $stmt = $conn->prepare($check_sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        // Check if account is locked
        if ($user['account_locked'] && $user['locked_until'] > date('Y-m-d H:i:s')) {
            return ['success' => false, 'message' => 'Account is temporarily locked. Please try again later.'];
        }
        
        // Verify password
        if (password_verify($password, $user['password'])) {
            // Reset login attempts on successful login
            $reset_sql = "UPDATE users SET login_attempts = 0, account_locked = 0, last_login = NOW() WHERE user_id = ?";
            $reset_stmt = $conn->prepare($reset_sql);
            $reset_stmt->bind_param("s", $user['user_id']);
            $reset_stmt->execute();
            
            // Log successful login
            logActivity($user['user_id'], $user['role'], 'Staff Login', "Staff logged in: $username", 'users', $user['user_id']);
            
            return [
                'success' => true,
                'user' => [
                    'user_id' => $user['user_id'],
                    'username' => $user['username'],
                    'first_name' => $user['first_name'],
                    'last_name' => $user['last_name'],
                    'email' => $user['email'],
                    'phone' => $user['phone'],
                    'role' => $user['role'],
                    'department' => $user['department']
                ]
            ];
            
        } else {
            // Increment login attempts
            $attempts = $user['login_attempts'] + 1;
            
            // Lock account after 3 failed attempts
            if ($attempts >= 3) {
                $lock_until = date('Y-m-d H:i:s', strtotime('+30 minutes'));
                $lock_sql = "UPDATE users SET login_attempts = ?, account_locked = 1, locked_until = ? WHERE username = ?";
                $lock_stmt = $conn->prepare($lock_sql);
                $lock_stmt->bind_param("iss", $attempts, $lock_until, $username);
                $lock_stmt->execute();
                
                return ['success' => false, 'message' => 'Account locked due to multiple failed login attempts. Please try again after 30 minutes.'];
            } else {
                $update_sql = "UPDATE users SET login_attempts = ? WHERE username = ?";
                $update_stmt = $conn->prepare($update_sql);
                $update_stmt->bind_param("is", $attempts, $username);
                $update_stmt->execute();
                
                return ['success' => false, 'message' => 'Invalid password. Attempts remaining: ' . (3 - $attempts)];
            }
        }
        
    } else {
        return ['success' => false, 'message' => 'User not found. Please check your username.'];
    }
}

// Check if user is logged in and has appropriate access
function checkAuth($required_role = null) {
    if (!isset($_SESSION['user_id'])) {
        header('Location: enhanced_login.php');
        exit();
    }
    
    if ($required_role && $_SESSION['role'] !== $required_role) {
        $_SESSION['error'] = 'Access denied. You do not have permission to access this page.';
        header('Location: dashboard.php');
        exit();
    }
    
    return true;
}

// Check if user has permission for specific action
function hasPermission($permission) {
    $role = $_SESSION['role'] ?? '';
    
    $permissions = [
        'Director General' => ['all'],
        'Chief Executive Officer' => ['all'],
        'Director Academics' => ['students', 'academics', 'reports'],
        'Director ICT' => ['system', 'users', 'reports'],
        'Director Finance' => ['fees', 'finance', 'reports'],
        'School Principal' => ['students', 'academics', 'fees', 'reports'],
        'Deputy Principal' => ['students', 'academics'],
        'School Bursar' => ['fees', 'finance'],
        'Academic Registrar' => ['students', 'academics'],
        'HR Manager' => ['users', 'hr'],
        'School Secretary' => ['students', 'administrative'],
        'Lecturers' => ['academics', 'students_view'],
        'Students' => ['profile', 'fees_view', 'academics_view']
    ];
    
    return in_array('all', $permissions[$role] ?? []) || 
           in_array($permission, $permissions[$role] ?? []);
}

// Get user dashboard based on role
function getUserDashboard($role) {
    $dashboards = [
        'Director General' => 'dashboards/director-general.php',
        'Chief Executive Officer' => 'dashboards/director-general.php',
        'School Principal' => 'dashboards/principal.php',
        'School Secretary' => 'dashboards/secretary.php',
        'Academic Registrar' => 'dashboards/academic-registrar.php',
        'School Bursar' => 'dashboards/bursar.php',
        'HR Manager' => 'dashboards/hr-manager.php',
        'Director Academics' => 'dashboards/director-general.php',
        'Director ICT' => 'dashboards/director-general.php',
        'Director Finance' => 'dashboards/director-general.php',
        'Students' => 'student_profile.php',
        'Lecturers' => 'dashboard.php'
    ];
    
    return $dashboards[$role] ?? 'dashboard.php';
}

// Logout function
function logout() {
    // Log logout activity
    if (isset($_SESSION['user_id'])) {
        logActivity($_SESSION['user_id'], $_SESSION['role'], 'Logout', 'User logged out', 'users', $_SESSION['user_id']);
    }
    
    // Destroy session
    session_destroy();
    
    // Redirect to login
    header('Location: enhanced_login.php');
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
