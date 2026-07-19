<?php
/**
 * Student Portal - Root Level Redirect
 * Redirects to the actual student portal in dashboards/
 */
session_start();
if (isset($_SESSION['student_id']) || isset($_SESSION['type']) && $_SESSION['type'] === 'student') {
    header('Location: dashboards/student-portal.php');
} else {
    header('Location: student-login.php');
}
exit;
