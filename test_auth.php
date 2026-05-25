<?php
require_once 'C:\xampp\htdocs\ISNM\auth-service.php';
$auth_service = new AuthenticationService();

// Test with the default Storekeeper credentials
$result = $auth_service->authenticateStaff('storekeeper@isnm.ug', '12345678');
if ($result['success']) {
    echo 'Authentication successful!';
    echo 'User: ' . $result['user']['full_name'] . '\\n';
    echo 'Role: ' . $result['user']['role'] . '\\n';
    echo 'Dashboard: ' . $auth_service->getDashboardRoute($result['user']['role']) . "\\n";
} else {
    echo 'Authentication failed: ' . $result['message'] . "\\n";
}
?>