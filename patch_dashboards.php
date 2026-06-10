<?php
set_time_limit(120);
$dir = __DIR__ . '/dashboards/';
$files = glob($dir . '*.php');
$patched = 0;

foreach ($files as $file) {
    $content = file_get_contents($file);
    $changed = false;

    // 1. Fix/add viewport meta
    if (strpos($content, 'name="viewport"') === false) {
        $content = str_replace('<head>', '<head>' . "\n" . '  <meta charset="UTF-8">' . "\n" . '  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">', $content);
        $changed = true;
    } elseif (strpos($content, 'maximum-scale') === false) {
        $content = preg_replace(
            '/(<meta\s+name="viewport"\s+content="[^"]*?)(")/i',
            '$1, maximum-scale=5.0$2',
            $content
        );
        $changed = true;
    }

    // 2. Inject favicon include after <head> if not present
    if (strpos($content, '_favicon.php') === false && strpos($content, 'dashboard_head.php') === false) {
        $content = preg_replace(
            '/(<head[^>]*>)/i',
            '$1' . "\n" . "<?php include_once __DIR__ . '/../includes/_favicon.php'; ?>",
            $content, 1
        );
        $changed = true;
    }

    // 3. Add dashboard-mobile.css if not present
    if (strpos($content, 'dashboard-mobile.css') === false) {
        $content = str_replace(
            'dashboard-style.css" rel="stylesheet">',
            'dashboard-style.css" rel="stylesheet">' . "\n" . '    <link href="../dashboards/dashboard-mobile.css" rel="stylesheet">',
            $content
        );
        // fallback if using href with relative path variations
        if (strpos($content, 'dashboard-mobile.css') === false) {
            $content = preg_replace(
                '/(<\/head>)/i',
                '    <link href="../dashboards/dashboard-mobile.css" rel="stylesheet">' . "\n" . '$1',
                $content, 1
            );
        }
        $changed = true;
    }

    // 4. Replace broken relative sw.js registration
    $content = preg_replace(
        "/navigator\.serviceWorker\.register\(['\"](?!\/ISNM).*?sw\.js['\"][^)]*\)/",
        "navigator.serviceWorker.register('/ISNM/sw.js', { scope: '/ISNM/' })",
        $content
    );

    // 5. Inject dashboard_footer.php before </body> if no SW registration present
    if (strpos($content, 'dashboard_footer.php') === false && strpos($content, "register('/ISNM/sw.js'") === false) {
        $content = str_replace(
            '</body>',
            "<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>\n</body>",
            $content
        );
        $changed = true;
    }

    if ($changed) {
        file_put_contents($file, $content);
        $patched++;
        echo "Patched: " . basename($file) . "\n";
    } else {
        echo "OK (no change): " . basename($file) . "\n";
    }
}

echo "\nDone. Patched $patched / " . count($files) . " dashboards.\n";
unlink(__FILE__);
