<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['logged_in']) || $_SESSION['type'] !== 'staff' || empty($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/dashboard_analytics.php';

$conn = getStaffConnection();
$studentsConn = getStudentsConnection();
$websiteConn = getWebsiteConnection();

if (!$conn) {
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}

try {
    $data = getAnalyticsData($conn, $studentsConn, $websiteConn);
    echo json_encode(['success' => true, 'data' => $data]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
