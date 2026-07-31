<?php
/**
 * ISNM Form Router & Notification System
 * Routes all forms to correct recipients based on type
 * Manages notifications for all departments
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/error_handler.php';

class FormRouter {
    private $conn;
    private $type;
    private $data;
    
    // Form routing configuration
    private $routes = [
        'application' => [
            'recipients' => 'admissions', // Director Admissions
            'table' => 'applications',
            'notification_type' => 'application_received',
        ],
        'contact' => [
            'recipients' => 'directorgeneral', // Director General
            'table' => 'contact_submissions',
            'notification_type' => 'contact_message',
        ],
        'feedback' => [
            'recipients' => 'directorgeneral,director_academics', // Multiple recipients
            'table' => 'feedback_submissions',
            'notification_type' => 'feedback_received',
        ],
        'complaint' => [
            'recipients' => 'directorgeneral,director_academics',
            'table' => 'complaint_submissions',
            'notification_type' => 'complaint_received',
        ],
        'volunteer' => [
            'recipients' => 'directorgeneral',
            'table' => 'volunteer_applications',
            'notification_type' => 'volunteer_applied',
        ],
    ];
    
    public function __construct() {
        $this->conn = getStudentsConnection();
        if (!$this->conn) {
            throw new Exception("Cannot connect to database");
        }
    }
    
    /**
     * Process form submission
     */
    public function processForm($type, $data) {
        $this->type = $type;
        $this->data = $data;
        
        // Validate form type exists
        if (!isset($this->routes[$type])) {
            return ['success' => false, 'message' => 'Invalid form type'];
        }
        
        $route = $this->routes[$type];
        
        try {
            // Store submission
            $submissionId = $this->storeSubmission($route['table'], $data);
            
            if (!$submissionId) {
                return ['success' => false, 'message' => 'Failed to store submission'];
            }
            
            // Send notifications to recipients
            $this->notifyRecipients($route, $submissionId, $data);
            
            // Send confirmation email to submitter
            $this->sendConfirmationEmail($data, $type);
            
            return [
                'success' => true,
                'message' => 'Form submitted successfully',
                'submission_id' => $submissionId,
            ];
        } catch (Exception $e) {
            error_log("Form processing error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error processing form'];
        }
    }
    
    /**
     * Store form submission
     */
    private function storeSubmission($table, $data) {
        // Sanitize data
        $sanitized = array_map(function($val) {
            return $this->conn->real_escape_string($val);
        }, $data);
        
        // Build insert query
        $columns = implode(', ', array_keys($sanitized));
        $values = "'" . implode("', '", array_values($sanitized)) . "'";
        $values .= ", NOW()"; // Add timestamp
        
        $sql = "INSERT INTO `$table` ($columns, created_at) VALUES ($values)";
        
        if ($this->conn->query($sql)) {
            return $this->conn->insert_id;
        }
        
        error_log("Insert error: " . $this->conn->error);
        return false;
    }
    
    /**
     * Notify recipients
     */
    private function notifyRecipients($route, $submissionId, $data) {
        $recipients = explode(',', $route['recipients']);
        
        foreach ($recipients as $role) {
            $role = trim($role);
            
            // Get staff member with this role
            $stmt = $this->conn->prepare(
                "SELECT id, email, full_name FROM staff WHERE role = ? AND status = 'active' LIMIT 1"
            );
            $stmt->bind_param('s', $role);
            if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
            $result = $stmt->get_result();
            
            if ($row = $result->fetch_assoc()) {
                $this->createNotification(
                    $row['id'],
                    $route['notification_type'],
                    "New {$this->type} received",
                    "A new {$this->type} has been submitted",
                    $submissionId,
                    $data['email'] ?? 'Unknown'
                );
            }
            
            $stmt->close();
        }
    }
    
    /**
     * Create in-app notification
     */
    private function createNotification($staffId, $type, $title, $message, $relatedId, $fromEmail) {
        $staffsDb = getStaffConnection();
        if (!$staffsDb) return;
        $stmt = $staffsDb->prepare(
            "INSERT INTO staff_notifications (staff_id, title, message, type, is_read, created_at) 
             VALUES (?, ?, ?, ?, 0, NOW())"
        );
        
        if ($stmt) {
            $stmt->bind_param('isss', $staffId, $title, $message, $type);
            if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
            $stmt->close();
        }
    }
    
    /**
     * Send confirmation email to submitter
     */
    private function sendConfirmationEmail($data, $type) {
        $email = $data['email'] ?? null;
        if (!$email) return;
        
        $subject = "Submission Received - ISNM";
        $message = "Thank you for your " . ucfirst($type) . " submission. ";
        $message .= "Our team will review it and get back to you soon.";
        
        $headers = "From: noreply@igangaschoolofnursingandmidwifery.ac.ug\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        
        mail($email, $subject, $message, $headers);
    }
}

/**
 * Notification Manager
 */
class NotificationManager {
    private $conn;
    
    public function __construct() {
        $this->conn = getStaffConnection();
        if (!$this->conn) {
            throw new Exception("Cannot connect to staff database");
        }
    }
    
    public function getNotifications($staffId, $limit = 10) {
        $stmt = $this->conn->prepare(
            "SELECT n.* FROM staff_notifications n 
             LEFT JOIN staff_notification_reads r ON n.id = r.notification_id AND r.staff_id = ?
             WHERE n.staff_id = ? AND r.id IS NULL
             ORDER BY n.created_at DESC LIMIT ?"
        );
        
        if (!$stmt) return [];
        
        $stmt->bind_param('iii', $staffId, $staffId, $limit);
        if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
        $result = $stmt->get_result();
        
        $notifications = [];
        while ($row = $result->fetch_assoc()) {
            $notifications[] = $row;
        }
        
        $stmt->close();
        return $notifications;
    }
    
    public function getNotificationCount($staffId) {
        $stmt = $this->conn->prepare(
            "SELECT COUNT(*) as count FROM staff_notifications n 
             LEFT JOIN staff_notification_reads r ON n.id = r.notification_id AND r.staff_id = ?
             WHERE n.staff_id = ? AND r.id IS NULL"
        );
        
        if (!$stmt) return 0;
        
        $stmt->bind_param('ii', $staffId, $staffId);
        if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        
        return $row['count'] ?? 0;
    }
    
    public function markAsRead($notificationId) {
        $staffId = $_SESSION['user_id'] ?? 0;
        if (!$staffId) return false;
        $stmt = $this->conn->prepare(
            "INSERT IGNORE INTO staff_notification_reads (staff_id, notification_id, read_at) VALUES (?, ?, NOW())"
        );
        
        if ($stmt) {
            $stmt->bind_param('ii', $staffId, $notificationId);
            $success = $stmt->execute();
            $stmt->close();
            return $success;
        }
        
        return false;
    }
    
    public function markAllAsRead($staffId) {
        $unread = $this->conn->query(
            "SELECT n.id FROM staff_notifications n 
             LEFT JOIN staff_notification_reads r ON n.id = r.notification_id AND r.staff_id = $staffId
             WHERE n.staff_id = $staffId AND r.id IS NULL"
        );
        if (!$unread) return false;
        $ok = true;
        while ($row = $unread->fetch_assoc()) {
            $stmt = $this->conn->prepare("INSERT IGNORE INTO staff_notification_reads (staff_id, notification_id, read_at) VALUES (?, ?, NOW())");
            if ($stmt) {
                $stmt->bind_param('ii', $staffId, $row['id']);
                if (!$stmt->execute()) $ok = false;
                $stmt->close();
            }
        }
        return $ok;
    }
    
    public function getRecentNotifications($staffId, $limit = 5) {
        $stmt = $this->conn->prepare(
            "SELECT n.* FROM staff_notifications n 
             LEFT JOIN staff_notification_reads r ON n.id = r.notification_id AND r.staff_id = ?
             WHERE n.staff_id = ? AND n.created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR) AND r.id IS NULL
             ORDER BY n.created_at DESC LIMIT ?"
        );
        
        if (!$stmt) return [];
        
        $stmt->bind_param('iii', $staffId, $staffId, $limit);
        if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
        $result = $stmt->get_result();
        
        $notifications = [];
        while ($row = $result->fetch_assoc()) {
            $notifications[] = $row;
        }
        
        $stmt->close();
        return $notifications;
    }
}

// API endpoint for processing forms
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'submit_form') {
    header('Content-Type: application/json');

    // CSRF validation for logged-in users; rate-limit by IP for public submissions
    if (!empty($_SESSION['user_id'])) {
        $csrfToken = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (empty($csrfToken) || !isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrfToken)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Invalid security token. Please refresh and try again.']);
            exit();
        }
    } else {
        // Rate-limit public submissions by IP (max 10 per minute)
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $rateKey = 'form_submit_' . md5($ip);
        if (!isset($_SESSION[$rateKey])) {
            $_SESSION[$rateKey] = ['count' => 0, 'window' => time()];
        }
        if (time() - $_SESSION[$rateKey]['window'] > 60) {
            $_SESSION[$rateKey] = ['count' => 0, 'window' => time()];
        }
        $_SESSION[$rateKey]['count']++;
        if ($_SESSION[$rateKey]['count'] > 10) {
            http_response_code(429);
            echo json_encode(['success' => false, 'message' => 'Too many submissions. Please try again later.']);
            exit();
        }
    }

    $type = $_POST['form_type'] ?? null;
    $data = $_POST;
    unset($data['form_type']);
    
    try {
        $router = new FormRouter();
        $result = $router->processForm($type, $data);
        echo json_encode($result);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// API endpoint for getting notifications
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'get_notifications') {
    header('Content-Type: application/json');
    
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }
    
    try {
        $manager = new NotificationManager();
        $notifications = $manager->getNotifications($_SESSION['user_id']);
        $count = $manager->getNotificationCount($_SESSION['user_id']);
        
        echo json_encode([
            'success' => true,
            'notifications' => $notifications,
            'count' => $count,
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// API endpoint for marking notification as read
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'mark_notification_read') {
    header('Content-Type: application/json');

    // CSRF validation
    if (!empty($_SESSION['user_id'])) {
        $csrfToken = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (empty($csrfToken) || !isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrfToken)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Invalid security token. Please refresh and try again.']);
            exit();
        }
    }
    
    $notificationId = $_POST['notification_id'] ?? null;
    
    if (!$notificationId) {
        echo json_encode(['success' => false, 'message' => 'Notification ID required']);
        exit;
    }
    
    try {
        $manager = new NotificationManager();
        $success = $manager->markAsRead($notificationId);
        
        echo json_encode(['success' => $success]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

?>
