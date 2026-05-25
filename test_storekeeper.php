<?php
header('Content-Type: text/plain');

// Check if Store Keeper role exists and create test user if needed
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/auth-service.php';

echo "Checking Store Keeper setup...\n\n";

// Check if the role "Store Keeper" exists in staff_roles
try {
    $conn = getStaffConnection();
    
    // Check if the role "Store Keeper" exists
    $role_result = $conn->query("SELECT id, role_name, dashboard_path FROM staff_roles WHERE role_name = 'Store Keeper'");
    if ($role_result->num_rows === 0) {
        echo "Role 'Store Keeper' does not exist. Creating it...\n";
        // Insert the role with correct column name
        $insert_role = $conn->query("INSERT INTO staff_roles (role_name, role_description, dashboard_path) VALUES ('Store Keeper', 'Inventory Management', 'dashboards/storekeeper.php')");
        if ($insert_role) {
            $role_id = $conn->insert_id;
            echo "Role 'Store Keeper' created with ID: " . $role_id . "\n";
        } else {
            throw new Exception("Failed to create role: " . $conn->error);
        }
    } else {
        $role_row = $role_result->fetch_assoc();
        $role_id = $role_row['id'];
        $dashboard_path = $role_row['dashboard_path'];
        echo "Role 'Store Keeper' exists with ID: " . $role_id . "\n";
        echo "Dashboard path: " . $dashboard_path . "\n";
    }
    $role_result->free();
    
    // Check if we already have a staff user with this role
    $staff_result = $conn->query("SELECT s.id, s.email, s.full_name FROM staff s WHERE s.role_id = $role_id AND s.status = 'Active' LIMIT 1");
    if ($staff_result->num_rows === 0) {
        echo "No active staff user found for role 'Store Keeper'. Creating one...\n";
        // Create a staff user with required staff_id field
        $hashed_password = password_hash('12345678', PASSWORD_DEFAULT);
        $staff_id = 'SK' . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT); // Generate a staff ID like SK00123
        $insert_staff = $conn->query("INSERT INTO staff (staff_id, full_name, email, phone, password, role_id, position, department, status, created_at) VALUES ('$staff_id', 'Store Keeper User', 'storekeeper@isnm.ug', '0771234567', '$hashed_password', $role_id, 'Store Keeper', 'Inventory', 'Active', NOW())");
        if ($insert_staff) {
            $staff_id_db = $conn->insert_id;
            echo "Staff user created with ID: " . $staff_id_db . "\n";
            echo "Staff ID: " . $staff_id . "\n";
            echo "Email: storekeeper@isnm.ug\n";
            echo "Password: 12345678\n";
        } else {
            throw new Exception("Failed to create staff user: " . $conn->error);
        }
    } else {
        $staff_row = $staff_result->fetch_assoc();
        echo "Staff user already exists for role 'Store Keeper':\n";
        echo "  ID: " . $staff_row['id'] . "\n";
        echo "  Email: " . $staff_row['email'] . "\n";
        echo "  Name: " . $staff_row['full_name'] . "\n";
    }
    $staff_result->free();
    
    $conn->close();
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

// Now test the authentication service with the Store Keeper user
echo "\n--- Testing Store Keeper Authentication ---\n";
$auth_service = new AuthenticationService();

// Test with the email we just created/used
$email = 'storekeeper@isnm.ug';
$password = '12345678';

echo "Authenticating with email: $email and password: $password\n";
$result = $auth_service->authenticateStaff($email, $password);

if ($result['success']) {
    echo "Authentication SUCCESSFUL!\n";
    echo "User ID: " . $result['user']['id'] . "\n";
    echo "Role: " . $result['user']['role'] . "\n";
    echo "Type: " . $result['user']['type'] . "\n";
    echo "Position: " . $result['user']['position'] . "\n";
    echo "Department: " . $result['user']['department'] . "\n";
    
    // Test getting dashboard route
    $dashboard = $auth_service->getDashboardRoute($result['user']['role']);
    echo "Dashboard route: " . $dashboard . "\n";
} else {
    echo "Authentication FAILED: " . $result['message'] . "\n";
}

// Test the organogram flow: get suggested email for Store Keeper position
echo "\n--- Testing Organogram Flow for Store Keeper ---\n";
$position = 'Store Keeper';
$requested_position = $position;
$resolved_role = $auth_service->resolveOrganogramPosition($requested_position);
$suggested_email = $resolved_role ? $auth_service->getStaffEmailForRole($resolved_role) : '';

echo "Position: $requested_position\n";
echo "Resolved role: " . ($resolved_role ?: '(none)') . "\n";
echo "Suggested email: " . ($suggested_email ?: '(none)') . "\n";

if ($suggested_email) {
    echo "\nTesting authentication with suggested email...\n";
    $result2 = $auth_service->authenticateStaff($suggested_email, '12345678');
    if ($result2['success']) {
        echo "Authentication with suggested email SUCCESSFUL!\n";
        echo "Dashboard route: " . $auth_service->getDashboardRoute($result2['user']['role']) . "\n";
    } else {
        echo "Authentication with suggested email FAILED: " . $result2['message'] . "\n";
    }
} else {
    echo "No suggested email found for position.\n";
}
?>