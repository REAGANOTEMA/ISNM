<?php
/**
 * Professional Hierarchical Sidebar Navigation
 * Collapsible accordion, smooth animations, role-filtered, auto-expand active.
 * Standardized 6-section groups: MAIN, OPERATIONS, MANAGEMENT, REPORTS, COMMUNICATION, ACCOUNT.
 * Supports LEFT (default) and RIGHT positioning via $sidebarPosition variable.
 *
 * Usage before including:
 *   $sidebarPosition = 'right';  // or 'left' (default)
 *   include_once __DIR__ . '/includes/sidebar.php';
 */
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || !isset($_SESSION['type'])) {
    header('Location: ../index.php'); exit();
}

$sidebarPosition = $sidebarPosition ?? 'left';
$isRight = ($sidebarPosition === 'right');

$user_role = $_SESSION['role'];
$user_type = $_SESSION['type'];
$user_name = $_SESSION['full_name'] ?? ($_SESSION['first_name'] ?? 'User');
$user_id   = (int)($_SESSION['user_id'] ?? 0);

// Try dynamic sidebar first
$useDynamicSidebar = false;
if (file_exists(__DIR__ . '/dynamic_sidebar.php')) {
    require_once __DIR__ . '/dynamic_sidebar.php';
    if (function_exists('renderDynamicSidebar') && isset($_SESSION['role'])) {
        $useDynamicSidebar = true;
    }
}

// Load profile image
$profileImage = '../images/username.png';
$profileClickHandler = "if(typeof openProfileModal==='function')openProfileModal();";
if ($user_type === 'student') {
    $profileClickHandler = "window.location.href='../student-profile.php'";
    try {
        $rootPath_sb = '..';
        $studentConn = getStudentsConnection();
        if ($studentConn) {
            $q = $studentConn->prepare("SELECT profile_picture, passport_photo FROM students WHERE id = ?");
            $q->bind_param('i', $user_id);
            $q->execute();
            $photoRow = $q->get_result()->fetch_assoc();
            $q->close();
            $photoFile = '';
            if ($photoRow) {
                if (!empty($photoRow['profile_picture'])) $photoFile = $photoRow['profile_picture'];
                elseif (!empty($photoRow['passport_photo'])) $photoFile = $photoRow['passport_photo'];
            }
            if ($photoFile && file_exists(__DIR__ . '/../studentUploads/profile_images/' . $photoFile)) {
                $profileImage = $rootPath_sb . '/studentUploads/profile_images/' . $photoFile . '?v=' . time();
            } elseif ($photoFile && file_exists(__DIR__ . '/../' . $photoFile)) {
                $profileImage = $rootPath_sb . '/' . $photoFile . '?v=' . time();
            }
        }
    } catch (Exception $e) { error_log('sidebar render: ' . $e->getMessage()); }
} else {
    $profileFile = __DIR__ . '/profile_settings.php';
    if (file_exists($profileFile)) {
        include_once $profileFile;
        if (function_exists('getStaffProfileImageUrl')) {
            $profileImage = getStaffProfileImageUrl($user_id);
        }
    }
}

require_once __DIR__ . '/sidebar_groups.php';

$currentPage = basename($_SERVER['PHP_SELF']);

// Load sidebar groups for this role
$sidebarGroups = getSidebarGroups($user_role);

$activePage = $_GET['page'] ?? 'home';

// Icon color mapping for groups
$groupIconColors = [
    'MAIN'          => '#3b82f6',
    'OPERATIONS'    => '#059669',
    'MANAGEMENT'    => '#d97706',
    'REPORTS'       => '#7c3aed',
    'COMMUNICATION' => '#0891b2',
    'ACCOUNT'       => '#64748b',
];

$accordionMode = true;
$currentDir  = dirname($_SERVER['PHP_SELF']);
?>
<?php if ($isRight): ?>
<style>
/* ── RIGHT-SIDED SIDEBAR OVERRIDES ── */
.isnm-sidebar.sidebar {
  right: 0 !important;
  left: auto !important;
  border-left: 1px solid var(--border-color, #e5e7eb) !important;
  border-right: none !important;
}

/* Override ALL content container margins */
.main, .main-content, .dashboard-main, .main-wrap, .page-wrap,
.lec-content, .mat-content, .war-content, .hr-content,
.secu-content, .drv-content, .lib-content, .gld-content,
.nurs-content, .mid-content, .dg-content, .prin-content,
.dep-content, .sec-content, .ma, .cpt-content, .ict-content,
.fin-content, .acad-content, .admissions-content, .store-content,
.lab-content, .dg-topbar, .dashboard-content, .content-section,
.dashboard-section.content-section, .page-content {
  margin-left: 0 !important;
  margin-right: var(--sidebar-w, 270px) !important;
}

/* Toggle button inside sidebar - keep on left inside the right-side panel */
.isnm-sidebar .sidebar-collapse-btn {
  right: auto;
  left: 8px;
}

/* Right-side mobile floating toggle */
.isnm-sidebar-right-toggle {
  display: none;
  position: fixed;
  top: 10px;
  right: 10px;
  z-index: 9999;
  width: 40px;
  height: 40px;
  border-radius: 50%;
  background: var(--isnm-blue, #1a237e);
  color: #fff;
  border: none;
  font-size: 18px;
  cursor: pointer;
  box-shadow: 0 2px 8px rgba(0,0,0,0.2);
  align-items: center;
  justify-content: center;
  transition: opacity 0.2s;
}
.isnm-sidebar-right-toggle:hover {
  opacity: 0.85;
}

/* Right-side close button inside sidebar for mobile */
.isnm-sidebar .sidebar-mobile-close {
  display: none;
  position: absolute;
  top: 8px;
  right: 8px;
  width: 32px;
  height: 32px;
  border-radius: 50%;
  background: rgba(255,255,255,0.15);
  color: #fff;
  border: none;
  font-size: 16px;
  cursor: pointer;
  z-index: 10;
  align-items: center;
  justify-content: center;
  transition: background 0.2s;
}
.isnm-sidebar .sidebar-mobile-close:hover {
  background: rgba(255,255,255,0.25);
}

/* Mobile responsive: slide in from right */
@media (max-width: 768px) {
  .isnm-sidebar.sidebar {
    transform: translateX(100%) !important;
    width: 280px !important;
    position: fixed !important;
    height: 100vh !important;
    top: 0 !important;
    box-shadow: -4px 0 20px rgba(0,0,0,0.15) !important;
  }
  .isnm-sidebar.sidebar.open,
  .isnm-sidebar.sidebar.active,
  .isnm-sidebar.sidebar.mobile-show {
    transform: translateX(0) !important;
  }
  .main, .main-content, .dashboard-main, .main-wrap, .page-wrap,
  .lec-content, .mat-content, .war-content, .hr-content,
  .secu-content, .drv-content, .lib-content, .gld-content,
  .nurs-content, .mid-content, .dg-content, .prin-content,
  .dep-content, .sec-content, .ma, .cpt-content, .ict-content,
  .fin-content, .acad-content, .admissions-content, .store-content,
  .lab-content, .dg-topbar, .dashboard-content, .content-section,
  .dashboard-section.content-section, .page-content {
    margin-right: 0 !important;
  }
  .isnm-sidebar-right-toggle {
    display: flex !important;
  }
  .isnm-sidebar .sidebar-mobile-close {
    display: flex !important;
  }
}
</style>
<?php endif; ?>
<?php if ($useDynamicSidebar): ?>
<?php renderDynamicSidebar(); ?>
<?php else: ?>
<nav class="isnm-sidebar sidebar" id="isnmSidebar">
    <?php if ($isRight): ?>
    <button class="sidebar-mobile-close" id="sidebarMobileClose" aria-label="Close sidebar">
        <i class="fas fa-times"></i>
    </button>
    <?php endif; ?>
    <div class="sidebar-brand">
        <button class="sidebar-collapse-btn" id="sidebarCollapse" aria-label="Toggle sidebar">
            <i class="fas fa-bars"></i>
        </button>
        <img src="../images/school-logo.png" alt="ISNM" class="brand-logo">
        <div class="brand-text">
            <span class="brand-name">ISNM</span>
            <span class="brand-sub">Management System</span>
        </div>
    </div>

    <div class="sidebar-user" onclick="<?= $profileClickHandler ?>" style="cursor:pointer" title="Click to update profile">
        <div class="user-avatar-wrap">
            <img src="<?= $profileImage ?>" alt="" class="user-avatar">
            <span class="user-dot"></span>
        </div>
        <div class="user-meta">
            <div class="user-fullname"><?= htmlspecialchars($user_name) ?></div>
            <div class="user-rolename"><?= htmlspecialchars($user_role) ?></div>
        </div>
    </div>

    <div class="sidebar-menu" id="sidebarMenu">
        <?php foreach ($sidebarGroups as $groupName => $items):
            $groupIdSafe = preg_replace('/[^a-z0-9]/', '', strtolower($groupName));
            $groupColor = $groupIconColors[$groupName] ?? '#64748b';
            $hasActiveChild = false;
            foreach ($items as $item) {
                if (isset($item['page']) && $item['page'] === $activePage) { $hasActiveChild = true; break; }
            }
        ?>
        <div class="menu-group <?= $hasActiveChild ? 'expanded' : '' ?>" data-group="<?= $groupIdSafe ?>">
            <div class="menu-group-header" data-target="<?= $groupIdSafe ?>">
                <span class="menu-icon"><i class="<?= htmlspecialchars($items[0]['icon'] ?? 'fas fa-link') ?>" style="color:<?= $groupColor ?>"></i></span>
                <span class="menu-label"><?= htmlspecialchars($groupName) ?></span>
                <span class="menu-chevron"><i class="fas fa-chevron-down"></i></span>
            </div>
            <div class="menu-children" id="childGroup-<?= $groupIdSafe ?>" style="<?= $hasActiveChild ? '' : 'max-height:0' ?>">
                <div class="menu-children-inner">
                    <?php foreach ($items as $item):
                        $isActive = (isset($item['page']) && $item['page'] === $activePage) || (isset($item['label']) && strtolower($item['label']) === $activePage);
                        if (isset($item['href'])): ?>
                        <a href="<?= htmlspecialchars($item['href']) ?>" class="child-link <?= $isActive ? 'active' : '' ?>">
                            <span class="child-bullet" style="<?= $isActive ? 'background:' . $groupColor : '' ?>"></span>
                            <span class="child-label"><?= htmlspecialchars($item['label']) ?></span>
                        </a>
                        <?php else:
                        $linkTarget = $currentPage . '?page=' . htmlspecialchars($item['page']);
                        ?>
                        <a href="<?= $linkTarget ?>" class="child-link <?= $isActive ? 'active' : '' ?>">
                            <span class="child-bullet" style="<?= $isActive ? 'background:' . $groupColor : '' ?>"></span>
                            <span class="child-label"><?= htmlspecialchars($item['label']) ?></span>
                        </a>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</nav>
<?php if ($isRight): ?>
<button class="isnm-sidebar-right-toggle" id="sidebarRightToggle" aria-label="Open sidebar">
    <i class="fas fa-bars"></i>
</button>
<?php endif; ?>
<?php endif; ?>

<?php if (!$useDynamicSidebar): ?>
<div class="sidebar-overlay" id="sidebarOverlay"></div>
<script>
(function() {
    // ── Collapsible Groups ──
    document.querySelectorAll('.menu-group-header[data-target]').forEach(function(header) {
        header.addEventListener('click', function(e) {
            var group = this.closest('.menu-group');
            if (!group) return;
            var targetId = this.getAttribute('data-target');
            var children = document.getElementById('childGroup-' + targetId);
            if (!children) return;
            var isExpanded = group.classList.contains('expanded');
            if (<?= json_encode($accordionMode) ?>) {
                document.querySelectorAll('.menu-group.expanded').forEach(function(g) {
                    if (g !== group && g.closest('.sidebar-menu') === group.closest('.sidebar-menu')) {
                        g.classList.remove('expanded');
                        var c = g.querySelector('.menu-children');
                        if (c) c.style.maxHeight = '0';
                    }
                });
            }
            if (isExpanded) {
                group.classList.remove('expanded');
                children.style.maxHeight = '0';
            } else {
                group.classList.add('expanded');
                children.style.maxHeight = children.scrollHeight + 'px';
            }
        });
    });

    // ── Sidebar collapse toggle (desktop) ──
    var collapseBtn = document.getElementById('sidebarCollapse');
    if (collapseBtn) {
        collapseBtn.addEventListener('click', function() {
            document.getElementById('isnmSidebar').classList.toggle('collapsed');
        });
    }

    // ── Right-side mobile toggle ──
    var rightToggle = document.getElementById('sidebarRightToggle');
    if (rightToggle) {
        rightToggle.addEventListener('click', function() {
            var sidebar = document.getElementById('isnmSidebar');
            sidebar.classList.toggle('open');
            var overlay = document.getElementById('sidebarOverlay');
            if (overlay) overlay.classList.toggle('open');
        });
    }

    // ── Right-side mobile close ──
    var mobileClose = document.getElementById('sidebarMobileClose');
    if (mobileClose) {
        mobileClose.addEventListener('click', function() {
            document.getElementById('isnmSidebar').classList.remove('open');
            var overlay = document.getElementById('sidebarOverlay');
            if (overlay) overlay.classList.remove('open');
        });
    }

    // ── Auto-expand active group ──
    var activeLink = document.querySelector('.child-link.active');
    if (activeLink) {
        var group = activeLink.closest('.menu-group');
        if (group && !group.classList.contains('expanded')) {
            group.classList.add('expanded');
            var children = group.querySelector('.menu-children');
            if (children) children.style.maxHeight = children.scrollHeight + 'px';
        }
    }

    // ── Responsive: close sidebar on outside click (mobile) ──
    document.addEventListener('click', function(e) {
        var sidebar = document.getElementById('isnmSidebar');
        if (!sidebar || window.innerWidth > 768) return;
        if (!sidebar.contains(e.target) && sidebar.classList.contains('open')) {
            sidebar.classList.remove('open');
        }
    });

    // ── Overlay click closes sidebar ──
    var overlay = document.getElementById('sidebarOverlay');
    if (overlay) {
        overlay.addEventListener('click', function() {
            var sidebar = document.getElementById('isnmSidebar');
            if (sidebar) sidebar.classList.remove('open');
        });
    }

    // ── Mark active section on page load ──
    (function initPage() {
        var params = new URLSearchParams(window.location.search);
        var page = params.get('page') || 'home';
        document.querySelectorAll('.child-link').forEach(function(link) {
            link.classList.remove('active');
            if (link.getAttribute('href') && link.getAttribute('href').indexOf('page=' + page) !== -1) {
                link.classList.add('active');
            }
        });
    })();
})();
</script>
<?php endif; ?>
<?php
$sidebarRendered = true;
require_once __DIR__ . '/settings_modal.php';
require_once __DIR__ . '/enterprise_control_panel.php';
?>
