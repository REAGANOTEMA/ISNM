<?php
header('Content-Type: application/json');
require_once __DIR__ . '/staff_dashboard_access.php';
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id'])) { echo json_encode(['success'=>false,'message'=>'Unauthorized']); exit; }
$ctx = bootstrapStaffDashboard([]);
$conn = $ctx['students'];
$term = trim($_GET['term'] ?? $_GET['q'] ?? '');
if (strlen($term) < 1) { echo json_encode(['success'=>false,'students'=>[]]); exit; }
$results = [];
if ($conn) {
    $like = '%' . $term . '%';
    $sql = "SELECT student_id, student_number, index_number, registration_number, full_name, first_name, surname, other_name, program, level, set_name, phone, mobile_number, email, gender, status, passport_photo, profile_picture
            FROM students
            WHERE full_name LIKE ? OR first_name LIKE ? OR surname LIKE ? OR other_name LIKE ? OR student_id LIKE ? OR student_number LIKE ? OR index_number LIKE ? OR phone LIKE ? OR mobile_number LIKE ?
            LIMIT 30";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param('sssssssss', $like, $like, $like, $like, $like, $like, $like, $like, $like);
        if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
        $r = $stmt->get_result();
        if ($r) while ($row = $r->fetch_assoc()) $results[] = $row;
        $stmt->close();
    }
}
echo json_encode(['success'=>true, 'students'=>$results, 'count'=>count($results)]);
