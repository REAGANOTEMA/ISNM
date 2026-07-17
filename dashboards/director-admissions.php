<?php
/**
 * Director of Admissions – Complete Dashboard
 * Tabs: Applications | Requirements | Enrolled Students
 */
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
require_once __DIR__ . '/../views/student_data_loader.php';
require_once __DIR__ . '/../includes/global_search.php';
require_once __DIR__ . '/../includes/student_sync.php';

$ctx = bootstrapStaffDashboard(['director', 'ceo', 'academic registrar', 'director admissions']);
$conn = $ctx['staff'];
$stuConn = $ctx['students'] ?? null;
$webConn = $ctx['website'] ?? null;
$user = $ctx['user'];
$userId = (int)($user['id'] ?? 0);
$userRole = $_SESSION['role'] ?? '';
$userName = $user['full_name'] ?? 'Director of Admissions';
$studentsDb = defined('STUDENTS_DB_NAME') ? STUDENTS_DB_NAME : 'igangaschool_students';
$uploadDir = __DIR__ . '/../uploads/admissions/';
if (!is_dir($uploadDir)) @mkdir($uploadDir, 0755, true);
if (!$conn) die('Database connection failed.');

// --- Auto-migrate core tables ---
$autoMigrateSQL = [
    "CREATE TABLE IF NOT EXISTS academic_programs (id INT AUTO_INCREMENT PRIMARY KEY,program_code VARCHAR(20) NOT NULL UNIQUE,program_name VARCHAR(255) NOT NULL,program_type ENUM('Certificate','Diploma','Degree','Short Course') NOT NULL DEFAULT 'Diploma',department VARCHAR(100) DEFAULT NULL,duration_years DECIMAL(3,1) NOT NULL DEFAULT 2.0,total_fee DECIMAL(14,2) NOT NULL DEFAULT 0.00,status ENUM('Active','Inactive') NOT NULL DEFAULT 'Active',created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    "CREATE TABLE IF NOT EXISTS intakes (id INT AUTO_INCREMENT PRIMARY KEY,intake_name VARCHAR(100) NOT NULL,intake_month VARCHAR(20) NOT NULL,intake_year YEAR NOT NULL,application_start DATE DEFAULT NULL,application_deadline DATE DEFAULT NULL,status ENUM('Open','Closed','Upcoming') NOT NULL DEFAULT 'Upcoming',created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,UNIQUE KEY uk_intake(intake_month,intake_year)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    "CREATE TABLE IF NOT EXISTS applicants (id INT AUTO_INCREMENT PRIMARY KEY,application_number VARCHAR(30) NOT NULL UNIQUE,student_number VARCHAR(50) DEFAULT NULL,registration_number VARCHAR(50) DEFAULT NULL,full_name VARCHAR(255) NOT NULL,first_name VARCHAR(100) DEFAULT NULL,middle_name VARCHAR(100) DEFAULT NULL,surname VARCHAR(100) DEFAULT NULL,gender ENUM('Male','Female','Other') DEFAULT NULL,date_of_birth DATE DEFAULT NULL,email VARCHAR(100) DEFAULT NULL,phone VARCHAR(20) DEFAULT NULL,nationality VARCHAR(100) DEFAULT 'Ugandan',district VARCHAR(100) DEFAULT NULL,religion VARCHAR(50) DEFAULT NULL,address TEXT DEFAULT NULL,program_id INT DEFAULT NULL,intake VARCHAR(50) DEFAULT NULL,application_source ENUM('Online','Manual','Walk-in','Referral','Other') DEFAULT 'Online',status ENUM('New','Under Review','Waiting for Documents','Requirements Verified','Interview Scheduled','Approved','Rejected','Registered','Withdrawn') NOT NULL DEFAULT 'New',rejection_reason TEXT DEFAULT NULL,guardian_name VARCHAR(200) DEFAULT NULL,guardian_phone VARCHAR(20) DEFAULT NULL,emergency_contact_name VARCHAR(100) DEFAULT NULL,emergency_contact_phone VARCHAR(20) DEFAULT NULL,submitted_at TIMESTAMP NULL DEFAULT NULL,reviewed_by INT DEFAULT NULL,reviewed_at TIMESTAMP NULL DEFAULT NULL,approved_by INT DEFAULT NULL,approved_at TIMESTAMP NULL DEFAULT NULL,registered_at TIMESTAMP NULL DEFAULT NULL,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    "CREATE TABLE IF NOT EXISTS student_admission_tracking (id INT AUTO_INCREMENT PRIMARY KEY,student_number VARCHAR(50) DEFAULT NULL,application_number VARCHAR(30) NOT NULL,applicant_id INT DEFAULT NULL,program VARCHAR(255) DEFAULT NULL,intake VARCHAR(50) DEFAULT NULL,admission_date DATE DEFAULT NULL,admission_status ENUM('Pending','Under Review','Requirements Pending','Approved','Rejected','Registered') NOT NULL DEFAULT 'Pending',requirements_total INT NOT NULL DEFAULT 0,requirements_completed INT NOT NULL DEFAULT 0,documents_uploaded INT NOT NULL DEFAULT 0,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,UNIQUE KEY uk_track_app(application_number)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    "CREATE TABLE IF NOT EXISTS admission_activity_logs (id INT AUTO_INCREMENT PRIMARY KEY,applicant_id INT DEFAULT NULL,user_id INT DEFAULT NULL,action VARCHAR(100) NOT NULL,description TEXT DEFAULT NULL,ip_address VARCHAR(45) DEFAULT NULL,user_agent TEXT DEFAULT NULL,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,INDEX idx_log_app(applicant_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    "CREATE TABLE IF NOT EXISTS admission_notifications (id INT AUTO_INCREMENT PRIMARY KEY,applicant_id INT DEFAULT NULL,user_id INT DEFAULT NULL,type ENUM('info','success','warning','danger') NOT NULL DEFAULT 'info',title VARCHAR(255) NOT NULL,message TEXT DEFAULT NULL,is_read TINYINT(1) NOT NULL DEFAULT 0,link VARCHAR(500) DEFAULT NULL,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    "CREATE TABLE IF NOT EXISTS admission_requirements (id INT AUTO_INCREMENT PRIMARY KEY,requirement_name VARCHAR(255) NOT NULL,type ENUM('Document','Certificate','ID','Photo','Form','Other') NOT NULL DEFAULT 'Document',display_order INT NOT NULL DEFAULT 0,is_mandatory TINYINT(1) NOT NULL DEFAULT 1,is_active TINYINT(1) NOT NULL DEFAULT 1,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    "CREATE TABLE IF NOT EXISTS applicant_requirement_status (id INT AUTO_INCREMENT PRIMARY KEY,applicant_id INT NOT NULL,requirement_id INT NOT NULL,status ENUM('Not Submitted','Pending','Submitted','Verified','Rejected','Missing','Received','Not Yet Given') NOT NULL DEFAULT 'Not Submitted',remarks TEXT DEFAULT NULL,submitted_by INT DEFAULT NULL,submitted_at TIMESTAMP NULL DEFAULT NULL,verified_by INT DEFAULT NULL,verified_at TIMESTAMP NULL DEFAULT NULL,updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,UNIQUE KEY uk_app_req(applicant_id,requirement_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    "CREATE TABLE IF NOT EXISTS student_documents (id INT AUTO_INCREMENT PRIMARY KEY,applicant_id INT NOT NULL,requirement_id INT DEFAULT NULL,document_name VARCHAR(255) NOT NULL,document_type VARCHAR(100) DEFAULT NULL,file_path VARCHAR(500) NOT NULL,file_size INT DEFAULT NULL,verification_status ENUM('Pending','Verified','Rejected') NOT NULL DEFAULT 'Pending',verification_remarks TEXT DEFAULT NULL,verified_by INT DEFAULT NULL,verified_at TIMESTAMP NULL DEFAULT NULL,document_status ENUM('Active','Deleted') NOT NULL DEFAULT 'Active',uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,INDEX idx_doc_app(applicant_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    "CREATE TABLE IF NOT EXISTS requirement_history (id INT AUTO_INCREMENT PRIMARY KEY,applicant_id INT NOT NULL,requirement_id INT DEFAULT NULL,action VARCHAR(100) NOT NULL,previous_status VARCHAR(50) DEFAULT NULL,new_status VARCHAR(50) DEFAULT NULL,performed_by INT DEFAULT NULL,remarks TEXT DEFAULT NULL,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,INDEX idx_rh_app(applicant_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    "CREATE TABLE IF NOT EXISTS student_requirements (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_id INT NOT NULL,
        student_name VARCHAR(200),
        registration_number VARCHAR(50),
        requirement_type VARCHAR(100) NOT NULL,
        document_name VARCHAR(200),
        file_path VARCHAR(500),
        status ENUM('pending','submitted','verified','rejected') DEFAULT 'pending',
        submitted_date DATE,
        verified_date DATE,
        verified_by INT,
        notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
];
foreach ($autoMigrateSQL as $sql) {
    try { $conn->query($sql); } catch (Exception $e) { error_log('Admissions migrate: ' . $e->getMessage()); }
}

// Seed default requirement types
$r = $conn->query("SELECT COUNT(*) c FROM admission_requirements WHERE is_active=1");
if ($r && (int)$r->fetch_assoc()['c'] === 0) {
    $conn->query("INSERT IGNORE INTO admission_requirements(requirement_name,type,display_order,is_mandatory) VALUES
        ('Admission Letter','Document',1,1),
        ('Academic Transcripts','Document',2,1),
        ('National ID','Document',3,1),
        ('Passport Photo','Photo',4,1),
        ('Medical Certificate','Document',5,1),
        ('Birth Certificate','Document',6,1),
        ('Recommendation Letter','Document',7,0),
        ('Fee Receipt','Document',8,0)
    ");
}
// Seed intakes if empty
$r = $conn->query("SELECT COUNT(*) c FROM intakes");
if ($r && (int)$r->fetch_assoc()['c'] === 0) {
    $conn->query("INSERT IGNORE INTO intakes(intake_name,intake_month,intake_year,application_start,application_deadline,status) VALUES
        ('January 2026','January',2026,'2025-09-01','2026-01-15','Open'),
        ('May 2026','May',2026,'2026-01-01','2026-05-15','Upcoming'),
        ('August 2026','August',2026,'2026-04-01','2026-08-15','Upcoming')
    ");
}
// Sync programs from students DB
if ($stuConn) {
    $conn->query("INSERT IGNORE INTO academic_programs(program_code,program_name,program_type,duration_years) SELECT CONCAT('PGM-',p.id),p.program_name,p.program_type,p.duration_years FROM $studentsDb.programs p WHERE p.is_active=1 AND NOT EXISTS(SELECT 1 FROM academic_programs ap WHERE ap.program_name=p.program_name COLLATE utf8mb4_general_ci LIMIT 1)");
}

// --- Helpers ---
function logAdmission($conn, $applicantId, $userId, $action, $desc) {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $s = $conn->prepare("INSERT INTO admission_activity_logs(applicant_id,user_id,action,description,ip_address,user_agent) VALUES(?,?,?,?,?,?)");
    if ($s) { $s->bind_param('iissss', $applicantId, $userId, $action, $desc, $ip, $ua); $s->execute(); $s->close(); }
}
function getStatusBadge($s) {
    $m = ['New' => 'bg-primary', 'Under Review' => 'bg-info', 'Waiting for Documents' => 'bg-warning text-dark', 'Requirements Verified' => 'bg-success', 'Interview Scheduled' => 'bg-purple', 'Approved' => 'bg-success', 'Rejected' => 'bg-danger', 'Registered' => 'bg-dark', 'Withdrawn' => 'bg-secondary'];
    $c = $m[$s] ?? 'bg-secondary';
    return "<span class=\"badge $c\">" . htmlspecialchars($s) . '</span>';
}
function adCount($conn, $status) {
    $r = $conn->query("SELECT COUNT(*) c FROM applicants WHERE status='" . $conn->real_escape_string($status) . "'");
    return $r ? (int)$r->fetch_assoc()['c'] : 0;
}

// --- Active tab ---
$tab = $_GET['tab'] ?? 'applications';
if ($tab === 'home') $tab = 'applications';

// --- POST / AJAX handlers ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Invalid security token.']);
        exit;
    }
    $action = $_POST['action'] ?? '';
    header('Content-Type: application/json');

    // --- Applicant CRUD ---
    if ($action === 'filter_applicants') {
        $where = "1=1";
        $params = [];
        $types = '';
        foreach (['status', 'intake', 'gender'] as $f) {
            $v = trim($_POST[$f] ?? '');
            if ($v !== '' && $v !== 'all') { $where .= " AND $f=?"; $params[] = $v; $types .= 's'; }
        }
        $pid = (int)($_POST['program_id'] ?? 0);
        if ($pid) { $where .= " AND program_id=?"; $params[] = $pid; $types .= 'i'; }
        $q = trim($_POST['search'] ?? '');
        if (strlen($q) >= 2) {
            $qq = '%' . $q . '%';
            $where .= " AND (full_name LIKE ? OR application_number LIKE ? OR email LIKE ? OR phone LIKE ?)";
            $params = array_merge($params, [$qq, $qq, $qq, $qq]);
            $types .= 'ssss';
        }
        $lim = min((int)($_POST['limit'] ?? 50), 200);
        $sql = "SELECT a.*,ap.program_name FROM applicants a LEFT JOIN academic_programs ap ON a.program_id=ap.id WHERE $where ORDER BY a.created_at DESC LIMIT $lim";
        $s = $conn->prepare($sql);
        $rows = [];
        if ($s) { $s->bind_param($types, ...$params); $s->execute(); $rows = $s->get_result()->fetch_all(MYSQLI_ASSOC); $s->close(); }
        echo json_encode($rows);
        exit;
    }
    if ($action === 'update_status') {
        $id = (int)($_POST['id'] ?? 0);
        $st = trim($_POST['status'] ?? '');
        if ($id && $st) {
            $stmt = $conn->prepare("UPDATE applicants SET status=?, reviewed_by=?, reviewed_at=NOW() WHERE id=?");
            if ($stmt) { $stmt->bind_param('sii', $st, $userId, $id); $stmt->execute(); $stmt->close(); }
            logAdmission($conn, $id, $userId, "Status: $st", "Status changed to $st");
        }
        echo json_encode(['success' => true]);
        exit;
    }
    if ($action === 'register_student') {
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) { echo json_encode(['success' => false, 'message' => 'Invalid applicant ID.']); exit; }

        $app = $conn->prepare("SELECT a.*, ap.program_name FROM applicants a LEFT JOIN academic_programs ap ON a.program_id=ap.id WHERE a.id=? AND a.status IN ('Approved','Registered') LIMIT 1");
        if (!$app) { echo json_encode(['success' => false, 'message' => 'Database error.']); exit; }
        $app->bind_param('i', $id);
        $app->execute();
        $applicant = $app->get_result()->fetch_assoc();
        $app->close();
        if (!$applicant) { echo json_encode(['success' => false, 'message' => 'Applicant not found or not approved.']); exit; }

        $full_name = $applicant['full_name'];
        $parts = explode(' ', $full_name);
        $first_name = $parts[0] ?? $full_name;
        $surname = count($parts) > 1 ? $parts[count($parts)-1] : $first_name;
        $other_name = $applicant['middle_name'] ?? '';
        $student_number = 'STU' . date('Y') . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
        $reg_number = 'REG' . date('Y') . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
        $index_number = 'IDX' . date('Y') . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
        $temp_password = bin2hex(random_bytes(4));
        $hashed_password = password_hash($temp_password, PASSWORD_BCRYPT);
        $program_name = $applicant['program_name'] ?? $applicant['program_id'] ?? '';
        $intake = $applicant['intake'] ?? '';
        $year = 1;
        $level = 'Year 1';

        $conn->begin_transaction();
        try {
            $stmt = $conn->prepare("UPDATE applicants SET status='Registered', registered_at=NOW(), reviewed_by=?, reviewed_at=NOW() WHERE id=?");
            if ($stmt) { $stmt->bind_param('ii', $userId, $id); $stmt->execute(); $stmt->close(); }
            logAdmission($conn, $id, $userId, "Registered", "Applicant registered as student $student_number");

            $rc = 0;
            $ck = $conn->query("SELECT COUNT(*) c FROM admission_requirements WHERE is_active=1");
            if ($ck) { $rc = (int)$ck->fetch_assoc()['c']; }
            $track = $conn->prepare("INSERT INTO student_admission_tracking (student_number, full_name, program, intake, admission_date, admission_status, requirements_completed, requirements_total) VALUES (?,?,?,?,?,?,?,?)");
            $track->bind_param('ssssssii', $student_number, $full_name, $program_name, $intake, date('Y-m-d'), 'Registered', 0, $rc);
            if (!$track->execute()) { error_log('track execute failed: ' . ($track->error ?? 'unknown')); };

            if ($stuConn) {
                $s_ins = $stuConn->prepare("INSERT IGNORE INTO `$studentsDb`.`students` (index_number, student_number, registration_number, first_name, surname, other_name, full_name, email, phone, program, course, year, level, intake_year, intake_period, date_of_birth, gender, address, guardian_name, guardian_phone, nationality, emergency_contact_name, emergency_contact_phone, set_name, status, password, is_first_login) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,'Active',?,0)");
                $s_ins->bind_param('sssssssssssssssssssssssss',
                    $index_number, $student_number, $reg_number, $first_name, $surname, $other_name, $full_name,
                    $applicant['email'], $applicant['phone'], $program_name, $program_name,
                    $year, $level, (string)date('Y'), $intake, $applicant['date_of_birth'],
                    $applicant['gender'], $applicant['address'], $applicant['guardian_name'],
                    $applicant['guardian_phone'], $applicant['nationality'],
                    $applicant['emergency_contact_name'], $applicant['emergency_contact_phone'],
                    '', $hashed_password
                );
                if (!$s_ins->execute()) { error_log('s_ins execute failed: ' . ($s_ins->error ?? 'unknown')); };
                $s_id = $stuConn->insert_id;
                if ($s_id > 0) {
                    $prof = $stuConn->prepare("INSERT IGNORE INTO `$studentsDb`.`student_profiles` (student_id, admission_status, fee_status) VALUES (?,?,?)");
                    $prof->bind_param('iss', $s_id, $applicant['status'], 'unpaid');
                    if (!$prof->execute()) { error_log('prof execute failed: ' . ($prof->error ?? 'unknown')); };
                }
            }

            $conn->commit();
            echo json_encode(['success' => true, 'student_number' => $student_number, 'reg_number' => $reg_number, 'portal_username' => $student_number, 'portal_password' => $temp_password]);
        } catch (Exception $e) {
            $conn->rollback();
            echo json_encode(['success' => false, 'message' => 'Registration failed: ' . $e->getMessage()]);
        }
        exit;
    }
    if ($action === 'approve') {
        $id = (int)($_POST['id'] ?? 0);
        $stmt = $conn->prepare("UPDATE applicants SET status='Approved', approved_by=?, approved_at=NOW() WHERE id=?");
        if ($stmt) { $stmt->bind_param('ii', $userId, $id); $stmt->execute(); $stmt->close(); }
        logAdmission($conn, $id, $userId, "Approved", "Application approved");
        echo json_encode(['success' => true]);
        exit;
    }
    if ($action === 'reject') {
        $id = (int)($_POST['id'] ?? 0);
        $reason = trim($_POST['reason'] ?? '');
        $stmt = $conn->prepare("UPDATE applicants SET status='Rejected', rejection_reason=? WHERE id=?");
        if ($stmt) { $stmt->bind_param('si', $reason, $id); $stmt->execute(); $stmt->close(); }
        logAdmission($conn, $id, $userId, "Rejected", "Reason: $reason");
        echo json_encode(['success' => true]);
        exit;
    }

    // --- Requirements CRUD ---
    if ($action === 'add_requirement') {
        $studentId = (int)($_POST['student_id'] ?? 0);
        $studentName = trim($_POST['student_name'] ?? '');
        $regNum = trim($_POST['registration_number'] ?? '');
        $reqType = trim($_POST['requirement_type'] ?? '');
        $docName = trim($_POST['document_name'] ?? '');
        $notes = trim($_POST['notes'] ?? '');
        if (!$studentId || !$reqType) {
            echo json_encode(['success' => false, 'message' => 'Student and requirement type are required.']);
            exit;
        }
        $s = $conn->prepare("INSERT INTO student_requirements(student_id,student_name,registration_number,requirement_type,document_name,notes,status) VALUES(?,?,?,?,?,?,'pending')");
        if ($s) {
            $s->bind_param('isssss', $studentId, $studentName, $regNum, $reqType, $docName, $notes);
            if ($s->execute()) {
                logAdmission($conn, 0, $userId, "Requirement Added", "Added '$reqType' for $studentName");
                echo json_encode(['success' => true, 'id' => $s->insert_id]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to add requirement.']);
            }
            $s->close();
        } else {
            echo json_encode(['success' => false, 'message' => 'Database error.']);
        }
        exit;
    }
    if ($action === 'submit_document') {
        $id = (int)($_POST['requirement_id'] ?? 0);
        $docName = trim($_POST['document_name'] ?? '');
        $filePath = '';
        if (isset($_FILES['document_file']) && $_FILES['document_file']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['document_file']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx'];
            if (in_array($ext, $allowed)) {
                $fileName = 'req_' . $id . '_' . time() . '.' . $ext;
                $dest = $uploadDir . $fileName;
                if (move_uploaded_file($_FILES['document_file']['tmp_name'], $dest)) {
                    $filePath = 'uploads/admissions/' . $fileName;
                }
            }
        }
        $s = $conn->prepare("UPDATE student_requirements SET status='submitted',submitted_date=CURDATE(),document_name=COALESCE(NULLIF(?,''),document_name),file_path=COALESCE(NULLIF(?,''),file_path) WHERE id=?");
        if ($s) {
            $s->bind_param('ssi', $docName, $filePath, $id);
            $s->execute();
            $s->close();
        }
        logAdmission($conn, 0, $userId, "Document Submitted", "Requirement #$id submitted");
        echo json_encode(['success' => true]);
        exit;
    }
    if ($action === 'verify_requirement') {
        $id = (int)($_POST['requirement_id'] ?? 0);
        $notes = trim($_POST['notes'] ?? '');
        $s = $conn->prepare("UPDATE student_requirements SET status='verified',verified_date=CURDATE(),verified_by=?,notes=COALESCE(NULLIF(?,''),notes) WHERE id=?");
        if ($s) {
            $s->bind_param('isi', $userId, $notes, $id);
            $s->execute();
            $s->close();
        }
        logAdmission($conn, 0, $userId, "Requirement Verified", "Requirement #$id verified");
        echo json_encode(['success' => true]);
        exit;
    }
    if ($action === 'reject_requirement') {
        $id = (int)($_POST['requirement_id'] ?? 0);
        $reason = trim($_POST['reason'] ?? '');
        $s = $conn->prepare("UPDATE student_requirements SET status='rejected',notes=? WHERE id=?");
        if ($s) {
            $s->bind_param('si', $reason, $id);
            $s->execute();
            $s->close();
        }
        logAdmission($conn, 0, $userId, "Requirement Rejected", "Requirement #$id rejected: $reason");
        echo json_encode(['success' => true]);
        exit;
    }
    if ($action === 'delete_requirement') {
        $id = (int)($_POST['requirement_id'] ?? 0);
        if ($id) {
            $stmt = $conn->prepare("DELETE FROM student_requirements WHERE id=?");
            if ($stmt) { $stmt->bind_param('i', $id); $stmt->execute(); $stmt->close(); }
            logAdmission($conn, 0, $userId, "Requirement Deleted", "Requirement #$id deleted");
        }
        echo json_encode(['success' => true]);
        exit;
    }
    if ($action === 'get_requirements') {
        $where = "1=1";
        $params = [];
        $types = '';
        $fStudent = trim($_POST['filter_student'] ?? '');
        $fStatus = trim($_POST['filter_status'] ?? '');
        $fType = trim($_POST['filter_type'] ?? '');
        if ($fStudent !== '') { $where .= " AND sr.student_name LIKE ?"; $params[] = "%$fStudent%"; $types .= 's'; }
        if ($fStatus !== '' && $fStatus !== 'all') { $where .= " AND sr.status=?"; $params[] = $fStatus; $types .= 's'; }
        if ($fType !== '' && $fType !== 'all') { $where .= " AND sr.requirement_type=?"; $params[] = $fType; $types .= 's'; }
        $sql = "SELECT sr.* FROM student_requirements sr WHERE $where ORDER BY sr.created_at DESC LIMIT 200";
        $s = $conn->prepare($sql);
        $rows = [];
        if ($s) {
            if ($types) $s->bind_param($types, ...$params);
            $s->execute();
            $rows = $s->get_result()->fetch_all(MYSQLI_ASSOC);
            $s->close();
        }
        echo json_encode($rows);
        exit;
    }
    if ($action === 'get_requirement_types') {
        $r = $conn->query("SELECT DISTINCT requirement_type FROM student_requirements ORDER BY requirement_type");
        $types = [];
        if ($r) while ($row = $r->fetch_assoc()) $types[] = $row['requirement_type'];
        echo json_encode($types);
        exit;
    }
    if ($action === 'get_enrolled_students') {
        $rows = [];
        if ($stuConn) {
            $r = $stuConn->query("SELECT id,student_number,registration_number,first_name,surname,full_name,email,phone,program,gender,status,created_at FROM $studentsDb.students WHERE status='Active' ORDER BY full_name LIMIT 300");
            if ($r) $rows = $r->fetch_all(MYSQLI_ASSOC);
        }
        echo json_encode($rows);
        exit;
    }
    if ($action === 'dashboard_stats') {
        $totalApps = $conn->query("SELECT COUNT(*) c FROM applicants")->fetch_assoc()['c'] ?? 0;
        $pendingReviews = adCount($conn, 'New') + adCount($conn, 'Under Review') + adCount($conn, 'Waiting for Documents');
        $enrolled = 0;
        if ($stuConn) {
            $r = $stuConn->query("SELECT COUNT(*) c FROM $studentsDb.students WHERE status='Active'");
            if ($r) $enrolled = (int)$r->fetch_assoc()['c'];
        }
        $reqPending = $conn->query("SELECT COUNT(*) c FROM student_requirements WHERE status IN ('pending','submitted')")->fetch_assoc()['c'] ?? 0;
        echo json_encode([
            'total_apps' => (int)$totalApps,
            'pending_reviews' => (int)$pendingReviews,
            'enrolled' => (int)$enrolled,
            'req_pending' => (int)$reqPending,
        ]);
        exit;
    }

    // --- Direct Register Student (bypass application process) ---
    if ($action === 'direct_register_student') {
        $firstName = trim($_POST['first_name'] ?? '');
        $surname = trim($_POST['surname'] ?? '');
        $gender = trim($_POST['gender'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $program = trim($_POST['program'] ?? '');
        $intake = trim($_POST['intake'] ?? '');
        $dob = trim($_POST['date_of_birth'] ?? '');
        $nationalId = trim($_POST['national_id'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $district = trim($_POST['district'] ?? '');
        $nokName = trim($_POST['next_of_kin_name'] ?? '');
        $nokPhone = trim($_POST['next_of_kin_phone'] ?? '');

        if (!$firstName || !$surname || !$gender || !$phone || !$program || !$intake) {
            echo json_encode(['success' => false, 'message' => 'First name, surname, gender, phone, program, and intake are required.']);
            exit;
        }

        $full_name = trim($firstName . ' ' . $surname);

        // Build intake code from intake name (e.g. "January 2026" -> "JAN26")
        $intakeParts = explode(' ', $intake);
        $intakeMonth = ucfirst(strtolower($intakeParts[0] ?? ''));
        $intakeYearShort = substr($intakeParts[1] ?? date('Y'), -2);
        $monthMap = ['January'=>'JAN','February'=>'FEB','March'=>'MAR','April'=>'APR','May'=>'MAY','June'=>'JUN','July'=>'JUL','August'=>'AUG','September'=>'SEP','October'=>'OCT','November'=>'NOV','December'=>'DEC'];
        $intakeCode = $monthMap[$intakeMonth] ?? strtoupper(substr($intakeMonth, 0, 3));

        // Get program code
        $progCode = 'GEN';
        $progStmt = $conn->prepare("SELECT program_code FROM academic_programs WHERE program_name=? LIMIT 1");
        if ($progStmt) {
            $progStmt->bind_param('s', $program);
            $progStmt->execute();
            $progRes = $progStmt->get_result();
            if ($progRow = $progRes->fetch_assoc()) $progCode = strtoupper(substr($progRow['program_code'], 0, 4));
            $progStmt->close();
        }

        // Get next sequence
        $seqPrefix = $intakeCode . $intakeYearShort . '/' . $progCode . '/' . date('Y');
        $seqStmt = $conn->prepare("SELECT COUNT(*) c FROM student_admission_tracking WHERE student_number LIKE ?");
        $seqLike = $seqPrefix . '/%';
        $nextSeq = 1;
        if ($seqStmt) {
            $seqStmt->bind_param('s', $seqLike);
            $seqStmt->execute();
            $seqRes = $seqStmt->get_result();
            if ($seqRow = $seqRes->fetch_assoc()) $nextSeq = (int)$seqRow['c'] + 1;
            $seqStmt->close();
        }
        $index_number = $seqPrefix . '/' . str_pad($nextSeq, 4, '0', STR_PAD_LEFT);

        // Student number
        $snPrefix = 'ISNM/' . date('Y');
        $snStmt = $conn->prepare("SELECT COUNT(*) c FROM student_admission_tracking WHERE student_number LIKE ?");
        $snLike = $snPrefix . '/%';
        $nextSnSeq = 1;
        if ($snStmt) {
            $snStmt->bind_param('s', $snLike);
            $snStmt->execute();
            $snRes = $snStmt->get_result();
            if ($snRow = $snRes->fetch_assoc()) $nextSnSeq = (int)$snRow['c'] + 1;
            $snStmt->close();
        }
        $student_number = $snPrefix . '/' . str_pad($nextSnSeq, 4, '0', STR_PAD_LEFT);

        // Random temp password
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $temp_password = '';
        for ($i = 0; $i < 8; $i++) { $temp_password .= $chars[random_int(0, strlen($chars) - 1)]; }
        $hashed_password = password_hash($temp_password, PASSWORD_BCRYPT);

        $reg_number = 'REG' . date('Y') . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
        $year = 1;
        $level = 'Year 1';

        $conn->begin_transaction();
        try {
            $rc = 0;
            $ck = $conn->query("SELECT COUNT(*) c FROM admission_requirements WHERE is_active=1");
            if ($ck) { $rc = (int)$ck->fetch_assoc()['c']; }

            $track = $conn->prepare("INSERT INTO student_admission_tracking (student_number, index_number, application_number, full_name, program, intake, admission_date, admission_status, requirements_total) VALUES (?,?,?,?,?,?,?,'Registered',?)");
            $trackAppNum = 'DR-' . date('YmdHis') . '-' . mt_rand(1000, 9999);
            $track->bind_param('sssssssi', $student_number, $index_number, $trackAppNum, $full_name, $program, $intake, date('Y-m-d'), $rc);
            if (!$track->execute()) throw new Exception('Tracking insert failed: ' . $track->error);
            $track->close();

            if ($stuConn) {
                $s_ins = $stuConn->prepare("INSERT IGNORE INTO `$studentsDb`.`students` (student_number, registration_number, first_name, surname, other_name, full_name, email, phone, program, course, year, level, intake_year, intake_period, date_of_birth, gender, address, national_id, district, guardian_name, guardian_phone, status, password, is_first_login) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,'Active',?,0)");
                $s_ins->bind_param('sssssssssssssssssssssss',
                    $student_number, $reg_number, $firstName, $surname, '', $full_name,
                    $email, $phone, $program, $program,
                    $year, $level, (string)date('Y'), $intake, $dob,
                    $gender, $address, $nationalId, $district, $nokName, $nokPhone, $hashed_password
                );
                if (!$s_ins->execute()) throw new Exception('Student insert failed: ' . $s_ins->error);
                $s_id = $stuConn->insert_id;
                $s_ins->close();
                if ($s_id > 0) {
                    $prof = $stuConn->prepare("INSERT IGNORE INTO `$studentsDb`.`student_profiles` (student_id, admission_status, fee_status) VALUES (?,?,?)");
                    $prof->bind_param('iss', $s_id, 'Registered', 'unpaid');
                    if (!$prof->execute()) error_log('prof execute failed: ' . ($prof->error ?? 'unknown'));
                    $prof->close();
                }
            }

            logAdmission($conn, 0, $userId, "Direct Registered", "Directly registered $full_name as $student_number");
            $conn->commit();
            echo json_encode(['success' => true, 'student_number' => $student_number, 'index_number' => $index_number, 'temp_password' => $temp_password]);
        } catch (Exception $e) {
            $conn->rollback();
            echo json_encode(['success' => false, 'message' => 'Registration failed: ' . $e->getMessage()]);
        }
        exit;
    }

    // --- Get Student Profile ---
    if ($action === 'get_student_profile') {
        $studentId = (int)($_POST['student_id'] ?? 0);
        if (!$studentId) { echo json_encode(['success' => false, 'message' => 'Invalid student ID.']); exit; }

        $result = ['student' => null, 'profile' => null, 'requirements' => ['total' => 0, 'completed' => 0, 'pending' => 0, 'rejected' => 0], 'fee_balance' => 0];

        if ($stuConn) {
            $s = $stuConn->prepare("SELECT * FROM `$studentsDb`.`students` WHERE id=? LIMIT 1");
            if ($s) { $s->bind_param('i', $studentId); $s->execute(); $result['student'] = $s->get_result()->fetch_assoc(); $s->close(); }
            if ($result['student']) {
                $p = $stuConn->prepare("SELECT * FROM `$studentsDb`.`student_profiles` WHERE student_id=? LIMIT 1");
                if ($p) { $p->bind_param('i', $studentId); $p->execute(); $result['profile'] = $p->get_result()->fetch_assoc(); $p->close(); }
            }
        }

        $reqQ = $conn->prepare("SELECT status, COUNT(*) cnt FROM student_requirements WHERE student_id=? GROUP BY status");
        if ($reqQ) {
            $reqQ->bind_param('i', $studentId);
            $reqQ->execute();
            $reqRes = $reqQ->get_result();
            while ($rr = $reqRes->fetch_assoc()) {
                $result['requirements']['total'] += (int)$rr['cnt'];
                $sKey = $rr['status'];
                if ($sKey === 'verified') $result['requirements']['completed'] += (int)$rr['cnt'];
                elseif ($sKey === 'pending' || $sKey === 'submitted') $result['requirements']['pending'] += (int)$rr['cnt'];
                elseif ($sKey === 'rejected') $result['requirements']['rejected'] += (int)$rr['cnt'];
            }
            $reqQ->close();
        }

        if ($stuConn) {
            $fn = $result['student']['student_number'] ?? '';
            if ($fn) {
                try {
                    $feeQ = $stuConn->query("SELECT COALESCE(SUM(amount_due - amount_paid),0) AS balance FROM `$studentsDb`.`student_invoices` WHERE student_number='" . $stuConn->real_escape_string($fn) . "' LIMIT 1");
                    if ($feeQ) { $feeRow = $feeQ->fetch_assoc(); $result['fee_balance'] = (float)($feeRow['balance'] ?? 0); }
                } catch (Exception $e) { $result['fee_balance'] = 0; }
            }
        }

        echo json_encode($result);
        exit;
    }

    // --- Get Student Requirements ---
    if ($action === 'get_student_requirements') {
        $studentId = (int)($_POST['student_id'] ?? 0);
        if (!$studentId) { echo json_encode([]); exit; }
        $rows = [];
        $s = $conn->prepare("SELECT * FROM student_requirements WHERE student_id=? ORDER BY created_at DESC");
        if ($s) { $s->bind_param('i', $studentId); $s->execute(); $rows = $s->get_result()->fetch_all(MYSQLI_ASSOC); $s->close(); }
        echo json_encode($rows);
        exit;
    }

    if ($action === 'get_clearance_students') {
        $where = "1=1";
        $params = [];
        $types = '';
        $search = trim($_POST['search'] ?? '');
        $filterField = trim($_POST['filter_field'] ?? '');
        if (strlen($search) >= 2) {
            if ($filterField === 'name') { $where .= " AND sr.student_name LIKE ?"; $params[] = "%$search%"; $types .= 's'; }
            elseif ($filterField === 'admission') { $where .= " AND sr.registration_number LIKE ?"; $params[] = "%$search%"; $types .= 's'; }
            elseif ($filterField === 'phone') { $where .= " AND sr.registration_number LIKE ? OR sr.student_name LIKE ?"; $params[] = "%$search%"; $params[] = "%$search%"; $types .= 'ss'; }
            else { $where .= " AND (sr.student_name LIKE ? OR sr.registration_number LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; $types .= 'ss'; }
        }
        $fStatus = trim($_POST['filter_status'] ?? '');
        if ($fStatus && $fStatus !== 'all') { $where .= " AND sr.status = ?"; $params[] = $fStatus; $types .= 's'; }
        $sql = "SELECT sr.* FROM student_requirements sr WHERE $where ORDER BY sr.student_name, sr.created_at DESC LIMIT 500";
        $s = $conn->prepare($sql);
        $rows = [];
        if ($s) {
            if ($types) $s->bind_param($types, ...$params);
            $s->execute();
            $rows = $s->get_result()->fetch_all(MYSQLI_ASSOC);
            $s->close();
        }
        echo json_encode(['success' => true, 'students' => $rows]);
        exit;
    }
    if ($action === 'toggle_clearance_item') {
        $studentId = (int)($_POST['student_id'] ?? 0);
        $reqType = trim($_POST['requirement_type'] ?? '');
        $newStatus = trim($_POST['new_status'] ?? 'verified');
        if (!$studentId || !$reqType) {
            echo json_encode(['success' => false, 'message' => 'Student ID and requirement type required.']);
            exit;
        }
        // Find or create the requirement record for this student/type
        $stmt = $conn->prepare("SELECT id, status FROM student_requirements WHERE student_id=? AND requirement_type=? LIMIT 1");
        $stmt->bind_param("is", $studentId, $reqType);
        $stmt->execute();
        $existing = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if ($existing) {
            $stmt = $conn->prepare("UPDATE student_requirements SET status=?, verified_date=CASE WHEN ?='verified' THEN CURDATE() ELSE verified_date END, verified_by=?, updated_at=NOW() WHERE id=?");
            $stmt->bind_param("ssii", $newStatus, $newStatus, $userId, $existing['id']);
            $stmt->execute();
            $stmt->close();
        } else {
            $stmt = $conn->prepare("INSERT INTO student_requirements (student_id, student_name, requirement_type, status, verified_date, verified_by) VALUES (?, '', ?, ?, CASE WHEN ?='verified' THEN CURDATE() ELSE NULL END, ?)");
            $sName = '';
            $stmt->bind_param("isssi", $studentId, $reqType, $newStatus, $newStatus, $userId);
            $stmt->execute();
            $stmt->close();
        }
        logAdmission($conn, 0, $userId, "Clearance Updated", "$reqType set to $newStatus for student #$studentId");
        echo json_encode(['success' => true, 'message' => "Requirement $newStatus"]);
        exit;
    }
    if ($action === 'bulk_clearance') {
        $studentId = (int)($_POST['student_id'] ?? 0);
        $items = $_POST['items'] ?? [];
        if (!$studentId || empty($items)) {
            echo json_encode(['success' => false, 'message' => 'No items to clear.']);
            exit;
        }
        foreach ($items as $reqType) {
            $reqType = trim($reqType);
            if (!$reqType) continue;
            $stmt = $conn->prepare("SELECT id FROM student_requirements WHERE student_id=? AND requirement_type=? LIMIT 1");
            $stmt->bind_param("is", $studentId, $reqType);
            $stmt->execute();
            $exists = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if ($exists) {
                $stmt = $conn->prepare("UPDATE student_requirements SET status='verified', verified_date=CURDATE(), verified_by=? WHERE id=?");
                $stmt->bind_param("ii", $userId, $exists['id']);
                $stmt->execute();
                $stmt->close();
            } else {
                $stmt = $conn->prepare("INSERT INTO student_requirements (student_id, student_name, requirement_type, status, verified_date, verified_by) VALUES (?, '', ?, 'verified', CURDATE(), ?)");
                $stmt->bind_param("isi", $studentId, $reqType, $userId);
                $stmt->execute();
                $stmt->close();
            }
        }
        logAdmission($conn, 0, $userId, "Bulk Clearance", count($items) . " items cleared for student #$studentId");
        echo json_encode(['success' => true, 'message' => count($items) . " requirements cleared"]);
        exit;
    }
    if ($action === 'get_clearance_detail') {
        $studentId = (int)($_POST['student_id'] ?? 0);
        $rows = [];
        if ($studentId) {
            $s = $conn->prepare("SELECT * FROM student_requirements WHERE student_id=? ORDER BY created_at DESC");
            if ($s) { $s->bind_param("i", $studentId); $s->execute(); $rows = $s->get_result()->fetch_all(MYSQLI_ASSOC); $s->close(); }
        }
        $studentName = '';
        if (!empty($rows)) { $studentName = $rows[0]['student_name'] ?? ''; }
        echo json_encode(['success' => true, 'requirements' => $rows, 'student_name' => $studentName]);
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Unknown action']);
    exit;
}

// --- Page data ---
$totalApps = $conn->query("SELECT COUNT(*) c FROM applicants")->fetch_assoc()['c'] ?? 0;
$pendingReviews = adCount($conn, 'New') + adCount($conn, 'Under Review') + adCount($conn, 'Waiting for Documents');
$enrolledCount = 0;
if ($stuConn) {
    $r = $stuConn->query("SELECT COUNT(*) c FROM $studentsDb.students WHERE status='Active'");
    if ($r) $enrolledCount = (int)$r->fetch_assoc()['c'];
}
$reqPending = $conn->query("SELECT COUNT(*) c FROM student_requirements WHERE status IN ('pending','submitted')")->fetch_assoc()['c'] ?? 0;

$programs = [];
$r = $conn->query("SELECT * FROM academic_programs WHERE status='Active' ORDER BY program_name");
if ($r) $programs = $r->fetch_all(MYSQLI_ASSOC);
$intakes = [];
$r = $conn->query("SELECT * FROM intakes ORDER BY intake_year DESC, intake_month");
if ($r) $intakes = $r->fetch_all(MYSQLI_ASSOC);

$recentApps = [];
$r = $conn->query("SELECT a.*,ap.program_name FROM applicants a LEFT JOIN academic_programs ap ON a.program_id=ap.id ORDER BY a.created_at DESC LIMIT 10");
if ($r) $recentApps = $r->fetch_all(MYSQLI_ASSOC);

// Get all requirement types from student_requirements
$reqTypes = [];
$r = $conn->query("SELECT DISTINCT requirement_type FROM student_requirements ORDER BY requirement_type");
if ($r) while ($row = $r->fetch_assoc()) $reqTypes[] = $row['requirement_type'];
// Merge in default types
$defaultTypes = ['Admission Letter', 'Academic Transcripts', 'National ID', 'Passport Photo', 'Medical Certificate', 'Birth Certificate', 'Recommendation Letter', 'Fee Receipt'];
$reqTypes = array_unique(array_merge($reqTypes, $defaultTypes));
sort($reqTypes);

$csrfToken = htmlspecialchars($_SESSION['csrf_token'] ?? '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include_once __DIR__ . '/../includes/dashboard_head.php'; ?>
<style>
:root{--adm-primary:#2563EB;--adm-dark:#1d4ed8;--adm-light:#dbeafe}
body{background:#f1f5f9;font-family:'Inter',system-ui,-apple-system,sans-serif}
.da-content{margin-left:270px;padding:24px;min-height:100vh}
.da-header{background:linear-gradient(135deg,#1e40af,#2563eb,#3b82f6);color:#fff;padding:24px 28px;border-radius:16px;margin-bottom:24px;position:relative;overflow:hidden}
.da-header::before{content:'';position:absolute;top:-50%;right:-20%;width:300px;height:300px;border-radius:50%;background:rgba(255,255,255,.06)}
.da-header h1{margin:0;font-size:24px;font-weight:700;letter-spacing:-.3px}
.da-header p{margin:4px 0 0;opacity:.85;font-size:14px}
.stats-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:14px;margin-bottom:24px}
.stat-card{background:#fff;border-radius:12px;padding:18px 20px;border:1px solid #e2e8f0;transition:all .2s;position:relative;overflow:hidden}
.stat-card:hover{transform:translateY(-2px);box-shadow:0 8px 25px rgba(37,99,235,.12);border-color:var(--adm-primary)}
.stat-card .num{font-size:28px;font-weight:700;color:#0f172a;line-height:1.2}
.stat-card .lbl{font-size:12px;color:#64748b;margin-top:2px;font-weight:500}
.stat-card .icon{position:absolute;right:14px;top:50%;transform:translateY(-50%);font-size:32px;opacity:.1;color:var(--adm-primary)}
.da-tabs{display:flex;gap:3px;margin-bottom:24px;background:#fff;padding:6px;border-radius:12px;flex-wrap:wrap;border:1px solid #e2e8f0;overflow-x:auto}
.da-tabs a{padding:10px 20px;border-radius:8px;color:#475569;text-decoration:none;font-size:13px;font-weight:500;transition:all .2s;white-space:nowrap}
.da-tabs a:hover,.da-tabs a.active{background:var(--adm-primary);color:#fff}
.da-card{background:#fff;border-radius:12px;border:1px solid #e2e8f0;padding:20px;margin-bottom:16px;box-shadow:0 1px 3px rgba(0,0,0,.04)}
.da-card h3{margin:0 0 16px;font-size:15px;font-weight:600;color:#0f172a;border-bottom:2px solid #f1f5f9;padding-bottom:12px;display:flex;align-items:center;gap:8px}
.table{font-size:13px;margin-bottom:0}
.table th{font-weight:600;color:#475569;border-bottom:2px solid #f1f5f9;white-space:nowrap}
.table td{vertical-align:middle;color:#334155}
.table-hover tbody tr:hover{background:#f8fafc}
.badge{font-weight:500;font-size:11px;padding:4px 10px;border-radius:6px}
.bg-purple{background:#7C3AED;color:#fff}
.btn-sm{border-radius:6px;font-size:12px;padding:4px 12px;font-weight:500}
.filter-row{display:flex;gap:10px;flex-wrap:wrap;align-items:center;margin-bottom:16px}
.filter-row>*{min-width:140px}
.empty-state{text-align:center;padding:40px 20px;color:#94a3b8}
.empty-state i{font-size:48px;margin-bottom:12px;opacity:.3}
@media(max-width:768px){
.da-content{margin-left:0;padding:12px}
.da-header{padding:16px;border-radius:12px}
.da-header h1{font-size:18px}
.da-tabs{padding:4px;gap:2px;flex-wrap:nowrap;overflow-x:auto;-webkit-overflow-scrolling:touch;scrollbar-width:none}
.da-tabs::-webkit-scrollbar{display:none}
.da-tabs a{padding:6px 10px;font-size:11px;white-space:nowrap}
.stats-grid{grid-template-columns:repeat(2,1fr);gap:10px}
.stat-card{padding:14px}
.stat-card .num{font-size:22px}
.da-card{padding:14px;border-radius:10px}
.da-card h3{font-size:13px}
.table{font-size:12px}
.table td,.table th{padding:6px 8px}
.filter-row{gap:8px}
.filter-row>*{min-width:100%}
}
@media(max-width:480px){
.stats-grid{grid-template-columns:1fr;gap:8px}
.da-header h1{font-size:16px}
.stat-card .num{font-size:20px}
.da-card{padding:10px}
.table{font-size:11px}
.table td,.table th{padding:4px 6px}
}
.isnm-sidebar.sidebar{position:fixed;top:0;left:0;z-index:1050}
.isnm-sidebar.sidebar .sidebar-menu{flex:1;overflow-y:auto;overflow-x:hidden;padding:8px 0}
.isnm-sidebar.sidebar .menu-group-header{cursor:pointer;user-select:none;padding:10px 16px;display:flex;align-items:center;gap:10px}
.isnm-sidebar.sidebar .menu-children{max-height:0;overflow:hidden;transition:max-height 0.3s ease}
.isnm-sidebar.sidebar .menu-group.expanded .menu-children{max-height:1000px}
.isnm-sidebar.sidebar .child-link{display:flex;align-items:center;gap:10px;padding:9px 16px 9px 44px;color:rgba(255,255,255,0.7);text-decoration:none;font-size:13px;transition:all 0.15s;border-left:3px solid transparent}
.isnm-sidebar.sidebar .child-link:hover{background:rgba(255,255,255,0.08);color:#fff}
.isnm-sidebar.sidebar .child-link.active{background:rgba(255,255,255,0.1);color:#fff;border-left-color:#3b82f6}
.isnm-sidebar.sidebar .menu-chevron{transition:transform 0.2s;font-size:10px}
.isnm-sidebar.sidebar .menu-group.expanded .menu-chevron{transform:rotate(180deg)}
@media(max-width:768px){
  .isnm-sidebar.sidebar{transform:translateX(-100%);width:280px!important;box-shadow:4px 0 20px rgba(0,0,0,0.15)}
  .isnm-sidebar.sidebar.open,.isnm-sidebar.sidebar.active{transform:translateX(0)}
  .da-content{margin-left:0!important}
}
</style>
</head>
<body>
<?php include_once __DIR__ . '/../includes/sidebar.php'; ?>
<?php include_once __DIR__ . '/../includes/dashboard_topbar.php'; renderDashboardTopbar('Director of Admissions'); ?>
<div class="da-content">
<?php if (isset($_SESSION['success'])): ?><div class="alert alert-success alert-dismissible"><?=$_SESSION['success']?><button class="btn-close" data-bs-dismiss="alert"></button></div><?php unset($_SESSION['success']); endif; ?>

<div class="da-header">
  <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
    <div>
      <h1>Director of Admissions</h1>
      <p><?= htmlspecialchars($userName) ?> &middot; Admissions Management</p>
    </div>
    <div class="d-flex gap-2">
      <span class="badge bg-light text-dark" style="font-size:12px"><i class="fas fa-calendar"></i> <?= date('d M Y') ?></span>
    </div>
  </div>
</div>

<nav class="da-tabs">
  <a href="?tab=applications" class="<?= $tab === 'applications' ? 'active' : '' ?>"><i class="fas fa-file-alt"></i> Applications</a>
  <a href="?tab=requirements" class="<?= $tab === 'requirements' ? 'active' : '' ?>"><i class="fas fa-clipboard-check"></i> Requirements</a>
  <a href="?tab=clearance" class="<?= $tab === 'clearance' ? 'active' : '' ?>"><i class="fas fa-check-double"></i> Requirements Clearance</a>
  <a href="?tab=enrolled" class="<?= $tab === 'enrolled' ? 'active' : '' ?>"><i class="fas fa-user-graduate"></i> Enrolled Students</a>
</nav>

<!-- ======================== STATS ======================== -->
<div class="stats-grid">
  <div class="stat-card">
    <div class="num"><?= number_format($totalApps) ?></div>
    <div class="lbl">Total Applications</div>
    <i class="fas fa-file-alt icon"></i>
  </div>
  <div class="stat-card">
    <div class="num" style="color:#d97706"><?= number_format($pendingReviews) ?></div>
    <div class="lbl">Pending Reviews</div>
    <i class="fas fa-clock icon"></i>
  </div>
  <div class="stat-card">
    <div class="num" style="color:#059669"><?= number_format($enrolledCount) ?></div>
    <div class="lbl">Enrolled Students</div>
    <i class="fas fa-user-graduate icon"></i>
  </div>
  <div class="stat-card">
    <div class="num" style="color:#dc2626"><?= number_format($reqPending) ?></div>
    <div class="lbl">Requirements Pending</div>
    <i class="fas fa-clipboard-list icon"></i>
  </div>
</div>

<!-- ======================== APPLICATIONS TAB ======================== -->
<?php if ($tab === 'applications'): ?>
<div class="da-card">
  <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <h3 style="border:none;padding:0;margin:0"><i class="fas fa-file-alt"></i> Applications</h3>
  </div>
  <div class="filter-row">
    <input class="form-control form-control-sm" style="width:200px" id="appSearch" placeholder="Search name, email, phone..." oninput="filterApps()">
    <select class="form-select form-select-sm" id="filterStatus" onchange="filterApps()">
      <option value="all">All Statuses</option>
      <?php foreach (['New', 'Under Review', 'Waiting for Documents', 'Requirements Verified', 'Approved', 'Rejected', 'Registered'] as $s): ?>
        <option value="<?= $s ?>"><?= $s ?></option>
      <?php endforeach; ?>
    </select>
    <select class="form-select form-select-sm" id="filterProgram" onchange="filterApps()">
      <option value="all">All Programs</option>
      <?php foreach ($programs as $p): ?>
        <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['program_name']) ?></option>
      <?php endforeach; ?>
    </select>
    <select class="form-select form-select-sm" id="filterIntake" onchange="filterApps()">
      <option value="all">All Intakes</option>
      <?php foreach ($intakes as $i): ?>
        <option value="<?= htmlspecialchars($i['intake_name']) ?>"><?= htmlspecialchars($i['intake_name']) ?></option>
      <?php endforeach; ?>
    </select>
    <button class="btn btn-sm btn-success ms-auto" onclick="showDirectRegisterModal()"><i class="fas fa-user-plus"></i> Register New Student</button>
  </div>
  <div class="table-responsive">
    <table class="table table-hover table-sm">
      <thead>
        <tr><th>App #</th><th>Name</th><th>Contact</th><th>Program</th><th>Intake</th><th>Status</th><th>Date</th><th>Actions</th></tr>
      </thead>
      <tbody id="appTableBody">
        <tr><td colspan="8" class="text-center text-muted py-3"><i class="fas fa-spinner fa-spin"></i> Loading...</td></tr>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<!-- ======================== REQUIREMENTS TAB ======================== -->
<?php if ($tab === 'requirements'): ?>
<div class="da-card">
  <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <h3 style="border:none;padding:0;margin:0"><i class="fas fa-clipboard-check"></i> Requirements Management</h3>
    <button class="btn btn-sm btn-primary" onclick="showAddRequirementModal()"><i class="fas fa-plus"></i> Add Requirement</button>
  </div>
  <div class="filter-row">
    <input class="form-control form-control-sm" style="width:200px" id="reqFilterStudent" placeholder="Filter by student name..." oninput="loadRequirements()">
    <select class="form-select form-select-sm" id="reqFilterStatus" onchange="loadRequirements()">
      <option value="all">All Statuses</option>
      <option value="pending">Pending</option>
      <option value="submitted">Submitted</option>
      <option value="verified">Verified</option>
      <option value="rejected">Rejected</option>
    </select>
    <select class="form-select form-select-sm" id="reqFilterType" onchange="loadRequirements()">
      <option value="all">All Types</option>
      <?php foreach ($reqTypes as $rt): ?>
        <option value="<?= htmlspecialchars($rt) ?>"><?= htmlspecialchars($rt) ?></option>
      <?php endforeach; ?>
    </select>
    <button class="btn btn-sm btn-outline-secondary" onclick="clearReqFilters()"><i class="fas fa-times"></i> Clear</button>
  </div>
  <div class="table-responsive">
    <table class="table table-hover table-sm">
      <thead>
        <tr><th>Student</th><th>Reg #</th><th>Requirement Type</th><th>Document</th><th>Status</th><th>Submitted</th><th>Verified</th><th>Actions</th></tr>
      </thead>
      <tbody id="reqTableBody">
        <tr><td colspan="8" class="text-center text-muted py-3"><i class="fas fa-spinner fa-spin"></i> Loading...</td></tr>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<!-- ======================== REQUIREMENTS CLEARANCE TAB ======================== -->
<?php if ($tab === 'clearance'): ?>
<?php
$clearanceReqs = [
    'Surgical Gloves', 'Examination Gloves', 'Photocopying Ream', 'Ruled Paper Reams',
    'Omo', 'Toilet Papers', 'Compound Brooms', 'Soft Brooms', 'Rake',
    'Cobweb Brush', 'Scrubbing Brush', 'Squeezer', 'Toilet Brush',
    'JIK', 'Vim', 'Mops', 'Sanitizer', 'Liquid Soap', 'Face Masks', 'Heavy Duty Gloves'
];
?>
<div class="da-card">
  <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <h3 style="border:none;padding:0;margin:0"><i class="fas fa-check-double"></i> Requirements Clearance Portal</h3>
    <div class="d-flex gap-2">
      <button class="btn btn-sm btn-outline-primary" onclick="refreshClearanceList()"><i class="fas fa-sync"></i> Refresh</button>
    </div>
  </div>
  <div class="filter-row">
    <div class="input-group" style="width:250px">
      <span class="input-group-text bg-white"><i class="fas fa-search"></i></span>
      <input class="form-control form-control-sm" id="clearanceSearch" placeholder="Search student name, admission #..." oninput="debounceClearance()">
    </div>
    <select class="form-select form-select-sm" id="clearanceFilterField" style="width:160px" onchange="refreshClearanceList()">
      <option value="name">By Name</option>
      <option value="admission">By Admission #</option>
      <option value="phone">By Phone</option>
    </select>
    <select class="form-select form-select-sm" id="clearanceFilterStatus" style="width:160px" onchange="refreshClearanceList()">
      <option value="all">All Status</option>
      <option value="verified">Cleared</option>
      <option value="pending">Pending</option>
      <option value="submitted">Submitted</option>
      <option value="rejected">Rejected</option>
    </select>
    <button class="btn btn-sm btn-outline-secondary" onclick="clearClearanceFilters()"><i class="fas fa-times"></i> Clear</button>
  </div>
</div>

<div id="clearanceResults" class="text-center py-4"><i class="fas fa-spinner fa-spin"></i> Loading students...</div>
<?php endif; ?>

<!-- Clearance Detail Modal -->
<div class="modal fade" id="clearanceDetailModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header" style="background:linear-gradient(135deg,#059669,#10b981);color:#fff">
        <h5 class="modal-title"><i class="fas fa-check-double"></i> <span id="clearanceDetailTitle">Student Clearance</span></h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="clearanceDetailBody">
        <div class="text-center py-4"><i class="fas fa-spinner fa-spin"></i> Loading...</div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Close</button>
        <button type="button" class="btn btn-sm btn-success" onclick="bulkClearAll()"><i class="fas fa-check-double"></i> Mark All Cleared</button>
      </div>
    </div>
  </div>
</div>

<!-- ======================== ENROLLED STUDENTS TAB ======================== -->
<?php if ($tab === 'enrolled'): ?>
<div class="da-card">
  <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <h3 style="border:none;padding:0;margin:0"><i class="fas fa-user-graduate"></i> Enrolled Students</h3>
  </div>
  <div class="filter-row">
    <input class="form-control form-control-sm" style="width:200px" id="enrolledSearch" placeholder="Search students..." oninput="loadEnrolled()">
  </div>
  <div class="table-responsive">
    <table class="table table-hover table-sm">
      <thead>
        <tr><th>Student #</th><th>Name</th><th>Program</th><th>Gender</th><th>Email</th><th>Phone</th><th>Status</th><th>Enrolled</th><th>Actions</th></tr>
      </thead>
      <tbody id="enrolledTableBody">
        <tr><td colspan="9" class="text-center text-muted py-3"><i class="fas fa-spinner fa-spin"></i> Loading...</td></tr>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<!-- ======================== MODALS ======================== -->

<!-- Add Requirement Modal -->
<div class="modal fade" id="addReqModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header" style="background:linear-gradient(135deg,#1e40af,#2563eb);color:#fff">
        <h5 class="modal-title"><i class="fas fa-plus-circle"></i> Add Requirement</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form id="addReqForm" onsubmit="submitAddRequirement(event)">
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label small fw-bold">Select Student *</label>
            <select class="form-select" id="addReqStudent" required>
              <option value="">-- Select Student --</option>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label small fw-bold">Registration Number</label>
            <input type="text" class="form-control form-control-sm" id="addReqRegNum" placeholder="Auto-filled from student">
          </div>
          <div class="mb-3">
            <label class="form-label small fw-bold">Requirement Type *</label>
            <select class="form-select" id="addReqType" required>
              <option value="">-- Select Type --</option>
              <?php foreach ($defaultTypes as $dt): ?>
                <option value="<?= htmlspecialchars($dt) ?>"><?= htmlspecialchars($dt) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label small fw-bold">Document Name</label>
            <input type="text" class="form-control form-control-sm" id="addReqDocName" placeholder="e.g. UACE Certificate 2024">
          </div>
          <div class="mb-3">
            <label class="form-label small fw-bold">Notes</label>
            <textarea class="form-control form-control-sm" id="addReqNotes" rows="2" placeholder="Optional notes"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <div id="addReqMsg" class="small me-auto"></div>
          <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-save"></i> Add Requirement</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Submit Document Modal -->
<div class="modal fade" id="submitDocModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header" style="background:linear-gradient(135deg,#0369a1,#0284c7);color:#fff">
        <h5 class="modal-title"><i class="fas fa-upload"></i> Submit Document</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form id="submitDocForm" onsubmit="submitDocument(event)" enctype="multipart/form-data">
        <div class="modal-body">
          <input type="hidden" id="submitDocReqId">
          <div class="mb-3">
            <label class="form-label small fw-bold">Document Name</label>
            <input type="text" class="form-control form-control-sm" id="submitDocName" placeholder="Document name">
          </div>
          <div class="mb-3">
            <label class="form-label small fw-bold">Upload File</label>
            <input type="file" class="form-control form-control-sm" id="submitDocFile" accept=".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx">
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-sm btn-info text-white"><i class="fas fa-upload"></i> Submit</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- View Document Modal -->
<div class="modal fade" id="viewDocModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fas fa-file-alt"></i> Document Details</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="viewDocBody">
        <p class="text-muted">Loading...</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<!-- Reject Reason Modal -->
<div class="modal fade" id="rejectReasonModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header" style="background:linear-gradient(135deg,#dc2626,#ef4444);color:#fff">
        <h5 class="modal-title"><i class="fas fa-times-circle"></i> Reject Requirement</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="rejectReqId">
        <div class="mb-3">
          <label class="form-label small fw-bold">Reason for Rejection *</label>
          <textarea class="form-control form-control-sm" id="rejectReason" rows="3" placeholder="Enter rejection reason..." required></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-sm btn-danger" onclick="confirmReject()"><i class="fas fa-times"></i> Reject</button>
      </div>
    </div>
  </div>
</div>

<!-- Review Application Modal -->
<div class="modal fade" id="reviewAppModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header" style="background:linear-gradient(135deg,#1e40af,#2563eb);color:#fff">
        <h5 class="modal-title"><i class="fas fa-user"></i> <span id="reviewAppName">Applicant Details</span></h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="reviewAppBody">
        <p class="text-muted">Loading...</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Close</button>
        <button type="button" class="btn btn-sm btn-warning" id="reviewRejectBtn" onclick="showRejectFromReview()"><i class="fas fa-times"></i> Reject</button>
        <button type="button" class="btn btn-sm btn-success" id="reviewApproveBtn" onclick="approveFromReview()"><i class="fas fa-check"></i> Approve</button>
        <button type="button" class="btn btn-sm btn-dark" id="reviewRegisterBtn" onclick="registerFromReview()"><i class="fas fa-user-graduate"></i> Register</button>
      </div>
    </div>
  </div>
</div>

<!-- Confirm Reject Application Modal -->
<div class="modal fade" id="rejectAppModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header" style="background:linear-gradient(135deg,#dc2626,#ef4444);color:#fff">
        <h5 class="modal-title"><i class="fas fa-times-circle"></i> Reject Application</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="rejectAppId">
        <div class="mb-3">
          <label class="form-label small fw-bold">Rejection Reason *</label>
          <textarea class="form-control form-control-sm" id="rejectAppReason" rows="3" placeholder="Enter reason..." required></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-sm btn-danger" onclick="confirmRejectApp()"><i class="fas fa-times"></i> Reject</button>
      </div>
    </div>
  </div>
</div>

<!-- Direct Register New Student Modal -->
<div class="modal fade" id="directRegisterModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header" style="background:linear-gradient(135deg,#059669,#10b981);color:#fff">
        <h5 class="modal-title"><i class="fas fa-user-plus"></i> Register New Student</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form id="directRegisterForm" onsubmit="submitDirectRegister(event)">
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label small fw-bold">First Name *</label>
              <input type="text" class="form-control form-control-sm" id="dr_first_name" required>
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-bold">Surname *</label>
              <input type="text" class="form-control form-control-sm" id="dr_surname" required>
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-bold">Gender *</label>
              <select class="form-select form-select-sm" id="dr_gender" required>
                <option value="">-- Select --</option>
                <option value="Male">Male</option>
                <option value="Female">Female</option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-bold">Date of Birth</label>
              <input type="date" class="form-control form-control-sm" id="dr_date_of_birth">
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-bold">Phone *</label>
              <input type="text" class="form-control form-control-sm" id="dr_phone" required>
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-bold">Email</label>
              <input type="email" class="form-control form-control-sm" id="dr_email">
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-bold">Program *</label>
              <select class="form-select form-select-sm" id="dr_program" required>
                <option value="">-- Select Program --</option>
                <?php foreach ($programs as $p): ?>
                  <option value="<?= htmlspecialchars($p['program_name']) ?>"><?= htmlspecialchars($p['program_name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-bold">Intake *</label>
              <select class="form-select form-select-sm" id="dr_intake" required>
                <option value="">-- Select Intake --</option>
                <?php foreach ($intakes as $i): ?>
                  <option value="<?= htmlspecialchars($i['intake_name']) ?>"><?= htmlspecialchars($i['intake_name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-bold">National ID</label>
              <input type="text" class="form-control form-control-sm" id="dr_national_id">
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-bold">Address</label>
              <input type="text" class="form-control form-control-sm" id="dr_address">
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-bold">District</label>
              <input type="text" class="form-control form-control-sm" id="dr_district">
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-bold">Next of Kin Name</label>
              <input type="text" class="form-control form-control-sm" id="dr_next_of_kin_name">
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-bold">Next of Kin Phone</label>
              <input type="text" class="form-control form-control-sm" id="dr_next_of_kin_phone">
            </div>
          </div>
          <div id="directRegisterResult" class="mt-3"></div>
        </div>
        <div class="modal-footer">
          <div id="directRegisterMsg" class="small me-auto"></div>
          <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-sm btn-success" id="directRegisterBtn"><i class="fas fa-save"></i> Register Student</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Student Profile Modal -->
<div class="modal fade" id="studentProfileModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header" style="background:linear-gradient(135deg,#1e40af,#2563eb);color:#fff">
        <h5 class="modal-title"><i class="fas fa-user"></i> Student Profile</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="studentProfileBody">
        <div class="text-center py-4"><i class="fas fa-spinner fa-spin"></i> Loading...</div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Close</button>
        <button type="button" class="btn btn-sm btn-primary" onclick="printStudentProfile()"><i class="fas fa-print"></i> Print</button>
      </div>
    </div>
  </div>
</div>

</div><!-- /.da-content -->

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
const CSRF = '<?= $csrfToken ?>';
let _reviewAppId = 0;

function postData(data) {
  data.csrf_token = CSRF;
  return fetch('director-admissions.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: new URLSearchParams(data)
  }).then(r => r.json());
}

function showToast(message, type) {
  type = type || 'info';
  const toastId = 'toast-' + Date.now();
  const toastHtml = '<div id="' + toastId + '" class="toast align-items-center text-bg-' + type + ' border-0" role="alert"><div class="d-flex"><div class="toast-body">' + message.replace(/\n/g, '<br>') + '</div><button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button></div></div>';
  let container = document.getElementById('toastContainer');
  if (!container) {
    container = document.createElement('div');
    container.id = 'toastContainer';
    container.className = 'toast-container position-fixed bottom-0 end-0 p-3';
    container.style.zIndex = '9999';
    document.body.appendChild(container);
  }
  container.insertAdjacentHTML('beforeend', toastHtml);
  const toastEl = document.getElementById(toastId);
  const toast = new bootstrap.Toast(toastEl, {delay: 4000});
  toast.show();
  toastEl.addEventListener('hidden.bs.toast', function() { this.remove(); });
}

function escapeHtml(text) {
  const div = document.createElement('div');
  div.textContent = text || '';
  return div.innerHTML;
}

// ===================== APPLICATIONS =====================
function loadApplicants() {
  const q = document.getElementById('appSearch').value;
  const st = document.getElementById('filterStatus').value;
  const pg = document.getElementById('filterProgram').value;
  const in_ = document.getElementById('filterIntake').value;
  postData({action: 'filter_applicants', search: q, status: st, program_id: pg, intake: in_, limit: 200}).then(d => {
    const tbody = document.getElementById('appTableBody');
    if (!d || d.length === 0) {
      tbody.innerHTML = '<tr><td colspan="8" class="text-muted text-center py-4"><i class="fas fa-inbox"></i> No applications found.</td></tr>';
      return;
    }
    tbody.innerHTML = d.map(a => {
      const statusBadge = getStatusBadgeHtml(a.status);
      return '<tr>' +
        '<td><span class="text-muted small">' + escapeHtml(a.application_number) + '</span></td>' +
        '<td><strong>' + escapeHtml(a.full_name) + '</strong></td>' +
        '<td><small>' + escapeHtml(a.email || '') + '<br>' + escapeHtml(a.phone || '') + '</small></td>' +
        '<td>' + escapeHtml(a.program_name || '-') + '</td>' +
        '<td>' + escapeHtml(a.intake || '-') + '</td>' +
        '<td>' + statusBadge + '</td>' +
        '<td class="text-muted small">' + new Date(a.created_at).toLocaleDateString() + '</td>' +
        '<td><div class="d-flex gap-1">' +
          '<button class="btn btn-sm btn-outline-primary py-0 px-1" onclick="reviewApplication(' + a.id + ')" title="Review"><i class="fas fa-eye"></i></button>' +
          '<button class="btn btn-sm btn-outline-success py-0 px-1" onclick="quickApprove(' + a.id + ')" title="Approve"><i class="fas fa-check"></i></button>' +
          '<button class="btn btn-sm btn-outline-danger py-0 px-1" onclick="showRejectApplication(' + a.id + ')" title="Reject"><i class="fas fa-times"></i></button>' +
        '</div></td>' +
      '</tr>';
    }).join('');
  });
}

function getStatusBadgeHtml(status) {
  const m = {'New':'bg-primary','Under Review':'bg-info','Waiting for Documents':'bg-warning text-dark','Requirements Verified':'bg-success','Interview Scheduled':'bg-purple','Approved':'bg-success','Rejected':'bg-danger','Registered':'bg-dark','Withdrawn':'bg-secondary'};
  return '<span class="badge ' + (m[status] || 'bg-secondary') + '">' + escapeHtml(status) + '</span>';
}

function filterApps() { loadApplicants(); }

function reviewApplication(id) {
  _reviewAppId = id;
  document.getElementById('reviewAppBody').innerHTML = '<div class="text-center py-4"><i class="fas fa-spinner fa-spin"></i> Loading...</div>';
  new bootstrap.Modal(document.getElementById('reviewAppModal')).show();
  postData({action: 'filter_applicants', search: '', status: '', limit: 1}).then(() => {
    // fetch specific applicant
    fetch('director-admissions.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/x-www-form-urlencoded'},
      body: 'action=filter_applicants&search=&status=&limit=200&csrf_token=' + encodeURIComponent(CSRF)
    }).then(r => r.json()).then(all => {
      const a = all.find(x => x.id == id);
      if (!a) { document.getElementById('reviewAppBody').innerHTML = '<p class="text-danger">Applicant not found.</p>'; return; }
      document.getElementById('reviewAppName').textContent = a.full_name;
      const st = a.status;
      document.getElementById('reviewApproveBtn').style.display = (st === 'Rejected' || st === 'Registered') ? 'none' : '';
      document.getElementById('reviewRejectBtn').style.display = (st === 'Rejected' || st === 'Registered') ? 'none' : '';
      document.getElementById('reviewRegisterBtn').style.display = (st === 'Approved') ? '' : 'none';
      let html = '<div class="row">' +
        '<div class="col-md-6"><div class="mb-2"><strong>Application #:</strong> ' + escapeHtml(a.application_number) + '</div>' +
        '<div class="mb-2"><strong>Name:</strong> ' + escapeHtml(a.full_name) + '</div>' +
        '<div class="mb-2"><strong>Gender:</strong> ' + escapeHtml(a.gender || '-') + '</div>' +
        '<div class="mb-2"><strong>Date of Birth:</strong> ' + escapeHtml(a.date_of_birth || '-') + '</div>' +
        '<div class="mb-2"><strong>Nationality:</strong> ' + escapeHtml(a.nationality || '-') + '</div>' +
        '<div class="mb-2"><strong>District:</strong> ' + escapeHtml(a.district || '-') + '</div></div>' +
        '<div class="col-md-6"><div class="mb-2"><strong>Email:</strong> ' + escapeHtml(a.email || '-') + '</div>' +
        '<div class="mb-2"><strong>Phone:</strong> ' + escapeHtml(a.phone || '-') + '</div>' +
        '<div class="mb-2"><strong>Program:</strong> ' + escapeHtml(a.program_name || '-') + '</div>' +
        '<div class="mb-2"><strong>Intake:</strong> ' + escapeHtml(a.intake || '-') + '</div>' +
        '<div class="mb-2"><strong>Source:</strong> ' + escapeHtml(a.application_source || '-') + '</div>' +
        '<div class="mb-2"><strong>Status:</strong> ' + getStatusBadgeHtml(a.status) + '</div></div></div>';
      if (a.guardian_name) {
        html += '<hr><h6 class="fw-bold">Guardian Info</h6>' +
          '<div class="row"><div class="col-md-6"><div class="mb-2"><strong>Guardian:</strong> ' + escapeHtml(a.guardian_name) + '</div>' +
          '<div class="mb-2"><strong>Guardian Phone:</strong> ' + escapeHtml(a.guardian_phone || '-') + '</div></div>' +
          '<div class="col-md-6"><div class="mb-2"><strong>Emergency:</strong> ' + escapeHtml(a.emergency_contact_name || '-') + '</div>' +
          '<div class="mb-2"><strong>Emergency Phone:</strong> ' + escapeHtml(a.emergency_contact_phone || '-') + '</div></div></div>';
      }
      if (a.rejection_reason) {
        html += '<div class="alert alert-danger mt-2"><strong>Rejection Reason:</strong> ' + escapeHtml(a.rejection_reason) + '</div>';
      }
      document.getElementById('reviewAppBody').innerHTML = html;
    });
  });
}

function quickApprove(id) {
  if (!confirm('Approve this application?')) return;
  postData({action: 'approve', id: id}).then(d => {
    if (d.success) { showToast('Application approved!', 'success'); loadApplicants(); }
    else { showToast('Failed to approve.', 'danger'); }
  });
}

function showRejectApplication(id) {
  document.getElementById('rejectAppId').value = id;
  document.getElementById('rejectAppReason').value = '';
  new bootstrap.Modal(document.getElementById('rejectAppModal')).show();
}

function showRejectFromReview() {
  const id = _reviewAppId;
  bootstrap.Modal.getInstance(document.getElementById('reviewAppModal')).hide();
  showRejectApplication(id);
}

function confirmRejectApp() {
  const id = document.getElementById('rejectAppId').value;
  const reason = document.getElementById('rejectAppReason').value.trim();
  if (!reason) { alert('Please enter a rejection reason.'); return; }
  postData({action: 'reject', id: id, reason: reason}).then(d => {
    if (d.success) { showToast('Application rejected.', 'success'); bootstrap.Modal.getInstance(document.getElementById('rejectAppModal')).hide(); loadApplicants(); }
    else { showToast('Failed to reject.', 'danger'); }
  });
}

function approveFromReview() {
  if (!confirm('Approve this application?')) return;
  postData({action: 'approve', id: _reviewAppId}).then(d => {
    if (d.success) {
      showToast('Application approved!', 'success');
      bootstrap.Modal.getInstance(document.getElementById('reviewAppModal')).hide();
      loadApplicants();
    }
  });
}

function registerFromReview() {
  if (!confirm('Register this applicant as a student? This will create a student record visible to all dashboards.')) return;
  postData({action: 'register_student', id: _reviewAppId}).then(d => {
    if (d.success) {
      let msg = 'Student registered successfully! Number: ' + d.student_number;
      if (d.portal_password) msg += '\n\nPortal Username: ' + d.portal_username + '\nPortal Password: ' + d.portal_password;
      alert(msg);
      showToast('Student registered!', 'success');
      bootstrap.Modal.getInstance(document.getElementById('reviewAppModal')).hide();
      loadApplicants();
    } else {
      showToast(d.message || 'Registration failed.', 'danger');
    }
  });
}

// ===================== REQUIREMENTS =====================
function loadRequirements() {
  const student = document.getElementById('reqFilterStudent').value;
  const status = document.getElementById('reqFilterStatus').value;
  const type = document.getElementById('reqFilterType').value;
  postData({action: 'get_requirements', filter_student: student, filter_status: status, filter_type: type}).then(d => {
    const tbody = document.getElementById('reqTableBody');
    if (!d || d.length === 0) {
      tbody.innerHTML = '<tr><td colspan="8" class="text-center text-muted py-4"><i class="fas fa-clipboard-list"></i> No requirements found. Click "Add Requirement" to get started.</td></tr>';
      return;
    }
    tbody.innerHTML = d.map(r => {
      const statusBadge = getReqStatusBadge(r.status);
      return '<tr>' +
        '<td><strong>' + escapeHtml(r.student_name) + '</strong></td>' +
        '<td class="small">' + escapeHtml(r.registration_number || '-') + '</td>' +
        '<td><span class="badge bg-info text-dark">' + escapeHtml(r.requirement_type) + '</span></td>' +
        '<td class="small">' + escapeHtml(r.document_name || '-') + '</td>' +
        '<td>' + statusBadge + '</td>' +
        '<td class="small text-muted">' + (r.submitted_date || '-') + '</td>' +
        '<td class="small text-muted">' + (r.verified_date || '-') + '</td>' +
        '<td><div class="d-flex gap-1 flex-wrap">' +
          (r.status === 'pending' ? '<button class="btn btn-sm btn-outline-info py-0 px-1" onclick="showSubmitDoc(' + r.id + ',\'' + escapeHtml(r.document_name || '') + '\')" title="Submit Document"><i class="fas fa-upload"></i></button>' : '') +
          (r.status === 'submitted' ? '<button class="btn btn-sm btn-outline-success py-0 px-1" onclick="verifyReq(' + r.id + ')" title="Verify"><i class="fas fa-check"></i></button>' : '') +
          (r.status !== 'rejected' && r.status !== 'verified' ? '<button class="btn btn-sm btn-outline-warning py-0 px-1" onclick="showRejectReq(' + r.id + ')" title="Reject"><i class="fas fa-times"></i></button>' : '') +
          '<button class="btn btn-sm btn-outline-primary py-0 px-1" onclick="viewReqDoc(' + r.id + ')" title="View Details"><i class="fas fa-eye"></i></button>' +
          '<button class="btn btn-sm btn-outline-danger py-0 px-1" onclick="deleteReq(' + r.id + ')" title="Delete"><i class="fas fa-trash"></i></button>' +
        '</div></td>' +
      '</tr>';
    }).join('');
  });
}

function getReqStatusBadge(status) {
  const m = {
    'pending': 'bg-warning text-dark',
    'submitted': 'bg-info',
    'verified': 'bg-success',
    'rejected': 'bg-danger'
  };
  return '<span class="badge ' + (m[status] || 'bg-secondary') + '">' + (status || 'unknown').toUpperCase() + '</span>';
}

function clearReqFilters() {
  document.getElementById('reqFilterStudent').value = '';
  document.getElementById('reqFilterStatus').value = 'all';
  document.getElementById('reqFilterType').value = 'all';
  loadRequirements();
}

function showAddRequirementModal() {
  document.getElementById('addReqForm').reset();
  document.getElementById('addReqMsg').textContent = '';
  // Load students into select
  const sel = document.getElementById('addReqStudent');
  sel.innerHTML = '<option value="">Loading students...</option>';
  fetch('director-admissions.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: 'action=get_enrolled_students&csrf_token=' + encodeURIComponent(CSRF)
  }).then(r => r.json()).then(students => {
    sel.innerHTML = '<option value="">-- Select Student --</option>';
    students.forEach(s => {
      const opt = document.createElement('option');
      opt.value = s.id;
      opt.dataset.name = s.full_name || (s.first_name + ' ' + (s.surname || ''));
      opt.dataset.reg = s.registration_number || '';
      opt.textContent = (s.full_name || s.first_name + ' ' + (s.surname || '')) + ' (' + (s.student_number || '') + ')';
      sel.appendChild(opt);
    });
    // Also fetch applicants
    fetch('director-admissions.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/x-www-form-urlencoded'},
      body: 'action=filter_applicants&search=&status=&limit=200&csrf_token=' + encodeURIComponent(CSRF)
    }).then(r2 => r2.json()).then(apps => {
      if (apps && apps.length) {
        const grp = document.createElement('optgroup');
        grp.label = 'Applicants';
        apps.forEach(a => {
          const opt = document.createElement('option');
          opt.value = 'app_' + a.id;
          opt.dataset.name = a.full_name;
          opt.dataset.reg = a.application_number || '';
          opt.textContent = a.full_name + ' (' + (a.application_number || '') + ')';
          grp.appendChild(opt);
        });
        sel.appendChild(grp);
      }
    });
  });
  // Auto-fill reg number when student changes
  sel.addEventListener('change', function() {
    const opt = this.options[this.selectedIndex];
    if (opt && opt.dataset.reg) {
      document.getElementById('addReqRegNum').value = opt.dataset.reg;
    }
  });
  new bootstrap.Modal(document.getElementById('addReqModal')).show();
}

function submitAddRequirement(e) {
  e.preventDefault();
  const studentVal = document.getElementById('addReqStudent').value;
  const studentNameEl = document.getElementById('addReqStudent').options[document.getElementById('addReqStudent').selectedIndex];
  const studentName = studentNameEl ? studentNameEl.dataset.name || studentNameEl.textContent : '';
  const regNum = document.getElementById('addReqRegNum').value;
  const reqType = document.getElementById('addReqType').value;
  const docName = document.getElementById('addReqDocName').value;
  const notes = document.getElementById('addReqNotes').value;
  const msgEl = document.getElementById('addReqMsg');
  if (!studentVal || !reqType) {
    msgEl.innerHTML = '<span class="text-danger">Please select student and type.</span>';
    return;
  }
  const studentId = studentVal.startsWith('app_') ? studentVal.replace('app_', '') : studentVal;
  msgEl.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Adding...';
  fetch('director-admissions.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: 'action=add_requirement&student_id=' + encodeURIComponent(studentId) + '&student_name=' + encodeURIComponent(studentName) + '&registration_number=' + encodeURIComponent(regNum) + '&requirement_type=' + encodeURIComponent(reqType) + '&document_name=' + encodeURIComponent(docName) + '&notes=' + encodeURIComponent(notes) + '&csrf_token=' + encodeURIComponent(CSRF)
  }).then(r => r.json()).then(d => {
    if (d.success) {
      showToast('Requirement added!', 'success');
      bootstrap.Modal.getInstance(document.getElementById('addReqModal')).hide();
      loadRequirements();
    } else {
      msgEl.innerHTML = '<span class="text-danger">' + (d.message || 'Failed') + '</span>';
    }
  });
}

function showSubmitDoc(reqId, docName) {
  document.getElementById('submitDocReqId').value = reqId;
  document.getElementById('submitDocName').value = docName;
  document.getElementById('submitDocFile').value = '';
  new bootstrap.Modal(document.getElementById('submitDocModal')).show();
}

function submitDocument(e) {
  e.preventDefault();
  const reqId = document.getElementById('submitDocReqId').value;
  const docName = document.getElementById('submitDocName').value;
  const fileInput = document.getElementById('submitDocFile');
  const fd = new FormData();
  fd.append('action', 'submit_document');
  fd.append('requirement_id', reqId);
  fd.append('document_name', docName);
  fd.append('csrf_token', CSRF);
  if (fileInput.files.length > 0) {
    fd.append('document_file', fileInput.files[0]);
  }
  fetch('director-admissions.php', {method: 'POST', body: fd}).then(r => r.json()).then(d => {
    if (d.success) {
      showToast('Document submitted!', 'success');
      bootstrap.Modal.getInstance(document.getElementById('submitDocModal')).hide();
      loadRequirements();
    } else {
      showToast(d.message || 'Submit failed', 'danger');
    }
  });
}

function verifyReq(reqId) {
  if (!confirm('Verify this requirement?')) return;
  postData({action: 'verify_requirement', requirement_id: reqId}).then(d => {
    if (d.success) { showToast('Requirement verified!', 'success'); loadRequirements(); }
    else { showToast('Verification failed.', 'danger'); }
  });
}

function showRejectReq(reqId) {
  document.getElementById('rejectReqId').value = reqId;
  document.getElementById('rejectReason').value = '';
  new bootstrap.Modal(document.getElementById('rejectReasonModal')).show();
}

function confirmReject() {
  const reqId = document.getElementById('rejectReqId').value;
  const reason = document.getElementById('rejectReason').value.trim();
  if (!reason) { alert('Please enter a reason.'); return; }
  postData({action: 'reject_requirement', requirement_id: reqId, reason: reason}).then(d => {
    if (d.success) {
      showToast('Requirement rejected.', 'success');
      bootstrap.Modal.getInstance(document.getElementById('rejectReasonModal')).hide();
      loadRequirements();
    } else {
      showToast('Rejection failed.', 'danger');
    }
  });
}

function deleteReq(reqId) {
  if (!confirm('Delete this requirement?')) return;
  postData({action: 'delete_requirement', requirement_id: reqId}).then(d => {
    if (d.success) { showToast('Requirement deleted.', 'success'); loadRequirements(); }
    else { showToast('Delete failed.', 'danger'); }
  });
}

function viewReqDoc(reqId) {
  document.getElementById('viewDocBody').innerHTML = '<div class="text-center py-3"><i class="fas fa-spinner fa-spin"></i> Loading...</div>';
  new bootstrap.Modal(document.getElementById('viewDocModal')).show();
  postData({action: 'get_requirements', filter_student: '', filter_status: '', filter_type: ''}).then(all => {
    const r = all.find(x => x.id == reqId);
    if (!r) { document.getElementById('viewDocBody').innerHTML = '<p class="text-danger">Requirement not found.</p>'; return; }
    const statusBadge = getReqStatusBadge(r.status);
    let html = '<div class="row">' +
      '<div class="col-sm-4 fw-bold text-muted">Student</div><div class="col-sm-8">' + escapeHtml(r.student_name) + '</div>' +
      '<div class="col-sm-4 fw-bold text-muted">Reg #</div><div class="col-sm-8">' + escapeHtml(r.registration_number || '-') + '</div>' +
      '<div class="col-sm-4 fw-bold text-muted">Requirement Type</div><div class="col-sm-8">' + escapeHtml(r.requirement_type) + '</div>' +
      '<div class="col-sm-4 fw-bold text-muted">Document Name</div><div class="col-sm-8">' + escapeHtml(r.document_name || '-') + '</div>' +
      '<div class="col-sm-4 fw-bold text-muted">Status</div><div class="col-sm-8">' + statusBadge + '</div>' +
      '<div class="col-sm-4 fw-bold text-muted">Submitted</div><div class="col-sm-8">' + (r.submitted_date || '-') + '</div>' +
      '<div class="col-sm-4 fw-bold text-muted">Verified</div><div class="col-sm-8">' + (r.verified_date || '-') + '</div>' +
      '<div class="col-sm-4 fw-bold text-muted">Notes</div><div class="col-sm-8">' + escapeHtml(r.notes || '-') + '</div>' +
      '<div class="col-sm-4 fw-bold text-muted">Created</div><div class="col-sm-8">' + (r.created_at || '-') + '</div>' +
    '</div>';
    if (r.file_path) {
      html += '<hr><a href="../' + r.file_path + '" target="_blank" class="btn btn-sm btn-outline-primary"><i class="fas fa-external-link-alt"></i> Open Document</a>';
    }
    document.getElementById('viewDocBody').innerHTML = html;
  });
}

// ===================== ENROLLED STUDENTS =====================
let _enrolledData = [];
function loadEnrolled() {
  const q = (document.getElementById('enrolledSearch').value || '').toLowerCase();
  fetch('director-admissions.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: 'action=get_enrolled_students&csrf_token=' + encodeURIComponent(CSRF)
  }).then(r => r.json()).then(d => {
    _enrolledData = d || [];
    renderEnrolled();
  });
}

function renderEnrolled() {
  const q = (document.getElementById('enrolledSearch').value || '').toLowerCase();
  const filtered = q ? _enrolledData.filter(s => {
    const hay = ((s.full_name || '') + ' ' + (s.student_number || '') + ' ' + (s.program || '') + ' ' + (s.email || '') + ' ' + (s.phone || '')).toLowerCase();
    return hay.includes(q);
  }) : _enrolledData;
  const tbody = document.getElementById('enrolledTableBody');
  if (!filtered.length) {
    tbody.innerHTML = '<tr><td colspan="9" class="text-center text-muted py-4"><i class="fas fa-user-graduate"></i> No enrolled students found.</td></tr>';
    return;
  }
  tbody.innerHTML = filtered.map(s => {
    const stBadge = s.status === 'Active' ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-secondary">' + escapeHtml(s.status) + '</span>';
    return '<tr>' +
      '<td class="small">' + escapeHtml(s.student_number || '-') + '</td>' +
      '<td><strong>' + escapeHtml(s.full_name || (s.first_name + ' ' + (s.surname || ''))) + '</strong></td>' +
      '<td class="small">' + escapeHtml(s.program || '-') + '</td>' +
      '<td>' + escapeHtml(s.gender || '-') + '</td>' +
      '<td class="small">' + escapeHtml(s.email || '-') + '</td>' +
      '<td class="small">' + escapeHtml(s.phone || '-') + '</td>' +
      '<td>' + stBadge + '</td>' +
      '<td class="small text-muted">' + (s.created_at || '-') + '</td>' +
      '<td><div class="d-flex gap-1">' +
        '<button class="btn btn-sm btn-outline-primary py-0 px-1" onclick="viewStudentProfile(' + s.id + ')" title="View Profile"><i class="fas fa-eye"></i></button>' +
        '<button class="btn btn-sm btn-outline-secondary py-0 px-1" onclick="printStudentFromList(' + s.id + ')" title="Print"><i class="fas fa-print"></i></button>' +
        '<button class="btn btn-sm btn-outline-info py-0 px-1" onclick="viewStudentRequirements(' + s.id + ',\'' + escapeHtml(s.full_name || '') + '\')" title="Requirements"><i class="fas fa-clipboard-list"></i></button>' +
      '</div></td>' +
    '</tr>';
  }).join('');
}

// ===================== DIRECT REGISTER =====================
function showDirectRegisterModal() {
  document.getElementById('directRegisterForm').reset();
  document.getElementById('directRegisterMsg').textContent = '';
  document.getElementById('directRegisterResult').innerHTML = '';
  document.getElementById('directRegisterBtn').disabled = false;
  new bootstrap.Modal(document.getElementById('directRegisterModal')).show();
}

function submitDirectRegister(e) {
  e.preventDefault();
  const msgEl = document.getElementById('directRegisterMsg');
  const resultEl = document.getElementById('directRegisterResult');
  const btn = document.getElementById('directRegisterBtn');
  btn.disabled = true;
  msgEl.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Registering...';

  const data = {
    action: 'direct_register_student',
    first_name: document.getElementById('dr_first_name').value.trim(),
    surname: document.getElementById('dr_surname').value.trim(),
    gender: document.getElementById('dr_gender').value,
    phone: document.getElementById('dr_phone').value.trim(),
    email: document.getElementById('dr_email').value.trim(),
    program: document.getElementById('dr_program').value,
    intake: document.getElementById('dr_intake').value,
    date_of_birth: document.getElementById('dr_date_of_birth').value,
    national_id: document.getElementById('dr_national_id').value.trim(),
    address: document.getElementById('dr_address').value.trim(),
    district: document.getElementById('dr_district').value.trim(),
    next_of_kin_name: document.getElementById('dr_next_of_kin_name').value.trim(),
    next_of_kin_phone: document.getElementById('dr_next_of_kin_phone').value.trim()
  };

  postData(data).then(d => {
    if (d.success) {
      msgEl.innerHTML = '<span class="text-success fw-bold"><i class="fas fa-check-circle"></i> Student registered successfully!</span>';
      resultEl.innerHTML = '<div class="alert alert-success">' +
        '<strong>Student Number:</strong> ' + escapeHtml(d.student_number) + '<br>' +
        '<strong>Index Number:</strong> ' + escapeHtml(d.index_number) + '<br>' +
        '<strong>Temp Password:</strong> <code>' + escapeHtml(d.temp_password) + '</code><br>' +
        '<small class="text-muted">Please save these credentials. The password should be given to the student.</small>' +
        '</div>';
      btn.disabled = true;
      loadApplicants();
    } else {
      msgEl.innerHTML = '<span class="text-danger">' + escapeHtml(d.message || 'Registration failed.') + '</span>';
      btn.disabled = false;
    }
  }).catch(err => {
    msgEl.innerHTML = '<span class="text-danger">Network error. Please try again.</span>';
    btn.disabled = false;
  });
}

// ===================== STUDENT PROFILE =====================
function viewStudentProfile(studentId) {
  document.getElementById('studentProfileBody').innerHTML = '<div class="text-center py-4"><i class="fas fa-spinner fa-spin"></i> Loading...</div>';
  new bootstrap.Modal(document.getElementById('studentProfileModal')).show();
  postData({action: 'get_student_profile', student_id: studentId}).then(d => {
    if (!d || !d.student) {
      document.getElementById('studentProfileBody').innerHTML = '<p class="text-danger">Student not found.</p>';
      return;
    }
    const s = d.student;
    const p = d.profile || {};
    const req = d.requirements || {total:0,completed:0,pending:0,rejected:0};
    const fullName = s.full_name || (s.first_name + ' ' + (s.surname || ''));
    const photo = s.photo ? '<img src="../' + escapeHtml(s.photo) + '" alt="Photo" class="rounded-circle mb-3" style="width:100px;height:100px;object-fit:cover">' :
      '<div class="rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width:100px;height:100px;background:#e2e8f0;font-size:36px;color:#64748b"><i class="fas fa-user"></i></div>';

    let html = '<div class="text-center">' + photo + '</div>' +
      '<h5 class="text-center fw-bold">' + escapeHtml(fullName) + '</h5>' +
      '<div class="row mt-3">' +
        '<div class="col-sm-6"><div class="mb-2"><strong>Student #:</strong> ' + escapeHtml(s.student_number || '-') + '</div>' +
        '<div class="mb-2"><strong>Index #:</strong> ' + escapeHtml(s.index_number || '-') + '</div>' +
        '<div class="mb-2"><strong>Reg #:</strong> ' + escapeHtml(s.registration_number || '-') + '</div>' +
        '<div class="mb-2"><strong>Program:</strong> ' + escapeHtml(s.program || '-') + '</div>' +
        '<div class="mb-2"><strong>Intake:</strong> ' + escapeHtml(s.intake_period || '-') + ' ' + escapeHtml(s.intake_year || '') + '</div>' +
        '<div class="mb-2"><strong>Year:</strong> ' + escapeHtml(s.year || '-') + '</div>' +
        '<div class="mb-2"><strong>Level:</strong> ' + escapeHtml(s.level || '-') + '</div></div>' +
        '<div class="col-sm-6"><div class="mb-2"><strong>Gender:</strong> ' + escapeHtml(s.gender || '-') + '</div>' +
        '<div class="mb-2"><strong>Phone:</strong> ' + escapeHtml(s.phone || '-') + '</div>' +
        '<div class="mb-2"><strong>Email:</strong> ' + escapeHtml(s.email || '-') + '</div>' +
        '<div class="mb-2"><strong>Address:</strong> ' + escapeHtml(s.address || '-') + '</div>' +
        '<div class="mb-2"><strong>Status:</strong> <span class="badge bg-' + (s.status === 'Active' ? 'success' : 'secondary') + '">' + escapeHtml(s.status || '-') + '</span></div>' +
        '<div class="mb-2"><strong>Registered:</strong> ' + escapeHtml(s.created_at || '-') + '</div></div>' +
      '</div>';

    html += '<hr><h6 class="fw-bold">Fee Balance</h6>' +
      '<div class="mb-2"><strong>Outstanding Balance:</strong> UGX ' + number_format_local(d.fee_balance) + '</div>' +
      '<div class="mb-2"><strong>Fee Status:</strong> <span class="badge bg-' + (p.fee_status === 'paid' ? 'success' : 'warning text-dark') + '">' + escapeHtml(p.fee_status || 'unpaid') + '</span></div>';

    html += '<hr><h6 class="fw-bold">Requirements Status</h6>' +
      '<div class="row text-center">' +
        '<div class="col"><div class="fs-4 fw-bold text-primary">' + req.total + '</div><div class="small text-muted">Total</div></div>' +
        '<div class="col"><div class="fs-4 fw-bold text-success">' + req.completed + '</div><div class="small text-muted">Completed</div></div>' +
        '<div class="col"><div class="fs-4 fw-bold text-warning">' + req.pending + '</div><div class="small text-muted">Pending</div></div>' +
        '<div class="col"><div class="fs-4 fw-bold text-danger">' + req.rejected + '</div><div class="small text-muted">Rejected</div></div>' +
      '</div>';

    document.getElementById('studentProfileBody').innerHTML = html;
  });
}

function number_format_local(num) {
  return parseFloat(num || 0).toLocaleString('en-US', {minimumFractionDigits: 0, maximumFractionDigits: 0});
}

function printStudentProfile() {
  const content = document.getElementById('studentProfileBody').innerHTML;
  const win = window.open('', '_blank', 'width=800,height=600');
  win.document.write('<html><head><title>Student Profile</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"><style>body{padding:30px;font-family:Arial,sans-serif}</style></head><body>' +
    '<h3 class="text-center mb-4">Student Profile</h3>' + content +
    '<script>setTimeout(function(){window.print();},500);<\/script></body></html>');
  win.document.close();
}

function printStudentFromList(studentId) {
  postData({action: 'get_student_profile', student_id: studentId}).then(d => {
    if (!d || !d.student) { showToast('Student not found.', 'danger'); return; }
    const s = d.student;
    const p = d.profile || {};
    const req = d.requirements || {total:0,completed:0,pending:0,rejected:0};
    const fullName = s.full_name || (s.first_name + ' ' + (s.surname || ''));
    let html = '<h3 class="text-center mb-4">Student Profile</h3>' +
      '<h5 class="text-center">' + escapeHtml(fullName) + '</h5>' +
      '<p class="text-center text-muted">' + escapeHtml(s.student_number || '') + ' | ' + escapeHtml(s.registration_number || '') + '</p>' +
      '<table class="table table-bordered"><tbody>' +
      '<tr><th width="30%">Program</th><td>' + escapeHtml(s.program || '-') + '</td></tr>' +
      '<tr><th>Intake</th><td>' + escapeHtml(s.intake_period || '-') + ' ' + escapeHtml(s.intake_year || '') + '</td></tr>' +
      '<tr><th>Year / Level</th><td>' + escapeHtml(s.year || '-') + ' / ' + escapeHtml(s.level || '-') + '</td></tr>' +
      '<tr><th>Gender</th><td>' + escapeHtml(s.gender || '-') + '</td></tr>' +
      '<tr><th>Phone</th><td>' + escapeHtml(s.phone || '-') + '</td></tr>' +
      '<tr><th>Email</th><td>' + escapeHtml(s.email || '-') + '</td></tr>' +
      '<tr><th>Address</th><td>' + escapeHtml(s.address || '-') + '</td></tr>' +
      '<tr><th>Status</th><td>' + escapeHtml(s.status || '-') + '</td></tr>' +
      '<tr><th>Fee Balance</th><td>UGX ' + number_format_local(d.fee_balance) + '</td></tr>' +
      '<tr><th>Requirements</th><td>' + req.completed + '/' + req.total + ' completed</td></tr>' +
      '</tbody></table>';
    const win = window.open('', '_blank', 'width=800,height=600');
    win.document.write('<html><head><title>Student Profile - ' + escapeHtml(fullName) + '</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"><style>body{padding:30px;font-family:Arial,sans-serif}</style></head><body>' + html + '<script>setTimeout(function(){window.print();},500);<\/script></body></html>');
    win.document.close();
  });
}

function viewStudentRequirements(studentId, studentName) {
  postData({action: 'get_student_requirements', student_id: studentId}).then(rows => {
    let bodyHtml = '<h6 class="fw-bold">' + escapeHtml(studentName || 'Student') + ' - Requirements</h6>';
    if (!rows || rows.length === 0) {
      bodyHtml += '<p class="text-muted">No requirements found for this student.</p>';
    } else {
      bodyHtml += '<table class="table table-sm table-hover"><thead><tr><th>Type</th><th>Document</th><th>Status</th><th>Submitted</th><th>Verified</th></tr></thead><tbody>';
      rows.forEach(r => {
        const sBadge = getReqStatusBadge(r.status);
        bodyHtml += '<tr>' +
          '<td>' + escapeHtml(r.requirement_type) + '</td>' +
          '<td>' + escapeHtml(r.document_name || '-') + '</td>' +
          '<td>' + sBadge + '</td>' +
          '<td class="small">' + (r.submitted_date || '-') + '</td>' +
          '<td class="small">' + (r.verified_date || '-') + '</td>' +
          '</tr>';
      });
      bodyHtml += '</tbody></table>';
    }
    document.getElementById('viewDocBody').innerHTML = bodyHtml;
    document.querySelector('#viewDocModal .modal-title').innerHTML = '<i class="fas fa-clipboard-list"></i> Student Requirements';
    new bootstrap.Modal(document.getElementById('viewDocModal')).show();
  });
}

// ===================== REQUIREMENTS CLEARANCE =====================
let clearanceTimer = null;
function debounceClearance() { clearTimeout(clearanceTimer); clearanceTimer = setTimeout(refreshClearanceList, 400); }

function clearClearanceFilters() {
  document.getElementById('clearanceSearch').value = '';
  document.getElementById('clearanceFilterField').value = 'name';
  document.getElementById('clearanceFilterStatus').value = 'all';
  refreshClearanceList();
}

function refreshClearanceList() {
  const search = document.getElementById('clearanceSearch').value.trim();
  if (search.length < 2 && search.length > 0) { document.getElementById('clearanceResults').innerHTML = '<div class="text-muted py-3">Type at least 2 characters to search...</div>'; return; }
  document.getElementById('clearanceResults').innerHTML = '<i class="fas fa-spinner fa-spin"></i> Searching...';
  const params = {
    action: 'get_clearance_students',
    search: search,
    filter_field: document.getElementById('clearanceFilterField').value,
    filter_status: document.getElementById('clearanceFilterStatus').value
  };
  postData(params).then(d => {
    if (!d || !d.success) { document.getElementById('clearanceResults').innerHTML = '<div class="text-danger py-3">Error loading students.</div>'; return; }
    const students = d.students || [];
    if (students.length === 0) {
      document.getElementById('clearanceResults').innerHTML = '<div class="text-muted py-4"><i class="fas fa-users-slash fa-2x mb-2 d-block"></i>No students found matching your criteria.</div>';
      return;
    }
    let html = '<div class="row g-2">';
    students.forEach(s => {
      const initials = (s.student_name || 'S').split(' ').map(w => w[0]).join('').substring(0, 2).toUpperCase();
      const statusColors = { verified: '#059669', submitted: '#2563eb', pending: '#f59e0b', rejected: '#dc2626' };
      const statusColor = statusColors[s.status] || '#6b7280';
      html += '<div class="col-md-6 col-lg-4">' +
        '<div class="card border-0 shadow-sm h-100 cursor-pointer" style="border-left:3px solid ' + statusColor + ' !important" onclick="openClearanceDetail(' + s.student_id + ', \'' + escapeHtml((s.student_name || '').replace(/'/g, "\\'")) + '\')">' +
        '<div class="card-body p-3">' +
        '<div class="d-flex align-items-center gap-2 mb-2">' +
        '<div style="width:36px;height:36px;border-radius:50%;background:linear-gradient(135deg,#1e40af,#3b82f6);color:#fff;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:600;flex-shrink:0">' + initials + '</div>' +
        '<div class="min-width-0">' +
        '<div class="fw-bold small text-truncate">' + escapeHtml(s.student_name || 'Unknown') + '</div>' +
        '<div class="text-muted" style="font-size:11px">' + escapeHtml(s.registration_number || '') + '</div>' +
        '</div></div>' +
        '<div class="d-flex justify-content-between align-items-center mt-2">' +
        '<span class="badge rounded-pill" style="background:' + statusColor + ';font-size:11px">' + (s.status || 'pending').toUpperCase() + '</span>' +
        '<span class="text-muted" style="font-size:11px">' + escapeHtml(s.requirement_type || '') + '</span>' +
        '</div></div></div></div>';
    });
    html += '</div>';
    html += '<div class="text-muted small mt-2 text-center">Showing ' + students.length + ' student(s)</div>';
    document.getElementById('clearanceResults').innerHTML = html;
  });
}

let currentClearanceStudentId = null;
function openClearanceDetail(studentId, studentName) {
  currentClearanceStudentId = studentId;
  document.getElementById('clearanceDetailTitle').textContent = studentName + ' - Requirements Clearance';
  document.getElementById('clearanceDetailBody').innerHTML = '<div class="text-center py-3"><i class="fas fa-spinner fa-spin"></i> Loading requirements...</div>';
  new bootstrap.Modal(document.getElementById('clearanceDetailModal')).show();
  postData({ action: 'get_clearance_detail', student_id: studentId }).then(d => {
    if (!d || !d.success) { document.getElementById('clearanceDetailBody').innerHTML = '<div class="text-danger">Error loading requirements.</div>'; return; }
    const requirements = d.requirements || [];
    const name = d.student_name || studentName;
    renderClearanceDetail(requirements, name);
  });
}

function renderClearanceDetail(requirements, studentName) {
  const allItems = [
    'Surgical Gloves', 'Examination Gloves', 'Photocopying Ream', 'Ruled Paper Reams',
    'Omo', 'Toilet Papers', 'Compound Brooms', 'Soft Brooms', 'Rake',
    'Cobweb Brush', 'Scrubbing Brush', 'Squeezer', 'Toilet Brush',
    'JIK', 'Vim', 'Mops', 'Sanitizer', 'Liquid Soap', 'Face Masks', 'Heavy Duty Gloves'
  ];
  const statusMap = {};
  requirements.forEach(r => { statusMap[r.requirement_type] = r.status; });

  let html = '<div class="mb-3 fw-bold text-primary"><i class="fas fa-user-graduate"></i> ' + escapeHtml(studentName) + '</div>';
  html += '<div class="row g-2">';
  allItems.forEach(item => {
    const st = statusMap[item] || 'pending';
    const verified = st === 'verified';
    const icon = verified ? 'fa-check-circle text-success' : 'fa-times-circle text-danger';
    const bg = verified ? 'border-success bg-success bg-opacity-10' : 'border-danger bg-danger bg-opacity-10';
    const labelColor = verified ? 'text-success' : 'text-danger';
    html += '<div class="col-md-6 col-lg-4">' +
      '<div class="card border ' + bg + ' h-100">' +
      '<div class="card-body p-2 d-flex align-items-center gap-2">' +
      '<i class="fas ' + icon + ' fa-lg"></i>' +
      '<div class="flex-grow-1">' +
      '<div class="fw-bold small">' + escapeHtml(item) + '</div>' +
      '<div class="text-muted" style="font-size:11px">' + (verified ? 'Cleared' : st.toUpperCase()) + '</div>' +
      '</div>' +
      '<button class="btn btn-sm btn-outline-' + (verified ? 'secondary' : 'success') + '" onclick="toggleClearanceItem(' + currentClearanceStudentId + ', \'' + escapeHtml(item.replace(/'/g, "\\'")) + '\', ' + (verified ? "'pending'" : "'verified'") + ')">' +
      '<i class="fas ' + (verified ? 'fa-undo' : 'fa-check') + '"></i></button>' +
      '</div></div></div>';
  });
  html += '</div>';
  const clearedCount = allItems.filter(i => statusMap[i] === 'verified').length;
  const total = allItems.length;
  const pct = Math.round((clearedCount / total) * 100);
  const barColor = pct === 100 ? 'bg-success' : pct >= 60 ? 'bg-warning' : 'bg-danger';
  html = '<div class="mb-3"><div class="d-flex justify-content-between mb-1"><span class="fw-bold small">Progress: ' + clearedCount + '/' + total + '</span><span class="fw-bold small">' + pct + '%</span></div>' +
    '<div class="progress" style="height:8px"><div class="progress-bar ' + barColor + '" style="width:' + pct + '%"></div></div></div>' + html;
  document.getElementById('clearanceDetailBody').innerHTML = html;
}

function toggleClearanceItem(studentId, requirementType, newStatus) {
  const params = { action: 'toggle_clearance_item', student_id: studentId, requirement_type: requirementType, new_status: newStatus };
  postData(params).then(d => {
    if (d && d.success) {
      showToast(d.message, 'success');
      openClearanceDetail(studentId, document.getElementById('clearanceDetailTitle').textContent);
      refreshClearanceList();
    } else {
      showToast(d ? d.message : 'Error updating.', 'danger');
    }
  });
}

function bulkClearAll() {
  if (!currentClearanceStudentId) return;
  if (!confirm('Mark ALL 20 requirements as cleared?')) return;
  const allItems = [
    'Surgical Gloves', 'Examination Gloves', 'Photocopying Ream', 'Ruled Paper Reams',
    'Omo', 'Toilet Papers', 'Compound Brooms', 'Soft Brooms', 'Rake',
    'Cobweb Brush', 'Scrubbing Brush', 'Squeezer', 'Toilet Brush',
    'JIK', 'Vim', 'Mops', 'Sanitizer', 'Liquid Soap', 'Face Masks', 'Heavy Duty Gloves'
  ];
  const params = { action: 'bulk_clearance', student_id: currentClearanceStudentId, items: allItems };
  postData(params).then(d => {
    if (d && d.success) {
      showToast(d.message, 'success');
      openClearanceDetail(currentClearanceStudentId, document.getElementById('clearanceDetailTitle').textContent);
      refreshClearanceList();
    } else {
      showToast(d ? d.message : 'Error clearing items.', 'danger');
    }
  });
}

// ===================== INIT =====================
document.addEventListener('DOMContentLoaded', function() {
  <?php if ($tab === 'applications'): ?>
  loadApplicants();
  <?php elseif ($tab === 'requirements'): ?>
  loadRequirements();
  <?php elseif ($tab === 'enrolled'): ?>
  loadEnrolled();
  <?php elseif ($tab === 'clearance'): ?>
  refreshClearanceList();
  <?php endif; ?>
});
</script>
<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
</body>
</html>
