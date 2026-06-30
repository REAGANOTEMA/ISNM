<?php
/**
 * Universal Dashboard Head — include at the very top of every dashboard <head>
 * Usage: include_once __DIR__ . '/../includes/dashboard_head.php';
 * Set $pageTitle before including, e.g.: $pageTitle = 'Director General';
 */
$pageTitle = $pageTitle ?? 'Dashboard';
$selfDir   = dirname($_SERVER['PHP_SELF']);
$rootPath  = '..';
// Calculate absolute project root for SW scope
$scopeParts = explode('/', trim($selfDir, '/'));
array_pop($scopeParts); // remove 'dashboards'
$swScope    = empty($scopeParts) ? '/' : '/' . implode('/', $scopeParts) . '/';

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
$(document).ajaxSend(function(e, xhr, opts) {
    if (window.CSRF_TOKEN) {
        xhr.setRequestHeader('X-CSRF-Token', window.CSRF_TOKEN);
        if (opts.type === 'POST') {
            if (typeof opts.data === 'string' && opts.data.indexOf('csrf_token=') === -1) {
                opts.data += (opts.data ? '&' : '') + 'csrf_token=' + encodeURIComponent(window.CSRF_TOKEN);
            } else if (typeof opts.data === 'object' && opts.data && !(opts.data instanceof FormData)) {
                opts.data.csrf_token = window.CSRF_TOKEN;
            }
        }
    }
});
document.addEventListener('DOMContentLoaded', function() {
    if (window.CSRF_TOKEN) {
        document.querySelectorAll('form[method="POST"], form[method="post"]').forEach(function(form) {
            if (!form.querySelector('input[name="csrf_token"]')) {
                var input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'csrf_token';
                input.value = window.CSRF_TOKEN;
                form.appendChild(input);
            }
        });
    }
});
</script>
<script>
if (!window._isnmErrorHandlerInstalled) {
    window._isnmErrorHandlerInstalled = true;
    window.addEventListener('unhandledrejection', function(e) {
        var url = '';
        try {
            if (e.reason && typeof e.reason === 'object') {
                url = e.reason.url || '';
            } else if (typeof e.reason === 'string') {
                url = e.reason;
            }
        } catch(ex) {}
        if (url.indexOf('/writing/') > -1 || url.indexOf('/generate/') > -1 || url.indexOf('/site_integration/') > -1) {
            e.preventDefault();
        }
    });
}
</script>
<script>
window.onerror = function(msg, url) {
    if (url && url.indexOf('chrome-extension://') === 0) return true;
    return false;
};
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
<!-- Flatpickr with 4-tier fallback -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css" id="flatpickr-css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr" id="flatpickr-js"></script>
<script>
(function() {
    function loadFlatpickrFromFallback() {
        var s = document.createElement('script');
        s.src = 'https://cdnjs.cloudflare.com/ajax/libs/flatpickr/4.6.13/flatpickr.min.js';
        s.onload = function() {
            var l = document.createElement('link');
            l.rel = 'stylesheet';
            l.href = 'https://cdnjs.cloudflare.com/ajax/libs/flatpickr/4.6.13/flatpickr.min.css';
            document.head.appendChild(l);
            initFlatpickr();
        };
        s.onerror = function() {
            console.warn('Flatpickr CDN unavailable - date inputs will use native picker');
            window.flatpickr = function() { return { destroy: function(){} }; };
        };
        document.head.appendChild(s);
    }
    function initFlatpickr() {
        if (typeof flatpickr === 'function') {
            document.querySelectorAll('input[type="date"], input.flatpickr-input, .datepicker').forEach(function(el) {
                try {
                    if (!el._flatpickr) flatpickr(el, { dateFormat: 'Y-m-d', allowInput: true });
                } catch(e) { console.warn('flatpickr init skipped:', e); }
            });
        }
    }
    // Tier 1: Already loaded
    if (typeof flatpickr === 'function') { initFlatpickr(); return; }
    // Tier 2: Wait for CDN
    var check = setInterval(function() {
        if (typeof flatpickr === 'function') { clearInterval(check); initFlatpickr(); }
    }, 200);
    // Tier 3: Load from fallback CDN after 3s timeout
    setTimeout(function() {
        if (typeof flatpickr !== 'function') loadFlatpickrFromFallback();
    }, 3000);
    // Tier 4: Native fallback after 8s
    setTimeout(function() {
        if (typeof flatpickr !== 'function') {
            window.flatpickr = function() { return { destroy: function(){} }; };
            console.warn('Flatpickr unavailable after 8s - using native date picker');
        }
    }, 8000);
    // Re-init on dynamic content
    document.addEventListener('DOMContentLoaded', function() {
        setTimeout(initFlatpickr, 500);
    });
})();
</script>
<!-- Push Notification Subscription (disabled: VAPID keys not configured) -->

<style>
/* Responsive dashboard fixes */
@media (max-width: 768px) {
  .sidebar { width: 100% !important; position: relative !important; }
  .main-content { margin-left: 0 !important; padding: 10px !important; }
  .dashboard-card { margin-bottom: 15px; }
  .card-body { padding: 15px; }
  .table-responsive { font-size: 13px; }
  .btn { width: 100%; margin-bottom: 5px; }
  .row-cards { padding: 0 5px; }
  h1, h2, h3 { font-size: 1.2rem; }
  .navbar-brand { font-size: 1rem; }
  .container-fluid { padding-left: 10px; padding-right: 10px; }
  .page-header { flex-direction: column; align-items: flex-start; }
  .page-header .btn-group { margin-top: 10px; width: 100%; }
  .page-header .btn-group .btn { flex: 1; }
  .stat-card { padding: 12px; }
  .stat-card .stat-value { font-size: 1.5rem; }
  .stat-card .stat-label { font-size: 0.75rem; }
  input, select, textarea { font-size: 16px !important; }
  .dataTables_wrapper .dataTables_filter input { width: 150px !important; }
  .modal-dialog { margin: 10px; }
  .card-title { font-size: 1rem; }
  .nav-tabs .nav-link { padding: 8px 10px; font-size: 0.85rem; }
}
@media (max-width: 576px) {
  .sidebar { display: none; }
  .sidebar.mobile-show { display: block; position: fixed; z-index: 9999; width: 100%; height: 100%; top: 0; left: 0; overflow-y: auto; }
  .mobile-menu-toggle { display: block !important; }
  .main-content { padding: 5px !important; }
  .card-body { padding: 10px; }
  .row-cards .col-6 { padding: 0 3px; }
  .stat-card .stat-value { font-size: 1.2rem; }
  h1 { font-size: 1.3rem; }
  h2 { font-size: 1.1rem; }
  h3 { font-size: 1rem; }
  .page-header h1 { font-size: 1.2rem; }
  .breadcrumb { font-size: 0.8rem; }
  table { font-size: 12px; }
  .table td, .table th { padding: 6px 4px; }
}
@media (min-width: 769px) {
  .mobile-menu-toggle { display: none !important; }
}
/* Card hover effects */
.dashboard-card { transition: transform 0.2s, box-shadow 0.2s; }
.dashboard-card:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(0,0,0,0.1); }
/* Professional spacing */
.page-header { margin-bottom: 1.5rem; padding-bottom: 1rem; border-bottom: 1px solid #dee2e6; }
.stat-card { background: #fff; border-radius: 10px; padding: 20px; margin-bottom: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border: 1px solid #e9ecef; }
.stat-card .stat-icon { font-size: 2rem; opacity: 0.8; }
.stat-card .stat-value { font-size: 1.8rem; font-weight: 700; color: #2c3e50; }
.stat-card .stat-label { font-size: 0.85rem; color: #6c757d; text-transform: uppercase; letter-spacing: 0.5px; }
/* Table improvements */
.table th { background: #f8f9fa; border-top: none; font-weight: 600; text-transform: uppercase; font-size: 0.8rem; letter-spacing: 0.3px; }
.table td { vertical-align: middle; }
/* Badge fixes */
.badge { font-weight: 500; padding: 5px 10px; }
/* Empty state */
.empty-state { padding: 40px 20px; text-align: center; }
.empty-state i { font-size: 3rem; color: #dee2e6; margin-bottom: 15px; }
</style>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var toggle = document.querySelector('.mobile-menu-toggle') || (function() {
        var btn = document.createElement('button');
        btn.className = 'mobile-menu-toggle btn btn-sm btn-outline-secondary';
        btn.innerHTML = '<i class="fas fa-bars"></i>';
        btn.style.cssText = 'position:fixed;top:10px;left:10px;z-index:9999;display:none;border-radius:50%;width:40px;height:40px;';
        document.body.appendChild(btn);
        return btn;
    })();
    toggle.addEventListener('click', function() {
        document.querySelector('.sidebar').classList.toggle('mobile-show');
    });
});
</script>
