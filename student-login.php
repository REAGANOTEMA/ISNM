<?php
/**
 * student-login.php — redirect stub to the unified login page
 *
 * All student auth now lives in staff-login.php (Student Login tab).
 * Any ?student_role=… query parameter is forwarded as a session hint so
 * the unified page can pre-select the student tab when it lands.
 */
if (session_status() === PHP_SESSION_NONE) { session_start(); }

$student_role = isset($_GET['student_role']) ? urldecode($_GET['student_role']) : '';
if ($student_role) {
    $_SESSION['student_role']            = $student_role;
    $_SESSION['error_source']            = 'student';
}

header('Location: staff-login.php');
exit();
