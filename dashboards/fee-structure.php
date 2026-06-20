<?php
require_once __DIR__ . '/../includes/config_enhanced.php';
$staffConn = getStaffConnection();
$studentsConn = getStudentsConnection();
$conn = $studentsConn ?: $staffConn;
$pageTitle = 'Fee Structure';
$programs = 0; $structures = 0; $active = 0; $archived = 0; $records = [];
if ($conn) {
    $qr = $conn->query("SELECT COUNT(*) c FROM programs"); if ($qr) $programs = (int)$qr->fetch_assoc()['c'];
    $qr = $conn->query("SELECT COUNT(*) c FROM fee_structures"); if ($qr) $structures = (int)$qr->fetch_assoc()['c'];
    $qr = $conn->query("SELECT COUNT(*) c FROM fee_structures WHERE status='Active'"); if ($qr) $active = (int)$qr->fetch_assoc()['c'];
    $q = $conn->query("SELECT p.name program_name, f.year, f.semester, f.fee_category, f.amount FROM fee_structures f JOIN programs p ON f.program_id=p.id ORDER BY p.name, f.year, f.semester");
    if ($q) $records = $q->fetch_all(MYSQLI_ASSOC);
}
$totalArchived = $archived;
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
<h4 class="fw-bold mb-0"><i class="fas fa-dollar-sign me-2"></i>Fee Structure</h4>
<span class="text-muted small"><?= date('l, d M Y') ?></span>
</div>
<div class="row g-3 mb-4">
<?php $c=[['Total Programs',$programs,'primary','graduation-cap'],['Fee Structures',$structures,'info','file-invoice-dollar'],['Active',$active,'success','check-circle'],['Archived',$totalArchived,'warning','archive']]; foreach($c as $s): ?>
<div class="col-md-3">
<div class="stat-card <?= $s[2] ?>">
<div class="stat-icon"><i class="fas fa-<?= $s[3] ?>"></i></div>
<div class="stat-content"><h3><?= number_format($s[1]) ?></h3><p><?= $s[0] ?></p></div>
</div>
</div>
<?php endforeach; ?>
</div>
<div class="content-section">
<h5 class="fw-bold mb-3"><i class="fas fa-list me-2"></i>Fee Structures</h5>
<div class="table-responsive">
<table class="table table-striped table-hover align-middle">
<thead class="table-light"><tr><th>Program</th><th>Year</th><th>Semester</th><th>Fee Category</th><th>Amount</th></tr></thead>
<tbody>
<?php if(empty($records)): ?>
<tr><td colspan="5" class="text-center text-muted py-3">No fee structures found.</td></tr>
<?php else: $gp=''; foreach($records as $r):
if($gp!==$r['program_name']): $gp=$r['program_name']; ?>
<tr class="table-info"><td colspan="5"><strong><?= htmlspecialchars($gp) ?></strong></td></tr>
<?php endif; ?>
<tr><td></td><td><?= htmlspecialchars($r['year']??'-') ?></td><td><?= htmlspecialchars($r['semester']??'-') ?></td><td><?= htmlspecialchars($r['fee_category']??'-') ?></td><td><?= number_format((float)($r['amount']??0),2) ?></td></tr>
<?php endforeach; endif; ?>
</tbody>
</table>
</div>
</div>
</div>
</main>
<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
</body>
