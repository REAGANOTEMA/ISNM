<?php
/**
 * Profile View for ISNM Student Management System
 */

require_once __DIR__ . '/../controllers/AuthController.php';
require_once __DIR__ . '/../controllers/UserController.php';
require_once __DIR__ . '/../config/config.php';

// Check authentication
$authController = new AuthController();
$authController->checkAuth();

// Get current user
$currentUser = $authController->getCurrentUser();

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $authController->changePassword();
}

// Get flash messages
$flashMessages = getFlashMessages();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - ISNM</title>
    <link rel="icon" type="image/png" href="../images/school-logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .sidebar {
            min-height: 100vh;
            background-color: #343a40;
        }
        .sidebar .nav-link {
            color: #adb5bd;
            padding: 10px 20px;
            border-radius: 5px;
            margin: 2px 0;
        }
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            background-color: #495057;
            color: white;
        }
        .main-content {
            padding: 20px;
        }
        .card {
            border: none;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .profile-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            border-radius: 15px;
            margin-bottom: 2rem;
        }
        .profile-avatar {
            width: 120px;
            height: 120px;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            color: #667eea;
            margin: 0 auto 1rem;
        }
        .info-item {
            padding: 1rem;
            border-bottom: 1px solid #e0e0e0;
        }
        .info-item:last-child {
            border-bottom: none;
        }
        .info-label {
            font-weight: 600;
            color: #666;
            margin-bottom: 0.25rem;
        }
        .info-value {
            color: #333;
        }
        
        /* Mobile Responsive Styles */
        @media (max-width: 768px) {
            .sidebar {
                min-height: auto;
                position: relative;
            }
            .main-content {
                padding: 15px;
            }
            .profile-avatar {
                width: 100px;
                height: 100px;
            }
        }
        
        @media (max-width: 576px) {
            .sidebar .nav-link {
                padding: 8px 15px;
                font-size: 0.9rem;
            }
            .main-content {
                padding: 10px;
            }
            .profile-avatar {
                width: 80px;
                height: 80px;
            }
            .info-item {
                padding: 0.75rem;
            }
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-2 sidebar p-0">
                <div class="p-3">
                    <h5 class="text-white mb-4">ISNM System</h5>
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="students_dashboards.php">
                                <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="students.php">
                                <i class="fas fa-user-graduate me-2"></i> Students
                            </a>
                        </li>
                        <?php if (hasPermission('create')): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="students.php?action=create">
                                <i class="fas fa-plus me-2"></i> Add Student
                            </a>
                        </li>
                        <?php endif; ?>
                        <?php if (hasPermission('import')): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="import.php">
                                <i class="fas fa-file-excel me-2"></i> Import Excel
                            </a>
                        </li>
                        <?php endif; ?>
                        <?php if (hasPermission('reports')): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="reports.php">
                                <i class="fas fa-chart-bar me-2"></i> Reports
                            </a>
                        </li>
                        <?php endif; ?>
                        <?php if (hasPermission('create')): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="users.php">
                                <i class="fas fa-users me-2"></i> Users
                            </a>
                        </li>
                        <?php endif; ?>
                        <li class="nav-item">
                            <a class="nav-link active" href="profile.php">
                                <i class="fas fa-user me-2"></i> Profile
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="logout.php">
                                <i class="fas fa-sign-out-alt me-2"></i> Logout
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-10 main-content">
                <!-- Header -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>Profile</h2>
                    <div class="text-muted">
                        <i class="fas fa-user me-2"></i>
                        <?php echo $_SESSION['full_name']; ?> 
                        <span class="badge bg-secondary ms-2"><?php echo $_SESSION['role_name']; ?></span>
                    </div>
                </div>

                <!-- Flash Messages -->
                <?php foreach ($flashMessages as $type => $message): ?>
                    <div class="alert alert-<?php echo $type; ?> alert-dismissible fade show" role="alert">
                        <?php echo $message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endforeach; ?>

                <!-- Profile Header -->
                <div class="profile-header text-center">
                    <div class="profile-avatar">
                        <i class="fas fa-user"></i>
                    </div>
                    <h3><?php echo htmlspecialchars($currentUser['full_name']); ?></h3>
                    <p class="mb-0"><?php echo htmlspecialchars($currentUser['role_name']); ?></p>
                </div>

                <div class="row">
                    <!-- Profile Information -->
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Profile Information</h5>
                            </div>
                            <div class="card-body">
                                <div class="info-item">
                                    <div class="info-label">Username</div>
                                    <div class="info-value"><?php echo htmlspecialchars($currentUser['username']); ?></div>
                                </div>
                                <div class="info-item">
                                    <div class="info-label">Full Name</div>
                                    <div class="info-value"><?php echo htmlspecialchars($currentUser['full_name']); ?></div>
                                </div>
                                <div class="info-item">
                                    <div class="info-label">Email</div>
                                    <div class="info-value"><?php echo htmlspecialchars($currentUser['email']); ?></div>
                                </div>
                                <div class="info-item">
                                    <div class="info-label">Role</div>
                                    <div class="info-value">
                                        <span class="badge bg-primary"><?php echo htmlspecialchars($currentUser['role_name']); ?></span>
                                    </div>
                                </div>
                                <?php if (!empty($currentUser['staff_id'])): ?>
                                <div class="info-item">
                                    <div class="info-label">Staff ID</div>
                                    <div class="info-value"><?php echo htmlspecialchars($currentUser['staff_id']); ?></div>
                                </div>
                                <?php endif; ?>
                                <div class="info-item">
                                    <div class="info-label">Member Since</div>
                                    <div class="info-value"><?php echo date('F j, Y', strtotime($currentUser['created_at'])); ?></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Change Password -->
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Change Password</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <div class="mb-3">
                                        <label class="form-label">Current Password *</label>
                                        <input type="password" name="current_password" class="form-control" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">New Password *</label>
                                        <input type="password" name="new_password" class="form-control" required minlength="6">
                                        <small class="text-muted">Password must be at least 6 characters long</small>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Confirm New Password *</label>
                                        <input type="password" name="confirm_password" class="form-control" required minlength="6">
                                    </div>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-lock me-2"></i> Change Password
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
