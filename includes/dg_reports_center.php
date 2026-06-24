<?php
/**
 * Executive Reports Center for Director General Dashboard.
 * Provides: Academic, Financial, HR, and System reports with export & search.
 *
 * Requires these variables in scope: $conn, $studentsConn, $websiteConn, $user_id, $user_name
 */
if (session_status() === PHP_SESSION_NONE && php_sapi_name() !== 'cli') session_start();

$rcAcademicData = [];
$rcFinancialData = [];
$rcHRData = [];
$rcSystemData = [];

// ── ACADEMIC REPORTS ──
try {
    if ($studentsConn) {
        $r = $studentsConn->query("SELECT program, COUNT(*) as count FROM students WHERE program IS NOT NULL AND program != '' GROUP BY program ORDER BY count DESC");
        if ($r) while ($row = $r->fetch_assoc()) $rcAcademicData['students_by_program'][] = $row;

        $r = $studentsConn->query("SELECT YEAR(created_at) as yr, COUNT(*) as count FROM students WHERE created_at IS NOT NULL GROUP BY YEAR(created_at) ORDER BY yr DESC");
        if ($r) while ($row = $r->fetch_assoc()) $rcAcademicData['enrollment_trends'][] = $row;

        $r = $studentsConn->query("SELECT
            (SELECT COUNT(*) FROM students WHERE status = 'Active') as active,
            (SELECT COUNT(*) FROM students WHERE status = 'Graduated') as graduated,
            (SELECT COUNT(*) FROM students WHERE status = 'Dropped') as dropped,
            (SELECT COUNT(*) FROM students WHERE status = 'Suspended') as suspended,
            (SELECT COUNT(*) FROM students WHERE status IN ('Active','Enrolled')) as enrolled");
        if ($r) $rcAcademicData['graduation_pipeline'] = $r->fetch_assoc();

        $rcExams = [];
        $r = $studentsConn->query("
            SELECT
                er.course_id,
                ac.course_name,
                ac.program,
                COUNT(er.id) as total_exams,
                ROUND(AVG(er.score), 1) as avg_score,
                SUM(CASE WHEN er.score >= 50 THEN 1 ELSE 0 END) as passed,
                ROUND((SUM(CASE WHEN er.score >= 50 THEN 1 ELSE 0 END) / COUNT(er.id)) * 100, 1) as pass_rate
            FROM examination_records er
            LEFT JOIN academic_course_catalog ac ON er.course_id = ac.id
            WHERE er.score IS NOT NULL AND ac.program IS NOT NULL
            GROUP BY er.course_id, ac.program
            ORDER BY pass_rate DESC
        ");
        if ($r) while ($row = $r->fetch_assoc()) $rcExams[] = $row;
        $rcAcademicData['pass_rates'] = $rcExams;
    }
} catch (Exception $e) { error_log('reports_center academic: ' . $e->getMessage()); }

// ── FINANCIAL REPORTS ──
try {
    if ($conn) {
        $r = $conn->query("
            SELECT DATE_FORMAT(payment_date, '%Y-%m') as ym, DATE_FORMAT(payment_date, '%b %Y') as label,
                   COUNT(*) as txns, COALESCE(SUM(amount_received), 0) as total
            FROM igangaschoolofl_students_db.payments
            WHERE status IN ('verified','approved')
            GROUP BY DATE_FORMAT(payment_date, '%Y-%m')
            ORDER BY ym DESC LIMIT 12
        ");
        if ($r) while ($row = $r->fetch_assoc()) $rcFinancialData['revenue_by_month'][] = $row;

        $r = $conn->query("
            SELECT COALESCE(s.program, 'Unknown') as program,
                   COUNT(DISTINCT s.id) as students,
                   COUNT(DISTINCT si.id) as invoices,
                   COALESCE(SUM(si.total_amount), 0) as total_billed,
                   COALESCE(SUM(si.amount_paid), 0) as total_paid,
                   COALESCE(SUM(si.balance), 0) as total_outstanding,
                   ROUND(COALESCE(SUM(si.amount_paid) / NULLIF(SUM(si.total_amount), 0) * 100, 0), 1) as collection_rate
            FROM student_invoices si
            LEFT JOIN igangaschoolofl_students_db.students s ON si.student_id = s.id
            WHERE si.status IN ('pending','partial','overdue','paid')
            GROUP BY s.program
            ORDER BY total_outstanding DESC
        ");
        if ($r) while ($row = $r->fetch_assoc()) $rcFinancialData['outstanding_by_program'][] = $row;

        $r = $conn->query("
            SELECT
                ROUND((SELECT COALESCE(SUM(amount_paid),0) FROM student_invoices WHERE status = 'paid')
                    / NULLIF((SELECT COALESCE(SUM(total_amount),0) FROM student_invoices WHERE status IN ('pending','partial','overdue','paid')), 0) * 100, 1) as overall_rate,
                (SELECT COUNT(*) FROM student_invoices WHERE status = 'paid') as paid_invoices,
                (SELECT COUNT(*) FROM student_invoices WHERE status IN ('pending','partial','overdue')) as unpaid_invoices,
                (SELECT COALESCE(SUM(balance),0) FROM student_invoices WHERE status IN ('partial','overdue')) as outstanding_total,
                (SELECT COALESCE(SUM(total_amount),0) FROM student_invoices WHERE status = 'paid') as collected_total
        ");
        if ($r) $rcFinancialData['collection_rates'] = $r->fetch_assoc();

        $r = $conn->query("
            SELECT si.student_id, s.first_name, s.last_name, s.student_number, s.program,
                   si.total_amount, si.amount_paid, si.balance
            FROM student_invoices si
            LEFT JOIN igangaschoolofl_students_db.students s ON si.student_id = s.id
            WHERE si.status IN ('partial','overdue') AND si.balance > 0
            ORDER BY si.balance DESC LIMIT 10
        ");
        if ($r) while ($row = $r->fetch_assoc()) $rcFinancialData['top_debtors'][] = $row;
    }
} catch (Exception $e) { error_log('reports_center financial: ' . $e->getMessage()); }

// ── HR REPORTS ──
try {
    if ($conn) {
        $r = $conn->query("
            SELECT COALESCE(department, 'Unassigned') as department,
                   COUNT(*) as total,
                   SUM(CASE WHEN status = 'Active' THEN 1 ELSE 0 END) as active,
                   SUM(CASE WHEN status = 'On Leave' THEN 1 ELSE 0 END) as on_leave,
                   SUM(CASE WHEN status = 'Inactive' THEN 1 ELSE 0 END) as inactive
            FROM staff
            GROUP BY department
            ORDER BY total DESC
        ");
        if ($r) while ($row = $r->fetch_assoc()) $rcHRData['staff_by_dept'][] = $row;

        $r = $conn->query("
            SELECT
                (SELECT COUNT(*) FROM staff_attendance WHERE DATE(date) = CURDATE() AND status = 'Present') as present,
                (SELECT COUNT(*) FROM staff_attendance WHERE DATE(date) = CURDATE() AND status = 'Late') as late,
                (SELECT COUNT(*) FROM staff_attendance WHERE DATE(date) = CURDATE() AND status = 'Absent') as absent,
                (SELECT COUNT(*) FROM staff_attendance WHERE DATE(date) = CURDATE() AND status IN ('On Leave','on_leave')) as on_leave,
                (SELECT COUNT(*) FROM staff WHERE status = 'Active') as total_active
        ");
        if ($r) $rcHRData['attendance_summary'] = $r->fetch_assoc();

        $r = $conn->query("
            SELECT leave_type, COUNT(*) as total,
                   SUM(CASE WHEN status = 'Approved' THEN 1 ELSE 0 END) as approved,
                   SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending,
                   SUM(CASE WHEN status = 'Rejected' THEN 1 ELSE 0 END) as rejected
            FROM staff_leave WHERE YEAR(created_at) = YEAR(CURDATE())
            GROUP BY leave_type ORDER BY total DESC
        ");
        if ($r) while ($row = $r->fetch_assoc()) $rcHRData['leave_stats'][] = $row;

        $r = $conn->query("
            SELECT id, staff_id, full_name, position, department, employment_date,
                   CASE
                       WHEN contract_end_date IS NOT NULL THEN contract_end_date
                       ELSE DATE_ADD(employment_date, INTERVAL 2 YEAR)
                   END as contract_end,
                   DATEDIFF(
                       CASE
                           WHEN contract_end_date IS NOT NULL THEN contract_end_date
                           ELSE DATE_ADD(employment_date, INTERVAL 2 YEAR)
                       END,
                       CURDATE()
                   ) as days_remaining
            FROM staff
            WHERE status = 'Active'
            HAVING days_remaining BETWEEN 0 AND 90
            ORDER BY days_remaining ASC LIMIT 15
        ");
        if ($r) while ($row = $r->fetch_assoc()) $rcHRData['contract_expiry'][] = $row;
    }
} catch (Exception $e) { error_log('reports_center hr: ' . $e->getMessage()); }

// ── SYSTEM REPORTS ──
$rcSystemData['table_counts'] = [];
$rcSystemData['activity_summary'] = [];
$rcSystemData['errors'] = 0;
try {
    $countQueries = [
        ['Staffs DB' => $conn, 'tables' => [
            'staff' => 'staff', 'staff_roles' => 'staff_roles', 'staff_departments' => 'staff_departments',
            'staff_attendance' => 'staff_attendance', 'staff_leave' => 'staff_leave',
            'staff_activity_log' => 'staff_activity_log', 'alerts' => 'alerts',
            'approval_requests' => 'approval_requests', 'store_requests' => 'store_requests',
            'director_news' => 'director_news',
        ]],
    ];
    if ($conn) {
        foreach ($countQueries[0]['tables'] as $label => $table) {
            $r = $conn->query("SELECT COUNT(*) as c FROM {$table}");
            if ($r) $rcSystemData['table_counts'][] = ['table' => $label, 'count' => (int)$r->fetch_assoc()['c']];
        }
        // Students DB table counts
        if ($studentsConn) {
            $stuTables = ['students', 'examination_records', 'payments', 'student_invoices', 'student_attendance', 'student_notifications', 'announcements'];
            foreach ($stuTables as $table) {
                $r = $studentsConn->query("SELECT COUNT(*) as c FROM {$table}");
                if ($r) $rcSystemData['table_counts'][] = ['table' => "students_db.{$table}", 'count' => (int)$r->fetch_assoc()['c']];
            }
        }
        // Website DB table counts
        if ($websiteConn) {
            $webTables = ['contact_submissions', 'volunteer_applications', 'donations', 'student_applications', 'news'];
            foreach ($webTables as $table) {
                $r = $websiteConn->query("SELECT COUNT(*) as c FROM {$table}");
                if ($r) $rcSystemData['table_counts'][] = ['table' => "website_db.{$table}", 'count' => (int)$r->fetch_assoc()['c']];
            }
        }
    }

    if ($conn) {
        $r = $conn->query("
            SELECT activity_type, COUNT(*) as count, MAX(created_at) as last_occurrence
            FROM staff_activity_log
            WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
            GROUP BY activity_type
            ORDER BY count DESC LIMIT 15
        ");
        if ($r) while ($row = $r->fetch_assoc()) $rcSystemData['activity_summary'][] = $row;

        try {
            $r = $conn->query("SELECT COUNT(*) as c FROM error_logs WHERE DATE(created_at) = CURDATE()");
            if ($r) $rcSystemData['errors'] = (int)$r->fetch_assoc()['c'];
        } catch (Exception $e) {
            $rcSystemData['errors'] = 0;
        }
    }
} catch (Exception $e) { error_log('reports_center system: ' . $e->getMessage()); }

// ── Helper: render a table with search & export ──
function rcRenderTable($id, $headers, $rows, $emptyMsg = 'No data available.') {
    $html = '<div class="rc-table-wrap" style="margin-top:8px;">';
    $html .= '<div class="rc-table-toolbar" style="display:flex;align-items:center;gap:8px;margin-bottom:8px;flex-wrap:wrap;">';
    $html .= '<input type="text" class="rc-search" id="rcSearch_' . $id . '" placeholder="Search table..." style="padding:4px 10px;border:1px solid #e2e8f0;border-radius:6px;font-size:12px;outline:none;flex:1;min-width:140px;max-width:280px;" oninput="rcFilterTable(\'' . $id . '\',this.value)">';
    $html .= '<button onclick="rcExportCSV(\'' . $id . '\')" class="btn btn-sm" style="background:#fff;border:1px solid #e2e8f0;border-radius:6px;font-size:11px;color:#475569;white-space:nowrap;"><i class="fas fa-download me-1"></i>CSV</button>';
    $html .= '</div>';
    $html .= '<div class="table-responsive" style="max-height:320px;overflow-y:auto;">';
    $html .= '<table class="dg-table table rc-table" id="rcTable_' . $id . '" style="font-size:12px;margin-bottom:0;">';
    $html .= '<thead><tr>';
    foreach ($headers as $h) $html .= '<th style="background:#f8fafc;font-weight:600;color:#64748b;text-transform:uppercase;font-size:10px;letter-spacing:0.4px;padding:7px 10px;border-bottom:2px solid #e2e8f0;white-space:nowrap;">' . htmlspecialchars($h) . '</th>';
    $html .= '</tr></thead><tbody>';
    if (empty($rows)) {
        $cols = count($headers);
        $html .= '<tr><td colspan="' . $cols . '" class="text-center text-muted py-4">' . $emptyMsg . '</td></tr>';
    } else {
        foreach ($rows as $row) {
            $html .= '<tr>';
            foreach ($row as $cell) {
                $align = is_numeric($cell) && !preg_match('/^0\d/', $cell) ? ' style="text-align:right;"' : '';
                $html .= '<td' . $align . ' style="padding:7px 10px;vertical-align:middle;border-bottom:1px solid #f1f5f9;">' . $cell . '</td>';
            }
            $html .= '</tr>';
        }
    }
    $html .= '</tbody></table></div></div>';
    return $html;
}

function rcFormatCurrency($v) {
    if ($v === null || $v === '') return '-';
    return 'UGX ' . number_format((float)$v);
}

?>
<style>
.rc-tab-nav {
    display: flex;
    gap: 4px;
    margin-bottom: 16px;
    background: #fff;
    border-radius: 10px;
    padding: 5px 6px;
    box-shadow: 0 1px 4px rgba(0,0,0,0.06);
    flex-wrap: wrap;
}
.rc-tab-btn {
    padding: 7px 16px;
    border-radius: 8px;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    border: none;
    background: transparent;
    color: #64748b;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}
.rc-tab-btn:hover { background: rgba(37,99,235,0.06); color: #1e293b; }
.rc-tab-btn.active { background: rgba(37,99,235,0.1); color: #2563eb; }
.rc-tab-content { display: none; }
.rc-tab-content.active { display: block; }
.rc-table-wrap { position: relative; }
.rc-table tbody tr:hover { background: #f8fafc; }
.rc-table::-webkit-scrollbar { width: 4px; }
.rc-table::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
.rc-stat-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 10px; margin-bottom: 14px; }
.rc-stat-card { padding: 12px 14px; border-radius: 10px; background: #fff; box-shadow: 0 1px 4px rgba(0,0,0,0.06); border: 1px solid #f1f5f9; text-align: center; }
.rc-stat-card .rc-stat-val { font-size: 18px; font-weight: 800; letter-spacing: -0.3px; }
.rc-stat-card .rc-stat-lbl { font-size: 10px; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.4px; margin-top: 2px; }
</style>

<div style="margin-bottom:14px;">
    <?php dgToolbar('Reports Center', 'fa-chart-pie', '4 report types', 'bg-info'); ?>
</div>

<div class="section-card" style="padding:16px 18px;">
    <div class="section-header" style="margin-bottom:6px;">
        <div>
            <h3 class="section-title"><i class="fas fa-chart-pie" style="color:#3b82f6;"></i>Executive Reports Center</h3>
            <p class="section-subtitle">Comprehensive institution-wide reports at a glance</p>
        </div>
    </div>

    <!-- Tab Navigation -->
    <div class="rc-tab-nav">
        <button class="rc-tab-btn active" data-rc-tab="academic" onclick="rcSwitchTab('academic')"><i class="fas fa-graduation-cap"></i> Academic</button>
        <button class="rc-tab-btn" data-rc-tab="financial" onclick="rcSwitchTab('financial')"><i class="fas fa-coins"></i> Financial</button>
        <button class="rc-tab-btn" data-rc-tab="hr" onclick="rcSwitchTab('hr')"><i class="fas fa-users"></i> HR</button>
        <button class="rc-tab-btn" data-rc-tab="system" onclick="rcSwitchTab('system')"><i class="fas fa-cogs"></i> System</button>
    </div>

    <!-- ═══ ACADEMIC TAB ═══ -->
    <div class="rc-tab-content active" id="rcTab_academic">
        <div class="row g-3">
            <div class="col-lg-6">
                <div class="section-card h-100" style="margin-bottom:0;padding:14px 16px;">
                    <h4 style="font-size:13px;font-weight:700;margin:0 0 8px;display:flex;align-items:center;gap:6px;"><i class="fas fa-users" style="color:#3b82f6;"></i>Students by Program</h4>
                    <?php
                    $rows = [];
                    if (!empty($rcAcademicData['students_by_program'])) {
                        foreach ($rcAcademicData['students_by_program'] as $r) {
                            $rows[] = [htmlspecialchars($r['program']), number_format((int)$r['count'])];
                        }
                    }
                    echo rcRenderTable('acad_program', ['Program', 'Students'], $rows);
                    ?>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="section-card h-100" style="margin-bottom:0;padding:14px 16px;">
                    <h4 style="font-size:13px;font-weight:700;margin:0 0 8px;display:flex;align-items:center;gap:6px;"><i class="fas fa-chart-line" style="color:#059669;"></i>Pass Rate by Course</h4>
                    <?php
                    $rows = [];
                    if (!empty($rcAcademicData['pass_rates'])) {
                        foreach ($rcAcademicData['pass_rates'] as $r) {
                            $rate = (float)$r['pass_rate'];
                            $badge = $rate >= 80 ? '<span class="badge bg-success" style="font-size:10px;">' . $rate . '%</span>'
                                   : ($rate >= 50 ? '<span class="badge bg-warning text-dark" style="font-size:10px;">' . $rate . '%</span>'
                                   : '<span class="badge bg-danger" style="font-size:10px;">' . $rate . '%</span>');
                            $rows[] = [
                                htmlspecialchars(mb_substr($r['course_name'] ?? 'Unknown', 0, 40)),
                                htmlspecialchars($r['program'] ?? '-'),
                                (int)$r['total_exams'],
                                $badge,
                            ];
                        }
                    }
                    echo rcRenderTable('acad_passrate', ['Course', 'Program', 'Exams', 'Pass Rate'], $rows);
                    ?>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="section-card h-100" style="margin-bottom:0;padding:14px 16px;">
                    <h4 style="font-size:13px;font-weight:700;margin:0 0 8px;display:flex;align-items:center;gap:6px;"><i class="fas fa-calendar-alt" style="color:#8b5cf6;"></i>Enrollment Trends</h4>
                    <?php
                    $rows = [];
                    if (!empty($rcAcademicData['enrollment_trends'])) {
                        foreach ($rcAcademicData['enrollment_trends'] as $r) {
                            $rows[] = ['<strong>' . htmlspecialchars($r['yr']) . '</strong>', number_format((int)$r['count'])];
                        }
                    }
                    echo rcRenderTable('acad_trends', ['Year', 'New Enrollments'], $rows);
                    ?>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="section-card h-100" style="margin-bottom:0;padding:14px 16px;">
                    <h4 style="font-size:13px;font-weight:700;margin:0 0 8px;display:flex;align-items:center;gap:6px;"><i class="fas fa-graduation-cap" style="color:#d97706;"></i>Graduation Pipeline</h4>
                    <?php
                    $pipe = $rcAcademicData['graduation_pipeline'] ?? [];
                    if (!empty($pipe)) {
                        echo '<div class="rc-stat-grid">';
                        $items = [
                            ['Active', (int)($pipe['active'] ?? 0), '#3b82f6'],
                            ['Enrolled', (int)($pipe['enrolled'] ?? 0), '#0891b2'],
                            ['Graduated', (int)($pipe['graduated'] ?? 0), '#059669'],
                            ['Dropped', (int)($pipe['dropped'] ?? 0), '#dc2626'],
                            ['Suspended', (int)($pipe['suspended'] ?? 0), '#d97706'],
                        ];
                        foreach ($items as $it) {
                            echo '<div class="rc-stat-card"><div class="rc-stat-val" style="color:' . $it[2] . ';">' . number_format($it[1]) . '</div><div class="rc-stat-lbl">' . $it[0] . '</div></div>';
                        }
                        echo '</div>';
                    } else {
                        echo '<p class="text-muted small text-center py-4">Pipeline data not available.</p>';
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══ FINANCIAL TAB ═══ -->
    <div class="rc-tab-content" id="rcTab_financial">
        <div class="row g-3">
            <div class="col-lg-7">
                <div class="section-card h-100" style="margin-bottom:0;padding:14px 16px;">
                    <h4 style="font-size:13px;font-weight:700;margin:0 0 8px;display:flex;align-items:center;gap:6px;"><i class="fas fa-calendar-check" style="color:#059669;"></i>Revenue by Month</h4>
                    <?php
                    $rows = [];
                    $revTotal = 0;
                    if (!empty($rcFinancialData['revenue_by_month'])) {
                        foreach ($rcFinancialData['revenue_by_month'] as $r) {
                            $rows[] = [htmlspecialchars($r['label']), (int)$r['txns'], rcFormatCurrency($r['total'])];
                            $revTotal += (float)$r['total'];
                        }
                    }
                    echo rcRenderTable('fin_revenue', ['Month', 'Transactions', 'Total'], $rows);
                    if ($revTotal > 0) {
                        echo '<div class="text-end mt-1 small text-muted"><strong>Total (12 months):</strong> ' . rcFormatCurrency($revTotal) . '</div>';
                    }
                    ?>
                </div>
            </div>
            <div class="col-lg-5">
                <div class="section-card h-100" style="margin-bottom:0;padding:14px 16px;">
                    <h4 style="font-size:13px;font-weight:700;margin:0 0 8px;display:flex;align-items:center;gap:6px;"><i class="fas fa-exclamation-triangle" style="color:#dc2626;"></i>Top Debtors</h4>
                    <?php
                    $rows = [];
                    if (!empty($rcFinancialData['top_debtors'])) {
                        foreach ($rcFinancialData['top_debtors'] as $r) {
                            $name = htmlspecialchars(($r['first_name'] ?? '') . ' ' . ($r['last_name'] ?? ''));
                            $sid = htmlspecialchars($r['student_number'] ?? '');
                            $rows[] = [
                                $name . '<br><code style="font-size:10px;">' . $sid . '</code>',
                                htmlspecialchars($r['program'] ?? '-'),
                                rcFormatCurrency($r['balance']),
                            ];
                        }
                    }
                    echo rcRenderTable('fin_debtors', ['Student', 'Program', 'Balance'], $rows, 'No outstanding debtors.');
                    ?>
                </div>
            </div>
            <div class="col-lg-5">
                <div class="section-card h-100" style="margin-bottom:0;padding:14px 16px;">
                    <h4 style="font-size:13px;font-weight:700;margin:0 0 8px;display:flex;align-items:center;gap:6px;"><i class="fas fa-building" style="color:#0891b2;"></i>Outstanding by Program</h4>
                    <?php
                    $rows = [];
                    if (!empty($rcFinancialData['outstanding_by_program'])) {
                        foreach ($rcFinancialData['outstanding_by_program'] as $r) {
                            $cr = (float)$r['collection_rate'];
                            $badge = $cr >= 80 ? '<span class="badge bg-success" style="font-size:10px;">' . $cr . '%</span>'
                                   : ($cr >= 50 ? '<span class="badge bg-warning text-dark" style="font-size:10px;">' . $cr . '%</span>'
                                   : '<span class="badge bg-danger" style="font-size:10px;">' . $cr . '%</span>');
                            $rows[] = [
                                htmlspecialchars($r['program']),
                                rcFormatCurrency($r['total_billed']),
                                rcFormatCurrency($r['total_outstanding']),
                                $badge,
                            ];
                        }
                    }
                    echo rcRenderTable('fin_outstanding', ['Program', 'Billed', 'Outstanding', 'Collection'], $rows);
                    ?>
                </div>
            </div>
            <div class="col-lg-7">
                <div class="section-card h-100" style="margin-bottom:0;padding:14px 16px;">
                    <h4 style="font-size:13px;font-weight:700;margin:0 0 8px;display:flex;align-items:center;gap:6px;"><i class="fas fa-percent" style="color:#8b5cf6;"></i>Fee Collection Summary</h4>
                    <?php
                    $cr = $rcFinancialData['collection_rates'] ?? [];
                    if (!empty($cr)) {
                        $overall = (float)($cr['overall_rate'] ?? 0);
                        $paid = (int)($cr['paid_invoices'] ?? 0);
                        $unpaid = (int)($cr['unpaid_invoices'] ?? 0);
                        $outstanding = (float)($cr['outstanding_total'] ?? 0);
                        $collected = (float)($cr['collected_total'] ?? 0);
                        echo '<div class="rc-stat-grid">';
                        echo '<div class="rc-stat-card"><div class="rc-stat-val" style="color:#059669;">' . $overall . '%</div><div class="rc-stat-lbl">Collection Rate</div></div>';
                        echo '<div class="rc-stat-card"><div class="rc-stat-val" style="color:#3b82f6;">' . number_format($paid) . '</div><div class="rc-stat-lbl">Paid Invoices</div></div>';
                        echo '<div class="rc-stat-card"><div class="rc-stat-val" style="color:#dc2626;">' . number_format($unpaid) . '</div><div class="rc-stat-lbl">Unpaid Invoices</div></div>';
                        echo '<div class="rc-stat-card"><div class="rc-stat-val" style="color:#059669;">' . rcFormatCurrency($collected) . '</div><div class="rc-stat-lbl">Collected</div></div>';
                        echo '<div class="rc-stat-card"><div class="rc-stat-val" style="color:#dc2626;">' . rcFormatCurrency($outstanding) . '</div><div class="rc-stat-lbl">Outstanding</div></div>';
                        echo '</div>';
                    } else {
                        echo '<p class="text-muted small text-center py-4">Collection data not available.</p>';
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══ HR TAB ═══ -->
    <div class="rc-tab-content" id="rcTab_hr">
        <div class="row g-3">
            <div class="col-lg-6">
                <div class="section-card h-100" style="margin-bottom:0;padding:14px 16px;">
                    <h4 style="font-size:13px;font-weight:700;margin:0 0 8px;display:flex;align-items:center;gap:6px;"><i class="fas fa-building" style="color:#3b82f6;"></i>Staff Count by Department</h4>
                    <?php
                    $rows = [];
                    if (!empty($rcHRData['staff_by_dept'])) {
                        foreach ($rcHRData['staff_by_dept'] as $r) {
                            $rows[] = [
                                htmlspecialchars($r['department']),
                                (int)$r['total'],
                                '<span class="badge bg-success" style="font-size:10px;">' . (int)$r['active'] . '</span>',
                                (int)$r['on_leave'],
                                (int)$r['inactive'],
                            ];
                        }
                    }
                    echo rcRenderTable('hr_dept', ['Department', 'Total', 'Active', 'On Leave', 'Inactive'], $rows);
                    ?>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="section-card h-100" style="margin-bottom:0;padding:14px 16px;">
                    <h4 style="font-size:13px;font-weight:700;margin:0 0 8px;display:flex;align-items:center;gap:6px;"><i class="fas fa-clock" style="color:#059669;"></i>Today's Attendance</h4>
                    <?php
                    $att = $rcHRData['attendance_summary'] ?? [];
                    if (!empty($att)) {
                        echo '<div class="rc-stat-grid">';
                        $items = [
                            ['Present', (int)($att['present'] ?? 0), '#059669'],
                            ['Late', (int)($att['late'] ?? 0), '#d97706'],
                            ['Absent', (int)($att['absent'] ?? 0), '#dc2626'],
                            ['On Leave', (int)($att['on_leave'] ?? 0), '#3b82f6'],
                            ['Total Active', (int)($att['total_active'] ?? 0), '#64748b'],
                        ];
                        foreach ($items as $it) {
                            echo '<div class="rc-stat-card"><div class="rc-stat-val" style="color:' . $it[2] . ';">' . number_format($it[1]) . '</div><div class="rc-stat-lbl">' . $it[0] . '</div></div>';
                        }
                        echo '</div>';
                    } else {
                        echo '<p class="text-muted small text-center py-4">No attendance data for today.</p>';
                    }
                    ?>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="section-card h-100" style="margin-bottom:0;padding:14px 16px;">
                    <h4 style="font-size:13px;font-weight:700;margin:0 0 8px;display:flex;align-items:center;gap:6px;"><i class="fas fa-calendar-alt" style="color:#8b5cf6;"></i>Leave Statistics (This Year)</h4>
                    <?php
                    $rows = [];
                    if (!empty($rcHRData['leave_stats'])) {
                        foreach ($rcHRData['leave_stats'] as $r) {
                            $rows[] = [
                                htmlspecialchars($r['leave_type']),
                                (int)$r['total'],
                                '<span class="badge bg-success" style="font-size:10px;">' . (int)$r['approved'] . '</span>',
                                '<span class="badge bg-warning text-dark" style="font-size:10px;">' . (int)$r['pending'] . '</span>',
                                (int)$r['rejected'],
                            ];
                        }
                    }
                    echo rcRenderTable('hr_leave', ['Leave Type', 'Total', 'Approved', 'Pending', 'Rejected'], $rows, 'No leave records for this year.');
                    ?>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="section-card h-100" style="margin-bottom:0;padding:14px 16px;">
                    <h4 style="font-size:13px;font-weight:700;margin:0 0 8px;display:flex;align-items:center;gap:6px;"><i class="fas fa-file-contract" style="color:#dc2626;"></i>Contract Expiration (Next 90 Days)</h4>
                    <?php
                    $rows = [];
                    if (!empty($rcHRData['contract_expiry'])) {
                        foreach ($rcHRData['contract_expiry'] as $r) {
                            $days = (int)$r['days_remaining'];
                            $urgency = $days <= 14 ? '<span class="badge bg-danger" style="font-size:10px;">' . $days . ' days</span>'
                                     : ($days <= 30 ? '<span class="badge bg-warning text-dark" style="font-size:10px;">' . $days . ' days</span>'
                                     : '<span class="badge bg-info" style="font-size:10px;">' . $days . ' days</span>');
                            $rows[] = [
                                htmlspecialchars($r['full_name'] ?? ''),
                                htmlspecialchars($r['position'] ?? '-'),
                                htmlspecialchars($r['department'] ?? '-'),
                                $urgency,
                            ];
                        }
                    }
                    echo rcRenderTable('hr_contracts', ['Name', 'Position', 'Department', 'Expiry'], $rows, 'No contracts expiring within 90 days.');
                    ?>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══ SYSTEM TAB ═══ -->
    <div class="rc-tab-content" id="rcTab_system">
        <div class="row g-3">
            <div class="col-lg-12">
                <div class="section-card h-100" style="margin-bottom:0;padding:14px 16px;">
                    <h4 style="font-size:13px;font-weight:700;margin:0 0 8px;display:flex;align-items:center;gap:6px;"><i class="fas fa-database" style="color:#3b82f6;"></i>Database Table Counts</h4>
                    <?php
                    $rows = [];
                    $totalRows = 0;
                    if (!empty($rcSystemData['table_counts'])) {
                        foreach ($rcSystemData['table_counts'] as $r) {
                            $rows[] = [htmlspecialchars($r['table']), number_format($r['count'])];
                            $totalRows += $r['count'];
                        }
                    }
                    echo rcRenderTable('sys_tables', ['Table', 'Row Count'], $rows);
                    if ($totalRows > 0) {
                        echo '<div class="text-end mt-1 small text-muted"><strong>Total Records:</strong> ' . number_format($totalRows) . '</div>';
                    }
                    ?>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="section-card h-100" style="margin-bottom:0;padding:14px 16px;">
                    <h4 style="font-size:13px;font-weight:700;margin:0 0 8px;display:flex;align-items:center;gap:6px;"><i class="fas fa-history" style="color:#64748b;"></i>Activity Log Summary (30 Days)</h4>
                    <?php
                    $rows = [];
                    if (!empty($rcSystemData['activity_summary'])) {
                        foreach ($rcSystemData['activity_summary'] as $r) {
                            $rows[] = [
                                htmlspecialchars($r['activity_type']),
                                (int)$r['count'],
                                '<span style="font-size:11px;color:#64748b;">' . htmlspecialchars($r['last_occurrence'] ?? '-') . '</span>',
                            ];
                        }
                    }
                    echo rcRenderTable('sys_activity', ['Activity Type', 'Count', 'Last Occurrence'], $rows, 'No activity logs in the last 30 days.');
                    ?>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="section-card h-100" style="margin-bottom:0;padding:14px 16px;">
                    <h4 style="font-size:13px;font-weight:700;margin:0 0 8px;display:flex;align-items:center;gap:6px;"><i class="fas fa-exclamation-circle" style="color:#dc2626;"></i>System Health</h4>
                    <?php
                    $errCount = (int)($rcSystemData['errors'] ?? 0);
                    echo '<div class="rc-stat-grid">';
                    echo '<div class="rc-stat-card"><div class="rc-stat-val" style="color:' . ($errCount > 0 ? '#dc2626' : '#059669') . ';">' . number_format($errCount) . '</div><div class="rc-stat-lbl">Errors Today</div></div>';
                    echo '<div class="rc-stat-card"><div class="rc-stat-val" style="color:#3b82f6;">' . count($rcSystemData['table_counts']) . '</div><div class="rc-stat-lbl">Tables Monitored</div></div>';
                    echo '<div class="rc-stat-card"><div class="rc-stat-val" style="color:#8b5cf6;">' . number_format($totalRows) . '</div><div class="rc-stat-lbl">Total Records</div></div>';
                    echo '<div class="rc-stat-card"><div class="rc-stat-val" style="color:#059669;">' . date('Y-m-d H:i') . '</div><div class="rc-stat-lbl">Last Check</div></div>';
                    echo '</div>';
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function rcSwitchTab(tabId) {
    document.querySelectorAll('.rc-tab-content').forEach(function(el) {
        el.classList.toggle('active', el.id === 'rcTab_' + tabId);
    });
    document.querySelectorAll('.rc-tab-btn').forEach(function(el) {
        el.classList.toggle('active', el.dataset.rcTab === tabId);
    });
}

function rcFilterTable(tblId, query) {
    var q = query.toLowerCase().trim();
    var table = document.getElementById('rcTable_' + tblId);
    if (!table || !table.tBodies[0]) return;
    var rows = table.tBodies[0].rows;
    for (var i = 0; i < rows.length; i++) {
        rows[i].style.display = !q || rows[i].textContent.toLowerCase().indexOf(q) !== -1 ? '' : 'none';
    }
}

function rcExportCSV(tblId) {
    var table = document.getElementById('rcTable_' + tblId);
    if (!table) return;
    var rows = [].slice.call(table.querySelectorAll('tr'));
    var csv = rows.map(function(row) {
        return [].slice.call(row.querySelectorAll('th,td')).map(function(cell) {
            return '"' + cell.textContent.trim().replace(/"/g, '""') + '"';
        }).join(',');
    }).join('\n');
    var blob = new Blob(["\uFEFF" + csv], {type: 'text/csv;charset=utf-8;'});
    var link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = 'report-' + tblId + '-' + new Date().toISOString().slice(0,10) + '.csv';
    link.click();
}
</script>
