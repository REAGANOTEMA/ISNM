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
$v = '2.0.1';

// Profile image URL for dashboard header avatars (falls back to username.png)
$profileImageUrl = '../images/username.png';
if (!empty($_SESSION['user_id'])) {
    $pf = __DIR__ . '/profile_settings.php';
    if (file_exists($pf)) {
        include_once $pf;
        if (function_exists('getStaffProfileImageUrl')) {
            $url = getStaffProfileImageUrl((int)$_SESSION['user_id']);
            if ($url) $profileImageUrl = $url;
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
<title><?= htmlspecialchars($pageTitle) ?> | ISNM</title>

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
<link rel="manifest"                               href="/ISNM/manifest.json?v=<?= $v ?>">

<!-- Bootstrap 5.3 -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css?v=<?= $v ?>" rel="stylesheet">
<!-- Font Awesome 6 -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css?v=<?= $v ?>" rel="stylesheet">
<!-- Google Fonts -->
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<!-- Dashboard base styles -->
<link href="<?= $rootPath ?>/dashboards/dashboard-style.css?v=<?= $v ?>" rel="stylesheet">
<!-- Mobile dashboard styles -->
<link href="<?= $rootPath ?>/dashboards/dashboard-mobile.css?v=<?= $v ?>" rel="stylesheet">
<!-- Dashboard Theme System -->
<script src="<?= $rootPath ?>/dashboards/dashboard-theme.js?v=<?= $v ?>" defer></script>
