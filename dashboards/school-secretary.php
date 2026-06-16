<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
require_once __DIR__ . '/../includes/news_management_widget.php';

$ctx = bootstrapStaffDashboard(['school secretary', 'secretary']);
$auth_service  = $ctx['auth'];
$user          = $ctx['user'];
$staff_conn    = $ctx['staff'];
$students_conn = $ctx['students'];
$website_conn  = $ctx['website'];

$user_id   = (int)($_SESSION['user_id'] ?? 0);
$user_role = $_SESSION['role'] ?? '';
$user_name = $_SESSION['full_name'] ?? 'Secretary';

// Real stats from DB
$total_students    = 0; $total_staff = 0; $active_students = 0;
$today_appointments = 0; $pending_docs = 0; $unread_msgs = 0;
if ($students_conn) {
    $r = $students_conn->query("SELECT COUNT(*) c FROM students WHERE status='Active'");
    if ($r) $active_students = (int)$r->fetch_assoc()['c'];
    $r2 = $students_conn->query("SELECT COUNT(*) c FROM students");
    if ($r2) $total_students = (int)$r2->fetch_assoc()['c'];
}
if ($staff_conn) {
    $r = $staff_conn->query("SELECT COUNT(*) c FROM staff WHERE status='Active'");
    if ($r) $total_staff = (int)$r->fetch_assoc()['c'];
    // Document count from staff_documents if table exists
    $r2 = $staff_conn->query("SELECT COUNT(*) c FROM staff_documents");
    if ($r2) $pending_docs = (int)$r2->fetch_assoc()['c'];
    // Activity log as proxy for appointments
    $r3 = $staff_conn->query("SELECT COUNT(*) c FROM staff_activity_log WHERE DATE(created_at)=CURDATE()");
    if ($r3) $today_appointments = (int)$r3->fetch_assoc()['c'];
}

// Recent activities
$recent_activities = [];
if ($staff_conn) {
    $ra = $staff_conn->query("SELECT activity_description AS activity, created_at FROM staff_activity_log ORDER BY created_at DESC LIMIT 8");
    if ($ra) $recent_activities = $ra->fetch_all(MYSQLI_ASSOC);
}
if (empty($recent_activities)) {
    $recent_activities = [
        ['activity' => 'Dashboard initialized', 'created_at' => date('Y-m-d H:i:s')],
    ];
}

// POST: publish announcement via inline form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sec_ann_title'])) {
    $title    = trim($_POST['sec_ann_title'] ?? '');
    $body     = trim($_POST['sec_ann_body'] ?? '');
    $target   = $_POST['sec_ann_target'] ?? 'All';
    $priority = $_POST['sec_ann_priority'] ?? 'Normal';
    if ($title && $body && $students_conn) {
        $t = $staff_conn->real_escape_string($title);
        $b = $staff_conn->real_escape_string($body);
        $students_conn->query("INSERT INTO announcements (title,body,target_audience,priority,posted_by,is_active,created_at) VALUES ('$t','$b','$target','$priority',$user_id,1,NOW())");
        $_SESSION['sec_success'] = "Announcement published.";
    }
    header('Location: school-secretary.php'); exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include_once __DIR__ . '/../includes/_favicon.php'; ?>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>School Secretary – ISNM</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
<style>
body{font-family:'Segoe UI',sans-serif;background:#f0f2f5;margin:0}
.page-content{margin-left:280px;flex:1;min-height:100vh}
@media(max-width:768px){.page-content{margin-left:0}}
.top-bar{background:#fff;padding:14px 22px;display:flex;justify-content:space-between;align-items:center;box-shadow:0 2px 8px rgba(0,0,0,.07);position:sticky;top:0;z-index:100}
.content-area{padding:22px}
.stat-card{background:linear-gradient(to bottom,#ffe082 0%,#ffe082 5px,#fef9e7 5px,#fef9e7 100%);border-radius:14px;padding:20px;display:flex;align-items:center;gap:14px;transition:transform .25s}
.stat-card:hover{transform:translateY(-4px)}
.si{width:50px;height:50px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:1.2rem;color:#fff;flex-shrink:0}
.si-blue{background:linear-gradient(135deg,#1a237e,#3949ab)}
.si-green{background:linear-gradient(135deg,#2e7d32,#43a047)}
.si-cyan{background:linear-gradient(135deg,#0277bd,#039be5)}
.si-orange{background:linear-gradient(135deg,#e65100,#fb8c00)}
.si-purple{background:linear-gradient(135deg,#4a148c,#8e24aa)}
.si-red{background:linear-gradient(135deg,#b71c1c,#ef5350)}
.stat-content h3{font-size:1.6rem;font-weight:700;margin:0;line-height:1}
.stat-content p{font-size:.77rem;color:#666;margin:2px 0 0}
.section-card{background:#fff;border-radius:14px;padding:20px;margin-bottom:22px;box-shadow:0 1px 3px rgba(0,0,0,.06)}
.section-card h2{font-size:1rem;font-weight:700;margin-bottom:14px;padding-bottom:10px;border-bottom:2px solid #f0f2f5}
</style>
</head>
<body>
<?php include_once '../includes/sidebar.php'; ?>

<div class="page-content">
  <div class="top-bar">
    <div>
      <strong><i class="fas fa-user-tie me-2 text-primary"></i>School Secretary – <?= htmlspecialchars($user_name) ?></strong>
      <div class="text-muted small">Administrative Support &amp; Office Management | ISNM</div>
    </div>
    <div class="d-flex align-items-center gap-3">
      <span class="text-muted small d-none d-md-block"><?= date('D, d M Y') ?></span>
      <a href="../logout.php" class="btn btn-sm btn-outline-danger"><i class="fas fa-sign-out-alt me-1"></i>Logout</a>
    </div>
  </div>

  <div class="content-area">
    <?php if(!empty($_SESSION['sec_success'])): ?>
    <div class="alert alert-success alert-dismissible fade show py-2"><?= htmlspecialchars($_SESSION['sec_success']) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php unset($_SESSION['sec_success']); endif; ?>

    <!-- STATS -->
    <div class="row g-3 mb-4">
      <?php $cards = [
        ['Total Students', $total_students, 'si-blue', 'user-graduate'],
        ['Active Students', $active_students, 'si-green', 'users'],
        ['Staff Members', $total_staff, 'si-cyan', 'id-badge'],
        ['Appointments Today', $today_appointments, 'si-orange', 'calendar-check'],
        ['Documents Processed', $pending_docs, 'si-purple', 'file-alt'],
      ]; foreach($cards as $c): ?>
      <div class="col-6 col-md-2">
        <div class="stat-card">
          <div class="si <?= $c[2] ?>"><i class="fas fa-<?= $c[3] ?>"></i></div>
          <div class="stat-content"><h3><?= is_numeric($c[1]) ? number_format($c[1]) : $c[1] ?></h3><p><?= $c[0] ?></p></div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

    <!-- QUICK ACTIONS -->
    <div class="section-card">
      <h2><i class="fas fa-bolt me-2"></i>Quick Actions</h2>
      <div class="d-flex flex-wrap gap-2">
        <a href="../news.php" class="btn btn-outline-dark btn-sm"><i class="fas fa-newspaper me-1"></i>Full News Manager</a>
        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#annModal"><i class="fas fa-bullhorn me-1"></i>Send Announcement</button>
        <a href="../student-directory.php" class="btn btn-outline-info btn-sm"><i class="fas fa-address-book me-1"></i>Student Directory</a>
        <a href="../dashboards/staff_transcript_generation.php" class="btn btn-outline-success btn-sm"><i class="fas fa-file-alt me-1"></i>Transcript Generation</a>
        <a href="../dashboards/staff_receipt_printing.php" class="btn btn-outline-info btn-sm"><i class="fas fa-receipt me-1"></i>Receipt Printing</a>
        <a href="../student_communication_system.php" class="btn btn-outline-warning btn-sm"><i class="fas fa-comments me-1"></i>Communication</a>
      </div>
    </div>

    <!-- TWO COLUMN LAYOUT -->
    <div class="row g-3">
      <div class="col-md-6">
        <!-- NEWS MANAGEMENT -->
        <div class="section-card">
          <?php renderNewsWidget($staff_conn, $website_conn, $user_id, $user_name, $user_role, 5); ?>
        </div>
      </div>
      <div class="col-md-6">
        <!-- RECENT ACTIVITY -->
        <div class="section-card">
          <h2><i class="fas fa-history me-2"></i>Recent Activity</h2>
          <?php if(empty($recent_activities)): ?>
          <p class="text-muted small">No recent activities.</p>
          <?php else: ?>
          <ul class="list-unstyled mb-0" style="max-height:320px;overflow-y:auto">
            <?php foreach($recent_activities as $act): ?>
            <li class="border-bottom py-2 small d-flex gap-2">
              <i class="fas fa-circle text-primary mt-1" style="font-size:6px"></i>
              <div>
                <div><?= htmlspecialchars($act['activity'] ?? 'Activity') ?></div>
                <small class="text-muted"><?= $act['created_at'] ? date('d M Y H:i',strtotime($act['created_at'])) : '' ?></small>
              </div>
            </li>
            <?php endforeach; ?>
          </ul>
          <?php endif; ?>
        </div>

        <!-- DOCUMENT TRACKING (placeholder with real table data) -->
        <div class="section-card">
          <h2><i class="fas fa-file-alt me-2"></i>Document Overview</h2>
          <div class="row g-2 text-center">
            <div class="col-6">
              <div class="border rounded p-3">
                <div class="fw-bold h4 mb-0 text-primary"><?= number_format($pending_docs) ?></div>
                <small class="text-muted">Processed Documents</small>
              </div>
            </div>
            <div class="col-6">
              <div class="border rounded p-3">
                <div class="fw-bold h4 mb-0 text-success"><?= number_format($total_staff) ?></div>
                <small class="text-muted">Staff Members</small>
              </div>
            </div>
          </div>
          <div class="mt-3 d-flex flex-wrap gap-2">
            <a href="../dashboards/document_management.php" class="btn btn-sm btn-outline-primary"><i class="fas fa-folder-open me-1"></i>Document Manager</a>
            <a href="../store_request.php" class="btn btn-sm btn-outline-dark"><i class="fas fa-shopping-cart me-1"></i>Store Request</a>
          </div>
        </div>
      </div>
    </div>

    <!-- STUDENT RECORDS BY SET -->
    <div class="section-card">
      <?php
      require_once __DIR__ . '/../includes/student_set_viewer.php';
      renderStudentSetViewer($students_conn, [
          'title'       => 'Student Records – Secretary View',
          'icon'        => 'fa-users-gear',
          'super_admin' => false,
          'show_all'    => true,
      ]);
      ?>
    </div>
  </div>
</div>

<!-- SEND ANNOUNCEMENT MODAL -->
<div class="modal fade" id="annModal" tabindex="-1">
  <div class="modal-dialog">
    <form method="POST" class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title"><i class="fas fa-bullhorn me-2"></i>Send Announcement</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3"><label class="form-label fw-semibold">Title *</label><input type="text" name="sec_ann_title" class="form-control" required></div>
        <div class="mb-3"><label class="form-label fw-semibold">Message *</label><textarea name="sec_ann_body" class="form-control" rows="4" required></textarea></div>
        <div class="row g-3">
          <div class="col-6">
            <label class="form-label fw-semibold">Target</label>
            <select name="sec_ann_target" class="form-select">
              <option value="All">All</option><option value="Students">Students</option><option value="Staff">Staff</option>
            </select>
          </div>
          <div class="col-6">
            <label class="form-label fw-semibold">Priority</label>
            <select name="sec_ann_priority" class="form-select">
              <option value="Normal">Normal</option><option value="High">High</option><option value="Urgent">Urgent</option>
            </select>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane me-1"></i>Publish</button>
      </div>
    </form>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
</body>
</html>
