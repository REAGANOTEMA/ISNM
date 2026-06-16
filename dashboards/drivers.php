<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';

$ctx = bootstrapStaffDashboard(['driver']);
$auth_service = $ctx['auth'];
$conn = $ctx['staff'];
$user = $ctx['user'];
$user_id = (int) ($user['id'] ?? 0);
$user_role = $user['role'] ?? '';
$user_email = $user['email'] ?? '';
$user_name = $user['full_name'] ?? '';

// Get driver statistics from database
$total_trips_today = ($conn && ($q = $conn->query("SELECT COUNT(*) FROM trip_logs WHERE trip_date = CURDATE()")) && ($r = $q->fetch_row())) ? (int) $r[0] : 0;
$students_transport = ($conn && ($q = $conn->query("SELECT COALESCE(SUM(passengers_count),0) FROM trip_logs WHERE trip_date = CURDATE()")) && ($r = $q->fetch_row())) ? (int) $r[0] : 0;
$fuel_consumption = ($conn && ($q = $conn->query("SELECT COALESCE(SUM(fuel_quantity),0) FROM fuel_management WHERE fueling_date = CURDATE()")) && ($r = $q->fetch_row())) ? (float) $r[0] : 0;
$vehicle_maintenance = ($conn && ($q = $conn->query("SELECT COUNT(*) FROM vehicles WHERE status = 'Maintenance'")) && ($r = $q->fetch_row())) ? (int) $r[0] : 0;
$upcoming_trips = ($conn && ($q = $conn->query("SELECT COUNT(*) FROM trip_logs WHERE trip_date >= CURDATE() AND status = 'Scheduled'")) && ($r = $q->fetch_row())) ? (int) $r[0] : 0;
$total_vehicles = ($conn && ($q = $conn->query("SELECT COUNT(*) FROM vehicles")) && ($r = $q->fetch_row())) ? (int) $r[0] : 0;
$active_routes = ($conn && ($q = $conn->query("SELECT COUNT(*) FROM route_schedules WHERE status = 'Active'")) && ($r = $q->fetch_row())) ? (int) $r[0] : 0;

// Get recent trips
$recent_trips = [];
if ($conn) {
    try {
        $result = $conn->query("SELECT vehicle, driver_name, destination, departure_time, status FROM fleet_management ORDER BY departure_time DESC LIMIT 10");
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $recent_trips[] = $row;
            }
        }
    } catch (Exception $e) {}
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include_once __DIR__ . '/../includes/_favicon.php'; ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <title>Drivers Dashboard - ISNM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="icon" type="image/x-icon" href="../images/school-logo.png">
    <link href="dashboard-style.css" rel="stylesheet">
    <link href="../dashboards/dashboard-mobile.css" rel="stylesheet">
    <style>
        .dashboard-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
        }
        .stat-card {
            background: linear-gradient(to bottom, #ffe082 0%, #ffe082 5px, #fef9e7 5px, #fef9e7 100%);
            border-radius: 15px;
            padding: 1.5rem;
            transition: transform 0.3s ease;
            border-left: 4px solid #667eea;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: #667eea;
        }
        .transport-schedule {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        .route-item {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1rem;
            border-left: 4px solid #28a745;
        }
        .vehicle-status {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        .status-available {
            background: #d4edda;
            color: #155724;
        }
        .status-busy {
            background: #fff3cd;
            color: #856404;
        }
        .status-maintenance {
            background: #f8d7da;
            color: #721c24;
        }
    </style>
</head>
<body>
<?php include_once __DIR__ . '/../includes/sidebar.php'; ?>
<div style="margin-left:270px">
    <!-- Header -->
    <div class="dashboard-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h1><i class="fas fa-bus"></i> Drivers Dashboard</h1>
                    <p class="mb-0">Transport Services Management</p>
                </div>
                <div class="col-md-6 text-end">
                    <div class="user-info">
                        <span class="me-3">Welcome, <?php echo htmlspecialchars($user_name); ?></span>
                        <a href="../student-directory.php" class="btn btn-sm btn-outline-info me-1"><i class="fas fa-address-book me-1"></i>Directory</a>
<a href="student-records.php" class="btn btn-sm btn-outline-primary me-1"><i class="fas fa-users-gear me-1"></i>Students</a>
<a href="../store_request.php" class="btn btn-sm btn-outline-warning me-1"><i class="fas fa-shopping-cart me-1"></i>Store</a>
<a href="../news.php" class="btn btn-sm btn-outline-secondary me-1"><i class="fas fa-newspaper me-1"></i>News</a>
                        <a href="../logout.php" class="btn btn-light btn-sm">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <!-- Statistics -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stat-card">
                    <h3><i class="fas fa-route"></i> Routes</h3>
                    <div class="stat-number"><?php echo $active_routes; ?></div>
                    <p class="text-muted mb-0">Active Routes</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <h3><i class="fas fa-bus"></i> Vehicles</h3>
                    <div class="stat-number"><?php echo $total_vehicles; ?></div>
                    <p class="text-muted mb-0">Total Vehicles</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <h3><i class="fas fa-users"></i> Students</h3>
                    <div class="stat-number"><?php echo $students_transport; ?></div>
                    <p class="text-muted mb-0">Transported Today</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <h3><i class="fas fa-clock"></i> Trips</h3>
                    <div class="stat-number"><?php echo $total_trips_today; ?></div>
                    <p class="text-muted mb-0">Completed Today</p>
                </div>
            </div>
        </div>

        <!-- Transport Schedule -->
        <div class="transport-schedule">
            <h3><i class="fas fa-calendar-alt"></i> Today's Transport Schedule</h3>
            <div class="row">
                <div class="col-md-6">
                    <h5>Morning Routes</h5>
                    <div class="route-item">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6>Route 1: Iganga Town</h6>
                                <small class="text-muted">Departure: 6:30 AM | Driver: John Smith</small>
                            </div>
                            <span class="vehicle-status status-available">Available</span>
                        </div>
                    </div>
                    <div class="route-item">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6>Route 2: Jinja Road</h6>
                                <small class="text-muted">Departure: 6:45 AM | Driver: Michael Johnson</small>
                            </div>
                            <span class="vehicle-status status-busy">In Transit</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <h5>Evening Routes</h5>
                    <div class="route-item">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6>Route 1: Iganga Town</h6>
                                <small class="text-muted">Departure: 5:00 PM | Driver: John Smith</small>
                            </div>
                            <span class="vehicle-status status-available">Scheduled</span>
                        </div>
                    </div>
                    <div class="route-item">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6>Route 2: Jinja Road</h6>
                                <small class="text-muted">Departure: 5:15 PM | Driver: Michael Johnson</small>
                            </div>
                            <span class="vehicle-status status-available">Scheduled</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Vehicle Status -->
        <div class="row">
            <div class="col-md-6">
                <div class="transport-schedule">
                    <h3><i class="fas fa-bus"></i> Vehicle Status</h3>
                    <div class="route-item">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6>Bus 1 - Toyota Coaster</h6>
                                <small class="text-muted">Capacity: 30 | License: UAB 123A</small>
                            </div>
                            <span class="vehicle-status status-available">Available</span>
                        </div>
                    </div>
                    <div class="route-item">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6>Bus 2 - Nissan Civilian</h6>
                                <small class="text-muted">Capacity: 25 | License: UAB 456B</small>
                            </div>
                            <span class="vehicle-status status-busy">In Use</span>
                        </div>
                    </div>
                    <div class="route-item">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6>Van 1 - Toyota Hiace</h6>
                                <small class="text-muted">Capacity: 15 | License: UAB 789C</small>
                            </div>
                            <span class="vehicle-status status-maintenance">Maintenance</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="transport-schedule">
                    <h3><i class="fas fa-tasks"></i> Driver Tasks</h3>
                    <div class="route-item">
                        <h6>Daily Vehicle Check</h6>
                        <small class="text-muted">Complete pre-trip inspection checklist</small>
                        <div class="progress mt-2" style="height: 5px;">
                            <div class="progress-bar bg-success" style="width: 100%"></div>
                        </div>
                    </div>
                    <div class="route-item">
                        <h6>Student Pickup List</h6>
                        <small class="text-muted">Verify student attendance for all routes</small>
                        <div class="progress mt-2" style="height: 5px;">
                            <div class="progress-bar bg-warning" style="width: 60%"></div>
                        </div>
                    </div>
                    <div class="route-item">
                        <h6>Fuel Management</h6>
                        <small class="text-muted">Log fuel consumption and refueling</small>
                        <div class="progress mt-2" style="height: 5px;">
                            <div class="progress-bar bg-info" style="width: 40%"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
</div>
</body>
</html>

