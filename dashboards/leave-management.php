<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
$ctx = bootstrapStaffDashboard(['hr', 'director', 'admin']);
$conn = $ctx['staff'];
if ($conn) {
    $staff_db = defined('STAFF_DB_NAME') ? STAFF_DB_NAME : 'igangaschool_staffs';
    $conn->query("CREATE TABLE IF NOT EXISTS `{$staff_db}`.`leave_types` (
        id INT AUTO_INCREMENT PRIMARY KEY,
        type_name VARCHAR(100) NOT NULL,
        description TEXT,
        default_days INT DEFAULT 0,
        is_active TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $conn->query("CREATE TABLE IF NOT EXISTS `{$staff_db}`.`leave_requests` (
        id INT AUTO_INCREMENT PRIMARY KEY,
        staff_id INT NOT NULL,
        leave_type_id INT DEFAULT 0,
        start_date DATE NOT NULL,
        end_date DATE NOT NULL,
        reason TEXT,
        status VARCHAR(50) DEFAULT 'Pending',
        reviewed_by INT DEFAULT 0,
        reviewed_at DATETIME DEFAULT NULL,
        comments TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        KEY idx_staff (staff_id),
        KEY idx_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $conn->query("CREATE TABLE IF NOT EXISTS `{$staff_db}`.`leave_balance` (
        id INT AUTO_INCREMENT PRIMARY KEY,
        staff_id INT NOT NULL,
        leave_type_id INT DEFAULT 0,
        year YEAR DEFAULT NULL,
        balance_days INT DEFAULT 0,
        used_days INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uniq_staff_type_year (staff_id, leave_type_id, year)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    // Seed default leave types if empty
    $chk = $conn->query("SELECT COUNT(*) c FROM leave_types");
    if ($chk && (int)$chk->fetch_assoc()['c'] === 0) {
        $conn->query("INSERT INTO leave_types (type_name, description, default_days) VALUES 
            ('Annual Leave', 'Yearly paid leave', 21),
            ('Sick Leave', 'Medical leave', 14),
            ('Maternity Leave', 'Maternity/paternity leave', 90),
            ('Compassionate Leave', 'Emergency leave', 5),
            ('Study Leave', 'Educational leave', 30),
            ('Unpaid Leave', 'Leave without pay', 0)");
    }
}
$user = $ctx['user'];
$user_role = $_SESSION['role'] ?? '';
$pageTitle = 'Leave Management';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$pending = 0; $approvedMonth = 0; $onLeave = 0; $balances = 0; $records = []; $leaveTypes = []; $staffList = [];
if ($conn) {
    $r = $conn->query("SELECT COUNT(*) c FROM leave_requests WHERE status='Pending'");
    if ($r) $pending = (int)$r->fetch_assoc()['c'];
    $r = $conn->query("SELECT COUNT(*) c FROM leave_requests WHERE status='Approved' AND MONTH(start_date)=MONTH(CURDATE()) AND YEAR(start_date)=YEAR(CURDATE())");
    if ($r) $approvedMonth = (int)$r->fetch_assoc()['c'];
    $r = $conn->query("SELECT COUNT(*) c FROM leave_requests WHERE status='Approved' AND CURDATE() BETWEEN start_date AND end_date");
    if ($r) $onLeave = (int)$r->fetch_assoc()['c'];
    $r = $conn->query("SELECT COALESCE(SUM(balance_days),0) c FROM leave_balance");
    if ($r) $balances = (int)$r->fetch_assoc()['c'];
    $search = trim($_GET['search'] ?? '');
    $statusFilter = trim($_GET['status'] ?? '');
    $w = "1=1"; $bindTypes = ''; $bindValues = [];
    if ($search !== '') { $w .= " AND (s.full_name LIKE ? OR lt.type_name LIKE ? OR lr.status LIKE ?)"; $bindTypes .= 'sss'; $bindValues[] = "%$search%"; $bindValues[] = "%$search%"; $bindValues[] = "%$search%"; }
    if ($statusFilter !== '') { $w .= " AND lr.status=?"; $bindTypes .= 's'; $bindValues[] = $statusFilter; }
    if ($bindTypes) {
        $stmt = $conn->prepare("SELECT lr.*, s.full_name staff_name, lt.type_name leave_type, DATEDIFF(lr.end_date,lr.start_date)+1 days FROM leave_requests lr JOIN staff s ON lr.staff_id=s.id LEFT JOIN leave_types lt ON lr.leave_type_id=lt.id WHERE $w ORDER BY lr.created_at DESC LIMIT 100");
        if ($stmt) { $stmt->bind_param($bindTypes, ...$bindValues); if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); }; $q = $stmt->get_result(); if ($q) $records = isnm_fetch_all($q); $stmt->close(); }
    } else {
        $q = $conn->query("SELECT lr.*, s.full_name staff_name, lt.type_name leave_type, DATEDIFF(lr.end_date,lr.start_date)+1 days FROM leave_requests lr JOIN staff s ON lr.staff_id=s.id LEFT JOIN leave_types lt ON lr.leave_type_id=lt.id WHERE $w ORDER BY lr.created_at DESC LIMIT 100");
        if ($q) $records = isnm_fetch_all($q);
    }
    $lt = $conn->query("SELECT * FROM leave_types ORDER BY type_name");
    if ($lt) while ($row = $lt->fetch_assoc()) $leaveTypes[] = $row;
    $sl = $conn->query("SELECT id, full_name FROM staff WHERE status='Active' ORDER BY full_name LIMIT 200");
    if ($sl) while ($row = $sl->fetch_assoc()) $staffList[] = $row;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
        die('Invalid CSRF token');
    }
    $action = $_POST['action'] ?? '';
    if (!$conn) { $_SESSION['error'] = 'DB connection failed'; header('Location: leave-management.php'); exit; }

    if ($action === 'add_request') {
        $staffId = intval($_POST['staff_id'] ?? 0);
        $leaveTypeId = intval($_POST['leave_type_id'] ?? 0);
        $startDate = trim($_POST['start_date'] ?? '');
        $endDate = trim($_POST['end_date'] ?? '');
        $reason = trim($_POST['reason'] ?? '');
        if ($staffId && $leaveTypeId && $startDate && $endDate) {
            $stmt = $conn->prepare("INSERT INTO leave_requests (staff_id, leave_type_id, start_date, end_date, reason, status) VALUES (?, ?, ?, ?, ?, 'Pending')");
            if ($stmt) {
                $stmt->bind_param('iisss', $staffId, $leaveTypeId, $startDate, $endDate, $reason);
                if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
                $stmt->close();
                $_SESSION['success'] = 'Leave request submitted.';
            } else {
                $_SESSION['error'] = 'Database error.';
            }
        } else {
            $_SESSION['error'] = 'All fields required.';
        }
        header('Location: leave-management.php'); exit;
    }

    if ($action === 'update_status') {
        $id = intval($_POST['id'] ?? 0);
        $newStatus = trim($_POST['status'] ?? '');
        if (in_array($newStatus, ['Approved','Rejected','Cancelled']) && $id > 0) {
            $stmt = $conn->prepare("UPDATE leave_requests SET status=?, reviewed_by=? WHERE id=?");
            if ($stmt) {
                $reviewedBy = (int)($_SESSION['user_id'] ?? 0);
                $stmt->bind_param('sii', $newStatus, $reviewedBy, $id);
                if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
                $stmt->close();
                $_SESSION['success'] = "Leave request $newStatus.";
            }
        }
        header('Location: leave-management.php'); exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include_once __DIR__ . '/../includes/dashboard_head.php'; ?>
<style>@media print { body * { visibility: hidden; } .print-area, .print-area * { visibility: visible; } .print-area { position: absolute; left: 0; top: 0; width: 100%; } .no-print { display: none !important; } .main { margin-left: 0 !important; padding: 20px !important; } }</style>
</head>
<body>
<?php include_once __DIR__ . '/../includes/sidebar.php'; ?>
<?php include_once __DIR__ . '/../includes/dashboard_topbar.php'; ?>
<section class="content-section dashboard-section active" data-section="overview">
<main class="main" style="margin-left:270px;padding:32px;">
<div class="d-flex justify-content-between align-items-center mb-4 no-print">
<h4 class="fw-bold mb-0"><i class="fas fa-plane me-2"></i>Leave Management</h4>
<span class="text-muted small"><?= date('l, d M Y') ?></span>
</div>

<?php if(!empty($_SESSION['success'])): ?><div class="alert alert-success py-2 no-print"><?= htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></div><?php endif; ?>
<?php if(!empty($_SESSION['error'])): ?><div class="alert alert-danger py-2 no-print"><?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></div><?php endif; ?>

<div class="row g-3 mb-4 no-print">
<?php $c=[['Pending Requests',$pending,'warning','hourglass-half'],['Approved This Month',$approvedMonth,'success','check-circle'],['On Leave Today',$onLeave,'info','calendar-day'],['Remaining Balances',$balances,'primary','wallet']]; foreach($c as $s): ?>
<div class="col-md-3">
<div class="stat-card <?= $s[2] ?>">
<div class="stat-icon"><i class="fas fa-<?= $s[3] ?>"></i></div>
<div class="stat-content"><h3><?= number_format($s[1]) ?></h3><p><?= $s[0] ?></p></div>
</div>
</div>
<?php endforeach; ?>
</div>

<div class="no-print d-flex justify-content-between align-items-center mb-3">
<h5 class="fw-bold mb-0"><i class="fas fa-list me-2"></i>Leave Requests</h5>
<div>
<button class="btn btn-sm btn-outline-primary me-1" onclick="window.print()"><i class="fas fa-print"></i> Print</button>
<button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#leaveModal"><i class="fas fa-plus me-1"></i>New Request</button>
</div>
</div>

<form method="GET" class="row g-2 mb-3 no-print">
<div class="col-md-5"><input type="text" name="search" class="form-control form-control-sm" placeholder="Search staff name, leave type..." value="<?= htmlspecialchars($_GET['search'] ?? '') ?>"></div>
<div class="col-md-2"><select name="status" class="form-select form-select-sm"><option value="">All Status</option><option <?= ($_GET['status']??'')==='Pending'?'selected':''?>>Pending</option><option <?= ($_GET['status']??'')==='Approved'?'selected':''?>>Approved</option><option <?= ($_GET['status']??'')==='Rejected'?'selected':''?>>Rejected</option><option <?= ($_GET['status']??'')==='Cancelled'?'selected':''?>>Cancelled</option></select></div>
<div class="col-md-2"><button class="btn btn-sm btn-primary w-100"><i class="fas fa-search"></i></button></div>
<div class="col-md-2"><a href="leave-management.php" class="btn btn-sm btn-outline-secondary w-100"><i class="fas fa-times"></i> Clear</a></div>
</form>

<div class="print-area content-section">
<div class="mb-2"><input class="form-control form-control-sm" style="max-width:300px" id="srchTLMR" type="text" placeholder="Search..." onkeyup="filterTable('srchTLMR','tblTLMR')"></div>
<div class="table-responsive">
<table id="tblTLMR" class="table table-striped table-hover align-middle">
<thead class="table-light"><tr><th>Staff</th><th>Leave Type</th><th>Start</th><th>End</th><th>Days</th><th>Reason</th><th>Status</th><th class="no-print">Actions</th></tr></thead>
<tbody>
<?php if(empty($records)): ?>
<tr><td colspan="8" class="text-center text-muted py-3">No leave requests found.</td></tr>
<?php else: foreach($records as $r):
$st=$r['status']??'';
$bc=$st==='Approved'?'bg-success':($st==='Pending'?'bg-warning text-dark':($st==='Rejected'?'bg-danger':'bg-secondary'));
?>
<tr>
<td><strong><?= htmlspecialchars($r['staff_name']??'-') ?></strong></td>
<td><?= htmlspecialchars($r['leave_type']??'-') ?></td>
<td><?= htmlspecialchars($r['start_date']??'-') ?></td>
<td><?= htmlspecialchars($r['end_date']??'-') ?></td>
<td><?= htmlspecialchars($r['days']??'-') ?></td>
<td><small><?= htmlspecialchars($r['reason']??'') ?></small></td>
<td><span class="badge <?= $bc ?>"><?= htmlspecialchars($st) ?></span></td>
<td class="no-print">
<?php if ($st === 'Pending'): ?>
<form method="POST" style="display:inline" onsubmit="return confirm('Approve this leave?')"><input type="hidden" name="action" value="update_status"><input type="hidden" name="id" value="<?= $r['id'] ?>"><input type="hidden" name="status" value="Approved"><button class="btn btn-sm btn-outline-success py-0 px-1" title="Approve"><i class="fas fa-check"></i></button></form>
<form method="POST" style="display:inline" onsubmit="return confirm('Reject this leave?')"><input type="hidden" name="action" value="update_status"><input type="hidden" name="id" value="<?= $r['id'] ?>"><input type="hidden" name="status" value="Rejected"><button class="btn btn-sm btn-outline-danger py-0 px-1" title="Reject"><i class="fas fa-times"></i></button></form>
<?php endif; ?>
</td>
</tr>
<?php endforeach; endif; ?>
</tbody>
</table>
</div>
</div>
</main>
</section>

<!-- New Leave Modal -->
<div class="modal fade" id="leaveModal" tabindex="-1"><div class="modal-dialog"><form method="POST" class="modal-content"><input type="hidden" name="action" value="add_request"><div class="modal-header bg-primary text-white"><h5 class="modal-title">New Leave Request</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div><div class="modal-body"><div class="row g-3">
<div class="col-12"><label class="form-label">Staff *</label><select name="staff_id" class="form-select" required><option value="">-- Select Staff --</option><?php foreach($staffList as $s): ?><option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['full_name']) ?></option><?php endforeach; ?></select></div>
<div class="col-12"><label class="form-label">Leave Type *</label><select name="leave_type_id" class="form-select" required><option value="">-- Select Type --</option><?php foreach($leaveTypes as $lt): ?><option value="<?= $lt['id'] ?>"><?= htmlspecialchars($lt['type_name']) ?></option><?php endforeach; ?></select></div>
<div class="col-md-6"><label class="form-label">Start Date *</label><input type="date" name="start_date" class="form-control" required></div>
<div class="col-md-6"><label class="form-label">End Date *</label><input type="date" name="end_date" class="form-control" required></div>
<div class="col-12"><label class="form-label">Reason</label><textarea name="reason" class="form-control" rows="3"></textarea></div>
</div></div><div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Submit Request</button></div></form></div></div>

<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
<script>
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
<script>document.addEventListener('DOMContentLoaded',function(){var t='<?=htmlspecialchars($_SESSION["csrf_token"] ?? "")?>';document.querySelectorAll('form[method="POST"],form[method="post"]').forEach(function(f){if(!f.querySelector('input[name="csrf_token"]')){var i=document.createElement('input');i.type='hidden';i.name='csrf_token';i.value=t;f.appendChild(i);}});});</script>
</body>
</html>
