<?php
/**
 * ISNM AJAX Task Handler â€” Task management via AJAX
 * Handles: get tasks, create task, update status, delete task
 */
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/staff_dashboard_access.php';
require_once __DIR__ . '/enterprise_auth.php';

$ctx = bootstrapStaffDashboard(['director general', 'ceo', 'system admin', 'director academics',
    'director finance', 'director ict', 'director admissions', 'school principal',
    'deputy principal', 'academic registrar', 'school bursar', 'hr manager',
    'school secretary', 'school librarian', 'head nursing', 'head midwifery',
    'senior lecturer', 'lecturer', 'matron', 'warden', 'sickbay nurse',
    'driver', 'security officer', 'store keeper', 'computer lab manager',
    'skills lab manager', 'system admin']);
$conn = $ctx['staff'];
$userId = (int)($ctx['user']['id'] ?? 0);
$action = $_GET['action'] ?? $_POST['action'] ?? '';

if (!$conn || !$userId) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

switch ($action) {

    case 'get_my_tasks':
        $limit = min((int)($_GET['limit'] ?? 20), 50);
        $tasks = [];
        $stmt = $conn->prepare(
            "SELECT t.id, t.title, t.description, t.priority, t.status, t.due_date, t.due_time,
                    t.category, t.created_at, s.full_name AS assigned_by_name
             FROM task_assignments t
             LEFT JOIN staff s ON t.assigned_by = s.id
             WHERE t.assigned_to = ? AND t.status IN ('pending','in_progress')
             ORDER BY FIELD(t.priority,'urgent','high','medium','low'), t.due_date ASC
             LIMIT ?"
        );
        if ($stmt) {
            $stmt->bind_param('ii', $userId, $limit);
            if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $row['due_date'] = $row['due_date'] ? date('M j, Y', strtotime($row['due_date'])) : null;
                $row['time_ago'] = timeAgo($row['created_at']);
                $tasks[] = $row;
            }
            $stmt->close();
        }
        echo json_encode(['success' => true, 'tasks' => $tasks]);
        break;

    case 'get_all_tasks':
        $limit = min((int)($_GET['limit'] ?? 50), 200);
        $status = $_GET['status'] ?? '';
        $where = "WHERE t.assigned_to = ?";
        $params = [$userId];
        $types = 'i';
        if ($status && in_array($status, ['pending','in_progress','completed','cancelled'])) {
            $where .= " AND t.status = ?";
            $params[] = $status;
            $types .= 's';
        }
        $tasks = [];
        $stmt = $conn->prepare(
            "SELECT t.id, t.title, t.description, t.priority, t.status, t.due_date, t.due_time,
                    t.category, t.reference_type, t.reference_id, t.created_at, t.completed_at,
                    s.full_name AS assigned_by_name
             FROM task_assignments t
             LEFT JOIN staff s ON t.assigned_by = s.id
             {$where}
             ORDER BY FIELD(t.priority,'urgent','high','medium','low'), t.due_date ASC
             LIMIT ?"
        );
        if ($stmt) {
            $params[] = $limit;
            $types .= 'i';
            $stmt->bind_param($types, ...$params);
            if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $tasks[] = $row;
            }
            $stmt->close();
        }
        echo json_encode(['success' => true, 'tasks' => $tasks]);
        break;

    case 'create_task':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'error' => 'POST required']);
            exit;
        }
        // CSRF check
        if (!isset($_POST['csrf_token']) || !hash_equals(($_SESSION['csrf_token'] ?? ''), $_POST['csrf_token'])) {
            echo json_encode(['success' => false, 'error' => 'Invalid CSRF token']);
            exit;
        }
        $title = trim($_POST['title'] ?? '');
        $desc = trim($_POST['description'] ?? '');
        $assignedTo = (int)($_POST['assigned_to'] ?? 0);
        $priority = $_POST['priority'] ?? 'medium';
        $dueDate = $_POST['due_date'] ?? null;
        $category = trim($_POST['category'] ?? '');

        if (!$title) {
            echo json_encode(['success' => false, 'error' => 'Title is required']);
            exit;
        }
        if (!in_array($priority, ['low','medium','high','urgent'])) $priority = 'medium';
        if ($dueDate && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dueDate)) $dueDate = null;

        $stmt = $conn->prepare(
            "INSERT INTO task_assignments (title, description, assigned_by, assigned_to, priority, status, due_date, category, created_at)
             VALUES (?, ?, ?, ?, ?, 'pending', ?, ?, NOW())"
        );
        if ($stmt) {
            $stmt->bind_param('ssiisss', $title, $desc, $userId, $assignedTo, $priority, $dueDate, $category);
            if ($stmt->execute()) {
                $taskId = $stmt->insert_id;
                $stmt->close();
                logActivity($conn, 'task_created', "Created task: {$title}");
                logAuditTrail($conn, 'create', 'task', $taskId, "Task created: {$title}");
                echo json_encode(['success' => true, 'task_id' => $taskId]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Database error']);
            }
        } else {
            echo json_encode(['success' => false, 'error' => 'Database error']);
        }
        break;

    case 'update_status':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'error' => 'POST required']);
            exit;
        }
        if (!isset($_POST['csrf_token']) || !hash_equals(($_SESSION['csrf_token'] ?? ''), $_POST['csrf_token'])) {
            echo json_encode(['success' => false, 'error' => 'Invalid CSRF token']);
            exit;
        }
        $taskId = (int)($_POST['task_id'] ?? 0);
        $newStatus = $_POST['status'] ?? '';
        if (!$taskId || !in_array($newStatus, ['pending','in_progress','completed','cancelled'])) {
            echo json_encode(['success' => false, 'error' => 'Invalid parameters']);
            exit;
        }
        $completedAt = $newStatus === 'completed' ? date('Y-m-d H:i:s') : null;
        $stmt = $conn->prepare(
            "UPDATE task_assignments SET status = ?, completed_at = ?, updated_at = NOW() WHERE id = ? AND (assigned_to = ? OR assigned_by = ?)"
        );
        if ($stmt) {
            $stmt->bind_param('ssiii', $newStatus, $completedAt, $taskId, $userId, $userId);
            if ($stmt->execute() && $stmt->affected_rows >= 0) {
                $stmt->close();
                logActivity($conn, 'task_updated', "Task #{$taskId} status â†’ {$newStatus}");
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Task not found or no change']);
            }
        } else {
            echo json_encode(['success' => false, 'error' => 'Database error']);
        }
        break;

    case 'delete_task':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'error' => 'POST required']);
            exit;
        }
        if (!isset($_POST['csrf_token']) || !hash_equals(($_SESSION['csrf_token'] ?? ''), $_POST['csrf_token'])) {
            echo json_encode(['success' => false, 'error' => 'Invalid CSRF token']);
            exit;
        }
        $taskId = (int)($_POST['task_id'] ?? 0);
        if (!$taskId) {
            echo json_encode(['success' => false, 'error' => 'Invalid task ID']);
            exit;
        }
        $stmt = $conn->prepare("DELETE FROM task_assignments WHERE id = ? AND assigned_by = ?");
        if ($stmt) {
            $stmt->bind_param('ii', $taskId, $userId);
            if ($stmt->execute()) {
                $stmt->close();
                logAuditTrail($conn, 'delete', 'task', $taskId, "Task #{$taskId} deleted");
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Database error']);
            }
        }
        break;

    default:
        echo json_encode(['success' => false, 'error' => 'Unknown action: ' . $action]);
}

function timeAgo($datetime) {
    $now = new DateTime();
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);
    if ($diff->y > 0) return $diff->y . ' year' . ($diff->y > 1 ? 's' : '') . ' ago';
    if ($diff->m > 0) return $diff->m . ' month' . ($diff->m > 1 ? 's' : '') . ' ago';
    if ($diff->d > 0) return $diff->d . ' day' . ($diff->d > 1 ? 's' : '') . ' ago';
    if ($diff->h > 0) return $diff->h . ' hour' . ($diff->h > 1 ? 's' : '') . ' ago';
    if ($diff->i > 0) return $diff->i . ' min' . ($diff->i > 1 ? 's' : '') . ' ago';
    return 'Just now';
}
