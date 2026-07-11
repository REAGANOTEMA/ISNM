<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
require_once __DIR__ . '/../includes/enterprise_auth.php';
$ctx = bootstrapStaffDashboard(['computer lab']);
$staff_conn = $ctx['staff'];
$user = $ctx['user'];
$user_id = (int)($user['id'] ?? 0);
$user_role = $_SESSION['role'] ?? '';
$user_name = $user['full_name'] ?? 'Lab Manager';

function lab_q($conn, $sql) {
    if (!$conn) return 0;
    try { $r = $conn->query($sql); if (!$r) return 0; $row = $r->fetch_assoc(); return (int)($row[array_key_first($row)] ?? 0); }
    catch (Exception $e) { error_log('computer_lab getCount: ' . $e->getMessage()); return 0; }
}
function lab_fetch($conn, $sql) {
    if (!$conn) return [];
    try { $r = $conn->query($sql); if (!$r) return []; return $r->fetch_all(MYSQLI_ASSOC); }
    catch (Exception $e) { error_log('computer_lab getList: ' . $e->getMessage()); return []; }
}
function lab_fetch_one($conn, $sql) {
    if (!$conn) return null;
    try { $r = $conn->query($sql); if (!$r) return null; return $r->fetch_assoc(); }
    catch (Exception $e) { error_log('computer_lab getDetail: ' . $e->getMessage()); return null; }
}

$ict = null;
try { $ict = getICTConnection(); } catch (Exception $e) { error_log('computer_lab context: ' . $e->getMessage()); }
$students = null;
try { $students = getStudentsConnection(); } catch (Exception $e) { error_log('computer_lab context: ' . $e->getMessage()); }
$students_db_name = defined('STUDENTS_DB_NAME') ? STUDENTS_DB_NAME : 'igangaschool_students';

// Stats
$total_computers   = lab_q($ict, "SELECT COUNT(*) FROM lab_computers WHERE status!='deleted'");
$online_computers  = lab_q($ict, "SELECT COUNT(*) FROM lab_computers WHERE status='online'");
$offline_computers = lab_q($ict, "SELECT COUNT(*) FROM lab_computers WHERE status='offline'");
$maint_computers   = lab_q($ict, "SELECT COUNT(*) FROM lab_computers WHERE status='maintenance'");
$total_rooms       = lab_q($ict, "SELECT COUNT(*) FROM lab_rooms WHERE status='active'");
$total_equip       = lab_q($ict, "SELECT COUNT(*) FROM lab_equipment WHERE status!='retired'");
$open_tickets      = lab_q($ict, "SELECT COUNT(*) FROM it_support_tickets WHERE status IN ('open','in_progress')");
$open_repairs      = lab_q($ict, "SELECT COUNT(*) FROM computer_repairs WHERE status NOT IN ('completed','closed','cancelled')");
$pending_print     = lab_q($ict, "SELECT COUNT(*) FROM printing_jobs WHERE status='pending'");
$active_ids        = lab_q($ict, "SELECT COUNT(*) FROM student_id_cards WHERE status='active'");
$total_sessions    = lab_q($ict, "SELECT COUNT(*) FROM lab_practical_sessions WHERE session_date >= CURDATE()");
$checked_out       = lab_q($ict, "SELECT COUNT(*) FROM lab_equipment_checkout WHERE status='checked_out'");
$total_students    = lab_q($students, "SELECT COUNT(*) FROM students WHERE status='Active'");
$low_consumables   = lab_q($ict, "SELECT COUNT(*) FROM lab_consumables WHERE quantity <= reorder_level AND quantity > 0");
$out_consumables   = lab_q($ict, "SELECT COUNT(*) FROM lab_consumables WHERE quantity = 0");

// Section data
$rooms       = lab_fetch($ict, "SELECT * FROM lab_rooms ORDER BY room_name");
$computers   = lab_fetch($ict, "SELECT * FROM lab_computers WHERE status!='deleted' ORDER BY location, computer_name");
$equipment   = lab_fetch($ict, "SELECT e.*, r.room_name FROM lab_equipment e LEFT JOIN lab_rooms r ON e.lab_room_id=r.id WHERE e.status!='retired' ORDER BY e.equipment_name");
$tickets     = lab_fetch($ict, "SELECT t.* FROM it_support_tickets t ORDER BY FIELD(priority,'critical','high','medium','low'), created_at DESC LIMIT 50");
$repairs     = lab_fetch($ict, "SELECT * FROM computer_repairs ORDER BY FIELD(priority,'critical','high','medium','low'), reported_date DESC LIMIT 50");
$sessions    = lab_fetch($ict, "SELECT s.*, r.room_name FROM lab_practical_sessions s LEFT JOIN lab_rooms r ON s.lab_room_id=r.id WHERE s.session_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) ORDER BY s.session_date DESC, s.start_time LIMIT 50");
$software    = lab_fetch($ict, "SELECT * FROM software_inventory ORDER BY software_name");
$print_jobs  = lab_fetch($ict, "SELECT * FROM printing_jobs ORDER BY created_at DESC LIMIT 50");
$print_charges = lab_fetch($ict, "SELECT * FROM printing_charges WHERE is_active=1 ORDER BY print_type, paper_size");
$consumables = lab_fetch($ict, "SELECT * FROM lab_consumables ORDER BY item_name");
$checkouts   = lab_fetch($ict, "SELECT co.*, e.equipment_name, e.equipment_code FROM lab_equipment_checkout co LEFT JOIN lab_equipment e ON co.equipment_id=e.id WHERE co.status='checked_out' OR co.status='overdue' ORDER BY co.expected_return");
$id_cards    = lab_fetch($ict, "SELECT c.*, s.full_name, s.student_number FROM student_id_cards c LEFT JOIN {$students_db_name}.students s ON c.student_id=s.id ORDER BY c.created_at DESC LIMIT 50");
$card_replacements = lab_fetch($ict, "SELECT r.*, s.full_name FROM id_card_replacements r LEFT JOIN {$students_db_name}.students s ON r.student_id=s.id WHERE r.status='pending' ORDER BY r.created_at DESC");
$attendances  = lab_fetch($ict, "SELECT a.*, s.full_name FROM lab_attendance a LEFT JOIN {$students_db_name}.students s ON a.student_id=s.id WHERE a.attendance_date >= CURDATE() ORDER BY a.created_at DESC LIMIT 30");
$maintenance_logs = lab_fetch($ict, "SELECT ml.*, c.computer_name FROM maintenance_logs ml LEFT JOIN lab_computers c ON ml.computer_id=c.id ORDER BY ml.created_at DESC LIMIT 50");
$installations = lab_fetch($ict, "SELECT si.*, sw.software_name, c.computer_name FROM software_installations si LEFT JOIN software_inventory sw ON si.software_id=sw.id LEFT JOIN lab_computers c ON si.computer_id=c.id ORDER BY si.created_at DESC LIMIT 30");
$assignments  = lab_fetch($ict, "SELECT a.*, c.computer_name FROM lab_computer_assignments a LEFT JOIN lab_computers c ON a.computer_id=c.id WHERE a.status='active' ORDER BY a.assigned_date DESC");

if (isset($_GET['page']) && !isset($_GET['section'])) $_GET['section'] = $_GET['page'];
$section = $_GET['section'] ?? 'dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include_once __DIR__ . '/../includes/dashboard_head.php'; ?>
<style>
:root { --sidebar-width: 260px; }
.stat-card { background:#fff; border-radius:12px; padding:18px; border:1px solid #e5e7eb; transition:all .2s; display:flex; align-items:center; gap:14px; }
.stat-card:hover { box-shadow:0 4px 14px rgba(0,0,0,0.07); transform:translateY(-1px); }
.stat-card .icon-circle { width:44px; height:44px; border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:18px; flex-shrink:0; }
.stat-card h4 { font-size:20px; font-weight:700; margin:0; line-height:1.2; }
.stat-card p { margin:0; font-size:12px; color:#6b7280; }
.bg-blue-soft { background:#eff6ff; color:#2563eb; }
.bg-green-soft { background:#f0fdf4; color:#16a34a; }
.bg-red-soft { background:#fef2f2; color:#dc2626; }
.bg-orange-soft { background:#fff7ed; color:#ea580c; }
.bg-purple-soft { background:#faf5ff; color:#9333ea; }
.bg-yellow-soft { background:#fefce8; color:#ca8a04; }
.bg-teal-soft { background:#f0fdfa; color:#0d9488; }
.bg-pink-soft { background:#fdf2f8; color:#db2777; }
.bg-indigo-soft { background:#eef2ff; color:#4f46e5; }
.bg-cyan-soft { background:#ecfeff; color:#0891b2; }
.section-card { background:#fff; border-radius:12px; padding:20px; border:1px solid #e5e7eb; margin-bottom:16px; }
.section-card h2 { font-size:15px; font-weight:700; margin-bottom:14px; color:#111827; }
.comp-status { display:inline-flex; align-items:center; gap:4px; padding:2px 10px; border-radius:20px; font-size:11px; font-weight:600; }
.comp-status.online { background:#dcfce7; color:#166534; }
.comp-status.offline { background:#fef2f2; color:#991b1b; }
.comp-status.maintenance { background:#fff7ed; color:#9a3412; }
.filter-pill { display:inline-flex; align-items:center; gap:6px; padding:7px 16px; border-radius:8px; font-size:13px; font-weight:500; color:#4b5563; background:#f3f4f6; text-decoration:none; transition:all .15s; }
.filter-pill:hover { background:#e5e7eb; color:#111827; }
.filter-pill.active { background:#2563eb; color:#fff; }
.filter-pill.active .badge { background:rgba(255,255,255,0.3); color:#fff; }
.nav-pills-ict { display:flex; flex-wrap:wrap; gap:4px; margin-bottom:16px; padding:8px; background:#f9fafb; border-radius:10px; border:1px solid #e5e7eb; }
.nav-pills-ict .nav-item { list-style:none; }
.nav-pills-ict .nav-link { padding:6px 12px; border-radius:6px; font-size:12px; font-weight:500; color:#4b5563; text-decoration:none; transition:all .15s; white-space:nowrap; }
.nav-pills-ict .nav-link:hover { background:#e5e7eb; }
.nav-pills-ict .nav-link.active { background:#2563eb; color:#fff; }
.search-box { max-width:280px; }
.badge-dot { width:8px; height:8px; border-radius:50%; display:inline-block; }
.id-card-preview { width:320px; border:2px solid #d1d5db; border-radius:12px; overflow:hidden; background:#fff; }
.id-card-preview .id-header { background:#1e3a5f; color:#fff; padding:10px 14px; text-align:center; font-size:11px; font-weight:600; letter-spacing:1px; }
.id-card-preview .id-body { padding:14px; display:flex; gap:12px; }
.id-card-preview .id-photo { width:90px; height:110px; border:1px solid #d1d5db; border-radius:6px; object-fit:cover; background:#f3f4f6; flex-shrink:0; }
.id-card-preview .id-info { flex:1; font-size:11px; line-height:1.5; }
.id-card-preview .id-info strong { font-size:13px; display:block; }
.id-card-preview .id-qr { text-align:center; padding:6px; }
.id-card-preview .id-footer { background:#f9fafb; border-top:1px solid #d1d5db; padding:8px; text-align:center; font-size:9px; color:#6b7280; }
.qr-svg { width:60px; height:60px; }
@media print { .page-content,.content-section { margin:0!important; padding:0!important; } .top-bar,.nav-pills-ict,.sidebar { display:none!important; } }

.cpt-content{margin-left:270px;padding:24px;min-height:100vh}
@media(max-width:768px){.cpt-content{margin-left:0!important;padding:12px!important}}
</style>
</head>
<body class="ent-layout">
<?php include_once __DIR__ . '/../includes/sidebar.php'; ?>
<?php include_once __DIR__ . '/../includes/dashboard_topbar.php'; ?>

<div class="cpt-content">
    <div class="content-section active content-area">
        <?php if ($msg = $_SESSION['success'] ?? null): ?><div class="alert alert-success alert-dismissible fade show py-2"><?= htmlspecialchars($msg) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; unset($_SESSION['success']); ?>
        <?php if ($err = $_SESSION['error'] ?? null): ?><div class="alert alert-danger alert-dismissible fade show py-2"><?= htmlspecialchars($err) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; unset($_SESSION['error']); ?>

        <!-- ======== DASHBOARD OVERVIEW ======== -->
        <?php if ($section === 'dashboard'): ?>
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-4 col-lg-3 col-xl"><div class="stat-card"><div class="icon-circle bg-blue-soft"><i class="fas fa-desktop"></i></div><div><h4><?= $total_computers ?></h4><p>Computers</p></div></div></div>
            <div class="col-6 col-md-4 col-lg-3 col-xl"><div class="stat-card"><div class="icon-circle bg-green-soft"><i class="fas fa-check-circle"></i></div><div><h4><?= $online_computers ?></h4><p>Online</p></div></div></div>
            <div class="col-6 col-md-4 col-lg-3 col-xl"><div class="stat-card"><div class="icon-circle bg-red-soft"><i class="fas fa-exclamation-triangle"></i></div><div><h4><?= $offline_computers ?></h4><p>Offline</p></div></div></div>
            <div class="col-6 col-md-4 col-lg-3 col-xl"><div class="stat-card"><div class="icon-circle bg-orange-soft"><i class="fas fa-tools"></i></div><div><h4><?= $maint_computers ?></h4><p>Maintenance</p></div></div></div>
            <div class="col-6 col-md-4 col-lg-3 col-xl"><div class="stat-card"><div class="icon-circle bg-purple-soft"><i class="fas fa-id-card"></i></div><div><h4><?= $active_ids ?></h4><p>Active ID Cards</p></div></div></div>
            <div class="col-6 col-md-4 col-lg-3 col-xl"><div class="stat-card"><div class="icon-circle bg-yellow-soft"><i class="fas fa-headset"></i></div><div><h4><?= $open_repairs ?></h4><p>Open Repairs</p></div></div></div>
            <div class="col-6 col-md-4 col-lg-3 col-xl"><div class="stat-card"><div class="icon-circle bg-teal-soft"><i class="fas fa-print"></i></div><div><h4><?= $pending_print ?></h4><p>Print Jobs</p></div></div></div>
            <div class="col-6 col-md-4 col-lg-3 col-xl"><div class="stat-card"><div class="icon-circle bg-pink-soft"><i class="fas fa-toolbox"></i></div><div><h4><?= $checked_out ?></h4><p>Equipment Out</p></div></div></div>
            <div class="col-6 col-md-4 col-lg-3 col-xl"><div class="stat-card"><div class="icon-circle bg-indigo-soft"><i class="fas fa-chalkboard"></i></div><div><h4><?= $total_sessions ?></h4><p>Sessions Today</p></div></div></div>
            <div class="col-6 col-md-4 col-lg-3 col-xl"><div class="stat-card"><div class="icon-circle bg-cyan-soft"><i class="fas fa-archive"></i></div><div><h4><?= $low_consumables ?></h4><p>Low Stock Items</p></div></div></div>
        </div>

        <div class="row g-3">
            <div class="col-lg-6">
                <div class="section-card">
                    <h2><i class="fas fa-desktop me-2 text-primary"></i>Computer Status</h2>
                    <div style="max-height:360px;overflow-y:auto">
                    <?php if (empty($computers)): ?><div class="text-center py-4 text-muted"><i class="fas fa-desktop fa-2x mb-2"></i><p>No computers yet</p></div>
                    <?php else: foreach (array_slice($computers, 0, 15) as $c):
                        $sc = $c['status'] === 'online' ? 'online' : ($c['status'] === 'offline' ? 'offline' : 'maintenance');
                        $si = $c['status'] === 'online' ? 'fa-check-circle text-success' : ($c['status'] === 'offline' ? 'fa-times-circle text-danger' : 'fa-wrench text-warning');
                    ?>
                    <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                        <div><i class="fas <?= $si ?> me-2"></i><strong><?= htmlspecialchars($c['computer_name']) ?></strong><small class="text-muted d-block ms-4"><?= htmlspecialchars($c['location'] ?? '') ?> | <?= htmlspecialchars($c['ip_address'] ?? 'N/A') ?></small></div>
                        <span class="comp-status <?= $sc ?>"><?= ucfirst($c['status']) ?></span>
                    </div>
                    <?php endforeach; endif; ?>
                    </div>
                </div>
                <div class="section-card">
                    <h2><i class="fas fa-toolbox me-2 text-teal"></i>Equipment Checked Out</h2>
                    <?php if (empty($checkouts)): ?><div class="text-center py-3 text-muted"><i class="fas fa-check-circle fa-2x mb-2"></i><p>No equipment currently checked out</p></div>
                    <?php else: foreach ($checkouts as $co): ?>
                    <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                        <div><strong><?= htmlspecialchars($co['equipment_name'] ?? 'Item') ?></strong><small class="d-block text-muted">to <?= htmlspecialchars($co['checked_out_to']) ?>, due <?= htmlspecialchars($co['expected_return'] ?? 'N/A') ?></small></div>
                        <span class="badge bg-<?= $co['status']==='overdue'?'danger':'warning' ?>"><?= $co['status'] ?></span>
                    </div>
                    <?php endforeach; endif; ?>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="section-card">
                    <h2><i class="fas fa-headset me-2 text-red"></i>Open Repairs & Tickets</h2>
                    <?php if (empty($repairs) && empty($tickets)): ?><div class="text-center py-3 text-muted"><i class="fas fa-check-circle fa-2x mb-2"></i><p>No open issues</p></div>
                    <?php else:
                        foreach (array_merge(array_slice($repairs, 0, 4), array_slice($tickets, 0, 4)) as $r):
                            $isTicket = isset($r['ticket_number']);
                            $pClass = ($r['priority'] ?? 'medium') === 'critical'||($r['priority']??'')==='high' ? 'border-start border-danger border-4' : (($r['priority']??'')==='medium' ? 'border-start border-warning border-4' : 'border-start border-success border-4');
                    ?>
                    <div class="ps-2 py-2 border-bottom <?= $pClass ?>">
                        <strong><code><?= htmlspecialchars($r[$isTicket?'ticket_number':'repair_number'] ?? '#'.$r['id']) ?></code></strong>
                        <small class="d-block text-muted"><?= htmlspecialchars(mb_substr($r['issue_description'] ?? $r['description'] ?? '', 0, 80)) ?></small>
                        <span class="badge bg-<?= ($r['status']??'')==='open'||($r['status']??'')==='reported'?'danger':($r['status']==='in_progress'?'warning text-dark':'secondary') ?>"><?= str_replace('_',' ',$r['status']??'open') ?></span>
                        <span class="badge bg-<?= ($r['priority']??'')==='critical'||($r['priority']??'')==='high'?'danger':($r['priority']==='medium'?'warning text-dark':'success') ?> ms-1"><?= $r['priority'] ?? 'medium' ?></span>
                    </div>
                    <?php endforeach; endif; ?>
                </div>
                <div class="section-card">
                    <h2><i class="fas fa-print me-2 text-purple"></i>Recent Print Jobs</h2>
                    <?php if (empty($print_jobs)): ?><div class="text-center py-3 text-muted"><p>No print jobs yet</p></div>
                    <?php else: foreach (array_slice($print_jobs, 0, 5) as $p): ?>
                    <div class="d-flex justify-content-between align-items-center py-1 border-bottom">
                        <div><small><code><?= htmlspecialchars($p['job_number']) ?></code></small> <strong><?= htmlspecialchars($p['requester_name']) ?></strong><small class="text-muted ms-2"><?= $p['pages'] ?>p x<?= $p['copies'] ?></small></div>
                        <span class="badge bg-<?= $p['status']==='completed'?'success':($p['status']==='pending'?'warning text-dark':'secondary') ?>"><?= $p['status'] ?></span>
                    </div>
                    <?php endforeach; endif; ?>
                </div>
            </div>
        </div>

        <!-- ======== STUDENT ID CARDS ======== -->
        <?php elseif ($section === 'id-cards'): ?>
        <div class="row g-3">
            <div class="col-md-5">
                <div class="section-card">
                    <h2><i class="fas fa-id-card me-2 text-primary"></i>Generate ID Card</h2>
                    <form id="idCardForm">
                        <input type="hidden" name="action" value="generate_id_card">
                        <div class="mb-2"><label class="form-label">Search Student</label>
                            <select name="student_id" class="form-select" id="studentSelect" required>
                                <option value="">Select student...</option>
                                <?php if ($students): $stus = $students->query("SELECT id, CONCAT(full_name,' (',student_number,')') as label FROM students WHERE status='Active' ORDER BY full_name LIMIT 500"); while($s=$stus->fetch_assoc()): ?>
                                <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['label']) ?></option>
                                <?php endwhile; endif; ?>
                            </select>
                        </div>
                        <div class="mb-2"><label class="form-label">Expiry Date</label><input type="date" name="expiry_date" class="form-control" value="<?= date('Y-m-d', strtotime('+1 year')) ?>"></div>
                        <button type="submit" class="btn btn-primary w-100"><i class="fas fa-id-card me-1"></i>Generate ID Card</button>
                    </form>
                    <hr>
                    <h6 class="mt-3"><i class="fas fa-exchange-alt me-1"></i>Replace Card</h6>
                    <form id="replaceCardForm">
                        <input type="hidden" name="action" value="replace_id_card">
                        <div class="mb-2"><input type="text" name="card_id" class="form-control" placeholder="Card ID or number" required></div>
                        <div class="mb-2">
                            <select name="reason" class="form-select">
                                <option value="lost">Lost</option><option value="damaged">Damaged</option>
                                <option value="stolen">Stolen</option><option value="name_change">Name Change</option>
                                <option value="info_update">Info Update</option>
                            </select>
                        </div>
                        <div class="mb-2"><label class="form-label">Charge (UGX)</label><input type="number" name="charge_amount" class="form-control" value="5000"></div>
                        <button type="submit" class="btn btn-warning w-100"><i class="fas fa-exchange-alt me-1"></i>Request Replacement</button>
                    </form>
                </div>
                <div class="section-card">
                    <h2><i class="fas fa-search me-2 text-success"></i>Verify Card</h2>
                    <form id="verifyCardForm">
                        <input type="hidden" name="action" value="verify_id_card">
                        <div class="mb-2"><input type="text" name="card_number" class="form-control" placeholder="Scan QR or enter card number" required></div>
                        <button type="submit" class="btn btn-success w-100"><i class="fas fa-check-circle me-1"></i>Verify</button>
                    </form>
                    <div id="verifyResult" class="mt-2"></div>
                </div>
                <?php if (!empty($card_replacements)): ?>
                <div class="section-card">
                    <h2><i class="fas fa-clock me-2 text-warning"></i>Pending Replacements (<?= count($card_replacements) ?>)</h2>
                    <?php foreach ($card_replacements as $cr): ?>
                    <div class="d-flex justify-content-between py-1 border-bottom">
                        <span><strong><?= htmlspecialchars($cr['full_name'] ?? 'Student') ?></strong><small class="d-block text-muted"><?= $cr['reason'] ?> | UGX <?= number_format($cr['charge_amount']??0) ?></small></span>
                        <span class="badge bg-warning text-dark"><?= $cr['status'] ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
            <div class="col-md-7">
                <div class="section-card">
                    <h2><i class="fas fa-list me-2"></i>Issued ID Cards</h2>
                    <div class="table-responsive" style="max-height:500px;overflow-y:auto">
                        <table class="table table-sm table-hover">
                            <thead><tr><th>Student</th><th>Card #</th><th>Program</th><th>Expiry</th><th>Status</th><th>Prints</th><th>Actions</th></tr></thead>
                            <tbody>
                                <?php if (empty($id_cards)): ?><tr><td colspan="7" class="text-center text-muted">No ID cards generated yet</td></tr><?php endif; ?>
                                <?php foreach ($id_cards as $c): ?>
                                <tr>
                                    <td><small><?= htmlspecialchars($c['full_name'] ?? 'N/A') ?></small><br><code class="small"><?= htmlspecialchars($c['student_number'] ?? '') ?></code></td>
                                    <td><code><?= htmlspecialchars($c['card_number']) ?></code></td>
                                    <td><small><?= htmlspecialchars(mb_substr($c['program']??'',0,20)) ?></small></td>
                                    <td><small class="<?= (strtotime($c['expiry_date']??'') < time()) ? 'text-danger' : '' ?>"><?= htmlspecialchars($c['expiry_date']??'N/A') ?></small></td>
                                    <td><span class="badge bg-<?= $c['status']==='active'?'success':($c['status']==='expired'?'danger':'secondary') ?>"><?= $c['status'] ?></span></td>
                                    <td><?= (int)$c['print_count'] ?></td>
                                    <td>
                                        <div class="dropdown">
                                            <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="dropdown"><i class="fas fa-ellipsis-v"></i></button>
                                            <ul class="dropdown-menu">
                                                <li><a class="dropdown-item" href="?section=id-cards&section=preview&card=<?= $c['id'] ?>"><i class="fas fa-eye me-2"></i>Preview</a></li>
                                                <li><a class="dropdown-item" href="javascript:void(0)" onclick="reprintCard(<?= $c['id'] ?>)"><i class="fas fa-print me-2"></i>Reprint</a></li>
                                                <li><a class="dropdown-item" href="javascript:void(0)" onclick="printCardDirect(<?= $c['id'] ?>)"><i class="fas fa-file-pdf me-2"></i>Print / PDF</a></li>
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <?php $previewCardId = (int)($_GET['card'] ?? 0);
                if ($section === 'preview' && $previewCardId):
                    $cardData = lab_fetch_one($ict, "SELECT c.*, s.full_name, s.student_number, s.registration_number, s.program, s.intake_date, s.passport_photo, s.profile_picture FROM student_id_cards c LEFT JOIN {$students_db_name}.students s ON c.student_id=s.id WHERE c.id=$previewCardId");
                    if ($cardData): $photo = $cardData['passport_photo'] ?: $cardData['profile_picture'] ?: $cardData['photo_path'] ?: ''; ?>
                <div class="section-card text-center" id="idCardPreview">
                    <h2><i class="fas fa-eye me-2"></i>ID Card Preview</h2>
                    <div class="id-card-preview mx-auto" id="printArea">
                        <div class="id-header"><img src="../images/school-logo.png" style="height:30px" onerror="this.style.display='none'"> IGANG A SCHOOL OF NURSING &amp; MIDWIFERY</div>
                        <div class="id-body">
                            <img src="<?= htmlspecialchars($photo ? '../'.$photo : '../images/default-avatar.png') ?>" class="id-photo" onerror="this.src='../images/default-avatar.png'">
                            <div class="id-info">
                                <strong><?= htmlspecialchars($cardData['full_name'] ?? '') ?></strong>
                                <small><?= htmlspecialchars($cardData['registration_number'] ?? '') ?></small><br>
                                <small>Student #: <?= htmlspecialchars($cardData['student_number'] ?? '') ?></small><br>
                                <small><?= htmlspecialchars($cardData['program'] ?? '') ?></small><br>
                                <small>Intake: <?= htmlspecialchars($cardData['intake'] ?? '') ?></small><br>
                                <small>Expires: <?= htmlspecialchars($cardData['expiry_date'] ?? '') ?></small>
                                <div class="id-qr mt-1"><svg class="qr-svg" viewBox="0 0 100 100"><rect x="10" y="10" width="80" height="80" fill="none" stroke="#000" stroke-width="2"/><text x="50" y="55" text-anchor="middle" font-size="8" fill="#000"><?= htmlspecialchars($cardData['card_number']) ?></text></svg></div>
                            </div>
                        </div>
                        <div class="id-footer">
                            <div class="d-flex justify-content-between px-3">
                                <div><small>Registrar: _________</small></div>
                                <div><small>Principal: _________</small></div>
                            </div>
                            <div class="mt-1"><small>Card: <?= htmlspecialchars($cardData['card_number']) ?> | <?= date('d/m/Y') ?></small></div>
                        </div>
                    </div>
                    <div class="mt-3">
                        <button class="btn btn-primary" onclick="window.print()"><i class="fas fa-print me-1"></i>Print</button>
                        <button class="btn btn-success" onclick="downloadPDF()"><i class="fas fa-file-pdf me-1"></i>Download PDF</button>
                        <button class="btn btn-info text-white" onclick="reprintCard(<?= $cardData['id'] ?>)"><i class="fas fa-redo me-1"></i>Log Reprint</button>
                    </div>
                </div>
                <?php endif; endif; ?>
            </div>
        </div>

        <!-- ======== COMPUTER LAB ======== -->
        <?php elseif ($section === 'computers'): ?>
        <div class="row g-3">
            <div class="col-lg-8">
                <div class="section-card">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h2 class="mb-0"><i class="fas fa-desktop me-2"></i>Computer Inventory</h2>
                        <div class="d-flex gap-2">
                            <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addComputerModal"><i class="fas fa-plus me-1"></i>Add</button>
                            <input type="text" id="compSearch" class="form-control form-control-sm search-box" placeholder="Search...">
                        </div>
                    </div>
                    <div class="table-responsive" style="max-height:550px;overflow-y:auto">
                        <table class="table table-sm table-hover" id="compTable">
                            <thead><tr><th>Computer ID</th><th>Name</th><th>Location</th><th>IP</th><th>OS</th><th>Status</th><th>Actions</th></tr></thead>
                            <tbody>
                                <?php if (empty($computers)): ?><tr><td colspan="7" class="text-center text-muted">No computers</td></tr><?php endif; ?>
                                <?php foreach ($computers as $c):
                                    $sc = $c['status'] === 'online' ? 'success' : ($c['status'] === 'offline' ? 'danger' : 'warning');
                                ?>
                                <tr>
                                    <td><code><?= htmlspecialchars($c['computer_id'] ?? '#'.$c['id']) ?></code></td>
                                    <td class="fw-semibold"><?= htmlspecialchars($c['computer_name']) ?></td>
                                    <td><?= htmlspecialchars($c['location'] ?? '-') ?></td>
                                    <td><code><?= htmlspecialchars($c['ip_address'] ?? '-') ?></code></td>
                                    <td><small><?= htmlspecialchars($c['os_installed'] ?? '-') ?></small></td>
                                    <td><span class="badge bg-<?= $sc ?>"><?= ucfirst($c['status']) ?></span></td>
                                    <td>
                                        <div class="dropdown">
                                            <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="dropdown"><i class="fas fa-ellipsis-v"></i></button>
                                            <ul class="dropdown-menu">
                                                <li><a class="dropdown-item" href="javascript:void(0)" onclick="editComputer(<?= $c['id'] ?>)"><i class="fas fa-edit me-2"></i>Edit</a></li>
                                                <li><a class="dropdown-item" href="javascript:void(0)" onclick="setComputerStatus(<?= $c['id'] ?>,'online')"><i class="fas fa-check-circle text-success me-2"></i>Online</a></li>
                                                <li><a class="dropdown-item" href="javascript:void(0)" onclick="setComputerStatus(<?= $c['id'] ?>,'offline')"><i class="fas fa-times-circle text-danger me-2"></i>Offline</a></li>
                                                <li><a class="dropdown-item" href="javascript:void(0)" onclick="setComputerStatus(<?= $c['id'] ?>,'maintenance')"><i class="fas fa-wrench text-warning me-2"></i>Maintenance</a></li>
                                                <li><hr class="dropdown-divider"></li>
                                                <li><a class="dropdown-item text-danger" href="javascript:void(0)" onclick="deleteComputer(<?= $c['id'] ?>)"><i class="fas fa-trash me-2"></i>Remove</a></li>
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="section-card">
                    <h2><i class="fas fa-tasks me-2 text-info"></i>Computer Assignments</h2>
                    <div class="table-responsive" style="max-height:300px;overflow-y:auto">
                        <table class="table table-sm">
                            <thead><tr><th>Computer</th><th>Assigned To</th><th>Type</th><th>Date</th><th>Status</th></tr></thead>
                            <tbody>
                                <?php if (empty($assignments)): ?><tr><td colspan="5" class="text-center text-muted">No active assignments</td></tr><?php endif; ?>
                                <?php foreach ($assignments as $a): ?>
                                <tr><td><?= htmlspecialchars($a['computer_name'] ?? '#'.$a['computer_id']) ?></td><td><?= $a['student_id']?'Student #'.$a['student_id']:($a['staff_id']?'Staff #'.$a['staff_id']:'N/A') ?></td><td><?= $a['assignment_type'] ?></td><td><small><?= $a['assigned_date'] ?></small></td><td><span class="badge bg-success"><?= $a['status'] ?></span></td></tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="section-card">
                    <h2><i class="fas fa-door-open me-2 text-primary"></i>Lab Rooms</h2>
                    <div style="max-height:400px;overflow-y:auto">
                    <?php if (empty($rooms)): ?><div class="text-center py-3 text-muted"><p>No rooms yet</p><button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addRoomModal"><i class="fas fa-plus me-1"></i>Add Room</button></div>
                    <?php else: foreach ($rooms as $r): ?>
                    <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                        <div><strong><?= htmlspecialchars($r['room_name']) ?></strong><small class="d-block text-muted">Capacity: <?= $r['capacity'] ?> | Computers: <?= $r['computer_count'] ?></small></div>
                        <span class="badge bg-<?= $r['status']==='active'?'success':'secondary' ?>"><?= $r['status'] ?></span>
                    </div>
                    <?php endforeach; endif; ?>
                    </div>
                    <button class="btn btn-sm btn-primary mt-2" data-bs-toggle="modal" data-bs-target="#addRoomModal"><i class="fas fa-plus me-1"></i>Add Room</button>
                </div>
                <div class="section-card">
                    <h2><i class="fas fa-history me-2 text-warning"></i>Maintenance Logs</h2>
                    <div style="max-height:300px;overflow-y:auto">
                    <?php if (empty($maintenance_logs)): ?><div class="text-center py-3 text-muted"><p>No maintenance logs</p></div>
                    <?php else: foreach (array_slice($maintenance_logs, 0, 8) as $m): ?>
                    <div class="py-1 border-bottom small">
                        <strong><?= htmlspecialchars($m['computer_name'] ?? '#'.$m['computer_id']) ?></strong> - <?= htmlspecialchars($m['maintenance_type'] ?? 'General') ?>
                        <small class="d-block text-muted"><?= htmlspecialchars(mb_substr($m['description']??'',0,60)) ?></small>
                    </div>
                    <?php endforeach; endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- ======== PRACTICAL SESSIONS ======== -->
        <?php elseif ($section === 'sessions'): ?>
        <div class="row g-3">
            <div class="col-lg-4">
                <div class="section-card">
                    <h2><i class="fas fa-plus-circle me-2 text-success"></i>New Session</h2>
                    <form id="sessionForm">
                        <input type="hidden" name="action" value="add_practical_session">
                        <div class="mb-2"><label class="form-label">Session Code</label><input type="text" name="session_code" class="form-control" placeholder="e.g., CS101-PRAC-01" required></div>
                        <div class="mb-2"><label class="form-label">Course</label><input type="text" name="course_name" class="form-control" required></div>
                        <div class="mb-2"><label class="form-label">Instructor</label><input type="text" name="instructor_name" class="form-control"></div>
                        <div class="mb-2"><label class="form-label">Lab Room</label>
                            <select name="lab_room_id" class="form-select">
                                <option value="0">Select room...</option>
                                <?php foreach ($rooms as $r): ?>
                                <option value="<?= $r['id'] ?>"><?= htmlspecialchars($r['room_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="row g-2 mb-2">
                            <div class="col-4"><label class="form-label">Date</label><input type="date" name="session_date" class="form-control" value="<?= date('Y-m-d') ?>" required></div>
                            <div class="col-4"><label class="form-label">Start</label><input type="time" name="start_time" class="form-control" required></div>
                            <div class="col-4"><label class="form-label">End</label><input type="time" name="end_time" class="form-control" required></div>
                        </div>
                        <div class="row g-2 mb-2">
                            <div class="col-6"><label class="form-label">Program</label><input type="text" name="program" class="form-control"></div>
                            <div class="col-3"><label class="form-label">Year</label><input type="number" name="year" class="form-control" min="1" max="4"></div>
                            <div class="col-3"><label class="form-label">Max</label><input type="number" name="max_students" class="form-control" value="40"></div>
                        </div>
                        <button type="submit" class="btn btn-success w-100"><i class="fas fa-plus me-1"></i>Create Session</button>
                    </form>
                </div>
            </div>
            <div class="col-lg-8">
                <div class="section-card">
                    <h2><i class="fas fa-chalkboard me-2"></i>Practical Sessions</h2>
                    <div class="table-responsive" style="max-height:500px;overflow-y:auto">
                        <table class="table table-sm table-hover">
                            <thead><tr><th>Code</th><th>Course</th><th>Instructor</th><th>Room</th><th>Date</th><th>Time</th><th>Status</th><th>Actions</th></tr></thead>
                            <tbody>
                                <?php if (empty($sessions)): ?><tr><td colspan="8" class="text-center text-muted">No sessions scheduled</td></tr><?php endif; ?>
                                <?php foreach ($sessions as $s): ?>
                                <tr>
                                    <td><code><?= htmlspecialchars($s['session_code']) ?></code></td>
                                    <td><?= htmlspecialchars($s['course_name']) ?></td>
                                    <td><small><?= htmlspecialchars($s['instructor_name'] ?? '-') ?></small></td>
                                    <td><?= htmlspecialchars($s['room_name'] ?? 'Room #'.$s['lab_room_id']) ?></td>
                                    <td><small><?= htmlspecialchars($s['session_date']) ?></small></td>
                                    <td><small><?= htmlspecialchars($s['start_time'] ?? '') ?>-<?= htmlspecialchars($s['end_time'] ?? '') ?></small></td>
                                    <td><span class="badge bg-<?= $s['status']==='scheduled'?'primary':($s['status']==='ongoing'?'success':($s['status']==='completed'?'info':'secondary')) ?>"><?= $s['status'] ?></span></td>
                                    <td>
                                        <div class="dropdown">
                                            <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="dropdown"><i class="fas fa-ellipsis-v"></i></button>
                                            <ul class="dropdown-menu">
                                                <li><a class="dropdown-item" href="javascript:void(0)" onclick="editSession(<?= $s['id'] ?>)"><i class="fas fa-edit me-2"></i>Edit</a></li>
                                                <li><a class="dropdown-item" href="javascript:void(0)" onclick="markAttendance(<?= $s['id'] ?>)"><i class="fas fa-clipboard-check me-2"></i>Mark Attendance</a></li>
                                                <li><hr class="dropdown-divider"></li>
                                                <li><a class="dropdown-item text-success" href="javascript:void(0)" onclick="updateSessionStatus(<?= $s['id'] ?>,'ongoing')"><i class="fas fa-play me-2"></i>Start</a></li>
                                                <li><a class="dropdown-item text-info" href="javascript:void(0)" onclick="updateSessionStatus(<?= $s['id'] ?>,'completed')"><i class="fas fa-check me-2"></i>Complete</a></li>
                                                <li><a class="dropdown-item text-danger" href="javascript:void(0)" onclick="updateSessionStatus(<?= $s['id'] ?>,'cancelled')"><i class="fas fa-times me-2"></i>Cancel</a></li>
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- ======== EQUIPMENT ======== -->
        <?php elseif ($section === 'equipment'): ?>
        <div class="row g-3">
            <div class="col-lg-8">
                <div class="section-card">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h2 class="mb-0"><i class="fas fa-toolbox me-2"></i>Equipment Inventory</h2>
                        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addEquipmentModal"><i class="fas fa-plus me-1"></i>Add</button>
                    </div>
                    <div class="table-responsive" style="max-height:450px;overflow-y:auto">
                        <table class="table table-sm table-hover">
                            <thead><tr><th>Code</th><th>Name</th><th>Type</th><th>Brand</th><th>Room</th><th>Condition</th><th>Status</th><th>Actions</th></tr></thead>
                            <tbody>
                                <?php if (empty($equipment)): ?><tr><td colspan="8" class="text-center text-muted">No equipment</td></tr><?php endif; ?>
                                <?php foreach ($equipment as $e): ?>
                                <tr>
                                    <td><code><?= htmlspecialchars($e['equipment_code']) ?></code></td>
                                    <td><?= htmlspecialchars($e['equipment_name']) ?></td>
                                    <td><?= ucfirst($e['equipment_type']) ?></td>
                                    <td><small><?= htmlspecialchars($e['brand'] ?? '-') ?></small></td>
                                    <td><small><?= htmlspecialchars($e['room_name'] ?? '-') ?></small></td>
                                    <td><span class="badge bg-<?= $e['condition_status']==='excellent'||$e['condition_status']==='good'?'success':($e['condition_status']==='fair'?'warning text-dark':'danger') ?>"><?= $e['condition_status'] ?></span></td>
                                    <td><span class="badge bg-<?= $e['status']==='available'?'success':($e['status']==='in_use'?'warning text-dark':'secondary') ?>"><?= $e['status'] ?></span></td>
                                    <td>
                                        <div class="dropdown">
                                            <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="dropdown"><i class="fas fa-ellipsis-v"></i></button>
                                            <ul class="dropdown-menu">
                                                <li><a class="dropdown-item" href="javascript:void(0)" onclick="editEquipment(<?= $e['id'] ?>)"><i class="fas fa-edit me-2"></i>Edit</a></li>
                                                <?php if ($e['status'] === 'available'): ?>
                                                <li><a class="dropdown-item" href="javascript:void(0)" onclick="checkoutEquipment(<?= $e['id'] ?>)"><i class="fas fa-sign-out-alt me-2"></i>Check Out</a></li>
                                                <?php endif; ?>
                                                <li><hr class="dropdown-divider"></li>
                                                <li><a class="dropdown-item text-danger" href="javascript:void(0)" onclick="deleteEquipment(<?= $e['id'] ?>)"><i class="fas fa-trash me-2"></i>Retire</a></li>
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="section-card">
                    <h2><i class="fas fa-sign-out-alt me-2 text-warning"></i>Checked Out Equipment</h2>
                    <div class="table-responsive" style="max-height:250px;overflow-y:auto">
                        <table class="table table-sm">
                            <thead><tr><th>Item</th><th>Borrower</th><th>Checkout</th><th>Due</th><th>Status</th><th>Action</th></tr></thead>
                            <tbody>
                                <?php if (empty($checkouts)): ?><tr><td colspan="6" class="text-center text-muted">No items checked out</td></tr><?php endif; ?>
                                <?php foreach ($checkouts as $co): ?>
                                <tr>
                                    <td><code><?= htmlspecialchars($co['equipment_code'] ?? '') ?></code> <?= htmlspecialchars($co['equipment_name'] ?? '') ?></td>
                                    <td><?= htmlspecialchars($co['checked_out_to']) ?></td>
                                    <td><small><?= htmlspecialchars($co['checkout_date'] ?? '') ?></small></td>
                                    <td><small class="<?= strtotime($co['expected_return']??'') < time() ? 'text-danger' : '' ?>"><?= htmlspecialchars($co['expected_return'] ?? 'N/A') ?></small></td>
                                    <td><span class="badge bg-<?= $co['status']==='overdue'?'danger':'warning' ?>"><?= $co['status'] ?></span></td>
                                    <td><button class="btn btn-sm btn-success" onclick="returnEquipment(<?= $co['id'] ?>)"><i class="fas fa-undo"></i></button></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="section-card">
                    <h2><i class="fas fa-sign-in-alt me-2 text-success"></i>Check Out</h2>
                    <form id="checkoutForm">
                        <input type="hidden" name="action" value="checkout_equipment">
                        <input type="hidden" name="equipment_id" id="checkoutEqId">
                        <div class="mb-2"><label class="form-label">Equipment</label>
                            <select class="form-select" id="checkoutEqSelect" onchange="$('#checkoutEqId').val(this.value)">
                                <option value="">Select...</option>
                                <?php foreach ($equipment as $e): if ($e['status']==='available'): ?>
                                <option value="<?= $e['id'] ?>">[<?= $e['equipment_code'] ?>] <?= htmlspecialchars($e['equipment_name']) ?></option>
                                <?php endif; endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-2"><label class="form-label">Borrower Name</label><input type="text" name="checked_out_to" class="form-control" required></div>
                        <div class="mb-2"><label class="form-label">Type</label>
                            <select name="borrower_type" class="form-select"><option value="student">Student</option><option value="staff">Staff</option><option value="lecturer">Lecturer</option></select>
                        </div>
                        <div class="mb-2"><label class="form-label">Expected Return</label><input type="datetime-local" name="expected_return" class="form-control"></div>
                        <button type="submit" class="btn btn-success w-100"><i class="fas fa-sign-out-alt me-1"></i>Check Out</button>
                    </form>
                </div>
                <div class="section-card">
                    <h2><i class="fas fa-shopping-cart me-2 text-purple"></i>Consumables</h2>
                    <div style="max-height:300px;overflow-y:auto">
                    <?php if (empty($consumables)): ?><div class="text-center py-2 text-muted"><p>No consumables tracked</p></div>
                    <?php else: foreach ($consumables as $co): ?>
                    <div class="d-flex justify-content-between py-1 border-bottom small">
                        <span><?= htmlspecialchars($co['item_name']) ?> <span class="badge bg-<?= $co['quantity']<=0?'danger':($co['quantity']<=$co['reorder_level']?'warning text-dark':'success') ?> ms-1"><?= $co['quantity'] ?> <?= $co['unit'] ?></span></span>
                        <span><button class="btn btn-sm btn-outline-primary py-0 px-1" onclick="adjustConsumable(<?= $co['id'] ?>,<?= $co['quantity'] ?>)"><i class="fas fa-edit"></i></button></span>
                    </div>
                    <?php endforeach; endif; ?>
                    </div>
                    <button class="btn btn-sm btn-primary mt-2" data-bs-toggle="modal" data-bs-target="#addConsumableModal"><i class="fas fa-plus me-1"></i>Add Item</button>
                </div>
            </div>
        </div>

        <!-- ======== PRINTING ======== -->
        <?php elseif ($section === 'printing'): ?>
        <div class="row g-3">
            <div class="col-lg-5">
                <div class="section-card">
                    <h2><i class="fas fa-plus-circle me-2 text-primary"></i>New Print Job</h2>
                    <form id="printJobForm">
                        <input type="hidden" name="action" value="create_print_job">
                        <div class="mb-2"><label class="form-label">Requester Name *</label><input type="text" name="requester_name" class="form-control" required></div>
                        <div class="row g-2 mb-2">
                            <div class="col-6"><label class="form-label">Type</label><select name="requester_type" class="form-select"><option value="student">Student</option><option value="staff">Staff</option></select></div>
                            <div class="col-6"><label class="form-label">Requester ID</label><input type="number" name="requester_id" class="form-control"></div>
                        </div>
                        <div class="mb-2"><label class="form-label">Document Name</label><input type="text" name="document_name" class="form-control"></div>
                        <div class="row g-2 mb-2">
                            <div class="col-4"><label class="form-label">Pages</label><input type="number" name="pages" class="form-control" value="1" min="1" required></div>
                            <div class="col-4"><label class="form-label">Copies</label><input type="number" name="copies" class="form-control" value="1" min="1"></div>
                            <div class="col-4"><label class="form-label">Paper</label><select name="paper_size" class="form-select"><option value="A4">A4</option><option value="A3">A3</option></select></div>
                        </div>
                        <div class="mb-2"><label class="form-label">Print Type</label>
                            <select name="print_type" class="form-select" id="printTypeSelect">
                                <option value="bw">Black & White</option>
                                <option value="color">Colour</option>
                                <option value="photocopy">Photocopy</option>
                            </select>
                        </div>
                        <div id="chargeDisplay" class="alert alert-info py-1 small">Charge will be calculated</div>
                        <button type="submit" class="btn btn-primary w-100"><i class="fas fa-print me-1"></i>Submit Print Job</button>
                    </form>
                </div>
            </div>
            <div class="col-lg-7">
                <div class="section-card">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h2 class="mb-0"><i class="fas fa-list me-2"></i>Print Jobs</h2>
                        <div class="d-flex gap-1">
                            <button class="btn btn-sm btn-outline-info" onclick="filterPrint('all')">All</button>
                            <button class="btn btn-sm btn-outline-warning" onclick="filterPrint('pending')">Pending</button>
                            <button class="btn btn-sm btn-outline-success" onclick="filterPrint('completed')">Completed</button>
                        </div>
                    </div>
                    <div class="table-responsive" style="max-height:500px;overflow-y:auto">
                        <table class="table table-sm table-hover" id="printTable">
                            <thead><tr><th>Job #</th><th>Requester</th><th>Pages</th><th>Type</th><th>Charge</th><th>Status</th><th>Actions</th></tr></thead>
                            <tbody>
                                <?php if (empty($print_jobs)): ?><tr><td colspan="7" class="text-center text-muted">No print jobs</td></tr><?php endif; ?>
                                <?php foreach ($print_jobs as $p): ?>
                                <tr class="print-row-<?= $p['status'] ?>">
                                    <td><code><?= htmlspecialchars($p['job_number']) ?></code></td>
                                    <td><?= htmlspecialchars($p['requester_name']) ?></td>
                                    <td><?= $p['pages'] ?> x <?= $p['copies'] ?></td>
                                    <td><span class="badge bg-<?= $p['print_type']==='color'?'danger':($p['print_type']==='bw'?'secondary':'info') ?>"><?= $p['print_type'] ?></span></td>
                                    <td><small>UGX <?= number_format($p['total_charge']??0) ?></small></td>
                                    <td><span class="badge bg-<?= $p['status']==='completed'?'success':($p['status']==='pending'?'warning text-dark':($p['status']==='printing'?'info':'secondary')) ?>"><?= $p['status'] ?></span></td>
                                    <td>
                                        <?php if ($p['status'] === 'pending' || $p['status'] === 'printing'): ?>
                                        <button class="btn btn-sm btn-success" onclick="completePrint(<?= $p['id'] ?>)"><i class="fas fa-check"></i></button>
                                        <button class="btn btn-sm btn-outline-danger" onclick="cancelPrint(<?= $p['id'] ?>)"><i class="fas fa-times"></i></button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="section-card">
                    <h2><i class="fas fa-cog me-2 text-secondary"></i>Printing Charges</h2>
                    <table class="table table-sm">
                        <thead><tr><th>Type</th><th>Paper</th><th>Charge/Page (UGX)</th><th>Action</th></tr></thead>
                        <tbody>
                            <?php foreach ($print_charges as $pc): ?>
                            <tr>
                                <td><?= ucfirst($pc['print_type']) ?></td>
                                <td><?= $pc['paper_size'] ?></td>
                                <td><input type="number" class="form-control form-control-sm d-inline w-auto" value="<?= $pc['charge_per_page'] ?>" id="charge_<?= $pc['id'] ?>" style="width:100px"></td>
                                <td><button class="btn btn-sm btn-outline-primary" onclick="updateCharge(<?= $pc['id'] ?>)"><i class="fas fa-save"></i></button></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ======== SUPPORT (REPAIRS) ======== -->
        <?php elseif ($section === 'support'): ?>
        <div class="row g-3">
            <div class="col-lg-4">
                <div class="section-card">
                    <h2><i class="fas fa-bug me-2 text-danger"></i>Report Issue</h2>
                    <form id="repairForm">
                        <input type="hidden" name="action" value="report_repair">
                        <div class="mb-2"><label class="form-label">Computer</label>
                            <select name="computer_id" class="form-select">
                                <option value="0">General (non-computer)</option>
                                <?php foreach ($computers as $c): ?>
                                <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['computer_name'].' ('.$c['location'].')') ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-2"><label class="form-label">Reported By *</label><input type="text" name="reported_by" class="form-control" required></div>
                        <div class="mb-2"><label class="form-label">Issue Category</label>
                            <select name="issue_category" class="form-select">
                                <option value="hardware">Hardware</option><option value="software">Software</option>
                                <option value="network">Network</option><option value="other">Other</option>
                            </select>
                        </div>
                        <div class="mb-2"><label class="form-label">Priority</label>
                            <select name="priority" class="form-select">
                                <option value="low">Low</option><option value="medium" selected>Medium</option>
                                <option value="high">High</option><option value="critical">Critical</option>
                            </select>
                        </div>
                        <div class="mb-2"><label class="form-label">Description *</label><textarea name="issue_description" class="form-control" rows="3" required></textarea></div>
                        <button type="submit" class="btn btn-danger w-100"><i class="fas fa-bug me-1"></i>Report Issue</button>
                    </form>
                </div>
            </div>
            <div class="col-lg-8">
                <div class="section-card">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h2 class="mb-0"><i class="fas fa-headset me-2"></i>Repairs & Technical Support</h2>
                        <div class="d-flex gap-1">
                            <button class="btn btn-sm btn-outline-primary" onclick="filterRepair('all')">All</button>
                            <button class="btn btn-sm btn-outline-danger" onclick="filterRepair('reported')">Reported</button>
                            <button class="btn btn-sm btn-outline-warning" onclick="filterRepair('in_progress')">In Progress</button>
                            <button class="btn btn-sm btn-outline-success" onclick="filterRepair('completed')">Completed</button>
                        </div>
                    </div>
                    <div class="table-responsive" style="max-height:550px;overflow-y:auto">
                        <table class="table table-sm table-hover" id="repairTable">
                            <thead><tr><th>#</th><th>Reporter</th><th>Issue</th><th>Category</th><th>Priority</th><th>Status</th><th>Actions</th></tr></thead>
                            <tbody>
                                <?php if (empty($repairs)): ?><tr><td colspan="7" class="text-center text-muted">No repairs reported</td></tr><?php endif; ?>
                                <?php foreach ($repairs as $r): ?>
                                <tr class="repair-row-<?= $r['status'] ?>">
                                    <td><code><?= htmlspecialchars($r['repair_number']) ?></code></td>
                                    <td><small><?= htmlspecialchars($r['reported_by']) ?></small></td>
                                    <td><small><?= htmlspecialchars(mb_substr($r['issue_description']??'',0,50)) ?></small></td>
                                    <td><span class="badge bg-secondary"><?= $r['issue_category'] ?></span></td>
                                    <td><span class="badge bg-<?= $r['priority']==='critical'||$r['priority']==='high'?'danger':($r['priority']==='medium'?'warning text-dark':'success') ?>"><?= $r['priority'] ?></span></td>
                                    <td><span class="badge bg-<?= $r['status']==='reported'?'danger':($r['status']==='in_progress'?'warning text-dark':($r['status']==='completed'||$r['status']==='closed'?'success':'secondary')) ?>"><?= str_replace('_',' ',$r['status']) ?></span></td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary" onclick="updateRepair(<?= $r['id'] ?>)"><i class="fas fa-cog"></i></button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- ======== SOFTWARE ======== -->
        <?php elseif ($section === 'software'): ?>
        <div class="row g-3">
            <div class="col-lg-4">
                <div class="section-card">
                    <h2><i class="fas fa-plus-circle me-2 text-success"></i>Add Software</h2>
                    <form id="softwareForm">
                        <input type="hidden" name="action" value="add_software">
                        <div class="mb-2"><label class="form-label">Software Name *</label><input type="text" name="software_name" class="form-control" required></div>
                        <div class="row g-2 mb-2">
                            <div class="col-6"><label class="form-label">Version</label><input type="text" name="version" class="form-control"></div>
                            <div class="col-6"><label class="form-label">Category</label><select name="category" class="form-select"><option>os</option><option>office</option><option>development</option><option>design</option><option>antivirus</option><option>utility</option></select></div>
                        </div>
                        <div class="mb-2"><label class="form-label">License Key</label><input type="text" name="license_key" class="form-control"></div>
                        <div class="mb-2"><label class="form-label">License Type</label><select name="license_type" class="form-select"><option value="educational">Educational</option><option value="commercial">Commercial</option><option value="free">Free</option><option value="trial">Trial</option></select></div>
                        <div class="mb-2"><label class="form-label">License Expiry</label><input type="date" name="license_expiry" class="form-control"></div>
                        <button type="submit" class="btn btn-success w-100"><i class="fas fa-plus me-1"></i>Add Software</button>
                    </form>
                </div>
                <div class="section-card">
                    <h2><i class="fas fa-download me-2 text-info"></i>Record Installation</h2>
                    <form id="installForm">
                        <input type="hidden" name="action" value="install_software">
                        <div class="mb-2"><label class="form-label">Software</label>
                            <select name="software_id" class="form-select">
                                <option value="0">Select...</option>
                                <?php foreach ($software as $sw): ?>
                                <option value="<?= $sw['id'] ?>"><?= htmlspecialchars($sw['software_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-2"><label class="form-label">Computer</label>
                            <select name="computer_id" class="form-select">
                                <option value="0">Select...</option>
                                <?php foreach ($computers as $c): ?>
                                <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['computer_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-2"><label class="form-label">Version Installed</label><input type="text" name="version_installed" class="form-control"></div>
                        <button type="submit" class="btn btn-info text-white w-100"><i class="fas fa-download me-1"></i>Record Installation</button>
                    </form>
                </div>
            </div>
            <div class="col-lg-8">
                <div class="section-card">
                    <h2><i class="fas fa-code me-2"></i>Software Inventory</h2>
                    <div class="table-responsive" style="max-height:350px;overflow-y:auto">
                        <table class="table table-sm table-hover">
                            <thead><tr><th>Name</th><th>Version</th><th>Type</th><th>Category</th><th>License Expiry</th><th>Actions</th></tr></thead>
                            <tbody>
                                <?php if (empty($software)): ?><tr><td colspan="6" class="text-center text-muted">No software</td></tr><?php endif; ?>
                                <?php foreach ($software as $sw): ?>
                                <tr>
                                    <td><?= htmlspecialchars($sw['software_name']) ?></td>
                                    <td><small><?= htmlspecialchars($sw['version'] ?? '-') ?></small></td>
                                    <td><span class="badge bg-<?= $sw['license_type']==='free'?'success':($sw['license_type']==='educational'?'info':'warning text-dark') ?>"><?= $sw['license_type'] ?></span></td>
                                    <td><small><?= $sw['category'] ?></small></td>
                                    <td><small class="<?= (strtotime($sw['license_expiry']??'') < time()) ? 'text-danger' : '' ?>"><?= htmlspecialchars($sw['license_expiry'] ?? 'N/A') ?></small></td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary" onclick="editSoftware(<?= $sw['id'] ?>)"><i class="fas fa-edit"></i></button>
                                        <button class="btn btn-sm btn-outline-danger" onclick="deleteSoftware(<?= $sw['id'] ?>)"><i class="fas fa-trash"></i></button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="section-card">
                    <h2><i class="fas fa-history me-2 text-purple"></i>Installation History</h2>
                    <div class="table-responsive" style="max-height:300px;overflow-y:auto">
                        <table class="table table-sm">
                            <thead><tr><th>Software</th><th>Computer</th><th>Version</th><th>Date</th></tr></thead>
                            <tbody>
                                <?php if (empty($installations)): ?><tr><td colspan="4" class="text-center text-muted">No installations recorded</td></tr><?php endif; ?>
                                <?php foreach ($installations as $i): ?>
                                <tr><td><?= htmlspecialchars($i['software_name'] ?? '#'.$i['software_id']) ?></td><td><?= htmlspecialchars($i['computer_name'] ?? '#'.$i['computer_id']) ?></td><td><small><?= htmlspecialchars($i['version_installed'] ?? '-') ?></small></td><td><small><?= $i['installation_date'] ?? $i['created_at'] ?? '' ?></small></td></tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- ======== INVENTORY ======== -->
        <?php elseif ($section === 'inventory'): ?>
        <div class="row g-3">
            <div class="col-lg-8">
                <div class="section-card">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h2 class="mb-0"><i class="fas fa-archive me-2"></i>Consumables Stock</h2>
                        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addConsumableModal"><i class="fas fa-plus me-1"></i>Add Item</button>
                    </div>
                    <div class="table-responsive" style="max-height:400px;overflow-y:auto">
                        <table class="table table-sm table-hover">
                            <thead><tr><th>Item</th><th>Category</th><th>Quantity</th><th>Reorder</th><th>Supplier</th><th>Status</th><th>Actions</th></tr></thead>
                            <tbody>
                                <?php if (empty($consumables)): ?><tr><td colspan="7" class="text-center text-muted">No consumables</td></tr><?php endif; ?>
                                <?php foreach ($consumables as $co): ?>
                                <tr>
                                    <td><?= htmlspecialchars($co['item_name']) ?></td>
                                    <td><span class="badge bg-secondary"><?= str_replace('_', ' ', $co['item_category']) ?></span></td>
                                    <td><strong><?= $co['quantity'] ?></strong> <small><?= $co['unit'] ?></small></td>
                                    <td><small><?= $co['reorder_level'] ?></small></td>
                                    <td><small><?= htmlspecialchars($co['supplier'] ?? '-') ?></small></td>
                                    <td><span class="badge bg-<?= $co['quantity']<=0?'danger':($co['quantity']<=$co['reorder_level']?'warning text-dark':'success') ?>"><?= str_replace('_', ' ', $co['status']) ?></span></td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary" onclick="editConsumable(<?= $co['id'] ?>)"><i class="fas fa-edit"></i></button>
                                        <button class="btn btn-sm btn-outline-danger" onclick="deleteConsumable(<?= $co['id'] ?>)"><i class="fas fa-trash"></i></button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="section-card">
                    <h2><i class="fas fa-chart-pie me-2 text-info"></i>Stock Summary</h2>
                    <div class="mb-2"><div class="d-flex justify-content-between"><span>In Stock</span><span class="badge bg-success"><?= lab_q($ict, "SELECT COUNT(*) FROM lab_consumables WHERE quantity > reorder_level AND status='in_stock'") ?></span></div></div>
                    <div class="mb-2"><div class="d-flex justify-content-between"><span>Low Stock</span><span class="badge bg-warning text-dark"><?= $low_consumables ?></span></div></div>
                    <div class="mb-2"><div class="d-flex justify-content-between"><span>Out of Stock</span><span class="badge bg-danger"><?= $out_consumables ?></span></div></div>
                    <div class="mb-2"><div class="d-flex justify-content-between"><span>Total Items</span><span class="badge bg-primary"><?= count($consumables) ?></span></div></div>
                </div>
                <div class="section-card">
                    <h2><i class="fas fa-tag me-2 text-primary"></i>Equipment by Type</h2>
                    <?php
                    $typeCounts = [];
                    foreach ($equipment as $e) { $t = $e['equipment_type']; $typeCounts[$t] = ($typeCounts[$t] ?? 0) + 1; }
                    foreach ($typeCounts as $type => $count):
                    ?>
                    <div class="d-flex justify-content-between py-1 border-bottom">
                        <span><?= ucfirst($type) ?></span><span class="badge bg-secondary"><?= $count ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- ======== ATTENDANCE ======== -->
        <?php elseif ($section === 'attendance'): ?>
        <div class="row g-3">
            <div class="col-lg-4">
                <div class="section-card">
                    <h2><i class="fas fa-clipboard-check me-2 text-success"></i>Mark Attendance</h2>
                    <form id="attendanceForm">
                        <input type="hidden" name="action" value="mark_attendance">
                        <div class="mb-2"><label class="form-label">Student</label>
                            <select name="student_id" class="form-select" required>
                                <option value="">Select...</option>
                                <?php if ($students): $stus = $students->query("SELECT id, CONCAT(full_name,' (',student_number,')') as label FROM students WHERE status='Active' ORDER BY full_name LIMIT 200"); while($s=$stus->fetch_assoc()): ?>
                                <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['label']) ?></option>
                                <?php endwhile; endif; ?>
                            </select>
                        </div>
                        <div class="mb-2"><label class="form-label">Date</label><input type="date" name="attendance_date" class="form-control" value="<?= date('Y-m-d') ?>"></div>
                        <div class="mb-2"><label class="form-label">Session</label>
                            <select name="session_id" class="form-select">
                                <option value="0">General</option>
                                <?php foreach ($sessions as $s): ?>
                                <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['session_code'].' - '.$s['course_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-2"><label class="form-label">Status</label>
                            <select name="status" class="form-select"><option value="present">Present</option><option value="late">Late</option><option value="absent">Absent</option><option value="excused">Excused</option></select>
                        </div>
                        <button type="submit" class="btn btn-success w-100"><i class="fas fa-check me-1"></i>Mark</button>
                    </form>
                </div>
            </div>
            <div class="col-lg-8">
                <div class="section-card">
                    <h2><i class="fas fa-list me-2"></i>Today's Attendance</h2>
                    <div class="table-responsive" style="max-height:500px;overflow-y:auto">
                        <table class="table table-sm table-hover">
                            <thead><tr><th>Student</th><th>Date</th><th>Time In</th><th>Session</th><th>Status</th></tr></thead>
                            <tbody>
                                <?php if (empty($attendances)): ?><tr><td colspan="5" class="text-center text-muted">No attendance records for today</td></tr><?php endif; ?>
                                <?php foreach ($attendances as $a): ?>
                                <tr>
                                    <td><?= htmlspecialchars($a['full_name'] ?? 'Student #'.$a['student_id']) ?></td>
                                    <td><small><?= $a['attendance_date'] ?></small></td>
                                    <td><small><?= htmlspecialchars($a['time_in'] ?? '-') ?></small></td>
                                    <td><small>Session #<?= $a['session_id'] ?: 'General' ?></small></td>
                                    <td><span class="badge bg-<?= $a['status']==='present'?'success':($a['status']==='late'?'warning text-dark':($a['status']==='excused'?'info':'danger')) ?>"><?= $a['status'] ?></span></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- ======== REPORTS ======== -->
        <?php elseif ($section === 'reports'): ?>
        <div class="row g-3">
            <div class="col-md-6 col-lg-4">
                <div class="section-card text-center">
                    <i class="fas fa-id-card fa-3x text-primary mb-2"></i>
                    <h5>Student ID Cards</h5>
                    <p class="small text-muted"><?= $active_ids ?> active cards | <?= count($id_cards) ?> total issued</p>
                    <button class="btn btn-sm btn-primary" onclick="window.open('?section=id-cards&section=preview&card=<?= $id_cards[0]['id']??0 ?>','_blank')"><i class="fas fa-eye me-1"></i>View</button>
                    <button class="btn btn-sm btn-success" onclick="window.print()"><i class="fas fa-print me-1"></i>Print</button>
                </div>
            </div>
            <div class="col-md-6 col-lg-4">
                <div class="section-card text-center">
                    <i class="fas fa-desktop fa-3x text-success mb-2"></i>
                    <h5>Computer Inventory</h5>
                    <p class="small text-muted"><?= $total_computers ?> total | <?= $online_computers ?> online | <?= $offline_computers ?> offline</p>
                    <button class="btn btn-sm btn-success" onclick="window.open('?section=computers','_self')"><i class="fas fa-eye me-1"></i>View</button>
                </div>
            </div>
            <div class="col-md-6 col-lg-4">
                <div class="section-card text-center">
                    <i class="fas fa-print fa-3x text-warning mb-2"></i>
                    <h5>Printing Summary</h5>
                    <p class="small text-muted"><?= $pending_print ?> pending | <?= count($print_jobs) ?> total jobs</p>
                    <button class="btn btn-sm btn-warning text-dark" onclick="window.open('?section=printing','_self')"><i class="fas fa-eye me-1"></i>View</button>
                </div>
            </div>
            <div class="col-md-6 col-lg-4">
                <div class="section-card text-center">
                    <i class="fas fa-headset fa-3x text-danger mb-2"></i>
                    <h5>Technical Support</h5>
                    <p class="small text-muted"><?= $open_repairs ?> open repairs</p>
                    <button class="btn btn-sm btn-danger" onclick="window.open('?section=support','_self')"><i class="fas fa-eye me-1"></i>View</button>
                </div>
            </div>
            <div class="col-md-6 col-lg-4">
                <div class="section-card text-center">
                    <i class="fas fa-toolbox fa-3x text-purple mb-2"></i>
                    <h5>Equipment Status</h5>
                    <p class="small text-muted"><?= $total_equip ?> total | <?= $checked_out ?> checked out</p>
                    <button class="btn btn-sm btn-primary" onclick="window.open('?section=equipment','_self')"><i class="fas fa-eye me-1"></i>View</button>
                </div>
            </div>
            <div class="col-md-6 col-lg-4">
                <div class="section-card text-center">
                    <i class="fas fa-archive fa-3x text-info mb-2"></i>
                    <h5>Consumables</h5>
                    <p class="small text-muted"><?= $low_consumables ?> low stock | <?= $out_consumables ?> out of stock</p>
                    <button class="btn btn-sm btn-info text-white" onclick="window.open('?section=inventory','_self')"><i class="fas fa-eye me-1"></i>View</button>
                </div>
            </div>
        </div>

        <!-- ======== SETTINGS ======== -->
        <?php elseif ($section === 'settings'): ?>
        <div class="row g-3">
            <div class="col-lg-6">
                <div class="section-card">
                    <h2><i class="fas fa-door-open me-2 text-primary"></i>Lab Rooms</h2>
                    <div class="mb-2"><input class="form-control form-control-sm" style="max-width:300px" id="srchBLUE" type="text" placeholder="Search..." onkeyup="filterTable('srchBLUE','tblBLUE')"></div>
<div class="table-responsive">
                        <table class="table table-sm">
                            <thead><tr><th>Room</th><th>Code</th><th>Capacity</th><th>Computers</th><th>Status</th></tr></thead>
                            <tbody>
                                <?php foreach ($rooms as $r): ?>
                                <tr>
                                    <td><?= htmlspecialchars($r['room_name']) ?></td>
                                    <td><code><?= htmlspecialchars($r['room_code']) ?></code></td>
                                    <td><?= $r['capacity'] ?></td>
                                    <td><?= $r['computer_count'] ?></td>
                                    <td><span class="badge bg-<?= $r['status']==='active'?'success':'secondary' ?>"><?= $r['status'] ?></span></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addRoomModal"><i class="fas fa-plus me-1"></i>Add Room</button>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="section-card">
                    <h2><i class="fas fa-print me-2 text-warning"></i>Printing Charges</h2>
                    <p class="small text-muted">Update per-page charges for printing services.</p>
                    <table class="table table-sm">
                        <thead><tr><th>Type</th><th>Paper</th><th>UGX/Page</th></tr></thead>
                        <tbody>
                            <?php foreach ($print_charges as $pc): ?>
                            <tr>
                                <td><?= ucfirst($pc['print_type']) ?></td>
                                <td><?= $pc['paper_size'] ?></td>
                                <td><input type="number" class="form-control form-control-sm d-inline w-auto" value="<?= $pc['charge_per_page'] ?>" id="set_charge_<?= $pc['id'] ?>" style="width:100px">
                                <button class="btn btn-sm btn-outline-primary" onclick="updateCharge(<?= $pc['id'] ?>)"><i class="fas fa-save"></i></button></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- ====== MODALS ====== -->
<div class="modal fade" id="addRoomModal" tabindex="-1"><div class="modal-dialog modal-sm">
<form class="modal-content" id="addRoomForm">
<input type="hidden" name="action" value="add_lab_room">
<div class="modal-header bg-primary text-white"><h5 class="modal-title"><i class="fas fa-door-open me-2"></i>Add Lab Room</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
<div class="modal-body">
    <div class="mb-2"><label class="form-label">Room Name *</label><input type="text" name="room_name" class="form-control" required></div>
    <div class="mb-2"><label class="form-label">Room Code *</label><input type="text" name="room_code" class="form-control" required></div>
    <div class="row g-2 mb-2">
        <div class="col-6"><label class="form-label">Capacity</label><input type="number" name="capacity" class="form-control" value="40"></div>
        <div class="col-6"><label class="form-label">Computer Count</label><input type="number" name="computer_count" class="form-control" value="40"></div>
    </div>
    <div class="mb-2"><label class="form-label">Location</label><input type="text" name="location" class="form-control"></div>
</div>
<div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Add</button></div>
</form>
</div></div>

<div class="modal fade" id="addComputerModal" tabindex="-1"><div class="modal-dialog">
<form class="modal-content" id="addComputerForm">
<input type="hidden" name="action" value="add_computer">
<div class="modal-header bg-primary text-white"><h5 class="modal-title"><i class="fas fa-plus-circle me-2"></i>Add Computer</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
<div class="modal-body">
    <div class="mb-2"><label class="form-label">Computer ID *</label><input type="text" name="computer_id" class="form-control" placeholder="e.g., PC-001" required></div>
    <div class="mb-2"><label class="form-label">Computer Name *</label><input type="text" name="computer_name" class="form-control" required></div>
    <div class="mb-2"><label class="form-label">Location / Lab *</label><input type="text" name="location" class="form-control" placeholder="e.g., Lab A" required></div>
    <div class="row g-2 mb-2">
        <div class="col-6"><label class="form-label">IP Address</label><input type="text" name="ip_address" class="form-control"></div>
        <div class="col-6"><label class="form-label">MAC Address</label><input type="text" name="mac_address" class="form-control"></div>
    </div>
    <div class="mb-2"><label class="form-label">OS Installed</label><input type="text" name="os_installed" class="form-control"></div>
    <div class="mb-2"><label class="form-label">Specifications</label><textarea name="specifications" class="form-control" rows="2"></textarea></div>
</div>
<div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Add</button></div>
</form>
</div></div>

<div class="modal fade" id="addEquipmentModal" tabindex="-1"><div class="modal-dialog">
<form class="modal-content" id="addEquipmentForm">
<input type="hidden" name="action" value="add_equipment">
<div class="modal-header bg-primary text-white"><h5 class="modal-title"><i class="fas fa-toolbox me-2"></i>Add Equipment</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
<div class="modal-body">
    <div class="mb-2"><label class="form-label">Equipment Code *</label><input type="text" name="equipment_code" class="form-control" required></div>
    <div class="mb-2"><label class="form-label">Equipment Name *</label><input type="text" name="equipment_name" class="form-control" required></div>
    <div class="row g-2 mb-2">
        <div class="col-6"><label class="form-label">Type</label><select name="equipment_type" class="form-select"><option value="computer">Computer</option><option value="printer">Printer</option><option value="scanner">Scanner</option><option value="projector">Projector</option><option value="ups">UPS</option><option value="accessory">Accessory</option><option value="other">Other</option></select></div>
        <div class="col-6"><label class="form-label">Brand</label><input type="text" name="brand" class="form-control"></div>
    </div>
    <div class="mb-2"><label class="form-label">Model</label><input type="text" name="model" class="form-control"></div>
    <div class="mb-2"><label class="form-label">Serial Number</label><input type="text" name="serial_number" class="form-control"></div>
    <div class="mb-2"><label class="form-label">Lab Room</label><select name="lab_room_id" class="form-select"><option value="0">None</option><?php foreach ($rooms as $r): ?><option value="<?= $r['id'] ?>"><?= htmlspecialchars($r['room_name']) ?></option><?php endforeach; ?></select></div>
</div>
<div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Add</button></div>
</form>
</div></div>

<div class="modal fade" id="addConsumableModal" tabindex="-1"><div class="modal-dialog modal-sm">
<form class="modal-content" id="addConsumableForm">
<input type="hidden" name="action" value="add_consumable">
<div class="modal-header bg-primary text-white"><h5 class="modal-title"><i class="fas fa-cube me-2"></i>Add Consumable</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
<div class="modal-body">
    <div class="mb-2"><label class="form-label">Item Name *</label><input type="text" name="item_name" class="form-control" required></div>
    <div class="mb-2"><label class="form-label">Category</label><select name="item_category" class="form-select"><option value="toner">Toner</option><option value="ink">Ink</option><option value="paper">Paper</option><option value="cable">Cable</option><option value="mouse">Mouse</option><option value="keyboard">Keyboard</option><option value="usb">USB</option><option value="other">Other</option></select></div>
    <div class="row g-2 mb-2">
        <div class="col-6"><label class="form-label">Quantity</label><input type="number" name="quantity" class="form-control" value="10"></div>
        <div class="col-6"><label class="form-label">Reorder Level</label><input type="number" name="reorder_level" class="form-control" value="5"></div>
    </div>
    <div class="mb-2"><label class="form-label">Unit Cost (UGX)</label><input type="number" name="unit_cost" class="form-control" step="0.01"></div>
    <div class="mb-2"><label class="form-label">Supplier</label><input type="text" name="supplier" class="form-control"></div>
</div>
<div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Add</button></div>
</form>
</div></div>

<div class="modal fade" id="repairUpdateModal" tabindex="-1"><div class="modal-dialog">
<div class="modal-content">
<div class="modal-header bg-info text-white"><h5 class="modal-title"><i class="fas fa-cog me-2"></i>Update Repair</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
<div class="modal-body" id="repairUpdateBody">Loading...</div>
<div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button></div>
</div>
</div></div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
const LAB_HANDLER = '../handlers/lab_handler.php';

function showAlert(msg, type) {
    const c = $('.content-area');
    c.prepend(`<div class="alert alert-${type} alert-dismissible fade show py-2">${msg}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>`);
    setTimeout(() => $('.alert').alert('close'), 5000);
}

function doAjax(formId, successMsg, callback) {
    const f = $(`#${formId}`);
    if (f.length && f[0].checkValidity && !f[0].checkValidity()) { f[0].reportValidity(); return; }
    const data = f.serializeArray().reduce((o, x) => (o[x.name] = x.value, o), {});
    $.post(LAB_HANDLER, data).done(r => {
        if (r.success) { showAlert(r.message, 'success'); if (callback) callback(r.data); else setTimeout(() => location.reload(), 800); }
        else showAlert(r.message || 'Error', 'danger');
    }).fail(() => showAlert('AJAX error', 'danger'));
}

// Form handlers
$('#idCardForm').submit(e => { e.preventDefault(); doAjax('idCardForm', 'Card generated', d => setTimeout(() => location.reload(), 600)); });
$('#replaceCardForm').submit(e => { e.preventDefault(); doAjax('replaceCardForm', 'Replacement requested', d => setTimeout(() => location.reload(), 600)); });
$('#verifyCardForm').submit(e => {
    e.preventDefault();
    const data = $('#verifyCardForm').serializeArray().reduce((o, x) => (o[x.name] = x.value, o), {});
    $.post(LAB_HANDLER, data).done(r => {
        if (r.success) {
            const s = r.data;
            $('#verifyResult').html(`<div class="alert alert-success mt-2"><strong>Verified!</strong><br>Student: ${s.full_name || 'N/A'}<br>Card: ${s.card_number}<br>Status: ${s.status}<br>Expiry: ${s.expiry_date || 'N/A'}</div>`);
        } else $('#verifyResult').html(`<div class="alert alert-danger mt-2">${r.message}</div>`);
    }).fail(() => $('#verifyResult').html('<div class="alert alert-danger">Verification failed</div>'));
});
$('#addRoomForm, #addRoomForm select, #addRoomForm input').on('submit', e => { e.preventDefault(); doAjax('addRoomForm', 'Room added'); });
$('#addComputerForm').submit(e => { e.preventDefault(); doAjax('addComputerForm', 'Computer added'); });
$('#addEquipmentForm').submit(e => { e.preventDefault(); doAjax('addEquipmentForm', 'Equipment added'); });
$('#addConsumableForm').submit(e => { e.preventDefault(); doAjax('addConsumableForm', 'Consumable added'); });
$('#sessionForm').submit(e => { e.preventDefault(); doAjax('sessionForm', 'Session created'); });
$('#repairForm').submit(e => { e.preventDefault(); doAjax('repairForm', 'Repair reported'); });
$('#attendanceForm').submit(e => { e.preventDefault(); doAjax('attendanceForm', 'Attendance marked'); });
$('#softwareForm').submit(e => { e.preventDefault(); doAjax('softwareForm', 'Software added'); });
$('#installForm').submit(e => { e.preventDefault(); doAjax('installForm', 'Installation recorded'); });
$('#checkoutForm').submit(e => { e.preventDefault(); doAjax('checkoutForm', 'Equipment checked out'); });
$('#printJobForm').submit(e => { e.preventDefault(); doAjax('printJobForm', 'Print job created'); });

$('#printTypeSelect, #printJobForm select[name=paper_size], #printJobForm input[name=pages], #printJobForm input[name=copies]').on('change keyup', function() {
    const type = $('#printTypeSelect').val();
    const paper = $('#printJobForm select[name=paper_size]').val();
    const pages = parseInt($('#printJobForm input[name=pages]').val()) || 1;
    const copies = parseInt($('#printJobForm input[name=copies]').val()) || 1;
    const charges = <?= json_encode($print_charges) ?>;
    const c = charges.find(x => x.print_type === type && x.paper_size === paper);
    const pp = c ? parseFloat(c.charge_per_page) : 0;
    const total = pp * pages * copies;
    $('#chargeDisplay').text(total > 0 ? `UGX ${pp.toLocaleString()} × ${pages}p × ${copies}x = UGX ${total.toLocaleString()}` : 'Charge not configured');
});

// Single-click actions
function reprintCard(id) {
    if (!confirm('Log a reprint for this card?')) return;
    $.post(LAB_HANDLER, { action: 'reprint_id_card', card_id: id, reason: 'reprint' }).done(r => {
        if (r.success) { showAlert('Reprint logged', 'success'); setTimeout(() => location.reload(), 500); }
        else showAlert(r.message, 'danger');
    });
}

function printCardDirect(id) { window.open(`?section=id-cards&section=preview&card=${id}`, '_blank'); }

function setComputerStatus(id, status) {
    $.post(LAB_HANDLER, { action: 'edit_computer', id, status }).done(r => { if(r.success) location.reload(); else showAlert(r.message, 'danger'); });
}

function deleteComputer(id) {
    if (!confirm('Remove this computer?')) return;
    $.post(LAB_HANDLER, { action: 'delete_computer', id }).done(r => { if(r.success) location.reload(); else showAlert(r.message, 'danger'); });
}

function editComputer(id) {
    const comps = <?= json_encode($computers) ?>;
    const c = comps.find(x => x.id == id);
    if (!c) return;
    const h = Object.keys(c).map(k => {
        if (['id','created_at','updated_at','status','location','ip_address','mac_address','specifications','os_installed'].includes(k)) return;
        const v = c[k] || '';
        return `<div class="mb-1"><label class="small">${k.replace(/_/g,' ')}</label><input type="text" name="${k}" class="form-control form-control-sm" value="${v}"></div>`;
    }).filter(Boolean).join('');
    document.getElementById('editCompBody').innerHTML = h || 'No editable fields';
    new bootstrap.Modal(document.getElementById('editComputerModal')).show();
}

function completePrint(id) {
    $.post(LAB_HANDLER, { action: 'complete_print_job', id }).done(r => { if(r.success) location.reload(); else showAlert(r.message, 'danger'); });
}
function cancelPrint(id) {
    if (!confirm('Cancel this print job?')) return;
    $.post(LAB_HANDLER, { action: 'cancel_print_job', id }).done(r => { if(r.success) location.reload(); else showAlert(r.message, 'danger'); });
}
function updateCharge(id) {
    const v = $(`#charge_${id}`).val() || $(`#set_charge_${id}`).val();
    $.post(LAB_HANDLER, { action: 'update_printing_charge', id, charge_per_page: v }).done(r => { if(r.success) showAlert('Charge updated', 'success'); else showAlert(r.message, 'danger'); });
}

function updateRepair(id) {
    const repairs = <?= json_encode($repairs) ?>;
    const r = repairs.find(x => x.id == id);
    if (!r) return;
    $('#repairUpdateBody').html(`
        <form id="repairUpdateForm">
            <input type="hidden" name="action" value="update_repair_status">
            <input type="hidden" name="id" value="${id}">
            <p><strong>Repair #${r.repair_number}</strong><br><small>${r.issue_description || ''}</small></p>
            <div class="mb-2"><label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="reported" ${r.status==='reported'?'selected':''}>Reported</option>
                    <option value="diagnosed" ${r.status==='diagnosed'?'selected':''}>Diagnosed</option>
                    <option value="in_progress" ${r.status==='in_progress'?'selected':''}>In Progress</option>
                    <option value="completed" ${r.status==='completed'?'selected':''}>Completed</option>
                    <option value="closed" ${r.status==='closed'?'selected':''}>Closed</option>
                </select>
            </div>
            <div class="mb-2"><label class="form-label">Technician</label><input type="text" name="assigned_technician" class="form-control" value="${r.assigned_technician||''}"></div>
            <div class="mb-2"><label class="form-label">Diagnosis</label><textarea name="diagnosis" class="form-control" rows="2">${r.diagnosis||''}</textarea></div>
            <div class="mb-2"><label class="form-label">Resolution</label><textarea name="resolution" class="form-control" rows="2">${r.resolution||''}</textarea></div>
            <div class="mb-2"><label class="form-label">Parts Replaced</label><textarea name="parts_replaced" class="form-control" rows="2">${r.parts_replaced||''}</textarea></div>
            <div class="mb-2"><label class="form-label">Cost (UGX)</label><input type="number" name="cost" class="form-control" value="${r.cost||0}"></div>
            <button type="submit" class="btn btn-info text-white w-100"><i class="fas fa-save me-1"></i>Update</button>
        </form>
    `);
    new bootstrap.Modal(document.getElementById('repairUpdateModal')).show();
    setTimeout(() => {
        $('#repairUpdateForm').submit(e => { e.preventDefault(); doAjax('repairUpdateForm', 'Repair updated'); });
    }, 100);
}

function checkoutEquipment(id) { $('#checkoutEqId').val(id); $('#checkoutEqSelect').val(id); $('#checkoutForm')[0].scrollIntoView({behavior:'smooth'}); }
function returnEquipment(id) {
    if (!confirm('Confirm return?')) return;
    $.post(LAB_HANDLER, { action: 'return_equipment', checkout_id: id }).done(r => { if(r.success) location.reload(); else showAlert(r.message, 'danger'); });
}
function deleteEquipment(id) {
    if (!confirm('Retire this equipment?')) return;
    $.post(LAB_HANDLER, { action: 'delete_equipment', id }).done(r => { if(r.success) location.reload(); else showAlert(r.message, 'danger'); });
}
function editEquipment(id) {
    const name = prompt('Equipment name:') || '';
    if (!name) return;
    const type = prompt('Type (computer/printer/scanner/other):') || '';
    const brand = prompt('Brand:') || '';
    const condition = prompt('Condition (excellent/good/fair/poor):') || 'good';
    const status = prompt('Status (available/in_use/retired):') || 'available';
    $.post(LAB_HANDLER, { action: 'edit_equipment', id, equipment_name: name, equipment_type: type, brand, model: '', serial_number: '', lab_room_id: 0, condition_status: condition, status }).done(r => { if(r.success) location.reload(); else showAlert(r.message, 'danger'); });
}
function editSoftware(id) {
    const name = prompt('Software name:') || '';
    if (!name) return;
    const ver = prompt('Version:') || '';
    const license = prompt('License key:') || '';
    const type = prompt('License type (opensource/commercial/academic):') || '';
    const expiry = prompt('License expiry (YYYY-MM-DD) or leave blank:');
    $.post(LAB_HANDLER, { action: 'edit_software', id, software_name: name, version: ver, license_key: license, license_type: type, license_expiry: expiry || null }).done(r => { if(r.success) location.reload(); else showAlert(r.message, 'danger'); });
}
function deleteSoftware(id) {
    if (!confirm('Delete this software?')) return;
    $.post(LAB_HANDLER, { action: 'delete_software', id }).done(r => { if(r.success) location.reload(); else showAlert(r.message, 'danger'); });
}
function editConsumable(id) { showAlert('Edit feature coming soon', 'info'); }
function deleteConsumable(id) {
    if (!confirm('Delete this consumable?')) return;
    $.post(LAB_HANDLER, { action: 'delete_consumable', id }).done(r => { if(r.success) location.reload(); else showAlert(r.message, 'danger'); });
}
function adjustConsumable(id, curQty) {
    const q = prompt('New quantity:', curQty);
    if (q === null) return;
    $.post(LAB_HANDLER, { action: 'update_consumable_stock', id, quantity: parseInt(q) }).done(r => { if(r.success) location.reload(); else showAlert(r.message, 'danger'); });
}
function updateSessionStatus(id, status) {
    $.post(LAB_HANDLER, { action: 'edit_practical_session', id, status }).done(r => { if(r.success) location.reload(); else showAlert(r.message, 'danger'); });
}
function editSession(id) { showAlert('Edit via modal coming soon', 'info'); }
function markAttendance(id) { showAlert(`Open attendance for session #${id}`, 'info'); }
function filterPrint(s) {
    $('#printTable tbody tr').each(function() { $(this).toggle(s === 'all' || $(this).hasClass('print-row-' + s)); });
}
function filterRepair(s) {
    $('#repairTable tbody tr').each(function() { $(this).toggle(s === 'all' || $(this).hasClass('repair-row-' + s)); });
}

// Search
$('#compSearch').on('keyup', function() {
    const q = $(this).val().toLowerCase();
    $('#compTable tbody tr').each(function() { $(this).toggle($(this).text().toLowerCase().includes(q)); });
});

function downloadPDF() {
    const content = document.getElementById('printArea');
    if (!content) return;
    const win = window.open('', '_blank');
    win.document.write(`<html><head><title>ID Card</title><style>body{font-family:Arial,sans-serif;margin:20px}@media print{body{margin:0}}.id-card-preview{width:320px;border:2px solid #000;border-radius:12px;overflow:hidden}.id-header{background:#1e3a5f;color:#fff;padding:10px;text-align:center;font-size:11px;font-weight:600}.id-body{padding:14px;display:flex;gap:12px}.id-photo{width:90px;height:110px;border:1px solid #ccc;object-fit:cover}.id-info{font-size:11px;line-height:1.5}.id-footer{background:#f5f5f5;border-top:1px solid #ccc;padding:8px;text-align:center;font-size:9px}</style></head><body>${content.innerHTML}</body></html>`);
    win.document.close();
    setTimeout(() => { win.print(); }, 500);
}
function filterTable(inputId, tableId) {
    var input = document.getElementById(inputId);
    var filter = input.value.toUpperCase();
    var table = document.getElementById(tableId);
    if (!table) return;
    var tr = table.getElementsByTagName("tr");
    for (var i = 1; i < tr.length; i++) {
        var td = tr[i].getElementsByTagName("td");
        var found = false;
        for (var j = 0; j < td.length; j++) {
            if (td[j] && td[j].textContent.toUpperCase().indexOf(filter) > -1) { found = true; break; }
        }
        tr[i].style.display = found ? "" : "none";
    }
}

</script>
<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
</body>
</html>
