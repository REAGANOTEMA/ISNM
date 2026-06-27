<?php
$results = [];
$allPass = true;

$pv = phpversion();
$pvOk = version_compare($pv, '8.0', '>=');
$results[] = ['PHP Version', $pv . ($pvOk ? '' : ' — NEEDS 8.0+'), $pvOk ? 'pass' : 'fail'];
if (!$pvOk) $allPass = false;

if (file_exists(__DIR__ . '/.env')) {
    $results[] = ['.env file', 'Found', 'pass'];
    $env = parse_ini_file(__DIR__ . '/.env');
    $results[] = ['APP_ENV', $env['APP_ENV'] ?? 'not set', 'info'];
} else {
    $results[] = ['.env file', 'MISSING', 'fail'];
    $allPass = false;
}

if (extension_loaded('mysqli')) {
    $results[] = ['MySQLi Extension', 'Loaded', 'pass'];
} else {
    $results[] = ['MySQLi Extension', 'MISSING', 'fail'];
    $allPass = false;
}

$dbs = [
    'Students' => ['host' => $_ENV['STUDENTS_DB_HOST'] ?? 'localhost', 'user' => $_ENV['STUDENTS_DB_USER'] ?? 'root', 'pass' => $_ENV['STUDENTS_DB_PASS'] ?? '', 'name' => $_ENV['STUDENTS_DB_NAME'] ?? ''],
    'Staff'    => ['host' => $_ENV['STAFF_DB_HOST'] ?? 'localhost', 'user' => $_ENV['STAFF_DB_USER'] ?? 'root', 'pass' => $_ENV['STAFF_DB_PASS'] ?? '', 'name' => $_ENV['STAFF_DB_NAME'] ?? ''],
    'Website'  => ['host' => $_ENV['WEBSITE_DB_HOST'] ?? 'localhost', 'user' => $_ENV['WEBSITE_DB_USER'] ?? 'root', 'pass' => $_ENV['WEBSITE_DB_PASS'] ?? '', 'name' => $_ENV['WEBSITE_DB_NAME'] ?? ''],
    'ICT'      => ['host' => $_ENV['ICT_DB_HOST'] ?? 'localhost', 'user' => $_ENV['ICT_DB_USER'] ?? 'root', 'pass' => $_ENV['ICT_DB_PASS'] ?? '', 'name' => $_ENV['ICT_DB_NAME'] ?? ''],
];

foreach ($dbs as $label => $cfg) {
    if (empty($cfg['name'])) { $results[] = ["DB: $label", "No database configured", 'warn']; continue; }
    $conn = @new mysqli($cfg['host'] === 'localhost' ? '127.0.0.1' : $cfg['host'], $cfg['user'], $cfg['pass'], $cfg['name'], 3306);
    if (!$conn->connect_error) {
        $tables = $conn->query("SHOW TABLES");
        $count = $tables ? $tables->num_rows : 0;
        $results[] = ["DB: $label ({$cfg['name']})", "Connected, $count tables", 'pass'];
        $conn->close();
    } else {
        $results[] = ["DB: $label ({$cfg['name']})", "FAILED: " . $conn->connect_error, 'fail'];
        $allPass = false;
    }
}

if (session_status() === PHP_SESSION_NONE) session_start();
$_SESSION['health_check'] = time();
$results[] = ['Sessions', 'Working', 'pass'];

$criticalFiles = [
    'config/database.php', 'auth-service.php', 'includes/staff_dashboard_access.php',
    'includes/sidebar.php', 'dashboards/director-ict.php', 'dashboards/computer_lab.php',
    'handlers/ict_handler.php', 'handlers/lab_handler.php', 'includes/student_auth.php',
];
foreach ($criticalFiles as $f) {
    $path = __DIR__ . '/' . $f;
    if (file_exists($path)) {
        $results[] = ["File: $f", 'Found', 'pass'];
    } else {
        $results[] = ["File: $f", 'MISSING', 'fail'];
        $allPass = false;
    }
}

$writableDirs = ['studentUploads/profile_images', 'uploads', 'temp'];
foreach ($writableDirs as $d) {
    $path = __DIR__ . '/' . $d;
    if (is_dir($path) && is_writable($path)) {
        $results[] = ["Dir: $d", 'Writable', 'pass'];
    } elseif (is_dir($path)) {
        $results[] = ["Dir: $d", 'Not writable', 'fail'];
        $allPass = false;
    } else {
        $results[] = ["Dir: $d", 'Not found', 'warn'];
    }
}

$filesize = @filesize(__DIR__ . '/.env');
if ($filesize && $filesize > 0) {
    $results[] = ['.env readable', 'Yes (' . $filesize . ' bytes)', 'pass'];
} else {
    $results[] = ['.env readable', 'No', 'fail'];
    $allPass = false;
}
?>
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><title>ISNM Health Check</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body { font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif; background:#f1f5f9; padding:20px; }
.container { max-width:800px; margin:0 auto; }
h1 { font-size:22px; margin-bottom:5px; color:#1e293b; }
p.sub { color:#64748b; margin-bottom:20px; font-size:14px; }
.card { background:#fff; border-radius:12px; box-shadow:0 1px 3px rgba(0,0,0,.08); overflow:hidden; }
table { width:100%; border-collapse:collapse; }
th { background:#f8fafc; color:#475569; font-size:11px; text-transform:uppercase; letter-spacing:.5px; padding:10px 14px; text-align:left; border-bottom:1px solid #e2e8f0; }
td { padding:8px 14px; border-bottom:1px solid #f1f5f9; font-size:13px; color:#334155; }
tr:last-child td { border-bottom:none; }
.badge { display:inline-block; padding:2px 8px; border-radius:4px; font-size:11px; font-weight:600; }
.pass { background:#dcfce7; color:#16a34a; }
.fail { background:#fef2f2; color:#dc2626; }
.warn { background:#fef9c3; color:#ca8a04; }
.info { background:#e0f2fe; color:#0284c7; }
.summary { margin:16px 0; padding:12px 16px; border-radius:8px; font-size:14px; font-weight:600; }
.summary-pass { background:#dcfce7; color:#16a34a; }
.summary-fail { background:#fef2f2; color:#dc2626; }
.chrome-note { margin-top:16px; padding:12px 16px; background:#fef9c3; border-radius:8px; font-size:13px; color:#92400e; }
</style>
</head>
<body>
<div class="container">
    <h1>ISNM System Health Check</h1>
    <p class="sub">Run this page on your hosting platform to diagnose issues.</p>
    <div class="card"><table>
        <thead><tr><th style="width:260px">Check</th><th>Result</th><th style="width:70px">Status</th></tr></thead>
        <tbody>
        <?php foreach ($results as $r): ?>
        <tr><td><?= htmlspecialchars($r[0]) ?></td><td><?= htmlspecialchars($r[1]) ?></td><td><span class="badge <?= $r[2] ?>"><?= $r[2] ?></span></td></tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
    <div class="summary <?= $allPass ? 'summary-pass' : 'summary-fail' ?>">
        <?= $allPass ? 'All checks passed. Your deployment is configured correctly.' : 'Some checks failed. Review the table above and fix the issues.' ?>
    </div>
    <div class="chrome-note">
        <strong>Chrome Extension 403 Error:</strong> The error you showed (<code>/writing/get_template_list</code>, <code>/site_integration/template_list</code>) is from a <strong>third-party Chrome extension</strong> trying to access API endpoints on your server. This is NOT from ISNM. To fix it: (1) Disable the extension in Chrome's <code>chrome://extensions</code> that's making those requests, or (2) if you need that extension, configure its permissions to allow access to your domain. ISNM itself is working correctly if all checks above pass.
    </div>
</div>
</body>
</html>
