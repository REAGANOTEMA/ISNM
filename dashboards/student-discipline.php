<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
$ctx = bootstrapStaffDashboard(['director','principal','deputy','secretary','matron','warden','head']);
$staffDb = $ctx['staff'];
$pageTitle = 'Student Discipline';

$totalCases = $openCases = $resolvedCases = $warningCases = 0;
$records = [];
if ($staffDb) {
    try {
        $r = $staffDb->query("SELECT COUNT(*) as c FROM disciplinary_records");
        if ($r) $totalCases = (int)$r->fetch_assoc()['c'];
        $r = $staffDb->query("SELECT COUNT(*) as c FROM disciplinary_records WHERE status='open'");
        if ($r) $openCases = (int)$r->fetch_assoc()['c'];
        $r = $staffDb->query("SELECT COUNT(*) as c FROM disciplinary_records WHERE status='resolved'");
        if ($r) $resolvedCases = (int)$r->fetch_assoc()['c'];
        $r = $staffDb->query("SELECT COUNT(*) as c FROM disciplinary_records WHERE action_taken LIKE '%warning%'");
        if ($r) $warningCases = (int)$r->fetch_assoc()['c'];
        $r = $staffDb->query("SELECT d.*, CONCAT(s.first_name,' ',s.surname) as student_name FROM disciplinary_records d LEFT JOIN igangaschoolofl_students_db.students s ON d.student_id=s.id ORDER BY d.date DESC LIMIT 100");
        if ($r) while ($row = $r->fetch_assoc()) $records[] = $row;
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
        <h4 class="fw-bold mb-0"><i class="fas fa-gavel me-2"></i>Student Discipline</h4>
        <span class="text-muted small"><?= date('l, d M Y') ?></span>
    </div>
    <div class="stats-grid">
        <div class="stat-card primary"><div class="stat-icon"><i class="fas fa-folder-open"></i></div><div class="stat-content"><h3><?= $totalCases ?></h3><p>Total Cases</p></div></div>
        <div class="stat-card warning"><div class="stat-icon"><i class="fas fa-clock"></i></div><div class="stat-content"><h3><?= $openCases ?></h3><p>Open</p></div></div>
        <div class="stat-card success"><div class="stat-icon"><i class="fas fa-check-double"></i></div><div class="stat-content"><h3><?= $resolvedCases ?></h3><p>Resolved</p></div></div>
        <div class="stat-card info"><div class="stat-icon"><i class="fas fa-exclamation-triangle"></i></div><div class="stat-content"><h3><?= $warningCases ?></h3><p>Warning Issued</p></div></div>
    </div>
    <div class="content-section">
        <h5 class="fw-bold mb-3"><i class="fas fa-list me-2"></i>Disciplinary Cases</h5>
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead><tr><th>Student</th><th>Offense</th><th>Description</th><th>Action Taken</th><th>Date</th><th>Status</th></tr></thead>
                <tbody>
                    <?php if (empty($records)): ?>
                    <tr><td colspan="6" class="text-center text-muted">No disciplinary records found.</td></tr>
                    <?php else: ?>
                    <?php foreach ($records as $d): ?>
                    <tr>
                        <td><?= htmlspecialchars($d['student_name'] ?? $d['student_id'] ?? '') ?></td>
                        <td><?= htmlspecialchars($d['offense'] ?? '') ?></td>
                        <td><?= htmlspecialchars(mb_substr($d['description'] ?? '', 0, 60)) ?></td>
                        <td><?= htmlspecialchars($d['action_taken'] ?? '') ?></td>
                        <td><?= htmlspecialchars($d['date'] ?? '') ?></td>
                        <td><span class="badge bg-<?= ($d['status']??'')==='resolved'?'success':(($d['status']??'')==='open'?'warning':'secondary') ?>"><?= htmlspecialchars(ucfirst($d['status'] ?? 'Unknown')) ?></span></td>
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