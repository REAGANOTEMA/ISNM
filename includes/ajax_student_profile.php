<?php
// AJAX handler for loading student profile content
// Use absolute includes for robust resolution from any include path
require_once $_SERVER['DOCUMENT_ROOT'] . '/ISNM/includes/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/ISNM/includes/functions.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/ISNM/includes/photo_upload.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/ISNM/includes/student_profile_component.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo '<div class="alert alert-danger">Unauthorized access</div>';
    exit();
}

$student_id = $_GET['student_id'] ?? '';

if (empty($student_id)) {
    echo '<div class="alert alert-warning">Student ID not provided</div>';
    exit();
}

// Display the detailed student profile
echo displayStudentProfileCard($student_id, 'detailed');
?>
