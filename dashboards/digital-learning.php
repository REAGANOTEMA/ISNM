<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
$ctx = bootstrapStaffDashboard(['director', 'ict', 'it', 'lecturer']);
$conn = $ctx['staff'];
$user = $ctx['user'];

$resources = [];
if ($conn) {
    $r = $conn->query("SELECT * FROM library_digital_resources ORDER BY added_date DESC LIMIT 50");
    if ($r) while ($row = $r->fetch_assoc()) $resources[] = $row;
}
$labs = [];
if ($conn) {
    $r = $conn->query("SELECT * FROM skills_laboratory ORDER BY lab_name");
    if ($r) while ($row = $r->fetch_assoc()) $labs[] = $row;
}
$pageTitle = 'Digital Learning';
?><!DOCTYPE html>
<html lang="en">
<head><?php include_once __DIR__ . '/../includes/dashboard_head.php'; ?>
</head>
<body>
<?php include_once __DIR__ . '/../includes/sidebar.php'; ?>
<?php include_once __DIR__ . '/../includes/dashboard_topbar.php'; ?>
<div class="main" style="margin-left:270px;padding:32px">
<div class="page-title-card"><h2><i class="fas fa-laptop me-2"></i>Digital Learning</h2><p>Manage e-learning resources, digital content, and skills laboratory</p></div>
<div class="row g-4">
<div class="col-lg-7"><div class="card"><div class="card-header">Digital Resources (<?= count($resources) ?>)</div><div class="card-body" style="max-height:400px;overflow-y:auto">
<?php if (empty($resources)): ?><div class="empty-state"><i class="fas fa-book-open"></i><p>No digital resources uploaded yet.</p></div>
<?php else: ?>
<?php foreach ($resources as $res): ?>
<div class="d-flex justify-content-between align-items-start border-bottom pb-2 mb-2">
<div>
<strong class="small"><?= htmlspecialchars($res['title']??'') ?></strong><br>
<span class="text-muted small"><?= htmlspecialchars($res['author_creator']??'') ?></span>
<?php if (!empty($res['resource_type'])): ?><br><span class="badge bg-info mt-1"><?= htmlspecialchars($res['resource_type']) ?></span><?php endif; ?>
<?php if (!empty($res['access_level'])): ?><span class="badge bg-secondary mt-1 ms-1"><?= htmlspecialchars($res['access_level']) ?></span><?php endif; ?>
</div>
<div class="text-end small text-nowrap">
<?php if (!empty($res['publication_year'])): ?><div class="text-muted"><?= htmlspecialchars($res['publication_year']) ?></div><?php endif; ?>
<div class="text-muted"><?= htmlspecialchars($res['added_date']??'') ?></div>
</div>
</div>
<?php endforeach; ?>
<?php endif; ?>
</div></div></div>
<div class="col-lg-5"><div class="card"><div class="card-header">Skills Labs</div><div class="card-body">
<?php if (empty($labs)): ?><p class="text-muted small text-center py-3">No labs configured.</p>
<?php else: ?><?php foreach ($labs as $lab): ?>
<div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-2 small">
<div><strong><?= htmlspecialchars($lab['lab_name']) ?></strong><br><span class="text-muted"><?= htmlspecialchars($lab['location']??'') ?> &middot; Cap: <?= (int)($lab['capacity']??0) ?></span></div>
<span class="status-pill <?= ($lab['status']??'') === 'Active' ? 'success' : (($lab['status']??'') === 'Under Maintenance' ? 'warning' : 'secondary') ?>"><?= htmlspecialchars($lab['status']??'Active') ?></span>
</div>
<?php endforeach; ?><?php endif; ?>
</div></div></div></div>
</div>
<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
<script>
function filterTable(inputId, tableId) {
    var input = document.getElementById(inputId);
    var filter = input.value.toUpperCase();
    var table = document.getElementById(tableId);
    if (!table) return;
    var tr = table.getElementsByTagName("tr");
    for (var i = 1; i < tr.length; i++) {
        var td = tr[i].getElementsByTagName("td");
        var found = false;
        for (var j = 0; j < td.length; j++) {
            if (td[j] && td[j].textContent.toUpperCase().indexOf(filter) > -1) { found = true; break; }
        }
        tr[i].style.display = found ? "" : "none";
    }
}
</script>
</body></html>
