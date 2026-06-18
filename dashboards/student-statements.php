<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
$ctx = bootstrapStaffDashboard(['bursar', 'finance', 'accountant']);
$staffDb = $ctx['staff'];
$pageTitle = 'Student Statements';

$totalOutstanding = $paidThisMonth = $overdueAccounts = $fullyPaid = 0;
$statements = [];
if ($staffDb) {
    try {
        $r = $staffDb->query("SELECT COALESCE(SUM(balance),0) as t FROM fee_balance WHERE balance>0");
        if ($r) $totalOutstanding = (float)$r->fetch_assoc()['t'];
        $r = $staffDb->query("SELECT COALESCE(SUM(amount_paid),0) as t FROM fee_collections WHERE MONTH(payment_date)=MONTH(CURDATE()) AND YEAR(payment_date)=YEAR(CURDATE())");
        if ($r) $paidThisMonth = (float)$r->fetch_assoc()['t'];
        $r = $staffDb->query("SELECT COUNT(*) as c FROM fee_balance WHERE balance>0 AND due_date<CURDATE()");
        if ($r) $overdueAccounts = (int)$r->fetch_assoc()['c'];
        $r = $staffDb->query("SELECT COUNT(*) as c FROM fee_balance WHERE balance<=0");
        if ($r) $fullyPaid = (int)$r->fetch_assoc()['c'];
        $r = $staffDb->query("SELECT s.student_id, CONCAT(s.first_name,' ',s.last_name) as student_name, COALESCE(sf.total_fees,0) as total_fees, COALESCE(sf.amount_paid,0) as amount_paid, COALESCE(sf.balance,0) as balance, CASE WHEN COALESCE(sf.balance,0)<=0 THEN 'Fully Paid' WHEN sf.due_date<CURDATE() THEN 'Overdue' ELSE 'Pending' END as status FROM students s LEFT JOIN fee_balance sf ON s.student_id=sf.student_id ORDER BY sf.balance DESC LIMIT 100");
        if (!$r) $r = $staffDb->query("SELECT f.student_id, CONCAT(s.first_name,' ',s.last_name) as student_name, f.total_fees, f.amount_paid, f.balance, f.status FROM student_fees f LEFT JOIN students s ON f.student_id=s.student_id ORDER BY f.balance DESC LIMIT 100");
        if ($r) while ($row = $r->fetch_assoc()) $statements[] = $row;
    } catch (Exception $e) {}
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include_once __DIR__ . '/../includes/dashboard_head.php'; ?>
</head>
<body>
<?php include_once __DIR__ . '/../includes/sidebar.php'; ?>
<div class="main" style="margin-left:270px;padding:32px">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold mb-0"><i class="fas fa-file-invoice me-2"></i>Student Statements</h4>
        <span class="text-muted small"><?= date('l, d M Y') ?></span>
    </div>
    <div class="stats-grid">
        <div class="stat-card warning"><div class="stat-icon"><i class="fas fa-exclamation-circle"></i></div><div class="stat-content"><h3><?= number_format($totalOutstanding) ?></h3><p>Total Outstanding</p></div></div>
        <div class="stat-card success"><div class="stat-icon"><i class="fas fa-check-circle"></i></div><div class="stat-content"><h3><?= number_format($paidThisMonth) ?></h3><p>Paid This Month</p></div></div>
        <div class="stat-card info"><div class="stat-icon"><i class="fas fa-clock"></i></div><div class="stat-content"><h3><?= $overdueAccounts ?></h3><p>Overdue Accounts</p></div></div>
        <div class="stat-card primary"><div class="stat-icon"><i class="fas fa-check-double"></i></div><div class="stat-content"><h3><?= $fullyPaid ?></h3><p>Fully Paid</p></div></div>
    </div>
    <div class="content-section">
        <h5 class="fw-bold mb-3"><i class="fas fa-list me-2"></i>Fee Balances</h5>
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead><tr><th>Student Name</th><th>Total Fees</th><th>Amount Paid</th><th>Balance</th><th>Status</th></tr></thead>
                <tbody>
                    <?php if (empty($statements)): ?>
                    <tr><td colspan="5" class="text-center text-muted">No fee records found.</td></tr>
                    <?php else: ?>
                    <?php foreach ($statements as $st): ?>
                    <tr>
                        <td><?= htmlspecialchars($st['student_name'] ?? $st['student_id'] ?? '') ?></td>
                        <td><?= htmlspecialchars(number_format((float)($st['total_fees']??0))) ?></td>
                        <td><?= htmlspecialchars(number_format((float)($st['amount_paid']??0))) ?></td>
                        <td><?= htmlspecialchars(number_format((float)($st['balance']??0))) ?></td>
                        <td><span class="badge bg-<?= (($st['status']??'')==='Fully Paid'||($st['status']??'')==='fully_paid')?'success':(($st['status']??'')==='Overdue'||($st['status']??'')==='overdue'?'danger':'warning') ?>"><?= htmlspecialchars(ucwords(str_replace('_',' ',$st['status']??'Pending'))) ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
</body>
</html>