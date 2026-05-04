<?php
/**
 * User Controller for ISNM Student Management System
 */

require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../config/config.php';

class UserController {
    private $user;
    
    public function __construct() {
        $this->user = new User();
    }
    
    /**
     * Handle user creation
     */
    public function create() {
        if (!hasPermission('create')) {
            flashMessage('error', 'You do not have permission to create users');
            redirect('users.php');
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
     * Handle user update
     */
    public function update() {
        if (!hasPermission('update')) {
            flashMessage('error', 'You do not have permission to update users');
            redirect('users.php');
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['id'])) {
            redirect('users.php');
        }
        
        $id = (int)$_POST['id'];
        
        // Validate required fields
        $requiredFields = ['full_name', 'email', 'role'];
        
        foreach ($requiredFields as $field) {
            if (empty($_POST[$field])) {
                flashMessage('error', "Field '$field' is required");
                redirect("users.php?action=edit&id=$id");
            }
        }
        
        // Validate email
        if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
            flashMessage('error', 'Invalid email address');
            redirect("users.php?action=edit&id=$id");
        }
        
        // Sanitize input data
        $data = [
            'full_name' => sanitizeInput($_POST['full_name']),
            'email' => sanitizeInput($_POST['email']),
            'role' => sanitizeInput($_POST['role']),
            'staff_id' => sanitizeInput($_POST['staff_id'] ?? '')
        ];
        
        // Add password if provided
        if (!empty($_POST['password'])) {
            if (strlen($_POST['password']) < 6) {
                flashMessage('error', 'Password must be at least 6 characters long');
                redirect("users.php?action=edit&id=$id");
            }
            $data['password'] = $_POST['password'];
        }
        
        // Update user
        $result = $this->user->update($id, $data);
        
        if ($result['success']) {
            flashMessage('success', 'User updated successfully');
            redirect('users.php');
        } else {
            flashMessage('error', $result['error']);
            redirect("users.php?action=edit&id=$id");
        }
    }
    
    /**
     * Handle user deletion
     */
    public function delete() {
        if (!hasPermission('delete')) {
            flashMessage('error', 'You do not have permission to delete users');
            redirect('users.php');
        }
        
        if (empty($_GET['id'])) {
            redirect('users.php');
        }
        
        $id = (int)$_GET['id'];
        
        // Prevent self-deletion
        if ($id == $_SESSION['user_id']) {
            flashMessage('error', 'You cannot delete your own account');
            redirect('users.php');
        }
        
        // Delete user
        $result = $this->user->deactivate($id);
        
        if ($result['success']) {
            flashMessage('success', 'User deactivated successfully');
        } else {
            flashMessage('error', $result['error']);
        }
        
        redirect('users.php');
    }
    
    /**
     * Display user list
     */
    public function index() {
        if (!hasPermission('read')) {
            flashMessage('error', 'You do not have permission to view users');
            redirect('dashboard.php');
        }
        
        $role = sanitizeInput($_GET['role'] ?? '');
        
        // Get users
        $result = $this->user->getAll($role);
        
        if (!$result['success']) {
            flashMessage('error', $result['error']);
            $users = [];
        } else {
            $users = $result['users'];
        }
        
        // Get roles for filter
        $rolesResult = $this->user->getRoles();
        $roles = $rolesResult['success'] ? $rolesResult['roles'] : [];
        
        return [
            'users' => $users,
            'roles' => $roles,
            'filter_role' => $role
        ];
    }
    
    /**
     * Display user edit form
     */
    public function edit($id) {
        if (!hasPermission('update')) {
            flashMessage('error', 'You do not have permission to update users');
            redirect('users.php');
        }
        
        $result = $this->user->getById($id);
        
        if (!$result['success'] || empty($result['user'])) {
            flashMessage('error', 'User not found');
            redirect('users.php');
        }
        
        return $result['user'];
    }
}
?>
