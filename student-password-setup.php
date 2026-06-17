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
    <title>Set Student Password | ISNM</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #f8fafc;
            margin: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .card {
            width: 100%;
            max-width: 520px;
            border-radius: 1rem;
            box-shadow: 0 28px 60px rgba(15, 23, 42, 0.35);
            overflow: hidden;
            border: none;
        }
        .card-header {
            background: #0ea5e9;
            color: white;
            padding: 2rem;
            text-align: center;
        }
        .card-body {
            background: white;
            color: #0f172a;
            padding: 2rem;
        }
        .form-label {
            font-weight: 600;
        }
        .btn-primary {
            background: #0ea5e9;
            border-color: #0ea5e9;
        }
        .hint-box {
            background: #f8fafc;
            border-left: 4px solid #0ea5e9;
            padding: 1rem 1rem 1rem 1rem;
            margin-bottom: 1rem;
            color: #334155;
            border-radius: 0.65rem;
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="card-header">
            <i class="fas fa-key fa-2x"></i>
            <h2 class="mt-3">Set Your Student Portal Password</h2>
            <p class="mb-0">Secure your account with a strong password before accessing the student dashboard.</p>
        </div>
        <div class="card-body">
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

            <div class="hint-box">
                <strong>Student:</strong> <?php echo htmlspecialchars($pending['full_name'] ?? 'Unknown'); ?><br>
                <strong>Index Number:</strong> <?php echo htmlspecialchars($pending['index_number'] ?? 'Unknown'); ?>
            </div>

            <form method="POST" action="auth-handler.php">
                <input type="hidden" name="action" value="student_set_password">

                <div class="mb-3">
                    <label class="form-label" for="password">New Password</label>
                    <input id="password" name="password" type="password" class="form-control" required minlength="8" placeholder="Enter a new password">
                </div>

                <div class="mb-3">
                    <label class="form-label" for="confirm_password">Confirm Password</label>
                    <input id="confirm_password" name="confirm_password" type="password" class="form-control" required minlength="8" placeholder="Re-enter your password">
                </div>

                <div class="mb-3 text-muted" style="font-size:.95rem;">
                    Use at least 8 characters. Choose a password that you can remember but others cannot guess.
                </div>

                <button type="submit" class="btn btn-primary w-100 py-2">
                    <i class="fas fa-lock-open me-2"></i>Save Password &amp; Continue
                </button>
            </form>

            <div class="mt-4 text-center">
                <a href="student-login.php" class="text-decoration-none">Back to Student Login</a>
            </div>
        </div>
    </div>
</body>
</html>
