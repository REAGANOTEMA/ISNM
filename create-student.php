<?php
// Start secure session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include unified authentication system
require_once 'auth-handler.php';

// Check if user is authenticated and has permission to create students
if (!$auth_service->isAuthenticated()) {
    $_SESSION['error'] = 'Authentication required';
    header('Location: staff-login.php');
    exit();
}

if (!$auth_service->canCreateStudents($_SESSION['role'])) {
    $_SESSION['error'] = 'You do not have permission to create student accounts';
    header('Location: dashboards/student.php');
    exit();
}

// Handle student account creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_student') {
    // The auth-handler.php will process this automatically
    // No need to handle here - it's already handled in auth-handler.php
}

// Get current user info
$currentUser = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Student Account - ISNM</title>
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
            margin: 0;
            padding: 20px;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
        }

        .form-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
            margin-top: 20px;
        }

        .form-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 2rem;
            text-align: center;
        }

        .school-logo {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            margin-bottom: 1rem;
            border: 3px solid var(--accent-color);
        }

        .form-content {
            padding: 2rem;
        }

        .form-title {
            font-size: 1.8rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
            color: var(--primary-color);
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
            outline: none;
        }

        .btn-create {
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

        .btn-create:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(26, 35, 126, 0.3);
        }

        .btn-cancel {
            background: #6c757d;
            color: white;
            border: none;
            border-radius: 10px;
            padding: 1rem;
            font-size: 1.1rem;
            font-weight: 600;
            width: 100%;
            transition: all 0.3s ease;
            margin-top: 0.5rem;
        }

        .btn-cancel:hover {
            background: #5a6268;
            transform: translateY(-2px);
        }

        .alert {
            border-radius: 10px;
            border: none;
            margin-bottom: 1.5rem;
        }

        .back-link {
            text-align: center;
            margin-top: 2rem;
        }

        .back-link a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .back-link a:hover {
            text-decoration: underline;
        }

        .permission-notice {
            background: #e8f5e8;
            border-left: 4px solid var(--success-color);
            padding: 1rem;
            margin-bottom: 1.5rem;
            border-radius: 5px;
        }

        .permission-notice i {
            color: var(--success-color);
            margin-right: 0.5rem;
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

        .help-text {
            font-size: 0.85rem;
            color: #666;
            margin-top: 0.25rem;
        }

        @media (max-width: 768px) {
            .form-container {
                margin-top: 10px;
            }

            .form-header {
                padding: 1.5rem;
            }

            .form-content {
                padding: 1.5rem;
            }

            .school-logo {
                width: 60px;
                height: 60px;
            }

            .form-title {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="form-container">
            <div class="form-header">
                <img src="images/school-logo.png" alt="ISNM Logo" class="school-logo">
                <h2 class="form-title">Create Student Account</h2>
                <p>Register a new student for Iganga School of Nursing and Midwifery</p>
            </div>

            <div class="form-content">
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

                <div class="permission-notice">
                    <i class="fas fa-check-circle"></i>
                    <strong>Permission Granted:</strong> You are authorized to create student accounts as <?php echo htmlspecialchars($currentUser['role']); ?>
                </div>

                <form method="POST" action="create-student.php">
                    <input type="hidden" name="action" value="create_student">
                    
                    <div class="form-group">
                        <label class="form-label" for="index_number">Index Number *</label>
                        <div class="input-icon">
                            <i class="fas fa-id-badge"></i>
                            <input type="text" class="form-control" id="index_number" name="index_number" 
                                   placeholder="e.g., U001/CM/056/16" required>
                        </div>
                        <div class="help-text">Format: U001/CM/056/16 (CM=Midwifery, CN=Nursing, DMORDN=Diploma)</div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="full_name">Full Name *</label>
                        <div class="input-icon">
                            <i class="fas fa-user"></i>
                            <input type="text" class="form-control" id="full_name" name="full_name" 
                                   placeholder="Enter student's full name" required>
                        </div>
                        <div class="help-text">Enter the complete name as it appears on academic records</div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="phone">Phone Number *</label>
                        <div class="input-icon">
                            <i class="fas fa-phone"></i>
                            <input type="tel" class="form-control" id="phone" name="phone" 
                                   placeholder="e.g., 0771234567" required>
                        </div>
                        <div class="help-text">Uganda phone number format (10 digits starting with 7)</div>
                    </div>

                    <button type="submit" class="btn btn-create">
                        <i class="fas fa-user-plus"></i> Create Student Account
                    </button>

                    <a href="dashboards/student.php" class="btn btn-cancel">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                </form>

                <div class="back-link">
                    <a href="dashboards/student.php">
                        <i class="fas fa-arrow-left"></i> Back to Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const indexNumber = document.getElementById('index_number').value;
            const fullName = document.getElementById('full_name').value;
            const phone = document.getElementById('phone').value;
            
            // Validate index number format
            if (!/^U\d{3}\/(CM|CN|DMORDN)\/\d{3}\/\d{2}$/.test(indexNumber)) {
                e.preventDefault();
                alert('Invalid index number format. Use format: U001/CM/056/16');
                return;
            }
            
            // Validate phone number
            const cleanPhone = phone.replace(/[^0-9]/g, '');
            if (cleanPhone.length !== 10 || !/^7\d{9}$/.test(cleanPhone)) {
                e.preventDefault();
                alert('Invalid phone number format. Use 10-digit Uganda number starting with 7');
                return;
            }
            
            // Validate full name
            if (fullName.trim().length < 3) {
                e.preventDefault();
                alert('Please enter a valid full name');
                return;
            }
            
            // Confirm submission
            if (!confirm('Are you sure you want to create this student account?')) {
                e.preventDefault();
            }
        });

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
    </script>
</body>
</html>
