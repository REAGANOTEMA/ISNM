<?php
/**
 * ISNM Form Router & Notification System
 * Routes all forms to correct recipients based on type
 * Manages notifications for all departments
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/error_handler.php';

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_samesite', 'Lax');
    ini_set('session.use_strict_mode', 1);
    ini_set('session.cookie_path', SESSION_COOKIE_PATH);
    session_start();
}

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
            'columns' => ['student_id', 'applicant_name', 'email', 'phone', 'program', 'intake', 'academic_year', 'application_type', 'notes'],
        ],
        'contact' => [
            'recipients' => 'directorgeneral', // Director General
            'table' => 'contact_submissions',
            'notification_type' => 'contact_message',
            'columns' => ['first_name', 'last_name', 'email', 'phone', 'subject', 'message'],
        ],
        'feedback' => [
            'recipients' => 'directorgeneral,director_academics', // Multiple recipients
            'table' => 'feedback_submissions',
            'notification_type' => 'feedback_received',
            'columns' => ['name', 'email', 'rating', 'subject', 'feedback', 'category'],
        ],
        'complaint' => [
            'recipients' => 'directorgeneral,director_academics',
            'table' => 'complaint_submissions',
            'notification_type' => 'complaint_received',
            'columns' => ['complainant_name', 'complainant_email', 'complainant_phone', 'subject', 'description', 'department', 'severity'],
        ],
        'volunteer' => [
            'recipients' => 'directorgeneral',
            'table' => 'volunteer_applications',
            'notification_type' => 'volunteer_applied',
            'columns' => ['first_name', 'surname', 'email', 'phone', 'skills', 'availability', 'motivation', 'experience'],
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
            $submissionId = $this->storeSubmission($route, $data);
            
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
    private function storeSubmission(array $route, $data) {
        $table = $route['table'];
        $allowed = $route['columns'] ?? [];
        if (!is_array($data) || !$allowed || !preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $table)) {
            return false;
        }
        foreach ($allowed as $column) {
            if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $column)) {
                return false;
            }
        }

        $data = array_intersect_key($data, array_flip($allowed));
        $columns = [];
        $values = [];
        foreach ($allowed as $column) {
            if (!array_key_exists($column, $data)) {
                continue;
            }
            $value = $data[$column];
            if (is_array($value) || is_object($value)) {
                return false;
            }
            $value = is_scalar($value) ? (string)$value : '';
            if (strlen($value) > 10000) {
                return false;
            }
            $columns[] = $column;
            $values[] = $value;
        }
        if (!$columns) {
            return false;
        }

        $quoted = array_map(function ($c) { return '`' . $c . '`'; }, $columns);
        $marks = implode(', ', array_fill(0, count($columns), '?'));
        $sql = "INSERT INTO `$table` (" . implode(', ', $quoted) . ", created_at) VALUES ($marks, NOW())";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            error_log('FormRouter prepare failed: ' . $this->conn->error);
            return false;
        }
        $stmt->bind_param(str_repeat('s', count($values)), ...$values);
        if (!$stmt->execute()) {
            error_log('FormRouter insert failed: ' . $stmt->error);
            $stmt->close();
            return false;
        }
        $insertId = $this->conn->insert_id;
        $stmt->close();
        return $insertId;
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
        $staffId = (int)($_SESSION['user_id'] ?? 0);
        $notificationId = (int)$notificationId;
        if (!$staffId || !$notificationId) return false;

        $ownerCheck = $this->conn->prepare(
            "SELECT id FROM staff_notifications WHERE id = ? AND (staff_id = ? OR staff_id IS NULL OR staff_id = 0) LIMIT 1"
        );
        if (!$ownerCheck) return false;
        $ownerCheck->bind_param('ii', $notificationId, $staffId);
        if (!$ownerCheck->execute() || !$ownerCheck->get_result()->fetch_row()) {
            $ownerCheck->close();
            return false;
        }
        $ownerCheck->close();

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
        $staffId = (int)$staffId;
        if (!$staffId) return false;
        $stmt = $this->conn->prepare(
            "SELECT n.id FROM staff_notifications n 
             LEFT JOIN staff_notification_reads r ON n.id = r.notification_id AND r.staff_id = ?
             WHERE n.staff_id = ? AND r.id IS NULL"
        );
        if (!$stmt) return false;
        $stmt->bind_param('ii', $staffId, $staffId);
        if (!$stmt->execute()) { $stmt->close(); return false; }
        $unread = $stmt->get_result();
        $ok = true;
        while ($row = $unread->fetch_assoc()) {
            $insert = $this->conn->prepare("INSERT IGNORE INTO staff_notification_reads (staff_id, notification_id, read_at) VALUES (?, ?, NOW())");
            if ($insert) {
                $insert->bind_param('ii', $staffId, $row['id']);
                if (!$insert->execute()) $ok = false;
                $insert->close();
            }
        }
        $stmt->close();
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
