<?php
/**
 * Professional Hierarchical Sidebar Navigation
 * Collapsible accordion, smooth animations, role-filtered, auto-expand active.
 */
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || !isset($_SESSION['type'])) {
    header('Location: ../index.php'); exit();
}

$user_role = $_SESSION['role'];
$user_type = $_SESSION['type'];
$user_name = $_SESSION['full_name'] ?? ($_SESSION['first_name'] ?? 'User');
$user_id   = (int)($_SESSION['user_id'] ?? 0);

// Load profile image from staff_profiles
$profileImage = '../images/username.png';
if ($user_id) {
    $profileFile = __DIR__ . '/profile_settings.php';
    if (file_exists($profileFile)) {
        include_once $profileFile;
        if (function_exists('getStaffProfileImageUrl')) {
            $profileImage = getStaffProfileImageUrl($user_id);
        }
    }
}

require_once __DIR__ . '/module_config.php';

$modules = getFilteredModules($user_role);

// Config: set to true to allow only one parent open at a time
$accordionMode = true;

// Detect current page for active highlighting
$currentPage = basename($_SERVER['PHP_SELF']);
$currentDir  = dirname($_SERVER['PHP_SELF']);
?>
<nav class="isnm-sidebar" id="isnmSidebar">
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

    <div class="sidebar-user" onclick="if(typeof openProfileModal==='function')openProfileModal();" style="cursor:pointer" title="Click to update profile">
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
        <div class="menu-divider"><span>Modules</span></div>

        <?php foreach ($modules as $parent):
            $parentId = preg_replace('/[^a-z0-9]/', '', strtolower($parent['title']));
            $hasChildren = !empty($parent['children']);
            $hasActiveChild = false;
            if ($hasChildren) {
                foreach ($parent['children'] as $child) {
                    if (basename($child['route']) === $currentPage) {
                        $hasActiveChild = true;
                        break;
                    }
                }
            }
            $isStudentMgmt = stripos($parent['title'], 'Student Management') !== false;
        ?>
        <?php if ($isStudentMgmt): ?><div id="hiddenStudentMgmt" style="display:none"><?php endif; ?>
        <div class="menu-group <?= $hasActiveChild ? 'expanded' : '' ?>" data-group="<?= $parentId ?>">
            <div class="menu-group-header" data-target="<?= $parentId ?>">
                <span class="menu-icon"><i class="<?= $parent['icon'] ?>"></i></span>
                <span class="menu-label"><?= htmlspecialchars($parent['title']) ?></span>
                <?php if ($hasChildren): ?>
                <span class="menu-chevron"><i class="fas fa-chevron-down"></i></span>
                <?php endif; ?>
            </div>
            <?php if ($hasChildren): ?>
            <div class="menu-children" id="childGroup-<?= $parentId ?>" style="<?= $hasActiveChild ? '' : 'max-height:0;' ?>">
                <div class="menu-children-inner">
                    <?php foreach ($parent['children'] as $child):
                        $childPage = basename($child['route']);
                        $isActive = ($childPage === $currentPage);
                    ?>
                    <a href="<?= htmlspecialchars($child['route']) ?>" class="child-link <?= $isActive ? 'active' : '' ?>">
                        <span class="child-bullet"></span>
                        <span class="child-label"><?= htmlspecialchars($child['title']) ?></span>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
        <?php if ($isStudentMgmt): ?></div><?php endif; ?>
        <?php endforeach; ?>
    </div>

    <div class="sidebar-extra">
        <a href="../student-directory.php" class="extra-link"><i class="fas fa-address-book"></i> Directory</a>
        <a href="../store_request.php" class="extra-link"><i class="fas fa-shopping-cart"></i> Store Request</a>
        <a href="../dashboards/recycle_bin.php" class="extra-link"><i class="fas fa-trash-alt"></i> Recycle Bin</a>
        <a href="director-general.php#alerts" class="extra-link"><i class="fas fa-bell"></i> <span>Alerts</span><span id="alertBadgeSidebar" class="badge bg-danger ms-auto" style="font-size:8px;display:none">0</span></a>
        <a href="director-general.php#audit" class="extra-link"><i class="fas fa-history"></i> <span>Audit Trail</span></a>
        <a href="director-general.php#approvals" class="extra-link"><i class="fas fa-check-double"></i> <span>Approvals</span><span id="approvalBadgeSidebar" class="badge bg-warning ms-auto" style="font-size:8px;display:none">0</span></a>
        <a href="#" class="extra-link settings-trigger" data-bs-toggle="modal" data-bs-target="#settingsModal">
            <i class="fas fa-cog"></i> Settings
        </a>
        <a href="#" class="extra-link theme-trigger" onclick="event.preventDefault();if(typeof openThemeModal==='function')openThemeModal();">
            <i class="fas fa-palette"></i> <span>Theme</span>
            <span class="theme-picker-name ms-auto"><span class="theme-current-name">Default</span></span>
        </a>
        <a href="#" class="extra-link" id="toggleStudentMgmt">
            <i class="fas fa-user-graduate"></i> <span>Student Management</span>
            <i class="fas fa-chevron-down smgmt-chevron ms-auto"></i>
        </a>
    </div>
    <script>
    (function() {
        // Fetch alert and approval counts for sidebar badges
        function updateBadges() {
            var xhr = new XMLHttpRequest();
            xhr.open('GET', '../ajax/get_counts.php', true);
            xhr.onload = function() {
                if (xhr.status === 200) {
                    try {
                        var data = JSON.parse(xhr.responseText);
                        var alertBadge = document.getElementById('alertBadgeSidebar');
                        var approvalBadge = document.getElementById('approvalBadgeSidebar');
                        if (alertBadge && data.critical_alerts > 0) {
                            alertBadge.textContent = data.critical_alerts;
                            alertBadge.style.display = 'inline';
                        }
                        if (approvalBadge && data.pending_approvals > 0) {
                            approvalBadge.textContent = data.pending_approvals;
                            approvalBadge.style.display = 'inline';
                        }
                    } catch(e) {}
                }
            };
            xhr.send();
        }
        updateBadges();
        setInterval(updateBadges, 60000);
    })();
    </script>

    <div class="sidebar-footer">
        <a href="../auth-handler.php?action=logout" class="logout-btn">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
        <div class="footer-meta">
            <span>v2.1.0</span>
            <span>&copy; 2026 ISNM</span>
        </div>
    </div>
</nav>

<!-- Mobile overlay + toggle -->
<div class="isnm-overlay" id="isnmOverlay"></div>
<button class="isnm-mobile-toggle" id="isnmMobileToggle" aria-label="Toggle menu">
    <span></span><span></span><span></span>
</button>

<style>
/* ── Reset & Variables ── */
:root {
    --sidebar-w: 270px;
    --sidebar-bg: #0f172a;
    --sidebar-hover: #1e293b;
    --sidebar-active: #2563eb;
    --sidebar-text: #94a3b8;
    --sidebar-text-active: #ffffff;
    --sidebar-accent: #3b82f6;
    --sidebar-border: #1e293b;
    --sidebar-radius: 8px;
    --sidebar-transition: 0.25s cubic-bezier(0.4, 0, 0.2, 1);
}

/* ── Sidebar Base ── */
.isnm-sidebar {
    position: fixed;
    top: 0;
    left: 0;
    width: var(--sidebar-w);
    height: 100vh;
    background: var(--sidebar-bg);
    color: var(--sidebar-text);
    display: flex;
    flex-direction: column;
    z-index: 1050;
    overflow: hidden;
    box-shadow: 0 0 30px rgba(0,0,0,0.3);
    transition: transform var(--sidebar-transition);
}

/* ── Brand ── */
.sidebar-brand {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 18px 20px 14px;
    border-bottom: 1px solid var(--sidebar-border);
    flex-shrink: 0;
}
.brand-logo {
    width: 42px;
    height: 42px;
    border-radius: 10px;
    object-fit: cover;
    flex-shrink: 0;
}
.brand-text { display: flex; flex-direction: column; min-width: 0; }
.brand-name { font-size: 18px; font-weight: 700; color: #fff; line-height: 1.2; }
.brand-sub { font-size: 11px; color: var(--sidebar-text); text-transform: uppercase; letter-spacing: 0.5px; }
.sidebar-collapse-btn {
    background: none; border: none; color: var(--sidebar-text); font-size: 16px;
    cursor: pointer; padding: 4px 6px; border-radius: 6px; display: none;
}
.sidebar-collapse-btn:hover { background: var(--sidebar-hover); color: #fff; }

/* ── User ── */
.sidebar-user {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px 20px;
    border-bottom: 1px solid var(--sidebar-border);
    flex-shrink: 0;
}
.user-avatar-wrap { position: relative; flex-shrink: 0; }
.user-avatar { width: 36px; height: 36px; border-radius: 50%; object-fit: cover; border: 2px solid rgba(255,255,255,0.1); }
.user-dot {
    position: absolute; bottom: -1px; right: -1px;
    width: 10px; height: 10px; border-radius: 50%;
    background: #22c55e; border: 2px solid var(--sidebar-bg);
}
.user-meta { min-width: 0; }
.user-fullname { font-size: 13px; font-weight: 600; color: #fff; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.user-rolename { font-size: 11px; color: var(--sidebar-text); text-transform: capitalize; }

/* ── Scrollable Menu ── */
.sidebar-menu {
    flex: 1;
    overflow-y: auto;
    overflow-x: hidden;
    padding: 8px 0;
}
.sidebar-menu::-webkit-scrollbar { width: 3px; }
.sidebar-menu::-webkit-scrollbar-track { background: transparent; }
.sidebar-menu::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.15); border-radius: 10px; }

/* ── Divider ── */
.menu-divider {
    padding: 16px 20px 6px;
    font-size: 10px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 1px;
    color: rgba(255,255,255,0.25);
}
.menu-divider span { display: block; }

/* ── Menu Items ── */
.menu-item { padding: 0 8px; margin-bottom: 1px; }

.menu-link {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px 14px;
    color: var(--sidebar-text);
    text-decoration: none;
    border-radius: var(--sidebar-radius);
    font-size: 14px;
    font-weight: 450;
    transition: all var(--sidebar-transition);
    cursor: pointer;
}
.menu-link:hover {
    background: var(--sidebar-hover);
    color: var(--sidebar-text-active);
}
.menu-link.active {
    background: var(--sidebar-active);
    color: #fff;
    font-weight: 500;
    box-shadow: 0 4px 12px rgba(37,99,235,0.3);
}
.menu-icon {
    width: 20px;
    text-align: center;
    font-size: 14px;
    flex-shrink: 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}
.menu-label {
    flex: 1;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* ── Parent Group Header ── */
.menu-group { padding: 0 8px; margin-bottom: 1px; }
.menu-group-header {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px 14px;
    color: var(--sidebar-text);
    border-radius: var(--sidebar-radius);
    font-size: 14px;
    font-weight: 450;
    cursor: pointer;
    transition: all var(--sidebar-transition);
    user-select: none;
}
.menu-group-header:hover {
    background: var(--sidebar-hover);
    color: var(--sidebar-text-active);
}
.menu-group.expanded > .menu-group-header {
    color: var(--sidebar-text-active);
    background: rgba(255,255,255,0.04);
}
.menu-chevron {
    font-size: 11px;
    transition: transform var(--sidebar-transition);
    flex-shrink: 0;
}
.menu-group.expanded .menu-chevron {
    transform: rotate(180deg);
}

/* ── Children (animated) ── */
.menu-children {
    max-height: 0;
    overflow: hidden;
    transition: max-height 0.35s cubic-bezier(0.4, 0, 0.2, 1);
}
.menu-children-inner {
    padding: 2px 0 4px 32px;
}
.child-link {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 8px 14px;
    color: var(--sidebar-text);
    text-decoration: none;
    border-radius: 6px;
    font-size: 13px;
    font-weight: 400;
    transition: all var(--sidebar-transition);
    position: relative;
}
.child-link:hover {
    color: var(--sidebar-text-active);
    background: rgba(255,255,255,0.05);
}
.child-link.active {
    color: var(--sidebar-text-active);
    background: rgba(59,130,246,0.15);
    font-weight: 500;
}
.child-link.active::before {
    content: '';
    position: absolute;
    left: -6px;
    top: 50%;
    transform: translateY(-50%);
    width: 3px;
    height: 18px;
    background: var(--sidebar-accent);
    border-radius: 3px;
}
.child-bullet {
    width: 5px;
    height: 5px;
    border-radius: 50%;
    background: currentColor;
    opacity: 0.4;
    flex-shrink: 0;
    transition: all var(--sidebar-transition);
}
.child-link.active .child-bullet {
    opacity: 1;
    background: var(--sidebar-accent);
    box-shadow: 0 0 6px rgba(59,130,246,0.5);
}
.child-link:hover .child-bullet {
    opacity: 0.7;
}
.child-label {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* ── Extra Links ── */
.sidebar-extra {
    padding: 8px;
    border-top: 1px solid var(--sidebar-border);
    flex-shrink: 0;
    display: flex;
    flex-direction: column;
    gap: 1px;
}
.extra-link {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 9px 14px;
    color: var(--sidebar-text);
    text-decoration: none;
    border-radius: var(--sidebar-radius);
    font-size: 13px;
    transition: all var(--sidebar-transition);
}
.extra-link:hover {
    background: var(--sidebar-hover);
    color: var(--sidebar-text-active);
}
.extra-link i { width: 18px; text-align: center; font-size: 13px; }

/* ── Footer ── */
.sidebar-footer {
    padding: 10px 12px 14px;
    border-top: 1px solid var(--sidebar-border);
    flex-shrink: 0;
}
.logout-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 10px;
    color: #f87171;
    text-decoration: none;
    border-radius: var(--sidebar-radius);
    font-size: 13px;
    font-weight: 500;
    transition: all var(--sidebar-transition);
    border: 1px solid rgba(248,113,113,0.15);
}
.logout-btn:hover {
    background: rgba(248,113,113,0.1);
    border-color: rgba(248,113,113,0.3);
}
.footer-meta {
    display: flex;
    justify-content: space-between;
    padding: 8px 4px 0;
    font-size: 10px;
    color: rgba(255,255,255,0.2);
}

/* ── Mobile Overlay ── */
.isnm-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.5);
    z-index: 1040;
    backdrop-filter: blur(2px);
}

/* ── Mobile Toggle ── */
.isnm-mobile-toggle {
    display: none;
    position: fixed;
    top: 14px;
    left: 14px;
    z-index: 1060;
    background: var(--sidebar-bg);
    border: 1px solid var(--sidebar-border);
    border-radius: 10px;
    padding: 10px 11px;
    cursor: pointer;
    box-shadow: 0 4px 16px rgba(0,0,0,0.2);
    flex-direction: column;
    gap: 4px;
}
.isnm-mobile-toggle span {
    display: block;
    width: 18px;
    height: 2px;
    background: #fff;
    border-radius: 2px;
    transition: all 0.3s ease;
}
.isnm-mobile-toggle.active span:nth-child(1) { transform: rotate(45deg) translate(4px, 4px); }
.isnm-mobile-toggle.active span:nth-child(2) { opacity: 0; }
.isnm-mobile-toggle.active span:nth-child(3) { transform: rotate(-45deg) translate(4px, -4px); }

/* ── Responsive ── */
@media (max-width: 768px) {
    .isnm-sidebar {
        transform: translateX(-100%);
    }
    .isnm-sidebar.open {
        transform: translateX(0);
    }
    .isnm-mobile-toggle {
        display: flex;
    }
    .isnm-overlay.active {
        display: block;
    }
    .sidebar-collapse-btn { display: none; }
}
@media (min-width: 769px) {
    .isnm-sidebar { transform: translateX(0); }
    .isnm-sidebar.collapsed { width: 64px; }
    .isnm-sidebar.collapsed .brand-text,
    .isnm-sidebar.collapsed .user-meta,
    .isnm-sidebar.collapsed .menu-label,
    .isnm-sidebar.collapsed .menu-chevron,
    .isnm-sidebar.collapsed .menu-divider span,
    .isnm-sidebar.collapsed .menu-children,
    .isnm-sidebar.collapsed .extra-link span,
    .isnm-sidebar.collapsed .logout-btn span,
    .isnm-sidebar.collapsed .footer-meta { display: none; }
    .isnm-sidebar.collapsed .sidebar-extra { padding: 4px; align-items: center; }
    .isnm-sidebar.collapsed .menu-group-header,
    .isnm-sidebar.collapsed .menu-link { justify-content: center; padding: 10px 0; }
    .isnm-sidebar.collapsed .menu-icon,
    .isnm-sidebar.collapsed .extra-link i { width: auto; font-size: 16px; margin: 0; }
    .isnm-sidebar.collapsed .extra-link { justify-content: center; padding: 8px 0; width: 48px; margin: 0 auto; }
    .isnm-sidebar.collapsed .logout-btn { justify-content: center; }
    .isnm-sidebar.collapsed .sidebar-brand { justify-content: center; padding: 18px 8px 14px; }
    .isnm-sidebar.collapsed .sidebar-user { justify-content: center; padding: 14px 8px; }
    .isnm-sidebar.collapsed .brand-logo { margin: 0; }
    .isnm-sidebar.collapsed .smgmt-chevron { display: none; }
    .sidebar-collapse-btn { display: block; }
}
.smgmt-chevron { transition: transform .25s ease; font-size: 10px; }
.smgmt-chevron.open { transform: rotate(180deg); }
</style>

<script>
(function() {
    const SIDEBAR = document.getElementById('isnmSidebar');
    const OVERLAY = document.getElementById('isnmOverlay');
    const MOBILE_TOGGLE = document.getElementById('isnmMobileToggle');
    const ACCORDION_MODE = <?= $accordionMode ? 'true' : 'false' ?>;
    const STORAGE_KEY = 'isnm_sidebar_v2';

    // ── Restore expanded state ──
    function restoreState() {
        try {
            const saved = JSON.parse(localStorage.getItem(STORAGE_KEY) || '{}');
            if (saved.expanded) {
                saved.expanded.forEach(function(id) {
                    const group = document.querySelector('.menu-group[data-group="' + id + '"]');
                    const children = document.getElementById('childGroup-' + id);
                    if (group && children) {
                        group.classList.add('expanded');
                        children.style.maxHeight = children.scrollHeight + 'px';
                    }
                });
            }
        } catch(e) {}
    }
    restoreState();

    // ── Save expanded state ──
    function saveState() {
        var expanded = [];
        document.querySelectorAll('.menu-group.expanded').forEach(function(g) {
            expanded.push(g.dataset.group);
        });
        try {
            localStorage.setItem(STORAGE_KEY, JSON.stringify({ expanded: expanded }));
        } catch(e) {}
    }

    // ── Toggle children with smooth animation ──
    function toggleGroup(header) {
        var group = header.closest('.menu-group');
        if (!group) return;
        var targetId = header.dataset.target;
        var children = document.getElementById('childGroup-' + targetId);
        if (!children) return;

        var isExpanding = !group.classList.contains('expanded');

        // Accordion: close other groups
        if (isExpanding && ACCORDION_MODE) {
            document.querySelectorAll('.menu-group.expanded').forEach(function(other) {
                if (other !== group) {
                    var otherChildren = document.getElementById('childGroup-' + other.dataset.group);
                    other.classList.remove('expanded');
                    if (otherChildren) otherChildren.style.maxHeight = '0';
                }
            });
        }

        if (isExpanding) {
            group.classList.add('expanded');
            children.style.maxHeight = children.scrollHeight + 'px';
        } else {
            group.classList.remove('expanded');
            children.style.maxHeight = '0';
        }

        saveState();
    }

    // ── Attach click listeners to group headers ──
    document.querySelectorAll('.menu-group-header').forEach(function(header) {
        header.addEventListener('click', function(e) {
            e.preventDefault();
            toggleGroup(this);
        });
    });

    // ── Desktop collapse toggle ──
    document.getElementById('sidebarCollapse').addEventListener('click', function() {
        SIDEBAR.classList.toggle('collapsed');
        try {
            localStorage.setItem(STORAGE_KEY + '_collapsed', SIDEBAR.classList.contains('collapsed'));
        } catch(e) {}
    });
    // Restore collapsed state
    try {
        if (localStorage.getItem(STORAGE_KEY + '_collapsed') === 'true' && window.innerWidth > 768) {
            SIDEBAR.classList.add('collapsed');
        }
    } catch(e) {}

    // ── Mobile toggle ──
    if (MOBILE_TOGGLE) {
        MOBILE_TOGGLE.addEventListener('click', function() {
            SIDEBAR.classList.toggle('open');
            OVERLAY.classList.toggle('active');
            this.classList.toggle('active');
            document.body.style.overflow = SIDEBAR.classList.contains('open') ? 'hidden' : '';
        });
    }

    function closeMobile() {
        SIDEBAR.classList.remove('open');
        OVERLAY.classList.remove('active');
        if (MOBILE_TOGGLE) MOBILE_TOGGLE.classList.remove('active');
        document.body.style.overflow = '';
    }

    if (OVERLAY) OVERLAY.addEventListener('click', closeMobile);

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && SIDEBAR.classList.contains('open')) closeMobile();
    });

    // ── Close mobile on child link click ──
    document.querySelectorAll('.child-link').forEach(function(link) {
        link.addEventListener('click', function() {
            if (window.innerWidth <= 768) closeMobile();
        });
    });

    // ── Re-calculate max-height on window resize (for open groups) ──
    var resizeTimer;
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function() {
            document.querySelectorAll('.menu-group.expanded').forEach(function(g) {
                var children = document.getElementById('childGroup-' + g.dataset.group);
                if (children) children.style.maxHeight = children.scrollHeight + 'px';
            });
        }, 200);
    });

    // ── Student Management toggle ──
    var smgmtToggle = document.getElementById('toggleStudentMgmt');
    var smgmtBlock = document.getElementById('hiddenStudentMgmt');
    if (smgmtToggle && smgmtBlock) {
        smgmtToggle.addEventListener('click', function(e) {
            e.preventDefault();
            var isHidden = smgmtBlock.style.display === 'none';
            smgmtBlock.style.display = isHidden ? '' : 'none';
            smgmtToggle.querySelector('.smgmt-chevron').classList.toggle('open', isHidden);
        });
    }
})();
</script>
<?php
$sidebarRendered = true;

// Include universal settings modal
require_once __DIR__ . '/settings_modal.php';
?>
