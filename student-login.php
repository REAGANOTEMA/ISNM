<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$student_role = isset($_GET['student_role']) ? trim($_GET['student_role']) : '';
if ($student_role !== '') {
    $_SESSION['student_role'] = urldecode($student_role);
}

$_SESSION['student_login_allowed'] = true;

$login_error   = $_SESSION['error']   ?? '';
$login_success = $_SESSION['success'] ?? '';
$student_hint  = $_SESSION['student_role'] ?? '';

if ($login_error) {
    unset($_SESSION['error']);
}
if ($login_success) {
    unset($_SESSION['success']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, shrink-to-fit=no">
  <title>Student Login — ISNM</title>
  <link rel="icon" type="image/x-icon" href="images/school-logo.png">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
  <style>
    body { margin:0; font-family:'Poppins',sans-serif; background: linear-gradient(135deg, #0d47a1 0%, #283593 100%); min-height:100vh; display:flex; align-items:center; justify-content:center; color:#fff; }
    .login-card { width:100%; max-width:520px; background:#fff; color:#212121; border-radius:24px; box-shadow:0 28px 60px rgba(0,0,0,.22); overflow:hidden; }
    .login-header { background:linear-gradient(135deg,#1a237e 0%,#283593 100%); padding:36px 30px; text-align:center; }
    .login-header img { width:84px; height:84px; border-radius:50%; border:4px solid rgba(255,255,255,.24); margin-bottom:18px; }
    .login-header h1 { margin-bottom:10px; font-size:2rem; letter-spacing:.18em; text-transform:uppercase; }
    .login-header p { margin:0; opacity:.85; font-size:.95rem; }
    .login-body { padding:30px; }
    .form-label { display:block; margin-bottom:8px; font-weight:600; color:#212121; }
    .form-control { border-radius:14px; padding:14px 16px; border:1px solid #d7dce3; width:100%; }
    .input-group-text { background:#f5f7fb; border:none; border-radius:14px 0 0 14px; color:#5f6d7f; }
    .btn-login { width:100%; padding:14px 18px; border:none; border-radius:16px; font-weight:700; font-size:1rem; color:#fff; background:linear-gradient(135deg,#1976d2,#42a5f5); transition:transform .22s ease, box-shadow .22s ease; }
    .btn-login:hover { transform:translateY(-1px); box-shadow:0 18px 32px rgba(25,118,210,.25); }
    .info-block { background:#f7f9fc; border-radius:16px; padding:18px; margin-top:20px; color:#2d3a4b; }
    .info-block .block-title { margin-bottom:10px; font-weight:700; }
    .link-row { margin-top:18px; display:flex; justify-content:space-between; gap:16px; flex-wrap:wrap; }
    .link-row a { color:#1976d2; text-decoration:none; font-size:.95rem; }
    .hint-pill { display:inline-block; margin-top:10px; padding:8px 16px; background:rgba(25,118,210,.12); color:#0d47a1; border-radius:999px; font-size:.88rem; }
  </style>
</head>
<body>
  <div class="login-card">
    <div class="login-header">
      <img src="images/school-logo.png" alt="ISNM Logo">
      <h1>Student Portal</h1>
      <p>Secure student access from the official student portal only.</p>
      <?php if ($student_hint): ?>
        <div class="hint-pill"><i class="fas fa-info-circle"></i> Portal: <?php echo htmlspecialchars($student_hint); ?></div>
      <?php endif; ?>
    </div>
    <div class="login-body">
      <?php if ($login_error): ?>
        <div class="alert alert-danger" role="alert">
          <i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($login_error); ?>
        </div>
      <?php endif; ?>
      <?php if ($login_success): ?>
        <div class="alert alert-success" role="alert">
          <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($login_success); ?>
        </div>
      <?php endif; ?>

      <form method="POST" action="auth-handler.php">
        <input type="hidden" name="action" value="student_login">
        <?php if ($student_hint): ?>
          <input type="hidden" name="student_role" value="<?php echo htmlspecialchars($student_hint); ?>">
        <?php endif; ?>

        <div class="mb-3">
          <label class="form-label" for="stu-index">Index Number</label>
          <div class="input-group">
            <span class="input-group-text"><i class="fas fa-id-card"></i></span>
            <input id="stu-index" name="index_number" class="form-control" type="text" required placeholder="U001/CM/056/16" autocomplete="username">
          </div>
          <div class="text-muted mt-1" style="font-size:.85rem;">Format: UXXX/CC/XXX/XX</div>
        </div>

        <div class="mb-3">
          <label class="form-label" for="stu-name">Full Name</label>
          <div class="input-group">
            <span class="input-group-text"><i class="fas fa-user"></i></span>
            <input id="stu-name" name="full_name" class="form-control" type="text" required placeholder="Enter your full name" autocomplete="name">
          </div>
        </div>

        <div class="mb-3">
          <label class="form-label" for="stu-phone">Phone Number</label>
          <div class="input-group">
            <span class="input-group-text"><i class="fas fa-phone"></i></span>
            <input id="stu-phone" name="phone_number" class="form-control" type="tel" required placeholder="0771234567" autocomplete="tel">
          </div>
        </div>

        <div class="mb-3">
          <label class="form-label" for="stu-password">Password</label>
          <div class="input-group">
            <span class="input-group-text"><i class="fas fa-lock"></i></span>
            <input id="stu-password" name="password" class="form-control" type="password" placeholder="Enter your password (if already set)" autocomplete="current-password">
          </div>
          <div class="text-muted mt-1" style="font-size:.85rem;">If this is your first login, leave this field blank and set your password on the next page.</div>
        </div>

        <button type="submit" class="btn-login"><i class="fas fa-sign-in-alt me-2"></i>Login to Student Portal</button>
      </form>

      <div class="info-block">
        <div class="block-title">Student Portal Access</div>
        <p>Students should log in from the official student portal only. Staff access is handled exclusively from the organogram page.</p>
        <p>If you were redirected from a portal link, your session will preserve the portal hint.</p>
      </div>

      <div class="link-row">
        <a href="organogram.php"><i class="fas fa-arrow-left"></i> Staff login via organogram</a>
        <a href="index.php"><i class="fas fa-home"></i> Back to homepage</a>
      </div>
    </div>
  </div>
</body>
</html>
