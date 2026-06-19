<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
require_once __DIR__ . '/../includes/news_management_widget.php';

$ctx          = bootstrapStaffDashboard(['hr', 'manager']);
$auth_service = $ctx['auth'];
$user         = $ctx['user'];
$user_role    = $_SESSION['role'] ?? '';
$staff_conn   = $ctx['staff'];
$students_conn = $ctx['students'];
$website_conn  = $ctx['website'];

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
<?php include_once __DIR__ . '/../includes/dashboard_head.php'; ?>
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

  <!-- Section Hub Navigation -->
  <div class="section-nav mb-3" style="overflow-x:auto;white-space:nowrap;padding:8px 0;">
    <a href="#overview" class="section-tab active" data-section="overview"><i class="fas fa-home me-1"></i>Overview</a>
    <a href="#staff-records" class="section-tab" data-section="staff-records"><i class="fas fa-id-card me-1"></i>Staff Records</a>
    <a href="#attendance" class="section-tab" data-section="attendance"><i class="fas fa-calendar-check me-1"></i>Attendance</a>
    <a href="#leave" class="section-tab" data-section="leave"><i class="fas fa-calendar-alt me-1"></i>Leave</a>
    <a href="#performance" class="section-tab" data-section="performance"><i class="fas fa-chart-line me-1"></i>Performance</a>
    <a href="#training" class="section-tab" data-section="training"><i class="fas fa-graduation-cap me-1"></i>Training</a>
    <a href="#recruitment" class="section-tab" data-section="recruitment"><i class="fas fa-user-plus me-1"></i>Recruitment</a>
    <a href="#contracts" class="section-tab" data-section="contracts"><i class="fas fa-file-contract me-1"></i>Contracts</a>
    <a href="#disciplinary" class="section-tab" data-section="disciplinary"><i class="fas fa-gavel me-1"></i>Disciplinary</a>
    <a href="#payroll" class="section-tab" data-section="payroll"><i class="fas fa-money-check me-1"></i>Payroll</a>
    <a href="#communications" class="section-tab" data-section="communications"><i class="fas fa-bullhorn me-1"></i>Comms</a>
    <a href="#reports" class="section-tab" data-section="reports"><i class="fas fa-chart-bar me-1"></i>Reports</a>
    <a href="#roles" class="section-tab" data-section="roles"><i class="fas fa-user-shield me-1"></i>Roles</a>
  </div>

  <div class="content content-section dashboard-section active" data-section="overview">
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

    <!-- STORE REQUESTS -->
    <?php
    $storeReqs = [];
    if ($staff_conn) {
        $sr = $staff_conn->query("SELECT sr.request_number, sr.urgency, sr.status, sr.created_at, s.full_name as requester FROM store_requests sr LEFT JOIN staff s ON sr.requested_by=s.id WHERE sr.status IN ('pending','forwarded') ORDER BY FIELD(sr.urgency,'urgent','high','medium','low'), sr.created_at ASC LIMIT 5");
        if ($sr) while ($row = $sr->fetch_assoc()) $storeReqs[] = $row;
    }
    ?>
    <div class="card-section">
      <h2><i class="fas fa-shopping-cart me-2 text-warning"></i>Pending Store Requests <?= count($storeReqs) ? '<span class="badge bg-danger ms-1">'.count($storeReqs).'</span>' : '' ?></h2>
      <?php if (empty($storeReqs)): ?>
        <p class="text-muted small">No pending store requests.</p>
      <?php else: foreach ($storeReqs as $sr_): ?>
        <div class="d-flex justify-content-between align-items-center border-bottom py-2">
          <div><code class="fw-bold"><?= htmlspecialchars($sr_['request_number']) ?></code><small class="text-muted ms-2">by <?= htmlspecialchars($sr_['requester'] ?? '') ?></small></div>
          <div class="d-flex align-items-center gap-2">
            <span class="badge bg-<?= $sr_['urgency']==='urgent'?'danger':($sr_['urgency']==='high'?'warning text-dark':'info') ?>"><?= $sr_['urgency'] ?></span>
            <small class="text-muted"><?= date('d M', strtotime($sr_['created_at'])) ?></small>
          </div>
        </div>
      <?php endforeach; ?>
        <div class="text-center mt-2"><a href="../dashboards/storekeeper.php" class="btn btn-sm btn-outline-warning"><i class="fas fa-warehouse me-1"></i>Manage Store</a></div>
      <?php endif; ?>
    </div>

    <!-- Quick Actions -->
    <div class="card-section">
      <h2><i class="fas fa-bolt me-2"></i>Quick Actions</h2>
      <div class="d-flex flex-wrap gap-2">
        <a href="../store_request.php" class="btn btn-outline-dark btn-sm"><i class="fas fa-shopping-cart me-1"></i>Store Request</a>
        <a href="../news.php" class="btn btn-outline-dark btn-sm"><i class="fas fa-newspaper me-1"></i>Manage News</a>
        <a href="../student-directory.php" class="btn btn-outline-dark btn-sm"><i class="fas fa-address-book me-1"></i>Student Directory</a>
        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addStaffModal"><i class="fas fa-user-plus me-1"></i>Add Staff</button>
        <a href="../hr_staff_records.php" class="btn btn-outline-info btn-sm"><i class="fas fa-id-card me-1"></i>Staff Records</a>
        <a href="../hr_leave.php" class="btn btn-outline-warning btn-sm"><i class="fas fa-calendar me-1"></i>Leave Management</a>
        <a href="../hr_payroll.php" class="btn btn-outline-success btn-sm"><i class="fas fa-money-check me-1"></i>Payroll</a>
        <a href="../hr_performance.php" class="btn btn-outline-secondary btn-sm"><i class="fas fa-chart-line me-1"></i>Performance</a>
        <a href="../hr_reports.php" class="btn btn-outline-dark btn-sm"><i class="fas fa-file-alt me-1"></i>Reports</a>
      </div>
    </div>

    <!-- NEWS MANAGEMENT -->
    <div class="card-section">
      <?php renderNewsWidget($staff_conn, $website_conn, $user_id, $user_name, $_SESSION['role'] ?? 'HR Manager', 5); ?>
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
              <td><?= htmlspecialchars($s['department'] ?? '-') ?></td>
              <td><small><?= htmlspecialchars($s['email']) ?></small></td>
              <td><span class="badge <?= $bc ?>"><?= $s['status'] ?></span></td>
              <td><?= $s['hire_date'] ? date('d M Y',strtotime($s['hire_date'])) : '-' ?></td>
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

  <!-- HR Sections Hub -->
  <?php
  $sectionCounts = [];
  if ($staff_conn) {
      $sectionCounts['total_staff'] = $active_staff;
      $sectionCounts['attendance_today'] = (int)($staff_conn->query("SELECT COUNT(*) c FROM staff_attendance WHERE attendance_date=CURDATE()")->fetch_assoc()['c']??0);
      $sectionCounts['pending_leave'] = $pending_applications;
      $sectionCounts['pending_appraisals'] = (int)($staff_conn->query("SELECT COUNT(*) c FROM staff_appraisals WHERE status='Pending'")->fetch_assoc()['c']??0);
      $sectionCounts['active_trainings'] = (int)($staff_conn->query("SELECT COUNT(*) c FROM staff_training WHERE status='In Progress'")->fetch_assoc()['c']??0);
      $sectionCounts['active_recruitments'] = (int)($staff_conn->query("SELECT COUNT(*) c FROM staff_recruitment WHERE status='Open'")->fetch_assoc()['c']??0);
      $sectionCounts['active_contracts'] = (int)($staff_conn->query("SELECT COUNT(*) c FROM staff_contracts WHERE status='Active'")->fetch_assoc()['c']??0);
      $sectionCounts['disciplinary_cases'] = (int)($staff_conn->query("SELECT COUNT(*) c FROM staff_disciplinary WHERE status='Open'")->fetch_assoc()['c']??0);
  }
  ?>

  <div class="content content-section dashboard-section" data-section="staff-records">
    <div class="card-section">
      <h2><i class="fas fa-id-card me-2"></i>Staff Records & Documentation</h2>
      <div class="row g-3">
        <div class="col-md-4"><div class="card border shadow-sm p-3 text-center"><div class="fs-4 fw-bold text-primary"><?= $sectionCounts['total_staff'] ?></div><small>Total Active Staff</small><a href="staff-directory.php" class="btn btn-sm btn-outline-primary mt-2"><i class="fas fa-address-book me-1"></i>View Directory</a></div></div>
        <div class="col-md-4"><div class="card border shadow-sm p-3 text-center"><i class="fas fa-user-plus fa-2x text-success mb-2"></i><a href="onboarding.php" class="btn btn-sm btn-outline-success">Onboarding & Orientation</a></div></div>
        <div class="col-md-4"><div class="card border shadow-sm p-3 text-center"><i class="fas fa-upload fa-2x text-info mb-2"></i><a href="staff_profile_management.php" class="btn btn-sm btn-outline-info">Upload Documents</a></div></div>
      </div>
    </div>
  </div>

  <div class="content content-section dashboard-section" data-section="attendance">
    <div class="card-section">
      <h2><i class="fas fa-calendar-check me-2"></i>Attendance & Time Management</h2>
      <div class="row g-3">
        <div class="col-md-4"><div class="card border shadow-sm p-3 text-center"><div class="fs-4 fw-bold text-success"><?= $sectionCounts['attendance_today'] ?></div><small>Checked In Today</small><a href="staff-attendance.php" class="btn btn-sm btn-outline-success mt-2"><i class="fas fa-clock me-1"></i>Manage Attendance</a></div></div>
        <div class="col-md-4"><div class="card border shadow-sm p-3 text-center"><div class="fs-4 fw-bold text-warning"><?= $sectionCounts['pending_leave'] ?></div><small>Pending Leave</small></div></div>
        <div class="col-md-4"><div class="card border shadow-sm p-3 text-center"><i class="fas fa-calendar-alt fa-2x text-primary mb-2"></i><a href="duty-rosters.php" class="btn btn-sm btn-outline-primary">Duty Rosters</a></div></div>
      </div>
    </div>
  </div>

  <div class="content content-section dashboard-section" data-section="performance">
    <div class="card-section">
      <h2><i class="fas fa-chart-line me-2"></i>Performance Appraisal</h2>
      <div class="row g-3">
        <div class="col-md-4"><div class="card border shadow-sm p-3 text-center"><div class="fs-4 fw-bold text-warning"><?= $sectionCounts['pending_appraisals'] ?></div><small>Pending Appraisals</small><a href="performance-appraisal.php" class="btn btn-sm btn-outline-primary mt-2"><i class="fas fa-clipboard-check me-1"></i>Manage Appraisals</a></div></div>
        <div class="col-md-4"><div class="card border shadow-sm p-3 text-center"><i class="fas fa-graduation-cap fa-2x text-info mb-2"></i><div><small>Training & CPD</small></div><a href="training-cpd.php" class="btn btn-sm btn-outline-info">Manage Training</a></div></div>
        <div class="col-md-4"><div class="card border shadow-sm p-3 text-center"><div class="fs-4 fw-bold text-primary"><?= $sectionCounts['active_trainings'] ?></div><small>Active Trainings</small></div></div>
      </div>
    </div>
  </div>

  <div class="content content-section dashboard-section" data-section="recruitment">
    <div class="card-section">
      <h2><i class="fas fa-user-plus me-2"></i>Recruitment & Selection</h2>
      <div class="row g-3">
        <div class="col-md-4"><div class="card border shadow-sm p-3 text-center"><div class="fs-4 fw-bold text-success"><?= $sectionCounts['active_recruitments'] ?></div><small>Open Positions</small><a href="recruitment.php" class="btn btn-sm btn-outline-primary mt-2"><i class="fas fa-briefcase me-1"></i>Manage Recruitment</a></div></div>
        <div class="col-md-4"><div class="card border shadow-sm p-3 text-center"><i class="fas fa-file-alt fa-2x text-info mb-2"></i><a href="onboarding.php" class="btn btn-sm btn-outline-info">Onboarding</a></div></div>
        <div class="col-md-4"><div class="card border shadow-sm p-3 text-center"><i class="fas fa-sign-out-alt fa-2x text-danger mb-2"></i><a href="resignations.php" class="btn btn-sm btn-outline-danger">Resignations & Exit</a></div></div>
      </div>
    </div>
  </div>

  <div class="content content-section dashboard-section" data-section="contracts">
    <div class="card-section">
      <h2><i class="fas fa-file-contract me-2"></i>Contracts Management</h2>
      <div class="row g-3">
        <div class="col-md-4"><div class="card border shadow-sm p-3 text-center"><div class="fs-4 fw-bold text-success"><?= $sectionCounts['active_contracts'] ?></div><small>Active Contracts</small><a href="contracts-management.php" class="btn btn-sm btn-outline-primary mt-2"><i class="fas fa-file-signature me-1"></i>Manage Contracts</a></div></div>
        <div class="col-md-4"><div class="card border shadow-sm p-3 text-center"><i class="fas fa-handshake fa-2x text-warning mb-2"></i><a href="professional-licenses.php" class="btn btn-sm btn-outline-warning">Professional Licenses</a></div></div>
        <div class="col-md-4"><div class="card border shadow-sm p-3 text-center"><i class="fas fa-clock fa-2x text-secondary mb-2"></i><div><small>Contract Renewal Tracking</small></div></div></div>
      </div>
    </div>
  </div>

  <div class="content content-section dashboard-section" data-section="disciplinary">
    <div class="card-section">
      <h2><i class="fas fa-gavel me-2"></i>Disciplinary & Grievance</h2>
      <div class="row g-3">
        <div class="col-md-4"><div class="card border shadow-sm p-3 text-center"><div class="fs-4 fw-bold text-danger"><?= $sectionCounts['disciplinary_cases'] ?></div><small>Open Cases</small><a href="staff-disciplinary.php" class="btn btn-sm btn-outline-danger mt-2"><i class="fas fa-balance-scale me-1"></i>Manage Disciplinary</a></div></div>
        <div class="col-md-4"><div class="card border shadow-sm p-3 text-center"><i class="fas fa-file-alt fa-2x text-muted mb-2"></i><div><small>Grievance Reports</small></div></div></div>
      </div>
    </div>
  </div>

  <div class="content content-section dashboard-section" data-section="payroll">
    <div class="card-section">
      <h2><i class="fas fa-money-check me-2"></i>Payroll & Benefits</h2>
      <div class="row g-3">
        <div class="col-md-4"><div class="card border shadow-sm p-3 text-center"><i class="fas fa-wallet fa-2x text-success mb-2"></i><a href="bursar-payroll.php" class="btn btn-sm btn-outline-success">Payroll Processing</a></div></div>
        <div class="col-md-4"><div class="card border shadow-sm p-3 text-center"><i class="fas fa-file-invoice fa-2x text-primary mb-2"></i><div><small>Benefits Administration</small></div></div></div>
        <div class="col-md-4"><div class="card border shadow-sm p-3 text-center"><i class="fas fa-calculator fa-2x text-warning mb-2"></i><div><small>Salary Structure</small></div></div></div>
      </div>
    </div>
  </div>

  <div class="content content-section dashboard-section" data-section="communications">
    <div class="card-section">
      <h2><i class="fas fa-bullhorn me-2"></i>Staff Communication</h2>
      <div class="row g-3">
        <div class="col-md-6"><div class="card border shadow-sm p-3 text-center"><i class="fas fa-newspaper fa-2x text-primary mb-2"></i><a href="../news.php" class="btn btn-sm btn-outline-primary">Manage News</a></div></div>
        <div class="col-md-6"><div class="card border shadow-sm p-3 text-center"><i class="fas fa-bullhorn fa-2x text-info mb-2"></i><a href="../messaging.php" class="btn btn-sm btn-outline-info">Send Announcement</a></div></div>
      </div>
    </div>
  </div>

  <div class="content content-section dashboard-section" data-section="reports">
    <div class="card-section">
      <h2><i class="fas fa-chart-bar me-2"></i>Reports & Analytics</h2>
      <div class="row g-3">
        <div class="col-md-4"><div class="card border shadow-sm p-3 text-center"><i class="fas fa-users fa-2x text-primary mb-2"></i><div><small>Staff Utilization Reports</small></div></div></div>
        <div class="col-md-4"><div class="card border shadow-sm p-3 text-center"><i class="fas fa-calendar-alt fa-2x text-success mb-2"></i><div><small>Attendance Reports</small></div></div></div>
        <div class="col-md-4"><div class="card border shadow-sm p-3 text-center"><i class="fas fa-file-invoice fa-2x text-warning mb-2"></i><div><small>Payroll Reports</small></div></div></div>
      </div>
    </div>
  </div>

  <div class="content content-section dashboard-section" data-section="roles">
    <div class="card-section">
      <h2><i class="fas fa-user-shield me-2"></i>Role Management & Access Control</h2>
      <div class="row g-3">
        <div class="col-md-6"><div class="card border shadow-sm p-3 text-center"><i class="fas fa-user-tag fa-2x text-primary mb-2"></i><a href="staff-directory.php" class="btn btn-sm btn-outline-primary">Staff Roles & Permissions</a></div></div>
        <div class="col-md-6"><div class="card border shadow-sm p-3 text-center"><i class="fas fa-history fa-2x text-info mb-2"></i><a href="recycle_bin.php" class="btn btn-sm btn-outline-info">Audit Trail</a></div></div>
      </div>
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

<script>
// Section tab switching
document.querySelectorAll('.section-tab').forEach(function(tab) {
    tab.addEventListener('click', function(e) {
        e.preventDefault();
        var section = this.getAttribute('data-section');
        document.querySelectorAll('.section-tab').forEach(function(t) { t.classList.remove('active'); });
        this.classList.add('active');
        document.querySelectorAll('.dashboard-section').forEach(function(s) { s.classList.remove('active'); });
        var target = document.querySelector('.dashboard-section[data-section="' + section + '"]');
        if (target) target.classList.add('active');
        if (window.location.hash !== '#' + section) history.replaceState(null, '', '#' + section);
    });
});
// Check hash on load
var hash = window.location.hash.replace('#', '');
if (hash) {
    var tab = document.querySelector('.section-tab[data-section="' + hash + '"]');
    if (tab) tab.click();
}
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
</body>
</html>
