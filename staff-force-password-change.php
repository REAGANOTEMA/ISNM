<?php
require_once 'auth-service.php';
require_once 'config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$auth_service = new AuthenticationService();
$force_reason = '';

// Must be logged in as staff
if (!$auth_service->isAuthenticated() || ($_SESSION['type'] ?? '') !== 'staff') {
    header('Location: staff-login.php');
    exit();
}

// Verify is_first_login is still true in DB
$conn = getStaffConnection();
if ($conn) {
    $stmt = $conn->prepare("SELECT is_first_login FROM staff WHERE id = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param('i', $_SESSION['user_id']);
        if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$row || empty($row['is_first_login'])) {
            // Already changed password — redirect to dashboard
            $dashboard = $auth_service->getDashboardRoute($_SESSION['role'] ?? '');
            header('Location: ' . ($dashboard ?: 'index.php'));
            exit();
        }
    }
} else {
    $force_reason = 'Database unavailable. Please contact the system administrator.';
}

// Handle password change POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'change_password') {
    $new_password     = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($new_password) || empty($confirm_password)) {
        $error = 'All fields are required.';
    } elseif ($new_password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } elseif (strlen($new_password) < 8) {
        $error = 'Password must be at least 8 characters.';
    } else {
        $hash = password_hash($new_password, PASSWORD_DEFAULT);
        $conn = getStaffConnection();
        if ($conn) {
            $update = $conn->prepare("UPDATE staff SET password = ?, is_first_login = 0, password_changed = 1 WHERE id = ?");
            if ($update) {
                $update->bind_param('si', $hash, $_SESSION['user_id']);
                if ($update->execute()) {
                    $update->close();
                    $_SESSION['success'] = 'Password changed successfully. Welcome to the system!';
                    $dashboard = $auth_service->getDashboardRoute($_SESSION['role'] ?? '');
                    header('Location: ' . ($dashboard ?: 'index.php'));
                    exit();
                }
                $update->close();
            }
        }
        $error = 'Failed to update password. Please try again.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Set Your Password | ISNM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/3d-buttons.css">
    <link rel="icon" type="image/x-icon" href="images/school-logo.png">
    <style>
        :root {
            --primary-color: #3E2723;
            --secondary-color: #1A237E;
            --accent-color: #FFD700;
        }
        body {
            font-family: 'Poppins', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            padding: 20px;
        }
        .card-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
            max-width: 500px;
            width: 100%;
        }
        .card-header {
            background: var(--primary-color);
            color: white;
            padding: 30px 20px;
            text-align: center;
        }
        .card-header img {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            border: 3px solid var(--accent-color);
            margin-bottom: 15px;
        }
        .card-header h2 {
            margin: 0;
            font-size: 1.5rem;
            font-weight: 600;
        }
        .card-header p {
            margin: 5px 0 0;
            opacity: 0.9;
        }
        .card-body {
            padding: 30px;
        }
        .form-control {
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            padding: 15px;
            font-size: 16px;
            transition: all 0.3s ease;
            height: auto;
        }
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(62, 39, 35, 0.1);
        }
        .btn-change {
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 10px;
            padding: 15px;
            font-size: 16px;
            font-weight: 600;
            width: 100%;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        .btn-change:hover {
            background: var(--secondary-color);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        .alert {
            border-radius: 10px;
            margin-bottom: 20px;
            border: none;
            padding: 15px;
        }
    </style>
</head>
<body>
    <div class="card-container">
        <div class="card-header">
            <img src="images/school-logo.png" alt="ISNM Logo">
            <h2>Welcome, <?php echo htmlspecialchars($_SESSION['full_name'] ?? 'Staff'); ?>!</h2>
            <p>Please set your password to continue</p>
        </div>
        <div class="card-body">
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <?php if (!empty($force_reason)): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($force_reason); ?></div>
            <?php endif; ?>

            <p class="text-muted mb-4">
                <i class="fas fa-info-circle me-2"></i>
                This is your first login or your password has been reset by an administrator.
                Please choose a new password to activate your account.
            </p>

            <form method="POST" action="staff-force-password-change.php">
                <input type="hidden" name="action" value="change_password">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">

                <div class="mb-3">
                    <label for="new_password" class="form-label">New Password</label>
                    <input type="password" class="form-control" id="new_password" name="new_password"
                           placeholder="Enter new password" required minlength="8">
                    <div class="form-text">Minimum 8 characters.</div>
                </div>

                <div class="mb-3">
                    <label for="confirm_password" class="form-label">Confirm Password</label>
                    <input type="password" class="form-control" id="confirm_password" name="confirm_password"
                           placeholder="Confirm new password" required minlength="8">
                </div>

                <button type="submit" class="btn-change">
                    <i class="fas fa-lock me-2"></i>Set Password &amp; Continue
                </button>
            </form>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
