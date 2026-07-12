<?php
/**
 * AJAX endpoint for student self-service payment requests
 */
require_once __DIR__ . '/staff_dashboard_access.php';
$ctx = bootstrapStaffDashboard(['bursar','finance','director','registrar']);
$user = $ctx['user'];
$staffDb = $ctx['staff'];
$studentsDb = $ctx['students'];

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$action = $_POST['action'] ?? '';
$studentId = $_POST['student_id'] ?? ($user['student_number'] ?? '');
$amount = (float)($_POST['amount'] ?? 0);
$method = $_POST['payment_method'] ?? '';
$phone = $_POST['phone'] ?? '';
$notes = $_POST['notes'] ?? '';
$proofFile = '';

if (!$studentId || $amount < 1000) {
    echo json_encode(['success' => false, 'message' => 'Invalid student ID or amount (minimum UGX 1,000)']);
    exit;
}

if ($action === 'student_payment_request') {
    try {
        if (isset($_FILES['proof']) && $_FILES['proof']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/../uploads/payments/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            $ext = pathinfo($_FILES['proof']['name'], PATHINFO_EXTENSION);
            $proofFile = 'payment_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            move_uploaded_file($_FILES['proof']['tmp_name'], $uploadDir . $proofFile);
        }

        $reference = 'REQ-' . strtoupper(bin2hex(random_bytes(4))) . '-' . date('YmdHis');
        $status = 'pending';

        $stmt = $studentsDb->prepare("INSERT INTO payments (student_id, amount, payment_method, transaction_id, mobile_number, proof_file, notes, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param("isssssss", $studentId, $amount, $method, $reference, $phone, $proofFile, $notes, $status);
        if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };

        $paymentId = $studentsDb->insert_id;

        if (function_exists('createNotification')) {
            $msg = "New payment request of UGX " . number_format($amount) . " from student #$studentId via $method";
            $nid = createNotification('Payment Request', $msg, '', 'payment', 'fas fa-money-bill');
            if ($nid && function_exists('notifyAllStaff')) {
                notifyAllStaff($nid);
            }
        }

        echo json_encode(['success' => true, 'reference' => $reference, 'payment_id' => $paymentId]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Unknown action']);
}
