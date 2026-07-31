<?php
/**
 * ISNM Payroll Management System â€” Core Functions
 * Tax calculations, payroll processing, payslip generation, audit logging.
 */

if (!defined('PAYROLL_LOADED')) {
    define('PAYROLL_LOADED', true);
}

if (!function_exists('payStatusBadge')) {
    function payStatusBadge($status): string {
        $map = [
            'draft' => ['bg-secondary', 'Draft'],
            'processing' => ['bg-info', 'Processing'],
            'processed' => ['bg-primary', 'Processed'],
            'approved' => ['bg-success', 'Approved'],
            'paid' => ['bg-dark', 'Paid'],
            'pending' => ['bg-warning text-dark', 'Pending'],
            'generated' => ['bg-info', 'Generated'],
            'completed' => ['bg-success', 'Completed'],
            'cancelled' => ['bg-danger', 'Cancelled'],
            'rejected' => ['bg-danger', 'Rejected'],
        ];
        $s = strtolower($status);
        $m = $map[$s] ?? ['bg-secondary', ucfirst($status)];
        return '<span class="badge badge-status ' . $m[0] . '">' . htmlspecialchars($m[1]) . '</span>';
    }
}

if (!function_exists('jsonResponse')) {
    function jsonResponse(bool $success, string $message = '', $data = null, int $httpCode = 200): void {
        http_response_code($httpCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => $success, 'message' => $message, 'data' => $data], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

if (!function_exists('formatCurrencyUGX')) {
    function formatCurrencyUGX($amount): string {
        return 'UGX ' . number_format((float)$amount, 0);
    }
}

if (!function_exists('getPayrollConnection')) {
    function getPayrollConnection() {
        require_once __DIR__ . '/../config/database.php';
        return isnm_mysqli_connect('Payroll', PAYROLL_DB_HOST, PAYROLL_DB_USER, PAYROLL_DB_PASS, PAYROLL_DB_NAME, PAYROLL_DB_PORT, PAYROLL_DB_CHARSET);
    }
}

if (!function_exists('getPayrollSetting')) {
    function getPayrollSetting(string $key, $default = null) {
        try {
            $conn = getPayrollConnection();
            if (!$conn) return $default;
            $stmt = $conn->prepare("SELECT setting_value FROM payroll_settings WHERE setting_key = ? LIMIT 1");
            if (!$stmt) { return $default; }
            $stmt->bind_param('s', $key);
            if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $stmt->close();

            return $row ? $row['setting_value'] : $default;
        } catch (Exception $e) {
            error_log('getPayrollSetting error: ' . $e->getMessage());
            return $default;
        }
    }
}

if (!function_exists('updatePayrollSetting')) {
    function updatePayrollSetting(string $key, $value, int $updatedBy = 0): bool {
        try {
            $conn = getPayrollConnection();
            if (!$conn) return false;
            $stmt = $conn->prepare("INSERT INTO payroll_settings (setting_key, setting_value, updated_by) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_by = VALUES(updated_by)");
            if (!$stmt) { return false; }
            $stmt->bind_param('ssi', $key, $value, $updatedBy);
            $result = $stmt->execute();
            $stmt->close();
            return $result;
        } catch (Exception $e) {
            error_log('updatePayrollSetting error: ' . $e->getMessage());
            return false;
        }
    }
}

if (!function_exists('calculatePAYE')) {
    function calculatePAYE(float $taxableIncome): float {
        if ($taxableIncome <= 0) return 0.00;
        $brackets = [
            ['min' => 0,       'max' => 235000,   'rate' => 0],
            ['min' => 235000,  'max' => 335000,   'rate' => 10],
            ['min' => 335000,  'max' => 410000,   'rate' => 20],
            ['min' => 410000,  'max' => PHP_FLOAT_MAX, 'rate' => 30],
        ];
        $tax = 0.0;
        foreach ($brackets as $b) {
            if ($taxableIncome > $b['min']) {
                $taxableInBracket = min($taxableIncome, $b['max']) - $b['min'];
                $tax += $taxableInBracket * ($b['rate'] / 100);
            }
            if ($taxableIncome <= $b['max']) break;
        }
        return round($tax, 2);
    }
}

if (!function_exists('calculateNSSF')) {
    function calculateNSSF(float $basicSalary, float $employeeRate = 5.0, float $employerRate = 10.0, float $maxEarning = 5000000): array {
        $insurableEarning = min($basicSalary, $maxEarning);
        return [
            'employee'  => round($insurableEarning * ($employeeRate / 100), 2),
            'employer'  => round($insurableEarning * ($employerRate / 100), 2),
            'insurable' => $insurableEarning,
        ];
    }
}

if (!function_exists('calculatePayrollNet')) {
    function calculatePayrollNet(float $basicSalary, float $totalAllowances, float $totalBonus, float $totalOvertime, float $totalStatutoryDeductions, float $totalOtherDeductions): array {
        $grossPay = $basicSalary + $totalAllowances + $totalBonus + $totalOvertime;
        $totalDeductions = $totalStatutoryDeductions + $totalOtherDeductions;
        $netPay = max(0, $grossPay - $totalDeductions);
        return [
            'gross_pay'    => round($grossPay, 2),
            'net_pay'      => round($netPay, 2),
            'total_ded'    => round($totalDeductions, 2),
        ];
    }
}

if (!function_exists('logPayrollAudit')) {
    function logPayrollAudit(?int $staffId, string $action, ?string $entityType = null, ?int $entityId = null, $oldValues = null, $newValues = null): bool {
        try {
            $conn = getPayrollConnection();
            if (!$conn) return false;
            $ip = $_SERVER['REMOTE_ADDR'] ?? '';
            $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
            $oldJson = is_null($oldValues) ? null : (is_string($oldValues) ? $oldValues : json_encode($oldValues));
            $newJson = is_null($newValues) ? null : (is_string($newValues) ? $newValues : json_encode($newValues));
            $stmt = $conn->prepare("INSERT INTO payroll_audit_logs (staff_id, action, entity_type, entity_id, old_values, new_values, ip_address, user_agent) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            if (!$stmt) { return false; }
            $stmt->bind_param('ississss', $staffId, $action, $entityType, $entityId, $oldJson, $newJson, $ip, $ua);
            $result = $stmt->execute();
            $stmt->close();

            return $result;
        } catch (Exception $e) {
            error_log('logPayrollAudit error: ' . $e->getMessage());
            return false;
        }
    }
}

if (!function_exists('logPayrollApproval')) {
    function logPayrollApproval(string $entityType, int $entityId, string $action, ?string $step = null, ?string $comments = null, int $actedBy = 0): bool {
        try {
            $conn = getPayrollConnection();
            if (!$conn) return false;
            $stmt = $conn->prepare("INSERT INTO payroll_approval_history (entity_type, entity_id, action, step, comments, acted_by) VALUES (?, ?, ?, ?, ?, ?)");
            if (!$stmt) { return false; }
            $stmt->bind_param('sisssi', $entityType, $entityId, $action, $step, $comments, $actedBy);
            $result = $stmt->execute();
            $stmt->close();

            return $result;
        } catch (Exception $e) {
            error_log('logPayrollApproval error: ' . $e->getMessage());
            return false;
        }
    }
}

if (!function_exists('getEmployeeActiveAllowances')) {
    function getEmployeeActiveAllowances(int $payrollEmployeeId): array {
        try {
            $conn = getPayrollConnection();
            if (!$conn) return [];
            $stmt = $conn->prepare("SELECT pea.*, pat.allowance_code, pat.allowance_name, pat.is_taxable as type_taxable FROM payroll_employee_allowances pea JOIN payroll_allowance_types pat ON pea.allowance_type_id = pat.id WHERE pea.payroll_employee_id = ? AND pea.status = 'active' AND (pea.effective_from IS NULL OR pea.effective_from <= CURDATE()) AND (pea.effective_to IS NULL OR pea.effective_to >= CURDATE())");
            if (!$stmt) { return []; }
            $stmt->bind_param('i', $payrollEmployeeId);
            if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
            $result = $stmt->get_result();
            $rows = isnm_fetch_all($result);
            $stmt->close();

            return $rows ?: [];
        } catch (Exception $e) {
            error_log('getEmployeeActiveAllowances error: ' . $e->getMessage());
            return [];
        }
    }
}

if (!function_exists('getEmployeeActiveDeductions')) {
    function getEmployeeActiveDeductions(int $payrollEmployeeId): array {
        try {
            $conn = getPayrollConnection();
            if (!$conn) return [];
            $stmt = $conn->prepare("SELECT ped.*, pdt.deduction_code, pdt.deduction_name, pdt.is_statutory, pdt.category as type_category FROM payroll_employee_deductions ped JOIN payroll_deduction_types pdt ON ped.deduction_type_id = pdt.id WHERE ped.payroll_employee_id = ? AND ped.status = 'active' AND (ped.effective_from IS NULL OR ped.effective_from <= CURDATE()) AND (ped.effective_to IS NULL OR ped.effective_to >= CURDATE())");
            if (!$stmt) { return []; }
            $stmt->bind_param('i', $payrollEmployeeId);
            if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
            $result = $stmt->get_result();
            $rows = isnm_fetch_all($result);
            $stmt->close();

            return $rows ?: [];
        } catch (Exception $e) {
            error_log('getEmployeeActiveDeductions error: ' . $e->getMessage());
            return [];
        }
    }
}

if (!function_exists('getEmployeeOvertimeTotal')) {
    function getEmployeeOvertimeTotal(int $payrollEmployeeId, int $payrollPeriodId): float {
        try {
            $conn = getPayrollConnection();
            if (!$conn) return 0;
            $stmt = $conn->prepare("SELECT COALESCE(SUM(total_amount), 0) as total FROM payroll_overtime WHERE payroll_employee_id = ? AND payroll_period_id = ? AND status IN ('approved', 'paid')");
            if (!$stmt) { return 0; }
            $stmt->bind_param('ii', $payrollEmployeeId, $payrollPeriodId);
            if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $stmt->close();

            return (float)($row['total'] ?? 0);
        } catch (Exception $e) {
            error_log('getEmployeeOvertimeTotal error: ' . $e->getMessage());
            return 0;
        }
    }
}

if (!function_exists('getEmployeeBonusTotal')) {
    function getEmployeeBonusTotal(int $payrollEmployeeId, int $payrollPeriodId): float {
        try {
            $conn = getPayrollConnection();
            if (!$conn) return 0;
            $stmt = $conn->prepare("SELECT COALESCE(SUM(amount), 0) as total FROM payroll_bonus WHERE payroll_employee_id = ? AND payroll_period_id = ? AND status IN ('approved', 'paid')");
            if (!$stmt) { return 0; }
            $stmt->bind_param('ii', $payrollEmployeeId, $payrollPeriodId);
            if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $stmt->close();

            return (float)($row['total'] ?? 0);
        } catch (Exception $e) {
            error_log('getEmployeeBonusTotal error: ' . $e->getMessage());
            return 0;
        }
    }
}

if (!function_exists('getEmployeeLoanInstallment')) {
    function getEmployeeLoanInstallment(int $payrollEmployeeId): float {
        try {
            $conn = getPayrollConnection();
            if (!$conn) return 0;
            $stmt = $conn->prepare("SELECT COALESCE(SUM(installment_amount), 0) as total FROM payroll_loans WHERE payroll_employee_id = ? AND status = 'active'");
            if (!$stmt) { return 0; }
            $stmt->bind_param('i', $payrollEmployeeId);
            if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $stmt->close();

            return (float)($row['total'] ?? 0);
        } catch (Exception $e) {
            error_log('getEmployeeLoanInstallment error: ' . $e->getMessage());
            return 0;
        }
    }
}

if (!function_exists('generatePayslipHTML')) {
    function generatePayslipHTML(array $item, array $employee, array $allowances, array $deductions): string {
        $institution = getPayrollSetting('institution_name', 'Iganga School of Nursing and Midwifery');
        $address     = getPayrollSetting('institution_address', 'P.O. Box 418, Iganga, Uganda');
        $phone       = getPayrollSetting('institution_phone', '0782 990 403');
        $email       = getPayrollSetting('institution_email', 'info@isnm.ug');
        $motto       = getPayrollSetting('institution_motto', 'Chosen to Serve, Disciplined Mind for Health Action');

        $name   = htmlspecialchars($employee['full_name'] ?? 'N/A');
        $pos    = htmlspecialchars($employee['position'] ?? '');
        $dept   = htmlspecialchars($employee['department'] ?? '');
        $empNum = htmlspecialchars($employee['payroll_number'] ?? '');
        $tin    = htmlspecialchars($employee['tin'] ?? '');
        $nssfNo = htmlspecialchars($employee['nssf_number'] ?? '');

        $basic    = (float)($item['basic_salary'] ?? 0);
        $allowAmt = (float)($item['total_allowances'] ?? 0);
        $bonusAmt = (float)($item['total_bonus'] ?? 0);
        $otAmt    = (float)($item['total_overtime'] ?? 0);
        $gross    = (float)($item['gross'] ?? $item['gross_pay'] ?? ($basic + $allowAmt + $bonusAmt + $otAmt));
        $paye     = (float)($item['paye_tax'] ?? 0);
        $nssfEmp  = (float)($item['nssf_employee'] ?? 0);
        $statDed  = (float)($item['total_statutory_deductions'] ?? ($paye + $nssfEmp));
        $otherDed = (float)($item['total_other_deductions'] ?? 0);
        $net      = (float)($item['net_pay'] ?? 0);
        $period   = htmlspecialchars($item['period_name'] ?? '');

        $allowRows = '';
        foreach ($allowances as $a) {
            $allowRows .= '<tr><td>' . htmlspecialchars($a['allowance_name']) . '</td><td class="amt">' . number_format((float)$a['amount'], 2) . '</td></tr>';
        }
        $dedRows = '';
        foreach ($deductions as $d) {
            $dedRows .= '<tr><td>' . htmlspecialchars($d['deduction_name']) . '</td><td class="amt">(' . number_format((float)$d['amount'], 2) . ')</td></tr>';
        }

        return '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>Payslip | ISNM</title>
<style>
body{font-family:Arial,sans-serif;margin:20px;color:#333}.payslip{max-width:800px;margin:0 auto;padding:30px;border:2px solid #1a237e;border-radius:10px}
.header{text-align:center;padding-bottom:20px;border-bottom:3px solid #1a237e;margin-bottom:24px}
.header h2{color:#1a237e;margin:0 0 4px}.header h3{color:#283593;margin:0 0 8px}
.school-info{text-align:center;font-size:13px;color:#555;margin-bottom:20px}
table{width:100%;border-collapse:collapse;margin-bottom:20px}
th,td{border:1px solid #ccc;padding:10px;text-align:left;font-size:13px}
th{background:#1a237e;color:#fff;font-weight:600;text-align:center}
.total-row{font-weight:700;background:#e8eaf6;font-size:14px}
.label{color:#666;font-size:12px}.amt{text-align:right;font-weight:600}
.signature{margin-top:30px;text-align:right;font-style:italic;font-size:12px;color:#666;border-top:1px dashed #ccc;padding-top:20px}
.footer{text-align:center;font-size:11px;color:#999;margin-top:20px}
</style></head><body>
<div class="payslip">
<div class="header"><h2>' . htmlspecialchars($institution) . '</h2><h3>MONTHLY PAYSLIP</h3></div>
<div class="school-info"><p>' . htmlspecialchars($address) . ' | Tel: ' . htmlspecialchars($phone) . ' | Email: ' . htmlspecialchars($email) . '</p></div>
<table><thead><tr><th colspan="2">EMPLOYEE DETAILS</th><th>AMOUNT (UGX)</th></tr></thead>
<tbody>
<tr><td colspan="2"><strong>Name:</strong> ' . $name . '<br><span class="label">Position: ' . $pos . ' | Dept: ' . $dept . ' | Emp#: ' . $empNum . '</span></td><td class="amt">' . number_format($gross, 2) . '</td></tr>
<tr><td colspan="2"><strong>Basic Salary</strong></td><td class="amt">' . number_format($basic, 2) . '</td></tr>
' . $allowRows . '
' . ($bonusAmt > 0 ? '<tr><td colspan="2"><strong>Bonus</strong></td><td class="amt">' . number_format($bonusAmt, 2) . '</td></tr>' : '') . '
' . ($otAmt > 0 ? '<tr><td colspan="2"><strong>Overtime</strong></td><td class="amt">' . number_format($otAmt, 2) . '</td></tr>' : '') . '
<tr class="total-row"><td colspan="2">GROSS PAY</td><td class="amt">' . number_format($gross, 2) . '</td></tr>
<tr><td colspan="2"><strong>PAYE Tax</strong></td><td class="amt">(' . number_format($paye, 2) . ')</td></tr>
<tr><td colspan="2"><strong>NSSF Employee</strong></td><td class="amt">(' . number_format($nssfEmp, 2) . ')</td></tr>
' . $dedRows . '
<tr class="total-row"><td colspan="2">NET PAY</td><td class="amt">' . number_format($net, 2) . '</td></tr>
</tbody></table>
' . ($tin ? '<p><strong>TIN:</strong> ' . $tin . ' | <strong>NSSF:</strong> ' . $nssfNo . '</p>' : '') . '
<div class="signature">
<p><strong>Period:</strong> ' . $period . '</p>
<p><strong>Generated:</strong> ' . date('Y-m-d H:i') . ' | <strong>System:</strong> ISNM Payroll</p>
<p><em>Electronically generated payslip â€” valid without signature.</em></p>
</div>
<div class="footer">"' . htmlspecialchars($motto) . '"</div>
</div></body></html>';
    }
}

if (!function_exists('processPayrollRun')) {
    function processPayrollRun(int $payrollPeriodId, int $processedBy): array {
        $result = ['success' => false, 'message' => '', 'data' => []];
        try {
            $conn = getPayrollConnection();
            if (!$conn) {
                $result['message'] = 'Database connection failed';
                return $result;
            }

            $conn->begin_transaction();

            $periodStmt = $conn->prepare("SELECT id, period_code, period_name, month, year, start_date, end_date, status FROM payroll_periods WHERE id = ? FOR UPDATE");
            if (!$periodStmt) { $conn->rollback(); $result['message'] = 'Period query failed'; return $result; }
            $periodStmt->bind_param('i', $payrollPeriodId);
            if (!$periodStmt->execute()) { error_log('$periodStmt execute failed: ' . ($periodStmt->error ?? 'unknown')); };
            $period = $periodStmt->get_result()->fetch_assoc();
            $periodStmt->close();

            if (!$period) { $conn->rollback(); $result['message'] = 'Payroll period not found'; return $result; }
            if ($period['status'] !== 'open' && $period['status'] !== 'draft') {
                $conn->rollback();
                $result['message'] = 'Period status must be "open" or "draft", currently "' . $period['status'] . '"';
                return $result;
            }

            $conn->query("UPDATE payroll_periods SET status = 'processing' WHERE id = $payrollPeriodId");

            $runNumber = 'PR-' . $period['period_code'] . '-' . date('YmdHis');
            $stmt = $conn->prepare("INSERT INTO payroll_runs (payroll_period_id, run_number, run_type, status, processed_by, processed_at) VALUES (?, ?, 'normal', 'processing', ?, NOW())");
            if (!$stmt) { $conn->rollback(); $result['message'] = 'Run insert failed'; return $result; }
            $stmt->bind_param('isi', $payrollPeriodId, $runNumber, $processedBy);
            if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
            $payrollRunId = $stmt->insert_id;
            $stmt->close();

            $empStmt = $conn->prepare("SELECT pe.id as payroll_employee_id, pe.staff_id, pe.monthly_salary, pe.hourly_rate, pe.payment_method, pe.bank_account_number, pe.mobile_money_number, s.full_name, s.position FROM payroll_employees pe JOIN staff s ON pe.staff_id = s.id WHERE pe.payroll_status = 'active' AND s.status = 'Active'");
            if (!$empStmt) { $conn->rollback(); $result['message'] = 'Employee query failed'; return $result; }
            if (!$empStmt->execute()) { error_log('$empStmt execute failed: ' . ($empStmt->error ?? 'unknown')); };
            $employees = isnm_fetch_all($empStmt->get_result());
            $empStmt->close();

            $processed = 0;
            $totalGross = 0;
            $totalNet = 0;
            $totalAllow = 0;
            $totalDed = 0;
            $totalStat = 0;
            $totalTax = 0;
            $totalNssf = 0;
            $totalEmployerNssf = 0;

            foreach ($employees as $emp) {
                $peId = (int)$emp['payroll_employee_id'];
                $staffId = (int)$emp['staff_id'];
                $basic = (float)($emp['monthly_salary'] ?? 0);

                $allowances = getEmployeeActiveAllowances($peId);
                $deductions = getEmployeeActiveDeductions($peId);

                $totalAllowAmt = 0;
                $totalStatDed = 0;
                $totalOtherDed = 0;
                $payeTax = 0;
                $nssfEmployee = 0;
                $nssfEmployer = 0;

                foreach ($allowances as $a) {
                    $amt = (float)$a['amount'];
                    $totalAllowAmt += $amt;
                }

                $totalBonus = getEmployeeBonusTotal($peId, $payrollPeriodId);
                $totalOvertime = getEmployeeOvertimeTotal($peId, $payrollPeriodId);

                $grossForTax = $basic + $totalAllowAmt + $totalBonus + $totalOvertime;
                $payeTax = calculatePAYE($grossForTax);

                $nssfResult = calculateNSSF($basic);
                $nssfEmployee = $nssfResult['employee'];
                $nssfEmployer = $nssfResult['employer'];
                $totalStatDed = $payeTax + $nssfEmployee;

                $loanInstallment = getEmployeeLoanInstallment($peId);

                foreach ($deductions as $d) {
                    $amt = (float)$d['amount'];
                    if ($d['is_statutory']) {
                        $totalStatDed += $amt;
                    } else {
                        $totalOtherDed += $amt;
                    }
                }
                $totalOtherDed += $loanInstallment;

                $netCalc = calculatePayrollNet($basic, $totalAllowAmt, $totalBonus, $totalOvertime, $totalStatDed, $totalOtherDed);
                $netPay = $netCalc['net_pay'];
                $grossPay = $netCalc['gross_pay'];

                $bankAcc = $emp['bank_account_number'] ?? '';
                $mobileMoney = $emp['mobile_money_number'] ?? '';
                $payMethod = $emp['payment_method'] ?? 'bank';

                $itemStmt = $conn->prepare("INSERT INTO payroll_items (payroll_run_id, payroll_employee_id, staff_id, basic_salary, total_allowances, total_bonus, total_overtime, total_statutory_deductions, total_other_deductions, paye_tax, nssf_employee, nssf_employer, net_pay, bank_account, mobile_money, payment_method, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')");
                if ($itemStmt) {
                    $itemStmt->bind_param('iiiddddddddddsss', $payrollRunId, $peId, $staffId, $basic, $totalAllowAmt, $totalBonus, $totalOvertime, $totalStatDed, $totalOtherDed, $payeTax, $nssfEmployee, $nssfEmployer, $netPay, $bankAcc, $mobileMoney, $payMethod);
                    if ($itemStmt->execute()) {
                        $processed++;
                        $totalGross += $grossPay;
                        $totalNet += $netPay;
                        $totalAllow += $totalAllowAmt;
                        $totalDed += ($totalStatDed + $totalOtherDed);
                        $totalStat += $totalStatDed;
                        $totalTax += $payeTax;
                        $totalNssf += $nssfEmployee;
                        $totalEmployerNssf += $nssfEmployer;

                        $loanUpdate = $conn->prepare("UPDATE payroll_loans SET amount_paid = amount_paid + ?, installments_paid = installments_paid + 1, status = CASE WHEN installments_paid + 1 >= installments THEN 'completed' ELSE 'active' END WHERE payroll_employee_id = ? AND status = 'active'");
                        if ($loanUpdate) {
                            $loanUpdate->bind_param('di', $loanInstallment, $peId);
                            if (!$loanUpdate->execute()) { error_log('$loanUpdate execute failed: ' . ($loanUpdate->error ?? 'unknown')); };
                            $loanUpdate->close();
                        }
                    }
                    $itemStmt->close();
                }
            }

            $updateStmt = $conn->prepare("UPDATE payroll_runs SET total_employees = ?, total_gross = ?, total_allowances = ?, total_deductions = ?, total_statutory = ?, total_tax = ?, total_nssf = ?, total_employer_nssf = ?, total_net = ?, status = 'completed' WHERE id = ?");
            if ($updateStmt) {
                $updateStmt->bind_param('iddddddddi', $processed, $totalGross, $totalAllow, $totalDed, $totalStat, $totalTax, $totalNssf, $totalEmployerNssf, $totalNet, $payrollRunId);
                if (!$updateStmt->execute()) { error_log('$updateStmt execute failed: ' . ($updateStmt->error ?? 'unknown')); };
                $updateStmt->close();
            }

            $conn->query("UPDATE payroll_periods SET status = 'processed' WHERE id = $payrollPeriodId");

            $conn->commit();

            logPayrollAudit($processedBy, 'payroll_processed', 'payroll_run', $payrollRunId, null, ['period_id' => $payrollPeriodId, 'employees' => $processed, 'total_net' => $totalNet]);
            logPayrollApproval('payroll_run', $payrollRunId, 'submitted', 'HR Preparation', 'Auto-processed ' . $processed . ' employees', $processedBy);

            $result['success'] = true;
            $result['message'] = "Payroll run #$runNumber completed: $processed employees processed.";
            $result['data'] = [
                'payroll_run_id' => $payrollRunId,
                'run_number'     => $runNumber,
                'total_employees'=> $processed,
                'total_gross'    => $totalGross,
                'total_net'      => $totalNet,
                'total_tax'      => $totalTax,
                'total_nssf'     => $totalNssf,
            ];

            return $result;
        } catch (Exception $e) {
            if (isset($conn) && $conn) {
                try { $conn->rollback(); } catch (Exception $ignore) { error_log('payroll_functions compute: ' . $ignore->getMessage()); }
            }
            error_log('processPayrollRun error: ' . $e->getMessage());
            $result['message'] = 'Internal error: ' . $e->getMessage();
            return $result;
        }
    }
}

if (!function_exists('generatePayslipsForRun')) {
    function generatePayslipsForRun(int $payrollRunId, int $generatedBy): array {
        $result = ['success' => false, 'message' => '', 'data' => []];
        try {
            $conn = getPayrollConnection();
            if (!$conn) { $result['message'] = 'Database connection failed'; return $result; }

            $runStmt = $conn->prepare("SELECT id, run_number, payroll_period_id FROM payroll_runs WHERE id = ?");
            if (!$runStmt) { $result['message'] = 'Run query failed'; return $result; }
            $runStmt->bind_param('i', $payrollRunId);
            if (!$runStmt->execute()) { error_log('$runStmt execute failed: ' . ($runStmt->error ?? 'unknown')); };
            $run = $runStmt->get_result()->fetch_assoc();
            $runStmt->close();
            if (!$run) { $result['message'] = 'Payroll run not found'; return $result; }

            $periodStmt = $conn->prepare("SELECT period_name FROM payroll_periods WHERE id = ?");
            $periodStmt->bind_param('i', $run['payroll_period_id']);
            if (!$periodStmt->execute()) { error_log('$periodStmt execute failed: ' . ($periodStmt->error ?? 'unknown')); };
            $period = $periodStmt->get_result()->fetch_assoc();
            $periodStmt->close();

            $itemsStmt = $conn->prepare("SELECT pi.*, pe.staff_id as pe_staff_id, pe.payroll_number, pe.tin, pe.nssf_number, s.full_name, s.position FROM payroll_items pi JOIN payroll_employees pe ON pi.payroll_employee_id = pe.id JOIN staff s ON pi.staff_id = s.id WHERE pi.payroll_run_id = ? AND pi.status = 'active'");
            if (!$itemsStmt) { $result['message'] = 'Items query failed'; return $result; }
            $itemsStmt->bind_param('i', $payrollRunId);
            if (!$itemsStmt->execute()) { error_log('$itemsStmt execute failed: ' . ($itemsStmt->error ?? 'unknown')); };
            $items = isnm_fetch_all($itemsStmt->get_result());
            $itemsStmt->close();

            $periodName = $period['period_name'] ?? '';
            $count = 0;

            foreach ($items as $item) {
                $piId = (int)$item['id'];
                $peId = (int)$item['payroll_employee_id'];
                $staffId = (int)$item['staff_id'];

                $existing = $conn->prepare("SELECT id FROM payroll_payslips WHERE payroll_item_id = ?");
                $existing->bind_param('i', $piId);
                if (!$existing->execute()) { error_log('$existing execute failed: ' . ($existing->error ?? 'unknown')); };
                if ($existing->get_result()->fetch_assoc()) { $existing->close(); continue; }
                $existing->close();

                $allowances = getEmployeeActiveAllowances($peId);
                $deductions = getEmployeeActiveDeductions($peId);
                $item['period_name'] = $periodName;

                $employee = [
                    'full_name'      => $item['full_name'],
                    'position'       => $item['position'],
                    'department'     => '',
                    'payroll_number' => $item['payroll_number'] ?? '',
                    'tin'            => $item['tin'] ?? '',
                    'nssf_number'    => $item['nssf_number'] ?? '',
                ];

                $html = generatePayslipHTML($item, $employee, $allowances, $deductions);
                $payslipNumber = 'PSL-' . $run['run_number'] . '-' . str_pad($staffId, 4, '0', STR_PAD_LEFT);

                $insertStmt = $conn->prepare("INSERT INTO payroll_payslips (payroll_item_id, payroll_run_id, payroll_employee_id, staff_id, payslip_number, payslip_html, pdf_generated, generated_by, generated_at) VALUES (?, ?, ?, ?, ?, ?, 0, ?, NOW())");
                if ($insertStmt) {
                    $insertStmt->bind_param('iiiisis', $piId, $payrollRunId, $peId, $staffId, $payslipNumber, $html, $generatedBy);
                    if ($insertStmt->execute()) $count++;
                    $insertStmt->close();
                }
            }


            $result['success'] = true;
            $result['message'] = "$count payslips generated.";
            $result['data'] = ['count' => $count];
            return $result;
        } catch (Exception $e) {
            error_log('generatePayslipsForRun error: ' . $e->getMessage());
            $result['message'] = 'Internal error: ' . $e->getMessage();
            return $result;
        }
    }
}

if (!function_exists('getPayrollDashboardStats')) {
    function getPayrollDashboardStats(): array {
        $defaults = [
            'total_employees'     => 0,
            'active_payroll'      => 0,
            'pending_approvals'   => 0,
            'current_period'      => 'N/A',
            'monthly_gross'       => 0,
            'monthly_net'         => 0,
            'monthly_tax'         => 0,
            'monthly_nssf'        => 0,
        ];
        try {
            $conn = getPayrollConnection();
            if (!$conn) return $defaults;

            $empResult = $conn->query("SELECT COUNT(*) as c FROM payroll_employees WHERE payroll_status = 'active'");
            if ($empResult) $defaults['total_employees'] = (int)$empResult->fetch_assoc()['c'];

            $periodResult = $conn->query("SELECT id, period_name, status FROM payroll_periods WHERE status IN ('open', 'processing', 'processed', 'approved') ORDER BY year DESC, month DESC LIMIT 1");
            if ($periodResult && ($p = $periodResult->fetch_assoc())) {
                $defaults['current_period'] = $p['period_name'];
                $defaults['active_payroll'] = $p['id'];

                $runResult = $conn->query("SELECT COALESCE(SUM(total_gross),0) as gross, COALESCE(SUM(total_net),0) as net, COALESCE(SUM(total_tax),0) as tax, COALESCE(SUM(total_nssf),0) as nssf FROM payroll_runs WHERE payroll_period_id = {$p['id']} AND status NOT IN ('cancelled')");
                if ($runResult && ($r = $runResult->fetch_assoc())) {
                    $defaults['monthly_gross'] = (float)$r['gross'];
                    $defaults['monthly_net'] = (float)$r['net'];
                    $defaults['monthly_tax'] = (float)$r['tax'];
                    $defaults['monthly_nssf'] = (float)$r['nssf'];
                }
            }

            $approvalResult = $conn->query("SELECT COUNT(*) as c FROM payroll_approval_history WHERE action IN ('submitted', 'pending') AND acted_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
            if ($approvalResult) $defaults['pending_approvals'] = (int)$approvalResult->fetch_assoc()['c'];


            return $defaults;
        } catch (Exception $e) {
            error_log('getPayrollDashboardStats error: ' . $e->getMessage());
            return $defaults;
        }
    }
}

if (!function_exists('getPayrollPeriods')) {
    function getPayrollPeriods(string $status = null): array {
        try {
            $conn = getPayrollConnection();
            if (!$conn) return [];
            $sql = "SELECT * FROM payroll_periods ORDER BY year DESC, month DESC";
            if ($status) {
                $sql = "SELECT * FROM payroll_periods WHERE status = ? ORDER BY year DESC, month DESC";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param('s', $status);
            } else {
                $stmt = $conn->prepare($sql);
            }
            if (!$stmt) { return []; }
            if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
            $result = $stmt->get_result();
            $rows = isnm_fetch_all($result);
            $stmt->close();

            return $rows ?: [];
        } catch (Exception $e) {
            error_log('getPayrollPeriods error: ' . $e->getMessage());
            return [];
        }
    }
}

if (!function_exists('createPayrollPeriod')) {
    function createPayrollPeriod(int $month, int $year, string $frequency = 'monthly', int $createdBy = 0): array {
        $result = ['success' => false, 'message' => '', 'data' => []];
        try {
            $conn = getPayrollConnection();
            if (!$conn) { $result['message'] = 'Database connection failed'; return $result; }

            $periodCode = strtoupper(substr($frequency, 0, 3)) . '-' . str_pad($month, 2, '0', STR_PAD_LEFT) . '-' . $year;
            $periodName = date('F', mktime(0, 0, 0, $month, 1)) . ' ' . $year;
            $startDate = date('Y-m-d', mktime(0, 0, 0, $month, 1, $year));
            $endDate = date('Y-m-t', mktime(0, 0, 0, $month, 1, $year));
            $payDay = (int)getPayrollSetting('payroll_day', 25);
            $paymentDate = date('Y-m-d', mktime(0, 0, 0, $month, $payDay, $year));

            $stmt = $conn->prepare("INSERT INTO payroll_periods (period_code, period_name, frequency, month, year, start_date, end_date, payment_date, status, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'draft', ?) ON DUPLICATE KEY UPDATE period_name = VALUES(period_name)");
            if (!$stmt) { $result['message'] = 'Insert prepare failed'; return $result; }
            $stmt->bind_param('ssiiiissi', $periodCode, $periodName, $frequency, $month, $year, $startDate, $endDate, $paymentDate, $createdBy);
            if (!$stmt->execute()) {
    
                $result['message'] = 'Execute failed: ' . $stmt->error;
                return $result;
            }
            $periodId = $stmt->insert_id;
            $stmt->close();


            logPayrollAudit($createdBy, 'period_created', 'payroll_period', $periodId, null, ['code' => $periodCode, 'name' => $periodName]);

            $result['success'] = true;
            $result['message'] = "Period $periodName created.";
            $result['data'] = ['period_id' => $periodId, 'period_code' => $periodCode];
            return $result;
        } catch (Exception $e) {
            error_log('createPayrollPeriod error: ' . $e->getMessage());
            $result['message'] = 'Internal error: ' . $e->getMessage();
            return $result;
        }
    }
}

if (!function_exists('getPayrollEmployees')) {
    function getPayrollEmployees(string $status = 'active'): array {
        try {
            $conn = getPayrollConnection();
            if (!$conn) return [];
            $stmt = $conn->prepare("SELECT pe.*, s.full_name, s.position, s.email, s.phone FROM payroll_employees pe JOIN staff s ON pe.staff_id = s.id WHERE pe.payroll_status = ? ORDER BY s.full_name");
            if (!$stmt) { return []; }
            $stmt->bind_param('s', $status);
            if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
            $result = $stmt->get_result();
            $rows = isnm_fetch_all($result);
            $stmt->close();

            return $rows ?: [];
        } catch (Exception $e) {
            error_log('getPayrollEmployees error: ' . $e->getMessage());
            return [];
        }
    }
}
