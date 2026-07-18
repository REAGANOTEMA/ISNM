<?php
/**
 * Mobile Money Payment Processor - Integration for MTN MoMo & Airtel Money
 */
require_once __DIR__ . '/includes/config_enhanced.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/financial_functions.php';
require_once __DIR__ . '/includes/email_notifications.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

class PaymentProcessor {
    
    private $mtn_api_url = 'https://api.mtn.com/v1';
    private $airtel_api_url = 'https://api.airtel.com/v2';
    
    private function sanitize($input) {
        if (is_string($input)) {
            return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
        }
        return $input;
    }

    private function validateAmount($amount) {
        return is_numeric($amount) && (float)$amount > 0;
    }

    private function logActivity($activityType, $description, $ip = '') {
        try {
            $staffsDb = getStaffConnection();
            if ($staffsDb) {
                $stmt = $staffsDb->prepare("INSERT INTO activity_log (user_id, activity, details, ip_address, created_at) VALUES (?, ?, ?, ?, NOW())");
                $userId = $_SESSION['user_id'] ?? 0;
                $stmt->bind_param('isss', $userId, $activityType, $description, $ip);
                if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
                $stmt->close();
            }
        } catch (Exception $e) {
            error_log('Payment activity log error: ' . $e->getMessage());
        }
    }

    private function createNotification($title, $message, $priority = 'normal') {
        try {
            $staffsDb = getStaffConnection();
            if ($staffsDb) {
                $userId = $_SESSION['user_id'] ?? 0;
                $stmt = $staffsDb->prepare("INSERT INTO staff_notifications (staff_id, title, message, type, is_read, created_at) VALUES (?, ?, ?, 'payment', 0, NOW())");
                if ($stmt) {
                    $staffId = $userId;
                    $stmt->bind_param('iss', $staffId, $title, $message);
                    if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
                    $stmt->close();
                }
            }
        } catch (Exception $e) {
            error_log('Payment notification error: ' . $e->getMessage());
        }
    }

    public function processMTNPayment($phone, $amount, $reference, $student_id, $invoiceId = null) {
        $phone = $this->sanitize($phone);
        $reference = $this->sanitize($reference);
        $student_id = (int)$student_id;
        $invoice_id = $invoiceId ? (int)$invoiceId : null;
        $amount = (float)$amount;

        if (!$phone || !$this->validateAmount($amount) || !$student_id) {
            return ['success' => false, 'message' => 'Invalid payment parameters'];
        }

        $payment_reference = generatePaymentReference();
        
        $conn = getStudentsConnection();
        $stmt = $conn->prepare(" 
            INSERT INTO payments (
                payment_reference, student_id, amount_received, invoice_id,
                payment_method, transaction_ref,
                payment_date, status
            ) VALUES (?, ?, ?, ?, 'Mobile Money', ?, NOW(), 'Pending')
        ");
        $stmt->bind_param("sidss", 
            $payment_reference,
            $student_id,
            $amount,
            $invoice_id,
            $reference
        );
        
        if ($stmt->execute()) {
            $this->logActivity('MTN Payment Initiated', 'Payment ' . $payment_reference . ' of UGX ' . number_format($amount, 0) . ' via MTN MoMo for student #' . $student_id);
            $this->createNotification('MTN MoMo Payment Initiated', 'Payment ' . $payment_reference . ' of UGX ' . number_format($amount, 0) . ' initiated by ' . $phone, 'normal');

            return [
                'success' => true,
                'payment_reference' => $payment_reference,
                'message' => 'Payment initiated successfully. Please confirm on your phone.'
            ];
        }
        
        return ['success' => false, 'message' => 'Payment failed'];
    }
    
    public function processAirtelPayment($phone, $amount, $reference, $student_id, $invoiceId = null) {
        $phone = $this->sanitize($phone);
        $reference = $this->sanitize($reference);
        $student_id = (int)$student_id;
        $invoice_id = $invoiceId ? (int)$invoiceId : null;
        $amount = (float)$amount;

        if (!$phone || !$this->validateAmount($amount) || !$student_id) {
            return ['success' => false, 'message' => 'Invalid payment parameters'];
        }

        $payment_reference = generatePaymentReference();
        
        $conn = getStudentsConnection();
        $stmt = $conn->prepare(" 
            INSERT INTO payments (
                payment_reference, student_id, amount_received, invoice_id,
                payment_method, transaction_ref,
                payment_date, status
            ) VALUES (?, ?, ?, ?, 'Mobile Money', ?, NOW(), 'Pending')
        ");
        $stmt->bind_param("sidss",
            $payment_reference,
            $student_id,
            $amount,
            $invoice_id,
            $reference
        );
        
        if ($stmt->execute()) {
            $this->logActivity('Airtel Payment Initiated', 'Payment ' . $payment_reference . ' of UGX ' . number_format($amount, 0) . ' via Airtel Money for student #' . $student_id);
            $this->createNotification('Airtel Money Payment Initiated', 'Payment ' . $payment_reference . ' of UGX ' . number_format($amount, 0) . ' initiated by ' . $phone, 'normal');

            return [
                'success' => true,
                'payment_reference' => $payment_reference,
                'message' => 'Payment initiated successfully. Please confirm on your phone.'
            ];
        }
        
        return ['success' => false, 'message' => 'Payment failed'];
    }

    public function processBankDeposit($bankName, $accountNumber, $amount, $reference, $student_id, $invoice_id = null) {
        $bankName = $this->sanitize($bankName);
        $accountNumber = $this->sanitize($accountNumber);
        $reference = $this->sanitize($reference);
        $student_id = (int)$student_id;
        $invoice_id = $invoice_id ? (int)$invoice_id : null;
        $amount = (float)$amount;

        if (!$bankName || !$this->validateAmount($amount) || !$student_id) {
            return ['success' => false, 'message' => 'Invalid bank deposit parameters'];
        }

        $payment_reference = generatePaymentReference();

        $conn = getStudentsConnection();
        $stmt = $conn->prepare(" 
            INSERT INTO payments (
                payment_reference, student_id, amount_received, invoice_id,
                payment_method, transaction_ref, notes,
                payment_date, status
            ) VALUES (?, ?, ?, ?, 'Bank Transfer', ?, ?, NOW(), 'Pending')
        ");
        $notes = 'Bank: ' . $bankName . ', Account: ' . $accountNumber;
        $stmt->bind_param("sidsss", 
            $payment_reference,
            $student_id,
            $amount,
            $invoice_id,
            $reference,
            $notes
        );

        if ($stmt->execute()) {
            $this->logActivity('Bank Deposit Initiated', 'Payment ' . $payment_reference . ' of UGX ' . number_format($amount, 0) . ' via bank deposit (' . $bankName . ') for student #' . $student_id);
            $this->createNotification('Bank Deposit Recorded', 'Payment ' . $payment_reference . ' of UGX ' . number_format($amount, 0) . ' via ' . $bankName . '. Verification pending.', 'normal');

            return [
                'success' => true,
                'payment_reference' => $payment_reference,
                'message' => 'Bank deposit recorded. Your payment will be verified by the finance office.'
            ];
        }

        return ['success' => false, 'message' => 'Bank deposit registration failed'];
    }
    
    public function verifyPayment($payment_reference) {
        $payment_reference = $this->sanitize($payment_reference);
        if (!$payment_reference) {
            return ['success' => false, 'message' => 'Invalid payment reference'];
        }

        $conn = getStudentsConnection();
        $stmt = $conn->prepare("
            UPDATE payments 
            SET status = 'Completed', 
                received_by = ?
            WHERE payment_reference = ? AND status = 'Pending'
        ");
        
        $processed_by = $_SESSION['user_id'] ?? 0;
        $stmt->bind_param("is", $processed_by, $payment_reference);
        
        if ($stmt->execute()) {
            if ($conn->affected_rows > 0) {
                $this->updateRelatedInvoice($payment_reference);
                $this->logActivity('Payment Verified', 'Payment ' . $payment_reference . ' verified by staff #' . $processed_by);
                $this->createNotification('Payment Verified', 'Payment ' . $payment_reference . ' has been verified and marked as completed.', 'high');
                return ['success' => true, 'message' => 'Payment verified'];
            }
            return ['success' => false, 'message' => 'Payment not found or already processed'];
        }
        
        return ['success' => false, 'message' => 'Verification failed'];
    }
    
    private function updateRelatedInvoice($payment_reference) {
        $conn = getStudentsConnection();
        
        $stmt = $conn->prepare("
            SELECT invoice_id FROM payments WHERE payment_reference = ?
        ");
        $stmt->bind_param("s", $payment_reference);
        if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            if ($row['invoice_id']) {
                updateStudentInvoiceBalance($row['invoice_id']);
            }
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    if (empty($_SESSION['user_id']) || empty($_SESSION['type'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Authentication required']);
        exit();
    }

    // CSRF validation
    $csrfToken = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (empty($csrfToken) || !isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrfToken)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Invalid security token. Please refresh and try again.']);
        exit();
    }

    $processor = new PaymentProcessor();
    
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'initiate_mtn_payment':
                $result = $processor->processMTNPayment(
                    $_POST['phone'] ?? '',
                    $_POST['amount'] ?? 0,
                    $_POST['reference'] ?? '',
                    $_POST['student_id'] ?? 0,
                    $_POST['invoice_id'] ?? null
                );
                echo json_encode($result);
                break;
                
            case 'initiate_airtel_payment':
                $result = $processor->processAirtelPayment(
                    $_POST['phone'] ?? '',
                    $_POST['amount'] ?? 0,
                    $_POST['reference'] ?? '',
                    $_POST['student_id'] ?? 0,
                    $_POST['invoice_id'] ?? null
                );
                echo json_encode($result);
                break;

            case 'initiate_bank_payment':
                $result = $processor->processBankDeposit(
                    $_POST['bank_name'] ?? '',
                    $_POST['account_number'] ?? '',
                    $_POST['amount'] ?? 0,
                    $_POST['reference'] ?? '',
                    $_POST['student_id'] ?? 0,
                    $_POST['invoice_id'] ?? null
                );
                echo json_encode($result);
                break;
                
            case 'verify_payment':
                $userRole = $_SESSION['role'] ?? '';
                if (!in_array($userRole, ['admin', 'bursar', 'finance'])) {
                    echo json_encode(['success' => false, 'message' => 'Insufficient permissions']);
                    break;
                }
                $result = $processor->verifyPayment($_POST['payment_reference'] ?? '');
                echo json_encode($result);
                break;

            case 'initiate_payment':
                // Rate limit: max 5 payment initiations per hour per user
                $userId = $_SESSION['user_id'] ?? 0;
                $rateConn = getStudentsConnection();
                if ($rateConn && $userId > 0) {
                    $rateCheck = $rateConn->prepare("SELECT COUNT(*) as cnt FROM payment_transactions WHERE user_id = ? AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)");
                    if ($rateCheck) {
                        $rateCheck->bind_param('i', $userId);
                        $rateCheck->execute();
                        $rateResult = $rateCheck->get_result();
                        $rateRow = $rateResult->fetch_assoc();
                        $rateCheck->close();
                        if ($rateRow['cnt'] >= 5) {
                            echo json_encode(['success' => false, 'message' => 'Too many payment attempts. Please try again later.']);
                            exit;
                        }
                    }
                }
                require_once __DIR__ . '/includes/payment_gateway/PaymentService.php';
                $service = new PaymentService();
                $providerKey = trim($_POST['provider'] ?? '');
                $result = $service->initiatePayment($providerKey, [
                    'amount'          => (float)($_POST['amount'] ?? 0),
                    'phone'           => trim($_POST['phone'] ?? ''),
                    'email'           => trim($_POST['email'] ?? ''),
                    'payer_name'      => trim($_POST['payer_name'] ?? ''),
                    'payment_type'    => trim($_POST['payment_type'] ?? 'student_fees'),
                    'reference_type'  => trim($_POST['reference_type'] ?? ''),
                    'reference_id'    => (int)($_POST['reference_id'] ?? 0),
                    'student_id'      => (int)($_POST['student_id'] ?? 0),
                    'currency'        => trim($_POST['currency'] ?? 'UGX'),
                    'description'     => trim($_POST['description'] ?? ''),
                    'success_url'     => trim($_POST['success_url'] ?? ''),
                    'cancel_url'      => trim($_POST['cancel_url'] ?? ''),
                    'metadata'        => ['source' => 'payment_processor'],
                    'initiated_by'    => $_SESSION['user_id'] ?? 0,
                ]);
                echo json_encode($result);
                break;

            case 'check_payment_status':
                require_once __DIR__ . '/includes/payment_gateway/PaymentService.php';
                $service = new PaymentService();
                $result = $service->checkStatus(trim($_POST['transaction_ref'] ?? ''));
                echo json_encode($result);
                break;

            default:
                echo json_encode(['success' => false, 'message' => 'Unknown action']);
                break;
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'No action specified']);
    }
}
