<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
$ctx = bootstrapStaffDashboard(['ceo', 'director general']);
$conn = $ctx['staff'];
$students_conn = $ctx['students'];
$user = $ctx['user'];
$user_name = $user['full_name'] ?? 'CEO';

// ── Page routing ──
$pageToSection = [
    'home'          => 'overview',
    'overview'      => 'overview',
    'departments'   => 'departments',
    'performance'   => 'performance',
    'financial'     => 'financial',
    'staff'         => 'staff',
    'student'       => 'student',
    'system-health' => 'system-health',
    'quality'       => 'quality',
    'audit'         => 'audit',
];
$page  = $_GET['page'] ?? 'home';
$section = $pageToSection[$page] ?? 'overview';

$stats = [
    'students' => 0, 'staff' => 0, 'programs' => 0, 'revenue' => 0
];
if ($students_conn) {
    $r = $students_conn->query("SELECT COUNT(*) c FROM students"); if ($r) $stats['students'] = (int)$r->fetch_assoc()['c'];
}
if ($conn) {
    $r = $conn->query("SELECT COUNT(*) c FROM staff"); if ($r) $stats['staff'] = (int)$r->fetch_assoc()['c'];
    $r = $conn->query("SELECT COUNT(*) c FROM academic_programs WHERE status='Active'"); if ($r) $stats['programs'] = (int)$r->fetch_assoc()['c'];
    $r = $conn->query("SELECT COALESCE(SUM(amount_paid),0) total FROM igangaschoolofl_students_db.payments"); if ($r) $stats['revenue'] = (float)$r->fetch_assoc()['total'];
}

// Data for additional sections
$deptList = []; $deptStaffCounts = [];
if ($conn) {
    $r = $conn->query("SELECT d.name, d.code, (SELECT COUNT(*) FROM staff WHERE department=d.code) staff_count FROM departments d ORDER BY d.name");
    if ($r) { $deptList = $r->fetch_all(MYSQLI_ASSOC); $deptStaffCounts = array_column($deptList, 'staff_count', 'code'); }
}
$pendingApprovals = 0;
if ($conn) { $r = $conn->query("SELECT COUNT(*) c FROM approval_requests WHERE status='Pending'"); if ($r) $pendingApprovals = (int)$r->fetch_assoc()['c']; }
$qaReviews = []; $qaPassRate = 0;
if ($conn) {
    $r = $conn->query("SELECT COUNT(*) c FROM quality_assurance_reviews"); $qaTotal = $r ? (int)$r->fetch_assoc()['c'] : 0;
    $r = $conn->query("SELECT COUNT(*) c FROM quality_assurance_reviews WHERE status IN ('Pass','Compliant','Approved')"); $qaPass = $r ? (int)$r->fetch_assoc()['c'] : 0;
    $qaPassRate = $qaTotal > 0 ? round($qaPass / $qaTotal * 100) : 0;
    $r = $conn->query("SELECT q.*, u.full_name reviewer_name FROM quality_assurance_reviews q LEFT JOIN staff u ON q.reviewed_by=u.id ORDER BY q.review_date DESC LIMIT 10");
    if ($r) $qaReviews = $r->fetch_all(MYSQLI_ASSOC);
}
$auditLogs = [];
if ($conn) {
    $r = $conn->query("SELECT a.*, u.full_name user_name FROM staff_audit_logs a LEFT JOIN staff u ON a.user_id=u.id ORDER BY a.created_at DESC LIMIT 20");
    if ($r) $auditLogs = $r->fetch_all(MYSQLI_ASSOC);
}
$recentStudents = [];
if ($students_conn) {
    $r = $students_conn->query("SELECT id, student_number, full_name, program, status, created_at FROM students ORDER BY created_at DESC LIMIT 10");
    if ($r) $recentStudents = $r->fetch_all(MYSQLI_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head><?php include_once __DIR__ . '/../includes/dashboard_head.php'; ?>
</head>
<body>
<?php include_once __DIR__ . '/../includes/sidebar.php'; ?>
<?php include_once __DIR__ . '/../includes/dashboard_topbar.php'; ?>
<div class="page-content">
<?php switch ($section):
    case 'overview': ?>
    <div class="content-header">
        <h1><i class="fas fa-crown"></i> CEO Dashboard</h1>
        <span class="text-muted"><?= date('l, d M Y') ?></span>
    </div>
    <div class="row g-3 mb-4">
        <div class="col-md-3"><div class="card"><div class="card-body"><h6>Students</h6><h3><?= number_format($stats['students']) ?></h3></div></div></div>
        <div class="col-md-3"><div class="card"><div class="card-body"><h6>Staff</h6><h3><?= number_format($stats['staff']) ?></h3></div></div></div>
        <div class="col-md-3"><div class="card"><div class="card-body"><h6>Active Programs</h6><h3><?= $stats['programs'] ?></h3></div></div></div>
        <div class="col-md-3"><div class="card"><div class="card-body"><h6>Total Revenue</h6><h3>UGX <?= number_format($stats['revenue'], 0) ?></h3></div></div></div>
    </div>
    <div class="card">
        <div class="card-body text-center py-4">
            <i class="fas fa-search fa-3x mb-3 text-muted"></i>
            <h5>Student Records</h5>
            <p class="text-muted">Use the <a href="student-management.php" class="fw-bold">Student Management</a> module to search and view student records.</p>
        </div>
    </div>
        <?php break;
    case 'staff': ?>
        <div class="content-header">
            <h1><i class="fas fa-users me-2"></i>Staff Management</h1>
            <span class="text-muted"><?= date('l, d M Y') ?></span>
        </div>
        <?php
        $totalStaff = 0; $activeStaff = 0; $onLeave = 0; $newThisMonth = 0;
        $staffList = [];
        if ($conn) {
            $r = $conn->query("SELECT COUNT(*) c FROM staff"); if ($r) $totalStaff = (int)$r->fetch_assoc()['c'];
            $r = $conn->query("SELECT COUNT(*) c FROM staff WHERE status='Active'"); if ($r) $activeStaff = (int)$r->fetch_assoc()['c'];
            $r = $conn->query("SELECT COUNT(*) c FROM staff WHERE status='On Leave'"); if ($r) $onLeave = (int)$r->fetch_assoc()['c'];
            $r = $conn->query("SELECT COUNT(*) c FROM staff WHERE MONTH(created_at)=MONTH(NOW()) AND YEAR(created_at)=YEAR(NOW())"); if ($r) $newThisMonth = (int)$r->fetch_assoc()['c'];
            $s = $conn->query("SELECT id, full_name, email, phone, department, position, status FROM staff ORDER BY full_name");
            if ($s) $staffList = $s->fetch_all(MYSQLI_ASSOC);
        }
        ?>
        <div class="row g-3 mb-4">
            <div class="col-md-3"><div class="stat-card primary"><div class="stat-icon"><i class="fas fa-users"></i></div><div class="stat-content"><h3><?= number_format($totalStaff) ?></h3><p>Total Staff</p></div></div></div>
            <div class="col-md-3"><div class="stat-card success"><div class="stat-icon"><i class="fas fa-user-check"></i></div><div class="stat-content"><h3><?= number_format($activeStaff) ?></h3><p>Active</p></div></div></div>
            <div class="col-md-3"><div class="stat-card info"><div class="stat-icon"><i class="fas fa-plane"></i></div><div class="stat-content"><h3><?= number_format($onLeave) ?></h3><p>On Leave</p></div></div></div>
            <div class="col-md-3"><div class="stat-card warning"><div class="stat-icon"><i class="fas fa-user-plus"></i></div><div class="stat-content"><h3><?= number_format($newThisMonth) ?></h3><p>New This Month</p></div></div></div>
        </div>
        <div class="content-section">
            <h5 class="fw-bold mb-3"><i class="fas fa-address-book me-2"></i>Staff Directory</h5>
            <?php if (empty($staffList)): ?>
            <div class="text-center py-4 text-muted"><i class="fas fa-database fa-2x mb-2"></i><p class="mb-0">No staff records found.</p></div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle">
                    <thead class="table-light">
                        <tr><th>Full Name</th><th>Email</th><th>Phone</th><th>Department</th><th>Position</th><th>Status</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($staffList as $s): ?>
                        <tr>
                            <td><?= htmlspecialchars($s['full_name']) ?></td>
                            <td><?= htmlspecialchars($s['email'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($s['phone'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($s['department'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($s['position'] ?? '-') ?></td>
                            <td><span class="badge bg-<?= ($s['status'] ?? 'Active') === 'Active' ? 'success' : ($s['status'] === 'On Leave' ? 'info' : 'secondary') ?>"><?= htmlspecialchars($s['status'] ?? 'Active') ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
        <?php break;
    case 'departments': ?>
        <div class="content-header"><h1><i class="fas fa-building me-2"></i>Department Monitoring</h1><span class="text-muted"><?= date('l, d M Y') ?></span></div>
        <div class="row g-3 mb-4">
            <div class="col-md-3"><div class="card"><div class="card-body"><h6>Total Departments</h6><h3><?= count($deptList) ?></h3></div></div></div>
            <div class="col-md-3"><div class="card"><div class="card-body"><h6>Total Staff</h6><h3><?= number_format($stats['staff']) ?></h3></div></div></div>
            <div class="col-md-3"><div class="card"><div class="card-body"><h6>Pending Approvals</h6><h3><?= $pendingApprovals ?></h3></div></div></div>
            <div class="col-md-3"><div class="card"><div class="card-body"><h6>Active Programs</h6><h3><?= $stats['programs'] ?></h3></div></div></div>
        </div>
        <div class="content-section"><h5 class="fw-bold mb-3"><i class="fas fa-sitemap me-2"></i>Departments</h5>
            <div class="table-responsive"><table class="table table-striped table-hover"><thead class="table-light"><tr><th>Department</th><th>Code</th><th>Staff Count</th></tr></thead><tbody>
            <?php foreach ($deptList as $d): ?><tr><td><?= htmlspecialchars($d['name']) ?></td><td><?= htmlspecialchars($d['code']) ?></td><td><?= (int)$d['staff_count'] ?></td></tr><?php endforeach; ?>
            </tbody></table></div>
        </div>
        <?php break;
    case 'performance': ?>
        <div class="content-header"><h1><i class="fas fa-chart-bar me-2"></i>Performance Dashboard</h1><span class="text-muted"><?= date('l, d M Y') ?></span></div>
        <div class="row g-3 mb-4">
            <div class="col-md-4"><div class="card border-primary"><div class="card-body text-center"><h6 class="text-primary">Students</h6><h2 class="text-primary"><?= number_format($stats['students']) ?></h2><small class="text-muted">Enrolled</small></div></div></div>
            <div class="col-md-4"><div class="card border-success"><div class="card-body text-center"><h6 class="text-success">Staff</h6><h2 class="text-success"><?= number_format($stats['staff']) ?></h2><small class="text-muted">Active</small></div></div></div>
            <div class="col-md-4"><div class="card border-warning"><div class="card-body text-center"><h6 class="text-warning">QA Pass Rate</h6><h2 class="text-warning"><?= $qaPassRate ?>%</h2><small class="text-muted">Quality Assurance</small></div></div></div>
        </div>
        <div class="row g-3"><div class="col-md-6"><div class="card"><div class="card-body"><h6><i class="fas fa-check-circle text-success me-2"></i>Key Metrics</h6><hr>
            <p><strong>Programs:</strong> <?= $stats['programs'] ?></p><p><strong>Revenue:</strong> UGX <?= number_format($stats['revenue'], 0) ?></p>
            <p><strong>Departments:</strong> <?= count($deptList) ?></p><p><strong>Pending Approvals:</strong> <?= $pendingApprovals ?></p>
        </div></div></div></div>
        <?php break;
    case 'financial': ?>
        <div class="content-header"><h1><i class="fas fa-coins me-2"></i>Financial Overview</h1><span class="text-muted"><?= date('l, d M Y') ?></span></div>
        <div class="row g-3 mb-4">
            <div class="col-md-6"><div class="card border-success"><div class="card-body"><h6 class="text-success">Total Revenue</h6><h2 class="text-success">UGX <?= number_format($stats['revenue'], 0) ?></h2></div></div></div>
            <div class="col-md-6"><div class="card"><div class="card-body text-center"><p class="mb-0 text-muted">View detailed financial reports in the <a href="financial-reports.php" class="fw-bold">Financial Reports</a> module.</p></div></div></div>
        </div>
        <?php break;
    case 'student': ?>
        <div class="content-header"><h1><i class="fas fa-user-graduate me-2"></i>Student Management</h1><span class="text-muted"><?= date('l, d M Y') ?></span></div>
        <div class="row g-3 mb-4">
            <div class="col-md-3"><div class="card"><div class="card-body"><h6>Total Students</h6><h3><?= number_format($stats['students']) ?></h3></div></div></div>
            <div class="col-md-3"><div class="card"><div class="card-body"><h6>Programs</h6><h3><?= $stats['programs'] ?></h3></div></div></div>
        </div>
        <div class="content-section"><h5 class="fw-bold mb-3"><i class="fas fa-clock me-2"></i>Recent Registrations</h5>
            <?php if (empty($recentStudents)): ?><div class="text-center py-4 text-muted"><p>No recent student registrations.</p></div>
            <?php else: ?>
            <div class="table-responsive"><table class="table table-striped table-hover"><thead class="table-light"><tr><th>#</th><th>Name</th><th>Program</th><th>Status</th><th>Registered</th></tr></thead><tbody>
            <?php foreach ($recentStudents as $s): ?><tr><td><?= htmlspecialchars($s['student_number']) ?></td><td><?= htmlspecialchars($s['full_name']) ?></td><td><?= htmlspecialchars($s['program']) ?></td><td><span class="badge bg-<?= ($s['status']??'Active')==='Active'?'success':'secondary' ?>"><?= htmlspecialchars($s['status']??'Active') ?></span></td><td><?= htmlspecialchars($s['created_at']) ?></td></tr><?php endforeach; ?>
            </tbody></table></div>
            <?php endif; ?>
            <p class="mt-2"><a href="student-management.php" class="btn btn-sm btn-outline-primary"><i class="fas fa-external-link-alt me-1"></i>Open Student Management</a></p>
        </div>
        <?php break;
    case 'quality': ?>
        <div class="content-header"><h1><i class="fas fa-shield-alt me-2"></i>Quality Assurance</h1><span class="text-muted"><?= date('l, d M Y') ?></span></div>
        <div class="row g-3 mb-4">
            <div class="col-md-4"><div class="card border-success"><div class="card-body text-center"><h6 class="text-success">QA Pass Rate</h6><h2 class="text-success"><?= $qaPassRate ?>%</h2></div></div></div>
            <div class="col-md-4"><div class="card"><div class="card-body text-center"><h6>Reviews</h6><h2><?= count($qaReviews) ?></h2></div></div></div>
            <div class="col-md-4"><div class="card"><div class="card-body text-center"><p class="mb-0 text-muted">View full QA in the <a href="quality-assurance.php" class="fw-bold">Quality Assurance</a> module.</p></div></div></div>
        </div>
        <div class="content-section"><h5 class="fw-bold mb-3"><i class="fas fa-clipboard-check me-2"></i>Recent Reviews</h5>
            <?php if (empty($qaReviews)): ?><div class="text-center py-4 text-muted"><p>No QA reviews recorded yet.</p></div>
            <?php else: ?>
            <div class="table-responsive"><table class="table table-striped"><thead class="table-light"><tr><th>Review</th><th>Reviewer</th><th>Status</th><th>Date</th></tr></thead><tbody>
            <?php foreach ($qaReviews as $q): ?><tr><td><?= htmlspecialchars(mb_substr($q['review_title']??$q['description']??'',0,60)) ?></td><td><?= htmlspecialchars($q['reviewer_name']??'-') ?></td><td><span class="badge bg-<?= in_array($q['status']??'',['Pass','Compliant','Approved'])?'success':'warning' ?>"><?= htmlspecialchars($q['status']??'N/A') ?></span></td><td><?= htmlspecialchars($q['review_date']??$q['created_at']??'') ?></td></tr><?php endforeach; ?>
            </tbody></table></div>
            <?php endif; ?>
        </div>
        <?php break;
    case 'audit': ?>
        <div class="content-header"><h1><i class="fas fa-clipboard-check me-2"></i>Audit Trail</h1><span class="text-muted"><?= date('l, d M Y') ?></span></div>
        <div class="content-section"><h5 class="fw-bold mb-3"><i class="fas fa-history me-2"></i>Recent Activity</h5>
            <?php if (empty($auditLogs)): ?><div class="text-center py-4 text-muted"><i class="fas fa-database fa-2x mb-2"></i><p>No audit logs found.</p><p class="small">The <code>staff_audit_logs</code> table may be empty or does not exist.</p></div>
            <?php else: ?>
            <div class="table-responsive"><table class="table table-sm table-striped"><thead class="table-light"><tr><th>User</th><th>Action</th><th>Description</th><th>Date</th></tr></thead><tbody>
            <?php foreach ($auditLogs as $a): ?><tr><td><?= htmlspecialchars($a['user_name']??$a['user_id']??'-') ?></td><td><?= htmlspecialchars($a['action']??$a['event_type']??'N/A') ?></td><td><?= htmlspecialchars(mb_substr($a['description']??$a['details']??'',0,80)) ?></td><td><?= htmlspecialchars($a['created_at']??$a['logged_at']??'') ?></td></tr><?php endforeach; ?>
            </tbody></table></div>
            <?php endif; ?>
        </div>
        <?php break;
    case 'system-health': ?>
        <div class="content-header"><h1><i class="fas fa-heartbeat me-2"></i>System Health</h1><span class="text-muted"><?= date('l, d M Y') ?></span></div>
        <div class="row g-3 mb-4">
            <div class="col-md-4"><div class="card border-success"><div class="card-body text-center"><h6 class="text-success">Staff DB</h6><i class="fas fa-check-circle fa-2x text-success"></i><p class="mt-2 mb-0">Connected</p></div></div></div>
            <div class="col-md-4"><div class="card border-success"><div class="card-body text-center"><h6 class="text-success">Students DB</h6><i class="fas fa-check-circle fa-2x text-success"></i><p class="mt-2 mb-0">Connected</p></div></div></div>
            <div class="col-md-4"><div class="card border-info"><div class="card-body text-center"><h6 class="text-info">Data</h6><h2><?= number_format($stats['students'] + $stats['staff']) ?></h2><p class="mb-0 text-muted">Total Records</p></div></div></div>
        </div>
        <?php break;
    default: ?>
    <?php include_once __DIR__ . '/../includes/control_panel.php'; ?>
        <?php break;
endswitch; ?>
</div>
<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
</body>
</html>
