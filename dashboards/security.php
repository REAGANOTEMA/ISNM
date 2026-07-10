<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
require_once __DIR__ . '/../includes/enterprise_auth.php';

$ctx = bootstrapStaffDashboard(['security officer','security','admin']);
$auth_service = $ctx['auth'];
$conn = $ctx['staff'];
$user = $ctx['user'];
$user_id = (int) ($user['id'] ?? 0);
$user_role = $user['role'] ?? '';
$user_email = $user['email'] ?? '';
$user_name = $user['full_name'] ?? '';

// ── Page routing ──
$pageToSection = [
    'home'                  => 'overview',
    'overview'              => 'overview',
    'visitor-registration'  => 'visitors',
    'visitor-history'       => 'visitors',
    'visitors'              => 'visitors',
    'incidents'             => 'incidents',
    'emergency'             => 'emergency',
    'patrol'                => 'patrol',
];
$page    = $_GET['page'] ?? 'home';
$section = $pageToSection[$page] ?? 'overview';

// ── Flash messages ──
$flash_success = $_SESSION['success'] ?? null;
$flash_error   = $_SESSION['error']   ?? null;
unset($_SESSION['success'], $_SESSION['error']);

// ── Statistics ──
$total_incidents_today = ($conn && ($q = $conn->query("SELECT COUNT(*) FROM security_incidents WHERE DATE(incident_date) = CURDATE()")) && ($r = $q->fetch_row())) ? (int) $r[0] : 0;
$security_patrols      = ($conn && ($q = $conn->query("SELECT COUNT(*) FROM security_patrols WHERE DATE(start_time) = CURDATE() AND status IN ('Scheduled','In Progress','Active')")) && ($r = $q->fetch_row())) ? (int) $r[0] : 0;
$access_control_checks = ($conn && ($q = $conn->query("SELECT COUNT(*) FROM access_control_logs WHERE DATE(access_time) = CURDATE()")) && ($r = $q->fetch_row())) ? (int) $r[0] : 0;
$emergency_alerts      = ($conn && ($q = $conn->query("SELECT COUNT(*) FROM security_incidents WHERE severity = 'Critical' AND DATE(incident_date) = CURDATE()")) && ($r = $q->fetch_row())) ? (int) $r[0] : 0;
$cctv_cameras_active   = ($conn && ($q = $conn->query("SELECT COUNT(*) FROM security_equipment WHERE equipment_type = 'CCTV Camera' AND status = 'Operational'")) && ($r = $q->fetch_row())) ? (int) $r[0] : 0;
$total_guards          = ($conn && ($q = $conn->query("SELECT COUNT(*) FROM security_patrols WHERE DATE(start_time) = CURDATE()")) && ($r = $q->fetch_row())) ? (int) $r[0] : 0;

// ── Data: All incidents ──
$all_incidents = [];
if ($conn) {
    try {
        $result = $conn->query("SELECT * FROM security_incidents ORDER BY incident_date DESC, id DESC LIMIT 100");
        if ($result) { while ($row = $result->fetch_assoc()) { $all_incidents[] = $row; } }
    } catch (Exception $e) { error_log('security context: ' . $e->getMessage()); }
}

// ── Data: Recent incidents for overview ──
$recent_incidents = array_slice($all_incidents, 0, 10);

// ── Data: All patrols ──
$all_patrols = [];
if ($conn) {
    try {
        $r = $conn->query("SELECT * FROM security_patrols ORDER BY start_time DESC LIMIT 100");
        if ($r) $all_patrols = $r->fetch_all(MYSQLI_ASSOC);
    } catch (Exception $e) { error_log('security context: ' . $e->getMessage()); }
}

// ── Data: Active patrols for overview ──
$active_patrols = [];
if ($conn) {
    try {
        $r = $conn->query("SELECT sp.*, s.full_name as guard_name FROM security_patrols sp LEFT JOIN staff s ON sp.officer_id=s.id WHERE DATE(sp.start_time)=CURDATE() ORDER BY sp.start_time DESC LIMIT 10");
        if ($r) $active_patrols = $r->fetch_all(MYSQLI_ASSOC);
    } catch (Exception $e) { error_log('security context: ' . $e->getMessage()); }
}

// ── Data: All visitors ──
$all_visitors = [];
if ($conn) {
    try {
        $r = $conn->query("SELECT * FROM security_visitors ORDER BY visit_date DESC, actual_arrival DESC LIMIT 100");
        if ($r) $all_visitors = $r->fetch_all(MYSQLI_ASSOC);
    } catch (Exception $e) { error_log('security context: ' . $e->getMessage()); }
}

// ── Data: Today's visitors for overview ──
$today_visitors  = [];
$visitor_count   = 0;
if ($conn) {
    try {
        $r = $conn->query("SELECT * FROM security_visitors WHERE visit_date = CURDATE() ORDER BY actual_arrival DESC LIMIT 20");
        if ($r) $today_visitors = $r->fetch_all(MYSQLI_ASSOC);
        $rq = $conn->query("SELECT COUNT(*) FROM security_visitors WHERE visit_date = CURDATE()");
        if ($rq) $visitor_count = (int) $rq->fetch_row()[0];
    } catch (Exception $e) { error_log('security context: ' . $e->getMessage()); }
}

// ── Data: Emergency contacts ──
$emergency_contacts = [];
if ($conn) {
    try {
        $r = $conn->query("SELECT * FROM security_emergency_contacts WHERE is_active=1 ORDER BY contact_type");
        if ($r) $emergency_contacts = $r->fetch_all(MYSQLI_ASSOC);
    } catch (Exception $e) { error_log('security context: ' . $e->getMessage()); }
}

// ── Data: Equipment due ──
$equipment_due = [];
if ($conn) {
    try {
        $r = $conn->query("SELECT * FROM security_equipment WHERE status!='Retired' AND (next_maintenance_date <= CURDATE() OR next_maintenance_date IS NULL) ORDER BY next_maintenance_date ASC LIMIT 5");
        if ($r) $equipment_due = $r->fetch_all(MYSQLI_ASSOC);
    } catch (Exception $e) { error_log('security context: ' . $e->getMessage()); }
}

// ── Data: Staff list for patrol form ──
$staff_list = [];
if ($conn) {
    try {
        $r = $conn->query("SELECT id, full_name FROM staff WHERE status='Active' ORDER BY full_name");
        if ($r) $staff_list = $r->fetch_all(MYSQLI_ASSOC);
    } catch (Exception $e) { error_log('security context: ' . $e->getMessage()); }
}
$csrf_field = '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($_SESSION['csrf_token'] ?? '') . '">';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include_once __DIR__ . '/../includes/dashboard_head.php'; ?>
<style>
.secu-content{margin-left:270px;padding:24px;min-height:100vh}
@media(max-width:768px){.secu-content{margin-left:0!important;padding:12px!important}}
.secu-content .nav-pills .nav-link{border-radius:8px;margin-right:4px;padding:8px 16px;font-size:0.9rem;color:#555}
.secu-content .nav-pills .nav-link.active{background:#0d6efd;color:#fff}
.secu-content .card-section{background:#fff;border-radius:12px;padding:20px;box-shadow:0 1px 3px rgba(0,0,0,.08);margin-bottom:20px}
.secu-content .badge-severity-high{background:#dc3545;color:#fff}
.secu-content .badge-severity-medium{background:#ffc107;color:#212529}
.secu-content .badge-severity-low{background:#198754;color:#fff}
.secu-content .badge-severity-critical{background:#6f1d1d;color:#fff}
.secu-content .table th{font-size:0.82rem;text-transform:uppercase;letter-spacing:.5px;color:#666;border-bottom-width:1px}
.secu-content .table td{vertical-align:middle;font-size:0.9rem}
.secu-content .btn-group-sm .btn{font-size:0.78rem;padding:3px 10px}
.secu-content .modal-header{border-bottom:1px solid #eee}
.secu-content .modal-footer{border-top:1px solid #eee}
</style>
</head>
<body class="ent-layout">
<?php include_once __DIR__ . '/../includes/sidebar.php'; ?>
<?php include_once __DIR__ . '/../includes/dashboard_topbar.php'; ?>

<div class="secu-content">

<?php if ($flash_success): ?>
<div class="alert alert-success alert-dismissible fade show" role="alert"><i class="fas fa-check-circle me-2"></i><?= htmlspecialchars($flash_success) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>
<?php if ($flash_error): ?>
<div class="alert alert-danger alert-dismissible fade show" role="alert"><i class="fas fa-exclamation-circle me-2"></i><?= htmlspecialchars($flash_error) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>

<?php switch ($section):
    // ═══════════════════════════════════════════════
    // OVERVIEW
    // ═══════════════════════════════════════════════
    case 'overview': ?>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="fw-bold mb-0"><i class="fas fa-shield-alt me-2"></i>Security Dashboard</h4>
        <span class="text-muted small"><?= date('l, d M Y') ?></span>
    </div>
    <div class="row mb-4">
        <div class="col-md-3"><div class="stat-card"><h3><i class="fas fa-users"></i> Guards</h3><div class="stat-number"><?= $total_guards ?></div><p class="text-muted mb-0">On Duty Today</p></div></div>
        <div class="col-md-3"><div class="stat-card"><h3><i class="fas fa-exclamation-triangle"></i> Incidents</h3><div class="stat-number"><?= $total_incidents_today ?></div><p class="text-muted mb-0">Reported Today</p></div></div>
        <div class="col-md-3"><div class="stat-card"><h3><i class="fas fa-video"></i> Cameras</h3><div class="stat-number"><?= $cctv_cameras_active ?></div><p class="text-muted mb-0">Active Monitoring</p></div></div>
        <div class="col-md-3"><div class="stat-card"><h3><i class="fas fa-door-open"></i> Access</h3><div class="stat-number"><?= $access_control_checks ?></div><p class="text-muted mb-0">Entries Today</p></div></div>
    </div>

    <div class="card-section">
        <h5 class="fw-bold mb-3"><i class="fas fa-bell me-2"></i>Recent Security Alerts</h5>
        <?php if (empty($recent_incidents)): ?>
        <div class="text-center text-muted py-4"><i class="fas fa-check-circle fa-2x text-success mb-2"></i><p>No recent incidents</p></div>
        <?php else: ?>
        <?php foreach (array_slice($recent_incidents, 0, 5) as $inc):
            $sev = strtolower($inc['severity'] ?? 'low');
            $sevClass = ($sev === 'critical') ? 'critical' : (($sev === 'high') ? 'high' : (($sev === 'medium') ? 'medium' : 'low'));
        ?>
        <div class="alert-item <?= $sevClass ?>"><div class="d-flex justify-content-between align-items-center"><div><h6><i class="fas fa-<?= $sevClass === 'high' || $sevClass === 'critical' ? 'exclamation-circle' : ($sevClass === 'medium' ? 'exclamation-triangle' : 'info-circle') ?>"></i> <?= htmlspecialchars($inc['incident_type'] ?? 'Incident') ?></h6><small class="text-muted"><?= htmlspecialchars($inc['location'] ?? '-') ?> | <?= !empty($inc['incident_date']) ? date('g:i A', strtotime($inc['incident_date'])) : '-' ?> | <?= htmlspecialchars(substr($inc['description'] ?? '-', 0, 80)) ?></small></div><span class="badge bg-<?= $sevClass === 'high' || $sevClass === 'critical' ? 'danger' : ($sevClass === 'medium' ? 'warning text-dark' : 'success') ?>"><?= ucfirst(htmlspecialchars($inc['severity'] ?? 'Low')) ?></span></div></div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="card-section">
                <h5 class="fw-bold mb-3"><i class="fas fa-walking me-2"></i>Current Patrols</h5>
                <?php if (empty($active_patrols)): ?>
                <div class="text-center text-muted py-3">No patrol records for today</div>
                <?php else: ?>
                <?php foreach ($active_patrols as $pat):
                    $pStatus = strtolower($pat['status'] ?? 'scheduled');
                ?>
                <div class="alert-item"><div class="d-flex justify-content-between align-items-center"><div><h6><?= htmlspecialchars($pat['officer_name'] ?? $pat['guard_name'] ?? 'Guard') ?></h6><small class="text-muted"><?= htmlspecialchars($pat['patrol_area'] ?? '-') ?> | Started: <?= !empty($pat['start_time']) ? date('g:i A', strtotime($pat['start_time'])) : '-' ?></small></div><span class="badge bg-<?= $pStatus === 'active' || $pStatus === 'in progress' ? 'primary' : ($pStatus === 'completed' ? 'success' : 'secondary') ?>"><?= ucfirst(htmlspecialchars($pat['status'])) ?></span></div></div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card-section">
                <h5 class="fw-bold mb-3"><i class="fas fa-tasks me-2"></i>Equipment Status</h5>
                <?php if (empty($equipment_due)): ?>
                <div class="text-center text-muted py-3">All equipment is up to date</div>
                <?php else: ?>
                <?php foreach ($equipment_due as $eq): ?>
                <div class="alert-item"><h6><?= htmlspecialchars($eq['equipment_name'] ?? 'Equipment') ?></h6><small class="text-muted"><?= htmlspecialchars($eq['equipment_type'] ?? '') ?> @ <?= htmlspecialchars($eq['location'] ?? '') ?></small><div class="d-flex justify-content-between mt-1"><span class="badge bg-<?= $eq['status']==='Operational'?'success':($eq['status']==='Under Maintenance'?'warning text-dark':'danger') ?>"><?= htmlspecialchars($eq['status']) ?></span><small class="text-muted">Next maint: <?= $eq['next_maintenance_date'] ?? 'Not set' ?></small></div></div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-12">
            <div class="card-section">
                <h5 class="fw-bold mb-3"><i class="fas fa-address-book me-2"></i>Today's Visitors (<?= $visitor_count ?>)</h5>
                <?php if (empty($today_visitors)): ?>
                <div class="text-center text-muted py-3">No visitors for today</div>
                <?php else: ?>
                <div class="table-responsive"><table class="table table-sm table-hover"><thead><tr><th>Name</th><th>Phone</th><th>Purpose</th><th>Person to Visit</th><th>Arrival</th><th>Status</th></tr></thead><tbody>
                <?php foreach ($today_visitors as $v):
                    $vs = $v['status'];
                    switch($vs) { case 'Checked In': $vs_badge = 'success'; break; case 'On Campus': $vs_badge = 'primary'; break; case 'Checked Out': $vs_badge = 'secondary'; break; default: $vs_badge = 'secondary'; }
                ?>
                <tr><td><strong><?= htmlspecialchars($v['visitor_name']) ?></strong></td><td><small><?= htmlspecialchars($v['visitor_phone'] ?? '-') ?></small></td><td><span class="badge bg-info"><?= htmlspecialchars($v['visitor_nature']) ?></span></td><td><small><?= htmlspecialchars($v['person_to_visit_name'] ?? '-') ?></small></td><td><small><?= !empty($v['actual_arrival']) ? date('g:i A', strtotime($v['actual_arrival'])) : '-' ?></small></td><td><span class="badge bg-<?= $vs_badge ?>"><?= htmlspecialchars($v['status']) ?></span></td></tr>
                <?php endforeach; ?>
                </tbody></table></div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-12">
            <div class="card-section">
                <h5 class="fw-bold mb-3"><i class="fas fa-bolt me-2"></i>Quick Actions</h5>
                <div class="row">
                    <div class="col-md-2 mb-2"><a href="?page=incidents" class="btn btn-warning w-100"><i class="fas fa-exclamation-triangle"></i> Incidents</a></div>
                    <div class="col-md-2 mb-2"><a href="?page=patrol" class="btn btn-primary w-100"><i class="fas fa-walking"></i> Patrols</a></div>
                    <div class="col-md-2 mb-2"><a href="?page=visitors" class="btn btn-success w-100"><i class="fas fa-user-plus"></i> Visitors</a></div>
                    <div class="col-md-2 mb-2"><button class="btn btn-danger w-100" data-bs-toggle="modal" data-bs-target="#emergencyModal"><i class="fas fa-phone"></i> Emergency</button></div>
                    <div class="col-md-2 mb-2"><button class="btn btn-info w-100" onclick="window.print()"><i class="fas fa-clipboard"></i> Report</button></div>
                </div>
            </div>
        </div>
    </div>
    <?php break;

    // ═══════════════════════════════════════════════
    // INCIDENTS — Full CRUD
    // ═══════════════════════════════════════════════
    case 'incidents': ?>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="fw-bold mb-0"><i class="fas fa-exclamation-triangle me-2"></i>Security Incidents</h4>
        <button class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#addIncidentModal"><i class="fas fa-plus me-1"></i>Report Incident</button>
    </div>

    <div class="card-section">
        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle" id="incidentsTable">
                <thead class="table-light">
                    <tr><th>#</th><th>Type</th><th>Location</th><th>Severity</th><th>Status</th><th>Reported By</th><th>Date</th><th>Actions</th></tr>
                </thead>
                <tbody>
                <?php if (empty($all_incidents)): ?>
                    <tr><td colspan="8" class="text-center text-muted py-4">No incidents recorded yet.</td></tr>
                <?php else: ?>
                <?php foreach ($all_incidents as $inc):
                    $sevLc = strtolower($inc['severity'] ?? 'low');
                    $sevBadge = ($sevLc === 'critical') ? 'dark' : (($sevLc === 'high') ? 'danger' : (($sevLc === 'medium') ? 'warning' : 'success'));
                    $statusBadge = strtolower($inc['status'] ?? 'reported') === 'resolved' || strtolower($inc['status'] ?? '') === 'closed' ? 'success' : (strtolower($inc['status'] ?? '') === 'in progress' ? 'primary' : 'secondary');
                ?>
                <tr>
                    <td><strong>#<?= (int)$inc['id'] ?></strong></td>
                    <td><?= htmlspecialchars($inc['incident_type']) ?></td>
                    <td><small><?= htmlspecialchars($inc['location'] ?? '-') ?></small></td>
                    <td><span class="badge bg-<?= $sevBadge ?>"><?= htmlspecialchars($inc['severity']) ?></span></td>
                    <td><span class="badge bg-<?= $statusBadge ?>"><?= htmlspecialchars($inc['status']) ?></span></td>
                    <td><small><?= htmlspecialchars($inc['reported_by_name'] ?? '-') ?></small></td>
                    <td><small><?= !empty($inc['incident_date']) ? date('d M Y, g:i A', strtotime($inc['incident_date'])) : '-' ?></small></td>
                    <td>
                        <div class="btn-group btn-group-sm">
                            <button class="btn btn-outline-primary" title="Edit" data-bs-toggle="modal" data-bs-target="#editIncidentModal"
                                data-id="<?= (int)$inc['id'] ?>"
                                data-status="<?= htmlspecialchars($inc['status'] ?? '') ?>"
                                data-resolution="<?= htmlspecialchars($inc['resolution_notes'] ?? '') ?>"
                                data-type="<?= htmlspecialchars($inc['incident_type'] ?? '') ?>"
                                data-severity="<?= htmlspecialchars($inc['severity'] ?? '') ?>"><i class="fas fa-edit"></i></button>
                            <button class="btn btn-outline-danger" title="Delete" data-bs-toggle="modal" data-bs-target="#deleteIncidentModal"
                                data-id="<?= (int)$inc['id'] ?>"
                                data-type="<?= htmlspecialchars($inc['incident_type'] ?? '') ?>"><i class="fas fa-trash"></i></button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php break;

    // ═══════════════════════════════════════════════
    // PATROLS — Full CRUD
    // ═══════════════════════════════════════════════
    case 'patrol': ?>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="fw-bold mb-0"><i class="fas fa-walking me-2"></i>Patrol Management</h4>
        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addPatrolModal"><i class="fas fa-plus me-1"></i>Add Patrol</button>
    </div>

    <div class="card-section">
        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle" id="patrolsTable">
                <thead class="table-light">
                    <tr><th>#</th><th>Officer</th><th>Area</th><th>Start</th><th>End</th><th>Findings</th><th>Status</th><th>Actions</th></tr>
                </thead>
                <tbody>
                <?php if (empty($all_patrols)): ?>
                    <tr><td colspan="8" class="text-center text-muted py-4">No patrol records found.</td></tr>
                <?php else: ?>
                <?php foreach ($all_patrols as $pat):
                    $pSt = strtolower($pat['status'] ?? 'scheduled');
                    $pBadge = ($pSt === 'active' || $pSt === 'in progress') ? 'primary' : ($pSt === 'completed' ? 'success' : ($pSt === 'cancelled' ? 'danger' : 'secondary'));
                ?>
                <tr>
                    <td><strong>#<?= (int)$pat['id'] ?></strong></td>
                    <td><?= htmlspecialchars($pat['officer_name'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($pat['patrol_area']) ?></td>
                    <td><small><?= !empty($pat['start_time']) ? date('d M Y, g:i A', strtotime($pat['start_time'])) : '-' ?></small></td>
                    <td><small><?= !empty($pat['end_time']) ? date('d M Y, g:i A', strtotime($pat['end_time'])) : '—' ?></small></td>
                    <td><small><?= htmlspecialchars(substr($pat['findings'] ?? '-', 0, 50)) ?></small></td>
                    <td><span class="badge bg-<?= $pBadge ?>"><?= htmlspecialchars($pat['status']) ?></span></td>
                    <td>
                        <button class="btn btn-outline-primary btn-sm" title="Update" data-bs-toggle="modal" data-bs-target="#editPatrolModal"
                            data-id="<?= (int)$pat['id'] ?>"
                            data-officer="<?= htmlspecialchars($pat['officer_name'] ?? '') ?>"
                            data-area="<?= htmlspecialchars($pat['patrol_area'] ?? '') ?>"
                            data-start="<?= htmlspecialchars($pat['start_time'] ?? '') ?>"
                            data-end="<?= htmlspecialchars($pat['end_time'] ?? '') ?>"
                            data-findings="<?= htmlspecialchars($pat['findings'] ?? '') ?>"
                            data-status="<?= htmlspecialchars($pat['status'] ?? '') ?>"><i class="fas fa-edit"></i></button>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php break;

    // ═══════════════════════════════════════════════
    // VISITORS — Full CRUD
    // ═══════════════════════════════════════════════
    case 'visitors': ?>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="fw-bold mb-0"><i class="fas fa-id-card me-2"></i>Visitor Management</h4>
        <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#addVisitorModal"><i class="fas fa-plus me-1"></i>Check In Visitor</button>
    </div>

    <div class="card-section">
        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle" id="visitorsTable">
                <thead class="table-light">
                    <tr><th>#</th><th>Name</th><th>ID/Passport</th><th>Phone</th><th>Purpose</th><th>Person to Visit</th><th>Date</th><th>Arrival</th><th>Departure</th><th>Status</th><th>Actions</th></tr>
                </thead>
                <tbody>
                <?php if (empty($all_visitors)): ?>
                    <tr><td colspan="11" class="text-center text-muted py-4">No visitor records found.</td></tr>
                <?php else: ?>
                <?php foreach ($all_visitors as $v):
                    $vStBadge = ($v['status'] === 'Checked In') ? 'success' : (($v['status'] === 'On Campus') ? 'primary' : (($v['status'] === 'Checked Out') ? 'secondary' : 'warning'));
                ?>
                <tr>
                    <td><strong>#<?= (int)$v['id'] ?></strong></td>
                    <td><?= htmlspecialchars($v['visitor_name']) ?></td>
                    <td><small><?= htmlspecialchars($v['badge_number'] ?? '-') ?></small></td>
                    <td><small><?= htmlspecialchars($v['visitor_phone'] ?? '-') ?></small></td>
                    <td><span class="badge bg-info"><?= htmlspecialchars($v['visitor_nature']) ?></span></td>
                    <td><small><?= htmlspecialchars($v['person_to_visit_name'] ?? '-') ?></small></td>
                    <td><small><?= !empty($v['visit_date']) ? date('d M Y', strtotime($v['visit_date'])) : '-' ?></small></td>
                    <td><small><?= !empty($v['actual_arrival']) ? date('g:i A', strtotime($v['actual_arrival'])) : '-' ?></small></td>
                    <td><small><?= !empty($v['actual_departure']) ? date('g:i A', strtotime($v['actual_departure'])) : '—' ?></small></td>
                    <td><span class="badge bg-<?= $vStBadge ?>"><?= htmlspecialchars($v['status']) ?></span></td>
                    <td>
                        <?php if ($v['status'] !== 'Checked Out'): ?>
                        <button class="btn btn-outline-warning btn-sm" title="Check Out" data-bs-toggle="modal" data-bs-target="#editVisitorModal"
                            data-id="<?= (int)$v['id'] ?>"
                            data-name="<?= htmlspecialchars($v['visitor_name'] ?? '') ?>"
                            data-status="<?= htmlspecialchars($v['status'] ?? '') ?>"><i class="fas fa-sign-out-alt"></i></button>
                        <?php else: ?>
                        <span class="text-muted small"><i class="fas fa-check-circle"></i> Done</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php break;

    // ═══════════════════════════════════════════════
    // EMERGENCY
    // ═══════════════════════════════════════════════
    case 'emergency': ?>
    <h4 class="fw-bold mb-3"><i class="fas fa-ambulance me-2"></i>Emergency Contacts</h4>
    <div class="card-section">
        <?php if (empty($emergency_contacts)): ?>
        <p class="text-muted text-center py-3">No emergency contacts configured.</p>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-bordered"><thead><tr><th>Name</th><th>Type</th><th>Phone</th><th>Email</th></tr></thead><tbody>
            <?php foreach ($emergency_contacts as $ec): ?>
            <tr><td><?= htmlspecialchars($ec['contact_name']) ?></td><td><span class="badge bg-danger"><?= htmlspecialchars($ec['contact_type']) ?></span></td><td><a href="tel:<?= htmlspecialchars($ec['phone_number']) ?>"><?= htmlspecialchars($ec['phone_number']) ?></a></td><td><?= htmlspecialchars($ec['email'] ?? '-') ?></td></tr>
            <?php endforeach; ?>
            </tbody></table>
        </div>
        <?php endif; ?>
    </div>
    <?php break;

    // ═══════════════════════════════════════════════
    // DEFAULT
    // ═══════════════════════════════════════════════
    default: ?>
    <h4 class="fw-bold mb-3"><i class="fas fa-shield-alt me-2"></i>Security Module</h4>
    <div class="card-section"><p class="text-muted">Select a section from the sidebar.</p></div>
    <?php break;
endswitch; ?>
</div>

<!-- ═══════════════════════════════════════════════════════════════
     MODALS — INCIDENTS
     ═══════════════════════════════════════════════════════════════ -->

<!-- Add Incident -->
<div class="modal fade" id="addIncidentModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
<form method="POST" action="../handlers/security_handler.php">
<?= $csrf_field ?>
<div class="modal-header"><h5 class="modal-title"><i class="fas fa-exclamation-triangle me-2 text-danger"></i>Report Incident</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body">
    <input type="hidden" name="action" value="add_incident">
    <div class="mb-3"><label class="form-label">Incident Type *</label><select class="form-select" name="incident_type" required><option value="">Select...</option><option value="Unauthorized Access">Unauthorized Access</option><option value="Theft">Theft</option><option value="Vandalism">Vandalism</option><option value="Assault">Assault</option><option value="Parking Violation">Parking Violation</option><option value="Fire">Fire</option><option value="Trespassing">Trespassing</option><option value="Suspicious Activity">Suspicious Activity</option><option value="Emergency">Emergency</option><option value="Other">Other</option></select></div>
    <div class="mb-3"><label class="form-label">Location *</label><input class="form-control" name="location" required placeholder="e.g. Building A, Main Gate"></div>
    <div class="mb-3"><label class="form-label">Severity</label><select class="form-select" name="severity"><option value="Low">Low</option><option value="Medium" selected>Medium</option><option value="High">High</option><option value="Critical">Critical</option></select></div>
    <div class="mb-3"><label class="form-label">Description *</label><textarea class="form-control" name="description" rows="3" required placeholder="Describe the incident..."></textarea></div>
</div>
<div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-danger"><i class="fas fa-paper-plane me-1"></i>Submit Report</button></div>
</form></div></div></div>

<!-- Edit Incident -->
<div class="modal fade" id="editIncidentModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
<form method="POST" action="../handlers/security_handler.php">
<?= $csrf_field ?>
<div class="modal-header"><h5 class="modal-title"><i class="fas fa-edit me-2 text-primary"></i>Update Incident</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body">
    <input type="hidden" name="action" value="update_incident">
    <input type="hidden" name="id" id="editIncidentId">
    <p class="text-muted mb-2"><strong>Incident:</strong> <span id="editIncidentType"></span></p>
    <div class="mb-3"><label class="form-label">Status *</label><select class="form-select" name="status" id="editIncidentStatus" required><option value="Reported">Reported</option><option value="In Progress">In Progress</option><option value="Under Investigation">Under Investigation</option><option value="Resolved">Resolved</option><option value="Closed">Closed</option></select></div>
    <div class="mb-3"><label class="form-label">Resolution Notes</label><textarea class="form-control" name="resolution_notes" id="editIncidentNotes" rows="3" placeholder="Resolution details..."></textarea></div>
</div>
<div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Update</button></div>
</form></div></div></div>

<!-- Delete Incident -->
<div class="modal fade" id="deleteIncidentModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
<form method="POST" action="../handlers/security_handler.php">
<?= $csrf_field ?>
<div class="modal-header bg-danger text-white"><h5 class="modal-title"><i class="fas fa-trash me-2"></i>Delete Incident</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
<div class="modal-body">
    <input type="hidden" name="action" value="delete_incident">
    <input type="hidden" name="id" id="deleteIncidentId">
    <p>Are you sure you want to delete incident <strong>#<span id="deleteIncidentIdDisplay"></span></strong>?</p>
    <p class="text-muted mb-0">Type: <span id="deleteIncidentType"></span></p>
    <p class="text-danger small mt-2 mb-0"><i class="fas fa-exclamation-triangle"></i> This action cannot be undone.</p>
</div>
<div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-danger"><i class="fas fa-trash me-1"></i>Delete</button></div>
</form></div></div></div>

<!-- ═══════════════════════════════════════════════════════════════
     MODALS — PATROLS
     ═══════════════════════════════════════════════════════════════ -->

<!-- Add Patrol -->
<div class="modal fade" id="addPatrolModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
<form method="POST" action="../handlers/security_handler.php">
<?= $csrf_field ?>
<div class="modal-header"><h5 class="modal-title"><i class="fas fa-walking me-2 text-primary"></i>Add Patrol Record</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body">
    <input type="hidden" name="action" value="add_patrol">
    <div class="mb-3"><label class="form-label">Officer</label><select class="form-select" name="officer_id">
        <?php foreach ($staff_list as $sl): ?>
        <option value="<?= (int)$sl['id'] ?>" <?= $sl['id'] == $user_id ? 'selected' : '' ?>><?= htmlspecialchars($sl['full_name']) ?></option>
        <?php endforeach; ?>
    </select></div>
    <div class="mb-3"><label class="form-label">Officer Name</label><input class="form-control" name="officer_name" value="<?= htmlspecialchars($user_name) ?>"></div>
    <div class="mb-3"><label class="form-label">Patrol Area *</label><input class="form-control" name="patrol_area" required placeholder="e.g. North Campus, Main Gate, Parking Lot"></div>
    <div class="mb-3"><label class="form-label">Start Time</label><input type="datetime-local" class="form-control" name="start_time" value="<?= date('Y-m-d\TH:i') ?>"></div>
    <div class="mb-3"><label class="form-label">Status</label><select class="form-select" name="status"><option value="Scheduled">Scheduled</option><option value="In Progress" selected>In Progress</option><option value="Active">Active</option></select></div>
    <div class="mb-3"><label class="form-label">Initial Findings</label><textarea class="form-control" name="findings" rows="2" placeholder="Initial observations..."></textarea></div>
</div>
<div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary"><i class="fas fa-plus me-1"></i>Add Patrol</button></div>
</form></div></div></div>

<!-- Edit Patrol -->
<div class="modal fade" id="editPatrolModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
<form method="POST" action="../handlers/security_handler.php">
<?= $csrf_field ?>
<div class="modal-header"><h5 class="modal-title"><i class="fas fa-edit me-2 text-primary"></i>Update Patrol</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body">
    <input type="hidden" name="action" value="update_patrol">
    <input type="hidden" name="id" id="editPatrolId">
    <p class="text-muted mb-2"><strong>Patrol #<span id="editPatrolIdDisplay"></span></strong> — <span id="editPatrolArea"></span></p>
    <div class="mb-3"><label class="form-label">End Time</label><input type="datetime-local" class="form-control" name="end_time" id="editPatrolEndTime"></div>
    <div class="mb-3"><label class="form-label">Status *</label><select class="form-select" name="status" id="editPatrolStatus" required><option value="In Progress">In Progress</option><option value="Active">Active</option><option value="Completed">Completed</option><option value="Cancelled">Cancelled</option></select></div>
    <div class="mb-3"><label class="form-label">Findings</label><textarea class="form-control" name="findings" id="editPatrolFindings" rows="3" placeholder="Patrol findings..."></textarea></div>
</div>
<div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Update</button></div>
</form></div></div></div>

<!-- ═══════════════════════════════════════════════════════════════
     MODALS — VISITORS
     ═══════════════════════════════════════════════════════════════ -->

<!-- Add Visitor (Check In) -->
<div class="modal fade" id="addVisitorModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
<form method="POST" action="../handlers/security_handler.php">
<?= $csrf_field ?>
<div class="modal-header"><h5 class="modal-title"><i class="fas fa-user-plus me-2 text-success"></i>Check In Visitor</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body">
    <input type="hidden" name="action" value="add_visitor">
    <div class="mb-3"><label class="form-label">Visitor Name *</label><input class="form-control" name="visitor_name" required placeholder="Full name"></div>
    <div class="row"><div class="col-md-6 mb-3"><label class="form-label">ID Number</label><input class="form-control" name="id_number" placeholder="National ID / Passport"></div>
    <div class="col-md-6 mb-3"><label class="form-label">Phone</label><input class="form-control" name="phone" placeholder="Phone number"></div></div>
    <div class="mb-3"><label class="form-label">Purpose *</label><select class="form-select" name="purpose" required><option value="">Select...</option><option value="Meeting">Meeting</option><option value="Delivery">Delivery</option><option value="Interview">Interview</option><option value="Maintenance">Maintenance</option><option value="Official Visit">Official Visit</option><option value="Personal Visit">Personal Visit</option><option value="Other">Other</option></select></div>
    <div class="mb-3"><label class="form-label">Person to Visit</label><input class="form-control" name="person_to_visit" placeholder="Staff/Student name or department"></div>
    <div class="mb-3"><label class="form-label">Check-In Time</label><input type="datetime-local" class="form-control" name="check_in_time" value="<?= date('Y-m-d\TH:i') ?>"></div>
</div>
<div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-success"><i class="fas fa-sign-in-alt me-1"></i>Check In</button></div>
</form></div></div></div>

<!-- Edit Visitor (Check Out) -->
<div class="modal fade" id="editVisitorModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
<form method="POST" action="../handlers/security_handler.php">
<?= $csrf_field ?>
<div class="modal-header"><h5 class="modal-title"><i class="fas fa-sign-out-alt me-2 text-warning"></i>Check Out Visitor</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body">
    <input type="hidden" name="action" value="update_visitor">
    <input type="hidden" name="id" id="editVisitorId">
    <p class="text-muted mb-2"><strong>Visitor:</strong> <span id="editVisitorName"></span></p>
    <div class="mb-3"><label class="form-label">Check-Out Time</label><input type="datetime-local" class="form-control" name="check_out_time" value="<?= date('Y-m-d\TH:i') ?>"></div>
    <div class="mb-3"><label class="form-label">Status</label><select class="form-select" name="status"><option value="Checked Out">Checked Out</option><option value="No Show">No Show</option></select></div>
</div>
<div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-warning"><i class="fas fa-sign-out-alt me-1"></i>Check Out</button></div>
</form></div></div></div>

<!-- Emergency Contacts Modal -->
<div class="modal fade" id="emergencyModal" tabindex="-1"><div class="modal-dialog modal-lg"><div class="modal-content">
<div class="modal-header"><h5 class="modal-title"><i class="fas fa-phone-alt me-2"></i>Emergency Contacts</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body">
<?php if (empty($emergency_contacts)): ?>
<p class="text-muted">No emergency contacts configured.</p>
<?php else: ?>
<div class="table-responsive"><table class="table table-bordered"><thead><tr><th>Name</th><th>Type</th><th>Phone</th><th>Email</th></tr></thead><tbody>
<?php foreach ($emergency_contacts as $ec): ?>
<tr><td><?= htmlspecialchars($ec['contact_name']) ?></td><td><span class="badge bg-danger"><?= htmlspecialchars($ec['contact_type']) ?></span></td><td><a href="tel:<?= htmlspecialchars($ec['phone_number']) ?>"><?= htmlspecialchars($ec['phone_number']) ?></a></td><td><?= htmlspecialchars($ec['email'] ?? '-') ?></td></tr>
<?php endforeach; ?>
</tbody></table></div>
<?php endif; ?>
</div>
</div></div></div>

<script>
document.addEventListener('DOMContentLoaded',function(){
    document.querySelectorAll('[data-bs-target="#editIncidentModal"]').forEach(function(btn){
        btn.addEventListener('click',function(){
            document.getElementById('editIncidentId').value = this.dataset.id;
            document.getElementById('editIncidentType').textContent = this.dataset.type + ' (' + this.dataset.severity + ')';
            document.getElementById('editIncidentStatus').value = this.dataset.status;
            document.getElementById('editIncidentNotes').value = this.dataset.resolution || '';
        });
    });
    document.querySelectorAll('[data-bs-target="#deleteIncidentModal"]').forEach(function(btn){
        btn.addEventListener('click',function(){
            document.getElementById('deleteIncidentId').value = this.dataset.id;
            document.getElementById('deleteIncidentIdDisplay').textContent = this.dataset.id;
            document.getElementById('deleteIncidentType').textContent = this.dataset.type;
        });
    });
    document.querySelectorAll('[data-bs-target="#editPatrolModal"]').forEach(function(btn){
        btn.addEventListener('click',function(){
            document.getElementById('editPatrolId').value = this.dataset.id;
            document.getElementById('editPatrolIdDisplay').textContent = this.dataset.id;
            document.getElementById('editPatrolArea').textContent = this.dataset.area;
            var et = this.dataset.end;
            if(et){ document.getElementById('editPatrolEndTime').value = et.substring(0,16); }
            else { document.getElementById('editPatrolEndTime').value = new Date().toISOString().substring(0,16); }
            document.getElementById('editPatrolStatus').value = this.dataset.status;
            document.getElementById('editPatrolFindings').value = this.dataset.findings || '';
        });
    });
    document.querySelectorAll('[data-bs-target="#editVisitorModal"]').forEach(function(btn){
        btn.addEventListener('click',function(){
            document.getElementById('editVisitorId').value = this.dataset.id;
            document.getElementById('editVisitorName').textContent = this.dataset.name;
        });
    });
});
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
</body>
</html>
