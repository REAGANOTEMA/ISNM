<?php
error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
require '../../assets/config.php';

header('Content-Type: application/json');

$studentId = (int) ($_SESSION['student_id'] ?? $_SESSION['uid'] ?? $_SESSION['user_id'] ?? 0);
if ($studentId <= 0) {
    http_response_code(403);
    echo json_encode([['error' => 'Student authentication required']]);
    exit;
}

$studentsConn = function_exists('getStudentsConnection') ? getStudentsConnection() : null;
if (!$studentsConn) {
    http_response_code(500);
    echo json_encode([['error' => 'Database error']]);
    exit;
}
$studentStmt = $studentsConn->prepare('SELECT id FROM students WHERE id = ? AND (status IS NULL OR status <> \'deleted\') LIMIT 1');
if (!$studentStmt) {
    http_response_code(500);
    echo json_encode([['error' => 'Database error']]);
    exit;
}
$studentStmt->bind_param('i', $studentId);
$studentStmt->execute();
$studentResult = $studentStmt->get_result();
$studentExists = $studentResult && $studentResult->num_rows > 0;
$studentStmt->close();
if (!$studentExists) {
    http_response_code(403);
    echo json_encode([['error' => 'Student authentication required']]);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$requestedId = (int) ($input['id'] ?? $_POST['id'] ?? 0);
if ($requestedId !== 0 && $requestedId !== $studentId) {
    http_response_code(403);
    echo json_encode([['error' => 'You may only view your own results']]);
    exit;
}

$output = [];
$stmt = $conn->prepare("SELECT `exam_id`, `marks` FROM `marks` WHERE `student_id` = ? ORDER BY `s_no` DESC LIMIT 10");
if (!$stmt) {
    http_response_code(500);
    echo json_encode([['error' => 'Database error']]);
    exit;
}
$stmt->bind_param('i', $studentId);
if (!$stmt->execute()) {
    $stmt->close();
    http_response_code(500);
    echo json_encode([['error' => 'Unable to load results']]);
    exit;
}
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $examStmt = $conn->prepare("SELECT `exam_id`, `exam_title`, `passing_marks`, `timestamp` FROM `exams` WHERE `exam_id` = ? LIMIT 1");
    if (!$examStmt) {
        continue;
    }
    $examStmt->bind_param('s', $row['exam_id']);
    $examStmt->execute();
    $examResult = $examStmt->get_result();
    $exam = $examResult ? $examResult->fetch_assoc() : null;
    $examStmt->close();
    if (!$exam) {
        continue;
    }

    $mark = (int) $row['marks'];
    $output[] = [
        's_id' => $studentId,
        'exam_id' => $exam['exam_id'],
        'exam_name' => $exam['exam_title'],
        'date' => date('d-m-Y', strtotime($exam['timestamp'])),
        'marks' => $mark,
        'status' => $mark >= (int) $exam['passing_marks'] ? 'Pass' : 'Fail',
    ];
}
$stmt->close();

if (!$output) {
    $output[] = ['error' => 'No results available'];
}
echo json_encode($output);