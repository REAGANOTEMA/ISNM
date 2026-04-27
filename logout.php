<?php
session_start();

// Destroy all session variables
$_SESSION = array();

// Destroy the session
session_destroy();

// Redirect to appropriate login page based on session role
if (isset($_SESSION['role']) && $_SESSION['role'] === 'Student') {
    header('Location: student-login.php');
} else {
    header('Location: staff-login.php');
}
exit();
?>
