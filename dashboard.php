<?php
session_start();
include_once 'includes/config.php';
include_once 'includes/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header("Location: login.php");
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - ISNM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #1a237e;
            --secondary-color: #3949ab;
            --accent-color: #ffd700;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
        }

        .navbar {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .navbar-brand {
            font-weight: bold;
            color: var(--accent-color) !important;
        }

        .main-container {
            padding: 2rem;
        }

        .welcome-card {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            border-left: 5px solid var(--primary-color);
        }

        .access-denied {
            background: white;
            border-radius: 15px;
            padding: 3rem;
            text-align: center;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            margin-top: 5rem;
        }

        .access-denied i {
            font-size: 4rem;
            color: var(--danger-color);
            margin-bottom: 1rem;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border: none;
            border-radius: 8px;
            padding: 0.75rem 2rem;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(26, 35, 126, 0.3);
        }

        .stats-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 3px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
            border-left: 4px solid var(--primary-color);
        }

        .stats-card:hover {
            transform: translateY(-5px);
        }

        .stats-number {
            font-size: 2.5rem;
            font-weight: bold;
            color: var(--primary-color);
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard.php">
                <i class="fas fa-graduation-cap"></i> ISNM Management System
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="dashboard.php">
                            <i class="fas fa-tachometer-alt"></i> Dashboard
                        </a>
                    </li>
                    <?php if ($access_level >= 8): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="student_accounts_management.php">
                            <i class="fas fa-users"></i> Student Accounts
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($_SESSION['first_name'] . ' ' . $_SESSION['last_name']); ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="profile.php">
                                <i class="fas fa-user"></i> Profile
                            </a></li>
                            <li><a class="dropdown-item" href="settings.php">
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

    <!-- Main Content -->
    <div class="container-fluid main-container">
        <?php if ($access_level >= 8): ?>
            <!-- Welcome Message for Top Administrators -->
            <div class="welcome-card">
                <h1 class="h3 mb-3">
                    <i class="fas fa-shield-alt text-primary"></i> Welcome, <?php echo htmlspecialchars($user['first_name']); ?>
                </h1>
                <p class="text-muted mb-4">
                    You have full access to the Student Accounts Management System as a <?php echo htmlspecialchars($role); ?>.
                </p>
                <div class="text-center">
                    <a href="student_accounts_management.php" class="btn btn-primary btn-lg">
                        <i class="fas fa-users"></i> Manage Student Accounts
                    </a>
                </div>
            </div>

            <!-- Quick Statistics -->
            <div class="row mb-4">
                <div class="col-md-3 mb-3">
                    <div class="stats-card">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="stats-number">
                                    <?php 
                                    $total_sql = "SELECT COUNT(*) as count FROM students";
                                    $total_result = executeQuery($total_sql);
                                    echo number_format($total_result[0]['count']);
                                    ?>
                                </div>
                                <div class="text-muted">Total Students</div>
                            </div>
                            <div class="text-primary">
                                <i class="fas fa-users fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="stats-card">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="stats-number">
                                    <?php 
                                    $active_sql = "SELECT COUNT(*) as count FROM students WHERE status = 'active'";
                                    $active_result = executeQuery($active_sql);
                                    echo number_format($active_result[0]['count']);
                                    ?>
                                </div>
                                <div class="text-muted">Active Students</div>
                            </div>
                            <div class="text-success">
                                <i class="fas fa-user-check fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="stats-card">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="stats-number">
                                    <?php 
                                    $programs_sql = "SELECT COUNT(DISTINCT program) as count FROM students";
                                    $programs_result = executeQuery($programs_sql);
                                    echo number_format($programs_result[0]['count']);
                                    ?>
                                </div>
                                <div class="text-muted">Programs</div>
                            </div>
                            <div class="text-info">
                                <i class="fas fa-graduation-cap fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="stats-card">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="stats-number">
                                    <?php 
                                    $current_year = date('Y');
                                    $year_sql = "SELECT COUNT(*) as count FROM students WHERE intake_year = ?";
                                    $year_result = executeQuery($year_sql, [$current_year], 's');
                                    echo number_format($year_result[0]['count']);
                                    ?>
                                </div>
                                <div class="text-muted">Current Year</div>
                            </div>
                            <div class="text-warning">
                                <i class="fas fa-calendar fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="row">
                <div class="col-md-4 mb-3">
                    <div class="stats-card text-center">
                        <i class="fas fa-plus fa-3x text-primary mb-3"></i>
                        <h5>Add New Student</h5>
                        <p class="text-muted">Register a new student in the system</p>
                        <a href="student_accounts_management.php?action=add" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Add Student
                        </a>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="stats-card text-center">
                        <i class="fas fa-upload fa-3x text-success mb-3"></i>
                        <h5>Import Students</h5>
                        <p class="text-muted">Bulk import students from CSV file</p>
                        <a href="import_student_data.php" class="btn btn-success">
                            <i class="fas fa-upload"></i> Import Data
                        </a>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="stats-card text-center">
                        <i class="fas fa-chart-bar fa-3x text-info mb-3"></i>
                        <h5>View Reports</h5>
                        <p class="text-muted">Generate and view student reports</p>
                        <a href="reports.php" class="btn btn-info">
                            <i class="fas fa-chart-bar"></i> Reports
                        </a>
                    </div>
                </div>
            </div>

        <?php else: ?>
            <!-- Access Denied for Lower Level Users -->
            <div class="access-denied">
                <i class="fas fa-lock"></i>
                <h2>Access Restricted</h2>
                <p class="text-muted mb-4">
                    Your current access level does not permit access to the Student Accounts Management System.
                    This feature is available only to top administrators and directors.
                </p>
                <p class="text-muted mb-4">
                    <strong>Your Role:</strong> <?php echo htmlspecialchars($role); ?><br>
                    <strong>Access Level:</strong> <?php echo $access_level; ?><br>
                    <strong>Required Level:</strong> 8 or higher
                </p>
                <div class="mt-4">
                    <a href="login.php" class="btn btn-primary me-2">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                    <?php if (hasPermission($role, 'dashboard')): ?>
                        <a href="dashboards/<?php echo strtolower(str_replace(' ', '-', $role)); ?>.php" class="btn btn-secondary">
                            <i class="fas fa-tachometer-alt"></i> Your Dashboard
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
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
