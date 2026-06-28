<?php
$pageTitle = 'Performance Appraisal';
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
$ctx = bootstrapStaffDashboard(['hr','manager','director','principal','head']);
$conn = $ctx['staff'];
$user = $ctx['user'];

$totalAppraisals = 0; $pending = 0; $completed = 0; $avgScore = 0;
$appraisals = [];

if ($conn) {
    $r1 = $conn->query("SELECT COUNT(*) c FROM staff_appraisals WHERE YEAR(created_at)=YEAR(NOW())");
    if ($r1) $totalAppraisals = (int)$r1->fetch_assoc()['c'];
    $r2 = $conn->query("SELECT COUNT(*) c FROM staff_appraisals WHERE status='Pending'");
    if ($r2) $pending = (int)$r2->fetch_assoc()['c'];
    $r3 = $conn->query("SELECT COUNT(*) c FROM staff_appraisals WHERE status='Completed'");
    if ($r3) $completed = (int)$r3->fetch_assoc()['c'];
    $r4 = $conn->query("SELECT AVG(score) a FROM staff_appraisals WHERE score IS NOT NULL");
    if ($r4 && $row = $r4->fetch_assoc()) $avgScore = round((float)$row['a'], 1);
    $a = $conn->query("SELECT a.*, COALESCE(s.full_name, a.staff_name) staff_name FROM staff_appraisals a LEFT JOIN staff s ON a.staff_id=s.id ORDER BY a.created_at DESC LIMIT 50");
    if ($a) $appraisals = $a->fetch_all(MYSQLI_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include_once __DIR__ . '/../includes/dashboard_head.php'; ?>
</head>
<body>
<?php include_once __DIR__ . '/../includes/sidebar.php'; ?>
<div class="main" style="margin-left:270px;padding:32px;">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold mb-0"><i class="fas fa-chart-line me-2"></i>Performance Appraisal</h4>
        <span class="text-muted small"><?= date('l, d M Y') ?></span>
    </div>
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="stat-card primary">
                <div class="stat-icon"><i class="fas fa-clipboard-list"></i></div>
                <div class="stat-content"><h3><?= number_format($totalAppraisals) ?></h3><p>This Year</p></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card warning">
                <div class="stat-icon"><i class="fas fa-hourglass-half"></i></div>
                <div class="stat-content"><h3><?= number_format($pending) ?></h3><p>Pending</p></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card success">
                <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
                <div class="stat-content"><h3><?= number_format($completed) ?></h3><p>Completed</p></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card info">
                <div class="stat-icon"><i class="fas fa-star"></i></div>
                <div class="stat-content"><h3><?= $avgScore ?></h3><p>Avg Score</p></div>
            </div>
        </div>
    </div>
    <div class="content-section">
        <h5 class="fw-bold mb-3"><i class="fas fa-users me-2"></i>Appraisal Records</h5>
        <?php if (empty($appraisals)): ?>
        <div class="text-center py-4 text-muted"><i class="fas fa-database fa-2x mb-2"></i><p class="mb-0">No appraisal records found.</p></div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle">
                <thead class="table-light">
                    <tr><th>Staff Name</th><th>Period</th><th>Score</th><th>Rating</th><th>Status</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($appraisals as $ap): ?>
                    <tr>
                        <td><?= htmlspecialchars($ap['staff_name'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($ap['appraisal_period'] ?? '-') ?></td>
                        <td><?= $ap['score'] !== null ? number_format((float)$ap['score'], 1) : '-' ?></td>
                        <td>
                            <?php if ($ap['rating']): ?>
                            <span class="badge bg-<?= $ap['rating'] === 'Excellent' ? 'success' : ($ap['rating'] === 'Good' ? 'info' : ($ap['rating'] === 'Satisfactory' ? 'warning' : 'danger')) ?>"><?= htmlspecialchars($ap['rating']) ?></span>
                            <?php else: ?>-<?php endif; ?>
                        </td>
                        <td><span class="badge bg-<?= ($ap['status'] ?? 'Pending') === 'Completed' ? 'success' : 'warning text-dark' ?>"><?= htmlspecialchars($ap['status'] ?? 'Pending') ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
</body>
</html>
