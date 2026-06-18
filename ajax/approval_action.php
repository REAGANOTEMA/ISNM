<?php
/**
 * AJAX handler for approval workflow actions.
 * Called from approval_workflow.php action buttons.
 */
header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['type'] ?? '') !== 'staff') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/institutional_framework.php';
require_once __DIR__ . '/../includes/approval_workflow.php';

$staffId = (int)($_SESSION['user_id'] ?? 0);
$requestId = (int)($_POST['request_id'] ?? 0);
$action = $_POST['action'] ?? '';
$comments = $_POST['comments'] ?? '';

if (!$requestId || !$action) {
    echo json_encode(['success' => false, 'error' => 'Missing parameters']);
    exit;
}

$conn = getStaffConnection();
if (!$conn) {
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}

$result = processApprovalAction($requestId, $staffId, $action, $comments, null, $conn);
if ($result) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to process approval action']);
}
