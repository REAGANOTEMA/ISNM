<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized. Please log in.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed.']);
    exit;
}

$action = $_POST['action'] ?? '';
$user_id = (int)$_SESSION['user_id'];

// CSRF only for write actions (send, read, delete) — skip for read-only
$writeActions = ['send', 'read', 'delete'];
if (in_array($action, $writeActions)) {
    $csrfToken = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (empty($csrfToken) || !isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrfToken)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Invalid or missing security token.']);
        exit;
    }
}

$conn = function_exists('getStaffConnection') ? getStaffConnection() : null;
if (!$conn) {
    echo json_encode(['success' => false, 'error' => 'Database connection failed.']);
    exit;
}

switch ($action) {

    // â”€â”€ Send a new message â”€â”€
    case 'send':
        $sender_id    = (int)($_POST['sender_id'] ?? $user_id);
        $recipient_id = (int)($_POST['recipient_id'] ?? 0);
        $subject      = trim($_POST['subject'] ?? '');
        $message      = trim($_POST['message'] ?? '');
        $priority     = $_POST['priority'] ?? 'Normal';
        $parent_id    = !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : 0;

        if ($sender_id !== $user_id) {
            echo json_encode(['success' => false, 'error' => 'Sender identity mismatch.']);
            exit;
        }
        if ($recipient_id <= 0) {
            echo json_encode(['success' => false, 'error' => 'Please select a recipient.']);
            exit;
        }
        if (empty($subject) || empty($message)) {
            echo json_encode(['success' => false, 'error' => 'Subject and message are required.']);
            exit;
        }

        $sender_role = '';
        $sender_name = '';
        $r = $conn->prepare("SELECT full_name, position FROM staff WHERE id = ?");
        if ($r) {
            $r->bind_param("i", $sender_id);
            if (!$r->execute()) { error_log('$r execute failed: ' . ($r->error ?? 'unknown')); };
            $row = $r->get_result()->fetch_assoc();
            $r->close();
            if ($row) {
                $sender_name = $row['full_name'];
                $sender_role = $row['position'] ?? '';
            }
        }

        $recipient_name = '';
        $r2 = $conn->prepare("SELECT full_name FROM staff WHERE id = ?");
        if ($r2) {
            $r2->bind_param("i", $recipient_id);
            if (!$r2->execute()) { error_log('$r2 execute failed: ' . ($r2->error ?? 'unknown')); };
            $row2 = $r2->get_result()->fetch_assoc();
            $r2->close();
            if ($row2) $recipient_name = $row2['full_name'];
        }

        if (empty($sender_name)) $sender_name = $_SESSION['full_name'] ?? 'Unknown';
        if (empty($recipient_name)) {
            echo json_encode(['success' => false, 'error' => 'Recipient not found.']);
            exit;
        }

        $priority = strtolower($priority);
        if (!in_array($priority, ['low', 'normal', 'high', 'urgent'])) $priority = 'normal';

        $stmt = $conn->prepare("INSERT INTO staff_inbox (sender_id, sender_name, sender_role, recipient_id, recipient_name, subject, message, priority, parent_id, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
        if (!$stmt) {
            echo json_encode(['success' => false, 'error' => 'Failed to send message']);
            exit;
        }
        $stmt->bind_param("issssssi", $sender_id, $sender_name, $sender_role, $recipient_id, $recipient_name, $subject, $message, $priority, $parent_id);

        if ($stmt->execute()) {
            $new_id = $stmt->insert_id;
            $stmt->close();

            // Queue email notification for recipient
            queueMessageEmail($conn, $recipient_id, $recipient_name, $sender_name, $subject, $message, $priority);

            echo json_encode(['success' => true, 'message_id' => $new_id]);
        } else {
            $stmt->close();
            echo json_encode(['success' => false, 'error' => 'Failed to send message']);
        }
        break;

    // â”€â”€ Get inbox messages â”€â”€
    case 'inbox':
        $rid    = (int)($_POST['recipient_id'] ?? $user_id);
        $offset = max(0, (int)($_POST['offset'] ?? 0));
        $limit  = min(100, max(1, (int)($_POST['limit'] ?? 50)));

        $stmt = $conn->prepare("SELECT * FROM staff_inbox WHERE recipient_id = ? AND is_deleted_recipient = 0 ORDER BY created_at DESC LIMIT ? OFFSET ?");
        if (!$stmt) {
            echo json_encode(['success' => false, 'error' => 'Failed to load messages']);
            exit;
        }
        $limitStr = (string)$limit;
        $offsetStr = (string)$offset;
        $stmt->bind_param("iss", $rid, $limitStr, $offsetStr);
        if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
        $result = $stmt->get_result();
        $messages = [];
        while ($row = $result->fetch_assoc()) $messages[] = $row;
        $stmt->close();

        $ct = $conn->prepare("SELECT COUNT(*) as c FROM staff_inbox WHERE recipient_id = ? AND is_deleted_recipient = 0");
        $total = 0;
        if ($ct) {
            $ct->bind_param("i", $rid);
            if (!$ct->execute()) { error_log('$ct execute failed: ' . ($ct->error ?? 'unknown')); };
            $total = (int)$ct->get_result()->fetch_assoc()['c'];
            $ct->close();
        }

        echo json_encode(['success' => true, 'data' => $messages, 'total' => $total]);
        break;

    // â"€â"€ Get sent messages â"€â"€
    case 'sent':
        $sid    = (int)($_POST['sender_id'] ?? $user_id);
        $offset = max(0, (int)($_POST['offset'] ?? 0));
        $limit  = min(100, max(1, (int)($_POST['limit'] ?? 50)));

        $stmt = $conn->prepare("SELECT * FROM staff_inbox WHERE sender_id = ? AND is_deleted_sender = 0 ORDER BY created_at DESC LIMIT ? OFFSET ?");
        if (!$stmt) {
            echo json_encode(['success' => false, 'error' => 'Failed to load messages']);
            exit;
        }
        $limitStr2 = (string)$limit;
        $offsetStr2 = (string)$offset;
        $stmt->bind_param("iss", $sid, $limitStr2, $offsetStr2);
        if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
        $result = $stmt->get_result();
        $messages = [];
        while ($row = $result->fetch_assoc()) $messages[] = $row;
        $stmt->close();

        $ct = $conn->prepare("SELECT COUNT(*) as c FROM staff_inbox WHERE sender_id = ? AND is_deleted_sender = 0");
        $total = 0;
        if ($ct) {
            $ct->bind_param("i", $sid);
            if (!$ct->execute()) { error_log('$ct execute failed: ' . ($ct->error ?? 'unknown')); };
            $total = (int)$ct->get_result()->fetch_assoc()['c'];
            $ct->close();
        }

        echo json_encode(['success' => true, 'data' => $messages, 'total' => $total]);
        break;

    // â”€â”€ Mark message as read â”€â”€
    case 'read':
        $msg_id = (int)($_POST['message_id'] ?? 0);
        $uid    = (int)($_POST['user_id'] ?? $user_id);

        if ($msg_id <= 0) {
            echo json_encode(['success' => false, 'error' => 'Invalid message ID.']);
            exit;
        }

        $stmt = $conn->prepare("UPDATE staff_inbox SET is_read = 1, read_at = NOW() WHERE id = ? AND recipient_id = ? AND is_read = 0");
        if (!$stmt) {
            echo json_encode(['success' => false, 'error' => 'Failed to update message']);
            exit;
        }
        $stmt->bind_param("ii", $msg_id, $uid);
        if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
        $affected = $stmt->affected_rows;
        $stmt->close();

        echo json_encode(['success' => true, 'updated' => $affected > 0]);
        break;

    // â”€â”€ Soft delete message â”€â”€
    case 'delete':
        $msg_id = (int)($_POST['message_id'] ?? 0);
        $uid    = (int)($_POST['user_id'] ?? $user_id);
        $box    = $_POST['box'] ?? 'inbox';

        if ($msg_id <= 0) {
            echo json_encode(['success' => false, 'error' => 'Invalid message ID.']);
            exit;
        }

        if ($box === 'sent') {
            $stmt = $conn->prepare("UPDATE staff_inbox SET is_deleted_sender = 1 WHERE id = ? AND sender_id = ?");
        } else {
            $stmt = $conn->prepare("UPDATE staff_inbox SET is_deleted_recipient = 1 WHERE id = ? AND recipient_id = ?");
        }
        if (!$stmt) {
            echo json_encode(['success' => false, 'error' => 'Failed to delete message']);
            exit;
        }
        $stmt->bind_param("ii", $msg_id, $uid);
        if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
        $affected = $stmt->affected_rows;
        $stmt->close();

        echo json_encode(['success' => true, 'deleted' => $affected > 0]);
        break;

    // â”€â”€ Unread count â”€â”€
    case 'unread_count':
        $uid = (int)($_POST['user_id'] ?? $user_id);
        $r = $conn->prepare("SELECT COUNT(*) as c FROM staff_inbox WHERE recipient_id = ? AND is_read = 0 AND is_deleted_recipient = 0");
        $count = 0;
        if ($r) {
            $r->bind_param("i", $uid);
            if (!$r->execute()) { error_log('$r execute failed: ' . ($r->error ?? 'unknown')); };
            $count = (int)$r->get_result()->fetch_assoc()['c'];
            $r->close();
        }
        echo json_encode(['success' => true, 'count' => $count]);
        break;

    // â”€â”€ Get thread â”€â”€
    case 'thread':
        $parent_id = (int)($_POST['parent_id'] ?? 0);
        $msg_id    = (int)($_POST['message_id'] ?? 0);
        $uid       = $user_id;

        $messages = [];

        if ($msg_id > 0) {
            $r0 = $conn->prepare("SELECT parent_id FROM staff_inbox WHERE id = ?");
            if ($r0) {
                $r0->bind_param("i", $msg_id);
                if (!$r0->execute()) { error_log('$r0 execute failed: ' . ($r0->error ?? 'unknown')); };
                $row0 = $r0->get_result()->fetch_assoc();
                $r0->close();
                if ($row0 && $row0['parent_id']) {
                    $parent_id = (int)$row0['parent_id'];
                } else {
                    $parent_id = $msg_id;
                }
            }
        }

        if ($parent_id > 0) {
            $stmt = $conn->prepare("SELECT * FROM staff_inbox WHERE (id = ? OR parent_id = ?) AND (sender_id = ? OR recipient_id = ?) ORDER BY created_at ASC");
            if ($stmt) {
                $stmt->bind_param("iiii", $parent_id, $parent_id, $uid, $uid);
                if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
                $res = $stmt->get_result();
                while ($row = $res->fetch_assoc()) $messages[] = $row;
                $stmt->close();
            }
        }

        echo json_encode(['success' => true, 'data' => $messages]);
        break;

    // â”€â”€ Search messages â”€â”€
    case 'search':
        $uid   = (int)($_POST['user_id'] ?? $user_id);
        $query = trim($_POST['query'] ?? '');

        if (empty($query)) {
            echo json_encode(['success' => false, 'error' => 'Search query is required.']);
            exit;
        }

        $like = '%' . $query . '%';
        $stmt = $conn->prepare("SELECT * FROM staff_inbox WHERE (sender_id = ? OR recipient_id = ?) AND (subject LIKE ? OR message LIKE ?) AND (is_deleted_sender = 0 AND is_deleted_recipient = 0) ORDER BY created_at DESC LIMIT 50");
        if (!$stmt) {
            echo json_encode(['success' => false, 'error' => 'Failed to search messages']);
            exit;
        }
        $stmt->bind_param("iiss", $uid, $uid, $like, $like);
        if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
        $res = $stmt->get_result();
        $messages = [];
        while ($row = $res->fetch_assoc()) $messages[] = $row;
        $stmt->close();

        echo json_encode(['success' => true, 'data' => $messages]);
        break;

    default:
        echo json_encode(['success' => false, 'error' => 'Unknown action: ' . $action]);
        break;
}

/**
 * Queue an email notification for a new message.
 */
function queueMessageEmail($conn, $recipientId, $recipientName, $senderName, $subject, $message, $priority) {
    try {
        // Get recipient email from staff table
        $emailStmt = $conn->prepare("SELECT email FROM staff WHERE id = ?");
        if (!$emailStmt) return;
        $emailStmt->bind_param("i", $recipientId);
        if (!$emailStmt->execute()) { error_log('queueMessageEmail email query failed: ' . $emailStmt->error); return; }
        $row = $emailStmt->get_result()->fetch_assoc();
        $emailStmt->close();
        if (!$row || empty($row['email'])) return;

        $recipientEmail = $row['email'];
        $emailSubject = "New Message from $senderName: $subject";
        $emailContent = "Dear $recipientName,\n\nYou have received a new message from $senderName.\n\nSubject: $subject\nPriority: $priority\n\nMessage:\n$message\n\n---\nISNM School Management System";

        $prioMap = ['urgent' => 'high', 'high' => 'high', 'normal' => 'normal', 'low' => 'low'];
        $emailPrio = $prioMap[strtolower($priority)] ?? 'normal';

        // Try to use email_notifications_queue if it exists
        $checkTable = $conn->query("SHOW TABLES LIKE 'email_notifications_queue'");
        if ($checkTable && $checkTable->num_rows > 0) {
            $eqStmt = $conn->prepare("INSERT INTO email_notifications_queue (recipient_email, recipient_name, subject, email_content, email_type, priority, status, scheduled_at, created_at) VALUES (?, ?, ?, ?, 'message_notification', ?, 'pending', NOW(), NOW())");
            if ($eqStmt) {
                $eqStmt->bind_param("sssss", $recipientEmail, $recipientName, $emailSubject, $emailContent, $emailPrio);
                $eqStmt->execute();
                $eqStmt->close();
                return;
            }
        }

        // Fallback: log to error_log if queue table not available
        error_log("EMAIL QUEUE: To=$recipientEmail Subject=$emailSubject");
    } catch (Exception $e) {
        error_log('queueMessageEmail: ' . $e->getMessage());
    }
}
