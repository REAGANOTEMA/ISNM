<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
require_once __DIR__ . '/../includes/global_search.php';
$ctx = bootstrapStaffDashboard(['system admin']);
$conn = $ctx['staff'];
$studentsConn = $ctx['students'];
$user = $ctx['user'];

$pageTitle = 'System Administration';
$view = $_GET['view'] ?? $_GET['page'] ?? $_GET['section'] ?? '';

$backups = []; $logs = []; $sync = []; $settings = []; $errorLogs = []; $cacheCount = 0;
if ($conn) {
    $r = $conn->query("SELECT * FROM backup_management ORDER BY created_at DESC LIMIT 50");
    if ($r) while ($row = $r->fetch_assoc()) $backups[] = $row;
    $r2 = $conn->query("SELECT * FROM system_logs ORDER BY created_at DESC LIMIT 50");
    if ($r2) while ($row = $r2->fetch_assoc()) $logs[] = $row;
    $r3 = $conn->query("SELECT * FROM data_sync_status ORDER BY last_sync DESC LIMIT 20");
    if ($r3) while ($row = $r3->fetch_assoc()) $sync[] = $row;
    $r4 = $conn->query("SELECT * FROM system_settings ORDER BY setting_name LIMIT 50");
    if ($r4) while ($row = $r4->fetch_assoc()) $settings[] = $row;
    $r5 = $conn->query("SELECT * FROM error_logs ORDER BY created_at DESC LIMIT 50");
    if ($r5) while ($row = $r5->fetch_assoc()) $errorLogs[] = $row;
    $r6 = $conn->query("SELECT COUNT(*) c FROM cache_management");
    if ($r6) $cacheCount = (int)$r6->fetch_assoc()['c'];
}

// Global search AJAX handler
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'global_stu_search') {
    globalStudentSearchHandler($conn, $studentsConn, $conn);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include_once __DIR__ . '/../includes/dashboard_head.php'; ?>
<style>
.sec-nav{display:flex;gap:2px;margin-bottom:18px;padding:6px;background:#f8fafc;border-radius:8px;border:1px solid #e2e8f0}
.sec-nav a{text-decoration:none;padding:7px 16px;border-radius:6px;font-size:0.85rem;color:#475569;transition:all 0.15s}
.sec-nav a:hover{background:#e2e8f0}
.sec-nav a.active{background:#3b82f6;color:#fff;font-weight:500}
.cs{display:none}.cs.active{display:block}
</style>
</head>
<body>
<?php include_once __DIR__ . '/../includes/sidebar.php'; ?>
<?php include_once __DIR__ . '/../includes/dashboard_topbar.php'; ?>
<div class="page-content">
    <div class="content-header d-flex justify-content-between align-items-center">
        <h1 class="mb-0"><i class="fas fa-cogs"></i> System Administration</h1>
        <div class="d-flex gap-2">
        <input type="text" class="form-control form-control-sm" id="sysSearchInput" placeholder="Filter tables..." style="width:200px" onkeyup="filterSysTables(this.value)">
        <button class="btn btn-sm btn-outline-secondary" onclick="window.print()" title="Print page"><i class="fas fa-print"></i></button>
        <button class="btn btn-sm btn-3d btn-3d-blue" onclick="openGlobalSearch()" title="Search students (Ctrl+K)">
          <i class="fas fa-search"></i> Global Search <small style="opacity:0.7">Ctrl+K</small>
        </button>
        </div>
    </div>
    <?php renderGlobalSearchBar($conn, $studentsConn); ?>

    <nav class="sec-nav">
        <a href="system-admin.php" class="<?= !$view ? 'active' : '' ?>"><i class="fas fa-tachometer-alt me-1"></i>Dashboard</a>
        <a href="system-admin.php?view=backup" class="<?= $view === 'backup' ? 'active' : '' ?>"><i class="fas fa-database me-1"></i>Backup & Restore</a>
    </nav>

    <?php if ($view === 'backup'): ?>

    <!-- ═══ BACKUP & RESTORE ═══ -->
    <div class="cs active" id="sec-backup">
    <div class="row mb-4">
        <div class="col-md-3"><div class="card"><div class="card-body"><h6>Total Backups</h6><h3><?= count($backups) ?></h3></div></div></div>
        <div class="col-md-3"><div class="card border-success"><div class="card-body"><h6>Successful</h6><h3 class="text-success"><?php $sok=0; foreach($backups as $b){ if(($b['status']??'completed')==='completed') $sok++; } echo $sok; ?></h3></div></div></div>
        <div class="col-md-3"><div class="card border-danger"><div class="card-body"><h6>Failed</h6><h3 class="text-danger"><?php $sfail=0; foreach($backups as $b){ if(($b['status']??'')==='failed') $sfail++; } echo $sfail; ?></h3></div></div></div>
        <div class="col-md-3"><div class="card border-info"><div class="card-body"><h6>Cache Entries</h6><h3 class="text-info"><?= $cacheCount ?></h3></div></div></div>
    </div>
    <div class="row">
        <div class="col-md-12">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-history me-2"></i>Backup History</h5>
                    <button class="btn btn-sm btn-primary" onclick="alert('Backup initiated. This may take a few minutes.')"><i class="fas fa-plus me-1"></i>Create Backup</button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead><tr><th>File</th><th>Type</th><th>Size</th><th>Date</th><th>Status</th><th>Actions</th></tr></thead>
                            <tbody>
                                <?php foreach ($backups as $b): ?>
                                <tr>
                                    <td><?= htmlspecialchars($b['file_name'] ?? $b['name'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($b['backup_type'] ?? $b['type'] ?? '-') ?></td>
                                    <td><?= $b['file_size'] ?? $b['size'] ?? '-' ?></td>
                                    <td><?= $b['created_at'] ?? '-' ?></td>
                                    <td><span class="badge bg-<?= ($b['status'] ?? 'completed') === 'completed' ? 'success' : 'warning' ?>"><?= $b['status'] ?? 'completed' ?></span></td>
                                    <td><button class="btn btn-sm btn-outline-primary py-0 px-1" onclick="alert('Restore from <?= htmlspecialchars($b['file_name'] ?? 'backup') ?>?')"><i class="fas fa-download"></i></button></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if (empty($backups)): ?><tr><td colspan="6" class="text-center">No backups recorded</td></tr><?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header"><h5>Error Logs</h5></div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead><tr><th>Error</th><th>File</th><th>Line</th><th>Date</th></tr></thead>
                            <tbody>
                                <?php foreach ($errorLogs as $e): ?>
                                <tr>
                                    <td><?= htmlspecialchars(substr($e['error_message'] ?? $e['message'] ?? '', 0, 50)) ?></td>
                                    <td><?= htmlspecialchars(basename($e['file'] ?? $e['script'] ?? '')) ?></td>
                                    <td><?= $e['line'] ?? '-' ?></td>
                                    <td><?= $e['created_at'] ?? '-' ?></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if (empty($errorLogs)): ?><tr><td colspan="4" class="text-center">No error logs</td></tr><?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header"><h5>Data Sync Status</h5></div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead><tr><th>Table/Dataset</th><th>Last Sync</th><th>Status</th><th>Records</th></tr></thead>
                            <tbody>
                                <?php foreach ($sync as $s): ?>
                                <tr>
                                    <td><?= htmlspecialchars($s['table_name'] ?? $s['dataset'] ?? '-') ?></td>
                                    <td><?= $s['last_sync'] ?? '-' ?></td>
                                    <td><span class="badge bg-<?= ($s['status'] ?? 'synced') === 'synced' ? 'success' : 'danger' ?>"><?= $s['status'] ?? 'synced' ?></span></td>
                                    <td><?= $s['records_count'] ?? $s['count'] ?? '-' ?></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if (empty($sync)): ?><tr><td colspan="4" class="text-center">No sync records</td></tr><?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </div>

    <?php else: ?>

    <!-- ═══ DEFAULT DASHBOARD ═══ -->
    <div class="cs active" id="sec-dashboard">
    <div class="row mb-4">
        <div class="col-md-2"><div class="card"><div class="card-body"><h6>Backups</h6><h3><?= count($backups) ?></h3></div></div></div>
        <div class="col-md-2"><div class="card"><div class="card-body"><h6>System Logs</h6><h3><?= count($logs) ?></h3></div></div></div>
        <div class="col-md-2"><div class="card"><div class="card-body"><h6>Sync Records</h6><h3><?= count($sync) ?></h3></div></div></div>
        <div class="col-md-2"><div class="card"><div class="card-body"><h6>Settings</h6><h3><?= count($settings) ?></h3></div></div></div>
        <div class="col-md-2"><div class="card"><div class="card-body"><h6>Errors</h6><h3><?= count($errorLogs) ?></h3></div></div></div>
        <div class="col-md-2"><div class="card"><div class="card-body"><h6>Cache</h6><h3><?= $cacheCount ?></h3></div></div></div>
    </div>
    <div class="row">
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header"><h5>Backup History</h5></div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead><tr><th>File</th><th>Type</th><th>Size</th><th>Date</th><th>Status</th></tr></thead>
                            <tbody>
                                <?php foreach ($backups as $b): ?>
                                <tr>
                                    <td><?= htmlspecialchars($b['file_name'] ?? $b['name'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($b['backup_type'] ?? $b['type'] ?? '-') ?></td>
                                    <td><?= $b['file_size'] ?? $b['size'] ?? '-' ?></td>
                                    <td><?= $b['created_at'] ?? '-' ?></td>
                                    <td><span class="badge bg-<?= ($b['status'] ?? 'completed') === 'completed' ? 'success' : 'warning' ?>"><?= $b['status'] ?? 'completed' ?></span></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if (empty($backups)): ?><tr><td colspan="5" class="text-center">No backups recorded</td></tr><?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header"><h5>System Settings</h5></div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead><tr><th>Setting</th><th>Value</th><th>Description</th></tr></thead>
                            <tbody>
                                <?php foreach ($settings as $s): ?>
                                <tr>
                                    <td><?= htmlspecialchars($s['setting_name'] ?? $s['key'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars(substr($s['setting_value'] ?? $s['value'] ?? '', 0, 40)) ?></td>
                                    <td><?= htmlspecialchars(substr($s['description'] ?? '', 0, 40)) ?></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if (empty($settings)): ?><tr><td colspan="3" class="text-center">No settings defined</td></tr><?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header"><h5>Error Logs</h5></div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead><tr><th>Error</th><th>File</th><th>Line</th><th>Date</th></tr></thead>
                            <tbody>
                                <?php foreach ($errorLogs as $e): ?>
                                <tr>
                                    <td><?= htmlspecialchars(substr($e['error_message'] ?? $e['message'] ?? '', 0, 50)) ?></td>
                                    <td><?= htmlspecialchars(basename($e['file'] ?? $e['script'] ?? '')) ?></td>
                                    <td><?= $e['line'] ?? '-' ?></td>
                                    <td><?= $e['created_at'] ?? '-' ?></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if (empty($errorLogs)): ?><tr><td colspan="4" class="text-center">No error logs</td></tr><?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header"><h5>Data Sync Status</h5></div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead><tr><th>Table/Dataset</th><th>Last Sync</th><th>Status</th><th>Records</th></tr></thead>
                            <tbody>
                                <?php foreach ($sync as $s): ?>
                                <tr>
                                    <td><?= htmlspecialchars($s['table_name'] ?? $s['dataset'] ?? '-') ?></td>
                                    <td><?= $s['last_sync'] ?? '-' ?></td>
                                    <td><span class="badge bg-<?= ($s['status'] ?? 'synced') === 'synced' ? 'success' : 'danger' ?>"><?= $s['status'] ?? 'synced' ?></span></td>
                                    <td><?= $s['records_count'] ?? $s['count'] ?? '-' ?></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if (empty($sync)): ?><tr><td colspan="4" class="text-center">No sync records</td></tr><?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </div>

    <?php endif; ?>
</div>
<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
<script>
function filterSysTables(q) {
    q = q.toLowerCase();
    document.querySelectorAll('.table-responsive table').forEach(function(tbl) {
        tbl.querySelectorAll('tbody tr').forEach(function(tr) {
            var txt = tr.textContent.toLowerCase();
            tr.style.display = txt.indexOf(q) > -1 ? '' : 'none';
        });
    });
}
function openGlobalSearch() {
    var el = document.querySelector('.global-search-bar input, .student-lookup, [onkeyup*="search"]');
    if (el) el.focus();
}
</script>
</body>
</html>
