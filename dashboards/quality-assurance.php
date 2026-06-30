<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
$ctx = bootstrapStaffDashboard(['director', 'academics', 'principal', 'head']);
$conn = $ctx['staff'];
$user = $ctx['user'];

$qa = [];
if ($conn) {
    $r = $conn->query("SELECT * FROM quality_assurance ORDER BY created_at DESC LIMIT 50");
    if ($r) while ($row = $r->fetch_assoc()) $qa[] = $row;
}
$indicators = [];
if ($conn) {
    $r = $conn->query("SELECT * FROM performance_indicators WHERE status='active' ORDER BY indicator_category, indicator_name");
    if ($r) while ($row = $r->fetch_assoc()) $indicators[] = $row;
}
$pageTitle = 'Quality Assurance';
?><!DOCTYPE html>
<html lang="en">
<head><?php include_once __DIR__ . '/../includes/dashboard_head.php'; ?>
</head>
<body>
<?php include_once __DIR__ . '/../includes/sidebar.php'; ?>
<?php include_once __DIR__ . '/../includes/dashboard_topbar.php'; ?>
<div class="main" style="margin-left:270px;padding:32px">
<div class="page-title-card"><h2><i class="fas fa-check-circle me-2"></i>Quality Assurance</h2><p>Monitor quality standards, accreditation, compliance, and performance indicators</p></div>
<div class="card mb-4"><div class="card-header">Quality Assessments</div><div class="card-body">
<?php if (empty($qa)): ?><div class="empty-state"><i class="fas fa-clipboard-check"></i><p>No quality assurance records yet.</p></div>
<?php else: ?>
<div class="table-responsive"><table class="table table-hover"><thead><tr><th>Type</th><th>Title</th><th>Department</th><th>Period</th><th>Status</th><th>Date</th></tr></thead><tbody>
<?php foreach ($qa as $q): ?>
<tr><td><span class="badge bg-info"><?= htmlspecialchars($q['assessment_type']??'') ?></span></td><td><?= htmlspecialchars(mb_substr($q['title']??'', 0, 50)) ?></td><td class="small"><?= htmlspecialchars($q['department']??'') ?></td><td class="small"><?= htmlspecialchars($q['assessment_period']??'') ?></td><td><span class="status-pill <?= ($q['status']??'') === 'Completed' ? 'success' : (($q['status']??'') === 'In Progress' ? 'warning' : 'info') ?>"><?= htmlspecialchars($q['status']??'Scheduled') ?></span></td><td class="small"><?= htmlspecialchars($q['created_at']??'') ?></td></tr>
<?php endforeach; ?>
</tbody></table></div>
<?php endif; ?>
</div></div>
<div class="card"><div class="card-header">Active Performance Indicators (<?= count($indicators) ?>)</div><div class="card-body">
<?php if (!empty($indicators)): ?>
<div class="row g-2"><?php foreach ($indicators as $ind): ?>
<div class="col-md-4 col-6"><div class="border rounded p-2 small bg-light"><?= htmlspecialchars($ind['indicator_name']) ?><br><span class="text-muted"><?= htmlspecialchars($ind['indicator_category']??'') ?> | Target: <?= htmlspecialchars($ind['target_value']??'N/A') ?></span></div></div>
<?php endforeach; ?></div>
<?php else: ?><p class="text-muted small text-center py-3">No active indicators.</p><?php endif; ?>
</div></div></div>
<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
</body></html>
