<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
$ctx = bootstrapStaffDashboard(['matron', 'warden', 'registrar', 'director']);
$conn = $ctx['staff'];
$user = $ctx['user'];

$pageTitle = 'Meal & Accommodation Management';

$meals = [];
$r = $conn->query("SELECT * FROM meal_tracking ORDER BY meal_date DESC LIMIT 100");
if ($r) while ($row = $r->fetch_assoc()) $meals[] = $row;

$inspections = [];
$r2 = $conn->query("SELECT * FROM room_inspections ORDER BY inspection_date DESC LIMIT 100");
if ($r2) while ($row = $r2->fetch_assoc()) $inspections[] = $row;

$totalMeals = count($meals);
$totalInspections = count($inspections);
$passedInsp = count(array_filter($inspections, fn($i) => ($i['status'] ?? '') === 'passed'));
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
        <h1><i class="fas fa-utensils"></i> Meal & Accommodation Management</h1>
    </div>
    <div class="row mb-4">
        <div class="col-md-4"><div class="card"><div class="card-body"><h6>Meal Records</h6><h3><?= $totalMeals ?></h3></div></div></div>
        <div class="col-md-4"><div class="card"><div class="card-body"><h6>Room Inspections</h6><h3><?= $totalInspections ?></h3></div></div></div>
        <div class="col-md-4"><div class="card"><div class="card-body"><h6>Passed Inspections</h6><h3><?= $passedInsp ?></h3></div></div></div>
    </div>
    <div class="row">
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header"><h5>Meal Tracking</h5></div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead><tr><th>Meal Type</th><th>Menu</th><th>Prepared By</th><th>Date</th><th>Status</th></tr></thead>
                            <tbody>
                                <?php foreach ($meals as $m): ?>
                                <tr>
                                    <td><?= htmlspecialchars($m['meal_type'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($m['menu'] ?? $m['description'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($m['prepared_by'] ?? $m['cook'] ?? '-') ?></td>
                                    <td><?= $m['meal_date'] ?? $m['created_at'] ?? '-' ?></td>
                                    <td><span class="badge bg-<?= ($m['status'] ?? 'served') === 'served' ? 'success' : 'warning' ?>"><?= $m['status'] ?? 'served' ?></span></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if (empty($meals)): ?><tr><td colspan="5" class="text-center">No meal records</td></tr><?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header"><h5>Room Inspections</h5></div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead><tr><th>Room</th><th>Student</th><th>Inspector</th><th>Score</th><th>Status</th></tr></thead>
                            <tbody>
                                <?php foreach ($inspections as $i): ?>
                                <tr>
                                    <td><?= htmlspecialchars($i['room_number'] ?? $i['room'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($i['student_name'] ?? $i['student_id'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($i['inspector_name'] ?? $i['inspector'] ?? '-') ?></td>
                                    <td><?= $i['score'] ?? $i['grade'] ?? '-' ?></td>
                                    <td><span class="badge bg-<?= ($i['status'] ?? '') === 'passed' ? 'success' : (($i['status'] ?? '') === 'failed' ? 'danger' : 'warning') ?>"><?= $i['status'] ?? 'pending' ?></span></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if (empty($inspections)): ?><tr><td colspan="5" class="text-center">No room inspections</td></tr><?php endif; ?>
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
