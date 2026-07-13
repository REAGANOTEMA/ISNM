<?php
include("config.php");
header('Content-Type: application/json');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['status' => 'NODATA']); exit; }

$input = json_decode(file_get_contents('php://input'), true);
$class = trim($input['class'] ?? '');
$section = trim($input['section'] ?? '');
$totalMarks = floatval($input['totalMarks'] ?? 100);
$subject = trim($input['subject'] ?? '');

if (empty($class)) { echo json_encode(['status' => 'NODATA']); exit; }

try {
    $params = [$class];
    $types = 's';
    $sql = "SELECT id, CONCAT(fname, ' ', lname) AS full_name, class FROM students WHERE class=?";
    if ($section) { $sql .= " AND section=?"; $params[] = $section; $types .= 's'; }
    $sql .= " ORDER BY lname, fname";

    $stmt = $conn->prepare($sql);
    if (!$stmt) { echo json_encode(['status' => 'NODATA']); exit; }
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) { $stmt->close(); echo json_encode(['status' => 'NODATA']); exit; }

    $head = '<tr><th>#</th><th>Student Name</th><th>' . htmlspecialchars($subject ?: 'Marks') . ' (/' . $totalMarks . ')</th></tr>';
    $body = '';
    $i = 1;
    while ($row = $result->fetch_assoc()) {
        $body .= '<tr><td>' . $i . '</td><td>' . htmlspecialchars($row['full_name']) . '</td>';
        $body .= '<td><input type="number" class="marks-input form-control form-control-sm" min="0" max="' . $totalMarks . '" data-student-id="' . $row['id'] . '" name="students[' . $row['id'] . '][ca]" placeholder="CA"></td>';
        $body .= '<td><input type="number" class="marks-input form-control form-control-sm" min="0" max="' . $totalMarks . '" data-student-id="' . $row['id'] . '" name="students[' . $row['id'] . '][exam]" placeholder="Exam"></td></tr>';
        $i++;
    }
    $stmt->close();
    echo json_encode(['status' => 'DATA', 'head' => $head, 'body' => $body, 'class' => $class, 'section' => $section]);
} catch (Exception $e) { error_log('getStudentsForResult error: ' . $e->getMessage()); echo json_encode(['status' => 'NODATA']); }
