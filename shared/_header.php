<?php
require_once __DIR__ . '/../includes/config_enhanced.php';
include_once __DIR__ . '/../includes/functions.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes, shrink-to-fit=no, viewport-fit=cover">
  <meta name="mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="default">
  <meta name="apple-mobile-web-app-title" content="ISNM">
  <meta name="application-name" content="ISNM">
  <meta name="theme-color" content="#3E2723">
  <meta name="msapplication-TileColor" content="#3E2723">
  <meta name="msapplication-config" content="/browserconfig.xml">
  <meta name="apple-touch-fullscreen" content="yes">
  <link rel="manifest" href="manifest.json">
  <title><?php echo isset($pageTitle) ? $pageTitle : 'Iganga School of Nursing and Midwifery'; ?></title>
  <meta name="description" content="Iganga School of Nursing and Midwifery - Quality Healthcare Education in Uganda">
  <meta name="keywords" content="nursing school, midwifery, healthcare education, ISNM, Uganda">
  <meta name="author" content="Iganga School of Nursing and Midwifery">
  
  <!-- Favicon and Apple Touch Icons -->
  <link rel="icon" type="image/x-icon" href="images/school-logo.png">
  <link rel="apple-touch-icon" href="images/school-logo.png">
  <link rel="apple-touch-icon" sizes="152x152" href="images/school-logo.png">
  <link rel="apple-touch-icon" sizes="180x180" href="images/school-logo.png">
  <link rel="icon" type="image/png" sizes="32x32" href="images/school-logo.png">
  <link rel="icon" type="image/png" sizes="16x16" href="images/school-logo.png">
    
  <!-- 3D Buttons CSS -->
  <link rel="stylesheet" href="css/3d-buttons.css">

  <!-- Preconnect for faster CDN resource loading -->
  <link rel="preconnect" href="https://cdn.jsdelivr.net">
  <link rel="preconnect" href="https://cdnjs.cloudflare.com">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  
  <!-- Bootstrap CSS -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
  
  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  
  <!-- Google Fonts - Professional & Clean -->
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Playfair+Display:wght@400;600;700;900&family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  
  <!-- Custom CSS (with cache-busting version) -->
  <link rel="stylesheet" href="shared/style.css?v=2" />
  <link rel="stylesheet" href="css/isnm-style.css?v=3" />
  <link rel="stylesheet" href="css/responsive.css?v=2" />
  <link rel="stylesheet" href="css/navigation.css?v=5" />
  <link rel="stylesheet" href="css/animations.css?v=2" />
  <link rel="stylesheet" href="css/polish.css?v=1" />
  <link rel="stylesheet" href="css/mobile-fixes.css?v=1" />
</head>

<body>

<!-- ISNM Header -->
<header class="isnm-header">
  <div class="header-container">
    <div class="header-logo">
      <a href="index.php" class="logo-link">
        <img src="images/school-logo.png" alt="ISNM Logo" class="logo-img">
      </a>
    </div>
    
    <div class="header-title">
      <h1 class="school-title">Iganga School of Nursing and Midwifery</h1>
      <p class="school-motto">"Chosen to Serve, Based on a disciplined mind for health action"</p>
    </div>
    
    <div class="header-logo">
      <a href="index.php" class="logo-link">
        <img src="images/school-logo.png" alt="ISNM Logo" class="logo-img">
      </a>
    </div>
  </div>
</header>

<!-- Navigation Bar -->
<nav class="navbar navbar-expand-lg isnm-navbar sticky-top">
  <div class="container">
    <a class="navbar-brand d-lg-none" href="index.php">
      <img src="images/school-logo.png" alt="ISNM Logo" class="brand-logo">
      <span class="brand-name">ISNM</span>
    </a>
    
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar" aria-controls="mainNavbar" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    
    <div class="collapse navbar-collapse" id="mainNavbar">
      <ul class="navbar-nav">
        <li class="nav-item">
          <a class="nav-link" href="index.php">
            <i class="fas fa-home"></i> Home
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="about.php">
            <i class="fas fa-info-circle"></i> About
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="history.php">
            <i class="fas fa-history"></i> History
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="programs.php">
            <i class="fas fa-graduation-cap"></i> Programs
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="news.php">
            <i class="fas fa-newspaper"></i> News
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="donation.php">
            <i class="fas fa-hand-holding-heart"></i> Donate
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="volunteer.php">
            <i class="fas fa-hands-helping"></i> Volunteer
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="contact.php">
            <i class="fas fa-envelope"></i> Contact
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="organogram.php">
            <i class="fas fa-sitemap"></i> Portal
          </a>
        </li>
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" id="loginDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="fas fa-sign-in-alt"></i> Login
          </a>
          <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="loginDropdown">
            <li><a class="dropdown-item" href="student-login.php">
              <i class="fas fa-user-graduate"></i> Student Login
            </a></li>
            <li><a class="dropdown-item" href="staff-login.php">
              <i class="fas fa-user-tie"></i> Staff Login
            </a></li>
            <li><a class="dropdown-item" href="student-directory.php">
              <i class="fas fa-address-book"></i> Student Directory
            </a></li>
            <li><a class="dropdown-item" href="application.php">
              <i class="fas fa-edit"></i> Apply Now
            </a></li>
          </ul>
        </li>
      </ul>
    </div>
  </div>
</nav>
<?php if (session_status() === PHP_SESSION_NONE) @session_start(); ?>
<?php if (isset($_SESSION['success_message'])): ?>
<div class="container mt-3">
    <div class="alert alert-success alert-dismissible fade show d-flex align-items-center gap-2 shadow-sm rounded-3" role="alert">
        <i class="fas fa-check-circle fs-5"></i>
        <span><?= htmlspecialchars($_SESSION['success_message']) ?></span>
        <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
    </div>
</div>
<?php unset($_SESSION['success_message']); endif; ?>
<?php if (isset($_SESSION['error_message'])): ?>
<div class="container mt-3">
    <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center gap-2 shadow-sm rounded-3" role="alert">
        <i class="fas fa-exclamation-circle fs-5"></i>
        <span><?= htmlspecialchars($_SESSION['error_message']) ?></span>
        <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
    </div>
</div>
<?php unset($_SESSION['error_message']); endif; ?>

<script>
// Navigation scroll effect
document.addEventListener('DOMContentLoaded', function() {
  var nav = document.querySelector('.isnm-navbar');
  if (!nav) return;
  
  function onScroll() {
    if (window.scrollY > 30) {
      nav.classList.add('scrolled');
    } else {
      nav.classList.remove('scrolled');
    }
  }
  
  window.addEventListener('scroll', onScroll, {passive: true});
  onScroll();

  var collapse = document.getElementById('mainNavbar');
  var toggler = document.querySelector('.navbar-toggler');
  if (collapse) {
    function hideMenu() {
      if (collapse.classList.contains('show')) {
        collapse.classList.remove('show');
        if (toggler) {
          toggler.classList.add('collapsed');
          toggler.setAttribute('aria-expanded', 'false');
        }
      }
      document.body.classList.remove('menu-open');
    }
    collapse.addEventListener('shown.bs.collapse', function() { document.body.classList.add('menu-open'); });
    collapse.addEventListener('hidden.bs.collapse', function() { document.body.classList.remove('menu-open'); });
    // Close menu instantly when tapping a nav link (before page navigation)
    collapse.querySelectorAll('.nav-link:not(.dropdown-toggle)').forEach(function(l) {
      l.addEventListener('click', hideMenu);
    });
    collapse.querySelectorAll('.dropdown-item').forEach(function(i) {
      i.addEventListener('click', hideMenu);
    });
    // Close on outside tap
    document.addEventListener('click', function(e) {
      if (window.innerWidth >= 992) return;
      var nav = document.querySelector('.isnm-navbar');
      if (nav && !nav.contains(e.target) && collapse.classList.contains('show')) hideMenu();
    });
    // Close on resize to desktop
    window.addEventListener('resize', function() {
      if (window.innerWidth >= 992) hideMenu();
    });
    // Reset menu on bfcache restore (back/forward navigation)
    window.addEventListener('pageshow', function(e) {
      if (e.persisted) hideMenu();
    });
  }
});
</script>