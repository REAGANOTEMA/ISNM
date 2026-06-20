<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
$ctx = bootstrapStaffDashboard(['ceo', 'director']);
$conn = $ctx['staff'];
$students_conn = $ctx['students'];
$user = $ctx['user'];
$user_name = $user['full_name'] ?? 'CEO';

$stats = [
    'students' => 0, 'staff' => 0, 'programs' => 0, 'revenue' => 0
];
if ($students_conn) {
    $r = $students_conn->query("SELECT COUNT(*) c FROM students"); if ($r) $stats['students'] = (int)$r->fetch_assoc()['c'];
}
if ($conn) {
    $r = $conn->query("SELECT COUNT(*) c FROM staff"); if ($r) $stats['staff'] = (int)$r->fetch_assoc()['c'];
    $r = $conn->query("SELECT COUNT(*) c FROM academic_programs WHERE status='Active'"); if ($r) $stats['programs'] = (int)$r->fetch_assoc()['c'];
    $r = $conn->query("SELECT COALESCE(SUM(amount_paid),0) total FROM payments"); if ($r) $stats['revenue'] = (float)$r->fetch_assoc()['total'];
}

$recent_students = [];
if ($students_conn) {
    $r = $students_conn->query("SELECT id, first_name, surname, program, status FROM students ORDER BY id DESC LIMIT 10");
    if ($r) $recent_students = $r->fetch_all(MYSQLI_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head><?php include_once __DIR__ . '/../includes/dashboard_head.php'; ?></head>
<body>
<?php include_once __DIR__ . '/../includes/sidebar.php'; ?>
<div class="page-content">
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
        <div class="card-header"><h5>Recently Enrolled Students</h5></div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead><tr><th>#</th><th>Name</th><th>Program</th><th>Status</th></tr></thead>
                    <tbody>
                        <?php if (empty($recent_students)): ?><tr><td colspan="4" class="text-muted text-center">No data</td></tr><?php endif; ?>
                        <?php foreach ($recent_students as $i => $s): ?>
                        <tr><td><?= $i+1 ?></td><td><?= htmlspecialchars($s['first_name'].' '.$s['surname']) ?></td><td><?= htmlspecialchars($s['program'] ?? '-') ?></td><td><span class="badge bg-success"><?= htmlspecialchars($s['status']) ?></span></td></tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
</body>
</html>
