<?php
/**
 * Role guard for admin-panel AJAX endpoints (JSON responses).
 */
$adminRole = '';
$uid = $_SESSION['uid'] ?? $_SESSION['user_id'] ?? null;
if ($uid && function_exists('getStaffConnection')) {
    $guardConn = getStaffConnection();
    if ($guardConn) {
        $guardStmt = $guardConn->prepare('SELECT LOWER(sr.role_name) AS role_name
            FROM `igangaschool_system`.`staff` s
            INNER JOIN `igangaschool_system`.`staff_roles` sr ON s.role_id = sr.id
            WHERE s.id = ? LIMIT 1');
        if ($guardStmt) {
            $guardStmt->bind_param('i', $uid);
            if ($guardStmt->execute()) {
                $guardRow = $guardStmt->get_result()->fetch_assoc();
                $adminRole = strtolower(trim($guardRow['role_name'] ?? ''));
            }
            $guardStmt->close();
        }
    }
}
$isAdmin = in_array($adminRole, ['system administrator', 'owner', 'super admin', 'director general', 'ceo', 'school principal', 'deputy principal'], true)
    || (strpos($adminRole, 'admin') !== false);
if (!$isAdmin) {
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode(['status' => 'ERROR', 'message' => 'Administrator access required']);
    exit;
}
