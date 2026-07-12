<?php
/**
 * HR Module Helper Functions for ISNM ERP
 * Provides reusable HR utilities across all HR pages.
 */

if (!function_exists('getHrConnection')) {
    function getHrConnection() {
        return getStaffConnection();
    }
}

if (!function_exists('hrGetStaff')) {
    function hrGetStaff($conn, $id = null, $limit = 100) {
        $sql = "SELECT s.*, COALESCE(r.role_name, sr.role_name) as role_name, d.department_name 
                FROM staff s 
                LEFT JOIN roles r ON s.role_id = r.id 
                LEFT JOIN staff_roles sr ON s.role_id = sr.id 
                LEFT JOIN departments d ON s.department_id = d.id";
        $params = [];
        $types = '';

        if ($id) {
            $sql .= " WHERE s.id = ?";
            $params[] = $id;
            $types = 'i';
        } else {
            $sql .= " ORDER BY s.full_name LIMIT ?";
            $params[] = $limit;
            $types = 'i';
        }

        $stmt = $conn->prepare($sql);
        if ($stmt) {
            if ($params) $stmt->bind_param($types, ...$params);
            if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
            $result = $stmt->get_result();
            return $id ? $result->fetch_assoc() : $result->fetch_all(MYSQLI_ASSOC);
        }
        return $id ? null : [];
    }
}

if (!function_exists('hrGetDepartments')) {
    function hrGetDepartments($conn) {
        $result = $conn->query("SELECT * FROM departments ORDER BY department_name");
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }
}

if (!function_exists('hrGetRoles')) {
    function hrGetRoles($conn) {
        $result = $conn->query("SELECT * FROM roles ORDER BY role_name");
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }
}

if (!function_exists('hrGetLeaveTypes')) {
    function hrGetLeaveTypes($conn) {
        $result = $conn->query("SELECT * FROM leave_types ORDER BY leave_type_name");
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }
}

if (!function_exists('hrGetStats')) {
    function hrGetStats($conn) {
        $stats = [
            'total_staff' => 0,
            'active_staff' => 0,
            'inactive_staff' => 0,
            'suspended_staff' => 0,
            'retired_staff' => 0,
            'pending_leave' => 0,
            'attendance_today' => 0,
            'late_today' => 0,
            'absent_today' => 0,
            'expired_licenses' => 0,
            'expiring_licenses' => 0,
            'open_cases' => 0,
            'open_vacancies' => 0,
            'pending_applications' => 0,
            'pending_appraisals' => 0,
            'ongoing_trainings' => 0,
            'departments' => 0,
        ];

        if (!$conn) return $stats;

        $queries = [
            'total_staff' => "SELECT COUNT(*) c FROM staff",
            'active_staff' => "SELECT COUNT(*) c FROM staff WHERE status='active'",
            'inactive_staff' => "SELECT COUNT(*) c FROM staff WHERE status='inactive'",
            'suspended_staff' => "SELECT COUNT(*) c FROM staff WHERE status='suspended'",
            'retired_staff' => "SELECT COUNT(*) c FROM staff WHERE status='retired'",
            'pending_leave' => "SELECT COUNT(*) c FROM leave_requests WHERE status='pending'",
            'attendance_today' => "SELECT COUNT(*) c FROM attendance WHERE attendance_date=CURDATE() AND attendance_status='present'",
            'late_today' => "SELECT COUNT(*) c FROM attendance WHERE attendance_date=CURDATE() AND attendance_status='late'",
            'absent_today' => "SELECT COUNT(*) c FROM attendance WHERE attendance_date=CURDATE() AND attendance_status='absent'",
            'expired_licenses' => "SELECT COUNT(*) c FROM staff_licenses WHERE status='expired'",
            'expiring_licenses' => "SELECT COUNT(*) c FROM staff_licenses WHERE expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 60 DAY) AND status='valid'",
            'open_cases' => "SELECT COUNT(*) c FROM disciplinary_cases WHERE status IN ('open','investigating')",
            'open_vacancies' => "SELECT COUNT(*) c FROM job_vacancies WHERE status='open'",
            'pending_applications' => "SELECT COUNT(*) c FROM job_applications WHERE application_status='received'",
            'pending_appraisals' => "SELECT COUNT(*) c FROM performance_reviews WHERE status='draft'",
            'ongoing_trainings' => "SELECT COUNT(*) c FROM staff_trainings WHERE status='scheduled'",
            'departments' => "SELECT COUNT(*) c FROM departments",
        ];

        foreach ($queries as $key => $sql) {
            $r = $conn->query($sql);
            if ($r) $stats[$key] = (int)$r->fetch_assoc()['c'];
        }

        return $stats;
    }
}

if (!function_exists('hrLogActivity')) {
    function hrLogActivity($conn, $staff_id, $action, $module, $description) {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $stmt = $conn->prepare("INSERT INTO hr_activity_log (staff_id, action, module, description, ip_address) VALUES (?, ?, ?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param('issss', $staff_id, $action, $module, $description, $ip);
            if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
        }
    }
}

if (!function_exists('hrGetGenderOptions')) {
    function hrGetGenderOptions() {
        return ['Male', 'Female', 'Other'];
    }
}

if (!function_exists('hrGetEmploymentTypes')) {
    function hrGetEmploymentTypes() {
        return ['permanent', 'contract', 'part-time', 'internship', 'temporary'];
    }
}

if (!function_exists('hrGetStaffStatuses')) {
    function hrGetStaffStatuses() {
        return ['active', 'inactive', 'suspended', 'retired'];
    }
}

if (!function_exists('hrGetAttendanceStatuses')) {
    function hrGetAttendanceStatuses() {
        return ['present', 'late', 'absent', 'half-day', 'leave'];
    }
}

if (!function_exists('hrFormatDate')) {
    function hrFormatDate($date) {
        if (!$date || $date === '0000-00-00') return '-';
        return date('d M Y', strtotime($date));
    }
}

if (!function_exists('hrTimeAgo')) {
    function hrTimeAgo($datetime) {
        if (!$datetime) return '-';
        $timestamp = strtotime($datetime);
        $diff = time() - $timestamp;
        if ($diff < 60) return 'just now';
        if ($diff < 3600) return floor($diff / 60) . 'm ago';
        if ($diff < 86400) return floor($diff / 3600) . 'h ago';
        if ($diff < 604800) return floor($diff / 86400) . 'd ago';
        return date('d M', $timestamp);
    }
}

if (!function_exists('hrStatusBadge')) {
    function hrStatusBadge($status) {
        $map = [
            'active' => 'bg-success',
            'inactive' => 'bg-secondary',
            'suspended' => 'bg-warning text-dark',
            'retired' => 'bg-dark',
            'pending' => 'bg-warning text-dark',
            'approved' => 'bg-success',
            'rejected' => 'bg-danger',
            'cancelled' => 'bg-secondary',
            'valid' => 'bg-success',
            'expired' => 'bg-danger',
            'revoked' => 'bg-dark',
            'pending_renewal' => 'bg-warning text-dark',
            'present' => 'bg-success',
            'late' => 'bg-warning text-dark',
            'absent' => 'bg-danger',
            'half-day' => 'bg-info',
            'open' => 'bg-warning text-dark',
            'investigating' => 'bg-info',
            'resolved' => 'bg-success',
            'dismissed' => 'bg-secondary',
            'draft' => 'bg-secondary',
            'scheduled' => 'bg-info',
            'completed' => 'bg-success',
            'submitted' => 'bg-primary',
            'acknowledged' => 'bg-info',
            'received' => 'bg-info',
            'reviewing' => 'bg-warning text-dark',
            'shortlisted' => 'bg-primary',
            'interviewed' => 'bg-purple',
            'offered' => 'bg-success',
            'hired' => 'bg-success',
            'terminated' => 'bg-danger',
        ];
        $class = $map[strtolower($status)] ?? 'bg-secondary';
        return '<span class="badge ' . $class . '">' . htmlspecialchars(ucfirst($status)) . '</span>';
    }
}

if (!function_exists('hrGetLeaveBalances')) {
    function hrGetLeaveBalances($conn, $staff_id) {
        $stmt = $conn->prepare("
            SELECT lt.id, lt.leave_type_name, lt.days_per_year,
                   COALESCE(lb.used_days, 0) as used_days,
                   (COALESCE(lb.total_days, lt.days_per_year) - COALESCE(lb.used_days, 0)) as remaining_days
            FROM leave_types lt
            LEFT JOIN leave_balances lb ON lt.id = lb.leave_type_id AND lb.staff_id = ? AND lb.year = YEAR(CURDATE())
            ORDER BY lt.leave_type_name
        ");
        if ($stmt) {
            $stmt->bind_param('i', $staff_id);
            if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        }
        return [];
    }
}

if (!function_exists('hrCheckLicenseCompliance')) {
    function hrCheckLicenseCompliance($conn, $staff_id) {
        $stmt = $conn->prepare("
            SELECT license_type, license_number, expiry_date, status 
            FROM staff_licenses 
            WHERE staff_id = ? AND status IN ('valid', 'expired', 'pending_renewal')
            ORDER BY expiry_date DESC
        ");
        if ($stmt) {
            $stmt->bind_param('i', $staff_id);
            if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        }
        return [];
    }
}

if (!function_exists('hrCanAssignClinical')) {
    function hrCanAssignClinical($conn, $staff_id) {
        $stmt = $conn->prepare("
            SELECT COUNT(*) c FROM staff_licenses 
            WHERE staff_id = ? AND status = 'valid' AND expiry_date >= CURDATE()
        ");
        if ($stmt) {
            $stmt->bind_param('i', $staff_id);
            if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
            $count = (int)$stmt->get_result()->fetch_assoc()['c'];
            return $count > 0;
        }
        return false;
    }
}
