<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
$ctx = bootstrapStaffDashboard(['director', 'principal', 'deputy', 'matron', 'warden', 'secretary']);
$conn = $ctx['staff'];
$user = $ctx['user'];

$sessions = [];
$r = $conn->query("SELECT * FROM counseling_sessions ORDER BY session_date DESC LIMIT 100");
if ($r) while ($row = $r->fetch_assoc()) $sessions[] = $row;

$welfare = [];
$r2 = $conn->query("SELECT * FROM student_welfare_cases ORDER BY created_at DESC LIMIT 100");
if ($r2) while ($row = $r2->fetch_assoc()) $welfare[] = $row;

$pageTitle = 'Counseling & Student Welfare';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include_once __DIR__ . '/../includes/dashboard_head.php'; ?>
</head>
<body>
<?php include_once __DIR__ . '/../includes/sidebar.php'; ?>
<div class="page-content">
    <div class="content-header">
        <h1><i class="fas fa-hand-holding-heart"></i> Counseling & Student Welfare</h1>
    </div>
    <div class="row mb-4">
        <div class="col-md-4"><div class="card"><div class="card-body"><h6>Counseling Sessions</h6><h3><?= count($sessions) ?></h3></div></div></div>
        <div class="col-md-4"><div class="card"><div class="card-body"><h6>Welfare Cases</h6><h3><?= count($welfare) ?></h3></div></div></div>
        <div class="col-md-4"><div class="card"><div class="card-body"><h6>Open Welfare</h6><h3><?= count(array_filter($welfare, fn($w) => ($w['status'] ?? '') === 'open')) ?></h3></div></div></div>
    </div>
    <div class="row">
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header"><h5>Recent Counseling Sessions</h5></div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead><tr><th>Student</th><th>Type</th><th>Counselor</th><th>Date</th><th>Status</th></tr></thead>
                            <tbody>
                                <?php foreach ($sessions as $s): ?>
                                <tr>
                                    <td><?= htmlspecialchars($s['student_name'] ?? $s['student_id'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($s['session_type'] ?? $s['type'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($s['counselor_name'] ?? $s['counselor'] ?? '-') ?></td>
                                    <td><?= $s['session_date'] ?? $s['created_at'] ?? '-' ?></td>
                                    <td><span class="badge bg-<?= ($s['status'] ?? 'completed') === 'completed' ? 'success' : 'warning' ?>"><?= $s['status'] ?? 'completed' ?></span></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if (empty($sessions)): ?><tr><td colspan="5" class="text-center">No counseling sessions recorded</td></tr><?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header"><h5>Welfare Cases</h5></div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead><tr><th>Student</th><th>Issue</th><th>Priority</th><th>Status</th><th>Date</th></tr></thead>
                            <tbody>
                                <?php foreach ($welfare as $w): ?>
                                <tr>
                                    <td><?= htmlspecialchars($w['student_name'] ?? $w['student_id'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($w['issue_description'] ?? $w['issue'] ?? '-') ?></td>
                                    <td><span class="badge bg-<?= ($w['priority'] ?? 'normal') === 'high' ? 'danger' : (($w['priority'] ?? '') === 'medium' ? 'warning' : 'info') ?>"><?= $w['priority'] ?? 'normal' ?></span></td>
                                    <td><span class="badge bg-<?= ($w['status'] ?? 'open') === 'resolved' ? 'success' : 'danger' ?>"><?= $w['status'] ?? 'open' ?></span></td>
                                    <td><?= $w['created_at'] ?? '-' ?></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if (empty($welfare)): ?><tr><td colspan="5" class="text-center">No welfare cases</td></tr><?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
</body>
</html>
