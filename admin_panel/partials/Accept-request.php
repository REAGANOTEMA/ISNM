<?php
require 'config.php';

header('Content-Type: application/json');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'ERROR', 'message' => 'Method not allowed']);
    exit;
}

$csrfToken = $_POST['csrf_token'] ?? '';
$sessionToken = $_SESSION['csrf_token'] ?? '';
if ($csrfToken === '' || $sessionToken === '' || !hash_equals($sessionToken, $csrfToken)) {
    http_response_code(403);
    echo json_encode(['status' => 'ERROR', 'message' => 'Invalid security token']);
    exit;
}

if (function_exists('isnm_require_admin_role')) {
    isnm_require_admin_role();
}

$id = (int) ($_POST['id'] ?? 0);
if ($id <= 0) {
    http_response_code(400);
    echo json_encode(['status' => 'ERROR', 'message' => 'Valid student ID required']);
    exit;
}

$stmt = $conn->prepare("UPDATE students SET bus_request_status = 'accepted', bus_request_date = NOW() WHERE id = ? AND bus_request_status = 'pending'");
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['status' => 'ERROR', 'message' => 'Database error']);
    exit;
}
$stmt->bind_param('i', $id);
$updated = $stmt->execute() && $stmt->affected_rows > 0;
$stmt->close();

echo json_encode($updated
    ? ['status' => 'success', 'message' => 'Bus request accepted']
    : ['status' => 'ERROR', 'message' => 'Pending request not found']);
