<?php
/**
 * Unified Authentication Handler for ISNM School Management System
 * Centralized authentication processing for both students and staff
 */

require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'auth-service.php';

// Start secure session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Global authentication service
$auth_service = new AuthenticationService();

/**
 * Process authentication requests
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'student_login':
            handleStudentLogin();
            break;
            
        case 'staff_login':
            handleStaffLogin();
            break;
            
        case 'create_student':
            handleCreateStudent();
            break;
            
        case 'create_staff':
            handleCreateStaff();
            break;
            
        case 'logout':
            handleLogout();
            break;
            
        default:
            $_SESSION['error'] = 'Invalid action';
            header('Location: index.php');
            exit();
    }
}

/**
 * Handle student login
 */
function handleStudentLogin() {
    global $auth_service;
    
    $index_number = sanitizeInput($_POST['index_number'] ?? '');
    $full_name = sanitizeInput($_POST['full_name'] ?? '');
    $phone_number = sanitizeInput($_POST['phone'] ?? '');
    
    $result = $auth_service->authenticateStudent($index_number, $full_name, $phone_number);
    
    if ($result['success']) {
        $auth_service->createSecureSession($result['user']);
        $_SESSION['success'] = "Login successful! Welcome, " . $result['user']['full_name'];
        header('Location: dashboards/student.php');
        exit();
    } else {
        $_SESSION['error'] = $result['message'];
        header('Location: student-login.php');
        exit();
    }
}

/**
 * Handle staff login
 */
function handleStaffLogin() {
    global $auth_service;
    
    $email = sanitizeInput($_POST['email'] ?? '');
    $password = sanitizeInput($_POST['password'] ?? '');
    $requestedPosition = $_POST['requested_position'] ?? '';
    
    $result = $auth_service->authenticateStaff($email, $password);
    
    if ($result['success']) {
        $auth_service->createSecureSession($result['user']);
        $_SESSION['success'] = "Login successful! Welcome, " . $result['user']['full_name'];
        
        // Determine dashboard route
        $dashboard = null;
        
        // Priority 1: If coming from organogram position, resolve to dashboard
        if (!empty($requestedPosition)) {
            // Resolve organogram position to role and get dashboard
            $resolvedRole = $auth_service->resolveOrganogramPosition($requestedPosition);
            $dashboard = $auth_service->getDashboardRoute($resolvedRole);
            // Clear position hint after use
            if (!empty($_SESSION['requested_position'])) {
                unset($_SESSION['requested_position']);
            }
        }
        
        // Priority 2: Use user's actual role-based dashboard
        if (empty($dashboard)) {
            $dashboard = $auth_service->getDashboardRoute($_SESSION['role']);
        }
        
        header("Location: $dashboard");
        exit();
    } else {
        $_SESSION['error'] = 'Invalid email or password';
        header('Location: staff-login.php');
        exit();
    }
}

/**
 * Handle student account creation
 */
function handleCreateStudent() {
    global $auth_service;
    
    // Check if user is authenticated and has permission
    if (!$auth_service->isAuthenticated()) {
        $_SESSION['error'] = 'Authentication required';
        header('Location: staff-login.php');
        exit();
    }
    
    if (!$auth_service->canCreateStudents($_SESSION['role'])) {
        $_SESSION['error'] = 'You do not have permission to create student accounts';
        header('Location: dashboards/' . basename($_SERVER['HTTP_REFERER']));
        exit();
    }
    
    $studentData = [
        'index_number' => $_POST['index_number'] ?? '',
        'full_name' => $_POST['full_name'] ?? '',
        'phone' => $_POST['phone'] ?? ''
    ];
    
    $result = $auth_service->createStudentAccount($studentData);
    
    if ($result['success']) {
        $_SESSION['success'] = $result['message'];
    } else {
        $_SESSION['error'] = $result['message'];
    }
    
    header('Location: ' . $_SERVER['HTTP_REFERER']);
    exit();
}

/**
 * Handle staff account creation
 */
function handleCreateStaff() {
    global $auth_service;
    
    // Check if user is authenticated and has permission
    if (!$auth_service->isAuthenticated()) {
        $_SESSION['error'] = 'Authentication required';
        header('Location: staff-login.php');
        exit();
    }
    
    // Only admin or director roles can create staff accounts
    $userRole = strtolower($_SESSION['role']);
    if (!($auth_service->canCreateStudents($userRole) || strpos($userRole, 'admin') !== false)) {
        $_SESSION['error'] = 'You do not have permission to create staff accounts';
        header('Location: dashboards/' . basename($_SERVER['HTTP_REFERER']));
        exit();
    }
    
    $staffData = [
        'full_name' => $_POST['full_name'] ?? '',
        'email' => $_POST['email'] ?? '',
        'phone' => $_POST['phone'] ?? '',
        'password' => $_POST['password'] ?? '',
        'role' => $_POST['role'] ?? ''
    ];
    
    $result = $auth_service->createStaffAccount($staffData);
    
    if ($result['success']) {
        $_SESSION['success'] = $result['message'];
    } else {
        $_SESSION['error'] = $result['message'];
    }
    
    header('Location: ' . $_SERVER['HTTP_REFERER']);
    exit();
}

/**
 * Handle logout
 */
function handleLogout() {
    global $auth_service;
    $auth_service->destroySession();
    $_SESSION['success'] = 'You have been logged out successfully';
    header('Location: index.php');
    exit();
}

/**
 * Note: Helper functions requireAuth(), requireRole(), and getCurrentUser() are defined in
 * security-middleware.php and should be used from there for consistency across the application.
 */

?>
