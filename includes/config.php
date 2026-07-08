<?php
// Legacy configuration kept for older modules.
// Prefer config/database.php for new code.

require_once __DIR__ . '/../config/database.php';

$conn = getStaffConnection();

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_samesite', 'Lax');
    ini_set('session.use_strict_mode', 1);
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        ini_set('session.cookie_secure', 1);
    }
    session_start();
}

if (!function_exists('executeQuery')) {
function executeQuery($database, $sql = '', $params = [], $types = '') {
    if (empty($sql) && is_string($database)) {
        $sql = $database;
        $database = null;
    }
    $conn = null;
    if ($database) {
        $map = ['staff'=>'getStaffConnection','students'=>'getStudentsConnection','website'=>'getWebsiteConnection','ict'=>'getICTConnection'];
        $func = $map[$database] ?? null;
        $conn = $func ? $func() : getStaffConnection();
    } else {
        global $conn;
        if (!$conn) $conn = getStaffConnection();
    }
    if (!$conn) return [];

    $stmt = $conn->prepare($sql);
    if (!$stmt) return [];

    if (!empty($params) && !empty($types)) {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    if ($result === false) { $stmt->close(); return []; }

    $data = [];
    while ($row = $result->fetch_assoc()) { $data[] = $row; }
    $stmt->close();
    return $data;
}
}

function sanitizeInput($input) {
    global $conn;
    if (!$conn) {
        return htmlspecialchars(trim((string) $input), ENT_QUOTES, 'UTF-8');
    }
    return htmlspecialchars(trim($conn->real_escape_string($input)), ENT_QUOTES, 'UTF-8');
}

function logActivity($user_id, $user_role, $activity_type, $activity_description, $module_affected, $record_id) {
    global $conn;
    if (!$conn) return;

    $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';

    $sql = 'INSERT INTO staff_activity_log (staff_id, activity_type, activity_description, module_accessed, ip_address, user_agent, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())';

    $stmt = $conn->prepare($sql);
    if (!$stmt) return;
    $stmt->bind_param('isssss', $user_id, $activity_type, $activity_description, $module_affected, $ip_address, $user_agent);
    $stmt->execute();
    $stmt->close();
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function checkAccessLevel($required_level) {
    if (!isset($_SESSION['access_level']) || $_SESSION['access_level'] < $required_level) {
        header('Location: ../staff-login.php');
        exit();
    }
}

function getUserInfo($user_id) {
    global $conn;
    if (!$conn) return null;

    $sql = 'SELECT * FROM staff WHERE id = ?';
    $stmt = $conn->prepare($sql);
    if (!$stmt) return null;
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc() ?: null;
    $stmt->close();
    return $row;
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
$env = ($env = getenv('APP_ENV')) !== false ? $env : ($_ENV['APP_ENV'] ?? 'production');
ini_set('display_errors', $env === 'development' ? '1' : '0');

date_default_timezone_set('Africa/Kampala');
?>
