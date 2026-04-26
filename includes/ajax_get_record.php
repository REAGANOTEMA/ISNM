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

$record_id = $_GET['id'] ?? '';

if (empty($record_id)) {
    echo json_encode(['success' => false, 'message' => 'Record ID not provided']);
    exit();
}

// Get academic record details
$record_sql = "SELECT * FROM academic_records WHERE id = ?";
$record_result = executeQuery($record_sql, [$record_id], 'i');

if (empty($record_result)) {
    echo json_encode(['success' => false, 'message' => 'Record not found']);
    exit();
}

echo json_encode([
    'success' => true,
    'record' => $record_result[0]
]);
?>
