<?php
$pageTitle = 'Recruitment';
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
bootstrapStaffDashboard(['hr','manager','director','principal']);
require_once __DIR__ . '/../includes/config_enhanced.php';
$conn = getStaffConnection();

$openPositions = 0; $totalApplicants = 0; $shortlisted = 0; $hiredThisMonth = 0;
$positions = [];

if ($conn) {
    $r1 = $conn->query("SELECT COUNT(*) c FROM recruitment WHERE status='Open'");
    if ($r1) $openPositions = (int)$r1->fetch_assoc()['c'];
    $r2 = $conn->query("SELECT COUNT(*) c FROM job_applications");
    if ($r2) $totalApplicants = (int)$r2->fetch_assoc()['c'];
    $r3 = $conn->query("SELECT COUNT(*) c FROM job_applications WHERE status='Shortlisted'");
    if ($r3) $shortlisted = (int)$r3->fetch_assoc()['c'];
    $r4 = $conn->query("SELECT COUNT(*) c FROM job_applications WHERE status='Hired' AND MONTH(updated_at)=MONTH(NOW()) AND YEAR(updated_at)=YEAR(NOW())");
    if ($r4) $hiredThisMonth = (int)$r4->fetch_assoc()['c'];
    $p = $conn->query("SELECT r.*, (SELECT COUNT(*) FROM job_applications ja WHERE ja.position_id=r.id) applicants_count FROM recruitment r ORDER BY r.posted_date DESC LIMIT 50");
    if ($p) $positions = $p->fetch_all(MYSQLI_ASSOC);
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
        <h4 class="fw-bold mb-0"><i class="fas fa-user-tie me-2"></i>Recruitment</h4>
        <span class="text-muted small"><?= date('l, d M Y') ?></span>
    </div>
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="stat-card primary">
                <div class="stat-icon"><i class="fas fa-briefcase"></i></div>
                <div class="stat-content"><h3><?= number_format($openPositions) ?></h3><p>Open Positions</p></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card info">
                <div class="stat-icon"><i class="fas fa-users"></i></div>
                <div class="stat-content"><h3><?= number_format($totalApplicants) ?></h3><p>Total Applicants</p></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card warning">
                <div class="stat-icon"><i class="fas fa-filter"></i></div>
                <div class="stat-content"><h3><?= number_format($shortlisted) ?></h3><p>Shortlisted</p></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card success">
                <div class="stat-icon"><i class="fas fa-user-check"></i></div>
                <div class="stat-content"><h3><?= number_format($hiredThisMonth) ?></h3><p>Hired This Month</p></div>
            </div>
        </div>
    </div>
    <div class="content-section">
        <h5 class="fw-bold mb-3"><i class="fas fa-list me-2"></i>Open Positions</h5>
        <?php if (empty($positions)): ?>
        <div class="text-center py-4 text-muted"><i class="fas fa-database fa-2x mb-2"></i><p class="mb-0">No recruitment positions found.</p></div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle">
                <thead class="table-light">
                    <tr><th>Position Title</th><th>Department</th><th>Applicants</th><th>Status</th><th>Posted Date</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($positions as $pos): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($pos['position_title'] ?? $pos['title'] ?? '-') ?></strong></td>
                        <td><?= htmlspecialchars($pos['department'] ?? '-') ?></td>
                        <td><?= (int)($pos['applicants_count'] ?? 0) ?></td>
                        <td><span class="badge bg-<?= ($pos['status'] ?? 'Open') === 'Open' ? 'success' : 'secondary' ?>"><?= htmlspecialchars($pos['status'] ?? 'Open') ?></span></td>
                        <td><?= !empty($pos['posted_date']) ? date('d M Y', strtotime($pos['posted_date'])) : '-' ?></td>
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
