<?php
/**
 * Bursar Logout
 */

require_once 'auth-service.php';
$auth_service->logout();

header('Location: staff-login.php?logout=success');
exit;
exit;
?>
