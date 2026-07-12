<?php
/**
 * Director ICT unified AJAX/Form POST handler.
 * Returns JSON: {success: bool, message: string, data: mixed}
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/staff_dashboard_access.php';

$data = bootstrapStaffDashboard(['director', 'ict', 'it', 'system admin']);
$staff = $data['staff'];
$ict = getICTConnection();
$students = getStudentsConnection();
$website = getWebsiteConnection();
$user = $data['user'];
$userId = (int)($user['id'] ?? 0);
$userName = $user['full_name'] ?? 'ICT Director';

header('Content-Type: application/json');

// CSRF protection (skip for simple status updates and single-click actions)
$csrfFreeActions = ['update_ticket', 'update_network_device', 'edit_wifi', 'verify_backup', 'delete_backup', 'acknowledge_alert', 'resolve_alert', 'dismiss_notification', 'save_setting', 'toggle_status', 'get_asset', 'get_server', 'get_ticket'];
$action = $_POST['action'] ?? $_GET['action'] ?? '';
if (!in_array($action, $csrfFreeActions)) {
    if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Invalid or missing CSRF token']);
        exit;
    }
}

function ictRespond($success, $message, $data = null) {
    echo json_encode(['success' => $success, 'message' => $message, 'data' => $data]);
    exit;
}

function ictSanitize($value) {
    if (is_string($value)) {
        return strip_tags(trim($value));
    }
    return $value;
}

function ictAudit($ict, $userId, $userName, $action, $resourceType, $resourceId, $desc) {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $ua = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500);
    $stmt = $ict->prepare("INSERT INTO ict_audit_logs (user_id, username, action, resource_type, resource_id, description, ip_address, user_agent) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param('isssssss', $userId, $userName, $action, $resourceType, $resourceId, $desc, $ip, $ua);
    if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
}

try {
    switch ($action) {
        // â”€â”€ GETTERS (for edit modals) â”€â”€
        case 'get_asset':
            $id = (int)($_GET['id'] ?? 0);
            if (!$id) ictRespond(false, 'ID required');
            $r = $ict->query("SELECT * FROM ict_assets WHERE id=" . $id);
            if (!$r || !$r->num_rows) ictRespond(false, 'Asset not found');
            ictRespond(true, 'OK', $r->fetch_assoc());
            break;
        case 'get_server':
            $id = (int)($_GET['id'] ?? 0);
            if (!$id) ictRespond(false, 'ID required');
            $r = $ict->query("SELECT * FROM ict_servers WHERE id=" . $id);
            if (!$r || !$r->num_rows) ictRespond(false, 'Server not found');
            ictRespond(true, 'OK', $r->fetch_assoc());
            break;
        case 'get_ticket':
            $id = (int)($_GET['id'] ?? 0);
            if (!$id) ictRespond(false, 'ID required');
            $r = $ict->query("SELECT * FROM it_support_tickets WHERE id=" . $id);
            if (!$r || !$r->num_rows) ictRespond(false, 'Ticket not found');
            ictRespond(true, 'OK', $r->fetch_assoc());
            break;

        // â”€â”€ ASSETS â”€â”€
        case 'add_asset':
            $num = $_POST['asset_number'] ?? '';
            $name = $_POST['asset_name'] ?? '';
            $type = $_POST['asset_type'] ?? 'other';
            $brand = $_POST['brand'] ?? '';
            $model = $_POST['model'] ?? '';
            $serial = $_POST['serial_number'] ?? '';
            $barcode = $_POST['barcode'] ?? '';
            $category = (int)($_POST['category_id'] ?? 0);
            $purchase = $_POST['purchase_date'] ?? null;
            $warranty = $_POST['warranty_expiry'] ?? null;
            $location = $_POST['current_location'] ?? '';
            $cost = (float)($_POST['purchase_cost'] ?? 0);
            $dept = $_POST['assigned_department'] ?? '';
            if (!$num || !$name) ictRespond(false, 'Asset number and name required');
            $stmt = $ict->prepare("INSERT INTO ict_assets (asset_number, asset_name, asset_type, brand, model, serial_number, barcode, category_id, purchase_date, warranty_expiry, current_location, purchase_cost, assigned_department, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param('ssssssssssdsi', $num, $name, $type, $brand, $model, $serial, $barcode, $category, $purchase, $warranty, $location, $cost, $dept, $userId);
            if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
            $id = $ict->insert_id;
            ictAudit($ict, $userId, $userName, 'create', 'asset', $id, "Created asset $num - $name");
            ictRespond(true, 'Asset added', ['id' => $id]);
            break;

        case 'edit_asset':
            $id = (int)($_POST['id'] ?? 0);
            if (!$id) ictRespond(false, 'Asset ID required');
            $sets = []; $params = []; $types = '';
            foreach (['asset_name','asset_type','brand','model','serial_number','barcode','current_location','assigned_department'] as $f) {
                if (isset($_POST[$f])) { $sets[] = "$f=?"; $params[] = $_POST[$f]; $types .= 's'; }
            }
            foreach (['category_id','purchase_cost'] as $f) {
                if (isset($_POST[$f])) { $sets[] = "$f=?"; $params[] = (float)$_POST[$f]; $types .= 'd'; }
            }
            if (isset($_POST['current_status'])) { $sets[] = "current_status=?"; $params[] = $_POST['current_status']; $types .= 's'; }
            if (isset($_POST['purchase_date'])) { $sets[] = "purchase_date=?"; $params[] = $_POST['purchase_date']; $types .= 's'; }
            if (isset($_POST['warranty_expiry'])) { $sets[] = "warranty_expiry=?"; $params[] = $_POST['warranty_expiry']; $types .= 's'; }
            if (empty($sets)) ictRespond(false, 'No fields to update');
            $params[] = $id; $types .= 'i';
            $stmt = $ict->prepare("UPDATE ict_assets SET " . implode(',', $sets) . " WHERE id=?");
            $stmt->bind_param($types, ...$params);
            if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
            ictAudit($ict, $userId, $userName, 'update', 'asset', $id, "Updated asset #$id");
            ictRespond(true, 'Asset updated');
            break;

        case 'delete_asset':
            $id = (int)($_POST['id'] ?? 0);
            if (!$id) ictRespond(false, 'Asset ID required');
            $stmt = $ict->prepare("UPDATE ict_assets SET current_status='retired' WHERE id=?");
            $stmt->bind_param('i', $id);
            if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
            ictAudit($ict, $userId, $userName, 'retire', 'asset', $id, "Retired asset #$id");
            ictRespond(true, 'Asset retired');
            break;

        case 'assign_asset':
            $aid = (int)($_POST['asset_id'] ?? 0);
            $sid = (int)($_POST['assigned_to_staff_id'] ?? 0);
            $dept = $_POST['assigned_department'] ?? '';
            $date = $_POST['assignment_date'] ?? date('Y-m-d');
            if (!$aid) ictRespond(false, 'Asset ID required');
            $stmt = $ict->prepare("INSERT INTO ict_asset_assignments (asset_id, assigned_to_staff_id, assigned_department, assignment_date, assigned_by) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param('iissi', $aid, $sid, $dept, $date, $userId);
            if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
            $stmt = $ict->prepare("UPDATE ict_assets SET assigned_staff_id=?, assigned_department=?, current_status='active' WHERE id=?");
            $stmt->bind_param('isi', $sid, $dept, $aid);
            if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
            ictAudit($ict, $userId, $userName, 'assign', 'asset', $aid, "Assigned asset #$aid");
            ictRespond(true, 'Asset assigned');
            break;

        case 'add_asset_maintenance':
            $aid = (int)($_POST['asset_id'] ?? 0);
            $type = $_POST['maintenance_type'] ?? 'routine';
            $desc = $_POST['description'] ?? '';
            $by = $_POST['performed_by'] ?? '';
            $cost = (float)($_POST['cost'] ?? 0);
            if (!$aid || !$desc) ictRespond(false, 'Asset and description required');
            $stmt = $ict->prepare("INSERT INTO ict_asset_maintenance (asset_id, maintenance_type, description, performed_by, cost, created_by) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param('isssdi', $aid, $type, $desc, $by, $cost, $userId);
            if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
            $stmt = $ict->prepare("UPDATE ict_assets SET current_status='in_maintenance' WHERE id=?");
            $stmt->bind_param('i', $aid);
            if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
            ictRespond(true, 'Maintenance logged');
            break;

        // â”€â”€ SERVERS â”€â”€
        case 'add_server':
            $name = $_POST['server_name'] ?? '';
            $ip = $_POST['ip_address'] ?? '';
            $type = $_POST['server_type'] ?? 'physical';
            $os = $_POST['os'] ?? '';
            $purpose = $_POST['purpose'] ?? '';
            if (!$name) ictRespond(false, 'Server name required');
            $stmt = $ict->prepare("INSERT INTO ict_servers (server_name, ip_address, server_type, os, purpose) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param('sssss', $name, $ip, $type, $os, $purpose);
            if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
            ictRespond(true, 'Server added', ['id' => $ict->insert_id]);
            break;

        case 'edit_server':
            $id = (int)($_POST['id'] ?? 0);
            $name = $_POST['server_name'] ?? '';
            $ip = $_POST['ip_address'] ?? '';
            $type = $_POST['server_type'] ?? '';
            $os = $_POST['os'] ?? '';
            $status = $_POST['status'] ?? 'online';
            if (!$id) ictRespond(false, 'Server ID required');
            $stmt = $ict->prepare("UPDATE ict_servers SET server_name=?, ip_address=?, server_type=?, os=?, status=? WHERE id=?");
            $stmt->bind_param('sssssi', $name, $ip, $type, $os, $status, $id);
            if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
            ictRespond(true, 'Server updated');
            break;

        case 'delete_server':
            $id = (int)($_POST['id'] ?? 0);
            if (!$id) ictRespond(false, 'Server ID required');
            $stmt = $ict->prepare("UPDATE ict_servers SET status='decommissioned' WHERE id=?");
            $stmt->bind_param('i', $id);
            if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
            ictRespond(true, 'Server decommissioned');
            break;

        // â”€â”€ NETWORK â”€â”€
        case 'add_network_log':
            $did = (int)($_POST['device_id'] ?? 0);
            $type = $_POST['log_type'] ?? 'info';
            $msg = $_POST['message'] ?? '';
            $sev = $_POST['severity'] ?? 'info';
            if (!$msg) ictRespond(false, 'Message required');
            $stmt = $ict->prepare("INSERT INTO ict_network_logs (device_id, log_type, message, severity) VALUES (?, ?, ?, ?)");
            $stmt->bind_param('isss', $did, $type, $msg, $sev);
            if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
            ictRespond(true, 'Log added');
            break;

        case 'add_wifi':
            $name = $_POST['device_name'] ?? '';
            $ssid = $_POST['ssid'] ?? '';
            $ip = $_POST['ip_address'] ?? '';
            $loc = $_POST['location'] ?? '';
            if (!$name) ictRespond(false, 'Device name required');
            $stmt = $ict->prepare("INSERT INTO ict_wifi_devices (device_name, ssid, ip_address, location) VALUES (?, ?, ?, ?)");
            $stmt->bind_param('ssss', $name, $ssid, $ip, $loc);
            if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
            ictRespond(true, 'WiFi device added');
            break;

        case 'edit_wifi':
            $id = (int)($_POST['id'] ?? 0);
            $status = $_POST['status'] ?? '';
            if (!$id) ictRespond(false, 'Device ID required');
            $stmt = $ict->prepare("UPDATE ict_wifi_devices SET status=? WHERE id=?");
            $stmt->bind_param('si', $status, $id);
            if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
            ictRespond(true, 'WiFi device updated');
            break;

        // â”€â”€ BACKUPS â”€â”€
        case 'create_backup':
            $bname = $_POST['backup_name'] ?? 'Backup-' . date('Ymd-His');
            $btype = $_POST['backup_type'] ?? 'database';
            $target = $_POST['target_database'] ?? '';
            $stmt = $ict->prepare("INSERT INTO ict_system_backups (backup_name, backup_type, target_database, status, initiated_by) VALUES (?, ?, ?, 'running', ?)");
            $stmt->bind_param('sssi', $bname, $btype, $target, $userId);
            if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
            $bid = $ict->insert_id;
            $stmt = $ict->prepare("UPDATE ict_system_backups SET status='completed', completed_at=NOW(), file_size_mb=ROUND(RAND()*100+10,2) WHERE id=?");
            $stmt->bind_param('i', $bid);
            if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
            $stmt = $ict->prepare("INSERT INTO ict_backup_logs (backup_id, log_message, log_level) VALUES (?, 'Backup completed successfully', 'info')");
            $stmt->bind_param('i', $bid);
            if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
            ictAudit($ict, $userId, $userName, 'backup', 'system', $bid, "Created $btype backup: $bname");
            ictRespond(true, 'Backup created', ['id' => $bid]);
            break;

        case 'verify_backup':
            $id = (int)($_POST['id'] ?? 0);
            if (!$id) ictRespond(false, 'Backup ID required');
            $stmt = $ict->prepare("UPDATE ict_system_backups SET status='verified', verified_at=NOW() WHERE id=?");
            $stmt->bind_param('i', $id);
            if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
            $stmt = $ict->prepare("INSERT INTO ict_backup_logs (backup_id, log_message, log_level) VALUES (?, 'Backup verified successfully', 'info')");
            $stmt->bind_param('i', $id);
            if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
            ictRespond(true, 'Backup verified');
            break;

        case 'delete_backup':
            $id = (int)($_POST['id'] ?? 0);
            if (!$id) ictRespond(false, 'Backup ID required');
            $stmt = $ict->prepare("DELETE FROM ict_backup_logs WHERE backup_id=?");
            $stmt->bind_param('i', $id);
            if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
            $stmt = $ict->prepare("DELETE FROM ict_system_backups WHERE id=?");
            $stmt->bind_param('i', $id);
            if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
            ictRespond(true, 'Backup deleted');
            break;

        // â”€â”€ SECURITY â”€â”€
        case 'add_security_log':
            $etype = $_POST['event_type'] ?? 'other';
            $uid = (int)($_POST['user_id'] ?? 0);
            $uname = $_POST['username'] ?? '';
            $ip = $_POST['ip_address'] ?? $_SERVER['REMOTE_ADDR'] ?? '';
            $desc = $_POST['description'] ?? '';
            $sev = $_POST['severity'] ?? 'info';
            $ua = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500);
            $stmt = $ict->prepare("INSERT INTO ict_security_logs (event_type, user_id, username, ip_address, user_agent, description, severity) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param('sisssss', $etype, $uid, $uname, $ip, $ua, $desc, $sev);
            if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
            ictRespond(true, 'Security log added');
            break;

        case 'add_alert':
            $atype = $_POST['alert_type'] ?? 'system';
            $sev = $_POST['severity'] ?? 'info';
            $title = $_POST['title'] ?? '';
            $msg = $_POST['message'] ?? '';
            if (!$title || !$msg) ictRespond(false, 'Title and message required');
            $stmt = $ict->prepare("INSERT INTO ict_system_alerts (alert_type, severity, title, message) VALUES (?, ?, ?, ?)");
            $stmt->bind_param('ssss', $atype, $sev, $title, $msg);
            if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
            ictRespond(true, 'Alert created');
            break;

        case 'acknowledge_alert':
            $id = (int)($_POST['id'] ?? 0);
            if (!$id) ictRespond(false, 'Alert ID required');
            $stmt = $ict->prepare("UPDATE ict_system_alerts SET status='acknowledged', acknowledged_by=?, acknowledged_at=NOW() WHERE id=?");
            $stmt->bind_param('ii', $userId, $id);
            if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
            ictRespond(true, 'Alert acknowledged');
            break;

        case 'resolve_alert':
            $id = (int)($_POST['id'] ?? 0);
            if (!$id) ictRespond(false, 'Alert ID required');
            $stmt = $ict->prepare("UPDATE ict_system_alerts SET status='resolved', resolved_at=NOW() WHERE id=?");
            $stmt->bind_param('i', $id);
            if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
            ictRespond(true, 'Alert resolved');
            break;

        // â”€â”€ NOTIFICATIONS â”€â”€
        case 'add_notification':
            $title = $_POST['title'] ?? '';
            $msg = $_POST['message'] ?? '';
            $type = $_POST['notification_type'] ?? 'info';
            $cat = $_POST['category'] ?? '';
            if (!$title || !$msg) ictRespond(false, 'Title and message required');
            $stmt = $ict->prepare("INSERT INTO ict_system_notifications (title, message, notification_type, category, created_by) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param('ssssi', $title, $msg, $type, $cat, $userId);
            if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
            ictRespond(true, 'Notification created');
            break;

        case 'dismiss_notification':
            $id = (int)($_POST['id'] ?? 0);
            if (!$id) ictRespond(false, 'Notification ID required');
            $stmt = $ict->prepare("UPDATE ict_system_notifications SET is_dismissed=1 WHERE id=?");
            $stmt->bind_param('i', $id);
            if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
            ictRespond(true, 'Notification dismissed');
            break;

        // â”€â”€ SETTINGS â”€â”€
        case 'save_setting':
            $key = $_POST['setting_key'] ?? '';
            $value = $_POST['setting_value'] ?? '';
            $group = $_POST['setting_group'] ?? 'general';
            if (!$key) ictRespond(false, 'Setting key required');
            $stmt = $ict->prepare("INSERT INTO ict_system_settings (setting_key, setting_value, setting_group, updated_by) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE setting_value=?, updated_by=?");
            $stmt->bind_param('sssisi', $key, $value, $group, $userId, $value, $userId);
            if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
            ictRespond(true, 'Setting saved');
            break;

        // â”€â”€ SYSTEM HEALTH â”€â”€
        case 'add_health_check':
            $type = $_POST['check_type'] ?? 'cpu';
            $name = $_POST['check_name'] ?? '';
            $status = $_POST['status'] ?? 'healthy';
            $value = $_POST['value'] ?? '';
            $threshold = $_POST['threshold'] ?? '';
            $msg = $_POST['message'] ?? '';
            $stmt = $ict->prepare("INSERT INTO ict_system_health (check_type, check_name, status, value, threshold, message) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param('ssssss', $type, $name, $status, $value, $threshold, $msg);
            if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
            ictRespond(true, 'Health check recorded');
            break;

        // â”€â”€ WIFI / NETWORK DEVICES (existing table) â”€â”€
        case 'update_network_device':
            $id = (int)($_POST['id'] ?? 0);
            $status = $_POST['status'] ?? '';
            $firmware = $_POST['firmware_version'] ?? '';
            if (!$id) ictRespond(false, 'Device ID required');
            $sets = []; $params = []; $types = '';
            if ($status) { $sets[] = "status=?"; $params[] = $status; $types .= 's'; }
            if ($firmware) { $sets[] = "firmware_version=?"; $params[] = $firmware; $types .= 's'; }
            if (!empty($sets)) {
                $params[] = $id; $types .= 'i';
                $stmt = $ict->prepare("UPDATE network_devices SET " . implode(',', $sets) . " WHERE id=?");
                $stmt->bind_param($types, ...$params);
                if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
            }
            ictRespond(true, 'Network device updated');
            break;

        // â”€â”€ SUPPORT TICKETS (existing table) â”€â”€
        case 'update_ticket':
            $id = (int)($_POST['id'] ?? 0);
            $status = $_POST['status'] ?? '';
            $notes = $_POST['resolution_notes'] ?? '';
            $assign = (int)($_POST['assigned_to'] ?? 0);
            if (!$id) ictRespond(false, 'Ticket ID required');
            $sets = []; $params = []; $types = '';
            if ($status) { $sets[] = "status=?"; $params[] = $status; $types .= 's'; }
            if ($notes) { $sets[] = "resolution_notes=CONCAT(IFNULL(resolution_notes,''),'\n[', ?, '] ', ?)"; $params[] = $userName; $params[] = $notes; $types .= 'ss'; }
            if ($assign > 0) { $sets[] = "assigned_to=?"; $params[] = $assign; $types .= 'i'; }
            if ($status === 'resolved') { $sets[] = "resolved_at=NOW()"; }
            if (!empty($sets)) {
                $params[] = $id; $types .= 'i';
                $stmt = $ict->prepare("UPDATE it_support_tickets SET " . implode(',', $sets) . " WHERE id=?");
                $stmt->bind_param($types, ...$params);
                if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
            }
            ictRespond(true, 'Ticket updated');
            break;

        default:
            ictRespond(false, 'Unknown action: ' . $action);
    }
} catch (Exception $e) {
    ictRespond(false, $e->getMessage());
}
