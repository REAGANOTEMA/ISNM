<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
require_once __DIR__ . '/../includes/institutional_framework.php';
require_once __DIR__ . '/../includes/approval_workflow.php';
$ctx = bootstrapStaffDashboard(['admissions', 'director']);
$conn = $ctx['staff'];
$students_conn = $ctx['students'] ?? null;
$website_conn = $ctx['website'];
$user = $ctx['user'];
$user_id = (int)($user['id'] ?? 0);
$user_role = $_SESSION['role'] ?? '';
$user_name = $user['full_name'] ?? 'Director Admissions';

$students_db = defined('STUDENTS_DB') ? STUDENTS_DB : 'igangaschoolofl_students_db';
$staff_db = defined('STAFF_DB') ? STAFF_DB : 'igangaschoolofl_staffs_db';

// ── Auto-migration ──
$migrate_tables = [
    "CREATE TABLE IF NOT EXISTS `{$staff_db}`.`admission_requirements` (
      id INT AUTO_INCREMENT PRIMARY KEY,
      requirement_name VARCHAR(300) NOT NULL,
      is_active TINYINT(1) DEFAULT 1,
      display_order INT DEFAULT 0,
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    "CREATE TABLE IF NOT EXISTS `{$staff_db}`.`applicants` (
      id INT AUTO_INCREMENT PRIMARY KEY,
      full_name VARCHAR(300) NOT NULL,
      date_of_birth DATE NULL,
      gender VARCHAR(20) DEFAULT 'Other',
      phone VARCHAR(50) DEFAULT '',
      email VARCHAR(200) DEFAULT '',
      address TEXT,
      guardian_name VARCHAR(300) DEFAULT '',
      guardian_phone VARCHAR(50) DEFAULT '',
      guardian_relationship VARCHAR(100) DEFAULT '',
      application_number VARCHAR(50) UNIQUE,
      program_id INT DEFAULT 0,
      intake VARCHAR(100) DEFAULT '',
      admission_date DATE NULL,
      status VARCHAR(50) DEFAULT 'New Applicant',
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    "CREATE TABLE IF NOT EXISTS `{$staff_db}`.`applicant_requirement_status` (
      id INT AUTO_INCREMENT PRIMARY KEY,
      applicant_id INT NOT NULL,
      requirement_id INT NOT NULL,
      status ENUM('Not Submitted','Submitted','Verified','Rejected','Missing') DEFAULT 'Not Submitted',
      submitted_by INT NULL, verified_by INT NULL, rejected_by INT NULL,
      remarks TEXT,
      submitted_at DATETIME NULL, verified_at DATETIME NULL,
      received_by INT NULL, received_at DATETIME NULL,
      UNIQUE KEY uq_app_req (applicant_id,requirement_id),
      KEY idx_applicant_id (applicant_id),
      KEY idx_requirement_id (requirement_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    "CREATE TABLE IF NOT EXISTS `{$staff_db}`.`requirement_history` (
      id INT AUTO_INCREMENT PRIMARY KEY,
      applicant_id INT NOT NULL, requirement_id INT NULL,
      action VARCHAR(100) NOT NULL, performed_by INT NULL,
      remarks TEXT, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      KEY idx_hist_applicant (applicant_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    "CREATE TABLE IF NOT EXISTS `{$staff_db}`.`student_documents` (
      id INT AUTO_INCREMENT PRIMARY KEY,
      applicant_id INT NOT NULL,
      document_type VARCHAR(100) DEFAULT 'Other',
      document_title VARCHAR(300) DEFAULT '',
      file_name VARCHAR(300) DEFAULT '', file_path VARCHAR(500) DEFAULT '',
      file_size INT DEFAULT 0, mime_type VARCHAR(100) DEFAULT '',
      verification_status ENUM('Pending','Verified','Rejected') DEFAULT 'Pending',
      verified_by INT NULL, verified_at DATETIME NULL, remarks TEXT,
      uploaded_by INT NOT NULL, uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      KEY idx_doc_applicant (applicant_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    "CREATE TABLE IF NOT EXISTS `{$staff_db}`.`admission_activity_logs` (
      id INT AUTO_INCREMENT PRIMARY KEY,
      user_id INT NOT NULL, action VARCHAR(200) NOT NULL,
      module VARCHAR(100) NOT NULL, record_id INT DEFAULT 0,
      description TEXT, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      KEY idx_log_module (module),
      KEY idx_log_record (record_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    "CREATE TABLE IF NOT EXISTS `{$staff_db}`.`admission_notifications` (
      id INT AUTO_INCREMENT PRIMARY KEY,
      applicant_id INT NULL, recipient_type VARCHAR(50) DEFAULT 'applicant',
      recipient_id INT NULL, title VARCHAR(300) NOT NULL,
      message TEXT NOT NULL, channel VARCHAR(50) DEFAULT 'portal',
      sent_by INT NOT NULL, is_read TINYINT(1) DEFAULT 0,
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      KEY idx_notif_applicant (applicant_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    "CREATE TABLE IF NOT EXISTS `{$staff_db}`.`applicant_messages` (
      id INT AUTO_INCREMENT PRIMARY KEY,
      applicant_id INT NOT NULL, sender_id INT NOT NULL,
      recipient_type VARCHAR(50) DEFAULT 'applicant',
      subject VARCHAR(300) NOT NULL, message TEXT NOT NULL,
      is_read TINYINT(1) DEFAULT 0,
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      KEY idx_msg_applicant (applicant_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
];
foreach ($migrate_tables as $sql) { $conn->query($sql); }

// Add missing columns if they don't exist
$colCheck=$conn->query("SHOW COLUMNS FROM `{$staff_db}`.`student_documents` LIKE 'requirement_id'");
if(!$colCheck||!$colCheck->num_rows){
    $conn->query("ALTER TABLE `{$staff_db}`.`student_documents` ADD COLUMN requirement_id INT NULL AFTER applicant_id, ADD KEY idx_doc_requirement (requirement_id)");
}
$colCheck=$conn->query("SHOW COLUMNS FROM `{$staff_db}`.`student_documents` LIKE 'document_status'");
if(!$colCheck||!$colCheck->num_rows){
    $conn->query("ALTER TABLE `{$staff_db}`.`student_documents` ADD COLUMN document_status VARCHAR(50) DEFAULT 'Pending' AFTER remarks");
}
$colCheck=$conn->query("SHOW COLUMNS FROM `{$staff_db}`.`applicant_requirement_status` LIKE 'updated_at'");
if(!$colCheck||!$colCheck->num_rows){
    $conn->query("ALTER TABLE `{$staff_db}`.`applicant_requirement_status` ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER received_at");
}
$colCheck=$conn->query("SHOW COLUMNS FROM `{$staff_db}`.`admission_requirements` LIKE 'type'");
if(!$colCheck||!$colCheck->num_rows){
    $conn->query("ALTER TABLE `{$staff_db}`.`admission_requirements` ADD COLUMN type VARCHAR(50) DEFAULT 'Document' AFTER requirement_name");
}
$colCheck=$conn->query("SHOW COLUMNS FROM `{$staff_db}`.`admission_requirements` LIKE 'is_mandatory'");
if(!$colCheck||!$colCheck->num_rows){
    $conn->query("ALTER TABLE `{$staff_db}`.`admission_requirements` ADD COLUMN is_mandatory TINYINT(1) DEFAULT 1 AFTER is_active");
}
$colCheck=$conn->query("SHOW COLUMNS FROM `{$staff_db}`.`applicants` LIKE 'rejection_reason'");
if(!$colCheck||!$colCheck->num_rows){
    $conn->query("ALTER TABLE `{$staff_db}`.`applicants` ADD COLUMN rejection_reason TEXT NULL AFTER status");
}
$colCheck=$conn->query("SHOW COLUMNS FROM `{$staff_db}`.`applicants` LIKE 'other_names'");
if(!$colCheck||!$colCheck->num_rows){
    $conn->query("ALTER TABLE `{$staff_db}`.`applicants` ADD COLUMN other_names VARCHAR(200) DEFAULT '' AFTER full_name");
}

// Seed default admission requirements if empty
$reqCheck = $conn->query("SELECT COUNT(*)c FROM `{$staff_db}`.`admission_requirements`");
if ($reqCheck) {
    $rc = (int)$reqCheck->fetch_assoc()['c'];
    if ($rc === 0) {
        $defaultReqs = [
            'Completed Application Form','Academic Certificates','Transcript','Birth Certificate',
            'Passport Photos','Medical Report','Recommendation Letter','National ID Copy',
            'Proof of Payment','Interview Letter','Entry Qualification','English Proficiency',
            'Character Reference','Guardian Consent Form','Health Declaration','Immunization Record',
            'Previous School Report','Employment Letter (if applicable)','Community Service Certificate','Sports Certificate',
        ];
        $order = 1;
        foreach ($defaultReqs as $rname) {
            $stmt = $conn->prepare("INSERT INTO `{$staff_db}`.`admission_requirements` (requirement_name,is_active,display_order) VALUES (?,?,?)");
            if ($stmt) { $stmt->bind_param('sii', $rname, $one, $order); $one = 1; $stmt->execute(); $stmt->close(); }
            $order++;
        }
    }
}

function safeCount($c, $q) { $r=$c->query($q); if(!$r)return 0; $w=$r->fetch_assoc(); return intval($w['c']??0); }
function logAdmission($conn, $uid, $action, $module, $rid, $desc) {
    global $staff_db;
    $stmt = $conn->prepare("INSERT INTO `{$staff_db}`.`admission_activity_logs` (user_id,action,module,record_id,description) VALUES (?,?,?,?,?)");
    if ($stmt) { $stmt->bind_param('issis', $uid, $action, $module, $rid, $desc); $stmt->execute(); $stmt->close(); }
}
function _r($s){return $s;}

// ── Stats ──
$total_applicants    = safeCount($conn, "SELECT COUNT(*)c FROM `{$staff_db}`.`applicants`");
$new_applicants      = safeCount($conn, "SELECT COUNT(*)c FROM `{$staff_db}`.`applicants` WHERE status='New Applicant'");
$approved_applicants = safeCount($conn, "SELECT COUNT(*)c FROM `{$staff_db}`.`applicants` WHERE status='Approved'");
$pending_verify      = safeCount($conn, "SELECT COUNT(*)c FROM `{$staff_db}`.`applicants` WHERE status IN('Under Review','Pending')");
$cleared_count       = safeCount($conn, "SELECT COUNT(*)c FROM `{$staff_db}`.`applicants` WHERE status='Registered'");
$rejected_count      = safeCount($conn, "SELECT COUNT(*)c FROM `{$staff_db}`.`applicants` WHERE status='Rejected'");
$pending_count       = $total_applicants - $approved_applicants - $rejected_count - $cleared_count;
if ($pending_count < 0) $pending_count = 0;
$total_students      = $students_conn ? safeCount($students_conn, "SELECT COUNT(*)c FROM `{$students_db}`.`students` WHERE status!='deleted'") : 0;
$total_reqs          = safeCount($conn, "SELECT COUNT(*)c FROM `{$staff_db}`.`admission_requirements` WHERE is_active=1");
$total_req_items     = max($total_reqs, 1);

// ── Intake counts for charts ──
$jan_count = safeCount($conn, "SELECT COUNT(*)c FROM `{$staff_db}`.`applicants` WHERE intake='January'");
$may_count = safeCount($conn, "SELECT COUNT(*)c FROM `{$staff_db}`.`applicants` WHERE intake='May'");
$aug_count = safeCount($conn, "SELECT COUNT(*)c FROM `{$staff_db}`.`applicants` WHERE intake='August'");

// ── Program counts for program pie chart ──
$program_counts = [];
$r=$conn->query("SELECT program_id,COUNT(*)c FROM `{$staff_db}`.`applicants` GROUP BY program_id");
if($r) while($row=$r->fetch_assoc()) $program_counts[$row['program_id']] = (int)$row['c'];

// ── Students awaiting / fully cleared / missing requirements ──
$students_awaiting_reqs = safeCount($conn, "SELECT COUNT(DISTINCT applicant_id)c FROM `{$staff_db}`.`applicant_requirement_status` WHERE status='Submitted'");
$students_fully_cleared  = safeCount($conn, "SELECT COUNT(DISTINCT applicant_id)c FROM `{$staff_db}`.`applicant_requirement_status` WHERE status='Verified'");
$students_missing_reqs   = safeCount($conn, "SELECT COUNT(DISTINCT applicant_id)c FROM `{$staff_db}`.`applicant_requirement_status` WHERE status IN('Missing','Rejected')");

$clearance_levels = ['Not Submitted'=>0,'Submitted'=>0,'Verified'=>0,'Rejected'=>0,'Missing'=>0];
$r=$conn->query("SELECT status,COUNT(*)c FROM `{$staff_db}`.`applicant_requirement_status` GROUP BY status");
if($r) while($row=$r->fetch_assoc()) $clearance_levels[$row['status']] = intval($row['c']);

$program_clearance = [];
$r=$conn->query("SELECT COALESCE(p.program_name,'Unassigned') program,
    COUNT(DISTINCT a.id) total,
    SUM(CASE WHEN a.status='Registered' THEN 1 ELSE 0 END) cleared,
    SUM(CASE WHEN a.status='Approved' THEN 1 ELSE 0 END) approved,
    SUM(CASE WHEN a.status='Rejected' THEN 1 ELSE 0 END) rejected
    FROM `{$staff_db}`.`applicants` a LEFT JOIN `{$staff_db}`.`academic_programs` p ON a.program_id=p.id
    GROUP BY p.program_name ORDER BY total DESC");
if($r) while($row=$r->fetch_assoc()){
    $row['pct'] = $row['total'] > 0 ? round(($row['cleared']/$row['total'])*100, 1) : 0;
    $program_clearance[]=$row;
}

$intake_stats = []; $r=$conn->query("SELECT intake,COUNT(*)c FROM `{$staff_db}`.`applicants` WHERE intake IS NOT NULL AND intake!='' GROUP BY intake ORDER BY intake");
if($r) while($row=$r->fetch_assoc()) $intake_stats[]=$row;

$req_items=[]; $r=$conn->query("SELECT * FROM `{$staff_db}`.`admission_requirements` WHERE is_active=1 ORDER BY display_order");
if($r) while($row=$r->fetch_assoc()) $req_items[]=$row;
$requirements = $req_items;
$active_reqs = count($req_items);

$recent_activity=[]; $r=$conn->query("SELECT al.*,s.full_name performer_name FROM `{$staff_db}`.`admission_activity_logs` al LEFT JOIN `{$staff_db}`.`staff` s ON al.user_id=s.id ORDER BY al.created_at DESC LIMIT 20");
if($r) while($row=$r->fetch_assoc()) $recent_activity[]=$row;

$programs_list=[]; $r=$conn->query("SELECT id,program_code,program_name,program_type,department,duration_years,status FROM `{$staff_db}`.`academic_programs` WHERE status='Active' ORDER BY program_name");
if($r) while($row=$r->fetch_assoc()) $programs_list[]=$row;

// ── Get all applicants for listing ──
$applicants = [];
$r=$conn->query("SELECT a.*,COALESCE((SELECT program_name FROM `{$staff_db}`.`academic_programs` WHERE id=a.program_id),'N/A') program_name FROM `{$staff_db}`.`applicants` a ORDER BY a.created_at DESC LIMIT 100");
if($r) while($row=$r->fetch_assoc()) $applicants[]=$row;

// ── Get pending applicants for approvals ──
$pending_applicants = [];
$r=$conn->query("SELECT a.*,COALESCE((SELECT program_name FROM `{$staff_db}`.`academic_programs` WHERE id=a.program_id),'N/A') program_name FROM `{$staff_db}`.`applicants` a WHERE a.status IN('New Applicant','Under Review','Approved') ORDER BY a.created_at DESC LIMIT 50");
if($r) while($row=$r->fetch_assoc()) $pending_applicants[]=$row;

$view = $_GET['section'] ?? 'overview';
$ajax = $_REQUEST['ajax'] ?? $_REQUEST['action'] ?? '';

// ══════════════════════════════════════════════════════════════════════
// AJAX ENDPOINTS
// ══════════════════════════════════════════════════════════════════════

if ($ajax === 'search_applicants') {
    header('Content-Type: application/json');
    $q = trim($_POST['q'] ?? ($_GET['q'] ?? ''));
    $statusFilter = trim($_POST['status'] ?? ($_GET['status'] ?? ''));
    $intakeFilter = trim($_POST['intake'] ?? ($_GET['intake'] ?? ''));
    $where = ["1=1"];
    $params = [];
    $types = '';
    if ($q !== '') {
        $where[] = "(a.full_name LIKE ? OR a.application_number LIKE ? OR a.phone LIKE ? OR a.email LIKE ?)";
        $like = "%$q%";
        $params = array_merge($params, [$like, $like, $like, $like]);
        $types .= 'ssss';
    }
    if ($statusFilter !== '') {
        $statuses = array_map('trim', explode(',', $statusFilter));
        $placeholders = [];
        foreach ($statuses as $s) { $placeholders[] = '?'; $params[] = $s; $types .= 's'; }
        $where[] = "a.status IN (".implode(',', $placeholders).")";
    }
    if ($intakeFilter !== '') {
        $where[] = "a.intake = ?";
        $params[] = $intakeFilter;
        $types .= 's';
    }
    $whereSql = 'WHERE '.implode(' AND ', $where);
    $stmt = $conn->prepare("SELECT a.id,a.full_name,a.application_number,a.phone,COALESCE((SELECT program_name FROM `{$staff_db}`.`academic_programs` WHERE id=a.program_id),'N/A') program_name,a.intake,a.status FROM `{$staff_db}`.`applicants` a $whereSql ORDER BY a.created_at DESC LIMIT 100");
    if ($stmt) { if ($types) $stmt->bind_param($types, ...$params); $stmt->execute(); $r = $stmt->get_result(); $stmt->close(); } else $r = null;
    $out = ['data'=>[]];
    if ($r) { while($row = $r->fetch_assoc()) $out['data'][] = $row; }
    echo json_encode($out); exit;
}

if ($ajax === 'search_students') {
    header('Content-Type: application/json');
    if (!$students_conn) { echo json_encode(['data'=>[],'total'=>0]); exit; }
    $q = trim($_GET['q'] ?? ($_POST['q'] ?? ''));
    $program = trim($_GET['program'] ?? ($_POST['program'] ?? ''));
    $intake = trim($_GET['intake'] ?? ($_POST['intake'] ?? ''));
    $status = trim($_GET['status'] ?? ($_POST['status'] ?? ''));
    $year = trim($_GET['year'] ?? ($_POST['year'] ?? ''));
    $phone = trim($_GET['phone'] ?? ($_POST['phone'] ?? ''));
    $national_id = trim($_GET['national_id'] ?? ($_POST['national_id'] ?? ''));
    $admission_no = trim($_GET['admission_no'] ?? ($_POST['admission_no'] ?? ''));
    $reg_no = trim($_GET['reg_no'] ?? ($_POST['reg_no'] ?? ''));
    $page = max(1, intval($_GET['page'] ?? ($_POST['page'] ?? 1)));
    $limit = max(1, min(100, intval($_GET['limit'] ?? ($_POST['limit'] ?? 20))));
    $offset = ($page - 1) * $limit;

    $where = ["1=1"];
    $params = [];
    $types = '';
    if ($q !== '') {
        $where[] = "(CONCAT_WS(' ',first_name,other_name,surname) LIKE ? OR CONCAT_WS(' ',first_name,surname) LIKE ? OR student_number LIKE ? OR registration_number LIKE ? OR index_number LIKE ? OR full_name LIKE ? OR email LIKE ?)";
        $like = "%$q%";
        $params = array_merge($params, [$like, $like, $like, $like, $like, $like, $like]);
        $types .= 'sssssss';
    }
    if ($admission_no !== '') { $where[] = "student_number LIKE ?"; $params[] = "%$admission_no%"; $types .= 's'; }
    if ($reg_no !== '') { $where[] = "registration_number LIKE ?"; $params[] = "%$reg_no%"; $types .= 's'; }
    if ($phone !== '') { $where[] = "(phone LIKE ? OR mobile_number LIKE ?)"; $params[] = "%$phone%"; $params[] = "%$phone%"; $types .= 'ss'; }
    if ($national_id !== '') { $where[] = "national_student_id_number LIKE ?"; $params[] = "%$national_id%"; $types .= 's'; }
    if ($program !== '') { $where[] = "program LIKE ?"; $params[] = "%$program%"; $types .= 's'; }
    if ($intake !== '') { $where[] = "intake_date LIKE ?"; $params[] = "%$intake%"; $types .= 's'; }
    if ($status !== '') { $where[] = "status = ?"; $params[] = $status; $types .= 's'; }
    if ($year !== '') { $where[] = "year = ?"; $params[] = $year; $types .= 's'; }

    $whereSql = 'WHERE '.implode(' AND ', $where);
    $countResult = $students_conn->query("SELECT COUNT(*) total FROM `{$students_db}`.`students` $whereSql");
    $total = ($countResult && $countResult->num_rows) ? (int)$countResult->fetch_assoc()['total'] : 0;

    $limitParam = $limit;
    $offsetParam = $offset;
    $stmt = $students_conn->prepare("SELECT id AS student_id,student_number AS admission_no,registration_number AS reg_no,national_student_id_number,first_name,other_name,surname,full_name,phone,mobile_number,email,program,intake_date AS intake_period,year AS intake_year,status,gender,date_of_birth,address FROM `{$students_db}`.`students` $whereSql ORDER BY surname,first_name LIMIT ? OFFSET ?");
    if ($stmt) {
        $types .= 'ii';
        $params[] = $limitParam;
        $params[] = $offsetParam;
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $r = $stmt->get_result();
        $stmt->close();
    } else $r = null;
    $students = [];
    if ($r) while ($row = $r->fetch_assoc()) $students[] = $row;
    echo json_encode(['data'=>$students,'total'=>$total,'page'=>$page,'totalPages'=>max(1,ceil($total/$limit))]);
    exit;
}

if ($ajax === 'get_student_profile') {
    header('Content-Type: application/json');
    $sid = intval($_GET['student_id'] ?? ($_POST['student_id'] ?? 0));
    if (!$sid || !$students_conn) { echo json_encode(['success'=>false,'error'=>'Invalid student ID']); exit; }
    $info = []; $r = $students_conn->query("SELECT * FROM `{$students_db}`.`students` WHERE id=".intval($sid)." LIMIT 1");
    if ($r) $info = $r->fetch_assoc() ?: [];
    if (empty($info)) { echo json_encode(['success'=>false,'error'=>'Student not found']); exit; }

    $aid=0; $applicant=[];
    $studentNo = $info['student_number']??'';
    $fullName  = trim($info['full_name']??'');
    if ($studentNo) {
        $stmt = $conn->prepare("SELECT * FROM `{$staff_db}`.`applicants` WHERE application_number LIKE ? LIMIT 1");
        if ($stmt) { $like = "%$studentNo%"; $stmt->bind_param('s', $like); $stmt->execute(); $ar = $stmt->get_result(); $stmt->close(); } else $ar = null;
        if($ar&&$ar->num_rows){$applicant=$ar->fetch_assoc();$aid=(int)$applicant['id'];}
    }
    if (!$aid&&$fullName){
        $stmt = $conn->prepare("SELECT * FROM `{$staff_db}`.`applicants` WHERE full_name LIKE ? LIMIT 1");
        if ($stmt) { $like = "%$fullName%"; $stmt->bind_param('s', $like); $stmt->execute(); $ar = $stmt->get_result(); $stmt->close(); } else $ar = null;
        if($ar&&$ar->num_rows){$applicant=$ar->fetch_assoc();$aid=(int)$applicant['id'];}
    }

    $reqs=[];
    $rr=$conn->query("SELECT adr.id requirement_id,adr.requirement_name,adr.display_order,ars.status,ars.verified_by,ars.remarks,ars.submitted_at,ars.verified_at,ars.received_by,ars.received_at FROM `{$staff_db}`.`admission_requirements` adr LEFT JOIN `{$staff_db}`.`applicant_requirement_status` ars ON ars.requirement_id=adr.id AND ars.applicant_id=".intval($aid)." WHERE adr.is_active=1 ORDER BY adr.display_order");
    if($rr) while($row=$rr->fetch_assoc()){$row['status']=$row['status']??'Not Submitted';$reqs[]=$row;}

    $docs=[];
    $dr=$conn->query("SELECT sd.*,s.full_name uploader_name FROM `{$staff_db}`.`student_documents` sd LEFT JOIN `{$staff_db}`.`staff` s ON sd.uploaded_by=s.id WHERE sd.applicant_id=".intval($aid)." ORDER BY sd.uploaded_at DESC");
    if($dr) while($row=$dr->fetch_assoc())$docs[]=$row;

    $activity=[];
    $ar2=$conn->query("SELECT al.*,s.full_name performer_name FROM `{$staff_db}`.`admission_activity_logs` al LEFT JOIN `{$staff_db}`.`staff` s ON al.user_id=s.id WHERE al.record_id=".intval($aid)." OR (al.module='students' AND al.record_id=".intval($sid).") ORDER BY al.created_at DESC LIMIT 20");
    if($ar2) while($row=$ar2->fetch_assoc())$activity[]=$row;

    $completed=0;$rejected=0;
    $reqs_total = count($reqs);
    foreach($reqs as $r){if($r['status']==='Verified')$completed++;elseif($r['status']==='Rejected')$rejected++;}
    $pending=$reqs_total-$completed-$rejected;
    $clearance_pct=$total_req_items>0?round(($completed/$total_req_items)*100):0;

    echo json_encode(['success'=>true,'data'=>['info'=>$info,'applicant'=>$applicant,'applicant_id'=>$aid,'requirements'=>$reqs,'documents'=>$docs,'activity'=>$activity,'completed'=>$completed,'pending'=>$pending,'rejected'=>$rejected,'clearance_pct'=>$clearance_pct,'total_reqs'=>$total_req_items,'full_name'=>$info['full_name']??'', 'admission_no'=>$info['student_number']??'', 'phone'=>$info['phone']??'', 'email'=>$info['email']??'', 'program_name'=>$info['program']??'', 'status'=>$info['status']??'', 'national_id'=>$info['national_student_id_number']??'', 'date_of_birth'=>$info['date_of_birth']??'']]);
    exit;
}

if ($ajax === 'get_requirements') {
    header('Content-Type: application/json');
    $aid=intval($_GET['applicant_id']??0);
    if(!$aid){echo json_encode([]);exit;}
    $info=[];$rn=$conn->query("SELECT * FROM `{$staff_db}`.`applicants` WHERE id=".intval($aid));if($rn)$info=$rn->fetch_assoc()?:[];
    $req=[];$rr=$conn->query("SELECT ars.*,adr.requirement_name,adr.display_order FROM `{$staff_db}`.`applicant_requirement_status` ars RIGHT JOIN `{$staff_db}`.`admission_requirements` adr ON ars.requirement_id=adr.id AND ars.applicant_id=".intval($aid)." WHERE adr.is_active=1 ORDER BY adr.display_order");
    if($rr) while($row=$rr->fetch_assoc())$req[]=$row;
    $hist=[];$rh=$conn->query("SELECT rh.*,s.full_name performed_by_name FROM `{$staff_db}`.`requirement_history` rh LEFT JOIN `{$staff_db}`.`staff` s ON rh.performed_by=s.id WHERE rh.applicant_id=".intval($aid)." ORDER BY rh.created_at DESC LIMIT 50");
    if($rh) while($row=$rh->fetch_assoc())$hist[]=$row;
    $docs=[];$dr=$conn->query("SELECT sd.*,s.full_name uploader_name FROM `{$staff_db}`.`student_documents` sd LEFT JOIN `{$staff_db}`.`staff` s ON sd.uploaded_by=s.id WHERE sd.applicant_id=".intval($aid)." ORDER BY sd.uploaded_at DESC");
    if($dr) while($row=$dr->fetch_assoc())$docs[]=$row;
    echo json_encode(['info'=>$info,'requirements'=>$req,'history'=>$hist,'documents'=>$docs]);exit;
}

if ($ajax === 'toggle_requirement') {
    header('Content-Type: application/json');
    $rid=intval($_POST['id']??$_POST['requirement_id']??0);
    if(!$rid){echo json_encode(['success'=>false,'error'=>'ID required']);exit;}
    $cr=$conn->query("SELECT is_active FROM `{$staff_db}`.`admission_requirements` WHERE id=".intval($rid));
    if(!$cr||!$cr->num_rows){echo json_encode(['success'=>false,'error'=>'Not found']);exit;}
    $cur=(int)$cr->fetch_assoc()['is_active'];
    $new=$cur?0:1;
    $conn->query("UPDATE `{$staff_db}`.`admission_requirements` SET is_active=$new WHERE id=".intval($rid));
    logAdmission($conn,$user_id,'Toggle Requirement','requirements',$rid,"Requirement #$rid "._r($new?'activated':'deactivated'));
    echo json_encode(['success'=>true,'is_active'=>$new]);exit;
}

if ($ajax === 'set_requirement_status') {
    header('Content-Type: application/json');
    $aid=intval($_POST['applicant_id']??0);
    $rid=intval($_POST['requirement_id']??0);
    $new_status=trim($_POST['status']??'Not Submitted');
    $remarks=trim($_POST['remarks']??'');
    $valid_statuses=['Not Submitted','Submitted','Verified','Rejected','Missing'];
    if(!in_array($new_status,$valid_statuses)){echo json_encode(['success'=>false,'error'=>'Invalid status']);exit;}
    if(!$aid||!$rid){echo json_encode(['success'=>false,'error'=>'Invalid IDs']);exit;}
    $uid=intval($user_id);
    $stmt = $conn->prepare("INSERT INTO `{$staff_db}`.`applicant_requirement_status` (applicant_id,requirement_id,status,submitted_by,submitted_at,verified_by,verified_at,rejected_by,remarks) VALUES (?,?,?,?,NOW(),NULL,NULL,NULL,?) ON DUPLICATE KEY UPDATE status=?, submitted_by=IF(?='Submitted',?,submitted_by), submitted_at=IF(?='Submitted',NOW(),submitted_at), verified_by=IF(?='Verified',?,verified_by), verified_at=IF(?='Verified',NOW(),verified_at), rejected_by=IF(?='Rejected',?,rejected_by), remarks=?");
    if ($stmt) {
        $subBy = ($new_status==='Submitted') ? $uid : null;
        $verBy = ($new_status==='Verified') ? $uid : null;
        $rejBy = ($new_status==='Rejected') ? $uid : null;
        $stmt->bind_param('iisssssisiiiiiiis', $aid, $rid, $new_status, $subBy, $remarks, $new_status, $new_status, $uid, $new_status, $uid, $new_status, $uid, $new_status, $uid, $remarks);
        $stmt->execute();
        $stmt->close();
    }
    $stmt2 = $conn->prepare("INSERT INTO `{$staff_db}`.`requirement_history` (applicant_id,requirement_id,action,performed_by,remarks) VALUES (?,?,?,?,?)");
    if ($stmt2) { $stmt2->bind_param('iisis', $aid, $rid, $new_status, $user_id, $remarks); $stmt2->execute(); $stmt2->close(); }
    $rn=$conn->query("SELECT requirement_name FROM `{$staff_db}`.`admission_requirements` WHERE id=".intval($rid));
    $req_name=($rn&&$rn->num_rows)?$rn->fetch_assoc()['requirement_name']:"requirement #$rid";
    logAdmission($conn,$user_id,"Requirement $new_status",'requirements',$rid,"$req_name $new_status for applicant #$aid");
    echo json_encode(['success'=>true]);exit;
}

if ($ajax === 'receive_requirement') {
    header('Content-Type: application/json');
    $rid=intval($_POST['id']??$_POST['requirement_id']??0);
    $aid=intval($_POST['applicant_id']??0);
    if($aid&&$rid){
        $conn->query("INSERT INTO `{$staff_db}`.`applicant_requirement_status` (applicant_id,requirement_id,status,received_by,received_at,submitted_by,submitted_at) VALUES ($aid,$rid,'Submitted',$user_id,NOW(),$user_id,NOW()) ON DUPLICATE KEY UPDATE status='Submitted',received_by=$user_id,received_at=NOW(),submitted_by=$user_id,submitted_at=NOW()");
        $conn->query("INSERT INTO `{$staff_db}`.`requirement_history` (applicant_id,requirement_id,action,performed_by,remarks) VALUES ($aid,$rid,'Received',$user_id,'Item received at receiving center')");
        logAdmission($conn,$user_id,'Receive Requirement','receiving',$aid,"Requirement #$rid received for applicant");
    }elseif($rid){
        $r=$conn->query("SELECT id FROM `{$staff_db}`.`applicants` WHERE status IN('New Applicant','Under Review','Approved','Registered')");
        if($r) while($row=$r->fetch_assoc()){
            $aaid=(int)$row['id'];
            $conn->query("INSERT INTO `{$staff_db}`.`applicant_requirement_status` (applicant_id,requirement_id,status,received_by,received_at,submitted_by,submitted_at) VALUES ($aaid,$rid,'Submitted',$user_id,NOW(),$user_id,NOW()) ON DUPLICATE KEY UPDATE status='Submitted',received_by=$user_id,received_at=NOW(),submitted_by=$user_id,submitted_at=NOW()");
        }
        logAdmission($conn,$user_id,'Receive Requirement','receiving',$rid,"Requirement #$rid received for all applicants");
    }else{echo json_encode(['success'=>false,'error'=>'ID required']);exit;}
    echo json_encode(['success'=>true]);exit;
}

if ($ajax === 'reopen_requirement') {
    header('Content-Type: application/json');
    $aid=intval($_POST['applicant_id']??0);
    $rid=intval($_POST['requirement_id']??0);
    if(!$aid||!$rid){echo json_encode(['success'=>false,'error'=>'Invalid IDs']);exit;}
    $conn->query("UPDATE `{$staff_db}`.`applicant_requirement_status` SET status='Not Submitted',received_by=NULL,received_at=NULL,submitted_by=NULL,submitted_at=NULL,verified_by=NULL,verified_at=NULL,rejected_by=NULL,remarks='' WHERE applicant_id=$aid AND requirement_id=$rid");
    $conn->query("INSERT INTO `{$staff_db}`.`requirement_history` (applicant_id,requirement_id,action,performed_by,remarks) VALUES ($aid,$rid,'Reopened',$user_id,'Requirement reopened for resubmission')");
    logAdmission($conn,$user_id,'Reopen Requirement','receiving',$rid,"Requirement #$rid reopened for applicant $aid");
    echo json_encode(['success'=>true]);exit;
}

if ($ajax === 'mark_all_submitted') {
    header('Content-Type: application/json');
    $aid=intval($_POST['applicant_id']??0);
    if(!$aid){echo json_encode(['success'=>false]);exit;}
    foreach($req_items as $ri){
        $iid=$ri['id'];
        $conn->query("INSERT INTO `{$staff_db}`.`applicant_requirement_status` (applicant_id,requirement_id,status,submitted_by,submitted_at) VALUES (".intval($aid).",".intval($iid).",'Submitted',".intval($user_id).",NOW()) ON DUPLICATE KEY UPDATE status='Submitted',submitted_by=".intval($user_id).",submitted_at=NOW()");
        $conn->query("INSERT INTO `{$staff_db}`.`requirement_history` (applicant_id,requirement_id,action,performed_by,remarks) VALUES (".intval($aid).",".intval($iid).",'Submitted',".intval($user_id).",'Bulk submitted')");
    }
    logAdmission($conn,$user_id,'Bulk Submitted','requirements',$aid,"All requirements submitted for applicant #$aid");
    echo json_encode(['success'=>true]);exit;
}

if ($ajax === 'reset_requirements') {
    header('Content-Type: application/json');
    $aid=intval($_POST['applicant_id']??0);
    if($aid){
        $conn->query("UPDATE `{$staff_db}`.`applicant_requirement_status` SET status='Not Submitted',submitted_by=NULL,verified_by=NULL,rejected_by=NULL,submitted_at=NULL,verified_at=NULL,remarks='' WHERE applicant_id=".intval($aid));
        $conn->query("INSERT INTO `{$staff_db}`.`requirement_history` (applicant_id,action,performed_by,remarks) VALUES (".intval($aid).",'Reset',".intval($user_id).",'All requirements reset')");
        logAdmission($conn,$user_id,'Reset All','requirements',$aid,"All requirements reset for applicant #$aid");
    } else {
        $conn->query("UPDATE `{$staff_db}`.`applicant_requirement_status` SET status='Not Submitted',submitted_by=NULL,verified_by=NULL,rejected_by=NULL,submitted_at=NULL,verified_at=NULL,remarks=''");
        logAdmission($conn,$user_id,'Reset All Requirements','requirements',0,"All applicant requirements reset");
    }
    echo json_encode(['success'=>true]);exit;
}

if ($ajax === 'upload_document') {
    header('Content-Type: application/json');
    $aid=intval($_POST['applicant_id']??0);
    $rid=intval($_POST['requirement_id']??0);
    $docType=trim($_POST['document_type']??'Other');
    $docTitle=trim($_POST['document_title']??($docType));
    if(!$aid||empty($_FILES['doc_file']['name'])){echo json_encode(['success'=>false,'error'=>'Missing data']);exit;}
    $uploadDir=__DIR__.'/../uploads/admissions/';
    if(!is_dir($uploadDir))mkdir($uploadDir,0755,true);
    $ext=strtolower(pathinfo($_FILES['doc_file']['name'],PATHINFO_EXTENSION));
    if(!in_array($ext,['pdf','jpg','jpeg','png','gif','doc','docx'])){echo json_encode(['success'=>false,'error'=>'File type not allowed']);exit;}
    $fname='doc_'.$aid.'_'.time().'_'.bin2hex(random_bytes(4)).'.'.$ext;
    $fpath='uploads/admissions/'.$fname;
    if(move_uploaded_file($_FILES['doc_file']['tmp_name'],__DIR__.'/../'.$fpath)){
        $stmt=$conn->prepare("INSERT INTO `{$staff_db}`.`student_documents` (applicant_id,requirement_id,document_type,document_title,file_name,file_path,file_size,mime_type,uploaded_by) VALUES (?,?,?,?,?,?,?,?,?)");
        if($stmt){$fn=$fname;$fs=$_FILES['doc_file']['size'];$mt=$_FILES['doc_file']['type']??'';$stmt->bind_param('iissssssi',$aid,$rid,$docType,$docTitle,$fn,$fpath,$fs,$mt,$user_id);$stmt->execute();$stmt->close();logAdmission($conn,$user_id,'Upload Document','documents',$aid,"Document uploaded");echo json_encode(['success'=>true]);}
        else{echo json_encode(['success'=>false,'error'=>'DB error']);}
    }else{echo json_encode(['success'=>false,'error'=>'Upload failed']);}
    exit;
}

if ($ajax === 'send_notification') {
    header('Content-Type: application/json');
    $title=trim($_POST['title']??'');
    $message=trim($_POST['message']??'');
    $recipientType=trim($_POST['recipient_type']??'applicant');
    $applicantId=intval($_POST['applicant_id']??0);
    $channel=trim($_POST['channel']??'portal');
    if(!$title||!$message){echo json_encode(['success'=>false,'error'=>'Title and message required']);exit;}
    $stmt = $conn->prepare("INSERT INTO `{$staff_db}`.`admission_notifications` (applicant_id,recipient_type,title,message,channel,sent_by) VALUES (?,?,?,?,?,?)");
    if ($stmt) { $stmt->bind_param('issssi', $applicantId, $recipientType, $title, $message, $channel, $user_id); $stmt->execute(); $stmt->close(); }
    logAdmission($conn,$user_id,'Send Notification','communication',$applicantId,"Notification sent: $title");
    echo json_encode(['success'=>true]);exit;
}

if ($ajax === 'send_message') {
    header('Content-Type: application/json');
    $applicantId=intval($_POST['applicant_id']??$_POST['id']??0);
    $subject=trim($_POST['subject']??'Admissions Message');
    $message=trim($_POST['message']??'');
    $recipientType=trim($_POST['recipient_type']??'applicant');
    if(!$applicantId||!$message){echo json_encode(['success'=>false,'error'=>'Message required']);exit;}
    $stmt = $conn->prepare("INSERT INTO `{$staff_db}`.`applicant_messages` (applicant_id,sender_id,recipient_type,subject,message) VALUES (?,?,?,?,?)");
    if ($stmt) { $stmt->bind_param('iisss', $applicantId, $user_id, $recipientType, $subject, $message); $stmt->execute(); $stmt->close(); }
    logAdmission($conn,$user_id,'Send Message','communication',$applicantId,"Message sent");
    echo json_encode(['success'=>true]);exit;
}

if ($ajax === 'get_messages') {
    header('Content-Type: application/json');
    $aid=intval($_GET['applicant_id']??0);
    if(!$aid){echo json_encode([]);exit;}
    $r=$conn->query("SELECT m.*,s.full_name sender_name FROM `{$staff_db}`.`applicant_messages` m LEFT JOIN `{$staff_db}`.`staff` s ON m.sender_id=s.id WHERE m.applicant_id=$aid ORDER BY m.created_at DESC LIMIT 50");
    $out=[];if($r)while($row=$r->fetch_assoc())$out[]=$row;
    echo json_encode($out);exit;
}

if ($ajax === 'get_notifications') {
    header('Content-Type: application/json');
    $type=trim($_GET['type']??'all');
    if ($type !== 'all') {
        $stmt = $conn->prepare("SELECT n.*,s.full_name sender_name FROM `{$staff_db}`.`admission_notifications` n LEFT JOIN `{$staff_db}`.`staff` s ON n.sent_by=s.id WHERE recipient_type=? ORDER BY n.created_at DESC LIMIT 50");
        if ($stmt) { $stmt->bind_param('s', $type); $stmt->execute(); $r = $stmt->get_result(); $stmt->close(); } else $r = null;
    } else {
        $r = $conn->query("SELECT n.*,s.full_name sender_name FROM `{$staff_db}`.`admission_notifications` n LEFT JOIN `{$staff_db}`.`staff` s ON n.sent_by=s.id ORDER BY n.created_at DESC LIMIT 50");
    }
    $out=[];if($r)while($row=$r->fetch_assoc())$out[]=$row;
    echo json_encode(['data'=>$out]);exit;
}

if ($ajax === 'registration_readiness') {
    header('Content-Type: application/json');
    $r=$conn->query("SELECT a.id,a.full_name,a.application_number,a.status,
        (SELECT COUNT(*) FROM `{$staff_db}`.`applicant_requirement_status` WHERE applicant_id=a.id AND status='Verified') verified_count,
        (SELECT COUNT(*) FROM `{$staff_db}`.`applicant_requirement_status` WHERE applicant_id=a.id AND status='Submitted') submitted_count,
        (SELECT COUNT(*) FROM `{$staff_db}`.`applicant_requirement_status` WHERE applicant_id=a.id AND status='Not Submitted') not_submitted_count,
        (SELECT COUNT(*) FROM `{$staff_db}`.`applicant_requirement_status` WHERE applicant_id=a.id AND status='Missing') missing_count,
        (SELECT COUNT(*) FROM `{$staff_db}`.`applicant_requirement_status` WHERE applicant_id=a.id AND status='Rejected') rejected_count
        FROM `{$staff_db}`.`applicants` a WHERE a.status IN('New Applicant','Under Review','Approved','Registered') ORDER BY a.created_at DESC LIMIT 200");
    $out=[];
    if($r)while($row=$r->fetch_assoc()){
        $row['total']=$total_req_items;
        $ready=$row['verified_count']>=$total_req_items;
        if($row['status']==='Registered'){$row['readiness']='Registered';}
        elseif($ready){$row['readiness']='Fully Ready';}
        elseif($row['submitted_count']>0){$row['readiness']='Awaiting Verification';}
        elseif($row['not_submitted_count']==$total_req_items){$row['readiness']='Not Started';}
        else{$row['readiness']='Missing Requirements';}
        $row['progress_pct']=$total_req_items>0?round(($row['verified_count']/$total_req_items)*100):0;
        $out[]=$row;
    }
    echo json_encode($out);exit;
}

if ($ajax === 'convert_to_student') {
    header('Content-Type: application/json');
    $aid=intval($_POST['applicant_id']??0);
    if(!$aid||!$students_conn){echo json_encode(['success'=>false,'error'=>'Invalid']);exit;}
    $ar=$conn->query("SELECT * FROM `{$staff_db}`.`applicants` WHERE id=$aid LIMIT 1");
    if(!$ar||!$ar->num_rows){echo json_encode(['success'=>false,'error'=>'Applicant not found']);exit;}
    $app=$ar->fetch_assoc();
    $check_no=$app['application_number'];
    $stmt = $students_conn->prepare("SELECT id FROM `{$students_db}`.`students` WHERE student_number=? LIMIT 1");
    if ($stmt) { $stmt->bind_param('s', $check_no); $stmt->execute(); $check = $stmt->get_result(); $stmt->close(); } else $check = null;
    if($check&&$check->num_rows){echo json_encode(['success'=>false,'error'=>'Student already exists']);exit;}
    $fullName=trim($app['full_name']);
    $phone=trim($app['phone']);
    $email=trim($app['email']);
    $gender=trim($app['gender']?:'Other');
    $dob=$app['date_of_birth'] ?: null;
    $intakePeriod=trim($app['intake']?:'January');
    $studentNo=trim($app['application_number']);
    $conn->query("UPDATE `{$staff_db}`.`applicants` SET status='Registered' WHERE id=$aid");
    $stmt = $students_conn->prepare("INSERT INTO `{$students_db}`.`students` (student_number,full_name,phone,email,gender,date_of_birth,intake_period,status,created_at,updated_at) VALUES (?,?,?,?,?,?,?,?,NOW(),NOW())");
    if ($stmt) { $stmt->bind_param('ssssssss', $studentNo, $fullName, $phone, $email, $gender, $dob, $intakePeriod, 'Active'); $stmt->execute(); $newSid = $students_conn->insert_id; $stmt->close(); }
    else $newSid = 0;
    if($newSid > 0){logAdmission($conn,$user_id,'Convert to Student','students',$newSid,"Applicant #$aid converted");echo json_encode(['success'=>true,'student_id'=>$newSid]);}
    else{echo json_encode(['success'=>false,'error'=>$students_conn->error]);}
    exit;
}

if ($ajax === 'approve_applicant_ajax') {
    header('Content-Type: application/json');
    $aid=intval($_POST['applicant_id']??0);
    if($aid){$conn->query("UPDATE `{$staff_db}`.`applicants` SET status='Approved' WHERE id=".intval($aid));logAdmission($conn,$user_id,'Approve','applicants',$aid,"Applicant approved");echo json_encode(['success'=>true]);exit;}
    echo json_encode(['success'=>false]);exit;
}
if ($ajax === 'reject_applicant_ajax') {
    header('Content-Type: application/json');
    $aid=intval($_POST['applicant_id']??0);
    $reason=trim($_POST['reason']??'');
    if($aid){
        $reasonEsc=$conn->real_escape_string($reason);
        $conn->query("UPDATE `{$staff_db}`.`applicants` SET status='Rejected',rejection_reason='$reasonEsc' WHERE id=".intval($aid));
        logAdmission($conn,$user_id,'Reject','applicants',$aid,"Applicant rejected".($reason?" — $reason":''));
        echo json_encode(['success'=>true]);exit;
    }
    echo json_encode(['success'=>false]);exit;
}
if ($ajax === 'get_applicant_data') {
    header('Content-Type: application/json');
    $aid=intval($_GET['id']??($_POST['id']??0));
    if(!$aid){echo json_encode(['success'=>false,'error'=>'ID required']);exit;}
    $r=$conn->query("SELECT a.*,COALESCE((SELECT program_name FROM `{$staff_db}`.`academic_programs` WHERE id=a.program_id),'N/A') program_name FROM `{$staff_db}`.`applicants` a WHERE a.id=$aid LIMIT 1");
    $data=$r?($r->fetch_assoc()?:[]):[];
    if(empty($data)){echo json_encode(['success'=>false,'error'=>'Not found']);exit;}
    echo json_encode(['success'=>true,'data'=>$data]);exit;
}

if ($ajax === 'get_applicant_reqs') {
    header('Content-Type: application/json');
    $aid=intval($_GET['applicant_id']??($_POST['id']??$_POST['applicant_id']??0));
    if(!$aid){echo json_encode(['data'=>[]]);exit;}
    $info=[];$rn=$conn->query("SELECT a.*,COALESCE((SELECT program_name FROM `{$staff_db}`.`academic_programs` WHERE id=a.program_id),'N/A') program_name FROM `{$staff_db}`.`applicants` a WHERE a.id=$aid");if($rn)$info=$rn->fetch_assoc()?:[];
    $req=[];$rr=$conn->query("SELECT adr.id,adr.requirement_name,adr.display_order,ars.status,ars.remarks,ars.submitted_at,ars.verified_at,ars.received_at,ars.received_by FROM `{$staff_db}`.`admission_requirements` adr LEFT JOIN `{$staff_db}`.`applicant_requirement_status` ars ON ars.requirement_id=adr.id AND ars.applicant_id=$aid WHERE adr.is_active=1 ORDER BY adr.display_order");
    if($rr)while($row=$rr->fetch_assoc()){$row['status']=$row['status']??'Not Submitted';$req[]=$row;}
    $docs=[];$dr=$conn->query("SELECT sd.*,s.full_name uploader_name FROM `{$staff_db}`.`student_documents` sd LEFT JOIN `{$staff_db}`.`staff` s ON sd.uploaded_by=s.id WHERE sd.applicant_id=$aid ORDER BY sd.uploaded_at DESC");
    if($dr)while($row=$dr->fetch_assoc())$docs[]=$row;
    $hist=[];$rh=$conn->query("SELECT rh.*,s.full_name performed_by_name FROM `{$staff_db}`.`requirement_history` rh LEFT JOIN `{$staff_db}`.`staff` s ON rh.performed_by=s.id WHERE rh.applicant_id=$aid ORDER BY rh.created_at DESC LIMIT 50");
    if($rh)while($row=$rh->fetch_assoc())$hist[]=$row;
    echo json_encode(['success'=>true,'data'=>['info'=>$info,'requirements'=>$req,'documents'=>$docs,'history'=>$hist]]);exit;
}

if ($ajax === 'reports_data') {
    header('Content-Type: application/json');
    $type=trim($_GET['type']??($_POST['type']??'admission'));
    $format=trim($_GET['format']??($_POST['format']??'json'));
    $from=trim($_GET['from']??($_POST['from']??''));
    $to=trim($_GET['to']??($_POST['to']??''));
    $dateParams = [];
    $dateTypes = '';
    $dateWhere='';
    if($from&&$to){$dateWhere=" AND DATE(a.created_at) BETWEEN ? AND ?"; $dateParams[] = $from; $dateParams[] = $to; $dateTypes = 'ss';}
    elseif($from){$dateWhere=" AND DATE(a.created_at) >= ?"; $dateParams[] = $from; $dateTypes = 's';}
    elseif($to){$dateWhere=" AND DATE(a.created_at) <= ?"; $dateParams[] = $to; $dateTypes = 's';}
    $data=[];
    if($type==='admission'){
        $sql = "SELECT a.*,COALESCE((SELECT program_name FROM `{$staff_db}`.`academic_programs` WHERE id=a.program_id),'N/A') program_name FROM `{$staff_db}`.`applicants` a WHERE 1=1$dateWhere ORDER BY a.created_at DESC LIMIT 500";
        if ($dateTypes) { $stmt = $conn->prepare($sql); $stmt->bind_param($dateTypes, ...$dateParams); $stmt->execute(); $r = $stmt->get_result(); $stmt->close(); }
        else $r = $conn->query($sql);
        if($r)while($row=$r->fetch_assoc())$data[]=$row;
    }elseif($type==='requirements'){
        $r=$conn->query("SELECT a.id,a.full_name,a.application_number,(SELECT COUNT(*) FROM `{$staff_db}`.`applicant_requirement_status` WHERE applicant_id=a.id AND status='Verified') verified,(SELECT COUNT(*) FROM `{$staff_db}`.`applicant_requirement_status` WHERE applicant_id=a.id AND status='Submitted') submitted,(SELECT COUNT(*) FROM `{$staff_db}`.`applicant_requirement_status` WHERE applicant_id=a.id AND status='Not Submitted') not_submitted,(SELECT COUNT(*) FROM `{$staff_db}`.`applicant_requirement_status` WHERE applicant_id=a.id AND status='Missing') missing,(SELECT COUNT(*) FROM `{$staff_db}`.`applicant_requirement_status` WHERE applicant_id=a.id AND status='Rejected') rejected FROM `{$staff_db}`.`applicants` a WHERE a.status IN('New Applicant','Under Review','Approved','Registered')$dateWhere ORDER BY a.full_name LIMIT 500");
        if($r)while($row=$r->fetch_assoc()){$row['total']=$total_req_items;$row['clearance_pct']=$total_req_items>0?round(($row['verified']/$total_req_items)*100):0;$data[]=$row;}
    }elseif($type==='registration'){
        $r=$conn->query("SELECT status,COUNT(*)c FROM `{$staff_db}`.`applicants` WHERE 1=1$dateWhere GROUP BY status");
        if($r)while($row=$r->fetch_assoc())$data[]=$row;
    }elseif($type==='intake'){
        $r=$conn->query("SELECT intake,COUNT(*)c FROM `{$staff_db}`.`applicants` WHERE intake IS NOT NULL AND intake!=''$dateWhere GROUP BY intake ORDER BY intake");
        if($r)while($row=$r->fetch_assoc())$data[]=$row;
    }elseif($type==='clearance'){
        $clrW="1=1"; $clrParams=[]; $clrTypes='';
        $clrProg=trim($_POST['program']??''); $clrIntake=trim($_POST['intake']??'');
        if($clrProg!==''){$clrW.=" AND a.program_id=?";$clrParams[]=intval($clrProg);$clrTypes.='i';}
        if($clrIntake!==''){$clrW.=" AND a.intake=?";$clrParams[]=$clrIntake;$clrTypes.='s';}
        $clrSql="SELECT a.id,a.full_name,a.application_number,a.phone,a.status,a.intake,COALESCE((SELECT program_name FROM `{$staff_db}`.`academic_programs` WHERE id=a.program_id),'N/A') program_name,(SELECT COUNT(*) FROM `{$staff_db}`.`applicant_requirement_status` WHERE applicant_id=a.id AND status='Verified') verified_count,(SELECT COUNT(*) FROM `{$staff_db}`.`admission_requirements` WHERE is_active=1) total_req FROM `{$staff_db}`.`applicants` a WHERE $clrW$dateWhere ORDER BY a.full_name LIMIT 500";
        if($clrTypes){$stmt=$conn->prepare($clrSql);$stmt->bind_param($clrTypes,...$clrParams);$stmt->execute();$r=$stmt->get_result();$stmt->close();}
        else $r=$conn->query($clrSql);
        if($r)while($row=$r->fetch_assoc()){$data[]=$row;}
    }
    if($format==='csv'){
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="admissions_report_'.date('Ymd').'.csv"');
        $out=fopen('php://output','w');
        if(!empty($data)){fputcsv($out,array_keys($data[0]));foreach($data as $row)fputcsv($out,$row);}
        fclose($out);exit;
    }
    echo json_encode(['data'=>$data,'total'=>count($data)]);exit;
}

if ($ajax === 'toggle_student_status') {
    header('Content-Type: application/json');
    $sid=intval($_POST['student_id']??0);
    $ns=trim($_POST['new_status']??'');
    if(!$sid||!$ns||!$students_conn){echo json_encode(['success'=>false,'error'=>'Invalid']);exit;}
    $stmt = $students_conn->prepare("UPDATE `{$students_db}`.`students` SET status=? WHERE id=?");
    if ($stmt) { $stmt->bind_param('si', $ns, $sid); $stmt->execute(); $stmt->close(); }
    echo json_encode(['success'=>true,'message'=>'Student '.strtolower($ns)]);exit;
}
if ($ajax === 'clear_notifications') {
    header('Content-Type: application/json');
    $conn->query("DELETE FROM `{$staff_db}`.`admission_notifications`");
    echo json_encode(['success'=>true]);exit;
}
if ($ajax === 'get_requirements_tracking') {
    header('Content-Type: application/json');
    $q=trim($_GET['q']??($_POST['q']??''));
    $st=trim($_GET['status']??($_POST['status']??''));
    $w=["1=1"];$params=[];$types='';
    if($q!==''){$w[]="(a.full_name LIKE ? OR a.application_number LIKE ?)"; $like="%$q%"; $params[]=$like; $params[]=$like; $types.='ss';}
    if($st!==''){$w[]="ars.status=?"; $params[]=$st; $types.='s';}
    $ws='WHERE '.implode(' AND ',$w);
    $stmt=$conn->prepare("SELECT ars.*,a.full_name,a.application_number,adr.requirement_name FROM `{$staff_db}`.`applicant_requirement_status` ars LEFT JOIN `{$staff_db}`.`applicants` a ON ars.applicant_id=a.id LEFT JOIN `{$staff_db}`.`admission_requirements` adr ON ars.requirement_id=adr.id $ws ORDER BY ars.updated_at DESC LIMIT 200");
    if ($stmt) { if ($types) $stmt->bind_param($types, ...$params); $stmt->execute(); $r = $stmt->get_result(); $stmt->close(); } else $r = null;
    $out=[];if($r)while($row=$r->fetch_assoc()){$out[]=$row;}
    echo json_encode(['data'=>$out]);exit;
}
if ($ajax === 'get_verification_list') {
    header('Content-Type: application/json');
    $r=$conn->query("SELECT sd.*,a.full_name,adr.requirement_name FROM `{$staff_db}`.`student_documents` sd LEFT JOIN `{$staff_db}`.`applicants` a ON sd.applicant_id=a.id LEFT JOIN `{$staff_db}`.`admission_requirements` adr ON sd.requirement_id=adr.id WHERE sd.verification_status='Pending' ORDER BY sd.uploaded_at DESC LIMIT 100");
    $out=[];if($r)while($row=$r->fetch_assoc()){$out[]=$row;}
    echo json_encode(['data'=>$out]);exit;
}
if ($ajax === 'get_documents_list') {
    header('Content-Type: application/json');
    $r=$conn->query("SELECT sd.*,a.full_name,adr.requirement_name FROM `{$staff_db}`.`student_documents` sd LEFT JOIN `{$staff_db}`.`applicants` a ON sd.applicant_id=a.id LEFT JOIN `{$staff_db}`.`admission_requirements` adr ON sd.requirement_id=adr.id ORDER BY sd.uploaded_at DESC LIMIT 200");
    $out=[];if($r)while($row=$r->fetch_assoc()){$out[]=$row;}
    echo json_encode(['data'=>$out]);exit;
}
if ($ajax === 'get_registration_list') {
    header('Content-Type: application/json');
    $q=trim($_POST['q']??'');$intake=trim($_POST['intake']??'');
    $w=["a.status IN('Approved','Registered')"];$params=[];$types='';
    if($q!==''){$w[]="(a.full_name LIKE ? OR a.application_number LIKE ?)"; $like="%$q%"; $params[]=$like; $params[]=$like; $types.='ss';}
    if($intake!==''){$w[]="a.intake=?"; $params[]=$intake; $types.='s';}
    $ws='WHERE '.implode(' AND ',$w);
    $stmt=$conn->prepare("SELECT a.*,COALESCE((SELECT program_name FROM `{$staff_db}`.`academic_programs` WHERE id=a.program_id),'N/A') program_name,(CASE WHEN a.status='Registered' THEN 1 ELSE 0 END) is_registered FROM `{$staff_db}`.`applicants` a $ws ORDER BY a.full_name LIMIT 200");
    if ($stmt) { if ($types) $stmt->bind_param($types, ...$params); $stmt->execute(); $r = $stmt->get_result(); $stmt->close(); } else $r = null;
    $out=[];if($r)while($row=$r->fetch_assoc()){$out[]=$row;}
    echo json_encode(['data'=>$out]);exit;
}
if ($ajax === 'get_requirement_alerts') {
    header('Content-Type: application/json');
    $r=$conn->query("SELECT ars.*,a.full_name,a.application_number,adr.requirement_name FROM `{$staff_db}`.`applicant_requirement_status` ars LEFT JOIN `{$staff_db}`.`applicants` a ON ars.applicant_id=a.id LEFT JOIN `{$staff_db}`.`admission_requirements` adr ON ars.requirement_id=adr.id WHERE ars.status IN('Missing','Rejected','Not Submitted') AND a.status NOT IN('Registered','Rejected') ORDER BY ars.updated_at DESC LIMIT 100");
    $out=[];if($r)while($row=$r->fetch_assoc()){$out[]=$row;}
    echo json_encode(['data'=>$out]);exit;
}
if ($ajax === 'verify_document') {
    header('Content-Type: application/json');
    $docId=intval($_POST['doc_id']??$_POST['id']??0);
    $newStatus=$_POST['status']??'';
    $remarks=trim($_POST['remarks']??'');
    if(!$docId||!in_array($newStatus,['Verified','Rejected'])){echo json_encode(['success'=>false,'error'=>'Invalid']);exit;}
    $stmt = $conn->prepare("UPDATE `{$staff_db}`.`student_documents` SET verification_status=?,verified_by=?,verified_at=NOW(),remarks=? WHERE id=?");
    if ($stmt) { $stmt->bind_param('sisi', $newStatus, $user_id, $remarks, $docId); $stmt->execute(); $stmt->close(); }
    echo json_encode(['success'=>true]);exit;
}

// ══════════════════════════════════════════════════════════════════════
// POST HANDLERS
// ══════════════════════════════════════════════════════════════════════
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'add_applicant') {
        $fn=trim($_POST['full_name']??'');
        $dob=$_POST['date_of_birth']??null;
        $gen=trim($_POST['gender']??'Other');
        $ph=trim($_POST['phone']??'');
        $em=trim($_POST['email']??'');
        $addr=trim($_POST['address']??'');
        $gn=trim($_POST['guardian_name']??'');
        $gp=trim($_POST['guardian_phone']??'');
        $gr=trim($_POST['guardian_relationship']??'');
        $prog_id=intval($_POST['program_id']??0);
        $intake=trim($_POST['intake']??'');
        $app_num='APP-'.date('Y').str_pad(mt_rand(1,99999),5,'0',STR_PAD_LEFT);
        if(!$fn){$_SESSION['error']='Full name required.';header("Location: director-admissions.php");exit;}
        if($ph){$stmt=$conn->prepare("SELECT id FROM `{$staff_db}`.`applicants` WHERE phone=? LIMIT 1");if($stmt){$stmt->bind_param('s',$ph);$stmt->execute();$dup=$stmt->get_result();$stmt->close();if($dup&&$dup->num_rows){$_SESSION['error']='Phone already exists.';header("Location: director-admissions.php");exit;}}}
        if($em){$stmt=$conn->prepare("SELECT id FROM `{$staff_db}`.`applicants` WHERE email=? LIMIT 1");if($stmt){$stmt->bind_param('s',$em);$stmt->execute();$dup=$stmt->get_result();$stmt->close();if($dup&&$dup->num_rows){$_SESSION['error']='Email already exists.';header("Location: director-admissions.php");exit;}}}
        $stmt = $conn->prepare("INSERT INTO `{$staff_db}`.`applicants` (full_name,date_of_birth,gender,phone,email,address,guardian_name,guardian_phone,guardian_relationship,application_number,program_id,intake,admission_date,status) VALUES (?,?,?, ?,?, ?,?, ?,?, ?,?,?,CURDATE(),'New Applicant')");
        if ($stmt) { $stmt->bind_param('ssssssssssis', $fn, $dob, $gen, $ph, $em, $addr, $gn, $gp, $gr, $app_num, $prog_id, $intake); $stmt->execute(); $aid = $conn->insert_id; $stmt->close(); }
        else $aid = 0;
        if($aid > 0){foreach($req_items as $ri){$stmt2=$conn->prepare("INSERT INTO `{$staff_db}`.`applicant_requirement_status` (applicant_id,requirement_id,status) VALUES (?,?,?)");if($stmt2){$s='Not Submitted';$stmt2->bind_param('iis',$aid,$ri['id'],$s);$stmt2->execute();$stmt2->close();}}logAdmission($conn,$user_id,'Add Applicant','applicants',$aid,"Added applicant: $fn ($app_num)");$_SESSION['success']="Applicant '$fn' added. App No: $app_num";}
        else{$_SESSION['error']='Failed: '.$conn->error;}
        header("Location: director-admissions.php");exit;
    }
    if ($action === 'edit_applicant') {
        header('Content-Type: application/json');
        $aid=intval($_POST['id']??$_POST['applicant_id']??0);
        $fn=trim($_POST['full_name']??'');
        $dob=$_POST['date_of_birth']??trim($_POST['dob']??'');
        $gen=trim($_POST['gender']??'Other');
        $ph=trim($_POST['phone']??'');
        $em=trim($_POST['email']??'');
        $addr=trim($_POST['address']??'');
        $gn=trim($_POST['guardian_name']??'');
        $gp=trim($_POST['guardian_phone']??'');
        $gr=trim($_POST['guardian_relationship']??'');
        $prog_id=intval($_POST['program_id']??0);
        $intake=trim($_POST['intake']??'');
        $status=trim($_POST['status']??'New Applicant');
        if($aid&&$fn){
            $stmt=$conn->prepare("UPDATE `{$staff_db}`.`applicants` SET full_name=?,date_of_birth=?,gender=?,phone=?,email=?,address=?,guardian_name=?,guardian_phone=?,guardian_relationship=?,program_id=?,intake=?,status=? WHERE id=?");
            if($stmt){$stmt->bind_param('sssssssssisii',$fn,$dob,$gen,$ph,$em,$addr,$gn,$gp,$gr,$prog_id,$intake,$status,$aid);$stmt->execute();$stmt->close();}
            logAdmission($conn,$user_id,'Edit Applicant','applicants',$aid,"Edited applicant: $fn");
            echo json_encode(['success'=>true,'message'=>'Applicant updated.']);exit;
        }
        else{echo json_encode(['success'=>false,'error'=>'Edit failed: missing data.']);exit;}
    }
    if ($action === 'delete_applicant') {
        header('Content-Type: application/json');
        $aid=intval($_POST['applicant_id']??$_POST['id']??0);
        if($aid){$conn->query("DELETE FROM `{$staff_db}`.`requirement_history` WHERE applicant_id=".intval($aid));$conn->query("DELETE FROM `{$staff_db}`.`applicant_requirement_status` WHERE applicant_id=".intval($aid));$conn->query("DELETE FROM `{$staff_db}`.`applicants` WHERE id=".intval($aid));logAdmission($conn,$user_id,'Delete Applicant','applicants',$aid,"Applicant deleted");echo json_encode(['success'=>true,'message'=>'Applicant deleted.']);exit;}
        else{echo json_encode(['success'=>false,'error'=>'Delete failed: invalid ID.']);exit;}
    }
    if ($action === 'edit_student') {
        $sid=intval($_POST['student_id']??0);$status=trim($_POST['status']??'');
        if($sid&&$students_conn){$stmt=$students_conn->prepare("UPDATE `{$students_db}`.`students` SET status=? WHERE id=?");if($stmt){$stmt->bind_param('si',$status,$sid);$stmt->execute();$stmt->close();}}
        echo json_encode(['success'=>true]);exit;
    }
    header("Location: director-admissions.php");exit;
}

// ══════════════════════════════════════════════════════════════════════
// PRINT REPORTS
// ══════════════════════════════════════════════════════════════════════
$report=$_GET['report']??'';
if($report){
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html><html><head><style>body{font-family:sans-serif;padding:20px}table{width:100%;border-collapse:collapse;margin-top:10px}th,td{border:1px solid #ddd;padding:6px 8px;text-align:left}th{background:#f3f4f6}h2{color:#1f2937}@media print{body{print-color-adjust:exact}.no-print{display:none}}</style></head><body>';
    echo '<div class="no-print"><button onclick="window.print()" style="padding:6px 16px;margin-bottom:12px">Print</button> <button onclick="window.close()" style="padding:6px 16px">Close</button></div>';
    if($report==='applications'){echo '<h2>All Applicants Report</h2>';$r=$conn->query("SELECT application_number,full_name,COALESCE((SELECT program_name FROM `{$staff_db}`.`academic_programs` WHERE id=a.program_id),'N/A') program,intake,status,created_at FROM `{$staff_db}`.`applicants` a ORDER BY created_at DESC");echo '<table><thead><tr><th>App No</th><th>Name</th><th>Program</th><th>Intake</th><th>Date</th><th>Status</th></tr></thead><tbody>';if($r)while($row=$r->fetch_assoc()){echo '<tr><td>'.htmlspecialchars($row['application_number']).'</td><td>'.htmlspecialchars($row['full_name']).'</td><td>'.htmlspecialchars($row['program']).'</td><td>'.htmlspecialchars($row['intake']??'-').'</td><td>'.$row['created_at'].'</td><td>'.$row['status'].'</td></tr>';}echo '</tbody></table>';}
    elseif($report==='cleared'){echo '<h2>Fully Cleared Applicants</h2>';$r=$conn->query("SELECT a.id,a.application_number,a.full_name,a.phone,a.status,COUNT(CASE WHEN ars.status='Verified' THEN 1 END) vc FROM `{$staff_db}`.`applicants` a LEFT JOIN `{$staff_db}`.`applicant_requirement_status` ars ON ars.applicant_id=a.id GROUP BY a.id,a.application_number,a.full_name,a.phone,a.status HAVING vc>=$total_req_items ORDER BY a.full_name");echo '<table><thead><tr><th>App No</th><th>Name</th><th>Phone</th><th>Status</th></tr></thead><tbody>';if($r)while($row=$r->fetch_assoc()){echo '<tr><td>'.htmlspecialchars($row['application_number']).'</td><td>'.htmlspecialchars($row['full_name']).'</td><td>'.htmlspecialchars($row['phone']??'-').'</td><td>'.$row['status'].'</td></tr>';}echo '</tbody></table>';}
    elseif($report==='clearance'){echo '<h2>Requirements Clearance Report</h2>';$r=$conn->query("SELECT ars.*,a.full_name applicant_name,adr.requirement_name FROM `{$staff_db}`.`applicant_requirement_status` ars LEFT JOIN `{$staff_db}`.`applicants` a ON ars.applicant_id=a.id LEFT JOIN `{$staff_db}`.`admission_requirements` adr ON ars.requirement_id=adr.id ORDER BY ars.applicant_id,adr.display_order");echo '<table><thead><tr><th>Applicant</th><th>Requirement</th><th>Status</th><th>Verified By</th><th>Date</th></tr></thead><tbody>';if($r)while($row=$r->fetch_assoc()){echo '<tr><td>'.htmlspecialchars($row['applicant_name']??$row['applicant_id']).'</td><td>'.htmlspecialchars($row['requirement_name']??'-').'</td><td>'.$row['status'].'</td><td>'.$row['verified_by'].'</td><td>'.$row['verified_at'].'</td></tr>';}echo '</tbody></table>';}
    elseif($report==='intake'){echo '<h2>Intake Report</h2>';$r=$conn->query("SELECT intake,COUNT(*) total FROM `{$staff_db}`.`applicants` WHERE intake IS NOT NULL AND intake!='' GROUP BY intake ORDER BY intake");echo '<table><thead><tr><th>Intake</th><th>Applicants</th></tr></thead><tbody>';if($r)while($row=$r->fetch_assoc()){echo '<tr><td>'.htmlspecialchars($row['intake']).'</td><td>'.$row['total'].'</td></tr>';}echo '</tbody></table>';}
    echo '</body></html>';exit;
}

?><!DOCTYPE html>
<html lang="en">
<head>
<?php include_once __DIR__ . "/../includes/dashboard_head.php"; ?>
<style>
:root{--adm-prim:#6d28d9;--adm-sec:#5b21b6;--adm-accent:#7c3aed;--adm-bg:#f1f5f9;--adm-card:#ffffff;--adm-border:#e2e8f0;--adm-radius:14px;--adm-shadow:0 1px 3px rgba(0,0,0,.06);--adm-shadow-md:0 4px 16px rgba(0,0,0,.08);--adm-shadow-lg:0 8px 30px rgba(0,0,0,.12)}
.dashboard-content{padding:0!important;background:var(--adm-bg);min-height:100vh}
.adm-content-wrap{padding:20px 24px 40px}
.da-header{background:linear-gradient(135deg,#1e1b4b 0%,#312e81 50%,#4338ca 100%);padding:26px 32px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:14px;position:relative;overflow:hidden}
.da-header::before{content:'';position:absolute;top:-50%;right:-10%;width:300px;height:300px;background:radial-gradient(circle,rgba(124,58,237,.15) 0%,transparent 70%);border-radius:50%}
.da-header::after{content:'';position:absolute;bottom:-40%;left:20%;width:250px;height:250px;background:radial-gradient(circle,rgba(99,102,241,.1) 0%,transparent 70%);border-radius:50%}
.da-header h1{font-size:1.4rem;font-weight:800;color:#fff;margin:0;letter-spacing:-.4px;text-shadow:0 1px 2px rgba(0,0,0,.15);position:relative;z-index:1}
.da-header p{font-size:.8rem;color:rgba(255,255,255,.65);margin:4px 0 0;position:relative;z-index:1}
.da-header .badge{position:relative;z-index:1}
.section-tabs{display:flex;flex-wrap:wrap;gap:4px;margin:0;padding:10px 16px;background:#fff;border-bottom:2px solid var(--adm-border);position:sticky;top:0;z-index:100;box-shadow:0 2px 8px rgba(0,0,0,.04)}
.section-tab{padding:8px 16px;font-size:12px;font-weight:600;color:#64748b;background:transparent;border:1.5px solid transparent;border-radius:8px;cursor:pointer;text-decoration:none;transition:all .2s ease;white-space:nowrap;position:relative}
.section-tab:hover{color:var(--adm-prim);background:rgba(124,58,237,.05);border-color:rgba(124,58,237,.15)}
.section-tab.active{color:var(--adm-prim)!important;background:rgba(124,58,237,.08);border-color:var(--adm-accent);font-weight:700;box-shadow:0 2px 8px rgba(124,58,237,.15)}
.dashboard-section{display:none}.dashboard-section.active{display:block}
.stats-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:16px;margin-bottom:24px}
.stat-card{background:var(--adm-card);border-radius:var(--adm-radius);padding:18px 20px;display:flex;align-items:center;gap:14px;box-shadow:var(--adm-shadow);border:1px solid var(--adm-border);transition:all .25s ease}
.stat-card:hover{box-shadow:var(--adm-shadow-md);transform:translateY(-2px);border-color:#d1d5db}
.stat-icon{width:44px;height:44px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:18px;color:#fff;flex-shrink:0;transition:transform .2s;box-shadow:0 3px 10px rgba(0,0,0,.15)}
.stat-card:hover .stat-icon{transform:scale(1.08)}
.stat-content h3{font-size:1.4rem;font-weight:800;margin:0;color:#1e293b;line-height:1.1}
.stat-content p{font-size:.72rem;color:#64748b;margin:2px 0 0;font-weight:500;letter-spacing:.02em;text-transform:uppercase}
.scard{background:#fff;border-radius:var(--adm-radius);border:1px solid var(--adm-border);margin-bottom:18px;overflow:hidden;box-shadow:var(--adm-shadow);transition:box-shadow .25s}
.scard:hover{box-shadow:var(--adm-shadow-md)}
.sch{padding:14px 20px;font-size:14px;font-weight:700;color:#1e293b;border-bottom:2px solid var(--adm-border);background:linear-gradient(180deg,#fafbff 0%,#f8fafc 100%);display:flex;align-items:center;gap:8px}
.sch i{color:var(--adm-accent);font-size:15px}
.scb{padding:18px 20px}
.badge{display:inline-block;padding:3px 10px;border-radius:8px;font-size:10px;font-weight:700;letter-spacing:.03em}
.badge-success{background:#d1fae5;color:#065f46}.badge-warning{background:#fef3c7;color:#92400e}
.badge-danger{background:#fee2e2;color:#991b1b}.badge-info{background:#dbeafe;color:#1e40af}
.badge-secondary{background:#f1f5f9;color:#475569}.badge-primary{background:#ede9fe;color:#5b21b6}
.empty-state{text-align:center;padding:48px 24px;color:#94a3b8}
.empty-state i{font-size:3.5rem;display:block;margin-bottom:14px;color:#cbd5e1}
.req-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(210px,1fr));gap:10px}
.req-card{border:1.5px solid #e2e8f0;border-radius:10px;padding:14px 16px;background:#fff;transition:all .2s ease}
.req-card:hover{border-color:var(--adm-accent);box-shadow:0 4px 12px rgba(124,58,237,.1);transform:translateY(-1px)}
.req-card .req-name{font-size:12px;font-weight:600;color:#1e293b;margin-bottom:6px}
.req-card .req-status{font-size:10px}
.req-select{width:100%;font-size:11px;padding:5px 8px;border:1.5px solid #e2e8f0;border-radius:6px;transition:border-color .2s}
.req-select:focus{border-color:var(--adm-accent);outline:none;box-shadow:0 0 0 3px rgba(124,58,237,.1)}
.progress-bar-wrap{height:8px;background:#e2e8f0;border-radius:4px;overflow:hidden;margin:6px 0}
.progress-bar-fill{height:100%;background:linear-gradient(90deg,var(--adm-prim),var(--adm-accent));border-radius:4px;transition:width .4s ease}
.filter-group{display:flex;flex-wrap:wrap;gap:10px;margin-bottom:14px;padding:14px 16px;background:#f8fafc;border-radius:10px;border:1px solid var(--adm-border)}
.filter-group input,.filter-group select{padding:8px 12px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:12px;transition:border-color .2s}
.filter-group input:focus,.filter-group select:focus{border-color:var(--adm-accent);outline:none;box-shadow:0 0 0 3px rgba(124,58,237,.1)}
.filter-group .btn{padding:8px 18px;border-radius:8px;font-size:12px;font-weight:600}
.pagination{display:flex;gap:5px;justify-content:center;margin-top:16px;flex-wrap:wrap}
.pagination button{padding:6px 14px;border:1.5px solid #e2e8f0;border-radius:8px;background:#fff;font-size:11px;font-weight:600;cursor:pointer;transition:all .2s}
.pagination button.active{background:var(--adm-accent);color:#fff;border-color:var(--adm-accent);box-shadow:0 2px 8px rgba(124,58,237,.3)}
.pagination button:hover:not(.active){background:#f5f3ff;border-color:var(--adm-accent)}
.readiness-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(190px,1fr));gap:12px;margin-bottom:20px}
.readiness-card{text-align:center;padding:24px 18px;border-radius:var(--adm-radius);border:1.5px solid var(--adm-border);background:#fff;transition:all .25s}
.readiness-card:hover{box-shadow:var(--adm-shadow-md);transform:translateY(-2px)}
.readiness-card .rc-num{font-size:2.2rem;font-weight:800;margin:6px 0;color:#1e293b}
.student-result-card{background:#fff;border:1.5px solid #e2e8f0;border-radius:10px;padding:14px 16px;cursor:pointer;transition:all .2s;margin-bottom:10px}
.student-result-card:hover{border-color:var(--adm-accent);box-shadow:0 4px 12px rgba(124,58,237,.1);transform:translateY(-1px)}
.student-result-card.selected{border-color:var(--adm-accent);background:#f5f3ff;box-shadow:0 0 0 3px rgba(124,58,237,.1)}
.student-result-card .s-name{font-weight:700;color:#1e293b;font-size:13px}
.student-result-card .s-meta{font-size:11px;color:#64748b;margin-top:2px}
.modal-xl{max-width:900px!important}
.modal-content{border-radius:var(--adm-radius);border:none;box-shadow:var(--adm-shadow-lg)}
.modal-header{border-radius:var(--adm-radius) var(--adm-radius) 0 0;padding:18px 24px}
.modal-body{padding:20px 24px}
.notif-item{padding:14px 18px;border-bottom:1px solid var(--adm-border);transition:background .15s}
.notif-item:hover{background:#f8fafc}
.notif-item:last-child{border-bottom:none}
.notif-title{font-weight:700;font-size:13px;color:#1e293b}
.notif-meta{font-size:10px;color:#94a3b8;margin-top:2px}
.profile-sidebar{background:#fff;border:1.5px solid var(--adm-border);border-radius:var(--adm-radius);overflow:hidden;box-shadow:var(--adm-shadow)}
.psh{padding:12px 18px;font-weight:700;font-size:13px;color:#1e293b;background:linear-gradient(180deg,#fafbff 0%,#f8fafc 100%);border-bottom:2px solid var(--adm-border)}
.psb{padding:14px 18px}
.psb dl{margin:0}
.psb dt{font-size:10px;color:#64748b;text-transform:uppercase;font-weight:600;margin-top:8px;letter-spacing:.04em}
.psb dd{font-size:13px;color:#1e293b;margin:0 0 4px 0;font-weight:500}
.search-filters-wrap{background:#f8fafc;border-radius:10px;padding:16px;border:1.5px solid var(--adm-border);margin-bottom:14px}
.form-label{font-size:12px;font-weight:600;color:#374151;margin-bottom:4px}
.form-control,.form-select{border-radius:8px;font-size:13px;border-color:#d1d5db;transition:border-color .2s,box-shadow .2s}
.form-control:focus,.form-select:focus{border-color:var(--adm-accent);box-shadow:0 0 0 3px rgba(124,58,237,.1)}
.table{border-radius:10px;overflow:hidden}
.table thead th{font-weight:700;color:#374151;border-bottom:2px solid var(--adm-border);font-size:11px;text-transform:uppercase;letter-spacing:.04em;padding:10px 14px}
.table tbody td{padding:10px 14px;font-size:12px;vertical-align:middle}
.table-hover tbody tr:hover{background:#f8fafc}
.btn{border-radius:8px;font-weight:600;font-size:12px;transition:all .2s}
.btn-sm{padding:5px 12px;font-size:11px}
.btn-primary{background:var(--adm-accent);border-color:var(--adm-accent)}
.btn-primary:hover{background:var(--adm-prim);border-color:var(--adm-prim);box-shadow:0 2px 8px rgba(124,58,237,.3)}
.alert{border-radius:10px;font-size:13px;padding:12px 18px}
code{font-size:11px;background:#f1f5f9;padding:2px 7px;border-radius:5px;color:#475569;font-weight:500}
@media(max-width:768px){
    .section-tabs{overflow-x:auto;flex-wrap:nowrap;padding:8px 10px;-webkit-overflow-scrolling:touch;scrollbar-width:none;gap:3px}
    .section-tabs::-webkit-scrollbar{display:none}
    .section-tab{font-size:10px;padding:6px 10px;white-space:nowrap;flex-shrink:0}
    .stats-grid{grid-template-columns:repeat(2,1fr);gap:8px}
    .stat-card{padding:12px 14px;gap:10px}
    .stat-icon{width:36px;height:36px;font-size:15px;border-radius:8px}
    .stat-content h3{font-size:1.1rem}
    .stat-content p{font-size:.65rem}
    .filter-group{flex-direction:column;gap:8px;padding:12px}
    .filter-group input,.filter-group select{width:100%}
    .filter-group .btn{width:100%}
    .req-grid{grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:8px}
    .scard{margin-bottom:12px}
    .sch{padding:12px 14px;font-size:13px}
    .scb{padding:12px 14px}
    .da-header{padding:16px 18px}
    .da-header h1{font-size:1.1rem}
    .da-header p{font-size:.72rem}
    .table-responsive{font-size:12px;border-radius:10px}
    .table-sm th,.table-sm td{padding:8px 10px}
    .readiness-grid{grid-template-columns:repeat(2,1fr);gap:8px}
    .readiness-card{padding:16px 12px}
    .readiness-card .rc-num{font-size:1.6rem}
    .form-label{font-size:11px}
    .student-result-card{padding:10px 12px}
    .adm-content-wrap{padding:12px 12px 30px}
}
@media(min-width:769px) and (max-width:1024px){
    .stats-grid{grid-template-columns:repeat(3,1fr)}
    .section-tab{font-size:11px;padding:7px 13px}
}
</style></head><body>
<?php include_once __DIR__ . "/../includes/sidebar.php"; ?>
<div class="dashboard-content">
<div class="da-header">
<div><h1><i class="fas fa-file-signature me-2"></i>Director Admissions &amp; Requirements</h1>
<p>Admissions management &middot; applicant tracking &middot; requirement clearance &middot; student registration</p></div>
<div><span class="badge" style="background:rgba(255,255,255,0.1);color:#fff;font-size:11px"><?=htmlspecialchars($user_name)?></span></div></div>
<?php if(!empty($_SESSION["success"])):?><div class="alert alert-success" style="margin:0 0 14px;border-radius:10px"><?=htmlspecialchars($_SESSION["success"]);unset($_SESSION["success"]);?></div><?php endif;?>
<?php if(!empty($_SESSION["error"])):?><div class="alert alert-danger" style="margin:0 0 14px;border-radius:10px"><?=htmlspecialchars($_SESSION["error"]);unset($_SESSION["error"]);?></div><?php endif;?>
<div class="section-tabs">
<a href="#overview" class="section-tab<?=$view==="overview"?" active":""?>" data-section="overview"><i class="fas fa-chart-pie me-1"></i>Dashboard</a>
<a href="#new_applicant" class="section-tab" data-section="new_applicant"><i class="fas fa-user-plus me-1"></i>New Applicant</a>
<a href="#applicant_records" class="section-tab" data-section="applicant_records"><i class="fas fa-users me-1"></i>Applicant Records</a>
<a href="#student_search" class="section-tab" data-section="student_search"><i class="fas fa-search me-1"></i>Student Search</a>
<a href="#intake_management" class="section-tab" data-section="intake_management"><i class="fas fa-calendar me-1"></i>Intake</a>
<a href="#admission_approvals" class="section-tab" data-section="admission_approvals"><i class="fas fa-check-double me-1"></i>Approvals</a>
<a href="#requirement_portal" class="section-tab" data-section="requirement_portal"><i class="fas fa-list-check me-1"></i>Requirement Portal</a>
<a href="#requirement_clearance" class="section-tab" data-section="requirement_clearance"><i class="fas fa-clipboard-check me-1"></i>Clearance</a>
<a href="#requirement_verification" class="section-tab" data-section="requirement_verification"><i class="fas fa-certificate me-1"></i>Verification</a>
<a href="#requirement_tracking" class="section-tab" data-section="requirement_tracking"><i class="fas fa-tasks me-1"></i>Tracking</a>
<a href="#registration_readiness" class="section-tab" data-section="registration_readiness"><i class="fas fa-flag-checkered me-1"></i>Readiness</a>
<a href="#student_registration" class="section-tab" data-section="student_registration"><i class="fas fa-user-graduate me-1"></i>Registration</a>
<a href="#student_activation" class="section-tab" data-section="student_activation"><i class="fas fa-toggle-on me-1"></i>Activation</a>
<a href="#document_verification" class="section-tab" data-section="document_verification"><i class="fas fa-file-alt me-1"></i>Documents</a>
<a href="#admission_reports" class="section-tab" data-section="admission_reports"><i class="fas fa-chart-bar me-1"></i>Reports</a>
<a href="#intake_statistics" class="section-tab" data-section="intake_statistics"><i class="fas fa-chart-line me-1"></i>Intake Stats</a>
<a href="#applicant_messaging" class="section-tab" data-section="applicant_messaging"><i class="fas fa-envelope me-1"></i>Messaging</a>
<a href="#notifications" class="section-tab" data-section="notifications"><i class="fas fa-bell me-1"></i>Notifications</a>
<a href="#news_publishing" class="section-tab" data-section="news_publishing"><i class="fas fa-newspaper me-1"></i>News</a>
<a href="#requirement_alerts" class="section-tab" data-section="requirement_alerts"><i class="fas fa-exclamation-triangle me-1"></i>Alerts</a>
</div>
<div class="adm-content-wrap">

<!-- OVERVIEW / DASHBOARD -->
<div id="overview" class="dashboard-section<?=$view==='overview'?' active':''?>" data-section="overview">
<div class="stats-grid">
    <div class="stat-card"><div class="stat-icon" style="background:#7c3aed"><i class="fas fa-users"></i></div><div class="stat-content"><h3><?=number_format($total_applicants??0)?></h3><p>Total Applicants</p></div></div>
    <div class="stat-card"><div class="stat-icon" style="background:#2563eb"><i class="fas fa-user-clock"></i></div><div class="stat-content"><h3><?=number_format($new_applicants??0)?></h3><p>New Applicants</p></div></div>
    <div class="stat-card"><div class="stat-icon" style="background:#059669"><i class="fas fa-check-circle"></i></div><div class="stat-content"><h3><?=number_format($approved_applicants??0)?></h3><p>Approved</p></div></div>
    <div class="stat-card"><div class="stat-icon" style="background:#d97706"><i class="fas fa-spinner"></i></div><div class="stat-content"><h3><?=number_format($pending_verify??0)?></h3><p>Under Review</p></div></div>
    <div class="stat-card"><div class="stat-icon" style="background:#0891b2"><i class="fas fa-user-graduate"></i></div><div class="stat-content"><h3><?=number_format($cleared_count??0)?></h3><p>Registered</p></div></div>
    <div class="stat-card"><div class="stat-icon" style="background:#dc2626"><i class="fas fa-times-circle"></i></div><div class="stat-content"><h3><?=number_format($rejected_count??0)?></h3><p>Rejected</p></div></div>
    <div class="stat-card"><div class="stat-icon" style="background:#7c3aed"><i class="fas fa-clipboard-list"></i></div><div class="stat-content"><h3><?=number_format($total_reqs??0)?></h3><p>Requirements</p></div></div>
    <div class="stat-card"><div class="stat-icon" style="background:#65a30d"><i class="fas fa-hourglass-half"></i></div><div class="stat-content"><h3><?=number_format($students_awaiting_reqs??0)?></h3><p>Awaiting Req</p></div></div>
    <div class="stat-card"><div class="stat-icon" style="background:#0891b2"><i class="fas fa-check-double"></i></div><div class="stat-content"><h3><?=number_format($students_fully_cleared??0)?></h3><p>Fully Cleared</p></div></div>
    <div class="stat-card"><div class="stat-icon" style="background:#be185d"><i class="fas fa-exclamation-triangle"></i></div><div class="stat-content"><h3><?=number_format($students_missing_reqs??0)?></h3><p>Missing Req</p></div></div>
</div>
<div class="row g-3">
    <div class="col-md-8"><div class="scard"><div class="sch"><i class="fas fa-chart-bar me-2"></i>Intake Overview</div><div class="scb"><canvas id="intakeChart" height="250"></canvas></div></div></div>
    <div class="col-md-4"><div class="scard"><div class="sch"><i class="fas fa-chart-doughnut me-2"></i>Clearance Status</div><div class="scb"><canvas id="clearanceChart" height="250"></canvas></div></div></div>
</div>
</div>
<!-- NEW APPLICANT -->
<div id="new_applicant" class="dashboard-section" data-section="new_applicant">
<div class="scard"><div class="sch"><i class="fas fa-user-plus me-2"></i>Register New Applicant</div><div class="scb">
<form method="post" class="row g-3">
<input type="hidden" name="action" value="add_applicant">
<div class="col-md-4"><label class="form-label">Full Name <span class="text-danger">*</span></label><input type="text" name="full_name" class="form-control form-control-sm" required></div>
<div class="col-md-2"><label class="form-label">Date of Birth</label><input type="date" name="date_of_birth" class="form-control form-control-sm"></div>
<div class="col-md-2"><label class="form-label">Gender</label><select name="gender" class="form-select form-select-sm"><option>Male</option><option>Female</option></select></div>
<div class="col-md-2"><label class="form-label">Phone</label><input type="text" name="phone" class="form-control form-control-sm"></div>
<div class="col-md-2"><label class="form-label">Email</label><input type="email" name="email" class="form-control form-control-sm"></div>
<div class="col-md-3"><label class="form-label">Program</label><select name="program_id" class="form-select form-select-sm"><option value="">Select Program</option><?php foreach($programs_list as $p):?><option value="<?=$p['id']?>"><?=htmlspecialchars($p['program_name'])?></option><?php endforeach;?></select></div>
<div class="col-md-2"><label class="form-label">Intake Period</label><select name="intake" class="form-select form-select-sm"><option>January</option><option>May</option><option>August</option></select></div>
<div class="col-md-3"><label class="form-label">Guardian Name</label><input type="text" name="guardian_name" class="form-control form-control-sm"></div>
<div class="col-md-2"><label class="form-label">Guardian Phone</label><input type="text" name="guardian_phone" class="form-control form-control-sm"></div>
<div class="col-md-2"><label class="form-label">Guardian Relationship</label><input type="text" name="guardian_relationship" class="form-control form-control-sm"></div>
<div class="col-md-3"><label class="form-label">Address</label><textarea name="address" class="form-control form-control-sm" rows="1"></textarea></div>
<div class="col-12"><button type="submit" class="btn btn-sm" style="background:#7c3aed;color:#fff;border:none;border-radius:6px"><i class="fas fa-save me-1"></i>Save Applicant</button></div>
</form>
</div></div>
</div>

<!-- APPLICANT RECORDS -->
<div id="applicant_records" class="dashboard-section" data-section="applicant_records">
<div class="scard"><div class="sch"><i class="fas fa-users me-2"></i>Applicant Records</div><div class="scb">
<div class="filter-group">
<input type="text" id="applicantSearch" placeholder="Search applicants..." style="max-width:300px">
<select id="filterStatus" style="width:auto"><option value="">All</option><option value="New Applicant">New</option><option value="Under Review">Under Review</option><option value="Approved">Approved</option><option value="Rejected">Rejected</option><option value="Registered">Registered</option></select>
<select id="filterIntake" style="width:auto"><option value="">All Intake</option><option value="January">January</option><option value="May">May</option><option value="August">August</option></select>
</div>
<div class="table-responsive"><table class="table table-sm"><thead><tr><th>App No</th><th>Name</th><th>Phone</th><th>Program</th><th>Intake</th><th>Status</th><th>Actions</th></tr></thead><tbody id="applicantList">
<?php if (!empty($applicants)): foreach ($applicants as $a): ?>
<tr>
<td><?=htmlspecialchars($a['application_number']??'')?></td>
<td><?=htmlspecialchars($a['full_name'])?></td>
<td><?=htmlspecialchars($a['phone']??'')?></td>
<td><?=htmlspecialchars($a['program_name']??'')?></td>
<td><?=htmlspecialchars($a['intake']??'')?></td>
<td><span class="badge badge-<?=$a['status']==='Approved'?'success':($a['status']==='Rejected'?'danger':($a['status']==='Registered'?'info':'primary'))?>"><?=htmlspecialchars($a['status'])?></span></td>
<td>
<button class="btn btn-sm btn-outline-primary py-0 px-2" style="font-size:10px" onclick='editApplicant(<?=$a['id']?>)'><i class="fas fa-edit"></i></button>
<button class="btn btn-sm btn-outline-<?=$a['status']==='Approved'?'secondary':'success'?> py-0 px-2" style="font-size:10px" onclick='approveApplicant(<?=$a['id']?>)' <?=$a['status']==='Approved'||$a['status']==='Registered'?'disabled':''?>><i class="fas fa-check"></i></button>
<button class="btn btn-sm btn-outline-danger py-0 px-2" style="font-size:10px" onclick='rejectApplicant(<?=$a['id']?>)' <?=$a['status']==='Approved'||$a['status']==='Registered'?'disabled':''?>><i class="fas fa-times"></i></button>
<button class="btn btn-sm btn-outline-info py-0 px-2" style="font-size:10px" onclick='convertToStudent(<?=$a['id']?>)' <?=$a['status']!=='Approved'?'disabled':''?>><i class="fas fa-user-graduate"></i></button>
<button class="btn btn-sm btn-outline-danger py-0 px-2" style="font-size:10px" onclick='deleteApplicant(<?=$a['id']?>)' <?=$a['status']==='Registered'?'disabled':''?>><i class="fas fa-trash"></i></button>
</td>
</tr>
<?php endforeach; else: ?>
<tr><td colspan="7" class="text-muted text-center">No applicants found</td></tr>
<?php endif; ?>
</tbody></table></div>
</div></div>
</div>

<!-- STUDENT SEARCH -->
<div id="student_search" class="dashboard-section" data-section="student_search">
<div class="scard"><div class="sch"><i class="fas fa-search me-2"></i>Student Search</div><div class="scb">
<div class="search-filters-wrap">
<div class="filter-group">
<input type="text" id="ssQuery" placeholder="Search by name, email, phone..." style="flex:1;min-width:160px">
<input type="text" id="ssAdmissionNo" placeholder="Admission No" style="width:130px">
<input type="text" id="ssRegNo" placeholder="Reg No" style="width:130px">
<input type="text" id="ssPhone" placeholder="Phone" style="width:120px">
<input type="text" id="ssNationalId" placeholder="National ID" style="width:120px">
</div>
<div class="filter-group">
<select id="ssProgram" style="width:140px"><option value="">All Programs</option><?php foreach($programs_list as $p):?><option value="<?=htmlspecialchars($p['program_name'])?>"><?=htmlspecialchars($p['program_name'])?></option><?php endforeach;?></select>
<select id="ssIntake" style="width:100px"><option value="">Intake</option><option>January</option><option>May</option><option>August</option></select>
<input type="text" id="ssYear" placeholder="Year" style="width:80px">
<select id="ssStatus" style="width:110px"><option value="">All Status</option><option>Active</option><option>Inactive</option><option>Suspended</option><option>Graduated</option></select>
<button class="btn btn-sm" style="background:#7c3aed;color:#fff;border:none;border-radius:6px" onclick="searchStudents(1)"><i class="fas fa-search me-1"></i>Search</button>
<button class="btn btn-sm btn-outline-secondary" onclick="clearSearch()"><i class="fas fa-undo me-1"></i>Clear</button>
</div>
</div>
<div class="d-flex justify-content-between align-items-center mb-2"><small id="ssCount">0 results</small></div>
<div id="ssResults"><div class="empty-state"><i class="fas fa-search"></i><div>Use the filters above to search for students. Results appear here.</div></div></div>
<div id="ssPagination" class="pagination"></div>
</div></div>
</div>

<!-- INTAKE MANAGEMENT -->
<div id="intake_management" class="dashboard-section" data-section="intake_management">
<div class="scard"><div class="sch"><i class="fas fa-calendar me-2"></i>Intake Management</div><div class="scb">
<div class="row g-3">
<div class="col-md-6"><h6 class="fw-semibold">Intake Distribution</h6><canvas id="intakeChart2" height="250"></canvas></div>
<div class="col-md-6"><h6 class="fw-semibold">Intake Records</h6>
<table class="table table-sm"><thead><tr><th>Intake</th><th>Count</th></tr></thead><tbody>
<?php if(!empty($intake_stats)): foreach($intake_stats as $is):?><tr><td><?=htmlspecialchars($is['intake'])?></td><td><?=(int)$is['c']?></td></tr><?php endforeach; endif;?>
</tbody></table></div>
</div></div></div>
</div>

<!-- ADMISSION APPROVALS -->
<div id="admission_approvals" class="dashboard-section" data-section="admission_approvals">
<div class="scard"><div class="sch"><i class="fas fa-check-double me-2"></i>Admission Approvals</div><div class="scb">
<p class="text-muted small">Review and approve/reject applicants.</p>
<div class="table-responsive"><table class="table table-sm"><thead><tr><th>Applicant</th><th>App No</th><th>Program</th><th>Intake</th><th>Status</th><th>Actions</th></tr></thead><tbody id="approvalList">
<?php if(!empty($pending_applicants)): foreach($pending_applicants as $a): ?>
<tr><td><?=htmlspecialchars($a['full_name'])?></td><td><?=htmlspecialchars($a['application_number'])?></td><td><?=htmlspecialchars($a['program_name']??'')?></td><td><?=htmlspecialchars($a['intake']??'')?></td>
<td><span class="badge badge-<?=$a['status']==='Approved'?'success':($a['status']==='Rejected'?'danger':'warning')?>"><?=htmlspecialchars($a['status'])?></span></td>
<td>
<button class="btn btn-sm" style="background:#059669;color:#fff;border:none;border-radius:4px;font-size:10px" onclick='approveApplicant(<?=$a['id']?>)' <?=$a['status']==='Approved'||$a['status']==='Registered'?'disabled':''?>><i class="fas fa-check me-1"></i>Approve</button>
<button class="btn btn-sm" style="background:#dc2626;color:#fff;border:none;border-radius:4px;font-size:10px" onclick='rejectApplicant(<?=$a['id']?>)' <?=$a['status']==='Approved'||$a['status']==='Registered'?'disabled':''?>><i class="fas fa-times me-1"></i>Reject</button>
<button class="btn btn-sm btn-outline-primary py-0 px-2" style="font-size:10px" onclick='editApplicant(<?=$a['id']?>)'><i class="fas fa-edit"></i></button>
</td></tr>
<?php endforeach; else: ?><tr><td colspan="6" class="text-muted text-center">No pending applicants</td></tr><?php endif; ?>
</tbody></table></div>
</div></div>
</div>
<!-- REQUIREMENT PORTAL -->
<div id="requirement_portal" class="dashboard-section" data-section="requirement_portal">
<div class="scard"><div class="sch"><i class="fas fa-list-check me-2"></i>Requirement Portal</div><div class="scb">
<div class="row g-3">
<div class="col-md-7">
<h6 class="fw-semibold mb-2">All Requirements</h6>
<div class="table-responsive"><table class="table table-sm"><thead><tr><th>#</th><th>Requirement</th><th>Type</th><th>Mandatory</th><th>Actions</th></tr></thead><tbody>
<?php foreach($requirements as $r): ?>
<tr><td><?=(int)$r['id']?></td><td><?=htmlspecialchars($r['requirement_name'])?></td><td><?=htmlspecialchars($r['type']??'Document')?></td>
<td><?=$r['is_mandatory']?'<span class="badge bg-danger">Yes</span>':'<span class="badge bg-secondary">No</span>'?></td>
<td>
<button class="btn btn-sm btn-outline-primary py-0 px-2" style="font-size:10px" onclick='toggleRequirement(<?=(int)$r['id']?>)'><i class="fas <?=$r['is_active']?'fa-toggle-on text-success':'fa-toggle-off text-muted'?>"></i></button>
<button class="btn btn-sm btn-outline-success py-0 px-2" style="font-size:10px" onclick='receiveRequirement(<?=(int)$r['id']?>)' title="Mark all submitted"><i class="fas fa-check-double"></i></button>
</td></tr>
<?php endforeach; ?>
</tbody></table></div>
</div>
<div class="col-md-5">
<h6 class="fw-semibold mb-2">Quick Stats</h6>
<div class="stats-grid" style="grid-template-columns:1fr 1fr;gap:8px">
<div class="stat-card" style="padding:10px"><div class="stat-icon" style="background:#7c3aed;width:32px;height:32px;font-size:12px"><i class="fas fa-list"></i></div><div class="stat-content"><h3 style="font-size:16px"><?=$total_reqs??0?></h3><p style="font-size:10px">Total</p></div></div>
<div class="stat-card" style="padding:10px"><div class="stat-icon" style="background:#059669;width:32px;height:32px;font-size:12px"><i class="fas fa-check-circle"></i></div><div class="stat-content"><h3 style="font-size:16px"><?=$active_reqs??0?></h3><p style="font-size:10px">Active</p></div></div>
</div>
<div class="mt-3">
<button class="btn btn-sm" style="background:#7c3aed;color:#fff;border:none;border-radius:6px" onclick='resetAllRequirements()'><i class="fas fa-undo me-1"></i>Reset All Requirements</button>
<button class="btn btn-sm btn-outline-success" onclick="alert('Already seeded with defaults.')"><i class="fas fa-database me-1"></i>Re-seed Defaults</button>
</div>
</div>
</div>
</div></div>
</div>

<!-- REQUIREMENT CLEARANCE -->
<div id="requirement_clearance" class="dashboard-section" data-section="requirement_clearance">
<div class="scard"><div class="sch"><i class="fas fa-clipboard-check me-2"></i>Requirement Clearance</div><div class="scb">
<div class="filter-group">
<select id="clrProgram" style="width:auto" onchange="loadClearance()"><option value="">All Programs</option><?php foreach($programs_list as $p):?><option value="<?=$p['id']?>"><?=htmlspecialchars($p['program_name'])?></option><?php endforeach;?></select>
<select id="clrIntake" style="width:auto" onchange="loadClearance()"><option value="">All Intake</option><option>January</option><option>May</option><option>August</option></select>
</div>
<div class="table-responsive"><table class="table table-sm"><thead><tr><th>App No</th><th>Name</th><th>Program</th><th>Intake</th><th>Progress</th><th>Actions</th></tr></thead><tbody id="clearanceList">
<tr><td colspan="6" class="text-muted text-center">Loading...</td></tr>
</tbody></table></div>
</div></div>
</div>

<!-- REQUIREMENT VERIFICATION -->
<div id="requirement_verification" class="dashboard-section" data-section="requirement_verification">
<div class="scard"><div class="sch"><i class="fas fa-certificate me-2"></i>Requirement Verification</div><div class="scb">
<div class="table-responsive"><table class="table table-sm"><thead><tr><th>Applicant</th><th>Requirement</th><th>Status</th><th>Updated</th><th>Action</th></tr></thead><tbody id="verificationList">
<tr><td colspan="5" class="text-muted text-center">Loading...</td></tr>
</tbody></table></div>
</div></div>
</div>

<!-- REQUIREMENT TRACKING -->
<div id="requirement_tracking" class="dashboard-section" data-section="requirement_tracking">
<div class="scard"><div class="sch"><i class="fas fa-tasks me-2"></i>Requirement Tracking</div><div class="scb">
<div class="filter-group">
<input type="text" id="trkSearch" placeholder="Search applicant..." style="max-width:200px" onkeyup="loadReqTracking()">
<select id="trkStatus" style="width:auto" onchange="loadReqTracking()"><option value="">All</option><option>Submitted</option><option>Verified</option><option>Pending</option><option>Rejected</option></select>
</div>
<div class="table-responsive"><table class="table table-sm"><thead><tr><th>Applicant</th><th>Requirement</th><th>Status</th><th>Remarks</th><th>Updated</th></tr></thead><tbody id="trackingList">
<tr><td colspan="5" class="text-muted text-center">Loading...</td></tr>
</tbody></table></div>
</div></div>
</div>

<!-- REGISTRATION READINESS -->
<div id="registration_readiness" class="dashboard-section" data-section="registration_readiness">
<div class="scard"><div class="sch"><i class="fas fa-flag-checkered me-2"></i>Registration Readiness</div><div class="scb">
<div class="table-responsive"><table class="table table-sm"><thead><tr><th>Applicant</th><th>Program</th><th>Requirements Met</th><th>Status</th><th>Actions</th></tr></thead><tbody id="readinessList">
<tr><td colspan="5" class="text-muted text-center">Loading...</td></tr>
</tbody></table></div>
</div></div>
</div>

<!-- STUDENT REGISTRATION -->
<div id="student_registration" class="dashboard-section" data-section="student_registration">
<div class="scard"><div class="sch"><i class="fas fa-user-graduate me-2"></i>Student Registration</div><div class="scb">
<div class="filter-group">
<input type="text" id="regSearch" placeholder="Search applicant..." style="max-width:200px" onkeyup="loadRegList()">
<select id="regIntake" style="width:auto" onchange="loadRegList()"><option value="">All Intake</option><option>January</option><option>May</option><option>August</option></select>
</div>
<div class="table-responsive"><table class="table table-sm"><thead><tr><th>App No</th><th>Name</th><th>Program</th><th>Intake</th><th>Registered</th><th>Action</th></tr></thead><tbody id="regList">
<tr><td colspan="6" class="text-muted text-center">Loading...</td></tr>
</tbody></table></div>
</div></div>
</div>

<!-- STUDENT ACTIVATION -->
<div id="student_activation" class="dashboard-section" data-section="student_activation">
<div class="scard"><div class="sch"><i class="fas fa-toggle-on me-2"></i>Student Activation</div><div class="scb">
<div class="table-responsive"><table class="table table-sm"><thead><tr><th>Admission No</th><th>Name</th><th>Program</th><th>Status</th><th>Action</th></tr></thead><tbody id="activationList">
<tr><td colspan="5" class="text-muted text-center">Loading...</td></tr>
</tbody></table></div>
</div></div>
</div>

<!-- DOCUMENT VERIFICATION -->
<div id="document_verification" class="dashboard-section" data-section="document_verification">
<div class="scard"><div class="sch"><i class="fas fa-file-alt me-2"></i>Document Verification</div><div class="scb">
<div class="table-responsive"><table class="table table-sm"><thead><tr><th>Applicant</th><th>Document</th><th>Status</th><th>Uploaded</th><th>Actions</th></tr></thead><tbody id="docList">
<tr><td colspan="5" class="text-muted text-center">Loading...</td></tr>
</tbody></table></div>
</div></div>
</div>
<!-- ADMISSION REPORTS -->
<div id="admission_reports" class="dashboard-section" data-section="admission_reports">
<div class="scard"><div class="sch"><i class="fas fa-chart-bar me-2"></i>Admission Reports</div><div class="scb">
<div class="row g-3 mb-3">
<div class="col-md-3"><label class="form-label">From</label><input type="date" id="rptFrom" class="form-control form-control-sm"></div>
<div class="col-md-3"><label class="form-label">To</label><input type="date" id="rptTo" class="form-control form-control-sm"></div>
<div class="col-md-2"><label class="form-label">&nbsp;</label><br><button class="btn btn-sm" style="background:#7c3aed;color:#fff;border:none;border-radius:6px" onclick="loadReport()"><i class="fas fa-sync me-1"></i>Load</button></div>
<div class="col-md-2"><label class="form-label">&nbsp;</label><br><button class="btn btn-sm btn-success" onclick="exportCSV()"><i class="fas fa-file-csv me-1"></i>Export CSV</button></div>
<div class="col-md-2"><label class="form-label">&nbsp;</label><br><form method="get" style="display:inline" target="_blank"><input type="hidden" name="report" value="applications"><button class="btn btn-sm btn-outline-secondary" type="submit"><i class="fas fa-print me-1"></i>Print</button></form></div>
</div>
<div class="table-responsive"><table class="table table-sm"><thead><tr><th>Date</th><th>Applicant</th><th>Program</th><th>Status</th><th>Intake</th></tr></thead><tbody id="reportTable">
<tr><td colspan="5" class="text-muted text-center">Select date range and load</td></tr>
</tbody></table></div>
</div></div>
</div>

<!-- INTAKE STATISTICS -->
<div id="intake_statistics" class="dashboard-section" data-section="intake_statistics">
<div class="scard"><div class="sch"><i class="fas fa-chart-line me-2"></i>Intake Statistics</div><div class="scb">
<div class="row g-3">
<div class="col-md-6"><canvas id="intakeBarChart" height="250"></canvas></div>
<div class="col-md-6"><canvas id="programPieChart" height="250"></canvas></div>
</div>
<div class="table-responsive mt-3"><table class="table table-sm"><thead><tr><th>Metric</th><th>Value</th></tr></thead><tbody>
<tr><td>Total Applicants</td><td><?=$total_applicants??0?></td></tr>
<tr><td>Approved</td><td><?=$approved_applicants??0?></td></tr>
<tr><td>Rejected</td><td><?=$rejected_count??0?></td></tr>
<tr><td>Under Review</td><td><?=$pending_verify??0?></td></tr>
<tr><td>Registered</td><td><?=$cleared_count??0?></td></tr>
</tbody></table></div>
</div></div>
</div>

<!-- APPLICANT MESSAGING -->
<div id="applicant_messaging" class="dashboard-section" data-section="applicant_messaging">
<div class="scard"><div class="sch"><i class="fas fa-envelope me-2"></i>Applicant Messaging</div><div class="scb">
<div class="row g-3">
<div class="col-md-4">
<select id="msgApplicant" class="form-select form-select-sm" style="max-width:100%"><option value="">Select applicant...</option>
<?php if(!empty($applicants)): foreach($applicants as $a):?><option value="<?=$a['id']?>"><?=htmlspecialchars($a['full_name'])?> (<?=htmlspecialchars($a['application_number']??'')?>)</option><?php endforeach; endif;?>
</select>
<textarea id="msgText" class="form-control form-control-sm mt-2" rows="3" placeholder="Type message..."></textarea>
<button class="btn btn-sm mt-2" style="background:#7c3aed;color:#fff;border:none;border-radius:6px" onclick="sendMessage()"><i class="fas fa-paper-plane me-1"></i>Send</button>
</div>
<div class="col-md-8">
<h6 class="fw-semibold">Conversation</h6>
<div id="msgConversation" style="max-height:300px;overflow-y:auto;background:#f8f9fa;padding:10px;border-radius:8px">
<p class="text-muted text-center">Select an applicant to view messages</p>
</div>
</div>
</div>
</div></div>
</div>

<!-- NOTIFICATIONS -->
<div id="notifications" class="dashboard-section" data-section="notifications">
<div class="scard"><div class="sch"><i class="fas fa-bell me-2"></i>Notifications</div><div class="scb">
<div class="d-flex justify-content-between mb-2">
<select id="notifType" class="form-select form-select-sm" style="width:auto" onchange="loadNotifications()"><option value="">All Types</option><option>info</option><option>success</option><option>warning</option><option>danger</option></select>
<button class="btn btn-sm btn-outline-danger" id="clearAllNotifBtn"><i class="fas fa-trash me-1"></i>Clear All</button>
</div>
<div id="notificationList"><p class="text-muted text-center">Loading...</p></div>
</div></div>
</div>

<!-- NEWS PUBLISHING -->
<div id="news_publishing" class="dashboard-section" data-section="news_publishing">
<div class="scard"><div class="sch"><i class="fas fa-newspaper me-2"></i>Publish News to Website</div><div class="scb">
<div class="row g-3 mb-3">
<div class="col-md-8">
<label class="form-label fw-semibold">News Title <span class="text-danger">*</span></label>
<input type="text" id="newsTitle" class="form-control" placeholder="Enter news title...">
</div>
<div class="col-md-4">
<label class="form-label fw-semibold">Status</label>
<select id="newsStatus" class="form-select">
<option value="draft">Draft</option>
<option value="published" selected>Published</option>
</select>
</div>
<div class="col-12">
<label class="form-label fw-semibold">Excerpt / Summary</label>
<textarea id="newsExcerpt" class="form-control" rows="2" placeholder="Brief summary for news card..."></textarea>
</div>
<div class="col-12">
<label class="form-label fw-semibold">Content <span class="text-danger">*</span></label>
<textarea id="newsContent" class="form-control" rows="8" placeholder="Write the full news article here..."></textarea>
</div>
<div class="col-md-6">
<label class="form-label fw-semibold">Featured Image</label>
<input type="file" id="newsImage" class="form-control" accept="image/*">
</div>
<div class="col-md-6 d-flex align-items-end">
<button type="button" class="btn btn-sm me-2" style="background:#7c3aed;color:#fff" onclick="publishNews()"><i class="fas fa-paper-plane me-1"></i>Publish</button>
<button type="button" class="btn btn-sm btn-outline-secondary" onclick="saveDraftNews()"><i class="fas fa-save me-1"></i>Save Draft</button>
</div>
</div>
<hr>
<h6 class="mb-3"><i class="fas fa-list me-1"></i>Published News</h6>
<div id="newsList"><p class="text-muted text-center">Loading...</p></div>
</div></div>
</div>

<!-- REQUIREMENT ALERTS -->
<div id="requirement_alerts" class="dashboard-section" data-section="requirement_alerts">
<div class="scard"><div class="sch"><i class="fas fa-exclamation-triangle me-2"></i>Requirement Alerts</div><div class="scb">
<div id="alertList"><p class="text-muted text-center">Loading...</p></div>
</div></div>
</div>

</div><!-- end adm-content-wrap -->
</div><!-- end dashboard-content -->
<!-- MODALS -->
<div class="modal fade" id="studentProfileModal"><div class="modal-dialog modal-lg"><div class="modal-content"><div class="modal-header"><h5 class="modal-title"><i class="fas fa-user-graduate me-2"></i>Student Profile</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body" id="studentProfileBody"><p class="text-muted">Loading...</p></div></div></div></div>

<div class="modal fade" id="editApplicantModal"><div class="modal-dialog modal-lg"><div class="modal-content"><div class="modal-header"><h5 class="modal-title"><i class="fas fa-edit me-2"></i>Edit Applicant</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><form id="editApplicantForm"><input type="hidden" name="id" id="editApplicantId">
<div class="row g-2">
<div class="col-md-6"><div class="mb-2"><label class="form-label">Full Name</label><input type="text" name="full_name" id="editName" class="form-control form-control-sm"></div></div>
<div class="col-md-3"><div class="mb-2"><label class="form-label">Date of Birth</label><input type="date" name="date_of_birth" id="editDob" class="form-control form-control-sm"></div></div>
<div class="col-md-3"><div class="mb-2"><label class="form-label">Gender</label><select name="gender" id="editGender" class="form-select form-select-sm"><option>Male</option><option>Female</option><option>Other</option></select></div></div>
<div class="col-md-4"><div class="mb-2"><label class="form-label">Phone</label><input type="text" name="phone" id="editPhone" class="form-control form-control-sm"></div></div>
<div class="col-md-4"><div class="mb-2"><label class="form-label">Email</label><input type="email" name="email" id="editEmail" class="form-control form-control-sm"></div></div>
<div class="col-md-4"><div class="mb-2"><label class="form-label">Program</label><select name="program_id" id="editProgram" class="form-select form-select-sm"><?php foreach($programs_list as $p):?><option value="<?=$p['id']?>"><?=htmlspecialchars($p['program_name'])?></option><?php endforeach;?></select></div></div>
<div class="col-md-4"><div class="mb-2"><label class="form-label">Intake</label><select name="intake" id="editIntake" class="form-select form-select-sm"><option>January</option><option>May</option><option>August</option></select></div></div>
<div class="col-md-4"><div class="mb-2"><label class="form-label">Guardian Name</label><input type="text" name="guardian_name" id="editGuardian" class="form-control form-control-sm"></div></div>
<div class="col-md-2"><div class="mb-2"><label class="form-label">Guardian Phone</label><input type="text" name="guardian_phone" id="editGuardianPhone" class="form-control form-control-sm"></div></div>
<div class="col-md-2"><div class="mb-2"><label class="form-label">Relationship</label><input type="text" name="guardian_relationship" id="editGuardianRel" class="form-control form-control-sm"></div></div>
<div class="col-md-4"><div class="mb-2"><label class="form-label">Status</label><select name="status" id="editStatus" class="form-select form-select-sm"><option>New Applicant</option><option>Under Review</option><option>Approved</option><option>Rejected</option><option>Registered</option></select></div></div>
<div class="col-md-12"><div class="mb-2"><label class="form-label">Address</label><textarea name="address" id="editAddress" class="form-control form-control-sm" rows="1"></textarea></div></div>
</div>
</form></div><div class="modal-footer"><button class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancel</button><button class="btn btn-sm" style="background:#7c3aed;color:#fff;border:none;border-radius:6px" onclick="saveEditApplicant()"><i class="fas fa-save me-1"></i>Save</button></div></div></div></div>

<div class="modal fade" id="requirementModal"><div class="modal-dialog modal-lg"><div class="modal-content"><div class="modal-header"><h5 class="modal-title"><i class="fas fa-clipboard-list me-2"></i>Applicant Requirements</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body" id="requirementBody"><p class="text-muted">Loading...</p></div></div></div></div>

<div class="modal fade" id="uploadDocModal"><div class="modal-dialog"><div class="modal-content"><div class="modal-header"><h5 class="modal-title"><i class="fas fa-upload me-2"></i>Upload Document</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body">
<input type="hidden" id="uploadDocReqId">
<input type="hidden" id="uploadDocAppId">
<div class="mb-2"><label class="form-label">Document File</label><input type="file" id="uploadDocFile" class="form-control form-control-sm"></div>
<div class="mb-2"><label class="form-label">Notes</label><textarea id="uploadDocNotes" class="form-control form-control-sm" rows="2"></textarea></div>
</div><div class="modal-footer"><button class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancel</button><button class="btn btn-sm" style="background:#7c3aed;color:#fff;border:none;border-radius:6px" onclick="submitDocument()"><i class="fas fa-upload me-1"></i>Upload</button></div></div></div></div>
<script>
jQuery(function($) {
  'use strict';

  /* ── Helper: HTML escape ── */
  function htmlEscape(s) {
    return $('<span>').text(s).html();
  }

  /* ── Debounce ── */
  function debounce(fn, ms) {
    var t;
    return function() { clearTimeout(t); t = setTimeout(fn, ms); };
  }

  /* ── Toast notification ── */
  function showToast(type, msg) {
    var bg = { success: '#059669', danger: '#dc2626', warning: '#d97706', info: '#2563eb' };
    var el = $('<div>').css({
      position: 'fixed', top: '20px', right: '20px',
      background: bg[type] || '#333', color: '#fff',
      padding: '10px 20px', borderRadius: '8px',
      zIndex: 99999, fontSize: '13px'
    }).text(msg);
    $('body').append(el);
    setTimeout(function() { el.fadeOut(function() { el.remove(); }); }, 3000);
  }

  /* ── Section switching ── */
  function switchSection(id) {
    $('.dashboard-section').removeClass('active');
    $('#' + id).addClass('active');
    $('.section-tab').removeClass('active');
    $('.section-tab[data-section="' + id + '"]').addClass('active');
    window.location.hash = '#' + id;
  }

  /* ── Section tab click handlers ── */
  $('.section-tab').on('click', function(e) {
    e.preventDefault();
    var section = $(this).data('section');
    if (section) switchSection(section);
  });

  /* ── Init section from hash ── */
  (function initSection() {
    var h = window.location.hash.replace('#', '');
    if (h && $('#' + h).length) { switchSection(h); }
    else { switchSection('overview'); }
  })();

  /* ── Applicant Records ── */
  function loadApplicantRecords() {
    $.post('', {
      action: 'search_applicants', q: $('#applicantSearch').val(),
      status: $('#filterStatus').val(), intake: $('#filterIntake').val()
    }, function(r) {
      var h = '';
      if (r.data && r.data.length) {
        r.data.forEach(function(a) {
          var statusClass = a.status === 'Approved' ? 'success' : a.status === 'Rejected' ? 'danger' : a.status === 'Registered' ? 'info' : 'primary';
          var disabledApprove = (a.status === 'Approved' || a.status === 'Registered') ? 'disabled' : '';
          var disabledConvert = a.status !== 'Approved' ? 'disabled' : '';
          var disabledDelete = a.status === 'Registered' ? 'disabled' : '';
          h += '<tr><td>' + htmlEscape(a.application_number || '') + '</td><td>' + htmlEscape(a.full_name) + '</td><td>' + htmlEscape(a.phone || '') + '</td><td>' + htmlEscape(a.program_name || '') + '</td><td>' + htmlEscape(a.intake || '') + '</td><td><span class="badge badge-' + statusClass + '">' + htmlEscape(a.status) + '</span></td><td>' +
            '<button class="btn btn-sm btn-outline-primary py-0 px-2" style="font-size:10px" data-action="edit" data-id="' + a.id + '"><i class="fas fa-edit"></i></button>' +
            '<button class="btn btn-sm btn-outline-' + (a.status === 'Approved' ? 'secondary' : 'success') + ' py-0 px-2" style="font-size:10px" data-action="approve" data-id="' + a.id + '" ' + disabledApprove + '><i class="fas fa-check"></i></button>' +
            '<button class="btn btn-sm btn-outline-danger py-0 px-2" style="font-size:10px" data-action="reject" data-id="' + a.id + '" ' + disabledApprove + '><i class="fas fa-times"></i></button>' +
            '<button class="btn btn-sm btn-outline-info py-0 px-2" style="font-size:10px" data-action="convert" data-id="' + a.id + '" ' + disabledConvert + '><i class="fas fa-user-graduate"></i></button>' +
            '<button class="btn btn-sm btn-outline-danger py-0 px-2" style="font-size:10px" data-action="delete" data-id="' + a.id + '" ' + disabledDelete + '><i class="fas fa-trash"></i></button></td></tr>';
        });
      } else { h = '<tr><td colspan="7" class="text-muted text-center">No applicants found</td></tr>'; }
      $('#applicantList').html(h);
    }, 'json').fail(function() { $('#applicantList').html('<tr><td colspan="7" class="text-danger text-center">Error loading applicants</td></tr>'); });
  }
  $('#applicantSearch').on('input', debounce(loadApplicantRecords, 300));
  $('#filterStatus, #filterIntake').on('change', loadApplicantRecords);
  window.applyFilter = loadApplicantRecords;
  window.loadApplicantRecords = loadApplicantRecords;

  /* ── Student Search ── */
  window.searchStudents = function(page) {
    page = page || 1;
    $.post('', { action: 'search_students', q: $('#ssQuery').val(), admission_no: $('#ssAdmissionNo').val(), reg_no: $('#ssRegNo').val(), phone: $('#ssPhone').val(), national_id: $('#ssNationalId').val(), program: $('#ssProgram').val(), intake: $('#ssIntake').val(), year: $('#ssYear').val(), status: $('#ssStatus').val(), page: page, limit: 50 },
    function(r) {
      var h = '';
      if (r.data && r.data.length) {
        r.data.forEach(function(s) {
          var name = s.full_name || (s.firstname + ' ' + s.lastname) || '';
          var statusClass = s.status === 'Active' ? 'success' : s.status === 'Inactive' ? 'secondary' : s.status === 'Suspended' ? 'warning' : 'info';
          h += '<tr><td>' + htmlEscape(s.admission_no || s.application_number || '') + '</td><td>' + htmlEscape(name) + '</td><td>' + htmlEscape(s.program_name || s.program || '') + '</td><td>' + htmlEscape(s.phone || '') + '</td><td><span class="badge bg-' + statusClass + '">' + htmlEscape(s.status || '') + '</span></td><td><button class="btn btn-sm btn-outline-info py-0 px-2" style="font-size:10px" data-action="viewProfile" data-id="' + (s.student_id || s.id || 0) + '"><i class="fas fa-eye"></i></button></td></tr>';
        });
      } else { h = '<tr><td colspan="6" class="text-muted text-center">No results found</td></tr>'; }
      $('#ssResults').html('<table class="table table-sm"><thead><tr><th>ID/Adm No</th><th>Name</th><th>Program</th><th>Phone</th><th>Status</th><th>Actions</th></tr></thead><tbody>' + h + '</tbody></table>');
      $('#ssCount').text((r.total || 0) + ' results');
      if (r.total > 50) {
        var ph = '';
        for (var i = 1; i <= Math.ceil(r.total / 50); i++) {
          ph += '<button class="btn btn-sm ' + (i === page ? 'btn-primary' : 'btn-outline-primary') + ' py-0 px-2 me-1" data-action="searchPage" data-page="' + i + '">' + i + '</button>';
        }
        $('#ssPagination').html(ph);
      } else { $('#ssPagination').empty(); }
    }, 'json').fail(function() { $('#ssResults').html('<p class="text-danger">Error searching students</p>'); });
  };

  window.clearSearch = function() {
    $('#ssQuery,#ssAdmissionNo,#ssRegNo,#ssPhone,#ssNationalId,#ssProgram,#ssIntake,#ssYear,#ssStatus').val('');
    $('#ssResults').html('<div class="empty-state"><i class="fas fa-search"></i><div>Use the filters above to search for students. Results appear here.</div></div>');
    $('#ssCount').text('0 results');
    $('#ssPagination').empty();
  };

  /* ── Student Profile ── */
  window.viewStudentProfile = function(id) {
    $.post('', { action: 'get_student_profile', student_id: id }, function(r) {
      if (r.success) {
        var d = r.data;
        var reqsHtml = '';
        if (d.requirements && d.requirements.length) {
          d.requirements.forEach(function(req) {
            var cls = req.status === 'Verified' ? 'success' : req.status === 'Submitted' ? 'primary' : req.status === 'Rejected' ? 'danger' : 'secondary';
            reqsHtml += '<tr><td>' + htmlEscape(req.requirement_name) + '</td><td><span class="badge bg-' + cls + '">' + htmlEscape(req.status || 'Pending') + '</span></td></tr>';
          });
        }
        var docsHtml = '';
        if (d.documents && d.documents.length) {
          d.documents.forEach(function(doc) {
            var cls2 = doc.verification_status === 'Verified' ? 'success' : doc.verification_status === 'Rejected' ? 'danger' : 'warning';
            docsHtml += '<div class="d-flex justify-content-between align-items-center mb-1 p-1" style="background:#f8fafc;border-radius:4px"><span class="small">' + htmlEscape(doc.document_title || doc.file_name || 'Document') + '</span><span class="badge bg-' + cls2 + '">' + htmlEscape(doc.verification_status || 'Pending') + '</span></div>';
          });
        } else { docsHtml = '<p class="text-muted small mb-0">No documents uploaded</p>'; }
        var h = '<div class="row g-3"><div class="col-md-6"><div class="scard"><div class="sch"><i class="fas fa-user me-2"></i>Personal Information</div><div class="scb"><dl><dt>Name</dt><dd>' + htmlEscape(d.full_name || '') + '</dd><dt>Admission No</dt><dd>' + htmlEscape(d.admission_no || '') + '</dd><dt>Phone</dt><dd>' + htmlEscape(d.phone || '') + '</dd><dt>Email</dt><dd>' + htmlEscape(d.email || '') + '</dd><dt>DOB</dt><dd>' + htmlEscape(d.date_of_birth || '') + '</dd><dt>National ID</dt><dd>' + htmlEscape(d.national_id || '') + '</dd></dl></div></div></div>' +
          '<div class="col-md-6"><div class="scard"><div class="sch"><i class="fas fa-graduation-cap me-2"></i>Admission Info</div><div class="scb"><dl><dt>Program</dt><dd>' + htmlEscape(d.program_name || (d.info && d.info.program) || '') + '</dd><dt>Status</dt><dd><span class="badge bg-' + ((d.info && d.info.status === 'Active') ? 'success' : 'secondary') + '">' + htmlEscape((d.info && d.info.status) || '') + '</span></dd><dt>Student No</dt><dd>' + htmlEscape((d.info && d.info.student_number) || '') + '</dd><dt>Reg No</dt><dd>' + htmlEscape((d.info && d.info.registration_number) || '') + '</dd></dl></div></div></div></div>' +
          '<div class="row g-3 mt-1"><div class="col-md-6"><div class="scard"><div class="sch"><i class="fas fa-clipboard-check me-2"></i>Requirements (' + d.completed + '/' + d.total_reqs + ' completed)</div><div class="scb"><div class="progress mb-3" style="height:20px;border-radius:10px"><div class="progress-bar" style="width:' + d.clearance_pct + '%;background:' + (d.clearance_pct >= 100 ? '#059669' : d.clearance_pct >= 50 ? '#d97706' : '#dc2626') + ';font-size:11px">' + d.clearance_pct + '%</div></div><table class="table table-sm"><thead><tr><th>Requirement</th><th>Status</th></tr></thead><tbody>' + reqsHtml + '</tbody></table></div></div></div>' +
          '<div class="col-md-6"><div class="scard"><div class="sch"><i class="fas fa-file-alt me-2"></i>Documents (' + d.documents.length + ')</div><div class="scb">' + docsHtml + '</div></div></div></div>';
        $('#studentProfileBody').html(h);
        $('#studentProfileModal').modal('show');
      } else { alert(r.error || 'Error loading profile'); }
    }, 'json');
  };

  /* ── Delegate clicks for action buttons ── */
  $(document).on('click', '[data-action]', function() {
    var action = $(this).data('action');
    var id = $(this).data('id');
    var page = $(this).data('page');
    if (page) { window.searchStudents(page); return; }
    if (action === 'viewProfile') { window.viewStudentProfile(id); return; }
    if (action === 'edit') { window.editApplicant(id); return; }
    if (action === 'approve') { window.approveApplicant(id); return; }
    if (action === 'reject') { window.rejectApplicant(id); return; }
    if (action === 'convert') { window.convertToStudent(id); return; }
    if (action === 'delete') { window.deleteApplicant(id); return; }
    if (action === 'verifyDoc') { window.verifyDoc(id, $(this).data('appid')); return; }
    if (action === 'rejectDoc') { window.rejectDoc(id, $(this).data('appid')); return; }
  });

  /* ── Edit Applicant ── */
  window.editApplicant = function(id) {
    $.post('', { action: 'get_applicant_data', id: id }, function(r) {
      if (r.id || r.success) {
        var d = r.data || r;
        $('#editApplicantId').val(d.id);
        $('#editName').val(d.full_name);
        $('#editDob').val(d.date_of_birth || '');
        if (d.gender) $('#editGender').val(d.gender);
        $('#editPhone').val(d.phone || '');
        $('#editEmail').val(d.email || '');
        $('#editProgram').val(d.program_id);
        $('#editIntake').val(d.intake || 'January');
        $('#editGuardian').val(d.guardian_name || '');
        $('#editGuardianPhone').val(d.guardian_phone || '');
        $('#editGuardianRel').val(d.guardian_relationship || '');
        $('#editStatus').val(d.status || 'New Applicant');
        $('#editAddress').val(d.address || '');
        $('#editApplicantModal').modal('show');
      } else { alert(r.message || 'Error loading applicant'); }
    }, 'json');
  };
  window.saveEditApplicant = function() {
    var f = $('#editApplicantForm').serialize() + '&action=edit_applicant';
    $.post('', f, function(r) {
      if (r.success) { $('#editApplicantModal').modal('hide'); loadApplicantRecords(); showToast('success', r.message || 'Updated'); }
      else { showToast('danger', r.message || 'Error'); }
    }, 'json').fail(function() { showToast('danger', 'Server error'); });
  };

  /* ── Approve / Reject / Convert / Delete ── */
  window.approveApplicant = function(id) {
    if (!confirm('Approve this applicant?')) return;
    $.post('', { action: 'approve_applicant_ajax', applicant_id: id }, function(r) {
      if (r.success) { loadApplicantRecords(); window.loadApprovalList(); window.loadClearance(); showToast('success', 'Approved'); }
      else { showToast('danger', r.error || 'Error'); }
    }, 'json').fail(function() { showToast('danger', 'Server error'); });
  };
  window.rejectApplicant = function(id) {
    var reason = prompt('Rejection reason (optional):');
    $.post('', { action: 'reject_applicant_ajax', applicant_id: id, reason: reason || '' }, function(r) {
      if (r.success) { loadApplicantRecords(); window.loadApprovalList(); showToast('success', 'Rejected'); }
      else { showToast('danger', r.error || 'Error'); }
    }, 'json').fail(function() { showToast('danger', 'Server error'); });
  };
  window.convertToStudent = function(id) {
    if (!confirm('Convert this approved applicant to a student?')) return;
    $.post('', { action: 'convert_to_student', applicant_id: id }, function(r) {
      if (r.success) { loadApplicantRecords(); window.loadApprovalList(); showToast('success', 'Converted'); }
      else { showToast('danger', r.error || 'Error'); }
    }, 'json').fail(function() { showToast('danger', 'Server error'); });
  };
  window.deleteApplicant = function(id) {
    if (!confirm('Permanently delete this applicant?')) return;
    $.post('', { action: 'delete_applicant', applicant_id: id }, function(r) {
      if (r.success) { loadApplicantRecords(); showToast('success', 'Deleted'); }
      else { showToast('danger', r.error || 'Error'); }
    }, 'json').fail(function() { showToast('danger', 'Server error'); });
  };

  /* ── Approval List ── */
  window.loadApprovalList = function() {
    $.post('', { action: 'search_applicants', status: 'New Applicant,Under Review' }, function(r) {
      var h = '';
      if (r.data && r.data.length) {
        r.data.forEach(function(a) {
          var disabled = (a.status === 'Approved' || a.status === 'Registered') ? 'disabled' : '';
          var badgeCls = a.status === 'Approved' ? 'success' : a.status === 'Rejected' ? 'danger' : 'warning';
          h += '<tr><td>' + htmlEscape(a.full_name) + '</td><td>' + htmlEscape(a.application_number || '') + '</td><td>' + htmlEscape(a.program_name || '') + '</td><td>' + htmlEscape(a.intake || '') + '</td><td><span class="badge badge-' + badgeCls + '">' + htmlEscape(a.status) + '</span></td><td>' +
            '<button class="btn btn-sm" style="background:#059669;color:#fff;border:none;border-radius:4px;font-size:10px" data-action="approve" data-id="' + a.id + '" ' + disabled + '><i class="fas fa-check me-1"></i>Approve</button>' +
            '<button class="btn btn-sm" style="background:#dc2626;color:#fff;border:none;border-radius:4px;font-size:10px" data-action="reject" data-id="' + a.id + '" ' + disabled + '><i class="fas fa-times me-1"></i>Reject</button>' +
            '<button class="btn btn-sm btn-outline-primary py-0 px-2" style="font-size:10px" data-action="edit" data-id="' + a.id + '"><i class="fas fa-edit"></i></button></td></tr>';
        });
      } else { h = '<tr><td colspan="6" class="text-muted text-center">No pending applicants</td></tr>'; }
      $('#approvalList').html(h);
    }, 'json');
  };

  /* ── Requirements ── */
  window.toggleRequirement = function(id) {
    $.post('', { action: 'toggle_requirement', id: id }, function(r) {
      if (r.success) { location.reload(); } else { showToast('danger', r.error || 'Error'); }
    }, 'json');
  };
  window.receiveRequirement = function(id) {
    $.post('', { action: 'receive_requirement', requirement_id: id }, function(r) {
      showToast(r.success ? 'success' : 'danger', r.success ? 'All submitted' : (r.error || 'Error'));
    }, 'json');
  };
  window.resetAllRequirements = function() {
    if (!confirm('Reset ALL applicants requirement statuses?')) return;
    $.post('', { action: 'reset_requirements' }, function(r) {
      showToast(r.success ? 'success' : 'danger', r.success ? 'Reset complete' : (r.error || 'Error'));
    }, 'json');
  };

  /* ── View Applicant Requirements (modal) ── */
  window.viewApplicantReqs = function(id) {
    $.post('', { action: 'get_applicant_reqs', applicant_id: id }, function(r) {
      if (r.success) {
        var d = r.data, info = d.info, infoId = info.id || id;
        if (!info || !info.full_name) { $('#requirementBody').html('<p class="text-danger">Applicant not found</p>'); $('#requirementModal').modal('show'); return; }
        var h = '<div class="mb-3"><strong>' + htmlEscape(info.full_name) + '</strong> &mdash; ' + htmlEscape(info.application_number || '') + '<br><small class="text-muted">' + htmlEscape(info.program_name || '') + ' / ' + htmlEscape(info.intake || '') + '</small></div><hr><table class="table table-sm"><thead><tr><th>Requirement</th><th>Status</th><th>Documents</th><th>Actions</th></tr></thead><tbody>';
        if (d.requirements && d.requirements.length) {
          d.requirements.forEach(function(req) {
            var cls = req.status === 'Verified' ? 'success' : req.status === 'Submitted' ? 'primary' : req.status === 'Rejected' ? 'danger' : 'secondary';
            h += '<tr><td>' + htmlEscape(req.requirement_name) + '</td><td><span class="badge bg-' + cls + '">' + htmlEscape(req.status || 'Pending') + '</span></td><td>';
            var hasDoc = false;
            if (d.documents && d.documents.length) {
              d.documents.forEach(function(doc) {
                if (doc.requirement_id == req.id) {
                  hasDoc = true;
                  h += '<div><a href="' + htmlEscape(doc.file_path || '#') + '" target="_blank" class="text-decoration-none small"><i class="fas fa-file me-1"></i>View</a>';
                  if (doc.verification_status !== 'Verified') {
                    h += ' <button class="btn btn-sm btn-outline-success py-0 px-1" style="font-size:9px" data-action="verifyDoc" data-id="' + doc.id + '" data-appid="' + infoId + '"><i class="fas fa-check"></i></button>';
                  }
                  h += ' <button class="btn btn-sm btn-outline-danger py-0 px-1" style="font-size:9px" data-action="rejectDoc" data-id="' + doc.id + '" data-appid="' + infoId + '"><i class="fas fa-times"></i></button></div>';
                }
              });
            }
            if (!hasDoc) h += '<span class="text-muted small">No docs</span>';
            h += '</td><td><button class="btn btn-sm btn-outline-primary py-0 px-2" style="font-size:10px" data-action="showUpload" data-reqid="' + req.id + '" data-appid="' + infoId + '"><i class="fas fa-upload"></i></button></td></tr>';
          });
        } else { h += '<tr><td colspan="4" class="text-muted text-center">No requirements loaded</td></tr>'; }
        h += '</tbody></table>';
        if (d.history && d.history.length) {
          h += '<hr><h6>History</h6><ul class="small">';
          d.history.forEach(function(hh) { h += '<li>' + htmlEscape(hh.action) + ' - ' + htmlEscape(hh.requirement_name || '') + ' <span class="text-muted">' + hh.created_at + '</span></li>'; });
          h += '</ul>';
        }
        $('#requirementBody').html(h);
        $('#requirementModal').modal('show');
      } else { alert(r.error || 'Error'); }
    }, 'json');
  };

  /* ── Upload Document Modal ── */
  window.showUploadModal = function(reqId, appId) {
    $('#uploadDocReqId').val(reqId);
    $('#uploadDocAppId').val(appId);
    $('#uploadDocFile').val('');
    $('#uploadDocNotes').val('');
    $('#uploadDocModal').modal('show');
  };
  window.submitDocument = function() {
    var fd = new FormData();
    fd.append('action', 'upload_document');
    fd.append('requirement_id', $('#uploadDocReqId').val());
    fd.append('applicant_id', $('#uploadDocAppId').val());
    var file = $('#uploadDocFile')[0].files[0];
    if (!file) { showToast('danger', 'Please select a file'); return; }
    fd.append('doc_file', file);
    $.ajax({
      url: '', type: 'POST', data: fd, processData: false, contentType: false, dataType: 'json',
      success: function(r) {
        if (r.success) { $('#uploadDocModal').modal('hide'); window.viewApplicantReqs($('#uploadDocAppId').val()); showToast('success', 'Uploaded'); }
        else { showToast('danger', r.error || 'Upload failed'); }
      }
    });
  };

  /* ── Document Verification ── */
  window.verifyDoc = function(docId, appId) {
    $.post('', { action: 'verify_document', doc_id: docId, status: 'Verified' }, function(r) {
      if (r.success) { window.viewApplicantReqs(appId); showToast('success', 'Verified'); } else { showToast('danger', r.error || 'Error'); }
    }, 'json');
  };
  window.rejectDoc = function(docId, appId) {
    $.post('', { action: 'verify_document', doc_id: docId, status: 'Rejected' }, function(r) {
      if (r.success) { window.viewApplicantReqs(appId); showToast('success', 'Rejected'); } else { showToast('danger', r.error || 'Error'); }
    }, 'json');
  };

  /* ── Clearance ── */
  window.loadClearance = function() {
    $.post('', { action: 'reports_data', type: 'clearance', program: ($('#clrProgram').val()||''), intake: ($('#clrIntake').val()||'') }, function(r) {
      var h = '';
      if (r.data && r.data.length) {
        r.data.forEach(function(pc) {
          var pct = pc.total_req > 0 ? Math.round((pc.verified_count / pc.total_req) * 100) : 0;
          var barCls = pct >= 100 ? '#059669' : pct >= 50 ? '#d97706' : '#dc2626';
          h += '<tr><td>' + htmlEscape(pc.application_number || '') + '</td><td>' + htmlEscape(pc.full_name) + '</td><td>' + htmlEscape(pc.program_name || '') + '</td><td>' + htmlEscape(pc.intake || '') + '</td><td><div class="progress" style="height:16px;border-radius:8px"><div class="progress-bar" style="width:' + pct + '%;background:' + barCls + ';font-size:10px">' + pct + '%</div></div></td><td><button class="btn btn-sm btn-outline-info py-0 px-2" style="font-size:10px" data-action="viewReqs" data-id="' + pc.id + '"><i class="fas fa-eye"></i></button></td></tr>';
        });
      } else { h = '<tr><td colspan="6" class="text-muted text-center">No data</td></tr>'; }
      $('#clearanceList').html(h);
    }, 'json');
  };

  /* ── Tracking ── */
  window.loadReqTracking = function() {
    $.post('', { action: 'get_requirements_tracking', q: $('#trkSearch').val(), status: $('#trkStatus').val() }, function(r) {
      var h = '';
      if (r.data && r.data.length) {
        r.data.forEach(function(t) {
          var cls = t.status === 'Verified' ? 'success' : t.status === 'Submitted' ? 'primary' : t.status === 'Rejected' ? 'danger' : 'secondary';
          h += '<tr><td>' + htmlEscape(t.full_name) + '</td><td>' + htmlEscape(t.requirement_name) + '</td><td><span class="badge bg-' + cls + '">' + htmlEscape(t.status || 'Pending') + '</span></td><td>' + htmlEscape(t.remarks || '') + '</td><td><small class="text-muted">' + htmlEscape(t.updated_at || t.created_at || '') + '</small></td></tr>';
        });
      } else { h = '<tr><td colspan="5" class="text-muted text-center">No data</td></tr>'; }
      $('#trackingList').html(h);
    }, 'json');
  };

  /* ── Readiness ── */
  window.loadReadiness = function() {
    $.post('', { action: 'registration_readiness' }, function(r) {
      var h = '';
      if (r.data && r.data.length) {
        r.data.forEach(function(x) {
          var verified = x.verified_count || 0;
          var total = x.total || 1;
          var pct = Math.round((verified / total) * 100);
          var cls = pct >= 100 ? 'success' : pct >= 50 ? 'warning' : 'danger';
          var label = pct >= 100 ? 'Ready' : pct >= 50 ? 'Partial' : 'Not Ready';
          h += '<tr><td>' + htmlEscape(x.full_name) + '</td><td>' + htmlEscape(x.program_name || '') + '</td><td>' + verified + '/' + total + ' (' + pct + '%)</td><td><span class="badge bg-' + cls + '">' + label + '</span></td><td><button class="btn btn-sm btn-outline-info py-0 px-2" style="font-size:10px" data-action="viewReqs" data-id="' + x.id + '"><i class="fas fa-eye"></i></button></td></tr>';
        });
      } else { h = '<tr><td colspan="5" class="text-muted text-center">No data</td></tr>'; }
      $('#readinessList').html(h);
    }, 'json');
  };

  /* ── Registration List ── */
  window.loadRegList = function() {
    $.post('', { action: 'get_registration_list', q: $('#regSearch').val(), intake: $('#regIntake').val() }, function(r) {
      var h = '';
      if (r.data && r.data.length) {
        r.data.forEach(function(x) {
          h += '<tr><td>' + htmlEscape(x.application_number || '') + '</td><td>' + htmlEscape(x.full_name) + '</td><td>' + htmlEscape(x.program_name || '') + '</td><td>' + htmlEscape(x.intake || '') + '</td><td>' + (x.is_registered ? '<span class="badge bg-success">Yes</span>' : '<span class="badge bg-secondary">No</span>') + '</td><td><button class="btn btn-sm" style="background:#7c3aed;color:#fff;border:none;border-radius:4px;font-size:10px" data-action="convert" data-id="' + x.id + '" ' + (x.is_registered ? 'disabled' : '') + '><i class="fas fa-user-graduate me-1"></i>Register</button></td></tr>';
        });
      } else { h = '<tr><td colspan="6" class="text-muted text-center">No data</td></tr>'; }
      $('#regList').html(h);
    }, 'json');
  };

  /* ── Activation List ── */
  window.loadActivationList = function() {
    $.post('', { action: 'search_students', limit: 50 }, function(r) {
      var h = '';
      if (r.data && r.data.length) {
        r.data.forEach(function(s) {
          var name = s.full_name || (s.firstname + ' ' + s.lastname) || '';
          var isActive = s.status === 'Active';
          h += '<tr><td>' + htmlEscape(s.admission_no || s.application_number || '') + '</td><td>' + htmlEscape(name) + '</td><td>' + htmlEscape(s.program_name || s.program || '') + '</td><td><span class="badge bg-' + (isActive ? 'success' : 'secondary') + '">' + htmlEscape(s.status || 'Inactive') + '</span></td><td><button class="btn btn-sm ' + (isActive ? 'btn-outline-warning' : 'btn-outline-success') + ' py-0 px-2" style="font-size:10px" data-action="toggleStatus" data-id="' + (s.student_id || s.id || 0) + '" data-status="' + (isActive ? 'deactivate' : 'activate') + '">' + (isActive ? '<i class="fas fa-toggle-off"></i>' : '<i class="fas fa-toggle-on"></i>') + '</button></td></tr>';
        });
      } else { h = '<tr><td colspan="5" class="text-muted text-center">No results</td></tr>'; }
      $('#activationList').html(h);
    }, 'json');
  };

  /* ── Handle toggle status button ── */
  $(document).on('click', '[data-action="toggleStatus"]', function() {
    var id = $(this).data('id');
    var mode = $(this).data('status');
    var msg = mode === 'activate' ? 'Activate this student?' : 'Deactivate this student?';
    if (!confirm(msg)) return;
    $.post('', { action: 'toggle_student_status', student_id: id, new_status: (mode === 'activate' ? 'Active' : 'Inactive') }, function(r) {
      if (r.success) { window.loadActivationList(); showToast('success', r.message || 'Done'); }
      else { showToast('danger', r.error || 'Error'); }
    }, 'json');
  });

  /* ── Document List ── */
  window.loadDocList = function() {
    $.post('', { action: 'get_documents_list' }, function(r) {
      var h = '';
      if (r.data && r.data.length) {
        r.data.forEach(function(d) {
          var cls = d.verification_status === 'Verified' ? 'success' : d.verification_status === 'Rejected' ? 'danger' : 'warning';
          h += '<tr><td>' + htmlEscape(d.full_name) + '</td><td>' + htmlEscape(d.requirement_name) + '</td><td><span class="badge bg-' + cls + '">' + htmlEscape(d.verification_status || 'Pending') + '</span></td><td><small class="text-muted">' + htmlEscape(d.uploaded_at || d.created_at || '') + '</small></td><td>' +
            '<button class="btn btn-sm btn-outline-success py-0 px-2" style="font-size:10px" data-action="verifyDoc" data-id="' + d.id + '" data-appid="' + d.applicant_id + '" ' + (d.verification_status === 'Verified' ? 'disabled' : '') + '><i class="fas fa-check"></i></button>' +
            '<button class="btn btn-sm btn-outline-danger py-0 px-2" style="font-size:10px" data-action="rejectDoc" data-id="' + d.id + '" data-appid="' + d.applicant_id + '" ' + (d.verification_status === 'Rejected' ? 'disabled' : '') + '><i class="fas fa-times"></i></button></td></tr>';
        });
      } else { h = '<tr><td colspan="5" class="text-muted text-center">No documents</td></tr>'; }
      $('#docList').html(h);
    }, 'json');
  };

  /* ── Verification List ── */
  window.loadVerificationList = function() {
    $.post('', { action: 'get_verification_list' }, function(r) {
      var h = '';
      if (r.data && r.data.length) {
        r.data.forEach(function(v) {
          var cls = v.verification_status === 'Verified' ? 'success' : v.verification_status === 'Submitted' ? 'primary' : v.verification_status === 'Rejected' ? 'danger' : 'secondary';
          h += '<tr><td>' + htmlEscape(v.full_name) + '</td><td>' + htmlEscape(v.requirement_name) + '</td><td><span class="badge bg-' + cls + '">' + htmlEscape(v.verification_status || 'Pending') + '</span></td><td><small class="text-muted">' + htmlEscape(v.updated_at || v.created_at || '') + '</small></td><td>' +
            '<button class="btn btn-sm btn-outline-success py-0 px-2" style="font-size:10px" data-action="verifyDoc" data-id="' + v.id + '" data-appid="' + v.applicant_id + '" ' + (v.verification_status === 'Verified' ? 'disabled' : '') + '><i class="fas fa-check"></i></button>' +
            '<button class="btn btn-sm btn-outline-danger py-0 px-2" style="font-size:10px" data-action="rejectDoc" data-id="' + v.id + '" data-appid="' + v.applicant_id + '" ' + (v.verification_status === 'Rejected' ? 'disabled' : '') + '><i class="fas fa-times"></i></button></td></tr>';
        });
      } else { h = '<tr><td colspan="5" class="text-muted text-center">No items to verify</td></tr>'; }
      $('#verificationList').html(h);
    }, 'json');
  };

  /* ── Handle "viewReqs" clicks (clearance/readiness tables) ── */
  $(document).on('click', '[data-action="viewReqs"]', function() {
    window.viewApplicantReqs($(this).data('id'));
  });
  /* ── Handle "showUpload" clicks ── */
  $(document).on('click', '[data-action="showUpload"]', function() {
    window.showUploadModal($(this).data('reqid'), $(this).data('appid'));
  });
  /* ── Handle verifyDoc/rejectDoc from dynamic rows ── */
  $(document).on('click', '[data-action="verifyDoc"]', function() {
    window.verifyDoc($(this).data('id'), $(this).data('appid'));
  });
  $(document).on('click', '[data-action="rejectDoc"]', function() {
    window.rejectDoc($(this).data('id'), $(this).data('appid'));
  });

  /* ── Reports ── */
  window.loadReport = function() {
    $.post('', { action: 'reports_data', from: $('#rptFrom').val(), to: $('#rptTo').val() }, function(r) {
      var h = '';
      if (r.data && r.data.length) {
        r.data.forEach(function(x) {
          h += '<tr><td>' + htmlEscape(x.created_at || '') + '</td><td>' + htmlEscape(x.full_name) + '</td><td>' + htmlEscape(x.program_name || '') + '</td><td>' + htmlEscape(x.status) + '</td><td>' + htmlEscape(x.intake || '') + '</td></tr>';
        });
      } else { h = '<tr><td colspan="5" class="text-muted text-center">No data for selected range</td></tr>'; }
      $('#reportTable').html(h);
    }, 'json');
  };
  window.exportCSV = function() {
    var f = $('#rptFrom').val(), t = $('#rptTo').val();
    window.open('?ajax=reports_data&type=admission&format=csv&from=' + encodeURIComponent(f) + '&to=' + encodeURIComponent(t), '_blank');
  };

  /* ── Messaging ── */
  window.loadMessages = function() {
    var id = $('#msgApplicant').val();
    if (!id) { $('#msgConversation').html('<p class="text-muted text-center">Select an applicant to view messages</p>'); return; }
    $.post('', { action: 'get_messages', applicant_id: id }, function(r) {
      var h = '';
      if (r && r.length) {
        r.forEach(function(m) {
          h += '<div class="mb-2"><span style="display:inline-block;padding:6px 12px;border-radius:12px;background:#e9ecef;color:#000;font-size:12px;max-width:80%">' + htmlEscape(m.message) + '</span><br><small class="text-muted">' + htmlEscape(m.created_at || '') + '</small></div>';
        });
      } else { h = '<p class="text-muted text-center">No messages</p>'; }
      $('#msgConversation').html(h);
    }, 'json');
  };
  window.sendMessage = function() {
    var id = $('#msgApplicant').val(), msg = $('#msgText').val();
    if (!id || !msg) { showToast('warning', 'Select applicant and type message'); return; }
    $.post('', { action: 'send_message', applicant_id: id, message: msg }, function(r) {
      if (r.success) { $('#msgText').val(''); window.loadMessages(); showToast('success', 'Sent'); }
      else { showToast('danger', r.error || 'Error'); }
    }, 'json');
  };
  $('#msgApplicant').on('change', window.loadMessages);

  /* ── Notifications ── */
  window.loadNotifications = function() {
    var t = $('#notifType').val();
    $.post('', { action: 'get_notifications', type: t }, function(r) {
      var h = '';
      if (r.data && r.data.length) {
        r.data.forEach(function(n) {
          var icons = { info: 'fa-info-circle', success: 'fa-check-circle', warning: 'fa-exclamation-triangle', danger: 'fa-times-circle' };
          h += '<div class="alert alert-' + (n.type || 'info') + ' d-flex justify-content-between align-items-center py-2 px-3 mb-2" style="border-radius:8px"><span><i class="fas ' + (icons[n.type] || 'fa-bell') + ' me-2"></i>' + htmlEscape(n.message) + '</span><small class="text-muted">' + htmlEscape(n.created_at || '') + '</small></div>';
        });
      } else { h = '<p class="text-muted text-center">No notifications</p>'; }
      $('#notificationList').html(h);
    }, 'json');
  };
  $('#clearAllNotifBtn').on('click', function() {
    if (!confirm('Clear all notifications?')) return;
    $.post('', { action: 'clear_notifications' }, function() { window.loadNotifications(); }, 'json');
  });

  /* ── Alerts ── */
  window.loadAlerts = function() {
    $.post('', { action: 'get_requirement_alerts' }, function(r) {
      var h = '';
      if (r.data && r.data.length) {
        r.data.forEach(function(a) {
          h += '<div class="alert alert-warning d-flex justify-content-between align-items-center py-2 px-3 mb-2" style="border-radius:8px"><span><i class="fas fa-exclamation-triangle me-2"></i>' + htmlEscape(a.full_name) + ' - ' + htmlEscape(a.requirement_name) + ' <span class="badge bg-secondary">' + htmlEscape(a.status || 'Pending') + '</span></span><small class="text-muted">' + htmlEscape(a.updated_at || a.created_at || '') + '</small></div>';
        });
      } else { h = '<p class="text-muted text-center">No alerts</p>'; }
      $('#alertList').html(h);
    }, 'json');
  };

  /* ── Charts ── */
  var intakeChart, clearanceChart, intakeChart2, intakeBarChart, programPieChart;
  function initCharts() {
    if ($('#intakeChart').length) {
      intakeChart = new Chart(document.getElementById('intakeChart').getContext('2d'), {
        type: 'bar',
        data: { labels: ['January', 'May', 'August'], datasets: [{ label: 'Applicants', data: [<?=$jan_count??0?>,<?=$may_count??0?>,<?=$aug_count??0?>], backgroundColor: ['#7c3aed', '#2563eb', '#059669'] }] },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } }
      });
    }
    if ($('#clearanceChart').length) {
      clearanceChart = new Chart(document.getElementById('clearanceChart').getContext('2d'), {
        type: 'doughnut',
        data: { labels: ['Cleared', 'Pending'], datasets: [{ data: [<?=$cleared_count??0?>,<?=$pending_count??0?>], backgroundColor: ['#059669', '#d97706'] }] },
        options: { responsive: true, maintainAspectRatio: false }
      });
    }
    if ($('#intakeChart2').length) {
      intakeChart2 = new Chart(document.getElementById('intakeChart2').getContext('2d'), {
        type: 'pie',
        data: { labels: ['January', 'May', 'August'], datasets: [{ data: [<?=$jan_count??0?>,<?=$may_count??0?>,<?=$aug_count??0?>], backgroundColor: ['#7c3aed', '#2563eb', '#059669'] }] },
        options: { responsive: true }
      });
    }
    if ($('#intakeBarChart').length) {
      intakeBarChart = new Chart(document.getElementById('intakeBarChart').getContext('2d'), {
        type: 'bar',
        data: { labels: ['January', 'May', 'August'], datasets: [{ label: 'Applicants', data: [<?=$jan_count??0?>,<?=$may_count??0?>,<?=$aug_count??0?>], backgroundColor: ['#7c3aed', '#2563eb', '#059669'] }] },
        options: { responsive: true, plugins: { legend: { display: false } } }
      });
    }
    if ($('#programPieChart').length) {
      var plabels = [<?php $first=true;foreach($programs_list as $p):if(!$first)echo ',';echo "'".str_replace("'","\\'",$p['program_name'])."'";$first=false;endforeach;?>];
      var pdata = [<?php $first=true;foreach($programs_list as $p):if(!$first)echo ',';echo($program_counts[$p['id']]??0);$first=false;endforeach;?>];
      programPieChart = new Chart(document.getElementById('programPieChart').getContext('2d'), {
        type: 'pie',
        data: { labels: plabels, datasets: [{ data: pdata, backgroundColor: ['#7c3aed', '#2563eb', '#059669', '#d97706', '#dc2626', '#0891b2', '#be185d', '#65a30d'] }] },
        options: { responsive: true }
      });
    }
  }

  /* ── Load all data + init ── */
  function loadData() {
    window.loadClearance();
    window.loadReqTracking();
    window.loadReadiness();
    window.loadRegList();
    window.loadActivationList();
    window.loadDocList();
    window.loadVerificationList();
    window.loadNotifications();
    window.loadAlerts();
    window.loadApprovalList();
    initCharts();
  }
  loadData();
});
</script>
<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
</body>
</html>
