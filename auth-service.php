<?php
/**
 * Unified Authentication Service for ISNM School Management System
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/error_handler.php';

class AuthenticationService {

    private $maxLoginAttempts = 10;
    private $lockoutDuration  = 300;

    private function isStudentAccountLocked($indexNumber) {
        $conn = getConnection();
        if (!$conn) return false;
        // Auto-unlock expired locks
        $conn->query("UPDATE students SET locked_until = NULL, login_attempts = 0 WHERE locked_until IS NOT NULL AND locked_until <= NOW()");
        $stmt = $conn->prepare("SELECT locked_until FROM students WHERE index_number = ? AND status = 'Active' AND locked_until > NOW()");
        if (!$stmt) return false;
        $stmt->bind_param('s', $indexNumber);
        $stmt->execute();
        $locked = $stmt->get_result()->num_rows > 0;
        $stmt->close();
        return $locked;
    }

    private function isStaffAccountLocked($email) {
        $conn = getStaffConnection();
        if (!$conn) return false;
        // Auto-unlock expired locks
        $conn->query("UPDATE staff SET locked_until = NULL, login_attempts = 0 WHERE locked_until IS NOT NULL AND locked_until <= NOW()");
        $stmt = $conn->prepare("SELECT locked_until FROM staff WHERE LOWER(email) = ? AND LOWER(status) = 'active' AND locked_until > NOW()");
        if (!$stmt) return false;
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $locked = $stmt->get_result()->num_rows > 0;
        $stmt->close();
        return $locked;
    }

    private function recordStudentFailedAttempt($indexNumber) {
        $conn = getConnection();
        if (!$conn) return;
        $s = $conn->prepare("UPDATE students SET login_attempts = login_attempts + 1 WHERE index_number = ?");
        if ($s) { $s->bind_param('s', $indexNumber); $s->execute(); $s->close(); }
        $s2 = $conn->prepare("SELECT login_attempts FROM students WHERE index_number = ?");
        if (!$s2) return;
        $s2->bind_param('s', $indexNumber);
        $s2->execute();
        $row = $s2->get_result()->fetch_assoc();
        $s2->close();
        if ($row && $row['login_attempts'] >= $this->maxLoginAttempts) {
            $lock = date('Y-m-d H:i:s', time() + $this->lockoutDuration);
            $s3 = $conn->prepare("UPDATE students SET locked_until = ? WHERE index_number = ?");
            if ($s3) { $s3->bind_param('ss', $lock, $indexNumber); $s3->execute(); $s3->close(); }
        }
    }

    private function recordStaffFailedAttempt($email) {
        $conn = getStaffConnection();
        if (!$conn) return;
        $s = $conn->prepare("UPDATE staff SET login_attempts = login_attempts + 1 WHERE LOWER(email) = ? AND LOWER(status) = 'active'");
        if ($s) { $s->bind_param('s', $email); $s->execute(); $s->close(); }
        $s2 = $conn->prepare("SELECT login_attempts FROM staff WHERE LOWER(email) = ? AND LOWER(status) = 'active'");
        if (!$s2) return;
        $s2->bind_param('s', $email);
        $s2->execute();
        $row = $s2->get_result()->fetch_assoc();
        $s2->close();
        if ($row && $row['login_attempts'] >= $this->maxLoginAttempts) {
            $lock = date('Y-m-d H:i:s', time() + $this->lockoutDuration);
            $s3 = $conn->prepare("UPDATE staff SET locked_until = ? WHERE LOWER(email) = ? AND LOWER(status) = 'active'");
            if ($s3) { $s3->bind_param('ss', $lock, $email); $s3->execute(); $s3->close(); }
        }
    }

    private function resetStaffFailedAttempts($userId) {
        $conn = getStaffConnection();
        if (!$conn) return;
        $s = $conn->prepare("UPDATE staff SET login_attempts = 0, locked_until = NULL, last_login = NOW() WHERE id = ?");
        if ($s) { $s->bind_param('i', $userId); $s->execute(); $s->close(); }
    }

    private function resetStudentFailedAttempts($userId) {
        $conn = getConnection();
        if (!$conn) return;
        $s = $conn->prepare("UPDATE students SET login_attempts = 0, locked_until = NULL, last_login = NOW() WHERE id = ?");
        if ($s) { $s->bind_param('i', $userId); $s->execute(); $s->close(); }
    }

    private function splitFullName($fullName) {
        $clean = trim(preg_replace('/\s+/', ' ', $fullName));
        $parts = explode(' ', $clean);
        $first = array_shift($parts);
        $sur   = trim(implode(' ', $parts));
        return [trim($first), $sur ?: $first];
    }

    private function loadOrCreateStudentFromMaster($indexNumber, $fullName, $phoneNumber) {
        $conn = getConnection();
        if (!$conn) return null;
        $s = $conn->prepare("SELECT * FROM students WHERE index_number = ? LIMIT 1");
        if ($s) {
            $s->bind_param('s', $indexNumber);
            $s->execute();
            $r = $s->get_result();
            if ($r && $r->num_rows === 1) return $r->fetch_assoc();
            $s->close();
        }
        if (!file_exists(__DIR__ . '/views/student_data_loader.php')) return null;
        require_once __DIR__ . '/views/student_data_loader.php';
        $loader     = new StudentDataLoader();
        $candidates = $loader->searchStudents($indexNumber);
        $match      = null;
        foreach ($candidates as $st) {
            if (strcasecmp(trim($st['index_number'] ?? ''), trim($indexNumber)) === 0) { $match = $st; break; }
        }
        if (!$match) {
            foreach ($loader->searchStudents($fullName) as $st) {
                if (strcasecmp(trim($st['full_name'] ?? ''), trim($fullName)) === 0 &&
                    preg_replace('/[^0-9]/', '', $st['phone'] ?? '') === preg_replace('/[^0-9]/', '', $phoneNumber)) {
                    $match = $st; break;
                }
            }
        }
        if (!$match) return null;
        $matchName = $match['full_name'] ?? $fullName;
        [$first, $sur] = $this->splitFullName($matchName);
        $on   = trim($match['other_name']  ?? '');
        $em   = trim($match['email']        ?? '');
        $prog = trim($match['program']      ?? '');
        $lv   = trim($match['level']        ?? '');
        $set  = trim($match['set']          ?? '');
        $snum = $indexNumber;
        $ins  = $conn->prepare("INSERT INTO students (student_number,index_number,first_name,surname,other_name,email,phone,program,level,set_name,status,is_first_login,password_changed,created_at,updated_at) VALUES (?,?,?,?,?,?,?,?,?,?,'Active',TRUE,FALSE,NOW(),NOW())");
        if (!$ins) return null;
        $excelPhone = trim($match['phone'] ?? $phoneNumber);
        $ins->bind_param('ssssssssss', $snum, $indexNumber, $first, $sur, $on, $em, $excelPhone, $prog, $lv, $set);
        if ($ins->execute()) {
            $id  = $conn->insert_id;
            $rs  = $conn->prepare("SELECT * FROM students WHERE id = ? LIMIT 1");
            if ($rs) { $rs->bind_param('i', $id); $rs->execute(); $r2 = $rs->get_result(); if ($r2 && $r2->num_rows === 1) return $r2->fetch_assoc(); }
        }
        return null;
    }

    public function resetPasswordWithToken($token, $newPassword) {
        if (empty($token) || empty($newPassword)) {
            return ['success' => false, 'message' => 'Token and new password are required.'];
        }
        if (strlen($newPassword) < 8) {
            return ['success' => false, 'message' => 'Password must be at least 8 characters long.'];
        }
        $conn = getStaffConnection();
        if (!$conn) {
            $errMsg = 'Database unavailable.';
            if (defined('APP_DEBUG') && APP_DEBUG && !empty($GLOBALS['isnm_last_db_error'])) {
                $errMsg .= ' (' . $GLOBALS['isnm_last_db_error'] . ')';
            }
            return ['success' => false, 'message' => $errMsg];
        }
        $stmt = $conn->prepare("SELECT id FROM staff WHERE reset_token = ? AND reset_expiry > NOW()");
        if (!$stmt) {
            return ['success' => false, 'message' => 'Invalid or expired reset token.'];
        }
        $stmt->bind_param('s', $token);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows === 0) {
            $stmt->close();
            return ['success' => false, 'message' => 'Invalid or expired reset token.'];
        }
        $row = $result->fetch_assoc();
        $stmt->close();
        $hash = password_hash($newPassword, PASSWORD_BCRYPT);
        $update = $conn->prepare("UPDATE staff SET password = ?, reset_token = NULL, reset_expiry = NULL, updated_at = NOW() WHERE id = ?");
        if (!$update) {
            return ['success' => false, 'message' => 'Failed to update password.'];
        }
        $update->bind_param('si', $hash, $row['id']);
        if ($update->execute()) {
            $update->close();
            return ['success' => true, 'message' => 'Password has been reset successfully.'];
        }
        $update->close();
        return ['success' => false, 'message' => 'Failed to update password.'];
    }

    public function setStudentPassword($studentId, $password) {
        if (!is_int($studentId) || $studentId <= 0) return ['success' => false, 'message' => 'Invalid student record'];
        if (strlen($password) < 8) return ['success' => false, 'message' => 'Password must be at least 8 characters long'];
        $conn = getConnection();
        if (!$conn) {
            $errMsg = 'Database unavailable';
            if (defined('APP_DEBUG') && APP_DEBUG && !empty($GLOBALS['isnm_last_db_error'])) {
                $errMsg .= ' (' . $GLOBALS['isnm_last_db_error'] . ')';
            }
            return ['success' => false, 'message' => $errMsg];
        }
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $s = $conn->prepare("UPDATE students SET password=?,password_changed=TRUE,is_first_login=FALSE,login_attempts=0,locked_until=NULL,updated_at=NOW() WHERE id=?");
        if (!$s) return ['success' => false, 'message' => 'Unable to prepare password update'];
        $s->bind_param('si', $hash, $studentId);
        return $s->execute() ? ['success' => true, 'message' => 'Password saved successfully'] : ['success' => false, 'message' => 'Failed to update password'];
    }

    public function getStudentById($studentId) {
        $conn = getConnection();
        if (!$conn) return null;
        $s = $conn->prepare("SELECT * FROM students WHERE id = ? LIMIT 1");
        if (!$s) return null;
        $s->bind_param('i', $studentId);
        $s->execute();
        $r = $s->get_result();
        return ($r && $r->num_rows === 1) ? $r->fetch_assoc() : null;
    }

    public function authenticateStudent($indexNumber, $fullName = '', $phoneNumber = '', $password = null) {
        $indexNumber = sanitizeInput($indexNumber);
        $fullName    = sanitizeInput($fullName);
        $phoneNumber = sanitizeInput($phoneNumber);
        $password    = is_string($password) ? trim($password) : null;

        if (empty($indexNumber))
            return ['success' => false, 'message' => 'Index number is required.'];
        if (!validateIndexNumber($indexNumber))
            return ['success' => false, 'message' => 'Invalid index number format'];
        if ($this->isStudentAccountLocked($indexNumber))
            return ['success' => false, 'message' => 'Account temporarily locked. Please try again later.'];

        $conn = getConnection();
        if (!$conn) {
            $errMsg = 'Database unavailable';
            if (defined('APP_DEBUG') && APP_DEBUG && !empty($GLOBALS['isnm_last_db_error'])) {
                $errMsg .= ' (' . $GLOBALS['isnm_last_db_error'] . ')';
            }
            return ['success' => false, 'message' => $errMsg];
        }

        $q = $conn->prepare("SELECT id, index_number, TRIM(CONCAT_WS(' ', first_name, NULLIF(other_name,''), surname)) AS full_name, phone, password, is_first_login FROM students WHERE index_number = ? LIMIT 1");

        $existing = null;
        if ($q) {
            $q->bind_param('s', $indexNumber);
            $q->execute();
            $existing = $q->get_result()->fetch_assoc();
            $q->close();
        }

        if ($existing && !empty($existing['password'])) {
            if (empty($password))
                return ['success' => false, 'message' => 'Password is required for this account.'];
            if (!password_verify($password, $existing['password'])) {
                $this->recordStudentFailedAttempt($indexNumber);
                return ['success' => false, 'message' => 'Invalid password. Please try again.'];
            }
            $this->resetStudentFailedAttempts($existing['id']);
            return ['success' => true, 'first_login' => false, 'user' => [
                'id'           => $existing['id'],
                'index_number' => $existing['index_number'],
                'full_name'    => $existing['full_name'],
                'phone'        => $existing['phone'],
                'role'         => 'student',
                'type'         => 'student',
            ]];
        }

        if (empty($fullName) || empty($phoneNumber))
            return ['success' => false, 'message' => 'Full name and phone number are required for first-time login.'];
        if (!validatePhoneLenient($phoneNumber))
            return ['success' => false, 'message' => 'Please enter a valid phone number.'];

        $student = $this->loadOrCreateStudentFromMaster($indexNumber, $fullName, $phoneNumber);
        if (!$student) {
            $this->recordStudentFailedAttempt($indexNumber);
            return ['success' => false, 'message' => 'Unable to find a matching student profile. Please verify your details.'];
        }

        if (!empty($student['password'])) {
            if (empty($password))
                return ['success' => false, 'message' => 'Password is required for this student account.'];
            if (!password_verify($password, $student['password'])) {
                $this->recordStudentFailedAttempt($indexNumber);
                return ['success' => false, 'message' => 'Invalid student credentials. Please check your password.'];
            }
        } else {
            $dbName  = trim(preg_replace('/\s+/', ' ', strtolower($student['full_name'] ?? '')));
            $inName  = trim(preg_replace('/\s+/', ' ', strtolower($fullName)));
            $dbPhone = preg_replace('/[^0-9]/', '', $student['phone'] ?? '');
            $inPhone = preg_replace('/[^0-9]/', '', $phoneNumber);
            if ($dbName !== $inName || $dbPhone !== $inPhone) {
                $this->recordStudentFailedAttempt($indexNumber);
                return ['success' => false, 'message' => 'Name or phone does not match our records. Please use the exact details you registered with.'];
            }
        }

        $this->resetStudentFailedAttempts($student['id']);
        $result = [
            'success' => true,
            'user'    => [
                'id'           => $student['id'],
                'index_number' => $student['index_number'],
                'full_name'    => $student['full_name'],
                'phone'        => $student['phone'],
                'role'         => 'student',
                'type'         => 'student',
            ],
        ];
        if (empty($student['password'])) $result['first_login'] = true;
        return $result;
    }

    public function authenticateStaff($email, $password) {
        // Raw trim only — no sanitization that could alter characters
        $email    = strtolower(trim((string) $email));
        $password = (string) $password; // Do NOT trim or decode password here to preserve exact input

        if (strpos($email, '@') === false)
            $email .= '@igangaschoolofnursingandmidwifery.ac.ug';

        if (empty($email) || empty($password))
            return ['success' => false, 'message' => 'Email and password are required'];
        if (!validateEmail($email))
            return ['success' => false, 'message' => 'Invalid email format'];

        $conn = getStaffConnection();
        if (!$conn) {
            $errMsg = 'Database unavailable. Please contact the system administrator.';
            if (defined('APP_DEBUG') && APP_DEBUG && !empty($GLOBALS['isnm_last_db_error'])) {
                $errMsg .= ' (' . $GLOBALS['isnm_last_db_error'] . ')';
            }
            return ['success' => false, 'message' => $errMsg];
        }

        if ($this->isStaffAccountLocked($email))
            return ['success' => false, 'message' => 'Invalid email or password'];

        // Try with staff_roles JOIN first, fall back to staff-only query if table missing
        $roleName = '';
        $stmt = $conn->prepare(
            "SELECT s.*, sr.role_name FROM staff s
             LEFT JOIN staff_roles sr ON s.role_id = sr.id
             WHERE LOWER(s.email) = ?
             LIMIT 1"
        );
        if (!$stmt) {
            error_log('authenticateStaff JOIN prepare failed (staff_roles may be missing): ' . $conn->error);
            // staff_roles table likely doesn't exist — query staff table directly
            $stmt = $conn->prepare(
                "SELECT * FROM staff WHERE LOWER(email) = ? LIMIT 1"
            );
            if (!$stmt) {
                error_log('authenticateStaff prepare failed: ' . $conn->error);
                return ['success' => false, 'message' => 'Database error. Please contact the system administrator.'];
            }
        }
        $stmt->bind_param('s', $email);
        if (!$stmt->execute()) {
            $stmt->close();
            return ['success' => false, 'message' => 'Database error. Please try again.'];
        }
        $result = $stmt->get_result();
        if (!$result || $result->num_rows === 0) {
            $stmt->close();
            $this->recordStaffFailedAttempt($email);
            return ['success' => false, 'message' => 'Invalid email or password'];
        }
        $staff = $result->fetch_assoc();
        $stmt->close();

        // Get role name — try staff_roles if available, otherwise use role_id
        if (!empty($staff['role_name'])) {
            $roleName = $staff['role_name'];
        } else {
            $roleCheck = $conn->prepare("SELECT role_name FROM staff_roles WHERE id = ? LIMIT 1");
            if (!$roleCheck) {
                error_log('authenticateStaff roleCheck prepare failed: ' . $conn->error);
            }
            if ($roleCheck) {
                $roleCheck->bind_param('i', $staff['role_id']);
                $roleCheck->execute();
                $roleRow = $roleCheck->get_result()->fetch_assoc();
                $roleCheck->close();
                $roleName = $roleRow['role_name'] ?? '';
            }
            if (empty($roleName)) {
                $roleName = $staff['position'] ?? 'Staff';
            }
        }

        // Check status
        if (strtolower($staff['status']) !== 'active') {
            $this->recordStaffFailedAttempt($email);
            return ['success' => false, 'message' => 'Invalid email or password'];
        }

        // Check password - hashed only
        if (!password_verify($password, $staff['password'])) {
            $this->recordStaffFailedAttempt($email);
            return ['success' => false, 'message' => 'Invalid email or password'];
        }

        $this->resetStaffFailedAttempts($staff['id'] ?? 0);
        return [
            'success' => true,
            'user'    => [
                'id'              => $staff['id'] ?? 0,
                'email'           => $staff['email'] ?? '',
                'full_name'       => $staff['full_name'] ?? '',
                'phone'           => $staff['phone'] ?? '',
                'role'            => $roleName,
                'type'            => 'staff',
                'position'        => $staff['position'] ?? '',
                'department'      => $staff['department'] ?? '',
                'is_first_login'  => (bool)($staff['is_first_login'] ?? false),
            ],
        ];
    }

    public function createSecureSession($user) {
        if (session_status() === PHP_SESSION_NONE) {
            ini_set('session.use_only_cookies', 1);
            ini_set('session.cookie_httponly', 1);
            ini_set('session.cookie_samesite', 'Lax');
            ini_set('session.use_strict_mode', 1);
            if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
                ini_set('session.cookie_secure', 1);
            }
            session_start();
        }
        $_SESSION['csrf_token']     = bin2hex(random_bytes(32));
        $_SESSION['user_id']        = $user['id'];
        $_SESSION['email']          = $user['email'];
        $_SESSION['full_name']      = $user['full_name'];
        $_SESSION['role']           = $user['role'];
        $_SESSION['type']           = $user['type'];
        $_SESSION['phone']          = $user['phone']      ?? '';
        $_SESSION['position']       = $user['position']   ?? '';
        $_SESSION['department']     = $user['department'] ?? '';
        $_SESSION['logged_in']      = true;
        $_SESSION['login_time']     = time();
        $_SESSION['last_activity']  = time();
        $_SESSION['session_locked'] = false;
        $_SESSION['can_access_all'] = $this->hasFullInstitutionAccess($user['role'] ?? '');
        $_SESSION['dashboard_path'] = $this->getDashboardRoute($user['role'] ?? '');

        // RBAC identity alignment: provide role_id for module handler/registry
        $_SESSION['role_id'] = null;
        if (!empty($user['role']) && $user['type'] === 'staff') {
            try {
                $conn = getStaffConnection();
                if ($conn) {
                    $roleName = $this->resolveOrganogramPosition($user['role']);
                    $s = $conn->prepare("SELECT id FROM staff_roles WHERE role_name = ? LIMIT 1");
                    if ($s) {
                        $s->bind_param('s', $roleName);
                        $s->execute();
                        $row = $s->get_result()->fetch_assoc();
                        $s->close();
                        if (!empty($row['id'])) {
                            $_SESSION['role_id'] = (int)$row['id'];
                        }
                    }
                }
            } catch (Exception $e) {
                // Keep session valid even if role_id can't be resolved
                error_log('RBAC role_id resolve failed: ' . $e->getMessage());
            }
        }

        if ($user['type'] === 'staff') {
            try {
                $conn = getStaffConnection();
                if ($conn) {
                    $tok = session_id();
                    $ip  = $_SERVER['REMOTE_ADDR']     ?? '';
                    $ua  = $_SERVER['HTTP_USER_AGENT'] ?? '';
                    $s1  = $conn->prepare("INSERT INTO staff_login_sessions (staff_id,session_token,ip_address,user_agent,created_at,expires_at) VALUES (?,?,?,?,NOW(),DATE_ADD(NOW(),INTERVAL 30 MINUTE))");
                    if ($s1) { $s1->bind_param('isss', $user['id'], $tok, $ip, $ua); $s1->execute(); $s1->close(); }
                    $s2  = $conn->prepare("INSERT INTO staff_activity_log (staff_id,activity_type,activity_description,module_accessed,ip_address,user_agent) VALUES (?,'Login','User logged in successfully','authentication',?,?)");
                    if ($s2) { $s2->bind_param('iss', $user['id'], $ip, $ua); $s2->execute(); $s2->close(); }
                }
            } catch (Exception $e) { error_log('Session log skipped: ' . $e->getMessage()); }
        }
        return true;
    }

    public function isAuthenticated() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    }

    public function checkSessionValidity() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) return false;
        return $this->checkAndLockSession();
    }

    /**
     * Check for idle timeout (20 min) and lock the session if exceeded.
     * Also enforces the 1-hour absolute session timeout.
     */
    public function checkAndLockSession() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) return false;

        // 1-hour absolute session timeout (destroy session entirely)
        if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time']) > 3600) {
            $this->logout();
            return false;
        }

        // 20-minute idle timeout → log out
        $idleTimeout = 1200;
        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $idleTimeout) {
            $this->logout();
            return false;
        }

        // Update activity timestamp
        $_SESSION['last_activity'] = time();
        return true;
    }

    public function isSessionLocked() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        return !empty($_SESSION['session_locked']);
    }

    public function lockSession() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $_SESSION['session_locked'] = true;
        return true;
    }

    public function unlockSession() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $_SESSION['session_locked'] = false;
        $_SESSION['last_activity']  = time();
        return true;
    }

    public function logout() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (isset($_SESSION['type'], $_SESSION['user_id']) && $_SESSION['type'] === 'staff') {
            $conn = getStaffConnection();
            if ($conn) {
                $ip = $_SERVER['REMOTE_ADDR']     ?? '';
                $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
                $s  = $conn->prepare("INSERT INTO staff_activity_log (staff_id,activity_type,activity_description,module_accessed,ip_address,user_agent) VALUES (?,'Logout','User logged out','authentication',?,?)");
                if ($s) { $s->bind_param('iss', $_SESSION['user_id'], $ip, $ua); $s->execute(); $s->close(); }
            }
        }
        session_unset();
        session_destroy();
        return true;
    }

    public function destroySession() { return $this->logout(); }

    public function getCurrentUser() {
        if (!$this->isAuthenticated()) return null;
        return [
            'id'         => $_SESSION['user_id']   ?? null,
            'email'      => $_SESSION['email']      ?? null,
            'full_name'  => $_SESSION['full_name']  ?? null,
            'role'       => $_SESSION['role']       ?? null,
            'type'       => $_SESSION['type']       ?? null,
            'phone'      => $_SESSION['phone']      ?? null,
            'position'   => $_SESSION['position']   ?? null,
            'department' => $_SESSION['department'] ?? null,
        ];
    }

    public function resolveOrganogramPosition($position) {
        $aliases = [
            'Chief Executive Officer' => 'CEO',
            'Head of Nursing'         => 'Head Nursing',
            'Head of Midwifery'       => 'Head Midwifery',
        ];
        return $aliases[trim($position)] ?? trim($position);
    }

    public function normalizeRoleKey($value) {
        return trim(preg_replace('/\s+/', ' ', strtolower(preg_replace('/[^a-z0-9]+/i', ' ', trim($value)))));
    }

    public function positionMatchesRole($requestedPosition, $userRole) {
        $req  = $this->normalizeRoleKey($this->resolveOrganogramPosition($requestedPosition));
        $role = $this->normalizeRoleKey($userRole);
        if ($req === '' || $role === '') return false;
        return strpos($role, $req) !== false || strpos($req, $role) !== false;
    }

    public function getStaffEmailForRole($roleName) {
        $roleName = $this->resolveOrganogramPosition($roleName);
        try {
            $conn = getStaffConnection();
            if (!$conn) return null;
            $s = $conn->prepare("SELECT s.email FROM staff s INNER JOIN staff_roles sr ON s.role_id=sr.id WHERE sr.role_name=? AND s.status='Active' ORDER BY s.id ASC LIMIT 1");
            if (!$s) return null;
            $s->bind_param('s', $roleName);
            $s->execute();
            $row = $s->get_result()->fetch_assoc();
            $s->close();
            return $row['email'] ?? null;
        } catch (Exception $e) {
            error_log('getStaffEmailForRole: ' . $e->getMessage());
            return null;
        }
    }

    public function hasFullInstitutionAccess($role) {
        return in_array($this->normalizeRoleKey($role), ['director general','ceo','chief executive officer','system administrator'], true);
    }

    public function getDashboardRouteFromKey($key) {
        $key = $this->normalizeRoleKey($this->resolveOrganogramPosition($key));
        $map = [
            'director general'        => 'dashboards/director-general.php',
            'chief executive officer' => 'dashboards/ceo.php',
            'ceo'                     => 'dashboards/ceo.php',
            'hr manager'              => 'dashboards/hr-manager.php',
            'bursar'                  => 'dashboards/school-bursar.php',
            'school bursar'           => 'dashboards/school-bursar.php',
            'academic registrar'      => 'dashboards/academic-registrar.php',
            'school principal'        => 'dashboards/school-principal.php',
            'principal'               => 'dashboards/school-principal.php',
            'director academics'      => 'dashboards/director-academics.php',
            'director finance'        => 'dashboards/director-finance.php',
            'director ict'            => 'dashboards/director-ict.php',
            'computer lab manager'    => 'dashboards/computer_lab.php',
            'computer lab'            => 'dashboards/computer_lab.php',
            'computer department'     => 'dashboards/director-ict.php',
            'ict officer'             => 'dashboards/director-ict.php',
            'deputy principal'        => 'dashboards/deputy-principal.php',
            'school secretary'        => 'dashboards/school-secretary.php',
            'secretary'               => 'dashboards/school-secretary.php',
            'drivers'                 => 'dashboards/drivers.php',
            'sickbay'                 => 'dashboards/sickbay.php',
            'matrons'                 => 'dashboards/matrons.php',
            'non teaching staff'      => 'dashboards/non-teaching-staff.php',
            'school librarian'        => 'dashboards/school-librarian.php',
            'security'                => 'dashboards/security.php',
            'senior lecturers'        => 'dashboards/senior-lecturers.php',
            'wardens'                 => 'dashboards/wardens.php',
            'lecturers'               => 'dashboards/lecturers.php',
            'storekeeper'             => 'dashboards/storekeeper.php',
            'head nursing'            => 'dashboards/head-nursing.php',
            'head of nursing'         => 'dashboards/head-nursing.php',
            'head midwifery'          => 'dashboards/head-midwifery.php',
            'head of midwifery'       => 'dashboards/head-midwifery.php',
            'director admissions requirements' => 'dashboards/director-admissions.php',
            'director admissions'             => 'dashboards/director-admissions.php',
            'admissions officer'              => 'dashboards/director-admissions.php',
            'admissions clerk'                => 'dashboards/director-admissions.php',
            'store keeper'                    => 'dashboards/storekeeper.php',
            'admissions'                      => 'dashboards/director-admissions.php',
            'guild president'                 => 'dashboards/guild-president.php',
            'skills lab manager'              => 'dashboards/skills-lab.php',
            'skills lab'                      => 'dashboards/skills-lab.php',
            'senior lecturer'                => 'dashboards/senior-lecturers.php',
            'lecturer'                       => 'dashboards/lecturers.php',
            'matron'                         => 'dashboards/matrons.php',
            'warden'                         => 'dashboards/wardens.php',
            'security officer'               => 'dashboards/security.php',
            'sickbay nurse'                  => 'dashboards/sickbay.php',
            'store keeper'                   => 'dashboards/storekeeper.php',
            'driver'                         => 'dashboards/drivers.php',
            'skills lab technician'          => 'dashboards/skills-lab.php',
        ];
        return $map[$key] ?? null;
    }

    public function getDashboardRoute($role) {
        $roleName = trim($role);
        if ($roleName !== '') {
            try {
                $conn = getStaffConnection();
                if ($conn) {
                    $s = $conn->prepare("SELECT dashboard_path FROM staff_roles WHERE role_name = ? LIMIT 1");
                    if ($s && $s->bind_param('s', $roleName) && $s->execute()) {
                        $row = $s->get_result()->fetch_assoc();
                        $s->close();
                        if (!empty($row['dashboard_path'])) return ltrim($row['dashboard_path'], '/');
                    }
                    $resolved = $this->resolveOrganogramPosition($roleName);
                    if ($resolved !== $roleName) {
                        $s2 = $conn->prepare("SELECT dashboard_path FROM staff_roles WHERE role_name = ? LIMIT 1");
                        if ($s2 && $s2->bind_param('s', $resolved) && $s2->execute()) {
                            $row2 = $s2->get_result()->fetch_assoc();
                            $s2->close();
                            if (!empty($row2['dashboard_path'])) return ltrim($row2['dashboard_path'], '/');
                        }
                    }
                }
            } catch (Exception $e) { error_log('getDashboardRoute DB: ' . $e->getMessage()); }
        }
        $exact = $this->getDashboardRouteFromKey($role);
        if ($exact) return $exact;

        // Partial fallback: any role containing "admissions" → director-admissions.php
        $normalized = $this->normalizeRoleKey($role);
        if (strpos($normalized, 'admissions') !== false) {
            return 'dashboards/director-admissions.php';
        }

        return 'dashboards/director-general.php';
    }

    public function canSearchStudentProfiles($role) {
        if ($this->hasFullInstitutionAccess($role)) return true;
        $r = $this->normalizeRoleKey($role);
        $allowed = ['director academics','director finance','director ict','computer department','ict officer','computer lab manager','skills lab manager','skills lab','school principal','deputy principal','academic registrar','hr manager','school secretary','school bursar','bursar','head nursing','head midwifery','head of nursing','head of midwifery','senior lecturers','lecturers','school librarian','matrons','wardens','sickbay','non teaching staff'];
        return in_array($r, $allowed, true) || strpos($r, 'director') !== false || strpos($r, 'lecturer') !== false || strpos($r, 'registrar') !== false;
    }

    public function canCreateStudents($role) {
        return in_array(strtolower(trim($role)), ['director general','hr manager','academic registrar','school principal','director academics','director ict','computer department','ict officer','school secretary','secretary','ceo','computer lab manager','bursar','school bursar'], true);
    }

    public function createStudentAccount($studentData) {
        $index        = sanitizeInput($studentData['index_number']  ?? '');
        $fullName     = sanitizeInput($studentData['full_name']     ?? '');
        $phone        = sanitizeInput($studentData['phone']         ?? '');
        $setName      = sanitizeInput($studentData['set_name']      ?? '');
        $program      = sanitizeInput($studentData['program']       ?? '');
        $level        = sanitizeInput($studentData['level']         ?? '');
        $intakeYear   = sanitizeInput($studentData['intake_year']   ?? '');
        $intakePeriod = sanitizeInput($studentData['intake_period'] ?? '');
        $email        = sanitizeInput($studentData['email']         ?? '');

        if (empty($index) || empty($fullName) || empty($phone))
            return ['success' => false, 'message' => 'Index number, full name and phone are required.'];
        if (!validateIndexNumber($index)) return ['success' => false, 'message' => 'Invalid index number format'];
        if (!validatePhone($phone))       return ['success' => false, 'message' => 'Invalid phone number format'];
        if (!empty($email) && !validateEmail($email)) return ['success' => false, 'message' => 'Invalid email format'];
        if (!empty($intakeYear) && !preg_match('/^20\d{2}$/', $intakeYear)) return ['success' => false, 'message' => 'Invalid intake year format (e.g., 2026)'];

        $conn = getConnection();
        if (!$conn) {
            $errMsg = 'Database unavailable';
            if (defined('APP_DEBUG') && APP_DEBUG && !empty($GLOBALS['isnm_last_db_error'])) {
                $errMsg .= ' (' . $GLOBALS['isnm_last_db_error'] . ')';
            }
            return ['success' => false, 'message' => $errMsg];
        }

        $chk = $conn->prepare("SELECT id FROM students WHERE index_number = ? LIMIT 1");
        if ($chk) { $chk->bind_param('s', $index); $chk->execute(); if ($chk->get_result()->num_rows > 0) { $chk->close(); return ['success' => false, 'message' => 'A student with this index number already exists.']; } $chk->close(); }

        if (file_exists(__DIR__ . '/views/student_data_loader.php')) {
            require_once __DIR__ . '/views/student_data_loader.php';
            try {
                $loader = new StudentDataLoader();
                $match  = $this->findStudentInDataFiles($loader, $index, $phone);
                if ($match) {
                    if (empty($program)      && !empty($match['program']))       $program      = $match['program'];
                    if (empty($level)        && !empty($match['level']))         $level        = $match['level'];
                    if (empty($setName)      && !empty($match['set']))           $setName      = $match['set'];
                    if (empty($intakeYear)   && !empty($match['intake_year']))   $intakeYear   = $match['intake_year'];
                    if (empty($intakePeriod) && !empty($match['intake_period'])) $intakePeriod = $match['intake_period'];
                    if (empty($email)        && !empty($match['email']))         $email        = $match['email'];
                }
            } catch (Exception $e) { error_log('Student data loader: ' . $e->getMessage()); }
        }

        [$firstName, $surname] = $this->splitFullName($fullName);
        if (empty($firstName) || empty($surname))
            return ['success' => false, 'message' => 'Please provide a valid full name with at least two names.'];

        try {
            $s = $conn->prepare("INSERT INTO students (student_number,index_number,first_name,surname,other_name,phone,email,program,level,set_name,intake_year,intake_period,status,is_first_login,password_changed,created_at,updated_at) VALUES (?,?,?,?,'',?,?,?,?,?,?,?,'Active',TRUE,FALSE,NOW(),NOW())");
            if (!$s) return ['success' => false, 'message' => 'Failed to prepare student account creation.'];
            $s->bind_param('sssssssssss', $index, $index, $firstName, $surname, $phone, $email, $program, $level, $setName, $intakeYear, $intakePeriod);
            $s->execute();
            return ['success' => true, 'message' => 'Student account created successfully', 'data' => ['index_number' => $index, 'full_name' => $fullName, 'set' => $setName, 'program' => $program, 'level' => $level]];
        } catch (Exception $e) {
            error_log('createStudentAccount error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to create student account. Please try again or contact an administrator.'];
        }
    }

    private function findStudentInDataFiles($loader, $index, $phone) {
        $clean = preg_replace('/[^0-9]/', '', $phone);
        foreach ($loader->searchStudents($index) as $st) {
            if (strtolower(trim($st['index_number'] ?? '')) === strtolower($index) && preg_replace('/[^0-9]/', '', $st['phone'] ?? '') === $clean) return $st;
        }
        foreach ($loader->searchStudents('') as $st) {
            if (preg_replace('/[^0-9]/', '', $st['phone'] ?? '') === $clean && !empty($st['index_number'])) return $st;
        }
        return null;
    }

    public function createStaffAccount($staffData) {
        $conn = getStaffConnection();
        if (!$conn) {
            $errMsg = 'Database unavailable.';
            if (defined('APP_DEBUG') && APP_DEBUG && !empty($GLOBALS['isnm_last_db_error'])) {
                $errMsg .= ' (' . $GLOBALS['isnm_last_db_error'] . ')';
            }
            return ['success' => false, 'message' => $errMsg];
        }
        try {
            $hash  = password_hash($staffData['password'], PASSWORD_BCRYPT);
            $rs    = $conn->prepare("SELECT id FROM staff_roles WHERE role_name = ?");
            $rs->bind_param('s', $staffData['role']);
            $rs->execute();
            $rrow  = $rs->get_result()->fetch_assoc();
            $rs->close();
            if (!$rrow) return ['success' => false, 'message' => 'Invalid role specified'];
            $rid   = $rrow['id'];
            $s     = $conn->prepare("INSERT INTO staff (full_name,email,phone,password,role_id,position,department,status,created_at) VALUES (?,?,?,?,?,?,?,'Active',NOW())");
            $s->bind_param('ssssiss', $staffData['full_name'], $staffData['email'], $staffData['phone'], $hash, $rid, $staffData['position'], $staffData['department']);
            $s->execute();
            return ['success' => true, 'message' => 'Staff account created successfully'];
        } catch (Exception $e) {
            error_log('createStaffAccount error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to create staff account. Please try again or contact an administrator.'];
        }
    }
}

$auth_service = new AuthenticationService();

// ── Lightweight session activity ping ──────────────────────────────
if (!empty($_GET['ajax']) && $_GET['ajax'] === 'ping_activity' && $auth_service->isAuthenticated()) {
    $auth_service->checkAndLockSession();
    header('Content-Type: application/json');
    echo '{"ok":true,"t":' . time() . '}';
    exit();
}
