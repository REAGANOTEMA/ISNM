<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
$ctx = bootstrapStaffDashboard([]);
$conn = getStaffConnection();
$pageTitle = 'Expenditure Tracking';

$expenses = [];
$totalMonth = $approvedCount = $pendingCount = $rejectedCount = 0;
if ($conn) {
    $r = $conn->query("SELECT * FROM expenses ORDER BY expense_date DESC LIMIT 50");
    if ($r) {
        while ($row = $r->fetch_assoc()) $expenses[] = $row;
    }
    $r = $conn->query("SELECT COALESCE(SUM(amount), 0) as total FROM expenses WHERE MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())");
    if ($r) $totalMonth = (float)$r->fetch_assoc()['total'];
    foreach ($expenses as $e) {
        $s = strtolower($e['status'] ?? '');
        if ($s === 'approved') $approvedCount++;
        elseif ($s === 'pending') $pendingCount++;
        elseif ($s === 'rejected') $rejectedCount++;
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
<main class="main" style="margin-left:270px;padding:32px;">
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold mb-0"><i class="fas fa-shopping-cart me-2"></i>Expenditure Tracking</h4>
        <span class="text-muted small"><?= date('l, d M Y') ?></span>
    </div>
    <div class="stats-grid">
        <div class="stat-card primary">
            <div class="stat-icon"><i class="fas fa-calendar-week"></i></div>
            <div class="stat-content"><h3>UGX <?= number_format($totalMonth, 2) ?></h3><p>Total This Month</p></div>
        </div>
        <div class="stat-card success">
            <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
            <div class="stat-content"><h3><?= $approvedCount ?></h3><p>Approved</p></div>
        </div>
        <div class="stat-card warning">
            <div class="stat-icon"><i class="fas fa-clock"></i></div>
            <div class="stat-content"><h3><?= $pendingCount ?></h3><p>Pending</p></div>
        </div>
        <div class="stat-card" style="--card-yellow-accent:#ef4444;--card-yellow:#fef2f2;--card-chocolate-accent:#ef4444;--card-chocolate:#fef2f2;">
            <div class="stat-icon" style="background:linear-gradient(135deg,#dc2626,#ef4444);"><i class="fas fa-times-circle"></i></div>
            <div class="stat-content"><h3><?= $rejectedCount ?></h3><p>Rejected</p></div>
        </div>
    </div>
    <div class="content-section">
        <h5 class="fw-bold mb-3"><i class="fas fa-list me-2"></i>Expense Records</h5>
        <?php if (!empty($expenses)): ?>
        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle">
                <thead class="table-light">
                    <tr><th>Expense Title</th><th>Amount (UGX)</th><th>Category</th><th>Requested By</th><th>Date</th><th>Status</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($expenses as $e): ?>
                    <tr>
                        <td><?= htmlspecialchars($e['expense_title'] ?? $e['title'] ?? '-') ?></td>
                        <td><?= number_format((float)($e['amount'] ?? 0), 2) ?></td>
                        <td><?= htmlspecialchars($e['category'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($e['requested_by'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($e['expense_date'] ?? $e['date'] ?? '-') ?></td>
                        <td><span class="badge bg-<?php $s = strtolower($e['status'] ?? ''); echo $s === 'approved' || $s === 'paid' ? 'success' : ($s === 'pending' ? 'warning' : ($s === 'rejected' ? 'danger' : 'secondary')); ?>"><?= htmlspecialchars($e['status'] ?? 'Unknown') ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="text-center py-4 text-muted">
            <i class="fas fa-database fa-2x mb-2"></i>
            <p class="mb-0">No records found.</p>
        </div>
        <?php endif; ?>
    </div>
</div>
</main>
<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
</body>
</html>