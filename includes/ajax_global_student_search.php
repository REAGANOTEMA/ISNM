<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../views/student_data_loader.php';
require_once __DIR__ . '/global_search.php';

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_path', '/ISNM/');
    session_start();
}

$csrfToken = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
if (empty($csrfToken) || !isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrfToken)) {
    header('Content-Type: application/json');
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Invalid security token']);
    exit;
}

if (($_POST['action'] ?? '') === 'global_stu_search' && function_exists('globalStudentSearchHandler')) {
    globalStudentSearchHandler(
        getStaffConnection(),
        getStudentsConnection(),
        getStaffConnection(),
        getWebsiteConnection(),
        function_exists('getICTConnection') ? getICTConnection() : null
    );
}

echo json_encode([]);
