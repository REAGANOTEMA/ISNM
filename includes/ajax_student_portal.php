<?php
session_start();
header('Content-Type: application/json');

if (empty($_SESSION['user_id']) || ($_SESSION['type'] ?? '') !== 'student') {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit();
}

require_once __DIR__ . '/../config/database.php';

$studentsDb = getStudentsConnection();
$student_id = (int)$_SESSION['user_id'];
$action = $_GET['action'] ?? $_POST['action'] ?? '';

if (!$studentsDb) {
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit();
}

switch ($action) {

case 'register_courses':
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['success' => false, 'error' => 'POST required']); exit(); }
    $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (empty($token) || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) { echo json_encode(['success' => false, 'error' => 'Invalid security token']); exit(); }
    $course_ids = $_POST['course_ids'] ?? [];
    $academic_year = trim($_POST['academic_year'] ?? date('Y'));
    $semester = trim($_POST['semester'] ?? 'Semester 1');

    if (empty($course_ids)) {
        echo json_encode(['success' => false, 'error' => 'Please select at least one course.']);
        exit();
    }

    $registered = 0;
    $skipped = 0;
    foreach ($course_ids as $cid) {
        $cid = (int)$cid;
        if ($cid <= 0) continue;
        $exists = $studentsDb->prepare("SELECT id FROM student_course_registrations WHERE student_id=? AND course_id=? AND academic_year=? AND semester=?");
        if ($exists) {
            $exists->bind_param("iiss", $student_id, $cid, $academic_year, $semester);
            if (!$exists->execute()) { error_log('$exists execute failed: ' . ($exists->error ?? 'unknown')); };
            if ($exists->get_result()->num_rows > 0) { $skipped++; $exists->close(); continue; }
            $exists->close();
        }
        $stmt = $studentsDb->prepare("INSERT INTO student_course_registrations (student_id, course_id, academic_year, semester, status, registration_date) VALUES (?, ?, ?, ?, 'Registered', NOW())");
        if ($stmt) {
            $stmt->bind_param("iiss", $student_id, $cid, $academic_year, $semester);
            if ($stmt->execute()) $registered++;
            $stmt->close();
        }
    }
    $_SESSION['success'] = "$registered course(s) registered." . ($skipped > 0 ? " $skipped already registered." : '');
    header('Location: ../dashboards/student-portal.php?page=courses');
    exit();

case 'request_transcript':
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['success' => false, 'error' => 'POST required']); exit(); }
    $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (empty($token) || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) { echo json_encode(['success' => false, 'error' => 'Invalid security token']); exit(); }
    $request_number = 'TRN-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
    $studentFullName = '';
    $getName = $studentsDb->prepare("SELECT CONCAT(first_name, ' ', surname) AS full_name FROM students WHERE id = ?");
    if ($getName) {
        $getName->bind_param("i", $student_id);
        if ($getName->execute()) { $nr = $getName->get_result()->fetch_assoc(); $studentFullName = $nr['full_name'] ?? ''; }
        $getName->close();
    }
    $stmt = $studentsDb->prepare("INSERT INTO registrar_transcript_requests (request_number, student_id, full_name, purpose, status, requested_by, request_date, created_at) VALUES (?, ?, ?, 'Student self-request', 'Pending', ?, NOW(), NOW())");
    if ($stmt) {
        $stmt->bind_param("siss", $request_number, $student_id, $studentFullName, $studentFullName);
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => "Transcript request submitted. Number: $request_number"]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to submit request']);
        }
        $stmt->close();
    } else {
        echo json_encode(['success' => false, 'error' => 'Database error']);
    }
    exit;

case 'get_student_data':
    $stmt = $studentsDb->prepare("SELECT id, student_number, registration_number, full_name, program, level, current_year, year, set_name, status, email, phone, date_of_birth, gender, guardian_name, guardian_phone, profile_picture FROM students WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $student_id);
        if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
        $student = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        echo json_encode(['success' => true, 'data' => $student]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Database error']);
    }
    exit;

case 'get_fee_summary':
    $total_fees = 0;
    $total_paid = 0;
    $balance = 0;
    $stmt = $studentsDb->prepare("SELECT COALESCE(SUM(amount),0) as total, COALESCE(SUM(amount_paid),0) as paid FROM student_fee_tracking WHERE student_id=?");
    if ($stmt) { $stmt->bind_param("i", $student_id); $stmt->execute(); $r = $stmt->get_result(); if ($r && $r->num_rows) { $row = $r->fetch_assoc(); $total_fees = (float)$row['total']; $total_paid = (float)$row['paid']; $balance = $total_fees - $total_paid; } $stmt->close(); }
    echo json_encode(['success' => true, 'total_fees' => $total_fees, 'total_paid' => $total_paid, 'balance' => $balance]);
    exit;

case 'get_attendance_summary':
    $total = 0;
    $present = 0;
    $pct = 0;
    $stmt = $studentsDb->prepare("SELECT COUNT(*) as total, COUNT(CASE WHEN status='Present' THEN 1 END) as present FROM student_attendance WHERE student_id=?");
    if ($stmt) { $stmt->bind_param("i", $student_id); $stmt->execute(); $r = $stmt->get_result(); if ($r && $r->num_rows) { $row = $r->fetch_assoc(); $total = (int)$row['total']; $present = (int)$row['present']; $pct = $total > 0 ? round($present * 100 / $total, 1) : 0; } $stmt->close(); }
    echo json_encode(['success' => true, 'total' => $total, 'present' => $present, 'percentage' => $pct]);
    exit;

case 'get_notifications':
    $notifs = [];
    $stmt = $studentsDb->prepare("SELECT * FROM student_notifications WHERE student_id=? AND is_read=0 ORDER BY created_at DESC LIMIT 10");
    if ($stmt) { $stmt->bind_param("i", $student_id); $stmt->execute(); $r = $stmt->get_result(); if ($r) while ($row = $r->fetch_assoc()) $notifs[] = $row; $stmt->close(); }
    $count = count($notifs);
    echo json_encode(['success' => true, 'count' => $count, 'notifications' => $notifs]);
    exit;

case 'mark_notification_read':
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['success' => false, 'error' => 'POST required']); exit(); }
    $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (empty($token) || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) { echo json_encode(['success' => false, 'error' => 'Invalid security token']); exit(); }
    $nid = (int)($_POST['notification_id'] ?? 0);
    if ($nid > 0) {
        $stmt = $studentsDb->prepare("UPDATE student_notifications SET is_read=1 WHERE id=? AND student_id=?");
        if ($stmt) {
            $stmt->bind_param("ii", $nid, $student_id);
            if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
            $stmt->close();
        }
    }
    echo json_encode(['success' => true]);
    exit;

default:
    echo json_encode(['success' => false, 'error' => 'Unknown action']);
    exit;
}
