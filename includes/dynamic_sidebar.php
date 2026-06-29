<?php
if (!function_exists('renderDynamicSidebar')) {

function renderDynamicSidebar(): void {
    global $conn;

    // Use a static variable so DB is queried only once per page load
    static $sidebarData = null;

    if ($sidebarData === null) {
        $userRoleKey = strtolower(trim($_SESSION['role'] ?? ''));

        // Try DatabaseConnection if $conn is not available
        $db = $conn;
        if (!$db && class_exists('DatabaseConnection')) {
            try {
                $db = DatabaseConnection::getStaffConnection();
            } catch (Exception $e) {
                $db = null;
            }
        }

        $sidebarData = [];

        if ($db && $userRoleKey !== '') {
            // Look up the role
            $roleSql = "SELECT id, dashboard_file FROM menu_roles WHERE LOWER(role_key) = ? LIMIT 1";
            $roleStmt = $db->prepare($roleSql);
            if ($roleStmt) {
                $roleStmt->bind_param('s', $userRoleKey);
                $roleStmt->execute();
                $roleResult = $roleStmt->get_result();
                $roleRow = $roleResult->fetch_assoc();
                $roleStmt->close();

                if ($roleRow) {
                    $roleId = (int)$roleRow['id'];

                    // Get permitted group IDs
                    $rgSql = "SELECT group_id FROM menu_role_groups WHERE role_id = ?";
                    $rgStmt = $db->prepare($rgSql);
                    if ($rgStmt) {
                        $rgStmt->bind_param('i', $roleId);
                        $rgStmt->execute();
                        $rgResult = $rgStmt->get_result();
                        $groupIds = [];
                        while ($rgRow = $rgResult->fetch_assoc()) {
                            $groupIds[] = (int)$rgRow['group_id'];
                        }
                        $rgStmt->close();

                        if (!empty($groupIds)) {
                            $idsStr = implode(',', $groupIds);

                            // Get all permitted groups sorted
                            $gSql = "SELECT id, group_name, display_name, icon, sort_order
                                     FROM menu_groups
                                     WHERE id IN ($idsStr) AND status = 'active'
                                     ORDER BY sort_order ASC";
                            $gResult = $db->query($gSql);
                            $groups = [];
                            while ($gRow = $gResult->fetch_assoc()) {
                                $groups[] = $gRow;
                            }

                            // Get all active menu items for those groups
                            $miSql = "SELECT mi.id, mi.group_id, mi.title, mi.route, mi.icon, mi.sort_order, mi.target
                                      FROM menu_items mi
                                      WHERE mi.group_id IN ($idsStr) AND mi.status = 'active'
                                      ORDER BY mi.sort_order ASC";
                            $miResult = $db->query($miSql);
                            $itemsByGroup = [];
                            while ($miRow = $miResult->fetch_assoc()) {
                                $gid = (int)$miRow['group_id'];
                                if (!isset($itemsByGroup[$gid])) {
                                    $itemsByGroup[$gid] = [];
                                }
                                $itemsByGroup[$gid][] = $miRow;
                            }

                            $sidebarData = [
                                'groups' => $groups,
                                'items'  => $itemsByGroup,
                            ];
                        }
                    }
                }
            }
        }
    }

    // If no data, render nothing (fallback will handle)
    if (empty($sidebarData['groups'])) {
        return;
    }

    $currentPage = basename($_SERVER['PHP_SELF']);
    $currentUri  = $_SERVER['REQUEST_URI'];

    // Reuse variables already set in sidebar.php
    $user_name   = $_SESSION['full_name'] ?? ($_SESSION['first_name'] ?? 'User');
    $user_role   = $_SESSION['role'] ?? '';
    $profileImage = $GLOBALS['profileImage'] ?? '../images/username.png';
    $profileClickHandler = $GLOBALS['profileClickHandler'] ?? "if(typeof openProfileModal==='function')openProfileModal();";
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

        <div class="sidebar-user" onclick="<?= $profileClickHandler ?>" style="cursor:pointer" title="Click to update profile">
            <div class="user-avatar-wrap">
                <img src="<?= htmlspecialchars($profileImage) ?>" alt="" class="user-avatar">
                <span class="user-dot"></span>
            </div>
            <div class="user-meta">
                <div class="user-fullname"><?= htmlspecialchars($user_name) ?></div>
                <div class="user-rolename"><?= htmlspecialchars($user_role) ?></div>
            </div>
        </div>

        <div class="sidebar-menu" id="sidebarMenu">
        <?php foreach ($sidebarData['groups'] as $group):
            $gid = (int)$group['id'];
            $groupIdSafe = preg_replace('/[^a-z0-9]/', '', strtolower($group['group_name']));
            $groupItems  = $sidebarData['items'][$gid] ?? [];
            $hasChildren = !empty($groupItems);

            // Check if any child is the current page
            $hasActiveChild = false;
            if ($hasChildren) {
                foreach ($groupItems as $item) {
                    $itemPage = basename(parse_url($item['route'], PHP_URL_PATH));
                    if ($itemPage === $currentPage) {
                        $hasActiveChild = true;
                        break;
                    }
                }
            }
        ?>
            <div class="menu-divider"><span><i class="<?= htmlspecialchars($group['icon']) ?>" style="color:#7c3aed;"></i> <?= htmlspecialchars($group['display_name']) ?></span></div>
            <?php if ($hasChildren): ?>
            <div class="menu-group <?= $hasActiveChild ? 'expanded' : '' ?>" data-group="<?= $groupIdSafe ?>">
                <div class="menu-group-header" data-target="<?= $groupIdSafe ?>">
                    <span class="menu-icon"><i class="<?= htmlspecialchars($group['icon']) ?>"></i></span>
                    <span class="menu-label"><?= htmlspecialchars($group['display_name']) ?></span>
                    <span class="menu-chevron"><i class="fas fa-chevron-down"></i></span>
                </div>
                <div class="menu-children" id="childGroup-<?= $groupIdSafe ?>" style="<?= $hasActiveChild ? '' : 'max-height:0;' ?>">
                    <div class="menu-children-inner">
                    <?php foreach ($groupItems as $item):
                        $itemRoute = $item['route'];
                        $itemPage  = basename(parse_url($itemRoute, PHP_URL_PATH));
                        $isActive  = ($itemPage === $currentPage);
                        $target    = ($item['target'] === 'blank') ? ' target="_blank" rel="noopener"' : '';
                    ?>
                        <a href="<?= htmlspecialchars($itemRoute) ?>" class="child-link <?= $isActive ? 'active' : '' ?>"<?= $target ?>>
                            <span class="child-bullet"></span>
                            <span class="child-label"><?= htmlspecialchars($item['title']) ?></span>
                        </a>
                    <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php else: ?>
            <div class="menu-group" data-group="<?= $groupIdSafe ?>">
                <div class="menu-group-header" style="cursor:pointer" onclick="window.location.href='<?= htmlspecialchars($group['group_name'] == 'overview' ? ($_SESSION['role_dashboard'] ?? '#') : '#') ?>'">
                    <span class="menu-icon"><i class="<?= htmlspecialchars($group['icon']) ?>"></i></span>
                    <span class="menu-label"><?= htmlspecialchars($group['display_name']) ?></span>
                </div>
            </div>
            <?php endif; ?>
        <?php endforeach; ?>
        </div>

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
    <?php
}
}
