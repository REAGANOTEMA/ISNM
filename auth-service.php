<?php
/**
 * Unified Authentication Service for ISNM School Management System
 * Handles both student and staff authentication with security measures
 */

require_once 'config/database.php';
require_once 'includes/functions.php';

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
        $conn = getConnection();
        
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
        $conn = getStaffConnection();
        
        $stmt = $conn->prepare("SELECT locked_until FROM staff WHERE email = ? AND status = 'Active' AND locked_until > NOW()");
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
        $conn = getConnection();
        
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
        $conn = getStaffConnection();
        
        // Increment login attempts
        $stmt = $conn->prepare("UPDATE staff SET login_attempts = login_attempts + 1 WHERE email = ? AND status = 'Active'");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        
        // Check if we should lock the account
        $stmt = $conn->prepare("SELECT login_attempts FROM staff WHERE email = ? AND status = 'Active'");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        
        if ($user && $user['login_attempts'] >= $this->maxLoginAttempts) {
            // Lock the account
            $lockUntil = date('Y-m-d H:i:s', time() + $this->lockoutDuration);
            $stmt = $conn->prepare("UPDATE staff SET locked_until = ? WHERE email = ? AND status = 'Active'");
            $stmt->bind_param("ss", $lockUntil, $email);
            $stmt->execute();
        }
    }
    
    /**
     * Reset failed login attempts on successful login
     * @param int $userId
     */
    private function resetFailedAttempts($userId) {
        $conn = getStaffConnection();
        
        $stmt = $conn->prepare("UPDATE staff SET login_attempts = 0, locked_until = NULL, last_login = NOW() WHERE id = ?");
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
        
        $conn = getConnection();
        
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
        
        // Convert to standard format if using isnm@2026 format
        if (strpos($email, '@') === false) {
            $email = $email . '@isnm.ug';
        }
        
        if (empty($email) || empty($password)) {
            return ['success' => false, 'message' => 'Email and password are required'];
        }
        
        if (!validateEmail($email)) {
            return ['success' => false, 'message' => 'Invalid email format'];
        }
        
        // Check if account is locked
        if ($this->isStaffAccountLocked($email)) {
            error_log("DEBUG: Account is locked");
            return ['success' => false, 'message' => 'Account temporarily locked due to multiple failed attempts. Please try again later.'];
        }
        
        $conn = getStaffConnection();
        
        // Query database for staff user
        $sql = "SELECT s.*, sr.role_name FROM staff s 
                LEFT JOIN staff_roles sr ON s.role_id = sr.id
                WHERE s.email = ? AND s.status = 'Active'";
        
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
        error_log("DEBUG: User found - ID: " . $staff['id'] . ", Role: " . $staff['role_name'] . ", Status: " . $staff['status']);
        error_log("DEBUG: Password hash in DB: " . substr($staff['password'], 0, 20) . "...");
        
        // Verify password - allow both default password and hashed passwords
        $defaultPassword = '12345678';
        $passwordValid = false;
        
        // Check if password matches default password
        if ($password === $defaultPassword) {
            $passwordValid = true;
        } else {
            // Check against hashed password
            $passwordValid = password_verify($password, $staff['password']);
        }
        
        if (!$passwordValid) {
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
                'role' => $staff['role_name'],
                'type' => 'staff',
                'position' => $staff['position'],
                'department' => $staff['department']
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
        
        // Store user information in session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['type'] = $user['type'];
        $_SESSION['phone'] = $user['phone'] ?? '';
        $_SESSION['position'] = $user['position'] ?? '';
        $_SESSION['department'] = $user['department'] ?? '';
        $_SESSION['logged_in'] = true;
        $_SESSION['login_time'] = time();
        
        // Log the login in staff activity log
        if ($user['type'] === 'staff') {
            $conn = getStaffConnection();
            $stmt = $conn->prepare("INSERT INTO staff_login_sessions (staff_id, session_token, ip_address, user_agent, created_at, expires_at) VALUES (?, ?, ?, ?, NOW(), DATE_ADD(NOW(), INTERVAL 30 MINUTE))");
            $session_token = session_id();
            $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
            $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
            $stmt->bind_param("isss", $user['id'], $session_token, $ip_address, $user_agent);
            $stmt->execute();
            
            // Log activity
            $log_stmt = $conn->prepare("INSERT INTO staff_activity_log (staff_id, activity_type, activity_description, module_accessed, ip_address, user_agent) VALUES (?, 'Login', 'User logged in successfully', 'authentication', ?, ?)");
            $log_stmt->bind_param("iss", $user['id'], $ip_address, $user_agent);
            $log_stmt->execute();
        }
        
        return true;
    }
    
    /**
     * Check if user is authenticated
     * @return bool
     */
    public function isAuthenticated() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    }
    
    /**
     * Logout user and destroy session
     * @return bool
     */
    public function logout() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Log the logout if staff
        if (isset($_SESSION['type']) && $_SESSION['type'] === 'staff' && isset($_SESSION['user_id'])) {
            $conn = getStaffConnection();
            $stmt = $conn->prepare("INSERT INTO staff_activity_log (staff_id, activity_type, activity_description, module_accessed, ip_address, user_agent) VALUES (?, 'Logout', 'User logged out', 'authentication', ?, ?)");
            $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
            $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
            $stmt->bind_param("iss", $_SESSION['user_id'], $ip_address, $user_agent);
            $stmt->execute();
        }
        
        // Destroy session
        session_unset();
        session_destroy();
        
        return true;
    }
    
    /**
     * Get current authenticated user
     * @return array|null
     */
    public function getCurrentUser() {
        if (!$this->isAuthenticated()) {
            return null;
        }
        
        return [
            'id' => $_SESSION['user_id'] ?? null,
            'email' => $_SESSION['email'] ?? null,
            'full_name' => $_SESSION['full_name'] ?? null,
            'role' => $_SESSION['role'] ?? null,
            'type' => $_SESSION['type'] ?? null,
            'phone' => $_SESSION['phone'] ?? null,
            'position' => $_SESSION['position'] ?? null,
            'department' => $_SESSION['department'] ?? null
        ];
    }
    
    /**
     * Map organogram position labels to canonical staff role names
     * @param string $position
     * @return string
     */
    public function resolveOrganogramPosition($position) {
        $position = trim($position);
        $aliases = [
            'Chief Executive Officer' => 'CEO',
            'Head of Nursing' => 'Head Nursing',
            'Head of Midwifery' => 'Head Midwifery',
        ];
        return $aliases[$position] ?? $position;
    }

    /**
     * Normalize role/position strings for comparison
     * @param string $value
     * @return string
     */
    public function normalizeRoleKey($value) {
        return strtolower(preg_replace('/[^a-z0-9]+/i', ' ', trim($value)));
    }

    /**
     * Check if organogram position matches the user's DB role
     * @param string $requestedPosition
     * @param string $userRole
     * @return bool
     */
    public function positionMatchesRole($requestedPosition, $userRole) {
        $requested = $this->normalizeRoleKey($this->resolveOrganogramPosition($requestedPosition));
        $role = $this->normalizeRoleKey($userRole);
        if ($requested === '' || $role === '') {
            return false;
        }
        return strpos($role, $requested) !== false || strpos($requested, $role) !== false;
    }

    /**
     * Look up active staff email for a role (used by organogram login hints)
     * @param string $roleName
     * @return string|null
     */
    public function getStaffEmailForRole($roleName) {
        $roleName = $this->resolveOrganogramPosition($roleName);
        try {
            $conn = getStaffConnection();
            $stmt = $conn->prepare(
                "SELECT s.email FROM staff s
                 INNER JOIN staff_roles sr ON s.role_id = sr.id
                 WHERE sr.role_name = ? AND s.status = 'Active'
                 ORDER BY s.id ASC LIMIT 1"
            );
            $stmt->bind_param("s", $roleName);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            return $row['email'] ?? null;
        } catch (Exception $e) {
            error_log('getStaffEmailForRole: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get dashboard route based on user role
     * @param string $role
     * @return string
     */
    public function getDashboardRoute($role) {
        $role = $this->normalizeRoleKey($role);
        
        $dashboardRoutes = [
            'director general' => 'dashboards/director-general.php',
            'chief executive officer' => 'dashboards/ceo.php',
            'ceo' => 'dashboards/ceo.php',
            'hr manager' => 'dashboards/hr-manager.php',
            'bursar' => 'dashboards/bursar.php',
            'school bursar' => 'dashboards/school-bursar.php',
            'academic registrar' => 'dashboards/academic-registrar.php',
            'school principal' => 'dashboards/school-principal.php',
            'director academics' => 'dashboards/director-academics.php',
            'director finance' => 'dashboards/director-finance.php',
            'director ict' => 'dashboards/director-ict.php',
            'head nursing' => 'dashboards/head-nursing.php',
            'head of nursing' => 'dashboards/head-nursing.php',
            'head midwifery' => 'dashboards/head-midwifery.php',
            'head of midwifery' => 'dashboards/head-midwifery.php',
            'deputy principal' => 'dashboards/deputy-principal.php',
            'school secretary' => 'dashboards/school-secretary.php',
            'drivers' => 'dashboards/drivers.php',
            'lab technicians' => 'dashboards/lab-technicians.php',
            'matrons' => 'dashboards/matrons.php',
            'non teaching staff' => 'dashboards/non-teaching-staff.php',
            'school librarian' => 'dashboards/school-librarian.php',
            'security' => 'dashboards/security.php',
            'senior lecturers' => 'dashboards/senior-lecturers.php',
            'wardens' => 'dashboards/wardens.php',
            'lecturers' => 'dashboards/lecturers.php',
        ];
        
        return $dashboardRoutes[$role] ?? 'dashboards/ceo.php';
    }
    
    /**
     * Check if user can create student accounts
     * @param string $role
     * @return bool
     */
    public function canCreateStudents($role) {
        $role = strtolower($role);
        
        $allowedRoles = [
            'director general',
            'hr manager',
            'academic registrar',
            'school principal',
            'director academics',
            'ceo'
        ];
        
        return in_array($role, $allowedRoles);
    }
    
    /**
     * Create student account
     * @param array $studentData
     * @return array
     */
    public function createStudentAccount($studentData) {
        $conn = getConnection();
        
        try {
            $stmt = $conn->prepare("INSERT INTO users (index_number, full_name, phone, role, status, created_at) VALUES (?, ?, ?, 'student', 'active', NOW())");
            $stmt->bind_param("sss", $studentData['index_number'], $studentData['full_name'], $studentData['phone']);
            $stmt->execute();
            
            return ['success' => true, 'message' => 'Student account created successfully'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Failed to create student account: ' . $e->getMessage()];
        }
    }
    
    /**
     * Create staff account
     * @param array $staffData
     * @return array
     */
    public function createStaffAccount($staffData) {
        $conn = getStaffConnection();
        
        try {
            // Hash password
            $hashedPassword = password_hash($staffData['password'], PASSWORD_DEFAULT);
            
            // Get role_id from staff_roles table
            $roleStmt = $conn->prepare("SELECT id FROM staff_roles WHERE role_name = ?");
            $roleStmt->bind_param("s", $staffData['role']);
            $roleStmt->execute();
            $roleResult = $roleStmt->get_result();
            $roleRow = $roleResult->fetch_assoc();
            
            if (!$roleRow) {
                return ['success' => false, 'message' => 'Invalid role specified'];
            }
            
            $roleId = $roleRow['id'];
            
            $stmt = $conn->prepare("INSERT INTO staff (full_name, email, phone, password, role_id, position, department, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, 'Active', NOW())");
            $stmt->bind_param("ssssiss", $staffData['full_name'], $staffData['email'], $staffData['phone'], $hashedPassword, $roleId, $staffData['position'], $staffData['department']);
            $stmt->execute();
            
            return ['success' => true, 'message' => 'Staff account created successfully'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Failed to create staff account: ' . $e->getMessage()];
        }
    }
    
    /**
     * Destroy session (alias for logout)
     * @return bool
     */
    public function destroySession() {
        return $this->logout();
    }
}

// Create global authentication service instance
$auth_service = new AuthenticationService();

?>
