<?php
$currentUser = $_SESSION['full_name'] ?? ($_SESSION['first_name'] . ' ' . $_SESSION['last_name']) ?? 'Staff';
$currentRole = $_SESSION['role'] ?? 'staff';
$currentLevel = $_SESSION['access_level'] ?? 1;
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
$userInitial = strtoupper(substr($currentUser, 0, 1));
?>
<aside class="isnm-sidebar" id="isnmSidebar">
  <div class="sidebar-header">
    <div class="sidebar-brand">
      <img src="images/school-logo.png" alt="ISNM">
      <span class="brand-text">ISNM</span>
    </div>
    <button class="sidebar-close-btn" id="sidebarClose" aria-label="Close sidebar">
      <i class="fas fa-times"></i>
    </button>
  </div>
  <div class="sidebar-user">
    <div class="user-avatar"><?= $userInitial ?></div>
    <div class="user-info">
      <div class="user-name"><?= htmlspecialchars($currentUser) ?></div>
      <div class="user-role"><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $currentRole))) ?></div>
    </div>
  </div>
  <nav class="sidebar-nav">
    <div class="nav-section">
      <div class="nav-section-title">Main</div>
      <a href="dashboard.php" class="nav-item <?= $currentPage === 'dashboard' ? 'active' : '' ?>">
        <i class="fas fa-tachometer-alt"></i><span>Dashboard</span>
      </a>
      <a href="student_accounts_management.php" class="nav-item <?= $currentPage === 'student_accounts_management' ? 'active' : '' ?>">
        <i class="fas fa-users"></i><span>Student Accounts</span>
      </a>
      <a href="student_profile.php" class="nav-item <?= $currentPage === 'student_profile' ? 'active' : '' ?>">
        <i class="fas fa-user-graduate"></i><span>Student Profile</span>
      </a>
      <a href="student_communication_system.php" class="nav-item <?= $currentPage === 'student_communication_system' ? 'active' : '' ?>">
        <i class="fas fa-comments"></i><span>Communication</span>
      </a>
      <a href="application.php" class="nav-item <?= $currentPage === 'application' ? 'active' : '' ?>">
        <i class="fas fa-file-alt"></i><span>Applications</span>
      </a>
    </div>
    <div class="nav-section">
      <div class="nav-section-title">Reports</div>
      <a href="student_financial_portal.php" class="nav-item <?= $currentPage === 'student_financial_portal' ? 'active' : '' ?>">
        <i class="fas fa-credit-card"></i><span>Financial Portal</span>
      </a>
    </div>
    <div class="nav-section">
      <div class="nav-section-title">Account</div>
      <a href="includes/profile_settings.php" class="nav-item">
        <i class="fas fa-user-cog"></i><span>Profile Settings</span>
      </a>
      <a href="logout.php" class="nav-item">
        <i class="fas fa-sign-out-alt"></i><span>Logout</span>
      </a>
    </div>
  </nav>
</aside>
<div class="sidebar-overlay" id="sidebarOverlay"></div>
<style>
.isnm-sidebar{position:fixed;left:0;top:0;width:270px;height:100vh;background:linear-gradient(180deg,#1a1a2e 0%,#16213e 100%);z-index:1000;display:flex;flex-direction:column;overflow:hidden;transition:transform 0.3s cubic-bezier(0.4,0,0.2,1);box-shadow:4px 0 30px rgba(0,0,0,0.15)}
.isnm-sidebar .sidebar-header{padding:16px 20px;display:flex;align-items:center;justify-content:space-between;border-bottom:1px solid rgba(255,255,255,0.06)}
.isnm-sidebar .sidebar-brand{display:flex;align-items:center;gap:12px}
.isnm-sidebar .sidebar-brand img{width:36px;height:36px;border-radius:8px}
.isnm-sidebar .brand-text{font-size:20px;font-weight:700;color:#fff;letter-spacing:1px}
.isnm-sidebar .sidebar-close-btn{background:rgba(255,255,255,0.08);border:none;color:rgba(255,255,255,0.6);width:32px;height:32px;border-radius:8px;cursor:pointer;display:none;align-items:center;justify-content:center;font-size:16px;transition:all 0.3s}
.isnm-sidebar .sidebar-close-btn:hover{background:rgba(255,255,255,0.15);color:#fff}
.isnm-sidebar .sidebar-user{padding:20px;display:flex;align-items:center;gap:14px;border-bottom:1px solid rgba(255,255,255,0.06)}
.isnm-sidebar .user-avatar{width:44px;height:44px;border-radius:12px;background:linear-gradient(135deg,#667eea,#764ba2);display:flex;align-items:center;justify-content:center;color:#fff;font-size:18px;font-weight:700;flex-shrink:0}
.isnm-sidebar .user-info{min-width:0}
.isnm-sidebar .user-name{color:#fff;font-size:14px;font-weight:600;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.isnm-sidebar .user-role{color:rgba(255,255,255,0.5);font-size:11px;text-transform:uppercase;letter-spacing:0.5px;margin-top:2px}
.isnm-sidebar .sidebar-nav{flex:1;overflow-y:auto;padding:12px 0}
.isnm-sidebar .sidebar-nav::-webkit-scrollbar{width:4px}
.isnm-sidebar .sidebar-nav::-webkit-scrollbar-thumb{background:rgba(255,255,255,0.1);border-radius:2px}
.isnm-sidebar .nav-section{padding:4px 0}
.isnm-sidebar .nav-section-title{padding:8px 20px 6px;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:1.5px;color:rgba(255,255,255,0.25)}
.isnm-sidebar .nav-item{display:flex;align-items:center;gap:14px;padding:10px 20px;color:rgba(255,255,255,0.65);text-decoration:none;font-size:13.5px;font-weight:500;transition:all 0.25s;border-left:3px solid transparent;position:relative}
.isnm-sidebar .nav-item i{width:20px;text-align:center;font-size:15px;flex-shrink:0}
.isnm-sidebar .nav-item:hover{color:#fff;background:rgba(255,255,255,0.05);padding-left:24px}
.isnm-sidebar .nav-item.active{color:#fff;background:linear-gradient(90deg,rgba(102,126,234,0.15),transparent);border-left-color:#667eea}
.isnm-sidebar .nav-item.active::after{content:'';position:absolute;right:0;top:50%;transform:translateY(-50%);width:3px;height:20px;background:#667eea;border-radius:3px 0 0 3px}
.sidebar-overlay{position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:999;display:none;opacity:0;transition:opacity 0.3s}
.sidebar-overlay.active{display:block;opacity:1}
@media(max-width:991px){.isnm-sidebar{transform:translateX(-100%)}.isnm-sidebar.active{transform:translateX(0)}.isnm-sidebar .sidebar-close-btn{display:flex}.sidebar-overlay.active{display:block}.sidebar-overlay{display:none}}
@media(min-width:992px){.sidebar-overlay{display:none!important}}
body.sidebar-open .dashboard-container{padding-left:290px}@media(max-width:991px){body.sidebar-open .dashboard-container{padding-left:20px}}
</style>
<script>
document.addEventListener('DOMContentLoaded',function(){var s=document.getElementById('isnmSidebar'),c=document.getElementById('sidebarClose'),o=document.getElementById('sidebarOverlay');function t(){s.classList.toggle('active');o.classList.toggle('active');document.body.classList.toggle('sidebar-open')}if(c)c.addEventListener('click',t);if(o)o.addEventListener('click',t);var h=document.querySelector('.navbar-toggler');if(h)h.addEventListener('click',function(e){if(window.innerWidth<992){e.preventDefault();t()}})})
</script>
