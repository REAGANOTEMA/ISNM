<?php
/**
 * Enterprise Dashboard Analytics & KPI Center
 * Live SQL-driven analytics, auto-refresh, executive KPIs, health score, AI insights.
 * Includes: KPI cards, Revenue vs Expenses, Payment Methods, Staff Attendance,
 *           Health Score, AI Insights, Revenue Forecast, Live Updates.
 */
if (session_status() === PHP_SESSION_NONE) session_start();
if (isset($GLOBALS['_dashboard_analytics_loaded'])) return;
$GLOBALS['_dashboard_analytics_loaded'] = true;

// ─── KPI Colors ───
$KPI_COLORS = [
    'blue'   => ['bg'=>'#eef2ff','icon'=>'#4f46e5','text'=>'#1e1b4b','border'=>'#4f46e5'],
    'green'  => ['bg'=>'#f0fdf4','icon'=>'#16a34a','text'=>'#052e16','border'=>'#16a34a'],
    'teal'   => ['bg'=>'#ecfeff','icon'=>'#0d9488','text'=>'#083344','border'=>'#0d9488'],
    'red'    => ['bg'=>'#fef2f2','icon'=>'#dc2626','text'=>'#450a0a','border'=>'#dc2626'],
    'orange' => ['bg'=>'#fffbeb','icon'=>'#d97706','text'=>'#451a03','border'=>'#d97706'],
    'purple' => ['bg'=>'#f5f3ff','icon'=>'#7c3aed','text'=>'#2e1065','border'=>'#7c3aed'],
    'pink'   => ['bg'=>'#fdf2f8','icon'=>'#db2777','text'=>'#4a051c','border'=>'#db2777'],
    'indigo' => ['bg'=>'#eef2ff','icon'=>'#6366f1','text'=>'#172554','border'=>'#6366f1'],
];

// ─── Helper: Format UGX ───
if (!function_exists('fmtUgx')) {
function fmtUgx($val) {
    if ($val >= 1000000) return 'UGX ' . number_format($val / 1000000, 1) . 'M';
    if ($val >= 1000) return 'UGX ' . number_format($val / 1000, 1) . 'K';
    return 'UGX ' . number_format($val);
}
}

// ─── Helper: Trend direction ───
if (!function_exists('trendDir')) {
function trendDir($current, $previous) {
    if ($previous <= 0) return ['dir'=>'up','pct'=>0,'icon'=>'fa-minus','color'=>'#64748b'];
    if ($previous < 5) return ['dir'=>'up','pct'=>0,'icon'=>'fa-minus','color'=>'#64748b'];
    $pct = round((($current - $previous) / $previous) * 100, 1);
    if ($pct > 999) $pct = 999;
    if ($pct < -999) $pct = -999;
    if ($pct > 0) return ['dir'=>'up','pct'=>$pct,'icon'=>'fa-arrow-up','color'=>'#16a34a'];
    if ($pct < 0) return ['dir'=>'down','pct'=>abs($pct),'icon'=>'fa-arrow-down','color'=>'#dc2626'];
    return ['dir'=>'flat','pct'=>0,'icon'=>'fa-minus','color'=>'#64748b'];
}
}

// ─── Get ALL analytics data ───
if (!function_exists('getAnalyticsData')) {
function getAnalyticsData($conn, $studentsConn, $websiteConn) {
    $data = [
        'students' => ['total'=>0,'active'=>0,'new_this_month'=>0,'trend'=>[]],
        'staff' => ['total'=>0,'active'=>0,'new_this_month'=>0,'by_department'=>[]],
        'finance' => ['today_collection'=>0,'week_collection'=>0,'month_collection'=>0,'total_revenue'=>0,'total_expenses'=>0,'outstanding'=>0,'monthly'=>[]],
        'attendance' => ['present'=>0,'absent'=>0,'late'=>0,'on_leave'=>0,'pct'=>0,'total'=>0],
        'applications' => ['total'=>0,'pending'=>0,'trend'=>[]],
        'payment_methods' => ['labels'=>[],'values'=>[],'total'=>0],
        'health' => ['score'=>0,'breakdown'=>[]],
        'insights' => [],
        'forecast' => ['current_month'=>0,'next_month'=>0,'next_quarter'=>0,'surplus'=>0],
        'last_updated' => time(),
    ];

    // ── Students ──
    if ($studentsConn) {
        try {
            $r = $studentsConn->query("SELECT COUNT(*) c FROM students"); if ($r) $data['students']['total'] = (int)$r->fetch_assoc()['c'];
            $r = $studentsConn->query("SELECT COUNT(*) c FROM students WHERE status='Active'"); if ($r) $data['students']['active'] = (int)$r->fetch_assoc()['c'];
            $r = $studentsConn->query("SELECT COUNT(*) c FROM students WHERE MONTH(created_at)=MONTH(CURDATE()) AND YEAR(created_at)=YEAR(CURDATE())"); if ($r) $data['students']['new_this_month'] = (int)$r->fetch_assoc()['c'];
            // Monthly trend (last 6 months)
            for ($i = 5; $i >= 0; $i--) {
                $m = date('Y-m', strtotime("-$i months"));
                $r = $studentsConn->query("SELECT COUNT(*) c FROM students WHERE DATE_FORMAT(created_at,'%Y-%m')='$m'");
                $data['students']['trend'][] = $r ? (int)$r->fetch_assoc()['c'] : 0;
            }
        } catch (Exception $e) {}
    }

    // ── Staff ──
    if ($conn) {
        try {
            $r = $conn->query("SELECT COUNT(*) c FROM staff WHERE status='Active'"); if ($r) $data['staff']['active'] = (int)$r->fetch_assoc()['c'];
            $r = $conn->query("SELECT COUNT(*) c FROM staff"); if ($r) $data['staff']['total'] = (int)$r->fetch_assoc()['c'];
            $r = $conn->query("SELECT COUNT(*) c FROM staff WHERE MONTH(created_at)=MONTH(CURDATE()) AND YEAR(created_at)=YEAR(CURDATE())"); if ($r) $data['staff']['new_this_month'] = (int)$r->fetch_assoc()['c'];
            // Staff by department
            $r = $conn->query("SELECT COALESCE(department,'General') dept, COUNT(*) c FROM staff WHERE status='Active' GROUP BY dept ORDER BY c DESC LIMIT 10");
            if ($r) while ($row = $r->fetch_assoc()) $data['staff']['by_department'][] = $row;
        } catch (Exception $e) {}
    }

    // ── Finance ──
    if ($conn) {
        try {
            $r = $conn->query("SELECT COALESCE(SUM(amount_received),0) v FROM igangaschoolofl_students_db.payments WHERE DATE(payment_date)=CURDATE() AND status IN('verified','approved')");
            if ($r) $data['finance']['today_collection'] = (float)$r->fetch_assoc()['v'];
            $r = $conn->query("SELECT COALESCE(SUM(amount_received),0) v FROM igangaschoolofl_students_db.payments WHERE YEARWEEK(payment_date)=YEARWEEK(CURDATE()) AND status IN('verified','approved')");
            if ($r) $data['finance']['week_collection'] = (float)$r->fetch_assoc()['v'];
            $r = $conn->query("SELECT COALESCE(SUM(amount_received),0) v FROM igangaschoolofl_students_db.payments WHERE MONTH(payment_date)=MONTH(CURDATE()) AND YEAR(payment_date)=YEAR(CURDATE()) AND status IN('verified','approved')");
            if ($r) $data['finance']['month_collection'] = (float)$r->fetch_assoc()['v'];
            $r = $conn->query("SELECT COALESCE(SUM(amount_received),0) v FROM igangaschoolofl_students_db.payments WHERE status IN('verified','approved')");
            if ($r) $data['finance']['total_revenue'] = (float)$r->fetch_assoc()['v'];
            $r = $conn->query("SELECT COALESCE(SUM(amount),0) v FROM expenses WHERE status IN('approved','paid')");
            if ($r) $data['finance']['total_expenses'] = (float)$r->fetch_assoc()['v'];
            $r = $conn->query("SELECT COALESCE(SUM(balance),0) v FROM student_invoices WHERE status IN('pending','partial','overdue')");
            if ($r) $data['finance']['outstanding'] = (float)$r->fetch_assoc()['v'];
            // Monthly finance trend (last 6 months)
            for ($i = 5; $i >= 0; $i--) {
                $mStart = date('Y-m-01', strtotime("-$i months"));
                $mEnd = date('Y-m-t', strtotime("-$i months"));
                $rev = 0; $exp = 0;
                $r = $conn->query("SELECT COALESCE(SUM(amount_received),0) v FROM igangaschoolofl_students_db.payments WHERE payment_date BETWEEN '$mStart' AND '$mEnd' AND status IN('verified','approved')");
                if ($r) $rev = (float)$r->fetch_assoc()['v'];
                $r = $conn->query("SELECT COALESCE(SUM(amount),0) v FROM expenses WHERE expense_date BETWEEN '$mStart' AND '$mEnd' AND status IN('approved','paid')");
                if ($r) $exp = (float)$r->fetch_assoc()['v'];
                $data['finance']['monthly'][] = ['month'=>date('M Y', strtotime("-$i months")), 'revenue'=>$rev, 'expenses'=>$exp];
            }
        } catch (Exception $e) {}
    }

    // ── Payment Methods ──
    if ($conn) {
        try {
            $r = $conn->query("SELECT COALESCE(payment_method,'Other') method, COUNT(*) cnt, COALESCE(SUM(amount_received),0) total FROM igangaschoolofl_students_db.payments WHERE status IN('verified','approved') GROUP BY payment_method ORDER BY total DESC");
            if ($r) {
                while ($row = $r->fetch_assoc()) {
                    $data['payment_methods']['labels'][] = $row['method'] ?: 'Other';
                    $data['payment_methods']['values'][] = (float)$row['total'];
                    $data['payment_methods']['total'] += (float)$row['total'];
                }
            }
            // Fallback if no payment data
            if (empty($data['payment_methods']['labels'])) {
                $data['payment_methods']['labels'] = ['No Data'];
                $data['payment_methods']['values'] = [0];
            }
        } catch (Exception $e) {}
    }

    // ── Attendance ──
    if ($conn) {
        try {
            $r = $conn->query("SELECT status, COUNT(*) cnt FROM staff_attendance WHERE DATE(date)=CURDATE() GROUP BY status");
            if ($r) {
                while ($row = $r->fetch_assoc()) {
                    $k = strtolower(str_replace(' ', '_', $row['status']));
                    if (in_array($k, ['present','absent','late','on_leave'])) $data['attendance'][$k] = (int)$row['cnt'];
                }
            }
            $data['attendance']['total'] = array_sum([$data['attendance']['present'],$data['attendance']['absent'],$data['attendance']['late'],$data['attendance']['on_leave']]);
            $data['attendance']['pct'] = $data['attendance']['total'] > 0 ? round(($data['attendance']['present'] / $data['attendance']['total']) * 100) : 0;
        } catch (Exception $e) {}
    }

    // ── Applications ──
    if ($websiteConn) {
        try {
            $r = $websiteConn->query("SELECT COUNT(*) c FROM student_applications"); if ($r) $data['applications']['total'] = (int)$r->fetch_assoc()['c'];
            $r = $websiteConn->query("SELECT COUNT(*) c FROM student_applications WHERE status IN('Pending','Submitted','Under Review')"); if ($r) $data['applications']['pending'] = (int)$r->fetch_assoc()['c'];
            for ($i = 5; $i >= 0; $i--) {
                $m = date('Y-m', strtotime("-$i months"));
                $r = $websiteConn->query("SELECT COUNT(*) c FROM student_applications WHERE DATE_FORMAT(submitted_at,'%Y-%m')='$m'");
                $data['applications']['trend'][] = $r ? (int)$r->fetch_assoc()['c'] : 0;
            }
        } catch (Exception $e) {}
    }

    // ── Health Score ──
    $hs = ['enrollment'=>0, 'fee_collection'=>0, 'attendance'=>0, 'staff_availability'=>0, 'admission_growth'=>0];
    $hs['enrollment'] = $data['students']['total'] > 0 ? min(100, round(($data['students']['active'] / max(1, $data['students']['total'])) * 100)) : 0;
    $hs['fee_collection'] = $data['finance']['total_revenue'] > 0 ? min(100, round(($data['finance']['month_collection'] / max(1, $data['finance']['total_revenue'] / 12)) * 100)) : 0;
    $hs['attendance'] = $data['attendance']['pct'];
    $hs['staff_availability'] = $data['staff']['total'] > 0 ? round(($data['staff']['active'] / $data['staff']['total']) * 100) : 0;
    // Admission growth: compare last 3 months vs previous 3
    $recent3 = array_slice($data['applications']['trend'], -3);
    $prev3 = array_slice($data['applications']['trend'], 0, 3);
    $recentSum = array_sum($recent3); $prevSum = array_sum($prev3);
    $hs['admission_growth'] = $prevSum > 0 ? min(100, round(($recentSum / $prevSum) * 50)) : 50;
    $data['health']['breakdown'] = $hs;
    $data['health']['score'] = round(array_sum($hs) / count($hs));

    // ── AI Insights ──
    $insights = [];
    // Enrollment insight
    if ($data['students']['new_this_month'] > 0) {
        $prevMonth = 0;
        if ($studentsConn) {
            $q = $studentsConn->query("SELECT COUNT(*) c FROM students WHERE MONTH(created_at)=MONTH(DATE_SUB(CURDATE(), INTERVAL 1 MONTH)) AND YEAR(created_at)=YEAR(DATE_SUB(CURDATE(), INTERVAL 1 MONTH))");
            if ($q) $prevMonth = (int)$q->fetch_assoc()['c'];
        }
        $pct = $prevMonth > 0 ? round((($data['students']['new_this_month'] - $prevMonth) / $prevMonth) * 100) : 0;
        $insights[] = ['title'=>($pct>=0?'Enrollment Up':'Enrollment Down'), 'message'=>($pct>=0?"Enrollment increased by $pct% this month with {$data['students']['new_this_month']} new students.":"Enrollment decreased by ".abs($pct)."% this month with {$data['students']['new_this_month']} new students."), 'icon'=>'fa-user-graduate', 'priority'=>abs($pct) > 20 ? 'high' : (abs($pct) > 5 ? 'medium' : 'low'), 'confidence'=>min(95, 70 + abs($pct))];
    }
    // Fee insight
    if ($data['finance']['outstanding'] > 0) {
        $insights[] = ['title'=>'Outstanding Fees Alert', 'message'=>'Outstanding fees total ' . fmtUgx($data['finance']['outstanding']) . '. Review collection strategy.', 'icon'=>'fa-exclamation-triangle', 'priority'=>$data['finance']['outstanding'] > 10000000 ? 'high' : 'medium', 'confidence'=>85];
    }
    // Attendance insight
    if ($data['attendance']['total'] > 0) {
        $insights[] = ['title'=>'Staff Attendance '.($data['attendance']['pct']>=80 ? 'Good' : 'Needs Improvement'), 'message'=>"{$data['attendance']['pct']}% attendance today. " . ($data['attendance']['pct'] < 80 ? "Below 80% target." : "Meeting target."), 'icon'=>'fa-user-clock', 'priority'=>$data['attendance']['pct'] < 80 ? 'high' : 'low', 'confidence'=>90];
    }
    // Admission trend insight
    if (!empty($data['applications']['trend'])) {
        $avg = array_sum($data['applications']['trend']) / max(1, count($data['applications']['trend']));
        if ($data['applications']['pending'] > $avg) {
            $insights[] = ['title'=>'Admissions Trending Up', 'message'=>'Application volume is above average. ' . $data['applications']['pending'] . ' pending reviews.', 'icon'=>'fa-file-signature', 'priority'=>'medium', 'confidence'=>80];
        }
    }
    // Revenue insight
    if ($data['finance']['total_revenue'] > 0 && $data['finance']['total_expenses'] > 0) {
        $profit = $data['finance']['total_revenue'] - $data['finance']['total_expenses'];
        $insights[] = ['title'=>($profit>=0?'Positive Cash Flow':'Budget Deficit'), 'message'=>($profit>=0?"Surplus of ".fmtUgx($profit)." — financially stable.":"Deficit of ".fmtUgx(abs($profit)).". Review expenditure."), 'icon'=>'fa-chart-line', 'priority'=>($profit<0?'high':'low'), 'confidence'=>88];
    }
    $data['insights'] = $insights;

    // ── Revenue Forecast ──
    if (!empty($data['finance']['monthly'])) {
        $revenues = array_column($data['finance']['monthly'], 'revenue');
        $avgRev = count($revenues) > 0 ? array_sum($revenues) / count($revenues) : 0;
        $data['forecast']['current_month'] = end($revenues) ?: 0;
        $trend = trendDir(end($revenues) ?: 0, prev($revenues) ?: 0);
        $factor = 1 + ($trend['dir'] === 'up' ? $trend['pct']/100 : ($trend['dir'] === 'down' ? -$trend['pct']/100 : 0));
        $data['forecast']['next_month'] = round($data['forecast']['current_month'] * max(0.5, $factor));
        $data['forecast']['next_quarter'] = round($data['forecast']['next_month'] * 3 * max(0.5, $factor));
        $avgExp = array_sum(array_column($data['finance']['monthly'], 'expenses')) / max(1, count($data['finance']['monthly']));
        $data['forecast']['surplus'] = $data['forecast']['next_month'] - round($avgExp);
    }

    $data['last_updated'] = time();
    return $data;
}
}

// ─── Render Analytics Successfully ───
if (!function_exists('renderAdminAnalytics')) {
function renderAdminAnalytics($conn, $studentsConn, $websiteConn) {
    global $KPI_COLORS;
    $d = getAnalyticsData($conn, $studentsConn, $websiteConn);
    $GLOBALS['_analytics_data'] = $d;
    $GLOBALS['KPI_COLORS'] = $KPI_COLORS ?? [];
    ob_start();
    ?>
    <div class="aa-dashboard">
    <!-- ═══ ANALYTICS STYLES ═══ -->
    <style>
    .aa-dashboard { padding: 0; }
    .aa-kpi-grid { display: grid; grid-template-columns: repeat(6, 1fr); gap: 16px; margin-bottom: 20px; }
    .aa-kpi-card { background:#fff; border:1px solid #e2e8f0; border-radius:14px; padding:20px 18px; transition:all 0.25s; position:relative; }
    .aa-kpi-card:hover { box-shadow:0 4px 16px rgba(0,0,0,0.06); transform:translateY(-1px); }
    .aa-kpi-card .aa-top { display:flex; align-items:flex-start; justify-content:space-between; margin-bottom:8px; }
    .aa-kpi-card .aa-icon { width:38px;height:38px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:17px;flex-shrink:0; }
    .aa-kpi-card .aa-badge { font-size:9px;font-weight:600;padding:2px 8px;border-radius:10px;white-space:nowrap; max-width:100%; overflow:hidden; text-overflow:ellipsis; }
    .aa-kpi-card .aa-value { font-size:22px;font-weight:800;color:#0f172a;line-height:1.2;letter-spacing:-0.3px; word-break:break-word; }
    .aa-kpi-card .aa-label { font-size:11px;color:#64748b;font-weight:500;margin-top:2px; }
    .aa-kpi-card .aa-footer { display:flex;align-items:center;justify-content:space-between;margin-top:12px;padding-top:10px;border-top:1px solid #f1f5f9; gap:8px; flex-wrap:wrap; }
    .aa-kpi-card .aa-trend { font-size:11px;font-weight:600;display:inline-flex;align-items:center;gap:4px; min-width:0; }
    .aa-kpi-card .aa-time { font-size:10px;color:#94a3b8;flex-shrink:0; }
    .aa-kpi-card .aa-minichart { height:24px;margin-top:6px;opacity:0.6; }
    .aa-kpi-card .aa-live-dot { width:6px;height:6px;border-radius:50%;background:#16a34a;display:inline-block;margin-right:4px;animation:aaPulse 2s infinite; }
    @keyframes aaPulse { 0%,100%{opacity:1} 50%{opacity:0.4} }

    .aa-row { display:grid; grid-template-columns: 2fr 1fr; gap:16px; margin-bottom:20px; }
    .aa-card { background:#fff; border:1px solid #e2e8f0; border-radius:14px; padding:20px; }
    .aa-card-title { font-size:13px;font-weight:700;color:#0f172a;margin-bottom:14px;display:flex;align-items:center;gap:8px; }
    .aa-card-title i { font-size:15px; }

    .aa-health { display:flex;align-items:center;gap:24px; }
    .aa-health-circle { position:relative;width:120px;height:120px;flex-shrink:0; }
    .aa-health-circle canvas { width:120px;height:120px; }
    .aa-health-score { position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);text-align:center; }
    .aa-health-score .val { font-size:28px;font-weight:800;color:#0f172a;line-height:1; }
    .aa-health-score .lbl { font-size:9px;color:#94a3b8;text-transform:uppercase;letter-spacing:0.3px; }
    .aa-health-breakdown { flex:1;display:grid;grid-template-columns:1fr 1fr;gap:8px; }
    .aa-hb-item { padding:10px 12px;border-radius:8px;background:#f8fafc;border:1px solid #f1f5f9; }
    .aa-hb-item .bar { height:4px;border-radius:2px;background:#e2e8f0;margin-top:6px;overflow:hidden; }
    .aa-hb-item .bar-fill { height:100%;border-radius:2px;transition:width 1s ease; }
    .aa-hb-item .lbl { font-size:10px;color:#64748b; }
    .aa-hb-item .val { font-size:13px;font-weight:700;color:#0f172a; }

    .aa-insight { padding:14px 16px;border-radius:10px;border:1px solid #f1f5f9;background:#fafbfc;margin-bottom:10px;display:flex;gap:12px;align-items:flex-start;transition:all 0.2s; }
    .aa-insight:hover { border-color:#cbd5e1;background:#fff; }
    .aa-insight .ii-icon { width:32px;height:32px;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:14px;flex-shrink:0; }
    .aa-insight .ii-body { flex:1;min-width:0; }
    .aa-insight .ii-title { font-size:12px;font-weight:700;color:#0f172a; }
    .aa-insight .ii-msg { font-size:11px;color:#64748b;margin-top:2px;line-height:1.4; }
    .aa-insight .ii-meta { display:flex;gap:8px;margin-top:6px;font-size:10px;flex-wrap:wrap; }
    .aa-insight .ii-priority { padding:1px 6px;border-radius:4px;font-weight:600; }
    .aa-insight .ii-confidence { color:#94a3b8; }

    .aa-forecast-grid { display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:16px; }
    .aa-fc-item { text-align:center;padding:14px 8px;border-radius:10px;background:#f8fafc;border:1px solid #f1f5f9; }
    .aa-fc-item .fc-val { font-size:18px;font-weight:800;color:#0f172a;word-break:break-word; }
    .aa-fc-item .fc-lbl { font-size:10px;color:#64748b;margin-top:3px; }
    .aa-fc-item .fc-trend { font-size:10px;font-weight:600;margin-top:2px; }
    .aa-empty-state { text-align:center;padding:32px 24px;color:#94a3b8;font-size:13px; }
    .aa-empty-state i { font-size:32px;margin-bottom:10px;color:#cbd5e1;display:block; }

    .aa-live-bar { display:flex;align-items:center;gap:12px;padding:10px 16px;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;margin-bottom:20px;font-size:12px;color:#166534; flex-wrap:wrap; }
    .aa-live-bar .aa-refresh-btn { margin-left:auto;padding:5px 14px;border-radius:6px;border:1px solid #86efac;background:#fff;color:#166534;font-size:11px;font-weight:600;cursor:pointer;transition:all 0.15s; }
    .aa-live-bar .aa-refresh-btn:hover { background:#dcfce7; }

    .aa-chart-container { position:relative;height:240px; }
    .aa-chart-container canvas { width:100% !important; height:100% !important; }

    @media (max-width:1400px) { .aa-kpi-grid { grid-template-columns: repeat(3,1fr); } }
    @media (max-width:992px) { .aa-kpi-grid { grid-template-columns: repeat(2,1fr); gap:12px; } .aa-row { grid-template-columns:1fr; } .aa-forecast-grid { grid-template-columns:repeat(2,1fr); } }
    @media (max-width:576px) { .aa-kpi-grid { grid-template-columns:1fr 1fr;gap:10px; } .aa-forecast-grid { grid-template-columns:1fr 1fr; } .aa-health { flex-direction:column; } .aa-health-breakdown { grid-template-columns:1fr; } .aa-kpi-card { padding:14px 12px; } .aa-kpi-card .aa-value { font-size:18px; } .aa-card { padding:14px; } }
    </style>

    <!-- ═══ LIVE BAR ═══ -->
    <div class="aa-live-bar" id="aaLiveBar">
        <span class="aa-live-dot"></span>
        <span><strong>Live Analytics</strong> — <span id="aaLastUpdated">just now</span></span>
        <span style="color:#64748b;font-size:11px;" id="aaNextRefresh">Auto-refresh in 30s</span>
        <button class="aa-refresh-btn" onclick="aaRefresh()"><i class="fas fa-sync-alt me-1"></i>Refresh Now</button>
    </div>

    <!-- ═══ KPI GRID ═══ -->
    <div class="aa-kpi-grid" id="aaKpiGrid">
        <?php
        $sectionMap = ['students'=>'student','staff'=>'staff','collection'=>'financial','outstanding'=>'financial','applications'=>'services','attendance'=>'staff'];
        $kpis = [
            ['key'=>'students','label'=>'Total Students','icon'=>'fa-user-graduate','color'=>'blue','value'=>number_format($d['students']['total']),'trend'=>trendDir($d['students']['new_this_month'], max(1, ($d['students']['trend'][count($d['students']['trend'])-2]??0))),'badge'=>$d['students']['active'].' Active','mini'=>$d['students']['trend']],
            ['key'=>'staff','label'=>'Total Staff','icon'=>'fa-users','color'=>'green','value'=>number_format($d['staff']['total']),'trend'=>trendDir($d['staff']['active'], max(1,$d['staff']['total'])),'badge'=>$d['staff']['active'].' Active','mini'=>[]],
            ['key'=>'collection','label'=>"Today's Collection",'icon'=>'fa-money-bill-wave','color'=>'teal','value'=>fmtUgx($d['finance']['today_collection']),'trend'=>trendDir($d['finance']['today_collection'], max(1,$d['finance']['week_collection']/7)),'badge'=>fmtUgx($d['finance']['month_collection']).' This Month','mini'=>[]],
            ['key'=>'outstanding','label'=>'Outstanding Fees','icon'=>'fa-exclamation-triangle','color'=>'red','value'=>fmtUgx($d['finance']['outstanding']),'trend'=>trendDir($d['finance']['outstanding'], max(1,$d['finance']['total_revenue']/12)),'badge'=>($d['finance']['outstanding']>0?'Needs Attention':'Fully Paid'),'mini'=>[]],
            ['key'=>'applications','label'=>'Applications','icon'=>'fa-file-alt','color'=>'orange','value'=>number_format($d['applications']['total']),'trend'=>trendDir($d['applications']['pending'], max(1,end($d['applications']['trend'])?:1)),'badge'=>$d['applications']['pending'].' Pending','mini'=>$d['applications']['trend']],
            ['key'=>'attendance','label'=>'Staff Attendance','icon'=>'fa-user-clock','color'=>'purple','value'=>$d['attendance']['pct'].'%','trend'=>trendDir($d['attendance']['present'], max(1,$d['attendance']['total'])),'badge'=>$d['attendance']['present'].' Present Today','mini'=>[]],
        ];
        foreach ($kpis as $k):
            $c = $GLOBALS['KPI_COLORS'][$k['color']] ?? ($KPI_COLORS ?: [])['blue'];
            $sectionTarget = $sectionMap[$k['key']] ?? '';
        ?>
        <div class="aa-kpi-card"<?= $sectionTarget ? ' onclick="switchToSection(\'' . $sectionTarget . '\');return false;" title="Click to view ' . htmlspecialchars($k['label']) . '" style="cursor:pointer;"' : '' ?>>
            <div class="aa-top">
                <div class="aa-icon" style="background:<?=$c['bg']?>;color:<?=$c['icon']?>;"><i class="fas <?=$k['icon']?>"></i></div>
                <span class="aa-badge" style="background:<?=$k['trend']['dir']==='up'?'#dcfce7':($k['trend']['dir']==='down'?'#fee2e2':'#f1f5f9')?>;color:<?=$k['trend']['color']?>;"><i class="fas <?=$k['trend']['icon']?> me-1"></i><?=$k['trend']['pct']?>%</span>
            </div>
            <div class="aa-value"><?=$k['value']?></div>
            <div class="aa-label"><?=$k['label']?></div>
            <div class="aa-footer">
                <span class="aa-trend" style="color:<?=$k['trend']['color']?>;"><i class="fas <?=$k['trend']['icon']?>"></i> <?=$k['badge']?></span>
                <span class="aa-time"><span class="aa-live-dot"></span>Live</span>
            </div>
            <?php if (!empty($k['mini'])): ?>
            <div class="aa-minichart" id="miniChart_<?=$k['key']?>"></div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- ═══ MAIN ROW: Revenue vs Expenses + Health ═══ -->
    <div class="aa-row">
        <div class="aa-card">
            <div class="aa-card-title"><i class="fas fa-chart-line" style="color:#3b82f6;"></i>Revenue vs Expenses</div>
            <?php if (!empty($d['finance']['monthly'])): ?>
            <div class="aa-chart-container"><canvas id="aaChartRevenue"></canvas></div>
            <?php else: ?>
            <div class="aa-empty-state"><i class="fas fa-chart-line"></i>No financial data available yet.</div>
            <?php endif; ?>
        </div>
        <div class="aa-card">
            <div class="aa-card-title"><i class="fas fa-heartbeat" style="color:#ef4444;"></i>Institution Health Index</div>
            <?php if ($d['health']['score'] > 0): ?>
            <div class="aa-health">
                <div class="aa-health-circle">
                    <canvas id="aaHealthGauge"></canvas>
                    <div class="aa-health-score"><div class="val" style="color:<?=$d['health']['score']>=90?'#16a34a':($d['health']['score']>=75?'#3b82f6':($d['health']['score']>=50?'#d97706':'#dc2626'))?>"><?=$d['health']['score']?></div><div class="lbl">/100</div></div>
                </div>
                <div class="aa-health-breakdown">
                    <?php foreach ($d['health']['breakdown'] as $hk=>$hv): $hp = $hv >= 90 ? '#16a34a' : ($hv >= 75 ? '#3b82f6' : ($hv >= 50 ? '#d97706' : '#dc2626')); ?>
                    <div class="aa-hb-item"><div class="lbl"><?=ucwords(str_replace('_',' ',$hk))?></div><div class="val"><?=$hv?>%</div><div class="bar"><div class="bar-fill" style="width:<?=$hv?>%;background:<?=$hp?>;"></div></div></div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div style="margin-top:12px;text-align:center;font-size:11px;color:<?=$d['health']['score']>=90?'#16a34a':($d['health']['score']>=75?'#3b82f6':($d['health']['score']>=50?'#d97706':'#dc2626'))?>;font-weight:600;">
                <?=$d['health']['score']>=90?'Excellent — Top performance across all metrics.':($d['health']['score']>=75?'Good — Stable with room for improvement.':($d['health']['score']>=50?'Fair — Several areas need attention.':'Needs Attention — Critical improvements required.'))?>
            </div>
            <?php else: ?>
            <div class="aa-empty-state"><i class="fas fa-heartbeat"></i>Health score requires sufficient data across all metrics.</div>
            <?php endif; ?>
        </div>
    </div>

    <!-- ═══ FORECAST + INSIGHTS ═══ -->
    <div class="aa-row">
        <div class="aa-card">
            <div class="aa-card-title"><i class="fas fa-chart-simple" style="color:#8b5cf6;"></i>Revenue Forecast</div>
            <?php if ($d['forecast']['current_month'] > 0): ?>
            <div class="aa-forecast-grid">
                <div class="aa-fc-item"><div class="fc-val" style="color:#3b82f6;"><?=fmtUgx($d['forecast']['current_month'])?></div><div class="fc-lbl">Current Month</div></div>
                <div class="aa-fc-item"><div class="fc-val" style="color:#8b5cf6;"><?=fmtUgx($d['forecast']['next_month'])?></div><div class="fc-lbl">Forecast Next Month</div></div>
                <div class="aa-fc-item"><div class="fc-val" style="color:#f59e0b;"><?=fmtUgx($d['forecast']['next_quarter'])?></div><div class="fc-lbl">Forecast Next Quarter</div></div>
                <div class="aa-fc-item"><div class="fc-val" style="color:<?=$d['forecast']['surplus']>=0?'#16a34a':'#dc2626'?>;"><?=fmtUgx(abs($d['forecast']['surplus']))?></div><div class="fc-lbl"><?=$d['forecast']['surplus']>=0?'Projected Surplus':'Projected Deficit'?></div></div>
            </div>
            <div class="aa-chart-container" style="height:160px;"><canvas id="aaChartForecast"></canvas></div>
            <?php else: ?>
            <div class="aa-empty-state"><i class="fas fa-chart-simple"></i>Forecast data available after sufficient transaction history.</div>
            <?php endif; ?>
        </div>
        <div class="aa-card">
            <div class="aa-card-title"><i class="fas fa-robot" style="color:#f59e0b;"></i>Executive Insights</div>
            <?php if (!empty($d['insights'])): ?>
            <?php foreach (array_slice($d['insights'], 0, 4) as $ins): $pc = $ins['priority']==='high'?'#dc2626':($ins['priority']==='medium'?'#d97706':'#3b82f6'); ?>
            <div class="aa-insight">
                <div class="ii-icon" style="background:<?=$pc?>15;color:<?=$pc?>;"><i class="fas <?=$ins['icon']?>"></i></div>
                <div class="ii-body">
                    <div class="ii-title"><?=$ins['title']?></div>
                    <div class="ii-msg"><?=$ins['message']?></div>
                    <div class="ii-meta">
                        <span class="ii-priority" style="background:<?=$pc?>20;color:<?=$pc?>;"><?=ucfirst($ins['priority'])?></span>
                        <span class="ii-confidence">Confidence: <?=$ins['confidence']?>%</span>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            <?php else: ?>
            <div class="aa-empty-state"><i class="fas fa-robot"></i>No insights available yet. Data will populate as metrics are recorded.</div>
            <?php endif; ?>
        </div>
    </div>

    <!-- ═══ CHARTS ROW: Payment Methods + Attendance ═══ -->
    <div class="aa-row" style="grid-template-columns: 1fr 1fr;">
        <div class="aa-card">
            <div class="aa-card-title"><i class="fas fa-chart-pie" style="color:#8b5cf6;"></i>Payment Methods</div>
            <?php if (!empty($d['payment_methods']['labels']) && $d['payment_methods']['labels'][0] !== 'No Data'): ?>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                <div class="aa-chart-container" style="height:200px;"><canvas id="aaChartPayments"></canvas></div>
                <div>
                    <?php foreach ($d['payment_methods']['labels'] as $i=>$lbl): $pct = $d['payment_methods']['total'] > 0 ? round(($d['payment_methods']['values'][$i]/$d['payment_methods']['total'])*100) : 0; ?>
                    <div style="padding:6px 0;border-bottom:1px solid #f1f5f9;font-size:12px;display:flex;justify-content:space-between;align-items:center;">
                        <span style="font-weight:600;color:#0f172a;"><?=htmlspecialchars($lbl)?></span>
                        <span><strong style="color:#0f172a;"><?=$pct?>%</strong> <span style="color:#94a3b8;font-size:11px;">(<?=fmtUgx($d['payment_methods']['values'][$i])?>)</span></span>
                    </div>
                    <?php endforeach; ?>
                    <?php $maxIdx = array_search(max($d['payment_methods']['values']), $d['payment_methods']['values']); ?>
                    <div style="margin-top:8px;font-size:11px;color:#64748b;text-align:center;">Most used: <strong style="color:#0f172a;"><?=htmlspecialchars($d['payment_methods']['labels'][$maxIdx] ?? 'N/A')?></strong></div>
                </div>
            </div>
            <?php else: ?>
            <div class="aa-empty-state"><i class="fas fa-chart-pie"></i>No payment records available.</div>
            <?php endif; ?>
        </div>
        <div class="aa-card">
            <div class="aa-card-title"><i class="fas fa-user-clock" style="color:#10b981;"></i>Staff Attendance</div>
            <?php if ($d['attendance']['total'] > 0): ?>
            <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:8px;margin-bottom:12px;">
                <div style="text-align:center;padding:10px;border-radius:8px;background:#f0fdf4;"><div style="font-size:20px;font-weight:800;color:#16a34a;"><?=$d['attendance']['present']?></div><div style="font-size:10px;color:#64748b;">Present</div></div>
                <div style="text-align:center;padding:10px;border-radius:8px;background:#fffbeb;"><div style="font-size:20px;font-weight:800;color:#d97706;"><?=$d['attendance']['late']?></div><div style="font-size:10px;color:#64748b;">Late</div></div>
                <div style="text-align:center;padding:10px;border-radius:8px;background:#fef2f2;"><div style="font-size:20px;font-weight:800;color:#dc2626;"><?=$d['attendance']['absent']?></div><div style="font-size:10px;color:#64748b;">Absent</div></div>
                <div style="text-align:center;padding:10px;border-radius:8px;background:#eff6ff;"><div style="font-size:20px;font-weight:800;color:#3b82f6;"><?=$d['attendance']['on_leave']?></div><div style="font-size:10px;color:#64748b;">On Leave</div></div>
            </div>
            <div class="aa-chart-container" style="height:120px;"><canvas id="aaChartAttendance"></canvas></div>
            <?php else: ?>
            <div class="aa-empty-state"><i class="fas fa-user-clock"></i>No attendance records available today.</div>
            <?php endif; ?>
        </div>
    </div>

    <!-- ═══ CHART.JS SCRIPTS ═══ -->
    <?php if (!empty($d['finance']['monthly'])): ?>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof Chart === 'undefined') {
            var s = document.createElement('script');
            s.src = 'https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js';
            s.onload = initAaCharts;
            document.head.appendChild(s);
        } else { initAaCharts(); }
    });
    function initAaCharts() {
        // Revenue vs Expenses
        var ctx1 = document.getElementById('aaChartRevenue');
        if (ctx1) {
            var monthly = <?=json_encode($d['finance']['monthly'])?>;
            new Chart(ctx1, { type:'bar', data:{
                labels: monthly.map(function(m){return m.month;}),
                datasets: [
                    { label:'Revenue', data: monthly.map(function(m){return m.revenue;}), backgroundColor:'rgba(59,130,246,0.6)', borderColor:'#3b82f6', borderWidth:1, borderRadius:4 },
                    { label:'Expenses', data: monthly.map(function(m){return m.expenses;}), backgroundColor:'rgba(239,68,68,0.5)', borderColor:'#ef4444', borderWidth:1, borderRadius:4 },
                ]
            }, options:{
                responsive:true, maintainAspectRatio:false,
                plugins:{ legend:{ position:'top', labels:{boxWidth:10,font:{size:10}} } },
                scales:{ y:{ beginAtZero:true, ticks:{ callback:function(v){return v>=1000000?(v/1000000)+'M':v>=1000?(v/1000)+'K':v;}, font:{size:10} } }, x:{ grid:{display:false}, ticks:{font:{size:9}} } }
            }});
        }
        // Health Gauge
        var ctx2 = document.getElementById('aaHealthGauge');
        if (ctx2) {
            var score = <?=$d['health']['score']?>;
            var color = score >= 90 ? '#16a34a' : (score >= 75 ? '#3b82f6' : (score >= 50 ? '#d97706' : '#dc2626'));
            new Chart(ctx2, { type:'doughnut', data:{
                datasets: [{
                    data: [score, 100-score],
                    backgroundColor: [color, '#e2e8f0'],
                    borderWidth: 0,
                    cutout: '80%',
                }]
            }, options:{
                responsive:true, maintainAspectRatio:false,
                plugins:{ tooltip:{enabled:false}, legend:{display:false} }
            }});
        }
        // Payment Methods
        var ctx3 = document.getElementById('aaChartPayments');
        if (ctx3) {
            var pm = <?=json_encode(['labels'=>$d['payment_methods']['labels'],'values'=>$d['payment_methods']['values']])?>;
            var pmColors = ['#3b82f6','#8b5cf6','#10b981','#f59e0b','#ef4444','#6366f1','#ec4899'];
            new Chart(ctx3, { type:'doughnut', data:{
                labels: pm.labels,
                datasets: [{ data: pm.values, backgroundColor: pmColors.slice(0,pm.labels.length), borderWidth:1, borderColor:'#fff' }]
            }, options:{
                responsive:true, maintainAspectRatio:false,
                plugins:{ legend:{ position:'bottom', labels:{boxWidth:10,font:{size:9},padding:8} } }
            }});
        }
        // Attendance
        var ctx4 = document.getElementById('aaChartAttendance');
        if (ctx4) {
            var att = <?=json_encode(['present'=>$d['attendance']['present'],'absent'=>$d['attendance']['absent'],'late'=>$d['attendance']['late'],'on_leave'=>$d['attendance']['on_leave']])?>;
            new Chart(ctx4, { type:'bar', data:{
                labels: ['Present','Late','Absent','On Leave'],
                datasets: [{
                    label: 'Today',
                    data: [att.present, att.late, att.absent, att.on_leave],
                    backgroundColor: ['#16a34a','#d97706','#dc2626','#3b82f6'],
                    borderRadius: 4,
                }]
            }, options:{
                responsive:true, maintainAspectRatio:false,
                plugins:{ legend:{display:false} },
                scales:{ y:{ beginAtZero:true, ticks:{stepSize:1,font:{size:10}} }, x:{ grid:{display:false}, ticks:{font:{size:9}} } }
            }});
        }
        // Forecast
        var ctx5 = document.getElementById('aaChartForecast');
        if (ctx5) {
            var monthly = <?=json_encode($d['finance']['monthly'])?>;
            var months = monthly.map(function(m){return m.month;});
            var revs = monthly.map(function(m){return m.revenue;});
            months.push('Forecast');
            revs.push(<?=$d['forecast']['next_month']?>);
            new Chart(ctx5, { type:'line', data:{
                labels: months,
                datasets: [{
                    label: 'Revenue',
                    data: revs,
                    borderColor: '#8b5cf6',
                    backgroundColor: 'rgba(139,92,246,0.1)',
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: function(ctx) {
                        return ctx.dataIndex === revs.length-1 ? '#f59e0b' : '#8b5cf6';
                    },
                    pointRadius: function(ctx) {
                        return ctx.dataIndex === revs.length-1 ? 6 : 3;
                    },
                    borderWidth: 2,
                }]
            }, options:{
                responsive:true, maintainAspectRatio:false,
                plugins:{
                    legend:{ position:'top', labels:{boxWidth:10,font:{size:10}} },
                    tooltip:{ callbacks:{ label:function(ctx){ return 'UGX ' + Number(ctx.raw).toLocaleString(); }}}
                },
                scales:{ y:{ beginAtZero:true, ticks:{ callback:function(v){return v>=1000000?(v/1000000)+'M':v>=1000?(v/1000)+'K':v;}, font:{size:10} } }, x:{ grid:{display:false}, ticks:{font:{size:9}} } }
            }});
        }
    }
    </script>
    <?php endif; ?>

    <!-- ═══ AUTO-REFRESH ═══ -->
    <script>
    var aaData = <?=json_encode($d)?>;
    var aaRefreshTimer = 30;
    var aaInterval = setInterval(function() {
        aaRefreshTimer--;
        var el = document.getElementById('aaNextRefresh');
        if (el) el.textContent = 'Auto-refresh in ' + aaRefreshTimer + 's';
        if (aaRefreshTimer <= 0) { aaRefresh(); }
    }, 1000);

    window.aaRefresh = function() {
        aaRefreshTimer = 30;
        document.getElementById('aaNextRefresh').textContent = 'Refreshing...';
        var xhr = new XMLHttpRequest();
        xhr.open('GET', '../includes/ajax_analytics_refresh.php?_=' + Date.now(), true);
        xhr.onload = function() {
            if (xhr.status === 200) {
                try {
                    var d = JSON.parse(xhr.responseText);
                    if (d.success) {
                        aaData = d.data;
                        window.location.reload();
                    }
                } catch(e) { window.location.reload(); }
            }
        };
        xhr.onerror = function() { window.location.reload(); };
        xhr.send();
    };
    </script>
    </div>
    <?php
    return ob_get_clean();
}
}
