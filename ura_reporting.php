<?php
/**
 * URA Reporting System - Tax Compliance Reports for Uganda Revenue Authority
 */

class URAReporting {
    
    /**
     * Generate VAT Report for Monthly Filing
     */
    public static function generateVATReport($month, $year) {
        $conn = getConnection();
        
        // Get taxable transactions for the period
        $stmt = $conn->prepare("
            SELECT 
                COUNT(*) as transaction_count,
                SUM(amount) as gross_amount,
                SUM(amount * 0.18) as vat_amount,
                SUM(amount * 0.82) as net_amount
            FROM payments 
            WHERE payment_method IN ('mobile_money', 'bank_deposit')
            AND MONTH(transaction_date) = ?
            AND YEAR(transaction_date) = ?
            AND status = 'verified'
        ");
        $stmt->bind_param("ii", $month, $year);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        
        $report = [
            'reporting_period' => date('F Y', mktime(0, 0, 0, $month, 1, $year)),
            'TIN' => '1012345678',
            'transaction_count' => $result['transaction_count'] ?? 0,
            'gross_amount' => $result['gross_amount'] ?? 0,
            'vat_amount' => $result['vat_amount'] ?? 0,
            'net_amount' => $result['net_amount'] ?? 0,
            'report_type' => 'VAT Return',
            'generated_date' => date('Y-m-d H:i:s')
        ];
        
        return $report;
    }
    
    /**
     * Generate Withholding Tax Report
     */
    public static function generateWithholdingTaxReport($month, $year) {
        $conn = getConnection();
        
        // Calculate PAYE withholding
        $stmt = $conn->prepare("
            SELECT 
                COUNT(*) as employee_count,
                SUM(net_salary * 0.15) as paye_withheld,
                SUM(net_salary * 0.10) as nssf_withheld,
                SUM(net_salary * 0.05) as other_deductions
            FROM staff_salaries
            WHERE MONTH(payment_month) = ?
            AND YEAR(payment_month) = ?
        ");
        $stmt->bind_param("ii", $month, $year);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        
        return [
            'reporting_period' => date('F Y', mktime(0, 0, 0, $month, 1, $year)),
            'TIN' => '1012345678',
            'employee_count' => $result['employee_count'] ?? 0,
            'paye_withheld' => $result['paye_withheld'] ?? 0,
            'nssf_withheld' => $result['nssf_withheld'] ?? 0,
            'total_withheld' => ($result['paye_withheld'] ?? 0) + ($result['nssf_withheld'] ?? 0),
            'report_type' => 'Withholding Tax Report',
            'generated_date' => date('Y-m-d H:i:s')
        ];
    }
    
    /**
     * Generate Annual Financial Report for URA
     */
    public static function generateAnnualReport($year) {
        $conn = getConnection();
        
        $report = [
            'reporting_period' => "Year Ended December $year",
            'TIN' => '1012345678',
            'institution' => 'Iganga School of Nursing and Midwifery',
        ];
        
        // Total revenue
        $stmt = $conn->query("
            SELECT SUM(amount) as total_revenue 
            FROM payments 
            WHERE YEAR(transaction_date) = $year AND status = 'verified'
        ");
        $report['total_revenue'] = $stmt->fetch_assoc()['total_revenue'] ?? 0;
        
        // Total expenses
        $stmt = $conn->query("
            SELECT SUM(amount) as total_expenses 
            FROM expenses 
            WHERE YEAR(expense_date) = $year AND status = 'paid'
        ");
        $report['total_expenses'] = $stmt->fetch_assoc()['total_expenses'] ?? 0;
        
        // VAT calculations
        $report['total_vat_collected'] = $report['total_revenue'] * 0.18;
        $report['taxable_revenue'] = $report['total_revenue'] - $report['total_vat_collected'];
        
        // Staff costs
        $stmt = $conn->query("
            SELECT SUM(base_salary) as total_staff_cost
            FROM staff_salaries
            WHERE YEAR(payment_month) = $year
        ");
        $report['staff_costs'] = $stmt->fetch_assoc()['total_staff_cost'] ?? 0;
        
        return $report;
    }
    
    /**
     * Generate CSV Export for URA Submission
     */
    public static function generateCSVExport($report_type, $month, $year) {
        $report = [];
        
        switch ($report_type) {
            case 'vat':
                $report = self::generateVATReport($month, $year);
                $csv = "TIN,Period,Gross Amount,VAT Amount,Net Amount,Transaction Count\n";
                $csv .= "{$report['TIN']},{$report['reporting_period']}," . 
                        number_format($report['gross_amount'], 2) . "," .
                        number_format($report['vat_amount'], 2) . "," .
                        number_format($report['net_amount'], 2) . "," .
                        $report['transaction_count'] . "\n";
                break;
                
            case 'annual':
                $report = self::generateAnnualReport($year);
                $csv = "Description,Amount (UGX)\n";
                $csv .= "Total Revenue," . number_format($report['total_revenue'], 2) . "\n";
                $csv .= "Total VAT Collected," . number_format($report['total_vat_collected'], 2) . "\n";
                $csv .= "Total Expenses," . number_format($report['total_expenses'], 2) . "\n";
                $csv .= "Staff Costs," . number_format($report['staff_costs'], 2) . "\n";
                break;
        }
        
        return $csv;
    }
}

// Handle report generation requests
if (isset($_GET['generate'])) {
    $type = $_GET['type'] ?? '';
    $month = $_GET['month'] ?? date('n');
    $year = $_GET['year'] ?? date('Y');
    
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="ura_report_' . $type . '_' . $year . '_' . $month . '.csv"');
    
    echo URAReporting::generateCSVExport($type, $month, $year);
    exit;
}