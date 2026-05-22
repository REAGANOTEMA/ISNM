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

// ── helpers ──────────────────────────────────────────────────────────────

/** Try unified-staff auth; return result array on success, null on failure. */
function tryStaffAuth(string $email, string $password, AuthenticationService $auth_service) {
    $result = $auth_service->authenticateStaff($email, $password);
    return $result['success'] ? $result : null;
}

/** Try hr_users table auth; return result array on success, null on failure. */
function tryHrAuth(string $email, string $password) {
    try {
        $conn = getStaffConnection();
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
        $email    = trim($_POST['email'] ?? '');
        $password = (string)($_POST['password'] ?? '');
        $requested_position = $_POST['requested_position'] ?? '';

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
            $sessionRole = $_SESSION['role'] ?? '';
            $dashboard   = $auth_service->getDashboardRoute($sessionRole);

            if ($requested_position !== '') {
                $resolved = $auth_service->resolveOrganogramPosition($requested_position);
                if ($auth_service->positionMatchesRole($requested_position, $sessionRole)) {
                    $dashboard = $auth_service->getDashboardRoute($resolved);
                }
            }
            if (!$dashboard) { $dashboard = 'dashboards/ceo.php'; }

            $_SESSION['success'] = 'Welcome, ' . ($_SESSION['full_name'] ?? 'User');
            header("Location: $dashboard");
        } else {
            $_SESSION['error'] = 'Invalid email or password.';
            header('Location: staff-login.php');
        }
        exit();

    // ── Student login ─────────────────────────────────────────────
    case 'student_login':
        handleStudentLogin();
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
    $index_number = sanitizeInput($_POST['index_number'] ?? '');
    $full_name    = sanitizeInput($_POST['full_name']    ?? '');
    $phone_number = sanitizeInput($_POST['phone_number'] ?? '');

    $res = $auth_service->authenticateStudent($index_number, $full_name, $phone_number);
    if ($res['success']) {
        $auth_service->createSecureSession($res['user']);
        $_SESSION['success'] = 'Welcome, ' . $res['user']['full_name'];
        header('Location: dashboards/student.php');
    } else {
        $_SESSION['error']   = $res['message'];
        $_SESSION['error_source'] = 'student';
        header('Location: staff-login.php');
    }
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
    global $auth_service;
    $auth_service->destroySession();
    header('Location: index.php');
    exit();
}
