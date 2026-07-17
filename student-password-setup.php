<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$pending = $_SESSION['pending_student_auth'] ?? null;
if (!$pending || empty($pending['student_id'])) {
    $_SESSION['error'] = 'First login verification is required before setting a password.';
    header('Location: student-login.php');
    exit();
}

$login_error = $_SESSION['error'] ?? '';
$login_success = $_SESSION['success'] ?? '';
unset($_SESSION['error'], $_SESSION['success']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Set Password | Student Portal | ISNM</title>
  <link rel="icon" type="image/x-icon" href="images/school-logo.png">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
  <style>
  :root {
    --primary: #2e7d32;
    --primary-dark: #1b5e20;
    --primary-mid: #388e3c;
    --accent: #ffd600;
    --accent-dark: #f9a825;
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
    display: flex; align-items: center; justify-content: center;
    padding: 20px;
    overflow-x: hidden;
    -webkit-font-smoothing: antialiased;
    background: #1a2e1a;
    background-image: url('images/loginimage.jpg');
    background-size: cover;
    background-position: center;
    background-attachment: fixed;
    background-repeat: no-repeat;
  }

  body::before {
    content: '';
    position: fixed;
    inset: 0;
    background: linear-gradient(135deg,
      rgba(27,94,32,0.85) 0%,
      rgba(20,65,24,0.78) 40%,
      rgba(10,30,12,0.82) 100%
    );
    z-index: 0;
    pointer-events: none;
  }

  .setup-wrapper { 
    width: 100%; max-width: 520px; margin: 0 auto; position: relative; z-index: 1;
  }

  .setup-card {
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

  .setup-header {
    background: linear-gradient(135deg, #2e7d32 0%, #1b5e20 100%);
    color: #fff;
    padding: 40px 30px 32px;
    text-align: center;
    position: relative;
    overflow: hidden;
  }

  .setup-header::before {
    content: '';
    position: absolute; top: -50%; left: -50%;
    width: 200%; height: 200%;
    background: 
      radial-gradient(circle at 30% 40%, rgba(255,255,255,0.06) 0%, transparent 50%),
      radial-gradient(circle at 70% 60%, rgba(255,255,255,0.04) 0%, transparent 50%);
    animation: rotateBg 40s linear infinite;
  }

  @keyframes rotateBg {
    from { transform: rotate(0deg); } to { transform: rotate(360deg); }
  }

  .header-inner { position: relative; z-index: 2; }

  .icon-circle {
    width: 72px; height: 72px;
    margin: 0 auto 16px;
    background: linear-gradient(145deg, #ffffff, #e6e6e6);
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    box-shadow: 
      8px 8px 16px rgba(0,0,0,0.2),
      -4px -4px 8px rgba(255,255,255,0.1),
      inset 2px 2px 4px rgba(255,255,255,0.3);
    position: relative;
  }

  .icon-circle i {
    font-size: 2rem; color: var(--primary);
  }

  .setup-header h1 {
    font-size: 1.65rem; font-weight: 800; margin: 0 0 6px;
    background: linear-gradient(135deg, #fff 0%, rgba(255,255,255,0.85) 100%);
    -webkit-background-clip: text; -webkit-text-fill-color: transparent;
    background-clip: text;
  }

  .setup-header p { opacity: 0.88; font-size: 0.92rem; font-weight: 400; }

  .setup-body { 
    padding: 28px 30px 24px;
    background: linear-gradient(180deg, #ffffff 0%, #fafbfc 100%);
  }

  .student-card {
    background: linear-gradient(135deg, #e8f5e9, #c8e6c9);
    border: 1px solid #a5d6a7;
    border-radius: 12px;
    padding: 16px 20px;
    margin-bottom: 24px;
  }

  .student-card .info-row {
    display: flex; align-items: center; gap: 8px;
    font-size: 0.9rem;
    margin-bottom: 4px;
  }

  .student-card .info-row:last-child { margin-bottom: 0; }

  .student-card .info-row i {
    width: 18px; color: var(--primary);
  }

  .student-card .label {
    color: var(--text-mid); font-weight: 500;
  }

  .student-card .value {
    color: var(--text-dark); font-weight: 600;
  }

  .form-group { margin-bottom: 18px; }

  .form-label {
    font-weight: 600; color: var(--text-dark);
    margin-bottom: 8px; font-size: 0.9rem; display: block;
  }

  .input-group { position: relative; }

  .input-group-text {
    position: absolute;
    left: 0; top: 0; bottom: 0;
    width: 46px;
    display: flex; align-items: center; justify-content: center;
    background: linear-gradient(180deg, #f8f9fa 0%, #e9ecef 100%);
    border: 2px solid var(--border);
    border-right: none;
    border-radius: 12px 0 0 12px;
    color: var(--text-light);
    font-size: 1rem;
    z-index: 2;
    transition: all 0.2s ease;
  }

  .input-group:focus-within .input-group-text {
    border-color: var(--primary);
    color: var(--primary);
    background: linear-gradient(180deg, #e8f5e9 0%, #c8e6c9 100%);
  }

  .form-control {
    border: 2px solid var(--border);
    border-radius: 0 12px 12px 0;
    padding: 13px 14px 13px 46px;
    font-size: 14px;
    transition: all 0.25s ease;
    background: var(--bg-light);
    height: auto;
  }

  .form-control:focus {
    border-color: var(--primary);
    background: #fff;
    box-shadow: 0 0 0 4px rgba(46,125,50,0.1);
    outline: none;
  }

  .form-control::placeholder { color: var(--text-light); }

  .form-text {
    font-size: 0.8rem; color: var(--text-mid);
    margin-top: 4px; font-weight: 400;
  }

  .password-toggle {
    position: absolute;
    right: 8px; top: 50%; transform: translateY(-50%);
    background: none; border: none;
    color: var(--text-light); cursor: pointer;
    padding: 8px; z-index: 3;
    transition: color 0.2s;
  }

  .password-toggle:hover { color: var(--text-dark); }

  .btn-save {
    background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
    color: #fff; border: none;
    border-radius: 12px; padding: 15px 24px;
    font-size: 15px; font-weight: 700;
    width: 100%; cursor: pointer;
    position: relative; overflow: hidden;
    transition: all 0.3s ease;
    box-shadow: 0 4px 12px rgba(46,125,50,0.3), inset 0 1px 0 rgba(255,255,255,0.15);
  }

  .btn-save::before {
    content: '';
    position: absolute; top: 0; left: -100%;
    width: 100%; height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.12), transparent);
    transition: left 0.5s ease;
  }

  .btn-save:hover::before { left: 100%; }

  .btn-save:hover {
    box-shadow: 0 6px 20px rgba(46,125,50,0.4), inset 0 1px 0 rgba(255,255,255,0.2);
    transform: translateY(-1px);
  }

  .btn-save:active {
    transform: translateY(0);
    box-shadow: 0 2px 8px rgba(46,125,50,0.3), inset 0 2px 4px rgba(0,0,0,0.1);
  }

  .back-link {
    display: block; text-align: center;
    margin-top: 18px;
  }

  .back-link a {
    color: var(--primary); text-decoration: none;
    font-size: 0.89rem; font-weight: 500;
    transition: color 0.2s;
  }

  .back-link a:hover { color: var(--primary-dark); text-decoration: underline; }
  </style>
</head>
<body>
<div class="setup-wrapper">
  <div class="setup-card">
    <div class="setup-header">
      <div class="header-inner">
        <div class="icon-circle">
          <i class="fas fa-key"></i>
        </div>
        <h1>Set Your Password</h1>
        <p>Secure your student portal account with a strong password</p>
      </div>
    </div>

    <div class="setup-body">
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

      <div class="student-card">
        <div class="info-row">
          <i class="fas fa-user-graduate"></i>
          <span class="label">Student:</span>
          <span class="value"><?php echo htmlspecialchars($pending['full_name'] ?? 'Unknown'); ?></span>
        </div>
        <div class="info-row">
          <i class="fas fa-id-card"></i>
          <span class="label">Index:</span>
          <span class="value"><?php echo htmlspecialchars($pending['index_number'] ?? 'Unknown'); ?></span>
        </div>
      </div>

      <form method="POST" action="auth-handler.php" id="password-form">
        <input type="hidden" name="action" value="student_set_password">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">

        <div class="form-group">
          <label class="form-label" for="password">
            <i class="fas fa-lock" style="margin-right: 6px; color: var(--primary);"></i>New Password
          </label>
          <div class="input-group">
            <span class="input-group-text"><i class="fas fa-lock"></i></span>
            <input id="password" name="password" type="password" class="form-control" required minlength="8" placeholder="Enter a new password">
            <button type="button" class="password-toggle" id="toggle-password" tabindex="-1" aria-label="Toggle password visibility">
              <i class="fas fa-eye"></i>
            </button>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label" for="confirm_password">
            <i class="fas fa-check-circle" style="margin-right: 6px; color: var(--primary);"></i>Confirm Password
          </label>
          <div class="input-group">
            <span class="input-group-text"><i class="fas fa-check-circle"></i></span>
            <input id="confirm_password" name="confirm_password" type="password" class="form-control" required minlength="8" placeholder="Re-enter your password">
            <button type="button" class="password-toggle" id="toggle-confirm" tabindex="-1" aria-label="Toggle confirm visibility">
              <i class="fas fa-eye"></i>
            </button>
          </div>
        </div>

        <div class="form-text" style="margin-bottom: 18px;">
          <i class="fas fa-info-circle me-1"></i>Use at least 8 characters. Choose a memorable but secure password.
        </div>

        <button type="submit" class="btn-save" id="btn-submit">
          <i class="fas fa-lock-open me-2"></i>Save Password &amp; Continue
        </button>
      </form>

      <div class="back-link">
        <a href="student-login.php"><i class="fas fa-arrow-left me-1"></i>Back to Student Login</a>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  const togglePwd = document.getElementById('toggle-password');
  const pwdInput = document.getElementById('password');
  if (togglePwd && pwdInput) {
    togglePwd.addEventListener('click', function() {
      const type = pwdInput.getAttribute('type') === 'password' ? 'text' : 'password';
      pwdInput.setAttribute('type', type);
      this.querySelector('i').className = 'fas fa-' + (type === 'password' ? 'eye' : 'eye-slash');
    });
  }

  const toggleConfirm = document.getElementById('toggle-confirm');
  const confirmInput = document.getElementById('confirm_password');
  if (toggleConfirm && confirmInput) {
    toggleConfirm.addEventListener('click', function() {
      const type = confirmInput.getAttribute('type') === 'password' ? 'text' : 'password';
      confirmInput.setAttribute('type', type);
      this.querySelector('i').className = 'fas fa-' + (type === 'password' ? 'eye' : 'eye-slash');
    });
  }

  const form = document.getElementById('password-form');
  const btn = document.getElementById('btn-submit');
  if (form && btn) {
    form.addEventListener('submit', function() {
      btn.disabled = true;
      btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Saving...';
    });
  }
});
</script>
</body>
</html>
