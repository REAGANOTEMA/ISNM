<?php
/**
 * System Health monitoring module for the Director General dashboard.
 * Provides: server health, database status, backup info, storage usage,
 * recent errors, and active user counts.
 *
 * Expects: $conn (staffs_db), $studentsConn (students_db) in scope.
 * Other DB connections are created locally via get*Connection() helpers.
 */

if (!isset($conn) || !$conn) {
    $conn = function_exists('DatabaseConnection') ? DatabaseConnection::getStaffConnection() : (function_exists('getStaffConnection') ? getStaffConnection() : null);
}
if (!isset($studentsConn) || !$studentsConn) {
    $studentsConn = function_exists('DatabaseConnection') ? DatabaseConnection::getStudentsConnection() : (function_exists('getStudentsConnection') ? getStudentsConnection() : null);
}

if (!function_exists('formatBytes')) {
    function formatBytes($bytes, $precision = 2) {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        return round($bytes / pow(1024, $pow), $precision) . ' ' . $units[$pow];
    }
}

if (!function_exists('dirSize')) {
    function dirSize($path) {
        $total = 0;
        if (!is_dir($path)) return 0;
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS));
        foreach ($iterator as $file) {
            $total += $file->getSize();
        }
        return $total;
    }
}

// â”€â”€ Gather data â”€â”€

// Server Health
$mysqlVersion = '';
$staffDbConnected = $conn && !$conn->connect_error;
$studentsDbConnected = $studentsConn && !$studentsConn->connect_error;
$phpVersion = phpversion();
if ($staffDbConnected) {
    $r = @$conn->query("SELECT VERSION() AS v");
    if ($r && $row = $r->fetch_assoc()) $mysqlVersion = $row['v'];
}

// Database Status
$websiteConn = null;
$ictConn = null;
try { $websiteConn = function_exists('getWebsiteConnection') ? getWebsiteConnection() : null; } catch (Exception $e) { error_log('dg_health check: ' . $e->getMessage()); }
try { $ictConn = function_exists('getICTConnection') ? getICTConnection() : null; } catch (Exception $e) { error_log('dg_health check: ' . $e->getMessage()); }

$dbStatuses = [
    'staffs_db'   => ['conn' => $staffDbConnected, 'label' => 'Staffs Database', 'icon' => 'fa-database'],
    'students_db' => ['conn' => $studentsDbConnected, 'label' => 'Students Database', 'icon' => 'fa-user-graduate'],
    'website_db'  => ['conn' => $websiteConn && !$websiteConn->connect_error, 'label' => 'Website Database', 'icon' => 'fa-globe'],
    'ict_db'      => ['conn' => $ictConn && !$ictConn->connect_error, 'label' => 'ICT Database', 'icon' => 'fa-microchip'],
];

function getTableStats($conn, $dbName) {
    $result = ['tables' => 0, 'rows' => 0];
    if (!$conn || $conn->connect_error) return $result;
    $stmt = $conn->prepare("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_TYPE = 'BASE TABLE'");
    if (!$stmt) return $result;
    $stmt->bind_param("s", $dbName);
    if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
    $tables = $stmt->get_result();
    if (!$tables) { $stmt->close(); return $result; }
    $result['tables'] = $tables->num_rows;
    while ($t = $tables->fetch_assoc()) {
        $tn = $t['TABLE_NAME'];
        $r = @$conn->query("SELECT COUNT(*) AS cnt FROM `" . preg_replace('/[^a-zA-Z0-9_]/', '', $tn) . "`");
        if ($r) $result['rows'] += (int)$r->fetch_assoc()['cnt'];
    }
    $stmt->close();
    return $result;
}

$staffDbStats = ['tables' => 0, 'rows' => 0];
$studentsDbStats = ['tables' => 0, 'rows' => 0];
$websiteDbStats = ['tables' => 0, 'rows' => 0];
$ictDbStats = ['tables' => 0, 'rows' => 0];

if ($staffDbConnected) {
    $staffDbStats = getTableStats($conn, defined('STAFF_DB_NAME') ? STAFF_DB_NAME : 'igangaschool_staffs');
}
if ($studentsDbConnected) {
    $studentsDbStats = getTableStats($studentsConn, defined('STUDENTS_DB_NAME') ? STUDENTS_DB_NAME : 'igangaschool_students');
}
if ($websiteConn && !$websiteConn->connect_error) {
    $websiteDbStats = getTableStats($websiteConn, defined('WEBSITE_DB_NAME') ? WEBSITE_DB_NAME : 'igangaschool_website');
}
if ($ictConn && !$ictConn->connect_error) {
    $ictDbStats = getTableStats($ictConn, defined('ICT_DB_NAME') ? ICT_DB_NAME : 'igangaschool_ict');
}

// Backup Status
$backupRecords = 0;
$lastBackup = null;
if ($staffDbConnected) {
    // Check backup_management first, then system_logs
    $chk = @$conn->query("SHOW TABLES LIKE 'backup_management'");
    if ($chk && $chk->num_rows > 0) {
        $r = @$conn->query("SELECT COUNT(*) AS cnt FROM backup_management");
        if ($r) $backupRecords = (int)$r->fetch_assoc()['cnt'];
        $r2 = @$conn->query("SELECT MAX(created_at) AS last_backup FROM backup_management");
        if ($r2 && $row = $r2->fetch_assoc()) $lastBackup = $row['last_backup'];
    } else {
        $chk2 = @$conn->query("SHOW TABLES LIKE 'system_logs'");
        if ($chk2 && $chk2->num_rows > 0) {
            $r = @$conn->query("SELECT COUNT(*) AS cnt, MAX(created_at) AS last_backup FROM system_logs WHERE log_type = 'backup'");
            if ($r && $row = $r->fetch_assoc()) {
                $backupRecords = (int)$row['cnt'];
                $lastBackup = $row['last_backup'];
            }
        }
    }
}

// Storage / Upload sizes
$uploadDirs = [
    'Uploads'        => __DIR__ . '/../uploads/',
    'News Uploads'   => __DIR__ . '/../newsUploads/',
    'Student Uploads' => __DIR__ . '/../studentUploads/',
];
$storageData = [];
foreach ($uploadDirs as $label => $path) {
    $size = is_dir($path) ? dirSize($path) : 0;
    $storageData[] = ['label' => $label, 'path' => $path, 'size' => $size, 'formatted' => formatBytes($size)];
}

// Recent Errors
$recentErrors = [];
if ($staffDbConnected) {
    $chk = @$conn->query("SHOW TABLES LIKE 'error_logs'");
    if ($chk && $chk->num_rows > 0) {
        $r = @$conn->query("SELECT id, error_message, created_at, error_level FROM error_logs ORDER BY created_at DESC LIMIT 10");
        if ($r) while ($row = $r->fetch_assoc()) $recentErrors[] = $row;
    }
}

// Active Users
$activeUsers24h = 0;
$totalUsers = 0;
$totalStaff = 0;
if ($staffDbConnected) {
    $chk = @$conn->query("SHOW TABLES LIKE 'staff_activity_log'");
    if ($chk && $chk->num_rows > 0) {
        $r = @$conn->query("SELECT COUNT(DISTINCT staff_id) AS cnt FROM staff_activity_log WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)");
        if ($r) $activeUsers24h = (int)$r->fetch_assoc()['cnt'];
    }
    $chk2 = @$conn->query("SHOW TABLES LIKE 'users'");
    if ($chk2 && $chk2->num_rows > 0) {
        $r = @$conn->query("SELECT COUNT(*) AS cnt FROM users");
        if ($r) $totalUsers = (int)$r->fetch_assoc()['cnt'];
    }
    $r = @$conn->query("SELECT COUNT(*) AS cnt FROM staff");
    if ($r) $totalStaff = (int)$r->fetch_assoc()['cnt'];
}
?>
<style>
.sh-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
@media (max-width: 992px) { .sh-grid { grid-template-columns: 1fr; } }
.sh-status-dot { width: 10px; height: 10px; border-radius: 50%; display: inline-block; margin-right: 6px; }
.sh-status-dot.good { background: #10b981; box-shadow: 0 0 6px rgba(16,185,129,0.4); }
.sh-status-dot.bad  { background: #ef4444; box-shadow: 0 0 6px rgba(239,68,68,0.4); }
.sh-info-row { display: flex; justify-content: space-between; align-items: center; padding: 5px 0; border-bottom: 1px solid #f1f5f9; font-size: 12px; }
.sh-info-row:last-child { border-bottom: none; }
.sh-info-label { color: #64748b; font-weight: 500; display: flex; align-items: center; gap: 6px; }
.sh-info-value { font-weight: 600; color: #0f172a; }
.sh-table { font-size: 12px; width: 100%; }
.sh-table th { background: #f8fafc; font-weight: 600; color: #64748b; text-transform: uppercase; font-size: 10px; letter-spacing: 0.4px; padding: 6px 10px; border-bottom: 2px solid #e2e8f0; text-align: left; }
.sh-table td { padding: 6px 10px; border-bottom: 1px solid #f1f5f9; }
.sh-table tbody tr:hover { background: #f8fafc; }
.sh-error-entry { padding: 6px 0; border-bottom: 1px solid #f1f5f9; font-size: 12px; }
.sh-error-entry:last-child { border-bottom: none; }
.sh-error-time { color: #94a3b8; font-size: 10px; white-space: nowrap; }
.sh-error-msg { color: #dc2626; font-weight: 500; }
.sh-bar { height: 6px; border-radius: 3px; background: #e2e8f0; margin-top: 4px; overflow: hidden; }
.sh-bar-fill { height: 100%; border-radius: 3px; transition: width 0.5s ease; }
</style>

<div class="sh-grid">

  <!-- â•â•â• Server Health Card â•â•â• -->
  <div class="section-card">
    <div class="section-header">
      <div>
        <h3 class="section-title"><i class="fas fa-server" style="color:#3b82f6;"></i>Server Health</h3>
        <p class="section-subtitle">Database &amp; application server status</p>
      </div>
      <span class="badge badge-soft <?= $staffDbConnected && $studentsDbConnected ? 'bg-success' : 'bg-danger' ?>" style="font-size:10px;">
        <i class="fas fa-circle me-1" style="font-size:8px;"></i><?= ($staffDbConnected && $studentsDbConnected) ? 'All Connected' : 'Issues Detected' ?>
      </span>
    </div>
    <div>
      <div class="sh-info-row">
        <span class="sh-info-label"><i class="fas fa-tag" style="color:#3b82f6;"></i>MySQL Version</span>
        <span class="sh-info-value"><?= $mysqlVersion ? htmlspecialchars($mysqlVersion) : '<span style="color:#ef4444;">Unavailable</span>' ?></span>
      </div>
      <div class="sh-info-row">
        <span class="sh-info-label"><i class="fab fa-php" style="color:#787cb5;"></i>PHP Version</span>
        <span class="sh-info-value"><?= htmlspecialchars($phpVersion) ?></span>
      </div>
      <?php foreach ($dbStatuses as $key => $db): $good = $db['conn']; ?>
      <div class="sh-info-row">
        <span class="sh-info-label"><i class="fas <?= $db['icon'] ?>" style="color:<?= $good ? '#10b981' : '#ef4444' ?>;"></i><?= htmlspecialchars($db['label']) ?></span>
        <span class="sh-info-value">
          <span class="sh-status-dot <?= $good ? 'good' : 'bad' ?>"></span>
          <?= $good ? 'Connected' : 'Disconnected' ?>
        </span>
      </div>
      <?php endforeach; ?>
      <div class="sh-info-row">
        <span class="sh-info-label"><i class="fas fa-clock" style="color:#059669;"></i>Uptime</span>
        <span class="sh-info-value" style="color:#059669;"><i class="fas fa-check-circle me-1"></i>Connected &amp; Running</span>
      </div>
    </div>
  </div>

  <!-- â•â•â• Database Status Card â•â•â• -->
  <div class="section-card">
    <div class="section-header">
      <div>
        <h3 class="section-title"><i class="fas fa-layer-group" style="color:#0891b2;"></i>Database Status</h3>
        <p class="section-subtitle">Tables &amp; total rows per database</p>
      </div>
    </div>
    <div>
      <?php
      $dbs = [
        ['label' => STAFF_DB_NAME, 'stats' => $staffDbStats, 'color' => '#3b82f6'],
        ['label' => STUDENTS_DB_NAME, 'stats' => $studentsDbStats, 'color' => '#10b981'],
        ['label' => WEBSITE_DB_NAME, 'stats' => $websiteDbStats, 'color' => '#f59e0b'],
        ['label' => ICT_DB_NAME, 'stats' => $ictDbStats, 'color' => '#8b5cf6'],
      ];
      $maxRows = 1;
      foreach ($dbs as $db) { if ($db['stats']['rows'] > $maxRows) $maxRows = $db['stats']['rows']; }
      foreach ($dbs as $db):
          $pct = $maxRows > 0 ? round(($db['stats']['rows'] / $maxRows) * 100) : 0;
      ?>
      <div class="sh-info-row">
        <span class="sh-info-label"><i class="fas fa-database" style="color:<?= $db['color'] ?>;"></i>
          <span style="font-size:11px;max-width:220px;display:inline-block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= htmlspecialchars($db['label']) ?></span>
        </span>
        <span class="sh-info-value" style="font-size:11px;white-space:nowrap;">
          <?= $db['stats']['tables'] ?> tables &middot; <?= number_format($db['stats']['rows']) ?> rows
        </span>
      </div>
      <div class="sh-bar"><div class="sh-bar-fill" style="width:<?= $pct ?>%;background:<?= $db['color'] ?>;"></div></div>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- â•â•â• Backup Status Card â•â•â• -->
  <div class="section-card">
    <div class="section-header">
      <div>
        <h3 class="section-title"><i class="fas fa-archive" style="color:#d97706;"></i>Backup Status</h3>
        <p class="section-subtitle">Database backup records</p>
      </div>
      <?php if ($backupRecords > 0): ?>
      <span class="badge badge-soft bg-success" style="font-size:10px;"><i class="fas fa-check me-1"></i><?= $backupRecords ?> backup(s)</span>
      <?php endif; ?>
    </div>
    <div style="text-align:center;padding:10px 0;">
      <?php if ($backupRecords > 0 && $lastBackup): ?>
      <div style="font-size:28px;font-weight:800;color:#0f172a;line-height:1.2;"><?= date('d M Y', strtotime($lastBackup)) ?></div>
      <div style="font-size:12px;color:#64748b;">Last backup</div>
      <div style="font-size:11px;color:#94a3b8;margin-top:4px;"><i class="far fa-clock me-1"></i><?= date('h:i A', strtotime($lastBackup)) ?></div>
      <div class="mt-2" style="font-size:12px;color:#059669;"><i class="fas fa-database me-1"></i><?= $backupRecords ?> total backup record(s)</div>
      <?php else: ?>
      <div style="font-size:40px;color:#e2e8f0;margin-bottom:8px;"><i class="fas fa-exclamation-triangle"></i></div>
      <div style="font-size:14px;font-weight:600;color:#64748b;">No backup records found</div>
      <div style="font-size:11px;color:#94a3b8;margin-top:2px;">Configure automated backups for data protection</div>
      <?php endif; ?>
    </div>
  </div>

  <!-- â•â•â• Storage Card â•â•â• -->
  <div class="section-card">
    <div class="section-header">
      <div>
        <h3 class="section-title"><i class="fas fa-hdd" style="color:#059669;"></i>Storage</h3>
        <p class="section-subtitle">Upload directory sizes</p>
      </div>
    </div>
    <div>
      <?php
      $maxStorage = 1;
      foreach ($storageData as $s) { if ($s['size'] > $maxStorage) $maxStorage = $s['size']; }
      foreach ($storageData as $s):
          $pct = $maxStorage > 0 ? round(($s['size'] / $maxStorage) * 100) : 0;
      ?>
      <div class="sh-info-row">
        <span class="sh-info-label"><i class="fas fa-folder-open" style="color:#3b82f6;"></i><?= htmlspecialchars($s['label']) ?></span>
        <span class="sh-info-value"><?= $s['formatted'] ?></span>
      </div>
      <div class="sh-bar"><div class="sh-bar-fill" style="width:<?= $pct ?>%;background:<?= $s['size'] > 104857600 ? '#ef4444' : ($s['size'] > 10485760 ? '#f59e0b' : '#10b981') ?>;"></div></div>
      <?php endforeach; ?>
      <div class="mt-2" style="font-size:11px;color:#64748b;text-align:right;">
        <i class="fas fa-info-circle me-1"></i>
        Total: <?= formatBytes(array_sum(array_column($storageData, 'size'))) ?>
      </div>
    </div>
  </div>

  <!-- â•â•â• Recent Errors Card â•â•â• -->
  <div class="section-card">
    <div class="section-header">
      <div>
        <h3 class="section-title"><i class="fas fa-exclamation-triangle" style="color:#ef4444;"></i>Recent Errors</h3>
        <p class="section-subtitle">Last 10 logged errors</p>
      </div>
      <?php if (!empty($recentErrors)): ?>
      <span class="badge badge-soft bg-danger" style="font-size:10px;"><?= count($recentErrors) ?> recent</span>
      <?php endif; ?>
    </div>
    <div style="max-height:240px;overflow-y:auto;">
      <?php if (empty($recentErrors)): ?>
      <div style="text-align:center;padding:20px 0;">
        <div style="font-size:36px;color:#d1fae5;margin-bottom:6px;"><i class="fas fa-check-circle" style="color:#10b981;"></i></div>
        <div style="font-weight:600;color:#059669;">No errors logged</div>
        <div style="font-size:11px;color:#94a3b8;margin-top:2px;">System is running smoothly</div>
      </div>
      <?php else: ?>
      <?php foreach ($recentErrors as $e): ?>
      <div class="sh-error-entry">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:8px;">
          <div class="sh-error-msg" style="flex:1;word-break:break-word;"><?= htmlspecialchars(mb_substr($e['error_message'] ?? $e['message'] ?? '', 0, 120)) ?></div>
          <div class="sh-error-time" style="flex-shrink:0;"><?= date('d M H:i', strtotime($e['created_at'])) ?></div>
        </div>
        <?php if (isset($e['error_level'])): ?>
        <div><span class="badge badge-soft bg-danger" style="font-size:9px;"><?= htmlspecialchars($e['error_level']) ?></span></div>
        <?php endif; ?>
      </div>
      <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>

  <!-- â•â•â• Active Users Card â•â•â• -->
  <div class="section-card">
    <div class="section-header">
      <div>
        <h3 class="section-title"><i class="fas fa-users" style="color:#8b5cf6;"></i>Active Users</h3>
        <p class="section-subtitle">System usage &amp; registered accounts</p>
      </div>
    </div>
    <div class="row g-2">
      <div class="col-4">
        <div class="stat-block" style="background:linear-gradient(135deg,#f5f3ff,#ede9fe);">
          <div class="stat-val" style="color:#6b21a8;"><?= number_format($activeUsers24h) ?></div>
          <div class="stat-lbl" style="color:#581c87;">Active (24h)</div>
        </div>
      </div>
      <div class="col-4">
        <div class="stat-block" style="background:linear-gradient(135deg,#eff6ff,#dbeafe);">
          <div class="stat-val" style="color:#1e40af;"><?= number_format($totalUsers) ?></div>
          <div class="stat-lbl" style="color:#1e3a8a;">Total Users</div>
        </div>
      </div>
      <div class="col-4">
        <div class="stat-block" style="background:linear-gradient(135deg,#f0fdf4,#dcfce7);">
          <div class="stat-val" style="color:#166534;"><?= number_format($totalStaff) ?></div>
          <div class="stat-lbl" style="color:#14532d;">Registered Staff</div>
        </div>
      </div>
    </div>
    <div class="mt-2" style="font-size:11px;color:#64748b;">
      <i class="fas fa-info-circle me-1"></i>
      Active users = distinct staff with activity in the last 24 hours
    </div>
  </div>

</div>
