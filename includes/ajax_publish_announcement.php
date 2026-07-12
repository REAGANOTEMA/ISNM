<?php
require_once __DIR__ . '/../config/database.php';
if (session_status() === PHP_SESSION_NONE) session_start();

// Only allow specific roles to publish
$allowed_roles = ['Director General', 'Director', 'CEO', 'Principal', 'Director Admissions', 'Administrator', 'Secretary', 'School Secretary', 'HR Manager'];
$user_role = $_SESSION['role'] ?? '';
$user_name = $_SESSION['full_name'] ?? ($_SESSION['user'] ?? 'Staff');

if (!in_array($user_role, $allowed_roles)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Accept POST data
$title = trim($_POST['title'] ?? '');
$content = trim($_POST['content'] ?? '');
$announcement_type = $_POST['announcement_type'] ?? 'general';
$target_audience = $_POST['target_audience'] ?? 'all';
$priority = $_POST['priority'] ?? 'medium';
$expiry_date = $_POST['expiry_date'] ?? null;
$status = $_POST['status'] ?? 'published';

if ($title === '' || $content === '') {
    echo json_encode(['success' => false, 'message' => 'Title and content are required']);
    exit;
}

try {
    $conn = getWebsiteConnection();
    $stmt = $conn->prepare("INSERT INTO announcements (title, content, announcement_type, target_audience, priority, posted_by_name, posted_by_role, status, expiry_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $posted_by_name = $user_name;
    $posted_by_role = $user_role;
    $stmt->bind_param('sssssssss', $title, $content, $announcement_type, $target_audience, $priority, $posted_by_name, $posted_by_role, $status, $expiry_date);
    if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
    $id = $stmt->insert_id;
    $stmt->close();
    echo json_encode(['success' => true, 'id' => $id]);
} catch (Exception $e) {
    error_log('Publish announcement error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
