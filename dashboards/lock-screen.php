<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../auth-service.php';
require_once __DIR__ . '/../includes/student_helpers.php';

if (session_status() === PHP_SESSION_NONE) session_start();

$auth_service = new AuthenticationService();

if (!$auth_service->isAuthenticated()) {
    header('Location: ../staff-login.php');
    exit();
}

if (!$_SESSION['session_locked']) {
    header('Location: ' . ($auth_service->getDashboardRoute($_SESSION['role'] ?? '') ?: '../index.php'));
    exit();
}

$userName  = $_SESSION['full_name'] ?? 'User';
$userRole  = $_SESSION['role'] ?? '';
$userEmail = $_SESSION['email'] ?? '';
$userAvatar = '../images/school-logo.png';

$profileFile = __DIR__ . '/../includes/profile_settings.php';
if (file_exists($profileFile)) {
    include_once $profileFile;
    if (function_exists('getStaffProfileImageUrl')) {
        $img = getStaffProfileImageUrl($_SESSION['user_id'] ?? 0);
        if ($img && $img !== '../images/username.png') $userAvatar = $img;
    }
}

$error = $_SESSION['lock_error'] ?? '';
unset($_SESSION['lock_error']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Session Locked | ISNM</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:'Inter',sans-serif;height:100vh;overflow:hidden;background:#0f172a}
.lock-bg{position:fixed;inset:0;background:url('../images/school-logo.png') center/cover no-repeat;filter:blur(20px) brightness(0.3);transform:scale(1.1);z-index:0}
.lock-overlay{position:fixed;inset:0;background:linear-gradient(135deg,rgba(15,23,42,0.85),rgba(30,27,75,0.9));z-index:1}
.lock-container{position:relative;z-index:2;display:flex;align-items:center;justify-content:center;min-height:100vh;padding:20px}
.lock-card{background:rgba(255,255,255,0.05);backdrop-filter:blur(16px);-webkit-backdrop-filter:blur(16px);border:1px solid rgba(255,255,255,0.1);border-radius:20px;padding:48px 40px;max-width:420px;width:100%;text-align:center;box-shadow:0 25px 60px rgba(0,0,0,0.5)}
.lock-card .logo{width:72px;height:72px;border-radius:50%;margin:0 auto 16px;display:block;border:3px solid rgba(255,255,255,0.15);padding:4px;background:rgba(255,255,255,0.05)}
.lock-card .lock-icon{font-size:2.5rem;color:#f59e0b;margin-bottom:8px}
.lock-card h2{font-size:1.3rem;font-weight:700;color:#fff;margin-bottom:4px}
.lock-card .role-badge{display:inline-block;padding:4px 14px;border-radius:20px;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:0.5px;color:rgba(255,255,255,0.7);background:rgba(255,255,255,0.08);margin-bottom:16px}
.lock-card .msg{font-size:13px;color:rgba(255,255,255,0.5);margin-bottom:24px;padding:10px 16px;background:rgba(245,158,11,0.1);border-radius:10px;border:1px solid rgba(245,158,11,0.2)}
.lock-card .msg i{color:#f59e0b;margin-right:8px}
.lock-card .form-control{background:rgba(255,255,255,0.08);border:1px solid rgba(255,255,255,0.15);border-radius:10px;padding:12px 16px;color:#fff;font-size:14px}
.lock-card .form-control:focus{background:rgba(255,255,255,0.12);border-color:#7c3aed;box-shadow:0 0 0 3px rgba(124,58,237,0.2);color:#fff}
.lock-card .form-control::placeholder{color:rgba(255,255,255,0.35)}
.lock-card .btn-unlock{background:linear-gradient(135deg,#7c3aed,#6d28d9);border:none;border-radius:10px;padding:12px;font-size:14px;font-weight:600;color:#fff;width:100%;transition:all 0.2s}
.lock-card .btn-unlock:hover{transform:translateY(-1px);box-shadow:0 8px 25px rgba(124,58,237,0.35)}
.lock-card .btn-outline-light{border-radius:10px;padding:10px;font-size:13px;border-color:rgba(255,255,255,0.15);color:rgba(255,255,255,0.6)}
.lock-card .btn-outline-light:hover{background:rgba(255,255,255,0.08);color:#fff;border-color:rgba(255,255,255,0.25)}
.lock-card .error-msg{background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.2);border-radius:10px;padding:10px 14px;font-size:12px;color:#fca5a5;margin-bottom:16px;display:<?=$error?'block':'none'?>}
@media(max-width:480px){.lock-card{padding:32px 24px;margin:10px}.lock-card .logo{width:60px;height:60px}}
</style>
</head>
<body>
<div class="lock-bg"></div>
<div class="lock-overlay"></div>
<div class="lock-container">
<div class="lock-card">
<img src="<?=htmlspecialchars($userAvatar)?>" alt="ISNM" class="logo">
<div class="lock-icon"><i class="fas fa-lock"></i></div>
<h2><?=htmlspecialchars($userName)?></h2>
<div class="role-badge"><?=htmlspecialchars($userRole)?></div>
<div class="msg"><i class="fas fa-clock"></i>Session Locked Due To Inactivity</div>
<?php if ($error): ?>
<div class="error-msg"><?=htmlspecialchars($error)?></div>
<?php endif; ?>
<form method="post" action="../auth-handler.php">
<input type="hidden" name="action" value="unlock_session">
<div class="mb-3">
<input type="password" name="password" class="form-control" placeholder="Enter your password to unlock" required autocomplete="off" autofocus>
</div>
<button type="submit" class="btn btn-unlock mb-3"><i class="fas fa-unlock me-2"></i>Unlock Session</button>
</form>
<a href="../auth-handler.php?action=logout" class="btn btn-outline-light w-100"><i class="fas fa-sign-out-alt me-2"></i>Return To Login</a>
</div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var inp = document.querySelector('input[name="password"]');
    if (inp) inp.focus();
});
</script>
</body>
</html>
