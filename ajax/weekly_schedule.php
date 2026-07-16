<?php
header('Content-Type: application/json');
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['user_id']) || ($_SESSION['type'] ?? '') !== 'staff') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../config/database.php';

$conn = getStaffConnection();
if (!$conn) {
    echo json_encode(['success' => false, 'error' => 'Database unavailable']);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action === 'get_weekly') {
    $userId = (int)($_SESSION['user_id'] ?? 0);
    $data = [];

    $stmt = $conn->prepare(
        "SELECT day_of_week AS day, start_time, end_time, course_code AS course, venue
         FROM academic_timetable
         WHERE lecturer_id = ? AND timetable_status != 'Cancelled'
         ORDER BY FIELD(day_of_week, 'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'), start_time"
    );
    if ($stmt) {
        $stmt->bind_param('i', $userId);
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            if ($result) $data = $result->fetch_all(MYSQLI_ASSOC);
        }
        $stmt->close();
    }

    echo json_encode(['success' => true, 'data' => $data]);
    exit;
}

echo json_encode(['success' => false, 'error' => 'Unknown action']);
