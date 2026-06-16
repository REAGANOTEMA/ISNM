<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
require_once __DIR__ . '/../includes/news_management_widget.php';

$ctx          = bootstrapStaffDashboard(['school principal', 'principal']);
$auth_service = $ctx['auth'];
$user         = $ctx['user'];
$staff_conn   = $ctx['staff'];
$students_conn = $ctx['students'];
$website_conn  = $ctx['website'];

$user_id   = (int)($_SESSION['user_id'] ?? 0);
$user_role = $_SESSION['role'] ?? '';
$user_name = $_SESSION['full_name'] ?? 'Principal';

// Safe stats using helper
$stats            = getDashboardStats($staff_conn, $user_id, $user_role);
$total_students   = $stats['total_students'];
$total_staff      = $stats['total_staff'];
$active_programs  = 0;
if ($students_conn) {
    try {
        $result = $students_conn->query("SELECT COUNT(DISTINCT program) as count FROM students WHERE status='Active'");
        if ($result) $active_programs = (int)$result->fetch_assoc()['count'];
    } catch (Exception $e) {}
}
if ($active_programs < 1 && $staff_conn) {
    try {
        $result = $staff_conn->query("SELECT COUNT(*) as count FROM academic_programs WHERE status='Active'");
        if ($result) $active_programs = (int)$result->fetch_assoc()['count'];
    } catch (Exception $e) {}
}
$total_applications = $stats['pending_applications'];

// Academic stats from students DB
$avg_gpa            = 0;
$pass_percentage    = 0;
$graduation_candidates = 0;

if ($students_conn) {
    $r = $students_conn->query("SELECT AVG(gpa) as v FROM student_academic_profiles WHERE academic_status='Good Standing'");
    if ($r) $avg_gpa = (float)($r->fetch_assoc()['v'] ?? 0);

    $r2 = $students_conn->query("SELECT COUNT(*) as p, (SELECT COUNT(*) FROM examination_records WHERE grade IS NOT NULL) as t FROM examination_records WHERE grade IN('A','B','C','D')");
    if ($r2) { $row2=$r2->fetch_assoc(); $t=(int)($row2['t']??0); $p=(int)($row2['p']??0); if($t>0) $pass_percentage=round($p/$t*100,1); }

    $r3 = $students_conn->query("SELECT COUNT(*) as v FROM students WHERE status='Active'");
    if ($r3) $graduation_candidates = (int)($r3->fetch_assoc()['v'] ?? 0);
}

// Pending grade approvals
$pending_approvals = 0;
if ($staff_conn) {
    $r4 = $staff_conn->query("SHOW TABLES LIKE 'grading_approval_workflow'");
    if ($r4 && $r4->num_rows > 0) {
        $r5 = $staff_conn->query("SELECT COUNT(*) as v FROM grading_approval_workflow WHERE current_stage='Principal Final Approval'");
        if ($r5) $pending_approvals = (int)($r5->fetch_assoc()['v'] ?? 0);
    }
}

// Recent activities
$recent_activities = [];
if ($staff_conn) {
    $ra = $staff_conn->query("SELECT activity_description as activity, created_at FROM staff_activity_log ORDER BY created_at DESC LIMIT 10");
    if ($ra) $recent_activities = $ra->fetch_all(MYSQLI_ASSOC);
}

// Teaching / support / admin counts from staff DB
$teaching_count = 0; $support_count = 0; $admin_count = 0;
if ($staff_conn) {
    $tc = $staff_conn->query("SELECT COUNT(*) as v FROM staff s JOIN staff_roles sr ON s.role_id=sr.id WHERE sr.role_name IN('Senior Lecturers','Lecturers') AND s.status='Active'");
    if ($tc) $teaching_count = (int)($tc->fetch_assoc()['v'] ?? 0);
    $sc = $staff_conn->query("SELECT COUNT(*) as v FROM staff s JOIN staff_roles sr ON s.role_id=sr.id WHERE sr.role_name IN('Matrons','Sickbay','Drivers','Security','School Secretary','School Librarian') AND s.status='Active'");
    if ($sc) $support_count = (int)($sc->fetch_assoc()['v'] ?? 0);
    $ac = $staff_conn->query("SELECT COUNT(*) as v FROM staff s JOIN staff_roles sr ON s.role_id=sr.id WHERE sr.role_name IN('Director Academics','Director ICT','Director Finance','Academic Registrar','HR Manager') AND s.status='Active'");
    if ($ac) $admin_count = (int)($ac->fetch_assoc()['v'] ?? 0);
}

// Enrolled per program
$nursing_count = 0; $midwifery_count = 0;
if ($students_conn) {
    $nc = $students_conn->query("SELECT COUNT(*) as v FROM students WHERE program LIKE '%Nursing%' AND status='Active'");
    if ($nc) $nursing_count = (int)($nc->fetch_assoc()['v'] ?? 0);
    $mc = $students_conn->query("SELECT COUNT(*) as v FROM students WHERE program LIKE '%Midwifery%' AND status='Active'");
    if ($mc) $midwifery_count = (int)($mc->fetch_assoc()['v'] ?? 0);
}

// Students list
$students_list = [];
if ($students_conn) {
    $sl = $students_conn->query("SELECT s.student_number, CONCAT(s.first_name,' ',s.surname) as full_name, s.program, s.current_year, sap.gpa, sap.academic_status FROM students s LEFT JOIN student_academic_profiles sap ON s.id=sap.student_id WHERE s.status='Active' ORDER BY sap.gpa DESC LIMIT 10");
    if ($sl) $students_list = $sl->fetch_all(MYSQLI_ASSOC);
}

// POST handlers
if ($_SERVER['REQUEST_METHOD']==='POST' && !empty($_POST['action'])) {
    switch ($_POST['action']) {
        case 'approve_graduation':
            if ($students_conn && !empty($_POST['student_ids'])) {
                foreach ((array)$_POST['student_ids'] as $sid) {
                    $stmt = $students_conn->prepare("UPDATE students SET status='Graduated' WHERE id=?");
                    if ($stmt) { $stmt->bind_param('i',(int)$sid); $stmt->execute(); $stmt->close(); }
                }
                $_SESSION['success'] = 'Graduation approved for '.count($_POST['student_ids']).' students.';
            }
            header('Location: school-principal.php'); exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include_once __DIR__ . '/../includes/_favicon.php'; ?>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
<title>School Principal Dashboard – ISNM</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="dashboard-style.css">
<link rel="stylesheet" href="dashboard-mobile.css">
<style>
body{font-family:'Segoe UI',sans-serif;background:#f0f2f5}
.page-wrap{margin-left:280px;min-height:100vh}
@media(max-width:768px){.page-wrap{margin-left:0}}
.top-bar{background:#fff;padding:14px 22px;display:flex;justify-content:space-between;align-items:center;box-shadow:0 2px 8px rgba(0,0,0,.07);position:sticky;top:0;z-index:100}
.content{padding:22px}
.stat-card{background:linear-gradient(to bottom,#ffe082 0%,#ffe082 5px,#fef9e7 5px,#fef9e7 100%);border-radius:12px;padding:18px;display:flex;align-items:center;gap:12px}
.si{width:48px;height:48px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:1.15rem;color:#fff;flex-shrink:0}
.si-blue{background:linear-gradient(135deg,#1a237e,#3949ab)}
.si-green{background:linear-gradient(135deg,#2e7d32,#43a047)}
.si-cyan{background:linear-gradient(135deg,#0277bd,#039be5)}
.si-orange{background:linear-gradient(135deg,#e65100,#fb8c00)}
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
      <strong><i class="fas fa-school me-2 text-primary"></i>School Principal</strong>
      <div class="text-muted small">Academic Leadership – ISNM | <?= date('D, d M Y') ?></div>
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
        ['Total Students',$total_students,'si-blue','user-graduate'],
        ['Total Staff',$total_staff,'si-green','users'],
        ['Active Programs',$active_programs,'si-cyan','book'],
        ['Pending Applications',$total_applications,'si-orange','hourglass-half'],
      ]; foreach($cards as $c): ?>
      <div class="col-6 col-md-3">
        <div class="stat-card">
          <div class="si <?= $c[2] ?>"><i class="fas fa-<?= $c[3] ?>"></i></div>
          <div class="stat-content"><h3><?= number_format($c[1]) ?></h3><p><?= $c[0] ?></p></div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

    <!-- Academic Performance -->
    <div class="card-section">
      <h2><i class="fas fa-chart-line me-2"></i>Academic Performance</h2>
      <div class="row g-3">
        <div class="col-md-3 col-6 text-center"><h4 class="fw-bold text-primary"><?= number_format($avg_gpa,2) ?></h4><p class="text-muted small">Average GPA</p></div>
        <div class="col-md-3 col-6 text-center"><h4 class="fw-bold text-success"><?= $pass_percentage ?>%</h4><p class="text-muted small">Pass Rate</p></div>
        <div class="col-md-3 col-6 text-center"><h4 class="fw-bold text-info"><?= $pending_approvals ?></h4><p class="text-muted small">Pending Grade Approvals</p></div>
        <div class="col-md-3 col-6 text-center"><h4 class="fw-bold text-warning"><?= $graduation_candidates ?></h4><p class="text-muted small">Active Students</p></div>
      </div>
    </div>

    <!-- Staff Overview -->
    <div class="card-section">
      <h2><i class="fas fa-users me-2"></i>Staff Overview</h2>
      <div class="row g-3">
        <div class="col-md-3 col-6 text-center"><h4 class="fw-bold"><?= $teaching_count ?></h4><p class="text-muted small">Teaching Staff</p></div>
        <div class="col-md-3 col-6 text-center"><h4 class="fw-bold"><?= $support_count ?></h4><p class="text-muted small">Support Staff</p></div>
        <div class="col-md-3 col-6 text-center"><h4 class="fw-bold"><?= $admin_count ?></h4><p class="text-muted small">Administrative</p></div>
        <div class="col-md-3 col-6 text-center"><h4 class="fw-bold text-success">95%</h4><p class="text-muted small">Attendance Rate</p></div>
      </div>
    </div>

    <!-- Programs -->
    <div class="card-section">
      <h2><i class="fas fa-graduation-cap me-2"></i>Program Enrollment</h2>
      <div class="row g-3">
        <div class="col-md-6">
          <div class="border rounded p-3">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <strong>Certificate in Nursing</strong><span class="badge bg-success">Active</span>
            </div>
            <p class="mb-1">Enrolled: <strong><?= $nursing_count ?></strong> | Completion Rate: <strong>92%</strong> | Employment: <strong>87%</strong></p>
          </div>
        </div>
        <div class="col-md-6">
          <div class="border rounded p-3">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <strong>Certificate in Midwifery</strong><span class="badge bg-success">Active</span>
            </div>
            <p class="mb-1">Enrolled: <strong><?= $midwifery_count ?></strong> | Completion Rate: <strong>95%</strong> | Employment: <strong>90%</strong></p>
          </div>
        </div>
      </div>
    </div>

    <!-- Students List -->
    <div class="card-section">
      <h2><i class="fas fa-user-graduate me-2"></i>Top Students by GPA</h2>
      <div class="table-responsive">
        <table class="table table-sm table-hover align-middle">
          <thead class="table-light"><tr><th>Student No.</th><th>Name</th><th>Program</th><th>Year</th><th>GPA</th><th>Standing</th></tr></thead>
          <tbody>
          <?php if(empty($students_list)): ?>
            <tr><td colspan="6" class="text-center text-muted">No student records found.</td></tr>
          <?php else: foreach($students_list as $st):
            $standing = $st['academic_status'] ?? 'Good Standing';
            $bc = $standing==='Good Standing'?'bg-success':($standing==='Probation'?'bg-warning text-dark':'bg-danger');
          ?>
            <tr>
              <td><code><?= htmlspecialchars($st['student_number']??'') ?></code></td>
              <td><?= htmlspecialchars($st['full_name']??'') ?></td>
              <td><?= htmlspecialchars($st['program']??'') ?></td>
              <td><?= htmlspecialchars($st['current_year']??'') ?></td>
              <td><?= number_format((float)($st['gpa']??0),2) ?></td>
              <td><span class="badge <?= $bc ?>"><?= htmlspecialchars($standing) ?></span></td>
            </tr>
          <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Quick Actions -->
    <div class="card-section">
      <h2><i class="fas fa-bolt me-2"></i>Quick Actions</h2>
      <div class="d-flex flex-wrap gap-2">
        <a href="../store_request.php" class="btn btn-outline-dark btn-sm"><i class="fas fa-shopping-cart me-1"></i>Store Request</a>
        <a href="../news.php" class="btn btn-outline-dark btn-sm"><i class="fas fa-newspaper me-1"></i>Manage News</a>
        <a href="../student-directory.php" class="btn btn-outline-dark btn-sm"><i class="fas fa-address-book me-1"></i>Student Directory</a>
        <a href="../dashboards/academic-registrar.php" class="btn btn-outline-primary btn-sm"><i class="fas fa-graduation-cap me-1"></i>Academic Registrar</a>
        <a href="../dashboards/bursar.php" class="btn btn-outline-success btn-sm"><i class="fas fa-money-bill me-1"></i>Bursar</a>
        <a href="../dashboards/hr-manager.php" class="btn btn-outline-danger btn-sm"><i class="fas fa-users me-1"></i>HR Manager</a>
        <a href="../dashboards/director-academics.php" class="btn btn-outline-info btn-sm"><i class="fas fa-book me-1"></i>Director Academics</a>
        <a href="../import_students_excel.php" class="btn btn-outline-secondary btn-sm"><i class="fas fa-file-excel me-1"></i>Import Students</a>
      </div>
    </div>

    <!-- NEWS MANAGEMENT -->
    <div class="card-section">
      <?php renderNewsWidget($staff_conn, $website_conn, $user_id, $user_name, $user_role, 5); ?>
    </div>

    <!-- Recent Activities -->
    <div class="card-section">
      <h2><i class="fas fa-history me-2"></i>Recent System Activities</h2>
      <?php if(empty($recent_activities)): ?>
      <p class="text-muted small">No recent activities.</p>
      <?php else: ?>
      <ul class="list-unstyled mb-0">
        <?php foreach($recent_activities as $act): ?>
        <li class="border-bottom py-2 small">
          <strong><?= htmlspecialchars($act['activity']??'Activity') ?></strong>
          <span class="text-muted ms-2"><?= $act['created_at'] ? date('d M Y H:i',strtotime($act['created_at'])) : '' ?></span>
        </li>
        <?php endforeach; ?>
      </ul>
      <?php endif; ?>
    </div>

  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
</body>
</html>
