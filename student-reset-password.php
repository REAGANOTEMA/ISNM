<?php
require_once 'auth-service.php';
require_once 'config/database.php';

if (session_status() === PHP_SESSION_NONE) session_start();

$auth_service = new AuthenticationService();

// Validate token from URL
$token = $_GET['token'] ?? $_POST['token'] ?? '';
$validToken = false;
$studentName = '';

if ($token) {
    $conn = getStudentsConnection();
    if ($conn) {
        $stmt = $conn->prepare("SELECT spr.id, spr.student_id, CONCAT(s.first_name,' ',s.surname) full_name FROM student_password_resets spr JOIN students s ON spr.student_id = s.id WHERE spr.reset_token = ? AND spr.expires_at > NOW() AND spr.is_used = 0 LIMIT 1");
        $stmt->bind_param('s', $token);
        if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
        $r = $stmt->get_result();
        if ($row = $r->fetch_assoc()) {
            $validToken = true;
            $studentName = $row['full_name'];
            $_SESSION['reset_student_id'] = (int)$row['student_id'];
            $_SESSION['reset_token_id'] = (int)$row['id'];
        }
        $stmt->close();
    }
}

// Handle password reset submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'reset_password') {
    $newPassword = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    $token = $_POST['token'] ?? '';

    if (strlen($newPassword) < 8) {
        $_SESSION['error'] = 'Password must be at least 8 characters long.';
        header('Location: student-reset-password.php?token=' . urlencode($token)); exit;
    }
    if ($newPassword !== $confirm) {
        $_SESSION['error'] = 'Passwords do not match.';
        header('Location: student-reset-password.php?token=' . urlencode($token)); exit;
    }

    $conn = getStudentsConnection();
    if ($conn) {
        $stmt = $conn->prepare("SELECT spr.id, spr.student_id FROM student_password_resets spr WHERE spr.reset_token = ? AND spr.expires_at > NOW() AND spr.is_used = 0 LIMIT 1");
        $stmt->bind_param('s', $token);
        if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
        $r = $stmt->get_result();
        if ($row = $r->fetch_assoc()) {
            $studentId = (int)$row['student_id'];
            $resetId = (int)$row['id'];

            $hash = password_hash($newPassword, PASSWORD_BCRYPT);

            $update = $conn->prepare("UPDATE students SET password = ?, password_changed = TRUE, is_first_login = FALSE, login_attempts = 0, locked_until = NULL, updated_at = NOW() WHERE id = ?");
            $update->bind_param('si', $hash, $studentId);
            if (!$update->execute()) { error_log('$update execute failed: ' . ($update->error ?? 'unknown')); };
            $update->close();

            $used = $conn->prepare("UPDATE student_password_resets SET is_used = 1 WHERE id = ?");
            $used->bind_param('i', $resetId);
            if (!$used->execute()) { error_log('$used execute failed: ' . ($used->error ?? 'unknown')); };
            $used->close();

            $_SESSION['success'] = 'Password has been reset successfully. You can now log in with your new password.';
            header('Location: student-login.php'); exit;
        } else {
            $_SESSION['error'] = 'Invalid or expired reset token. Please request a new password reset.';
            header('Location: student-forgot-password.php'); exit;
        }
        $stmt->close();
    } else {
        $_SESSION['error'] = 'System unavailable. Please try again later.';
        header('Location: student-reset-password.php?token=' . urlencode($token)); exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Reset Password - Student Portal</title>
<link rel="stylesheet" href="assets/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Segoe UI',system-ui,sans-serif;min-height:100vh;display:flex;align-items:center;justify-content:center;background:linear-gradient(135deg,#0f172a 0%,#1e3a5f 50%,#1e293b 100%);padding:20px}
.reset-card{background:#fff;border-radius:20px;padding:40px;width:100%;max-width:460px;box-shadow:0 25px 60px rgba(0,0,0,.3)}
.reset-card h1{font-size:1.5rem;font-weight:700;color:#0f172a;margin-bottom:4px}
.reset-card p{color:#64748b;font-size:.875rem;margin-bottom:24px}
.reset-card .form-label{font-size:.8125rem;font-weight:500;color:#374151;margin-bottom:4px}
.reset-card .form-control{border-radius:10px;padding:10px 14px;font-size:.875rem;border:1.5px solid #d1d5db;transition:all .2s}
.reset-card .form-control:focus{border-color:#059669;box-shadow:0 0 0 3px rgba(5,150,105,.15)}
.reset-card .btn-reset{width:100%;padding:11px;border-radius:10px;border:none;background:linear-gradient(135deg,#059669,#047857);color:#fff;font-weight:600;font-size:.875rem;transition:all .2s}
.reset-card .btn-reset:hover{transform:translateY(-1px);box-shadow:0 4px 12px rgba(5,150,105,.4)}
.reset-card .back-link{display:block;text-align:center;margin-top:16px;font-size:.8125rem;color:#64748b;text-decoration:none}
.reset-card .back-link:hover{color:#059669}
.password-hint{font-size:.75rem;color:#64748b;margin-top:4px}
</style>
</head>
<body>
<div class="reset-card">
    <div class="text-center mb-3">
        <div style="width:56px;height:56px;border-radius:14px;background:linear-gradient(135deg,#059669,#047857);display:flex;align-items:center;justify-content:center;margin:0 auto 12px">
            <i class="fas fa-lock" style="color:#fff;font-size:22px"></i>
        </div>
        <h1>Set New Password</h1>
        <?php if ($validToken && $studentName): ?>
        <p>Hello <strong><?= htmlspecialchars($studentName) ?></strong>, choose a new password for your account.</p>
        <?php else: ?>
        <p>Enter the reset token to set a new password.</p>
        <?php endif; ?>
    </div>

    <?php if (!empty($_SESSION['error'])): ?>
    <div class="alert alert-danger py-2 px-3" style="border-radius:10px;font-size:.8125rem"><?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></div>
    <?php endif; ?>

    <?php if (!$token): ?>
    <div class="alert alert-warning py-2 px-3" style="border-radius:10px;font-size:.8125rem">
        <i class="fas fa-exclamation-triangle me-1"></i>No reset token provided. Please use the link from your password reset email.
    </div>
    <a href="student-forgot-password.php" class="back-link"><i class="fas fa-arrow-left me-1"></i>Request a new reset link</a>
    <?php elseif (!$validToken): ?>
    <div class="alert alert-danger py-2 px-3" style="border-radius:10px;font-size:.8125rem">
        <i class="fas fa-times-circle me-1"></i>This reset link is invalid or has expired. Tokens expire 1 hour after request.
    </div>
    <a href="student-forgot-password.php" class="back-link"><i class="fas fa-arrow-left me-1"></i>Request a new reset link</a>
    <?php else: ?>
    <form method="POST">
        <input type="hidden" name="action" value="reset_password">
        <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
        <div class="mb-3">
            <label class="form-label">New Password</label>
            <input type="password" name="new_password" class="form-control" placeholder="At least 8 characters" minlength="8" required>
            <div class="password-hint"><i class="fas fa-info-circle me-1"></i>Minimum 8 characters</div>
        </div>
        <div class="mb-3">
            <label class="form-label">Confirm New Password</label>
            <input type="password" name="confirm_password" class="form-control" placeholder="Repeat your new password" minlength="8" required>
        </div>
        <button type="submit" class="btn-reset"><i class="fas fa-check-circle me-2"></i>Reset Password</button>
    </form>
    <a href="student-login.php" class="back-link"><i class="fas fa-arrow-left me-1"></i>Back to Student Login</a>
    <?php endif; ?>
</div>
</body>
</html>
