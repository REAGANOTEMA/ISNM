<?php
$rootPath = rtrim(str_repeat('../', substr_count($_SERVER['PHP_SELF'], '/') - 2), '/');
if ($rootPath === '') $rootPath = '.';
?>
<!-- Dashboard professional styles -->
<link href="<?= $rootPath ?>/dashboards/dashboard-professional.css" rel="stylesheet">
<!-- Bootstrap 5.3 JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<!-- Font Awesome JS (icons) -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/js/all.min.js" defer></script>

<script>
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

  // ── Live clock ────────────────────────────────────────────────
  function startClock() {
    var el = document.getElementById('currentDate') || document.getElementById('currentTime');
    if (!el) return;
    function tick() {
      var now = new Date();
      el.textContent = now.toLocaleDateString('en-UG', {
        weekday: 'short', year: 'numeric', month: 'short', day: 'numeric'
      }) + '  ' + now.toLocaleTimeString('en-UG', { hour: '2-digit', minute: '2-digit' });
    }
    tick();
    setInterval(tick, 1000);
  }

  // ── Service Worker ────────────────────────────────────────────
  function registerSW() {
    if ('serviceWorker' in navigator) {
      navigator.serviceWorker.register('/ISNM/sw.js', { scope: '/ISNM/' }).catch(function () {});
    }
  }

  document.addEventListener('DOMContentLoaded', function () {
    initSidebar();
    setActiveNav();
    startClock();
    registerSW();
  });
})();
</script>
