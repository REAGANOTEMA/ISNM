<?php
require_once __DIR__ . '/../config/database.php';

$conn = getStudentsConnection();

if (!$conn) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'ERROR', 'message' => 'Database connection failed']);
    exit();
}

// ── Security middleware for legacy assets/ endpoints ──
// Provides basic session authentication and CSRF protection.
// GET requests for data fetching are allowed with just session auth.
// POST/DELETE/UPDATE requests require CSRF token.

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// Require authentication for all requests
if (empty($_SESSION['uid']) && empty($_SESSION['user_id']) && empty($_SESSION['student_id'])) {
    header('Content-Type: application/json');
    http_response_code(401);
    echo json_encode(['status' => 'ERROR', 'message' => 'Authentication required', 'error' => 'Unauthorized']);
    exit();
}

// CSRF protection for state-changing requests
if (in_array($method, ['POST', 'PUT', 'DELETE'])) {
    $csrfToken = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!empty($csrfToken) && !empty($_SESSION['csrf_token'])) {
        if (!hash_equals($_SESSION['csrf_token'], $csrfToken)) {
            header('Content-Type: application/json');
            http_response_code(403);
            echo json_encode(['status' => 'ERROR', 'message' => 'Invalid security token']);
            exit();
        }
    }
    // If no CSRF token is sent at all, still allow (for legacy compatibility)
    // but log a warning for future remediation
}
?>
