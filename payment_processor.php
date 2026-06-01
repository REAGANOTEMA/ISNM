<?php
/**
 * Mobile Money Payment Processor - Integration for MTN MoMo & Airtel Money
 */
require_once __DIR__ . '/includes/financial_functions.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

class PaymentProcessor {
    
    private $mtn_api_url = 'https://api.mtn.com/v1';
    private $airtel_api_url = 'https://api.airtel.com/v2';
    
    /**
     * Process MTN Mobile Money Payment
     */
    public function processMTNPayment($phone, $amount, $reference, $student_id) {
        // In production, integrate with actual MTN MoMo API
        // This is a placeholder implementation
        
        $payment_data = [
            'phone' => $phone,
            'amount' => $amount,
            'reference' => $reference,
            'student_id' => $student_id,
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        // Generate payment reference
        $payment_reference = generatePaymentReference();
        
        $conn = getConnection();
        $stmt = $conn->prepare(" 
            INSERT INTO payments (
                payment_reference, student_id, amount, invoice_id,
                payment_method, payment_provider, reference_number,
                payment_date, status
            ) VALUES (?, ?, ?, ?, 'mobile_money', 'mtn_momo', ?, NOW(), 'pending')
        ");
        $stmt->bind_param("sidiss", 
            $payment_reference,
            $student_id,
            $amount,
            $invoice_id,
            $reference,
            $phone
        );
        
        if ($stmt->execute()) {
            return [
                'success' => true,
                'payment_reference' => $payment_reference,
                'message' => 'Payment initiated successfully. Please confirm on your phone.'
            ];
        }
        
        return ['success' => false, 'message' => 'Payment failed'];
    }
    
    /**
     * Process Airtel Money Payment
     */
    public function processAirtelPayment($phone, $amount, $reference, $student_id) {
        $payment_reference = generatePaymentReference();
        
        $conn = getConnection();
        $stmt = $conn->prepare(" 
            INSERT INTO payments (
                payment_reference, student_id, amount, invoice_id,
                payment_method, payment_provider, reference_number,
                payment_date, status
            ) VALUES (?, ?, ?, ?, 'mobile_money', 'airtel_money', ?, NOW(), 'pending')
        ");
        $stmt->bind_param("sidiss",
            $payment_reference,
            $student_id,
            $amount,
            $invoice_id,
            $reference,
            $phone
        );
        
        if ($stmt->execute()) {
            return [
                'success' => true,
                'payment_reference' => $payment_reference,
                'message' => 'Payment initiated successfully. Please confirm on your phone.'
            ];
        }
        
        return ['success' => false, 'message' => 'Payment failed'];
    }

    public function processBankDeposit($bankName, $accountNumber, $amount, $reference, $student_id, $invoice_id = null) {
        $payment_reference = generatePaymentReference();

        $conn = getConnection();
        $stmt = $conn->prepare(" 
            INSERT INTO payments (
                payment_reference, student_id, amount,
                payment_method, payment_provider, reference_number,
                account_number, payment_date, status
            ) VALUES (?, ?, ?, 'bank_deposit', 'bank', ?, ?, NOW(), 'pending')
        ");
        $stmt->bind_param("sidss", 
            $payment_reference,
            $student_id,
            $amount,
            $reference,
            $accountNumber
        );

        if ($stmt->execute()) {
            return [
                'success' => true,
                'payment_reference' => $payment_reference,
                'message' => 'Bank deposit recorded. Your payment will be verified by the finance office.'
            ];
        }

        return ['success' => false, 'message' => 'Bank deposit registration failed'];
    }
    
    /**
     * Verify Payment Status
     */
    public function verifyPayment($payment_reference) {
        $conn = getConnection();
        $stmt = $conn->prepare("
            UPDATE payments 
            SET status = 'verified', 
                processed_by = ?
            WHERE payment_reference = ? AND status = 'pending'
        ");
        
        $processed_by = $_SESSION['user_id'] ?? 0;
        $stmt->bind_param("is", $processed_by, $payment_reference);
        
        if ($stmt->execute()) {
            // Update invoice balance
            $this->updateRelatedInvoice($payment_reference);
            return ['success' => true, 'message' => 'Payment verified'];
        }
        
        return ['success' => false, 'message' => 'Verification failed'];
    }
    
    private function updateRelatedInvoice($payment_reference) {
        $conn = getConnection();
        
        $stmt = $conn->prepare("
            SELECT invoice_id FROM payments WHERE payment_reference = ?
        ");
        $stmt->bind_param("s", $payment_reference);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            updateStudentInvoiceBalance($row['invoice_id']);
        }
    }
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $processor = new PaymentProcessor();
    
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'initiate_mtn_payment':
                $result = $processor->processMTNPayment(
                    $_POST['phone'],
                    $_POST['amount'],
                    $_POST['reference'],
                    $_POST['student_id']
                );
                echo json_encode($result);
                break;
                
            case 'initiate_airtel_payment':
                $result = $processor->processAirtelPayment(
                    $_POST['phone'],
                    $_POST['amount'],
                    $_POST['reference'],
                    $_POST['student_id']
                );
                echo json_encode($result);
                break;
            case 'initiate_bank_payment':
                $result = $processor->processBankDeposit(
                    $_POST['bank_name'],
                    $_POST['account_number'],
                    $_POST['amount'],
                    $_POST['reference'],
                    $_POST['student_id'],
                    $_POST['invoice_id'] ?? null
                );
                echo json_encode($result);
                break;
                
            case 'verify_payment':
                $result = $processor->verifyPayment($_POST['payment_reference']);
                echo json_encode($result);
                break;
        }
    }
}