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
    echo '<!DOCTYPE html><html><head><style>body{font-family:sans-serif;display:flex;align-items:center;justify-content:center;height:100vh;margin:0;background:#f8fafc;color:#334155;}.error-box{text-align:center;padding:40px;background:#fff;border-radius:12px;box-shadow:0 4px 24px rgba(0,0,0,.08);max-width:480px;}.error-box h2{color:#dc2626;margin:0 0 8px;}.error-box p{color:#64748b;margin:0;}</style></head><body><div class="error-box"><h2>Authentication Required</h2><p>Please log in to access certificates.</p></div></body></html>';
    exit;
}

$is_staff = !empty($_SESSION['user_id']);
$is_student = !empty($_SESSION['student_id']) && ($_SESSION['type'] ?? '') === 'student';

// Role-based access control for staff
if ($is_staff) {
    require_once __DIR__ . '/config/database.php';
    $role = $_SESSION['role'] ?? '';
    if (empty($role) && function_exists('getStaffConnection')) {
        $sdb = getStaffConnection();
        if ($sdb) {
            $uid = (int)$_SESSION['user_id'];
            $r = $sdb->query("SELECT sr.role_name FROM staff s JOIN staff_roles sr ON s.role_id=sr.id WHERE s.id = $uid LIMIT 1");
            if ($r) { $row = $r->fetch_assoc(); $role = $row['role_name'] ?? ''; }
        }
    }
    $allowed_roles = ['academic registrar', 'registrar', 'director academics', 'director general', 'system admin', 'principal'];
    $role_lower = strtolower($role);
    $has_access = false;
    foreach ($allowed_roles as $ar) {
        if (strpos($role_lower, $ar) !== false) { $has_access = true; break; }
    }
    if (!$has_access) {
        header('Content-Type: text/html; charset=utf-8');
        echo '<!DOCTYPE html><html><head><style>body{font-family:sans-serif;display:flex;align-items:center;justify-content:center;height:100vh;margin:0;background:#f8fafc;color:#334155;}.error-box{text-align:center;padding:40px;background:#fff;border-radius:12px;box-shadow:0 4px 24px rgba(0,0,0,.08);max-width:480px;}.error-box h2{color:#dc2626;margin:0 0 8px;}.error-box p{color:#64748b;margin:0;}</style></head><body><div class="error-box"><h2>Access Denied</h2><p>You do not have permission to access certificate generation. Required role: Academic Registrar, Director, or Principal.</p></div></body></html>';
        exit;
    }
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
            $q = $studentsDb->prepare("SELECT first_name, surname, other_name, student_number, program FROM students WHERE id = ? OR student_number = ? LIMIT 1");
            if ($q) {
                $q->bind_param('is', $student_id, $student_id);
                if (!$q->execute()) { error_log('$q execute failed: ' . ($q->error ?? 'unknown')); };
                $s = $q->get_result()->fetch_assoc();
                $q->close();
                if ($s) {
                    $data['student_name'] = trim($s['first_name'] . ' ' . ($s['surname'] ?? '') . ($s['other_name'] ? ' ' . $s['other_name'] : ''));
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
