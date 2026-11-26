<?php
/**
 * Role guard for owner-panel AJAX endpoints.
 * Returns a JSON 403 instead of redirecting so fetch callers receive a clear error.
 */
$ownerRole = '';
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
                $ownerRole = strtolower(trim($guardRow['role_name'] ?? ''));
            }
            $guardStmt->close();
        }
    }
}
$isOwner = in_array($ownerRole, ['system administrator', 'owner', 'super admin', 'director general', 'ceo'], true)
    || (strpos($ownerRole, 'admin') !== false);
if (!$isOwner) {
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode(['status' => 'ERROR', 'message' => 'Administrator access required']);
    exit;
}
