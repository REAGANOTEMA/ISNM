<?php
require('../../assets/config.php');

header('Content-Type: application/json');

// Students may only update their own transport request.
$sessionId = (int)($_SESSION['student_id'] ?? $_SESSION['uid'] ?? $_SESSION['user_id'] ?? 0);
if (!$sessionId || (strtolower($_SESSION['type'] ?? $_SESSION['role'] ?? '') !== 'student')) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Student authentication required']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$requestedId = (int)($input['id'] ?? 0);
if ($requestedId && $requestedId !== $sessionId) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'You may only update your own request']);
    exit;
}

$stmt = $conn->prepare("UPDATE students SET bus_request_status = 'pending', bus_request_date = NOW() WHERE id = ?");
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database error']);
    exit;
}
$stmt->bind_param('i', $sessionId);
$success = $stmt->execute();
$stmt->close();

echo json_encode($success
    ? ['status' => 'success', 'message' => 'Request updated successfully']
    : ['status' => 'error', 'message' => 'Error updating request']);
