<?php
// Start secure session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include modern authentication handler
require_once 'auth-handler.php';

// Store position from organogram if provided
$requested_position = isset($_GET['position']) ? urldecode($_GET['position']) : '';
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

// Handle staff login using modern auth handler
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitizeInput($_POST['email']);
    $password = sanitizeInput($_POST['password']);
    
    // Use modern authentication service
    $auth_result = $auth_service->authenticateStaff($email, $password);
    
    if ($auth_result['success']) {
        // Use requested position from organogram if available, otherwise use user's role
        $target_role = isset($_SESSION['requested_position']) ? $_SESSION['requested_position'] : $auth_result['user']['role'];
        
        // Clear the requested position after use
        unset($_SESSION['requested_position']);
        
        // Get smart dashboard route
        $dashboard = $auth_service->getDashboardRoute($target_role);
        header("Location: $dashboard");
        exit();
    } else {
        $_SESSION['error'] = $auth_result['message'];
        header("Location: staff-login.php");
        exit();
    }
}

// Check if user is already logged in and session is valid
if (isset($_SESSION['user_id']) && $auth_service->checkSessionValidity()) {
    if ($_SESSION['type'] === 'staff') {
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Login - ISNM School Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #1a237e;
            --secondary-color: #3949ab;
            --accent-color: #ffd700;
            --success-color: #28a745;
            --danger-color: #dc3545;
            --warning-color: #ffc107;
            --info-color: #17a2b8;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            padding: 20px;
        }

        .login-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
            max-width: 500px;
            width: 100%;
            min-height: 600px;
        }

        .login-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 3rem 2rem;
            text-align: center;
            position: relative;
        }

        .school-logo {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            margin-bottom: 1.5rem;
            border: 4px solid var(--accent-color);
        }

        .school-name {
            font-size: 1.6rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
            color: var(--accent-color);
        }

        .school-motto {
            font-size: 0.9rem;
            opacity: 0.9;
        }

        .login-content {
            padding: 3rem 2rem;
        }

        .login-title {
            font-size: 1.8rem;
            font-weight: bold;
            color: var(--primary-color);
            margin-bottom: 1rem;
            text-align: center;
        }

        .login-subtitle {
            color: #666;
            font-size: 1rem;
            text-align: center;
            margin-bottom: 2rem;
        }

        .security-notice {
            background: #e3f2fd;
            border-left: 4px solid var(--info-color);
            padding: 1rem;
            margin-bottom: 2rem;
            border-radius: 8px;
            font-size: 0.9rem;
        }

        .security-notice i {
            color: var(--info-color);
            margin-right: 0.5rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            font-weight: 600;
            color: #333;
            margin-bottom: 0.5rem;
            display: block;
        }

        .form-control {
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            padding: 0.75rem 1rem;
            font-size: 1rem;
            transition: all 0.3s ease;
            width: 100%;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(26, 35, 126, 0.25);
            outline: none;
        }

        .input-icon {
            position: relative;
        }

        .input-icon i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #666;
            z-index: 1;
        }

        .input-icon .form-control {
            padding-left: 45px;
        }

        .btn-login {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            border: none;
            border-radius: 10px;
            padding: 1rem;
            font-size: 1.1rem;
            font-weight: 600;
            width: 100%;
            transition: all 0.3s ease;
            margin-top: 1rem;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(26, 35, 126, 0.3);
        }

        .login-help {
            text-align: center;
            margin-top: 1.5rem;
        }

        .login-help a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
        }

        .login-help a:hover {
            text-decoration: underline;
        }

        .alert {
            border-radius: 10px;
            border: none;
            margin-bottom: 1.5rem;
        }

        .back-to-main {
            text-align: center;
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid #e0e0e0;
        }

        .back-to-main a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
        }

        .back-to-main a:hover {
            text-decoration: underline;
        }

        .sample-info {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1rem;
            margin-top: 2rem;
            font-size: 0.85rem;
        }

        .sample-info h6 {
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }

        .sample-info table {
            width: 100%;
            margin-top: 0.5rem;
        }

        .sample-info th,
        .sample-info td {
            padding: 0.25rem;
            font-size: 0.8rem;
            text-align: left;
        }

        .sample-info th {
            font-weight: 600;
            color: #333;
        }

        @media (max-width: 768px) {
            .login-container {
                max-width: 400px;
            }

            .login-header {
                padding: 2rem 1.5rem;
            }

            .login-content {
                padding: 2rem 1.5rem;
            }

            .school-logo {
                width: 80px;
                height: 80px;
            }

            .school-name {
                font-size: 1.4rem;
            }

            .login-title {
                font-size: 1.5rem;
            }
        }

        .fade-in {
            animation: fadeIn 0.5s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>
    <div class="login-container fade-in">
        <div class="login-header">
            <img src="images/school-logo.png" alt="ISNM Logo" class="school-logo">
            <h2 class="school-name">IGANGA SCHOOL OF NURSING AND MIDWIFERY</h2>
            <p class="school-motto">Excellence in Healthcare Education</p>
        </div>

        <div class="login-content">
            <h1 class="login-title">Staff Login</h1>
            <p class="login-subtitle">Access your staff account</p>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($_SESSION['error']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>

            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($_SESSION['success']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>

            <div class="security-notice">
                <i class="fas fa-info-circle"></i>
                Staff login with their username and password
            </div>
            
            <form method="POST" action="staff-login.php">
                <div class="form-group">
                    <label class="form-label" for="email">Email *</label>
                    <div class="input-icon">
                        <i class="fas fa-envelope"></i>
                        <input type="email" class="form-control" id="email" name="email" 
                               placeholder="Enter your email" required>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="password">Password *</label>
                    <div class="input-icon">
                        <i class="fas fa-lock"></i>
                        <input type="password" class="form-control" id="password" name="password" 
                               placeholder="Enter your password" required>
                    </div>
                </div>

                <button type="submit" class="btn btn-login">
                    <i class="fas fa-sign-in-alt"></i> Login as Staff
                </button>
            </form>

            <div class="login-help">
                <a href="forgot-password.php">Forgot your password?</a>
            </div>

            <div class="sample-info">
                <h6>Sample Login Credentials:</h6>
                <table>
                    <tr>
                        <th>Role</th>
                        <th>Username</th>
                        <th>Password</th>
                    </tr>
                    <tr>
                        <td>Director General</td>
                        <td>john.mugisha</td>
                        <td>password</td>
                    </tr>
                    <tr>
                        <td>Principal</td>
                        <td>peter.lutaaya</td>
                        <td>password</td>
                    </tr>
                    <tr>
                        <td>Academic Registrar</td>
                        <td>henry.mugisha</td>
                        <td>password</td>
                    </tr>
                    <tr>
                        <td>Bursar</td>
                        <td>patience.nabasumba</td>
                        <td>password</td>
                    </tr>
                </table>
            </div>

            <div class="back-to-main">
                <a href="student-login.php">← Student Login</a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-hide alerts
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                if (alert.style.display !== 'none') {
                    alert.style.transition = 'opacity 0.5s';
                    alert.style.opacity = '0';
                    setTimeout(() => alert.remove(), 500);
                }
            });
        }, 5000);

        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const inputs = this.querySelectorAll('input[required]');
            let isValid = true;
            
            inputs.forEach(input => {
                if (!input.value.trim()) {
                    isValid = false;
                    input.classList.add('is-invalid');
                } else {
                    input.classList.remove('is-invalid');
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                showAlert('Please fill in all required fields', 'danger');
            }
        });

        // Show alert helper
        function showAlert(message, type = 'info') {
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
            alertDiv.innerHTML = `
                <i class="fas fa-info-circle"></i> ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            
            const container = document.querySelector('.login-content');
            container.insertBefore(alertDiv, container.firstChild);
            
            setTimeout(() => {
                alertDiv.style.opacity = '0';
                setTimeout(() => alertDiv.remove(), 500);
            }, 5000);
        }

        // Auto-focus first input
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelector('#username').focus();
        });
    </script>
</body>
</html>
