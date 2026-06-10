<?php
if (session_status() === PHP_SESSION_NONE) session_start();
$redirect = (($_SESSION['type'] ?? '') === 'student') ? 'student-login.php' : 'organogram.php';
session_unset();
session_destroy();
header('Location: ' . $redirect);
exit();
?>
