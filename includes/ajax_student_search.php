<?php
/**
 * AJAX student search for staff dashboards (DB + students_data Excel files).
 */
header('Content-Type: application/json');

require_once __DIR__ . '/../auth-service.php';
require_once __DIR__ . '/../views/student_data_loader.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$auth_service = new AuthenticationService();

if (!$auth_service->isAuthenticated() || ($_SESSION['type'] ?? '') !== 'staff') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

$role = $_SESSION['role'] ?? '';
if (!$auth_service->canSearchStudentProfiles($role)) {
    echo json_encode(['success' => false, 'message' => 'You do not have permission to search student profiles']);
    exit();
}

$search_term = trim($_GET['term'] ?? $_GET['q'] ?? '');

if (strlen($search_term) < 2) {
    echo json_encode(['success' => false, 'message' => 'Search term must be at least 2 characters']);
    exit();
}

$loader = new StudentDataLoader();
$students = $loader->searchStudents($search_term);
$results = [];

foreach (array_slice($students, 0, 50) as $student) {
    $results[] = [
        'student_id' => $student['index_number'] ?? '',
        'index_number' => $student['index_number'] ?? '',
        'first_name' => $student['first_name'] ?? '',
        'surname' => $student['surname'] ?? '',
        'other_name' => $student['other_name'] ?? '',
        'full_name' => trim(($student['surname'] ?? '') . ' ' . ($student['first_name'] ?? '') . ' ' . ($student['other_name'] ?? '')),
        'program' => $student['program'] ?? '',
        'level' => $student['level'] ?? '',
        'phone' => $student['phone'] ?? '',
        'email' => $student['email'] ?? '',
        'source' => $student['source_file'] ?? '',
    ];
}

echo json_encode([
    'success' => true,
    'students' => $results,
    'count' => count($results),
]);
