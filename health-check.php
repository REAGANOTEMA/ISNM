<?php
/**
 * ISNM System Health Check
 * Tests database connectivity, PHP configuration, and critical services.
 */
$checks = [];
$overall = 'healthy';

// 1. PHP Version
$phpOk = version_compare(phpversion(), '7.4.0', '>=');
$checks[] = [
    'name'   => 'PHP Version',
    'status' => $phpOk ? 'ok' : 'fail',
    'detail' => phpversion() . ($phpOk ? ' (OK)' : ' (requires 7.4+)'),
];

// 2. Required extensions
$requiredExt = ['mysqli', 'json', 'mbstring', 'session'];
foreach ($requiredExt as $ext) {
    $loaded = extension_loaded($ext);
    $checks[] = [
        'name'   => "Extension: $ext",
        'status' => $loaded ? 'ok' : 'fail',
        'detail' => $loaded ? 'Loaded' : 'Missing',
    ];
}

// 3. Database connections
require_once __DIR__ . '/config/database.php';
$dbNames = ['staffs' => 'getStaffConnection', 'students' => 'getStudentsConnection', 'website' => 'getWebsiteConnection', 'ict' => 'getICTConnection'];
foreach ($dbNames as $label => $func) {
    $conn = null;
    $error = '';
    try {
        $conn = @$func();
    } catch (\Throwable $e) {
        $error = $e->getMessage();
    }
    if ($conn && !$conn->connect_error) {
        $info = $conn->get_server_info();
        $checks[] = ['name' => "Database: $label", 'status' => 'ok', 'detail' => "Connected ($info)"];
        @$conn->close();
    } else {
        $err = $conn ? $conn->connect_error : ($error ?: 'Connection returned null');
        $checks[] = ['name' => "Database: $label", 'status' => 'fail', 'detail' => $err];
        $overall = 'degraded';
    }
}

// 4. Writable directories
$writableDirs = ['uploads', 'logs', 'adminUploads', 'studentUploads'];
foreach ($writableDirs as $dir) {
    $path = __DIR__ . '/' . $dir;
    $exists = is_dir($path);
    $writable = $exists && is_writable($path);
    $checks[] = [
        'name'   => "Directory: $dir",
        'status' => $writable ? 'ok' : ($exists ? 'warn' : 'fail'),
        'detail' => $writable ? 'Writable' : ($exists ? 'Not writable' : 'Missing'),
    ];
    if (!$writable) $overall = 'degraded';
}

// 5. Session config
$sessionOk = session_status() !== PHP_SESSION_DISABLED;
$checks[] = [
    'name'   => 'PHP Sessions',
    'status' => $sessionOk ? 'ok' : 'fail',
    'detail' => $sessionOk ? 'Available' : 'Disabled',
];

// Determine overall status
foreach ($checks as $c) {
    if ($c['status'] === 'fail') { $overall = 'failing'; break; }
}

$colors = ['ok' => '#16a34a', 'warn' => '#d97706', 'fail' => '#dc2626'];
$icons  = ['ok' => '&#10003;', 'warn' => '&#9888;', 'fail' => '&#10007;'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ISNM Health Check</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f1f5f9; padding: 20px; color: #1e293b; }
        .container { max-width: 700px; margin: 0 auto; }
        h1 { font-size: 24px; margin-bottom: 4px; }
        .subtitle { color: #64748b; margin-bottom: 24px; font-size: 14px; }
        .overall { padding: 16px 20px; border-radius: 10px; margin-bottom: 20px; font-weight: 600; font-size: 16px; }
        .overall.healthy { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
        .overall.degraded { background: #fef3c7; color: #92400e; border: 1px solid #fde68a; }
        .overall.failing { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
        .check { background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px 16px; margin-bottom: 8px; display: flex; align-items: center; gap: 12px; }
        .check-icon { width: 24px; height: 24px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 14px; color: #fff; flex-shrink: 0; }
        .check-name { font-weight: 500; flex: 1; font-size: 14px; }
        .check-detail { font-size: 12px; color: #64748b; max-width: 300px; text-align: right; word-break: break-all; }
        .actions { margin-top: 20px; display: flex; gap: 10px; }
        .btn { display: inline-block; padding: 10px 20px; border-radius: 8px; text-decoration: none; font-weight: 500; font-size: 14px; }
        .btn-primary { background: #1e40af; color: #fff; }
        .btn-secondary { background: #e2e8f0; color: #475569; }
        .timestamp { color: #94a3b8; font-size: 12px; margin-top: 16px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>System Health Check</h1>
        <p class="subtitle">Iganga School of Nursing and Midwifery</p>

        <div class="overall <?= $overall ?>">
            <?php if ($overall === 'healthy'): ?>
                &#10003; All systems operational
            <?php elseif ($overall === 'degraded'): ?>
                &#9888; Some services are degraded
            <?php else: ?>
                &#10007; Critical services are failing
            <?php endif; ?>
        </div>

        <?php foreach ($checks as $c): ?>
        <div class="check">
            <div class="check-icon" style="background:<?= $colors[$c['status']] ?>"><?= $icons[$c['status']] ?></div>
            <div class="check-name"><?= htmlspecialchars($c['name']) ?></div>
            <div class="check-detail"><?= htmlspecialchars($c['detail']) ?></div>
        </div>
        <?php endforeach; ?>

        <div class="actions">
            <a href="javascript:location.reload()" class="btn btn-primary">Re-check</a>
            <a href="javascript:history.back()" class="btn btn-secondary">Go Back</a>
            <a href="staff-login.php" class="btn btn-secondary">Login</a>
        </div>

        <p class="timestamp">Checked at: <?= date('Y-m-d H:i:s') ?></p>
    </div>
</body>
</html>
