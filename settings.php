<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id']) && !isset($_SESSION['uid'])) {
    // Redirect to appropriate login page
    if (strpos($_SERVER['REQUEST_URI'], 'student') !== false) {
        header('Location: student-login.php');
    } else {
        header('Location: staff-login.php');
    }
    exit();
}

// Get user role to determine which settings to show
$user_role = isset($_SESSION['role']) ? $_SESSION['role'] : 'Staff';

// Redirect to appropriate settings page based on role
if ($user_role === 'Student') {
    header('Location: student_profile.php');
} elseif (in_array($user_role, ['School Principal', 'Academic Registrar', 'School Bursar', 'School Secretary', 'HR Manager'])) {
    header('Location: admin_panel/settings.php');
} elseif (in_array($user_role, ['Lecturers', 'teacher'])) {
    header('Location: teacher_panel/settings.php');
} else {
    // Default to admin settings for other roles
    header('Location: admin_panel/settings.php');
}
exit();
?>
