<?php
// AJAX handler for getting message details
header('Content-Type: application/json');

include_once __DIR__ . '/config.php';
include_once __DIR__ . '/functions.php';

if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

$message_id = (int)($_GET['id'] ?? 0);

if ($message_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Message ID not provided']);
    exit();
}

$conn = getStaffConnection();
if (!$conn) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

$stmt = $conn->prepare("SELECT * FROM messages WHERE id = ? AND (sender_id = ? OR recipient_id = ?)");
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Query failed']);
    exit();
}
$stmt->bind_param("iii", $message_id, $_SESSION['user_id'], $_SESSION['user_id']);
if (!$stmt->execute()) {
    echo json_encode(['success' => false, 'message' => 'Query execution failed']);
    exit();
}
$result = $stmt->get_result();
$message = $result->fetch_assoc();
$stmt->close();

if (!$message) {
    echo json_encode(['success' => false, 'message' => 'Message not found']);
    exit();
}

echo json_encode([
    'success' => true,
    'message' => $message
]);
?>
