<?php
// Include unified authentication system
require_once 'auth-service.php';

// Start secure session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Global authentication service
$auth_service = new AuthenticationService();

// Handle password reset request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    switch ($action) {
        case 'request_reset':
            handlePasswordResetRequest();
            break;
        case 'reset_password':
            handlePasswordReset();
            break;
    }
}

/**
 * Handle password reset request
 */
function handlePasswordResetRequest() {
    global $auth_service;
    
    $email = sanitizeInput($_POST['email'] ?? '');
    
    if (empty($email)) {
        $_SESSION['error'] = 'Email address is required';
        header('Location: staff-password-reset.php');
        exit();
    }
    
    if (!validateEmail($email)) {
        $_SESSION['error'] = 'Invalid email format';
        header('Location: staff-password-reset.php');
        exit();
    }
    
    // Check if user exists
    $conn = getConnection();
    $stmt = $conn->prepare("SELECT id, full_name, role FROM users WHERE email = ? AND role != 'student'");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $_SESSION['error'] = 'No account found with this email address';
        header('Location: staff-password-reset.php');
        exit();
    }
    
    $user = $result->fetch_assoc();
    
    // Generate reset token
    $reset_token = bin2hex(random_bytes(32));
    $reset_expiry = date('Y-m-d H:i:s', time() + 3600); // 1 hour expiry
    
    // Store reset token
    $update_stmt = $conn->prepare("UPDATE users SET reset_token = ?, reset_expiry = ? WHERE email = ?");
    $update_stmt->bind_param("sss", $reset_token, $reset_expiry, $email);
    $update_stmt->execute();
    
    // In a real system, you would send an email here
    // For now, show the reset link directly
    $_SESSION['reset_token'] = $reset_token;
    $_SESSION['reset_email'] = $email;
    $_SESSION['success'] = 'Password reset link generated. Please check below for your reset link.';
    header('Location: staff-password-reset.php');
    exit();
}

/**
 * Handle actual password reset
 */
function handlePasswordReset() {
    global $auth_service;
    
    $token = sanitizeInput($_POST['token'] ?? '');
    $new_password = sanitizeInput($_POST['new_password'] ?? '');
    $confirm_password = sanitizeInput($_POST['confirm_password'] ?? '');
    
    if (empty($token) || empty($new_password) || empty($confirm_password)) {
        $_SESSION['error'] = 'All fields are required';
        header('Location: staff-password-reset.php');
        exit();
    }
    
    if ($new_password !== $confirm_password) {
        $_SESSION['error'] = 'Passwords do not match';
        header('Location: staff-password-reset.php');
        exit();
    }
    
    // Use auth service to reset password
    $result = $auth_service->resetPasswordWithToken($token, $new_password);
    
    if ($result['success']) {
        $_SESSION['success'] = 'Password has been reset successfully. You can now login with your new password.';
        header('Location: staff-login.php');
        exit();
    } else {
        $_SESSION['error'] = $result['message'];
        header('Location: staff-password-reset.php');
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Password Reset - ISNM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="icon" type="image/x-icon" href="images/school-logo.png">
    <style>
        :root {
            --primary-color: #3E2723;
            --secondary-color: #1A237E;
            --accent-color: #FFD700;
            --success-color: #28a745;
            --danger-color: #dc3545;
            --warning-color: #ffc107;
            --info-color: #17a2b8;
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

        .reset-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
            max-width: 500px;
            width: 100%;
            min-height: 600px;
            display: flex;
            flex-direction: column;
        }

        .reset-header {
            background: var(--primary-color);
            color: white;
            padding: 30px 20px;
            text-align: center;
        }

        .reset-header img {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            border: 3px solid var(--accent-color);
            margin-bottom: 15px;
        }

        .reset-header h2 {
            margin: 0;
            font-size: 1.5rem;
            font-weight: 600;
        }

        .reset-header p {
            margin: 5px 0 0;
            opacity: 0.9;
            font-size: 0.9rem;
        }

        .reset-form {
            padding: 30px;
            flex: 1;
        }

        .form-group {
            margin-bottom: 20px;
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
            outline: none;
        }

        .btn-reset {
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 10px;
            padding: 15px;
            font-size: 16px;
            font-weight: 600;
            width: 100%;
            transition: all 0.3s ease;
            min-height: 50px;
            cursor: pointer;
        }

        .btn-reset:hover {
            background: var(--secondary-color);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }

        .btn-reset:active {
            transform: translateY(0);
        }

        .alert {
            border-radius: 10px;
            margin-bottom: 20px;
            border: none;
            padding: 15px;
        }

        .reset-link-section {
            background: #e8f5e8;
            border: 1px solid #c3e6c3;
            padding: 15px;
            margin: 20px 0;
            border-radius: 5px;
            font-size: 0.85rem;
        }

        .reset-link {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
        }

        .reset-link:hover {
            text-decoration: underline;
        }

        @media (max-width: 768px) {
            body {
                padding: 10px;
            }

            .reset-container {
                max-width: 100%;
                min-height: auto;
                border-radius: 15px;
            }

            .reset-header {
                padding: 25px 20px;
            }

            .reset-form {
                padding: 20px;
            }

            .form-control {
                padding: 12px 15px;
                font-size: 16px;
            }

            .btn-reset {
                padding: 12px;
                font-size: 15px;
                min-height: 48px;
            }
        }
    </style>
</head>

<body>
    <div class="reset-container">
        <div class="reset-header">
            <img src="images/school-logo.png" alt="ISNM Logo">
            <h2>Staff Password Reset</h2>
            <p>Reset your ISNM staff account password</p>
        </div>
        
        <div class="reset-form">
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger">
                    <?php 
                    echo htmlspecialchars($_SESSION['error']);
                    unset($_SESSION['error']);
                    ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success">
                    <?php 
                    echo htmlspecialchars($_SESSION['success']);
                    unset($_SESSION['success']);
                    ?>
                </div>
            <?php endif; ?>

            <?php if (!isset($_SESSION['reset_token'])): ?>
                <!-- Request Reset Form -->
                <form method="POST" action="staff-password-reset.php">
                    <input type="hidden" name="action" value="request_reset">
                    
                    <div class="form-group">
                        <label for="email" class="form-label">Email Address</label>
                        <input type="email" class="form-control" id="email" name="email" 
                               placeholder="Enter your staff email" required autocomplete="email">
                    </div>
                    
                    <button type="submit" class="btn-reset">
                        <i class="fas fa-key me-2"></i>Request Password Reset
                    </button>
                </form>
            <?php else: ?>
                <!-- Reset Token Display -->
                <div class="reset-link-section">
                    <h6><i class="fas fa-info-circle me-2"></i>Password Reset Link:</h6>
                    <p>Use this link to reset your password:</p>
                    <a href="staff-password-reset.php?token=<?php echo htmlspecialchars($_SESSION['reset_token']); ?>" class="reset-link">
                        staff-password-reset.php?token=<?php echo htmlspecialchars($_SESSION['reset_token']); ?>
                    </a>
                </div>

                <!-- Reset Form -->
                <form method="POST" action="staff-password-reset.php">
                    <input type="hidden" name="action" value="reset_password">
                    <input type="hidden" name="token" value="<?php echo htmlspecialchars($_SESSION['reset_token']); ?>">
                    
                    <div class="form-group">
                        <label for="new_password" class="form-label">New Password</label>
                        <input type="password" class="form-control" id="new_password" name="new_password" 
                               placeholder="Enter new password" required minlength="8">
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password" class="form-label">Confirm Password</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                               placeholder="Confirm new password" required minlength="8">
                    </div>
                    
                    <button type="submit" class="btn-reset">
                        <i class="fas fa-lock me-2"></i>Reset Password
                    </button>
                </form>
            <?php endif; ?>
            
            <div class="text-center mt-4">
                <p class="mb-0">
                    <a href="staff-login.php" class="text-decoration-none">
                        <i class="fas fa-arrow-left me-2"></i>Back to Login
                    </a>
                </p>
                <p class="mt-2">
                    <small>
                        Student? <a href="student-login.php" class="text-decoration-none">Click here for student login</a>
                    </small>
                </p>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
