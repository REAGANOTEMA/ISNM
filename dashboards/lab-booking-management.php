<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
require_once __DIR__ . '/../includes/database_connections.php';

$ctx = bootstrapStaffDashboard(['ict']);
$staff_conn = $ctx['staff'];
$students_conn = $ctx['students'];
$user = $ctx['user'];
$user_name = $user['full_name'] ?? 'ICT Staff';
$user_id = (int)($user['id'] ?? 0);

$ict_conn = getICTConnection();

function lb_q($conn, $sql) {
    if (!$conn) return 0;
    try { $r = $conn->query($sql); if (!$r) return 0; $row = $r->fetch_assoc(); return (int)($row[array_key_first($row)] ?? 0); }
    catch (Exception $e) { return 0; }
}

function lb_fetch($conn, $sql) {
    if (!$conn) return [];
    try { $r = $conn->query($sql); if (!$r) return []; return $r->fetch_all(MYSQLI_ASSOC); }
    catch (Exception $e) { return []; }
}

$filter_date = $_GET['filter_date'] ?? date('Y-m-d');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $ict_conn) {
    $action = $_POST['action'] ?? '';

    if ($action === 'create_booking') {
        $ref = 'LB-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
        $course = trim($_POST['course_name']);
        $instructor = trim($_POST['instructor_name']);
        $email = trim($_POST['instructor_email'] ?? '');
        $date = trim($_POST['booking_date']);
        $slot = trim($_POST['time_slot']);
        $students = (int)($_POST['number_of_students'] ?? 0);
        $purpose = trim($_POST['purpose'] ?? '');
        $requirements = trim($_POST['special_requirements'] ?? '');
        $lab = trim($_POST['lab_assigned'] ?? '');
        $stmt = $ict_conn->prepare("INSERT INTO lab_bookings (booking_reference, course_name, instructor_name, instructor_email, booking_date, time_slot, number_of_students, purpose, special_requirements, status, lab_assigned, created_by) VALUES (?,?,?,?,?,?,?,?,'pending',?,?)");
        if ($stmt) { $stmt->bind_param('sssssisssi', $ref, $course, $instructor, $email, $date, $slot, $students, $purpose, $requirements, $lab, $user_id); $stmt->execute(); $stmt->close(); }
        $_SESSION['success'] = "Booking $ref created successfully.";
        header('Location: lab-booking-management.php'); exit;
    }

    if ($action === 'confirm_booking') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $stmt = $ict_conn->prepare("UPDATE lab_bookings SET status='confirmed', approved_by=? WHERE id=?");
            if ($stmt) { $stmt->bind_param('ii', $user_id, $id); $stmt->execute(); $stmt->close(); }
            $_SESSION['success'] = 'Booking confirmed.';
        }
        header('Location: lab-booking-management.php'); exit;
    }

    if ($action === 'cancel_booking') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $stmt = $ict_conn->prepare("UPDATE lab_bookings SET status='cancelled' WHERE id=?");
            if ($stmt) { $stmt->bind_param('i', $id); $stmt->execute(); $stmt->close(); }
            $_SESSION['success'] = 'Booking cancelled.';
        }
        header('Location: lab-booking-management.php'); exit;
    }

    if ($action === 'complete_booking') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $stmt = $ict_conn->prepare("UPDATE lab_bookings SET status='completed' WHERE id=?");
            if ($stmt) { $stmt->bind_param('i', $id); $stmt->execute(); $stmt->close(); }
            $_SESSION['success'] = 'Booking marked completed.';
        }
        header('Location: lab-booking-management.php'); exit;
    }

    header('Location: lab-booking-management.php'); exit;
}

$total_bookings = lb_q($ict_conn, "SELECT COUNT(*) FROM lab_bookings");
$confirmed_today = lb_q($ict_conn, "SELECT COUNT(*) FROM lab_bookings WHERE booking_date = CURDATE() AND status = 'confirmed'");
$pending_total = lb_q($ict_conn, "SELECT COUNT(*) FROM lab_bookings WHERE status = 'pending'");
$completed_today = lb_q($ict_conn, "SELECT COUNT(*) FROM lab_bookings WHERE booking_date = CURDATE() AND status = 'completed'");

if ($filter_date !== '') {
    $stmt = $ict_conn->prepare("SELECT * FROM lab_bookings WHERE booking_date = ? ORDER BY time_slot ASC, created_at DESC");
    if ($stmt) { $stmt->bind_param('s', $filter_date); $stmt->execute(); $r = $stmt->get_result(); $stmt->close(); } else $r = null;
} else {
    $r = $ict_conn->query("SELECT * FROM lab_bookings ORDER BY time_slot ASC, created_at DESC");
}
$bookings = $r ? $r->fetch_all(MYSQLI_ASSOC) : [];

$time_slots = ['08:00-09:00', '09:00-10:00', '10:00-11:00', '11:00-12:00', '12:00-13:00', '13:00-14:00', '14:00-15:00', '15:00-16:00', '16:00-17:00'];
$labs = ['Lab A', 'Lab B', 'Lab C', 'Lab D', 'Computer Lab 1', 'Computer Lab 2'];
$availability = [];
// Batch all lab/time availability in a single query instead of 54 individual queries
$avail_rows = [];
if ($ict_conn) {
    $avail_result = $ict_conn->query("SELECT lab_assigned, time_slot, COUNT(*) as cnt FROM lab_bookings WHERE booking_date=CURDATE() AND status IN ('pending','confirmed') GROUP BY lab_assigned, time_slot");
    if ($avail_result) { while ($row = $avail_result->fetch_assoc()) { $avail_rows[$row['lab_assigned']][$row['time_slot']] = true; } }
}
foreach ($labs as $lab) {
    foreach ($time_slots as $slot) {
        $availability[$lab][$slot] = !isset($avail_rows[$lab][$slot]);
    }
}

$pageTitle = 'Lab Booking Management';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include_once __DIR__ . '/../includes/dashboard_head.php'; ?>
<style>
.lb-section { display: none; }
.lb-section.active { display: block; }
.page-content { margin-left: 270px; }
.stat-card { background: #fff; border-radius: 16px; padding: 18px; border: 1px solid rgba(148,163,184,0.16); box-shadow: 0 1px 2px rgba(15,23,42,0.03), 0 4px 12px rgba(15,23,42,0.04); display: flex; align-items: center; gap: 14px; transition: all 0.25s ease; }
.stat-card:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(0,0,0,0.08); }
.si { width: 48px; height: 48px; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 20px; flex-shrink: 0; }
.si-blue { background: #eef2ff; color: #2563eb; }
.si-green { background: #ecfdf5; color: #059669; }
.si-orange { background: #fff7ed; color: #d97706; }
.si-purple { background: #f5f3ff; color: #7c3aed; }
.stat-content h3 { font-size: 1.5rem; font-weight: 700; margin: 0; color: #0f172a; line-height: 1.2; }
.stat-content p { font-size: 0.75rem; color: #64748b; margin: 0; text-transform: uppercase; letter-spacing: 0.5px; }
.section-card { background: #fff; border-radius: 16px; padding: 20px; margin-bottom: 20px; border: 1px solid rgba(148,163,184,0.16); box-shadow: 0 1px 2px rgba(15,23,42,0.03), 0 4px 12px rgba(15,23,42,0.04); }
.top-bar { background: #fff; border-radius: 16px; padding: 16px 20px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; border: 1px solid rgba(148,163,184,0.16); }
.form-control, .form-select { border-radius: 10px; border: 1.5px solid #e2e8f0; padding: 8px 14px; font-size: 0.875rem; }
.form-control:focus, .form-select:focus { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,0.1); }
.btn { border-radius: 10px; font-weight: 500; padding: 8px 18px; }
.table th { font-weight: 600; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px; color: #64748b; }
.table td { vertical-align: middle; font-size: 0.875rem; }
.availability-grid { display: grid; grid-template-columns: 120px repeat(9, 1fr); gap: 4px; font-size: 0.7rem; }
.availability-grid .cell { padding: 6px 2px; text-align: center; border-radius: 6px; font-weight: 600; }
.avail-free { background: #d1fae5; color: #065f46; }
.avail-booked { background: #fee2e2; color: #991b1b; }
.avail-label { font-weight: 600; display: flex; align-items: center; padding: 4px; }
@media (max-width: 768px) { .page-content { margin-left: 0; } .availability-grid { grid-template-columns: 80px repeat(9, 1fr); } }
</style>
</head>
<body>
<?php include_once __DIR__ . '/../includes/sidebar.php'; ?>
<?php include_once __DIR__ . '/../includes/dashboard_topbar.php'; ?>
<div class="page-content">
<div class="top-bar"><div><strong><i class="fas fa-calendar-alt me-2 text-primary"></i>Lab Booking Management</strong><div class="text-muted small">Iganga School of Nursing &amp; Midwifery</div></div><div class="d-flex align-items-center gap-3"><span class="text-muted small d-none d-md-block"><?=date('D, d M Y')?></span><button class="btn btn-sm btn-outline-success no-print" onclick="window.print()"><i class="fas fa-print me-1"></i></button><a href="../logout.php" class="btn btn-sm btn-outline-danger no-print"><i class="fas fa-sign-out-alt me-1"></i>Logout</a></div></div>
<div class="content-area">
<?php if(!empty($_SESSION['success'])):?><div class="alert alert-success alert-dismissible fade show py-2"><?=htmlspecialchars($_SESSION['success'])?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php unset($_SESSION['success']);endif;?>
<?php if(!empty($_SESSION['error'])):?><div class="alert alert-danger alert-dismissible fade show py-2"><?=htmlspecialchars($_SESSION['error'])?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php unset($_SESSION['error']);endif;?>

<div class="row g-3 mb-4">
<div class="col-6 col-md-3 col-lg-3"><div class="stat-card"><div class="si si-blue"><i class="fas fa-calendar-alt"></i></div><div class="stat-content"><h3><?=number_format($total_bookings)?></h3><p>Total Bookings</p></div></div></div>
<div class="col-6 col-md-3 col-lg-3"><div class="stat-card"><div class="si si-green"><i class="fas fa-check-circle"></i></div><div class="stat-content"><h3><?=$confirmed_today?></h3><p>Confirmed Today</p></div></div></div>
<div class="col-6 col-md-3 col-lg-3"><div class="stat-card"><div class="si si-orange"><i class="fas fa-clock"></i></div><div class="stat-content"><h3><?=$pending_total?></h3><p>Pending</p></div></div></div>
<div class="col-6 col-md-3 col-lg-3"><div class="stat-card"><div class="si si-purple"><i class="fas fa-check-double"></i></div><div class="stat-content"><h3><?=$completed_today?></h3><p>Completed Today</p></div></div></div>
</div>

<div class="row g-4 mb-4">
<div class="col-lg-8">
<div class="section-card">
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
<h5 class="fw-bold mb-0"><i class="fas fa-list me-2 text-primary"></i>Lab Bookings</h5>
<div class="d-flex gap-2 align-items-center">
<form method="GET" class="d-flex gap-2 align-items-center">
<input type="date" name="filter_date" class="form-control form-control-sm" value="<?=htmlspecialchars($filter_date)?>">
<button type="submit" class="btn btn-sm btn-outline-primary"><i class="fas fa-filter me-1"></i>Filter</button>
<?php if($filter_date !== date('Y-m-d')):?><a href="lab-booking-management.php" class="btn btn-sm btn-outline-secondary"><i class="fas fa-times"></i></a><?php endif;?>
</form>
<button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#createBookingModal"><i class="fas fa-plus me-1"></i>New Booking</button>
</div>
</div>
<?php if(empty($bookings)):?>
<div class="text-center py-5 text-muted"><i class="fas fa-calendar-times fa-3x mb-3"></i><p>No bookings found for <?=htmlspecialchars($filter_date)?>.</p></div>
<?php else:?>
<div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead class="table-light"><tr><th>Reference</th><th>Course</th><th>Instructor</th><th>Date</th><th>Time</th><th>Students</th><th>Lab</th><th>Status</th><th>Actions</th></tr></thead><tbody>
<?php foreach($bookings as $b):
switch($b['status']) {
    case 'pending': $badge = 'bg-warning text-dark'; break;
    case 'confirmed': $badge = 'bg-success'; break;
    case 'cancelled': $badge = 'bg-danger'; break;
    case 'completed': $badge = 'bg-primary'; break;
    default: $badge = 'bg-secondary';
}
?>
<tr>
<td><code><?=htmlspecialchars($b['booking_reference'])?></code></td>
<td><strong><?=htmlspecialchars($b['course_name'])?></strong></td>
<td><?=htmlspecialchars($b['instructor_name'])?><br><small class="text-muted"><?=htmlspecialchars($b['instructor_email']??'')?></small></td>
<td><?=date('d M Y', strtotime($b['booking_date']))?></td>
<td><?=htmlspecialchars($b['time_slot'])?></td>
<td><?=(int)$b['number_of_students']?></td>
<td><?=htmlspecialchars($b['lab_assigned'] ?? '-')?></td>
<td><span class="badge <?=$badge?>"><?=ucfirst($b['status'])?></span></td>
<td>
<div class="d-flex gap-1 flex-wrap">
<?php if($b['status'] === 'pending'):?>
<form method="POST" class="d-inline"><input type="hidden" name="action" value="confirm_booking"><input type="hidden" name="id" value="<?=$b['id']?>"><button class="btn btn-sm btn-outline-success" title="Confirm"><i class="fas fa-check"></i></button></form>
<form method="POST" class="d-inline" onsubmit="return confirm('Cancel this booking?')"><input type="hidden" name="action" value="cancel_booking"><input type="hidden" name="id" value="<?=$b['id']?>"><button class="btn btn-sm btn-outline-danger" title="Cancel"><i class="fas fa-times"></i></button></form>
<?php elseif($b['status'] === 'confirmed'):?>
<form method="POST" class="d-inline"><input type="hidden" name="action" value="complete_booking"><input type="hidden" name="id" value="<?=$b['id']?>"><button class="btn btn-sm btn-outline-primary" title="Mark Completed"><i class="fas fa-check-double"></i></button></form>
<form method="POST" class="d-inline" onsubmit="return confirm('Cancel this booking?')"><input type="hidden" name="action" value="cancel_booking"><input type="hidden" name="id" value="<?=$b['id']?>"><button class="btn btn-sm btn-outline-danger" title="Cancel"><i class="fas fa-times"></i></button></form>
<?php endif;?>
</div>
</td>
</tr>
<?php endforeach;?>
</tbody></table></div>
<?php endif;?>
</div>
</div>

<div class="col-lg-4">
<div class="section-card">
<h5 class="fw-bold mb-3"><i class="fas fa-microchip me-2 text-success"></i>Lab Availability Today</h5>
<p class="text-muted small mb-2"><?=date('l, d M Y')?></p>
<div class="availability-grid">
<div class="avail-label fw-bold">Lab / Time</div>
<?php foreach($time_slots as $ts):?>
<div class="avail-label" style="justify-content:center;font-size:0.6rem"><?=htmlspecialchars($ts)?></div>
<?php endforeach;?>
<?php foreach($labs as $lab):?>
<div class="avail-label" style="font-size:0.7rem"><?=htmlspecialchars($lab)?></div>
<?php foreach($time_slots as $ts):?>
<div class="cell <?=$availability[$lab][$ts] ? 'avail-free' : 'avail-booked'?>"><?=$availability[$lab][$ts] ? 'Free' : 'Booked'?></div>
<?php endforeach;?>
<?php endforeach;?>
</div>
</div>

<div class="section-card">
<h5 class="fw-bold mb-3"><i class="fas fa-info-circle me-2 text-info"></i>Booking Summary</h5>
<div class="d-flex justify-content-between py-2 border-bottom"><span class="text-muted">Today's Date:</span><strong><?=date('d M Y')?></strong></div>
<div class="d-flex justify-content-between py-2 border-bottom"><span class="text-muted">Filter Date:</span><strong><?=htmlspecialchars($filter_date)?></strong></div>
<div class="d-flex justify-content-between py-2 border-bottom"><span class="text-muted">Bookings on Date:</span><strong><?=count($bookings)?></strong></div>
<div class="d-flex justify-content-between py-2"><span class="text-muted">Available Labs:</span><strong><?=count($labs)?></strong></div>
</div>
</div>
</div>

<div class="section-card">
<h5 class="fw-bold mb-3"><i class="fas fa-calendar-week me-2 text-warning"></i>All Time Slots</h5>
<div class="table-responsive">
<table class="table table-sm table-hover align-middle mb-0">
<thead class="table-light"><tr><th>Time Slot</th><th>Bookings</th><th>Status</th></tr></thead>
<tbody>
<?php foreach($time_slots as $ts):
$stmt = $ict_conn->prepare("SELECT COUNT(*) FROM lab_bookings WHERE time_slot=? AND booking_date=? AND status IN ('pending','confirmed')");
if ($stmt) { $stmt->bind_param('ss', $ts, $filter_date); $stmt->execute(); $row = $stmt->get_result()->fetch_row(); $count = (int)$row[0]; $stmt->close(); } else $count = 0;
$stat = $count === 0 ? '<span class="badge bg-success">Available</span>' : ($count < 3 ? '<span class="badge bg-warning text-dark">Limited</span>' : '<span class="badge bg-danger">Full</span>');
?>
<tr><td><?=htmlspecialchars($ts)?></td><td><?=$count?></td><td><?=$stat?></td></tr>
<?php endforeach;?>
</tbody>
</table>
</div>
</div>

</div>
</div>

<div class="modal fade" id="createBookingModal" tabindex="-1">
<div class="modal-dialog modal-lg">
<form method="POST" class="modal-content">
<input type="hidden" name="action" value="create_booking">
<div class="modal-header"><h5 class="modal-title"><i class="fas fa-plus-circle me-2 text-primary"></i>New Lab Booking</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body p-4">
<div class="row g-3">
<div class="col-md-6"><label class="form-label fw-semibold">Course Name <span class="text-danger">*</span></label><input type="text" name="course_name" class="form-control" required placeholder="e.g. Diploma in Nursing"></div>
<div class="col-md-6"><label class="form-label fw-semibold">Instructor Name <span class="text-danger">*</span></label><input type="text" name="instructor_name" class="form-control" required></div>
<div class="col-md-6"><label class="form-label fw-semibold">Instructor Email</label><input type="email" name="instructor_email" class="form-control" placeholder="email@example.com"></div>
<div class="col-md-6"><label class="form-label fw-semibold">Number of Students <span class="text-danger">*</span></label><input type="number" name="number_of_students" class="form-control" required min="1"></div>
<div class="col-md-4"><label class="form-label fw-semibold">Booking Date <span class="text-danger">*</span></label><input type="date" name="booking_date" class="form-control" required value="<?=date('Y-m-d')?>"></div>
<div class="col-md-4"><label class="form-label fw-semibold">Time Slot <span class="text-danger">*</span></label><select name="time_slot" class="form-select" required>
<option value="">-- Select --</option>
<?php foreach($time_slots as $ts):?><option value="<?=htmlspecialchars($ts)?>"><?=htmlspecialchars($ts)?></option><?php endforeach;?>
</select></div>
<div class="col-md-4"><label class="form-label fw-semibold">Lab Assigned <span class="text-danger">*</span></label><select name="lab_assigned" class="form-select" required>
<option value="">-- Select --</option>
<?php foreach($labs as $lab):?><option value="<?=htmlspecialchars($lab)?>"><?=htmlspecialchars($lab)?></option><?php endforeach;?>
</select></div>
<div class="col-12"><label class="form-label fw-semibold">Purpose</label><textarea name="purpose" class="form-control" rows="2" placeholder="Purpose of the lab session"></textarea></div>
<div class="col-12"><label class="form-label fw-semibold">Special Requirements</label><textarea name="special_requirements" class="form-control" rows="2" placeholder="Any special software, equipment, or setup needed"></textarea></div>
</div>
</div>
<div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Create Booking</button></div>
</form>
</div>
</div>

<script>
window.addEventListener('unhandledrejection',function(e){
  var url = '';
  try { if (e.reason && typeof e.reason === 'object') url = e.reason.url || ''; else if (typeof e.reason === 'string') url = e.reason; } catch(ex) {}
  if (url.indexOf('/writing/') > -1 || url.indexOf('/generate/') > -1 || url.indexOf('/site_integration/') > -1) e.preventDefault();
});
</script>
<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
</body>
</html>
