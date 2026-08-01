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
        if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
        $locked = $stmt->get_result()->num_rows > 0;
        $stmt->close();
        return $locked;
    }

    private function isStaffAccountLocked($email) {
        $conn = getStaffConnection();
        if (!$conn) return false;
        // Auto-unlock expired locks (try status column first, fall back to is_active)
        $unlockResult = $conn->query("UPDATE staff SET locked_until = NULL, login_attempts = 0 WHERE locked_until IS NOT NULL AND locked_until <= NOW()");
        if (!$unlockResult) { error_log('isStaffAccountLocked: Failed to auto-unlock expired staff locks - ' . $conn->error); }
        // Try with status column
        $stmt = $conn->prepare("SELECT 1 FROM staff WHERE LOWER(email) = ? AND (LOWER(COALESCE(status,'active')) = 'active') AND locked_until IS NOT NULL AND locked_until > NOW() LIMIT 1");
        if (!$stmt) {
            // status column may not exist — skip lock check gracefully
            error_log('isStaffAccountLocked: status/locked_until column may be missing');
            return false;
        }
        $stmt->bind_param('s', $email);
        if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
        $locked = $stmt->get_result()->num_rows > 0;
        $stmt->close();
        return $locked;
    }

    private function recordStudentFailedAttempt($indexNumber) {
        $conn = getConnection();
        if (!$conn) return;
        $s = $conn->prepare("UPDATE students SET login_attempts = login_attempts + 1 WHERE index_number = ?");
        if ($s) { $s->bind_param('s', $indexNumber); if (!$s->execute()) { error_log('$s execute failed: ' . ($s->error ?? 'unknown')); }; $s->close(); }
        $s2 = $conn->prepare("SELECT login_attempts FROM students WHERE index_number = ?");
        if (!$s2) return;
        $s2->bind_param('s', $indexNumber);
        if (!$s2->execute()) { error_log('$s2 execute failed: ' . ($s2->error ?? 'unknown')); };
        $row = $s2->get_result()->fetch_assoc();
        $s2->close();
        if ($row && $row['login_attempts'] >= $this->maxLoginAttempts) {
            $lock = date('Y-m-d H:i:s', time() + $this->lockoutDuration);
            $s3 = $conn->prepare("UPDATE students SET locked_until = ? WHERE index_number = ?");
            if ($s3) { $s3->bind_param('ss', $lock, $indexNumber); if (!$s3->execute()) { error_log('$s3 execute failed: ' . ($s3->error ?? 'unknown')); }; $s3->close(); }
        }
    }

    private function recordStaffFailedAttempt($email) {
        $conn = getStaffConnection();
        if (!$conn) return;
        // Try updating with status column, fall back to is_active
        $s = $conn->prepare("UPDATE staff SET login_attempts = COALESCE(login_attempts,0) + 1 WHERE LOWER(email) = ? AND (LOWER(COALESCE(status,'active')) = 'active')");
        if (!$s) {
            error_log('recordStaffFailedAttempt: Failed to prepare UPDATE - login_attempts/status column may be missing - ' . $conn->error);
            return;
        }
        $s->bind_param('s', $email);
        if (!$s->execute()) { error_log('$s execute failed: ' . ($s->error ?? 'unknown')); }; $s->close();
        // Intentional @: optional lookup — staff schema may lack login_attempts column
        $s2 = @$conn->prepare("SELECT COALESCE(login_attempts,0) AS login_attempts FROM staff WHERE LOWER(email) = ? AND (LOWER(COALESCE(status,'active')) = 'active')");
        if (!$s2) return;
        $s2->bind_param('s', $email);
        if (!$s2->execute()) { error_log('$s2 execute failed: ' . ($s2->error ?? 'unknown')); };
        $row = $s2->get_result()->fetch_assoc();
        $s2->close();
        if ($row && $row['login_attempts'] >= $this->maxLoginAttempts) {
            $lock = date('Y-m-d H:i:s', time() + $this->lockoutDuration);
            $s3 = $conn->prepare("UPDATE staff SET locked_until = ? WHERE LOWER(email) = ? AND (LOWER(COALESCE(status,'active')) = 'active')");
            if (!$s3) {
                error_log("recordStaffFailedAttempt: Failed to prepare lock UPDATE - " . $conn->error);
            } else {
                $s3->bind_param('ss', $lock, $email); if (!$s3->execute()) { error_log('$s3 execute failed: ' . ($s3->error ?? 'unknown')); }; $s3->close();
            }
        }
    }

    private function resetStaffFailedAttempts($userId) {
        $conn = getStaffConnection();
        if (!$conn) return;
        $s = $conn->prepare("UPDATE staff SET login_attempts = 0, locked_until = NULL, last_login = NOW() WHERE id = ?");
        if (!$s) {
            error_log("resetStaffFailedAttempts: Failed to prepare UPDATE - " . $conn->error);
            return;
        }
        if ($s) { $s->bind_param('i', $userId); if (!$s->execute()) { error_log('$s execute failed: ' . ($s->error ?? 'unknown')); }; $s->close(); }
    }

    private function resetStudentFailedAttempts($userId) {
        $conn = getConnection();
        if (!$conn) return;
        $s = $conn->prepare("UPDATE students SET login_attempts = 0, locked_until = NULL, last_login = NOW() WHERE id = ?");
        if ($s) { $s->bind_param('i', $userId); if (!$s->execute()) { error_log('$s execute failed: ' . ($s->error ?? 'unknown')); }; $s->close(); }
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
            if (!$s->execute()) { error_log('$s execute failed: ' . ($s->error ?? 'unknown')); };
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
            if ($rs) { $rs->bind_param('i', $id); if (!$rs->execute()) { error_log('$rs execute failed: ' . ($rs->error ?? 'unknown')); }; $r2 = $rs->get_result(); if ($r2 && $r2->num_rows === 1) return $r2->fetch_assoc(); }
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
            error_log('resetPasswordWithToken: Database connection failed');
            return ['success' => false, 'message' => 'Database unavailable. Please try again later.'];
        }
        $stmt = $conn->prepare("SELECT id FROM staff WHERE reset_token = ? AND reset_expiry > NOW()");
        if (!$stmt) {
            return ['success' => false, 'message' => 'Invalid or expired reset token.'];
        }
        $stmt->bind_param('s', $token);
        if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
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
            error_log('setStudentPassword: Database connection failed');
            return ['success' => false, 'message' => 'Database unavailable. Please try again later.'];
        }

        // Rate limit: max 3 password set attempts per 15 minutes per student
        $idxStmt = $conn->prepare("SELECT index_number FROM students WHERE id = ?");
        if ($idxStmt) {
            $idxStmt->bind_param('i', $studentId);
            $idxStmt->execute();
            $idxRow = $idxStmt->get_result()->fetch_assoc();
            $studentIndex = $idxRow['index_number'] ?? '';
            $idxStmt->close();
        } else {
            $studentIndex = '';
        }
        $checkLimit = $conn->prepare("SELECT COUNT(*) as attempts FROM student_login_attempts WHERE student_index_number = ? AND action = 'set_password' AND created_at > DATE_SUB(NOW(), INTERVAL 15 MINUTE)");
        if ($checkLimit) {
            $checkLimit->bind_param('s', $studentIndex);
            $checkLimit->execute();
            $limitResult = $checkLimit->get_result();
            $limitRow = $limitResult->fetch_assoc();
            if ($limitRow['attempts'] >= 3) {
                return ['success' => false, 'message' => 'Too many password set attempts. Please try again in 15 minutes.'];
            }
        }

        $hash = password_hash($password, PASSWORD_BCRYPT);
        $s = $conn->prepare("UPDATE students SET password=?,password_changed=TRUE,is_first_login=FALSE,login_attempts=0,locked_until=NULL,updated_at=NOW() WHERE id=?");
        if (!$s) return ['success' => false, 'message' => 'Unable to prepare password update'];
        $s->bind_param('si', $hash, $studentId);
        $ok = $s->execute(); if (!$ok) { error_log('$s execute failed: ' . ($s->error ?? 'unknown')); } return $ok ? ['success' => true, 'message' => 'Password saved successfully'] : ['success' => false, 'message' => 'Failed to update password'];
    }

    public function getStudentById($studentId) {
        $conn = getConnection();
        if (!$conn) return null;
        $s = $conn->prepare("SELECT * FROM students WHERE id = ? LIMIT 1");
        if (!$s) return null;
        $s->bind_param('i', $studentId);
        if (!$s->execute()) { error_log('$s execute failed: ' . ($s->error ?? 'unknown')); };
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
            error_log('authenticateStudent: Database connection failed');
            return ['success' => false, 'message' => 'Database unavailable. Please try again later.'];
        }

        $q = $conn->prepare("SELECT id, index_number, TRIM(CONCAT_WS(' ', first_name, NULLIF(other_name,''), surname)) AS full_name, phone, password, is_first_login FROM students WHERE index_number = ? LIMIT 1");

        $existing = null;
        if ($q) {
            $q->bind_param('s', $indexNumber);
            if (!$q->execute()) { error_log('$q execute failed: ' . ($q->error ?? 'unknown')); };
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
            error_log('authenticateStaff: Database connection failed');
            return ['success' => false, 'message' => 'Database unavailable. Please contact the system administrator.'];
        }

        if ($this->isStaffAccountLocked($email)) {
            error_log("authenticateStaff: Account locked for $email");
            return ['success' => false, 'message' => 'Account temporarily locked. Please try again later.'];
        }

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
            error_log("authenticateStaff: execute failed for $email: " . $stmt->error);
            $stmt->close();
            return ['success' => false, 'message' => 'Database error. Please try again.'];
        }
        $result = $stmt->get_result();
        if (!$result || $result->num_rows === 0) {
            error_log("authenticateStaff: No staff found for email=$email");
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
            // Intentional @: staff_roles table may not exist on all schemas — role_name is non-critical
            $roleCheck = @$conn->prepare("SELECT role_name FROM staff_roles WHERE id = ? LIMIT 1");
            if ($roleCheck) {
                $roleCheck->bind_param('i', $staff['role_id']);
                if (!$roleCheck->execute()) { error_log('$roleCheck execute failed: ' . ($roleCheck->error ?? 'unknown')); };
                $roleRow = $roleCheck->get_result()->fetch_assoc();
                $roleCheck->close();
                $roleName = $roleRow['role_name'] ?? '';
            }
            if (empty($roleName)) {
                $roleName = $staff['position'] ?? 'Staff';
            }
        }

        // Check status — support both `status` (varchar) and `is_active` (tinyint) columns
        $isActive = true;
        if (isset($staff['status'])) {
            $isActive = strtolower(trim($staff['status'])) === 'active';
        } elseif (isset($staff['is_active'])) {
            $isActive = (bool)$staff['is_active'];
        }
        if (!$isActive) {
            error_log("authenticateStaff: Account inactive for $email");
            $this->recordStaffFailedAttempt($email);
            return ['success' => false, 'message' => 'Invalid email or password'];
        }

        // Check password — support bcrypt, MD5, sha1, and plain text with auto-rehash
        $passwordVerified = false;
        $needsRehash = false;

        if (password_verify($password, $staff['password'])) {
            // bcrypt match
            $passwordVerified = true;
            // Check if hash needs rehash (algorithm upgrade or cost change)
            if (password_needs_rehash($staff['password'], PASSWORD_DEFAULT)) {
                $needsRehash = true;
            }
        } elseif (strlen($staff['password']) === 32 && ctype_xdigit($staff['password'])) {
            // MD5 hash — verify and auto-upgrade to bcrypt
            if (md5($password) === $staff['password']) {
                $passwordVerified = true;
                $needsRehash = true;
                error_log("authenticateStaff: Legacy MD5 password verified for $email — upgrading to bcrypt");
            }
        } elseif (strlen($staff['password']) === 40 && ctype_xdigit($staff['password'])) {
            // SHA1 hash — verify and auto-upgrade to bcrypt
            if (sha1($password) === $staff['password']) {
                $passwordVerified = true;
                $needsRehash = true;
                error_log("authenticateStaff: Legacy SHA1 password verified for $email — upgrading to bcrypt");
            }
        } elseif ($password === $staff['password']) {
            // Legacy plain-text support: auto-upgrade to bcrypt
            // NOTE: This path exists only for pre-existing accounts; new passwords are always bcrypt-hashed.
            $passwordVerified = true;
            $needsRehash = true;
            error_log("authenticateStaff: Plain text password verified for $email — upgrading to bcrypt");
        }

        if (!$passwordVerified) {
            error_log("authenticateStaff: Password mismatch for $email");
            $this->recordStaffFailedAttempt($email);
            return ['success' => false, 'message' => 'Invalid email or password'];
        }

        // Auto-upgrade legacy password to bcrypt
        if ($needsRehash) {
            $newHash = password_hash($password, PASSWORD_DEFAULT);
            $up = $conn->prepare("UPDATE staff SET password = ? WHERE id = ?");
            if (!$up) {
                error_log("authenticateStaff: Failed to prepare password rehash for $email - " . $conn->error);
            } else {
                $up->bind_param('si', $newHash, $staff['id']);
                if (!$up->execute()) {
                    error_log("authenticateStaff: Failed to rehash password for $email: " . $up->error);
                }
                $up->close();
            }
        }

        error_log("authenticateStaff: Login successful for $email (role=$roleName)");
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
            $https = false;
            if (!empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off') {
                $https = true;
            }
            if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
                $https = in_array(strtolower((string)$_SERVER['HTTP_X_FORWARDED_PROTO']), ['https', 'wss'], true);
            }
            if ($https) {
                ini_set('session.cookie_secure', 1);
            } else {
                ini_set('session.cookie_secure', 0);
            }
            ini_set('session.cookie_path', SESSION_COOKIE_PATH);
            session_start();
        }
        // Prevent session fixation: regenerate ID and discard old session
        session_regenerate_id(true);
        $_SESSION['csrf_token']     = bin2hex(random_bytes(32));
        $_SESSION['user_id']        = $user['id'];
        $_SESSION['email']          = $user['email']      ?? '';
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
                        if (!$s->execute()) { error_log('$s execute failed: ' . ($s->error ?? 'unknown')); };
                        $row = $s->get_result()->fetch_assoc();
                        $s->close();
                        if (!empty($row['id'])) {
                            $_SESSION['role_id'] = (int)$row['id'];
                        }
                    }
                }
            } catch (\Throwable $e) {
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
                    if ($s1) { $s1->bind_param('isss', $user['id'], $tok, $ip, $ua); if (!$s1->execute()) { error_log('$s1 execute failed: ' . ($s1->error ?? 'unknown')); }; $s1->close(); }
                    $s2  = $conn->prepare("INSERT INTO staff_activity_log (staff_id,activity_type,activity_description,module_accessed,ip_address,user_agent) VALUES (?,'Login','User logged in successfully','authentication',?,?)");
                    if ($s2) { $s2->bind_param('iss', $user['id'], $ip, $ua); if (!$s2->execute()) { error_log('$s2 execute failed: ' . ($s2->error ?? 'unknown')); }; $s2->close(); }
                }
            } catch (\Throwable $e) { error_log('Session log skipped: ' . $e->getMessage()); }
        }
        return true;
    }

    /**
     * Issue a persistent "remember me" token stored in the database
     * (staff_login_sessions for staff, user_sessions for students) and set
     * an HttpOnly cookie. The DB only ever stores the SHA-256 hash.
     */
    public function issueRememberMe(int $userId, string $type) {
        if ($userId <= 0) return false;
        $type    = $type === 'staff' ? 'staff' : 'student';
        $token   = bin2hex(random_bytes(32));
        $hash    = hash('sha256', $token);
        $expires = date('Y-m-d H:i:s', time() + 2592000); // 30 days
        $ip      = $_SERVER['REMOTE_ADDR']     ?? '';
        $ua      = $_SERVER['HTTP_USER_AGENT'] ?? '';

        if ($type === 'staff') {
            $conn = getStaffConnection();
            if (!$conn) return false;
            $s = $conn->prepare("INSERT INTO staff_login_sessions (staff_id, session_token, ip_address, user_agent, expires_at) VALUES (?,?,?,?,?)");
            if (!$s) return false;
            $s->bind_param('issss', $userId, $hash, $ip, $ua, $expires);
        } else {
            $conn = getConnection();
            if (!$conn) return false;
            $s = $conn->prepare("INSERT INTO user_sessions (user_id, session_token, ip_address, user_agent, expires_at) VALUES (?,?,?,?,?)");
            if (!$s) return false;
            $s->bind_param('issss', $userId, $hash, $ip, $ua, $expires);
        }
        if (!$s->execute()) { error_log('issueRememberMe execute failed: ' . ($s->error ?? 'unknown')); $s->close(); return false; }
        $s->close();

        setcookie('isnm_remember_' . $type, $token, [
            'expires'  => time() + 2592000,
            'path'     => defined('SESSION_COOKIE_PATH') ? SESSION_COOKIE_PATH : '/',
            'secure'   => !empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        return true;
    }

    /**
     * Restore a session from a valid "remember me" cookie (token rotation on use).
     * Returns true when a session was created.
     */
    public function restoreRememberMe() {
        try {
            if (session_status() === PHP_SESSION_NONE) session_start();
            if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) return false;

            $type = null; $cookie = null;
            if (!empty($_COOKIE['isnm_remember_staff']))   { $type = 'staff';   $cookie = (string)$_COOKIE['isnm_remember_staff']; }
            elseif (!empty($_COOKIE['isnm_remember_student'])) { $type = 'student'; $cookie = (string)$_COOKIE['isnm_remember_student']; }
            if ($type === null || $cookie === '') return false;
            if (strlen($cookie) !== 64 || !ctype_xdigit($cookie)) {
                $this->clearRememberMe();
                return false;
            }
            $hash = hash('sha256', $cookie);

            $user = null;
            if ($type === 'staff') {
                $conn = getStaffConnection();
                if (!$conn) return false;
                $s = $conn->prepare(
                    "SELECT sls.staff_id, s.email, s.full_name, s.phone, s.position, s.department, s.is_first_login, sr.role_name
                     FROM staff_login_sessions sls
                     JOIN staff s ON s.id = sls.staff_id
                     LEFT JOIN staff_roles sr ON s.role_id = sr.id
                     WHERE sls.session_token = ? AND sls.expires_at > NOW()
                     LIMIT 1"
                );
                if (!$s) return false;
                $s->bind_param('s', $hash);
                if (!$s->execute()) { error_log('restoreRememberMe staff execute failed: ' . ($s->error ?? 'unknown')); $s->close(); return false; }
                $row = $s->get_result()->fetch_assoc();
                $s->close();
                if (!$row || empty($row['staff_id'])) return false;
                $del = $conn->prepare("DELETE FROM staff_login_sessions WHERE session_token = ?");
                if ($del) { $del->bind_param('s', $hash); if (!$del->execute()) { error_log('$del execute failed: ' . ($del->error ?? 'unknown')); }; $del->close(); }
                $roleName = $row['role_name'] ?? ($row['position'] ?? 'Staff');
                $user = [
                    'id'             => (int)$row['staff_id'],
                    'email'          => $row['email']    ?? '',
                    'full_name'      => $row['full_name'] ?? '',
                    'phone'          => $row['phone']    ?? '',
                    'role'           => $roleName,
                    'type'           => 'staff',
                    'position'       => $row['position']    ?? '',
                    'department'     => $row['department']  ?? '',
                    'is_first_login' => (bool)($row['is_first_login'] ?? false),
                ];
            } else {
                $conn = getConnection();
                if (!$conn) return false;
                $s = $conn->prepare(
                    "SELECT u.user_id, s.id, s.index_number, TRIM(CONCAT_WS(' ', s.first_name, NULLIF(s.other_name,''), s.surname)) AS full_name, s.phone
                     FROM user_sessions u
                     JOIN students s ON s.id = u.user_id
                     WHERE u.session_token = ? AND u.expires_at > NOW()
                     LIMIT 1"
                );
                if (!$s) return false;
                $s->bind_param('s', $hash);
                if (!$s->execute()) { error_log('restoreRememberMe student execute failed: ' . ($s->error ?? 'unknown')); $s->close(); return false; }
                $row = $s->get_result()->fetch_assoc();
                $s->close();
                if (!$row || empty($row['user_id'])) return false;
                $del = $conn->prepare("DELETE FROM user_sessions WHERE session_token = ?");
                if ($del) { $del->bind_param('s', $hash); if (!$del->execute()) { error_log('$del execute failed: ' . ($del->error ?? 'unknown')); }; $del->close(); }
                $user = [
                    'id'           => (int)$row['id'],
                    'index_number' => $row['index_number'] ?? '',
                    'full_name'    => $row['full_name'] ?? '',
                    'phone'        => $row['phone'] ?? '',
                    'role'         => 'student',
                    'type'         => 'student',
                ];
            }

            $this->createSecureSession($user);
            $this->issueRememberMe((int)$user['id'], $type); // rotate token
            return true;
        } catch (\Throwable $e) {
            error_log('restoreRememberMe failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Remove the "remember me" token from the DB and clear the cookie.
     */
    public function clearRememberMe() {
        foreach (['staff', 'student'] as $t) {
            if (empty($_COOKIE['isnm_remember_' . $t])) continue;
            $hash = hash('sha256', (string)$_COOKIE['isnm_remember_' . $t]);
            if ($t === 'staff') { $conn = getStaffConnection(); }
            else { $conn = getConnection(); }
            if ($conn) {
                $table = $t === 'staff' ? 'staff_login_sessions' : 'user_sessions';
                $col   = $t === 'staff' ? 'staff_id' : 'user_id';
                $s = $conn->prepare("DELETE FROM `$table` WHERE session_token = ?");
                if ($s) { $s->bind_param('s', $hash); if (!$s->execute()) { error_log('clearRememberMe execute failed: ' . ($s->error ?? 'unknown')); }; $s->close(); }
            }
            setcookie('isnm_remember_' . $t, '', [
                'expires'  => time() - 3600,
                'path'     => defined('SESSION_COOKIE_PATH') ? SESSION_COOKIE_PATH : '/',
                'secure'   => !empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off',
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
        }
    }

    public function isAuthenticated() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) return true;
        return $this->restoreRememberMe();
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

        // 20-minute idle timeout â†’ log out
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
        $this->clearRememberMe();
        if (isset($_SESSION['type'], $_SESSION['user_id']) && $_SESSION['type'] === 'staff') {
            $conn = getStaffConnection();
            if ($conn) {
                $ip = $_SERVER['REMOTE_ADDR']     ?? '';
                $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
                $s  = $conn->prepare("INSERT INTO staff_activity_log (staff_id,activity_type,activity_description,module_accessed,ip_address,user_agent) VALUES (?,'Logout','User logged out','authentication',?,?)");
                if ($s) { $s->bind_param('iss', $_SESSION['user_id'], $ip, $ua); if (!$s->execute()) { error_log('$s execute failed: ' . ($s->error ?? 'unknown')); }; $s->close(); }
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
            'Chief Executive Officer'           => 'CEO',
            'Head of Nursing'                   => 'Head Nursing',
            'Head of Midwifery'                 => 'Head Midwifery',
            'Director Admissions & Requirements'=> 'Director Admissions & Requirements',
            'Director Admissions'               => 'Director Admissions & Requirements',
            'Academic Registrar'                => 'Academic Registrar',
            'HR Manager'                        => 'HR Manager',
            'School Secretary'                  => 'School Secretary',
            'School Librarian'                  => 'School Librarian',
            'Events Coordinator'                => 'Events Coordinator',
            'Alumni Relations Officer'          => 'Alumni Relations Officer',
            'Store Keeper'                      => 'Storekeeper',
            'Skills Lab Manager'                => 'Skills Lab Manager',
            'Skills Lab'                        => 'Skills Lab',
            'Computer Lab Manager'              => 'Computer Lab Manager',
            'Guild President'                   => 'Guild President',
            'Security Officer'                  => 'Security',
            'Director ICT'                      => 'Director ICT',
        ];
        return $aliases[trim($position)] ?? trim($position);
    }

    public function normalizeRoleKey($value) {
        return trim(preg_replace('/\s+/', ' ', strtolower(preg_replace('/[^a-z0-9]+/i', ' ', trim($value)))));
    }

    /**
     * Normalize and extract significant words from a role string.
     * Strips common filler words (of, the, and, in, for, at, to, a, an, by, with, or)
     * and returns a set of unique, meaningful words for flexible matching.
     */
    public function getRoleKeywords($value) {
        $normalized = $this->normalizeRoleKey($value);
        $words = explode(' ', $normalized);
        $filler = ['of', 'the', 'and', 'in', 'for', 'at', 'to', 'a', 'an', 'by', 'with', 'or'];
        $significant = array_diff($words, $filler);
        return array_values(array_unique(array_filter($significant)));
    }

    /**
     * Check if every word in $wordsA is fuzzy-matched in $wordsB.
     * Fuzzy means: exact match OR one word is a substring of the other.
     * This correctly handles: "lecturer" vs "lecturers", "driver" vs "drivers",
     * "storekeeper" vs "store keeper" (via substring), "admin" vs "administrator".
     */
    private function wordsFuzzyMatch(array $wordsA, array $wordsB) {
        foreach ($wordsA as $wa) {
            $found = false;
            foreach ($wordsB as $wb) {
                if ($wa === $wb || strpos($wa, $wb) !== false || strpos($wb, $wa) !== false) {
                    $found = true;
                    break;
                }
            }
            if (!$found) return false;
        }
        return true;
    }

    public function positionMatchesRole($requestedPosition, $userRole) {
        $reqWords  = $this->getRoleKeywords($this->resolveOrganogramPosition($requestedPosition));
        $roleWords = $this->getRoleKeywords($userRole);
        if (empty($reqWords) || empty($roleWords)) return false;
        $allReqInRole = $this->wordsFuzzyMatch($reqWords, $roleWords);
        $allRoleInReq = $this->wordsFuzzyMatch($roleWords, $reqWords);
        return $allReqInRole || $allRoleInReq;
    }

    /**
     * Check if a role is allowed by a list of role keywords.
     * Uses fuzzy word matching (handles singular/plural, compound words).
     */
    public function roleMatchesKeywords($role, array $keywords) {
        $roleWords = $this->getRoleKeywords($role);
        if (empty($roleWords)) return false;
        foreach ($keywords as $keyword) {
            $kwWords = $this->getRoleKeywords($keyword);
            if (empty($kwWords)) continue;
            if ($this->wordsFuzzyMatch($kwWords, $roleWords)) {
                return true;
            }
        }
        return false;
    }

    public function getStaffEmailForRole($roleName) {
        $roleName = $this->resolveOrganogramPosition($roleName);
        try {
            $conn = getStaffConnection();
            if (!$conn) return null;
            // Try with status column first, fall back to is_active
            // Intentional @: staff_roles JOIN may fail if table doesn't exist — email lookup is non-critical
            $s = @$conn->prepare("SELECT s.email FROM staff s INNER JOIN staff_roles sr ON s.role_id=sr.id WHERE sr.role_name=? AND (LOWER(COALESCE(s.status,'active')) = 'active') ORDER BY s.id ASC LIMIT 1");
            if (!$s) {
                // staff_roles JOIN may fail — try direct query (Intentional @: status column may not exist)
                $s = @$conn->prepare("SELECT email FROM staff WHERE LOWER(COALESCE(status,'active'))='active' LIMIT 1");
                if (!$s) return null;
                if (!$s->execute()) { error_log('$s execute failed: ' . ($s->error ?? 'unknown')); };
                $row = $s->get_result()->fetch_assoc();
                $s->close();
                return $row['email'] ?? null;
            }
            $s->bind_param('s', $roleName);
            if (!$s->execute()) { error_log('$s execute failed: ' . ($s->error ?? 'unknown')); };
            $row = $s->get_result()->fetch_assoc();
            $s->close();
            return $row['email'] ?? null;
        } catch (\Throwable $e) {
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
            'sickbay'                        => 'dashboards/sickbay.php',
            'store keeper'                   => 'dashboards/storekeeper.php',
            'driver'                         => 'dashboards/drivers.php',
            'skills lab technician'          => 'dashboards/skills-lab.php',
            'system administrator'           => 'dashboards/system-admin.php',
            'system admin'                   => 'dashboards/system-admin.php',
            'events coordinator'             => 'dashboards/events-manager.php',
            'events manager'                 => 'dashboards/events-manager.php',
            'events'                         => 'dashboards/events-manager.php',
            'alumni relations officer'       => 'dashboards/alumni-manager.php',
            'alumni officer'                 => 'dashboards/alumni-manager.php',
            'alumni'                         => 'dashboards/alumni-manager.php',
        ];
        return $map[$key] ?? null;
    }

    public function getDashboardRoute($role) {
        $roleName = trim($role);
        if ($roleName !== '') {
            try {
                $conn = getStaffConnection();
                if ($conn) {
                    // Try querying dashboard_path — column may not exist
                    // Intentional @: dashboard_path column may not exist on older schemas
                    $s = @$conn->prepare("SELECT dashboard_path FROM staff_roles WHERE role_name = ? LIMIT 1");
                    if ($s && $s->bind_param('s', $roleName) && $s->execute()) {
                        $row = $s->get_result()->fetch_assoc();
                        $s->close();
                        if (!empty($row['dashboard_path'])) return ltrim($row['dashboard_path'], '/');
                    } elseif ($s) { $s->close(); }
                    $resolved = $this->resolveOrganogramPosition($roleName);
                    if ($resolved !== $roleName) {
                        // Intentional @: same reason — dashboard_path column may not exist
                        $s2 = @$conn->prepare("SELECT dashboard_path FROM staff_roles WHERE role_name = ? LIMIT 1");
                        if ($s2 && $s2->bind_param('s', $resolved) && $s2->execute()) {
                            $row2 = $s2->get_result()->fetch_assoc();
                            $s2->close();
                            if (!empty($row2['dashboard_path'])) return ltrim($row2['dashboard_path'], '/');
                        } elseif ($s2) { $s2->close(); }
                    }
                }
            } catch (\Throwable $e) { error_log('getDashboardRoute DB: ' . $e->getMessage()); }
        }
        $exact = $this->getDashboardRouteFromKey($role);
        if ($exact) return $exact;

        // Partial fallback: any role containing "admissions" â†’ director-admissions.php
        $normalized = $this->normalizeRoleKey($role);
        if (strpos($normalized, 'admissions') !== false) {
            return 'dashboards/director-admissions.php';
        }

        return 'dashboards/non-teaching-staff.php';
    }

    public function canSearchStudentProfiles($role) {
        if ($this->hasFullInstitutionAccess($role)) return true;
        $r = $this->normalizeRoleKey($role);
        $allowed = ['director academics','director finance','director ict','computer department','ict officer','computer lab manager','skills lab manager','skills lab','school principal','deputy principal','academic registrar','hr manager','school secretary','school bursar','bursar','head nursing','head midwifery','head of nursing','head of midwifery','senior lecturers','lecturers','school librarian','matrons','wardens','sickbay','non teaching staff','events coordinator','events manager','alumni relations officer','alumni officer'];
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
            error_log('createStudentAccount: Database connection failed');
            return ['success' => false, 'message' => 'Database unavailable. Please try again later.'];
        }

        $chk = $conn->prepare("SELECT id FROM students WHERE index_number = ? LIMIT 1");
        if ($chk) { $chk->bind_param('s', $index); if (!$chk->execute()) { error_log('$chk execute failed: ' . ($chk->error ?? 'unknown')); }; if ($chk->get_result()->num_rows > 0) { $chk->close(); return ['success' => false, 'message' => 'A student with this index number already exists.']; } $chk->close(); }

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
            } catch (\Throwable $e) { error_log('Student data loader: ' . $e->getMessage()); }
        }

        [$firstName, $surname] = $this->splitFullName($fullName);
        if (empty($firstName) || empty($surname))
            return ['success' => false, 'message' => 'Please provide a valid full name with at least two names.'];

        try {
            $s = $conn->prepare("INSERT INTO students (student_number,index_number,first_name,surname,other_name,phone,email,program,level,set_name,intake_year,intake_period,status,is_first_login,password_changed,created_at,updated_at) VALUES (?,?,?,?,'',?,?,?,?,?,?,?,'Active',TRUE,FALSE,NOW(),NOW())");
            if (!$s) return ['success' => false, 'message' => 'Failed to prepare student account creation.'];
            $s->bind_param('sssssssssss', $index, $index, $firstName, $surname, $phone, $email, $program, $level, $setName, $intakeYear, $intakePeriod);
            if (!$s->execute()) { error_log('$s execute failed: ' . ($s->error ?? 'unknown')); };
            return ['success' => true, 'message' => 'Student account created successfully', 'data' => ['index_number' => $index, 'full_name' => $fullName, 'set' => $setName, 'program' => $program, 'level' => $level]];
        } catch (\Throwable $e) {
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
            error_log('createStaffAccount: Database connection failed');
            return ['success' => false, 'message' => 'Database unavailable. Please try again later.'];
        }
        try {
            $staffData['full_name']  = sanitizeInput($staffData['full_name']);
            $staffData['email']      = sanitizeInput($staffData['email']);
            $staffData['phone']      = sanitizeInput($staffData['phone']);
            $staffData['position']   = sanitizeInput($staffData['position']);
            $staffData['department'] = sanitizeInput($staffData['department']);

            $hash  = password_hash($staffData['password'], PASSWORD_BCRYPT);
            $rs    = $conn->prepare("SELECT id FROM staff_roles WHERE role_name = ?");
            $rs->bind_param('s', $staffData['role']);
            if (!$rs->execute()) { error_log('$rs execute failed: ' . ($rs->error ?? 'unknown')); };
            $rrow  = $rs->get_result()->fetch_assoc();
            $rs->close();
            if (!$rrow) return ['success' => false, 'message' => 'Invalid role specified'];
            $rid   = $rrow['id'];
            $s     = $conn->prepare("INSERT INTO staff (full_name,email,phone,password,role_id,position,department,status,created_at) VALUES (?,?,?,?,?,?,?,'Active',NOW())");
            $s->bind_param('ssssiss', $staffData['full_name'], $staffData['email'], $staffData['phone'], $hash, $rid, $staffData['position'], $staffData['department']);
            if (!$s->execute()) { error_log('$s execute failed: ' . ($s->error ?? 'unknown')); };
            return ['success' => true, 'message' => 'Staff account created successfully'];
        } catch (\Throwable $e) {
            error_log('createStaffAccount error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to create staff account. Please try again or contact an administrator.'];
        }
    }
}

$auth_service = new AuthenticationService();

// â”€â”€ Lightweight session activity ping â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
if (!empty($_GET['ajax']) && $_GET['ajax'] === 'ping_activity' && $auth_service->isAuthenticated()) {
    $auth_service->checkAndLockSession();
    header('Content-Type: application/json');
    echo '{"ok":true,"t":' . time() . '}';
    exit();
}
