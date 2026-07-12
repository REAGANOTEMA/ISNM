<?php
require_once 'auth-service.php';
require_once 'config/database.php';

if (session_status() === PHP_SESSION_NONE) session_start();

$auth_service = new AuthenticationService();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'request_reset') {
        $identifier = trim($_POST['identifier'] ?? '');
        if (empty($identifier)) {
            $_SESSION['error'] = 'Please enter your index number, student number, or email address.';
            header('Location: student-forgot-password.php'); exit;
        }

        $conn = getStudentsConnection();
        if (!$conn) {
            $_SESSION['error'] = 'System unavailable. Please try again later.';
            header('Location: student-forgot-password.php'); exit;
        }

        // Look up student by index_number, student_number, or email
        $stmt = $conn->prepare("SELECT id, index_number, student_number, CONCAT(first_name,' ',surname) full_name, email, phone FROM students WHERE (index_number = ? OR student_number = ? OR email = ?) AND status = 'Active' LIMIT 1");
        $stmt->bind_param('sss', $identifier, $identifier, $identifier);
        if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
        $r = $stmt->get_result();
        $student = $r->fetch_assoc();
        $stmt->close();

        if (!$student) {
            $_SESSION['error'] = 'No active student account found with that information.';
            header('Location: student-forgot-password.php'); exit;
        }

        // Generate reset token
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', time() + 3600);
        $studentId = (int)$student['id'];

        // Store in student_password_resets table
        $insert = $conn->prepare("INSERT INTO student_password_resets (student_id, reset_token, expires_at, is_used, created_at) VALUES (?, ?, ?, 0, NOW())");
        $insert->bind_param('iss', $studentId, $token, $expires);
        if (!$insert->execute()) { error_log('$insert execute failed: ' . ($insert->error ?? 'unknown')); };
        $insert->close();

        // Build reset link
        $resetLink = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']) . '/student-reset-password.php?token=' . $token;

        // Try to send email
        $emailSent = false;
        if (!empty($student['email'])) {
            require_once 'includes/email_notifications.php';
            $emailSent = sendProfessionalEmail(
                $student['email'],
                $student['full_name'],
                'Password Reset - Iganga School of Nursing and Midwifery',
                buildProfessionalEmailTemplate(
                    'Password Reset Request',
                    [
                        ['type' => 'text', 'content' => "Hello {$student['full_name']},"],
                        ['type' => 'text', 'content' => 'We received a request to reset your student portal password. Click the button below to set a new password. This link expires in 1 hour.'],
                        ['type' => 'cta', 'content' => 'Reset Password', 'url' => $resetLink],
                        ['type' => 'text', 'content' => 'If you did not request a password reset, please ignore this email. Your account remains secure.'],
                    ],
                    $resetLink
                )
            );
        }

        if ($emailSent) {
            $_SESSION['success'] = 'Password reset instructions have been sent to your registered email address.';
        } else {
            // No email capability or no email on file â€” display the link directly
            $_SESSION['reset_token_display'] = $token;
            $_SESSION['success'] = 'Password reset link generated.';
        }

        header('Location: student-forgot-password.php'); exit;
    }
}

$showToken = $_SESSION['reset_token_display'] ?? null;
unset($_SESSION['reset_token_display']);
$studentConn = getStudentsConnection();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Forgot Password - Student Portal</title>
<link rel="stylesheet" href="assets/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Segoe UI',system-ui,sans-serif;min-height:100vh;display:flex;align-items:center;justify-content:center;background:linear-gradient(135deg,#0f172a 0%,#1e3a5f 50%,#1e293b 100%);padding:20px}
.reset-card{background:#fff;border-radius:20px;padding:40px;width:100%;max-width:460px;box-shadow:0 25px 60px rgba(0,0,0,.3)}
.reset-card h1{font-size:1.5rem;font-weight:700;color:#0f172a;margin-bottom:4px}
.reset-card p{color:#64748b;font-size:.875rem;margin-bottom:24px}
.reset-card .form-label{font-size:.8125rem;font-weight:500;color:#374151;margin-bottom:4px}
.reset-card .form-control,.reset-card .form-select{border-radius:10px;padding:10px 14px;font-size:.875rem;border:1.5px solid #d1d5db;transition:all .2s}
.reset-card .form-control:focus{border-color:#3b82f6;box-shadow:0 0 0 3px rgba(59,130,246,.15)}
.reset-card .btn-reset{width:100%;padding:11px;border-radius:10px;border:none;background:linear-gradient(135deg,#2563eb,#1d4ed8);color:#fff;font-weight:600;font-size:.875rem;transition:all .2s}
.reset-card .btn-reset:hover{transform:translateY(-1px);box-shadow:0 4px 12px rgba(37,99,235,.4)}
.reset-card .back-link{display:block;text-align:center;margin-top:16px;font-size:.8125rem;color:#64748b;text-decoration:none}
.reset-card .back-link:hover{color:#2563eb}
.token-box{background:#f0fdf4;border:1px solid #bbf7d0;border-radius:10px;padding:12px 16px;margin-bottom:20px}
.token-box .token-label{font-size:.75rem;color:#059669;font-weight:600;margin-bottom:4px}
.token-box .token-value{font-size:.8125rem;color:#0f172a;word-break:break-all;font-family:monospace}
.token-box .token-note{font-size:.75rem;color:#64748b;margin-top:4px}
</style>
</head>
<body>
<div class="reset-card">
    <div class="text-center mb-3">
        <div style="width:56px;height:56px;border-radius:14px;background:linear-gradient(135deg,#2563eb,#1d4ed8);display:flex;align-items:center;justify-content:center;margin:0 auto 12px">
            <i class="fas fa-key" style="color:#fff;font-size:22px"></i>
        </div>
        <h1>Forgot Password?</h1>
        <p>Enter your index number, student number, or email to reset your password.</p>
    </div>

    <?php if (!empty($_SESSION['success'])): ?>
    <div class="alert alert-success py-2 px-3" style="border-radius:10px;font-size:.8125rem"><?= htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></div>
    <?php endif; ?>
    <?php if (!empty($_SESSION['error'])): ?>
    <div class="alert alert-danger py-2 px-3" style="border-radius:10px;font-size:.8125rem"><?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></div>
    <?php endif; ?>

    <?php if ($showToken): ?>
    <div class="token-box">
        <div class="token-label"><i class="fas fa-link me-1"></i>Your Reset Link</div>
        <div class="token-value">
            <?php
            $base = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']);
            echo htmlspecialchars($base . '/student-reset-password.php?token=' . $showToken);
            ?>
        </div>
        <div class="token-note"><i class="fas fa-info-circle me-1"></i>This link expires in 1 hour. Copy and share with the student.</div>
    </div>
    <?php endif; ?>

    <form method="POST">
        <input type="hidden" name="action" value="request_reset">
        <div class="mb-3">
            <label class="form-label">Index Number / Student Number / Email</label>
            <input type="text" name="identifier" class="form-control" placeholder="e.g. JUL24/U041/CN/004 or student@example.com" required autofocus>
        </div>
        <button type="submit" class="btn-reset"><i class="fas fa-paper-plane me-2"></i>Send Reset Link</button>
    </form>

    <a href="student-login.php" class="back-link"><i class="fas fa-arrow-left me-1"></i>Back to Student Login</a>
</div>
</body>
</html>
