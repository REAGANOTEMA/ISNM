<?php
/**
 * AJAX handler for approval workflow actions.
 * Called from approval_workflow.php action buttons.
 * Also processes entity-specific side effects (store, students).
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
require_once __DIR__ . '/../includes/approval_integration.php';

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

// Process the approval workflow action
$result = processApprovalAction($requestId, $staffId, $action, $comments, null, $conn);

if ($result) {
    // Process entity-specific side effects
    try {
        $reqInfo = $conn->query("SELECT reference_type, reference_id, status FROM approval_requests WHERE id = $requestId");
        if ($reqInfo && ($r = $reqInfo->fetch_assoc())) {
            $refType = $r['reference_type'] ?? '';
            if ($refType === 'store_requests' && in_array($action, ['approve','reject'])) {
                processStoreApproval($requestId, $action, $comments, $conn);
            } elseif ($refType === 'pending_students' && in_array($action, ['approve','reject'])) {
                $studentsConn = getStudentsConnection();
                processStudentApproval($requestId, $action, $comments, $conn, $studentsConn);
            }
        }
    } catch (Exception $e) {
        error_log('approval_action side-effect error: ' . $e->getMessage());
    }
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to process approval action']);
}
