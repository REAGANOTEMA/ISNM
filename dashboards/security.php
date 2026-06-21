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

// Get emergency contacts
$emergency_contacts = [];
if ($conn) {
    try {
        $r = $conn->query("SELECT * FROM security_emergency_contacts WHERE is_active=1 ORDER BY contact_type");
        if ($r) $emergency_contacts = $r->fetch_all(MYSQLI_ASSOC);
    } catch (Exception $e) {}
}

// Get equipment maintenance status
$equipment_due = [];
if ($conn) {
    try {
        $r = $conn->query("SELECT * FROM security_equipment WHERE status!='Retired' AND (next_maintenance_date <= CURDATE() OR next_maintenance_date IS NULL) ORDER BY next_maintenance_date ASC LIMIT 5");
        if ($r) $equipment_due = $r->fetch_all(MYSQLI_ASSOC);
    } catch (Exception $e) {}
}

$today_visitors = []; $visitor_count = 0;
if ($conn) {
    try {
        $r = $conn->query("SELECT * FROM security_visitors WHERE visit_date = CURDATE() ORDER BY expected_arrival ASC LIMIT 20");
        if ($r) $today_visitors = $r->fetch_all(MYSQLI_ASSOC);
        $visitor_count = (int)(($r=$conn->query("SELECT COUNT(*) FROM security_visitors WHERE visit_date = CURDATE()"))&&$r?$r->fetch_row()[0]:0);
    } catch (Exception $e) {}
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include_once __DIR__ . '/../includes/dashboard_head.php'; ?>
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
                        <small class="text-muted"><?= htmlspecialchars($inc['location'] ?? '-') ?> , <?= !empty($inc['reported_at']) ? date('g:i A', strtotime($inc['reported_at'])) : '-' ?> | <?= htmlspecialchars(substr($inc['description'] ?? '-', 0, 80)) ?></small>
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
                                <small class="text-muted"><?= htmlspecialchars($pat['location'] ?? $pat['patrol_area'] ?? '-') ?> | Started: <?= !empty($pat['start_time']) ? date('g:i A', strtotime($pat['start_time'])) : '-' ?></small>
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
                    <h3><i class="fas fa-tasks"></i> Equipment Status</h3>
                    <?php if (empty($equipment_due)): ?>
                    <div class="text-center text-muted py-3">All equipment is up to date</div>
                    <?php else: ?>
                    <?php foreach ($equipment_due as $eq): ?>
                    <div class="alert-item">
                        <h6><?= htmlspecialchars($eq['equipment_name'] ?? 'Equipment') ?></h6>
                        <small class="text-muted"><?= htmlspecialchars($eq['equipment_type'] ?? '') ?> @ <?= htmlspecialchars($eq['location'] ?? '') ?></small>
                        <div class="d-flex justify-content-between mt-1">
                            <span class="badge bg-<?= $eq['status']==='Operational'?'success':($eq['status']==='Under Maintenance'?'warning text-dark':'danger') ?>"><?= htmlspecialchars($eq['status']) ?></span>
                            <small class="text-muted">Next maint: <?= $eq['next_maintenance_date'] ?? 'Not set' ?></small>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-12">
                <div class="security-alert">
                    <h3><i class="fas fa-address-book"></i> Today's Visitors (<?= $visitor_count ?>)</h3>
                    <?php if (empty($today_visitors)): ?>
                    <div class="text-center text-muted py-3">No visitors scheduled for today</div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead><tr><th>Name</th><th>Phone</th><th>Nature</th><th>Person to Visit</th><th>Arrival</th><th>Status</th></tr></thead>
                            <tbody>
                            <?php foreach ($today_visitors as $v): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($v['visitor_name']) ?></strong></td>
                                <td><small><?= htmlspecialchars($v['visitor_phone'] ?? '-') ?></small></td>
                                <td><span class="badge bg-info"><?= htmlspecialchars($v['visitor_nature']) ?></span></td>
                                <td><small><?= htmlspecialchars($v['person_to_visit_name'] ?? '-') ?></small></td>
                                <td><small><?= !empty($v['expected_arrival']) ? date('g:i A', strtotime($v['expected_arrival'])) : '-' ?></small></td>
                                <td><span class="badge bg-<?= match($v['status']){'Checked In'=>'success','On Campus'=>'primary','Checked Out'=>'secondary','Scheduled'=>'warning','No Show'=>'danger',default=>'secondary'} ?>"><?= htmlspecialchars($v['status']) ?></span></td>
                            </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
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
                            <button class="btn btn-danger w-100" data-bs-toggle="modal" data-bs-target="#emergencyModal">
                                <i class="fas fa-phone"></i> Emergency Contact
                            </button>
                        </div>
                        <div class="col-md-3 mb-2">
                            <button class="btn btn-warning w-100" data-bs-toggle="modal" data-bs-target="#incidentModal">
                                <i class="fas fa-exclamation-triangle"></i> Report Incident
                            </button>
                        </div>
                        <div class="col-md-3 mb-2">
                            <button class="btn btn-info w-100" onclick="document.getElementById('cameraSection').scrollIntoView({behavior:'smooth'})">
                                <i class="fas fa-video"></i> View Equipment
                            </button>
                        </div>
                        <div class="col-md-3 mb-2">
                            <button class="btn btn-success w-100" onclick="window.print()">
                                <i class="fas fa-clipboard"></i> Daily Report
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Emergency Contacts Modal -->
    <div class="modal fade" id="emergencyModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-phone-alt me-2"></i>Emergency Contacts</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <?php if (empty($emergency_contacts)): ?>
                    <p class="text-muted">No emergency contacts configured.</p>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead><tr><th>Name</th><th>Type</th><th>Phone</th><th>Email</th></tr></thead>
                            <tbody>
                                <?php foreach ($emergency_contacts as $ec): ?>
                                <tr>
                                    <td><?= htmlspecialchars($ec['contact_name']) ?></td>
                                    <td><span class="badge bg-danger"><?= htmlspecialchars($ec['contact_type']) ?></span></td>
                                    <td><a href="tel:<?= htmlspecialchars($ec['phone_number']) ?>"><?= htmlspecialchars($ec['phone_number']) ?></a></td>
                                    <td><?= htmlspecialchars($ec['email'] ?? '-') ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Report Incident Modal -->
    <div class="modal fade" id="incidentModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="../handlers/security_handler.php">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="fas fa-exclamation-triangle me-2"></i>Report Incident</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="report_incident">
                        <div class="mb-3">
                            <label class="form-label">Incident Type</label>
                            <select class="form-select" name="incident_type" required>
                                <option value="">Select...</option>
                                <option value="Unauthorized Access">Unauthorized Access</option>
                                <option value="Theft">Theft</option>
                                <option value="Vandalism">Vandalism</option>
                                <option value="Assault">Assault</option>
                                <option value="Parking Violation">Parking Violation</option>
                                <option value="Emergency">Emergency</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Location</label>
                            <input class="form-control" name="location" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Severity</label>
                            <select class="form-select" name="severity">
                                <option value="Low">Low</option>
                                <option value="Medium" selected>Medium</option>
                                <option value="High">High</option>
                                <option value="Critical">Critical</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" rows="3" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-danger"><i class="fas fa-paper-plane"></i> Submit Report</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
</div>
</body>
</html>

