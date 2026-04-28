<?php
/**
 * Security Middleware for ISNM School Management System
 * Provides centralized security checks for all dashboards and protected pages
 */

// Include required files
require_once 'auth-handler.php';

/**
 * Security Middleware Class
 */
class SecurityMiddleware {
    
    private $auth_service;
    
    public function __construct() {
        global $auth_service;
        $this->auth_service = $auth_service;
    }
    
    /**
     * Check if user is authenticated and session is valid
     */
    public function requireAuthentication() {
        // Start session if not started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Check if user is logged in
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || !isset($_SESSION['type'])) {
            // Redirect to appropriate login page
            $this->redirectToLogin();
            exit();
        }
        
        // Check session validity
        if (!$this->auth_service->checkSessionValidity()) {
            $_SESSION['error'] = 'Session expired. Please login again.';
            $this->redirectToLogin();
            exit();
        }
        
        return true;
    }
    
    /**
     * Check if user has required role for dashboard access
     */
    public function requireRole($required_role = null) {
        // First ensure user is authenticated
        $this->requireAuthentication();
        
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
                    $this->redirectToUserDashboard();
                    exit();
                }
            }
        }
        
        return true;
    }
    
    /**
     * Check if user can create students
     */
    public function requireStudentCreationPermission() {
        $this->requireAuthentication();
        
        if (!$this->auth_service->canCreateStudents($_SESSION['role'])) {
            $_SESSION['error'] = 'Access denied. You do not have permission to create students.';
            $this->redirectToUserDashboard();
            exit();
        }
        
        return true;
    }
    
    /**
     * Check messaging permissions
     */
    public function requireMessagingPermission($recipient_role) {
        $this->requireAuthentication();
        
        if (!$this->auth_service->canSendMessageTo($recipient_role, $_SESSION['role'])) {
            $_SESSION['error'] = 'Access denied. You do not have permission to send messages to this recipient.';
            $this->redirectToUserDashboard();
            exit();
        }
        
        return true;
    }
    
    /**
     * Check if user can edit student profile (image only for students)
     */
    public function requireProfileEditPermission($target_user_id = null) {
        $this->requireAuthentication();
        
        // Students can only edit their own profile image
        if ($_SESSION['type'] === 'student') {
            if ($target_user_id && $target_user_id !== $_SESSION['user_id']) {
                $_SESSION['error'] = 'Access denied. You can only edit your own profile.';
                $this->redirectToUserDashboard();
                exit();
            }
            // Students can only upload images, not edit other data
            return ['can_edit_image' => true, 'can_edit_data' => false];
        }
        
        // Staff can edit student profiles (with proper permissions)
        return ['can_edit_image' => true, 'can_edit_data' => true];
    }
    
    /**
     * Redirect to appropriate login page
     */
    private function redirectToLogin() {
        if (isset($_SESSION['type']) && $_SESSION['type'] === 'student') {
            header('Location: student-login.php');
        } else {
            header('Location: staff-login.php');
        }
        exit();
    }
    
    /**
     * Redirect to user's appropriate dashboard
     */
    private function redirectToUserDashboard() {
        $dashboard = $this->auth_service->getDashboardRoute($_SESSION['role']);
        header("Location: $dashboard");
        exit();
    }
    
    /**
     * Get current user information safely
     */
    public function getCurrentUser() {
        $this->requireAuthentication();
        
        return [
            'user_id' => $_SESSION['user_id'],
            'role' => $_SESSION['role'],
            'type' => $_SESSION['type'],
            'first_name' => $_SESSION['first_name'] ?? '',
            'last_name' => $_SESSION['last_name'] ?? '',
            'email' => $_SESSION['email'] ?? '',
            'phone' => $_SESSION['phone'] ?? ''
        ];
    }
    
    /**
     * Log user activity
     */
    public function logActivity($action, $description, $table = null, $record_id = null) {
        if (function_exists('logActivity')) {
            logActivity($_SESSION['user_id'], $_SESSION['role'], $action, $description, $table, $record_id);
        }
    }
}

// Create global security middleware instance
$security = new SecurityMiddleware();

// Helper functions for easy access
function requireAuth() {
    global $security;
    return $security->requireAuthentication();
}

function requireRole($role = null) {
    global $security;
    return $security->requireRole($role);
}

function requireStudentCreationPermission() {
    global $security;
    return $security->requireStudentCreationPermission();
}

function requireMessagingPermission($recipient_role) {
    global $security;
    return $security->requireMessagingPermission($recipient_role);
}

function requireProfileEditPermission($target_user_id = null) {
    global $security;
    return $security->requireProfileEditPermission($target_user_id);
}

function getCurrentUser() {
    global $security;
    return $security->getCurrentUser();
}

?>
