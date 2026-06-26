<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['user_id']) || ($_SESSION['type'] ?? '') !== 'student') {
    header('Location: student-login.php');
    exit();
}
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/financial_functions.php';
