<?php
/**
 * Shared staff dashboard authentication and multi-database bootstrap.
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../auth-service.php';

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
            header('Location: ../staff-login.php');
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
                header('Location: ../staff-login.php?error=unauthorized');
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

        return [
            'auth' => $auth_service,
            'staff' => getStaffConnection(),
            'students' => getStudentsConnection(),
            'website' => getWebsiteConnection(),
            'user' => $auth_service->getCurrentUser(),
        ];
    }

    function staffRequireRole(array $roleKeywords) {
        bootstrapStaffDashboard($roleKeywords);
    }
}
