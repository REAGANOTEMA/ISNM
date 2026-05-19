<?php
// Test database connection and check for director staff
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'auth-service.php';

// Test connection
$auth_service = new AuthenticationService();
$connections = $auth_service->testConnections();

echo "Database Connections:<br>";
echo "Staffs DB: " . ($connections['staffs_db'] ? 'OK' : 'FAILED') . "<br>";
echo "Students DB: " . ($connections['students_db'] ? 'OK' : 'FAILED') . "<br>";
echo "Website DB: " . ($connections['website_db'] ? 'OK' : 'FAILED') . "<br><br>";

// Check for staff with director role
try {
    $stmt = $auth_service->getStaffsConnection()->prepare("
        SELECT s.*, sr.role_name 
        FROM staff s 
        JOIN staff_roles sr ON s.role_id = sr.id 
        WHERE sr.role_name LIKE ?
    ");
    $search_term = '%Director%';
    $stmt->bind_param("s", $search_term);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        echo "Found " . $result->num_rows . " staff member(s) with director role:<br>";
        while ($staff = $result->fetch_assoc()) {
            echo "- ID: " . $staff['id'] . 
                 ", Email: " . $staff['email'] . 
                 ", Role: " . $staff['role_name'] . 
                 ", Position: " . $staff['position'] . "<br>";
        }
    } else {
        echo "No staff members found with director role.<br>";
        
        // Let's see what roles are available
        $stmt2 = $auth_service->getStaffsConnection()->prepare("
            SELECT DISTINCT sr.role_name 
            FROM staff s 
            JOIN staff_roles sr ON s.role_id = sr.id
            LIMIT 10
        ");
        $stmt2->execute();
        $result2 = $stmt2->get_result();
        
        echo "Available roles:<br>";
        while ($role = $result2->fetch_assoc()) {
            echo "- " . $role['role_name'] . "<br>";
        }
    }
    
    $stmt->close();
} catch (Exception $e) {
    echo "Error checking staff: " . $e->getMessage();
}
?>