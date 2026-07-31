<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/staff_dashboard_access.php';

header('Content-Type: application/json');

$ctx = bootstrapStaffDashboard(['non teaching', 'staff']);
$conn = $ctx['staff'];
$user = $ctx['user'];
$user_id = (int) ($user['id'] ?? 0);
$user_role = $user['role'] ?? '';

$respond = function ($success, $message, $code = 200) {
    http_response_code($code);
    echo json_encode(['success' => $success, 'message' => $message]);
    exit();
};

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || $user_id <= 0) {
    $respond(false, 'Invalid upload request.', 400);
}

$token = $_POST['csrf_token'] ?? '';
if (empty($token) || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
    $respond(false, 'Invalid security token. Please refresh and try again.', 403);
}

if (empty($_FILES['document_file']) || $_FILES['document_file']['error'] !== UPLOAD_ERR_OK) {
    $respond(false, 'Please select a file to upload.', 400);
}

$file = $_FILES['document_file'];
$maxSize = 10 * 1024 * 1024;
$allowedTypes = [
    'pdf'       => 'application/pdf',
    'jpg'       => 'image/jpeg',
    'jpeg'      => 'image/jpeg',
    'png'       => 'image/png',
    'gif'       => 'image/gif',
    'doc'       => 'application/msword',
    'docx'      => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'xls'       => 'application/vnd.ms-excel',
    'xlsx'      => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'ppt'       => 'application/vnd.ms-powerpoint',
    'pptx'      => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
    'txt'       => 'text/plain',
    'csv'       => 'text/csv',
    'zip'       => 'application/zip',
];

$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
if (!array_key_exists($ext, $allowedTypes)) {
    $respond(false, 'File type not allowed. Allowed: ' . implode(', ', array_keys($allowedTypes)) . '.', 400);
}

$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime = $finfo ? finfo_file($finfo, $file['tmp_name']) : ($file['type'] ?? '');
if ($finfo) finfo_close($finfo);

if ($file['size'] > $maxSize) {
    $respond(false, 'File exceeds the 10MB size limit.', 400);
}

if ($mime !== '' && strpos($allowedTypes[$ext], $mime) !== 0 && !in_array($ext, ['txt', 'csv', 'zip'], true)) {
    $respond(false, 'File content does not match its extension.', 400);
}

$uploadDir = __DIR__ . '/../uploads/staff_documents';
if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true)) {
    $respond(false, 'Unable to create upload directory.', 500);
}

$safeBase = preg_replace('/[^A-Za-z0-9._-]/', '_', pathinfo($file['name'], PATHINFO_FILENAME));
$safeBase = $safeBase !== '' ? $safeBase : 'document';
$uniqueName = $user_id . '_' . date('Ymd_His') . '_' . substr(bin2hex(random_bytes(8)), 0, 8) . '.' . $ext;
$destPath = $uploadDir . DIRECTORY_SEPARATOR . $uniqueName;

if (!move_uploaded_file($file['tmp_name'], $destPath)) {
    $respond(false, 'Failed to save the uploaded file.', 500);
}

$documentName = trim($_POST['document_name'] ?? '');
if ($documentName === '') {
    $documentName = $safeBase;
}
$documentType = trim($_POST['document_type'] ?? 'Other');
if ($documentType === '') {
    $documentType = 'Other';
}
$notes = trim($_POST['notes'] ?? '');
$dbPath = 'uploads/staff_documents/' . $uniqueName;

$stmt = $conn->prepare("INSERT INTO staff_documents (staff_id, document_type, document_name, file_path, file_size, mime_type, uploaded_by, notes, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
if (!$stmt) {
    @unlink($destPath);
    $respond(false, 'Database error.', 500);
}

$size = (int) $file['size'];
$stmt->bind_param('isssisis', $user_id, $documentType, $documentName, $dbPath, $size, $mime, $user_id, $notes);
if (!$stmt->execute()) {
    @unlink($destPath);
    $respond(false, 'Failed to save document record: ' . $stmt->error, 500);
}
$stmt->close();

if (function_exists('logActivity')) {
    logActivity($user_id, $user_role, 'Document Uploaded', "Uploaded $documentName", 'staff_documents', $user_id);
}

$respond(true, 'Document "' . $documentName . '" uploaded successfully.');
