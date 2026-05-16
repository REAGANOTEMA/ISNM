<?php
/**
 * Students Management View for ISNM Student Management System
 */

require_once __DIR__ . '/../controllers/StudentController.php';
require_once __DIR__ . '/../controllers/AuthController.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/student_data_loader.php';

// Check authentication
$authController = new AuthController();
$authController->checkAuth();

// Initialize controller and data loader
$studentController = new StudentController();
$dataLoader = new StudentDataLoader();

// Handle actions
$action = $_GET['action'] ?? 'index';

switch ($action) {
    case 'create':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $studentController->create();
        }
        $pageTitle = 'Create Student';
        break;
        
    case 'edit':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $studentController->update();
        }
        $student = $studentController->edit($_GET['id'] ?? 0);
        $pageTitle = 'Edit Student';
        break;
        
    case 'delete':
        $studentController->delete();
        break;
        
    default:
        // Load students from Excel data
        $searchTerm = $_GET['search'] ?? '';
        $filters = [
            'program' => $_GET['program'] ?? '',
            'level' => $_GET['level'] ?? '',
            'set' => $_GET['set'] ?? '',
            'gender' => $_GET['gender'] ?? '',
            'year' => $_GET['year'] ?? ''
        ];
        
        $students = $dataLoader->searchStudents($searchTerm, $filters);
        $filterOptions = $dataLoader->getFilterOptions();
        $statistics = $dataLoader->getStatistics();
        $pageTitle = 'Students Management';
        break;
}

// Get flash messages
$flashMessages = getFlashMessages();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - ISNM</title>
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
        .btn-action {
            padding: 5px 10px;
            margin: 0 2px;
        }
        .student-photo {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 50%;
        }
        .stats-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .form-section {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
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
            .student-photo {
                width: 40px;
                height: 40px;
            }
            .btn-action {
                padding: 3px 6px;
                font-size: 0.8rem;
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
            .student-photo {
                width: 30px;
                height: 30px;
            }
            .form-section {
                padding: 15px;
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
                            <a class="nav-link active" href="students.php">
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
                    <h2><?php echo $pageTitle; ?></h2>
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

                <?php if ($action === 'index'): ?>
                    <!-- Statistics Cards -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card stats-card">
                                <div class="card-body">
                                    <h5 class="card-title">Total Students</h5>
                                    <h3><?php echo $statistics['total_students'] ?? 0; ?></h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card stats-card">
                                <div class="card-body">
                                    <h5 class="card-title">Programs</h5>
                                    <h3><?php echo $statistics['total_programs'] ?? 0; ?></h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card stats-card">
                                <div class="card-body">
                                    <h5 class="card-title">Sets</h5>
                                    <h3><?php echo $statistics['total_sets'] ?? 0; ?></h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card stats-card">
                                <div class="card-body">
                                    <h5 class="card-title">Intake Years</h5>
                                    <h3><?php echo $statistics['total_years'] ?? 0; ?></h3>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Filters -->
                    <div class="card mb-4">
                        <div class="card-body">
                            <form method="GET" class="row g-3">
                                <div class="col-md-3">
                                    <input type="text" name="search" class="form-control" 
                                           placeholder="Search by name, index number..." 
                                           value="<?php echo htmlspecialchars($searchTerm); ?>">
                                </div>
                                <div class="col-md-2">
                                    <select name="program" class="form-select">
                                        <option value="">All Programs</option>
                                        <?php foreach ($filterOptions['programs'] as $program): ?>
                                            <option value="<?php echo $program; ?>" 
                                                    <?php echo $filters['program'] === $program ? 'selected' : ''; ?>>
                                                <?php echo $program; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <select name="level" class="form-select">
                                        <option value="">All Levels</option>
                                        <?php foreach ($filterOptions['levels'] as $level): ?>
                                            <option value="<?php echo $level; ?>" 
                                                    <?php echo $filters['level'] === $level ? 'selected' : ''; ?>>
                                                <?php echo $level; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <select name="set" class="form-select">
                                        <option value="">All Sets</option>
                                        <?php foreach ($filterOptions['sets'] as $set): ?>
                                            <option value="<?php echo $set; ?>" 
                                                    <?php echo $filters['set'] === $set ? 'selected' : ''; ?>>
                                                <?php echo $set; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <select name="gender" class="form-select">
                                        <option value="">All Genders</option>
                                        <?php foreach ($filterOptions['genders'] as $gender): ?>
                                            <option value="<?php echo $gender; ?>" 
                                                    <?php echo $filters['gender'] === $gender ? 'selected' : ''; ?>>
                                                <?php echo $gender; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-1">
                                    <button type="submit" class="btn btn-primary w-100">
                                        <i class="fas fa-search"></i>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Students Table -->
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Students List</h5>
                            <?php if (hasPermission('create')): ?>
                                <a href="students.php?action=create" class="btn btn-success">
                                    <i class="fas fa-plus me-2"></i> Add New Student
                                </a>
                            <?php endif; ?>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>Index Number</th>
                                            <th>Program</th>
                                            <th>Level</th>
                                            <th>Set</th>
                                            <th>Gender</th>
                                            <th>Phone</th>
                                            <th>Source</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($students as $student): ?>
                                            <tr>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($student['surname'] . ', ' . $student['first_name'] . ' ' . $student['other_name']); ?></strong>
                                                </td>
                                                <td><code><?php echo htmlspecialchars($student['index_number']); ?></code></td>
                                                <td><?php echo htmlspecialchars($student['program']); ?></td>
                                                <td><?php echo htmlspecialchars($student['level']); ?></td>
                                                <td><?php echo htmlspecialchars($student['set']); ?></td>
                                                <td>
                                                    <span class="badge bg-<?php echo strtolower($student['gender']) === 'male' ? 'primary' : 'danger'; ?>">
                                                        <?php echo htmlspecialchars($student['gender']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo htmlspecialchars($student['phone']); ?></td>
                                                <td><small class="text-muted"><?php echo htmlspecialchars($student['source_file']); ?></small></td>
                                            </tr>
                                        <?php endforeach; ?>
                                        <?php if (empty($students)): ?>
                                            <tr>
                                                <td colspan="8" class="text-center text-muted">No students found</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Data Source Info -->
                            <div class="alert alert-info mt-3">
                                <i class="fas fa-info-circle me-2"></i>
                                <strong>Data Source:</strong> Loaded from Excel files in students_data folder
                                (<?php echo count($students); ?> students displayed)
                            </div>
                        </div>
                    </div>

                <?php elseif ($action === 'create'): ?>
                    <!-- Create Student Form -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Create New Student</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" enctype="multipart/form-data">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-section">
                                            <h6>Personal Information</h6>
                                            <div class="mb-3">
                                                <label class="form-label">Full Name *</label>
                                                <input type="text" name="full_name" class="form-control" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Gender *</label>
                                                <select name="gender" class="form-select" required>
                                                    <option value="">Select Gender</option>
                                                    <option value="Male">Male</option>
                                                    <option value="Female">Female</option>
                                                </select>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Mobile Number *</label>
                                                <input type="tel" name="mobile_number" class="form-control" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Passport Photo</label>
                                                <input type="file" name="passport_photo" class="form-control" accept="image/*">
                                                <small class="text-muted">JPG, PNG only. Max 2MB.</small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-section">
                                            <h6>Academic Information</h6>
                                            <div class="mb-3">
                                                <label class="form-label">Registration Number *</label>
                                                <input type="text" name="registration_number" class="form-control" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">National Student ID Number *</label>
                                                <input type="text" name="national_student_id_number" class="form-control" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Index Number *</label>
                                                <input type="text" name="index_number" class="form-control" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Course *</label>
                                                <input type="text" name="course" class="form-control" required>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label class="form-label">Year *</label>
                                                        <select name="year" class="form-select" required>
                                                            <option value="">Select Year</option>
                                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                                <option value="<?php echo $i; ?>">Year <?php echo $i; ?></option>
                                                            <?php endfor; ?>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label class="form-label">Set/Class *</label>
                                                        <input type="text" name="set_name" class="form-control" required>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="text-end">
                                    <a href="students.php" class="btn btn-secondary me-2">Cancel</a>
                                    <button type="submit" class="btn btn-success">
                                        <i class="fas fa-save me-2"></i> Create Student
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                <?php elseif ($action === 'edit'): ?>
                    <!-- Edit Student Form -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Edit Student</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" enctype="multipart/form-data">
                                <input type="hidden" name="id" value="<?php echo $student['id']; ?>">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-section">
                                            <h6>Personal Information</h6>
                                            <div class="mb-3">
                                                <label class="form-label">Full Name *</label>
                                                <input type="text" name="full_name" class="form-control" 
                                                       value="<?php echo htmlspecialchars($student['full_name']); ?>" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Gender *</label>
                                                <select name="gender" class="form-select" required>
                                                    <option value="">Select Gender</option>
                                                    <option value="Male" <?php echo $student['gender'] === 'Male' ? 'selected' : ''; ?>>Male</option>
                                                    <option value="Female" <?php echo $student['gender'] === 'Female' ? 'selected' : ''; ?>>Female</option>
                                                </select>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Mobile Number *</label>
                                                <input type="tel" name="mobile_number" class="form-control" 
                                                       value="<?php echo htmlspecialchars($student['mobile_number']); ?>" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Passport Photo</label>
                                                <input type="file" name="passport_photo" class="form-control" accept="image/*">
                                                <small class="text-muted">JPG, PNG only. Max 2MB. Leave empty to keep current photo.</small>
                                                <?php if (!empty($student['passport_photo'])): ?>
                                                    <div class="mt-2">
                                                        <img src="uploads/students/<?php echo $student['passport_photo']; ?>" 
                                                             alt="Current Photo" class="student-photo">
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-section">
                                            <h6>Academic Information</h6>
                                            <div class="mb-3">
                                                <label class="form-label">Registration Number</label>
                                                <input type="text" class="form-control" 
                                                       value="<?php echo htmlspecialchars($student['registration_number']); ?>" readonly>
                                                <small class="text-muted">Registration number cannot be changed</small>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">National Student ID Number *</label>
                                                <input type="text" name="national_student_id_number" class="form-control" 
                                                       value="<?php echo htmlspecialchars($student['national_student_id_number']); ?>" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Index Number *</label>
                                                <input type="text" name="index_number" class="form-control" 
                                                       value="<?php echo htmlspecialchars($student['index_number']); ?>" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Course *</label>
                                                <input type="text" name="course" class="form-control" 
                                                       value="<?php echo htmlspecialchars($student['course']); ?>" required>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label class="form-label">Year *</label>
                                                        <select name="year" class="form-select" required>
                                                            <option value="">Select Year</option>
                                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                                <option value="<?php echo $i; ?>" 
                                                                        <?php echo $student['year'] == $i ? 'selected' : ''; ?>>
                                                                    Year <?php echo $i; ?>
                                                                </option>
                                                            <?php endfor; ?>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label class="form-label">Set/Class *</label>
                                                        <input type="text" name="set_name" class="form-control" 
                                                               value="<?php echo htmlspecialchars($student['set_name']); ?>" required>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="text-end">
                                    <a href="students.php" class="btn btn-secondary me-2">Cancel</a>
                                    <button type="submit" class="btn btn-warning">
                                        <i class="fas fa-save me-2"></i> Update Student
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
