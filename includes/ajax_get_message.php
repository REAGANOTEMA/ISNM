<?php
// AJAX handler for getting message details
header('Content-Type: application/json');

include_once 'config.php';
include_once 'functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

$message_id = $_GET['id'] ?? '';

if (empty($message_id)) {
    echo json_encode(['success' => false, 'message' => 'Message ID not provided']);
    exit();
}

// Get message details
$message_sql = "SELECT * FROM messages WHERE id = ?";
$message_result = executeQuery($message_sql, [$message_id], 'i');

if (empty($message_result)) {
    echo json_encode(['success' => false, 'message' => 'Message not found']);
    exit();
}

echo json_encode([
    'success' => true,
    'message' => $message_result[0]
]);
?>
