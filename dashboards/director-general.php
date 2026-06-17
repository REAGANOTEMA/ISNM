<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
require_once __DIR__ . '/../includes/institution_stats.php';
require_once __DIR__ . '/../includes/student_profile_component.php';
require_once __DIR__ . '/../includes/student_set_viewer.php';
require_once __DIR__ . '/../includes/news_management_widget.php';
require_once __DIR__ . '/../includes/email_notifications.php';
require_once __DIR__ . '/../includes/notification_helper.php';
require_once __DIR__ . '/../views/student_data_loader.php';

// Load all students for search functionality
$loader = new StudentDataLoader();
$allStudentsData = $loader->loadAllStudents();

// DG has full access - no role restriction
$ctx          = bootstrapStaffDashboard([]);
$auth_service = $ctx['auth'];
$conn         = $ctx['staff'];
$studentsConn = $ctx['students'];
$websiteConn  = $ctx['website'];
$user         = $ctx['user'];

$user_id   = (int)($user['id'] ?? 0);
$user_role = $user['role'] ?? '';
$user_name = $user['full_name'] ?? ($_SESSION['full_name'] ?? 'Director General');

$overview            = getInstitutionOverviewStats();
$total_students      = $overview['total_students'];
$total_staff         = $overview['total_staff'];
$total_applications  = $overview['website_applications'];
$pending_apps        = $overview['pending_applications'];
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
        // Create notification for all staff
        $nid = createNotification('New Announcement: ' . $title, $body, 'director-general.php', 'announcement', 'fas fa-bullhorn');
        if ($nid) {
            notifyAllStaff($nid);
            // Send email notification using existing system
            if (function_exists('notifyDirectorGeneral')) {
                notifyDirectorGeneral("New Announcement: $title", "The DG posted a new announcement targeting $target.\n\n$body\n\nPriority: $priority");
            }
        }
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
<?php $pageTitle = 'Director General Dashboard'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include_once __DIR__ . '/../includes/dashboard_head.php'; ?>
</head>
<body>

<?php include_once '../includes/sidebar.php'; ?>

<div class="page-content">
  <div class="top-bar">
    <div>
      <strong><i class="fas fa-crown me-2 text-warning"></i>Director General – <?= htmlspecialchars($user_name) ?></strong>
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
      <div class="col-6 col-md-4 col-lg-2">
        <div class="stat-card">
          <div class="si <?= $c[2] ?>"><i class="fas fa-<?= $c[3] ?>"></i></div>
          <div class="stat-content"><h3><?= is_numeric($c[1]) ? number_format($c[1]) : $c[1] ?></h3><p><?= $c[0] ?></p></div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

    <!-- PENDING SUBMISSIONS INBOX -->
    <?php
    $pendingContacts = 0; $pendingVolunteers = 0; $pendingDonations = 0; $pendingApplications = 0;
    $recentSubmissions = [];
    if ($websiteConn) {
        $r = $websiteConn->query("SELECT COUNT(*) c FROM contact_submissions WHERE status='unread'");
        if ($r) $pendingContacts = (int)$r->fetch_assoc()['c'];
        $r = $websiteConn->query("SELECT COUNT(*) c FROM volunteer_applications WHERE status='pending'");
        if ($r) $pendingVolunteers = (int)$r->fetch_assoc()['c'];
        $r = $websiteConn->query("SELECT COUNT(*) c FROM donations WHERE status='pending'");
        if ($r) $pendingDonations = (int)$r->fetch_assoc()['c'];
        $r = $websiteConn->query("SELECT COUNT(*) c FROM student_applications WHERE status='Pending'");
        if ($r) $pendingApplications = (int)$r->fetch_assoc()['c'];

        // Fetch latest 5 submissions across all types
        $union = $websiteConn->query("
            (SELECT 'contact' as type, id, CONCAT(first_name,' ',last_name) as name, subject as title, created_at FROM contact_submissions WHERE status='unread')
            UNION ALL
            (SELECT 'volunteer', id, CONCAT(first_name,' ',last_name), CONCAT(profession,' - ',opportunity), created_at FROM volunteer_applications WHERE status='pending')
            UNION ALL
            (SELECT 'donation', id, donor_name, CONCAT('UGX ',FORMAT(amount,0)), created_at FROM donations WHERE status='pending')
            UNION ALL
            (SELECT 'application', id, CONCAT(first_name,' ',surname), program_applied, submitted_at FROM student_applications WHERE status='Pending')
            ORDER BY created_at DESC LIMIT 8
        ");
        if ($union) while ($row = $union->fetch_assoc()) $recentSubmissions[] = $row;
    }
    $totalPending = $pendingContacts + $pendingVolunteers + $pendingDonations + $pendingApplications;
    ?>
    <?php if ($totalPending > 0): ?>
    <div class="section-card mb-4" style="border-left:4px solid #dc2626">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <h2 class="mb-0"><i class="fas fa-bell me-2" style="color:#dc2626"></i>Pending Submissions</h2>
            <span class="badge bg-danger rounded-pill fs-6"><?= $totalPending ?> New</span>
        </div>
        <div class="row g-2 mb-3">
            <div class="col-3 col-md-3">
                <div class="p-3 rounded text-center" style="background:#fee2e2">
                    <div class="fs-3 fw-bold" style="color:#991b1b"><?= $pendingContacts ?></div>
                    <small style="color:#7f1d1d">Messages</small>
                </div>
            </div>
            <div class="col-3 col-md-3">
                <div class="p-3 rounded text-center" style="background:#fef3c7">
                    <div class="fs-3 fw-bold" style="color:#92400e"><?= $pendingVolunteers ?></div>
                    <small style="color:#78350f">Volunteers</small>
                </div>
            </div>
            <div class="col-3 col-md-3">
                <div class="p-3 rounded text-center" style="background:#dbeafe">
                    <div class="fs-3 fw-bold" style="color:#1e40af"><?= $pendingDonations ?></div>
                    <small style="color:#1e3a8a">Donations</small>
                </div>
            </div>
            <div class="col-3 col-md-3">
                <div class="p-3 rounded text-center" style="background:#dcfce7">
                    <div class="fs-3 fw-bold" style="color:#166534"><?= $pendingApplications ?></div>
                    <small style="color:#14532d">Applications</small>
                </div>
            </div>
        </div>
        <?php if (!empty($recentSubmissions)): ?>
        <div class="list-group list-group-flush">
            <?php foreach ($recentSubmissions as $sub): 
                $icons = ['contact' => 'fa-envelope','volunteer' => 'fa-hands-helping','donation' => 'fa-hand-holding-heart','application' => 'fa-file-alt'];
                $colors = ['contact' => '#dc2626','volunteer' => '#d97706','donation' => '#2563eb','application' => '#16a34a'];
                $labels = ['contact' => 'Contact','volunteer' => 'Volunteer','donation' => 'Donation','application' => 'Application'];
                $ic = $icons[$sub['type']] ?? 'fa-bell';
                $cl = $colors[$sub['type']] ?? '#6b7280';
                $lb = $labels[$sub['type']] ?? 'Submission';
            ?>
            <div class="list-group-item border-0 ps-0 d-flex align-items-center gap-3">
                <div style="width:36px;height:36px;border-radius:50%;background:<?= $cl ?>15;display:flex;align-items:center;justify-content:center;flex-shrink:0">
                    <i class="fas <?= $ic ?>" style="color:<?= $cl ?>;font-size:14px"></i>
                </div>
                <div class="flex-grow-1 min-width-0">
                    <div class="fw-semibold" style="font-size:14px"><?= htmlspecialchars($sub['name']) ?></div>
                    <div style="font-size:12px;color:#64748b"><?= htmlspecialchars($sub['title']) ?> <span class="badge bg-light text-dark ms-1"><?= $lb ?></span></div>
                </div>
                <small style="color:#94a3b8;flex-shrink:0"><?= date('d M H:i', strtotime($sub['created_at'])) ?></small>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- STUDENT SEARCH -->
    <div class="section-card">
      <h2><i class="fas fa-search me-2"></i>Student Search</h2>
      <?php include_once __DIR__ . '/../views/student_search_component.php'; ?>
    </div>

    <!-- QUICK ACTIONS -->
    <div class="section-card">
      <h2><i class="fas fa-bolt me-2"></i>Quick Actions</h2>

      <div class="mb-3">
        <div class="d-flex align-items-center gap-2 mb-2"><span class="badge bg-primary" style="font-size:11px">OPERATIONS</span><small class="text-muted">Core daily tasks</small></div>
        <div class="d-flex flex-wrap gap-2">
          <a href="../news.php" class="btn btn-outline-dark btn-sm"><i class="fas fa-newspaper me-1"></i>Manage News</a>
          <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#annModal"><i class="fas fa-bullhorn me-1"></i>Send Announcement</button>
          <a href="../dashboards/staff_transcript_generation.php" class="btn btn-outline-success btn-sm"><i class="fas fa-file-alt me-1"></i>Transcript Generation</a>
          <a href="../dashboards/staff_receipt_printing.php" class="btn btn-outline-info btn-sm"><i class="fas fa-receipt me-1"></i>Receipt Printing</a>
          <a href="../import_students_excel.php" class="btn btn-outline-info btn-sm"><i class="fas fa-file-excel me-1"></i>Import Students</a>
          <button class="btn btn-outline-primary btn-sm no-print" onclick="window.print()"><i class="fas fa-print me-1"></i>Print Overview</button>
        </div>
      </div>

      <div class="mb-3">
        <div class="d-flex align-items-center gap-2 mb-2"><span class="badge bg-warning text-dark" style="font-size:11px">EXECUTIVE</span><small class="text-muted">Leadership dashboards</small></div>
        <div class="d-flex flex-wrap gap-2">
          <a href="../dashboards/director-academics.php" class="btn btn-outline-primary btn-sm"><i class="fas fa-graduation-cap me-1"></i>Director Academics</a>
          <a href="../dashboards/director-finance.php" class="btn btn-outline-success btn-sm"><i class="fas fa-coins me-1"></i>Director Finance</a>
          <a href="../dashboards/director-admissions.php" class="btn btn-outline-info btn-sm"><i class="fas fa-file-contract me-1"></i>Director Admissions</a>
          <a href="../dashboards/director-ict.php" class="btn btn-outline-secondary btn-sm"><i class="fas fa-laptop-code me-1"></i>Director ICT</a>
        </div>
      </div>

      <div class="mb-3">
        <div class="d-flex align-items-center gap-2 mb-2"><span class="badge bg-info" style="font-size:11px">ADMINISTRATION</span><small class="text-muted">School administration</small></div>
        <div class="d-flex flex-wrap gap-2">
          <a href="../dashboards/school-principal.php" class="btn btn-outline-primary btn-sm"><i class="fas fa-chalkboard-teacher me-1"></i>Principal</a>
          <a href="../dashboards/deputy-principal.php" class="btn btn-outline-primary btn-sm"><i class="fas fa-user-check me-1"></i>Deputy Principal</a>
          <a href="../dashboards/academic-registrar.php" class="btn btn-outline-primary btn-sm"><i class="fas fa-file-alt me-1"></i>Academic Registrar</a>
          <a href="../dashboards/school-secretary.php" class="btn btn-outline-info btn-sm"><i class="fas fa-envelope me-1"></i>School Secretary</a>
          <a href="../dashboards/hr-manager.php" class="btn btn-outline-danger btn-sm"><i class="fas fa-users me-1"></i>HR Manager</a>
          <a href="../dashboards/school-bursar.php" class="btn btn-outline-success btn-sm"><i class="fas fa-money-bill me-1"></i>Bursar</a>
        </div>
      </div>

      <div class="mb-3">
        <div class="d-flex align-items-center gap-2 mb-2"><span class="badge bg-success" style="font-size:11px">ACADEMIC</span><small class="text-muted">Faculty & student services</small></div>
        <div class="d-flex flex-wrap gap-2">
          <a href="../dashboards/head-nursing.php" class="btn btn-outline-success btn-sm"><i class="fas fa-heartbeat me-1"></i>Head Nursing</a>
          <a href="../dashboards/head-midwifery.php" class="btn btn-outline-success btn-sm"><i class="fas fa-user-md me-1"></i>Head Midwifery</a>
          <a href="../dashboards/senior-lecturers.php" class="btn btn-outline-success btn-sm"><i class="fas fa-user-graduate me-1"></i>Senior Lecturers</a>
          <a href="../dashboards/lecturers.php" class="btn btn-outline-success btn-sm"><i class="fas fa-chalkboard me-1"></i>Lecturers</a>
          <a href="../dashboards/school-librarian.php" class="btn btn-outline-info btn-sm"><i class="fas fa-book me-1"></i>Librarian</a>
          <a href="../dashboards/student-management.php" class="btn btn-outline-primary btn-sm"><i class="fas fa-users-rectangle me-1"></i>Student Management</a>
        </div>
      </div>

      <div>
        <div class="d-flex align-items-center gap-2 mb-2"><span class="badge bg-secondary" style="font-size:11px">SUPPORT</span><small class="text-muted">Welfare & facilities</small></div>
        <div class="d-flex flex-wrap gap-2">
          <a href="../dashboards/matrons.php" class="btn btn-outline-purple btn-sm"><i class="fas fa-hospital me-1"></i>Matrons</a>
          <a href="../dashboards/wardens.php" class="btn btn-outline-purple btn-sm"><i class="fas fa-building-user me-1"></i>Wardens</a>
          <a href="../dashboards/sickbay.php" class="btn btn-outline-red btn-sm"><i class="fas fa-hospital-user me-1"></i>Sickbay</a>
          <a href="../dashboards/storekeeper.php" class="btn btn-outline-secondary btn-sm"><i class="fas fa-boxes-stacked me-1"></i>Storekeeper</a>
          <a href="../dashboards/drivers.php" class="btn btn-outline-secondary btn-sm"><i class="fas fa-car me-1"></i>Drivers</a>
          <a href="../dashboards/security.php" class="btn btn-outline-secondary btn-sm"><i class="fas fa-shield-halved me-1"></i>Security</a>
          <a href="../dashboards/guild-president.php" class="btn btn-outline-secondary btn-sm"><i class="fas fa-people-group me-1"></i>Guild President</a>
        </div>
      </div>
    </div>

    <!-- INSTITUTIONAL REPORTS -->
    <div class="section-card">
      <h2><i class="fas fa-chart-bar me-2"></i>Institutional Reports</h2>
      <div class="row g-3">
        <div class="col-6 col-md-3">
          <div class="report-card text-center p-3">
            <div class="report-icon mx-auto mb-2" style="width:50px;height:50px;font-size:1.2rem"><i class="fas fa-chart-pie"></i></div>
            <h6 class="fw-bold mb-1">Institutional Summary</h6>
            <p class="small text-muted mb-2">Performance summary</p>
            <a href="../dashboards/inventory-reports.php" class="btn btn-sm btn-primary w-100">Generate</a>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="report-card text-center p-3">
            <div class="report-icon mx-auto mb-2" style="width:50px;height:50px;font-size:1.2rem"><i class="fas fa-users"></i></div>
            <h6 class="fw-bold mb-1">Enrollment Statistics</h6>
            <p class="small text-muted mb-2">Trends & analysis</p>
            <a href="../dashboards/student-management.php" class="btn btn-sm btn-primary w-100">Generate</a>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="report-card text-center p-3">
            <div class="report-icon mx-auto mb-2" style="width:50px;height:50px;font-size:1.2rem"><i class="fas fa-trophy"></i></div>
            <h6 class="fw-bold mb-1">Graduation Report</h6>
            <p class="small text-muted mb-2">Completion statistics</p>
            <a href="../academic_records_management.php" class="btn btn-sm btn-primary w-100">Generate</a>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="report-card text-center p-3">
            <div class="report-icon mx-auto mb-2" style="width:50px;height:50px;font-size:1.2rem"><i class="fas fa-balance-scale"></i></div>
            <h6 class="fw-bold mb-1">Financial Summary</h6>
            <p class="small text-muted mb-2">Revenue & expenses</p>
            <a href="../dashboards/director-finance.php" class="btn btn-sm btn-primary w-100">Generate</a>
          </div>
        </div>
      </div>
    </div>

    <!-- NEWS MANAGEMENT -->
    <div class="section-card">
      <?php renderNewsWidget($conn, $websiteConn, $user_id, $user_name, $user_role, 5); ?>
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
      <?= displayStudentSearchBox('Search for any student by name or index number', 'dg_search') ?>

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
        <a href="../dashboards/hr-manager.php" class="btn btn-sm btn-outline-primary">View HR Dashboard</a>
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
            <td><?= htmlspecialchars($s['department'] ?? '-') ?></td>
            <td><small><?= htmlspecialchars($s['email']) ?></small></td>
            <td><span class="badge <?= $bc ?>"><?= htmlspecialchars($s['status']) ?></span></td>
            <td><?= $s['last_login'] ? date('d M Y H:i',strtotime($s['last_login'])) : '<span class="text-muted">Never</span>' ?></td>
          </tr>
          <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- STUDENT RECORDS BY SET – Full Institution View -->
    <div class="section-card">
      <?php renderStudentSetViewer($studentsConn, [
          'title'       => 'All Student Records – Full Institution View',
          'icon'        => 'fa-users-gear',
          'super_admin' => true,
          'show_all'    => true,
      ]); ?>
    </div>

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

    <!-- STORE REQUESTS WIDGET -->
    <?php
    $storeReqs = [];
    if ($conn) {
        $sr = $conn->query("SELECT sr.request_number, sr.urgency, sr.status, sr.created_at, s.full_name as requester FROM store_requests sr LEFT JOIN staff s ON sr.requested_by=s.id WHERE sr.status IN ('pending','forwarded') ORDER BY FIELD(sr.urgency,'urgent','high','medium','low'), sr.created_at ASC LIMIT 5");
        if ($sr) while ($row = $sr->fetch_assoc()) $storeReqs[] = $row;
    }
    ?>
    <div class="section-card">
      <h2><i class="fas fa-shopping-cart me-2 text-warning"></i>Pending Store Requests <?= count($storeReqs) ? '<span class="badge bg-danger ms-1">'.count($storeReqs).'</span>' : '' ?></h2>
      <?php if (empty($storeReqs)): ?>
        <p class="text-muted small">No pending store requests.</p>
      <?php else: foreach ($storeReqs as $sr_): ?>
        <div class="d-flex justify-content-between align-items-center border-bottom py-2">
          <div>
            <code class="fw-bold"><?= htmlspecialchars($sr_['request_number']) ?></code>
            <small class="text-muted ms-2">by <?= htmlspecialchars($sr_['requester'] ?? '') ?></small>
          </div>
          <div class="d-flex align-items-center gap-2">
            <span class="badge bg-<?= $sr_['urgency']==='urgent'?'danger':($sr_['urgency']==='high'?'warning text-dark':'info') ?>"><?= $sr_['urgency'] ?></span>
            <small class="text-muted"><?= date('d M', strtotime($sr_['created_at'])) ?></small>
          </div>
        </div>
      <?php endforeach; ?>
        <div class="text-center mt-2"><a href="../dashboards/storekeeper.php" class="btn btn-sm btn-outline-warning"><i class="fas fa-warehouse me-1"></i>Go to Store</a></div>
      <?php endif; ?>
    </div>

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
        <h5 class="modal-title"><i class="fas fa-bullhorn me-2"></i>Send Announcement</h5>
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
