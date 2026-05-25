<?php
// Test the staff login flow with a sample position
$_GET['position'] = 'Director General';

// Include the necessary files
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/auth-service.php';

$auth_service = new AuthenticationService();

$requested_position = isset($_GET['position']) ? urldecode($_GET['position']) : '';
$resolved_role = $requested_position ? $auth_service->resolveOrganogramPosition($requested_position) : '';
$suggested_email = $resolved_role ? $auth_service->getStaffEmailForRole($resolved_role) : '';

echo "Position: " . htmlspecialchars($requested_position) . "\n";
echo "Resolved role: " . htmlspecialchars($resolved_role) . "\n";
echo "Suggested email: " . htmlspecialchars($suggested_email) . "\n";

if ($suggested_email) {
    echo "\nTesting authentication with suggested email and default password...\n";
    $result = $auth_service->authenticateStaff($suggested_email, '12345678');
    if ($result['success']) {
        echo "Authentication SUCCESSFUL!\n";
        echo "User ID: " . $result['user']['id'] . "\n";
        echo "Role: " . $result['user']['role'] . "\n";
        echo "Type: " . $result['user']['type'] . "\n";
    } else {
        echo "Authentication FAILED: " . $result['message'] . "\n";
    }
} else {
    echo "No suggested email found for position.\n";
}
?>