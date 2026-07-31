<?php
session_start();
include_once __DIR__ . '/../config/database.php';
include_once 'functions.php';
include_once 'auth_functions.php';

// Check if user is logged in and is a student
checkAuth('Student');

header('Content-Type: application/json');

try {
    // Get JSON data
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['action'])) {
        throw new Exception('Invalid request');
    }
    
    $token = $input['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (empty($token) || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        throw new Exception('Invalid security token');
    }
    
    $action = sanitizeInput($input['action']);
    $student_id = $_SESSION['user_id'];
    
    switch ($action) {
        case 'request_bus':
            $studentsConn = getStudentsConnection();
            if (!$studentsConn) throw new Exception('Database connection failed');

            // Update student bus request status
            $update_sql = "UPDATE students SET bus_request_status = 'pending', bus_request_date = NOW() WHERE id = ?";
            $stmt = $studentsConn->prepare($update_sql);
            $stmt->bind_param("s", $student_id);
            
            if ($stmt->execute()) {
                // Log the activity
                logActivity($student_id, 'Student', 'Bus Request', 'Student applied for bus service', 'students', $student_id);
                
                // Create notification for administrators
                $staffConn = getStaffConnection();
                if ($staffConn) {
                    $fullName = ($_SESSION['first_name'] ?? '') . ' ' . ($_SESSION['last_name'] ?? '');
                    $notification_sql = "INSERT INTO notifications (user_id, notification_type, title, message) 
                                       SELECT id, 'bus_request', 'New Bus Request', 
                                       CONCAT('Student ', ?, ' has applied for bus service') 
                                       FROM staff WHERE role IN ('School Principal', 'School Secretary', 'Academic Registrar')";
                    $notif_stmt = $staffConn->prepare($notification_sql);
                    $notif_stmt->bind_param("s", $fullName);
                    if (!$notif_stmt->execute()) { error_log('$notif_stmt execute failed: ' . ($notif_stmt->error ?? 'unknown')); };
                    $notif_stmt->close();
                }
                
                echo json_encode(['success' => true, 'message' => 'Bus service request submitted successfully']);
            } else {
                throw new Exception('Failed to submit bus request');
            }
            $stmt->close();
            break;
            
        default:
            throw new Exception('Unknown action');
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
