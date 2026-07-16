<?php
require_once __DIR__ . '/../../includes/staff_dashboard_access.php';
$ctx = bootstrapStaffDashboard([]);
header('Content-Type: application/json');

$user_id = (int)($ctx['user']['id'] ?? 0);
$conn = $ctx['staff'];
if (!$user_id || !$conn) { echo json_encode([]); exit; }

$distribution = [];
$stmt = $conn->prepare("SELECT ar.grade, COUNT(*) AS cnt FROM academic_records ar WHERE (ar.staff_id = ? OR ar.lecturer_id = ?) AND ar.grade IS NOT NULL AND ar.grade != '' GROUP BY ar.grade ORDER BY ar.grade");
if ($stmt) {
    $stmt->bind_param("ii", $user_id, $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) { $distribution[$row['grade']] = (int)$row['cnt']; }
    $stmt->close();
}

if (empty($distribution)) {
    $stmt2 = $conn->prepare("SELECT ar.grade, COUNT(*) AS cnt FROM academic_records ar JOIN course_assignments ca ON ar.course_name = ca.course_name WHERE ca.staff_id = ? AND ar.grade IS NOT NULL AND ar.grade != '' GROUP BY ar.grade ORDER BY ar.grade");
    if ($stmt2) {
        $stmt2->bind_param("i", $user_id);
        $stmt2->execute();
        $res2 = $stmt2->get_result();
        while ($row = $res2->fetch_assoc()) { $distribution[$row['grade']] = (int)$row['cnt']; }
        $stmt2->close();
    }
}

echo json_encode($distribution);
