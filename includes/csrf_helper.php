<?php
if (session_status() === PHP_SESSION_NONE) session_start();

if (!function_exists('generateCsrfToken')) {
    function generateCsrfToken() {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
}

if (!function_exists('csrfField')) {
    function csrfField() {
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(generateCsrfToken()) . '">';
    }
}

if (!function_exists('verifyCsrfToken')) {
    function verifyCsrfToken() {
        if (!isset($_POST['csrf_token'], $_SESSION['csrf_token'])) return false;
        return hash_equals($_SESSION['csrf_token'], $_POST['csrf_token']);
    }
}
