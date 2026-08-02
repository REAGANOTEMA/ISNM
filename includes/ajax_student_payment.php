<?php
/**
 * AJAX endpoint for student self-service payment requests
 * Supports both staff (bursar) and student authentication.
 */
session_start();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

require_once __DIR__ . '/../config/database.php';

$studentsDb = getStudentsConnection();
$action = $_POST['action'] ?? '';

$userType = $_SESSION['type'] ?? '';
$userId = (int)($_SESSION['user_id'] ?? 0);

$isStudent = ($userType === 'student');
$isStaff = ($userType === 'staff');

if (!$isStudent && !$isStaff) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

if ($isStudent) {
    $studentId = $userId;
} else {
    $studentId = (int)($_POST['student_id'] ?? 0);
}

$amount = (float)($_POST['amount'] ?? 0);
$method = $_POST['payment_method'] ?? '';
$phone = trim($_POST['phone'] ?? '');
$notes = trim($_POST['notes'] ?? '');
if ($phone !== '') {
    $notes = ($notes === '') ? 'Phone: ' . $phone : 'Phone: ' . $phone . "\n" . $notes;
}
$proofFile = '';

if ($studentId <= 0 || $amount < 1000) {
    echo json_encode(['success' => false, 'message' => 'Invalid student ID or amount (minimum UGX 1,000)']);
    exit;
}

if ($action === 'student_payment_request') {
    $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (empty($token) || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        echo json_encode(['success' => false, 'message' => 'Invalid security token']);
        exit;
    }

    try {
        if (isset($_FILES['proof']) && $_FILES['proof']['error'] === UPLOAD_ERR_OK) {
            $allowedExts = ['jpg','jpeg','png','gif','pdf','webp'];
            $allowedMimes = ['image/jpeg','image/png','image/gif','application/pdf','image/webp'];
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mimeType = $finfo->file($_FILES['proof']['tmp_name']);
            $ext = strtolower(pathinfo($_FILES['proof']['name'], PATHINFO_EXTENSION));

            if (!in_array($ext, $allowedExts, true) || !in_array($mimeType, $allowedMimes, true)) {
                echo json_encode(['success' => false, 'message' => 'Invalid file type. Allowed: JPG, PNG, GIF, PDF, WEBP']);
                exit;
            }
            if ($_FILES['proof']['size'] > 5 * 1024 * 1024) {
                echo json_encode(['success' => false, 'message' => 'File too large. Maximum 5MB allowed']);
                exit;
            }

            $uploadDir = dirname(__DIR__, 2) . '/uploads/payments/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            $safeExt = $ext === 'jpeg' ? 'jpg' : $ext;
            $proofFile = 'payment_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $safeExt;
            if (!move_uploaded_file($_FILES['proof']['tmp_name'], $uploadDir . $proofFile)) {
                echo json_encode(['success' => false, 'message' => 'Failed to save uploaded file']);
                exit;
            }
        }

        $reference = 'REQ-' . strtoupper(bin2hex(random_bytes(4))) . '-' . date('YmdHis');
        $status = 'Pending';

        $stmt = $studentsDb->prepare("INSERT INTO payments (payment_reference, student_id, amount_received, payment_method, transaction_ref, notes, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
        if (!$stmt) {
            echo json_encode(['success' => false, 'message' => 'Database error']);
            exit;
        }
        $stmt->bind_param("sidssss", $reference, $studentId, $amount, $method, $reference, $notes, $status);
        if (!$stmt->execute()) {
            error_log('student_payment execute failed: ' . ($stmt->error ?? 'unknown'));
            echo json_encode(['success' => false, 'message' => 'Failed to save payment request']);
            $stmt->close();
            exit;
        }

        $paymentId = $studentsDb->insert_id;
        $stmt->close();

        if (!empty($proofFile) && $paymentId > 0) {
            $proofNumber = 'POP-' . strtoupper(bin2hex(random_bytes(4))) . '-' . date('YmdHis');
            $popStmt = $studentsDb->prepare("INSERT INTO proof_of_payments (proof_number, payment_id, student_id, document_path, uploaded_by, verified, notes, created_at) VALUES (?, ?, ?, ?, ?, 0, ?, NOW())");
            if ($popStmt) {
                $popStmt->bind_param("siiiss", $proofNumber, $paymentId, $studentId, $proofFile, $studentId, $notes);
                $popStmt->execute();
                $popStmt->close();
            }
        }

        echo json_encode(['success' => true, 'reference' => $reference, 'payment_id' => $paymentId]);
    } catch (Exception $e) {
        error_log('student_payment error: ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'An error occurred processing your payment']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Unknown action']);
}
