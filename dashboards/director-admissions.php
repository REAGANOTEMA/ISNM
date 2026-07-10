<?php
/**
 * Director of Admissions & Requirements — Complete Enterprise Dashboard
 * Covers: Applications, Review, Requirements, Filtering, Search, Analytics,
 * Registration, Communications, WhatsApp, Reports, Security, Audit.
 */
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
require_once __DIR__ . '/../views/student_data_loader.php';
require_once __DIR__ . '/../includes/global_search.php';
$ctx = bootstrapStaffDashboard(['director admissions', 'admissions', 'admissions officer', 'admissions clerk']);
$conn = $ctx['staff'];
$stuConn = $ctx['students'] ?? null;
$webConn = $ctx['website'] ?? null;
$user = $ctx['user'];
$userId = (int)($user['id'] ?? 0);
$userRole = $_SESSION['role'] ?? '';
$userName = $user['full_name'] ?? 'Director';
$studentsDb = defined('STUDENTS_DB_NAME') ? STUDENTS_DB_NAME : 'igangaschoolofl_students_db';
$uploadDir = __DIR__ . '/../uploads/admissions/';
if (!is_dir($uploadDir)) @mkdir($uploadDir, 0755, true);
$photoDir = __DIR__ . '/../uploads/passport_photos/';
if (!is_dir($photoDir)) @mkdir($photoDir, 0755, true);
if (!$conn) die('Database connection failed.');

// ── Auto-migrate tables (safe, runs once per page load if table missing) ──
$autoMigrateSQL = [
    "CREATE TABLE IF NOT EXISTS academic_programs (id INT AUTO_INCREMENT PRIMARY KEY,program_code VARCHAR(20) NOT NULL UNIQUE,program_name VARCHAR(255) NOT NULL,program_type ENUM('Certificate','Diploma','Degree','Short Course') NOT NULL DEFAULT 'Diploma',department VARCHAR(100) DEFAULT NULL,duration_years DECIMAL(3,1) NOT NULL DEFAULT 2.0,total_fee DECIMAL(14,2) NOT NULL DEFAULT 0.00,status ENUM('Active','Inactive') NOT NULL DEFAULT 'Active',created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    "CREATE TABLE IF NOT EXISTS intakes (id INT AUTO_INCREMENT PRIMARY KEY,intake_name VARCHAR(100) NOT NULL,intake_month VARCHAR(20) NOT NULL,intake_year YEAR NOT NULL,application_start DATE DEFAULT NULL,application_deadline DATE DEFAULT NULL,status ENUM('Open','Closed','Upcoming') NOT NULL DEFAULT 'Upcoming',created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,UNIQUE KEY uk_intake(intake_month,intake_year)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    "CREATE TABLE IF NOT EXISTS applicants (id INT AUTO_INCREMENT PRIMARY KEY,application_number VARCHAR(30) NOT NULL UNIQUE,student_number VARCHAR(50) DEFAULT NULL UNIQUE,registration_number VARCHAR(50) DEFAULT NULL,portal_username VARCHAR(100) DEFAULT NULL,portal_password_hash VARCHAR(255) DEFAULT NULL,full_name VARCHAR(255) NOT NULL,first_name VARCHAR(100) DEFAULT NULL,middle_name VARCHAR(100) DEFAULT NULL,surname VARCHAR(100) DEFAULT NULL,gender ENUM('Male','Female','Other') DEFAULT NULL,date_of_birth DATE DEFAULT NULL,email VARCHAR(100) DEFAULT NULL,phone VARCHAR(20) DEFAULT NULL,alternative_phone VARCHAR(20) DEFAULT NULL,nationality VARCHAR(100) DEFAULT 'Ugandan',district VARCHAR(100) DEFAULT NULL,county VARCHAR(100) DEFAULT NULL,religion VARCHAR(50) DEFAULT NULL,marital_status ENUM('Single','Married','Divorced','Widowed') DEFAULT 'Single',address TEXT DEFAULT NULL,photo_path VARCHAR(500) DEFAULT NULL,program_id INT DEFAULT NULL,intake VARCHAR(50) DEFAULT NULL,intake_id INT DEFAULT NULL,application_source ENUM('Online','Manual','Walk-in','Referral','Other') DEFAULT 'Online',status ENUM('New','Under Review','Waiting for Documents','Requirements Verified','Interview Scheduled','Approved','Rejected','Registered','Withdrawn') NOT NULL DEFAULT 'New',rejection_reason TEXT DEFAULT NULL,previous_education TEXT DEFAULT NULL,previous_institution VARCHAR(255) DEFAULT NULL,previous_qualification VARCHAR(255) DEFAULT NULL,last_attended_school VARCHAR(255) DEFAULT NULL,guardian_name VARCHAR(200) DEFAULT NULL,guardian_phone VARCHAR(20) DEFAULT NULL,guardian_email VARCHAR(100) DEFAULT NULL,guardian_relationship VARCHAR(50) DEFAULT NULL,emergency_contact_name VARCHAR(100) DEFAULT NULL,emergency_contact_phone VARCHAR(20) DEFAULT NULL,submitted_at TIMESTAMP NULL DEFAULT NULL,reviewed_by INT DEFAULT NULL,reviewed_at TIMESTAMP NULL DEFAULT NULL,approved_by INT DEFAULT NULL,approved_at TIMESTAMP NULL DEFAULT NULL,registered_at TIMESTAMP NULL DEFAULT NULL,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,INDEX idx_app_status(status),INDEX idx_app_program(program_id),INDEX idx_app_intake(intake),INDEX idx_app_created(created_at),INDEX idx_app_name(full_name),INDEX idx_app_email(email),INDEX idx_app_nationality(nationality),INDEX idx_app_district(district)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    "CREATE TABLE IF NOT EXISTS admission_requirements (id INT AUTO_INCREMENT PRIMARY KEY,requirement_name VARCHAR(255) NOT NULL,type ENUM('Document','Certificate','ID','Photo','Form','Other') NOT NULL DEFAULT 'Document',display_order INT NOT NULL DEFAULT 0,is_mandatory TINYINT(1) NOT NULL DEFAULT 1,is_active TINYINT(1) NOT NULL DEFAULT 1,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    "CREATE TABLE IF NOT EXISTS applicant_requirement_status (id INT AUTO_INCREMENT PRIMARY KEY,applicant_id INT NOT NULL,requirement_id INT NOT NULL,status ENUM('Not Submitted','Pending','Submitted','Verified','Rejected','Missing','Received') NOT NULL DEFAULT 'Not Submitted',remarks TEXT DEFAULT NULL,submitted_by INT DEFAULT NULL,submitted_at TIMESTAMP NULL DEFAULT NULL,verified_by INT DEFAULT NULL,verified_at TIMESTAMP NULL DEFAULT NULL,updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,UNIQUE KEY uk_app_req(applicant_id,requirement_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    "CREATE TABLE IF NOT EXISTS student_documents (id INT AUTO_INCREMENT PRIMARY KEY,applicant_id INT NOT NULL,requirement_id INT DEFAULT NULL,document_name VARCHAR(255) NOT NULL,document_type VARCHAR(100) DEFAULT NULL,file_path VARCHAR(500) NOT NULL,file_size INT DEFAULT NULL,file_mime VARCHAR(100) DEFAULT NULL,verification_status ENUM('Pending','Verified','Rejected') NOT NULL DEFAULT 'Pending',verification_remarks TEXT DEFAULT NULL,verified_by INT DEFAULT NULL,verified_at TIMESTAMP NULL DEFAULT NULL,document_status ENUM('Active','Deleted') NOT NULL DEFAULT 'Active',uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,INDEX idx_doc_app(applicant_id),INDEX idx_doc_ver(verification_status)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    "CREATE TABLE IF NOT EXISTS requirement_history (id INT AUTO_INCREMENT PRIMARY KEY,applicant_id INT NOT NULL,requirement_id INT DEFAULT NULL,action VARCHAR(100) NOT NULL,previous_status VARCHAR(50) DEFAULT NULL,new_status VARCHAR(50) DEFAULT NULL,performed_by INT DEFAULT NULL,remarks TEXT DEFAULT NULL,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,INDEX idx_rh_app(applicant_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    "CREATE TABLE IF NOT EXISTS admission_activity_logs (id INT AUTO_INCREMENT PRIMARY KEY,applicant_id INT DEFAULT NULL,user_id INT DEFAULT NULL,action VARCHAR(100) NOT NULL,description TEXT DEFAULT NULL,ip_address VARCHAR(45) DEFAULT NULL,user_agent TEXT DEFAULT NULL,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,INDEX idx_log_app(applicant_id),INDEX idx_log_user(user_id),INDEX idx_log_created(created_at)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    "CREATE TABLE IF NOT EXISTS student_admission_tracking (id INT AUTO_INCREMENT PRIMARY KEY,student_number VARCHAR(50) DEFAULT NULL,application_number VARCHAR(30) NOT NULL,applicant_id INT DEFAULT NULL,program VARCHAR(255) DEFAULT NULL,intake VARCHAR(50) DEFAULT NULL,admission_date DATE DEFAULT NULL,admission_status ENUM('Pending','Under Review','Requirements Pending','Approved','Rejected','Registered') NOT NULL DEFAULT 'Pending',requirements_total INT NOT NULL DEFAULT 0,requirements_completed INT NOT NULL DEFAULT 0,documents_uploaded INT NOT NULL DEFAULT 0,interview_scheduled TINYINT(1) NOT NULL DEFAULT 0,interview_date DATETIME DEFAULT NULL,interview_notes TEXT DEFAULT NULL,communication_count INT NOT NULL DEFAULT 0,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,UNIQUE KEY uk_track_app(application_number),INDEX idx_track_status(admission_status)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    "CREATE TABLE IF NOT EXISTS admission_notifications (id INT AUTO_INCREMENT PRIMARY KEY,applicant_id INT DEFAULT NULL,user_id INT DEFAULT NULL,type ENUM('info','success','warning','danger') NOT NULL DEFAULT 'info',title VARCHAR(255) NOT NULL,message TEXT DEFAULT NULL,is_read TINYINT(1) NOT NULL DEFAULT 0,link VARCHAR(500) DEFAULT NULL,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,INDEX idx_n_app(applicant_id),INDEX idx_n_user(user_id),INDEX idx_n_read(is_read)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    "CREATE TABLE IF NOT EXISTS admission_interviews (id INT AUTO_INCREMENT PRIMARY KEY,applicant_id INT NOT NULL,interviewer_id INT DEFAULT NULL,interview_date DATETIME NOT NULL,interview_mode ENUM('In-Person','Online','Phone') NOT NULL DEFAULT 'In-Person',interview_link VARCHAR(500) DEFAULT NULL,interview_score DECIMAL(5,2) DEFAULT NULL,interview_outcome ENUM('Pass','Fail','Pending','Reschedule') DEFAULT 'Pending',notes TEXT DEFAULT NULL,recommendation TEXT DEFAULT NULL,created_by INT DEFAULT NULL,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,INDEX idx_int_app(applicant_id),INDEX idx_int_date(interview_date)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    "CREATE TABLE IF NOT EXISTS admission_communications (id INT AUTO_INCREMENT PRIMARY KEY,applicant_id INT NOT NULL,sender_id INT DEFAULT NULL,communication_type ENUM('Email','SMS','Portal','WhatsApp','Internal Note') NOT NULL DEFAULT 'Portal',subject VARCHAR(255) DEFAULT NULL,message TEXT NOT NULL,status ENUM('Sent','Delivered','Read','Failed') DEFAULT 'Sent',sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,INDEX idx_com_app(applicant_id),INDEX idx_com_type(communication_type)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    "CREATE TABLE IF NOT EXISTS admission_decisions (id INT AUTO_INCREMENT PRIMARY KEY,applicant_id INT NOT NULL,decision ENUM('Approved','Rejected','Deferred','Waitlisted') NOT NULL,decision_reason TEXT DEFAULT NULL,decided_by INT DEFAULT NULL,decided_at TIMESTAMP NULL DEFAULT NULL,notified_applicant TINYINT(1) NOT NULL DEFAULT 0,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,INDEX idx_dec_app(applicant_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
];
foreach ($autoMigrateSQL as $sql) { try { $conn->query($sql); } catch (Exception $e) { error_log('Admissions migrate: '.$e->getMessage()); } }
// Add "Not Yet Given" to requirement status enum if missing
try { $conn->query("ALTER TABLE applicant_requirement_status MODIFY COLUMN status ENUM('Not Submitted','Pending','Submitted','Verified','Rejected','Missing','Received','Not Yet Given') NOT NULL DEFAULT 'Not Submitted'"); } catch (Exception $e) { error_log('alter req status: '.$e->getMessage()); }
// Add director_notes column if missing
try { $r = $conn->query("SHOW COLUMNS FROM applicant_requirement_status LIKE 'director_notes'"); if ($r && $r->num_rows === 0) $conn->query("ALTER TABLE applicant_requirement_status ADD COLUMN director_notes TEXT DEFAULT NULL AFTER remarks"); } catch (Exception $e) { error_log('add director_notes: '.$e->getMessage()); }
// Migrate old intakes table (created by academic-registrar.php with different schema) to new schema
$chk = $conn->query("SHOW COLUMNS FROM intakes LIKE 'intake_month'");
if ($chk && $chk->num_rows === 0) {
    $conn->query("ALTER TABLE intakes ADD COLUMN intake_month VARCHAR(20) NOT NULL AFTER intake_name");
    $conn->query("ALTER TABLE intakes ADD COLUMN intake_year YEAR NOT NULL DEFAULT '2026' AFTER intake_month");
    $conn->query("ALTER TABLE intakes ADD COLUMN application_start DATE DEFAULT NULL AFTER intake_year");
    $conn->query("ALTER TABLE intakes ADD COLUMN application_deadline DATE DEFAULT NULL AFTER application_start");
    $conn->query("ALTER TABLE intakes MODIFY status ENUM('Open','Closed','Upcoming') NOT NULL DEFAULT 'Upcoming'");
}
// Migrate student_admission_tracking - add missing columns
$chk2 = $conn->query("SHOW COLUMNS FROM student_admission_tracking LIKE 'application_number'");
if ($chk2 && $chk2->num_rows === 0) {
    $conn->query("ALTER TABLE student_admission_tracking ADD COLUMN application_number VARCHAR(30) NOT NULL AFTER id");
    $conn->query("ALTER TABLE student_admission_tracking ADD COLUMN applicant_id INT DEFAULT NULL AFTER application_number");
    $conn->query("ALTER TABLE student_admission_tracking ADD COLUMN interview_scheduled TINYINT(1) NOT NULL DEFAULT 0 AFTER documents_uploaded");
    $conn->query("ALTER TABLE student_admission_tracking ADD COLUMN interview_date DATETIME DEFAULT NULL AFTER interview_scheduled");
    $conn->query("ALTER TABLE student_admission_tracking ADD COLUMN interview_notes TEXT DEFAULT NULL AFTER interview_date");
    $conn->query("ALTER TABLE student_admission_tracking ADD COLUMN communication_count INT NOT NULL DEFAULT 0 AFTER interview_notes");
    $conn->query("ALTER TABLE student_admission_tracking MODIFY admission_status ENUM('Pending','Under Review','Requirements Pending','Approved','Rejected','Registered') NOT NULL DEFAULT 'Pending'");
}
// Migrate academic_programs - add missing columns
$chk3 = $conn->query("SHOW COLUMNS FROM academic_programs LIKE 'total_fee'");
if ($chk3 && $chk3->num_rows === 0) {
    $conn->query("ALTER TABLE academic_programs ADD COLUMN total_fee DECIMAL(14,2) NOT NULL DEFAULT 0.00 AFTER duration_years");
    $conn->query("ALTER TABLE academic_programs ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at");
}
// Migrate admission_activity_logs - add missing columns
$chk4 = $conn->query("SHOW COLUMNS FROM admission_activity_logs LIKE 'applicant_id'");
if ($chk4 && $chk4->num_rows === 0) {
    $conn->query("ALTER TABLE admission_activity_logs ADD COLUMN applicant_id INT DEFAULT NULL AFTER id");
    $conn->query("ALTER TABLE admission_activity_logs ADD COLUMN ip_address VARCHAR(45) DEFAULT NULL AFTER description");
    $conn->query("ALTER TABLE admission_activity_logs ADD COLUMN user_agent TEXT DEFAULT NULL AFTER ip_address");
    $conn->query("ALTER TABLE admission_activity_logs ADD INDEX idx_log_app (applicant_id)");
}
// Migrate admission_notifications - add missing columns
$chk5 = $conn->query("SHOW COLUMNS FROM admission_notifications LIKE 'user_id'");
if ($chk5 && $chk5->num_rows === 0) {
    $conn->query("ALTER TABLE admission_notifications ADD COLUMN user_id INT DEFAULT NULL AFTER applicant_id");
    $conn->query("ALTER TABLE admission_notifications ADD COLUMN type ENUM('info','success','warning','danger') NOT NULL DEFAULT 'info' AFTER user_id");
    $conn->query("ALTER TABLE admission_notifications ADD COLUMN link VARCHAR(500) DEFAULT NULL AFTER is_read");
}
// Auto-migrate photo columns on students table
if ($stuConn) {
    try {
        $chkPhoto = $stuConn->query("SHOW COLUMNS FROM students LIKE 'passport_photo'");
        if ($chkPhoto && $chkPhoto->num_rows === 0) {
            $stuConn->query("ALTER TABLE students ADD COLUMN passport_photo VARCHAR(500) DEFAULT NULL AFTER status");
            $stuConn->query("ALTER TABLE students ADD COLUMN profile_picture VARCHAR(500) DEFAULT NULL AFTER passport_photo");
        }
    } catch (Exception $e) { error_log('photo migration: '.$e->getMessage()); }
}
// Seed defaults with exact 8 document requirements + 20 supply items
$r = $conn->query("SELECT COUNT(*) c FROM admission_requirements"); if ($r && (int)$r->fetch_assoc()['c']===0) { 
    // 8 Document Requirements (remove Proof of Payment and Interview Letter)
    $conn->query("INSERT IGNORE INTO admission_requirements(requirement_name,type,display_order,is_mandatory) VALUES 
        ('Completed Application Form','Document',1,1),
        ('A-Level Certificate (UACE)','Document',2,1),
        ('O-Level Certificate (UCE)','Document',3,1),
        ('Birth Certificate','Document',4,1),
        ('Passport Photos (4)','Photo',5,1),
        ('National ID Copy','Document',6,1),
        ('Medical Report','Document',7,1),
        ('Recommendation Letter (LC1)','Document',8,1),
        
        // 20 Supply Items
        ('Surgical Gloves','Other',9,0),
        ('Examination Gloves','Other',10,0),
        ('Photocopying Ream','Other',11,0),
        ('Ruled Paper Reams','Other',12,0),
        ('Omo','Other',13,0),
        ('Toilet Papers','Other',14,0),
        ('Compound brooms','Other',15,0),
        ('Soft brooms','Other',16,0),
        ('Rake','Other',17,0),
        ('Cobweb brush','Other',18,0),
        ('Scrubbing Brush','Other',19,0),
        ('Squeezer','Other',20,0),
        ('Toilet Brush','Other',21,0),
        ('JIK','Other',22,0),
        ('Vim','Other',23,0),
        ('Mops','Other',24,0),
        ('Sanitizer','Other',25,0),
        ('Liquid Soap','Other',26,0),
        ('Face Masks','Other',27,0),
        ('Heavy duty Gloves','Other',28,0)
    "); 
}
$r = $conn->query("SELECT COUNT(*) c FROM intakes"); if ($r && (int)$r->fetch_assoc()['c']===0) { $conn->query("INSERT IGNORE INTO intakes(intake_name,intake_month,intake_year,application_start,application_deadline,status) VALUES ('January 2026','January',2026,'2025-09-01','2026-01-15','Open'),('May 2026','May',2026,'2026-01-01','2026-05-15','Upcoming'),('August 2026','August',2026,'2026-04-01','2026-08-15','Upcoming')"); }
// Sync programs from students DB
if ($stuConn) { $conn->query("INSERT IGNORE INTO academic_programs(program_code,program_name,program_type,duration_years) SELECT CONCAT('PGM-',p.id),p.program_name,p.program_type,p.duration_years FROM $studentsDb.programs p WHERE p.is_active=1 AND NOT EXISTS(SELECT 1 FROM academic_programs ap WHERE ap.program_name=p.program_name COLLATE utf8mb4_general_ci LIMIT 1)"); }

// ── Helpers ──
function logAdmission($conn, $applicantId, $userId, $action, $desc) { $ip=$_SERVER['REMOTE_ADDR']??'';$ua=$_SERVER['HTTP_USER_AGENT']??'';$s=$conn->prepare("INSERT INTO admission_activity_logs(applicant_id,user_id,action,description,ip_address,user_agent) VALUES(?,?,?,?,?,?)");if($s){$s->bind_param('iissss',$applicantId,$userId,$action,$desc,$ip,$ua);$s->execute();$s->close();} }
function notifyAdmission($conn, $applicantId, $userId, $type, $title, $msg, $link='') { $s=$conn->prepare("INSERT INTO admission_notifications(applicant_id,user_id,type,title,message,link) VALUES(?,?,?,?,?,?)");if($s){$s->bind_param('iissss',$applicantId,$userId,$type,$title,$msg,$link);$s->execute();$s->close();} }
function getStatusBadge($s) { $m=['New'=>'bg-primary','Under Review'=>'bg-info','Waiting for Documents'=>'bg-warning text-dark','Requirements Verified'=>'bg-success','Interview Scheduled'=>'bg-purple','Approved'=>'bg-success','Rejected'=>'bg-danger','Registered'=>'bg-dark','Withdrawn'=>'bg-secondary'];$c=$m[$s]??'bg-secondary';return "<span class=\"badge $c\">".htmlspecialchars($s).'</span>'; }
function adCount($conn, $status) { $r=$conn->query("SELECT COUNT(*) c FROM applicants WHERE status='".$conn->real_escape_string($status)."'"); return $r?(int)$r->fetch_assoc()['c']:0; }

$page = $_GET['page'] ?? 'overview';
if ($page === 'home') $page = 'overview';
$sub = $_GET['sub'] ?? '';
$aid = (int)($_GET['aid'] ?? 0);

// ── StudentDataLoader (needed early for POST handlers) ──
$stuLoader = new StudentDataLoader($stuConn);
$allStudents = $stuLoader->loadAllStudents();
$totalAllStudents = count($allStudents);
$excelSources = $stuLoader->getExcelFileSummary();
$excelFileCount = count($excelSources);
$excelRowCount = 0;
foreach ($excelSources as $es) $excelRowCount += $es['students'];

// ── POST / AJAX handlers ──
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    header('Content-Type: application/json');

    if ($action === 'quick_search') {
        $q = trim($_POST['q']??'');
        if (strlen($q)<2) { echo json_encode([]); exit; }
        $qq = '%'.$conn->real_escape_string($q).'%';
        $s = $conn->prepare("SELECT id,application_number,full_name,phone,email,status,program_id,intake FROM applicants WHERE full_name LIKE ? OR application_number LIKE ? OR email LIKE ? OR phone LIKE ? LIMIT 20");
        if ($s) { $s->bind_param('ssss',$qq,$qq,$qq,$qq); $s->execute(); $res=$s->get_result()->fetch_all(MYSQLI_ASSOC); $s->close(); echo json_encode($res); }
        exit;
    }
    if ($action === 'get_applicant') {
        $id=(int)($_POST['id']??0); $s=$conn->prepare("SELECT a.*,ap.program_name,ap.program_code FROM applicants a LEFT JOIN academic_programs ap ON a.program_id=ap.id WHERE a.id=?"); $data=null;
        if($s){$s->bind_param('i',$id);$s->execute();$data=$s->get_result()->fetch_assoc();$s->close();} echo json_encode($data?:[]); exit;
    }
    if ($action === 'update_status') {
        $id=(int)($_POST['id']??0); $st=trim($_POST['status']??'');
        if ($id && $st) { $conn->query("UPDATE applicants SET status='".$conn->real_escape_string($st)."',reviewed_by=$userId,reviewed_at=NOW() WHERE id=$id"); $conn->query("UPDATE student_admission_tracking SET admission_status='".$conn->real_escape_string($st)."' WHERE applicant_id=$id"); logAdmission($conn,$id,$userId,"Status: $st","Status changed to $st"); notifyAdmission($conn,$id,$userId,'info',"Application $st","Your application has been updated to: $st",'staff-portal.php'); }
        echo json_encode(['success'=>true]); exit;
    }
    if ($action === 'approve') {
        $id=(int)($_POST['id']??0);
        $conn->query("UPDATE applicants SET status='Approved',approved_by=$userId,approved_at=NOW() WHERE id=$id");
        $conn->query("UPDATE student_admission_tracking SET admission_status='Approved' WHERE applicant_id=$id");
        $dec='Approved';
        $s=$conn->prepare("INSERT INTO admission_decisions(applicant_id,decision,decision_reason,decided_by,decided_at,notified_applicant) VALUES(?,?,'',?,NOW(),1)");
        if($s){$s->bind_param('isi',$id,$dec,$userId);$s->execute();$s->close();}
        logAdmission($conn,$id,$userId,"Approved","Application approved"); notifyAdmission($conn,$id,$userId,'success','Application Approved','Congratulations! Your application has been approved.','staff-portal.php');
        echo json_encode(['success'=>true]); exit;
    }
    if ($action === 'reject') {
        $id=(int)($_POST['id']??0); $reason=trim($_POST['reason']??''); $dec='Rejected';
        $conn->query("UPDATE applicants SET status='Rejected',rejection_reason='".$conn->real_escape_string($reason)."' WHERE id=$id");
        $conn->query("UPDATE student_admission_tracking SET admission_status='Rejected' WHERE applicant_id=$id");
        $s=$conn->prepare("INSERT INTO admission_decisions(applicant_id,decision,decision_reason,decided_by,decided_at,notified_applicant) VALUES(?,?,?,?,NOW(),1)");
        if($s){$s->bind_param('issi',$id,$dec,$reason,$userId);$s->execute();$s->close();}
        logAdmission($conn,$id,$userId,"Rejected","Reason: $reason"); notifyAdmission($conn,$id,$userId,'danger','Application Rejected',"Your application was rejected. Reason: $reason");
        echo json_encode(['success'=>true]); exit;
    }
    if ($action === 'set_requirement') {
        $aid=(int)($_POST['applicant_id']??0); $rid=(int)($_POST['requirement_id']??0); $st=trim($_POST['status']??'Submitted'); $notes=trim($_POST['notes']??'');
        $s=$conn->prepare("INSERT INTO applicant_requirement_status(applicant_id,requirement_id,status,submitted_by,submitted_at,director_notes) VALUES(?,?,?,?,NOW(),?) ON DUPLICATE KEY UPDATE status=?,submitted_by=?,submitted_at=NOW(),director_notes=COALESCE(NULLIF(?,''),director_notes)");
        if($s){$s->bind_param('iississ',$aid,$rid,$st,$userId,$notes,$st,$userId,$notes);$s->execute();$s->close();}
        $s=$conn->prepare("INSERT INTO requirement_history(applicant_id,requirement_id,action,new_status,performed_by) VALUES(?,?,?,?,?)");
        if($s){$ac="Requirement: $st";$s->bind_param('iissi',$aid,$rid,$ac,$st,$userId);$s->execute();$s->close();}
        // Update tracking counts
        $tr=$conn->query("SELECT COUNT(*) tot FROM admission_requirements WHERE is_active=1")->fetch_assoc()['tot'];
        $cr=$conn->query("SELECT COUNT(*) c FROM applicant_requirement_status WHERE applicant_id=$aid AND status IN('Submitted','Verified','Received','Not Yet Given')")->fetch_assoc()['c'];
        $conn->query("UPDATE student_admission_tracking SET requirements_total=$tr,requirements_completed=$cr WHERE applicant_id=$aid");
        echo json_encode(['success'=>true]); exit;
    }
    if ($action === 'register_student') {
        $id=(int)($_POST['id']??0);
        $ap=$conn->query("SELECT * FROM applicants WHERE id=$id")->fetch_assoc();
        if(!$ap){echo json_encode(['success'=>false,'message'=>'Applicant not found']);exit;}
        $prog=$conn->query("SELECT program_name FROM academic_programs WHERE id=".(int)$ap['program_id'])->fetch_assoc();
        $progName=$prog['program_name']??'Unknown';
        $randPart=str_pad(rand(1,99999),5,'0',STR_PAD_LEFT);
        $sn='STU'.date('Y').$randPart;
        $rn='REG'.date('Y').$randPart;
        $pw=substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#'),0,12);
        $ph=password_hash($pw,PASSWORD_BCRYPT);
        // Start transactions on both connections
        if ($stuConn) $stuConn->begin_transaction();
        $conn->begin_transaction();
        try {
            // Insert into students DB with is_first_login=0 (password already set)
            if($stuConn){
                $parts=explode(' ',$ap['full_name'],2);
                $fn=$parts[0]; $sn2=$parts[1]??'';
                $set=trim($ap['intake']??'');
                $ins=$stuConn->prepare("INSERT INTO students(student_number,registration_number,index_number,first_name,surname,full_name,email,phone,gender,program,date_of_birth,nationality,address,set_name,status,password,is_first_login,password_changed,created_at,updated_at) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,'Active',?,0,1,NOW(),NOW())");
                if($ins){$ins->bind_param('sssssssssssssss',$sn,$rn,$sn,$fn,$sn2,$ap['full_name'],$ap['email'],$ap['phone'],$ap['gender'],$progName,$ap['date_of_birth'],$ap['nationality'],$ap['address'],$set,$ph);$ins->execute();$ins->close();}
                // Create academic record
                $acStmt = $stuConn->prepare("INSERT INTO student_academic_profiles(student_number,full_name,program,academic_year,status) VALUES(?,?,?,?,?)");
                if($acStmt){$acStmt->bind_param('sssss',$sn,$ap['full_name'],$progName,date('Y'),'Active');$acStmt->execute();$acStmt->close();}
            }
            // Update applicant
            $upd1 = $conn->prepare("UPDATE applicants SET status='Registered',student_number=?,registration_number=?,portal_username=?,portal_password_hash=?,registered_at=NOW() WHERE id=?");
            $upd1->bind_param('ssssi',$sn,$rn,$sn,$ph,$id);
            if(!$upd1->execute()) throw new Exception('Failed to update applicant: ' . $upd1->error);
            $upd1->close();
            $upd2 = $conn->prepare("UPDATE student_admission_tracking SET student_number=?,admission_status='Registered' WHERE applicant_id=?");
            $upd2->bind_param('si',$sn,$id);
            if(!$upd2->execute()) throw new Exception('Failed to update tracking: ' . $upd2->error);
            $upd2->close();
            // Create default requirement records for this applicant (mark as 'Not Yet Given')
            $activeReqs=$conn->query("SELECT id FROM admission_requirements WHERE is_active=1");
            if($activeReqs)while($req=$activeReqs->fetch_assoc()){
                $insReq=$conn->prepare("INSERT IGNORE INTO applicant_requirement_status(applicant_id,requirement_id,status,submitted_by) VALUES(?,?,?,?)");
                $notYet='Not Yet Given';
                $insReq->bind_param('iiss',$id,$req['id'],$notYet,$userId);
                if(!$insReq->execute()) throw new Exception('Failed to create requirement record: ' . $insReq->error);
                $insReq->close();
            }
            if ($stuConn) $stuConn->commit();
            $conn->commit();
            logAdmission($conn,$id,$userId,"Registered","Student registered: $sn ($progName)");
            notifyAdmission($conn,$id,$userId,'success','Registration Complete',"Welcome! Your student number is $sn. Username: $sn, Password: $pw",'student-login.php');
            echo json_encode(['success'=>true,'student_number'=>$sn,'username'=>$sn,'password'=>$pw,'program'=>$progName]);
        } catch (Exception $e) {
            if ($stuConn) $stuConn->rollback();
            $conn->rollback();
            echo json_encode(['success'=>false,'message'=>'Registration failed: ' . $e->getMessage()]);
        }
        exit;
    }
    
    // New action: Update applicant details
    if ($action === 'update_applicant') {
        $id=(int)($_POST['id']??0);
        $fields = ['full_name','gender','date_of_birth','nationality','district','religion','email','phone','program_id','intake','guardian_name','guardian_phone','emergency_contact_name','emergency_contact_phone'];
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
        
        if (empty($sets)) {
            echo json_encode(['success'=>false,'message'=>'No fields to update']); exit;
        }
        
        $params[] = $id;
        $types .= 'i';
        $sql = "UPDATE applicants SET " . implode(', ', $sets) . " WHERE id = ?";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param($types, ...$params);
            if ($stmt->execute()) {
                logAdmission($conn,$id,$userId,"Updated","Applicant details updated");
                echo json_encode(['success'=>true,'message'=>'Applicant updated']);
            } else {
                echo json_encode(['success'=>false,'message'=>'Update failed: '.$conn->error]);
            }
            $stmt->close();
        } else {
            echo json_encode(['success'=>false,'message'=>'Prepare failed']);
        }
        exit;
    }
    
    // New action: Delete applicant
    if ($action === 'delete_applicant') {
        $id=(int)($_POST['id']??0);
        if (!$id) {
            echo json_encode(['success'=>false,'message'=>'Invalid ID']); exit;
        }
        
        // Get applicant info for logging
        $app = $conn->query("SELECT full_name, application_number FROM applicants WHERE id=$id")->fetch_assoc();
        
        // Delete related records
        $conn->query("DELETE FROM applicant_requirement_status WHERE applicant_id=$id");
        $conn->query("DELETE FROM student_documents WHERE applicant_id=$id");
        $conn->query("DELETE FROM admission_activity_logs WHERE applicant_id=$id");
        $conn->query("DELETE FROM admission_notifications WHERE applicant_id=$id");
        $conn->query("DELETE FROM admission_communications WHERE applicant_id=$id");
        $conn->query("DELETE FROM admission_interviews WHERE applicant_id=$id");
        $conn->query("DELETE FROM admission_decisions WHERE applicant_id=$id");
        
        // Delete from tracking
        if ($app && isset($app['application_number'])) {
            $conn->query("DELETE FROM student_admission_tracking WHERE application_number='".$conn->real_escape_string($app['application_number'])."'");
        }
        
        // Finally delete applicant
        if ($conn->query("DELETE FROM applicants WHERE id=$id")) {
            logAdmission($conn,$id,$userId,"Deleted","Applicant deleted: ".($app['full_name']??'')." (".($app['application_number']??'').")");
            echo json_encode(['success'=>true,'message'=>'Applicant deleted']);
        } else {
            echo json_encode(['success'=>false,'message'=>'Delete failed']);
        }
        exit;
    }
    if ($action === 'send_communication') {
        $aid=(int)($_POST['applicant_id']??0); $type=trim($_POST['comm_type']??'Portal'); $subj=trim($_POST['subject']??''); $msg=trim($_POST['message']??'');
        if($aid && $msg){$s=$conn->prepare("INSERT INTO admission_communications(applicant_id,sender_id,communication_type,subject,message) VALUES(?,?,?,?,?)");if($s){$s->bind_param('iisss',$aid,$userId,$type,$subj,$msg);$s->execute();$s->close();}logAdmission($conn,$aid,$userId,"Communication: $type","Sent $type: $subj");}
        echo json_encode(['success'=>true]); exit;
    }
    if ($action === 'schedule_interview') {
        $aid=(int)($_POST['applicant_id']??0); $dt=$_POST['interview_date']??''; $mode=trim($_POST['interview_mode']??'In-Person'); $link=trim($_POST['interview_link']??'');
        if($aid && $dt){$s=$conn->prepare("INSERT INTO admission_interviews(applicant_id,interviewer_id,interview_date,interview_mode,interview_link,created_by) VALUES(?,?,?,?,?,?)");if($s){$s->bind_param('iisssi',$aid,$userId,$dt,$mode,$link,$userId);$s->execute();$s->close();}$conn->query("UPDATE applicants SET status='Interview Scheduled' WHERE id=$aid");$conn->query("UPDATE student_admission_tracking SET interview_scheduled=1,interview_date='$dt' WHERE applicant_id=$aid");logAdmission($conn,$aid,$userId,"Interview Scheduled","$mode interview on $dt");notifyAdmission($conn,$aid,$userId,'info','Interview Scheduled',"Your interview is scheduled for $dt ($mode).",'staff-portal.php');}
        echo json_encode(['success'=>true]); exit;
    }
    if ($action === 'request_docs') {
        $aid=(int)($_POST['applicant_id']??0); $msg=trim($_POST['message']??'');
        $conn->query("UPDATE applicants SET status='Waiting for Documents' WHERE id=$aid");
        $conn->query("UPDATE student_admission_tracking SET admission_status='Requirements Pending' WHERE applicant_id=$aid");
        if($msg){$s=$conn->prepare("INSERT INTO admission_communications(applicant_id,sender_id,communication_type,subject,message) VALUES(?,?,'Portal','Additional Documents Required',?)");if($s){$s->bind_param('iis',$aid,$userId,$msg);$s->execute();$s->close();}}
        logAdmission($conn,$aid,$userId,"Documents Requested","Requested: $msg"); notifyAdmission($conn,$aid,$userId,'warning','Documents Required',"Please submit: $msg",'staff-portal.php');
        echo json_encode(['success'=>true]); exit;
    }
    if ($action === 'dashboard_stats') {
        $total=$conn->query("SELECT COUNT(*) c FROM applicants")->fetch_assoc()['c'];
        $new=adCount($conn,'New'); $review=adCount($conn,'Under Review'); $waiting=adCount($conn,'Waiting for Documents');
        $verified=adCount($conn,'Requirements Verified'); $approved=adCount($conn,'Approved'); $rejected=adCount($conn,'Rejected');
        $registered=adCount($conn,'Registered'); $interview=adCount($conn,'Interview Scheduled');
        $pendDocs=$conn->query("SELECT COUNT(*) c FROM student_documents WHERE verification_status='Pending' AND document_status='Active'")->fetch_assoc()['c'];
        echo json_encode(compact('total','new','review','waiting','verified','approved','rejected','registered','interview','pendDocs')); exit;
    }
    if ($action === 'filter_applicants') {
        $where="1=1"; $params=[]; $types='';
        foreach(['status','intake','gender','nationality','district'] as $f){$v=trim($_POST[$f]??'');if($v!==''&&$v!=='all'){$where.=" AND $f=?";$params[]=$v;$types.='s';}}
        $pid=(int)($_POST['program_id']??0);if($pid){$where.=" AND program_id=?";$params[]=$pid;$types.='i';}
        $q=trim($_POST['search']??'');if(strlen($q)>=2){$qq='%'.$q.'%';$where.=" AND (full_name LIKE ? OR application_number LIKE ? OR email LIKE ? OR phone LIKE ?)";$params=array_merge($params,[$qq,$qq,$qq,$qq]);$types.='ssss';}
        $lim=min((int)($_POST['limit']??50),200);$off=(int)($_POST['offset']??0);
        $sql="SELECT a.*,ap.program_name FROM applicants a LEFT JOIN academic_programs ap ON a.program_id=ap.id WHERE $where ORDER BY a.created_at DESC LIMIT ? OFFSET ?";
        $params[]=$lim;$params[]=$off;$types.='ii';
        $s=$conn->prepare($sql); $rows=[];
        if($s){$s->bind_param($types,...$params);$s->execute();$rows=$s->get_result()->fetch_all(MYSQLI_ASSOC);$s->close();}
        echo json_encode($rows); exit;
    }
    if ($action === 'reports_data') {
        $from=date('Y-m-d',strtotime($_POST['from']??'-30 days'));$to=date('Y-m-d',strtotime($_POST['to']??'today'));
        $rs=$conn->prepare("SELECT status,COUNT(*) c FROM applicants WHERE DATE(created_at) BETWEEN ? AND ? GROUP BY status");
        $byStatus=[];if($rs){$rs->bind_param('ss',$from,$to);$rs->execute();$r1=$rs->get_result();while($rw=$r1->fetch_assoc())$byStatus[$rw['status']]=(int)$rw['c'];$rs->close();}
        $rs=$conn->prepare("SELECT ap.program_name,COUNT(a.id) c FROM applicants a JOIN academic_programs ap ON a.program_id=ap.id WHERE DATE(a.created_at) BETWEEN ? AND ? GROUP BY a.program_id");
        $byProgram=[];if($rs){$rs->bind_param('ss',$from,$to);$rs->execute();$r2=$rs->get_result();while($rw=$r2->fetch_assoc())$byProgram[]=$rw;$rs->close();}
        $rs=$conn->prepare("SELECT intake,COUNT(*) c FROM applicants WHERE DATE(created_at) BETWEEN ? AND ? GROUP BY intake");
        $byIntake=[];if($rs){$rs->bind_param('ss',$from,$to);$rs->execute();$r3=$rs->get_result();while($rw=$r3->fetch_assoc())$byIntake[]=$rw;$rs->close();}
        $rs=$conn->prepare("SELECT gender,COUNT(*) c FROM applicants WHERE DATE(created_at) BETWEEN ? AND ? GROUP BY gender");
        $byGender=[];if($rs){$rs->bind_param('ss',$from,$to);$rs->execute();$r4=$rs->get_result();while($rw=$r4->fetch_assoc())$byGender[]=$rw;$rs->close();}
        $rs=$conn->prepare("SELECT nationality,COUNT(*) c FROM applicants WHERE DATE(created_at) BETWEEN ? AND ? GROUP BY nationality ORDER BY c DESC LIMIT 10");
        $byNationality=[];if($rs){$rs->bind_param('ss',$from,$to);$rs->execute();$r5=$rs->get_result();while($rw=$r5->fetch_assoc())$byNationality[]=$rw;$rs->close();}
        $rs=$conn->prepare("SELECT DATE(created_at) dt,COUNT(*) c FROM applicants WHERE DATE(created_at) BETWEEN ? AND ? GROUP BY DATE(created_at) ORDER BY dt");
        $trend=[];if($rs){$rs->bind_param('ss',$from,$to);$rs->execute();$r6=$rs->get_result();while($rw=$r6->fetch_assoc())$trend[]=$rw;$rs->close();}
        echo json_encode(compact('byStatus','byProgram','byIntake','byGender','byNationality','trend')); exit;
    }
    if ($action === 'export_csv') {
        $type=trim($_POST['export_type']??'applicants'); $from=date('Y-m-d',strtotime($_POST['from']??'-30 days')); $to=date('Y-m-d',strtotime($_POST['to']??'today'));
        header('Content-Type: text/csv; charset=utf-8'); header('Content-Disposition: attachment; filename="admissions_'.date('Ymd').'.csv"');
        $out=fopen('php://output','w');
        if($type==='applicants'){
            fputcsv($out,['Application #','Full Name','Gender','Phone','Email','Program','Intake','Status','District','Nationality','Submitted']);
            $rs=$conn->prepare("SELECT a.*,ap.program_name FROM applicants a LEFT JOIN academic_programs ap ON a.program_id=ap.id WHERE DATE(a.created_at) BETWEEN ? AND ? ORDER BY a.created_at");
            if($rs){$rs->bind_param('ss',$from,$to);$rs->execute();$r=$rs->get_result();while($rw=$r->fetch_assoc())fputcsv($out,[$rw['application_number'],$rw['full_name'],$rw['gender'],$rw['phone'],$rw['email'],$rw['program_name'],$rw['intake'],$rw['status'],$rw['district'],$rw['nationality'],$rw['created_at']]);$rs->close();}
        }elseif($type==='requirements'){
            fputcsv($out,['Applicant','Requirement','Status','Submitted','Verified']);
            $r=$conn->query("SELECT a.full_name,ar.requirement_name,ars.status,ars.submitted_at,ars.verified_at FROM applicant_requirement_status ars JOIN applicants a ON ars.applicant_id=a.id JOIN admission_requirements ar ON ars.requirement_id=ar.id ORDER BY a.full_name");
            if($r)while($rw=$r->fetch_assoc())fputcsv($out,$rw);
        }
        fclose($out); exit;
    }
    if ($action === 'website_submissions') {
        $subs=[]; $lim=50;
        if($webConn){
            $r=$webConn->query("SELECT id,CONCAT(first_name,' ',surname) as name,email,phone,program_applied,status,submitted_at as created_at,'Application' as src FROM student_applications ORDER BY submitted_at DESC LIMIT $lim");
            if($r)$subs=array_merge($subs,$r->fetch_all(MYSQLI_ASSOC));
        }
        echo json_encode($subs); exit;
    }
    if ($action === 'get_communications') {
        $aid=(int)($_POST['applicant_id']??0); $s=$conn->prepare("SELECT * FROM admission_communications WHERE applicant_id=? ORDER BY sent_at DESC LIMIT 50"); $rows=[];
        if($s){$s->bind_param('i',$aid);$s->execute();$rows=$s->get_result()->fetch_all(MYSQLI_ASSOC);$s->close();} echo json_encode($rows); exit;
    }
    if ($action === 'get_interviews') {
        $aid=(int)($_POST['applicant_id']??0); $s=$conn->prepare("SELECT * FROM admission_interviews WHERE applicant_id=? ORDER BY interview_date DESC"); $rows=[];
        if($s){$s->bind_param('i',$aid);$s->execute();$rows=$s->get_result()->fetch_all(MYSQLI_ASSOC);$s->close();} echo json_encode($rows); exit;
    }
    if ($action === 'get_decisions') {
        $aid=(int)($_POST['applicant_id']??0); $s=$conn->prepare("SELECT * FROM admission_decisions WHERE applicant_id=? ORDER BY created_at DESC"); $rows=[];
        if($s){$s->bind_param('i',$aid);$s->execute();$rows=$s->get_result()->fetch_all(MYSQLI_ASSOC);$s->close();} echo json_encode($rows); exit;
    }
    if ($action === 'get_activity') {
        $aid=(int)($_POST['applicant_id']??0); $s=$conn->prepare("SELECT * FROM admission_activity_logs WHERE applicant_id=? ORDER BY created_at DESC LIMIT 50"); $rows=[];
        if($s){$s->bind_param('i',$aid);$s->execute();$rows=$s->get_result()->fetch_all(MYSQLI_ASSOC);$s->close();} echo json_encode($rows); exit;
    }
    if ($action === 'get_notifications') {
        $s=$conn->prepare("SELECT n.*,a.full_name as app_name FROM admission_notifications n LEFT JOIN applicants a ON n.applicant_id=a.id WHERE (n.user_id=? OR n.user_id IS NULL) ORDER BY n.created_at DESC LIMIT 20"); $rows=[];
        if($s){$s->bind_param('i',$userId);$s->execute();$rows=$s->get_result()->fetch_all(MYSQLI_ASSOC);$s->close();} echo json_encode($rows); exit;
    }
    
    if ($action === 'get_bulk_requirement_status') {
        $appIds = json_decode($_POST['applicant_ids'] ?? '[]', true);
        if (empty($appIds)) { echo json_encode([]); exit; }
        
        $placeholders = implode(',', array_fill(0, count($appIds), '?'));
        $types = str_repeat('i', count($appIds));
        
        $s = $conn->prepare("SELECT applicant_id, requirement_id, status FROM applicant_requirement_status WHERE applicant_id IN ($placeholders)");
        if ($s) {
            $s->bind_param($types, ...$appIds);
            $s->execute();
            $result = $s->get_result();
            $statusData = [];
            while ($row = $result->fetch_assoc()) {
                if (!isset($statusData[$row['applicant_id']])) {
                    $statusData[$row['applicant_id']] = [];
                }
                $statusData[$row['applicant_id']][$row['requirement_id']] = $row['status'];
            }
            $s->close();
            echo json_encode($statusData);
        } else {
            echo json_encode([]);
        }
        exit;
    }
    
    if ($action === 'bulk_set_requirements') {
        $updates = json_decode($_POST['updates'] ?? '[]', true);
        $status = trim($_POST['status'] ?? 'Submitted');
        
        if (empty($updates)) {
            echo json_encode(['success'=>false,'message'=>'No updates provided']); exit;
        }
        
        $successCount = 0;
        foreach ($updates as $update) {
            $aid = (int)($update['appId'] ?? 0);
            $rid = (int)($update['reqId'] ?? 0);
            
            if ($aid && $rid) {
                $s = $conn->prepare("INSERT INTO applicant_requirement_status(applicant_id,requirement_id,status,submitted_by,submitted_at) VALUES(?,?,?,?,NOW()) ON DUPLICATE KEY UPDATE status=?,submitted_by=?,submitted_at=NOW()");
                if ($s) {
                    $s->bind_param('iissis', $aid, $rid, $status, $userId, $status, $userId);
                    if ($s->execute()) $successCount++;
                    $s->close();
                }
            }
        }
        
        echo json_encode(['success'=>true,'updated'=>$successCount,'total'=>count($updates)]);
        exit;
    }
    
    if ($action === 'export_requirements_csv') {
        $search = trim($_POST['search'] ?? '');
        $status = trim($_POST['status'] ?? '');
        $program_id = (int)($_POST['program_id'] ?? 0);
        $intake = trim($_POST['intake'] ?? '');
        
        $where = "WHERE 1=1";
        $params = [];
        $types = '';
        
        if ($search) {
            $where .= " AND (a.full_name LIKE ? OR a.application_number LIKE ? OR a.email LIKE ? OR a.phone LIKE ?)";
            $s = "%$search%";
            $params = array_merge($params, [$s, $s, $s, $s]);
            $types .= 'ssss';
        }
        if ($status) {
            $where .= " AND a.status = ?";
            $params[] = $status;
            $types .= 's';
        }
        if ($program_id) {
            $where .= " AND a.program_id = ?";
            $params[] = $program_id;
            $types .= 'i';
        }
        if ($intake) {
            $where .= " AND a.intake = ?";
            $params[] = $intake;
            $types .= 's';
        }
        
        // Get all requirements for headers
        $reqs = $conn->query("SELECT id, requirement_name FROM admission_requirements WHERE is_active=1 ORDER BY display_order");
        $requirementHeaders = [];
        $reqIds = [];
        while ($req = $reqs->fetch_assoc()) {
            $requirementHeaders[] = $req['requirement_name'];
            $reqIds[] = $req['id'];
        }
        
        // Get applicants
        $sql = "SELECT a.id, a.application_number, a.full_name, a.email, a.phone, a.program_id, a.intake, a.status, ap.program_name FROM applicants a LEFT JOIN academic_programs ap ON a.program_id=ap.id $where ORDER BY a.full_name";
        $stmt = $conn->prepare($sql);
        if ($params) $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="requirements_export_'.date('Ymd_His').'.csv"');
        
        $output = fopen('php://output', 'w');
        
        // Write headers
        $headers = ['Application #', 'Full Name', 'Email', 'Phone', 'Program', 'Intake', 'Status'];
        $headers = array_merge($headers, $requirementHeaders, ['% Complete']);
        fputcsv($output, $headers);
        
        // Write data
        while ($row = $result->fetch_assoc()) {
            $data = [
                $row['application_number'],
                $row['full_name'],
                $row['email'],
                $row['phone'],
                $row['program_name'],
                $row['intake'],
                $row['status']
            ];
            
            // Get requirement status for this applicant
            $reqStatus = [];
            $reqStmt = $conn->prepare("SELECT requirement_id, status FROM applicant_requirement_status WHERE applicant_id=?");
            $reqStmt->bind_param('i', $row['id']);
            $reqStmt->execute();
            $reqResult = $reqStmt->get_result();
            while ($rs = $reqResult->fetch_assoc()) {
                $reqStatus[$rs['requirement_id']] = $rs['status'];
            }
            $reqStmt->close();
            
            // Add requirement statuses in order
            $completed = 0;
            $totalMandatory = 0;
            foreach ($reqIds as $reqId) {
                $status = $reqStatus[$reqId] ?? 'Not Submitted';
                $data[] = $status;
                
                // Check if this is a mandatory requirement
                $isMandatory = true; // Default to true for simplicity
                if ($isMandatory) {
                    $totalMandatory++;
                    if (in_array($status, ['Submitted','Verified','Received'])) {
                        $completed++;
                    }
                }
            }
            
            // Add completion percentage
            $percentage = $totalMandatory > 0 ? round(($completed / $totalMandatory) * 100) : 0;
            $data[] = $percentage . '%';
            
            fputcsv($output, $data);
        }
        
        fclose($output);
        exit;
    }
    if ($action === 'mark_read') {
        $nid=(int)($_POST['notification_id']??0); $conn->query("UPDATE admission_notifications SET is_read=1 WHERE id=$nid"); echo json_encode(['success'=>true]); exit;
    }
    if ($action === 'import_online') {
        $aid=(int)($_POST['application_id']??0);
        if(!$aid||!$webConn){echo json_encode(['success'=>false,'message'=>'Invalid']);exit;}
        $qr=$webConn->query("SELECT * FROM student_applications WHERE id=$aid");
        if(!$qr){echo json_encode(['success'=>false,'message'=>'Query failed']);exit;}
        $app=$qr->fetch_assoc();
        if(!$app){echo json_encode(['success'=>false,'message'=>'Not found']);exit;}
        $appNum='ONL'.date('Y').str_pad($aid,5,'0',STR_PAD_LEFT);
        $fullName=$app['full_name']??($app['first_name'].' '.$app['surname']);
        $progName=$app['program_applied']??'';
        $progId=0;
        if($progName){$pr=$conn->query("SELECT id FROM academic_programs WHERE program_name='".$conn->real_escape_string($progName)."' LIMIT 1");if($pr&&$pr->num_rows)$progId=(int)$pr->fetch_assoc()['id'];}
        $s=$conn->prepare("INSERT INTO applicants(application_number,full_name,email,phone,gender,program_id,intake,application_source,status,submitted_at) VALUES(?,?,?,?,?,?,?,'Online','New',NOW())");
        if($s){$s->bind_param('sssssis',$appNum,$fullName,$app['email']??'',$app['phone']??'','Female',$progId,$progName);$s->execute();$newId=$conn->insert_id;$s->close();
        $conn->query("INSERT INTO student_admission_tracking(application_number,applicant_id,admission_status) VALUES('$appNum',$newId,'Pending')");
        logAdmission($conn,$newId,$userId,"Imported Online","Imported from website application #$aid");}
        echo json_encode(['success'=>true,'id'=>$newId??0]);exit;
    }
    // ── Student Directory CRUD ──
    if ($action === 'stu_search') {
        $q=trim($_POST['q']??''); $rows=[];$set=trim($_POST['set']??'');$pg=trim($_POST['program']??'');$st=trim($_POST['status']??'');$yr=trim($_POST['year']??'');$gd=trim($_POST['gender']??'');
        // Search DB students
        if($stuConn){
            if(strlen($q)>=2){
                $qq='%'.$conn->real_escape_string($q).'%';
                $s=$stuConn->prepare("SELECT id,student_id,student_number,CONCAT(first_name,' ',COALESCE(surname,'')) full_name,email,phone,program,level,gender,date_of_birth,set_name,status,passport_photo,profile_picture FROM {$studentsDb}.students WHERE (first_name LIKE ? OR surname LIKE ? OR student_id LIKE ? OR phone LIKE ? OR email LIKE ?) AND status!='deleted' LIMIT 100");
                if($s){$s->bind_param('sssss',$qq,$qq,$qq,$qq,$qq);$s->execute();$rows=$s->get_result()->fetch_all(MYSQLI_ASSOC);$s->close();}
            } else {
                // No search keyword - return recent active students
                $s=$stuConn->query("SELECT id,student_id,student_number,CONCAT(first_name,' ',COALESCE(surname,'')) full_name,email,phone,program,level,gender,date_of_birth,set_name,status,passport_photo,profile_picture FROM {$studentsDb}.students WHERE status!='deleted' ORDER BY id DESC LIMIT 200");
                if($s)$rows=$s->fetch_all(MYSQLI_ASSOC);
            }
        }
        // Search Excel students via StudentDataLoader
        try{$excelResults = $stuLoader->searchStudents($q, array_filter(['set'=>$set,'program'=>$pg,'gender'=>$gd,'year'=>$yr]));}catch(Exception $e){$excelResults=[];}
        $merged=$rows;
        $seen=[];foreach($merged as $r)$seen[strtolower(trim($r['full_name']??''))]=true;
        foreach($excelResults as $er){
            $key=strtolower(trim($er['full_name']??''));
            if(!isset($seen[$key])){$merged[]=['id'=>0,'student_id'=>$er['index_number']??$er['student_number']??'EXCEL','full_name'=>$er['full_name']??'','email'=>$er['email']??'','phone'=>$er['phone']??'','program'=>$er['program']??'','level'=>$er['level']??'','gender'=>$er['gender']??'','date_of_birth'=>$er['date_of_birth']??'','set_name'=>$er['set_name']??$er['set']??'','status'=>'Active','_source'=>'Excel','_file'=>$er['source_file']??''];$seen[$key]=true;}
        }
        echo json_encode($merged); exit;
    }
    if ($action === 'excel_stu_list') {
        $set=trim($_POST['set']??'');$pg=trim($_POST['program']??'');$gd=trim($_POST['gender']??'');$yr=trim($_POST['year']??'');
        $filters=[];if($set)$filters['set']=$set;if($pg)$filters['program']=$pg;if($gd)$filters['gender']=$gd;if($yr)$filters['year']=$yr;
        try{$results = $stuLoader->searchStudents('', $filters);}catch(Exception $e){$results=[];}
        echo json_encode($results); exit;
    }
    if ($action === 'stu_add') {
        $fn=trim($_POST['first_name']??''); $sn=trim($_POST['surname']??''); $em=trim($_POST['email']??'');
        $ph=trim($_POST['phone']??''); $pg=trim($_POST['program']??''); $lv=trim($_POST['level']??'');
        $g=trim($_POST['gender']??''); $dob=trim($_POST['date_of_birth']??''); $set=trim($_POST['set_name']??'');
        $success=false;$msg='';$newId=0;$studentNum='';$regNum='';$tempPw='';
        if($fn&&$sn&&$stuConn){
            $randPart=str_pad(rand(1,99999),5,'0',STR_PAD_LEFT);
            $studentNum='STU'.date('Y').$randPart;
            $regNum='REG'.date('Y').$randPart;
            $full="$fn $sn";
            $tempPw=substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#'),0,10);
            $pwHash=password_hash($tempPw,PASSWORD_BCRYPT);
            // Handle photo upload
            $photoPath = '';
            if (isset($_FILES['passport_photo']) && $_FILES['passport_photo']['error'] === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($_FILES['passport_photo']['name'], PATHINFO_EXTENSION));
                $allowed = ['jpg','jpeg','png','gif','webp'];
                if (in_array($ext, $allowed)) {
                    $photoName = 'stu_' . $studentNum . '_' . time() . '.' . $ext;
                    $dest = $photoDir . $photoName;
                    if (move_uploaded_file($_FILES['passport_photo']['tmp_name'], $dest)) {
                        $photoPath = 'uploads/passport_photos/' . $photoName;
                    }
                }
            }
            $s=$stuConn->prepare("INSERT INTO {$studentsDb}.students(student_id,student_number,registration_number,first_name,surname,full_name,email,phone,program,level,gender,date_of_birth,set_name,password,is_first_login,status,passport_photo,profile_picture,created_at,updated_at) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,1,'Active',?,?,NOW(),NOW())");
            if($s){$s->bind_param('sssssssssssssssss',$studentNum,$studentNum,$regNum,$fn,$sn,$full,$em,$ph,$pg,$lv,$g,$dob,$set,$pwHash,$photoPath,$photoPath);$success=$s->execute();$newId=$s->insert_id;$s->close();}
            if($success){
                $msg="Student added. Login: $studentNum / Password: $tempPw";
                if($conn){
                    $s2=$conn->prepare("INSERT INTO student_admission_tracking(application_number,student_number,program,intake,admission_status,requirements_total,requirements_completed) VALUES(?,?,?,?,'Registered',0,0)");
                    if($s2){$s2->bind_param('ssss',$regNum,$studentNum,$pg,$set);$s2->execute();$s2->close();}
                }
            }else{$msg='Insert failed';}
        }else{$msg='First name and surname required.';}
        echo json_encode(['success'=>$success,'message'=>$msg,'id'=>$newId,'student_number'=>$studentNum,'password'=>$tempPw]); exit;
    }
    if ($action === 'stu_update') {
        $id=(int)($_POST['id']??0); $fn=trim($_POST['first_name']??''); $sn=trim($_POST['surname']??'');
        $em=trim($_POST['email']??''); $ph=trim($_POST['phone']??''); $pg=trim($_POST['program']??'');
        $lv=trim($_POST['level']??''); $g=trim($_POST['gender']??''); $st=trim($_POST['status']??'Active');
        $set=trim($_POST['set_name']??'');
        $success=false;$msg='';
        if($id&&$fn&&$sn&&$stuConn){
            // Handle photo upload
            $photoSql = ''; $photoParams = []; $photoTypes = '';
            if (isset($_FILES['passport_photo']) && $_FILES['passport_photo']['error'] === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($_FILES['passport_photo']['name'], PATHINFO_EXTENSION));
                $allowed = ['jpg','jpeg','png','gif','webp'];
                if (in_array($ext, $allowed)) {
                    $photoName = 'stu_' . $id . '_' . time() . '.' . $ext;
                    $dest = $photoDir . $photoName;
                    if (move_uploaded_file($_FILES['passport_photo']['tmp_name'], $dest)) {
                        $pPath = 'uploads/passport_photos/' . $photoName;
                        $photoSql = ',passport_photo=?,profile_picture=?';
                        $photoParams = [$pPath, $pPath];
                        $photoTypes = 'ss';
                    }
                }
            }
            $sql = "UPDATE {$studentsDb}.students SET first_name=?,surname=?,full_name=CONCAT(?,' ',?),email=?,phone=?,program=?,level=?,gender=?,set_name=?,status=?,updated_at=NOW(){$photoSql} WHERE id=?";
            $s=$stuConn->prepare($sql);
            if($s){
                $types = 'sssssssssss' . $photoTypes . 'i';
                $params = array_merge([$fn,$sn,$fn,$sn,$em,$ph,$pg,$lv,$g,$set,$st], $photoParams, [$id]);
                $s->bind_param($types, ...$params);
                $success=$s->execute();$s->close();$msg=$success?'Updated.':'Update failed';
            }
        }else{$msg='ID and name required.';}
        echo json_encode(['success'=>$success,'message'=>$msg]); exit;
    }
    if ($action === 'stu_delete') {
        $id=(int)($_POST['id']??0);$success=false;$msg='';
        if($id&&$stuConn){$s=$stuConn->prepare("UPDATE {$studentsDb}.students SET status='Inactive' WHERE id=?");if($s){$s->bind_param('i',$id);$success=$s->execute();$s->close();$msg=$success?'Student deactivated.':'Delete failed';}}
        else{$msg='ID required.';}
        echo json_encode(['success'=>$success,'message'=>$msg]); exit;
    }
    // ── Student Details Fetch (for full edit) ──
    if ($action === 'stu_get') {
        $id=(int)($_POST['id']??0); $data=null;
        if($id&&$stuConn){
            $s=$stuConn->prepare("SELECT * FROM {$studentsDb}.students WHERE id=?");
            if($s){$s->bind_param('i',$id);$s->execute();$data=$s->get_result()->fetch_assoc();$s->close();}
        }
        echo json_encode($data?:[]); exit;
    }
    // ── Student Requirements Status ──
    if ($action === 'stu_requirements') {
        $id=(int)($_POST['id']??0); $rows=[];
        if($id&&$conn){
            // Get the applicant linked to this student by student_number
            $stu=$stuConn->query("SELECT student_number,full_name FROM {$studentsDb}.students WHERE id=$id")->fetch_assoc();
            if($stu){
                $sn=$stuConn->real_escape_string($stu['student_number']);
                $app=$conn->query("SELECT id FROM applicants WHERE student_number='$sn' LIMIT 1")->fetch_assoc();
                $aid=$app['id']??0;
                if($aid){
                    $r=$conn->query("SELECT ar.requirement_name,ar.is_mandatory,ar.display_order,COALESCE(ars.status,'Not Submitted') as status,ars.remarks as director_notes,ars.submitted_at,ars.verified_at,ars.requirement_id FROM admission_requirements ar LEFT JOIN applicant_requirement_status ars ON ar.id=ars.requirement_id AND ars.applicant_id=$aid WHERE ar.is_active=1 ORDER BY ar.display_order");
                    if($r)$rows=$r->fetch_all(MYSQLI_ASSOC);
                }
            }
        }
        echo json_encode($rows); exit;
    }
    // ── Student Set Requirement Status ──
    if ($action === 'stu_set_req') {
        $aid=(int)($_POST['applicant_id']??0); $rid=(int)($_POST['requirement_id']??0); $st=trim($_POST['status']??'Submitted');
        if($aid&&$rid&&$conn){
            $s=$conn->prepare("INSERT INTO applicant_requirement_status(applicant_id,requirement_id,status,submitted_by,submitted_at) VALUES(?,?,?,?,NOW()) ON DUPLICATE KEY UPDATE status=?,submitted_by=?,submitted_at=NOW()");
            if($s){$s->bind_param('iissis',$aid,$rid,$st,$userId,$st,$userId);$s->execute();$s->close();}
            $conn->query("UPDATE student_admission_tracking SET requirements_completed=(SELECT COUNT(*) FROM applicant_requirement_status WHERE applicant_id=$aid AND status IN('Submitted','Verified','Received')) WHERE applicant_id=$aid");
        }
        echo json_encode(['success'=>true]); exit;
    }
    // ── Student Set Requirement by student_id (direct, no applicant lookup needed) ──
    if ($action === 'stu_set_req_by_student') {
        $sid=(int)($_POST['student_id']??0); $rid=(int)($_POST['requirement_id']??0); $st=trim($_POST['status']??'Submitted');
        $ok=false;
        if($sid&&$rid&&$stuConn&&$conn){
            $sr=$stuConn->query("SELECT student_number FROM {$studentsDb}.students WHERE id=$sid")->fetch_assoc();
            $sn=$sr['student_number']??'';
            if($sn){
                $ar=$conn->query("SELECT id FROM applicants WHERE student_number='".$conn->real_escape_string($sn)."' LIMIT 1")->fetch_assoc();
                $aid=$ar['id']??0;
                if($aid){
                    $s=$conn->prepare("INSERT INTO applicant_requirement_status(applicant_id,requirement_id,status,submitted_by,submitted_at) VALUES(?,?,?,?,NOW()) ON DUPLICATE KEY UPDATE status=?,submitted_by=?,submitted_at=NOW()");
                    if($s){$s->bind_param('iissis',$aid,$rid,$st,$userId,$st,$userId);$s->execute();$s->close();
                    $conn->query("UPDATE student_admission_tracking SET requirements_completed=(SELECT COUNT(*) FROM applicant_requirement_status WHERE applicant_id=$aid AND status IN('Submitted','Verified','Received')) WHERE applicant_id=$aid");
                    $ok=true;}
                }
            }
        }
        echo json_encode(['success'=>$ok]); exit;
    }
    if ($action === 'global_stu_search') {
        globalStudentSearchHandler($conn, $stuConn);
        exit;
    }
    echo json_encode(['success'=>false,'message'=>'Unknown action']); exit;
}

// ── Data fetching ──
$stats = ['total'=>0,'new'=>0,'review'=>0,'waiting'=>0,'verified'=>0,'approved'=>0,'rejected'=>0,'registered'=>0,'interview'=>0];
$r=$conn->query("SELECT COUNT(*) c FROM applicants"); if($r)$stats['total']=(int)$r->fetch_assoc()['c'];
foreach(['New','Under Review','Waiting for Documents','Requirements Verified','Interview Scheduled','Approved','Rejected','Registered'] as $s) $stats[str_replace(' ','_',strtolower($s))]=adCount($conn,$s);
$pendDocs=$conn->query("SELECT COUNT(*) c FROM student_documents WHERE verification_status='Pending' AND document_status='Active'")->fetch_assoc()['c']??0;

// Student data stats for dashboard cards
$missingReqCount = 0;
if ($conn) {
    $r2 = $conn->query("SELECT COUNT(*) c FROM applicant_requirement_status ars JOIN applicants a ON ars.applicant_id=a.id WHERE ars.status IN('Missing','Not Submitted','Rejected') AND a.status NOT IN('Registered','Rejected','Withdrawn')");
    if ($r2) $missingReqCount = (int)$r2->fetch_assoc()['c'];
}
$pendingAdmissionCount = $stats['new'] + $stats['waiting_for_documents'] + $stats['under_review'];

$programs=[];$r=$conn->query("SELECT * FROM academic_programs WHERE status='Active' ORDER BY program_name"); if($r)$programs=$r->fetch_all(MYSQLI_ASSOC);
$intakes=[];$r=$conn->query("SELECT * FROM intakes ORDER BY intake_year DESC,intake_month"); if($r)$intakes=$r->fetch_all(MYSQLI_ASSOC);
$requirements=[];$r=$conn->query("SELECT * FROM admission_requirements WHERE is_active=1 ORDER BY display_order"); if($r)$requirements=$r->fetch_all(MYSQLI_ASSOC);

// Recent applicants
$recent=[];$r=$conn->query("SELECT a.*,ap.program_name FROM applicants a LEFT JOIN academic_programs ap ON a.program_id=ap.id ORDER BY a.created_at DESC LIMIT 10"); if($r)$recent=$r->fetch_all(MYSQLI_ASSOC);

$pageTitle = 'Admissions Director';
$csrfToken = htmlspecialchars($_SESSION['csrf_token'] ?? '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include_once __DIR__ . '/../includes/dashboard_head.php'; ?>
<style>
:root{--adm-primary:#7C3AED;--adm-dark:#5B21B6;--adm-light:#EDE9FE;--adm-accent:#F59E0B}
body{background:#f1f5f9;font-family:'Inter',system-ui,-apple-system,sans-serif}
.adm-content{margin-left:270px;padding:24px;min-height:100vh}
.adm-header{background:linear-gradient(135deg,#7C3AED,#6D28D9,#5B21B6);color:#fff;padding:24px 28px;border-radius:16px;margin-bottom:24px;position:relative;overflow:hidden}
.adm-header::before{content:'';position:absolute;top:-50%;right:-20%;width:300px;height:300px;border-radius:50%;background:rgba(255,255,255,.05)}
.adm-header h1{margin:0;font-size:24px;font-weight:700;letter-spacing:-.3px}
.adm-header p{margin:4px 0 0;opacity:.85;font-size:14px}
.stats-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:14px;margin-bottom:24px}
.stat-card{background:#fff;border-radius:12px;padding:18px 20px;border:1px solid #e2e8f0;transition:all .2s;position:relative;overflow:hidden}
.stat-card:hover{transform:translateY(-2px);box-shadow:0 8px 25px rgba(124,58,237,.12);border-color:#7C3AED}
.stat-card .num{font-size:28px;font-weight:700;color:#0f172a;line-height:1.2}
.stat-card .lbl{font-size:12px;color:#64748b;margin-top:2px;font-weight:500}
.stat-card .icon{position:absolute;right:14px;top:50%;transform:translateY(-50%);font-size:32px;opacity:.12;color:var(--adm-primary)}
.stat-card .trend{font-size:11px;margin-top:4px}
.adm-tabs{display:flex;gap:3px;margin-bottom:24px;background:#fff;padding:6px;border-radius:12px;flex-wrap:wrap;border:1px solid #e2e8f0;overflow-x:auto}
.adm-tabs a{padding:8px 16px;border-radius:8px;color:#475569;text-decoration:none;font-size:13px;font-weight:500;transition:all .2s;white-space:nowrap}
.adm-tabs a:hover,.adm-tabs a.active{background:var(--adm-primary);color:#fff}
.card{background:#fff;border-radius:12px;border:1px solid #e2e8f0;padding:20px;margin-bottom:16px;box-shadow:0 1px 3px rgba(0,0,0,.04)}
.card h3{margin:0 0 16px;font-size:15px;font-weight:600;color:#0f172a;border-bottom:2px solid #f1f5f9;padding-bottom:12px;display:flex;align-items:center;gap:8px}
.table{font-size:13px;margin-bottom:0}.table th{font-weight:600;color:#475569;border-bottom:2px solid #f1f5f9;white-space:nowrap}
.table td{vertical-align:middle;color:#334155}.table-hover tbody tr:hover{background:#f8fafc}
.badge{font-weight:500;font-size:11px;padding:4px 10px;border-radius:6px}
.bg-purple{background:#7C3AED;color:#fff}
.btn-sm{border-radius:6px;font-size:12px;padding:4px 12px;font-weight:500}
.search-box{border:1px solid #e2e8f0;border-radius:8px;padding:8px 14px;font-size:13px;width:100%;transition:border-color .2s}
.search-box:focus{border-color:var(--adm-primary);outline:none;box-shadow:0 0 0 3px rgba(124,58,237,.1)}
.form-select-sm{border-radius:6px;border:1px solid #e2e8f0;font-size:12px}
.filter-row{display:flex;gap:10px;flex-wrap:wrap;align-items:center;margin-bottom:16px}
.filter-row>*{min-width:140px}
.progress-tracker{display:flex;align-items:center;gap:6px;flex-wrap:wrap;margin:16px 0;padding:14px;background:#f8fafc;border-radius:10px}
.progress-step{display:flex;align-items:center;gap:6px;font-size:12px;color:#94a3b8;padding:4px 10px;border-radius:6px;background:#fff;border:1px solid #e2e8f0}
.progress-step.completed{color:#059669;border-color:#059669;background:#ecfdf5}
.progress-step.active{color:#7C3AED;border-color:#7C3AED;background:#EDE9FE}
.progress-step i{font-size:10px}
.progress-arrow{color:#cbd5e1;font-size:10px}
.profile-section{padding:16px;background:#f8fafc;border-radius:10px;margin-bottom:12px}
.profile-section h4{font-size:13px;font-weight:600;color:#0f172a;margin:0 0 10px;text-transform:uppercase;letter-spacing:.5px}
.info-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:10px}
.info-item .label{font-size:11px;color:#94a3b8;font-weight:500;text-transform:uppercase}
.info-item .value{font-size:14px;color:#0f172a;font-weight:500}
.whatsapp-float{position:fixed;bottom:var(--fab-whatsapp,84px);right:24px;z-index:999;background:#25D366;color:#fff;width:56px;height:56px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:28px;box-shadow:0 4px 20px rgba(37,211,102,.4);cursor:pointer;transition:all .2s;text-decoration:none}
.whatsapp-float:hover{transform:scale(1.1);color:#fff;box-shadow:0 6px 30px rgba(37,211,102,.5)}
.empty-state{text-align:center;padding:40px 20px;color:#94a3b8}.empty-state i{font-size:48px;margin-bottom:12px;opacity:.3}
.loading-skeleton{background:linear-gradient(90deg,#f1f5f9 25%,#e2e8f0 50%,#f1f5f9 75%);background-size:200% 100%;animation:skeleton 1.5s infinite;border-radius:6px;height:20px;margin-bottom:8px}
@keyframes skeleton{0%{background-position:200% 0}100%{background-position:-200% 0}}
@media(max-width:768px){
.adm-content{margin-left:0;padding:12px}
.adm-header{padding:16px;border-radius:12px}
.adm-header h1{font-size:18px}
.adm-tabs{padding:4px;gap:2px;flex-wrap:nowrap;overflow-x:auto;-webkit-overflow-scrolling:touch;scrollbar-width:none}
.adm-tabs::-webkit-scrollbar{display:none}
.adm-tabs a{padding:6px 10px;font-size:11px;white-space:nowrap}
.stats-grid{grid-template-columns:repeat(2,1fr);gap:10px}
.stat-card{padding:14px}
.stat-card .num{font-size:22px}
.stat-card .icon{font-size:24px;opacity:.08}
.card{padding:14px;border-radius:10px}
.card h3{font-size:13px}
.table{font-size:12px}
.table td,.table th{padding:6px 8px}
.filter-row{gap:8px}
.filter-row>*{min-width:100%}
.search-box{font-size:12px;padding:6px 10px}
.progress-tracker{gap:4px;padding:10px;overflow-x:auto;flex-wrap:nowrap}
.progress-step{font-size:10px;padding:3px 6px;white-space:nowrap}
.profile-section{padding:10px;border-radius:8px}
.info-grid{grid-template-columns:1fr;gap:6px}
.info-item .value{font-size:13px}
.whatsapp-float{width:44px;height:44px;font-size:22px;bottom:calc(var(--fab-whatsapp,84px) - 8px);right:16px}
.empty-state{padding:24px 12px}
.empty-state i{font-size:32px}
#stuTable{font-size:11px}
#stuTable td{padding:4px 6px}
}
@media(max-width:480px){
.stats-grid{grid-template-columns:1fr;gap:8px}
.adm-header h1{font-size:16px}
.adm-header p{font-size:12px}
.stat-card .num{font-size:20px}
.card{padding:10px}
.card h3{font-size:12px}
.table{font-size:11px}
.table td,.table th{padding:4px 6px}
.btn-sm{font-size:11px;padding:3px 8px}
.form-select-sm,.form-control-sm{font-size:11px}
.adm-tabs a{padding:5px 8px;font-size:10px}
}
/* ── Sidebar Override — ensure it works correctly ── */
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
  .adm-content{margin-left:0!important}
  #stuTableWrap{overflow-x:auto;-webkit-overflow-scrolling:touch}
  #stuTableWrap table{font-size:11px;min-width:800px}
}
@media(max-width:480px){
  #stuTableWrap table{min-width:700px;font-size:10px}
  #stuTableWrap table td,#stuTableWrap table th{padding:3px 4px}
}
</style>
</head>
<body>
<?php include_once __DIR__ . '/../includes/sidebar.php'; ?>
<?php include_once __DIR__ . '/../includes/dashboard_topbar.php'; renderDashboardTopbar('Admissions Management'); ?>
<div class="adm-content">
<?php if (isset($_SESSION['success'])): ?><div class="alert alert-success alert-dismissible"><?=htmlspecialchars($_SESSION['success'])?><button class="btn-close" data-bs-dismiss="alert"></button></div><?php unset($_SESSION['success']); endif; ?>

<div class="adm-header">
  <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
    <div>
      <h1>Admissions &amp; Requirements</h1>
      <p><?=htmlspecialchars($userName)?> &middot; Central Admissions Office</p>
    </div>
    <button class="btn btn-sm btn-3d btn-3d-blue" onclick="openGlobalSearch()" title="Search students (Ctrl+K)">
      <i class="fas fa-search"></i> Global Search <small style="opacity:0.7">Ctrl+K</small>
    </button>
  </div>
</div>
<?php renderGlobalSearchBar($conn, $stuConn); ?>

<nav class="adm-tabs">
  <a href="director-admissions.php" class="<?=$page==='overview'?'active':''?>"><i class="fas fa-chart-pie"></i> Overview</a>
  <a href="director-admissions.php?page=applicants" class="<?=$page==='applicants'?'active':''?>"><i class="fas fa-users"></i> Applicants</a>
  <a href="director-admissions.php?page=review&aid=<?=$aid?>" class="<?=$page==='review'?'active':''?>"><i class="fas fa-clipboard-check"></i> Review</a>
  <a href="director-admissions.php?page=requirements" class="<?=$page==='requirements'?'active':''?>"><i class="fas fa-check-double"></i> Requirements</a>
  <a href="director-admissions.php?page=analytics" class="<?=$page==='analytics'?'active':''?>"><i class="fas fa-chart-bar"></i> Analytics</a>
  <a href="director-admissions.php?page=registration" class="<?=$page==='registration'?'active':''?>"><i class="fas fa-user-plus"></i> Registration</a>
  <a href="director-admissions.php?page=communications" class="<?=$page==='communications'?'active':''?>"><i class="fas fa-envelope"></i> Comms</a>
  <a href="director-admissions.php?page=submissions" class="<?=$page==='submissions'?'active':''?>"><i class="fas fa-globe"></i> Online</a>
  <a href="director-admissions.php?page=reports" class="<?=$page==='reports'?'active':''?>"><i class="fas fa-file-alt"></i> Reports</a>
  <a href="director-admissions.php?page=students" class="<?=$page==='students'?'active':''?>"><i class="fas fa-user-graduate"></i> Students</a>
  <a href="director-admissions.php?page=activity" class="<?=$page==='activity'?'active':''?>"><i class="fas fa-history"></i> Audit</a>
</nav>

<?php if ($page === 'overview'): ?>
<div class="stats-grid">
  <div class="stat-card"><div class="num"><?=number_format($totalAllStudents)?></div><div class="lbl">Total Students</div><div class="trend" style="color:#059669"><i class="fas fa-arrow-up"></i> +12% vs last month</div><i class="fas fa-user-graduate icon"></i></div>
  <div class="stat-card"><div class="num" style="color:#d97706"><?=$pendingAdmissionCount?></div><div class="lbl">Pending Admissions</div><div class="trend" style="color:#d97706"><i class="fas fa-exclamation-triangle"></i> Needs attention</div><i class="fas fa-clock icon"></i></div>
  <div class="stat-card"><div class="num" style="color:#dc2626"><?=$missingReqCount?></div><div class="lbl">Missing Requirements</div><div class="trend" style="color:#dc2626">Students with incomplete files</div><i class="fas fa-file-excel icon"></i></div>
  <div class="stat-card"><div class="num" style="color:#059669"><?=$stats['registered']?></div><div class="lbl">Fully Registered</div><div class="trend" style="color:#059669"><i class="fas fa-check-circle"></i> Excellent</div><i class="fas fa-check-double icon"></i></div>
  <div class="stat-card"><div class="num"><?=$stats['total']?></div><div class="lbl">Total Applicants</div><div class="trend" style="color:#7C3AED">All applications received</div><i class="fas fa-users icon"></i></div>
  <div class="stat-card"><div class="num" style="color:#0284c7"><?=$stats['under_review']?></div><div class="lbl">Under Review</div><div class="trend">Awaiting decision</div><i class="fas fa-search icon"></i></div>
  <div class="stat-card"><div class="num" style="color:#059669"><?=$stats['approved']?></div><div class="lbl">Approved</div><div class="trend" style="color:#059669">Ready for registration</div><i class="fas fa-check-circle icon"></i></div>
  <div class="stat-card"><div class="num"><?=$excelFileCount?></div><div class="lbl">Data Files Loaded</div><div class="trend" style="color:#7C3AED"><?=number_format($excelRowCount)?> Excel records</div><i class="fas fa-file-excel icon"></i></div>
</div>

<div class="row">
  <div class="col-md-8">
    <div class="card"><h3><i class="fas fa-chart-area text-purple"></i> Admission Trends (14 days)</h3>
      <canvas id="trendChart" height="120"></canvas>
    </div>
    <div class="card"><h3><i class="fas fa-list"></i> Recent Applications</h3>
      <div class="table-responsive"><table class="table table-hover table-sm"><thead><tr><th>#</th><th>Name</th><th>Program</th><th>Intake</th><th>Status</th><th>Date</th></tr></thead><tbody>
      <?php foreach ($recent as $a): ?><tr>
        <td><a href="director-admissions.php?page=review&aid=<?=$a['id']?>" class="text-primary"><?=htmlspecialchars($a['application_number'])?></a></td>
        <td><strong><?=htmlspecialchars($a['full_name'])?></strong></td>
        <td><?=htmlspecialchars($a['program_name']??'-')?></td>
        <td><?=htmlspecialchars($a['intake']??'-')?></td>
        <td><?=getStatusBadge($a['status'])?></td>
        <td class="text-muted"><?=date('d M',strtotime($a['created_at']))?></td>
      </tr><?php endforeach; if(empty($recent)): ?><tr><td colspan="6" class="text-muted text-center py-3">No applications yet.</td></tr><?php endif; ?>
      </tbody></table></div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card"><h3><i class="fas fa-chart-pie"></i> By Status</h3><canvas id="statusChart" height="220"></canvas></div>
    <div class="card"><h3><i class="fas fa-chart-bar"></i> By Program</h3><canvas id="programChart" height="220"></canvas></div>
    <div class="card"><h3><i class="fab fa-whatsapp text-success"></i> Quick Actions</h3>
      <a href="https://wa.me/256700451998" target="_blank" class="btn btn-success btn-sm w-100 mb-2"><i class="fab fa-whatsapp"></i> Chat Admissions (WhatsApp)</a>
      <a href="director-admissions.php?page=applicants" class="btn btn-outline-primary btn-sm w-100 mb-2"><i class="fas fa-users"></i> View All Applicants</a>
      <a href="director-admissions.php?page=registration" class="btn btn-outline-success btn-sm w-100"><i class="fas fa-user-plus"></i> Register Student</a>
    </div>
  </div>
</div>

<?php elseif ($page === 'applicants'): ?>
<div class="card">
  <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <h3 style="border:none;padding:0;margin:0"><i class="fas fa-users"></i> All Applicants</h3>
    <div>
      <button class="btn btn-sm btn-outline-primary" onclick="location='director-admissions.php?page=reports'"><i class="fas fa-download"></i> Export</button>
    </div>
  </div>
  <div class="filter-row">
    <input class="form-control form-control-sm" style="width:200px" id="appSearch" placeholder="Search name, email, phone...">
    <select class="form-select form-select-sm" id="filterStatus"><option value="all">All Statuses</option><?php foreach(['New','Under Review','Waiting for Documents','Requirements Verified','Interview Scheduled','Approved','Rejected','Registered'] as $s): ?><option value="<?=$s?>"><?=$s?></option><?php endforeach; ?></select>
    <select class="form-select form-select-sm" id="filterProgram"><option value="all">All Programs</option><?php foreach($programs as $p): ?><option value="<?=$p['id']?>"><?=htmlspecialchars($p['program_name'])?></option><?php endforeach; ?></select>
    <select class="form-select form-select-sm" id="filterIntake"><option value="all">All Intakes</option><?php foreach($intakes as $i): ?><option value="<?=htmlspecialchars($i['intake_name'])?>"><?=htmlspecialchars($i['intake_name'])?></option><?php endforeach; ?></select>
    <select class="form-select form-select-sm" id="filterGender"><option value="all">All Genders</option><option value="Male">Male</option><option value="Female">Female</option></select>
  </div>
  <div class="table-responsive"><table class="table table-hover table-sm"><thead><tr><th>App #</th><th>Name</th><th>Contact</th><th>Program</th><th>Intake</th><th>Status</th><th>Date</th><th>Action</th></tr></thead>
  <tbody id="applicantTableBody">
    <?php
    $allApps=$conn->query("SELECT a.*,ap.program_name FROM applicants a LEFT JOIN academic_programs ap ON a.program_id=ap.id ORDER BY a.created_at DESC LIMIT 200");
    if($allApps)while($a=$allApps->fetch_assoc()): ?>
    <tr>
      <td><span class="text-muted small"><?=htmlspecialchars($a['application_number'])?></span></td>
      <td><strong><?=htmlspecialchars($a['full_name'])?></strong></td>
      <td><small><?=htmlspecialchars($a['email'])?><br><?=htmlspecialchars($a['phone'])?></small></td>
      <td><?=htmlspecialchars($a['program_name']??'-')?></td>
      <td><?=htmlspecialchars($a['intake']??'-')?></td>
      <td><?=getStatusBadge($a['status'])?></td>
      <td class="text-muted small"><?=date('d/m/Y',strtotime($a['created_at']))?></td>
      <td><a href="director-admissions.php?page=review&aid=<?=$a['id']?>" class="btn btn-sm btn-outline-primary"><i class="fas fa-eye"></i></a></td>
    </tr>
    <?php endwhile; ?>
  </tbody></table></div>
</div>

<?php elseif ($page === 'review' && $aid): 
  $app=$conn->query("SELECT a.*,ap.program_name,ap.program_code,ap.duration_years FROM applicants a LEFT JOIN academic_programs ap ON a.program_id=ap.id WHERE a.id=$aid")->fetch_assoc();
  if(!$app): ?><div class="alert alert-danger">Applicant not found.</div>
  <?php else:
  $reqStatus=[];$r=$conn->query("SELECT ar.*,ars.status as curr_status,ars.remarks as curr_remarks,ars.submitted_at,ars.verified_at FROM admission_requirements ar LEFT JOIN applicant_requirement_status ars ON ar.id=ars.requirement_id AND ars.applicant_id=$aid WHERE ar.is_active=1 ORDER BY ar.display_order"); if($r)$reqStatus=$r->fetch_all(MYSQLI_ASSOC);
  $docs=[];$r=$conn->query("SELECT * FROM student_documents WHERE applicant_id=$aid AND document_status='Active' ORDER BY uploaded_at DESC"); if($r)$docs=$r->fetch_all(MYSQLI_ASSOC);
  $stage=0;$stages=['New','Under Review','Waiting for Documents','Requirements Verified','Interview Scheduled','Approved','Registered']; $stage=array_search($app['status'],$stages); if($stage===false)$stage=0;
  ?>
<div class="row">
  <div class="col-md-8">
    <div class="card">
      <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <h3 style="border:none;padding:0;margin:0"><i class="fas fa-user"></i> <span id="applicantNameDisplay"><?=htmlspecialchars($app['full_name'])?></span></h3>
        <div class="d-flex gap-1 flex-wrap">
          <button class="btn btn-sm btn-outline-primary" onclick="editApplicantDetails(<?=$aid?>)"><i class="fas fa-edit"></i> Edit</button>
          <button class="btn btn-sm btn-outline-danger" onclick="deleteApplicant(<?=$aid?>,'<?=htmlspecialchars(addslashes($app['full_name']))?>')"><i class="fas fa-trash"></i> Delete</button>
          <button class="btn btn-sm btn-primary" onclick="updateStatus(<?=$aid?>,'Under Review')"><i class="fas fa-search"></i> Review</button>
          <button class="btn btn-sm btn-warning" onclick="showRequestDocs(<?=$aid?>)"><i class="fas fa-file"></i> Request Docs</button>
          <button class="btn btn-sm btn-info text-white" onclick="showScheduleInterview(<?=$aid?>)"><i class="fas fa-calendar"></i> Interview</button>
          <button class="btn btn-sm btn-success" onclick="approveApplicant(<?=$aid?>)"><i class="fas fa-check"></i> Approve</button>
          <button class="btn btn-sm btn-danger" onclick="showReject(<?=$aid?>)"><i class="fas fa-times"></i> Reject</button>
          <button class="btn btn-sm btn-dark" onclick="registerApplicant(<?=$aid?>)"><i class="fas fa-user-graduate"></i> Register</button>
        </div>
      </div>
      <div class="progress-tracker">
        <?php foreach($stages as $i=>$s): ?>
        <div class="progress-step <?=$i<$stage?'completed':($i===$stage?'active':'')?>"><i class="fas fa-<?=$i<$stage?'check-circle':($i===$stage?'circle':'circle-notch')?>"></i> <?=$s?></div>
        <?php if($i<count($stages)-1): ?><span class="progress-arrow"><i class="fas fa-chevron-right"></i></span><?php endif; ?>
        <?php endforeach; ?>
      </div>
      <div class="row">
        <div class="col-md-6">
          <div class="profile-section"><h4>Personal Information</h4>
            <div class="info-grid" id="personalInfoGrid">
              <div class="info-item"><div class="label">Full Name</div><div class="value" id="fullNameValue"><?=htmlspecialchars($app['full_name'])?></div></div>
              <div class="info-item"><div class="label">Gender</div><div class="value" id="genderValue"><?=htmlspecialchars($app['gender']??'-')?></div></div>
              <div class="info-item"><div class="label">Date of Birth</div><div class="value" id="dobValue"><?=htmlspecialchars($app['date_of_birth']??'-')?></div></div>
              <div class="info-item"><div class="label">Nationality</div><div class="value" id="nationalityValue"><?=htmlspecialchars($app['nationality']??'Ugandan')?></div></div>
              <div class="info-item"><div class="label">District</div><div class="value" id="districtValue"><?=htmlspecialchars($app['district']??'-')?></div></div>
              <div class="info-item"><div class="label">Religion</div><div class="value" id="religionValue"><?=htmlspecialchars($app['religion']??'-')?></div></div>
            </div>
          </div>
        </div>
        <div class="col-md-6">
          <div class="profile-section"><h4>Contact &amp; Program</h4>
            <div class="info-grid" id="contactInfoGrid">
              <div class="info-item"><div class="label">Email</div><div class="value" id="emailValue"><?=htmlspecialchars($app['email']??'-')?></div></div>
              <div class="info-item"><div class="label">Phone</div><div class="value" id="phoneValue"><?=htmlspecialchars($app['phone']??'-')?></div></div>
              <div class="info-item"><div class="label">Program</div><div class="value" id="programValue"><?=htmlspecialchars($app['program_name']??'-')?></div></div>
              <div class="info-item"><div class="label">Intake</div><div class="value" id="intakeValue"><?=htmlspecialchars($app['intake']??'-')?></div></div>
              <div class="info-item"><div class="label">Application #</div><div class="value"><?=htmlspecialchars($app['application_number'])?></div></div>
              <div class="info-item"><div class="label">Source</div><div class="value"><?=htmlspecialchars($app['application_source']??'Online')?></div></div>
            </div>
          </div>
        </div>
      </div>
      <?php if($app['guardian_name']): ?>
      <div class="profile-section"><h4>Guardian / Emergency</h4>
        <div class="info-grid" id="guardianInfoGrid">
          <div class="info-item"><div class="label">Guardian</div><div class="value" id="guardianNameValue"><?=htmlspecialchars($app['guardian_name'])?> (<?=htmlspecialchars($app['guardian_relationship']??'')?>)</div></div>
          <div class="info-item"><div class="label">Guardian Phone</div><div class="value" id="guardianPhoneValue"><?=htmlspecialchars($app['guardian_phone']??'-')?></div></div>
              <div class="info-item"><div class="label">Emergency Contact</div><div class="value" id="emergencyContactNameValue"><?=htmlspecialchars($app['emergency_contact_name']??'-')?></div></div>
              <div class="info-item"><div class="label">Emergency Phone</div><div class="value" id="emergencyContactPhoneValue"><?=htmlspecialchars($app['emergency_contact_phone']??'-')?></div></div>
        </div>
      </div>
      <?php endif; ?>
      <?php if($app['rejection_reason']): ?><div class="alert alert-danger mt-2">Rejection Reason: <?=htmlspecialchars($app['rejection_reason'])?></div><?php endif; ?>
    </div>

    <div class="card"><h3><i class="fas fa-check-double"></i> Requirements Checklist</h3>
      <div class="table-responsive"><table class="table table-sm"><thead><tr><th>Requirement</th><th>Status</th><th>Submitted</th><th>Verified</th><th>Action</th></tr></thead><tbody>
      <?php foreach($reqStatus as $r): $cs=$r['curr_status']??'Not Submitted'; $notes=htmlspecialchars($r['curr_remarks']??$r['director_notes']??'', ENT_QUOTES); ?>
      <tr>
        <td><?=htmlspecialchars($r['requirement_name'])?> <?=$r['is_mandatory']?'<span class="text-danger">*</span>':''?></td>
        <td><span class="badge bg-<?=$cs==='Verified'||$cs==='Received'?'success':($cs==='Submitted'?'info':($cs==='Rejected'||$cs==='Missing'?'danger':($cs==='Not Yet Given'?'warning text-dark':'secondary')))?>"><?=$cs?></span></td>
        <td class="small text-muted"><?=$r['submitted_at']?date('d/m/Y',strtotime($r['submitted_at'])):'-'?></td>
        <td class="small text-muted"><?=$r['verified_at']?date('d/m/Y',strtotime($r['verified_at'])):'-'?></td>
        <td style="min-width:180px">
          <div class="d-flex gap-1">
            <select id="reqStatus_<?=$r['id']?>" class="form-select form-select-sm" style="width:auto;display:inline-block" onchange="setRequirement(<?=$aid?>,<?=$r['id']?>,this.value,document.getElementById('reqNote_<?=$r['id']?>').value)">
              <option value="">—</option>
              <option value="Received" <?=$cs==='Received'?'selected':''?>>Received</option>
              <option value="Submitted" <?=$cs==='Submitted'?'selected':''?>>Submitted</option>
              <option value="Verified" <?=$cs==='Verified'?'selected':''?>>Verified</option>
              <option value="Missing" <?=$cs==='Missing'?'selected':''?>>Missing</option>
              <option value="Not Yet Given" <?=$cs==='Not Yet Given'?'selected':''?>>Not Yet Given</option>
              <option value="Rejected" <?=$cs==='Rejected'?'selected':''?>>Rejected</option>
            </select>
            <button class="btn btn-sm btn-outline-secondary py-0 px-1" type="button" onclick="var n=document.getElementById('reqNote_<?=$r['id']?>');n.style.display=n.style.display==='none'?'block':'none'" title="Director Note"><i class="fas fa-sticky-note"></i></button>
          </div>
          <textarea id="reqNote_<?=$r['id']?>" class="form-control form-control-sm mt-1" rows="2" placeholder="Director's note..." style="display:<?=$notes?'block':'none'?>" onchange="setRequirement(<?=$aid?>,<?=$r['id']?>,document.getElementById('reqStatus_<?=$r['id']?>')?.value||'<?=$cs?>',this.value)"><?=$notes?></textarea>
        </td>
      </tr>
      <?php endforeach; ?>
      </tbody></table></div>
    </div>

    <div class="card"><h3><i class="fas fa-file-alt"></i> Uploaded Documents (<?=count($docs)?>)</h3>
      <?php if(empty($docs)): ?><p class="text-muted small">No documents uploaded.</p>
      <?php else: ?>
      <div class="table-responsive"><table class="table table-sm"><thead><tr><th>Document</th><th>Type</th><th>Status</th><th>Uploaded</th></tr></thead><tbody>
      <?php foreach($docs as $d): ?><tr><td><?=htmlspecialchars($d['document_name'])?></td><td><?=htmlspecialchars($d['document_type']??'-')?></td><td><?=$d['verification_status']==='Verified'?'<span class="badge bg-success">Verified</span>':($d['verification_status']==='Rejected'?'<span class="badge bg-danger">Rejected</span>':'<span class="badge bg-warning text-dark">Pending</span>')?></td><td class="small text-muted"><?=$d['uploaded_at']?></td></tr><?php endforeach; ?>
      </tbody></table></div><?php endif; ?>
    </div>
  </div>

  <div class="col-md-4">
    <div class="card"><h3><i class="fas fa-envelope"></i> Send Communication</h3>
      <form onsubmit="sendComm(event,this)"><input type="hidden" name="applicant_id" value="<?=$aid?>">
        <div class="mb-2"><select class="form-select form-select-sm" name="comm_type"><option value="Portal">Portal Notification</option><option value="Email">Email</option><option value="SMS">SMS</option><option value="WhatsApp">WhatsApp</option></select></div>
        <div class="mb-2"><input class="form-control form-control-sm" name="subject" placeholder="Subject"></div>
        <div class="mb-2"><textarea class="form-control form-control-sm" name="message" rows="3" placeholder="Message" required></textarea></div>
        <button class="btn btn-sm btn-primary w-100"><i class="fas fa-paper-plane"></i> Send</button>
      </form>
    </div>
    <div class="card"><h3><i class="fas fa-calendar-check"></i> Schedule Interview</h3>
      <form onsubmit="scheduleInterview(event,this)"><input type="hidden" name="applicant_id" value="<?=$aid?>">
        <div class="mb-2"><input type="datetime-local" class="form-control form-control-sm" name="interview_date" required></div>
        <div class="mb-2"><select class="form-select form-select-sm" name="interview_mode"><option value="In-Person">In Person</option><option value="Online">Online</option><option value="Phone">Phone</option></select></div>
        <div class="mb-2"><input class="form-control form-control-sm" name="interview_link" placeholder="Meeting Link (if online)"></div>
        <button class="btn btn-sm btn-info text-white w-100"><i class="fas fa-calendar-plus"></i> Schedule</button>
      </form>
    </div>
    <div class="card"><h3><i class="fas fa-history"></i> Recent Activity</h3>
      <div id="activityLog" style="max-height:300px;overflow-y:auto"><div class="text-muted small p-2">Loading...</div></div>
    </div>
    <div class="card"><h3><i class="fas fa-comments"></i> Communication History</h3>
      <div id="commHistory" style="max-height:300px;overflow-y:auto"><div class="text-muted small p-2">Loading...</div></div>
    </div>
  </div>
</div>
<script>
var _tk='<?=$csrfToken?>';
function loadActivity(){fetch('director-admissions.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'action=get_activity&applicant_id=<?=$aid?>&csrf_token=<?=$csrfToken?>'}).then(r=>r.json()).then(d=>{let h=document.getElementById('activityLog');if(!d||d.length===0){h.innerHTML='<div class="text-muted small p-2">No activity.</div>';return;}h.innerHTML=d.map(a=>'<div class="p-2 border-bottom small"><strong>'+a.action+'</strong><br><span class="text-muted">'+a.description+'<br>'+a.created_at+'</span></div>').join('');});}
function loadComm(){fetch('director-admissions.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'action=get_communications&applicant_id=<?=$aid?>&csrf_token=<?=$csrfToken?>'}).then(r=>r.json()).then(d=>{let h=document.getElementById('commHistory');if(!d||d.length===0){h.innerHTML='<div class="text-muted small p-2">No communications.</div>';return;}h.innerHTML=d.map(c=>'<div class="p-2 border-bottom small"><strong>['+c.communication_type+']</strong> '+c.subject+'<br><span class="text-muted">'+c.message.substring(0,100)+'<br>'+c.sent_at+'</span></div>').join('');});}
loadActivity();loadComm();
function sendComm(e,f){e.preventDefault();const fd=new FormData(f);fd.append('action','send_communication');fd.append('csrf_token','<?=$csrfToken?>');
fetch('director-admissions.php',{method:'POST',body:new URLSearchParams(fd)}).then(r=>r.json()).then(d=>{if(d.success){alert('Sent!');loadComm();f.reset();}});}
function scheduleInterview(e,f){e.preventDefault();const fd=new FormData(f);fd.append('action','schedule_interview');fd.append('csrf_token','<?=$csrfToken?>');
fetch('director-admissions.php',{method:'POST',body:new URLSearchParams(fd)}).then(r=>r.json()).then(d=>{if(d.success){alert('Interview scheduled!');location.reload();}});}
function updateStatus(id,st){if(!confirm('Change status to '+st+'?'))return;fetch('director-admissions.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'action=update_status&id='+id+'&status='+encodeURIComponent(st)+'&csrf_token=<?=$csrfToken?>'}).then(r=>r.json()).then(d=>{if(d.success){showToast('Status updated to '+st,'success');location.reload();}else{showToast('Update failed','danger');}});}
function approveApplicant(id){if(!confirm('Approve this application?'))return;fetch('director-admissions.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'action=approve&id='+id+'&csrf_token=<?=$csrfToken?>'}).then(r=>r.json()).then(d=>{if(d.success){showToast('Application approved','success');location.reload();}else{showToast('Approval failed','danger');}});}
function showReject(id){const r=prompt('Rejection reason:');if(!r)return;fetch('director-admissions.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'action=reject&id='+id+'&reason='+encodeURIComponent(r)+'&csrf_token=<?=$csrfToken?>'}).then(r=>r.json()).then(d=>{if(d.success){showToast('Application rejected','success');location.reload();}else{showToast('Rejection failed','danger');}});}
function showRequestDocs(id){const m=prompt('Message to applicant:');if(!m)return;fetch('director-admissions.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'action=request_docs&applicant_id='+id+'&message='+encodeURIComponent(m)+'&csrf_token=<?=$csrfToken?>'}).then(r=>r.json()).then(d=>{if(d.success){showToast('Document request sent','success');location.reload();}else{showToast('Request failed','danger');}});}
function showScheduleInterview(id){document.querySelector('[name=interview_date]')?.scrollIntoView({behavior:'smooth'});}
function registerApplicant(id){if(!confirm('Register this applicant as a student?'))return;fetch('director-admissions.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'action=register_student&id='+id+'&csrf_token=<?=$csrfToken?>'}).then(r=>r.json()).then(d=>{if(d.success){showToast('Student registered!\nStudent #: '+d.student_number+'\nUsername: '+d.username+'\nPassword: '+d.password+'\nProgram: '+d.program,'success');location.reload();}else{showToast('Registration failed','danger');}});}
function setRequirement(aid,rid,val,notes){if(!val)return;var body='action=set_requirement&applicant_id='+aid+'&requirement_id='+rid+'&status='+encodeURIComponent(val)+'&csrf_token=<?=$csrfToken?>';if(typeof notes!=='undefined')body+='&notes='+encodeURIComponent(notes);fetch('director-admissions.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:body}).then(r=>r.json()).then(d=>{if(d.success){showToast('Requirement updated to '+val,'success');}else{showToast('Update failed','danger');}});}

// Edit applicant functionality
function editApplicantDetails(id) {
    // Convert display fields to editable inputs
    const fields = [
        {id: 'fullNameValue', name: 'full_name', type: 'text'},
        {id: 'genderValue', name: 'gender', type: 'select', options: ['Male','Female','Other']},
        {id: 'dobValue', name: 'date_of_birth', type: 'date'},
        {id: 'nationalityValue', name: 'nationality', type: 'text'},
        {id: 'districtValue', name: 'district', type: 'text'},
        {id: 'religionValue', name: 'religion', type: 'text'},
        {id: 'emailValue', name: 'email', type: 'email'},
        {id: 'phoneValue', name: 'phone', type: 'tel'},
        {id: 'guardianNameValue', name: 'guardian_name', type: 'text'},
        {id: 'guardianPhoneValue', name: 'guardian_phone', type: 'tel'},
        {id: 'emergencyContactNameValue', name: 'emergency_contact_name', type: 'text'},
        {id: 'emergencyContactPhoneValue', name: 'emergency_contact_phone', type: 'tel'}
    ];
    
    fields.forEach(field => {
        const element = document.getElementById(field.id);
        if (element) {
            const currentValue = element.textContent.trim();
            let inputHtml = '';
            
            if (field.type === 'select') {
                inputHtml = `<select class="form-control form-control-sm" id="edit_${field.name}">`;
                field.options.forEach(opt => {
                    inputHtml += `<option value="${opt}" ${currentValue === opt ? 'selected' : ''}>${opt}</option>`;
                });
                inputHtml += `</select>`;
            } else {
                inputHtml = `<input type="${field.type}" class="form-control form-control-sm" id="edit_${field.name}" value="${currentValue}">`;
            }
            
            element.innerHTML = inputHtml;
        }
    });
    
    // Add save/cancel buttons
    const actionDiv = document.createElement('div');
    actionDiv.className = 'mt-3 d-flex gap-2';
    actionDiv.innerHTML = `
        <button class="btn btn-sm btn-success" onclick="saveApplicantDetails(${id})"><i class="fas fa-save"></i> Save Changes</button>
        <button class="btn btn-sm btn-secondary" onclick="cancelEditApplicant()"><i class="fas fa-times"></i> Cancel</button>
    `;
    
    const personalInfoGrid = document.getElementById('personalInfoGrid');
    personalInfoGrid.parentNode.insertBefore(actionDiv, personalInfoGrid.nextSibling);
}

function saveApplicantDetails(id) {
    const fields = ['full_name','gender','date_of_birth','nationality','district','religion','email','phone','guardian_name','guardian_phone','emergency_contact_name','emergency_contact_phone'];
    const data = {id: id};
    
    fields.forEach(field => {
        const input = document.getElementById(`edit_${field}`);
        if (input) {
            data[field] = input.value;
        }
    });
    
    const body = new URLSearchParams();
    body.append('action', 'update_applicant');
    Object.keys(data).forEach(key => {
        body.append(key, data[key]);
    });
    body.append('csrf_token', _tk);
    
    fetch('director-admissions.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: body
    })
    .then(r => r.json())
    .then(result => {
        if (result.success) {
            showToast('Applicant details updated', 'success');
            location.reload();
        } else {
            showToast(result.message || 'Update failed', 'danger');
        }
    });
}

function cancelEditApplicant() {
    location.reload();
}

function deleteApplicant(id, name) {
    if (!confirm(`Are you sure you want to delete applicant "${name}"? This action cannot be undone.`)) return;
    
    const body = new URLSearchParams();
    body.append('action', 'delete_applicant');
    body.append('id', id);
    body.append('csrf_token', _tk);
    
    fetch('director-admissions.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: body
    })
    .then(r => r.json())
    .then(result => {
        if (result.success) {
            showToast('Applicant deleted', 'success');
            // Redirect to applicants list after 1 second
            setTimeout(() => {
                window.location.href = 'director-admissions.php?page=applicants';
            }, 1000);
        } else {
            showToast(result.message || 'Delete failed', 'danger');
        }
    });
}
</script>
<?php endif; ?>

<?php elseif ($page === 'requirements'): ?>
<div class="card"><h3><i class="fas fa-check-double"></i> Requirements Portal — All Applicants</h3>
  <p class="text-muted small mb-3">Matrix view: each row is an applicant, each column is a requirement. Click to toggle status. Checkboxes for bulk marking.</p>
  
  <!-- Advanced Filters -->
  <div class="filter-row">
    <input type="text" class="form-control form-control-sm" id="reqSearch" placeholder="Search name, ID, phone..." style="min-width:200px">
    <select class="form-select form-select-sm" id="reqFilterStatus"><option value="all">All Status</option><option value="New">New</option><option value="Under Review">Under Review</option><option value="Waiting for Documents">Waiting</option><option value="Requirements Verified">Requirements Verified</option><option value="Approved">Approved</option><option value="Registered">Registered</option><option value="Rejected">Rejected</option></select>
    <select class="form-select form-select-sm" id="reqFilterProgram"><option value="all">All Programs</option><?php foreach($programs as $p): ?><option value="<?=$p['id']?>"><?=htmlspecialchars($p['program_name'])?></option><?php endforeach; ?></select>
    <select class="form-select form-select-sm" id="reqFilterIntake"><option value="all">All Intakes</option><?php foreach($intakes as $i): ?><option value="<?=htmlspecialchars($i['intake_name'])?>"><?=htmlspecialchars($i['intake_name'])?></option><?php endforeach; ?></select>
    <button class="btn btn-sm btn-primary" onclick="loadRequirementsPortal()"><i class="fas fa-search"></i> Search</button>
    <button class="btn btn-sm btn-outline-secondary" onclick="clearReqFilters()"><i class="fas fa-times"></i> Clear</button>
  </div>
  
  <!-- Bulk Actions -->
  <div class="d-flex gap-2 mb-3 align-items-center">
    <div class="form-check">
      <input class="form-check-input" type="checkbox" id="selectAllReqs" onchange="toggleAllRequirements(this.checked)">
      <label class="form-check-label small" for="selectAllReqs">Select All</label>
    </div>
    <select class="form-select form-select-sm" id="bulkStatus" style="width:150px">
      <option value="">Bulk Mark As...</option>
      <option value="Verified">Verified</option>
      <option value="Submitted">Submitted</option>
      <option value="Missing">Missing</option>
      <option value="Not Yet Given">Not Yet Given</option>
      <option value="Rejected">Rejected</option>
    </select>
    <button class="btn btn-sm btn-success" onclick="bulkUpdateRequirements()"><i class="fas fa-check"></i> Apply</button>
    <div class="ms-auto">
      <button class="btn btn-sm btn-outline-info" onclick="exportRequirementsCSV()"><i class="fas fa-download"></i> Export CSV</button>
    </div>
  </div>
  
  <div class="table-responsive" style="max-height:600px;overflow-y:auto" id="reqPortalTable">
    <div class="text-center py-4"><i class="fas fa-spinner fa-spin"></i> Loading requirements portal...</div>
  </div>
  
  <div class="mt-3 small text-muted" id="reqStats"></div>
</div>

<script>
var _tk=window.CSRF_TOKEN||'<?=$csrfToken?>';
let allRequirements = <?= json_encode($requirements) ?>;
let selectedReqs = {};

function loadRequirementsPortal() {
    const search = document.getElementById('reqSearch').value;
    const status = document.getElementById('reqFilterStatus').value;
    const program = document.getElementById('reqFilterProgram').value;
    const intake = document.getElementById('reqFilterIntake').value;
    
    const body = new URLSearchParams();
    body.append('action', 'filter_applicants');
    body.append('csrf_token', _tk);
    body.append('search', search);
    body.append('status', status === 'all' ? '' : status);
    body.append('program_id', program === 'all' ? '' : program);
    body.append('intake', intake === 'all' ? '' : intake);
    body.append('limit', 100);
    
    fetch('director-admissions.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: body
    })
    .then(r => r.json())
    .then(applicants => {
        if (!applicants || applicants.length === 0) {
            document.getElementById('reqPortalTable').innerHTML = '<p class="text-muted text-center py-4">No applicants found matching your criteria.</p>';
            document.getElementById('reqStats').innerHTML = '0 applicants';
            return;
        }
        
        // Get requirement status for all applicants in batch
        const appIds = applicants.map(a => a.id);
        const body2 = new URLSearchParams();
        body2.append('action', 'get_bulk_requirement_status');
        body2.append('csrf_token', _tk);
        body2.append('applicant_ids', JSON.stringify(appIds));
        
        fetch('director-admissions.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: body2
        })
        .then(r => r.json())
        .then(statusData => {
            renderRequirementsTable(applicants, statusData);
        })
        .catch(err => {
            // Fallback: load individually
            renderRequirementsTable(applicants, {});
        });
    });
}

function renderRequirementsTable(applicants, statusData) {
    let html = '<table class="table table-sm table-bordered table-hover">';
    
    // Header row
    html += '<thead><tr>';
    html += '<th style="position:sticky;top:0;background:#fff;z-index:3;min-width:180px">Applicant</th>';
    
    allRequirements.forEach((req, idx) => {
        const isMandatory = req.is_mandatory == 1;
        const shortName = req.requirement_name.length > 15 ? req.requirement_name.substring(0,15)+'...' : req.requirement_name;
        html += `<th style="position:sticky;top:0;background:#fff;z-index:3;font-size:10px;white-space:nowrap;padding:4px;min-width:60px;text-align:center" title="${req.requirement_name}">`;
        html += `<div class="d-flex flex-column align-items-center">`;
        html += `<span>${shortName}</span>`;
        if (isMandatory) html += '<span class="text-danger small">*</span>';
        html += `</div></th>`;
    });
    
    html += '<th style="position:sticky;top:0;background:#fff;z-index:3;min-width:60px">%</th>';
    html += '</tr></thead><tbody>';
    
    // Data rows
    applicants.forEach(app => {
        const appStatus = statusData[app.id] || {};
        let completed = 0;
        let totalMandatory = 0;
        
        html += `<tr>`;
        html += `<td class="small">`;
        html += `<div class="d-flex align-items-center gap-1">`;
        html += `<input type="checkbox" class="form-check-input req-app-check" data-app-id="${app.id}" onchange="toggleAppRequirements(${app.id}, this.checked)">`;
        html += `<a href="director-admissions.php?page=review&aid=${app.id}" class="text-primary text-decoration-none">${escapeHtml(app.full_name)}</a>`;
        html += `</div>`;
        html += `<div class="text-muted">${escapeHtml(app.application_number || '')}</div>`;
        html += `<div><small class="badge bg-${getStatusColor(app.status)}">${app.status}</small></div>`;
        html += `</td>`;
        
        allRequirements.forEach(req => {
            const isMandatory = req.is_mandatory == 1;
            if (isMandatory) totalMandatory++;
            
            const status = appStatus[req.id] || 'Not Submitted';
            const isCompleted = ['Submitted','Verified','Received'].includes(status);
            if (isCompleted && isMandatory) completed++;
            
            const statusColors = {
                'Verified': 'success',
                'Received': 'success',
                'Submitted': 'info',
                'Missing': 'danger',
                'Rejected': 'danger',
                'Not Yet Given': 'warning text-dark',
                'Not Submitted': 'secondary'
            };
            
            const colorClass = statusColors[status] || 'secondary';
            const shortStatus = status.substring(0,3);
            
            html += `<td class="text-center p-1 align-middle" style="min-width:60px">`;
            html += `<div class="d-flex flex-column align-items-center gap-1">`;
            html += `<input type="checkbox" class="form-check-input req-item-check" data-app-id="${app.id}" data-req-id="${req.id}" ${isCompleted ? 'checked' : ''} onchange="toggleRequirementCheck(${app.id}, ${req.id}, this.checked)">`;
            html += `<span class="badge bg-${colorClass} small" style="font-size:9px;cursor:pointer" onclick="showQuickStatusModal(${app.id}, ${req.id}, '${status}')">${shortStatus}</span>`;
            html += `</div>`;
            html += `</td>`;
        });
        
        const percentage = totalMandatory > 0 ? Math.round((completed / totalMandatory) * 100) : 0;
        const progressColor = percentage >= 100 ? 'success' : percentage >= 50 ? 'warning' : 'danger';
        
        html += `<td class="text-center align-middle">`;
        html += `<div class="progress" style="height:8px;width:60px;margin:0 auto">`;
        html += `<div class="progress-bar bg-${progressColor}" role="progressbar" style="width: ${percentage}%" title="${percentage}% complete"></div>`;
        html += `</div>`;
        html += `<small class="d-block mt-1">${percentage}%</small>`;
        html += `</td>`;
        
        html += `</tr>`;
    });
    
    html += '</tbody></table>';
    document.getElementById('reqPortalTable').innerHTML = html;
    document.getElementById('reqStats').innerHTML = `${applicants.length} applicants displayed`;
    
    // Initialize selectedReqs
    selectedReqs = {};
}

function getStatusColor(status) {
    const colors = {
        'New': 'primary',
        'Under Review': 'info',
        'Waiting for Documents': 'warning',
        'Requirements Verified': 'success',
        'Approved': 'success',
        'Registered': 'dark',
        'Rejected': 'danger'
    };
    return colors[status] || 'secondary';
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function toggleAllRequirements(checked) {
    document.querySelectorAll('.req-app-check').forEach(cb => {
        cb.checked = checked;
        const appId = cb.dataset.appId;
        toggleAppRequirements(appId, checked);
    });
}

function toggleAppRequirements(appId, checked) {
    document.querySelectorAll(`.req-item-check[data-app-id="${appId}"]`).forEach(cb => {
        cb.checked = checked;
        const reqId = cb.dataset.reqId;
        toggleRequirementCheck(appId, reqId, checked);
    });
}

function toggleRequirementCheck(appId, reqId, checked) {
    if (!selectedReqs[appId]) selectedReqs[appId] = {};
    selectedReqs[appId][reqId] = checked;
}

function bulkUpdateRequirements() {
    const status = document.getElementById('bulkStatus').value;
    if (!status) {
        alert('Please select a status to apply');
        return;
    }
    
    const updates = [];
    for (const appId in selectedReqs) {
        for (const reqId in selectedReqs[appId]) {
            if (selectedReqs[appId][reqId]) {
                updates.push({appId, reqId});
            }
        }
    }
    
    if (updates.length === 0) {
        alert('No requirements selected');
        return;
    }
    
    if (!confirm(`Apply "${status}" to ${updates.length} selected requirement(s)?`)) return;
    
    const body = new URLSearchParams();
    body.append('action', 'bulk_set_requirements');
    body.append('updates', JSON.stringify(updates));
    body.append('status', status);
    body.append('csrf_token', _tk);
    
    fetch('director-admissions.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: body
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            showToast(`Updated ${updates.length} requirement(s) to "${status}"`, 'success');
            loadRequirementsPortal();
            selectedReqs = {};
            document.getElementById('bulkStatus').value = '';
            document.getElementById('selectAllReqs').checked = false;
        } else {
            showToast(data.message || 'Update failed', 'danger');
        }
    });
}

function showQuickStatusModal(appId, reqId, currentStatus) {
    const req = allRequirements.find(r => r.id == reqId);
    if (!req) return;
    
    const modalHtml = `
        <div class="modal fade" id="quickStatusModal">
            <div class="modal-dialog modal-sm">
                <div class="modal-content">
                    <div class="modal-header">
                        <h6 class="modal-title">Update Requirement</h6>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p class="small"><strong>${req.requirement_name}</strong></p>
                        <select class="form-select form-select-sm mb-2" id="quickStatusSelect">
                            <option value="Verified" ${currentStatus === 'Verified' ? 'selected' : ''}>Verified</option>
                            <option value="Submitted" ${currentStatus === 'Submitted' ? 'selected' : ''}>Submitted</option>
                            <option value="Missing" ${currentStatus === 'Missing' ? 'selected' : ''}>Missing</option>
                            <option value="Not Yet Given" ${currentStatus === 'Not Yet Given' ? 'selected' : ''}>Not Yet Given</option>
                            <option value="Rejected" ${currentStatus === 'Rejected' ? 'selected' : ''}>Rejected</option>
                            <option value="Not Submitted" ${currentStatus === 'Not Submitted' ? 'selected' : ''}>Not Submitted</option>
                        </select>
                        <textarea class="form-control form-control-sm" id="quickStatusNotes" rows="2" placeholder="Director's notes (optional)"></textarea>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-sm btn-primary" onclick="updateQuickStatus(${appId}, ${reqId})">Update</button>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // Remove existing modal if any
    const existingModal = document.getElementById('quickStatusModal');
    if (existingModal) existingModal.remove();
    
    // Add new modal
    document.body.insertAdjacentHTML('beforeend', modalHtml);
    const modal = new bootstrap.Modal(document.getElementById('quickStatusModal'));
    modal.show();
}

function updateQuickStatus(appId, reqId) {
    const status = document.getElementById('quickStatusSelect').value;
    const notes = document.getElementById('quickStatusNotes').value;
    
    const body = new URLSearchParams();
    body.append('action', 'set_requirement');
    body.append('applicant_id', appId);
    body.append('requirement_id', reqId);
    body.append('status', status);
    body.append('notes', notes);
    body.append('csrf_token', _tk);
    
    fetch('director-admissions.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: body
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            showToast('Requirement updated', 'success');
            bootstrap.Modal.getInstance(document.getElementById('quickStatusModal')).hide();
            loadRequirementsPortal();
        } else {
            showToast('Update failed', 'danger');
        }
    });
}

function clearReqFilters() {
    document.getElementById('reqSearch').value = '';
    document.getElementById('reqFilterStatus').value = 'all';
    document.getElementById('reqFilterProgram').value = 'all';
    document.getElementById('reqFilterIntake').value = 'all';
    loadRequirementsPortal();
}

function exportRequirementsCSV() {
    const search = document.getElementById('reqSearch').value;
    const status = document.getElementById('reqFilterStatus').value;
    const program = document.getElementById('reqFilterProgram').value;
    const intake = document.getElementById('reqFilterIntake').value;
    
    const body = new URLSearchParams();
    body.append('action', 'export_requirements_csv');
    body.append('search', search);
    body.append('status', status === 'all' ? '' : status);
    body.append('program_id', program === 'all' ? '' : program);
    body.append('intake', intake === 'all' ? '' : intake);
    body.append('csrf_token', _tk);
    
    fetch('director-admissions.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: body
    })
    .then(r => r.blob())
    .then(blob => {
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `requirements_export_${new Date().toISOString().split('T')[0]}.csv`;
        document.body.appendChild(a);
        a.click();
        window.URL.revokeObjectURL(url);
        document.body.removeChild(a);
    });
}

// Initialize
loadRequirementsPortal();
</script>

<?php elseif ($page === 'analytics'): ?>
<div class="stats-grid">
  <div class="stat-card"><div class="num"><?=$stats['total']?></div><div class="lbl">Total Applications</div><i class="fas fa-database icon"></i></div>
  <div class="stat-card"><div class="num" style="color:#059669"><?=$stats['approved']?></div><div class="lbl">Approved</div><i class="fas fa-check-circle icon"></i></div>
  <div class="stat-card"><div class="num" style="color:#dc2626"><?=$stats['rejected']?></div><div class="lbl">Rejected</div><i class="fas fa-times-circle icon"></i></div>
  <div class="stat-card"><div class="num" style="color:#7C3AED"><?=$stats['registered']?></div><div class="lbl">Registered</div><i class="fas fa-user-graduate icon"></i></div>
  <div class="stat-card"><div class="num"><?=$pendDocs?></div><div class="lbl">Pending Docs</div><i class="fas fa-clock icon"></i></div>
</div>
<div class="row">
  <div class="col-md-6"><div class="card"><h3><i class="fas fa-chart-bar"></i> By Status</h3><canvas id="analyticsStatusChart" height="200"></canvas></div></div>
  <div class="col-md-6"><div class="card"><h3><i class="fas fa-chart-bar"></i> By Program</h3><canvas id="analyticsProgramChart" height="200"></canvas></div></div>
  <div class="col-md-6"><div class="card"><h3><i class="fas fa-chart-bar"></i> By Gender</h3><canvas id="analyticsGenderChart" height="200"></canvas></div></div>
  <div class="col-md-6"><div class="card"><h3><i class="fas fa-chart-bar"></i> Nationality Distribution</h3><canvas id="analyticsNationalityChart" height="200"></canvas></div></div>
</div>

<?php elseif ($page === 'registration'): ?>
<div class="card"><h3><i class="fas fa-user-plus"></i> Register Approved Applicant as Student</h3>
  <p class="text-muted small">Select an approved applicant to complete registration. This will create student records across all modules.</p>
  <div class="row">
    <div class="col-md-6">
      <select class="form-select" id="regSelect" size="10">
        <?php $apps=$conn->query("SELECT a.*,ap.program_name FROM applicants a LEFT JOIN academic_programs ap ON a.program_id=ap.id WHERE a.status='Approved' ORDER BY a.full_name"); if($apps)while($a=$apps->fetch_assoc()): ?>
        <option value="<?=$a['id']?>"><?=htmlspecialchars($a['full_name'])?> — <?=htmlspecialchars($a['application_number'])?> (<?=htmlspecialchars($a['program_name']??'')?>)</option>
        <?php endwhile; ?>
      </select>
    </div>
    <div class="col-md-6">
      <div class="p-4 text-center border rounded bg-light">
        <i class="fas fa-user-graduate fa-4x text-muted mb-3"></i>
        <p class="text-muted">Select an approved applicant from the list, then click Register.</p>
        <button class="btn btn-lg btn-success" onclick="doRegister()"><i class="fas fa-user-plus"></i> Register Selected Applicant</button>
      </div>
    </div>
  </div>
</div>

<?php elseif ($page === 'communications'): ?>
<div class="row">
  <div class="col-md-12">
    <div class="card"><h3><i class="fas fa-comments"></i> Communication Center</h3>
      <p class="text-muted small mb-3">Send messages to applicants and view communication history.</p>
      <div class="row">
        <div class="col-md-5">
          <div class="mb-3"><label class="form-label small fw-bold">Select Applicant</label>
            <select class="form-select" id="commApplicant">
              <option value="">— Select —</option>
              <?php $allApps=$conn->query("SELECT id,full_name,application_number,email,phone FROM applicants ORDER BY full_name"); if($allApps)while($a=$allApps->fetch_assoc()): ?>
              <option value="<?=$a['id']?>" data-email="<?=htmlspecialchars($a['email'])?>" data-phone="<?=htmlspecialchars($a['phone'])?>"><?=htmlspecialchars($a['full_name'])?> (<?=htmlspecialchars($a['application_number'])?>)</option>
              <?php endwhile; ?>
            </select>
          </div>
          <form onsubmit="sendComm(event,this)">
            <div class="mb-2"><select class="form-select form-select-sm" name="comm_type"><option value="Portal">Portal Notification</option><option value="Email">Email</option><option value="SMS">SMS</option><option value="WhatsApp">WhatsApp</option></select></div>
            <div class="mb-2"><input class="form-control form-control-sm" name="subject" placeholder="Subject" required></div>
            <div class="mb-2"><textarea class="form-control form-control-sm" name="message" rows="4" placeholder="Message" required></textarea></div>
            <button class="btn btn-sm btn-primary"><i class="fas fa-paper-plane"></i> Send</button>
          </form>
        </div>
        <div class="col-md-7">
          <h4 class="fs-6 mb-2">Recent Communications</h4>
          <div style="max-height:500px;overflow-y:auto" id="allCommHistory">
            <?php
            $comms=$conn->query("SELECT c.*,a.full_name as app_name FROM admission_communications c JOIN applicants a ON c.applicant_id=a.id ORDER BY c.sent_at DESC LIMIT 50");
            if($comms&&$comms->num_rows>0): while($c=$comms->fetch_assoc()): ?>
            <div class="p-2 border-bottom small"><strong>[<?=htmlspecialchars($c['communication_type'])?>]</strong> <?=htmlspecialchars($c['subject'])?> → <span class="text-primary"><?=htmlspecialchars($c['app_name'])?></span><br><span class="text-muted"><?=htmlspecialchars(substr($c['message'],0,150))?><br><span class="smaller"><?=$c['sent_at']?></span></span></div>
            <?php endwhile; else: ?><p class="text-muted small">No communications yet.</p><?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php elseif ($page === 'submissions'): ?>
<div class="card"><h3><i class="fas fa-globe"></i> Online Website Submissions</h3>
  <p class="text-muted small">Applications submitted via the public website application form. Import them into the admissions system to process.</p>
  <div class="table-responsive"><table class="table table-sm table-hover"><thead><tr><th>Name</th><th>Email</th><th>Phone</th><th>Program</th><th>Date</th><th>Action</th></tr></thead><tbody id="webSubTable">
    <tr><td colspan="6" class="text-muted text-center"><div class="loading-skeleton" style="width:80%;margin:8px auto"></div>Loading...</td></tr>
  </tbody></table></div>
</div>

<?php elseif ($page === 'reports'): ?>
<div class="card"><h3><i class="fas fa-file-alt"></i> Admission Reports</h3>
  <div class="row mb-3">
    <div class="col-md-3"><label class="form-label small">From</label><input type="date" class="form-control form-control-sm" id="rptFrom" value="<?=date('Y-m-d',strtotime('-30 days'))?>"></div>
    <div class="col-md-3"><label class="form-label small">To</label><input type="date" class="form-control form-control-sm" id="rptTo" value="<?=date('Y-m-d')?>"></div>
    <div class="col-md-3"><label class="form-label small">&nbsp;</label><button class="btn btn-sm btn-primary w-100" onclick="loadReport()"><i class="fas fa-sync"></i> Generate</button></div>
    <div class="col-md-3"><label class="form-label small">&nbsp;</label>
      <button class="btn btn-sm btn-success w-100" onclick="exportCSV('applicants')"><i class="fas fa-file-csv"></i> Export CSV</button>
    </div>
  </div>
  <div id="reportContent">
    <div class="row" id="reportStatsRow"></div>
    <div class="row">
      <div class="col-md-6"><div class="card"><h3>By Status</h3><canvas id="rptStatusChart" height="180"></canvas></div></div>
      <div class="col-md-6"><div class="card"><h3>By Program</h3><canvas id="rptProgramChart" height="180"></canvas></div></div>
      <div class="col-md-6"><div class="card"><h3>By Intake</h3><canvas id="rptIntakeChart" height="180"></canvas></div></div>
      <div class="col-md-6"><div class="card"><h3>By Gender</h3><canvas id="rptGenderChart" height="180"></canvas></div></div>
    </div>
  </div>
</div>

<?php elseif ($page === 'students'): ?>
<?php
// Get filter options from StudentDataLoader
$filterOpts = $stuLoader->getFilterOptions();
$programsList = $filterOpts['programs'] ?? [];
$setsList = $filterOpts['sets'] ?? [];
$levelsList = $filterOpts['levels'] ?? [];
$yearsList = $filterOpts['years'] ?? [];
?>
<div class="card">
  <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <h3 style="border:none;padding:0;margin:0"><i class="fas fa-user-graduate"></i> Student Directory</h3>
    <div class="d-flex gap-1 flex-wrap">
      <span class="badge bg-light text-dark border me-1"><i class="fas fa-database"></i> <?=number_format($excelRowCount)?> Excel</span>
      <span class="badge bg-light text-dark border me-1"><i class="fas fa-server"></i> DB</span>
      <button class="btn btn-sm btn-success" onclick="showStuModal(0)"><i class="fas fa-plus"></i> Add Student</button>
    </div>
  </div>

  <!-- Advanced Filters -->
  <div class="filter-row">
    <input type="text" id="stuKeyword" class="form-control form-control-sm" style="min-width:160px;flex:1" placeholder="Search name, ID, phone, email..." oninput="filterStudents()">
    <select id="stuFilterSet" class="form-select form-select-sm" onchange="filterStudents()"><option value="">All Sets</option><?php foreach($setsList as $s): ?><option value="<?=htmlspecialchars($s)?>"><?=htmlspecialchars($s)?></option><?php endforeach; ?></select>
    <select id="stuFilterProgram" class="form-select form-select-sm" onchange="filterStudents()"><option value="">All Programs</option><?php foreach($programsList as $p): ?><option value="<?=htmlspecialchars($p)?>"><?=htmlspecialchars($p)?></option><?php endforeach; ?></select>
    <select id="stuFilterLevel" class="form-select form-select-sm" onchange="filterStudents()"><option value="">All Levels</option><?php foreach($levelsList as $l): ?><option value="<?=htmlspecialchars($l)?>"><?=htmlspecialchars($l)?></option><?php endforeach; ?></select>
    <select id="stuFilterYear" class="form-select form-select-sm" onchange="filterStudents()"><option value="">All Years</option><?php foreach($yearsList as $y): ?><option value="<?=htmlspecialchars($y)?>"><?=htmlspecialchars($y)?></option><?php endforeach; ?></select>
    <select id="stuFilterGender" class="form-select form-select-sm" onchange="filterStudents()"><option value="">All Genders</option><option value="Male">Male</option><option value="Female">Female</option></select>
    <select id="stuFilterStatus" class="form-select form-select-sm" onchange="filterStudents()"><option value="">All Status</option><option value="Active">Active</option><option value="Inactive">Inactive</option><option value="Graduated">Graduated</option></select>
    <button class="btn btn-sm btn-outline-secondary" onclick="clearFilters()"><i class="fas fa-times"></i> Clear</button>
  </div>

  <!-- Stats row -->
  <div class="d-flex gap-3 mb-2 flex-wrap small text-muted">
    <span id="stuResultCount">0 results</span>
    <span><i class="fas fa-file-excel text-success"></i> <span id="stuExcelCount"><?=$excelRowCount?></span> Excel records loaded</span>
  </div>

  <div class="table-responsive" style="max-height:600px;overflow-y:auto">
    <div id="stuTableWrap"><table class="table table-sm table-hover" id="stuTable"><thead><tr><th>Photo</th><th>ID</th><th>Name</th><th>Set</th><th>Program</th><th>Level</th><th>Gender</th><th>Contact</th><th>Status</th><th>Source</th><th>Actions</th></tr></thead>
    <tbody id="stuTableBody">
      <tr><td colspan="10" class="text-center text-muted py-4"><i class="fas fa-spinner fa-spin"></i> Loading...</td></tr>
    </tbody></table>
  </div>
</div>

<!-- Student Modal (Add/Edit) -->
<div class="modal fade" id="stuModal" tabindex="-1"><div class="modal-dialog modal-lg"><div class="modal-content">
  <div class="modal-header"><h5 class="modal-title" id="stuModalTitle">Add Student</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
  <form id="stuForm" enctype="multipart/form-data"><div class="modal-body row g-2">
    <input type="hidden" name="action" id="stuAction" value="stu_add">
    <input type="hidden" name="id" id="stuId" value="0">
    <div class="col-md-6"><label class="small fw-medium">First Name *</label><input type="text" name="first_name" id="stuFn" class="form-control form-control-sm" required></div>
    <div class="col-md-6"><label class="small fw-medium">Surname *</label><input type="text" name="surname" id="stuSn" class="form-control form-control-sm" required></div>
    <div class="col-md-6"><label class="small fw-medium">Email</label><input type="email" name="email" id="stuEm" class="form-control form-control-sm" placeholder="student@school.edu"></div>
    <div class="col-md-6"><label class="small fw-medium">Phone</label><input type="text" name="phone" id="stuPh" class="form-control form-control-sm" placeholder="+256 XXX XXX"></div>
    <div class="col-md-4"><label class="small fw-medium">Set Name</label><input type="text" name="set_name" id="stuSet" class="form-control form-control-sm" placeholder="e.g. Set 28"></div>
    <div class="col-md-4"><label class="small fw-medium">Program</label><input type="text" name="program" id="stuPg" class="form-control form-control-sm" placeholder="e.g. Diploma Nursing"></div>
    <div class="col-md-4"><label class="small fw-medium">Level</label><select name="level" id="stuLv" class="form-select form-select-sm"><option value="">-- Select --</option><option value="Certificate">Certificate</option><option value="Diploma">Diploma</option><option value="Degree">Degree</option></select></div>
    <div class="col-md-3"><label class="small fw-medium">Gender</label><select name="gender" id="stuGd" class="form-select form-select-sm"><option value="">-- Select --</option><option value="Male">Male</option><option value="Female">Female</option></select></div>
    <div class="col-md-3"><label class="small fw-medium">Date of Birth</label><input type="date" name="date_of_birth" id="stuDb" class="form-control form-control-sm"></div>
    <div class="col-md-3"><label class="small fw-medium">Status</label><select name="status" id="stuSt" class="form-select form-select-sm"><option value="Active">Active</option><option value="Inactive">Inactive</option><option value="Graduated">Graduated</option><option value="Suspended">Suspended</option></select></div>
    <div class="col-md-3"><label class="small fw-medium">Passport Photo</label><input type="file" name="passport_photo" id="stuPhoto" class="form-control form-control-sm" accept="image/jpeg,image/png,image/gif,image/webp"></div>
    <div class="col-md-12" id="stuPhotoPreview" style="display:none"><img id="stuPhotoImg" style="max-height:100px;border-radius:8px;border:2px solid #e2e8f0" alt="Preview"></div>
  </div>
  <div class="modal-footer">
    <div id="stuMsg" class="small me-auto"></div>
    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancel</button>
    <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-save"></i> Save Student</button>
  </div></form></div></div>
</div>

<!-- Requirements Viewer Modal -->
<div class="modal fade" id="reqViewModal" tabindex="-1"><div class="modal-dialog modal-lg"><div class="modal-content">
  <div class="modal-header"><h5 class="modal-title"><i class="fas fa-clipboard-check"></i> <span id="reqViewTitle">Requirements</span></h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
  <div class="modal-body" id="reqViewBody">
    <div class="text-center py-4"><i class="fas fa-spinner fa-spin"></i> Loading requirements...</div>
  </div>
  <div class="modal-footer">
    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Close</button>
  </div>
</div></div></div>

<script>
var _stuData=[];
var _tk=window.CSRF_TOKEN||'<?=$csrfToken?>';
function editStuFromRow(idx){
  var s=_stuData[idx];
  if(!s) return;
  // Load full data from DB for photo and all details
  fetch('',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'action=stu_get&id='+(s.id||0)+'&csrf_token='+encodeURIComponent(_tk)}).then(function(r){return r.json();}).then(function(full){
    if(full&&full.id) editStu(full); else editStu(s);
  });
}
function showStuModal(id){
  document.getElementById('stuForm').reset();
  document.getElementById('stuAction').value='stu_add';
  document.getElementById('stuModalTitle').textContent='Add New Student';
  document.getElementById('stuId').value=0;
  document.getElementById('stuSt').value='Active';
  document.getElementById('stuMsg').textContent='';
  document.getElementById('stuPhotoPreview').style.display='none';
  document.getElementById('stuPhoto').value='';
  new bootstrap.Modal(document.getElementById('stuModal')).show();
}
function editStu(s){
  document.getElementById('stuForm').reset();
  document.getElementById('stuAction').value='stu_update';
  document.getElementById('stuModalTitle').textContent='Edit: '+s.full_name;
  document.getElementById('stuId').value=s.id;
  document.getElementById('stuFn').value=s.first_name||'';
  document.getElementById('stuSn').value=s.surname||'';
  document.getElementById('stuEm').value=s.email||'';
  document.getElementById('stuPh').value=s.phone||'';
  document.getElementById('stuSet').value=s.set_name||'';
  document.getElementById('stuPg').value=s.program||'';
  document.getElementById('stuLv').value=s.level||'';
  document.getElementById('stuGd').value=s.gender||'';
  document.getElementById('stuDb').value=s.date_of_birth||'';
  document.getElementById('stuSt').value=s.status||'Active';
  document.getElementById('stuMsg').textContent='';
  // Show existing photo
  var photo=s.passport_photo||s.profile_picture||'';
  if(photo){
    document.getElementById('stuPhotoPreview').style.display='block';
    document.getElementById('stuPhotoImg').src='../'+photo;
  } else {
    document.getElementById('stuPhotoPreview').style.display='none';
  }
  new bootstrap.Modal(document.getElementById('stuModal')).show();
}
function deleteStu(id,name){
  if(!confirm('Deactivate '+name+'?'))return;
  fetch('',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'action=stu_delete&id='+id+'&csrf_token='+encodeURIComponent(_tk)}).then(r=>r.json()).then(d=>{if(d.success)filterStudents();else alert(d.message);});
}
function viewExcelStudent(name,file,id,setInfo,program,phone){
  var msg='Excel Student: '+name+'\nFile: '+file+'\nIndex: '+id+'\nSet: '+(setInfo||'-')+'\nProgram: '+(program||'-')+'\nPhone: '+(phone||'-');
  alert(msg);
}
function showRequirements(stuId,stuName,stuNumber){
  document.getElementById('reqViewTitle').textContent=stuName+' — Requirements';
  document.getElementById('reqViewBody').innerHTML='<div class="text-center py-4"><i class="fas fa-spinner fa-spin"></i> Loading requirements...</div>';
  new bootstrap.Modal(document.getElementById('reqViewModal')).show();
  var body='action=stu_requirements&id='+stuId+'&csrf_token='+encodeURIComponent(_tk);
  fetch('',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:body}).then(function(r){return r.json();}).then(function(data){
    if(!data||data.length===0){
      document.getElementById('reqViewBody').innerHTML='<div class="text-center py-4 text-muted"><i class="fas fa-clipboard-list fa-2x mb-2"></i><br>No requirements found. This student may not have an applicant record linked.</div>';
      return;
    }
    var h='<div class="table-responsive"><table class="table table-sm table-bordered"><thead><tr><th>Requirement</th><th>Status</th><th>Notes</th><th style="width:140px">Set Status</th></tr></thead><tbody>';
    data.forEach(function(r){
      var statusClass=r.status==='Verified'||r.status==='Received'?'success':r.status==='Submitted'?'info':r.status==='Missing'||r.status==='Rejected'?'danger':'secondary';
      var reqId=r.requirement_id||0;
      h+='<tr><td class="small">'+r.requirement_name+(r.is_mandatory==1?' <span class="text-danger">*</span>':'')+'</td>'
        +'<td><span class="badge bg-'+statusClass+'" id="srs_'+reqId+'">'+r.status+'</span></td>'
        +'<td class="small text-muted">'+((r.director_notes||'').substring(0,50)||'-')+'</td>'
        +'<td><select class="form-select form-select-sm req-status-select" data-stuid="'+stuId+'" data-reqid="'+reqId+'" onchange="setStudentReqDirect(this)">'
        +'<option value="">—</option>'
        +'<option value="Received" '+(r.status==='Received'?'selected':'')+'>Received</option>'
        +'<option value="Submitted" '+(r.status==='Submitted'?'selected':'')+'>Submitted</option>'
        +'<option value="Verified" '+(r.status==='Verified'?'selected':'')+'>Verified</option>'
        +'<option value="Missing" '+(r.status==='Missing'?'selected':'')+'>Missing</option>'
        +'<option value="Not Yet Given" '+(r.status==='Not Yet Given'?'selected':'')+'>Not Yet Given</option>'
        +'<option value="Rejected" '+(r.status==='Rejected'?'selected':'')+'>Rejected</option>'
        +'</select></td></tr>';
    });
    h+='</tbody></table></div>'
      +'<div class="d-flex justify-content-between align-items-center mt-2">'
      +'<small class="text-muted"><i class="fas fa-info-circle"></i> Changes save instantly.</small>'
      +'<button class="btn btn-sm btn-outline-success" onclick="markAllRequirements('+stuId+')"><i class="fas fa-check-double"></i> Mark All Verified</button>'
      +'</div>';
    document.getElementById('reqViewBody').innerHTML=h;
  });
}
function setStudentReqDirect(sel){
  var stuId=parseInt(sel.getAttribute('data-stuid'))||0;
  var reqId=parseInt(sel.getAttribute('data-reqid'))||0;
  var status=sel.value;
  if(!status||!stuId||!reqId){sel.value='';return;}
  var badge=document.getElementById('srs_'+reqId);
  var origStatus=badge?badge.textContent:'';
  // Optimistic UI update
  if(badge){
    var sc=status==='Verified'||status==='Received'?'success':status==='Submitted'?'info':status==='Missing'||status==='Rejected'?'danger':'secondary';
    badge.textContent=status;
    badge.className='badge bg-'+sc;
  }
  fetch('',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'action=stu_set_req_by_student&student_id='+stuId+'&requirement_id='+reqId+'&status='+encodeURIComponent(status)+'&csrf_token='+encodeURIComponent(_tk)}).then(function(r){return r.json();}).then(function(d){
    if(!d.success && badge){
      badge.textContent=origStatus;
      badge.className='badge bg-secondary';
    }
  });
}
function markAllRequirements(stuId){
  if(!confirm('Set ALL requirements to Verified for this student?'))return;
  var selects=document.querySelectorAll('.req-status-select[data-stuid="'+stuId+'"]');
  var count=0;
  selects.forEach(function(sel){
    sel.value='Verified';
    setStudentReqDirect(sel);
    count++;
  });
  if(typeof showToast==='function')showToast('Marked '+count+' requirements as Verified.','success');
}
function filterStudents(){
  var q=document.getElementById('stuKeyword').value;
  var set=document.getElementById('stuFilterSet').value;
  var pg=document.getElementById('stuFilterProgram').value;
  var lv=document.getElementById('stuFilterLevel').value;
  var yr=document.getElementById('stuFilterYear').value;
  var gd=document.getElementById('stuFilterGender').value;
  var st=document.getElementById('stuFilterStatus').value;
  document.getElementById('stuTableBody').innerHTML='<tr><td colspan="11" class="text-center text-muted py-4"><i class="fas fa-spinner fa-spin"></i> Searching...</td></tr>';
  var body=new URLSearchParams();
  body.append('action','stu_search');
  body.append('csrf_token',_tk);
  body.append('q',q);
  body.append('set',set);
  body.append('program',pg);
  body.append('level',lv);
  body.append('year',yr);
  body.append('gender',gd);
  body.append('status',st);
  fetch('',{method:'POST',body:body}).then(r=>r.json()).then(function(data){
    _stuData=data;
    var tbody=document.getElementById('stuTableBody');
    if(!data||data.length===0){
      tbody.innerHTML='<tr><td colspan="11" class="text-center text-muted py-4"><i class="fas fa-search"></i> No students found matching your criteria.</td></tr>';
      document.getElementById('stuResultCount').textContent='0 results';
      return;
    }
    var h='';
    for(var i=0;i<data.length;i++){
      var s=data[i];
      var isExcel=s._source==='Excel';
      var statusClass=(s.status||'Active').toLowerCase()==='active'?'bg-success':'bg-secondary';
      var sourceBadge=isExcel?'<span class="badge bg-info text-white" title="'+(s._file||'')+'">Excel</span>':'<span class="badge bg-dark">DB</span>';
      var nameAttr=s.full_name.replace(/['"\\]/g,'');
      var hasPhoto=s.passport_photo||s.profile_picture||'';
      var photoHtml=hasPhoto?'<img src="../'+hasPhoto+'" style="width:32px;height:32px;border-radius:50%;object-fit:cover;border:2px solid #e2e8f0">':'<div style="width:32px;height:32px;border-radius:50%;background:linear-gradient(135deg,#7C3AED,#6D28D9);color:#fff;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:600">'+(s.full_name?s.full_name.charAt(0).toUpperCase():'?')+'</div>';
      var actions='';
      if(isExcel){
        var fileAttr=(s._file||'').replace(/['"\\]/g,'');
        var idAttr=(s.student_id||'').replace(/['"\\]/g,'');
        var setAttr=(s.set_name||s.set||'').replace(/['"\\]/g,'');
        var progAttr=(s.program||'').replace(/['"\\]/g,'');
        var phoneAttr=(s.phone||'').replace(/['"\\]/g,'');
        actions='<div class="d-flex gap-1">'
          +'<button class="btn btn-sm btn-outline-info py-0 px-1" onclick="viewExcelStudent(\''+nameAttr+'\',\''+fileAttr+'\',\''+idAttr+'\',\''+setAttr+'\',\''+progAttr+'\',\''+phoneAttr+'\')" title="View"><i class="fas fa-eye"></i></button>'
          +'</div>';
      } else {
        actions='<div class="d-flex gap-1">'
          +'<button class="btn btn-sm btn-outline-primary py-0 px-1" onclick="editStuFromRow('+i+')" title="Edit"><i class="fas fa-edit"></i></button>'
          +'<button class="btn btn-sm btn-outline-info py-0 px-1" onclick="showRequirements('+(s.id||0)+',\''+nameAttr+'\',\''+((s.student_number||s.student_id||'').replace(/[\'\\]/g,''))+'\')" title="Requirements"><i class="fas fa-clipboard-check"></i></button>'
          +'<button class="btn btn-sm btn-outline-danger py-0 px-1" onclick="deleteStu('+(s.id||0)+',\''+nameAttr+'\')" title="Deactivate"><i class="fas fa-user-slash"></i></button>'
          +'</div>';
      }
      h+='<tr>'
        +'<td class="text-center">'+photoHtml+'</td>'
        +'<td class="small">'+(s.student_id||s.index_number||s.student_number||'-')+'</td>'
        +'<td><strong>'+s.full_name+'</strong></td>'
        +'<td class="small">'+(s.set_name||s.set||'-')+'</td>'
        +'<td class="small">'+(s.program||'-')+'</td>'
        +'<td>'+(s.level||'-')+'</td>'
        +'<td>'+(s.gender||'-')+'</td>'
        +'<td class="small">'+(s.phone||s.email||'-')+'</td>'
        +'<td><span class="badge '+statusClass+'">'+(s.status||'Active')+'</span></td>'
        +'<td>'+sourceBadge+'</td>'
        +'<td>'+actions+'</td>'
        +'</tr>';
    }
    tbody.innerHTML=h;
    document.getElementById('stuResultCount').textContent=data.length+' results'+(isExcel?' (includes Excel data)':'');
  });
}
function clearFilters(){
  document.getElementById('stuKeyword').value='';
  document.getElementById('stuFilterSet').value='';
  document.getElementById('stuFilterProgram').value='';
  document.getElementById('stuFilterLevel').value='';
  document.getElementById('stuFilterYear').value='';
  document.getElementById('stuFilterGender').value='';
  document.getElementById('stuFilterStatus').value='';
  filterStudents();
}
// Photo preview
document.addEventListener('change',function(e){
  if(e.target&&e.target.id==='stuPhoto'){
    var file=e.target.files[0];
    if(file){
      var reader=new FileReader();
      reader.onload=function(ev){
        document.getElementById('stuPhotoPreview').style.display='block';
        document.getElementById('stuPhotoImg').src=ev.target.result;
      };
      reader.readAsDataURL(file);
    }
  }
});
// Initial load
filterStudents();

document.getElementById('stuForm').addEventListener('submit',function(e){
  e.preventDefault();
  var fd=new FormData(this);
  fd.append('csrf_token',_tk);
  fetch('',{method:'POST',body:fd}).then(function(r){return r.json();}).then(function(d){
    var msg=document.getElementById('stuMsg');
    if(d.success && d.student_number){
      msg.innerHTML='<i class="fas fa-check-circle text-success"></i> <strong>Student added!</strong><br><small>Login: <code>'+d.student_number+'</code> / Password: <code>'+(d.password||'set during first login')+'</code><br>Share these credentials with the student.</small>';
      msg.style.color='#059669';
    } else if(d.success){
      msg.innerHTML='<i class="fas fa-check-circle text-success"></i> '+d.message;
      msg.style.color='#059669';
    } else {
      msg.textContent=d.message;
      msg.style.color=d.success?'#059669':'#dc2626';
    }
    if(d.success){setTimeout(function(){bootstrap.Modal.getInstance(document.getElementById('stuModal')).hide();filterStudents();},3000);}
  });
});
</script>
<?php elseif ($page === 'activity'): ?>
<div class="card"><h3><i class="fas fa-history"></i> Audit Log</h3>
  <div class="table-responsive" style="max-height:600px;overflow-y:auto"><table class="table table-sm"><thead><tr><th>Date/Time</th><th>Action</th><th>Description</th><th>User</th><th>IP</th></tr></thead><tbody>
    <?php $logs=$conn->query("SELECT al.*,a.full_name as app_name FROM admission_activity_logs al LEFT JOIN applicants a ON al.applicant_id=a.id ORDER BY al.created_at DESC LIMIT 200"); if($logs)while($l=$logs->fetch_assoc()): ?>
    <tr><td class="small text-muted"><?=$l['created_at']?></td><td><span class="badge bg-secondary"><?=htmlspecialchars($l['action'])?></span></td><td class="small"><?=htmlspecialchars($l['description']??'')?> <?=$l['app_name']?'<br><span class="text-muted">Applicant: '.htmlspecialchars($l['app_name']).'</span>':''?></td><td class="small"><?=$l['user_id']?></td><td class="small text-muted"><?=htmlspecialchars($l['ip_address']??'')?></td></tr>
    <?php endwhile; ?>
  </tbody></table></div>
</div>
<?php endif; ?>
</div>

<a href="https://wa.me/256700451998?text=Hello%20Admissions%20Office%2C%20I%20need%20help%20with%20my%20application." target="_blank" class="whatsapp-float" title="Chat with Admissions on WhatsApp"><i class="fab fa-whatsapp"></i></a>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
const CSRF='<?=$csrfToken?>';
function postData(data){data.csrf_token=CSRF;return fetch('director-admissions.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:new URLSearchParams(data)}).then(r=>r.json());}

// Toast notification function
function showToast(message, type='info') {
    const toastId = 'toast-' + Date.now();
    const toastHtml = `
        <div id="${toastId}" class="toast align-items-center text-bg-${type} border-0" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body">
                    ${message.replace(/\n/g, '<br>')}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
    `;
    
    // Create toast container if it doesn't exist
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
    const toast = new bootstrap.Toast(toastEl, {delay: 5000});
    toast.show();
    
    // Remove toast after it's hidden
    toastEl.addEventListener('hidden.bs.toast', function() {
        this.remove();
    });
}

// ── Overview Charts ──
<?php if($page==='overview'): ?>
postData({action:'dashboard_stats'}).then(d=>{
  if(!d)return;
  new Chart(document.getElementById('statusChart'),{type:'doughnut',data:{labels:['New','Review','Waiting','Verified','Approved','Rejected','Registered'],datasets:[{data:[d.new,d.review,d.waiting,d.verified,d.approved,d.rejected,d.registered],backgroundColor:['#7C3AED','#0284c7','#d97706','#8b5cf6','#059669','#dc2626','#1e293b']}]},options:{plugins:{legend:{position:'bottom',labels:{boxWidth:10,font:{size:10}}}}}});
  new Chart(document.getElementById('programChart'),{type:'bar',data:{labels:Object.keys(d),datasets:[{label:'Applicants',data:Object.values(d),backgroundColor:'#7C3AED'}]},options:{plugins:{legend:{display:false}}}});
});
postData({action:'reports_data',from:'<?=date('Y-m-d',strtotime('-14 days'))?>',to:'<?=date('Y-m-d')?>'}).then(d=>{
  if(!d||!d.trend)return;
  const labels=d.trend.map(t=>t.dt.substring(5));const vals=d.trend.map(t=>parseInt(t.c));
  new Chart(document.getElementById('trendChart'),{type:'line',data:{labels,datasets:[{label:'Applications',data:vals,borderColor:'#7C3AED',backgroundColor:'rgba(124,58,237,.1)',fill:true,tension:.3}]},options:{plugins:{legend:{display:false}},scales:{y:{beginAtZero:true,grid:{color:'#f1f5f9'}},x:{grid:{display:false}}}}});
});
<?php endif; ?>

// ── Applicant filter ──
<?php if($page==='applicants'): ?>
['appSearch','filterStatus','filterProgram','filterIntake','filterGender'].forEach(id=>document.getElementById(id)?.addEventListener('change',filterApps));
document.getElementById('appSearch')?.addEventListener('input',filterApps);
function admStatusBadge(status){
  const m={'New':'bg-primary','Under Review':'bg-info','Waiting for Documents':'bg-warning text-dark','Requirements Verified':'bg-success','Interview Scheduled':'bg-purple','Approved':'bg-success','Rejected':'bg-danger','Registered':'bg-dark','Withdrawn':'bg-secondary'};
  return '<span class="badge '+(m[status]||'bg-secondary')+'">'+status+'</span>';
}
function filterApps(){
  const q=document.getElementById('appSearch').value;
  const st=document.getElementById('filterStatus').value;
  const pg=document.getElementById('filterProgram').value;
  const in_=document.getElementById('filterIntake').value;
  const gd=document.getElementById('filterGender').value;
  postData({action:'filter_applicants',search:q,status:st,program_id:pg,intake:in_,gender:gd,limit:200}).then(d=>{
    const tbody=document.getElementById('applicantTableBody');
    if(!d||d.length===0){tbody.innerHTML='<tr><td colspan="8" class="text-muted text-center py-4">No matching applicants.</td></tr>';return;}
    tbody.innerHTML=d.map(a=>'<tr><td><span class="text-muted small">'+a.application_number+'</span></td><td><strong>'+a.full_name+'</strong></td><td><small>'+a.email+'<br>'+a.phone+'</small></td><td>'+(a.program_name||'-')+'</td><td>'+(a.intake||'-')+'</td><td>'+admStatusBadge(a.status)+'</td><td class="text-muted small">'+new Date(a.created_at).toLocaleDateString()+'</td><td><a href="director-admissions.php?page=review&aid='+a.id+'" class="btn btn-sm btn-outline-primary"><i class="fas fa-eye"></i></a></td></tr>').join('');
  });
}
<?php endif; ?>

// ── Requirements portal toggle ──
function toggleReq(aid,rid,st){postData({action:'set_requirement',applicant_id:aid,requirement_id:rid,status:st}).then(d=>{if(d.success){showToast('Requirement updated','success');loadRequirementsPortal();}else{showToast('Update failed','danger');}});}

// ── Website submissions ──
<?php if($page==='submissions'): ?>
postData({action:'website_submissions'}).then(d=>{
  const tb=document.getElementById('webSubTable');
  if(!d||d.length===0){tb.innerHTML='<tr><td colspan="6" class="text-muted text-center py-4">No website submissions yet.</td></tr>';return;}
  tb.innerHTML=d.map(s=>'<tr><td>'+s.name+'</td><td>'+s.email+'</td><td>'+(s.phone||'-')+'</td><td>'+(s.program_applied||'-')+'</td><td class="small">'+new Date(s.created_at).toLocaleDateString()+'</td><td><button class="btn btn-sm btn-outline-primary" onclick="postData({action:\'import_online\',application_id:'+s.id+'}).then(d=>{if(d.success)alert(\'Imported!\');location.reload();})"><i class="fas fa-import"></i> Import</button></td></tr>');
});
<?php endif; ?>

// ── Reports ──
<?php if($page==='reports'||$page==='analytics'): ?>
function loadReport(){
  const from=document.getElementById('rptFrom')?.value||'<?=date('Y-m-d',strtotime('-30 days'))?>';
  const to=document.getElementById('rptTo')?.value||'<?=date('Y-m-d')?>';
  postData({action:'reports_data',from,to}).then(d=>{
    if(!d)return;
    // Status chart
    const stLabels=Object.keys(d.byStatus);const stVals=Object.values(d.byStatus);
    const stCanvas=document.getElementById('rptStatusChart')||document.getElementById('analyticsStatusChart');
    if(stCanvas)new Chart(stCanvas,{type:'doughnut',data:{labels:stLabels,datasets:[{data:stVals,backgroundColor:['#7C3AED','#0284c7','#d97706','#8b5cf6','#059669','#dc2626','#1e293b']}]},options:{plugins:{legend:{position:'bottom',labels:{boxWidth:10,font:{size:10}}}}}});
    // Program chart
    const pgCanvas=document.getElementById('rptProgramChart')||document.getElementById('analyticsProgramChart');
    if(pgCanvas&&d.byProgram)new Chart(pgCanvas,{type:'bar',data:{labels:d.byProgram.map(p=>p.program_name),datasets:[{label:'Applicants',data:d.byProgram.map(p=>parseInt(p.c)),backgroundColor:'#7C3AED'}]},options:{plugins:{legend:{display:false}},scales:{y:{beginAtZero:true}}}});
    // Gender chart
    const gdCanvas=document.getElementById('rptGenderChart')||document.getElementById('analyticsGenderChart');
    if(gdCanvas&&d.byGender)new Chart(gdCanvas,{type:'pie',data:{labels:d.byGender.map(g=>g.gender),datasets:[{data:d.byGender.map(g=>parseInt(g.c)),backgroundColor:['#7C3AED','#F59E0B','#10B981']}]}});
    // Intake chart
    const inCanvas=document.getElementById('rptIntakeChart');
    if(inCanvas&&d.byIntake)new Chart(inCanvas,{type:'bar',data:{labels:d.byIntake.map(i=>i.intake),datasets:[{label:'Applicants',data:d.byIntake.map(i=>parseInt(i.c)),backgroundColor:'#8b5cf6'}]}});
    // Nationality chart
    const natCanvas=document.getElementById('analyticsNationalityChart');
    if(natCanvas&&d.byNationality)new Chart(natCanvas,{type:'bar',data:{labels:d.byNationality.map(n=>n.nationality),datasets:[{label:'Applicants',data:d.byNationality.map(n=>parseInt(n.c)),backgroundColor:'#059669'}]}});
  });
}
loadReport();
<?php endif; ?>

function exportCSV(type){postData({action:'export_csv',export_type:type});showToast('Exporting CSV...','info');}
function doRegister(){const sel=document.getElementById('regSelect');if(!sel||!sel.value)return alert('Select an approved applicant.');if(!confirm('Register this applicant?'))return;postData({action:'register_student',id:sel.value}).then(d=>{if(d.success){showToast('Student registered!\nStudent #: '+d.student_number+'\nUsername: '+d.username+'\nPassword: '+d.password,'success');location.reload();}else{showToast('Registration failed','danger');}});}
</script>
</div><!-- /.adm-content -->
<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
</body></html>
