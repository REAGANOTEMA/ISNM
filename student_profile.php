<?php
/**
 * Comprehensive Student Profile System
 * Updated to work with current ISNM database structure and role-based access control
 */

require_once 'config/database.php';
require_once 'auth-service.php';

// Start session and check authentication
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: student-login.php");
    exit();
}

// Get current user information
$current_user_id = $_SESSION['user_id'];
$current_role = $_SESSION['role'];
$current_type = $_SESSION['type'] ?? '';

// Initialize database connection
$conn = getConnection();

// Function to check if user has permission to view student profile
function canViewStudentProfile($viewer_role, $viewer_type, $target_student_id = null) {
    // All students can view their own profile
    if ($viewer_type === 'student' && $target_student_id === $_SESSION['user_id']) {
        return true;
    }
    
    // Staff roles that can view any student profile
    $allowed_roles = ['admin', 'principal', 'deputy_principal', 'academic', 'registrar', 
                      'accountant', 'hr', 'director', 'secretary', 'librarian', 'counselor', 'tutor'];
    
    return in_array($viewer_role, $allowed_roles) && $viewer_type === 'staff';
}

// Function to check if user can edit student profile
function canEditStudentProfile($viewer_role, $viewer_type, $target_student_id = null) {
    // Only admin, principal, academic registrar, and academic staff can edit
    $editor_roles = ['admin', 'principal', 'deputy_principal', 'academic', 'registrar'];
    
    return in_array($viewer_role, $editor_roles) && $viewer_type === 'staff';
}

// Get student ID from URL or use current user
$student_id = $_GET['id'] ?? $current_user_id;

// Verify permissions
if (!canViewStudentProfile($current_role, $current_type, $student_id)) {
    $_SESSION['error'] = "You do not have permission to view this student profile.";
    header("Location: " . ($current_type === 'student' ? 'student-dashboard.php' : 'dashboard.php'));
    exit();
}

// Get student information
$student_sql = "SELECT s.*, u.full_name, u.email, u.phone, u.last_login 
                FROM students s 
                JOIN users u ON s.student_id = u.user_id 
                WHERE s.student_id = ?";
$stmt = $conn->prepare($student_sql);
$stmt->bind_param("s", $student_id);
$stmt->execute();
$student_result = $stmt->get_result();

if ($student_result->num_rows === 0) {
    $_SESSION['error'] = "Student not found.";
    header("Location: " . ($current_type === 'student' ? 'student-dashboard.php' : 'dashboard.php'));
    exit();
}

$student = $student_result->fetch_assoc();

// Check edit permissions
$can_edit = canEditStudentProfile($current_role, $current_type, $student_id);

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update_profile':
                if (!$can_edit) {
                    $_SESSION['error'] = "You do not have permission to update profile information.";
                    header("Location: student_profile.php?id=$student_id");
                    exit();
                }
                
                // Update student information
                $update_sql = "UPDATE students SET 
                              first_name = ?, surname = ?, other_name = ?, 
                              date_of_birth = ?, gender = ?, nationality = ?, 
                              address = ?, phone = ?, email = ?, 
                              program = ?, level = ?, intake_year = ?, 
                              intake_period = ?, guardian_name = ?, 
                              guardian_phone = ?, guardian_email = ?, 
                              medical_conditions = ?, emergency_contact_name = ?, 
                              emergency_contact_phone = ? 
                              WHERE student_id = ?";
                
                $stmt = $conn->prepare($update_sql);
                $stmt->bind_param("sssssssssssssssssss", 
                    $_POST['first_name'], $_POST['surname'], $_POST['other_name'],
                    $_POST['date_of_birth'], $_POST['gender'], $_POST['nationality'],
                    $_POST['address'], $_POST['phone'], $_POST['email'],
                    $_POST['program'], $_POST['level'], $_POST['intake_year'],
                    $_POST['intake_period'], $_POST['guardian_name'],
                    $_POST['guardian_phone'], $_POST['guardian_email'],
                    $_POST['medical_conditions'], $_POST['emergency_contact_name'],
                    $_POST['emergency_contact_phone'], $student_id);
                
                if ($stmt->execute()) {
                    $_SESSION['success'] = "Profile updated successfully!";
                } else {
                    $_SESSION['error'] = "Error updating profile: " . $conn->error;
                }
                
                header("Location: student_profile.php?id=$student_id");
                exit();
                
            case 'update_status':
                if (!$can_edit) {
                    $_SESSION['error'] = "You do not have permission to update student status.";
                    header("Location: student_profile.php?id=$student_id");
                    exit();
                }
                
                $new_status = $_POST['status'];
                $update_status_sql = "UPDATE students SET status = ? WHERE student_id = ?";
                $stmt = $conn->prepare($update_status_sql);
                $stmt->bind_param("ss", $new_status, $student_id);
                
                if ($stmt->execute()) {
                    $_SESSION['success'] = "Student status updated successfully!";
                } else {
                    $_SESSION['error'] = "Error updating status: " . $conn->error;
                }
                
                header("Location: student_profile.php?id=$student_id");
                exit();
                
            case 'upload_photo':
                // Handle photo upload
                if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === 0) {
                    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
                    $max_size = 5 * 1024 * 1024; // 5MB
                    
                    if (!in_array($_FILES['profile_image']['type'], $allowed_types)) {
                        $_SESSION['error'] = "Invalid file type. Please upload JPG, PNG, or GIF.";
                    } elseif ($_FILES['profile_image']['size'] > $max_size) {
                        $_SESSION['error'] = "File too large. Maximum size is 5MB.";
                    } else {
                        $upload_dir = 'studentUploads/profile_images/';
                        if (!is_dir($upload_dir)) {
                            mkdir($upload_dir, 0755, true);
                        }
                        
                        $filename = $student_id . '_' . time() . '.' . pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION);
                        $filepath = $upload_dir . $filename;
                        
                        if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $filepath)) {
                            $update_photo_sql = "UPDATE students SET profile_image = ? WHERE student_id = ?";
                            $stmt = $conn->prepare($update_photo_sql);
                            $stmt->bind_param("ss", $filename, $student_id);
                            
                            if ($stmt->execute()) {
                                $_SESSION['success'] = "Profile image updated successfully!";
                            } else {
                                $_SESSION['error'] = "Error updating profile image.";
                            }
                        } else {
                            $_SESSION['error'] = "Error uploading file.";
                        }
                    }
                } else {
                    $_SESSION['error'] = "Please select a file to upload.";
                }
                
                header("Location: student_profile.php?id=$student_id");
                exit();
        }
    }
}

// Display messages
if (isset($_SESSION['success'])) {
    $success_message = $_SESSION['success'];
    unset($_SESSION['success']);
}
if (isset($_SESSION['error'])) {
    $error_message = $_SESSION['error'];
    unset($_SESSION['error']);
}

// Get academic records (if table exists)
$academic_records = [];
try {
    $academic_sql = "SELECT * FROM academic_records WHERE student_id = ? ORDER BY academic_year DESC, semester DESC";
    $stmt = $conn->prepare($academic_sql);
    $stmt->bind_param("s", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $academic_records[] = $row;
    }
} catch (Exception $e) {
    // Academic records table might not exist yet
}

// Get fee information (if table exists)
$fee_accounts = [];
try {
    $fee_sql = "SELECT * FROM student_fee_accounts WHERE student_id = ? ORDER BY academic_year DESC, semester DESC";
    $stmt = $conn->prepare($fee_sql);
    $stmt->bind_param("s", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $fee_accounts[] = $row;
    }
} catch (Exception $e) {
    // Fee accounts table might not exist yet
}

// Get payment history (if table exists)
$payment_history = [];
try {
    $payment_sql = "SELECT * FROM fee_payments WHERE student_id = ? ORDER BY payment_date DESC LIMIT 10";
    $stmt = $conn->prepare($payment_sql);
    $stmt->bind_param("s", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $payment_history[] = $row;
    }
} catch (Exception $e) {
    // Fee payments table might not exist yet
}

// Calculate total balance
$total_balance = 0;
$total_paid = 0;
$total_fees = 0;
foreach ($fee_accounts as $account) {
    $total_balance += $account['balance'];
    $total_paid += $account['amount_paid'];
    $total_fees += $account['total_fees'];
}

// Handle profile update
function handleProfileUpdate() {
    global $conn;
    
    $student_id = $_SESSION['user_id'];
    $first_name = sanitizeInput($_POST['first_name']);
    $surname = sanitizeInput($_POST['surname']);
    $other_name = sanitizeInput($_POST['other_name']);
    $phone = sanitizeInput($_POST['phone']);
    $email = sanitizeInput($_POST['email']);
    $address = sanitizeInput($_POST['address']);
    $emergency_contact_name = sanitizeInput($_POST['emergency_contact_name']);
    $emergency_contact_phone = sanitizeInput($_POST['emergency_contact_phone']);
    
    $sql = "UPDATE students SET first_name = ?, surname = ?, other_name = ?, phone = ?, email = ?, address = ?, emergency_contact_name = ?, emergency_contact_phone = ?, updated_at = CURRENT_TIMESTAMP WHERE student_id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssssssss", $first_name, $surname, $other_name, $phone, $email, $address, $emergency_contact_name, $emergency_contact_phone, $student_id);
    
    if ($stmt->execute()) {
        logActivity($student_id, 'Student', 'Profile Updated', "Student updated their profile information", 'students', $student_id);
        $_SESSION['success'] = "Profile updated successfully!";
        
        // Update session variables
        $_SESSION['first_name'] = $first_name;
        $_SESSION['last_name'] = $surname;
    } else {
        $_SESSION['error'] = "Error updating profile: " . $conn->error;
    }
    
    header("Location: student_profile.php");
    exit();
}

// Handle photo upload
function handlePhotoUpload() {
    $student_id = $_SESSION['user_id'];
    
    if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK) {
        $upload_result = uploadPassportPhoto($_FILES['profile_photo'], $student_id);
        
        if ($upload_result['success']) {
            if (updateStudentPhoto($student_id, $upload_result['filename'])) {
                logActivity($student_id, 'Student', 'Photo Uploaded', "Student uploaded new profile photo", 'students', $student_id);
                $_SESSION['success'] = "Profile photo uploaded successfully!";
            } else {
                $_SESSION['error'] = "Photo uploaded but database update failed";
            }
        } else {
            $_SESSION['error'] = $upload_result['message'];
        }
    } else {
        $_SESSION['error'] = "Please select a photo to upload";
    }
    
    header("Location: student_profile.php");
    exit();
}

// Handle photo delete
function handlePhotoDelete() {
    global $conn;
    
    $student_id = $_SESSION['user_id'];
    
    // Get current photo
    $current_photo_sql = "SELECT profile_image FROM students WHERE student_id = ?";
    $current_result = executeQuery($current_photo_sql, [$student_id], 's');
    $current_photo = $current_result[0]['profile_image'] ?? '';
    
    // Delete photo file if it's not default
    if ($current_photo !== 'default-student.png') {
        deletePassportPhoto($current_photo);
    }
    
    // Update database to default
    $update_sql = "UPDATE students SET profile_image = 'default-student.png' WHERE student_id = ?";
    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param("s", $student_id);
    
    if ($stmt->execute()) {
        logActivity($student_id, 'Student', 'Photo Deleted', "Student deleted their profile photo", 'students', $student_id);
        $_SESSION['success'] = "Profile photo deleted successfully!";
    } else {
        $_SESSION['error'] = "Error deleting photo";
    }
    
    header("Location: student_profile.php");
    exit();
}

// Handle password change
function handlePasswordChange() {
    global $conn;
    
    $student_id = $_SESSION['user_id'];
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Get current password hash
    $user_sql = "SELECT password FROM users WHERE user_id = ?";
    $user_result = executeQuery($user_sql, [$student_id], 's');
    $user = $user_result[0] ?? null;
    
    if (!$user) {
        $_SESSION['error'] = "User account not found";
        header("Location: student_profile.php");
        exit();
    }
    
    // Verify current password
    if (!password_verify($current_password, $user['password']) && $current_password !== $user['password']) {
        $_SESSION['error'] = "Current password is incorrect";
        header("Location: student_profile.php");
        exit();
    }
    
    // Validate new password
    if (strlen($new_password) < 8) {
        $_SESSION['error'] = "New password must be at least 8 characters long";
        header("Location: student_profile.php");
        exit();
    }
    
    if ($new_password !== $confirm_password) {
        $_SESSION['error'] = "New passwords do not match";
        header("Location: student_profile.php");
        exit();
    }
    
    // Update password
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
    $update_sql = "UPDATE users SET password = ?, updated_at = CURRENT_TIMESTAMP WHERE user_id = ?";
    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param("ss", $hashed_password, $student_id);
    
    if ($stmt->execute()) {
        logActivity($student_id, 'Student', 'Password Changed', "Student changed their password", 'students', $student_id);
        $_SESSION['success'] = "Password changed successfully!";
    } else {
        $_SESSION['error'] = "Error changing password: " . $conn->error;
    }
    
    header("Location: student_profile.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, shrink-to-fit=no">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="theme-color" content="#3E2723">
    <title>My Profile - ISNM</title>
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
            --light-bg: #f8f9fa;
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
            font-weight: 600;
            color: var(--accent-color) !important;
            display: flex;
            align-items: center;
        }

        .navbar-brand img {
            height: 30px;
            width: auto;
            margin-right: 10px;
        }

        .main-container {
            padding: 20px;
            max-width: 1200px;
            margin: 0 auto;
        }

        .profile-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            border-radius: 20px;
            padding: 40px 20px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            position: relative;
            overflow: hidden;
        }

        .profile-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="rgba(255,255,255,0.1)"/><circle cx="75" cy="75" r="1" fill="rgba(255,255,255,0.1)"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
            opacity: 0.3;
        }

        .profile-photo-container {
            text-align: center;
            position: relative;
            z-index: 1;
            margin-bottom: 20px;
        }

        .profile-photo {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            border: 4px solid white;
            box-shadow: 0 8px 25px rgba(0,0,0,0.3);
            object-fit: cover;
            transition: transform 0.3s ease;
            cursor: pointer;
        }

        .profile-photo:hover {
            transform: scale(1.05);
        }

        .profile-info {
            text-align: center;
            position: relative;
            z-index: 1;
        }

        .profile-name {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }

        .profile-id {
            font-size: 1.2rem;
            opacity: 0.9;
            margin-bottom: 1rem;
        }

        .profile-section {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            border-left: 5px solid var(--primary-color);
        }

        .section-title {
            color: var(--primary-color);
            font-weight: bold;
            margin-bottom: 1.5rem;
            font-size: 1.3rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
        }

        .info-item {
            padding: 1rem;
            background: var(--light-bg);
            border-radius: 10px;
            border-left: 3px solid var(--primary-color);
        }

        .info-label {
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 0.25rem;
            font-size: 0.9rem;
        }

        .info-value {
            color: #333;
            font-size: 1.1rem;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border: none;
            border-radius: 8px;
            padding: 0.75rem 1.5rem;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(26, 35, 126, 0.3);
        }

        .btn-success {
            background: linear-gradient(135deg, var(--success-color), #218838);
            border: none;
            border-radius: 8px;
        }

        .btn-danger {
            background: linear-gradient(135deg, var(--danger-color), #c82333);
            border: none;
            border-radius: 8px;
        }

        .photo-upload-area {
            border: 2px dashed #ddd;
            border-radius: 10px;
            padding: 2rem;
            text-align: center;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .photo-upload-area:hover {
            border-color: var(--primary-color);
            background: #f8f9ff;
        }

        .photo-preview {
            max-width: 200px;
            max-height: 200px;
            border-radius: 10px;
            margin: 1rem auto;
            display: block;
            border: 3px solid var(--primary-color);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        /* Perfect Mobile Styles */
        @media (max-width: 768px) {
            .main-container {
                padding: 15px;
            }

            .profile-header {
                padding: 30px 15px;
                margin-bottom: 20px;
            }

            .profile-photo {
                width: 100px;
                height: 100px;
            }

            .profile-name {
                font-size: 1.5rem;
            }

            .profile-id {
                font-size: 1rem;
            }

            .profile-section {
                padding: 1.5rem;
                margin-bottom: 1.5rem;
            }

            .section-title {
                font-size: 1.2rem;
            }

            .info-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }

            .info-item {
                padding: 0.75rem;
            }

            .info-label {
                font-size: 0.85rem;
            }

            .info-value {
                font-size: 1rem;
            }

            .btn-primary {
                padding: 0.6rem 1.2rem;
                font-size: 0.9rem;
            }

            .photo-upload-area {
                padding: 1.5rem;
            }

            .photo-preview {
                max-width: 150px;
                max-height: 150px;
            }
        }

        @media (max-width: 480px) {
            .main-container {
                padding: 10px;
            }

            .profile-header {
                padding: 20px 10px;
                margin-bottom: 15px;
            }

            .profile-photo {
                width: 80px;
                height: 80px;
            }

            .profile-name {
                font-size: 1.3rem;
            }

            .profile-id {
                font-size: 0.9rem;
            }

            .profile-section {
                padding: 1rem;
                margin-bottom: 1rem;
            }

            .section-title {
                font-size: 1.1rem;
            }

            .info-item {
                padding: 0.5rem;
            }

            .info-label {
                font-size: 0.8rem;
            }

            .info-value {
                font-size: 0.9rem;
            }

            .btn-primary {
                padding: 0.5rem 1rem;
                font-size: 0.85rem;
            }

            .photo-upload-area {
                padding: 1rem;
            }

            .photo-preview {
                max-width: 120px;
                max-height: 120px;
            }
        }

        /* Landscape Mobile */
        @media (max-height: 600px) and (orientation: landscape) {
            .profile-header {
                padding: 20px 15px;
            }

            .profile-photo-container {
                margin-bottom: 15px;
            }
        }

        .tab-content {
            padding: 2rem 0;
        }

        .nav-tabs .nav-link {
            color: var(--primary-color);
            font-weight: 600;
            border: none;
            border-bottom: 3px solid transparent;
            transition: all 0.3s ease;
        }

        .nav-tabs .nav-link.active {
            border-bottom-color: var(--primary-color);
            background: none;
            color: var(--primary-color);
        }

        .nav-tabs .nav-link:hover {
            border-bottom-color: var(--primary-color);
            background: rgba(26, 35, 126, 0.1);
        }

        .stats-card {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            border-radius: 15px;
            padding: 1.5rem;
            text-align: center;
            box-shadow: 0 5px 20px rgba(0,0,0,0.2);
            transition: transform 0.3s ease;
        }

        .stats-card:hover {
            transform: translateY(-5px);
        }

        .stats-number {
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }

        .stats-label {
            font-size: 0.9rem;
            opacity: 0.9;
        }

        .activity-item {
            padding: 1rem;
            border-left: 3px solid var(--info-color);
            background: var(--light-bg);
            margin-bottom: 1rem;
            border-radius: 0 8px 8px 0;
        }

        .activity-time {
            font-size: 0.8rem;
            color: #666;
        }

        @media (max-width: 768px) {
            .main-container {
                padding: 1rem;
            }

            .profile-header {
                padding: 2rem 1rem;
            }

            .profile-name {
                font-size: 1.8rem;
            }

            .info-grid {
                grid-template-columns: 1fr;
            }
        }

        .loading-spinner {
            display: none;
            text-align: center;
            padding: 2rem;
        }

        .fade-in {
            animation: fadeIn 0.5s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>
    <!-- Perfect Mobile Responsive Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark sticky-top">
        <div class="container">
            <!-- Brand Logo -->
            <a class="navbar-brand d-flex align-items-center" href="student_dashboard.php">
                <img src="images/school-logo.png" alt="ISNM Logo" class="me-2" style="height: 30px; width: auto;">
                <span class="d-none d-md-inline">Student Portal</span>
            </a>
            
            <!-- Mobile Toggle Button -->
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#profileNavbar" aria-controls="profileNavbar" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <!-- Navigation Menu -->
            <div class="collapse navbar-collapse" id="profileNavbar">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="student_dashboard.php">
                            <i class="fas fa-tachometer-alt me-1"></i>Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="student_profile.php">
                            <i class="fas fa-user me-1"></i>My Profile
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="academic_records.php">
                            <i class="fas fa-graduation-cap me-1"></i>Academics
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="fee_management.php">
                            <i class="fas fa-money-bill me-1"></i>Fees
                        </a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle me-1"></i> <?php echo htmlspecialchars($student['first_name']); ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="student_profile.php">
                                <i class="fas fa-user me-2"></i>Profile
                            </a></li>
                            <li><a class="dropdown-item" href="settings.php">
                                <i class="fas fa-cog me-2"></i>Settings
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php">
                                <i class="fas fa-sign-out-alt me-2"></i>Logout
                            </a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="main-container">
        <!-- Profile Header -->
        <div class="profile-header fade-in">
            <div class="profile-photo-container">
                <img src="<?php echo getPassportPhotoUrl($student['profile_image']); ?>" alt="Profile Photo" class="profile-photo" onclick="document.getElementById('photoInput').click()">
                <div class="mt-3">
                    <button type="button" class="btn btn-light btn-sm" onclick="document.getElementById('photoInput').click()">
                        <i class="fas fa-camera"></i> Change Photo
                    </button>
                    <?php if ($student['profile_image'] !== 'default-student.png'): ?>
                    <button type="button" class="btn btn-danger btn-sm ms-2" onclick="confirmDeletePhoto()">
                        <i class="fas fa-trash"></i> Remove
                    </button>
                    <?php endif; ?>
                </div>
            </div>
            <div class="profile-info">
                <h1 class="profile-name"><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['surname']); ?></h1>
                <p class="profile-id"><?php echo htmlspecialchars($student['student_id']); ?></p>
                <p class="mb-0"><i class="fas fa-graduation-cap"></i> <?php echo htmlspecialchars($student['program']); ?> - <?php echo htmlspecialchars($student['level']); ?></p>
            </div>
        </div>

        <!-- Mobile-Friendly Profile Tabs -->
        <ul class="nav nav-tabs" id="profileTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="personal-tab" data-bs-toggle="tab" data-bs-target="#personal" type="button">
                    <i class="fas fa-user me-1"></i> Personal
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="academic-tab" data-bs-toggle="tab" data-bs-target="#academic" type="button">
                    <i class="fas fa-graduation-cap me-1"></i> Academics
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="fees-tab" data-bs-toggle="tab" data-bs-target="#fees" type="button">
                    <i class="fas fa-money-bill me-1"></i> Fees
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="security-tab" data-bs-toggle="tab" data-bs-target="#security" type="button">
                    <i class="fas fa-lock me-1"></i> Security
                </button>
            </li>
        </ul>

        <div class="tab-content" id="profileTabContent">
            <!-- Personal Information Tab -->
            <div class="tab-pane fade show active" id="personal" role="tabpanel">
                <div class="profile-section fade-in">
                    <h3 class="section-title">
                        <i class="fas fa-user me-2"></i> Personal Information
                    </h3>
                    
                    <form method="POST" action="student_profile.php">
                        <input type="hidden" name="action" value="update_profile">
                        
                        <div class="row g-3">
                            <div class="col-lg-6 col-md-12">
                                <div class="info-item">
                                    <div class="info-label">First Name</div>
                                    <input type="text" class="form-control" name="first_name" value="<?php echo htmlspecialchars($student['first_name']); ?>" required>
                                </div>
                            </div>
                            <div class="col-lg-6 col-md-12">
                                <div class="info-item">
                                    <div class="info-label">Surname</div>
                                    <input type="text" class="form-control" name="surname" value="<?php echo htmlspecialchars($student['surname']); ?>" required>
                                </div>
                            </div>
                            <div class="col-lg-6 col-md-12">
                                <div class="info-item">
                                    <div class="info-label">Other Name</div>
                                    <input type="text" class="form-control" name="other_name" value="<?php echo htmlspecialchars($student['other_name'] ?? ''); ?>">
                                </div>
                            </div>
                            <div class="col-lg-6 col-md-12">
                                <div class="info-item">
                                    <div class="info-label">Date of Birth</div>
                                    <div class="info-value"><?php echo formatDate($student['date_of_birth']); ?></div>
                                </div>
                            </div>
                            <div class="col-lg-6 col-md-12">
                                <div class="info-item">
                                    <div class="info-label">Gender</div>
                                    <div class="info-value"><?php echo htmlspecialchars($student['gender']); ?></div>
                                </div>
                            </div>
                            <div class="col-lg-6 col-md-12">
                                <div class="info-item">
                                    <div class="info-label">Nationality</div>
                                    <div class="info-value"><?php echo htmlspecialchars($student['nationality']); ?></div>
                                </div>
                            </div>
                            <div class="col-lg-6 col-md-12">
                                <div class="info-item">
                                    <div class="info-label">Phone Number</div>
                                    <input type="tel" class="form-control" name="phone" value="<?php echo htmlspecialchars($student['phone']); ?>" required>
                                </div>
                            </div>
                            <div class="col-lg-6 col-md-12">
                                <div class="info-item">
                                    <div class="info-label">Email Address</div>
                                    <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($student['email']); ?>" required>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="info-item">
                                    <div class="info-label">Address</div>
                                    <textarea class="form-control" name="address" rows="2"><?php echo htmlspecialchars($student['address'] ?? ''); ?></textarea>
                                </div>
                            </div>
                            <div class="col-lg-6 col-md-12">
                                <div class="info-item">
                                    <div class="info-label">Program</div>
                                <div class="info-value"><?php echo htmlspecialchars($student['program']); ?></div>
                            </div>
                            <div class="col-lg-6 col-md-12">
                                <div class="info-item">
                                    <div class="info-label">Level</div>
                                    <div class="info-value"><?php echo htmlspecialchars($student['level']); ?></div>
                                </div>
                            </div>
                            <div class="col-lg-6 col-md-12">
                                <div class="info-item">
                                    <div class="info-label">Intake Year</div>
                                    <div class="info-value"><?php echo htmlspecialchars($student['intake_year']); ?></div>
                                </div>
                            </div>
                            <div class="col-lg-6 col-md-12">
                                <div class="info-item">
                                    <div class="info-label">Emergency Contact Name</div>
                                    <input type="text" class="form-control" name="emergency_contact_name" value="<?php echo htmlspecialchars($student['emergency_contact_name'] ?? ''); ?>">
                                </div>
                            </div>
                            <div class="col-lg-6 col-md-12">
                                <div class="info-item">
                                    <div class="info-label">Emergency Contact Phone</div>
                                    <input type="tel" class="form-control" name="emergency_contact_phone" value="<?php echo htmlspecialchars($student['emergency_contact_phone'] ?? ''); ?>">
                                </div>
                            </div>
                        </div>
                        
                        <div class="text-center mt-4">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-save"></i> Update Profile
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Academic Records Tab -->
            <div class="tab-pane fade" id="academic" role="tabpanel">
                <div class="profile-section fade-in">
                    <h3 class="section-title">
                        <i class="fas fa-graduation-cap"></i> Academic Records
                    </h3>
                    
                    <?php if (empty($academic_records)): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-graduation-cap fa-3x text-muted mb-3"></i>
                            <p class="text-muted">No academic records available yet.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Academic Year</th>
                                        <th>Semester</th>
                                        <th>Year</th>
                                        <th>GPA</th>
                                        <th>Class Position</th>
                                        <th>Attendance</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($academic_records as $record): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($record['academic_year']); ?></td>
                                            <td><?php echo htmlspecialchars($record['semester']); ?></td>
                                            <td><?php echo htmlspecialchars($record['year']); ?></td>
                                            <td>
                                                <?php if ($record['gpa']): ?>
                                                    <span class="badge bg-success"><?php echo number_format($record['gpa'], 2); ?></span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">N/A</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($record['class_position']): ?>
                                                    <?php echo htmlspecialchars($record['class_position']); ?>/<?php echo htmlspecialchars($record['total_students']); ?>
                                                <?php else: ?>
                                                    <span class="text-muted">N/A</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($record['attendance_percentage']): ?>
                                                    <?php echo number_format($record['attendance_percentage'], 1); ?>%
                                                <?php else: ?>
                                                    <span class="text-muted">N/A</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-info">Active</span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Fee Information Tab -->
            <div class="tab-pane fade" id="fees" role="tabpanel">
                <div class="profile-section fade-in">
                    <h3 class="section-title">
                        <i class="fas fa-money-bill"></i> Fee Information & Balance
                    </h3>
                    
                    <!-- Overall Balance Summary -->
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <div class="stats-card">
                                <div class="stats-number"><?php echo formatCurrency($total_fees); ?></div>
                                <div class="stats-label">Total Fees</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="stats-card">
                                <div class="stats-number"><?php echo formatCurrency($total_paid); ?></div>
                                <div class="stats-label">Amount Paid</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="stats-card">
                                <div class="stats-number <?php echo $total_balance > 0 ? 'text-warning' : 'text-success'; ?>"><?php echo formatCurrency($total_balance); ?></div>
                                <div class="stats-label">Outstanding Balance</div>
                            </div>
                        </div>
                    </div>
                    
                    <?php if (empty($fee_accounts)): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-money-bill fa-3x text-muted mb-3"></i>
                            <p class="text-muted">No fee information available yet.</p>
                        </div>
                    <?php else: ?>
                        <!-- Fee Account Details -->
                        <h4 class="mb-3">Fee Account Details</h4>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Academic Year</th>
                                        <th>Program</th>
                                        <th>Year</th>
                                        <th>Semester</th>
                                        <th>Total Fees</th>
                                        <th>Amount Paid</th>
                                        <th>Balance</th>
                                        <th>Due Date</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($fee_accounts as $account): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($account['academic_year']); ?></td>
                                            <td><?php echo htmlspecialchars($account['program']); ?></td>
                                            <td><?php echo htmlspecialchars($account['year']); ?></td>
                                            <td><?php echo htmlspecialchars($account['semester']); ?></td>
                                            <td><?php echo formatCurrency($account['total_fees']); ?></td>
                                            <td><?php echo formatCurrency($account['amount_paid']); ?></td>
                                            <td>
                                                <span class="badge <?php echo $account['balance'] > 0 ? 'bg-warning' : 'bg-success'; ?>">
                                                    <?php echo formatCurrency($account['balance']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo formatDate($account['due_date'] ?? 'Not set'); ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo $account['status'] === 'fully_paid' ? 'success' : ($account['status'] === 'partially_paid' ? 'warning' : 'danger'); ?>">
                                                    <?php echo ucfirst(str_replace('_', ' ', $account['status'])); ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Payment History -->
                        <?php if (!empty($payment_history)): ?>
                            <h4 class="mb-3 mt-4">Payment History</h4>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Receipt Number</th>
                                            <th>Amount</th>
                                            <th>Method</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($payment_history as $payment): ?>
                                            <tr>
                                                <td><?php echo formatDate($payment['payment_date']); ?></td>
                                                <td><?php echo htmlspecialchars($payment['receipt_number']); ?></td>
                                                <td><?php echo formatCurrency($payment['amount_paid']); ?></td>
                                                <td><?php echo ucfirst(str_replace('_', ' ', $payment['payment_method'])); ?></td>
                                                <td>
                                                    <span class="badge bg-<?php echo $payment['status'] === 'verified' ? 'success' : 'warning'; ?>">
                                                        <?php echo ucfirst($payment['status']); ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Security Tab -->
            <div class="tab-pane fade" id="security" role="tabpanel">
                <div class="profile-section fade-in">
                    <h3 class="section-title">
                        <i class="fas fa-lock"></i> Security Settings
                    </h3>
                    
                    <form method="POST" action="student_profile.php">
                        <input type="hidden" name="action" value="change_password">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Current Password</label>
                                    <input type="password" class="form-control" name="current_password" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">New Password</label>
                                    <input type="password" class="form-control" name="new_password" minlength="8" required>
                                    <div class="form-text">Password must be at least 8 characters long</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Confirm New Password</label>
                                    <input type="password" class="form-control" name="confirm_password" minlength="8" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="text-center">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-lock"></i> Change Password
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Hidden Photo Upload Form -->
    <form id="photoUploadForm" method="POST" enctype="multipart/form-data" style="display: none;">
        <input type="hidden" name="action" value="upload_photo">
        <input type="file" id="photoInput" name="profile_photo" accept="image/*" onchange="submitPhotoForm()">
    </form>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        // Photo upload functionality
        function submitPhotoForm() {
            const fileInput = document.getElementById('photoInput');
            if (fileInput.files.length > 0) {
                const file = fileInput.files[0];
                
                // Check file size
                if (file.size > 5 * 1024 * 1024) {
                    alert('File size must be less than 5MB');
                    return;
                }
                
                // Check file type
                if (!file.type.match('image.*')) {
                    alert('Please select an image file');
                    return;
                }
                
                // Show loading
                showLoading();
                
                // Submit form
                document.getElementById('photoUploadForm').submit();
            }
        }

        function confirmDeletePhoto() {
            if (confirm('Are you sure you want to delete your profile photo?')) {
                showLoading();
                window.location.href = 'student_profile.php?action=delete_photo';
            }
        }

        function showLoading() {
            const loadingHtml = `
                <div class="loading-spinner">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2">Processing...</p>
                </div>
            `;
            
            document.body.insertAdjacentHTML('beforeend', loadingHtml);
            document.querySelector('.loading-spinner').style.display = 'block';
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

        // Profile photo preview on hover
        document.querySelector('.profile-photo').addEventListener('mouseenter', function() {
            this.style.transform = 'scale(1.1)';
        });

        document.querySelector('.profile-photo').addEventListener('mouseleave', function() {
            this.style.transform = 'scale(1.05)';
        });

        // Form validation
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function(e) {
                const passwordFields = form.querySelectorAll('input[type="password"]');
                if (passwordFields.length >= 2) {
                    const newPassword = passwordFields[1].value;
                    const confirmPassword = passwordFields[2]?.value;
                    
                    if (newPassword && confirmPassword && newPassword !== confirmPassword) {
                        e.preventDefault();
                        alert('New passwords do not match');
                        return false;
                    }
                }
            });
        });

        // Tab animation
        document.querySelectorAll('#profileTabs button').forEach(button => {
            button.addEventListener('shown.bs.tab', function(e) {
                const target = document.querySelector(e.target.getAttribute('data-bs-target'));
                target.classList.add('fade-in');
            });
        });
    </script>
</body>
</html>

<?php
// Display alerts
if (isset($_SESSION['success'])) {
    echo '<div class="alert alert-success alert-dismissible fade show position-fixed" style="top: 20px; right: 20px; z-index: 9999;">
        <i class="fas fa-check-circle"></i> ' . htmlspecialchars($_SESSION['success']) . '
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>';
    unset($_SESSION['success']);
}

if (isset($_SESSION['error'])) {
    echo '<div class="alert alert-danger alert-dismissible fade show position-fixed" style="top: 20px; right: 20px; z-index: 9999;">
        <i class="fas fa-exclamation-triangle"></i> ' . htmlspecialchars($_SESSION['error']) . '
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>';
    unset($_SESSION['error']);
}
?>
