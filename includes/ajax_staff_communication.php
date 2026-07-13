<?php
/**
 * AJAX endpoint for staff communication system.
 * Handles sending messages from the dashboard communication modal.
 */
header('Content-Type: application/json');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/staff_communication.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Require staff authentication
if (empty($_SESSION['logged_in']) || $_SESSION['type'] !== 'staff' || empty($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized. Staff authentication required.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['action']) || $_POST['action'] !== 'send_communication') {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid request.']);
    exit;
}

$token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
if (empty($token) || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
    echo json_encode(['success' => false, 'error' => 'Invalid security token.']);
    exit;
}

// Validate required fields
$sender_id    = (int)($_POST['sender_id'] ?? 0);
$sender_email = trim($_POST['sender_email'] ?? '');
$sender_name  = trim($_POST['sender_name'] ?? '');
$subject      = trim($_POST['subject'] ?? '');
$message      = trim($_POST['message'] ?? '');
$recipient_type = $_POST['recipient_type'] ?? 'department';
$recipient_id = trim($_POST['recipient_id'] ?? '');
$priority     = $_POST['priority'] ?? 'Normal';

// Validate sender matches session (security check)
if ($sender_id !== (int)$_SESSION['user_id'] || $sender_email !== ($_SESSION['email'] ?? '')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Sender identity mismatch.']);
    exit;
}

if (empty($subject) || empty($message)) {
    echo json_encode(['success' => false, 'error' => 'Subject and message are required.']);
    exit;
}

if ($recipient_type === 'department' && empty($recipient_id)) {
    echo json_encode(['success' => false, 'error' => 'Please select a department.']);
    exit;
}

// Establish database connection (needed for all recipient types)
$conn = null;
if (function_exists('getStaffConnection')) {
    $conn = getStaffConnection();
} elseif (function_exists('getDatabaseConnection')) {
    $conn = getDatabaseConnection('staffs');
}

// Auto-create tables and seed departments if needed
if ($conn && function_exists('ensureCommunicationTables')) ensureCommunicationTables($conn);

// Resolve recipient name
$recipient_name = '';
if ($recipient_type === 'department' && $conn) {
    $deptCode = $recipient_id;
    $stmt = $conn->prepare("SELECT department_name FROM communication_channels WHERE department_code = ? AND is_active = 1 LIMIT 1");
    $stmt->bind_param("s", $deptCode);
    if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
    $r = $stmt->get_result();
    if ($r && ($row = $r->fetch_assoc())) {
        $recipient_name = $row['department_name'];
    }
    $stmt->close();
    if (empty($recipient_name)) {
        $recipient_name = ucwords(str_replace('_', ' ', $recipient_id));
    }
} elseif ($recipient_type === 'all_staff') {
    $recipient_name = 'All Staff';
}

$result = sendStaffCommunication(
    $conn,
    $sender_id,
    $sender_email,
    $sender_name,
    $recipient_type,
    $recipient_id,
    $recipient_name,
    $subject,
    $message,
    $priority
);

echo json_encode($result);
