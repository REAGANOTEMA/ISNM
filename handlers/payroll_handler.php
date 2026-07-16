<?php
/**
 * ISNM Payroll Management System â€” AJAX/Form POST Handler
 * All payroll CRUD operations, processing, and approvals via a single endpoint.
 */

require_once __DIR__ . '/../includes/payroll_functions.php';
require_once __DIR__ . '/../includes/staff_dashboard_access.php';

$ctx = bootstrapStaffDashboard([]);
$conn = $ctx['staff'];
$user = $ctx['user'];
$staffId = (int)($user['id'] ?? 0);
$userRole = $user['role'] ?? '';

// CSRF validation
if (!empty($_SESSION['user_id'])) {
    $csrfToken = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (empty($csrfToken) || !isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrfToken)) {
        header('Content-Type: application/json');
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Invalid security token. Please refresh and try again.']);
        exit();
    }
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$isAjax = (strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest') || (($_POST['format'] ?? '') === 'json');
$referrer = $_SERVER['HTTP_REFERER'] ?? '../payroll.php';
$allowedHost = $_SERVER['SERVER_NAME'] ?? '';
if (!empty($allowedHost) && isset(parse_url($referrer)['host']) && parse_url($referrer)['host'] !== $allowedHost) {
    $referrer = '../payroll.php';
}

try {
    switch ($action) {

        // â”€â”€ Employee Payroll Profiles â”€â”€
        case 'create_employee_profile':
            $staffIdParam = (int)($_POST['staff_id'] ?? 0);
            $employmentType = $_POST['employment_type'] ?? 'permanent';
            $monthlySalary = (float)($_POST['monthly_salary'] ?? 0);
            $paymentMethod = $_POST['payment_method'] ?? 'bank';
            $bankName = $_POST['bank_name'] ?? '';
            $bankAccount = $_POST['bank_account_number'] ?? '';
            $tin = $_POST['tin'] ?? '';
            $nssfNumber = $_POST['nssf_number'] ?? '';
            $hireDate = $_POST['hire_date'] ?? null;

            $pconn = getPayrollConnection();
            if (!$pconn) throw new Exception('Payroll DB connection failed');

            $stmt = $pconn->prepare("INSERT INTO payroll_employees (staff_id, bank_name, bank_account, tax_identification, nssf_number, salary_type, basic_salary, hire_date, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active')");
            if (!$stmt) throw new Exception('Prepare failed: ' . $pconn->error);
            $salaryType = ($employmentType === 'annual') ? 'annual' : 'monthly';
            $stmt->bind_param('isssssds', $staffIdParam, $bankName, $bankAccount, $tin, $nssfNumber, $salaryType, $monthlySalary, $hireDate);
            if (!$stmt->execute()) throw new Exception('Execute failed: ' . $stmt->error);
            $newId = $stmt->insert_id;
            $stmt->close();


            logPayrollAudit($staffId, 'employee_created', 'payroll_employee', $newId, null, $_POST);
            $_SESSION['success'] = 'Payroll profile created.';
            break;

        case 'update_employee_profile':
            $profileId = (int)($_POST['profile_id'] ?? 0);
            $monthlySalary = (float)($_POST['monthly_salary'] ?? 0);
            $employmentType = $_POST['employment_type'] ?? 'permanent';
            $bankName = $_POST['bank_name'] ?? '';
            $bankAccount = $_POST['bank_account_number'] ?? '';
            $tin = $_POST['tin'] ?? '';
            $nssfNumber = $_POST['nssf_number'] ?? '';
            $payrollStatus = $_POST['payroll_status'] ?? 'active';

            $pconn = getPayrollConnection();
            if (!$pconn) throw new Exception('Payroll DB connection failed');
            $stmt = $pconn->prepare("UPDATE payroll_employees SET bank_name=?, bank_account=?, tax_identification=?, nssf_number=?, salary_type=?, basic_salary=?, status=? WHERE id=?");
            if (!$stmt) throw new Exception('Prepare failed: ' . $pconn->error);
            $salaryType = ($employmentType === 'annual') ? 'annual' : 'monthly';
            $stmt->bind_param('sssssdsi', $bankName, $bankAccount, $tin, $nssfNumber, $salaryType, $monthlySalary, $payrollStatus, $profileId);
            if (!$stmt->execute()) throw new Exception('Execute failed: ' . $stmt->error);
            $stmt->close();


            logPayrollAudit($staffId, 'employee_updated', 'payroll_employee', $profileId, null, $_POST);
            $_SESSION['success'] = 'Payroll profile updated.';
            break;

        // â”€â”€ Allowance Assignment â”€â”€
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
            if (!$stmt->execute()) { error_log('payroll assign_allowance failed: ' . ($stmt->error ?? 'unknown')); $_SESSION['error'] = 'Failed to assign allowance.'; } else { $_SESSION['success'] = 'Allowance assigned.'; }
            $stmt->close();

            break;

        case 'remove_allowance':
            $allowanceId = (int)($_POST['allowance_id'] ?? 0);
            $pconn = getPayrollConnection();
            if (!$pconn) throw new Exception('Payroll DB connection failed');
            $stmt = $pconn->prepare("UPDATE payroll_employee_allowances SET status='inactive' WHERE id=?");
            $stmt->bind_param('i', $allowanceId);
            if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
            $stmt->close();

            $_SESSION['success'] = 'Allowance removed.';
            break;

        // â”€â”€ Deduction Assignment â”€â”€
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
            $stmt->bind_param('iidisi', $peId, $typeId, $amount, $isRecurring, $effectiveFrom, $staffId);
            if (!$stmt->execute()) { error_log('payroll assign_deduction failed: ' . ($stmt->error ?? 'unknown')); $_SESSION['error'] = 'Failed to assign deduction.'; } else { $_SESSION['success'] = 'Deduction assigned.'; }
            $stmt->close();

            break;

        case 'remove_deduction':
            $dedId = (int)($_POST['deduction_id'] ?? 0);
            $pconn = getPayrollConnection();
            if (!$pconn) throw new Exception('Payroll DB connection failed');
            $stmt = $pconn->prepare("UPDATE payroll_employee_deductions SET status='inactive' WHERE id=?");
            $stmt->bind_param('i', $dedId);
            if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
            $stmt->close();

            $_SESSION['success'] = 'Deduction removed.';
            break;

        // â”€â”€ Overtime â”€â”€
        case 'add_overtime':
            $staffIdParam = (int)($_POST['payroll_employee_id'] ?? $_POST['staff_id'] ?? 0);
            $hours = (float)($_POST['hours_worked'] ?? 0);
            $type = $_POST['overtime_type'] ?? 'normal';
            $month = $_POST['overtime_date'] ?? date('Y-m');

            $pconn = getPayrollConnection();
            if (!$pconn) throw new Exception('Payroll DB connection failed');

            $empStmt = $pconn->prepare("SELECT basic_salary FROM payroll_employees WHERE staff_id=?");
            $empStmt->bind_param('i', $staffIdParam);
            if (!$empStmt->execute()) { error_log('$empStmt execute failed: ' . ($empStmt->error ?? 'unknown')); };
            $empRow = $empStmt->get_result()->fetch_assoc();
            $empStmt->close();
            $basicSalary = (float)($empRow['basic_salary'] ?? 0);
            $hourlyRate = ($basicSalary > 0) ? $basicSalary / 160 : 0;

            $rates = ['normal' => 1.5, 'weekend' => 2.0, 'holiday' => 2.5, 'night' => 2.0];
            $multiplier = $rates[$type] ?? 1.5;
            $totalPay = $hours * $hourlyRate * $multiplier;

            $stmt = $pconn->prepare("INSERT INTO payroll_overtime (staff_id, hours, rate, total_pay, month, created_by) VALUES (?, ?, ?, ?, ?, ?)");
            if (!$stmt) throw new Exception('Prepare failed: ' . $pconn->error);
            $stmt->bind_param('idddsi', $staffIdParam, $hours, $hourlyRate, $totalPay, $month, $staffId);
            if (!$stmt->execute()) { error_log('payroll record_overtime failed: ' . ($stmt->error ?? 'unknown')); $_SESSION['error'] = 'Failed to record overtime.'; } else { $_SESSION['success'] = 'Overtime recorded.'; }
            $stmt->close();

            break;

        case 'approve_overtime':
            $otId = (int)($_POST['overtime_id'] ?? 0);
            $pconn = getPayrollConnection();
            if (!$pconn) throw new Exception('Payroll DB connection failed');
            $stmt = $pconn->prepare("UPDATE payroll_overtime SET status='approved', approved_by=?, approved_at=NOW() WHERE id=?");
            $stmt->bind_param('ii', $staffId, $otId);
            if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
            $stmt->close();

            logPayrollApproval('overtime', $otId, 'approved', 'Approval', 'Approved by ' . $userRole, $staffId);
            $_SESSION['success'] = 'Overtime approved.';
            break;

        // â”€â”€ Bonus â”€â”€
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
            if (!$stmt->execute()) { error_log('payroll add_bonus failed: ' . ($stmt->error ?? 'unknown')); $_SESSION['error'] = 'Failed to record bonus.'; } else { $_SESSION['success'] = 'Bonus recorded.'; }
            $stmt->close();

            break;

        // â”€â”€ Loan â”€â”€
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
            $stmt->bind_param('issididisi', $peId, $loanNumber, $loanType, $principal, $interest, $installments, $installmentAmount, $loanDate, $staffId);
            if (!$stmt->execute()) { error_log('payroll add_loan failed: ' . ($stmt->error ?? 'unknown')); $_SESSION['error'] = 'Failed to record loan.'; } else { $_SESSION['success'] = 'Loan recorded.'; }
            $stmt->close();

            break;

        case 'approve_loan':
            $loanId = (int)($_POST['loan_id'] ?? 0);
            $pconn = getPayrollConnection();
            if (!$pconn) throw new Exception('Payroll DB connection failed');
            $stmt = $pconn->prepare("UPDATE payroll_loans SET status='active', approved_by=?, approved_at=NOW() WHERE id=?");
            $stmt->bind_param('ii', $staffId, $loanId);
            if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
            $stmt->close();

            logPayrollApproval('loan', $loanId, 'approved', 'Approval', null, $staffId);
            $_SESSION['success'] = 'Loan approved.';
            break;

        // â”€â”€ Payroll Period â”€â”€
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
            if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
            $stmt->close();

            logPayrollAudit($staffId, 'period_opened', 'payroll_period', $periodId, null, null);
            $_SESSION['success'] = 'Payroll period opened.';
            break;

        case 'close_period':
            $periodId = (int)($_POST['period_id'] ?? 0);
            $pconn = getPayrollConnection();
            if (!$pconn) throw new Exception('Payroll DB connection failed');
            $stmt = $pconn->prepare("UPDATE payroll_periods SET status='closed', is_closed=1, closed_by=?, closed_at=NOW() WHERE id=? AND is_locked=1");
            $stmt->bind_param("ii", $staffId, $periodId);
            if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
            $stmt->close();

            $_SESSION['success'] = 'Payroll period closed.';
            break;

        // â”€â”€ Payroll Processing â”€â”€
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

        // â”€â”€ Approval Actions â”€â”€
        case 'approve_run':
            $runId = (int)($_POST['payroll_run_id'] ?? 0);
            $step = $_POST['step'] ?? 'Approval';
            $comments = $_POST['comments'] ?? '';

            $pconn = getPayrollConnection();
            if (!$pconn) throw new Exception('Payroll DB connection failed');
            $stmt = $pconn->prepare("UPDATE payroll_runs SET status='approved', approved_by=?, approved_at=NOW() WHERE id=?");
            $stmt->bind_param("ii", $staffId, $runId);
            if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
            $stmt->close();

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
            if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
            $stmt->close();
            $stmt = $pconn->prepare("UPDATE payroll_items SET payment_status='paid', payment_date=CURDATE() WHERE payroll_run_id=? AND status='active'");
            $stmt->bind_param('i', $runId);
            if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
            $stmt->close();

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
            if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
            $stmt->close();

            logPayrollApproval('payroll_run', $runId, 'rejected', 'Approval', $comments, $staffId);
            $_SESSION['error'] = 'Payroll run rejected.';
            break;

        // â”€â”€ Settings â”€â”€
        case 'update_setting':
            $key = $_POST['setting_key'] ?? '';
            $value = $_POST['setting_value'] ?? '';
            if ($key) {
                $pconn = getPayrollConnection();
                $stmt = $pconn->prepare("INSERT INTO payroll_settings (setting_key, setting_value, updated_by) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value), updated_by=VALUES(updated_by)");
                $stmt->bind_param('ssi', $key, $value, $staffId);
                if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
                $stmt->close();
    
                $_SESSION['success'] = 'Setting updated.';
            }
            break;

        // â”€â”€ Settings Bulk Save â”€â”€
        case 'save_settings':
            $pconn = getPayrollConnection();
            if (!$pconn) throw new Exception('Payroll DB connection failed');
            foreach ($_POST as $key => $value) {
                if (strpos($key, 'setting_') === 0) {
                    $settingKey = substr($key, 8);
                    $settingValue = (string)$value;
                    $stmt = $pconn->prepare("INSERT INTO payroll_settings (setting_key, setting_value, updated_by) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value), updated_by=VALUES(updated_by)");
                    $stmt->bind_param('ssi', $settingKey, $settingValue, $staffId);
                    if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
                    $stmt->close();
                }
            }

            $_SESSION['success'] = 'Settings saved.';
            break;

        // â”€â”€ Payment Processing â”€â”€
        case 'record_payment':
            $runId = (int)($_POST['payroll_run_id'] ?? 0);
            $payDate = $_POST['payment_date'] ?? date('Y-m-d');
            $payMethod = $_POST['payment_method'] ?? 'bank_transfer';
            $refNumber = $_POST['reference_number'] ?? '';

            $pconn = getPayrollConnection();
            if (!$pconn) throw new Exception('Payroll DB connection failed');

            $stmt = $pconn->prepare("SELECT total_net, total_employees FROM payroll_runs WHERE id=?");
            $stmt->bind_param("i", $runId);
            if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
            $runData = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if (!$runData) throw new Exception('Payroll run not found');

            $stmt = $pconn->prepare("INSERT INTO payroll_payments (payroll_run_id, payment_date, payment_method, total_amount, employee_count, reference_number, status, processed_by) VALUES (?, ?, ?, ?, ?, ?, 'completed', ?)");
            $stmt->bind_param('issdisi', $runId, $payDate, $payMethod, $runData['total_net'], $runData['total_employees'], $refNumber, $staffId);
            if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
            $stmt->close();

            $stmt = $pconn->prepare("UPDATE payroll_runs SET status='paid', paid_by=?, paid_at=NOW() WHERE id=?");
            $stmt->bind_param("ii", $staffId, $runId);
            if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
            $stmt->close();
            $stmt = $pconn->prepare("UPDATE payroll_items SET payment_status='paid', payment_date=?, payment_reference=? WHERE payroll_run_id=? AND status='active'");
            $stmt->bind_param("ssi", $payDate, $refNumber, $runId);
            if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
            $stmt->close();


            $_SESSION['success'] = 'Payment recorded.';
            break;

        // â”€â”€ Employee Data (JSON) for Select2 / modals â”€â”€
        case 'get_employees_json':
            $status = $_GET['status'] ?? 'active';
            $rows = getPayrollEmployees($status);
            jsonResponse(true, '', $rows);
            break;

        // â”€â”€ Dashboard Stats (JSON) â”€â”€
        case 'get_dashboard_stats':
            $stats = getPayrollDashboardStats();
            jsonResponse(true, '', $stats);
            break;

        // â”€â”€ Payroll Periods (JSON) â”€â”€
        case 'get_periods_json':
            $status = $_GET['status'] ?? null;
            $rows = getPayrollPeriods($status);
            jsonResponse(true, '', $rows);
            break;

        // â”€â”€ Create Leave Request â”€â”€
        case 'create_leave_request':
            $staffIdReq = (int)($_POST['staff_id'] ?? 0);
            $leaveTypeId = (int)($_POST['leave_type_id'] ?? 0);
            $startDate = trim($_POST['start_date'] ?? '');
            $endDate = trim($_POST['end_date'] ?? '');
            $reason = trim($_POST['reason'] ?? '');
            if ($staffIdReq && $leaveTypeId && $startDate && $endDate) {
                $pconn = getPayrollConnection();
                if (!$pconn) throw new Exception('Payroll DB connection failed');
                $stmt = $pconn->prepare("INSERT INTO staff_leave_requests (staff_id, leave_type_id, start_date, end_date, reason, status) VALUES (?, ?, ?, ?, ?, 'Pending')");
                if ($stmt) {
                    $stmt->bind_param("iisss", $staffIdReq, $leaveTypeId, $startDate, $endDate, $reason);
                    if ($stmt->execute()) {
                        $_SESSION['success'] = 'Leave request submitted.';
                    } else {
                        $_SESSION['error'] = 'Failed to submit leave request.';
                    }
                    $stmt->close();
                } else {
                    $_SESSION['error'] = 'Failed to prepare leave request.';
                }
    
            } else {
                $_SESSION['error'] = 'Please fill all required fields.';
            }
            break;

        // â”€â”€ Approve Leave â”€â”€
        case 'approve_leave':
            $leaveId = (int)($_POST['leave_id'] ?? 0);
            if ($leaveId) {
                $pconn = getPayrollConnection();
                if (!$pconn) throw new Exception('Payroll DB connection failed');
                $stmt = $pconn->prepare("UPDATE staff_leave_requests SET status='Approved', reviewed_by=? WHERE id=? AND status='Pending'");
                if ($stmt) {
                    $stmt->bind_param("ii", $staffId, $leaveId);
                    if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
                    $stmt->close();
                    $_SESSION['success'] = 'Leave request approved.';
                } else {
                    $_SESSION['error'] = 'Could not approve leave request.';
                }
    
            }
            break;

        // â”€â”€ Reject Leave â”€â”€
        case 'reject_leave':
            $leaveId = (int)($_POST['leave_id'] ?? 0);
            if ($leaveId) {
                $pconn = getPayrollConnection();
                if (!$pconn) throw new Exception('Payroll DB connection failed');
                $stmt = $pconn->prepare("UPDATE staff_leave_requests SET status='Rejected', reviewed_by=? WHERE id=? AND status='Pending'");
                if ($stmt) {
                    $stmt->bind_param("ii", $staffId, $leaveId);
                    if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
                    $stmt->close();
                    $_SESSION['success'] = 'Leave request rejected.';
                } else {
                    $_SESSION['error'] = 'Could not reject leave request.';
                }
    
            }
            break;

        // â”€â”€ Add Leave Type â”€â”€
        case 'add_leave_type':
            $typeName = trim($_POST['type_name'] ?? '');
            $daysPerYear = (int)($_POST['days_per_year'] ?? 30);
            $description = trim($_POST['description'] ?? '');
            if ($typeName) {
                $pconn = getPayrollConnection();
                if (!$pconn) throw new Exception('Payroll DB connection failed');
                $stmt = $pconn->prepare("INSERT INTO leave_types (type_name, leave_type_name, days_per_year, description, is_active) VALUES (?, ?, ?, ?, 1)");
                if ($stmt) {
                    $stmt->bind_param("ssis", $typeName, $typeName, $daysPerYear, $description);
                    if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
                    $stmt->close();
                    $_SESSION['success'] = 'Leave type added.';
                } else {
                    $stmt2 = $pconn->prepare("INSERT INTO leave_types (type_name, days_per_year, is_active) VALUES (?, ?, 1)");
                    if ($stmt2) {
                        $stmt2->bind_param("si", $typeName, $daysPerYear);
                        if (!$stmt2->execute()) { error_log('$stmt2 execute failed: ' . ($stmt2->error ?? 'unknown')); };
                        $stmt2->close();
                        $_SESSION['success'] = 'Leave type added.';
                    }
                }
    
            }
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
        jsonResponse(false, 'An error occurred processing your request');
    }
    $_SESSION['error'] = 'An error occurred processing your request';
}

// Non-AJAX: redirect back
if (!$isAjax) {
    header("Location: $referrer");
    exit;
}
