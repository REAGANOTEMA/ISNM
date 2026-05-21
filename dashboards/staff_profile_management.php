<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../auth-service.php';

// Database connection
$conn = getStaffConnection();

// Start secure session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Global authentication service
$auth_service = new AuthenticationService();

// Check authentication
if (!$auth_service->isAuthenticated()) {
    header('Location: staff-login.php');
    exit();
}

// Get user information
$staff_id = $_SESSION['user_id'] ?? 0;
$staff_email = $_SESSION['email'] ?? '';

// Handle profile picture upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['profile_picture'])) {
    $upload_dir = 'uploads/staff_profiles/';
    
    // Create directory if it doesn't exist
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    $file = $_FILES['profile_picture'];
    $file_name = $file['name'];
    $file_size = $file['size'];
    $file_tmp = $file['tmp_name'];
    $file_type = $file['type'];
    
    // Validate file
    $allowed_types = ['image/jpeg', 'image/png', 'image/jpg'];
    $max_size = 5 * 1024 * 1024; // 5MB
    
    if (!in_array($file_type, $allowed_types)) {
        $_SESSION['error'] = "Invalid file type. Only JPEG and PNG files are allowed.";
        header("Location: staff_profile_management.php");
        exit();
    }
    
    if ($file_size > $max_size) {
        $_SESSION['error'] = "File size too large. Maximum size is 5MB.";
        header("Location: staff_profile_management.php");
        exit();
    }
    
    // Generate unique filename
    $file_extension = pathinfo($file_name, PATHINFO_EXTENSION);
    $new_file_name = 'staff_' . $staff_id . '_' . time() . '.' . $file_extension;
    $upload_path = $upload_dir . $new_file_name;
    
    // Upload file
    if (move_uploaded_file($file_tmp, $upload_path)) {
        // Update database with new profile picture
        $sql = "UPDATE staff_profiles SET profile_picture = ? WHERE staff_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $upload_path, $staff_id);
        $stmt->execute();
        
        $_SESSION['success'] = "Profile picture uploaded successfully!";
    } else {
        $_SESSION['error'] = "Failed to upload profile picture.";
    }
    
    header("Location: staff_profile_management.php");
    exit();
}

// Handle access control management
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['manage_access'])) {
    $target_staff_id = $_POST['target_staff_id'];
    $module_name = $_POST['module_name'];
    $access_level = $_POST['access_level'];
    $access_reason = $_POST['access_reason'] ?? '';
    
    // Check if current user can manage access for this module
    $current_user_role = $_SESSION['role'] ?? '';
    $can_manage = false;
    
    if (stripos($current_user_role, 'Director') !== false || 
        stripos($current_user_role, 'General') !== false ||
        stripos($current_user_role, 'Principal') !== false ||
        stripos($current_user_role, 'CEO') !== false) {
        $can_manage = true;
    }
    
    if (!$can_manage) {
        $_SESSION['error'] = "You don't have permission to manage access control.";
        header("Location: staff_profile_management.php");
        exit();
    }
    
    // Insert or update access control
    $sql = "INSERT INTO staff_access_control (staff_id, module_name, access_level, granted_by, access_reason) 
             VALUES (?, ?, ?, ?, ?) 
             ON DUPLICATE KEY UPDATE 
             access_level = VALUES(access_level), 
             granted_by = VALUES(granted_by), 
             access_reason = VALUES(access_reason), 
             granted_at = NOW()";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("issss", $target_staff_id, $module_name, $access_level, $staff_id, $access_reason);
    $stmt->execute();
    
    $_SESSION['success'] = "Access control updated successfully!";
    header("Location: staff_profile_management.php");
    exit();
}

// Get current staff profile
$sql = "SELECT sp.*, s.full_name, s.email, s.position, s.department 
          FROM staff_profiles sp 
          JOIN staff s ON sp.staff_id = s.id 
          WHERE sp.staff_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $staff_id);
$stmt->execute();
$profile = $stmt->get_result()->fetch_assoc();

// Get access control list for current user
$access_sql = "SELECT sac.*, s.full_name as granted_by_name, s.position as granted_by_position 
                FROM staff_access_control sac 
                JOIN staff s ON sac.granted_by = s.id 
                WHERE sac.staff_id = ? 
                ORDER BY sac.granted_at DESC";
$access_stmt = $conn->prepare($access_sql);
$access_stmt->bind_param("i", $staff_id);
$access_stmt->execute();
$access_list = $access_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Profile Management - ISNM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .profile-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            padding: 30px;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .profile-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .profile-picture {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #007bff;
            margin-bottom: 20px;
        }
        
        .profile-info {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .upload-section {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
        }
        
        .access-control {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
        }
        
        .btn-primary {
            background: #007bff;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
        }
        
        .btn-primary:hover {
            background: #0056b3;
        }
        
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        .table th, .table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #dee2e6;
        }
        
        .table th {
            background: #f8f9fa;
            font-weight: bold;
        }
        
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        
        .alert-danger {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
    </style>
</head>
<body>
    <div class="profile-container">
        <div class="profile-header">
            <h2><i class="fas fa-user-circle me-2"></i>Staff Profile Management</h2>
            <p>Manage your profile picture and access control</p>
        </div>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger">
                <?php 
                    echo htmlspecialchars($_SESSION['error']);
                    unset($_SESSION['error']);
                    ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <?php 
                    echo htmlspecialchars($_SESSION['success']);
                    unset($_SESSION['success']);
                    ?>
            </div>
        <?php endif; ?>
        
        <div class="profile-info">
            <div>
                <h4>Profile Picture</h4>
                <?php if (!empty($profile['profile_picture'])): ?>
                    <img src="<?php echo htmlspecialchars($profile['profile_picture']); ?>" alt="Profile Picture" class="profile-picture">
                <?php else: ?>
                    <div style="width: 150px; height: 150px; background: #e9ecef; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-user fa-3x" style="color: #6c757d;"></i>
                    </div>
                <?php endif; ?>
            </div>
            
            <div>
                <h4>Personal Information</h4>
                <p><strong>Name:</strong> <?php echo htmlspecialchars($profile['full_name'] ?? ''); ?></p>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($profile['email'] ?? ''); ?></p>
                <p><strong>Position:</strong> <?php echo htmlspecialchars($profile['position'] ?? ''); ?></p>
                <p><strong>Department:</strong> <?php echo htmlspecialchars($profile['department'] ?? ''); ?></p>
            </div>
        </div>
        
        <div class="upload-section">
            <h4>Upload Profile Picture</h4>
            <form method="POST" enctype="multipart/form-data">
                <div class="mb-3">
                    <label for="profile_picture" class="form-label">Choose Profile Picture:</label>
                    <input type="file" class="form-control" id="profile_picture" name="profile_picture" accept="image/*" required>
                    <small class="form-text">Allowed: JPEG, PNG. Maximum size: 5MB</small>
                </div>
                <button type="submit" class="btn-primary">Upload Picture</button>
            </form>
        </div>
        
        <?php if (stripos($_SESSION['role'] ?? '', 'Director') !== false || 
                  stripos($_SESSION['role'] ?? '', 'General') !== false ||
                  stripos($_SESSION['role'] ?? '', 'Principal') !== false ||
                  stripos($_SESSION['role'] ?? '', 'CEO') !== false): ?>
            <div class="access-control">
                <h4>Access Control Management</h4>
                <form method="POST">
                    <div class="mb-3">
                        <label for="target_staff_id" class="form-label">Staff Member:</label>
                        <select class="form-control" id="target_staff_id" name="target_staff_id" required>
                            <option value="">Select Staff Member</option>
                            <?php
                            $staff_sql = "SELECT id, full_name, position FROM staff WHERE status = 'Active' AND id != ?";
                            $staff_stmt = $conn->prepare($staff_sql);
                            $staff_stmt->bind_param("i", $staff_id);
                            $staff_stmt->execute();
                            $staff_result = $staff_stmt->get_result();
                            
                            while ($staff = $staff_result->fetch_assoc()) {
                                echo '<option value="' . $staff['id'] . '">' . htmlspecialchars($staff['full_name']) . ' - ' . htmlspecialchars($staff['position']) . '</option>';
                            }
                            ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="module_name" class="form-label">Module:</label>
                        <input type="text" class="form-control" id="module_name" name="module_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="access_level" class="form-label">Access Level:</label>
                        <select class="form-control" id="access_level" name="access_level" required>
                            <option value="None">None</option>
                            <option value="Read">Read Only</option>
                            <option value="Write">Write</option>
                            <option value="Delete">Delete</option>
                            <option value="Admin">Admin</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="access_reason" class="form-label">Reason:</label>
                        <textarea class="form-control" id="access_reason" name="access_reason" rows="3"></textarea>
                    </div>
                    <button type="submit" name="manage_access" class="btn-primary">Grant/Update Access</button>
                </form>
            </div>
        <?php endif; ?>
        
        <?php if ($access_list->num_rows > 0): ?>
            <div class="access-control">
                <h4>Access Control History</h4>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Staff Member</th>
                            <th>Module</th>
                            <th>Access Level</th>
                            <th>Granted By</th>
                            <th>Granted At</th>
                            <th>Reason</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($access = $access_list->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($access['module_name']); ?></td>
                                <td><?php echo htmlspecialchars($access['access_level']); ?></td>
                                <td><?php echo htmlspecialchars($access['granted_by_name']); ?></td>
                                <td><?php echo htmlspecialchars($access['granted_at']); ?></td>
                                <td><?php echo htmlspecialchars($access['access_reason'] ?? ''); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>

