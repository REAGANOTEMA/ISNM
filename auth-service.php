<?php
/**
 * Unified Authentication Service for ISNM School Management System
 * Handles both student and staff authentication with security measures
 */

require_once 'db.php';

/**
 * Authentication Service Class
 */
class AuthenticationService {
    
    private $maxLoginAttempts = 5;
    private $lockoutDuration = 900; // 15 minutes in seconds
    
    /**
     * Check if student account is locked due to failed login attempts
     * @param string $indexNumber
     * @return bool
     */
    private function isStudentAccountLocked($indexNumber) {
        $conn = getDatabaseConnection();
        
        $stmt = $conn->prepare("SELECT locked_until FROM users WHERE index_number = ? AND role = 'student' AND locked_until > NOW()");
        $stmt->bind_param("s", $indexNumber);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->num_rows > 0;
    }
    
    /**
     * Check if staff account is locked due to failed login attempts
     * @param string $email
     * @return bool
     */
    private function isStaffAccountLocked($email) {
        $conn = getDatabaseConnection();
        
        $stmt = $conn->prepare("SELECT locked_until FROM users WHERE email = ? AND role != 'student' AND locked_until > NOW()");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->num_rows > 0;
    }
    
    /**
     * Record failed student login attempt
     * @param string $indexNumber
     */
    private function recordStudentFailedAttempt($indexNumber) {
        $conn = getDatabaseConnection();
        
        // Increment login attempts
        $stmt = $conn->prepare("UPDATE users SET login_attempts = login_attempts + 1 WHERE index_number = ? AND role = 'student'");
        $stmt->bind_param("s", $indexNumber);
        $stmt->execute();
        
        // Check if we should lock the account
        $stmt = $conn->prepare("SELECT login_attempts FROM users WHERE index_number = ? AND role = 'student'");
        $stmt->bind_param("s", $indexNumber);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        
        if ($user && $user['login_attempts'] >= $this->maxLoginAttempts) {
            // Lock the account
            $lockUntil = date('Y-m-d H:i:s', time() + $this->lockoutDuration);
            $stmt = $conn->prepare("UPDATE users SET locked_until = ? WHERE index_number = ? AND role = 'student'");
            $stmt->bind_param("ss", $lockUntil, $indexNumber);
            $stmt->execute();
        }
    }
    
    /**
     * Record failed staff login attempt
     * @param string $email
     */
    private function recordStaffFailedAttempt($email) {
        $conn = getDatabaseConnection();
        
        // Increment login attempts
        $stmt = $conn->prepare("UPDATE users SET login_attempts = login_attempts + 1 WHERE email = ? AND role != 'student'");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        
        // Check if we should lock the account
        $stmt = $conn->prepare("SELECT login_attempts FROM users WHERE email = ? AND role != 'student'");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        
        if ($user && $user['login_attempts'] >= $this->maxLoginAttempts) {
            // Lock the account
            $lockUntil = date('Y-m-d H:i:s', time() + $this->lockoutDuration);
            $stmt = $conn->prepare("UPDATE users SET locked_until = ? WHERE email = ? AND role != 'student'");
            $stmt->bind_param("ss", $lockUntil, $email);
            $stmt->execute();
        }
    }
    
    /**
     * Reset failed login attempts on successful login
     * @param int $userId
     */
    private function resetFailedAttempts($userId) {
        $conn = getDatabaseConnection();
        
        $stmt = $conn->prepare("UPDATE users SET login_attempts = 0, locked_until = NULL, last_login = NOW() WHERE id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
    }
    
    /**
     * Authenticate student using 3-field verification
     * @param string $indexNumber
     * @param string $fullName
     * @param string $phoneNumber
     * @return array
     */
    public function authenticateStudent($indexNumber, $fullName, $phoneNumber) {
        // Validate inputs
        $indexNumber = sanitizeInput($indexNumber);
        $fullName = sanitizeInput($fullName);
        $phoneNumber = sanitizeInput($phoneNumber);
        
        if (empty($indexNumber) || empty($fullName) || empty($phoneNumber)) {
            return ['success' => false, 'message' => 'All fields are required for student login'];
        }
        
        if (!validateIndexNumber($indexNumber)) {
            return ['success' => false, 'message' => 'Invalid index number format'];
        }
        
        if (!validatePhone($phoneNumber)) {
            return ['success' => false, 'message' => 'Invalid phone number format'];
        }
        
        // Check if account is locked
        if ($this->isStudentAccountLocked($indexNumber)) {
            return ['success' => false, 'message' => 'Account temporarily locked due to multiple failed attempts. Please try again later.'];
        }
        
        $conn = getDatabaseConnection();
        
        // Split full name into first and last name
        $nameParts = explode(' ', trim($fullName));
        $firstName = $nameParts[0] ?? '';
        $lastName = isset($nameParts[1]) ? implode(' ', array_slice($nameParts, 1)) : '';
        
        // Query database - ALL THREE fields must match exactly
        $sql = "SELECT * FROM users WHERE 
                index_number = ? AND 
                full_name = ? AND 
                phone = ? AND 
                role = 'student' AND 
                status = 'active'";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sss", $indexNumber, $fullName, $phoneNumber);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            $this->recordStudentFailedAttempt($indexNumber);
            return ['success' => false, 'message' => 'Invalid student credentials. All fields must match exactly.'];
        }
        
        $student = $result->fetch_assoc();
        
        // Reset failed attempts on successful login
        $this->resetFailedAttempts($student['id']);
        
        return [
            'success' => true, 
            'user' => [
                'id' => $student['id'],
                'index_number' => $student['index_number'],
                'full_name' => $student['full_name'],
                'phone' => $student['phone'],
                'role' => $student['role'],
                'type' => 'student'
            ]
        ];
    }
    
    /**
     * Authenticate staff using email and password
     * @param string $email
     * @param string $password
     * @return array
     */
    public function authenticateStaff($email, $password) {
        // Validate inputs
        $email = sanitizeInput($email);
        $password = sanitizeInput($password);
        
        // Debug: Log inputs
        error_log("DEBUG: Staff login attempt - Email: $email, Password length: " . strlen($password));
        
        if (empty($email) || empty($password)) {
            error_log("DEBUG: Empty email or password");
            return ['success' => false, 'message' => 'Email and password are required'];
        }
        
        if (!validateEmail($email)) {
            error_log("DEBUG: Invalid email format");
            return ['success' => false, 'message' => 'Invalid email format'];
        }
        
        // Check if account is locked
        if ($this->isStaffAccountLocked($email)) {
            error_log("DEBUG: Account is locked");
            return ['success' => false, 'message' => 'Account temporarily locked due to multiple failed attempts. Please try again later.'];
        }
        
        $conn = getDatabaseConnection();
        
        // Query database for staff user
        $sql = "SELECT * FROM users WHERE 
                email = ? AND 
                role != 'student' AND 
                status = 'active'";
        
        error_log("DEBUG: Executing query: $sql with email: $email");
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        error_log("DEBUG: Found " . $result->num_rows . " users");
        
        if ($result->num_rows === 0) {
            error_log("DEBUG: No user found, recording failed attempt");
            $this->recordStaffFailedAttempt($email);
            return ['success' => false, 'message' => 'Invalid email or password'];
        }
        
        $staff = $result->fetch_assoc();
        error_log("DEBUG: User found - ID: " . $staff['id'] . ", Role: " . $staff['role'] . ", Status: " . $staff['status']);
        error_log("DEBUG: Password hash in DB: " . substr($staff['password'], 0, 20) . "...");
        
        // Verify password using password_verify
        if (!password_verify($password, $staff['password'])) {
            error_log("DEBUG: Password verification failed");
            $this->recordStaffFailedAttempt($email);
            return ['success' => false, 'message' => 'Invalid email or password'];
        }
        
        error_log("DEBUG: Authentication successful");
        
        // Reset failed attempts on successful login
        $this->resetFailedAttempts($staff['id']);
        
        return [
            'success' => true, 
            'user' => [
                'id' => $staff['id'],
                'email' => $staff['email'],
                'full_name' => $staff['full_name'],
                'phone' => $staff['phone'],
                'role' => $staff['role'],
                'type' => 'staff'
            ]
        ];
    }
    
    /**
     * Create secure session for authenticated user
     * @param array $user
     * @return bool
     */
    public function createSecureSession($user) {
        // Start session if not already started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Regenerate session ID for security
        session_regenerate_id(true);
        
        // Set standardized session variables
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['type'] = $user['type'];
        
        // Additional session data for convenience
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['email'] = $user['email'] ?? '';
        $_SESSION['phone'] = $user['phone'] ?? '';
        $_SESSION['index_number'] = $user['index_number'] ?? '';
        
        // Session security
        $_SESSION['user_ip'] = $_SERVER['REMOTE_ADDR'];
        $_SESSION['created_at'] = time();
        $_SESSION['last_activity'] = time();
        
        return true;
    }
    
    /**
     * Destroy session securely
     */
    public function destroySession() {
        // Start session if not started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Unset all session variables
        $_SESSION = array();
        
        // Destroy session cookie
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        
        // Destroy session
        session_destroy();
    }
    
    /**
     * Check if user is authenticated and session is valid
     * @return bool
     */
    public function isAuthenticated() {
        // Start session if not started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Check required session variables
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || !isset($_SESSION['type'])) {
            return false;
        }
        
        // Check session timeout (30 minutes)
        $timeout = 1800; // 30 minutes
        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout) {
            $this->destroySession();
            return false;
        }
        
        // Check IP address for session hijacking
        if (isset($_SESSION['user_ip']) && $_SESSION['user_ip'] !== $_SERVER['REMOTE_ADDR']) {
            $this->destroySession();
            return false;
        }
        
        // Update last activity
        $_SESSION['last_activity'] = time();
        
        return true;
    }
    
    /**
     * Get dashboard route based on user role
     * @param string $role
     * @return string
     */
    public function getDashboardRoute($role) {
        // Students always go to student dashboard
        if (strtolower($role) === 'student') {
            return 'dashboards/student.php';
        }
        
        // Staff routing based on role
        $roleClean = strtolower(str_replace([' ', '-'], '', $role));
        $dashboardFile = "dashboards/{$roleClean}.php";
        
        // Check if exact dashboard exists
        if (file_exists($dashboardFile)) {
            return $dashboardFile;
        }
        
        // Fallback logic
        $fallbacks = [
            'director' => 'dashboards/director-general.php',
            'principal' => 'dashboards/school-principal.php',
            'lecturer' => 'dashboards/lecturers.php',
            'secretary' => 'dashboards/school-secretary.php',
            'accountant' => 'dashboards/school-bursar.php'
        ];
        
        foreach ($fallbacks as $keyword => $fallback) {
            if (strpos($roleClean, $keyword) !== false && file_exists($fallback)) {
                return $fallback;
            }
        }
        
        // Final fallback to admin dashboard
        return file_exists('dashboards/admin-dashboard.php') ? 'dashboards/admin-dashboard.php' : 'dashboards/student.php';
    }
    
    /**
     * Check if user can create students
     * @param string $role
     * @return bool
     */
    public function canCreateStudents($role) {
        $roleLower = strtolower($role);
        
        // Direct allowed roles
        $allowedRoles = ['secretary', 'principal', 'accountant', 'school secretary', 'school principal', 'school bursar'];
        
        // Check if role is allowed or contains "director"
        if (in_array($roleLower, $allowedRoles) || strpos($roleLower, 'director') !== false) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Create student account
     * @param array $studentData
     * @return array
     */
    public function createStudentAccount($studentData) {
        $conn = getDatabaseConnection();
        
        // Validate required fields
        $requiredFields = ['index_number', 'full_name', 'phone'];
        foreach ($requiredFields as $field) {
            if (empty($studentData[$field])) {
                return ['success' => false, 'message' => "Field '$field' is required"];
            }
        }
        
        $indexNumber = sanitizeInput($studentData['index_number']);
        $fullName = sanitizeInput($studentData['full_name']);
        $phone = sanitizeInput($studentData['phone']);
        
        // Validate formats
        if (!validateIndexNumber($indexNumber)) {
            return ['success' => false, 'message' => 'Invalid index number format'];
        }
        
        if (!validatePhone($phone)) {
            return ['success' => false, 'message' => 'Invalid phone number format'];
        }
        
        // Check if index number already exists
        if (studentExistsByIndexNumber($indexNumber)) {
            return ['success' => false, 'message' => 'Index number already exists'];
        }
        
        try {
            // Insert student record
            $sql = "INSERT INTO users (index_number, full_name, phone, role, type, status) 
                    VALUES (?, ?, ?, 'student', 'student', 'active')";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sss", $indexNumber, $fullName, $phone);
            
            if ($stmt->execute()) {
                return ['success' => true, 'message' => 'Student account created successfully'];
            } else {
                return ['success' => false, 'message' => 'Error creating student account'];
            }
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Create staff account
     * @param array $staffData
     * @return array
     */
    public function createStaffAccount($staffData) {
        $conn = getDatabaseConnection();
        
        // Validate required fields
        $requiredFields = ['full_name', 'email', 'phone', 'password', 'role'];
        foreach ($requiredFields as $field) {
            if (empty($staffData[$field])) {
                return ['success' => false, 'message' => "Field '$field' is required"];
            }
        }
        
        $fullName = sanitizeInput($staffData['full_name']);
        $email = sanitizeInput($staffData['email']);
        $phone = sanitizeInput($staffData['phone']);
        $password = $staffData['password'];
        $role = sanitizeInput($staffData['role']);
        
        // Validate formats
        if (!validateEmail($email)) {
            return ['success' => false, 'message' => 'Invalid email format'];
        }
        
        if (!validatePhone($phone)) {
            return ['success' => false, 'message' => 'Invalid phone number format'];
        }
        
        if (strlen($password) < 8) {
            return ['success' => false, 'message' => 'Password must be at least 8 characters long'];
        }
        
        // Check if email already exists
        if (userExistsByEmail($email)) {
            return ['success' => false, 'message' => 'Email already exists'];
        }
        
        try {
            // Hash password
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert staff record
            $sql = "INSERT INTO users (full_name, email, phone, password, role, type, status) 
                    VALUES (?, ?, ?, ?, ?, 'staff', 'active')";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssss", $fullName, $email, $phone, $hashedPassword, $role);
            
            if ($stmt->execute()) {
                return ['success' => true, 'message' => 'Staff account created successfully'];
            } else {
                return ['success' => false, 'message' => 'Error creating staff account'];
            }
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }
}

// Create global authentication service instance
$authService = new AuthenticationService();

?>
