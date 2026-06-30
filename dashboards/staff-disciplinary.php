<?php
$pageTitle = 'Staff Disciplinary';
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
bootstrapStaffDashboard(['hr','manager','director','principal']);
require_once __DIR__ . '/../includes/config_enhanced.php';
$conn = getStaffConnection();

$totalCases = 0; $openCases = 0; $resolved = 0; $thisMonth = 0;
$cases = [];

if ($conn) {
    $r1 = $conn->query("SELECT COUNT(*) c FROM disciplinary_actions");
    if ($r1) $totalCases = (int)$r1->fetch_assoc()['c'];
    $r2 = $conn->query("SELECT COUNT(*) c FROM disciplinary_actions WHERE status='Open'");
    if ($r2) $openCases = (int)$r2->fetch_assoc()['c'];
    $r3 = $conn->query("SELECT COUNT(*) c FROM disciplinary_actions WHERE status='Resolved'");
    if ($r3) $resolved = (int)$r3->fetch_assoc()['c'];
    $r4 = $conn->query("SELECT COUNT(*) c FROM disciplinary_actions WHERE MONTH(incident_date)=MONTH(NOW()) AND YEAR(incident_date)=YEAR(NOW())");
    if ($r4) $thisMonth = (int)$r4->fetch_assoc()['c'];
    $c = $conn->query("SELECT d.*, s.full_name staff_name FROM disciplinary_actions d LEFT JOIN staff s ON d.staff_id=s.id ORDER BY d.incident_date DESC LIMIT 50");
    if ($c) $cases = $c->fetch_all(MYSQLI_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include_once __DIR__ . '/../includes/dashboard_head.php'; ?>
</head>
<body>
<?php include_once __DIR__ . '/../includes/sidebar.php'; ?>
<?php include_once __DIR__ . '/../includes/dashboard_topbar.php'; ?>
<div class="main" style="margin-left:270px;padding:32px;">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold mb-0"><i class="fas fa-gavel me-2"></i>Staff Disciplinary</h4>
        <span class="text-muted small"><?= date('l, d M Y') ?></span>
    </div>
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="stat-card primary">
                <div class="stat-icon"><i class="fas fa-folder-open"></i></div>
                <div class="stat-content"><h3><?= number_format($totalCases) ?></h3><p>Total Cases</p></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card warning">
                <div class="stat-icon"><i class="fas fa-exclamation-triangle"></i></div>
                <div class="stat-content"><h3><?= number_format($openCases) ?></h3><p>Open</p></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card success">
                <div class="stat-icon"><i class="fas fa-check-double"></i></div>
                <div class="stat-content"><h3><?= number_format($resolved) ?></h3><p>Resolved</p></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card info">
                <div class="stat-icon"><i class="fas fa-calendar-month"></i></div>
                <div class="stat-content"><h3><?= number_format($thisMonth) ?></h3><p>This Month</p></div>
            </div>
        </div>
    </div>
    <div class="content-section">
        <h5 class="fw-bold mb-3"><i class="fas fa-list me-2"></i>Disciplinary Cases</h5>
        <?php if (empty($cases)): ?>
        <div class="text-center py-4 text-muted"><i class="fas fa-database fa-2x mb-2"></i><p class="mb-0">No disciplinary records found.</p></div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle">
                <thead class="table-light">
                    <tr><th>Staff Name</th><th>Incident Date</th><th>Offense Type</th><th>Description</th><th>Action Taken</th><th>Status</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($cases as $dc): ?>
                    <tr>
                        <td><?= htmlspecialchars($dc['staff_name'] ?? '-') ?></td>
                        <td><?= !empty($dc['incident_date']) ? date('d M Y', strtotime($dc['incident_date'])) : '-' ?></td>
                        <td><?= htmlspecialchars($dc['offense_type'] ?? '-') ?></td>
                        <td><small><?= htmlspecialchars(mb_substr($dc['description'] ?? '-', 0, 60)) ?><?= strlen($dc['description'] ?? '') > 60 ? '...' : '' ?></small></td>
                        <td><?= htmlspecialchars($dc['action_taken'] ?? '-') ?></td>
                        <td><span class="badge bg-<?= ($dc['status'] ?? 'Open') === 'Resolved' ? 'success' : 'danger' ?>"><?= htmlspecialchars($dc['status'] ?? 'Open') ?></span></td>
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
