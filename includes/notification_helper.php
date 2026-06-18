<?php
/**
 * Notification Helper — create, fetch, and manage notifications across all dashboards.
 * Uses website_db.notifications + notification_reads tables.
 */
require_once __DIR__ . '/../config/database.php';

if (!function_exists('getNotifConn')) {
    function getNotifConn() {
        if (function_exists('getDatabaseConnection')) {
            return getDatabaseConnection('website');
        }
        if (function_exists('getWebsiteConnection')) {
            return getWebsiteConnection();
        }
        return null;
    }
}

if (!function_exists('getStaffConn')) {
    function getStaffConn() {
        if (function_exists('getDatabaseConnection')) {
            return getDatabaseConnection('staffs');
        }
        if (function_exists('getStaffConnection')) {
            return getStaffConnection();
        }
        return null;
    }
}

if (!function_exists('createNotification')) {
    function createNotification($title, $message = '', $url = '', $type = 'info', $icon = 'fas fa-bell') {
        try {
            $conn = getNotifConn();
            if (!$conn) return false;
            $stmt = $conn->prepare("INSERT INTO notifications (title, message, url, type, icon) VALUES (?, ?, ?, ?, ?)");
            if (!$stmt) return false;
            $stmt->bind_param("sssss", $title, $message, $url, $type, $icon);
            $ok = $stmt->execute();
            $id = $stmt->insert_id;
            $stmt->close();
            return $ok ? $id : false;
        } catch (Exception $e) {
            error_log('createNotification: ' . $e->getMessage());
            return false;
        }
    }
}

if (!function_exists('notifyAllStaff')) {
    function notifyAllStaff($notification_id) {
        try {
            $staffConn = getStaffConn();
            $notifConn = getNotifConn();
            if (!$staffConn || !$notifConn) return 0;
            $r = $staffConn->query("SELECT id FROM staff WHERE is_active = 1");
            if (!$r) return 0;
            $count = 0;
            $stmt = $notifConn->prepare("INSERT IGNORE INTO notification_reads (notification_id, user_id, user_type) VALUES (?, ?, 'staff')");
            if (!$stmt) return 0;
            while ($row = $r->fetch_assoc()) {
                $stmt->bind_param("ii", $notification_id, $row['id']);
                if ($stmt->execute()) $count++;
            }
            $stmt->close();
            return $count;
        } catch (Exception $e) {
            error_log('notifyAllStaff: ' . $e->getMessage());
            return 0;
        }
    }
}

if (!function_exists('getUnreadNotificationCount')) {
    function getUnreadNotificationCount($user_id, $user_type = 'staff') {
        try {
            $conn = getNotifConn();
            if (!$conn) return 0;
            $stmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM notifications n WHERE NOT EXISTS (SELECT 1 FROM notification_reads nr WHERE nr.notification_id = n.id AND nr.user_id = ? AND nr.user_type = ?)");
            if (!$stmt) return 0;
            $stmt->bind_param("is", $user_id, $user_type);
            $stmt->execute();
            $result = (int)$stmt->get_result()->fetch_assoc()['cnt'];
            $stmt->close();
            return $result;
        } catch (Exception $e) {
            error_log('getUnreadNotificationCount: ' . $e->getMessage());
            return 0;
        }
    }
}

if (!function_exists('getRecentNotifications')) {
    function getRecentNotifications($user_id, $user_type = 'staff', $limit = 10) {
        try {
            $conn = getNotifConn();
            if (!$conn) return [];
            $stmt = $conn->prepare("SELECT n.*, CASE WHEN nr.id IS NOT NULL THEN 1 ELSE 0 END AS is_read FROM notifications n LEFT JOIN notification_reads nr ON nr.notification_id = n.id AND nr.user_id = ? AND nr.user_type = ? ORDER BY n.created_at DESC LIMIT ?");
            if (!$stmt) return [];
            $stmt->bind_param("isi", $user_id, $user_type, $limit);
            $stmt->execute();
            $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            return $rows;
        } catch (Exception $e) {
            error_log('getRecentNotifications: ' . $e->getMessage());
            return [];
        }
    }
}

if (!function_exists('markNotificationRead')) {
    function markNotificationRead($notification_id, $user_id, $user_type = 'staff') {
        try {
            $conn = getNotifConn();
            if (!$conn) return false;
            $stmt = $conn->prepare("INSERT IGNORE INTO notification_reads (notification_id, user_id, user_type) VALUES (?, ?, ?)");
            if (!$stmt) return false;
            $stmt->bind_param("iis", $notification_id, $user_id, $user_type);
            $ok = $stmt->execute();
            $stmt->close();
            return $ok;
        } catch (Exception $e) {
            error_log('markNotificationRead: ' . $e->getMessage());
            return false;
        }
    }
}

if (!function_exists('markAllNotificationsRead')) {
    function markAllNotificationsRead($user_id, $user_type = 'staff') {
        try {
            $conn = getNotifConn();
            if (!$conn) return false;
            $stmt = $conn->prepare("INSERT IGNORE INTO notification_reads (notification_id, user_id, user_type) SELECT n.id, ?, ? FROM notifications n WHERE NOT EXISTS (SELECT 1 FROM notification_reads nr WHERE nr.notification_id = n.id AND nr.user_id = ? AND nr.user_type = ?)");
            if (!$stmt) return false;
            $stmt->bind_param("isii", $user_id, $user_type, $user_id, $user_type);
            $ok = $stmt->execute();
            $stmt->close();
            return $ok;
        } catch (Exception $e) {
            error_log('markAllNotificationsRead: ' . $e->getMessage());
            return false;
        }
    }
}
