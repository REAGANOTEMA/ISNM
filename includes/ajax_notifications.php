<?php
/**
 * AJAX endpoint for notification operations.
 * GET  ?action=fetch   — returns unread count + recent notifications
 * POST ?action=mark_read — marks a single notification as read
 * POST ?action=mark_all_read — marks all as read
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/notification_helper.php';

header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) session_start();

$user_id = (int)($_SESSION['user_id'] ?? 0);
$user_type = ($_SESSION['type'] ?? '') === 'student' ? 'student' : 'staff';

if (!$user_id) {
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

$action = $_REQUEST['action'] ?? 'fetch';

switch ($action) {
    case 'fetch':
        $unread = getUnreadNotificationCount($user_id, $user_type);
        $recent = getRecentNotifications($user_id, $user_type, 10);
        echo json_encode(['unread' => $unread, 'notifications' => $recent]);
        break;

    case 'mark_read':
        $nid = (int)($_POST['id'] ?? $_GET['id'] ?? 0);
        if ($nid) markNotificationRead($nid, $user_id, $user_type);
        echo json_encode(['ok' => true]);
        break;

    case 'mark_all_read':
        markAllNotificationsRead($user_id, $user_type);
        echo json_encode(['ok' => true]);
        break;

    default:
        echo json_encode(['error' => 'Unknown action']);
}
