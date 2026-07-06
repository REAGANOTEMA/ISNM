<?php
/**
 * ISNM Enterprise Control Panel Addon
 * Include this at the BOTTOM of any dashboard (before </body>) to add the right-side control panel.
 * Works with ANY existing dashboard layout — no restructuring needed.
 *
 * Usage:
 *   <?php include_once __DIR__ . '/../includes/enterprise_control_panel.php'; ?>
 */
if (session_status() === PHP_SESSION_NONE) session_start();

if (!function_exists('checkEnterprisePermission')) {
    require_once __DIR__ . '/enterprise_auth.php';
}

$cpUser = $_SESSION['full_name'] ?? 'Staff';
$cpRole = $_SESSION['role'] ?? '';
$cpUid  = (int)($_SESSION['user_id'] ?? 0);
$cpConn = null;
$cpStudentsConn = null;

try { if (function_exists('getStaffConnection')) $cpConn = getStaffConnection(); } catch (Exception $e) { error_log('enterprise_cp init: ' . $e->getMessage()); }
try { if (function_exists('getStudentsConnection')) $cpStudentsConn = getStudentsConnection(); } catch (Exception $e) { error_log('enterprise_cp init: ' . $e->getMessage()); }

// Gather stats
$cpStats = ['students' => 0, 'staff' => 0, 'approvals' => 0, 'tasks' => 0, 'events' => 0, 'notifs' => 0];
try {
    if ($cpStudentsConn) {
        $r = $cpStudentsConn->query("SELECT COUNT(*)c FROM students WHERE status='Active'");
        if ($r) $cpStats['students'] = (int)$r->fetch_assoc()['c'];
    }
    if ($cpConn) {
        $r = $cpConn->query("SELECT COUNT(*)c FROM staff WHERE status='Active'");
        if ($r) $cpStats['staff'] = (int)$r->fetch_assoc()['c'];
        $r = $cpConn->query("SELECT COUNT(*)c FROM approval_requests WHERE status IN ('Active','pending','in_review')");
        if ($r) $cpStats['approvals'] = (int)$r->fetch_assoc()['c'];
        if ($cpUid) {
            $stmt = $cpConn->prepare("SELECT COUNT(*)c FROM task_assignments WHERE assigned_to = ? AND status IN ('pending','in_progress')");
            if ($stmt) { $stmt->bind_param('i', $cpUid); $stmt->execute(); $cpStats['tasks'] = (int)$stmt->get_result()->fetch_assoc()['c']; $stmt->close(); }
            $cpStats['notifs'] = getUnreadNotificationCount($cpConn, $cpUid);
        }
        $r = $cpConn->query("SELECT COUNT(*)c FROM calendar_events WHERE event_date = CURDATE() AND is_active = 1");
        if ($r) $cpStats['events'] = (int)$r->fetch_assoc()['c'];
    }
} catch (Exception $e) { error_log('enterprise_cp init: ' . $e->getMessage()); }

$cpDashboardUrl = $_SESSION['role_dashboard'] ?? '#';
$cpInitial = strtoupper(substr($cpUser, 0, 1));
?>

<!-- ═══ ENTERPRISE CONTROL PANEL (ADDON) ═══ -->
<style>
.ent-cp-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,0.4); z-index:1099; }
.ent-cp-overlay.open { display:block; }
.ent-cp-panel {
    position:fixed; top:0; right:-320px; bottom:0; width:320px;
    background:#fff; box-shadow:-4px 0 30px rgba(0,0,0,0.15);
    z-index:1100; transition:all 0.3s cubic-bezier(0.4,0,0.2,1);
    overflow-y:auto; font-family:'Inter',-apple-system,sans-serif;
}
.ent-cp-panel.open { right:0; }
.ent-cp-close {
    position:absolute; top:12px; right:12px; width:32px; height:32px;
    border-radius:8px; border:none; background:rgba(255,255,255,0.1);
    color:#fff; font-size:14px; cursor:pointer; display:flex; align-items:center; justify-content:center;
    transition:background 0.2s; z-index:2;
}
.ent-cp-close:hover { background:rgba(255,255,255,0.2); }
.ent-cp-profile {
    text-align:center; padding:24px 16px 16px;
    background:linear-gradient(135deg,#0f172a 0%,#1e3a5f 100%);
    color:#fff;
}
.ent-cp-avatar {
    width:64px; height:64px; border-radius:50%; margin:0 auto 10px;
    background:#3b82f6; color:#fff; font-size:24px; font-weight:700;
    display:flex; align-items:center; justify-content:center;
    border:3px solid rgba(255,255,255,0.2);
}
.ent-cp-profile h4 { margin:0; font-size:15px; font-weight:700; }
.ent-cp-profile p { margin:2px 0 0; font-size:11px; opacity:0.7; }
.ent-cp-role-badge {
    display:inline-block; margin-top:8px; padding:3px 12px;
    background:rgba(255,255,255,0.15); border-radius:12px;
    font-size:10px; font-weight:600;
}
.ent-cp-section { padding:14px 16px; border-bottom:1px solid #e2e8f0; }
.ent-cp-section-title {
    font-size:10px; font-weight:700; text-transform:uppercase;
    letter-spacing:0.08em; color:#64748b; margin-bottom:10px;
    display:flex; align-items:center; gap:6px;
}
.ent-cp-stats { display:grid; grid-template-columns:1fr 1fr; gap:8px; }
.ent-cp-stat {
    text-align:center; padding:10px 8px; background:#f8fafc;
    border-radius:8px; border:1px solid #e2e8f0;
}
.ent-cp-stat-value { font-size:20px; font-weight:700; line-height:1.2; }
.ent-cp-stat-label { font-size:10px; color:#64748b; margin-top:2px; }
.ent-cp-action {
    display:flex; align-items:center; gap:10px; padding:10px 12px;
    border-radius:8px; color:#0f172a; text-decoration:none;
    font-size:12px; font-weight:500; transition:all 0.2s;
    margin-bottom:4px;
}
.ent-cp-action:hover { background:#3b82f6; color:#fff; }
.ent-cp-action i { width:18px; text-align:center; font-size:13px; }
.ent-cp-action .ent-cp-action-badge {
    margin-left:auto; background:#ef4444; color:#fff;
    font-size:9px; font-weight:700; padding:2px 6px;
    border-radius:10px; min-width:18px; text-align:center;
}
.ent-cp-clock { text-align:center; padding:8px; }
.ent-cp-clock-time { font-size:28px; font-weight:700; color:#0f172a; letter-spacing:-0.02em; }
.ent-cp-clock-date { font-size:11px; color:#64748b; margin-top:2px; }
.ent-cp-online { display:inline-block; width:8px; height:8px; border-radius:50%; background:#10b981; margin-right:4px; vertical-align:middle; }

/* Toggle button */
.ent-cp-toggle {
    position:fixed; bottom:20px; right:20px; width:48px; height:48px;
    border-radius:50%; border:none; background:linear-gradient(135deg,#0f172a,#1e3a5f);
    color:#fff; font-size:18px; cursor:pointer; z-index:999;
    box-shadow:0 4px 20px rgba(0,0,0,0.2); display:flex; align-items:center; justify-content:center;
    transition:transform 0.2s;
}
.ent-cp-toggle:hover { transform:scale(1.1); }
@media(max-width:768px) { .ent-cp-panel { width:100%; right:-100%; } }
</style>

<div class="ent-cp-overlay" id="entCpOverlay" onclick="document.getElementById('entCpPanel').classList.remove('open');this.classList.remove('open')"></div>
<div class="ent-cp-panel" id="entCpPanel">
    <button class="ent-cp-close" onclick="document.getElementById('entCpPanel').classList.remove('open');document.getElementById('entCpOverlay').classList.remove('open')">
        <i class="fas fa-times"></i>
    </button>

    <!-- Profile -->
    <div class="ent-cp-profile">
        <div class="ent-cp-avatar"><?= $cpInitial ?></div>
        <h4><?= htmlspecialchars($cpUser) ?></h4>
        <p><?= htmlspecialchars($cpRole) ?></p>
        <span class="ent-cp-role-badge"><span class="ent-cp-online"></span>Online</span>
    </div>

    <!-- Clock -->
    <div class="ent-cp-section">
        <div class="ent-cp-clock">
            <div class="ent-cp-clock-time" id="entCpClock"><?= date('H:i') ?></div>
            <div class="ent-cp-clock-date"><?= date('l, d M Y') ?></div>
        </div>
    </div>

    <!-- Stats -->
    <div class="ent-cp-section">
        <div class="ent-cp-section-title"><i class="fas fa-chart-pie"></i> Quick Stats</div>
        <div class="ent-cp-stats">
            <div class="ent-cp-stat">
                <div class="ent-cp-stat-value" style="color:#3b82f6"><?= number_format($cpStats['students']) ?></div>
                <div class="ent-cp-stat-label">Students</div>
            </div>
            <div class="ent-cp-stat">
                <div class="ent-cp-stat-value" style="color:#10b981"><?= number_format($cpStats['staff']) ?></div>
                <div class="ent-cp-stat-label">Staff</div>
            </div>
            <div class="ent-cp-stat">
                <div class="ent-cp-stat-value" style="color:#f59e0b"><?= number_format($cpStats['tasks']) ?></div>
                <div class="ent-cp-stat-label">My Tasks</div>
            </div>
            <div class="ent-cp-stat">
                <div class="ent-cp-stat-value" style="color:#8b5cf6"><?= number_format($cpStats['events']) ?></div>
                <div class="ent-cp-stat-label">Events</div>
            </div>
        </div>
    </div>

    <!-- Pending -->
    <div class="ent-cp-section">
        <div class="ent-cp-section-title"><i class="fas fa-check-double"></i> Pending</div>
        <div style="display:flex;gap:8px">
            <div style="flex:1;text-align:center;padding:8px;background:#fff7ed;border-radius:8px;border:1px solid #fed7aa">
                <div style="font-size:18px;font-weight:700;color:#ea580c"><?= $cpStats['approvals'] ?></div>
                <div style="font-size:10px;color:#9a3412">Approvals</div>
            </div>
            <div style="flex:1;text-align:center;padding:8px;background:#eff6ff;border-radius:8px;border:1px solid #bfdbfe">
                <div style="font-size:18px;font-weight:700;color:#2563eb"><?= $cpStats['notifs'] ?></div>
                <div style="font-size:10px;color:#1e40af">Alerts</div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="ent-cp-section">
        <div class="ent-cp-section-title"><i class="fas fa-bolt"></i> Quick Actions</div>
        <a href="<?= htmlspecialchars($cpDashboardUrl) ?>" class="ent-cp-action">
            <i class="fas fa-home" style="color:#3b82f6"></i> Dashboard Home
        </a>
        <a href="<?= htmlspecialchars($cpDashboardUrl) ?>?section=profile" class="ent-cp-action">
            <i class="fas fa-user-circle" style="color:#8b5cf6"></i> My Profile
        </a>
        <a href="<?= htmlspecialchars($cpDashboardUrl) ?>?section=settings" class="ent-cp-action">
            <i class="fas fa-cog" style="color:#f59e0b"></i> Settings
        </a>
        <a href="../auth-handler.php?action=logout" class="ent-cp-action">
            <i class="fas fa-sign-out-alt" style="color:#ef4444"></i> Logout
        </a>
    </div>
</div>

<!-- Toggle Button -->
<button class="ent-cp-toggle" onclick="document.getElementById('entCpPanel').classList.toggle('open');document.getElementById('entCpOverlay').classList.toggle('open')" title="Control Panel">
    <i class="fas fa-sliders-h"></i>
</button>

<script>
// Live clock
(function(){
    function updateClock() {
        var el = document.getElementById('entCpClock');
        if (!el) return;
        var now = new Date();
        el.textContent = now.toLocaleTimeString('en-UG', {hour:'2-digit', minute:'2-digit'});
    }
    updateClock();
    setInterval(updateClock, 30000);
})();
</script>
