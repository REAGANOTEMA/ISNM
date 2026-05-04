<?php
/**
 * Authentication Controller for ISNM Student Management System
 */

require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../config/config.php';

class AuthController {
    private $user;
    
    public function __construct() {
        $this->user = new User();
    }
    
    /**
     * Handle user login
     */
    public function login() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('login.php');
        }
        
        $username = sanitizeInput($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        
        if (empty($username) || empty($password)) {
            flashMessage('error', 'Username and password are required');
            redirect('login.php');
        }
        
        // Authenticate user
        $result = $this->user->authenticate($username, $password);
        
        if ($result['success']) {
            // Set session variables
            $_SESSION['user_id'] = $result['user']['id'];
            $_SESSION['username'] = $result['user']['username'];
            $_SESSION['full_name'] = $result['user']['full_name'];
            $_SESSION['email'] = $result['user']['email'];
            $_SESSION['role'] = $result['user']['role'];
            $_SESSION['role_name'] = $result['user']['role_name'];
            $_SESSION['staff_id'] = $result['user']['staff_id'];
            $_SESSION['login_time'] = time();
            
            flashMessage('success', 'Welcome, ' . $result['user']['full_name']);
            redirect('dashboard.php');
        } else {
            flashMessage('error', $result['error']);
            redirect('login.php');
        }
    }
    
    /**
     * Handle user logout
     */
    public function logout() {
        // Destroy session
        session_destroy();
        
        // Clear session cookie
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 3600, '/');
        }
        
        flashMessage('success', 'You have been logged out successfully');
        redirect('login.php');
    }
    
    /**
     * Check if user is logged in
     */
    public function checkAuth() {
        if (!isLoggedIn()) {
            flashMessage('error', 'Please login to access this page');
            redirect('login.php');
        }
        
        // Check session timeout
        if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time']) > SESSION_LIFETIME) {
            $this->logout();
        }
        
        // Refresh login time
        $_SESSION['login_time'] = time();
    }
    
    /**
     * Get current user information
     */
    public function getCurrentUser() {
        if (!isLoggedIn()) {
            return null;
        }
        
        $result = $this->user->getById($_SESSION['user_id']);
        return $result['success'] ? $result['user'] : null;
    }
    
    /**
     * Handle user registration (for admin only)
     */
    public function register() {
        if (!hasPermission('create')) {
            flashMessage('error', 'You do not have permission to create users');
            redirect('dashboard.php');
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('users.php?action=create');
        }
        
        // Validate required fields
        $requiredFields = ['username', 'password', 'full_name', 'email', 'role'];
        
        foreach ($requiredFields as $field) {
            if (empty($_POST[$field])) {
                flashMessage('error', "Field '$field' is required");
                redirect('users.php?action=create');
            }
        }
        
        // Validate email
        if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
            flashMessage('error', 'Invalid email address');
            redirect('users.php?action=create');
        }
        
        // Validate password strength
        if (strlen($_POST['password']) < 6) {
            flashMessage('error', 'Password must be at least 6 characters long');
            redirect('users.php?action=create');
        }
        
        // Sanitize input data
        $data = [
            'username' => sanitizeInput($_POST['username']),
            'password' => $_POST['password'],
            'full_name' => sanitizeInput($_POST['full_name']),
            'email' => sanitizeInput($_POST['email']),
            'role' => sanitizeInput($_POST['role']),
            'staff_id' => sanitizeInput($_POST['staff_id'] ?? '')
        ];
        
        // Create user
        $result = $this->user->create($data);
        
        if ($result['success']) {
            flashMessage('success', 'User created successfully');
            redirect('users.php');
        } else {
            flashMessage('error', $result['error']);
            redirect('users.php?action=create');
        }
    }
    
    /**
     * Handle password change
     */
    public function changePassword() {
        if (!isLoggedIn()) {
            redirect('login.php');
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('profile.php');
        }
        
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        // Validate inputs
        if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
            flashMessage('error', 'All password fields are required');
            redirect('profile.php');
        }
        
        if ($newPassword !== $confirmPassword) {
            flashMessage('error', 'New passwords do not match');
            redirect('profile.php');
        }
        
        if (strlen($newPassword) < 6) {
            flashMessage('error', 'New password must be at least 6 characters long');
            redirect('profile.php');
        }
        
        // Verify current password
        $username = $_SESSION['username'];
        $result = $this->user->authenticate($username, $currentPassword);
        
        if (!$result['success']) {
            flashMessage('error', 'Current password is incorrect');
            redirect('profile.php');
        }
        
        // Update password
        $updateData = [
            'password' => $newPassword,
            'full_name' => $_SESSION['full_name'],
            'email' => $_SESSION['email'],
            'role' => $_SESSION['role']
        ];
        
        $updateResult = $this->user->update($_SESSION['user_id'], $updateData);
        
        if ($updateResult['success']) {
            flashMessage('success', 'Password changed successfully');
        } else {
            flashMessage('error', 'Failed to change password');
        }
        
        redirect('profile.php');
    }
}
?>
