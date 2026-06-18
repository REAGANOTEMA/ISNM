<?php
/**
 * Certificate Printing Endpoint
 * Generates professional certificate HTML for printing
 */
require_once __DIR__ . '/includes/certificate_generator.php';

// Authentication
session_start();
if (!isset($_SESSION['user_id']) && !isset($_SESSION['student_id'])) {
    header('Content-Type: text/html; charset=utf-8');
    echo generateCertificateHTML([]);
    exit;
}

$student_id = $_GET['student_id'] ?? $_GET['id'] ?? null;
$type = $_GET['type'] ?? 'completion';
$print = isset($_GET['print']);

// Gather data (default to sample if no student_id)
$data = [
    'certificate_type' => $type === 'completion' ? 'Certificate of Completion' : 'Certificate of Achievement',
    'student_name' => 'Student Name',
    'registration_number' => 'ISNM/2024/0001',
    'program' => 'Certificate in Nursing',
    'program_duration' => '2.5 Years',
    'academic_year' => date('Y'),
    'completion_date' => date('F j, Y'),
    'grade' => 'Pass',
    'class' => 'Second Class',
    'principal_name' => 'Principal Name',
    'director_name' => 'Director General',
    'certificate_number' => 'ISNM/CERT/' . date('Y') . '/' . strtoupper(substr(uniqid(), -6)),
    'issue_date' => date('F j, Y'),
];

// Try to lookup student data if ID provided
if ($student_id) {
    try {
        $staffDb = null;
        if (function_exists('getDatabaseConnection')) $staffDb = getDatabaseConnection('staffs');
        elseif (function_exists('getStaffConnection')) $staffDb = getStaffConnection();
        
        if ($staffDb) {
            $q = $staffDb->prepare("SELECT first_name, surname, other_names, student_number, program FROM students WHERE id = ? OR student_number = ? LIMIT 1");
            if ($q) {
                $q->bind_param('is', $student_id, $student_id);
                $q->execute();
                $s = $q->get_result()->fetch_assoc();
                $q->close();
                if ($s) {
                    $data['student_name'] = $s['first_name'] . ' ' . $s['surname'] . ($s['other_names'] ? ' ' . $s['other_names'] : '');
                    $data['registration_number'] = $s['student_number'] ?? $data['registration_number'];
                    $data['program'] = $s['program'] ?? $data['program'];
                }
            }
        }
    } catch (Exception $e) {}
}

$html = generateCertificateHTML($data);

if ($print) {
    echo '<script>window.onload = function() { window.print(); };</script>';
}
echo $html;
