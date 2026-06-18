<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
$ctx = bootstrapStaffDashboard([]);
$user = $ctx['user'];
$conn = getStaffConnection();
$pageTitle = 'Bank Reconciliation';

$totalAccounts = 0; $pending = 0; $reconciledThisMonth = 0;
$transactions = [];
$accounts = [];
if ($conn) {
    $totalAccounts = $conn->query("SELECT COUNT(*) c FROM bank_accounts WHERE is_active=1")->fetch_assoc()['c'] ?? 0;
    $pending = $conn->query("SELECT COUNT(*) c FROM bank_reconciliations WHERE status='draft'")->fetch_assoc()['c'] ?? 0;
    $reconciledThisMonth = $conn->query("SELECT COUNT(*) c FROM bank_reconciliations WHERE status='completed' AND MONTH(reconciliation_date)=MONTH(CURDATE()) AND YEAR(reconciliation_date)=YEAR(CURDATE())")->fetch_assoc()['c'] ?? 0;
    $r = $conn->query("SELECT * FROM bank_accounts WHERE is_active=1 ORDER BY bank_name");
    if ($r) while ($row = $r->fetch_assoc()) $accounts[] = $row;
    $r2 = $conn->query("SELECT * FROM cashbook ORDER BY transaction_date DESC LIMIT 50");
    if ($r2) while ($row = $r2->fetch_assoc()) $transactions[] = $row;
}
?><!DOCTYPE html>
<html lang="en">
<head>
<?php include_once __DIR__ . '/../includes/dashboard_head.php'; ?>
</head>
<body>
<?php include_once __DIR__ . '/../includes/sidebar.php'; ?>
<div class="main" style="margin-left:270px;padding:32px">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold mb-0"><i class="fas fa-university me-2"></i>Bank Reconciliation</h4>
    <span class="text-muted small"><?= date('l, d M Y') ?></span>
  </div>
  <div class="row g-3 mb-4">
    <div class="col-md-4"><div class="stat-card primary"><div class="stat-icon"><i class="fas fa-university"></i></div><div class="stat-content"><h3><?= $totalAccounts ?></h3><p>Active Accounts</p></div></div></div>
    <div class="col-md-4"><div class="stat-card warning"><div class="stat-icon"><i class="fas fa-clock"></i></div><div class="stat-content"><h3><?= $pending ?></h3><p>Pending Reconciliations</p></div></div></div>
    <div class="col-md-4"><div class="stat-card success"><div class="stat-icon"><i class="fas fa-check-double"></i></div><div class="stat-content"><h3><?= $reconciledThisMonth ?></h3><p>Reconciled This Month</p></div></div></div>
  </div>
  <div class="row g-4">
    <div class="col-md-4">
      <div class="content-section">
        <h5 class="fw-bold mb-3"><i class="fas fa-list me-2"></i>Bank Accounts</h5>
        <?php if (empty($accounts)): ?><p class="text-muted small">No accounts configured.</p><?php else: foreach ($accounts as $a): ?>
        <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-2">
          <div><strong><?= htmlspecialchars($a['account_name']) ?></strong><br><small class="text-muted"><?= htmlspecialchars($a['account_number']) ?></small></div>
          <span class="badge bg-primary"><?= htmlspecialchars(number_format($a['current_balance'], 2)) ?></span>
        </div>
        <?php endforeach; endif; ?>
      </div>
    </div>
    <div class="col-md-8">
      <div class="content-section">
        <h5 class="fw-bold mb-3"><i class="fas fa-exchange-alt me-2"></i>Recent Transactions</h5>
        <div class="table-responsive">
          <table class="table table-striped table-hover">
            <thead class="table-dark"><tr><th>Date</th><th>Description</th><th>Ref No</th><th>Amount</th><th>Type</th></tr></thead>
            <tbody><?php if (empty($transactions)): ?><tr><td colspan="5" class="text-muted text-center py-3">No transactions found.</td></tr><?php else: foreach ($transactions as $t): ?><tr><td><?= date('d M Y', strtotime($t['transaction_date'])) ?></td><td><?= htmlspecialchars($t['description'] ?? '-') ?></td><td><code><?= htmlspecialchars($t['reference_number'] ?? '-') ?></code></td><td><?= number_format(max($t['debit_amount'] ?? 0, $t['credit_amount'] ?? 0), 2) ?></td><td><span class="badge <?= ($t['transaction_type']??'')==='cash_in'?'bg-success':'bg-danger' ?>"><?= htmlspecialchars(($t['transaction_type']??'')==='cash_in'?'Credit':'Debit') ?></span></td></tr><?php endforeach; endif; ?></tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
</body>
</html>