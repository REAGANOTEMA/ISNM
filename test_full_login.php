<?php
header('Content-Type: text/plain');

// Test the full staff login process
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/auth-service.php';

$auth_service = new AuthenticationService();

// First, let's check if we have any staff users in the database
try {
    $conn = getStaffConnection(); // Use the global function
    $result = $conn->query("SELECT s.id, s.email, s.full_name, sr.role_name FROM staff s LEFT JOIN staff_roles sr ON s.role_id = sr.id WHERE s.status = 'Active' LIMIT 5");
    echo "Existing staff users:\n";
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            echo " - ID: " . $row['id'] . ", Email: " . $row['email'] . ", Name: " . $row['full_name'] . ", Role: " . $row['role_name'] . "\n";
        }
    } else {
        echo "  No active staff users found.\n";
    }
    $result->free();
    $conn->close();
} catch (Exception $e) {
    echo "Error checking staff users: " . $e->getMessage() . "\n";
}

// Now, let's create a test staff user for the role "Director General" if it doesn't exist
// We'll need to check if the role exists in staff_roles, and if not, create it.
// Then create a staff user with that role.

try {
    $conn = getStaffConnection();
    
    // Check if the role "Director General" exists
    $role_result = $conn->query("SELECT id FROM staff_roles WHERE role_name = 'Director General'");
    if ($role_result->num_rows === 0) {
        echo "Role 'Director General' does not exist. Creating it...\n";
        // Insert the role
        $insert_role = $conn->query("INSERT INTO staff_roles (role_name, dashboard_path, description) VALUES ('Director General', 'dashboards/director-general.php', 'Overall Institution Leadership')");
        if ($insert_role) {
            $role_id = $conn->insert_id;
            echo "Role created with ID: " . $role_id . "\n";
        } else {
            throw new Exception("Failed to create role: " . $conn->error);
        }
    } else {
        $role_row = $role_result->fetch_assoc();
        $role_id = $role_row['id'];
        echo "Role 'Director General' exists with ID: " . $role_id . "\n";
    }
    $role_result->free();
    
    // Check if we already have a staff user with this role
    $staff_result = $conn->query("SELECT s.id FROM staff s WHERE s.role_id = $role_id AND s.status = 'Active' LIMIT 1");
    if ($staff_result->num_rows === 0) {
        echo "No active staff user found for role 'Director General'. Creating one...\n";
        // Create a staff user
        $hashed_password = password_hash('12345678', PASSWORD_DEFAULT);
        $insert_staff = $conn->query("INSERT INTO staff (full_name, email, phone, password, role_id, position, department, status, created_at) VALUES ('Test Director General', 'director@isnm.ug', '0771234567', '$hashed_password', $role_id, 'Director General', 'Administration', 'Active', NOW())");
        if ($insert_staff) {
            $staff_id = $conn->insert_id;
            echo "Staff user created with ID: " . $staff_id . "\n";
        } else {
            throw new Exception("Failed to create staff user: " . $conn->error);
        }
    } else {
        $staff_row = $staff_result->fetch_assoc();
        echo "Staff user already exists for role 'Director General' with ID: " . $staff_row['id'] . "\n";
    }
    $staff_result->free();
    
    $conn->close();
} catch (Exception $e) {
    echo "Error setting up test staff user: " . $e->getMessage() . "\n";
}

// Now test the authentication service with the created user
echo "\n--- Testing Authentication ---\n";
$auth_service = new AuthenticationService();

// Test with the email we just created
$email = 'director@isnm.ug';
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
} else {
    echo "Authentication FAILED: " . $result['message'] . "\n";
}

// Test the organogram flow: get suggested email for a position
echo "\n--- Testing Organogram Flow ---\n";
$position = 'Director General';
$requested_position = $position;
$resolved_role = $auth_service->resolveOrganogramPosition($requested_position);
$suggested_email = $resolved_role ? $auth_service->getStaffEmailForRole($resolved_role) : '';

echo "Position: $requested_position\n";
echo "Resolved role: $resolved_role\n";
echo "Suggested email: " . ($suggested_email ?: '(none)') . "\n";

if ($suggested_email) {
    echo "\nTesting authentication with suggested email...\n";
    $result2 = $auth_service->authenticateStaff($suggested_email, '12345678');
    if ($result2['success']) {
        echo "Authentication with suggested email SUCCESSFUL!\n";
    } else {
        echo "Authentication with suggested email FAILED: " . $result2['message'] . "\n";
    }
} else {
    echo "No suggested email found for position. This is expected if no staff user exists for that role.\n";
}
?>