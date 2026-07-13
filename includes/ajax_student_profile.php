<?php
// AJAX handler for loading student profile content
// Use relative includes for portability across environments
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/photo_upload.php';
require_once __DIR__ . '/student_profile_component.php';

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
