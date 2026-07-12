<?php
/**
 * AJAX endpoint for notification operations.
 * GET  ?action=fetch   — returns unread count + recent notifications
 * POST ?action=mark_read — marks a single notification as read
 * POST ?action=mark_all_read — marks all as read
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/notification_helper.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

ob_start(function($buf) {
    // Strip any UTF-8 BOM that might leak into output
    if (strlen($buf) >= 3 && ord($buf[0]) === 0xEF && ord($buf[1]) === 0xBB && ord($buf[2]) === 0xBF) {
        $buf = substr($buf, 3);
    }
    return $buf;
});

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

    case 'get_notifications':
        $limit = min((int)($_GET['limit'] ?? 10), 50);
        $recent = getRecentNotifications($user_id, $user_type, $limit);
        $formatted = [];
        foreach ($recent as $n) {
            $formatted[] = [
                'title'      => $n['title'] ?? '',
                'message'    => $n['message'] ?? '',
                'type'       => $n['type'] ?? 'info',
                'read_at'    => $n['is_read'] ?? null,
                'created_at' => $n['created_at'] ?? '',
                'time_ago'   => function_exists('timeAgoNotif') ? timeAgoNotif($n['created_at'] ?? '') : ($n['created_at'] ?? ''),
            ];
        }
        echo json_encode(['success' => true, 'notifications' => $formatted]);
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

function timeAgoNotif($datetime) {
    if (empty($datetime)) return '';
    $now = new DateTime();
    try { $ago = new DateTime($datetime); } catch (Exception $e) { error_log('ajax_notifications timeago failed: ' . $e->getMessage()); return $datetime; }
    $diff = $now->diff($ago);
    if ($diff->y > 0) return $diff->y . 'y ago';
    if ($diff->m > 0) return $diff->m . 'mo ago';
    if ($diff->d > 0) return $diff->d . 'd ago';
    if ($diff->h > 0) return $diff->h . 'h ago';
    if ($diff->i > 0) return $diff->i . 'm ago';
    return 'Just now';
}
