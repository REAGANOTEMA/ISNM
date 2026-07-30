<?php
// AJAX handler for getting academic record details
header('Content-Type: application/json');

include_once __DIR__ . '/../config/database.php';
include_once 'functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

$userType = $_SESSION['type'] ?? '';
$userId = (int)$_SESSION['user_id'];
$record_id = (int)($_GET['id'] ?? 0);

if ($record_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Record ID not provided']);
    exit();
}

$studentsConn = getStudentsConnection();
if (!$studentsConn) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

if ($userType === 'student') {
    $record_sql = "SELECT * FROM academic_records WHERE id = ? AND student_id = ?";
    $stmt = $studentsConn->prepare($record_sql);
    $stmt->bind_param('ii', $record_id, $userId);
} else {
    $record_sql = "SELECT * FROM academic_records WHERE id = ?";
    $stmt = $studentsConn->prepare($record_sql);
    $stmt->bind_param('i', $record_id);
}

if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Query failed']);
    exit();
}

$stmt->execute();
$record_result = $stmt->get_result();
$records = $record_result ? isnm_fetch_all($record_result) : [];
$stmt->close();

if (empty($records)) {
    echo json_encode(['success' => false, 'message' => 'Record not found']);
    exit();
}

echo json_encode([
    'success' => true,
    'record' => $records[0]
]);
?>
