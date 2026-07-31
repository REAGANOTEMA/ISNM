<?php
require_once __DIR__ . '/../config/database.php';

$conn = getStudentsConnection();

if (!$conn) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'ERROR', 'message' => 'Database connection failed']);
    exit();
}

// ── Security middleware for legacy assets/ endpoints ──
// Provides basic session authentication and CSRF protection.
// GET requests for data fetching are allowed with just session auth.
// POST/DELETE/UPDATE requests require CSRF token.

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// Require authentication for all requests
if (empty($_SESSION['uid']) && empty($_SESSION['user_id']) && empty($_SESSION['student_id'])) {
    header('Content-Type: application/json');
    http_response_code(401);
    echo json_encode(['status' => 'ERROR', 'message' => 'Authentication required', 'error' => 'Unauthorized']);
    exit();
}

// CSRF protection for state-changing requests
if (in_array($method, ['POST', 'PUT', 'DELETE'])) {
    $csrfToken = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    $csrfOk = false;

    if (!empty($csrfToken) && !empty($_SESSION['csrf_token'])) {
        $csrfOk = hash_equals($_SESSION['csrf_token'], $csrfToken);
    } else {
        // No token submitted: fall back to same-origin verification so legacy
        // same-site forms keep working while cross-site (CSRF) calls are blocked.
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
        $referer = $_SERVER['HTTP_REFERER'] ?? '';
        $host = $_SERVER['HTTP_HOST'] ?? '';
        if ($origin !== '') {
            $originHost = parse_url($origin, PHP_URL_HOST);
            $csrfOk = ($originHost !== null && strcasecmp($originHost, $host) === 0);
        } elseif ($referer !== '') {
            $refHost = parse_url($referer, PHP_URL_HOST);
            $csrfOk = ($refHost !== null && strcasecmp($refHost, $host) === 0);
        }
    }

    if (!$csrfOk) {
        header('Content-Type: application/json');
        http_response_code(403);
        echo json_encode(['status' => 'ERROR', 'message' => 'Invalid security token']);
        exit();
    }
}

// ── Role helpers for legacy assets/ endpoints ──
if (!function_exists('isnm_current_actor_role')) {
    function isnm_current_actor_role() {
        $uid = $_SESSION['uid'] ?? $_SESSION['user_id'] ?? null;
        if (!$uid) {
            return '';
        }
        $staffConn = getStaffConnection();
        if (!$staffConn) {
            return '';
        }
        $stmt = $staffConn->prepare('SELECT LOWER(sr.role_name) AS role_name
                FROM `igangaschool_staffs`.`staff` s
                INNER JOIN `igangaschool_staffs`.`staff_roles` sr ON s.role_id = sr.id
                WHERE s.id = ? LIMIT 1');
        if (!$stmt) {
            return '';
        }
        $stmt->bind_param('i', $uid);
        if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return strtolower(trim($row['role_name'] ?? ''));
    }
}

if (!function_exists('isnm_is_staff_actor')) {
    function isnm_is_staff_actor(): bool {
        return isnm_current_actor_role() !== '';
    }
}

if (!function_exists('isnm_require_staff_role')) {
    function isnm_require_staff_role(): void {
        if (!isnm_is_staff_actor()) {
            header('Content-Type: application/json');
            http_response_code(403);
            echo json_encode(['status' => 'ERROR', 'message' => 'Access denied: staff role required']);
            exit();
        }
    }
}

if (!function_exists('isnm_require_admin_role')) {
    function isnm_require_admin_role(): void {
        $role = isnm_current_actor_role();
        $isAdmin = in_array($role, ['system administrator', 'admin', 'owner', 'super admin', 'director general', 'ceo', 'principal', 'school principal', 'deputy principal'], true)
            || (strpos($role, 'admin') !== false)
            || (strpos($role, 'director') !== false);
        if (!$isAdmin) {
            header('Content-Type: application/json');
            http_response_code(403);
            echo json_encode(['status' => 'ERROR', 'message' => 'Access denied: administrator role required']);
            exit();
        }
    }
}
?>
