<?php
/**
 * AJAX endpoint for submitting department approval requests to Director General.
 * Creates an approval_requests record linked to the correct workflow.
 */
header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['type'] ?? '') !== 'staff') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$action = $_POST['action'] ?? '';
if ($action !== 'submit_approval_request') {
    echo json_encode(['success' => false, 'error' => 'Invalid action']);
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/approval_workflow.php';

$conn = getStaffConnection();
if (!$conn) {
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}

$title = trim($_POST['title'] ?? '');
$description = trim($_POST['description'] ?? '');
$priority = trim($_POST['priority'] ?? 'Normal');
$category = trim($_POST['category'] ?? '');
$department = trim($_POST['department'] ?? '');

if (!$title || !$description || !$category) {
    echo json_encode(['success' => false, 'error' => 'Title, description, and category are required']);
    exit;
}

// Map category to workflow name
$workflowNameMap = [
    'General Administration' => 'General Department Request',
    'Human Resources' => 'HR Request',
    'Finance' => 'Finance Request',
    'ICT' => 'ICT Request',
    'Academic' => 'Academic Request',
    'Admissions' => 'Admissions Request',
    'Library' => 'Library Request',
    'Store & Assets' => 'Store Requisition',
];

$workflowName = $workflowNameMap[$category] ?? 'General Department Request';

try {
    // Find the workflow
    $stmt = $conn->prepare("SELECT id FROM igangaschoolofl_staffs_db.approval_workflows WHERE workflow_name = ? AND is_active = 1 LIMIT 1");
    if (!$stmt) {
        echo json_encode(['success' => false, 'error' => 'Database error']);
        exit;
    }
    $stmt->bind_param('s', $workflowName);
    $stmt->execute();
    $result = $stmt->get_result();
    $wf = $result->fetch_assoc();
    $stmt->close();

    if (!$wf) {
        echo json_encode(['success' => false, 'error' => "No active workflow found for category: $category"]);
        exit;
    }

    $workflowId = (int)$wf['id'];
    $staffId = (int)$_SESSION['user_id'];
    $staffName = $_SESSION['full_name'] ?? 'Staff';
    $staffRole = $_SESSION['role'] ?? 'Staff';
    $position = $_SESSION['position'] ?? $staffRole;

    // Build full description with department context
    $fullDesc = $description;
    if ($department) {
        $fullDesc = "[Department: $department] $description";
    }

    $result = createApprovalRequest($workflowId, $title, $fullDesc, $staffId, $staffName, $position, $priority, 'general', null, null, $conn);

    if ($result) {
        // Get the created request number
        $lastId = $conn->insert_id;
        $rn = $conn->query("SELECT request_number FROM igangaschoolofl_staffs_db.approval_requests WHERE id = $lastId");
        $requestNumber = ($rn && $row = $rn->fetch_assoc()) ? $row['request_number'] : '';

        // Send notification to DG about new request
        if (function_exists('createNotification')) {
            require_once __DIR__ . '/../includes/notification_helper.php';
            $nid = createNotification(
                "New Approval Request: $title",
                "$staffName submitted a new $category approval request.",
                '../dashboards/director-general.php?page=approvals',
                'info',
                'fas fa-file-signature'
            );
        }

        echo json_encode([
            'success' => true,
            'request_number' => $requestNumber,
            'message' => "Request #$requestNumber submitted successfully and sent to the Director General for review."
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to create approval request. Check that approval workflows are configured.']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Server error: ' . $e->getMessage()]);
}
