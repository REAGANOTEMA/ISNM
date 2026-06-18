<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
require_once __DIR__ . '/../includes/institution_stats.php';
require_once __DIR__ . '/../includes/student_profile_component.php';
require_once __DIR__ . '/../includes/student_set_viewer.php';
require_once __DIR__ . '/../includes/news_management_widget.php';
require_once __DIR__ . '/../includes/email_notifications.php';
require_once __DIR__ . '/../includes/notification_helper.php';
require_once __DIR__ . '/../views/student_data_loader.php';
require_once __DIR__ . '/../includes/institutional_framework.php';
require_once __DIR__ . '/../includes/approval_workflow.php';
require_once __DIR__ . '/../includes/executive_overview.php';

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

// Financial quick stats from staffs DB
$today_collection = 0; $outstanding = 0;
if ($conn) {
    $r = $conn->query("SELECT COALESCE(SUM(amount_received),0) v FROM payments WHERE DATE(payment_date)=CURDATE() AND status IN('verified','approved')");
    if ($r) $today_collection = $r->fetch_assoc()['v'] ?? 0;
    $r2 = $conn->query("SELECT COALESCE(SUM(balance),0) v FROM student_invoices WHERE status IN('pending','partial','overdue')");
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

// ── Get current user's role_id for official duties ──
$user_role_id = 0;
if ($conn) {
    $ri = $conn->query("SELECT role_id FROM staff WHERE id = $user_id");
    if ($ri) { $user_role_id = (int)$ri->fetch_assoc()['role_id']; }
}

// ── Staff attendance today ──
$staffAttendanceToday = ['present' => 0, 'late' => 0, 'absent' => 0, 'on_leave' => 0, 'onLeave' => 0];
if ($conn) {
    $sa = $conn->query("SELECT status, COUNT(*) cnt FROM staff_attendance WHERE DATE(date)=CURDATE() GROUP BY status");
    if ($sa) while ($row = $sa->fetch_assoc()) {
        $k = strtolower(str_replace(' ', '_', $row['status']));
        if (isset($staffAttendanceToday[$k])) $staffAttendanceToday[$k] = (int)$row['cnt'];
    }
}

// ── Enhanced financials ──
$week_collection = 0; $month_collection = 0; $total_expenses = 0; $total_revenue = 0;
if ($conn) {
    $rw = $conn->query("SELECT COALESCE(SUM(amount_received),0) v FROM payments WHERE YEARWEEK(payment_date)=YEARWEEK(CURDATE()) AND status IN('verified','approved')");
    if ($rw) $week_collection = $rw->fetch_assoc()['v'] ?? 0;
    $rm = $conn->query("SELECT COALESCE(SUM(amount_received),0) v FROM payments WHERE MONTH(payment_date)=MONTH(CURDATE()) AND YEAR(payment_date)=YEAR(CURDATE()) AND status IN('verified','approved')");
    if ($rm) $month_collection = $rm->fetch_assoc()['v'] ?? 0;
    $re = $conn->query("SELECT COALESCE(SUM(amount),0) v FROM expenses WHERE status IN('approved','paid')");
    if ($re) $total_expenses = $re->fetch_assoc()['v'] ?? 0;
    $rr = $conn->query("SELECT COALESCE(SUM(amount_received),0) v FROM payments WHERE status IN('verified','approved')");
    if ($rr) $total_revenue = $rr->fetch_assoc()['v'] ?? 0;
}

// ── Latest 5 payments ──
$recent_payments = [];
if ($conn) {
    $rp = $conn->query("SELECT p.*, s.first_name, s.last_name, s.student_number FROM payments p LEFT JOIN students s ON p.student_id = s.id ORDER BY p.payment_date DESC LIMIT 5");
    if ($rp) while ($row = $rp->fetch_assoc()) $recent_payments[] = $row;
}

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
        try {
            $full_name = $first_name . ($middle_name ? ' ' . $middle_name : '') . ' ' . $last_name;
            $intake_date = $intake_year . '-' . ($intake_period === 'July' ? '07' : '01') . '-01';
            $newStudent = [
                'full_name' => $full_name,
                'first_name' => $first_name,
                'surname' => $last_name,
                'student_number' => $student_id,
                'program' => $program,
                'level' => $level,
                'intake_date' => $intake_date,
                'phone' => $phone,
                'email' => $email,
                'date_of_birth' => $date_of_birth
            ];

            if ($studentsConn) {
                $stmt = $studentsConn->prepare("INSERT IGNORE INTO students (student_number, first_name, surname, full_name, program, level, intake_date, phone, email, date_of_birth, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Active')");
                if ($stmt) {
                    $stmt->bind_param("ssssssssss", $student_id, $first_name, $last_name, $full_name, $program, $level, $intake_date, $phone, $email, $date_of_birth);
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
      <strong><i class="fas fa-crown me-2 text-warning"></i>Director General – Namugwanya Doris Joy</strong>
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

    <!-- ═══ MODULE SLIDER ═══ -->
    <?php require_once __DIR__ . '/../includes/dashboard_module_slider.php'; renderModuleSlider($user_role); ?>

    <!-- ═══ KPI STATS ═══ -->
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

    <!-- ═══ ANALYTICS ROW ═══ -->
    <?php
    $mn = []; $rv = []; $ex = [];
    for ($m = 5; $m >= 0; $m--) {
        $ts = strtotime("-$m months"); $mn[] = date('M Y', $ts);
        $mo = date('m', $ts); $yr = date('Y', $ts);
        $r = $conn ? $conn->query("SELECT COALESCE(SUM(amount_received),0) v FROM payments WHERE MONTH(payment_date)=$mo AND YEAR(payment_date)=$yr AND status IN('verified','approved')") : null;
        $e = $conn ? $conn->query("SELECT COALESCE(SUM(amount),0) v FROM expenses WHERE MONTH(expense_date)=$mo AND YEAR(expense_date)=$yr AND status IN('approved','paid')") : null;
        $rv[] = $r ? (float)$r->fetch_assoc()['v'] : 0; $ex[] = $e ? (float)$e->fetch_assoc()['v'] : 0;
    }
    $ml = []; $mv = [];
    if ($conn) {
        $mr = $conn->query("SELECT payment_method, COALESCE(SUM(amount_received),0) t FROM payments WHERE status IN('verified','approved') GROUP BY payment_method ORDER BY t DESC LIMIT 5");
        if ($mr) while ($row = $mr->fetch_assoc()) { $ml[] = $row['payment_method'] ?: 'Other'; $mv[] = (float)$row['t']; }
    }
    $collRate = $total_revenue > 0 ? round(min(100, ($today_collection / max(1, $total_revenue / 365)) * 100)) : 50;
    ?>
    <div class="section-card mb-3 p-2 analytics-bar" data-ax='<?= json_encode(['months'=>$mn,'rev'=>$rv,'exp'=>$ex,'methods'=>['l'=>$ml,'v'=>$mv],'attendance'=>$staffAttendanceToday,'collRate'=>$collRate]) ?>'>
      <div class="ax-strip">
        <div class="ax"><canvas id="chartRevenue" height="58"></canvas></div>
        <div class="ax"><canvas id="chartPaymentMethods" height="58"></canvas></div>
        <div class="ax"><canvas id="chartStaffAttendance" height="58"></canvas></div>
        <div class="ax"><div id="performanceGauge" style="height:58px"></div></div>
        <div class="ax ax-w"><div id="aiInsightsPanel" style="font-size:9px;min-height:42px;line-height:1.3"><span class="text-muted">Analyzing...</span></div><div id="aiPredictionPanel" style="font-size:9px"></div></div>
      </div>
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
    <!-- ═══ DYNAMIC SECTIONS (hidden, shown via module card click) ═══ -->
    <style>
    .dg-section { display:none; }
    .dg-section.active { display:block; }
    .quick-chevron { transition: transform .25s ease; }
    .quick-chevron.rotated { transform: rotate(180deg); }
    </style>

    <div id="section-executive" class="dg-section">
      <div class="executive-section mb-4">
        <div class="section-card">
          <div class="d-flex align-items-center justify-content-between mb-3">
            <div><h5 class="fw-bold mb-0" style="font-size:1rem"><i class="fas fa-chart-simple me-2 text-primary"></i>Executive Overview</h5><small class="text-muted">Real-time institutional snapshot</small></div>
            <span class="badge bg-primary" style="font-size:9px">Updated live</span>
          </div>
          <?= renderExecutiveOverview($studentsConn, $conn) ?>
        </div>
      </div>
      <div class="mb-4"><div class="section-card"><div class="d-flex align-items-center justify-content-between mb-3"><div><h5 class="fw-bold mb-0" style="font-size:1rem"><i class="fas fa-building me-2 text-warning"></i>Department Performance</h5><small class="text-muted">Status, problems, trends &amp; responsible directors</small></div></div><?= renderDepartmentComparison($conn) ?></div></div>
      <div class="row g-3 mb-4">
        <div class="col-lg-4"><div class="section-card h-100"><h6 class="fw-bold mb-3" style="font-size:0.95rem"><i class="fas fa-sitemap me-2 text-info"></i>Institutional Hierarchy</h6><?php echo renderHierarchyChart($conn); ?></div></div>
        <div class="col-lg-4"><div class="section-card h-100"><div class="d-flex align-items-center justify-content-between mb-3"><h6 class="fw-bold mb-0" style="font-size:0.95rem"><i class="fas fa-bell me-2 text-danger"></i>Active Alerts</h6><?php $ac=getAlertCounts($conn); if($ac['critical']>0): ?><span class="badge bg-danger"><?= $ac['critical'] ?> Critical</span><?php endif; if($ac['high']>0): ?><span class="badge bg-warning text-dark"><?= $ac['high'] ?> High</span><?php endif; ?></div><?= renderAlertsPanel($conn,null,5) ?></div></div>
        <div class="col-lg-4"><div class="section-card h-100"><h6 class="fw-bold mb-3" style="font-size:0.95rem"><i class="fas fa-shield-alt me-2 text-success"></i>Compliance &amp; Risk</h6><div class="mb-3"><div class="fw-semibold small mb-2">Compliance Status</div><?= renderComplianceSummary($conn) ?></div><div><div class="fw-semibold small mb-2">Top Risks</div><?= renderRiskRegister($conn,4) ?></div></div></div>
      </div>
      <div class="mb-4"><div class="section-card"><div class="d-flex align-items-center justify-content-between mb-3"><div><h5 class="fw-bold mb-0" style="font-size:1rem"><i class="fas fa-chart-bar me-2 text-success"></i>Director Performance Monitoring</h5><small class="text-muted">Dept targets, completed/pending/delayed tasks</small></div></div><div class="row g-3"><?php foreach([1,3,4,5,6,27] as $rid): $rq=$conn?$conn->prepare("SELECT id,role_name FROM igangaschoolofl_staffs_db.staff_roles WHERE id=?"):false; $rn=''; $si=0; if($rq){$rq->bind_param('i',$rid);$rq->execute();$rr=$rq->get_result()->fetch_assoc();$rq->close();if($rr)$rn=$rr['role_name'];} if($rn): $sq=$conn->prepare("SELECT id FROM staff WHERE role_id=? AND status='Active' LIMIT 1"); if($sq){$sq->bind_param('i',$rid);$sq->execute();$sr=$sq->get_result()->fetch_assoc();$sq->close();if($sr)$si=$sr['id'];} ?><div class="col-md-4 col-lg-3"><?= renderDirectorPerformanceCard($si,$rid,$rn,$conn) ?></div><?php endif; endforeach; ?></div></div></div>
    </div>

    <div id="section-financial" class="dg-section">
      <div class="row g-4">
        <div class="col-lg-7">
          <div class="section-card h-100">
            <h2 style="cursor:pointer" data-bs-toggle="collapse" data-bs-target="#financialOverviewContent" aria-expanded="false"><i class="fas fa-coins me-2 text-success"></i>Financial Overview<i class="fas fa-chevron-down float-end mt-1 quick-chevron"></i></h2>
            <div id="financialOverviewContent" class="collapse">
              <div class="row g-3 mt-2">
                <div class="col-4 col-md-3"><div class="p-3 rounded text-center" style="background:#f0fdf4"><div class="fs-5 fw-bold" style="color:#166534">UGX <?= number_format($today_collection) ?></div><small style="color:#14532d">Today</small></div></div>
                <div class="col-4 col-md-3"><div class="p-3 rounded text-center" style="background:#fefce8"><div class="fs-5 fw-bold" style="color:#854d0e">UGX <?= number_format($week_collection) ?></div><small style="color:#713f12">This Week</small></div></div>
                <div class="col-4 col-md-3"><div class="p-3 rounded text-center" style="background:#eff6ff"><div class="fs-5 fw-bold" style="color:#1e40af">UGX <?= number_format($month_collection) ?></div><small style="color:#1e3a8a">This Month</small></div></div>
                <div class="col-6 col-md-3"><div class="p-3 rounded text-center" style="background:#fef2f2"><div class="fs-5 fw-bold" style="color:#991b1b">UGX <?= number_format($outstanding) ?></div><small style="color:#7f1d1d">Outstanding</small></div></div>
                <div class="col-6 col-md-3"><div class="p-3 rounded text-center" style="background:#f8fafc"><div class="fs-5 fw-bold" style="color:#0f172a">UGX <?= number_format($total_revenue) ?></div><small style="color:#475569">Total Revenue</small></div></div>
                <div class="col-6 col-md-3"><div class="p-3 rounded text-center" style="background:#fff7ed"><div class="fs-5 fw-bold" style="color:#9a3412">UGX <?= number_format($total_expenses) ?></div><small style="color:#7c2d12">Total Expenses</small></div></div>
                <div class="col-6 col-md-3"><div class="p-3 rounded text-center" style="background:#f0fdf4"><div class="fs-5 fw-bold" style="color:#166534">UGX <?= number_format($total_revenue-$total_expenses) ?></div><small style="color:#14532d">Net Position</small></div></div>
                <div class="col-6 col-md-3"><div class="p-3 rounded text-center" style="background:#faf5ff"><div class="fs-5 fw-bold" style="color:#6b21a8">UGX <?= number_format($month_collection-$total_expenses) ?></div><small style="color:#581c87">Monthly Balance</small></div></div>
              </div>
              <div class="mt-2 d-flex flex-wrap gap-2"><a href="../dashboards/director-finance.php" class="btn btn-outline-success btn-sm"><i class="fas fa-coins me-1"></i>Finance Dashboard</a><a href="../dashboards/school-bursar.php" class="btn btn-outline-primary btn-sm"><i class="fas fa-money-bill me-1"></i>Bursar Panel</a><a href="../dashboards/budget-management.php" class="btn btn-outline-warning btn-sm"><i class="fas fa-chart-line me-1"></i>Budget</a></div>
            </div>
          </div>
        </div>
        <div class="col-lg-5">
          <div class="section-card h-100">
            <h2 class="mb-2"><i class="fas fa-list me-2 text-primary"></i>Recent Payments</h2>
            <?php if(empty($recent_payments)): ?><p class="text-muted small">No recent payments.</p>
            <?php else: ?>
            <div class="table-responsive"><table class="table table-sm table-hover"><thead class="table-light"><tr><th>Student</th><th>Amount</th><th>Method</th><th>Date</th><th>Status</th></tr></thead><tbody>
            <?php foreach($recent_payments as $p): $pc=in_array($p['status'],['verified','approved']) ? 'bg-success' : 'bg-warning text-dark'; ?>
            <tr><td><small><?= htmlspecialchars(($p['first_name']??'').' '.($p['last_name']??'').' ('.($p['student_number']??'').')') ?></small></td><td><strong>UGX <?= number_format($p['amount_received']??$p['amount_paid']??0) ?></strong></td><td><small><?= htmlspecialchars($p['payment_method']??'-') ?></small></td><td><small><?= isset($p['payment_date'])?date('d M',strtotime($p['payment_date'])):'-' ?></small></td><td><span class="badge <?= $pc ?>"><?= htmlspecialchars($p['status']??'') ?></span></td></tr>
            <?php endforeach; ?></tbody></table></div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>

    <div id="section-staff" class="dg-section">
      <div class="row g-4">
        <div class="col-lg-5">
          <div class="section-card h-100">
            <h2><i class="fas fa-clipboard-list me-2"></i>Employee Daily Analysis</h2>
            <div class="row g-3 mt-2">
              <div class="col-3 col-md-3"><div class="p-3 rounded text-center" style="background:#dcfce7"><div class="fs-3 fw-bold" style="color:#166534"><?= $staffAttendanceToday['present'] ?></div><small style="color:#14532d">Present</small></div></div>
              <div class="col-3 col-md-3"><div class="p-3 rounded text-center" style="background:#fef9c3"><div class="fs-3 fw-bold" style="color:#854d0e"><?= $staffAttendanceToday['late'] ?></div><small style="color:#713f12">Late</small></div></div>
              <div class="col-3 col-md-3"><div class="p-3 rounded text-center" style="background:#fee2e2"><div class="fs-3 fw-bold" style="color:#991b1b"><?= $staffAttendanceToday['absent'] ?></div><small style="color:#7f1d1d">Absent</small></div></div>
              <div class="col-3 col-md-3"><div class="p-3 rounded text-center" style="background:#dbeafe"><div class="fs-3 fw-bold" style="color:#1e40af"><?= $staffAttendanceToday['on_leave'] ?></div><small style="color:#1e3a8a">On Leave</small></div></div>
            </div>
            <div class="mt-3 d-flex flex-wrap gap-2"><a href="../dashboards/staff-attendance.php" class="btn btn-outline-primary btn-sm"><i class="fas fa-clock me-1"></i>Full Report</a><a href="../dashboards/hr-manager.php" class="btn btn-outline-success btn-sm"><i class="fas fa-users me-1"></i>HR Dashboard</a><a href="../dashboards/staff-directory.php" class="btn btn-outline-info btn-sm"><i class="fas fa-address-book me-1"></i>Staff Directory</a></div>
            <?php if(!empty($dept_list)): ?>
            <h2 class="mt-4"><i class="fas fa-building me-2"></i>Departments</h2><div class="row g-2"><?php foreach($dept_list as $d): ?><div class="col-md-6 col-6"><div class="border rounded p-2"><div class="fw-bold small"><?= htmlspecialchars($d['department_name']) ?></div><small class="text-muted"><?= htmlspecialchars($d['department_code']??'') ?> | <?= htmlspecialchars($d['department_level']??'') ?></small></div></div><?php endforeach; ?></div>
            <?php endif; ?>
          </div>
        </div>
        <div class="col-lg-7">
          <div class="section-card h-100">
            <div class="d-flex justify-content-between align-items-center mb-3"><h2 class="mb-0"><i class="fas fa-id-badge me-2"></i>All Staff (<?= count($staff_list) ?>+)</h2><a href="../dashboards/hr-manager.php" class="btn btn-sm btn-outline-primary">HR Dashboard</a></div>
            <div class="table-responsive" style="max-height:320px;overflow-y:auto"><table class="table table-sm table-hover align-middle"><thead class="table-light"><tr><th>ID</th><th>Name</th><th>Role</th><th>Department</th><th>Email</th><th>Status</th></tr></thead><tbody>
            <?php if(empty($staff_list)): ?><tr><td colspan="6" class="text-center text-muted py-3">No staff records found.</td></tr>
            <?php else: foreach($staff_list as $s): $bc=$s['status']==='Active'?'bg-success':($s['status']==='On Leave'?'bg-warning text-dark':'bg-danger'); ?>
            <tr><td><code><?= htmlspecialchars($s['staff_id']) ?></code></td><td><strong><?= htmlspecialchars($s['full_name']) ?></strong></td><td><?= htmlspecialchars($s['role_name']??$s['position']) ?></td><td><?= htmlspecialchars($s['department']??'-') ?></td><td><small><?= htmlspecialchars($s['email']) ?></small></td><td><span class="badge <?= $bc ?>"><?= htmlspecialchars($s['status']) ?></span></td></tr>
            <?php endforeach; endif; ?></tbody></table></div>
          </div>
          <div class="section-card mt-3"><h2><i class="fas fa-history me-2"></i>Recent System Activities</h2><?php if(empty($recent_activities)): ?><p class="text-muted small">No recent activities recorded.</p><?php else: ?><ul class="list-unstyled mb-0"><?php foreach($recent_activities as $act): ?><li class="border-bottom py-2 d-flex gap-3 align-items-start"><span class="badge bg-primary mt-1"><?= htmlspecialchars($act['activity_type']) ?></span><div><div class="small"><?= htmlspecialchars($act['activity_description']??'') ?></div><small class="text-muted"><?= $act['created_at']?date('d M Y H:i',strtotime($act['created_at'])):'' ?></small></div></li><?php endforeach; ?></ul><?php endif; ?></div>
        </div>
      </div>
    </div>

    <div id="section-student" class="dg-section">
      <div class="section-card">
        <div class="d-flex justify-content-between align-items-center mb-0">
          <h2 class="mb-0" style="cursor:pointer" data-bs-toggle="collapse" data-bs-target="#studentManagementContent" aria-expanded="false"><i class="fas fa-user-graduate me-2"></i>Student Management<i class="fas fa-chevron-down ms-1 small quick-chevron"></i></h2>
          <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addStudentModal"><i class="fas fa-plus me-2"></i>Add New</button>
        </div>
        <div id="studentManagementContent" class="collapse mt-3">
          <?= displayStudentSearchBox('Search students by name or index number', 'dg_search') ?>
          <div class="row g-3 mt-3"><?php $rs=array_slice($allStudentsData,0,6); foreach($rs as $s): $sid=$s['index_number']??$s['student_number']??$s['national_id']??''; ?><div class="col-md-4 col-lg-2"><div class="cursor-pointer" onclick="showStudentProfileModal('<?= addslashes($sid) ?>')"><?= displayStudentProfileCard($sid,'compact') ?></div></div><?php endforeach; ?></div>
          <div class="mt-3"><button class="btn btn-outline-secondary btn-sm w-100" type="button" data-bs-toggle="collapse" data-bs-target="#fullStudentRecords"><i class="fas fa-chevron-down me-1"></i>View All Records</button><div class="collapse mt-2" id="fullStudentRecords"><?php renderStudentSetViewer($studentsConn,['title'=>'','icon'=>'fa-users-gear','super_admin'=>true,'show_all'=>true]); ?></div></div>
        </div>
      </div>
    </div>

    <div id="section-approvals" class="dg-section">
      <div class="section-card"><div class="d-flex align-items-center justify-content-between mb-3"><div><h5 class="fw-bold mb-0" style="font-size:1rem"><i class="fas fa-check-double me-2 text-primary"></i>Pending Approvals</h5><small class="text-muted">Workflow items requiring attention</small></div></div><?php $pa=getPendingApprovals($conn,null,8); if(!empty($pa)): ?><div class="row g-2"><?php foreach($pa as $a): ?><div class="col-md-6 col-lg-3"><?= renderApprovalWorkflowCard($a,$conn) ?><?= renderApprovalActionButtons($a['id']) ?></div><?php endforeach; ?></div><?php else: ?><div class="text-center text-muted py-3"><i class="fas fa-check-circle fa-2x mb-2 text-success"></i><div>No pending approvals.</div></div><?php endif; ?></div>
    </div>

    <div id="section-monitoring" class="dg-section">
      <div class="row g-4">
        <div class="col-lg-6"><div class="section-card h-100"><h5 class="fw-bold mb-3" style="font-size:0.95rem"><i class="fas fa-bell me-2 text-danger"></i>Active Alerts</h5><?php renderAlertsPanel($conn,null,8); ?></div></div>
        <div class="col-lg-6"><div class="section-card h-100"><div class="d-flex align-items-center justify-content-between mb-3"><div><h5 class="fw-bold mb-0" style="font-size:1rem"><i class="fas fa-history me-2 text-secondary"></i>Recent Audit Trail</h5><small class="text-muted">Latest tracked actions</small></div></div><?= renderAuditTrailTable($conn,[],8) ?></div></div>
      </div>
    </div>

    <div id="section-services" class="dg-section">
      <?php if($totalPending>0): ?>
      <div class="section-card mb-4" style="border-left:4px solid #dc2626">
        <div class="d-flex align-items-center justify-content-between mb-3"><h2 class="mb-0"><i class="fas fa-bell me-2" style="color:#dc2626"></i>Pending Submissions</h2><span class="badge bg-danger rounded-pill fs-6"><?= $totalPending ?> New</span></div>
        <div class="row g-2 mb-3">
          <div class="col-3 col-md-3"><div class="p-3 rounded text-center" style="background:#fee2e2"><div class="fs-3 fw-bold" style="color:#991b1b"><?= $pendingContacts ?></div><small style="color:#7f1d1d">Messages</small></div></div>
          <div class="col-3 col-md-3"><div class="p-3 rounded text-center" style="background:#fef3c7"><div class="fs-3 fw-bold" style="color:#92400e"><?= $pendingVolunteers ?></div><small style="color:#78350f">Volunteers</small></div></div>
          <div class="col-3 col-md-3"><div class="p-3 rounded text-center" style="background:#dbeafe"><div class="fs-3 fw-bold" style="color:#1e40af"><?= $pendingDonations ?></div><small style="color:#1e3a8a">Donations</small></div></div>
          <div class="col-3 col-md-3"><div class="p-3 rounded text-center" style="background:#dcfce7"><div class="fs-3 fw-bold" style="color:#166534"><?= $pendingApplications ?></div><small style="color:#14532d">Applications</small></div></div>
        </div>
        <?php if(!empty($recentSubmissions)): ?><div class="list-group list-group-flush"><?php foreach($recentSubmissions as $sub): $ic=['contact'=>'fa-envelope','volunteer'=>'fa-hands-helping','donation'=>'fa-hand-holding-heart','application'=>'fa-file-alt'][$sub['type']]??'fa-bell'; $cl=['contact'=>'#dc2626','volunteer'=>'#d97706','donation'=>'#2563eb','application'=>'#16a34a'][$sub['type']]??'#6b7280'; $lb=['contact'=>'Contact','volunteer'=>'Volunteer','donation'=>'Donation','application'=>'Application'][$sub['type']]??'Submission'; ?><div class="list-group-item border-0 ps-0 d-flex align-items-center gap-3"><div style="width:36px;height:36px;border-radius:50%;background:<?= $cl ?>15;display:flex;align-items:center;justify-content:center;flex-shrink:0"><i class="fas <?= $ic ?>" style="color:<?= $cl ?>;font-size:14px"></i></div><div class="flex-grow-1 min-width-0"><div class="fw-semibold" style="font-size:14px"><?= htmlspecialchars($sub['name']) ?></div><div style="font-size:12px;color:#64748b"><?= htmlspecialchars($sub['title']) ?> <span class="badge bg-light text-dark ms-1"><?= $lb ?></span></div></div><small style="color:#94a3b8;flex-shrink:0"><?= date('d M H:i',strtotime($sub['created_at'])) ?></small></div><?php endforeach; ?></div><?php endif; ?>
      </div>
      <?php endif; ?>
    </div>

    <div id="section-store" class="dg-section">
      <div class="row g-4">
        <div class="col-lg-6">
          <div class="section-card h-100"><h2><i class="fas fa-shopping-cart me-2 text-warning"></i>Pending Store Requests</h2><?php $storeReqs=[]; if($conn){$sr=$conn->query("SELECT sr.request_number,sr.urgency,sr.status,sr.created_at,s.full_name as requester FROM store_requests sr LEFT JOIN staff s ON sr.requested_by=s.id WHERE sr.status IN('pending','forwarded') ORDER BY FIELD(sr.urgency,'urgent','high','medium','low'),sr.created_at ASC LIMIT 5");if($sr)while($row=$sr->fetch_assoc())$storeReqs[]=$row;} if(empty($storeReqs)): ?><p class="text-muted small">No pending store requests.</p><?php else: foreach($storeReqs as $sr_): ?><div class="d-flex justify-content-between align-items-center border-bottom py-2"><div><code class="fw-bold"><?= htmlspecialchars($sr_['request_number']) ?></code><small class="text-muted ms-2">by <?= htmlspecialchars($sr_['requester']??'') ?></small></div><div class="d-flex align-items-center gap-2"><span class="badge bg-<?= $sr_['urgency']==='urgent'?'danger':($sr_['urgency']==='high'?'warning text-dark':'info') ?>"><?= $sr_['urgency'] ?></span><small class="text-muted"><?= date('d M',strtotime($sr_['created_at'])) ?></small></div></div><?php endforeach; ?><div class="text-center mt-2"><a href="../dashboards/storekeeper.php" class="btn btn-sm btn-outline-warning"><i class="fas fa-warehouse me-1"></i>Go to Store</a></div><?php endif; ?></div>
        </div>
        <div class="col-lg-6">
          <div class="section-card h-100"><h2><i class="fas fa-tasks me-2 text-primary"></i>Official Duties</h2><?php renderOfficialDuties($user_role_id,$conn); ?></div>
        </div>
      </div>
    </div>

    <div id="section-communications" class="dg-section">
      <div class="section-card"><?php renderNewsWidget($conn,$websiteConn,$user_id,$user_name,$user_role,5); ?></div>
    </div>

    <div id="section-quick" class="dg-section">
      <div class="section-card">
        <h2 style="cursor:pointer" data-bs-toggle="collapse" data-bs-target="#quickActionsContent" aria-expanded="false"><i class="fas fa-bolt me-2"></i>Quick Actions<i class="fas fa-chevron-down float-end mt-1 quick-chevron"></i></h2>
        <div id="quickActionsContent" class="collapse">
          <div class="mb-3 mt-2"><div class="d-flex align-items-center gap-2 mb-2"><span class="badge bg-primary" style="font-size:11px">OPERATIONS</span></div><div class="d-flex flex-wrap gap-2"><a href="../news.php" class="btn btn-outline-dark btn-sm"><i class="fas fa-newspaper me-1"></i>Manage News</a><button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#annModal"><i class="fas fa-bullhorn me-1"></i>Send Announcement</button><a href="../dashboards/staff_transcript_generation.php" class="btn btn-outline-success btn-sm"><i class="fas fa-file-alt me-1"></i>Transcripts</a><a href="../dashboards/staff_receipt_printing.php" class="btn btn-outline-info btn-sm"><i class="fas fa-receipt me-1"></i>Receipts</a><a href="../import_students_excel.php" class="btn btn-outline-info btn-sm"><i class="fas fa-file-excel me-1"></i>Import Students</a><button class="btn btn-outline-primary btn-sm no-print" onclick="window.print()"><i class="fas fa-print me-1"></i>Print</button></div></div>
          <div class="mb-3"><div class="d-flex align-items-center gap-2 mb-2"><span class="badge bg-warning text-dark" style="font-size:11px">EXECUTIVE</span></div><div class="d-flex flex-wrap gap-2"><a href="../dashboards/director-academics.php" class="btn btn-outline-primary btn-sm"><i class="fas fa-graduation-cap me-1"></i>Academics</a><a href="../dashboards/director-finance.php" class="btn btn-outline-success btn-sm"><i class="fas fa-coins me-1"></i>Finance</a><a href="../dashboards/director-admissions.php" class="btn btn-outline-info btn-sm"><i class="fas fa-file-contract me-1"></i>Admissions</a><a href="../dashboards/director-ict.php" class="btn btn-outline-secondary btn-sm"><i class="fas fa-laptop-code me-1"></i>ICT</a></div></div>
          <div class="mb-3"><div class="d-flex align-items-center gap-2 mb-2"><span class="badge bg-info" style="font-size:11px">ADMIN</span></div><div class="d-flex flex-wrap gap-2"><a href="../dashboards/school-principal.php" class="btn btn-outline-primary btn-sm"><i class="fas fa-chalkboard-teacher me-1"></i>Principal</a><a href="../dashboards/deputy-principal.php" class="btn btn-outline-primary btn-sm"><i class="fas fa-user-check me-1"></i>Deputy</a><a href="../dashboards/academic-registrar.php" class="btn btn-outline-primary btn-sm"><i class="fas fa-file-alt me-1"></i>Registrar</a><a href="../dashboards/school-secretary.php" class="btn btn-outline-info btn-sm"><i class="fas fa-envelope me-1"></i>Secretary</a><a href="../dashboards/hr-manager.php" class="btn btn-outline-danger btn-sm"><i class="fas fa-users me-1"></i>HR</a><a href="../dashboards/school-bursar.php" class="btn btn-outline-success btn-sm"><i class="fas fa-money-bill me-1"></i>Bursar</a></div></div>
          <div><div class="d-flex align-items-center gap-2 mb-2"><span class="badge bg-success" style="font-size:11px">ACADEMIC</span></div><div class="d-flex flex-wrap gap-2"><a href="../dashboards/head-nursing.php" class="btn btn-outline-success btn-sm"><i class="fas fa-heartbeat me-1"></i>Nursing</a><a href="../dashboards/head-midwifery.php" class="btn btn-outline-success btn-sm"><i class="fas fa-user-md me-1"></i>Midwifery</a><a href="../dashboards/senior-lecturers.php" class="btn btn-outline-success btn-sm"><i class="fas fa-user-graduate me-1"></i>Senior</a><a href="../dashboards/lecturers.php" class="btn btn-outline-success btn-sm"><i class="fas fa-chalkboard me-1"></i>Lecturers</a><a href="../dashboards/school-librarian.php" class="btn btn-outline-info btn-sm"><i class="fas fa-book me-1"></i>Librarian</a><a href="../dashboards/student-management.php" class="btn btn-outline-primary btn-sm"><i class="fas fa-users-rectangle me-1"></i>Students</a></div></div>
        </div>
      </div>
    </div>

    <script>
    // Module card → section toggle + hash routing
    (function () {
      var sections = document.querySelectorAll('.dg-section');
      var sectionMap = {
        'Student Management':'section-student', 'Academic Management':'section-student',
        'Financial Management':'section-financial', 'Staff & HR Management':'section-staff',
        'Executive':'section-executive', 'Executive Management':'section-executive',
        'Student Services':'section-services', 'Store & Assets':'section-store',
        'Communications':'section-communications', 'Approvals & Workflow':'section-approvals',
        'Monitoring & Alerts':'section-monitoring',
        'Quick Access':'section-quick', 'Quick Actions':'section-quick'
      };
      var hashMap = {
        'executive': 'section-executive', 'hierarchy': 'section-executive',
        'departments': 'section-executive', 'performance': 'section-executive',
        'financial': 'section-financial',
        'staff': 'section-staff',
        'student': 'section-student',
        'approvals': 'section-approvals',
        'alerts': 'section-monitoring', 'audit': 'section-monitoring',
        'compliance': 'section-monitoring', 'risks': 'section-monitoring',
        'services': 'section-services',
        'store': 'section-store',
        'communications': 'section-communications',
        'quick': 'section-quick'
      };

      function showSection(id) {
        sections.forEach(function(s){ s.classList.remove('active'); });
        var el = document.getElementById(id);
        if (el) el.classList.add('active');
      }

      function showSectionFromHash() {
        var hash = window.location.hash.replace('#', '');
        if (!hash) return;
        var sid = hashMap[hash];
        if (sid) showSection(sid);
      }

      // Handle hash changes
      window.addEventListener('hashchange', showSectionFromHash);

      // Add onclick to module cards. Use event delegation on the slider track.
      document.addEventListener('click', function(e) {
        var card = e.target.closest('.module-card');
        if (!card) return;
        var title = card.querySelector('.module-card-title');
        if (!title) return;
        var t = title.textContent.trim();
        var sid = sectionMap[t];
        if (sid) {
          var target = document.getElementById(sid);
          if (target && target.classList.contains('active')) {
            target.classList.remove('active');
          } else {
            showSection(sid);
          }
        }
      });

      // Collapse chevron rotation
      function bindChevron(cid) {
        var el = document.getElementById(cid);
        if (!el) return;
        var ch = document.querySelector('[data-bs-target="#' + cid + '"] .quick-chevron');
        el.addEventListener('show.bs.collapse', function(){ if(ch) ch.classList.add('rotated'); });
        el.addEventListener('hide.bs.collapse', function(){ if(ch) ch.classList.remove('rotated'); });
      }
      function initChevrons() {
        ['quickActionsContent','employeeAnalysisContent','financialOverviewContent','studentManagementContent','staffTableCollapse'].forEach(bindChevron);
      }
      if (document.readyState === 'complete') { initChevrons(); showSectionFromHash(); }
      else document.addEventListener('DOMContentLoaded', function() { initChevrons(); showSectionFromHash(); });
    })();
    </script>
    <?php if (function_exists('registerApprovalActionHandler')) registerApprovalActionHandler(); ?>

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
window.allStudents = <?php echo json_encode(array_slice($allStudentsData, 0, 1000)) ?: '[]' ?>;
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
