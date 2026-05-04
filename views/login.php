<?php
/**
 * Login View for ISNM Student Management System
 */

require_once __DIR__ . '/../config/config.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirect('dashboard.php');
}

// Handle login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . '/../controllers/AuthController.php';
    $authController = new AuthController();
    $authController->login();
}

// Get flash messages
$flashMessages = getFlashMessages();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - ISNM Student Management System</title>
    <link rel="icon" type="image/png" href="../images/school-logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
            max-width: 900px;
            width: 100%;
            margin: 20px;
        }
        .login-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            text-align: center;
        }
        .login-header h1 {
            margin: 0;
            font-size: 2rem;
            font-weight: bold;
        }
        .login-header p {
            margin: 0.5rem 0 0;
            opacity: 0.9;
        }
        .login-form {
            padding: 3rem;
        }
        .form-control {
            border-radius: 10px;
            border: 1px solid #e0e0e0;
            padding: 12px 15px;
            font-size: 1rem;
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .btn-login {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 10px;
            padding: 12px;
            font-size: 1rem;
            font-weight: 600;
            color: white;
            width: 100%;
            transition: transform 0.2s;
        }
        .btn-login:hover {
            transform: translateY(-2px);
            color: white;
        }
        .input-group-text {
            background: transparent;
            border: 1px solid #e0e0e0;
            border-right: none;
            border-radius: 10px 0 0 10px;
        }
        .input-group .form-control {
            border-left: none;
            border-radius: 0 10px 10px 0;
        }
        .system-info {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1.5rem;
            margin-top: 2rem;
        }
        .system-info h6 {
            color: #667eea;
            margin-bottom: 1rem;
        }
        .feature-list {
            list-style: none;
            padding: 0;
        }
        .feature-list li {
            padding: 0.25rem 0;
            color: #666;
        }
        .feature-list li i {
            color: #667eea;
            margin-right: 0.5rem;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="row g-0">
            <div class="col-md-6">
                <div class="login-header">
                    <i class="fas fa-graduation-cap fa-3x mb-3"></i>
                    <h1>ISNM</h1>
                    <p>Iganga School of Nursing and Midwifery</p>
                    <p class="small mt-2">Student Management System</p>
                </div>
            </div>
            <div class="col-md-6">
                <div class="login-form">
                    <h3 class="mb-4 text-center">Login</h3>
                    
                    <!-- Flash Messages -->
                    <?php foreach ($flashMessages as $type => $message): ?>
                        <div class="alert alert-<?php echo $type; ?> alert-dismissible fade show" role="alert">
                            <?php echo $message; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endforeach; ?>

                    <form method="POST">
                        <div class="mb-3">
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="fas fa-user"></i>
                                </span>
                                <input type="text" name="username" class="form-control" 
                                       placeholder="Username" required>
                            </div>
                        </div>
                        <div class="mb-4">
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="fas fa-lock"></i>
                                </span>
                                <input type="password" name="password" class="form-control" 
                                       placeholder="Password" required>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-login">
                            <i class="fas fa-sign-in-alt me-2"></i> Login
                        </button>
                    </form>

                    <div class="system-info">
                        <h6>System Features</h6>
                        <ul class="feature-list">
                            <li><i class="fas fa-check"></i> Student Management</li>
                            <li><i class="fas fa-check"></i> Excel Import/Export</li>
                            <li><i class="fas fa-check"></i> Role-based Access</li>
                            <li><i class="fas fa-check"></i> Photo Upload</li>
                            <li><i class="fas fa-check"></i> Reports & Analytics</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
