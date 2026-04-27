<?php
// Database configuration
$host = 'localhost';
$dbname = 'isnm_db';
$username = 'root';
$password = 'ReagaN23#';

// Create connection
$conn = new mysqli($host, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset
$conn->set_charset("utf8mb4");

// Session configuration - ensure session is started only once
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Global functions
function executeQuery($sql, $params = [], $types = '') {
    global $conn;
    
    $stmt = $conn->prepare($sql);
    
    if (!empty($params) && !empty($types)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result === false) {
        return [];
    }
    
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    
    $stmt->close();
    return $data;
}

function sanitizeInput($input) {
    global $conn;
    return htmlspecialchars(trim($conn->real_escape_string($input)));
}

function logActivity($user_id, $user_role, $activity_type, $activity_description, $module_affected, $record_id) {
    global $conn;
    
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    
    $sql = "INSERT INTO activity_logs (user_id, user_role, activity_type, activity_description, module_affected, record_id, ip_address, user_agent, activity_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssssss", $user_id, $user_role, $activity_type, $activity_description, $module_affected, $record_id, $ip_address, $user_agent);
    $stmt->execute();
    $stmt->close();
}

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Check user access level
function checkAccessLevel($required_level) {
    if (!isset($_SESSION['access_level']) || $_SESSION['access_level'] < $required_level) {
        // Redirect to appropriate login page based on session role
        if (isset($_SESSION['role']) && $_SESSION['role'] === 'Student') {
            header("Location: ../student-login.php");
        } else {
            header("Location: ../staff-login.php");
        }
        exit();
    }
}

// Get user information
function getUserInfo($user_id) {
    $sql = "SELECT * FROM users WHERE user_id = ?";
    $result = executeQuery($sql, [$user_id], 's');
    return $result[0] ?? null;
}

// Format date
function formatDate($date, $format = 'd/m/Y') {
    if (empty($date)) return '';
    return date($format, strtotime($date));
}

// Generate pagination links
function generatePagination($current_page, $total_pages, $base_url) {
    $pagination = '<nav aria-label="Page navigation"><ul class="pagination justify-content-center">';
    
    // Previous button
    if ($current_page > 1) {
        $pagination .= '<li class="page-item"><a class="page-link" href="' . $base_url . '?page=' . ($current_page - 1) . '">Previous</a></li>';
    }
    
    // Page numbers
    $start_page = max(1, $current_page - 2);
    $end_page = min($total_pages, $current_page + 2);
    
    for ($i = $start_page; $i <= $end_page; $i++) {
        $active_class = $i == $current_page ? 'active' : '';
        $pagination .= '<li class="page-item ' . $active_class . '"><a class="page-link" href="' . $base_url . '?page=' . $i . '">' . $i . '</a></li>';
    }
    
    // Next button
    if ($current_page < $total_pages) {
        $pagination .= '<li class="page-item"><a class="page-link" href="' . $base_url . '?page=' . ($current_page + 1) . '">Next</a></li>';
    }
    
    $pagination .= '</ul></nav>';
    return $pagination;
}

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set timezone
date_default_timezone_set('Africa/Kampala');
?>
