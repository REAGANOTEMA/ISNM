<?php
session_start();

// Check user role before destroying session
$user_role = isset($_SESSION['role']) ? $_SESSION['role'] : 'Staff';

// Destroy all session variables
$_SESSION = array();

// Destroy the session
session_destroy();

// Redirect to appropriate login page based on original role
if ($user_role === 'Student') {
    header('Location: student-login.php');
} else {
    header('Location: staff-login.php');
}
exit();
?>
