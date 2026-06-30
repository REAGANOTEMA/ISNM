<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
require_once __DIR__ . '/../includes/enterprise_auth.php';

$ctx = bootstrapStaffDashboard(['security officer']);
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
    'visitor-registration'  => 'visitor-registration',
    'visitor-history'       => 'visitor-history',
    'visitor-exit'          => 'visitor-exit',
    'vehicle-entry'         => 'vehicle-entry',
    'vehicle-exit'          => 'vehicle-exit',
    'incidents'             => 'incidents',
    'emergency'             => 'emergency',
    'blacklist'             => 'blacklist',
    'visitor-pass'          => 'visitor-pass',
    'patrol'                => 'patrol',
];
$page  = $_GET['page'] ?? 'home';
$section = $pageToSection[$page] ?? 'overview';

// Get security statistics
$total_incidents_today = ($conn && ($q = $conn->query("SELECT COUNT(*) FROM security_incidents WHERE DATE(incident_date) = CURDATE()")) && ($r = $q->fetch_row())) ? (int) $r[0] : 0;
$security_patrols = ($conn && ($q = $conn->query("SELECT COUNT(*) FROM security_patrols WHERE patrol_date = CURDATE() AND status IN ('Scheduled','In Progress')")) && ($r = $q->fetch_row())) ? (int) $r[0] : 0;
$access_control_checks = ($conn && ($q = $conn->query("SELECT COUNT(*) FROM access_control_logs WHERE DATE(access_time) = CURDATE()")) && ($r = $q->fetch_row())) ? (int) $r[0] : 0;
$emergency_alerts = ($conn && ($q = $conn->query("SELECT COUNT(*) FROM security_incidents WHERE severity = 'Critical' AND DATE(incident_date) = CURDATE()")) && ($r = $q->fetch_row())) ? (int) $r[0] : 0;
$cctv_cameras_active = ($conn && ($q = $conn->query("SELECT COUNT(*) FROM security_equipment WHERE equipment_type = 'CCTV Camera' AND status = 'Operational'")) && ($r = $q->fetch_row())) ? (int) $r[0] : 0;
$total_guards = ($conn && ($q = $conn->query("SELECT COUNT(*) FROM security_patrols WHERE patrol_date = CURDATE()")) && ($r = $q->fetch_row())) ? (int) $r[0] : 0;

$recent_incidents = [];
if ($conn) { try { $result = $conn->query("SELECT incident_type, description, location, reported_at, status, severity FROM security_incidents ORDER BY reported_at DESC LIMIT 10"); if ($result) { while ($row = $result->fetch_assoc()) { $recent_incidents[] = $row; } } } catch (Exception $e) {} }

$active_patrols = [];
if ($conn) { try { $r = $conn->query("SELECT sp.*, s.full_name as guard_name FROM security_patrols sp LEFT JOIN staff s ON sp.guard_id=s.id WHERE sp.patrol_date=CURDATE() ORDER BY sp.start_time LIMIT 10"); if ($r) $active_patrols = $r->fetch_all(MYSQLI_ASSOC); } catch (Exception $e) {} }

$emergency_contacts = [];
if ($conn) { try { $r = $conn->query("SELECT * FROM security_emergency_contacts WHERE is_active=1 ORDER BY contact_type"); if ($r) $emergency_contacts = $r->fetch_all(MYSQLI_ASSOC); } catch (Exception $e) {} }

$equipment_due = [];
if ($conn) { try { $r = $conn->query("SELECT * FROM security_equipment WHERE status!='Retired' AND (next_maintenance_date <= CURDATE() OR next_maintenance_date IS NULL) ORDER BY next_maintenance_date ASC LIMIT 5"); if ($r) $equipment_due = $r->fetch_all(MYSQLI_ASSOC); } catch (Exception $e) {} }

$today_visitors = []; $visitor_count = 0;
if ($conn) { try { $r = $conn->query("SELECT * FROM security_visitors WHERE visit_date = CURDATE() ORDER BY expected_arrival ASC LIMIT 20"); if ($r) $today_visitors = $r->fetch_all(MYSQLI_ASSOC); $visitor_count = (int)(($r=$conn->query("SELECT COUNT(*) FROM security_visitors WHERE visit_date = CURDATE()"))&&$r?$r->fetch_row()[0]:0); } catch (Exception $e) {} }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include_once __DIR__ . '/../includes/dashboard_head.php'; ?>
<style>
.secu-topbar{background:linear-gradient(135deg,#991b1b,#7f1d1d,#450a0a);padding:0 32px;height:64px;display:flex;align-items:center;position:sticky;top:0;z-index:100;box-shadow:0 2px 12px rgba(0,0,0,.15)}.secu-topbar-content{width:100%;display:flex;align-items:center;justify-content:space-between}.secu-topbar-left{display:flex;flex-direction:column}.secu-topbar-title{color:#fff;font-size:18px;font-weight:700;letter-spacing:.3px}.secu-topbar-subtitle{color:#fca5a5;font-size:12px;margin-top:-2px}.secu-topbar-right{display:flex;align-items:center;gap:12px}.secu-date-badge{background:rgba(255,255,255,.15);color:#fff;padding:6px 14px;border-radius:20px;font-size:12px;font-weight:500;backdrop-filter:blur(4px)}.secu-print-btn,.secu-logout-btn{color:#fca5a5;font-size:16px;padding:6px 10px;border-radius:8px;transition:all .2s;text-decoration:none}.secu-print-btn:hover,.secu-logout-btn:hover{background:rgba(255,255,255,.2);color:#fff}
.secu-content{margin-left:270px;padding:24px;min-height:100vh}
@media(max-width:768px){.secu-content{margin-left:0!important;padding:12px!important}}
</style>
</head>
<body class="ent-layout">
<?php include_once __DIR__ . '/../includes/sidebar.php'; ?>
<div class="secu-topbar"><div class="secu-topbar-content"><div class="secu-topbar-left"><div class="secu-topbar-title">Security</div><div class="secu-topbar-subtitle">Campus Security &amp; Access Control</div></div><div class="secu-topbar-right"><span class="secu-date-badge"><i class="fas fa-calendar-alt me-1"></i><?= date('l, F j, Y') ?></span><a href="#" class="secu-print-btn" onclick="window.print()"><i class="fas fa-print"></i></a><a href="../auth-handler.php?action=logout" class="secu-logout-btn"><i class="fas fa-sign-out-alt"></i></a></div></div></div>
<div class="secu-content">
<?php switch ($section):
    case 'overview': ?>
    <div class="container">
        <div class="row mb-4">
            <div class="col-md-3"><div class="stat-card"><h3><i class="fas fa-users"></i> Guards</h3><div class="stat-number"><?php echo $total_guards; ?></div><p class="text-muted mb-0">On Duty Today</p></div></div>
            <div class="col-md-3"><div class="stat-card"><h3><i class="fas fa-exclamation-triangle"></i> Incidents</h3><div class="stat-number"><?php echo $total_incidents_today; ?></div><p class="text-muted mb-0">Reported Today</p></div></div>
            <div class="col-md-3"><div class="stat-card"><h3><i class="fas fa-video"></i> Cameras</h3><div class="stat-number"><?php echo $cctv_cameras_active; ?></div><p class="text-muted mb-0">Active Monitoring</p></div></div>
            <div class="col-md-3"><div class="stat-card"><h3><i class="fas fa-door-open"></i> Access</h3><div class="stat-number"><?php echo $access_control_checks; ?></div><p class="text-muted mb-0">Entries Today</p></div></div>
        </div>
        <div class="security-alert"><h3><i class="fas fa-bell"></i> Recent Security Alerts</h3>
            <?php if (empty($recent_incidents)): ?>
            <div class="text-center text-muted py-4"><i class="fas fa-check-circle fa-2x text-success mb-2"></i><p>No recent incidents</p></div>
            <?php else: ?>
            <?php foreach (array_slice($recent_incidents, 0, 5) as $inc): $sev = strtolower($inc['severity'] ?? 'low'); $sevClass = $sev === 'critical' || $sev === 'high' ? 'high' : ($sev === 'medium' ? 'medium' : 'low'); ?>
            <div class="alert-item <?= $sevClass ?>"><div class="d-flex justify-content-between align-items-center"><div><h6><i class="fas fa-<?= $sevClass === 'high' ? 'exclamation-circle' : ($sevClass === 'medium' ? 'exclamation-triangle' : 'info-circle') ?>"></i> <?= htmlspecialchars($inc['incident_type'] ?? 'Incident') ?></h6><small class="text-muted"><?= htmlspecialchars($inc['location'] ?? '-') ?> , <?= !empty($inc['reported_at']) ? date('g:i A', strtotime($inc['reported_at'])) : '-' ?> | <?= htmlspecialchars(substr($inc['description'] ?? '-', 0, 80)) ?></small></div><span class="badge bg-<?= $sevClass === 'high' ? 'danger' : ($sevClass === 'medium' ? 'warning text-dark' : 'success') ?>"><?= ucfirst(htmlspecialchars($inc['severity'] ?? 'Low')) ?> Priority</span></div></div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <div class="row">
            <div class="col-md-6">
                <div class="security-alert"><h3><i class="fas fa-walking"></i> Current Patrol Status</h3>
                    <?php if (empty($active_patrols)): ?>
                    <div class="text-center text-muted py-3">No patrol records for today</div>
                    <?php else: ?>
                    <?php foreach ($active_patrols as $pat): $pStatus = strtolower($pat['status'] ?? 'scheduled'); ?>
                    <div class="alert-item"><div class="d-flex justify-content-between align-items-center"><div><h6><?= htmlspecialchars($pat['guard_name'] ?? 'Guard') ?></h6><small class="text-muted"><?= htmlspecialchars($pat['location'] ?? $pat['patrol_area'] ?? '-') ?> | Started: <?= !empty($pat['start_time']) ? date('g:i A', strtotime($pat['start_time'])) : '-' ?></small></div><span class="patrol-status status-<?= $pStatus === 'active' ? 'active' : ($pStatus === 'break' ? 'break' : 'inactive') ?>"><?= ucfirst(htmlspecialchars($pStatus)) ?></span></div></div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-md-6">
                <div class="security-alert"><h3><i class="fas fa-tasks"></i> Equipment Status</h3>
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
                <div class="security-alert"><h3><i class="fas fa-address-book"></i> Today's Visitors (<?= $visitor_count ?>)</h3>
                    <?php if (empty($today_visitors)): ?>
                    <div class="text-center text-muted py-3">No visitors scheduled for today</div>
                    <?php else: ?>
                    <div class="table-responsive"><table class="table table-sm table-hover"><thead><tr><th>Name</th><th>Phone</th><th>Nature</th><th>Person to Visit</th><th>Arrival</th><th>Status</th></tr></thead><tbody>
                    <?php foreach ($today_visitors as $v): $vs = $v['status']; switch($vs) { case 'Checked In': $vs_badge = 'success'; break; case 'On Campus': $vs_badge = 'primary'; break; case 'Checked Out': $vs_badge = 'secondary'; break; case 'Scheduled': $vs_badge = 'warning'; break; case 'No Show': $vs_badge = 'danger'; break; default: $vs_badge = 'secondary'; } ?>
                    <tr><td><strong><?= htmlspecialchars($v['visitor_name']) ?></strong></td><td><small><?= htmlspecialchars($v['visitor_phone'] ?? '-') ?></small></td><td><span class="badge bg-info"><?= htmlspecialchars($v['visitor_nature']) ?></span></td><td><small><?= htmlspecialchars($v['person_to_visit_name'] ?? '-') ?></small></td><td><small><?= !empty($v['expected_arrival']) ? date('g:i A', strtotime($v['expected_arrival'])) : '-' ?></small></td><td><span class="badge bg-<?= $vs_badge ?>"><?= htmlspecialchars($v['status']) ?></span></td></tr>
                    <?php endforeach; ?>
                    </tbody></table></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="row mt-4">
            <div class="col-md-12">
                <div class="security-alert"><h3><i class="fas fa-bolt"></i> Quick Actions</h3>
                    <div class="row">
                        <div class="col-md-3 mb-2"><button class="btn btn-danger w-100" data-bs-toggle="modal" data-bs-target="#emergencyModal"><i class="fas fa-phone"></i> Emergency Contact</button></div>
                        <div class="col-md-3 mb-2"><button class="btn btn-warning w-100" data-bs-toggle="modal" data-bs-target="#incidentModal"><i class="fas fa-exclamation-triangle"></i> Report Incident</button></div>
                        <div class="col-md-3 mb-2"><button class="btn btn-info w-100" onclick="document.getElementById('cameraSection')?.scrollIntoView({behavior:'smooth'})"><i class="fas fa-video"></i> View Equipment</button></div>
                        <div class="col-md-3 mb-2"><button class="btn btn-success w-100" onclick="window.print()"><i class="fas fa-clipboard"></i> Daily Report</button></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
        <?php break;
    case 'incidents': ?>
    <h2><i class="fas fa-exclamation-triangle me-2"></i>Incident Reports</h2>
    <p class="text-muted">All security incidents will be listed here.</p>
        <?php break;
    case 'visitor-registration': ?>
    <h2><i class="fas fa-user-plus me-2"></i>Visitor Registration</h2>
    <p class="text-muted">Register new visitors and issue passes.</p>
        <?php break;
    case 'emergency': ?>
    <h2><i class="fas fa-ambulance me-2"></i>Emergency Contacts</h2>
    <p class="text-muted">Emergency contact management and escalation.</p>
        <?php break;
    default: ?>
    <h2><i class="fas fa-shield-alt me-2"></i>Security Module</h2>
    <p class="text-muted">Module content coming soon.</p>
        <?php break;
endswitch; ?>
</div>

<div class="modal fade" id="emergencyModal" tabindex="-1"><div class="modal-dialog modal-lg"><div class="modal-content"><div class="modal-header"><h5 class="modal-title"><i class="fas fa-phone-alt me-2"></i>Emergency Contacts</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><?php if (empty($emergency_contacts)): ?><p class="text-muted">No emergency contacts configured.</p><?php else: ?><div class="table-responsive"><table class="table table-bordered"><thead><tr><th>Name</th><th>Type</th><th>Phone</th><th>Email</th></tr></thead><tbody><?php foreach ($emergency_contacts as $ec): ?><tr><td><?= htmlspecialchars($ec['contact_name']) ?></td><td><span class="badge bg-danger"><?= htmlspecialchars($ec['contact_type']) ?></span></td><td><a href="tel:<?= htmlspecialchars($ec['phone_number']) ?>"><?= htmlspecialchars($ec['phone_number']) ?></a></td><td><?= htmlspecialchars($ec['email'] ?? '-') ?></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?></div></div></div></div>
<div class="modal fade" id="incidentModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content"><form method="POST" action="../handlers/security_handler.php"><div class="modal-header"><h5 class="modal-title"><i class="fas fa-exclamation-triangle me-2"></i>Report Incident</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><input type="hidden" name="action" value="report_incident"><div class="mb-3"><label class="form-label">Incident Type</label><select class="form-select" name="incident_type" required><option value="">Select...</option><option value="Unauthorized Access">Unauthorized Access</option><option value="Theft">Theft</option><option value="Vandalism">Vandalism</option><option value="Assault">Assault</option><option value="Parking Violation">Parking Violation</option><option value="Emergency">Emergency</option><option value="Other">Other</option></select></div><div class="mb-3"><label class="form-label">Location</label><input class="form-control" name="location" required></div><div class="mb-3"><label class="form-label">Severity</label><select class="form-select" name="severity"><option value="Low">Low</option><option value="Medium" selected>Medium</option><option value="High">High</option><option value="Critical">Critical</option></select></div><div class="mb-3"><label class="form-label">Description</label><textarea class="form-control" name="description" rows="3" required></textarea></div></div><div class="modal-footer"><button type="submit" class="btn btn-danger"><i class="fas fa-paper-plane"></i> Submit Report</button></div></form></div></div></div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>

<?php include_once __DIR__ . '/../includes/enterprise_control_panel.php'; ?>
</body>
</html>
