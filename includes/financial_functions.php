<?php
/**
 * Financial Management Functions for ISNM
 * Uses bursar_system.sql schema (student_invoices, payments, expenditure_records, penalty_configurations)
 */

require_once __DIR__ . '/../config/database.php';

if (!function_exists('getStudentsConnectionFinance')) {
    function getStudentsConnectionFinance() {
        return getStudentsConnection();
    }
}

if (!function_exists('generateFeeInvoiceNumber')) {
    function generateFeeInvoiceNumber() {
        $conn = getStudentsConnection();
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
        $conn = getStudentsConnection();
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
        $conn = getStudentsConnection();
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
        $conn = getStudentsConnection();
        $prefix = 'EXP-' . date('Y') . '-';
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM expenditure_records WHERE expenditure_number LIKE ?");
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
        $conn = getStudentsConnection();
        $stmt = $conn->prepare("
            SELECT COALESCE(SUM(balance), 0) as total_balance 
            FROM student_invoices 
            WHERE student_id = ? AND status IN ('Pending', 'Partially Paid', 'Overdue')
        ");
        $stmt->bind_param("i", $student_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        return $result['total_balance'];
    }
}

if (!function_exists('calculatePenalty')) {
    function calculatePenalty($amount, $days_late = 0, $penalty_config_id = null) {
        $conn = getStudentsConnection();
        $penalty = 0;
        
        if ($penalty_config_id) {
            $stmt = $conn->prepare("SELECT * FROM penalty_configurations WHERE id = ? AND is_active = TRUE");
            $stmt->bind_param("i", $penalty_config_id);
            $stmt->execute();
            $config = $stmt->get_result()->fetch_assoc();
            
            if ($config) {
                $penalty = (float)($config['amount'] ?? 0);
            }
        }
        
        return $penalty;
    }
}

if (!function_exists('updateStudentInvoiceBalance')) {
    function updateStudentInvoiceBalance($invoice_id) {
        $conn = getStudentsConnection();
        $stmt = $conn->prepare("
            SELECT si.total_amount, COALESCE(SUM(p.amount_received), 0) as paid
            FROM student_invoices si
            LEFT JOIN payments p ON si.id = p.invoice_id AND p.status = 'Completed'
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
                SET amount_paid = ?, 
                    status = CASE 
                        WHEN ? >= ? THEN 'Paid' 
                        WHEN ? > 0 THEN 'Partially Paid' 
                        ELSE status 
                    END
                WHERE id = ?
            ");
            $update_stmt->bind_param("ddddi", $paid, $paid, $total, $paid, $invoice_id);
            $update_stmt->execute();
        }
    }
}

if (!function_exists('getTotalCollections')) {
    function getTotalCollections($period = 'today') {
        $conn = getStudentsConnection();
        $current_date = date('Y-m-d');

        switch ($period) {
            case 'today':
                $stmt = $conn->prepare("SELECT COALESCE(SUM(amount_received), 0) as total FROM payments WHERE DATE(payment_date) = ? AND status = 'Completed'");
                $stmt->bind_param("s", $current_date);
                break;
            case 'week':
                $stmt = $conn->prepare("SELECT COALESCE(SUM(amount_received), 0) as total FROM payments WHERE YEARWEEK(payment_date) = YEARWEEK(NOW()) AND status = 'Completed'");
                break;
            case 'month':
                $stmt = $conn->prepare("SELECT COALESCE(SUM(amount_received), 0) as total FROM payments WHERE MONTH(payment_date) = MONTH(NOW()) AND YEAR(payment_date) = YEAR(NOW()) AND status = 'Completed'");
                break;
            default:
                $stmt = $conn->prepare("SELECT COALESCE(SUM(amount_received), 0) as total FROM payments WHERE status = 'Completed'");
                break;
        }
        if (!$stmt) return 0;
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc()['total'];
    }
}

if (!function_exists('getOutstandingFees')) {
    function getOutstandingFees() {
        $conn = getStudentsConnection();
        $stmt = $conn->query("SELECT COALESCE(SUM(balance), 0) as total FROM student_invoices WHERE status IN ('Pending', 'Partially Paid', 'Overdue')");
        return $stmt->fetch_assoc()['total'];
    }
}

if (!function_exists('logFinancialActivity')) {
    function logFinancialActivity($action_type, $table_name, $record_id, $old_values = null, $new_values = null) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $conn = getStaffConnection();
        $stmt = $conn->prepare("
            INSERT INTO staff_activity_log 
            (staff_id, activity_type, activity_description, module_accessed, ip_address, user_agent) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        $user_id = $_SESSION['user_id'] ?? 0;
        $description = "$action_type on $table_name #$record_id";
        $module = 'finance';
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        $stmt->bind_param("isssss", $user_id, $action_type, $description, $module, $ip_address, $user_agent);
        $stmt->execute();
    }
}

if (!function_exists('generateFinancialStatement')) {
    function generateFinancialStatement($student_id) {
        $conn = getStudentsConnection();
        
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
                p.amount_received as amount,
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

if (!function_exists('getPaymentProviders')) {
    function getPaymentProviders() {
        return [
            'mtn_momo' => [
                'name' => 'MTN Mobile Money',
                'short' => 'MTN',
                'logo' => '../images/mtn-logo.jpg',
                'group' => 'mobile_money',
            ],
            'airtel_money' => [
                'name' => 'Airtel Money',
                'short' => 'Airtel',
                'logo' => '../images/airtel-logo.png',
                'group' => 'mobile_money',
            ],
            'stanbic_bank' => [
                'name' => 'Stanbic Bank',
                'short' => 'Stanbic',
                'logo' => '../images/stanbic-logo.jpg',
                'group' => 'bank',
            ],
            'equity_bank' => [
                'name' => 'Equity Bank',
                'short' => 'Equity',
                'logo' => '../images/equity_logo.png',
                'group' => 'bank',
            ],
            'centenary_bank' => [
                'name' => 'Centenary Bank',
                'short' => 'Centenary',
                'logo' => '../images/centenary-logo.jpg',
                'group' => 'bank',
            ],
            'pearl_bank' => [
                'name' => 'Pearl Bank',
                'short' => 'Pearl',
                'logo' => '../images/pearl-logo.png',
                'group' => 'bank',
            ],
            'uba_bank' => [
                'name' => 'UBA Bank',
                'short' => 'UBA',
                'logo' => '../images/uba-bank-logo.png',
                'group' => 'bank',
            ],
            'dfcu_bank' => [
                'name' => 'DFCU Bank',
                'short' => 'DFCU',
                'logo' => '../images/bank-default.svg',
                'group' => 'bank',
            ],
            'absa_bank' => [
                'name' => 'Absa Bank',
                'short' => 'Absa',
                'logo' => '../images/bank-default.svg',
                'group' => 'bank',
            ],
            'mastercard' => [
                'name' => 'Mastercard',
                'short' => 'Mastercard',
                'logo' => '../images/mastercard-logo.png',
                'group' => 'card',
            ],
        ];
    }
}

if (!function_exists('getPaymentProviderLogo')) {
    function getPaymentProviderLogo($provider) {
        $providers = getPaymentProviders();
        if (isset($providers[$provider])) return $providers[$provider]['logo'];
        $alias_map = [
            'mtn' => 'mtn_momo',
            'momo' => 'mtn_momo',
            'airtel' => 'airtel_money',
            'stanbic' => 'stanbic_bank',
            'equity' => 'equity_bank',
            'centenary' => 'centenary_bank',
            'pearl' => 'pearl_bank',
            'uba' => 'uba_bank',
            'dfcu' => 'dfcu_bank',
            'absa' => 'absa_bank',
        ];
        $key = $alias_map[$provider] ?? null;
        if ($key && isset($providers[$key])) return $providers[$key]['logo'];
        return '../images/bank-default.svg';
    }
}

if (!function_exists('getPaymentProviderName')) {
    function getPaymentProviderName($provider) {
        $providers = getPaymentProviders();
        if (isset($providers[$provider])) return $providers[$provider]['name'];
        $alias_map = [
            'mtn' => 'mtn_momo', 'momo' => 'mtn_momo', 'airtel' => 'airtel_money',
            'stanbic' => 'stanbic_bank', 'equity' => 'equity_bank', 'centenary' => 'centenary_bank',
            'pearl' => 'pearl_bank', 'uba' => 'uba_bank', 'dfcu' => 'dfcu_bank', 'absa' => 'absa_bank',
        ];
        $key = $alias_map[$provider] ?? null;
        if ($key && isset($providers[$key])) return $providers[$key]['name'];
        return ucfirst(str_replace('_', ' ', $provider));
    }
}

if (!function_exists('renderPaymentProviderLogo')) {
    function renderPaymentProviderLogo($provider, $height = 24, $show_label = true) {
        $path = getPaymentProviderLogo($provider);
        $name = getPaymentProviderName($provider);
        $html = '<img src="' . htmlspecialchars($path) . '" alt="' . htmlspecialchars($name) . '"'
              . ' style="height:' . (int)$height . 'px;vertical-align:middle;border-radius:4px;object-fit:contain;"'
              . ' onerror="this.style.display=\'none\'">';
        if ($show_label) $html .= ' ' . htmlspecialchars($name);
        return $html;
    }
}