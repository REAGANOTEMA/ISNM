<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
$ctx = bootstrapStaffDashboard(['director', 'bursar', 'finance', 'ceo']);
$conn = $ctx['staff'];
$websiteConn = $ctx['website'];
$user = $ctx['user'];

$pageTitle = 'Donations & Fundraising';

$donations = [];
foreach ([$websiteConn, $conn] as $db) {
    if (!$db) continue;
    $r = $db->query("SELECT * FROM donations ORDER BY donation_date DESC LIMIT 100");
    if ($r && $r->num_rows) { while ($row = $r->fetch_assoc()) $donations[] = $row; break; }
}

$totalDonations = count($donations);
$totalAmount = array_sum(array_column($donations, 'amount'));
$thisMonth = 0;
foreach ($donations as $d) {
    if (substr($d['donation_date'] ?? $d['created_at'] ?? '', 0, 7) === date('Y-m')) {
        $thisMonth += (float)($d['amount'] ?? 0);
    }
}
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
        <h1><i class="fas fa-donate"></i> Donations & Fundraising</h1>
    </div>
    <div class="row mb-4">
        <div class="col-md-4"><div class="card"><div class="card-body"><h6>Total Donations</h6><h3><?= $totalDonations ?></h3></div></div></div>
        <div class="col-md-4"><div class="card"><div class="card-body"><h6>Total Amount</h6><h3><?= number_format($totalAmount, 0) ?></h3></div></div></div>
        <div class="col-md-4"><div class="card"><div class="card-body"><h6>This Month</h6><h3><?= number_format($thisMonth, 0) ?></h3></div></div></div>
    </div>
    <div class="card">
        <div class="card-header"><h5>Donation Records</h5></div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead><tr><th>Donor</th><th>Amount</th><th>Payment Method</th><th>Purpose</th><th>Date</th><th>Status</th></tr></thead>
                    <tbody>
                        <?php foreach ($donations as $d): ?>
                        <tr>
                            <td><?= htmlspecialchars($d['donor_name'] ?? $d['name'] ?? $d['full_name'] ?? 'Anonymous') ?></td>
                            <td><strong><?= number_format($d['amount'] ?? 0, 0) ?></strong></td>
                            <td><?= htmlspecialchars($d['payment_method'] ?? $d['method'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($d['purpose'] ?? $d['notes'] ?? '-') ?></td>
                            <td><?= $d['donation_date'] ?? $d['created_at'] ?? '-' ?></td>
                            <td><span class="badge bg-<?= ($d['status'] ?? 'completed') === 'completed' ? 'success' : 'warning' ?>"><?= $d['status'] ?? 'completed' ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($donations)): ?><tr><td colspan="6" class="text-center">No donations recorded</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
</body>
</html>
