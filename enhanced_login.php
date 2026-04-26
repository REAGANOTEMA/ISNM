<?php
session_start();
include_once 'includes/config.php';
include_once 'includes/functions.php';

// Handle login attempts
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login_type = $_POST['login_type'] ?? '';
    
    if ($login_type === 'student') {
        handleStudentLogin();
    } elseif ($login_type === 'staff') {
        handleStaffLogin();
    }
}

// Handle student login with NSIN number, name, and contact number
function handleStudentLogin() {
    global $conn;
    
    $nsin_number = sanitizeInput($_POST['nsin_number']);
    $first_name = sanitizeInput($_POST['first_name']);
    $phone = sanitizeInput($_POST['phone']);
    
    // Validate input
    if (empty($nsin_number) || empty($first_name) || empty($phone)) {
        $_SESSION['error'] = "All fields are required for student login";
        header("Location: enhanced_login.php");
        exit();
    }
    
    // Check if student exists and is not locked
    $check_sql = "SELECT * FROM students WHERE nsin_number = ? AND first_name = ? AND phone = ? AND status = 'active'";
    $stmt = $conn->prepare($check_sql);
    $stmt->bind_param("sss", $nsin_number, $first_name, $phone);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $student = $result->fetch_assoc();
        
        // Check if account is locked
        if ($student['account_locked'] && $student['locked_until'] > date('Y-m-d H:i:s')) {
            $_SESSION['error'] = "Account is temporarily locked. Please try again later.";
            header("Location: enhanced_login.php");
            exit();
        }
        
        // Reset login attempts on successful login
        $reset_sql = "UPDATE students SET login_attempts = 0, account_locked = 0, last_login = NOW() WHERE student_id = ?";
        $reset_stmt = $conn->prepare($reset_sql);
        $reset_stmt->bind_param("s", $student['student_id']);
        $reset_stmt->execute();
        
        // Set session variables
        $_SESSION['user_id'] = $student['student_id'];
        $_SESSION['first_name'] = $student['first_name'];
        $_SESSION['last_name'] = $student['surname'];
        $_SESSION['email'] = $student['email'];
        $_SESSION['phone'] = $student['phone'];
        $_SESSION['role'] = 'Student';
        $_SESSION['nsin_number'] = $student['nsin_number'];
        $_SESSION['program'] = $student['program'];
        $_SESSION['level'] = $student['level'];
        
        // Log successful login
        logActivity($student['student_id'], 'Student', 'Student Login', "Student logged in: $nsin_number - $first_name $phone", 'students', $student['student_id']);
        
        // Redirect to student dashboard
        header("Location: student_profile.php");
        exit();
        
    } else {
        // Check if student exists with NSIN number but wrong credentials
        $check_nsin_sql = "SELECT * FROM students WHERE nsin_number = ?";
        $check_nsin_stmt = $conn->prepare($check_nsin_sql);
        $check_nsin_stmt->bind_param("s", $nsin_number);
        $check_nsin_stmt->execute();
        $nsin_result = $check_nsin_stmt->get_result();
        
        if ($nsin_result->num_rows === 1) {
            $student_data = $nsin_result->fetch_assoc();
            
            // Increment login attempts
            $attempts = $student_data['login_attempts'] + 1;
            
            // Lock account after 3 failed attempts
            if ($attempts >= 3) {
                $lock_until = date('Y-m-d H:i:s', strtotime('+30 minutes'));
                $lock_sql = "UPDATE students SET login_attempts = ?, account_locked = 1, locked_until = ? WHERE nsin_number = ?";
                $lock_stmt = $conn->prepare($lock_sql);
                $lock_stmt->bind_param("iss", $attempts, $lock_until, $nsin_number);
                $lock_stmt->execute();
                
                $_SESSION['error'] = "Account locked due to multiple failed login attempts. Please try again after 30 minutes.";
            } else {
                $update_sql = "UPDATE students SET login_attempts = ? WHERE nsin_number = ?";
                $update_stmt = $conn->prepare($update_sql);
                $update_stmt->bind_param("is", $attempts, $nsin_number);
                $update_stmt->execute();
                
                $_SESSION['error'] = "Invalid credentials. Attempts remaining: " . (3 - $attempts);
            }
        } else {
            $_SESSION['error'] = "Student not found. Please check your NSIN number, name, and contact number.";
        }
        
        header("Location: enhanced_login.php");
        exit();
    }
}

// Handle staff login with username and password
function handleStaffLogin() {
    global $conn;
    
    $username = sanitizeInput($_POST['username']);
    $password = sanitizeInput($_POST['password']);
    
    // Validate input
    if (empty($username) || empty($password)) {
        $_SESSION['error'] = "Username and password are required for staff login";
        header("Location: enhanced_login.php");
        exit();
    }
    
    // Check if user exists
    $check_sql = "SELECT * FROM users WHERE username = ? AND status = 'active'";
    $stmt = $conn->prepare($check_sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        // Check if account is locked
        if ($user['account_locked'] && $user['locked_until'] > date('Y-m-d H:i:s')) {
            $_SESSION['error'] = "Account is temporarily locked. Please try again later.";
            header("Location: enhanced_login.php");
            exit();
        }
        
        // Verify password
        if (password_verify($password, $user['password'])) {
            // Reset login attempts on successful login
            $reset_sql = "UPDATE users SET login_attempts = 0, account_locked = 0, last_login = NOW() WHERE user_id = ?";
            $reset_stmt = $conn->prepare($reset_sql);
            $reset_stmt->bind_param("s", $user['user_id']);
            $reset_stmt->execute();
            
            // Set session variables
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['first_name'] = $user['first_name'];
            $_SESSION['last_name'] = $user['last_name'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['phone'] = $user['phone'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['department'] = $user['department'];
            $_SESSION['username'] = $user['username'];
            
            // Log successful login
            logActivity($user['user_id'], $user['role'], 'Staff Login', "Staff logged in: $username", 'users', $user['user_id']);
            
            // Redirect based on role
            switch ($user['role']) {
                case 'Director General':
                    header("Location: dashboards/director-general.php");
                    break;
                case 'Chief Executive Officer':
                    header("Location: dashboards/director-general.php");
                    break;
                case 'School Principal':
                    header("Location: dashboards/principal.php");
                    break;
                case 'School Secretary':
                    header("Location: dashboards/secretary.php");
                    break;
                case 'Academic Registrar':
                    header("Location: dashboards/academic-registrar.php");
                    break;
                case 'School Bursar':
                    header("Location: dashboards/bursar.php");
                    break;
                case 'HR Manager':
                    header("Location: dashboards/hr-manager.php");
                    break;
                case 'Director Academics':
                    header("Location: dashboards/director-general.php");
                    break;
                case 'Director ICT':
                    header("Location: dashboards/director-general.php");
                    break;
                case 'Director Finance':
                    header("Location: dashboards/director-general.php");
                    break;
                default:
                    header("Location: dashboard.php");
                    break;
            }
            exit();
            
        } else {
            // Increment login attempts
            $attempts = $user['login_attempts'] + 1;
            
            // Lock account after 3 failed attempts
            if ($attempts >= 3) {
                $lock_until = date('Y-m-d H:i:s', strtotime('+30 minutes'));
                $lock_sql = "UPDATE users SET login_attempts = ?, account_locked = 1, locked_until = ? WHERE username = ?";
                $lock_stmt = $conn->prepare($lock_sql);
                $lock_stmt->bind_param("iss", $attempts, $lock_until, $username);
                $lock_stmt->execute();
                
                $_SESSION['error'] = "Account locked due to multiple failed login attempts. Please try again after 30 minutes.";
            } else {
                $update_sql = "UPDATE users SET login_attempts = ? WHERE username = ?";
                $update_stmt = $conn->prepare($update_sql);
                $update_stmt->bind_param("is", $attempts, $username);
                $update_stmt->execute();
                
                $_SESSION['error'] = "Invalid password. Attempts remaining: " . (3 - $attempts);
            }
        }
        
    } else {
        $_SESSION['error'] = "User not found. Please check your username.";
    }
    
    header("Location: enhanced_login.php");
    exit();
}

// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'Student') {
        header("Location: student_profile.php");
    } else {
        header("Location: dashboard.php");
    }
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - ISNM School Management System</title>
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
            max-width: 1000px;
            width: 100%;
            display: flex;
            min-height: 600px;
        }

        .login-sidebar {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 3rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            flex: 1;
        }

        .school-logo {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            margin-bottom: 2rem;
            border: 4px solid var(--accent-color);
        }

        .school-name {
            font-size: 1.8rem;
            font-weight: bold;
            margin-bottom: 1rem;
            color: var(--accent-color);
        }

        .school-motto {
            font-size: 1rem;
            opacity: 0.9;
            margin-bottom: 2rem;
        }

        .login-content {
            padding: 3rem;
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .login-title {
            font-size: 2rem;
            font-weight: bold;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }

        .login-subtitle {
            color: #666;
            font-size: 1rem;
        }

        .login-tabs {
            display: flex;
            margin-bottom: 2rem;
            border-bottom: 2px solid #e0e0e0;
        }

        .tab-btn {
            flex: 1;
            padding: 1rem;
            background: none;
            border: none;
            font-size: 1rem;
            font-weight: 600;
            color: #666;
            cursor: pointer;
            transition: all 0.3s ease;
            border-bottom: 3px solid transparent;
        }

        .tab-btn.active {
            color: var(--primary-color);
            border-bottom-color: var(--primary-color);
        }

        .tab-btn:hover {
            color: var(--primary-color);
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
            animation: fadeIn 0.5s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            font-weight: 600;
            color: #333;
            margin-bottom: 0.5rem;
        }

        .form-control {
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            padding: 0.75rem 1rem;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(26, 35, 126, 0.25);
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

        .login-info {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            padding: 1.5rem;
            margin-top: 2rem;
        }

        .login-info h5 {
            color: var(--accent-color);
            margin-bottom: 1rem;
        }

        .login-info ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .login-info li {
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
        }

        .login-info li i {
            margin-right: 0.5rem;
            color: var(--accent-color);
        }

        @media (max-width: 768px) {
            .login-container {
                flex-direction: column;
                max-width: 400px;
            }

            .login-sidebar {
                padding: 2rem;
            }

            .login-content {
                padding: 2rem;
            }

            .school-logo {
                width: 80px;
                height: 80px;
            }

            .school-name {
                font-size: 1.4rem;
            }

            .login-title {
                font-size: 1.6rem;
            }
        }

        .security-notice {
            background: #f8f9fa;
            border-left: 4px solid var(--info-color);
            padding: 1rem;
            margin-bottom: 1.5rem;
            border-radius: 5px;
            font-size: 0.9rem;
        }

        .security-notice i {
            color: var(--info-color);
            margin-right: 0.5rem;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-sidebar">
            <img src="images/school-logo.png" alt="ISNM Logo" class="school-logo">
            <h2 class="school-name">IGANGA SCHOOL OF NURSING AND MIDWIFERY</h2>
            <p class="school-motto">Excellence in Healthcare Education</p>
            
            <div class="login-info">
                <h5>Login Information</h5>
                <ul>
                    <li><i class="fas fa-user-graduate"></i> Students: NSIN + Name + Phone</li>
                    <li><i class="fas fa-user-tie"></i> Staff: Username + Password</li>
                    <li><i class="fas fa-shield-alt"></i> Secure Authentication</li>
                    <li><i class="fas fa-lock"></i> Account Protection</li>
                </ul>
            </div>
        </div>

        <div class="login-content">
            <div class="login-header">
                <h1 class="login-title">Welcome Back</h1>
                <p class="login-subtitle">Login to access your account</p>
            </div>

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

            <div class="login-tabs">
                <button class="tab-btn active" onclick="showTab('student')">
                    <i class="fas fa-user-graduate"></i> Student Login
                </button>
                <button class="tab-btn" onclick="showTab('staff')">
                    <i class="fas fa-user-tie"></i> Staff Login
                </button>
            </div>

            <!-- Student Login Tab -->
            <div id="student-tab" class="tab-content active">
                <div class="security-notice">
                    <i class="fas fa-info-circle"></i>
                    Students login with their NSIN number, first name, and contact number
                </div>
                
                <form method="POST" action="enhanced_login.php">
                    <input type="hidden" name="login_type" value="student">
                    
                    <div class="form-group">
                        <label class="form-label">NSIN Number *</label>
                        <div class="input-icon">
                            <i class="fas fa-id-card"></i>
                            <input type="text" class="form-control" name="nsin_number" placeholder="Enter your NSIN number" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">First Name *</label>
                        <div class="input-icon">
                            <i class="fas fa-user"></i>
                            <input type="text" class="form-control" name="first_name" placeholder="Enter your first name" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Contact Number *</label>
                        <div class="input-icon">
                            <i class="fas fa-phone"></i>
                            <input type="tel" class="form-control" name="phone" placeholder="Enter your contact number" required>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-login">
                        <i class="fas fa-sign-in-alt"></i> Login as Student
                    </button>
                </form>

                <div class="login-help">
                    <a href="forgot_password.php">Forgot your details?</a>
                </div>
            </div>

            <!-- Staff Login Tab -->
            <div id="staff-tab" class="tab-content">
                <div class="security-notice">
                    <i class="fas fa-info-circle"></i>
                    Staff login with their username and password
                </div>
                
                <form method="POST" action="enhanced_login.php">
                    <input type="hidden" name="login_type" value="staff">
                    
                    <div class="form-group">
                        <label class="form-label">Username *</label>
                        <div class="input-icon">
                            <i class="fas fa-user"></i>
                            <input type="text" class="form-control" name="username" placeholder="Enter your username" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Password *</label>
                        <div class="input-icon">
                            <i class="fas fa-lock"></i>
                            <input type="password" class="form-control" name="password" placeholder="Enter your password" required>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-login">
                        <i class="fas fa-sign-in-alt"></i> Login as Staff
                    </button>
                </form>

                <div class="login-help">
                    <a href="forgot_password.php">Forgot your password?</a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Tab switching functionality
        function showTab(tabName) {
            // Hide all tabs
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Remove active class from all buttons
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            
            // Show selected tab
            document.getElementById(tabName + '-tab').classList.add('active');
            
            // Add active class to clicked button
            event.target.classList.add('active');
        }

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
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function(e) {
                const inputs = form.querySelectorAll('input[required]');
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
        });

        // Phone number validation
        document.querySelector('input[name="phone"]')?.addEventListener('input', function(e) {
            const value = e.target.value.replace(/\D/g, '');
            if (value.length >= 9) {
                e.target.value = value;
            }
        });

        // NSIN number validation
        document.querySelector('input[name="nsin_number"]')?.addEventListener('input', function(e) {
            const value = e.target.value.replace(/[^A-Za-z0-9]/g, '').toUpperCase();
            e.target.value = value;
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
    </script>
</body>
</html>
