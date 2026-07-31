<?php
require_once __DIR__ . '/../../includes/staff_dashboard_access.php';
$ctx = bootstrapStaffDashboard(['lecturer']);
header('Content-Type: application/json');

$user_id = (int)($ctx['user']['id'] ?? 0);
$conn = $ctx['staff'];
if (!$user_id || !$conn) { echo json_encode([]); exit; }

$results = [];
$stmt = $conn->prepare("SELECT s.full_name AS student_name, ar.marks_obtained AS marks, ar.grade FROM academic_records ar JOIN students s ON ar.student_id = s.id WHERE ar.staff_id = ? OR ar.lecturer_id = ? ORDER BY s.full_name DESC");
if ($stmt) {
    $stmt->bind_param("ii", $user_id, $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) { $results[] = $row; }
    $stmt->close();
}

if (empty($results)) {
    $stmt2 = $conn->prepare("SELECT s.full_name AS student_name, ar.marks_obtained AS marks, ar.grade FROM academic_records ar JOIN course_assignments ca ON ar.course_name = ca.course_name JOIN students s ON ar.student_id = s.id WHERE ca.staff_id = ? ORDER BY s.full_name");
    if ($stmt2) {
        $stmt2->bind_param("i", $user_id);
        $stmt2->execute();
        $res2 = $stmt2->get_result();
        while ($row = $res2->fetch_assoc()) { $results[] = $row; }
        $stmt2->close();
    }
}

echo json_encode($results);
