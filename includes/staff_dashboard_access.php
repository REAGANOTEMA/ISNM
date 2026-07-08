<?php
/**
 * Shared staff dashboard authentication and multi-database bootstrap.
 */

// ── Fatal error catcher (prevents blank pages on production) ──
register_shutdown_function(function () {
    $err = error_get_last();
    if ($err && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        $msg = htmlspecialchars($err['message']);
        $file = htmlspecialchars($err['file']);
        $line = (int)$err['line'];
        $phpVer = phpversion();
        if (ob_get_level()) ob_clean();
        echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>System Error</title>';
        echo '<style>body{font-family:sans-serif;background:#fef2f2;padding:30px;color:#991b1b}';
        echo '.card{background:#fff;border-radius:12px;padding:24px;border:1px solid #fecaca;max-width:700px;margin:40px auto}';
        echo 'h2{color:#dc2626;margin:0 0 10px}pre{background:#fef2f2;padding:12px;border-radius:6px;overflow:auto;font-size:13px}</style></head>';
        echo '<body><div class="card"><h2>Internal Server Error</h2>';
        echo '<p>The system encountered a PHP error on your hosting platform.</p>';
        echo '<p><strong>PHP Version:</strong> ' . $phpVer . ' (ISNM requires PHP 8.0+)</p>';
        echo '<pre>' . $msg . "\nFile: $file\nLine: $line" . '</pre>';
        echo '<p><a href="health-check.php" style="color:#2563eb">Run Health Check</a></p></div></body></html>';
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
        session_start();

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

        // ── Ensure CSRF token exists before validating ──
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        // ── Centralized CSRF validation for all POST requests ──
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
                            $rs->execute();
                            $rw = $rs->get_result()->fetch_assoc();
                            $rs->close();
                            if ($rw && !empty($rw['role_name']) && $rw['role_name'] !== $role) {
                                $_SESSION['role'] = $rw['role_name'];
                                $role = $rw['role_name'];
                            }
                        }
                        $sc->close();
                    }
                } catch (Exception $e) { error_log('staff_dashboard_access role refresh: ' . $e->getMessage()); }
            }
        }

        if (!empty($roleKeywords) && !$auth_service->hasFullInstitutionAccess($role)) {
            $allowed = false;
            $roleLower = strtolower(trim(preg_replace('/[^a-z0-9]+/', ' ', $role)));
            foreach ($roleKeywords as $keyword) {
                $kw = strtolower(trim(preg_replace('/[^a-z0-9]+/', ' ', $keyword)));
                if ($kw !== '' && strpos(" $roleLower ", " $kw ") !== false) {
                    $allowed = true;
                    break;
                }
            }
            if (!$allowed) {
                $userDashboard = $auth_service->getDashboardRoute($role);
                $dashboardFile = basename($userDashboard);
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

        return [
            'auth'     => $auth_service,
            'staff'    => getStaffConnection(),
            'students' => getStudentsConnection(),
            'website'  => getWebsiteConnection(),
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

// ── Fallback for renderEmptyState if dashboard_components.php wasn't loaded ──
if (!function_exists('renderEmptyState')) {
    function renderEmptyState($message, $icon = 'fas fa-inbox', $extra = '') {
        return '<div class="empty-state text-center py-5"><i class="' . htmlspecialchars($icon) . ' fa-3x text-muted mb-3"></i><p class="text-muted">' . htmlspecialchars($message) . '</p>' . ($extra ? '<p>' . $extra . '</p>' : '') . '</div>';
    }
}
