<?php
// Simple AJAX endpoint to send a message to a student (stores in DB and optionally emails)
header('Content-Type: application/json');

require_once $_SERVER['DOCUMENT_ROOT'] . '/ISNM/includes/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/ISNM/includes/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$student_id = $_POST['student_id'] ?? '';
$message = trim($_POST['message'] ?? '');

if (empty($student_id) || empty($message)) {
    echo json_encode(['success' => false, 'message' => 'Student ID and message are required']);
    exit();
}

// Lookup student contact info
$stmt = $conn->prepare("SELECT student_id AS sid, full_name, email, phone FROM students WHERE student_id = ? LIMIT 1");
$stmt->bind_param('s', $student_id);
$stmt->execute();
$res = $stmt->get_result();
$student = $res->fetch_assoc();

if (!$student) {
    echo json_encode(['success' => false, 'message' => 'Student not found']);
    exit();
}

// Insert into messages table if exists, otherwise into activity log
try {
    $inserted = false;
    $db = $conn; // from includes/config.php

    // Attempt to insert into messages table if it exists
    $check = $db->query("SHOW TABLES LIKE 'messages'");
    if ($check && $check->num_rows > 0) {
        $ins = $db->prepare("INSERT INTO messages (sender_id, recipient_student_id, message, sent_at) VALUES (?, ?, ?, NOW())");
        $sender = $_SESSION['user_id'];
        $ins->bind_param('iss', $sender, $student['sid'], $message);
        $ins->execute();
        $inserted = $ins->affected_rows > 0;
    } else {
        // fallback to activity_logs
        $log_stmt = $db->prepare("INSERT INTO activity_logs (user_id, user_role, activity_type, activity_description, module_affected, record_id, ip_address, user_agent, activity_date) VALUES (?, ?, 'Message Sent', ?, 'messaging', ?, ?, ?, ?, NOW())");
        $user_role = $_SESSION['role'] ?? '';
        $desc = 'Sent message to ' . ($student['full_name'] ?? $student['sid']) . ': ' . substr($message, 0, 200);
        $rec_id = $student['sid'];
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $log_stmt->bind_param('isssisss', $_SESSION['user_id'], $user_role, $desc, $rec_id, $ip, $ua, $_SESSION['user_id']);
        @$log_stmt->execute();
        $inserted = true;
    }

    // Try sending email if student has an email
    if (!empty($student['email']) && validateEmail($student['email'])) {
        $subject = 'Message from ISNM';
        $body = "Hello " . htmlspecialchars($student['full_name']) . ",<br><br>" . nl2br(htmlspecialchars($message)) . "<br><br>Regards,<br>ISNM";
        @sendEmail($student['email'], $subject, $body);
    }

    echo json_encode(['success' => true, 'message' => 'Message sent']);
    exit();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
    exit();
}

?>
