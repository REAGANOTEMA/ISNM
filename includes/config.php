<?php
// Legacy configuration - uses staff database for compatibility
// Modern code should use config/database.php + includes/config_enhanced.php

$host = 'localhost';
$dbname = 'igangaschoolofl_staffs_db';
$username = 'root';
$password = '';

$conn = new mysqli($host, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

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
    
    $sql = "INSERT INTO staff_activity_log (staff_id, activity_type, activity_description, module_accessed, ip_address, user_agent, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssss", $user_id, $user_role, $activity_type, $activity_description, $module_affected, $record_id);
    $stmt->execute();
    $stmt->close();
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function checkAccessLevel($required_level) {
    if (!isset($_SESSION['access_level']) || $_SESSION['access_level'] < $required_level) {
        header("Location: ../staff-login.php");
        exit();
    }
}

function getUserInfo($user_id) {
    global $conn;
    $sql = "SELECT * FROM staff WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc() ?: null;
}

function formatDate($date, $format = 'd/m/Y') {
    if (empty($date)) return '';
    return date($format, strtotime($date));
}

function generatePagination($current_page, $total_pages, $base_url) {
    $pagination = '<nav aria-label="Page navigation"><ul class="pagination justify-content-center">';
    
    if ($current_page > 1) {
        $pagination .= '<li class="page-item"><a class="page-link" href="' . $base_url . '?page=' . ($current_page - 1) . '">Previous</a></li>';
    }
    
    $start_page = max(1, $current_page - 2);
    $end_page = min($total_pages, $current_page + 2);
    
    for ($i = $start_page; $i <= $end_page; $i++) {
        $active_class = $i == $current_page ? 'active' : '';
        $pagination .= '<li class="page-item ' . $active_class . '"><a class="page-link" href="' . $base_url . '?page=' . $i . '">' . $i . '</a></li>';
    }
    
    if ($current_page < $total_pages) {
        $pagination .= '<li class="page-item"><a class="page-link" href="' . $base_url . '?page=' . ($current_page + 1) . '">Next</a></li>';
    }
    
    $pagination .= '</ul></nav>';
    return $pagination;
}

error_reporting(E_ALL);
ini_set('display_errors', 1);

date_default_timezone_set('Africa/Kampala');
?>
