<?php
// Legacy configuration kept for older modules.
// Prefer config/database.php for new code.

require_once __DIR__ . '/../config/database.php';

$host = STAFF_DB_HOST;
$dbname = STAFF_DB_NAME;
$username = STAFF_DB_USER;
$password = STAFF_DB_PASS;

mysqli_report(MYSQLI_REPORT_OFF);
$conn = new mysqli($host, $username, $password, $dbname, STAFF_DB_PORT);

if ($conn->connect_error) {
    error_log('includes/config.php connection failed: ' . $conn->connect_error);
    $conn = null;
} elseif ($dbname !== '') {
    $conn->set_charset(STAFF_DB_CHARSET);
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function executeQuery($sql, $params = [], $types = '') {
    global $conn;

    if (!$conn) {
        return [];
    }

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return [];
    }

    if (!empty($params) && !empty($types)) {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    if ($result === false) {
        $stmt->close();
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
    $stmt->bind_param('ssssss', $user_id, $activity_type, $activity_description, $module_affected, $ip_address, $user_agent);
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
ini_set('display_errors', 1);

date_default_timezone_set('Africa/Kampala');
?>
