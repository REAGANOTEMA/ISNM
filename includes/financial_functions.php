<?php
/**
 * Financial Management Functions for ISNM
 */

require_once __DIR__ . '/../config/database.php';

if (!function_exists('generateFeeInvoiceNumber')) {
    function generateFeeInvoiceNumber() {
        $conn = getConnection();
        $prefix = 'INV-' . date('Y') . '-';
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM student_invoices WHERE invoice_number LIKE ?");
        $likePattern = $prefix . '%';
        $stmt->bind_param("s", $likePattern);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $number = $result['count'] + 1;
        return $prefix . str_pad($number, 5, '0', STR_PAD_LEFT);
    }
}

if (!function_exists('generatePaymentReference')) {
    function generatePaymentReference() {
        $conn = getConnection();
        $prefix = 'PMT-' . date('Ymd') . '-';
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM payments WHERE payment_reference LIKE ?");
        $likePattern = $prefix . '%';
        $stmt->bind_param("s", $likePattern);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $number = $result['count'] + 1;
        return $prefix . str_pad($number, 4, '0', STR_PAD_LEFT);
    }
}

if (!function_exists('generateReceiptNumber')) {
    function generateReceiptNumber() {
        $conn = getConnection();
        $prefix = 'RCPT-' . date('Y') . '-';
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM payment_receipts WHERE receipt_number LIKE ?");
        $likePattern = $prefix . '%';
        $stmt->bind_param("s", $likePattern);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $number = $result['count'] + 1;
        return $prefix . str_pad($number, 5, '0', STR_PAD_LEFT);
    }
}

if (!function_exists('generateExpenseId')) {
    function generateExpenseId() {
        $conn = getConnection();
        $prefix = 'EXP-' . date('Y') . '-';
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM expenses WHERE expense_id LIKE ?");
        $likePattern = $prefix . '%';
        $stmt->bind_param("s", $likePattern);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $number = $result['count'] + 1;
        return $prefix . str_pad($number, 5, '0', STR_PAD_LEFT);
    }
}

if (!function_exists('getStudentBalance')) {
    function getStudentBalance($student_id) {
        $conn = getConnection();
        $stmt = $conn->prepare("
            SELECT COALESCE(SUM(balance), 0) as total_balance 
            FROM student_invoices 
            WHERE student_id = ? AND status IN ('pending', 'partial', 'overdue')
        ");
        $stmt->bind_param("i", $student_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        return $result['total_balance'];
    }
}

if (!function_exists('calculatePenalty')) {
    function calculatePenalty($amount, $days_late = 0, $penalty_config_id = null) {
        $conn = getConnection();
        $penalty = 0;
        
        if ($penalty_config_id) {
            $stmt = $conn->prepare("SELECT * FROM penalty_config WHERE id = ? AND status = 'active'");
            $stmt->bind_param("i", $penalty_config_id);
            $stmt->execute();
            $config = $stmt->get_result()->fetch_assoc();
            
            if ($config) {
                switch ($config['calculation_method']) {
                    case 'fixed_amount':
                        $penalty = $config['fixed_amount'];
                        break;
                    case 'percentage':
                        $penalty = ($amount * $config['percentage_value']) / 100;
                        break;
                    case 'daily':
                        $penalty = $config['daily_rate'] * $days_late;
                        break;
                }
                
                if ($config['max_penalty_amount'] > 0 && $penalty > $config['max_penalty_amount']) {
                    $penalty = $config['max_penalty_amount'];
                }
            }
        }
        
        return $penalty;
    }
}

if (!function_exists('updateStudentInvoiceBalance')) {
    function updateStudentInvoiceBalance($invoice_id) {
        $conn = getConnection();
        $stmt = $conn->prepare("
            SELECT si.total_amount, COALESCE(SUM(p.amount), 0) as paid
            FROM student_invoices si
            LEFT JOIN payments p ON si.id = p.invoice_id AND p.status IN ('verified', 'approved')
            WHERE si.id = ?
            GROUP BY si.id
        ");
        $stmt->bind_param("i", $invoice_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        
        if ($result) {
            $total = $result['total_amount'];
            $paid = $result['paid'];
            $balance = $total - $paid;
            
            $update_stmt = $conn->prepare("
                UPDATE student_invoices 
                SET amount_paid = ?, balance = ?, 
                    status = CASE 
                        WHEN ? >= ? THEN 'paid' 
                        WHEN ? > 0 THEN 'partial' 
                        ELSE status 
                    END
                WHERE id = ?
            ");
            $update_stmt->bind_param("dddddi", $paid, $balance, $paid, $total, $paid, $invoice_id);
            $update_stmt->execute();
        }
    }
}

if (!function_exists('getTotalCollections')) {
    function getTotalCollections($period = 'today') {
        $conn = getConnection();
        $where_clause = "";
        $current_date = date('Y-m-d');
        
        switch ($period) {
            case 'today':
                $where_clause = "WHERE DATE(transaction_date) = '$current_date' AND status IN ('verified', 'approved')";
                break;
            case 'week':
                $where_clause = "WHERE YEARWEEK(transaction_date) = YEARWEEK(NOW()) AND status IN ('verified', 'approved')";
                break;
            case 'month':
                $where_clause = "WHERE MONTH(transaction_date) = MONTH(NOW()) AND YEAR(transaction_date) = YEAR(NOW()) AND status IN ('verified', 'approved')";
                break;
        }
        
        $stmt = $conn->query("SELECT COALESCE(SUM(amount), 0) as total FROM payments $where_clause");
        return $stmt->fetch_assoc()['total'];
    }
}

if (!function_exists('getOutstandingFees')) {
    function getOutstandingFees() {
        $conn = getConnection();
        $stmt = $conn->query("SELECT COALESCE(SUM(balance), 0) as total FROM student_invoices WHERE status IN ('pending', 'partial', 'overdue')");
        return $stmt->fetch_assoc()['total'];
    }
}

if (!function_exists('logFinancialActivity')) {
    function logFinancialActivity($action_type, $table_name, $record_id, $old_values = null, $new_values = null) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $conn = getConnection();
        $stmt = $conn->prepare("
            INSERT INTO financial_audit_log 
            (action_type, table_name, record_id, old_values, new_values, user_id, user_role, ip_address) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $user_id = $_SESSION['user_id'] ?? 0;
        $user_role = $_SESSION['role'] ?? '';
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
        
        $stmt->bind_param(
            "ssisssss",
            $action_type,
            $table_name,
            $record_id,
            json_encode($old_values),
            json_encode($new_values),
            $user_id,
            $user_role,
            $ip_address
        );
        $stmt->execute();
    }
}

if (!function_exists('generateFinancialStatement')) {
    function generateFinancialStatement($student_id) {
        $conn = getConnection();
        
        $stmt = $conn->prepare("
            SELECT 
                si.invoice_number,
                si.academic_year,
                si.semester,
                si.total_amount,
                si.amount_paid,
                si.balance,
                si.status,
                si.due_date
            FROM student_invoices si
            WHERE si.student_id = ?
            ORDER BY si.created_at DESC
        ");
        $stmt->bind_param("i", $student_id);
        $stmt->execute();
        $invoices = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        $stmt = $conn->prepare("
            SELECT 
                p.payment_reference,
                p.amount,
                p.payment_method,
                p.payment_date,
                p.status
            FROM payments p
            WHERE p.student_id = ?
            ORDER BY p.payment_date DESC
        ");
        $stmt->bind_param("i", $student_id);
        $stmt->execute();
        $payments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        return [
            'invoices' => $invoices,
            'payments' => $payments,
            'total_invoiced' => array_sum(array_column($invoices, 'total_amount')),
            'total_paid' => array_sum(array_column($payments, 'amount')),
            'current_balance' => getStudentBalance($student_id)
        ];
    }
}

if (!function_exists('getPaymentProviderLogo')) {
    function getPaymentProviderLogo($provider) {
        $logos = [
            'mtn_momo' => '../images/mtn-logo.png',
            'airtel_money' => '../images/airtel-logo.png',
            'stanbic_bank' => '../images/stanbic-logo.png',
            'equity_bank' => '../images/equity-logo.png',
            'centenary_bank' => '../images/centenary-logo.png',
        ];
        return $logos[$provider] ?? '../images/bank-default.png';
    }
}
?>