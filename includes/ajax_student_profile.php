<?php
// AJAX handler for loading student profile content
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/photo_upload.php';
require_once __DIR__ . '/student_profile_component.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

$student_id = $_GET['student_id'] ?? '';

if (empty($student_id)) {
    echo json_encode(['success' => false, 'message' => 'Student ID not provided']);
    exit();
}

// Display the detailed student profile
$html = displayStudentProfileCard($student_id, 'detailed');
echo json_encode(['success' => true, 'html' => $html]);
?>
