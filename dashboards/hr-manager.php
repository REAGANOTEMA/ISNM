<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
require_once __DIR__ . '/../includes/hr_functions.php';
require_once __DIR__ . '/../includes/payroll_functions.php';

$ctx          = bootstrapStaffDashboard(['hr manager','hr']);
$auth_service = $ctx['auth'];
$user         = $ctx['user'];
$user_role    = $_SESSION['role'] ?? '';
$staff_conn   = getStaffConnection();
$students_conn = $ctx['students'];
$website_conn  = $ctx['website'];
$user_id   = (int)($_SESSION['user_id'] ?? 0);
$user_name = $_SESSION['full_name'] ?? 'HR Manager';

$stats = hrGetStats($staff_conn);
$departments = hrGetDepartments($staff_conn);
$roles_list = hrGetRoles($staff_conn);
$leave_types = hrGetLeaveTypes($staff_conn);

$staff_list = hrGetStaff($staff_conn);

$active_section = $_GET['section'] ?? 'overview';

$pending_leave = [];
if ($staff_conn) {
    $r = $staff_conn->query("SELECT lr.*, s.full_name, s.staff_id, lt.leave_type_name AS leave_name FROM leave_requests lr JOIN staff s ON lr.staff_id = s.id LEFT JOIN leave_types lt ON lr.leave_type_id = lt.id WHERE lr.status='pending' ORDER BY lr.created_at DESC LIMIT 20");
    if ($r) $pending_leave = $r->fetch_all(MYSQLI_ASSOC);
}

$recent_activities = [];
if ($staff_conn) {
    $r = $staff_conn->query("SELECT action, description, created_at FROM hr_activity_log ORDER BY created_at DESC LIMIT 10");
    if ($r) $recent_activities = $r->fetch_all(MYSQLI_ASSOC);
}

$upcoming_trainings = [];
if ($staff_conn) {
    $r = $staff_conn->query("SELECT st.*, s.full_name FROM staff_trainings st JOIN staff s ON st.staff_id = s.id WHERE st.status='scheduled' ORDER BY st.start_date ASC LIMIT 10");
    if ($r) $upcoming_trainings = $r->fetch_all(MYSQLI_ASSOC);
}

$open_vacancies = [];
if ($staff_conn) {
    $r = $staff_conn->query("SELECT jv.*, d.department_name FROM job_vacancies jv LEFT JOIN departments d ON jv.department_id = d.id WHERE jv.status='open' ORDER BY jv.posted_date DESC");
    if ($r) $open_vacancies = $r->fetch_all(MYSQLI_ASSOC);
}

$open_cases = [];
if ($staff_conn) {
    $r = $staff_conn->query("SELECT dc.*, s.full_name FROM disciplinary_cases dc JOIN staff s ON dc.staff_id = s.id WHERE dc.status IN ('open','investigating') ORDER BY dc.created_at DESC");
    if ($r) $open_cases = $r->fetch_all(MYSQLI_ASSOC);
}

$expired_licenses = [];
if ($staff_conn) {
    $r = $staff_conn->query("SELECT sl.*, s.full_name, s.staff_id FROM staff_licenses sl JOIN staff s ON sl.staff_id = s.id WHERE sl.status IN ('expired','pending_renewal') OR (sl.expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 60 DAY) AND sl.status='valid') ORDER BY sl.expiry_date ASC LIMIT 20");
    if ($r) $expired_licenses = $r->fetch_all(MYSQLI_ASSOC);
}

$attendance_today = [];
if ($staff_conn) {
    $r = $staff_conn->query("SELECT a.*, s.full_name, s.staff_id FROM attendance a JOIN staff s ON a.staff_id = s.id WHERE a.attendance_date = CURDATE() ORDER BY a.check_in_time ASC LIMIT 20");
    if ($r) $attendance_today = $r->fetch_all(MYSQLI_ASSOC);
}

$upcoming_shifts = [];
if ($staff_conn) {
    $r = $staff_conn->query("SELECT ss.*, s.full_name, s.staff_id FROM shift_schedules ss JOIN staff s ON ss.staff_id = s.id WHERE ss.shift_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY) ORDER BY ss.shift_date ASC, ss.start_time ASC LIMIT 20");
    if ($r) $upcoming_shifts = $r->fetch_all(MYSQLI_ASSOC);
}

$performance_reviews = [];
if ($staff_conn) {
    $r = $staff_conn->query("SELECT pr.*, s.full_name, s.staff_id, r.full_name as reviewer_name FROM performance_reviews pr JOIN staff s ON pr.staff_id = s.id LEFT JOIN staff r ON pr.reviewer_id = r.id ORDER BY pr.created_at DESC LIMIT 20");
    if ($r) $performance_reviews = $r->fetch_all(MYSQLI_ASSOC);
}

$rotation_schedules = [];
if ($staff_conn) {
    $r = $staff_conn->query("SELECT cr.*, s.full_name, s.staff_id, sup.full_name as supervisor_name FROM clinical_rotations cr JOIN staff s ON cr.staff_id = s.id LEFT JOIN staff sup ON cr.supervisor_id = sup.id WHERE cr.status IN ('scheduled','active') ORDER BY cr.start_date ASC LIMIT 20");
    if ($r) $rotation_schedules = $r->fetch_all(MYSQLI_ASSOC);
}

$pay_active_tab = $_GET['tab'] ?? 'overview';
$pay_stats = payRunStats($staff_conn);
$pay_employees = payGetEmployees($staff_conn);
$pay_runs = payGetRuns($staff_conn);
$pay_allowances = payGetAllowances($staff_conn, null, date('Y-m'));
$pay_deductions = payGetDeductions($staff_conn, null, date('Y-m'));
$pay_overtime = payGetOvertime($staff_conn, null, date('Y-m'));
$pay_bonuses = payGetBonuses($staff_conn, null, date('Y-m'));

if ($_SERVER['REQUEST_METHOD']==='POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add_staff') {
        $fn   = trim($_POST['full_name'] ?? '');
        $em   = trim($_POST['email'] ?? '');
        $ph   = trim($_POST['phone'] ?? '');
        $gen  = trim($_POST['gender'] ?? '');
        $dept = (int)($_POST['department_id'] ?? 0);
        $rid  = (int)($_POST['role_id'] ?? 0);
        $pos  = trim($_POST['position'] ?? '');
        $etype = trim($_POST['employment_type'] ?? 'permanent');
        $hd   = trim($_POST['hire_date'] ?? date('Y-m-d'));
        if ($fn && $em && $staff_conn) {
            $sid = 'STAFF'.date('Y').str_pad(mt_rand(1,9999),4,'0',STR_PAD_LEFT);
            $hash = password_hash('isnm2026', PASSWORD_BCRYPT);
            $stmt = $staff_conn->prepare("INSERT INTO staff (staff_id,full_name,email,password,phone,gender,department_id,role_id,position,employment_type,hire_date,status,login_attempts) VALUES (?,?,?,?,?,?,?,?,?,?,?,'active',0)");
            if ($stmt) {
                $stmt->bind_param('ssssssiisss',$sid,$fn,$em,$hash,$ph,$gen,$dept,$rid,$pos,$etype,$hd);
                $stmt->execute();
                hrLogActivity($staff_conn, $user_id, 'add_staff', 'Staff Management', "Added staff: $fn ($sid)");
                $_SESSION['success'] = "Staff member $fn added successfully. Default password: isnm2026";
            }
        }
        header('Location: hr-manager.php?section=staff-records'); exit;
    }
    
    if ($action === 'approve_leave' || $action === 'reject_leave') {
        $lid = (int)($_POST['leave_id'] ?? 0);
        $new_status = ($action === 'approve_leave') ? 'approved' : 'rejected';
        if ($staff_conn && $lid) {
            $stmt = $staff_conn->prepare("UPDATE leave_requests SET status=?, approved_by=?, approval_date=NOW() WHERE id=?");
            if ($stmt) { $stmt->bind_param('sii',$new_status,$user_id,$lid); $stmt->execute(); }
            hrLogActivity($staff_conn, $user_id, $action, 'Leave Management', ucfirst($action)." leave request #$lid");
            $_SESSION['success'] = "Leave request $new_status.";
        }
        header('Location: hr-manager.php?section=leave'); exit;
    }

    if ($action === 'add_department') {
        $dn = trim($_POST['department_name'] ?? '');
        $desc = trim($_POST['description'] ?? '');
        if ($dn && $staff_conn) {
            $stmt = $staff_conn->prepare("INSERT INTO departments (department_name, description) VALUES (?, ?)");
            if ($stmt) { $stmt->bind_param('ss', $dn, $desc); $stmt->execute(); }
            header('Location: hr-manager.php?section=settings'); exit;
        }
    }

    if ($action === 'add_role') {
        $rn = trim($_POST['role_name'] ?? '');
        $desc = trim($_POST['description'] ?? '');
        if ($rn && $staff_conn) {
            $stmt = $staff_conn->prepare("INSERT INTO roles (role_name, description) VALUES (?, ?)");
            if ($stmt) { $stmt->bind_param('ss', $rn, $desc); $stmt->execute(); }
            header('Location: hr-manager.php?section=settings&tab=roles'); exit;
        }
    }

    if ($action === 'add_leave_type') {
        $ln = trim($_POST['leave_name'] ?? '');
        $dd = (int)($_POST['days_per_year'] ?? 0);
        if ($ln && $staff_conn) {
            $stmt = $staff_conn->prepare("INSERT INTO leave_types (leave_type_name, days_per_year, status) VALUES (?, ?, 'active')");
            if ($stmt) { $stmt->bind_param('si', $ln, $dd); $stmt->execute(); }
            header('Location: hr-manager.php?section=settings&tab=leave-types'); exit;
        }
    }

    if ($action === 'mark_attendance') {
        $sid = (int)($_POST['staff_id'] ?? 0);
        $status = $_POST['attendance_status'] ?? 'present';
        $time_in = $_POST['time_in'] ?? date('H:i:s');
        if ($sid && $staff_conn) {
            $stmt = $staff_conn->prepare("INSERT INTO attendance (staff_id, attendance_date, check_in_time, attendance_status, recorded_by) VALUES (?, CURDATE(), ?, ?, ?) ON DUPLICATE KEY UPDATE check_in_time=VALUES(check_in_time), attendance_status=VALUES(attendance_status), recorded_by=VALUES(recorded_by)");
            if ($stmt) { $stmt->bind_param('issi', $sid, $time_in, $status, $user_id); $stmt->execute(); }
            header('Location: hr-manager.php?section=attendance'); exit;
        }
    }

    if ($action === 'add_training') {
        $sid = (int)($_POST['staff_id'] ?? 0);
        $tn = trim($_POST['training_name'] ?? '');
        $sd = $_POST['start_date'] ?? '';
        $ed = $_POST['end_date'] ?? '';
        $hrs = (int)($_POST['hours'] ?? 0);
        if ($sid && $tn && $staff_conn) {
            $stmt = $staff_conn->prepare("INSERT INTO staff_trainings (staff_id, training_name, start_date, end_date, hours, status) VALUES (?, ?, ?, ?, ?, 'scheduled')");
            if ($stmt) { $stmt->bind_param('isssi', $sid, $tn, $sd, $ed, $hrs); $stmt->execute(); }
            header('Location: hr-manager.php?section=training'); exit;
        }
    }

    if ($action === 'add_vacancy') {
        $jt = trim($_POST['job_title'] ?? '');
        $did = (int)($_POST['department_id'] ?? 0);
        $pa = (int)($_POST['positions_available'] ?? 1);
        $cd = $_POST['closing_date'] ?? '';
        if ($jt && $staff_conn) {
            $vc = 'VAC-' . strtoupper(substr(md5(uniqid()), 0, 8));
            $dn = '';
            $rd = $staff_conn->query("SELECT department_name FROM departments WHERE id=$did");
            if ($rd) { $dn = $rd->fetch_assoc()['department_name'] ?? ''; }
            $stmt = $staff_conn->prepare("INSERT INTO job_vacancies (vacancy_code, job_title, department_id, number_of_positions, closing_date, posted_by, posted_date, status, department, posting_date) VALUES (?, ?, ?, ?, ?, ?, CURDATE(), 'open', ?, CURDATE())");
            if ($stmt) { $stmt->bind_param('ssiiiss', $vc, $jt, $did, $pa, $cd, $user_id, $dn); $stmt->execute(); }
            header('Location: hr-manager.php?section=recruitment'); exit;
        }
    }

    if ($action === 'edit_staff') {
        $sid = (int)($_POST['staff_id'] ?? 0);
        $fn   = trim($_POST['full_name'] ?? '');
        $em   = trim($_POST['email'] ?? '');
        $ph   = trim($_POST['phone'] ?? '');
        $gen  = trim($_POST['gender'] ?? '');
        $dept = (int)($_POST['department_id'] ?? 0);
        $rid  = (int)($_POST['role_id'] ?? 0);
        $pos  = trim($_POST['position'] ?? '');
        $etype = trim($_POST['employment_type'] ?? 'permanent');
        $hd   = trim($_POST['hire_date'] ?? '');
        $status = trim($_POST['status'] ?? 'active');
        if ($sid && $fn && $em && $staff_conn) {
            $stmt = $staff_conn->prepare("UPDATE staff SET full_name=?, email=?, phone=?, gender=?, department_id=?, role_id=?, position=?, employment_type=?, hire_date=?, status=? WHERE id=?");
            if ($stmt) {
                $stmt->bind_param('ssssiissssi', $fn, $em, $ph, $gen, $dept, $rid, $pos, $etype, $hd, $status, $sid);
                $stmt->execute();
                hrLogActivity($staff_conn, $user_id, 'edit_staff', 'Staff Management', "Updated staff: $fn (ID: $sid)");
                $_SESSION['success'] = "Staff member $fn updated successfully.";
            }
        }
        header('Location: hr-manager.php?section=staff-records'); exit;
    }

    // ─── PAYROLL HANDLERS ───────────────────────────────
    if ($action === 'pay_add_employee') {
        $sid = (int)($_POST['staff_id'] ?? 0);
        $bs = (float)($_POST['basic_salary'] ?? 0);
        $st = $_POST['salary_type'] ?? 'monthly';
        $bn = trim($_POST['bank_name'] ?? '');
        $ba = trim($_POST['bank_account'] ?? '');
        $tin = trim($_POST['tin_number'] ?? '');
        $nssf = trim($_POST['nssf_number'] ?? '');
        $sg = trim($_POST['salary_grade'] ?? '');
        $ef = $_POST['effective_from'] ?? date('Y-m-d');
        if ($sid && $bs > 0 && $staff_conn) {
            $stmt = $staff_conn->prepare("INSERT INTO payroll_employees (staff_id, basic_salary, salary_type, bank_name, bank_account, tin_number, nssf_number, salary_grade, effective_from) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE basic_salary=VALUES(basic_salary), salary_type=VALUES(salary_type), bank_name=VALUES(bank_name), bank_account=VALUES(bank_account), tin_number=VALUES(tin_number), nssf_number=VALUES(nssf_number), salary_grade=VALUES(salary_grade), effective_from=VALUES(effective_from)");
            if ($stmt) { $stmt->bind_param('idsssssss', $sid, $bs, $st, $bn, $ba, $tin, $nssf, $sg, $ef); $stmt->execute(); }
            payLogActivity($staff_conn, $user_id, 'add_employee', "Payroll profile for staff ID $sid");
        }
        header('Location: hr-manager.php?section=payroll&tab=employees'); exit;
    }

    if ($action === 'pay_run_create') {
        $period = $_POST['period'] ?? date('Y-m');
        $sd = $_POST['start_date'] ?? date('Y-m-01');
        $ed = $_POST['end_date'] ?? date('Y-m-t');
        if ($staff_conn) {
            $stmt = $staff_conn->prepare("INSERT INTO payroll_runs (period, start_date, end_date, status, created_by) VALUES (?, ?, ?, 'draft', ?)");
            if ($stmt) { $stmt->bind_param('sssi', $period, $sd, $ed, $user_id); $stmt->execute(); $run_id = $stmt->insert_id; }
            if (!empty($run_id)) {
                $emps = $staff_conn->query("SELECT pe.staff_id, pe.basic_salary FROM payroll_employees pe JOIN staff s ON pe.staff_id = s.id WHERE s.status='active'");
                while ($emp = $emps->fetch_assoc()) {
                    $sid = $emp['staff_id'];
                    $base = (float)$emp['basic_salary'];
                    $month = $period;
                    $alw = $staff_conn->query("SELECT COALESCE(SUM(amount),0) c FROM payroll_allowances WHERE staff_id=$sid AND month='$month'")->fetch_assoc()['c'];
                    $ot = $staff_conn->query("SELECT COALESCE(SUM(total_pay),0) c FROM payroll_overtime WHERE staff_id=$sid AND month='$month'")->fetch_assoc()['c'];
                    $bn = $staff_conn->query("SELECT COALESCE(SUM(amount),0) c FROM payroll_bonuses WHERE staff_id=$sid AND month='$month'")->fetch_assoc()['c'];
                    $dd = $staff_conn->query("SELECT COALESCE(SUM(amount),0) c FROM payroll_deductions WHERE staff_id=$sid AND month='$month'")->fetch_assoc()['c'];
                    $gross = payCalculateGross($base, (float)$alw, (float)$ot, (float)$bn);
                    $paye = payCalculatePAYE($gross);
                    $nssf_calc = payCalculateNSSF($base);
                    $net = payCalculateNet($gross, $paye, $nssf_calc['employee'], 0, (float)$dd);
                    $ins = $staff_conn->prepare("INSERT INTO payroll_details (payroll_run_id, staff_id, basic_salary, total_allowances, overtime_pay, bonuses, gross_pay, paye_tax, nssf_employee, nssf_employer, other_deductions, net_pay) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    if ($ins) { $ins->bind_param('iidddddddddd', $run_id, $sid, $base, $alw, $ot, $bn, $gross, $paye, $nssf_calc['employee'], $nssf_calc['employer'], $dd, $net); $ins->execute(); }
                }
                $staff_conn->query("UPDATE payroll_runs SET total_gross=(SELECT COALESCE(SUM(gross_pay),0) FROM payroll_details WHERE payroll_run_id=$run_id), total_deductions=(SELECT COALESCE(SUM(paye_tax+nssf_employee+other_deductions),0) FROM payroll_details WHERE payroll_run_id=$run_id), total_net=(SELECT COALESCE(SUM(net_pay),0) FROM payroll_details WHERE payroll_run_id=$run_id) WHERE id=$run_id");
                payLogActivity($staff_conn, $user_id, 'run_create', "Payroll run $period created");
            }
        }
        header('Location: hr-manager.php?section=payroll&tab=run'); exit;
    }

    if ($action === 'pay_add_allowance') {
        $sid = (int)($_POST['staff_id'] ?? 0);
        $at = trim($_POST['allowance_type'] ?? '');
        $am = (float)($_POST['amount'] ?? 0);
        $mo = $_POST['month'] ?? date('Y-m');
        if ($sid && $at && $am > 0 && $staff_conn) {
            $stmt = $staff_conn->prepare("INSERT INTO payroll_allowances (staff_id, allowance_type, amount, month, created_by) VALUES (?, ?, ?, ?, ?)");
            if ($stmt) { $stmt->bind_param('isdsi', $sid, $at, $am, $mo, $user_id); $stmt->execute(); }
        }
        header('Location: hr-manager.php?section=payroll&tab=allowances'); exit;
    }

    if ($action === 'pay_add_deduction') {
        $sid = (int)($_POST['staff_id'] ?? 0);
        $dt = trim($_POST['deduction_type'] ?? '');
        $am = (float)($_POST['amount'] ?? 0);
        $mo = $_POST['month'] ?? date('Y-m');
        if ($sid && $dt && $am > 0 && $staff_conn) {
            $stmt = $staff_conn->prepare("INSERT INTO payroll_deductions (staff_id, deduction_type, amount, month, created_by) VALUES (?, ?, ?, ?, ?)");
            if ($stmt) { $stmt->bind_param('isdsi', $sid, $dt, $am, $mo, $user_id); $stmt->execute(); }
        }
        header('Location: hr-manager.php?section=payroll&tab=deductions'); exit;
    }

    if ($action === 'pay_add_overtime') {
        $sid = (int)($_POST['staff_id'] ?? 0);
        $hrs = (float)($_POST['hours'] ?? 0);
        $rt = (float)($_POST['rate'] ?? 0);
        $mo = $_POST['month'] ?? date('Y-m');
        $total = $hrs * $rt;
        if ($sid && $hrs > 0 && $staff_conn) {
            $stmt = $staff_conn->prepare("INSERT INTO payroll_overtime (staff_id, hours, rate, total_pay, month, approved_by) VALUES (?, ?, ?, ?, ?, ?)");
            if ($stmt) { $stmt->bind_param('idddsi', $sid, $hrs, $rt, $total, $mo, $user_id); $stmt->execute(); }
        }
        header('Location: hr-manager.php?section=payroll&tab=overtime'); exit;
    }

    if ($action === 'pay_add_bonus') {
        $sid = (int)($_POST['staff_id'] ?? 0);
        $bt = trim($_POST['bonus_type'] ?? '');
        $am = (float)($_POST['amount'] ?? 0);
        $mo = $_POST['month'] ?? date('Y-m');
        if ($sid && $bt && $am > 0 && $staff_conn) {
            $stmt = $staff_conn->prepare("INSERT INTO payroll_bonuses (staff_id, bonus_type, amount, month, created_by) VALUES (?, ?, ?, ?, ?)");
            if ($stmt) { $stmt->bind_param('isdsi', $sid, $bt, $am, $mo, $user_id); $stmt->execute(); }
        }
        header('Location: hr-manager.php?section=payroll&tab=bonuses'); exit;
    }

    if ($action === 'pay_run_approve') {
        $rid = (int)($_POST['run_id'] ?? 0);
        $level = trim($_POST['approval_level'] ?? 'HR');
        if ($rid && $staff_conn) {
            $stmt = $staff_conn->prepare("INSERT INTO payroll_approvals (payroll_run_id, level, status, approved_by) VALUES (?, ?, 'approved', ?) ON DUPLICATE KEY UPDATE status='approved', approved_by=VALUES(approved_by), updated_at=NOW()");
            if ($stmt) { $stmt->bind_param('isi', $rid, $level, $user_id); $stmt->execute(); }
            $all_approved = $staff_conn->query("SELECT COUNT(*) c FROM payroll_approvals WHERE payroll_run_id=$rid AND status='approved'")->fetch_assoc()['c'];
            if ($all_approved >= 4) {
                $staff_conn->query("UPDATE payroll_runs SET status='approved', approved_by=$user_id, approved_at=NOW() WHERE id=$rid");
            }
            payLogActivity($staff_conn, $user_id, 'approve_run', "Payroll run #$rid approved at $level level");
        }
        header('Location: hr-manager.php?section=payroll&tab=approvals'); exit;
    }

    if ($action === 'pay_generate_payslips') {
        $rid = (int)($_POST['run_id'] ?? 0);
        if ($rid && $staff_conn) {
            $details = $staff_conn->query("SELECT pd.*, pr.period FROM payroll_details pd JOIN payroll_runs pr ON pd.payroll_run_id = pr.id WHERE pd.payroll_run_id = $rid AND pd.payment_status='pending'");
            while ($d = $details->fetch_assoc()) {
                $sid = $d['staff_id'];
                $ref = 'PAY-' . $d['period'] . '-' . str_pad($sid, 4, '0', STR_PAD_LEFT);
                $ins = $staff_conn->prepare("INSERT INTO payslips (staff_id, payslip_number, salary_month, basic_salary, allowances, gross_salary, deductions, net_salary, payroll_run_id, payroll_detail_id, payment_ref, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'generated') ON DUPLICATE KEY UPDATE payment_ref=VALUES(payment_ref)");
                if ($ins) { $ins->bind_param('issdddddiis', $sid, $ref, $d['period'], $d['basic_salary'], $d['total_allowances'], $d['gross_pay'], ($d['paye_tax']+$d['nssf_employee']+$d['other_deductions']), $d['net_pay'], $rid, $d['id'], $ref); $ins->execute(); }
                $upd = $staff_conn->prepare("UPDATE payroll_details SET payment_reference=? WHERE id=?");
                if ($upd) { $upd->bind_param('si', $ref, $d['id']); $upd->execute(); }
            }
            $staff_conn->query("UPDATE payroll_runs SET status='processed' WHERE id=$rid");
            payLogActivity($staff_conn, $user_id, 'generate_payslips', "Payslips generated for run #$rid");
        }
        header('Location: hr-manager.php?section=payroll&tab=payslips'); exit;
    }

    if ($action === 'pay_mark_paid') {
        $rid = (int)($_POST['run_id'] ?? 0);
        $method = trim($_POST['payment_method'] ?? 'bank_transfer');
        if ($rid && $staff_conn) {
            $staff_conn->query("UPDATE payroll_details SET payment_status='paid', payment_method='$method', payment_date=CURDATE() WHERE payroll_run_id=$rid");
            $staff_conn->query("UPDATE payroll_runs SET status='paid' WHERE id=$rid");
            payLogActivity($staff_conn, $user_id, 'mark_paid', "Payroll run #$rid marked as paid via $method");
        }
        header('Location: hr-manager.php?section=payroll&tab=run'); exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include_once __DIR__ . '/../includes/dashboard_head.php'; ?>
<style>
:root {
    --hr-primary: #1e3a5f;
    --hr-accent: #e63946;
    --hr-success: #2d6a4f;
    --hr-warning: #e09f3e;
    --hr-info: #457b9d;
    --hr-bg: #f0f2f5;
}
.hr-dashboard { padding: 0; }
.hr-topbar {
    background: linear-gradient(135deg, var(--hr-primary) 0%, #2a5080 100%);
    color: white; padding: 20px 24px; border-radius: 0 0 16px 16px; margin-bottom: 24px;
}
.hr-topbar h1 { font-size: 1.5rem; font-weight: 700; margin: 0; }
.hr-topbar p { margin: 4px 0 0; opacity: 0.85; font-size: 0.85rem; }
.stat-card {
    background: white; border-radius: 12px; padding: 16px; box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    transition: transform 0.2s, box-shadow 0.2s; border-left: 4px solid var(--hr-primary);
}
.stat-card:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(0,0,0,0.1); }
.stat-card .stat-icon { font-size: 1.8rem; opacity: 0.2; position: absolute; right: 16px; top: 16px; }
.stat-card h3 { font-size: 1.6rem; font-weight: 700; margin: 0; color: #1a1a2e; }
.stat-card p { margin: 4px 0 0; font-size: 0.8rem; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px; }
.section-nav {
    display: flex; gap: 4px; padding: 4px; background: white; border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06); margin-bottom: 24px; overflow-x: auto;
    flex-wrap: nowrap; scrollbar-width: none;
}
.section-nav::-webkit-scrollbar { display: none; }
.section-tab {
    padding: 10px 16px; border-radius: 8px; font-size: 0.8rem; font-weight: 500;
    color: #6b7280; text-decoration: none; white-space: nowrap; transition: all 0.2s;
    border: none; background: transparent; cursor: pointer; display: inline-flex; align-items: center; gap: 6px;
}
.section-tab:hover { background: #f3f4f6; color: var(--hr-primary); }
.section-tab.active { background: var(--hr-primary); color: white; box-shadow: 0 2px 8px rgba(30,58,95,0.2); }
.hr-section { display: none; }
.hr-section.active { display: block; }
.content-card {
    background: white; border-radius: 12px; padding: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); margin-bottom: 20px;
}
.content-card h2 { font-size: 1.1rem; font-weight: 600; margin: 0 0 16px; display: flex; align-items: center; gap: 8px; }
.content-card h2 .badge-count { margin-left: auto; }
.table-hr { font-size: 0.85rem; }
.table-hr th { background: #f8fafc; font-weight: 600; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px; color: #6b7280; border-top: none; }
.table-hr td { vertical-align: middle; }
.status-dot { width: 8px; height: 8px; border-radius: 50%; display: inline-block; margin-right: 6px; }
.status-dot.active { background: #10b981; }
.status-dot.inactive { background: #9ca3af; }
.status-dot.suspended { background: #f59e0b; }
.status-dot.retired { background: #374151; }
.alert-card { border-left: 4px solid var(--hr-accent); background: #fef2f2; border-radius: 8px; padding: 12px 16px; margin-bottom: 8px; }
.alert-card.warning { border-left-color: var(--hr-warning); background: #fffbeb; }
.alert-card.info { border-left-color: var(--hr-info); background: #eff6ff; }
.quick-action-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: 8px; }
.quick-action-btn {
    display: flex; flex-direction: column; align-items: center; gap: 6px; padding: 14px 10px;
    background: #f8fafc; border: 1px solid #e5e7eb; border-radius: 10px; text-decoration: none;
    color: #374151; font-size: 0.8rem; font-weight: 500; transition: all 0.2s; text-align: center;
}
.quick-action-btn:hover { background: var(--hr-primary); color: white; border-color: var(--hr-primary); transform: translateY(-2px); }
.quick-action-btn i { font-size: 1.3rem; }
.license-expiry { font-size: 0.75rem; font-weight: 600; }
.license-expiry.urgent { color: #dc2626; }
.license-expiry.soon { color: #f59e0b; }
.license-expiry.safe { color: #10b981; }
.modal-hr .modal-header { background: var(--hr-primary); color: white; }
.form-label { font-size: 0.8rem; font-weight: 600; color: #374151; margin-bottom: 4px; }
@media (max-width: 768px) {
    .section-nav { flex-wrap: nowrap; }
    .quick-action-grid { grid-template-columns: repeat(2, 1fr); }
}
</style>
</head>
<body>
<?php include_once __DIR__ . '/../includes/sidebar.php'; ?>
<div class="page-wrap">
<div class="hr-dashboard">

<div class="hr-topbar">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1><i class="fas fa-users-cog me-2"></i>HR Manager Dashboard</h1>
            <p>Human Resources Management — Iganga School of Nursing & Midwifery | <?= date('l, d F Y') ?></p>
        </div>
        <div class="d-flex gap-2">
            <span class="badge bg-light text-dark px-3 py-2"><?= $stats['active_staff'] ?> Active Staff</span>
        </div>
    </div>
</div>

<?php if (!empty($_SESSION['success'])): ?>
<div class="alert alert-success alert-dismissible fade show mx-3"><?= htmlspecialchars($_SESSION['success']) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php unset($_SESSION['success']); endif; ?>
<?php if (!empty($_SESSION['error'])): ?>
<div class="alert alert-danger alert-dismissible fade show mx-3"><?= htmlspecialchars($_SESSION['error']) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php unset($_SESSION['error']); endif; ?>

<div class="px-3">
<div class="section-nav" id="sectionNav">
    <a href="?section=overview" class="section-tab <?= $active_section==='overview'?'active':'' ?>"><i class="fas fa-home"></i>Overview</a>
    <a href="?section=staff-records" class="section-tab <?= $active_section==='staff-records'?'active':'' ?>"><i class="fas fa-id-card"></i>Staff Records</a>
    <a href="?section=attendance" class="section-tab <?= $active_section==='attendance'?'active':'' ?>"><i class="fas fa-calendar-check"></i>Attendance</a>
    <a href="?section=leave" class="section-tab <?= $active_section==='leave'?'active':'' ?>"><i class="fas fa-calendar-alt"></i>Leave</a>
    <a href="?section=performance" class="section-tab <?= $active_section==='performance'?'active':'' ?>"><i class="fas fa-chart-line"></i>Performance</a>
    <a href="?section=training" class="section-tab <?= $active_section==='training'?'active':'' ?>"><i class="fas fa-graduation-cap"></i>Training</a>
    <a href="?section=recruitment" class="section-tab <?= $active_section==='recruitment'?'active':'' ?>"><i class="fas fa-user-plus"></i>Recruitment</a>
    <a href="?section=payroll" class="section-tab <?= $active_section==='payroll'?'active':'' ?>"><i class="fas fa-money-check"></i>Payroll</a>
    <a href="?section=disciplinary" class="section-tab <?= $active_section==='disciplinary'?'active':'' ?>"><i class="fas fa-gavel"></i>Disciplinary</a>
    <a href="?section=licenses" class="section-tab <?= $active_section==='licenses'?'active':'' ?>"><i class="fas fa-certificate"></i>Compliance</a>
    <a href="?section=rotation" class="section-tab <?= $active_section==='rotation'?'active':'' ?>"><i class="fas fa-exchange-alt"></i>Deployment</a>
    <a href="?section=communication" class="section-tab <?= $active_section==='communication'?'active':'' ?>"><i class="fas fa-bullhorn"></i>Comms</a>
    <a href="?section=reports" class="section-tab <?= $active_section==='reports'?'active':'' ?>"><i class="fas fa-chart-bar"></i>Reports</a>
    <a href="?section=settings" class="section-tab <?= $active_section==='settings'?'active':'' ?>"><i class="fas fa-cog"></i>Settings</a>
</div>

<!-- ═══════════════════════════════════════════════════ -->
<!-- SECTION: OVERVIEW                                  -->
<!-- ═══════════════════════════════════════════════════ -->
<div class="hr-section <?= $active_section==='overview'?'active':'' ?>" id="section-overview">
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="stat-card position-relative" style="border-left-color:#1e3a5f;">
                <div class="stat-icon"><i class="fas fa-users"></i></div>
                <h3><?= $stats['total_staff'] ?></h3>
                <p>Total Staff</p>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card position-relative" style="border-left-color:#10b981;">
                <div class="stat-icon"><i class="fas fa-user-check"></i></div>
                <h3><?= $stats['active_staff'] ?></h3>
                <p>Active Staff</p>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card position-relative" style="border-left-color:#f59e0b;">
                <div class="stat-icon"><i class="fas fa-clock"></i></div>
                <h3><?= $stats['attendance_today'] ?></h3>
                <p>Present Today</p>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card position-relative" style="border-left-color:#ef4444;">
                <div class="stat-icon"><i class="fas fa-exclamation-triangle"></i></div>
                <h3><?= $stats['expired_licenses'] ?></h3>
                <p>Expired Licenses</p>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card position-relative" style="border-left-color:#8b5cf6;">
                <div class="stat-icon"><i class="fas fa-calendar-alt"></i></div>
                <h3><?= $stats['pending_leave'] ?></h3>
                <p>Pending Leave</p>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card position-relative" style="border-left-color:#ec4899;">
                <div class="stat-icon"><i class="fas fa-gavel"></i></div>
                <h3><?= $stats['open_cases'] ?></h3>
                <p>Open Cases</p>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card position-relative" style="border-left-color:#14b8a6;">
                <div class="stat-icon"><i class="fas fa-briefcase"></i></div>
                <h3><?= $stats['open_vacancies'] ?></h3>
                <p>Open Vacancies</p>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card position-relative" style="border-left-color:#f97316;">
                <div class="stat-icon"><i class="fas fa-graduation-cap"></i></div>
                <h3><?= $stats['ongoing_trainings'] ?></h3>
                <p>Upcoming Trainings</p>
            </div>
        </div>
    </div>
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="stat-card position-relative" style="border-left-color:#1e3a5f;">
                <div class="stat-icon"><i class="fas fa-wallet"></i></div>
                <h3><?= $pay_stats['total_employees'] ?></h3>
                <p>Payroll Employees</p>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card position-relative" style="border-left-color:#2d6a4f;">
                <div class="stat-icon"><i class="fas fa-calculator"></i></div>
                <h3>UGX <?= payFormatMoney($pay_stats['current_run_gross']) ?></h3>
                <p>Current Gross Pay</p>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card position-relative" style="border-left-color:#e09f3e;">
                <div class="stat-icon"><i class="fas fa-hourglass-half"></i></div>
                <h3><?= $pay_stats['pending_approvals'] ?></h3>
                <p>Pending Approvals</p>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card position-relative" style="border-left-color:#457b9d;">
                <div class="stat-icon"><i class="fas fa-play-circle"></i></div>
                <h3><?= $pay_stats['active_runs'] ?></h3>
                <p>Active Runs</p>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-8">
            <div class="content-card">
                <h2><i class="fas fa-bolt text-warning"></i>Quick Actions</h2>
                <div class="quick-action-grid">
                    <a href="?section=staff-records" class="quick-action-btn"><i class="fas fa-user-plus"></i>Add Staff</a>
                    <a href="?section=attendance" class="quick-action-btn"><i class="fas fa-clock"></i>Mark Attendance</a>
                    <a href="?section=leave" class="quick-action-btn"><i class="fas fa-calendar-check"></i>Leave Requests</a>
                    <a href="?section=training" class="quick-action-btn"><i class="fas fa-chalkboard-teacher"></i>New Training</a>
                    <a href="?section=recruitment" class="quick-action-btn"><i class="fas fa-briefcase"></i>Post Vacancy</a>
                    <a href="?section=licenses" class="quick-action-btn"><i class="fas fa-certificate"></i>Check Licenses</a>
                    <a href="?section=disciplinary" class="quick-action-btn"><i class="fas fa-gavel"></i>Disciplinary</a>
                    <a href="?section=settings" class="quick-action-btn"><i class="fas fa-cog"></i>Settings</a>
                </div>
            </div>

            <div class="content-card">
                <h2><i class="fas fa-calendar-check text-success"></i>Today's Attendance <?= $stats['attendance_today'] ? '<span class="badge bg-success ms-2">'.$stats['attendance_today'].' present</span>' : '' ?></h2>
                <?php if (empty($attendance_today)): ?>
                <p class="text-muted small">No attendance records for today yet.</p>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-sm table-hover table-hr">
                        <thead><tr><th>Staff</th><th>ID</th><th>Time In</th><th>Status</th></tr></thead>
                        <tbody>
                        <?php foreach ($attendance_today as $a): ?>
                        <tr>
                            <td><?= htmlspecialchars($a['full_name']) ?></td>
                            <td><code><?= htmlspecialchars($a['staff_id']) ?></code></td>
                            <td><?= htmlspecialchars($a['check_in_time'] ?? '-') ?></td>
                            <td><?= hrStatusBadge($a['attendance_status']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="col-md-4">
            <?php if (!empty($expired_licenses)): ?>
            <div class="content-card">
                <h2><i class="fas fa-exclamation-triangle text-danger"></i>License Alerts <span class="badge bg-danger ms-2"><?= count($expired_licenses) ?></span></h2>
                <?php foreach (array_slice($expired_licenses, 0, 5) as $l): ?>
                <div class="alert-card <?= $l['status']==='expired'?'':'warning' ?> d-flex justify-content-between align-items-center">
                    <div>
                        <strong><?= htmlspecialchars($l['full_name']) ?></strong><br>
                        <small><?= htmlspecialchars($l['license_type']) ?> — Expires <?= hrFormatDate($l['expiry_date']) ?></small>
                    </div>
                    <?= hrStatusBadge($l['status']) ?>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <div class="content-card">
                <h2><i class="fas fa-clock text-warning"></i>Pending Leave <span class="badge bg-warning text-dark ms-2"><?= count($pending_leave) ?></span></h2>
                <?php if (empty($pending_leave)): ?>
                <p class="text-muted small">No pending leave requests.</p>
                <?php else: foreach (array_slice($pending_leave, 0, 5) as $l): ?>
                <div class="d-flex justify-content-between align-items-center border-bottom py-2">
                    <div><strong><?= htmlspecialchars($l['full_name']) ?></strong><br><small><?= htmlspecialchars($l['leave_name'] ?? 'Leave') ?> — <?= $l['start_date'] ?> to <?= $l['end_date'] ?></small></div>
                    <span class="badge bg-warning text-dark">Pending</span>
                </div>
                <?php endforeach; endif; ?>
            </div>

            <div class="content-card">
                <h2><i class="fas fa-history text-info"></i>Recent Activity</h2>
                <?php if (empty($recent_activities)): ?>
                <p class="text-muted small">No recent activity.</p>
                <?php else: foreach (array_slice($recent_activities, 0, 6) as $a): ?>
                <div class="d-flex justify-content-between py-1 border-bottom small">
                    <span class="text-truncate"><?= htmlspecialchars($a['description'] ?? $a['action']) ?></span>
                    <span class="text-muted ms-2 flex-shrink-0"><?= hrTimeAgo($a['created_at']) ?></span>
                </div>
                <?php endforeach; endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════ -->
<!-- SECTION: STAFF RECORDS                             -->
<!-- ═══════════════════════════════════════════════════ -->
<div class="hr-section <?= $active_section==='staff-records'?'active':'' ?>" id="section-staff-records">
    <div class="content-card">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="mb-0"><i class="fas fa-id-card text-primary"></i>Staff Records <span class="badge bg-secondary ms-2"><?= count($staff_list) ?></span></h2>
            <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addStaffModal"><i class="fas fa-plus me-1"></i>Add Staff</button>
        </div>
        <div class="table-responsive">
            <table class="table table-sm table-hover table-hr" id="staffTable">
                <thead><tr><th>Staff ID</th><th>Full Name</th><th>Email</th><th>Phone</th><th>Department</th><th>Role</th><th>Type</th><th>Status</th><th>Hire Date</th><th>Actions</th></tr></thead>
                <tbody>
                <?php if (empty($staff_list)): ?>
                <tr><td colspan="10" class="text-center text-muted py-4">No staff records found. <a href="#" data-bs-toggle="modal" data-bs-target="#addStaffModal">Add your first staff member</a></td></tr>
                <?php else: foreach ($staff_list as $s): ?>
                <tr>
                    <td><code><?= htmlspecialchars($s['staff_id'] ?? 'N/A') ?></code></td>
                    <td><strong><?= htmlspecialchars($s['full_name']) ?></strong></td>
                    <td><small><?= htmlspecialchars($s['email'] ?? '-') ?></small></td>
                    <td><small><?= htmlspecialchars($s['phone'] ?? '-') ?></small></td>
                    <td><?= htmlspecialchars($s['department_name'] ?? $s['department'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($s['role_name'] ?? $s['position'] ?? '-') ?></td>
                    <td><small><?= htmlspecialchars(ucfirst($s['employment_type'] ?? $s['position'] ?? '-')) ?></small></td>
                    <td><?= hrStatusBadge($s['status']) ?></td>
                    <td><small><?= hrFormatDate($s['hire_date']) ?></small></td>
                    <td>
                        <button class="btn btn-xs btn-outline-primary" title="View" data-staff-id="<?= $s['id'] ?>" onclick="viewStaff(<?= $s['id'] ?>)"><i class="fas fa-eye"></i></button>
                        <button class="btn btn-xs btn-outline-secondary" title="Edit" data-staff-id="<?= $s['id'] ?>" onclick="editStaff(<?= $s['id'] ?>)"><i class="fas fa-edit"></i></button>
                    </td>
                </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════ -->
<!-- SECTION: ATTENDANCE                                -->
<!-- ═══════════════════════════════════════════════════ -->
<div class="hr-section <?= $active_section==='attendance'?'active':'' ?>" id="section-attendance">
    <div class="row g-3">
        <div class="col-md-4">
            <div class="content-card">
                <h2><i class="fas fa-clock text-success"></i>Quick Mark Attendance</h2>
                <form method="POST">
                    <input type="hidden" name="action" value="mark_attendance">
                    <div class="mb-2">
                        <label class="form-label">Staff Member</label>
                        <select name="staff_id" class="form-select form-select-sm" required>
                            <option value="">Select staff...</option>
                            <?php foreach ($staff_list as $s): ?>
                            <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['full_name']) ?> (<?= htmlspecialchars($s['staff_id']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="row g-2 mb-2">
                        <div class="col">
                            <label class="form-label">Time In</label>
                            <input type="time" name="time_in" class="form-control form-control-sm" value="<?= date('H:i') ?>">
                        </div>
                        <div class="col">
                            <label class="form-label">Status</label>
                            <select name="attendance_status" class="form-select form-select-sm">
                                <?php foreach (hrGetAttendanceStatuses() as $st): ?>
                                <option value="<?= $st ?>"><?= ucfirst($st) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-success btn-sm w-100"><i class="fas fa-check me-1"></i>Mark Attendance</button>
                </form>
            </div>
            <div class="content-card">
                <h2><i class="fas fa-chart-simple text-info"></i>Today Summary</h2>
                <div class="d-flex justify-content-around text-center">
                    <div><span class="badge bg-success fs-6"><?= $stats['attendance_today'] ?></span><br><small>Present</small></div>
                    <div><span class="badge bg-warning text-dark fs-6"><?= $stats['late_today'] ?></span><br><small>Late</small></div>
                    <div><span class="badge bg-danger fs-6"><?= $stats['absent_today'] ?></span><br><small>Absent</small></div>
                </div>
            </div>
        </div>
        <div class="col-md-8">
            <div class="content-card">
                <h2><i class="fas fa-calendar-check text-primary"></i>Today's Attendance Log</h2>
                <div class="table-responsive">
                    <table class="table table-sm table-hover table-hr">
                        <thead><tr><th>Staff</th><th>ID</th><th>Time In</th><th>Time Out</th><th>Status</th><th>Remarks</th></tr></thead>
                        <tbody>
                        <?php if (empty($attendance_today)): ?>
                        <tr><td colspan="6" class="text-center text-muted py-3">No attendance records for today.</td></tr>
                        <?php else: foreach ($attendance_today as $a): ?>
                        <tr>
                            <td><?= htmlspecialchars($a['full_name']) ?></td>
                            <td><code><?= htmlspecialchars($a['staff_id']) ?></code></td>
                            <td><?= htmlspecialchars($a['check_in_time'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($a['check_out_time'] ?? '-') ?></td>
                            <td><?= hrStatusBadge($a['attendance_status']) ?></td>
                            <td><small><?= htmlspecialchars($a['remarks'] ?? '-') ?></small></td>
                        </tr>
                        <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="content-card">
                <h2><i class="fas fa-calendar-week text-info"></i>Upcoming Shifts (Next 7 Days) <span class="badge bg-info badge-count"><?= count($upcoming_shifts) ?></span></h2>
                <div class="table-responsive">
                    <table class="table table-sm table-hover table-hr">
                        <thead><tr><th>Staff</th><th>Date</th><th>Shift</th><th>Start</th><th>End</th><th>Location</th></tr></thead>
                        <tbody>
                        <?php if (empty($upcoming_shifts)): ?>
                        <tr><td colspan="6" class="text-center text-muted py-3">No shifts scheduled for the next 7 days.</td></tr>
                        <?php else: foreach ($upcoming_shifts as $sh): ?>
                        <tr>
                            <td><?= htmlspecialchars($sh['full_name']) ?></td>
                            <td><small><?= hrFormatDate($sh['shift_date']) ?></small></td>
                            <td><?= htmlspecialchars(ucfirst($sh['shift_type'] ?? 'Regular')) ?></td>
                            <td><?= htmlspecialchars($sh['start_time'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($sh['end_time'] ?? '-') ?></td>
                            <td><small><?= htmlspecialchars($sh['location'] ?? '-') ?></small></td>
                        </tr>
                        <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════ -->
<!-- SECTION: LEAVE MANAGEMENT                           -->
<!-- ═══════════════════════════════════════════════════ -->
<div class="hr-section <?= $active_section==='leave'?'active':'' ?>" id="section-leave">
    <div class="content-card">
        <h2><i class="fas fa-calendar-alt text-warning"></i>Pending Leave Requests <span class="badge bg-warning text-dark badge-count"><?= count($pending_leave) ?></span></h2>
        <?php if (empty($pending_leave)): ?>
        <p class="text-muted small">No pending leave requests.</p>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-sm table-hover table-hr">
                <thead><tr><th>Staff</th><th>Staff ID</th><th>Leave Type</th><th>Start</th><th>End</th><th>Days</th><th>Reason</th><th>Actions</th></tr></thead>
                <tbody>
                <?php foreach ($pending_leave as $lr):
                    $days = $lr['total_days'] ?? max(1, (strtotime($lr['end_date']) - strtotime($lr['start_date'])) / 86400 + 1);
                ?>
                <tr>
                    <td><strong><?= htmlspecialchars($lr['full_name']) ?></strong></td>
                    <td><code><?= htmlspecialchars($lr['staff_id']) ?></code></td>
                    <td><?= htmlspecialchars($lr['leave_name'] ?? 'Leave') ?></td>
                    <td><small><?= hrFormatDate($lr['start_date']) ?></small></td>
                    <td><small><?= hrFormatDate($lr['end_date']) ?></small></td>
                    <td><?= $days ?> days</td>
                    <td><small class="text-muted"><?= htmlspecialchars(StrLen($lr['reason']??'') > 40 ? substr($lr['reason'],0,40).'...' : ($lr['reason']??'-')) ?></small></td>
                    <td>
                        <form method="POST" class="d-inline">
                            <input type="hidden" name="action" value="approve_leave">
                            <input type="hidden" name="leave_id" value="<?= $lr['id'] ?>">
                            <button class="btn btn-xs btn-success"><i class="fas fa-check"></i></button>
                        </form>
                        <form method="POST" class="d-inline">
                            <input type="hidden" name="action" value="reject_leave">
                            <input type="hidden" name="leave_id" value="<?= $lr['id'] ?>">
                            <button class="btn btn-xs btn-danger"><i class="fas fa-times"></i></button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════ -->
<!-- SECTION: PERFORMANCE                                -->
<!-- ═══════════════════════════════════════════════════ -->
<div class="hr-section <?= $active_section==='performance'?'active':'' ?>" id="section-performance">
    <div class="content-card">
        <h2><i class="fas fa-chart-line text-primary"></i>Performance Management</h2>
        <div class="row g-3 mb-3">
            <div class="col-md-4">
                <div class="card border p-4 text-center h-100">
                    <i class="fas fa-clipboard-check fa-3x text-primary mb-3"></i>
                    <h5>Staff Appraisals</h5>
                    <p class="text-muted small"><?= $stats['pending_appraisals'] ?> pending reviews</p>
                    <a href="?section=performance" class="btn btn-outline-primary btn-sm">Manage</a>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border p-4 text-center h-100">
                    <i class="fas fa-chalkboard-teacher fa-3x text-info mb-3"></i>
                    <h5>Lecturer Evaluation</h5>
                    <p class="text-muted small">Student feedback integration</p>
                    <a href="?section=performance" class="btn btn-outline-info btn-sm">Evaluate</a>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border p-4 text-center h-100">
                    <i class="fas fa-bullseye fa-3x text-success mb-3"></i>
                    <h5>KPI Tracking</h5>
                    <p class="text-muted small">Key performance indicators</p>
                    <a href="?section=performance" class="btn btn-outline-success btn-sm">Track</a>
                </div>
            </div>
        </div>
        <h2><i class="fas fa-list text-secondary"></i>Recent Performance Reviews</h2>
        <div class="table-responsive">
            <table class="table table-sm table-hover table-hr">
                <thead><tr><th>Staff</th><th>Review Period</th><th>Score</th><th>Rating</th><th>Reviewer</th><th>Date</th><th>Status</th></tr></thead>
                <tbody>
                <?php if (empty($performance_reviews)): ?>
                <tr><td colspan="7" class="text-center text-muted py-3">No performance reviews recorded yet.</td></tr>
                <?php else: foreach ($performance_reviews as $pr): ?>
                <tr>
                    <td><?= htmlspecialchars($pr['full_name']) ?></td>
                    <td><small><?= htmlspecialchars($pr['review_period'] ?? '-') ?></small></td>
                    <td><strong><?= htmlspecialchars($pr['score'] ?? '-') ?></strong></td>
                    <td><?= hrStatusBadge($pr['rating'] ?? 'pending') ?></td>
                    <td><small><?= htmlspecialchars($pr['reviewer_name'] ?? '-') ?></small></td>
                    <td><small><?= hrFormatDate($pr['review_date']) ?></small></td>
                    <td><?= hrStatusBadge($pr['status']) ?></td>
                </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════ -->
<!-- SECTION: TRAINING & CPD                            -->
<!-- ═══════════════════════════════════════════════════ -->
<div class="hr-section <?= $active_section==='training'?'active':'' ?>" id="section-training">
    <div class="row g-3">
        <div class="col-md-5">
            <div class="content-card">
                <h2><i class="fas fa-plus-circle text-success"></i>Schedule Training</h2>
                <form method="POST">
                    <input type="hidden" name="action" value="add_training">
                    <div class="mb-2">
                        <label class="form-label">Staff Member</label>
                        <select name="staff_id" class="form-select form-select-sm" required>
                            <option value="">Select staff...</option>
                            <?php foreach ($staff_list as $s): ?>
                            <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['full_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Training Name</label>
                        <input type="text" name="training_name" class="form-control form-control-sm" required>
                    </div>
                    <div class="row g-2 mb-2">
                        <div class="col">
                            <label class="form-label">Start Date</label>
                            <input type="date" name="start_date" class="form-control form-control-sm">
                        </div>
                        <div class="col">
                            <label class="form-label">End Date</label>
                            <input type="date" name="end_date" class="form-control form-control-sm">
                        </div>
                        <div class="col">
                            <label class="form-label">Hours</label>
                            <input type="number" name="hours" class="form-control form-control-sm">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-success btn-sm w-100"><i class="fas fa-save me-1"></i>Schedule Training</button>
                </form>
            </div>
        </div>
        <div class="col-md-7">
            <div class="content-card">
                <h2><i class="fas fa-calendar text-info"></i>Upcoming Trainings <span class="badge bg-info badge-count"><?= count($upcoming_trainings) ?></span></h2>
                <?php if (empty($upcoming_trainings)): ?>
                <p class="text-muted small">No upcoming trainings scheduled.</p>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-sm table-hover table-hr">
                        <thead><tr><th>Staff</th><th>Training</th><th>Start</th><th>End</th><th>Hours</th><th>Status</th></tr></thead>
                        <tbody>
                        <?php foreach ($upcoming_trainings as $t): ?>
                        <tr>
                            <td><?= htmlspecialchars($t['full_name']) ?></td>
                            <td><?= htmlspecialchars($t['training_name']) ?></td>
                            <td><small><?= hrFormatDate($t['start_date']) ?></small></td>
                            <td><small><?= hrFormatDate($t['end_date']) ?></small></td>
                            <td><?= $t['hours'] ?? '-' ?></td>
                            <td><?= hrStatusBadge($t['status']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════ -->
<!-- SECTION: RECRUITMENT                                -->
<!-- ═══════════════════════════════════════════════════ -->
<div class="hr-section <?= $active_section==='recruitment'?'active':'' ?>" id="section-recruitment">
    <div class="row g-3">
        <div class="col-md-5">
            <div class="content-card">
                <h2><i class="fas fa-plus-circle text-primary"></i>Post New Vacancy</h2>
                <form method="POST">
                    <input type="hidden" name="action" value="add_vacancy">
                    <div class="mb-2">
                        <label class="form-label">Job Title</label>
                        <input type="text" name="job_title" class="form-control form-control-sm" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Department</label>
                        <select name="department_id" class="form-select form-select-sm">
                            <option value="0">Select department...</option>
                            <?php foreach ($departments as $d): ?>
                            <option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['department_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="row g-2 mb-2">
                        <div class="col">
                            <label class="form-label">Positions</label>
                            <input type="number" name="positions_available" class="form-control form-control-sm" value="1" min="1">
                        </div>
                        <div class="col">
                            <label class="form-label">Closing Date</label>
                            <input type="date" name="closing_date" class="form-control form-control-sm">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm w-100"><i class="fas fa-save me-1"></i>Post Vacancy</button>
                </form>
            </div>
        </div>
        <div class="col-md-7">
            <div class="content-card">
                <h2><i class="fas fa-briefcase text-success"></i>Open Vacancies <span class="badge bg-success badge-count"><?= count($open_vacancies) ?></span></h2>
                <?php if (empty($open_vacancies)): ?>
                <p class="text-muted small">No open vacancies.</p>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-sm table-hover table-hr">
                        <thead><tr><th>Job Title</th><th>Department</th><th>Positions</th><th>Posted</th><th>Closes</th><th>Status</th></tr></thead>
                        <tbody>
                        <?php foreach ($open_vacancies as $v): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($v['job_title']) ?></strong></td>
                            <td><?= htmlspecialchars($v['department_name'] ?? '-') ?></td>
                            <td><?= $v['number_of_positions'] ?></td>
                            <td><small><?= hrFormatDate($v['posted_date']) ?></small></td>
                            <td><small><?= hrFormatDate($v['closing_date']) ?></small></td>
                            <td><?= hrStatusBadge($v['status']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════ -->
<!-- ═══════════════════════════════════════════════════ -->
<!-- SECTION: PAYROLL (HR VIEW)                          -->
<!-- ═══════════════════════════════════════════════════ -->
<div class="hr-section <?= $active_section==='payroll'?'active':'' ?>" id="section-payroll">
    <div class="content-card">
        <div class="d-flex align-items-center gap-2 mb-3 flex-wrap">
            <a href="?section=payroll&tab=overview" class="btn btn-sm <?= $pay_active_tab==='overview'?'btn-success':'btn-outline-success' ?>"><i class="fas fa-chart-pie"></i> Overview</a>
            <a href="?section=payroll&tab=employees" class="btn btn-sm <?= $pay_active_tab==='employees'?'btn-primary':'btn-outline-primary' ?>"><i class="fas fa-users"></i> Employees</a>
            <a href="?section=payroll&tab=run" class="btn btn-sm <?= $pay_active_tab==='run'?'btn-info':'btn-outline-info' ?>"><i class="fas fa-play"></i> Payroll Run</a>
            <a href="?section=payroll&tab=allowances" class="btn btn-sm <?= $pay_active_tab==='allowances'?'btn-warning':'btn-outline-warning' ?>"><i class="fas fa-plus-circle"></i> Allowances</a>
            <a href="?section=payroll&tab=deductions" class="btn btn-sm <?= $pay_active_tab==='deductions'?'btn-danger':'btn-outline-danger' ?>"><i class="fas fa-minus-circle"></i> Deductions</a>
            <a href="?section=payroll&tab=overtime" class="btn btn-sm <?= $pay_active_tab==='overtime'?'btn-secondary':'btn-outline-secondary' ?>"><i class="fas fa-clock"></i> Overtime</a>
            <a href="?section=payroll&tab=bonuses" class="btn btn-sm <?= $pay_active_tab==='bonuses'?'btn-success':'btn-outline-success' ?>"><i class="fas fa-gift"></i> Bonuses</a>
            <a href="?section=payroll&tab=payslips" class="btn btn-sm <?= $pay_active_tab==='payslips'?'btn-primary':'btn-outline-primary' ?>"><i class="fas fa-file-invoice"></i> Payslips</a>
            <a href="?section=payroll&tab=approvals" class="btn btn-sm <?= $pay_active_tab==='approvals'?'btn-warning':'btn-outline-warning' ?>"><i class="fas fa-check-double"></i> Approvals</a>
            <a href="?section=payroll&tab=reports" class="btn btn-sm <?= $pay_active_tab==='reports'?'btn-info':'btn-outline-info' ?>"><i class="fas fa-chart-bar"></i> Reports</a>
        </div>
    </div>

<?php if ($pay_active_tab === 'overview'): ?>
    <!-- ─── PAYROLL OVERVIEW ─── -->
    <div class="row g-3 mb-3">
        <div class="col-6 col-md-3">
            <div class="stat-card position-relative" style="border-left-color:#1e3a5f;">
                <i class="fas fa-users stat-icon"></i>
                <h3><?= $pay_stats['total_employees'] ?></h3>
                <p>Payroll Employees</p>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card position-relative" style="border-left-color:#e63946;">
                <i class="fas fa-play-circle stat-icon"></i>
                <h3><?= $pay_stats['active_runs'] ?></h3>
                <p>Active Runs</p>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card position-relative" style="border-left-color:#e09f3e;">
                <i class="fas fa-hourglass-half stat-icon"></i>
                <h3><?= $pay_stats['pending_approvals'] ?></h3>
                <p>Pending Approvals</p>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card position-relative" style="border-left-color:#2d6a4f;">
                <i class="fas fa-wallet stat-icon"></i>
                <h3>UGX <?= payFormatMoney($pay_stats['current_run_gross']) ?></h3>
                <p>Current Gross Pay</p>
            </div>
        </div>
    </div>
    <div class="row g-3">
        <div class="col-md-8">
            <div class="content-card">
                <h2><i class="fas fa-list text-secondary"></i>Payroll Runs</h2>
                <div class="table-responsive">
                    <table class="table table-sm table-hover table-hr">
                        <thead><tr><th>Period</th><th>Employees</th><th>Gross</th><th>Deductions</th><th>Net</th><th>Status</th><th>Created</th></tr></thead>
                        <tbody>
                        <?php if (empty($pay_runs)): ?>
                        <tr><td colspan="7" class="text-center text-muted py-3">No payroll runs yet.</td></tr>
                        <?php else: foreach ($pay_runs as $pr): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($pr['period']) ?></strong></td>
                            <td><?= $pr['employee_count'] ?? 0 ?></td>
                            <td>UGX <?= payFormatMoney($pr['total_gross']) ?></td>
                            <td>UGX <?= payFormatMoney($pr['total_deductions']) ?></td>
                            <td>UGX <?= payFormatMoney($pr['total_net']) ?></td>
                            <td><?= payStatusBadge($pr['status']) ?></td>
                            <td><small><?= hrFormatDate($pr['created_at']) ?></small></td>
                        </tr>
                        <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="content-card">
                <h2><i class="fas fa-calculator text-info"></i>Current Month Summary</h2>
                <table class="table table-sm table-hr">
                    <tr><td>Allowances</td><td class="text-end">UGX <?= payFormatMoney($pay_stats['month_allowances']) ?></td></tr>
                    <tr><td>Overtime</td><td class="text-end">UGX <?= payFormatMoney($pay_stats['month_overtime']) ?></td></tr>
                    <tr><td>Bonuses</td><td class="text-end">UGX <?= payFormatMoney($pay_stats['month_bonuses']) ?></td></tr>
                    <tr><td>Deductions</td><td class="text-end text-danger">UGX <?= payFormatMoney($pay_stats['month_deductions']) ?></td></tr>
                    <tr class="table-active"><th>Net Payroll</th><th class="text-end">UGX <?= payFormatMoney($pay_stats['current_run_net']) ?></th></tr>
                </table>
            </div>
        </div>
    </div>

<?php elseif ($pay_active_tab === 'employees'): ?>
    <!-- ─── PAYROLL EMPLOYEES ─── -->
    <div class="row g-3">
        <div class="col-md-5">
            <div class="content-card">
                <h2><i class="fas fa-user-plus text-primary"></i>Add / Edit Payroll Profile</h2>
                <form method="POST">
                    <input type="hidden" name="action" value="pay_add_employee">
                    <div class="mb-2">
                        <label class="form-label">Staff *</label>
                        <select name="staff_id" class="form-select form-select-sm" required>
                            <option value="">Select staff...</option>
                            <?php foreach ($staff_list as $s): ?>
                            <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['full_name']) ?> (<?= htmlspecialchars($s['staff_id']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="row g-2 mb-2">
                        <div class="col-6"><label class="form-label">Basic Salary *</label><input type="number" name="basic_salary" class="form-control form-control-sm" step="0.01" required></div>
                        <div class="col-6"><label class="form-label">Type</label>
                            <select name="salary_type" class="form-select form-select-sm">
                                <option value="monthly">Monthly</option>
                                <option value="annual">Annual</option>
                            </select>
                        </div>
                    </div>
                    <div class="row g-2 mb-2">
                        <div class="col-6"><label class="form-label">Bank Name</label><input type="text" name="bank_name" class="form-control form-control-sm"></div>
                        <div class="col-6"><label class="form-label">Account No</label><input type="text" name="bank_account" class="form-control form-control-sm"></div>
                    </div>
                    <div class="row g-2 mb-2">
                        <div class="col-4"><label class="form-label">TIN</label><input type="text" name="tin_number" class="form-control form-control-sm"></div>
                        <div class="col-4"><label class="form-label">NSSF No</label><input type="text" name="nssf_number" class="form-control form-control-sm"></div>
                        <div class="col-4"><label class="form-label">Grade</label><input type="text" name="salary_grade" class="form-control form-control-sm" placeholder="e.g. L_G1"></div>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Effective From</label>
                        <input type="date" name="effective_from" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>">
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm w-100"><i class="fas fa-save me-1"></i>Save Profile</button>
                </form>
            </div>
        </div>
        <div class="col-md-7">
            <div class="content-card">
                <h2><i class="fas fa-list text-secondary"></i>Payroll Employees <span class="badge bg-primary badge-count"><?= count($pay_employees) ?></span></h2>
                <div class="table-responsive" style="max-height:400px;overflow-y:auto;">
                    <table class="table table-sm table-hover table-hr">
                        <thead><tr><th>Staff</th><th>Salary</th><th>Type</th><th>Bank</th><th>TIN</th><th>NSSF</th><th>Grade</th></tr></thead>
                        <tbody>
                        <?php if (empty($pay_employees)): ?>
                        <tr><td colspan="7" class="text-center text-muted py-3">No payroll profiles set up.</td></tr>
                        <?php else: foreach ($pay_employees as $pe): ?>
                        <tr>
                            <td><small><?= htmlspecialchars($pe['full_name']) ?></small></td>
                            <td><strong>UGX <?= payFormatMoney($pe['basic_salary']) ?></strong></td>
                            <td><?= ucfirst($pe['salary_type'] ?? 'monthly') ?></td>
                            <td><small><?= htmlspecialchars($pe['bank_name'] ?? '-') ?></small></td>
                            <td><code><?= htmlspecialchars($pe['tin_number'] ?? '-') ?></code></td>
                            <td><code><?= htmlspecialchars($pe['nssf_number'] ?? '-') ?></code></td>
                            <td><span class="badge bg-secondary"><?= htmlspecialchars($pe['salary_grade'] ?? '-') ?></span></td>
                        </tr>
                        <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

<?php elseif ($pay_active_tab === 'run'): ?>
    <!-- ─── PAYROLL RUN ─── -->
    <div class="row g-3">
        <div class="col-md-4">
            <div class="content-card">
                <h2><i class="fas fa-play-circle text-success"></i>Create New Payroll Run</h2>
                <form method="POST">
                    <input type="hidden" name="action" value="pay_run_create">
                    <div class="mb-2">
                        <label class="form-label">Period (YYYY-MM)</label>
                        <input type="month" name="period" class="form-control form-control-sm" value="<?= date('Y-m') ?>" required>
                    </div>
                    <div class="row g-2 mb-2">
                        <div class="col"><label class="form-label">Start Date</label><input type="date" name="start_date" class="form-control form-control-sm" value="<?= date('Y-m-01') ?>"></div>
                        <div class="col"><label class="form-label">End Date</label><input type="date" name="end_date" class="form-control form-control-sm" value="<?= date('Y-m-t') ?>"></div>
                    </div>
                    <button type="submit" class="btn btn-success btn-sm w-100"><i class="fas fa-calculator me-1"></i>Calculate & Create Run</button>
                </form>
                <hr>
                <form method="POST" class="mt-2">
                    <input type="hidden" name="action" value="pay_mark_paid">
                    <label class="form-label">Mark Run as Paid</label>
                    <div class="row g-2">
                        <div class="col"><select name="run_id" class="form-select form-select-sm" required>
                            <option value="">Select processed run...</option>
                            <?php foreach ($pay_runs as $pr): if ($pr['status']==='processed'): ?>
                            <option value="<?= $pr['id'] ?>"><?= htmlspecialchars($pr['period']) ?> (UGX <?= payFormatMoney($pr['total_net']) ?>)</option>
                            <?php endif; endforeach; ?>
                        </select></div>
                        <div class="col"><select name="payment_method" class="form-select form-select-sm">
                            <option value="bank_transfer">Bank Transfer</option>
                            <option value="mobile_money">Mobile Money</option>
                            <option value="cheque">Cheque</option>
                        </select></div>
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm w-100 mt-2"><i class="fas fa-check me-1"></i>Mark Paid</button>
                </form>
            </div>
        </div>
        <div class="col-md-8">
            <div class="content-card">
                <h2><i class="fas fa-list text-secondary"></i>Payroll Runs</h2>
                <div class="table-responsive" style="max-height:450px;overflow-y:auto;">
                    <table class="table table-sm table-hover table-hr">
                        <thead><tr><th>Period</th><th>Staff</th><th>Gross</th><th>Deductions</th><th>Net</th><th>Status</th><th>Created By</th></tr></thead>
                        <tbody>
                        <?php if (empty($pay_runs)): ?>
                        <tr><td colspan="7" class="text-center text-muted py-3">No runs yet.</td></tr>
                        <?php else: foreach ($pay_runs as $pr): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($pr['period']) ?></strong></td>
                            <td><?= $pr['employee_count'] ?? 0 ?></td>
                            <td>UGX <?= payFormatMoney($pr['total_gross']) ?></td>
                            <td class="text-danger">UGX <?= payFormatMoney($pr['total_deductions']) ?></td>
                            <td><strong>UGX <?= payFormatMoney($pr['total_net']) ?></strong></td>
                            <td><?= payStatusBadge($pr['status']) ?></td>
                            <td><small><?= htmlspecialchars($pr['created_name'] ?? '-') ?></small></td>
                        </tr>
                        <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

<?php elseif ($pay_active_tab === 'allowances'): ?>
    <!-- ─── ALLOWANCES ─── -->
    <div class="row g-3">
        <div class="col-md-4">
            <div class="content-card">
                <h2><i class="fas fa-plus-circle text-success"></i>Add Allowance</h2>
                <form method="POST">
                    <input type="hidden" name="action" value="pay_add_allowance">
                    <div class="mb-2">
                        <label class="form-label">Staff</label>
                        <select name="staff_id" class="form-select form-select-sm" required>
                            <option value="">Select...</option>
                            <?php foreach ($staff_list as $s): ?>
                            <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['full_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Type</label>
                        <select name="allowance_type" class="form-select form-select-sm" required>
                            <option value="">Select...</option>
                            <option value="housing">Housing</option>
                            <option value="transport">Transport</option>
                            <option value="medical">Medical</option>
                            <option value="airtime">Airtime</option>
                            <option value="lunch">Lunch</option>
                            <option value="hardship">Hardship</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="row g-2 mb-2">
                        <div class="col"><label class="form-label">Amount</label><input type="number" name="amount" class="form-control form-control-sm" step="0.01" required></div>
                        <div class="col"><label class="form-label">Month</label><input type="month" name="month" class="form-control form-control-sm" value="<?= date('Y-m') ?>"></div>
                    </div>
                    <button type="submit" class="btn btn-success btn-sm w-100"><i class="fas fa-save me-1"></i>Add Allowance</button>
                </form>
            </div>
        </div>
        <div class="col-md-8">
            <div class="content-card">
                <h2><i class="fas fa-list text-secondary"></i>Current Month Allowances <span class="badge bg-warning badge-count"><?= count($pay_allowances) ?></span></h2>
                <div class="table-responsive" style="max-height:400px;overflow-y:auto;">
                    <table class="table table-sm table-hover table-hr">
                        <thead><tr><th>Staff</th><th>Type</th><th>Amount</th><th>Month</th></tr></thead>
                        <tbody>
                        <?php if (empty($pay_allowances)): ?>
                        <tr><td colspan="4" class="text-center text-muted py-3">No allowances for this month.</td></tr>
                        <?php else: foreach ($pay_allowances as $pa): ?>
                        <tr>
                            <td><?= htmlspecialchars($pa['full_name']) ?></td>
                            <td><span class="badge bg-info"><?= ucfirst($pa['allowance_type']) ?></span></td>
                            <td><strong>UGX <?= payFormatMoney($pa['amount']) ?></strong></td>
                            <td><small><?= htmlspecialchars($pa['month']) ?></small></td>
                        </tr>
                        <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

<?php elseif ($pay_active_tab === 'deductions'): ?>
    <!-- ─── DEDUCTIONS ─── -->
    <div class="row g-3">
        <div class="col-md-4">
            <div class="content-card">
                <h2><i class="fas fa-minus-circle text-danger"></i>Add Deduction</h2>
                <form method="POST">
                    <input type="hidden" name="action" value="pay_add_deduction">
                    <div class="mb-2">
                        <label class="form-label">Staff</label>
                        <select name="staff_id" class="form-select form-select-sm" required>
                            <option value="">Select...</option>
                            <?php foreach ($staff_list as $s): ?>
                            <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['full_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Type</label>
                        <select name="deduction_type" class="form-select form-select-sm" required>
                            <option value="">Select...</option>
                            <option value="salary_advance">Salary Advance</option>
                            <option value="loan_repayment">Loan Repayment</option>
                            <option value="sacco">SACCO Contribution</option>
                            <option value="insurance">Insurance</option>
                            <option value="union_dues">Union Dues</option>
                            <option value="disciplinary">Disciplinary Fine</option>
                        </select>
                    </div>
                    <div class="row g-2 mb-2">
                        <div class="col"><label class="form-label">Amount</label><input type="number" name="amount" class="form-control form-control-sm" step="0.01" required></div>
                        <div class="col"><label class="form-label">Month</label><input type="month" name="month" class="form-control form-control-sm" value="<?= date('Y-m') ?>"></div>
                    </div>
                    <button type="submit" class="btn btn-danger btn-sm w-100"><i class="fas fa-save me-1"></i>Add Deduction</button>
                </form>
            </div>
        </div>
        <div class="col-md-8">
            <div class="content-card">
                <h2><i class="fas fa-list text-secondary"></i>Current Month Deductions <span class="badge bg-danger badge-count"><?= count($pay_deductions) ?></span></h2>
                <div class="table-responsive" style="max-height:400px;overflow-y:auto;">
                    <table class="table table-sm table-hover table-hr">
                        <thead><tr><th>Staff</th><th>Type</th><th>Amount</th><th>Month</th></tr></thead>
                        <tbody>
                        <?php if (empty($pay_deductions)): ?>
                        <tr><td colspan="4" class="text-center text-muted py-3">No deductions for this month.</td></tr>
                        <?php else: foreach ($pay_deductions as $pd): ?>
                        <tr>
                            <td><?= htmlspecialchars($pd['full_name']) ?></td>
                            <td><span class="badge bg-secondary"><?= ucfirst(str_replace('_',' ',$pd['deduction_type'])) ?></span></td>
                            <td><strong class="text-danger">UGX <?= payFormatMoney($pd['amount']) ?></strong></td>
                            <td><small><?= htmlspecialchars($pd['month']) ?></small></td>
                        </tr>
                        <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

<?php elseif ($pay_active_tab === 'overtime'): ?>
    <!-- ─── OVERTIME ─── -->
    <div class="row g-3">
        <div class="col-md-4">
            <div class="content-card">
                <h2><i class="fas fa-clock text-info"></i>Add Overtime</h2>
                <form method="POST">
                    <input type="hidden" name="action" value="pay_add_overtime">
                    <div class="mb-2">
                        <label class="form-label">Staff</label>
                        <select name="staff_id" class="form-select form-select-sm" required>
                            <option value="">Select...</option>
                            <?php foreach ($staff_list as $s): ?>
                            <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['full_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="row g-2 mb-2">
                        <div class="col"><label class="form-label">Hours</label><input type="number" name="hours" class="form-control form-control-sm" step="0.5" required></div>
                        <div class="col"><label class="form-label">Rate/Hour</label><input type="number" name="rate" class="form-control form-control-sm" step="0.01" required></div>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Month</label>
                        <input type="month" name="month" class="form-control form-control-sm" value="<?= date('Y-m') ?>">
                    </div>
                    <button type="submit" class="btn btn-info btn-sm w-100"><i class="fas fa-save me-1"></i>Add Overtime</button>
                </form>
            </div>
        </div>
        <div class="col-md-8">
            <div class="content-card">
                <h2><i class="fas fa-list text-secondary"></i>Overtime Records <span class="badge bg-info badge-count"><?= count($pay_overtime) ?></span></h2>
                <div class="table-responsive" style="max-height:400px;overflow-y:auto;">
                    <table class="table table-sm table-hover table-hr">
                        <thead><tr><th>Staff</th><th>Hours</th><th>Rate</th><th>Total Pay</th><th>Month</th><th>Approved By</th></tr></thead>
                        <tbody>
                        <?php if (empty($pay_overtime)): ?>
                        <tr><td colspan="6" class="text-center text-muted py-3">No overtime records.</td></tr>
                        <?php else: foreach ($pay_overtime as $po): ?>
                        <tr>
                            <td><?= htmlspecialchars($po['full_name']) ?></td>
                            <td><?= $po['hours'] ?></td>
                            <td>UGX <?= payFormatMoney($po['rate']) ?></td>
                            <td><strong>UGX <?= payFormatMoney($po['total_pay']) ?></strong></td>
                            <td><small><?= htmlspecialchars($po['month']) ?></small></td>
                            <td><small><?= htmlspecialchars($po['approver_name'] ?? '-') ?></small></td>
                        </tr>
                        <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

<?php elseif ($pay_active_tab === 'bonuses'): ?>
    <!-- ─── BONUSES ─── -->
    <div class="row g-3">
        <div class="col-md-4">
            <div class="content-card">
                <h2><i class="fas fa-gift text-success"></i>Add Bonus</h2>
                <form method="POST">
                    <input type="hidden" name="action" value="pay_add_bonus">
                    <div class="mb-2">
                        <label class="form-label">Staff</label>
                        <select name="staff_id" class="form-select form-select-sm" required>
                            <option value="">Select...</option>
                            <?php foreach ($staff_list as $s): ?>
                            <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['full_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Type</label>
                        <select name="bonus_type" class="form-select form-select-sm" required>
                            <option value="">Select...</option>
                            <option value="performance">Performance Bonus</option>
                            <option value="commission">Commission</option>
                            <option value="appreciation">Appreciation</option>
                            <option value="academic_excellence">Academic Excellence</option>
                            <option value="clinical_supervision">Clinical Supervision</option>
                        </select>
                    </div>
                    <div class="row g-2 mb-2">
                        <div class="col"><label class="form-label">Amount</label><input type="number" name="amount" class="form-control form-control-sm" step="0.01" required></div>
                        <div class="col"><label class="form-label">Month</label><input type="month" name="month" class="form-control form-control-sm" value="<?= date('Y-m') ?>"></div>
                    </div>
                    <button type="submit" class="btn btn-success btn-sm w-100"><i class="fas fa-save me-1"></i>Add Bonus</button>
                </form>
            </div>
        </div>
        <div class="col-md-8">
            <div class="content-card">
                <h2><i class="fas fa-list text-secondary"></i>Bonuses <span class="badge bg-success badge-count"><?= count($pay_bonuses) ?></span></h2>
                <div class="table-responsive" style="max-height:400px;overflow-y:auto;">
                    <table class="table table-sm table-hover table-hr">
                        <thead><tr><th>Staff</th><th>Type</th><th>Amount</th><th>Month</th></tr></thead>
                        <tbody>
                        <?php if (empty($pay_bonuses)): ?>
                        <tr><td colspan="4" class="text-center text-muted py-3">No bonuses recorded.</td></tr>
                        <?php else: foreach ($pay_bonuses as $pb): ?>
                        <tr>
                            <td><?= htmlspecialchars($pb['full_name']) ?></td>
                            <td><span class="badge bg-success"><?= ucfirst(str_replace('_',' ',$pb['bonus_type'])) ?></span></td>
                            <td><strong>UGX <?= payFormatMoney($pb['amount']) ?></strong></td>
                            <td><small><?= htmlspecialchars($pb['month']) ?></small></td>
                        </tr>
                        <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

<?php elseif ($pay_active_tab === 'payslips'): ?>
    <!-- ─── PAYSLIPS ─── -->
    <div class="row g-3">
        <div class="col-md-4">
            <div class="content-card">
                <h2><i class="fas fa-file-invoice text-primary"></i>Generate Payslips</h2>
                <form method="POST">
                    <input type="hidden" name="action" value="pay_generate_payslips">
                    <div class="mb-2">
                        <label class="form-label">Select Payroll Run</label>
                        <select name="run_id" class="form-select form-select-sm" required>
                            <option value="">Select run...</option>
                            <?php foreach ($pay_runs as $pr): if ($pr['status']==='approved'): ?>
                            <option value="<?= $pr['id'] ?>"><?= htmlspecialchars($pr['period']) ?></option>
                            <?php endif; endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm w-100"><i class="fas fa-print me-1"></i>Generate Payslips</button>
                </form>
                <hr>
                <p class="text-muted small mt-2"><i class="fas fa-info-circle me-1"></i>Payslips are generated for approved runs. Each employee gets a unique payment reference number.</p>
            </div>
            <div class="content-card">
                <h2><i class="fas fa-search text-info"></i>Find Payslip</h2>
                <div class="mb-2">
                    <select id="payslipStaffSelect" class="form-select form-select-sm">
                        <option value="">Select staff...</option>
                        <?php foreach ($staff_list as $s): ?>
                        <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['full_name']) ?> (<?= htmlspecialchars($s['staff_id']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <select id="payslipRunSelect" class="form-select form-select-sm mb-2">
                    <option value="">Select period...</option>
                    <?php foreach ($pay_runs as $pr): ?>
                    <option value="<?= $pr['id'] ?>"><?= htmlspecialchars($pr['period']) ?></option>
                    <?php endforeach; ?>
                </select>
                <button class="btn btn-outline-primary btn-sm w-100" onclick="viewPayslip()"><i class="fas fa-eye me-1"></i>View Payslip</button>
            </div>
        </div>
        <div class="col-md-8">
            <div class="content-card">
                <h2><i class="fas fa-list text-secondary"></i>Generated Payslips</h2>
                <?php
                $payslips = [];
                if ($staff_conn) {
                    $r = $staff_conn->query("SELECT ps.*, s.full_name, s.staff_id, pr.period, pd.gross_pay, pd.net_pay, pd.paye_tax, pd.nssf_employee, pd.total_allowances, pd.overtime_pay, pd.bonuses, pd.other_deductions, pd.basic_salary FROM payslips ps JOIN staff s ON ps.staff_id = s.id JOIN payroll_runs pr ON ps.payroll_run_id = pr.id LEFT JOIN payroll_details pd ON ps.payroll_detail_id = pd.id ORDER BY ps.generated_date DESC LIMIT 50");
                    if ($r) $payslips = $r->fetch_all(MYSQLI_ASSOC);
                }
                ?>
                <div class="table-responsive" style="max-height:450px;overflow-y:auto;">
                    <table class="table table-sm table-hover table-hr">
                        <thead><tr><th>Ref</th><th>Staff</th><th>Period</th><th>Gross</th><th>Net</th><th>Generated</th></tr></thead>
                        <tbody>
                        <?php if (empty($payslips)): ?>
                        <tr><td colspan="6" class="text-center text-muted py-3">No payslips generated yet.</td></tr>
                        <?php else: foreach ($payslips as $ps): ?>
                        <tr>
                            <td><code><?= htmlspecialchars($ps['payment_ref'] ?? '-') ?></code></td>
                            <td><small><?= htmlspecialchars($ps['full_name']) ?></small></td>
                            <td><?= htmlspecialchars($ps['period']) ?></td>
                            <td>UGX <?= payFormatMoney($ps['gross_pay']) ?></td>
                            <td><strong>UGX <?= payFormatMoney($ps['net_pay']) ?></strong></td>
                            <td><small><?= hrFormatDate($ps['generated_date']) ?></small></td>
                        </tr>
                        <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

<?php elseif ($pay_active_tab === 'approvals'): ?>
    <!-- ─── APPROVALS ─── -->
    <div class="row g-3">
        <div class="col-md-5">
            <div class="content-card">
                <h2><i class="fas fa-check-double text-warning"></i>Approval Workflow</h2>
                <form method="POST">
                    <input type="hidden" name="action" value="pay_run_approve">
                    <div class="mb-2">
                        <label class="form-label">Payroll Run</label>
                        <select name="run_id" class="form-select form-select-sm" required>
                            <option value="">Select run...</option>
                            <?php foreach ($pay_runs as $pr): if (in_array($pr['status'],['draft','approved'])): ?>
                            <option value="<?= $pr['id'] ?>"><?= htmlspecialchars($pr['period']) ?> (<?= ucfirst($pr['status']) ?>)</option>
                            <?php endif; endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Approval Level</label>
                        <select name="approval_level" class="form-select form-select-sm" required>
                            <option value="HR">1. HR Manager</option>
                            <option value="PayrollOfficer">2. Payroll Officer</option>
                            <option value="Bursar">3. Bursar/Finance</option>
                            <option value="DirectorFinance">4. Director Finance</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-warning btn-sm w-100"><i class="fas fa-check me-1"></i>Approve at This Level</button>
                </form>
                <hr>
                <div class="alert alert-info small mb-0">
                    <i class="fas fa-info-circle me-1"></i> Workflow: HR Manager &rarr; Payroll Officer &rarr; Bursar &rarr; Director Finance. All 4 levels must approve before processing.
                </div>
            </div>
        </div>
        <div class="col-md-7">
            <div class="content-card">
                <h2><i class="fas fa-list text-secondary"></i>Approval Status by Run</h2>
                <div class="table-responsive" style="max-height:450px;overflow-y:auto;">
                    <table class="table table-sm table-hover table-hr">
                        <thead><tr><th>Period</th><th>HR</th><th>Payroll Officer</th><th>Bursar</th><th>Director Finance</th><th>Status</th></tr></thead>
                        <tbody>
                        <?php 
                        $all_runs = payGetRuns($staff_conn, null, 50);
                        foreach ($all_runs as $prun): 
                            $approvals = payGetApprovals($staff_conn, $prun['id']);
                            $hr = 'pending'; $po = 'pending'; $bu = 'pending'; $df = 'pending';
                            foreach ($approvals as $a) {
                                if ($a['level'] === 'HR') $hr = $a['status'];
                                if ($a['level'] === 'PayrollOfficer') $po = $a['status'];
                                if ($a['level'] === 'Bursar') $bu = $a['status'];
                                if ($a['level'] === 'DirectorFinance') $df = $a['status'];
                            }
                        ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($prun['period']) ?></strong></td>
                            <td><?= payStatusBadge($hr) ?></td>
                            <td><?= payStatusBadge($po) ?></td>
                            <td><?= payStatusBadge($bu) ?></td>
                            <td><?= payStatusBadge($df) ?></td>
                            <td><?= payStatusBadge($prun['status']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

<?php elseif ($pay_active_tab === 'reports'): ?>
    <!-- ─── REPORTS ─── -->
    <div class="row g-3">
        <div class="col-md-3">
            <div class="card border p-4 text-center h-100">
                <i class="fas fa-file-alt fa-3x text-primary mb-3"></i>
                <h5>Payroll Summary</h5>
                <p class="text-muted small">Overall payroll by period</p>
                <a href="?section=payroll&tab=reports" class="btn btn-outline-primary btn-sm">View</a>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border p-4 text-center h-100">
                <i class="fas fa-building fa-3x text-info mb-3"></i>
                <h5>By Department</h5>
                <p class="text-muted small">Department payroll breakdown</p>
                <a href="?section=payroll&tab=reports" class="btn btn-outline-info btn-sm">View</a>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border p-4 text-center h-100">
                <i class="fas fa-file-invoice-dollar fa-3x text-success mb-3"></i>
                <h5>Deductions Report</h5>
                <p class="text-muted small">Tax, NSSF & other deductions</p>
                <a href="?section=payroll&tab=reports" class="btn btn-outline-success btn-sm">View</a>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border p-4 text-center h-100">
                <i class="fas fa-history fa-3x text-secondary mb-3"></i>
                <h5>Audit Trail</h5>
                <p class="text-muted small">Payroll activity log</p>
                <a href="?section=payroll&tab=reports" class="btn btn-outline-secondary btn-sm">View</a>
            </div>
        </div>
    </div>
    <div class="content-card mt-3">
        <h2><i class="fas fa-chart-bar text-secondary"></i>Payroll by Department</h2>
        <?php
        $dept_payroll = [];
        if ($staff_conn) {
            $r = $staff_conn->query("SELECT d.department_name, COUNT(pe.id) as emp_count, COALESCE(SUM(pe.basic_salary),0) as total_salary FROM departments d LEFT JOIN staff s ON s.department_id = d.id LEFT JOIN payroll_employees pe ON pe.staff_id = s.id GROUP BY d.id, d.department_name ORDER BY total_salary DESC");
            if ($r) $dept_payroll = $r->fetch_all(MYSQLI_ASSOC);
        }
        ?>
        <div class="table-responsive">
            <table class="table table-sm table-hover table-hr">
                <thead><tr><th>Department</th><th>Employees</th><th>Total Salary</th></tr></thead>
                <tbody>
                <?php foreach ($dept_payroll as $dp): ?>
                <tr>
                    <td><?= htmlspecialchars($dp['department_name']) ?></td>
                    <td><?= $dp['emp_count'] ?></td>
                    <td><strong>UGX <?= payFormatMoney($dp['total_salary']) ?></strong></td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>
    </div>
<?php endif; ?>
</div>

<!-- ═══════════════════════════════════════════════════ -->
<!-- SECTION: DISCIPLINARY                               -->
<!-- ═══════════════════════════════════════════════════ -->
<div class="hr-section <?= $active_section==='disciplinary'?'active':'' ?>" id="section-disciplinary">
    <div class="content-card">
        <h2><i class="fas fa-gavel text-danger"></i>Disciplinary Cases <span class="badge bg-danger badge-count"><?= count($open_cases) ?></span></h2>
        <?php if (empty($open_cases)): ?>
        <p class="text-muted small">No open disciplinary cases.</p>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-sm table-hover table-hr">
                <thead><tr><th>Staff</th><th>Case Type</th><th>Incident Date</th><th>Status</th><th>Created</th><th>Actions</th></tr></thead>
                <tbody>
                <?php foreach ($open_cases as $c): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($c['full_name']) ?></strong></td>
                    <td><?= htmlspecialchars(ucfirst(str_replace('_',' ',$c['case_type']))) ?></td>
                    <td><small><?= hrFormatDate($c['incident_date']) ?></small></td>
                    <td><?= hrStatusBadge($c['status']) ?></td>
                    <td><small><?= hrFormatDate($c['created_at']) ?></small></td>
                    <td><button class="btn btn-xs btn-outline-primary"><i class="fas fa-eye"></i></button></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════ -->
<!-- SECTION: LICENSES & COMPLIANCE                      -->
<!-- ═══════════════════════════════════════════════════ -->
<div class="hr-section <?= $active_section==='licenses'?'active':'' ?>" id="section-licenses">
    <div class="content-card">
        <h2><i class="fas fa-certificate text-warning"></i>Professional License Compliance <span class="badge bg-danger badge-count"><?= count($expired_licenses) ?> Alerts</span></h2>
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle me-2"></i> Licenses expiring within 60 days or already expired are shown below. Staff with expired licenses must be restricted from clinical assignments.
        </div>
        <?php if (empty($expired_licenses)): ?>
        <p class="text-muted small">All licenses are up to date. No compliance issues.</p>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-sm table-hover table-hr">
                <thead><tr><th>Staff</th><th>Staff ID</th><th>License Type</th><th>Number</th><th>Issued</th><th>Expiry</th><th>Status</th><th>Clinical Eligibility</th></tr></thead>
                <tbody>
                <?php foreach ($expired_licenses as $l):
                    $eligible = $l['status'] === 'valid' && strtotime($l['expiry_date']) >= time();
                ?>
                <tr>
                    <td><strong><?= htmlspecialchars($l['full_name']) ?></strong></td>
                    <td><code><?= htmlspecialchars($l['staff_id']) ?></code></td>
                    <td><?= htmlspecialchars(ucfirst(str_replace('_',' ',$l['license_type']))) ?></td>
                    <td><code><?= htmlspecialchars($l['license_number'] ?? '-') ?></code></td>
                    <td><small><?= hrFormatDate($l['issue_date']) ?></small></td>
                    <td>
                        <small><?= hrFormatDate($l['expiry_date']) ?></small>
                        <span class="license-expiry <?= $l['status']==='expired'||strtotime($l['expiry_date'])<time()?'urgent':(strtotime($l['expiry_date'])<strtotime('+60 days')?'soon':'safe') ?> ms-2">
                            <?= $l['status']==='expired'||strtotime($l['expiry_date'])<time()?'EXPIRED':(strtotime($l['expiry_date'])<strtotime('+30 days')?'Expires soon!':(strtotime($l['expiry_date'])<strtotime('+60 days')?'Expiring':'Valid')) ?>
                        </span>
                    </td>
                    <td><?= hrStatusBadge($l['status']) ?></td>
                    <td>
                        <?php if ($eligible): ?>
                        <span class="badge bg-success">Eligible</span>
                        <?php else: ?>
                        <span class="badge bg-danger">Restricted</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════ -->
<!-- SECTION: DEPLOYMENT & ROTATION                      -->
<!-- ═══════════════════════════════════════════════════ -->
<div class="hr-section <?= $active_section==='rotation'?'active':'' ?>" id="section-rotation">
    <div class="content-card">
        <h2><i class="fas fa-exchange-alt text-info"></i>Clinical Rotation & Deployment</h2>
        <div class="row g-3 mb-3">
            <div class="col-md-6">
                <div class="card border p-4 text-center h-100">
                    <i class="fas fa-hospital fa-3x text-primary mb-3"></i>
                    <h5>Clinical Rotation Scheduling</h5>
                    <p class="text-muted small">Assign staff to clinical rotations</p>
                    <a href="?section=rotation" class="btn btn-outline-primary btn-sm">Manage</a>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card border p-4 text-center h-100">
                    <i class="fas fa-chalkboard-teacher fa-3x text-success mb-3"></i>
                    <h5>Lecturer Deployment</h5>
                    <p class="text-muted small">Assign lecturers to courses & shifts</p>
                    <a href="?section=rotation" class="btn btn-outline-success btn-sm">Manage</a>
                </div>
            </div>
        </div>
        <h2><i class="fas fa-list text-secondary"></i>Active Rotations</h2>
        <div class="table-responsive">
            <table class="table table-sm table-hover table-hr">
                <thead><tr><th>Staff</th><th>Rotation Type</th><th>Location</th><th>Start</th><th>End</th><th>Supervisor</th><th>Status</th></tr></thead>
                <tbody>
                <?php if (empty($rotation_schedules)): ?>
                <tr><td colspan="7" class="text-center text-muted py-3">No active rotations scheduled.</td></tr>
                <?php else: foreach ($rotation_schedules as $cr): ?>
                <tr>
                    <td><?= htmlspecialchars($cr['full_name']) ?></td>
                    <td><?= htmlspecialchars(ucfirst(str_replace('_',' ',$cr['rotation_type'] ?? 'General'))) ?></td>
                    <td><small><?= htmlspecialchars($cr['location'] ?? '-') ?></small></td>
                    <td><small><?= hrFormatDate($cr['start_date']) ?></small></td>
                    <td><small><?= hrFormatDate($cr['end_date']) ?></small></td>
                    <td><small><?= htmlspecialchars($cr['supervisor_name'] ?? '-') ?></small></td>
                    <td><?= hrStatusBadge($cr['status']) ?></td>
                </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════ -->
<!-- SECTION: COMMUNICATION                              -->
<!-- ═══════════════════════════════════════════════════ -->
<div class="hr-section <?= $active_section==='communication'?'active':'' ?>" id="section-communication">
    <div class="content-card">
        <h2><i class="fas fa-bullhorn text-primary"></i>HR Communication Center</h2>
        <div class="row g-3">
            <div class="col-md-3">
                <div class="card border p-3 text-center h-100">
                    <i class="fas fa-bullhorn fa-2x text-warning mb-2"></i>
                    <h6>Announcements</h6>
                    <a href="../news.php" class="btn btn-outline-warning btn-sm mt-2">Create</a>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border p-3 text-center h-100">
                    <i class="fas fa-bell fa-2x text-info mb-2"></i>
                    <h6>Staff Notices</h6>
                    <a href="../notifications.php" class="btn btn-outline-info btn-sm mt-2">Send</a>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border p-3 text-center h-100">
                    <i class="fas fa-file-alt fa-2x text-secondary mb-2"></i>
                    <h6>Policy Updates</h6>
                    <a href="?section=communication" class="btn btn-outline-secondary btn-sm mt-2">Update</a>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border p-3 text-center h-100">
                    <i class="fas fa-comments fa-2x text-success mb-2"></i>
                    <h6>Internal Messaging</h6>
                    <a href="../messaging.php" class="btn btn-outline-success btn-sm mt-2">Message</a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════ -->
<!-- SECTION: REPORTS                                    -->
<!-- ═══════════════════════════════════════════════════ -->
<div class="hr-section <?= $active_section==='reports'?'active':'' ?>" id="section-reports">
    <div class="content-card">
        <h2><i class="fas fa-chart-bar text-primary"></i>HR Reports & Analytics</h2>
        <div class="row g-3">
            <div class="col-md-4">
                <div class="card border p-3 text-center h-100">
                    <i class="fas fa-users fa-2x text-primary mb-2"></i>
                    <h6>Staff Reports</h6>
                    <p class="text-muted small">Staff demographics, turnover</p>
                    <a href="?section=reports" class="btn btn-outline-primary btn-sm">Generate</a>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border p-3 text-center h-100">
                    <i class="fas fa-calendar-check fa-2x text-success mb-2"></i>
                    <h6>Attendance Reports</h6>
                    <p class="text-muted small">Daily/monthly attendance</p>
                    <a href="?section=reports" class="btn btn-outline-success btn-sm">Generate</a>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border p-3 text-center h-100">
                    <i class="fas fa-calendar-alt fa-2x text-warning mb-2"></i>
                    <h6>Leave Reports</h6>
                    <p class="text-muted small">Leave utilization & balances</p>
                    <a href="?section=reports" class="btn btn-outline-warning btn-sm">Generate</a>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border p-3 text-center h-100">
                    <i class="fas fa-chart-line fa-2x text-info mb-2"></i>
                    <h6>Performance Reports</h6>
                    <p class="text-muted small">Appraisal & KPI summaries</p>
                    <a href="?section=reports" class="btn btn-outline-info btn-sm">Generate</a>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border p-3 text-center h-100">
                    <i class="fas fa-certificate fa-2x text-danger mb-2"></i>
                    <h6>License Compliance</h6>
                    <p class="text-muted small">License status & expiry</p>
                    <a href="?section=licenses" class="btn btn-outline-danger btn-sm">View</a>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border p-3 text-center h-100">
                    <i class="fas fa-file-export fa-2x text-secondary mb-2"></i>
                    <h6>Export Data</h6>
                    <p class="text-muted small">CSV/Excel exports</p>
                    <a href="?section=reports" class="btn btn-outline-secondary btn-sm">Export</a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════ -->
<!-- SECTION: SETTINGS                                   -->
<!-- ═══════════════════════════════════════════════════ -->
<div class="hr-section <?= $active_section==='settings'?'active':'' ?>" id="section-settings">
    <div class="row g-3">
        <div class="col-md-4">
            <div class="content-card">
                <h2><i class="fas fa-building text-primary"></i>Departments <span class="badge bg-primary badge-count"><?= count($departments) ?></span></h2>
                <form method="POST" class="mb-3">
                    <input type="hidden" name="action" value="add_department">
                    <div class="input-group input-group-sm">
                        <input type="text" name="department_name" class="form-control" placeholder="Department name" required>
                        <button class="btn btn-primary" type="submit"><i class="fas fa-plus"></i></button>
                    </div>
                </form>
                <div class="list-group list-group-flush small">
                    <?php foreach ($departments as $d): ?>
                    <div class="list-group-item d-flex justify-content-between align-items-center py-2">
                        <?= htmlspecialchars($d['department_name']) ?>
                        <span class="badge bg-secondary rounded-pill">ID:<?= $d['id'] ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="content-card">
                <h2><i class="fas fa-user-tag text-info"></i>Roles <span class="badge bg-info badge-count"><?= count($roles_list) ?></span></h2>
                <form method="POST" class="mb-3">
                    <input type="hidden" name="action" value="add_role">
                    <div class="input-group input-group-sm">
                        <input type="text" name="role_name" class="form-control" placeholder="Role name" required>
                        <button class="btn btn-info" type="submit"><i class="fas fa-plus"></i></button>
                    </div>
                </form>
                <div class="list-group list-group-flush small" style="max-height:300px;overflow-y:auto;">
                    <?php foreach ($roles_list as $r): ?>
                    <div class="list-group-item d-flex justify-content-between align-items-center py-1">
                        <?= htmlspecialchars($r['role_name']) ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="content-card">
                <h2><i class="fas fa-calendar-alt text-success"></i>Leave Types <span class="badge bg-success badge-count"><?= count($leave_types) ?></span></h2>
                <form method="POST" class="mb-3">
                    <input type="hidden" name="action" value="add_leave_type">
                    <div class="row g-1">
                        <div class="col">
                            <input type="text" name="leave_name" class="form-control form-control-sm" placeholder="Leave type" required>
                        </div>
                        <div class="col-4">
                            <label class="form-label">Days/Year</label>
                            <input type="number" name="days_per_year" class="form-control form-control-sm" placeholder="Days" value="30">
                        </div>
                        <div class="col-2">
                            <button class="btn btn-success btn-sm w-100" type="submit"><i class="fas fa-plus"></i></button>
                        </div>
                    </div>
                </form>
                <div class="list-group list-group-flush small">
                    <?php foreach ($leave_types as $lt): ?>
                    <div class="list-group-item d-flex justify-content-between align-items-center py-1">
                        <?= htmlspecialchars($lt['leave_type_name']) ?>
                        <span class="badge bg-secondary"><?= $lt['days_per_year'] ?> days</span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

</div><!-- /.px-3 -->
</div><!-- /.hr-dashboard -->
</div><!-- /.page-wrap -->

<!-- ADD STAFF MODAL -->
<div class="modal fade modal-hr" id="addStaffModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <form method="POST" class="modal-content">
      <input type="hidden" name="action" value="add_staff">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fas fa-user-plus me-2"></i>Add New Staff Member</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="row g-3">
          <div class="col-md-6"><label class="form-label">Full Name *</label><input type="text" name="full_name" class="form-control form-control-sm" required></div>
          <div class="col-md-6"><label class="form-label">Email *</label><input type="email" name="email" class="form-control form-control-sm" required></div>
          <div class="col-md-4"><label class="form-label">Phone</label><input type="text" name="phone" class="form-control form-control-sm"></div>
          <div class="col-md-4"><label class="form-label">Gender</label>
            <select name="gender" class="form-select form-select-sm">
              <option value="">Select...</option>
              <?php foreach (hrGetGenderOptions() as $g): ?>
              <option value="<?= $g ?>"><?= $g ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-4"><label class="form-label">Employment Type</label>
            <select name="employment_type" class="form-select form-select-sm">
              <?php foreach (hrGetEmploymentTypes() as $et): ?>
              <option value="<?= $et ?>"><?= ucfirst($et) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-6"><label class="form-label">Department</label>
            <select name="department_id" class="form-select form-select-sm">
              <option value="0">Select department...</option>
              <?php foreach ($departments as $d): ?>
              <option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['department_name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-6"><label class="form-label">Role</label>
            <select name="role_id" class="form-select form-select-sm">
              <option value="0">Select role...</option>
              <?php foreach ($roles_list as $r): ?>
              <option value="<?= $r['id'] ?>"><?= htmlspecialchars($r['role_name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-6"><label class="form-label">Position/Title</label><input type="text" name="position" class="form-control form-control-sm"></div>
          <div class="col-md-6"><label class="form-label">Hire Date</label><input type="date" name="hire_date" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>"></div>
          <div class="col-12"><small class="text-muted">Default password: <code>isnm2026</code>. Staff must change on first login.</small></div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-save me-1"></i>Add Staff</button>
      </div>
    </form>
  </div>
</div>

<!-- VIEW STAFF MODAL -->
<div class="modal fade modal-hr" id="viewStaffModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fas fa-user me-2"></i>Staff Details</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="row g-3" id="viewStaffContent">
          <div class="col-12 text-center text-muted py-4">Loading...</div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<!-- EDIT STAFF MODAL -->
<div class="modal fade modal-hr" id="editStaffModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <form method="POST" class="modal-content">
      <input type="hidden" name="action" value="edit_staff">
      <input type="hidden" name="staff_id" id="edit_staff_id" value="0">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fas fa-user-edit me-2"></i>Edit Staff Member</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="row g-3">
          <div class="col-md-6"><label class="form-label">Full Name *</label><input type="text" name="full_name" id="edit_full_name" class="form-control form-control-sm" required></div>
          <div class="col-md-6"><label class="form-label">Email *</label><input type="email" name="email" id="edit_email" class="form-control form-control-sm" required></div>
          <div class="col-md-4"><label class="form-label">Phone</label><input type="text" name="phone" id="edit_phone" class="form-control form-control-sm"></div>
          <div class="col-md-4"><label class="form-label">Gender</label>
            <select name="gender" id="edit_gender" class="form-select form-select-sm">
              <option value="">Select...</option>
              <?php foreach (hrGetGenderOptions() as $g): ?>
              <option value="<?= $g ?>"><?= $g ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-4"><label class="form-label">Employment Type</label>
            <select name="employment_type" id="edit_employment_type" class="form-select form-select-sm">
              <?php foreach (hrGetEmploymentTypes() as $et): ?>
              <option value="<?= $et ?>"><?= ucfirst($et) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-6"><label class="form-label">Department</label>
            <select name="department_id" id="edit_department_id" class="form-select form-select-sm">
              <option value="0">Select department...</option>
              <?php foreach ($departments as $d): ?>
              <option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['department_name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-6"><label class="form-label">Role</label>
            <select name="role_id" id="edit_role_id" class="form-select form-select-sm">
              <option value="0">Select role...</option>
              <?php foreach ($roles_list as $r): ?>
              <option value="<?= $r['id'] ?>"><?= htmlspecialchars($r['role_name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-4"><label class="form-label">Position/Title</label><input type="text" name="position" id="edit_position" class="form-control form-control-sm"></div>
          <div class="col-md-4"><label class="form-label">Hire Date</label><input type="date" name="hire_date" id="edit_hire_date" class="form-control form-control-sm"></div>
          <div class="col-md-4"><label class="form-label">Status</label>
            <select name="status" id="edit_status" class="form-select form-select-sm">
              <?php foreach (hrGetStaffStatuses() as $st): ?>
              <option value="<?= $st ?>"><?= ucfirst($st) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-save me-1"></i>Save Changes</button>
      </div>
    </form>
  </div>
</div>

<script>
// Staff data loaded from PHP
var staffData = <?= json_encode($staff_list) ?>;

function getStaffById(id) {
    return staffData.find(function(s) { return parseInt(s.id) === parseInt(id); });
}

function viewStaff(id) {
    var s = getStaffById(id);
    if (!s) return;
    var html = '<div class="row">';
    html += '<div class="col-md-6"><table class="table table-sm table-hr">';
    html += '<tr><th>Staff ID</th><td><code>' + (s.staff_id || 'N/A') + '</code></td></tr>';
    html += '<tr><th>Full Name</th><td><strong>' + escapeHtml(s.full_name) + '</strong></td></tr>';
    html += '<tr><th>Email</th><td>' + escapeHtml(s.email || '-') + '</td></tr>';
    html += '<tr><th>Phone</th><td>' + escapeHtml(s.phone || '-') + '</td></tr>';
    html += '<tr><th>Gender</th><td>' + escapeHtml(s.gender || '-') + '</td></tr>';
    html += '<tr><th>Department</th><td>' + escapeHtml(s.department_name || s.department || '-') + '</td></tr>';
    html += '<tr><th>Role</th><td>' + escapeHtml(s.role_name || s.position || '-') + '</td></tr>';
    html += '<tr><th>Position</th><td>' + escapeHtml(s.position || '-') + '</td></tr>';
    html += '<tr><th>Employment Type</th><td>' + escapeHtml(s.employment_type ? ucfirst(s.employment_type) : '-') + '</td></tr>';
    html += '<tr><th>Status</th><td>' + (s.status === 'active' ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-secondary">' + ucfirst(s.status) + '</span>') + '</td></tr>';
    html += '<tr><th>Hire Date</th><td>' + (s.hire_date ? formatDate(s.hire_date) : '-') + '</td></tr>';
    html += '<tr><th>Created</th><td>' + (s.created_at ? formatDate(s.created_at) : '-') + '</td></tr>';
    html += '</table></div></div>';
    document.getElementById('viewStaffContent').innerHTML = html;
    new bootstrap.Modal(document.getElementById('viewStaffModal')).show();
}

function editStaff(id) {
    var s = getStaffById(id);
    if (!s) return;
    document.getElementById('edit_staff_id').value = s.id;
    document.getElementById('edit_full_name').value = s.full_name || '';
    document.getElementById('edit_email').value = s.email || '';
    document.getElementById('edit_phone').value = s.phone || '';
    document.getElementById('edit_gender').value = s.gender || '';
    document.getElementById('edit_employment_type').value = s.employment_type || 'permanent';
    document.getElementById('edit_department_id').value = s.department_id || 0;
    document.getElementById('edit_role_id').value = s.role_id || 0;
    document.getElementById('edit_position').value = s.position || '';
    document.getElementById('edit_hire_date').value = s.hire_date || '';
    document.getElementById('edit_status').value = s.status || 'active';
    new bootstrap.Modal(document.getElementById('editStaffModal')).show();
}

function escapeHtml(str) {
    if (!str) return '';
    var div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}

function ucfirst(str) {
    if (!str) return '';
    return str.charAt(0).toUpperCase() + str.slice(1);
}

function formatDate(dateStr) {
    if (!dateStr) return '-';
    var d = new Date(dateStr);
    var months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
    return d.getDate() + ' ' + months[d.getMonth()] + ' ' + d.getFullYear();
}

function viewPayslip() {
    var staffId = document.getElementById('payslipStaffSelect').value;
    var runId = document.getElementById('payslipRunSelect').value;
    if (!staffId || !runId) { alert('Please select both staff and period.'); return; }
    var s = getStaffById(parseInt(staffId));
    if (!s) { alert('Staff not found.'); return; }
    var html = '<div class="alert alert-info"><i class="fas fa-file-invoice me-2"></i>Payslip for <strong>' + escapeHtml(s.full_name) + '</strong> (Run #' + runId + ')</div>';
    html += '<p class="text-muted">Detailed payslip view will be shown here. Payment reference numbers are generated when payslips are processed.</p>';
    var modal = new bootstrap.Modal(document.getElementById('viewStaffModal'));
    document.getElementById('viewStaffContent').innerHTML = html;
    document.querySelector('#viewStaffModal .modal-title').innerHTML = '<i class="fas fa-file-invoice me-2"></i>Payslip';
    modal.show();
    document.querySelector('#viewStaffModal .modal-title').innerHTML = '<i class="fas fa-user me-2"></i>Staff Details';
}

document.addEventListener('DOMContentLoaded', function() {
    var urlParams = new URLSearchParams(window.location.search);
    var activeSection = urlParams.get('section') || 'overview';
    var tabs = document.querySelectorAll('.section-tab');
    tabs.forEach(function(tab) {
        tab.addEventListener('click', function(e) {
            tabs.forEach(function(t) { t.classList.remove('active'); });
            this.classList.add('active');
        });
    });
});
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
</body>
</html>
