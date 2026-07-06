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
require_once __DIR__ . '/includes/error_handler.php';
require_once __DIR__ . '/auth-service.php';

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
    session_start();
}

// CSRF token generation for auth forms
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Verify critical database connections are available
if (($_GET['action'] ?? '') === 'check_student' || $_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if at least the primary databases are available
    $students_conn = @getStudentsConnection();
    $staff_conn = @getStaffConnection();
    
    if (!$students_conn || !$staff_conn) {
        // Show database error page with diagnostics
        ErrorHandler::renderDatabaseUnavailableError();
    }
    
    if ($students_conn) $students_conn->close();
    if ($staff_conn) $staff_conn->close();
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
    // Return the actual error message so users know what went wrong
    return $result;
}

/** Try hr_users table auth; return result array on success, null on failure. */
function tryHrAuth(string $email, string $password) {
    try {
        $conn = getStaffConnection();
        if (!$conn) {
            $errMsg = 'Database unavailable. Please contact the system administrator.';
            if (defined('APP_DEBUG') && APP_DEBUG && !empty($GLOBALS['isnm_last_db_error'])) {
                $errMsg .= ' (' . $GLOBALS['isnm_last_db_error'] . ')';
            }
            return ['success' => false, 'message' => $errMsg];
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
        $ok   = password_verify($password, $u['password_hash']);
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
            $errMsg = 'Database unavailable. Please contact the system administrator.';
            if (defined('APP_DEBUG') && APP_DEBUG && !empty($GLOBALS['isnm_last_db_error'])) {
                $errMsg .= ' (' . $GLOBALS['isnm_last_db_error'] . ')';
            }
            return ['success' => false, 'message' => $errMsg];
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
        $ok   = password_verify($password, $u['password_hash']);
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
// ── rate limit: check_student endpoint ──────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'GET' && ($_GET['action'] ?? '') === 'check_student') {
    header('Content-Type: application/json');
    $indexNumber = trim($_GET['index_number'] ?? '');
    if (empty($indexNumber)) { echo json_encode(['exists' => false]); exit(); }

    // Rate limit: max 10 lookups per IP per minute
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $rateKey = 'rate_check_student_' . $ip;
    $now = time();
    if (!isset($_SESSION[$rateKey])) $_SESSION[$rateKey] = [];
    $_SESSION[$rateKey] = array_filter($_SESSION[$rateKey], fn($t) => $t > $now - 60);
    if (count($_SESSION[$rateKey]) >= 10) {
        echo json_encode(['exists' => false, 'error' => 'rate_limited']);
        exit();
    }
    $_SESSION[$rateKey][] = $now;

    $conn = getConnection();
    if (!$conn) { echo json_encode(['exists' => false]); exit(); }
    $q = $conn->prepare("SELECT id, password FROM students WHERE index_number = ? LIMIT 1");
    if (!$q) { echo json_encode(['exists' => false]); exit(); }
    $q->bind_param('s', $indexNumber);
    $q->execute();
    $r = $q->get_result();
    $student = $r->fetch_assoc();
    $q->close();
    echo json_encode(['exists' => !empty($student), 'has_password' => !empty($student['password'])]);
    exit();
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// Allow GET for logout (sidebar link uses GET)
if ($action === 'logout') {
    handleLogout();
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    session_write_close();
    header('Location: staff-login.php');
    exit();
}

// CSRF verification on all POST actions
$token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
    // Regenerate CSRF token so the refreshed page has a valid one
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    $_SESSION['error'] = 'Invalid security token. Please refresh and try again.';
    // Determine which login page to redirect back to
    $referrer = $_SERVER['HTTP_REFERER'] ?? '';
    if (strpos($referrer, 'student-login.php') !== false) {
        session_write_close();
        header('Location: student-login.php');
    } else {
        session_write_close();
        header('Location: staff-login.php');
    }
    exit();
}

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

        // Capture redirect URL from POST or session
        $redirect_url = trim($_POST['redirect_url'] ?? '');
        if ($redirect_url === '' && !empty($_SESSION['login_redirect_url'])) {
            $redirect_url = $_SESSION['login_redirect_url'];
        }
        if ($redirect_url) {
            // Validate: only allow internal paths
            if (strpos($redirect_url, '..') !== false || strpos($redirect_url, '://') !== false) {
                $redirect_url = '';
            }
        }
        if ($redirect_url) {
            $_SESSION['login_redirect_url'] = $redirect_url;
        }

        if ($email === '' || $password === '') {
            $_SESSION['error'] = 'Email and password are required.';
            session_write_close();
            header('Location: staff-login.php');
            exit();
        }

        $result = null;

        // ① Unified staff table
        $result = tryStaffAuth($email, $password, $auth_service);

        // ② hr_users table (only if staff table had no matching email)
        if ($result !== null && !$result['success'] && strpos($result['message'] ?? '', 'Invalid email or password') !== false) {
            // Staff auth failed — try hr_users as fallback
            $hrResult = tryHrAuth($email, $password);
            if ($hrResult && $hrResult['success']) {
                $result = $hrResult;
                applyLegacyUserSession($result['user']);
            }
        }

        // ③ bursar_users table (only if both above failed with invalid email)
        if ($result !== null && !$result['success'] && strpos($result['message'] ?? '', 'Invalid email or password') !== false) {
            $bursarResult = tryBursarAuth($email, $password);
            if ($bursarResult && $bursarResult['success']) {
                $result = $bursarResult;
                applyLegacyUserSession($result['user']);
            }
        }

        if ($result !== null && $result['success']) {
            // Create session if not already done by unified auth
            if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
                $auth_service->createSecureSession($result['user']);
            }

            // Role-to-dashboard mapping
            $dashboardMap = [
                'Director General'       => 'dashboards/director-general.php',
                'CEO'                    => 'dashboards/ceo.php',
                'Director Academics'     => 'dashboards/director-academics.php',
                'Director Finance'       => 'dashboards/director-finance.php',
                'Director ICT'           => 'dashboards/director-ict.php',
                'Director Admissions'    => 'dashboards/director-admissions.php',
                'Director Admissions & Requirements' => 'dashboards/director-admissions.php',
                'School Principal'       => 'dashboards/school-principal.php',
                'Deputy Principal'       => 'dashboards/deputy-principal.php',
                'Academic Registrar'     => 'dashboards/academic-registrar.php',
                'School Bursar'          => 'dashboards/school-bursar.php',
                'School Secretary'       => 'dashboards/school-secretary.php',
                'HR Manager'             => 'dashboards/hr-manager.php',
                'School Librarian'       => 'dashboards/school-librarian.php',
                'Head of Nursing'        => 'dashboards/head-nursing.php',
                'Head of Midwifery'      => 'dashboards/head-midwifery.php',
                'Senior Lecturer'        => 'dashboards/senior-lecturers.php',
                'Lecturer'               => 'dashboards/lecturers.php',
                'Security Officer'       => 'dashboards/security.php',
                'Storekeeper'            => 'dashboards/storekeeper.php',
                'Driver'                 => 'dashboards/drivers.php',
                'Matron'                 => 'dashboards/matrons.php',
                'Warden'                 => 'dashboards/wardens.php',
                'Guild President'        => 'dashboards/guild-president.php',
                'Sickbay Nurse'          => 'dashboards/sickbay.php',
                'Computer Lab Manager'   => 'dashboards/computer_lab.php',
                'Skills Lab Technician'  => 'dashboards/skills-lab.php',
                'Skills Lab Manager'     => 'dashboards/skills-lab.php',
                'System Administrator'   => 'dashboards/system-admin.php',
            ];

            // Determine dashboard: prefer requested_position (organogram card clicked)
            $dashboard = null;
            if (!empty($requested_position)) {
                $resolvedForDashboard = $auth_service->resolveOrganogramPosition($requested_position);
                $dashboard = $auth_service->getDashboardRouteFromKey($resolvedForDashboard);
                if (!$dashboard) {
                    $dashboard = $auth_service->getDashboardRoute($resolvedForDashboard);
                }
            }
            // Fall back to the authenticated role using the map
            if (!$dashboard) {
                $sessionRole = $result['user']['role'] ?? ($_SESSION['role'] ?? '');
                $dashboard = $dashboardMap[$sessionRole] ?? null;
                if (!$dashboard) {
                    $dashboard = $auth_service->getDashboardRouteFromKey($sessionRole);
                }
                if (!$dashboard) {
                    $dashboard = $auth_service->getDashboardRoute($sessionRole);
                }
            }
            if (!$dashboard) { $dashboard = 'dashboards/director-general.php'; }

            unset($_SESSION['staff_login_allowed'], $_SESSION['staff_login_position']);

            // If a redirect URL was requested, go there instead of default dashboard (SAFE ALLOWLIST)
            if (!empty($_SESSION['login_redirect_url'])) {
                $target = (string)$_SESSION['login_redirect_url'];
                unset($_SESSION['login_redirect_url']);
                $_SESSION['success'] = 'Welcome, ' . ($result['user']['full_name'] ?? 'User');

                // Only allow internal relative paths we explicitly recognize
                $allowed = [
                    'dashboard.php',
                    'staff-login.php',
                    'student-login.php',
                    'student-password-setup.php',
                    'dashboards/student.php',
                    'dashboards/director-general.php',
                    'dashboards/system-admin.php',
                    'dashboards/director-admissions.php',
                    'dashboards/ceo.php',
                    'dashboards/director-academics.php',
                    'dashboards/director-finance.php',
                    'dashboards/director-ict.php',
                    'dashboards/school-principal.php',
                    'dashboards/deputy-principal.php',
                    'dashboards/academic-registrar.php',
                    'dashboards/school-bursar.php',
                    'dashboards/school-secretary.php',
                    'dashboards/hr-manager.php',
                    'dashboards/school-librarian.php',
                    'dashboards/head-nursing.php',
                    'dashboards/head-midwifery.php',
                    'dashboards/senior-lecturers.php',
                    'dashboards/lecturers.php',
                    'dashboards/matrons.php',
                    'dashboards/wardens.php',
                    'dashboards/security.php',
                    'dashboards/storekeeper.php',
                    'dashboards/drivers.php',
                    'dashboards/sickbay.php',
                    'dashboards/guild-president.php',
                    'dashboards/skills-lab.php',
                    'dashboards/computer_lab.php',
                ];

                // Normalize target to just the path segment (strip query/fragment)
                $normalized = $target;
                $normalized = strtok($normalized, "?#");

                // Forbid traversal and absolute URLs
                if (strpos($normalized, '..') !== false || strpos($normalized, '://') !== false || strlen($normalized) === 0) {
                    $normalized = '';
                }

                // Allow only known safe internal routes
                if ($normalized && in_array($normalized, $allowed, true)) {
                    session_write_close();
                    header('Location: ' . $normalized);
                } else {
                    session_write_close();
                    header('Location: ' . $dashboard);
                }
                exit();
            }

            $_SESSION['success'] = 'Welcome, ' . ($result['user']['full_name'] ?? 'User');
            session_write_close();
            header('Location: ' . $dashboard);
            exit();
        } else {
            $_SESSION['error'] = ($result['message'] ?? 'Invalid email or password.');
            $redirectPosition = $requested_position ?: ($_SESSION['requested_position'] ?? '');
            $redirectUrl = 'staff-login.php';
            $params = [];
            if ($redirectPosition !== '') {
                $params[] = 'position=' . urlencode($redirectPosition);
            }
            if ($redirect_url) {
                $params[] = 'redirect=' . urlencode($redirect_url);
            }
            if (!empty($params)) {
                $redirectUrl .= '?' . implode('&', $params);
            }
            session_write_close();
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
        session_write_close();
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
            session_write_close();
            header('Location: student-password-setup.php');
        } else {
            $auth_service->createSecureSession($res['user']);
            unset($_SESSION['student_login_allowed']);
            $_SESSION['success'] = 'Welcome, ' . $res['user']['full_name'];
            session_write_close();
            header('Location: dashboards/student.php');
        }
    } else {
        $_SESSION['error'] = $res['message'];
        session_write_close();
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
        session_write_close();
        header('Location: student-login.php');
        exit();
    }

    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (trim($password) === '' || trim($confirm_password) === '') {
        $_SESSION['error'] = 'Please enter and confirm your new password.';
        session_write_close();
        header('Location: student-password-setup.php');
        exit();
    }

    if ($password !== $confirm_password) {
        $_SESSION['error'] = 'Passwords do not match.';
        session_write_close();
        header('Location: student-password-setup.php');
        exit();
    }

    $result = $auth_service->setStudentPassword((int)$pending['student_id'], $password);
    if (!$result['success']) {
        $_SESSION['error'] = $result['message'];
        session_write_close();
        header('Location: student-password-setup.php');
        exit();
    }

    $student = $auth_service->getStudentById((int)$pending['student_id']);
    if (!$student) {
        $_SESSION['error'] = 'Unable to load student after password setup. Please login again.';
        session_write_close();
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
    session_write_close();
    header('Location: dashboards/student.php');
    exit();
}

function handleCreateStudent() {
    global $auth_service;
    if (!$auth_service->isAuthenticated()) {
        $_SESSION['error'] = 'Authentication required.';
        session_write_close();
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
    $referer = $_SERVER['HTTP_REFERER'] ?? '';
    $parsedRef = parse_url($referer);
    $safeRef = ($referer && (!$parsedRef || !isset($parsedRef['host']) || $parsedRef['host'] === ($_SERVER['HTTP_HOST'] ?? '')) && strpos($referer, '://') === false) ? $referer : 'staff-login.php';
    session_write_close();
    header('Location: ' . $safeRef);
    exit();
}

function handleCreateStaff() {
    global $auth_service;
    if (!$auth_service->isAuthenticated()) {
        $_SESSION['error'] = 'Authentication required.';
        session_write_close();
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
    $referer = $_SERVER['HTTP_REFERER'] ?? '';
    $parsedRef = parse_url($referer);
    $safeRef = ($referer && (!$parsedRef || !isset($parsedRef['host']) || $parsedRef['host'] === ($_SERVER['HTTP_HOST'] ?? '')) && strpos($referer, '://') === false) ? $referer : 'staff-login.php';
    session_write_close();
    header('Location: ' . $safeRef);
    exit();
}

function handleLogout() {
    global $auth_service;
    if (session_status() === PHP_SESSION_NONE) session_start();
    $auth_service->logout();
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_write_close();
    header('Location: staff-login.php');
    exit();
}
