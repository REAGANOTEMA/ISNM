<?php
require_once __DIR__ . '/../../includes/staff_dashboard_access.php';
$ctx = bootstrapStaffDashboard(['lecturer']);
header('Content-Type: application/json');

$user_id = (int)($ctx['user']['id'] ?? 0);
$conn = $ctx['staff'];
if (!$user_id || !$conn) { echo json_encode(['appeals' => []]); exit; }

$conn->query("CREATE TABLE IF NOT EXISTS grade_appeals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    student_name VARCHAR(255) DEFAULT '',
    course_name VARCHAR(255) DEFAULT '',
    lecturer_id INT DEFAULT 0,
    reason TEXT,
    notes TEXT,
    status VARCHAR(50) DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$results = [];
$stmt = $conn->prepare("SELECT student_name, reason, notes, status FROM grade_appeals WHERE lecturer_id = ? ORDER BY created_at DESC");
if ($stmt) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) { $results[] = $row; }
    $stmt->close();
}

echo json_encode(['appeals' => $results]);
