<?php
/**
 * Notification Helper — create, fetch, and manage general notifications.
 * Uses staff_notifications + staff_notification_reads (NOT the bursar notifications table).
 */
require_once __DIR__ . '/../config/database.php';

if (!function_exists('getNotifConn')) {
    function getNotifConn() {
        if (function_exists('getStaffConnection')) {
            return getStaffConnection();
        }
        if (function_exists('getDatabaseConnection')) {
            return getDatabaseConnection('staffs');
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

if (!function_exists('ensureNotificationTables')) {
    function ensureNotificationTables($conn) {
        static $done = false;
        if ($done) return;
        $done = true;
        $conn->query("CREATE TABLE IF NOT EXISTS staff_notifications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            message TEXT,
            type VARCHAR(30) DEFAULT 'info',
            url VARCHAR(500) DEFAULT NULL,
            icon VARCHAR(100) DEFAULT 'fas fa-bell',
            target_user_id INT DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            KEY idx_created (created_at),
            KEY idx_target (target_user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $conn->query("CREATE TABLE IF NOT EXISTS staff_notification_reads (
            id INT AUTO_INCREMENT PRIMARY KEY,
            notification_id INT NOT NULL,
            user_id INT NOT NULL,
            user_type VARCHAR(20) DEFAULT 'staff',
            read_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uq_notif_read (notification_id, user_id, user_type),
            KEY idx_user (user_id, user_type)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        // Ensure user_type column exists (added after initial table creation)
        @$conn->query("ALTER TABLE staff_notification_reads ADD COLUMN IF NOT EXISTS `user_type` VARCHAR(20) DEFAULT 'staff'");
        // Add unique key if missing
        @$conn->query("ALTER TABLE staff_notification_reads ADD UNIQUE KEY IF NOT EXISTS uq_notif_read (notification_id, user_id, user_type)");
    }
}

if (!function_exists('createNotification')) {
    function createNotification($title, $message = '', $url = '', $type = 'info', $icon = 'fas fa-bell') {
        try {
            $conn = getNotifConn();
            if (!$conn) return false;
            ensureNotificationTables($conn);
            $stmt = $conn->prepare("INSERT INTO staff_notifications (title, message, type, url, icon, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
            if (!$stmt) return false;
            $stmt->bind_param("sssss", $title, $message, $type, $url, $icon);
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
    /**
     * Mark a notification as intended for all active staff.
     * This does NOT mark it as read — notifications are UNREAD by default.
     * Each user will see the notification until they explicitly mark it as read
     * via markNotificationRead() or markAllNotificationsRead().
     */
    function notifyAllStaff($notification_id) {
        try {
            $staffConn = getStaffConn();
            if (!$staffConn) return 0;
            $r = $staffConn->query("SELECT COUNT(*) AS cnt FROM staff WHERE status = 'Active'");
            if (!$r) return 0;
            $row = $r->fetch_assoc();
            return (int)($row['cnt'] ?? 0);
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
            ensureNotificationTables($conn);
            $stmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM staff_notifications n WHERE NOT EXISTS (SELECT 1 FROM staff_notification_reads nr WHERE nr.notification_id = n.id AND nr.user_id = ? AND nr.user_type = ?)");
            if (!$stmt) return 0;
            $stmt->bind_param("is", $user_id, $user_type);
            if (!$stmt->execute()) { error_log('getUnreadNotificationCount execute failed: ' . ($stmt->error ?? 'unknown')); }
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
            ensureNotificationTables($conn);
            $stmt = $conn->prepare("SELECT n.*, CASE WHEN nr.id IS NOT NULL THEN 1 ELSE 0 END AS is_read FROM staff_notifications n LEFT JOIN staff_notification_reads nr ON nr.notification_id = n.id AND nr.user_id = ? AND nr.user_type = ? ORDER BY n.created_at DESC LIMIT ?");
            if (!$stmt) return [];
            $stmt->bind_param("isi", $user_id, $user_type, $limit);
            if (!$stmt->execute()) { error_log('getRecentNotifications execute failed: ' . ($stmt->error ?? 'unknown')); }
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
            ensureNotificationTables($conn);
            $stmt = $conn->prepare("INSERT IGNORE INTO staff_notification_reads (notification_id, user_id, user_type) VALUES (?, ?, ?)");
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
            ensureNotificationTables($conn);
            $stmt = $conn->prepare("INSERT IGNORE INTO staff_notification_reads (notification_id, user_id, user_type) SELECT n.id, ?, ? FROM staff_notifications n WHERE NOT EXISTS (SELECT 1 FROM staff_notification_reads nr WHERE nr.notification_id = n.id AND nr.user_id = ? AND nr.user_type = ?)");
            if (!$stmt) return false;
            $stmt->bind_param("isis", $user_id, $user_type, $user_id, $user_type);
            $ok = $stmt->execute();
            $stmt->close();
            return $ok;
        } catch (Exception $e) {
            error_log('markAllNotificationsRead: ' . $e->getMessage());
            return false;
        }
    }
}
