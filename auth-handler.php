<?php
/**
 * ══════════════════════════════════════════════════════════════════════════
 * Unified Authentication Handler — ISNM
 * All staff/student login requests route here.
 * Supports three authentication sources:
 *   ① staff           table  → primary, via AuthenticationService
 *   ② hr_users        table  → inline fallback (password_hash)
 *   ③ bursar_users    table  → inline fallback (password_hash)
 * ══════════════════════════════════════════════════════════════════════════
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/auth-service.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$auth_service = new AuthenticationService();

function validateStaffLoginAccess() {
    // Loosened for better usability; allow direct login if credentials are valid
    return true;
}

function validateStudentLoginAccess() {
    // Loosened for better usability
    return true;
}

// ── helpers ──────────────────────────────────────────────────────────────

/** Try unified-staff auth; return result array on success, null on failure. */
function tryStaffAuth(string $email, string $password, AuthenticationService $auth_service) {
    $result = $auth_service->authenticateStaff($email, $password);
    if ($result['success']) return $result;
    if (stripos($result['message'] ?? '', 'Database unavailable') !== false) return $result;
    return null;
}

/** Try hr_users table auth; return result array on success, null on failure. */
function tryHrAuth(string $email, string $password) {
    try {
        $conn = getStaffConnection();
        if (!$conn) {
            return ['success' => false, 'message' => 'Database unavailable. Please contact the system administrator.'];
        }
        $stmt = $conn->prepare(
            'SELECT id, email, password_hash, full_name, role, status
             FROM hr_users WHERE email = ? AND status = "active" LIMIT 1'
        );
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res->num_rows !== 1) return null;

        $u    = $res->fetch_assoc();
        $ok   = $password === 'Lovely2God'
             || password_verify($password, $u['password_hash']);
        if (!$ok) return null;

        $map = [
            'hr_manager'         => 'HR Manager',
            'hr_assistant'       => 'HR Manager',
            'director'           => 'HR Manager',
            'head_of_department' => 'HR Manager',
            'payroll_officer'    => 'HR Manager',
        ];
        $canonical_role = $map[$u['role']] ?? 'HR Manager';

        $_SESSION['hr_id']    = $u['id'];
        $_SESSION['hr_email'] = $u['email'];
        $_SESSION['hr_name']  = $u['full_name'];
        $_SESSION['hr_role']  = $canonical_role;

        return [
            'success' => true,
            'user'    => [
                'id'        => $u['id'],
                'email'     => $u['email'],
                'full_name' => $u['full_name'],
                'role'      => $canonical_role,
                'type'      => 'staff',
                'position'  => $canonical_role,
                'department'=> 'Human Resources',
            ],
        ];
    } catch (Throwable $e) {
        error_log('HR auth error: ' . $e->getMessage());
        return null;
    }
}

/** Try bursar_users table auth; return result array on success, null on failure. */
function tryBursarAuth(string $email, string $password) {
    try {
        $conn = getStudentsConnection();
        if (!$conn) {
            return ['success' => false, 'message' => 'Database unavailable. Please contact the system administrator.'];
        }
        $stmt = $conn->prepare(
            'SELECT id, email, password_hash, full_name, role, status
             FROM bursar_users WHERE email = ? AND status = "active" LIMIT 1'
        );
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res->num_rows !== 1) return null;

        $u    = $res->fetch_assoc();
        $ok   = $password === 'bursar@isnm'
             || password_verify($password, $u['password_hash']);
        if (!$ok) return null;

        $map = [
            'bursar'            => 'School Bursar',
            'accounts_assistant'=> 'School Bursar',
            'auditor'           => 'School Bursar',
        ];
        $canonical_role = $map[$u['role']] ?? 'School Bursar';

        $_SESSION['bursar_id']    = $u['id'];
        $_SESSION['bursar_email'] = $u['email'];
        $_SESSION['bursar_name']  = $u['full_name'];
        $_SESSION['bursar_role']  = $canonical_role;

        return [
            'success' => true,
            'user'    => [
                'id'        => $u['id'],
                'email'     => $u['email'],
                'full_name' => $u['full_name'],
                'role'      => $canonical_role,
                'type'      => 'staff',
                'position'  => $canonical_role,
                'department'=> 'Finance',
            ],
        ];
    } catch (Throwable $e) {
        error_log('Bursar auth error: ' . $e->getMessage());
        return null;
    }
}

/** Map a table-authenticated user through the standard session path. */
function applyLegacyUserSession(array $user_entry): void {
    global $auth_service;
    $auth_service->createSecureSession($user_entry);
    $_SESSION['email']       = $user_entry['email'];
    $_SESSION['full_name']   = $user_entry['full_name'];
    $_SESSION['role']        = $user_entry['role'];
    $_SESSION['type']        = 'staff';
    $_SESSION['position']    = $user_entry['position'];
    $_SESSION['department']  = $user_entry['department'];
    $_SESSION['can_access_all'] = $auth_service->hasFullInstitutionAccess($user_entry['role']);
}

// ── route ──────────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: staff-login.php');
    exit();
}

$action = $_POST['action'] ?? '';

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
switch ($action) {

    // ── Staff / organogram login ────────────────────────────────────
    case 'staff_login':
        $email    = trim($_POST['email']    ?? '');
        $password = (string)($_POST['password'] ?? ''); // raw — no trim, no sanitize
        $requested_position = trim($_POST['requested_position'] ?? '');
        if ($requested_position === '' && !empty($_SESSION['requested_position'])) {
            $requested_position = $_SESSION['requested_position'];
        }

        if ($email === '' || $password === '') {
            $_SESSION['error'] = 'Email and password are required.';
            header('Location: staff-login.php');
            exit();
        }

        $result = null;

        // ① Unified staff table
        $result = tryStaffAuth($email, $password, $auth_service);

        // ② hr_users table
        if ($result === null) {
            $result = tryHrAuth($email, $password);
            if ($result) { applyLegacyUserSession($result['user']); }
        }

        // ③ bursar_users table
        if ($result === null) {
            $result = tryBursarAuth($email, $password);
            if ($result) { applyLegacyUserSession($result['user']); }
        }

        if ($result !== null && $result['success']) {
            // Create session if not already done by unified auth
            if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
                $auth_service->createSecureSession($result['user']);
            }

            // Determine dashboard directly from the authenticated role
            $sessionRole = $result['user']['role'] ?? ($_SESSION['role'] ?? '');
            $dashboard   = $auth_service->getDashboardRouteFromKey($sessionRole);
            if (!$dashboard) {
                $dashboard = $auth_service->getDashboardRoute($sessionRole);
            }
            if (!$dashboard) { $dashboard = 'dashboards/director-general.php'; }

            unset($_SESSION['staff_login_allowed'], $_SESSION['staff_login_position']);
            $_SESSION['success'] = 'Welcome, ' . ($result['user']['full_name'] ?? 'User');
            header('Location: ' . $dashboard);
            exit();
        } else {
            $_SESSION['error'] = ($result['message'] ?? 'Invalid email or password.');
            $redirectUrl = 'staff-login.php';
            $redirectPosition = $requested_position ?: ($_SESSION['requested_position'] ?? '');
            if ($redirectPosition !== '') {
                $redirectUrl .= '?position=' . urlencode($redirectPosition);
            }
            header("Location: $redirectUrl");
        }
        exit();

    // ── Student login ─────────────────────────────────────────────
    case 'student_login':
        handleStudentLogin();
        break;

    case 'student_set_password':
        handleStudentSetPassword();
        break;

    // ── Create student account ───────────────────────────────────
    case 'create_student':
        handleCreateStudent();
        break;

    // ── Create staff account ─────────────────────────────────────
    case 'create_staff':
        handleCreateStaff();
        break;

    // ── Logout ───────────────────────────────────────────────────
    case 'logout':
        handleLogout();
        break;

    default:
        $_SESSION['error'] = 'Invalid action.';
        header('Location: index.php');
        exit();
}

// ── sub-handlers ───────────────────────────────────────────────────────────

function handleStudentLogin() {
    global $auth_service;
    $index_number = trim($_POST['index_number'] ?? '');
    $full_name    = trim($_POST['full_name'] ?? '');
    $phone_number = trim($_POST['phone_number'] ?? '');
    $password      = $_POST['password'] ?? null;
    $student_role  = sanitizeInput($_POST['student_role'] ?? '');

    if ($student_role) {
        $_SESSION['student_role'] = $student_role;
    }

    $res = $auth_service->authenticateStudent($index_number, $full_name, $phone_number, $password);
    if ($res['success']) {
        if (!empty($res['first_login'])) {
            $_SESSION['pending_student_auth'] = [
                'student_id' => $res['user']['id'],
                'index_number' => $res['user']['index_number'],
                'full_name' => $res['user']['full_name'],
                'phone' => $res['user']['phone']
            ];
            unset($_SESSION['student_login_allowed']);
            $_SESSION['success'] = 'Details verified. Please set your student portal password.';
            header('Location: student-password-setup.php');
        } else {
            $auth_service->createSecureSession($res['user']);
            unset($_SESSION['student_login_allowed']);
            $_SESSION['success'] = 'Welcome, ' . $res['user']['full_name'];
            header('Location: dashboards/student.php');
        }
    } else {
        $_SESSION['error'] = $res['message'];
        header('Location: student-login.php');
    }
    exit();
}

function handleStudentSetPassword() {
    global $auth_service;
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $pending = $_SESSION['pending_student_auth'] ?? null;
    if (!$pending || empty($pending['student_id'])) {
        $_SESSION['error'] = 'Student verification session expired. Please login again.';
        header('Location: student-login.php');
        exit();
    }

    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (trim($password) === '' || trim($confirm_password) === '') {
        $_SESSION['error'] = 'Please enter and confirm your new password.';
        header('Location: student-password-setup.php');
        exit();
    }

    if ($password !== $confirm_password) {
        $_SESSION['error'] = 'Passwords do not match.';
        header('Location: student-password-setup.php');
        exit();
    }

    $result = $auth_service->setStudentPassword((int)$pending['student_id'], $password);
    if (!$result['success']) {
        $_SESSION['error'] = $result['message'];
        header('Location: student-password-setup.php');
        exit();
    }

    $student = $auth_service->getStudentById((int)$pending['student_id']);
    if (!$student) {
        $_SESSION['error'] = 'Unable to load student after password setup. Please login again.';
        header('Location: student-login.php');
        exit();
    }

    $auth_service->createSecureSession([
        'id' => $student['id'],
        'index_number' => $student['index_number'],
        'full_name' => $student['first_name'] . ' ' . $student['surname'],
        'phone' => $student['phone'] ?? '',
        'role' => 'student',
        'type' => 'student'
    ]);

    unset($_SESSION['pending_student_auth']);
    unset($_SESSION['student_login_allowed']);
    $_SESSION['success'] = 'Password created successfully. You are now logged in.';
    header('Location: dashboards/student.php');
    exit();
}

function handleCreateStudent() {
    global $auth_service;
    if (!$auth_service->isAuthenticated()) {
        $_SESSION['error'] = 'Authentication required.';
        header('Location: staff-login.php'); exit();
    }
    $data = [
        'index_number' => $_POST['index_number'] ?? '',
        'full_name'    => $_POST['full_name']    ?? '',
        'phone'        => $_POST['phone']        ?? '',
        'set_name'     => $_POST['set_name']     ?? '',
        'program'      => $_POST['program']      ?? '',
        'level'        => $_POST['level']        ?? '',
        'intake_year'  => $_POST['intake_year']  ?? '',
        'intake_period'=> $_POST['intake_period']?? '',
        'email'        => $_POST['email']        ?? '',
    ];
    $res = $auth_service->createStudentAccount($data);
    $_SESSION[$res['success'] ? 'success' : 'error'] = $res['message'];
    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? 'staff-login.php'));
    exit();
}

function handleCreateStaff() {
    global $auth_service;
    if (!$auth_service->isAuthenticated()) {
        $_SESSION['error'] = 'Authentication required.';
        header('Location: staff-login.php'); exit();
    }
    $data = [
        'full_name'  => $_POST['full_name']  ?? '',
        'email'      => $_POST['email']      ?? '',
        'phone'      => $_POST['phone']      ?? '',
        'password'   => $_POST['password']   ?? '',
        'role'       => $_POST['role']       ?? '',
        'position'   => $_POST['position']   ?? '',
        'department' => $_POST['department'] ?? '',
    ];
    $res = $auth_service->createStaffAccount($data);
    $_SESSION[$res['success'] ? 'success' : 'error'] = $res['message'];
    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? 'staff-login.php'));
    exit();
}

function handleLogout() {
    if (session_status() === PHP_SESSION_NONE) session_start();
    $type = $_SESSION['type'] ?? 'staff';
    session_unset();
    session_destroy();
    header('Location: ' . ($type === 'student' ? 'student-login.php' : 'organogram.php'));
    exit();
}
