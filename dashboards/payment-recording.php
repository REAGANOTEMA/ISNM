<?php
require_once __DIR__ . '/../includes/config_enhanced.php';
$conn = getStaffConnection();
$pageTitle = 'Payment Recording';
$today = 0; $week = 0; $pendingVer = 0; $month = 0; $records = [];
if ($conn) {
    $r = $conn->query("SELECT COALESCE(SUM(amount),0) c FROM payments WHERE DATE(payment_date)=CURDATE() AND status='verified'");
    if ($r) $today = (float)$r->fetch_assoc()['c'];
    $r = $conn->query("SELECT COALESCE(SUM(amount),0) c FROM payments WHERE YEARWEEK(payment_date)=YEARWEEK(CURDATE()) AND status='verified'");
    if ($r) $week = (float)$r->fetch_assoc()['c'];
    $r = $conn->query("SELECT COUNT(*) c FROM payments WHERE status='pending'");
    if ($r) $pendingVer = (int)$r->fetch_assoc()['c'];
    $r = $conn->query("SELECT COALESCE(SUM(amount),0) c FROM payments WHERE MONTH(payment_date)=MONTH(CURDATE()) AND YEAR(payment_date)=YEAR(CURDATE()) AND status='verified'");
    if ($r) $month = (float)$r->fetch_assoc()['c'];
    $q = $conn->query("SELECT p.receipt_number, CONCAT(s.first_name,' ',s.surname) student_name, p.amount, p.payment_method, p.payment_date, p.status FROM payments p LEFT JOIN students s ON p.student_id=s.id ORDER BY p.payment_date DESC LIMIT 50");
    if ($q) $records = $q->fetch_all(MYSQLI_ASSOC);
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
<h4 class="fw-bold mb-0"><i class="fas fa-hand-holding-usd me-2"></i>Payment Recording</h4>
<span class="text-muted small"><?= date('l, d M Y') ?></span>
</div>
<div class="row g-3 mb-4">
<?php $c=[["Today's Coll.","UGX ".number_format($today,0),'success','cash-register'],['This Week',"UGX ".number_format($week,0),'info','calendar-week'],['Pending Verif.',$pendingVer,'warning','hourglass-half'],['This Month',"UGX ".number_format($month,0),'primary','wallet']]; foreach($c as $s): ?>
<div class="col-md-3">
<div class="stat-card <?= $s[2] ?>">
<div class="stat-icon"><i class="fas fa-<?= $s[3] ?>"></i></div>
<div class="stat-content"><h3><?= htmlspecialchars($s[1]) ?></h3><p><?= htmlspecialchars($s[0]) ?></p></div>
</div>
</div>
<?php endforeach; ?>
</div>
<div class="content-section">
<h5 class="fw-bold mb-3"><i class="fas fa-list me-2"></i>Recent Payments</h5>
<div class="table-responsive">
<table class="table table-striped table-hover align-middle">
<thead class="table-light"><tr><th>Receipt #</th><th>Student</th><th>Amount</th><th>Method</th><th>Date</th><th>Status</th></tr></thead>
<tbody>
<?php if(empty($records)): ?>
<tr><td colspan="6" class="text-center text-muted py-3">No payments found.</td></tr>
<?php else: foreach($records as $r):
$st=$r['status']??'';
$bc=$st==='verified'?'bg-success':($st==='approved'?'bg-info':($st==='pending'?'bg-warning text-dark':($st==='rejected'?'bg-danger':($st==='bounced'?'bg-secondary':'bg-dark'))));
?>
<tr><td><code><?= htmlspecialchars($r['receipt_number']??'-') ?></code></td><td><?= htmlspecialchars($r['student_name']??'-') ?></td><td><?= number_format((float)($r['amount']??0),2) ?></td><td><?= htmlspecialchars($r['payment_method']??'-') ?></td><td><?= htmlspecialchars($r['payment_date']??'-') ?></td><td><span class="badge <?= $bc ?>"><?= htmlspecialchars($st) ?></span></td></tr>
<?php endforeach; endif; ?>
</tbody>
</table>
</div>
</div>
</div>
</main>
<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
</body>
