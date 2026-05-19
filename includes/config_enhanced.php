<?php
// ISNM Enhanced Configuration with Multi-Database Support
// Supports staffs_db, students_db, and website_db

// Database configuration
$config = [
    'host' => 'localhost',
    'username' => 'root',
    'password' => 'ReagaN23#',
    'charset' => 'utf8mb4',
    'databases' => [
        'staffs' => 'staffs_db',
        'students' => 'students_db',
        'website' => 'website_db'
    ]
];

// Include unified database connection system
require_once 'database_connections.php';

// Session configuration - ensure session is started only once
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Enhanced error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/php_errors.log');

// Set timezone
date_default_timezone_set('Africa/Kampala');

// Initialize database connections
try {
    // Test all connections
    $connection_tests = DatabaseConnection::testAllConnections();
    
    foreach ($connection_tests as $db => $status) {
        if (!$status) {
            error_log("Warning: Failed to connect to {$db} database");
        }
    }
    
} catch (Exception $e) {
    error_log("Database initialization error: " . $e->getMessage());
}

// Enhanced global functions with database selection
if (!function_exists('executeQuery')) {
    function executeQuery($database, $sql, $params = [], $types = '') {
        return DatabaseConnection::executeQuery($database, $sql, $params, $types);
    }
}

if (!function_exists('executeInsert')) {
    function executeInsert($database, $sql, $params = [], $types = '') {
        return DatabaseConnection::executeInsert($database, $sql, $params, $types);
    }
}

if (!function_exists('executeUpdate')) {
    function executeUpdate($database, $sql, $params = [], $types = '') {
        return DatabaseConnection::executeUpdate($database, $sql, $params, $types);
    }
}

if (!function_exists('sanitizeInput')) {
    function sanitizeInput($input) {
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('escapeString')) {
    function escapeString($database, $string) {
        return DatabaseConnection::escapeString($database, $string);
    }
}

// Enhanced utility functions
if (!function_exists('getDatabaseConnection')) {
    function getDatabaseConnection($database) {
        switch ($database) {
            case 'staffs':
                return DatabaseConnection::getStaffConnection();
            case 'students':
                return DatabaseConnection::getStudentsConnection();
            case 'website':
                return DatabaseConnection::getWebsiteConnection();
            default:
                throw new Exception("Unknown database: {$database}");
        }
    }
}

if (!function_exists('beginTransaction')) {
    function beginTransaction($database) {
        return DatabaseConnection::beginTransaction($database);
    }
}

if (!function_exists('commitTransaction')) {
    function commitTransaction($database) {
        return DatabaseConnection::commitTransaction($database);
    }
}

if (!function_exists('rollbackTransaction')) {
    function rollbackTransaction($database) {
        return DatabaseConnection::rollbackTransaction($database);
    }
}

if (!function_exists('executeCrossDatabaseQuery')) {
    // Cross-database query function
    function executeCrossDatabaseQuery($sql, $params = []) {
        $results = [];
        
        try {
            // Execute query on all databases
            $databases = ['staffs', 'students', 'website'];
            
            foreach ($databases as $db) {
                $results[$db] = DatabaseConnection::executeQuery($db, $sql, $params);
            }
            
        } catch (Exception $e) {
            error_log("Cross-database query error: " . $e->getMessage());
        }
        
        return $results;
    }
}

if (!function_exists('checkSystemHealth')) {
    // System health check function
    function checkSystemHealth() {
        $health = [
            'timestamp' => date('Y-m-d H:i:s'),
            'databases' => [],
            'overall_status' => 'healthy'
        ];
        
        $databases = ['staffs', 'students', 'website'];
        
        foreach ($databases as $db) {
            try {
                $connection = getDatabaseConnection($db);
                $health['databases'][$db] = [
                    'connected' => $connection->ping(),
                    'server_info' => $connection->get_server_info(),
                    'client_info' => $connection->get_client_info(),
                    'error' => null
                ];
            } catch (Exception $e) {
                $health['databases'][$db] = [
                    'connected' => false,
                    'error' => $e->getMessage()
                ];
                $health['overall_status'] = 'degraded';
            }
        }
        
        return $health;
    }
}

if (!function_exists('logSystemActivity')) {
    // Enhanced logging function
    function logSystemActivity($activity, $details = '', $database = 'system') {
        try {
            $sql = "INSERT INTO system_logs (log_type, log_level, log_message, context_data, created_at) VALUES (?, 'info', ?, ?, NOW())";
            $params = [$activity, $details];
            
            if ($database !== 'system') {
                $sql = "INSERT INTO activity_log (user_id, activity, details, created_at) VALUES (?, ?, ?, NOW())";
                $params = [$_SESSION['user_id'] ?? 0, $activity, $details];
            }
            
            DatabaseConnection::executeInsert($database, $sql, $params);
            
        } catch (Exception $e) {
            error_log("Logging error: " . $e->getMessage());
        }
    }
}

if (!function_exists('getCacheData')) {
    // Cache management function
    function getCacheData($key, $database = 'staffs') {
        try {
            $sql = "SELECT cache_data FROM cache_management WHERE cache_key = ? AND expiry_time > NOW()";
            $result = DatabaseConnection::executeQuery($database, $sql, [$key]);
            
            if ($result && count($result) > 0) {
                return json_decode($result[0]['cache_data'], true);
            }
            
            return null;
            
        } catch (Exception $e) {
            error_log("Cache retrieval error: " . $e->getMessage());
            return null;
        }
    }
}

if (!function_exists('setCacheData')) {
    function setCacheData($key, $data, $expiry = '+1 hour', $database = 'staffs') {
        try {
            $sql = "INSERT INTO cache_management (cache_key, cache_data, expiry_time) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL ?)) ON DUPLICATE KEY UPDATE cache_data = VALUES(cache_data), expiry_time = DATE_ADD(NOW(), INTERVAL ?)";
            $params = [$key, json_encode($data), $expiry, $expiry];
            
            DatabaseConnection::executeQuery($database, $sql, $params);
            
        } catch (Exception $e) {
            error_log("Cache storage error: " . $e->getMessage());
        }
    }
}

if (!function_exists('sendNotification')) {
    // Notification system function
    function sendNotification($userId, $title, $message, $type = 'info', $priority = 'medium', $database = 'staffs') {
        try {
            $sql = "INSERT INTO notifications (user_id, notification_type, title, message, priority, created_at) VALUES (?, ?, ?, ?, ?, NOW())";
            $params = [$userId, $type, $title, $message, $priority];
            
            DatabaseConnection::executeInsert($database, $sql, $params);
            
        } catch (Exception $e) {
            error_log("Notification error: " . $e->getMessage());
        }
    }
}

if (!function_exists('queueEmail')) {
    // Email queue function
    function queueEmail($to, $subject, $content, $type = 'notification', $priority = 'medium') {
        try {
            $sql = "INSERT INTO email_notifications_queue (recipient_email, subject, email_content, email_type, priority, scheduled_at) VALUES (?, ?, ?, ?, ?, NOW())";
            $params = [$to, $subject, $content, $type, $priority];
            
            DatabaseConnection::executeInsert('staffs', $sql, $params);
            
        } catch (Exception $e) {
            error_log("Email queue error: " . $e->getMessage());
        }
    }
}

// Constants for database names
define('DB_STAFFS', 'staffs');
define('DB_STUDENTS', 'students');
define('DB_WEBSITE', 'website');

// Enhanced security functions
function validateSession() {
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
        return false;
    }
    
    // Check session timeout
    $timeout = 30 * 60; // 30 minutes
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout) {
        session_destroy();
        return false;
    }
    
    $_SESSION['last_activity'] = time();
    return true;
}

if (!function_exists('hasPermission')) {
function hasPermission($permission, $userId = null) {
    $userId = $userId ?? $_SESSION['user_id'];
    
    if (!$userId) {
        return false;
    }
    
    try {
        $sql = "SELECT COUNT(*) as has_permission FROM staff_permissions sp 
                  JOIN staff_roles sr ON sp.staff_id = ? 
                  WHERE sp.permission_level IN (?, ?, ?, ?) AND sp.is_active = 1";
        $params = [$userId, 'Write', 'Delete', 'Admin', 'Super Admin'];
        
        $result = DatabaseConnection::executeQuery('staffs', $sql, $params);
        
        return $result && $result[0]['has_permission'] > 0;
        
    } catch (Exception $e) {
        error_log("Permission check error: " . $e->getMessage());
        return false;
    }
}
}

if (!function_exists('trackPerformance')) {
    // Performance monitoring
    function trackPerformance($metric, $value, $unit = '', $database = 'staffs') {
        try {
            $sql = "INSERT INTO performance_metrics (user_id, metric_type, metric_value, metric_unit, recorded_at) VALUES (?, ?, ?, ?, NOW())";
            $params = [$_SESSION['user_id'] ?? 0, $metric, $value, $unit];
            
            DatabaseConnection::executeInsert($database, $sql, $params);
            
        } catch (Exception $e) {
            error_log("Performance tracking error: " . $e->getMessage());
        }
    }
}

// Smart suggestions
if (!function_exists('addSmartSuggestion')) {
    // Smart suggestions
    function addSmartSuggestion($userId, $suggestion, $type = 'action', $priority = 'medium') {
        try {
            $sql = "INSERT INTO smart_suggestions (user_id, suggestion_type, suggestion_text, priority, created_at) VALUES (?, ?, ?, ?, NOW())";
            $params = [$userId, $type, $suggestion, $priority];
            
            DatabaseConnection::executeInsert('staffs', $sql, $params);
            
        } catch (Exception $e) {
            error_log("Smart suggestion error: " . $e->getMessage());
        }
    }
}

if (!function_exists('logDocumentGeneration')) {
    // Document generation logging
    function logDocumentGeneration($documentType, $documentId, $database = 'staffs') {
        try {
            $sql = "INSERT INTO document_generation_log (document_type, document_id, generated_by, created_at) VALUES (?, ?, ?, NOW())";
            $params = [$documentType, $documentId, $_SESSION['user_id'] ?? 0];
            
            DatabaseConnection::executeInsert($database, $sql, $params);
            
        } catch (Exception $e) {
            error_log("Document generation log error: " . $e->getMessage());
        }
    }
}

if (!function_exists('addRealTimeUpdate')) {
    // Real-time updates
    function addRealTimeUpdate($updateType, $title, $description, $data = null, $priority = 'medium') {
        try {
            $sql = "INSERT INTO real_time_updates (update_type, update_title, update_description, update_data, priority, created_at) VALUES (?, ?, ?, ?, ?, NOW())";
            $params = [$updateType, $title, $description, json_encode($data), $priority];
            
            DatabaseConnection::executeInsert('staffs', $sql, $params);
            
        } catch (Exception $e) {
            error_log("Real-time update error: " . $e->getMessage());
        }
    }
}

if (!function_exists('getSystemSetting')) {
    // System settings management
    function getSystemSetting($key, $default = null) {
        try {
            $sql = "SELECT setting_value FROM system_settings WHERE setting_key = ? AND is_public = 1";
            $result = DatabaseConnection::executeQuery('staffs', $sql, [$key]);
            
            if ($result && count($result) > 0) {
                return $result[0]['setting_value'];
            }
            
            return $default;
            
        } catch (Exception $e) {
            error_log("System setting retrieval error: " . $e->getMessage());
            return $default;
        }
    }
}

if (!function_exists('setSystemSetting')) {
    function setSystemSetting($key, $value, $type = 'text') {
        try {
            $sql = "INSERT INTO system_settings (setting_key, setting_value, setting_type, created_at) VALUES (?, ?, ?, NOW()) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = NOW()";
            $params = [$key, $value, $type];
            
            DatabaseConnection::executeQuery('staffs', $sql, $params);
            
        } catch (Exception $e) {
            error_log("System setting update error: " . $e->getMessage());
        }
    }
}
?>
