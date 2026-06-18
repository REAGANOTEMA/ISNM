<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
$ctx = bootstrapStaffDashboard(['bursar', 'finance', 'director', 'accountant']);
$conn = $ctx['staff'];
$user = $ctx['user'];

$pageTitle = 'URA / Tax Reporting';

$uraReports = [];
$r = $conn->query("SELECT * FROM ura_reports ORDER BY report_date DESC LIMIT 50");
if ($r) while ($row = $r->fetch_assoc()) $uraReports[] = $row;

if (empty($uraReports)) {
    $r = $conn->query("SELECT * FROM ura_reporting ORDER BY created_at DESC LIMIT 50");
    if ($r) while ($row = $r->fetch_assoc()) $uraReports[] = $row;
}

$totalReports = count($uraReports);
$filed = count(array_filter($uraReports, fn($u) => ($u['status'] ?? '') === 'filed'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include_once __DIR__ . '/../includes/dashboard_head.php'; ?>
</head>
<body>
<?php include_once __DIR__ . '/../includes/sidebar.php'; ?>
<div class="page-content">
    <div class="content-header">
        <h1><i class="fas fa-file-invoice-dollar"></i> URA / Tax Reporting</h1>
    </div>
    <div class="row mb-4">
        <div class="col-md-4"><div class="card"><div class="card-body"><h6>Total Reports</h6><h3><?= $totalReports ?></h3></div></div></div>
        <div class="col-md-4"><div class="card"><div class="card-body"><h6>Filed</h6><h3><?= $filed ?></h3></div></div></div>
        <div class="col-md-4"><div class="card"><div class="card-body"><h6>Pending</h6><h3><?= $totalReports - $filed ?></h3></div></div></div>
    </div>
    <div class="card">
        <div class="card-header"><h5>URA Reports</h5></div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead><tr><th>Report Name</th><th>Period</th><th>Amount</th><th>Status</th><th>Filed Date</th></tr></thead>
                    <tbody>
                        <?php foreach ($uraReports as $u): ?>
                        <tr>
                            <td><?= htmlspecialchars($u['report_name'] ?? $u['name'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($u['tax_period'] ?? $u['period'] ?? '-') ?></td>
                            <td><?= number_format($u['amount'] ?? $u['tax_amount'] ?? 0, 0) ?></td>
                            <td><span class="badge bg-<?= ($u['status'] ?? 'pending') === 'filed' ? 'success' : 'warning' ?>"><?= $u['status'] ?? 'pending' ?></span></td>
                            <td><?= $u['filed_date'] ?? $u['report_date'] ?? $u['created_at'] ?? '-' ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($uraReports)): ?><tr><td colspan="5" class="text-center">No URA reports found</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
</body>
</html>
