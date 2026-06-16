<?php
/**
 * Hierarchical Dashboard Sidebar Navigation
 * Collapsible parent/child modules with role-based filtering.
 */
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || !isset($_SESSION['type'])) {
    header('Location: ../index.php'); exit();
}

$user_role = $_SESSION['role'];
$user_type = $_SESSION['type'];
$user_name = $_SESSION['full_name'] ?? ($_SESSION['first_name'] ?? 'User');
$user_id   = $_SESSION['user_id'];

require_once __DIR__ . '/module_config.php';

$modules = getFilteredModules($user_role);

// Detect current page for active highlighting
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!-- Responsive Dashboard Navigation -->
<div class="dashboard-nav-container">
    <button class="mobile-menu-toggle" id="mobileMenuToggle" aria-label="Toggle menu">
        <span class="hamburger-line"></span>
        <span class="hamburger-line"></span>
        <span class="hamburger-line"></span>
    </button>

    <nav class="dashboard-sidebar" id="dashboardSidebar">
        <div class="sidebar-header">
            <div class="sidebar-logo">
                <img src="../images/school-logo.png" alt="ISNM Logo" class="sidebar-logo-img">
                <div class="sidebar-title">
                    <h3>ISNM</h3>
                    <span class="sidebar-subtitle">Management System</span>
                </div>
            </div>
            <div class="sidebar-user">
                <div class="user-avatar">
                    <img src="../images/default-avatar.png" alt="User Avatar" class="user-avatar-img">
                    <span class="user-status online"></span>
                </div>
                <div class="user-info">
                    <div class="user-name"><?= htmlspecialchars($user_name) ?></div>
                    <div class="user-role"><?= htmlspecialchars($user_role) ?></div>
                </div>
                <button class="sidebar-close" id="sidebarClose" aria-label="Close sidebar">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>

        <div class="sidebar-nav">
            <ul class="nav-list">
                <!-- Dashboard Home -->
                <li class="nav-item">
                    <a href="index.php" class="nav-link <?= ($currentPage === 'index.php' || $currentPage === '') ? 'active' : '' ?>" data-route="index">
                        <div class="nav-icon"><i class="fas fa-tachometer-alt"></i></div>
                        <span class="nav-text">Dashboard</span>
                    </a>
                </li>

                <?php foreach ($modules as $parent): ?>
                <?php
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
                    $expanded = $hasActiveChild ? 'expanded' : '';
                ?>
                <li class="nav-item nav-parent <?= $expanded ?>">
                    <a href="#" class="nav-link parent-toggle" data-parent="<?= $parentId ?>" onclick="return false;">
                        <div class="nav-icon"><i class="<?= $parent['icon'] ?>"></i></div>
                        <span class="nav-text"><?= htmlspecialchars($parent['title']) ?></span>
                        <?php if ($hasChildren): ?>
                        <i class="fas fa-chevron-down nav-arrow"></i>
                        <?php endif; ?>
                    </a>
                    <?php if ($hasChildren): ?>
                    <ul class="nav-children" id="children-<?= $parentId ?>" style="<?= $expanded ? '' : 'display:none;' ?>">
                        <?php foreach ($parent['children'] as $child): ?>
                        <?php
                            $childPage = basename($child['route']);
                            $isActive = ($childPage === $currentPage);
                        ?>
                        <li class="nav-item nav-child-item <?= $isActive ? 'active' : '' ?>">
                            <a href="<?= htmlspecialchars($child['route']) ?>" class="nav-link child-link <?= $isActive ? 'active' : '' ?>">
                                <div class="nav-icon"><i class="fas fa-circle" style="font-size:6px;"></i></div>
                                <span class="nav-text"><?= htmlspecialchars($child['title']) ?></span>
                            </a>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    <?php endif; ?>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>

        <div class="sidebar-footer">
            <div class="d-flex justify-content-center flex-wrap mb-2">
                <a href="../student-directory.php" class="btn btn-sm btn-outline-info me-1"><i class="fas fa-address-book me-1"></i>Directory</a>
                <a href="../store_request.php" class="btn btn-sm btn-outline-warning me-1"><i class="fas fa-shopping-cart me-1"></i>Store</a>
            </div>
            <div class="footer-info">
                <div class="version">v2.0.1</div>
                <div class="copyright">&copy; 2026 ISNM</div>
                <a href="../auth-handler.php?action=logout" class="btn btn-danger btn-sm mt-2 w-100">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
    </nav>

    <div class="sidebar-overlay" id="sidebarOverlay"></div>
</div>

<style>
.dashboard-nav-container { position: fixed; top: 0; left: 0; z-index: 1000; }
.mobile-menu-toggle {
    display: none; position: fixed; top: 20px; left: 20px; z-index: 1001;
    background: linear-gradient(135deg, #667eea, #764ba2); border: none; border-radius: 8px;
    padding: 12px; cursor: pointer; box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}
.mobile-menu-toggle:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(0,0,0,0.2); }
.hamburger-line { display: block; width: 24px; height: 3px; background: white; margin: 5px 0; border-radius: 2px; }
.mobile-menu-toggle.active .hamburger-line:nth-child(1) { transform: rotate(45deg) translate(5px,5px); }
.mobile-menu-toggle.active .hamburger-line:nth-child(2) { opacity: 0; }
.mobile-menu-toggle.active .hamburger-line:nth-child(3) { transform: rotate(-45deg) translate(7px,-6px); }

.dashboard-sidebar {
    position: fixed; top: 0; left: 0;     width: 260px; height: 100vh;
    background: linear-gradient(180deg, #1a237e 0%, #283593 50%, #3949ab 100%);
    color: white; overflow-y: auto; overflow-x: hidden;
    transition: transform 0.3s ease; z-index: 1000;
    box-shadow: 2px 0 15px rgba(0,0,0,0.1);
}
.sidebar-header { padding: 20px; border-bottom: 1px solid rgba(255,255,255,0.1); }
.sidebar-logo { display: flex; align-items: center; margin-bottom: 20px; }
.sidebar-logo-img { width: 50px; height: 50px; border-radius: 50%; margin-right: 15px; border: 2px solid rgba(255,255,255,0.2); }
.sidebar-title h3 { margin: 0; font-size: 24px; font-weight: 600; color: white; }
.sidebar-subtitle { font-size: 12px; color: rgba(255,255,255,0.7); text-transform: uppercase; letter-spacing: 1px; }
.sidebar-user { display: flex; align-items: center; position: relative; }
.user-avatar { position: relative; margin-right: 15px; }
.user-avatar-img { width: 40px; height: 40px; border-radius: 50%; border: 2px solid rgba(255,255,255,0.3); }
.user-status { position: absolute; bottom: 0; right: 0; width: 12px; height: 12px; border-radius: 50%; border: 2px solid #1a237e; }
.user-status.online { background: #4caf50; }
.user-info { flex: 1; }
.user-name { font-weight: 600; font-size: 14px; margin-bottom: 2px; }
.user-role { font-size: 12px; color: rgba(255,255,255,0.7); text-transform: capitalize; }
.sidebar-close { display: none; background: none; border: none; color: white; font-size: 18px; cursor: pointer; padding: 5px; border-radius: 4px; }
.sidebar-close:hover { background: rgba(255,255,255,0.1); }

.sidebar-nav { padding: 10px 0; overflow-y: auto; flex: 1; }
.nav-list { list-style: none; margin: 0; padding: 0; }
.nav-item { margin-bottom: 1px; }

.nav-link {
    display: flex; align-items: center; padding: 12px 20px;
    color: rgba(255,255,255,0.8); text-decoration: none;
    transition: all 0.2s ease; border-left: 3px solid transparent;
    cursor: pointer;
}
.nav-link:hover {
    background: rgba(255,255,255,0.1); color: white;
    border-left-color: #ffd700; padding-left: 25px;
}
.nav-link.active {
    background: rgba(255,255,255,0.15); color: white;
    border-left-color: #ffd700;
}
.nav-icon { width: 24px; margin-right: 12px; text-align: center; font-size: 16px; flex-shrink: 0; }
.nav-text { flex: 1; font-weight: 500; font-size: 14px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.nav-arrow { font-size: 12px; transition: transform 0.3s ease; margin-left: 8px; }
.nav-parent.expanded .nav-arrow { transform: rotate(180deg); }

/* Child nav items */
.nav-children { background: rgba(0,0,0,0.15); border-left: 2px solid rgba(255,215,0,0.3); margin-left: 20px; }
.nav-child-item { }
.nav-child-item .nav-link { padding: 10px 20px; font-size: 13px; border-left: 2px solid transparent; }
.nav-child-item .nav-link:hover { border-left-color: #ffd700; background: rgba(255,255,255,0.08); }
.nav-child-item.active .nav-link { color: #ffd700; border-left-color: #ffd700; background: rgba(255,215,0,0.1); }
.nav-child-item .nav-icon { width: 16px; margin-right: 10px; display: flex; align-items: center; justify-content: center; }

.sidebar-footer {
    padding: 15px 20px; border-top: 1px solid rgba(255,255,255,0.1); margin-top: auto;
}
.footer-info { text-align: center; font-size: 12px; color: rgba(255,255,255,0.6); }

.sidebar-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 999; }
.sidebar-overlay.active { display: block; }

@media (max-width: 768px) {
    .mobile-menu-toggle { display: block; }
    .dashboard-sidebar { transform: translateX(-100%); }
    .dashboard-sidebar.active { transform: translateX(0); }
    .sidebar-close { display: block; }
    .sidebar-overlay.active { display: block; }
}
@media (min-width: 769px) {
    .dashboard-sidebar { transform: translateX(0); }
}

.nav-link:focus { outline: 2px solid #ffd700; outline-offset: 2px; }

/* Scrollbar styling */
.dashboard-sidebar::-webkit-scrollbar { width: 4px; }
.dashboard-sidebar::-webkit-scrollbar-track { background: rgba(255,255,255,0.05); }
.dashboard-sidebar::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.2); border-radius: 2px; }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById('dashboardSidebar');
    const mobileMenuToggle = document.getElementById('mobileMenuToggle');
    const sidebarClose = document.getElementById('sidebarClose');
    const sidebarOverlay = document.getElementById('sidebarOverlay');

    // Restore sidebar expanded state from localStorage
    const savedState = localStorage.getItem('isnm_sidebar_state');
    if (savedState) {
        try {
            const expanded = JSON.parse(savedState);
            document.querySelectorAll('.nav-parent').forEach(el => {
                const toggle = el.querySelector('.parent-toggle');
                if (toggle) {
                    const id = toggle.dataset.parent;
                    if (expanded[id]) {
                        el.classList.add('expanded');
                        const children = document.getElementById('children-' + id);
                        if (children) children.style.display = '';
                    }
                }
            });
        } catch(e) {}
    }

    // Parent toggle with arrow rotation
    document.querySelectorAll('.parent-toggle').forEach(toggle => {
        toggle.addEventListener('click', function(e) {
            e.preventDefault();
            const parent = this.closest('.nav-parent');
            const id = this.dataset.parent;
            const children = document.getElementById('children-' + id);
            if (!children) return;

            const isExpanding = children.style.display === 'none' || !children.style.display;
            children.style.display = isExpanding ? '' : 'none';
            parent.classList.toggle('expanded', isExpanding);

            // Save state to localStorage
            const saved = JSON.parse(localStorage.getItem('isnm_sidebar_state') || '{}');
            saved[id] = isExpanding;
            localStorage.setItem('isnm_sidebar_state', JSON.stringify(saved));
        });
    });

    // Mobile menu toggle
    if (mobileMenuToggle) {
        mobileMenuToggle.addEventListener('click', function() {
            sidebar.classList.toggle('active');
            sidebarOverlay.classList.toggle('active');
            this.classList.toggle('active');
            document.body.style.overflow = sidebar.classList.contains('active') ? 'hidden' : '';
        });
    }

    function closeSidebar() {
        sidebar.classList.remove('active');
        sidebarOverlay.classList.remove('active');
        if (mobileMenuToggle) mobileMenuToggle.classList.remove('active');
        document.body.style.overflow = '';
    }

    if (sidebarClose) sidebarClose.addEventListener('click', closeSidebar);
    if (sidebarOverlay) sidebarOverlay.addEventListener('click', closeSidebar);

    // Keyboard: Escape closes sidebar
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && sidebar.classList.contains('active')) closeSidebar();
    });

    // Close mobile sidebar on child link click
    document.querySelectorAll('.child-link').forEach(link => {
        link.addEventListener('click', function() {
            if (window.innerWidth <= 768) closeSidebar();
        });
    });
});
</script>
<?php
// Forgot: include direct links for Directory, Store, Logout
?>
