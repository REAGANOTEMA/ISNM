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

// Gather data (default to error message if no student_id found)
$data = [];
$error_msg = '';

if (!$student_id) {
    $error_msg = 'No student specified. Please provide a valid student ID.';
}

// Try to lookup student data if ID provided
if ($student_id) {
    try {
        require_once __DIR__ . '/config/database.php';
        $studentsDb = function_exists('getStudentsConnection') ? getStudentsConnection() : null;
        
        if (!$studentsDb) {
            $studentsDb = @new mysqli(STUDENTS_DB_HOST, STUDENTS_DB_USER, STUDENTS_DB_PASS, STUDENTS_DB_NAME, STUDENTS_DB_PORT);
        }
        
        if ($studentsDb && !$studentsDb->connect_error) {
            $q = $studentsDb->prepare("SELECT first_name, surname, other_names, student_number, program FROM students WHERE id = ? OR student_number = ? LIMIT 1");
            if ($q) {
                $q->bind_param('is', $student_id, $student_id);
                if (!$q->execute()) { error_log('$q execute failed: ' . ($q->error ?? 'unknown')); };
                $s = $q->get_result()->fetch_assoc();
                $q->close();
                if ($s) {
                    $data['student_name'] = trim($s['first_name'] . ' ' . ($s['surname'] ?? '') . ($s['other_names'] ? ' ' . $s['other_names'] : ''));
                    $data['registration_number'] = $s['student_number'] ?? '';
                    $data['program'] = $s['program'] ?? '';
                } else {
                    $error_msg = 'Student not found in the database.';
                }
            }
            $studentsDb->close();
        } else {
            $error_msg = 'Database connection failed.';
        }
    } catch (Exception $e) {
        error_log('Certificate DB lookup: ' . $e->getMessage());
    }
}

if ($error_msg) {
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html><html><head><style>body{font-family:sans-serif;display:flex;align-items:center;justify-content:center;height:100vh;margin:0;background:#f8fafc;color:#334155;}.error-box{text-align:center;padding:40px;background:#fff;border-radius:12px;box-shadow:0 4px 24px rgba(0,0,0,.08);max-width:480px;}.error-box h2{color:#dc2626;margin:0 0 8px;}.error-box p{color:#64748b;margin:0;}</style></head><body><div class="error-box"><h2>Certificate Error</h2><p>' . htmlspecialchars($error_msg) . '</p></div></body></html>';
    exit;
}

$html = generateCertificateHTML($data);

if ($print) {
    echo '<script>window.onload = function() { window.print(); };</script>';
}
echo $html;
