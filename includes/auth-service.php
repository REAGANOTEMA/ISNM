<?php
// ISNM Unified Authentication Service
// Professional authentication system for all databases

class AuthenticationService {
    private $staffs_conn;
    private $students_conn;
    private $website_conn;
    private $config;
    
    public function __construct() {
        $this->config = [
            'host' => 'localhost',
            'username' => 'root',
            'password' => 'ReagaN23#',
            'charset' => 'utf8mb4'
        ];
        
        $this->initializeConnections();
    }
    
    private function initializeConnections() {
        try {
            // Connect to all three databases
            $this->staffs_conn = new mysqli(
                $this->config['host'],
                $this->config['username'],
                $this->config['password'],
                'staffs_db'
            );
            
            $this->students_conn = new mysqli(
                $this->config['host'],
                $this->config['username'],
                $this->config['password'],
                'students_db'
            );
            
            $this->website_conn = new mysqli(
                $this->config['host'],
                $this->config['username'],
                $this->config['password'],
                'website_db'
            );
            
            // Set charset for all connections
            $this->staffs_conn->set_charset($this->config['charset']);
            $this->students_conn->set_charset($this->config['charset']);
            $this->website_conn->set_charset($this->config['charset']);
            
            // Check connections
            if ($this->staffs_conn->connect_error) {
                throw new Exception("Staffs DB connection failed: " . $this->staffs_conn->connect_error);
            }
            
            if ($this->students_conn->connect_error) {
                throw new Exception("Students DB connection failed: " . $this->students_conn->connect_error);
            }
            
            if ($this->website_conn->connect_error) {
                throw new Exception("Website DB connection failed: " . $this->website_conn->connect_error);
            }
            
        } catch (Exception $e) {
            error_log("Authentication service initialization error: " . $e->getMessage());
            throw $e;
        }
    }
    
    public function authenticateStaff($email, $password) {
        try {
            $stmt = $this->staffs_conn->prepare("
                SELECT s.*, sr.role_name, sr.dashboard_path, sr.permissions 
                FROM staff s 
                JOIN staff_roles sr ON s.role_id = sr.id 
                WHERE s.email = ? AND s.status = 'Active'
            ");
            
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();
                
                if (password_verify($password, $user['password'])) {
                    // Check if password needs rehash
                    if (password_needs_rehash($user['password'])) {
                        $new_hash = password_hash($password, PASSWORD_DEFAULT);
                        $this->updatePassword($user['id'], $new_hash);
                    }
                    
                    // Update last login
                    $this->updateLastLogin($user['id']);
                    
                    // Log successful login
                    $this->logLoginAttempt($email, true);
                    
                    // Create session
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['staff_id'] = $user['staff_id'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['full_name'] = $user['full_name'];
                    $_SESSION['role'] = $user['role_name'];
                    $_SESSION['position'] = $user['position'];
                    $_SESSION['department'] = $user['department'];
                    $_SESSION['dashboard_path'] = $user['dashboard_path'];
                    $_SESSION['permissions'] = json_decode($user['permissions'], true);
                    $_SESSION['type'] = 'staff';
                    $_SESSION['login_time'] = time();
                    $_SESSION['last_activity'] = time();
                    
                    return true;
                }
            }
            
            // Log failed login
            $this->logLoginAttempt($email, false);
            return false;
            
        } catch (Exception $e) {
            error_log("Staff authentication error: " . $e->getMessage());
            return false;
        }
    }
    
    public function authenticateStudent($student_id, $password) {
        try {
            $stmt = $this->students_conn->prepare("
                SELECT * FROM students 
                WHERE student_id = ? AND status = 'Active'
            ");
            
            $stmt->bind_param("s", $student_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 1) {
                $student = $result->fetch_assoc();
                
                if (password_verify($password, $student['password'])) {
                    // Update last login
                    $this->updateStudentLastLogin($student['id']);
                    
                    // Log successful login
                    $this->logStudentLoginAttempt($student_id, true);
                    
                    // Create session
                    $_SESSION['student_id'] = $student['id'];
                    $_SESSION['student_reg_no'] = $student['student_id'];
                    $_SESSION['student_name'] = $student['full_name'];
                    $_SESSION['student_email'] = $student['email'];
                    $_SESSION['program'] = $student['program'];
                    $_SESSION['year_of_study'] = $student['year_of_study'];
                    $_SESSION['type'] = 'student';
                    $_SESSION['login_time'] = time();
                    $_SESSION['last_activity'] = time();
                    
                    return true;
                }
            }
            
            // Log failed login
            $this->logStudentLoginAttempt($student_id, false);
            return false;
            
        } catch (Exception $e) {
            error_log("Student authentication error: " . $e->getMessage());
            return false;
        }
    }
    
    public function checkSessionValidity() {
        if (!isset($_SESSION['type'])) {
            return false;
        }
        
        // Check session timeout (30 minutes)
        $timeout = 30 * 60;
        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout) {
            $this->logout();
            return false;
        }
        
        $_SESSION['last_activity'] = time();
        return true;
    }
    
    public function getDashboardRoute($role) {
        $routes = [
            'Director General' => 'dashboards/director-general.php',
            'School Principal' => 'dashboards/school-principal.php',
            'CEO' => 'dashboards/ceo.php',
            'Director Academics' => 'dashboards/director-academics.php',
            'Director Finance' => 'dashboards/director-finance.php',
            'Director ICT' => 'dashboards/director-ict.php',
            'HR Manager' => 'dashboards/hr-manager.php',
            'Academic Registrar' => 'dashboards/academic-registrar.php',
            'School Bursar' => 'dashboards/school-bursar.php',
            'School Librarian' => 'dashboards/school-librarian.php',
            'Head Nursing' => 'dashboards/head-nursing.php',
            'Head Midwifery' => 'dashboards/head-midwifery.php',
            'Lecturers' => 'dashboards/lecturers.php',
            'Senior Lecturers' => 'dashboards/senior-lecturers.php',
            'Non-Teaching Staff' => 'dashboards/non-teaching-staff.php',
            'Lab Technicians' => 'dashboards/lab-technicians.php',
            'Matrons' => 'dashboards/matrons.php',
            'Security' => 'dashboards/security.php',
            'Drivers' => 'dashboards/drivers.php',
            'Wardens' => 'dashboards/wardens.php',
            'School Secretary' => 'dashboards/school-secretary.php',
            'Bursar' => 'dashboards/bursar.php',
            'Deputy Principal' => 'dashboards/deputy-principal.php'
        ];
        
        return $routes[$role] ?? 'dashboards/default.php';
    }
    
    public function hasPermission($permission) {
        if (!isset($_SESSION['permissions'])) {
            return false;
        }
        
        return $_SESSION['permissions'][$permission] ?? false;
    }
    
    public function logout() {
        // Log activity before destroying session
        if (isset($_SESSION['user_id'])) {
            $this->logActivity($_SESSION['user_id'], 'Logout', 'Authentication', null, 'User logged out');
        }
        
        // Destroy session
        session_destroy();
        session_start();
    }
    
    private function updatePassword($user_id, $new_hash) {
        try {
            $stmt = $this->staffs_conn->prepare("
                UPDATE staff SET password = ?, password_changed = TRUE 
                WHERE id = ?
            ");
            $stmt->bind_param("si", $new_hash, $user_id);
            $stmt->execute();
        } catch (Exception $e) {
            error_log("Password update error: " . $e->getMessage());
        }
    }
    
    private function updateLastLogin($user_id) {
        try {
            $stmt = $this->staffs_conn->prepare("
                UPDATE staff SET last_login = NOW(), login_attempts = 0, locked_until = NULL 
                WHERE id = ?
            ");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
        } catch (Exception $e) {
            error_log("Last login update error: " . $e->getMessage());
        }
    }
    
    private function updateStudentLastLogin($student_id) {
        try {
            $stmt = $this->students_conn->prepare("
                UPDATE students SET last_login = NOW() 
                WHERE id = ?
            ");
            $stmt->bind_param("i", $student_id);
            $stmt->execute();
        } catch (Exception $e) {
            error_log("Student last login update error: " . $e->getMessage());
        }
    }
    
    private function logLoginAttempt($email, $success) {
        try {
            $stmt = $this->staffs_conn->prepare("
                INSERT INTO staff_login_attempts (email, ip_address, user_agent, success, staff_id, attempt_time) 
                VALUES (?, ?, ?, ?, ?, NOW())
            ");
            
            $user_id = $success ? ($this->getUserIdByEmail($email) ?? null) : null;
            $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
            $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
            
            $stmt->bind_param("ssssi", $email, $ip_address, $user_agent, $success, $user_id);
            $stmt->execute();
        } catch (Exception $e) {
            error_log("Login attempt logging error: " . $e->getMessage());
        }
    }
    
    private function logStudentLoginAttempt($student_id, $success) {
        try {
            $stmt = $this->students_conn->prepare("
                INSERT INTO login_attempts (student_id, ip_address, user_agent, success, attempt_time) 
                VALUES (?, ?, ?, ?, NOW())
            ");
            
            $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
            $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
            
            $stmt->bind_param("sssi", $student_id, $ip_address, $user_agent, $success);
            $stmt->execute();
        } catch (Exception $e) {
            error_log("Student login attempt logging error: " . $e->getMessage());
        }
    }
    
    private function logActivity($user_id, $activity, $module, $record_id, $details) {
        try {
            $stmt = $this->staffs_conn->prepare("
                INSERT INTO staff_activity_log (user_id, activity, module_accessed, record_id, details, ip_address, user_agent, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            
            $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
            $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
            
            $stmt->bind_param("ississs", $user_id, $activity, $module, $record_id, $details, $ip_address, $user_agent);
            $stmt->execute();
        } catch (Exception $e) {
            error_log("Activity logging error: " . $e->getMessage());
        }
    }
    
    private function getUserIdByEmail($email) {
        try {
            $stmt = $this->staffs_conn->prepare("SELECT id FROM staff WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            return $result->num_rows === 1 ? $result->fetch_assoc()['id'] : null;
        } catch (Exception $e) {
            error_log("Get user ID error: " . $e->getMessage());
            return null;
        }
    }
    
    public function getStaffsConnection() {
        return $this->staffs_conn;
    }
    
    public function getStudentsConnection() {
        return $this->students_conn;
    }
    
    public function getWebsiteConnection() {
        return $this->website_conn;
    }
    
    public function testConnections() {
        return [
            'staffs_db' => !$this->staffs_conn->connect_error,
            'students_db' => !$this->students_conn->connect_error,
            'website_db' => !$this->website_conn->connect_error
        ];
    }
    
    public function getConnectionInfo() {
        return [
            'staffs_db' => [
                'connected' => !$this->staffs_conn->connect_error,
                'error' => $this->staffs_conn->connect_error,
                'server_info' => $this->staffs_conn->get_server_info()
            ],
            'students_db' => [
                'connected' => !$this->students_conn->connect_error,
                'error' => $this->students_conn->connect_error,
                'server_info' => $this->students_conn->get_server_info()
            ],
            'website_db' => [
                'connected' => !$this->website_conn->connect_error,
                'error' => $this->website_conn->connect_error,
                'server_info' => $this->website_conn->get_server_info()
            ]
        ];
    }
    
    public function __destruct() {
        // Close all connections
        if ($this->staffs_conn) $this->staffs_conn->close();
        if ($this->students_conn) $this->students_conn->close();
        if ($this->website_conn) $this->website_conn->close();
    }
}

// Global authentication service instance
global $auth_service;
$auth_service = new AuthenticationService();
?>
