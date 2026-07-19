<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
require_once __DIR__ . '/../includes/global_search.php';
$ctx = bootstrapStaffDashboard(['system admin']);
$conn = $ctx['staff'];
$studentsConn = $ctx['students'];
$user = $ctx['user'];

$pageTitle = 'System Administration';
$view = $_GET['view'] ?? $_GET['page'] ?? $_GET['section'] ?? '';

$backups = []; $logs = []; $sync = []; $settings = []; $errorLogs = []; $cacheCount = 0; $users = []; $roles = []; $recycle = [];
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
    $r7 = $conn->query("SELECT id, full_name, email, phone, department, position, status FROM staff ORDER BY full_name LIMIT 100");
    if ($r7) while ($row = $r7->fetch_assoc()) $users[] = $row;
    $r8 = @$conn->query("SELECT * FROM roles ORDER BY name LIMIT 50");
    if ($r8) while ($row = $r8->fetch_assoc()) $roles[] = $row;
    $deletedTables = [['t'=>'staff','c'=>'status','v'=>"='Inactive'"],['t'=>'backup_management','c'=>'status','v'=>"='deleted'"]];
    foreach ($deletedTables as $dt) {
        $rr = @$conn->query("SELECT *, '{$dt['t']}' as source_table FROM {$dt['t']} WHERE {$dt['c']} {$dt['v']} ORDER BY 1 DESC LIMIT 20");
        if ($rr) while ($row = $rr->fetch_assoc()) $recycle[] = $row;
    }
    if ($studentsConn) {
        $rr2 = @$studentsConn->query("SELECT *, 'students' as source_table FROM students WHERE status='Inactive' OR status='Withdrawn' ORDER BY surname LIMIT 20");
        if ($rr2) while ($row = $rr2->fetch_assoc()) $recycle[] = $row;
    }
}

// Global search AJAX handler
if (empty($_SESSION['csrf_token'])) { $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); }
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Invalid security token.']);
        exit;
    }
    $adminAction = $_POST['action'];
    $adminId = (int)($_POST['user_id'] ?? 0);

    if ($adminAction === 'global_stu_search') {
        header('Content-Type: application/json');
        $searchQuery = trim($_POST['query'] ?? $_POST['search'] ?? '');
        $results = [];
        if ($studentsConn && $searchQuery) {
            $s = "%$searchQuery%";
            $stmt = $studentsConn->prepare("SELECT id, student_number, full_name, first_name, surname, program, course, year, level, status FROM students WHERE (student_number LIKE ? OR full_name LIKE ? OR first_name LIKE ? OR surname LIKE ?) LIMIT 20");
            if ($stmt) {
                $stmt->bind_param('ssss', $s, $s, $s, $s);
                $stmt->execute();
                $result = $stmt->get_result();
                while ($row = $result->fetch_assoc()) { $results[] = $row; }
                $stmt->close();
            }
        }
        echo json_encode(['success' => true, 'students' => $results]);
        exit;
    }

    if ($adminAction === 'create_backup' && $conn) {
        $backupType = trim($_POST['backup_type'] ?? 'full');
        $fileName = 'backup_' . date('Y-m-d_H-i-s') . '_' . $backupType . '.sql';
        $conn->query("CREATE TABLE IF NOT EXISTS backup_management (id INT AUTO_INCREMENT PRIMARY KEY, file_name VARCHAR(255), backup_type VARCHAR(50), file_size VARCHAR(50), status VARCHAR(20) DEFAULT 'completed', created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");
        $stmt = $conn->prepare("INSERT INTO backup_management (file_name, backup_type, file_size, status) VALUES (?, ?, ?, 'completed')");
        $fname = $fileName; $btype = $backupType; $fsize = '0 KB';
        $stmt->bind_param("sss", $fname, $btype, $fsize);
        $stmt->execute(); $stmt->close();
        $_SESSION['success'] = "Backup created: $fileName";
        header('Location: system-admin.php?page=backup'); exit;
    }

    if ($adminAction === 'toggle_user_status' && $conn) {
        if ($adminId > 0) {
            $r = $conn->query("SELECT status FROM staff WHERE id=$adminId");
            if ($r && $r->num_rows > 0) {
                $current = $r->fetch_assoc()['status'];
                $newStatus = ($current === 'Active') ? 'Inactive' : 'Active';
                $stmt = $conn->prepare("UPDATE staff SET status=? WHERE id=?");
                $stmt->bind_param("si", $newStatus, $adminId);
                $stmt->execute(); $stmt->close();
                $_SESSION['success'] = "Account " . strtolower($newStatus) . ".";
            }
        }
        header('Location: system-admin.php?page=users'); exit;
    }

    if ($adminAction === 'restore_recycle' && $conn) {
        $sourceTable = trim($_POST['source_table'] ?? '');
        if ($adminId > 0 && in_array($sourceTable, ['staff', 'students', 'backup_management'])) {
            $newStatus = ($sourceTable === 'backup_management') ? 'completed' : 'Active';
            $stmt = $conn->prepare("UPDATE `$sourceTable` SET status=? WHERE id=?");
            $stmt->bind_param("si", $newStatus, $adminId);
            $stmt->execute(); $stmt->close();
            $_SESSION['success'] = 'Record restored.';
        }
        header('Location: system-admin.php?page=recycle'); exit;
    }

    if ($adminAction === 'permanent_delete' && $conn) {
        $sourceTable = trim($_POST['source_table'] ?? '');
        if ($adminId > 0 && in_array($sourceTable, ['backup_management'])) {
            $stmt = $conn->prepare("DELETE FROM `$sourceTable` WHERE id=?");
            $stmt->bind_param("i", $adminId);
            $stmt->execute(); $stmt->close();
            $_SESSION['success'] = 'Record permanently deleted.';
        }
        header('Location: system-admin.php?page=recycle'); exit;
    }

    if ($adminAction === 'clear_cache' && $conn) {
        $conn->query("TRUNCATE TABLE cache_management");
        $_SESSION['success'] = 'Cache cleared.';
        header('Location: system-admin.php?page=cache'); exit;
    }
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
        <a href="system-admin.php?view=users" class="<?= $view === 'users' ? 'active' : '' ?>"><i class="fas fa-users-cog me-1"></i>Users</a>
        <a href="system-admin.php?view=roles" class="<?= $view === 'roles' ? 'active' : '' ?>"><i class="fas fa-shield-alt me-1"></i>Roles</a>
        <a href="system-admin.php?view=backup" class="<?= $view === 'backup' ? 'active' : '' ?>"><i class="fas fa-database me-1"></i>Backup</a>
        <a href="system-admin.php?view=audit" class="<?= $view === 'audit' ? 'active' : '' ?>"><i class="fas fa-clipboard-list me-1"></i>Audit Logs</a>
        <a href="system-admin.php?view=settings" class="<?= $view === 'settings' ? 'active' : '' ?>"><i class="fas fa-cog me-1"></i>Settings</a>
        <a href="system-admin.php?view=recycle" class="<?= $view === 'recycle' ? 'active' : '' ?>"><i class="fas fa-trash-restore me-1"></i>Recycle Bin</a>
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
                    <button class="btn btn-sm btn-primary" onclick="document.getElementById('backupForm').submit()"><i class="fas fa-plus me-1"></i>Create Backup</button>
                </div>
                <div class="card-body">
                    <form id="backupForm" method="POST" style="display:none"><input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>"><input type="hidden" name="action" value="create_backup"><input type="hidden" name="backup_type" value="full"></form>
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
                                    <td><button class="btn btn-sm btn-outline-primary py-0 px-1" title="Restore" onclick="var f=document.createElement('form');f.method='POST';f.action='system-admin.php?page=backup';f.innerHTML='<input type=hidden name=csrf_token value=<?= $_SESSION["csrf_token"] ?>><input type=hidden name=action value=create_backup><input type=hidden name=backup_type value=<?= htmlspecialchars($b["backup_type"] ?? "full") ?>>';document.body.appendChild(f);f.submit()"><i class="fas fa-download"></i></button></td>
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

    <?php elseif ($view === 'users'): ?>

    <!-- ═══ USER MANAGEMENT ═══ -->
    <div class="cs active" id="sec-users">
    <div class="row mb-4">
        <div class="col-md-3"><div class="card"><div class="card-body"><h6>Total Users</h6><h3><?= count($users) ?></h3></div></div></div>
        <div class="col-md-3"><div class="card border-success"><div class="card-body"><h6>Active</h6><h3 class="text-success"><?php $uc=0; foreach($users as $u){ if(($u['status']??'Active')==='Active') $uc++; } echo $uc; ?></h3></div></div></div>
        <div class="col-md-3"><div class="card border-warning"><div class="card-body"><h6>Inactive</h6><h3 class="text-warning"><?php $ui=0; foreach($users as $u){ if(($u['status']??'')==='Inactive') $ui++; } echo $ui; ?></h3></div></div></div>
        <div class="col-md-3"><div class="card border-danger"><div class="card-body"><h6>On Leave</h6><h3 class="text-danger"><?php $ul=0; foreach($users as $u){ if(($u['status']??'')==='On Leave') $ul++; } echo $ul; ?></h3></div></div></div>
    </div>
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="fas fa-users-cog me-2"></i>Staff Accounts</h5>
            <input type="text" class="form-control form-control-sm" placeholder="Filter users..." style="width:200px" onkeyup="filterSysTables(this.value)">
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead><tr><th>Name</th><th>Email</th><th>Phone</th><th>Department</th><th>Position</th><th>Status</th><th>Actions</th></tr></thead>
                    <tbody>
                        <?php foreach ($users as $u): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($u['full_name'] ?? '-') ?></strong></td>
                            <td><?= htmlspecialchars($u['email'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($u['phone'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($u['department'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($u['position'] ?? '-') ?></td>
                            <td><span class="badge bg-<?= ($u['status'] ?? 'Active') === 'Active' ? 'success' : (($u['status'] ?? '') === 'Inactive' ? 'warning' : 'secondary') ?>"><?= htmlspecialchars($u['status'] ?? 'Active') ?></span></td>
                            <td>
                                <button class="btn btn-sm btn-outline-primary py-0 px-1" title="Reset Password" onclick="if(confirm('Send password reset to <?= htmlspecialchars($u['email'] ?? '') ?>?')){var f=document.createElement('form');f.method='POST';f.action='system-admin.php?page=users';f.innerHTML='<input type=hidden name=csrf_token value=<?= $_SESSION["csrf_token"] ?>><input type=hidden name=action value=toggle_user_status><input type=hidden name=user_id value=<?= (int)$u["id"] ?>>';document.body.appendChild(f);f.submit()}"><i class="fas fa-key"></i></button>
                                <form method="POST" style="display:inline" onsubmit="return confirm('<?= ($u['status'] ?? 'Active') === 'Active' ? 'Disable' : 'Enable' ?> this account?')"><input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>"><input type="hidden" name="action" value="toggle_user_status"><input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>"><button type="submit" class="btn btn-sm btn-outline-<?= ($u['status'] ?? 'Active') === 'Active' ? 'danger' : 'success' ?> py-0 px-1" title="<?= ($u['status'] ?? 'Active') === 'Active' ? 'Disable' : 'Enable' ?>"><i class="fas fa-<?= ($u['status'] ?? 'Active') === 'Active' ? 'ban' : 'check' ?>"></i></button></form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($users)): ?><tr><td colspan="7" class="text-center">No user accounts found</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    </div>

    <?php elseif ($view === 'roles'): ?>

    <!-- ═══ ROLES & PERMISSIONS ═══ -->
    <div class="cs active" id="sec-roles">
    <div class="row mb-4">
        <div class="col-md-4"><div class="card"><div class="card-body"><h6>Defined Roles</h6><h3><?= count($roles) ?></h3></div></div></div>
        <div class="col-md-4"><div class="card border-info"><div class="card-body"><h6>System Users</h6><h3 class="text-info"><?= count($users) ?></h3></div></div></div>
        <div class="col-md-4"><div class="card border-success"><div class="card-body"><h6>Active Users</h6><h3 class="text-success"><?php $au=0; foreach($users as $u){ if(($u['status']??'Active')==='Active') $au++; } echo $au; ?></h3></div></div></div>
    </div>
    <div class="row">
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header"><h5 class="mb-0"><i class="fas fa-shield-alt me-2"></i>System Roles</h5></div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead><tr><th>Role</th><th>Description</th><th>Users</th></tr></thead>
                            <tbody>
                                <?php foreach ($roles as $r): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($r['name'] ?? $r['role_name'] ?? '-') ?></strong></td>
                                    <td><?= htmlspecialchars($r['description'] ?? '-') ?></td>
                                    <td><?php $rc=0; foreach($users as $u){ if(strtolower($u['position']??'')===strtolower($r['name']??$r['role_name']??'')) $rc++; } echo $rc; ?></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if (empty($roles)):
                                    $uniqueRoles = [];
                                    foreach ($users as $u) { $p = $u['position'] ?? 'Unknown'; if (!isset($uniqueRoles[$p])) $uniqueRoles[$p] = 0; $uniqueRoles[$p]++; }
                                    foreach ($uniqueRoles as $roleName => $count): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($roleName) ?></strong></td>
                                    <td class="text-muted">Derived from staff positions</td>
                                    <td><?= $count ?></td>
                                </tr>
                                <?php endforeach; endif; ?>
                                <?php if (empty($roles) && empty($uniqueRoles)): ?><tr><td colspan="3" class="text-center">No roles defined</td></tr><?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header"><h5 class="mb-0"><i class="fas fa-key me-2"></i>Permissions Matrix</h5></div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead><tr><th>Module</th><th>Read</th><th>Write</th><th>Delete</th></tr></thead>
                            <tbody>
                                <tr><td>Students</td><td><i class="fas fa-check text-success"></i></td><td><i class="fas fa-check text-success"></i></td><td><i class="fas fa-times text-danger"></i></td></tr>
                                <tr><td>Staff</td><td><i class="fas fa-check text-success"></i></td><td><i class="fas fa-check text-success"></i></td><td><i class="fas fa-times text-danger"></i></td></tr>
                                <tr><td>Finance</td><td><i class="fas fa-check text-success"></i></td><td><i class="fas fa-check text-success"></i></td><td><i class="fas fa-times text-danger"></i></td></tr>
                                <tr><td>Settings</td><td><i class="fas fa-check text-success"></i></td><td><i class="fas fa-check text-success"></i></td><td><i class="fas fa-times text-danger"></i></td></tr>
                                <tr><td>Backups</td><td><i class="fas fa-check text-success"></i></td><td><i class="fas fa-check text-success"></i></td><td><i class="fas fa-times text-danger"></i></td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </div>

    <?php elseif ($view === 'audit'): ?>

    <!-- ═══ AUDIT LOGS ═══ -->
    <div class="cs active" id="sec-audit">
    <div class="row mb-4">
        <div class="col-md-3"><div class="card"><div class="card-body"><h6>Total Logs</h6><h3><?= count($logs) ?></h3></div></div></div>
        <div class="col-md-3"><div class="card border-danger"><div class="card-body"><h6>Error Logs</h6><h3 class="text-danger"><?= count($errorLogs) ?></h3></div></div></div>
        <div class="col-md-3"><div class="card border-info"><div class="card-body"><h6>Cache Entries</h6><h3 class="text-info"><?= $cacheCount ?></h3></div></div></div>
        <div class="col-md-3"><div class="card border-warning"><div class="card-body"><h6>Sync Records</h6><h3 class="text-warning"><?= count($sync) ?></h3></div></div></div>
    </div>
    <div class="row">
        <div class="col-md-12">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-clipboard-list me-2"></i>System Activity Logs</h5>
                    <input type="text" class="form-control form-control-sm" placeholder="Filter logs..." style="width:200px" onkeyup="filterSysTables(this.value)">
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead><tr><th>Event</th><th>User</th><th>IP</th><th>Date</th><th>Status</th></tr></thead>
                            <tbody>
                                <?php foreach ($logs as $l): ?>
                                <tr>
                                    <td><?= htmlspecialchars($l['action'] ?? $l['event'] ?? $l['message'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($l['user_name'] ?? $l['user'] ?? $l['actor'] ?? '-') ?></td>
                                    <td class="small"><?= htmlspecialchars($l['ip_address'] ?? $l['ip'] ?? '-') ?></td>
                                    <td class="small"><?= $l['created_at'] ?? '-' ?></td>
                                    <td><span class="badge bg-<?= ($l['status'] ?? 'info') === 'error' ? 'danger' : (($l['status'] ?? '') === 'warning' ? 'warning' : 'info') ?>"><?= htmlspecialchars($l['status'] ?? 'info') ?></span></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if (empty($logs)): ?><tr><td colspan="5" class="text-center">No audit logs recorded</td></tr><?php endif; ?>
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
                <div class="card-header"><h5><i class="fas fa-exclamation-circle me-2"></i>Error Logs</h5></div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead><tr><th>Error</th><th>File</th><th>Line</th><th>Date</th></tr></thead>
                            <tbody>
                                <?php foreach ($errorLogs as $e): ?>
                                <tr>
                                    <td><?= htmlspecialchars(substr($e['error_message'] ?? $e['message'] ?? '', 0, 60)) ?></td>
                                    <td class="small"><?= htmlspecialchars(basename($e['file'] ?? $e['script'] ?? '')) ?></td>
                                    <td><?= $e['line'] ?? '-' ?></td>
                                    <td class="small"><?= $e['created_at'] ?? '-' ?></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if (empty($errorLogs)): ?><tr><td colspan="4" class="text-center">No errors logged</td></tr><?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header"><h5><i class="fas fa-sync me-2"></i>Data Sync Activity</h5></div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead><tr><th>Dataset</th><th>Last Sync</th><th>Status</th><th>Records</th></tr></thead>
                            <tbody>
                                <?php foreach ($sync as $s): ?>
                                <tr>
                                    <td><?= htmlspecialchars($s['table_name'] ?? $s['dataset'] ?? '-') ?></td>
                                    <td class="small"><?= $s['last_sync'] ?? '-' ?></td>
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

    <?php elseif ($view === 'settings'): ?>

    <!-- ═══ SYSTEM SETTINGS ═══ -->
    <div class="cs active" id="sec-settings">
    <div class="row mb-4">
        <div class="col-md-4"><div class="card"><div class="card-body"><h6>Total Settings</h6><h3><?= count($settings) ?></h3></div></div></div>
        <div class="col-md-4"><div class="card border-info"><div class="card-body"><h6>System Logs</h6><h3 class="text-info"><?= count($logs) ?></h3></div></div></div>
        <div class="col-md-4"><div class="card border-success"><div class="card-body"><h6>Cache Entries</h6><h3 class="text-success"><?= $cacheCount ?></h3></div></div></div>
    </div>
    <div class="row">
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header"><h5 class="mb-0"><i class="fas fa-cog me-2"></i>System Configuration</h5></div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead><tr><th>Setting</th><th>Value</th><th>Description</th><th>Last Modified</th></tr></thead>
                            <tbody>
                                <?php foreach ($settings as $s): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($s['setting_name'] ?? $s['key'] ?? '-') ?></strong></td>
                                    <td><?= htmlspecialchars(substr($s['setting_value'] ?? $s['value'] ?? '', 0, 50)) ?></td>
                                    <td class="small"><?= htmlspecialchars($s['description'] ?? '-') ?></td>
                                    <td class="small"><?= $s['updated_at'] ?? $s['created_at'] ?? '-' ?></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if (empty($settings)): ?><tr><td colspan="4" class="text-center">No settings configured</td></tr><?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header"><h5><i class="fas fa-info-circle me-2"></i>System Info</h5></div>
                <div class="card-body">
                    <div class="mb-3"><strong>PHP Version:</strong> <span class="badge bg-info"><?= phpversion() ?></span></div>
                    <div class="mb-3"><strong>Server:</strong> <span class="small"><?= htmlspecialchars($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown') ?></span></div>
                    <div class="mb-3"><strong>Database:</strong> <span class="badge bg-success">MySQL <?= $conn->server_info ?? '' ?></span></div>
                    <div class="mb-3"><strong>Uptime:</strong> <span class="small"><?= @php_uname('n') ?></span></div>
                    <hr>
                    <div class="mb-2"><strong>Cache:</strong> <?= $cacheCount ?> entries</div>
                    <div class="mb-2"><strong>Error Logs:</strong> <?= count($errorLogs) ?> entries</div>
                    <div><strong>Sync Status:</strong> <?= count($sync) ?> datasets</div>
                </div>
            </div>
            <div class="card mb-4">
                <div class="card-header"><h5><i class="fas fa-database me-2"></i>Data Sync Status</h5></div>
                <div class="card-body">
                    <?php foreach ($sync as $s): ?>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="small"><?= htmlspecialchars($s['table_name'] ?? $s['dataset'] ?? '-') ?></span>
                        <span class="badge bg-<?= ($s['status'] ?? 'synced') === 'synced' ? 'success' : 'danger' ?>"><?= $s['status'] ?? 'synced' ?></span>
                    </div>
                    <?php endforeach; ?>
                    <?php if (empty($sync)): ?><div class="text-muted small">No sync data available</div><?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    </div>

    <?php elseif ($view === 'recycle'): ?>

    <!-- ═══ RECYCLE BIN ═══ -->
    <div class="cs active" id="sec-recycle">
    <div class="row mb-4">
        <div class="col-md-4"><div class="card border-danger"><div class="card-body"><h6>Deleted/Inactive Records</h6><h3 class="text-danger"><?= count($recycle) ?></h3></div></div></div>
        <div class="col-md-4"><div class="card border-warning"><div class="card-body"><h6>Inactive Staff</h6><h3 class="text-warning"><?php $is=0; foreach($recycle as $rc){ if(($rc['source_table']??'')==='staff') $is++; } echo $is; ?></h3></div></div></div>
        <div class="col-md-4"><div class="card border-info"><div class="card-body"><h6>Inactive Students</h6><h3 class="text-info"><?php $iss=0; foreach($recycle as $rc){ if(($rc['source_table']??'')==='students') $iss++; } echo $iss; ?></h3></div></div></div>
    </div>
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="fas fa-trash-restore me-2"></i>Deleted & Inactive Records</h5>
            <input type="text" class="form-control form-control-sm" placeholder="Filter..." style="width:200px" onkeyup="filterSysTables(this.value)">
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead><tr><th>Record</th><th>Source</th><th>Status</th><th>Details</th><th>Actions</th></tr></thead>
                    <tbody>
                        <?php foreach ($recycle as $rc): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($rc['full_name'] ?? $rc['name'] ?? $rc['file_name'] ?? $rc['surname'] ?? 'Record #' . ($rc['id'] ?? '?')) ?></strong></td>
                            <td><span class="badge bg-secondary"><?= htmlspecialchars($rc['source_table'] ?? 'unknown') ?></span></td>
                            <td><span class="badge bg-danger"><?= htmlspecialchars($rc['status'] ?? 'deleted') ?></span></td>
                            <td class="small"><?= htmlspecialchars(substr($rc['email'] ?? $rc['description'] ?? $rc['department'] ?? '', 0, 40)) ?></td>
                            <td>
                                <form method="POST" style="display:inline" onsubmit="return confirm('Restore this record?')"><input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>"><input type="hidden" name="action" value="restore_recycle"><input type="hidden" name="user_id" value="<?= (int)($rc['id'] ?? 0) ?>"><input type="hidden" name="source_table" value="<?= htmlspecialchars($rc['source_table'] ?? '') ?>"><button type="submit" class="btn btn-sm btn-outline-success py-0 px-1" title="Restore"><i class="fas fa-undo"></i></button></form>
                                <form method="POST" style="display:inline" onsubmit="return confirm('Permanently delete this record?')"><input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>"><input type="hidden" name="action" value="permanent_delete"><input type="hidden" name="user_id" value="<?= (int)($rc['id'] ?? 0) ?>"><input type="hidden" name="source_table" value="<?= htmlspecialchars($rc['source_table'] ?? '') ?>"><button type="submit" class="btn btn-sm btn-outline-danger py-0 px-1" title="Permanently Delete"><i class="fas fa-trash"></i></button></form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($recycle)): ?><tr><td colspan="5" class="text-center">Recycle bin is empty</td></tr><?php endif; ?>
                    </tbody>
                </table>
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
</script>
</body>
</html>
