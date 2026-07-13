<?php
/**
 * Student Search (DB-only) — Legacy bridge
 *
 * All new code should call includes/ajax_student_search.php directly.
 * This file is kept for backward compatibility with dashboards that
 * still reference it. It validates auth then delegates to the centralized endpoint.
 */
header('Content-Type: application/json');
require_once __DIR__ . '/staff_dashboard_access.php';

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'students' => [], 'count' => 0, 'message' => 'Unauthorized']);
    exit;
}

$ctx = bootstrapStaffDashboard([]);

$term = trim($_GET['term'] ?? $_GET['q'] ?? $_GET['query'] ?? '');
if (strlen($term) < 1) {
    echo json_encode(['success' => true, 'students' => [], 'count' => 0]);
    exit;
}

require_once __DIR__ . '/ajax_student_search.php';
exit;
