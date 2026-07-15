<?php
/**
 * ISNM Enterprise Header — Shared top navigation bar
 * Include this in every dashboard for consistent header across the ERP.
 *
 * Expects: $user_name, $user_role, $uid, $ctx (optional) to be set.
 * Uses: enterprise_auth.php for notification/task counts.
 */
if (!function_exists('checkEnterprisePermission')) {
    require_once __DIR__ . '/enterprise_auth.php';
}

// Gather header data
$hUserName   = $user_name ?? ($_SESSION['full_name'] ?? 'Staff');
$hUserRole   = $user_role ?? ($_SESSION['role'] ?? '');
$hUserId     = $uid ?? (int)($_SESSION['user_id'] ?? 0);
$hStaffConn  = $ctx['staff'] ?? ($conn ?? null);
$hInstName   = 'Iganga School of Nursing and Midwifery';
$hInstLogo   = '../images/school-logo.png';

// Counts
$hNotifCount  = 0;
$hTaskCount   = 0;
$hApprovalCount = 0;
if ($hStaffConn && $hUserId) {
    $hNotifCount    = getUnreadNotificationCount($hUserId);
    $hTaskCount     = getPendingTaskCount($hStaffConn, $hUserId);
    $hApprovalCount = getPendingApprovalCount($hStaffConn);
}

$hProfileImage = '../images/username.png';
try {
    $pf = __DIR__ . '/profile_settings.php';
    if (file_exists($pf)) {
        include_once $pf;
        if (function_exists('getStaffProfileImageUrl') && $hUserId) {
            $url = getStaffProfileImageUrl($hUserId);
            if ($url) $hProfileImage = $url;
        }
    }
} catch (Exception $e) { error_log('enterprise_header load: ' . $e->getMessage()); }

$hDashboardUrl = $_SESSION['role_dashboard'] ?? '#';
$hCurrentPage  = basename($_SERVER['PHP_SELF']);
?>
<header class="ent-header" id="entHeader">
    <div class="ent-header-left">
        <button class="ent-sidebar-toggle" id="entSidebarToggle" title="Toggle sidebar">
            <i class="fas fa-bars"></i>
        </button>
        <a href="<?= htmlspecialchars($hDashboardUrl) ?>" class="ent-header-brand">
            <img src="<?= $hInstLogo ?>" alt="ISNM" class="ent-header-logo">
            <div class="ent-header-brand-text">
                <span class="ent-header-brand-name">ISNM</span>
                <span class="ent-header-brand-sub">Management System</span>
            </div>
        </a>
    </div>

    <div class="ent-header-center">
        <div class="ent-header-breadcrumb">
            <a href="<?= htmlspecialchars($hDashboardUrl) ?>"><i class="fas fa-home"></i></a>
            <span class="ent-breadcrumb-sep">/</span>
            <span class="ent-breadcrumb-current"><?= htmlspecialchars($hUserRole) ?></span>
        </div>
    </div>

    <div class="ent-header-right">
        <!-- Search -->
        <div class="ent-header-search">
            <i class="fas fa-search"></i>
            <input type="text" placeholder="Search..." id="entGlobalSearch" autocomplete="off">
        </div>

        <!-- Notification Bell -->
        <div class="ent-header-icon" id="entNotifToggle" title="Notifications">
            <i class="fas fa-bell"></i>
            <?php if ($hNotifCount > 0): ?>
                <span class="ent-badge ent-badge-danger"><?= $hNotifCount > 99 ? '99+' : $hNotifCount ?></span>
            <?php endif; ?>
        </div>

        <!-- Tasks -->
        <div class="ent-header-icon" id="entTaskToggle" title="My Tasks">
            <i class="fas fa-tasks"></i>
            <?php if ($hTaskCount > 0): ?>
                <span class="ent-badge ent-badge-warning"><?= $hTaskCount ?></span>
            <?php endif; ?>
        </div>

        <!-- Approvals (Director General only) -->
        <?php if ($hApprovalCount > 0 && in_array(strtolower($hUserRole), ['director general', 'ceo'])): ?>
        <div class="ent-header-icon" title="Pending Approvals">
            <a href="<?= htmlspecialchars($hDashboardUrl) ?>?view=approvals" style="color:inherit;text-decoration:none">
                <i class="fas fa-check-double"></i>
                <span class="ent-badge ent-badge-info"><?= $hApprovalCount ?></span>
            </a>
        </div>
        <?php endif; ?>

        <!-- Clock -->
        <div class="ent-header-clock" id="entHeaderClock"></div>

        <!-- Profile -->
        <div class="ent-header-profile" id="entProfileToggle">
            <img src="<?= htmlspecialchars($hProfileImage) ?>" alt="" class="ent-header-avatar" onerror="this.src='../images/username.png'">
            <div class="ent-header-profile-info">
                <span class="ent-header-username"><?= htmlspecialchars($hUserName) ?></span>
                <span class="ent-header-role"><?= htmlspecialchars($hUserRole) ?></span>
            </div>
            <i class="fas fa-chevron-down"></i>
        </div>

        <!-- Profile Dropdown -->
        <div class="ent-profile-dropdown" id="entProfileDropdown">
            <a href="<?= htmlspecialchars($hDashboardUrl) ?>?view=profile"><i class="fas fa-user"></i> My Profile</a>
            <a href="<?= htmlspecialchars($hDashboardUrl) ?>?view=settings"><i class="fas fa-cog"></i> Settings</a>
            <div class="ent-dropdown-divider"></div>
            <a href="#" class="ent-logout-link" onclick="event.preventDefault();var f=document.createElement('form');f.method='POST';f.action='../auth-handler.php?action=logout';document.body.appendChild(f);f.submit();"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>

        <!-- Notification Dropdown -->
        <div class="ent-notif-dropdown" id="entNotifDropdown">
            <div class="ent-notif-header">
                <span>Notifications</span>
                <a href="#" onclick="markAllNotifsRead();return false;" class="ent-notif-mark-read">Mark all read</a>
            </div>
            <div class="ent-notif-list" id="entNotifList">
                <div class="ent-notif-loading"><i class="fas fa-spinner fa-spin"></i> Loading...</div>
            </div>
            <a href="<?= htmlspecialchars($hDashboardUrl) ?>?view=notifications" class="ent-notif-footer">View All</a>
        </div>

        <!-- Task Dropdown -->
        <div class="ent-task-dropdown" id="entTaskDropdown">
            <div class="ent-task-header">
                <span>My Tasks</span>
                <span class="ent-task-count"><?= $hTaskCount ?> pending</span>
            </div>
            <div class="ent-task-list" id="entTaskList">
                <div class="ent-notif-loading"><i class="fas fa-spinner fa-spin"></i> Loading...</div>
            </div>
            <a href="<?= htmlspecialchars($hDashboardUrl) ?>?view=tasks" class="ent-task-footer">View All Tasks</a>
        </div>
    </div>
</header>

<style>
.ent-header{position:fixed;top:0;left:0;right:0;height:var(--ent-header-h,60px);background:linear-gradient(135deg,#0f172a 0%,#1e3a5f 100%);display:flex;align-items:center;justify-content:space-between;padding:0 20px;z-index:1000;box-shadow:0 2px 8px rgba(0,0,0,.15)}
.ent-header-left{display:flex;align-items:center;gap:16px}
.ent-sidebar-toggle{background:none;border:none;color:rgba(255,255,255,.8);font-size:18px;cursor:pointer;padding:6px;border-radius:6px;transition:all .2s}
.ent-sidebar-toggle:hover{background:rgba(255,255,255,.1);color:#fff}
.ent-header-brand{display:flex;align-items:center;gap:10px;text-decoration:none}
.ent-header-logo{width:36px;height:36px;border-radius:8px;border:1.5px solid rgba(255,255,255,.2);object-fit:cover}
.ent-header-brand-text{display:flex;flex-direction:column}
.ent-header-brand-name{color:#fff;font-size:15px;font-weight:700;letter-spacing:-.3px}
.ent-header-brand-sub{color:rgba(255,255,255,.5);font-size:10px}
.ent-header-center{display:flex;align-items:center}
.ent-header-breadcrumb{display:flex;align-items:center;gap:8px;color:rgba(255,255,255,.6);font-size:12px}
.ent-header-breadcrumb a{color:rgba(255,255,255,.6);text-decoration:none;transition:color .2s}
.ent-header-breadcrumb a:hover{color:#fff}
.ent-breadcrumb-sep{color:rgba(255,255,255,.3)}
.ent-breadcrumb-current{color:rgba(255,255,255,.9);font-weight:500}
.ent-header-right{display:flex;align-items:center;gap:8px}
.ent-header-search{position:relative;display:flex;align-items:center}
.ent-header-search i{position:absolute;left:10px;color:rgba(255,255,255,.4);font-size:12px}
.ent-header-search input{background:rgba(255,255,255,.1);border:1px solid rgba(255,255,255,.15);border-radius:20px;padding:6px 12px 6px 30px;color:#fff;font-size:12px;width:180px;transition:all .2s;outline:none}
.ent-header-search input::placeholder{color:rgba(255,255,255,.4)}
.ent-header-search input:focus{background:rgba(255,255,255,.15);border-color:rgba(255,255,255,.3);width:240px}
.ent-header-icon{position:relative;color:rgba(255,255,255,.7);font-size:16px;cursor:pointer;padding:8px;border-radius:8px;transition:all .2s}
.ent-header-icon:hover{background:rgba(255,255,255,.1);color:#fff}
.ent-badge{position:absolute;top:2px;right:2px;min-width:18px;height:18px;border-radius:9px;font-size:10px;font-weight:700;display:flex;align-items:center;justify-content:center;padding:0 4px;line-height:1}
.ent-badge-danger{background:#ef4444;color:#fff}
.ent-badge-warning{background:#f59e0b;color:#fff}
.ent-badge-info{background:#3b82f6;color:#fff}
.ent-badge-success{background:#10b981;color:#fff}
.ent-header-clock{color:rgba(255,255,255,.5);font-size:11px;font-variant-numeric:tabular-nums;padding:0 8px}
.ent-header-profile{display:flex;align-items:center;gap:8px;cursor:pointer;padding:4px 10px;border-radius:8px;transition:all .2s;color:#fff}
.ent-header-profile:hover{background:rgba(255,255,255,.1)}
.ent-header-avatar{width:32px;height:32px;border-radius:50%;border:1.5px solid rgba(255,255,255,.3);object-fit:cover}
.ent-header-profile-info{display:flex;flex-direction:column}
.ent-header-username{font-size:12px;font-weight:600;line-height:1.2}
.ent-header-role{font-size:10px;color:rgba(255,255,255,.5);line-height:1.2}
.ent-header-profile .fa-chevron-down{font-size:10px;color:rgba(255,255,255,.4);transition:transform .2s}

/* Profile Dropdown */
.ent-profile-dropdown{position:fixed;top:var(--ent-header-h,60px);right:20px;background:#fff;border-radius:12px;box-shadow:0 8px 30px rgba(0,0,0,.15);min-width:200px;padding:8px 0;z-index:1001;display:none;overflow:hidden}
.ent-profile-dropdown.show{display:block}
.ent-profile-dropdown a{display:flex;align-items:center;gap:10px;padding:10px 16px;color:#1e293b;text-decoration:none;font-size:13px;transition:background .15s}
.ent-profile-dropdown a:hover{background:#f1f5f9;color:#0f172a;text-decoration:none}
.ent-profile-dropdown a i{width:16px;text-align:center;color:#64748b}
.ent-dropdown-divider{height:1px;background:#e2e8f0;margin:4px 0}
.ent-logout-link{color:#ef4444 !important}
.ent-logout-link i{color:#ef4444 !important}

/* Notification Dropdown */
.ent-notif-dropdown,.ent-task-dropdown{position:fixed;top:var(--ent-header-h,60px);right:120px;background:#fff;border-radius:12px;box-shadow:0 8px 30px rgba(0,0,0,.15);width:360px;max-height:420px;z-index:1001;display:none;overflow:hidden}
.ent-notif-dropdown.show,.ent-task-dropdown.show{display:flex;flex-direction:column}
.ent-notif-header,.ent-task-header{display:flex;align-items:center;justify-content:space-between;padding:14px 16px;border-bottom:1px solid #e2e8f0;font-weight:600;font-size:14px;color:#0f172a}
.ent-notif-mark-read,.ent-task-count{font-size:11px;color:#3b82f6;font-weight:500;cursor:pointer;text-decoration:none}
.ent-notif-list,.ent-task-list{flex:1;overflow-y:auto;max-height:320px;padding:4px 0}
.ent-notif-item,.ent-task-item{display:flex;gap:10px;padding:10px 16px;cursor:pointer;transition:background .15s;border-bottom:1px solid #f1f5f9}
.ent-notif-item:hover,.ent-task-item:hover{background:#f8fafc}
.ent-notif-item.unread{background:#eff6ff}
.ent-notif-icon{width:36px;height:36px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:14px;flex-shrink:0}
.ent-notif-icon.type-info{background:#dbeafe;color:#2563eb}
.ent-notif-icon.type-warning{background:#fef3c7;color:#d97706}
.ent-notif-icon.type-success{background:#d1fae5;color:#059669}
.ent-notif-icon.type-danger{background:#fee2e2;color:#dc2626}
.ent-notif-body{flex:1;min-width:0}
.ent-notif-title{font-size:12px;font-weight:600;color:#0f172a;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.ent-notif-msg{font-size:11px;color:#64748b;margin-top:2px;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
.ent-notif-time{font-size:10px;color:#94a3b8;margin-top:2px}
.ent-notif-loading{padding:30px;text-align:center;color:#94a3b8;font-size:13px}
.ent-notif-footer,.ent-task-footer{display:block;text-align:center;padding:12px;color:#3b82f6;font-size:12px;font-weight:600;text-decoration:none;border-top:1px solid #e2e8f0;transition:background .15s}
.ent-notif-footer:hover,.ent-task-footer:hover{background:#f8fafc;color:#2563eb;text-decoration:none}

/* Task items */
.ent-task-item{flex-direction:column;gap:4px}
.ent-task-top{display:flex;align-items:center;justify-content:space-between}
.ent-task-title{font-size:12px;font-weight:600;color:#0f172a}
.ent-task-priority{font-size:10px;padding:2px 6px;border-radius:4px;font-weight:600}
.ent-task-priority.low{background:#d1fae5;color:#065f46}
.ent-task-priority.medium{background:#dbeafe;color:#1e40af}
.ent-task-priority.high{background:#fef3c7;color:#92400e}
.ent-task-priority.urgent{background:#fee2e2;color:#991b1b}
.ent-task-due{font-size:10px;color:#94a3b8}

@media(max-width:768px){
    .ent-header-search,.ent-header-clock,.ent-header-breadcrumb{display:none}
    .ent-header-profile-info{display:none}
    .ent-notif-dropdown,.ent-task-dropdown{width:calc(100vw - 20px);right:10px}
}
</style>

<script>
(function(){
    // Clock
    function entUpdateClock(){
        var el=document.getElementById('entHeaderClock');
        if(!el)return;
        el.textContent=new Date().toLocaleTimeString('en-UG',{hour:'2-digit',minute:'2-digit',second:'2-digit'});
    }
    entUpdateClock();
    setInterval(entUpdateClock,1000);

    // Profile dropdown toggle
    var profileToggle=document.getElementById('entProfileToggle');
    var profileDrop=document.getElementById('entProfileDropdown');
    if(profileToggle&&profileDrop){
        profileToggle.addEventListener('click',function(e){
            e.stopPropagation();
            profileDrop.classList.toggle('show');
            var nd=document.getElementById('entNotifDropdown');if(nd)nd.classList.remove('show');
            var td=document.getElementById('entTaskDropdown');if(td)td.classList.remove('show');
        });
    }

    // Notification dropdown toggle
    var notifToggle=document.getElementById('entNotifToggle');
    var notifDrop=document.getElementById('entNotifDropdown');
    if(notifToggle&&notifDrop){
        notifToggle.addEventListener('click',function(e){
            e.stopPropagation();
            notifDrop.classList.toggle('show');
            if(profileDrop)profileDrop.classList.remove('show');
            var td=document.getElementById('entTaskDropdown');if(td)td.classList.remove('show');
            if(notifDrop.classList.contains('show'))loadNotifications();
        });
    }

    // Task dropdown toggle
    var taskToggle=document.getElementById('entTaskToggle');
    var taskDrop=document.getElementById('entTaskDropdown');
    if(taskToggle&&taskDrop){
        taskToggle.addEventListener('click',function(e){
            e.stopPropagation();
            taskDrop.classList.toggle('show');
            if(profileDrop)profileDrop.classList.remove('show');
            var nd=document.getElementById('entNotifDropdown');if(nd)nd.classList.remove('show');
            if(taskDrop.classList.contains('show'))loadTasks();
        });
    }

    // Close on outside click
    document.addEventListener('click',function(){
        if(profileDrop)profileDrop.classList.remove('show');
        if(notifDrop)notifDrop.classList.remove('show');
        if(taskDrop)taskDrop.classList.remove('show');
    });

    // Sidebar toggle
    var sidebarToggle=document.getElementById('entSidebarToggle');
    if(sidebarToggle){
        sidebarToggle.addEventListener('click',function(){
            var sidebar=document.getElementById('isnmSidebar');
            if(sidebar)sidebar.classList.toggle('collapsed');
            var main=document.querySelector('.ent-main-content,.main-content');
            if(main)main.classList.toggle('sidebar-collapsed');
        });
    }

    // Load notifications via AJAX
    function loadNotifications(){
        var list=document.getElementById('entNotifList');
        if(!list)return;
        list.innerHTML='<div class="ent-notif-loading"><i class="fas fa-spinner fa-spin"></i> Loading...</div>';
        fetch('../includes/ajax_notifications.php?action=get_notifications&limit=10')
            .then(function(r){return r.text()})
            .then(function(t){return JSON.parse(t.replace(/^\uFEFF/,''))})
            .catch(function(){return {success:false,notifications:[]}})
            .then(function(data){
                if(!data||!data.success||!data.notifications||data.notifications.length===0){
                    list.innerHTML='<div class="ent-notif-loading"><i class="fas fa-check-circle" style="color:#10b981"></i> No new notifications</div>';
                    return;
                }
                var html='';
                data.notifications.forEach(function(n){
                    var iconClass='type-info';
                    if(n.type==='warning')iconClass='type-warning';
                    else if(n.type==='success')iconClass='type-success';
                    else if(n.type==='error'||n.type==='danger')iconClass='type-danger';
                    var icon='fas fa-info-circle';
                    if(n.type==='warning')icon='fas fa-exclamation-triangle';
                    else if(n.type==='success')icon='fas fa-check-circle';
                    else if(n.type==='error'||n.type==='danger')icon='fas fa-times-circle';
                    html+='<div class="ent-notif-item'+(n.read_at?'':' unread')+'">';
                    html+='<div class="ent-notif-icon '+iconClass+'"><i class="'+icon+'"></i></div>';
                    html+='<div class="ent-notif-body">';
                    html+='<div class="ent-notif-title">'+escHtml(n.title)+'</div>';
                    html+='<div class="ent-notif-msg">'+escHtml(n.message||'')+'</div>';
                    html+='<div class="ent-notif-time">'+escHtml(n.time_ago||n.created_at||'')+'</div>';
                    html+='</div></div>';
                });
                list.innerHTML=html;
            })
            .catch(function(){list.innerHTML='<div class="ent-notif-loading"><i class="fas fa-exclamation-triangle"></i> Error loading</div>'});
    }

    // Load tasks via AJAX
    function loadTasks(){
        var list=document.getElementById('entTaskList');
        if(!list)return;
        list.innerHTML='<div class="ent-notif-loading"><i class="fas fa-spinner fa-spin"></i> Loading...</div>';
        fetch('../includes/ajax_task_handler.php?action=get_my_tasks&limit=10')
            .then(function(r){return r.text()})
            .then(function(t){return JSON.parse(t.replace(/^\uFEFF/,''))})
            .then(function(data){
                if(!data||!data.success||!data.tasks||data.tasks.length===0){
                    list.innerHTML='<div class="ent-notif-loading"><i class="fas fa-check-circle" style="color:#10b981"></i> No pending tasks</div>';
                    return;
                }
                var html='';
                data.tasks.forEach(function(t){
                    html+='<div class="ent-task-item">';
                    html+='<div class="ent-task-top">';
                    html+='<span class="ent-task-title">'+escHtml(t.title)+'</span>';
                    html+='<span class="ent-task-priority '+escHtml(t.priority)+'">'+escHtml(t.priority)+'</span>';
                    html+='</div>';
                    html+='<div class="ent-task-due">'+(t.due_date?'Due: '+escHtml(t.due_date):'No deadline')+'</div>';
                    html+='</div>';
                });
                list.innerHTML=html;
            })
            .catch(function(){list.innerHTML='<div class="ent-notif-loading"><i class="fas fa-exclamation-triangle"></i> Error loading</div>'});
    }

    window.markAllNotifsRead=function(){
        var fd = new FormData();
        fd.append('csrf_token', window.CSRF_TOKEN || '');
        fetch('../includes/ajax_notifications.php?action=mark_all_read',{method:'POST',body:fd})
            .then(function(){loadNotifications();var b=document.querySelector('.ent-badge-danger');if(b)b.remove();})
            .catch(function(){});
    };

    function escHtml(s){var d=document.createElement('div');d.appendChild(document.createTextNode(s));return d.innerHTML;}
})();
</script>
