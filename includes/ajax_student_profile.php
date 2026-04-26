<?php
// AJAX handler for loading student profile content
include_once 'config.php';
include_once 'functions.php';
include_once 'photo_upload.php';
include_once 'student_profile_component.php';

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
