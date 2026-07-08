<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
$ctx = bootstrapStaffDashboard(['driver', 'director', 'manager']);
$conn = $ctx['staff'];
$user = $ctx['user'];

$pageTitle = 'Fuel & Trip Management';

$fuel = []; $trips = []; $vehicles = [];
if ($conn) {
    $r = $conn->query("SELECT f.*, v.vehicle_name FROM fuel_management f LEFT JOIN vehicles v ON f.vehicle_id=v.id ORDER BY f.fueling_date DESC LIMIT 100");
    if ($r) while ($row = $r->fetch_assoc()) $fuel[] = $row;
    $r2 = $conn->query("SELECT t.*, v.vehicle_name, s.full_name AS driver_name FROM trip_logs t LEFT JOIN vehicles v ON t.vehicle_id=v.id LEFT JOIN staff s ON t.driver_id=s.id ORDER BY t.trip_date DESC LIMIT 100");
    if ($r2) while ($row = $r2->fetch_assoc()) $trips[] = $row;
    $r3 = $conn->query("SELECT * FROM vehicles ORDER BY vehicle_name");
    if ($r3) while ($row = $r3->fetch_assoc()) $vehicles[] = $row;
}

$totalFuel = count($fuel);
$totalTrips = count($trips);
$totalVehicles = count($vehicles);
$fuelCost = array_sum(array_column($fuel, 'total_cost'));
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
        <h1><i class="fas fa-truck"></i> Fuel & Trip Management</h1>
    </div>
    <div class="row mb-4">
        <div class="col-md-3"><div class="card"><div class="card-body"><h6>Vehicles</h6><h3><?= $totalVehicles ?></h3></div></div></div>
        <div class="col-md-3"><div class="card"><div class="card-body"><h6>Fuel Records</h6><h3><?= $totalFuel ?></h3></div></div></div>
        <div class="col-md-3"><div class="card"><div class="card-body"><h6>Trips</h6><h3><?= $totalTrips ?></h3></div></div></div>
        <div class="col-md-3"><div class="card"><div class="card-body"><h6>Fuel Cost</h6><h3><?= number_format($fuelCost, 0) ?></h3></div></div></div>
    </div>
    <div class="row">
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header"><h5>Vehicles</h5></div>
                <div class="card-body">
                    <ul class="list-group">
                        <?php foreach ($vehicles as $v): ?>
                        <li class="list-group-item d-flex justify-content-between">
                            <?= htmlspecialchars($v['vehicle_name'] ?? $v['license_plate'] ?? '-') ?>
                            <span class="badge bg-<?= ($v['status'] ?? 'Available') === 'Available' ? 'success' : 'secondary' ?>"><?= $v['status'] ?? 'Available' ?></span>
                        </li>
                        <?php endforeach; ?>
                        <?php if (empty($vehicles)): ?><li class="list-group-item text-center">No vehicles</li><?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header"><h5>Recent Fuel Records</h5></div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead><tr><th>Vehicle</th><th>Liters</th><th>Cost</th><th>Date</th></tr></thead>
                            <tbody>
                                <?php foreach (array_slice($fuel, 0, 10) as $f): ?>
                                <tr>
                                    <td><?= htmlspecialchars($f['vehicle_name'] ?? $f['vehicle'] ?? '-') ?></td>
                                    <td><?= number_format($f['fuel_quantity'] ?? 0, 1) ?></td>
                                    <td><?= number_format($f['total_cost'] ?? 0, 0) ?></td>
                                    <td><?= $f['fueling_date'] ?? $f['created_at'] ?? '-' ?></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if (empty($fuel)): ?><tr><td colspan="4" class="text-center">No fuel records</td></tr><?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header"><h5>Recent Trips</h5></div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead><tr><th>Vehicle</th><th>Driver</th><th>Destination</th><th>Date</th></tr></thead>
                            <tbody>
                                <?php foreach (array_slice($trips, 0, 10) as $t): ?>
                                <tr>
                                    <td><?= htmlspecialchars($t['vehicle_name'] ?? $t['vehicle'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($t['driver_name'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($t['route_name'] ?? $t['end_location'] ?? '-') ?></td>
                                    <td><?= $t['trip_date'] ?? $t['created_at'] ?? '-' ?></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if (empty($trips)): ?><tr><td colspan="4" class="text-center">No trip records</td></tr><?php endif; ?>
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
