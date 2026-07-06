<?php
/**
 * ISNM Enterprise Auth — Shared authentication & permission engine
 * Used by ALL dashboards. No duplicates — this is the single source of truth.
 *
 * Provides:
 * - checkPermission($conn, $roleId, $permissionSlug) : bool
 * - getRolePermissions($conn, $roleId) : array
 * - getRoleName($conn, $roleId) : string
 * - hasRole($roleName) : bool
 * - requirePermission($conn, $roleId, $permissionSlug) : void (redirects if denied)
 * - getStaffById($conn, $staffId) : array|null
 * - getSystemSetting($conn, $key, $default) : mixed
 * - setSystemSetting($conn, $key, $value) : bool
 */

// Auth guard — redirect to staff-login.php if not authenticated
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || ($_SESSION['type'] ?? '') !== 'staff') {
    $redirect = isset($_SERVER['REQUEST_URI']) ? urlencode($_SERVER['REQUEST_URI']) : '';
    session_write_close();
    header('Location: ../staff-login.php' . ($redirect ? "?redirect=$redirect" : ''));
    exit();
}

if (function_exists('checkEnterprisePermission')) return;

/**
 * Check if a role has a specific permission.
 * Checks staff_roles.permissions JSON first, falls back to permissions table.
 */
if (!function_exists('checkEnterprisePermission')) {
function checkEnterprisePermission($conn, int $roleId, string $permissionSlug): bool {
    if (!$conn || $roleId <= 0) return false;

    // Director General and CEO have full access
    $roleName = getRoleName($conn, $roleId);
    if (in_array(strtolower($roleName), ['director general', 'ceo', 'system admin'])) return true;

    // Check staff_roles.permissions JSON column
    try {
        $stmt = $conn->prepare("SELECT permissions FROM staff_roles WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param('i', $roleId);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $stmt->close();

            if ($row && !empty($row['permissions'])) {
                $perms = json_decode($row['permissions'], true);
                if (is_array($perms)) {
                    // Check if permission slug exists in array or if wildcard * is set
                    if (in_array('*', $perms) || in_array($permissionSlug, $perms)) return true;
                    // Check category match (e.g., 'students.*' matches 'students.view')
                    foreach ($perms as $p) {
                        if (substr($p, -2) === '.*') {
                            $cat = substr($p, 0, -2);
                            if (strpos($permissionSlug, $cat . '.') === 0) return true;
                        }
                    }
                }
            }
        }
    } catch (Exception $e) { error_log('enterprise_auth.php: ' . $e->getMessage()); }

    // Fallback: check permissions table via role_permissions junction
    try {
        $stmt2 = $conn->prepare(
            "SELECT COUNT(*) cnt FROM role_permissions rp
             JOIN permissions p ON rp.permission_id = p.id
             WHERE rp.role_id = ? AND p.slug = ?"
        );
        if ($stmt2) {
            $stmt2->bind_param('is', $roleId, $permissionSlug);
            $stmt2->execute();
            $cnt = (int)$stmt2->get_result()->fetch_assoc()['cnt'];
            $stmt2->close();
            return $cnt > 0;
        }
    } catch (Exception $e) { error_log('enterprise_auth.php: ' . $e->getMessage()); }

    return false;
}
}

/**
 * Get all permissions for a role (from staff_roles.permissions JSON).
 */
if (!function_exists('getRolePermissions')) {
function getRolePermissions($conn, int $roleId): array {
    if (!$conn || $roleId <= 0) return [];
    try {
        $stmt = $conn->prepare("SELECT permissions FROM staff_roles WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param('i', $roleId);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if ($row && !empty($row['permissions'])) {
                $perms = json_decode($row['permissions'], true);
                return is_array($perms) ? $perms : [];
            }
        }
    } catch (Exception $e) { error_log('enterprise_auth.php: ' . $e->getMessage()); }
    return [];
}
}

/**
 * Get role name by ID.
 */
if (!function_exists('getRoleName')) {
function getRoleName($conn, int $roleId): string {
    if (!$conn || $roleId <= 0) return '';
    static $cache = [];
    if (isset($cache[$roleId])) return $cache[$roleId];
    try {
        $stmt = $conn->prepare("SELECT role_name FROM staff_roles WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param('i', $roleId);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            $name = $row['role_name'] ?? '';
            $cache[$roleId] = $name;
            return $name;
        }
    } catch (Exception $e) { error_log('enterprise_auth.php: ' . $e->getMessage()); }
    return '';
}
}

/**
 * Check if current user session has a specific role.
 */
if (!function_exists('hasRole')) {
function hasRole(string $roleName): bool {
    $current = strtolower(trim($_SESSION['role'] ?? ''));
    $target = strtolower(trim($roleName));
    return $current === $target || $current === str_replace(' ', '_', $target);
}
}

/**
 * Require permission — redirect to dashboard if denied.
 */
if (!function_exists('requirePermission')) {
function requirePermission($conn, int $roleId, string $permissionSlug): void {
    if (!checkEnterprisePermission($conn, $roleId, $permissionSlug)) {
        $dashboard = $_SESSION['role_dashboard'] ?? '../index.php';
        header('Location: ' . $dashboard);
        exit;
    }
}
}

/**
 * Get staff record by ID.
 */
if (!function_exists('getStaffById')) {
function getStaffById($conn, int $staffId): ?array {
    if (!$conn || $staffId <= 0) return null;
    try {
        $stmt = $conn->prepare(
            "SELECT s.*, sr.role_name, sr.role_level, sr.dashboard_path
             FROM staff s LEFT JOIN staff_roles sr ON s.role_id = sr.id
             WHERE s.id = ? LIMIT 1"
        );
        if ($stmt) {
            $stmt->bind_param('i', $staffId);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            return $row ?: null;
        }
    } catch (Exception $e) { error_log('enterprise_auth.php: ' . $e->getMessage()); }
    return null;
}
}

/**
 * Get a system setting by key (direct connection version).
 */
if (!function_exists('getSystemSettingDirect')) {
function getSystemSettingDirect($conn, string $key, $default = null) {
    if (!$conn) return $default;
    try {
        $stmt = $conn->prepare("SELECT setting_value, setting_type FROM system_settings WHERE setting_key = ?");
        if ($stmt) {
            $stmt->bind_param('s', $key);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if ($row) {
                $val = $row['setting_value'];
                $type = $row['setting_type'];
                if ($type === 'boolean') return (bool)$val;
                if ($type === 'integer') return (int)$val;
                if ($type === 'json') return json_decode($val, true);
                return $val;
            }
        }
    } catch (Exception $e) { error_log('enterprise_auth.php: ' . $e->getMessage()); }
    return $default;
}
}

/**
 * Set a system setting.
 */
if (!function_exists('setSystemSettingDirect')) {
function setSystemSettingDirect($conn, string $key, $value, string $type = 'string', string $group = 'general'): bool {
    if (!$conn) return false;
    try {
        $val = is_array($value) ? json_encode($value) : (string)$value;
        $stmt = $conn->prepare(
            "INSERT INTO system_settings (setting_key, setting_value, setting_type, setting_group, updated_by)
             VALUES (?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), setting_type = VALUES(setting_type), updated_at = NOW()"
        );
        if ($stmt) {
            $userId = (int)($_SESSION['user_id'] ?? 0);
            $stmt->bind_param('ssssi', $key, $val, $type, $group, $userId);
            $ok = $stmt->execute();
            $stmt->close();
            return $ok;
        }
    } catch (Exception $e) { error_log('enterprise_auth.php: ' . $e->getMessage()); }
    return false;
}
}

/**
 * Count unread notifications for a staff member.
 */
if (!function_exists('getUnreadNotificationCount')) {
function getUnreadNotificationCount($conn, int $staffId): int {
    if (!$conn || $staffId <= 0) return 0;
    try {
        $r = $conn->query(
            "SELECT COUNT(*) cnt FROM notifications n
             LEFT JOIN notification_reads nr ON n.id = nr.notification_id AND nr.user_id = {$staffId}
             WHERE nr.id IS NULL"
        );
        if ($r) return (int)$r->fetch_assoc()['cnt'];
    } catch (Exception $e) { error_log('enterprise_auth.php: ' . $e->getMessage()); }
    return 0;
}
}

/**
 * Count pending tasks for a staff member.
 */
if (!function_exists('getPendingTaskCount')) {
function getPendingTaskCount($conn, int $staffId): int {
    if (!$conn || $staffId <= 0) return 0;
    try {
        $stmt = $conn->prepare("SELECT COUNT(*) cnt FROM task_assignments WHERE assigned_to = ? AND status IN ('pending','in_progress')");
        if ($stmt) {
            $stmt->bind_param('i', $staffId);
            $stmt->execute();
            $cnt = (int)$stmt->get_result()->fetch_assoc()['cnt'];
            $stmt->close();
            return $cnt;
        }
    } catch (Exception $e) { error_log('enterprise_auth.php: ' . $e->getMessage()); }
    return 0;
}
}

/**
 * Count pending approval requests for Director General.
 */
if (!function_exists('getPendingApprovalCount')) {
function getPendingApprovalCount($conn): int {
    if (!$conn) return 0;
    try {
        $r = $conn->query("SELECT COUNT(*) cnt FROM approval_requests WHERE status IN ('Active','pending','in_review')");
        if ($r) return (int)$r->fetch_assoc()['cnt'];
    } catch (Exception $e) { error_log('enterprise_auth.php: ' . $e->getMessage()); }
    return 0;
}
}

/**
 * Get recent activities for dashboard.
 */
if (!function_exists('getRecentActivities')) {
function getRecentActivities($conn, int $limit = 10): array {
    if (!$conn) return [];
    $activities = [];
    try {
        $r = $conn->query(
            "SELECT activity_type, activity_description, created_at
             FROM staff_activity_log ORDER BY created_at DESC LIMIT " . (int)$limit
        );
        if ($r) while ($row = $r->fetch_assoc()) $activities[] = $row;
    } catch (Exception $e) { error_log('enterprise_auth.php: ' . $e->getMessage()); }
    return $activities;
}
}

/**
 * Log an activity to staff_activity_log.
 */
if (!function_exists('logActivity')) {
function logActivity($conn, string $activityType, string $description, int $userId = 0): void {
    if (!$conn) return;
    $uid = $userId ?: (int)($_SESSION['user_id'] ?? 0);
    try {
        $stmt = $conn->prepare(
            "INSERT INTO staff_activity_log (staff_id, activity_type, activity_description, created_at) VALUES (?, ?, ?, NOW())"
        );
        if ($stmt) {
            $stmt->bind_param('iss', $uid, $activityType, $description);
            $stmt->execute();
            $stmt->close();
        }
    } catch (Exception $e) { error_log('enterprise_auth.php: ' . $e->getMessage()); }
}
}

/**
 * Log an audit trail entry.
 */
if (!function_exists('logAuditTrail')) {
function logAuditTrail($conn, string $actionType, string $entityType, int $entityId = 0, string $description = ''): void {
    if (!$conn) return;
    $staffId = (int)($_SESSION['user_id'] ?? 0);
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
    try {
        $stmt = $conn->prepare(
            "INSERT INTO audit_trail (staff_id, action_type, entity_type, entity_id, description, ip_address, user_agent, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, NOW())"
        );
        if ($stmt) {
            $stmt->bind_param('ississs', $staffId, $actionType, $entityType, $entityId, $description, $ip, $ua);
            $stmt->execute();
            $stmt->close();
        }
    } catch (Exception $e) { error_log('enterprise_auth.php: ' . $e->getMessage()); }
}
}

/**
 * Send an in-app notification.
 */
if (!function_exists('sendNotification')) {
function sendNotification($conn, string $title, string $message, string $type = 'info', string $audience = 'all', int $createdBy = 0): bool {
    if (!$conn) return false;
    $uid = $createdBy ?: (int)($_SESSION['user_id'] ?? 0);
    try {
        $stmt = $conn->prepare(
            "INSERT INTO notifications (title, message, type, priority, audience, created_by, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())"
        );
        if ($stmt) {
            $priority = $type === 'urgent' ? 'high' : 'normal';
            $stmt->bind_param('sssssi', $title, $message, $type, $priority, $audience, $uid);
            $ok = $stmt->execute();
            $stmt->close();
            return $ok;
        }
    } catch (Exception $e) { error_log('enterprise_auth.php: ' . $e->getMessage()); }
    return false;
}
}
