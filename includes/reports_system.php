<?php
/**
 * ISNM Reports System
 * Generates summary and analytical reports from the synchronized student database.
 */

require_once __DIR__ . '/../config/database.php';

if (!function_exists('getStudentReports')) {
    /**
     * Get comprehensive student reports.
     */
    function getStudentReports(): array {
        $conn = getStudentsConnection();
        if (!$conn) return ['error' => 'Database connection failed'];

        $reports = [];

        // Total students by status
        $r = $conn->query("SELECT status, COUNT(*) as count FROM students WHERE status != 'deleted' GROUP BY status ORDER BY count DESC");
        $reports['by_status'] = $r ? isnm_fetch_all($r) : [];

        // Students by program
        $r = $conn->query("SELECT program, COUNT(*) as count FROM students WHERE status = 'Active' AND program != '' GROUP BY program ORDER BY count DESC");
        $reports['by_program'] = $r ? isnm_fetch_all($r) : [];

        // Students by level
        $r = $conn->query("SELECT level, COUNT(*) as count FROM students WHERE status = 'Active' AND level != '' GROUP BY level ORDER BY level");
        $reports['by_level'] = $r ? isnm_fetch_all($r) : [];

        // Students by gender
        $r = $conn->query("SELECT gender, COUNT(*) as count FROM students WHERE status = 'Active' GROUP BY gender");
        $reports['by_gender'] = $r ? isnm_fetch_all($r) : [];

        // Students by district (top 20)
        $r = $conn->query("SELECT district, COUNT(*) as count FROM students WHERE status = 'Active' AND district != '' GROUP BY district ORDER BY count DESC LIMIT 20");
        $reports['by_district'] = $r ? isnm_fetch_all($r) : [];

        // Students by stream
        $r = $conn->query("SELECT stream, COUNT(*) as count FROM students WHERE status = 'Active' AND stream != '' GROUP BY stream ORDER BY count DESC");
        $reports['by_stream'] = $r ? isnm_fetch_all($r) : [];

        // Students by intake period
        $r = $conn->query("SELECT intake_year, intake_period, COUNT(*) as count FROM students WHERE status != 'deleted' GROUP BY intake_year, intake_period ORDER BY intake_year DESC, intake_period");
        $reports['by_intake'] = $r ? isnm_fetch_all($r) : [];

        // New registrations this year vs last year
        $year = date('Y');
        $r = $conn->query("SELECT COUNT(*) as count FROM students WHERE YEAR(created_at) = $year");
        $reports['new_this_year'] = $r ? (int)$r->fetch_assoc()['count'] : 0;
        $r = $conn->query("SELECT COUNT(*) as count FROM students WHERE YEAR(created_at) = " . ($year - 1));
        $reports['new_last_year'] = $r ? (int)$r->fetch_assoc()['count'] : 0;

        // Active students count
        $r = $conn->query("SELECT COUNT(*) as count FROM students WHERE status = 'Active'");
        $reports['active_count'] = $r ? (int)$r->fetch_assoc()['count'] : 0;

        // Total students count
        $r = $conn->query("SELECT COUNT(*) as count FROM students WHERE status != 'deleted'");
        $reports['total_count'] = $r ? (int)$r->fetch_assoc()['count'] : 0;

        return $reports;
    }
}

if (!function_exists('getFinanceReports')) {
    /**
     * Get financial reports.
     */
    function getFinanceReports(array $dateRange = []): array {
        $conn = getStudentsConnection();
        if (!$conn) return ['error' => 'Database connection failed'];

        $reports = [];

        // Total payments
        $r = $conn->query("SELECT COUNT(*) as count, COALESCE(SUM(amount_received),0) as total FROM payments WHERE status = 'completed'");
        $reports['total_payments'] = $r ? $r->fetch_assoc() : ['count' => 0, 'total' => 0];

        // Payments by method
        $r = $conn->query("SELECT payment_method, COUNT(*) as count, COALESCE(SUM(amount_received),0) as total FROM payments WHERE status = 'completed' GROUP BY payment_method ORDER BY total DESC");
        $reports['by_method'] = $r ? isnm_fetch_all($r) : [];

        // Payments by fee type
        $r = $conn->query("SELECT fee_type, COUNT(*) as count, COALESCE(SUM(amount),0) as total FROM payment_history WHERE status = 'completed' GROUP BY fee_type ORDER BY total DESC");
        $reports['by_fee_type'] = $r ? isnm_fetch_all($r) : [];

        // Monthly payments (last 12 months)
        $startDate = date('Y-m-01', strtotime('-11 months'));
        $r = $conn->query("SELECT DATE_FORMAT(payment_date, '%Y-%m') as month, COUNT(*) as count, COALESCE(SUM(amount_received),0) as total FROM payments WHERE status = 'completed' AND payment_date >= '$startDate' GROUP BY month ORDER BY month");
        $reports['monthly'] = $r ? isnm_fetch_all($r) : [];

        // Total expenses
        $r = $conn->query("SELECT COUNT(*) as count, COALESCE(SUM(amount),0) as total FROM expenses WHERE status IN ('approved','paid')");
        $reports['total_expenses'] = $r ? $r->fetch_assoc() : ['count' => 0, 'total' => 0];

        // Expenses by category
        $r = $conn->query("SELECT category, COUNT(*) as count, COALESCE(SUM(amount),0) as total FROM expenses WHERE status IN ('approved','paid') GROUP BY category ORDER BY total DESC");
        $reports['expenses_by_category'] = $r ? isnm_fetch_all($r) : [];

        // Fee status summary
        $r = $conn->query("SELECT status, COUNT(*) as count, COALESCE(SUM(amount),0) as total, COALESCE(SUM(amount_paid),0) as paid FROM student_fee_tracking GROUP BY status");
        $reports['fee_status'] = $r ? isnm_fetch_all($r) : [];

        // Pending fees total
        $r = $conn->query("SELECT COALESCE(SUM(balance),0) as total FROM student_fee_tracking WHERE status != 'paid'");
        $reports['pending_fees_total'] = $r ? (float)$r->fetch_assoc()['total'] : 0;

        return $reports;
    }
}

if (!function_exists('getRequirementsReports')) {
    /**
     * Get admission requirements completion reports.
     */
    function getRequirementsReports(): array {
        $conn = getStudentsConnection();
        if (!$conn) return ['error' => 'Database connection failed'];

        $reports = [];

        // Requirements status summary
        $r = $conn->query("SELECT srs.status, COUNT(*) as count FROM student_requirements_status srs GROUP BY srs.status ORDER BY count DESC");
        $reports['by_status'] = $r ? isnm_fetch_all($r) : [];

        // Students with all requirements verified
        $r = $conn->query("SELECT COUNT(DISTINCT student_id) as count FROM student_requirements_status WHERE status = 'Verified'");
        $reports['fully_verified'] = $r ? (int)$r->fetch_assoc()['count'] : 0;

        // Students with pending requirements
        $r = $conn->query("SELECT COUNT(DISTINCT student_id) as count FROM student_requirements_status WHERE status IN ('Not Submitted','Pending')");
        $reports['pending_count'] = $r ? (int)$r->fetch_assoc()['count'] : 0;

        // Requirements by type
        $r = $conn->query("SELECT ar.requirement_name, ar.type, ar.is_mandatory, COUNT(srs.id) as total, SUM(CASE WHEN srs.status = 'Verified' THEN 1 ELSE 0 END) as verified FROM admission_requirements ar LEFT JOIN student_requirements_status srs ON ar.id = srs.requirement_id GROUP BY ar.id, ar.requirement_name, ar.type, ar.is_mandatory ORDER BY ar.display_order");
        $reports['by_requirement'] = $r ? isnm_fetch_all($r) : [];

        return $reports;
    }
}

if (!function_exists('getStudentProfileReport')) {
    /**
     * Get a full report for a single student.
     */
    function getStudentProfileReport(int $studentId): array {
        $conn = getStudentsConnection();
        if (!$conn) return ['error' => 'Database connection failed'];

        $report = [];

        // Student basic info
        $stmt = $conn->prepare("SELECT * FROM students WHERE id = ?");
        $stmt->bind_param('i', $studentId);
        $stmt->execute();
        $r = $stmt->get_result();
        $report['student'] = $r ? $r->fetch_assoc() : null;
        $stmt->close();

        if (!$report['student']) return ['error' => 'Student not found'];

        // Payment history
        $stmt = $conn->prepare("SELECT * FROM payments WHERE student_id = ? ORDER BY payment_date DESC");
        $stmt->bind_param('i', $studentId);
        $stmt->execute();
        $r = $stmt->get_result();
        $report['payments'] = $r ? isnm_fetch_all($r) : [];
        $stmt->close();

        // Total paid
        $totalPaid = 0;
        foreach ($report['payments'] as $p) {
            if (($p['status'] ?? '') === 'completed') {
                $totalPaid += floatval($p['amount_received'] ?? 0);
            }
        }
        $report['total_paid'] = $totalPaid;

        // Fee tracking
        $stmt = $conn->prepare("SELECT * FROM student_fee_tracking WHERE student_id = ? ORDER BY id");
        $stmt->bind_param('i', $studentId);
        $stmt->execute();
        $r = $stmt->get_result();
        $report['fee_tracking'] = $r ? isnm_fetch_all($r) : [];
        $stmt->close();

        // Requirements
        $stmt = $conn->prepare("SELECT srs.*, ar.requirement_name, ar.type as requirement_type, ar.is_mandatory FROM student_requirements_status srs JOIN admission_requirements ar ON srs.requirement_id = ar.id WHERE srs.student_id = ? ORDER BY ar.display_order");
        $stmt->bind_param('i', $studentId);
        $stmt->execute();
        $r = $stmt->get_result();
        $report['requirements'] = $r ? isnm_fetch_all($r) : [];
        $stmt->close();

        // Requirement stats
        $verified = 0;
        $pending = 0;
        $total = count($report['requirements']);
        foreach ($report['requirements'] as $req) {
            if (in_array($req['status'] ?? '', ['Verified', 'Received'])) $verified++;
            elseif (in_array($req['status'] ?? '', ['Not Submitted', 'Pending'])) $pending++;
        }
        $report['req_verified'] = $verified;
        $report['req_pending'] = $pending;
        $report['req_total'] = $total;

        // Documents
        $stmt = $conn->prepare("SELECT * FROM student_documents WHERE student_id = ? AND status = 'Active' ORDER BY created_at DESC");
        $stmt->bind_param('i', $studentId);
        $stmt->execute();
        $r = $stmt->get_result();
        $report['documents'] = $r ? isnm_fetch_all($r) : [];
        $stmt->close();

        // Status history
        $stmt = $conn->prepare("SELECT * FROM student_status_history WHERE student_id = ? ORDER BY created_at DESC LIMIT 20");
        $stmt->bind_param('i', $studentId);
        $stmt->execute();
        $r = $stmt->get_result();
        $report['status_history'] = $r ? isnm_fetch_all($r) : [];
        $stmt->close();

        return $report;
    }
}

if (!function_exists('getAcademicReports')) {
    /**
     * Get academic performance reports.
     */
    function getAcademicReports(): array {
        $conn = getStudentsConnection();
        if (!$conn) return ['error' => 'Database connection failed'];

        $reports = [];

        // Academic standings
        $r = $conn->query("SELECT academic_standing, COUNT(*) as count FROM student_academic_profiles GROUP BY academic_standing ORDER BY count DESC");
        $reports['by_standing'] = $r ? isnm_fetch_all($r) : [];

        // Average GPA
        $r = $conn->query("SELECT AVG(gpa) as avg_gpa, MIN(gpa) as min_gpa, MAX(gpa) as max_gpa FROM student_academic_profiles WHERE gpa IS NOT NULL");
        $reports['gpa_stats'] = $r ? $r->fetch_assoc() : ['avg_gpa' => 0, 'min_gpa' => 0, 'max_gpa' => 0];

        // Students by program and year
        $r = $conn->query("SELECT s.program, sap.current_year, COUNT(*) as count FROM students s JOIN student_academic_profiles sap ON s.id = sap.student_id WHERE s.status = 'Active' GROUP BY s.program, sap.current_year ORDER BY s.program, sap.current_year");
        $reports['by_program_year'] = $r ? isnm_fetch_all($r) : [];

        return $reports;
    }
}

if (!function_exists('getDashboardAnalytics')) {
    /**
     * Get dashboard analytics for any role.
     */
    function getDashboardAnalytics(): array {
        $conn = getStudentsConnection();
        if (!$conn) return ['error' => 'Database connection failed'];

        $analytics = [];

        // Total active students
        $r = $conn->query("SELECT COUNT(*) as c FROM students WHERE status = 'Active'");
        $analytics['active_students'] = $r ? (int)$r->fetch_assoc()['c'] : 0;

        // New this month
        $monthStart = date('Y-m-01');
        $r = $conn->query("SELECT COUNT(*) as c FROM students WHERE created_at >= '$monthStart'");
        $analytics['new_this_month'] = $r ? (int)$r->fetch_assoc()['c'] : 0;

        // Total payments this month
        $r = $conn->query("SELECT COUNT(*) as c, COALESCE(SUM(amount_received),0) as total FROM payments WHERE status = 'completed' AND payment_date >= '$monthStart'");
        $payRow = $r ? $r->fetch_assoc() : ['c' => 0, 'total' => 0];
        $analytics['payments_this_month'] = (int)$payRow['c'];
        $analytics['revenue_this_month'] = (float)$payRow['total'];

        // Pending fees
        $r = $conn->query("SELECT COALESCE(SUM(balance),0) as total FROM student_fee_tracking WHERE status != 'paid'");
        $analytics['pending_fees'] = $r ? (float)$r->fetch_assoc()['total'] : 0;

        // Requirements completion rate
        $r = $conn->query("SELECT COUNT(*) as total, SUM(CASE WHEN status = 'Verified' THEN 1 ELSE 0 END) as verified FROM student_requirements_status");
        $reqRow = $r ? $r->fetch_assoc() : ['total' => 0, 'verified' => 0];
        $analytics['req_completion_rate'] = $reqRow['total'] > 0 ? round(($reqRow['verified'] / $reqRow['total']) * 100, 1) : 0;

        // Recent activities (last 10 audit logs)
        $r = $conn->query("SELECT * FROM audit_logs ORDER BY created_at DESC LIMIT 10");
        $analytics['recent_activities'] = $r ? isnm_fetch_all($r) : [];

        // Program distribution
        $r = $conn->query("SELECT program, COUNT(*) as count FROM students WHERE status = 'Active' GROUP BY program ORDER BY count DESC LIMIT 10");
        $analytics['program_distribution'] = $r ? isnm_fetch_all($r) : [];

        return $analytics;
    }
}

if (!function_exists('exportReportCSV')) {
    /**
     * Export a report as CSV.
     */
    function exportReportCSV(array $data, string $filename, array $headers = []): void {
        if (empty($data)) {
            echo "No data to export.";
            return;
        }

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '_' . date('Y-m-d') . '.csv"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $output = fopen('php://output', 'w');

        // BOM for Excel UTF-8 compatibility
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

        if (!empty($headers)) {
            fputcsv($output, $headers);
        } else {
            fputcsv($output, array_keys($data[0]));
        }

        foreach ($data as $row) {
            fputcsv($output, $row);
        }

        fclose($output);
        exit;
    }
}

if (!function_exists('generateReport')) {
    /**
     * Generate a report by type.
     */
    function generateReport(string $type, array $params = []): array {
        switch ($type) {
            case 'student_summary':
                return getStudentReports();
            case 'finance':
                return getFinanceReports($params['date_range'] ?? []);
            case 'requirements':
                return getRequirementsReports();
            case 'student_profile':
                return getStudentProfileReport(intval($params['student_id'] ?? 0));
            case 'academic':
                return getAcademicReports();
            case 'dashboard':
                return getDashboardAnalytics();
            default:
                return ['error' => 'Unknown report type: ' . $type];
        }
    }
}
