<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
$ctx = bootstrapStaffDashboard(['director', 'finance', 'bursar', 'accountant']);
$conn = $ctx['staff'];
$user = $ctx['user'];

$audits = [];
if ($conn) {
    $r = $conn->query("SELECT staff_name, role_name, action, category, description, ip_address, created_at FROM audit_trail ORDER BY created_at DESC LIMIT 100");
    if ($r) while ($row = $r->fetch_assoc()) $audits[] = $row;
}
$fin_audits = [];
if ($conn) {
    $r = $conn->query("SELECT action_type, table_name, record_id, user_id, user_role, created_at FROM financial_audit_log ORDER BY created_at DESC LIMIT 50");
    if ($r) while ($row = $r->fetch_assoc()) $fin_audits[] = $row;
}
$pageTitle = 'Audit Management';
?><!DOCTYPE html>
<html lang="en">
<head><?php include_once __DIR__ . '/../includes/dashboard_head.php'; ?></head>
<body>
<?php include_once __DIR__ . '/../includes/sidebar.php'; ?>
<div class="main" style="margin-left:270px;padding:32px">
<div class="page-title-card"><h2><i class="fas fa-search me-2"></i>Audit Management</h2><p>System audit trails, financial audit logs, and compliance monitoring</p></div>
<div class="row g-4">
<div class="col-lg-6"><div class="card"><div class="card-header">Activity Audit Trail</div><div class="card-body" style="max-height:400px;overflow-y:auto">
<?php if (empty($audits)): ?><div class="empty-state"><i class="fas fa-history"></i><p>No audit records.</p></div>
<?php else: ?>
<div class="table-responsive"><table class="table table-sm"><thead><tr><th>Staff</th><th>Action</th><th>Category</th><th>Description</th><th>Time</th></tr></thead><tbody>
<?php foreach ($audits as $a): ?>
<tr><td class="small"><?= htmlspecialchars($a['staff_name']??$a['role_name']??'') ?></td><td><span class="badge bg-secondary"><?= htmlspecialchars($a['action']??'') ?></span></td><td><span class="badge bg-info"><?= htmlspecialchars($a['category']??'') ?></span></td><td class="small text-muted"><?= htmlspecialchars(mb_substr($a['description']??'', 0, 60)) ?></td><td class="small text-nowrap"><?= htmlspecialchars($a['created_at']??'') ?></td></tr>
<?php endforeach; ?>
</tbody></table></div>
<?php endif; ?>
</div></div></div>
<div class="col-lg-6"><div class="card"><div class="card-header">Financial Audit Log</div><div class="card-body" style="max-height:400px;overflow-y:auto">
<?php if (empty($fin_audits)): ?><div class="empty-state"><i class="fas fa-coins"></i><p>No financial audit records.</p></div>
<?php else: ?>
<div class="table-responsive"><table class="table table-sm"><thead><tr><th>Action</th><th>Table</th><th>Record</th><th>User</th><th>Date</th></tr></thead><tbody>
<?php foreach ($fin_audits as $f): ?>
<tr><td><span class="badge bg-secondary"><?= htmlspecialchars($f['action_type']??'') ?></span></td><td class="small"><?= htmlspecialchars($f['table_name']??'') ?></td><td class="small">#<?= htmlspecialchars($f['record_id']??'') ?></td><td class="small"><?= htmlspecialchars($f['user_role']??$f['user_id']??'') ?></td><td class="small text-nowrap"><?= htmlspecialchars($f['created_at']??'') ?></td></tr>
<?php endforeach; ?>
</tbody></table></div>
<?php endif; ?>
</div></div></div></div>
</div>
<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
</body></html>
