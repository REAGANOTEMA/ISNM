<?php
/**
 * Shared staff dashboard authentication and multi-database bootstrap.
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../auth-service.php';
require_once __DIR__ . '/../includes/student_helpers.php';

if (!function_exists('bootstrapStaffDashboard')) {
    /**
     * @param array<int, string> $roleKeywords Empty = any authenticated staff.
     * @return array{auth: AuthenticationService, staff: mysqli, students: mysqli, website: mysqli, user: array}
     */
    function bootstrapStaffDashboard(array $roleKeywords = []) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        global $auth_service;
        if (!isset($auth_service) || !($auth_service instanceof AuthenticationService)) {
            $auth_service = new AuthenticationService();
        }

        if (!$auth_service->isAuthenticated() || ($_SESSION['type'] ?? '') !== 'staff') {
            $redirect = isset($_SERVER['REQUEST_URI']) ? urlencode($_SERVER['REQUEST_URI']) : '';
            header('Location: ../staff-login.php' . ($redirect ? "?redirect=$redirect" : ''));
            exit();
        }

        // Session timeout enforcement (1hr sliding window)
        if (!$auth_service->checkSessionValidity()) {
            $redirect = isset($_SERVER['REQUEST_URI']) ? urlencode($_SERVER['REQUEST_URI']) : '';
            header('Location: ../staff-login.php?error=expired' . ($redirect ? "&redirect=$redirect" : ''));
            exit();
        }

        $role = $_SESSION['role'] ?? '';

        if (!empty($roleKeywords) && !$auth_service->hasFullInstitutionAccess($role)) {
            $allowed = false;
            foreach ($roleKeywords as $keyword) {
                if ($keyword !== '' && stripos($role, $keyword) !== false) {
                    $allowed = true;
                    break;
                }
            }
            if (!$allowed) {
                // User is authenticated but lacks role — send to their own dashboard, not login (avoids redirect loop)
                $userDashboard = $auth_service->getDashboardRoute($role);
                // getDashboardRoute returns e.g. "dashboards/foo.php" — prepend "../" because we're inside /dashboards/
                header('Location: ../' . ($userDashboard ?: 'index.php'));
                exit();
            }
        }

        // Enforce that initial staff login was performed via organogram when present.
        // If the request came from organogram, ensure the position matches the authenticated role.
        if (!empty($_SESSION['logged_in_via_organogram']) && !empty($_SESSION['logged_in_via_position'])) {
            $requestedPos = $_SESSION['logged_in_via_position'];
            if (!$auth_service->positionMatchesRole($requestedPos, $role) && !$auth_service->hasFullInstitutionAccess($role)) {
                // Mismatch: force user back to organogram to pick correct position
                header('Location: ../organogram.php');
                exit();
            }
            // Clear the one-time flags so normal navigation does not require organogram each page
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
