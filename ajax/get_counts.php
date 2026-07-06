<?php
/**
 * AJAX endpoint: returns badge counts for sidebar (alerts, approvals).
 */
header('Content-Type: application/json');
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['type'] ?? '') !== 'staff') {
    echo json_encode(['critical_alerts' => 0, 'pending_approvals' => 0]);
    exit;
}
require_once __DIR__ . '/../config/database.php';
$conn = getStaffConnection();
$data = ['critical_alerts' => 0, 'pending_approvals' => 0];
if ($conn) {
    try {
        $r = $conn->query("SELECT COUNT(*) as c FROM igangaschoolofl_staffs_db.institutional_alerts WHERE is_resolved = 0 AND priority = 'Critical'");
        if ($r) $data['critical_alerts'] = (int)$r->fetch_assoc()['c'];
    } catch (Exception $e) { error_log('get_counts context: ' . $e->getMessage()); }
    try {
        $r = $conn->query("SELECT COUNT(*) as c FROM igangaschoolofl_staffs_db.approval_requests WHERE status = 'Active'");
        if ($r) $data['pending_approvals'] = (int)$r->fetch_assoc()['c'];
    } catch (Exception $e) { error_log('get_counts context: ' . $e->getMessage()); }
}
echo json_encode($data);
