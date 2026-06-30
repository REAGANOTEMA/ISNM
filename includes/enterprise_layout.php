<?php
/**
 * ISNM Enterprise Layout Wrapper
 * Include this at the TOP of any dashboard to get the standard ERP layout:
 *   - Left sidebar (dark navy)
 *   - Top header bar
 *   - Center workspace
 *   - Right control panel (collapsible)
 *
 * Usage:
 *   require_once __DIR__ . '/../includes/enterprise_layout.php';
 *   // Then output your main content inside the workspace area
 */
if (session_status() === PHP_SESSION_NONE) session_start();

if (!function_exists('checkEnterprisePermission')) {
    require_once __DIR__ . '/enterprise_auth.php';
}

$elUser = $_SESSION['full_name'] ?? 'Staff';
$elRole = $_SESSION['role'] ?? '';
$elUid  = (int)($_SESSION['user_id'] ?? 0);
$elConn = null;

// Get connections if available
try {
    if (function_exists('getStaffConnection')) $elConn = getStaffConnection();
} catch (Exception $e) {}

// Counts for header badges
$elNotifCount = 0;
$elTaskCount = 0;
$elApprovalCount = 0;
if ($elConn && $elUid) {
    try {
        $elNotifCount = getUnreadNotificationCount($elConn, $elUid);
        $elTaskCount = getPendingTaskCount($elConn, $elUid);
        $elApprovalCount = getPendingApprovalCount($elConn);
    } catch (Exception $e) {}
}

// Profile image
$elProfileImage = '../images/username.png';
try {
    $pf = __DIR__ . '/profile_settings.php';
    if (file_exists($pf)) {
        include_once $pf;
        if (function_exists('getStaffProfileImageUrl') && $elUid) {
            $url = getStaffProfileImageUrl($elUid);
            if ($url) $elProfileImage = $url;
        }
    }
} catch (Exception $e) {}

$elDashboardUrl = $_SESSION['role_dashboard'] ?? '#';
$elCurrentPage = basename($_SERVER['PHP_SELF']);
$elCurrentSection = $_GET['section'] ?? $_GET['page'] ?? 'home';

// Dashboard stats for control panel
$elStats = ['students' => 0, 'staff' => 0, 'approvals' => 0, 'tasks' => 0, 'events' => 0];
try {
    if ($elConn) {
        $r = $elConn->query("SELECT COUNT(*)c FROM staff WHERE status='Active'");
        if ($r) $elStats['staff'] = (int)$r->fetch_assoc()['c'];
        $r = $elConn->query("SELECT COUNT(*)c FROM approval_requests WHERE status IN ('Active','pending','in_review')");
        if ($r) $elStats['approvals'] = (int)$r->fetch_assoc()['c'];
        if ($elUid) {
            $stmt = $elConn->prepare("SELECT COUNT(*)c FROM task_assignments WHERE assigned_to = ? AND status IN ('pending','in_progress')");
            if ($stmt) { $stmt->bind_param('i', $elUid); $stmt->execute(); $elStats['tasks'] = (int)$stmt->get_result()->fetch_assoc()['c']; $stmt->close(); }
        }
        $r = $elConn->query("SELECT COUNT(*)c FROM calendar_events WHERE event_date = CURDATE() AND is_active = 1");
        if ($r) $elStats['events'] = (int)$r->fetch_assoc()['c'];
    }
    if (function_exists('getStudentsConnection')) {
        $sc = getStudentsConnection();
        if ($sc) { $r = $sc->query("SELECT COUNT(*)c FROM students WHERE status='Active'"); if ($r) $elStats['students'] = (int)$r->fetch_assoc()['c']; }
    }
} catch (Exception $e) {}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ISNM — <?= htmlspecialchars($elRole) ?></title>
    <?php include_once __DIR__ . '/dashboard_head.php'; ?>
    <link rel="stylesheet" href="../css/enterprise-layout.css">
    <style>
        /* Override sidebar styles for enterprise layout */
        .isnm-sidebar { display: none !important; }
        body.ent-layout .ent-sidebar { display: block !important; }
        .ent-main { margin-left: var(--ent-sidebar-w); margin-right: var(--ent-control-w); }
        @media(max-width:1200px) {
            .ent-control { display: none !important; }
            .ent-main { margin-right: 0 !important; }
        }
        @media(max-width:768px) {
            .ent-sidebar { transform: translateX(-100%) !important; }
            .ent-sidebar.open { transform: translateX(0) !important; }
            .ent-main { margin-left: 0 !important; margin-right: 0 !important; }
        }
    </style>
</head>
<body class="ent-layout">

<!-- ═══ LEFT SIDEBAR ═══ -->
<nav class="ent-sidebar" id="entSidebar">
    <div class="ent-sidebar-brand">
        <img src="../images/school-logo.png" alt="ISNM" class="ent-sidebar-logo">
        <div class="ent-sidebar-brand-text">
            <span class="ent-sidebar-brand-name">ISNM</span>
            <span class="ent-sidebar-brand-sub">Management System</span>
        </div>
    </div>
    <div class="ent-sidebar-user">
        <div class="ent-sidebar-avatar"><?= strtoupper(substr($elUser, 0, 1)) ?></div>
        <div class="ent-sidebar-user-info">
            <div class="ent-sidebar-user-name"><?= htmlspecialchars($elUser) ?></div>
            <div class="ent-sidebar-user-role"><?= htmlspecialchars($elRole) ?></div>
        </div>
    </div>
    <div class="ent-sidebar-menu" id="entSidebarMenu">
        <?php
        // Render sidebar groups from sidebar_groups.php
        require_once __DIR__ . '/sidebar_groups.php';
        $sidebarGroups = getSidebarGroups($elRole);
        $groupIcons = [
            'MAIN' => 'fas fa-th-large', 'OPERATIONS' => 'fas fa-cogs',
            'MANAGEMENT' => 'fas fa-users-cog', 'REPORTS' => 'fas fa-chart-bar',
            'COMMUNICATION' => 'fas fa-comments', 'ACCOUNT' => 'fas fa-user-circle',
        ];
        $groupColors = [
            'MAIN' => '#3b82f6', 'OPERATIONS' => '#059669',
            'MANAGEMENT' => '#d97706', 'REPORTS' => '#7c3aed',
            'COMMUNICATION' => '#0891b2', 'ACCOUNT' => '#64748b',
        ];
        foreach ($sidebarGroups as $groupName => $items):
            $gid = preg_replace('/[^a-z0-9]/', '', strtolower($groupName));
            $gColor = $groupColors[$groupName] ?? '#64748b';
            $isActive = false;
            foreach ($items as $item) {
                if (($item['page'] ?? '') === $elCurrentSection) { $isActive = true; break; }
            }
        ?>
        <div class="ent-sidebar-group <?= $isActive ? 'expanded' : '' ?>">
            <div class="ent-sidebar-divider" data-target="<?= $gid ?>">
                <i class="<?= $groupIcons[$groupName] ?? 'fas fa-link' ?>" style="color:<?= $gColor ?>"></i>
                <span><?= htmlspecialchars($groupName) ?></span>
                <i class="fas fa-chevron-down ent-sidebar-chevron" style="margin-left:auto;font-size:10px"></i>
            </div>
            <div class="ent-sidebar-items" id="childGroup-<?= $gid ?>" style="<?= $isActive ? '' : 'max-height:0;overflow:hidden' ?>">
                <?php foreach ($items as $item):
                    $isActiveItem = (($item['page'] ?? '') === $elCurrentSection);
                    $href = $item['href'] ?? ($elCurrentPage . '?section=' . htmlspecialchars($item['page']));
                ?>
                <a href="<?= htmlspecialchars($href) ?>" class="ent-sidebar-item <?= $isActiveItem ? 'active' : '' ?>">
                    <span class="ent-sidebar-bullet" style="<?= $isActiveItem ? 'background:'.$gColor : '' ?>"></span>
                    <span><?= htmlspecialchars($item['label']) ?></span>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</nav>

<!-- ═══ TOP HEADER ═══ -->
<header class="ent-header" id="entHeader">
    <div class="ent-header-left">
        <button class="ent-hamburger" id="entHamburger" onclick="document.getElementById('entSidebar').classList.toggle('open')">
            <i class="fas fa-bars"></i>
        </button>
        <a href="<?= htmlspecialchars($elDashboardUrl) ?>" class="ent-header-brand" style="text-decoration:none;display:flex;align-items:center;gap:8px">
            <img src="../images/school-logo.png" alt="ISNM" style="height:28px;border-radius:6px">
            <div>
                <div style="color:#fff;font-size:14px;font-weight:700;line-height:1.2">ISNM</div>
                <div style="color:rgba(255,255,255,0.5);font-size:9px;text-transform:uppercase;letter-spacing:0.08em">Management System</div>
            </div>
        </a>
    </div>
    <div class="ent-header-center">
        <div class="ent-search-wrap">
            <i class="fas fa-search"></i>
            <input type="text" class="ent-search" placeholder="Search modules...">
        </div>
    </div>
    <div class="ent-header-right">
        <button class="ent-header-btn" title="Notifications" onclick="document.getElementById('entControlPanel').classList.toggle('open')">
            <i class="fas fa-bell"></i>
            <?php if ($elNotifCount > 0): ?><span class="badge-dot"></span><?php endif; ?>
        </button>
        <button class="ent-header-btn" title="Messages">
            <i class="fas fa-envelope"></i>
        </button>
        <button class="ent-header-btn" title="Calendar">
            <i class="fas fa-calendar-alt"></i>
        </button>
        <div class="ent-user-chip" onclick="document.getElementById('entControlPanel').classList.toggle('open')">
            <div class="ent-user-avatar"><?= strtoupper(substr($elUser, 0, 1)) ?></div>
            <span><?= htmlspecialchars(explode(' ', $elUser)[0]) ?></span>
            <i class="fas fa-chevron-down" style="color:rgba(255,255,255,0.4);font-size:10px"></i>
        </div>
    </div>
</header>

<!-- ═══ MAIN WORKSPACE ═══ -->
<main class="ent-main" id="entMain">
    <div class="ent-content-area" id="entContentArea">
        <!-- Dashboard content goes here -->
