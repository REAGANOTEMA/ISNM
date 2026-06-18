<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
$ctx = bootstrapStaffDashboard(['registrar', 'admissions', 'admin']);
$staffDb = $ctx['staff'];
$pageTitle = 'Student Management';

$totalStudents = $activeStudents = $newThisYear = $graduated = 0;
$students = [];
if ($staffDb) {
    try {
        $r = $staffDb->query("SELECT COUNT(*) as c FROM students");
        if ($r) $totalStudents = (int)$r->fetch_assoc()['c'];
        $r = $staffDb->query("SELECT COUNT(*) as c FROM students WHERE status='active'");
        if ($r) $activeStudents = (int)$r->fetch_assoc()['c'];
        $r = $staffDb->query("SELECT COUNT(*) as c FROM students WHERE YEAR(created_at)=YEAR(CURDATE())");
        if ($r) $newThisYear = (int)$r->fetch_assoc()['c'];
        $r = $staffDb->query("SELECT COUNT(*) as c FROM students WHERE status='graduated'");
        if ($r) $graduated = (int)$r->fetch_assoc()['c'];
        $r = $staffDb->query("SELECT student_id,first_name,middle_name,last_name,program,year_of_study,status FROM students ORDER BY student_id DESC LIMIT 100");
        if ($r) while ($row = $r->fetch_assoc()) $students[] = $row;
    } catch (Exception $e) {}
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include_once __DIR__ . '/../includes/dashboard_head.php'; ?>
</head>
<body>
<?php include_once __DIR__ . '/../includes/sidebar.php'; ?>
<div class="main" style="margin-left:270px;padding:32px">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold mb-0"><i class="fas fa-user-plus me-2"></i>Student Management</h4>
        <span class="text-muted small"><?= date('l, d M Y') ?></span>
    </div>
    <div class="stats-grid">
        <div class="stat-card primary"><div class="stat-icon"><i class="fas fa-users"></i></div><div class="stat-content"><h3><?= $totalStudents ?></h3><p>Total Students</p></div></div>
        <div class="stat-card success"><div class="stat-icon"><i class="fas fa-check-circle"></i></div><div class="stat-content"><h3><?= $activeStudents ?></h3><p>Active</p></div></div>
        <div class="stat-card info"><div class="stat-icon"><i class="fas fa-calendar-plus"></i></div><div class="stat-content"><h3><?= $newThisYear ?></h3><p>New This Year</p></div></div>
        <div class="stat-card warning"><div class="stat-icon"><i class="fas fa-graduation-cap"></i></div><div class="stat-content"><h3><?= $graduated ?></h3><p>Graduated</p></div></div>
    </div>
    <div class="content-section">
        <h5 class="fw-bold mb-3"><i class="fas fa-list me-2"></i>Student Records</h5>
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead><tr><th>Student #</th><th>Full Name</th><th>Program</th><th>Year</th><th>Status</th></tr></thead>
                <tbody>
                    <?php if (empty($students)): ?>
                    <tr><td colspan="5" class="text-center text-muted">No records found.</td></tr>
                    <?php else: ?>
                    <?php foreach ($students as $s): ?>
                    <tr>
                        <td><?= htmlspecialchars($s['student_id'] ?? '') ?></td>
                        <td><?= htmlspecialchars(trim(($s['first_name']??'').' '.($s['middle_name']??'').' '.($s['last_name']??''))) ?></td>
                        <td><?= htmlspecialchars($s['program'] ?? '') ?></td>
                        <td><?= htmlspecialchars($s['year_of_study'] ?? '') ?></td>
                        <td><span class="badge bg-<?= ($s['status']??'')==='active'?'success':(($s['status']??'')==='graduated'?'info':'secondary') ?>"><?= htmlspecialchars(ucfirst($s['status'] ?? 'Unknown')) ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
</body>
</html>