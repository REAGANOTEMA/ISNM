<?php
/**
 * Application Configuration for ISNM Student Management System
 */

// Start session
session_start();

// Application settings
define('APP_NAME', 'ISNM Student Management System');
define('APP_VERSION', '1.0.0');
define('APP_URL', 'https://isnm.ac.ug');
define('APP_EMAIL_DOMAIN', 'igangaschoolofnursingandmidwifery.ac.ug');

// Department Email Contacts (for student messaging)
define('EMAIL_DIRECTOR_GENERAL', 'director_general@igangaschoolofnursingandmidwifery.ac.ug');
define('EMAIL_CEO', 'ceo@igangaschoolofnursingandmidwifery.ac.ug');
define('EMAIL_DIRECTOR_ACADEMICS', 'director_academics@igangaschoolofnursingandmidwifery.ac.ug');
define('EMAIL_DIRECTOR_FINANCE', 'director_finance@igangaschoolofnursingandmidwifery.ac.ug');
define('EMAIL_DIRECTOR_ICT', 'director_ict@igangaschoolofnursingandmidwifery.ac.ug');
define('EMAIL_PRINCIPAL', 'principal@igangaschoolofnursingandmidwifery.ac.ug');
define('EMAIL_DEPUTY_PRINCIPAL', 'deputy_principal@igangaschoolofnursingandmidwifery.ac.ug');
define('EMAIL_BURSAR', 'bursar@igangaschoolofnursingandmidwifery.ac.ug');
define('EMAIL_ADMISSIONS', 'admissions@igangaschoolofnursingandmidwifery.ac.ug');
define('EMAIL_REGISTRAR', 'registrar@igangaschoolofnursingandmidwifery.ac.ug');
define('EMAIL_HR', 'hr@igangaschoolofnursingandmidwifery.ac.ug');
define('EMAIL_SECRETARY', 'secretary@igangaschoolofnursingandmidwifery.ac.ug');
define('EMAIL_LIBRARIAN', 'librarian@igangaschoolofnursingandmidwifery.ac.ug');
define('EMAIL_NURSING_HEAD', 'nursing@igangaschoolofnursingandmidwifery.ac.ug');
define('EMAIL_MIDWIFERY_HEAD', 'midwifery@igangaschoolofnursingandmidwifery.ac.ug');
define('EMAIL_MATRONS', 'matrons@igangaschoolofnursingandmidwifery.ac.ug');
define('EMAIL_WARDENS', 'wardens@igangaschoolofnursingandmidwifery.ac.ug');
define('EMAIL_LAB', 'lab@igangaschoolofnursingandmidwifery.ac.ug');
define('EMAIL_SECURITY', 'security@igangaschoolofnursingandmidwifery.ac.ug');

// File upload settings
define('UPLOAD_PATH', __DIR__ . '/../uploads/');
define('STUDENT_PHOTO_PATH', UPLOAD_PATH . 'students/');
define('MAX_FILE_SIZE', 2 * 1024 * 1024); // 2MB
define('ALLOWED_IMAGE_TYPES', ['jpg', 'jpeg', 'png']);

// Pagination settings
define('ITEMS_PER_PAGE', 20);

// Security settings
define('SESSION_LIFETIME', 3600); // 1 hour
define('HASH_ALGO', PASSWORD_DEFAULT);

// Role permissions
define('ROLES', [
    'admin' => ['create', 'read', 'update', 'delete', 'import', 'export', 'reports'],
    'principal' => ['create', 'read', 'update', 'delete', 'import', 'export', 'reports'],
    'director' => ['create', 'read', 'update', 'delete', 'import', 'export', 'reports'],
    'bursar' => ['read', 'reports', 'fees'],
    'hr' => ['read', 'create', 'update', 'reports'],
    'secretary' => ['read', 'create', 'update'],
    'lecturer' => ['read']
]);

// Error reporting (production: log errors, don't display)
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Timezone
date_default_timezone_set('Africa/Kampala');

// Include required files
require_once __DIR__ . '/database.php';

// Helper functions
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

if (!function_exists('hasPermission')) {
function hasPermission($permission) {
    if (!isLoggedIn()) return false;
    
    $role = $_SESSION['user_role'] ?? '';
    return in_array($permission, ROLES[$role] ?? []);
}
}

function redirect($url) {
    header("Location: $url");
    exit();
}

function flashMessage($type, $message) {
    $_SESSION['flash'][$type] = $message;
}

function getFlashMessages() {
    $messages = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $messages;
}

function generateFileName($originalName) {
    $extension = pathinfo($originalName, PATHINFO_EXTENSION);
    return uniqid() . '_' . time() . '.' . $extension;
}

function validateImage($file) {
    // Check file size
    if ($file['size'] > MAX_FILE_SIZE) {
        return 'File size must be less than 2MB';
    }
    
    // Check file type
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, ALLOWED_IMAGE_TYPES)) {
        return 'Only JPG, JPEG, and PNG files are allowed';
    }
    
    // Check if it's actually an image
    if (!getimagesize($file['tmp_name'])) {
        return 'File must be a valid image';
    }
    
    return true;
}

function uploadImage($file, $uploadPath) {
    $validation = validateImage($file);
    if ($validation !== true) {
        return ['success' => false, 'error' => $validation];
    }
    
    // Create directory if it doesn't exist
    if (!is_dir($uploadPath)) {
        mkdir($uploadPath, 0777, true);
    }
    
    $fileName = generateFileName($file['name']);
    $filePath = $uploadPath . $fileName;
    
    if (move_uploaded_file($file['tmp_name'], $filePath)) {
        return ['success' => true, 'filename' => $fileName];
    }
    
    return ['success' => false, 'error' => 'Failed to upload file'];
}
?>
