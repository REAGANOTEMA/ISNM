<?php
/**
 * Users Management View for ISNM Student Management System
 */

require_once __DIR__ . '/../controllers/UserController.php';
require_once __DIR__ . '/../controllers/AuthController.php';
require_once __DIR__ . '/../config/config.php';

// Check authentication
$authController = new AuthController();
$authController->checkAuth();

// Initialize controller
$userController = new UserController();

// Handle actions
$action = $_GET['action'] ?? 'index';

switch ($action) {
    case 'create':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $userController->create();
        }
        $pageTitle = 'Create User';
        break;
        
    case 'edit':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $userController->update();
        }
        $user = $userController->edit($_GET['id'] ?? 0);
        $pageTitle = 'Edit User';
        break;
        
    case 'delete':
        $userController->delete();
        break;
        
    default:
        $data = $userController->index();
        $users = $data['users'];
        $roles = $data['roles'];
        $filterRole = $data['filter_role'];
        $pageTitle = 'Users Management';
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
        .form-section {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        .role-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.875rem;
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
            .btn-action {
                padding: 3px 6px;
                font-size: 0.8rem;
            }
            .form-section {
                padding: 15px;
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
            .form-section {
                padding: 15px;
            }
            .role-badge {
                font-size: 0.75rem;
                padding: 0.2rem 0.5rem;
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
                            <a class="nav-link" href="../dashboards/admin.php">
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
                            <a class="nav-link active" href="users.php">
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
                    <!-- Filters -->
                    <div class="card mb-4">
                        <div class="card-body">
                            <form method="GET" class="row g-3">
                                <div class="col-md-4">
                                    <select name="role" class="form-select">
                                        <option value="">All Roles</option>
                                        <?php foreach ($roles as $role): ?>
                                            <option value="<?php echo $role['name']; ?>" 
                                                    <?php echo $filterRole === $role['name'] ? 'selected' : ''; ?>>
                                                <?php echo $role['name']; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <button type="submit" class="btn btn-primary w-100">
                                        <i class="fas fa-filter me-2"></i> Filter
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Users Table -->
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Users List</h5>
                            <a href="users.php?action=create" class="btn btn-success">
                                <i class="fas fa-plus me-2"></i> Add New User
                            </a>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Username</th>
                                            <th>Full Name</th>
                                            <th>Email</th>
                                            <th>Role</th>
                                            <th>Staff ID</th>
                                            <th>Created</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($users as $user): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($user['username']); ?></td>
                                                <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                                <td>
                                                    <span class="badge role-badge bg-<?php echo $this->getRoleColor($user['role']); ?>">
                                                        <?php echo htmlspecialchars($user['role_name']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo htmlspecialchars($user['staff_id'] ?? 'N/A'); ?></td>
                                                <td><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                                                <td>
                                                    <?php if (hasPermission('update')): ?>
                                                        <a href="users.php?action=edit&id=<?php echo $user['id']; ?>" 
                                                           class="btn btn-sm btn-warning btn-action">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                    <?php if (hasPermission('delete') && $user['id'] != $_SESSION['user_id']): ?>
                                                        <a href="users.php?action=delete&id=<?php echo $user['id']; ?>" 
                                                           class="btn btn-sm btn-danger btn-action"
                                                           onclick="return confirm('Are you sure you want to deactivate this user?')">
                                                            <i class="fas fa-trash"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                        <?php if (empty($users)): ?>
                                            <tr>
                                                <td colspan="7" class="text-center text-muted">No users found</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                <?php elseif ($action === 'create'): ?>
                    <!-- Create User Form -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Create New User</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-section">
                                            <h6>User Information</h6>
                                            <div class="mb-3">
                                                <label class="form-label">Username *</label>
                                                <input type="text" name="username" class="form-control" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Full Name *</label>
                                                <input type="text" name="full_name" class="form-control" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Email *</label>
                                                <input type="email" name="email" class="form-control" required>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-section">
                                            <h6>Account Settings</h6>
                                            <div class="mb-3">
                                                <label class="form-label">Password *</label>
                                                <input type="password" name="password" class="form-control" required minlength="6">
                                                <small class="text-muted">Password must be at least 6 characters long</small>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Role *</label>
                                                <select name="role" class="form-select" required>
                                                    <option value="">Select Role</option>
                                                    <?php foreach ($roles as $role): ?>
                                                        <option value="<?php echo $role['name']; ?>">
                                                            <?php echo $role['name']; ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Staff ID</label>
                                                <input type="text" name="staff_id" class="form-control">
                                                <small class="text-muted">Optional - Link to staff record</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="text-end">
                                    <a href="users.php" class="btn btn-secondary me-2">Cancel</a>
                                    <button type="submit" class="btn btn-success">
                                        <i class="fas fa-save me-2"></i> Create User
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                <?php elseif ($action === 'edit'): ?>
                    <!-- Edit User Form -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Edit User</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <input type="hidden" name="id" value="<?php echo $user['id']; ?>">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-section">
                                            <h6>User Information</h6>
                                            <div class="mb-3">
                                                <label class="form-label">Username</label>
                                                <input type="text" class="form-control" 
                                                       value="<?php echo htmlspecialchars($user['username']); ?>" readonly>
                                                <small class="text-muted">Username cannot be changed</small>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Full Name *</label>
                                                <input type="text" name="full_name" class="form-control" 
                                                       value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Email *</label>
                                                <input type="email" name="email" class="form-control" 
                                                       value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-section">
                                            <h6>Account Settings</h6>
                                            <div class="mb-3">
                                                <label class="form-label">New Password</label>
                                                <input type="password" name="password" class="form-control" minlength="6">
                                                <small class="text-muted">Leave empty to keep current password. Min 6 characters if changed.</small>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Role *</label>
                                                <select name="role" class="form-select" required>
                                                    <?php foreach ($roles as $role): ?>
                                                        <option value="<?php echo $role['name']; ?>" 
                                                                <?php echo $user['role'] === $role['name'] ? 'selected' : ''; ?>>
                                                            <?php echo $role['name']; ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Staff ID</label>
                                                <input type="text" name="staff_id" class="form-control" 
                                                       value="<?php echo htmlspecialchars($user['staff_id'] ?? ''); ?>">
                                                <small class="text-muted">Optional - Link to staff record</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="text-end">
                                    <a href="users.php" class="btn btn-secondary me-2">Cancel</a>
                                    <button type="submit" class="btn btn-warning">
                                        <i class="fas fa-save me-2"></i> Update User
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
    
    <?php
    // Helper function to get role color
    function getRoleColor($role) {
        $colors = [
            'admin' => 'danger',
            'principal' => 'primary',
            'director' => 'success',
            'bursar' => 'warning',
            'hr' => 'info',
            'secretary' => 'secondary',
            'lecturer' => 'dark'
        ];
        return $colors[$role] ?? 'secondary';
    }
    ?>
</body>
</html>
