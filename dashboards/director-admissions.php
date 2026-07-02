<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
require_once __DIR__ . '/../includes/enterprise_auth.php';
require_once __DIR__ . '/../includes/website_submissions_widget.php';
$ctx = bootstrapStaffDashboard(['director admissions', 'admissions']);
$conn = $ctx['staff'];
$students_conn = $ctx['students'] ?? null;
$website_conn = $ctx['website'];
$user = $ctx['user'];
$user_id = (int)($user['id'] ?? 0);
$user_role = $_SESSION['role'] ?? '';
$user_name = $user['full_name'] ?? 'Director Admissions';
$students_db = defined('STUDENTS_DB_NAME') ? STUDENTS_DB_NAME : 'igangaschoolofl_students_db';
$staff_db = defined('STAFF_DB_NAME') ? STAFF_DB_NAME : 'igangaschoolofl_staffs_db';
$upload_dir = __DIR__ . '/../uploads/admissions/';
if (!is_dir($upload_dir)) { mkdir($upload_dir, 0755, true); }
if (!isset($conn) || !$conn) { die('Database connection failed.'); }

function auto_migrate($c) {
    $sqls = [];
    $sqls[] = "CREATE TABLE IF NOT EXISTS academic_programs (id INT AUTO_INCREMENT PRIMARY KEY, program_code VARCHAR(20) NOT NULL, program_name VARCHAR(200) NOT NULL, program_type VARCHAR(50) DEFAULT 'Certificate', department VARCHAR(100) DEFAULT '', duration_years INT DEFAULT 2, status VARCHAR(20) DEFAULT 'Active', created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    $sqls[] = "CREATE TABLE IF NOT EXISTS applicants (id INT AUTO_INCREMENT PRIMARY KEY, full_name VARCHAR(200) NOT NULL, other_names VARCHAR(200) DEFAULT '', date_of_birth DATE DEFAULT NULL, gender ENUM('Male','Female','Other') DEFAULT 'Female', phone VARCHAR(30) DEFAULT '', email VARCHAR(200) DEFAULT '', address TEXT, guardian_name VARCHAR(200) DEFAULT '', guardian_phone VARCHAR(30) DEFAULT '', guardian_relationship VARCHAR(50) DEFAULT '', application_number VARCHAR(50) DEFAULT '', program_id INT DEFAULT NULL, intake VARCHAR(30) DEFAULT 'January', admission_date DATE DEFAULT NULL, status VARCHAR(30) DEFAULT 'New Applicant', rejection_reason TEXT, created_by INT DEFAULT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    $sqls[] = "CREATE TABLE IF NOT EXISTS admission_requirements (id INT AUTO_INCREMENT PRIMARY KEY, requirement_name VARCHAR(200) NOT NULL, type VARCHAR(50) DEFAULT 'Document', is_active TINYINT(1) DEFAULT 1, is_mandatory TINYINT(1) DEFAULT 1, display_order INT DEFAULT 0, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    $sqls[] = "CREATE TABLE IF NOT EXISTS applicant_requirement_status (id INT AUTO_INCREMENT PRIMARY KEY, applicant_id INT NOT NULL, requirement_id INT NOT NULL, status VARCHAR(30) DEFAULT 'Not Submitted', submitted_by INT DEFAULT NULL, verified_by INT DEFAULT NULL, rejected_by INT DEFAULT NULL, remarks TEXT, submitted_at DATETIME DEFAULT NULL, verified_at DATETIME DEFAULT NULL, received_by INT DEFAULT NULL, received_at DATETIME DEFAULT NULL, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, UNIQUE KEY unique_app_req (applicant_id, requirement_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    $sqls[] = "CREATE TABLE IF NOT EXISTS student_documents (id INT AUTO_INCREMENT PRIMARY KEY, applicant_id INT NOT NULL, requirement_id INT DEFAULT NULL, document_type VARCHAR(100) DEFAULT '', document_title VARCHAR(200) DEFAULT '', file_name VARCHAR(255) NOT NULL, file_path VARCHAR(500) NOT NULL, file_size INT DEFAULT 0, mime_type VARCHAR(100) DEFAULT '', verification_status VARCHAR(30) DEFAULT 'Pending', verified_by INT DEFAULT NULL, verified_at DATETIME DEFAULT NULL, remarks TEXT, document_status VARCHAR(20) DEFAULT 'Active', uploaded_by INT DEFAULT NULL, uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    $sqls[] = "CREATE TABLE IF NOT EXISTS requirement_history (id INT AUTO_INCREMENT PRIMARY KEY, applicant_id INT NOT NULL, requirement_id INT NOT NULL, action VARCHAR(100) NOT NULL, performed_by INT DEFAULT NULL, remarks TEXT, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    $sqls[] = "CREATE TABLE IF NOT EXISTS admission_activity_logs (id INT AUTO_INCREMENT PRIMARY KEY, user_id INT DEFAULT NULL, action VARCHAR(200) NOT NULL, module VARCHAR(100) DEFAULT '', record_id INT DEFAULT NULL, description TEXT, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    $sqls[] = "CREATE TABLE IF NOT EXISTS admission_notifications (id INT AUTO_INCREMENT PRIMARY KEY, applicant_id INT DEFAULT NULL, recipient_type VARCHAR(50) DEFAULT '', recipient_id INT DEFAULT NULL, title VARCHAR(200) DEFAULT '', message TEXT, channel VARCHAR(50) DEFAULT 'in_app', sent_by INT DEFAULT NULL, is_read TINYINT(1) DEFAULT 0, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    $sqls[] = "CREATE TABLE IF NOT EXISTS student_admission_tracking (id INT AUTO_INCREMENT PRIMARY KEY, student_number VARCHAR(50) DEFAULT '', full_name VARCHAR(200) NOT NULL, program VARCHAR(200) DEFAULT '', intake VARCHAR(30) DEFAULT '', admission_date DATE DEFAULT NULL, admission_status VARCHAR(30) DEFAULT 'Pending', requirements_completed INT DEFAULT 0, requirements_total INT DEFAULT 0, fee_status VARCHAR(30) DEFAULT 'Unpaid', total_fees DECIMAL(12,2) DEFAULT 0, amount_paid DECIMAL(12,2) DEFAULT 0, documents_uploaded INT DEFAULT 0, assigned_to INT DEFAULT NULL, notes TEXT, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    foreach ($sqls as $sql) { @$c->query($sql); }
    $check = $c->query("SELECT COUNT(*) as cnt FROM admission_requirements");
    if ($check) { $row = $check->fetch_assoc(); if ((int)$row['cnt'] === 0) {
        $reqs = [['Primary School Leaving Certificate','Document',1,1],['Birth Certificate','Document',2,1],['National ID Copy','Document',3,1],['Passport Photo','Document',4,1],['Medical Certificate','Document',5,1],['Recommendation Letter','Document',6,0],['O-Level Results','Document',7,1],['A-Level Results','Document',8,1],['Application Form','Form',9,1],['Admission Letter','Document',10,1],['Fee Payment Receipt','Document',11,1],['Guardian Consent Form','Form',12,1],['HIV Test Results','Document',13,1],['Dental Certificate','Document',14,0],['Immunization Record','Document',15,1],['Transfer Letter','Document',16,0],['Good Conduct Certificate','Document',17,0],['Sponsorship Letter','Document',18,0],['Marriage Certificate','Document',19,0],['Previous School ID','Document',20,0]];
        $stmt = $c->prepare("INSERT INTO admission_requirements (requirement_name, type, display_order, is_mandatory) VALUES (?, ?, ?, ?)");
        foreach ($reqs as $r) { $stmt->bind_param('ssi', $r[0], $r[1], $r[2]); $stmt->execute(); }
        $stmt->close();
    }}
    $chk = $c->query("SELECT COUNT(*) as cnt FROM academic_programs");
    if ($chk) { $r = $chk->fetch_assoc(); if ((int)$r['cnt'] === 0) {
        $progs = [['DNE','Diploma in Nursing Education','Diploma','Nursing',3],['DMH','Diploma in Midwifery & Health','Diploma','Midwifery',3],['CNE','Certificate in Nursing Education','Certificate','Nursing',2],['CMW','Certificate in Midwifery','Certificate','Midwifery',2],['DEN','Diploma in Environmental Health','Diploma','Environmental Health',3]];
        $stmt = $c->prepare("INSERT INTO academic_programs (program_code, program_name, program_type, department, duration_years) VALUES (?, ?, ?, ?, ?)");
        foreach ($progs as $p) { $stmt->bind_param('ssssi', $p[0], $p[1], $p[2], $p[3], $p[4]); $stmt->execute(); }
        $stmt->close();
    }}
}
auto_migrate($conn);

function log_activity($c, $uid, $action, $module, $rid = null, $desc = '') {
    $stmt = $c->prepare("INSERT INTO admission_activity_logs (user_id, action, module, record_id, description) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param('issis', $uid, $action, $module, $rid, $desc); $stmt->execute(); $stmt->close();
}

function get_requirement_stats($c, $aid) {
    $total = 0; $completed = 0;
    $r = $c->query("SELECT COUNT(*) as total FROM admission_requirements WHERE is_active = 1");
    if ($r) { $row = $r->fetch_assoc(); $total = (int)$row['total']; }
    $r2 = $c->query("SELECT COUNT(*) as completed FROM applicant_requirement_status WHERE applicant_id = $aid AND status IN ('Submitted','Verified')");
    if ($r2) { $row2 = $r2->fetch_assoc(); $completed = (int)$row2['completed']; }
    return ['total'=>$total, 'completed'=>$completed, 'percentage'=>$total>0?round(($completed/$total)*100):0];
}

$current_page = $_GET['page'] ?? 'overview';
$programs = $conn->query("SELECT * FROM academic_programs WHERE status = 'Active' ORDER BY program_name");
$program_list = []; if ($programs) while ($p = $programs->fetch_assoc()) $program_list[] = $p;

// Handle website submission actions
if (function_exists('handleWebsiteSubmissionsAction')) {
    handleWebsiteSubmissionsAction($website_conn);
}

if (isset($_REQUEST['ajax'])) {
    header('Content-Type: application/json');
    $act = $_REQUEST['ajax'];
    if ($act === 'add_student') {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['success'=>false,'error'=>'POST required']); exit; }
        $fn = trim($_POST['full_name'] ?? ''); $on = trim($_POST['other_names'] ?? '');
        $dob = $_POST['date_of_birth'] ?? ''; $gen = $_POST['gender'] ?? 'Female';
        $ph = trim($_POST['phone'] ?? ''); $em = trim($_POST['email'] ?? '');
        $ad = trim($_POST['address'] ?? ''); $gn = trim($_POST['guardian_name'] ?? '');
        $gp = trim($_POST['guardian_phone'] ?? ''); $gr = trim($_POST['guardian_relationship'] ?? '');
        $pid = (int)($_POST['program_id'] ?? 0); $int = $_POST['intake'] ?? 'January';
        $adm_dt = $_POST['admission_date'] ?? date('Y-m-d');
        if (empty($fn)) { echo json_encode(['success'=>false,'error'=>'Full name required']); exit; }
        $an = 'APP-'.date('Y').'-'.str_pad(mt_rand(1,99999),5,'0',STR_PAD_LEFT);
        $st = $conn->prepare("INSERT INTO applicants (full_name,other_names,date_of_birth,gender,phone,email,address,guardian_name,guardian_phone,guardian_relationship,application_number,program_id,intake,admission_date,status,created_by) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
        $st->bind_param('sssssssssssssssi',$fn,$on,$dob,$gen,$ph,$em,$ad,$gn,$gp,$gr,$an,$pid,$int,$adm_dt,$user_id);
        if ($st->execute()) {
            $aid = $conn->insert_id; $st->close();
            $pn = ''; $pr = $conn->query("SELECT program_name FROM academic_programs WHERE id=$pid");
            if ($pr && $pr->num_rows > 0) { $pg = $pr->fetch_assoc(); $pn = $pg['program_name']; }
            $rc = 0; $ck = $conn->query("SELECT COUNT(*) as cnt FROM admission_requirements WHERE is_active=1");
            if ($ck) { $rw = $ck->fetch_assoc(); $rc = (int)$rw['cnt']; }
            $ts = $conn->prepare("INSERT INTO student_admission_tracking (student_number,full_name,program,intake,admission_date,admission_status,requirements_completed,requirements_total) VALUES (?,?,?,?,?,'New Applicant',0,?)");
            $ts->bind_param('ssssss',$an,$fn,$pn,$int,$adm_dt,$rc); $ts->execute(); $ts->close();
            $reqs = $conn->query("SELECT id FROM admission_requirements WHERE is_active=1");
            if ($reqs) { $ins = $conn->prepare("INSERT IGNORE INTO applicant_requirement_status (applicant_id,requirement_id,status) VALUES (?,?,'Not Submitted')");
                while ($rq = $reqs->fetch_assoc()) { $ins->bind_param('ii',$aid,$rq['id']); $ins->execute(); } $ins->close(); }
            log_activity($conn,$user_id,'Add Applicant','admissions',$aid,"Added: $fn ($an)");
            echo json_encode(['success'=>true,'message'=>'Applicant added','applicant_id'=>$aid,'application_number'=>$an]);
        } else { echo json_encode(['success'=>false,'error'=>'Failed: '.$conn->error]); }
        exit;
    }
    if ($act === 'edit_applicant') {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['success'=>false,'error'=>'POST required']); exit; }
        $id = (int)($_POST['applicant_id'] ?? 0); $fn = trim($_POST['full_name'] ?? '');
        $on = trim($_POST['other_names'] ?? ''); $dob = $_POST['date_of_birth'] ?? '';
        $gen = $_POST['gender'] ?? 'Female'; $ph = trim($_POST['phone'] ?? '');
        $em = trim($_POST['email'] ?? ''); $ad = trim($_POST['address'] ?? '');
        $gn = trim($_POST['guardian_name'] ?? ''); $gp = trim($_POST['guardian_phone'] ?? '');
        $gr = trim($_POST['guardian_relationship'] ?? ''); $pid = (int)($_POST['program_id'] ?? 0);
        $int = $_POST['intake'] ?? 'January'; $adm = $_POST['admission_date'] ?? '';
        if ($id <= 0 || empty($fn)) { echo json_encode(['success'=>false,'error'=>'Invalid']); exit; }
        $st = $conn->prepare("UPDATE applicants SET full_name=?,other_names=?,date_of_birth=?,gender=?,phone=?,email=?,address=?,guardian_name=?,guardian_phone=?,guardian_relationship=?,program_id=?,intake=?,admission_date=? WHERE id=?");
        $st->bind_param('sssssssssssssi',$fn,$on,$dob,$gen,$ph,$em,$ad,$gn,$gp,$gr,$pid,$int,$adm,$id);
        if ($st->execute()) {
            $st->close();
            $conn->query("UPDATE student_admission_tracking SET full_name='".$conn->real_escape_string($fn)."',intake='".$conn->real_escape_string($int)."',admission_date='".$conn->real_escape_string($adm)."' WHERE student_number IN (SELECT application_number FROM applicants WHERE id=$id)");
            log_activity($conn,$user_id,'Edit Applicant','admissions',$id,"Updated: $fn");
            echo json_encode(['success'=>true,'message'=>'Updated']);
        } else { echo json_encode(['success'=>false,'error'=>'Failed']); }
        exit;
    }
    if ($act === 'delete_applicant') {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['success'=>false,'error'=>'POST required']); exit; }
        $id = (int)($_POST['applicant_id'] ?? 0);
        if ($id <= 0) { echo json_encode(['success'=>false,'error'=>'Invalid']); exit; }
        $an = ''; $ar = $conn->query("SELECT application_number FROM applicants WHERE id=$id");
        if ($ar && $ar->num_rows > 0) $an = $ar->fetch_assoc()['application_number'];
        $st = $conn->prepare("DELETE FROM applicants WHERE id=?"); $st->bind_param('i',$id);
        if ($st->execute()) {
            $st->close(); $conn->query("DELETE FROM applicant_requirement_status WHERE applicant_id=$id");
            $conn->query("DELETE FROM student_documents WHERE applicant_id=$id");
            $conn->query("DELETE FROM requirement_history WHERE applicant_id=$id");
            if ($an) $conn->query("DELETE FROM student_admission_tracking WHERE student_number='".$conn->real_escape_string($an)."'");
            log_activity($conn,$user_id,'Delete Applicant','admissions',$id,"Deleted #$id");
            echo json_encode(['success'=>true,'message'=>'Deleted']);
        } else { echo json_encode(['success'=>false,'error'=>'Failed']); }
        exit;
    }
    if ($act === 'get_applicant') {
        $id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
        if ($id <= 0) { echo json_encode(['success'=>false,'error'=>'Invalid']); exit; }
        $st = $conn->prepare("SELECT a.*,ap.program_name,ap.program_code FROM applicants a LEFT JOIN academic_programs ap ON a.program_id=ap.id WHERE a.id=?");
        $st->bind_param('i',$id); $st->execute(); $r = $st->get_result(); $row = $r->fetch_assoc(); $st->close();
        echo $row ? json_encode(['success'=>true,'data'=>$row]) : json_encode(['success'=>false,'error'=>'Not found']);
        exit;
    }
    if ($act === 'search_applicants') {
        $s = trim($_REQUEST['search'] ?? ''); $st = $_REQUEST['status'] ?? '';
        $pg = $_REQUEST['program'] ?? ''; $int = $_REQUEST['intake'] ?? '';
        $lim = min((int)($_REQUEST['limit'] ?? 50),200); $off = max((int)($_REQUEST['offset'] ?? 0),0);
        $w = "WHERE 1=1"; $p = []; $t = '';
        if (!empty($s)) { $w .= " AND (a.full_name LIKE ? OR a.application_number LIKE ? OR a.phone LIKE ?)"; $sv = "%$s%"; $p[] = $sv; $p[] = $sv; $p[] = $sv; $t .= 'sss'; }
        if (!empty($st)) { $w .= " AND a.status=?"; $p[] = $st; $t .= 's'; }
        if (!empty($pg)) { $w .= " AND a.program_id=?"; $p[] = (int)$pg; $t .= 'i'; }
        if (!empty($int)) { $w .= " AND a.intake=?"; $p[] = $int; $t .= 's'; }
        $sql = "SELECT a.*,ap.program_name,ap.program_code FROM applicants a LEFT JOIN academic_programs ap ON a.program_id=ap.id $w ORDER BY a.created_at DESC LIMIT $lim OFFSET $off";
        if (!empty($p)) { $st2 = $conn->prepare($sql); $st2->bind_param($t,...$p); $st2->execute(); $res = $st2->get_result(); $st2->close(); }
        else { $res = $conn->query($sql); }
        $arr = [];
        if ($res) { while ($row = $res->fetch_assoc()) { $stats = get_requirement_stats($conn,$row['id']); $row['req_completed']=$stats['completed']; $row['req_total']=$stats['total']; $row['req_percentage']=$stats['percentage']; $arr[] = $row; } }
        $csql = "SELECT COUNT(*) as total FROM applicants a $w";
        if (!empty($p)) { $st3 = $conn->prepare($csql); $st3->bind_param($t,...$p); $st3->execute(); $cr = $st3->get_result(); $st3->close(); }
        else { $cr = $conn->query($csql); }
        $total = 0; if ($cr) { $tr = $cr->fetch_assoc(); $total = (int)$tr['total']; }
        echo json_encode(['success'=>true,'data'=>$arr,'total'=>$total]); exit;
    }
    if ($act === 'get_requirements') {
        $aid = (int)($_GET['applicant_id'] ?? $_POST['applicant_id'] ?? 0);
        if ($aid <= 0) { $all = $conn->query("SELECT * FROM admission_requirements WHERE is_active=1 ORDER BY display_order");
            $reqs = []; if ($all) while ($r = $all->fetch_assoc()) $reqs[] = $r;
            echo json_encode(['success'=>true,'data'=>$reqs]); exit; }
        $st = $conn->prepare("SELECT ar.*,ars.status as current_status,ars.remarks as current_remarks,ars.submitted_at,ars.verified_at FROM admission_requirements ar LEFT JOIN applicant_requirement_status ars ON ar.id=ars.requirement_id AND ars.applicant_id=? WHERE ar.is_active=1 ORDER BY ar.display_order");
        $st->bind_param('i',$aid); $st->execute(); $res = $st->get_result(); $reqs = [];
        while ($row = $res->fetch_assoc()) $reqs[] = $row; $st->close();
        echo json_encode(['success'=>true,'data'=>$reqs]); exit;
    }
    if ($act === 'set_requirement_status') {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['success'=>false,'error'=>'POST required']); exit; }
        $aid = (int)($_POST['applicant_id'] ?? 0); $rid = (int)($_POST['requirement_id'] ?? 0);
        $status = $_POST['status'] ?? 'Not Submitted'; $rem = trim($_POST['remarks'] ?? '');
        if ($aid <= 0 || $rid <= 0) { echo json_encode(['success'=>false,'error'=>'Invalid']); exit; }
        $valid = ['Not Submitted','Submitted','Verified','Rejected','Missing'];
        if (!in_array($status,$valid)) { echo json_encode(['success'=>false,'error'=>'Invalid status']); exit; }
        $extra = ''; $et = '';
        if ($status === 'Submitted') { $extra = ",submitted_by=?,submitted_at=NOW()"; $et = 'i'; }
        elseif ($status === 'Verified') { $extra = ",verified_by=?,verified_at=NOW()"; $et = 'i'; }
        elseif ($status === 'Rejected') { $extra = ",rejected_by=?"; $et = 'i'; }
        $sql = "INSERT INTO applicant_requirement_status (applicant_id,requirement_id,status,remarks$extra) VALUES (?,?,?,?" . str_repeat(',?',substr_count($et,'i')) . ") ON DUPLICATE KEY UPDATE status=?,remarks=?,updated_at=NOW()$extra";
        $pa = [$aid,$rid,$status,$rem]; $ty = 'iis';
        if (!empty($et)) { $pa[] = $user_id; $ty .= $et; }
        $pa[] = $status; $pa[] = $rem; $ty .= 's';
        if (!empty($et)) { $pa[] = $user_id; $ty .= $et; }
        $st = $conn->prepare($sql); $st->bind_param($ty,...$pa);
        if ($st->execute()) {
            $st->close();
            $conn->query("INSERT INTO requirement_history (applicant_id,requirement_id,action,performed_by,remarks) VALUES ($aid,$rid,'Status: $status',$user_id,'".$conn->real_escape_string($rem)."')");
            $stats = get_requirement_stats($conn,$aid);
            $conn->query("UPDATE student_admission_tracking SET requirements_completed={$stats['completed']},requirements_total={$stats['total']} WHERE student_number=(SELECT application_number FROM applicants WHERE id=$aid LIMIT 1)");
            log_activity($conn,$user_id,'Update Req','requirements',$aid,"App #$aid Req #$rid -> $status");
            echo json_encode(['success'=>true,'message'=>'Updated','stats'=>$stats]);
        } else { echo json_encode(['success'=>false,'error'=>'Failed']); }
        exit;
    }
    if ($act === 'upload_document') {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['success'=>false,'error'=>'POST required']); exit; }
        $aid = (int)($_POST['applicant_id'] ?? 0); $rid = (int)($_POST['requirement_id'] ?? 0);
        $dt = trim($_POST['document_type'] ?? ''); $dtitle = trim($_POST['document_title'] ?? '');
        if ($aid <= 0) { echo json_encode(['success'=>false,'error'=>'Invalid applicant']); exit; }
        if (!isset($_FILES['document']) || $_FILES['document']['error'] !== UPLOAD_ERR_OK)
            { echo json_encode(['success'=>false,'error'=>'Upload error']); exit; }
        $file = $_FILES['document'];
        if ($file['size'] > 10*1024*1024) { echo json_encode(['success'=>false,'error'=>'Max 10MB']); exit; }
        $allowed = ['image/jpeg','image/png','image/gif','image/webp','application/pdf','application/msword','application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
        if (!in_array($file['type'],$allowed)) { echo json_encode(['success'=>false,'error'=>'Invalid type']); exit; }
        $adir = $upload_dir."applicant_$aid/"; if (!is_dir($adir)) mkdir($adir,0755,true);
        $ext = pathinfo($file['name'],PATHINFO_EXTENSION);
        $fn = 'doc_'.$rid.'_'.time().'_'.mt_rand(1000,9999).'.'.$ext; $fp = $adir.$fn;
        if (move_uploaded_file($file['tmp_name'],$fp)) {
            $rp = '../uploads/admissions/applicant_'.$aid.'/'.$fn;
            $st = $conn->prepare("INSERT INTO student_documents (applicant_id,requirement_id,document_type,document_title,file_name,file_path,file_size,mime_type,uploaded_by) VALUES (?,?,?,?,?,?,?,?,?)");
            $st->bind_param('iissssiii',$aid,$rid,$dt,$dtitle,$fn,$rp,$file['size'],$file['type'],$user_id);
            if ($st->execute()) {
                $did = $conn->insert_id; $st->close();
                if ($rid > 0) $conn->query("INSERT INTO applicant_requirement_status (applicant_id,requirement_id,status,submitted_by,submitted_at) VALUES ($aid,$rid,'Submitted',$user_id,NOW()) ON DUPLICATE KEY UPDATE status='Submitted',submitted_by=$user_id,submitted_at=NOW()");
                $conn->query("UPDATE student_admission_tracking SET documents_uploaded=(SELECT COUNT(*) FROM student_documents WHERE applicant_id=$aid AND document_status='Active') WHERE student_number=(SELECT application_number FROM applicants WHERE id=$aid LIMIT 1)");
                log_activity($conn,$user_id,'Upload Doc','documents',$did,"Uploaded: $dtitle for #$aid");
                echo json_encode(['success'=>true,'message'=>'Uploaded','doc_id'=>$did]);
            } else { echo json_encode(['success'=>false,'error'=>'DB failed']); }
        } else { echo json_encode(['success'=>false,'error'=>'Move failed']); }
        exit;
    }
    if ($act === 'get_student_profile') {
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) { echo json_encode(['success'=>false,'error'=>'Invalid']); exit; }
        $st = $conn->prepare("SELECT a.*,ap.program_name,ap.program_code,ap.duration_years,ap.department FROM applicants a LEFT JOIN academic_programs ap ON a.program_id=ap.id WHERE a.id=?");
        $st->bind_param('i',$id); $st->execute(); $applicant = $st->get_result()->fetch_assoc(); $st->close();
        if (!$applicant) { echo json_encode(['success'=>false,'error'=>'Not found']); exit; }
        $rs = $conn->prepare("SELECT ar.*,ars.status as current_status,ars.remarks as current_remarks,ars.submitted_at,ars.verified_at,ars.submitted_by,ars.verified_by FROM admission_requirements ar LEFT JOIN applicant_requirement_status ars ON ar.id=ars.requirement_id AND ars.applicant_id=? WHERE ar.is_active=1 ORDER BY ar.display_order");
        $rs->bind_param('i',$id); $rs->execute(); $requirements = $rs->get_result()->fetch_all(MYSQLI_ASSOC); $rs->close();
        $ds = $conn->prepare("SELECT * FROM student_documents WHERE applicant_id=? AND document_status='Active' ORDER BY uploaded_at DESC");
        $ds->bind_param('i',$id); $ds->execute(); $documents = $ds->get_result()->fetch_all(MYSQLI_ASSOC); $ds->close();
        $hs = $conn->prepare("SELECT * FROM requirement_history WHERE applicant_id=? ORDER BY created_at DESC LIMIT 50");
        $hs->bind_param('i',$id); $hs->execute(); $history = $hs->get_result()->fetch_all(MYSQLI_ASSOC); $hs->close();
        $stats = get_requirement_stats($conn,$id);
        echo json_encode(['success'=>true,'applicant'=>$applicant,'requirements'=>$requirements,'documents'=>$documents,'history'=>$history,'stats'=>$stats]); exit;
    }
    if ($act === 'registration_readiness') {
        $res = $conn->query("SELECT a.id,a.full_name,a.application_number,a.status,a.intake,ap.program_name,ap.program_code FROM applicants a LEFT JOIN academic_programs ap ON a.program_id=ap.id ORDER BY a.created_at DESC");
        $arr = []; if ($res) while ($row = $res->fetch_assoc()) { $stats = get_requirement_stats($conn,$row['id']); $row['req_completed']=$stats['completed']; $row['req_total']=$stats['total']; $row['req_percentage']=$stats['percentage']; $arr[] = $row; }
        echo json_encode(['success'=>true,'data'=>$arr]); exit;
    }
    if ($act === 'incomplete_list') {
        $res = $conn->query("SELECT a.id,a.full_name,a.application_number,a.status,a.intake,ap.program_name,ap.program_code FROM applicants a LEFT JOIN academic_programs ap ON a.program_id=ap.id ORDER BY a.created_at DESC");
        $inc = []; if ($res) while ($row = $res->fetch_assoc()) { $stats = get_requirement_stats($conn,$row['id']); $row['req_completed']=$stats['completed']; $row['req_total']=$stats['total']; $row['req_percentage']=$stats['percentage']; if ($stats['completed'] < $stats['total']) $inc[] = $row; }
        echo json_encode(['success'=>true,'data'=>$inc]); exit;
    }
    if ($act === 'verify_document') {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['success'=>false,'error'=>'POST required']); exit; }
        $did = (int)($_POST['doc_id'] ?? 0); $type = $_POST['action'] ?? 'verify';
        $rem = trim($_POST['remarks'] ?? ''); $ns = ($type==='reject')?'Rejected':'Verified';
        $st = $conn->prepare("UPDATE student_documents SET verification_status=?,verified_by=?,verified_at=NOW(),remarks=? WHERE id=?");
        $st->bind_param('sisi',$ns,$user_id,$rem,$did);
        if ($st->execute()) {
            $st->close();
            $dc = $conn->query("SELECT applicant_id,requirement_id FROM student_documents WHERE id=$did");
            if ($dc && $dc->num_rows > 0) { $d = $dc->fetch_assoc(); if ($d['requirement_id'] > 0) $conn->query("UPDATE applicant_requirement_status SET status='$ns',verified_by=$user_id,verified_at=NOW() WHERE applicant_id={$d['applicant_id']} AND requirement_id={$d['requirement_id']}"); }
            log_activity($conn,$user_id,'Verify Doc','documents',$did,"Doc #$did $ns");
            echo json_encode(['success'=>true,'message'=>"Document $ns"]);
        } else { echo json_encode(['success'=>false,'error'=>'Failed']); }
        exit;
    }
    if ($act === 'approve_applicant') {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['success'=>false,'error'=>'POST required']); exit; }
        $id = (int)($_POST['applicant_id'] ?? 0);
        if ($id <= 0) { echo json_encode(['success'=>false,'error'=>'Invalid']); exit; }
        $st = $conn->prepare("UPDATE applicants SET status='Approved' WHERE id=?"); $st->bind_param('i',$id);
        if ($st->execute()) { $st->close();
            $conn->query("UPDATE student_admission_tracking SET admission_status='Approved' WHERE student_number=(SELECT application_number FROM applicants WHERE id=$id LIMIT 1)");
            log_activity($conn,$user_id,'Approve','admissions',$id,"Approved #$id");
            echo json_encode(['success'=>true,'message'=>'Approved']);
        } else { echo json_encode(['success'=>false,'error'=>'Failed']); }
        exit;
    }
    if ($act === 'reject_applicant') {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['success'=>false,'error'=>'POST required']); exit; }
        $id = (int)($_POST['applicant_id'] ?? 0); $reason = trim($_POST['reason'] ?? '');
        if ($id <= 0) { echo json_encode(['success'=>false,'error'=>'Invalid']); exit; }
        $st = $conn->prepare("UPDATE applicants SET status='Rejected',rejection_reason=? WHERE id=?"); $st->bind_param('si',$reason,$id);
        if ($st->execute()) { $st->close();
            $conn->query("UPDATE student_admission_tracking SET admission_status='Rejected' WHERE student_number=(SELECT application_number FROM applicants WHERE id=$id LIMIT 1)");
            log_activity($conn,$user_id,'Reject','admissions',$id,"Rejected #$id: $reason");
            echo json_encode(['success'=>true,'message'=>'Rejected']);
        } else { echo json_encode(['success'=>false,'error'=>'Failed']); }
        exit;
    }
    if ($act === 'convert_to_student') {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['success'=>false,'error'=>'POST required']); exit; }
        $id = (int)($_POST['applicant_id'] ?? 0);
        if ($id <= 0) { echo json_encode(['success'=>false,'error'=>'Invalid']); exit; }
        $st = $conn->prepare("SELECT * FROM applicants WHERE id=?"); $st->bind_param('i',$id); $st->execute();
        $a = $st->get_result()->fetch_assoc(); $st->close();
        if (!$a) { echo json_encode(['success'=>false,'error'=>'Not found']); exit; }
        $stats = get_requirement_stats($conn,$id);
        $sn = 'STU-'.date('Y').'-'.str_pad($id,4,'0',STR_PAD_LEFT);
        $conn->query("UPDATE applicants SET status='Registered' WHERE id=$id");
        $up = $conn->prepare("UPDATE student_admission_tracking SET student_number=?,admission_status='Registered',requirements_completed=?,requirements_total=? WHERE student_number=?");
        $up->bind_param('siis',$sn,$stats['completed'],$stats['total'],$a['application_number']); $up->execute(); $up->close();
        log_activity($conn,$user_id,'Convert Student','admissions',$id,"Converted #$id to $sn");
        echo json_encode(['success'=>true,'message'=>'Registered','student_number'=>$sn]); exit;
    }
    if ($act === 'reports_data') {
        $from = $_GET['from'] ?? date('Y-01-01'); $to = $_GET['to'] ?? date('Y-m-d');
        $pid = (int)($_GET['program_id'] ?? 0);
        $wp = "WHERE a.created_at BETWEEN '$from 00:00:00' AND '$to 23:59:59'";
        if ($pid > 0) $wp .= " AND a.program_id=$pid";
        $sum = []; $r1 = $conn->query("SELECT COUNT(*) as total,SUM(CASE WHEN status='New Applicant' THEN 1 ELSE 0 END) as new_app,SUM(CASE WHEN status='Under Review' THEN 1 ELSE 0 END) as review,SUM(CASE WHEN status='Approved' THEN 1 ELSE 0 END) as approved,SUM(CASE WHEN status='Registered' THEN 1 ELSE 0 END) as registered,SUM(CASE WHEN status='Rejected' THEN 1 ELSE 0 END) as rejected FROM applicants a $wp");
        if ($r1) $sum = $r1->fetch_assoc();
        $bp = []; $r2 = $conn->query("SELECT ap.program_name,COUNT(a.id) as count FROM applicants a LEFT JOIN academic_programs ap ON a.program_id=ap.id $wp GROUP BY a.program_id,ap.program_name");
        if ($r2) while ($rw = $r2->fetch_assoc()) $bp[] = $rw;
        $bi = []; $r3 = $conn->query("SELECT a.intake,COUNT(a.id) as count FROM applicants a $wp GROUP BY a.intake");
        if ($r3) while ($rw = $r3->fetch_assoc()) $bi[] = $rw;
        $bs = []; $r4 = $conn->query("SELECT a.status,COUNT(a.id) as count FROM applicants a $wp GROUP BY a.status");
        if ($r4) while ($rw = $r4->fetch_assoc()) $bs[] = $rw;
        echo json_encode(['success'=>true,'summary'=>$sum,'by_program'=>$bp,'by_intake'=>$bi,'by_status'=>$bs,'from'=>$from,'to'=>$to]); exit;
    }
    if ($act === 'export_csv') {
        $type = $_GET['type'] ?? 'applicants'; $from = $_GET['from'] ?? date('Y-01-01'); $to = $_GET['to'] ?? date('Y-m-d');
        header('Content-Type: text/csv'); header('Content-Disposition: attachment; filename="'.$type.'_'.date('Y-m-d').'.csv"');
        $out = fopen('php://output','w');
        if ($type === 'applicants') {
            fputcsv($out,['ID','App Number','Full Name','Phone','Program','Intake','Status','Date']);
            $res = $conn->query("SELECT a.*,ap.program_name FROM applicants a LEFT JOIN academic_programs ap ON a.program_id=ap.id WHERE a.created_at BETWEEN '$from' AND '$to 23:59:59' ORDER BY a.created_at DESC");
            if ($res) while ($row = $res->fetch_assoc()) fputcsv($out,[$row['id'],$row['application_number'],$row['full_name'],$row['phone'],$row['program_name'],$row['intake'],$row['status'],$row['created_at']]);
        } elseif ($type === 'incomplete') {
            fputcsv($out,['ID','App Number','Full Name','Program','Completed','Total','Percentage']);
            $res = $conn->query("SELECT a.id,a.application_number,a.full_name,ap.program_name FROM applicants a LEFT JOIN academic_programs ap ON a.program_id=ap.id");
            if ($res) while ($row = $res->fetch_assoc()) { $st2 = get_requirement_stats($conn,$row['id']); if ($st2['completed'] < $st2['total']) fputcsv($out,[$row['id'],$row['application_number'],$row['full_name'],$row['program_name'],$st2['completed'],$st2['total'],$st2['percentage'].'%']); }
        }
        fclose($out); exit;
    }
    if ($act === 'delete_document') {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['success'=>false,'error'=>'POST required']); exit; }
        $did = (int)($_POST['doc_id'] ?? 0);
        if ($did <= 0) { echo json_encode(['success'=>false,'error'=>'Invalid']); exit; }
        $st = $conn->prepare("UPDATE student_documents SET document_status='Deleted' WHERE id=?"); $st->bind_param('i',$did);
        if ($st->execute()) { $st->close(); log_activity($conn,$user_id,'Delete Doc','documents',$did,"Deleted doc #$did"); echo json_encode(['success'=>true,'message'=>'Deleted']); }
        else echo json_encode(['success'=>false,'error'=>'Failed']); exit;
    }
    if ($act === 'get_programs') {
        $res = $conn->query("SELECT * FROM academic_programs WHERE status='Active' ORDER BY program_name");
        $p = []; if ($res) while ($r = $res->fetch_assoc()) $p[] = $r;
        echo json_encode(['success'=>true,'data'=>$p]); exit;
    }
    if ($act === 'activity_log') {
        $lim = min((int)($_REQUEST['limit'] ?? 100),500);
        $res = $conn->query("SELECT al.*,s.full_name as user_name FROM admission_activity_logs al LEFT JOIN {$staff_db}.users s ON al.user_id=s.id ORDER BY al.created_at DESC LIMIT $lim");
        $logs = []; if ($res) while ($row = $res->fetch_assoc()) $logs[] = $row;
        echo json_encode(['success'=>true,'data'=>$logs]); exit;
    }
    if ($act === 'get_document_verification') {
        $res = $conn->query("SELECT sd.*,a.full_name,a.application_number FROM student_documents sd JOIN applicants a ON sd.applicant_id=a.id WHERE sd.verification_status='Pending' AND sd.document_status='Active' ORDER BY sd.uploaded_at DESC");
        $docs = []; if ($res) while ($row = $res->fetch_assoc()) $docs[] = $row;
        echo json_encode(['success'=>true,'data'=>$docs]); exit;
    }
    if ($act === 'dashboard_stats') {
        $s = []; $r = $conn->query("SELECT COUNT(*) as total FROM applicants"); if ($r) { $row = $r->fetch_assoc(); $s['total_applicants'] = (int)$row['total']; }
        foreach (['New Applicant','Under Review','Approved','Registered','Rejected'] as $st2) {
            $esc = $conn->real_escape_string($st2); $r2 = $conn->query("SELECT COUNT(*) as cnt FROM applicants WHERE status='$esc'");
            if ($r2) { $rw = $r2->fetch_assoc(); $s[strtolower(str_replace(' ','_',$st2))] = (int)$rw['cnt']; }
        }
        $r3 = $conn->query("SELECT COUNT(*) as cnt FROM student_documents WHERE verification_status='Pending' AND document_status='Active'");
        if ($r3) { $rw = $r3->fetch_assoc(); $s['pending_docs'] = (int)$rw['cnt']; }
        $inc = 0; $all = $conn->query("SELECT id FROM applicants");
        if ($all) while ($a = $all->fetch_assoc()) { $st2 = get_requirement_stats($conn,$a['id']); if ($st2['completed'] < $st2['total']) $inc++; }
        $s['incomplete_requirements'] = $inc;
        echo json_encode(['success'=>true,'data'=>$s]); exit;
    }
    if ($act === 'intake_stats') {
        $res = $conn->query("SELECT a.intake,COUNT(a.id) as count FROM applicants a GROUP BY a.intake ORDER BY FIELD(a.intake,'January','May','August')");
        $d = []; if ($res) while ($row = $res->fetch_assoc()) $d[] = $row;
        echo json_encode(['success'=>true,'data'=>$d]); exit;
    }
    if ($act === 'program_stats') {
        $res = $conn->query("SELECT ap.program_name,COUNT(a.id) as count FROM applicants a LEFT JOIN academic_programs ap ON a.program_id=ap.id GROUP BY a.program_id,ap.program_name");
        $d = []; if ($res) while ($row = $res->fetch_assoc()) $d[] = $row;
        echo json_encode(['success'=>true,'data'=>$d]); exit;
    }
    echo json_encode(['success'=>false,'error'=>'Unknown action']); exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Director Admissions - Iganga School of Nursing and Midwifery</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
:root{--isnm-primary:#0a1628;--isnm-secondary:#1a3a5c;--isnm-accent:#2563eb;--isnm-accent-light:#3b82f6;--isnm-success:#10b981;--isnm-warning:#f59e0b;--isnm-danger:#ef4444;--isnm-info:#06b6d4;--isnm-bg:#f1f5f9;--isnm-card:#fff;--isnm-sidebar:#0a1628;--isnm-sidebar-hover:#1e3a5f;--isnm-text:#1e293b;--isnm-text-muted:#64748b;--isnm-border:#e2e8f0;--sidebar-width:260px;--topbar-height:64px}
*{font-family:'Inter',sans-serif}
body{background:var(--isnm-bg);color:var(--isnm-text);margin:0}
.isnm-sidebar{position:fixed;top:0;left:0;width:var(--sidebar-width);height:100vh;background:var(--isnm-sidebar);z-index:1040;overflow-y:auto;transition:transform .3s}
.sidebar-brand{padding:16px 20px;display:flex;align-items:center;gap:12px;border-bottom:1px solid rgba(255,255,255,.1)}
.sidebar-brand .brand-icon{width:42px;height:42px;background:var(--isnm-accent);border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:20px;color:#fff}
.sidebar-brand .brand-text{color:#fff}
.sidebar-brand .brand-text h6{margin:0;font-weight:700;font-size:14px}
.sidebar-brand .brand-text small{color:rgba(255,255,255,.6);font-size:11px}
.sidebar-menu{padding:12px 0}
.sidebar-menu .menu-label{padding:8px 20px 4px;font-size:10px;font-weight:600;text-transform:uppercase;letter-spacing:1px;color:rgba(255,255,255,.4)}
.sidebar-menu a{display:flex;align-items:center;gap:12px;padding:10px 20px;color:rgba(255,255,255,.7);text-decoration:none;font-size:13px;font-weight:500;transition:all .2s;border-left:3px solid transparent}
.sidebar-menu a:hover{background:var(--isnm-sidebar-hover);color:#fff}
.sidebar-menu a.active{background:var(--isnm-sidebar-hover);color:#fff;border-left-color:var(--isnm-accent)}
.sidebar-menu a i{font-size:18px;width:22px;text-align:center}
.isnm-topbar{position:fixed;top:0;left:var(--sidebar-width);right:0;height:var(--topbar-height);background:var(--isnm-card);border-bottom:1px solid var(--isnm-border);z-index:1039;display:flex;align-items:center;justify-content:space-between;padding:0 24px;transition:left .3s}
.main-content{margin-left:var(--sidebar-width);margin-top:var(--topbar-height);padding:24px;min-height:calc(100vh - var(--topbar-height))}
.page-header{margin-bottom:24px}
.page-header h4{font-weight:700;color:var(--isnm-primary);margin:0}
.page-header p{color:var(--isnm-text-muted);margin:4px 0 0;font-size:14px}
.stat-card{background:var(--isnm-card);border-radius:12px;padding:20px;border:1px solid var(--isnm-border);transition:transform .2s,box-shadow .2s}
.stat-card:hover{transform:translateY(-2px);box-shadow:0 8px 25px rgba(0,0,0,.08)}
.stat-card .stat-icon{width:48px;height:48px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:22px}
.stat-card .stat-value{font-size:28px;font-weight:700;color:var(--isnm-primary)}
.stat-card .stat-label{font-size:13px;color:var(--isnm-text-muted)}
.data-card{background:var(--isnm-card);border-radius:12px;border:1px solid var(--isnm-border);overflow:hidden}
.card-header-custom{padding:16px 20px;border-bottom:1px solid var(--isnm-border);display:flex;align-items:center;justify-content:space-between}
.card-header-custom h6{margin:0;font-weight:600;font-size:15px}
.card-body-custom{padding:20px}
.table thead th{background:#f8fafc;border-bottom:2px solid var(--isnm-border);font-size:12px;font-weight:600;text-transform:uppercase;letter-spacing:.5px;color:var(--isnm-text-muted);padding:12px 16px}
.table tbody td{padding:12px 16px;vertical-align:middle;font-size:13px}
.table tbody tr:hover{background:#f8fafc}
.badge-status{padding:5px 12px;border-radius:20px;font-size:11px;font-weight:600;display:inline-block}
.badge-new{background:#dbeafe;color:#1d4ed8}.badge-review{background:#fef3c7;color:#b45309}
.badge-approved{background:#d1fae5;color:#047857}.badge-registered{background:#ede9fe;color:#6d28d9}
.badge-rejected{background:#fee2e2;color:#dc2626}.badge-submitted{background:#dbeafe;color:#1d4ed8}
.badge-verified{background:#d1fae5;color:#047857}.badge-missing{background:#fee2e2;color:#dc2626}
.badge-not-submitted{background:#f1f5f9;color:#64748b}
.progress-mini{height:6px;border-radius:3px;background:#e2e8f0}
.progress-mini .progress-bar{border-radius:3px}
.btn-isnm{background:var(--isnm-accent);border-color:var(--isnm-accent);color:#fff;font-weight:500;font-size:13px}
.btn-isnm:hover{background:var(--isnm-accent-light);border-color:var(--isnm-accent-light);color:#fff}
.modal-header-custom{background:var(--isnm-primary);color:#fff;padding:16px 24px}
.modal-header-custom .btn-close{filter:brightness(0) invert(1)}
.filter-bar{background:var(--isnm-card);border-radius:12px;padding:16px 20px;border:1px solid var(--isnm-border);margin-bottom:20px}
.toast-container{position:fixed;top:80px;right:24px;z-index:9999}
.requirement-item{display:flex;align-items:center;justify-content:space-between;padding:12px 16px;border:1px solid var(--isnm-border);border-radius:8px;margin-bottom:8px;transition:background .15s}
.requirement-item:hover{background:#f8fafc}
.profile-header{background:linear-gradient(135deg,var(--isnm-primary),var(--isnm-secondary));border-radius:12px;padding:30px;color:#fff;margin-bottom:24px}
.profile-avatar{width:80px;height:80px;border-radius:50%;background:rgba(255,255,255,.2);display:flex;align-items:center;justify-content:center;font-size:32px;font-weight:700}
.chart-container{position:relative;height:280px}
.loading-spinner{display:inline-block;width:20px;height:20px;border:2px solid #e2e8f0;border-top-color:var(--isnm-accent);border-radius:50%;animation:spin .8s linear infinite}
@keyframes spin{to{transform:rotate(360deg)}}
@media(max-width:991.98px){.isnm-sidebar{transform:translateX(-100%)}.isnm-sidebar.show{transform:translateX(0)}.isnm-topbar{left:0}.main-content{margin-left:0}}
.sidebar-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:1038}
.sidebar-overlay.show{display:block}
@media print{.isnm-sidebar,.isnm-topbar,.no-print,.filter-bar,.sidebar-overlay{display:none!important}.main-content{margin:0!important;padding:10px!important}}
</style>
</head>
<body>
<div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>
<aside class="isnm-sidebar" id="sidebar">
<div class="sidebar-brand">
<div class="brand-icon"><i class="bi bi-mortarboard-fill"></i></div>
<div class="brand-text"><h6>ISNM</h6><small>Director Admissions</small></div>
</div>
<nav class="sidebar-menu">
<div class="menu-label">Main</div>
<a href="?page=overview" class="<?=$current_page==='overview'?'active':''?>"><i class="bi bi-grid-1x2-fill"></i> Dashboard Overview</a>
<a href="?page=add_student" class="<?=$current_page==='add_student'?'active':''?>"><i class="bi bi-person-plus-fill"></i> Add New Student</a>
<a href="?page=applicant_records" class="<?=$current_page==='applicant_records'?'active':''?>"><i class="bi bi-people-fill"></i> Applicant Records</a>
<div class="menu-label">Management</div>
<a href="?page=requirement_tracking" class="<?=$current_page==='requirement_tracking'?'active':''?>"><i class="bi bi-clipboard-check"></i> Requirement Tracking</a>
<a href="?page=incomplete_requirements" class="<?=$current_page==='incomplete_requirements'?'active':''?>"><i class="bi bi-exclamation-triangle-fill"></i> Incomplete Requirements</a>
<a href="?page=document_verification" class="<?=$current_page==='document_verification'?'active':''?>"><i class="bi bi-file-earmark-check"></i> Document Verification</a>
<a href="?page=direct_registration" class="<?=$current_page==='direct_registration'?'active':''?>"><i class="bi bi-person-check-fill"></i> Direct Registration</a>
<div class="menu-label">Reports</div>
<a href="?page=reports" class="<?=$current_page==='reports'?'active':''?>"><i class="bi bi-bar-chart-line-fill"></i> Reports</a>
<a href="?page=activity_log" class="<?=$current_page==='activity_log'?'active':''?>"><i class="bi bi-clock-history"></i> Activity Log</a>
</nav>
</aside>
<header class="isnm-topbar" id="topbar">
<div class="d-flex align-items-center gap-3">
<button class="btn btn-sm btn-outline-secondary d-lg-none" onclick="toggleSidebar()"><i class="bi bi-list"></i></button>
<h6 style="margin:0;font-weight:600;font-size:15px;" id="pageTitle">Dashboard</h6>
</div>
<div class="d-flex align-items-center gap-3">
<span class="text-muted" style="font-size:13px;"><i class="bi bi-person-circle me-1"></i><?=$user_name?></span>
<span class="badge" style="background:var(--isnm-accent);color:#fff;font-size:11px;"><?=$user_role?></span>
</div>
</header>
<main class="main-content" id="mainContent">
<?php if ($current_page === 'overview'): ?>
<div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-2">
<div><h4>Dashboard Overview</h4><p>Admissions overview for Iganga School of Nursing and Midwifery</p></div>
<div class="d-flex gap-2">
<a href="?page=add_student" class="btn btn-isnm btn-sm"><i class="bi bi-plus-lg me-1"></i>Add Student</a>
<button class="btn btn-outline-secondary btn-sm" onclick="exportCSV('applicants')"><i class="bi bi-download me-1"></i>Export</button>
</div>
</div>
<div class="row g-3 mb-4" id="statsCards">
<div class="col-xl-3 col-md-6"><div class="stat-card"><div class="d-flex justify-content-between align-items-start"><div><div class="stat-value" id="statTotal">-</div><div class="stat-label">Total Applicants</div></div><div class="stat-icon" style="background:#dbeafe;color:#1d4ed8;"><i class="bi bi-people-fill"></i></div></div></div></div>
<div class="col-xl-3 col-md-6"><div class="stat-card"><div class="d-flex justify-content-between align-items-start"><div><div class="stat-value" id="statNew">-</div><div class="stat-label">New Applicants</div></div><div class="stat-icon" style="background:#e0f2fe;color:#0284c7;"><i class="bi bi-person-plus"></i></div></div></div></div>
<div class="col-xl-3 col-md-6"><div class="stat-card"><div class="d-flex justify-content-between align-items-start"><div><div class="stat-value" id="statReview">-</div><div class="stat-label">Under Review</div></div><div class="stat-icon" style="background:#fef3c7;color:#b45309;"><i class="bi bi-hourglass-split"></i></div></div></div></div>
<div class="col-xl-3 col-md-6"><div class="stat-card"><div class="d-flex justify-content-between align-items-start"><div><div class="stat-value" id="statApproved">-</div><div class="stat-label">Approved</div></div><div class="stat-icon" style="background:#d1fae5;color:#047857;"><i class="bi bi-check-circle"></i></div></div></div></div>
<div class="col-xl-3 col-md-6"><div class="stat-card"><div class="d-flex justify-content-between align-items-start"><div><div class="stat-value" id="statRegistered">-</div><div class="stat-label">Registered</div></div><div class="stat-icon" style="background:#ede9fe;color:#6d28d9;"><i class="bi bi-person-check"></i></div></div></div></div>
<div class="col-xl-3 col-md-6"><div class="stat-card"><div class="d-flex justify-content-between align-items-start"><div><div class="stat-value" id="statRejected">-</div><div class="stat-label">Rejected</div></div><div class="stat-icon" style="background:#fee2e2;color:#dc2626;"><i class="bi bi-x-circle"></i></div></div></div></div>
<div class="col-xl-3 col-md-6"><div class="stat-card"><div class="d-flex justify-content-between align-items-start"><div><div class="stat-value" id="statIncomplete">-</div><div class="stat-label">Incomplete Req.</div></div><div class="stat-icon" style="background:#fff7ed;color:#ea580c;"><i class="bi bi-exclamation-triangle"></i></div></div></div></div>
<div class="col-xl-3 col-md-6"><div class="stat-card"><div class="d-flex justify-content-between align-items-start"><div><div class="stat-value" id="statPendingDocs">-</div><div class="stat-label">Pending Verification</div></div><div class="stat-icon" style="background:#f0fdf4;color:#15803d;"><i class="bi bi-file-earmark-check"></i></div></div></div></div>
</div>
<div class="row g-3">
<div class="col-xl-4"><div class="data-card"><div class="card-header-custom"><h6><i class="bi bi-pie-chart me-2"></i>Status Distribution</h6></div><div class="card-body-custom"><div class="chart-container"><canvas id="statusChart"></canvas></div></div></div></div>
<div class="col-xl-4"><div class="data-card"><div class="card-header-custom"><h6><i class="bi bi-calendar me-2"></i>By Intake</h6></div><div class="card-body-custom"><div class="chart-container"><canvas id="intakeChart"></canvas></div></div></div></div>
<div class="col-xl-4"><div class="data-card"><div class="card-header-custom"><h6><i class="bi bi-book me-2"></i>By Program</h6></div><div class="card-body-custom"><div class="chart-container"><canvas id="programChart"></canvas></div></div></div></div>
</div>
<div class="row g-3 mt-3">
<!-- Website Submissions -->
<div class="col-xl-4">
<div class="card border-0 shadow-sm mb-3">
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
<div class="col-xl-8"><div class="data-card"><div class="card-header-custom"><h6><i class="bi bi-clock-history me-2"></i>Recent Activity</h6><a href="?page=activity_log" class="btn btn-sm btn-outline-primary">View All</a></div><div class="card-body-custom p-0"><div class="table-responsive"><table class="table table-hover mb-0"><thead><tr><th>Action</th><th>User</th><th>Module</th><th>Description</th><th>Time</th></tr></thead><tbody id="recentActivityTable"></tbody></table></div></div></div></div>
</div>
<?php endif; ?>
<?php if ($current_page === 'add_student'): ?>
<div class="page-header"><h4>Add New Student</h4><p>Register a new applicant into the system</p></div>
<div class="data-card"><div class="card-header-custom"><h6><i class="bi bi-person-plus me-2"></i>New Applicant Form</h6></div>
<div class="card-body-custom">
<form id="addStudentForm" onsubmit="return submitAddStudent(event)">
<div class="row g-3">
<div class="col-md-6"><label class="form-label fw-semibold">Full Name <span class="text-danger">*</span></label><input type="text" class="form-control" name="full_name" required placeholder="Enter full name"></div>
<div class="col-md-6"><label class="form-label fw-semibold">Other Names</label><input type="text" class="form-control" name="other_names" placeholder="Other names"></div>
<div class="col-md-4"><label class="form-label fw-semibold">Date of Birth</label><input type="date" class="form-control" name="date_of_birth"></div>
<div class="col-md-4"><label class="form-label fw-semibold">Gender <span class="text-danger">*</span></label><select class="form-select" name="gender" required><option value="Female">Female</option><option value="Male">Male</option><option value="Other">Other</option></select></div>
<div class="col-md-4"><label class="form-label fw-semibold">Phone</label><input type="text" class="form-control" name="phone" placeholder="Phone number"></div>
<div class="col-md-6"><label class="form-label fw-semibold">Email</label><input type="email" class="form-control" name="email" placeholder="Email address"></div>
<div class="col-md-6"><label class="form-label fw-semibold">Address</label><input type="text" class="form-control" name="address" placeholder="Physical address"></div>
<div class="col-12"><hr class="my-2"><h6 class="text-muted">Guardian Information</h6></div>
<div class="col-md-4"><label class="form-label fw-semibold">Guardian Name</label><input type="text" class="form-control" name="guardian_name" placeholder="Guardian name"></div>
<div class="col-md-4"><label class="form-label fw-semibold">Guardian Phone</label><input type="text" class="form-control" name="guardian_phone" placeholder="Guardian phone"></div>
<div class="col-md-4"><label class="form-label fw-semibold">Relationship</label><input type="text" class="form-control" name="guardian_relationship" placeholder="e.g. Parent, Sibling"></div>
<div class="col-12"><hr class="my-2"><h6 class="text-muted">Academic Information</h6></div>
<div class="col-md-4"><label class="form-label fw-semibold">Program <span class="text-danger">*</span></label>
<select class="form-select" name="program_id" required><option value="">-- Select Program --</option>
<?php foreach ($program_list as $prog): ?><option value="<?= (int)$prog['id'] ?>"><?= htmlspecialchars($prog['program_name']) ?> (<?= htmlspecialchars($prog['program_code']) ?>)</option><?php endforeach; ?>
</select></div>
<div class="col-md-4"><label class="form-label fw-semibold">Intake <span class="text-danger">*</span></label><select class="form-select" name="intake" required><option value="January">January</option><option value="May">May</option><option value="August">August</option></select></div>
<div class="col-md-4"><label class="form-label fw-semibold">Admission Date</label><input type="date" class="form-control" name="admission_date" value="<?= date('Y-m-d') ?>"></div>
</div>
<div class="mt-4 d-flex gap-2">
<button type="submit" class="btn btn-isnm" id="btnAddStudent"><i class="bi bi-save me-1"></i>Save Applicant</button>
<button type="reset" class="btn btn-outline-secondary"><i class="bi bi-arrow-counterclockwise me-1"></i>Reset</button>
</div>
</form>
</div></div>
<?php endif; ?>
<?php if ($current_page === 'applicant_records'): ?>
<div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-2">
<div><h4>Applicant Records</h4><p>Manage all applicant records</p></div>
<div class="d-flex gap-2">
<a href="?page=add_student" class="btn btn-isnm btn-sm"><i class="bi bi-plus-lg me-1"></i>Add New</a>
<button class="btn btn-outline-secondary btn-sm" onclick="exportCSV('applicants')"><i class="bi bi-download me-1"></i>Export CSV</button>
</div>
</div>
<div class="filter-bar">
<div class="row g-2 align-items-end">
<div class="col-md-3"><label class="form-label fw-semibold" style="font-size:12px;">Search</label><input type="text" class="form-control form-control-sm" id="searchApplicants" placeholder="Name, phone, email..." oninput="loadApplicants()"></div>
<div class="col-md-2"><label class="form-label fw-semibold" style="font-size:12px;">Status</label>
<select class="form-select form-select-sm" id="filterStatus" onchange="loadApplicants()"><option value="">All Statuses</option><option value="New Applicant">New Applicant</option><option value="Under Review">Under Review</option><option value="Approved">Approved</option><option value="Registered">Registered</option><option value="Rejected">Rejected</option></select></div>
<div class="col-md-2"><label class="form-label fw-semibold" style="font-size:12px;">Intake</label>
<select class="form-select form-select-sm" id="filterIntake" onchange="loadApplicants()"><option value="">All Intakes</option><option value="January">January</option><option value="May">May</option><option value="August">August</option></select></div>
<div class="col-md-2"><label class="form-label fw-semibold" style="font-size:12px;">Program</label>
<select class="form-select form-select-sm" id="filterProgram" onchange="loadApplicants()"><option value="">All Programs</option>
<?php foreach ($program_list as $prog): ?><option value="<?= (int)$prog['id'] ?>"><?= htmlspecialchars($prog['program_code']) ?></option><?php endforeach; ?>
</select></div>
<div class="col-md-3 text-end"><label class="form-label d-block" style="font-size:12px;">&nbsp;</label><button class="btn btn-sm btn-outline-secondary" onclick="resetFilters()"><i class="bi bi-x-lg me-1"></i>Clear</button></div>
</div></div>
<div class="data-card"><div class="card-body-custom p-0"><div class="table-responsive">
<table class="table table-hover mb-0">
<thead><tr><th>#</th><th>App. No.</th><th>Full Name</th><th>Phone</th><th>Program</th><th>Intake</th><th>Requirements</th><th>Status</th><th>Date</th><th>Actions</th></tr></thead>
<tbody id="applicantsTableBody"><tr><td colspan="10" class="text-center py-4 text-muted"><div class="loading-spinner"></div> Loading...</td></tr></tbody>
</table></div></div></div>
<?php endif; ?>
<?php if ($current_page === 'applicant_profile'): ?>
<div class="page-header"><div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
<div><h4>Applicant Profile</h4><p>View and manage applicant details</p></div>
<div class="d-flex gap-2">
<a href="?page=applicant_records" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i>Back</a>
<button class="btn btn-outline-secondary btn-sm" onclick="window.print()"><i class="bi bi-printer me-1"></i>Print</button>
</div></div></div>
<input type="hidden" id="profileApplicantId" value="<?= (int)($_GET['id'] ?? 0) ?>">
<div id="profileContent"><div class="text-center py-5"><div class="loading-spinner" style="width:40px;height:40px;border-width:3px;"></div><p class="mt-3 text-muted">Loading profile...</p></div></div>
<?php endif; ?>

<?php if ($current_page === 'requirement_tracking'): ?>
<div class="page-header"><h4>Requirement Tracking</h4><p>Track requirement completion across all applicants</p></div>
<div class="filter-bar"><div class="row g-2 align-items-end">
<div class="col-md-3"><label class="form-label fw-semibold" style="font-size:12px;">Search</label><input type="text" class="form-control form-control-sm" id="reqSearch" placeholder="Search applicant..." oninput="loadRequirementTracking()"></div>
<div class="col-md-3"><label class="form-label fw-semibold" style="font-size:12px;">Status Filter</label>
<select class="form-select form-select-sm" id="reqStatusFilter" onchange="loadRequirementTracking()"><option value="">All Statuses</option><option value="Not Submitted">Not Submitted</option><option value="Submitted">Submitted</option><option value="Verified">Verified</option><option value="Rejected">Rejected</option></select></div>
</div></div>
<div class="data-card"><div class="card-body-custom p-0"><div class="table-responsive">
<table class="table table-hover mb-0"><thead id="reqTrackingHead"></thead>
<tbody id="reqTrackingBody"><tr><td colspan="100%" class="text-center py-4 text-muted"><div class="loading-spinner"></div> Loading...</td></tr></tbody>
</table></div></div></div>
<?php endif; ?>

<?php if ($current_page === 'incomplete_requirements'): ?>
<div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-2">
<div><h4>Incomplete Requirements</h4><p>Applicants who have not completed all requirements</p></div>
<button class="btn btn-outline-secondary btn-sm" onclick="exportCSV('incomplete')"><i class="bi bi-download me-1"></i>Export CSV</button>
</div>
<div class="data-card"><div class="card-body-custom p-0"><div class="table-responsive">
<table class="table table-hover mb-0">
<thead><tr><th>#</th><th>App. No.</th><th>Full Name</th><th>Program</th><th>Intake</th><th>Progress</th><th>Status</th><th>Actions</th></tr></thead>
<tbody id="incompleteTableBody"><tr><td colspan="8" class="text-center py-4 text-muted"><div class="loading-spinner"></div> Loading...</td></tr></tbody>
</table></div></div></div>
<?php endif; ?>

<?php if ($current_page === 'document_verification'): ?>
<div class="page-header"><h4>Document Verification</h4><p>Review and verify uploaded documents</p></div>
<div class="data-card"><div class="card-body-custom p-0"><div class="table-responsive">
<table class="table table-hover mb-0">
<thead><tr><th>#</th><th>Applicant</th><th>Doc Type</th><th>Title</th><th>File</th><th>Uploaded</th><th>Actions</th></tr></thead>
<tbody id="docVerificationBody"><tr><td colspan="7" class="text-center py-4 text-muted"><div class="loading-spinner"></div> Loading...</td></tr></tbody>
</table></div></div></div>
<?php endif; ?>
<?php if ($current_page === 'direct_registration'): ?>
<div class="page-header"><h4>Direct Registration</h4><p>Register a student directly, bypassing the application process</p></div>
<div class="data-card"><div class="card-header-custom"><h6><i class="bi bi-person-check me-2"></i>Direct Student Registration</h6></div>
<div class="card-body-custom">
<div class="alert alert-info mb-3" style="font-size:13px;"><i class="bi bi-info-circle me-1"></i> This registers the student directly with status "Registered". All requirements will be auto-created as "Not Submitted".</div>
<form id="directRegForm" onsubmit="return submitDirectRegistration(event)">
<div class="row g-3">
<div class="col-md-6"><label class="form-label fw-semibold">Full Name <span class="text-danger">*</span></label><input type="text" class="form-control" name="full_name" required></div>
<div class="col-md-6"><label class="form-label fw-semibold">Other Names</label><input type="text" class="form-control" name="other_names"></div>
<div class="col-md-4"><label class="form-label fw-semibold">Date of Birth</label><input type="date" class="form-control" name="date_of_birth"></div>
<div class="col-md-4"><label class="form-label fw-semibold">Gender <span class="text-danger">*</span></label><select class="form-select" name="gender" required><option value="Female">Female</option><option value="Male">Male</option></select></div>
<div class="col-md-4"><label class="form-label fw-semibold">Phone</label><input type="text" class="form-control" name="phone"></div>
<div class="col-md-6"><label class="form-label fw-semibold">Email</label><input type="email" class="form-control" name="email"></div>
<div class="col-md-6"><label class="form-label fw-semibold">Address</label><input type="text" class="form-control" name="address"></div>
<div class="col-md-4"><label class="form-label fw-semibold">Guardian Name</label><input type="text" class="form-control" name="guardian_name"></div>
<div class="col-md-4"><label class="form-label fw-semibold">Guardian Phone</label><input type="text" class="form-control" name="guardian_phone"></div>
<div class="col-md-4"><label class="form-label fw-semibold">Relationship</label><input type="text" class="form-control" name="guardian_relationship"></div>
<div class="col-md-4"><label class="form-label fw-semibold">Program <span class="text-danger">*</span></label>
<select class="form-select" name="program_id" required><option value="">-- Select --</option>
<?php foreach ($program_list as $prog): ?><option value="<?= (int)$prog['id'] ?>"><?= htmlspecialchars($prog['program_name']) ?></option><?php endforeach; ?>
</select></div>
<div class="col-md-4"><label class="form-label fw-semibold">Intake <span class="text-danger">*</span></label><select class="form-select" name="intake" required><option value="January">January</option><option value="May">May</option><option value="August">August</option></select></div>
<div class="col-md-4"><label class="form-label fw-semibold">Admission Date <span class="text-danger">*</span></label><input type="date" class="form-control" name="admission_date" value="<?= date('Y-m-d') ?>" required></div>
</div>
<div class="mt-4"><button type="submit" class="btn btn-isnm" id="btnDirectReg"><i class="bi bi-person-check me-1"></i>Register Student</button></div>
</form></div></div>
<?php endif; ?>

<?php if ($current_page === 'reports'): ?>
<div class="page-header"><h4>Reports</h4><p>Generate and export admission reports</p></div>
<div class="filter-bar"><div class="row g-2 align-items-end">
<div class="col-md-2"><label class="form-label fw-semibold" style="font-size:12px;">From</label><input type="date" class="form-control form-control-sm" id="reportFrom" value="<?= date('Y-01-01') ?>"></div>
<div class="col-md-2"><label class="form-label fw-semibold" style="font-size:12px;">To</label><input type="date" class="form-control form-control-sm" id="reportTo" value="<?= date('Y-m-d') ?>"></div>
<div class="col-md-2"><label class="form-label fw-semibold" style="font-size:12px;">Program</label>
<select class="form-select form-select-sm" id="reportProgram"><option value="">All Programs</option>
<?php foreach ($program_list as $prog): ?><option value="<?= (int)$prog['id'] ?>"><?= htmlspecialchars($prog['program_code']) ?></option><?php endforeach; ?>
</select></div>
<div class="col-md-4"><label class="form-label d-block" style="font-size:12px;">&nbsp;</label>
<button class="btn btn-isnm btn-sm" onclick="loadReports()"><i class="bi bi-search me-1"></i>Generate</button>
<button class="btn btn-outline-secondary btn-sm ms-1" onclick="exportCSV('applicants')"><i class="bi bi-download me-1"></i>CSV</button>
</div></div></div>
<div class="row g-3" id="reportResults"><div class="col-12"><div class="data-card"><div class="card-body-custom"><p class="text-muted text-center mb-0">Select date range and click Generate.</p></div></div></div></div>
<?php endif; ?>

<?php if ($current_page === 'activity_log'): ?>
<div class="page-header"><h4>Activity Log</h4><p>Track all system activities and changes</p></div>
<div class="data-card"><div class="card-body-custom p-0"><div class="table-responsive">
<table class="table table-hover mb-0">
<thead><tr><th>#</th><th>Date & Time</th><th>User</th><th>Action</th><th>Module</th><th>Description</th></tr></thead>
<tbody id="activityLogBody"><tr><td colspan="6" class="text-center py-4 text-muted"><div class="loading-spinner"></div> Loading...</td></tr></tbody>
</table></div></div></div>
<?php endif; ?>
</main>
<div class="modal fade" id="editApplicantModal" tabindex="-1"><div class="modal-dialog modal-lg"><div class="modal-content">
<div class="modal-header-custom"><h6 class="modal-title"><i class="bi bi-pencil-square me-2"></i>Edit Applicant</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body"><form id="editApplicantForm">
<input type="hidden" name="applicant_id" id="edit_applicant_id">
<div class="row g-3">
<div class="col-md-6"><label class="form-label fw-semibold">Full Name *</label><input type="text" class="form-control" name="full_name" id="edit_full_name" required></div>
<div class="col-md-6"><label class="form-label fw-semibold">Other Names</label><input type="text" class="form-control" name="other_names" id="edit_other_names"></div>
<div class="col-md-4"><label class="form-label fw-semibold">Date of Birth</label><input type="date" class="form-control" name="date_of_birth" id="edit_dob"></div>
<div class="col-md-4"><label class="form-label fw-semibold">Gender</label><select class="form-select" name="gender" id="edit_gender"><option value="Female">Female</option><option value="Male">Male</option><option value="Other">Other</option></select></div>
<div class="col-md-4"><label class="form-label fw-semibold">Phone</label><input type="text" class="form-control" name="phone" id="edit_phone"></div>
<div class="col-md-6"><label class="form-label fw-semibold">Email</label><input type="email" class="form-control" name="email" id="edit_email"></div>
<div class="col-md-6"><label class="form-label fw-semibold">Address</label><input type="text" class="form-control" name="address" id="edit_address"></div>
<div class="col-md-4"><label class="form-label fw-semibold">Guardian Name</label><input type="text" class="form-control" name="guardian_name" id="edit_gn"></div>
<div class="col-md-4"><label class="form-label fw-semibold">Guardian Phone</label><input type="text" class="form-control" name="guardian_phone" id="edit_gp"></div>
<div class="col-md-4"><label class="form-label fw-semibold">Relationship</label><input type="text" class="form-control" name="guardian_relationship" id="edit_gr"></div>
<div class="col-md-4"><label class="form-label fw-semibold">Program</label>
<select class="form-select" name="program_id" id="edit_pid"><option value="">-- Select --</option>
<?php foreach ($program_list as $prog): ?><option value="<?= (int)$prog['id'] ?>"><?= htmlspecialchars($prog['program_name']) ?></option><?php endforeach; ?>
</select></div>
<div class="col-md-4"><label class="form-label fw-semibold">Intake</label><select class="form-select" name="intake" id="edit_intake"><option value="January">January</option><option value="May">May</option><option value="August">August</option></select></div>
<div class="col-md-4"><label class="form-label fw-semibold">Admission Date</label><input type="date" class="form-control" name="admission_date" id="edit_ad"></div>
</div></form></div>
<div class="modal-footer"><button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button><button type="button" class="btn btn-isnm btn-sm" onclick="submitEditApplicant()"><i class="bi bi-save me-1"></i>Save</button></div>
</div></div></div>

<div class="modal fade" id="rejectModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
<div class="modal-header-custom" style="background:var(--isnm-danger);"><h6 class="modal-title"><i class="bi bi-x-circle me-2"></i>Reject Applicant</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body">
<input type="hidden" id="reject_applicant_id">
<div class="mb-3"><label class="form-label fw-semibold">Reason for Rejection *</label><textarea class="form-control" id="reject_reason" rows="4" required placeholder="Enter reason..."></textarea></div>
</div>
<div class="modal-footer"><button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button><button type="button" class="btn btn-danger btn-sm" onclick="submitReject()"><i class="bi bi-x-lg me-1"></i>Reject</button></div>
</div></div></div>

<div class="modal fade" id="uploadDocModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
<div class="modal-header-custom"><h6 class="modal-title"><i class="bi bi-upload me-2"></i>Upload Document</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body">
<input type="hidden" id="upload_applicant_id">
<div class="mb-3"><label class="form-label fw-semibold">Requirement</label><select class="form-select" id="upload_requirement_id"></select></div>
<div class="mb-3"><label class="form-label fw-semibold">Document Type</label><input type="text" class="form-control" id="upload_doc_type" placeholder="e.g. Certificate"></div>
<div class="mb-3"><label class="form-label fw-semibold">Document Title</label><input type="text" class="form-control" id="upload_doc_title" placeholder="e.g. Birth Certificate"></div>
<div class="mb-3"><label class="form-label fw-semibold">Select File *</label><input type="file" class="form-control" id="upload_file" accept=".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx"><div class="form-text">Max 10MB. JPG, PNG, GIF, PDF, DOC, DOCX</div></div>
</div>
<div class="modal-footer"><button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button><button type="button" class="btn btn-isnm btn-sm" id="btnUploadDoc" onclick="submitUploadDoc()"><i class="bi bi-upload me-1"></i>Upload</button></div>
</div></div></div>
<div class="toast-container" id="toastContainer"></div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
<script>
const BASE='director-admissions.php';
const PROGRAMS_DATA=<?=json_encode($program_list)?>;
let statusChart,intakeChart,programChart;

function toggleSidebar(){document.getElementById('sidebar').classList.toggle('show');document.getElementById('sidebarOverlay').classList.toggle('show');}

function showToast(msg,type='success'){
const icons={success:'check-circle-fill',error:'exclamation-circle-fill',info:'info-circle-fill',warning:'exclamation-triangle-fill'};
const colors={success:'#10b981',error:'#ef4444',info:'#06b6d4',warning:'#f59e0b'};
const id='toast_'+Date.now();
const html=`<div id="${id}" class="toast align-items-center border-0 shadow" role="alert" style="background:${colors[type]||colors.info};color:#fff;min-width:300px;"><div class="d-flex"><div class="toast-body"><i class="bi bi-${icons[type]||icons.info} me-2"></i>${msg}</div><button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button></div></div>`;
document.getElementById('toastContainer').insertAdjacentHTML('beforeend',html);
const t=new bootstrap.Toast(document.getElementById(id),{delay:4000});t.show();
document.getElementById(id).addEventListener('hidden.bs.toast',()=>document.getElementById(id).remove());
}

function showLoading(btn,loading){
if(loading){btn.disabled=true;btn.dataset.orig=btn.innerHTML;btn.innerHTML='<span class="loading-spinner me-1"></span>Processing...';}
else{btn.disabled=false;if(btn.dataset.orig)btn.innerHTML=btn.dataset.orig;}
}

function fmtStatus(s){
const cls={'New Applicant':'badge-new','Under Review':'badge-review','Approved':'badge-approved','Registered':'badge-registered','Rejected':'badge-rejected','Submitted':'badge-submitted','Verified':'badge-verified','Missing':'badge-missing','Not Submitted':'badge-not-submitted','Pending':'badge-review'};
return `<span class="badge-status ${cls[s]||'badge-not-submitted'}">${s}</span>`;
}

function fmtDate(d){if(!d)return '-';return new Date(d).toLocaleDateString('en-GB',{day:'2-digit',month:'short',year:'numeric'});}
function fmtSize(b){if(!b)return '0 B';const k=1024,s=['B','KB','MB','GB'];const i=Math.floor(Math.log(b)/Math.log(k));return parseFloat((b/Math.pow(k,i)).toFixed(1))+' '+s[i];}

function resetFilters(){
document.getElementById('searchApplicants').value='';
document.getElementById('filterStatus').value='';
document.getElementById('filterIntake').value='';
document.getElementById('filterProgram').value='';
loadApplicants();
}

function exportCSV(type){
const from=document.getElementById('reportFrom')?.value||'2024-01-01';
const to=document.getElementById('reportTo')?.value||new Date().toISOString().split('T')[0];
window.open(`${BASE}?ajax=export_csv&type=${type}&from=${from}&to=${to}`,'_blank');
}

async function apiGet(params){const qs=new URLSearchParams(params).toString();const r=await fetch(`${BASE}?ajax=1&${qs}`);return await r.json();}
async function apiPost(action,data){data.append('ajax',action);const r=await fetch(BASE,{method:'POST',body:data});return await r.json();}

async function loadDashboardStats(){
try{
const[statsRes,intakeRes,progRes,logRes]=await Promise.all([
apiGet({action:'dashboard_stats'}),apiGet({action:'intake_stats'}),
apiGet({action:'program_stats'}),apiGet({action:'activity_log',limit:10})
]);
if(statsRes.success){
const d=statsRes.data;
document.getElementById('statTotal').textContent=d.total_applicants||0;
document.getElementById('statNew').textContent=d.new_applicant||0;
document.getElementById('statReview').textContent=d.under_review||0;
document.getElementById('statApproved').textContent=d.approved||0;
document.getElementById('statRegistered').textContent=d.registered||0;
document.getElementById('statRejected').textContent=d.rejected||0;
document.getElementById('statIncomplete').textContent=d.incomplete_requirements||0;
document.getElementById('statPendingDocs').textContent=d.pending_docs||0;

const labels=['New','Under Review','Approved','Registered','Rejected'];
const values=[d.new_applicant||0,d.under_review||0,d.approved||0,d.registered||0,d.rejected||0];
const colors=['#3b82f6','#f59e0b','#10b981','#8b5cf6','#ef4444'];
if(statusChart)statusChart.destroy();
statusChart=new Chart(document.getElementById('statusChart'),{type:'pie',data:{labels,datasets:[{data:values,backgroundColor:colors}]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{position:'bottom',labels:{boxWidth:12,font:{size:11}}}}}});
}
if(intakeRes.success&&intakeRes.data.length){
const labels=intakeRes.data.map(x=>x.intake);const values=intakeRes.data.map(x=>x.count);
if(intakeChart)intakeChart.destroy();
intakeChart=new Chart(document.getElementById('intakeChart'),{type:'bar',data:{labels,datasets:[{label:'Applicants',data:values,backgroundColor:['#2563eb','#f59e0b','#10b981']}]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false}}}});
}
if(progRes.success&&progRes.data.length){
const labels=progRes.data.map(x=>x.program_name||'Unknown');const values=progRes.data.map(x=>x.count);
const pcolors=['#2563eb','#10b981','#f59e0b','#ef4444','#8b5cf6','#06b6d4'];
if(programChart)programChart.destroy();
programChart=new Chart(document.getElementById('programChart'),{type:'doughnut',data:{labels,datasets:[{data:values,backgroundColor:pcolors}]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{position:'bottom',labels:{boxWidth:12,font:{size:11}}}}}});
}
if(logRes.success&&logRes.data.length){
const tbody=document.getElementById('recentActivityTable');
if(tbody)tbody.innerHTML=logRes.data.map(l=>`<tr><td>${l.action}</td><td>${l.user_name||'System'}</td><td>${l.module||'-'}</td><td style="max-width:250px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">${l.description||'-'}</td><td style="white-space:nowrap;">${fmtDate(l.created_at)}</td></tr>`).join('');
}
}catch(e){console.error('Dashboard error:',e);}
}

async function submitAddStudent(e){
e.preventDefault();
const btn=document.getElementById('btnAddStudent');
showLoading(btn,true);
const fd=new FormData(document.getElementById('addStudentForm'));
try{
const res=await apiPost('add_student',fd);
if(res.success){showToast(res.message,'success');document.getElementById('addStudentForm').reset();
if(confirm('Applicant added. View profile now?'))window.location.href=`${BASE}?page=applicant_profile&id=${res.applicant_id}`;
}else showToast(res.error||'Failed','error');
}catch(e){showToast('Network error','error');}
showLoading(btn,false);return false;
}

async function loadApplicants(){
const tbody=document.getElementById('applicantsTableBody');
if(!tbody)return;
tbody.innerHTML='<tr><td colspan="10" class="text-center py-4"><div class="loading-spinner"></div></td></tr>';
try{
const fd=new FormData();
fd.append('search',document.getElementById('searchApplicants')?.value||'');
fd.append('status',document.getElementById('filterStatus')?.value||'');
fd.append('intake',document.getElementById('filterIntake')?.value||'');
fd.append('program',document.getElementById('filterProgram')?.value||'');
fd.append('limit',200);fd.append('offset',0);
const res=await apiPost('search_applicants',fd);
if(res.success&&res.data.length){
tbody.innerHTML=res.data.map((a,i)=>`<tr style="cursor:pointer;" onclick="viewProfile(${a.id})">
<td>${i+1}</td><td><code>${a.application_number}</code></td>
<td><strong>${a.full_name}</strong>${a.other_names?' ('+a.other_names+')':''}</td>
<td>${a.phone||'-'}</td><td><small>${a.program_name||'-'}</small></td><td>${a.intake}</td>
<td><div class="d-flex align-items-center gap-2"><div class="progress-mini flex-grow-1" style="width:80px;"><div class="progress-bar" style="width:${a.req_percentage}%;background:${a.req_percentage===100?'var(--isnm-success)':a.req_percentage>=50?'var(--isnm-warning)':'var(--isnm-danger)'}"></div></div><small class="text-muted">${a.req_completed}/${a.req_total}</small></div></td>
<td>${fmtStatus(a.status)}</td><td style="white-space:nowrap;">${fmtDate(a.created_at)}</td>
<td onclick="event.stopPropagation()"><div class="btn-group btn-group-sm">
<button class="btn btn-outline-primary" title="View" onclick="viewProfile(${a.id})"><i class="bi bi-eye"></i></button>
<button class="btn btn-outline-secondary" title="Edit" onclick="openEditModal(${a.id})"><i class="bi bi-pencil"></i></button>
<button class="btn btn-outline-danger" title="Delete" onclick="deleteApplicant(${a.id})"><i class="bi bi-trash"></i></button>
</div></td></tr>`).join('');
}else tbody.innerHTML='<tr><td colspan="10" class="text-center py-4 text-muted"><i class="bi bi-inbox d-block" style="font-size:32px;"></i>No applicants found</td></tr>';
}catch(e){tbody.innerHTML='<tr><td colspan="10" class="text-center text-danger py-4">Error loading data</td></tr>';}
}

function viewProfile(id){window.location.href=`${BASE}?page=applicant_profile&id=${id}`;}

async function deleteApplicant(id){
if(!confirm('Delete this applicant? This cannot be undone.'))return;
try{const fd=new FormData();fd.append('applicant_id',id);
const res=await apiPost('delete_applicant',fd);
if(res.success){showToast('Deleted','success');loadApplicants();}else showToast(res.error||'Failed','error');
}catch(e){showToast('Network error','error');}
}

async function openEditModal(id){
try{const res=await apiGet({action:'get_applicant',id});
if(res.success){const d=res.data;
document.getElementById('edit_applicant_id').value=d.id;
document.getElementById('edit_full_name').value=d.full_name||'';
document.getElementById('edit_other_names').value=d.other_names||'';
document.getElementById('edit_dob').value=d.date_of_birth||'';
document.getElementById('edit_gender').value=d.gender||'Female';
document.getElementById('edit_phone').value=d.phone||'';
document.getElementById('edit_email').value=d.email||'';
document.getElementById('edit_address').value=d.address||'';
document.getElementById('edit_gn').value=d.guardian_name||'';
document.getElementById('edit_gp').value=d.guardian_phone||'';
document.getElementById('edit_gr').value=d.guardian_relationship||'';
document.getElementById('edit_pid').value=d.program_id||'';
document.getElementById('edit_intake').value=d.intake||'January';
document.getElementById('edit_ad').value=d.admission_date||'';
new bootstrap.Modal(document.getElementById('editApplicantModal')).show();
}else showToast(res.error||'Failed','error');
}catch(e){showToast('Network error','error');}
}

async function submitEditApplicant(){
const fd=new FormData(document.getElementById('editApplicantForm'));
try{const res=await apiPost('edit_applicant',fd);
if(res.success){showToast('Updated','success');bootstrap.Modal.getInstance(document.getElementById('editApplicantModal')).hide();loadApplicants();}
else showToast(res.error||'Failed','error');
}catch(e){showToast('Network error','error');}
}

async function loadProfile(){
const id=document.getElementById('profileApplicantId')?.value;
if(!id)return;
const container=document.getElementById('profileContent');
try{
const res=await apiGet({action:'get_student_profile',id});
if(!res.success){container.innerHTML='<div class="alert alert-danger">Applicant not found.</div>';return;}
const a=res.applicant,reqs=res.requirements,docs=res.documents,hist=res.history,stats=res.stats;
const rp=stats.percentage;
let html=`
<div class="profile-header"><div class="d-flex align-items-center gap-4">
<div class="profile-avatar">${a.full_name.charAt(0)}</div>
<div class="flex-grow-1"><h4 class="mb-1">${a.full_name}${a.other_names?' ('+a.other_names+')':''}</h4>
<div class="d-flex flex-wrap gap-3" style="font-size:13px;opacity:.8;">
<span><i class="bi bi-hash me-1"></i>${a.application_number}</span>
<span><i class="bi bi-book me-1"></i>${a.program_name||'-'}</span>
<span><i class="bi bi-calendar me-1"></i>${a.intake} Intake</span>
<span><i class="bi bi-clock me-1"></i>Added ${fmtDate(a.created_at)}</span>
</div></div><div>${fmtStatus(a.status)}</div></div></div>

<div class="row g-3 mb-4">
<div class="col-xl-3 col-md-6"><div class="stat-card"><div class="stat-value">${rp}%</div><div class="stat-label">Requirements Complete</div>
<div class="progress-mini mt-2" style="height:8px;"><div class="progress-bar" style="width:${rp}%;background:${rp===100?'var(--isnm-success)':rp>=50?'var(--isnm-warning)':'var(--isnm-danger)'}"></div></div></div></div>
<div class="col-xl-3 col-md-6"><div class="stat-card"><div class="stat-value">${stats.completed}/${stats.total}</div><div class="stat-label">Requirements Met</div></div></div>
<div class="col-xl-3 col-md-6"><div class="stat-card"><div class="stat-value">${docs.length}</div><div class="stat-label">Documents Uploaded</div></div></div>
<div class="col-xl-3 col-md-6"><div class="stat-card"><div class="stat-value">${hist.length}</div><div class="stat-label">Activity Records</div></div></div>
</div>

<div class="row g-3">
<div class="col-lg-6">
<div class="data-card mb-3"><div class="card-header-custom"><h6><i class="bi bi-person me-2"></i>Personal Information</h6></div>
<div class="card-body-custom"><div class="row g-2" style="font-size:13px;">
<div class="col-6"><strong>Full Name:</strong><br>${a.full_name}</div>
<div class="col-6"><strong>Other Names:</strong><br>${a.other_names||'-'}</div>
<div class="col-6"><strong>Date of Birth:</strong><br>${fmtDate(a.date_of_birth)}</div>
<div class="col-6"><strong>Gender:</strong><br>${a.gender}</div>
<div class="col-6"><strong>Phone:</strong><br>${a.phone||'-'}</div>
<div class="col-6"><strong>Email:</strong><br>${a.email||'-'}</div>
<div class="col-12"><strong>Address:</strong><br>${a.address||'-'}</div>
</div></div></div>

<div class="data-card mb-3"><div class="card-header-custom"><h6><i class="bi bi-people me-2"></i>Guardian Information</h6></div>
<div class="card-body-custom"><div class="row g-2" style="font-size:13px;">
<div class="col-4"><strong>Name:</strong><br>${a.guardian_name||'-'}</div>
<div class="col-4"><strong>Phone:</strong><br>${a.guardian_phone||'-'}</div>
<div class="col-4"><strong>Relationship:</strong><br>${a.guardian_relationship||'-'}</div>
</div></div></div>

<div class="data-card mb-3"><div class="card-header-custom d-flex justify-content-between">
<h6 class="mb-0"><i class="bi bi-folder me-2"></i>Uploaded Documents</h6>
<button class="btn btn-sm btn-isnm" onclick="openUploadModal(${a.id})"><i class="bi bi-upload me-1"></i>Upload</button></div>
<div class="card-body-custom p-0">${docs.length?`<div class="table-responsive"><table class="table table-hover mb-0">
<thead><tr><th>Title</th><th>Type</th><th>Size</th><th>Status</th><th>Date</th><th>Actions</th></tr></thead>
<tbody>${docs.map(d=>`<tr><td>${d.document_title||d.file_name}</td><td>${d.document_type||'-'}</td><td>${fmtSize(d.file_size)}</td><td>${fmtStatus(d.verification_status)}</td><td>${fmtDate(d.uploaded_at)}</td>
<td><a href="${d.file_path}" target="_blank" class="btn btn-sm btn-outline-primary" title="View"><i class="bi bi-eye"></i></a>
<button class="btn btn-sm btn-outline-danger" onclick="deleteDocument(${d.id},${a.id})" title="Delete"><i class="bi bi-trash"></i></button></td></tr>`).join('')}</tbody></table></div>`:'<div class="empty-state py-3"><i class="bi bi-file-earmark-x d-block"></i>No documents uploaded yet.</div>'}</div></div>
</div>

<div class="col-lg-6">
<div class="data-card mb-3"><div class="card-header-custom d-flex justify-content-between">
<h6 class="mb-0"><i class="bi bi-clipboard-check me-2"></i>Requirements Checklist</h6>
<div class="btn-group btn-group-sm">
<button class="btn btn-outline-success" onclick="approveApplicant(${a.id})" ${a.status==='Approved'||a.status==='Registered'?'disabled':''}><i class="bi bi-check-lg me-1"></i>Approve</button>
<button class="btn btn-outline-danger" onclick="openRejectModal(${a.id})" ${a.status==='Rejected'?'disabled':''}><i class="bi bi-x-lg me-1"></i>Reject</button>
<button class="btn btn-outline-primary" onclick="convertToStudent(${a.id})" ${a.status==='Approved'?'':'disabled'}><i class="bi bi-person-check me-1"></i>Register</button>
</div></div>
<div class="card-body-custom" style="max-height:500px;overflow-y:auto;">
${reqs.map(r=>{const st=r.current_status||'Not Submitted';const sc={'Submitted':'badge-submitted','Verified':'badge-verified','Rejected':'badge-missing','Missing':'badge-missing'}[st]||'badge-not-submitted';
return `<div class="requirement-item"><div><strong style="font-size:13px;">${r.requirement_name}</strong>${r.is_mandatory?'<span class="text-danger ms-1">*</span>':'<span class="text-muted ms-1" style="font-size:11px;">(Optional)</span>'}
${r.current_remarks?'<br><small class="text-muted">'+r.current_remarks+'</small>':''}</div>
<div class="d-flex align-items-center gap-2">
<select class="form-select form-select-sm" style="width:140px;font-size:12px;" onchange="setReqStatus(${a.id},${r.id},this.value)">
${['Not Submitted','Submitted','Verified','Rejected','Missing'].map(s=>`<option value="${s}" ${st===s?'selected':''}>${s}</option>`).join('')}</select>
<span class="badge-status ${sc}" style="font-size:10px;">${st}</span></div></div>`;}).join('')}
</div></div>

<div class="data-card"><div class="card-header-custom"><h6><i class="bi bi-clock-history me-2"></i>Activity History</h6></div>
<div class="card-body-custom" style="max-height:300px;overflow-y:auto;">
${hist.length?hist.map(h=>`<div class="d-flex gap-2 mb-2" style="font-size:12px;">
<div><i class="bi bi-circle-fill" style="font-size:6px;margin-top:6px;color:var(--isnm-accent);"></i></div>
<div><strong>${h.action}</strong><br>${h.remarks||'-'}<br><small class="text-muted">${fmtDate(h.created_at)}</small></div></div>`).join(''):'<p class="text-muted text-center mb-0">No activity recorded.</p>'}
</div></div>
</div></div>`;
container.innerHTML=html;
document.getElementById('pageTitle').textContent=a.full_name;
}catch(e){container.innerHTML='<div class="alert alert-danger">Error loading profile.</div>';console.error(e);}
}

async function setReqStatus(aid,rid,status){
try{const fd=new FormData();fd.append('applicant_id',aid);fd.append('requirement_id',rid);fd.append('status',status);fd.append('remarks','');
const res=await apiPost('set_requirement_status',fd);
if(res.success){showToast('Status updated','success');loadProfile();}else showToast(res.error||'Failed','error');
}catch(e){showToast('Network error','error');}
}

async function approveApplicant(id){
if(!confirm('Approve this applicant?'))return;
try{const fd=new FormData();fd.append('applicant_id',id);
const res=await apiPost('approve_applicant',fd);
if(res.success){showToast('Approved','success');loadProfile();}else showToast(res.error||'Failed','error');
}catch(e){showToast('Network error','error');}
}

function openRejectModal(id){
document.getElementById('reject_applicant_id').value=id;
document.getElementById('reject_reason').value='';
new bootstrap.Modal(document.getElementById('rejectModal')).show();
}

async function submitReject(){
const id=document.getElementById('reject_applicant_id').value;
const reason=document.getElementById('reject_reason').value.trim();
if(!reason){showToast('Enter a reason','warning');return;}
try{const fd=new FormData();fd.append('applicant_id',id);fd.append('reason',reason);
const res=await apiPost('reject_applicant',fd);
if(res.success){showToast('Rejected','success');bootstrap.Modal.getInstance(document.getElementById('rejectModal')).hide();loadProfile();}
else showToast(res.error||'Failed','error');
}catch(e){showToast('Network error','error');}
}

async function convertToStudent(id){
if(!confirm('Convert approved applicant to registered student?'))return;
try{const fd=new FormData();fd.append('applicant_id',id);
const res=await apiPost('convert_to_student',fd);
if(res.success){showToast('Registered: '+res.student_number,'success');loadProfile();}
else showToast(res.error||'Failed','error');
}catch(e){showToast('Network error','error');}
}

function openUploadModal(applicantId){
document.getElementById('upload_applicant_id').value=applicantId;
document.getElementById('upload_file').value='';
document.getElementById('upload_doc_type').value='';
document.getElementById('upload_doc_title').value='';
const sel=document.getElementById('upload_requirement_id');
sel.innerHTML='<option value="">-- None (General) --</option>';
fetch(`${BASE}?ajax=1&action=get_requirements`).then(r=>r.json()).then(res=>{
if(res.success)res.data.forEach(r=>{sel.innerHTML+=`<option value="${r.id}">${r.requirement_name}</option>`;});
});
new bootstrap.Modal(document.getElementById('uploadDocModal')).show();
}

async function submitUploadDoc(){
const aid=document.getElementById('upload_applicant_id').value;
const file=document.getElementById('upload_file').files[0];
if(!file){showToast('Select a file','warning');return;}
const btn=document.getElementById('btnUploadDoc');showLoading(btn,true);
const fd=new FormData();
fd.append('applicant_id',aid);
fd.append('requirement_id',document.getElementById('upload_requirement_id').value||0);
fd.append('document_type',document.getElementById('upload_doc_type').value);
fd.append('document_title',document.getElementById('upload_doc_title').value||file.name);
fd.append('document',file);
try{const res=await apiPost('upload_document',fd);
if(res.success){showToast('Uploaded','success');bootstrap.Modal.getInstance(document.getElementById('uploadDocModal')).hide();loadProfile();}
else showToast(res.error||'Failed','error');
}catch(e){showToast('Network error','error');}
showLoading(btn,false);
}

async function deleteDocument(did,aid){
if(!confirm('Delete this document?'))return;
try{const fd=new FormData();fd.append('doc_id',did);
const res=await apiPost('delete_document',fd);
if(res.success){showToast('Deleted','success');loadProfile();}else showToast(res.error||'Failed','error');
}catch(e){showToast('Network error','error');}
}

async function loadRequirementTracking(){
const head=document.getElementById('reqTrackingHead');
const body=document.getElementById('reqTrackingBody');
if(!head||!body)return;
let reqs=[];
try{const res=await apiGet({action:'get_requirements'});if(res.success)reqs=res.data;}catch(e){}
let headers='<tr><th>#</th><th>Applicant</th><th>Program</th><th>Intake</th>';
reqs.forEach(r=>{headers+=`<th style="writing-mode:vertical-rl;text-orientation:mixed;font-size:10px;max-width:40px;" title="${r.requirement_name}">${r.requirement_name.substring(0,12)}</th>`;});
headers+='<th>Progress</th></tr>';head.innerHTML=headers;
try{
const search=document.getElementById('reqSearch')?.value||'';
const statusFilter=document.getElementById('reqStatusFilter')?.value||'';
const res=await apiGet({action:'search_applicants',limit:200,search:search});
if(res.success&&res.data.length){
body.innerHTML=res.data.map((a,i)=>{
let cells=`<td>${i+1}</td><td><a href="${BASE}?page=applicant_profile&id=${a.id}" class="text-decoration-none fw-semibold">${a.full_name}</a></td>
<td><small>${a.program_name||'-'}</small></td><td>${a.intake}</td>`;
reqs.forEach(r=>{
const st=r.current_status||'Not Submitted';
const icons={'Not Submitted':'<i class="bi bi-dash-circle text-muted"></i>','Submitted':'<i class="bi bi-clock text-primary"></i>','Verified':'<i class="bi bi-check-circle-fill text-success"></i>','Rejected':'<i class="bi bi-x-circle-fill text-danger"></i>','Missing':'<i class="bi bi-exclamation-circle text-warning"></i>'};
cells+=`<td class="text-center" title="${r.requirement_name}: ${st}">${icons[st]||icons['Not Submitted']}</td>`;
});
cells+=`<td><div class="d-flex align-items-center gap-1"><div class="progress-mini" style="width:60px;"><div class="progress-bar" style="width:${a.req_percentage}%"></div></div><small>${a.req_completed}/${a.req_total}</small></div></td>`;
return `<tr>${cells}</tr>`;
}).join('');}else body.innerHTML='<tr><td colspan="100%" class="text-center py-4 text-muted">No applicants found.</td></tr>';
}catch(e){body.innerHTML='<tr><td colspan="100%" class="text-center text-danger py-4">Error loading data</td></tr>';}
}

async function loadIncompleteRequirements(){
const tbody=document.getElementById('incompleteTableBody');
if(!tbody)return;
try{const res=await apiGet({action:'incomplete_list'});
if(res.success&&res.data.length){
tbody.innerHTML=res.data.map((a,i)=>`<tr>
<td>${i+1}</td><td><code>${a.application_number}</code></td>
<td><a href="${BASE}?page=applicant_profile&id=${a.id}" class="text-decoration-none fw-semibold">${a.full_name}</a></td>
<td><small>${a.program_name||'-'}</small></td><td>${a.intake}</td>
<td><div class="d-flex align-items-center gap-2"><div class="progress-mini flex-grow-1" style="width:100px;height:8px;"><div class="progress-bar" style="width:${a.req_percentage}%;background:${a.req_percentage>=75?'var(--isnm-warning)':'var(--isnm-danger)'}"></div></div><strong style="font-size:12px;">${a.req_completed}/${a.req_total}</strong><small class="text-muted">(${a.req_percentage}%)</small></div></td>
<td>${fmtStatus(a.status)}</td>
<td><a href="${BASE}?page=applicant_profile&id=${a.id}" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></a></td></tr>`).join('');}else tbody.innerHTML='<tr><td colspan="8" class="text-center py-4 text-muted"><i class="bi bi-check-circle d-block" style="font-size:32px;"></i>All requirements complete!</td></tr>';
}catch(e){tbody.innerHTML='<tr><td colspan="8" class="text-center text-danger py-4">Error loading data</td></tr>';}
}

async function loadDocVerification(){
const tbody=document.getElementById('docVerificationBody');
if(!tbody)return;
try{const res=await apiGet({action:'get_document_verification'});
if(res.success&&res.data.length){
tbody.innerHTML=res.data.map((d,i)=>`<tr>
<td>${i+1}</td><td><strong>${d.full_name}</strong><br><small class="text-muted">${d.application_number}</small></td>
<td>${d.document_type||'-'}</td><td>${d.document_title||d.file_name}</td>
<td><a href="${d.file_path}" target="_blank" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></a></td>
<td style="white-space:nowrap;">${fmtDate(d.uploaded_at)}</td>
<td><div class="btn-group btn-group-sm">
<button class="btn btn-outline-success" onclick="verifyDoc(${d.id},'verify')" title="Verify"><i class="bi bi-check-lg"></i></button>
<button class="btn btn-outline-danger" onclick="verifyDoc(${d.id},'reject')" title="Reject"><i class="bi bi-x-lg"></i></button>
</div></td></tr>`).join('');}else tbody.innerHTML='<tr><td colspan="7" class="text-center py-4 text-muted"><i class="bi bi-check-circle d-block" style="font-size:32px;"></i>No pending documents</td></tr>';
}catch(e){tbody.innerHTML='<tr><td colspan="7" class="text-center text-danger py-4">Error loading data</td></tr>';}
}

async function verifyDoc(did,action){
const msg=action==='verify'?'Verify this document?':'Reject this document?';
if(!confirm(msg))return;
const remarks=prompt('Remarks (optional):')||'';
try{const fd=new FormData();fd.append('doc_id',did);fd.append('action',action);fd.append('remarks',remarks);
const res=await apiPost('verify_document',fd);
if(res.success){showToast(res.message,'success');loadDocVerification();}else showToast(res.error||'Failed','error');
}catch(e){showToast('Network error','error');}
}

async function submitDirectRegistration(e){
e.preventDefault();
const btn=document.getElementById('btnDirectReg');showLoading(btn,true);
const fd=new FormData(document.getElementById('directRegForm'));
try{const res=await apiPost('add_student',fd);
if(res.success){showToast('Student registered!','success');document.getElementById('directRegForm').reset();
if(confirm('View profile?'))window.location.href=`${BASE}?page=applicant_profile&id=${res.applicant_id}`;
}else showToast(res.error||'Failed','error');
}catch(e){showToast('Network error','error');}
showLoading(btn,false);return false;
}

async function loadReports(){
const from=document.getElementById('reportFrom')?.value||'';
const to=document.getElementById('reportTo')?.value||'';
const pid=document.getElementById('reportProgram')?.value||'';
const container=document.getElementById('reportResults');
try{const res=await apiGet({action:'reports_data',from:from,to:to,program_id:pid});
if(res.success){
const s=res.summary||{};
container.innerHTML=`
<div class="col-xl-4"><div class="stat-card"><div class="d-flex justify-content-between"><div><div class="stat-value">${s.total||0}</div><div class="stat-label">Total Applicants</div></div><div class="stat-icon" style="background:#dbeafe;color:#1d4ed8;"><i class="bi bi-people-fill"></i></div></div></div></div>
<div class="col-xl-4"><div class="stat-card"><div class="d-flex justify-content-between"><div><div class="stat-value">${s.new_app||0}</div><div class="stat-label">New</div></div><div class="stat-icon" style="background:#e0f2fe;color:#0284c7;"><i class="bi bi-person-plus"></i></div></div></div></div>
<div class="col-xl-4"><div class="stat-card"><div class="d-flex justify-content-between"><div><div class="stat-value">${s.review||0}</div><div class="stat-label">Under Review</div></div><div class="stat-icon" style="background:#fef3c7;color:#b45309;"><i class="bi bi-hourglass-split"></i></div></div></div></div>
<div class="col-xl-4"><div class="stat-card"><div class="d-flex justify-content-between"><div><div class="stat-value">${s.approved||0}</div><div class="stat-label">Approved</div></div><div class="stat-icon" style="background:#d1fae5;color:#047857;"><i class="bi bi-check-circle"></i></div></div></div></div>
<div class="col-xl-4"><div class="stat-card"><div class="d-flex justify-content-between"><div><div class="stat-value">${s.registered||0}</div><div class="stat-label">Registered</div></div><div class="stat-icon" style="background:#ede9fe;color:#6d28d9;"><i class="bi bi-person-check"></i></div></div></div></div>
<div class="col-xl-4"><div class="stat-card"><div class="d-flex justify-content-between"><div><div class="stat-value">${s.rejected||0}</div><div class="stat-label">Rejected</div></div><div class="stat-icon" style="background:#fee2e2;color:#dc2626;"><i class="bi bi-x-circle"></i></div></div></div></div>

<div class="col-xl-6 mt-3"><div class="data-card"><div class="card-header-custom"><h6><i class="bi bi-book me-2"></i>By Program</h6></div><div class="card-body-custom p-0"><table class="table table-hover mb-0"><thead><tr><th>Program</th><th>Count</th></tr></thead><tbody>
${(res.by_program||[]).map(p=>`<tr><td>${p.program_name||'Unknown'}</td><td><strong>${p.count}</strong></td></tr>`).join('')}
${(res.by_program||[]).length===0?'<tr><td colspan="2" class="text-center text-muted">No data</td></tr>':''}
</tbody></table></div></div></div>

<div class="col-xl-6 mt-3"><div class="data-card"><div class="card-header-custom"><h6><i class="bi bi-calendar me-2"></i>By Intake</h6></div><div class="card-body-custom p-0"><table class="table table-hover mb-0"><thead><tr><th>Intake</th><th>Count</th></tr></thead><tbody>
${(res.by_intake||[]).map(p=>`<tr><td>${p.intake}</td><td><strong>${p.count}</strong></td></tr>`).join('')}
${(res.by_intake||[]).length===0?'<tr><td colspan="2" class="text-center text-muted">No data</td></tr>':''}
</tbody></table></div></div></div>`;
}else showToast('Error loading reports','error');
}catch(e){showToast('Network error','error');}
}

async function loadActivityLog(){
const tbody=document.getElementById('activityLogBody');
if(!tbody)return;
try{const res=await apiGet({action:'activity_log',limit:200});
if(res.success&&res.data.length){
tbody.innerHTML=res.data.map((l,i)=>`<tr>
<td>${i+1}</td><td style="white-space:nowrap;">${fmtDate(l.created_at)}</td>
<td>${l.user_name||'System'}</td><td>${l.action}</td><td>${l.module||'-'}</td>
<td style="max-width:300px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">${l.description||'-'}</td></tr>`).join('');}else tbody.innerHTML='<tr><td colspan="6" class="text-center py-4 text-muted">No activity recorded.</td></tr>';
}catch(e){tbody.innerHTML='<tr><td colspan="6" class="text-center text-danger py-4">Error loading data</td></tr>';}
}

document.addEventListener('DOMContentLoaded',function(){
const page=document.getElementById('mainContent');
if(!page)return;
if(page.querySelector('#statsCards'))loadDashboardStats();
if(page.querySelector('#applicantsTableBody'))loadApplicants();
if(page.querySelector('#profileApplicantId'))loadProfile();
if(page.querySelector('#reqTrackingHead'))loadRequirementTracking();
if(page.querySelector('#incompleteTableBody'))loadIncompleteRequirements();
if(page.querySelector('#docVerificationBody'))loadDocVerification();
if(page.querySelector('#activityLogBody'))loadActivityLog();
});
</script>
</body>
</html>
