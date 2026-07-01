<?php
/**
 * Professional Control Panel for ISNM Staff Dashboards
 * Include this in any dashboard to render the home page.
 * Expects $ctx, $staff, $students, $user, $user_role, $user_name, $uid to be set.
 */
if (!isset($ctx)) return;

// ── Gather dashboard stats ──
$cpStats = [
    'total_students' => 0,
    'active_staff'   => 0,
    'pending_approvals' => 0,
    'pending_tasks'  => 0,
    'recent_alerts'  => 0,
    'new_messages'   => 0,
    'today_events'   => 0,
];

try {
    $cpStaffConn = $ctx['staff'] ?? null;
    $cpStudentsConn = $ctx['students'] ?? null;

    if ($cpStudentsConn) {
        $r = $cpStudentsConn->query("SELECT COUNT(*)c FROM students WHERE status='Active'");
        if ($r) $cpStats['total_students'] = (int)$r->fetch_assoc()['c'];
    }
    if ($cpStaffConn) {
        $r = $cpStaffConn->query("SELECT COUNT(*)c FROM staff WHERE status='Active'");
        if ($r) $cpStats['active_staff'] = (int)$r->fetch_assoc()['c'];

        // Pending approvals from approval_requests
        $r = $cpStaffConn->query("SELECT COUNT(*)c FROM approval_requests WHERE status IN ('Active','pending','in_review')");
        if ($r) $cpStats['pending_approvals'] = (int)$r->fetch_assoc()['c'];

        // Pending tasks
        $cpUid = (int)($uid ?? $_SESSION['user_id'] ?? 0);
        if ($cpUid) {
            $stmt = $cpStaffConn->prepare("SELECT COUNT(*)c FROM task_assignments WHERE assigned_to = ? AND status IN ('pending','in_progress')");
            if ($stmt) {
                $stmt->bind_param('i', $cpUid);
                $stmt->execute();
                $cpStats['pending_tasks'] = (int)$stmt->get_result()->fetch_assoc()['c'];
                $stmt->close();
            }
        }

        // Recent alerts (from notifications)
        $cpStats['recent_alerts'] = getUnreadNotificationCount($cpStaffConn, $cpUid);

        // Today's events
        $r = $cpStaffConn->query("SELECT COUNT(*)c FROM calendar_events WHERE event_date = CURDATE() AND is_active = 1");
        if ($r) $cpStats['today_events'] = (int)$r->fetch_assoc()['c'];

        // Recent activity count
        $r = $cpStaffConn->query("SELECT COUNT(*)c FROM staff_activity_log WHERE DATE(created_at) = CURDATE()");
        if ($r) $cpStats['recent_alerts'] = max($cpStats['recent_alerts'], (int)$r->fetch_assoc()['c']);
    }
} catch (Exception $e) {}

$userPhoto = '../images/username.png';
try {
    $profileFile = __DIR__ . '/profile_settings.php';
    if (file_exists($profileFile)) {
        include_once $profileFile;
        if (function_exists('getStaffProfileImageUrl')) {
            $userPhoto = getStaffProfileImageUrl($uid);
        }
    }
} catch (Exception $e) {}

$instName = 'Iganga School of Nursing and Midwifery';
$instLogo = '../images/school-logo.png';
?>
<div class="cp-container">
    <!-- Row 1: Institution Branding + User Profile -->
    <div class="cp-brand-row">
        <div class="cp-brand">
            <img src="<?= $instLogo ?>" alt="ISNM" class="cp-logo">
            <div>
                <h1 class="cp-inst-name"><?= htmlspecialchars($instName) ?></h1>
                <p class="cp-inst-sub">Management Information System</p>
            </div>
        </div>
        <div class="cp-user-card">
            <img src="<?= htmlspecialchars($userPhoto) ?>" alt="" class="cp-user-avatar" onerror="this.src='../images/username.png'">
            <div class="cp-user-info">
                <span class="cp-user-name"><?= htmlspecialchars($user_name) ?></span>
                <span class="cp-user-role"><?= htmlspecialchars($user_role) ?></span>
                <span class="cp-user-staffid">ID: <?= $uid ?></span>
            </div>
        </div>
    </div>

    <!-- Row 2: Welcome + Date/Time -->
    <div class="cp-welcome-row">
        <div class="cp-welcome">
            <h2>Welcome back, <?= htmlspecialchars(explode(' ', $user_name)[0]) ?>!</h2>
            <p><?= date('l, F j, Y') ?> &middot; <span id="cpClock"></span></p>
        </div>
        <div class="cp-summary-badge">
            <span><strong><?= number_format($cpStats['total_students']) ?></strong> Students</span>
            <span class="cp-divider">|</span>
            <span><strong><?= number_format($cpStats['active_staff']) ?></strong> Staff</span>
            <span class="cp-divider">|</span>
            <span><strong><?= $cpStats['pending_approvals'] ?></strong> Pending</span>
        </div>
    </div>

    <!-- Row 3: KPI Cards -->
    <div class="cp-kpi-grid">
        <div class="cp-kpi cp-kpi-primary">
            <div class="cp-kpi-icon"><i class="fas fa-user-graduate"></i></div>
            <div class="cp-kpi-body">
                <span class="cp-kpi-value"><?= number_format($cpStats['total_students']) ?></span>
                <span class="cp-kpi-label">Total Students</span>
            </div>
        </div>
        <div class="cp-kpi cp-kpi-success">
            <div class="cp-kpi-icon"><i class="fas fa-users"></i></div>
            <div class="cp-kpi-body">
                <span class="cp-kpi-value"><?= number_format($cpStats['active_staff']) ?></span>
                <span class="cp-kpi-label">Active Staff</span>
            </div>
        </div>
        <div class="cp-kpi cp-kpi-warning">
            <div class="cp-kpi-icon"><i class="fas fa-clock"></i></div>
            <div class="cp-kpi-body">
                <span class="cp-kpi-value"><?= $cpStats['pending_approvals'] ?></span>
                <span class="cp-kpi-label">Pending Approvals</span>
            </div>
        </div>
        <div class="cp-kpi cp-kpi-danger">
            <div class="cp-kpi-icon"><i class="fas fa-exclamation-triangle"></i></div>
            <div class="cp-kpi-body">
                <span class="cp-kpi-value"><?= $cpStats['recent_alerts'] ?></span>
                <span class="cp-kpi-label">Active Alerts</span>
            </div>
        </div>
        <div class="cp-kpi cp-kpi-info">
            <div class="cp-kpi-icon"><i class="fas fa-check-double"></i></div>
            <div class="cp-kpi-body">
                <span class="cp-kpi-value"><?= $cpStats['new_messages'] ?></span>
                <span class="cp-kpi-label">New Messages</span>
            </div>
        </div>
        <div class="cp-kpi cp-kpi-purple">
            <div class="cp-kpi-icon"><i class="fas fa-calendar-day"></i></div>
            <div class="cp-kpi-body">
                <span class="cp-kpi-value"><?= $cpStats['today_events'] ?></span>
                <span class="cp-kpi-label">Today's Events</span>
            </div>
        </div>
    </div>

    <!-- Row 4: Two-column widgets -->
    <div class="cp-widget-row">
        <!-- Left: Recent Activity -->
        <div class="cp-widget cp-recent-activity">
            <div class="cp-widget-header"><i class="fas fa-history me-2"></i>Recent Activity</div>
            <div class="cp-widget-body" id="cpRecentActivity">
                <div class="cp-loading">Loading...</div>
            </div>
        </div>
        <!-- Right: Quick Shortcuts -->
        <div class="cp-widget cp-quick-links">
            <div class="cp-widget-header"><i class="fas fa-bolt me-2"></i>Quick Shortcuts</div>
            <div class="cp-widget-body">
                <div class="cp-quick-grid" id="cpQuickLinks">
                    <a href="#" class="cp-quick-item" data-page="reports"><i class="fas fa-chart-bar"></i><span>Reports</span></a>
                    <a href="#" class="cp-quick-item" onclick="window.print()"><i class="fas fa-print"></i><span>Print</span></a>
                    <a href="#" class="cp-quick-item" data-page="notifications"><i class="fas fa-bell"></i><span>Notifications</span></a>
                    <a href="#" class="cp-quick-item" data-page="settings"><i class="fas fa-cog"></i><span>Settings</span></a>
                    <a href="#" class="cp-quick-item" data-page="profile"><i class="fas fa-user"></i><span>My Profile</span></a>
                    <a href="#" class="cp-quick-item" data-page="analytics"><i class="fas fa-chart-line"></i><span>Analytics</span></a>
                </div>
            </div>
        </div>
    </div>

    <!-- Row 5: Pending Tasks + Notifications -->
    <div class="cp-widget-row">
        <div class="cp-widget cp-pending-tasks">
            <div class="cp-widget-header"><i class="fas fa-tasks me-2"></i>Pending Tasks</div>
            <div class="cp-widget-body" id="cpPendingTasks">
                <div class="cp-loading">Loading...</div>
            </div>
        </div>
        <div class="cp-widget cp-notifications">
            <div class="cp-widget-header"><i class="fas fa-bell me-2"></i>Notifications</div>
            <div class="cp-widget-body" id="cpNotifications">
                <div class="cp-loading">Loading...</div>
            </div>
        </div>
    </div>

    <!-- Row 6: Charts (full width) -->
    <div class="cp-widget-row cp-chart-row">
        <div class="cp-widget cp-chart-widget">
            <div class="cp-widget-header"><i class="fas fa-chart-bar me-2"></i>Department Performance</div>
            <div class="cp-widget-body">
                <canvas id="cpDeptChart" height="200"></canvas>
            </div>
        </div>
        <div class="cp-widget cp-chart-widget">
            <div class="cp-widget-header"><i class="fas fa-chart-pie me-2"></i>System Status</div>
            <div class="cp-widget-body">
                <div id="cpSystemStatus">
                    <div class="cp-status-item"><span class="cp-status-dot cp-status-online"></span> Database <span class="cp-status-text">Connected</span></div>
                    <div class="cp-status-item"><span class="cp-status-dot cp-status-online"></span> Server <span class="cp-status-text">Online</span></div>
                    <div class="cp-status-item"><span class="cp-status-dot cp-status-online"></span> Storage <span class="cp-status-text">Operational</span></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Row 7: Latest Reports -->
    <div class="cp-widget-row">
        <div class="cp-widget cp-reports-widget">
            <div class="cp-widget-header"><i class="fas fa-file-alt me-2"></i>Latest Reports</div>
            <div class="cp-widget-body" id="cpLatestReports">
                <div class="cp-loading">Loading...</div>
            </div>
        </div>
    </div>
</div>

<style>
.cp-container{width:100%;margin:0;padding:0;font-family:'Inter',system-ui,-apple-system,sans-serif}
.cp-brand-row{display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:16px;margin-bottom:20px;padding:20px 24px;background:linear-gradient(135deg,#1e3a5f,#1a237e);border-radius:16px;box-shadow:0 4px 20px rgba(0,0,0,.1)}
.cp-brand{display:flex;align-items:center;gap:16px}
.cp-logo{width:56px;height:56px;border-radius:12px;border:2px solid rgba(255,255,255,.2);object-fit:cover}
.cp-inst-name{color:#fff;font-size:20px;font-weight:700;margin:0;letter-spacing:-.3px}
.cp-inst-sub{color:rgba(255,255,255,.6);font-size:12px;margin:2px 0 0}
.cp-user-card{display:flex;align-items:center;gap:12px;background:rgba(255,255,255,.1);padding:10px 18px 10px 14px;border-radius:12px;backdrop-filter:blur(8px)}
.cp-user-avatar{width:44px;height:44px;border-radius:50%;border:2px solid rgba(255,255,255,.3);object-fit:cover}
.cp-user-info{display:flex;flex-direction:column}
.cp-user-name{color:#fff;font-size:14px;font-weight:600}
.cp-user-role{color:rgba(255,255,255,.7);font-size:11px}
.cp-user-staffid{color:rgba(255,255,255,.4);font-size:10px}
.cp-welcome-row{display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-bottom:24px}
.cp-welcome h2{font-size:22px;font-weight:700;color:#0f172a;margin:0}
.cp-welcome p{color:#64748b;font-size:13px;margin:4px 0 0}
.cp-summary-badge{display:flex;align-items:center;gap:8px;background:#fff;padding:10px 18px;border-radius:10px;border:1px solid #e2e8f0;font-size:13px}
.cp-summary-badge strong{font-size:16px}
.cp-divider{color:#cbd5e1}
.cp-kpi-grid{display:grid;grid-template-columns:repeat(6,1fr);gap:14px;margin-bottom:24px}
@media(max-width:1200px){.cp-kpi-grid{grid-template-columns:repeat(3,1fr)}}
@media(max-width:768px){.cp-kpi-grid{grid-template-columns:repeat(2,1fr)}}
.cp-kpi{background:#fff;border-radius:14px;padding:16px 18px;display:flex;align-items:center;gap:14px;border:1px solid #e2e8f0;transition:all .2s;box-shadow:0 1px 3px rgba(0,0,0,.04)}
.cp-kpi:hover{transform:translateY(-2px);box-shadow:0 4px 16px rgba(0,0,0,.08)}
.cp-kpi-icon{width:44px;height:44px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:18px;color:#fff;flex-shrink:0}
.cp-kpi-primary .cp-kpi-icon{background:#3b82f6}
.cp-kpi-success .cp-kpi-icon{background:#10b981}
.cp-kpi-warning .cp-kpi-icon{background:#f59e0b}
.cp-kpi-danger .cp-kpi-icon{background:#ef4444}
.cp-kpi-info .cp-kpi-icon{background:#06b6d4}
.cp-kpi-purple .cp-kpi-icon{background:#8b5cf6}
.cp-kpi-body{display:flex;flex-direction:column}
.cp-kpi-value{font-size:22px;font-weight:800;color:#0f172a;line-height:1.1}
.cp-kpi-label{font-size:11px;color:#64748b;font-weight:500;margin-top:2px}
.cp-widget-row{display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px}
.cp-chart-row{grid-template-columns:2fr 1fr}
.cp-widget{background:#fff;border-radius:14px;border:1px solid #e2e8f0;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,.04)}
.cp-widget-header{padding:14px 20px;font-size:13px;font-weight:600;color:#1e293b;border-bottom:1px solid #e2e8f0;background:#f8fafc}
.cp-widget-body{padding:16px 20px}
.cp-loading{color:#94a3b8;font-size:13px;padding:12px 0;text-align:center}
.cp-quick-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:10px}
.cp-quick-item{display:flex;flex-direction:column;align-items:center;gap:6px;padding:14px 8px;border-radius:10px;background:#f8fafc;border:1px solid #e2e8f0;color:#475569;text-decoration:none;font-size:11px;font-weight:500;transition:all .2s}
.cp-quick-item:hover{background:#eef2ff;border-color:#a5b4fc;color:#4338ca;text-decoration:none;transform:translateY(-1px)}
.cp-quick-item i{font-size:20px;color:#64748b}
.cp-quick-item:hover i{color:#4338ca}
.cp-status-item{display:flex;align-items:center;gap:8px;padding:8px 0;font-size:13px;color:#475569}
.cp-status-dot{width:8px;height:8px;border-radius:50%;display:inline-block}
.cp-status-online{background:#10b981;box-shadow:0 0 6px rgba(16,185,129,.4)}
.cp-status-offline{background:#ef4444}
.cp-status-text{margin-left:auto;font-weight:500}
@media(max-width:768px){.cp-widget-row,.cp-chart-row{grid-template-columns:1fr}.cp-brand-row{flex-direction:column;align-items:flex-start}.cp-quick-grid{grid-template-columns:repeat(2,1fr)}}
</style>

<script>
(function(){
    // Clock
    function cpUpdateClock(){
        var el = document.getElementById('cpClock');
        if(!el) return;
        var now = new Date();
        el.textContent = now.toLocaleTimeString('en-UG', {hour:'2-digit',minute:'2-digit',second:'2-digit'});
    }
    cpUpdateClock();
    setInterval(cpUpdateClock, 1000);

    // Quick link clicks
    document.querySelectorAll('.cp-quick-item[data-page]').forEach(function(el){
        el.addEventListener('click', function(e){
            e.preventDefault();
            var page = this.getAttribute('data-page');
            var url = new URL(window.location.href);
            url.searchParams.set('page', page);
            window.location.href = url.toString();
        });
    });
})();
</script>
