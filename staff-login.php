<?php
/**
 * ═════════════════════════════════════════════════════════════════════════
 * ISNM UNIFIED LOGIN — SINGLE ENTRY POINT FOR ALL USERS
 * ═════════════════════════════════════════════════════════════════════════
 * ① Staff logins  →  staff table (AuthenticationService),
 *                     hr_users table (inline fallback),
 *                     bursar_users table (inline fallback)
 * ② Student logins →  users table (index_number + full_name + phone)
 *
 * All redirects for EVERY role hit this page:
 *   dashboards/*.php  →  staff-login.php
 *   student.php       →  staff-login.php
 *   bursar_login.php  →  staff-login.php
 *   hr_login.php      →  staff-login.php
 *   views/login.php   →  staff-login.php
 *   auth-handler.php  →  staff-login.php
 *
 * Auth POST handler : auth-handler.php  (action: staff_login | student_login)
 *
 * After login → auth-handler.php routes by role:
 *   staff  →  dashboards/{role-keyword}.php
 *   student →  dashboards/student.php
 * ═════════════════════════════════════════════════════════════════════════
 */

// ── 1. Session initialisation ────────────────────────────────────────
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ── 2. Core config & auth service ────────────────────────────────────
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/auth-service.php';

$auth_service = new AuthenticationService();

// ── 3. Role-selection hint from organogram links ─────────────────────
$requested_position = isset($_GET['position']) ? urldecode($_GET['position']) : '';
$resolved_role      = $requested_position ? $auth_service->resolveOrganogramPosition($requested_position) : '';
$suggested_email    = $resolved_role ? $auth_service->getStaffEmailForRole($resolved_role) : '';
if ($requested_position) {
    $_SESSION['requested_position'] = $requested_position;
}

// ── 3b. Student-role hint from student-login.php redirect stub ────────
$has_student_hint = !empty($_SESSION['student_role']);
if ($has_student_hint) {
    unset($_SESSION['student_role']);
}

// ── 4. Already logged-in? → redirect to dashboard immediately ─────────
if ($auth_service->isAuthenticated()) {
    if (($_SESSION['type'] ?? '') === 'staff') {
        $sessionRole = $_SESSION['role'] ?? '';
        $dashboard   = $auth_service->getDashboardRoute($sessionRole);
        if (!empty($requested_position)
            && $auth_service->positionMatchesRole($requested_position, $sessionRole)
        ) {
            $dashboard = $auth_service->getDashboardRoute(
                $auth_service->resolveOrganogramPosition($requested_position)
            );
        }
        header("Location: $dashboard");
        exit();
    }
    if (($_SESSION['type'] ?? '') === 'student') {
        header('Location: dashboards/student.php');
        exit();
    }
}

// ── 5. Read flash messages set by auth-handler.php ───────────────────
$login_error   = $_SESSION['error']   ?? '';
$login_success = $_SESSION['success'] ?? '';
if ($login_error)   { unset($_SESSION['error']); }
if ($login_success) { unset($_SESSION['success']); }

// ── 6. Determine active tab ──────────────────────────────────────────
$active_staff_tab   = 'show active';
$active_student_tab = '';
if ($login_error) {
    if (!empty($_SESSION['error_source']) && $_SESSION['error_source'] === 'student') {
        $active_staff_tab   = '';
        $active_student_tab = 'show';
        unset($_SESSION['error_source']);
    }
} elseif ($has_student_hint) {
    $active_staff_tab   = '';
    $active_student_tab = 'show';
}






?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0,
        maximum-scale=1.0, user-scalable=no, shrink-to-fit=no">
  <meta name="mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="default">
  <meta name="theme-color" content="#1a237e">
  <link rel="manifest" href="manifest.json">
  <title>Login — ISNM School Management System</title>
  <link rel="icon"   type="image/x-icon"  href="images/school-logo.png">
  <link rel="apple-touch-icon"              href="images/school-logo.png">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"      rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

  <style>
  /* ── Design tokens ─────────────────────────────────────────────── */
  :root {
    --primary:      #1a237e;
    --primary-dark: #0d47a1;
    --primary-mid:  #283593;
    --accent:       #ffd600;
    --success:      #2e7d32;
    --danger:       #c62828;
    --warning:      #f57f17;
    --info:         #0277bd;
    --brown:        #5d4037;
    --brown-mid:    #795548;
    --purple:       #4a148c;
    --purple-light: #7b1fa2;
    --text-dark:    #212121;
    --text-mid:     #616161;
    --text-light:   #9e9e9e;
    --bg-light:     #f8f8f8;
    --card-bg:      #ffffff;
    --border:       #e0e0e0;
    --hr-r:         #c0392b;
    --bur-r:        #2e8b57;
  }

  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

  body {
    font-family: 'Poppins', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    min-height: 100vh;
    display: flex; align-items: center; justify-content: center;
    padding: 20px;
    overflow-x: hidden;
    -webkit-font-smoothing: antialiased;
    /* ear-tag: mirror-gradient (no logo text /estd) */
    background: linear-gradient(135deg, var(--primary) 0%, var(--primary-mid) 50%, var(--primary-dark) 100%);
  }

  /* ── Layout ────────────────────────────────────────────────────── */
  .login-wrapper { width: 100%; max-width: 500px; margin: 0 auto; }

  .login-card {
    background: var(--card-bg);
    border-radius: 24px;
    box-shadow: 0 24px 64px rgba(0,0,0,.28);
    overflow: hidden;
    animation: cardIn .55s ease-out;
  }
  @keyframes cardIn {
    from { opacity: 0; transform: translateY(32px) scale(.98); }
    to   { opacity: 1; transform: translateY(0)   scale(1);   }
  }

  /* ── Header ────────────────────────────────────────────────────── */
  .login-header {
    background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
    color: #fff;
    padding: 44px 30px 40px;
    text-align: center;
    position: relative;
    overflow: hidden;
  }
  .login-header::before {
    content: '';
    position: absolute; top: -55%; left: -55%;
    width: 210%; height: 210%;
    background: radial-gradient(circle, rgba(255,255,255,.10) 0%, transparent 70%);
    animation: spin 22s linear infinite;
  }
  @keyframes spin { from { transform: rotate(0deg);   } to { transform: rotate(360deg); } }

  .header-inner { position: relative; z-index: 1; }

  .logo-wrap {
    width:100px; height:100px; margin:0 auto 20px;
    background:#fff; border-radius:50%;
    display:flex; align-items:center; justify-content:center;
    box-shadow:0 8px 28px rgba(0,0,0,.22); border:5px solid var(--accent);
  }
  .logo-wrap img { width:82px; height:82px; border-radius:50%; object-fit:cover; }

  .login-header h1 {
    font-size:1.80rem; font-weight:700; margin:0 0 6px; letter-spacing:.3px;
  }
  .login-header p { opacity:.92; font-size:.95rem; font-weight:300; }

  /* ── Tab bar ───────────────────────────────────────────────────── */
  .tab-bar {
    display: flex;
    background: var(--primary);
  }
  .tab-btn {
    flex: 1;
    padding: 16px 8px;
    background: transparent;
    color: rgba(255,255,255,.65);
    border: none;
    cursor: pointer;
    font-family: 'Poppins', sans-serif;
    font-size: .95rem;
    font-weight: 500;
    transition: all .25s;
    display: flex; align-items: center; justify-content: center; gap: 8px;
    position: relative;
  }
  .tab-btn:hover  { color: #fff; background: rgba(255,255,255,.08); }
  .tab-btn.active {
    color: #fff;
    background: rgba(255,255,255,.14);
    font-weight: 600;
  }
  .tab-btn.active::after {
    content: '';
    position: absolute; bottom: 0; left: 15%; right: 15%;
    height: 3px; border-radius: 3px 3px 0 0;
    background: var(--accent);
  }
  .tab-btn i { font-size: 1rem; }

  /* ── Body / form area ───────────────────────────────────────────── */
  .login-body { padding: 32px 30px 28px; }

  .form-group { margin-bottom: 22px; }
  .form-label {
    font-weight: 600; color: var(--text-dark); margin-bottom: 8px;
    font-size: .90rem; display: block;
  }
  .input-group { position: relative; }
  .input-group i {
    position: absolute; left: 16px; top: 50%; transform: translateY(-50%);
    color: var(--text-light); font-size: 1.05rem; z-index: 2;
  }
  .form-control {
    border: 2px solid var(--border);
    border-radius: 12px;
    padding: 14px 16px 14px 48px;
    font-size: 15px;
    transition: all .3s ease;
    background: var(--bg-light);
    height: auto;
  }
  .form-control:focus {
    border-color: var(--primary);
    background: #fff;
    box-shadow: 0 0 0 4px rgba(26,35,126,.10);
    outline: none;
  }
  .form-control::placeholder { color: var(--text-light); }

  .btn-login {
    background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
    color: #fff; border: none;
    border-radius: 12px; padding: 16px;
    font-size: 16px; font-weight: 600;
    width: 100%; cursor: pointer;
    box-shadow: 0 4px 14px rgba(26,35,126,.30);
    transition: all .3s ease;
    position: relative; overflow: hidden;
  }
  .btn-login::before {
    content: '';
    position: absolute; top: 0; left: -100%; width: 100%; height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,.18), transparent);
    transition: left .5s ease;
  }
  .btn-login:hover::before  { left: 100%; }
  .btn-login:hover          { transform: translateY(-2px);    box-shadow: 0 6px 22px rgba(26,35,126,.38); }
  .btn-login:active         { transform: translateY(0);       box-shadow: 0 2px  8px rgba(26,35,126,.28); }

  /* Tab panels */
  .tab-panel { display: none; }
  .tab-panel.active { display: block; }
  .panel-divider { height: 1px; background: var(--border); margin: 22px 0; }

  /* ── Info / demo blocks ────────────────────────────────────────── */
  .info-block {
    border-radius: 12px; padding: 16px 18px; margin-bottom: 18px;
    border-left: 4px solid; font-size: .83rem;
  }
  .info-block.info   { background: linear-gradient(135deg,#e3f2fd,#bbdefb); border-color: var(--info);   color: var(--text-dark); }
  .info-block.sample { background: #e8f5e8;                               border-color: var(--success); color: var(--text-dark); }
  .info-block .block-title { font-weight: 600; margin-bottom: 6px; }
  .info-block kbd { background:#fff; border:1px solid #ccc; border-radius:4px; padding:1px 6px; font-family:monospace; font-size:.78rem; }

  .help-links { text-align:center; }
  .help-links a { color: var(--primary); text-decoration:none; font-size:.90rem; display:block; margin:5px 0; }
  .help-links a:hover { text-decoration:underline; }
  .help-links i { margin-right:7px; }

  /* ── Alerts ────────────────────────────────────────────────────── */
  .alert {
    border-radius: 12px; margin-bottom: 20px;
    border: none; padding: 14px 16px; font-size: .895rem;
  }
  .alert-danger  { background:#fdecea;  color: var(--danger); border-left: 4px solid #ef9a9a; }
  .alert-success { background:#e8f5e9;  color: var(--success); border-left: 4px solid #a5d6a7; }

  /* ── Footer ────────────────────────────────────────────────────── */
  .login-footer { padding:0 30px 28px; }

  /* ── Responsive ────────────────────────────────────────────────── */
  @media(max-width:768px){
    .login-card  { border-radius:20px; }
    .login-header{ padding:36px 24px 32px; }
    .login-body  { padding:26px 22px; }
    .login-footer{ padding:0 22px 22px; }
    .form-control{ padding:12px 14px 12px 44px; font-size:16px; }
    .btn-login   { padding:14px; font-size:15px; }
    .tab-btn     { padding:14px 6px; font-size:.88rem; }
  }
  @media(max-width:480px){
    .login-card  { border-radius:16px; }
    .login-header{ padding:30px 18px 26px; }
    .login-header h1 { font-size:1.5rem; }
    .login-body  { padding:20px 16px; }
    .login-footer{ padding:0 16px 18px; }
  }
  @media(max-height:600px) and (orientation:landscape){
    .login-card  { max-height:90vh; overflow-y:auto; }
    .login-header{ padding:20px; }
    .login-body  { padding:20px; }
  }
  @supports(-webkit-touch-callout:none){
    .form-control{ -webkit-appearance:none; border-radius:12px; }
    .btn-login   { -webkit-appearance:none; -webkit-user-select:none; }
  }
  </style>
</head>
<body>
<div class="login-wrapper">
  <div class="login-card">

    <!-- ══ Header ══════════════════════════════════════════════════ -->
    <div class="login-header">
      <div class="header-inner">
        <div class="logo-wrap">
          <img src="images/school-logo.png" alt="ISNM Logo">
        </div>
        <h1>ISNM Portal</h1>
        <p>Iganga School of Nursing and Midwifery — Staff &amp; Student Sign-In</p>

        <?php if ($requested_position): ?>
          <div style="margin-top:10px;">
            <span style="background:rgba(255,255,255,.15); color:rgba(255,255,255,.9);
                  padding:4px 14px; border-radius:20px; font-size:.78rem;">
              <i class="fas fa-sitemap"></i> Role: <?php echo htmlspecialchars($requested_position); ?>
            </span>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- ══ Tab bar ══════════════════════════════════════════════════ -->
    <div class="tab-bar" role="tablist">
      <button type="button"
        class="tab-btn <?php echo $active_staff_tab === 'show active' ? 'active' : ''; ?>"
        id="tab-staff" role="tab"
        data-bs-toggle="tab" data-bs-target="#panel-staff"
        aria-controls="panel-staff" aria-selected="true">
        <i class="fas fa-user-tie"></i> Staff Login
      </button>
      <button type="button"
        class="tab-btn <?php echo $active_student_tab === 'show' ? 'active' : ''; ?>"
        id="tab-student" role="tab"
        data-bs-toggle="tab" data-bs-target="#panel-student"
        aria-controls="panel-student" aria-selected="false">
        <i class="fas fa-user-graduate"></i> Student Login
      </button>
    </div>

    <!-- ══ Form area ════════════════════════════════════════════════ -->
    <div class="login-body">

      <!-- ─► Global flash messages ──────────────────────────────── -->
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

      <!-- ══ TAB: Staff ═════════════════════════════════════════════ -->
      <div class="tab-panel <?php echo $active_staff_tab; ?>"
           id="panel-staff" role="tabpanel" aria-labelledby="tab-staff">

        <form method="POST" action="auth-handler.php">

          <input type="hidden" name="action" value="staff_login">
          <?php if ($requested_position): ?>
            <input type="hidden" name="requested_position"
                   value="<?php echo htmlspecialchars($requested_position); ?>">
          <?php endif; ?>

          <div class="form-group">
            <label for="s-email" class="form-label">Email Address</label>
            <div class="input-group">
              <i class="fas fa-envelope"></i>
              <input type="email" class="form-control" id="s-email" name="email"
                     placeholder="you@isnm.ug" required autocomplete="email"
                     value="<?php echo $suggested_email ? htmlspecialchars($suggested_email) : ''; ?>">
            </div>
          </div>

          <div class="form-group">
            <label for="s-password" class="form-label">Password</label>
            <div class="input-group">
              <i class="fas fa-lock"></i>
              <input type="password" class="form-control" id="s-password" name="password"
                     placeholder="Enter your password" required autocomplete="current-password">
            </div>
          </div>

          <button type="submit" class="btn-login" style="margin-top:6px;">
            <i class="fas fa-sign-in-alt me-2"></i>Login to Staff Portal
          </button>
        </form>

        <div class="info-block info" style="margin-top:20px;">
          <div class="block-title"><i class="fas fa-users me-1"></i> Who should use this tab?</div>
          <div style="font-size:.80rem; margin-top:6px; line-height:1.65;">
            Principal, Academic Registrar, Director General, Director Academics,<br>
            Director Finance, Director ICT, Bursar, HR Manager, Lecturers,<br>
            Librarian, Matrons, Wardens, Security, Lab Technicians, Drivers,<br>
            Secretary, Deputy Principal and all other staff roles.
          </div>
        </div>

        <div class="info-block sample" style="margin-top:10px;">
          <div class="block-title"><i class="fas fa-key me-1"></i> Default Credentials</div>
          <div style="font-size:.80rem; line-height:1.65;">
            <strong>Password:</strong> <kbd>12345678</kbd>&ensp;
            <strong>(HR)</strong> <kbd>Lovely2God</kbd>&ensp;
            <strong>(Bursar)</strong> <kbd>bursar@isnm</kbd><br>
            Every role uses the same password with their staff email.
          </div>
        </div>
      </div><!-- /panel-staff -->

      <!-- ══ TAB: Student ════════════════════════════════════════════ -->
      <div class="tab-panel <?php echo $active_student_tab; ?>"
           id="panel-student" role="tabpanel" aria-labelledby="tab-student">

        <form method="POST" action="auth-handler.php">
          <input type="hidden" name="action" value="student_login">

          <div class="form-group">
            <label for="stu-index" class="form-label">Index Number</label>
            <div class="input-group">
              <i class="fas fa-id-card"></i>
              <input type="text" class="form-control" id="stu-index" name="index_number"
                     placeholder="e.g. U001/CM/056/16" required autocomplete="username">
            </div>
            <div style="font-size:.77rem; color:var(--text-light); margin-top:5px;">
              Format: UXXX/CC/XXX/XX &nbsp;|&nbsp; Example: U001/CM/056/16
            </div>
          </div>

          <div class="form-group">
            <label for="stu-name" class="form-label">Full Name</label>
            <div class="input-group">
              <i class="fas fa-user"></i>
              <input type="text" class="form-control" id="stu-name" name="full_name"
                     placeholder="Enter your full name as on your registration" required autocomplete="name">
            </div>
          </div>

          <div class="form-group">
            <label for="stu-phone" class="form-label">Phone Number</label>
            <div class="input-group">
              <i class="fas fa-phone"></i>
              <input type="tel" class="form-control" id="stu-phone" name="phone_number"
                     placeholder="e.g. 0771234567" required autocomplete="tel">
            </div>
            <div style="font-size:.77rem; color:var(--text-light); margin-top:5px;">
              Enter the phone number registered with your student account.
            </div>
          </div>

          <button type="submit" class="btn-login" style="margin-top:4px;">
            <i class="fas fa-user-graduate me-2"></i>Login to Student Portal
          </button>
        </form>

        <div class="info-block sample" style="margin-top:20px;">
          <div class="block-title"><i class="fas fa-info-circle me-1"></i> Sample Credentials (for testing)</div>
          <div style="font-size:.80rem; line-height:1.75; margin-top:6px;">
            <strong>Index:</strong> U001/CM/056/16<br>
            <strong>Name:</strong> Aisha Nakato<br>
            <strong>Phone:</strong> 0771234567
          </div>
        </div>
      </div><!-- /panel-student -->

    </div><!-- /.login-body -->

    <div class="login-footer">
      <div class="panel-divider"></div>

      <div class="help-links">
        <a href="staff-password-reset.php"><i class="fas fa-key"></i>Forgot Password?</a>
        <a href="index.php"><i class="fas fa-arrow-left"></i>Back to Home Page</a>
      </div>

      <div class="info-block info" style="border-left-color:var(--info); text-align:left;">
        <div class="block-title"><i class="fas fa-university me-1"></i> About ISNM</div>
        <div style="font-size:.80rem; line-height:1.65;">
          <strong>Iganga School of Nursing and Midwifery</strong> —
          <strong>GOVERNMENT OF UGANDA</strong><br>
          P.O. Box 416, Iganga District — Tel: +256 703 204722
        </div>
      </div>
    </div><!-- /.login-footer -->

  </div><!-- /.card -->
</div><!-- /.wrapper -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
/* ── iOS viewport-zoom guard ─────────────────────────────────── */
document.addEventListener('DOMContentLoaded', function () {
  var m = 'width=device-width,initial-scale=1.0,maximum-scale=1.0,user-scalable=no';
  document.querySelectorAll('input[type="email"],input[type="password"],input[type="text"],input[type="tel"]').forEach(function (el) {
    el.addEventListener('focus',  function () { document.querySelector('meta[name=viewport]').setAttribute('content', m); });
    el.addEventListener('blur',   function () { document.querySelector('meta[name=viewport]').setAttribute('content', m + ',shrink-to-fit=no'); });
  });

  /* ── Bootstrap 5 tab switch → active state on our pseudo-border ── */
  var tabEl = document.querySelector('.tab-bar');
  if (tabEl) {
    tabEl.addEventListener('shown.bs.tab', function () {
      document.querySelectorAll('.tab-btn').forEach(function (b) { b.classList.remove('active'); });
    });
  }

  /* ── keyboard accessibility ── */
  document.querySelectorAll('.tab-btn').forEach(function (btn) {
    btn.addEventListener('keydown', function (e) {
      var buttons = Array.from(document.querySelectorAll('.tab-btn'));
      var idx     = buttons.indexOf(btn);
      var next;
      if (e.key === 'ArrowRight') next = buttons[(idx + 1) % buttons.length];
      if (e.key === 'ArrowLeft')  next = buttons[(idx - 1 + buttons.length) % buttons.length];
      if (next) { e.preventDefault(); next.click(); next.focus(); }
    });
  });
});
</script>
</body>
</html>
