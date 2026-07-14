<?php
if (session_status() === PHP_SESSION_NONE) session_start();

// Require POST to prevent CSRF logout attacks
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../../staff-login.php');
    exit();
}

// Clear all session data
$_SESSION = [];
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy session
session_unset();
session_destroy();

// Prevent browser caching of protected pages
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

header('Location: ../../staff-login.php');
exit();
