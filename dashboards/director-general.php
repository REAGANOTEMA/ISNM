<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
require_once __DIR__ . '/../includes/institution_stats.php';
require_once __DIR__ . '/../includes/student_profile_component.php';
require_once __DIR__ . '/../views/student_data_loader.php';

// DG has full access - no role restriction
$ctx          = bootstrapStaffDashboard([]);
$auth_service = $ctx['auth'];
$conn         = $ctx['staff'];
$studentsConn = $ctx['students'];
$user         = $ctx['user'];

$user_id   = (int)($user['id'] ?? 0);
$user_role = $user['role'] ?? '';
$user_name = $user['full_name'] ?? 'Doris Joy';

$overview            = getInstitutionOverviewStats();
$total_students      = $overview['total_students'];
$total_staff         = $overview['total_staff'];
$total_applications  = $overview['website_applications'];
$pending_apps        = $overview['pending_applications'];
$student_data_files  = $overview['data_files'];

// Load Excel file summary
$loader = new StudentDataLoader();
$excel_files_summary = $loader->getExcelFileSummary();

// Financial quick stats from students DB
$today_collection = 0; $outstanding = 0;
if ($studentsConn) {
    $r = $studentsConn->query("SELECT COALESCE(SUM(amount_received),0) v FROM payments WHERE DATE(payment_date)=CURDATE() AND status IN('Completed','verified','approved')");
    if ($r) $today_collection = $r->fetch_assoc()['v'] ?? 0;
    $r2 = $studentsConn->query("SELECT COALESCE(SUM(balance),0) v FROM student_invoices WHERE status IN('Pending','partial','Overdue','Partially Paid')");
    if ($r2) $outstanding = $r2->fetch_assoc()['v'] ?? 0;
}

// Staff list
$staff_list = [];
if ($conn) {
    $sr = $conn->query("SELECT s.id,s.staff_id,s.full_name,s.email,s.position,s.department,s.status,s.last_login,sr.role_name
        FROM staff s LEFT JOIN staff_roles sr ON s.role_id=sr.id ORDER BY s.full_name LIMIT 20");
    if ($sr) while ($row = $sr->fetch_assoc()) $staff_list[] = $row;
}

// Recent activities
$recent_activities = [];
if ($conn) {
    $ar = $conn->query("SELECT activity_type,activity_description,created_at FROM staff_activity_log ORDER BY created_at DESC LIMIT 8");
    if ($ar) while ($r = $ar->fetch_assoc()) $recent_activities[] = $r;
}

// All departments
$dept_list = [];
if ($conn) {
    $dr = $conn->query("SELECT department_name,department_code,department_level FROM staff_departments ORDER BY department_level,department_name");
    if ($dr) while ($r = $dr->fetch_assoc()) $dept_list[] = $r;
}

// Recent students from loader
$recent_students = [];
try { $recent_students = array_slice($loader->loadAllStudents(), 0, 6); } catch (Exception $e) {}

// POST: send announcement
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ann_title'])) {
    $title    = $conn ? $conn->real_escape_string(trim($_POST['ann_title'] ?? '')) : '';
    $body     = $conn ? $conn->real_escape_string(trim($_POST['ann_body'] ?? '')) : '';
    $target   = $conn ? $conn->real_escape_string($_POST['ann_target'] ?? 'All') : 'All';
    $priority = $conn ? $conn->real_escape_string($_POST['ann_priority'] ?? 'Normal') : 'Normal';
    if ($title && $body && $studentsConn) {
        $studentsConn->query("INSERT INTO announcements (title,body,target_audience,priority,posted_by,is_active,created_at) VALUES ('$title','$body','$target','$priority',$user_id,1,NOW())");
        $_SESSION['success'] = "Announcement published to all $target.";
    }
    header('Location: director-general.php'); exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include_once __DIR__ . '/../includes/_favicon.php'; ?>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Director General – Doris Joy – ISNM</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
<style>
body{font-family:'Segoe UI',sans-serif;background:#f0f2f5;margin:0}
.page-content{margin-left:280px;flex:1;min-height:100vh}
@media(max-width:768px){.page-content{margin-left:0}}
.top-bar{background:#fff;padding:14px 22px;display:flex;justify-content:space-between;align-items:center;box-shadow:0 2px 8px rgba(0,0,0,.07);position:sticky;top:0;z-index:100}
.content-area{padding:22px}
.stat-card{background:#fff;border-radius:14px;padding:20px;display:flex;align-items:center;gap:14px;box-shadow:0 2px 12px rgba(0,0,0,.07);transition:transform .25s}
.stat-card:hover{transform:translateY(-4px)}
        .si{width:50px;height:50px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:1.2rem;color:#fff;flex-shrink:0}
        .si-blue  {background:linear-gradient(135deg,#1a237e,#3949ab)}
        .si-green {background:linear-gradient(135deg,#2e7d32,#43a047)}
        .si-cyan  {background:linear-gradient(135deg,#0277bd,#039be5)}
        .si-orange{background:linear-gradient(135deg,#e65100,#fb8c00)}
        .si-red   {background:linear-gradient(135deg,#b71c1c,#ef5350)}
        .si-purple{background:linear-gradient(135deg,#4a148c,#8e24aa)}
        .stat-content h3{font-size:1.6rem;font-weight:700;margin:0;line-height:1}
        .stat-content p{font-size:.77rem;color:#666;margin:2px 0 0}
        .section-card{background:#fff;border-radius:14px;padding:20px;margin-bottom:22px;box-shadow:0 2px 12px rgba(0,0,0,.07)}
        .section-card h2{font-size:1rem;font-weight:700;margin-bottom:14px;padding-bottom:10px;border-bottom:2px solid #f0f2f5}

        /* Custom button colors */
        .btn-outline-purple {
            color: #7e57c2;
            border-color: #7e57c2;
        }
        .btn-outline-purple:hover {
            background-color: #7e57c2;
            color: white;
            border-color: #7e57c2;
        }
        .btn-outline-red {
            color: #ef5350;
            border-color: #ef5350;
        }
        .btn-outline-red:hover {
            background-color: #ef5350;
            color: white;
            border-color: #ef5350;
        }
</style>
</head>
<body>

<?php include_once '../includes/sidebar.php'; ?>

<div class="page-content">
  <div class="top-bar">
    <div>
      <strong><i class="fas fa-crown me-2 text-warning"></i>Director General – Doris Joy</strong>
      <div class="text-muted small">Full Institution Oversight | Iganga School of Nursing &amp; Midwifery</div>
    </div>
    <div class="d-flex align-items-center gap-3">
      <span class="text-muted small d-none d-md-block"><?= date('D, d M Y') ?></span>
      <a href="../logout.php" class="btn btn-sm btn-outline-danger"><i class="fas fa-sign-out-alt me-1"></i>Logout</a>
    </div>
  </div>

  <div class="content-area">
    <?php if(!empty($_SESSION['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show py-2"><?= htmlspecialchars($_SESSION['success']) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php unset($_SESSION['success']); endif; ?>

    <!-- DB STATUS -->
    <div class="alert alert-info d-flex align-items-center gap-2 mb-3" style="font-size:.82rem">
      <i class="fas fa-database"></i>
      <span>
        <strong>staffs_db:</strong> <?= $total_staff ?> staff &nbsp;|&nbsp;
        <strong>students_db:</strong> <?= (int)$overview['total_students_db'] ?> records &nbsp;|&nbsp;
        <strong>students_data/:</strong> <?= $student_data_files ?> Excel file(s), <?= (int)$overview['total_students_files'] ?> profiles
      </span>
    </div>

    <!-- STATS -->
    <div class="row g-3 mb-4">
      <?php $cards = [
        ['Total Students',      $total_students,               'si-blue',  'user-graduate'],
        ['Total Staff',         $total_staff,                  'si-green', 'users'],
        ['Today Collection',    'UGX '.number_format($today_collection), 'si-cyan', 'money-bill-wave'],
        ['Outstanding Fees',    'UGX '.number_format($outstanding),      'si-red',    'exclamation-triangle'],
        ['Applications',        $total_applications,           'si-orange','file-alt'],
        ['Pending Review',      $pending_apps,                 'si-purple','hourglass-half'],
      ];
      foreach($cards as $c): ?>
      <div class="col-6 col-md-2">
        <div class="stat-card">
          <div class="si <?= $c[2] ?>"><i class="fas fa-<?= $c[3] ?>"></i></div>
          <div class="stat-content"><h3><?= is_numeric($c[1]) ? number_format($c[1]) : $c[1] ?></h3><p><?= $c[0] ?></p></div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

    <!-- STUDENT SEARCH -->
    <div class="section-card">
      <h2><i class="fas fa-search me-2"></i>Student Search</h2>
      <?php include_once __DIR__ . '/../views/student_search_component.php'; ?>
    </div>

    <!-- QUICK ACTIONS -->
    <div class="section-card">
      <h2><i class="fas fa-bolt me-2"></i>Quick Actions – Full Control</h2>
      <div class="d-flex flex-wrap gap-2">
        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#annModal"><i class="fas fa-bullhorn me-1"></i>Send Announcement</button>
        <a href="../dashboards/ceo.php" class="btn btn-outline-warning btn-sm"><i class="fas fa-user-tie me-1"></i>CEO</a>
        <a href="../dashboards/director-academics.php" class="btn btn-outline-primary btn-sm"><i class="fas fa-graduation-cap me-1"></i>Director Academics</a>
        <a href="../dashboards/director-finance.php" class="btn btn-outline-success btn-sm"><i class="fas fa-coins me-1"></i>Director Finance</a>
        <a href="../dashboards/director-admissions.php" class="btn btn-outline-info btn-sm"><i class="fas fa-file-contract me-1"></i>Director Admissions</a>
        <a href="../dashboards/director-ict.php" class="btn btn-outline-secondary btn-sm"><i class="fas fa-laptop-code me-1"></i>Director ICT</a>
        <a href="../dashboards/school-principal.php" class="btn btn-outline-primary btn-sm"><i class="fas fa-chalkboard-teacher me-1"></i>Principal</a>
        <a href="../dashboards/deputy-principal.php" class="btn btn-outline-primary btn-sm"><i class="fas fa-user-check me-1"></i>Deputy Principal</a>
        <a href="../dashboards/academic-registrar.php" class="btn btn-outline-primary btn-sm"><i class="fas fa-file-alt me-1"></i>Academic Registrar</a>
        <a href="../dashboards/hr-manager.php" class="btn btn-outline-danger btn-sm"><i class="fas fa-users me-1"></i>HR Manager</a>
        <a href="../dashboards/school-secretary.php" class="btn btn-outline-info btn-sm"><i class="fas fa-envelope me-1"></i>School Secretary</a>
        <a href="../dashboards/school-librarian.php" class="btn btn-outline-info btn-sm"><i class="fas fa-book me-1"></i>Librarian</a>
        <a href="../dashboards/head-nursing.php" class="btn btn-outline-success btn-sm"><i class="fas fa-heartbeat me-1"></i>Head Nursing</a>
        <a href="../dashboards/head-midwifery.php" class="btn btn-outline-success btn-sm"><i class="fas fa-user-md me-1"></i>Head Midwifery</a>
        <a href="../dashboards/senior-lecturers.php" class="btn btn-outline-success btn-sm"><i class="fas fa-user-graduate me-1"></i>Senior Lecturers</a>
        <a href="../dashboards/lecturers.php" class="btn btn-outline-success btn-sm"><i class="fas fa-chalkboard me-1"></i>Lecturers</a>
        <a href="../dashboards/matrons.php" class="btn btn-outline-purple btn-sm"><i class="fas fa-hospital me-1"></i>Matrons</a>
        <a href="../dashboards/wardens.php" class="btn btn-outline-purple btn-sm"><i class="fas fa-building-user me-1"></i>Wardens</a>
        <a href="../dashboards/sickbay.php" class="btn btn-outline-red btn-sm"><i class="fas fa-hospital-user me-1"></i>Sickbay</a>
        <a href="../dashboards/drivers.php" class="btn btn-outline-secondary btn-sm"><i class="fas fa-car me-1"></i>Drivers</a>
        <a href="../dashboards/security.php" class="btn btn-outline-secondary btn-sm"><i class="fas fa-shield-halved me-1"></i>Security</a>
        <a href="../dashboards/storekeeper.php" class="btn btn-outline-secondary btn-sm"><i class="fas fa-boxes-stacked me-1"></i>Storekeeper</a>
        <a href="../dashboards/guild-president.php" class="btn btn-outline-secondary btn-sm"><i class="fas fa-people-group me-1"></i>Guild President</a>
        <a href="../bursar_dashboard.php" class="btn btn-outline-success btn-sm"><i class="fas fa-money-bill me-1"></i>Bursar Dashboard</a>
        <a href="../bursar_reports.php" class="btn btn-outline-secondary btn-sm"><i class="fas fa-chart-bar me-1"></i>Financial Reports</a>
        <a href="../import_students_excel.php" class="btn btn-outline-info btn-sm"><i class="fas fa-file-excel me-1"></i>Import Students</a>
      </div>
    </div>

    <!-- STUDENT DATA FILES SUMMARY -->
    <div class="section-card">
      <h2><i class="fas fa-file-excel me-2"></i>Student Data Excel Files</h2>
      <div class="table-responsive">
        <table class="table table-sm table-hover">
          <thead class="table-light">
            <tr>
              <th>#</th>
              <th>File Name</th>
              <th class="text-end">Student Count</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach($excel_files_summary as $i => $file): ?>
            <tr>
              <td><?= $i + 1 ?></td>
              <td><code><?= htmlspecialchars($file['name']) ?></code></td>
              <td class="text-end"><span class="badge bg-primary"><?= $file['students'] ?></span></td>
            </tr>
            <?php endforeach; ?>
            <?php if(empty($excel_files_summary)): ?>
            <tr><td colspan="3" class="text-muted text-center">No Excel files found in students_data/ directory</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- ALL STAFF -->
    <div class="section-card">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="mb-0"><i class="fas fa-id-badge me-2"></i>All Staff Members (<?= count($staff_list) ?>+)</h2>
        <a href="../hr_dashboard.php" class="btn btn-sm btn-outline-primary">View HR Dashboard</a>
      </div>
      <div class="table-responsive">
        <table class="table table-sm table-hover align-middle">
          <thead class="table-light"><tr><th>Staff ID</th><th>Full Name</th><th>Role</th><th>Department</th><th>Email</th><th>Status</th><th>Last Login</th></tr></thead>
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
            <td><span class="badge <?= $bc ?>"><?= htmlspecialchars($s['status']) ?></span></td>
            <td><?= $s['last_login'] ? date('d M Y H:i',strtotime($s['last_login'])) : '<span class="text-muted">Never</span>' ?></td>
          </tr>
          <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- RECENT STUDENTS FROM EXCEL -->
    <?php if(!empty($recent_students)): ?>
    <div class="section-card">
      <h2><i class="fas fa-user-graduate me-2"></i>Recently Loaded Student Profiles</h2>
      <div class="row g-2">
      <?php foreach($recent_students as $st): ?>
        <div class="col-md-4 col-lg-2">
          <div class="border rounded p-2 text-center small">
            <div class="fw-bold"><?= htmlspecialchars($st['full_name'] ?? ($st['first_name']??'').' '.($st['surname']??'')) ?></div>
            <div class="text-muted"><?= htmlspecialchars($st['index_number'] ?? $st['student_number'] ?? '') ?></div>
            <div><?= htmlspecialchars($st['program'] ?? $st['course'] ?? '') ?></div>
          </div>
        </div>
      <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

    <!-- DEPARTMENTS -->
    <?php if(!empty($dept_list)): ?>
    <div class="section-card">
      <h2><i class="fas fa-building me-2"></i>Departments</h2>
      <div class="row g-2">
      <?php foreach($dept_list as $d): ?>
        <div class="col-md-3 col-6">
          <div class="border rounded p-2">
            <div class="fw-bold small"><?= htmlspecialchars($d['department_name']) ?></div>
            <small class="text-muted"><?= htmlspecialchars($d['department_code'] ?? '') ?> | <?= htmlspecialchars($d['department_level'] ?? '') ?></small>
          </div>
        </div>
      <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

    <!-- RECENT ACTIVITIES -->
    <div class="section-card">
      <h2><i class="fas fa-history me-2"></i>Recent System Activities</h2>
      <?php if(empty($recent_activities)): ?>
      <p class="text-muted small">No recent activities recorded.</p>
      <?php else: ?>
      <ul class="list-unstyled mb-0">
      <?php foreach($recent_activities as $act): ?>
        <li class="border-bottom py-2 d-flex gap-3 align-items-start">
          <span class="badge bg-primary mt-1"><?= htmlspecialchars($act['activity_type']) ?></span>
          <div>
            <div class="small"><?= htmlspecialchars($act['activity_description'] ?? '') ?></div>
            <small class="text-muted"><?= $act['created_at'] ? date('d M Y H:i',strtotime($act['created_at'])) : '' ?></small>
          </div>
        </li>
      <?php endforeach; ?>
      </ul>
      <?php endif; ?>
    </div>

  </div><!-- /content-area -->
</div><!-- /page-content -->

<!-- SEND ANNOUNCEMENT MODAL -->
<div class="modal fade" id="annModal" tabindex="-1">
  <div class="modal-dialog">
    <form method="POST" class="modal-content">
      <div class="modal-header bg-warning">
        <h5 class="modal-title"><i class="fas fa-bullhorn me-2"></i>Send Announcement – Doris Joy</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3"><label class="form-label fw-semibold">Title *</label><input type="text" name="ann_title" class="form-control" required placeholder="Announcement title"></div>
        <div class="mb-3"><label class="form-label fw-semibold">Message *</label><textarea name="ann_body" class="form-control" rows="4" required placeholder="Write your announcement here…"></textarea></div>
        <div class="row g-3">
          <div class="col-6">
            <label class="form-label fw-semibold">Target Audience</label>
            <select name="ann_target" class="form-select">
              <option value="All">All</option><option value="Nursing">Nursing</option>
              <option value="Midwifery">Midwifery</option><option value="Staff">Staff</option>
            </select>
          </div>
          <div class="col-6">
            <label class="form-label fw-semibold">Priority</label>
            <select name="ann_priority" class="form-select">
              <option value="Normal">Normal</option><option value="High">High</option><option value="Urgent">Urgent</option>
            </select>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-warning"><i class="fas fa-paper-plane me-1"></i>Publish Announcement</button>
      </div>
    </form>
  </div>
</div>

<?php echo displayStudentProfileModal(''); ?>
<?php echo getStudentProfileStyles(); ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
function viewFullProfile(id){ showStudentProfileModal(id); }
function editStudent(id){ window.location.href='../student_accounts_management.php?action=edit&student_id='+id; }
function viewAcademic(id){ window.location.href='../academic_records_management.php?student_id='+id; }
function viewFees(id){ window.location.href='../bursar_student_fees.php?id='+id; }
function sendMessage(id){ alert('Messaging module for student ID: '+id); }
function printProfile(){ window.print(); }
</script>
<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
</body>
</html>
