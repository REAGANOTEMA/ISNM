<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
require_once __DIR__ . '/../includes/enterprise_auth.php';
require_once __DIR__ . '/../includes/website_submissions_widget.php';
$ctx = bootstrapStaffDashboard(['academic registrar', 'registrar', 'director academics', 'director general']);
$auth_service = $ctx['auth'];
$user = $ctx['user'];
$staff = $ctx['staff'];
$students = $ctx['students'];
$website = $ctx['website'];
$website_conn = $website;
$user_role = $_SESSION['role'] ?? '';
if (isset($_GET['page']) && !isset($_GET['section'])) $_GET['section'] = $_GET['page'];
$section = $_GET['section'] ?? 'dashboard';
$user_id = (int)($user['id'] ?? 0);
$user_name = $user['full_name'] ?? 'Academic Registrar';

$students_db = defined('STUDENTS_DB_NAME') ? STUDENTS_DB_NAME : 'igangaschoolofl_students_db';
$staff_db = defined('STAFF_DB_NAME') ? STAFF_DB_NAME : 'igangaschoolofl_staffs_db';

if (!function_exists('safeCount')) {
function safeCount($conn, $sql) {
    if (!$conn) return 0;
    $r = $conn->query($sql);
    if (!$r) return 0;
    $row = $r->fetch_assoc();
    return intval($row['c'] ?? $row['COUNT(*)'] ?? 0);
}
}

if (!function_exists('getGradeFromMarks')) {
function getGradeFromMarks($marks, $total = 100) {
    if ($total <= 0) return 'F';
    $pct = ($marks / $total) * 100;
    if ($pct >= 80) return 'A';
    if ($pct >= 75) return 'B+';
    if ($pct >= 70) return 'B';
    if ($pct >= 65) return 'C+';
    if ($pct >= 60) return 'C';
    if ($pct >= 55) return 'D+';
    if ($pct >= 50) return 'D';
    return 'F';
}
}

if (!function_exists('calculateGPA')) {
function calculateGPA($marks, $total = 100) {
    if ($total <= 0) return 0.0;
    $pct = ($marks / $total) * 100;
    if ($pct >= 80) return 4.0;
    if ($pct >= 75) return 3.7;
    if ($pct >= 70) return 3.3;
    if ($pct >= 65) return 3.0;
    if ($pct >= 60) return 2.7;
    if ($pct >= 55) return 2.3;
    if ($pct >= 50) return 2.0;
    return 0.0;
}
}

if (!function_exists('logAudit')) {
function logAudit($staff_conn, $user_id, $action, $entity_type, $entity_id, $description) {
    if (!$staff_conn) return;
    try {
        $stmt = $staff_conn->prepare("INSERT INTO academic_audit_logs (user_id, action, entity_type, entity_id, description) VALUES (?, ?, ?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param('issis', $user_id, $action, $entity_type, $entity_id, $description);
            $stmt->execute();
            $stmt->close();
        }
    } catch (Exception $e) {}
}
}

if (!function_exists('getStudentName')) {
function getStudentName($students_conn, $id) {
    if (!$students_conn) return 'Unknown';
    $stmt = $students_conn->prepare("SELECT full_name FROM students WHERE id = ?");
    if (!$stmt) return 'Unknown';
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $r = $stmt->get_result();
    $row = $r->fetch_assoc();
    $stmt->close();
    return $row ? $row['full_name'] : 'Unknown';
}
}

if (!function_exists('getProgramOptions')) {
function getProgramOptions($staff_conn) {
    $opts = [];
    if (!$staff_conn) return $opts;
    $r = $staff_conn->query("SELECT id, program_code, program_name FROM academic_programs WHERE status='Active' ORDER BY program_name");
    if ($r) while ($row = $r->fetch_assoc()) $opts[] = $row;
    return $opts;
}
}

if (!function_exists('getCourseOptions')) {
function getCourseOptions($staff_conn) {
    $opts = [];
    if (!$staff_conn) return $opts;
    $r = $staff_conn->query("SELECT id, course_code, course_title FROM academic_course_catalog WHERE status='Active' ORDER BY course_code");
    if ($r) while ($row = $r->fetch_assoc()) $opts[] = $row;
    return $opts;
}
}

$autoMigrate = [
    "CREATE TABLE IF NOT EXISTS `{$staff_db}`.`academic_calendar` (id INT AUTO_INCREMENT PRIMARY KEY, academic_year VARCHAR(20) NOT NULL, start_date DATE NULL, end_date DATE NULL, is_current TINYINT(1) DEFAULT 0, status VARCHAR(50) DEFAULT 'Active', created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, UNIQUE KEY uq_year (academic_year)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    "CREATE TABLE IF NOT EXISTS `{$staff_db}`.`semesters` (id INT AUTO_INCREMENT PRIMARY KEY, academic_year VARCHAR(20) NOT NULL, semester_name VARCHAR(100) NOT NULL, start_date DATE NULL, end_date DATE NULL, is_current TINYINT(1) DEFAULT 0, status VARCHAR(50) DEFAULT 'Active', created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    "CREATE TABLE IF NOT EXISTS `{$staff_db}`.`intakes` (id INT AUTO_INCREMENT PRIMARY KEY, intake_name VARCHAR(200) NOT NULL, academic_year VARCHAR(20) NOT NULL, start_date DATE NULL, end_date DATE NULL, status VARCHAR(50) DEFAULT 'Open', created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    "CREATE TABLE IF NOT EXISTS `{$staff_db}`.`transcripts` (id INT AUTO_INCREMENT PRIMARY KEY, student_id INT NOT NULL, transcript_number VARCHAR(50) UNIQUE, template_id INT DEFAULT 0, academic_year VARCHAR(20) DEFAULT NULL, semester VARCHAR(100) DEFAULT NULL, total_credit_hours DECIMAL(10,2) DEFAULT 0.00, cumulative_gpa DECIMAL(4,2) DEFAULT 0.00, status VARCHAR(50) DEFAULT 'Draft', generated_by INT DEFAULT 0, generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, is_archived TINYINT(1) DEFAULT 0, is_downloadable TINYINT(1) DEFAULT 0, KEY idx_student (student_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    "CREATE TABLE IF NOT EXISTS `{$staff_db}`.`transcript_items` (id INT AUTO_INCREMENT PRIMARY KEY, transcript_id INT NOT NULL, course_code VARCHAR(50) NOT NULL, course_title VARCHAR(300) DEFAULT '', credit_hours DECIMAL(5,2) DEFAULT 0.00, marks_obtained DECIMAL(8,2) DEFAULT 0.00, grade VARCHAR(5) DEFAULT '', grade_point DECIMAL(4,2) DEFAULT 0.00, academic_year VARCHAR(20) DEFAULT NULL, semester VARCHAR(100) DEFAULT NULL, KEY idx_transcript (transcript_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    "CREATE TABLE IF NOT EXISTS `{$staff_db}`.`transcript_templates` (id INT AUTO_INCREMENT PRIMARY KEY, template_name VARCHAR(200) NOT NULL, template_html TEXT, orientation VARCHAR(20) DEFAULT 'portrait', is_default TINYINT(1) DEFAULT 0, status VARCHAR(50) DEFAULT 'Active', created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    "CREATE TABLE IF NOT EXISTS `{$staff_db}`.`certificates` (id INT AUTO_INCREMENT PRIMARY KEY, student_id INT NOT NULL, certificate_number VARCHAR(50) UNIQUE, template_id INT DEFAULT 0, certificate_type VARCHAR(100) DEFAULT 'Certificate', program_name VARCHAR(300) DEFAULT NULL, completion_date DATE NULL, issue_date DATE DEFAULT NULL, status VARCHAR(50) DEFAULT 'Draft', generated_by INT DEFAULT 0, generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, is_archived TINYINT(1) DEFAULT 0, is_downloadable TINYINT(1) DEFAULT 0, KEY idx_student (student_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    "CREATE TABLE IF NOT EXISTS `{$staff_db}`.`certificate_templates` (id INT AUTO_INCREMENT PRIMARY KEY, template_name VARCHAR(200) NOT NULL, template_html TEXT, orientation VARCHAR(20) DEFAULT 'landscape', is_default TINYINT(1) DEFAULT 0, status VARCHAR(50) DEFAULT 'Active', created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    "CREATE TABLE IF NOT EXISTS `{$staff_db}`.`certificate_uploads` (id INT AUTO_INCREMENT PRIMARY KEY, certificate_id INT NOT NULL, file_name VARCHAR(300) DEFAULT '', file_path VARCHAR(500) DEFAULT '', file_size INT DEFAULT 0, mime_type VARCHAR(100) DEFAULT '', uploaded_by INT DEFAULT 0, uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, KEY idx_certificate (certificate_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    "CREATE TABLE IF NOT EXISTS `{$staff_db}`.`certificate_verification` (id INT AUTO_INCREMENT PRIMARY KEY, certificate_number VARCHAR(50) NOT NULL, verified_by VARCHAR(200) DEFAULT NULL, verification_reference VARCHAR(100) DEFAULT NULL, verification_status VARCHAR(50) DEFAULT 'Verified', verified_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, KEY idx_cert_number (certificate_number)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    "CREATE TABLE IF NOT EXISTS `{$staff_db}`.`graduation_candidates` (id INT AUTO_INCREMENT PRIMARY KEY, student_id INT NOT NULL, program_id INT DEFAULT 0, academic_year VARCHAR(20) DEFAULT NULL, graduation_year VARCHAR(20) DEFAULT NULL, status VARCHAR(50) DEFAULT 'Pending', remarks TEXT, submitted_by INT DEFAULT 0, submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, UNIQUE KEY uq_student_program (student_id, program_id), KEY idx_status (status)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    "CREATE TABLE IF NOT EXISTS `{$staff_db}`.`graduation_approvals` (id INT AUTO_INCREMENT PRIMARY KEY, candidate_id INT NOT NULL, approved_by INT DEFAULT 0, approval_level VARCHAR(100) DEFAULT 'Registrar', status VARCHAR(50) DEFAULT 'Pending', remarks TEXT, approved_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, KEY idx_candidate (candidate_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    "CREATE TABLE IF NOT EXISTS `{$staff_db}`.`student_progression` (id INT AUTO_INCREMENT PRIMARY KEY, student_id INT NOT NULL, from_year INT DEFAULT 0, to_year INT DEFAULT 0, from_semester VARCHAR(100) DEFAULT NULL, to_semester VARCHAR(100) DEFAULT NULL, academic_year VARCHAR(20) DEFAULT NULL, progression_type VARCHAR(50) DEFAULT 'Promotion', approved_by INT DEFAULT 0, status VARCHAR(50) DEFAULT 'Approved', created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, KEY idx_student (student_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    "CREATE TABLE IF NOT EXISTS `{$staff_db}`.`gpa_settings` (id INT AUTO_INCREMENT PRIMARY KEY, setting_key VARCHAR(100) UNIQUE NOT NULL, setting_value TEXT, description VARCHAR(500) DEFAULT NULL, updated_by INT DEFAULT 0, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    "CREATE TABLE IF NOT EXISTS `{$staff_db}`.`result_publications` (id INT AUTO_INCREMENT PRIMARY KEY, academic_year VARCHAR(20) NOT NULL, semester VARCHAR(100) NOT NULL, published_by INT DEFAULT 0, published_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, status VARCHAR(50) DEFAULT 'Published', UNIQUE KEY uq_pub (academic_year, semester)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    "CREATE TABLE IF NOT EXISTS `{$staff_db}`.`national_exam_results` (id INT AUTO_INCREMENT PRIMARY KEY, student_id INT NOT NULL, course_code VARCHAR(50) NOT NULL, exam_body VARCHAR(100) DEFAULT 'UNEB', marks_obtained DECIMAL(8,2) DEFAULT 0.00, grade VARCHAR(5) DEFAULT '', academic_year VARCHAR(20) DEFAULT NULL, semester VARCHAR(100) DEFAULT NULL, status VARCHAR(50) DEFAULT 'Pending', entered_by INT DEFAULT 0, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, KEY idx_student (student_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    "CREATE TABLE IF NOT EXISTS `{$staff_db}`.`clinical_assessments` (id INT AUTO_INCREMENT PRIMARY KEY, student_id INT NOT NULL, clinical_placement_id INT DEFAULT 0, assessment_type VARCHAR(100) DEFAULT '', score DECIMAL(8,2) DEFAULT 0.00, max_score DECIMAL(8,2) DEFAULT 100.00, grade VARCHAR(5) DEFAULT '', assessed_by INT DEFAULT 0, assessment_date DATE DEFAULT NULL, remarks TEXT, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, KEY idx_student (student_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    "CREATE TABLE IF NOT EXISTS `{$staff_db}`.`clinical_placements` (id INT AUTO_INCREMENT PRIMARY KEY, student_id INT NOT NULL, facility_name VARCHAR(300) NOT NULL, department VARCHAR(200) DEFAULT '', start_date DATE DEFAULT NULL, end_date DATE DEFAULT NULL, supervisor_name VARCHAR(200) DEFAULT '', supervisor_phone VARCHAR(50) DEFAULT '', status VARCHAR(50) DEFAULT 'Active', created_by INT DEFAULT 0, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, KEY idx_student (student_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    "CREATE TABLE IF NOT EXISTS `{$staff_db}`.`academic_approvals` (id INT AUTO_INCREMENT PRIMARY KEY, entity_type VARCHAR(100) NOT NULL, entity_id INT NOT NULL, requested_by INT DEFAULT 0, approved_by INT DEFAULT 0, approval_level VARCHAR(100) DEFAULT 'Registrar', status VARCHAR(50) DEFAULT 'Pending', remarks TEXT, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, approved_at DATETIME DEFAULT NULL, KEY idx_entity (entity_type, entity_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    "CREATE TABLE IF NOT EXISTS `{$staff_db}`.`academic_audit_logs` (id INT AUTO_INCREMENT PRIMARY KEY, user_id INT NOT NULL, action VARCHAR(200) NOT NULL, entity_type VARCHAR(100) DEFAULT '', entity_id INT DEFAULT 0, description TEXT, ip_address VARCHAR(50) DEFAULT NULL, user_agent VARCHAR(500) DEFAULT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, KEY idx_user (user_id), KEY idx_action (action), KEY idx_entity (entity_type, entity_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    "CREATE TABLE IF NOT EXISTS `{$staff_db}`.`grade_scales` (id INT AUTO_INCREMENT PRIMARY KEY, grade_letter VARCHAR(5) NOT NULL, grade_point DECIMAL(4,2) DEFAULT 0.00, min_percentage DECIMAL(5,2) DEFAULT 0.00, max_percentage DECIMAL(5,2) DEFAULT 100.00, status VARCHAR(50) DEFAULT 'Active', UNIQUE KEY uq_grade (grade_letter)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    "CREATE TABLE IF NOT EXISTS `{$staff_db}`.`communications` (id INT AUTO_INCREMENT PRIMARY KEY, recipient_type VARCHAR(50) DEFAULT 'student', recipient_id INT DEFAULT 0, subject VARCHAR(300) NOT NULL, message TEXT NOT NULL, channel VARCHAR(50) DEFAULT 'portal', sent_by INT DEFAULT 0, is_read TINYINT(1) DEFAULT 0, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    "CREATE TABLE IF NOT EXISTS `{$staff_db}`.`registrar_student_registration` (id INT AUTO_INCREMENT PRIMARY KEY, student_id INT NOT NULL, academic_year VARCHAR(20) NOT NULL, semester VARCHAR(100) NOT NULL, registration_date DATE DEFAULT NULL, registration_status VARCHAR(50) DEFAULT 'Registered', registered_by INT DEFAULT 0, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, KEY idx_student (student_id), KEY idx_year (academic_year)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    "CREATE TABLE IF NOT EXISTS `{$staff_db}`.`academic_programs` (id INT AUTO_INCREMENT PRIMARY KEY, program_code VARCHAR(50) NOT NULL UNIQUE, program_name VARCHAR(300) NOT NULL, program_type VARCHAR(100) DEFAULT '', department VARCHAR(200) DEFAULT '', duration_years INT DEFAULT 3, status VARCHAR(50) DEFAULT 'Active', created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    "CREATE TABLE IF NOT EXISTS `{$staff_db}`.`academic_course_catalog` (id INT AUTO_INCREMENT PRIMARY KEY, course_code VARCHAR(50) NOT NULL, course_title VARCHAR(300) NOT NULL, credits DECIMAL(5,2) DEFAULT 0.00, program_code VARCHAR(50) DEFAULT '', year_of_study INT DEFAULT 1, semester VARCHAR(100) DEFAULT '', status VARCHAR(50) DEFAULT 'Active', created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, KEY idx_program (program_code)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    "CREATE TABLE IF NOT EXISTS `{$staff_db}`.`examination_records` (id INT AUTO_INCREMENT PRIMARY KEY, student_id INT NOT NULL, course_code VARCHAR(50) NOT NULL, exam_type VARCHAR(50) DEFAULT 'Final', marks_obtained DECIMAL(8,2) DEFAULT 0.00, total_marks DECIMAL(8,2) DEFAULT 100.00, grade VARCHAR(5) DEFAULT '', continuous_assessment_marks DECIMAL(8,2) DEFAULT 0.00, final_exam_marks DECIMAL(8,2) DEFAULT 0.00, grade_status VARCHAR(50) DEFAULT 'Pending', created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, KEY idx_student (student_id), KEY idx_course (course_code)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    "CREATE TABLE IF NOT EXISTS `{$staff_db}`.`grading_approval_workflow` (id INT AUTO_INCREMENT PRIMARY KEY, workflow_number INT NOT NULL UNIQUE, examination_record_id INT DEFAULT 0, hod_status VARCHAR(50) DEFAULT 'Pending', registrar_status VARCHAR(50) DEFAULT 'Pending', principal_status VARCHAR(50) DEFAULT 'Pending', hod_approved_by INT DEFAULT 0, registrar_approved_by INT DEFAULT 0, principal_approved_by INT DEFAULT 0, current_stage VARCHAR(100) DEFAULT 'HOD', published_at DATETIME DEFAULT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, KEY idx_record (examination_record_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
];
if ($staff) {
    foreach ($autoMigrate as $ddl) {
        try { $staff->query($ddl); } catch (Exception $e) {}
    }
}

$redirectSection = $_POST['_section'] ?? '';
function redirectBack($hash = '') {
    $section = $GLOBALS['redirectSection'];
    $loc = 'academic-registrar.php';
    if ($hash) $loc .= '#' . $hash;
    elseif ($section) $loc .= '#' . $section;
    header('Location: ' . $loc);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $staff) {
    // Handle website submission actions
    if (function_exists('handleWebsiteSubmissionsAction')) {
        handleWebsiteSubmissionsAction($website_conn);
    }
    $action = $_POST['action'] ?? '';
    if ($action === 'add_academic_year') {
        $year = trim($_POST['academic_year'] ?? '');
        $start = $_POST['start_date'] ?? null;
        $end = $_POST['end_date'] ?? null;
        if ($year) {
            $stmt = $staff->prepare("INSERT INTO academic_calendar (academic_year, start_date, end_date, is_current, status) VALUES (?, ?, ?, 0, 'Active')");
            if ($stmt) {
                $stmt->bind_param('sss', $year, $start, $end);
                if ($stmt->execute()) { $_SESSION['success'] = "Academic year $year added."; logAudit($staff, $user_id, 'CREATE', 'academic_year', $staff->insert_id, "Added academic year $year"); }
                else $_SESSION['error'] = 'Failed: ' . $stmt->error;
                $stmt->close();
            }
        }
        redirectBack('academic-calendar');
    }
    if ($action === 'add_semester') {
        $year = trim($_POST['academic_year'] ?? ''); $sem = trim($_POST['semester_name'] ?? '');
        $start = $_POST['start_date'] ?? null; $end = $_POST['end_date'] ?? null;
        if ($year && $sem) {
            $stmt = $staff->prepare("INSERT INTO semesters (academic_year, semester_name, start_date, end_date, is_current, status) VALUES (?, ?, ?, ?, 0, 'Active')");
            if ($stmt) { $stmt->bind_param('ssss', $year, $sem, $start, $end);
                if ($stmt->execute()) { $_SESSION['success'] = "Semester $sem ($year) added."; logAudit($staff, $user_id, 'CREATE', 'semester', $staff->insert_id, "Added semester $sem ($year)"); }
                else $_SESSION['error'] = 'Failed: ' . $stmt->error; $stmt->close(); }
        }
        redirectBack('academic-calendar');
    }
    if ($action === 'add_program') {
        $code = trim($_POST['program_code'] ?? ''); $name = trim($_POST['program_name'] ?? '');
        $type = trim($_POST['program_type'] ?? ''); $dept = trim($_POST['department'] ?? '');
        $dur = intval($_POST['duration_years'] ?? 0);
        if ($code && $name) {
            $stmt = $staff->prepare("INSERT INTO academic_programs (program_code, program_name, program_type, department, duration_years, status) VALUES (?, ?, ?, ?, ?, 'Active')");
            if ($stmt) { $stmt->bind_param('ssssi', $code, $name, $type, $dept, $dur);
                if ($stmt->execute()) { $_SESSION['success'] = "Program $code added."; logAudit($staff, $user_id, 'CREATE', 'program', $staff->insert_id, "Added program $code - $name"); }
                else $_SESSION['error'] = 'Failed: ' . $stmt->error; $stmt->close(); }
        }
        redirectBack('programs');
    }
    if ($action === 'add_course') {
        $ccode = trim($_POST['course_code'] ?? ''); $ctitle = trim($_POST['course_title'] ?? '');
        $credits = floatval($_POST['credits'] ?? 0); $pcode = trim($_POST['program_code'] ?? '');
        $yos = intval($_POST['year_of_study'] ?? 1); $sem = trim($_POST['semester'] ?? '');
        if ($ccode && $ctitle) {
            $stmt = $staff->prepare("INSERT INTO academic_course_catalog (course_code, course_title, credits, program_code, year_of_study, semester, status) VALUES (?, ?, ?, ?, ?, ?, 'Active')");
            if ($stmt) { $stmt->bind_param('ssdsis', $ccode, $ctitle, $credits, $pcode, $yos, $sem);
                if ($stmt->execute()) { $_SESSION['success'] = "Course $ccode added."; logAudit($staff, $user_id, 'CREATE', 'course', $staff->insert_id, "Added course $ccode - $ctitle"); }
                else $_SESSION['error'] = 'Failed: ' . $stmt->error; $stmt->close(); }
        }
        redirectBack('courses');
    }
    if ($action === 'register_student') {
        $fn = trim($_POST['first_name'] ?? ''); $ln = trim($_POST['last_name'] ?? '');
        $gen = trim($_POST['gender'] ?? 'Other');
        $program = trim($_POST['program'] ?? ''); $level = intval($_POST['level'] ?? 1);
        $ph = trim($_POST['phone'] ?? '');
        $em = trim($_POST['email'] ?? '');
        if ($fn && $ln && $program) {
            $fullName = trim("$fn $ln"); $studentNum = 'STU-' . date('Y') . '-' . strtoupper(substr(uniqid(), -6));
            $stmt = $students->prepare("INSERT INTO students (student_number, full_name, first_name, last_name, program, level, status) VALUES (?, ?, ?, ?, ?, ?, 'Active')");
            if ($stmt) { $stmt->bind_param('sssssi', $studentNum, $fullName, $fn, $ln, $program, $level);
                if ($stmt->execute()) {
                    $sid = $stmt->insert_id; $ay = date('Y'); $semName = 'First Semester';
                    $stmt2 = $staff->prepare("INSERT INTO registrar_student_registration (student_id, academic_year, semester, registration_date, registration_status, registered_by) VALUES (?, ?, ?, CURDATE(), 'Registered', ?)");
                    if ($stmt2) { $stmt2->bind_param('issi', $sid, $ay, $semName, $user_id); $stmt2->execute(); $stmt2->close(); }
                    $_SESSION['success'] = "Student $fullName registered (#$studentNum)."; logAudit($staff, $user_id, 'CREATE', 'student', $sid, "Registered student $fullName ($studentNum)");
                } else $_SESSION['error'] = 'Failed: ' . $stmt->error; $stmt->close(); }
        } else $_SESSION['error'] = 'First name, last name, and program are required.';
        redirectBack('student-records');
    }
    if ($action === 'transfer_student') {
        $sid = intval($_POST['student_id'] ?? 0); $newProgram = trim($_POST['new_program'] ?? '');
        $newLevel = intval($_POST['new_level'] ?? 1); $remarks = trim($_POST['remarks'] ?? '');
        if ($sid && $newProgram) {
            $stmt = $students->prepare("UPDATE students SET program = ?, level = ? WHERE id = ?");
            if ($stmt) { $stmt->bind_param('sii', $newProgram, $newLevel, $sid);
                if ($stmt->execute()) {
                    $stmt2 = $staff->prepare("INSERT INTO student_progression (student_id, to_year, academic_year, progression_type, approved_by, status) VALUES (?, ?, ?, 'Transfer', ?, 'Approved')");
                    if ($stmt2) { $stmt2->bind_param('iisi', $sid, $newLevel, date('Y'), $user_id); $stmt2->execute(); $stmt2->close(); }
                    $_SESSION['success'] = 'Student transferred successfully.'; logAudit($staff, $user_id, 'TRANSFER', 'student', $sid, "Transferred to $newProgram level $newLevel. $remarks");
                } else $_SESSION['error'] = 'Failed: ' . $stmt->error; $stmt->close(); }
        } else $_SESSION['error'] = 'Student ID and new program required.';
        redirectBack('student-records');
    }
    if ($action === 'defer_student') {
        $sid = intval($_POST['student_id'] ?? 0); $remarks = trim($_POST['remarks'] ?? '');
        if ($sid) {
            $stmt = $students->prepare("UPDATE students SET status = 'Deferred' WHERE id = ?");
            if ($stmt) { $stmt->bind_param('i', $sid);
                if ($stmt->execute()) { $_SESSION['success'] = 'Student deferred.'; logAudit($staff, $user_id, 'DEFER', 'student', $sid, "Student deferred. $remarks"); }
                else $_SESSION['error'] = 'Failed: ' . $stmt->error; $stmt->close(); }
        }
        redirectBack('student-records');
    }
    if ($action === 'withdraw_student') {
        $sid = intval($_POST['student_id'] ?? 0); $remarks = trim($_POST['remarks'] ?? '');
        if ($sid) {
            $stmt = $students->prepare("UPDATE students SET status = 'Withdrawn' WHERE id = ?");
            if ($stmt) { $stmt->bind_param('i', $sid);
                if ($stmt->execute()) { $_SESSION['success'] = 'Student withdrawn.'; logAudit($staff, $user_id, 'WITHDRAW', 'student', $sid, "Student withdrawn. $remarks"); }
                else $_SESSION['error'] = 'Failed: ' . $stmt->error; $stmt->close(); }
        }
        redirectBack('student-records');
    }
    if ($action === 'readmit_student') {
        $sid = intval($_POST['student_id'] ?? 0); $remarks = trim($_POST['remarks'] ?? '');
        if ($sid) {
            $stmt = $students->prepare("UPDATE students SET status = 'Active' WHERE id = ?");
            if ($stmt) { $stmt->bind_param('i', $sid);
                if ($stmt->execute()) { $_SESSION['success'] = 'Student readmitted.'; logAudit($staff, $user_id, 'READMIT', 'student', $sid, "Student readmitted. $remarks"); }
                else $_SESSION['error'] = 'Failed: ' . $stmt->error; $stmt->close(); }
        }
        redirectBack('student-records');
    }
    if ($action === 'add_intake') {
        $iname = trim($_POST['intake_name'] ?? ''); $ay = trim($_POST['academic_year'] ?? '');
        $start = $_POST['start_date'] ?? null; $end = $_POST['end_date'] ?? null;
        if ($iname && $ay) {
            $stmt = $staff->prepare("INSERT INTO intakes (intake_name, academic_year, start_date, end_date, status) VALUES (?, ?, ?, ?, 'Open')");
            if ($stmt) { $stmt->bind_param('ssss', $iname, $ay, $start, $end);
                if ($stmt->execute()) { $_SESSION['success'] = "Intake $iname added."; logAudit($staff, $user_id, 'CREATE', 'intake', $staff->insert_id, "Added intake $iname ($ay)"); }
                else $_SESSION['error'] = 'Failed: ' . $stmt->error; $stmt->close(); }
        }
        redirectBack('intakes');
    }
    if ($action === 'enter_marks') {
        $sid = intval($_POST['student_id'] ?? 0); $courseCode = trim($_POST['course_code'] ?? '');
        $examType = trim($_POST['exam_type'] ?? 'Final'); $ca = floatval($_POST['continuous_assessment_marks'] ?? 0);
        $finalExam = floatval($_POST['final_exam_marks'] ?? 0); $total = floatval($_POST['total_marks'] ?? 100);
        $marksObtained = $ca + $finalExam; $grade = getGradeFromMarks($marksObtained, $total);
        $status = 'Pending';
        if ($sid && $courseCode) {
            $stmt = $staff->prepare("INSERT INTO examination_records (student_id, course_code, exam_type, marks_obtained, total_marks, grade, continuous_assessment_marks, final_exam_marks, grade_status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE marks_obtained=VALUES(marks_obtained), total_marks=VALUES(total_marks), grade=VALUES(grade), continuous_assessment_marks=VALUES(continuous_assessment_marks), final_exam_marks=VALUES(final_exam_marks), grade_status=VALUES(grade_status)");
            if ($stmt) { $stmt->bind_param('issdddsds', $sid, $courseCode, $examType, $marksObtained, $total, $grade, $ca, $finalExam, $status);
                if ($stmt->execute()) { $_SESSION['success'] = "Marks entered for $courseCode."; logAudit($staff, $user_id, 'ENTER_MARKS', 'examination_record', $staff->insert_id, "Entered marks $marksObtained/$total for student $sid course $courseCode"); }
                else $_SESSION['error'] = 'Failed: ' . $stmt->error; $stmt->close(); }
        } else $_SESSION['error'] = 'Student ID and course code required.';
        redirectBack('examinations');
    }
    if ($action === 'verify_marks') {
        $rid = intval($_POST['record_id'] ?? 0);
        if ($rid) {
            $stmt = $staff->prepare("UPDATE examination_records SET grade_status = 'Verified' WHERE id = ?");
            if ($stmt) { $stmt->bind_param('i', $rid);
                if ($stmt->execute()) { $_SESSION['success'] = 'Marks verified.'; logAudit($staff, $user_id, 'VERIFY', 'examination_record', $rid, 'Marks record verified'); }
                else $_SESSION['error'] = 'Failed: ' . $stmt->error; $stmt->close(); }
        }
        redirectBack('examinations');
    }
    if ($action === 'approve_results') {
        $wid = intval($_POST['workflow_id'] ?? 0); $remarks = trim($_POST['remarks'] ?? '');
        if ($wid) {
            $stmt = $staff->prepare("UPDATE grading_approval_workflow SET registrar_approved_by = ?, registrar_status = 'Approved', current_stage = 'Principal' WHERE workflow_number = ?");
            if ($stmt) { $stmt->bind_param('ii', $user_id, $wid);
                if ($stmt->execute()) { $_SESSION['success'] = 'Results approved at registrar level.'; logAudit($staff, $user_id, 'APPROVE', 'workflow', $wid, "Registrar approved workflow $wid. $remarks"); }
                else $_SESSION['error'] = 'Failed: ' . $stmt->error; $stmt->close(); }
        }
        redirectBack('approvals');
    }
    if ($action === 'generate_transcript') {
        $sid = intval($_POST['student_id'] ?? 0);
        if ($sid) {
            $tn = 'TRN-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
            $stmt = $staff->prepare("INSERT INTO transcripts (student_id, transcript_number, status, generated_by) VALUES (?, ?, 'Draft', ?)");
            if ($stmt) { $stmt->bind_param('isi', $sid, $tn, $user_id);
                if ($stmt->execute()) {
                    $trid = $stmt->insert_id;
                    $rstmt = $staff->prepare("SELECT course_code, marks_obtained, total_marks, grade, continuous_assessment_marks, final_exam_marks FROM examination_records WHERE student_id = ? AND grade_status = 'Verified'");
                    $rstmt->bind_param('i', $sid);
                    $rstmt->execute();
                    $res = $rstmt->get_result();
                    if ($res) {
                        while ($row = $res->fetch_assoc()) {
                            $gp = calculateGPA($row['marks_obtained'], $row['total_marks']);
                            $istmt = $staff->prepare("INSERT INTO transcript_items (transcript_id, course_code, marks_obtained, grade, grade_point) VALUES (?, ?, ?, ?, ?)");
                            if ($istmt) { $istmt->bind_param('idsds', $trid, $row['course_code'], $row['marks_obtained'], $row['grade'], $gp); $istmt->execute(); $istmt->close(); }
                        }
                        $res->close();
                    }
                    $_SESSION['success'] = "Transcript $tn generated."; logAudit($staff, $user_id, 'GENERATE', 'transcript', $trid, "Generated transcript $tn for student $sid");
                } else $_SESSION['error'] = 'Failed: ' . $stmt->error; $stmt->close(); }
        } else $_SESSION['error'] = 'Student ID required.';
        redirectBack('transcripts');
    }
    if ($action === 'generate_certificate') {
        $sid = intval($_POST['student_id'] ?? 0); $ctype = trim($_POST['certificate_type'] ?? 'Certificate');
        $compDate = $_POST['completion_date'] ?? null;
        if ($sid) {
            $cn = 'CERT-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
            $stmt = $staff->prepare("INSERT INTO certificates (student_id, certificate_number, certificate_type, completion_date, issue_date, status, generated_by) VALUES (?, ?, ?, ?, CURDATE(), 'Draft', ?)");
            if ($stmt) { $stmt->bind_param('isssi', $sid, $cn, $ctype, $compDate, $user_id);
                if ($stmt->execute()) { $_SESSION['success'] = "Certificate $cn generated."; logAudit($staff, $user_id, 'GENERATE', 'certificate', $stmt->insert_id, "Generated certificate $cn for student $sid"); }
                else $_SESSION['error'] = 'Failed: ' . $stmt->error; $stmt->close(); }
        } else $_SESSION['error'] = 'Student ID required.';
        redirectBack('certificates');
    }
    if ($action === 'add_to_graduation') {
        $sid = intval($_POST['student_id'] ?? 0); $pid = intval($_POST['program_id'] ?? 0);
        $gradYear = trim($_POST['graduation_year'] ?? date('Y')); $remarks = trim($_POST['remarks'] ?? '');
        if ($sid && $pid) {
            $stmt = $staff->prepare("INSERT INTO graduation_candidates (student_id, program_id, academic_year, graduation_year, status, remarks, submitted_by) VALUES (?, ?, ?, ?, 'Pending', ?, ?) ON DUPLICATE KEY UPDATE status='Pending', remarks=VALUES(remarks), submitted_by=VALUES(submitted_by)");
            if ($stmt) { $stmt->bind_param('iissis', $sid, $pid, $gradYear, $gradYear, $remarks, $user_id);
                if ($stmt->execute()) { $_SESSION['success'] = 'Student added to graduation candidates.'; logAudit($staff, $user_id, 'CREATE', 'graduation_candidate', $staff->insert_id, "Added student $sid to graduation candidates"); }
                else $_SESSION['error'] = 'Failed: ' . $stmt->error; $stmt->close(); }
        } else $_SESSION['error'] = 'Student ID and program required.';
        redirectBack('graduation');
    }
    if ($action === 'approve_graduation') {
        $cid = intval($_POST['candidate_id'] ?? 0); $remarks = trim($_POST['remarks'] ?? '');
        if ($cid) {
            $stmt = $staff->prepare("INSERT INTO graduation_approvals (candidate_id, approved_by, approval_level, status, remarks) VALUES (?, ?, 'Registrar', 'Approved', ?)");
            if ($stmt) { $stmt->bind_param('iis', $cid, $user_id, $remarks);
                if ($stmt->execute()) {
                    $stmt2 = $staff->prepare("UPDATE graduation_candidates SET status = 'Approved by Registrar' WHERE id = ?");
                    if ($stmt2) { $stmt2->bind_param('i', $cid); $stmt2->execute(); $stmt2->close(); }
                    $_SESSION['success'] = 'Graduation approved.'; logAudit($staff, $user_id, 'APPROVE', 'graduation', $cid, 'Registrar approved graduation candidate');
                } else $_SESSION['error'] = 'Failed: ' . $stmt->error; $stmt->close(); }
        }
        redirectBack('graduation');
    }
    if ($action === 'save_setting') {
        $key = trim($_POST['setting_key'] ?? ''); $val = trim($_POST['setting_value'] ?? '');
        $desc = trim($_POST['description'] ?? '');
        if ($key && $val) {
            $stmt = $staff->prepare("INSERT INTO gpa_settings (setting_key, setting_value, description, updated_by) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value), description=VALUES(description), updated_by=VALUES(updated_by)");
            if ($stmt) { $stmt->bind_param('sssi', $key, $val, $desc, $user_id);
                if ($stmt->execute()) { $_SESSION['success'] = "Setting $key saved."; logAudit($staff, $user_id, 'UPDATE', 'setting', 0, "Saved setting $key = $val"); }
                else $_SESSION['error'] = 'Failed: ' . $stmt->error; $stmt->close(); }
        }
        redirectBack('settings');
    }
    if ($action === 'send_notice') {
        $rectype = trim($_POST['recipient_type'] ?? 'student'); $recipientId = intval($_POST['recipient_id'] ?? 0);
        $subject = trim($_POST['subject'] ?? ''); $msg = trim($_POST['message'] ?? '');
        if ($subject && $msg) {
            $stmt = $staff->prepare("INSERT INTO communications (recipient_type, recipient_id, subject, message, channel, sent_by) VALUES (?, ?, ?, ?, 'portal', ?)");
            if ($stmt) { $stmt->bind_param('sissi', $rectype, $recipientId, $subject, $msg, $user_id);
                if ($stmt->execute()) { $_SESSION['success'] = 'Notice sent.'; logAudit($staff, $user_id, 'SEND', 'communication', $stmt->insert_id, "Sent notice '$subject' to $rectype $recipientId"); }
                else $_SESSION['error'] = 'Failed: ' . $stmt->error; $stmt->close(); }
        } else $_SESSION['error'] = 'Subject and message required.';
        redirectBack('notifications');
    }
    if ($action === 'publish_result') {
        $ay = trim($_POST['academic_year'] ?? date('Y')); $sem = trim($_POST['semester'] ?? '');
        if ($ay && $sem) {
            $stmt = $staff->prepare("INSERT INTO result_publications (academic_year, semester, published_by, status) VALUES (?, ?, ?, 'Published') ON DUPLICATE KEY UPDATE published_by=VALUES(published_by), status='Published', published_at=CURRENT_TIMESTAMP");
            if ($stmt) { $stmt->bind_param('ssi', $ay, $sem, $user_id);
                if ($stmt->execute()) {
                    $stmt2 = $staff->prepare("UPDATE grading_approval_workflow gw JOIN examination_records er ON gw.examination_record_id=er.id SET gw.published_at=NOW() WHERE er.grade_status='Verified' AND gw.published_at IS NULL");
                    if ($stmt2) { $stmt2->execute(); $stmt2->close(); }
                    $_SESSION['success'] = "Results published for $ay $sem."; logAudit($staff, $user_id, 'PUBLISH', 'result', 0, "Published results for $ay $sem");
                } else $_SESSION['error'] = 'Failed: ' . $stmt->error; $stmt->close(); }
        }
        redirectBack('results');
    }
    if ($action === 'add_clinical_placement') {
        $sid = intval($_POST['student_id'] ?? 0); $facility = trim($_POST['facility_name'] ?? '');
        $dept = trim($_POST['department'] ?? ''); $start = $_POST['start_date'] ?? null;
        $end = $_POST['end_date'] ?? null; $supervisor = trim($_POST['supervisor_name'] ?? '');
        $sphone = trim($_POST['supervisor_phone'] ?? '');
        if ($sid && $facility) {
            $stmt = $staff->prepare("INSERT INTO clinical_placements (student_id, facility_name, department, start_date, end_date, supervisor_name, supervisor_phone, status, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, 'Active', ?)");
            if ($stmt) { $stmt->bind_param('issssssi', $sid, $facility, $dept, $start, $end, $supervisor, $sphone, $user_id);
                if ($stmt->execute()) { $_SESSION['success'] = 'Clinical placement added.'; logAudit($staff, $user_id, 'CREATE', 'clinical_placement', $staff->insert_id, "Added clinical placement at $facility for student $sid"); }
                else $_SESSION['error'] = 'Failed: ' . $stmt->error; $stmt->close(); }
        } else $_SESSION['error'] = 'Student ID and facility required.';
        redirectBack('clinical-placement');
    }
}

$ajaxAction = $_GET['ajax'] ?? '';
$ajaxSid = intval($_GET['student_id'] ?? 0);

if ($ajaxAction === 'lookup_student' && $ajaxSid > 0) {
    header('Content-Type: application/json');
    if (!$students) { echo json_encode(['error' => 'No DB connection']); exit; }
    $stmt = $students->prepare("SELECT id, student_number, full_name, first_name, last_name, email, phone, program, level, status, gender FROM students WHERE id = ?");
    if (!$stmt) { echo json_encode(['error' => $students->error]); exit; }
    $stmt->bind_param('i', $ajaxSid);
    $stmt->execute();
    $r = $stmt->get_result();
    $student = $r->fetch_assoc();
    $stmt->close();
    echo json_encode($student ?: ['error' => 'Student not found']);
    exit;
}

if ($ajaxAction === 'search_students') {
    header('Content-Type: application/json');
    $q = trim($_GET['q'] ?? '');
    if (!$students || strlen($q) < 2) { echo json_encode([]); exit; }
    $like = '%' . $q . '%';
    $stmt = $students->prepare("SELECT id, student_number, full_name, program, level, status FROM students WHERE full_name LIKE ? OR student_number LIKE ? OR email LIKE ? OR phone LIKE ? ORDER BY full_name LIMIT 30");
    if (!$stmt) { echo json_encode([]); exit; }
    $stmt->bind_param('ssss', $like, $like, $like, $like);
    $stmt->execute();
    $r = $stmt->get_result();
    $data = [];
    while ($row = $r->fetch_assoc()) $data[] = $row;
    $stmt->close();
    echo json_encode($data);
    exit;
}

if ($ajaxAction === 'get_student_results') {
    header('Content-Type: application/json');
    $sid = $ajaxSid;
    if (!$staff) { echo json_encode([]); exit; }
    $stmt = $staff->prepare("SELECT id, exam_type, course_code, marks_obtained, total_marks, grade, continuous_assessment_marks, final_exam_marks, grade_status, created_at FROM examination_records WHERE student_id = ? ORDER BY created_at DESC");
    if (!$stmt) { echo json_encode([]); exit; }
    $stmt->bind_param('i', $sid);
    $stmt->execute();
    $r = $stmt->get_result();
    $data = [];
    while ($row = $r->fetch_assoc()) $data[] = $row;
    $stmt->close();
    echo json_encode($data);
    exit;
}

if ($ajaxAction === 'get_student_gpa') {
    header('Content-Type: application/json');
    $sid = $ajaxSid;
    if (!$staff) { echo json_encode(['gpa' => 0, 'total_credits' => 0, 'count' => 0]); exit; }
    $stmt = $staff->prepare("SELECT marks_obtained, total_marks FROM examination_records WHERE student_id = ? AND grade_status = 'Verified'");
    if (!$stmt) { echo json_encode(['error' => $staff->error]); exit; }
    $stmt->bind_param('i', $sid);
    $stmt->execute();
    $r = $stmt->get_result();
    $totalGp = 0; $count = 0; $totalCredits = 0;
    while ($row = $r->fetch_assoc()) {
        $gp = calculateGPA($row['marks_obtained'], $row['total_marks']);
        $totalGp += $gp; $count++;
    }
    $stmt->close();
    $gpa = $count > 0 ? round($totalGp / $count, 2) : 0;
    echo json_encode(['gpa' => $gpa, 'total_credits' => $totalCredits, 'count' => $count]);
    exit;
}

if ($ajaxAction === 'get_workflow') {
    header('Content-Type: application/json');
    $wid = intval($_GET['workflow_id'] ?? 0);
    if (!$staff || !$wid) { echo json_encode([]); exit; }
    $stmt = $staff->prepare("SELECT * FROM grading_approval_workflow WHERE workflow_number = ?");
    if (!$stmt) { echo json_encode([]); exit; }
    $stmt->bind_param('i', $wid);
    $stmt->execute();
    $r = $stmt->get_result();
    $row = $r->fetch_assoc();
    $stmt->close();
    echo json_encode($row ?: []);
    exit;
}

if ($ajaxAction === 'get_transcript_preview') {
    $sid = intval($_GET['student_id'] ?? 0);
    if (!$staff) { echo '<p class="text-muted">No connection</p>'; exit; }
    $stmt = $staff->prepare("SELECT t.*, s.full_name, s.student_number, s.program FROM transcripts t JOIN {$students_db}.students s ON t.student_id = s.id WHERE t.student_id = ? ORDER BY t.generated_at DESC LIMIT 1");
    if (!$stmt) { echo '<p>Error preparing statement</p>'; exit; }
    $stmt->bind_param('i', $sid);
    $stmt->execute();
    $r = $stmt->get_result();
    $t = $r->fetch_assoc();
    $stmt->close();
    if (!$t) { echo '<p class="text-muted">No transcript found.</p>'; exit; }
    $trid = $t['id'];
    $items = [];
    $istmt = $staff->prepare("SELECT * FROM transcript_items WHERE transcript_id = ? ORDER BY id");
    if ($istmt) {
        $istmt->bind_param('i', $trid);
        $istmt->execute();
        $ir = $istmt->get_result();
        while ($row = $ir->fetch_assoc()) $items[] = $row;
        $istmt->close();
    }
    echo '<div class="p-4" style="font-family:serif;">';
    echo '<h3 class="text-center mb-1">IGANGA SCHOOL OF NURSING AND MIDWIFERY</h3>';
    echo '<p class="text-center text-muted small mb-3">ACADEMIC TRANSCRIPT</p><hr>';
    echo '<p><strong>Student:</strong> ' . htmlspecialchars($t['full_name']) . ' &nbsp;&nbsp; <strong>#:</strong> ' . htmlspecialchars($t['student_number']) . '</p>';
    echo '<p><strong>Program:</strong> ' . htmlspecialchars($t['program']) . ' &nbsp;&nbsp; <strong>Transcript No:</strong> ' . htmlspecialchars($t['transcript_number']) . '</p>';
    echo '<table class="table table-bordered table-sm mt-3"><thead><tr><th>#</th><th>Course Code</th><th>Marks</th><th>Grade</th><th>GP</th></tr></thead><tbody>';
    $i = 1;
    foreach ($items as $it) {
        echo '<tr><td>' . $i++ . '</td><td>' . htmlspecialchars($it['course_code']) . '</td><td>' . htmlspecialchars($it['marks_obtained']) . '</td><td>' . htmlspecialchars($it['grade']) . '</td><td>' . htmlspecialchars($it['grade_point']) . '</td></tr>';
    }
    echo '</tbody></table>';
    echo '<p class="text-end"><strong>CGPA:</strong> ' . htmlspecialchars($t['cumulative_gpa'] ?: 'N/A') . '</p></div>';
    exit;
}

if ($ajaxAction === 'get_certificate_preview') {
    $sid = intval($_GET['student_id'] ?? 0);
    if (!$staff) { echo '<p class="text-muted">No connection</p>'; exit; }
    $stmt = $staff->prepare("SELECT c.*, s.full_name, s.program, s.gender FROM certificates c JOIN {$students_db}.students s ON c.student_id = s.id WHERE c.student_id = ? ORDER BY c.generated_at DESC LIMIT 1");
    if (!$stmt) { echo '<p>Error preparing statement</p>'; exit; }
    $stmt->bind_param('i', $sid);
    $stmt->execute();
    $r = $stmt->get_result();
    $c = $r->fetch_assoc();
    $stmt->close();
    if (!$c) { echo '<p class="text-muted">No certificate found.</p>'; exit; }
    echo '<div class="p-5 text-center" style="font-family:serif;border:3px double #1a237e;">';
    echo '<h2 class="mb-1">IGANGA SCHOOL OF NURSING AND MIDWIFERY</h2>';
    echo '<hr style="border-top:2px solid #1a237e;">';
    echo '<h4 class="text-uppercase mt-3">' . htmlspecialchars($c['certificate_type']) . '</h4>';
    echo '<p class="mt-4 lead">This is to certify that</p>';
    echo '<h3 class="fw-bold">' . htmlspecialchars($c['full_name']) . '</h3>';
    echo '<p>has successfully completed the program in</p>';
    echo '<h4 class="fw-bold">' . htmlspecialchars($c['program_name'] ?: $c['program']) . '</h4>';
    if ($c['completion_date']) echo '<p class="mt-3">Completion Date: ' . htmlspecialchars($c['completion_date']) . '</p>';
    echo '<p class="mt-2"><strong>Certificate No:</strong> ' . htmlspecialchars($c['certificate_number']) . '</p>';
    if ($c['issue_date']) echo '<p><strong>Issue Date:</strong> ' . htmlspecialchars($c['issue_date']) . '</p>';
    echo '</div>';
    exit;
}

if ($ajaxAction === 'mark_downloadable') {
    header('Content-Type: application/json');
    $type = $_GET['doc_type'] ?? ''; $did = intval($_GET['doc_id'] ?? 0);
    if (!$staff || !$did) { echo json_encode(['success' => false]); exit; }
    $table = $type === 'certificate' ? 'certificates' : 'transcripts';
    $stmt = $staff->prepare("UPDATE $table SET is_downloadable = 1 WHERE id = ?");
    if ($stmt) { $stmt->bind_param('i', $did); $stmt->execute(); $stmt->close();
        echo json_encode(['success' => true]);
        logAudit($staff, $user_id, 'MARK_DOWNLOADABLE', $type, $did, "Marked $type $did as downloadable");
    } else echo json_encode(['success' => false, 'error' => $staff->error]);
    exit;
}

if ($ajaxAction === 'archive_document') {
    header('Content-Type: application/json');
    $type = $_GET['doc_type'] ?? ''; $did = intval($_GET['doc_id'] ?? 0);
    if (!$staff || !$did) { echo json_encode(['success' => false]); exit; }
    $table = $type === 'certificate' ? 'certificates' : 'transcripts';
    $stmt = $staff->prepare("UPDATE $table SET is_archived = 1 WHERE id = ?");
    if ($stmt) { $stmt->bind_param('i', $did); $stmt->execute(); $stmt->close();
        echo json_encode(['success' => true]);
        logAudit($staff, $user_id, 'ARCHIVE', $type, $did, "Archived $type $did");
    } else echo json_encode(['success' => false, 'error' => $staff->error]);
    exit;
}

if ($ajaxAction === 'verify_certificate') {
    header('Content-Type: application/json');
    $certNo = trim($_GET['certificate_number'] ?? '');
    $verifiedBy = trim($_GET['verified_by'] ?? $user_name);
    $ref = 'VRF-' . strtoupper(substr(uniqid(), -8));
    if (!$staff || !$certNo) { echo json_encode(['success' => false, 'error' => 'Missing params']); exit; }
    $stmt = $staff->prepare("INSERT INTO certificate_verification (certificate_number, verified_by, verification_reference, verification_status) VALUES (?, ?, ?, 'Verified')");
    if ($stmt) { $stmt->bind_param('sss', $certNo, $verifiedBy, $ref);
        if ($stmt->execute()) { echo json_encode(['success' => true, 'reference' => $ref]);
            logAudit($staff, $user_id, 'VERIFY', 'certificate', 0, "Verified certificate $certNo (Ref: $ref)"); }
        else echo json_encode(['success' => false, 'error' => $stmt->error]); $stmt->close();
    } else echo json_encode(['success' => false, 'error' => $staff->error]);
    exit;
}

$totalStudents = $students ? safeCount($students, "SELECT COUNT(*) c FROM students WHERE status='Active'") : 0;
$totalPrograms = $staff ? safeCount($staff, "SELECT COUNT(*) c FROM academic_programs WHERE status='Active'") : 0;
$pendingApprovals = $staff ? safeCount($staff, "SELECT COUNT(*) c FROM grading_approval_workflow WHERE registrar_status='Pending' OR registrar_status IS NULL") : 0;
$upcomingExams = 0;
$recentRegistrations = [];
$pendingGraduation = [];
$recentActivity = [];
if ($staff) {
    $upcomingExams = safeCount($staff, "SELECT COUNT(*) c FROM examination_records WHERE grade_status='Pending'");
    $regR = $staff->query("SELECT r.*, s.full_name FROM registrar_student_registration r LEFT JOIN {$students_db}.students s ON r.student_id = s.id ORDER BY r.created_at DESC LIMIT 10");
    if ($regR) { while ($row = $regR->fetch_assoc()) $recentRegistrations[] = $row; $regR->close(); }
    $gradR = $staff->query("SELECT gc.*, s.full_name FROM graduation_candidates gc LEFT JOIN {$students_db}.students s ON gc.student_id = s.id WHERE gc.status='Pending' ORDER BY gc.submitted_at DESC LIMIT 10");
    if ($gradR) { while ($row = $gradR->fetch_assoc()) $pendingGraduation[] = $row; $gradR->close(); }
    $auditR = $staff->query("SELECT * FROM academic_audit_logs ORDER BY created_at DESC LIMIT 10");
    if ($auditR) { while ($row = $auditR->fetch_assoc()) $recentActivity[] = $row; $auditR->close(); }
}
$programs = getProgramOptions($staff);
$courses = getCourseOptions($staff);
$studentsList = [];
if ($students) {
    $sR = $students->query("SELECT id, student_number, full_name, program, level, status, phone, email FROM students ORDER BY id DESC LIMIT 100");
    if ($sR) { while ($row = $sR->fetch_assoc()) $studentsList[] = $row; $sR->close(); }
}
$sectionTitles = [
    'dashboard' => 'Dashboard',
    'student-records' => 'Student Records & Registration',
    'registration' => 'Student Registration',
    'admissions' => 'Admission Management',
    'programme-allocation' => 'Programme Allocation',
    'intake' => 'Intake Management',
    'transfers' => 'Student Transfers',
    'deferment' => 'Student Deferment',
    'withdrawal' => 'Student Withdrawal',
    'readmission' => 'Student Readmission',
    'student-directory' => 'Student Directory',
    'academic-profiles' => 'Academic Profiles',
    'guardian' => 'Guardian Information',
    'emergency' => 'Emergency Contacts',
    'documents' => 'Student Documents',
    'id-cards' => 'Student ID Cards',
    'academic-calendar' => 'Academic Calendar',
    'academic-years' => 'Academic Years',
    'semesters' => 'Semesters',
    'courses' => 'Courses',
    'curriculum' => 'Curriculum',
    'credit-units' => 'Credit Units',
    'programme-structure' => 'Programme Structure',
    'clinical-placement' => 'Clinical Placement',
    'exam-timetable' => 'Exam Timetable',
    'ca' => 'Continuous Assessment',
    'final-exam' => 'Final Examination',
    'practical-exam' => 'Practical Examination',
    'clinical-assessment' => 'Clinical Assessment',
    'marks-entry' => 'Marks Entry',
    'marks-verification' => 'Marks Verification',
    'missing-marks' => 'Missing Marks',
    'moderation' => 'Moderation',
    'generate-results' => 'Generate Results',
    'gpa' => 'GPA Calculation',
    'cgpa' => 'CGPA Calculation',
    'grade-book' => 'Grade Book',
    'publish-results' => 'Publish Results',
    'result-approval' => 'Result Approval Workflow',
    'result-slip' => 'Result Slip Generator',
    'retake' => 'Retake Management',
    'supplementary' => 'Supplementary Exams',
    'promotion' => 'Promotion Decisions',
    'national-registration' => 'Candidate Registration',
    'national-numbers' => 'National Exam Numbers',
    'national-results' => 'National Results',
    'national-uploads' => 'Certificate Uploads',
    'national-certificates' => 'National Certificates',
    'transcript-generator' => 'Transcript Generator',
    'transcript-archive' => 'Transcript Archive',
    'certificate-generator' => 'Certificate Generator',
    'certificate-uploads' => 'Certificate Uploads',
    'completion-letters' => 'Completion Letters',
    'recommendation-letters' => 'Recommendation Letters',
    'verification' => 'Verification Portal',
    'student-downloads' => 'Student Downloads',
    'graduation-list' => 'Graduation List',
    'graduation-clearance' => 'Graduation Clearance',
    'graduation-eligibility' => 'Graduation Eligibility',
    'senate-approval' => 'Senate Approval',
    'principal-approval' => 'Principal Approval',
    'dg-approval' => 'Director General Approval',
    'notices' => 'Student Notices',
    'announcements' => 'Academic Announcements',
    'emails' => 'Email Notifications',
    'sms' => 'SMS Notifications',
    'reports-student' => 'Student Reports',
    'reports-results' => 'Results Reports',
    'reports-gpa' => 'GPA Reports',
    'reports-graduation' => 'Graduation Reports',
    'reports-national' => 'National Exam Reports',
    'reports-transcript' => 'Transcript Reports',
    'reports-certificate' => 'Certificate Reports',
    'reports-audit' => 'Academic Audit Reports',
    'pending-lecturer' => 'Pending Lecturer Approvals',
    'pending-hod' => 'Pending HOD Approvals',
    'pending-dir-academics' => 'Pending Dir. Academics Approvals',
    'pending-principal' => 'Pending Principal Approvals',
    'pending-dg' => 'Pending Director General Approvals',
    'settings' => 'Settings',
    'notifications' => 'Notifications',
    'programs' => 'Programs',
    'intakes' => 'Intakes',
    'examinations' => 'Examinations & Marks Entry',
    'approvals' => 'Results & Approvals',
    'transcripts' => 'Transcripts',
    'certificates' => 'Certificates',
    'graduation' => 'Graduation Management',
];
?><!DOCTYPE html>
<html lang="en">
<head>
<?php include_once __DIR__ . '/../includes/dashboard_head.php'; ?>
<style>
:root { --reg-primary: #1a237e; --reg-primary-light: #3949ab; --reg-accent: #ffd700; }









.reg-content{margin-left:270px;padding:20px 30px;min-height:100vh;background:#f4f5f9}
.main { margin-left: 0 !important; min-height: auto !important; flex: none !important; }
.content-section { background: transparent !important; border: none !important; box-shadow: none !important; border-radius: 0 !important; padding: 0 !important; margin-bottom: 0 !important; }
.stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; margin-bottom: 24px; }
.stat-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 20px; display: flex; align-items: center; gap: 16px; box-shadow: 0 1px 3px rgba(0,0,0,0.04); transition: transform 0.2s; }
.stat-card:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.08); }
.stat-card .stat-icon { width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 20px; color: #fff; flex-shrink: 0; }
.stat-card .stat-number { font-size: 1.8rem; font-weight: 700; color: #0f172a; line-height: 1; }
.stat-card .stat-label { font-size: 0.75rem; color: #64748b; margin-top: 2px; }
.section-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 24px; margin-bottom: 24px; box-shadow: 0 1px 3px rgba(0,0,0,0.04); }
.quick-actions { display: flex; flex-wrap: wrap; gap: 8px; }
.quick-action-btn { display: inline-flex; align-items: center; gap: 8px; padding: 10px 18px; border-radius: 10px; font-size: 13px; font-weight: 500; text-decoration: none; transition: all 0.2s; border: none; cursor: pointer; }
.quick-action-btn:hover { transform: translateY(-1px); text-decoration: none; }
.form-section-title { font-size: 13px; font-weight: 600; color: #1a237e; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 16px; padding-bottom: 8px; border-bottom: 2px solid #eef2ff; }
.form-label { font-size: 13px; font-weight: 500; color: #374151; margin-bottom: 4px; }
.form-control, .form-select { border-radius: 8px; font-size: 13px; padding: 8px 12px; border: 1px solid #d1d5db; }
.form-control:focus, .form-select:focus { border-color: #3949ab; box-shadow: 0 0 0 3px rgba(57,73,171,0.12); }
.btn-reg { background: var(--reg-primary); color: #fff; border: none; border-radius: 8px; padding: 8px 20px; font-size: 13px; font-weight: 500; }
.btn-reg:hover { background: #283593; color: #fff; }
.btn-reg-outline { background: transparent; color: var(--reg-primary); border: 1.5px solid var(--reg-primary); border-radius: 8px; padding: 8px 20px; font-size: 13px; font-weight: 500; }
.btn-reg-outline:hover { background: var(--reg-primary); color: #fff; }
.btn-reg-accent { background: var(--reg-accent); color: #1a237e; border: none; border-radius: 8px; padding: 8px 20px; font-size: 13px; font-weight: 600; }
.btn-reg-accent:hover { background: #ffed4a; color: #1a237e; }
.student-lookup-wrapper { position: relative; }
.lookup-results { display: none; position: absolute; top: 100%; left: 0; right: 0; background: #fff; border: 1px solid #d1d5db; border-radius: 0 0 8px 8px; box-shadow: 0 8px 24px rgba(0,0,0,0.12); z-index: 1000; max-height: 260px; overflow-y: auto; }
.lookup-item { padding: 10px 14px; cursor: pointer; border-bottom: 1px solid #f3f4f6; }
.lookup-item:hover { background: #eef2ff; }
.lookup-item:last-child { border-bottom: none; }
.lookup-none { color: #6b7280; font-size: 13px; padding: 10px 14px; }
.selected-student-info { background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px; padding: 12px 16px; margin-top: 8px; }
@media (max-width: 768px) { .reg-content { margin-left: 0; padding: 16px; } .stats-grid { grid-template-columns: 1fr 1fr; } }
</style>
</head>
<body class="ent-layout">
<?php include_once __DIR__ . '/../includes/sidebar.php'; ?>
<?php include_once __DIR__ . '/../includes/dashboard_topbar.php'; ?>



<div class="reg-content">
  <?php if (!empty($_SESSION['success'])): ?>
  <div class="alert alert-success py-2 alert-dismissible fade show"><?= htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
  <?php endif; ?>
  <?php if (!empty($_SESSION['error'])): ?>
  <div class="alert alert-danger py-2 alert-dismissible fade show"><?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
  <?php endif; ?>

  <div class="main content-section dashboard-section active" id="content" data-section="dashboard">
    <div class="stats-grid">
      <div class="stat-card"><div class="stat-icon" style="background:#1a237e;"><i class="fas fa-user-graduate"></i></div><div><div class="stat-number"><?= $totalStudents ?></div><div class="stat-label">Total Enrolled Students</div></div></div>
      <div class="stat-card"><div class="stat-icon" style="background:#2e7d32;"><i class="fas fa-book-open"></i></div><div><div class="stat-number"><?= $totalPrograms ?></div><div class="stat-label">Active Programs</div></div></div>
      <div class="stat-card"><div class="stat-icon" style="background:#e65100;"><i class="fas fa-clock"></i></div><div><div class="stat-number"><?= $pendingApprovals ?></div><div class="stat-label">Pending Approvals</div></div></div>
      <div class="stat-card"><div class="stat-icon" style="background:#1565c0;"><i class="fas fa-file-signature"></i></div><div><div class="stat-number"><?= $upcomingExams ?></div><div class="stat-label">Pending Results Entry</div></div></div>
      <div class="stat-card"><div class="stat-icon" style="background:#7c3aed;"><i class="fas fa-user-check"></i></div><div><div class="stat-number"><?= count($pendingGraduation) ?></div><div class="stat-label">Graduation Candidates</div></div></div>
    </div>

    <div class="row g-3 mb-4">
      <div class="col-md-7">
        <div class="section-card">
          <h6 class="fw-bold mb-3"><i class="fas fa-bolt me-2" style="color:#ffd700;"></i>Quick Actions</h6>
          <div class="quick-actions">
            <a href="#student-records" class="quick-action-btn" style="background:#eef2ff;color:#1a237e;" onclick="switchSection('student-records')"><i class="fas fa-user-plus"></i> Register Student</a>
            <a href="#programs" class="quick-action-btn" style="background:#f0fdf4;color:#2e7d32;" onclick="switchSection('programs')"><i class="fas fa-layer-group"></i> Add Program</a>
            <a href="#courses" class="quick-action-btn" style="background:#fff7ed;color:#e65100;" onclick="switchSection('courses')"><i class="fas fa-book"></i> Add Course</a>
            <a href="#examinations" class="quick-action-btn" style="background:#fef2f2;color:#c62828;" onclick="switchSection('examinations')"><i class="fas fa-pen"></i> Enter Marks</a>
            <a href="#approvals" class="quick-action-btn" style="background:#f5f3ff;color:#7c3aed;" onclick="switchSection('approvals')"><i class="fas fa-check-double"></i> Approve Results</a>
            <a href="#transcripts" class="quick-action-btn" style="background:#ecfdf5;color:#065f46;" onclick="switchSection('transcripts')"><i class="fas fa-file-alt"></i> Generate Transcript</a>
            <a href="#certificates" class="quick-action-btn" style="background:#fffbeb;color:#92400e;" onclick="switchSection('certificates')"><i class="fas fa-certificate"></i> Certificates</a>
            <a href="#graduation" class="quick-action-btn" style="background:#f1f5f9;color:#475569;" onclick="switchSection('graduation')"><i class="fas fa-graduation-cap"></i> Graduation</a>
            <a href="#intakes" class="quick-action-btn" style="background:#fce7f3;color:#9d174d;" onclick="switchSection('intakes')"><i class="fas fa-door-open"></i> Intakes</a>
            <a href="#settings" class="quick-action-btn" style="background:#f1f5f9;color:#475569;" onclick="switchSection('settings')"><i class="fas fa-cog"></i> Settings</a>
          </div>
        </div>

        <div class="section-card">
          <h6 class="fw-bold mb-3"><i class="fas fa-history me-2" style="color:#1565c0;"></i>Recent Activity</h6>
          <?php if (empty($recentActivity)): ?>
          <p class="text-muted small mb-0">No recent activity logged.</p>
          <?php else: ?>
          <div style="max-height:320px;overflow-y:auto;">
            <?php foreach ($recentActivity as $log): ?>
            <div class="d-flex align-items-start gap-2 mb-2 pb-2 border-bottom">
              <div class="mt-1"><span class="badge bg-secondary" style="font-size:10px;"><?= htmlspecialchars(substr($log['action'], 0, 12)) ?></span></div>
              <div class="small flex-grow-1"><?= htmlspecialchars($log['description']) ?> <span class="text-muted d-block" style="font-size:10px;"><?= htmlspecialchars($log['created_at']) ?></span></div>
            </div>
            <?php endforeach; ?>
          </div>
          <?php endif; ?>
        </div>
      </div>

      <div class="col-md-5">
        <div class="section-card">
          <!-- Website Submissions -->
          <h6 class="fw-bold mb-3"><i class="fas fa-globe me-2" style="color:#2563eb;"></i>Website Submissions</h6>
          <div class="card border-0 shadow-sm mb-3">
            <div class="card-body p-0">
              <?php if (function_exists('renderWebsiteSubmissionsWidget') && $website_conn): ?>
                  <?php renderWebsiteSubmissionsWidget($website_conn, ['contacts', 'donations', 'volunteers', 'applications'], 10); ?>
              <?php else: ?>
                  <div class="text-center py-4 text-muted">
                      <i class="fas fa-globe fa-2x mb-2" style="color:#94a3b8;"></i>
                      <p>Website submissions will appear here.</p>
                  </div>
              <?php endif; ?>
            </div>
          </div>
        </div>

        <div class="section-card">
          <h6 class="fw-bold mb-3"><i class="fas fa-calendar-alt me-2" style="color:#1a237e;"></i>Academic Calendar</h6>
          <?php
          $calYears = [];
          if ($staff) {
            $cyR = $staff->query("SELECT * FROM academic_calendar WHERE status='Active' ORDER BY start_date DESC LIMIT 3");
            if ($cyR) { while ($row = $cyR->fetch_assoc()) $calYears[] = $row; $cyR->close(); }
          }
          if (empty($calYears)): ?>
          <p class="text-muted small mb-0">No academic years defined.</p>
          <?php else: ?>
          <?php foreach ($calYears as $cy): ?>
          <div class="d-flex justify-content-between align-items-center p-2 rounded mb-1 <?= $cy['is_current'] ? 'bg-primary bg-opacity-10' : '' ?>">
            <span class="fw-medium"><?= htmlspecialchars($cy['academic_year']) ?> <?= $cy['is_current'] ? '<span class="badge bg-primary">Current</span>' : '' ?></span>
            <small class="text-muted"><?= $cy['start_date'] ? date('d M', strtotime($cy['start_date'])) : '?' ?> - <?= $cy['end_date'] ? date('d M', strtotime($cy['end_date'])) : '?' ?></small>
          </div>
          <?php endforeach; ?>
          <?php endif; ?>
          <div class="mt-2">
            <a href="#academic-calendar" class="btn btn-sm btn-reg-outline" onclick="switchSection('academic-calendar')"><i class="fas fa-plus me-1"></i>Manage Calendar</a>
          </div>
        </div>

        <div class="section-card">
          <h6 class="fw-bold mb-3"><i class="fas fa-clock me-2" style="color:#e65100;"></i>Pending Graduation</h6>
          <?php if (empty($pendingGraduation)): ?>
          <p class="text-muted small mb-0">No pending graduation candidates.</p>
          <?php else: ?>
          <?php foreach (array_slice($pendingGraduation, 0, 5) as $gc): ?>
          <div class="d-flex justify-content-between align-items-center p-2 border-bottom small">
            <span><?= htmlspecialchars($gc['full_name'] ?? "Student #{$gc['student_id']}") ?></span>
            <span class="badge bg-warning text-dark">Pending</span>
          </div>
          <?php endforeach; ?>
          <?php endif; ?>
</div>

<script>
$(document).ready(function() {
    if ($.fn.DataTable) { $('.data-table').DataTable({ pageLength: 25, responsive: true, searching: true, ordering: true }); }
    $('input[type="date"]').addClass('datepicker');
    var hash = window.location.hash.replace('#', '');
    if (hash) switchSection(hash);
});

function switchSection(section) {
    document.querySelectorAll('.dashboard-section').forEach(function(el) { el.classList.remove('active'); });
    var target = document.querySelector('.dashboard-section[data-section="' + section + '"]');
    if (target) {
        target.classList.add('active');
    } else {
        var generic = document.querySelector('.dashboard-section[data-section="generic"]');
        if (generic) {
            generic.classList.add('active');
            var label = document.getElementById('genericSectionTitle');
            var desc = document.getElementById('genericSectionDesc');
            if (label) {
                var titles = <?= json_encode($sectionTitles ?? []) ?>;
                var title = titles[section] || section.replace(/-/g, ' ').replace(/\b\w/g, function(c){return c.toUpperCase();});
                label.textContent = title;
                if (desc) desc.textContent = 'This section is under development. Content will be available soon.';
            }
        }
    }
    window.location.hash = section;
}

window.addEventListener('hashchange', function() {
    var hash = window.location.hash.replace('#', '');
    if (hash) switchSection(hash);
});

document.querySelectorAll('.student-lookup').forEach(function(input) {
    var wrapper = input.closest('.student-lookup-wrapper');
    var results = wrapper.querySelector('.lookup-results');
    var hidden = wrapper.querySelector('.student-id-target');
    var info = wrapper.closest('.modal-body') ? wrapper.closest('.modal-body').querySelector('.selected-student-info') : null;
    var timer = null;
    input.addEventListener('input', function() {
        clearTimeout(timer);
        var q = this.value.trim();
        if (q.length < 2) { results.style.display = 'none'; return; }
        timer = setTimeout(function() {
            fetch('academic-registrar.php?ajax=search_students&q=' + encodeURIComponent(q))
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    results.innerHTML = '';
                    if (data.length === 0) {
                        results.innerHTML = '<div class="lookup-none">No students found</div>';
                    } else {
                        data.forEach(function(s) {
                            var div = document.createElement('div');
                            div.className = 'lookup-item';
                            div.innerHTML = '<strong>' + escJs(s.full_name) + '</strong> <small class="text-muted">' + escJs(s.student_number) + ' | ' + escJs(s.program) + ' | Lvl ' + s.level + '</small>';
                            div.addEventListener('click', function() {
                                hidden.value = s.id;
                                input.value = s.full_name + ' (' + s.student_number + ')';
                                results.style.display = 'none';
                                if (info) {
                                    info.classList.remove('d-none');
                                    info.innerHTML = '<strong>' + escJs(s.full_name) + '</strong><br><small>#' + escJs(s.student_number) + ' | ' + escJs(s.program) + ' | Status: ' + s.status + '</small>';
                                }
                            });
                            results.appendChild(div);
                        });
                    }
                    results.style.display = 'block';
                });
        }, 300);
    });
    input.addEventListener('blur', function() {
        setTimeout(function() { results.style.display = 'none'; }, 200);
    });
});

function previewStudent(data) {
    var html = '<div class="p-3"><h6 class="fw-bold mb-3">' + escJs(data.full_name) + '</h6>';
    html += '<table class="table table-sm table-bordered"><tr><td><strong>Student #</strong></td><td>' + escJs(data.student_number) + '</td></tr>';
    html += '<tr><td><strong>Reg #</strong></td><td>' + escJs(data.registration_number || '-') + '</td></tr>';
    html += '<tr><td><strong>Program</strong></td><td>' + escJs(data.program) + '</td></tr>';
    html += '<tr><td><strong>Level</strong></td><td>' + (data.level || '-') + '</td></tr>';
    html += '<tr><td><strong>Status</strong></td><td>' + escJs(data.status) + '</td></tr>';
    html += '<tr><td><strong>Phone</strong></td><td>' + escJs(data.phone || '-') + '</td></tr>';
    html += '<tr><td><strong>Email</strong></td><td>' + escJs(data.email || '-') + '</td></tr></table></div>';
    var m = document.createElement('div');
    m.className = 'modal fade';
    m.innerHTML = '<div class="modal-dialog"><div class="modal-content"><div class="modal-header bg-primary text-white"><h5 class="modal-title">Student Profile</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div><div class="modal-body">' + html + '</div></div></div>';
    document.body.appendChild(m);
    var modal = new bootstrap.Modal(m);
    modal.show();
    m.addEventListener('hidden.bs.modal', function() { m.remove(); });
}

function previewTranscript(studentId) {
    var w = window.open('', '_blank', 'width=800,height=600');
    w.document.write('<html><head><title>Transcript Preview</title><link rel="stylesheet" href="../assets/css/bootstrap.min.css"></head><body>');
    fetch('academic-registrar.php?ajax=get_transcript_preview&student_id=' + studentId)
        .then(function(r) { return r.text(); })
        .then(function(html) {
            w.document.write(html);
            w.document.write('</body></html>');
            w.document.close();
        });
}

function previewCertificate(studentId) {
    var w = window.open('', '_blank', 'width=800,height=600');
    w.document.write('<html><head><title>Certificate Preview</title><link rel="stylesheet" href="../assets/css/bootstrap.min.css"></head><body>');
    fetch('academic-registrar.php?ajax=get_certificate_preview&student_id=' + studentId)
        .then(function(r) { return r.text(); })
        .then(function(html) {
            w.document.write(html);
            w.document.write('</body></html>');
            w.document.close();
        });
}

function markDownloadable(type, id) {
    if (!confirm('Mark this ' + type + ' as downloadable for the student?')) return;
    fetch('academic-registrar.php?ajax=mark_downloadable&doc_type=' + type + '&doc_id=' + id)
        .then(function(r) { return r.json(); })
        .then(function(res) {
            if (res.success) { alert('Marked as downloadable.'); location.reload(); }
            else alert('Error: ' + (res.error || 'Unknown'));
        });
}

function archiveDoc(type, id) {
    if (!confirm('Archive this ' + type + '?')) return;
    fetch('academic-registrar.php?ajax=archive_document&doc_type=' + type + '&doc_id=' + id)
        .then(function(r) { return r.json(); })
        .then(function(res) {
            if (res.success) { alert('Archived.'); location.reload(); }
            else alert('Error: ' + (res.error || 'Unknown'));
        });
}

function escJs(str) { if (!str) return ''; return String(str).replace(/'/g, "\\'").replace(/"/g, '&quot;'); }

document.querySelectorAll('form').forEach(function(form) {
    form.addEventListener('submit', function() {
        var existing = form.querySelector('input[name="_section"]');
        if (!existing) {
            var input = document.createElement('input');
            input.type = 'hidden'; input.name = '_section';
            input.value = window.location.hash.replace('#', '') || 'dashboard';
            form.appendChild(input);
        }
    });
});

document.addEventListener('click', function(e) {
    if (!e.target.closest('.student-lookup-wrapper')) {
        document.querySelectorAll('.lookup-results').forEach(function(el) { el.style.display = 'none'; });
    }
});
</script>

  <div class="main content-section dashboard-section" data-section="student-records">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h5 class="fw-bold"><i class="fas fa-users me-2"></i>Student Records & Registration</h5>
      <div class="d-flex gap-2 flex-wrap">
        <button class="btn btn-reg" data-bs-toggle="modal" data-bs-target="#registerStudentModal"><i class="fas fa-user-plus me-1"></i>Register</button>
        <button class="btn btn-reg-outline" data-bs-toggle="modal" data-bs-target="#transferStudentModal"><i class="fas fa-exchange-alt me-1"></i>Transfer</button>
        <button class="btn btn-reg-outline" data-bs-toggle="modal" data-bs-target="#deferStudentModal"><i class="fas fa-pause me-1"></i>Defer</button>
        <button class="btn btn-reg-outline" data-bs-toggle="modal" data-bs-target="#withdrawStudentModal"><i class="fas fa-times me-1"></i>Withdraw</button>
        <button class="btn btn-reg-outline" data-bs-toggle="modal" data-bs-target="#readmitStudentModal"><i class="fas fa-undo me-1"></i>Readmit</button>
      </div>
    </div>
    <div class="section-card p-0">
      <div class="table-responsive">
        <table class="table table-hover mb-0 data-table">
          <thead class="table-light">
            <tr><th>#</th><th>Student #</th><th>Full Name</th><th>Program</th><th>Level</th><th>Status</th><th>Phone</th><th>Actions</th></tr>
          </thead>
          <tbody>
            <?php if (empty($studentsList)): ?>
            <tr><td colspan="8" class="text-center py-4 text-muted">No student records found.</td></tr>
            <?php else: $idx = 0; foreach ($studentsList as $s): $idx++; ?>
            <tr>
              <td><?= $idx ?></td>
              <td><code><?= htmlspecialchars($s['student_number']) ?></code></td>
              <td><strong><?= htmlspecialchars($s['full_name']) ?></strong></td>
              <td><?= htmlspecialchars($s['program']) ?></td>
              <td><?= $s['level'] ?></td>
              <td><span class="badge bg-<?= $s['status'] === 'Active' ? 'success' : ($s['status'] === 'Deferred' ? 'warning' : ($s['status'] === 'Withdrawn' ? 'danger' : 'secondary')) ?>"><?= htmlspecialchars($s['status']) ?></span></td>
              <td><?= htmlspecialchars($s['phone']) ?></td>
              <td>
                <button class="btn btn-sm btn-outline-primary py-0 px-2" onclick='previewStudent(<?= json_encode($s, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT) ?>)' title="View"><i class="fas fa-eye"></i></button>
                <a href="student-add.php?search=<?= urlencode($s['student_number']) ?>" class="btn btn-sm btn-outline-info py-0 px-2" title="Edit"><i class="fas fa-edit"></i></a>
              </td>
            </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="main content-section dashboard-section" data-section="programs">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h5 class="fw-bold"><i class="fas fa-layer-group me-2"></i>Academic Programs</h5>
      <button class="btn btn-reg" data-bs-toggle="modal" data-bs-target="#addProgramModal"><i class="fas fa-plus me-1"></i>Add Program</button>
    </div>
    <?php
    $allPrograms = [];
    if ($staff) {
      $pR = $staff->query("SELECT p.*, (SELECT COUNT(*) FROM {$students_db}.students s WHERE s.program = p.program_name AND s.status='Active') enrolled FROM academic_programs p ORDER BY p.program_name");
      if ($pR) { while ($row = $pR->fetch_assoc()) $allPrograms[] = $row; $pR->close(); }
    }
    ?>
    <div class="section-card p-0">
      <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead class="table-light"><tr><th>Code</th><th>Name</th><th>Type</th><th>Department</th><th>Duration</th><th>Enrolled</th><th>Status</th></tr></thead>
          <tbody>
            <?php if (empty($allPrograms)): ?>
            <tr><td colspan="7" class="text-center py-4 text-muted">No programs defined.</td></tr>
            <?php else: foreach ($allPrograms as $p): ?>
            <tr><td><code><?= htmlspecialchars($p['program_code']) ?></code></td><td><strong><?= htmlspecialchars($p['program_name']) ?></strong></td><td><?= htmlspecialchars($p['program_type']) ?></td><td><?= htmlspecialchars($p['department']) ?></td><td><?= $p['duration_years'] ?> yr(s)</td><td><?= $p['enrolled'] ?? 0 ?></td><td><span class="badge bg-<?= $p['status'] === 'Active' ? 'success' : 'secondary' ?>"><?= htmlspecialchars($p['status']) ?></span></td></tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="modal fade" id="addProgramModal" tabindex="-1">
    <div class="modal-dialog">
      <form method="POST" class="modal-content">
        <div class="modal-header bg-primary text-white"><h5 class="modal-title"><i class="fas fa-plus me-2"></i>Add Program</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
          <input type="hidden" name="action" value="add_program">
          <div class="row g-3">
            <div class="col-md-6"><label class="form-label">Code *</label><input type="text" name="program_code" class="form-control" required></div>
            <div class="col-md-6"><label class="form-label">Name *</label><input type="text" name="program_name" class="form-control" required></div>
            <div class="col-md-4"><label class="form-label">Type</label><select name="program_type" class="form-select"><option>Certificate</option><option>Diploma</option><option>Bachelor</option><option>Master</option></select></div>
            <div class="col-md-4"><label class="form-label">Department</label><input type="text" name="department" class="form-control"></div>
            <div class="col-md-4"><label class="form-label">Duration</label><input type="number" name="duration_years" class="form-control" value="3" min="1" max="8"></div>
          </div>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-reg"><i class="fas fa-save me-1"></i>Add</button></div>
      </form>
    </div>
  </div>

  <div class="main content-section dashboard-section" data-section="courses">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h5 class="fw-bold"><i class="fas fa-book me-2"></i>Course Catalog</h5>
      <button class="btn btn-reg" data-bs-toggle="modal" data-bs-target="#addCourseModal"><i class="fas fa-plus me-1"></i>Add Course</button>
    </div>
    <?php
    $allCourses = [];
    if ($staff) {
      $cR = $staff->query("SELECT c.*, p.program_name FROM academic_course_catalog c LEFT JOIN academic_programs p ON c.program_code = p.program_code ORDER BY c.program_code, c.year_of_study, c.semester");
      if ($cR) { while ($row = $cR->fetch_assoc()) $allCourses[] = $row; $cR->close(); }
    }
    ?>
    <div class="section-card p-0">
      <div class="table-responsive">
        <table class="table table-hover mb-0 data-table">
          <thead class="table-light"><tr><th>Code</th><th>Title</th><th>Program</th><th>Year</th><th>Semester</th><th>Credits</th><th>Status</th></tr></thead>
          <tbody>
            <?php if (empty($allCourses)): ?>
            <tr><td colspan="7" class="text-center py-4 text-muted">No courses defined.</td></tr>
            <?php else: foreach ($allCourses as $c): ?>
            <tr><td><code><?= htmlspecialchars($c['course_code']) ?></code></td><td><strong><?= htmlspecialchars($c['course_title']) ?></strong></td><td><?= htmlspecialchars($c['program_name'] ?: $c['program_code']) ?></td><td>Year <?= $c['year_of_study'] ?></td><td><?= htmlspecialchars($c['semester']) ?></td><td><?= $c['credits'] ?></td><td><span class="badge bg-<?= $c['status'] === 'Active' ? 'success' : 'secondary' ?>"><?= htmlspecialchars($c['status']) ?></span></td></tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="modal fade" id="addCourseModal" tabindex="-1">
    <div class="modal-dialog">
      <form method="POST" class="modal-content">
        <div class="modal-header bg-primary text-white"><h5 class="modal-title"><i class="fas fa-plus me-2"></i>Add Course</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
          <input type="hidden" name="action" value="add_course">
          <div class="row g-3">
            <div class="col-md-6"><label class="form-label">Course Code *</label><input type="text" name="course_code" class="form-control" required></div>
            <div class="col-md-6"><label class="form-label">Title *</label><input type="text" name="course_title" class="form-control" required></div>
            <div class="col-md-4"><label class="form-label">Credits</label><input type="number" step="0.5" name="credits" class="form-control" value="3"></div>
            <div class="col-md-4"><label class="form-label">Program</label><input type="text" name="program_code" class="form-control" list="progCodeList">
              <datalist id="progCodeList"><?php foreach ($programs as $p): ?><option value="<?= htmlspecialchars($p['program_code']) ?>"><?php endforeach; ?></datalist>
            </div>
            <div class="col-md-2"><label class="form-label">Year</label><input type="number" name="year_of_study" class="form-control" value="1" min="1" max="8"></div>
            <div class="col-md-2"><label class="form-label">Semester</label><select name="semester" class="form-select"><option>First</option><option>Second</option></select></div>
          </div>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-reg"><i class="fas fa-save me-1"></i>Add</button></div>
      </form>
    </div>
  </div>

  <div class="main content-section dashboard-section" data-section="intakes">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h5 class="fw-bold"><i class="fas fa-door-open me-2"></i>Intake Management</h5>
      <button class="btn btn-reg" data-bs-toggle="modal" data-bs-target="#addIntakeModal"><i class="fas fa-plus me-1"></i>Add Intake</button>
    </div>
    <?php
    $intakes = [];
    if ($staff) {
      $iR = $staff->query("SELECT * FROM intakes ORDER BY start_date DESC");
      if ($iR) { while ($row = $iR->fetch_assoc()) $intakes[] = $row; $iR->close(); }
    }
    ?>
    <div class="section-card p-0">
      <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead class="table-light"><tr><th>Intake Name</th><th>Academic Year</th><th>Start</th><th>End</th><th>Status</th></tr></thead>
          <tbody>
            <?php if (empty($intakes)): ?><tr><td colspan="5" class="text-center py-4 text-muted">No intakes defined.</td></tr>
            <?php else: foreach ($intakes as $i): ?>
            <tr><td><strong><?= htmlspecialchars($i['intake_name']) ?></strong></td><td><?= htmlspecialchars($i['academic_year']) ?></td><td><?= $i['start_date'] ?: '-' ?></td><td><?= $i['end_date'] ?: '-' ?></td><td><span class="badge bg-<?= $i['status'] === 'Open' ? 'success' : 'secondary' ?>"><?= htmlspecialchars($i['status']) ?></span></td></tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="modal fade" id="addIntakeModal" tabindex="-1">
    <div class="modal-dialog">
      <form method="POST" class="modal-content">
        <div class="modal-header bg-primary text-white"><h5 class="modal-title"><i class="fas fa-plus me-2"></i>Add Intake</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
          <input type="hidden" name="action" value="add_intake">
          <div class="row g-3">
            <div class="col-md-6"><label class="form-label">Name *</label><input type="text" name="intake_name" class="form-control" placeholder="e.g. Jan 2025" required></div>
            <div class="col-md-6"><label class="form-label">Year</label><input type="text" name="academic_year" class="form-control" placeholder="e.g. 2025"></div>
            <div class="col-md-6"><label class="form-label">Start Date</label><input type="date" name="start_date" class="form-control"></div>
            <div class="col-md-6"><label class="form-label">End Date</label><input type="date" name="end_date" class="form-control"></div>
          </div>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-reg"><i class="fas fa-save me-1"></i>Add</button></div>
      </form>
    </div>
  </div>

  <div class="main content-section dashboard-section" data-section="academic-calendar">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h5 class="fw-bold"><i class="fas fa-calendar-alt me-2"></i>Academic Calendar</h5>
      <div class="d-flex gap-2">
        <button class="btn btn-reg" data-bs-toggle="modal" data-bs-target="#addAcademicYearModal"><i class="fas fa-plus me-1"></i>Add Year</button>
        <button class="btn btn-reg-outline" data-bs-toggle="modal" data-bs-target="#addSemesterModal"><i class="fas fa-plus me-1"></i>Add Semester</button>
      </div>
    </div>
    <?php
    $academicYears = []; $semesters = [];
    if ($staff) {
      $ayR = $staff->query("SELECT * FROM academic_calendar ORDER BY start_date DESC");
      if ($ayR) { while ($row = $ayR->fetch_assoc()) $academicYears[] = $row; $ayR->close(); }
      $semR = $staff->query("SELECT * FROM semesters ORDER BY start_date DESC");
      if ($semR) { while ($row = $semR->fetch_assoc()) $semesters[] = $row; $semR->close(); }
    }
    ?>
    <div class="row g-3">
      <div class="col-md-6">
        <div class="section-card">
          <h6 class="fw-bold mb-3">Academic Years</h6>
          <table class="table table-sm"><thead><tr><th>Year</th><th>Start</th><th>End</th><th>Status</th></tr></thead>
            <tbody><?php if (empty($academicYears)): ?><tr><td colspan="4" class="text-muted">None.</td></tr><?php else: foreach ($academicYears as $ay): ?>
            <tr><td><strong><?= htmlspecialchars($ay['academic_year']) ?></strong> <?= $ay['is_current'] ? '<span class="badge bg-primary">Current</span>' : '' ?></td><td><?= $ay['start_date'] ?: '-' ?></td><td><?= $ay['end_date'] ?: '-' ?></td><td><span class="badge bg-<?= $ay['status'] === 'Active' ? 'success' : 'secondary' ?>"><?= htmlspecialchars($ay['status']) ?></span></td></tr>
            <?php endforeach; endif; ?></tbody>
          </table>
        </div>
      </div>
      <div class="col-md-6">
        <div class="section-card">
          <h6 class="fw-bold mb-3">Semesters</h6>
          <table class="table table-sm"><thead><tr><th>Name</th><th>Year</th><th>Start</th><th>End</th></tr></thead>
            <tbody><?php if (empty($semesters)): ?><tr><td colspan="4" class="text-muted">None.</td></tr><?php else: foreach ($semesters as $s): ?>
            <tr><td><strong><?= htmlspecialchars($s['semester_name']) ?></strong></td><td><?= htmlspecialchars($s['academic_year']) ?></td><td><?= $s['start_date'] ?: '-' ?></td><td><?= $s['end_date'] ?: '-' ?></td></tr>
            <?php endforeach; endif; ?></tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <div class="modal fade" id="addAcademicYearModal" tabindex="-1">
    <div class="modal-dialog">
      <form method="POST" class="modal-content">
        <div class="modal-header bg-primary text-white"><h5 class="modal-title">Add Academic Year</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
          <input type="hidden" name="action" value="add_academic_year">
          <div class="row g-3">
            <div class="col-md-6"><label class="form-label">Year *</label><input type="text" name="academic_year" class="form-control" placeholder="e.g. 2025" required></div>
            <div class="col-md-3"><label class="form-label">Start</label><input type="date" name="start_date" class="form-control"></div>
            <div class="col-md-3"><label class="form-label">End</label><input type="date" name="end_date" class="form-control"></div>
          </div>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-reg">Save</button></div>
      </form>
    </div>
  </div>

  <div class="modal fade" id="addSemesterModal" tabindex="-1">
    <div class="modal-dialog">
      <form method="POST" class="modal-content">
        <div class="modal-header bg-primary text-white"><h5 class="modal-title">Add Semester</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
          <input type="hidden" name="action" value="add_semester">
          <div class="row g-3">
            <div class="col-md-6"><label class="form-label">Year *</label><input type="text" name="academic_year" class="form-control" required></div>
            <div class="col-md-6"><label class="form-label">Semester *</label><input type="text" name="semester_name" class="form-control" placeholder="First Semester" required></div>
            <div class="col-md-6"><label class="form-label">Start</label><input type="date" name="start_date" class="form-control"></div>
            <div class="col-md-6"><label class="form-label">End</label><input type="date" name="end_date" class="form-control"></div>
          </div>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-reg">Save</button></div>
      </form>
    </div>
  </div>

  <div class="main content-section dashboard-section" data-section="examinations">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h5 class="fw-bold"><i class="fas fa-pen me-2"></i>Examinations & Marks Entry</h5>
      <button class="btn btn-reg" data-bs-toggle="modal" data-bs-target="#enterMarksModal"><i class="fas fa-plus me-1"></i>Enter Marks</button>
    </div>
    <?php
    $examRecords = [];
    if ($staff) {
      $eR = $staff->query("SELECT er.*, s.full_name FROM examination_records er LEFT JOIN {$students_db}.students s ON er.student_id = s.id ORDER BY er.created_at DESC LIMIT 100");
      if ($eR) { while ($row = $eR->fetch_assoc()) $examRecords[] = $row; $eR->close(); }
    }
    ?>
    <div class="section-card p-0">
      <div class="table-responsive">
        <table class="table table-hover mb-0 data-table">
          <thead class="table-light"><tr><th>Student</th><th>Course</th><th>Type</th><th>CA</th><th>Exam</th><th>Total</th><th>Grade</th><th>Status</th><th>Verify</th></tr></thead>
          <tbody>
            <?php if (empty($examRecords)): ?><tr><td colspan="9" class="text-center py-4 text-muted">No records.</td></tr>
            <?php else: foreach ($examRecords as $er): ?>
            <tr><td><?= htmlspecialchars($er['full_name'] ?? "ID:{$er['student_id']}") ?></td><td><code><?= htmlspecialchars($er['course_code']) ?></code></td><td><?= htmlspecialchars($er['exam_type']) ?></td><td><?= $er['continuous_assessment_marks'] ?? '-' ?></td><td><?= $er['final_exam_marks'] ?? '-' ?></td><td><?= ($er['marks_obtained'] ?? 0) . '/' . ($er['total_marks'] ?? 100) ?></td><td><strong><?= htmlspecialchars($er['grade'] ?? '-') ?></strong></td><td><span class="badge bg-<?= ($er['grade_status'] ?? 'Pending') === 'Verified' ? 'success' : 'warning' ?>"><?= htmlspecialchars($er['grade_status'] ?? 'Pending') ?></span></td>
            <td><?php if (($er['grade_status'] ?? 'Pending') !== 'Verified'): ?><form method="POST" class="d-inline"><input type="hidden" name="action" value="verify_marks"><input type="hidden" name="record_id" value="<?= $er['id'] ?>"><button type="submit" class="btn btn-sm btn-outline-success py-0 px-2"><i class="fas fa-check"></i></button></form><?php endif; ?></td></tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="modal fade" id="enterMarksModal" tabindex="-1">
    <div class="modal-dialog">
      <form method="POST" class="modal-content">
        <div class="modal-header bg-primary text-white"><h5 class="modal-title"><i class="fas fa-pen me-2"></i>Enter Marks</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
          <input type="hidden" name="action" value="enter_marks">
          <div class="mb-3">
            <label class="form-label">Student *</label>
            <div class="student-lookup-wrapper">
              <input type="text" class="form-control student-lookup" placeholder="Search student..." data-target="marks">
              <input type="hidden" name="student_id" class="student-id-target">
              <div class="lookup-results"></div>
            </div>
            <div class="selected-student-info d-none mt-2 p-2 small bg-light rounded"></div>
          </div>
          <div class="row g-3">
            <div class="col-md-6"><label class="form-label">Course Code *</label><input type="text" name="course_code" class="form-control" list="courseCodeList" required>
              <datalist id="courseCodeList"><?php foreach ($courses as $c): ?><option value="<?= htmlspecialchars($c['course_code']) ?>"><?php endforeach; ?></datalist>
            </div>
            <div class="col-md-6"><label class="form-label">Type</label><select name="exam_type" class="form-select"><option>Final</option><option>Supplementary</option><option>Retake</option><option>Special</option></select></div>
            <div class="col-md-6"><label class="form-label">CA Marks</label><input type="number" step="0.1" name="continuous_assessment_marks" class="form-control" value="0"></div>
            <div class="col-md-6"><label class="form-label">Exam Marks</label><input type="number" step="0.1" name="final_exam_marks" class="form-control" value="0"></div>
            <div class="col-md-6"><label class="form-label">Total</label><input type="number" step="0.1" name="total_marks" class="form-control" value="100"></div>
          </div>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-reg"><i class="fas fa-save me-1"></i>Save</button></div>
      </form>
    </div>
  </div>

  <div class="main content-section dashboard-section" data-section="approvals">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h5 class="fw-bold"><i class="fas fa-check-double me-2"></i>Results & Approvals</h5>
      <div class="d-flex gap-2">
        <button class="btn btn-reg" data-bs-toggle="modal" data-bs-target="#approveResultsModal"><i class="fas fa-check me-1"></i>Approve</button>
        <button class="btn btn-reg-accent" data-bs-toggle="modal" data-bs-target="#publishResultsModal"><i class="fas fa-bullhorn me-1"></i>Publish</button>
      </div>
    </div>
    <?php
    $workflows = [];
    if ($staff) {
      $wR = $staff->query("SELECT w.*, er.student_id, er.course_code, er.marks_obtained, er.grade, s.full_name FROM grading_approval_workflow w LEFT JOIN examination_records er ON w.examination_record_id = er.id LEFT JOIN {$students_db}.students s ON er.student_id = s.id ORDER BY w.workflow_number DESC LIMIT 50");
      if ($wR) { while ($row = $wR->fetch_assoc()) $workflows[] = $row; $wR->close(); }
    }
    ?>
    <div class="section-card p-0">
      <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead class="table-light"><tr><th>W#</th><th>Student</th><th>Course</th><th>Marks</th><th>HOD</th><th>Registrar</th><th>Principal</th><th>Approve</th></tr></thead>
          <tbody>
            <?php if (empty($workflows)): ?><tr><td colspan="8" class="text-center py-4 text-muted">No workflows.</td></tr>
            <?php else: foreach ($workflows as $w): ?>
            <tr><td><code>#<?= $w['workflow_number'] ?></code></td><td><?= htmlspecialchars($w['full_name'] ?? "ID:{$w['student_id']}") ?></td><td><?= htmlspecialchars($w['course_code']) ?></td><td><?= $w['marks_obtained'] ?? '-' ?></td>
            <td><span class="badge bg-<?= ($w['hod_status'] ?? 'Pending') === 'Approved' ? 'success' : 'warning' ?>"><?= htmlspecialchars($w['hod_status'] ?? 'Pending') ?></span></td>
            <td><span class="badge bg-<?= ($w['registrar_status'] ?? 'Pending') === 'Approved' ? 'success' : 'warning' ?>"><?= htmlspecialchars($w['registrar_status'] ?? 'Pending') ?></span></td>
            <td><span class="badge bg-<?= ($w['principal_status'] ?? 'Pending') === 'Approved' ? 'success' : 'warning' ?>"><?= htmlspecialchars($w['principal_status'] ?? 'Pending') ?></span></td>
            <td><?php if (($w['registrar_status'] ?? 'Pending') !== 'Approved'): ?><form method="POST" class="d-inline"><input type="hidden" name="action" value="approve_results"><input type="hidden" name="workflow_id" value="<?= $w['workflow_number'] ?>"><button type="submit" class="btn btn-sm btn-outline-success py-0 px-2"><i class="fas fa-check"></i></button></form><?php endif; ?></td></tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="modal fade" id="approveResultsModal" tabindex="-1">
    <div class="modal-dialog">
      <form method="POST" class="modal-content">
        <div class="modal-header bg-success text-white"><h5 class="modal-title"><i class="fas fa-check me-2"></i>Approve Results (Registrar)</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
          <input type="hidden" name="action" value="approve_results">
          <div class="mb-3"><label class="form-label">Workflow ID</label><input type="number" name="workflow_id" class="form-control" required></div>
          <div class="mb-3"><label class="form-label">Remarks</label><textarea name="remarks" class="form-control" rows="2"></textarea></div>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-success"><i class="fas fa-check me-1"></i>Approve</button></div>
      </form>
    </div>
  </div>

  <div class="modal fade" id="publishResultsModal" tabindex="-1">
    <div class="modal-dialog">
      <form method="POST" class="modal-content">
        <div class="modal-header bg-warning"><h5 class="modal-title"><i class="fas fa-bullhorn me-2"></i>Publish Results</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
          <input type="hidden" name="action" value="publish_result">
          <div class="mb-3"><label class="form-label">Year</label><input type="text" name="academic_year" class="form-control" value="<?= date('Y') ?>"></div>
          <div class="mb-3"><label class="form-label">Semester</label><select name="semester" class="form-select"><option>First Semester</option><option>Second Semester</option></select></div>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-warning"><i class="fas fa-bullhorn me-1"></i>Publish</button></div>
      </form>
    </div>
  </div>

  <div class="main content-section dashboard-section" data-section="transcripts">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h5 class="fw-bold"><i class="fas fa-file-alt me-2"></i>Transcripts</h5>
      <button class="btn btn-reg" data-bs-toggle="modal" data-bs-target="#generateTranscriptModal"><i class="fas fa-plus me-1"></i>Generate</button>
    </div>
    <?php
    $transcripts = [];
    if ($staff) {
      $tR = $staff->query("SELECT t.*, s.full_name, s.student_number FROM transcripts t LEFT JOIN {$students_db}.students s ON t.student_id = s.id ORDER BY t.generated_at DESC LIMIT 50");
      if ($tR) { while ($row = $tR->fetch_assoc()) $transcripts[] = $row; $tR->close(); }
    }
    ?>
    <div class="section-card p-0">
      <div class="table-responsive">
        <table class="table table-hover mb-0 data-table">
          <thead class="table-light"><tr><th>#</th><th>Student</th><th>Transcript No</th><th>Status</th><th>DL</th><th>Actions</th></tr></thead>
          <tbody>
            <?php if (empty($transcripts)): ?><tr><td colspan="6" class="text-center py-4 text-muted">No transcripts.</td></tr>
            <?php else: foreach ($transcripts as $t): ?>
            <tr><td><?= $t['id'] ?></td><td><?= htmlspecialchars($t['full_name'] ?? "ID:{$t['student_id']}") ?></td><td><code><?= htmlspecialchars($t['transcript_number']) ?></code></td>
            <td><span class="badge bg-<?= $t['status'] === 'Published' ? 'success' : ($t['status'] === 'Draft' ? 'secondary' : 'warning') ?>"><?= htmlspecialchars($t['status']) ?></span></td>
            <td><?= $t['is_downloadable'] ? '<span class="badge bg-success">Yes</span>' : '<span class="badge bg-secondary">No</span>' ?></td>
            <td>
              <button class="btn btn-sm btn-outline-primary py-0 px-2" onclick="previewTranscript(<?= $t['student_id'] ?>)" title="Preview"><i class="fas fa-eye"></i></button>
              <?php if (!$t['is_downloadable']): ?><button class="btn btn-sm btn-outline-success py-0 px-2" onclick="markDownloadable('transcript', <?= $t['id'] ?>)" title="DL"><i class="fas fa-download"></i></button><?php endif; ?>
              <button class="btn btn-sm btn-outline-danger py-0 px-2" onclick="archiveDoc('transcript', <?= $t['id'] ?>)" title="Archive"><i class="fas fa-archive"></i></button>
            </td></tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="modal fade" id="generateTranscriptModal" tabindex="-1">
    <div class="modal-dialog">
      <form method="POST" class="modal-content">
        <div class="modal-header bg-primary text-white"><h5 class="modal-title"><i class="fas fa-file-alt me-2"></i>Generate Transcript</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
          <input type="hidden" name="action" value="generate_transcript">
          <div class="mb-3">
            <label class="form-label">Student *</label>
            <div class="student-lookup-wrapper">
              <input type="text" class="form-control student-lookup" placeholder="Search student..." data-target="transcript">
              <input type="hidden" name="student_id" class="student-id-target">
              <div class="lookup-results"></div>
            </div>
            <div class="selected-student-info d-none mt-2 p-2 small bg-light rounded"></div>
          </div>
          <p class="text-muted small">Includes all verified exam records.</p>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-reg"><i class="fas fa-file-alt me-1"></i>Generate</button></div>
      </form>
    </div>
  </div>

  <div class="main content-section dashboard-section" data-section="certificates">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h5 class="fw-bold"><i class="fas fa-certificate me-2"></i>Certificates</h5>
      <button class="btn btn-reg" data-bs-toggle="modal" data-bs-target="#generateCertificateModal"><i class="fas fa-plus me-1"></i>Generate</button>
    </div>
    <?php
    $certificates = [];
    if ($staff) {
      $cR = $staff->query("SELECT c.*, s.full_name, s.student_number FROM certificates c LEFT JOIN {$students_db}.students s ON c.student_id = s.id ORDER BY c.generated_at DESC LIMIT 50");
      if ($cR) { while ($row = $cR->fetch_assoc()) $certificates[] = $row; $cR->close(); }
    }
    ?>
    <div class="section-card p-0">
      <div class="table-responsive">
        <table class="table table-hover mb-0 data-table">
          <thead class="table-light"><tr><th>#</th><th>Student</th><th>Certificate No</th><th>Type</th><th>Status</th><th>DL</th><th>Actions</th></tr></thead>
          <tbody>
            <?php if (empty($certificates)): ?><tr><td colspan="7" class="text-center py-4 text-muted">No certificates.</td></tr>
            <?php else: foreach ($certificates as $c): ?>
            <tr><td><?= $c['id'] ?></td><td><?= htmlspecialchars($c['full_name'] ?? "ID:{$c['student_id']}") ?></td><td><code><?= htmlspecialchars($c['certificate_number']) ?></code></td><td><?= htmlspecialchars($c['certificate_type']) ?></td>
            <td><span class="badge bg-<?= $c['status'] === 'Published' ? 'success' : ($c['status'] === 'Draft' ? 'secondary' : 'warning') ?>"><?= htmlspecialchars($c['status']) ?></span></td>
            <td><?= $c['is_downloadable'] ? '<span class="badge bg-success">Yes</span>' : '<span class="badge bg-secondary">No</span>' ?></td>
            <td>
              <button class="btn btn-sm btn-outline-primary py-0 px-2" onclick="previewCertificate(<?= $c['student_id'] ?>)" title="Preview"><i class="fas fa-eye"></i></button>
              <?php if (!$c['is_downloadable']): ?><button class="btn btn-sm btn-outline-success py-0 px-2" onclick="markDownloadable('certificate', <?= $c['id'] ?>)" title="DL"><i class="fas fa-download"></i></button><?php endif; ?>
              <button class="btn btn-sm btn-outline-danger py-0 px-2" onclick="archiveDoc('certificate', <?= $c['id'] ?>)" title="Archive"><i class="fas fa-archive"></i></button>
            </td></tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="modal fade" id="generateCertificateModal" tabindex="-1">
    <div class="modal-dialog">
      <form method="POST" class="modal-content">
        <div class="modal-header bg-primary text-white"><h5 class="modal-title"><i class="fas fa-certificate me-2"></i>Generate Certificate</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
          <input type="hidden" name="action" value="generate_certificate">
          <div class="mb-3">
            <label class="form-label">Student *</label>
            <div class="student-lookup-wrapper">
              <input type="text" class="form-control student-lookup" placeholder="Search student..." data-target="cert">
              <input type="hidden" name="student_id" class="student-id-target">
              <div class="lookup-results"></div>
            </div>
            <div class="selected-student-info d-none mt-2 p-2 small bg-light rounded"></div>
          </div>
          <div class="row g-3">
            <div class="col-md-6"><label class="form-label">Type</label><select name="certificate_type" class="form-select"><option>Certificate</option><option>Diploma</option><option>Degree</option><option>Transcript</option></select></div>
            <div class="col-md-6"><label class="form-label">Completion Date</label><input type="date" name="completion_date" class="form-control"></div>
          </div>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-reg"><i class="fas fa-certificate me-1"></i>Generate</button></div>
      </form>
    </div>
  </div>

  <div class="main content-section dashboard-section" data-section="graduation">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h5 class="fw-bold"><i class="fas fa-graduation-cap me-2"></i>Graduation Management</h5>
      <div class="d-flex gap-2">
        <button class="btn btn-reg" data-bs-toggle="modal" data-bs-target="#addGraduationModal"><i class="fas fa-plus me-1"></i>Add Candidate</button>
        <button class="btn btn-reg-outline" data-bs-toggle="modal" data-bs-target="#approveGraduationModal"><i class="fas fa-check me-1"></i>Approve</button>
      </div>
    </div>
    <?php
    $gradCandidates = [];
    if ($staff) {
      $gR = $staff->query("SELECT gc.*, s.full_name, s.student_number, p.program_name FROM graduation_candidates gc LEFT JOIN {$students_db}.students s ON gc.student_id = s.id LEFT JOIN academic_programs p ON gc.program_id = p.id ORDER BY gc.submitted_at DESC LIMIT 50");
      if ($gR) { while ($row = $gR->fetch_assoc()) $gradCandidates[] = $row; $gR->close(); }
    }
    ?>
    <div class="section-card p-0">
      <div class="table-responsive">
        <table class="table table-hover mb-0 data-table">
          <thead class="table-light"><tr><th>#</th><th>Student</th><th>Program</th><th>Grad Year</th><th>Status</th><th>Submitted</th></tr></thead>
          <tbody>
            <?php if (empty($gradCandidates)): ?><tr><td colspan="6" class="text-center py-4 text-muted">No candidates.</td></tr>
            <?php else: foreach ($gradCandidates as $gc): ?>
            <tr><td><?= $gc['id'] ?></td><td><?= htmlspecialchars($gc['full_name'] ?? "ID:{$gc['student_id']}") ?></td><td><?= htmlspecialchars($gc['program_name'] ?? "-") ?></td><td><?= htmlspecialchars($gc['graduation_year']) ?></td>
            <td><span class="badge bg-<?= $gc['status'] === 'Approved by Registrar' ? 'success' : ($gc['status'] === 'Pending' ? 'warning' : 'secondary') ?>"><?= htmlspecialchars($gc['status']) ?></span></td>
            <td><small><?= $gc['submitted_at'] ? date('d M Y', strtotime($gc['submitted_at'])) : '-' ?></small></td></tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="modal fade" id="addGraduationModal" tabindex="-1">
    <div class="modal-dialog">
      <form method="POST" class="modal-content">
        <div class="modal-header bg-primary text-white"><h5 class="modal-title"><i class="fas fa-plus me-2"></i>Add Graduation Candidate</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
          <input type="hidden" name="action" value="add_to_graduation">
          <div class="mb-3">
            <label class="form-label">Student *</label>
            <div class="student-lookup-wrapper">
              <input type="text" class="form-control student-lookup" placeholder="Search student..." data-target="grad">
              <input type="hidden" name="student_id" class="student-id-target">
              <div class="lookup-results"></div>
            </div>
            <div class="selected-student-info d-none mt-2 p-2 small bg-light rounded"></div>
          </div>
          <div class="mb-3"><label class="form-label">Program</label><select name="program_id" class="form-select"><?php foreach ($programs as $p): ?><option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['program_name']) ?></option><?php endforeach; ?></select></div>
          <div class="mb-3"><label class="form-label">Graduation Year</label><input type="text" name="graduation_year" class="form-control" value="<?= date('Y') ?>"></div>
          <div class="mb-3"><label class="form-label">Remarks</label><textarea name="remarks" class="form-control" rows="2"></textarea></div>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-reg"><i class="fas fa-save me-1"></i>Add</button></div>
      </form>
    </div>
  </div>

  <div class="modal fade" id="approveGraduationModal" tabindex="-1">
    <div class="modal-dialog">
      <form method="POST" class="modal-content">
        <div class="modal-header bg-success text-white"><h5 class="modal-title"><i class="fas fa-check me-2"></i>Approve Graduation</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
          <input type="hidden" name="action" value="approve_graduation">
          <div class="mb-3"><label class="form-label">Candidate ID</label><input type="number" name="candidate_id" class="form-control" required></div>
          <div class="mb-3"><label class="form-label">Remarks</label><textarea name="remarks" class="form-control" rows="2"></textarea></div>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-success"><i class="fas fa-check me-1"></i>Approve</button></div>
      </form>
    </div>
  </div>

  <div class="main content-section dashboard-section" data-section="notifications">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h5 class="fw-bold"><i class="fas fa-bell me-2"></i>Send Notice</h5>
      <button class="btn btn-reg" data-bs-toggle="modal" data-bs-target="#sendNoticeModal"><i class="fas fa-paper-plane me-1"></i>New Notice</button>
    </div>
  </div>

  <div class="modal fade" id="sendNoticeModal" tabindex="-1">
    <div class="modal-dialog">
      <form method="POST" class="modal-content">
        <div class="modal-header bg-primary text-white"><h5 class="modal-title"><i class="fas fa-paper-plane me-2"></i>Send Notice</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
          <input type="hidden" name="action" value="send_notice">
          <div class="mb-3"><label class="form-label">Recipient Type</label><select name="recipient_type" class="form-select"><option value="student">Student</option><option value="staff">Staff</option><option value="all">All</option></select></div>
          <div class="mb-3"><label class="form-label">Recipient ID (optional)</label><input type="number" name="recipient_id" class="form-control"></div>
          <div class="mb-3"><label class="form-label">Subject *</label><input type="text" name="subject" class="form-control" required></div>
          <div class="mb-3"><label class="form-label">Message *</label><textarea name="message" class="form-control" rows="4" required></textarea></div>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-reg"><i class="fas fa-paper-plane me-1"></i>Send</button></div>
      </form>
    </div>
  </div>

  <div class="main content-section dashboard-section" data-section="generic">
    <div class="section-card text-center py-5">
      <i class="fas fa-cogs fa-3x mb-3" style="color:#94a3b8;"></i>
      <h4 id="genericSectionTitle" class="fw-bold">Section Name</h4>
      <p id="genericSectionDesc" class="text-muted mb-4">This section is under development.</p>
      <button class="btn btn-reg-outline" onclick="switchSection('dashboard')"><i class="fas fa-arrow-left me-1"></i>Back to Dashboard</button>
    </div>
  </div>

  <div class="main content-section dashboard-section" data-section="settings">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h5 class="fw-bold"><i class="fas fa-cog me-2"></i>Settings</h5>
      <button class="btn btn-reg" data-bs-toggle="modal" data-bs-target="#saveSettingModal"><i class="fas fa-plus me-1"></i>Add Setting</button>
    </div>
    <?php
    $settings = [];
    if ($staff) {
      $sR = $staff->query("SELECT * FROM gpa_settings ORDER BY setting_key");
      if ($sR) { while ($row = $sR->fetch_assoc()) $settings[] = $row; $sR->close(); }
    }
    ?>
    <div class="section-card">
      <table class="table table-sm">
        <thead><tr><th>Key</th><th>Value</th><th>Description</th></tr></thead>
        <tbody><?php if (empty($settings)): ?><tr><td colspan="3" class="text-muted">No settings.</td></tr><?php else: foreach ($settings as $s): ?>
        <tr><td><code><?= htmlspecialchars($s['setting_key']) ?></code></td><td><?= htmlspecialchars($s['setting_value']) ?></td><td><small class="text-muted"><?= htmlspecialchars($s['description'] ?? '') ?></small></td></tr>
        <?php endforeach; endif; ?></tbody>
      </table>
    </div>
  </div>

  <div class="modal fade" id="saveSettingModal" tabindex="-1">
    <div class="modal-dialog">
      <form method="POST" class="modal-content">
        <div class="modal-header bg-primary text-white"><h5 class="modal-title">Add/Edit Setting</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
          <input type="hidden" name="action" value="save_setting">
          <div class="mb-3"><label class="form-label">Key *</label><input type="text" name="setting_key" class="form-control" required></div>
          <div class="mb-3"><label class="form-label">Value *</label><input type="text" name="setting_value" class="form-control" required></div>
          <div class="mb-3"><label class="form-label">Description</label><input type="text" name="description" class="form-control"></div>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-reg"><i class="fas fa-save me-1"></i>Save</button></div>
      </form>
    </div>
  </div>

  <div class="modal fade" id="registerStudentModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
      <form method="POST" class="modal-content">
        <div class="modal-header bg-primary text-white"><h5 class="modal-title"><i class="fas fa-user-plus me-2"></i>Register New Student</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
          <input type="hidden" name="action" value="register_student">
          <div class="row g-3">
            <div class="col-md-4"><label class="form-label">First Name *</label><input type="text" name="first_name" class="form-control" required></div>
            <div class="col-md-4"><label class="form-label">Last Name *</label><input type="text" name="last_name" class="form-control" required></div>
            <div class="col-md-4"><label class="form-label">Gender</label><select name="gender" class="form-select"><option>Male</option><option>Female</option><option>Other</option></select></div>
            <div class="col-md-6"><label class="form-label">Program *</label><select name="program" class="form-select" required><?php foreach ($programs as $p): ?><option value="<?= htmlspecialchars($p['program_name']) ?>"><?= htmlspecialchars($p['program_name']) ?></option><?php endforeach; ?></select></div>
            <div class="col-md-6"><label class="form-label">Level</label><select name="level" class="form-select"><option value="1">Level 1</option><option value="2">Level 2</option><option value="3">Level 3</option><option value="4">Level 4</option></select></div>
            <div class="col-md-6"><label class="form-label">Phone</label><input type="text" name="phone" class="form-control"></div>
            <div class="col-md-6"><label class="form-label">Email</label><input type="email" name="email" class="form-control"></div>
          </div>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-reg"><i class="fas fa-save me-1"></i>Register</button></div>
      </form>
    </div>
  </div>

  <div class="modal fade" id="transferStudentModal" tabindex="-1">
    <div class="modal-dialog">
      <form method="POST" class="modal-content">
        <div class="modal-header bg-primary text-white"><h5 class="modal-title"><i class="fas fa-exchange-alt me-2"></i>Transfer Student</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
          <input type="hidden" name="action" value="transfer_student">
          <div class="mb-3">
            <label class="form-label">Student *</label>
            <div class="student-lookup-wrapper">
              <input type="text" class="form-control student-lookup" placeholder="Search student...">
              <input type="hidden" name="student_id" class="student-id-target">
              <div class="lookup-results"></div>
            </div>
            <div class="selected-student-info d-none mt-2 p-2 small bg-light rounded"></div>
          </div>
          <div class="mb-3"><label class="form-label">New Program *</label><select name="new_program" class="form-select" required><?php foreach ($programs as $p): ?><option value="<?= htmlspecialchars($p['program_name']) ?>"><?= htmlspecialchars($p['program_name']) ?></option><?php endforeach; ?></select></div>
          <div class="mb-3"><label class="form-label">New Level</label><select name="new_level" class="form-select"><option value="1">Level 1</option><option value="2">Level 2</option><option value="3">Level 3</option><option value="4">Level 4</option></select></div>
          <div class="mb-3"><label class="form-label">Remarks</label><textarea name="remarks" class="form-control" rows="2"></textarea></div>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-reg"><i class="fas fa-save me-1"></i>Transfer</button></div>
      </form>
    </div>
  </div>

  <div class="modal fade" id="deferStudentModal" tabindex="-1">
    <div class="modal-dialog">
      <form method="POST" class="modal-content">
        <div class="modal-header bg-warning"><h5 class="modal-title"><i class="fas fa-pause me-2"></i>Defer Student</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
          <input type="hidden" name="action" value="defer_student">
          <div class="mb-3">
            <label class="form-label">Student *</label>
            <div class="student-lookup-wrapper">
              <input type="text" class="form-control student-lookup" placeholder="Search student...">
              <input type="hidden" name="student_id" class="student-id-target">
              <div class="lookup-results"></div>
            </div>
            <div class="selected-student-info d-none mt-2 p-2 small bg-light rounded"></div>
          </div>
          <div class="mb-3"><label class="form-label">Remarks</label><textarea name="remarks" class="form-control" rows="2"></textarea></div>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-warning"><i class="fas fa-pause me-1"></i>Defer</button></div>
      </form>
    </div>
  </div>

  <div class="modal fade" id="withdrawStudentModal" tabindex="-1">
    <div class="modal-dialog">
      <form method="POST" class="modal-content">
        <div class="modal-header bg-danger text-white"><h5 class="modal-title"><i class="fas fa-times me-2"></i>Withdraw Student</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
          <input type="hidden" name="action" value="withdraw_student">
          <div class="mb-3">
            <label class="form-label">Student *</label>
            <div class="student-lookup-wrapper">
              <input type="text" class="form-control student-lookup" placeholder="Search student...">
              <input type="hidden" name="student_id" class="student-id-target">
              <div class="lookup-results"></div>
            </div>
            <div class="selected-student-info d-none mt-2 p-2 small bg-light rounded"></div>
          </div>
          <div class="mb-3"><label class="form-label">Remarks</label><textarea name="remarks" class="form-control" rows="2"></textarea></div>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-danger"><i class="fas fa-times me-1"></i>Withdraw</button></div>
      </form>
    </div>
  </div>

  <div class="modal fade" id="readmitStudentModal" tabindex="-1">
    <div class="modal-dialog">
      <form method="POST" class="modal-content">
        <div class="modal-header bg-success text-white"><h5 class="modal-title"><i class="fas fa-undo me-2"></i>Readmit Student</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
          <input type="hidden" name="action" value="readmit_student">
          <div class="mb-3">
            <label class="form-label">Student *</label>
            <div class="student-lookup-wrapper">
              <input type="text" class="form-control student-lookup" placeholder="Search student...">
              <input type="hidden" name="student_id" class="student-id-target">
              <div class="lookup-results"></div>
            </div>
            <div class="selected-student-info d-none mt-2 p-2 small bg-light rounded"></div>
          </div>
          <div class="mb-3"><label class="form-label">Remarks</label><textarea name="remarks" class="form-control" rows="2"></textarea></div>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-success"><i class="fas fa-undo me-1"></i>Readmit</button></div>
      </form>
    </div>
  </div>

<?php include __DIR__ . '/../includes/dashboard_footer.php'; ?>

<!-- ═══ AJAX MODULE LOADING ═══ -->
<div id="ajaxLoadingOverlay" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(255,255,255,.7);z-index:9999;align-items:center;justify-content:center;">
  <div style="text-align:center;padding:30px;background:#fff;border-radius:14px;box-shadow:0 8px 30px rgba(0,0,0,.12);">
    <i class="fas fa-spinner fa-spin" style="font-size:28px;color:#3b82f6;"></i>
    <p style="margin:12px 0 0;font-size:13px;color:#64748b;">Loading module...</p>
  </div>
</div>
<script>
(function(){
    var contentArea = document.querySelector('.reg-content');
    var loadingOverlay = document.getElementById('ajaxLoadingOverlay');
    var isAjaxLoading = false;

    function showLoading() { if (loadingOverlay) loadingOverlay.style.display = 'flex'; isAjaxLoading = true; }
    function hideLoading() { if (loadingOverlay) loadingOverlay.style.display = 'none'; isAjaxLoading = false; }

    document.querySelectorAll('.child-link').forEach(function(link) {
        link.addEventListener('click', function(e) {
            var href = this.getAttribute('href');
            if (!href || href.indexOf('?') === -1) return;
            if (isAjaxLoading) return;

            e.preventDefault();
            showLoading();
            history.pushState({}, '', href);
            document.querySelectorAll('.child-link').forEach(function(l) { l.classList.remove('active'); });
            this.classList.add('active');

            var section = href.split('section=')[1] || href.split('page=')[1] || 'dashboard';
            fetch('academic-registrar.php?section=' + encodeURIComponent(section), {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(function(r) { return r.text(); })
            .then(function(html) {
                var parser = new DOMParser();
                var doc = parser.parseFromString(html, 'text/html');
                var newContent = doc.querySelector('.reg-content');
                if (newContent && contentArea) {
                    contentArea.innerHTML = newContent.innerHTML;
                    contentArea.querySelectorAll('script').forEach(function(oldScript) {
                        var newScript = document.createElement('script');
                        if (oldScript.src) { newScript.src = oldScript.src; }
                        else { newScript.textContent = oldScript.textContent; }
                        oldScript.parentNode.replaceChild(newScript, oldScript);
                    });
                }
                hideLoading();
            })
            .catch(function(err) {
                console.error('[AJAX Load Error]', err);
                hideLoading();
                window.location.href = href;
            });
        });
    });

    window.addEventListener('popstate', function() { window.location.reload(); });

    document.querySelectorAll('.child-link').forEach(function(link) {
        link.addEventListener('click', function() {
            if (window.innerWidth <= 768) {
                var sidebar = document.querySelector('.isnm-sidebar');
                if (sidebar) sidebar.classList.remove('open', 'mobile-show');
            }
        });
    });
})();

function openProfileModal(){var m=document.getElementById('profileModal');if(m){var bsModal=new bootstrap.Modal(m);bsModal.show();}}
</script>

</div>

<?php
require_once __DIR__ . '/../includes/profile_settings.php';
if (function_exists('renderProfileModal')) renderProfileModal();
if (function_exists('renderProfileStyles')) renderProfileStyles();
if (function_exists('renderProfileScripts')) renderProfileScripts();
?>
</body>
</html>
