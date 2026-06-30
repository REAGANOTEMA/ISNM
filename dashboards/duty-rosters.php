<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
$ctx = bootstrapStaffDashboard(['hr', 'manager', 'director', 'head', 'matron', 'warden']);
$conn = $ctx['staff'];
$user = $ctx['user'];

$pageTitle = 'Duty Rosters & Scheduling';

$rosters = [];
$r = $conn->query("SELECT * FROM duty_rosters ORDER BY roster_date DESC LIMIT 100");
if ($r) while ($row = $r->fetch_assoc()) $rosters[] = $row;

if (empty($rosters)) {
    $r = $conn->query("SELECT * FROM duty_roster ORDER BY date DESC LIMIT 100");
    if ($r) while ($row = $r->fetch_assoc()) $rosters[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include_once __DIR__ . '/../includes/dashboard_head.php'; ?>
</head>
<body>
<?php include_once __DIR__ . '/../includes/sidebar.php'; ?>
<?php include_once __DIR__ . '/../includes/dashboard_topbar.php'; ?>
<div class="page-content">
    <div class="content-header">
        <h1><i class="fas fa-calendar-alt"></i> Duty Rosters & Scheduling</h1>
    </div>
    <div class="row mb-4">
        <div class="col-md-3"><div class="card"><div class="card-body"><h6>Total Rosters</h6><h3><?= count($rosters) ?></h3></div></div></div>
    </div>
    <div class="card">
        <div class="card-header"><h5>Duty Rosters</h5></div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead><tr><th>Staff Name</th><th>Role/Shift</th><th>Date</th><th>Location</th><th>Status</th></tr></thead>
                    <tbody>
                        <?php foreach ($rosters as $r): ?>
                        <tr>
                            <td><?= htmlspecialchars($r['staff_name'] ?? $r['name'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($r['shift'] ?? $r['role'] ?? $r['duty_type'] ?? '-') ?></td>
                            <td><?= $r['roster_date'] ?? $r['date'] ?? '-' ?></td>
                            <td><?= htmlspecialchars($r['location'] ?? $r['department'] ?? '-') ?></td>
                            <td><span class="badge bg-<?= ($r['status'] ?? 'scheduled') === 'completed' ? 'success' : 'primary' ?>"><?= $r['status'] ?? 'scheduled' ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($rosters)): ?><tr><td colspan="5" class="text-center">No duty rosters found</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
</body>
</html>
