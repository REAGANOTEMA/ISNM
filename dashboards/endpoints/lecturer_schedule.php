<?php
require_once __DIR__ . '/../../includes/staff_dashboard_access.php';
$ctx = bootstrapStaffDashboard([]);
header('Content-Type: application/json');

$user_id = (int)($ctx['user']['id'] ?? 0);
$conn = $ctx['staff'];
if (!$user_id || !$conn) { echo json_encode([]); exit; }

$results = [];

$stmt = $conn->prepare("SELECT course_name, day_of_week, start_time, end_time FROM academic_timetable WHERE staff_id = ? ORDER BY FIELD(day_of_week, 'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'), start_time");
if ($stmt) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) { $results[] = $row; }
    $stmt->close();
}

if (empty($results)) {
    $stmt2 = $conn->prepare("SELECT course_name, day_of_week, start_time, end_time FROM lecture_schedule WHERE lecturer_id = ? ORDER BY FIELD(day_of_week, 'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'), start_time");
    if ($stmt2) {
        $stmt2->bind_param("i", $user_id);
        $stmt2->execute();
        $res2 = $stmt2->get_result();
        while ($row = $res2->fetch_assoc()) { $results[] = $row; }
        $stmt2->close();
    }
}

echo json_encode($results);
