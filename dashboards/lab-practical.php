<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
$ctx = bootstrapStaffDashboard(['lecturer', 'head', 'nursing', 'midwifery', 'lab']);
$conn = $ctx['staff'];
$studentsConn = $ctx['students'];
$user = $ctx['user'];

$pageTitle = 'Lab Practical Sessions';

$sessions = [];
foreach ([$studentsConn, $conn] as $db) {
    if (!$db) continue;
    foreach (['lab_practical_sessions', 'lab_skills_sessions', 'lab_skills_demonstrations', 'skills_lab_sessions'] as $tbl) {
        $r = $db->query("SELECT * FROM $tbl ORDER BY session_date DESC LIMIT 100");
        if ($r && $r->num_rows) { while ($row = $r->fetch_assoc()) { $row['_source'] = $tbl; $sessions[] = $row; } break 2; }
    }
}

$attendance = [];
foreach ([$studentsConn, $conn] as $db) {
    if (!$db) continue;
    $r = $db->query("SELECT * FROM lab_attendance ORDER BY session_date DESC LIMIT 100");
    if ($r && $r->num_rows) { while ($row = $r->fetch_assoc()) $attendance[] = $row; break; }
}

$equipment = [];
foreach ([$studentsConn, $conn] as $db) {
    if (!$db) continue;
    $r = $db->query("SELECT * FROM lab_equipment ORDER BY equipment_name LIMIT 100");
    if ($r && $r->num_rows) { while ($row = $r->fetch_assoc()) $equipment[] = $row; break; }
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
        <h1><i class="fas fa-microscope"></i> Lab Practical Sessions</h1>
    </div>
    <div class="row mb-4">
        <div class="col-md-4"><div class="card"><div class="card-body"><h6>Practical Sessions</h6><h3><?= count($sessions) ?></h3></div></div></div>
        <div class="col-md-4"><div class="card"><div class="card-body"><h6>Attendance Records</h6><h3><?= count($attendance) ?></h3></div></div></div>
        <div class="col-md-4"><div class="card"><div class="card-body"><h6>Equipment Items</h6><h3><?= count($equipment) ?></h3></div></div></div>
    </div>
    <div class="row">
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header"><h5>Upcoming & Recent Sessions</h5></div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead><tr><th>Session</th><th>Module</th><th>Instructor</th><th>Date</th><th>Status</th></tr></thead>
                            <tbody>
                                <?php foreach ($sessions as $s): ?>
                                <tr>
                                    <td><?= htmlspecialchars($s['session_name'] ?? $s['title'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($s['module'] ?? $s['course'] ?? $s['subject'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($s['instructor'] ?? $s['facilitator'] ?? '-') ?></td>
                                    <td><?= $s['session_date'] ?? $s['created_at'] ?? '-' ?></td>
                                    <td><span class="badge bg-<?= ($s['status'] ?? 'scheduled') === 'completed' ? 'success' : 'primary' ?>"><?= $s['status'] ?? 'scheduled' ?></span></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if (empty($sessions)): ?><tr><td colspan="5" class="text-center">No sessions recorded</td></tr><?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header"><h5>Lab Equipment</h5></div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead><tr><th>Equipment</th><th>Quantity</th><th>Condition</th><th>Location</th></tr></thead>
                            <tbody>
                                <?php foreach ($equipment as $e): ?>
                                <tr>
                                    <td><?= htmlspecialchars($e['equipment_name'] ?? $e['name'] ?? '-') ?></td>
                                    <td><?= $e['quantity'] ?? $e['qty'] ?? '-' ?></td>
                                    <td><span class="badge bg-<?= ($e['condition_status'] ?? $e['status'] ?? 'good') === 'good' ? 'success' : 'warning' ?>"><?= $e['condition_status'] ?? $e['status'] ?? 'good' ?></span></td>
                                    <td><?= htmlspecialchars($e['location'] ?? $e['room'] ?? '-') ?></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if (empty($equipment)): ?><tr><td colspan="4" class="text-center">No equipment registered</td></tr><?php endif; ?>
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
