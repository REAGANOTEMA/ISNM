<?php
/**
 * Student Dashboard View for ISNM Student Management System
 */

require_once __DIR__ . '/../controllers/AuthController.php';
require_once __DIR__ . '/../models/Student.php';
require_once __DIR__ . '/../config/config.php';

// Check authentication
$authController = new AuthController();
$authController->checkAuth();

// Get current user
$currentUser = $authController->getCurrentUser();

// Get student statistics
$student = new Student();
$statsResult = $student->getStatistics();
$statistics = $statsResult['success'] ? $statsResult['statistics'] : [];

// Get recent students
$recentResult = $student->getAll(1, '', '', '', '');
$recentStudents = $recentResult['success'] ? array_slice($recentResult['students'], 0, 5) : [];

// Get flash messages
$flashMessages = getFlashMessages();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - ISNM</title>
    <link rel="icon" type="image/png" href="images/school-logo.png">
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
        .stats-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
        }
        .stats-card .card-body {
            padding: 1.5rem;
        }
        .stats-card h3 {
            font-size: 2.5rem;
            font-weight: bold;
        }
        .welcome-card {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
        }
        .quick-action-card {
            transition: transform 0.2s;
            cursor: pointer;
        }
        .quick-action-card:hover {
            transform: translateY(-5px);
        }
        .quick-action-card .card-body {
            text-align: center;
            padding: 2rem;
        }
        .quick-action-card i {
            font-size: 3rem;
            margin-bottom: 1rem;
        }
        .student-photo {
            width: 40px;
            height: 40px;
            object-fit: cover;
            border-radius: 50%;
        }
        .role-badge {
            background-color: rgba(255,255,255,0.2);
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
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
            .stats-card h3 {
                font-size: 1.8rem;
            }
            .quick-action-card i {
                font-size: 2rem;
            }
            .quick-action-card .card-body {
                padding: 1rem;
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
            .stats-card h3 {
                font-size: 1.5rem;
            }
            .quick-action-card i {
                font-size: 1.5rem;
            }
            .student-photo {
                width: 30px;
                height: 30px;
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
                            <a class="nav-link active" href="students_dashboards.php">
                                <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="students.php">
                                <i class="fas fa-user-graduate me-2"></i> Students
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="profile.php">
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
                    <h2>Student Dashboard</h2>
                    <div class="text-white">
                        <i class="fas fa-user me-2"></i>
                        <?php echo $currentUser['full_name']; ?> 
                        <span class="role-badge ms-2"><?php echo $currentUser['role_name']; ?></span>
                    </div>
                </div>

                <!-- Flash Messages -->
                <?php foreach ($flashMessages as $type => $message): ?>
                    <div class="alert alert-<?php echo $type; ?> alert-dismissible fade show" role="alert">
                        <?php echo $message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endforeach; ?>

                <!-- Welcome Card -->
                <div class="card welcome-card mb-4">
                    <div class="card-body">
                        <h4 class="card-title">Welcome to ISNM Student Management System</h4>
                        <p class="card-text mb-0">
                            Hello, <?php echo $currentUser['full_name']; ?>! You are logged in as 
                            <strong><?php echo $currentUser['role_name']; ?></strong>.
                            This is your student dashboard where you can view your information and manage your academic records.
                        </p>
                    </div>
                </div>

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card stats-card mb-3">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-title mb-1">Total Students</h6>
                                        <h3><?php echo $statistics['total_students'] ?? 0; ?></h3>
                                    </div>
                                    <div class="text-white-50">
                                        <i class="fas fa-users fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stats-card mb-3">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-title mb-1">Active Students</h6>
                                        <h3><?php echo $statistics['active_students'] ?? 0; ?></h3>
                                    </div>
                                    <div class="text-white-50">
                                        <i class="fas fa-user-check fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stats-card mb-3">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-title mb-1">Courses</h6>
                                        <h3><?php echo $statistics['total_courses'] ?? 0; ?></h3>
                                    </div>
                                    <div class="text-white-50">
                                        <i class="fas fa-graduation-cap fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stats-card mb-3">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-title mb-1">Sets</h6>
                                        <h3><?php echo $statistics['total_sets'] ?? 0; ?></h3>
                                    </div>
                                    <div class="text-white-50">
                                        <i class="fas fa-layer-group fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="row mb-4">
                    <div class="col-12">
                        <h4 class="mb-3">Quick Actions</h4>
                    </div>
                    <div class="col-md-3">
                        <div class="card quick-action-card border-info" onclick="window.location.href='students.php'">
                            <div class="card-body">
                                <i class="fas fa-list text-info"></i>
                                <h6>View Students</h6>
                                <p class="text-muted small mb-0">Browse all students</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card quick-action-card border-warning" onclick="window.location.href='profile.php'">
                            <div class="card-body">
                                <i class="fas fa-user text-warning"></i>
                                <h6>My Profile</h6>
                                <p class="text-muted small mb-0">View and edit profile</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card quick-action-card border-success" onclick="window.location.href='logout.php'">
                            <div class="card-body">
                                <i class="fas fa-sign-out-alt text-success"></i>
                                <h6>Logout</h6>
                                <p class="text-muted small mb-0">Sign out safely</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Students -->
                <?php if (!empty($recentStudents)): ?>
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Recent Students</h5>
                        <a href="students.php" class="btn btn-sm btn-primary">View All</a>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Photo</th>
                                        <th>Name</th>
                                        <th>Reg Number</th>
                                        <th>Course</th>
                                        <th>Year</th>
                                        <th>Set</th>
                                        <th>Gender</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentStudents as $student): ?>
                                        <tr>
                                            <td>
                                                <?php if (!empty($student['passport_photo'])): ?>
                                                    <img src="uploads/students/<?php echo $student['passport_photo']; ?>" 
                                                         alt="Photo" class="student-photo">
                                                <?php else: ?>
                                                    <div class="student-photo bg-secondary d-flex align-items-center justify-content-center">
                                                        <i class="fas fa-user text-white"></i>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($student['full_name']); ?></td>
                                            <td><?php echo htmlspecialchars($student['registration_number']); ?></td>
                                            <td><?php echo htmlspecialchars($student['course']); ?></td>
                                            <td>Year <?php echo $student['year']; ?></td>
                                            <td><?php echo htmlspecialchars($student['set_name']); ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo $student['gender'] === 'Male' ? 'primary' : 'pink'; ?>">
                                                    <?php echo $student['gender']; ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
