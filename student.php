<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/auth-service.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$isLoggedIn = isset($_SESSION['user_id'], $_SESSION['role']);
$isStaff = ($_SESSION['type'] ?? '') === 'staff';
$isStudent = ($_SESSION['type'] ?? '') === 'student';

// Staff viewing a student — redirect via the student dashboards route
if ($isStaff) {
    header("Location: dashboards/student-management.php");
    exit;
}

// Student must be properly authenticated
if (!$isLoggedIn || !$isStudent) {
    header('Location: student-login.php');
    exit;
}

header("Location: dashboards/student.php");
exit;
