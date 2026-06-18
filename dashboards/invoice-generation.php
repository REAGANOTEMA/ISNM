<?php
require_once __DIR__ . '/../includes/config_enhanced.php';
$conn = getStaffConnection();
$pageTitle = 'Invoice Generation';
$total = 0; $paid = 0; $pending = 0; $overdue = 0; $records = [];
if ($conn) {
    $r = $conn->query("SELECT COUNT(*) c FROM invoices");
    if ($r) $total = (int)$r->fetch_assoc()['c'];
    $r = $conn->query("SELECT COUNT(*) c FROM invoices WHERE status='paid'");
    if ($r) $paid = (int)$r->fetch_assoc()['c'];
    $r = $conn->query("SELECT COUNT(*) c FROM invoices WHERE status='pending'");
    if ($r) $pending = (int)$r->fetch_assoc()['c'];
    $r = $conn->query("SELECT COUNT(*) c FROM invoices WHERE status='overdue'");
    if ($r) $overdue = (int)$r->fetch_assoc()['c'];
    $q = $conn->query("SELECT i.invoice_number, CONCAT(s.first_name,' ',s.surname) student_name, i.amount, i.due_date, i.status FROM invoices i LEFT JOIN students s ON i.student_id=s.id ORDER BY i.created_at DESC LIMIT 50");
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
<h4 class="fw-bold mb-0"><i class="fas fa-file-invoice me-2"></i>Invoice Generation</h4>
<span class="text-muted small"><?= date('l, d M Y') ?></span>
</div>
<div class="row g-3 mb-4">
<?php $c=[['Total Invoices',$total,'primary','file-invoice'],['Paid',$paid,'success','check-double'],['Pending',$pending,'warning','clock'],['Overdue',$overdue,'danger','exclamation-triangle']]; foreach($c as $s): ?>
<div class="col-md-3">
<div class="stat-card <?= $s[2] ?>">
<div class="stat-icon"><i class="fas fa-<?= $s[3] ?>"></i></div>
<div class="stat-content"><h3><?= number_format($s[1]) ?></h3><p><?= $s[0] ?></p></div>
</div>
</div>
<?php endforeach; ?>
</div>
<div class="content-section">
<h5 class="fw-bold mb-3"><i class="fas fa-list me-2"></i>Invoices</h5>
<div class="table-responsive">
<table class="table table-striped table-hover align-middle">
<thead class="table-light"><tr><th>Invoice #</th><th>Student</th><th>Amount</th><th>Due Date</th><th>Status</th></tr></thead>
<tbody>
<?php if(empty($records)): ?>
<tr><td colspan="5" class="text-center text-muted py-3">No invoices found.</td></tr>
<?php else: foreach($records as $r):
$st=$r['status']??'';
$bc=$st==='paid'?'bg-success':($st==='pending'?'bg-warning text-dark':($st==='overdue'?'bg-danger':($st==='partial'?'bg-info':($st==='cancelled'?'bg-secondary':'bg-dark'))));
?>
<tr><td><code><?= htmlspecialchars($r['invoice_number']??'-') ?></code></td><td><?= htmlspecialchars($r['student_name']??'-') ?></td><td><?= number_format((float)($r['amount']??0),2) ?></td><td><?= htmlspecialchars($r['due_date']??'-') ?></td><td><span class="badge <?= $bc ?>"><?= htmlspecialchars($st) ?></span></td></tr>
<?php endforeach; endif; ?>
</tbody>
</table>
</div>
</div>
</div>
</main>
<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
</body>
