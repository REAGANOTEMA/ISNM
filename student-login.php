<?php
// Start secure session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include unified authentication system
require_once 'config/database.php';
include_once 'includes/functions.php';
require_once 'auth-service.php';

// Handle student login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'student_login') {
    // The auth-handler.php will process this automatically
    // No need to handle here - it's already handled in auth-handler.php
}

// Check if user is already logged in and session is valid
if (isset($_SESSION['user_id']) && $auth_service->isAuthenticated()) {
    if ($_SESSION['type'] === 'student') {
        header("Location: dashboards/student.php");
        exit();
    } else {
        // User is logged in but not a student - redirect to appropriate dashboard
        $dashboard = $auth_service->getDashboardRoute($_SESSION['role']);
        header("Location: $dashboard");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, shrink-to-fit=no">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="theme-color" content="#3E2723">
    <title>Student Login - ISNM School Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="icon" type="image/x-icon" href="images/school-logo.png">
    <link rel="apple-touch-icon" href="images/school-logo.png">
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

        * {
            box-sizing: border-box;
            -webkit-box-sizing: border-box;
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
            overflow-x: hidden;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        .login-container {
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

        .login-header {
            background: var(--primary-color);
            color: white;
            padding: 30px 20px;
            text-align: center;
            position: relative;
        }

        .login-header img {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            border: 3px solid var(--accent-color);
            margin-bottom: 15px;
        }

        .login-header h2 {
            margin: 0;
            font-size: 1.5rem;
            font-weight: 600;
        }

        .login-header p {
            margin: 5px 0 0;
            opacity: 0.9;
            font-size: 0.9rem;
        }

        .login-form {
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

        .btn-login {
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
            touch-action: manipulation;
        }

        .btn-login:hover {
            background: var(--secondary-color);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        .security-notice {
            background: #f8f9fa;
            border-left: 4px solid var(--info-color);
            padding: 15px;
            margin: 20px 0;
            border-radius: 5px;
            font-size: 0.85rem;
        }

        .sample-credentials {
            background: #e8f5e8;
            border: 1px solid #c3e6c3;
            padding: 15px;
            margin: 20px 0;
            border-radius: 5px;
            font-size: 0.85rem;
        }

        .sample-credentials h6 {
            color: var(--success-color);
            margin-bottom: 10px;
        }

        .alert {
            border-radius: 10px;
            margin-bottom: 20px;
            border: none;
            padding: 15px;
        }

        /* Perfect Mobile Styles */
        @media (max-width: 768px) {
            body {
                padding: 10px;
            }

            .login-container {
                max-width: 100%;
                min-height: auto;
                border-radius: 15px;
            }

            .login-header {
                padding: 25px 20px;
            }

            .login-header img {
                width: 60px;
                height: 60px;
            }

            .login-header h2 {
                font-size: 1.3rem;
            }

            .login-form {
                padding: 20px;
            }

            .form-control {
                padding: 12px 15px;
                font-size: 16px; /* Prevents zoom on iOS */
            }

            .btn-login {
                padding: 12px;
                font-size: 15px;
                min-height: 48px;
            }

            .security-notice,
            .sample-credentials {
                font-size: 0.8rem;
                padding: 12px;
            }
        }

        @media (max-width: 480px) {
            body {
                padding: 5px;
            }

            .login-container {
                border-radius: 10px;
            }

            .login-header {
                padding: 20px 15px;
            }

            .login-header img {
                width: 50px;
                height: 50px;
            }

            .login-header h2 {
                font-size: 1.2rem;
            }

            .login-form {
                padding: 15px;
            }

            .form-group {
                margin-bottom: 15px;
            }

            .btn-login {
                padding: 10px;
                font-size: 14px;
                min-height: 44px;
            }
        }

        /* Landscape Mobile */
        @media (max-height: 600px) and (orientation: landscape) {
            .login-container {
                min-height: auto;
                max-height: 90vh;
                overflow-y: auto;
            }

            .login-header {
                padding: 15px;
            }

            .login-form {
                padding: 15px;
            }
        }

        /* iOS Specific */
        @supports (-webkit-touch-callout: none) {
            .form-control {
                -webkit-appearance: none;
                border-radius: 10px;
            }

            .btn-login {
                -webkit-appearance: none;
                -webkit-user-select: none;
            }
        }
    </style>
</head>

<body>
    <div class="login-container">
        <div class="login-header">
            <img src="images/school-logo.png" alt="ISNM Logo" class="school-logo">
            <h2>Student Portal</h2>
            <p>Iganga School of Nursing and Midwifery</p>
        </div>
        
        <div class="login-form">
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

            <form method="POST" action="auth-handler.php">
                <input type="hidden" name="action" value="student_login">
                
                <div class="form-group">
                    <label for="index_number" class="form-label">Index Number</label>
                    <input type="text" class="form-control" id="index_number" name="index_number" 
                           placeholder="e.g., U001/CM/056/16" required autocomplete="username">
                </div>
                
                <div class="form-group">
                    <label for="full_name" class="form-label">Full Name</label>
                    <input type="text" class="form-control" id="full_name" name="full_name" 
                           placeholder="Enter your full name" required autocomplete="name">
                </div>
                
                <div class="form-group">
                    <label for="phone" class="form-label">Phone Number</label>
                    <input type="tel" class="form-control" id="phone" name="phone" 
                           placeholder="e.g., 0771234567" required autocomplete="tel">
                </div>
                
                <button type="submit" class="btn-login">
                    <i class="fas fa-sign-in-alt me-2"></i>Login to Student Portal
                </button>
            </form>
            
            <div class="security-notice">
                <i class="fas fa-shield-alt me-2"></i>
                <strong>Security Notice:</strong> This is a secure portal. Your login information is encrypted and protected.
            </div>
            
            <div class="sample-credentials">
                <h6><i class="fas fa-info-circle me-2"></i>Sample Credentials for Testing:</h6>
                <p><strong>Index:</strong> U001/CM/056/16</p>
                <p><strong>Name:</strong> Aisha Nakato</p>
                <p><strong>Phone:</strong> 0771234567</p>
            </div>
            
            <div class="text-center mt-4">
                <p class="mb-0">
                    <a href="index.php" class="text-decoration-none">
                        <i class="fas fa-arrow-left me-2"></i>Back to Home
                    </a>
                </p>
                <p class="mt-2">
                    <small>
                        Staff? <a href="staff-login.php" class="text-decoration-none">Click here for staff login</a>
                    </small>
                </p>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Prevent zoom on iOS when focusing inputs
        document.addEventListener('DOMContentLoaded', function() {
            const inputs = document.querySelectorAll('input[type="text"], input[type="tel"]');
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
