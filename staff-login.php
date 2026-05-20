<?php
// Start secure session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Use unified configuration
require_once 'config/database.php';
include_once 'includes/functions.php';
require_once 'auth-service.php';

// Global authentication service
$auth_service = new AuthenticationService();

// Store position from organogram if provided
$requested_position = isset($_GET['position']) ? urldecode($_GET['position']) : '';
$resolved_role = $requested_position ? $auth_service->resolveOrganogramPosition($requested_position) : '';
$suggested_email = $resolved_role ? $auth_service->getStaffEmailForRole($resolved_role) : '';
if ($requested_position) {
    $_SESSION['requested_position'] = $requested_position;
}

// Handle student role redirection
$student_role = isset($_GET['student_role']) ? urldecode($_GET['student_role']) : '';
if ($student_role) {
    $_SESSION['student_role'] = $student_role;
    header("Location: student-login.php");
    exit();
}

// Check if user is already logged in
if ($auth_service->isAuthenticated() && isset($_SESSION['type']) && $_SESSION['type'] === 'staff') {
    $sessionRole = $_SESSION['role'] ?? '';
    $dashboard = $auth_service->getDashboardRoute($sessionRole);

    if (!empty($requested_position) && $auth_service->positionMatchesRole($requested_position, $sessionRole)) {
        $dashboard = $auth_service->getDashboardRoute($auth_service->resolveOrganogramPosition($requested_position));
    }

    header("Location: $dashboard");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, shrink-to-fit=no">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="theme-color" content="#1a237e">
    <link rel="manifest" href="manifest.json">
    <title>Staff Login - ISNM School Management System</title>
    <link rel="icon" type="image/x-icon" href="images/school-logo.png">
    <link rel="apple-touch-icon" href="images/school-logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #1a237e;
            --primary-dark: #0d47a1;
            --primary-light: #534bae;
            --secondary-color: #ff6f00;
            --accent-color: #ffd600;
            --success-color: #2e7d32;
            --danger-color: #c62828;
            --warning-color: #f57f17;
            --info-color: #0277bd;
            --text-dark: #212121;
            --text-light: #757575;
            --bg-light: #f5f5f5;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Poppins', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-light) 50%, var(--primary-dark) 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            padding: 20px;
            overflow-x: hidden;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        .login-wrapper {
            width: 100%;
            max-width: 480px;
            margin: 0 auto;
        }

        .login-card {
            background: white;
            border-radius: 24px;
            box-shadow: 0 24px 48px rgba(0, 0, 0, 0.2);
            overflow: hidden;
            animation: slideUp 0.6s ease-out;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .login-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
            color: white;
            padding: 40px 30px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .login-header::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            animation: rotate 20s linear infinite;
        }

        @keyframes rotate {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        .login-header-content {
            position: relative;
            z-index: 1;
        }

        .logo-container {
            width: 100px;
            height: 100px;
            margin: 0 auto 20px;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.2);
            border: 4px solid var(--accent-color);
        }

        .logo-container img {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
        }

        .login-header h1 {
            font-size: 1.75rem;
            font-weight: 700;
            margin: 0 0 8px;
            letter-spacing: -0.5px;
        }

        .login-header p {
            margin: 0;
            opacity: 0.95;
            font-size: 0.95rem;
            font-weight: 300;
        }

        .login-body {
            padding: 40px 30px;
        }

        .form-group {
            margin-bottom: 24px;
        }

        .form-label {
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 8px;
            font-size: 0.9rem;
            display: block;
        }

        .input-group {
            position: relative;
        }

        .input-group i {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-light);
            font-size: 1.1rem;
            z-index: 2;
        }

        .form-control {
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            padding: 14px 16px 14px 48px;
            font-size: 15px;
            transition: all 0.3s ease;
            height: auto;
            background: var(--bg-light);
        }

        .form-control:focus {
            border-color: var(--primary-color);
            background: white;
            box-shadow: 0 0 0 4px rgba(26, 35, 126, 0.1);
            outline: none;
        }

        .form-control::placeholder {
            color: #bdbdbd;
        }

        .btn-login {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
            color: white;
            border: none;
            border-radius: 12px;
            padding: 16px;
            font-size: 16px;
            font-weight: 600;
            width: 100%;
            transition: all 0.3s ease;
            cursor: pointer;
            touch-action: manipulation;
            box-shadow: 0 4px 12px rgba(26, 35, 126, 0.3);
            position: relative;
            overflow: hidden;
        }

        .btn-login::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s ease;
        }

        .btn-login:hover::before {
            left: 100%;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(26, 35, 126, 0.4);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        .alert {
            border-radius: 12px;
            margin-bottom: 24px;
            border: none;
            padding: 16px;
            font-size: 0.9rem;
        }

        .alert-danger {
            background: #ffebee;
            color: var(--danger-color);
            border-left: 4px solid var(--danger-color);
        }

        .alert-success {
            background: #e8f5e9;
            color: var(--success-color);
            border-left: 4px solid var(--success-color);
        }

        .info-section {
            background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
            border-radius: 12px;
            padding: 20px;
            margin: 24px 0;
            border-left: 4px solid var(--info-color);
        }

        .info-section h6 {
            color: var(--primary-color);
            font-weight: 600;
            margin-bottom: 12px;
            font-size: 0.95rem;
        }

        .info-section p {
            margin: 8px 0;
            font-size: 0.85rem;
            color: var(--text-dark);
        }

        .info-section strong {
            color: var(--primary-dark);
        }

        .divider {
            height: 1px;
            background: #e0e0e0;
            margin: 24px 0;
        }

        .help-links {
            text-align: center;
        }

        .help-links a {
            display: block;
            color: var(--primary-color);
            text-decoration: none;
            font-size: 0.9rem;
            margin: 8px 0;
            transition: color 0.3s ease;
        }

        .help-links a:hover {
            color: var(--primary-dark);
            text-decoration: underline;
        }

        .help-links i {
            margin-right: 8px;
        }

        .footer-text {
            text-align: center;
            margin-top: 24px;
            font-size: 0.8rem;
            color: var(--text-light);
        }

        .footer-text a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
        }

        .footer-text a:hover {
            text-decoration: underline;
        }

        /* Responsive Styles */
        @media (max-width: 768px) {
            body {
                padding: 10px;
            }

            .login-card {
                border-radius: 20px;
            }

            .login-header {
                padding: 35px 25px;
            }

            .logo-container {
                width: 90px;
                height: 90px;
            }

            .logo-container img {
                width: 70px;
                height: 70px;
            }

            .login-header h1 {
                font-size: 1.5rem;
            }

            .login-body {
                padding: 30px 25px;
            }

            .form-control {
                padding: 12px 14px 12px 44px;
                font-size: 16px;
            }

            .btn-login {
                padding: 14px;
                font-size: 15px;
            }

            .info-section {
                padding: 16px;
            }
        }

        @media (max-width: 480px) {
            .login-card {
                border-radius: 16px;
            }

            .login-header {
                padding: 30px 20px;
            }

            .logo-container {
                width: 80px;
                height: 80px;
            }

            .logo-container img {
                width: 60px;
                height: 60px;
            }

            .login-header h1 {
                font-size: 1.3rem;
            }

            .login-body {
                padding: 25px 20px;
            }

            .form-group {
                margin-bottom: 20px;
            }

            .form-control {
                padding: 12px 14px 12px 42px;
            }

            .btn-login {
                padding: 12px;
                font-size: 14px;
            }
        }

        @media (max-height: 600px) and (orientation: landscape) {
            .login-card {
                max-height: 90vh;
                overflow-y: auto;
            }

            .login-header {
                padding: 20px;
            }

            .login-body {
                padding: 20px;
            }
        }

        /* iOS Specific */
        @supports (-webkit-touch-callout: none) {
            .form-control {
                -webkit-appearance: none;
                border-radius: 12px;
            }

            .btn-login {
                -webkit-appearance: none;
                -webkit-user-select: none;
            }
        }
    </style>
</head>

<body>
    <div class="login-wrapper">
        <div class="login-card">
            <div class="login-header">
                <div class="login-header-content">
                    <div class="logo-container">
                        <img src="images/school-logo.png" alt="ISNM Logo">
                    </div>
                    <h1>Staff Portal</h1>
                    <p>Iganga School of Nursing and Midwifery</p>
                </div>
            </div>
            
            <div class="login-body">
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <?php 
                        echo htmlspecialchars($_SESSION['error']);
                        unset($_SESSION['error']);
                        ?>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle me-2"></i>
                        <?php 
                        echo htmlspecialchars($_SESSION['success']);
                        unset($_SESSION['success']);
                        ?>
                    </div>
                <?php endif; ?>

                <?php if ($requested_position): ?>
                    <div class="alert alert-info mb-3" role="status">
                        <i class="fas fa-sitemap me-2"></i>
                        Logging in as: <strong><?php echo htmlspecialchars($requested_position); ?></strong>
                    </div>
                <?php endif; ?>

                <form method="POST" action="auth-handler.php">
                    <input type="hidden" name="action" value="staff_login">
                    <?php if ($requested_position): ?>
                    <input type="hidden" name="requested_position" value="<?php echo htmlspecialchars($requested_position); ?>">
                    <?php endif; ?>
                    
                    <div class="form-group">
                        <label for="email" class="form-label">Email Address</label>
                        <div class="input-group">
                            <i class="fas fa-envelope"></i>
                            <input type="email" class="form-control" id="email" name="email" 
                                   placeholder="Enter your staff email" required autocomplete="email"
                                   value="<?php echo $suggested_email ? htmlspecialchars($suggested_email) : ''; ?>">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="password" class="form-label">Password</label>
                        <div class="input-group">
                            <i class="fas fa-lock"></i>
                            <input type="password" class="form-control" id="password" name="password" 
                                   placeholder="Enter your password" required autocomplete="current-password">
                        </div>
                    </div>
                    
                    <button type="submit" class="btn-login">
                        <i class="fas fa-sign-in-alt me-2"></i>Login to Staff Portal
                    </button>
                </form>
                
                <div class="info-section">
                    <h6><i class="fas fa-info-circle me-2"></i>Default Staff Login</h6>
                    <p><strong>Email:</strong> Your staff email address</p>
                    <p><strong>Password:</strong> 12345678 (default for all staff)</p>
                    <p style="margin-top: 12px; font-size: 0.8rem; color: var(--text-light);">
                        <i class="fas fa-shield-alt me-1"></i>
                        Please change your password after first login for security
                    </p>
                </div>
                
                <div class="divider"></div>
                
                <div class="help-links">
                    <a href="staff-password-reset.php">
                        <i class="fas fa-key"></i>Forgot Password?
                    </a>
                    <a href="index.php">
                        <i class="fas fa-arrow-left"></i>Back to Home
                    </a>
                </div>
                
                <div class="footer-text">
                    <p>Student? <a href="student-login.php">Click here for student login</a></p>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Prevent zoom on iOS when focusing inputs
        document.addEventListener('DOMContentLoaded', function() {
            const inputs = document.querySelectorAll('input[type="email"], input[type="password"]');
            inputs.forEach(input => {
                input.addEventListener('focus', function() {
                    document.querySelector('meta[name="viewport"]').setAttribute('content', 'width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no');
                });
                
                input.addEventListener('blur', function() {
                    document.querySelector('meta[name="viewport"]').setAttribute('content', 'width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, shrink-to-fit=no');
                });
            });
        });
    </script>
</body>
</html>
