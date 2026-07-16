<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
require_once __DIR__ . '/../includes/enterprise_auth.php';
require_once __DIR__ . '/../includes/website_submissions_widget.php';
require_once __DIR__ . '/../includes/notification_helper.php';
require_once __DIR__ . '/../includes/secretary_requirements_handler.php';
$ctx = bootstrapStaffDashboard(['school secretary', 'secretary']);
$conn = $ctx['staff'];
$students_conn = $ctx['students'] ?? null;
$user = $ctx['user'];
$user_id = (int)($user['id'] ?? 0);
$user_name = $user['full_name'] ?? 'School Secretary';
$students_db = defined('STUDENTS_DB_NAME') ? STUDENTS_DB_NAME : 'igangaschool_students';
$staff_db = defined('STAFF_DB_NAME') ? STAFF_DB_NAME : 'igangaschool_staffs';
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    "CREATE TABLE IF NOT EXISTS `$staff_db`.`secretary_correspondence` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `user_id` INT NOT NULL,
        `type` ENUM('incoming','outgoing') NOT NULL DEFAULT 'outgoing',
        `subject` VARCHAR(255) NOT NULL,
        `content` TEXT,
        `recipient_name` VARCHAR(255),
        `recipient_email` VARCHAR(255),
        `recipient_phone` VARCHAR(50),
        `category` VARCHAR(100),
        `status` ENUM('draft','sent','received','archived') DEFAULT 'draft',
        `reference_number` VARCHAR(100),
        `attachment_path` VARCHAR(500),
        `sent_date` DATE,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    "CREATE TABLE IF NOT EXISTS `$staff_db`.`secretary_meeting_agenda` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `meeting_id` INT NOT NULL,
        `topic` VARCHAR(500) NOT NULL,
        `presenter` VARCHAR(255),
        `duration_minutes` INT DEFAULT 0,
        `display_order` INT DEFAULT 0,
        `notes` TEXT,
        `status` ENUM('pending','discussed','deferred','cancelled') DEFAULT 'pending',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    "CREATE TABLE IF NOT EXISTS `$staff_db`.`secretary_meeting_action_items` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `meeting_id` INT NOT NULL,
        `agenda_id` INT DEFAULT NULL,
        `action` TEXT NOT NULL,
        `assigned_to` VARCHAR(255),
        `assigned_to_id` INT DEFAULT NULL,
        `due_date` DATE,
        `priority` ENUM('low','medium','high','critical') DEFAULT 'medium',
        `status` ENUM('open','in_progress','completed','overdue') DEFAULT 'open',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `completed_at` TIMESTAMP NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    "CREATE TABLE IF NOT EXISTS `$staff_db`.`secretary_official_documents` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `user_id` INT NOT NULL,
        `doc_type` ENUM('letter','memo','circular','notice','report','minutes','certificate','form','proposal','other') DEFAULT 'letter',
        `title` VARCHAR(500) NOT NULL,
        `reference_number` VARCHAR(100),
        `subject` VARCHAR(500),
        `content` LONGTEXT,
        `department` VARCHAR(255),
        `category` VARCHAR(100),
        `status` ENUM('draft','review','approved','published','archived','rejected') DEFAULT 'draft',
        `priority` ENUM('low','medium','high','urgent') DEFAULT 'medium',
        `recipient_name` VARCHAR(255),
        `recipient_organization` VARCHAR(255),
        `file_path` VARCHAR(500),
        `file_name` VARCHAR(255),
        `file_size` INT DEFAULT 0,
        `mime_type` VARCHAR(100),
        `is_confidential` TINYINT(1) DEFAULT 0,
        `reviewed_by` INT DEFAULT NULL,
        `reviewed_at` TIMESTAMP NULL,
        `approved_by` INT DEFAULT NULL,
        `approved_at` TIMESTAMP NULL,
        `published_by` INT DEFAULT NULL,
        `published_at` TIMESTAMP NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
];
foreach ($migrations as $sql) { @$conn->query($sql); }

// Add minutes and outcome columns to secretary_meetings if not exist
$meetingCols = $conn->query("SHOW COLUMNS FROM `$staff_db`.`secretary_meetings` LIKE 'minutes'");
if ($meetingCols && $meetingCols->num_rows === 0) {
    @$conn->query("ALTER TABLE `$staff_db`.`secretary_meetings` ADD COLUMN `minutes` TEXT AFTER `attendees`");
    @$conn->query("ALTER TABLE `$staff_db`.`secretary_meetings` ADD COLUMN `outcome` TEXT AFTER `minutes`");
    @$conn->query("ALTER TABLE `$staff_db`.`secretary_meetings` ADD COLUMN `meeting_type` VARCHAR(100) DEFAULT 'general' AFTER `location`");
    @$conn->query("ALTER TABLE `$staff_db`.`secretary_meetings` ADD COLUMN `venue` VARCHAR(255) AFTER `location`");
    @$conn->query("ALTER TABLE `$staff_db`.`secretary_meetings` ADD COLUMN `is_recurring` TINYINT(1) DEFAULT 0 AFTER `status`");
    @$conn->query("ALTER TABLE `$staff_db`.`secretary_meetings` ADD COLUMN `recurrence_pattern` VARCHAR(50) AFTER `is_recurring`");
    @$conn->query("ALTER TABLE `$staff_db`.`secretary_meetings` ADD COLUMN `organizer` VARCHAR(255) AFTER `attendees`");
    @$conn->query("ALTER TABLE `$staff_db`.`secretary_meetings` ADD COLUMN `reminder_sent` TINYINT(1) DEFAULT 0 AFTER `recurrence_pattern`");
}

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
                if (!$ins->execute()) { error_log('$ins execute failed: ' . ($ins->error ?? 'unknown')); };
                $applicant_id = $conn->insert_id;
                $rc = 0;
                $ck = $conn->query("SELECT COUNT(*) as cnt FROM `$staff_db`.`admission_requirements` WHERE is_active=1");
                if ($ck) { $rc = (int)$ck->fetch_assoc()['cnt']; }
                $track = $conn->prepare("INSERT INTO `$staff_db`.`student_admission_tracking` (student_number, full_name, program, intake, admission_date, admission_status, requirements_completed, requirements_total) VALUES (?,?,?,?,?,?,?,?)");
                $track->bind_param('ssssssii', $student_number, $full_name, $program_name, $intake, $admission_date, $status, 0, $rc);
                if (!$track->execute()) { error_log('$track execute failed: ' . ($track->error ?? 'unknown')); };
                $reqs = $conn->query("SELECT id FROM `$staff_db`.`admission_requirements` WHERE is_active=1");
                if ($reqs) {
                    $ins2 = $conn->prepare("INSERT IGNORE INTO `$staff_db`.`applicant_requirement_status` (applicant_id, requirement_id, status) VALUES (?,?,'Not Submitted')");
                    while ($rq = $reqs->fetch_assoc()) { $ins2->bind_param('ii', $applicant_id, $rq['id']); if (!$ins2->execute()) { error_log('$ins2 execute failed: ' . ($ins2->error ?? 'unknown')); }; }
                    $ins2->close();
                }
                if ($students_conn) {
                    $parts = explode(' ', $full_name);
                    $first_name = $parts[0] ?? $full_name;
                    $surname = count($parts) > 1 ? $parts[count($parts)-1] : $first_name;
                    $last_name = $parts[1] ?? $surname;
                    $year = 1; $level = 'Year 1';
                    $s_ins = $students_conn->prepare("INSERT IGNORE INTO `$students_db`.`students` (student_number, registration_number, first_name, surname, other_name, full_name, email, phone, program, course, year, level, intake_year, intake_period, date_of_birth, gender, address, guardian_name, guardian_phone, nationality, emergency_contact_name, emergency_contact_phone, status, password, is_first_login) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,'Active',?,0)");
                    $s_ins->bind_param('sssssssssssssssssssssss', $student_number, $reg_number, $first_name, $surname, $other_names, $full_name, $email, $phone, $program_name, $program_name, $year, $level, (string)date('Y'), $intake, $dob, $gender, $address, $guardian_name, $guardian_phone, $nationality, $emergency_contact, $emergency_phone, $hashed_password);
                    if (!$s_ins->execute()) { error_log('$s_ins execute failed: ' . ($s_ins->error ?? 'unknown')); };
                    $s_id = $students_conn->insert_id;
                    if ($s_id > 0) {
                        $prof = $students_conn->prepare("INSERT IGNORE INTO `$students_db`.`student_profiles` (student_id, admission_status, fee_status) VALUES (?,?,?)");
                        $prof->bind_param('iss', $s_id, $status, 'unpaid');
                        if (!$prof->execute()) { error_log('$prof execute failed: ' . ($prof->error ?? 'unknown')); };
                    }
                }
                $conn->commit();
                $response['success'] = true;
                $response['message'] = 'Student registered successfully!';
                $response['student_number'] = $student_number;
                $response['reg_number'] = $reg_number;
                $response['portal_username'] = $student_number;
                $response['portal_password'] = $temp_password;
                $nid = createNotification('New Student Registered', "Student $full_name (#$student_number) registered.", 'school-secretary.php', 'success', 'fas fa-user-graduate');
                if ($nid) notifyAllStaff($nid);
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
            if (!$count_stmt->execute()) { error_log('$count_stmt execute failed: ' . ($count_stmt->error ?? 'unknown')); };
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
            if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
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
            if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                $req_stmt = $conn->prepare("SELECT ar.*, COALESCE(ars.status, 'Not Submitted') as req_status, ars.remarks, ars.submitted_at, ars.verified_at FROM `$staff_db`.`admission_requirements` ar LEFT JOIN `$staff_db`.`applicant_requirement_status` ars ON ars.requirement_id = ar.id AND ars.applicant_id = ? WHERE ar.is_active=1 ORDER BY ar.display_order");
                $req_stmt->bind_param('i', $id);
                if (!$req_stmt->execute()) { error_log('$req_stmt execute failed: ' . ($req_stmt->error ?? 'unknown')); };
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
                $rstmt = $conn->prepare("SELECT application_number FROM `$staff_db`.`applicants` WHERE id=?");
                if ($rstmt) { $rstmt->bind_param('i', $id); $rstmt->execute(); $r = $rstmt->get_result(); } else { $r = false; }
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
                        if (!$t_stmt->execute()) { error_log('$t_stmt execute failed: ' . ($t_stmt->error ?? 'unknown')); };
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
            $rstmt = $conn->prepare("SELECT application_number FROM `$staff_db`.`applicants` WHERE id=?");
            if ($rstmt) { $rstmt->bind_param('i', $id); $rstmt->execute(); $r = $rstmt->get_result(); } else { $r = false; }
            if ($r && $r->num_rows > 0) $an = $r->fetch_assoc()['application_number'];
            $stmt = $conn->prepare("DELETE FROM `$staff_db`.`applicants` WHERE id = ?");
            $stmt->bind_param('i', $id);
            if ($stmt->execute()) {
                $d1 = $conn->prepare("DELETE FROM `$staff_db`.`applicant_requirement_status` WHERE applicant_id = ?");
                if ($d1) { $d1->bind_param('i', $id); $d1->execute(); $d1->close(); }
                $d2 = $conn->prepare("DELETE FROM `$staff_db`.`student_documents` WHERE applicant_id = ?");
                if ($d2) { $d2->bind_param('i', $id); $d2->execute(); $d2->close(); }
                if ($an) { $d3 = $conn->prepare("DELETE FROM `$staff_db`.`student_admission_tracking` WHERE student_number = ?"); if ($d3) { $d3->bind_param('s', $an); $d3->execute(); $d3->close(); } }
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
            if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
            $student = $stmt->get_result()->fetch_assoc();
            if (!$student) { $response['message'] = 'Not found'; break; }
            $req_stmt = $conn->prepare("SELECT ar.*, COALESCE(ars.status, 'Not Submitted') as req_status, ars.remarks, ars.submitted_at, ars.verified_at FROM `$staff_db`.`admission_requirements` ar LEFT JOIN `$staff_db`.`applicant_requirement_status` ars ON ars.requirement_id = ar.id AND ars.applicant_id = ? WHERE ar.is_active=1 ORDER BY ar.display_order");
            $req_stmt->bind_param('i', $id);
            if (!$req_stmt->execute()) { error_log('$req_stmt execute failed: ' . ($req_stmt->error ?? 'unknown')); };
            $req_res = $req_stmt->get_result();
            $requirements = [];
            while ($r = $req_res->fetch_assoc()) { $requirements[] = $r; }
            $doc_stmt = $conn->prepare("SELECT * FROM `$staff_db`.`student_documents` WHERE applicant_id = ? AND document_status='Active' ORDER BY uploaded_at DESC");
            $doc_stmt->bind_param('i', $id);
            if (!$doc_stmt->execute()) { error_log('$doc_stmt execute failed: ' . ($doc_stmt->error ?? 'unknown')); };
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
            $stmt = $conn->prepare("UPDATE `$staff_db`.`applicants` SET status = 'Approved' WHERE id = ?");
            if ($stmt) { $stmt->bind_param('i', $id); $stmt->execute(); $stmt->close(); }
            $an = '';
            $rstmt = $conn->prepare("SELECT application_number FROM `$staff_db`.`applicants` WHERE id=?");
            if ($rstmt) { $rstmt->bind_param('i', $id); $rstmt->execute(); $r = $rstmt->get_result(); } else { $r = false; }
            if ($r && $r->num_rows > 0) $an = $r->fetch_assoc()['application_number'];
            if ($an) { $tstmt = $conn->prepare("UPDATE `$staff_db`.`student_admission_tracking` SET admission_status = 'Approved' WHERE student_number = ?"); if ($tstmt) { $tstmt->bind_param('s', $an); $tstmt->execute(); $tstmt->close(); } }
            $response['success'] = true;
            $response['message'] = 'Student approved';
            break;

        case 'reject_student':
            $id = (int)($_POST['id'] ?? 0);
            if (!$id) { $response['message'] = 'Invalid ID'; break; }
            $stmt = $conn->prepare("UPDATE `$staff_db`.`applicants` SET status = 'Rejected' WHERE id = ?");
            if ($stmt) { $stmt->bind_param('i', $id); $stmt->execute(); $stmt->close(); }
            $an = '';
            $rstmt = $conn->prepare("SELECT application_number FROM `$staff_db`.`applicants` WHERE id=?");
            if ($rstmt) { $rstmt->bind_param('i', $id); $rstmt->execute(); $r = $rstmt->get_result(); } else { $r = false; }
            if ($r && $r->num_rows > 0) $an = $r->fetch_assoc()['application_number'];
            if ($an) { $tstmt = $conn->prepare("UPDATE `$staff_db`.`student_admission_tracking` SET admission_status = 'Rejected' WHERE student_number = ?"); if ($tstmt) { $tstmt->bind_param('s', $an); $tstmt->execute(); $tstmt->close(); } }
            $response['success'] = true;
            $response['message'] = 'Student rejected';
            break;

        case 'get_requirements':
            $applicant_id = (int)($_REQUEST['applicant_id'] ?? 0);
            if (!$applicant_id) { $response['message'] = 'Invalid ID'; break; }
            $stmt = $conn->prepare("SELECT ar.*, COALESCE(ars.status, 'Not Submitted') as req_status, ars.remarks, ars.submitted_at, ars.verified_at FROM `$staff_db`.`admission_requirements` ar LEFT JOIN `$staff_db`.`applicant_requirement_status` ars ON ars.requirement_id = ar.id AND ars.applicant_id = ? WHERE ar.is_active=1 ORDER BY ar.display_order");
            $stmt->bind_param('i', $applicant_id);
            if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
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
            if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
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
            if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
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
            $stmt = $conn->prepare("SELECT * FROM `$staff_db`.`secretary_meetings` WHERE user_id = ? ORDER BY meeting_date DESC, meeting_time DESC");
            $stmt->bind_param('i', $user_id);
            if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
            $result = $stmt->get_result();
            $items = [];
            while ($row = $result->fetch_assoc()) {
                $ag = $conn->query("SELECT COUNT(*) as cnt FROM `$staff_db`.`secretary_meeting_agenda` WHERE meeting_id=" . $row['id']);
                $row['agenda_count'] = $ag ? (int)$ag->fetch_assoc()['cnt'] : 0;
                $ai = $conn->query("SELECT COUNT(*) as cnt FROM `$staff_db`.`secretary_meeting_action_items` WHERE meeting_id=" . $row['id'] . " AND status='open'");
                $row['open_actions'] = $ai ? (int)$ai->fetch_assoc()['cnt'] : 0;
                $items[] = $row;
            }
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
            $venue = trim($_POST['venue'] ?? $location);
            $attendees = trim($_POST['attendees'] ?? '');
            $organizer = trim($_POST['organizer'] ?? '');
            $meeting_type = $_POST['meeting_type'] ?? 'general';
            $minutes = trim($_POST['minutes'] ?? '');
            $outcome = trim($_POST['outcome'] ?? '');
            $status = $_POST['status'] ?? 'scheduled';
            $is_recurring = (int)($_POST['is_recurring'] ?? 0);
            $recurrence_pattern = $_POST['recurrence_pattern'] ?? '';
            if (!$title || !$date || !$time) { $response['message'] = 'Missing fields'; break; }
            $id = (int)($_POST['id'] ?? 0);
            if ($id) {
                $stmt = $conn->prepare("UPDATE `$staff_db`.`secretary_meetings` SET title=?, description=?, meeting_date=?, meeting_time=?, duration_minutes=?, location=?, venue=?, meeting_type=?, attendees=?, organizer=?, minutes=?, outcome=?, status=?, is_recurring=?, recurrence_pattern=? WHERE id=? AND user_id=?");
                $stmt->bind_param('ssssissssssssiisi', $title, $desc, $date, $time, $duration, $location, $venue, $meeting_type, $attendees, $organizer, $minutes, $outcome, $status, $is_recurring, $recurrence_pattern, $id, $user_id);
            } else {
                $stmt = $conn->prepare("INSERT INTO `$staff_db`.`secretary_meetings` (user_id, title, description, meeting_date, meeting_time, duration_minutes, location, venue, meeting_type, attendees, organizer, minutes, outcome, status, is_recurring, recurrence_pattern) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
                $stmt->bind_param('isssisssssssssis', $user_id, $title, $desc, $date, $time, $duration, $location, $venue, $meeting_type, $attendees, $organizer, $minutes, $outcome, $status, $is_recurring, $recurrence_pattern);
            }
            if ($stmt->execute()) { $response['success'] = true; $response['message'] = 'Meeting saved'; }
            break;

        case 'delete_meeting':
            $id = (int)($_POST['id'] ?? 0);
            $d1 = $conn->prepare("DELETE FROM `$staff_db`.`secretary_meeting_action_items` WHERE meeting_id=?");
            if ($d1) { $d1->bind_param('i', $id); $d1->execute(); $d1->close(); }
            $d2 = $conn->prepare("DELETE FROM `$staff_db`.`secretary_meeting_agenda` WHERE meeting_id=?");
            if ($d2) { $d2->bind_param('i', $id); $d2->execute(); $d2->close(); }
            $stmt = $conn->prepare("DELETE FROM `$staff_db`.`secretary_meetings` WHERE id=? AND user_id=?");
            $stmt->bind_param('ii', $id, $user_id);
            if ($stmt->execute()) { $response['success'] = true; $response['message'] = 'Deleted'; }
            break;

        case 'get_meeting_agenda':
            $meeting_id = (int)($_REQUEST['meeting_id'] ?? 0);
            if (!$meeting_id) { $response['message'] = 'Invalid meeting'; break; }
            $stmt = $conn->prepare("SELECT * FROM `$staff_db`.`secretary_meeting_agenda` WHERE meeting_id = ? ORDER BY display_order ASC");
            $stmt->bind_param('i', $meeting_id);
            if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
            $items = []; $r = $stmt->get_result(); while ($row = $r->fetch_assoc()) { $items[] = $row; }
            $response['success'] = true;
            $response['agenda'] = $items;
            break;

        case 'save_meeting_agenda_item':
            $meeting_id = (int)($_POST['meeting_id'] ?? 0);
            $topic = trim($_POST['topic'] ?? '');
            $presenter = trim($_POST['presenter'] ?? '');
            $duration = (int)($_POST['duration_minutes'] ?? 0);
            $order = (int)($_POST['display_order'] ?? 0);
            $notes = trim($_POST['notes'] ?? '');
            $id = (int)($_POST['id'] ?? 0);
            if (!$meeting_id || !$topic) { $response['message'] = 'Missing fields'; break; }
            if ($id) {
                $stmt = $conn->prepare("UPDATE `$staff_db`.`secretary_meeting_agenda` SET topic=?, presenter=?, duration_minutes=?, display_order=?, notes=? WHERE id=? AND meeting_id=?");
                $stmt->bind_param('ssiisii', $topic, $presenter, $duration, $order, $notes, $id, $meeting_id);
            } else {
                $stmt = $conn->prepare("INSERT INTO `$staff_db`.`secretary_meeting_agenda` (meeting_id, topic, presenter, duration_minutes, display_order, notes) VALUES (?,?,?,?,?,?)");
                $stmt->bind_param('issiis', $meeting_id, $topic, $presenter, $duration, $order, $notes);
            }
            if ($stmt->execute()) { $response['success'] = true; $response['message'] = 'Agenda item saved'; }
            break;

        case 'delete_meeting_agenda_item':
            $id = (int)($_POST['id'] ?? 0);
            $stmt = $conn->prepare("DELETE FROM `$staff_db`.`secretary_meeting_agenda` WHERE id=?");
            $stmt->bind_param('i', $id);
            if ($stmt->execute()) { $response['success'] = true; $response['message'] = 'Deleted'; }
            break;

        case 'get_meeting_action_items':
            $meeting_id = (int)($_REQUEST['meeting_id'] ?? 0);
            if (!$meeting_id) { $response['message'] = 'Invalid meeting'; break; }
            $stmt = $conn->prepare("SELECT * FROM `$staff_db`.`secretary_meeting_action_items` WHERE meeting_id = ? ORDER BY priority DESC, due_date ASC");
            $stmt->bind_param('i', $meeting_id);
            if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
            $items = []; $r = $stmt->get_result(); while ($row = $r->fetch_assoc()) { $items[] = $row; }
            $response['success'] = true;
            $response['action_items'] = $items;
            break;

        case 'save_meeting_action_item':
            $meeting_id = (int)($_POST['meeting_id'] ?? 0);
            $agenda_id = (int)($_POST['agenda_id'] ?? 0);
            $action = trim($_POST['action'] ?? '');
            $assigned_to = trim($_POST['assigned_to'] ?? '');
            $assigned_to_id = (int)($_POST['assigned_to_id'] ?? 0);
            $due_date = $_POST['due_date'] ?? '';
            $priority = $_POST['priority'] ?? 'medium';
            $id = (int)($_POST['id'] ?? 0);
            if (!$meeting_id || !$action) { $response['message'] = 'Missing fields'; break; }
            if ($id) {
                $stmt = $conn->prepare("UPDATE `$staff_db`.`secretary_meeting_action_items` SET agenda_id=?, action=?, assigned_to=?, assigned_to_id=?, due_date=?, priority=? WHERE id=? AND meeting_id=?");
                $stmt->bind_param('ississii', $agenda_id, $action, $assigned_to, $assigned_to_id, $due_date, $priority, $id, $meeting_id);
            } else {
                $stmt = $conn->prepare("INSERT INTO `$staff_db`.`secretary_meeting_action_items` (meeting_id, agenda_id, action, assigned_to, assigned_to_id, due_date, priority) VALUES (?,?,?,?,?,?,?)");
                $stmt->bind_param('iississ', $meeting_id, $agenda_id, $action, $assigned_to, $assigned_to_id, $due_date, $priority);
            }
            if ($stmt->execute()) { $response['success'] = true; $response['message'] = 'Action item saved'; }
            break;

        case 'complete_meeting_action_item':
            $id = (int)($_POST['id'] ?? 0);
            $stmt = $conn->prepare("UPDATE `$staff_db`.`secretary_meeting_action_items` SET status='completed', completed_at=NOW() WHERE id=?");
            $stmt->bind_param('i', $id);
            if ($stmt->execute()) { $response['success'] = true; $response['message'] = 'Marked complete'; }
            break;

        case 'delete_meeting_action_item':
            $id = (int)($_POST['id'] ?? 0);
            $stmt = $conn->prepare("DELETE FROM `$staff_db`.`secretary_meeting_action_items` WHERE id=?");
            $stmt->bind_param('i', $id);
            if ($stmt->execute()) { $response['success'] = true; $response['message'] = 'Deleted'; }
            break;

        case 'get_meeting_calendar_data':
            $month = (int)($_REQUEST['month'] ?? (int)date('m'));
            $year = (int)($_REQUEST['year'] ?? (int)date('Y'));
            $start = "$year-$month-01";
            $end = date('Y-m-t', strtotime($start));
            $stmt = $conn->prepare("SELECT id, title, meeting_date, meeting_time, status, location, venue, duration_minutes FROM `$staff_db`.`secretary_meetings` WHERE user_id = ? AND meeting_date BETWEEN ? AND ? ORDER BY meeting_date ASC, meeting_time ASC");
            $stmt->bind_param('iss', $user_id, $start, $end);
            if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
            $items = []; $r = $stmt->get_result(); while ($row = $r->fetch_assoc()) { $items[] = $row; }
            $response['success'] = true;
            $response['meetings'] = $items;
            break;

        case 'get_staff_list':
            $r = $conn->query("SELECT id, full_name, email, phone, department FROM `$staff_db`.`staffs` WHERE status='Active' ORDER BY full_name ASC");
            $staff = [];
            while ($row = $r->fetch_assoc()) { $staff[] = $row; }
            $response['success'] = true;
            $response['staff'] = $staff;
            break;

        case 'get_messages':
            $stmt = $conn->prepare("SELECT m.*, s.full_name as sender_name FROM `$staff_db`.`secretary_messages` m LEFT JOIN `$staff_db`.`staffs` s ON s.id = m.sender_id WHERE m.recipient_id = ? ORDER BY m.created_at DESC");
            $stmt->bind_param('i', $user_id);
            if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
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
            $stmt = $conn->prepare("UPDATE `$staff_db`.`secretary_messages` SET is_read = 1 WHERE id = ? AND recipient_id = ?");
            if ($stmt) { $stmt->bind_param('ii', $id, $user_id); $stmt->execute(); $stmt->close(); }
            $response['success'] = true;
            break;

        case 'get_requests':
            $stmt = $conn->prepare("SELECT * FROM `$staff_db`.`secretary_requests` WHERE user_id = ? ORDER BY created_at DESC");
            $stmt->bind_param('i', $user_id);
            if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
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

        case 'delete_request':
            $id = (int)($_POST['id'] ?? 0);
            $stmt = $conn->prepare("DELETE FROM `$staff_db`.`secretary_requests` WHERE id=? AND user_id=?");
            $stmt->bind_param('ii', $id, $user_id);
            if ($stmt->execute()) { $response['success'] = true; $response['message'] = 'Request deleted'; }
            break;

        case 'get_announcements':
            $stmt = $conn->prepare("SELECT * FROM `$staff_db`.`secretary_announcements` WHERE user_id = ? ORDER BY publish_date DESC");
            $stmt->bind_param('i', $user_id);
            if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
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

        case 'delete_announcement':
            $id = (int)($_POST['id'] ?? 0);
            $stmt = $conn->prepare("DELETE FROM `$staff_db`.`secretary_announcements` WHERE id=? AND user_id=?");
            $stmt->bind_param('ii', $id, $user_id);
            if ($stmt->execute()) { $response['success'] = true; $response['message'] = 'Announcement deleted'; }
            break;

        case 'get_contacts':
            $stmt = $conn->prepare("SELECT * FROM `$staff_db`.`secretary_contacts` WHERE user_id = ? ORDER BY contact_name ASC");
            $stmt->bind_param('i', $user_id);
            if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
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

        case 'delete_contact':
            $id = (int)($_POST['id'] ?? 0);
            $stmt = $conn->prepare("DELETE FROM `$staff_db`.`secretary_contacts` WHERE id=? AND user_id=?");
            $stmt->bind_param('ii', $id, $user_id);
            if ($stmt->execute()) { $response['success'] = true; $response['message'] = 'Contact deleted'; }
            break;

        case 'get_correspondence':
            $stmt = $conn->prepare("SELECT * FROM `$staff_db`.`secretary_correspondence` WHERE user_id = ? ORDER BY created_at DESC");
            $stmt->bind_param('i', $user_id);
            if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
            $result = $stmt->get_result();
            $items = [];
            while ($row = $result->fetch_assoc()) { $items[] = $row; }
            $response['success'] = true;
            $response['correspondence'] = $items;
            break;

        case 'save_correspondence':
            $type = $_POST['type'] ?? 'outgoing';
            $subject = trim($_POST['subject'] ?? '');
            $content = trim($_POST['content'] ?? '');
            $recipient_name = trim($_POST['recipient_name'] ?? '');
            $recipient_email = trim($_POST['recipient_email'] ?? '');
            $recipient_phone = trim($_POST['recipient_phone'] ?? '');
            $category = trim($_POST['category'] ?? 'general');
            $status = $_POST['status'] ?? 'draft';
            $ref = trim($_POST['reference_number'] ?? '');
            $sent_date = $_POST['sent_date'] ?? date('Y-m-d');
            if (!$subject) { $response['message'] = 'Subject is required'; break; }
            $id = (int)($_POST['id'] ?? 0);
            if ($id) {
                $stmt = $conn->prepare("UPDATE `$staff_db`.`secretary_correspondence` SET type=?, subject=?, content=?, recipient_name=?, recipient_email=?, recipient_phone=?, category=?, status=?, reference_number=?, sent_date=? WHERE id=? AND user_id=?");
                $stmt->bind_param('ssssssssssii', $type, $subject, $content, $recipient_name, $recipient_email, $recipient_phone, $category, $status, $ref, $sent_date, $id, $user_id);
            } else {
                $stmt = $conn->prepare("INSERT INTO `$staff_db`.`secretary_correspondence` (user_id, type, subject, content, recipient_name, recipient_email, recipient_phone, category, status, reference_number, sent_date) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
                $stmt->bind_param('issssssssss', $user_id, $type, $subject, $content, $recipient_name, $recipient_email, $recipient_phone, $category, $status, $ref, $sent_date);
            }
            if ($stmt->execute()) { $response['success'] = true; $response['message'] = 'Correspondence saved'; }
            else { $response['message'] = 'Failed: ' . $conn->error; }
            break;

        case 'delete_correspondence':
            $id = (int)($_POST['id'] ?? 0);
            $stmt = $conn->prepare("DELETE FROM `$staff_db`.`secretary_correspondence` WHERE id=? AND user_id=?");
            $stmt->bind_param('ii', $id, $user_id);
            if ($stmt->execute()) { $response['success'] = true; $response['message'] = 'Deleted'; }
            break;

        case 'get_official_documents':
            $type = $_REQUEST['type'] ?? '';
            $status = $_REQUEST['status'] ?? '';
            $dept = $_REQUEST['department'] ?? '';
            $search = trim($_REQUEST['search'] ?? '');
            $where = "WHERE user_id = ?";
            $params = [$user_id];
            $types = 'i';
            if ($type) { $where .= " AND doc_type = ?"; $params[] = $type; $types .= 's'; }
            if ($status) { $where .= " AND status = ?"; $params[] = $status; $types .= 's'; }
            if ($dept) { $where .= " AND department = ?"; $params[] = $dept; $types .= 's'; }
            if ($search) { $where .= " AND (title LIKE ? OR reference_number LIKE ? OR subject LIKE ?)"; $s = "%$search%"; $params = array_merge($params, [$s, $s, $s]); $types .= 'sss'; }
            $stmt = $conn->prepare("SELECT * FROM `$staff_db`.`secretary_official_documents` $where ORDER BY created_at DESC");
            $stmt->bind_param($types, ...$params);
            if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
            $items = []; $r = $stmt->get_result(); while ($row = $r->fetch_assoc()) { $items[] = $row; }
            $response['success'] = true;
            $response['documents'] = $items;
            break;

        case 'save_official_document':
            $doc_type = $_POST['doc_type'] ?? 'letter';
            $title = trim($_POST['title'] ?? '');
            $subject = trim($_POST['subject'] ?? '');
            $reference_number = trim($_POST['reference_number'] ?? '');
            $content = trim($_POST['content'] ?? '');
            $department = trim($_POST['department'] ?? '');
            $category = trim($_POST['category'] ?? '');
            $status = $_POST['status'] ?? 'draft';
            $priority = $_POST['priority'] ?? 'medium';
            $recipient_name = trim($_POST['recipient_name'] ?? '');
            $recipient_organization = trim($_POST['recipient_organization'] ?? '');
            $is_confidential = (int)($_POST['is_confidential'] ?? 0);
            $id = (int)($_POST['id'] ?? 0);
            if (!$title) { $response['message'] = 'Title is required'; break; }
            if ($id) {
                $stmt = $conn->prepare("UPDATE `$staff_db`.`secretary_official_documents` SET doc_type=?, title=?, subject=?, reference_number=?, content=?, department=?, category=?, status=?, priority=?, recipient_name=?, recipient_organization=?, is_confidential=? WHERE id=? AND user_id=?");
                $stmt->bind_param('sssssssssssiii', $doc_type, $title, $subject, $reference_number, $content, $department, $category, $status, $priority, $recipient_name, $recipient_organization, $is_confidential, $id, $user_id);
            } else {
                if (!$reference_number) { $reference_number = 'DOC-' . date('Ymd') . '-' . str_pad(mt_rand(1,9999),4,'0',STR_PAD_LEFT); }
                $stmt = $conn->prepare("INSERT INTO `$staff_db`.`secretary_official_documents` (user_id, doc_type, title, subject, reference_number, content, department, category, status, priority, recipient_name, recipient_organization, is_confidential) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)");
                $stmt->bind_param('isssssssssssi', $user_id, $doc_type, $title, $subject, $reference_number, $content, $department, $category, $status, $priority, $recipient_name, $recipient_organization, $is_confidential);
            }
            if ($stmt->execute()) {
                if (!$id && $status === 'published') {
                    $doc_id = $id ?: $conn->insert_id;
                    $pstmt = $conn->prepare("UPDATE `$staff_db`.`secretary_official_documents` SET published_by=?, published_at=NOW() WHERE id=?");
                    if ($pstmt) { $pstmt->bind_param('ii', $user_id, $doc_id); $pstmt->execute(); $pstmt->close(); }
                }
                if (!$id && ($status === 'approved' || $status === 'review')) {
                    $doc_id = $id ?: $conn->insert_id;
                    if ($status === 'review') {
                        $rstmt = $conn->prepare("UPDATE `$staff_db`.`secretary_official_documents` SET reviewed_by=?, reviewed_at=NOW() WHERE id=?");
                        if ($rstmt) { $rstmt->bind_param('ii', $user_id, $doc_id); $rstmt->execute(); $rstmt->close(); }
                    } else {
                        $astmt = $conn->prepare("UPDATE `$staff_db`.`secretary_official_documents` SET approved_by=?, approved_at=NOW() WHERE id=?");
                        if ($astmt) { $astmt->bind_param('ii', $user_id, $doc_id); $astmt->execute(); $astmt->close(); }
                    }
                }
                $response['success'] = true;
                $response['message'] = 'Document saved';
                if (!$id) { $response['reference_number'] = $reference_number; }
            } else { $response['message'] = 'Failed: ' . $conn->error; }
            break;

        case 'publish_official_document':
            $id = (int)($_POST['id'] ?? 0);
            $new_status = $_POST['status'] ?? 'published';
            $valid = ['published','archived','rejected'];
            if (!in_array($new_status, $valid)) { $new_status = 'published'; }
            if ($new_status === 'published') {
                $stmt = $conn->prepare("UPDATE `$staff_db`.`secretary_official_documents` SET status=?, published_by=?, published_at=NOW() WHERE id=? AND user_id=?");
                $stmt->bind_param('siii', $new_status, $user_id, $id, $user_id);
            } else {
                $stmt = $conn->prepare("UPDATE `$staff_db`.`secretary_official_documents` SET status=? WHERE id=? AND user_id=?");
                $stmt->bind_param('sii', $new_status, $id, $user_id);
            }
            if ($stmt->execute()) { $response['success'] = true; $response['message'] = 'Document ' . $new_status; }
            break;

        case 'delete_official_document':
            $id = (int)($_POST['id'] ?? 0);
            $stmt = $conn->prepare("DELETE FROM `$staff_db`.`secretary_official_documents` WHERE id=? AND user_id=?");
            $stmt->bind_param('ii', $id, $user_id);
            if ($stmt->execute()) { $response['success'] = true; $response['message'] = 'Deleted'; }
            break;

        case 'get_document_stats':
            $stats = [];
            $r = @$conn->query("SELECT COUNT(*) as cnt FROM `$staff_db`.`secretary_official_documents` WHERE user_id=$user_id");
            $stats['total'] = (int)$r->fetch_assoc()['cnt'];
            $r = @$conn->query("SELECT status, COUNT(*) as cnt FROM `$staff_db`.`secretary_official_documents` WHERE user_id=$user_id GROUP BY status");
            $stats['by_status'] = []; while ($row = $r->fetch_assoc()) { $stats['by_status'][$row['status']] = (int)$row['cnt']; }
            $r = @$conn->query("SELECT doc_type, COUNT(*) as cnt FROM `$staff_db`.`secretary_official_documents` WHERE user_id=$user_id GROUP BY doc_type ORDER BY cnt DESC");
            $stats['by_type'] = []; while ($row = $r->fetch_assoc()) { $stats['by_type'][] = $row; }
            $r = @$conn->query("SELECT department, COUNT(*) as cnt FROM `$staff_db`.`secretary_official_documents` WHERE user_id=$user_id AND department!='' GROUP BY department ORDER BY cnt DESC");
            $stats['by_department'] = []; while ($row = $r->fetch_assoc()) { $stats['by_department'][] = $row; }
            $response['success'] = true;
            $response['stats'] = $stats;
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
            if (handleSecretaryRequirementsAjax($action, $conn, $user_id, $user_name, $staff_db, $response)) break;
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
.nav-tabs .nav-link{font-size:13px;padding:8px 16px}
.nav-tabs .nav-link.active{font-weight:600;border-bottom:2px solid var(--isnm-navy)}
.bg-xs{font-size:9px;padding:1px 4px;border-radius:3px}
#meetingCalendar td{vertical-align:top;font-size:12px;cursor:default}
#meetingCalendar td:hover{background:#f0f4ff}
#meetingCalendar .badge{cursor:pointer;transition:opacity .2s;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:100%}
#meetingCalendar .badge:hover{opacity:.8}
.table-card table th{white-space:nowrap}
pre{white-space:pre-wrap;word-wrap:break-word}
</style>
</head>
<body>
<?php include_once __DIR__ . '/../includes/sidebar.php'; ?>
<div class="page-content">
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
            <div class="col-md-4"><label class="form-label fw-bold">Full Name <span class="text-danger">*</span></label><input type="text" class="form-control" name="full_name" required placeholder="e.g. Reagan Otema"></div>
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
    $stmt->bind_param('i', $stud_id); if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); }; $student = $stmt->get_result()->fetch_assoc();
    if ($student) {
        $req_stmt = $conn->prepare("SELECT ar.*, COALESCE(ars.status, 'pending') as req_status, ars.completed_at FROM `$staff_db`.`admission_requirements` ar LEFT JOIN `$staff_db`.`applicant_requirement_status` ars ON ars.requirement_id = ar.id AND ars.applicant_id = ? ORDER BY ar.sort_order");
        $req_stmt->bind_param('i', $stud_id); if (!$req_stmt->execute()) { error_log('$req_stmt execute failed: ' . ($req_stmt->error ?? 'unknown')); }; $req_res = $req_stmt->get_result();
        while ($r = $req_res->fetch_assoc()) $requirements[] = $r;
        $doc_stmt = $conn->prepare("SELECT * FROM `$staff_db`.`student_documents` WHERE applicant_id = ? ORDER BY created_at DESC");
        $doc_stmt->bind_param('i', $stud_id); if (!$doc_stmt->execute()) { error_log('$doc_stmt execute failed: ' . ($doc_stmt->error ?? 'unknown')); }; $doc_res = $doc_stmt->get_result();
        while ($d = $doc_res->fetch_assoc()) $documents[] = $d;
        if ($students_conn) {
            $p_stmt = $students_conn->prepare("SELECT * FROM `$students_db`.`student_profiles` WHERE student_id = ?");
            $p_stmt->bind_param('i', $stud_id); if (!$p_stmt->execute()) { error_log('$p_stmt execute failed: ' . ($p_stmt->error ?? 'unknown')); };
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
                                <?php if (!empty($doc['file_path'])): ?><a href="<?= APP_BASE_PATH ?>/<?= htmlspecialchars($doc['file_path']) ?>" target="_blank" class="btn btn-sm btn-outline-primary"><i class="fas fa-eye"></i></a><?php endif; ?>
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
    $stmt->bind_param('i', $edit_id); if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); }; $edit_student = $stmt->get_result()->fetch_assoc();
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
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <h5 class="mb-0"><i class="fas fa-handshake me-2 text-primary"></i>Meetings</h5>
        <div class="d-flex gap-2">
            <button class="btn btn-sm btn-outline-secondary" onclick="toggleCalView()"><i class="fas fa-calendar-alt me-1"></i><span id="calToggleLabel">Calendar</span></button>
            <button class="btn btn-isnm btn-sm" onclick="showMeetingModal()"><i class="fas fa-plus me-1"></i>New Meeting</button>
        </div>
    </div>
    <ul class="nav nav-tabs mb-3" id="meetingTabs" role="tablist">
        <li class="nav-item"><button class="nav-link active" id="listTab" data-bs-toggle="tab" data-bs-target="#meetingListView" type="button">List View</button></li>
        <li class="nav-item"><button class="nav-link" id="calTab" data-bs-toggle="tab" data-bs-target="#meetingCalView" type="button">Calendar</button></li>
    </ul>
    <div class="tab-content">
        <div class="tab-pane fade show active" id="meetingListView">
            <div class="table-responsive">
                <table class="table table-hover table-striped mb-0">
                    <thead><tr><th>Title</th><th>Date</th><th>Time</th><th>Duration</th><th>Location</th><th>Agenda</th><th>Status</th><th>Actions</th></tr></thead>
                    <tbody id="meetBody"></tbody>
                </table>
            </div>
        </div>
        <div class="tab-pane fade" id="meetingCalView">
            <div id="meetingCalendar" class="p-2"></div>
        </div>
    </div>
</div>
<div id="meetingDetailPanel" class="section-card mt-3" style="display:none">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0"><i class="fas fa-info-circle me-2 text-primary"></i>Meeting Details</h5>
        <button class="btn btn-sm btn-outline-secondary" onclick="closeMeetingDetail()"><i class="fas fa-times"></i></button>
    </div>
    <ul class="nav nav-tabs mb-3" id="detailTabs" role="tablist">
        <li class="nav-item"><button class="nav-link active" id="dtlInfoTab" data-bs-toggle="tab" data-bs-target="#dtlInfo" type="button">Info</button></li>
        <li class="nav-item"><button class="nav-link" id="dtlAgendaTab" data-bs-toggle="tab" data-bs-target="#dtlAgenda" type="button">Agenda</button></li>
        <li class="nav-item"><button class="nav-link" id="dtlActionsTab" data-bs-toggle="tab" data-bs-target="#dtlActions" type="button">Action Items</button></li>
        <li class="nav-item"><button class="nav-link" id="dtlMinutesTab" data-bs-toggle="tab" data-bs-target="#dtlMinutes" type="button">Minutes</button></li>
    </ul>
    <div class="tab-content">
        <div class="tab-pane fade show active" id="dtlInfo"></div>
        <div class="tab-pane fade" id="dtlAgenda"></div>
        <div class="tab-pane fade" id="dtlActions"></div>
        <div class="tab-pane fade" id="dtlMinutes"></div>
    </div>
</div>
<div class="modal fade" id="meetModal" tabindex="-1"><div class="modal-dialog modal-lg"><div class="modal-content">
    <div class="modal-header"><h5 class="modal-title" id="meetModalTitle">New Meeting</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <form id="meetForm" onsubmit="return saveMeeting(event)">
            <input type="hidden" name="id" id="meet_id">
            <div class="row g-2">
                <div class="col-8 mb-3"><label class="form-label fw-bold">Title *</label><input type="text" class="form-control" name="title" id="meet_title" required></div>
                <div class="col-4 mb-3"><label class="form-label fw-bold">Type</label><select class="form-select" name="meeting_type" id="meet_type"><option value="general">General</option><option value="staff">Staff</option><option value="departmental">Departmental</option><option value="committee">Committee</option><option value="board">Board</option><option value="emergency">Emergency</option><option value="one_on_one">One-on-One</option></select></div>
            </div>
            <div class="mb-3"><label class="form-label fw-bold">Description</label><textarea class="form-control" name="description" id="meet_desc" rows="2"></textarea></div>
            <div class="row g-2">
                <div class="col-3 mb-3"><label class="form-label fw-bold">Date *</label><input type="date" class="form-control" name="meeting_date" id="meet_date" required></div>
                <div class="col-3 mb-3"><label class="form-label fw-bold">Time *</label><input type="time" class="form-control" name="meeting_time" id="meet_time" required></div>
                <div class="col-3 mb-3"><label class="form-label fw-bold">Duration (min)</label><input type="number" class="form-control" name="duration_minutes" id="meet_duration" value="60"></div>
                <div class="col-3 mb-3"><label class="form-label fw-bold">Status</label><select class="form-select" name="status" id="meet_status"><option value="scheduled">Scheduled</option><option value="in_progress">In Progress</option><option value="completed">Completed</option><option value="cancelled">Cancelled</option></select></div>
            </div>
            <div class="row g-2">
                <div class="col-4 mb-3"><label class="form-label fw-bold">Location</label><input type="text" class="form-control" name="location" id="meet_location"></div>
                <div class="col-4 mb-3"><label class="form-label fw-bold">Venue</label><input type="text" class="form-control" name="venue" id="meet_venue" placeholder="Room/Hall"></div>
                <div class="col-4 mb-3"><label class="form-label fw-bold">Organizer</label><input type="text" class="form-control" name="organizer" id="meet_organizer" value="<?= htmlspecialchars($user_name) ?>"></div>
            </div>
            <div class="mb-3"><label class="form-label fw-bold">Attendees</label><div class="input-group"><input type="text" class="form-control" name="attendees" id="meet_attendees" placeholder="Type names or select from staff"><button class="btn btn-outline-secondary" type="button" onclick="showStaffPicker('meet_attendees')"><i class="fas fa-user-plus"></i></button></div></div>
            <div class="row g-2">
                <div class="col-6 mb-3"><label class="form-check-label"><input type="checkbox" class="form-check-input me-1" name="is_recurring" id="meet_recurring" value="1"> Recurring meeting</label></div>
                <div class="col-6 mb-3" id="recurrenceField" style="display:none"><label class="form-label fw-bold">Pattern</label><select class="form-select" name="recurrence_pattern" id="meet_recurrence"><option value="daily">Daily</option><option value="weekly">Weekly</option><option value="biweekly">Bi-Weekly</option><option value="monthly">Monthly</option></select></div>
            </div>
            <div class="mb-3 d-none" id="meetMinutesField"><label class="form-label fw-bold">Minutes / Outcome</label><textarea class="form-control" name="minutes" id="meet_minutes" rows="4" placeholder="Record meeting minutes, decisions, and key points"></textarea></div>
            <button type="submit" class="btn btn-isnm"><i class="fas fa-save me-1"></i>Save</button>
        </form>
    </div>
</div></div></div>
<div class="modal fade" id="staffPickerModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
    <div class="modal-header"><h5 class="modal-title">Select Staff</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <input type="text" class="form-control mb-2" id="staffSearch" placeholder="Search staff..." oninput="filterStaffList()">
        <div id="staffList" class="list-group" style="max-height:300px;overflow-y:auto"></div>
    </div>
    <div class="modal-footer"><button class="btn btn-isnm" onclick="addSelectedStaff()"><i class="fas fa-check me-1"></i>Add Selected</button></div>
</div></div></div>
<div class="modal fade" id="agendaItemModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
    <div class="modal-header"><h5 class="modal-title" id="agItemTitle">Agenda Item</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <form id="agendaForm" onsubmit="return saveAgendaItem(event)">
            <input type="hidden" name="id" id="ag_item_id">
            <input type="hidden" name="meeting_id" id="ag_meeting_id">
            <div class="mb-3"><label class="form-label fw-bold">Topic *</label><input type="text" class="form-control" name="topic" id="ag_topic" required></div>
            <div class="row g-2">
                <div class="col-6 mb-3"><label class="form-label fw-bold">Presenter</label><input type="text" class="form-control" name="presenter" id="ag_presenter"></div>
                <div class="col-3 mb-3"><label class="form-label fw-bold">Duration (min)</label><input type="number" class="form-control" name="duration_minutes" id="ag_duration" value="10"></div>
                <div class="col-3 mb-3"><label class="form-label fw-bold">Order</label><input type="number" class="form-control" name="display_order" id="ag_order" value="0"></div>
            </div>
            <div class="mb-3"><label class="form-label fw-bold">Notes</label><textarea class="form-control" name="notes" id="ag_notes" rows="2"></textarea></div>
            <button type="submit" class="btn btn-isnm"><i class="fas fa-save me-1"></i>Save</button>
        </form>
    </div>
</div></div></div>
<div class="modal fade" id="actionItemModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
    <div class="modal-header"><h5 class="modal-title" id="aiItemTitle">Action Item</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <form id="actionForm" onsubmit="return saveActionItem(event)">
            <input type="hidden" name="id" id="ai_item_id">
            <input type="hidden" name="meeting_id" id="ai_meeting_id">
            <input type="hidden" name="assigned_to_id" id="ai_assigned_id">
            <div class="mb-3"><label class="form-label fw-bold">Action *</label><textarea class="form-control" name="action" id="ai_action" rows="2" required></textarea></div>
            <div class="row g-2">
                <div class="col-6 mb-3"><label class="form-label fw-bold">Assigned To</label><div class="input-group"><input type="text" class="form-control" name="assigned_to" id="ai_assigned" placeholder="Person responsible"><button class="btn btn-outline-secondary" type="button" onclick="showStaffPicker('ai_assigned','ai_assigned_id')"><i class="fas fa-user-plus"></i></button></div></div>
                <div class="col-3 mb-3"><label class="form-label fw-bold">Due Date</label><input type="date" class="form-control" name="due_date" id="ai_due"></div>
                <div class="col-3 mb-3"><label class="form-label fw-bold">Priority</label><select class="form-select" name="priority" id="ai_priority"><option value="low">Low</option><option value="medium" selected>Medium</option><option value="high">High</option><option value="critical">Critical</option></select></div>
            </div>
            <button type="submit" class="btn btn-isnm"><i class="fas fa-save me-1"></i>Save</button>
        </form>
    </div>
</div></div></div>

<?php elseif ($page === 'documents'): ?>
<div class="section-card">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <h5 class="mb-0"><i class="fas fa-file-alt me-2 text-primary"></i>Official Documents</h5>
        <button class="btn btn-isnm btn-sm" onclick="showDocModal()"><i class="fas fa-plus me-1"></i>New Document</button>
    </div>
    <div class="row g-2 mb-3">
        <div class="col-auto"><select class="form-select form-select-sm" id="docTypeFilter" onchange="loadDocuments()"><option value="">All Types</option><option value="letter">Letter</option><option value="memo">Memo</option><option value="circular">Circular</option><option value="notice">Notice</option><option value="report">Report</option><option value="minutes">Minutes</option><option value="certificate">Certificate</option><option value="form">Form</option><option value="proposal">Proposal</option><option value="other">Other</option></select></div>
        <div class="col-auto"><select class="form-select form-select-sm" id="docStatusFilter" onchange="loadDocuments()"><option value="">All Status</option><option value="draft">Draft</option><option value="review">In Review</option><option value="approved">Approved</option><option value="published">Published</option><option value="archived">Archived</option></select></div>
        <div class="col-auto"><input type="text" class="form-control form-control-sm" id="docSearch" placeholder="Search by title, ref..." onkeyup="if(event.key==='Enter')loadDocuments()"></div>
        <div class="col-auto"><button class="btn btn-sm btn-outline-primary" onclick="loadDocuments()"><i class="fas fa-search"></i></button></div>
    </div>
    <div class="table-responsive">
        <table class="table table-hover table-striped mb-0">
            <thead><tr><th>Ref #</th><th>Title</th><th>Type</th><th>Department</th><th>Status</th><th>Priority</th><th>Date</th><th>Actions</th></tr></thead>
            <tbody id="docBody"></tbody>
        </table>
    </div>
</div>
<div class="modal fade" id="docModal" tabindex="-1"><div class="modal-dialog modal-lg"><div class="modal-content">
    <div class="modal-header"><h5 class="modal-title" id="docModalTitle">New Document</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <form id="docForm" onsubmit="return saveDocument(event)">
            <input type="hidden" name="id" id="doc_id">
            <div class="row g-2">
                <div class="col-4 mb-3"><label class="form-label fw-bold">Type *</label><select class="form-select" name="doc_type" id="doc_type"><option value="letter">Letter</option><option value="memo">Memo</option><option value="circular">Circular</option><option value="notice">Notice</option><option value="report">Report</option><option value="minutes">Minutes</option><option value="certificate">Certificate</option><option value="form">Form</option><option value="proposal">Proposal</option><option value="other">Other</option></select></div>
                <div class="col-4 mb-3"><label class="form-label fw-bold">Status</label><select class="form-select" name="status" id="doc_status"><option value="draft">Draft</option><option value="review">In Review</option><option value="approved">Approved</option><option value="published">Published</option></select></div>
                <div class="col-4 mb-3"><label class="form-label fw-bold">Priority</label><select class="form-select" name="priority" id="doc_priority"><option value="low">Low</option><option value="medium" selected>Medium</option><option value="high">High</option><option value="urgent">Urgent</option></select></div>
            </div>
            <div class="mb-3"><label class="form-label fw-bold">Title *</label><input type="text" class="form-control" name="title" id="doc_title" required></div>
            <div class="mb-3"><label class="form-label fw-bold">Subject</label><input type="text" class="form-control" name="subject" id="doc_subject"></div>
            <div class="row g-2">
                <div class="col-6 mb-3"><label class="form-label fw-bold">Reference Number</label><input type="text" class="form-control" name="reference_number" id="doc_ref" placeholder="Auto-generated if empty"></div>
                <div class="col-3 mb-3"><label class="form-label fw-bold">Department</label><input type="text" class="form-control" name="department" id="doc_dept" placeholder="e.g. Academic"></div>
                <div class="col-3 mb-3"><label class="form-label fw-bold">Category</label><input type="text" class="form-control" name="category" id="doc_cat" placeholder="e.g. Policy"></div>
            </div>
            <div class="mb-3"><label class="form-label fw-bold">Content</label><textarea class="form-control" name="content" id="doc_content" rows="8" style="font-family:monospace"></textarea></div>
            <div class="row g-2">
                <div class="col-6 mb-3"><label class="form-label fw-bold">Recipient Name</label><input type="text" class="form-control" name="recipient_name" id="doc_recipient_name"></div>
                <div class="col-6 mb-3"><label class="form-label fw-bold">Recipient Organization</label><input type="text" class="form-control" name="recipient_organization" id="doc_recipient_org"></div>
            </div>
            <div class="mb-3"><label class="form-check-label"><input type="checkbox" class="form-check-input me-1" name="is_confidential" id="doc_confidential" value="1"> Confidential document</label></div>
            <button type="submit" class="btn btn-isnm"><i class="fas fa-save me-1"></i>Save</button>
        </form>
    </div>
</div></div></div>

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

<?php elseif ($page === 'correspondence'): ?>
<div class="section-card">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0"><i class="fas fa-envelope-open-text me-2 text-primary"></i>Correspondence</h5>
        <button class="btn btn-isnm btn-sm" onclick="showCorrModal()"><i class="fas fa-plus me-1"></i>New Record</button>
    </div>
    <div class="table-responsive">
        <table class="table table-hover table-striped mb-0">
            <thead><tr><th>Ref #</th><th>Subject</th><th>Type</th><th>Recipient</th><th>Category</th><th>Status</th><th>Date</th><th>Actions</th></tr></thead>
            <tbody id="corrBody"></tbody>
        </table>
    </div>
</div>
<div class="modal fade" id="corrModal" tabindex="-1"><div class="modal-dialog modal-lg"><div class="modal-content">
    <div class="modal-header"><h5 class="modal-title" id="corrModalTitle">New Correspondence</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <form id="corrForm" onsubmit="return saveCorrespondence(event)">
            <input type="hidden" name="id" id="corr_id">
            <div class="row g-2">
                <div class="col-4 mb-3"><label class="form-label fw-bold">Type</label><select class="form-select" name="type" id="corr_type"><option value="outgoing">Outgoing</option><option value="incoming">Incoming</option></select></div>
                <div class="col-4 mb-3"><label class="form-label fw-bold">Status</label><select class="form-select" name="status" id="corr_status"><option value="draft">Draft</option><option value="sent">Sent</option><option value="received">Received</option><option value="archived">Archived</option></select></div>
                <div class="col-4 mb-3"><label class="form-label fw-bold">Category</label><select class="form-select" name="category" id="corr_category"><option value="general">General</option><option value="official">Official</option><option value="academic">Academic</option><option value="financial">Financial</option><option value="disciplinary">Disciplinary</option></select></div>
            </div>
            <div class="mb-3"><label class="form-label fw-bold">Subject *</label><input type="text" class="form-control" name="subject" id="corr_subject" required></div>
            <div class="mb-3"><label class="form-label fw-bold">Content</label><textarea class="form-control" name="content" id="corr_content" rows="3"></textarea></div>
            <div class="row g-2">
                <div class="col-4 mb-3"><label class="form-label fw-bold">Recipient Name</label><input type="text" class="form-control" name="recipient_name" id="corr_recipient_name"></div>
                <div class="col-4 mb-3"><label class="form-label fw-bold">Recipient Email</label><input type="email" class="form-control" name="recipient_email" id="corr_recipient_email"></div>
                <div class="col-4 mb-3"><label class="form-label fw-bold">Recipient Phone</label><input type="tel" class="form-control" name="recipient_phone" id="corr_recipient_phone"></div>
            </div>
            <div class="row g-2">
                <div class="col-6 mb-3"><label class="form-label fw-bold">Reference Number</label><input type="text" class="form-control" name="reference_number" id="corr_ref" placeholder="Auto-generated if empty"></div>
                <div class="col-6 mb-3"><label class="form-label fw-bold">Date</label><input type="date" class="form-control" name="sent_date" id="corr_date" value="<?= date('Y-m-d') ?>"></div>
            </div>
            <button type="submit" class="btn btn-isnm"><i class="fas fa-save me-1"></i>Save</button>
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
<?php elseif ($page === 'requirements_checklist'): ?>
<div class="section-card">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0"><i class="fas fa-clipboard-check me-2 text-primary"></i>Requirements Checklist</h5>
        <div class="d-flex gap-2">
            <input type="text" class="form-control form-control-sm" id="reqSearchInput" placeholder="Search by student number..." style="width:250px" onkeyup="if(event.key==='Enter')loadStudentRequirements()">
            <button class="btn btn-isnm btn-sm" onclick="loadStudentRequirements()"><i class="fas fa-search me-1"></i>Search</button>
        </div>
    </div>
    <div id="reqChecklistResult"></div>
    <div id="reqChecklistBody" class="mt-3"></div>
</div>
<?php elseif ($page === 'store_items'): ?>
<div class="section-card">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0"><i class="fas fa-warehouse me-2 text-primary"></i>General Utilities Store</h5>
        <div class="d-flex gap-2">
            <input type="text" class="form-control form-control-sm" id="storeSearchInput" placeholder="Search items..." style="width:200px" onkeyup="if(event.key==='Enter')loadStoreItems()">
            <select class="form-select form-select-sm" id="storeCatFilter" style="width:180px" onchange="loadStoreItems()">
                <option value="">All Categories</option>
                <option value="General Utilities" selected>General Utilities</option>
                <option value="Food Store">Food Store</option>
                <option value="Matron Items">Matron Items</option>
            </select>
            <button class="btn btn-isnm btn-sm" onclick="showStoreItemModal()"><i class="fas fa-plus me-1"></i>Add Item</button>
        </div>
    </div>
    <div id="storeStatsRow" class="row g-3 mb-3"></div>
    <div class="table-responsive">
        <table class="table table-hover table-sm">
            <thead><tr><th>#</th><th>Item Name</th><th>Category</th><th>Unit</th><th>Stock</th><th>Min Level</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody id="storeItemsBody"><tr><td colspan="8" class="text-center text-muted">Loading...</td></tr></tbody>
        </table>
    </div>
</div>
<div class="modal fade" id="storeItemModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
    <div class="modal-header"><h5 class="modal-title" id="storeItemModalTitle">Add Item</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <form id="storeItemForm" onsubmit="return saveStoreItem(event)">
            <input type="hidden" name="item_id" id="si_id">
            <div class="row g-2">
                <div class="col-md-8 mb-2"><label class="form-label fw-bold">Item Name *</label><input type="text" class="form-control" name="item_name" id="si_name" required></div>
                <div class="col-md-4 mb-2"><label class="form-label fw-bold">Unit</label><input type="text" class="form-control" name="unit" id="si_unit" value="piece"></div>
            </div>
            <div class="row g-2">
                <div class="col-md-6 mb-2"><label class="form-label fw-bold">Category</label><select class="form-select" name="category" id="si_cat"><option>General Utilities</option><option>Food Store</option><option>Matron Items</option></select></div>
                <div class="col-md-6 mb-2"><label class="form-label fw-bold">Location</label><input type="text" class="form-control" name="location" id="si_location" placeholder="e.g. Store A"></div>
            </div>
            <div class="row g-2">
                <div class="col-md-4 mb-2"><label class="form-label fw-bold">Quantity</label><input type="number" class="form-control" name="quantity_in_stock" id="si_qty" min="0" value="0"></div>
                <div class="col-md-4 mb-2"><label class="form-label fw-bold">Min Level</label><input type="number" class="form-control" name="minimum_level" id="si_min" min="0" value="0"></div>
                <div class="col-md-4 mb-2"><label class="form-label fw-bold">Unit Price</label><input type="number" class="form-control" name="unit_price" id="si_price" min="0" step="0.01" value="0"></div>
            </div>
            <button type="submit" class="btn btn-isnm"><i class="fas fa-save me-1"></i>Save</button>
        </form>
    </div>
</div></div></div>
<?php elseif ($page === 'food_store'): ?>
<div class="section-card">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0"><i class="fas fa-utensils me-2 text-primary"></i>Food Store Supplies</h5>
        <button class="btn btn-isnm btn-sm" onclick="document.getElementById('storeCatFilter').value='Food Store';window.location.href='?page=store_items'"><i class="fas fa-external-link-alt me-1"></i>Manage in Store</button>
    </div>
    <div class="table-responsive">
        <table class="table table-hover table-sm">
            <thead><tr><th>#</th><th>Item</th><th>Unit</th><th>Stock</th><th>Min Level</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody id="foodStoreBody"><tr><td colspan="7" class="text-center text-muted">Loading...</td></tr></tbody>
        </table>
    </div>
</div>
<?php elseif ($page === 'matron_requisitions'): ?>
<div class="section-card">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0"><i class="fas fa-broom me-2 text-primary"></i>Matron Requisitions</h5>
        <button class="btn btn-isnm btn-sm" onclick="showRequisitionModal()"><i class="fas fa-plus me-1"></i>New Requisition</button>
    </div>
    <div class="d-flex gap-2 mb-3">
        <select class="form-select form-select-sm" id="reqStatusFilter" style="width:150px" onchange="loadRequisitions()">
            <option value="">All Status</option>
            <option value="Pending">Pending</option>
            <option value="Approved">Approved</option>
            <option value="Rejected">Rejected</option>
            <option value="Issued">Issued</option>
        </select>
    </div>
    <div class="table-responsive">
        <table class="table table-hover table-sm">
            <thead><tr><th>Req No</th><th>Item</th><th>Qty</th><th>Department</th><th>Purpose</th><th>Requested By</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody id="requisitionBody"><tr><td colspan="8" class="text-center text-muted">Loading...</td></tr></tbody>
        </table>
    </div>
</div>
<div class="modal fade" id="requisitionModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
    <div class="modal-header"><h5 class="modal-title" id="requisitionModalTitle">New Requisition</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <form id="requisitionForm" onsubmit="return saveRequisition(event)">
            <input type="hidden" name="req_id" id="rq_id">
            <div class="mb-2"><label class="form-label fw-bold">Item Name *</label><select class="form-select" name="item_name" id="rq_item" required><option value="">-- Select --</option></select></div>
            <div class="row g-2">
                <div class="col-md-6 mb-2"><label class="form-label fw-bold">Quantity *</label><input type="number" class="form-control" name="quantity_requested" id="rq_qty" min="1" value="1" required></div>
                <div class="col-md-6 mb-2"><label class="form-label fw-bold">Department</label><input type="text" class="form-control" name="department" id="rq_dept" value="Matron"></div>
            </div>
            <div class="mb-2"><label class="form-label fw-bold">Purpose</label><textarea class="form-control" name="purpose" id="rq_purpose" rows="2"></textarea></div>
            <button type="submit" class="btn btn-isnm"><i class="fas fa-save me-1"></i>Submit</button>
        </form>
    </div>
</div></div></div>
<?php elseif ($page === 'director_requirements'): ?>
<div class="section-card">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0"><i class="fas fa-user-check me-2 text-primary"></i>Director Requirements Portal</h5>
    </div>
    <div class="row g-2 mb-3">
        <div class="col-md-3"><input type="text" class="form-control form-control-sm" id="dirReqSearch" placeholder="Search by name, admission #, phone..." onkeyup="if(event.key==='Enter')loadDirectorRequirements()"></div>
        <div class="col-md-2"><select class="form-select form-select-sm" id="dirReqCategory" onchange="loadDirectorRequirements()"><option value="">All Categories</option><option value="Application">Application</option><option value="General Utilities">General Utilities</option><option value="Food Store">Food Store</option><option value="Matron Items">Matron Items</option></select></div>
        <div class="col-md-2"><select class="form-select form-select-sm" id="dirReqStatus" onchange="loadDirectorRequirements()"><option value="">All Status</option><option value="Pending">Pending</option><option value="Cleared">Cleared</option><option value="Submitted">Submitted</option><option value="Missing">Missing</option></select></div>
        <div class="col-md-2"><button class="btn btn-isnm btn-sm w-100" onclick="loadDirectorRequirements()"><i class="fas fa-search me-1"></i>Search</button></div>
    </div>
    <div class="table-responsive">
        <table class="table table-hover table-sm">
            <thead><tr><th><input type="checkbox" id="dirReqSelectAll" onchange="toggleAllDirReq(this)"></th><th>Student #</th><th>Name</th><th>Requirement</th><th>Category</th><th>Status</th><th>Cleared</th><th>Actions</th></tr></thead>
            <tbody id="dirReqBody"><tr><td colspan="8" class="text-center text-muted">Search for a student...</td></tr></tbody>
        </table>
    </div>
</div>
<?php else: ?>
<script>window.location.replace('?page=home');</script>
<?php endif; ?>
    </div>
</div><!-- /page-content -->
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

var currentMeetingId = 0;
function loadMeetings(){ajax('get_meetings','GET').then(d=>{if(!d.success)return;const tb=document.getElementById('meetBody');if(!tb)return;tb.innerHTML=d.meetings.map(m=>`<tr class="${m.id===currentMeetingId?'table-active':''}" style="cursor:pointer"><td><a href="#" onclick="event.preventDefault();showMeetingDetail(${m.id})" class="text-decoration-none fw-bold">${esc(m.title)}</a></td><td>${m.meeting_date}</td><td>${m.meeting_time}</td><td>${m.duration_minutes}min</td><td>${esc(m.venue||m.location||'')}</td><td><small>${m.agenda_count||0} items</small></td><td><span class="badge bg-${m.status==='completed'?'success':m.status==='cancelled'?'danger':m.status==='in_progress'?'warning':'info'}">${m.status}</span></td><td><button class="btn btn-sm btn-outline-warning" onclick='event.stopPropagation();editMeeting(${JSON.stringify(m).replace(/'/g,"\\'")})'><i class="fas fa-edit"></i></button> <button class="btn btn-sm btn-outline-danger" onclick="event.stopPropagation();deleteMeeting(${m.id})"><i class="fas fa-trash"></i></button></td></tr>`).join('')||'<tr><td colspan="8" class="text-center text-muted">No meetings</td></tr>'});renderMeetingCalendar()}
function showMeetingModal(m){document.getElementById('meetModalTitle').textContent=m?'Edit Meeting':'New Meeting';document.getElementById('meet_id').value=m?.id||'';document.getElementById('meet_title').value=m?.title||'';document.getElementById('meet_type').value=m?.meeting_type||'general';document.getElementById('meet_desc').value=m?.description||'';document.getElementById('meet_date').value=m?.meeting_date||'';document.getElementById('meet_time').value=m?.meeting_time||'';document.getElementById('meet_duration').value=m?.duration_minutes||60;document.getElementById('meet_location').value=m?.location||'';document.getElementById('meet_venue').value=m?.venue||'';document.getElementById('meet_organizer').value=m?.organizer||'<?= htmlspecialchars($user_name) ?>';document.getElementById('meet_attendees').value=m?.attendees||'';document.getElementById('meet_status').value=m?.status||'scheduled';document.getElementById('meet_recurring').checked=m?.is_recurring==1;document.getElementById('meet_recurrence').value=m?.recurrence_pattern||'weekly';document.getElementById('recurrenceField').style.display=m?.is_recurring?'block':'none';document.getElementById('meetMinutesField').classList.toggle('d-none',m?.status!=='completed'&&m?.status!=='in_progress');document.getElementById('meet_minutes').value=m?.minutes||'';new bootstrap.Modal(document.getElementById('meetModal')).show()}
function editMeeting(m){showMeetingModal(m)}
function saveMeeting(e){e.preventDefault();const fd=new FormData(document.getElementById('meetForm'));ajax('save_meeting','POST',fd).then(d=>{if(d.success){bootstrap.Modal.getInstance(document.getElementById('meetModal')).hide();showMsg(d.message);loadMeetings()}else showMsg(d.message,'danger')});return false}
function deleteMeeting(id){if(!confirm('Delete this meeting?'))return;ajax('delete_meeting','POST',{id}).then(d=>{if(d.success){showMsg(d.message);loadMeetings();if(currentMeetingId===id){document.getElementById('meetingDetailPanel').style.display='none';currentMeetingId=0}}})}
document.getElementById('meet_recurring').addEventListener('change',function(){document.getElementById('recurrenceField').style.display=this.checked?'block':'none'});
document.getElementById('meet_status').addEventListener('change',function(){document.getElementById('meetMinutesField').classList.toggle('d-none',this.value!=='completed'&&this.value!=='in_progress')});
if(document.getElementById('meetBody'))loadMeetings();

function showMeetingDetail(id){currentMeetingId=id;document.getElementById('meetingDetailPanel').style.display='block';loadMeetingInfo(id);loadMeetingAgenda(id);loadMeetingActionItems(id);loadMeetingMinutes(id);document.querySelector('#detailTabs button[data-bs-target="#dtlInfo"]').click()}
function closeMeetingDetail(){document.getElementById('meetingDetailPanel').style.display='none';currentMeetingId=0;loadMeetings()}
function loadMeetingInfo(id){ajax('get_meetings','GET').then(d=>{if(!d.success)return;const m=d.meetings.find(x=>x.id==id);if(!m)return;document.getElementById('dtlInfo').innerHTML=`<div class="p-3"><div class="row g-3"><div class="col-md-6"><table class="table table-sm table-borderless mb-0"><tr><td class="text-muted" style="width:35%">Title</td><td><strong>${esc(m.title)}</strong></td></tr><tr><td class="text-muted">Type</td><td>${esc(m.meeting_type||'General')}</td></tr><tr><td class="text-muted">Date</td><td>${m.meeting_date} at ${m.meeting_time}</td></tr><tr><td class="text-muted">Duration</td><td>${m.duration_minutes} minutes</td></tr><tr><td class="text-muted">Location</td><td>${esc(m.venue||m.location||'-')}</td></tr></table></div><div class="col-md-6"><table class="table table-sm table-borderless mb-0"><tr><td class="text-muted" style="width:35%">Organizer</td><td>${esc(m.organizer||'<?= htmlspecialchars($user_name) ?>')}</td></tr><tr><td class="text-muted">Attendees</td><td>${esc(m.attendees||'None specified')}</td></tr><tr><td class="text-muted">Status</td><td><span class="badge bg-${m.status==='completed'?'success':m.status==='cancelled'?'danger':m.status==='in_progress'?'warning':'info'}">${m.status}</span></td></tr><tr><td class="text-muted">Recurring</td><td>${m.is_recurring?'Yes ('+esc(m.recurrence_pattern||'')+')':'No'}</td></tr></table></div></div>${m.description?`<div class="mt-3"><h6>Description</h6><p class="text-muted">${esc(m.description)}</p></div>`:''}</div>`})}
function loadMeetingAgenda(id){ajax('get_meeting_agenda','GET',{meeting_id:id}).then(d=>{if(!d.success)return;let html='<div class="p-3"><div class="d-flex justify-content-between mb-2"><h6>Agenda Items</h6><button class="btn btn-sm btn-isnm" onclick="showAgendaItemModal('+id+')"><i class="fas fa-plus"></i> Add Item</button></div>';if(!d.agenda||!d.agenda.length){html+='<p class="text-muted">No agenda items yet</p>'}else{html+='<table class="table table-sm table-hover"><thead><tr><th>#</th><th>Topic</th><th>Presenter</th><th>Duration</th><th>Status</th><th>Actions</th></tr></thead><tbody>';d.agenda.forEach((a,i)=>{html+=`<tr><td>${a.display_order||i+1}</td><td>${esc(a.topic)}</td><td>${esc(a.presenter||'-')}</td><td>${a.duration_minutes||'-'}min</td><td><span class="badge bg-${a.status==='discussed'?'success':a.status==='deferred'?'warning':'secondary'}">${a.status}</span></td><td><button class="btn btn-sm btn-outline-warning" onclick='editAgendaItem(${JSON.stringify(a).replace(/'/g,"\\'")})'><i class="fas fa-edit"></i></button> <button class="btn btn-sm btn-outline-danger" onclick="deleteAgendaItem(${a.id})"><i class="fas fa-trash"></i></button></td></tr>`});html+='</tbody></table>'};html+='</div>';document.getElementById('dtlAgenda').innerHTML=html})}
function showAgendaItemModal(mid,item){document.getElementById('agItemTitle').textContent=item?'Edit Agenda Item':'New Agenda Item';document.getElementById('ag_item_id').value=item?.id||'';document.getElementById('ag_meeting_id').value=mid;document.getElementById('ag_topic').value=item?.topic||'';document.getElementById('ag_presenter').value=item?.presenter||'';document.getElementById('ag_duration').value=item?.duration_minutes||10;document.getElementById('ag_order').value=item?.display_order||0;document.getElementById('ag_notes').value=item?.notes||'';new bootstrap.Modal(document.getElementById('agendaItemModal')).show()}
function editAgendaItem(item){showAgendaItemModal(item.meeting_id,item)}
function saveAgendaItem(e){e.preventDefault();const fd=new FormData(document.getElementById('agendaForm'));ajax('save_meeting_agenda_item','POST',fd).then(d=>{if(d.success){bootstrap.Modal.getInstance(document.getElementById('agendaItemModal')).hide();showMsg(d.message);loadMeetingAgenda(currentMeetingId)}else showMsg(d.message,'danger')});return false}
function deleteAgendaItem(id){if(!confirm('Delete this agenda item?'))return;ajax('delete_meeting_agenda_item','POST',{id}).then(d=>{if(d.success){showMsg(d.message);loadMeetingAgenda(currentMeetingId)}})}
function loadMeetingActionItems(id){ajax('get_meeting_action_items','GET',{meeting_id:id}).then(d=>{if(!d.success)return;let html='<div class="p-3"><div class="d-flex justify-content-between mb-2"><h6>Action Items</h6><button class="btn btn-sm btn-isnm" onclick="showActionItemModal('+id+')"><i class="fas fa-plus"></i> Add Action</button></div>';if(!d.action_items||!d.action_items.length){html+='<p class="text-muted">No action items yet</p>'}else{html+='<table class="table table-sm table-hover"><thead><tr><th>Action</th><th>Assigned To</th><th>Due</th><th>Priority</th><th>Status</th><th>Actions</th></tr></thead><tbody>';d.action_items.forEach(a=>{html+=`<tr class="${a.status==='completed'?'table-success':''}"><td>${esc(a.action)}</td><td>${esc(a.assigned_to||'-')}</td><td>${a.due_date||'-'}</td><td><span class="badge bg-${a.priority==='critical'?'danger':a.priority==='high'?'warning':'info'}">${a.priority}</span></td><td><span class="badge bg-${a.status==='completed'?'success':a.status==='in_progress'?'warning':'secondary'}">${a.status}</span></td><td>${a.status!=='completed'?`<button class="btn btn-sm btn-outline-success" onclick="completeActionItem(${a.id})"><i class="fas fa-check"></i></button> `:''}<button class="btn btn-sm btn-outline-warning" onclick='editActionItem(${JSON.stringify(a).replace(/'/g,"\\'")})'><i class="fas fa-edit"></i></button> <button class="btn btn-sm btn-outline-danger" onclick="deleteActionItem(${a.id})"><i class="fas fa-trash"></i></button></td></tr>`});html+='</tbody></table>'};html+='</div>';document.getElementById('dtlActions').innerHTML=html})}
function showActionItemModal(mid,item){document.getElementById('aiItemTitle').textContent=item?'Edit Action Item':'New Action Item';document.getElementById('ai_item_id').value=item?.id||'';document.getElementById('ai_meeting_id').value=mid;document.getElementById('ai_action').value=item?.action||'';document.getElementById('ai_assigned').value=item?.assigned_to||'';document.getElementById('ai_assigned_id').value=item?.assigned_to_id||'';document.getElementById('ai_due').value=item?.due_date||'';document.getElementById('ai_priority').value=item?.priority||'medium';new bootstrap.Modal(document.getElementById('actionItemModal')).show()}
function editActionItem(item){showActionItemModal(item.meeting_id,item)}
function saveActionItem(e){e.preventDefault();const fd=new FormData(document.getElementById('actionForm'));ajax('save_meeting_action_item','POST',fd).then(d=>{if(d.success){bootstrap.Modal.getInstance(document.getElementById('actionItemModal')).hide();showMsg(d.message);loadMeetingActionItems(currentMeetingId)}else showMsg(d.message,'danger')});return false}
function completeActionItem(id){ajax('complete_meeting_action_item','POST',{id}).then(d=>{if(d.success){showMsg(d.message);loadMeetingActionItems(currentMeetingId)}})}
function deleteActionItem(id){if(!confirm('Delete this action item?'))return;ajax('delete_meeting_action_item','POST',{id}).then(d=>{if(d.success){showMsg(d.message);loadMeetingActionItems(currentMeetingId)}})}
function loadMeetingMinutes(id){ajax('get_meetings','GET').then(d=>{if(!d.success)return;const m=d.meetings.find(x=>x.id==id);if(!m)return;document.getElementById('dtlMinutes').innerHTML=`<div class="p-3"><div class="d-flex justify-content-between mb-2"><h6>Meeting Minutes</h6>${m.status==='completed'||m.status==='in_progress'?`<button class="btn btn-sm btn-isnm" onclick="editMeeting(${JSON.stringify(m).replace(/'/g,"\\'")})"><i class="fas fa-edit me-1"></i>Edit Minutes</button>`:''}</div>${m.minutes?`<div class="p-3 bg-light rounded"><pre style="white-space:pre-wrap;font-family:inherit">${esc(m.minutes)}</pre></div>`:'<p class="text-muted">No minutes recorded yet.'+(m.status==='completed'||m.status==='in_progress'?' Click edit to add minutes.':'')+'</p>'}${m.outcome?`<div class="mt-3"><h6>Outcome</h6><div class="p-3 bg-light rounded"><pre style="white-space:pre-wrap;font-family:inherit">${esc(m.outcome)}</pre></div></div>`:''}</div>`})}
function renderMeetingCalendar(){ajax('get_meeting_calendar_data','GET',{month:new Date().getMonth()+1,year:new Date().getFullYear()}).then(d=>{if(!d.success)return;const cal=document.getElementById('meetingCalendar');if(!cal)return;let html='<div class="d-flex justify-content-between align-items-center mb-2"><button class="btn btn-sm btn-outline-secondary" onclick="changeCalMonth(-1)"><i class="fas fa-chevron-left"></i></button><strong id="calMonthLabel">'+new Date().toLocaleString('default',{month:'long',year:'numeric'})+'</strong><button class="btn btn-sm btn-outline-secondary" onclick="changeCalMonth(1)"><i class="fas fa-chevron-right"></i></button></div><table class="table table-bordered text-center"><thead><tr><th>Sun</th><th>Mon</th><th>Tue</th><th>Wed</th><th>Thu</th><th>Fri</th><th>Sat</th></tr></thead><tbody>';var today=new Date();var firstDay=new Date(today.getFullYear(),today.getMonth(),1);var lastDay=new Date(today.getFullYear(),today.getMonth()+1,0);var startPad=firstDay.getDay();var daysInMonth=lastDay.getDate();var day=1;for(var w=0;w<6;w++){html+='<tr>';for(var d=0;d<7;d++){if((w===0&&d<startPad)||day>daysInMonth){html+='<td class="text-muted" style="height:70px;vertical-align:top;font-size:11px">&nbsp;</td>'}else{var dayMeetings=d.meetings.filter(function(m){var md=new Date(m.meeting_date);return md.getDate()===day&&md.getMonth()===today.getMonth()&&md.getFullYear()===today.getFullYear()});var cls=(day===today.getDate())?'bg-light fw-bold':'';html+='<td class="'+cls+'" style="height:70px;vertical-align:top;font-size:11px"><div>'+day+'</div>';dayMeetings.forEach(function(m){html+='<div class="badge bg-'+{scheduled:'info',in_progress:'warning',completed:'success',cancelled:'danger'}[m.status]+' bg-xs" style="font-size:9px;cursor:pointer;display:block;margin:1px 0" onclick="showMeetingDetail('+m.id+')">'+esc(m.title.substring(0,15))+'</div>'});html+='</td>';day++}}html+='</tr>';if(day>daysInMonth)break}html+='</tbody></table>';cal.innerHTML=html})}
var calMonthOffset=0;
function changeCalMonth(delta){calMonthOffset+=delta;var d=new Date();d.setMonth(d.getMonth()+calMonthOffset);ajax('get_meeting_calendar_data','GET',{month:d.getMonth()+1,year:d.getFullYear()}).then(res=>{if(!res.success)return;var cal=document.getElementById('meetingCalendar');if(!cal)return;document.getElementById('calMonthLabel').textContent=d.toLocaleString('default',{month:'long',year:'numeric'});var firstDay=new Date(d.getFullYear(),d.getMonth(),1);var lastDay=new Date(d.getFullYear(),d.getMonth()+1,0);var startPad=firstDay.getDay();var daysInMonth=lastDay.getDate();var day=1;var html='<div class="d-flex justify-content-between align-items-center mb-2"><button class="btn btn-sm btn-outline-secondary" onclick="changeCalMonth(-1)"><i class="fas fa-chevron-left"></i></button><strong>'+d.toLocaleString('default',{month:'long',year:'numeric'})+'</strong><button class="btn btn-sm btn-outline-secondary" onclick="changeCalMonth(1)"><i class="fas fa-chevron-right"></i></button></div><table class="table table-bordered text-center"><thead><tr><th>Sun</th><th>Mon</th><th>Tue</th><th>Wed</th><th>Thu</th><th>Fri</th><th>Sat</th></tr></thead><tbody>';var dayNum=1;for(var w=0;w<6;w++){html+='<tr>';for(var dd=0;dd<7;dd++){if((w===0&&dd<startPad)||dayNum>daysInMonth){html+='<td class="text-muted" style="height:70px;vertical-align:top;font-size:11px">&nbsp;</td>'}else{var dayMeetings=res.meetings.filter(function(m){var md=new Date(m.meeting_date);return md.getDate()===dayNum&&md.getMonth()===d.getMonth()&&md.getFullYear()===d.getFullYear()});html+='<td style="height:70px;vertical-align:top;font-size:11px"><div>'+(dayNum)+'</div>';dayMeetings.forEach(function(m){html+='<div class="badge bg-'+{scheduled:'info',in_progress:'warning',completed:'success',cancelled:'danger'}[m.status]+' bg-xs" style="font-size:9px;cursor:pointer;display:block;margin:1px 0" onclick="showMeetingDetail('+m.id+')">'+esc(m.title.substring(0,15))+'</div>'});html+='</td>';dayNum++}}html+='</tr>';if(dayNum>daysInMonth)break}html+='</tbody></table>';cal.innerHTML=html})}
function toggleCalView(){var calTab=document.getElementById('calTab');if(calTab)calTab.click()}
var selectedStaff=[];
function showStaffPicker(inputId,idField){var modal=new bootstrap.Modal(document.getElementById('staffPickerModal'));modal.show();document.getElementById('staffPickerModal').dataset.inputId=inputId;document.getElementById('staffPickerModal').dataset.idField=idField||'';loadStaffList()}
function loadStaffList(){ajax('get_staff_list','GET').then(d=>{if(!d.success)return;var el=document.getElementById('staffList');el.innerHTML=d.staff.map(s=>`<div class="list-group-item list-group-item-action d-flex align-items-center gap-2"><input type="checkbox" class="form-check-input staff-check" value="${esc(s.full_name)}" data-id="${s.id}"><div><strong>${esc(s.full_name)}</strong><br><small class="text-muted">${esc(s.department||'')} | ${esc(s.email||'')}</small></div></div>`).join('')})}
function filterStaffList(){var q=document.getElementById('staffSearch').value.toLowerCase();document.querySelectorAll('#staffList .list-group-item').forEach(function(el){el.style.display=el.textContent.toLowerCase().includes(q)?'':'none'})}
function addSelectedStaff(){var checked=document.querySelectorAll('.staff-check:checked');var names=[];var ids=[];checked.forEach(function(cb){names.push(cb.value);ids.push(cb.dataset.id)});var inputId=document.getElementById('staffPickerModal').dataset.inputId;var idField=document.getElementById('staffPickerModal').dataset.idField;var input=document.getElementById(inputId);if(input){var existing=input.value?input.value.split(',').map(function(s){return s.trim()}):[];names.forEach(function(n){if(!existing.includes(n))existing.push(n)});input.value=existing.join(', ')}if(idField){document.getElementById(idField).value=ids.join(',')}bootstrap.Modal.getInstance(document.getElementById('staffPickerModal')).hide()}
function loadMessages(){ajax('get_messages','GET').then(d=>{if(!d.success)return;const tb=document.getElementById('msgBody');if(!tb)return;tb.innerHTML=d.messages.map(m=>`<tr class="${m.is_read?'':'table-primary'}"><td>${esc(m.sender_name||'User #'+m.sender_id)}</td><td>${esc(m.subject||'No Subject')}</td><td><small>${esc((m.message||'').substring(0,80))}${(m.message||'').length>80?'...':''}</small></td><td><small>${m.created_at}</small></td><td>${m.is_read?'<span class="badge bg-secondary">Read</span>':'<span class="badge bg-primary">New</span>'}</td><td>${!m.is_read?`<button class="btn btn-sm btn-outline-success" onclick="markRead(${m.id})"><i class="fas fa-check"></i></button>`:''}</td></tr>`).join('')||'<tr><td colspan="6" class="text-center text-muted">No messages</td></tr>'})}
function showMsgModal(){new bootstrap.Modal(document.getElementById('msgModal')).show()}
function sendMessage(e){e.preventDefault();const fd=new FormData(document.getElementById('msgForm'));ajax('send_message','POST',fd).then(d=>{if(d.success){bootstrap.Modal.getInstance(document.getElementById('msgModal')).hide();showMsg(d.message);loadMessages();document.getElementById('msgForm').reset()}else showMsg(d.message,'danger')});return false}
function markRead(id){ajax('mark_message_read','POST',{id}).then(()=>loadMessages())}
if(document.getElementById('msgBody'))loadMessages();

function loadRequests(){ajax('get_requests','GET').then(d=>{if(!d.success)return;const tb=document.getElementById('reqBody');if(!tb)return;tb.innerHTML=d.requests.map(r=>`<tr><td>${esc(r.request_type)}</td><td><small>${esc((r.description||'').substring(0,60))}</small></td><td><span class="badge bg-${r.priority==='high'?'danger':r.priority==='medium'?'warning':'secondary'}">${r.priority}</span></td><td><span class="badge bg-${r.status==='resolved'?'success':r.status==='rejected'?'danger':r.status==='in_progress'?'info':'warning'}">${r.status}</span></td><td><small>${r.created_at}</small></td><td><button class="btn btn-sm btn-outline-warning" onclick='editRequest(${JSON.stringify(r).replace(/'/g,"\\'")})'><i class="fas fa-edit"></i></button> <button class="btn btn-sm btn-outline-danger" onclick="deleteRequest(${r.id})"><i class="fas fa-trash"></i></button></td></tr>`).join('')||'<tr><td colspan="6" class="text-center text-muted">No requests</td></tr>'})}
function deleteRequest(id){if(!confirm('Delete this request?'))return;ajax('delete_request','POST',{id}).then(d=>{if(d.success){showMsg(d.message);loadRequests()}else showMsg(d.message,'danger')})}
function showReqModal(r){document.getElementById('req_id').value=r?.id||'';document.getElementById('req_type').value=r?.request_type||'';document.getElementById('req_desc').value=r?.description||'';document.getElementById('req_priority').value=r?.priority||'medium';new bootstrap.Modal(document.getElementById('reqModal')).show()}
function editRequest(r){showReqModal(r)}
function saveRequest(e){e.preventDefault();const fd=new FormData(document.getElementById('reqForm'));ajax('save_request','POST',fd).then(d=>{if(d.success){bootstrap.Modal.getInstance(document.getElementById('reqModal')).hide();showMsg(d.message);loadRequests()}else showMsg(d.message,'danger')});return false}
if(document.getElementById('reqBody'))loadRequests();

function loadAnnouncements(){ajax('get_announcements','GET').then(d=>{if(!d.success)return;const tb=document.getElementById('annBody');if(!tb)return;tb.innerHTML=d.announcements.map(a=>`<tr><td>${esc(a.title)}</td><td><small>${esc((a.content||'').substring(0,80))}</small></td><td><span class="badge bg-info">${a.target_audience}</span></td><td>${a.publish_date}</td><td><button class="btn btn-sm btn-outline-warning" onclick='editAnnouncement(${JSON.stringify(a).replace(/'/g,"\\'")})'><i class="fas fa-edit"></i></button> <button class="btn btn-sm btn-outline-danger" onclick="deleteAnnouncement(${a.id})"><i class="fas fa-trash"></i></button></td></tr>`).join('')||'<tr><td colspan="5" class="text-center text-muted">No announcements</td></tr>'})}
function deleteAnnouncement(id){if(!confirm('Delete this announcement?'))return;ajax('delete_announcement','POST',{id}).then(d=>{if(d.success){showMsg(d.message);loadAnnouncements()}else showMsg(d.message,'danger')})}
function showAnnouncementModal(a){document.getElementById('ann_id').value=a?.id||'';document.getElementById('ann_title').value=a?.title||'';document.getElementById('ann_content').value=a?.content||'';document.getElementById('ann_audience').value=a?.target_audience||'all';document.getElementById('ann_date').value=a?.publish_date||'';new bootstrap.Modal(document.getElementById('annModal')).show()}
function editAnnouncement(a){showAnnouncementModal(a)}
function saveAnnouncement(e){e.preventDefault();const fd=new FormData(document.getElementById('annForm'));ajax('save_announcement','POST',fd).then(d=>{if(d.success){bootstrap.Modal.getInstance(document.getElementById('annModal')).hide();showMsg(d.message);loadAnnouncements()}else showMsg(d.message,'danger')});return false}
if(document.getElementById('annBody'))loadAnnouncements();

function loadContacts(){ajax('get_contacts','GET').then(d=>{if(!d.success)return;const tb=document.getElementById('contBody');if(!tb)return;tb.innerHTML=d.contacts.map(c=>`<tr><td>${esc(c.contact_name)}</td><td>${esc(c.contact_email||'')}</td><td>${esc(c.contact_phone||'')}</td><td>${esc(c.organization||'')}</td><td>${esc(c.category||'')}</td><td><button class="btn btn-sm btn-outline-warning" onclick='editContact(${JSON.stringify(c).replace(/'/g,"\\'")})'><i class="fas fa-edit"></i></button> <button class="btn btn-sm btn-outline-danger" onclick="deleteContact(${c.id})"><i class="fas fa-trash"></i></button></td></tr>`).join('')||'<tr><td colspan="6" class="text-center text-muted">No contacts</td></tr>'})}
function deleteContact(id){if(!confirm('Delete this contact?'))return;ajax('delete_contact','POST',{id}).then(d=>{if(d.success){showMsg(d.message);loadContacts()}else showMsg(d.message,'danger')})}
function showContactModal(c){document.getElementById('cont_id').value=c?.id||'';document.getElementById('cont_name').value=c?.contact_name||'';document.getElementById('cont_email').value=c?.contact_email||'';document.getElementById('cont_phone').value=c?.contact_phone||'';document.getElementById('cont_org').value=c?.organization||'';document.getElementById('cont_cat').value=c?.category||'';document.getElementById('cont_notes').value=c?.notes||'';new bootstrap.Modal(document.getElementById('contModal')).show()}
function editContact(c){showContactModal(c)}
function saveContact(e){e.preventDefault();const fd=new FormData(document.getElementById('contForm'));ajax('save_contact','POST',fd).then(d=>{if(d.success){bootstrap.Modal.getInstance(document.getElementById('contModal')).hide();showMsg(d.message);loadContacts()}else showMsg(d.message,'danger')});return false}
if(document.getElementById('contBody'))loadContacts();

function loadCorrespondence(){ajax('get_correspondence','GET').then(d=>{if(!d.success)return;const tb=document.getElementById('corrBody');if(!tb)return;tb.innerHTML=d.correspondence.map(c=>`<tr><td><small>${esc(c.reference_number||'-')}</small></td><td><strong>${esc(c.subject)}</strong></td><td><span class="badge bg-${c.type==='incoming'?'info':'primary'}">${c.type}</span></td><td><small>${esc(c.recipient_name||'-')}</small></td><td><span class="badge bg-secondary">${esc(c.category)}</span></td><td><span class="badge bg-${c.status==='sent'?'success':c.status==='received'?'info':c.status==='archived'?'dark':'warning'}">${c.status}</span></td><td><small>${c.sent_date||c.created_at}</small></td><td><button class="btn btn-sm btn-outline-warning" onclick='editCorrespondence(${JSON.stringify(c).replace(/'/g,"\\'")})'><i class="fas fa-edit"></i></button> <button class="btn btn-sm btn-outline-danger" onclick="deleteCorrespondence(${c.id})"><i class="fas fa-trash"></i></button></td></tr>`).join('')||'<tr><td colspan="8" class="text-center text-muted">No correspondence records</td></tr>'})}
function showCorrModal(c){document.getElementById('corrModalTitle').textContent=c?'Edit Correspondence':'New Correspondence';document.getElementById('corr_id').value=c?.id||'';document.getElementById('corr_type').value=c?.type||'outgoing';document.getElementById('corr_status').value=c?.status||'draft';document.getElementById('corr_category').value=c?.category||'general';document.getElementById('corr_subject').value=c?.subject||'';document.getElementById('corr_content').value=c?.content||'';document.getElementById('corr_recipient_name').value=c?.recipient_name||'';document.getElementById('corr_recipient_email').value=c?.recipient_email||'';document.getElementById('corr_recipient_phone').value=c?.recipient_phone||'';document.getElementById('corr_ref').value=c?.reference_number||'CORR-'+Date.now();document.getElementById('corr_date').value=c?.sent_date||new Date().toISOString().split('T')[0];new bootstrap.Modal(document.getElementById('corrModal')).show()}
function editCorrespondence(c){showCorrModal(c)}
function saveCorrespondence(e){e.preventDefault();const fd=new FormData(document.getElementById('corrForm'));ajax('save_correspondence','POST',fd).then(d=>{if(d.success){bootstrap.Modal.getInstance(document.getElementById('corrModal')).hide();showMsg(d.message);loadCorrespondence()}else showMsg(d.message,'danger')});return false}
function deleteCorrespondence(id){if(!confirm('Delete this record?'))return;ajax('delete_correspondence','POST',{id}).then(d=>{if(d.success){showMsg(d.message);loadCorrespondence()}})}
if(document.getElementById('corrBody'))loadCorrespondence();

function loadDocuments(){var t=document.getElementById('docTypeFilter')?.value||'';var s=document.getElementById('docStatusFilter')?.value||'';var q=document.getElementById('docSearch')?.value||'';ajax('get_official_documents','GET',{type:t,status:s,search:q}).then(d=>{if(!d.success)return;const tb=document.getElementById('docBody');if(!tb)return;tb.innerHTML=d.documents.map(doc=>`<tr><td><small class="text-muted">${esc(doc.reference_number||'-')}</small></td><td><strong>${esc(doc.title)}</strong>${doc.subject?`<br><small class="text-muted">${esc(doc.subject)}</small>`:''}</td><td><span class="badge bg-secondary">${esc(doc.doc_type)}</span></td><td><small>${esc(doc.department||'-')}</small></td><td><span class="badge bg-${doc.status==='published'?'success':doc.status==='approved'?'primary':doc.status==='review'?'warning':doc.status==='archived'?'dark':'secondary'}">${doc.status}</span></td><td><span class="badge bg-${doc.priority==='urgent'?'danger':doc.priority==='high'?'warning':'info'}">${doc.priority}</span></td><td><small>${doc.published_at||doc.created_at||''}</small></td><td><button class="btn btn-sm btn-outline-info" onclick="viewDocument(${JSON.stringify(doc).replace(/'/g,"\\'")})"><i class="fas fa-eye"></i></button> ${doc.status==='draft'||doc.status==='review'?`<button class="btn btn-sm btn-outline-success" onclick="publishDocument(${doc.id},'published')"><i class="fas fa-check"></i></button> `:''}${doc.status==='published'?`<button class="btn btn-sm btn-outline-secondary" onclick="publishDocument(${doc.id},'archived')"><i class="fas fa-archive"></i></button> `:''}<button class="btn btn-sm btn-outline-warning" onclick='editDocument(${JSON.stringify(doc).replace(/'/g,"\\'")})'><i class="fas fa-edit"></i></button> <button class="btn btn-sm btn-outline-danger" onclick="deleteDocument(${doc.id})"><i class="fas fa-trash"></i></button></td></tr>`).join('')||'<tr><td colspan="8" class="text-center text-muted">No documents found</td></tr>'})}
function showDocModal(doc){document.getElementById('docModalTitle').textContent=doc?'Edit Document':'New Document';document.getElementById('doc_id').value=doc?.id||'';document.getElementById('doc_type').value=doc?.doc_type||'letter';document.getElementById('doc_status').value=doc?.status||'draft';document.getElementById('doc_priority').value=doc?.priority||'medium';document.getElementById('doc_title').value=doc?.title||'';document.getElementById('doc_subject').value=doc?.subject||'';document.getElementById('doc_ref').value=doc?.reference_number||'';document.getElementById('doc_dept').value=doc?.department||'';document.getElementById('doc_cat').value=doc?.category||'';document.getElementById('doc_content').value=doc?.content||'';document.getElementById('doc_recipient_name').value=doc?.recipient_name||'';document.getElementById('doc_recipient_org').value=doc?.recipient_organization||'';document.getElementById('doc_confidential').checked=doc?.is_confidential==1;new bootstrap.Modal(document.getElementById('docModal')).show()}
function editDocument(doc){showDocModal(doc)}
function saveDocument(e){e.preventDefault();const fd=new FormData(document.getElementById('docForm'));ajax('save_official_document','POST',fd).then(d=>{if(d.success){bootstrap.Modal.getInstance(document.getElementById('docModal')).hide();showMsg(d.message);loadDocuments()}else showMsg(d.message,'danger')});return false}
function viewDocument(doc){var win=window.open('','_blank');win.document.write('<!DOCTYPE html><html><head><title>'+esc(doc.title)+'</title><style>body{font-family:serif;max-width:800px;margin:40px auto;padding:20px;line-height:1.6}.header{text-align:center;border-bottom:2px solid #000;padding-bottom:15px;margin-bottom:25px}.ref{font-size:12px;color:#666}.meta{margin:20px 0}.content{margin:20px 0;white-space:pre-wrap}.footer{margin-top:40px;border-top:1px solid #ccc;padding-top:15px;font-size:12px;color:#666}@media print{body{margin:0}}</style></head><body>');win.document.write('<div class="header"><h2>'+esc(doc.title)+'</h2>'+(doc.subject?'<p><em>'+esc(doc.subject)+'</em></p>':'')+'<div class="ref">Ref: '+(doc.reference_number||'-')+'</div></div>');win.document.write('<div class="meta"><table style="width:100%"><tr><td><strong>Type:</strong> '+esc(doc.doc_type)+'</td><td><strong>Status:</strong> '+doc.status+'</td><td><strong>Date:</strong> '+(doc.published_at||doc.created_at||'')+'</td></tr>'+(doc.department?'<tr><td><strong>Department:</strong> '+esc(doc.department)+'</td><td><strong>Category:</strong> '+esc(doc.category||'-')+'</td><td><strong>Priority:</strong> '+doc.priority+'</td></tr>':'')+'</table></div>');win.document.write('<hr>');if(doc.recipient_name){win.document.write('<div class="meta"><strong>To:</strong> '+esc(doc.recipient_name)+(doc.recipient_organization?'<br>'+esc(doc.recipient_organization):'')+'</div>')}win.document.write('<div class="content">'+esc(doc.content||'No content')+'</div>');win.document.write('<div class="footer"><p>Generated by ISNM Secretary Document Management</p></div>');win.document.write('<script>
function filterTable(inputId, tableId) {
    var input = document.getElementById(inputId);
    var filter = input.value.toUpperCase();
    var table = document.getElementById(tableId);
    if (!table) return;
    var tr = table.getElementsByTagName("tr");
    for (var i = 1; i < tr.length; i++) {
        var td = tr[i].getElementsByTagName("td");
        var found = false;
        for (var j = 0; j < td.length; j++) {
            if (td[j] && td[j].textContent.toUpperCase().indexOf(filter) > -1) { found = true; break; }
        }
        tr[i].style.display = found ? "" : "none";
    }
}
</script>
</body></html>');win.document.close();win.print()}
function publishDocument(id,status){if(!confirm('Set this document to '+status+'?'))return;ajax('publish_official_document','POST',{id:id,status:status}).then(d=>{if(d.success){showMsg(d.message);loadDocuments()}else showMsg(d.message,'danger')})}
function deleteDocument(id){if(!confirm('Delete this document permanently?'))return;ajax('delete_official_document','POST',{id}).then(d=>{if(d.success){showMsg(d.message);loadDocuments()}else showMsg(d.message,'danger')})}
if(document.getElementById('docBody')){loadDocuments();ajax('get_document_stats','GET').then(d=>{if(d.success){var s=d.stats;var el=document.querySelector('.section-card .row.g-2.mb-3');if(el&&s){var info='<div class="col-12"><small class="text-muted">Total: '+s.total+' | ';if(s.by_status){info+=Object.entries(s.by_status).map(function(e){return e[0]+': '+e[1]}).join(' | ')}info+='</small></div>';el.insertAdjacentHTML('beforebegin','<div class="row g-2 mb-2">'+info+'</div>')}}})}

/* ── Requirements Checklist ── */
function loadStudentRequirements(){
    var q=document.getElementById('reqSearchInput')?.value||'';
    if(!q){document.getElementById('reqChecklistBody').innerHTML='<div class="alert alert-info">Enter a student number to search</div>';return}
    ajax('search_students','GET',{search:q,page:1}).then(d=>{
        if(!d.success||!d.students.length){document.getElementById('reqChecklistBody').innerHTML='<div class="alert alert-warning">No students found</div>';return}
        var s=d.students[0];
        document.getElementById('reqChecklistResult').innerHTML='<div class="alert alert-info"><strong>'+esc(s.full_name)+'</strong> (#'+esc(s.student_number||'')+') — '+esc(s.program||'')+'</div>';
        ajax('get_student_app_requirements','GET',{student_number:s.student_number}).then(r=>{
            if(!r.success)return;
            var reqs=r.requirements;
            var cats={};
            reqs.forEach(function(x){if(!cats[x.category])cats[x.category]=[];cats[x.category].push(x)});
            var html='';
            Object.entries(cats).forEach(function([cat,items]){
                var cleared=items.filter(function(x){return x.status==='Cleared'}).length;
                var total=items.length;
                var pct=total?Math.round(cleared/total*100):0;
                html+='<div class="card mb-3"><div class="card-header d-flex justify-content-between align-items-center" style="background:var(--isnm-navy);color:#fff"><strong>'+esc(cat)+'</strong><span class="badge bg-light text-dark">'+cleared+'/'+total+' cleared ('+pct+'%)</span></div><div class="card-body p-2"><table class="table table-sm table-hover mb-0"><thead><tr><th style="width:40px"></th><th>Requirement</th><th>Status</th><th>Remarks</th><th>Cleared By</th></tr></thead><tbody>';
                items.forEach(function(x){
                    var isCleared=x.status==='Cleared';
                    html+='<tr><td><input type="checkbox" class="form-check-input" '+(isCleared?'checked':'')+' onchange="toggleStudentReq('+x.id+',this.checked)"></td><td>'+esc(x.requirement_name)+'</td><td><span class="badge bg-'+(isCleared?'success':x.status==='Submitted'?'info':x.status==='Missing'?'danger':'secondary')+'">'+esc(x.status)+'</span></td><td><small class="text-muted">'+esc(x.remarks||'')+'</small></td><td><small>'+esc(x.verified_by||'-')+'</small></td></tr>';
                });
                html+='</tbody></table></div></div>';
            });
            if(!reqs.length) html='<div class="alert alert-info">No requirements found. <button class="btn btn-isnm btn-sm ms-2" onclick="initStudentReqs(\''+esc(s.student_number)+'\',\''+esc(s.full_name)+'\')">Initialize Requirements</button></div>';
            document.getElementById('reqChecklistBody').innerHTML=html;
        });
    });
}
function toggleStudentReq(id,checked){ajax('clear_student_requirement','POST',{req_id:id,cleared:checked?1:0}).then(d=>{if(!d.success)showMsg(d.message,'danger');else loadStudentRequirements()})}
function initStudentReqs(num,name){ajax('bulk_init_student_requirements','POST',{student_number:num,student_name:name}).then(d=>{if(d.success){showMsg(d.message);loadStudentRequirements()}else showMsg(d.message,'danger')})}

/* ── Store Items ── */
function loadStoreItems(){
    var cat=document.getElementById('storeCatFilter')?.value||'';
    var q=document.getElementById('storeSearchInput')?.value||'';
    ajax('get_store_items','GET',{category:cat,search:q}).then(d=>{
        if(!d.success)return;
        var tb=document.getElementById('storeItemsBody');
        if(!tb)return;
        tb.innerHTML=d.items.map(function(it,i){
            var stClass=it.status==='Out of Stock'?'danger':it.status==='Low Stock'?'warning':'success';
            return'<tr><td>'+(i+1)+'</td><td><strong>'+esc(it.item_name)+'</strong></td><td><span class="badge bg-info">'+esc(it.category)+'</span></td><td>'+esc(it.unit)+'</td><td><strong>'+it.quantity_in_stock+'</strong></td><td>'+it.minimum_level+'</td><td><span class="badge bg-'+stClass+'">'+esc(it.status)+'</span></td><td><button class="btn btn-sm btn-outline-warning" onclick=\'editStoreItem('+JSON.stringify(it).replace(/'/g,"\\'")+')\'><i class="fas fa-edit"></i></button> <button class="btn btn-sm btn-outline-danger" onclick="deleteStoreItem('+it.id+')"><i class="fas fa-trash"></i></button></td></tr>';
        }).join('')||'<tr><td colspan="8" class="text-center text-muted">No items found</td></tr>';
    });
    ajax('get_store_stats','GET').then(d=>{
        if(!d.success)return;
        var s=d.stats;
        document.getElementById('storeStatsRow').innerHTML='<div class="col-md-3"><div class="stat-card text-center"><div class="stat-value text-primary">'+s.total_items+'</div><div class="stat-label">Total Items</div></div></div><div class="col-md-3"><div class="stat-card text-center"><div class="stat-value text-warning">'+s.low_stock+'</div><div class="stat-label">Low Stock</div></div></div><div class="col-md-3"><div class="stat-card text-center"><div class="stat-value text-danger">'+s.out_of_stock+'</div><div class="stat-label">Out of Stock</div></div></div><div class="col-md-3"><div class="stat-card text-center"><div class="stat-value text-info">'+s.pending_requisitions+'</div><div class="stat-label">Pending Requisitions</div></div></div>';
    });
}
function showStoreItemModal(it){
    document.getElementById('storeItemModalTitle').textContent=it?'Edit Item':'Add Item';
    document.getElementById('si_id').value=it?.id||'';
    document.getElementById('si_name').value=it?.item_name||'';
    document.getElementById('si_unit').value=it?.unit||'piece';
    document.getElementById('si_cat').value=it?.category||'General Utilities';
    document.getElementById('si_location').value=it?.location||'';
    document.getElementById('si_qty').value=it?.quantity_in_stock||0;
    document.getElementById('si_min').value=it?.minimum_level||0;
    document.getElementById('si_price').value=it?.unit_price||0;
    new bootstrap.Modal(document.getElementById('storeItemModal')).show();
}
function editStoreItem(it){showStoreItemModal(it)}
function saveStoreItem(e){e.preventDefault();var fd=new FormData(document.getElementById('storeItemForm'));ajax('save_store_item','POST',fd).then(d=>{if(d.success){bootstrap.Modal.getInstance(document.getElementById('storeItemModal')).hide();showMsg(d.message);loadStoreItems()}else showMsg(d.message,'danger')});return false}
function deleteStoreItem(id){if(!confirm('Delete this item?'))return;ajax('delete_store_item','POST',{item_id:id}).then(d=>{if(d.success){showMsg(d.message);loadStoreItems()}else showMsg(d.message,'danger')})}
if(document.getElementById('storeItemsBody'))loadStoreItems();

/* ── Food Store ── */
function loadFoodStore(){
    ajax('get_store_items','GET',{category:'Food Store'}).then(d=>{
        if(!d.success)return;
        var tb=document.getElementById('foodStoreBody');
        if(!tb)return;
        tb.innerHTML=d.items.map(function(it,i){
            var stClass=it.status==='Out of Stock'?'danger':it.status==='Low Stock'?'warning':'success';
            return'<tr><td>'+(i+1)+'</td><td><strong>'+esc(it.item_name)+'</strong></td><td>'+esc(it.unit)+'</td><td><strong>'+it.quantity_in_stock+'</strong></td><td>'+it.minimum_level+'</td><td><span class="badge bg-'+stClass+'">'+esc(it.status)+'</span></td><td><button class="btn btn-sm btn-outline-warning" onclick=\'editStoreItem('+JSON.stringify(it).replace(/'/g,"\\'")+')\'><i class="fas fa-edit"></i></button></td></tr>';
        }).join('')||'<tr><td colspan="7" class="text-center text-muted">No food items</td></tr>';
    });
}
if(document.getElementById('foodStoreBody'))loadFoodStore();

/* ── Requisitions (Matron) ── */
function loadRequisitions(){
    var st=document.getElementById('reqStatusFilter')?.value||'';
    ajax('get_requisitions','GET',{status:st}).then(d=>{
        if(!d.success)return;
        var tb=document.getElementById('requisitionBody');
        if(!tb)return;
        tb.innerHTML=d.requisitions.map(function(r){
            var badge=r.status==='Approved'?'success':r.status==='Rejected'?'danger':r.status==='Issued'?'info':r.status==='Partially Issued'?'warning':'secondary';
            var actions='';
            if(r.status==='Pending'){
                actions='<button class="btn btn-sm btn-outline-success" onclick="updateReqStatus('+r.id+',\'Approved\')"><i class="fas fa-check"></i></button> <button class="btn btn-sm btn-outline-danger" onclick="updateReqStatus('+r.id+',\'Rejected\')"><i class="fas fa-times"></i></button>';
            }else if(r.status==='Approved'){
                actions='<button class="btn btn-sm btn-outline-info" onclick="updateReqStatus('+r.id+',\'Issued\')"><i class="fas fa-truck"></i></button>';
            }
            return'<tr><td><small>'+esc(r.requisition_number||'-')+'</small></td><td>'+esc(r.item_name)+'</td><td>'+r.quantity_requested+'</td><td>'+esc(r.department||'-')+'</td><td><small>'+esc(r.purpose||'')+'</small></td><td><small>'+esc(r.requested_by_name||'')+'</small></td><td><span class="badge bg-'+badge+'">'+r.status+'</span></td><td>'+actions+'</td></tr>';
        }).join('')||'<tr><td colspan="8" class="text-center text-muted">No requisitions</td></tr>';
    });
}
function showRequisitionModal(r){
    document.getElementById('requisitionModalTitle').textContent=r?'Edit Requisition':'New Requisition';
    document.getElementById('rq_id').value=r?.id||'';
    document.getElementById('rq_qty').value=r?.quantity_requested||1;
    document.getElementById('rq_dept').value=r?.department||'Matron';
    document.getElementById('rq_purpose').value=r?.purpose||'';
    ajax('get_store_items','GET',{category:'Matron Items'}).then(d=>{
        var sel=document.getElementById('rq_item');
        sel.innerHTML='<option value="">-- Select --</option>';
        if(d.success)d.items.forEach(function(it){sel.innerHTML+='<option value="'+esc(it.item_name)+'"'+(r&&r.item_name===it.item_name?' selected':'')+'>'+esc(it.item_name)+'</option>'});
    });
    new bootstrap.Modal(document.getElementById('requisitionModal')).show();
}
function saveRequisition(e){e.preventDefault();var fd=new FormData(document.getElementById('requisitionForm'));ajax('save_requisition','POST',fd).then(d=>{if(d.success){bootstrap.Modal.getInstance(document.getElementById('requisitionModal')).hide();showMsg(d.message);loadRequisitions()}else showMsg(d.message,'danger')});return false}
function updateReqStatus(id,status){if(!confirm('Set status to '+status+'?'))return;ajax('update_requisition_status','POST',{req_id:id,new_status:status}).then(d=>{if(d.success){showMsg(d.message);loadRequisitions()}else showMsg(d.message,'danger')})}
if(document.getElementById('requisitionBody'))loadRequisitions();

/* ── Director Requirements Portal ── */
function loadDirectorRequirements(){
    var q=document.getElementById('dirReqSearch')?.value||'';
    var cat=document.getElementById('dirReqCategory')?.value||'';
    var st=document.getElementById('dirReqStatus')?.value||'';
    if(!q){document.getElementById('dirReqBody').innerHTML='<tr><td colspan="8" class="text-center text-muted">Search for a student...</td></tr>';return}
    ajax('search_students','GET',{search:q,page:1}).then(d=>{
        if(!d.success||!d.students.length){document.getElementById('dirReqBody').innerHTML='<tr><td colspan="8" class="text-center text-muted">No students found</td></tr>';return}
        var s=d.students[0];
        ajax('get_student_app_requirements','GET',{student_number:s.student_number}).then(r=>{
            if(!r.success)return;
            var reqs=r.requirements;
            if(cat)reqs=reqs.filter(function(x){return x.category===cat});
            if(st)reqs=reqs.filter(function(x){return x.status===st});
            var tb=document.getElementById('dirReqBody');
            tb.innerHTML=reqs.map(function(x){
                var isCleared=x.status==='Cleared';
                return'<tr><td><input type="checkbox" class="form-check-input dir-req-check" data-id="'+x.id+'" '+(isCleared?'checked':'')+' onchange="toggleStudentReq('+x.id+',this.checked)"></td><td><small>'+esc(x.student_number)+'</small></td><td>'+esc(x.student_name||s.full_name)+'</td><td>'+esc(x.requirement_name)+'</td><td><span class="badge bg-info">'+esc(x.category)+'</span></td><td><span class="badge bg-'+(isCleared?'success':x.status==='Submitted'?'info':x.status==='Missing'?'danger':'secondary')+'">'+esc(x.status)+'</span></td><td><input type="checkbox" class="form-check-input" '+(isCleared?'checked':'')+' onchange="toggleStudentReq('+x.id+',this.checked)"></td><td><small class="text-muted">'+esc(x.verified_by||'-')+'</small></td></tr>';
            }).join('')||'<tr><td colspan="8" class="text-center text-muted">No requirements for this student</td></tr>';
        });
    });
}
function toggleAllDirReq(el){document.querySelectorAll('.dir-req-check').forEach(function(cb){cb.checked=el.checked;toggleStudentReq(cb.dataset.id,el.checked)})}
<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
