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
    $course_ids = $_POST['course_ids'] ?? [];
    $academic_year = trim($_POST['academic_year'] ?? date('Y'));
    $semester = trim($_POST['semester'] ?? 'Semester 1');

    if (empty($course_ids)) {
        $_SESSION['error'] = 'Please select at least one course.';
        header('Location: student-portal.php?page=courses');
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
    header('Location: student-portal.php?page=courses');
    exit();

case 'request_transcript':
    $transcript_number = 'TRN-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
    $stmt = $studentsDb->prepare("INSERT INTO student_transcripts (student_id, transcript_number, request_type, purpose, status, requested_by, created_at) VALUES (?, ?, 'Official', 'Student self-request', 'Requested', ?, NOW())");
    if ($stmt) {
        $stmt->bind_param("isi", $student_id, $transcript_number, $student_id);
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => "Transcript request submitted. Number: $transcript_number"]);
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
    $r = $studentsDb->query("SELECT COALESCE(SUM(amount),0) as total, COALESCE(SUM(amount_paid),0) as paid FROM student_fee_tracking WHERE student_id=$student_id");
    if ($r && $r->num_rows) {
        $row = $r->fetch_assoc();
        $total_fees = (float)$row['total'];
        $total_paid = (float)$row['paid'];
        $balance = $total_fees - $total_paid;
    }
    echo json_encode(['success' => true, 'total_fees' => $total_fees, 'total_paid' => $total_paid, 'balance' => $balance]);
    exit;

case 'get_attendance_summary':
    $total = 0;
    $present = 0;
    $pct = 0;
    $r = $studentsDb->query("SELECT COUNT(*) as total, COUNT(CASE WHEN status='Present' THEN 1 END) as present FROM student_attendance WHERE student_id=$student_id");
    if ($r && $r->num_rows) {
        $row = $r->fetch_assoc();
        $total = (int)$row['total'];
        $present = (int)$row['present'];
        $pct = $total > 0 ? round($present * 100 / $total, 1) : 0;
    }
    echo json_encode(['success' => true, 'total' => $total, 'present' => $present, 'percentage' => $pct]);
    exit;

case 'get_notifications':
    $notifs = [];
    $r = $studentsDb->query("SELECT * FROM student_notifications WHERE student_id=$student_id AND is_read=0 ORDER BY created_at DESC LIMIT 10");
    if ($r) while ($row = $r->fetch_assoc()) $notifs[] = $row;
    $count = count($notifs);
    echo json_encode(['success' => true, 'count' => $count, 'notifications' => $notifs]);
    exit;

case 'mark_notification_read':
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
    echo json_encode(['success' => false, 'error' => 'Unknown action: ' . $action]);
    exit;
}
