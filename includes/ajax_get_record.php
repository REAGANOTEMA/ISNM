<?php
// AJAX handler for getting academic record details
header('Content-Type: application/json');

include_once 'config.php';
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

if ($userType === 'student') {
    $record_sql = "SELECT * FROM academic_records WHERE id = ? AND student_id = ?";
    $record_result = executeQuery($record_sql, [$record_id, $userId], 'ii');
} else {
    $record_sql = "SELECT * FROM academic_records WHERE id = ?";
    $record_result = executeQuery($record_sql, [$record_id], 'i');
}

if (empty($record_result)) {
    echo json_encode(['success' => false, 'message' => 'Record not found']);
    exit();
}

echo json_encode([
    'success' => true,
    'record' => $record_result[0]
]);
?>
