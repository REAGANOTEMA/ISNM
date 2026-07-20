<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../auth-service.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$response = ['logged_in' => false, 'valid' => false];

if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
    $response['logged_in'] = true;
    $response['valid'] = true;
    $response['user_id'] = (int)$_SESSION['user_id'];
    $response['role'] = $_SESSION['role'] ?? '';
    $response['full_name'] = $_SESSION['full_name'] ?? '';
} else {
    $response['message'] = 'No active session';
}

echo json_encode($response);
