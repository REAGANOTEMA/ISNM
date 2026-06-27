<?php
/**
 * Universal Dashboard Head — include at the very top of every dashboard <head>
 * Usage: include_once __DIR__ . '/../includes/dashboard_head.php';
 * Set $pageTitle before including, e.g.: $pageTitle = 'Director General';
 */
$pageTitle = $pageTitle ?? 'Dashboard';
$rootPath  = rtrim(str_repeat('../', substr_count($_SERVER['PHP_SELF'], '/') - 2), '/');
if ($rootPath === '') $rootPath = '.';

// Cache-busting version — bump on every deploy
$v = '2.1.0';

// Profile image URL for dashboard header avatars (falls back to username.png)
$profileImageUrl = $rootPath . '/images/username.png';
$userType = $_SESSION['type'] ?? '';
if (!empty($_SESSION['user_id'])) {
    if ($userType === 'student') {
        try {
            $studentConn = getStudentsConnection();
            if ($studentConn) {
                $q = $studentConn->prepare("SELECT profile_picture, passport_photo FROM students WHERE id = ?");
                $q->bind_param('i', (int)$_SESSION['user_id']);
                $q->execute();
                $photoRow = $q->get_result()->fetch_assoc();
                $q->close();
                if ($photoRow) {
                    $photoFile = '';
                    if (!empty($photoRow['profile_picture'])) $photoFile = $photoRow['profile_picture'];
                    elseif (!empty($photoRow['passport_photo'])) $photoFile = $photoRow['passport_photo'];
                    if ($photoFile) {
                        $checkPath = __DIR__ . '/../studentUploads/profile_images/' . $photoFile;
                        if (file_exists($checkPath)) {
                            $profileImageUrl = $rootPath . '/studentUploads/profile_images/' . $photoFile . '?v=' . time();
                        }
                    }
                }
            }
        } catch (Exception $e) {}
    } else {
        $pf = __DIR__ . '/profile_settings.php';
        if (file_exists($pf)) {
            include_once $pf;
            if (function_exists('getStaffProfileImageUrl')) {
                $url = getStaffProfileImageUrl((int)$_SESSION['user_id']);
                if ($url) $profileImageUrl = $url;
            }
        }
    }
}
?>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="ISNM">
<meta name="theme-color" content="#1a237e">
<meta name="msapplication-TileColor" content="#1a237e">
<meta name="msapplication-TileImage" content="<?= $rootPath ?>/images/school-logo.png">
<meta name="application-name" content="ISNM">
<meta name="description" content="Iganga School of Nursing &amp; Midwifery , School Management System">
<meta name="keywords" content="ISNM, Iganga, Nursing, Midwifery, School, Management, ERP">
<meta name="author" content="ISNM">
<meta name="robots" content="noindex, nofollow">
<meta name="csrf-token" content="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
<title><?= htmlspecialchars($pageTitle) ?> | ISNM</title>
<!-- jQuery 3.6 — MUST load before any $ usage -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
window.CSRF_TOKEN = '<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>';
// Auto-include CSRF token in all jQuery AJAX POST requests
$(document).ajaxSend(function(e, xhr, opts) {
    if (opts.type === 'POST' && window.CSRF_TOKEN) {
        if (typeof opts.data === 'string' && opts.data.indexOf('csrf_token=') === -1) {
            opts.data += (opts.data ? '&' : '') + 'csrf_token=' + encodeURIComponent(window.CSRF_TOKEN);
        } else if (typeof opts.data === 'object' && opts.data && !(opts.data instanceof FormData)) {
            opts.data.csrf_token = window.CSRF_TOKEN;
        }
    }
});
(function(){
  window.addEventListener('unhandledrejection', function(e){ e.preventDefault(); });
})();
</script>

<!-- Favicon — all sizes, all devices -->
<link rel="icon"                  type="image/png" href="<?= $rootPath ?>/images/school-logo.png?v=<?= $v ?>">
<link rel="shortcut icon"         type="image/png" href="<?= $rootPath ?>/images/school-logo.png?v=<?= $v ?>">
<link rel="apple-touch-icon"                       href="<?= $rootPath ?>/images/school-logo.png?v=<?= $v ?>">
<link rel="apple-touch-icon" sizes="57x57"         href="<?= $rootPath ?>/images/school-logo.png?v=<?= $v ?>">
<link rel="apple-touch-icon" sizes="72x72"         href="<?= $rootPath ?>/images/school-logo.png?v=<?= $v ?>">
<link rel="apple-touch-icon" sizes="114x114"       href="<?= $rootPath ?>/images/school-logo.png?v=<?= $v ?>">
<link rel="apple-touch-icon" sizes="144x144"       href="<?= $rootPath ?>/images/school-logo.png?v=<?= $v ?>">
<link rel="apple-touch-icon" sizes="152x152"       href="<?= $rootPath ?>/images/school-logo.png?v=<?= $v ?>">
<link rel="apple-touch-icon" sizes="180x180"       href="<?= $rootPath ?>/images/school-logo.png?v=<?= $v ?>">
<link rel="manifest"                               href="<?= $rootPath ?>/manifest.json?v=<?= $v ?>">

<!-- Bootstrap 5.3 -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css?v=<?= $v ?>" rel="stylesheet">
<!-- Font Awesome 6 -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css?v=<?= $v ?>" rel="stylesheet">
<!-- Google Fonts -->
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<!-- Dashboard Base — normalized foundation (resolves cross-file conflicts) -->
<link href="<?= $rootPath ?>/dashboards/dashboard-base.css?v=<?= $v ?>" rel="stylesheet">
<!-- Dashboard Layout -->
<link href="<?= $rootPath ?>/dashboards/dashboard-style.css?v=<?= $v ?>" rel="stylesheet">
<!-- Dashboard Professional Design System (cards, tables, badges, KPI) -->
<link href="<?= $rootPath ?>/dashboards/dashboard-professional.css?v=<?= $v ?>" rel="stylesheet">
<!-- Mobile Dashboard Styles -->
<link href="<?= $rootPath ?>/dashboards/dashboard-mobile.css?v=<?= $v ?>" rel="stylesheet">
<!-- Modern UI Enhancement Styles -->
<link href="<?= $rootPath ?>/css/modern-ui.css?v=<?= $v ?>" rel="stylesheet">
<!-- Dashboard Theme System -->
<script src="<?= $rootPath ?>/dashboards/dashboard-theme.js?v=<?= $v ?>" defer></script>
<!-- Chart.js 4.x for dashboard analytics -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<!-- Dashboard Analytics Engine (Chart.js + AI) -->
<script src="<?= $rootPath ?>/dashboards/dashboard-charts.js?v=<?= $v ?>" defer></script>
<!-- Service Worker + Push Notification Registration -->
<script>
if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register('<?= $rootPath ?>/sw.js?v=<?= $v ?>', { scope: '/ISNM/' })
        .then(function(reg) {
            if ('PushManager' in window && 'Notification' in window && Notification.permission === 'granted') {
                reg.pushManager.subscribe({ userVisibleOnly: true }).then(function(sub) {
                    if (sub) {
                        var data = new URLSearchParams();
                        data.append('endpoint', sub.endpoint);
                        data.append('auth_key', (sub.toJSON().keys && sub.toJSON().keys.auth) || '');
                        data.append('p256dh_key', (sub.toJSON().keys && sub.toJSON().keys.p256dh) || '');
                        data.append('device_type', /Mobile|Android|iPhone/i.test(navigator.userAgent) ? 'mobile' : 'desktop');
                        fetch('../includes/ajax_push_subscribe.php', { method: 'POST', body: data }).catch(function(){});
                    }
                }).catch(function(){});
            }
        }).catch(function(err) { console.warn('[ISNM] SW registration failed:', err); });
}
</script>
