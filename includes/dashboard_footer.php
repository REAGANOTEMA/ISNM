<?php
$rootPath = rtrim(str_repeat('../', substr_count($_SERVER['PHP_SELF'], '/') - 2), '/');
if ($rootPath === '') $rootPath = '.';

// Cache-busting version — bump on every deploy
$v = '2.0.1';

// Universal student quick-search on every dashboard
if (!isset($studentQuickSearchRendered) && !defined('STUDENT_QUICK_SEARCH_DISABLED')) {
    $sqsFile = __DIR__ . '/student_quick_search.php';
    if (file_exists($sqsFile)) {
        try {
            include_once $sqsFile;
        } catch (Exception $e) {}
    }
}
?>
<!-- Dashboard professional styles -->
<link href="<?= $rootPath ?>/dashboards/dashboard-professional.css?v=<?= $v ?>" rel="stylesheet">
<!-- Bootstrap 5.3 JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<!-- Font Awesome JS (icons) -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/js/all.min.js" defer></script>

<script>
// Cache-busting version constant
var ISNM_VERSION = '<?= $v ?>';

// ── Mobile sidebar toggle ─────────────────────────────────────
(function () {
  function initSidebar() {
    var sidebar  = document.querySelector('.sidebar, .dashboard-sidebar');
    var overlay  = document.getElementById('sidebarOverlay');
    var toggleBtn = document.getElementById('sidebarToggle');
    if (!sidebar) return;

    // Inject toggle button if missing
    if (!toggleBtn) {
      toggleBtn = document.createElement('button');
      toggleBtn.id = 'sidebarToggle';
      toggleBtn.className = 'sidebar-toggle';
      toggleBtn.innerHTML = '<i class="fas fa-bars"></i>';
      document.body.appendChild(toggleBtn);
    }

    // Inject overlay if missing
    if (!overlay) {
      overlay = document.createElement('div');
      overlay.id = 'sidebarOverlay';
      overlay.className = 'sidebar-overlay';
      document.body.appendChild(overlay);
    }

    function open()  { sidebar.classList.add('open'); overlay.classList.add('open'); }
    function close() { sidebar.classList.remove('open'); overlay.classList.remove('open'); }

    toggleBtn.addEventListener('click', function () {
      sidebar.classList.contains('open') ? close() : open();
    });
    overlay.addEventListener('click', close);

    // Close on nav link click (mobile)
    sidebar.querySelectorAll('.nav-link').forEach(function (link) {
      link.addEventListener('click', function () {
        if (window.innerWidth < 769) close();
      });
    });
  }

  // ── Active nav link ───────────────────────────────────────────
  function setActiveNav() {
    var path = window.location.pathname;
    document.querySelectorAll('.sidebar .nav-link, .dashboard-sidebar .nav-link').forEach(function (link) {
      var href = link.getAttribute('href') || '';
      if (href && path.includes(href.replace(/^.*\//, '').replace('.php', ''))) {
        link.classList.add('active');
      }
    });
  }

  // ── Live clock (multi-format) ────────────────────────────────
  function startClock() {
    var el = document.getElementById('currentDate') || document.getElementById('currentTime');
    if (!el) return;
    function tick() {
      var now = new Date();
      var opts = { weekday: 'short', year: 'numeric', month: 'short', day: 'numeric' };
      el.textContent = now.toLocaleDateString('en-UG', opts)
        + '  ' + now.toLocaleTimeString('en-UG', { hour: '2-digit', minute: '2-digit' });
    }
    tick();
    setInterval(tick, 1000);
  }

  // ── Service Worker ────────────────────────────────────────────
  function registerSW() {
    if ('serviceWorker' in navigator) {
      navigator.serviceWorker.register('/ISNM/sw.js?v=' + ISNM_VERSION, { scope: '/ISNM/' }).catch(function () {});
    }
  }

  // ── iOS / Android install banner detection ────────────────────
  function detectPWA() {
    var isStandalone = window.navigator.standalone || window.matchMedia('(display-mode: standalone)').matches;
    if (isStandalone) {
      document.documentElement.classList.add('is-pwa');
    }
  }

  document.addEventListener('DOMContentLoaded', function () {
    initSidebar();
    setActiveNav();
    startClock();
    registerSW();
    detectPWA();
  });
})();
</script>
