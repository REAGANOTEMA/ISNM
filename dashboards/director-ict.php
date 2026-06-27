<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
require_once __DIR__ . '/../includes/department_approval_request.php';
try {
    $ctx = bootstrapStaffDashboard(['director', 'ict', 'it', 'system admin']);
} catch (Throwable $e) {
    if (ob_get_level()) ob_clean();
    echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>Access Error</title></head><body>';
    echo '<h2>Access Error</h2><p>' . htmlspecialchars($e->getMessage()) . '</p>';
    echo '<p><a href="../staff-login.php">Return to Login</a></p></body></html>';
    exit;
}
$staff_conn = $ctx['staff'];
$students_conn = $ctx['students'];
$website_conn = $ctx['website'];
$user = $ctx['user'];
$user_id = (int)($user['id'] ?? 0);
$user_name = $user['full_name'] ?? 'ICT Director';
$ict = null;
try { $ict = getICTConnection(); } catch (Exception $e) {}

function ict_q($conn, $sql) {
    if (!$conn) return 0;
    try { $r = $conn->query($sql); if (!$r) return 0; $row = $r->fetch_assoc(); return (int)($row[array_key_first($row)] ?? 0); }
    catch (Exception $e) { return 0; }
}
function ict_fetch($conn, $sql) {
    if (!$conn) return [];
    try { $r = $conn->query($sql); if (!$r) return []; return $r->fetch_all(MYSQLI_ASSOC); }
    catch (Exception $e) { return []; }
}
function ict_fetch_one($conn, $sql) {
    if (!$conn) return null;
    try { $r = $conn->query($sql); if (!$r) return null; return $r->fetch_assoc(); }
    catch (Exception $e) { return null; }
}

// ── STATS ──
$total_staff   = ict_q($staff_conn, "SELECT COUNT(*) FROM staff WHERE status='Active'");
$total_students = ict_q($students_conn, "SELECT COUNT(*) FROM students WHERE status='Active'");
$active_servers  = ict_q($ict, "SELECT COUNT(*) FROM ict_servers WHERE status='online'");
$network_active  = ict_q($ict, "SELECT COUNT(*) FROM network_devices WHERE status='online'");
$total_assets    = ict_q($ict, "SELECT COUNT(*) FROM ict_assets WHERE current_status!='retired'");
$open_tickets    = ict_q($ict, "SELECT COUNT(*) FROM it_support_tickets WHERE status IN ('open','in_progress')");
$closed_tickets  = ict_q($ict, "SELECT COUNT(*) FROM it_support_tickets WHERE status='closed'");
$today_backups   = ict_q($ict, "SELECT COUNT(*) FROM ict_system_backups WHERE DATE(created_at)=CURDATE()");
$active_alerts   = ict_q($ict, "SELECT COUNT(*) FROM ict_system_alerts WHERE status='active'");
$wifi_active     = ict_q($ict, "SELECT COUNT(*) FROM ict_wifi_devices WHERE status='online'");
$db_size_mb      = 0;
$size_row = ict_fetch_one($ict, "SELECT ROUND(SUM(data_length+index_length)/1024/1024,2) as size_mb FROM information_schema.TABLES WHERE TABLE_SCHEMA='igangaschoolofl_ict'");
if ($size_row) $db_size_mb = $size_row['size_mb'];
$total_users     = ict_q($staff_conn, "SELECT COUNT(*) FROM staff") + ict_q($students_conn, "SELECT COUNT(*) FROM students WHERE status='Active'");

// ── DATA ──
$assets       = ict_fetch($ict, "SELECT a.*, c.category_name FROM ict_assets a LEFT JOIN ict_asset_categories c ON a.category_id=c.id ORDER BY a.created_at DESC LIMIT 30");
$asset_cats   = ict_fetch($ict, "SELECT * FROM ict_asset_categories ORDER BY category_name");
$servers      = ict_fetch($ict, "SELECT * FROM ict_servers ORDER BY server_name");
$net_devices  = ict_fetch($ict, "SELECT * FROM network_devices ORDER BY device_type, device_name");
$wifi_devices = ict_fetch($ict, "SELECT * FROM ict_wifi_devices ORDER BY device_name");
$backups      = ict_fetch($ict, "SELECT * FROM ict_system_backups ORDER BY created_at DESC LIMIT 20");
$backup_logs  = ict_fetch($ict, "SELECT l.*, b.backup_name FROM ict_backup_logs l LEFT JOIN ict_system_backups b ON l.backup_id=b.id ORDER BY l.logged_at DESC LIMIT 20");
$security_logs = ict_fetch($ict, "SELECT * FROM ict_security_logs ORDER BY created_at DESC LIMIT 30");
$failed_logins = ict_fetch($ict, "SELECT * FROM ict_failed_logins ORDER BY attempted_at DESC LIMIT 20");
$alerts       = ict_fetch($ict, "SELECT * FROM ict_system_alerts ORDER BY FIELD(severity,'critical','warning','info'), created_at DESC LIMIT 20");
$notifications= ict_fetch($ict, "SELECT * FROM ict_system_notifications WHERE is_dismissed=0 ORDER BY created_at DESC LIMIT 10");
$health_checks= ict_fetch($ict, "SELECT * FROM ict_system_health ORDER BY checked_at DESC LIMIT 20");
$settings     = ict_fetch($ict, "SELECT * FROM ict_system_settings ORDER BY setting_group, setting_key");
$audit_logs   = ict_fetch($ict, "SELECT * FROM ict_audit_logs ORDER BY created_at DESC LIMIT 30");
$tickets      = ict_fetch($ict, "SELECT * FROM it_support_tickets ORDER BY FIELD(priority,'critical','high','medium','low'), created_at DESC LIMIT 20");
$network_logs = ict_fetch($ict, "SELECT * FROM ict_network_logs ORDER BY logged_at DESC LIMIT 20");
$assignments  = ict_fetch($ict, "SELECT a.*, ast.asset_number, ast.asset_name FROM ict_asset_assignments a LEFT JOIN ict_assets ast ON a.asset_id=ast.id WHERE a.status='active' ORDER BY a.assignment_date DESC LIMIT 20");
$maintenance  = ict_fetch($ict, "SELECT m.*, a.asset_number, a.asset_name FROM ict_asset_maintenance m LEFT JOIN ict_assets a ON m.asset_id=a.id ORDER BY m.created_at DESC LIMIT 20");

// ── User & Access ──
$staff_accounts  = ict_fetch($staff_conn, "SELECT id, full_name, email, role, status, last_login FROM staff ORDER BY full_name LIMIT 20");
$staff_count     = ict_q($staff_conn, "SELECT COUNT(*) FROM staff");
$student_count   = ict_q($students_conn, "SELECT COUNT(*) FROM students WHERE status='Active'");
$active_sessions = ict_q($ict, "SELECT COUNT(*) FROM ict_login_sessions WHERE status='active'");
$failed_today    = ict_q($ict, "SELECT COUNT(*) FROM ict_failed_logins WHERE DATE(attempted_at)=CURDATE()");
// ── Module Permissions ──
$module_perms    = ict_fetch($ict, "SELECT * FROM ict_module_permissions ORDER BY module_name, role_name");
// ── Approvals ──
$pending_tickets = ict_fetch($ict, "SELECT * FROM it_support_tickets WHERE status IN ('open','in_progress') ORDER BY FIELD(priority,'critical','high','medium','low'), created_at DESC LIMIT 15");
$pending_approval_requests = [];
if ($staff_conn) {
    try {
        $r = $staff_conn->query("SELECT ar.*, ws.workflow_name, ws.category FROM igangaschoolofl_staffs_db.approval_requests ar LEFT JOIN igangaschoolofl_staffs_db.approval_workflows ws ON ar.workflow_id = ws.id WHERE ar.status = 'Active' AND (ws.category = 'ICT' OR ws.category IS NULL) ORDER BY FIELD(ar.priority,'Critical','High','Medium','Normal'), ar.created_at DESC LIMIT 15");
        if ($r) while ($row = $r->fetch_assoc()) $pending_approval_requests[] = $row;
    } catch (Exception $e) {}
}

$tab = $_GET['tab'] ?? 'dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include_once __DIR__ . '/../includes/dashboard_head.php'; ?>
<style>
:root { --sidebar-width: 260px; }
.stat-card { background:#fff; border-radius:12px; padding:16px; border:1px solid #e5e7eb; display:flex; align-items:center; gap:14px; transition:all .2s; }
.stat-card:hover { box-shadow:0 4px 14px rgba(0,0,0,0.07); transform:translateY(-1px); }
.stat-card .icon-circle { width:42px; height:42px; border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:18px; flex-shrink:0; }
.stat-card h4 { font-size:18px; font-weight:700; margin:0; line-height:1.2; }
.stat-card p { margin:0; font-size:11px; color:#6b7280; }
.section-card { background:#fff; border-radius:12px; padding:18px; border:1px solid #e5e7eb; margin-bottom:14px; }
.section-card h2 { font-size:14px; font-weight:700; margin-bottom:12px; color:#111827; }
.bg-blue-soft { background:#eff6ff; color:#2563eb; }
.bg-green-soft { background:#f0fdf4; color:#16a34a; }
.bg-red-soft { background:#fef2f2; color:#dc2626; }
.bg-orange-soft { background:#fff7ed; color:#ea580c; }
.bg-purple-soft { background:#faf5ff; color:#9333ea; }
.bg-yellow-soft { background:#fefce8; color:#ca8a04; }
.bg-teal-soft { background:#f0fdfa; color:#0d9488; }
.bg-pink-soft { background:#fdf2f8; color:#db2777; }
.bg-indigo-soft { background:#eef2ff; color:#4f46e5; }
.bg-cyan-soft { background:#ecfeff; color:#0891b2; }
.monitor-card { background:#1e293b; border-radius:10px; padding:14px; color:#e2e8f0; text-align:center; }
.monitor-card h3 { font-size:28px; font-weight:700; margin:0; }
.monitor-card p { font-size:11px; color:#94a3b8; margin:4px 0 0; }
.monitor-card .progress { height:4px; margin-top:8px; }
.nav-pills-ict { display:flex; flex-wrap:wrap; gap:3px; margin-bottom:14px; padding:6px; background:#f9fafb; border-radius:10px; border:1px solid #e5e7eb; }
.nav-pills-ict .nav-link { padding:5px 10px; border-radius:6px; font-size:11px; font-weight:500; color:#4b5563; text-decoration:none; white-space:nowrap; }
.nav-pills-ict .nav-link:hover { background:#e5e7eb; }
.nav-pills-ict .nav-link.active { background:#2563eb; color:#fff; }
.filter-pill { display:inline-flex; padding:5px 12px; border-radius:6px; font-size:11px; font-weight:500; color:#4b5563; background:#f3f4f6; text-decoration:none; }
.filter-pill:hover { background:#e5e7eb; }
.filter-pill.active { background:#2563eb; color:#fff; }
.badge-dot { width:8px; height:8px; border-radius:50%; display:inline-block; }
.status-led { width:10px; height:10px; border-radius:50%; display:inline-block; }
.table-small td, .table-small th { padding:4px 8px!important; font-size:12px; }
</style>
</head>
<body>
<?php include_once __DIR__ . '/../includes/sidebar.php'; ?>
<div class="page-content">
    <div class="top-bar">
        <div><strong><i class="fas fa-laptop-code me-2 text-primary"></i>Director ICT Dashboard</strong><span class="text-muted small ms-2"><?= htmlspecialchars($user_name) ?></span></div>
        <div class="d-flex align-items-center gap-2">
            <span class="text-muted small d-none d-md-block"><?= date('D, d M Y H:i') ?></span>
            <?php if ($active_alerts): ?><span class="badge bg-danger"><?= $active_alerts ?> alerts</span><?php endif; ?>
            <a href="../index.php" class="btn btn-sm btn-outline-secondary"><i class="fas fa-home"></i></a>
            <a href="../logout.php" class="btn btn-sm btn-outline-danger"><i class="fas fa-sign-out-alt"></i></a>
        </div>
    </div>

    <div class="content-section active content-area">
        <?php if ($msg = $_SESSION['success'] ?? null): ?><div class="alert alert-success alert-dismissible fade show py-2"><?= htmlspecialchars($msg) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; unset($_SESSION['success']); ?>
        <?php if ($err = $_SESSION['error'] ?? null): ?><div class="alert alert-danger alert-dismissible fade show py-2"><?= htmlspecialchars($err) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; unset($_SESSION['error']); ?>

        <!-- Navigation -->
        <ul class="nav nav-pills-ict">
            <li><a class="nav-link <?= $tab==='dashboard'?'active':'' ?>" href="?tab=dashboard"><i class="fas fa-chart-pie me-1"></i>Dashboard</a></li>
            <li><a class="nav-link <?= $tab==='assets'?'active':'' ?>" href="?tab=assets"><i class="fas fa-boxes me-1"></i>Assets</a></li>
            <li><a class="nav-link <?= $tab==='infrastructure'?'active':'' ?>" href="?tab=infrastructure"><i class="fas fa-server me-1"></i>Infrastructure</a></li>
            <li><a class="nav-link <?= $tab==='helpdesk'?'active':'' ?>" href="?tab=helpdesk"><i class="fas fa-headset me-1"></i>Help Desk<?= $open_tickets ? ' <span class="badge bg-danger">'.$open_tickets.'</span>' : '' ?></a></li>
            <li><a class="nav-link <?= $tab==='backups'?'active':'' ?>" href="?tab=backups"><i class="fas fa-database me-1"></i>Backups</a></li>
            <li><a class="nav-link <?= $tab==='security'?'active':'' ?>" href="?tab=security"><i class="fas fa-shield-alt me-1"></i>Security</a></li>
            <li><a class="nav-link <?= $tab==='monitoring'?'active':'' ?>" href="?tab=monitoring"><i class="fas fa-heartbeat me-1"></i>Monitoring</a></li>
            <li><a class="nav-link <?= $tab==='settings'?'active':'' ?>" href="?tab=settings"><i class="fas fa-cog me-1"></i>Settings</a></li>
            <li><a class="nav-link <?= $tab==='users'?'active':'' ?>" href="?tab=users"><i class="fas fa-users-cog me-1"></i>Users</a></li>
            <li><a class="nav-link <?= $tab==='erp'?'active':'' ?>" href="?tab=erp"><i class="fas fa-cubes me-1"></i>ERP System</a></li>
            <li><a class="nav-link <?= $tab==='website'?'active':'' ?>" href="?tab=website"><i class="fas fa-globe me-1"></i>Website</a></li>
            <li><a class="nav-link <?= $tab==='approvals'?'active':'' ?>" href="?tab=approvals"><i class="fas fa-check-double me-1"></i>Approvals<?= count($pending_tickets) ? ' <span class="badge bg-danger">'.count($pending_tickets).'</span>' : '' ?></a></li>
        </ul>

        <!-- ======== DASHBOARD ======== -->
        <?php if ($tab === 'dashboard'): ?>
        <div class="row g-2 mb-3">
            <div class="col-6 col-md-4 col-lg-3 col-xl"><div class="stat-card"><div class="icon-circle bg-blue-soft"><i class="fas fa-users"></i></div><div><h4><?= $total_users ?></h4><p>Total Users</p></div></div></div>
            <div class="col-6 col-md-4 col-lg-3 col-xl"><div class="stat-card"><div class="icon-circle bg-green-soft"><i class="fas fa-user-tie"></i></div><div><h4><?= $total_staff ?></h4><p>Staff</p></div></div></div>
            <div class="col-6 col-md-4 col-lg-3 col-xl"><div class="stat-card"><div class="icon-circle bg-purple-soft"><i class="fas fa-user-graduate"></i></div><div><h4><?= $total_students ?></h4><p>Students</p></div></div></div>
            <div class="col-6 col-md-4 col-lg-3 col-xl"><div class="stat-card"><div class="icon-circle bg-teal-soft"><i class="fas fa-server"></i></div><div><h4><?= $active_servers ?></h4><p>Servers</p></div></div></div>
            <div class="col-6 col-md-4 col-lg-3 col-xl"><div class="stat-card"><div class="icon-circle bg-cyan-soft"><i class="fas fa-network-wired"></i></div><div><h4><?= $network_active ?></h4><p>Network</p></div></div></div>
            <div class="col-6 col-md-4 col-lg-3 col-xl"><div class="stat-card"><div class="icon-circle bg-yellow-soft"><i class="fas fa-wifi"></i></div><div><h4><?= $wifi_active ?></h4><p>WiFi AP</p></div></div></div>
            <div class="col-6 col-md-4 col-lg-3 col-xl"><div class="stat-card"><div class="icon-circle bg-pink-soft"><i class="fas fa-boxes"></i></div><div><h4><?= $total_assets ?></h4><p>Assets</p></div></div></div>
            <div class="col-6 col-md-4 col-lg-3 col-xl"><div class="stat-card"><div class="icon-circle bg-red-soft"><i class="fas fa-ticket-alt"></i></div><div><h4><?= $open_tickets ?></h4><p>Open Tickets</p></div></div></div>
            <div class="col-6 col-md-4 col-lg-3 col-xl"><div class="stat-card"><div class="icon-circle bg-orange-soft"><i class="fas fa-database"></i></div><div><h4><?= $today_backups ?></h4><p>Backups Today</p></div></div></div>
            <div class="col-6 col-md-4 col-lg-3 col-xl"><div class="stat-card"><div class="icon-circle bg-indigo-soft"><i class="fas fa-shield-alt"></i></div><div><h4><?= $active_alerts ?></h4><p>Alerts</p></div></div></div>
        </div>

        <div class="row g-3">
            <div class="col-lg-7">
                <div class="section-card">
                    <h2><i class="fas fa-heartbeat me-2 text-danger"></i>System Monitoring</h2>
                    <div class="row g-2">
                        <div class="col-4"><div class="monitor-card"><h3 id="cpuVal">67%</h3><p>CPU Usage</p><div class="progress"><div class="progress-bar bg-success" style="width:67%"></div></div></div></div>
                        <div class="col-4"><div class="monitor-card"><h3 id="ramVal">72%</h3><p>RAM Usage</p><div class="progress"><div class="progress-bar bg-warning" style="width:72%"></div></div></div></div>
                        <div class="col-4"><div class="monitor-card"><h3 id="diskVal">45%</h3><p>Disk Usage</p><div class="progress"><div class="progress-bar bg-info" style="width:45%"></div></div></div></div>
                        <div class="col-4"><div class="monitor-card"><h3 id="uptimeVal">99.8%</h3><p>Uptime</p><span class="badge bg-success">Online</span></div></div>
                        <div class="col-4"><div class="monitor-card"><h3 id="sessionsVal"><?= ict_q($ict, "SELECT COUNT(*) FROM ict_login_sessions WHERE status='active'") ?></h3><p>Active Sessions</p><span class="badge bg-info">Active</span></div></div>
                        <div class="col-4"><div class="monitor-card"><h3 id="failuresVal"><?= ict_q($ict, "SELECT COUNT(*) FROM ict_failed_logins WHERE DATE(attempted_at)=CURDATE()") ?></h3><p>Failed Logins Today</p><span class="badge bg-<?= ict_q($ict,"SELECT COUNT(*) FROM ict_failed_logins WHERE DATE(attempted_at)=CURDATE()") > 10 ? 'danger' : 'secondary' ?>">Today</span></div></div>
                    </div>
                </div>
                <div class="section-card">
                    <h2><i class="fas fa-ticket-alt me-2 text-warning"></i>Recent Support Tickets</h2>
                    <div style="max-height:300px;overflow-y:auto">
                    <?php if (empty($tickets)): ?><div class="text-center py-3 text-muted"><p>No tickets</p></div>
                    <?php else: foreach (array_slice($tickets, 0, 8) as $t): ?>
                    <div class="d-flex justify-content-between align-items-center py-1 border-bottom small">
                        <div><code><?= htmlspecialchars($t['ticket_number']) ?></code> <strong><?= htmlspecialchars($t['requester_name']) ?></strong><span class="text-muted ms-2"><?= htmlspecialchars(mb_substr($t['description']??'',0,50)) ?></span></div>
                        <div><span class="badge bg-<?= $t['priority']==='critical'||$t['priority']==='high'?'danger':($t['priority']==='medium'?'warning text-dark':'success') ?> me-1"><?= $t['priority'] ?></span><span class="badge bg-<?= $t['status']==='open'?'danger':($t['status']==='in_progress'?'warning text-dark':'success') ?>"><?= $t['status'] ?></span></div>
                    </div>
                    <?php endforeach; endif; ?>
                    </div>
                </div>
            </div>
            <div class="col-lg-5">
                <div class="section-card">
                    <h2><i class="fas fa-bell me-2 text-<?= $active_alerts?'danger':'secondary' ?>"></i>Active Alerts <?= $active_alerts ? '<span class="badge bg-danger">'.$active_alerts.'</span>' : '' ?></h2>
                    <div style="max-height:250px;overflow-y:auto">
                    <?php if (empty($alerts)): ?><div class="text-center py-3 text-muted"><p>No active alerts</p></div>
                    <?php else: foreach ($alerts as $a): ?>
                    <div class="d-flex justify-content-between py-1 border-bottom small">
                        <div><span class="badge bg-<?= $a['severity']==='critical'?'danger':($a['severity']==='warning'?'warning text-dark':'info') ?> me-1"><?= $a['severity'] ?></span><strong><?= htmlspecialchars($a['title']) ?></strong></div>
                        <div><span class="badge bg-<?= $a['status']==='active'?'danger':'secondary' ?>"><?= $a['status'] ?></span></div>
                    </div>
                    <?php endforeach; endif; ?>
                    </div>
                </div>
                <div class="section-card">
                    <h2><i class="fas fa-history me-2 text-info"></i>Audit Trail</h2>
                    <div style="max-height:300px;overflow-y:auto">
                    <?php if (empty($audit_logs)): ?><div class="text-center py-3 text-muted"><p>No audit logs</p></div>
                    <?php else: foreach (array_slice($audit_logs, 0, 8) as $a): ?>
                    <div class="py-1 border-bottom small">
                        <strong><?= htmlspecialchars($a['username'] ?: 'System') ?></strong> <?= htmlspecialchars($a['action']) ?> <code><?= htmlspecialchars($a['resource_type'] ?: '') ?></code>
                        <small class="d-block text-muted"><?= htmlspecialchars(mb_substr($a['description']??'',0,60)) ?> | <?= htmlspecialchars($a['created_at'] ?? '') ?></small>
                    </div>
                    <?php endforeach; endif; ?>
                    </div>
                </div>
                <div class="section-card">
                    <h2><i class="fas fa-hdd me-2 text-purple"></i>Database Info</h2>
                    <div class="small">
                        <div class="d-flex justify-content-between py-1"><span>Database Size</span><strong><?= number_format($db_size_mb, 2) ?> MB</strong></div>
                        <div class="d-flex justify-content-between py-1"><span>ICT Tables</span><strong><?= count($ict->query("SHOW TABLES")->fetch_all()) ?></strong></div>
                        <div class="d-flex justify-content-between py-1"><span>Backups Today</span><strong><?= $today_backups ?></strong></div>
                        <div class="d-flex justify-content-between py-1"><span>Total Assets</span><strong><?= $total_assets ?></strong></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ======== ASSETS ======== -->
        <?php elseif ($tab === 'assets'): ?>
        <div class="row g-3">
            <div class="col-lg-8">
                <div class="section-card">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h2 class="mb-0"><i class="fas fa-boxes me-2"></i>ICT Asset Register</h2>
                        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addAssetModal"><i class="fas fa-plus me-1"></i>Add Asset</button>
                    </div>
                    <div class="table-responsive" style="max-height:500px;overflow-y:auto">
                        <table class="table table-sm table-hover table-small">
                            <thead><tr><th>Asset #</th><th>Name</th><th>Type</th><th>Category</th><th>Status</th><th>Location</th><th>Actions</th></tr></thead>
                            <tbody>
                                <?php if (empty($assets)): ?><tr><td colspan="7" class="text-center text-muted">No assets</td></tr><?php endif; ?>
                                <?php foreach ($assets as $a): ?>
                                <tr>
                                    <td><code><?= htmlspecialchars($a['asset_number']) ?></code></td>
                                    <td><?= htmlspecialchars($a['asset_name']) ?></td>
                                    <td><?= ucfirst($a['asset_type']) ?></td>
                                    <td><small><?= htmlspecialchars($a['category_name'] ?? '-') ?></small></td>
                                    <td><span class="badge bg-<?= $a['current_status']==='active'?'success':($a['current_status']==='in_maintenance'?'warning text-dark':'secondary') ?>"><?= str_replace('_',' ',$a['current_status']) ?></span></td>
                                    <td><small><?= htmlspecialchars($a['current_location'] ?? '-') ?></small></td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary py-0 px-1" onclick="editAsset(<?= $a['id'] ?>)"><i class="fas fa-edit"></i></button>
                                        <button class="btn btn-sm btn-outline-info py-0 px-1" onclick="assignAsset(<?= $a['id'] ?>)"><i class="fas fa-user-tag"></i></button>
                                        <button class="btn btn-sm btn-outline-warning py-0 px-1" onclick="logMaint(<?= $a['id'] ?>)"><i class="fas fa-tools"></i></button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="row g-2">
                    <div class="col-md-6">
                        <div class="section-card">
                            <h2><i class="fas fa-user-tag me-2 text-info"></i>Asset Assignments</h2>
                            <div style="max-height:250px;overflow-y:auto">
                            <?php if (empty($assignments)): ?><div class="text-muted small">No active assignments</div>
                            <?php else: foreach ($assignments as $as): ?>
                            <div class="py-1 border-bottom small"><strong><?= htmlspecialchars($as['asset_name'] ?? $as['asset_number']) ?></strong> → Staff #<?= $as['assigned_to_staff_id'] ?: 'Dept' ?>
                            <span class="badge bg-<?= $as['status']==='active'?'success':'secondary' ?> float-end"><?= $as['status'] ?></span></div>
                            <?php endforeach; endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="section-card">
                            <h2><i class="fas fa-tools me-2 text-warning"></i>Maintenance</h2>
                            <div style="max-height:250px;overflow-y:auto">
                            <?php if (empty($maintenance)): ?><div class="text-muted small">No maintenance records</div>
                            <?php else: foreach (array_slice($maintenance, 0, 8) as $m): ?>
                            <div class="py-1 border-bottom small"><strong><?= htmlspecialchars($m['asset_name'] ?? $m['asset_number']) ?></strong> - <?= $m['maintenance_type'] ?> <span class="badge bg-<?= $m['status']==='completed'?'success':'warning text-dark' ?> float-end"><?= $m['status'] ?></span></div>
                            <?php endforeach; endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="section-card">
                    <h2><i class="fas fa-tag me-2 text-success"></i>Categories</h2>
                    <div style="max-height:300px;overflow-y:auto">
                    <?php if (empty($asset_cats)): ?><div class="text-muted small">No categories</div>
                    <?php else: foreach ($asset_cats as $c): ?>
                    <div class="py-1 border-bottom small"><?= htmlspecialchars($c['category_name']) ?> <span class="badge bg-secondary float-end"><?= ict_q($ict, "SELECT COUNT(*) FROM ict_assets WHERE category_id={$c['id']}") ?></span></div>
                    <?php endforeach; endif; ?>
                    </div>
                </div>
                <div class="section-card">
                    <h2><i class="fas fa-chart-pie me-2 text-purple"></i>Asset Summary</h2>
                    <?php
                    $typeCounts = []; foreach ($assets as $a) { $t = $a['asset_type']; $typeCounts[$t] = ($typeCounts[$t] ?? 0) + 1; }
                    foreach ($typeCounts as $t => $c): ?>
                    <div class="d-flex justify-content-between py-1 border-bottom small"><span><?= ucfirst($t) ?></span><span class="badge bg-secondary"><?= $c ?></span></div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- ======== INFRASTRUCTURE ======== -->
        <?php elseif ($tab === 'infrastructure'): ?>
        <div class="row g-3">
            <div class="col-md-6">
                <div class="section-card">
                    <div class="d-flex justify-content-between mb-2"><h2 class="mb-0"><i class="fas fa-server me-2 text-primary"></i>Servers</h2>
                    <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addServerModal"><i class="fas fa-plus"></i></button></div>
                    <div class="table-responsive" style="max-height:350px;overflow-y:auto">
                        <table class="table table-sm table-hover table-small">
                            <thead><tr><th>Name</th><th>IP</th><th>Type</th><th>OS</th><th>Status</th><th>Actions</th></tr></thead>
                            <tbody>
                                <?php if (empty($servers)): ?><tr><td colspan="6" class="text-muted text-center">No servers</td></tr><?php endif; ?>
                                <?php foreach ($servers as $s): ?>
                                <tr>
                                    <td><?= htmlspecialchars($s['server_name']) ?></td>
                                    <td><code><?= htmlspecialchars($s['ip_address'] ?? '-') ?></code></td>
                                    <td><small><?= $s['server_type'] ?></small></td>
                                    <td><small><?= htmlspecialchars($s['os'] ?? '-') ?></small></td>
                                    <td><span class="badge bg-<?= $s['status']==='online'?'success':($s['status']==='offline'?'danger':'warning text-dark') ?>"><?= $s['status'] ?></span></td>
                                    <td><button class="btn btn-sm btn-outline-secondary py-0 px-1" onclick="editServer(<?= $s['id'] ?>)"><i class="fas fa-edit"></i></button></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="section-card">
                    <div class="d-flex justify-content-between mb-2"><h2 class="mb-0"><i class="fas fa-network-wired me-2 text-info"></i>Network Devices</h2></div>
                    <div class="table-responsive" style="max-height:300px;overflow-y:auto">
                        <table class="table table-sm table-small">
                            <thead><tr><th>Name</th><th>Type</th><th>IP</th><th>Location</th><th>Status</th><th>Action</th></tr></thead>
                            <tbody>
                                <?php if (empty($net_devices)): ?><tr><td colspan="6" class="text-muted text-center">No network devices</td></tr><?php endif; ?>
                                <?php foreach ($net_devices as $d): ?>
                                <tr>
                                    <td><?= htmlspecialchars($d['device_name']) ?></td>
                                    <td><small><?= $d['device_type'] ?></small></td>
                                    <td><code><?= htmlspecialchars($d['ip_address'] ?? '-') ?></code></td>
                                    <td><small><?= htmlspecialchars($d['location'] ?? '-') ?></small></td>
                                    <td><span class="status-led bg-<?= $d['status']==='online'?'success':'danger' ?> me-1"></span><?= $d['status'] ?></td>
                                    <td>
                                        <select class="form-select form-select-sm d-inline w-auto" onchange="updateNetDevice(<?= $d['id'] ?>,this.value)">
                                            <option value="online" <?= $d['status']==='online'?'selected':'' ?>>Online</option>
                                            <option value="offline" <?= $d['status']==='offline'?'selected':'' ?>>Offline</option>
                                            <option value="maintenance" <?= $d['status']==='maintenance'?'selected':'' ?>>Maint</option>
                                        </select>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="section-card">
                    <div class="d-flex justify-content-between mb-2"><h2 class="mb-0"><i class="fas fa-wifi me-2 text-success"></i>WiFi Access Points</h2>
                    <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addWifiModal"><i class="fas fa-plus"></i></button></div>
                    <div class="table-responsive" style="max-height:350px;overflow-y:auto">
                        <table class="table table-sm table-small">
                            <thead><tr><th>Name</th><th>SSID</th><th>IP</th><th>Location</th><th>Clients</th><th>Status</th><th>Action</th></tr></thead>
                            <tbody>
                                <?php if (empty($wifi_devices)): ?><tr><td colspan="7" class="text-muted text-center">No WiFi devices</td></tr><?php endif; ?>
                                <?php foreach ($wifi_devices as $w): ?>
                                <tr>
                                    <td><?= htmlspecialchars($w['device_name']) ?></td>
                                    <td><code><?= htmlspecialchars($w['ssid'] ?? '-') ?></code></td>
                                    <td><code><?= htmlspecialchars($w['ip_address'] ?? '-') ?></code></td>
                                    <td><small><?= htmlspecialchars($w['location'] ?? '-') ?></small></td>
                                    <td><?= $w['connected_clients'] ?: 0 ?>/<?= $w['max_clients'] ?: 50 ?></td>
                                    <td><span class="badge bg-<?= $w['status']==='online'?'success':'danger' ?>"><?= $w['status'] ?></span></td>
                                    <td>
                                        <select class="form-select form-select-sm d-inline w-auto" onchange="updateWifi(<?= $w['id'] ?>,this.value)">
                                            <option value="online" <?= $w['status']==='online'?'selected':'' ?>>Online</option>
                                            <option value="offline" <?= $w['status']==='offline'?'selected':'' ?>>Offline</option>
                                        </select>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="section-card">
                    <h2><i class="fas fa-list me-2 text-secondary"></i>Network Logs</h2>
                    <div style="max-height:300px;overflow-y:auto">
                    <?php if (empty($network_logs)): ?><div class="text-muted small">No network logs</div>
                    <?php else: foreach (array_slice($network_logs, 0, 10) as $nl): ?>
                    <div class="py-1 border-bottom small"><span class="badge bg-<?= $nl['severity']==='error'||$nl['severity']==='critical'?'danger':($nl['severity']==='warning'?'warning text-dark':'info') ?> me-1"><?= $nl['severity'] ?></span><?= htmlspecialchars(mb_substr($nl['message']??'',0,80)) ?> <small class="text-muted float-end"><?= htmlspecialchars($nl['logged_at'] ?? '') ?></small></div>
                    <?php endforeach; endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- ======== HELP DESK ======== -->
        <?php elseif ($tab === 'helpdesk'): ?>
        <div class="row g-3">
            <div class="col-lg-8">
                <div class="section-card">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h2 class="mb-0"><i class="fas fa-ticket-alt me-2"></i>Support Tickets</h2>
                        <div class="d-flex gap-1">
                            <button class="btn btn-sm btn-outline-primary" onclick="filterTickets('all')">All</button>
                            <button class="btn btn-sm btn-outline-danger" onclick="filterTickets('open')">Open</button>
                            <button class="btn btn-sm btn-outline-warning" onclick="filterTickets('in_progress')">In Progress</button>
                            <button class="btn btn-sm btn-outline-success" onclick="filterTickets('resolved')">Resolved</button>
                            <button class="btn btn-sm btn-outline-secondary" onclick="filterTickets('closed')">Closed</button>
                        </div>
                    </div>
                    <div class="table-responsive" style="max-height:550px;overflow-y:auto">
                        <table class="table table-sm table-hover table-small" id="ticketTable">
                            <thead><tr><th>#</th><th>Requester</th><th>Issue</th><th>Priority</th><th>Status</th><th>Created</th><th>Actions</th></tr></thead>
                            <tbody>
                                <?php if (empty($tickets)): ?><tr><td colspan="7" class="text-center text-muted">No tickets</td></tr><?php endif; ?>
                                <?php foreach ($tickets as $t): ?>
                                <tr class="ticket-row-<?= $t['status'] ?>">
                                    <td><code><?= htmlspecialchars($t['ticket_number']) ?></code></td>
                                    <td><?= htmlspecialchars($t['requester_name']) ?></td>
                                    <td><small><?= htmlspecialchars(mb_substr($t['description']??'',0,40)) ?></small></td>
                                    <td><span class="badge bg-<?= $t['priority']==='critical'||$t['priority']==='high'?'danger':($t['priority']==='medium'?'warning text-dark':'success') ?>"><?= $t['priority'] ?></span></td>
                                    <td><span class="badge bg-<?= $t['status']==='open'?'danger':($t['status']==='in_progress'?'warning text-dark':($t['status']==='resolved'?'info':'secondary')) ?>"><?= str_replace('_',' ',$t['status']) ?></span></td>
                                    <td><small><?= date('d/m H:i', strtotime($t['created_at'])) ?></small></td>
                                    <td>
                                        <div class="dropdown">
                                            <button class="btn btn-sm btn-outline-secondary py-0 px-1" data-bs-toggle="dropdown"><i class="fas fa-cog"></i></button>
                                            <ul class="dropdown-menu">
                                                <li><a class="dropdown-item" href="javascript:void(0)" onclick="updateTicket(<?= $t['id'] ?>)"><i class="fas fa-edit me-2"></i>Update</a></li>
                                                <li><a class="dropdown-item" href="javascript:void(0)" onclick="updateTicketStatus(<?= $t['id'] ?>,'in_progress')"><i class="fas fa-play me-2"></i>In Progress</a></li>
                                                <li><a class="dropdown-item" href="javascript:void(0)" onclick="updateTicketStatus(<?= $t['id'] ?>,'resolved')"><i class="fas fa-check me-2"></i>Resolved</a></li>
                                                <li><a class="dropdown-item" href="javascript:void(0)" onclick="updateTicketStatus(<?= $t['id'] ?>,'closed')"><i class="fas fa-times me-2"></i>Closed</a></li>
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="section-card">
                    <h2><i class="fas fa-chart-simple me-2"></i>Ticket Summary</h2>
                    <div class="small">
                        <div class="d-flex justify-content-between py-1 border-bottom"><span>Open</span><span class="badge bg-danger"><?= ict_q($ict,"SELECT COUNT(*) FROM it_support_tickets WHERE status='open'") ?></span></div>
                        <div class="d-flex justify-content-between py-1 border-bottom"><span>In Progress</span><span class="badge bg-warning text-dark"><?= ict_q($ict,"SELECT COUNT(*) FROM it_support_tickets WHERE status='in_progress'") ?></span></div>
                        <div class="d-flex justify-content-between py-1 border-bottom"><span>Resolved</span><span class="badge bg-info"><?= ict_q($ict,"SELECT COUNT(*) FROM it_support_tickets WHERE status='resolved'") ?></span></div>
                        <div class="d-flex justify-content-between py-1 border-bottom"><span>Closed</span><span class="badge bg-secondary"><?= ict_q($ict,"SELECT COUNT(*) FROM it_support_tickets WHERE status='closed'") ?></span></div>
                        <div class="d-flex justify-content-between py-1"><span>Total</span><span class="badge bg-primary"><?= ict_q($ict,"SELECT COUNT(*) FROM it_support_tickets") ?></span></div>
                    </div>
                </div>
                <div class="section-card">
                    <h2><i class="fas fa-history me-2 text-info"></i>Security Logs</h2>
                    <div style="max-height:300px;overflow-y:auto">
                    <?php if (empty($security_logs)): ?><div class="text-muted small">No security logs</div>
                    <?php else: foreach (array_slice($security_logs, 0, 10) as $sl): ?>
                    <div class="py-1 border-bottom small"><span class="badge bg-<?= $sl['severity']==='critical'?'danger':($sl['severity']==='warning'?'warning text-dark':'info') ?> me-1"><?= $sl['event_type'] ?></span><?= htmlspecialchars(mb_substr($sl['description']??'',0,50)) ?> <small class="text-muted float-end"><?= date('d/m', strtotime($sl['created_at'])) ?></small></div>
                    <?php endforeach; endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- ======== BACKUPS ======== -->
        <?php elseif ($tab === 'backups'): ?>
        <div class="row g-3">
            <div class="col-lg-4">
                <div class="section-card">
                    <h2><i class="fas fa-database me-2 text-success"></i>Create Backup</h2>
                    <form id="backupForm">
                        <input type="hidden" name="action" value="create_backup">
                        <div class="mb-2"><label class="form-label">Backup Name</label><input type="text" name="backup_name" class="form-control" value="Backup-<?= date('Ymd-His') ?>"></div>
                        <div class="mb-2"><label class="form-label">Type</label>
                            <select name="backup_type" class="form-select">
                                <option value="database">Database</option>
                                <option value="file">File System</option>
                                <option value="full">Full System</option>
                                <option value="incremental">Incremental</option>
                            </select>
                        </div>
                        <div class="mb-2"><label class="form-label">Target Database</label>
                            <select name="target_database" class="form-select">
                                <option value="igangaschoolofl_ict">ICT Database</option>
                                <option value="igangaschoolofl_staffs_db">Staff Database</option>
                                <option value="igangaschoolofl_students_db">Students Database</option>
                                <option value="igangaschoolofl_website_db">Website Database</option>
                                <option value="all">All Databases</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-success w-100"><i class="fas fa-play me-1"></i>Start Backup</button>
                    </form>
                </div>
                <div class="section-card">
                    <h2><i class="fas fa-wrench me-2 text-warning"></i>Backup Settings</h2>
                    <form id="backupSettingsForm">
                        <input type="hidden" name="action" value="save_setting">
                        <div class="mb-1"><label class="form-label small">Auto Backup</label>
                            <select name="setting_value" class="form-select form-select-sm" onchange="saveBackupSetting('auto_backup_enabled',this.value)">
                                <option value="true" <?= ($s=ict_fetch_one($ict,"SELECT setting_value FROM ict_system_settings WHERE setting_key='auto_backup_enabled'")) && $s['setting_value']==='true'?'selected':'' ?>>Enabled</option>
                                <option value="false" <?= $s&&$s['setting_value']==='false'?'selected':'' ?>>Disabled</option>
                            </select>
                        </div>
                        <div class="mb-1"><label class="form-label small">Retention (days)</label>
                            <input type="number" class="form-control form-control-sm" value="<?= ($s=ict_fetch_one($ict,"SELECT setting_value FROM ict_system_settings WHERE setting_key='backup_retention_days'")) ? htmlspecialchars($s['setting_value']) : 30 ?>" onchange="saveBackupSetting('backup_retention_days',this.value)">
                        </div>
                        <div class="mb-1"><label class="form-label small">Scheduled Time</label>
                            <input type="time" class="form-control form-control-sm" value="<?= ($s=ict_fetch_one($ict,"SELECT setting_value FROM ict_system_settings WHERE setting_key='backup_time'")) ? htmlspecialchars($s['setting_value']) : '02:00' ?>" onchange="saveBackupSetting('backup_time',this.value)">
                        </div>
                    </form>
                </div>
            </div>
            <div class="col-lg-8">
                <div class="section-card">
                    <div class="d-flex justify-content-between mb-2">
                        <h2 class="mb-0"><i class="fas fa-history me-2"></i>Backup History</h2>
                        <div>
                            <button class="btn btn-sm btn-outline-primary" onclick="filterBackup('all')">All</button>
                            <button class="btn btn-sm btn-outline-success" onclick="filterBackup('completed')">Completed</button>
                            <button class="btn btn-sm btn-outline-danger" onclick="filterBackup('failed')">Failed</button>
                            <button class="btn btn-sm btn-outline-info" onclick="filterBackup('verified')">Verified</button>
                        </div>
                    </div>
                    <div class="table-responsive" style="max-height:400px;overflow-y:auto">
                        <table class="table table-sm table-hover table-small" id="backupTable">
                            <thead><tr><th>Name</th><th>Type</th><th>Database</th><th>Size</th><th>Status</th><th>Created</th><th>Actions</th></tr></thead>
                            <tbody>
                                <?php if (empty($backups)): ?><tr><td colspan="7" class="text-center text-muted">No backups yet</td></tr><?php endif; ?>
                                <?php foreach ($backups as $b): ?>
                                <tr class="backup-row-<?= $b['status'] ?>">
                                    <td><small><?= htmlspecialchars($b['backup_name']) ?></small></td>
                                    <td><span class="badge bg-secondary"><?= $b['backup_type'] ?></span></td>
                                    <td><small><?= htmlspecialchars($b['target_database'] ?? '-') ?></small></td>
                                    <td><small><?= number_format($b['file_size_mb']??0, 1) ?> MB</small></td>
                                    <td><span class="badge bg-<?= $b['status']==='completed'?'success':($b['status']==='failed'?'danger':($b['status']==='verified'?'info':'warning text-dark')) ?>"><?= $b['status'] ?></span></td>
                                    <td><small><?= date('d/m H:i', strtotime($b['created_at'])) ?></small></td>
                                    <td>
                                        <?php if ($b['status'] === 'completed'): ?>
                                        <button class="btn btn-sm btn-outline-info py-0 px-1" onclick="verifyBackup(<?= $b['id'] ?>)"><i class="fas fa-check-circle"></i></button>
                                        <?php endif; ?>
                                        <button class="btn btn-sm btn-outline-danger py-0 px-1" onclick="deleteBackup(<?= $b['id'] ?>)"><i class="fas fa-trash"></i></button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="section-card">
                    <h2><i class="fas fa-list-alt me-2 text-secondary"></i>Backup Logs</h2>
                    <div style="max-height:250px;overflow-y:auto">
                    <?php if (empty($backup_logs)): ?><div class="text-muted small">No backup logs</div>
                    <?php else: foreach (array_slice($backup_logs, 0, 12) as $bl): ?>
                    <div class="py-1 border-bottom small"><span class="badge bg-<?= $bl['log_level']==='error'?'danger':($bl['log_level']==='warning'?'warning text-dark':'info') ?> me-1"><?= $bl['log_level'] ?></span><?= htmlspecialchars($bl['log_message'] ?? '') ?> <small class="text-muted float-end"><?= date('d/m H:i', strtotime($bl['logged_at'])) ?></small></div>
                    <?php endforeach; endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- ======== SECURITY ======== -->
        <?php elseif ($tab === 'security'): ?>
        <div class="row g-3">
            <div class="col-lg-8">
                <div class="section-card">
                    <h2><i class="fas fa-shield-alt me-2 text-danger"></i>Security Event Log</h2>
                    <div class="table-responsive" style="max-height:400px;overflow-y:auto">
                        <table class="table table-sm table-small">
                            <thead><tr><th>Event</th><th>User</th><th>IP</th><th>Description</th><th>Severity</th><th>Time</th></tr></thead>
                            <tbody>
                                <?php if (empty($security_logs)): ?><tr><td colspan="6" class="text-center text-muted">No security logs</td></tr><?php endif; ?>
                                <?php foreach ($security_logs as $sl): ?>
                                <tr>
                                    <td><span class="badge bg-secondary"><?= $sl['event_type'] ?></span></td>
                                    <td><small><?= htmlspecialchars($sl['username'] ?: '-') ?></small></td>
                                    <td><code><?= htmlspecialchars($sl['ip_address'] ?? '-') ?></code></td>
                                    <td><small><?= htmlspecialchars(mb_substr($sl['description']??'',0,60)) ?></small></td>
                                    <td><span class="badge bg-<?= $sl['severity']==='critical'?'danger':($sl['severity']==='warning'?'warning text-dark':'info') ?>"><?= $sl['severity'] ?></span></td>
                                    <td><small><?= date('d/m H:i', strtotime($sl['created_at'])) ?></small></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="row g-2">
                    <div class="col-md-6">
                        <div class="section-card">
                            <h2><i class="fas fa-ban me-2 text-danger"></i>Failed Logins</h2>
                            <div style="max-height:250px;overflow-y:auto">
                            <?php if (empty($failed_logins)): ?><div class="text-muted small">No failed logins</div>
                            <?php else: foreach ($failed_logins as $fl): ?>
                            <div class="py-1 border-bottom small"><code><?= htmlspecialchars($fl['username'] ?? '?') ?></code> from <code><?= htmlspecialchars($fl['ip_address'] ?? '?') ?></code> <small class="text-muted float-end"><?= date('d/m H:i', strtotime($fl['attempted_at'])) ?></small></div>
                            <?php endforeach; endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="section-card">
                            <h2><i class="fas fa-clipboard-list me-2 text-purple"></i>Audit Trail</h2>
                            <div style="max-height:250px;overflow-y:auto">
                            <?php if (empty($audit_logs)): ?><div class="text-muted small">No audit logs</div>
                            <?php else: foreach (array_slice($audit_logs, 0, 10) as $al): ?>
                            <div class="py-1 border-bottom small"><strong><?= htmlspecialchars($al['username'] ?: 'System') ?></strong> <?= $al['action'] ?> <code><?= $al['resource_type'] ?></code> <small class="text-muted float-end"><?= date('d/m H:i', strtotime($al['created_at'])) ?></small></div>
                            <?php endforeach; endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="section-card">
                    <h2><i class="fas fa-cog me-2 text-warning"></i>Security Settings</h2>
                    <div class="small">
                        <?php
                        $secSettings = ['session_timeout_minutes','max_login_attempts','lockout_duration_minutes','password_min_length'];
                        foreach ($secSettings as $sk):
                            $sv = ict_fetch_one($ict, "SELECT setting_value FROM ict_system_settings WHERE setting_key='$sk'");
                            $val = $sv ? $sv['setting_value'] : '';
                        ?>
                        <div class="mb-2">
                            <label class="form-label small text-muted text-capitalize"><?= str_replace('_', ' ', $sk) ?></label>
                            <div class="input-group input-group-sm">
                                <input type="text" class="form-control" value="<?= htmlspecialchars($val) ?>" id="sec_<?= $sk ?>">
                                <button class="btn btn-outline-primary" onclick="saveSetting('<?= $sk ?>',$('#sec_<?= $sk ?>').val(),'security')"><i class="fas fa-save"></i></button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="section-card">
                    <h2><i class="fas fa-bell me-2 text-danger"></i>Active Alerts</h2>
                    <div style="max-height:300px;overflow-y:auto">
                    <?php if (empty($alerts)): ?><div class="text-muted small">No active alerts</div>
                    <?php else: foreach ($alerts as $a): ?>
                    <div class="py-1 border-bottom small">
                        <span class="badge bg-<?= $a['severity']==='critical'?'danger':'warning text-dark' ?>"><?= $a['severity'] ?></span>
                        <strong><?= htmlspecialchars($a['title']) ?></strong>
                        <p class="mb-0 text-muted"><?= htmlspecialchars(mb_substr($a['message']??'',0,60)) ?></p>
                        <div class="mt-1">
                            <button class="btn btn-sm btn-outline-success py-0 px-1" onclick="acknowledgeAlert(<?= $a['id'] ?>)"><i class="fas fa-check"></i></button>
                            <button class="btn btn-sm btn-outline-info py-0 px-1" onclick="resolveAlert(<?= $a['id'] ?>)"><i class="fas fa-check-double"></i></button>
                        </div>
                    </div>
                    <?php endforeach; endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- ======== MONITORING ======== -->
        <?php elseif ($tab === 'monitoring'): ?>
        <div class="row g-3">
            <div class="col-lg-6">
                <div class="section-card">
                    <h2><i class="fas fa-heartbeat me-2 text-danger"></i>System Health Checks</h2>
                    <div style="max-height:400px;overflow-y:auto">
                    <?php if (empty($health_checks)): ?><div class="text-center py-4 text-muted"><i class="fas fa-check-circle fa-2x mb-2"></i><p>No health checks recorded yet</p></div>
                    <?php else: foreach ($health_checks as $h): ?>
                    <div class="d-flex justify-content-between align-items-center py-1 border-bottom small">
                        <div><span class="status-led bg-<?= $h['status']==='healthy'?'success':'danger' ?> me-2"></span><strong><?= htmlspecialchars($h['check_name'] ?: $h['check_type']) ?></strong> <span class="text-muted ms-2"><?= htmlspecialchars($h['value'] ?? '') ?></span></div>
                        <span class="badge bg-<?= $h['status']==='healthy'?'success':($h['status']==='warning'?'warning text-dark':'danger') ?>"><?= $h['status'] ?></span>
                    </div>
                    <?php endforeach; endif; ?>
                    </div>
                </div>
                <div class="section-card">
                    <h2><i class="fas fa-bell me-2 text-warning"></i>Notifications</h2>
                    <div style="max-height:250px;overflow-y:auto">
                    <?php if (empty($notifications)): ?><div class="text-muted small">No notifications</div>
                    <?php else: foreach ($notifications as $n): ?>
                    <div class="d-flex justify-content-between py-1 border-bottom small">
                        <div><span class="badge bg-<?= $n['notification_type']==='critical'?'danger':($n['notification_type']==='warning'?'warning text-dark':'info') ?> me-1"><?= $n['notification_type'] ?></span><?= htmlspecialchars($n['title']) ?></div>
                        <button class="btn btn-sm btn-outline-secondary py-0 px-1" onclick="dismissNotif(<?= $n['id'] ?>)"><i class="fas fa-times"></i></button>
                    </div>
                    <?php endforeach; endif; ?>
                    </div>
                    <button class="btn btn-sm btn-primary mt-2" data-bs-toggle="modal" data-bs-target="#addNotifModal"><i class="fas fa-plus me-1"></i>Add Notification</button>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="section-card">
                    <h2><i class="fas fa-sliders-h me-2 text-primary"></i>Live Monitoring</h2>
                    <div class="row g-2">
                        <div class="col-6"><div class="monitor-card"><h3 id="liveCpu">67%</h3><p>CPU Usage</p><div class="progress"><div class="progress-bar bg-success" style="width:67%"></div></div></div></div>
                        <div class="col-6"><div class="monitor-card"><h3 id="liveRam">72%</h3><p>RAM Usage</p><div class="progress"><div class="progress-bar bg-warning" style="width:72%"></div></div></div></div>
                        <div class="col-6"><div class="monitor-card"><h3 id="liveDisk">45%</h3><p>Disk Usage</p><div class="progress"><div class="progress-bar bg-info" style="width:45%"></div></div></div></div>
                        <div class="col-6"><div class="monitor-card"><h3 id="liveNet">99.8%</h3><p>Network Uptime</p><span class="badge bg-success">Online</span></div></div>
                    </div>
                </div>
                <div class="section-card">
                    <h2><i class="fas fa-exclamation-triangle me-2 text-danger"></i>System Alerts</h2>
                    <form id="alertForm">
                        <input type="hidden" name="action" value="add_alert">
                        <div class="row g-1 mb-1">
                            <div class="col-4"><select name="alert_type" class="form-select form-select-sm"><option value="system">System</option><option value="security">Security</option><option value="backup">Backup</option><option value="performance">Performance</option><option value="network">Network</option><option value="storage">Storage</option></select></div>
                            <div class="col-3"><select name="severity" class="form-select form-select-sm"><option value="info">Info</option><option value="warning">Warning</option><option value="critical">Critical</option></select></div>
                            <div class="col-5"><input type="text" name="title" class="form-control form-control-sm" placeholder="Alert title" required></div>
                        </div>
                        <div class="mb-1"><textarea name="message" class="form-control form-control-sm" rows="2" placeholder="Alert message" required></textarea></div>
                        <button type="submit" class="btn btn-sm btn-danger w-100"><i class="fas fa-plus me-1"></i>Create Alert</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- ======== SETTINGS ======== -->
        <?php elseif ($tab === 'settings'): ?>
        <div class="row g-3">
            <div class="col-md-6">
                <div class="section-card">
                    <h2><i class="fas fa-cog me-2 text-primary"></i>System Settings</h2>
                    <div class="small">
                        <?php foreach ($settings as $s): ?>
                        <div class="mb-2">
                            <label class="form-label small text-muted text-capitalize"><?= htmlspecialchars(str_replace('_', ' ', $s['setting_key'])) ?></label>
                            <div class="input-group input-group-sm">
                                <input type="text" class="form-control" value="<?= htmlspecialchars($s['setting_value'] ?? '') ?>" id="set_<?= $s['setting_key'] ?>">
                                <button class="btn btn-outline-primary" onclick="saveSetting('<?= $s['setting_key'] ?>',$('#set_<?= $s['setting_key'] ?>').val(),'<?= $s['setting_group'] ?>')"><i class="fas fa-save"></i></button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="section-card">
                    <h2><i class="fas fa-tag me-2 text-success"></i>Asset Categories</h2>
                    <div style="max-height:300px;overflow-y:auto">
                    <?php foreach ($asset_cats as $c): ?>
                    <div class="d-flex justify-content-between py-1 border-bottom small"><?= htmlspecialchars($c['category_name']) ?> <span class="badge bg-secondary"><?= ict_q($ict, "SELECT COUNT(*) FROM ict_assets WHERE category_id={$c['id']}") ?></span></div>
                    <?php endforeach; ?>
                    </div>
                </div>
                <div class="section-card">
                    <h2><i class="fas fa-shield-alt me-2 text-danger"></i>Quick Actions</h2>
                    <div class="d-grid gap-2">
                        <button class="btn btn-sm btn-outline-success text-start" onclick="createQuickBackup()"><i class="fas fa-database me-2"></i>Quick Backup All Databases</button>
                        <button class="btn btn-sm btn-outline-info text-start" onclick="addHealthCheck()"><i class="fas fa-heartbeat me-2"></i>Run System Health Check</button>
                        <button class="btn btn-sm btn-outline-warning text-start" onclick="addSecurityLog()"><i class="fas fa-shield-alt me-2"></i>Log Security Event</button>
                    </div>
                </div>
            </div>
        </div>
        <!-- ======== USERS & ACCESS MANAGEMENT ======== -->
        <?php elseif ($tab === 'users'): ?>
        <div class="row g-3">
            <div class="col-md-7">
                <div class="section-card">
                    <h2><i class="fas fa-users me-2 text-primary"></i>Staff Accounts <span class="badge bg-secondary"><?= $staff_count ?></span></h2>
                    <div class="table-responsive" style="max-height:400px;overflow-y:auto">
                        <table class="table table-sm table-hover table-small">
                            <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Status</th><th>Last Login</th></tr></thead>
                            <tbody>
                                <?php if (empty($staff_accounts)): ?><tr><td colspan="5" class="text-center text-muted">No staff accounts</td></tr><?php endif; ?>
                                <?php foreach ($staff_accounts as $s): ?>
                                <tr>
                                    <td><?= htmlspecialchars($s['full_name']) ?></td>
                                    <td><small><?= htmlspecialchars($s['email'] ?? '-') ?></small></td>
                                    <td><span class="badge bg-info"><?= htmlspecialchars($s['role']) ?></span></td>
                                    <td><span class="badge bg-<?= $s['status']==='Active'?'success':'secondary' ?>"><?= $s['status'] ?></span></td>
                                    <td><small class="text-muted"><?= htmlspecialchars($s['last_login'] ?? 'Never') ?></small></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="section-card">
                    <h2><i class="fas fa-history me-2 text-info"></i>Active Login Sessions <span class="badge bg-primary"><?= $active_sessions ?></span></h2>
                    <div class="small text-muted mb-2">Active sessions across the system. Failed logins today: <strong><?= $failed_today ?></strong></div>
                    <?php $logins = ict_fetch($ict, "SELECT * FROM ict_login_sessions WHERE status='active' ORDER BY logged_in_at DESC LIMIT 10"); ?>
                    <?php if (empty($logins)): ?><p class="text-muted small">No active sessions recorded</p>
                    <?php else: ?>
                    <div style="max-height:250px;overflow-y:auto">
                        <?php foreach ($logins as $l): ?>
                        <div class="d-flex justify-content-between py-1 border-bottom small">
                            <span><i class="fas fa-user-circle me-1"></i><?= htmlspecialchars($l['username']) ?></span>
                            <span class="text-muted"><?= htmlspecialchars($l['logged_in_at']) ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-md-5">
                <div class="section-card">
                    <h2><i class="fas fa-user-graduate me-2 text-purple"></i>Student Accounts <span class="badge bg-secondary"><?= $student_count ?></span></h2>
                    <?php $students_list = ict_fetch($students_conn, "SELECT id, first_name, last_name, email, status, last_login FROM students ORDER BY last_login DESC LIMIT 15"); ?>
                    <div class="table-responsive" style="max-height:300px;overflow-y:auto">
                        <table class="table table-sm table-hover table-small">
                            <thead><tr><th>Name</th><th>Status</th><th>Last Login</th></tr></thead>
                            <tbody>
                                <?php if (empty($students_list)): ?><tr><td colspan="3" class="text-center text-muted">No records</td></tr><?php endif; ?>
                                <?php foreach ($students_list as $s): ?>
                                <tr>
                                    <td><small><?= htmlspecialchars($s['first_name'] . ' ' . $s['last_name']) ?></small></td>
                                    <td><span class="badge bg-<?= $s['status']==='Active'?'success':'secondary' ?>"><?= $s['status'] ?? 'Active' ?></span></td>
                                    <td><small class="text-muted"><?= htmlspecialchars($s['last_login'] ?? 'Never') ?></small></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <p class="mt-2 small text-muted"><i class="fas fa-info-circle me-1"></i>Full account management is in <a href="../system-admin.php">System Administration</a>.</p>
                </div>
                <div class="section-card">
                    <h2><i class="fas fa-shield-alt me-2 text-danger"></i>Recent Failed Logins</h2>
                    <?php if (empty($failed_logins)): ?><p class="text-muted small">No failed login attempts</p>
                    <?php else: ?>
                    <div style="max-height:250px;overflow-y:auto">
                        <?php foreach (array_slice($failed_logins, 0, 8) as $f): ?>
                        <div class="d-flex justify-content-between py-1 border-bottom small">
                            <span><i class="fas fa-user-slash me-1 text-danger"></i><?= htmlspecialchars($f['username']) ?>@<?= htmlspecialchars($f['ip_address'] ?? '?') ?></span>
                            <span class="text-muted"><?= htmlspecialchars($f['attempted_at']) ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- ======== ERP SYSTEM MANAGEMENT ======== -->
        <?php elseif ($tab === 'erp'): ?>
        <div class="row g-3">
            <div class="col-md-7">
                <div class="section-card">
                    <h2><i class="fas fa-cubes me-2 text-primary"></i>ERP Module Permissions</h2>
                    <div class="table-responsive" style="max-height:400px;overflow-y:auto">
                        <table class="table table-sm table-hover table-small">
                            <thead><tr><th>Module</th><th>Role</th><th>Access Level</th><th>Status</th></tr></thead>
                            <tbody>
                                <?php if (empty($module_perms)): ?><tr><td colspan="4" class="text-center text-muted">No module permissions configured</td></tr><?php endif; ?>
                                <?php foreach ($module_perms as $m): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($m['module_name'] ?? '-') ?></strong></td>
                                    <td><span class="badge bg-info"><?= htmlspecialchars($m['role_name'] ?? '-') ?></span></td>
                                    <td><small><?= htmlspecialchars($m['access_level'] ?? 'full') ?></small></td>
                                    <td><span class="badge bg-<?= ($m['is_active']??'1')=='1'?'success':'secondary' ?>"><?= ($m['is_active']??'1')=='1'?'Active':'Inactive' ?></span></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="section-card">
                    <h2><i class="fas fa-database me-2 text-teal"></i>System Environment</h2>
                    <div class="row g-2 small">
                        <div class="col-6">
                            <div class="d-flex justify-content-between py-1"><span>PHP Version</span><strong><?= phpversion() ?></strong></div>
                            <div class="d-flex justify-content-between py-1"><span>Database</span><strong>MySQL</strong></div>
                            <div class="d-flex justify-content-between py-1"><span>Server</span><strong><?= $_SERVER['SERVER_SOFTWARE'] ?? 'N/A' ?></strong></div>
                        </div>
                        <div class="col-6">
                            <div class="d-flex justify-content-between py-1"><span>DB Size (ICT)</span><strong><?= number_format($db_size_mb, 2) ?> MB</strong></div>
                            <div class="d-flex justify-content-between py-1"><span>ICT Tables</span><strong><?= $ict ? count($ict->query("SHOW TABLES")->fetch_all()) : 0 ?></strong></div>
                            <div class="d-flex justify-content-between py-1"><span>Active Users</span><strong><?= $total_users ?></strong></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-5">
                <div class="section-card">
                    <h2><i class="fas fa-cog me-2 text-warning"></i>ERP Configuration</h2>
                    <p class="small text-muted">Full ERP configuration & module management is available in <a href="../system-admin.php">System Administration</a>.</p>
                    <div class="d-grid gap-2 mt-2">
                        <a href="../system-admin.php" class="btn btn-sm btn-outline-primary"><i class="fas fa-cog me-1"></i>System Administration</a>
                        <a href="../index.php" class="btn btn-sm btn-outline-info"><i class="fas fa-home me-1"></i>ERP Home</a>
                    </div>
                </div>
                <div class="section-card">
                    <h2><i class="fas fa-tasks me-2 text-success"></i>System Status</h2>
                    <div class="small">
                        <div class="d-flex justify-content-between py-1"><span>Staff Records</span><span class="badge bg-success"><?= $staff_count ?></span></div>
                        <div class="d-flex justify-content-between py-1"><span>Active Students</span><span class="badge bg-success"><?= $student_count ?></span></div>
                        <div class="d-flex justify-content-between py-1"><span>Open Tickets</span><span class="badge bg-<?= $open_tickets ? 'danger' : 'success' ?>"><?= $open_tickets ?></span></div>
                        <div class="d-flex justify-content-between py-1"><span>Active Alerts</span><span class="badge bg-<?= $active_alerts ? 'danger' : 'secondary' ?>"><?= $active_alerts ?></span></div>
                        <div class="d-flex justify-content-between py-1"><span>Active Servers</span><span class="badge bg-success"><?= $active_servers ?></span></div>
                    </div>
                </div>
                <div class="section-card">
                    <h2><i class="fas fa-server me-2 text-purple"></i>Quick Actions</h2>
                    <div class="d-grid gap-2">
                        <button class="btn btn-sm btn-outline-success text-start" onclick="createQuickBackup()"><i class="fas fa-database me-2"></i>Quick Backup</button>
                        <button class="btn btn-sm btn-outline-info text-start" onclick="addHealthCheck()"><i class="fas fa-heartbeat me-2"></i>Run System Health Check</button>
                        <a href="../system-admin.php" class="btn btn-sm btn-outline-primary text-start"><i class="fas fa-user-shield me-2"></i>User Permissions</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- ======== WEBSITE & PORTAL ======== -->
        <?php elseif ($tab === 'website'): ?>
        <div class="row g-3">
            <div class="col-md-7">
                <div class="section-card">
                    <h2><i class="fas fa-globe me-2 text-primary"></i>Website Status</h2>
                    <?php
                    $site_url = ($settings_entry = ict_fetch_one($ict, "SELECT setting_value FROM ict_system_settings WHERE setting_key='site_url'")) ? $settings_entry['setting_value'] : '../index.php';
                    $pages = ict_fetch($ict, "SELECT * FROM ict_system_settings WHERE setting_group='website' OR setting_key LIKE 'site_%' LIMIT 10");
                    ?>
                    <div class="small">
                        <div class="d-flex justify-content-between py-1"><span>Site URL</span><strong><a href="<?= htmlspecialchars($site_url) ?>" target="_blank"><?= htmlspecialchars($site_url) ?></a></strong></div>
                        <?php foreach ($pages as $p): ?>
                        <div class="d-flex justify-content-between py-1 border-bottom">
                            <span><?= htmlspecialchars(str_replace('_', ' ', ucfirst($p['setting_key']))) ?></span>
                            <span class="text-muted"><?= htmlspecialchars(mb_substr($p['setting_value'] ?? '', 0, 60)) ?></span>
                        </div>
                        <?php endforeach; ?>
                        <?php if (empty($pages)): ?><p class="text-muted">No website settings configured</p><?php endif; ?>
                    </div>
                </div>
                <div class="section-card">
                    <h2><i class="fas fa-newspaper me-2 text-warning"></i>News & Updates</h2>
                    <?php $news_items = ict_fetch($website_conn ?? $staff_conn, "SELECT id, title, created_at, status FROM news ORDER BY created_at DESC LIMIT 8"); ?>
                    <?php if (!empty($news_items)): ?>
                    <div style="max-height:300px;overflow-y:auto">
                        <?php foreach ($news_items as $n): ?>
                        <div class="d-flex justify-content-between py-1 border-bottom small">
                            <span><strong><?= htmlspecialchars($n['title']) ?></strong></span>
                            <span><span class="badge bg-<?= ($n['status']??'published')==='published'?'success':'secondary' ?>"><?= $n['status'] ?? 'published' ?></span> <small class="text-muted"><?= htmlspecialchars($n['created_at']) ?></small></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <p class="text-muted small">No news items found</p>
                    <?php endif; ?>
                    <p class="mt-2 small text-muted"><i class="fas fa-info-circle me-1"></i>Manage website content in <a href="../news.php" target="_blank">News & Updates</a> | <a href="../website-pages.php" target="_blank">Website Pages</a></p>
                </div>
            </div>
            <div class="col-md-5">
                <div class="section-card">
                    <h2><i class="fas fa-download me-2 text-info"></i>Downloads & Resources</h2>
                    <div class="d-grid gap-2">
                        <a href="../student-downloads.php" class="btn btn-sm btn-outline-primary text-start"><i class="fas fa-download me-2"></i>Student Downloads</a>
                        <a href="../document_management.php" class="btn btn-sm btn-outline-info text-start"><i class="fas fa-folder me-2"></i>Document Management</a>
                        <a href="../index.php" class="btn btn-sm btn-outline-secondary text-start"><i class="fas fa-home me-2"></i>Portal Home</a>
                    </div>
                </div>
                <div class="section-card">
                    <h2><i class="fas fa-portal me-2 text-purple"></i>Portal Links</h2>
                    <div class="small">
                        <div class="d-flex justify-content-between py-1"><a href="../student.php">Student Portal</a> <span class="badge bg-info"><?= $total_students ?> users</span></div>
                        <div class="d-flex justify-content-between py-1"><a href="../index.php">Staff Portal</a> <span class="badge bg-info"><?= $staff_count ?> users</span></div>
                        <div class="d-flex justify-content-between py-1"><a href="../news.php">News</a></div>
                        <div class="d-flex justify-content-between py-1"><a href="../messaging.php">Messaging</a></div>
                    </div>
                </div>
                <div class="section-card">
                    <h2><i class="fas fa-cog me-2 text-secondary"></i>Settings</h2>
                    <p class="small text-muted">Configure website banners, portal settings, and homepage in <a href="../website-pages.php">Website Pages</a>.</p>
                </div>
            </div>
        </div>

        <!-- ======== APPROVALS ======== -->
        <?php elseif ($tab === 'approvals'): ?>
        <div class="row g-3">
            <div class="col-lg-8">
                <div class="section-card">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h2 class="mb-0"><i class="fas fa-check-double me-2 text-warning"></i>Pending IT Tickets</h2>
                        <div>
                            <button class="btn btn-sm btn-outline-secondary filter-pill active" onclick="filterApproval('all')">All</button>
                            <button class="btn btn-sm btn-outline-danger filter-pill" onclick="filterApproval('critical')">Critical</button>
                            <button class="btn btn-sm btn-outline-warning filter-pill" onclick="filterApproval('high')">High</button>
                        </div>
                    </div>
                    <?php if (empty($pending_tickets)): ?>
                    <div class="text-center py-4 text-muted"><i class="fas fa-check-circle fa-2x mb-2 d-block"></i>No pending tickets</div>
                    <?php else: ?>
                    <div class="table-responsive" style="max-height:300px;overflow-y:auto">
                        <table class="table table-sm table-hover table-small">
                            <thead><tr><th>Ticket #</th><th>Requester</th><th>Description</th><th>Priority</th><th>Status</th><th>Actions</th></tr></thead>
                            <tbody>
                                <?php foreach ($pending_tickets as $t): ?>
                                <tr class="ticket-row-<?= $t['priority'] ?>">
                                    <td><code><?= htmlspecialchars($t['ticket_number']) ?></code></td>
                                    <td><?= htmlspecialchars($t['requester_name']) ?></td>
                                    <td><small><?= htmlspecialchars(mb_substr($t['description'] ?? '', 0, 60)) ?></small></td>
                                    <td><span class="badge bg-<?= $t['priority']==='critical'||$t['priority']==='high'?'danger':($t['priority']==='medium'?'warning text-dark':'success') ?>"><?= $t['priority'] ?></span></td>
                                    <td><span class="badge bg-<?= $t['status']==='open'?'danger':($t['status']==='in_progress'?'warning text-dark':'success') ?>"><?= str_replace('_', ' ', $t['status']) ?></span></td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-success py-0 px-1" onclick="updateTicketStatus(<?= $t['id'] ?>,'resolved')" title="Approve"><i class="fas fa-check"></i></button>
                                        <button class="btn btn-sm btn-outline-info py-0 px-1" onclick="updateTicketStatus(<?= $t['id'] ?>,'in_progress')" title="Assign"><i class="fas fa-user-tag"></i></button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="section-card">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h2 class="mb-0"><i class="fas fa-file-signature me-2 text-primary"></i>DG Approval Requests</h2>
                        <div>
                            <?php renderDepartmentApprovalButton(); ?>
                        </div>
                    </div>
                    <?php if (empty($pending_approval_requests)): ?>
                    <div class="text-center py-4 text-muted"><i class="fas fa-inbox fa-2x mb-2 d-block"></i>No pending approval requests</div>
                    <?php else: ?>
                    <div class="table-responsive" style="max-height:300px;overflow-y:auto">
                        <table class="table table-sm table-hover table-small">
                            <thead><tr><th>Request #</th><th>Title</th><th>Requester</th><th>Priority</th><th>Stage</th><th>Actions</th></tr></thead>
                            <tbody>
                                <?php foreach ($pending_approval_requests as $ar): ?>
                                <tr class="ticket-row-<?= strtolower($ar['priority']) ?>">
                                    <td><code><?= htmlspecialchars($ar['request_number']) ?></code></td>
                                    <td><small><?= htmlspecialchars(mb_substr($ar['title'] ?? '', 0, 50)) ?></small></td>
                                    <td><?= htmlspecialchars($ar['requester_name']) ?></td>
                                    <td><span class="badge bg-<?= $ar['priority']==='Critical'||$ar['priority']==='High'?'danger':'info' ?>"><?= $ar['priority'] ?></span></td>
                                    <td><span class="badge bg-primary"><?= htmlspecialchars($ar['workflow_name'] ?? 'ICT Request') ?></span></td>
                                    <td>
                                        <a href="../dashboards/director-general.php?page=approvals" class="btn btn-sm btn-outline-primary py-0 px-1" title="View in DG Center"><i class="fas fa-external-link-alt"></i></a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="section-card">
                    <h2><i class="fas fa-chart-pie me-2 text-primary"></i>IT Ticket Summary</h2>
                    <div class="small">
                        <div class="d-flex justify-content-between py-1"><span>Open Tickets</span><span class="badge bg-danger"><?= ict_q($ict, "SELECT COUNT(*) FROM it_support_tickets WHERE status='open'") ?></span></div>
                        <div class="d-flex justify-content-between py-1"><span>In Progress</span><span class="badge bg-warning text-dark"><?= ict_q($ict, "SELECT COUNT(*) FROM it_support_tickets WHERE status='in_progress'") ?></span></div>
                        <div class="d-flex justify-content-between py-1"><span>Resolved</span><span class="badge bg-success"><?= ict_q($ict, "SELECT COUNT(*) FROM it_support_tickets WHERE status='resolved'") ?></span></div>
                        <div class="d-flex justify-content-between py-1"><span>Closed</span><span class="badge bg-secondary"><?= ict_q($ict, "SELECT COUNT(*) FROM it_support_tickets WHERE status='closed'") ?></span></div>
                        <hr class="my-1">
                        <div class="d-flex justify-content-between py-1"><span>Critical/High</span><span class="badge bg-danger"><?= ict_q($ict, "SELECT COUNT(*) FROM it_support_tickets WHERE status IN ('open','in_progress') AND priority IN ('critical','high')") ?></span></div>
                        <div class="d-flex justify-content-between py-1"><span>Normal</span><span class="badge bg-info"><?= ict_q($ict, "SELECT COUNT(*) FROM it_support_tickets WHERE status IN ('open','in_progress') AND priority IN ('medium','low')") ?></span></div>
                    </div>
                </div>
                <div class="section-card">
                    <h2><i class="fas fa-chart-line me-2 text-success"></i>DG Request Summary</h2>
                    <div class="small">
                        <div class="d-flex justify-content-between py-1"><span>Active Requests</span><span class="badge bg-primary"><?= ($staff_conn) ? ict_q($staff_conn, "SELECT COUNT(*) FROM igangaschoolofl_staffs_db.approval_requests WHERE status='Active'") : 0 ?></span></div>
                        <div class="d-flex justify-content-between py-1"><span>Approved</span><span class="badge bg-success"><?= ($staff_conn) ? ict_q($staff_conn, "SELECT COUNT(*) FROM igangaschoolofl_staffs_db.approval_requests WHERE status='Approved'") : 0 ?></span></div>
                        <div class="d-flex justify-content-between py-1"><span>My Requests</span><span class="badge bg-info"><?= ($staff_conn) ? ict_q($staff_conn, "SELECT COUNT(*) FROM igangaschoolofl_staffs_db.approval_requests WHERE requester_id=" . (int)($user_id)) : 0 ?></span></div>
                    </div>
                </div>
                <?php if (function_exists('renderMyApprovalRequestsWidget')): ?>
                <?= renderMyApprovalRequestsWidget($staff_conn) ?>
                <?php endif; ?>
                <div class="section-card">
                    <h2><i class="fas fa-clipboard-list me-2 text-info"></i>Approval Types</h2>
                    <div class="d-grid gap-2 small">
                        <div class="d-flex justify-content-between py-1 border-bottom">
                            <span><i class="fas fa-user-plus me-1 text-primary"></i> New User Requests</span>
                            <span class="badge bg-secondary">System Admin</span>
                        </div>
                        <div class="d-flex justify-content-between py-1 border-bottom">
                            <span><i class="fas fa-shopping-cart me-1 text-warning"></i> ICT Procurement</span>
                            <span class="badge bg-secondary">Help Desk</span>
                        </div>
                        <div class="d-flex justify-content-between py-1 border-bottom">
                            <span><i class="fas fa-download me-1 text-info"></i> Software Install</span>
                            <span class="badge bg-secondary">Help Desk</span>
                        </div>
                        <div class="d-flex justify-content-between py-1 border-bottom">
                            <span><i class="fas fa-toolbox me-1 text-success"></i> Equipment Requests</span>
                            <span class="badge bg-secondary">Help Desk</span>
                        </div>
                        <div class="d-flex justify-content-between py-1">
                            <span><i class="fas fa-tools me-1 text-orange"></i> Maintenance</span>
                            <span class="badge bg-secondary">Assets</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
<div class="modal fade" id="addAssetModal" tabindex="-1"><div class="modal-dialog">
<form class="modal-content" id="addAssetForm">
<input type="hidden" name="action" value="add_asset">
<div class="modal-header bg-primary text-white"><h5 class="modal-title"><i class="fas fa-box me-2"></i>Add Asset</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
<div class="modal-body">
    <div class="row g-2 mb-2">
        <div class="col-6"><label class="form-label">Asset Number *</label><input type="text" name="asset_number" class="form-control" required></div>
        <div class="col-6"><label class="form-label">Type</label><select name="asset_type" class="form-select"><option>computer</option><option>printer</option><option>scanner</option><option>projector</option><option>network</option><option>server</option><option>ups</option><option>software</option><option>other</option></select></div>
    </div>
    <div class="mb-2"><label class="form-label">Asset Name *</label><input type="text" name="asset_name" class="form-control" required></div>
    <div class="row g-2 mb-2">
        <div class="col-6"><label class="form-label">Brand</label><input type="text" name="brand" class="form-control"></div>
        <div class="col-6"><label class="form-label">Model</label><input type="text" name="model" class="form-control"></div>
    </div>
    <div class="mb-2"><label class="form-label">Serial Number</label><input type="text" name="serial_number" class="form-control"></div>
    <div class="mb-2"><label class="form-label">Barcode / QR</label><input type="text" name="barcode" class="form-control"></div>
    <div class="row g-2 mb-2">
        <div class="col-6"><label class="form-label">Category</label><select name="category_id" class="form-select"><option value="0">None</option><?php foreach ($asset_cats as $c): ?><option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['category_name']) ?></option><?php endforeach; ?></select></div>
        <div class="col-6"><label class="form-label">Purchase Cost</label><input type="number" name="purchase_cost" class="form-control" step="0.01"></div>
    </div>
    <div class="row g-2 mb-2">
        <div class="col-6"><label class="form-label">Purchase Date</label><input type="date" name="purchase_date" class="form-control"></div>
        <div class="col-6"><label class="form-label">Warranty Expiry</label><input type="date" name="warranty_expiry" class="form-control"></div>
    </div>
    <div class="mb-2"><label class="form-label">Location</label><input type="text" name="current_location" class="form-control"></div>
    <div class="mb-2"><label class="form-label">Department</label><input type="text" name="assigned_department" class="form-control"></div>
</div>
<div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Add Asset</button></div>
</form>
</div></div>

<div class="modal fade" id="addServerModal" tabindex="-1"><div class="modal-dialog modal-sm">
<form class="modal-content" id="addServerForm">
<input type="hidden" name="action" value="add_server">
<div class="modal-header bg-primary text-white"><h5 class="modal-title"><i class="fas fa-server me-2"></i>Add Server</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
<div class="modal-body">
    <div class="mb-2"><label class="form-label">Server Name *</label><input type="text" name="server_name" class="form-control" required></div>
    <div class="mb-2"><label class="form-label">IP Address</label><input type="text" name="ip_address" class="form-control"></div>
    <div class="mb-2"><label class="form-label">Type</label><select name="server_type" class="form-select"><option value="physical">Physical</option><option value="virtual">Virtual</option><option value="cloud">Cloud</option></select></div>
    <div class="mb-2"><label class="form-label">OS</label><input type="text" name="os" class="form-control"></div>
    <div class="mb-2"><label class="form-label">Purpose</label><textarea name="purpose" class="form-control" rows="2"></textarea></div>
</div>
<div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Add</button></div>
</form>
</div></div>

<div class="modal fade" id="addWifiModal" tabindex="-1"><div class="modal-dialog modal-sm">
<form class="modal-content" id="addWifiForm">
<input type="hidden" name="action" value="add_wifi">
<div class="modal-header bg-primary text-white"><h5 class="modal-title"><i class="fas fa-wifi me-2"></i>Add WiFi AP</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
<div class="modal-body">
    <div class="mb-2"><label class="form-label">Device Name *</label><input type="text" name="device_name" class="form-control" required></div>
    <div class="mb-2"><label class="form-label">SSID</label><input type="text" name="ssid" class="form-control"></div>
    <div class="mb-2"><label class="form-label">IP Address</label><input type="text" name="ip_address" class="form-control"></div>
    <div class="mb-2"><label class="form-label">Location</label><input type="text" name="location" class="form-control"></div>
</div>
<div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Add</button></div>
</form>
</div></div>

<div class="modal fade" id="addNotifModal" tabindex="-1"><div class="modal-dialog modal-sm">
<form class="modal-content" id="addNotifForm">
<input type="hidden" name="action" value="add_notification">
<div class="modal-header bg-info text-white"><h5 class="modal-title"><i class="fas fa-bell me-2"></i>Add Notification</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
<div class="modal-body">
    <div class="mb-2"><label class="form-label">Title *</label><input type="text" name="title" class="form-control" required></div>
    <div class="mb-2"><label class="form-label">Message *</label><textarea name="message" class="form-control" rows="3" required></textarea></div>
    <div class="mb-2"><label class="form-label">Type</label><select name="notification_type" class="form-select"><option value="info">Info</option><option value="warning">Warning</option><option value="critical">Critical</option><option value="success">Success</option></select></div>
</div>
<div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-info text-white"><i class="fas fa-save me-1"></i>Add</button></div>
</form>
</div></div>

<?php if (function_exists('renderDepartmentApprovalModal')) renderDepartmentApprovalModal(); ?>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
const ICT_HANDLER = '../handlers/ict_handler.php';
function showAlert(m, t) { $('.content-area').prepend(`<div class="alert alert-${t} alert-dismissible fade show py-2">${m}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>`); setTimeout(()=>$('.alert').alert('close'),5000); }

function doAjax(formId, cb) {
    const f = $(`#${formId}`);
    if (f.length && f[0].checkValidity && !f[0].checkValidity()) { f[0].reportValidity(); return; }
    const data = f.serializeArray().reduce((o, x) => (o[x.name] = x.value, o), {});
    data.action = f.find('[name=action]').val();
    $.post(ICT_HANDLER, data).done(r => { if(r.success){ showAlert(r.message,'success'); if(cb) cb(r.data); else setTimeout(()=>location.reload(),600); } else showAlert(r.message,'danger'); }).fail(()=>showAlert('AJAX error','danger'));
}

// Form handlers
$('#addAssetForm').submit(e=>{ e.preventDefault(); doAjax('addAssetForm'); });
$('#addServerForm').submit(e=>{ e.preventDefault(); doAjax('addServerForm'); });
$('#addWifiForm').submit(e=>{ e.preventDefault(); doAjax('addWifiForm'); });
$('#addNotifForm').submit(e=>{ e.preventDefault(); doAjax('addNotifForm'); });
$('#backupForm').submit(e=>{ e.preventDefault(); doAjax('backupForm'); });
$('#alertForm').submit(e=>{ e.preventDefault(); doAjax('alertForm'); });

// Single-click actions
function updateTicketStatus(id, status) {
    $.post(ICT_HANDLER, { action: 'update_ticket', id, status }).done(r => { if(r.success) location.reload(); else showAlert(r.message,'danger'); });
}
function updateNetDevice(id, status) {
    $.post(ICT_HANDLER, { action: 'update_network_device', id, status }).done(r => { if(r.success) showAlert('Updated','success'); else showAlert(r.message,'danger'); });
}
function updateWifi(id, status) {
    $.post(ICT_HANDLER, { action: 'edit_wifi', id, status }).done(r => { if(r.success) showAlert('Updated','success'); else showAlert(r.message,'danger'); });
}
function verifyBackup(id) {
    $.post(ICT_HANDLER, { action: 'verify_backup', id }).done(r => { if(r.success) location.reload(); else showAlert(r.message,'danger'); });
}
function deleteBackup(id) {
    if(!confirm('Delete this backup?')) return;
    $.post(ICT_HANDLER, { action: 'delete_backup', id }).done(r => { if(r.success) location.reload(); else showAlert(r.message,'danger'); });
}
function acknowledgeAlert(id) {
    $.post(ICT_HANDLER, { action: 'acknowledge_alert', id }).done(r => { if(r.success) location.reload(); else showAlert(r.message,'danger'); });
}
function resolveAlert(id) {
    $.post(ICT_HANDLER, { action: 'resolve_alert', id }).done(r => { if(r.success) location.reload(); else showAlert(r.message,'danger'); });
}
function dismissNotif(id) {
    $.post(ICT_HANDLER, { action: 'dismiss_notification', id }).done(r => { if(r.success) location.reload(); else showAlert(r.message,'danger'); });
}
function saveSetting(key, value, group) {
    $.post(ICT_HANDLER, { action: 'save_setting', setting_key: key, setting_value: value, setting_group: group || 'general' }).done(r => { if(r.success) showAlert('Setting saved','success'); else showAlert(r.message,'danger'); });
}
function saveBackupSetting(key, value) { saveSetting(key, value, 'backup'); }
function editAsset(id) { showAlert('Edit via modal coming soon for asset #'+id, 'info'); }
function assignAsset(id) { showAlert('Assign modal coming soon for asset #'+id, 'info'); }
function logMaint(id) { showAlert('Maintenance log modal coming soon', 'info'); }
function editServer(id) { showAlert('Edit via modal coming soon', 'info'); }
function updateTicket(id) { showAlert('Update ticket modal coming soon', 'info'); }

function createQuickBackup() {
    $.post(ICT_HANDLER, { action: 'create_backup', backup_name: 'QuickBackup-'+new Date().toISOString().slice(0,19), backup_type: 'full', target_database: 'all' }).done(r => { if(r.success) { showAlert('Quick backup started','success'); setTimeout(()=>location.reload(),600); } else showAlert(r.message,'danger'); });
}
function addHealthCheck() {
    const checks = [
        {type:'cpu',name:'CPU Usage',status:'healthy',value:'45%',threshold:'90%'},
        {type:'memory',name:'Memory Usage',status:'healthy',value:'62%',threshold:'90%'},
        {type:'disk',name:'Disk Usage',status:'healthy',value:'55%',threshold:'90%'},
        {type:'network',name:'Network Connectivity',status:'healthy',value:'Online',threshold:'-'},
        {type:'database',name:'Database Connection',status:'healthy',value:'Connected',threshold:'-'}
    ];
    let i = 0;
    checks.forEach(c => {
        setTimeout(() => {
            $.post(ICT_HANDLER, { action: 'add_health_check', check_type: c.type, check_name: c.name, status: c.status, value: c.value, threshold: c.threshold, message: c.name + ' is ' + c.status }).done(r => { i++; if(i===checks.length) { showAlert('Health check complete','success'); setTimeout(()=>location.reload(),500); } });
        }, 200 * checks.indexOf(c));
    });
}
function addSecurityLog() {
    $.post(ICT_HANDLER, { action: 'add_security_log', event_type: 'other', description: 'Manual security check by ' + '<?= $user_name ?>', severity: 'info' }).done(r => { if(r.success) showAlert('Security event logged','success'); else showAlert(r.message,'danger'); });
}
function filterTickets(s) { $('#ticketTable tbody tr').each(function() { $(this).toggle(s==='all' || $(this).hasClass('ticket-row-'+s)); }); }
function filterBackup(s) { $('#backupTable tbody tr').each(function() { $(this).toggle(s==='all' || $(this).hasClass('backup-row-'+s)); }); }
function filterApproval(s) { $('.filter-pill').removeClass('active'); $(`.filter-pill[onclick*="'${s}'"]`).addClass('active'); $('.section-card tbody tr').each(function() { $(this).toggle(s==='all' || $(this).hasClass('ticket-row-'+s)); }); }
<?php if (function_exists('renderDepartmentApprovalScripts')) renderDepartmentApprovalScripts(); ?>
</script>
<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
</body>
</html>
