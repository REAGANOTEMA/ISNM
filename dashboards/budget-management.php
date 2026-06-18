<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
$ctx = bootstrapStaffDashboard([]);
$conn = getStaffConnection();
$pageTitle = 'Budget Management';

$budgets = [];
$totalBudgets = $activeBudgets = $draftBudgets = $completedBudgets = 0;
if ($conn) {
    $r = $conn->query("SELECT * FROM budget ORDER BY created_at DESC LIMIT 50");
    if ($r) {
        while ($row = $r->fetch_assoc()) $budgets[] = $row;
    }
    $totalBudgets = count($budgets);
    foreach ($budgets as $b) {
        $s = strtolower($b['status'] ?? '');
        if ($s === 'active') $activeBudgets++;
        elseif ($s === 'draft') $draftBudgets++;
        elseif ($s === 'completed') $completedBudgets++;
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
        <h4 class="fw-bold mb-0"><i class="fas fa-calculator me-2"></i>Budget Management</h4>
        <span class="text-muted small"><?= date('l, d M Y') ?></span>
    </div>
    <div class="stats-grid">
        <div class="stat-card primary">
            <div class="stat-icon"><i class="fas fa-coins"></i></div>
            <div class="stat-content"><h3><?= $totalBudgets ?></h3><p>Total Budgets</p></div>
        </div>
        <div class="stat-card success">
            <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
            <div class="stat-content"><h3><?= $activeBudgets ?></h3><p>Active</p></div>
        </div>
        <div class="stat-card warning">
            <div class="stat-icon"><i class="fas fa-pen"></i></div>
            <div class="stat-content"><h3><?= $draftBudgets ?></h3><p>Draft</p></div>
        </div>
        <div class="stat-card info">
            <div class="stat-icon"><i class="fas fa-flag-checkered"></i></div>
            <div class="stat-content"><h3><?= $completedBudgets ?></h3><p>Completed</p></div>
        </div>
    </div>
    <div class="content-section">
        <h5 class="fw-bold mb-3"><i class="fas fa-list me-2"></i>Budget Records</h5>
        <?php if (!empty($budgets)): ?>
        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle">
                <thead class="table-light">
                    <tr><th>Budget Name</th><th>Fiscal Year</th><th>Total Amount (UGX)</th><th>Spent Amount (UGX)</th><th>Status</th><th>Created At</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($budgets as $b): ?>
                    <tr>
                        <td><?= htmlspecialchars($b['budget_name'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($b['fiscal_year'] ?? '-') ?></td>
                        <td><?= number_format((float)($b['total_amount'] ?? 0), 2) ?></td>
                        <td><?= number_format((float)($b['spent_amount'] ?? 0), 2) ?></td>
                        <td><span class="badge bg-<?php $s = strtolower($b['status'] ?? ''); echo $s === 'active' ? 'success' : ($s === 'completed' ? 'info' : ($s === 'draft' ? 'warning' : 'secondary')); ?>"><?= htmlspecialchars($b['status'] ?? 'Unknown') ?></span></td>
                        <td><?= htmlspecialchars($b['created_at'] ?? '-') ?></td>
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