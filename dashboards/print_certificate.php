<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
$ctx = bootstrapStaffDashboard(['registrar','director','academics','principal']);
$staff_conn = $ctx['staff'];
$students_conn = $ctx['students'];

require_once __DIR__ . '/../includes/certificate_generator.php';
require_once __DIR__ . '/../includes/document_settings.php';
$docSettings = loadDocumentSettings();

$student_id = isset($_GET['student_id']) ? (int)$_GET['student_id'] : 0;
if ($student_id <= 0) {
    header('Location: document_management.php');
    exit;
}

$stmt = $students_conn->prepare("SELECT * FROM students WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $student_id);
if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
$student_result = $stmt->get_result();
$student = $student_result ? $student_result->fetch_assoc() : null;
$stmt->close();

if (!$student) {
    $_SESSION['error'] = 'Student not found.';
    header('Location: document_management.php');
    exit;
}
$student['full_name'] = $student['full_name'] ?: trim(($student['first_name']??'') . ' ' . ($student['surname']??'') . ($student['other_name'] ? ' ' . $student['other_name'] : ''));
$student['registration_number'] = $student['registration_number'] ?: $student['student_number'] ?: '';

$stmt = $staff_conn->prepare("SELECT * FROM academic_records WHERE student_id = ? ORDER BY academic_year, semester");
$stmt->bind_param("i", $student_id);
if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
$records_result = $stmt->get_result();
$records = $records_result ? $records_result->fetch_all(MYSQLI_ASSOC) : [];
$stmt->close();

$total_points = 0; $total_credits = 0;
foreach ($records as $r) {
    $g = strtoupper($r['grade'] ?? '');
    $gp = 0.0;
    if ($g === 'A') $gp = 4.0;
    elseif ($g === 'B' || $g === 'B+') $gp = 3.5;
    elseif ($g === 'C' || $g === 'C+') $gp = 3.0;
    elseif ($g === 'D') $gp = 2.0;
    elseif ($g === 'E') $gp = 1.0;
    $cr = (float)($r['credits'] ?? 0);
    $total_points += $gp * $cr;
    $total_credits += $cr;
}
$gpa = $total_credits > 0 ? round($total_points / $total_credits, 2) : 0;
$class = $gpa >= 3.5 ? 'First Class' : ($gpa >= 3.0 ? 'Second Class Upper' : ($gpa >= 2.0 ? 'Second Class Lower' : ($gpa >= 1.0 ? 'Pass' : 'Fail')));

$data = [
    'student_name'      => $student['full_name'],
    'registration_number' => $student['registration_number'],
    'program'           => $student['program'] ?: $student['course'] ?: '',
    'program_duration'  => $student['current_year'] ? 'Year ' . $student['current_year'] : '',
    'academic_year'     => date('Y'),
    'completion_date'   => date('F j, Y'),
    'grade'             => $gpa >= 2.0 ? 'Pass' : 'Fail',
    'gpa'               => number_format($gpa, 2),
    'class'             => $class,
    'principal_name'    => $docSettings['principal_name'] ?? '_______________________',
    'director_name'     => $docSettings['director_name'] ?? '_______________________',
];
echo generateCertificateHTML($data);
echo '<script>window.onload=function(){window.print();setTimeout(function(){window.close()},1000)};</script>';
