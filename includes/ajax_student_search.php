<?php
// AJAX handler for student search functionality
header('Content-Type: application/json');

include_once 'config.php';
include_once 'functions.php';
include_once 'photo_upload.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

$search_term = $_GET['term'] ?? '';

if (empty($search_term) || strlen($search_term) < 2) {
    echo json_encode(['success' => false, 'message' => 'Search term must be at least 2 characters']);
    exit();
}

// Search students
$search_sql = "SELECT student_id, first_name, surname, other_name, program, level, profile_image, phone, email 
              FROM students 
              WHERE (first_name LIKE ? OR surname LIKE ? OR other_name LIKE ? OR student_id LIKE ? OR phone LIKE ? OR email LIKE ?)
              AND status = 'active'
              ORDER BY surname, first_name
              LIMIT 20";

$search_param = "%$search_term%";
$params = [$search_param, $search_param, $search_param, $search_param, $search_param, $search_param];
$types = 'ssssss';

$students = executeQuery($search_sql, $params, $types);

$results = [];
foreach ($students as $student) {
    $results[] = [
        'student_id' => $student['student_id'],
        'first_name' => $student['first_name'],
        'surname' => $student['surname'],
        'other_name' => $student['other_name'],
        'full_name' => $student['surname'] . ', ' . $student['first_name'] . ($student['other_name'] ? ' ' . $student['other_name'] : ''),
        'program' => $student['program'],
        'level' => $student['level'],
        'photo_url' => getPassportPhotoUrl($student['profile_image']),
        'phone' => $student['phone'],
        'email' => $student['email']
    ];
}

echo json_encode([
    'success' => true,
    'students' => $results,
    'count' => count($results)
]);
?>
