<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
$ctx = bootstrapStaffDashboard([]);
$user = $ctx['user'];
$conn = getStaffConnection();
$pageTitle = 'Financial Reports';

$revenue = 0; $expensesTotal = 0; $netIncome = 0; $pendingInvoices = 0;
$monthly = [];
$recentTransactions = [];
if ($conn) {
    $q1 = $conn->query("SELECT COALESCE(SUM(amount_received),0) c FROM payments WHERE status IN('approved','verified')"); $revenue = $q1 ? (int)($q1->fetch_assoc()['c'] ?? 0) : 0;
    $q2 = $conn->query("SELECT COALESCE(SUM(amount),0) c FROM expenses WHERE status IN('approved','paid')"); $expensesTotal = $q2 ? (float)($q2->fetch_assoc()['c'] ?? 0) : 0;
    $q3 = $conn->query("SELECT COUNT(*) c FROM invoice_records WHERE status IN('Sent','Partial','Draft')"); $pendingInvoices = $q3 ? (int)($q3->fetch_assoc()['c'] ?? 0) : 0;
    $netIncome = $revenue - $expensesTotal;
    $r = $conn->query("SELECT DATE_FORMAT(p.payment_date,'%Y-%m') month, COALESCE(SUM(p.amount_received),0) payments, 0 expenses FROM payments p WHERE p.status IN('approved','verified') GROUP BY month UNION ALL SELECT DATE_FORMAT(e.expense_date,'%Y-%m') month, 0 payments, COALESCE(SUM(e.amount),0) expenses FROM expenses e WHERE e.status IN('approved','paid') GROUP BY month ORDER BY month DESC LIMIT 12");
    if ($r) {
        $map = [];
        while ($row = $r->fetch_assoc()) {
            $m = $row['month'];
            if (!isset($map[$m])) $map[$m] = ['month'=>$m, 'payments'=>0, 'expenses'=>0];
            $map[$m]['payments'] += (float)$row['payments'];
            $map[$m]['expenses'] += (float)$row['expenses'];
        }
        $monthly = array_values($map);
    }
    $r2 = $conn->query("(SELECT 'Payment' type, p.payment_reference ref, p.amount_received amount, p.payment_date date, 'Payment' category, p.status FROM payments p WHERE p.status IN('approved','verified') LIMIT 25) UNION ALL (SELECT 'Expense' type, e.expense_id ref, e.amount amount, e.expense_date date, e.expense_category category, e.status FROM expenses e WHERE e.status IN('approved','paid') LIMIT 25) ORDER BY date DESC LIMIT 30");
    if ($r2) while ($row = $r2->fetch_assoc()) $recentTransactions[] = $row;
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
    <h4 class="fw-bold mb-0"><i class="fas fa-chart-pie me-2"></i>Financial Reports</h4>
    <span class="text-muted small"><?= date('l, d M Y') ?></span>
  </div>
  <div class="row g-3 mb-4">
    <div class="col-md-3"><div class="stat-card success"><div class="stat-icon"><i class="fas fa-arrow-up"></i></div><div class="stat-content"><h3><?= number_format($revenue, 2) ?></h3><p>Total Revenue</p></div></div></div>
    <div class="col-md-3"><div class="stat-card danger"><div class="stat-icon"><i class="fas fa-arrow-down"></i></div><div class="stat-content"><h3><?= number_format($expensesTotal, 2) ?></h3><p>Total Expenses</p></div></div></div>
    <div class="col-md-3"><div class="stat-card <?= $netIncome >= 0 ? 'primary' : 'warning' ?>"><div class="stat-icon"><i class="fas fa-balance-scale"></i></div><div class="stat-content"><h3><?= number_format($netIncome, 2) ?></h3><p>Net Income</p></div></div></div>
    <div class="col-md-3"><div class="stat-card info"><div class="stat-icon"><i class="fas fa-file-invoice"></i></div><div class="stat-content"><h3><?= $pendingInvoices ?></h3><p>Pending Invoices</p></div></div></div>
  </div>
  <div class="row g-4">
    <div class="col-md-7">
      <div class="content-section">
        <h5 class="fw-bold mb-3"><i class="fas fa-list me-2"></i>Recent Transactions</h5>
        <div class="table-responsive">
          <table class="table table-striped table-hover">
            <thead class="table-dark"><tr><th>Description</th><th>Amount</th><th>Date</th><th>Category</th><th>Status</th></tr></thead>
            <tbody><?php if (empty($recentTransactions)): ?><tr><td colspan="5" class="text-muted text-center py-3">No transactions found.</td></tr><?php else: foreach ($recentTransactions as $t): ?><tr><td><?= htmlspecialchars($t['ref']) ?></td><td><?= number_format($t['amount'], 2) ?></td><td><?= date('d M Y', strtotime($t['date'])) ?></td><td><span class="badge <?= $t['type']==='Payment'?'bg-success':'bg-danger' ?>"><?= htmlspecialchars($t['category']) ?></span></td><td><span class="badge bg-primary"><?= htmlspecialchars($t['status']) ?></span></td></tr><?php endforeach; endif; ?></tbody>
          </table>
        </div>
      </div>
    </div>
    <div class="col-md-5">
      <div class="content-section">
        <h5 class="fw-bold mb-3"><i class="fas fa-calendar-alt me-2"></i>Monthly Summary</h5>
        <div class="table-responsive">
          <table class="table table-striped table-hover">
            <thead class="table-dark"><tr><th>Month</th><th>Revenue</th><th>Expenses</th><th>Net</th></tr></thead>
            <tbody><?php if (empty($monthly)): ?><tr><td colspan="4" class="text-muted text-center py-3">No data yet.</td></tr><?php else: foreach ($monthly as $m): $net = $m['payments'] - $m['expenses']; ?><tr><td><strong><?= htmlspecialchars($m['month']) ?></strong></td><td class="text-success"><?= number_format($m['payments'], 2) ?></td><td class="text-danger"><?= number_format($m['expenses'], 2) ?></td><td class="<?= $net >= 0 ? 'text-success' : 'text-danger' ?>"><?= number_format($net, 2) ?></td></tr><?php endforeach; endif; ?></tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
</body>
</html>