<?php
/**
 * Modern Authentication Handler for ISNM School Management System
 * Centralized authentication logic with enhanced security
 */

// Include required files
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Start secure session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Modern Authentication Service Class
 */
class ModernAuthService {
    
    private $conn;
    private $max_login_attempts = 5;
    private $lockout_duration = 900; // 15 minutes
    
    public function __construct() {
        global $conn;
        $this->conn = $conn;
    }
    
    /**
     * Standardized session creation
     */
    private function createSecureSession($user, $user_type) {
        // Regenerate session ID for security
        session_regenerate_id(true);
        
        // Standard session variables ONLY
        $_SESSION['user_id'] = $user['id'] ?? $user['user_id'] ?? $user['student_id'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['type'] = $user_type;
        
        // Additional data for convenience
        $_SESSION['first_name'] = $user['first_name'] ?? '';
        $_SESSION['last_name'] = $user['surname'] ?? $user['last_name'] ?? '';
        $_SESSION['email'] = $user['email'] ?? '';
        $_SESSION['phone'] = $user['phone'] ?? '';
        
        // Session security
        $_SESSION['user_ip'] = $_SERVER['REMOTE_ADDR'];
        $_SESSION['last_activity'] = time();
        $_SESSION['created_at'] = time();
        
        // Role-specific data
        if ($user_type === 'student') {
            $_SESSION['index_number'] = $user['student_id'] ?? $user['application_id'] ?? '';
            $_SESSION['full_name'] = $user['first_name'] . ' ' . ($user['surname'] ?? $user['last_name'] ?? '');
            $_SESSION['phone_number'] = $user['phone'] ?? '';
            $_SESSION['program'] = $user['program'] ?? '';
            $_SESSION['level'] = $user['level'] ?? '';
        } else {
            $_SESSION['username'] = $user['username'] ?? '';
            $_SESSION['department'] = $user['department'] ?? '';
        }
        
        // Clear login attempts
        $this->clearLoginAttempts($user_type, $user['email'] ?? $user['student_id'] ?? '');
    }
    
    /**
     * Check if account is locked due to failed attempts
     */
    private function isAccountLocked($identifier, $user_type) {
        $lockout_key = $user_type . '_' . md5($identifier);
        
        if (isset($_SESSION[$lockout_key . '_attempts']) && 
            $_SESSION[$lockout_key . '_attempts'] >= $this->max_login_attempts) {
            
            $lockout_time = $_SESSION[$lockout_key . '_lockout_time'] ?? 0;
            if (time() - $lockout_time < $this->lockout_duration) {
                return true;
            } else {
                // Lockout expired, clear attempts
                unset($_SESSION[$lockout_key . '_attempts']);
                unset($_SESSION[$lockout_key . '_lockout_time']);
            }
        }
        
        return false;
    }
    
    /**
     * Record failed login attempt
     */
    private function recordFailedAttempt($identifier, $user_type) {
        $lockout_key = $user_type . '_' . md5($identifier);
        
        $_SESSION[$lockout_key . '_attempts'] = ($_SESSION[$lockout_key . '_attempts'] ?? 0) + 1;
        
        if ($_SESSION[$lockout_key . '_attempts'] >= $this->max_login_attempts) {
            $_SESSION[$lockout_key . '_lockout_time'] = time();
        }
    }
    
    /**
     * Clear login attempts on successful login
     */
    private function clearLoginAttempts($user_type, $identifier) {
        $lockout_key = $user_type . '_' . md5($identifier);
        unset($_SESSION[$lockout_key . '_attempts']);
        unset($_SESSION[$lockout_key . '_lockout_time']);
    }
    
    /**
     * Modern Staff Authentication
     */
    public function authenticateStaff($email, $password) {
        // Validate input
        if (empty($email) || empty($password)) {
            return ['success' => false, 'message' => 'Email and password are required'];
        }
        
        // Validate email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Invalid email format'];
        }
        
        // Check if account is locked
        if ($this->isAccountLocked($email, 'staff')) {
            return ['success' => false, 'message' => 'Account temporarily locked due to multiple failed attempts. Please try again later.'];
        }
        
        try {
            // Query database for staff user
            $sql = "SELECT * FROM users WHERE email = ? AND status = 'active'";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                $this->recordFailedAttempt($email, 'staff');
                return ['success' => false, 'message' => 'Invalid email or password'];
            }
            
            $user = $result->fetch_assoc();
            
            // Verify password using password_verify
            if (!password_verify($password, $user['password'])) {
                $this->recordFailedAttempt($email, 'staff');
                return ['success' => false, 'message' => 'Invalid email or password'];
            }
            
            // Update last login
            $update_sql = "UPDATE users SET last_login = NOW() WHERE id = ?";
            $update_stmt = $this->conn->prepare($update_sql);
            $update_stmt->bind_param("i", $user['id']);
            $update_stmt->execute();
            
            // Create secure session
            $this->createSecureSession($user, 'staff');
            
            return ['success' => true, 'user' => $user];
            
        } catch (Exception $e) {
            error_log("Staff authentication error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Authentication system error. Please try again.'];
        }
    }
    
    /**
     * Modern Student Authentication (3-field verification)
     */
    public function authenticateStudent($index_number, $full_name, $phone_number) {
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
        
        // Check if account is locked
        if ($this->isAccountLocked($index_number, 'student')) {
            return ['success' => false, 'message' => 'Account temporarily locked due to multiple failed attempts. Please try again later.'];
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
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("sssss", $index_number, $index_number, $first_name, $phone_number, $phone_number);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                $this->recordFailedAttempt($index_number, 'student');
                return ['success' => false, 'message' => 'Invalid student credentials. All fields must match exactly.'];
            }
            
            $student = $result->fetch_assoc();
            
            // Update last login
            $update_sql = "UPDATE students SET last_login = NOW() WHERE id = ?";
            $update_stmt = $this->conn->prepare($update_sql);
            $update_stmt->bind_param("i", $student['id']);
            $update_stmt->execute();
            
            // Create secure session
            $this->createSecureSession($student, 'student');
            
            return ['success' => true, 'user' => $student];
            
        } catch (Exception $e) {
            error_log("Student authentication error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Authentication system error. Please try again.'];
        }
    }
    
    /**
     * Check session timeout and validity
     */
    public function checkSessionValidity() {
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || !isset($_SESSION['type'])) {
            return false;
        }
        
        // Check session timeout (30 minutes of inactivity)
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
     * Secure session destruction
     */
    public function destroySession() {
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
     * Get smart dashboard routing
     */
    public function getDashboardRoute($role) {
        // Students always go to student dashboard
        if (strtolower($role) === 'student' || strtolower($role) === 'students') {
            return 'dashboards/student.php';
        }
        
        // Smart routing for staff roles
        $role_lower = strtolower($role);
        $role_clean = str_replace([' ', '-'], '', $role_lower);
        $dashboard_file = 'dashboards/' . $role_clean . '.php';
        
        // Check if exact dashboard exists
        if (file_exists($dashboard_file)) {
            return $dashboard_file;
        }
        
        // Fallback logic based on role type
        if (strpos($role_lower, 'director') !== false) {
            return 'dashboards/director-general.php';
        } elseif (strpos($role_lower, 'principal') !== false) {
            return 'dashboards/school-principal.php';
        } elseif (strpos($role_lower, 'lecturer') !== false || strpos($role_lower, 'teacher') !== false) {
            return 'dashboards/lecturers.php';
        } elseif (strpos($role_lower, 'bursar') !== false || strpos($role_lower, 'accountant') !== false) {
            return 'dashboards/school-bursar.php';
        } elseif (strpos($role_lower, 'secretary') !== false) {
            return 'dashboards/school-secretary.php';
        } else {
            // Default fallback for admin/support staff
            return 'dashboards/school-secretary.php';
        }
    }
    
    /**
     * Check if user can create students
     */
    public function canCreateStudents($role) {
        $role_lower = strtolower($role);
        
        // Allowed roles for student creation
        $allowed_roles = ['secretary', 'principal', 'accountant', 'school secretary', 'school principal', 'school bursar'];
        
        // Check if user is in allowed roles OR has 'director' in their role
        if (in_array($role_lower, $allowed_roles) || strpos($role_lower, 'director') !== false) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Check messaging permissions
     */
    public function canSendMessageTo($recipient_role, $sender_role) {
        $sender_role_lower = strtolower($sender_role);
        $recipient_role_lower = strtolower($recipient_role);
        
        // Students can message other students
        if ($sender_role_lower === 'student' && $recipient_role_lower === 'student') {
            return true;
        }
        
        // Students can message their assigned offices
        if ($sender_role_lower === 'student') {
            $allowed_offices = ['director', 'principal', 'accountant', 'wardens', 'matron', 'boys-warden', 'girls-matron'];
            foreach ($allowed_offices as $office) {
                if (strpos($recipient_role_lower, $office) !== false) {
                    return true;
                }
            }
            return false;
        }
        
        // Staff can message students
        if ($sender_role_lower !== 'student' && $recipient_role_lower === 'student') {
            return true;
        }
        
        // Staff can message other staff
        if ($sender_role_lower !== 'student' && $recipient_role_lower !== 'student') {
            return true;
        }
        
        return false;
    }
}

// Create global auth service instance
$auth_service = new ModernAuthService();

// Handle authentication requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'staff_login':
            $result = $auth_service->authenticateStaff($_POST['email'], $_POST['password']);
            if ($result['success']) {
                $_SESSION['success'] = "Login successful! Welcome, " . $result['user']['first_name'];
                $dashboard = $auth_service->getDashboardRoute($result['user']['role']);
                header("Location: $dashboard");
                exit();
            } else {
                $_SESSION['error'] = $result['message'];
                header("Location: staff-login.php");
                exit();
            }
            break;
            
        case 'student_login':
            $result = $auth_service->authenticateStudent($_POST['index_number'], $_POST['full_name'], $_POST['phone_number']);
            if ($result['success']) {
                $_SESSION['success'] = "Login successful! Welcome, " . $result['user']['first_name'];
                header("Location: dashboards/student.php");
                exit();
            } else {
                $_SESSION['error'] = $result['message'];
                header("Location: student-login.php");
                exit();
            }
            break;
            
        case 'logout':
            $auth_service->destroySession();
            header("Location: index.php");
            exit();
            break;
    }
}

?>
