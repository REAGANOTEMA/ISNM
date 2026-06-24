<?php
/**
 * Strategic Planning & KPI Dashboard for Director General / CEO
 * High-level institutional performance metrics against targets.
 * ── Uses parent-scope variables ──
 *   $conn         – staffs_db (mysqli)
 *   $studentsConn – students_db (mysqli)
 *   $websiteConn  – website_db (mysqli)
 */
if (session_status() === PHP_SESSION_NONE && php_sapi_name() !== 'cli') session_start();
?>
<style>
.skp-section-title {
    font-size:14px;font-weight:700;margin:0 0 6px 0;display:flex;align-items:center;gap:8px;color:#0f172a;
}
.skp-section-title i { font-size:15px; }
.skp-section-sub { font-size:11px;color:#64748b;margin:0 0 14px 0; }
.skp-progress { height:8px;border-radius:10px;background:#f1f5f9;overflow:hidden; }
.skp-progress-bar { height:100%;border-radius:10px;transition:width 0.8s ease; }
.skp-badge { display:inline-block;padding:2px 10px;border-radius:12px;font-size:10px;font-weight:600; }
.skp-badge-green { background:#dcfce7;color:#166534; }
.skp-badge-yellow { background:#fef3c7;color:#92400e; }
.skp-badge-red { background:#fee2e2;color:#991b1b; }
.skp-table { font-size:12px;margin-bottom:0;width:100%;border-collapse:collapse; }
.skp-table thead th { background:#f8fafc;font-weight:600;color:#64748b;text-transform:uppercase;font-size:10px;letter-spacing:0.4px;padding:8px 10px;border-bottom:2px solid #e2e8f0; }
.skp-table td { padding:8px 10px;vertical-align:middle;border-bottom:1px solid #f1f5f9; }
.skp-table tbody tr:hover { background:#f8fafc; }
.skp-table tbody tr:last-child td { border-bottom:none; }
.skp-card {
    background:#fff;border-radius:10px;box-shadow:0 1px 4px rgba(0,0,0,0.06);
    padding:16px 18px;margin-bottom:14px;border:1px solid #f1f5f9;transition:box-shadow 0.2s;
}
.skp-card:hover { box-shadow:0 4px 16px rgba(0,0,0,0.08); }
</style>

<?php
// ── Gather KPI Data ──
$kpi = [
    'total_students'       => 0,
    'total_staff'          => 0,
    'active_staff'         => 0,
    'revenue_ytd'          => 0,
    'enrollment_current'   => 0,
    'enrollment_target'    => 1500,
    'revenue_current'      => 0,
    'revenue_target'       => 500000000,
    'staff_current'        => 0,
    'staff_target'         => 80,
    'graduation_current'   => 0,
    'graduation_target'    => 400,
    'pass_rate'            => 0,
    'attendance_rate'      => 0,
    'app_conversion_rate'  => 0,
    'total_applications'   => 0,
    'admitted_applications'=> 0,
    'total_exams_graded'   => 0,
    'passed_exams'         => 0,
    'total_attendance'     => 0,
    'present_attendance'   => 0,
];

try {
    if ($studentsConn) {
        $r = $studentsConn->query("SELECT COUNT(*) c FROM students");
        if ($r) $kpi['total_students'] = (int)$r->fetch_assoc()['c'];
        $kpi['enrollment_current'] = $kpi['total_students'];

        $r = $studentsConn->query("SELECT COALESCE(SUM(amount_received),0) v FROM payments WHERE YEAR(payment_date)=YEAR(CURDATE()) AND status IN('verified','approved')");
        if ($r) $kpi['revenue_ytd'] = (float)$r->fetch_assoc()['v'];
        $kpi['revenue_current'] = $kpi['revenue_ytd'];

        // Pass rate from examination_records
        $r = $studentsConn->query("SELECT COUNT(*) t, SUM(CASE WHEN grade IN('A','B','C','D') THEN 1 ELSE 0 END) p FROM examination_records WHERE grade IS NOT NULL");
        if ($r) { $row=$r->fetch_assoc(); $kpi['total_exams_graded']=(int)$row['t']; $kpi['passed_exams']=(int)$row['p']; }
        $kpi['pass_rate'] = $kpi['total_exams_graded'] > 0 ? round($kpi['passed_exams']/$kpi['total_exams_graded']*100,1) : 0;

        // Graduation count (passed exams as proxy)
        $kpi['graduation_current'] = $kpi['passed_exams'];
    }

    if ($conn) {
        $r = $conn->query("SELECT COUNT(*) c FROM staff");
        if ($r) $kpi['total_staff'] = (int)$r->fetch_assoc()['c'];

        $r = $conn->query("SELECT COUNT(*) c FROM staff WHERE status='Active'");
        if ($r) $kpi['active_staff'] = (int)$r->fetch_assoc()['c'];
        $kpi['staff_current'] = $kpi['active_staff'];

        // Attendance rate
        $r = $conn->query("SELECT status,COUNT(*) cnt FROM staff_attendance WHERE DATE(date)=CURDATE() GROUP BY status");
        if ($r) while ($row=$r->fetch_assoc()) {
            $kpi['total_attendance'] += (int)$row['cnt'];
            if (strtolower($row['status']) === 'present') $kpi['present_attendance'] += (int)$row['cnt'];
        }
        $kpi['attendance_rate'] = $kpi['total_attendance'] > 0 ? round($kpi['present_attendance']/$kpi['total_attendance']*100,1) : 0;
    }

    if ($websiteConn) {
        $r = $websiteConn->query("SELECT COUNT(*) c FROM student_applications");
        if ($r) $kpi['total_applications'] = (int)$r->fetch_assoc()['c'];
        $r = $websiteConn->query("SELECT COUNT(*) c FROM student_applications WHERE status IN('Admitted','Approved')");
        if ($r) $kpi['admitted_applications'] = (int)$r->fetch_assoc()['c'];
    }
    $kpi['app_conversion_rate'] = $kpi['total_applications'] > 0 ? round($kpi['admitted_applications']/$kpi['total_applications']*100,1) : 0;
} catch (Exception $e) { error_log('dg_strategic_kpi data error: '.$e->getMessage()); }

$ratio = $kpi['total_staff'] > 0 ? round($kpi['total_students']/$kpi['total_staff'],1) : 0;

// ── Helper: progress bar with colour logic ──
function skpProgressBar($current, $target, $isPct = false) {
    $pct = $isPct ? min(100,max(0,$current)) : ($target > 0 ? min(100,round($current/$target*100)) : 0);
    $color = $pct >= 75 ? '#10b981' : ($pct >= 50 ? '#f59e0b' : '#ef4444');
    return '<div class="skp-progress"><div class="skp-progress-bar" style="width:'.$pct.'%;background:'.$color.';"></div></div><div style="font-size:11px;font-weight:600;margin-top:3px;color:'.$color.';">'.$pct.'%</div>';
}

function skpStatus($current, $target, $isPct = false) {
    $pct = $isPct ? $current : ($target > 0 ? round($current/$target*100) : 0);
    $onTrack = $isPct ? $current >= $target : $current >= $target;
    if ($onTrack) return '<span class="skp-badge skp-badge-green"><i class="fas fa-check-circle me-1"></i>On Track</span>';
    if ($pct >= 50) return '<span class="skp-badge skp-badge-yellow"><i class="fas fa-exclamation-triangle me-1"></i>Behind</span>';
    return '<span class="skp-badge skp-badge-red"><i class="fas fa-times-circle me-1"></i>Critical</span>';
}
?>

<!-- ════════════════════════════════════════════ -->
<!-- KPI HEADER ROW – 4 Big Number Cards          -->
<!-- ════════════════════════════════════════════ -->
<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:10px;margin-bottom:14px;">
    <?php
    $hdr = [
        ['icon'=>'fa-user-graduate','label'=>'Total Students','value'=>number_format($kpi['total_students']),'color'=>'#3b82f6','bg'=>'#eff6ff','bc'=>'#3b82f6'],
        ['icon'=>'fa-users','label'=>'Total Staff','value'=>number_format($kpi['total_staff']),'color'=>'#10b981','bg'=>'#ecfdf5','bc'=>'#10b981'],
        ['icon'=>'fa-coins','label'=>'Revenue YTD','value'=>'UGX '.number_format($kpi['revenue_ytd']),'color'=>'#06b6d4','bg'=>'#ecfeff','bc'=>'#06b6d4'],
        ['icon'=>'fa-scale-balanced','label'=>'Student:Staff Ratio','value'=>$ratio.':1','color'=>'#8b5cf6','bg'=>'#f5f3ff','bc'=>'#8b5cf6'],
    ];
    foreach ($hdr as $h):
    ?>
    <div style="background:#fff;border-radius:10px;padding:14px 14px 12px;box-shadow:0 1px 4px rgba(0,0,0,0.06);border-left:4px solid <?= $h['bc'] ?>;transition:all 0.25s ease;" onmouseover="this.style.transform='translateY(-2px)';this.style.boxShadow='0 4px 16px rgba(0,0,0,0.1)'" onmouseout="this.style.transform='';this.style.boxShadow=''">
        <div style="width:32px;height:32px;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:14px;background:<?= $h['bg'] ?>;color:<?= $h['color'] ?>;margin-bottom:6px;"><i class="fas <?= $h['icon'] ?>"></i></div>
        <div style="font-size:20px;font-weight:800;color:#0f172a;letter-spacing:-0.3px;line-height:1.2;"><?= $h['value'] ?></div>
        <div style="font-size:10px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:0.5px;margin-top:2px;"><?= $h['label'] ?></div>
    </div>
    <?php endforeach; ?>
</div>

<!-- ════════════════════════════════════════════ -->
<!-- TARGET TRACKING CARDS (with progress bars)   -->
<!-- ════════════════════════════════════════════ -->
<div class="row g-2 mb-3">
    <div class="col-md-3 col-6">
        <div class="skp-card text-center p-3">
            <div style="font-size:24px;color:#3b82f6;margin-bottom:4px;"><i class="fas fa-user-graduate"></i></div>
            <div class="fw-bold" style="font-size:1.5rem;color:#0f172a;"><?= number_format($kpi['enrollment_current']) ?></div>
            <div style="font-size:9px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:0.5px;">Enrolled</div>
            <div style="font-size:11px;color:#94a3b8;margin:4px 0;">Target: <?= number_format($kpi['enrollment_target']) ?></div>
            <?= skpProgressBar($kpi['enrollment_current'],$kpi['enrollment_target']) ?>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="skp-card text-center p-3">
            <div style="font-size:24px;color:#059669;margin-bottom:4px;"><i class="fas fa-coins"></i></div>
            <div class="fw-bold" style="font-size:1.5rem;color:#0f172a;">UGX <?= number_format($kpi['revenue_current']) ?></div>
            <div style="font-size:9px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:0.5px;">Revenue YTD</div>
            <div style="font-size:11px;color:#94a3b8;margin:4px 0;">Target: UGX <?= number_format($kpi['revenue_target']) ?></div>
            <?= skpProgressBar($kpi['revenue_current'],$kpi['revenue_target']) ?>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="skp-card text-center p-3">
            <div style="font-size:24px;color:#f59e0b;margin-bottom:4px;"><i class="fas fa-users"></i></div>
            <div class="fw-bold" style="font-size:1.5rem;color:#0f172a;"><?= number_format($kpi['staff_current']) ?></div>
            <div style="font-size:9px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:0.5px;">Active Staff</div>
            <div style="font-size:11px;color:#94a3b8;margin:4px 0;">Target: <?= number_format($kpi['staff_target']) ?></div>
            <?= skpProgressBar($kpi['staff_current'],$kpi['staff_target']) ?>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="skp-card text-center p-3">
            <div style="font-size:24px;color:#8b5cf6;margin-bottom:4px;"><i class="fas fa-graduation-cap"></i></div>
            <div class="fw-bold" style="font-size:1.5rem;color:#0f172a;"><?= number_format($kpi['graduation_current']) ?></div>
            <div style="font-size:9px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:0.5px;">Passed Exams</div>
            <div style="font-size:11px;color:#94a3b8;margin:4px 0;">Target: <?= number_format($kpi['graduation_target']) ?></div>
            <?= skpProgressBar($kpi['graduation_current'],$kpi['graduation_target']) ?>
        </div>
    </div>
</div>

<!-- ════════════════════════════════════════════ -->
<!-- INSTITUTION KPIs TABLE                       -->
<!-- ════════════════════════════════════════════ -->
<div class="skp-card">
    <h4 class="skp-section-title"><i class="fas fa-chart-simple" style="color:#3b82f6;"></i>Institutional KPIs vs Targets</h4>
    <p class="skp-section-sub">Key performance indicators tracked against annual strategic targets</p>
    <div class="table-responsive">
        <table class="skp-table">
            <thead>
                <tr><th>Metric</th><th>Current</th><th>Target</th><th style="min-width:140px;">Progress</th><th>Status</th></tr>
            </thead>
            <tbody>
                <?php
                $rows = [
                    ['Student Enrollment', number_format($kpi['enrollment_current']), number_format($kpi['enrollment_target']), $kpi['enrollment_current'], $kpi['enrollment_target']],
                    ['Staff Strength (Active)', number_format($kpi['staff_current']), number_format($kpi['staff_target']), $kpi['staff_current'], $kpi['staff_target']],
                    ['Revenue (UGX)', 'UGX '.number_format($kpi['revenue_current']), 'UGX '.number_format($kpi['revenue_target']), $kpi['revenue_current'], $kpi['revenue_target']],
                    ['Pass Rate', $kpi['pass_rate'].'%', '85%', $kpi['pass_rate'], 85],
                    ['Attendance Rate (Today)', $kpi['attendance_rate'].'%', '90%', $kpi['attendance_rate'], 90],
                    ['Application Conversion', $kpi['app_conversion_rate'].'%', '60%', $kpi['app_conversion_rate'], 60],
                ];
                foreach ($rows as $r):
                    $pct = $r[4] > 0 ? min(100,round($r[3]/$r[4]*100)) : 0;
                    $color = $pct >= 75 ? '#10b981' : ($pct >= 50 ? '#f59e0b' : '#ef4444');
                    $onTrack = $r[4] > 0 && $r[3] >= $r[4];
                ?>
                <tr>
                    <td><strong><?= $r[0] ?></strong></td>
                    <td><?= $r[1] ?></td>
                    <td><?= $r[2] ?></td>
                    <td>
                        <div class="skp-progress">
                            <div class="skp-progress-bar" style="width:<?= $pct ?>%;background:<?= $color ?>;"></div>
                        </div>
                        <div style="font-size:11px;font-weight:600;margin-top:2px;color:<?= $color ?>;"><?= $pct ?>%</div>
                    </td>
                    <td>
                        <?php if ($onTrack): ?>
                        <span class="skp-badge skp-badge-green"><i class="fas fa-check-circle me-1"></i>On Track</span>
                        <?php elseif ($pct >= 50): ?>
                        <span class="skp-badge skp-badge-yellow"><i class="fas fa-exclamation-triangle me-1"></i>Behind</span>
                        <?php else: ?>
                        <span class="skp-badge skp-badge-red"><i class="fas fa-times-circle me-1"></i>Critical</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ════════════════════════════════════════════ -->
<!-- STRATEGIC INITIATIVES SECTION                -->
<!-- ════════════════════════════════════════════ -->
<div class="row g-3">
    <div class="col-lg-4">
        <div class="skp-card h-100">
            <h4 class="skp-section-title"><i class="fas fa-check-double" style="color:#059669;"></i>Recent Completed Initiatives</h4>
            <p class="skp-section-sub">Latest strategic activities logged in the system</p>
            <?php
            $completed = [];
            if ($conn) {
                $cq = $conn->query("SELECT activity_description,activity_type,created_at FROM staff_activity_log ORDER BY created_at DESC LIMIT 6");
                if ($cq) while ($row = $cq->fetch_assoc()) $completed[] = $row;
            }
            if (empty($completed)): ?>
            <div class="text-center py-4 text-muted"><i class="fas fa-clipboard-list fa-2x mb-2" style="color:#94a3b8;"></i><p class="small mb-0">No recent activities logged.</p></div>
            <?php else: ?>
            <ul style="list-style:none;padding:0;margin:0;">
                <?php foreach ($completed as $act): ?>
                <li style="display:flex;gap:8px;padding:6px 0;border-bottom:1px solid #f1f5f9;font-size:12px;">
                    <span style="flex-shrink:0;width:6px;height:6px;border-radius:50%;background:#10b981;margin-top:6px;"></span>
                    <div>
                        <div style="color:#0f172a;font-weight:500;"><?= htmlspecialchars(mb_substr($act['activity_description']??$act['activity_type'],0,80)) ?></div>
                        <small style="color:#94a3b8;"><?= date('d M Y',strtotime($act['created_at'])) ?></small>
                    </div>
                </li>
                <?php endforeach; ?>
            </ul>
            <?php endif; ?>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="skp-card h-100">
            <h4 class="skp-section-title"><i class="fas fa-clock" style="color:#f59e0b;"></i>Pending Strategic Items</h4>
            <p class="skp-section-sub">Outstanding approvals and pending submissions</p>
            <?php
            $pendingItems = 0; $pendingList = [];

            // Count pending submissions from website
            $pendingSources = ['contact_submissions'=>'status=\'unread\'','volunteer_applications'=>'status=\'pending\'','donations'=>'status=\'pending\'','student_applications'=>'status=\'Pending\''];
            if ($websiteConn) {
                foreach ($pendingSources as $table => $cond) {
                    $pq = $websiteConn->query("SELECT COUNT(*) c FROM $table WHERE $cond");
                    if ($pq) $pendingList[] = ['table'=>$table,'count'=>(int)$pq->fetch_assoc()['c']];
                }
            }
            $pendingItems = array_sum(array_column($pendingList,'count'));
            ?>
            <?php if ($pendingItems === 0): ?>
            <div class="text-center py-4 text-muted"><i class="fas fa-check-circle fa-2x mb-2" style="color:#10b981;"></i><p class="small mb-0">No pending items requiring attention.</p></div>
            <?php else: ?>
            <div style="margin-bottom:10px;">
                <span style="font-size:28px;font-weight:800;color:#f59e0b;"><?= $pendingItems ?></span>
                <span style="font-size:12px;color:#64748b;margin-left:6px;">items awaiting action</span>
            </div>
            <ul style="list-style:none;padding:0;margin:0;">
                <?php
                $labels = ['contact_submissions'=>'Contact Messages','volunteer_applications'=>'Volunteer Apps','donations'=>'Donations','student_applications'=>'Applications'];
                $icons = ['contact_submissions'=>'fa-envelope','volunteer_applications'=>'fa-hands-helping','donations'=>'fa-hand-holding-heart','student_applications'=>'fa-file-alt'];
                $colors = ['contact_submissions'=>'#dc2626','volunteer_applications'=>'#d97706','donations'=>'#2563eb','student_applications'=>'#16a34a'];
                foreach ($pendingList as $pl): if ($pl['count'] === 0) continue; ?>
                <li style="display:flex;justify-content:space-between;align-items:center;padding:6px 0;border-bottom:1px solid #f1f5f9;font-size:12px;">
                    <span><i class="fas <?= $icons[$pl['table']] ?> me-2" style="color:<?= $colors[$pl['table']] ?>;"></i><?= $labels[$pl['table']] ?></span>
                    <span class="fw-bold"><?= $pl['count'] ?></span>
                </li>
                <?php endforeach; ?>
            </ul>
            <?php endif; ?>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="skp-card h-100">
            <h4 class="skp-section-title"><i class="fas fa-certificate" style="color:#8b5cf6;"></i>Accreditation & Compliance Status</h4>
            <p class="skp-section-sub">Regulatory bodies and institutional compliance</p>
            <?php
            $accr = [
                ['body'=>'Uganda Nurses & Midwives Council (UNMC)','status'=>'Accredited','pct'=>100,'icon'=>'fa-check-circle','color'=>'#10b981'],
                ['body'=>'Ministry of Education & Sports','status'=>'Licensed','pct'=>100,'icon'=>'fa-check-circle','color'=>'#10b981'],
                ['body'=>'National Council for Higher Education (NCHE)','status'=>'Accredited','pct'=>100,'icon'=>'fa-check-circle','color'=>'#10b981'],
                ['body'=>'Uganda Business & Technical Examinations Board (UBTEB)','status'=>'Accredited','pct'=>100,'icon'=>'fa-check-circle','color'=>'#10b981'],
                ['body'=>'ISO 9001:2015 Certification','status'=>'In Progress','pct'=>65,'icon'=>'fa-spinner','color'=>'#f59e0b'],
                ['body'=>'Data Protection & Privacy Compliance','status'=>'Compliant','pct'=>90,'icon'=>'fa-shield-halved','color'=>'#3b82f6'],
            ];
            ?>
            <?php foreach ($accr as $a): ?>
            <div style="display:flex;align-items:center;gap:8px;padding:7px 0;border-bottom:1px solid #f1f5f9;">
                <i class="fas <?= $a['icon'] ?>" style="color:<?= $a['color'] ?>;font-size:13px;width:16px;text-align:center;"></i>
                <div style="flex:1;min-width:0;">
                    <div style="font-size:12px;font-weight:500;color:#0f172a;"><?= htmlspecialchars($a['body']) ?></div>
                    <div class="skp-progress" style="height:5px;margin-top:3px;">
                        <div class="skp-progress-bar" style="width:<?= $a['pct'] ?>%;background:<?= $a['color'] ?>;"></div>
                    </div>
                </div>
                <span class="skp-badge" style="flex-shrink:0;background:<?= $a['pct']>=80?'#dcfce7':($a['pct']>=50?'#fef3c7':'#fee2e2') ?>;color:<?= $a['pct']>=80?'#166534':($a['pct']>=50?'#92400e':'#991b1b') ?>;">
                    <?= $a['status'] ?>
                </span>
            </div>
            <?php endforeach; ?>
            <div style="margin-top:12px;padding:8px 10px;background:#f0fdf4;border-radius:8px;display:flex;align-items:center;gap:8px;">
                <i class="fas fa-check-circle" style="color:#10b981;font-size:16px;"></i>
                <div>
                    <div style="font-size:12px;font-weight:600;color:#166534;">Overall Compliance: <strong>92%</strong></div>
                    <div class="skp-progress" style="height:5px;margin-top:3px;">
                        <div class="skp-progress-bar" style="width:92%;background:#10b981;"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
