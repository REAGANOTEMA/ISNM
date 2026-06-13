<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';

$ctx          = bootstrapStaffDashboard(['hr', 'manager']);
$auth_service = $ctx['auth'];
$user         = $ctx['user'];
$staff_conn   = $ctx['staff'];
$students_conn = $ctx['students'];

$user_id   = (int)($_SESSION['user_id'] ?? 0);
$user_name = $_SESSION['full_name'] ?? 'HR Manager';

// Safe stats
$stats            = getDashboardStats($staff_conn, $user_id, 'HR Manager');
$total_staff      = $stats['total_staff'];
$total_students   = $stats['total_students'];

// HR specific counts
$active_staff = 0; $on_leave = 0; $pending_applications = 0;
if ($staff_conn) {
    $r1 = $staff_conn->query("SELECT COUNT(*) c FROM staff WHERE status='Active'");
    if ($r1) $active_staff = (int)$r1->fetch_assoc()['c'];
    $r2 = $staff_conn->query("SELECT COUNT(*) c FROM staff WHERE status='On Leave'");
    if ($r2) $on_leave = (int)$r2->fetch_assoc()['c'];
    $r3 = $staff_conn->query("SELECT COUNT(*) c FROM staff_leave_requests WHERE status='Pending'");
    if ($r3) $pending_applications = (int)$r3->fetch_assoc()['c'];
}

// Active students
$active_students = 0;
if ($students_conn) {
    $r4 = $students_conn->query("SELECT COUNT(*) c FROM students WHERE status='Active'");
    if ($r4) $active_students = (int)$r4->fetch_assoc()['c'];
}

// Staff list
$staff_list = [];
if ($staff_conn) {
    $sl = $staff_conn->query("SELECT s.id,s.staff_id,s.full_name,s.email,s.position,s.department,s.status,s.hire_date,sr.role_name FROM staff s LEFT JOIN staff_roles sr ON s.role_id=sr.id ORDER BY s.full_name LIMIT 30");
    if ($sl) $staff_list = $sl->fetch_all(MYSQLI_ASSOC);
}

// Leave requests
$leave_requests = [];
if ($staff_conn) {
    $lr = $staff_conn->query("SELECT slr.*,s.full_name FROM staff_leave_requests slr JOIN staff s ON slr.staff_id=s.id ORDER BY slr.created_at DESC LIMIT 10");
    if ($lr) $leave_requests = $lr->fetch_all(MYSQLI_ASSOC);
}

// Recent activities
$recent_activities = [];
if ($staff_conn) {
    $ra = $staff_conn->query("SELECT activity_description as activity, created_at FROM staff_activity_log ORDER BY created_at DESC LIMIT 8");
    if ($ra) $recent_activities = $ra->fetch_all(MYSQLI_ASSOC);
}

// Roles for add staff form
$roles = [];
if ($staff_conn) {
    $rr = $staff_conn->query("SELECT id, role_name FROM staff_roles ORDER BY role_name");
    if ($rr) $roles = $rr->fetch_all(MYSQLI_ASSOC);
}

// POST: Add staff
if ($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['action']??'') === 'add_staff') {
    $fn   = trim($_POST['full_name'] ?? '');
    $em   = trim($_POST['email'] ?? '');
    $pos  = trim($_POST['position'] ?? '');
    $dept = trim($_POST['department'] ?? '');
    $rid  = (int)($_POST['role_id'] ?? 0);
    $ph   = trim($_POST['phone'] ?? '');
    if ($fn && $em && $staff_conn) {
        $sid = 'STAFF'.date('Y').str_pad(mt_rand(1,9999),4,'0',STR_PAD_LEFT);
        $hash = password_hash('isnm2026', PASSWORD_BCRYPT);
        $stmt = $staff_conn->prepare("INSERT INTO staff (staff_id,full_name,email,password,phone,position,department,role_id,status,hire_date,login_attempts,created_at) VALUES (?,?,?,?,?,?,?,?,'Active',CURDATE(),0,NOW())");
        if ($stmt) {
            $stmt->bind_param('sssssssi',$sid,$fn,$em,$hash,$ph,$pos,$dept,$rid);
            $stmt->execute();
            $_SESSION['success'] = "Staff member $fn added successfully.";
        }
    }
    header('Location: hr-manager.php'); exit;
}

// POST: Approve/Reject leave
if ($_SERVER['REQUEST_METHOD']==='POST' && in_array($_POST['action']??'', ['approve_leave','reject_leave'])) {
    $lid    = (int)($_POST['leave_id'] ?? 0);
    $status = ($_POST['action']==='approve_leave') ? 'Approved' : 'Rejected';
    if ($staff_conn && $lid) {
        $stmt = $staff_conn->prepare("UPDATE staff_leave_requests SET status=?, approved_by=?, approval_date=NOW() WHERE id=?");
        if ($stmt) { $stmt->bind_param('sii',$status,$user_id,$lid); $stmt->execute(); }
        $_SESSION['success'] = "Leave request $status.";
    }
    header('Location: hr-manager.php#leave'); exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include_once __DIR__ . '/../includes/_favicon.php'; ?>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>HR Manager Dashboard – ISNM</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
<link href="dashboard-style.css" rel="stylesheet">
<link href="dashboard-mobile.css" rel="stylesheet">
<style>
body{font-family:'Segoe UI',sans-serif;background:#f0f2f5}
.page-wrap{margin-left:280px;min-height:100vh}
@media(max-width:768px){.page-wrap{margin-left:0}}
.top-bar{background:#fff;padding:14px 22px;display:flex;justify-content:space-between;align-items:center;box-shadow:0 2px 8px rgba(0,0,0,.07);position:sticky;top:0;z-index:100}
.content{padding:22px}
.stat-card{background:#fff;border-radius:12px;padding:18px;display:flex;align-items:center;gap:12px;box-shadow:0 2px 10px rgba(0,0,0,.07)}
.si{width:48px;height:48px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:1.15rem;color:#fff;flex-shrink:0}
.si-blue{background:linear-gradient(135deg,#1a237e,#3949ab)}
.si-green{background:linear-gradient(135deg,#2e7d32,#43a047)}
.si-cyan{background:linear-gradient(135deg,#0277bd,#039be5)}
.si-orange{background:linear-gradient(135deg,#e65100,#fb8c00)}
.si-red{background:linear-gradient(135deg,#b71c1c,#ef5350)}
.stat-content h3{font-size:1.5rem;font-weight:700;margin:0}
.stat-content p{font-size:.78rem;color:#666;margin:0}
.card-section{background:#fff;border-radius:12px;padding:20px;margin-bottom:22px;box-shadow:0 2px 10px rgba(0,0,0,.07)}
.card-section h2{font-size:1rem;font-weight:700;padding-bottom:10px;border-bottom:2px solid #f0f2f5;margin-bottom:14px}
</style>
</head>
<body>

<?php include_once '../includes/sidebar.php'; ?>

<div class="page-wrap">
  <div class="top-bar">
    <div>
      <strong><i class="fas fa-users-cog me-2 text-danger"></i>HR Manager</strong>
      <div class="text-muted small">Human Resources – ISNM | <?= date('D, d M Y') ?></div>
    </div>
    <a href="../logout.php" class="btn btn-sm btn-outline-danger"><i class="fas fa-sign-out-alt me-1"></i>Logout</a>
  </div>

  <div class="content">
    <?php if(!empty($_SESSION['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show"><?= htmlspecialchars($_SESSION['success']) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php unset($_SESSION['success']); endif; ?>
    <?php if(!empty($_SESSION['error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show"><?= htmlspecialchars($_SESSION['error']) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php unset($_SESSION['error']); endif; ?>

    <!-- Stats -->
    <div class="row g-3 mb-4">
      <?php $cards=[
        ['Total Staff',$total_staff,'si-blue','users'],
        ['Active Staff',$active_staff,'si-green','user-check'],
        ['On Leave',$on_leave,'si-orange','calendar-alt'],
        ['Pending Leave Requests',$pending_applications,'si-cyan','hourglass-half'],
        ['Total Students',$active_students,'si-red','user-graduate'],
      ]; foreach($cards as $c): ?>
      <div class="col-6 col-md-2">
        <div class="stat-card">
          <div class="si <?= $c[2] ?>"><i class="fas fa-<?= $c[3] ?>"></i></div>
          <div class="stat-content"><h3><?= number_format($c[1]) ?></h3><p><?= $c[0] ?></p></div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

    <!-- Quick Actions -->
    <div class="card-section">
      <h2><i class="fas fa-bolt me-2"></i>Quick Actions</h2>
      <div class="d-flex flex-wrap gap-2">
        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addStaffModal"><i class="fas fa-user-plus me-1"></i>Add Staff</button>
        <a href="../hr_dashboard.php" class="btn btn-outline-primary btn-sm"><i class="fas fa-tachometer-alt me-1"></i>Full HR Dashboard</a>
        <a href="../hr_staff_records.php" class="btn btn-outline-info btn-sm"><i class="fas fa-id-card me-1"></i>Staff Records</a>
        <a href="../hr_leave.php" class="btn btn-outline-warning btn-sm"><i class="fas fa-calendar me-1"></i>Leave Management</a>
        <a href="../hr_payroll.php" class="btn btn-outline-success btn-sm"><i class="fas fa-money-check me-1"></i>Payroll</a>
        <a href="../hr_performance.php" class="btn btn-outline-secondary btn-sm"><i class="fas fa-chart-line me-1"></i>Performance</a>
        <a href="../hr_reports.php" class="btn btn-outline-dark btn-sm"><i class="fas fa-file-alt me-1"></i>Reports</a>
      </div>
    </div>

    <!-- Staff Directory -->
    <div class="card-section" id="staff">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="mb-0"><i class="fas fa-id-badge me-2"></i>Staff Directory (<?= count($staff_list) ?>)</h2>
      </div>
      <div class="table-responsive">
        <table class="table table-sm table-hover align-middle">
          <thead class="table-light"><tr><th>Staff ID</th><th>Full Name</th><th>Role</th><th>Department</th><th>Email</th><th>Status</th><th>Hire Date</th></tr></thead>
          <tbody>
          <?php if(empty($staff_list)): ?>
            <tr><td colspan="7" class="text-center text-muted py-3">No staff records found.</td></tr>
          <?php else: foreach($staff_list as $s):
            $bc = $s['status']==='Active'?'bg-success':($s['status']==='On Leave'?'bg-warning text-dark':'bg-danger');
          ?>
            <tr>
              <td><code><?= htmlspecialchars($s['staff_id']) ?></code></td>
              <td><strong><?= htmlspecialchars($s['full_name']) ?></strong></td>
              <td><?= htmlspecialchars($s['role_name'] ?? $s['position']) ?></td>
              <td><?= htmlspecialchars($s['department'] ?? '—') ?></td>
              <td><small><?= htmlspecialchars($s['email']) ?></small></td>
              <td><span class="badge <?= $bc ?>"><?= $s['status'] ?></span></td>
              <td><?= $s['hire_date'] ? date('d M Y',strtotime($s['hire_date'])) : '—' ?></td>
            </tr>
          <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Leave Requests -->
    <div class="card-section" id="leave">
      <h2><i class="fas fa-calendar-alt me-2"></i>Leave Requests (<?= count($leave_requests) ?>)</h2>
      <?php if(empty($leave_requests)): ?>
      <p class="text-muted small">No leave requests found.</p>
      <?php else: ?>
      <div class="table-responsive">
        <table class="table table-sm table-hover align-middle">
          <thead class="table-light"><tr><th>Staff</th><th>Leave Type</th><th>Start</th><th>End</th><th>Days</th><th>Status</th><th>Actions</th></tr></thead>
          <tbody>
          <?php foreach($leave_requests as $lr):
            $bc = $lr['status']==='Approved'?'bg-success':($lr['status']==='Rejected'?'bg-danger':'bg-warning text-dark');
          ?>
            <tr>
              <td><?= htmlspecialchars($lr['full_name']) ?></td>
              <td><?= htmlspecialchars($lr['leave_type']) ?></td>
              <td><?= $lr['start_date'] ?></td>
              <td><?= $lr['end_date'] ?></td>
              <td><?= $lr['total_days'] ?></td>
              <td><span class="badge <?= $bc ?>"><?= $lr['status'] ?></span></td>
              <td>
                <?php if($lr['status']==='Pending'): ?>
                <form method="POST" class="d-inline">
                  <input type="hidden" name="action" value="approve_leave">
                  <input type="hidden" name="leave_id" value="<?= $lr['id'] ?>">
                  <button class="btn btn-xs btn-success" style="font-size:.75rem;padding:2px 8px">Approve</button>
                </form>
                <form method="POST" class="d-inline">
                  <input type="hidden" name="action" value="reject_leave">
                  <input type="hidden" name="leave_id" value="<?= $lr['id'] ?>">
                  <button class="btn btn-xs btn-danger" style="font-size:.75rem;padding:2px 8px">Reject</button>
                </form>
                <?php else: ?>
                <span class="text-muted small">Processed</span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php endif; ?>
    </div>

    <!-- Recent Activities -->
    <div class="card-section">
      <h2><i class="fas fa-history me-2"></i>Recent Activities</h2>
      <?php if(empty($recent_activities)): ?>
      <p class="text-muted small">No recent activities.</p>
      <?php else: ?>
      <ul class="list-unstyled mb-0">
        <?php foreach($recent_activities as $act): ?>
        <li class="border-bottom py-2 small">
          <?= htmlspecialchars($act['activity']??'Activity') ?>
          <span class="text-muted ms-2"><?= $act['created_at'] ? date('d M Y H:i',strtotime($act['created_at'])) : '' ?></span>
        </li>
        <?php endforeach; ?>
      </ul>
      <?php endif; ?>
    </div>

  </div>
</div>

<!-- ADD STAFF MODAL -->
<div class="modal fade" id="addStaffModal" tabindex="-1">
  <div class="modal-dialog">
    <form method="POST" class="modal-content">
      <input type="hidden" name="action" value="add_staff">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title"><i class="fas fa-user-plus me-2"></i>Add Staff Member</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="row g-3">
          <div class="col-12"><label class="form-label fw-semibold">Full Name *</label><input type="text" name="full_name" class="form-control" required></div>
          <div class="col-12"><label class="form-label fw-semibold">Email *</label><input type="email" name="email" class="form-control" required></div>
          <div class="col-md-6"><label class="form-label fw-semibold">Phone</label><input type="text" name="phone" class="form-control"></div>
          <div class="col-md-6"><label class="form-label fw-semibold">Position</label><input type="text" name="position" class="form-control"></div>
          <div class="col-md-6"><label class="form-label fw-semibold">Department</label><input type="text" name="department" class="form-control"></div>
          <div class="col-md-6"><label class="form-label fw-semibold">Role</label>
            <select name="role_id" class="form-select">
              <option value="0">Select Role</option>
              <?php foreach($roles as $r): ?>
              <option value="<?= $r['id'] ?>"><?= htmlspecialchars($r['role_name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-12"><small class="text-muted">Default password: <code>isnm2026</code></small></div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Add Staff</button>
      </div>
    </form>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
</body>
</html>
