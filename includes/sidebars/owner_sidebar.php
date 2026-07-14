<?php
$currentUser = $_SESSION['full_name'] ?? ($_SESSION['first_name'] . ' ' . $_SESSION['last_name']) ?? 'Owner';
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
$userInitial = strtoupper(substr($currentUser, 0, 1));
?>
<aside class="own-sidebar" id="ownSidebar">
  <div class="sidebar-header">
    <div class="sidebar-brand">
      <img src="../images/school-logo.png" alt="ISNM">
      <span class="brand-text">ISNM ERP</span>
    </div>
    <button class="sidebar-close-btn" id="ownSidebarClose" aria-label="Close sidebar">
      <i class="fas fa-times"></i>
    </button>
  </div>
  <div class="sidebar-user">
    <div class="user-avatar"><?= $userInitial ?></div>
    <div class="user-info">
      <div class="user-name"><?= htmlspecialchars($currentUser) ?></div>
      <div class="user-role">Owner</div>
    </div>
  </div>
  <nav class="sidebar-nav">
    <div class="nav-section">
      <div class="nav-section-title">Main</div>
      <a href="index.php" class="nav-item <?= $currentPage === 'index' ? 'active' : '' ?>">
        <i class="fas fa-tachometer-alt"></i><span>Dashboard</span>
      </a>
      <a href="teacher-list.php" class="nav-item <?= $currentPage === 'teacher-list' ? 'active' : '' ?>">
        <i class="fas fa-chalkboard-teacher"></i><span>Teachers</span>
      </a>
      <a href="student-list.php" class="nav-item <?= $currentPage === 'student-list' ? 'active' : '' ?>">
        <i class="fas fa-user-graduate"></i><span>Students</span>
      </a>
      <a href="student-attendence.php" class="nav-item <?= $currentPage === 'student-attendence' ? 'active' : '' ?>">
        <i class="fas fa-calendar-check"></i><span>Attendance</span>
      </a>
    </div>
    <div class="nav-section">
      <div class="nav-section-title">Finance</div>
      <a href="make-payment.php" class="nav-item <?= $currentPage === 'make-payment' ? 'active' : '' ?>">
        <i class="fas fa-money-bill-wave"></i><span>Payroll</span>
      </a>
      <a href="see-payment.php" class="nav-item <?= $currentPage === 'see-payment' ? 'active' : '' ?>">
        <i class="fas fa-receipt"></i><span>See Payments</span>
      </a>
      <a href="qr.php" class="nav-item <?= $currentPage === 'qr' ? 'active' : '' ?>">
        <i class="fas fa-qrcode"></i><span>QR Payment</span>
      </a>
    </div>
    <div class="nav-section">
      <div class="nav-section-title">Communication</div>
      <a href="notices.php" class="nav-item <?= $currentPage === 'notices' ? 'active' : '' ?>">
        <i class="fas fa-bullhorn"></i><span>Notices</span>
      </a>
    </div>
    <div class="nav-section">
      <div class="nav-section-title">Account</div>
      <a href="change-password.php" class="nav-item <?= $currentPage === 'change-password' ? 'active' : '' ?>">
        <i class="fas fa-key"></i><span>Change Password</span>
      </a>
      <a href="#" class="nav-item" onclick="event.preventDefault();var f=document.createElement('form');f.method='POST';f.action='../logout.php';document.body.appendChild(f);f.submit();">
        <i class="fas fa-sign-out-alt"></i><span>Logout</span>
      </a>
    </div>
  </nav>
</aside>
<div class="own-sidebar-overlay" id="ownSidebarOverlay"></div>
<style>
.own-sidebar{position:fixed;left:0;top:0;width:270px;height:100vh;background:linear-gradient(180deg,#1a1a2e 0%,#16213e 100%);z-index:1000;display:flex;flex-direction:column;overflow:hidden;transition:transform 0.3s cubic-bezier(0.4,0,0.2,1);box-shadow:4px 0 30px rgba(0,0,0,0.15)}
.own-sidebar .sidebar-header{padding:16px 20px;display:flex;align-items:center;justify-content:space-between;border-bottom:1px solid rgba(255,255,255,0.06)}
.own-sidebar .sidebar-brand{display:flex;align-items:center;gap:12px}
.own-sidebar .sidebar-brand img{width:36px;height:36px;border-radius:8px}
.own-sidebar .brand-text{font-size:20px;font-weight:700;color:#fff;letter-spacing:1px}
.own-sidebar .sidebar-close-btn{background:rgba(255,255,255,0.08);border:none;color:rgba(255,255,255,0.6);width:32px;height:32px;border-radius:8px;cursor:pointer;display:none;align-items:center;justify-content:center;font-size:16px;transition:all 0.3s}
.own-sidebar .sidebar-close-btn:hover{background:rgba(255,255,255,0.15);color:#fff}
.own-sidebar .sidebar-user{padding:20px;display:flex;align-items:center;gap:14px;border-bottom:1px solid rgba(255,255,255,0.06)}
.own-sidebar .user-avatar{width:44px;height:44px;border-radius:12px;background:linear-gradient(135deg,#667eea,#764ba2);display:flex;align-items:center;justify-content:center;color:#fff;font-size:18px;font-weight:700;flex-shrink:0}
.own-sidebar .user-info{min-width:0}
.own-sidebar .user-name{color:#fff;font-size:14px;font-weight:600;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.own-sidebar .user-role{color:rgba(255,255,255,0.5);font-size:11px;text-transform:uppercase;letter-spacing:0.5px;margin-top:2px}
.own-sidebar .sidebar-nav{flex:1;overflow-y:auto;padding:12px 0}
.own-sidebar .sidebar-nav::-webkit-scrollbar{width:4px}
.own-sidebar .sidebar-nav::-webkit-scrollbar-thumb{background:rgba(255,255,255,0.1);border-radius:2px}
.own-sidebar .nav-section{padding:4px 0}
.own-sidebar .nav-section-title{padding:8px 20px 6px;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:1.5px;color:rgba(255,255,255,0.25)}
.own-sidebar .nav-item{display:flex;align-items:center;gap:14px;padding:10px 20px;color:rgba(255,255,255,0.65);text-decoration:none;font-size:13.5px;font-weight:500;transition:all 0.25s;border-left:3px solid transparent;position:relative}
.own-sidebar .nav-item i{width:20px;text-align:center;font-size:15px;flex-shrink:0}
.own-sidebar .nav-item:hover{color:#fff;background:rgba(255,255,255,0.05);padding-left:24px}
.own-sidebar .nav-item.active{color:#fff;background:linear-gradient(90deg,rgba(102,126,234,0.15),transparent);border-left-color:#667eea}
.own-sidebar .nav-item.active::after{content:'';position:absolute;right:0;top:50%;transform:translateY(-50%);width:3px;height:20px;background:#667eea;border-radius:3px 0 0 3px}
.own-sidebar-overlay{position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:999;display:none;opacity:0;transition:opacity 0.3s}
.own-sidebar-overlay.active{display:block;opacity:1}
@media(max-width:991px){.own-sidebar{transform:translateX(-100%)}.own-sidebar.active{transform:translateX(0)}.own-sidebar .sidebar-close-btn{display:flex}.own-sidebar-overlay.active{display:block}.own-sidebar-overlay{display:none}}
@media(min-width:992px){.own-sidebar-overlay{display:none!important}}
@media(min-width:992px){body{padding-left:270px}}@media(max-width:991px){body{padding-left:0}}
</style>
<script>
document.addEventListener('DOMContentLoaded',function(){var s=document.getElementById('ownSidebar'),c=document.getElementById('ownSidebarClose'),o=document.getElementById('ownSidebarOverlay');function t(){s.classList.toggle('active');o.classList.toggle('active')}c&&c.addEventListener('click',t);o&&o.addEventListener('click',t);var h=document.querySelector('.navbar-toggler');h&&h.addEventListener('click',function(e){window.innerWidth<992&&(e.preventDefault(),t())})})
</script>
