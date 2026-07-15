<?php
/**
 * Universal Dashboard Head â€” include at the very top of every dashboard <head>
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

// Cache-busting version â€” bump on every deploy
$v = '2.2.1';

/**
 * Profile image URL for dashboard header avatars (falls back to username.png).
 * Performance: avoid repeated DB/file existence checks by caching computed URL in session.
 */
$profileImageUrl = $rootPath . '/images/username.png';

// If cached from a previous dashboard request, reuse it.
if (!empty($_SESSION['dashboard_profile_image_url'])) {
    $profileImageUrl = $_SESSION['dashboard_profile_image_url'];
} else {
    $userType = $_SESSION['type'] ?? '';
    if (!empty($_SESSION['user_id'])) {
        if ($userType === 'student') {
            try {
                $studentConn = getStudentsConnection();
                if ($studentConn) {
                    $q = $studentConn->prepare("SELECT profile_picture, passport_photo FROM students WHERE id = ?");
                    $q->bind_param('i', (int)$_SESSION['user_id']);
                    if (!$q->execute()) { error_log('$q execute failed: ' . ($q->error ?? 'unknown')); };
                    $photoRow = $q->get_result()->fetch_assoc();
                    $q->close();

                    if ($photoRow) {
                        $photoFile = '';
                        if (!empty($photoRow['profile_picture'])) $photoFile = $photoRow['profile_picture'];
                        elseif (!empty($photoRow['passport_photo'])) $photoFile = $photoRow['passport_photo'];

                        if ($photoFile) {
                            $checkPath = __DIR__ . '/../studentUploads/profile_images/' . $photoFile;
                            if (file_exists($checkPath)) {
                                // include cache-buster param to reflect actual file
                                $profileImageUrl = $rootPath . '/studentUploads/profile_images/' . $photoFile . '?v=' . time();
                            }
                        }
                    }
                }
            } catch (Exception $e) { error_log('dashboard_head init: ' . $e->getMessage()); }
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

        // Cache computed URL for subsequent dashboard_head includes.
        $_SESSION['dashboard_profile_image_url'] = $profileImageUrl;
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
<!-- jQuery 3.6 â€” MUST load before any $ usage -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
window.CSRF_TOKEN = '<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>';
window.AJAX_BASE = '<?= $rootPath ?? '..' ?>';
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
        try {
            var r = e.reason;
            if (!r || typeof r !== 'object') return;
            var chk = r.url || '';
            if (!chk && r.reqInfo) {
                chk = (r.reqInfo.pathPrefix || '') + '/' + (r.reqInfo.path || '');
            }
            if (chk.indexOf('/writing/') > -1 || chk.indexOf('/generate/') > -1 || chk.indexOf('/site_integration/') > -1) {
                e.preventDefault();
            }
        } catch(ex) {}
    });
}
</script>
<script>
window.onerror = function(msg, url) {
    if (url && url.indexOf('chrome-extension://') === 0) return true;
    return false;
};
</script>

<!-- Favicon â€” all sizes, all devices -->
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
<!-- 3D Buttons System -->
<link href="<?= $rootPath ?>/css/3d-buttons.css?v=<?= $v ?>" rel="stylesheet">
<!-- Dashboard Base â€” normalized foundation (resolves cross-file conflicts) -->
<link href="<?= $rootPath ?>/dashboards/dashboard-base.css?v=<?= $v ?>" rel="stylesheet">
<!-- Dashboard Layout -->
<link href="<?= $rootPath ?>/dashboards/dashboard-style.css?v=<?= $v ?>" rel="stylesheet">
<!-- Dashboard Professional Design System (cards, tables, badges, KPI) -->
<link href="<?= $rootPath ?>/dashboards/dashboard-professional.css?v=<?= $v ?>" rel="stylesheet">
<!-- Mobile Dashboard Styles -->
<link href="<?= $rootPath ?>/dashboards/dashboard-mobile.css?v=<?= $v ?>" rel="stylesheet">
<!-- Enterprise Dashboard Layout System (merged: modern-ui + enterprise-layout + erp-design-system) -->
<link href="<?= $rootPath ?>/css/enterprise-layout.css?v=<?= $v ?>" rel="stylesheet">
<!-- Mobile Fixes â€” comprehensive responsive improvements (MUST be last CSS) -->
<link href="<?= $rootPath ?>/css/mobile-fixes.css?v=<?= $v ?>" rel="stylesheet">
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
    }, 500);
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
<?php if (defined('VAPID_PUBLIC_KEY') && VAPID_PUBLIC_KEY !== ''): ?>
<!-- Push Notification Service Worker Registration -->
<script>
(function() {
  if (!('serviceWorker' in navigator) || !('PushManager' in window)) return;
  if (document.querySelector('meta[name="push-sw-registered"]')) return;
  if (location.hostname === 'localhost' || location.hostname === '127.0.0.1') return;
  var meta = document.createElement('meta'); meta.name = 'push-sw-registered'; meta.content = '1'; document.head.appendChild(meta);

  navigator.serviceWorker.register('/service-worker.js', { scope: '/' }).then(function(reg) {
      function subscribeUser() {
        reg.pushManager.subscribe({
          userVisibleOnly: true,
          applicationServerKey: urlBase64ToUint8Array('<?= VAPID_PUBLIC_KEY ?>')
        }).then(function(sub) {
          var data = new URLSearchParams();
          data.append('endpoint', sub.endpoint);
          data.append('auth_key', arrayBufferToBase64(sub.getKey('auth')));
          data.append('p256dh_key', arrayBufferToBase64(sub.getKey('p256dh')));
          data.append('device_type', /Mobile|Android|iPhone|iPad/i.test(navigator.userAgent) ? 'mobile' : 'desktop');
          fetch('../includes/ajax_push_subscribe.php', { method: 'POST', body: data, credentials: 'same-origin' }).catch(function(){});
        }).catch(function(err) { console.warn('[SW] Subscribe failed:', err); });
      }

      if (Notification.permission === 'granted') { subscribeUser(); }
      else if (Notification.permission !== 'denied') {
        Notification.requestPermission().then(function(p) { if (p === 'granted') subscribeUser(); });
      }

      reg.onupdatefound = function() {
        var installing = reg.installing;
        installing.onstatechange = function() {
          if (installing.state === 'installed' && navigator.serviceWorker.controller) {
            console.log('[SW] Updated');
          }
        };
      };
    }).catch(function(){});

  function urlBase64ToUint8Array(base64) {
    var padding = '='.repeat((4 - base64.length % 4) % 4);
    var raw = atob((base64 + padding).replace(/-/g, '+').replace(/_/g, '/'));
    var out = new Uint8Array(raw.length);
    for (var i = 0; i < raw.length; i++) out[i] = raw.charCodeAt(i);
    return out;
  }
  function arrayBufferToBase64(buf) {
    var bytes = new Uint8Array(buf);
    var chars = [];
    for (var i = 0; i < bytes.length; i++) chars.push(String.fromCharCode(bytes[i]));
    return btoa(chars.join('')).replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, '');
  }
})();
</script>
<?php endif; ?>

<style>
/* â”€â”€ Floating Action Button Stack (prevents overlap) â”€â”€ */
:root {
  --fab-spacing: 60px;
  --fab-scroll: 24px;          /* scroll-to-top */
  --fab-whatsapp: 84px;        /* whatsapp float */
  --fab-search: 144px;         /* student quick search */
  --fab-comm: 204px;           /* communication button */
  --fab-approval: 264px;       /* department approval fab */
  --fab-cp: 324px;             /* enterprise control panel */
}
/* â”€â”€ Dashboard Responsive Fixes (unified with dashboard-mobile.css) â”€â”€ */
@media (max-width: 768px) {
  .main-content, .dashboard-main { margin-left: 0 !important; }
  .dashboard-header { padding: 0 14px 0 64px !important; }
  .dashboard-header h1 { font-size: 1rem !important; }
  .dashboard-header p { display: none !important; }
  .stat-card { padding: 14px !important; }
  .stat-card .stat-value { font-size: 1.2rem !important; }
  .stat-card .stat-label { font-size: 0.75rem !important; }
  .page-header { flex-direction: column !important; align-items: flex-start !important; }
  .page-header .btn-group { margin-top: 10px; width: 100%; }
  .page-header .btn-group .btn { flex: 1; }
  .nav-tabs .nav-link { padding: 8px 10px; font-size: 0.85rem; }
  .modal-dialog { margin: 10px; }
  input, select, textarea { font-size: 16px !important; }
}
@media (max-width: 576px) {
  .dashboard-header { padding: 0 10px 0 56px !important; }
  .dashboard-header h1 { font-size: 0.9rem !important; }
  h1 { font-size: 1.2rem !important; }
  h2 { font-size: 1rem !important; }
  h3 { font-size: 0.95rem !important; }
  .stat-card .stat-value { font-size: 1.1rem !important; }
  table { font-size: 12px; }
  .table td, .table th { padding: 6px 4px; }
}
@media (min-width: 769px) {
  .sidebar-toggle { display: none !important; }
}
/* Card hover effects */
.dashboard-card { transition: transform 0.2s, box-shadow 0.2s; }
.dashboard-card:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(0,0,0,0.1); }
/* Professional spacing */
.page-header { margin-bottom: 1.5rem; padding-bottom: 1rem; border-bottom: 1px solid #dee2e6; }
/* Table improvements */
.table th { background: #f8f9fa; border-top: none; font-weight: 600; text-transform: uppercase; font-size: 0.8rem; letter-spacing: 0.3px; }
.table td { vertical-align: middle; }
/* Badge fixes */
.badge { font-weight: 500; padding: 5px 10px; }
/* Empty state */
.empty-state { padding: 40px 20px; text-align: center; }
.empty-state i { font-size: 3rem; color: #dee2e6; margin-bottom: 15px; }
/* â”€â”€ Loading Progress Bar â”€â”€ */
.isnm-loading-bar { position:fixed;top:0;left:0;width:0;height:3px;background:linear-gradient(90deg,#3b82f6,#8b5cf6,#ec4899);z-index:9999;transition:width 0.4s ease,opacity 0.3s;box-shadow:0 0 10px rgba(59,130,246,0.5); }
/* â”€â”€ Scroll-to-top â”€â”€ */
.isnm-scroll-top { position:fixed;bottom:var(--fab-scroll,24px);right:24px;width:44px;height:44px;border-radius:50%;background:linear-gradient(135deg,#1a237e,#283593);color:#fff;border:none;font-size:18px;cursor:pointer;z-index:1050;box-shadow:0 4px 16px rgba(0,0,0,0.2);display:none;align-items:center;justify-content:center;transition:transform 0.2s,opacity 0.2s;opacity:0; }
.isnm-scroll-top.show { display:flex;opacity:1; }
.isnm-scroll-top:hover { transform:translateY(-3px);box-shadow:0 6px 20px rgba(0,0,0,0.3); }
/* â”€â”€ Keyboard shortcut toast â”€â”€ */
.isnm-shortcut-toast { position:fixed;bottom:var(--fab-cp,324px);right:24px;background:#0f172a;color:#fff;border-radius:12px;padding:16px 20px;font-size:12px;z-index:9999;box-shadow:0 8px 32px rgba(0,0,0,0.3);max-width:320px;display:none; }
.isnm-shortcut-toast.show { display:block;animation:fadeIn 0.2s ease; }
.isnm-shortcut-toast kbd { display:inline-block;background:rgba(255,255,255,0.1);padding:2px 7px;border-radius:4px;font-size:11px;margin:0 2px;font-family:inherit; }
.isnm-shortcut-toast hr { border-color:rgba(255,255,255,0.1);margin:8px 0; }
</style>
<script>
/* â”€â”€ Loading Progress Bar (deferred until body exists) â”€â”€ */
(function(){
    'use strict';
    var bar, timer;
    function initBar() {
        if (!document.body) { setTimeout(initBar, 30); return; }
        bar = document.createElement('div');
        bar.className = 'isnm-loading-bar';
        document.body.appendChild(bar);
    }
    function startLoad(instant) {
        if (!bar) return;
        clearInterval(timer); bar.style.opacity = '1';
        bar.style.width = instant ? '70%' : '30%';
        if (instant) return;
        timer = setInterval(function(){
            var w = parseFloat(bar.style.width) || 0;
            if (w < 90) bar.style.width = Math.min(90, w + (90 - w) * 0.08) + '%';
        }, 500);
    }
    function finishLoad() {
        if (!bar) return;
        clearInterval(timer);
        bar.style.width = '100%';
        setTimeout(function(){ bar.style.opacity = '0'; setTimeout(function(){ bar.style.width = '0%'; }, 300); }, 300);
    }
    initBar();
    window.ISNM_loadingBar = { start: startLoad, done: finishLoad };
    document.addEventListener('click', function(e){
        var link = e.target.closest('a:not([target="_blank"]):not([href^="#"]):not([href^="javascript"])');
        if (link && link.href && link.href.indexOf(window.location.host) > -1) startLoad(false);
    });
    document.addEventListener('submit', function(){ startLoad(false); });
    if (document.readyState === 'complete') finishLoad();
    else window.addEventListener('load', finishLoad);
})();

/* â”€â”€ Scroll-to-top Button â”€â”€ */
(function(){
    'use strict';
    var btn;
    function initScrollBtn() {
        if (!document.body) { setTimeout(initScrollBtn, 30); return; }
        btn = document.createElement('button');
        btn.className = 'isnm-scroll-top';
        btn.innerHTML = '<i class="fas fa-chevron-up"></i>';
        btn.setAttribute('aria-label', 'Scroll to top');
        document.body.appendChild(btn);
        var ticking = false;
        window.addEventListener('scroll', function(){
            if (!ticking) { requestAnimationFrame(function() {
                if (btn) btn.classList.toggle('show', window.scrollY > 400);
                ticking = false;
            }); ticking = true; }
        });
        btn.addEventListener('click', function(){ window.scrollTo({ top: 0, behavior: 'smooth' }); });
    }
    initScrollBtn();
})();

/* â”€â”€ Keyboard Shortcuts â”€â”€ */
(function(){
    'use strict';
    var toast;
    function initShortcuts() {
        if (!document.body) { setTimeout(initShortcuts, 30); return; }
        toast = document.createElement('div');
        toast.className = 'isnm-shortcut-toast';
        toast.innerHTML = '<strong>Keyboard Shortcuts</strong><hr>\
<kbd>?</kbd> Show this help\
<span style="float:right"><kbd>s</kbd> Search</span><br>\
<kbd>h</kbd> Home\
<span style="float:right"><kbd>q</kbd> Quick actions</span><br>\
<kbd>n</kbd> Notifications\
<span style="float:right"><kbd>Esc</kbd> Close</span>';
        }
    var toastTimer;
    function showHelp() {
        if (!toast) return;
        clearTimeout(toastTimer);
        toast.classList.add('show');
        toastTimer = setTimeout(function(){ if (toast) toast.classList.remove('show'); }, 4000);
    }
    document.addEventListener('keydown', function(e){
        if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA' || e.target.tagName === 'SELECT') return;
        switch(e.key) {
            case '?': e.preventDefault(); showHelp(); break;
            case 's': case 'S': e.preventDefault();
                var search = document.querySelector('.ent-search, .search-input, input[type="search"]');
                if (search) { search.focus(); search.select(); } break;
            case 'h': case 'H': e.preventDefault();
                var home = document.querySelector('.sidebar-brand a, .brand-link, a[href$="director-general.php"]');
                if (home && home.href) window.location.href = home.href; break;
            case 'n': case 'N': e.preventDefault();
                var notif = document.querySelector('.ent-header-btn[data-notif], .notification-btn, .notif-link');
                if (notif) notif.click(); break;
            case 'Escape': if (toast) toast.classList.remove('show'); break;
        }
    });
    initShortcuts();
})();
</script>
