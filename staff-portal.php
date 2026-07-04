<?php
/**
 * Staff Self-Service Portal
 * - View payslips, apply for leave, check leave balance
 * - Update personal details (with approval)
 * - View schedules/duties, download employment documents
 */
require_once __DIR__ . '/includes/staff_dashboard_access.php';
require_once __DIR__ . '/includes/hr_functions.php';

$ctx = bootstrapStaffDashboard([]);
$auth = $ctx['auth'];
$user = $ctx['user'];
$staffConn = $ctx['staff'];
$userId = (int)($_SESSION['user_id'] ?? 0);
$role = $_SESSION['role'] ?? '';

$page = $_GET['page'] ?? 'dashboard';
$staff = hrGetStaff($staffConn, $userId);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $staffConn) {
    $action = $_POST['action'] ?? '';
    if ($action === 'apply_leave') {
        $typeId = (int)($_POST['leave_type_id'] ?? 0);
        $start = $_POST['start_date'] ?? '';
        $end = $_POST['end_date'] ?? '';
        $reason = trim($_POST['reason'] ?? '');
        if ($typeId && $start && $end) {
            $stmt = $staffConn->prepare("INSERT INTO leave_requests (staff_id, leave_type_id, start_date, end_date, reason, status) VALUES (?,?,?,?,?,'pending')");
            if ($stmt) { $stmt->bind_param('iisss', $userId, $typeId, $start, $end, $reason); $stmt->execute(); $_SESSION['success'] = 'Leave application submitted.'; }
        }
        header('Location: staff-portal.php?page=leave'); exit;
    }
    if ($action === 'update_profile') {
        $phone = trim($_POST['phone'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $emergencyName = trim($_POST['emergency_contact_name'] ?? '');
        $emergencyPhone = trim($_POST['emergency_contact_phone'] ?? '');
        $nextKin = trim($_POST['next_of_kin_name'] ?? '');
        $nextKinPhone = trim($_POST['next_of_kin_phone'] ?? '');
        $nextKinRel = trim($_POST['next_of_kin_relationship'] ?? '');
        if ($staffConn) {
            $stmt = $staffConn->prepare("UPDATE staff SET phone=?, address=?, emergency_contact_name=?, emergency_contact_phone=?, next_of_kin_name=?, next_of_kin_phone=?, next_of_kin_relationship=? WHERE id=?");
            if ($stmt) {
                $stmt->bind_param('sssssssi', $phone, $address, $emergencyName, $emergencyPhone, $nextKin, $nextKinPhone, $nextKinRel, $userId);
                $stmt->execute();
                $_SESSION['success'] = 'Profile updated.';
            }
        }
        header('Location: staff-portal.php?page=profile'); exit;
    }
}

$leaveTypes = [];
if ($staffConn) {
    $r = $staffConn->query("SELECT * FROM leave_types WHERE 1=1 ORDER BY leave_type_name");
    if ($r) $leaveTypes = $r->fetch_all(MYSQLI_ASSOC);
}

$leaveBalances = [];
if ($staffConn && $userId) {
    $st=$staffConn->prepare("SELECT lt.id,lt.type_name,lt.days_per_year,COALESCE(lb.used_days,0) as used_days,(COALESCE(lb.total_days,lt.days_per_year)-COALESCE(lb.used_days,0)) as remaining FROM leave_types lt LEFT JOIN leave_balances lb ON lt.id=lb.leave_type_id AND lb.staff_id=? AND lb.year=YEAR(CURDATE()) ORDER BY lt.type_name");
    if($st){$st->bind_param('i',$userId);$st->execute();$leaveBalances=$st->get_result()->fetch_all(MYSQLI_ASSOC);$st->close();}
}

$myLeaves = [];
if ($staffConn && $userId) {
    $st=$staffConn->prepare("SELECT lr.*,lt.type_name FROM leave_requests lr JOIN leave_types lt ON lr.leave_type_id=lt.id WHERE lr.staff_id=? ORDER BY lr.created_at DESC LIMIT 20");
    if($st){$st->bind_param('i',$userId);$st->execute();$myLeaves=$st->get_result()->fetch_all(MYSQLI_ASSOC);$st->close();}
}

$myPayslips = [];
if ($staffConn && $userId) {
    $st=$staffConn->prepare("SELECT id,payslip_number,salary_month,basic_salary,gross_pay,net_pay,status FROM payslips WHERE staff_id=? ORDER BY salary_month DESC LIMIT 12");
    if($st){$st->bind_param('i',$userId);$st->execute();$myPayslips=$st->get_result()->fetch_all(MYSQLI_ASSOC);$st->close();}
}

$myDuties = [];
if ($staffConn && $userId) {
    $st=$staffConn->prepare("SELECT * FROM duty_rosters WHERE staff_id=? AND duty_date>=CURDATE() ORDER BY duty_date LIMIT 10");
    if($st){$st->bind_param('i',$userId);$st->execute();$myDuties=$st->get_result()->fetch_all(MYSQLI_ASSOC);$st->close();}
}

$myDocuments = [];
if ($staffConn && $userId) {
    $st=$staffConn->prepare("SELECT * FROM staff_documents WHERE staff_id=? ORDER BY created_at DESC");
    if($st){$st->bind_param('i',$userId);$st->execute();$myDocuments=$st->get_result()->fetch_all(MYSQLI_ASSOC);$st->close();}
}

$myNotifications = [];
if ($staffConn && $userId) {
    $st=$staffConn->prepare("SELECT * FROM staff_notifications WHERE (target_user_id=? OR target_role='all') ORDER BY created_at DESC LIMIT 10");
    if($st){$st->bind_param('i',$userId);$st->execute();$myNotifications=$st->get_result()->fetch_all(MYSQLI_ASSOC);$st->close();}
}

$pageTitle = 'Staff Portal';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include_once __DIR__ . '/includes/dashboard_head.php'; ?>
<style>
:root { --sp-primary: #1E3A8A; }
.sp-content{margin-left:270px;padding:24px;min-height:100vh;background:#f8fafc}
.sp-header{background:linear-gradient(135deg,#1E3A8A,#3B82F6);color:#fff;padding:24px 32px;border-radius:16px;margin-bottom:24px}
.sp-header h1{margin:0;font-size:24px;font-weight:700}
.sp-header p{margin:4px 0 0;opacity:.85;font-size:14px}
.sp-nav{display:flex;gap:4px;margin-bottom:24px;background:#fff;padding:8px;border-radius:12px;flex-wrap:wrap;border:1px solid #e2e8f0}
.sp-nav a{padding:8px 16px;border-radius:8px;color:#475569;text-decoration:none;font-size:13px;font-weight:500;transition:all .2s}
.sp-nav a:hover,.sp-nav a.active{background:#1E3A8A;color:#fff}
.sp-card{background:#fff;border-radius:12px;border:1px solid #e2e8f0;padding:20px;margin-bottom:16px}
.sp-card h3{margin:0 0 16px;font-size:16px;font-weight:600;color:#1e293b;border-bottom:2px solid #f1f5f9;padding-bottom:12px}
.stats-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px;margin-bottom:24px}
.stat-box{background:#fff;border-radius:12px;padding:20px;border:1px solid #e2e8f0;text-align:center}
.stat-box .num{font-size:28px;font-weight:700;color:#1E3A8A}
.stat-box .label{font-size:12px;color:#64748b;margin-top:4px}
@media(max-width:768px){.sp-content{margin-left:0;padding:16px}.sp-nav a{padding:6px 12px;font-size:12px}}
</style>
</head>
<body>
<?php include_once __DIR__ . '/includes/sidebar.php'; ?>
<div class="sp-content">
<?php if (isset($_SESSION['success'])): ?><div class="alert alert-success"><?=htmlspecialchars($_SESSION['success'])?></div><?php unset($_SESSION['success']); endif; ?>

<div class="sp-header"><h1>Staff Portal</h1><p>Welcome, <?=htmlspecialchars($staff['full_name'] ?? $user['full_name'] ?? 'Staff')?></p></div>

<nav class="sp-nav">
  <a href="staff-portal.php" class="<?=$page==='dashboard'?'active':''?>">Dashboard</a>
  <a href="staff-portal.php?page=profile" class="<?=$page==='profile'?'active':''?>">My Profile</a>
  <a href="staff-portal.php?page=leave" class="<?=$page==='leave'?'active':''?>">Leave</a>
  <a href="staff-portal.php?page=payslips" class="<?=$page==='payslips'?'active':''?>">Payslips</a>
  <a href="staff-portal.php?page=schedule" class="<?=$page==='schedule'?'active':''?>">My Schedule</a>
  <a href="staff-portal.php?page=documents" class="<?=$page==='documents'?'active':''?>">Documents</a>
  <a href="staff-portal.php?page=notifications" class="<?=$page==='notifications'?'active':''?>">Notifications</a>
</nav>

<?php if ($page === 'dashboard'): ?>
<div class="stats-grid">
  <div class="stat-box"><div class="num"><?=count($myLeaves)?></div><div class="label">Leave Applications</div></div>
  <div class="stat-box"><div class="num"><?=count($myPayslips)?></div><div class="label">Payslips</div></div>
  <div class="stat-box"><div class="num"><?=count($myDuties)?></div><div class="label">Upcoming Duties</div></div>
  <div class="stat-box"><div class="num"><?=count($myNotifications)?></div><div class="label">Notifications</div></div>
</div>

<div class="row">
  <div class="col-md-6">
    <div class="sp-card"><h3>Leave Balances</h3>
      <table class="table table-sm"><thead><tr><th>Leave Type</th><th>Allocated</th><th>Used</th><th>Remaining</th></tr></thead><tbody>
      <?php foreach ($leaveBalances as $lb): ?>
        <tr><td><?=htmlspecialchars($lb['type_name'])?></td><td><?=(int)$lb['days_per_year']?></td><td><?=(int)$lb['used_days']?></td><td><strong><?=(int)$lb['remaining']?></strong></td></tr>
      <?php endforeach; ?>
      </tbody></table>
    </div>
  </div>
  <div class="col-md-6">
    <div class="sp-card"><h3>Recent Notifications</h3>
      <?php if (empty($myNotifications)): ?><p class="text-muted">No notifications.</p>
      <?php else: foreach (array_slice($myNotifications,0,5) as $n): ?>
        <div class="mb-2 pb-2 border-bottom"><strong><?=htmlspecialchars($n['title'])?></strong><br><small class="text-muted"><?=htmlspecialchars(substr($n['message'],0,100))?></small></div>
      <?php endforeach; endif; ?>
    </div>
  </div>
</div>
<?php elseif ($page === 'profile'): ?>
<div class="sp-card"><h3>My Profile</h3>
<form method="post" class="row g-3">
  <input type="hidden" name="action" value="update_profile">
  <div class="col-md-6"><label class="form-label">Full Name</label><input class="form-control" value="<?=htmlspecialchars($staff['full_name']??'')?>" readonly></div>
  <div class="col-md-6"><label class="form-label">Email</label><input class="form-control" value="<?=htmlspecialchars($staff['email']??'')?>" readonly></div>
  <div class="col-md-6"><label class="form-label">Phone</label><input class="form-control" name="phone" value="<?=htmlspecialchars($staff['phone']??'')?>"></div>
  <div class="col-md-6"><label class="form-label">Position</label><input class="form-control" value="<?=htmlspecialchars($staff['position']??'')?>" readonly></div>
  <div class="col-md-6"><label class="form-label">Department</label><input class="form-control" value="<?=htmlspecialchars($staff['department']??'')?>" readonly></div>
  <div class="col-12"><label class="form-label">Address</label><textarea class="form-control" name="address" rows="2"><?=htmlspecialchars($staff['address']??'')?></textarea></div>
  <div class="col-md-6"><label class="form-label">Next of Kin</label><input class="form-control" name="next_of_kin_name" value="<?=htmlspecialchars($staff['next_of_kin_name']??'')?>"></div>
  <div class="col-md-6"><label class="form-label">Next of Kin Phone</label><input class="form-control" name="next_of_kin_phone" value="<?=htmlspecialchars($staff['next_of_kin_phone']??'')?>"></div>
  <div class="col-md-6"><label class="form-label">Relationship</label><input class="form-control" name="next_of_kin_relationship" value="<?=htmlspecialchars($staff['next_of_kin_relationship']??'')?>"></div>
  <div class="col-md-6"><label class="form-label">Emergency Contact</label><input class="form-control" name="emergency_contact_name" value="<?=htmlspecialchars($staff['emergency_contact_name']??'')?>"></div>
  <div class="col-md-6"><label class="form-label">Emergency Phone</label><input class="form-control" name="emergency_contact_phone" value="<?=htmlspecialchars($staff['emergency_contact_phone']??'')?>"></div>
  <div class="col-12"><button class="btn btn-primary">Update Profile</button></div>
</form></div>
<?php elseif ($page === 'leave'): ?>
<div class="row">
  <div class="col-md-5">
    <div class="sp-card"><h3>Apply for Leave</h3>
    <form method="post">
      <input type="hidden" name="action" value="apply_leave">
      <div class="mb-3"><label class="form-label">Leave Type</label>
        <select class="form-select" name="leave_type_id" required><?php foreach ($leaveTypes as $lt): ?><option value="<?=$lt['id']?>"><?=htmlspecialchars($lt['type_name']??$lt['leave_type_name'])?> (<?=(int)$lt['days_per_year']?> days)</option><?php endforeach; ?></select></div>
      <div class="mb-3"><label class="form-label">Start Date</label><input type="date" class="form-control" name="start_date" required></div>
      <div class="mb-3"><label class="form-label">End Date</label><input type="date" class="form-control" name="end_date" required></div>
      <div class="mb-3"><label class="form-label">Reason</label><textarea class="form-control" name="reason" rows="3"></textarea></div>
      <button class="btn btn-primary">Submit Application</button>
    </form></div>
  </div>
  <div class="col-md-7">
    <div class="sp-card"><h3>Leave Balances</h3>
    <table class="table table-sm"><thead><tr><th>Type</th><th>Allocated</th><th>Used</th><th>Remaining</th></tr></thead><tbody>
    <?php foreach ($leaveBalances as $lb): ?><tr><td><?=htmlspecialchars($lb['type_name'])?></td><td><?=(int)$lb['days_per_year']?></td><td><?=(int)$lb['used_days']?></td><td><strong><?=(int)$lb['remaining']?></strong></td></tr><?php endforeach; ?>
    </tbody></table></div>
    <div class="sp-card"><h3>My Leave Applications</h3>
    <div class="table-responsive"><table class="table table-sm"><thead><tr><th>Type</th><th>Dates</th><th>Status</th></tr></thead><tbody>
    <?php foreach ($myLeaves as $l): ?><tr><td><?=htmlspecialchars($l['type_name'])?></td><td><?=htmlspecialchars($l['start_date'])?> - <?=htmlspecialchars($l['end_date'])?></td><td><?=hrStatusBadge($l['status'])?></td></tr><?php endforeach; ?>
    </tbody></table></div></div>
  </div>
</div>
<?php elseif ($page === 'payslips'): ?>
<div class="sp-card"><h3>My Payslips</h3>
<div class="table-responsive"><table class="table table-sm"><thead><tr><th>Period</th><th>Basic</th><th>Gross</th><th>Net</th><th>Status</th><th></th></tr></thead><tbody>
<?php foreach ($myPayslips as $p): ?><tr><td><?=htmlspecialchars($p['salary_month'])?></td><td><?=number_format($p['basic_salary'])?></td><td><?=number_format($p['gross_pay'])?></td><td><strong><?=number_format($p['net_pay'])?></strong></td><td><?=hrStatusBadge($p['status'])?></td><td><a href="dashboards/print_certificate.php?id=<?=$p['id']?>" class="btn btn-sm btn-outline-primary">View</a></td></tr><?php endforeach; ?>
</tbody></table></div></div>
<?php elseif ($page === 'schedule'): ?>
<div class="sp-card"><h3>My Duty Schedule</h3>
<div class="table-responsive"><table class="table table-sm"><thead><tr><th>Date</th><th>Type</th><th>Location</th><th>Time</th><th>Status</th></tr></thead><tbody>
<?php foreach ($myDuties as $d): ?><tr><td><?=htmlspecialchars($d['duty_date'])?></td><td><?=htmlspecialchars($d['duty_type'])?></td><td><?=htmlspecialchars($d['location'])?></td><td><?=htmlspecialchars($d['start_time']??'-')?> - <?=htmlspecialchars($d['end_time']??'-')?></td><td><?=hrStatusBadge($d['status'])?></td></tr><?php endforeach; ?>
</tbody></table></div></div>
<?php elseif ($page === 'documents'): ?>
<div class="sp-card"><h3>My Documents</h3>
<div class="table-responsive"><table class="table table-sm"><thead><tr><th>Document</th><th>Type</th><th>Uploaded</th><th>Status</th><th></th></tr></thead><tbody>
<?php foreach ($myDocuments as $d): ?><tr><td><?=htmlspecialchars($d['document_name'])?></td><td><?=htmlspecialchars($d['document_type'])?></td><td><?=htmlspecialchars($d['created_at'])?></td><td><?=$d['is_verified']?'<span class="badge bg-success">Verified</span>':'<span class="badge bg-warning">Pending</span>'?></td><td><a href="<?=htmlspecialchars($d['file_path'])?>" class="btn btn-sm btn-outline-primary" download>Download</a></td></tr><?php endforeach; ?>
</tbody></table></div></div>
<?php elseif ($page === 'notifications'): ?>
<div class="sp-card"><h3>Notifications</h3>
<?php foreach ($myNotifications as $n): ?>
<div class="p-3 mb-2 border rounded <?=$n['is_read']?'bg-white':'bg-light'?>">
  <strong><?=htmlspecialchars($n['title'])?></strong>
  <p class="mb-0 text-muted small"><?=htmlspecialchars($n['message'])?></p>
  <small class="text-muted"><?=htmlspecialchars($n['created_at'])?></small>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>
</div>
<script>document.addEventListener('DOMContentLoaded',function(){var t='<?=htmlspecialchars($_SESSION['csrf_token'])?>';document.querySelectorAll('form[method="post"]').forEach(function(f){if(!f.querySelector('input[name="csrf_token"]')){var i=document.createElement('input');i.type='hidden';i.name='csrf_token';i.value=t;f.appendChild(i);}});});</script>
<?php include_once __DIR__ . '/includes/dashboard_footer.php'; ?>
</body></html>
