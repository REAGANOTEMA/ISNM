<?php
if (session_status() === PHP_SESSION_NONE) session_start();

// Require POST to prevent CSRF logout attacks
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../staff-login.php');
    exit();
}

session_unset();
session_destroy();
header('Location: ../staff-login.php');
exit();
