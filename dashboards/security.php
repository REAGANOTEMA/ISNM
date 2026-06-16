<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';

$ctx = bootstrapStaffDashboard(['security']);
$auth_service = $ctx['auth'];
$conn = $ctx['staff'];
$user = $ctx['user'];
$user_id = (int) ($user['id'] ?? 0);
$user_role = $user['role'] ?? '';
$user_email = $user['email'] ?? '';
$user_name = $user['full_name'] ?? '';

// Get security statistics from database
$total_incidents_today = ($conn && ($q = $conn->query("SELECT COUNT(*) FROM security_incidents WHERE DATE(incident_date) = CURDATE()")) && ($r = $q->fetch_row())) ? (int) $r[0] : 0;
$security_patrols = ($conn && ($q = $conn->query("SELECT COUNT(*) FROM security_patrols WHERE patrol_date = CURDATE() AND status IN ('Scheduled','In Progress')")) && ($r = $q->fetch_row())) ? (int) $r[0] : 0;
$access_control_checks = ($conn && ($q = $conn->query("SELECT COUNT(*) FROM access_control_logs WHERE DATE(access_time) = CURDATE()")) && ($r = $q->fetch_row())) ? (int) $r[0] : 0;
$emergency_alerts = ($conn && ($q = $conn->query("SELECT COUNT(*) FROM security_incidents WHERE severity = 'Critical' AND DATE(incident_date) = CURDATE()")) && ($r = $q->fetch_row())) ? (int) $r[0] : 0;
$cctv_cameras_active = ($conn && ($q = $conn->query("SELECT COUNT(*) FROM security_equipment WHERE equipment_type = 'CCTV Camera' AND status = 'Operational'")) && ($r = $q->fetch_row())) ? (int) $r[0] : 0;
$total_guards = ($conn && ($q = $conn->query("SELECT COUNT(*) FROM security_patrols WHERE patrol_date = CURDATE()")) && ($r = $q->fetch_row())) ? (int) $r[0] : 0;

// Get recent security incidents
$recent_incidents = [];
if ($conn) {
    try {
        $result = $conn->query("SELECT incident_type, description, location, reported_at, status, severity FROM security_incidents ORDER BY reported_at DESC LIMIT 10");
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $recent_incidents[] = $row;
            }
        }
    } catch (Exception $e) {}
}

// Get patrol status
$active_patrols = [];
if ($conn) {
    try {
        $r = $conn->query("SELECT sp.*, s.full_name as guard_name FROM security_patrols sp LEFT JOIN staff s ON sp.guard_id=s.id WHERE sp.patrol_date=CURDATE() ORDER BY sp.start_time LIMIT 10");
        if ($r) $active_patrols = $r->fetch_all(MYSQLI_ASSOC);
    } catch (Exception $e) {}
}

// Get security tasks progress
$security_tasks = [];
if ($conn) {
    try {
        $r = $conn->query("SELECT * FROM security_tasks WHERE assigned_date=CURDATE() OR status='In Progress' ORDER BY priority LIMIT 5");
        if ($r) $security_tasks = $r->fetch_all(MYSQLI_ASSOC);
    } catch (Exception $e) {}
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include_once __DIR__ . '/../includes/_favicon.php'; ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <title>Security Dashboard - ISNM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="icon" type="image/x-icon" href="../images/school-logo.png">
    <link href="dashboard-style.css" rel="stylesheet">
    <link href="../dashboards/dashboard-mobile.css" rel="stylesheet">
    <style>
        .dashboard-header {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
        }
        .stat-card {
            background: linear-gradient(to bottom, #ffe082 0%, #ffe082 5px, #fef9e7 5px, #fef9e7 100%);
            border-radius: 15px;
            padding: 1.5rem;
            transition: transform 0.3s ease;
            border-left: 4px solid #dc3545;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: #dc3545;
        }
        .security-alert {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        .alert-item {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1rem;
            border-left: 4px solid #ffc107;
        }
        .alert-item.high {
            border-left-color: #dc3545;
            background: #f8d7da;
        }
        .alert-item.medium {
            border-left-color: #ffc107;
            background: #fff3cd;
        }
        .alert-item.low {
            border-left-color: #28a745;
            background: #d4edda;
        }
        .patrol-status {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        .status-active {
            background: #d4edda;
            color: #155724;
        }
        .status-inactive {
            background: #f8d7da;
            color: #721c24;
        }
        .status-break {
            background: #fff3cd;
            color: #856404;
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
                    <h1><i class="fas fa-shield-alt"></i> Security Dashboard</h1>
                    <p class="mb-0">Campus Security Management</p>
                </div>
                <div class="col-md-6 text-end">
                    <div class="user-info">
                        <span class="me-3">Welcome, <?php echo htmlspecialchars($user_name); ?></span>
                        <a href="../student-directory.php" class="btn btn-sm btn-outline-info me-1"><i class="fas fa-address-book me-1"></i>Directory</a>
<a href="../store_request.php" class="btn btn-sm btn-outline-warning me-1"><i class="fas fa-shopping-cart me-1"></i>Store</a>
                        <a href="../news.php" class="btn btn-sm btn-outline-secondary me-1"><i class="fas fa-newspaper me-1"></i>News</a>
                        <a href="student-records.php" class="btn btn-sm btn-outline-primary me-1"><i class="fas fa-users-gear me-1"></i> Students</a>
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
                    <h3><i class="fas fa-users"></i> Guards</h3>
                    <div class="stat-number"><?php echo $total_guards; ?></div>
                    <p class="text-muted mb-0">On Duty Today</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <h3><i class="fas fa-exclamation-triangle"></i> Incidents</h3>
                    <div class="stat-number"><?php echo $total_incidents_today; ?></div>
                    <p class="text-muted mb-0">Reported Today</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <h3><i class="fas fa-video"></i> Cameras</h3>
                    <div class="stat-number"><?php echo $cctv_cameras_active; ?></div>
                    <p class="text-muted mb-0">Active Monitoring</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <h3><i class="fas fa-door-open"></i> Access</h3>
                    <div class="stat-number"><?php echo $access_control_checks; ?></div>
                    <p class="text-muted mb-0">Entries Today</p>
                </div>
            </div>
        </div>

        <!-- Security Alerts -->
        <div class="security-alert">
            <h3><i class="fas fa-bell"></i> Recent Security Alerts</h3>
            <?php if (empty($recent_incidents)): ?>
            <div class="text-center text-muted py-4"><i class="fas fa-check-circle fa-2x text-success mb-2"></i><p>No recent incidents</p></div>
            <?php else: ?>
            <?php foreach (array_slice($recent_incidents, 0, 5) as $inc): $sev = strtolower($inc['severity'] ?? 'low'); $sevClass = $sev === 'critical' || $sev === 'high' ? 'high' : ($sev === 'medium' ? 'medium' : 'low'); ?>
            <div class="alert-item <?= $sevClass ?>">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6><i class="fas fa-<?= $sevClass === 'high' ? 'exclamation-circle' : ($sevClass === 'medium' ? 'exclamation-triangle' : 'info-circle') ?>"></i> <?= htmlspecialchars($inc['incident_type'] ?? 'Incident') ?></h6>
                        <small class="text-muted"><?= htmlspecialchars($inc['location'] ?? '—') ?> - <?= !empty($inc['reported_at']) ? date('g:i A', strtotime($inc['reported_at'])) : '—' ?> | <?= htmlspecialchars(substr($inc['description'] ?? '—', 0, 80)) ?></small>
                    </div>
                    <span class="badge bg-<?= $sevClass === 'high' ? 'danger' : ($sevClass === 'medium' ? 'warning text-dark' : 'success') ?>"><?= ucfirst(htmlspecialchars($inc['severity'] ?? 'Low')) ?> Priority</span>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Patrol Schedule -->
        <div class="row">
            <div class="col-md-6">
                <div class="security-alert">
                    <h3><i class="fas fa-walking"></i> Current Patrol Status</h3>
                    <?php if (empty($active_patrols)): ?>
                    <div class="text-center text-muted py-3">No patrol records for today</div>
                    <?php else: ?>
                    <?php foreach ($active_patrols as $pat): $pStatus = strtolower($pat['status'] ?? 'scheduled'); ?>
                    <div class="alert-item">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6><?= htmlspecialchars($pat['guard_name'] ?? 'Guard') ?></h6>
                                <small class="text-muted"><?= htmlspecialchars($pat['location'] ?? $pat['patrol_area'] ?? '—') ?> | Started: <?= !empty($pat['start_time']) ? date('g:i A', strtotime($pat['start_time'])) : '—' ?></small>
                            </div>
                            <span class="patrol-status status-<?= $pStatus === 'active' ? 'active' : ($pStatus === 'break' ? 'break' : 'inactive') ?>"><?= ucfirst(htmlspecialchars($pStatus)) ?></span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-md-6">
                <div class="security-alert">
                    <h3><i class="fas fa-tasks"></i> Security Tasks</h3>
                    <?php if (empty($security_tasks)): ?>
                    <div class="text-center text-muted py-3">No tasks assigned for today</div>
                    <?php else: ?>
                    <?php foreach ($security_tasks as $task): $prog = min(100, max(0, (int)($task['progress'] ?? 0))); ?>
                    <div class="alert-item">
                        <h6><?= htmlspecialchars($task['title'] ?? $task['task_name'] ?? 'Task') ?></h6>
                        <small class="text-muted"><?= htmlspecialchars($task['description'] ?? '') ?></small>
                        <div class="progress mt-2" style="height: 5px;">
                            <div class="progress-bar bg-<?= $prog >= 80 ? 'success' : ($prog >= 40 ? 'warning' : 'info') ?>" style="width: <?= $prog ?>%"><?= $prog ?>%</div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="row mt-4">
            <div class="col-md-12">
                <div class="security-alert">
                    <h3><i class="fas fa-bolt"></i> Quick Actions</h3>
                    <div class="row">
                        <div class="col-md-3 mb-2">
                            <button class="btn btn-danger w-100">
                                <i class="fas fa-phone"></i> Emergency Contact
                            </button>
                        </div>
                        <div class="col-md-3 mb-2">
                            <button class="btn btn-warning w-100">
                                <i class="fas fa-exclamation-triangle"></i> Report Incident
                            </button>
                        </div>
                        <div class="col-md-3 mb-2">
                            <button class="btn btn-info w-100">
                                <i class="fas fa-video"></i> View Cameras
                            </button>
                        </div>
                        <div class="col-md-3 mb-2">
                            <button class="btn btn-success w-100">
                                <i class="fas fa-clipboard"></i> Daily Report
                            </button>
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

