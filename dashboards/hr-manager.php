<?php
/**
 * HR Manager Dashboard — Complete 13-Module Interface
 * Modules: Staff Records, Recruitment, Attendance, Payroll Support,
 * Performance, Training, Disciplinary, Contracts, Communication,
 * Reports, RBAC, Self-Service, Integration
 */
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
require_once __DIR__ . '/../includes/enterprise_auth.php';
require_once __DIR__ . '/../includes/hr_functions.php';

$ctx          = bootstrapStaffDashboard(['hr manager', 'hr', 'director general', 'ceo']);
$auth_service = $ctx['auth'];
$user         = $ctx['user'];
$staff_conn   = $ctx['staff'];
$students_conn = $ctx['students'];
$website_conn  = $ctx['website'];
$user_id      = (int)($_SESSION['user_id'] ?? 0);
$user_role    = $_SESSION['role'] ?? '';

$page  = $_GET['page'] ?? 'overview';
$sub   = $_GET['sub'] ?? '';
$isSuper = $auth_service->hasFullInstitutionAccess($user_role);

// ── Handle POST actions ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $staff_conn) {
    $action = $_POST['action'] ?? '';
    $id = (int)($_POST['id'] ?? 0);

    if ($action === 'add_staff') {
        $fn = trim($_POST['full_name'] ?? ''); $em = trim($_POST['email'] ?? '');
        $pos = trim($_POST['position'] ?? ''); $dept = trim($_POST['department'] ?? '');
        $rid = (int)($_POST['role_id'] ?? 0); $ph = trim($_POST['phone'] ?? '');
        $cat = trim($_POST['staff_category'] ?? 'non-teaching');
        $gender = trim($_POST['gender'] ?? ''); $qual = trim($_POST['highest_qualification'] ?? '');
        $nin = trim($_POST['nin'] ?? ''); $exp = (int)($_POST['year_of_experience'] ?? 0);
        $dob = $_POST['date_of_birth'] ?? '';
        if ($fn && $em) {
            $sid = 'STAFF'.date('Y').str_pad(mt_rand(1,9999),4,'0',STR_PAD_LEFT);
            $plainpw = bin2hex(random_bytes(8));
            $hash = password_hash($plainpw, PASSWORD_BCRYPT);
            $stmt = $staff_conn->prepare("INSERT INTO staff (staff_id,full_name,email,password,phone,position,department,role_id,staff_category,gender,highest_qualification,nin,year_of_experience,date_of_birth,status,hire_date) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,'Active',CURDATE())");
            if ($stmt) { $stmt->bind_param('sssssssisssssi',$sid,$fn,$em,$hash,$ph,$pos,$dept,$rid,$cat,$gender,$qual,$nin,$exp,$dob); $stmt->execute(); $_SESSION['success'] = "Staff $fn added. Temporary password: $plainpw"; }
        }
        header('Location: hr-manager.php?page=staff'); exit;
    }
    if ($action === 'edit_staff') {
        $fn = trim($_POST['full_name'] ?? ''); $em = trim($_POST['email'] ?? '');
        $pos = trim($_POST['position'] ?? ''); $dept = trim($_POST['department'] ?? '');
        $rid = (int)($_POST['role_id'] ?? 0); $ph = trim($_POST['phone'] ?? '');
        $st = trim($_POST['status'] ?? 'Active');
        $cat = trim($_POST['staff_category'] ?? 'non-teaching');
        $gender = trim($_POST['gender'] ?? ''); $qual = trim($_POST['highest_qualification'] ?? '');
        $nin = trim($_POST['nin'] ?? ''); $exp = (int)($_POST['year_of_experience'] ?? 0);
        $dob = $_POST['date_of_birth'] ?? '';
        $resp = ['success' => false, 'error' => 'Invalid data'];
        if ($id && $fn && $em) {
            $stmt = $staff_conn->prepare("UPDATE staff SET full_name=?,email=?,phone=?,position=?,department=?,role_id=?,status=?,staff_category=?,gender=?,highest_qualification=?,nin=?,year_of_experience=?,date_of_birth=? WHERE id=?");
            if ($stmt) { $stmt->bind_param('sssssissssssi',$fn,$em,$ph,$pos,$dept,$rid,$st,$cat,$gender,$qual,$nin,$exp,$dob,$id); $resp = ['success'=>$stmt->execute(),'error'=>$stmt->error]; $stmt->close(); }
        }
        header('Content-Type: application/json'); echo json_encode($resp); exit;
    }
    if ($action === 'delete_staff') {
        $resp = ['success' => false, 'error' => 'Invalid'];
        if ($id && $staff_conn) { $stmt = $staff_conn->prepare("DELETE FROM staff WHERE id=?"); if ($stmt) { $stmt->bind_param('i',$id); $resp = ['success'=>$stmt->execute(),'error'=>$stmt->error]; $stmt->close(); } }
        header('Content-Type: application/json'); echo json_encode($resp); exit;
    }
    if ($action === 'approve_leave' || $action === 'reject_leave') {
        $lid = (int)($_POST['leave_id'] ?? 0);
        $status = $action === 'approve_leave' ? 'approved' : 'rejected';
        if ($lid && $staff_conn) { $stmt = $staff_conn->prepare("UPDATE leave_requests SET status=?, reviewed_by=?, updated_at=NOW() WHERE id=?"); if ($stmt) { $stmt->bind_param('sii',$status,$user_id,$lid); $stmt->execute(); } $_SESSION['success'] = "Leave $status."; }
        header('Location: hr-manager.php?page=attendance#leave'); exit;
    }
    if ($action === 'post_vacancy') {
        $title = trim($_POST['title'] ?? ''); $dept = trim($_POST['department'] ?? '');
        $desc = trim($_POST['description'] ?? ''); $req = trim($_POST['requirements'] ?? '');
        $salary = trim($_POST['salary_range'] ?? ''); $close = $_POST['closing_date'] ?? '';
        if ($title && $staff_conn) { $stmt = $staff_conn->prepare("INSERT INTO job_vacancies (title,department_id,description,requirements,salary_range,status,posted_date,closing_date) VALUES (?,?,?,?,?,'open',CURDATE(),?)"); if ($stmt) { $stmt->bind_param('sissss',$title,$dept,$desc,$req,$salary,$close); $stmt->execute(); $_SESSION['success'] = 'Vacancy posted.'; } }
        header('Location: hr-manager.php?page=recruitment'); exit;
    }
    if ($action === 'shortlist') {
        $appId = (int)($_POST['application_id'] ?? 0);
        if ($appId && $staff_conn) { $st=$staff_conn->prepare("UPDATE job_applications SET application_status='shortlisted' WHERE id=?"); if($st){$st->bind_param('i',$appId);$st->execute();$st->close();$_SESSION['success']='Applicant shortlisted.';} }
        header('Location: hr-manager.php?page=recruitment'); exit;
    }
    if ($action === 'record_attendance') {
        $sid = (int)($_POST['staff_id'] ?? 0); $date = $_POST['date'] ?? date('Y-m-d');
        $status = trim($_POST['attendance_status'] ?? 'present');
        if ($sid && $staff_conn) {
            $ck=$staff_conn->prepare("SELECT id FROM staff_attendance WHERE staff_id=? AND date=?");
            if($ck){$ck->bind_param('is',$sid,$date);$ck->execute();$exists=$ck->get_result()->num_rows>0;$ck->close();}
            if(!empty($exists)){
                $st=$staff_conn->prepare("UPDATE staff_attendance SET status=?, recorded_by=? WHERE staff_id=? AND date=?");
                if($st){$st->bind_param('siis',$status,$user_id,$sid,$date);$st->execute();$st->close();}
            } else {
                $st=$staff_conn->prepare("INSERT INTO staff_attendance (staff_id,date,status,recorded_by) VALUES (?,?,?,?)");
                if($st){$st->bind_param('issi',$sid,$date,$status,$user_id);$st->execute();$st->close();}
            }
            $_SESSION['success'] = 'Attendance recorded.';
        }
        header('Location: hr-manager.php?page=attendance'); exit;
    }
    if ($action === 'send_announcement') {
        $title = trim($_POST['title'] ?? ''); $msg = trim($_POST['message'] ?? '');
        $priority = trim($_POST['priority'] ?? 'normal');
        if ($title && $msg && $staff_conn) {
            $stmt = $staff_conn->prepare("INSERT INTO hr_announcements (title,content,priority,created_by) VALUES (?,?,?,?)");
            if ($stmt) { $stmt->bind_param('sssi',$title,$msg,$priority,$user_id); $stmt->execute(); $_SESSION['success'] = 'Announcement sent.'; }
        }
        header('Location: hr-manager.php?page=communications'); exit;
    }
    if ($action === 'add_disciplinary') {
        $sid = (int)($_POST['staff_id'] ?? 0); $offense = trim($_POST['offense_type'] ?? '');
        $desc = trim($_POST['description'] ?? ''); $actionTaken = trim($_POST['action_taken'] ?? '');
        $incidentDate = $_POST['incident_date'] ?? date('Y-m-d');
        if ($sid && $offense && $staff_conn) {
            $stmt = $staff_conn->prepare("INSERT INTO staff_disciplinary (staff_id,incident_date,offense_type,description,action_taken,status,reported_by) VALUES (?,?,?,?,?,'open',?)");
            if ($stmt) { $stmt->bind_param('issssi',$sid,$incidentDate,$offense,$desc,$actionTaken,$user_id); $stmt->execute(); $_SESSION['success'] = 'Disciplinary case opened.'; }
        }
        header('Location: hr-manager.php?page=disciplinary'); exit;
    }
    if ($action === 'close_case') {
        $cid = (int)($_POST['case_id'] ?? 0); $resolution = trim($_POST['resolution'] ?? '');
        if ($cid && $staff_conn) { $st=$staff_conn->prepare("UPDATE staff_disciplinary SET status='resolved', action_taken=CONCAT(action_taken,?) WHERE id=?"); if($st){$res=' | Resolution: '.$resolution;$st->bind_param('si',$res,$cid);$st->execute();$st->close();$_SESSION['success']='Case closed.';} }
        header('Location: hr-manager.php?page=disciplinary'); exit;
    }
    if ($action === 'add_training') {
        $sid = (int)($_POST['staff_id'] ?? 0); $tname = trim($_POST['training_name'] ?? '');
        $provider = trim($_POST['provider'] ?? ''); $start = $_POST['start_date'] ?? '';
        $end = $_POST['end_date'] ?? ''; $type = trim($_POST['training_type'] ?? 'workshop');
        if ($sid && $tname && $staff_conn) {
            $stmt = $staff_conn->prepare("INSERT INTO staff_training (staff_id,training_name,training_type,provider,start_date,end_date,status) VALUES (?,?,?,?,?,?,'scheduled')");
            if ($stmt) { $stmt->bind_param('isssss', $sid, $tname, $type, $provider, $start, $end); $stmt->execute(); $_SESSION['success'] = 'Training added.'; }
        }
        header('Location: hr-manager.php?page=training'); exit;
    }
    if ($action === 'add_appraisal') {
        $sid = (int)($_POST['staff_id'] ?? 0); $period = trim($_POST['review_period'] ?? '');
        $score = (float)($_POST['overall_score'] ?? 0); $comments = trim($_POST['comments'] ?? '');
        if ($sid && $period && $staff_conn) {
            $stmt = $staff_conn->prepare("INSERT INTO performance_reviews (staff_id,reviewer_id,review_period,overall_score,comments,status) VALUES (?,?,?,?,?,'completed')");
            if ($stmt) { $stmt->bind_param('iisd', $sid, $user_id, $period, $score, $comments); $stmt->execute(); $_SESSION['success'] = 'Appraisal recorded.'; }
        }
        header('Location: hr-manager.php?page=performance'); exit;
    }
    if ($action === 'add_contract') {
        $sid = (int)($_POST['staff_id'] ?? 0); $ctype = trim($_POST['contract_type'] ?? 'contract');
        $start = $_POST['start_date'] ?? ''; $end = $_POST['end_date'] ?? '';
        $salary = (float)($_POST['salary'] ?? 0); $terms = trim($_POST['terms'] ?? '');
        if ($sid && $staff_conn) {
            $stmt = $staff_conn->prepare("INSERT INTO employment_contracts (staff_id,contract_type,start_date,end_date,salary,terms,status) VALUES (?,?,?,?,?,?,'active')");
            if ($stmt) { $stmt->bind_param('issids', $sid, $ctype, $start, $end, $salary, $terms); $stmt->execute(); $_SESSION['success'] = 'Contract created.'; }
        }
        header('Location: hr-manager.php?page=contracts'); exit;
    }
    if ($action === 'get_staff') {
        header('Content-Type: application/json');
        $sid = (int)($_POST['id'] ?? 0);
        $s = hrGetStaff($staff_conn, $sid);
        echo json_encode($s ?: ['error' => 'Staff not found']);
        exit;
    }
    if ($action === 'add_license') {
        $sid = (int)($_POST['staff_id'] ?? 0); $ltype = trim($_POST['license_type'] ?? '');
        $lnum = trim($_POST['license_number'] ?? ''); $body = trim($_POST['issuing_body'] ?? '');
        $expiry = $_POST['expiry_date'] ?? '';
        if ($sid && $ltype && $staff_conn) {
            $stmt = $staff_conn->prepare("INSERT INTO staff_licenses (staff_id,license_type,license_number,issuing_body,issue_date,expiry_date,status) VALUES (?,?,?,?,CURDATE(),?,'valid')");
            if ($stmt) { $stmt->bind_param('issss', $sid, $ltype, $lnum, $body, $expiry); $stmt->execute(); $_SESSION['success'] = 'License recorded.'; }
        }
        header('Location: hr-manager.php?page=compliance'); exit;
    }
}

// ── Data fetching ──
$stats = hrGetStats($staff_conn);
$staffList = []; $roles = []; $departments = []; $leaveReqs = []; $leaveTypes = [];
$vacancies = []; $applications = []; $attendanceToday = []; $disciplinaryCases = [];
$trainingRecords = []; $appraisals = []; $contracts = []; $licenses = [];
$announcements = []; $onboardingItems = []; $promotions = [];

if ($staff_conn) {
    $rr = $staff_conn->query("SELECT id, role_name FROM staff_roles ORDER BY role_name");
    if ($rr) $roles = $rr->fetch_all(MYSQLI_ASSOC);
    $dd = $staff_conn->query("SELECT id, name, code FROM departments ORDER BY name");
    if ($dd) $departments = $dd->fetch_all(MYSQLI_ASSOC);

    $sl = $staff_conn->query("SELECT s.*, sr.role_name FROM staff s LEFT JOIN staff_roles sr ON s.role_id=sr.id ORDER BY s.full_name LIMIT 200");
    if ($sl) $staffList = $sl->fetch_all(MYSQLI_ASSOC);
    $lr = $staff_conn->query("SELECT lr.*, lt.type_name, s.full_name FROM leave_requests lr JOIN leave_types lt ON lr.leave_type_id=lt.id JOIN staff s ON lr.staff_id=s.id ORDER BY lr.created_at DESC LIMIT 50");
    if ($lr) $leaveReqs = $lr->fetch_all(MYSQLI_ASSOC);
    $lt = $staff_conn->query("SELECT * FROM leave_types ORDER BY leave_type_name");
    if ($lt) $leaveTypes = $lt->fetch_all(MYSQLI_ASSOC);
    $jv = $staff_conn->query("SELECT jv.*, d.name as dept_name FROM job_vacancies jv LEFT JOIN departments d ON jv.department_id=d.id ORDER BY jv.posted_date DESC LIMIT 30");
    if ($jv) $vacancies = $jv->fetch_all(MYSQLI_ASSOC);
    $ja = $staff_conn->query("SELECT ja.*, jv.title as vacancy_title FROM job_applications ja JOIN job_vacancies jv ON ja.position_id=jv.id ORDER BY ja.created_at DESC LIMIT 50");
    if ($ja) $applications = $ja->fetch_all(MYSQLI_ASSOC);
    $at = $staff_conn->query("SELECT sa.*, s.full_name FROM staff_attendance sa JOIN staff s ON sa.staff_id=s.id WHERE sa.date=CURDATE() ORDER BY s.full_name");
    if ($at) $attendanceToday = $at->fetch_all(MYSQLI_ASSOC);
    $dc = $staff_conn->query("SELECT sd.*, s.full_name FROM staff_disciplinary sd JOIN staff s ON sd.staff_id=s.id ORDER BY sd.created_at DESC LIMIT 30");
    if ($dc) $disciplinaryCases = $dc->fetch_all(MYSQLI_ASSOC);
    $tr = $staff_conn->query("SELECT st.*, s.full_name FROM staff_training st JOIN staff s ON st.staff_id=s.id ORDER BY st.start_date DESC LIMIT 30");
    if ($tr) $trainingRecords = $tr->fetch_all(MYSQLI_ASSOC);
    $pr = $staff_conn->query("SELECT p.*, s.full_name FROM performance_reviews p JOIN staff s ON p.staff_id=s.id ORDER BY p.created_at DESC LIMIT 30");
    if ($pr) $appraisals = $pr->fetch_all(MYSQLI_ASSOC);
    $ct = $staff_conn->query("SELECT ec.*, s.full_name FROM employment_contracts ec JOIN staff s ON ec.staff_id=s.id ORDER BY ec.start_date DESC LIMIT 30");
    if ($ct) $contracts = $ct->fetch_all(MYSQLI_ASSOC);
    $lc = $staff_conn->query("SELECT sl.*, s.full_name FROM staff_licenses sl JOIN staff s ON sl.staff_id=s.id ORDER BY sl.expiry_date ASC LIMIT 30");
    if ($lc) $licenses = $lc->fetch_all(MYSQLI_ASSOC);
    $an = $staff_conn->query("SELECT * FROM hr_announcements ORDER BY created_at DESC LIMIT 20");
    if ($an) $announcements = $an->fetch_all(MYSQLI_ASSOC);
    $ob = $staff_conn->query("SELECT * FROM onboarding_checklist ORDER BY item_name");
    if ($ob) $onboardingItems = $ob->fetch_all(MYSQLI_ASSOC);
    $pm = $staff_conn->query("SELECT pr.*, s.full_name FROM promotion_recommendations pr JOIN staff s ON pr.staff_id=s.id ORDER BY pr.created_at DESC LIMIT 20");
    if ($pm) $promotions = $pm->fetch_all(MYSQLI_ASSOC);
}

$pageTitle = 'HR Manager';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include_once __DIR__ . '/../includes/dashboard_head.php'; ?>
<style>
:root{--hr-primary:#dc2626;--hr-dark:#991b1b}
.hr-content{margin-left:270px;padding:24px;min-height:100vh;background:#f8fafc}
.hr-header{background:linear-gradient(135deg,#dc2626,#ef4444);color:#fff;padding:20px 28px;border-radius:14px;margin-bottom:20px}
.hr-header h1{margin:0;font-size:22px}
.hr-header p{margin:2px 0 0;opacity:.85;font-size:13px}
.hr-tabs{display:flex;gap:3px;margin-bottom:20px;background:#fff;padding:6px;border-radius:10px;flex-wrap:wrap;border:1px solid #e2e8f0}
.hr-tabs a{padding:7px 14px;border-radius:7px;color:#475569;text-decoration:none;font-size:12px;font-weight:500;transition:.2s;white-space:nowrap}
.hr-tabs a:hover,.hr-tabs a.active{background:#dc2626;color:#fff}
.hr-card{background:#fff;border-radius:10px;border:1px solid #e2e8f0;padding:18px;margin-bottom:16px}
.hr-card h3{margin:0 0 14px;font-size:15px;font-weight:600;color:#1e293b;border-bottom:2px solid #fee2e2;padding-bottom:10px}
.stats-row{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:12px;margin-bottom:20px}
.stat-item{background:#fff;border-radius:10px;padding:16px;border:1px solid #e2e8f0;text-align:center}
.stat-item .num{font-size:26px;font-weight:700;color:#dc2626}
.stat-item .lbl{font-size:11px;color:#64748b;margin-top:2px}
.stat-item .mini{font-size:10px;color:#94a3b8}
.modal-lg-custom{max-width:800px}
@media(max-width:768px){.hr-content{margin-left:0;padding:14px}.hr-tabs a{padding:5px 10px;font-size:11px}}
</style>
</head>
<body>
<?php include_once __DIR__ . '/../includes/sidebar.php'; ?>
<div class="hr-content">
<?php if (isset($_SESSION['success'])): ?><div class="alert alert-success alert-dismissible"><?=htmlspecialchars($_SESSION['success'])?><button class="btn-close" data-bs-dismiss="alert"></button></div><?php unset($_SESSION['success']); endif; ?>

<div class="hr-header"><h1>Human Resources Management</h1><p><?=htmlspecialchars($user['full_name'] ?? 'HR Manager')?> &middot; <?=htmlspecialchars($user_role)?></p></div>

<nav class="hr-tabs">
  <a href="hr-manager.php" class="<?=$page==='overview'?'active':''?>">Overview</a>
  <a href="hr-manager.php?page=staff" class="<?=$page==='staff'?'active':''?>">Staff Records</a>
  <a href="hr-manager.php?page=recruitment" class="<?=$page==='recruitment'?'active':''?>">Recruitment</a>
  <a href="hr-manager.php?page=attendance" class="<?=$page==='attendance'?'active':''?>">Attendance</a>
  <a href="hr-manager.php?page=payroll" class="<?=$page==='payroll'?'active':''?>">Payroll</a>
  <a href="hr-manager.php?page=performance" class="<?=$page==='performance'?'active':''?>">Performance</a>
  <a href="hr-manager.php?page=training" class="<?=$page==='training'?'active':''?>">Training</a>
  <a href="hr-manager.php?page=disciplinary" class="<?=$page==='disciplinary'?'active':''?>">Disciplinary</a>
  <a href="hr-manager.php?page=contracts" class="<?=$page==='contracts'?'active':''?>">Contracts</a>
  <a href="hr-manager.php?page=communications" class="<?=$page==='communications'?'active':''?>">Comms</a>
  <a href="hr-manager.php?page=reports" class="<?=$page==='reports'?'active':''?>">Reports</a>
</nav>

<?php if ($page === 'overview'): ?>
<div class="stats-row">
  <div class="stat-item"><div class="num"><?=$stats['active_staff']?></div><div class="lbl">Active Staff</div><div class="mini"><?=$stats['total_staff']?> total</div></div>
  <div class="stat-item"><div class="num"><?=$stats['attendance_today']?></div><div class="lbl">Present Today</div><div class="mini"><?=$stats['late_today']?> late</div></div>
  <div class="stat-item"><div class="num"><?=$stats['pending_leave']?></div><div class="lbl">Pending Leave</div></div>
  <div class="stat-item"><div class="num"><?=$stats['open_vacancies']?></div><div class="lbl">Open Positions</div></div>
  <div class="stat-item"><div class="num"><?=$stats['expiring_licenses']?></div><div class="lbl">Expiring Licenses</div><div class="mini">next 60 days</div></div>
  <div class="stat-item"><div class="num"><?=$stats['open_cases']?></div><div class="lbl">Disciplinary Cases</div></div>
</div>

<div class="row">
  <div class="col-md-6">
    <div class="hr-card"><h3>Pending Leave Requests</h3>
    <?php $pendingLeaves = array_filter($leaveReqs, fn($l)=>$l['status']==='pending'); if (empty($pendingLeaves)): ?><p class="text-muted small">None pending.</p>
    <?php else: foreach (array_slice($pendingLeaves,0,5) as $l): ?>
      <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom">
        <div><strong><?=htmlspecialchars($l['full_name'])?></strong><br><small class="text-muted"><?=htmlspecialchars($l['type_name'])?>: <?=htmlspecialchars($l['start_date'])?> - <?=htmlspecialchars($l['end_date'])?></small></div>
        <div class="d-flex gap-1">
          <form method="post" class="d-inline"><input type="hidden" name="action" value="approve_leave"><input type="hidden" name="leave_id" value="<?=$l['id']?>"><button class="btn btn-sm btn-success">Approve</button></form>
          <form method="post" class="d-inline"><input type="hidden" name="action" value="reject_leave"><input type="hidden" name="leave_id" value="<?=$l['id']?>"><button class="btn btn-sm btn-danger">Reject</button></form>
        </div></div>
    <?php endforeach; endif; ?>
    </div>
  </div>
  <div class="col-md-6">
    <div class="hr-card"><h3>Upcoming Contract Expirations</h3>
    <?php $expiring = array_filter($contracts, fn($c)=>$c['status']==='active' && $c['end_date'] && strtotime($c['end_date']) <= strtotime('+60 days')); if (empty($expiring)): ?><p class="text-muted small">No contracts expiring within 60 days.</p>
    <?php else: foreach ($expiring as $c): ?>
      <div class="mb-2 pb-2 border-bottom"><strong><?=htmlspecialchars($c['full_name'])?></strong> &mdash; <?=htmlspecialchars($c['contract_type'])?> <span class="badge bg-warning text-dark">Expires: <?=htmlspecialchars($c['end_date'])?></span></div>
    <?php endforeach; endif; ?>
    </div>
    <div class="hr-card"><h3>Recent Hires</h3>
    <?php $recent = array_filter($staffList, fn($s)=>$s['hire_date'] && strtotime($s['hire_date']) >= strtotime('-30 days')); if (empty($recent)): ?><p class="text-muted small">No recent hires.</p>
    <?php else: foreach (array_slice($recent,0,5) as $s): ?><div class="mb-1 small"><strong><?=htmlspecialchars($s['full_name'])?></strong> &mdash; <?=htmlspecialchars($s['position']??$s['role_name']??'Staff')?> <span class="text-muted">(<?=htmlspecialchars($s['hire_date'])?>)</span></div><?php endforeach; endif; ?>
    </div>
  </div>
</div>

<?php elseif ($page === 'staff'): ?>
<div class="hr-card"><h3>Staff Records</h3>
<button class="btn btn-sm btn-primary mb-3" onclick="$('#addStaffModal').modal('show')">+ Add Staff</button>
<div class="table-responsive"><table class="table table-sm table-hover"><thead><tr><th>ID</th><th>Name</th><th>Email</th><th>Position</th><th>Department</th><th>Role</th><th>Category</th><th>Status</th><th>Actions</th></tr></thead><tbody>
<?php foreach ($staffList as $s): ?><tr>
  <td><?=htmlspecialchars($s['staff_id']??$s['id'])?></td>
  <td><?=htmlspecialchars($s['full_name'])?></td>
  <td><?=htmlspecialchars($s['email'])?></td>
  <td><?=htmlspecialchars($s['position']??'-')?></td>
  <td><?=htmlspecialchars($s['department']??'-')?></td>
  <td><?=htmlspecialchars($s['role_name']??'-')?></td>
  <td><?=htmlspecialchars($s['staff_category']??'-')?></td>
  <td><?=hrStatusBadge($s['status'])?></td>
  <td>
    <button class="btn btn-sm btn-outline-primary" onclick="editStaff(<?=$s['id']?>)">Edit</button>
    <a href="hr-manager.php?page=staff&sub=view&id=<?=$s['id']?>" class="btn btn-sm btn-outline-info">View</a>
  </td>
</tr><?php endforeach; ?>
</tbody></table></div></div>

<?php if ($sub === 'view'): $sid = (int)($_GET['id']??0); $s = hrGetStaff($staff_conn, $sid); if ($s): ?>
<div class="hr-card"><h3><?=htmlspecialchars($s['full_name'])?> &mdash; Full Profile</h3>
<div class="row small">
  <div class="col-md-4"><strong>Staff ID:</strong> <?=htmlspecialchars($s['staff_id']??'-')?></div>
  <div class="col-md-4"><strong>Email:</strong> <?=htmlspecialchars($s['email'])?></div>
  <div class="col-md-4"><strong>Phone:</strong> <?=htmlspecialchars($s['phone']??'-')?></div>
  <div class="col-md-4"><strong>Position:</strong> <?=htmlspecialchars($s['position']??'-')?></div>
  <div class="col-md-4"><strong>Department:</strong> <?=htmlspecialchars($s['department']??'-')?></div>
  <div class="col-md-4"><strong>Category:</strong> <?=htmlspecialchars($s['staff_category']??'-')?></div>
  <div class="col-md-4"><strong>Gender:</strong> <?=htmlspecialchars($s['gender']??'-')?></div>
  <div class="col-md-4"><strong>DOB:</strong> <?=htmlspecialchars($s['date_of_birth']??'-')?></div>
  <div class="col-md-4"><strong>NIN:</strong> <?=htmlspecialchars($s['nin']??'-')?></div>
  <div class="col-md-4"><strong>Qualification:</strong> <?=htmlspecialchars($s['highest_qualification']??'-')?></div>
  <div class="col-md-4"><strong>Experience:</strong> <?=(int)($s['year_of_experience']??0)?> yrs</div>
  <div class="col-md-4"><strong>Status:</strong> <?=hrStatusBadge($s['status'])?></div>
  <div class="col-md-4"><strong>Next of Kin:</strong> <?=htmlspecialchars($s['next_of_kin_name']??'-')?> (<?=htmlspecialchars($s['next_of_kin_phone']??'')?>)</div>
  <div class="col-md-4"><strong>Emergency:</strong> <?=htmlspecialchars($s['emergency_contact_name']??'-')?> (<?=htmlspecialchars($s['emergency_contact_phone']??'')?>)</div>
  <?php if ($s['contract_end_date']): ?><div class="col-md-4"><strong>Contract Ends:</strong> <?=htmlspecialchars($s['contract_end_date'])?></div><?php endif; ?>
</div>
<h4 class="mt-4 mb-2 fs-6 fw-semibold">Work History</h4>
<div class="table-responsive"><table class="table table-sm"><thead><tr><th>Position</th><th>Department</th><th>From</th><th>To</th><th>Reason</th></tr></thead><tbody>
<?php $wh=null; $wq=$staff_conn->prepare("SELECT * FROM staff_work_history WHERE staff_id=? ORDER BY start_date DESC"); if($wq){$wq->bind_param('i',$sid);$wq->execute();$wh=$wq->get_result();$wq->close();} if ($wh) while ($w = $wh->fetch_assoc()): ?><tr><td><?=htmlspecialchars($w['position'])?></td><td><?=htmlspecialchars($w['department']??'')?></td><td><?=$w['start_date']?></td><td><?=$w['end_date']??'Current'?></td><td><?=htmlspecialchars($w['reason_for_change']??'')?></td></tr><?php endwhile; ?>
</tbody></table></div>
</div><?php endif; endif; ?>

<?php elseif ($page === 'recruitment'): ?>
<div class="row">
  <div class="col-md-5">
    <div class="hr-card"><h3>Post New Vacancy</h3>
    <form method="post"><input type="hidden" name="action" value="post_vacancy">
      <div class="mb-2"><input class="form-control form-control-sm" name="title" placeholder="Job Title" required></div>
      <div class="mb-2"><select class="form-select form-select-sm" name="department"><option value="">Department</option><?php foreach ($departments as $d): ?><option value="<?=$d['id']?>"><?=htmlspecialchars($d['name'])?></option><?php endforeach; ?></select></div>
      <div class="mb-2"><textarea class="form-control form-control-sm" name="description" rows="3" placeholder="Description"></textarea></div>
      <div class="mb-2"><textarea class="form-control form-control-sm" name="requirements" rows="3" placeholder="Requirements"></textarea></div>
      <div class="mb-2"><input class="form-control form-control-sm" name="salary_range" placeholder="Salary Range"></div>
      <div class="mb-2"><label class="small">Closing Date</label><input type="date" class="form-control form-control-sm" name="closing_date"></div>
      <button class="btn btn-sm btn-primary">Post Vacancy</button>
    </form></div>
    <div class="hr-card"><h3>Open Vacancies (<?=count($vacancies)?>)</h3>
    <?php foreach ($vacancies as $v): ?><div class="mb-2 pb-2 border-bottom small"><strong><?=htmlspecialchars($v['title'])?></strong> (<?=htmlspecialchars($v['dept_name']??'N/A')?>) <?=hrStatusBadge($v['status'])?><br><span class="text-muted">Posted: <?=$v['posted_date']?> | Closes: <?=$v['closing_date']?></span></div><?php endforeach; ?>
    </div>
  </div>
  <div class="col-md-7">
    <div class="hr-card"><h3>Applications (<?=count($applications)?>)</h3>
    <div class="table-responsive"><table class="table table-sm"><thead><tr><th>Applicant</th><th>Position</th><th>Date</th><th>Status</th><th>Action</th></tr></thead><tbody>
    <?php foreach ($applications as $a): ?><tr>
      <td><?=htmlspecialchars($a['applicant_name'])?><br><small class="text-muted"><?=htmlspecialchars($a['email'])?></small></td>
      <td><?=htmlspecialchars($a['vacancy_title'])?></td>
      <td><?=htmlspecialchars($a['created_at'])?></td>
      <td><?=hrStatusBadge($a['application_status'])?></td>
      <td><form method="post" class="d-inline"><input type="hidden" name="action" value="shortlist"><input type="hidden" name="application_id" value="<?=$a['id']?>"><button class="btn btn-sm btn-outline-success">Shortlist</button></form></td>
    </tr><?php endforeach; ?>
    </tbody></table></div></div>
  </div>
</div>

<?php elseif ($page === 'attendance'): ?>
<div class="row">
  <div class="col-md-5">
    <div class="hr-card"><h3>Record Attendance</h3>
    <form method="post"><input type="hidden" name="action" value="record_attendance">
      <div class="mb-2"><select class="form-select form-select-sm" name="staff_id" required><option value="">Select Staff</option><?php foreach ($staffList as $s): ?><option value="<?=$s['id']?>"><?=htmlspecialchars($s['full_name'])?> (<?=htmlspecialchars($s['staff_id']??$s['id'])?>)</option><?php endforeach; ?></select></div>
      <div class="mb-2"><input type="date" class="form-control form-control-sm" name="date" value="<?=date('Y-m-d')?>"></div>
      <div class="mb-2"><select class="form-select form-select-sm" name="attendance_status"><option value="present">Present</option><option value="late">Late</option><option value="absent">Absent</option><option value="half-day">Half Day</option><option value="leave">On Leave</option></select></div>
      <button class="btn btn-sm btn-primary">Record</button>
    </form></div>
    <div class="hr-card"><h3>Leave Types</h3>
    <table class="table table-sm"><thead><tr><th>Type</th><th>Days/Year</th></tr></thead><tbody>
    <?php foreach ($leaveTypes as $lt): ?><tr><td><?=htmlspecialchars($lt['leave_type_name']??$lt['type_name'])?></td><td><?=(int)$lt['days_per_year']?></td></tr><?php endforeach; ?>
    </tbody></table></div>
  </div>
  <div class="col-md-7">
    <div class="hr-card"><h3>Today's Attendance (<?=date('d M Y')?>)</h3>
    <div class="table-responsive"><table class="table table-sm"><thead><tr><th>Staff</th><th>Status</th><th>Time In</th><th>Time Out</th></tr></thead><tbody>
    <?php foreach ($attendanceToday as $a): ?><tr><td><?=htmlspecialchars($a['full_name'])?></td><td><?=hrStatusBadge($a['status'])?></td><td><?=htmlspecialchars($a['time_in']??'-')?></td><td><?=htmlspecialchars($a['time_out']??'-')?></td></tr><?php endforeach; if (empty($attendanceToday)): ?><tr><td colspan="4" class="text-muted text-center">No attendance recorded today.</td></tr><?php endif; ?>
    </tbody></table></div>
    <div class="hr-card"><h3 id="leave">Leave Requests</h3>
    <div class="table-responsive"><table class="table table-sm"><thead><tr><th>Staff</th><th>Type</th><th>Dates</th><th>Status</th><th>Actions</th></tr></thead><tbody>
    <?php foreach ($leaveReqs as $l): ?><tr><td><?=htmlspecialchars($l['full_name'])?></td><td><?=htmlspecialchars($l['type_name'])?></td><td><?=htmlspecialchars($l['start_date'])?> - <?=htmlspecialchars($l['end_date'])?></td><td><?=hrStatusBadge($l['status'])?></td>
      <td><?php if ($l['status']==='pending'): ?><form method="post" class="d-inline"><input type="hidden" name="action" value="approve_leave"><input type="hidden" name="leave_id" value="<?=$l['id']?>"><button class="btn btn-sm btn-success">Approve</button></form><form method="post" class="d-inline"><input type="hidden" name="action" value="reject_leave"><input type="hidden" name="leave_id" value="<?=$l['id']?>"><button class="btn btn-sm btn-danger">Reject</button></form><?php endif; ?></td>
    </tr><?php endforeach; ?>
    </tbody></table></div></div>
  </div>
</div>

<?php elseif ($page === 'payroll'): ?>
<div class="row">
  <div class="col-md-6">
    <div class="hr-card"><h3>Salary Structure Overview</h3>
    <div class="table-responsive"><table class="table table-sm"><thead><tr><th>Staff</th><th>Basic Salary</th><th>Allowances</th><th>Deductions</th><th>Net</th></tr></thead><tbody>
    <?php $ps = $staff_conn->query("SELECT ss.*, s.full_name FROM salary_structures ss JOIN staff s ON ss.staff_id=s.id ORDER BY s.full_name LIMIT 20"); if ($ps) while ($p = $ps->fetch_assoc()): ?><tr><td><?=htmlspecialchars($p['full_name'])?></td><td><?=number_format($p['basic_salary']??$p['base_salary']??0)?></td><td><?=number_format(($p['housing_allowance']??0)+($p['transport_allowance']??0))?></td><td>-</td><td><strong><?=number_format(($p['basic_salary']??$p['base_salary']??0)+($p['housing_allowance']??0)+($p['transport_allowance']??0))?></strong></td></tr><?php endwhile; ?>
    </tbody></table></div></div>
  <div class="col-md-6">
    <div class="hr-card"><h3>Payroll Integration</h3>
    <p class="text-muted small">Payroll is finalized by the Bursar's office. HR validates and submits salary inputs.</p>
    <div class="mb-2"><a href="../payroll.php" class="btn btn-sm btn-primary">Go to Full Payroll System</a> <a href="bursar-payroll.php" class="btn btn-sm btn-outline-primary">Bursar Payroll</a></div>
    <h4 class="fs-6 mt-3">Pending Payroll Validation</h4>
    <?php $pe = $staff_conn->query("SELECT COUNT(*) c FROM payroll_employees WHERE staff_id NOT IN (SELECT staff_id FROM salary_structures WHERE staff_id IS NOT NULL)"); $pendingPayroll = $pe ? (int)$pe->fetch_assoc()['c'] : 0; ?>
    <p class="small"><?=$pendingPayroll?> staff members missing salary structure setup.</p>
  </div>
</div>

<?php elseif ($page === 'performance'): ?>
<div class="row">
  <div class="col-md-5">
    <div class="hr-card"><h3>New Appraisal</h3>
    <form method="post"><input type="hidden" name="action" value="add_appraisal">
      <div class="mb-2"><select class="form-select form-select-sm" name="staff_id" required><option value="">Select Staff</option><?php foreach ($staffList as $s): ?><option value="<?=$s['id']?>"><?=htmlspecialchars($s['full_name'])?></option><?php endforeach; ?></select></div>
      <div class="mb-2"><input class="form-control form-control-sm" name="review_period" placeholder="e.g. Q1 2026" required></div>
      <div class="mb-2"><input type="number" class="form-control form-control-sm" name="overall_score" placeholder="Score (0-100)" min="0" max="100" step="0.1"></div>
      <div class="mb-2"><textarea class="form-control form-control-sm" name="comments" rows="3" placeholder="Comments/Feedback"></textarea></div>
      <button class="btn btn-sm btn-primary">Save Appraisal</button>
    </form></div>
  </div>
  <div class="col-md-7">
    <div class="hr-card"><h3>Appraisals & Performance Reviews</h3>
    <div class="table-responsive"><table class="table table-sm"><thead><tr><th>Staff</th><th>Period</th><th>Score</th><th>Status</th><th>Date</th></tr></thead><tbody>
    <?php foreach ($appraisals as $p): ?><tr><td><?=htmlspecialchars($p['full_name'])?></td><td><?=htmlspecialchars($p['review_period'])?></td><td><strong><?=htmlspecialchars($p['overall_score']??'-')?></strong></td><td><?=hrStatusBadge($p['status'])?></td><td><?=htmlspecialchars($p['created_at'])?></td></tr><?php endforeach; ?>
    </tbody></table></div></div>
</div>

<?php elseif ($page === 'training'): ?>
<div class="row">
  <div class="col-md-5">
    <div class="hr-card"><h3>Assign Training</h3>
    <form method="post"><input type="hidden" name="action" value="add_training">
      <div class="mb-2"><select class="form-select form-select-sm" name="staff_id" required><option value="">Select Staff</option><?php foreach ($staffList as $s): ?><option value="<?=$s['id']?>"><?=htmlspecialchars($s['full_name'])?></option><?php endforeach; ?></select></div>
      <div class="mb-2"><input class="form-control form-control-sm" name="training_name" placeholder="Training Name" required></div>
      <div class="mb-2"><input class="form-control form-control-sm" name="provider" placeholder="Provider/Institution"></div>
      <div class="mb-2"><select class="form-select form-select-sm" name="training_type"><option value="workshop">Workshop</option><option value="seminar">Seminar</option><option value="course">Course</option><option value="conference">Conference</option><option value="cpd">CPD</option></select></div>
      <div class="row g-1 mb-2"><div class="col-6"><input type="date" class="form-control form-control-sm" name="start_date" placeholder="Start"></div><div class="col-6"><input type="date" class="form-control form-control-sm" name="end_date" placeholder="End"></div></div>
      <button class="btn btn-sm btn-primary">Assign Training</button>
    </form></div>
  </div>
  <div class="col-md-7">
    <div class="hr-card"><h3>Training Records</h3>
    <div class="table-responsive"><table class="table table-sm"><thead><tr><th>Staff</th><th>Training</th><th>Provider</th><th>Dates</th><th>Status</th></tr></thead><tbody>
    <?php foreach ($trainingRecords as $t): ?><tr><td><?=htmlspecialchars($t['full_name'])?></td><td><?=htmlspecialchars($t['training_name'])?></td><td><?=htmlspecialchars($t['provider']??'-')?></td><td><?=htmlspecialchars($t['start_date'])?> - <?=htmlspecialchars($t['end_date'])?></td><td><?=hrStatusBadge($t['status'])?></td></tr><?php endforeach; ?>
    </tbody></table></div>
    <div class="hr-card"><h3>Licenses & Certification</h3>
    <div class="table-responsive"><table class="table table-sm"><thead><tr><th>Staff</th><th>License Type</th><th>Number</th><th>Expiry</th><th>Status</th></tr></thead><tbody>
    <?php foreach ($licenses as $l): ?><tr><td><?=htmlspecialchars($l['full_name'])?></td><td><?=htmlspecialchars($l['license_type'])?></td><td><?=htmlspecialchars($l['license_number'])?></td><td><?=$l['expiry_date']?></td><td><?=hrStatusBadge($l['status'])?></td></tr><?php endforeach; ?>
    </tbody></table></div></div>
  </div>
</div>

<?php elseif ($page === 'disciplinary'): ?>
<div class="row">
  <div class="col-md-5">
    <div class="hr-card"><h3>New Disciplinary Case</h3>
    <form method="post"><input type="hidden" name="action" value="add_disciplinary">
      <div class="mb-2"><select class="form-select form-select-sm" name="staff_id" required><option value="">Select Staff</option><?php foreach ($staffList as $s): ?><option value="<?=$s['id']?>"><?=htmlspecialchars($s['full_name'])?></option><?php endforeach; ?></select></div>
      <div class="mb-2"><input type="date" class="form-control form-control-sm" name="incident_date" value="<?=date('Y-m-d')?>"></div>
      <div class="mb-2"><input class="form-control form-control-sm" name="offense_type" placeholder="Offense Type" required></div>
      <div class="mb-2"><textarea class="form-control form-control-sm" name="description" rows="3" placeholder="Description"></textarea></div>
      <div class="mb-2"><textarea class="form-control form-control-sm" name="action_taken" rows="2" placeholder="Action Taken"></textarea></div>
      <button class="btn btn-sm btn-primary">Open Case</button>
    </form></div>
  </div>
  <div class="col-md-7">
    <div class="hr-card"><h3>Disciplinary Cases (<?=count($disciplinaryCases)?>)</h3>
    <div class="table-responsive"><table class="table table-sm"><thead><tr><th>Staff</th><th>Offense</th><th>Date</th><th>Status</th><th>Action</th></tr></thead><tbody>
    <?php foreach ($disciplinaryCases as $d): ?><tr>
      <td><?=htmlspecialchars($d['full_name'])?></td>
      <td><?=htmlspecialchars($d['offense_type'])?></td>
      <td><?=htmlspecialchars($d['incident_date'])?></td>
      <td><?=hrStatusBadge($d['status'])?></td>
      <td><?php if ($d['status']==='open'): ?><form method="post" class="d-inline"><input type="hidden" name="action" value="close_case"><input type="hidden" name="case_id" value="<?=$d['id']?>"><input name="resolution" placeholder="Resolution" class="form-control form-control-sm d-inline" style="width:120px" required><button class="btn btn-sm btn-success">Close</button></form><?php endif; ?></td>
    </tr><?php endforeach; ?>
    </tbody></table></div></div>
</div>

<?php elseif ($page === 'contracts'): ?>
<div class="row">
  <div class="col-md-5">
    <div class="hr-card"><h3>New Contract</h3>
    <form method="post"><input type="hidden" name="action" value="add_contract">
      <div class="mb-2"><select class="form-select form-select-sm" name="staff_id" required><option value="">Select Staff</option><?php foreach ($staffList as $s): ?><option value="<?=$s['id']?>"><?=htmlspecialchars($s['full_name'])?></option><?php endforeach; ?></select></div>
      <div class="mb-2"><select class="form-select form-select-sm" name="contract_type"><option value="permanent">Permanent</option><option value="contract">Contract</option><option value="part-time">Part-time</option><option value="internship">Internship</option><option value="temporary">Temporary</option></select></div>
      <div class="row g-1 mb-2"><div class="col-6"><label class="small">Start Date</label><input type="date" class="form-control form-control-sm" name="start_date" required></div><div class="col-6"><label class="small">End Date</label><input type="date" class="form-control form-control-sm" name="end_date"></div></div>
      <div class="mb-2"><input type="number" class="form-control form-control-sm" name="salary" placeholder="Salary" step="0.01"></div>
      <div class="mb-2"><textarea class="form-control form-control-sm" name="terms" rows="3" placeholder="Terms & Conditions"></textarea></div>
      <button class="btn btn-sm btn-primary">Create Contract</button>
    </form></div>
    <div class="hr-card"><h3>Add License/Certification</h3>
    <form method="post"><input type="hidden" name="action" value="add_license">
      <div class="mb-2"><select class="form-select form-select-sm" name="staff_id" required><option value="">Select Staff</option><?php foreach ($staffList as $s): ?><option value="<?=$s['id']?>"><?=htmlspecialchars($s['full_name'])?></option><?php endforeach; ?></select></div>
      <div class="mb-2"><input class="form-control form-control-sm" name="license_type" placeholder="License Type (e.g. Nursing License)" required></div>
      <div class="mb-2"><input class="form-control form-control-sm" name="license_number" placeholder="License Number"></div>
      <div class="mb-2"><input class="form-control form-control-sm" name="issuing_body" placeholder="Issuing Body"></div>
      <div class="mb-2"><label class="small">Expiry Date</label><input type="date" class="form-control form-control-sm" name="expiry_date"></div>
      <button class="btn btn-sm btn-primary">Record License</button>
    </form></div>
  </div>
  <div class="col-md-7">
    <div class="hr-card"><h3>Active Contracts</h3>
    <div class="table-responsive"><table class="table table-sm"><thead><tr><th>Staff</th><th>Type</th><th>Period</th><th>Status</th></tr></thead><tbody>
    <?php foreach ($contracts as $c): ?><tr><td><?=htmlspecialchars($c['full_name'])?></td><td><?=htmlspecialchars($c['contract_type'])?></td><td><?=$c['start_date']?> - <?=$c['end_date']??'Open'?></td><td><?=hrStatusBadge($c['status'])?></td></tr><?php endforeach; ?>
    </tbody></table></div>
    <div class="hr-card"><h3>Compliance & Certification Tracking</h3>
    <div class="table-responsive"><table class="table table-sm"><thead><tr><th>Staff</th><th>License</th><th>Number</th><th>Expires</th><th>Status</th></tr></thead><tbody>
    <?php foreach ($licenses as $l): ?><tr><td><?=htmlspecialchars($l['full_name'])?></td><td><?=htmlspecialchars($l['license_type'])?></td><td><?=htmlspecialchars($l['license_number'])?></td><td><?=$l['expiry_date']?></td><td><?=hrStatusBadge($l['status'])?></td></tr><?php endforeach; ?>
    </tbody></table></div></div>
  </div>
</div>

<?php elseif ($page === 'communications'): ?>
<div class="row">
  <div class="col-md-5">
    <div class="hr-card"><h3>Send Announcement</h3>
    <form method="post"><input type="hidden" name="action" value="send_announcement">
      <div class="mb-2"><input class="form-control form-control-sm" name="title" placeholder="Title" required></div>
      <div class="mb-2"><textarea class="form-control form-control-sm" name="message" rows="5" placeholder="Message" required></textarea></div>
      <div class="mb-2"><select class="form-select form-select-sm" name="priority"><option value="normal">Normal</option><option value="high">High</option><option value="urgent">Urgent</option></select></div>
      <button class="btn btn-sm btn-primary">Send</button>
    </form></div>
  </div>
  <div class="col-md-7">
    <div class="hr-card"><h3>Announcements</h3>
    <?php foreach ($announcements as $a): ?><div class="mb-3 pb-3 border-bottom">
      <strong><?=htmlspecialchars($a['title'])?></strong> <?=hrStatusBadge($a['priority'])?><br>
      <small><?=nl2br(htmlspecialchars($a['content']))?></small><br>
      <span class="text-muted small"><?=$a['created_at']?></span>
    </div><?php endforeach; if (empty($announcements)): ?><p class="text-muted">No announcements yet.</p><?php endif; ?>
    </div>
  </div>
</div>

<?php elseif ($page === 'reports'): ?>
<div class="row">
  <div class="col-md-6">
    <div class="hr-card"><h3>Staff by Department</h3>
    <div class="table-responsive"><table class="table table-sm"><thead><tr><th>Department</th><th>Count</th></tr></thead><tbody>
    <?php $deptStats = $staff_conn->query("SELECT COALESCE(department,'Unassigned') as dept, COUNT(*) c FROM staff GROUP BY department ORDER BY c DESC"); if ($deptStats) while ($d = $deptStats->fetch_assoc()): ?><tr><td><?=htmlspecialchars($d['dept'])?></td><td><?=$d['c']?></td></tr><?php endwhile; ?>
    </tbody></table></div>
  </div>
  <div class="col-md-6">
    <div class="hr-card"><h3>Staff by Category</h3>
    <div class="table-responsive"><table class="table table-sm"><thead><tr><th>Category</th><th>Count</th></tr></thead><tbody>
    <?php $catStats = $staff_conn->query("SELECT COALESCE(staff_category,'Unassigned') as cat, COUNT(*) c FROM staff GROUP BY staff_category ORDER BY c DESC"); if ($catStats) while ($c = $catStats->fetch_assoc()): ?><tr><td><?=htmlspecialchars($c['cat'])?></td><td><?=$c['c']?></td></tr><?php endwhile; ?>
    </tbody></table></div>
    <div class="hr-card"><h3>Staff by Gender</h3>
    <div class="table-responsive"><table class="table table-sm"><thead><tr><th>Gender</th><th>Count</th></tr></thead><tbody>
    <?php $genStats = $staff_conn->query("SELECT COALESCE(gender,'Not Specified') as gen, COUNT(*) c FROM staff GROUP BY gender ORDER BY c DESC"); if ($genStats) while ($g = $genStats->fetch_assoc()): ?><tr><td><?=htmlspecialchars($g['gen'])?></td><td><?=$g['c']?></td></tr><?php endwhile; ?>
    </tbody></table></div>
    <div class="hr-card"><h3>Qualification Distribution</h3>
    <div class="table-responsive"><table class="table table-sm"><thead><tr><th>Qualification</th><th>Count</th></tr></thead><tbody>
    <?php $qualStats = $staff_conn->query("SELECT COALESCE(highest_qualification,'Not Specified') as qual, COUNT(*) c FROM staff GROUP BY highest_qualification ORDER BY c DESC"); if ($qualStats) while ($q = $qualStats->fetch_assoc()): ?><tr><td><?=htmlspecialchars($q['qual'])?></td><td><?=$q['c']?></td></tr><?php endwhile; ?>
    </tbody></table></div>
  </div>
</div>
<?php endif; ?>
</div>

<!-- Add Staff Modal -->
<div class="modal fade" id="addStaffModal"><div class="modal-dialog modal-lg"><div class="modal-content">
<div class="modal-header"><h5 class="modal-title">Add New Staff</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
<form method="post"><div class="modal-body row g-2"><input type="hidden" name="action" value="add_staff">
  <div class="col-md-6"><label class="form-label small">Full Name *</label><input class="form-control form-control-sm" name="full_name" required></div>
  <div class="col-md-6"><label class="form-label small">Email *</label><input type="email" class="form-control form-control-sm" name="email" required></div>
  <div class="col-md-4"><label class="form-label small">Phone</label><input class="form-control form-control-sm" name="phone"></div>
  <div class="col-md-4"><label class="form-label small">Position</label><input class="form-control form-control-sm" name="position"></div>
  <div class="col-md-4"><label class="form-label small">Department</label><select class="form-select form-select-sm" name="department"><?php foreach ($departments as $d): ?><option value="<?=$d['name']?>"><?=htmlspecialchars($d['name'])?></option><?php endforeach; ?></select></div>
  <div class="col-md-4"><label class="form-label small">Role</label><select class="form-select form-select-sm" name="role_id"><option value="">Select Role</option><?php foreach ($roles as $r): ?><option value="<?=$r['id']?>"><?=htmlspecialchars($r['role_name'])?></option><?php endforeach; ?></select></div>
  <div class="col-md-4"><label class="form-label small">Category</label><select class="form-select form-select-sm" name="staff_category"><option value="teaching">Teaching</option><option value="non-teaching">Non-Teaching</option><option value="clinical">Clinical</option><option value="administrative">Administrative</option></select></div>
  <div class="col-md-4"><label class="form-label small">Gender</label><select class="form-select form-select-sm" name="gender"><option value="">Select</option><option value="Male">Male</option><option value="Female">Female</option></select></div>
  <div class="col-md-4"><label class="form-label small">Date of Birth</label><input type="date" class="form-control form-control-sm" name="date_of_birth"></div>
  <div class="col-md-4"><label class="form-label small">NIN</label><input class="form-control form-control-sm" name="nin"></div>
  <div class="col-md-4"><label class="form-label small">Highest Qualification</label><input class="form-control form-control-sm" name="highest_qualification"></div>
  <div class="col-md-4"><label class="form-label small">Years of Experience</label><input type="number" class="form-control form-control-sm" name="year_of_experience" value="0"></div>
</div><div class="modal-footer"><button class="btn btn-primary">Add Staff</button></div></form></div></div></div>

<script>
// Auto-inject CSRF token into all POST forms
document.addEventListener('DOMContentLoaded',function(){var t='<?=htmlspecialchars($_SESSION['csrf_token'])?>';document.querySelectorAll('form[method="post"]').forEach(function(f){if(!f.querySelector('input[name="csrf_token"]')){var i=document.createElement('input');i.type='hidden';i.name='csrf_token';i.value=t;f.appendChild(i);}});});
function editStaff(id) {
    fetch('hr-manager.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'action=get_staff&id='+id+'&csrf_token=<?=htmlspecialchars($_SESSION['csrf_token'])?>'})
    .then(r=>r.json()).then(d=>{if(d&&d.id){alert('Editing staff #'+id+' — use the form below.');window.location='hr-manager.php?page=staff&sub=view&id='+id;}});
}
</script>
<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
</body></html>
