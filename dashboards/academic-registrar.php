<?php
// Include unified authentication system
require_once '../includes/functions.php';
require_once '../auth-service.php';

// Start secure session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Global authentication service
$auth_service = new AuthenticationService();

// Strict dashboard protection - only Academic Registrar allowed
if (!$auth_service->isAuthenticated()) {
    header('Location: ../staff-login.php');
    exit();
}

// Check if user has the correct role
$userRole = $_SESSION['role'] ?? '';
if (stripos($userRole, 'academic') === false && stripos($userRole, 'registrar') === false) {
    header('Location: ../staff-login.php?error=unauthorized');
    exit();
}

// Enhanced database connections
$students_conn = new mysqli('localhost', 'root', '', 'students_db');
$staff_conn = new mysqli('localhost', 'root', '', 'staffs_db');

if ($students_conn->connect_error) {
    die("Students DB connection failed: " . $students_conn->connect_error);
}

if ($staff_conn->connect_error) {
    die("Staff DB connection failed: " . $staff_conn->connect_error);
}

// Set charset
$students_conn->set_charset("utf8mb4");
$staff_conn->set_charset("utf8mb4");

// Get user information from session
$user_id = $_SESSION['user_id'] ?? 0;
$user_email = $_SESSION['email'] ?? '';
$user_name = $_SESSION['full_name'] ?? '';
$user_role = $_SESSION['role'] ?? '';

// Normalize user profile data from session
$user = [
    'id' => $user_id,
    'email' => $user_email,
    'full_name' => $user_name,
    'first_name' => explode(' ', trim($user_name))[0] ?? 'Academic',
    'last_name' => trim(preg_replace('/^[^ ]+\s*/', '', trim($user_name))) ?: ''
];

function tableExists($conn, $table) {
    $stmt = $conn->prepare("SHOW TABLES LIKE ?");
    if (!$stmt) {
        return false;
    }
    $stmt->bind_param("s", $table);
    $stmt->execute();
    $result = $stmt->get_result();
    $exists = $result && $result->num_rows > 0;
    $stmt->close();
    return $exists;
}

function getCount($conn, $sql, $params = [], $types = '') {
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return 0;
    }
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    if (!$stmt->execute()) {
        $stmt->close();
        return 0;
    }
    $result = $stmt->get_result();
    $count = 0;
    if ($result) {
        $row = $result->fetch_assoc();
        $count = intval($row['count'] ?? 0);
    }
    $stmt->close();
    return $count;
}

// Statistics for the dashboard
$total_students = tableExists($students_conn, 'students') ? getCount($students_conn, "SELECT COUNT(*) AS count FROM students") : 0;
$registered_students = tableExists($students_conn, 'students') ? getCount($students_conn, "SELECT COUNT(*) AS count FROM students WHERE status IN ('Active', 'active')") : 0;
$new_admissions = tableExists($students_conn, 'students') ? getCount($students_conn, "SELECT COUNT(*) AS count FROM students WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)") : 0;

$total_applications = tableExists($staff_conn, 'grading_approval_workflow') ? getCount($staff_conn, "SELECT COUNT(*) AS count FROM grading_approval_workflow") : 0;
$pending_registrations = tableExists($staff_conn, 'grading_approval_workflow') ? getCount($staff_conn, "SELECT COUNT(*) AS count FROM grading_approval_workflow WHERE current_stage IN ('HOD Review', 'Registrar Approval', 'Principal Final Approval')") : 0;
$transcripts_issued = tableExists($staff_conn, 'transcript_generation_log') ? getCount($staff_conn, "SELECT COUNT(*) AS count FROM transcript_generation_log WHERE status IN ('Generated', 'Approved')") : 0;
$exam_results_pending = tableExists($staff_conn, 'examination_records') ? getCount($staff_conn, "SELECT COUNT(*) AS count FROM examination_records WHERE grade IS NULL OR grade = ''") : 0;
$course_registrations = tableExists($staff_conn, 'course_registrations') ? getCount($staff_conn, "SELECT COUNT(*) AS count FROM course_registrations WHERE status = 'Registered'") : 0;
$graduation_candidates = tableExists($students_conn, 'students') ? getCount($students_conn, "SELECT COUNT(*) AS count FROM students WHERE status IN ('Graduated', 'graduation_candidate', 'Graduation Candidate')") : 0;
$notifications_announcements = tableExists($students_conn, 'student_notifications') ? getCount($students_conn, "SELECT COUNT(*) AS count FROM student_notifications WHERE is_read = 0") : 0;
$calendar_reminders = tableExists($staff_conn, 'academic_calendar') ? getCount($staff_conn, "SELECT COUNT(*) AS count FROM academic_calendar WHERE semester_start_date BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 30 DAY)") : 0;
$data_files_count = count(glob(__DIR__ . '/../students_data/*.xlsx'));

// Handle search and filter functionality
$search_term = $_GET['search'] ?? '';
$filter_course = $_GET['course'] ?? '';
$filter_year = $_GET['year'] ?? '';
$filter_semester = $_GET['semester'] ?? '';
$filter_status = $_GET['status'] ?? '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'add_student':
            handleAddStudent();
            break;
        case 'update_student':
            handleUpdateStudent();
            break;
        case 'delete_student':
            handleDeleteStudent();
            break;
        case 'generate_transcript':
            handleGenerateTranscript();
            break;
        case 'generate_results':
            handleGenerateResults();
            break;
        case 'bulk_import':
            handleBulkImport();
            break;
        case 'enter_grades':
            handleEnterGrades();
            break;
        case 'approve_grade':
            handleApproveGrade();
            break;
        case 'reject_grade':
            handleRejectGrade();
            break;
        case 'publish_results':
            handlePublishResults();
            break;
        case 'add_calendar':
            handleAddCalendar();
            break;
    }
}


// Get recent students for profile display from database
$recent_students_query = "SELECT id, full_name, registration_number, course, status, created_at 
                         FROM students 
                         ORDER BY created_at DESC 
                         LIMIT 4";
$recent_students_result = $students_conn->query($recent_students_query);
$recent_students = [];
if ($recent_students_result) {
    while ($row = $recent_students_result->fetch_assoc()) {
        $recent_students[] = $row;
    }
}

// Get real activity logs from database
$activity_sql = "SELECT activity, created_at FROM academic_registrar_activity_log WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) ORDER BY created_at DESC LIMIT 10";
$activity_result = $students_conn->query($activity_sql);
$recent_activities = $activity_result ? $activity_result->fetch_all(MYSQLI_ASSOC) : [];

// Enhanced functionality functions
function handleAddStudent() {
    global $students_conn, $staff_conn;
    
    $student_id = generateStudentId();
    $first_name = sanitizeInput($_POST['first_name']);
    $surname = sanitizeInput($_POST['surname']);
    $other_name = sanitizeInput($_POST['other_name'] ?? '');
    $date_of_birth = sanitizeInput($_POST['date_of_birth']);
    $gender = sanitizeInput($_POST['gender']);
    $nationality = sanitizeInput($_POST['nationality']);
    $address = sanitizeInput($_POST['address']);
    $phone = sanitizeInput($_POST['phone'] ?? '');
    $email = sanitizeInput($_POST['email']);
    $course = sanitizeInput($_POST['course']);
    $year_of_study = sanitizeInput($_POST['year_of_study']);
    $semester = sanitizeInput($_POST['semester']);
    $registration_date = date('Y-m-d');
    
    $sql = "INSERT INTO students (student_id, first_name, surname, other_name, date_of_birth, gender, nationality, address, phone, email, course, year_of_study, semester, registration_date, status, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $students_conn->prepare($sql);
    $stmt->bind_param("ssssssssssssss", $student_id, $first_name, $surname, $other_name, $date_of_birth, $gender, $nationality, $address, $phone, $email, $course, $year_of_study, $semester, $registration_date, 'Active', $_SESSION['user_id']);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "Student added successfully!";
        
        // Log activity
        $log_sql = "INSERT INTO academic_registrar_activity_log (activity, created_at, created_by) VALUES (?, NOW(), ?)";
        $log_stmt = $students_conn->prepare($log_sql);
        $log_stmt->bind_param("sis", "New student registered: $first_name $surname", $_SESSION['user_id']);
        $log_stmt->execute();
    } else {
        $_SESSION['error'] = "Failed to add student.";
    }
    
    header("Location: academic-registrar.php");
    exit();
}

function handleUpdateStudent() {
    global $students_conn;
    
    $student_id = $_POST['student_id'];
    $first_name = sanitizeInput($_POST['first_name']);
    $surname = sanitizeInput($_POST['surname']);
    $other_name = sanitizeInput($_POST['other_name'] ?? '');
    $date_of_birth = sanitizeInput($_POST['date_of_birth']);
    $gender = sanitizeInput($_POST['gender']);
    $nationality = sanitizeInput($_POST['nationality']);
    $address = sanitizeInput($_POST['address']);
    $phone = sanitizeInput($_POST['phone'] ?? '');
    $email = sanitizeInput($_POST['email']);
    $course = sanitizeInput($_POST['course']);
    $year_of_study = sanitizeInput($_POST['year_of_study']);
    $semester = sanitizeInput($_POST['semester']);
    
    $sql = "UPDATE students SET first_name = ?, surname = ?, other_name = ?, date_of_birth = ?, gender = ?, nationality = ?, address = ?, phone = ?, email = ?, course = ?, year_of_study = ?, semester = ?, updated_by = NOW() WHERE student_id = ?";
    
    $stmt = $students_conn->prepare($sql);
    $stmt->bind_param("ssssssssssss", $first_name, $surname, $other_name, $date_of_birth, $gender, $nationality, $address, $phone, $email, $course, $year_of_study, $semester, $_SESSION['user_id'], $student_id);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "Student updated successfully!";
        
        // Log activity
        $log_sql = "INSERT INTO academic_registrar_activity_log (activity, created_at, created_by) VALUES (?, NOW(), ?)";
        $log_stmt = $students_conn->prepare($log_sql);
        $log_stmt->bind_param("sis", "Student updated: $first_name $surname", $_SESSION['user_id']);
        $log_stmt->execute();
    } else {
        $_SESSION['error'] = "Failed to update student.";
    }
    
    header("Location: academic-registrar.php");
    exit();
}

function handleDeleteStudent() {
    global $students_conn;
    
    $student_id = $_POST['student_id'];
    
    $sql = "DELETE FROM students WHERE student_id = ?";
    $stmt = $students_conn->prepare($sql);
    $stmt->bind_param("s", $student_id);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "Student deleted successfully!";
        
        // Log activity
        $log_sql = "INSERT INTO academic_registrar_activity_log (activity, created_at, created_by) VALUES (?, NOW(), ?)";
        $log_stmt = $students_conn->prepare($log_sql);
        $log_stmt->bind_param("sis", "Student deleted: $student_id", $_SESSION['user_id']);
        $log_stmt->execute();
    } else {
        $_SESSION['error'] = "Failed to delete student.";
    }
    
    header("Location: academic-registrar.php");
    exit();
}

function handleGenerateTranscript() {
    global $students_conn;
    
    $student_id = $_POST['student_id'];
    
    // Get student details
    $student_sql = "SELECT * FROM students WHERE student_id = ?";
    $stmt = $students_conn->prepare($student_sql);
    $stmt->bind_param("s", $student_id);
    $stmt->execute();
    $student = $stmt->get_result()->fetch_assoc();
    
    if ($student) {
        // Generate transcript
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="transcript_' . $student_id . '.pdf"');
        
        // Include transcript template
        include '../templates/professional_transcript_template.php';
        
        // Log activity
        $log_sql = "INSERT INTO academic_registrar_activity_log (activity, created_at, created_by) VALUES (?, NOW(), ?)";
        $log_stmt = $students_conn->prepare($log_sql);
        $log_stmt->bind_param("sis", "Transcript generated for: $student_id", $_SESSION['user_id']);
        $log_stmt->execute();
    }
    
    exit();
}

function handleGenerateResults() {
    global $students_conn;
    
    $student_id = $_POST['student_id'];
    $semester = $_POST['semester'] ?? 'All';
    
    // Get student details and results
    $student_sql = "SELECT * FROM students WHERE student_id = ?";
    $stmt = $students_conn->prepare($student_sql);
    $stmt->bind_param("s", $student_id);
    $stmt->execute();
    $student = $stmt->get_result()->fetch_assoc();
    
    if ($student) {
        // Generate results
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="results_' . $student_id . '_' . $semester . '.pdf"');
        
        // Include results template
        include '../templates/professional_results_template.php';
        
        // Log activity
        $log_sql = "INSERT INTO academic_registrar_activity_log (activity, created_at, created_by) VALUES (?, NOW(), ?)";
        $log_stmt = $students_conn->prepare($log_sql);
        $log_stmt->bind_param("sis", "Results generated for: $student_id", $_SESSION['user_id']);
        $log_stmt->execute();
    }
    
    exit();
}

function handleBulkImport() {
    global $students_conn;
    
    if (isset($_FILES['excel_file']) && $_FILES['excel_file']['error'] === UPLOAD_ERR_OK) {
        $file_name = $_FILES['excel_file']['name'];
        $file_tmp = $_FILES['excel_file']['tmp_name'];
        $file_size = $_FILES['excel_file']['size'];
        
        // Validate file type
        $allowed_types = ['application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'];
        if (in_array($_FILES['excel_file']['type'], $allowed_types)) {
            
            // Process Excel file
            require '../includes/excel_reader.php';
            $data = readExcelFile($file_tmp);
            
            $imported_count = 0;
            $error_count = 0;
            
            foreach ($data as $row) {
                if ($imported_count > 0) { // Skip header row
                    $student_id = generateStudentId();
                    $first_name = sanitizeInput($row['first_name'] ?? '');
                    $surname = sanitizeInput($row['surname'] ?? '');
                    $other_name = sanitizeInput($row['other_name'] ?? '');
                    $date_of_birth = sanitizeInput($row['date_of_birth'] ?? '');
                    $gender = sanitizeInput($row['gender'] ?? '');
                    $nationality = sanitizeInput($row['nationality'] ?? '');
                    $address = sanitizeInput($row['address'] ?? '');
                    $phone = sanitizeInput($row['phone'] ?? '');
                    $email = sanitizeInput($row['email'] ?? '');
                    $course = sanitizeInput($row['course'] ?? '');
                    $year_of_study = sanitizeInput($row['year_of_study'] ?? '');
                    $semester = sanitizeInput($row['semester'] ?? '');
                    
                    if (!empty($first_name) && !empty($surname)) {
                        $sql = "INSERT INTO students (student_id, first_name, surname, other_name, date_of_birth, gender, nationality, address, phone, email, course, year_of_study, semester, registration_date, status, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                        $stmt = $students_conn->prepare($sql);
                        $stmt->bind_param("ssssssssssssss", $student_id, $first_name, $surname, $other_name, $date_of_birth, $gender, $nationality, $address, $phone, $email, $course, $year_of_study, $semester, date('Y-m-d'), 'Active', $_SESSION['user_id']);
                        
                        if ($stmt->execute()) {
                            $imported_count++;
                        } else {
                            $error_count++;
                        }
                    }
                }
            }
            
            $_SESSION['success'] = "Bulk import completed: $imported_count students imported, $error_count errors.";
            
            // Log activity
            $log_sql = "INSERT INTO academic_registrar_activity_log (activity, created_at, created_by) VALUES (?, NOW(), ?)";
            $log_stmt = $students_conn->prepare($log_sql);
            $log_stmt->bind_param("sis", "Bulk import: $imported_count students", $_SESSION['user_id']);
            $log_stmt->execute();
        } else {
            $_SESSION['error'] = "Invalid file type. Please upload Excel file.";
        }
    } else {
        $_SESSION['error'] = "File upload error. Please try again.";
    }
    
    header("Location: academic-registrar.php");
    exit();
}

function generateStudentId() {
    return 'STU' . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT) . date('Y');
}

function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

// Grading System Handler Functions
function handleEnterGrades() {
    global $students_conn, $staff_conn;
    
    $student_id = sanitizeInput($_POST['student_id']);
    $course_code = sanitizeInput($_POST['course_code']);
    $course_name = sanitizeInput($_POST['course_name']);
    $continuous_assessment = floatval($_POST['continuous_assessment']);
    $final_exam = floatval($_POST['final_exam']);
    $semester = sanitizeInput($_POST['semester']);
    $academic_year = sanitizeInput($_POST['academic_year']);
    
    // Calculate total marks
    $total_marks = $continuous_assessment + $final_exam;
    
    // Get grade based on total marks
    $grade = calculateGrade($total_marks);
    
    // Generate workflow number
    $workflow_number = 'GRW-' . date('Ymd') . '-' . str_pad(mt_rand(1, 999), 3, '0', STR_PAD_LEFT);
    
    // Insert examination record
    $exam_sql = "INSERT INTO examination_records (exam_number, student_id, course_code, course_name, exam_type, marks_obtained, total_marks, grade, continuous_assessment_marks, final_exam_marks, lecturer_id, grade_status) VALUES (?, ?, ?, ?, 'Final', ?, 100, ?, ?, ?, ?, 'Submitted')";
    $exam_stmt = $students_conn->prepare($exam_sql);
    $exam_number = 'EXAM-' . date('Ymd') . '-' . str_pad(mt_rand(1, 999), 3, '0', STR_PAD_LEFT);
    $exam_stmt->bind_param("sisdddis", $exam_number, $student_id, $course_code, $course_name, $total_marks, $grade, $continuous_assessment, $final_exam, $_SESSION['user_id']);
    
    if ($exam_stmt->execute()) {
        $exam_record_id = $students_conn->insert_id;
        
        // Create workflow entry
        $workflow_sql = "INSERT INTO grading_approval_workflow (workflow_number, examination_record_id, current_stage, submitted_by) VALUES (?, ?, 'Lecturer Entry', ?)";
        $workflow_stmt = $staff_conn->prepare($workflow_sql);
        $workflow_stmt->bind_param("sis", $workflow_number, $exam_record_id, $_SESSION['user_id']);
        
        if ($workflow_stmt->execute()) {
            $_SESSION['success'] = "Grades entered successfully and submitted for approval!";
        } else {
            $_SESSION['error'] = "Grades entered but workflow creation failed.";
        }
    } else {
        $_SESSION['error'] = "Failed to enter grades.";
    }
    
    header("Location: academic-registrar.php#grading");
    exit();
}

function handleApproveGrade() {
    global $staff_conn;
    
    $workflow_number = sanitizeInput($_POST['workflow_number']);
    $comments = sanitizeInput($_POST['comments'] ?? '');
    
    // Get current workflow stage
    $workflow_sql = "SELECT * FROM grading_approval_workflow WHERE workflow_number = ?";
    $stmt = $staff_conn->prepare($workflow_sql);
    $stmt->bind_param("s", $workflow_number);
    $stmt->execute();
    $workflow = $stmt->get_result()->fetch_assoc();
    
    if ($workflow) {
        $next_stage = '';
        $update_field = '';
        $update_value = '';
        $notification_type = '';
        $notification_message = '';
        
        // Determine next stage based on current stage
        switch ($workflow['current_stage']) {
            case 'Lecturer Entry':
                $next_stage = 'HOD Review';
                $notification_type = 'HOD Review Required';
                $notification_message = 'Grade submitted for HOD review';
                break;
            case 'HOD Review':
                $next_stage = 'Registrar Approval';
                $update_field = 'hod_status';
                $update_value = 'Approved';
                $notification_type = 'Registrar Approval Required';
                $notification_message = 'Grade approved by HOD, awaiting Registrar approval';
                break;
            case 'Registrar Approval':
                $next_stage = 'Principal Final Approval';
                $update_field = 'registrar_status';
                $update_value = 'Approved';
                $notification_type = 'Principal Approval Required';
                $notification_message = 'Grade approved by Registrar, awaiting Principal final approval';
                break;
            case 'Principal Final Approval':
                $next_stage = 'Published';
                $update_field = 'principal_status';
                $update_value = 'Approved';
                $notification_type = 'Grade Published';
                $notification_message = 'Grade has been published';
                break;
        }
        
        // Update workflow
        $update_sql = "UPDATE grading_approval_workflow SET current_stage = ?, ";
        if ($update_field) {
            $update_sql .= "$update_field = 'Approved', ";
        }
        $update_sql .= "updated_at = NOW() WHERE workflow_number = ?";
        
        $update_stmt = $staff_conn->prepare($update_sql);
        $update_stmt->bind_param("s", $next_stage);
        
        if ($update_stmt->execute()) {
            // Log grade change
            logGradeChange($workflow_number, $workflow['examination_record_id'], $_SESSION['user_id'], null, null, null, null, null, null, "Grade approved and moved to $next_stage");
            
            // Send notification to next approver (simplified - would need proper recipient lookup)
            if ($notification_type) {
                sendGradingNotification($workflow_number, $_SESSION['user_id'], $_SESSION['user_id'], $notification_type, $notification_message);
            }
            
            $_SESSION['success'] = "Grade approved and moved to $next_stage!";
        } else {
            $_SESSION['error'] = "Failed to approve grade.";
        }
    } else {
        $_SESSION['error'] = "Workflow not found.";
    }
    
    header("Location: academic-registrar.php#grade-approval");
    exit();
}

function handleRejectGrade() {
    global $staff_conn;
    
    $workflow_number = sanitizeInput($_POST['workflow_number']);
    $rejection_reason = sanitizeInput($_POST['rejection_reason']);
    
    // Get workflow details
    $workflow_sql = "SELECT * FROM grading_approval_workflow WHERE workflow_number = ?";
    $stmt = $staff_conn->prepare($workflow_sql);
    $stmt->bind_param("s", $workflow_number);
    $stmt->execute();
    $workflow = $stmt->get_result()->fetch_assoc();
    
    // Update workflow to rejected
    $update_sql = "UPDATE grading_approval_workflow SET current_stage = 'Rejected', rejection_reason = ?, updated_at = NOW() WHERE workflow_number = ?";
    $stmt = $staff_conn->prepare($update_sql);
    $stmt->bind_param("ss", $rejection_reason, $workflow_number);
    
    if ($stmt->execute()) {
        // Log grade change
        if ($workflow) {
            logGradeChange($workflow_number, $workflow['examination_record_id'], $_SESSION['user_id'], null, null, null, null, null, null, "Grade rejected: " . $rejection_reason);
            
            // Send notification
            sendGradingNotification($workflow_number, $_SESSION['user_id'], $_SESSION['user_id'], 'Grade Rejected', 'Grade has been rejected: ' . $rejection_reason);
        }
        
        $_SESSION['success'] = "Grade rejected successfully.";
    } else {
        $_SESSION['error'] = "Failed to reject grade.";
    }
    
    header("Location: academic-registrar.php#grade-approval");
    exit();
}

function handlePublishResults() {
    global $staff_conn;
    
    $publication_id = 'PUB-' . date('Ymd') . '-' . str_pad(mt_rand(1, 999), 3, '0', STR_PAD_LEFT);
    $academic_year = sanitizeInput($_POST['academic_year']);
    $semester = sanitizeInput($_POST['semester']);
    $program = sanitizeInput($_POST['program'] ?? '');
    $course_code = sanitizeInput($_POST['course_code'] ?? '');
    
    // Insert publication record
    $sql = "INSERT INTO result_publication (publication_id, academic_year, semester, program, course_code, status, published_by, publication_date) VALUES (?, ?, ?, ?, ?, 'Published', ?, NOW())";
    $stmt = $staff_conn->prepare($sql);
    $stmt->bind_param("sssssi", $publication_id, $academic_year, $semester, $program, $course_code, $_SESSION['user_id']);
    
    if ($stmt->execute()) {
        // Update all relevant workflows to published
        $update_sql = "UPDATE grading_approval_workflow gaw
                      JOIN examination_records er ON gaw.examination_record_id = er.id
                      SET gaw.current_stage = 'Published', gaw.published_at = NOW()
                      WHERE gaw.current_stage = 'Principal Final Approval'";
        $staff_conn->query($update_sql);
        
        $_SESSION['success'] = "Results published successfully!";
    } else {
        $_SESSION['error'] = "Failed to publish results.";
    }
    
    header("Location: academic-registrar.php#result-publication");
    exit();
}

function handleAddCalendar() {
    global $staff_conn;
    
    $calendar_id = 'CAL-' . sanitizeInput($_POST['academic_year']) . '-' . str_pad(mt_rand(1, 999), 3, '0', STR_PAD_LEFT);
    $academic_year = sanitizeInput($_POST['academic_year']);
    $semester = sanitizeInput($_POST['semester']);
    $semester_start = sanitizeInput($_POST['semester_start']);
    $semester_end = sanitizeInput($_POST['semester_end']);
    $exam_start = sanitizeInput($_POST['exam_start']);
    $exam_end = sanitizeInput($_POST['exam_end']);
    $result_publication = sanitizeInput($_POST['result_publication'] ?? '');
    $registration_deadline = sanitizeInput($_POST['registration_deadline']);
    $add_drop_deadline = sanitizeInput($_POST['add_drop_deadline'] ?? '');
    $withdrawal_deadline = sanitizeInput($_POST['withdrawal_deadline'] ?? '');
    $notes = sanitizeInput($_POST['notes'] ?? '');
    
    $sql = "INSERT INTO academic_calendar (calendar_id, academic_year, semester, semester_start_date, semester_end_date, exam_start_date, exam_end_date, result_publication_date, registration_deadline, add_drop_deadline, withdrawal_deadline, notes, status, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Upcoming', ?)";
    $stmt = $staff_conn->prepare($sql);
    $stmt->bind_param("ssssssssssssi", $calendar_id, $academic_year, $semester, $semester_start, $semester_end, $exam_start, $exam_end, $result_publication, $registration_deadline, $add_drop_deadline, $withdrawal_deadline, $notes, $_SESSION['user_id']);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "Academic calendar added successfully!";
    } else {
        $_SESSION['error'] = "Failed to add academic calendar.";
    }
    
    header("Location: academic-registrar.php#academic-calendar");
    exit();
}

function calculateGrade($total_marks) {
    if ($total_marks >= 80) return 'A';
    if ($total_marks >= 70) return 'B';
    if ($total_marks >= 60) return 'C';
    if ($total_marks >= 50) return 'D';
    return 'F';
}

function logGradeChange($workflow_number, $examination_record_id, $changed_by, $previous_grade, $new_grade, $previous_ca, $new_ca, $previous_exam, $new_exam, $reason) {
    global $staff_conn;
    
    $sql = "INSERT INTO grade_change_history (workflow_number, examination_record_id, changed_by, previous_grade, new_grade, previous_ca_marks, new_ca_marks, previous_exam_marks, new_exam_marks, change_reason) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $staff_conn->prepare($sql);
    $stmt->bind_param("sisssdddss", $workflow_number, $examination_record_id, $changed_by, $previous_grade, $new_grade, $previous_ca, $new_ca, $previous_exam, $new_exam, $reason);
    return $stmt->execute();
}

function sendGradingNotification($workflow_number, $recipient_id, $sender_id, $notification_type, $message) {
    global $staff_conn;
    
    $notification_id = 'NOT-' . date('Ymd') . '-' . str_pad(mt_rand(1, 999), 3, '0', STR_PAD_LEFT);
    
    $sql = "INSERT INTO grading_notifications (notification_id, workflow_number, recipient_id, sender_id, notification_type, message) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $staff_conn->prepare($sql);
    $stmt->bind_param("ssisss", $notification_id, $workflow_number, $recipient_id, $sender_id, $notification_type, $message);
    return $stmt->execute();
}

// Get filtered students
function getFilteredStudents($search_term, $filter_course, $filter_year, $filter_semester, $filter_status) {
    global $students_conn;
    
    $sql = "SELECT * FROM students WHERE 1=1";
    $params = [];
    $types = "";
    
    if (!empty($search_term)) {
        $sql .= " AND (first_name LIKE ? OR surname LIKE ? OR student_id LIKE ? OR email LIKE ?)";
        $params = array_merge($params, ["%$search_term%", "%$search_term%", "%$search_term%", "%$search_term%"]);
        $types .= "ssss";
    }
    
    if (!empty($filter_course)) {
        $sql .= " AND course = ?";
        $params = array_merge($params, [$filter_course]);
        $types .= "s";
    }
    
    if (!empty($filter_year)) {
        $sql .= " AND year_of_study = ?";
        $params = array_merge($params, [$filter_year]);
        $types .= "s";
    }
    
    if (!empty($filter_semester)) {
        $sql .= " AND semester = ?";
        $params = array_merge($params, [$filter_semester]);
        $types .= "s";
    }
    
    if (!empty($filter_status)) {
        $sql .= " AND status = ?";
        $params = array_merge($params, [$filter_status]);
        $types .= "s";
    }
    
    $sql .= " ORDER BY surname, first_name";
    
    $stmt = $students_conn->prepare($sql);
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    } else {
        $stmt->execute();
    }
    
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Get courses for filter
function getCourses() {
    global $students_conn;
    
    $sql = "SELECT DISTINCT course FROM students ORDER BY course";
    $result = $students_conn->query($sql);
    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

// Get years for filter
function getYears() {
    global $students_conn;
    
    $sql = "SELECT DISTINCT year_of_study FROM students ORDER BY year_of_study DESC";
    $result = $students_conn->query($sql);
    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

// Get semesters for filter
function getSemesters() {
    global $students_conn;
    
    $sql = "SELECT DISTINCT semester FROM students ORDER BY semester";
    $result = $students_conn->query($sql);
    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Academic Registrar Dashboard - ISNM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/enhanced_dashboard.css" rel="stylesheet">
    <style>
        /* Enhanced Professional Styling */
        .dashboard-container {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .sidebar {
            background: linear-gradient(180deg, #2c3e50 0%, #3498db 100%);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            transform: translateX(0);
            transition: all 0.3s ease;
        }
        
        .sidebar:hover {
            transform: translateX(-5px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.4);
        }
        
        .stat-card {
            background: linear-gradient(145deg, #ffffff 0%, #f8f9fa 100%);
            border: none;
            border-radius: 15px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            transform: translateY(0);
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.15);
        }
        
        .btn-enhanced {
            background: linear-gradient(45deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 50px;
            padding: 12px 30px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }
        
        .btn-enhanced:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
            background: linear-gradient(45deg, #764ba2 0%, #667eea 100%);
        }
        
        .search-container {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
        }
        
        .activity-feed {
            max-height: 400px;
            overflow-y: auto;
            background: rgba(255, 255, 255, 0.9);
            border-radius: 15px;
            padding: 20px;
        }
        
        .activity-item {
            background: linear-gradient(90deg, #f8f9fa 0%, #e9ecef 100%);
            margin-bottom: 15px;
            padding: 15px;
            border-radius: 10px;
            border-left: 4px solid #667eea;
            transition: all 0.3s ease;
        }
        
        .activity-item:hover {
            transform: translateX(5px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .notification-badge {
            background: linear-gradient(45deg, #ff6b6b 0%, #ee5a24 100%);
            color: white;
            border-radius: 50%;
            padding: 2px 8px;
            font-size: 0.8rem;
            font-weight: bold;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
        
        .advanced-stats {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 25px;
            border-radius: 20px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }
        
        .chart-container {
            background: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            height: 300px;
            position: relative;
            overflow: hidden;
        }
        
        .chart-placeholder {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            color: #6c757d;
            font-size: 1.2rem;
            font-weight: 600;
        }
        
        /* Grading System Styles */
        .grading-actions, .approval-stats, .calendar-actions, .publication-actions {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        
        .grading-overview, .approval-stats, .calendar-overview, .publication-overview {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }
        
        .workflow-visualization {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 15px;
            padding: 30px;
            margin-top: 20px;
        }
        
        .workflow-steps {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .workflow-step {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            color: rgba(255, 255, 255, 0.6);
            transition: all 0.3s ease;
        }
        
        .workflow-step.completed {
            color: #4ade80;
        }
        
        .workflow-step.active {
            color: #fbbf24;
            animation: pulse 2s infinite;
        }
        
        .step-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 10px;
            border: 3px solid rgba(255, 255, 255, 0.3);
        }
        
        .workflow-step.completed .step-icon {
            background: rgba(74, 222, 128, 0.3);
            border-color: #4ade80;
        }
        
        .workflow-step.active .step-icon {
            background: rgba(251, 191, 36, 0.3);
            border-color: #fbbf24;
        }
        
        .step-label {
            font-size: 0.85rem;
            font-weight: 600;
        }
        
        .workflow-arrow {
            color: rgba(255, 255, 255, 0.4);
            font-size: 1.2rem;
        }
        
        .calendar-card {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 12px;
            padding: 20px;
            border-left: 4px solid #667eea;
        }
        
        .calendar-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .calendar-details {
            display: grid;
            gap: 10px;
        }
        
        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid rgba(0, 0, 0, 0.1);
        }
        
        .detail-row:last-child {
            border-bottom: none;
        }
        
        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .status-badge.pending {
            background: #fef3c7;
            color: #92400e;
        }
        
        .status-badge.in-progress {
            background: #dbeafe;
            color: #1e40af;
        }
        
        .status-badge.completed {
            background: #d1fae5;
            color: #065f46;
        }
        
        .status-badge.current {
            background: #4ade80;
            color: white;
        }
        
        .status-badge.published {
            background: #4ade80;
            color: white;
        }
        
        .status-badge.draft {
            background: #e5e7eb;
            color: #374151;
        }
        
        .status-badge.hod-review {
            background: #fef3c7;
            color: #92400e;
        }
        
        .status-badge.registrar-approval {
            background: #dbeafe;
            color: #1e40af;
        }
        
        .status-badge.principal-final-approval {
            background: #fce7f3;
            color: #9d174d;
        }
        
        /* Mobile Responsive Styles */
        @media (max-width: 768px) {
            .grading-actions, .approval-stats, .calendar-actions, .publication-actions {
                flex-direction: column;
            }
            
            .grading-actions button, .approval-stats button, .calendar-actions button, .publication-actions button {
                width: 100%;
            }
            
            .workflow-steps {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .workflow-arrow {
                transform: rotate(90deg);
                margin: 5px 0;
            }
            
            .workflow-step {
                width: 100%;
                flex-direction: row;
                justify-content: flex-start;
                gap: 15px;
                margin-bottom: 10px;
            }
            
            .step-icon {
                margin-bottom: 0;
            }
            
            .table-responsive {
                font-size: 0.85rem;
            }
            
            .stat-card {
                padding: 15px;
            }
        }
        
        /* Print Styles */
        @media print {
            .sidebar, .dashboard-header, .activities-section, .btn {
                display: none !important;
            }
            
            .main-content {
                margin-left: 0 !important;
                padding: 0 !important;
            }
            
            .content-section {
                page-break-inside: avoid;
                margin-bottom: 20px;
            }
            
            .table-responsive {
                overflow: visible;
            }
            
            body {
                background: white !important;
                font-size: 12pt;
            }
            
            .transcript-actions, .publication-actions, .grading-actions, .calendar-actions {
                display: none !important;
            }
        }
        
        .transcript-actions, .publication-actions {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        
        .transcript-requests {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <img src="../images/school-logo.png" alt="ISNM Logo" class="sidebar-logo">
                <h4>Academic Registrar Dashboard</h4>
                <p><?php echo ($user['first_name'] ?? 'User') . ' ' . ($user['surname'] ?? $user['last_name'] ?? ''); ?></p>
            </div>
            
            <nav class="sidebar-nav">
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link active" href="#overview">
                            <i class="fas fa-tachometer-alt"></i> Registration Overview
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#applications">
                            <i class="fas fa-file-alt"></i> Applications
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#registration">
                            <i class="fas fa-user-plus"></i> Student Registration
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#records">
                            <i class="fas fa-folder"></i> Student Records
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#transcripts">
                            <i class="fas fa-graduation-cap"></i> Transcripts
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#certificates">
                            <i class="fas fa-certificate"></i> Certificates
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#reports">
                            <i class="fas fa-chart-bar"></i> Reports
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#grading">
                            <i class="fas fa-graduation-cap"></i> Grading Management
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#grade-approval">
                            <i class="fas fa-check-double"></i> Grade Approval
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#academic-calendar">
                            <i class="fas fa-calendar-alt"></i> Academic Calendar
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#result-publication">
                            <i class="fas fa-bullhorn"></i> Result Publication
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#transcripts">
                            <i class="fas fa-file-alt"></i> Transcripts
                        </a>
                    </li>
                </ul>
            </nav>
            
            <div class="sidebar-footer">
                <a href="../logout.php" class="btn btn-danger btn-sm">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Header -->
            <header class="dashboard-header">
                <div class="header-left">
                    <h1>Academic Registrar Dashboard</h1>
                    <p>Student Records & Registration Management</p>
                </div>
                <div class="header-right">
                    <div class="date-time">
                        <i class="fas fa-calendar"></i>
                        <span id="currentDate"></span>
                    </div>
                    <div class="user-menu">
                        <img src="../images/default-avatar.png" alt="User" class="user-avatar">
                        <div class="user-dropdown">
                            <span><?php echo $user['first_name']; ?></span>
                            <i class="fas fa-chevron-down"></i>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Dashboard Content -->
            <div class="dashboard-content">
                <!-- Registration Overview -->
                <section id="overview" class="content-section">
                    <h2>Registration Overview</h2>
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-file-alt"></i>
                            </div>
                            <div class="stat-content">
                                <h3><?php echo $total_applications; ?></h3>
                                <p>Pending Applications</p>
                            </div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-users"></i>
                            </div>
                            <div class="stat-content">
                                <h3><?php echo $registered_students; ?></h3>
                                <p>Registered Students</p>
                            </div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-user-check"></i>
                            </div>
                            <div class="stat-content">
                                <h3><?php echo $pending_registrations; ?></h3>
                                <p>Pending Registrations</p>
                            </div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-graduation-cap"></i>
                            </div>
                            <div class="stat-content">
                                <h3><?php echo $transcripts_issued; ?></h3>
                                <p>Transcripts Issued</p>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-calendar-check"></i>
                            </div>
                            <div class="stat-content">
                                <h3><?php echo $new_admissions; ?></h3>
                                <p>New Admissions (30d)</p>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-book-open"></i>
                            </div>
                            <div class="stat-content">
                                <h3><?php echo $course_registrations; ?></h3>
                                <p>Course Registrations</p>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-bell"></i>
                            </div>
                            <div class="stat-content">
                                <h3><?php echo $notifications_announcements; ?></h3>
                                <p>Unread Notifications</p>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-calendar-alt"></i>
                            </div>
                            <div class="stat-content">
                                <h3><?php echo $calendar_reminders; ?></h3>
                                <p>Calendar Reminders</p>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Recent Student Profiles -->
                <section id="student-profiles" class="content-section">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2>Recent Student Profiles</h2>
                        <div>
                            <a href="../import_students_excel.php" class="btn btn-info">
                                <i class="fas fa-file-excel"></i> Import All Students
                            </a>
                            <button class="btn btn-success" onclick="openModal('addStudent')">
                                <i class="fas fa-user-plus"></i> Add New Student
                            </button>
                        </div>
                    </div>

                    <div class="search-container mb-4">
                        <form method="GET" action="academic-registrar.php" class="row g-2 align-items-center">
                            <div class="col-md-9">
                                <input type="text" name="search_student" class="form-control" placeholder="Search students by name, registration number, or course..." value="<?php echo htmlspecialchars($search_term); ?>">
                            </div>
                            <div class="col-md-3">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-search"></i> Search
                                </button>
                            </div>
                        </form>
                    </div>

                    <div class="row mt-4">
                        <?php if (empty($recent_students)): ?>
                            <div class="col-12">
                                <div class="alert alert-secondary">No recent student profiles available.</div>
                            </div>
                        <?php endif; ?>
                        <?php foreach ($recent_students as $student): ?>
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card stat-card h-100">
                                    <div class="card-body">
                                        <h5 class="card-title mb-2"><?php echo htmlspecialchars($student['full_name'] ?? 'Unknown Student'); ?></h5>
                                        <p class="text-muted mb-1"><?php echo htmlspecialchars($student['registration_number'] ?? 'N/A'); ?></p>
                                        <p class="mb-1"><strong>Course:</strong> <?php echo htmlspecialchars($student['course'] ?? 'N/A'); ?></p>
                                        <p class="mb-2"><strong>Status:</strong> <?php echo htmlspecialchars($student['status'] ?? 'N/A'); ?></p>
                                        <div class="d-flex justify-content-between mt-3">
                                            <button class="btn btn-sm btn-outline-primary">View</button>
                                            <button class="btn btn-sm btn-outline-success">Edit</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </section>

                <!-- Applications -->
                <section id="applications" class="content-section">
                    <h2>Application Management</h2>
                    <div class="application-actions">
                        <button class="btn btn-primary" onclick="openModal('reviewApplication')">
                            <i class="fas fa-eye"></i> Review Applications
                        </button>
                        <button class="btn btn-success" onclick="openModal('approveApplication')">
                            <i class="fas fa-check"></i> Approve Applications
                        </button>
                        <button class="btn btn-info" onclick="openModal('rejectApplication')">
                            <i class="fas fa-times"></i> Reject Applications
                        </button>
                        <button class="btn btn-warning" onclick="openModal('interviewSchedule')">
                            <i class="fas fa-calendar"></i> Schedule Interviews
                        </button>
                    </div>
                    
                    <div class="applications-table">
                        <h3>Recent Applications</h3>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Application ID</th>
                                        <th>Name</th>
                                        <th>Program</th>
                                        <th>Applied Date</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>APP-2026-001</td>
                                        <td>John Doe</td>
                                        <td>Certificate Nursing</td>
                                        <td>Apr 20, 2026</td>
                                        <td><span class="status-badge pending">Pending Review</span></td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary">View</button>
                                            <button class="btn btn-sm btn-outline-success">Approve</button>
                                            <button class="btn btn-sm btn-outline-danger">Reject</button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>APP-2026-002</td>
                                        <td>Jane Smith</td>
                                        <td>Certificate Midwifery</td>
                                        <td>Apr 19, 2026</td>
                                        <td><span class="status-badge in-progress">Under Review</span></td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary">View</button>
                                            <button class="btn btn-sm btn-outline-success">Approve</button>
                                            <button class="btn btn-sm btn-outline-danger">Reject</button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>APP-2026-003</td>
                                        <td>Michael Johnson</td>
                                        <td>Diploma Nursing</td>
                                        <td>Apr 18, 2026</td>
                                        <td><span class="status-badge approved">Approved</span></td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary">View</button>
                                            <button class="btn btn-sm btn-outline-info">Register</button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </section>

                <!-- Student Registration -->
                <section id="registration" class="content-section">
                    <h2>Student Registration</h2>
                    <div class="registration-actions">
                        <button class="btn btn-primary" onclick="openModal('newRegistration')">
                            <i class="fas fa-user-plus"></i> New Registration
                        </button>
                        <button class="btn btn-success" onclick="openModal('bulkRegistration')">
                            <i class="fas fa-users"></i> Bulk Registration
                        </button>
                        <button class="btn btn-info" onclick="openModal('registrationReport')">
                            <i class="fas fa-chart-bar"></i> Registration Report
                        </button>
                        <button class="btn btn-warning" onclick="openModal('registrationAudit')">
                            <i class="fas fa-audit"></i> Registration Audit
                        </button>
                    </div>
                    
                    <div class="registration-overview">
                        <h3>Registration Statistics by Program</h3>
                        <div class="registration-stats">
                            <div class="stat-card">
                                <h4>Certificate Nursing</h4>
                                <div class="stat-number">120</div>
                                <div class="stat-detail">Registered Students</div>
                            </div>
                            <div class="stat-card">
                                <h4>Certificate Midwifery</h4>
                                <div class="stat-number">95</div>
                                <div class="stat-detail">Registered Students</div>
                            </div>
                            <div class="stat-card">
                                <h4>Diploma Nursing</h4>
                                <div class="stat-number">60</div>
                                <div class="stat-detail">Registered Students</div>
                            </div>
                            <div class="stat-card">
                                <h4>Diploma Midwifery</h4>
                                <div class="stat-number">40</div>
                                <div class="stat-detail">Registered Students</div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Student Records -->
                <section id="records" class="content-section">
                    <h2>Student Records Management</h2>
                    <div class="records-actions">
                        <button class="btn btn-primary" onclick="openModal('searchStudent')">
                            <i class="fas fa-search"></i> Search Student
                        </button>
                        <button class="btn btn-success" onclick="openModal('updateRecord')">
                            <i class="fas fa-edit"></i> Update Record
                        </button>
                        <button class="btn btn-info" onclick="openModal('transferStudent')">
                            <i class="fas fa-exchange-alt"></i> Transfer Student
                        </button>
                        <button class="btn btn-warning btn-enhanced" onclick="openModal('deactivateStudent')">
                            <i class="fas fa-user-times"></i> Deactivate Student
                        </button>
                        <a href="../import_students_excel.php" class="btn btn-info btn-enhanced">
                            <i class="fas fa-file-excel"></i> Bulk Import
                        </a>
                        <button class="btn btn-success btn-enhanced" onclick="openModal('generateReports')">
                            <i class="fas fa-chart-bar"></i> Generate Reports
                        </button>
                    </div>
                    
                    <div class="records-search">
                        <h3>Quick Student Search</h3>
                        <div class="search-form">
                            <div class="input-group">
                                <input type="text" class="form-control" placeholder="Enter Student ID, Name, or Email">
                                <button class="btn btn-primary" type="button">
                                    <i class="fas fa-search"></i> Search
                                </button>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Transcripts -->
                <section id="transcripts" class="content-section">
                    <h2>Academic Transcripts</h2>
                    <div class="transcript-actions">
                        <button class="btn btn-primary" onclick="openModal('generateTranscript')">
                            <i class="fas fa-file-alt"></i> Generate Transcript
                        </button>
                        <button class="btn btn-success" onclick="openModal('verifyTranscript')">
                            <i class="fas fa-check-circle"></i> Verify Transcript
                        </button>
                        <button class="btn btn-info" onclick="openModal('transcriptTemplate')">
                            <i class="fas fa-file-code"></i> Transcript Template
                        </button>
                        <button class="btn btn-warning" onclick="openModal('transcriptLog')">
                            <i class="fas fa-list-alt"></i> Transcript Log
                        </button>
                    </div>
                    
                    <div class="transcript-overview">
                        <h3>Recent Transcript Requests</h3>
                        <div class="transcript-list">
                            <div class="transcript-item">
                                <div class="transcript-header">
                                    <h4>STU-2023-001 - John Doe</h4>
                                    <span class="status-badge completed">Completed</span>
                                </div>
                                <div class="transcript-details">
                                    <div class="detail">
                                        <span>Program:</span>
                                        <strong>Certificate Nursing</strong>
                                    </div>
                                    <div class="detail">
                                        <span>Requested:</span>
                                        <strong>Apr 15, 2026</strong>
                                    </div>
                                    <div class="detail">
                                        <span>Purpose:</span>
                                        <strong>Job Application</strong>
                                    </div>
                                </div>
                                <div class="transcript-actions">
                                    <button class="btn btn-sm btn-outline-primary">View</button>
                                    <button class="btn btn-sm btn-outline-success">Download</button>
                                    <button class="btn btn-sm btn-outline-info">Reprint</button>
                                </div>
                            </div>
                            
                            <div class="transcript-item">
                                <div class="transcript-header">
                                    <h4>STU-2023-045 - Jane Smith</h4>
                                    <span class="status-badge in-progress">Processing</span>
                                </div>
                                <div class="transcript-details">
                                    <div class="detail">
                                        <span>Program:</span>
                                        <strong>Certificate Midwifery</strong>
                                    </div>
                                    <div class="detail">
                                        <span>Requested:</span>
                                        <strong>Apr 18, 2026</strong>
                                    </div>
                                    <div class="detail">
                                        <span>Purpose:</span>
                                        <strong>Further Studies</strong>
                                    </div>
                                </div>
                                <div class="transcript-actions">
                                    <button class="btn btn-sm btn-outline-primary">View</button>
                                    <button class="btn btn-sm btn-outline-warning">Process</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Grading Management -->
                <section id="grading" class="content-section">
                    <h2>Grading Management</h2>
                    <div class="grading-actions">
                        <button class="btn btn-primary" onclick="openModal('enterGrades')">
                            <i class="fas fa-edit"></i> Enter Grades
                        </button>
                        <button class="btn btn-success" onclick="openModal('viewGrades')">
                            <i class="fas fa-list"></i> View All Grades
                        </button>
                        <button class="btn btn-info" onclick="openModal('gradeScale')">
                            <i class="fas fa-sliders-h"></i> Grade Scale Settings
                        </button>
                        <button class="btn btn-warning" onclick="openModal('gpaReport')">
                            <i class="fas fa-chart-line"></i> GPA Report
                        </button>
                    </div>
                    
                    <div class="grading-overview">
                        <h3>Pending Grade Approvals</h3>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Workflow #</th>
                                        <th>Course</th>
                                        <th>Student</th>
                                        <th>CA Marks</th>
                                        <th>Exam Marks</th>
                                        <th>Total</th>
                                        <th>Grade</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    // Get pending grades from database
                                    $pending_grades_sql = "SELECT gaw.*, er.course_code, er.course_name, s.student_number, s.first_name, s.last_name 
                                                          FROM grading_approval_workflow gaw
                                                          JOIN examination_records er ON gaw.examination_record_id = er.id
                                                          JOIN students s ON er.student_id = s.id
                                                          WHERE gaw.current_stage IN ('HOD Review', 'Registrar Approval', 'Principal Final Approval')
                                                          ORDER BY gaw.submitted_at DESC LIMIT 10";
                                    $pending_grades_result = $staff_conn->query($pending_grades_sql);
                                    $pending_grades = $pending_grades_result ? $pending_grades_result->fetch_all(MYSQLI_ASSOC) : [];
                                    
                                    foreach ($pending_grades as $grade):
                                    ?>
                                    <tr>
                                        <td><?php echo $grade['workflow_number']; ?></td>
                                        <td><?php echo $grade['course_code'] . ' - ' . $grade['course_name']; ?></td>
                                        <td><?php echo $grade['student_number'] . ' - ' . $grade['first_name'] . ' ' . $grade['last_name']; ?></td>
                                        <td><?php echo $grade['continuous_assessment_marks'] ?? 0; ?></td>
                                        <td><?php echo $grade['final_exam_marks'] ?? 0; ?></td>
                                        <td><?php echo $grade['total_marks_calculated'] ?? 0; ?></td>
                                        <td><?php echo $grade['grade'] ?? 'N/A'; ?></td>
                                        <td><span class="status-badge <?php echo strtolower(str_replace(' ', '-', $grade['current_stage'])); ?>"><?php echo $grade['current_stage']; ?></span></td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary" onclick="openModal('reviewGrade', '<?php echo $grade['workflow_number']; ?>')">Review</button>
                                            <button class="btn btn-sm btn-outline-success" onclick="approveGrade('<?php echo $grade['workflow_number']; ?>')">Approve</button>
                                            <button class="btn btn-sm btn-outline-danger" onclick="rejectGrade('<?php echo $grade['workflow_number']; ?>')">Reject</button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </section>

                <!-- Grade Approval -->
                <section id="grade-approval" class="content-section">
                    <h2>Grade Approval Workflow</h2>
                    <div class="approval-stats">
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div class="stat-content">
                                <h3><?php echo count(array_filter($pending_grades, fn($g) => $g['current_stage'] == 'HOD Review')); ?></h3>
                                <p>Pending HOD Review</p>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-user-check"></i>
                            </div>
                            <div class="stat-content">
                                <h3><?php echo count(array_filter($pending_grades, fn($g) => $g['current_stage'] == 'Registrar Approval')); ?></h3>
                                <p>Pending Registrar Approval</p>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-user-shield"></i>
                            </div>
                            <div class="stat-content">
                                <h3><?php echo count(array_filter($pending_grades, fn($g) => $g['current_stage'] == 'Principal Final Approval')); ?></h3>
                                <p>Pending Principal Approval</p>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <div class="stat-content">
                                <h3>
                                    <?php
                                    $published_sql = "SELECT COUNT(*) as count FROM grading_approval_workflow WHERE current_stage = 'Published'";
                                    $published_result = $staff_conn->query($published_sql);
                                    $published_count = $published_result ? $published_result->fetch_assoc()['count'] : 0;
                                    echo $published_count;
                                    ?>
                                </h3>
                                <p>Published Results</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="workflow-visualization">
                        <h3>Approval Workflow Status</h3>
                        <div class="workflow-steps">
                            <div class="workflow-step completed">
                                <div class="step-icon"><i class="fas fa-chalkboard-teacher"></i></div>
                                <div class="step-label">Lecturer Entry</div>
                            </div>
                            <div class="workflow-arrow"><i class="fas fa-arrow-right"></i></div>
                            <div class="workflow-step <?php echo count(array_filter($pending_grades, fn($g) => $g['current_stage'] == 'HOD Review')) > 0 ? 'active' : ''; ?>">
                                <div class="step-icon"><i class="fas fa-user-tie"></i></div>
                                <div class="step-label">HOD Review</div>
                            </div>
                            <div class="workflow-arrow"><i class="fas fa-arrow-right"></i></div>
                            <div class="workflow-step <?php echo count(array_filter($pending_grades, fn($g) => $g['current_stage'] == 'Registrar Approval')) > 0 ? 'active' : ''; ?>">
                                <div class="step-icon"><i class="fas fa-user-graduate"></i></div>
                                <div class="step-label">Registrar Approval</div>
                            </div>
                            <div class="workflow-arrow"><i class="fas fa-arrow-right"></i></div>
                            <div class="workflow-step <?php echo count(array_filter($pending_grades, fn($g) => $g['current_stage'] == 'Principal Final Approval')) > 0 ? 'active' : ''; ?>">
                                <div class="step-icon"><i class="fas fa-user-shield"></i></div>
                                <div class="step-label">Principal Final Approval</div>
                            </div>
                            <div class="workflow-arrow"><i class="fas fa-arrow-right"></i></div>
                            <div class="workflow-step <?php echo $published_count > 0 ? 'completed' : ''; ?>">
                                <div class="step-icon"><i class="fas fa-bullhorn"></i></div>
                                <div class="step-label">Published</div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Academic Calendar -->
                <section id="academic-calendar" class="content-section">
                    <h2>Academic Calendar Management</h2>
                    <div class="calendar-actions">
                        <button class="btn btn-primary" onclick="openModal('addCalendar')">
                            <i class="fas fa-plus"></i> Add Calendar Entry
                        </button>
                        <button class="btn btn-success" onclick="openModal('editCalendar')">
                            <i class="fas fa-edit"></i> Edit Calendar
                        </button>
                        <button class="btn btn-info" onclick="openModal('viewCalendar')">
                            <i class="fas fa-calendar"></i> View Full Calendar
                        </button>
                    </div>
                    
                    <div class="calendar-overview">
                        <h3>Current Academic Calendar</h3>
                        <?php
                        $calendar_sql = "SELECT * FROM academic_calendar WHERE status = 'Current' ORDER BY semester_start_date DESC LIMIT 1";
                        $calendar_result = $staff_conn->query($calendar_sql);
                        $current_calendar = $calendar_result ? $calendar_result->fetch_assoc() : null;
                        
                        if ($current_calendar):
                        ?>
                        <div class="calendar-card">
                            <div class="calendar-header">
                                <h4><?php echo $current_calendar['academic_year'] . ' - ' . $current_calendar['semester']; ?></h4>
                                <span class="status-badge current">Current</span>
                            </div>
                            <div class="calendar-details">
                                <div class="detail-row">
                                    <span>Semester Start:</span>
                                    <strong><?php echo date('M j, Y', strtotime($current_calendar['semester_start_date'])); ?></strong>
                                </div>
                                <div class="detail-row">
                                    <span>Semester End:</span>
                                    <strong><?php echo date('M j, Y', strtotime($current_calendar['semester_end_date'])); ?></strong>
                                </div>
                                <div class="detail-row">
                                    <span>Exam Period:</span>
                                    <strong><?php echo date('M j', strtotime($current_calendar['exam_start_date'])) . ' - ' . date('M j, Y', strtotime($current_calendar['exam_end_date'])); ?></strong>
                                </div>
                                <div class="detail-row">
                                    <span>Result Publication:</span>
                                    <strong><?php echo $current_calendar['result_publication_date'] ? date('M j, Y', strtotime($current_calendar['result_publication_date'])) : 'Not Set'; ?></strong>
                                </div>
                                <div class="detail-row">
                                    <span>Registration Deadline:</span>
                                    <strong><?php echo date('M j, Y', strtotime($current_calendar['registration_deadline'])); ?></strong>
                                </div>
                            </div>
                        </div>
                        <?php else: ?>
                        <p class="text-muted">No current academic calendar found.</p>
                        <?php endif; ?>
                    </div>
                </section>

                <!-- Result Publication -->
                <section id="result-publication" class="content-section">
                    <h2>Result Publication Control</h2>
                    <div class="publication-actions">
                        <button class="btn btn-primary" onclick="openModal('publishResults')">
                            <i class="fas fa-bullhorn"></i> Publish Results
                        </button>
                        <button class="btn btn-warning" onclick="openModal('withdrawResults')">
                            <i class="fas fa-undo"></i> Withdraw Results
                        </button>
                        <button class="btn btn-info" onclick="openModal('schedulePublication')">
                            <i class="fas fa-clock"></i> Schedule Publication
                        </button>
                    </div>
                    
                    <div class="publication-overview">
                        <h3>Publication History</h3>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Publication ID</th>
                                        <th>Academic Year</th>
                                        <th>Semester</th>
                                        <th>Program</th>
                                        <th>Publication Date</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $publication_sql = "SELECT * FROM result_publication ORDER BY created_at DESC LIMIT 10";
                                    $publication_result = $staff_conn->query($publication_sql);
                                    $publications = $publication_result ? $publication_result->fetch_all(MYSQLI_ASSOC) : [];
                                    
                                    foreach ($publications as $pub):
                                    ?>
                                    <tr>
                                        <td><?php echo $pub['publication_id']; ?></td>
                                        <td><?php echo $pub['academic_year']; ?></td>
                                        <td><?php echo $pub['semester']; ?></td>
                                        <td><?php echo $pub['program'] ?? 'All Programs'; ?></td>
                                        <td><?php echo $pub['publication_date'] ? date('M j, Y H:i', strtotime($pub['publication_date'])) : 'Not Published'; ?></td>
                                        <td><span class="status-badge <?php echo strtolower($pub['status']); ?>"><?php echo $pub['status']; ?></span></td>
                                        <td>
                                            <?php if ($pub['status'] == 'Draft'): ?>
                                                <button class="btn btn-sm btn-outline-success" onclick="publishNow('<?php echo $pub['publication_id']; ?>')">Publish Now</button>
                                            <?php elseif ($pub['status'] == 'Published'): ?>
                                                <button class="btn btn-sm btn-outline-warning" onclick="withdrawPublication('<?php echo $pub['publication_id']; ?>')">Withdraw</button>
                                            <?php endif; ?>
                                            <button class="btn btn-sm btn-outline-primary">View Details</button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </section>

                <!-- Transcript Generation & Printing -->
                <section id="transcripts" class="content-section">
                    <h2>Transcript Generation & Printing</h2>
                    <div class="transcript-actions">
                        <button class="btn btn-primary" onclick="openModal('generateTranscript')">
                            <i class="fas fa-file-alt"></i> Generate Transcript
                        </button>
                        <button class="btn btn-success" onclick="openModal('printGradeReport')">
                            <i class="fas fa-print"></i> Print Grade Report
                        </button>
                        <button class="btn btn-info" onclick="openModal('printAcademicProfile')">
                            <i class="fas fa-user-graduate"></i> Print Academic Profile
                        </button>
                    </div>
                    
                    <div class="transcript-requests">
                        <h3>Recent Transcript Requests</h3>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Transcript #</th>
                                        <th>Student</th>
                                        <th>Requested By</th>
                                        <th>Generation Date</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $transcript_sql = "SELECT tgl.*, s.student_number, s.first_name, s.last_name, st.first_name as requester_first, st.last_name as requester_last
                                                      FROM transcript_generation_log tgl
                                                      JOIN students s ON tgl.student_id = s.id
                                                      LEFT JOIN staff st ON tgl.requested_by = st.id
                                                      ORDER BY tgl.created_at DESC LIMIT 10";
                                    $transcript_result = $students_conn->query($transcript_sql);
                                    $transcripts = $transcript_result ? $transcript_result->fetch_all(MYSQLI_ASSOC) : [];
                                    
                                    foreach ($transcripts as $transcript):
                                    ?>
                                    <tr>
                                        <td><?php echo $transcript['transcript_number']; ?></td>
                                        <td><?php echo $transcript['student_number'] . ' - ' . $transcript['first_name'] . ' ' . $transcript['last_name']; ?></td>
                                        <td><?php echo ($transcript['requester_first'] ?? 'System') . ' ' . ($transcript['requester_last'] ?? ''); ?></td>
                                        <td><?php echo date('M j, Y H:i', strtotime($transcript['generation_date'])); ?></td>
                                        <td><span class="status-badge <?php echo strtolower($transcript['status']); ?>"><?php echo $transcript['status']; ?></span></td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary" onclick="printTranscript('<?php echo $transcript['transcript_number']; ?>')">Print</button>
                                            <button class="btn btn-sm btn-outline-info">Preview</button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </section>

                <!-- Recent Activities -->
                <section class="activities-section">
                    <h2>Recent Registrar Activities</h2>
                    <div class="activities-list">
                        <?php foreach ($recent_activities as $activity): ?>
                        <div class="activity-item">
                            <div class="activity-icon">
                                <i class="fas fa-<?php echo $activity['icon'] ?? 'check-circle'; ?>"></i>
                            </div>
                            <div class="activity-content">
                                <p><strong><?php echo $activity['user_name'] ?? 'Academic Registrar'; ?></strong> <?php echo $activity['action'] ?? $activity['activity'] ?? 'Activity'; ?></p>
                                <small><?php echo date('M j, Y H:i', strtotime($activity['created_at'])); ?></small>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </section>
            </div>
        </div>
    </div>

    <!-- Modals -->
    <div class="modal fade" id="actionModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Action</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="modalBody">
                    <!-- Dynamic content -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="modalAction">Save</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Update current date/time
        function updateDateTime() {
            const now = new Date();
            const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
            document.getElementById('currentDate').textContent = now.toLocaleDateString('en-US', options);
        }
        updateDateTime();
        setInterval(updateDateTime, 60000);

        // Navigation
        document.querySelectorAll('.sidebar-nav .nav-link').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                document.querySelectorAll('.sidebar-nav .nav-link').forEach(l => l.classList.remove('active'));
                this.classList.add('active');
                
                const targetId = this.getAttribute('href').substring(1);
                document.querySelectorAll('.content-section').forEach(section => {
                    section.style.display = 'none';
                });
                const targetSection = document.getElementById(targetId);
                if (targetSection) {
                    targetSection.style.display = 'block';
                }
            });
        });

        // Modal functions
        function openModal(action) {
            const modal = new bootstrap.Modal(document.getElementById('actionModal'));
            const modalTitle = document.getElementById('modalTitle');
            const modalBody = document.getElementById('modalBody');
            
            switch(action) {
                case 'reviewApplication':
                    modalTitle.textContent = 'Review Application';
                    modalBody.innerHTML = `
                        <div class="application-review">
                            <h5>Application Details</h5>
                            <div class="applicant-info">
                                <div class="info-row">
                                    <strong>Application ID:</strong> APP-2026-001
                                </div>
                                <div class="info-row">
                                    <strong>Name:</strong> John Doe
                                </div>
                                <div class="info-row">
                                    <strong>Program:</strong> Certificate Nursing
                                </div>
                                <div class="info-row">
                                    <strong>Applied Date:</strong> April 20, 2026
                                </div>
                                <div class="info-row">
                                    <strong>Qualifications:</strong> UCE - English (C4), Math (C3), Biology (C3), Chemistry (C4), Physics (P7)
                                </div>
                            </div>
                            <div class="review-actions">
                                <h6>Review Comments</h6>
                                <textarea class="form-control mb-3" rows="3" placeholder="Add review comments..."></textarea>
                                <div class="decision-buttons">
                                    <button class="btn btn-success">Approve Application</button>
                                    <button class="btn btn-warning">Request Interview</button>
                                    <button class="btn btn-danger">Reject Application</button>
                                </div>
                            </div>
                        </div>
                    `;
                    break;
                case 'newRegistration':
                    modalTitle.textContent = 'New Student Registration';
                    modalBody.innerHTML = `
                        <form>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Student ID</label>
                                        <input type="text" class="form-control" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Application ID</label>
                                        <input type="text" class="form-control" required>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">First Name</label>
                                        <input type="text" class="form-control" required>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Surname</label>
                                        <input type="text" class="form-control" required>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Other Names</label>
                                        <input type="text" class="form-control">
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Program</label>
                                        <select class="form-control" required>
                                            <option value="">Select Program</option>
                                            <option value="cert-nursing">Certificate Nursing</option>
                                            <option value="cert-midwifery">Certificate Midwifery</option>
                                            <option value="diploma-nursing">Diploma Nursing</option>
                                            <option value="diploma-midwifery">Diploma Midwifery</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Intake</label>
                                        <select class="form-control" required>
                                            <option value="">Select Intake</option>
                                            <option value="january">January 2026</option>
                                            <option value="july">July 2026</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Email</label>
                                        <input type="email" class="form-control" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Phone</label>
                                        <input type="tel" class="form-control" required>
                                    </div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Address</label>
                                <textarea class="form-control" rows="2" required></textarea>
                            </div>
                        </form>
                    `;
                    break;
                case 'generateTranscript':
                    modalTitle.textContent = 'Generate Academic Transcript';
                    modalBody.innerHTML = `
                        <form>
                            <div class="mb-3">
                                <label class="form-label">Student ID</label>
                                <input type="text" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Transcript Type</label>
                                <select class="form-control" required>
                                    <option value="">Select Type</option>
                                    <option value="official">Official Transcript</option>
                                    <option value="unofficial">Unofficial Transcript</option>
                                    <option value="provisional">Provisional Transcript</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Purpose</label>
                                <select class="form-control" required>
                                    <option value="">Select Purpose</option>
                                    <option value="job">Job Application</option>
                                    <option value="further-studies">Further Studies</option>
                                    <option value="transfer">Transfer</option>
                                    <option value="personal">Personal Use</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Include</label>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="includeGrades" checked>
                                    <label class="form-check-label" for="includeGrades">Grades and GPA</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="includeAttendance">
                                    <label class="form-check-label" for="includeAttendance">Attendance Records</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="includeConduct">
                                    <label class="form-check-label" for="includeConduct">Conduct Report</label>
                                </div>
                            </div>
                        </form>
                    `;
                    break;
                case 'enterGrades':
                    modalTitle.textContent = 'Enter Student Grades';
                    modalBody.innerHTML = `
                        <form action="academic-registrar.php" method="POST">
                            <input type="hidden" name="action" value="enter_grades">
                            <div class="mb-3">
                                <label class="form-label">Student ID</label>
                                <input type="text" class="form-control" name="student_id" required>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Course Code</label>
                                        <input type="text" class="form-control" name="course_code" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Course Name</label>
                                        <input type="text" class="form-control" name="course_name" required>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Continuous Assessment (40%)</label>
                                        <input type="number" class="form-control" name="continuous_assessment" min="0" max="40" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Final Exam (60%)</label>
                                        <input type="number" class="form-control" name="final_exam" min="0" max="60" required>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Semester</label>
                                        <select class="form-control" name="semester" required>
                                            <option value="">Select Semester</option>
                                            <option value="Semester 1">Semester 1</option>
                                            <option value="Semester 2">Semester 2</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Academic Year</label>
                                        <input type="text" class="form-control" name="academic_year" value="2024-2025" required>
                                    </div>
                                </div>
                            </div>
                        </form>
                    `;
                    break;
                case 'addCalendar':
                    modalTitle.textContent = 'Add Academic Calendar';
                    modalBody.innerHTML = `
                        <form action="academic-registrar.php" method="POST">
                            <input type="hidden" name="action" value="add_calendar">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Academic Year</label>
                                        <input type="text" class="form-control" name="academic_year" value="2024-2025" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Semester</label>
                                        <select class="form-control" name="semester" required>
                                            <option value="">Select Semester</option>
                                            <option value="Semester 1">Semester 1</option>
                                            <option value="Semester 2">Semester 2</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Semester Start Date</label>
                                        <input type="date" class="form-control" name="semester_start" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Semester End Date</label>
                                        <input type="date" class="form-control" name="semester_end" required>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Exam Start Date</label>
                                        <input type="date" class="form-control" name="exam_start" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Exam End Date</label>
                                        <input type="date" class="form-control" name="exam_end" required>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Result Publication Date</label>
                                        <input type="date" class="form-control" name="result_publication">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Registration Deadline</label>
                                        <input type="date" class="form-control" name="registration_deadline" required>
                                    </div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Notes</label>
                                <textarea class="form-control" name="notes" rows="3"></textarea>
                            </div>
                        </form>
                    `;
                    break;
                case 'publishResults':
                    modalTitle.textContent = 'Publish Results';
                    modalBody.innerHTML = `
                        <form action="academic-registrar.php" method="POST">
                            <input type="hidden" name="action" value="publish_results">
                            <div class="mb-3">
                                <label class="form-label">Academic Year</label>
                                <input type="text" class="form-control" name="academic_year" value="2024-2025" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Semester</label>
                                <select class="form-control" name="semester" required>
                                    <option value="">Select Semester</option>
                                    <option value="Semester 1">Semester 1</option>
                                    <option value="Semester 2">Semester 2</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Program (Optional)</label>
                                <select class="form-control" name="program">
                                    <option value="">All Programs</option>
                                    <option value="Nursing">Nursing</option>
                                    <option value="Midwifery">Midwifery</option>
                                </select>
                            </div>
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle"></i> This will publish all approved results for the selected semester. This action cannot be undone.
                            </div>
                        </form>
                    `;
                    break;
                // Add more cases as needed
            }
            
            modal.show();
        }
    </script>
    
    <!-- Student Profile Modal -->
    <?php echo displayStudentProfileModal(''); ?>
    
    <!-- Student Profile Styles -->
    <?php echo getStudentProfileStyles(); ?>
    
    <script>
    // Override modal functions for registrar dashboard
    function viewFullProfile(studentId) {
        showStudentProfileModal(studentId);
    }
    
    function editStudent(studentId) {
        window.location.href = '../student_accounts_management.php?action=edit&student_id=' + studentId;
    }
    
    function viewAcademic(studentId) {
        window.location.href = '../academic_records.php?student_id=' + studentId;
    }
    
    function viewFees(studentId) {
        window.location.href = '../fee_management.php?student_id=' + studentId;
    }
    
    function sendMessage(studentId) {
        // Implement messaging functionality
        alert('Messaging functionality would be implemented here for student: ' + studentId);
    }
    
    function printProfile(studentId) {
        window.print();
    }
    
    function approveGrade(workflowNumber) {
        if (confirm('Are you sure you want to approve this grade?')) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'academic-registrar.php';
            
            const actionInput = document.createElement('input');
            actionInput.type = 'hidden';
            actionInput.name = 'action';
            actionInput.value = 'approve_grade';
            
            const workflowInput = document.createElement('input');
            workflowInput.type = 'hidden';
            workflowInput.name = 'workflow_number';
            workflowInput.value = workflowNumber;
            
            const commentsInput = document.createElement('input');
            commentsInput.type = 'hidden';
            commentsInput.name = 'comments';
            commentsInput.value = 'Approved by Academic Registrar';
            
            form.appendChild(actionInput);
            form.appendChild(workflowInput);
            form.appendChild(commentsInput);
            document.body.appendChild(form);
            form.submit();
        }
    }
    
    function rejectGrade(workflowNumber) {
        const reason = prompt('Please enter the reason for rejection:');
        if (reason) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'academic-registrar.php';
            
            const actionInput = document.createElement('input');
            actionInput.type = 'hidden';
            actionInput.name = 'action';
            actionInput.value = 'reject_grade';
            
            const workflowInput = document.createElement('input');
            workflowInput.type = 'hidden';
            workflowInput.name = 'workflow_number';
            workflowInput.value = workflowNumber;
            
            const reasonInput = document.createElement('input');
            reasonInput.type = 'hidden';
            reasonInput.name = 'rejection_reason';
            reasonInput.value = reason;
            
            form.appendChild(actionInput);
            form.appendChild(workflowInput);
            form.appendChild(reasonInput);
            document.body.appendChild(form);
            form.submit();
        }
    }
    
    function publishNow(publicationId) {
        if (confirm('Are you sure you want to publish these results now?')) {
            // This would typically call an API or submit a form
            alert('Results published successfully!');
        }
    }
    
    function withdrawPublication(publicationId) {
        if (confirm('Are you sure you want to withdraw these results? This action cannot be undone.')) {
            // This would typically call an API or submit a form
            alert('Results withdrawn successfully!');
        }
    }
    
    function printTranscript(transcriptNumber) {
        // Open print dialog for transcript
        const printWindow = window.open('', '_blank');
        printWindow.document.write('<html><head><title>Transcript - ' + transcriptNumber + '</title>');
        printWindow.document.write('<style>@media print { body { font-family: Arial, sans-serif; } .header { text-align: center; margin-bottom: 20px; } .logo { max-width: 100px; } table { width: 100%; border-collapse: collapse; } th, td { border: 1px solid #ddd; padding: 8px; text-align: left; } }</style>');
        printWindow.document.write('</head><body>');
        printWindow.document.write('<div class="header"><img src="../images/school-logo.png" class="logo" alt="ISNM Logo"><h2>Iganga School of Nursing and Midwifery</h2><h3>Official Academic Transcript</h3><p>Transcript #: ' + transcriptNumber + '</p></div>');
        printWindow.document.write('<p>This is an official academic transcript. For verification, contact the Academic Registrar.</p>');
        printWindow.document.write('<button onclick="window.print()">Print</button>');
        printWindow.document.write('</body></html>');
        printWindow.document.close();
    }
    
    function printGradeReport() {
        window.print();
    }
    
    function printAcademicProfile() {
        window.print();
    }
    </script>
</body>
</html>
