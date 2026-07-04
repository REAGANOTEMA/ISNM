<?php
/**
 * ISNM System Health Check
 * Verifies database connections, PHP version, required extensions, file permissions.
 * Run from browser or CLI: php health-check.php
 */
$isCLI = php_sapi_name() === 'cli';
$results = ['pass' => 0, 'fail' => 0, 'warn' => 0];

function check($label, $ok, $msg = '') {
    global $results, $isCLI;
    $status = $ok ? 'PASS' : 'FAIL';
    if (!$ok && $msg) { $results['fail']++; }
    elseif ($ok) { $results['pass']++; }
    if ($isCLI) { echo "  [$status] $label" . ($msg ? " — $msg" : '') . "\n"; }
    return $ok;
}

if (!$isCLI): ?><!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>ISNM Health Check</title><style>body{font-family:sans-serif;background:#f8fafc;padding:30px}.card{background:#fff;border-radius:12px;padding:24px;max-width:700px;margin:0 auto;box-shadow:0 2px 8px rgba(0,0,0,.08)}h1{font-size:20px;margin:0 0 20px}.pass{color:#059669}.fail{color:#dc2626}.warn{color:#d97706}.item{padding:8px 0;border-bottom:1px solid #f1f5f9;font-size:13px}.summary{margin-top:16px;font-weight:600}</style></head><body><div class="card"><h1>ISNM System Health Check</h1><div><?php endif;

// PHP Version
check('PHP Version', PHP_VERSION_ID >= 80000, PHP_VERSION) || print '<div class="item fail">✗ PHP 8.0+ required (running ' . PHP_VERSION . ')</div>';
if (PHP_VERSION_ID >= 80000) print '<div class="item pass">✓ PHP ' . PHP_VERSION . ' (8.0+ OK)</div>';

// Required Extensions
foreach (['mysqli', 'pdo_mysql', 'json', 'session', 'mbstring', 'ctype', 'filter', 'hash', 'openssl'] as $ext) {
    $ok = extension_loaded($ext);
    check("Extension: $ext", $ok) || print '<div class="item fail">✗ Missing extension: ' . $ext . '</div>';
    if ($ok) print '<div class="item pass">✓ Extension: ' . $ext . '</div>';
}

// Files exist
$files = [
    '.env' => 'Environment config',
    'config/database.php' => 'Database config',
    'auth-service.php' => 'Auth service',
    'includes/staff_dashboard_access.php' => 'Dashboard access',
    'dashboards/hr-manager.php' => 'HR dashboard',
    'dashboards/school-bursar.php' => 'Bursar dashboard',
    'staff-portal.php' => 'Staff portal',
];
foreach ($files as $path => $label) {
    $full = __DIR__ . '/' . $path;
    $ok = file_exists($full);
    check("File: $label", $ok) || print '<div class="item fail">✗ Missing: ' . $path . '</div>';
    if ($ok) print '<div class="item pass">✓ ' . $label . '</div>';
}

// Database connections
try {
    require_once __DIR__ . '/config/database.php';

    foreach ([
        'Students' => 'getStudentsConnection',
        'Staff' => 'getStaffConnection',
        'Website' => 'getWebsiteConnection',
        'ICT' => 'getICTConnection',
    ] as $label => $func) {
        $conn = function_exists($func) ? $func() : null;
        if ($conn && !$conn->connect_error) {
            $dbName = $conn->query("SELECT DATABASE() as db")->fetch_assoc()['db'] ?? '?';
            check("DB: $label", true, "Connected to $dbName") || print '<div class="item pass">✓ DB ' . $label . ': Connected (' . $dbName . ')</div>';
            $conn->close();
        } else {
            check("DB: $label", false, $conn ? $conn->connect_error : 'Function not found') || print '<div class="item fail">✗ DB ' . $label . ': Connection failed</div>';
        }
    }
} catch (Throwable $e) {
    check('Database bootstrap', false, $e->getMessage()) || print '<div class="item fail">✗ Database bootstrap: ' . htmlspecialchars($e->getMessage()) . '</div>';
}

// Writable directories
foreach (['uploads', 'logs', 'assets'] as $dir) {
    $full = __DIR__ . '/' . $dir;
    if (is_dir($full)) {
        $ok = is_writable($full);
        check("Writable: $dir", $ok) || print '<div class="item warn">⚠ Directory not writable: ' . $dir . '</div>';
        if ($ok) print '<div class="item pass">✓ Writable: ' . $dir . '</div>';
    } else {
        print '<div class="item warn">⚠ Directory missing: ' . $dir . '</div>';
    }
}

$total = $results['pass'] + $results['fail'];
if (!$isCLI): ?>
<div class="summary"><?=$results['pass']?>/<?=$total?> passed, <?=$results['fail']?> failed, <?=$results['warn']?> warnings</div>
<?php if ($results['fail'] > 0): ?><p style="color:#dc2626">Fix failing checks before deploying to production.</p>
<?php else: ?><p style="color:#059669">All checks passed. System is ready.</p><?php endif; ?>
</div></body></html>
<?php else: echo "\n{$results['pass']}/$total passed, {$results['fail']} failed, {$results['warn']} warnings\n"; endif; ?>