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
$allowedApprovalRoles = ['admin','director','school principal','director general','registrar','bursar'];
$approvalRole = strtolower(trim($_SESSION['role'] ?? ''));
if (!in_array($approvalRole, $allowedApprovalRoles)) {
    echo json_encode(['success' => false, 'error' => 'Insufficient permissions for approval actions']);
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/institutional_framework.php';
require_once __DIR__ . '/../includes/approval_workflow.php';
require_once __DIR__ . '/../includes/approval_integration.php';
require_once __DIR__ . '/../includes/notification_helper.php';

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

$dgName = $_SESSION['full_name'] ?? 'Director General';

// Process the approval workflow action
$result = processApprovalAction($requestId, $staffId, $action, $comments, null, $conn);

if ($result) {
    try {
        $reqInfo = $conn->query("SELECT reference_type, reference_id, status, title, requester_id, requester_name FROM approval_requests WHERE id = $requestId");
        if ($reqInfo && ($r = $reqInfo->fetch_assoc())) {
            $refType = $r['reference_type'] ?? '';

            // Process entity-specific side effects
            if ($refType === 'store_requests' && in_array($action, ['approve','reject'])) {
                processStoreApproval($requestId, $action, $comments, $conn);
            } elseif ($refType === 'pending_students' && in_array($action, ['approve','reject'])) {
                $studentsConn = getStudentsConnection();
                processStudentApproval($requestId, $action, $comments, $conn, $studentsConn);
            }

            // Send notification to the requester
            $requesterId = (int)($r['requester_id'] ?? 0);
            $reqTitle = $r['title'] ?? 'Request';
            if ($requesterId > 0) {
                $actionLabels = ['approve' => 'approved', 'reject' => 'rejected', 'return' => 'returned for revision'];
                $label = $actionLabels[$action] ?? $action . 'd';
                $notificationTitle = "Request $label";
                $notificationMessage = "\"$reqTitle\" has been $label by Director General ($dgName).";
                if ($comments) $notificationMessage .= " Comment: $comments";
                $nid = createNotification(
                    $notificationTitle,
                    $notificationMessage,
                    '../dashboards/director-general.php?page=approvals',
                    $action === 'approve' ? 'success' : ($action === 'reject' ? 'danger' : 'warning'),
                    $action === 'approve' ? 'fas fa-check-circle' : ($action === 'reject' ? 'fas fa-times-circle' : 'fas fa-undo')
                );
                if ($nid) {
                    $websiteConn = getWebsiteConnection();
                    $staffConn = getStaffConnection();
                    if ($websiteConn && $staffConn) {
                        $allStaff = $staffConn->query("SELECT id FROM staff WHERE id != $requesterId");
                        if ($allStaff) {
                            $stmt = $websiteConn->prepare("INSERT IGNORE INTO notification_reads (notification_id, user_id, user_type) VALUES (?, ?, 'staff')");
                            if ($stmt) {
                                while ($s = $allStaff->fetch_assoc()) {
                                    $sid = (int)$s['id'];
                                    $stmt->bind_param('ii', $nid, $sid);
                                    $stmt->execute();
                                }
                                $stmt->close();
                            }
                        }
                    }
                }
            }
        }
    } catch (Exception $e) {
        error_log('approval_action side-effect error: ' . $e->getMessage());
    }
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to process approval action']);
}
