<?php
require_once __DIR__ . '/includes/staff_dashboard_access.php';
$ctx = bootstrapStaffDashboard([]);
$user = $ctx['user'];
$studentNumber = $user['student_number'] ?? ($_SESSION['student_number'] ?? '');

if (!$studentNumber) {
    header('Location: student-login.php');
    exit;
}

header("Location: dashboards/student.php");
exit;
