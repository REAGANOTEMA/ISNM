<?php
$rootPath = rtrim(str_repeat('../', substr_count($_SERVER['PHP_SELF'], '/') - 2), '/');
if ($rootPath === '') $rootPath = '.';

// Cache-busting version — bump on every deploy
$v = '2.0.1';

// Profile Settings — universal staff profile image upload
$profileSettingsFile = __DIR__ . '/profile_settings.php';
if (file_exists($profileSettingsFile)) {
    try {
        include_once $profileSettingsFile;
    } catch (Exception $e) {}
}

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
<?php if (function_exists('renderProfileStyles')) renderProfileStyles(); ?>
<!-- Dashboard professional styles -->
<link href="<?= $rootPath ?>/dashboards/dashboard-professional.css?v=<?= $v ?>" rel="stylesheet">
<!-- Bootstrap 5.3 JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<!-- Font Awesome JS (icons) -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/js/all.min.js" defer></script>
<?php if (function_exists('renderProfileButton')) renderProfileButton(); ?>
<?php if (function_exists('renderProfileModal')) renderProfileModal(); ?>

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

  // ── Notification Bell ──────────────────────────────────────────
  function initNotificationBell() {
    var container = document.createElement('div');
    container.id = 'notifBellContainer';
    container.innerHTML =
      '<div id="notifBell" class="notif-bell" title="Notifications">' +
        '<i class="fas fa-bell"></i>' +
        '<span id="notifBadge" class="notification-badge" style="display:none">0</span>' +
      '</div>' +
      '<div id="notifDropdown" class="notif-dropdown" style="display:none">' +
        '<div class="notif-header"><strong>Notifications</strong><button id="notifMarkAllRead" class="btn btn-sm btn-link">Mark all read</button></div>' +
        '<div id="notifList" class="notif-list"><div class="text-muted small text-center py-3">Loading...</div></div>' +
      '</div>';
    document.body.appendChild(container);

    var bellEl = document.getElementById('notifBell');
    var dropdown = document.getElementById('notifDropdown');
    var badge = document.getElementById('notifBadge');
    var listEl = document.getElementById('notifList');
    var markAllBtn = document.getElementById('notifMarkAllRead');

    function fetchNotifications() {
      var xhr = new XMLHttpRequest();
      xhr.open('GET', '../includes/ajax_notifications.php?action=fetch&_=' + Date.now(), true);
      xhr.onload = function () {
        if (xhr.status !== 200) return;
        try {
          var d = JSON.parse(xhr.responseText);
          if (d.unread > 0) {
            badge.textContent = d.unread > 99 ? '99+' : d.unread;
            badge.style.display = '';
          } else {
            badge.style.display = 'none';
          }
          if (dropdown.style.display !== 'none' && d.notifications) {
            renderNotifications(d.notifications);
          }
        } catch (e) {}
      };
      xhr.send();
    }

    function renderNotifications(notifs) {
      if (!notifs || !notifs.length) {
        listEl.innerHTML = '<div class="text-muted small text-center py-3">No notifications.</div>';
        return;
      }
      var html = '';
      notifs.forEach(function (n) {
        var cls = n.is_read == '1' ? 'notif-item-read' : '';
        var iconMap = { info: 'fas fa-info-circle', warning: 'fas fa-exclamation-triangle', success: 'fas fa-check-circle', danger: 'fas fa-times-circle', announcement: 'fas fa-bullhorn' };
        var icon = iconMap[n.type] || 'fas fa-bell';
        html += '<div class="notif-item ' + cls + '" data-id="' + n.id + '">' +
          '<div class="notif-icon"><i class="' + icon + '"></i></div>' +
          '<div class="notif-body">' +
            '<div class="notif-title">' + escHtml(n.title) + '</div>' +
            (n.message ? '<div class="notif-msg">' + escHtml(n.message) + '</div>' : '') +
            '<div class="notif-time">' + timeAgo(n.created_at) + '</div>' +
          '</div>' +
          (n.is_read == '0' ? '<div class="notif-unread-dot"></div>' : '') +
        '</div>';
      });
      listEl.innerHTML = html;

      listEl.querySelectorAll('.notif-item').forEach(function (item) {
        item.addEventListener('click', function () {
          var nid = this.getAttribute('data-id');
          markRead(nid);
        });
      });
    }

    function markRead(nid) {
      var xhr = new XMLHttpRequest();
      xhr.open('POST', '../includes/ajax_notifications.php?action=mark_read', true);
      xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
      xhr.onload = function () { fetchNotifications(); };
      xhr.send('id=' + nid);
    }

    function markAllRead() {
      var xhr = new XMLHttpRequest();
      xhr.open('POST', '../includes/ajax_notifications.php?action=mark_all_read', true);
      xhr.onload = function () { fetchNotifications(); };
      xhr.send();
    }

    bellEl.addEventListener('click', function (e) {
      e.stopPropagation();
      var isOpen = dropdown.style.display !== 'none';
      dropdown.style.display = isOpen ? 'none' : 'block';
      if (!isOpen) fetchNotifications();
    });

    document.addEventListener('click', function (e) {
      if (!container.contains(e.target)) dropdown.style.display = 'none';
    });

    if (markAllBtn) markAllBtn.addEventListener('click', markAllRead);

    // Poll every 30 seconds
    fetchNotifications();
    setInterval(fetchNotifications, 30000);
  }

  function escHtml(s) { if (!s) return ''; var d = document.createElement('div'); d.textContent = s; return d.innerHTML; }
  function timeAgo(ts) {
    if (!ts) return '';
    var diff = (Date.now() - new Date(ts).getTime()) / 1000;
    if (diff < 60) return 'just now';
    if (diff < 3600) return Math.floor(diff / 60) + 'm ago';
    if (diff < 86400) return Math.floor(diff / 3600) + 'h ago';
    if (diff < 604800) return Math.floor(diff / 86400) + 'd ago';
    return new Date(ts).toLocaleDateString();
  }

  document.addEventListener('DOMContentLoaded', function () {
    initSidebar();
    setActiveNav();
    startClock();
    registerSW();
    detectPWA();
    initNotificationBell();
    <?php if (function_exists('renderProfileScripts')): ?>try { <?php renderProfileScripts(); ?> } catch(e){}<?php endif; ?>
  });
})();
</script>

<style>
/* ── Notification Bell Styles ──────────────────────────────────── */
.notif-bell {
  position: fixed; top: 12px; right: 20px; z-index: 1060;
  width: 40px; height: 40px; border-radius: 50%;
  background: #1a237e; color: #fff; display: flex;
  align-items: center; justify-content: center;
  cursor: pointer; box-shadow: 0 2px 10px rgba(0,0,0,.15);
  transition: transform .2s ease; font-size: 18px;
}
.notif-bell:hover { transform: scale(1.08); }
.notif-bell .notification-badge {
  position: absolute; top: -4px; right: -4px;
  background: linear-gradient(45deg,#ff6b6b,#ee5a24); color: #fff;
  border-radius: 50%; min-width: 20px; height: 20px;
  font-size: 11px; font-weight: 700; line-height: 20px;
  text-align: center; animation: badgePulse 2s infinite;
}
@keyframes badgePulse { 0%,100%{transform:scale(1)} 50%{transform:scale(1.1)} }
.notif-dropdown {
  position: fixed; top: 58px; right: 16px; z-index: 1060;
  width: 360px; max-height: 480px; background: #fff;
  border-radius: 12px; box-shadow: 0 8px 30px rgba(0,0,0,.15);
  overflow: hidden; display: flex; flex-direction: column;
}
.notif-header {
  display: flex; justify-content: space-between; align-items: center;
  padding: 12px 16px; border-bottom: 1px solid #e2e8f0;
  background: #f8fafc;
}
.notif-header strong { color: #1a237e; font-size: 14px; }
.notif-header .btn-link { font-size: 12px; color: #1a237e; text-decoration: none; padding: 0; }
.notif-header .btn-link:hover { text-decoration: underline; }
.notif-list { overflow-y: auto; flex: 1; max-height: 400px; }
.notif-item {
  display: flex; gap: 12px; padding: 12px 16px; cursor: pointer;
  border-bottom: 1px solid #f1f5f9; transition: background .15s;
  position: relative;
}
.notif-item:hover { background: #f8fafc; }
.notif-item-read { opacity: .65; }
.notif-icon { width: 32px; height: 32px; flex-shrink: 0; display: flex; align-items: center; justify-content: center; border-radius: 8px; background: #eef2ff; color: #1a237e; font-size: 14px; }
.notif-body { flex: 1; min-width: 0; }
.notif-title { font-size: 13px; font-weight: 600; color: #0f172a; line-height: 1.3; }
.notif-msg { font-size: 12px; color: #64748b; margin-top: 2px; line-height: 1.3; overflow: hidden; text-overflow: ellipsis; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; }
.notif-time { font-size: 11px; color: #94a3b8; margin-top: 4px; }
.notif-unread-dot {
  width: 8px; height: 8px; border-radius: 50%; background: #1a237e;
  position: absolute; top: 14px; right: 14px; flex-shrink: 0;
}
@media (max-width: 480px) {
  .notif-dropdown { width: calc(100% - 32px); right: 16px; }
}
</style>
