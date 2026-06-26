<?php
if (!function_exists('payGetEmployees')) {
    function payGetEmployees($conn, $staff_id = null) {
        $sql = "SELECT pe.*, s.full_name, s.staff_id, s.department_id, s.role_id, s.employment_type,
                       d.department_name, COALESCE(r.role_name, sr.role_name) as role_name
                FROM payroll_employees pe
                JOIN staff s ON pe.staff_id = s.id
                LEFT JOIN departments d ON s.department_id = d.id
                LEFT JOIN roles r ON s.role_id = r.id
                LEFT JOIN staff_roles sr ON s.role_id = sr.id";
        if ($staff_id) {
            $stmt = $conn->prepare("$sql WHERE pe.staff_id = ?");
            if (!$stmt) return null;
            $stmt->bind_param('i', $staff_id);
            $stmt->execute();
            return $stmt->get_result()->fetch_assoc();
        }
        $result = $conn->query("$sql ORDER BY s.full_name");
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }
}

if (!function_exists('payGetRuns')) {
    function payGetRuns($conn, $id = null, $limit = 20) {
        $sql = "SELECT pr.*, 
                       (SELECT COUNT(*) FROM payroll_details pd WHERE pd.payroll_run_id = pr.id) as employee_count,
                       cr.full_name as created_name, ap.full_name as approved_name
                FROM payroll_runs pr
                LEFT JOIN staff cr ON pr.created_by = cr.id
                LEFT JOIN staff ap ON pr.approved_by = ap.id";
        if ($id) {
            $stmt = $conn->prepare("$sql WHERE pr.id = ?");
            if (!$stmt) return null;
            $stmt->bind_param('i', $id);
            $stmt->execute();
            return $stmt->get_result()->fetch_assoc();
        }
        $result = $conn->query("$sql ORDER BY pr.created_at DESC LIMIT $limit");
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }
}

if (!function_exists('payGetDetails')) {
    function payGetDetails($conn, $payroll_run_id, $staff_id = null) {
        $sql = "SELECT pd.*, s.full_name, s.staff_id, d.department_name
                FROM payroll_details pd
                JOIN staff s ON pd.staff_id = s.id
                LEFT JOIN departments d ON s.department_id = d.id
                WHERE pd.payroll_run_id = ?";
        $types = 'i';
        $params = [$payroll_run_id];
        if ($staff_id) {
            $sql .= " AND pd.staff_id = ?";
            $types .= 'i';
            $params[] = $staff_id;
        }
        $stmt = $conn->prepare($sql);
        if (!$stmt) return $staff_id ? null : [];
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        return $staff_id ? $result->fetch_assoc() : $result->fetch_all(MYSQLI_ASSOC);
    }
}

if (!function_exists('payGetAllowances')) {
    function payGetAllowances($conn, $staff_id = null, $month = null) {
        $sql = "SELECT pa.*, s.full_name FROM payroll_allowances pa JOIN staff s ON pa.staff_id = s.id WHERE 1=1";
        $types = '';
        $params = [];
        if ($staff_id) { $sql .= " AND pa.staff_id = ?"; $types .= 'i'; $params[] = $staff_id; }
        if ($month) { $sql .= " AND pa.month = ?"; $types .= 's'; $params[] = $month; }
        $sql .= " ORDER BY s.full_name, pa.allowance_type";
        $stmt = $conn->prepare($sql);
        if (!$stmt) return [];
        if ($params) $stmt->bind_param($types, ...$params);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}

if (!function_exists('payGetDeductions')) {
    function payGetDeductions($conn, $staff_id = null, $month = null) {
        $sql = "SELECT pd.*, s.full_name FROM payroll_deductions pd JOIN staff s ON pd.staff_id = s.id WHERE 1=1";
        $types = '';
        $params = [];
        if ($staff_id) { $sql .= " AND pd.staff_id = ?"; $types .= 'i'; $params[] = $staff_id; }
        if ($month) { $sql .= " AND pd.month = ?"; $types .= 's'; $params[] = $month; }
        $sql .= " ORDER BY s.full_name, pd.deduction_type";
        $stmt = $conn->prepare($sql);
        if (!$stmt) return [];
        if ($params) $stmt->bind_param($types, ...$params);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}

if (!function_exists('payGetOvertime')) {
    function payGetOvertime($conn, $staff_id = null, $month = null) {
        $sql = "SELECT po.*, s.full_name, s.staff_id, ap.full_name as approver_name 
                FROM payroll_overtime po 
                JOIN staff s ON po.staff_id = s.id 
                LEFT JOIN staff ap ON po.approved_by = ap.id 
                WHERE 1=1";
        $types = '';
        $params = [];
        if ($staff_id) { $sql .= " AND po.staff_id = ?"; $types .= 'i'; $params[] = $staff_id; }
        if ($month) { $sql .= " AND po.month = ?"; $types .= 's'; $params[] = $month; }
        $sql .= " ORDER BY s.full_name";
        $stmt = $conn->prepare($sql);
        if (!$stmt) return [];
        if ($params) $stmt->bind_param($types, ...$params);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}

if (!function_exists('payGetBonuses')) {
    function payGetBonuses($conn, $staff_id = null, $month = null) {
        $sql = "SELECT pb.*, s.full_name FROM payroll_bonuses pb JOIN staff s ON pb.staff_id = s.id WHERE 1=1";
        $types = '';
        $params = [];
        if ($staff_id) { $sql .= " AND pb.staff_id = ?"; $types .= 'i'; $params[] = $staff_id; }
        if ($month) { $sql .= " AND pb.month = ?"; $types .= 's'; $params[] = $month; }
        $sql .= " ORDER BY s.full_name, pb.bonus_type";
        $stmt = $conn->prepare($sql);
        if (!$stmt) return [];
        if ($params) $stmt->bind_param($types, ...$params);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}

if (!function_exists('payGetApprovals')) {
    function payGetApprovals($conn, $payroll_run_id) {
        $stmt = $conn->prepare("SELECT pa.*, s.full_name as approver_name 
                                FROM payroll_approvals pa 
                                LEFT JOIN staff s ON pa.approved_by = s.id 
                                WHERE pa.payroll_run_id = ? 
                                ORDER BY FIELD(pa.level, 'HR','PayrollOfficer','Bursar','DirectorFinance')");
        if (!$stmt) return [];
        $stmt->bind_param('i', $payroll_run_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}

if (!function_exists('payCalculateGross')) {
    function payCalculateGross($basic_salary, $total_allowances, $overtime_pay, $bonuses) {
        return $basic_salary + $total_allowances + $overtime_pay + $bonuses;
    }
}

if (!function_exists('payCalculatePAYE')) {
    function payCalculatePAYE($gross_pay) {
        $annual = $gross_pay * 12;
        $tax = 0;
        if ($annual <= 2820000) {
            $tax = 0;
        } elseif ($annual <= 4000000) {
            $tax = ($annual - 2820000) * 0.1;
        } elseif ($annual <= 10000000) {
            $tax = 118000 + ($annual - 4000000) * 0.2;
        } elseif ($annual <= 20000000) {
            $tax = 1318000 + ($annual - 10000000) * 0.3;
        } else {
            $tax = 4318000 + ($annual - 20000000) * 0.4;
        }
        return round($tax / 12, 0);
    }
}

if (!function_exists('payCalculateNSSF')) {
    function payCalculateNSSF($basic_salary) {
        $ceiling = 5000000;
        $employee_rate = 0.05;
        $employer_rate = 0.10;
        $subject = min($basic_salary, $ceiling);
        return [
            'employee' => round($subject * $employee_rate, 0),
            'employer' => round($subject * $employer_rate, 0)
        ];
    }
}

if (!function_exists('payCalculateNet')) {
    function payCalculateNet($gross_pay, $paye_tax, $nssf_employee, $leave_deductions, $other_deductions) {
        return $gross_pay - $paye_tax - $nssf_employee - $leave_deductions - $other_deductions;
    }
}

if (!function_exists('payRunStats')) {
    function payRunStats($conn) {
        $stats = [
            'total_employees' => 0,
            'active_runs' => 0,
            'pending_approvals' => 0,
            'current_run_gross' => 0,
            'current_run_net' => 0,
            'current_run_deductions' => 0,
            'month_allowances' => 0,
            'month_deductions' => 0,
            'month_overtime' => 0,
            'month_bonuses' => 0,
        ];
        if (!$conn) return $stats;
        $current = date('Y-m');
        $queries = [
            'total_employees' => "SELECT COUNT(*) c FROM payroll_employees",
            'active_runs' => "SELECT COUNT(*) c FROM payroll_runs WHERE status IN ('draft','approved')",
            'pending_approvals' => "SELECT COUNT(*) c FROM payroll_approvals WHERE status='pending'",
            'current_run_gross' => "SELECT COALESCE(SUM(gross_pay),0) c FROM payroll_details pd JOIN payroll_runs pr ON pd.payroll_run_id = pr.id WHERE pr.period = '$current'",
            'current_run_net' => "SELECT COALESCE(SUM(net_pay),0) c FROM payroll_details pd JOIN payroll_runs pr ON pd.payroll_run_id = pr.id WHERE pr.period = '$current'",
            'current_run_deductions' => "SELECT COALESCE(SUM(paye_tax+nssf_employee+other_deductions+leave_deductions),0) c FROM payroll_details pd JOIN payroll_runs pr ON pd.payroll_run_id = pr.id WHERE pr.period = '$current'",
            'month_allowances' => "SELECT COALESCE(SUM(amount),0) c FROM payroll_allowances WHERE month='$current'",
            'month_deductions' => "SELECT COALESCE(SUM(amount),0) c FROM payroll_deductions WHERE month='$current'",
            'month_overtime' => "SELECT COALESCE(SUM(total_pay),0) c FROM payroll_overtime WHERE month='$current'",
            'month_bonuses' => "SELECT COALESCE(SUM(amount),0) c FROM payroll_bonuses WHERE month='$current'",
        ];
        foreach ($queries as $key => $sql) {
            $r = $conn->query($sql);
            if ($r) $stats[$key] = (int)$r->fetch_assoc()['c'];
        }
        return $stats;
    }
}

if (!function_exists('payStatusBadge')) {
    function payStatusBadge($status) {
        $map = [
            'draft' => 'bg-secondary',
            'approved' => 'bg-success',
            'processed' => 'bg-info',
            'paid' => 'bg-primary',
            'pending' => 'bg-warning text-dark',
            'failed' => 'bg-danger',
            'rejected' => 'bg-danger',
        ];
        $class = $map[strtolower($status ?? '')] ?? 'bg-secondary';
        return '<span class="badge ' . $class . '">' . htmlspecialchars(ucfirst($status ?? 'Unknown')) . '</span>';
    }
}

if (!function_exists('payFormatMoney')) {
    function payFormatMoney($amount) {
        return number_format((float)$amount, 0, '.', ',');
    }
}

if (!function_exists('payLogActivity')) {
    function payLogActivity($conn, $staff_id, $action, $description) {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $stmt = $conn->prepare("INSERT INTO hr_activity_log (staff_id, action, module, description, ip_address) VALUES (?, ?, 'Payroll', ?, ?)");
        if ($stmt) {
            $stmt->bind_param('isss', $staff_id, $action, $description, $ip);
            $stmt->execute();
        }
    }
}
