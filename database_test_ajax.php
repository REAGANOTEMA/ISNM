<?php
// ISNM Database Test AJAX Handler
// Professional database testing functionality

header('Content-Type: application/json');
require_once 'includes/config_enhanced.php';

function jsonResponse($success, $message, $data = null) {
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    exit;
}

try {
    $test = $_GET['test'] ?? '';
    
    switch ($test) {
        case 'staffs':
            $result = DatabaseConnection::executeQuery('staffs', 'SELECT COUNT(*) as staff_count FROM staff WHERE status = "Active"');
            if ($result) {
                jsonResponse(true, 'Staffs database query successful', [
                    'staff_count' => $result[0]['staff_count'],
                    'database' => 'staffs_db',
                    'query' => 'SELECT COUNT(*) as staff_count FROM staff WHERE status = "Active"'
                ]);
            } else {
                jsonResponse(false, 'Staffs database query failed');
            }
            break;
            
        case 'students':
            $result = DatabaseConnection::executeQuery('students', 'SELECT COUNT(*) as student_count FROM students WHERE status = "Active"');
            if ($result) {
                jsonResponse(true, 'Students database query successful', [
                    'student_count' => $result[0]['student_count'],
                    'database' => 'students_db',
                    'query' => 'SELECT COUNT(*) as student_count FROM students WHERE status = "Active"'
                ]);
            } else {
                jsonResponse(false, 'Students database query failed');
            }
            break;
            
        case 'website':
            $result = DatabaseConnection::executeQuery('website', 'SELECT COUNT(*) as page_count FROM pages WHERE status = "Published"');
            if ($result) {
                jsonResponse(true, 'Website database query successful', [
                    'page_count' => $result[0]['page_count'],
                    'database' => 'website_db',
                    'query' => 'SELECT COUNT(*) as page_count FROM pages WHERE status = "Published"'
                ]);
            } else {
                jsonResponse(false, 'Website database query failed');
            }
            break;
            
        case 'cross':
            $results = [];
            $databases = ['staffs', 'students', 'website'];
            $queries = [
                'staffs' => 'SELECT COUNT(*) as count FROM staff WHERE status = "Active"',
                'students' => 'SELECT COUNT(*) as count FROM students WHERE status = "Active"',
                'website' => 'SELECT COUNT(*) as count FROM pages WHERE status = "Published"'
            ];
            
            $success = true;
            foreach ($databases as $db) {
                try {
                    $result = DatabaseConnection::executeQuery($db, $queries[$db]);
                    $results[$db] = [
                        'success' => true,
                        'count' => $result[0]['count'],
                        'query' => $queries[$db]
                    ];
                } catch (Exception $e) {
                    $results[$db] = [
                        'success' => false,
                        'error' => $e->getMessage()
                    ];
                    $success = false;
                }
            }
            
            jsonResponse($success, 'Cross-database query completed', $results);
            break;
            
        case 'connection':
            $health = checkSystemHealth();
            jsonResponse(true, 'System health check completed', $health);
            break;
            
        default:
            jsonResponse(false, 'Invalid test type specified');
    }
    
} catch (Exception $e) {
    jsonResponse(false, 'Database test error: ' . $e->getMessage());
}
?>
