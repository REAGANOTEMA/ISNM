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
    header('Location: staff_transcript_generation.php');
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
    header('Location: staff_transcript_generation.php');
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

$type = $_GET['type'] ?? $docSettings['transcript_default_type'] ?? 'transcript';
echo generateTranscriptHTML($student, $records, $type, $docSettings);
echo '<script>window.onload=function(){window.print();setTimeout(function(){window.close()},1000)};</script>';
