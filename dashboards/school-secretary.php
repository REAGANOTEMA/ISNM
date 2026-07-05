<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
require_once __DIR__ . '/../includes/enterprise_auth.php';
require_once __DIR__ . '/../includes/website_submissions_widget.php';
$ctx = bootstrapStaffDashboard(['school secretary', 'secretary']);
$conn = $ctx['staff'];
$students_conn = $ctx['students'] ?? null;
$user = $ctx['user'];
$user_id = (int)($user['id'] ?? 0);
$user_name = $user['full_name'] ?? 'School Secretary';
$students_db = defined('STUDENTS_DB_NAME') ? STUDENTS_DB_NAME : 'igangaschoolofl_students_db';
$staff_db = defined('STAFF_DB_NAME') ? STAFF_DB_NAME : 'igangaschoolofl_staffs_db';
$website_conn = $ctx['website'] ?? null;

$migrations = [
    "CREATE TABLE IF NOT EXISTS `$staff_db`.`secretary_appointments` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `user_id` INT NOT NULL,
        `title` VARCHAR(255) NOT NULL,
        `description` TEXT,
        `appointment_date` DATE NOT NULL,
        `appointment_time` TIME NOT NULL,
        `location` VARCHAR(255),
        `status` ENUM('pending','confirmed','cancelled','completed') DEFAULT 'pending',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    "CREATE TABLE IF NOT EXISTS `$staff_db`.`secretary_meetings` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `user_id` INT NOT NULL,
        `title` VARCHAR(255) NOT NULL,
        `description` TEXT,
        `meeting_date` DATE NOT NULL,
        `meeting_time` TIME NOT NULL,
        `duration_minutes` INT DEFAULT 60,
        `location` VARCHAR(255),
        `attendees` TEXT,
        `status` ENUM('scheduled','in_progress','completed','cancelled') DEFAULT 'scheduled',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    "CREATE TABLE IF NOT EXISTS `$staff_db`.`secretary_documents` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `user_id` INT NOT NULL,
        `title` VARCHAR(255) NOT NULL,
        `description` TEXT,
        `file_path` VARCHAR(500),
        `file_type` VARCHAR(50),
        `category` VARCHAR(100),
        `status` ENUM('active','archived') DEFAULT 'active',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    "CREATE TABLE IF NOT EXISTS `$staff_db`.`secretary_messages` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `sender_id` INT NOT NULL,
        `recipient_id` INT NOT NULL,
        `subject` VARCHAR(255),
        `message` TEXT NOT NULL,
        `is_read` TINYINT(1) DEFAULT 0,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    "CREATE TABLE IF NOT EXISTS `$staff_db`.`secretary_requests` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `user_id` INT NOT NULL,
        `request_type` VARCHAR(100) NOT NULL,
        `description` TEXT NOT NULL,
        `priority` ENUM('low','medium','high') DEFAULT 'medium',
        `status` ENUM('pending','in_progress','resolved','rejected') DEFAULT 'pending',
        `assigned_to` INT,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    "CREATE TABLE IF NOT EXISTS `$staff_db`.`secretary_announcements` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `user_id` INT NOT NULL,
        `title` VARCHAR(255) NOT NULL,
        `content` TEXT NOT NULL,
        `target_audience` VARCHAR(100) DEFAULT 'all',
        `is_active` TINYINT(1) DEFAULT 1,
        `publish_date` DATE NOT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    "CREATE TABLE IF NOT EXISTS `$staff_db`.`secretary_contacts` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `user_id` INT NOT NULL,
        `contact_name` VARCHAR(255) NOT NULL,
        `contact_email` VARCHAR(255),
        `contact_phone` VARCHAR(50),
        `organization` VARCHAR(255),
        `category` VARCHAR(100),
        `notes` TEXT,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
];
foreach ($migrations as $sql) { @$conn->query($sql); }

if ($students_conn) {
    $student_migrations = [
        "CREATE TABLE IF NOT EXISTS `$students_db`.`student_profiles` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `student_id` INT NOT NULL,
            `admission_status` ENUM('new','under_review','approved','registered','rejected') DEFAULT 'new',
            `fee_status` ENUM('paid','partial','unpaid') DEFAULT 'unpaid',
            `total_fees` DECIMAL(10,2) DEFAULT 0,
            `amount_paid` DECIMAL(10,2) DEFAULT 0,
            `notes` TEXT,
            `last_updated` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY `uk_student_profiles_student_id` (`student_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS `$students_db`.`student_status_history` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `student_id` INT NOT NULL,
            `status_type` VARCHAR(50) NOT NULL,
            `old_value` VARCHAR(100),
            `new_value` VARCHAR(100),
            `changed_by` INT,
            `changed_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    ];
    foreach ($student_migrations as $sql) { @$students_conn->query($sql); }
}

// Handle website submission actions
if (function_exists('handleWebsiteSubmissionsAction')) {
    handleWebsiteSubmissionsAction($website_conn);
}

if (isset($_REQUEST['ajax'])) {
    header('Content-Type: application/json');
    $action = $_REQUEST['ajax'];
    $response = ['success' => false, 'message' => ''];
    switch ($action) {
        case 'register_student':
            $full_name = trim($_POST['full_name'] ?? '');
            $other_names = trim($_POST['other_names'] ?? '');
            $dob = $_POST['date_of_birth'] ?? '';
            $gender = $_POST['gender'] ?? 'Female';
            $phone = trim($_POST['phone'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $address = trim($_POST['address'] ?? '');
            $guardian_name = trim($_POST['guardian_name'] ?? '');
            $guardian_phone = trim($_POST['guardian_phone'] ?? '');
            $program_name = $_POST['program'] ?? '';
            $intake = $_POST['intake'] ?? 'January';
            $admission_date = $_POST['admission_date'] ?? date('Y-m-d');
            $nationality = trim($_POST['nationality'] ?? 'Ugandan');
            $emergency_contact = trim($_POST['emergency_contact'] ?? '');
            $emergency_phone = trim($_POST['emergency_phone'] ?? '');
            if (!$full_name || !$gender || !$program_name || !$intake) {
                $response['message'] = 'Required fields are missing.';
                break;
            }
            $app_number = 'APP-' . date('Y') . '-' . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
            $student_number = 'STU' . date('Y') . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
            $reg_number = 'REG' . date('Y') . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
            $temp_password = bin2hex(random_bytes(4));
            $hashed_password = password_hash($temp_password, PASSWORD_BCRYPT);
            $status = 'New Applicant';
            $prog_id = 0;
            $pr = $conn->query("SELECT id FROM `$staff_db`.`academic_programs` WHERE program_name='" . $conn->real_escape_string($program_name) . "' LIMIT 1");
            if ($pr && $pr->num_rows > 0) { $prog_id = (int)$pr->fetch_assoc()['id']; }
            $conn->begin_transaction();
            try {
                $ins = $conn->prepare("INSERT INTO `$staff_db`.`applicants` (full_name, other_names, date_of_birth, gender, phone, email, address, guardian_name, guardian_phone, application_number, program_id, intake, admission_date, nationality, emergency_contact, emergency_phone, status) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
                $ins->bind_param('sssssssssssssssss', $full_name, $other_names, $dob, $gender, $phone, $email, $address, $guardian_name, $guardian_phone, $app_number, $prog_id, $intake, $admission_date, $nationality, $emergency_contact, $emergency_phone, $status);
                $ins->execute();
                $applicant_id = $conn->insert_id;
                $rc = 0;
                $ck = $conn->query("SELECT COUNT(*) as cnt FROM `$staff_db`.`admission_requirements` WHERE is_active=1");
                if ($ck) { $rc = (int)$ck->fetch_assoc()['cnt']; }
                $track = $conn->prepare("INSERT INTO `$staff_db`.`student_admission_tracking` (student_number, full_name, program, intake, admission_date, admission_status, requirements_completed, requirements_total) VALUES (?,?,?,?,?,?,?,?)");
                $track->bind_param('ssssssii', $student_number, $full_name, $program_name, $intake, $admission_date, $status, 0, $rc);
                $track->execute();
                $reqs = $conn->query("SELECT id FROM `$staff_db`.`admission_requirements` WHERE is_active=1");
                if ($reqs) {
                    $ins2 = $conn->prepare("INSERT IGNORE INTO `$staff_db`.`applicant_requirement_status` (applicant_id, requirement_id, status) VALUES (?,?,'Not Submitted')");
                    while ($rq = $reqs->fetch_assoc()) { $ins2->bind_param('ii', $applicant_id, $rq['id']); $ins2->execute(); }
                    $ins2->close();
                }
                if ($students_conn) {
                    $parts = explode(' ', $full_name);
                    $first_name = $parts[0] ?? $full_name;
                    $surname = count($parts) > 1 ? $parts[count($parts)-1] : $first_name;
                    $last_name = $parts[1] ?? $surname;
                    $year = 1; $level = 'Year 1';
                    $s_ins = $students_conn->prepare("INSERT IGNORE INTO `$students_db`.`students` (student_number, registration_number, first_name, surname, last_name, other_name, full_name, email, phone, program, course, year, level, intake_year, intake_period, date_of_birth, gender, address, guardian_name, guardian_phone, nationality, emergency_contact_name, emergency_contact_phone, status, password, is_first_login) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,'Active',?,0)");
                    $s_ins->bind_param('sssssssssssissssssssssss', $student_number, $reg_number, $first_name, $surname, $last_name, $other_names, $full_name, $email, $phone, $program_name, $program_name, $year, $level, (string)date('Y'), $intake, $dob, $gender, $address, $guardian_name, $guardian_phone, $nationality, $emergency_contact, $emergency_phone, $hashed_password);
                    $s_ins->execute();
                    $s_id = $students_conn->insert_id;
                    if ($s_id > 0) {
                        $prof = $students_conn->prepare("INSERT IGNORE INTO `$students_db`.`student_profiles` (student_id, admission_status, fee_status) VALUES (?,?,?)");
                        $prof->bind_param('iss', $s_id, $status, 'unpaid');
                        $prof->execute();
                    }
                }
                $conn->commit();
                $response['success'] = true;
                $response['message'] = 'Student registered successfully!';
                $response['student_number'] = $student_number;
                $response['reg_number'] = $reg_number;
                $response['portal_username'] = $student_number;
                $response['portal_password'] = $temp_password;
            } catch (Exception $e) {
                $conn->rollback();
                $response['message'] = 'Registration failed: ' . $e->getMessage();
            }
            break;

        case 'search_students':
            $search = trim($_REQUEST['search'] ?? '');
            $status_filter = $_REQUEST['status'] ?? '';
            $program_filter = $_REQUEST['program'] ?? '';
            $intake_filter = $_REQUEST['intake'] ?? '';
            $page = max(1, (int)($_REQUEST['page'] ?? 1));
            $per_page = 20;
            $offset = ($page - 1) * $per_page;
            $where = "WHERE 1=1";
            $params = [];
            $types = '';
            if ($search) {
                $where .= " AND (a.full_name LIKE ? OR a.application_number LIKE ? OR a.email LIKE ? OR a.phone LIKE ?)";
                $s = "%$search%";
                $params = array_merge($params, [$s, $s, $s, $s]);
                $types .= 'ssss';
            }
            if ($status_filter) { $where .= " AND a.status = ?"; $params[] = $status_filter; $types .= 's'; }
            if ($program_filter) { $where .= " AND a.program_id = ?"; $params[] = (int)$program_filter; $types .= 'i'; }
            if ($intake_filter) { $where .= " AND a.intake = ?"; $params[] = $intake_filter; $types .= 's'; }
            $count_sql = "SELECT COUNT(*) FROM `$staff_db`.`applicants` a $where";
            $count_stmt = $conn->prepare($count_sql);
            if ($params) $count_stmt->bind_param($types, ...$params);
            $count_stmt->execute();
            $total = $count_stmt->get_result()->fetch_row()[0];
            $sql = "SELECT a.*, ap.program_name, ap.program_code,
                    (SELECT COUNT(*) FROM `$staff_db`.`admission_requirements` ar
                     JOIN `$staff_db`.`applicant_requirement_status` ars ON ars.requirement_id = ar.id
                     WHERE ars.applicant_id = a.id AND ars.status IN ('Submitted','Verified')) as req_complete,
                    (SELECT COUNT(*) FROM `$staff_db`.`admission_requirements` WHERE is_active=1) as req_total
                    FROM `$staff_db`.`applicants` a
                    LEFT JOIN `$staff_db`.`academic_programs` ap ON a.program_id = ap.id $where
                    ORDER BY a.created_at DESC LIMIT $per_page OFFSET $offset";
            $stmt = $conn->prepare($sql);
            if ($params) $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $result = $stmt->get_result();
            $students = [];
            while ($row = $result->fetch_assoc()) { $students[] = $row; }
            $response['success'] = true;
            $response['students'] = $students;
            $response['total'] = (int)$total;
            $response['page'] = $page;
            $response['per_page'] = $per_page;
            $response['total_pages'] = (int)ceil($total / $per_page);
            break;
        case 'get_student':
            $id = (int)($_REQUEST['id'] ?? 0);
            if (!$id) { $response['message'] = 'Invalid student ID'; break; }
            $stmt = $conn->prepare("SELECT a.*, ap.program_name, ap.program_code FROM `$staff_db`.`applicants` a LEFT JOIN `$staff_db`.`academic_programs` ap ON a.program_id = ap.id WHERE a.id = ?");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                $req_stmt = $conn->prepare("SELECT ar.*, COALESCE(ars.status, 'Not Submitted') as req_status, ars.remarks, ars.submitted_at, ars.verified_at FROM `$staff_db`.`admission_requirements` ar LEFT JOIN `$staff_db`.`applicant_requirement_status` ars ON ars.requirement_id = ar.id AND ars.applicant_id = ? WHERE ar.is_active=1 ORDER BY ar.display_order");
                $req_stmt->bind_param('i', $id);
                $req_stmt->execute();
                $req_result = $req_stmt->get_result();
                $requirements = [];
                while ($r = $req_result->fetch_assoc()) { $requirements[] = $r; }
                $row['requirements'] = $requirements;
                $row['req_complete'] = count(array_filter($requirements, fn($r) => in_array($r['req_status'], ['Submitted', 'Verified'])));
                $row['req_total'] = count($requirements);
                $response['success'] = true;
                $response['student'] = $row;
            } else {
                $response['message'] = 'Student not found';
            }
            break;

        case 'update_student':
            $id = (int)($_POST['id'] ?? 0);
            if (!$id) { $response['message'] = 'Invalid ID'; break; }
            $fields = ['full_name','other_names','date_of_birth','gender','phone','email','address','guardian_name','guardian_phone','program_id','intake','status'];
            $sets = [];
            $params = [];
            $types = '';
            foreach ($fields as $f) {
                if (isset($_POST[$f])) {
                    $sets[] = "`$f` = ?";
                    $params[] = $_POST[$f];
                    $types .= 's';
                }
            }
            if (empty($sets)) { $response['message'] = 'No fields to update'; break; }
            $params[] = $id;
            $types .= 'i';
            $sql = "UPDATE `$staff_db`.`applicants` SET " . implode(', ', $sets) . " WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param($types, ...$params);
            if ($stmt->execute()) {
                $an = '';
                $r = $conn->query("SELECT application_number FROM `$staff_db`.`applicants` WHERE id=$id");
                if ($r && $r->num_rows > 0) $an = $r->fetch_assoc()['application_number'];
                if ($an) {
                    $track_sets = [];
                    $track_params = [];
                    $track_types = '';
                    if (isset($_POST['full_name'])) { $track_sets[] = "`full_name` = ?"; $track_params[] = $_POST['full_name']; $track_types .= 's'; }
                    if (isset($_POST['status'])) { $track_sets[] = "`admission_status` = ?"; $track_params[] = $_POST['status']; $track_types .= 's'; }
                    if (!empty($track_sets)) {
                        $track_params[] = $an;
                        $track_types .= 's';
                        $t_stmt = $conn->prepare("UPDATE `$staff_db`.`student_admission_tracking` SET " . implode(', ', $track_sets) . " WHERE student_number = ?");
                        $t_stmt->bind_param($track_types, ...$track_params);
                        $t_stmt->execute();
                    }
                }
                $response['success'] = true;
                $response['message'] = 'Student updated successfully';
            } else {
                $response['message'] = 'Update failed: ' . $conn->error;
            }
            break;

        case 'delete_student':
            $id = (int)($_POST['id'] ?? 0);
            if (!$id) { $response['message'] = 'Invalid ID'; break; }
            $an = '';
            $r = $conn->query("SELECT application_number FROM `$staff_db`.`applicants` WHERE id=$id");
            if ($r && $r->num_rows > 0) $an = $r->fetch_assoc()['application_number'];
            $stmt = $conn->prepare("DELETE FROM `$staff_db`.`applicants` WHERE id = ?");
            $stmt->bind_param('i', $id);
            if ($stmt->execute()) {
                $conn->query("DELETE FROM `$staff_db`.`applicant_requirement_status` WHERE applicant_id = $id");
                $conn->query("DELETE FROM `$staff_db`.`student_documents` WHERE applicant_id = $id");
                if ($an) $conn->query("DELETE FROM `$staff_db`.`student_admission_tracking` WHERE student_number = '" . $conn->real_escape_string($an) . "'");
                $response['success'] = true;
                $response['message'] = 'Student deleted';
            } else {
                $response['message'] = 'Delete failed';
            }
            break;

        case 'get_student_profile':
            $id = (int)($_REQUEST['id'] ?? 0);
            if (!$id) { $response['message'] = 'Invalid ID'; break; }
            $stmt = $conn->prepare("SELECT a.*, ap.program_name, ap.program_code FROM `$staff_db`.`applicants` a LEFT JOIN `$staff_db`.`academic_programs` ap ON a.program_id = ap.id WHERE a.id = ?");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $student = $stmt->get_result()->fetch_assoc();
            if (!$student) { $response['message'] = 'Not found'; break; }
            $req_stmt = $conn->prepare("SELECT ar.*, COALESCE(ars.status, 'Not Submitted') as req_status, ars.remarks, ars.submitted_at, ars.verified_at FROM `$staff_db`.`admission_requirements` ar LEFT JOIN `$staff_db`.`applicant_requirement_status` ars ON ars.requirement_id = ar.id AND ars.applicant_id = ? WHERE ar.is_active=1 ORDER BY ar.display_order");
            $req_stmt->bind_param('i', $id);
            $req_stmt->execute();
            $req_res = $req_stmt->get_result();
            $requirements = [];
            while ($r = $req_res->fetch_assoc()) { $requirements[] = $r; }
            $doc_stmt = $conn->prepare("SELECT * FROM `$staff_db`.`student_documents` WHERE applicant_id = ? AND document_status='Active' ORDER BY uploaded_at DESC");
            $doc_stmt->bind_param('i', $id);
            $doc_stmt->execute();
            $doc_res = $doc_stmt->get_result();
            $documents = [];
            while ($d = $doc_res->fetch_assoc()) { $documents[] = $d; }
            $response['success'] = true;
            $response['student'] = $student;
            $response['requirements'] = $requirements;
            $response['documents'] = $documents;
            $response['req_complete'] = count(array_filter($requirements, fn($r) => in_array($r['req_status'], ['Submitted', 'Verified'])));
            $response['req_total'] = count($requirements);
            break;

        case 'get_status_overview':
            $stmt = $conn->query("SELECT a.id, a.application_number, a.full_name, a.status as admission_status, a.intake, a.created_at,
                ap.program_name,
                (SELECT COUNT(*) FROM `$staff_db`.`admission_requirements` ar JOIN `$staff_db`.`applicant_requirement_status` ars ON ars.requirement_id = ar.id WHERE ars.applicant_id = a.id AND ars.status IN ('Submitted','Verified')) as req_complete,
                (SELECT COUNT(*) FROM `$staff_db`.`admission_requirements` WHERE is_active=1) as req_total,
                (SELECT COUNT(*) FROM `$staff_db`.`student_documents` WHERE applicant_id = a.id AND document_status='Active') as doc_count
                FROM `$staff_db`.`applicants` a LEFT JOIN `$staff_db`.`academic_programs` ap ON a.program_id = ap.id ORDER BY a.created_at DESC");
            $students = [];
            while ($row = $stmt->fetch_assoc()) { $students[] = $row; }
            $response['success'] = true;
            $response['students'] = $students;
            break;
        case 'approve_student':
            $id = (int)($_POST['id'] ?? 0);
            if (!$id) { $response['message'] = 'Invalid ID'; break; }
            $conn->query("UPDATE `$staff_db`.`applicants` SET status = 'Approved' WHERE id = $id");
            $an = '';
            $r = $conn->query("SELECT application_number FROM `$staff_db`.`applicants` WHERE id=$id");
            if ($r && $r->num_rows > 0) $an = $r->fetch_assoc()['application_number'];
            if ($an) $conn->query("UPDATE `$staff_db`.`student_admission_tracking` SET admission_status = 'Approved' WHERE student_number = '" . $conn->real_escape_string($an) . "'");
            $response['success'] = true;
            $response['message'] = 'Student approved';
            break;

        case 'reject_student':
            $id = (int)($_POST['id'] ?? 0);
            if (!$id) { $response['message'] = 'Invalid ID'; break; }
            $conn->query("UPDATE `$staff_db`.`applicants` SET status = 'Rejected' WHERE id = $id");
            $an = '';
            $r = $conn->query("SELECT application_number FROM `$staff_db`.`applicants` WHERE id=$id");
            if ($r && $r->num_rows > 0) $an = $r->fetch_assoc()['application_number'];
            if ($an) $conn->query("UPDATE `$staff_db`.`student_admission_tracking` SET admission_status = 'Rejected' WHERE student_number = '" . $conn->real_escape_string($an) . "'");
            $response['success'] = true;
            $response['message'] = 'Student rejected';
            break;

        case 'get_requirements':
            $applicant_id = (int)($_REQUEST['applicant_id'] ?? 0);
            if (!$applicant_id) { $response['message'] = 'Invalid ID'; break; }
            $stmt = $conn->prepare("SELECT ar.*, COALESCE(ars.status, 'Not Submitted') as req_status, ars.remarks, ars.submitted_at, ars.verified_at FROM `$staff_db`.`admission_requirements` ar LEFT JOIN `$staff_db`.`applicant_requirement_status` ars ON ars.requirement_id = ar.id AND ars.applicant_id = ? WHERE ar.is_active=1 ORDER BY ar.display_order");
            $stmt->bind_param('i', $applicant_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $reqs = [];
            while ($r = $result->fetch_assoc()) { $reqs[] = $r; }
            $response['success'] = true;
            $response['requirements'] = $reqs;
            break;

        case 'set_requirement_status':
            $applicant_id = (int)($_POST['applicant_id'] ?? 0);
            $requirement_id = (int)($_POST['requirement_id'] ?? 0);
            $status = $_POST['status'] ?? 'Not Submitted';
            $remarks = trim($_POST['remarks'] ?? trim($_POST['notes'] ?? ''));
            if (!$applicant_id || !$requirement_id) { $response['message'] = 'Invalid IDs'; break; }
            $valid = ['Not Submitted', 'Submitted', 'Verified', 'Rejected', 'Missing'];
            if (!in_array($status, $valid)) { $status = 'Not Submitted'; }
            $extra = '';
            if ($status === 'Submitted') { $extra = ", submitted_by=$user_id, submitted_at=NOW()"; }
            elseif ($status === 'Verified') { $extra = ", verified_by=$user_id, verified_at=NOW()"; }
            elseif ($status === 'Rejected') { $extra = ", rejected_by=$user_id"; }
            $stmt = $conn->prepare("INSERT INTO `$staff_db`.`applicant_requirement_status` (applicant_id, requirement_id, status, remarks) VALUES (?,?,?,?) ON DUPLICATE KEY UPDATE status=VALUES(status), remarks=VALUES(remarks)$extra");
            $stmt->bind_param('iiis', $applicant_id, $requirement_id, $status, $remarks);
            if ($stmt->execute()) {
                $response['success'] = true;
                $response['message'] = 'Requirement updated';
            } else {
                $response['message'] = 'Failed: ' . $conn->error;
            }
            break;

        case 'upload_document':
            $applicant_id = (int)($_POST['applicant_id'] ?? 0);
            $title = trim($_POST['title'] ?? trim($_POST['document_title'] ?? ''));
            $category = trim($_POST['category'] ?? 'general');
            $doc_type = trim($_POST['document_type'] ?? $category);
            $req_id = (int)($_POST['requirement_id'] ?? 0);
            if (!$applicant_id || !$title) { $response['message'] = 'Missing required fields'; break; }
            $file_name = '';
            $file_path = '';
            $file_size = 0;
            $mime_type = '';
            if (isset($_FILES['document']) && $_FILES['document']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = __DIR__ . '/../uploads/admissions/applicant_' . $applicant_id . '/';
                if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
                $ext = pathinfo($_FILES['document']['name'], PATHINFO_EXTENSION);
                $file_name = 'doc_' . $req_id . '_' . time() . '.' . $ext;
                $target = $upload_dir . $file_name;
                if (move_uploaded_file($_FILES['document']['tmp_name'], $target)) {
                    $file_path = '../uploads/admissions/applicant_' . $applicant_id . '/' . $file_name;
                    $file_size = $_FILES['document']['size'];
                    $mime_type = $_FILES['document']['type'];
                }
            }
            $stmt = $conn->prepare("INSERT INTO `$staff_db`.`student_documents` (applicant_id, requirement_id, document_type, document_title, file_name, file_path, file_size, mime_type, uploaded_by) VALUES (?,?,?,?,?,?,?,?,?)");
            $stmt->bind_param('iisssssii', $applicant_id, $req_id, $doc_type, $title, $file_name, $file_path, $file_size, $mime_type, $user_id);
            if ($stmt->execute()) {
                $response['success'] = true;
                $response['message'] = 'Document uploaded';
                $response['doc_id'] = $conn->insert_id;
            } else {
                $response['message'] = 'Upload failed';
            }
            break;

        case 'verify_document':
            $doc_id = (int)($_POST['doc_id'] ?? 0);
            $status = $_POST['status'] ?? 'Verified';
            if (!$doc_id) { $response['message'] = 'Invalid ID'; break; }
            $valid = ['Pending', 'Verified', 'Rejected'];
            if (!in_array($status, $valid)) { $status = 'Verified'; }
            $stmt = $conn->prepare("UPDATE `$staff_db`.`student_documents` SET verification_status = ?, verified_by = ?, verified_at = NOW() WHERE id = ?");
            $stmt->bind_param('sii', $status, $user_id, $doc_id);
            if ($stmt->execute()) { $response['success'] = true; $response['message'] = 'Document ' . strtolower($status); }
            else { $response['message'] = 'Failed'; }
            break;

        case 'get_admission_stats':
            $stats = [];
            $r = $conn->query("SELECT status, COUNT(*) as cnt FROM `$staff_db`.`applicants` GROUP BY status");
            $stats['by_status'] = [];
            while ($row = $r->fetch_assoc()) { $stats['by_status'][$row['status']] = (int)$row['cnt']; }
            $stats['total'] = array_sum($stats['by_status']);
            $r2 = $conn->query("SELECT ap.program_name as program, COUNT(a.id) as cnt FROM `$staff_db`.`applicants` a LEFT JOIN `$staff_db`.`academic_programs` ap ON a.program_id = ap.id GROUP BY a.program_id, ap.program_name ORDER BY cnt DESC");
            $stats['by_program'] = [];
            while ($row = $r2->fetch_assoc()) { $stats['by_program'][] = $row; }
            $r3 = $conn->query("SELECT intake, COUNT(*) as cnt FROM `$staff_db`.`applicants` GROUP BY intake ORDER BY cnt DESC");
            $stats['by_intake'] = [];
            while ($row = $r3->fetch_assoc()) { $stats['by_intake'][] = $row; }
            $response['success'] = true;
            $response['stats'] = $stats;
            break;

        case 'get_students_incomplete':
            $stmt = $conn->query("SELECT a.id, a.application_number, a.full_name, a.status,
                ap.program_name, a.intake,
                (SELECT COUNT(*) FROM `$staff_db`.`admission_requirements` ar JOIN `$staff_db`.`applicant_requirement_status` ars ON ars.requirement_id = ar.id WHERE ars.applicant_id = a.id AND ars.status IN ('Submitted','Verified')) as req_complete,
                (SELECT COUNT(*) FROM `$staff_db`.`admission_requirements` WHERE is_active=1) as req_total
                FROM `$staff_db`.`applicants` a
                LEFT JOIN `$staff_db`.`academic_programs` ap ON a.program_id = ap.id
                ORDER BY req_complete ASC");
            $students = [];
            while ($row = $stmt->fetch_assoc()) { if ((int)$row['req_complete'] < (int)$row['req_total']) $students[] = $row; }
            $response['success'] = true;
            $response['students'] = $students;
            break;

        case 'export_students_csv':
            $search = trim($_REQUEST['search'] ?? '');
            $status_filter = $_REQUEST['status'] ?? '';
            $program_filter = $_REQUEST['program'] ?? '';
            $where = "WHERE 1=1";
            $params = [];
            $types = '';
            if ($search) { $where .= " AND (a.full_name LIKE ? OR a.application_number LIKE ?)"; $s = "%$search%"; $params = [$s, $s]; $types = 'ss'; }
            if ($status_filter) { $where .= " AND a.status = ?"; $params[] = $status_filter; $types .= 's'; }
            if ($program_filter) { $where .= " AND a.program_id = ?"; $params[] = (int)$program_filter; $types .= 'i'; }
            $stmt = $conn->prepare("SELECT a.application_number, a.full_name, a.email, a.phone, ap.program_name, a.intake, a.status, a.date_of_birth, a.gender, a.address, a.guardian_name, a.guardian_phone, a.created_at FROM `$staff_db`.`applicants` a LEFT JOIN `$staff_db`.`academic_programs` ap ON a.program_id = ap.id $where ORDER BY a.created_at DESC");
            if ($params) $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $result = $stmt->get_result();
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="students_export_' . date('Y-m-d') . '.csv"');
            $output = fopen('php://output', 'w');
            fputcsv($output, ['Application Number', 'Full Name', 'Email', 'Phone', 'Program', 'Intake', 'Status', 'DOB', 'Gender', 'Address', 'Guardian', 'Guardian Phone', 'Created']);
            while ($row = $result->fetch_assoc()) { fputcsv($output, $row); }
            fclose($output);
            exit;
        case 'get_appointments':
            $stmt = $conn->prepare("SELECT * FROM `$staff_db`.`secretary_appointments` WHERE user_id = ? ORDER BY appointment_date DESC, appointment_time DESC");
            $stmt->bind_param('i', $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $items = [];
            while ($row = $result->fetch_assoc()) { $items[] = $row; }
            $response['success'] = true;
            $response['appointments'] = $items;
            break;

        case 'save_appointment':
            $title = trim($_POST['title'] ?? '');
            $desc = trim($_POST['description'] ?? '');
            $date = $_POST['appointment_date'] ?? '';
            $time = $_POST['appointment_time'] ?? '';
            $location = trim($_POST['location'] ?? '');
            $status = $_POST['status'] ?? 'pending';
            if (!$title || !$date || !$time) { $response['message'] = 'Missing required fields'; break; }
            $id = (int)($_POST['id'] ?? 0);
            if ($id) {
                $stmt = $conn->prepare("UPDATE `$staff_db`.`secretary_appointments` SET title=?, description=?, appointment_date=?, appointment_time=?, location=?, status=? WHERE id=? AND user_id=?");
                $stmt->bind_param('ssssssii', $title, $desc, $date, $time, $location, $status, $id, $user_id);
            } else {
                $stmt = $conn->prepare("INSERT INTO `$staff_db`.`secretary_appointments` (user_id, title, description, appointment_date, appointment_time, location, status) VALUES (?,?,?,?,?,?,?)");
                $stmt->bind_param('issssss', $user_id, $title, $desc, $date, $time, $location, $status);
            }
            if ($stmt->execute()) { $response['success'] = true; $response['message'] = 'Appointment saved'; }
            else { $response['message'] = 'Failed: ' . $conn->error; }
            break;

        case 'delete_appointment':
            $id = (int)($_POST['id'] ?? 0);
            $stmt = $conn->prepare("DELETE FROM `$staff_db`.`secretary_appointments` WHERE id=? AND user_id=?");
            $stmt->bind_param('ii', $id, $user_id);
            if ($stmt->execute()) { $response['success'] = true; $response['message'] = 'Deleted'; }
            break;

        case 'get_meetings':
            $stmt = $conn->prepare("SELECT * FROM `$staff_db`.`secretary_meetings` WHERE user_id = ? ORDER BY meeting_date DESC");
            $stmt->bind_param('i', $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $items = [];
            while ($row = $result->fetch_assoc()) { $items[] = $row; }
            $response['success'] = true;
            $response['meetings'] = $items;
            break;

        case 'save_meeting':
            $title = trim($_POST['title'] ?? '');
            $desc = trim($_POST['description'] ?? '');
            $date = $_POST['meeting_date'] ?? '';
            $time = $_POST['meeting_time'] ?? '';
            $duration = (int)($_POST['duration_minutes'] ?? 60);
            $location = trim($_POST['location'] ?? '');
            $attendees = trim($_POST['attendees'] ?? '');
            $status = $_POST['status'] ?? 'scheduled';
            if (!$title || !$date || !$time) { $response['message'] = 'Missing fields'; break; }
            $id = (int)($_POST['id'] ?? 0);
            if ($id) {
                $stmt = $conn->prepare("UPDATE `$staff_db`.`secretary_meetings` SET title=?, description=?, meeting_date=?, meeting_time=?, duration_minutes=?, location=?, attendees=?, status=? WHERE id=? AND user_id=?");
                $stmt->bind_param('sssissssii', $title, $desc, $date, $time, $duration, $location, $attendees, $status, $id, $user_id);
            } else {
                $stmt = $conn->prepare("INSERT INTO `$staff_db`.`secretary_meetings` (user_id, title, description, meeting_date, meeting_time, duration_minutes, location, attendees, status) VALUES (?,?,?,?,?,?,?,?,?)");
                $stmt->bind_param('isssisssi', $user_id, $title, $desc, $date, $time, $duration, $location, $attendees, $status);
            }
            if ($stmt->execute()) { $response['success'] = true; $response['message'] = 'Meeting saved'; }
            break;

        case 'delete_meeting':
            $id = (int)($_POST['id'] ?? 0);
            $stmt = $conn->prepare("DELETE FROM `$staff_db`.`secretary_meetings` WHERE id=? AND user_id=?");
            $stmt->bind_param('ii', $id, $user_id);
            if ($stmt->execute()) { $response['success'] = true; $response['message'] = 'Deleted'; }
            break;

        case 'get_messages':
            $stmt = $conn->prepare("SELECT m.*, s.full_name as sender_name FROM `$staff_db`.`secretary_messages` m LEFT JOIN `$staff_db`.`staffs` s ON s.id = m.sender_id WHERE m.recipient_id = ? ORDER BY m.created_at DESC");
            $stmt->bind_param('i', $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $items = [];
            while ($row = $result->fetch_assoc()) { $items[] = $row; }
            $response['success'] = true;
            $response['messages'] = $items;
            break;

        case 'send_message':
            $recipient_id = (int)($_POST['recipient_id'] ?? 0);
            $subject = trim($_POST['subject'] ?? '');
            $message = trim($_POST['message'] ?? '');
            if (!$recipient_id || !$message) { $response['message'] = 'Missing fields'; break; }
            $stmt = $conn->prepare("INSERT INTO `$staff_db`.`secretary_messages` (sender_id, recipient_id, subject, message) VALUES (?,?,?,?)");
            $stmt->bind_param('iiss', $user_id, $recipient_id, $subject, $message);
            if ($stmt->execute()) { $response['success'] = true; $response['message'] = 'Message sent'; }
            break;

        case 'mark_message_read':
            $id = (int)($_POST['id'] ?? 0);
            $conn->query("UPDATE `$staff_db`.`secretary_messages` SET is_read = 1 WHERE id = $id AND recipient_id = $user_id");
            $response['success'] = true;
            break;

        case 'get_requests':
            $stmt = $conn->prepare("SELECT * FROM `$staff_db`.`secretary_requests` WHERE user_id = ? ORDER BY created_at DESC");
            $stmt->bind_param('i', $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $items = [];
            while ($row = $result->fetch_assoc()) { $items[] = $row; }
            $response['success'] = true;
            $response['requests'] = $items;
            break;

        case 'save_request':
            $type = trim($_POST['request_type'] ?? '');
            $desc = trim($_POST['description'] ?? '');
            $priority = $_POST['priority'] ?? 'medium';
            if (!$type || !$desc) { $response['message'] = 'Missing fields'; break; }
            $id = (int)($_POST['id'] ?? 0);
            if ($id) {
                $status = $_POST['status'] ?? 'pending';
                $stmt = $conn->prepare("UPDATE `$staff_db`.`secretary_requests` SET request_type=?, description=?, priority=?, status=? WHERE id=? AND user_id=?");
                $stmt->bind_param('ssssii', $type, $desc, $priority, $status, $id, $user_id);
            } else {
                $stmt = $conn->prepare("INSERT INTO `$staff_db`.`secretary_requests` (user_id, request_type, description, priority) VALUES (?,?,?,?)");
                $stmt->bind_param('isss', $user_id, $type, $desc, $priority);
            }
            if ($stmt->execute()) { $response['success'] = true; $response['message'] = 'Request saved'; }
            break;

        case 'get_announcements':
            $stmt = $conn->prepare("SELECT * FROM `$staff_db`.`secretary_announcements` WHERE user_id = ? ORDER BY publish_date DESC");
            $stmt->bind_param('i', $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $items = [];
            while ($row = $result->fetch_assoc()) { $items[] = $row; }
            $response['success'] = true;
            $response['announcements'] = $items;
            break;

        case 'save_announcement':
            $title = trim($_POST['title'] ?? '');
            $content = trim($_POST['content'] ?? '');
            $audience = $_POST['target_audience'] ?? 'all';
            $pub_date = $_POST['publish_date'] ?? date('Y-m-d');
            if (!$title || !$content) { $response['message'] = 'Missing fields'; break; }
            $id = (int)($_POST['id'] ?? 0);
            if ($id) {
                $stmt = $conn->prepare("UPDATE `$staff_db`.`secretary_announcements` SET title=?, content=?, target_audience=?, publish_date=? WHERE id=? AND user_id=?");
                $stmt->bind_param('ssssii', $title, $content, $audience, $pub_date, $id, $user_id);
            } else {
                $stmt = $conn->prepare("INSERT INTO `$staff_db`.`secretary_announcements` (user_id, title, content, target_audience, publish_date) VALUES (?,?,?,?,?)");
                $stmt->bind_param('issss', $user_id, $title, $content, $audience, $pub_date);
            }
            if ($stmt->execute()) { $response['success'] = true; $response['message'] = 'Announcement saved'; }
            break;

        case 'get_contacts':
            $stmt = $conn->prepare("SELECT * FROM `$staff_db`.`secretary_contacts` WHERE user_id = ? ORDER BY contact_name ASC");
            $stmt->bind_param('i', $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $items = [];
            while ($row = $result->fetch_assoc()) { $items[] = $row; }
            $response['success'] = true;
            $response['contacts'] = $items;
            break;

        case 'save_contact':
            $name = trim($_POST['contact_name'] ?? '');
            $email = trim($_POST['contact_email'] ?? '');
            $phone = trim($_POST['contact_phone'] ?? '');
            $org = trim($_POST['organization'] ?? '');
            $cat = trim($_POST['category'] ?? '');
            $notes = trim($_POST['notes'] ?? '');
            if (!$name) { $response['message'] = 'Name required'; break; }
            $id = (int)($_POST['id'] ?? 0);
            if ($id) {
                $stmt = $conn->prepare("UPDATE `$staff_db`.`secretary_contacts` SET contact_name=?, contact_email=?, contact_phone=?, organization=?, category=?, notes=? WHERE id=? AND user_id=?");
                $stmt->bind_param('ssssssii', $name, $email, $phone, $org, $cat, $notes, $id, $user_id);
            } else {
                $stmt = $conn->prepare("INSERT INTO `$staff_db`.`secretary_contacts` (user_id, contact_name, contact_email, contact_phone, organization, category, notes) VALUES (?,?,?,?,?,?,?)");
                $stmt->bind_param('issssss', $user_id, $name, $email, $phone, $org, $cat, $notes);
            }
            if ($stmt->execute()) { $response['success'] = true; $response['message'] = 'Contact saved'; }
            break;

        case 'get_dashboard_stats':
            $stats = [];
            $r = @$conn->query("SELECT COUNT(*) as cnt FROM `$staff_db`.`applicants`");
            $stats['total_students'] = (int)$r->fetch_assoc()['cnt'];
            $r = @$conn->query("SELECT COUNT(*) as cnt FROM `$staff_db`.`applicants` WHERE status = 'Approved'");
            $stats['active_students'] = (int)$r->fetch_assoc()['cnt'];
            $r = @$conn->query("SELECT COUNT(*) as cnt FROM `$staff_db`.`applicants` WHERE status = 'New Applicant'");
            $stats['pending_admissions'] = (int)$r->fetch_assoc()['cnt'];
            $r = @$conn->query("SELECT COUNT(*) as cnt FROM `$staff_db`.`secretary_appointments` WHERE user_id = $user_id AND appointment_date = CURDATE()");
            $stats['today_appointments'] = (int)$r->fetch_assoc()['cnt'];
            $r = @$conn->query("SELECT COUNT(*) as cnt FROM `$staff_db`.`secretary_messages` WHERE recipient_id = $user_id AND is_read = 0");
            $stats['unread_messages'] = (int)$r->fetch_assoc()['cnt'];
            $r = @$conn->query("SELECT COUNT(*) as cnt FROM `$staff_db`.`secretary_requests` WHERE user_id = $user_id AND status = 'pending'");
            $stats['pending_requests'] = (int)$r->fetch_assoc()['cnt'];
            $r = @$conn->query("SELECT COUNT(*) as cnt FROM `$staff_db`.`secretary_meetings` WHERE user_id = $user_id AND meeting_date = CURDATE()");
            $stats['today_meetings'] = (int)$r->fetch_assoc()['cnt'];
            $response['success'] = true;
            $response['stats'] = $stats;
            break;

        default:
            $response['message'] = 'Unknown action';
    }
    echo json_encode($response);
    exit;
}
$stats = ['total_students'=>0,'active_students'=>0,'pending_admissions'=>0,'incomplete_reqs'=>0,'today_appointments'=>0,'unread_messages'=>0];
$r = @$conn->query("SELECT COUNT(*) as cnt FROM `$staff_db`.`applicants`");
if ($r) $stats['total_students'] = (int)$r->fetch_assoc()['cnt'];
$r = @$conn->query("SELECT COUNT(*) as cnt FROM `$staff_db`.`applicants` WHERE status='Approved'");
if ($r) $stats['active_students'] = (int)$r->fetch_assoc()['cnt'];
$r = @$conn->query("SELECT COUNT(*) as cnt FROM `$staff_db`.`applicants` WHERE status='New Applicant'");
if ($r) $stats['pending_admissions'] = (int)$r->fetch_assoc()['cnt'];
$r = @$conn->query("SELECT COUNT(*) as cnt FROM `$staff_db`.`applicants` a WHERE (SELECT COUNT(*) FROM `$staff_db`.`admission_requirements` ar JOIN `$staff_db`.`applicant_requirement_status` ars ON ars.requirement_id=ar.id WHERE ars.applicant_id=a.id AND ars.status IN ('Submitted','Verified')) < (SELECT COUNT(*) FROM `$staff_db`.`admission_requirements` WHERE is_active=1)");
if ($r) $stats['incomplete_reqs'] = (int)$r->fetch_assoc()['cnt'];
$r = @$conn->query("SELECT COUNT(*) as cnt FROM `$staff_db`.`secretary_appointments` WHERE user_id=$user_id AND appointment_date=CURDATE()");
if ($r) $stats['today_appointments'] = (int)$r->fetch_assoc()['cnt'];
$r = @$conn->query("SELECT COUNT(*) as cnt FROM `$staff_db`.`secretary_messages` WHERE recipient_id=$user_id AND is_read=0");
if ($r) $stats['unread_messages'] = (int)$r->fetch_assoc()['cnt'];

$programs = [];
$prog_res = @$conn->query("SELECT program_name FROM `$staff_db`.`academic_programs` WHERE status='Active' ORDER BY program_name");
if ($prog_res) while ($p = $prog_res->fetch_assoc()) $programs[] = $p['program_name'];
if (empty($programs)) $programs = ['Nursing','Midwifery'];
$intakes = ['January','May','September'];
$page = $_REQUEST['page'] ?? 'home';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php $pageTitle = 'School Secretary'; include_once __DIR__ . '/../includes/dashboard_head.php'; ?>
<style>
.content-area{padding:20px 24px}
.stat-card{background:var(--isnm-card);border-radius:10px;padding:18px;box-shadow:0 1px 4px rgba(0,0,0,.06);border-left:4px solid var(--isnm-navy);transition:transform .2s}
.stat-card:hover{transform:translateY(-2px)}
.stat-card .stat-icon{width:44px;height:44px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:18px;color:#fff;margin-bottom:10px}
.stat-card .stat-value{font-size:24px;font-weight:700;color:var(--isnm-navy);line-height:1}
.stat-card .stat-label{font-size:12px;color:#666;margin-top:4px}
.table-card{background:var(--isnm-card);border-radius:10px;box-shadow:0 1px 4px rgba(0,0,0,.06);overflow:hidden}
.table-card .card-header{background:var(--isnm-navy);color:#fff;padding:12px 16px;font-size:14px;font-weight:600;display:flex;align-items:center;justify-content:space-between}
.table-card table{margin:0}
.table-card table th{background:#f8f9fa;font-size:12px;text-transform:uppercase;letter-spacing:.5px;color:#555;border-bottom:2px solid #dee2e6;padding:10px 12px}
.table-card table td{padding:10px 12px;font-size:13px;vertical-align:middle}
.badge-status{padding:4px 10px;border-radius:20px;font-size:11px;font-weight:600}
.badge-new{background:#e3f2fd;color:#1565c0}
.badge-under_review{background:#fff3e0;color:#e65100}
.badge-approved{background:#e8f5e9;color:#2e7d32}
.badge-registered{background:#e0f7fa;color:#00695c}
.badge-rejected{background:#ffebee;color:#c62828}
.badge-paid{background:#e8f5e9;color:#2e7d32}
.badge-partial{background:#fff3e0;color:#e65100}
.badge-unpaid{background:#ffebee;color:#c62828}
.progress-req{height:8px;border-radius:4px;background:#e9ecef}
.progress-req-bar{height:100%;border-radius:4px;background:linear-gradient(90deg,var(--isnm-navy),var(--isnm-accent))}
.btn-isnm{background:var(--isnm-navy);color:#fff;border:none;padding:6px 16px;border-radius:6px;font-size:13px}
.btn-isnm:hover{background:var(--isnm-blue);color:#fff}
.btn-isnm-outline{border:1px solid var(--isnm-navy);color:var(--isnm-navy);background:transparent;padding:6px 16px;border-radius:6px;font-size:13px}
.btn-isnm-outline:hover{background:var(--isnm-navy);color:#fff}
.modal-header{background:var(--isnm-navy);color:#fff}
.modal-header .btn-close{filter:brightness(0) invert(1)}
</style>
</head>
<body>
<?php include_once __DIR__ . '/../includes/sidebar.php'; ?>
<?php include_once __DIR__ . '/../includes/dashboard_topbar.php'; renderDashboardTopbar($pageTitle); ?>
<div class="content-area">
<?php if ($page === 'home'): ?>
<div class="row g-3 mb-4">
    <div class="col-md-4 col-lg-2">
        <div class="stat-card">
            <div class="stat-icon" style="background:linear-gradient(135deg,#1a237e,#3949ab)"><i class="fas fa-users"></i></div>
            <div class="stat-value"><?= $stats['total_students'] ?></div>
            <div class="stat-label">Total Students</div>
        </div>
    </div>
    <div class="col-md-4 col-lg-2">
        <div class="stat-card">
            <div class="stat-icon" style="background:linear-gradient(135deg,#2e7d32,#43a047)"><i class="fas fa-user-check"></i></div>
            <div class="stat-value"><?= $stats['active_students'] ?></div>
            <div class="stat-label">Active Students</div>
        </div>
    </div>
    <div class="col-md-4 col-lg-2">
        <div class="stat-card">
            <div class="stat-icon" style="background:linear-gradient(135deg,#e65100,#fb8c00)"><i class="fas fa-hourglass-half"></i></div>
            <div class="stat-value"><?= $stats['pending_admissions'] ?></div>
            <div class="stat-label">Pending Admissions</div>
        </div>
    </div>
    <div class="col-md-4 col-lg-2">
        <div class="stat-card">
            <div class="stat-icon" style="background:linear-gradient(135deg,#c62828,#ef5350)"><i class="fas fa-exclamation-triangle"></i></div>
            <div class="stat-value"><?= $stats['incomplete_reqs'] ?></div>
            <div class="stat-label">Incomplete Requirements</div>
        </div>
    </div>
    <div class="col-md-4 col-lg-2">
        <div class="stat-card">
            <div class="stat-icon" style="background:linear-gradient(135deg,#00695c,#26a69a)"><i class="fas fa-calendar-day"></i></div>
            <div class="stat-value"><?= $stats['today_appointments'] ?></div>
            <div class="stat-label">Today's Appointments</div>
        </div>
    </div>
    <div class="col-md-4 col-lg-2">
        <div class="stat-card">
            <div class="stat-icon" style="background:linear-gradient(135deg,#4527a0,#7e57c2)"><i class="fas fa-envelope-open"></i></div>
            <div class="stat-value"><?= $stats['unread_messages'] ?></div>
            <div class="stat-label">Unread Messages</div>
        </div>
    </div>
</div>
<div class="row g-3">
    <div class="col-lg-8">
        <div class="table-card">
            <div class="card-header">
                <span><i class="fas fa-clock me-2"></i>Recent Registrations</span>
                <a href="?page=student_records" class="btn btn-sm btn-light">View All</a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead><tr><th>Student No.</th><th>Full Name</th><th>Program</th><th>Status</th><th>Date</th></tr></thead>
                    <tbody id="recentStudents"></tbody>
                </table>
            </div>
        </div>
        <!-- Website Submissions -->
        <div class="card border-0 shadow-sm mb-3 mt-3">
            <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                <h6 class="mb-0 fw-bold" style="color:#1e293b;"><i class="fas fa-globe me-2" style="color:#2563eb;"></i>Website Submissions</h6>
                <small class="text-muted">Latest from website</small>
            </div>
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
    <div class="col-lg-4">
        <div class="section-card">
            <h6 class="mb-3"><i class="fas fa-bolt me-2 text-warning"></i>Quick Actions</h6>
            <div class="d-grid gap-2">
                <a href="?page=student_registration" class="btn btn-isnm text-start"><i class="fas fa-user-plus me-2"></i>Register New Student</a>
                <a href="?page=admissions" class="btn btn-isnm-outline text-start"><i class="fas fa-clipboard-check me-2"></i>Review Admissions</a>
                <a href="?page=appointments" class="btn btn-isnm-outline text-start"><i class="fas fa-calendar-plus me-2"></i>Book Appointment</a>
                <a href="?page=status_dashboard" class="btn btn-isnm-outline text-start"><i class="fas fa-chart-bar me-2"></i>View Status Dashboard</a>
                <button class="btn btn-isnm-outline text-start" onclick="exportCSV()"><i class="fas fa-download me-2"></i>Export Students CSV</button>
            </div>
        </div>
        <div class="section-card mt-3">
            <h6 class="mb-3"><i class="fas fa-info-circle me-2 text-primary"></i>System Info</h6>
            <small class="text-muted">
                <div><strong>School:</strong> Iganga School of Nursing and Midwifery</div>
                <div><strong>Role:</strong> School Secretary</div>
                <div><strong>Date:</strong> <?= date('d M Y') ?></div>
                <div><strong>Students in System:</strong> <?= $stats['total_students'] ?></div>
            </small>
        </div>
    </div>
</div>
<?php elseif ($page === 'student_registration'): ?>
<div class="section-card">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0"><i class="fas fa-user-plus me-2 text-primary"></i>Register New Student</h5>
        <a href="?page=student_records" class="btn btn-isnm-outline btn-sm"><i class="fas fa-list me-1"></i>View Records</a>
    </div>
    <form id="registerForm" onsubmit="return registerStudent(event)">
        <div class="row g-3">
            <div class="col-md-4"><label class="form-label fw-bold">Full Name <span class="text-danger">*</span></label><input type="text" class="form-control" name="full_name" required placeholder="e.g. John Doe"></div>
            <div class="col-md-4"><label class="form-label fw-bold">Other Names</label><input type="text" class="form-control" name="other_names"></div>
            <div class="col-md-4"><label class="form-label fw-bold">Date of Birth</label><input type="date" class="form-control" name="date_of_birth"></div>
            <div class="col-md-4"><label class="form-label fw-bold">Gender <span class="text-danger">*</span></label><select class="form-select" name="gender" required><option value="">Select Gender</option><option value="Male">Male</option><option value="Female">Female</option></select></div>
            <div class="col-md-4"><label class="form-label fw-bold">Phone</label><input type="tel" class="form-control" name="phone" placeholder="+256..."></div>
            <div class="col-md-4"><label class="form-label fw-bold">Email</label><input type="email" class="form-control" name="email"></div>
            <div class="col-md-4"><label class="form-label fw-bold">Nationality</label><input type="text" class="form-control" name="nationality" value="Ugandan"></div>
            <div class="col-md-4"><label class="form-label fw-bold">Address</label><input type="text" class="form-control" name="address" placeholder="Home address"></div>
            <div class="col-md-4"><label class="form-label fw-bold">Guardian Name</label><input type="text" class="form-control" name="guardian_name"></div>
            <div class="col-md-3"><label class="form-label fw-bold">Guardian Phone</label><input type="tel" class="form-control" name="guardian_phone"></div>
            <div class="col-md-3"><label class="form-label fw-bold">Emergency Contact</label><input type="text" class="form-control" name="emergency_contact" placeholder="Emergency person"></div>
            <div class="col-md-2"><label class="form-label fw-bold">Emergency Phone</label><input type="tel" class="form-control" name="emergency_phone"></div>
            <div class="col-md-4"><label class="form-label fw-bold">Program <span class="text-danger">*</span></label><select class="form-select" name="program" required><option value="">Select Program</option><?php foreach ($programs as $p): ?><option value="<?= $p ?>"><?= $p ?></option><?php endforeach; ?></select></div>
            <div class="col-md-4"><label class="form-label fw-bold">Intake <span class="text-danger">*</span></label><select class="form-select" name="intake" required><option value="">Select Intake</option><?php foreach ($intakes as $i): ?><option value="<?= $i ?>"><?= $i ?></option><?php endforeach; ?></select></div>
            <div class="col-md-4"><label class="form-label fw-bold">Admission Date</label><input type="date" class="form-control" name="admission_date" value="<?= date('Y-m-d') ?>"></div>
        </div>
        <div class="mt-4 d-flex gap-2">
            <button type="submit" class="btn btn-isnm" id="registerBtn"><i class="fas fa-save me-1"></i>Register Student</button>
            <button type="reset" class="btn btn-isnm-outline"><i class="fas fa-undo me-1"></i>Reset</button>
        </div>
        <div id="regResult" class="mt-3" style="display:none"></div>
    </form>
</div>
<?php elseif ($page === 'student_records'): ?>
<div class="section-card">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <h5 class="mb-0"><i class="fas fa-users me-2 text-primary"></i>Student Records</h5>
        <div class="d-flex gap-2 flex-wrap">
            <button class="btn btn-isnm btn-sm" onclick="exportCSV()"><i class="fas fa-download me-1"></i>Export CSV</button>
            <a href="?page=student_registration" class="btn btn-isnm btn-sm"><i class="fas fa-plus me-1"></i>Add Student</a>
        </div>
    </div>
    <div class="row g-2 mb-3">
        <div class="col-md-3"><input type="text" class="form-control form-control-sm" id="srSearch" placeholder="Search name, number, email..." oninput="searchStudentsRecords()"></div>
        <div class="col-md-2"><select class="form-select form-select-sm" id="srStatus" onchange="searchStudentsRecords()"><option value="">All Status</option><option value="new">New</option><option value="under_review">Under Review</option><option value="approved">Approved</option><option value="registered">Registered</option><option value="rejected">Rejected</option></select></div>
        <div class="col-md-2"><select class="form-select form-select-sm" id="srProgram" onchange="searchStudentsRecords()"><option value="">All Programs</option><?php foreach ($programs as $p): ?><option value="<?= $p ?>"><?= $p ?></option><?php endforeach; ?></select></div>
        <div class="col-md-2"><select class="form-select form-select-sm" id="srIntake" onchange="searchStudentsRecords()"><option value="">All Intakes</option><?php foreach ($intakes as $i): ?><option value="<?= $i ?>"><?= $i ?></option><?php endforeach; ?></select></div>
        <div class="col-md-1"><button class="btn btn-sm btn-outline-secondary w-100" onclick="searchStudentsRecords()"><i class="fas fa-search"></i></button></div>
    </div>
    <div class="table-responsive">
        <table class="table table-hover table-striped mb-0">
            <thead><tr><th>#</th><th>Student No.</th><th>Full Name</th><th>Program</th><th>Intake</th><th>Status</th><th>Requirements</th><th>Actions</th></tr></thead>
            <tbody id="recordsBody"></tbody>
        </table>
    </div>
    <div class="d-flex justify-content-between align-items-center mt-3">
        <small class="text-muted" id="recordsInfo"></small>
        <nav><ul class="pagination pagination-sm mb-0" id="recordsPagination"></ul></nav>
    </div>
</div>
<?php elseif ($page === 'student_profile'): ?>
<?php
$stud_id = (int)($_REQUEST['id'] ?? 0);
$student = null; $requirements = []; $documents = []; $profile = null;
if ($stud_id) {
    $stmt = $conn->prepare("SELECT * FROM `$staff_db`.`applicants` WHERE id = ?");
    $stmt->bind_param('i', $stud_id); $stmt->execute(); $student = $stmt->get_result()->fetch_assoc();
    if ($student) {
        $req_stmt = $conn->prepare("SELECT ar.*, COALESCE(ars.status, 'pending') as req_status, ars.completed_at FROM `$staff_db`.`admission_requirements` ar LEFT JOIN `$staff_db`.`applicant_requirement_status` ars ON ars.requirement_id = ar.id AND ars.applicant_id = ? ORDER BY ar.sort_order");
        $req_stmt->bind_param('i', $stud_id); $req_stmt->execute(); $req_res = $req_stmt->get_result();
        while ($r = $req_res->fetch_assoc()) $requirements[] = $r;
        $doc_stmt = $conn->prepare("SELECT * FROM `$staff_db`.`student_documents` WHERE applicant_id = ? ORDER BY created_at DESC");
        $doc_stmt->bind_param('i', $stud_id); $doc_stmt->execute(); $doc_res = $doc_stmt->get_result();
        while ($d = $doc_res->fetch_assoc()) $documents[] = $d;
        if ($students_conn) {
            $p_stmt = $students_conn->prepare("SELECT * FROM `$students_db`.`student_profiles` WHERE student_id = ?");
            $p_stmt->bind_param('i', $stud_id); $p_stmt->execute();
            $profile = $p_stmt->get_result()->fetch_assoc();
        }
    }
}
$req_complete = count(array_filter($requirements, fn($r) => $r['req_status'] === 'completed'));
$req_total = count($requirements);
$fee_status = $profile['fee_status'] ?? 'unpaid';
$admission_status = $student['status'] ?? 'new';
?>
<?php if (!$student): ?>
<div class="alert alert-danger">Student not found. <a href="?page=student_records">Back to Records</a></div>
<?php else: ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0"><i class="fas fa-user me-2 text-primary"></i><?= htmlspecialchars($student['full_name']) ?></h5>
    <div class="d-flex gap-2">
        <a href="?page=student_edit&id=<?= $stud_id ?>" class="btn btn-isnm btn-sm"><i class="fas fa-edit me-1"></i>Edit</a>
        <button class="btn btn-isnm-outline btn-sm" onclick="window.print()"><i class="fas fa-print me-1"></i>Print</button>
        <a href="?page=student_records" class="btn btn-isnm-outline btn-sm"><i class="fas fa-arrow-left me-1"></i>Back</a>
    </div>
</div>
<div class="row g-3">
    <div class="col-lg-4">
        <div class="section-card text-center">
            <div style="width:80px;height:80px;border-radius:50%;background:var(--isnm-navy);color:#fff;display:flex;align-items:center;justify-content:center;font-size:28px;margin:0 auto 12px">
                <?= strtoupper(substr($student['full_name'], 0, 2)) ?>
            </div>
            <h5><?= htmlspecialchars($student['full_name']) ?></h5>
            <p class="text-muted mb-1"><small><?= htmlspecialchars($student['student_number'] ?? 'N/A') ?></small></p>
            <div class="d-flex justify-content-center gap-2 mt-2 mb-3">
                <span class="badge-status badge-<?= $admission_status ?>"><?= ucfirst(str_replace('_',' ',$admission_status)) ?></span>
                <span class="badge-status badge-<?= $fee_status ?>"><?= ucfirst($fee_status) ?></span>
            </div>
            <div class="progress-req mb-2" style="height:10px">
                <div class="progress-req-bar" style="width:<?= $req_total ? round($req_complete/$req_total*100) : 0 ?>%"></div>
            </div>
            <small class="text-muted">Requirements: <?= $req_complete ?>/<?= $req_total ?> completed</small>
        </div>
        <div class="section-card">
            <h6 class="mb-3"><i class="fas fa-id-card me-2"></i>Personal Information</h6>
            <table class="table table-sm table-borderless mb-0">
                <tr><td class="text-muted" style="width:40%">Program</td><td><strong><?= htmlspecialchars($student['program'] ?? '') ?></strong></td></tr>
                <tr><td class="text-muted">Intake</td><td><?= htmlspecialchars($student['intake_period'] ?? '') ?></td></tr>
                <tr><td class="text-muted">Gender</td><td><?= htmlspecialchars($student['gender'] ?? '') ?></td></tr>
                <tr><td class="text-muted">Date of Birth</td><td><?= htmlspecialchars($student['date_of_birth'] ?? '') ?></td></tr>
                <tr><td class="text-muted">Phone</td><td><?= htmlspecialchars($student['phone'] ?? '') ?></td></tr>
                <tr><td class="text-muted">Email</td><td><?= htmlspecialchars($student['email'] ?? '') ?></td></tr>
                <tr><td class="text-muted">Address</td><td><?= htmlspecialchars($student['address'] ?? '') ?></td></tr>
                <tr><td class="text-muted">Guardian</td><td><?= htmlspecialchars($student['guardian_name'] ?? '') ?></td></tr>
                <tr><td class="text-muted">Guardian Phone</td><td><?= htmlspecialchars($student['guardian_phone'] ?? '') ?></td></tr>
                <tr><td class="text-muted">Registered</td><td><?= $student['created_at'] ?? '' ?></td></tr>
            </table>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="section-card">
            <h6 class="mb-3"><i class="fas fa-clipboard-list me-2"></i>Admission Requirements</h6>
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0">
                    <thead><tr><th>#</th><th>Requirement</th><th>Status</th><th>Completed</th><th>Action</th></tr></thead>
                    <tbody>
                    <?php foreach ($requirements as $idx => $req): ?>
                        <tr>
                            <td><?= $idx+1 ?></td>
                            <td><?= htmlspecialchars($req['name'] ?? $req['requirement_name'] ?? 'Requirement '.($idx+1)) ?></td>
                            <td><?php if ($req['req_status'] === 'completed'): ?><span class="badge bg-success"><i class="fas fa-check"></i> Completed</span><?php else: ?><span class="badge bg-warning text-dark"><i class="fas fa-clock"></i> Pending</span><?php endif; ?></td>
                            <td><small class="text-muted"><?= $req['completed_at'] ?? '-' ?></small></td>
                            <td><?php if ($req['req_status'] !== 'completed'): ?><button class="btn btn-sm btn-success" onclick="setReqStatus(<?= $stud_id ?>, <?= $req['id'] ?>, 'completed')"><i class="fas fa-check"></i></button><?php else: ?><button class="btn btn-sm btn-outline-warning" onclick="setReqStatus(<?= $stud_id ?>, <?= $req['id'] ?>, 'pending')"><i class="fas fa-undo"></i></button><?php endif; ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($requirements)): ?><tr><td colspan="5" class="text-center text-muted py-3">No requirements defined in system</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="section-card">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="mb-0"><i class="fas fa-file-alt me-2"></i>Documents (<?= count($documents) ?>)</h6>
                <button class="btn btn-isnm btn-sm" onclick="showUploadDocModal(<?= $stud_id ?>)"><i class="fas fa-upload me-1"></i>Upload</button>
            </div>
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0">
                    <thead><tr><th>Title</th><th>Category</th><th>Type</th><th>Uploaded</th><th>Actions</th></tr></thead>
                    <tbody>
                    <?php foreach ($documents as $doc): ?>
                        <tr>
                            <td><?= htmlspecialchars($doc['title']) ?></td>
                            <td><span class="badge bg-secondary"><?= htmlspecialchars($doc['category'] ?? 'general') ?></span></td>
                            <td><?= htmlspecialchars($doc['file_type'] ?? '') ?></td>
                            <td><small><?= $doc['created_at'] ?></small></td>
                            <td>
                                <?php if (!empty($doc['file_path'])): ?><a href="/ISNM/<?= htmlspecialchars($doc['file_path']) ?>" target="_blank" class="btn btn-sm btn-outline-primary"><i class="fas fa-eye"></i></a><?php endif; ?>
                                <button class="btn btn-sm btn-outline-success" onclick="verifyDoc(<?= $doc['id'] ?>,'verified')"><i class="fas fa-check"></i></button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($documents)): ?><tr><td colspan="5" class="text-center text-muted py-3">No documents uploaded</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php if ($profile): ?>
        <div class="section-card">
            <h6 class="mb-3"><i class="fas fa-money-bill me-2"></i>Fee Status</h6>
            <div class="row">
                <div class="col-md-4"><strong>Total Fees:</strong> UGX <?= number_format($profile['total_fees'] ?? 0) ?></div>
                <div class="col-md-4"><strong>Amount Paid:</strong> UGX <?= number_format($profile['amount_paid'] ?? 0) ?></div>
                <div class="col-md-4"><strong>Balance:</strong> UGX <?= number_format(($profile['total_fees'] ?? 0) - ($profile['amount_paid'] ?? 0)) ?></div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>
<?php elseif ($page === 'student_edit'): ?>
<?php
$edit_id = (int)($_REQUEST['id'] ?? 0);
$edit_student = null;
if ($edit_id) {
    $stmt = $conn->prepare("SELECT * FROM `$staff_db`.`applicants` WHERE id = ?");
    $stmt->bind_param('i', $edit_id); $stmt->execute(); $edit_student = $stmt->get_result()->fetch_assoc();
}
?>
<?php if (!$edit_student): ?>
<div class="alert alert-danger">Student not found. <a href="?page=student_records">Back to Records</a></div>
<?php else: ?>
<div class="section-card">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0"><i class="fas fa-edit me-2 text-primary"></i>Edit Student: <?= htmlspecialchars($edit_student['full_name']) ?></h5>
        <a href="?page=student_profile&id=<?= $edit_id ?>" class="btn btn-isnm-outline btn-sm"><i class="fas fa-arrow-left me-1"></i>Back</a>
    </div>
    <form id="editForm" onsubmit="return updateStudent(event, <?= $edit_id ?>)">
        <div class="row g-3">
            <div class="col-md-4"><label class="form-label fw-bold">Full Name *</label><input type="text" class="form-control" name="full_name" value="<?= htmlspecialchars($edit_student['full_name']) ?>" required></div>
            <div class="col-md-4"><label class="form-label fw-bold">Other Names</label><input type="text" class="form-control" name="other_names" value="<?= htmlspecialchars($edit_student['other_name'] ?? '') ?>"></div>
            <div class="col-md-4"><label class="form-label fw-bold">Date of Birth</label><input type="date" class="form-control" name="date_of_birth" value="<?= $edit_student['date_of_birth'] ?? '' ?>"></div>
            <div class="col-md-4"><label class="form-label fw-bold">Gender *</label><select class="form-select" name="gender" required><option value="Male" <?= ($edit_student['gender']??'')==='Male'?'selected':'' ?>>Male</option><option value="Female" <?= ($edit_student['gender']??'')==='Female'?'selected':'' ?>>Female</option></select></div>
            <div class="col-md-4"><label class="form-label fw-bold">Phone</label><input type="tel" class="form-control" name="phone" value="<?= htmlspecialchars($edit_student['phone'] ?? '') ?>"></div>
            <div class="col-md-4"><label class="form-label fw-bold">Email</label><input type="email" class="form-control" name="email" value="<?= htmlspecialchars($edit_student['email'] ?? '') ?>"></div>
            <div class="col-md-6"><label class="form-label fw-bold">Address</label><input type="text" class="form-control" name="address" value="<?= htmlspecialchars($edit_student['address'] ?? '') ?>"></div>
            <div class="col-md-3"><label class="form-label fw-bold">Guardian Name</label><input type="text" class="form-control" name="guardian_name" value="<?= htmlspecialchars($edit_student['guardian_name'] ?? '') ?>"></div>
            <div class="col-md-3"><label class="form-label fw-bold">Guardian Phone</label><input type="tel" class="form-control" name="guardian_phone" value="<?= htmlspecialchars($edit_student['guardian_phone'] ?? '') ?>"></div>
            <div class="col-md-4"><label class="form-label fw-bold">Program *</label><select class="form-select" name="program" required><?php foreach ($programs as $p): ?><option value="<?= $p ?>" <?= ($edit_student['program']??'') === $p ? 'selected' : '' ?>><?= $p ?></option><?php endforeach; ?></select></div>
            <div class="col-md-4"><label class="form-label fw-bold">Intake *</label><select class="form-select" name="intake" required><?php foreach ($intakes as $i): ?><option value="<?= $i ?>" <?= ($edit_student['intake_period']??'') === $i ? 'selected' : '' ?>><?= $i ?></option><?php endforeach; ?></select></div>
            <div class="col-md-4"><label class="form-label fw-bold">Status</label><select class="form-select" name="status"><?php foreach (['new'=>'New','under_review'=>'Under Review','approved'=>'Approved','registered'=>'Registered','rejected'=>'Rejected'] as $v => $l): ?><option value="<?= $v ?>" <?= ($edit_student['status']??'') === $v ? 'selected' : '' ?>><?= $l ?></option><?php endforeach; ?></select></div>
        </div>
        <div class="mt-4 d-flex gap-2">
            <button type="submit" class="btn btn-isnm" id="editBtn"><i class="fas fa-save me-1"></i>Save Changes</button>
            <a href="?page=student_profile&id=<?= $edit_id ?>" class="btn btn-isnm-outline">Cancel</a>
        </div>
        <div id="editMsg" class="mt-3" style="display:none"></div>
    </form>
</div>
<?php endif; ?>
<?php elseif ($page === 'admissions'): ?>
<div class="section-card">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <h5 class="mb-0"><i class="fas fa-clipboard-check me-2 text-primary"></i>Admissions Management</h5>
        <button class="btn btn-isnm btn-sm" onclick="loadAdmissionStats()"><i class="fas fa-chart-bar me-1"></i>Stats</button>
    </div>
    <div class="row g-2 mb-3">
        <div class="col-md-3"><input type="text" class="form-control form-control-sm" id="admSearch" placeholder="Search..." oninput="searchAdmissions()"></div>
        <div class="col-md-2"><select class="form-select form-select-sm" id="admStatus" onchange="searchAdmissions()"><option value="">All Status</option><option value="new">New</option><option value="under_review">Under Review</option><option value="approved">Approved</option><option value="rejected">Rejected</option></select></div>
        <div class="col-md-2"><select class="form-select form-select-sm" id="admProgram" onchange="searchAdmissions()"><option value="">All Programs</option><?php foreach ($programs as $p): ?><option value="<?= $p ?>"><?= $p ?></option><?php endforeach; ?></select></div>
    </div>
    <div class="table-responsive">
        <table class="table table-hover table-striped mb-0">
            <thead><tr><th>#</th><th>Student No.</th><th>Full Name</th><th>Program</th><th>Intake</th><th>Status</th><th>Applied</th><th>Actions</th></tr></thead>
            <tbody id="admBody"></tbody>
        </table>
    </div>
    <div class="d-flex justify-content-between align-items-center mt-3">
        <small class="text-muted" id="admInfo"></small>
        <nav><ul class="pagination pagination-sm mb-0" id="admPagination"></ul></nav>
    </div>
</div>
<div class="modal fade" id="admStatsModal" tabindex="-1"><div class="modal-dialog modal-lg"><div class="modal-content">
    <div class="modal-header"><h5 class="modal-title"><i class="fas fa-chart-pie me-2"></i>Admission Statistics</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body" id="admStatsBody"></div>
</div></div></div>
<?php elseif ($page === 'status_dashboard'): ?>
<div class="section-card">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0"><i class="fas fa-chart-bar me-2 text-primary"></i>Status Dashboard</h5>
        <button class="btn btn-isnm btn-sm" onclick="loadStatusOverview()"><i class="fas fa-sync me-1"></i>Refresh</button>
    </div>
    <div class="row g-3 mb-4" id="statusKpis"></div>
    <div class="table-responsive">
        <table class="table table-hover table-sm mb-0">
            <thead><tr><th>Student No.</th><th>Full Name</th><th>Program</th><th>Intake</th><th>Admission Status</th><th>Requirements</th><th>Documents</th><th>Actions</th></tr></thead>
            <tbody id="statusBody"></tbody>
        </table>
    </div>
</div>
<?php elseif ($page === 'appointments'): ?>
<div class="section-card">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0"><i class="fas fa-calendar-check me-2 text-primary"></i>Appointments</h5>
        <button class="btn btn-isnm btn-sm" onclick="showAppointmentModal()"><i class="fas fa-plus me-1"></i>New Appointment</button>
    </div>
    <div class="table-responsive">
        <table class="table table-hover table-striped mb-0">
            <thead><tr><th>Title</th><th>Date</th><th>Time</th><th>Location</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody id="apptBody"></tbody>
        </table>
    </div>
</div>
<div class="modal fade" id="apptModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
    <div class="modal-header"><h5 class="modal-title" id="apptModalTitle">New Appointment</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <form id="apptForm" onsubmit="return saveAppointment(event)">
            <input type="hidden" name="id" id="appt_id">
            <div class="mb-3"><label class="form-label fw-bold">Title *</label><input type="text" class="form-control" name="title" id="appt_title" required></div>
            <div class="mb-3"><label class="form-label fw-bold">Description</label><textarea class="form-control" name="description" id="appt_desc" rows="2"></textarea></div>
            <div class="row g-2">
                <div class="col-6 mb-3"><label class="form-label fw-bold">Date *</label><input type="date" class="form-control" name="appointment_date" id="appt_date" required></div>
                <div class="col-6 mb-3"><label class="form-label fw-bold">Time *</label><input type="time" class="form-control" name="appointment_time" id="appt_time" required></div>
            </div>
            <div class="mb-3"><label class="form-label fw-bold">Location</label><input type="text" class="form-control" name="location" id="appt_location"></div>
            <div class="mb-3"><label class="form-label fw-bold">Status</label><select class="form-select" name="status" id="appt_status"><option value="pending">Pending</option><option value="confirmed">Confirmed</option><option value="cancelled">Cancelled</option><option value="completed">Completed</option></select></div>
            <button type="submit" class="btn btn-isnm"><i class="fas fa-save me-1"></i>Save</button>
        </form>
    </div>
</div></div></div>

<?php elseif ($page === 'meetings'): ?>
<div class="section-card">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0"><i class="fas fa-handshake me-2 text-primary"></i>Meetings</h5>
        <button class="btn btn-isnm btn-sm" onclick="showMeetingModal()"><i class="fas fa-plus me-1"></i>New Meeting</button>
    </div>
    <div class="table-responsive">
        <table class="table table-hover table-striped mb-0">
            <thead><tr><th>Title</th><th>Date</th><th>Time</th><th>Duration</th><th>Location</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody id="meetBody"></tbody>
        </table>
    </div>
</div>
<div class="modal fade" id="meetModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
    <div class="modal-header"><h5 class="modal-title" id="meetModalTitle">New Meeting</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <form id="meetForm" onsubmit="return saveMeeting(event)">
            <input type="hidden" name="id" id="meet_id">
            <div class="mb-3"><label class="form-label fw-bold">Title *</label><input type="text" class="form-control" name="title" id="meet_title" required></div>
            <div class="mb-3"><label class="form-label fw-bold">Description</label><textarea class="form-control" name="description" id="meet_desc" rows="2"></textarea></div>
            <div class="row g-2">
                <div class="col-6 mb-3"><label class="form-label fw-bold">Date *</label><input type="date" class="form-control" name="meeting_date" id="meet_date" required></div>
                <div class="col-6 mb-3"><label class="form-label fw-bold">Time *</label><input type="time" class="form-control" name="meeting_time" id="meet_time" required></div>
            </div>
            <div class="row g-2">
                <div class="col-6 mb-3"><label class="form-label fw-bold">Duration (min)</label><input type="number" class="form-control" name="duration_minutes" id="meet_duration" value="60"></div>
                <div class="col-6 mb-3"><label class="form-label fw-bold">Location</label><input type="text" class="form-control" name="location" id="meet_location"></div>
            </div>
            <div class="mb-3"><label class="form-label fw-bold">Attendees</label><input type="text" class="form-control" name="attendees" id="meet_attendees" placeholder="Comma separated"></div>
            <div class="mb-3"><label class="form-label fw-bold">Status</label><select class="form-select" name="status" id="meet_status"><option value="scheduled">Scheduled</option><option value="in_progress">In Progress</option><option value="completed">Completed</option><option value="cancelled">Cancelled</option></select></div>
            <button type="submit" class="btn btn-isnm"><i class="fas fa-save me-1"></i>Save</button>
        </form>
    </div>
</div></div></div>
<?php elseif ($page === 'documents'): ?>
<div class="section-card">
    <h5 class="mb-3"><i class="fas fa-file-alt me-2 text-primary"></i>Documents</h5>
    <p class="text-muted">Manage student documents from the <a href="?page=student_records">Student Records</a> page by selecting a student profile.</p>
</div>

<?php elseif ($page === 'comms'): ?>
<div class="section-card">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0"><i class="fas fa-envelope me-2 text-primary"></i>Messages</h5>
        <button class="btn btn-isnm btn-sm" onclick="showMsgModal()"><i class="fas fa-plus me-1"></i>New Message</button>
    </div>
    <div class="table-responsive">
        <table class="table table-hover table-striped mb-0">
            <thead><tr><th>From</th><th>Subject</th><th>Message</th><th>Date</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody id="msgBody"></tbody>
        </table>
    </div>
</div>
<div class="modal fade" id="msgModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
    <div class="modal-header"><h5 class="modal-title">New Message</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <form id="msgForm" onsubmit="return sendMessage(event)">
            <div class="mb-3"><label class="form-label fw-bold">Recipient ID *</label><input type="number" class="form-control" name="recipient_id" required></div>
            <div class="mb-3"><label class="form-label fw-bold">Subject</label><input type="text" class="form-control" name="subject"></div>
            <div class="mb-3"><label class="form-label fw-bold">Message *</label><textarea class="form-control" name="message" rows="4" required></textarea></div>
            <button type="submit" class="btn btn-isnm"><i class="fas fa-paper-plane me-1"></i>Send</button>
        </form>
    </div>
</div></div></div>

<?php elseif ($page === 'requests'): ?>
<div class="section-card">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0"><i class="fas fa-clipboard-list me-2 text-primary"></i>Internal Requests</h5>
        <button class="btn btn-isnm btn-sm" onclick="showReqModal()"><i class="fas fa-plus me-1"></i>New Request</button>
    </div>
    <div class="table-responsive">
        <table class="table table-hover table-striped mb-0">
            <thead><tr><th>Type</th><th>Description</th><th>Priority</th><th>Status</th><th>Date</th><th>Actions</th></tr></thead>
            <tbody id="reqBody"></tbody>
        </table>
    </div>
</div>
<div class="modal fade" id="reqModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
    <div class="modal-header"><h5 class="modal-title">New Request</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <form id="reqForm" onsubmit="return saveRequest(event)">
            <input type="hidden" name="id" id="req_id">
            <div class="mb-3"><label class="form-label fw-bold">Request Type *</label><input type="text" class="form-control" name="request_type" id="req_type" required></div>
            <div class="mb-3"><label class="form-label fw-bold">Description *</label><textarea class="form-control" name="description" id="req_desc" rows="3" required></textarea></div>
            <div class="mb-3"><label class="form-label fw-bold">Priority</label><select class="form-select" name="priority" id="req_priority"><option value="low">Low</option><option value="medium" selected>Medium</option><option value="high">High</option></select></div>
            <button type="submit" class="btn btn-isnm"><i class="fas fa-save me-1"></i>Save</button>
        </form>
    </div>
</div></div></div>

<?php elseif ($page === 'announcements'): ?>
<div class="section-card">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0"><i class="fas fa-bullhorn me-2 text-primary"></i>Announcements</h5>
        <button class="btn btn-isnm btn-sm" onclick="showAnnouncementModal()"><i class="fas fa-plus me-1"></i>New Announcement</button>
    </div>
    <div class="table-responsive">
        <table class="table table-hover table-striped mb-0">
            <thead><tr><th>Title</th><th>Content</th><th>Audience</th><th>Date</th><th>Actions</th></tr></thead>
            <tbody id="annBody"></tbody>
        </table>
    </div>
</div>
<div class="modal fade" id="annModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
    <div class="modal-header"><h5 class="modal-title">Announcement</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <form id="annForm" onsubmit="return saveAnnouncement(event)">
            <input type="hidden" name="id" id="ann_id">
            <div class="mb-3"><label class="form-label fw-bold">Title *</label><input type="text" class="form-control" name="title" id="ann_title" required></div>
            <div class="mb-3"><label class="form-label fw-bold">Content *</label><textarea class="form-control" name="content" id="ann_content" rows="4" required></textarea></div>
            <div class="row g-2">
                <div class="col-6 mb-3"><label class="form-label fw-bold">Target Audience</label><select class="form-select" name="target_audience" id="ann_audience"><option value="all">All</option><option value="students">Students</option><option value="staff">Staff</option><option value="parents">Parents</option></select></div>
                <div class="col-6 mb-3"><label class="form-label fw-bold">Publish Date</label><input type="date" class="form-control" name="publish_date" id="ann_date" value="<?= date('Y-m-d') ?>"></div>
            </div>
            <button type="submit" class="btn btn-isnm"><i class="fas fa-save me-1"></i>Save</button>
        </form>
    </div>
</div></div></div>

<?php elseif ($page === 'contacts'): ?>
<div class="section-card">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0"><i class="fas fa-address-book me-2 text-primary"></i>Contacts</h5>
        <button class="btn btn-isnm btn-sm" onclick="showContactModal()"><i class="fas fa-plus me-1"></i>New Contact</button>
    </div>
    <div class="table-responsive">
        <table class="table table-hover table-striped mb-0">
            <thead><tr><th>Name</th><th>Email</th><th>Phone</th><th>Organization</th><th>Category</th><th>Actions</th></tr></thead>
            <tbody id="contBody"></tbody>
        </table>
    </div>
</div>
<div class="modal fade" id="contModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
    <div class="modal-header"><h5 class="modal-title">Contact</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <form id="contForm" onsubmit="return saveContact(event)">
            <input type="hidden" name="id" id="cont_id">
            <div class="mb-3"><label class="form-label fw-bold">Name *</label><input type="text" class="form-control" name="contact_name" id="cont_name" required></div>
            <div class="row g-2">
                <div class="col-6 mb-3"><label class="form-label fw-bold">Email</label><input type="email" class="form-control" name="contact_email" id="cont_email"></div>
                <div class="col-6 mb-3"><label class="form-label fw-bold">Phone</label><input type="tel" class="form-control" name="contact_phone" id="cont_phone"></div>
            </div>
            <div class="row g-2">
                <div class="col-6 mb-3"><label class="form-label fw-bold">Organization</label><input type="text" class="form-control" name="organization" id="cont_org"></div>
                <div class="col-6 mb-3"><label class="form-label fw-bold">Category</label><input type="text" class="form-control" name="category" id="cont_cat"></div>
            </div>
            <div class="mb-3"><label class="form-label fw-bold">Notes</label><textarea class="form-control" name="notes" id="cont_notes" rows="2"></textarea></div>
            <button type="submit" class="btn btn-isnm"><i class="fas fa-save me-1"></i>Save</button>
        </form>
    </div>
</div></div></div>
<?php elseif ($page === 'reports'): ?>
<div class="section-card">
    <h5 class="mb-3"><i class="fas fa-chart-pie me-2 text-primary"></i>Reports</h5>
    <div class="row g-3">
        <div class="col-md-4"><div class="card text-center p-4 border-0 shadow-sm"><i class="fas fa-users fa-3x text-primary mb-3"></i><h6>Total Students</h6><h3 class="text-primary"><?= $stats['total_students'] ?></h3><button class="btn btn-isnm btn-sm mt-2" onclick="exportCSV()"><i class="fas fa-download me-1"></i>Export</button></div></div>
        <div class="col-md-4"><div class="card text-center p-4 border-0 shadow-sm"><i class="fas fa-user-check fa-3x text-success mb-3"></i><h6>Active Students</h6><h3 class="text-success"><?= $stats['active_students'] ?></h3></div></div>
        <div class="col-md-4"><div class="card text-center p-4 border-0 shadow-sm"><i class="fas fa-hourglass-half fa-3x text-warning mb-3"></i><h6>Pending Admissions</h6><h3 class="text-warning"><?= $stats['pending_admissions'] ?></h3></div></div>
        <div class="col-md-4"><div class="card text-center p-4 border-0 shadow-sm"><i class="fas fa-exclamation-triangle fa-3x text-danger mb-3"></i><h6>Incomplete Requirements</h6><h3 class="text-danger"><?= $stats['incomplete_reqs'] ?></h3></div></div>
        <div class="col-md-4"><div class="card text-center p-4 border-0 shadow-sm"><i class="fas fa-calendar-check fa-3x text-info mb-3"></i><h6>Today's Appointments</h6><h3 class="text-info"><?= $stats['today_appointments'] ?></h3></div></div>
        <div class="col-md-4"><div class="card text-center p-4 border-0 shadow-sm"><i class="fas fa-envelope fa-3x text-secondary mb-3"></i><h6>Unread Messages</h6><h3 class="text-secondary"><?= $stats['unread_messages'] ?></h3></div></div>
    </div>
</div>
<div class="section-card mt-3">
    <h6 class="mb-3"><i class="fas fa-download me-2"></i>Export Options</h6>
    <div class="d-flex gap-2 flex-wrap">
        <button class="btn btn-isnm" onclick="exportCSV()"><i class="fas fa-file-csv me-1"></i>Export All Students (CSV)</button>
        <button class="btn btn-isnm-outline" onclick="loadIncompleteStudents()"><i class="fas fa-exclamation-circle me-1"></i>View Incomplete Requirements</button>
    </div>
    <div id="incompleteList" class="mt-3"></div>
</div>
<?php endif; ?>
    </div>
</div>
<div class="modal fade" id="uploadDocModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
    <div class="modal-header"><h5 class="modal-title"><i class="fas fa-upload me-2"></i>Upload Document</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <form id="uploadDocForm" onsubmit="return uploadDoc(event)">
            <input type="hidden" name="applicant_id" id="doc_applicant_id">
            <div class="mb-3"><label class="form-label fw-bold">Title *</label><input type="text" class="form-control" name="title" id="doc_title" required></div>
            <div class="mb-3"><label class="form-label fw-bold">Category</label><select class="form-select" name="category" id="doc_category"><option value="identification">Identification</option><option value="academic">Academic</option><option value="medical">Medical</option><option value="financial">Financial</option><option value="general">General</option></select></div>
            <div class="mb-3"><label class="form-label fw-bold">Description</label><textarea class="form-control" name="description" id="doc_desc" rows="2"></textarea></div>
            <div class="mb-3"><label class="form-label fw-bold">File *</label><input type="file" class="form-control" name="document" id="doc_file" required></div>
            <button type="submit" class="btn btn-isnm"><i class="fas fa-upload me-1"></i>Upload</button>
        </form>
    </div>
</div></div></div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
const BASE = window.location.pathname.split('?')[0];
function showMsg(msg,type='success'){const d=document.createElement('div');d.className='alert alert-'+type+' alert-dismissible fade show mt-2';d.innerHTML=msg+'<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';document.querySelector('.content-area').prepend(d);setTimeout(()=>d.remove(),5000)}
async function ajax(action,method='GET',data=null){const opts={method};if(method==='POST'&&data){if(data instanceof FormData){opts.body=data}else{opts.body=new URLSearchParams(data)}}const url=new URL(BASE,window.location.origin);url.searchParams.set('ajax',action);if(method==='GET'&&data){Object.entries(data).forEach(([k,v])=>{if(v!==undefined&&v!==null)url.searchParams.set(k,v)})}const r=await fetch(url,opts);return r.json()}
function esc(s){if(!s)return'';const d=document.createElement('div');d.textContent=s;return d.innerHTML}
function exportCSV(){const s=document.getElementById('srSearch')?.value||'';const st=document.getElementById('srStatus')?.value||'';const pr=document.getElementById('srProgram')?.value||'';const url=new URL(BASE,window.location.origin);url.searchParams.set('ajax','export_students_csv');if(s)url.searchParams.set('search',s);if(st)url.searchParams.set('status',st);if(pr)url.searchParams.set('program',pr);window.location.href=url.toString()}
if(document.getElementById('recentStudents')){ajax('search_students','GET',{search:'',page:1}).then(d=>{if(d.success){const tb=document.getElementById('recentStudents');tb.innerHTML=d.students.slice(0,8).map(s=>`<tr><td><small>${esc(s.student_number||'')}</small></td><td>${esc(s.full_name)}</td><td><small>${esc(s.program||'')}</small></td><td><span class="badge-status badge-${s.status||'new'}">${(s.status||'new').replace(/_/g,' ')}</span></td><td><small>${s.created_at||''}</small></td></tr>`).join('')||'<tr><td colspan="5" class="text-center text-muted">No students registered yet</td></tr>'}})}

function registerStudent(e){e.preventDefault();const fd=new FormData(document.getElementById('registerForm'));const btn=document.getElementById('registerBtn');btn.disabled=true;btn.innerHTML='<i class="fas fa-spinner fa-spin me-1"></i>Registering...';ajax('register_student','POST',fd).then(d=>{btn.disabled=false;btn.innerHTML='<i class="fas fa-save me-1"></i>Register Student';if(d.success){document.getElementById('regResult').style.display='block';document.getElementById('regResult').className='alert alert-success';document.getElementById('regResult').innerHTML='<strong><i class="fas fa-check-circle me-1"></i>Student Registered!</strong><hr class="my-2"><div class="small"><div><strong>Student No:</strong> '+d.student_number+'</div><div><strong>Reg No:</strong> '+d.reg_number+'</div><div><strong>Portal Username:</strong> '+d.portal_username+'</div><div><strong>Portal Password:</strong> '+d.portal_password+'</div><div class="mt-2 text-muted"><i class="fas fa-info-circle"></i> Share credentials with student. They can login at <strong>student-login.php</strong></div></div>';document.getElementById('registerForm').reset()}else{document.getElementById('regResult').style.display='block';document.getElementById('regResult').className='alert alert-danger';document.getElementById('regResult').innerHTML='<i class="fas fa-exclamation-circle me-1"></i>'+d.message}});return false}

let srPage=1;
function searchStudentsRecords(p){srPage=p||1;const s=document.getElementById('srSearch')?.value||'';const st=document.getElementById('srStatus')?.value||'';const pr=document.getElementById('srProgram')?.value||'';const it=document.getElementById('srIntake')?.value||'';ajax('search_students','GET',{search:s,status:st,program:pr,intake:it,page:srPage}).then(d=>{if(!d.success)return;const tb=document.getElementById('recordsBody');if(!tb)return;tb.innerHTML=d.students.map((s,i)=>`<tr><td>${(d.page-1)*d.per_page+i+1}</td><td><small>${esc(s.student_number||'')}</small></td><td><a href="?page=student_profile&id=${s.id}" class="text-decoration-none fw-bold">${esc(s.full_name)}</a></td><td><small>${esc(s.program||'')}</small></td><td><small>${esc(s.intake_period||'')}</small></td><td><span class="badge-status badge-${s.status||'new'}">${(s.status||'new').replace(/_/g,' ')}</span></td><td><div class="progress-req" style="width:100px"><div class="progress-req-bar" style="width:${s.req_total?Math.round(s.req_complete/s.req_total*100):0}%"></div></div><small class="text-muted">${s.req_complete||0}/${s.req_total||0}</small></td><td><a href="?page=student_profile&id=${s.id}" class="btn btn-sm btn-outline-primary"><i class="fas fa-eye"></i></a> <a href="?page=student_edit&id=${s.id}" class="btn btn-sm btn-outline-warning"><i class="fas fa-edit"></i></a> <button class="btn btn-sm btn-outline-danger" onclick="deleteStudent(${s.id},'${esc(s.full_name)}')"><i class="fas fa-trash"></i></button></td></tr>`).join('')||'<tr><td colspan="8" class="text-center text-muted py-4">No students found</td></tr>';document.getElementById('recordsInfo').textContent=`Showing ${d.students.length} of ${d.total} students`;const pg=document.getElementById('recordsPagination');pg.innerHTML='';for(let p2=1;p2<=d.total_pages;p2++){pg.innerHTML+=`<li class="page-item ${p2===d.page?'active':''}"><a class="page-link" href="#" onclick="searchStudentsRecords(${p2});return false">${p2}</a></li>`}})}
if(document.getElementById('recordsBody'))searchStudentsRecords();

function deleteStudent(id,name){if(!confirm('Delete student "'+name+'"? This cannot be undone.'))return;ajax('delete_student','POST',{id}).then(d=>{if(d.success){showMsg(d.message);searchStudentsRecords(srPage)}else showMsg(d.message,'danger')})}
function setReqStatus(aid,rid,status){ajax('set_requirement_status','POST',{applicant_id:aid,requirement_id:rid,status:status}).then(d=>{if(d.success){location.reload()}else showMsg(d.message,'danger')})}
function showUploadDocModal(aid){document.getElementById('doc_applicant_id').value=aid;document.getElementById('doc_title').value='';document.getElementById('doc_file').value='';new bootstrap.Modal(document.getElementById('uploadDocModal')).show()}
function uploadDoc(e){e.preventDefault();const fd=new FormData(document.getElementById('uploadDocForm'));ajax('upload_document','POST',fd).then(d=>{if(d.success){showMsg(d.message);setTimeout(()=>location.reload(),1000)}else showMsg(d.message,'danger')});return false}
function verifyDoc(did,status){ajax('verify_document','POST',{doc_id:did,status:status}).then(d=>{if(d.success){location.reload()}else showMsg(d.message,'danger')})}
function updateStudent(e,id){e.preventDefault();const fd=new FormData(document.getElementById('editForm'));fd.append('id',id);const btn=document.getElementById('editBtn');btn.disabled=true;btn.innerHTML='<i class="fas fa-spinner fa-spin me-1"></i>Saving...';ajax('update_student','POST',fd).then(d=>{btn.disabled=false;btn.innerHTML='<i class="fas fa-save me-1"></i>Save Changes';const msg=document.getElementById('editMsg');msg.style.display='block';if(d.success){msg.className='alert alert-success';msg.innerHTML='<i class="fas fa-check-circle me-1"></i>'+d.message;setTimeout(()=>window.location.href='?page=student_profile&id='+id,1500)}else{msg.className='alert alert-danger';msg.innerHTML='<i class="fas fa-exclamation-circle me-1"></i>'+d.message}});return false}
let admPage=1;
function searchAdmissions(p){admPage=p||1;const s=document.getElementById('admSearch')?.value||'';const st=document.getElementById('admStatus')?.value||'';const pr=document.getElementById('admProgram')?.value||'';ajax('search_students','GET',{search:s,status:st,program:pr,page:admPage}).then(d=>{if(!d.success)return;const tb=document.getElementById('admBody');if(!tb)return;tb.innerHTML=d.students.map((s,i)=>`<tr><td>${(d.page-1)*d.per_page+i+1}</td><td><small>${esc(s.student_number||'')}</small></td><td><a href="?page=student_profile&id=${s.id}" class="text-decoration-none">${esc(s.full_name)}</a></td><td><small>${esc(s.program||'')}</small></td><td><small>${esc(s.intake_period||'')}</small></td><td><span class="badge-status badge-${s.status||'new'}">${(s.status||'new').replace(/_/g,' ')}</span></td><td><small>${s.created_at||''}</small></td><td>${s.status!=='approved'?`<button class="btn btn-sm btn-success" onclick="approveStudent(${s.id})"><i class="fas fa-check"></i></button> `:''}${s.status!=='rejected'?`<button class="btn btn-sm btn-danger" onclick="rejectStudent(${s.id})"><i class="fas fa-times"></i></button> `:''}<a href="?page=student_profile&id=${s.id}" class="btn btn-sm btn-outline-primary"><i class="fas fa-eye"></i></a></td></tr>`).join('')||'<tr><td colspan="8" class="text-center text-muted py-4">No applicants found</td></tr>';document.getElementById('admInfo').textContent=`${d.students.length} of ${d.total} applicants`;const pg=document.getElementById('admPagination');pg.innerHTML='';for(let p2=1;p2<=d.total_pages;p2++){pg.innerHTML+=`<li class="page-item ${p2===d.page?'active':''}"><a class="page-link" href="#" onclick="searchAdmissions(${p2});return false">${p2}</a></li>`}})}
if(document.getElementById('admBody'))searchAdmissions();
function approveStudent(id){if(!confirm('Approve this student?'))return;ajax('approve_student','POST',{id}).then(d=>{if(d.success){showMsg(d.message);searchAdmissions(admPage)}else showMsg(d.message,'danger')})}
function rejectStudent(id){if(!confirm('Reject this student?'))return;ajax('reject_student','POST',{id}).then(d=>{if(d.success){showMsg(d.message);searchAdmissions(admPage)}else showMsg(d.message,'danger')})}
function loadAdmissionStats(){ajax('get_admission_stats','GET').then(d=>{if(!d.success)return;const s=d.stats;let html=`<div class="row g-3">`;html+=`<div class="col-md-6"><h6>By Status</h6><table class="table table-sm"><thead><tr><th>Status</th><th>Count</th></tr></thead><tbody>`;for(const[k,v]of Object.entries(s.by_status)){html+=`<tr><td><span class="badge-status badge-${k}">${k.replace(/_/g,' ')}</span></td><td><strong>${v}</strong></td></tr>`}html+=`</tbody></table><p class="fw-bold mt-2">Total: ${s.total}</p></div>`;html+=`<div class="col-md-6"><h6>By Program</h6><table class="table table-sm"><thead><tr><th>Program</th><th>Count</th></tr></thead><tbody>`;s.by_program.forEach(p=>{html+=`<tr><td>${esc(p.program)}</td><td><strong>${p.cnt}</strong></td></tr>`});html+=`</tbody></table></div></div>`;document.getElementById('admStatsBody').innerHTML=html;new bootstrap.Modal(document.getElementById('admStatsModal')).show()})}

function loadStatusOverview(){ajax('get_status_overview','GET').then(d=>{if(!d.success)return;const students=d.students;const counts={new:0,under_review:0,approved:0,registered:0,rejected:0};students.forEach(s=>{counts[s.admission_status]=(counts[s.admission_status]||0)+1});document.getElementById('statusKpis').innerHTML=Object.entries(counts).map(([k,v])=>`<div class="col"><div class="stat-card text-center"><div class="stat-value">${v}</div><div class="stat-label"><span class="badge-status badge-${k}">${k.replace(/_/g,' ')}</span></div></div></div>`).join('');const tb=document.getElementById('statusBody');tb.innerHTML=students.map(s=>{const pct=s.req_total?Math.round(s.req_complete/s.req_total*100):0;return`<tr><td><small>${esc(s.student_number||'')}</small></td><td><a href="?page=student_profile&id=${s.id}" class="text-decoration-none">${esc(s.full_name)}</a></td><td><small>${esc(s.program||'')}</small></td><td><small>${esc(s.intake_period||'')}</small></td><td><span class="badge-status badge-${s.admission_status||'new'}">${(s.admission_status||'new').replace(/_/g,' ')}</span></td><td><div class="progress-req" style="width:80px"><div class="progress-req-bar" style="width:${pct}%"></div></div><small>${s.req_complete||0}/${s.req_total||0}</small></td><td>${s.doc_count||0}</td><td><a href="?page=student_profile&id=${s.id}" class="btn btn-sm btn-outline-primary"><i class="fas fa-eye"></i></a></td></tr>`}).join('')})}
if(document.getElementById('statusBody'))loadStatusOverview();
function loadIncompleteStudents(){ajax('get_students_incomplete','GET').then(d=>{if(!d.success)return;const el=document.getElementById('incompleteList');el.innerHTML=`<table class="table table-sm table-hover"><thead><tr><th>Student No.</th><th>Name</th><th>Program</th><th>Status</th><th>Requirements</th><th>Action</th></tr></thead><tbody>`+d.students.map(s=>`<tr><td>${esc(s.student_number||'')}</td><td>${esc(s.full_name)}</td><td>${esc(s.program||'')}</td><td><span class="badge-status badge-${s.status||'new'}">${(s.status||'new').replace(/_/g,' ')}</span></td><td><div class="progress-req" style="width:80px"><div class="progress-req-bar" style="width:${s.req_total?Math.round(s.req_complete/s.req_total*100):0}%"></div></div>${s.req_complete}/${s.req_total}</td><td><a href="?page=student_profile&id=${s.id}" class="btn btn-sm btn-isnm">View</a></td></tr>`).join('')+
`</tbody></table>`})}
function loadAppointments(){ajax('get_appointments','GET').then(d=>{if(!d.success)return;const tb=document.getElementById('apptBody');if(!tb)return;tb.innerHTML=d.appointments.map(a=>`<tr><td>${esc(a.title)}</td><td>${a.appointment_date}</td><td>${a.appointment_time}</td><td>${esc(a.location||'')}</td><td><span class="badge bg-${a.status==='confirmed'?'success':a.status==='cancelled'?'danger':a.status==='completed'?'info':'warning'}">${a.status}</span></td><td><button class="btn btn-sm btn-outline-warning" onclick='editAppointment(${JSON.stringify(a).replace(/'/g,"\\'")})'><i class="fas fa-edit"></i></button> <button class="btn btn-sm btn-outline-danger" onclick="deleteAppointment(${a.id})"><i class="fas fa-trash"></i></button></td></tr>`).join('')||'<tr><td colspan="6" class="text-center text-muted">No appointments</td></tr>'})}
function showAppointmentModal(a){document.getElementById('apptModalTitle').textContent=a?'Edit Appointment':'New Appointment';document.getElementById('appt_id').value=a?.id||'';document.getElementById('appt_title').value=a?.title||'';document.getElementById('appt_desc').value=a?.description||'';document.getElementById('appt_date').value=a?.appointment_date||'';document.getElementById('appt_time').value=a?.appointment_time||'';document.getElementById('appt_location').value=a?.location||'';document.getElementById('appt_status').value=a?.status||'pending';new bootstrap.Modal(document.getElementById('apptModal')).show()}
function editAppointment(a){showAppointmentModal(a)}
function saveAppointment(e){e.preventDefault();const fd=new FormData(document.getElementById('apptForm'));ajax('save_appointment','POST',fd).then(d=>{if(d.success){bootstrap.Modal.getInstance(document.getElementById('apptModal')).hide();showMsg(d.message);loadAppointments()}else showMsg(d.message,'danger')});return false}
function deleteAppointment(id){if(!confirm('Delete?'))return;ajax('delete_appointment','POST',{id}).then(d=>{if(d.success){showMsg(d.message);loadAppointments()}})}
if(document.getElementById('apptBody'))loadAppointments();

function loadMeetings(){ajax('get_meetings','GET').then(d=>{if(!d.success)return;const tb=document.getElementById('meetBody');if(!tb)return;tb.innerHTML=d.meetings.map(m=>`<tr><td>${esc(m.title)}</td><td>${m.meeting_date}</td><td>${m.meeting_time}</td><td>${m.duration_minutes}min</td><td>${esc(m.location||'')}</td><td><span class="badge bg-${m.status==='completed'?'success':m.status==='cancelled'?'danger':m.status==='in_progress'?'warning':'info'}">${m.status}</span></td><td><button class="btn btn-sm btn-outline-warning" onclick='editMeeting(${JSON.stringify(m).replace(/'/g,"\\'")})'><i class="fas fa-edit"></i></button> <button class="btn btn-sm btn-outline-danger" onclick="deleteMeeting(${m.id})"><i class="fas fa-trash"></i></button></td></tr>`).join('')||'<tr><td colspan="7" class="text-center text-muted">No meetings</td></tr>'})}
function showMeetingModal(m){document.getElementById('meetModalTitle').textContent=m?'Edit Meeting':'New Meeting';document.getElementById('meet_id').value=m?.id||'';document.getElementById('meet_title').value=m?.title||'';document.getElementById('meet_desc').value=m?.description||'';document.getElementById('meet_date').value=m?.meeting_date||'';document.getElementById('meet_time').value=m?.meeting_time||'';document.getElementById('meet_duration').value=m?.duration_minutes||60;document.getElementById('meet_location').value=m?.location||'';document.getElementById('meet_attendees').value=m?.attendees||'';document.getElementById('meet_status').value=m?.status||'scheduled';new bootstrap.Modal(document.getElementById('meetModal')).show()}
function editMeeting(m){showMeetingModal(m)}
function saveMeeting(e){e.preventDefault();const fd=new FormData(document.getElementById('meetForm'));ajax('save_meeting','POST',fd).then(d=>{if(d.success){bootstrap.Modal.getInstance(document.getElementById('meetModal')).hide();showMsg(d.message);loadMeetings()}else showMsg(d.message,'danger')});return false}
function deleteMeeting(id){if(!confirm('Delete?'))return;ajax('delete_meeting','POST',{id}).then(d=>{if(d.success){showMsg(d.message);loadMeetings()}})}
if(document.getElementById('meetBody'))loadMeetings();
function loadMessages(){ajax('get_messages','GET').then(d=>{if(!d.success)return;const tb=document.getElementById('msgBody');if(!tb)return;tb.innerHTML=d.messages.map(m=>`<tr class="${m.is_read?'':'table-primary'}"><td>${esc(m.sender_name||'User #'+m.sender_id)}</td><td>${esc(m.subject||'No Subject')}</td><td><small>${esc((m.message||'').substring(0,80))}${(m.message||'').length>80?'...':''}</small></td><td><small>${m.created_at}</small></td><td>${m.is_read?'<span class="badge bg-secondary">Read</span>':'<span class="badge bg-primary">New</span>'}</td><td>${!m.is_read?`<button class="btn btn-sm btn-outline-success" onclick="markRead(${m.id})"><i class="fas fa-check"></i></button>`:''}</td></tr>`).join('')||'<tr><td colspan="6" class="text-center text-muted">No messages</td></tr>'})}
function showMsgModal(){new bootstrap.Modal(document.getElementById('msgModal')).show()}
function sendMessage(e){e.preventDefault();const fd=new FormData(document.getElementById('msgForm'));ajax('send_message','POST',fd).then(d=>{if(d.success){bootstrap.Modal.getInstance(document.getElementById('msgModal')).hide();showMsg(d.message);loadMessages();document.getElementById('msgForm').reset()}else showMsg(d.message,'danger')});return false}
function markRead(id){ajax('mark_message_read','POST',{id}).then(()=>loadMessages())}
if(document.getElementById('msgBody'))loadMessages();

function loadRequests(){ajax('get_requests','GET').then(d=>{if(!d.success)return;const tb=document.getElementById('reqBody');if(!tb)return;tb.innerHTML=d.requests.map(r=>`<tr><td>${esc(r.request_type)}</td><td><small>${esc((r.description||'').substring(0,60))}</small></td><td><span class="badge bg-${r.priority==='high'?'danger':r.priority==='medium'?'warning':'secondary'}">${r.priority}</span></td><td><span class="badge bg-${r.status==='resolved'?'success':r.status==='rejected'?'danger':r.status==='in_progress'?'info':'warning'}">${r.status}</span></td><td><small>${r.created_at}</small></td><td><button class="btn btn-sm btn-outline-warning" onclick='editRequest(${JSON.stringify(r).replace(/'/g,"\\'")})'><i class="fas fa-edit"></i></button></td></tr>`).join('')||'<tr><td colspan="6" class="text-center text-muted">No requests</td></tr>'})}
function showReqModal(r){document.getElementById('req_id').value=r?.id||'';document.getElementById('req_type').value=r?.request_type||'';document.getElementById('req_desc').value=r?.description||'';document.getElementById('req_priority').value=r?.priority||'medium';new bootstrap.Modal(document.getElementById('reqModal')).show()}
function editRequest(r){showReqModal(r)}
function saveRequest(e){e.preventDefault();const fd=new FormData(document.getElementById('reqForm'));ajax('save_request','POST',fd).then(d=>{if(d.success){bootstrap.Modal.getInstance(document.getElementById('reqModal')).hide();showMsg(d.message);loadRequests()}else showMsg(d.message,'danger')});return false}
if(document.getElementById('reqBody'))loadRequests();

function loadAnnouncements(){ajax('get_announcements','GET').then(d=>{if(!d.success)return;const tb=document.getElementById('annBody');if(!tb)return;tb.innerHTML=d.announcements.map(a=>`<tr><td>${esc(a.title)}</td><td><small>${esc((a.content||'').substring(0,80))}</small></td><td><span class="badge bg-info">${a.target_audience}</span></td><td>${a.publish_date}</td><td><button class="btn btn-sm btn-outline-warning" onclick='editAnnouncement(${JSON.stringify(a).replace(/'/g,"\\'")})'><i class="fas fa-edit"></i></button></td></tr>`).join('')||'<tr><td colspan="5" class="text-center text-muted">No announcements</td></tr>'})}
function showAnnouncementModal(a){document.getElementById('ann_id').value=a?.id||'';document.getElementById('ann_title').value=a?.title||'';document.getElementById('ann_content').value=a?.content||'';document.getElementById('ann_audience').value=a?.target_audience||'all';document.getElementById('ann_date').value=a?.publish_date||'';new bootstrap.Modal(document.getElementById('annModal')).show()}
function editAnnouncement(a){showAnnouncementModal(a)}
function saveAnnouncement(e){e.preventDefault();const fd=new FormData(document.getElementById('annForm'));ajax('save_announcement','POST',fd).then(d=>{if(d.success){bootstrap.Modal.getInstance(document.getElementById('annModal')).hide();showMsg(d.message);loadAnnouncements()}else showMsg(d.message,'danger')});return false}
if(document.getElementById('annBody'))loadAnnouncements();

function loadContacts(){ajax('get_contacts','GET').then(d=>{if(!d.success)return;const tb=document.getElementById('contBody');if(!tb)return;tb.innerHTML=d.contacts.map(c=>`<tr><td>${esc(c.contact_name)}</td><td>${esc(c.contact_email||'')}</td><td>${esc(c.contact_phone||'')}</td><td>${esc(c.organization||'')}</td><td>${esc(c.category||'')}</td><td><button class="btn btn-sm btn-outline-warning" onclick='editContact(${JSON.stringify(c).replace(/'/g,"\\'")})'><i class="fas fa-edit"></i></button></td></tr>`).join('')||'<tr><td colspan="6" class="text-center text-muted">No contacts</td></tr>'})}
function showContactModal(c){document.getElementById('cont_id').value=c?.id||'';document.getElementById('cont_name').value=c?.contact_name||'';document.getElementById('cont_email').value=c?.contact_email||'';document.getElementById('cont_phone').value=c?.contact_phone||'';document.getElementById('cont_org').value=c?.organization||'';document.getElementById('cont_cat').value=c?.category||'';document.getElementById('cont_notes').value=c?.notes||'';new bootstrap.Modal(document.getElementById('contModal')).show()}
function editContact(c){showContactModal(c)}
function saveContact(e){e.preventDefault();const fd=new FormData(document.getElementById('contForm'));ajax('save_contact','POST',fd).then(d=>{if(d.success){bootstrap.Modal.getInstance(document.getElementById('contModal')).hide();showMsg(d.message);loadContacts()}else showMsg(d.message,'danger')});return false}
if(document.getElementById('contBody'))loadContacts();
</script>
</body>
</html>
