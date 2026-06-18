<?php
/**
 * Executive Overview Renderer for Director General Dashboard.
 * Provides: stats cards, department comparison, alerts, compliance, risks.
 */

if (session_status() === PHP_SESSION_NONE) session_start();

if (!function_exists('getExecutiveStats')) {
function getExecutiveStats($studentsConn, $staffConn) {
    $stats = [
        'total_students' => 0, 'active_students' => 0, 'new_admissions' => 0,
        'graduated' => 0, 'total_staff' => 0, 'total_revenue' => 0,
        'total_expenses' => 0, 'pending_approvals' => 0, 'critical_alerts' => 0,
        'avg_attendance' => 0, 'avg_performance' => 0,
    ];
    try {
        if ($studentsConn) {
            $r = $studentsConn->query("SELECT COUNT(*) as c FROM students"); if ($r) $stats['total_students'] = (int)$r->fetch_assoc()['c'];
            $r = $studentsConn->query("SELECT COUNT(*) as c FROM students WHERE status = 'Active'"); if ($r) $stats['active_students'] = (int)$r->fetch_assoc()['c'];
            $r = $studentsConn->query("SELECT COUNT(*) as c FROM students WHERE status = 'Graduated'"); if ($r) $stats['graduated'] = (int)$r->fetch_assoc()['c'];
            $r = $studentsConn->query("SELECT COUNT(*) as c FROM students WHERE DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)"); if ($r) $stats['new_admissions'] = (int)$r->fetch_assoc()['c'];
        }
        if ($staffConn) {
            $r = $staffConn->query("SELECT COUNT(*) as c FROM staff WHERE status = 'Active'"); if ($r) $stats['total_staff'] = (int)$r->fetch_assoc()['c'];
            try {
                $r = $staffConn->query("SELECT COUNT(*) as c FROM igangaschoolofl_staffs_db.institutional_alerts WHERE is_resolved = 0 AND priority = 'Critical'");
                if ($r) $stats['critical_alerts'] = (int)$r->fetch_assoc()['c'];
            } catch (Exception $e) {}
            try {
                $r = $staffConn->query("SELECT COUNT(*) as c FROM igangaschoolofl_staffs_db.approval_requests WHERE status = 'Active'");
                if ($r) $stats['pending_approvals'] = (int)$r->fetch_assoc()['c'];
            } catch (Exception $e) {}
        }
    } catch (Exception $e) { error_log('getExecutiveStats error: ' . $e->getMessage()); }
    return $stats;
}
}

if (!function_exists('getDepartmentPerformance')) {
function getDepartmentPerformance($staffConn) {
    $depts = [];
    if (!$staffConn) return $depts;
    try {
        $result = $staffConn->query("
            SELECT dd.department_name, dd.department_code, dd.description,
                   sr.role_name, sr.hierarchy_level,
                   (SELECT COUNT(*) FROM staff s WHERE s.role_id = dd.role_id AND s.status = 'Active') as staff_count,
                   (SELECT COUNT(*) FROM igangaschoolofl_staffs_db.department_targets dt WHERE dt.department_code = dd.department_code AND (dt.status = 'In Progress' OR dt.status = 'Not Started')) as active_targets,
                   (SELECT COUNT(*) FROM igangaschoolofl_staffs_db.department_targets dt WHERE dt.department_code = dd.department_code AND dt.status = 'Missed') as missed_targets,
                   (SELECT COUNT(*) FROM igangaschoolofl_staffs_db.department_targets dt WHERE dt.department_code = dd.department_code AND dt.status IN ('Achieved','Exceeded')) as achieved_targets,
                   (SELECT AVG(CASE WHEN dt.achieved_value > 0 AND dt.target_value > 0 THEN (dt.achieved_value / dt.target_value) * 100 ELSE 0 END) FROM igangaschoolofl_staffs_db.department_targets dt WHERE dt.department_code = dd.department_code) as avg_performance
            FROM igangaschoolofl_staffs_db.director_departments dd
            JOIN igangaschoolofl_staffs_db.staff_roles sr ON dd.role_id = sr.id
            WHERE dd.status = 'Active'
            ORDER BY sr.hierarchy_level ASC
        ");
        if ($result) while ($row = $result->fetch_assoc()) $depts[] = $row;
    } catch (Exception $e) {}
    return $depts;
}
}

if (!function_exists('renderExecutiveStatCard')) {
function renderExecutiveStatCard($icon, $label, $value, $color = 'primary', $trend = '', $trendDir = 'up') {
    $trendHtml = '';
    if ($trend !== '') {
        $trendIcon = $trendDir === 'up' ? 'fa-arrow-up text-success' : 'fa-arrow-down text-danger';
        $trendHtml = "<span class=\"exec-trend ms-2\"><i class=\"fas {$trendIcon}\"></i> {$trend}</span>";
    }
    return '<div class="exec-stat-card">'
        . '<div class="exec-stat-icon bg-' . $color . '-subtle text-' . $color . '"><i class="fas ' . $icon . '"></i></div>'
        . '<div class="exec-stat-info">'
        . '  <div class="exec-stat-value">' . $value . '</div>'
        . '  <div class="exec-stat-label">' . $label . $trendHtml . '</div>'
        . '</div></div>';
}
}

if (!function_exists('renderExecutiveOverview')) {
function renderExecutiveOverview($studentsConn, $staffConn) {
    $stats = getExecutiveStats($studentsConn, $staffConn);
    $html = '<div class="executive-overview">';
    $html .= '  <div class="exec-stats-grid">';
    $html .= renderExecutiveStatCard('fa-users', 'Total Students', number_format($stats['total_students']), 'primary');
    $html .= renderExecutiveStatCard('fa-user-check', 'Active Students', number_format($stats['active_students']), 'success');
    $html .= renderExecutiveStatCard('fa-user-plus', 'New Admissions (30d)', $stats['new_admissions'], 'info');
    $html .= renderExecutiveStatCard('fa-graduation-cap', 'Graduated', number_format($stats['graduated']), 'warning');
    $html .= renderExecutiveStatCard('fa-users-cog', 'Active Staff', number_format($stats['total_staff']), 'secondary');
    $html .= renderExecutiveStatCard('fa-clock', 'Pending Approvals', $stats['pending_approvals'], 'danger', '', $stats['pending_approvals'] > 5 ? 'up' : 'down');
    $html .= '  </div>';
    $html .= '</div>';
    return $html;
}
}

if (!function_exists('renderDepartmentComparison')) {
function renderDepartmentComparison($staffConn) {
    $depts = getDepartmentPerformance($staffConn);
    if (empty($depts)) return '<div class="text-muted small py-3 text-center">Department data not available yet. Run the SQL migration first.</div>';
    
    $html = '<div class="dept-comparison-grid">';
    foreach ($depts as $dept) {
        $perf = round((float)($dept['avg_performance'] ?? 0));
        $color = $perf >= 80 ? 'success' : ($perf >= 60 ? 'warning' : 'danger');
        $barWidth = min(100, max(5, $perf));
        $problems = (int)($dept['missed_targets'] ?? 0) + (int)($dept['critical_alerts'] ?? 0);
        
        $html .= '<div class="dept-comparison-card">';
        $html .= '  <div class="dept-card-header">';
        $html .= '    <div class="dept-card-title">';
        $html .= '      <h6 class="mb-0 fw-semibold">' . htmlspecialchars($dept['department_name']) . '</h6>';
        $html .= '      <span class="badge bg-secondary" style="font-size:8px">' . htmlspecialchars($dept['department_code']) . '</span>';
        $html .= '    </div>';
        $html .= '    <div class="dept-director">';
        $html .= '      <i class="fas fa-user-tie text-primary"></i> ' . htmlspecialchars($dept['role_name']);
        $html .= '    </div>';
        $html .= '  </div>';
        $html .= '  <div class="dept-card-body">';
        $html .= '    <div class="d-flex justify-content-between mb-1">';
        $html .= '      <span class="small">Performance</span>';
        $html .= '      <span class="small fw-bold text-' . $color . '">' . $perf . '%</span>';
        $html .= '    </div>';
        $html .= '    <div class="progress mb-2" style="height:6px">';
        $html .= '      <div class="progress-bar bg-' . $color . '" style="width:' . $barWidth . '%"></div>';
        $html .= '    </div>';
        $html .= '    <div class="row g-1 text-center small">';
        $html .= '      <div class="col-4"><span class="fw-bold text-success">' . ($dept['achieved_targets'] ?? 0) . '</span><br><span style="font-size:9px">Achieved</span></div>';
        $html .= '      <div class="col-4"><span class="fw-bold text-warning">' . ($dept['active_targets'] ?? 0) . '</span><br><span style="font-size:9px">Active</span></div>';
        $html .= '      <div class="col-4"><span class="fw-bold text-danger">' . ($dept['missed_targets'] ?? 0) . '</span><br><span style="font-size:9px">Missed</span></div>';
        $html .= '    </div>';
        $html .= '    <div class="mt-2 pt-2 border-top small">';
        $html .= '      <span class="text-muted">Staff: ' . ($dept['staff_count'] ?? 0) . '</span>';
        if ($problems > 0) $html .= '      <span class="badge bg-danger ms-2" style="font-size:8px">' . $problems . ' issue(s)</span>';
        $html .= '    </div>';
        $html .= '  </div>';
        $html .= '</div>';
    }
    $html .= '</div>';
    return $html;
}
}

if (!function_exists('renderComplianceSummary')) {
function renderComplianceSummary($staffConn) {
    $status = getComplianceStatus($staffConn);
    $html = '<div class="compliance-grid">';
    $html .= '  <div class="compliance-item bg-success-subtle">';
    $html .= '    <span class="fw-bold text-success">' . $status['compliant'] . '</span>';
    $html .= '    <span class="small">Compliant</span>';
    $html .= '  </div>';
    $html .= '  <div class="compliance-item bg-warning-subtle">';
    $html .= '    <span class="fw-bold text-warning">' . $status['in_progress'] . '</span>';
    $html .= '    <span class="small">In Progress</span>';
    $html .= '  </div>';
    $html .= '  <div class="compliance-item bg-danger-subtle">';
    $html .= '    <span class="fw-bold text-danger">' . ($status['non_compliant'] + $status['overdue']) . '</span>';
    $html .= '    <span class="small">Issues</span>';
    $html .= '  </div>';
    $html .= '  <div class="compliance-item bg-info-subtle">';
    $html .= '    <span class="fw-bold text-info">' . $status['not_started'] . '</span>';
    $html .= '    <span class="small">Not Started</span>';
    $html .= '  </div>';
    $html .= '</div>';
    return $html;
}
}

if (!function_exists('renderRiskRegister')) {
function renderRiskRegister($staffConn, $limit = 5) {
    $risks = getTopRisks($staffConn, $limit);
    if (empty($risks)) return '<div class="text-muted small py-2">No active risks registered.</div>';
    $html = '<div class="risk-list small">';
    foreach ($risks as $risk) {
        $html .= '<div class="risk-item d-flex justify-content-between align-items-center py-2 border-bottom">';
        $html .= '  <div>';
        $html .= '    <div class="fw-semibold">' . htmlspecialchars($risk['risk_title']) . '</div>';
        $html .= '    <span class="text-muted">' . htmlspecialchars($risk['risk_category']) . '</span>';
        $html .= '  </div>';
        $html .= '  <div class="text-end">';
        $html .= renderRiskBadge($risk['risk_score']);
        $html .= '    <div class="text-muted" style="font-size:9px">' . htmlspecialchars($risk['likelihood']) . ' / ' . htmlspecialchars($risk['impact']) . '</div>';
        $html .= '  </div>';
        $html .= '</div>';
    }
    $html .= '</div>';
    return $html;
}
}
