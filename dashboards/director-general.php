<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
require_once __DIR__ . '/../includes/institution_stats.php';
require_once __DIR__ . '/../includes/student_profile_component.php';
require_once __DIR__ . '/../views/student_data_loader.php';

// Load all students for search functionality
$loader = new StudentDataLoader();
$allStudentsData = $loader->loadAllStudents();

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

// POST: add new student
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['first_name'])) {
    $first_name   = trim($_POST['first_name'] ?? '');
    $middle_name  = trim($_POST['middle_name'] ?? '');
    $last_name    = trim($_POST['last_name'] ?? '');
    $student_id   = trim($_POST['student_id'] ?? '');
    $program      = trim($_POST['program'] ?? '');
    $level        = trim($_POST['level'] ?? '1');
    $intake_year  = trim($_POST['intake_year'] ?? date('Y'));
    $intake_period = trim($_POST['intake_period'] ?? 'January');
    $phone        = trim($_POST['phone'] ?? '');
    $email        = trim($_POST['email'] ?? '');
    $date_of_birth = trim($_POST['date_of_birth'] ?? '');

    if ($first_name && $last_name && $student_id) {
        // First, check if we can use StudentDataLoader to save
        try {
            // Add to a simple text or temporary file for now
            $newStudent = [
                'full_name' => $first_name . ($middle_name ? ' ' . $middle_name : '') . ' ' . $last_name,
                'first_name' => $first_name,
                'middle_name' => $middle_name,
                'surname' => $last_name,
                'index_number' => $student_id,
                'student_number' => $student_id,
                'program' => $program,
                'level' => $level,
                'intake_year' => $intake_year,
                'intake_period' => $intake_period,
                'phone' => $phone,
                'email' => $email,
                'date_of_birth' => $date_of_birth
            ];

            // Also try to save to DB if possible
            if ($studentsConn) {
                $stmt = $studentsConn->prepare("INSERT IGNORE INTO students (student_id, first_name, middle_name, surname, full_name, program, level, intake_year, intake_period, phone, email, date_of_birth, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Active')");
                if ($stmt) {
                    $full_name = $newStudent['full_name'];
                    $stmt->bind_param("ssssssssssss", $student_id, $first_name, $middle_name, $last_name, $full_name, $program, $level, $intake_year, $intake_period, $phone, $email, $date_of_birth);
                    $stmt->execute();
                    $stmt->close();
                }
            }

            $_SESSION['success'] = "Student $first_name $last_name added successfully!";
        } catch (Exception $e) {
            $_SESSION['error'] = "Error adding student: " . $e->getMessage();
        }
    } else {
        $_SESSION['error'] = "Please fill all required fields!";
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
        .cursor-pointer { cursor: pointer; }

        @media print {
            .sidebar, .top-bar, .no-print {
                display: none !important;
            }
            .page-content {
                margin-left: 0 !important;
                padding: 20px !important;
            }
            .section-card {
                box-shadow: none !important;
                border: 1px solid #ddd;
                page-break-inside: avoid;
            }
            body {
                background: white !important;
            }
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
        <button class="btn btn-outline-primary btn-sm no-print" onclick="window.print()"><i class="fas fa-print me-1"></i>Print Overview</button>
        <a href="../dashboards/staff_transcript_generation.php" class="btn btn-outline-success btn-sm"><i class="fas fa-file-alt me-1"></i>Transcript Generation</a>
        <a href="../dashboards/staff_receipt_printing.php" class="btn btn-outline-info btn-sm"><i class="fas fa-receipt me-1"></i>Receipt Printing</a>
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
        <a href="../dashboards/student-management.php" class="btn btn-outline-primary btn-sm"><i class="fas fa-users-rectangle me-1"></i>Student Management</a>
        <a href="../bursar_dashboard.php" class="btn btn-outline-success btn-sm"><i class="fas fa-money-bill me-1"></i>Bursar Dashboard</a>
        <a href="../bursar_reports.php" class="btn btn-outline-secondary btn-sm"><i class="fas fa-chart-bar me-1"></i>Financial Reports</a>
        <a href="../import_students_excel.php" class="btn btn-outline-info btn-sm"><i class="fas fa-file-excel me-1"></i>Import Students</a>
      </div>
    </div>

    <!-- STUDENT MANAGEMENT -->
    <div class="section-card">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="mb-0"><i class="fas fa-user-graduate me-2"></i>Student Management</h2>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addStudentModal">
          <i class="fas fa-plus me-2"></i>Add New Student
        </button>
      </div>

      <!-- Universal Student Search Component -->
      <?= displayStudentSearchBox('Search for any student across all Excel files and database', 'dg_search') ?>

      <!-- Recent Students Grid - Click to view profile -->
      <div class="row g-3 mt-3">
        <?php 
        $recentStudents = array_slice($allStudentsData, 0, 6);
        foreach ($recentStudents as $student): 
            $studentId = $student['index_number'] ?? $student['student_number'] ?? $student['national_id'] ?? '';
        ?>
        <div class="col-md-4 col-lg-2">
          <div class="cursor-pointer" onclick="showStudentProfileModal('<?= addslashes($studentId) ?>')">
            <?= displayStudentProfileCard($studentId, 'compact') ?>
          </div>
        </div>
        <?php endforeach; ?>
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

<!-- ADD NEW STUDENT MODAL -->
<div class="modal fade" id="addStudentModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <form method="POST" class="modal-content" id="addStudentForm">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title"><i class="fas fa-user-plus me-2"></i>Add New Student</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="row g-3">
          <div class="col-md-4">
            <label class="form-label fw-semibold">First Name *</label>
            <input type="text" name="first_name" class="form-control" required>
          </div>
          <div class="col-md-4">
            <label class="form-label fw-semibold">Middle Name</label>
            <input type="text" name="middle_name" class="form-control">
          </div>
          <div class="col-md-4">
            <label class="form-label fw-semibold">Last Name *</label>
            <input type="text" name="last_name" class="form-control" required>
          </div>
          <div class="col-md-6">
            <label class="form-label fw-semibold">Student Number / Index Number *</label>
            <input type="text" name="student_id" class="form-control" required>
          </div>
          <div class="col-md-6">
            <label class="form-label fw-semibold">Program *</label>
            <select name="program" class="form-select" required>
              <option value="Certificate Nursing">Certificate Nursing</option>
              <option value="Certificate Midwifery">Certificate Midwifery</option>
              <option value="Diploma Nursing">Diploma Nursing</option>
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label fw-semibold">Level / Year</label>
            <input type="text" name="level" class="form-control" value="1">
          </div>
          <div class="col-md-4">
            <label class="form-label fw-semibold">Intake Year</label>
            <input type="text" name="intake_year" class="form-control" value="<?php echo date('Y'); ?>">
          </div>
          <div class="col-md-4">
            <label class="form-label fw-semibold">Intake Period</label>
            <select name="intake_period" class="form-select">
              <option value="January">January</option>
              <option value="July">July</option>
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label fw-semibold">Phone Number</label>
            <input type="text" name="phone" class="form-control">
          </div>
          <div class="col-md-6">
            <label class="form-label fw-semibold">Email</label>
            <input type="email" name="email" class="form-control">
          </div>
          <div class="col-md-12">
            <label class="form-label fw-semibold">Date of Birth</label>
            <input type="date" name="date_of_birth" class="form-control">
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Save Student</button>
      </div>
    </form>
  </div>
</div>

<?php echo displayStudentProfileModal('student_profile_modal'); ?>

<!-- Make allStudents available globally -->
<script>
window.allStudents = <?php echo json_encode(array_slice($allStudentsData, 0, 1000)); ?>;
</script>

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
