<?php
/**
 * Bursar Dashboard — redirects to unified dashboards/school-bursar.php
 * This file exists for legacy routing compatibility.
 */
require_once __DIR__ . '/auth-service.php';
if (!$auth_service->isAuthenticated() || ($_SESSION['type'] ?? '') !== 'staff') {
    header('Location: staff-login.php'); exit;
}
header('Location: dashboards/school-bursar.php');
exit;
