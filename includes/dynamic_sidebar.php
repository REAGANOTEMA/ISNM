<?php
/**
 * ISNM DYNAMIC SIDEBAR — DB-driven, role-filtered
 * Uses the same HTML structure as the static sidebar for CSS compatibility.
 */
if (!function_exists('renderDynamicSidebar')) {

function renderDynamicSidebar(): void {
    if (!function_exists('getModuleRegistry')) {
        $regFile = __DIR__ . '/module_registry.php';
        if (file_exists($regFile)) require_once $regFile;
        else return;
    }

    $roleMap = [
        'Director General' => 1, 'CEO' => 2, 'Director Academics' => 3,
        'Director Finance' => 4, 'Director ICT' => 5, 'School Principal' => 6,
        'Deputy Principal' => 7, 'Academic Registrar' => 8, 'HR Manager' => 9,
        'School Secretary' => 10, 'School Librarian' => 11, 'Storekeeper' => 21,
        'Guild President' => 22, 'Computer Lab Manager' => 23, 'School Bursar' => 24,
        'Director Admissions & Requirements' => 26, 'Director Admissions' => 28,
        'Head of Nursing' => 29, 'Head of Midwifery' => 30, 'Senior Lecturer' => 31,
        'Lecturer' => 32, 'Security Officer' => 33, 'Driver' => 34,
        'Matron' => 35, 'Warden' => 36, 'Sickbay Nurse' => 37,
        'Computer Lab' => 39, 'Skills Lab Technician' => 40, 'Skills Lab Manager' => 41,
    ];

    $roleName = $_SESSION['role'] ?? '';
    $roleId = $roleMap[$roleName] ?? 0;
    if (!$roleId) return;

    try {
        $registry = getModuleRegistry();
        $sidebar = $registry->getSidebarForRole($roleId);
    } catch (Exception $e) { return; }
    if (empty($sidebar)) return;

    $userName = $_SESSION['full_name'] ?? 'User';
    $userRole = $roleName;
    $profileImage = $GLOBALS['profileImage'] ?? '../images/username.png';
    $profileClick = $GLOBALS['profileClickHandler'] ?? "if(typeof openProfileModal==='function')openProfileModal();";
    $currentPage = basename($_SERVER['PHP_SELF']);
    $activePage = $_GET['page'] ?? 'home';

    // Group colors matching the static sidebar
    $groupColors = [
        'leadership' => '#3b82f6', 'academic' => '#3b82f6', 'finance' => '#10b981',
        'hr' => '#8b5cf6', 'student_services' => '#f59e0b', 'operations' => '#6366f1',
        'compliance' => '#ef4444', 'clinical' => '#ef4444', 'system' => '#475569',
    ];
    ?>
    <nav class="isnm-sidebar sidebar" id="isnmSidebar">
        <div class="sidebar-brand">
            <button class="sidebar-collapse-btn" id="sidebarCollapse" aria-label="Toggle sidebar">
                <i class="fas fa-bars"></i>
            </button>
            <img src="../images/school-logo.png" alt="ISNM" class="brand-logo">
            <div class="brand-text">
                <span class="brand-name">ISNM</span>
                <span class="brand-sub">ERP System</span>
            </div>
        </div>

        <div class="sidebar-user" onclick="<?= $profileClick ?>" style="cursor:pointer" title="Click to update profile">
            <div class="user-avatar-wrap">
                <img src="<?= htmlspecialchars($profileImage) ?>" alt="" class="user-avatar">
                <span class="user-dot"></span>
            </div>
            <div class="user-meta">
                <div class="user-fullname"><?= htmlspecialchars($userName) ?></div>
                <div class="user-rolename"><?= htmlspecialchars($userRole) ?></div>
            </div>
        </div>

        <div class="sidebar-menu" id="sidebarMenu">
            <?php foreach ($sidebar as $deptKey => $dept):
                $groupIdSafe = preg_replace('/[^a-z0-9]/', '', strtolower($deptKey));
                $groupColor = $groupColors[$deptKey] ?? $dept['color'] ?? '#64748b';
                $hasActiveChild = false;
                foreach ($dept['modules'] as $mod) {
                    if ($mod['name'] === $activePage) { $hasActiveChild = true; break; }
                }
            ?>
            <div class="menu-group <?= $hasActiveChild ? 'expanded' : '' ?>" data-group="<?= $groupIdSafe ?>">
                <div class="menu-group-header" data-target="<?= $groupIdSafe ?>">
                    <span class="menu-icon"><i class="fas fa-<?= htmlspecialchars($dept['icon'] ?? 'fas fa-cube') ?>" style="color:<?= $groupColor ?>"></i></span>
                    <span class="menu-label"><?= htmlspecialchars($dept['label'] ?? $deptKey) ?></span>
                    <span class="menu-chevron"><i class="fas fa-chevron-down"></i></span>
                </div>
                <div class="menu-children" id="childGroup-<?= $groupIdSafe ?>" style="<?= $hasActiveChild ? '' : 'max-height:0' ?>">
                    <div class="menu-children-inner">
                        <?php foreach ($dept['modules'] as $mod):
                            $isActive = ($mod['name'] === $activePage);
                            $route = $mod['route'] ?? '#';
                        ?>
                        <a href="<?= htmlspecialchars($route) ?>" class="child-link <?= $isActive ? 'active' : '' ?>"
                           data-module="<?= htmlspecialchars($mod['name']) ?>"
                           title="<?= htmlspecialchars($mod['description'] ?? $mod['label']) ?>">
                            <span class="child-bullet" style="<?= $isActive ? 'background:' . $groupColor : '' ?>"></span>
                            <span class="child-label"><?= htmlspecialchars($mod['label']) ?></span>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="sidebar-footer">
            <a href="../auth-handler.php?action=logout" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
            <div class="footer-meta"><span>v3.0</span><span>&copy; <?= date('Y') ?> ISNM</span></div>
        </div>
    </nav>

    <script>
    (function() {
        // Collapsible groups
        document.querySelectorAll('.menu-group-header[data-target]').forEach(function(header) {
            header.addEventListener('click', function() {
                var group = this.closest('.menu-group');
                if (!group) return;
                var targetId = this.getAttribute('data-target');
                var children = document.getElementById('childGroup-' + targetId);
                if (!children) return;
                var isExpanded = group.classList.contains('expanded');
                // Accordion: close others
                document.querySelectorAll('.menu-group.expanded').forEach(function(g) {
                    if (g !== group && g.closest('.sidebar-menu') === group.closest('.sidebar-menu')) {
                        g.classList.remove('expanded');
                        var c = g.querySelector('.menu-children');
                        if (c) c.style.maxHeight = '0';
                    }
                });
                if (isExpanded) {
                    group.classList.remove('expanded');
                    children.style.maxHeight = '0';
                } else {
                    group.classList.add('expanded');
                    children.style.maxHeight = children.scrollHeight + 'px';
                }
            });
        });

        // Sidebar collapse toggle
        var collapseBtn = document.getElementById('sidebarCollapse');
        if (collapseBtn) {
            collapseBtn.addEventListener('click', function() {
                document.getElementById('isnmSidebar').classList.toggle('collapsed');
            });
        }

        // Auto-expand active group
        var activeLink = document.querySelector('.child-link.active');
        if (activeLink) {
            var group = activeLink.closest('.menu-group');
            if (group && !group.classList.contains('expanded')) {
                group.classList.add('expanded');
                var children = group.querySelector('.menu-children');
                if (children) children.style.maxHeight = children.scrollHeight + 'px';
            }
        }

        // Mobile: close sidebar on outside click
        document.addEventListener('click', function(e) {
            var sidebar = document.getElementById('isnmSidebar');
            if (!sidebar || window.innerWidth > 768) return;
            if (!sidebar.contains(e.target) && sidebar.classList.contains('open')) {
                sidebar.classList.remove('open');
            }
        });
    })();
    </script>
    <?php
}
}
