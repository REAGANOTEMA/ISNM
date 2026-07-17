<?php
$rootPath = '..';

// Cache-busting version — bump on every deploy
$v = '2.2.1';

// Profile Settings — universal staff profile image upload
$profileSettingsFile = __DIR__ . '/profile_settings.php';
if (file_exists($profileSettingsFile)) {
    try {
        include_once $profileSettingsFile;
    } catch (Exception $e) { error_log('dashboard_footer stats: ' . $e->getMessage()); }
}

// Universal student quick-search on every dashboard
if (!isset($studentQuickSearchRendered) && !defined('STUDENT_QUICK_SEARCH_DISABLED')) {
    $sqsFile = __DIR__ . '/student_quick_search.php';
    if (file_exists($sqsFile)) {
        try {
            include_once $sqsFile;
        } catch (Exception $e) { error_log('dashboard_footer stats: ' . $e->getMessage()); }
    }
}

// Global search UI (Ctrl+K) for every dashboard
$gsFile = __DIR__ . '/global_search.php';
if (file_exists($gsFile)) {
    try {
        include_once $gsFile;
        if (function_exists('renderGlobalSearchBar')) {
            renderGlobalSearchBar();
        }
    } catch (Exception $e) { error_log('dashboard_footer global_search: ' . $e->getMessage()); }
}

// Staff communication system (compose-to-department modal)
if (session_status() === PHP_SESSION_NONE) session_start();
if (!empty($_SESSION['logged_in']) && ($_SESSION['type'] ?? '') === 'staff') {
    $commFile = __DIR__ . '/staff_communication.php';
    if (file_exists($commFile)) {
        require_once $commFile;
        renderCommunicationModal();
    }
}

// Universal Department Approval Request — every staff dashboard gets Submit-to-DG capability
if (!empty($_SESSION['logged_in']) && ($_SESSION['type'] ?? '') === 'staff') {
    $depApprovalFile = __DIR__ . '/department_approval_request.php';
    if (file_exists($depApprovalFile)) {
        require_once $depApprovalFile;
        renderDepartmentApprovalModal();
    }
}

// Shared Component Library — available to ALL dashboards
try { require_once __DIR__ . '/dashboard_components.php'; } catch (Exception $e) { error_log('dashboard_components.php load failed: ' . $e->getMessage()); }

// Shared Dashboard Toolbar — available to ALL dashboards
$toolFile = __DIR__ . '/dashboard_toolbar.php';
if (file_exists($toolFile)) { try { include_once $toolFile; } catch (Exception $e) { error_log('dashboard_footer stats: ' . $e->getMessage()); } }
?>
<?php if (function_exists('renderProfileStyles')) renderProfileStyles(); ?>
<!-- Bootstrap 5.3 JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<!-- Font Awesome (CSS/webfont version — loaded in head, no fetch rejections) -->
<?php if (function_exists('renderProfileModal') && ($_SESSION['type'] ?? '') === 'staff') renderProfileModal(); ?>
<?php if (function_exists('renderDepartmentApprovalScripts') && ($_SESSION['type'] ?? '') === 'staff') renderDepartmentApprovalScripts(); ?>

<script>
// Cache-busting version constant
var ISNM_VERSION = '<?= $v ?>';

// ── Mobile sidebar toggle ─────────────────────────────────────
(function () {
  function initSidebar() {
    var sidebar  = document.querySelector('.sidebar, .dashboard-sidebar, .isnm-sidebar');
    var overlay  = document.getElementById('sidebarOverlay');
    var toggleBtn = document.getElementById('sidebarToggle');
    if (!sidebar) return;

    // Inject toggle button if missing
    if (!toggleBtn) {
      toggleBtn = document.createElement('button');
      toggleBtn.id = 'sidebarToggle';
      toggleBtn.className = 'sidebar-toggle';
      toggleBtn.setAttribute('aria-label', 'Toggle sidebar');
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

    function open()  {
      sidebar.classList.add('open');
      overlay.classList.add('open');
      toggleBtn.innerHTML = '<i class="fas fa-times"></i>';
      document.body.classList.add('menu-open');
    }
    function close() {
      sidebar.classList.remove('open');
      overlay.classList.remove('open');
      toggleBtn.innerHTML = '<i class="fas fa-bars"></i>';
      document.body.classList.remove('menu-open');
    }

    toggleBtn.addEventListener('click', function () {
      sidebar.classList.contains('open') ? close() : open();
    });
    overlay.addEventListener('click', close);

    // Close on nav link click (mobile)
    Array.from(sidebar.querySelectorAll('.nav-link')).forEach(function (link) {
      link.addEventListener('click', function () {
        if (window.innerWidth < 769) close();
      });
    });

    // Clean up on resize to desktop
    window.addEventListener('resize', function() {
      if (window.innerWidth >= 992) {
        document.body.classList.remove('menu-open');
      }
    });
  }

  // ── Active nav link ───────────────────────────────────────────
  function setActiveNav() {
    var path = window.location.pathname;
    Array.from(document.querySelectorAll('.sidebar .nav-link, .dashboard-sidebar .nav-link')).forEach(function (link) {
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
      xhr.open('GET', '<?= $rootPath ?>/includes/ajax_notifications.php?action=fetch&_=' + Date.now(), true);
        xhr.onload = function () {
          if (xhr.status !== 200) return;
          try {
            var txt = xhr.responseText.replace(/^\uFEFF/, '');
            var d = JSON.parse(txt);
          if (d.unread > 0) {
            badge.textContent = d.unread > 99 ? '99+' : d.unread;
            badge.style.display = '';
          } else {
            badge.style.display = 'none';
          }
          if (dropdown.style.display !== 'none' && d.notifications) {
            renderNotifications(d.notifications);
          }
        } catch (e) { console.warn('[ISNM] Notification parse error:', e); }
      };
      xhr.onerror = function(){ console.warn('[ISNM] Notification fetch failed (network error)'); };
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

      Array.from(listEl.querySelectorAll('.notif-item')).forEach(function (item) {
        item.addEventListener('click', function () {
          var nid = this.getAttribute('data-id');
          markRead(nid);
        });
      });
    }

    function markRead(nid) {
      var xhr = new XMLHttpRequest();
      xhr.open('POST', '<?= $rootPath ?>/includes/ajax_notifications.php?action=mark_read', true);
      xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
      xhr.setRequestHeader('X-CSRF-Token', window.CSRF_TOKEN || '');
      xhr.onload = function () { fetchNotifications(); };
      xhr.onerror = function(){ console.warn('[ISNM] Mark read network error'); };
      xhr.send('csrf_token=' + encodeURIComponent(window.CSRF_TOKEN || '') + '&notification_id=' + encodeURIComponent(nid));
    }

    function markAllRead() {
      var xhr = new XMLHttpRequest();
      xhr.open('POST', '<?= $rootPath ?>/includes/ajax_notifications.php?action=mark_all_read', true);
      xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
      xhr.setRequestHeader('X-CSRF-Token', window.CSRF_TOKEN || '');
      xhr.onload = function () { fetchNotifications(); };
      xhr.onerror = function(){ console.warn('[ISNM] Mark all read network error'); };
      xhr.send('csrf_token=' + encodeURIComponent(window.CSRF_TOKEN || ''));
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

  // ── Global Link Loading Animation ──────────────────────────────
  function initGlobalLoader() {
    var loader = document.getElementById('isnmLoader');
    if (!loader) return;
    var shown = false;
    var hideTimer = null;
    function showLoader() {
      if (!shown) { shown = true; loader.classList.add('active'); }
    }
    function hideLoader() {
      // Ensure loader shows for at least 600ms for smooth appearance
      if (hideTimer) clearTimeout(hideTimer);
      hideTimer = setTimeout(function() {
        shown = false;
        loader.classList.remove('active');
      }, 600);
    }

    document.addEventListener('click', function(e) {
      var link = e.target.closest('a');
      if (!link) return;
      var href = link.getAttribute('href') || '';
      if (href.startsWith('#') || href.startsWith('javascript:') || href.startsWith('mailto:') || href.startsWith('tel:') || href.startsWith('http')) return;
      if (link.getAttribute('target') === '_blank') return;
      if (link.hasAttribute('data-no-loader')) return;
      if (link.closest('.isnm-mobile-toggle') || link.closest('.sidebar-collapse-btn')) return;
      if (link.closest('form')) return;
      if (e.button !== 0) return;
      showLoader();
    });

    window.addEventListener('pageshow', hideLoader);
    window.addEventListener('load', hideLoader);
    window.addEventListener('popstate', hideLoader);
  }

  document.addEventListener('DOMContentLoaded', function () {
    initSidebar();
    setActiveNav();
    startClock();
    detectPWA();
    initNotificationBell();
    initGlobalLoader();
    var el = document.getElementById('isnmLoader');
    if (el) { el.classList.remove('active'); }
  });
})();

/* ══════════════════════════════════════════════════════════════════
   UNIVERSAL DASHBOARD FIXES — applies to EVERY dashboard
   ══════════════════════════════════════════════════════════════════ */

// ── 1. UNIVERSAL SECTION SWITCHER ──────────────────────────────
// If the dashboard uses data-section, this ensures ?page= and ?section= params
// activate the correct section.
(function() {
    function switchToSection(name) {
        if (!name) return;
        // Try data-section elements (most common pattern)
        var targets = document.querySelectorAll('[data-section]');
        if (targets.length) {
            Array.from(targets).forEach(function(el) { el.classList.remove('active'); });
            var match = document.querySelector('[data-section="' + name + '"]');
            if (match) {
                match.classList.add('active');
                return true;
            }
        }
        // Try element id match (director-general.php pattern)
        var byId = document.getElementById(name);
        if (byId) {
            byId.classList.add('active');
            return true;
        }
        // Try content-section + id
        var cs = document.querySelector('.content-section#' + name + ', .dashboard-section#' + name);
        if (cs) {
            cs.classList.add('active');
            return true;
        }
        return false;
    }

    // Read ?page= or ?section= from URL — activate the correct section
    // Default to 'home' → 'overview' → first data-section so content isn't hidden
    var m = window.location.search.match(/[?&](?:section|page)=([^&]+)/);
    var sectionParam = (m && m[1]) || '';
    if (sectionParam) {
        switchToSection(sectionParam);
    } else {
        // No param: try common defaults, then first available section
        if (!switchToSection('home') && !switchToSection('overview')) {
            var firstSec = document.querySelector('[data-section]');
            if (firstSec) switchToSection(firstSec.getAttribute('data-section'));
        }
    }

    // Listen for hash changes (for data-section switching)
    window.addEventListener('hashchange', function() {
        var hash = window.location.hash.replace('#', '');
        if (hash) switchToSection(hash);
    });

    // Expose globally so dashboard-specific code can call it
    window.switchToSection = window.switchToSection || switchToSection;
})();

// ── 2. CLOSE ALL BOOTSTRAP MODALS ON BACKDROP / BUTTON CLICK ──
// Fixes modals that open but cannot be closed
(function() {
    document.addEventListener('click', function(e) {
        var closeBtn = e.target.closest('[data-bs-dismiss="modal"], .btn-close, .close, .modal-close');
        if (!closeBtn) {
            // Click outside modal-content inside modal -> close
            var modal = e.target.closest('.modal');
            if (modal && !e.target.closest('.modal-content') && e.target !== modal) {
                var bsModal = bootstrap.Modal.getInstance(modal);
                if (bsModal) bsModal.hide();
            }
            return;
        }
        var modal = closeBtn.closest('.modal');
        if (modal) {
            var bsModal = bootstrap.Modal.getInstance(modal);
            if (bsModal) bsModal.hide();
        }
    });

    // Ensure all .modal elements can be closed with Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            Array.from(document.querySelectorAll('.modal.show')).forEach(function(m) {
                var bsModal = bootstrap.Modal.getInstance(m);
                if (bsModal) bsModal.hide();
            });
        }
    });
})();

// ── 3. MOBILE RESPONSIVENESS — remove inline margins/hardcode ──
(function() {
    function fixMobileLayout() {
        if (window.innerWidth > 768) return;
        // Remove hardcoded margin-left from common content wrappers
        var mobileSelectors = 'gld-content,page-content,content-section,dashboard-section,' +
                        'lec-content,mat-content,war-content,hr-content,' +
                        'secu-content,drv-content,lib-content,' +
                        'nurs-content,mid-content,dg-content,prin-content,' +
                        'dep-content,sec-content,ict-content,' +
                        'fin-content,acad-content,' +
                        'transcript-container,dg-topbar,' +
                        'dashboard-content,content-wrapper,main-content,main,' +
                        'page-wrap,main-wrap';
        mobileSelectors.split(',').forEach(function(cls) {
            var els = document.getElementsByClassName(cls);
            for (var i = 0; i < els.length; i++) {
                if (els[i]) els[i].style.marginLeft = '';
            }
            // Also try as id
            var el = document.getElementById(cls);
            if (el) el.style.marginLeft = '';
        });
    }
    fixMobileLayout();
    window.addEventListener('resize', function() {
        if (window.innerWidth <= 768) fixMobileLayout();
    });
})();

// ── 4. UNIVERSAL filterTable() FUNCTION ────────────────────────
// Makes filterTable available to ALL dashboards
window.filterTable = window.filterTable || function(inputId, tableId) {
    var input = document.getElementById(inputId);
    if (!input) return;
    var filter = input.value.toUpperCase();
    var table = document.getElementById(tableId);
    if (!table) return;
    var tr = table.getElementsByTagName("tr");
    for (var i = 1; i < tr.length; i++) {
        var td = tr[i].getElementsByTagName("td");
        var found = false;
        for (var j = 0; j < td.length; j++) {
            if (td[j] && td[j].textContent.toUpperCase().indexOf(filter) > -1) { found = true; break; }
        }
        tr[i].style.display = found ? "" : "none";
    }
};

// ── 5. UNIVERSAL HELPER FUNCTIONS ─────────────────────────────
window.escHtml = window.escHtml || function(s) {
    if (!s) return '';
    var d = document.createElement('div');
    d.textContent = s;
    return d.innerHTML;
};
window.escJs = window.escJs || function(s) {
    if (!s) return '';
    return String(s).replace(/[&<>"']/g, function(c) {
        return '&#' + c.charCodeAt(0) + ';';
    });
};

// ── 6. STUDENT LOOKUP — ensure close on outside click ─────────
(function() {
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.student-lookup-wrapper')) {
            Array.from(document.querySelectorAll('.lookup-results')).forEach(function(el) { el.style.display = 'none'; });
        }
    });
})();

// ── 7. ENSURE PRINT BUTTONS WORK ──────────────────────────────
// Some dashboards have print buttons that reference undeclared functions
window.printCertificate = window.printCertificate || function(studentId) {
    if (!studentId) { window.print(); return; }
    var w = window.open('', '_blank', 'width=800,height=600');
    w.document.write('<html><head><title>Print Certificate</title><link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">');
    w.document.write('<style>@media print{body{padding:20px;}.no-print{display:none!important;}}@page{size:landscape;margin:15mm;}</style></head><body>');
    w.document.write('<div class="p-5 text-center"><h2>Certificate</h2><p class="text-muted">Preview not available. Use browser print.</p>');
    w.document.write('<div class="text-center mt-3 no-print"><button class="btn btn-primary" onclick="window.print()">Print</button> <button class="btn btn-secondary" onclick="window.close()">Close</button></div>');
    w.document.write('</body></html>');
    w.document.close();
};
window.printTranscript = window.printTranscript || function(studentId) {
    window.printCertificate(studentId);
};
</script>
<?php if (function_exists('renderProfileScripts')) renderProfileScripts(); ?>

<!-- ── Session Idle Auto-Logout (10 min inactivity → sign out) ── -->
<script>
(function() {
    var IDLE_TIMEOUT_MS = 600000; // 10 minutes
    var idleTimer = null;

    function resetIdleTimer() {
        if (idleTimer) clearTimeout(idleTimer);
        idleTimer = setTimeout(function() {
            var f = document.createElement('form');
            f.method = 'POST';
            f.action = '<?= $rootPath ?>/logout.php';
            document.body.appendChild(f);
            f.submit();
        }, IDLE_TIMEOUT_MS);
    }

    var events = ['mousedown', 'mousemove', 'keydown', 'scroll', 'touchstart', 'click'];
    for (var i = 0; i < events.length; i++) {
        document.addEventListener(events[i], resetIdleTimer);
    }

    resetIdleTimer();
})();
</script>
<!-- ── End Session Idle Auto-Logout ── -->

<!-- ── Convert GET logout links to POST form submissions (CSRF protection) ── -->
<script>
(function() {
    document.addEventListener('click', function(e) {
        var link = e.target.closest('a[href*="logout"]');
        if (!link) return;
        e.preventDefault();
        var href = link.getAttribute('href');
        if (!href) return;
        var f = document.createElement('form');
        f.method = 'POST';
        f.action = href;
        document.body.appendChild(f);
        f.submit();
    });
})();
</script>

<?php if (!empty($_SESSION['logged_in']) && ($_SESSION['type'] ?? '') === 'staff'): ?>
<div class="isnm-loader" id="isnmLoader">
    <div class="loader-spinner"></div>
    <div class="loader-text">Please wait<span class="loader-dots"></span></div>
</div>

<div id="commFloatingBtn" class="comm-floating-btn" title="Send Department Communication" onclick="openCommunicationModal()">
    <i class="fas fa-envelope"></i>
</div>

<script>
function openCommunicationModal() {
    var el = document.getElementById('staffCommModal');
    if (el) {
        var modal = new bootstrap.Modal(el);
        modal.show();
    }
}
</script>
<?php endif; ?>

<style>
/* ── Global Loading Overlay ────────────────────────────────────── */
.isnm-loader {
    display: none;
    position: fixed;
    inset: 0;
    z-index: 99999;
    background: rgba(15,23,42,0.7);
    backdrop-filter: blur(4px);
    align-items: center;
    justify-content: center;
    flex-direction: column;
    gap: 16px;
}
.isnm-loader.active { display: flex; }
.isnm-loader .loader-spinner {
    width: 36px;
    height: 36px;
    border: 3px solid rgba(255,255,255,0.12);
    border-top-color: #fff;
    border-radius: 50%;
    animation: isnmSpin 0.5s linear infinite;
}
.isnm-loader .loader-text {
    color: #fff;
    font-size: 15px;
    font-weight: 500;
    font-family: 'Inter', sans-serif;
    letter-spacing: 0.3px;
}
.isnm-loader .loader-dots::after {
    content: '';
    animation: isnmDots 1.5s steps(4, end) infinite;
}
@keyframes isnmSpin { to { transform: rotate(360deg); } }
@keyframes isnmDots {
    0% { content: ''; }
    25% { content: '.'; }
    50% { content: '..'; }
    75% { content: '...'; }
    100% { content: ''; }
}

/* ── Communication Floating Button ─────────────────────────────── */
.comm-floating-btn {
    position: fixed; bottom: var(--fab-comm, 204px); right: 24px; z-index: 1059;
    width: 52px; height: 52px; border-radius: 50%;
    background: linear-gradient(135deg, #1a237e, #283593);
    color: #fff; display: flex; align-items: center; justify-content: center;
    cursor: pointer; box-shadow: 0 4px 15px rgba(26,35,126,.35);
    transition: transform .2s ease, box-shadow .2s ease;
    font-size: 22px;
}
.comm-floating-btn:hover {
    transform: scale(1.1);
    box-shadow: 0 6px 20px rgba(26,35,126,.5);
}
.comm-floating-btn:active { transform: scale(.95); }
@media (max-width: 480px) {
    .comm-floating-btn { width: 44px; height: 44px; font-size: 18px; bottom: calc(var(--fab-comm, 204px) - 8px); right: 16px; }
}
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
/* ============================================================
   PADDING / LAYOUT NORMALIZATION
   Ensures consistent spacing across all dashboards/pages.
    Overrides conflicting values from dashboard-style.css,
    dashboard-professional.css, etc. (merged into enterprise-layout.css)
   ============================================================ */
.main, .main-content, .dashboard-main,
.main-wrap, .page-wrap {
  margin-left: var(--sidebar-w, 270px);
  padding: 0;
  min-height: 100vh;
}
/* Single source of padding truth (content-section provides inner spacing) */
@media (max-width: 768px) {
  .main, .main-content, .dashboard-main,
  .main-wrap, .page-wrap {
    margin-left: 0 !important;
    padding: 16px !important;
  }
}
@media (max-width: 576px) {
  .main, .main-content, .dashboard-main,
  .main-wrap, .page-wrap {
    padding: 12px !important;
  }
}
.dashboard-content {
  padding: 0 !important;
}
.content-section, .dashboard-section.content-section {
  padding: 24px !important;
}
.card-body {
  padding: 20px !important;
}
/* Remove double padding from merged styles */
.main-content {
  padding: 0 !important;
}
/* Director-specific content wrappers */
.adm-content-wrap, .dg-content-wrap,
.acad-content-wrap, .ict-content-wrap,
.fin-content-wrap, .dash-content-wrap,
.content-wrapper, .page-content {
  padding: 20px !important;
}
/* Standardize all Bootstrap container padding */
.container-fluid, .container {
  padding-left: 0 !important;
  padding-right: 0 !important;
}
/* Row gap normalization */
.row.g-3, .row.g-2, .row.g-1 {
  --bs-gutter-x: 1rem;
  margin-left: 0;
  margin-right: 0;
}
.row.g-3 > [class*="col-"],
.row.g-2 > [class*="col-"],
.row.g-1 > [class*="col-"] {
  padding-left: calc(var(--bs-gutter-x) * 0.5);
  padding-right: calc(var(--bs-gutter-x) * 0.5);
}
/* Scard (styled card) spacing normalization */
.scard {
  margin-bottom: 16px;
}
.scb {
  padding: 16px !important;
}
.sch {
  padding: 12px 16px !important;
}
/* Page header spacing */
.page-header {
  padding-bottom: 12px !important;
  margin-bottom: 20px !important;
}
/* Filter group spacing */
.filter-group {
  gap: 8px;
  margin-bottom: 12px;
}
/* Remove duplicate margins/negative margins from rows */
.row {
  margin-left: 0;
  margin-right: 0;
}
/* Responsive adjustments */
@media (max-width: 768px) {
  .content-section, .dashboard-section.content-section {
    padding: 16px !important;
  }
  .card-body {
    padding: 14px !important;
  }
  .adm-content-wrap, .dg-content-wrap,
  .acad-content-wrap, .ict-content-wrap,
  .fin-content-wrap, .dash-content-wrap,
  .content-wrapper, .page-content {
    padding: 12px !important;
  }
  .scb {
    padding: 12px !important;
  }
}
@media print {
  .main, .main-content, .dashboard-main,
  .main-wrap, .page-wrap {
    margin-left: 0 !important;
    padding: 0 !important;
  }
  .content-section {
    padding: 0 !important;
  }
  .dep-approval-fab { display: none !important; }
}
/* ── Department Approval FAB (Floating Action Button) ── */
.dep-approval-fab {
  position: fixed; bottom: var(--fab-approval, 264px); right: 24px; z-index: 9999;
  width: 52px; height: 52px; border-radius: 50%;
  background: linear-gradient(135deg, #1a237e, #283593);
  color: #fff; border: none; box-shadow: 0 4px 16px rgba(26,35,126,0.35);
  display: flex; align-items: center; justify-content: center;
  font-size: 20px; cursor: pointer; transition: all 0.25s;
}
.dep-approval-fab:hover {
  transform: scale(1.08); box-shadow: 0 6px 24px rgba(26,35,126,0.45);
}
.dep-approval-fab-tooltip {
  position: absolute; right: 60px; top: 50%; transform: translateY(-50%);
  background: #1e293b; color: #fff; padding: 6px 12px; border-radius: 6px;
  font-size: 12px; white-space: nowrap; opacity: 0; pointer-events: none;
  transition: opacity 0.2s;
}
.dep-approval-fab:hover .dep-approval-fab-tooltip { opacity: 1; }
@media (max-width: 768px) {
  .dep-approval-fab { bottom: calc(var(--fab-approval, 264px) - 8px); right: 16px; width: 48px; height: 48px; font-size: 18px; }
}
@media (max-width: 480px) {
  .dep-approval-fab,
  .comm-floating-btn,
  .notif-bell,
  .sidebar-toggle,
  .ent-hamburger {
    width: 40px !important;
    height: 40px !important;
  }
}
</style>

<!-- Department Approval FAB (staff only) -->
<?php if (($GLOBALS['_dep_fab_rendered'] ?? false) === false && ($_SESSION['type'] ?? '') === 'staff'): ?>
<?php $GLOBALS['_dep_fab_rendered'] = true; ?>
<button class="dep-approval-fab" onclick="openDepartmentApprovalModal()" title="Submit for DG Approval" aria-label="Submit for DG Approval">
    <i class="fas fa-file-signature"></i>
    <span class="dep-approval-fab-tooltip">Submit for Approval</span>
</button>
<?php endif; ?>

<!-- Change Password Modal (shared) -->
<div class="modal fade" id="changePasswordModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fas fa-key me-2"></i>Change Password</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form id="changePasswordForm" onsubmit="return saveChangePassword()">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
        <input type="text" name="username" value="<?= htmlspecialchars($_SESSION['username'] ?? $_SESSION['full_name'] ?? '') ?>" autocomplete="username" style="display:none;">
        <div class="modal-body">
          <div id="cpwAlert" class="alert d-none"></div>
          <div class="mb-3">
            <label class="form-label">Current Password</label>
            <input type="password" class="form-control" name="current_password" autocomplete="current-password" required>
          </div>
          <div class="mb-3">
            <label class="form-label">New Password</label>
            <input type="password" class="form-control" name="new_password" autocomplete="new-password" required minlength="6">
          </div>
          <div class="mb-3">
            <label class="form-label">Confirm New Password</label>
            <input type="password" class="form-control" name="confirm_password" autocomplete="new-password" required minlength="6">
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary" id="cpwSubmit"><i class="fas fa-save me-1"></i> Update Password</button>
        </div>
      </form>
    </div>
  </div>
</div>
<script>
if (typeof openProfileModal !== 'function') {
  function openProfileModal() {
    var m = document.getElementById('profileSettingsModal');
    if (m) { bootstrap.Modal.getOrCreateInstance(m).show(); }
  }
}
function openChangePasswordModal() {
  var m = new bootstrap.Modal(document.getElementById('changePasswordModal'));
  m.show();
}
function saveChangePassword() {
  var f = document.getElementById('changePasswordForm');
  var d = new FormData(f);
  var a = document.getElementById('cpwAlert');
  var b = document.getElementById('cpwSubmit');
  a.classList.add('d-none');
  b.disabled = true;
  b.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Saving...';
  fetch('../auth-handler.php?action=change_password', { method:'POST', body:d })
    .then(function(r){ return r.text(); })
    .then(function(t){ return JSON.parse(t.replace(/^\uFEFF/, '')); })
    .then(function(j){
      if (j.success) {
        a.className = 'alert alert-success';
        a.innerHTML = '<i class="fas fa-check-circle me-1"></i> ' + (j.message || 'Password updated successfully.');
        a.classList.remove('d-none');
        setTimeout(function(){ location.reload(); }, 1500);
      } else {
        a.className = 'alert alert-danger';
        a.innerHTML = '<i class="fas fa-exclamation-circle me-1"></i> ' + (j.message || 'Failed to update password.');
        a.classList.remove('d-none');
        b.disabled = false;
        b.innerHTML = '<i class="fas fa-save me-1"></i> Update Password';
      }
    })
    .catch(function(){
      a.className = 'alert alert-danger';
      a.innerHTML = '<i class="fas fa-exclamation-circle me-1"></i> Network error. Please try again.';
      a.classList.remove('d-none');
      b.disabled = false;
      b.innerHTML = '<i class="fas fa-save me-1"></i> Update Password';
    });
  return false;
}
</script>
