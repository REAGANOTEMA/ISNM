<?php
/**
 * ISNM DYNAMIC SIDEBAR — CLEAN ERP LAYOUT
 * 9 departments, role-filtered, searchable, collapsible.
 * Single source of truth: system_modules + module_permissions (DB).
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
        'School Secretary' => 10, 'School Librarian' => 11, 'Head of Nursing' => 12,
        'Head of Midwifery' => 13, 'Senior Lecturer' => 14, 'Lecturer' => 15,
        'Matron' => 16, 'Warden' => 17, 'Sickbay Nurse' => 18, 'Driver' => 19,
        'Security Officer' => 20, 'Storekeeper' => 21, 'Guild President' => 22,
        'Computer Lab Manager' => 23, 'School Bursar' => 24, 'Store Keeper' => 25,
        'Director Admissions' => 26, 'Bursar' => 27, 'Director Admissions & Requirements' => 26,
        'System Administrator' => 38, 'Skills Lab Technician' => 40,
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
    $currentSection = $_GET['section'] ?? '';
    ?>
    <nav class="isnm-sidebar" id="isnmSidebar">
        <div class="sidebar-brand">
            <button class="sidebar-collapse-btn" id="sidebarCollapse"><i class="fas fa-bars"></i></button>
            <?php if (file_exists(__DIR__ . '/../images/school-logo.png')): ?>
                <img src="../images/school-logo.png" alt="ISNM" class="brand-logo">
            <?php endif; ?>
            <div class="brand-text">
                <span class="brand-name">ISNM</span>
                <span class="brand-sub">ERP System</span>
            </div>
        </div>

        <div class="sidebar-user" onclick="<?= $profileClick ?>" style="cursor:pointer">
            <div class="user-avatar-wrap">
                <img src="<?= htmlspecialchars($profileImage) ?>" alt="" class="user-avatar">
                <span class="user-dot"></span>
            </div>
            <div class="user-meta">
                <div class="user-fullname"><?= htmlspecialchars($userName) ?></div>
                <div class="user-rolename"><?= htmlspecialchars($userRole) ?></div>
            </div>
        </div>

        <div class="sidebar-search">
            <i class="fas fa-search"></i>
            <input type="text" id="sidebarSearch" placeholder="Search modules..." oninput="filterSidebarModules(this.value)">
        </div>

        <div class="sidebar-menu" id="sidebarMenu">
            <?php foreach ($sidebar as $deptKey => $dept): ?>
                <?php
                    $groupId = preg_replace('/[^a-z0-9]/', '', strtolower($deptKey));
                    $hasActive = false;
                    foreach ($dept['modules'] as $mod) {
                        if ($mod['name'] === $currentSection) { $hasActive = true; break; }
                    }
                ?>
                <div class="menu-group <?= $hasActive ? 'expanded' : '' ?>" data-group="<?= $groupId ?>">
                    <div class="menu-group-header" data-target="<?= $groupId ?>">
                        <span class="menu-icon"><i class="fas fa-<?= htmlspecialchars($dept['icon']) ?>" style="color:<?= htmlspecialchars($dept['color']) ?>"></i></span>
                        <span class="menu-label"><?= htmlspecialchars($dept['label']) ?></span>
                        <span class="menu-count"><?= count($dept['modules']) ?></span>
                        <span class="menu-chevron"><i class="fas fa-chevron-down"></i></span>
                    </div>
                    <div class="menu-children" id="childGroup-<?= $groupId ?>" style="<?= $hasActive ? '' : 'max-height:0;' ?>">
                        <div class="menu-children-inner">
                            <?php foreach ($dept['modules'] as $mod): ?>
                                <a href="<?= htmlspecialchars($mod['route']) ?>"
                                   class="child-link <?= $mod['name'] === $currentSection ? 'active' : '' ?>"
                                   data-module="<?= htmlspecialchars($mod['name']) ?>"
                                   onclick="return loadModuleContent('<?= htmlspecialchars($mod['name']) ?>','<?= htmlspecialchars($mod['route']) ?>',this);"
                                   title="<?= htmlspecialchars($mod['description'] ?? $mod['label']) ?>">
                                    <span class="child-bullet"></span>
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

    <style>
    .sidebar-search{padding:8px 12px;position:relative}
    .sidebar-search i{position:absolute;left:22px;top:50%;transform:translateY(-50%);color:#94a3b8;font-size:11px}
    .sidebar-search input{width:100%;padding:7px 10px 7px 30px;border:1px solid #e2e8f0;border-radius:6px;font-size:12px;background:#f8fafc}
    .sidebar-search input:focus{outline:none;border-color:#3b82f6;background:#fff}
    .menu-count{font-size:10px;background:rgba(255,255,255,0.15);color:#94a3b8;padding:1px 6px;border-radius:10px;margin-left:auto}
    .menu-group.search-hidden{display:none}
    .child-link.search-hidden{display:none}
    </style>

    <script>
    function loadModuleContent(m,route,el){
        document.querySelectorAll('.child-link').forEach(function(a){a.classList.remove('active')});
        el.classList.add('active');
        var c=document.getElementById('moduleContentArea')||document.querySelector('.ent-content-area')||document.querySelector('.page-content');
        if(!c)return true;
        c.innerHTML='<div style="display:flex;flex-direction:column;align-items:center;justify-content:center;padding:60px;color:#64748b"><div style="width:36px;height:36px;border:3px solid #e2e8f0;border-top-color:#3b82f6;border-radius:50%;animation:spin .8s linear infinite;margin-bottom:12px"></div><p>Loading '+m+'...</p></div>';
        fetch(route,{headers:{'X-Requested-With':'XMLHttpRequest','X-Module-Name':m}})
        .then(function(r){return r.text()})
        .then(function(h){c.innerHTML=h;c.classList.add('erp-animate');setTimeout(function(){c.classList.remove('erp-animate')},300);c.querySelectorAll('script').forEach(function(s){var n=document.createElement('script');if(s.src)n.src=s.src;else n.textContent=s.textContent;s.parentNode.replaceChild(n,s)});history.pushState({module:m},'', '?section='+m)})
        .catch(function(){window.location.href=route});
        if(window.innerWidth<=768){var sb=document.querySelector('.isnm-sidebar');if(sb)sb.classList.remove('open')}
        return false;
    }
    function filterSidebarModules(q){
        q=q.toLowerCase();
        document.querySelectorAll('.menu-group[data-group]').forEach(function(g){
            var v=false;
            g.querySelectorAll('.child-link').forEach(function(a){
                var t=(a.querySelector('.child-label')||{}).textContent||'';
                var n=a.getAttribute('data-module')||'';
                if(t.toLowerCase().indexOf(q)!==-1||n.toLowerCase().indexOf(q)!==-1){a.classList.remove('search-hidden');v=true}else{a.classList.add('search-hidden')}
            });
            if(q&&v){g.classList.add('expanded');var ch=g.querySelector('.menu-children');if(ch)ch.style.maxHeight='none'}
            g.classList.toggle('search-hidden',!v&&q!=='');
        });
    }
    window.addEventListener('popstate',function(e){
        if(e.state&&e.state.module){var a=document.querySelector('[data-module="'+e.state.module+'"]');if(a)loadModuleContent(e.state.module,a.getAttribute('href'),a)}
    });
    var s=document.createElement('style');s.textContent='@keyframes spin{to{transform:rotate(360deg)}}';document.head.appendChild(s);
    </script>
    <?php
}
}
