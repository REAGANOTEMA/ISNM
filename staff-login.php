<?php
/**
 * ═════════════════════════════════════════════════════════════════════════
 * ISNM ORGANOGRAM STAFF LOGIN — PREMIUM 3D EDITION
 * ═════════════════════════════════════════════════════════════════════════
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/auth-service.php';

$auth_service = new AuthenticationService();

$requested_position = isset($_GET['position']) ? urldecode($_GET['position']) : '';
if (!$requested_position && !empty($_SESSION['requested_position'])) {
    $requested_position = $_SESSION['requested_position'];
}
$resolved_role      = $requested_position ? $auth_service->resolveOrganogramPosition($requested_position) : '';
$suggested_email    = $resolved_role ? $auth_service->getStaffEmailForRole($resolved_role) : '';
if ($requested_position) {
    $_SESSION['requested_position'] = $requested_position;
}

if (!$requested_position) {
    header('Location: organogram.php');
    exit();
}

$_SESSION['staff_login_allowed']  = true;
$_SESSION['staff_login_position'] = $requested_position;
$_SESSION['requested_position']  = $requested_position;

if ($auth_service->isAuthenticated()) {
    if (($_SESSION['type'] ?? '') === 'staff') {
        $sessionRole = $_SESSION['role'] ?? '';
        $dashboard   = $auth_service->getDashboardRoute($sessionRole);
        $requestedPositionFromSession = $_SESSION['requested_position'] ?? '';
        if (!empty($requestedPositionFromSession)
            && $auth_service->positionMatchesRole($requestedPositionFromSession, $sessionRole)
        ) {
            $resolvedPosition = $auth_service->resolveOrganogramPosition($requestedPositionFromSession);
            $requestedDashboard = $auth_service->getDashboardRouteFromKey($resolvedPosition);
            if ($requestedDashboard) {
                $dashboard = $requestedDashboard;
            }
            unset($_SESSION['requested_position']);
        }
        header("Location: $dashboard");
        exit();
    }
    if (($_SESSION['type'] ?? '') === 'student') {
        header('Location: dashboards/student.php');
        exit();
    }
}

$login_error   = $_SESSION['error']   ?? '';
$login_success = $_SESSION['success'] ?? '';
if ($login_error)   { unset($_SESSION['error']); }
if ($login_success) { unset($_SESSION['success']); }

$active_staff_tab = 'show active';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
  <meta name="theme-color" content="#1a237e">
  <title>Staff Login — ISNM</title>
  <link rel="icon" type="image/x-icon" href="images/school-logo.png">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">

  <style>
  :root {
    --primary: #1a237e;
    --primary-dark: #0d47a1;
    --primary-mid: #283593;
    --accent: #ffd600;
    --accent-dark: #f9a825;
    --success: #2e7d32;
    --danger: #c62828;
    --info: #0277bd;
    --text-dark: #212121;
    --text-mid: #616161;
    --text-light: #9e9e9e;
    --bg-light: #f8f8f8;
    --card-bg: #ffffff;
    --border: #e0e0e0;
  }

  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

  body {
    font-family: 'Poppins', -apple-system, BlinkMacSystemFont, sans-serif;
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
    overflow-x: hidden;
    -webkit-font-smoothing: antialiased;
    background: 
      linear-gradient(135deg, #1a237e 0%, #283593 40%, #0d47a1 100%),
      url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.03'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
  }

  /* Floating particles */
  .bg-particles {
    position: fixed;
    top: 0; left: 0; right: 0; bottom: 0;
    pointer-events: none;
    overflow: hidden;
    z-index: 0;
  }
  
  .particle {
    position: absolute;
    border-radius: 50%;
    background: rgba(255,255,255,0.12);
    animation: float-particle linear infinite;
  }
  
  @keyframes float-particle {
    0% { transform: translateY(100vh) scale(0); opacity: 0; }
    10% { opacity: 1; }
    50% { transform: translateY(50vh) scale(1); }
    90% { opacity: 1; }
    100% { transform: translateY(-50px) scale(0); opacity: 0; }
  }

  .login-wrapper { 
    width: 100%; 
    max-width: 500px; 
    margin: 0 auto; 
    position: relative;
    z-index: 1;
  }

  /* Premium 3D Card */
  .login-card {
    background: var(--card-bg);
    border-radius: 24px;
    overflow: hidden;
    box-shadow: 
      0 2px 4px rgba(0,0,0,0.04),
      0 4px 8px rgba(0,0,0,0.06),
      0 8px 16px rgba(0,0,0,0.08),
      0 16px 32px rgba(0,0,0,0.1),
      0 32px 64px rgba(0,0,0,0.12);
    animation: cardEntrance 0.6s ease-out;
  }
  
  @keyframes cardEntrance {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
  }

  /* Header with premium gradient */
  .login-header {
    background: linear-gradient(135deg, #1a237e 0%, #0d47a1 100%);
    color: #fff;
    padding: 48px 30px 40px;
    text-align: center;
    position: relative;
    overflow: hidden;
  }
  
  .login-header::before {
    content: '';
    position: absolute;
    top: -50%; left: -50%;
    width: 200%; height: 200%;
    background: 
      radial-gradient(circle at 30% 40%, rgba(255,255,255,0.06) 0%, transparent 50%),
      radial-gradient(circle at 70% 60%, rgba(255,255,255,0.04) 0%, transparent 50%);
    animation: rotateBg 40s linear infinite;
  }
  
  @keyframes rotateBg {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
  }
  
  .header-inner { position: relative; z-index: 2; }

  /* 3D Logo */
  .logo-wrap {
    width: 100px; height: 100px;
    margin: 0 auto 20px;
    background: linear-gradient(145deg, #ffffff, #e6e6e6);
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    box-shadow: 
      8px 8px 16px rgba(0,0,0,0.2),
      -4px -4px 8px rgba(255,255,255,0.1),
      inset 2px 2px 4px rgba(255,255,255,0.3);
    position: relative;
  }
  
  .logo-wrap::after {
    content: '';
    position: absolute;
    top: -3px; left: -3px; right: -3px; bottom: -3px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--accent), var(--accent-dark));
    z-index: -1;
    opacity: 0.7;
  }
  
  .logo-wrap img { 
    width: 80px; height: 80px; 
    border-radius: 50%; 
    object-fit: cover;
  }

  .login-header h1 {
    font-size: 1.85rem; 
    font-weight: 800; 
    margin: 0 0 6px;
    background: linear-gradient(135deg, #fff 0%, rgba(255,255,255,0.85) 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
  }
  
  .login-header p { 
    opacity: 0.88; 
    font-size: 0.92rem; 
    font-weight: 400;
  }
  
  .role-badge {
    display: inline-block;
    margin-top: 12px;
    padding: 5px 16px;
    background: rgba(255,255,255,0.12);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255,255,255,0.18);
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 500;
  }

  /* Tab bar */
  .tab-bar {
    display: flex;
    background: linear-gradient(180deg, #0d47a1 0%, #1a237e 100%);
  }
  
  .tab-btn {
    flex: 1;
    padding: 16px 8px;
    background: transparent;
    color: rgba(255,255,255,0.55);
    border: none;
    cursor: pointer;
    font-family: 'Poppins', sans-serif;
    font-size: 0.92rem;
    font-weight: 500;
    transition: all 0.25s ease;
    display: flex; align-items: center; justify-content: center; gap: 8px;
  }
  
  .tab-btn:hover { color: rgba(255,255,255,0.8); }
  
  .tab-btn.active {
    color: #fff;
    background: rgba(255,255,255,0.1);
    font-weight: 600;
  }
  
  .tab-btn.active::after {
    content: '';
    position: absolute; bottom: 0; left: 20%; right: 20%;
    height: 3px;
    border-radius: 3px 3px 0 0;
    background: linear-gradient(90deg, var(--accent), var(--accent-dark));
  }

  /* Form area */
  .login-body { 
    padding: 32px 30px 28px;
    background: linear-gradient(180deg, #ffffff 0%, #fafbfc 100%);
  }

  .form-group { margin-bottom: 20px; }
  
  .form-label {
    font-weight: 600; 
    color: var(--text-dark); 
    margin-bottom: 8px;
    font-size: 0.9rem; 
    display: block;
  }
  
  .input-group { position: relative; }
  
  .input-group i {
    position: absolute; 
    left: 16px; 
    top: 50%; 
    transform: translateY(-50%);
    color: var(--text-light); 
    font-size: 1rem; 
    z-index: 2;
    transition: color 0.2s ease;
  }
  
  .form-control {
    border: 2px solid var(--border);
    border-radius: 12px;
    padding: 14px 16px 14px 46px;
    font-size: 14px;
    transition: all 0.25s ease;
    background: var(--bg-light);
    height: auto;
  }
  
  .form-control:focus {
    border-color: var(--primary);
    background: #fff;
    box-shadow: 0 0 0 4px rgba(26,35,126,0.1);
    outline: none;
  }
  
  .input-group:focus-within i { color: var(--primary); }
  
  .form-control::placeholder { color: var(--text-light); }

  /* Premium 3D Button */
  .btn-login {
    background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
    color: #fff; 
    border: none;
    border-radius: 12px; 
    padding: 15px 24px;
    font-size: 15px; 
    font-weight: 700;
    width: 100%; 
    cursor: pointer;
    position: relative; 
    overflow: hidden;
    transition: all 0.3s ease;
    box-shadow: 
      0 4px 12px rgba(26,35,126,0.3),
      inset 0 1px 0 rgba(255,255,255,0.15);
  }
  
  .btn-login::before {
    content: '';
    position: absolute; top: 0; left: -100%;
    width: 100%; height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.12), transparent);
    transition: left 0.5s ease;
  }
  
  .btn-login:hover::before { left: 100%; }
  
  .btn-login:hover {
    box-shadow: 
      0 6px 20px rgba(26,35,126,0.4),
      inset 0 1px 0 rgba(255,255,255,0.2);
    transform: translateY(-1px);
  }
  
  .btn-login:active {
    transform: translateY(0);
    box-shadow: 
      0 2px 8px rgba(26,35,126,0.3),
      inset 0 2px 4px rgba(0,0,0,0.1);
  }

  .tab-panel { display: none; }
  .tab-panel.active { display: block; animation: fadeIn 0.3s ease; }
  
  @keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
  }
  
  .panel-divider { 
    height: 1px; 
    background: linear-gradient(90deg, transparent, var(--border), transparent); 
    margin: 20px 0; 
  }

  .info-block {
    border-radius: 12px; 
    padding: 16px 18px; 
    margin-bottom: 16px;
    border-left: 3px solid var(--info);
    font-size: 0.83rem;
    background: linear-gradient(135deg, #e3f2fd, #bbdefb);
  }
  
  .info-block .block-title { 
    font-weight: 600; 
    margin-bottom: 6px;
    color: var(--text-dark);
  }

  .help-links { text-align: center; }
  .help-links a { 
    color: var(--primary); 
    text-decoration: none; 
    font-size: 0.9rem; 
    display: inline-block;
    margin: 5px 10px;
    transition: color 0.2s ease;
  }
  .help-links a:hover { color: var(--primary-dark); }
  .help-links i { margin-right: 6px; }

  .alert {
    border-radius: 10px; 
    margin-bottom: 18px;
    border: none; 
    padding: 12px 16px; 
    font-size: 0.88rem;
  }
  
  .alert-danger { background: #ffebee; color: #c62828; border-left: 4px solid #ef5350; }
  .alert-success { background: #e8f5e9; color: #2e7d32; border-left: 4px solid #66bb6a; }

  .login-footer { padding: 0 30px 24px; }

  @media(max-width:768px){
    .login-card { border-radius: 20px; }
    .login-header { padding: 36px 24px 32px; }
    .login-body { padding: 24px 20px; }
    .login-footer { padding: 0 20px 20px; }
    .login-header h1 { font-size: 1.5rem; }
  }
  
  @media(max-width:480px){
    .login-card { border-radius: 16px; }
    .login-header { padding: 30px 18px 26px; }
    .login-body { padding: 20px 16px; }
    .logo-wrap { width: 85px; height: 85px; }
    .logo-wrap img { width: 68px; height: 68px; }
  }
  </style>
</head>
<body>

<div class="bg-particles" id="particles"></div>

<div class="login-wrapper">
  <div class="login-card">
    <div class="login-header">
      <div class="header-inner">
        <div class="logo-wrap">
          <img src="images/school-logo.png" alt="ISNM Logo">
        </div>
        <h1>ISNM Portal</h1>
        <p>Iganga School of Nursing and Midwifery — Staff Sign-In</p>

        <?php if ($requested_position): ?>
          <div>
            <span class="role-badge">
              <i class="fas fa-sitemap"></i> <?php echo htmlspecialchars($requested_position); ?>
            </span>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <div class="tab-bar" role="tablist">
      <button type="button" class="tab-btn active" id="tab-staff" role="tab">
        <i class="fas fa-user-tie"></i> Staff Login
      </button>
    </div>

    <div class="login-body">
      <?php if ($login_error): ?>
        <div class="alert alert-danger">
          <i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($login_error); ?>
        </div>
      <?php endif; ?>

      <?php if ($login_success): ?>
        <div class="alert alert-success">
          <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($login_success); ?>
        </div>
      <?php endif; ?>

      <div class="tab-panel active" id="panel-staff">
        <form method="POST" action="auth-handler.php">
          <input type="hidden" name="action" value="staff_login">
          <?php if ($requested_position): ?>
            <input type="hidden" name="requested_position" value="<?php echo htmlspecialchars($requested_position); ?>">
          <?php endif; ?>

          <div class="form-group">
            <label for="s-email" class="form-label">
              <i class="fas fa-envelope" style="margin-right: 6px; color: var(--primary);"></i>Email Address
            </label>
            <div class="input-group">
              <i class="fas fa-envelope"></i>
              <input type="email" class="form-control" id="s-email" name="email"
                     placeholder="you@isnm.ug" required autocomplete="email"
                     value="<?php echo $suggested_email ? htmlspecialchars($suggested_email) : ''; ?>">
            </div>
          </div>

          <div class="form-group">
            <label for="s-password" class="form-label">
              <i class="fas fa-lock" style="margin-right: 6px; color: var(--primary);"></i>Password
            </label>
            <div class="input-group">
              <i class="fas fa-lock"></i>
              <input type="password" class="form-control" id="s-password" name="password"
                     placeholder="Enter your password" required autocomplete="current-password">
            </div>
          </div>

          <button type="submit" class="btn-login" style="margin-top:4px;">
            <i class="fas fa-sign-in-alt me-2"></i>Login to Staff Portal
          </button>
        </form>
      </div>
    </div>

    <div class="login-footer">
      <div class="panel-divider"></div>

      <div class="help-links">
        <a href="staff-password-reset.php"><i class="fas fa-key"></i>Forgot Password?</a>
        <a href="organogram.php"><i class="fas fa-arrow-left"></i>Back to Organogram</a>
      </div>

      <div class="info-block">
        <div class="block-title"><i class="fas fa-university"></i> About ISNM</div>
        <div style="font-size: 0.8rem; line-height: 1.6;">
          <strong>Iganga School of Nursing and Midwifery</strong> — GOVERNMENT OF UGANDA<br>
          P.O. Box 416, Iganga District — Tel: +256 703 204722
        </div>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
  // Create floating particles
  const container = document.getElementById('particles');
  const particleCount = 15;
  
  for (let i = 0; i < particleCount; i++) {
    const particle = document.createElement('div');
    particle.className = 'particle';
    const size = 2 + Math.random() * 4;
    particle.style.width = size + 'px';
    particle.style.height = size + 'px';
    particle.style.left = Math.random() * 100 + '%';
    particle.style.animationDuration = (10 + Math.random() * 15) + 's';
    particle.style.animationDelay = Math.random() * 10 + 's';
    container.appendChild(particle);
  }

  // iOS viewport guard
  const m = 'width=device-width,initial-scale=1.0,maximum-scale=1.0,user-scalable=no';
  document.querySelectorAll('input[type="email"],input[type="password"],input[type="text"]').forEach(function(el) {
    el.addEventListener('focus', function() { 
      document.querySelector('meta[name=viewport]').setAttribute('content', m); 
    });
    el.addEventListener('blur', function() { 
      document.querySelector('meta[name=viewport]').setAttribute('content', m + ',shrink-to-fit=no'); 
    });
  });
});
</script>
</body>
</html>