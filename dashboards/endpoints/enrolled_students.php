<?php
require_once __DIR__ . '/../../includes/staff_dashboard_access.php';
$ctx = bootstrapStaffDashboard(['lecturer']);
header('Content-Type: application/json');

$user_id = (int)($ctx['user']['id'] ?? 0);
$conn = $ctx['staff'];
$studentsConn = $ctx['students'];
if (!$user_id || !$conn || !$studentsConn) { echo json_encode([]); exit; }

$results = [];
$stmt = $conn->prepare("SELECT course_name FROM course_assignments WHERE staff_id = ?");
if ($stmt) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($course = $res->fetch_assoc()) {
        $cn = $course['course_name'] ?? '';
        if (!$cn) continue;
        $stmt2 = $studentsConn->prepare("SELECT id, student_number, full_name, course_name FROM students WHERE course_name = ? OR program = ? ORDER BY full_name");
        if ($stmt2) {
            $stmt2->bind_param("ss", $cn, $cn);
            $stmt2->execute();
            $res2 = $stmt2->get_result();
            while ($row = $res2->fetch_assoc()) {
                $results[] = ['student_number' => $row['student_number'] ?? $row['id'], 'id' => $row['id'], 'full_name' => $row['full_name'], 'course_name' => $row['course_name'] ?? ''];
            }
            $stmt2->close();
        }
    }
    $stmt->close();
}

$seen = []; $unique = [];
foreach ($results as $r) { if (!isset($seen[$r['id']])) { $seen[$r['id']] = true; $unique[] = $r; } }
echo json_encode($unique);
