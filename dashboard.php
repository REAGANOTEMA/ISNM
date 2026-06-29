<?php
session_start();
include_once 'includes/config.php';
include_once 'includes/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header("Location: organogram.php");
    exit();
}

// Get user information
$user = getUserInfo($_SESSION['user_id']);
$access_level = $_SESSION['access_level'] ?? 1;

// Redirect top administrators to student accounts management
if ($access_level >= 8) {
    header("Location: student_accounts_management.php");
    exit();
}

// For other users, show appropriate dashboard based on role
$role = $_SESSION['role'] ?? '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, shrink-to-fit=no">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="theme-color" content="#3E2723">
    <title>Dashboard | ISNM</title>
    <link rel="icon" type="image/x-icon" href="images/school-logo.png">
    <link rel="apple-touch-icon" href="images/school-logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
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
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            margin: 0;
            padding: 0;
            overflow-x: hidden;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        .navbar {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .navbar-brand {
            display: flex;
            align-items: center;
            font-weight: 600;
        }

        .navbar-brand img {
            height: 35px;
            width: auto;
            margin-right: 10px;
        }

        .dashboard-container {
            padding: 20px;
            max-width: 1400px;
            margin: 0 auto;
        }

        .welcome-card {
            padding: 30px;
            margin-bottom: 30px;
            border-left: 5px solid var(--accent-color);
        }

        .stat-card {
            padding: 25px;
            margin-bottom: 20px;
            border-left: 4px solid var(--primary-color);
        }

        .stat-card:hover {
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--primary-color);
        }

        .stat-label {
            color: #666;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .action-card {
            padding: 25px;
            margin-bottom: 20px;
            text-align: center;
        }


        .action-icon {
            font-size: 3rem;
            color: var(--primary-color);
            margin-bottom: 15px;
        }

        .btn-action {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            border: none;
            border-radius: 10px;
            padding: 12px 30px;
            font-weight: 600;
            transition: all 0.3s ease;
            min-height: 44px;
            touch-action: manipulation;
        }

        .btn-action:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            color: white;
        }

        /* Perfect Mobile Styles */
        @media (max-width: 768px) {
            .dashboard-container {
                padding: 15px;
            }

            .welcome-card {
                padding: 20px;
                margin-bottom: 20px;
            }

            .stat-card {
                padding: 20px;
                margin-bottom: 15px;
            }

            .stat-number {
                font-size: 2rem;
            }

            .action-card {
                padding: 20px;
                margin-bottom: 15px;
            }

            .action-icon {
                font-size: 2.5rem;
            }

            .navbar-brand img {
                height: 30px;
            }

            .btn-action {
                padding: 10px 20px;
                font-size: 0.9rem;
            }
        }

        @media (max-width: 480px) {
            .dashboard-container {
                padding: 10px;
            }

            .welcome-card {
                padding: 15px;
            }

            .stat-card {
                padding: 15px;
            }

            .stat-number {
                font-size: 1.8rem;
            }

            .action-card {
                padding: 15px;
            }

            .action-icon {
                font-size: 2rem;
            }

            .navbar-brand img {
                height: 25px;
            }

            .btn-action {
                padding: 8px 15px;
                font-size: 0.85rem;
                min-height: 40px;
            }
        }

        /* Landscape Mobile */
        @media (max-height: 600px) and (orientation: landscape) {
            .welcome-card {
                padding: 15px;
                margin-bottom: 15px;
            }

            .stat-card {
                padding: 15px;
            }

            .action-card {
                padding: 15px;
            }
        }
    </style>
</head>
<body>
    <!-- Perfect Mobile Responsive Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark sticky-top">
        <div class="container">
            <!-- Brand Logo -->
            <a class="navbar-brand d-flex align-items-center" href="dashboard.php">
                <img src="images/school-logo.png" alt="ISNM Logo" class="me-2" style="height: 35px; width: auto;">
                <span class="d-none d-md-inline">ISNM Dashboard</span>
            </a>
            
            <!-- Mobile Toggle Button -->
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#dashboardNavbar" aria-controls="dashboardNavbar" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <!-- Navigation Menu -->
            <div class="collapse navbar-collapse" id="dashboardNavbar">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="dashboard.php">
                            <i class="fas fa-tachometer-alt me-1"></i>Dashboard
                        </a>
                    </li>
                    <?php if ($access_level >= 8): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="student_accounts_management.php">
                            <i class="fas fa-users me-1"></i>Student Accounts
                        </a>
                    </li>
                    <?php endif; ?>
                    <li class="nav-item">
                        <a class="nav-link" href="student_profile.php">
                            <i class="fas fa-user-graduate me-1"></i>Student Profile
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="application.php">
                            <i class="fas fa-edit me-1"></i>Applications
                        </a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="student_accounts_management.php">
                            <i class="fas fa-users"></i> Student Accounts
                        </a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($_SESSION['first_name'] . ' ' . $_SESSION['last_name']); ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="includes/profile_settings.php">
                                <i class="fas fa-user"></i> Profile
                            </a></li>
                            <li><a class="dropdown-item" href="dashboards/system-admin.php">
                                <i class="fas fa-cog"></i> Settings
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php">
                                <i class="fas fa-sign-out-alt"></i> Logout
                            </a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Perfect Mobile Responsive Main Content -->
    <div class="dashboard-container">
        <?php if ($access_level >= 8): ?>
            <!-- Welcome Message for Top Administrators -->
            <div class="welcome-card">
                <div class="d-flex align-items-center mb-4">
                    <img src="images/school-logo.png" alt="ISNM Logo" class="me-3" style="height: 60px; width: auto; border-radius: 50%; border: 3px solid var(--accent-color);">
                    <div>
                        <h1 class="h3 mb-1">
                            <i class="fas fa-shield-alt text-primary me-2"></i> Welcome, <?php echo htmlspecialchars($user['first_name']); ?>
                        </h1>
                        <p class="text-muted mb-0">
                            <?php echo htmlspecialchars(ucfirst($role)); ?> Dashboard
                        </p>
                    </div>
                </div>
                <p class="text-muted mb-4">
                    You have full access to the Student Accounts Management System.
                </p>
                <div class="text-center">
                    <a href="student_accounts_management.php" class="btn-action">
                        <i class="fas fa-users me-2"></i> Manage Student Accounts
                    </a>
                </div>
            </div>

            <!-- Perfect Mobile Statistics -->
            <div class="row">
                <div class="col-lg-3 col-md-6 col-sm-6 mb-3">
                    <div class="stat-card">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="stat-number">
                                    <?php 
                                    $total_sql = "SELECT COUNT(*) as count FROM students";
                                    $total_result = executeQuery($total_sql);
                                    echo number_format($total_result[0]['count']);
                                    ?>
                                </div>
                                <div class="stat-label">Total Students</div>
                            </div>
                            <div class="text-primary">
                                <i class="fas fa-users fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 col-sm-6 mb-3">
                    <div class="stat-card">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="stat-number">
                                    <?php 
                                    $active_sql = "SELECT COUNT(*) as count FROM students WHERE status = 'active'";
                                    $active_result = executeQuery($active_sql);
                                    echo number_format($active_result[0]['count']);
                                    ?>
                                </div>
                                <div class="stat-label">Active Students</div>
                            </div>
                            <div class="text-success">
                                <i class="fas fa-user-check fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 col-sm-6 mb-3">
                    <div class="stat-card">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="stat-number">
                                    <?php 
                                    $programs_sql = "SELECT COUNT(DISTINCT program) as count FROM students";
                                    $programs_result = executeQuery($programs_sql);
                                    echo number_format($programs_result[0]['count']);
                                    ?>
                                </div>
                                <div class="stat-label">Programs</div>
                            </div>
                            <div class="text-info">
                                <i class="fas fa-graduation-cap fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 col-sm-6 mb-3">
                    <div class="stat-card">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="stat-number">
                                    <?php 
                                    $current_year = date('Y');
                                    $year_sql = "SELECT COUNT(*) as count FROM students WHERE intake_year = ?";
                                    $year_result = executeQuery($year_sql, [$current_year], 's');
                                    echo number_format($year_result[0]['count']);
                                    ?>
                                </div>
                                <div class="stat-label">Current Year</div>
                            </div>
                            <div class="text-warning">
                                <i class="fas fa-calendar fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Perfect Mobile Action Cards -->
            <div class="row">
                <div class="col-lg-4 col-md-6 col-sm-12 mb-3">
                    <div class="action-card">
                        <div class="action-icon text-primary">
                            <i class="fas fa-plus"></i>
                        </div>
                        <h5>Add New Student</h5>
                        <p class="text-muted">Register a new student in the system</p>
                        <a href="student_accounts_management.php?action=add" class="btn-action">
                            <i class="fas fa-plus me-2"></i> Add Student
                        </a>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6 col-sm-12 mb-3">
                    <div class="action-card">
                        <div class="action-icon text-success">
                            <i class="fas fa-upload"></i>
                        </div>
                        <h5>Import Students</h5>
                        <p class="text-muted">Bulk import students from CSV file</p>
                        <a href="bulk_photo_upload.php" class="btn-action">
                            <i class="fas fa-upload me-2"></i> Import Data
                        </a>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6 col-sm-12 mb-3">
                    <div class="action-card">
                        <div class="action-icon text-info">
                            <i class="fas fa-chart-bar"></i>
                        </div>
                        <h5>View Reports</h5>
                        <p class="text-muted">Generate and view student reports</p>
                        <a href="dashboards/director-general.php#reports" class="btn-action">
                            <i class="fas fa-chart-bar me-2"></i> Reports
                        </a>
                    </div>
                </div>
            </div>

        <?php else: ?>
            <!-- Mobile-Friendly Access Denied -->
            <div class="welcome-card">
                <div class="text-center">
                    <div class="action-icon text-danger mb-4">
                        <i class="fas fa-lock"></i>
                    </div>
                    <h2>Access Restricted</h2>
                    <p class="text-muted mb-4">
                        Your current access level does not permit access to the Student Accounts Management System.
                        This feature is available only to top administrators and directors.
                    </p>
                    <div class="alert alert-info">
                        <strong>Your Role:</strong> <?php echo htmlspecialchars($role); ?><br>
                        <strong>Access Level:</strong> <?php echo $access_level; ?><br>
                        <strong>Required Level:</strong> 8 or higher
                    </div>
                    <div class="mt-4">
                        <a href="logout.php" class="btn-action me-2">
                            <i class="fas fa-sign-out-alt me-2"></i> Logout
                        </a>
                        <?php if (hasPermission($role, 'dashboard')): ?>
                            <a href="dashboards/<?php echo strtolower(str_replace(' ', '-', $role)); ?>.php" class="btn-action">
                                <i class="fas fa-tachometer-alt me-2"></i> Your Dashboard
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Mobile-Optimized Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Mobile touch optimization
        document.addEventListener('DOMContentLoaded', function() {
            // Prevent double-tap zoom on mobile
            let lastTouchEnd = 0;
            document.addEventListener('touchend', function(event) {
                const now = Date.now();
                if (now - lastTouchEnd <= 300) {
                    event.preventDefault();
                }
                lastTouchEnd = now;
            }, false);

            // Smooth scroll for mobile
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function(e) {
                    e.preventDefault();
                    const target = document.querySelector(this.getAttribute('href'));
                    if (target) {
                        target.scrollIntoView({ behavior: 'smooth' });
                    }
                });
            });
        });

        // Auto-redirect to student accounts for top admins
        <?php if ($access_level >= 8): ?>
        setTimeout(function() {
            if (confirm('Redirect to Student Accounts Management?')) {
                window.location.href = 'student_accounts_management.php';
            }
        }, 3000);
        <?php endif; ?>
    </script>
</body>
</html>
