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
        $stmt = $conn->prepare("SELECT locked_until FROM students WHERE index_number = ? AND status = 'Active' AND locked_until > NOW()");
        if ($stmt === false) {
            error_log('isStudentAccountLocked prepare failed: ' . $conn->error);
            return false;
        }
        if (!$stmt->bind_param("s", $indexNumber)) {
            error_log('isStudentAccountLocked bind_param failed: ' . $stmt->error);
            $stmt->close();
            return false;
        }
        if (!$stmt->execute()) {
            error_log('isStudentAccountLocked execute failed: ' . $stmt->error);
            $stmt->close();
            return false;
        }
        $result = $stmt->get_result();
        if ($result === false) {
            error_log('isStudentAccountLocked get_result failed: ' . $stmt->error);
            $stmt->close();
            return false;
        }

        $locked = $result->num_rows > 0;
        $stmt->close();
        return $locked;
    }
    
    /**
     * Check if staff account is locked due to failed login attempts
     * @param string $email
     * @return bool
     */
    private function isStaffAccountLocked($email) {
        $conn = getStaffConnection();
        $stmt = $conn->prepare("SELECT locked_until FROM staff WHERE email = ? AND status = 'Active' AND locked_until > NOW()");
        if ($stmt === false) {
            error_log('isStaffAccountLocked prepare failed: ' . $conn->error);
            return false;
        }
        if (!$stmt->bind_param("s", $email)) {
            error_log('isStaffAccountLocked bind_param failed: ' . $stmt->error);
            $stmt->close();
            return false;
        }
        if (!$stmt->execute()) {
            error_log('isStaffAccountLocked execute failed: ' . $stmt->error);
            $stmt->close();
            return false;
        }
        $result = $stmt->get_result();
        if ($result === false) {
            error_log('isStaffAccountLocked get_result failed: ' . $stmt->error);
            $stmt->close();
            return false;
        }

        $locked = $result->num_rows > 0;
        $stmt->close();
        return $locked;
    }
    
    /**
     * Record failed student login attempt
     * @param string $indexNumber
     */
    private function recordStudentFailedAttempt($indexNumber) {
        $conn = getConnection();
        
        // Increment login attempts
        $stmt = $conn->prepare("UPDATE students SET login_attempts = login_attempts + 1 WHERE index_number = ?");
        if ($stmt === false) {
            error_log('recordStudentFailedAttempt prepare failed: ' . $conn->error);
        } else {
            if ($stmt->bind_param("s", $indexNumber) && $stmt->execute()) {
                $stmt->close();
            } else {
                error_log('recordStudentFailedAttempt execute failed: ' . $stmt->error);
                $stmt->close();
            }
        }
        
        // Check if we should lock the account
        $stmt = $conn->prepare("SELECT login_attempts FROM students WHERE index_number = ?");
        if ($stmt === false) {
            error_log('recordStudentFailedAttempt prepare failed: ' . $conn->error);
            return;
        }
        if (!$stmt->bind_param("s", $indexNumber) || !$stmt->execute()) {
            error_log('recordStudentFailedAttempt execute failed: ' . $stmt->error);
            $stmt->close();
            return;
        }
        $result = $stmt->get_result();
        if ($result === false) {
            error_log('recordStudentFailedAttempt get_result failed: ' . $stmt->error);
            $stmt->close();
            return;
        }
        $user = $result->fetch_assoc();
        $stmt->close();
        
        if ($user && $user['login_attempts'] >= $this->maxLoginAttempts) {
            // Lock the account
            $lockUntil = date('Y-m-d H:i:s', time() + $this->lockoutDuration);
            $stmt = $conn->prepare("UPDATE students SET locked_until = ? WHERE index_number = ?");
            if ($stmt === false) {
                error_log('recordStudentFailedAttempt prepare failed: ' . $conn->error);
                return;
            }
            if (!$stmt->bind_param("ss", $lockUntil, $indexNumber) || !$stmt->execute()) {
                error_log('recordStudentFailedAttempt execute failed: ' . $stmt->error);
            }
            $stmt->close();
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
        if ($stmt === false) {
            error_log('recordStaffFailedAttempt prepare failed: ' . $conn->error);
        } else {
            if ($stmt->bind_param("s", $email) && $stmt->execute()) {
                $stmt->close();
            } else {
                error_log('recordStaffFailedAttempt execute failed: ' . $stmt->error);
                $stmt->close();
            }
        }
        
        // Check if we should lock the account
        $stmt = $conn->prepare("SELECT login_attempts FROM staff WHERE email = ? AND status = 'Active'");
        if ($stmt === false) {
            error_log('recordStaffFailedAttempt prepare failed: ' . $conn->error);
            return;
        }
        if (!$stmt->bind_param("s", $email) || !$stmt->execute()) {
            error_log('recordStaffFailedAttempt execute failed: ' . $stmt->error);
            $stmt->close();
            return;
        }
        $result = $stmt->get_result();
        if ($result === false) {
            error_log('recordStaffFailedAttempt get_result failed: ' . $stmt->error);
            $stmt->close();
            return;
        }
        $user = $result->fetch_assoc();
        $stmt->close();
        
        if ($user && $user['login_attempts'] >= $this->maxLoginAttempts) {
            // Lock the account
            $lockUntil = date('Y-m-d H:i:s', time() + $this->lockoutDuration);
            $stmt = $conn->prepare("UPDATE staff SET locked_until = ? WHERE email = ? AND status = 'Active'");
            if ($stmt === false) {
                error_log('recordStaffFailedAttempt prepare failed: ' . $conn->error);
                return;
            }
            if (!$stmt->bind_param("ss", $lockUntil, $email) || !$stmt->execute()) {
                error_log('recordStaffFailedAttempt execute failed: ' . $stmt->error);
            }
            $stmt->close();
        }
    }
    
    /**
     * Reset failed login attempts on successful login for staff
     * @param int $userId
     */
    private function resetStaffFailedAttempts($userId) {
        $conn = getStaffConnection();
        
        $stmt = $conn->prepare("UPDATE staff SET login_attempts = 0, locked_until = NULL, last_login = NOW() WHERE id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
    }
    
    /**
     * Reset failed login attempts on successful login for student
     * @param int $userId
     */
    private function resetStudentFailedAttempts($userId) {
        $conn = getConnection();
        
        $stmt = $conn->prepare("UPDATE students SET login_attempts = 0, locked_until = NULL, last_login = NOW() WHERE id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
    }

    /**
     * Normalize full name into first and surname values
     * @param string $fullName
     * @return array
     */
    private function splitFullName($fullName) {
        $clean = trim(preg_replace('/\s+/', ' ', $fullName));
        $parts = explode(' ', $clean);
        $firstName = array_shift($parts);
        $surname = trim(implode(' ', $parts));
        return [trim($firstName), trim($surname) ?: $firstName];
    }

    /**
     * Load a student from the database or create a minimal student record from master student_data
     * @param string $indexNumber
     * @param string $fullName
     * @param string $phoneNumber
     * @return array|null
     */
    private function loadOrCreateStudentFromMaster($indexNumber, $fullName, $phoneNumber) {
        $conn = getConnection();
        $stmt = $conn->prepare("SELECT * FROM students WHERE index_number = ? LIMIT 1");
        if ($stmt) {
            $stmt->bind_param('s', $indexNumber);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result && $result->num_rows === 1) {
                return $result->fetch_assoc();
            }
        }

        if (!file_exists(__DIR__ . '/views/student_data_loader.php')) {
            return null;
        }
        require_once __DIR__ . '/views/student_data_loader.php';
        $loader = new StudentDataLoader();
        $candidates = $loader->searchStudents($indexNumber);
        $studentMatch = null;

        foreach ($candidates as $student) {
            if (strcasecmp(trim($student['index_number'] ?? ''), trim($indexNumber)) === 0) {
                $studentMatch = $student;
                break;
            }
        }

        if (!$studentMatch) {
            $candidates = $loader->searchStudents($fullName);
            foreach ($candidates as $student) {
                if (strcasecmp(trim($student['full_name'] ?? ''), trim($fullName)) === 0 && preg_replace('/[^0-9]/', '', $student['phone'] ?? '') === preg_replace('/[^0-9]/', '', $phoneNumber)) {
                    $studentMatch = $student;
                    break;
                }
            }
        }

        if (!$studentMatch) {
            return null;
        }

        list($firstName, $surname) = $this->splitFullName($fullName);
        $otherName = trim($studentMatch['other_name'] ?? '');
        $email = trim($studentMatch['email'] ?? '');
        $program = trim($studentMatch['program'] ?? '');
        $level = trim($studentMatch['level'] ?? '');
        $setName = trim($studentMatch['set'] ?? '');
        $intakeYear = trim($studentMatch['intake_year'] ?? '');

        $insert = $conn->prepare(
            "INSERT INTO students (student_number, index_number, first_name, surname, other_name, email, phone, program, level, set_name, status, is_first_login, password_changed, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Active', TRUE, FALSE, NOW(), NOW())"
        );
        if (!$insert) {
            return null;
        }

        $studentNumber = $indexNumber;
        $insert->bind_param('ssssssssss', $studentNumber, $indexNumber, $firstName, $surname, $otherName, $email, $phoneNumber, $program, $level, $setName);
        if ($insert->execute()) {
            $newId = $conn->insert_id;
            $rowStmt = $conn->prepare("SELECT * FROM students WHERE id = ? LIMIT 1");
            if ($rowStmt) {
                $rowStmt->bind_param('i', $newId);
                $rowStmt->execute();
                $row = $rowStmt->get_result();
                if ($row && $row->num_rows === 1) {
                    return $row->fetch_assoc();
                }
            }
        }
        return null;
    }

    /**
     * Update a student's password after first login verification
     * @param int $studentId
     * @param string $password
     * @return array
     */
    public function setStudentPassword($studentId, $password) {
        if (empty($studentId) || !is_int($studentId) || $studentId <= 0) {
            return ['success' => false, 'message' => 'Invalid student record'];
        }
        if (empty($password) || strlen($password) < 8) {
            return ['success' => false, 'message' => 'Password must be at least 8 characters long'];
        }

        $conn = getConnection();
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare(
            "UPDATE students SET password = ?, password_changed = TRUE, is_first_login = FALSE, login_attempts = 0, locked_until = NULL, updated_at = NOW() WHERE id = ?"
        );
        if (!$stmt) {
            return ['success' => false, 'message' => 'Unable to prepare password update'];
        }
        $stmt->bind_param('si', $hash, $studentId);
        if ($stmt->execute()) {
            return ['success' => true, 'message' => 'Password saved successfully'];
        }
        return ['success' => false, 'message' => 'Failed to update password'];
    }

    /**
     * Load a single student record by ID
     * @param int $studentId
     * @return array|null
     */
    public function getStudentById($studentId) {
        $conn = getConnection();
        $stmt = $conn->prepare("SELECT * FROM students WHERE id = ? LIMIT 1");
        if (!$stmt) {
            return null;
        }
        $stmt->bind_param('i', $studentId);
        $stmt->execute();
        $result = $stmt->get_result();
        return ($result && $result->num_rows === 1) ? $result->fetch_assoc() : null;
    }
    
     /**
      * Authenticate student using 3-field verification
      * @param string $indexNumber
      * @param string $fullName
      * @param string $phoneNumber
      * @return array
      */
     public function authenticateStudent($indexNumber, $fullName, $phoneNumber, $password = null) {
         // Validate inputs
         $indexNumber = sanitizeInput($indexNumber);
         $fullName = sanitizeInput($fullName);
         $phoneNumber = sanitizeInput($phoneNumber);
         $password = is_string($password) ? trim($password) : null;
         
         if (empty($indexNumber) || empty($fullName) || empty($phoneNumber)) {
             return ['success' => false, 'message' => 'Index number, full name and phone number are required.'];
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
         
         $student = $this->loadOrCreateStudentFromMaster($indexNumber, $fullName, $phoneNumber);
         if (!$student) {
             $this->recordStudentFailedAttempt($indexNumber);
             return ['success' => false, 'message' => 'Unable to find a matching student profile. Please verify your details.'];
         }
         
         if (!empty($student['password'])) {
             if (empty($password)) {
                 return ['success' => false, 'message' => 'Password is required for this student account.'];
             }
             
             $passwordValid = false;
             if (password_verify($password, $student['password'])) {
                 $passwordValid = true;
             } elseif ($student['password'] === $password) {
                 $passwordValid = true;
             }
             
             if (!$passwordValid) {
                 $this->recordStudentFailedAttempt($indexNumber);
                 return ['success' => false, 'message' => 'Invalid student credentials. Please check your password.'];
             }
         } else {
             // First login flow: allow verification by verified student details.
             if (strcasecmp($student['full_name'], $fullName) !== 0 || preg_replace('/[^0-9]/', '', $student['phone']) !== preg_replace('/[^0-9]/', '', $phoneNumber)) {
                 $this->recordStudentFailedAttempt($indexNumber);
                 return ['success' => false, 'message' => 'Student verification failed. Please use the exact registered details.'];
             }
         }
         
         // Reset failed attempts on successful login or first login validation
         $this->resetStudentFailedAttempts($student['id']);
         
         $result = [
             'success' => true,
             'user' => [
                 'id' => $student['id'],
                 'index_number' => $student['index_number'],
                 'full_name' => $student['full_name'],
                 'phone' => $student['phone'],
                 'role' => 'student',
                 'type' => 'student'
             ]
         ];
         
         if (empty($student['password'])) {
             $result['first_login'] = true;
         }
         
         return $result;
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
        
        // Convert to standard official institutional format if missing domain
        if (strpos($email, '@') === false) {
            $email = $email . '@igangaschoolofnursingandmidwifery.ac.ug';
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
        if ($stmt === false) {
            error_log('authenticateStaff prepare failed: ' . $conn->error . ' -- SQL: ' . $sql);
            // record failed attempt conservatively
            $this->recordStaffFailedAttempt($email);
            return ['success' => false, 'message' => 'Invalid email or password'];
        }

        if (!$stmt->bind_param("s", $email)) {
            error_log('authenticateStaff bind_param failed: ' . $stmt->error);
            $stmt->close();
            $this->recordStaffFailedAttempt($email);
            return ['success' => false, 'message' => 'Invalid email or password'];
        }

        if (!$stmt->execute()) {
            error_log('authenticateStaff execute failed: ' . $stmt->error);
            $stmt->close();
            $this->recordStaffFailedAttempt($email);
            return ['success' => false, 'message' => 'Invalid email or password'];
        }

        $result = $stmt->get_result();
        if ($result === false) {
            error_log('authenticateStaff get_result failed: ' . $stmt->error);
            $stmt->close();
            $this->recordStaffFailedAttempt($email);
            return ['success' => false, 'message' => 'Invalid email or password'];
        }

        error_log("DEBUG: Found " . $result->num_rows . " users");

        if ($result->num_rows === 0) {
            error_log("DEBUG: No user found, recording failed attempt");
            $this->recordStaffFailedAttempt($email);
            $stmt->close();
            return ['success' => false, 'message' => 'Invalid email or password'];
        }
        
        $staff = $result->fetch_assoc();
        error_log("DEBUG: User found - ID: " . $staff['id'] . ", Role: " . $staff['role_name'] . ", Status: " . $staff['status']);
        error_log("DEBUG: Password hash in DB: " . substr($staff['password'], 0, 20) . "...");
        
        // Verify password - allow both default password and hashed passwords
        $defaultPassword = 'staff@123';
        $passwordValid = false;
        
        // Check if password matches default password
        if ($password === $defaultPassword) {
            $passwordValid = true;
        } else {
            // Check against hashed password
            if (password_verify($password, $staff['password'])) {
                $passwordValid = true;
            } elseif ($staff['password'] === $password) {
                // Fallback: accept plaintext stored passwords (for initial import/setup)
                $passwordValid = true;
            }
        }
        
        if (!$passwordValid) {
            error_log("DEBUG: Password verification failed");
            $this->recordStaffFailedAttempt($email);
            return ['success' => false, 'message' => 'Invalid email or password'];
        }
        
        error_log("DEBUG: Authentication successful");
        
        // Reset failed attempts on successful login
        $this->resetStaffFailedAttempts($staff['id']);
        
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
        $_SESSION['can_access_all'] = $this->hasFullInstitutionAccess($user['role'] ?? '');
        $_SESSION['dashboard_path'] = $this->getDashboardRoute($user['role'] ?? '');
        
        // Log the login in staff activity log (non-blocking if tables differ)
        if ($user['type'] === 'staff') {
            try {
                $conn = getStaffConnection();
                $session_token = session_id();
                $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
                $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';

                $stmt = $conn->prepare("INSERT INTO staff_login_sessions (staff_id, session_token, ip_address, user_agent, created_at, expires_at) VALUES (?, ?, ?, ?, NOW(), DATE_ADD(NOW(), INTERVAL 30 MINUTE))");
                if ($stmt) {
                    $stmt->bind_param("isss", $user['id'], $session_token, $ip_address, $user_agent);
                    $stmt->execute();
                }

                $log_stmt = $conn->prepare("INSERT INTO staff_activity_log (staff_id, activity_type, activity_description, module_accessed, ip_address, user_agent) VALUES (?, 'Login', 'User logged in successfully', 'authentication', ?, ?)");
                if ($log_stmt) {
                    $log_stmt->bind_param("iss", $user['id'], $ip_address, $user_agent);
                    $log_stmt->execute();
                }
            } catch (Exception $e) {
                error_log('Session log skipped: ' . $e->getMessage());
            }
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
            $sql = "SELECT s.email FROM staff s
                 INNER JOIN staff_roles sr ON s.role_id = sr.id
                 WHERE sr.role_name = ? AND s.status = 'Active'
                 ORDER BY s.id ASC LIMIT 1";

            $stmt = $conn->prepare($sql);
            if ($stmt === false) {
                error_log('getStaffEmailForRole prepare failed: ' . $conn->error . ' -- SQL: ' . $sql);
                return null;
            }

            if (!$stmt->bind_param("s", $roleName)) {
                error_log('getStaffEmailForRole bind_param failed: ' . $stmt->error);
                $stmt->close();
                return null;
            }

            if (!$stmt->execute()) {
                error_log('getStaffEmailForRole execute failed: ' . $stmt->error);
                $stmt->close();
                return null;
            }

            $res = $stmt->get_result();
            if ($res === false) {
                error_log('getStaffEmailForRole get_result failed: ' . $stmt->error);
                $stmt->close();
                return null;
            }

            $row = $res->fetch_assoc();
            $stmt->close();
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
    /**
     * Director / CEO — full access to all modules and student profiles.
     */
    public function hasFullInstitutionAccess($role) {
        $role = $this->normalizeRoleKey($role);
        $executive = [
            'director general',
            'ceo',
            'chief executive officer',
            'system administrator',
        ];
        return in_array($role, $executive, true);
    }

    public function getDashboardRouteFromKey($key) {
        $key = $this->normalizeRoleKey($this->resolveOrganogramPosition($key));
        $dashboardRoutes = [
            'director general' => 'dashboards/director-general.php',
            'chief executive officer' => 'dashboards/ceo.php',
            'ceo' => 'dashboards/ceo.php',
            'hr manager' => 'dashboards/hr-manager.php',
            'bursar' => 'bursar_dashboard.php',
            'school bursar' => 'bursar_dashboard.php',
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
            'lab technicians' => 'dashboards/sickbay.php', // legacy alias preserved for redirect compatibility
            'sickbay' => 'dashboards/sickbay.php',
            'matrons' => 'dashboards/matrons.php',
            'non teaching staff' => 'dashboards/non-teaching-staff.php',
            'school librarian' => 'dashboards/school-librarian.php',
            'security' => 'dashboards/security.php',
            'senior lecturers' => 'dashboards/senior-lecturers.php',
            'wardens' => 'dashboards/wardens.php',
            'lecturers' => 'dashboards/lecturers.php',
            'principal' => 'dashboards/school-principal.php',
            'secretary' => 'dashboards/school-secretary.php',
            'storekeeper' => 'dashboards/storekeeper.php',
            'computer department' => 'dashboards/director-ict.php',
            'ict officer' => 'dashboards/director-ict.php',
            'director ict' => 'dashboards/director-ict.php',
        ];

        return $dashboardRoutes[$key] ?? null;
    }

    public function canSearchStudentProfiles($role) {
        if ($this->hasFullInstitutionAccess($role)) {
            return true;
        }
        $role = $this->normalizeRoleKey($role);
        $allowed = [
            'director academics', 'director finance', 'director ict',
            'computer department', 'ict officer',
            'school principal', 'deputy principal', 'academic registrar',
            'hr manager', 'school secretary', 'school bursar', 'bursar',
            'head nursing', 'head midwifery', 'head of nursing', 'head of midwifery',
            'senior lecturers', 'lecturers', 'school librarian',
            'matrons', 'wardens', 'lab technicians', 'sickbay', 'non teaching staff',
        ];
        return in_array($role, $allowed, true) || strpos($role, 'director') !== false
            || strpos($role, 'lecturer') !== false || strpos($role, 'registrar') !== false;
    }

    public function getDashboardRoute($role) {
        $roleName = trim($role);
        if ($roleName !== '') {
            try {
                $conn = getStaffConnection();
                $stmt = $conn->prepare(
                    "SELECT dashboard_path FROM staff_roles WHERE role_name = ? LIMIT 1"
                );
                if ($stmt !== false && $stmt->bind_param("s", $roleName) && $stmt->execute()) {
                    $row = $stmt->get_result()->fetch_assoc();
                    if (!empty($row['dashboard_path'])) {
                        return ltrim($row['dashboard_path'], '/');
                    }
                }

                $resolved = $this->resolveOrganogramPosition($roleName);
                if ($resolved !== $roleName) {
                    $stmt2 = $conn->prepare(
                        "SELECT dashboard_path FROM staff_roles WHERE role_name = ? LIMIT 1"
                    );
                    if ($stmt2 !== false && $stmt2->bind_param("s", $resolved) && $stmt2->execute()) {
                        $row2 = $stmt2->get_result()->fetch_assoc();
                        if (!empty($row2['dashboard_path'])) {
                            return ltrim($row2['dashboard_path'], '/');
                        }
                    }
                }
            } catch (Exception $e) {
                error_log('getDashboardRoute DB: ' . $e->getMessage());
            }
        }

        $route = $this->getDashboardRouteFromKey($role);
        return $route ?? 'dashboards/ceo.php';
    }
    
    /**
     * Check if user can create student accounts
     * @param string $role
     * @return bool
     */
    public function canCreateStudents($role) {
        $role = strtolower(trim($role));
        
        $allowedRoles = [
            'director general',
            'hr manager',
            'academic registrar',
            'school principal',
            'director academics',
            'director ict',
            'computer department',
            'ict officer',
            'school secretary',
            'secretary',
            'ceo'
        ];
        
        return in_array($role, $allowedRoles, true);
    }
    
    /**
     * Create student account
     * @param array $studentData
     * @return array
     */
    public function createStudentAccount($studentData) {
        $index = sanitizeInput($studentData['index_number'] ?? '');
        $fullName = sanitizeInput($studentData['full_name'] ?? '');
        $phone = sanitizeInput($studentData['phone'] ?? '');
        
        if (empty($index) || empty($fullName) || empty($phone)) {
            return ['success' => false, 'message' => 'Index number, full name and phone are required to create a student account.'];
        }
        
        if (!validateIndexNumber($index)) {
            return ['success' => false, 'message' => 'Invalid index number format'];
        }
        if (!validatePhone($phone)) {
            return ['success' => false, 'message' => 'Invalid phone number format'];
        }

        $conn = getConnection();
        $existing = $conn->prepare("SELECT id FROM students WHERE index_number = ? LIMIT 1");
        if ($existing) {
            $existing->bind_param('s', $index);
            $existing->execute();
            $existingRes = $existing->get_result();
            if ($existingRes && $existingRes->num_rows > 0) {
                return ['success' => false, 'message' => 'A student account with this index number already exists.'];
            }
        }

        list($firstName, $surname) = $this->splitFullName($fullName);
        if (empty($firstName) || empty($surname)) {
            return ['success' => false, 'message' => 'Please provide a valid full name with at least two names.'];
        }

        try {
            $stmt = $conn->prepare(
                "INSERT INTO students (student_number, index_number, first_name, surname, phone, status, is_first_login, password_changed, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, 'Active', TRUE, FALSE, NOW(), NOW())"
            );
            $studentNumber = $index;
            $stmt->bind_param('sssss', $studentNumber, $index, $firstName, $surname, $phone);
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
