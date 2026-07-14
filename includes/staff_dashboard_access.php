<?php
// â"€â"€ Buffer all output so headers are never "already sent" before session_start() â"€â"€
if (ob_get_level() === 0) {
    ob_start();
}

// â"€â"€ Production-safe error handling: log all errors but never display them â"€â"€
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
error_reporting(E_ALL);
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/../logs/php_errors.log');

/**
 * Shared staff dashboard authentication and multi-database bootstrap.
 */

// â”€â”€ Fatal error catcher (prevents blank pages on production) â”€â”€
register_shutdown_function(function () {
    $err = error_get_last();
    if ($err && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        $msg = htmlspecialchars($err['message']);
        $file = htmlspecialchars($err['file']);
        $line = (int)$err['line'];
        error_log("FATAL: {$err['message']} in {$err['file']} on line {$err['line']}");
        if (PHP_SAPI === 'cli') {
            fwrite(STDERR, "FATAL: {$err['message']}\n");
            exit(1);
        }
        if (ob_get_level()) ob_clean();
        http_response_code(500);
        echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>System Error</title>';
        echo '<style>body{font-family:-apple-system,BlinkMacSystemFont,Segoe UI,Roboto,sans-serif;background:#fef2f2;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0}';
        echo '.card{background:#fff;border-radius:12px;padding:32px;border:1px solid #fecaca;max-width:600px;text-align:center;box-shadow:0 4px 24px rgba(0,0,0,.08)}';
        echo 'h2{color:#dc2626;margin:0 0 10px}p{color:#64748b;line-height:1.6}a{color:#2563eb;text-decoration:none;font-weight:500}';
        echo '.btn{display:inline-block;padding:10px 24px;background:#1e40af;color:#fff;border-radius:8px;text-decoration:none;font-weight:600;margin-top:12px}</style></head>';
        echo '<body><div class="card"><h2>Internal Server Error</h2>';
        echo '<p>The system encountered an internal error. Our team has been notified.</p>';
        if (defined('APP_DEBUG') && APP_DEBUG) {
            echo '<p style="font-size:12px;color:#999;text-align:left;background:#f8fafc;padding:12px;border-radius:6px;word-break:break-all">';
            echo '<strong>' . $file . ':' . $line . '</strong><br>' . $msg . '</p>';
        }
        $base = (basename(dirname($_SERVER['SCRIPT_NAME'] ?? '')) === 'dashboards') ? '..' : '.';
        echo '<a href="' . $base . '/health-check.php" class="btn">Run Health Check</a>';
        echo '<br><br><a href="' . $base . '/staff-login.php" style="font-size:13px">Back to Login</a>';
        echo '</div></body></html>';
        exit;
    }
});

try {
    require_once __DIR__ . '/../config/database.php';
    require_once __DIR__ . '/../auth-service.php';
    require_once __DIR__ . '/../includes/student_helpers.php';
} catch (Throwable $e) {
    if (ob_get_level()) ob_clean();
    echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>System Error</title>';
    echo '<style>body{font-family:sans-serif;background:#fef2f2;padding:30px;color:#991b1b}</style></head>';
    echo '<body><h2>Missing Required File</h2><p>' . htmlspecialchars($e->getMessage()) . '</p></body></html>';
    exit;
}

if (!function_exists('bootstrapStaffDashboard')) {
    /**
     * @param array<int, string> $roleKeywords Empty = any authenticated staff.
     * @return array{auth: AuthenticationService, staff: mysqli, students: mysqli, website: mysqli, user: array}
     */
    function bootstrapStaffDashboard(array $roleKeywords = []) {
        if (session_status() === PHP_SESSION_NONE) {
            ini_set('session.cookie_path', SESSION_COOKIE_PATH);
            session_start();
        }

        global $auth_service;
        if (!isset($auth_service) || !($auth_service instanceof AuthenticationService)) {
            $auth_service = new AuthenticationService();
        }

        // Login is always ONE level above dashboards/ (at the project root)
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        $loginPath = dirname($scriptName) !== '' && dirname($scriptName) !== '/' ? '../staff-login.php' : 'staff-login.php';

        if (!$auth_service->isAuthenticated() || ($_SESSION['type'] ?? '') !== 'staff') {
            session_write_close();
            header('Location: ' . $loginPath);
            exit();
        }

        // Session timeout enforcement
        if (!$auth_service->checkSessionValidity()) {
            session_write_close();
            header('Location: ' . $loginPath . '?error=expired');
            exit();
        }

        // â”€â”€ Ensure CSRF token exists before validating â”€â”€
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        // â”€â”€ Centralized CSRF validation for all POST requests â”€â”€
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $csrfToken = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
            if (empty($csrfToken) || !isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrfToken)) {
                header('Content-Type: application/json');
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Invalid or missing security token. Please refresh the page.']);
                exit();
            }
        }

        $role = $_SESSION['role'] ?? '';

        // Refresh role from database if session is stale (handles role reassignments)
        if (!empty($_SESSION['user_id'])) {
            static $roleRefreshed = false;
            if (!$roleRefreshed) {
                $roleRefreshed = true;
                try {
                    $sc = getStaffConnection();
                    if ($sc) {
                        $rs = $sc->prepare("SELECT sr.role_name FROM staff s JOIN staff_roles sr ON s.role_id=sr.id WHERE s.id=? LIMIT 1");
                        if ($rs) {
                            $uid = (int)$_SESSION['user_id'];
                            $rs->bind_param('i', $uid);
                            if (!$rs->execute()) { error_log('$rs execute failed: ' . ($rs->error ?? 'unknown')); };
                            $rw = $rs->get_result()->fetch_assoc();
                            $rs->close();
                            if ($rw && !empty($rw['role_name']) && $rw['role_name'] !== $role) {
                                $_SESSION['role'] = $rw['role_name'];
                                $role = $rw['role_name'];
                            }
                        }
                    }
                } catch (Exception $e) { error_log('staff_dashboard_access role refresh: ' . $e->getMessage()); }
            }
        }

        if (!empty($roleKeywords) && !$auth_service->hasFullInstitutionAccess($role)) {
            $allowed = $auth_service->roleMatchesKeywords($role, $roleKeywords);
            if (!$allowed) {
                $userDashboard = $auth_service->getDashboardRoute($role);
                $dashboardFile = $userDashboard ? basename($userDashboard) : '';
                $currentFile = basename($_SERVER['SCRIPT_NAME'] ?? '');
                if ($dashboardFile === $currentFile) {
                    $dashboardFile = 'index.php';
                }
                $redirectUrl = $dashboardFile ?: 'index.php';
                session_write_close();
                header('Location: ' . $redirectUrl);
                exit();
            }
        }

        // Enforce that initial staff login was performed via organogram when present.
        // If the request came from organogram, ensure the position matches the authenticated role.
        if (!empty($_SESSION['logged_in_via_organogram']) && !empty($_SESSION['logged_in_via_position'])) {
            $requestedPos = $_SESSION['logged_in_via_position'];
            if (!$auth_service->positionMatchesRole($requestedPos, $role) && !$auth_service->hasFullInstitutionAccess($role)) {
                session_write_close();
                header('Location: ../staff-login.php');
                exit();
            }
            unset($_SESSION['logged_in_via_organogram']);
            unset($_SESSION['logged_in_via_position']);
        }

        $user = $auth_service->getCurrentUser();
        // Ensure first_name / surname are always available even though the
        // session only stores full_name.
        if (!isset($user['first_name'])) {
            $parts = explode(' ', trim($user['full_name'] ?? 'User'), 2);
            $user['first_name'] = $parts[0];
            $user['surname']    = $parts[1] ?? '';
            $user['last_name']  = $user['surname'];
        }

        $staffConn    = @getStaffConnection();
        $studentsConn = @getStudentsConnection();
        $websiteConn  = @getWebsiteConnection();

        if (!$staffConn) {
            if (ob_get_level()) ob_clean();
            http_response_code(503);
            echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>Service Unavailable</title>';
            echo '<style>body{font-family:-apple-system,BlinkMacSystemFont,Segoe UI,Roboto,sans-serif;background:#fef2f2;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0}';
            echo '.card{background:#fff;border-radius:12px;padding:32px;border:1px solid #fecaca;max-width:600px;text-align:center;box-shadow:0 4px 24px rgba(0,0,0,.08)}';
            echo 'h2{color:#dc2626;margin:0 0 10px}p{color:#64748b;line-height:1.6}';
            echo '.btn{display:inline-block;padding:10px 24px;background:#1e40af;color:#fff;border-radius:8px;text-decoration:none;font-weight:600;margin-top:12px}</style></head>';
            echo '<body><div class="card"><h2>Database Connection Failed</h2>';
            echo '<p>The system is unable to connect to the staff database. Please try again in a few moments, or contact the system administrator.</p>';
            echo '<a href="../staff-login.php" class="btn">Back to Login</a></div></body></html>';
            exit;
        }

        return [
            'auth'     => $auth_service,
            'staff'    => $staffConn,
            'students' => $studentsConn,
            'website'  => $websiteConn,
            'user'     => $user,
        ];
    }
}

if (!function_exists('getDashboardStats')) {
    /**
     * Safely call get_dashboard_statistics stored procedure.
     * Returns an array of stats, never throws.
     */
    function getDashboardStats($conn, int $userId, string $role): array {
        $defaults = [
            'total_staff'          => 0,
            'total_students'       => 0,
            'pending_applications' => 0,
            'active_programs'      => 2,
            'total_revenue'        => 0,
            'total_expenses'       => 0,
        ];
        if (!$conn) return $defaults;
        $stmt = $conn->prepare('CALL get_dashboard_statistics(?, ?)');
        if (!$stmt) return $defaults;
        $stmt->bind_param('is', $userId, $role);
        if (!$stmt->execute()) { $stmt->close(); return $defaults; }
        $res = $stmt->get_result();
        $row = ($res && !($res === false)) ? ($res->fetch_assoc() ?: []) : [];
        $stmt->close();
        // Drain extra result sets from stored procedure
        while ($conn->more_results()) { $conn->next_result(); }
        return array_merge($defaults, $row);
    }
}

if (!function_exists('staffRequireRole')) {
    function staffRequireRole(array $roleKeywords) {
        bootstrapStaffDashboard($roleKeywords);
    }
}

// â”€â”€ Normalize URL parameters: sidebar uses ?page= but many dashboards read ?section= â”€â”€
// This ensures both always work regardless of what the sidebar or dashboard code uses.
$_GET['section'] = $_GET['section'] ?? $_GET['page'] ?? null;
$_GET['page']    = $_GET['page']    ?? $_GET['section'] ?? null;

// â”€â”€ Fallback for renderEmptyState if dashboard_components.php wasn't loaded â”€â”€
if (!function_exists('renderEmptyState')) {
    function renderEmptyState($message, $icon = 'fas fa-inbox', $extra = '') {
        return '<div class="empty-state text-center py-5"><i class="' . htmlspecialchars($icon) . ' fa-3x text-muted mb-3"></i><p class="text-muted">' . htmlspecialchars($message) . '</p>' . ($extra ? '<p>' . $extra . '</p>' : '') . '</div>';
    }
}
