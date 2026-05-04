<?php
/**
 * User Model for ISNM Student Management System
 */

require_once __DIR__ . '/../config/database.php';

class User {
    private $conn;
    
    public function __construct() {
        $this->conn = getConnection();
    }
    
    public function __destruct() {
        closeConnection($this->conn);
    }
    
    /**
     * Authenticate user login
     */
    public function authenticate($username, $password) {
        try {
            $query = "SELECT u.*, r.name as role_name FROM users u 
                      LEFT JOIN roles r ON u.role = r.name 
                      WHERE u.username = ? AND u.status = 'active'";
            
            $stmt = executePrepared($this->conn, $query, 's', [$username]);
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
            $stmt->close();
            
            if ($user && password_verify($password, $user['password'])) {
                // Remove password from returned data
                unset($user['password']);
                return ['success' => true, 'user' => $user];
            }
            
            return ['success' => false, 'error' => 'Invalid username or password'];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Authentication failed: ' . $e->getMessage()];
        }
    }
    
    /**
     * Create new user
     */
    public function create($data) {
        try {
            $query = "INSERT INTO users (username, password, full_name, email, role, staff_id, status) 
                      VALUES (?, ?, ?, ?, ?, ?, 'active')";
            
            $hashedPassword = password_hash($data['password'], HASH_ALGO);
            
            $params = [
                $data['username'],
                $hashedPassword,
                $data['full_name'],
                $data['email'],
                $data['role'],
                $data['staff_id'] ?? null
            ];
            
            $stmt = executePrepared($this->conn, $query, 'ssssss', $params);
            $userId = $stmt->insert_id;
            $stmt->close();
            
            return ['success' => true, 'user_id' => $userId];
            
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                return ['success' => false, 'error' => 'Username already exists'];
            }
            return ['success' => false, 'error' => 'Failed to create user: ' . $e->getMessage()];
        }
    }
    
    /**
     * Get user by ID
     */
    public function getById($id) {
        try {
            $query = "SELECT u.*, r.name as role_name FROM users u 
                      LEFT JOIN roles r ON u.role = r.name 
                      WHERE u.id = ? AND u.status = 'active'";
            
            $stmt = executePrepared($this->conn, $query, 'i', [$id]);
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
            $stmt->close();
            
            if ($user) {
                unset($user['password']);
            }
            
            return ['success' => true, 'user' => $user];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Update user
     */
    public function update($id, $data) {
        try {
            $query = "UPDATE users SET full_name = ?, email = ?, role = ?, staff_id = ?";
            $params = [
                $data['full_name'],
                $data['email'],
                $data['role'],
                $data['staff_id'] ?? null
            ];
            $types = 'ssss';
            
            // Update password if provided
            if (!empty($data['password'])) {
                $query .= ", password = ?";
                $params[] = password_hash($data['password'], HASH_ALGO);
                $types .= 's';
            }
            
            $query .= " WHERE id = ? AND status = 'active'";
            $params[] = $id;
            $types .= 'i';
            
            $stmt = executePrepared($this->conn, $query, $types, $params);
            $affectedRows = $stmt->affected_rows;
            $stmt->close();
            
            return ['success' => $affectedRows > 0, 'affected_rows' => $affectedRows];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Failed to update user: ' . $e->getMessage()];
        }
    }
    
    /**
     * Get all users
     */
    public function getAll($role = '') {
        try {
            $query = "SELECT u.id, u.username, u.full_name, u.email, u.role, u.created_at, 
                      r.name as role_name FROM users u 
                      LEFT JOIN roles r ON u.role = r.name 
                      WHERE u.status = 'active'";
            
            $params = [];
            $types = '';
            
            if (!empty($role)) {
                $query .= " AND u.role = ?";
                $params[] = $role;
                $types .= 's';
            }
            
            $query .= " ORDER BY u.created_at DESC";
            
            $stmt = executePrepared($this->conn, $query, $types, $params);
            $result = $stmt->get_result();
            $users = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            
            return ['success' => true, 'users' => $users];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Failed to fetch users: ' . $e->getMessage()];
        }
    }
    
    /**
     * Deactivate user (soft delete)
     */
    public function deactivate($id) {
        try {
            $query = "UPDATE users SET status = 'inactive' WHERE id = ?";
            $stmt = executePrepared($this->conn, $query, 'i', [$id]);
            $affectedRows = $stmt->affected_rows;
            $stmt->close();
            
            return ['success' => $affectedRows > 0, 'affected_rows' => $affectedRows];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Failed to deactivate user: ' . $e->getMessage()];
        }
    }
    
    /**
     * Get all roles
     */
    public function getRoles() {
        try {
            $query = "SELECT * FROM roles ORDER BY name";
            $stmt = executePrepared($this->conn, $query, '', []);
            $result = $stmt->get_result();
            $roles = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            
            return ['success' => true, 'roles' => $roles];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
?>
