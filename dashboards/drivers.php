<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
require_once __DIR__ . '/../includes/enterprise_auth.php';

$ctx = bootstrapStaffDashboard(['driver']);
$auth_service = $ctx['auth'];
$conn = $ctx['staff'];
$user = $ctx['user'];
$user_id = (int) ($user['id'] ?? 0);
$user_role = $user['role'] ?? '';
$user_email = $user['email'] ?? '';
$user_name = $user['full_name'] ?? '';

// ── Page routing ──
$pageToSection = [
    'home'              => 'overview',
    'overview'          => 'overview',
    'trip-requests'     => 'trip-requests',
    'assigned-vehicles' => 'assigned-vehicles',
    'journey-planner'   => 'journey-planner',
    'fuel-requests'     => 'fuel-requests',
    'fuel-records'      => 'fuel-records',
    'mileage'           => 'mileage',
    'maintenance'       => 'maintenance',
    'repairs'           => 'repairs',
    'inspection'        => 'inspection',
    'vehicle-history'   => 'vehicle-history',
    'attendance'        => 'attendance',
    'journey-reports'   => 'journey-reports',
    'incidents'         => 'incidents',
];
$page  = $_GET['page'] ?? 'home';
$section = $pageToSection[$page] ?? 'overview';

// Get driver statistics from database
$total_trips_today = ($conn && ($q = $conn->query("SELECT COUNT(*) FROM trip_logs WHERE trip_date = CURDATE()")) && ($r = $q->fetch_row())) ? (int) $r[0] : 0;
$students_transport = ($conn && ($q = $conn->query("SELECT COALESCE(SUM(passengers_count),0) FROM trip_logs WHERE trip_date = CURDATE()")) && ($r = $q->fetch_row())) ? (int) $r[0] : 0;
$fuel_consumption = ($conn && ($q = $conn->query("SELECT COALESCE(SUM(fuel_quantity),0) FROM fuel_management WHERE fueling_date = CURDATE()")) && ($r = $q->fetch_row())) ? (float) $r[0] : 0;
$vehicle_maintenance = ($conn && ($q = $conn->query("SELECT COUNT(*) FROM vehicles WHERE status = 'Maintenance'")) && ($r = $q->fetch_row())) ? (int) $r[0] : 0;
$upcoming_trips = ($conn && ($q = $conn->query("SELECT COUNT(*) FROM trip_logs WHERE trip_date >= CURDATE() AND status = 'Scheduled'")) && ($r = $q->fetch_row())) ? (int) $r[0] : 0;
$total_vehicles = ($conn && ($q = $conn->query("SELECT COUNT(*) FROM vehicles")) && ($r = $q->fetch_row())) ? (int) $r[0] : 0;
$active_routes = ($conn && ($q = $conn->query("SELECT COUNT(*) FROM route_schedules WHERE status = 'Active'")) && ($r = $q->fetch_row())) ? (int) $r[0] : 0;

// Get today's trips with vehicle and driver info
$today_trips = [];
if ($conn) {
    try {
        $result = $conn->query("SELECT t.*, v.vehicle_name, v.license_plate, s.full_name AS driver_full_name FROM trip_logs t LEFT JOIN vehicles v ON t.vehicle_id=v.id LEFT JOIN staff s ON t.driver_id=s.id WHERE t.trip_date=CURDATE() ORDER BY t.departure_time");
        if ($result) { while ($row = $result->fetch_assoc()) { $today_trips[] = $row; } }
    } catch (Exception $e) {}
}

// Get route schedules
$routes = [];
if ($conn) {
    try {
        $r = $conn->query("SELECT rs.*, v.vehicle_name, s.full_name AS driver_name FROM route_schedules rs LEFT JOIN vehicles v ON rs.vehicle_id=v.id LEFT JOIN staff s ON rs.driver_id=s.id WHERE rs.status='Active' ORDER BY rs.departure_time");
        if ($r) $routes = $r->fetch_all(MYSQLI_ASSOC);
    } catch (Exception $e) {}
}

// Get vehicle maintenance status
$vehicle_statuses = [];
if ($conn) {
    try {
        $r = $conn->query("SELECT * FROM vehicles ORDER BY vehicle_name");
        if ($r) $vehicle_statuses = $r->fetch_all(MYSQLI_ASSOC);
    } catch (Exception $e) {}
}

$morning_routes = array_filter($routes, fn($rt) => $rt['route_type']==='Morning' || $rt['route_type']==='Both');
$evening_routes = array_filter($routes, fn($rt) => $rt['route_type']==='Evening' || $rt['route_type']==='Both');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include_once __DIR__ . '/../includes/dashboard_head.php'; ?>
<style>
.drv-topbar{background:linear-gradient(135deg,#d97706,#b45309,#92400e);padding:0 32px;height:64px;display:flex;align-items:center;position:sticky;top:0;z-index:100;box-shadow:0 2px 12px rgba(0,0,0,.15)}.drv-topbar-content{width:100%;display:flex;align-items:center;justify-content:space-between}.drv-topbar-left{display:flex;flex-direction:column}.drv-topbar-title{color:#fff;font-size:18px;font-weight:700;letter-spacing:.3px}.drv-topbar-subtitle{color:#fde68a;font-size:12px;margin-top:-2px}.drv-topbar-right{display:flex;align-items:center;gap:12px}.drv-date-badge{background:rgba(255,255,255,.15);color:#fff;padding:6px 14px;border-radius:20px;font-size:12px;font-weight:500;backdrop-filter:blur(4px)}.drv-print-btn,.drv-logout-btn{color:#fde68a;font-size:16px;padding:6px 10px;border-radius:8px;transition:all .2s;text-decoration:none}.drv-print-btn:hover,.drv-logout-btn:hover{background:rgba(255,255,255,.2);color:#fff}
.drv-content{margin-left:270px;padding:24px;min-height:100vh}
@media(max-width:768px){.drv-content{margin-left:0!important;padding:12px!important}}
</style>
</head>
<body class="ent-layout">
<?php include_once __DIR__ . '/../includes/sidebar.php'; ?>
<div class="drv-topbar"><div class="drv-topbar-content"><div class="drv-topbar-left"><div class="drv-topbar-title">Drivers</div><div class="drv-topbar-subtitle">Transport &amp; Fleet Management</div></div><div class="drv-topbar-right"><span class="drv-date-badge"><i class="fas fa-calendar-alt me-1"></i><?= date('l, F j, Y') ?></span><a href="#" class="drv-print-btn" onclick="window.print()"><i class="fas fa-print"></i></a><a href="../auth-handler.php?action=logout" class="drv-logout-btn"><i class="fas fa-sign-out-alt"></i></a></div></div></div>
<div class="drv-content">
<?php switch ($section):
    case 'overview': ?>
    <div class="container">
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stat-card"><h3><i class="fas fa-route"></i> Routes</h3><div class="stat-number"><?php echo $active_routes; ?></div><p class="text-muted mb-0">Active Routes</p></div>
            </div>
            <div class="col-md-3">
                <div class="stat-card"><h3><i class="fas fa-bus"></i> Vehicles</h3><div class="stat-number"><?php echo $total_vehicles; ?></div><p class="text-muted mb-0">Total Vehicles</p></div>
            </div>
            <div class="col-md-3">
                <div class="stat-card"><h3><i class="fas fa-users"></i> Students</h3><div class="stat-number"><?php echo $students_transport; ?></div><p class="text-muted mb-0">Transported Today</p></div>
            </div>
            <div class="col-md-3">
                <div class="stat-card"><h3><i class="fas fa-clock"></i> Trips</h3><div class="stat-number"><?php echo $total_trips_today; ?></div><p class="text-muted mb-0">Completed Today</p></div>
            </div>
        </div>
        <div class="transport-schedule">
            <h3><i class="fas fa-calendar-alt"></i> Today's Transport Schedule</h3>
            <div class="row">
                <div class="col-md-6">
                    <h5>Morning Routes</h5>
                    <?php if (empty($morning_routes)): ?>
                    <div class="route-item"><div class="text-muted text-center py-2">No morning routes scheduled</div></div>
                    <?php else: ?>
                    <?php foreach ($morning_routes as $rt): ?>
                    <div class="route-item">
                        <div class="d-flex justify-content-between align-items-center">
                            <div><h6><?= htmlspecialchars($rt['route_name']) ?></h6><small class="text-muted">Departure: <?= date('g:i A', strtotime($rt['departure_time'])) ?> | Driver: <?= htmlspecialchars($rt['driver_name'] ?? 'Unassigned') ?></small></div>
                            <span class="vehicle-status status-available"><?= htmlspecialchars($rt['status']) ?></span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <div class="col-md-6">
                    <h5>Evening Routes</h5>
                    <?php if (empty($evening_routes)): ?>
                    <div class="route-item"><div class="text-muted text-center py-2">No evening routes scheduled</div></div>
                    <?php else: ?>
                    <?php foreach ($evening_routes as $rt): ?>
                    <div class="route-item">
                        <div class="d-flex justify-content-between align-items-center">
                            <div><h6><?= htmlspecialchars($rt['route_name']) ?></h6><small class="text-muted">Departure: <?= date('g:i A', strtotime($rt['departure_time'])) ?> | Driver: <?= htmlspecialchars($rt['driver_name'] ?? 'Unassigned') ?></small></div>
                            <span class="vehicle-status status-available"><?= htmlspecialchars($rt['status']) ?></span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6">
                <div class="transport-schedule"><h3><i class="fas fa-bus"></i> Vehicle Status</h3>
                    <?php if (empty($vehicle_statuses)): ?>
                    <div class="route-item"><div class="text-muted text-center py-2">No vehicles registered</div></div>
                    <?php else: ?>
                    <?php foreach ($vehicle_statuses as $v): $vstat = strtolower($v['status'] ?? 'available'); $vclass = $vstat==='available'?'status-available':($vstat==='in use'?'status-busy':'status-maintenance'); ?>
                    <div class="route-item">
                        <div class="d-flex justify-content-between align-items-center">
                            <div><h6><?= htmlspecialchars($v['vehicle_name']) ?> (<?= htmlspecialchars($v['vehicle_type']) ?>)</h6><small class="text-muted">Capacity: <?= $v['capacity'] ?> | License: <?= htmlspecialchars($v['license_plate']) ?></small></div>
                            <span class="vehicle-status <?= $vclass ?>"><?= htmlspecialchars($v['status']) ?></span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-md-6">
                <div class="transport-schedule"><h3><i class="fas fa-tasks"></i> Today's Trips</h3>
                    <?php if (empty($today_trips)): ?>
                    <div class="route-item"><div class="text-muted text-center py-2">No trips scheduled for today</div></div>
                    <?php else: ?>
                    <?php foreach ($today_trips as $t): $tstat = strtolower($t['status'] ?? 'scheduled'); $prog = $tstat==='completed'?100:($tstat==='in transit'?60:($tstat==='scheduled'?10:0)); ?>
                    <div class="route-item">
                        <h6><?= htmlspecialchars($t['route_name'] ?? $t['start_location'].' → '.$t['end_location']) ?></h6>
                        <small class="text-muted"><?= htmlspecialchars($t['vehicle_name'] ?? 'Unknown vehicle') ?> | Driver: <?= htmlspecialchars($t['driver_full_name'] ?? 'Unassigned') ?> | <?= date('g:i A', strtotime($t['departure_time'])) ?></small>
                        <div class="progress mt-2" style="height:5px;"><div class="progress-bar bg-<?= $prog>=80?'success':($prog>=40?'warning':'info') ?>" style="width:<?= $prog ?>%"></div></div>
                        <small class="text-muted">Status: <?= htmlspecialchars($t['status']) ?> | Passengers: <?= $t['passengers_count'] ?? 0 ?></small>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
        <?php break;
    default: ?>
    <p class="text-muted py-4">Module content will appear here.</p>
        <?php break;
endswitch; ?>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>

<?php include_once __DIR__ . '/../includes/enterprise_control_panel.php'; ?>
</body>
</html>
