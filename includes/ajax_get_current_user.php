<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../auth-service.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$response = ['success' => false, 'user' => null];

if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
    $response['success'] = true;
    $response['user'] = [
        'id' => (int)$_SESSION['user_id'],
        'full_name' => $_SESSION['full_name'] ?? '',
        'email' => $_SESSION['email'] ?? '',
        'role' => $_SESSION['role'] ?? '',
        'type' => $_SESSION['type'] ?? '',
        'department' => $_SESSION['department'] ?? '',
        'username' => $_SESSION['username'] ?? '',
    ];
    $response['message'] = 'User found';
} else {
    $response['message'] = 'No active session';
}

echo json_encode($response);
