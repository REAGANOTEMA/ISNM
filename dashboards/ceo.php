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
    default: ?>
    <?php include_once __DIR__ . '/../includes/control_panel.php'; ?>
        <?php break;
endswitch; ?>
</div>
<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
</body>
</html>
