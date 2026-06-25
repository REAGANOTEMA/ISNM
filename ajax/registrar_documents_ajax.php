<?php
/**
 * AJAX Document Generator for Academic Registrar
 * Handles student lookup, document generation, and preview
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/registrar_document_templates.php';

header('Content-Type: application/json');

// Authentication check
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['user_id']) || ($_SESSION['type'] ?? '') !== 'staff') {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$action = $_GET['action'] ?? '';

$students_conn = getStudentsConnection();
$staff_conn = getStaffConnection();

if ($action === 'lookup_student') {
    $q = trim($_GET['q'] ?? '');
    if (strlen($q) < 2) {
        echo json_encode([]); exit;
    }
    $like = "%$q%";
    $stmt = $students_conn->prepare("SELECT id, full_name, student_number, registration_number, course, gender, date_of_birth, phone, email, current_year, status FROM students WHERE full_name LIKE ? OR student_number LIKE ? OR registration_number LIKE ? OR phone LIKE ? ORDER BY surname,first_name LIMIT 30");
    $stmt->bind_param("ssss", $like, $like, $like, $like);
    $stmt->execute();
    $result = $stmt->get_result();
    $students = [];
    while ($row = $result->fetch_assoc()) {
        $students[] = $row;
    }
    $stmt->close();
    echo json_encode($students);
    exit;
}

if ($action === 'create_student') {
    $fn  = $students_conn->real_escape_string(trim($_POST['first_name'] ?? ''));
    $sn  = $students_conn->real_escape_string(trim($_POST['surname'] ?? ''));
    $on  = $students_conn->real_escape_string(trim($_POST['other_name'] ?? ''));
    $gen = $students_conn->real_escape_string(trim($_POST['gender'] ?? 'Other'));
    $crs = $students_conn->real_escape_string(trim($_POST['course'] ?? ''));
    $ph  = $students_conn->real_escape_string(trim($_POST['phone'] ?? ''));
    $em  = $students_conn->real_escape_string(trim($_POST['email'] ?? ''));
    
    if (empty($fn) || empty($sn)) {
        echo json_encode(['success' => false, 'error' => 'First name and surname required']);
        exit;
    }
    
    $snum = 'STU'.date('Y').str_pad(mt_rand(1,9999),4,'0',STR_PAD_LEFT);
    $full = trim("$fn $on $sn");
    $reg = 'REG-'.date('Y').str_pad(mt_rand(1,99999),5,'0',STR_PAD_LEFT);
    
    $sql = "INSERT INTO students (student_number, registration_number, first_name, surname, other_name, full_name, gender, course, program, phone, email, status, created_at) VALUES ('$snum','$reg','$fn','$sn','$on','$full','$gen','$crs','$crs','$ph','$em','Active',NOW())";
    if ($students_conn->query($sql)) {
        $id = $students_conn->insert_id;
        echo json_encode(['success' => true, 'student' => [
            'id' => $id,
            'full_name' => $full,
            'student_number' => $snum,
            'registration_number' => $reg,
            'course' => $crs,
            'gender' => $gen,
            'phone' => $ph,
            'email' => $em
        ]]);
    } else {
        echo json_encode(['success' => false, 'error' => $students_conn->error]);
    }
    exit;
}

if ($action === 'get_student_detail') {
    $sid = intval($_GET['student_id'] ?? 0);
    if ($sid <= 0) { echo json_encode(['error' => 'Invalid student']); exit; }
    
    $r = $students_conn->query("SELECT * FROM students WHERE id=$sid");
    $student = $r ? $r->fetch_assoc() : null;
    if (!$student) { echo json_encode(['error' => 'Student not found']); exit; }
    
    // Get exam records count
    $er = $staff_conn->query("SELECT COUNT(*) c FROM examination_records WHERE student_id=$sid");
    $er_row = $er ? $er->fetch_assoc() : null;
    $exam_count = $er_row ? intval($er_row['c']) : 0;
    
    // Get courses
    $cr = $staff_conn->query("SELECT COUNT(*) c FROM course_registrations WHERE student_id=$sid");
    $cr_row = $cr ? $cr->fetch_assoc() : null;
    $course_count = $cr_row ? intval($cr_row['c']) : 0;
    
    // Get invoices/payments summary
    $inv_r = $students_conn->query("SELECT COALESCE(SUM(total_amount),0) ti FROM student_invoices WHERE student_id=$sid");
    $inv_row = $inv_r ? $inv_r->fetch_assoc() : null;
    $total_inv = $inv_row ? floatval($inv_row['ti']) : 0;
    $pay_r = $students_conn->query("SELECT COALESCE(SUM(amount_received),0) tp FROM payments WHERE student_id=$sid");
    $pay_row = $pay_r ? $pay_r->fetch_assoc() : null;
    $total_pay = $pay_row ? floatval($pay_row['tp']) : 0;
    
    $student['exam_count'] = $exam_count;
    $student['course_count'] = $course_count;
    $student['total_invoiced'] = floatval($total_inv);
    $student['total_paid'] = floatval($total_pay);
    $student['balance'] = floatval($total_inv) - floatval($total_pay);
    
    echo json_encode(['success' => true, 'student' => $student]);
    exit;
}

if ($action === 'generate_transcript') {
    $sid = intval($_GET['student_id'] ?? 0);
    if ($sid <= 0) { echo json_encode(['error' => 'Invalid student']); exit; }
    
    // Fetch student
    $r = $students_conn->query("SELECT * FROM students WHERE id=$sid");
    $student = $r ? $r->fetch_assoc() : null;
    if (!$student) { echo json_encode(['error' => 'Student not found']); exit; }
    
    // Fetch courses/exam records
    $courses = [];
    $er = $staff_conn->query("SELECT er.*, cc.course_name, cc.credit_hours FROM examination_records er LEFT JOIN academic_course_catalog cc ON er.course_code = cc.course_code WHERE er.student_id=$sid AND er.marks_obtained IS NOT NULL ORDER BY er.created_at ASC");
    if ($er) while ($row = $er->fetch_assoc()) $courses[] = $row;
    
    // If no exam records, get course registrations
    if (empty($courses)) {
        $cr = $staff_conn->query("SELECT cr.*, cc.course_name, cc.course_code, cc.credit_hours FROM course_registrations cr LEFT JOIN academic_course_catalog cc ON cr.course_id = cc.id WHERE cr.student_id=$sid AND cr.status='Approved'");
        if ($cr) while ($row = $cr->fetch_assoc()) $courses[] = $row;
    }
    
    // Fetch settings
    $settings = [];
    $sr = $staff_conn->query("SELECT setting_key, setting_value FROM registrar_settings");
    if ($sr) while ($row = $sr->fetch_assoc()) $settings[$row['setting_key']] = $row['setting_value'];
    if (empty($settings)) {
        $settings = [
            'institution_name' => 'ISNM',
            'current_academic_year' => date('Y'),
            'transcript_fee' => '50000'
        ];
    }
    
    // Generate transcript number
    $tnum = 'T'.date('Ymd').str_pad(mt_rand(100,999),3,'0',STR_PAD_LEFT);
    
    // Generate HTML
    $html = generateProfessionalTranscript($student, $courses, $settings, $tnum);
    
    // Store in database
    $title = $students_conn->real_escape_string("Academic Transcript - ".($student['full_name']??''));
    $html_escaped = $staff_conn->real_escape_string($html);
    $staff_conn->query("INSERT INTO generated_documents (document_type, student_id, generated_by, document_title, document_content, generation_date) VALUES ('Transcript', $sid, 1, '$title', '$html_escaped', NOW())");
    $doc_id = $staff_conn->insert_id;
    
    // Also update registrar_transcripts
    $course_esc = $staff_conn->real_escape_string($student['course']??'');
    $staff_conn->query("INSERT INTO registrar_transcripts (transcript_number, student_id, academic_year, program, transcript_status, request_date, generated_by) VALUES ('$tnum', $sid, '".date('Y')."', '$course_esc', 'Ready', NOW(), 1)");
    
    echo json_encode([
        'success' => true,
        'doc_id' => $doc_id,
        'transcript_number' => $tnum,
        'preview_url' => "ajax/registrar_documents_ajax.php?action=preview_document&doc_id=$doc_id",
        'message' => 'Transcript generated successfully'
    ]);
    exit;
}

if ($action === 'generate_certificate') {
    $sid = intval($_GET['student_id'] ?? 0);
    $cert_type = $_GET['cert_type'] ?? 'Certificate';
    if ($sid <= 0) { echo json_encode(['error' => 'Invalid student']); exit; }
    
    // Fetch student
    $r = $students_conn->query("SELECT * FROM students WHERE id=$sid");
    $student = $r ? $r->fetch_assoc() : null;
    if (!$student) { echo json_encode(['error' => 'Student not found']); exit; }
    
    // Fetch settings
    $settings = [];
    $sr = $staff_conn->query("SELECT setting_key, setting_value FROM registrar_settings");
    if ($sr) while ($row = $sr->fetch_assoc()) $settings[$row['setting_key']] = $row['setting_value'];
    if (empty($settings)) {
        $settings = ['institution_name' => 'ISNM', 'current_academic_year' => date('Y')];
    }
    
    // Determine class of award from GPA
    $class_of_award = '';
    $gpa_r = $staff_conn->query("SELECT AVG(marks_obtained) avg_m FROM examination_records WHERE student_id=$sid AND marks_obtained IS NOT NULL");
    if ($gpa_r && $gpa_row = $gpa_r->fetch_assoc()) {
        $avg = floatval($gpa_row['avg_m'] ?? 0);
        if ($avg >= 80) $class_of_award = 'First Class Honours';
        elseif ($avg >= 70) $class_of_award = 'Second Class Upper Division';
        elseif ($avg >= 60) $class_of_award = 'Second Class Lower Division';
        elseif ($avg >= 50) $class_of_award = 'Pass';
        else $class_of_award = 'Fail';
    }
    
    // Generate certificate number
    $cnum = 'CERT'.date('Ymd').str_pad(mt_rand(100,999),3,'0',STR_PAD_LEFT);
    
    // Generate HTML
    $html = generateProfessionalCertificate($student, $settings, $cert_type, $cnum, $class_of_award);
    
    // Store in database
    $title = $students_conn->real_escape_string("Certificate of $cert_type - ".($student['full_name']??''));
    $html_escaped = $staff_conn->real_escape_string($html);
    $staff_conn->query("INSERT INTO generated_documents (document_type, student_id, generated_by, document_title, document_content, generation_date) VALUES ('Certificate', $sid, 1, '$title', '$html_escaped', NOW())");
    $doc_id = $staff_conn->insert_id;
    
    // Also update registrar_certificates
    $cert_type_esc = $staff_conn->real_escape_string($cert_type);
    $full_name_esc = $staff_conn->real_escape_string($student['full_name']??'');
    $course_esc = $staff_conn->real_escape_string($student['course']??'');
    $staff_conn->query("INSERT INTO registrar_certificates (certificate_number, student_id, full_name, program, certificate_type, status, generated_by, generated_date) VALUES ('$cnum', $sid, '$full_name_esc', '$course_esc', '$cert_type_esc', 'Generated', 1, NOW())");
    
    echo json_encode([
        'success' => true,
        'doc_id' => $doc_id,
        'certificate_number' => $cnum,
        'preview_url' => "ajax/registrar_documents_ajax.php?action=preview_document&doc_id=$doc_id",
        'message' => 'Certificate generated successfully'
    ]);
    exit;
}

if ($action === 'preview_document') {
    $doc_id = intval($_GET['doc_id'] ?? 0);
    if ($doc_id <= 0) { echo "Invalid document"; exit; }
    
    $r = $staff_conn->query("SELECT document_content, document_title, document_type FROM generated_documents WHERE id=$doc_id");
    $doc = $r ? $r->fetch_assoc() : null;
    if (!$doc) { echo "Document not found"; exit; }
    
    header('Content-Type: text/html; charset=utf-8');
    echo $doc['document_content'];
    exit;
}

if ($action === 'auto_generate_all') {
    $sid = intval($_GET['student_id'] ?? 0);
    if ($sid <= 0) { echo json_encode(['error' => 'Invalid student']); exit; }
    
    // Generate both transcript and certificate
    $_GET['student_id'] = $sid;
    
    // Generate transcript
    $_GET['action'] = 'generate_transcript';
    // We need to re-execute the logic... let's just call the functions directly
    
    $r = $students_conn->query("SELECT * FROM students WHERE id=$sid");
    $student = $r ? $r->fetch_assoc() : null;
    if (!$student) { echo json_encode(['error' => 'Student not found']); exit; }
    
    $settings = [];
    $sr = $staff_conn->query("SELECT setting_key, setting_value FROM registrar_settings");
    if ($sr) while ($row = $sr->fetch_assoc()) $settings[$row['setting_key']] = $row['setting_value'];
    if (empty($settings)) $settings = ['institution_name' => 'ISNM', 'current_academic_year' => date('Y')];
    
    // Transcript
    $courses = [];
    $er = $staff_conn->query("SELECT er.*, cc.course_name, cc.credit_hours FROM examination_records er LEFT JOIN academic_course_catalog cc ON er.course_code = cc.course_code WHERE er.student_id=$sid AND er.marks_obtained IS NOT NULL ORDER BY er.created_at ASC");
    if ($er) while ($row = $er->fetch_assoc()) $courses[] = $row;
    
    $tnum = 'T'.date('Ymd').str_pad(mt_rand(100,999),3,'0',STR_PAD_LEFT);
    $t_html = generateProfessionalTranscript($student, $courses, $settings, $tnum);
    $title_t = $staff_conn->real_escape_string("Academic Transcript - ".($student['full_name']??''));
    $t_esc = $staff_conn->real_escape_string($t_html);
    $staff_conn->query("INSERT INTO generated_documents (document_type, student_id, generated_by, document_title, document_content, generation_date) VALUES ('Transcript', $sid, 1, '$title_t', '$t_esc', NOW())");
    $t_doc_id = $staff_conn->insert_id;
    
    // Certificate
    $class_of_award = '';
    $gpa_r = $staff_conn->query("SELECT AVG(marks_obtained) avg_m FROM examination_records WHERE student_id=$sid AND marks_obtained IS NOT NULL");
    if ($gpa_r && $gpa_row = $gpa_r->fetch_assoc()) {
        $avg = floatval($gpa_row['avg_m'] ?? 0);
        if ($avg >= 80) $class_of_award = 'First Class Honours';
        elseif ($avg >= 70) $class_of_award = 'Second Class Upper Division';
        elseif ($avg >= 60) $class_of_award = 'Second Class Lower Division';
        elseif ($avg >= 50) $class_of_award = 'Pass';
        else $class_of_award = 'Fail';
    }
    
    $cnum = 'CERT'.date('Ymd').str_pad(mt_rand(100,999),3,'0',STR_PAD_LEFT);
    $c_html = generateProfessionalCertificate($student, $settings, 'Diploma', $cnum, $class_of_award);
    $title_c = $staff_conn->real_escape_string("Certificate - ".($student['full_name']??''));
    $c_esc = $staff_conn->real_escape_string($c_html);
    $staff_conn->query("INSERT INTO generated_documents (document_type, student_id, generated_by, document_title, document_content, generation_date) VALUES ('Certificate', $sid, 1, '$title_c', '$c_esc', NOW())");
    $c_doc_id = $staff_conn->insert_id;
    
    echo json_encode([
        'success' => true,
        'transcript_doc_id' => $t_doc_id,
        'certificate_doc_id' => $c_doc_id,
        'transcript_number' => $tnum,
        'certificate_number' => $cnum,
        'message' => 'Both transcript and certificate generated successfully'
    ]);
    exit;
}

echo json_encode(['error' => 'Unknown action']);
exit;
