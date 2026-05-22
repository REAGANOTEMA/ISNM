<?php
/**
 * HR Logout
 */

session_start();

if (isset($_SESSION['hr_id'])) {
    $user_id = $_SESSION['hr_id'];
    
    // Log logout activity
    require_once 'config/database.php';
    try {
        $conn = getStaffConnection();
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        $agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        $stmt = $conn->prepare("
            INSERT INTO hr_activity_logs (user_id, user_name, user_role, action_type, entity_type, ip_address, user_agent, notes) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $name = $_SESSION['hr_name'];
        $role = 'hr_manager';
        $action = 'LOGOUT';
        $entity_type = 'hr_users';
        $notes = 'User logged out';
        
        $stmt->bind_param('isssssss', $user_id, $name, $role, $action, $entity_type, $ip, $agent, $notes);
        $stmt->execute();
        $stmt->close();
        $conn->close();
    } catch (Exception $e) {
        error_log('Logout error: ' . $e->getMessage());
    }
    
    // Destroy session
    session_destroy();
}

header('Location: staff-login.php?logout=success');
exit;
?>
