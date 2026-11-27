<?php
require '../../assets/config.php';

header('Content-Type: application/json');

$sessionId = (int) ($_SESSION['student_id'] ?? $_SESSION['uid'] ?? $_SESSION['user_id'] ?? 0);
if ($sessionId <= 0) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Student authentication required']);
    exit;
}

$studentsConn = function_exists('getStudentsConnection') ? getStudentsConnection() : null;
if (!$studentsConn) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database error']);
    exit;
}

$studentStmt = $studentsConn->prepare('SELECT id FROM students WHERE id = ? AND (status IS NULL OR status <> \'deleted\') LIMIT 1');
if (!$studentStmt) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database error']);
    exit;
}
$studentStmt->bind_param('i', $sessionId);
$studentStmt->execute();
$studentResult = $studentStmt->get_result();
$studentExists = $studentResult && $studentResult->num_rows > 0;
$studentStmt->close();
if (!$studentExists) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Student authentication required']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$requestedId = (int) (($input['id'] ?? $_POST['id'] ?? 0));
if ($requestedId !== 0 && $requestedId !== $sessionId) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'You may only update your own request']);
    exit;
}

$stmt = $studentsConn->prepare("UPDATE students SET bus_request_status = 'pending', bus_request_date = NOW() WHERE id = ?");
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