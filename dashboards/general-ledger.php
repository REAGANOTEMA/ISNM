<?php
$pageTitle = 'General Ledger';
require_once __DIR__ . '/../includes/config_enhanced.php';
$conn = getStaffConnection();

$totalAccounts = 0; $activeAccounts = 0; $periodEntries = 0;
$accounts = []; $types = [];

if ($conn) {
    $r1 = $conn->query("SELECT COUNT(*) c FROM chart_of_accounts");
    if ($r1) $totalAccounts = (int)$r1->fetch_assoc()['c'];
    $r2 = $conn->query("SELECT COUNT(*) c FROM chart_of_accounts WHERE status='Active'");
    if ($r2) $activeAccounts = (int)$r2->fetch_assoc()['c'];
    $r3 = $conn->query("SELECT COUNT(*) c FROM accounting_periods WHERE NOW() BETWEEN start_date AND end_date");
    if ($r3 && $row = $r3->fetch_assoc()) $periodEntries = (int)$row['c'];
    $a = $conn->query("SELECT account_code, account_name, account_type, balance, status FROM chart_of_accounts ORDER BY FIELD(account_type,'asset','liability','equity','revenue','expense'), account_name");
    if ($a) {
        while ($row = $a->fetch_assoc()) {
            $types[$row['account_type']][] = $row;
            $accounts[] = $row;
        }
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
<div class="main" style="margin-left:270px;padding:32px;">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold mb-0"><i class="fas fa-book me-2"></i>General Ledger</h4>
        <span class="text-muted small"><?= date('l, d M Y') ?></span>
    </div>
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="stat-card primary">
                <div class="stat-icon"><i class="fas fa-folder"></i></div>
                <div class="stat-content"><h3><?= number_format($totalAccounts) ?></h3><p>Total Accounts</p></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card success">
                <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
                <div class="stat-content"><h3><?= number_format($activeAccounts) ?></h3><p>Active</p></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card info">
                <div class="stat-icon"><i class="fas fa-calendar-week"></i></div>
                <div class="stat-content"><h3><?= number_format($periodEntries) ?></h3><p>Period Entries</p></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card warning">
                <div class="stat-icon"><i class="fas fa-layer-group"></i></div>
                <div class="stat-content"><h3><?= count($types) ?></h3><p>Account Types</p></div>
            </div>
        </div>
    </div>
    <?php if (empty($accounts)): ?>
    <div class="content-section text-center py-5 text-muted">
        <i class="fas fa-database fa-2x mb-2"></i>
        <p class="mb-0">No chart of accounts found.</p>
    </div>
    <?php else: foreach (['asset','liability','equity','revenue','expense'] as $type): if (empty($types[$type])) continue; ?>
    <div class="content-section">
        <h5 class="fw-bold mb-3 text-uppercase" style="color:var(--isnm-blue)"><i class="fas fa-tag me-2"></i><?= htmlspecialchars(ucfirst($type)) ?>s</h5>
        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle">
                <thead class="table-light">
                    <tr><th>Code</th><th>Account Name</th><th>Type</th><th>Balance (UGX)</th><th>Status</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($types[$type] as $acct): ?>
                    <tr>
                        <td><code><?= htmlspecialchars($acct['account_code']) ?></code></td>
                        <td><?= htmlspecialchars($acct['account_name']) ?></td>
                        <td><?= htmlspecialchars(ucfirst($acct['account_type'])) ?></td>
                        <td><?= number_format((float)($acct['balance'] ?? 0)) ?></td>
                        <td><span class="badge bg-<?= ($acct['status'] ?? 'Active') === 'Active' ? 'success' : 'secondary' ?>"><?= htmlspecialchars($acct['status'] ?? 'Active') ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endforeach; endif; ?>
</div>
<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
</body>
</html>
