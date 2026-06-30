<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
$ctx = bootstrapStaffDashboard(['bursar', 'finance', 'director', 'registrar', 'secretary']);
$conn = $ctx['staff'];
$studentsConn = $ctx['students'];
$user = $ctx['user'];

$pageTitle = 'Scholarships & Sponsorships';

$scholarships = [];
foreach ([$conn, $studentsConn] as $db) {
    if (!$db) continue;
    $r = $db->query("SELECT * FROM scholarships ORDER BY created_at DESC LIMIT 100");
    if ($r && $r->num_rows) { while ($row = $r->fetch_assoc()) $scholarships[] = $row; break; }
}

$sponsorships = [];
foreach ([$conn, $studentsConn] as $db) {
    if (!$db) continue;
    $r = $db->query("SELECT * FROM sponsorships ORDER BY created_at DESC LIMIT 100");
    if ($r && $r->num_rows) { while ($row = $r->fetch_assoc()) $sponsorships[] = $row; break; }
}

$totalScholarships = count($scholarships);
$totalSponsorships = count($sponsorships);
$activeScholarships = count(array_filter($scholarships, fn($s) => ($s['status'] ?? '') === 'active'));
$activeSponsorships = count(array_filter($sponsorships, fn($s) => ($s['status'] ?? '') === 'active'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include_once __DIR__ . '/../includes/dashboard_head.php'; ?>
</head>
<body>
<?php include_once __DIR__ . '/../includes/sidebar.php'; ?>
<?php include_once __DIR__ . '/../includes/dashboard_topbar.php'; ?>
<div class="page-content">
    <div class="content-header">
        <h1><i class="fas fa-trophy"></i> Scholarships & Sponsorships</h1>
    </div>
    <div class="row mb-4">
        <div class="col-md-3"><div class="card"><div class="card-body"><h6>Scholarships</h6><h3><?= $totalScholarships ?></h3></div></div></div>
        <div class="col-md-3"><div class="card"><div class="card-body"><h6>Active Scholarships</h6><h3><?= $activeScholarships ?></h3></div></div></div>
        <div class="col-md-3"><div class="card"><div class="card-body"><h6>Sponsorships</h6><h3><?= $totalSponsorships ?></h3></div></div></div>
        <div class="col-md-3"><div class="card"><div class="card-body"><h6>Active Sponsorships</h6><h3><?= $activeSponsorships ?></h3></div></div></div>
    </div>
    <div class="row">
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header"><h5>Scholarships</h5></div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead><tr><th>Name</th><th>Provider</th><th>Amount</th><th>Status</th></tr></thead>
                            <tbody>
                                <?php foreach ($scholarships as $s): ?>
                                <tr>
                                    <td><?= htmlspecialchars($s['scholarship_name'] ?? $s['name'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($s['provider'] ?? $s['sponsor'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($s['amount'] ?? $s['value'] ?? '-') ?></td>
                                    <td><span class="badge bg-<?= ($s['status'] ?? 'active') === 'active' ? 'success' : 'secondary' ?>"><?= $s['status'] ?? 'active' ?></span></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if (empty($scholarships)): ?><tr><td colspan="4" class="text-center">No scholarships</td></tr><?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header"><h5>Sponsorships</h5></div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead><tr><th>Sponsor</th><th>Student</th><th>Amount</th><th>Status</th></tr></thead>
                            <tbody>
                                <?php foreach ($sponsorships as $s): ?>
                                <tr>
                                    <td><?= htmlspecialchars($s['sponsor_name'] ?? $s['sponsor'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($s['student_name'] ?? $s['student_id'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($s['amount'] ?? $s['value'] ?? '-') ?></td>
                                    <td><span class="badge bg-<?= ($s['status'] ?? 'active') === 'active' ? 'success' : 'secondary' ?>"><?= $s['status'] ?? 'active' ?></span></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if (empty($sponsorships)): ?><tr><td colspan="4" class="text-center">No sponsorships</td></tr><?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
</body>
</html>
