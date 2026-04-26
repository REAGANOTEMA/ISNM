<?php
session_start();
include_once 'includes/config.php';
include_once 'includes/functions.php';
include_once 'includes/photo_upload.php';
include_once 'includes/student_profile_component.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'send_message':
                handleSendMessage();
                break;
            case 'send_bulk_message':
                handleSendBulkMessage();
                break;
            case 'reply_message':
                handleReplyMessage();
                break;
            case 'mark_read':
                handleMarkRead();
                break;
            case 'delete_message':
                handleDeleteMessage();
                break;
        }
    }
}

// Handle sending individual message
function handleSendMessage() {
    global $conn;
    
    $student_id = sanitizeInput($_POST['student_id']);
    $subject = sanitizeInput($_POST['subject']);
    $message = sanitizeInput($_POST['message']);
    $message_type = sanitizeInput($_POST['message_type']);
    $priority = sanitizeInput($_POST['priority']);
    $sender_id = $_SESSION['user_id'];
    $sender_role = $_SESSION['role'];
    
    $sql = "INSERT INTO messages (student_id, sender_id, sender_role, subject, message_content, message_type, priority, sent_date, status) VALUES (?, ?, ?, ?, ?, ?, ?, CURDATE(), 'sent')";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssssss", $student_id, $sender_id, $sender_role, $subject, $message, $message_type, $priority);
    
    if ($stmt->execute()) {
        // Create notification for student
        $notification_sql = "INSERT INTO notifications (user_id, notification_type, title, message, created_at, is_read) VALUES (?, 'message', ?, ?, CURDATE(), 0)";
        $notification_stmt = $conn->prepare($notification_sql);
        $notification_title = "New Message from $sender_role";
        $notification_message = "$subject: " . substr($message, 0, 100) . "...";
        $notification_stmt->bind_param("sss", $student_id, $notification_title, $notification_message);
        $notification_stmt->execute();
        
        logActivity($_SESSION['user_id'], $_SESSION['role'], 'Message Sent', "Sent $message_type message to: $student_id", 'messages', $student_id);
        $_SESSION['success'] = "Message sent successfully!";
    } else {
        $_SESSION['error'] = "Error sending message: " . $conn->error;
    }
    
    header("Location: student_communication_system.php");
    exit();
}

// Handle sending bulk messages
function handleSendBulkMessage() {
    global $conn;
    
    $recipients = $_POST['recipients'] ?? [];
    $subject = sanitizeInput($_POST['subject']);
    $message = sanitizeInput($_POST['message']);
    $message_type = sanitizeInput($_POST['message_type']);
    $priority = sanitizeInput($_POST['priority']);
    $sender_id = $_SESSION['user_id'];
    $sender_role = $_SESSION['role'];
    
    $success_count = 0;
    $error_count = 0;
    
    foreach ($recipients as $student_id) {
        $sql = "INSERT INTO messages (student_id, sender_id, sender_role, subject, message_content, message_type, priority, sent_date, status) VALUES (?, ?, ?, ?, ?, ?, ?, CURDATE(), 'sent')";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssssss", $student_id, $sender_id, $sender_role, $subject, $message, $message_type, $priority);
        
        if ($stmt->execute()) {
            // Create notification for student
            $notification_sql = "INSERT INTO notifications (user_id, notification_type, title, message, created_at, is_read) VALUES (?, 'message', ?, ?, CURDATE(), 0)";
            $notification_stmt = $conn->prepare($notification_sql);
            $notification_title = "New Message from $sender_role";
            $notification_message = "$subject: " . substr($message, 0, 100) . "...";
            $notification_stmt->bind_param("sss", $student_id, $notification_title, $notification_message);
            $notification_stmt->execute();
            
            $success_count++;
        } else {
            $error_count++;
        }
    }
    
    logActivity($_SESSION['user_id'], $_SESSION['role'], 'Bulk Message Sent', "Sent bulk message to $success_count students", 'messages', '');
    $_SESSION['success'] = "Bulk message sent successfully! Success: $success_count, Errors: $error_count";
    
    header("Location: student_communication_system.php");
    exit();
}

// Handle replying to message
function handleReplyMessage() {
    global $conn;
    
    $original_message_id = sanitizeInput($_POST['original_message_id']);
    $student_id = sanitizeInput($_POST['student_id']);
    $reply_message = sanitizeInput($_POST['reply_message']);
    $sender_id = $_SESSION['user_id'];
    $sender_role = $_SESSION['role'];
    
    // Get original message details
    $original_sql = "SELECT * FROM messages WHERE id = ?";
    $original_result = executeQuery($original_sql, [$original_message_id], 'i');
    $original_msg = $original_result[0] ?? null;
    
    if ($original_msg) {
        $subject = "Re: " . $original_msg['subject'];
        
        $sql = "INSERT INTO messages (student_id, sender_id, sender_role, subject, message_content, message_type, priority, sent_date, status, parent_message_id) VALUES (?, ?, ?, ?, ?, ?, ?, CURDATE(), 'sent', ?)";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssssssi", $student_id, $sender_id, $sender_role, $subject, $reply_message, 'reply', 'normal', $original_message_id);
        
        if ($stmt->execute()) {
            logActivity($_SESSION['user_id'], $_SESSION['role'], 'Message Reply', "Replied to message ID: $original_message_id", 'messages', $student_id);
            $_SESSION['success'] = "Reply sent successfully!";
        } else {
            $_SESSION['error'] = "Error sending reply: " . $conn->error;
        }
    }
    
    header("Location: student_communication_system.php");
    exit();
}

// Handle marking message as read
function handleMarkRead() {
    global $conn;
    
    $message_id = sanitizeInput($_POST['message_id']);
    
    $sql = "UPDATE messages SET status = 'read', read_date = CURDATE() WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $message_id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false]);
    }
    exit();
}

// Handle deleting message
function handleDeleteMessage() {
    global $conn;
    
    $message_id = sanitizeInput($_POST['message_id']);
    
    $sql = "UPDATE messages SET status = 'deleted', deleted_date = CURDATE() WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $message_id);
    
    if ($stmt->execute()) {
        logActivity($_SESSION['user_id'], $_SESSION['role'], 'Message Deleted', "Deleted message ID: $message_id", 'messages', '');
        $_SESSION['success'] = "Message deleted successfully!";
    } else {
        $_SESSION['error'] = "Error deleting message: " . $conn->error;
    }
    
    header("Location: student_communication_system.php");
    exit();
}

// Get messages based on user role
if ($_SESSION['role'] === 'Student') {
    // Students see messages sent to them
    $messages_sql = "SELECT * FROM messages WHERE student_id = ? AND status != 'deleted' ORDER BY sent_date DESC";
    $messages = executeQuery($messages_sql, [$_SESSION['user_id']], 's');
} else {
    // Staff see messages they've sent
    $messages_sql = "SELECT m.*, s.first_name, s.surname FROM messages m LEFT JOIN students s ON m.student_id = s.student_id WHERE m.sender_id = ? AND m.status != 'deleted' ORDER BY m.sent_date DESC";
    $messages = executeQuery($messages_sql, [$_SESSION['user_id']], 's');
}

// Get unread count
$unread_sql = "SELECT COUNT(*) as count FROM messages WHERE student_id = ? AND status = 'sent'";
$unread_result = executeQuery($unread_sql, [$_SESSION['user_id']], 's');
$unread_count = $unread_result[0]['count'];

// Get students for bulk messaging
if ($_SESSION['role'] !== 'Student') {
    $students_sql = "SELECT * FROM students WHERE status = 'active' ORDER BY surname, first_name";
    $all_students = executeQuery($students_sql);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Communication System - ISNM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #1a237e;
            --secondary-color: #3949ab;
            --accent-color: #ffd700;
            --success-color: #28a745;
            --danger-color: #dc3545;
            --warning-color: #ffc107;
            --info-color: #17a2b8;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
        }

        .navbar {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .navbar-brand {
            font-weight: bold;
            color: var(--accent-color) !important;
        }

        .main-container {
            padding: 2rem;
            max-width: 1400px;
            margin: 0 auto;
        }

        .page-header {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            border-left: 5px solid var(--primary-color);
        }

        .content-section {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }

        .section-title {
            color: var(--primary-color);
            font-weight: bold;
            margin-bottom: 1.5rem;
            font-size: 1.3rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border: none;
            border-radius: 8px;
            padding: 0.75rem 1.5rem;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(26, 35, 126, 0.3);
        }

        .message-item {
            border: 1px solid #e0e0e0;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .message-item:hover {
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }

        .message-item.unread {
            border-left: 4px solid var(--primary-color);
            background: #f8f9ff;
        }

        .message-header {
            display: flex;
            justify-content: between;
            align-items: center;
            margin-bottom: 0.5rem;
        }

        .message-sender {
            font-weight: bold;
            color: var(--primary-color);
        }

        .message-date {
            font-size: 0.875rem;
            color: #666;
        }

        .message-subject {
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: #333;
        }

        .message-preview {
            color: #666;
            font-size: 0.9rem;
            line-height: 1.4;
        }

        .message-type-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .type-urgent {
            background: #dc3545;
            color: white;
        }

        .type-important {
            background: #ffc107;
            color: #333;
        }

        .type-general {
            background: #17a2b8;
            color: white;
        }

        .type-academic {
            background: #28a745;
            color: white;
        }

        .type-financial {
            background: #6f42c1;
            color: white;
        }

        .message-actions {
            display: flex;
            gap: 0.5rem;
            margin-top: 1rem;
        }

        .message-actions .btn {
            padding: 0.25rem 0.75rem;
            font-size: 0.875rem;
        }

        .compose-form {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 2rem;
            margin-bottom: 2rem;
        }

        .student-selector {
            max-height: 200px;
            overflow-y: auto;
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 0.5rem;
        }

        .student-checkbox {
            margin-bottom: 0.5rem;
        }

        .notification-badge {
            position: absolute;
            top: -8px;
            right: -8px;
            background: var(--danger-color);
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
            font-weight: bold;
        }

        .message-thread {
            border: 1px solid #e0e0e0;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1rem;
        }

        .thread-message {
            margin-bottom: 1rem;
            padding: 1rem;
            border-radius: 8px;
        }

        .thread-message.sent {
            background: #e3f2fd;
            margin-left: 2rem;
        }

        .thread-message.received {
            background: #f5f5f5;
            margin-right: 2rem;
        }

        @media (max-width: 768px) {
            .main-container {
                padding: 1rem;
            }

            .message-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.5rem;
            }

            .message-actions {
                flex-wrap: wrap;
            }
        }

        .fade-in {
            animation: fadeIn 0.5s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard.php">
                <i class="fas fa-graduation-cap"></i> ISNM Communication
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">
                            <i class="fas fa-tachometer-alt"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="student_communication_system.php">
                            <i class="fas fa-envelope"></i> Messages
                            <?php if ($_SESSION['role'] === 'Student' && $unread_count > 0): ?>
                                <span class="notification-badge"><?php echo $unread_count; ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <?php if ($_SESSION['role'] !== 'Student'): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="student_accounts_management.php">
                                <i class="fas fa-users"></i> Students
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($_SESSION['first_name'] . ' ' . $_SESSION['last_name']); ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="logout.php">
                                <i class="fas fa-sign-out-alt"></i> Logout
                            </a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="main-container">
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="h3 mb-3">
                <i class="fas fa-envelope text-primary"></i> Student Communication System
            </h1>
            <p class="text-muted mb-0">Send and receive messages with students</p>
        </div>

        <?php if ($_SESSION['role'] !== 'Student'): ?>
            <!-- Compose Message Section -->
            <div class="content-section">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2 class="section-title">
                        <i class="fas fa-paper-plane"></i> Compose Message
                    </h2>
                    <div>
                        <button class="btn btn-primary" onclick="toggleComposeForm()">
                            <i class="fas fa-plus"></i> New Message
                        </button>
                        <button class="btn btn-success" onclick="toggleBulkForm()">
                            <i class="fas fa-users"></i> Bulk Message
                        </button>
                    </div>
                </div>

                <!-- Individual Message Form -->
                <div id="composeForm" class="compose-form" style="display: none;">
                    <h4 class="mb-3">Send Individual Message</h4>
                    <form method="POST" action="student_communication_system.php">
                        <input type="hidden" name="action" value="send_message">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Student ID *</label>
                                <input type="text" class="form-control" name="student_id" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Subject *</label>
                                <input type="text" class="form-control" name="subject" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Message Type *</label>
                                <select class="form-select" name="message_type" required>
                                    <option value="">Select Type</option>
                                    <option value="general">General Information</option>
                                    <option value="academic">Academic</option>
                                    <option value="financial">Financial</option>
                                    <option value="administrative">Administrative</option>
                                    <option value="emergency">Emergency</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Priority</label>
                                <select class="form-select" name="priority">
                                    <option value="normal">Normal</option>
                                    <option value="important">Important</option>
                                    <option value="urgent">Urgent</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Message *</label>
                                <textarea class="form-control" name="message" rows="4" required></textarea>
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-paper-plane"></i> Send Message
                                </button>
                                <button type="button" class="btn btn-secondary" onclick="toggleComposeForm()">
                                    <i class="fas fa-times"></i> Cancel
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Bulk Message Form -->
                <div id="bulkForm" class="compose-form" style="display: none;">
                    <h4 class="mb-3">Send Bulk Message</h4>
                    <form method="POST" action="student_communication_system.php">
                        <input type="hidden" name="action" value="send_bulk_message">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">Select Students *</label>
                                <div class="student-selector">
                                    <?php foreach ($all_students as $student): ?>
                                        <div class="student-checkbox">
                                            <input type="checkbox" name="recipients[]" value="<?php echo $student['student_id']; ?>" id="student_<?php echo $student['student_id']; ?>">
                                            <label for="student_<?php echo $student['student_id']; ?>">
                                                <?php echo htmlspecialchars($student['surname'] . ', ' . $student['first_name']); ?> (<?php echo $student['student_id']; ?>)
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Subject *</label>
                                <input type="text" class="form-control" name="subject" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Message Type *</label>
                                <select class="form-select" name="message_type" required>
                                    <option value="">Select Type</option>
                                    <option value="general">General Information</option>
                                    <option value="academic">Academic</option>
                                    <option value="financial">Financial</option>
                                    <option value="administrative">Administrative</option>
                                    <option value="emergency">Emergency</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Priority</label>
                                <select class="form-select" name="priority">
                                    <option value="normal">Normal</option>
                                    <option value="important">Important</option>
                                    <option value="urgent">Urgent</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Message *</label>
                                <textarea class="form-control" name="message" rows="4" required></textarea>
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-success">
                                    <i class="fas fa-paper-plane"></i> Send Bulk Message
                                </button>
                                <button type="button" class="btn btn-secondary" onclick="toggleBulkForm()">
                                    <i class="fas fa-times"></i> Cancel
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        <?php endif; ?>

        <!-- Messages Section -->
        <div class="content-section">
            <h2 class="section-title">
                <i class="fas fa-inbox"></i> Messages
                <?php if ($_SESSION['role'] === 'Student' && $unread_count > 0): ?>
                    <span class="badge bg-danger"><?php echo $unread_count; ?> Unread</span>
                <?php endif; ?>
            </h2>

            <div class="messages-list">
                <?php if (empty($messages)): ?>
                    <div class="text-center py-4">
                        <i class="fas fa-envelope fa-3x text-muted mb-3"></i>
                        <p class="text-muted">No messages found</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($messages as $message): ?>
                        <div class="message-item <?php echo ($message['status'] === 'sent' && $_SESSION['role'] === 'Student') ? 'unread' : ''; ?>" onclick="viewMessage(<?php echo $message['id']; ?>)">
                            <div class="message-header">
                                <div class="message-sender">
                                    <?php if ($_SESSION['role'] === 'Student'): ?>
                                        <?php echo htmlspecialchars($message['sender_role']); ?>
                                    <?php else: ?>
                                        <?php echo htmlspecialchars($message['surname'] . ', ' . $message['first_name']); ?> (<?php echo $message['student_id']; ?>)
                                    <?php endif; ?>
                                </div>
                                <div class="message-date">
                                    <?php echo formatDate($message['sent_date']); ?>
                                    <?php if ($message['priority'] === 'urgent'): ?>
                                        <i class="fas fa-exclamation-circle text-danger"></i>
                                    <?php elseif ($message['priority'] === 'important'): ?>
                                        <i class="fas fa-exclamation-triangle text-warning"></i>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="message-subject">
                                <?php echo htmlspecialchars($message['subject']); ?>
                                <span class="message-type-badge type-<?php echo $message['message_type']; ?>">
                                    <?php echo ucfirst($message['message_type']); ?>
                                </span>
                            </div>
                            <div class="message-preview">
                                <?php echo substr(htmlspecialchars($message['message_content']), 0, 150); ?>
                                <?php if (strlen($message['message_content']) > 150): ?>
                                    ...
                                <?php endif; ?>
                            </div>
                            <div class="message-actions">
                                <button class="btn btn-sm btn-primary" onclick="event.stopPropagation(); viewMessage(<?php echo $message['id']; ?>)">
                                    <i class="fas fa-eye"></i> View
                                </button>
                                <?php if ($_SESSION['role'] !== 'Student'): ?>
                                    <button class="btn btn-sm btn-info" onclick="event.stopPropagation(); replyToMessage(<?php echo $message['id']; ?>)">
                                        <i class="fas fa-reply"></i> Reply
                                    </button>
                                <?php endif; ?>
                                <button class="btn btn-sm btn-danger" onclick="event.stopPropagation(); deleteMessage(<?php echo $message['id']; ?>)">
                                    <i class="fas fa-trash"></i> Delete
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Message View Modal -->
    <div class="modal fade" id="messageViewModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-gradient text-white" style="background: linear-gradient(135deg, #17a2b8, #20c997);">
                    <h5 class="modal-title">
                        <i class="fas fa-envelope"></i> Message Details
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="messageContent">
                        <!-- Message content will be loaded here -->
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <?php if ($_SESSION['role'] !== 'Student'): ?>
                        <button type="button" class="btn btn-info" onclick="replyToMessage(currentMessageId)">
                            <i class="fas fa-reply"></i> Reply
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Reply Modal -->
    <div class="modal fade" id="replyModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-gradient text-white" style="background: linear-gradient(135deg, #ffc107, #ff9800);">
                    <h5 class="modal-title">
                        <i class="fas fa-reply"></i> Reply to Message
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="student_communication_system.php">
                    <input type="hidden" name="action" value="reply_message">
                    <input type="hidden" id="original_message_id" name="original_message_id">
                    <input type="hidden" id="reply_student_id" name="student_id">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Reply Message *</label>
                            <textarea class="form-control" name="reply_message" rows="4" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-warning">
                            <i class="fas fa-paper-plane"></i> Send Reply
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        let currentMessageId = null;

        // Toggle compose form
        function toggleComposeForm() {
            const form = document.getElementById('composeForm');
            const bulkForm = document.getElementById('bulkForm');
            
            form.style.display = form.style.display === 'none' ? 'block' : 'none';
            bulkForm.style.display = 'none';
        }

        // Toggle bulk form
        function toggleBulkForm() {
            const form = document.getElementById('bulkForm');
            const composeForm = document.getElementById('composeForm');
            
            form.style.display = form.style.display === 'none' ? 'block' : 'none';
            composeForm.style.display = 'none';
        }

        // View message
        function viewMessage(messageId) {
            fetch('includes/ajax_get_message.php?id=' + messageId)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        currentMessageId = messageId;
                        document.getElementById('messageContent').innerHTML = formatMessageContent(data.message);
                        
                        // Mark as read if student
                        <?php if ($_SESSION['role'] === 'Student'): ?>
                        if (data.message.status === 'sent') {
                            markAsRead(messageId);
                        }
                        <?php endif; ?>
                        
                        const modal = new bootstrap.Modal(document.getElementById('messageViewModal'));
                        modal.show();
                    } else {
                        alert('Error loading message');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error loading message');
                });
        }

        // Format message content
        function formatMessageContent(message) {
            let html = `
                <div class="message-thread">
                    <div class="thread-message ${message.sender_role === 'Student' ? 'received' : 'sent'}">
                        <div class="message-header">
                            <strong>From: ${message.sender_role}</strong>
                            <span class="message-date">${formatDate(message.sent_date)}</span>
                        </div>
                        <div class="message-subject">${message.subject}</div>
                        <div class="message-content">${message.message_content.replace(/\n/g, '<br>')}</div>
                    </div>
                </div>
            `;
            
            return html;
        }

        // Reply to message
        function replyToMessage(messageId) {
            document.getElementById('original_message_id').value = messageId;
            
            // Get message details
            fetch('includes/ajax_get_message.php?id=' + messageId)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('reply_student_id').value = data.message.student_id;
                        
                        const viewModal = bootstrap.Modal.getInstance(document.getElementById('messageViewModal'));
                        viewModal.hide();
                        
                        const replyModal = new bootstrap.Modal(document.getElementById('replyModal'));
                        replyModal.show();
                    }
                });
        }

        // Mark message as read
        function markAsRead(messageId) {
            fetch('student_communication_system.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=mark_read&message_id=' + messageId
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update unread count
                    const badge = document.querySelector('.notification-badge');
                    if (badge) {
                        const currentCount = parseInt(badge.textContent);
                        if (currentCount > 1) {
                            badge.textContent = currentCount - 1;
                        } else {
                            badge.style.display = 'none';
                        }
                    }
                }
            });
        }

        // Delete message
        function deleteMessage(messageId) {
            if (confirm('Are you sure you want to delete this message? This action cannot be undone.')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'student_communication_system.php';
                
                const actionInput = document.createElement('input');
                actionInput.type = 'hidden';
                actionInput.name = 'action';
                actionInput.value = 'delete_message';
                
                const messageIdInput = document.createElement('input');
                messageIdInput.type = 'hidden';
                messageIdInput.name = 'message_id';
                messageIdInput.value = messageId;
                
                form.appendChild(actionInput);
                form.appendChild(messageIdInput);
                
                document.body.appendChild(form);
                form.submit();
            }
        }

        // Format date helper
        function formatDate(dateString) {
            const date = new Date(dateString);
            return date.toLocaleDateString('en-US', { 
                year: 'numeric', 
                month: 'short', 
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        }

        // Auto-hide alerts
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                if (alert.style.display !== 'none') {
                    alert.style.transition = 'opacity 0.5s';
                    alert.style.opacity = '0';
                    setTimeout(() => alert.remove(), 500);
                }
            });
        }, 5000);
    </script>
</body>
</html>

<?php
// Display alerts
if (isset($_SESSION['success'])) {
    echo '<div class="alert alert-success alert-dismissible fade show position-fixed" style="top: 20px; right: 20px; z-index: 9999;">
        <i class="fas fa-check-circle"></i> ' . htmlspecialchars($_SESSION['success']) . '
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>';
    unset($_SESSION['success']);
}

if (isset($_SESSION['error'])) {
    echo '<div class="alert alert-danger alert-dismissible fade show position-fixed" style="top: 20px; right: 20px; z-index: 9999;">
        <i class="fas fa-exclamation-triangle"></i> ' . htmlspecialchars($_SESSION['error']) . '
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>';
    unset($_SESSION['error']);
}
?>
