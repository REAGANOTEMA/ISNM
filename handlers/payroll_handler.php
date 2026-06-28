<?php
/**
 * ISNM Payroll Management System — AJAX/Form POST Handler
 * All payroll CRUD operations, processing, and approvals via a single endpoint.
 */

require_once __DIR__ . '/../includes/payroll_functions.php';
require_once __DIR__ . '/../includes/staff_dashboard_access.php';

$ctx = bootstrapStaffDashboard([]);
$conn = $ctx['staff'];
$user = $ctx['user'];
$staffId = (int)($user['id'] ?? 0);
$userRole = $user['role'] ?? '';

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$isAjax = (strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest') || (($_POST['format'] ?? '') === 'json');
$referrer = $_SERVER['HTTP_REFERER'] ?? '../payroll.php';

try {
    switch ($action) {

        // ── Employee Payroll Profiles ──
        case 'create_employee_profile':
            $staffIdParam = (int)($_POST['staff_id'] ?? 0);
            $employmentType = $_POST['employment_type'] ?? 'permanent';
            $monthlySalary = (float)($_POST['monthly_salary'] ?? 0);
            $paymentMethod = $_POST['payment_method'] ?? 'bank';
            $bankName = $_POST['bank_name'] ?? '';
            $bankAccount = $_POST['bank_account_number'] ?? '';
            $mobileMoney = $_POST['mobile_money_number'] ?? '';
            $tin = $_POST['tin'] ?? '';
            $nssfNumber = $_POST['nssf_number'] ?? '';
            $nationalId = $_POST['national_id'] ?? '';

            $pconn = getPayrollConnection();
            if (!$pconn) throw new Exception('Payroll DB connection failed');

            $stmt = $pconn->prepare("INSERT INTO payroll_employees (staff_id, employment_type, payment_method, bank_name, bank_account_number, mobile_money_number, tin, nssf_number, national_id, monthly_salary, annual_salary, payroll_status, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', ?)");
            if (!$stmt) throw new Exception('Prepare failed: ' . $pconn->error);
            $annualSal = $monthlySalary * 12;
            $stmt->bind_param('isssssssddi', $staffIdParam, $employmentType, $paymentMethod, $bankName, $bankAccount, $mobileMoney, $tin, $nssfNumber, $nationalId, $monthlySalary, $annualSal, $staffId);
            if (!$stmt->execute()) throw new Exception('Execute failed: ' . $stmt->error);
            $newId = $stmt->insert_id;
            $stmt->close();
            $pconn->close();

            logPayrollAudit($staffId, 'employee_created', 'payroll_employee', $newId, null, $_POST);
            $_SESSION['success'] = 'Payroll profile created.';
            break;

        case 'update_employee_profile':
            $profileId = (int)($_POST['profile_id'] ?? 0);
            $monthlySalary = (float)($_POST['monthly_salary'] ?? 0);
            $employmentType = $_POST['employment_type'] ?? 'permanent';
            $paymentMethod = $_POST['payment_method'] ?? 'bank';
            $bankName = $_POST['bank_name'] ?? '';
            $bankAccount = $_POST['bank_account_number'] ?? '';
            $mobileMoney = $_POST['mobile_money_number'] ?? '';
            $tin = $_POST['tin'] ?? '';
            $nssfNumber = $_POST['nssf_number'] ?? '';
            $payrollStatus = $_POST['payroll_status'] ?? 'active';

            $pconn = getPayrollConnection();
            if (!$pconn) throw new Exception('Payroll DB connection failed');
            $stmt = $pconn->prepare("UPDATE payroll_employees SET employment_type=?, monthly_salary=?, annual_salary=?, payment_method=?, bank_name=?, bank_account_number=?, mobile_money_number=?, tin=?, nssf_number=?, payroll_status=? WHERE id=?");
            if (!$stmt) throw new Exception('Prepare failed: ' . $pconn->error);
            $annualSal = $monthlySalary * 12;
            $stmt->bind_param('sdssssssssi', $employmentType, $monthlySalary, $annualSal, $paymentMethod, $bankName, $bankAccount, $mobileMoney, $tin, $nssfNumber, $payrollStatus, $profileId);
            if (!$stmt->execute()) throw new Exception('Execute failed: ' . $stmt->error);
            $stmt->close();
            $pconn->close();

            logPayrollAudit($staffId, 'employee_updated', 'payroll_employee', $profileId, null, $_POST);
            $_SESSION['success'] = 'Payroll profile updated.';
            break;

        // ── Allowance Assignment ──
        case 'assign_allowance':
            $peId = (int)($_POST['payroll_employee_id'] ?? 0);
            $typeId = (int)($_POST['allowance_type_id'] ?? 0);
            $amount = (float)($_POST['amount'] ?? 0);
            $isTaxable = isset($_POST['is_taxable']) ? 1 : 0;
            $isRecurring = isset($_POST['is_recurring']) ? 1 : 0;
            $effectiveFrom = $_POST['effective_from'] ?? date('Y-m-d');

            $pconn = getPayrollConnection();
            if (!$pconn) throw new Exception('Payroll DB connection failed');
            $stmt = $pconn->prepare("INSERT INTO payroll_employee_allowances (payroll_employee_id, allowance_type_id, amount, is_taxable, is_recurring, effective_from, status, created_by) VALUES (?, ?, ?, ?, ?, ?, 'active', ?)");
            if (!$stmt) throw new Exception('Prepare failed: ' . $pconn->error);
            $stmt->bind_param('iidissi', $peId, $typeId, $amount, $isTaxable, $isRecurring, $effectiveFrom, $staffId);
            $stmt->execute() ? $_SESSION['success'] = 'Allowance assigned.' : $_SESSION['error'] = 'Failed: ' . $stmt->error;
            $stmt->close();
            $pconn->close();
            break;

        case 'remove_allowance':
            $allowanceId = (int)($_POST['allowance_id'] ?? 0);
            $pconn = getPayrollConnection();
            if (!$pconn) throw new Exception('Payroll DB connection failed');
            $stmt = $pconn->prepare("UPDATE payroll_employee_allowances SET status='inactive' WHERE id=?");
            $stmt->bind_param('i', $allowanceId);
            $stmt->execute();
            $stmt->close();
            $pconn->close();
            $_SESSION['success'] = 'Allowance removed.';
            break;

        // ── Deduction Assignment ──
        case 'assign_deduction':
            $peId = (int)($_POST['payroll_employee_id'] ?? 0);
            $typeId = (int)($_POST['deduction_type_id'] ?? 0);
            $amount = (float)($_POST['amount'] ?? 0);
            $isRecurring = isset($_POST['is_recurring']) ? 1 : 0;
            $effectiveFrom = $_POST['effective_from'] ?? date('Y-m-d');

            $pconn = getPayrollConnection();
            if (!$pconn) throw new Exception('Payroll DB connection failed');
            $stmt = $pconn->prepare("INSERT INTO payroll_employee_deductions (payroll_employee_id, deduction_type_id, amount, is_recurring, effective_from, status, created_by) VALUES (?, ?, ?, ?, ?, 'active', ?)");
            if (!$stmt) throw new Exception('Prepare failed: ' . $pconn->error);
            $stmt->bind_param('iidissi', $peId, $typeId, $amount, $isRecurring, $effectiveFrom, $staffId);
            $stmt->execute() ? $_SESSION['success'] = 'Deduction assigned.' : $_SESSION['error'] = 'Failed: ' . $stmt->error;
            $stmt->close();
            $pconn->close();
            break;

        case 'remove_deduction':
            $dedId = (int)($_POST['deduction_id'] ?? 0);
            $pconn = getPayrollConnection();
            if (!$pconn) throw new Exception('Payroll DB connection failed');
            $stmt = $pconn->prepare("UPDATE payroll_employee_deductions SET status='inactive' WHERE id=?");
            $stmt->bind_param('i', $dedId);
            $stmt->execute();
            $stmt->close();
            $pconn->close();
            $_SESSION['success'] = 'Deduction removed.';
            break;

        // ── Overtime ──
        case 'add_overtime':
            $peId = (int)($_POST['payroll_employee_id'] ?? 0);
            $hours = (float)($_POST['hours_worked'] ?? 0);
            $type = $_POST['overtime_type'] ?? 'normal';
            $date = $_POST['overtime_date'] ?? date('Y-m-d');
            $desc = $_POST['description'] ?? '';

            $pconn = getPayrollConnection();
            if (!$pconn) throw new Exception('Payroll DB connection failed');

            $empStmt = $pconn->prepare("SELECT hourly_rate FROM payroll_employees WHERE id=?");
            $empStmt->bind_param('i', $peId);
            $empStmt->execute();
            $empRow = $empStmt->get_result()->fetch_assoc();
            $empStmt->close();
            $hourlyRate = (float)($empRow['hourly_rate'] ?? 0);

            $rates = ['normal' => 1.5, 'weekend' => 2.0, 'holiday' => 2.5, 'night' => 2.0];
            $multiplier = $rates[$type] ?? 1.5;

            $stmt = $pconn->prepare("INSERT INTO payroll_overtime (payroll_employee_id, overtime_type, hours_worked, rate_multiplier, hourly_rate, overtime_date, description, status, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', ?)");
            if (!$stmt) throw new Exception('Prepare failed: ' . $pconn->error);
            $stmt->bind_param('isiddssi', $peId, $type, $hours, $multiplier, $hourlyRate, $date, $desc, $staffId);
            $stmt->execute() ? $_SESSION['success'] = 'Overtime recorded.' : $_SESSION['error'] = 'Failed: ' . $stmt->error;
            $stmt->close();
            $pconn->close();
            break;

        case 'approve_overtime':
            $otId = (int)($_POST['overtime_id'] ?? 0);
            $pconn = getPayrollConnection();
            if (!$pconn) throw new Exception('Payroll DB connection failed');
            $stmt = $pconn->prepare("UPDATE payroll_overtime SET status='approved', approved_by=?, approved_at=NOW() WHERE id=?");
            $stmt->bind_param('ii', $staffId, $otId);
            $stmt->execute();
            $stmt->close();
            $pconn->close();
            logPayrollApproval('overtime', $otId, 'approved', 'Approval', 'Approved by ' . $userRole, $staffId);
            $_SESSION['success'] = 'Overtime approved.';
            break;

        // ── Bonus ──
        case 'add_bonus':
            $peId = (int)($_POST['payroll_employee_id'] ?? 0);
            $name = $_POST['bonus_name'] ?? '';
            $amount = (float)($_POST['amount'] ?? 0);
            $type = $_POST['bonus_type'] ?? 'one_time';
            $date = $_POST['bonus_date'] ?? date('Y-m-d');
            $isTaxable = isset($_POST['is_taxable']) ? 1 : 0;

            $pconn = getPayrollConnection();
            if (!$pconn) throw new Exception('Payroll DB connection failed');
            $stmt = $pconn->prepare("INSERT INTO payroll_bonus (payroll_employee_id, bonus_type, bonus_name, amount, is_taxable, bonus_date, status, created_by) VALUES (?, ?, ?, ?, ?, ?, 'pending', ?)");
            if (!$stmt) throw new Exception('Prepare failed: ' . $pconn->error);
            $stmt->bind_param('issdisi', $peId, $type, $name, $amount, $isTaxable, $date, $staffId);
            $stmt->execute() ? $_SESSION['success'] = 'Bonus recorded.' : $_SESSION['error'] = 'Failed: ' . $stmt->error;
            $stmt->close();
            $pconn->close();
            break;

        // ── Loan ──
        case 'add_loan':
            $peId = (int)($_POST['payroll_employee_id'] ?? 0);
            $principal = (float)($_POST['principal_amount'] ?? 0);
            $interest = (float)($_POST['interest_rate'] ?? 0);
            $installments = (int)($_POST['installments'] ?? 1);
            $loanDate = $_POST['loan_date'] ?? date('Y-m-d');
            $loanType = $_POST['loan_type'] ?? 'staff_loan';

            $totalAmount = $principal + ($principal * $interest / 100);
            $installmentAmount = $installments > 0 ? round($totalAmount / $installments, 2) : $totalAmount;
            $loanNumber = 'LN-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));

            $pconn = getPayrollConnection();
            if (!$pconn) throw new Exception('Payroll DB connection failed');
            $stmt = $pconn->prepare("INSERT INTO payroll_loans (payroll_employee_id, loan_number, loan_type, principal_amount, interest_rate, installments, installment_amount, loan_date, status, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?)");
            if (!$stmt) throw new Exception('Prepare failed: ' . $pconn->error);
            $stmt->bind_param('issdiiidsi', $peId, $loanNumber, $loanType, $principal, $interest, $installments, $installmentAmount, $loanDate, $staffId);
            $stmt->execute() ? $_SESSION['success'] = 'Loan recorded.' : $_SESSION['error'] = 'Failed: ' . $stmt->error;
            $stmt->close();
            $pconn->close();
            break;

        case 'approve_loan':
            $loanId = (int)($_POST['loan_id'] ?? 0);
            $pconn = getPayrollConnection();
            if (!$pconn) throw new Exception('Payroll DB connection failed');
            $stmt = $pconn->prepare("UPDATE payroll_loans SET status='active', approved_by=?, approved_at=NOW() WHERE id=?");
            $stmt->bind_param('ii', $staffId, $loanId);
            $stmt->execute();
            $stmt->close();
            $pconn->close();
            logPayrollApproval('loan', $loanId, 'approved', 'Approval', null, $staffId);
            $_SESSION['success'] = 'Loan approved.';
            break;

        // ── Payroll Period ──
        case 'create_period':
            $month = (int)($_POST['month'] ?? 0);
            $year = (int)($_POST['year'] ?? 0);
            $frequency = $_POST['frequency'] ?? 'monthly';
            $result = createPayrollPeriod($month, $year, $frequency, $staffId);
            if ($result['success']) {
                $_SESSION['success'] = $result['message'];
            } else {
                $_SESSION['error'] = $result['message'];
            }
            break;

        case 'open_period':
            $periodId = (int)($_POST['period_id'] ?? 0);
            $pconn = getPayrollConnection();
            if (!$pconn) throw new Exception('Payroll DB connection failed');
            $stmt = $pconn->prepare("UPDATE payroll_periods SET status='open' WHERE id=?");
            $stmt->bind_param("i", $periodId);
            $stmt->execute();
            $stmt->close();
            $pconn->close();
            logPayrollAudit($staffId, 'period_opened', 'payroll_period', $periodId, null, null);
            $_SESSION['success'] = 'Payroll period opened.';
            break;

        case 'close_period':
            $periodId = (int)($_POST['period_id'] ?? 0);
            $pconn = getPayrollConnection();
            if (!$pconn) throw new Exception('Payroll DB connection failed');
            $stmt = $pconn->prepare("UPDATE payroll_periods SET status='closed', is_closed=1, closed_by=?, closed_at=NOW() WHERE id=? AND is_locked=1");
            $stmt->bind_param("ii", $staffId, $periodId);
            $stmt->execute();
            $stmt->close();
            $pconn->close();
            $_SESSION['success'] = 'Payroll period closed.';
            break;

        // ── Payroll Processing ──
        case 'process_payroll':
            $periodId = (int)($_POST['payroll_period_id'] ?? 0);
            if (!$periodId) throw new Exception('Payroll period ID required');
            $result = processPayrollRun($periodId, $staffId);
            if ($result['success']) {
                $_SESSION['success'] = $result['message'];
            } else {
                $_SESSION['error'] = $result['message'];
            }
            break;

        case 'generate_payslips':
            $runId = (int)($_POST['payroll_run_id'] ?? 0);
            if (!$runId) throw new Exception('Payroll run ID required');
            $result = generatePayslipsForRun($runId, $staffId);
            if ($result['success']) {
                $_SESSION['success'] = $result['message'];
            } else {
                $_SESSION['error'] = $result['message'];
            }
            break;

        // ── Approval Actions ──
        case 'approve_run':
            $runId = (int)($_POST['payroll_run_id'] ?? 0);
            $step = $_POST['step'] ?? 'Approval';
            $comments = $_POST['comments'] ?? '';

            $pconn = getPayrollConnection();
            if (!$pconn) throw new Exception('Payroll DB connection failed');
            $stmt = $pconn->prepare("UPDATE payroll_runs SET status='approved', approved_by=?, approved_at=NOW() WHERE id=?");
            $stmt->bind_param("ii", $staffId, $runId);
            $stmt->execute();
            $stmt->close();
            $pconn->close();
            logPayrollApproval('payroll_run', $runId, 'approved', $step, $comments, $staffId);
            $_SESSION['success'] = "Payroll run approved ($step).";
            break;

        case 'authorize_run':
            $runId = (int)($_POST['payroll_run_id'] ?? 0);
            $comments = $_POST['comments'] ?? '';
            $pconn = getPayrollConnection();
            if (!$pconn) throw new Exception('Payroll DB connection failed');
            $stmt = $pconn->prepare("UPDATE payroll_runs SET status='paid', paid_by=?, paid_at=NOW() WHERE id=?");
            $stmt->bind_param("ii", $staffId, $runId);
            $stmt->execute();
            $stmt->close();
            $stmt = $pconn->prepare("UPDATE payroll_items SET payment_status='paid', payment_date=CURDATE() WHERE payroll_run_id=? AND status='active'");
            $stmt->bind_param('i', $runId);
            $stmt->execute();
            $stmt->close();
            $pconn->close();
            logPayrollApproval('payroll_run', $runId, 'authorized', 'Principal Authorization', $comments, $staffId);
            $_SESSION['success'] = 'Payroll run authorized for payment.';
            break;

        case 'reject_run':
            $runId = (int)($_POST['payroll_run_id'] ?? 0);
            $comments = $_POST['comments'] ?? '';
            $pconn = getPayrollConnection();
            if (!$pconn) throw new Exception('Payroll DB connection failed');
            $stmt = $pconn->prepare("UPDATE payroll_runs SET status='draft' WHERE id=?");
            $stmt->bind_param("i", $runId);
            $stmt->execute();
            $stmt->close();
            $pconn->close();
            logPayrollApproval('payroll_run', $runId, 'rejected', 'Approval', $comments, $staffId);
            $_SESSION['error'] = 'Payroll run rejected.';
            break;

        // ── Settings ──
        case 'update_setting':
            $key = $_POST['setting_key'] ?? '';
            $value = $_POST['setting_value'] ?? '';
            if ($key) {
                $pconn = getPayrollConnection();
                $stmt = $pconn->prepare("INSERT INTO payroll_settings (setting_key, setting_value, updated_by) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value), updated_by=VALUES(updated_by)");
                $stmt->bind_param('ssi', $key, $value, $staffId);
                $stmt->execute();
                $stmt->close();
                $pconn->close();
                $_SESSION['success'] = 'Setting updated.';
            }
            break;

        // ── Settings Bulk Save ──
        case 'save_settings':
            $pconn = getPayrollConnection();
            if (!$pconn) throw new Exception('Payroll DB connection failed');
            foreach ($_POST as $key => $value) {
                if (strpos($key, 'setting_') === 0) {
                    $settingKey = substr($key, 8);
                    $settingValue = (string)$value;
                    $stmt = $pconn->prepare("INSERT INTO payroll_settings (setting_key, setting_value, updated_by) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value), updated_by=VALUES(updated_by)");
                    $stmt->bind_param('ssi', $settingKey, $settingValue, $staffId);
                    $stmt->execute();
                    $stmt->close();
                }
            }
            $pconn->close();
            $_SESSION['success'] = 'Settings saved.';
            break;

        // ── Payment Processing ──
        case 'record_payment':
            $runId = (int)($_POST['payroll_run_id'] ?? 0);
            $payDate = $_POST['payment_date'] ?? date('Y-m-d');
            $payMethod = $_POST['payment_method'] ?? 'bank_transfer';
            $refNumber = $_POST['reference_number'] ?? '';

            $pconn = getPayrollConnection();
            if (!$pconn) throw new Exception('Payroll DB connection failed');

            $stmt = $pconn->prepare("SELECT total_net, total_employees FROM payroll_runs WHERE id=?");
            $stmt->bind_param("i", $runId);
            $stmt->execute();
            $runData = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if (!$runData) throw new Exception('Payroll run not found');

            $stmt = $pconn->prepare("INSERT INTO payroll_payments (payroll_run_id, payment_date, payment_method, total_amount, employee_count, reference_number, status, processed_by) VALUES (?, ?, ?, ?, ?, ?, 'completed', ?)");
            $stmt->bind_param('issdisi', $runId, $payDate, $payMethod, $runData['total_net'], $runData['total_employees'], $refNumber, $staffId);
            $stmt->execute();
            $stmt->close();

            $stmt = $pconn->prepare("UPDATE payroll_runs SET status='paid', paid_by=?, paid_at=NOW() WHERE id=?");
            $stmt->bind_param("ii", $staffId, $runId);
            $stmt->execute();
            $stmt->close();
            $stmt = $pconn->prepare("UPDATE payroll_items SET payment_status='paid', payment_date=?, payment_reference=? WHERE payroll_run_id=? AND status='active'");
            $stmt->bind_param("ssii", $payDate, $refNumber, $runId);
            $stmt->execute();
            $stmt->close();
            $pconn->close();

            $_SESSION['success'] = 'Payment recorded.';
            break;

        // ── Employee Data (JSON) for Select2 / modals ──
        case 'get_employees_json':
            $status = $_GET['status'] ?? 'active';
            $rows = getPayrollEmployees($status);
            jsonResponse(true, '', $rows);
            break;

        // ── Dashboard Stats (JSON) ──
        case 'get_dashboard_stats':
            $stats = getPayrollDashboardStats();
            jsonResponse(true, '', $stats);
            break;

        // ── Payroll Periods (JSON) ──
        case 'get_periods_json':
            $status = $_GET['status'] ?? null;
            $rows = getPayrollPeriods($status);
            jsonResponse(true, '', $rows);
            break;

        default:
            if ($isAjax) {
                jsonResponse(false, "Unknown action: $action");
            }
            $_SESSION['error'] = "Unknown action: $action";
            break;
    }
} catch (Exception $e) {
    error_log('Payroll handler error: ' . $e->getMessage());
    if ($isAjax) {
        jsonResponse(false, $e->getMessage());
    }
    $_SESSION['error'] = 'Error: ' . $e->getMessage();
}

// Non-AJAX: redirect back
if (!$isAjax) {
    header("Location: $referrer");
    exit;
}
