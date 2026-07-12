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
    $stmt = $students_conn->prepare("SELECT id, full_name, student_number, registration_number, program, gender, date_of_birth, phone, email, level, status FROM students WHERE full_name LIKE ? OR student_number LIKE ? OR registration_number LIKE ? OR phone LIKE ? ORDER BY surname,first_name LIMIT 30");
    $stmt->bind_param("ssss", $like, $like, $like, $like);
    if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
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
    $fn  = trim($_POST['first_name'] ?? '');
    $sn  = trim($_POST['surname'] ?? '');
    $on  = trim($_POST['other_name'] ?? '');
    $gen = trim($_POST['gender'] ?? 'Other');
    $crs = trim($_POST['course'] ?? '');
    $ph  = trim($_POST['phone'] ?? '');
    $em  = trim($_POST['email'] ?? '');
    
    if (empty($fn) || empty($sn)) {
        echo json_encode(['success' => false, 'error' => 'First name and surname required']);
        exit;
    }
    
    $snum = 'STU'.date('Y').str_pad(mt_rand(1,9999),4,'0',STR_PAD_LEFT);
    $full = trim("$fn $on $sn");
    $reg = 'REG-'.date('Y').str_pad(mt_rand(1,99999),5,'0',STR_PAD_LEFT);
    
    $stmt = $students_conn->prepare("INSERT INTO students (student_number, registration_number, first_name, surname, other_name, full_name, gender, course, program, phone, email, status, created_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,'Active',NOW())");
    $stmt->bind_param("sssssssssss", $snum, $reg, $fn, $sn, $on, $full, $gen, $crs, $crs, $ph, $em);
    if ($stmt->execute()) {
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
    $stmt->close();
    exit;
}

if ($action === 'get_student_detail') {
    $sid = intval($_GET['student_id'] ?? 0);
    if ($sid <= 0) { echo json_encode(['error' => 'Invalid student']); exit; }
    
    $stmt = $students_conn->prepare("SELECT * FROM students WHERE id = ?");
    $stmt->bind_param("i", $sid);
    if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
    $r = $stmt->get_result();
    $student = $r ? $r->fetch_assoc() : null;
    $stmt->close();
    if (!$student) { echo json_encode(['error' => 'Student not found']); exit; }
    
    // Get exam records count
    $stmt2 = $staff_conn->prepare("SELECT COUNT(*) c FROM examination_records WHERE student_id = ?");
    $stmt2->bind_param("i", $sid);
    if (!$stmt2->execute()) { error_log('$stmt2 execute failed: ' . ($stmt2->error ?? 'unknown')); };
    $er = $stmt2->get_result();
    $er_row = $er ? $er->fetch_assoc() : null;
    $exam_count = $er_row ? intval($er_row['c']) : 0;
    $stmt2->close();
    
    // Get courses
    $stmt3 = $staff_conn->prepare("SELECT COUNT(*) c FROM course_registrations WHERE student_id = ?");
    $stmt3->bind_param("i", $sid);
    if (!$stmt3->execute()) { error_log('$stmt3 execute failed: ' . ($stmt3->error ?? 'unknown')); };
    $cr = $stmt3->get_result();
    $cr_row = $cr ? $cr->fetch_assoc() : null;
    $course_count = $cr_row ? intval($cr_row['c']) : 0;
    $stmt3->close();
    
    // Get invoices/payments summary
    $stmt4 = $students_conn->prepare("SELECT COALESCE(SUM(total_amount),0) ti FROM student_invoices WHERE student_id = ?");
    $stmt4->bind_param("i", $sid);
    if (!$stmt4->execute()) { error_log('$stmt4 execute failed: ' . ($stmt4->error ?? 'unknown')); };
    $inv_r = $stmt4->get_result();
    $inv_row = $inv_r ? $inv_r->fetch_assoc() : null;
    $total_inv = $inv_row ? floatval($inv_row['ti']) : 0;
    $stmt4->close();
    
    $stmt5 = $students_conn->prepare("SELECT COALESCE(SUM(amount_received),0) tp FROM payments WHERE student_id = ?");
    $stmt5->bind_param("i", $sid);
    if (!$stmt5->execute()) { error_log('$stmt5 execute failed: ' . ($stmt5->error ?? 'unknown')); };
    $pay_r = $stmt5->get_result();
    $pay_row = $pay_r ? $pay_r->fetch_assoc() : null;
    $total_pay = $pay_row ? floatval($pay_row['tp']) : 0;
    $stmt5->close();
    
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
    $stmt = $students_conn->prepare("SELECT * FROM students WHERE id = ?");
    $stmt->bind_param("i", $sid);
    if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
    $r = $stmt->get_result();
    $student = $r ? $r->fetch_assoc() : null;
    $stmt->close();
    if (!$student) { echo json_encode(['error' => 'Student not found']); exit; }
    
    // Fetch courses/exam records
    $courses = [];
    $stmt2 = $staff_conn->prepare("SELECT er.*, cc.course_name, cc.credit_hours FROM examination_records er LEFT JOIN academic_course_catalog cc ON er.course_code = cc.course_code WHERE er.student_id = ? AND er.marks_obtained IS NOT NULL ORDER BY er.created_at ASC");
    $stmt2->bind_param("i", $sid);
    if (!$stmt2->execute()) { error_log('$stmt2 execute failed: ' . ($stmt2->error ?? 'unknown')); };
    $er = $stmt2->get_result();
    if ($er) while ($row = $er->fetch_assoc()) $courses[] = $row;
    $stmt2->close();
    
    // If no exam records, get course registrations
    if (empty($courses)) {
        $stmt3 = $staff_conn->prepare("SELECT cr.*, cc.course_name, cc.course_code, cc.credit_hours FROM course_registrations cr LEFT JOIN academic_course_catalog cc ON cr.course_id = cc.id WHERE cr.student_id = ? AND cr.status='Approved'");
        $stmt3->bind_param("i", $sid);
        if (!$stmt3->execute()) { error_log('$stmt3 execute failed: ' . ($stmt3->error ?? 'unknown')); };
        $cr = $stmt3->get_result();
        if ($cr) while ($row = $cr->fetch_assoc()) $courses[] = $row;
        $stmt3->close();
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
    
    // Store in database (transactional)
    $staff_conn->begin_transaction();
    try {
        $title = "Academic Transcript - ".($student['full_name']??'');
        $stmt4 = $staff_conn->prepare("INSERT INTO generated_documents (document_type, student_id, generated_by, document_title, document_content, generation_date) VALUES ('Transcript', ?, 1, ?, ?, NOW())");
        $stmt4->bind_param("iss", $sid, $title, $html);
        if (!$stmt4->execute()) { error_log('$stmt4 execute failed: ' . ($stmt4->error ?? 'unknown')); };
        $doc_id = $staff_conn->insert_id;
        $stmt4->close();
        
        // Also update registrar_transcripts
        $course_val = $student['program']??'';
        $academic_year = date('Y');
        $stmt5 = $staff_conn->prepare("INSERT INTO registrar_transcripts (transcript_number, student_id, academic_year, program, transcript_status, request_date, generated_by) VALUES (?, ?, ?, ?, 'Ready', NOW(), 1)");
        $stmt5->bind_param("siss", $tnum, $sid, $academic_year, $course_val);
        if (!$stmt5->execute()) { error_log('$stmt5 execute failed: ' . ($stmt5->error ?? 'unknown')); };
        $stmt5->close();
        
        $staff_conn->commit();
        echo json_encode([
            'success' => true,
            'doc_id' => $doc_id,
            'transcript_number' => $tnum,
            'preview_url' => "ajax/registrar_documents_ajax.php?action=preview_document&doc_id=$doc_id",
            'message' => 'Transcript generated successfully'
        ]);
    } catch (Exception $e) {
        $staff_conn->rollback();
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

if ($action === 'generate_certificate') {
    $sid = intval($_GET['student_id'] ?? 0);
    $cert_type = $_GET['cert_type'] ?? 'Certificate';
    if ($sid <= 0) { echo json_encode(['error' => 'Invalid student']); exit; }
    
    // Fetch student
    $stmt = $students_conn->prepare("SELECT * FROM students WHERE id = ?");
    $stmt->bind_param("i", $sid);
    if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
    $r = $stmt->get_result();
    $student = $r ? $r->fetch_assoc() : null;
    $stmt->close();
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
    $stmt2 = $staff_conn->prepare("SELECT AVG(marks_obtained) avg_m FROM examination_records WHERE student_id = ? AND marks_obtained IS NOT NULL");
    $stmt2->bind_param("i", $sid);
    if (!$stmt2->execute()) { error_log('$stmt2 execute failed: ' . ($stmt2->error ?? 'unknown')); };
    $gpa_r = $stmt2->get_result();
    if ($gpa_r && $gpa_row = $gpa_r->fetch_assoc()) {
        $avg = floatval($gpa_row['avg_m'] ?? 0);
        if ($avg >= 80) $class_of_award = 'First Class Honours';
        elseif ($avg >= 70) $class_of_award = 'Second Class Upper Division';
        elseif ($avg >= 60) $class_of_award = 'Second Class Lower Division';
        elseif ($avg >= 50) $class_of_award = 'Pass';
        else $class_of_award = 'Fail';
    }
    $stmt2->close();
    
    // Generate certificate number
    $cnum = 'CERT'.date('Ymd').str_pad(mt_rand(100,999),3,'0',STR_PAD_LEFT);
    
    // Generate HTML
    $html = generateProfessionalCertificate($student, $settings, $cert_type, $cnum, $class_of_award);
    
    // Store in database (transactional)
    $staff_conn->begin_transaction();
    try {
        $title = "Certificate of $cert_type - ".($student['full_name']??'');
        $stmt3 = $staff_conn->prepare("INSERT INTO generated_documents (document_type, student_id, generated_by, document_title, document_content, generation_date) VALUES ('Certificate', ?, 1, ?, ?, NOW())");
        $stmt3->bind_param("iss", $sid, $title, $html);
        if (!$stmt3->execute()) { error_log('$stmt3 execute failed: ' . ($stmt3->error ?? 'unknown')); };
        $doc_id = $staff_conn->insert_id;
        $stmt3->close();
        
        // Also update registrar_certificates
        $full_name_val = $student['full_name']??'';
        $course_val = $student['program']??'';
        $stmt4 = $staff_conn->prepare("INSERT INTO registrar_certificates (certificate_number, student_id, full_name, program, certificate_type, status, generated_by, generated_date) VALUES (?, ?, ?, ?, ?, 'Generated', 1, NOW())");
        $stmt4->bind_param("siss", $cnum, $sid, $full_name_val, $course_val, $cert_type);
        if (!$stmt4->execute()) { error_log('$stmt4 execute failed: ' . ($stmt4->error ?? 'unknown')); };
        $stmt4->close();
        
        $staff_conn->commit();
        echo json_encode([
            'success' => true,
            'doc_id' => $doc_id,
            'certificate_number' => $cnum,
            'preview_url' => "ajax/registrar_documents_ajax.php?action=preview_document&doc_id=$doc_id",
            'message' => 'Certificate generated successfully'
        ]);
    } catch (Exception $e) {
        $staff_conn->rollback();
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

if ($action === 'preview_document') {
    $doc_id = intval($_GET['doc_id'] ?? 0);
    if ($doc_id <= 0) { echo "Invalid document"; exit; }
    
    $stmt = $staff_conn->prepare("SELECT document_content, document_title, document_type FROM generated_documents WHERE id = ?");
    $stmt->bind_param("i", $doc_id);
    if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
    $r = $stmt->get_result();
    $doc = $r ? $r->fetch_assoc() : null;
    $stmt->close();
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
    
    $stmt = $students_conn->prepare("SELECT * FROM students WHERE id = ?");
    $stmt->bind_param("i", $sid);
    if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
    $r = $stmt->get_result();
    $student = $r ? $r->fetch_assoc() : null;
    $stmt->close();
    if (!$student) { echo json_encode(['error' => 'Student not found']); exit; }
    
    $settings = [];
    $sr = $staff_conn->query("SELECT setting_key, setting_value FROM registrar_settings");
    if ($sr) while ($row = $sr->fetch_assoc()) $settings[$row['setting_key']] = $row['setting_value'];
    if (empty($settings)) $settings = ['institution_name' => 'ISNM', 'current_academic_year' => date('Y')];
    
    // Transcript
    $courses = [];
    $stmt2 = $staff_conn->prepare("SELECT er.*, cc.course_name, cc.credit_hours FROM examination_records er LEFT JOIN academic_course_catalog cc ON er.course_code = cc.course_code WHERE er.student_id = ? AND er.marks_obtained IS NOT NULL ORDER BY er.created_at ASC");
    $stmt2->bind_param("i", $sid);
    if (!$stmt2->execute()) { error_log('$stmt2 execute failed: ' . ($stmt2->error ?? 'unknown')); };
    $er = $stmt2->get_result();
    if ($er) while ($row = $er->fetch_assoc()) $courses[] = $row;
    $stmt2->close();
    
    $staff_conn->begin_transaction();
    try {
        $tnum = 'T'.date('Ymd').str_pad(mt_rand(100,999),3,'0',STR_PAD_LEFT);
        $t_html = generateProfessionalTranscript($student, $courses, $settings, $tnum);
        $title_t = "Academic Transcript - ".($student['full_name']??'');
        $stmt3 = $staff_conn->prepare("INSERT INTO generated_documents (document_type, student_id, generated_by, document_title, document_content, generation_date) VALUES ('Transcript', ?, 1, ?, ?, NOW())");
        $stmt3->bind_param("iss", $sid, $title_t, $t_html);
        if (!$stmt3->execute()) { error_log('$stmt3 execute failed: ' . ($stmt3->error ?? 'unknown')); };
        $t_doc_id = $staff_conn->insert_id;
        $stmt3->close();
        
        // Certificate
        $class_of_award = '';
        $stmt4 = $staff_conn->prepare("SELECT AVG(marks_obtained) avg_m FROM examination_records WHERE student_id = ? AND marks_obtained IS NOT NULL");
        $stmt4->bind_param("i", $sid);
        if (!$stmt4->execute()) { error_log('$stmt4 execute failed: ' . ($stmt4->error ?? 'unknown')); };
        $gpa_r = $stmt4->get_result();
        if ($gpa_r && $gpa_row = $gpa_r->fetch_assoc()) {
            $avg = floatval($gpa_row['avg_m'] ?? 0);
            if ($avg >= 80) $class_of_award = 'First Class Honours';
            elseif ($avg >= 70) $class_of_award = 'Second Class Upper Division';
            elseif ($avg >= 60) $class_of_award = 'Second Class Lower Division';
            elseif ($avg >= 50) $class_of_award = 'Pass';
            else $class_of_award = 'Fail';
        }
        $stmt4->close();
        
        $cnum = 'CERT'.date('Ymd').str_pad(mt_rand(100,999),3,'0',STR_PAD_LEFT);
        $c_html = generateProfessionalCertificate($student, $settings, 'Diploma', $cnum, $class_of_award);
        $title_c = "Certificate - ".($student['full_name']??'');
        $stmt5 = $staff_conn->prepare("INSERT INTO generated_documents (document_type, student_id, generated_by, document_title, document_content, generation_date) VALUES ('Certificate', ?, 1, ?, ?, NOW())");
        $stmt5->bind_param("iss", $sid, $title_c, $c_html);
        if (!$stmt5->execute()) { error_log('$stmt5 execute failed: ' . ($stmt5->error ?? 'unknown')); };
        $c_doc_id = $staff_conn->insert_id;
        $stmt5->close();
        
        $staff_conn->commit();
        echo json_encode([
            'success' => true,
            'transcript_doc_id' => $t_doc_id,
            'certificate_doc_id' => $c_doc_id,
            'transcript_number' => $tnum,
            'certificate_number' => $cnum,
            'message' => 'Both transcript and certificate generated successfully'
        ]);
    } catch (Exception $e) {
        $staff_conn->rollback();
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

echo json_encode(['error' => 'Unknown action']);
exit;
