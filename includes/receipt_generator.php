<?php
/**
 * Receipt Generation and Printing System
 */
require_once __DIR__ . '/../includes/financial_functions.php';

class ReceiptGenerator {
    
    public static function generateReceiptHTML($payment_id) {
        $conn = getStudentsConnection();
        
        $stmt = $conn->prepare("
            SELECT p.*, s.first_name, s.surname AS last_name, s.index_number
            FROM payments p 
            JOIN students s ON p.student_id = s.id 
            WHERE p.id = ?
        ");
        $stmt->bind_param("i", $payment_id);
        if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
        $payment = $stmt->get_result()->fetch_assoc();
        
        if (!$payment) {
            return "Payment not found";
        }
        
        $receipt_number = generateReceiptNumber();
        $date = date('F j, Y');
        
        $method_labels = [
            'cash' => 'Cash',
            'mobile_money' => 'Mobile Money',
            'bank_deposit' => 'Bank Deposit',
            'bank' => 'Bank Deposit',
            'cheque' => 'Cheque',
            'card' => 'Card Payment',
            'online' => 'Online Payment',
        ];
        
        $provider_labels = [
            'mtn_momo' => 'MTN Mobile Money',
            'airtel_money' => 'Airtel Money',
            'stanbic_bank' => 'Stanbic Bank',
            'equity_bank' => 'Equity Bank',
            'centenary_bank' => 'Centenary Bank',
            'pearl_bank' => 'Pearl Bank',
            'uba_bank' => 'UBA Bank',
            'dfcu_bank' => 'DFCU Bank',
            'absa_bank' => 'Absa Bank',
        ];
        
        $method = $method_labels[$payment['payment_method']] ?? $payment['payment_method'];
        if ($payment['payment_provider']) {
            $method .= ' (' . ($provider_labels[$payment['payment_provider']] ?? getPaymentProviderName($payment['payment_provider'])) . ')';
        }
        
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <title>Receipt | {$receipt_number}</title>
            <style>
                @media print {
                    body { margin: 0; padding: 20px; font-family: Arial, sans-serif; }
                    .receipt-container { max-width: 800px; margin: 0 auto; }
                    .no-print { display: none; }
                }
                body { font-family: Arial, sans-serif; margin: 20px; color: #333; }
                .receipt-container { max-width: 800px; margin: 0 auto; background: #fff; padding: 30px; border: 1px solid #ddd; }
                .header { text-align: center; border-bottom: 2px solid #1e3a8a; padding-bottom: 20px; margin-bottom: 20px; }
                .logo { height: 80px; margin-bottom: 10px; }
                .school-name { color: #1e3a8a; font-size: 24px; font-weight: bold; margin: 10px 0; }
                .contact-info { color: #666; font-size: 14px; }
                .receipt-title { color: #059669; font-size: 20px; margin: 10px 0; }
                .info-section { margin: 20px 0; }
                .info-row { display: flex; margin: 5px 0; }
                .info-label { font-weight: bold; width: 150px; }
                table { width: 100%; border-collapse: collapse; margin: 20px 0; }
                th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
                th { background: #1e3a8a; color: #fff; }
                .total-row { font-weight: bold; background: #f8f9fa; font-size: 18px; }
                .footer { margin-top: 30px; text-align: center; font-size: 12px; color: #666; }
                .signature-section { margin-top: 40px; display: flex; justify-content: space-between; }
                .signature-box { width: 200px; }
                .signature-line { border-top: 1px solid #333; margin-top: 50px; padding-top: 5px; text-align: center; }
                .paid-stamp { color: #059669; font-size: 48px; font-weight: bold; transform: rotate(15deg); opacity: 0.3; }
            </style>
        </head>
        <body>
            <div class='receipt-container'>
                <div class='header'>
                    <img src='../images/school-logo.png' alt='School Logo' class='logo'>
                    <div class='school-name'>IGANGA SCHOOL OF NURSING AND MIDWIFERY</div>
                    <div class='contact-info'>
                        P.O. Box 418, Iganga, Uganda | Tel: 0782 990 403<br>
                        Email: bursar@igangaschoolofnursingandmidwifery.ac.ug
                    </div>
                    <div class='receipt-title'>OFFICIAL PAYMENT RECEIPT</div>
                </div>
                
                <div class='info-section'>
                    <div class='info-row'><span class='info-label'>Receipt No:</span> {$receipt_number}</div>
                    <div class='info-row'><span class='info-label'>Date:</span> {$date}</div>
                    <div class='info-row'><span class='info-label'>Student Name:</span> {$payment['first_name']} {$payment['last_name']}</div>
                    <div class='info-row'><span class='info-label'>Student ID:</span> {$payment['index_number']}</div>
                </div>
                
                <table>
                    <thead>
                        <tr>
                            <th>Description</th>
                            <th>Amount (UGX)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Fee Payment , {$payment['payment_reference']}</td>
                            <td>" . number_format($payment['amount']) . "</td>
                        </tr>
                        <tr class='total-row'>
                            <td>TOTAL AMOUNT PAID</td>
                            <td>" . number_format($payment['amount']) . "</td>
                        </tr>
                    </tbody>
                </table>
                
                <div class='info-section'>
                    <div class='info-row'><span class='info-label'>Payment Method:</span> {$method}</div>
                    <div class='info-row'><span class='info-label'>Reference:</span> {$payment['payment_reference']}</div>
                    <div class='info-row'><span class='info-label'>Processed By:</span> School Bursar</div>
                </div>
                
                <div class='signature-section'>
                    <div class='signature-box'>
                        <div class='signature-line'>Student Signature</div>
                    </div>
                    <div class='signature-box'>
                        <div class='signature-line'>Authorized Signature</div>
                    </div>
                </div>
                
                <div class='footer'>
                    <p><strong>\"Chosen to Serve\" , Disciplined Mind for Health Action</strong></p>
                    <p>This is a computer-generated receipt and is valid without signature.</p>
                    <p>Printed on: " . date('F j, Y \a\t g:i A') . "</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }
    
    public static function generatePayslipHTML($staff_id, $month = null, $year = null) {
        $conn = getStaffConnection();
        
        $staff_stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
        $staff_stmt->bind_param("i", $staff_id);
        if (!$staff_stmt->execute()) { error_log('$staff_stmt execute failed: ' . ($staff_stmt->error ?? 'unknown')); };
        $staff = $staff_stmt->get_result()->fetch_assoc();
        
        if (!$staff) {
            return "Staff not found";
        }
        
        $month = $month ?? date('n');
        $year = $year ?? date('Y');
        
        $basic_salary = $staff['salary'] ?? rand(800000, 3000000);
        $housing = $basic_salary * 0.15;
        $transport = 200000;
        $medical = 150000;
        $gross = $basic_salary + $housing + $transport + $medical;
        $paye = $gross * 0.15;
        $nssf = $gross * 0.1;
        $other_deductions = 50000;
        $net = $gross - $paye - $nssf - $other_deductions;
        $staff_identifier = $staff['staff_id'] ?? 'STF' . $staff_id;
        $staff_department = $staff['department'] ?? 'Administration';
        $staff_position = $staff['position'] ?? 'Staff';
        $staff_name = trim(($staff['first_name'] ?? '') . ' ' . ($staff['surname'] ?? ''));
        
        $payslip_number = "PAYSLIP-" . $year . "-" . str_pad($month, 2, '0', STR_PAD_LEFT) . "-" . $staff_id;
        
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <title>Payslip | {$payslip_number}</title>
            <style>
                @media print { body { margin: 0; padding: 20px; } }
                body { font-family: Arial, sans-serif; margin: 20px; color: #333; }
                .payslip-container { max-width: 800px; margin: 0 auto; background: #fff; padding: 30px; border: 1px solid #ddd; }
                .header { text-align: center; border-bottom: 2px solid #1e3a8a; padding-bottom: 20px; margin-bottom: 20px; }
                .logo { height: 80px; }
                .school-name { color: #1e3a8a; font-size: 24px; font-weight: bold; }
                .contact-info { color: #666; font-size: 14px; }
                .section-title { color: #059669; font-size: 18px; margin: 20px 0 10px; border-bottom: 1px solid #ddd; padding-bottom: 5px; }
                .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin: 15px 0; }
                .info-item { padding: 5px 0; }
                table { width: 100%; border-collapse: collapse; margin: 15px 0; }
                th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
                th { background: #1e3a8a; color: #fff; }
                .earnings-row { background: #e8f5e9; }
                .deductions-row { background: #ffebee; }
                .total-row { font-weight: bold; background: #f5f5f5; }
                .net-row { font-weight: bold; background: #059669; color: #fff; font-size: 18px; }
                .footer { margin-top: 30px; text-align: center; font-size: 12px; color: #666; border-top: 1px solid #ddd; padding-top: 20px; }
            </style>
        </head>
        <body>
            <div class='payslip-container'>
                <div class='header'>
                    <img src='../images/school-logo.png' alt='School Logo' class='logo'>
                    <div class='school-name'>IGANGA SCHOOL OF NURSING AND MIDWIFERY</div>
                    <div class='contact-info'>
                        P.O. Box 418, Iganga, Uganda | Tel: 0782 990 403<br>
                        Email: bursar@igangaschoolofnursingandmidwifery.ac.ug
                    </div>
                    <h2 style='color: #059669;'>PAYSLIP</h2>
                </div>
                
                <div class='section-title'>Employee Information</div>
                <div class='info-grid'>
                    <div class='info-item'><strong>Payslip No:</strong> {$payslip_number}</div>
                    <div class='info-item'><strong>Payment Period:</strong> " . date('F Y', mktime(0, 0, 0, $month, 1, $year)) . "</div>
                    <div class='info-item'><strong>Staff ID:</strong> {$staff_identifier}</div>
                    <div class='info-item'><strong>Department:</strong> {$staff_department}</div>
                    <div class='info-item'><strong>Name:</strong> {$staff_name}</div>
                    <div class='info-item'><strong>Position:</strong> {$staff_position}</div>
                </div>
                
                <div class='section-title'>Earnings</div>
                <table>
                    <thead>
                        <tr><th>Description</th><th>Amount (UGX)</th></tr>
                    </thead>
                    <tbody>
                        <tr class='earnings-row'><td>Basic Salary</td><td>" . number_format($basic_salary) . "</td></tr>
                        <tr class='earnings-row'><td>Housing Allowance</td><td>" . number_format($housing) . "</td></tr>
                        <tr class='earnings-row'><td>Transport Allowance</td><td>" . number_format($transport) . "</td></tr>
                        <tr class='earnings-row'><td>Medical Allowance</td><td>" . number_format($medical) . "</td></tr>
                        <tr class='total-row'><td>Gross Pay</td><td>" . number_format($gross) . "</td></tr>
                    </tbody>
                </table>
                
                <div class='section-title'>Deductions</div>
                <table>
                    <thead>
                        <tr><th>Description</th><th>Amount (UGX)</th></tr>
                    </thead>
                    <tbody>
                        <tr class='deductions-row'><td>PAYE Tax</td><td>" . number_format($paye) . "</td></tr>
                        <tr class='deductions-row'><td>NSSF Contribution</td><td>" . number_format($nssf) . "</td></tr>
                        <tr class='deductions-row'><td>Other Deductions</td><td>" . number_format($other_deductions) . "</td></tr>
                        <tr class='total-row'><td>Total Deductions</td><td>" . number_format($paye + $nssf + $other_deductions) . "</td></tr>
                    </tbody>
                </table>
                
                <table>
                    <tr class='net-row'>
                        <td><strong>NET PAY</strong></td>
                        <td><strong>" . number_format($net) . "</strong></td>
                    </tr>
                </table>
                
                <div class='footer'>
                    <p><strong>\"Chosen to Serve\" , Disciplined Mind for Health Action</strong></p>
                    <p>This is a computer-generated payslip and is valid without signature.</p>
                    <p>Generated on: " . date('F j, Y \a\t g:i A') . "</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }
}
?>