<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
$ctx = bootstrapStaffDashboard(['director', 'finance', 'bursar', 'store', 'admin', 'secretary', 'registrar', 'deputy', 'principal', 'storekeeper', 'head-nursing', 'head-midwifery', 'nursing', 'midwifery', 'ict', 'hr', 'non-teaching', 'ceo']);
$user = $ctx['user'];
$userId = (int)($user['id'] ?? 0);

$conn = getConnection();

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Ensure department_requests table has correct schema
if ($conn) {
    $createTable = "CREATE TABLE IF NOT EXISTS department_requests (
        id INT AUTO_INCREMENT PRIMARY KEY,
        request_number VARCHAR(50) NOT NULL,
        from_department VARCHAR(100) NOT NULL,
        to_department VARCHAR(100) DEFAULT 'Store',
        item_name VARCHAR(300) NOT NULL,
        quantity INT DEFAULT 1,
        unit VARCHAR(50) DEFAULT '',
        purpose TEXT,
        urgency VARCHAR(50) DEFAULT 'Normal',
        status VARCHAR(50) DEFAULT 'Pending',
        requested_by INT,
        approved_by INT,
        notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_status (status),
        INDEX idx_from_dept (from_department),
        INDEX idx_to_dept (to_department)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    @$conn->query($createTable);
}

function dr_fetch($conn, $sql) {
    if (!$conn) return [];
    $r = $conn->query($sql);
    return $r ? $r->fetch_all(MYSQLI_ASSOC) : [];
}

function dr_count($conn, $sql) {
    if (!$conn) return 0;
    $r = $conn->query($sql);
    if (!$r) return 0;
    $row = $r->fetch_row();
    return (int)($row[0] ?? 0);
}

$msg = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $conn) {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
        die('Invalid CSRF token');
    }
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $rn = 'DR-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
        $fd = $_POST['from_department'] ?? '';
        $td = $_POST['to_department'] ?? 'Store';
        $in = $_POST['item_name'] ?? '';
        $qty = max(1, (int)($_POST['quantity'] ?? 1));
        $un = $_POST['unit'] ?? '';
        $pr = $_POST['purpose'] ?? '';
        $ug = $_POST['urgency'] ?? 'Normal';
        $stmt = $conn->prepare("INSERT INTO department_requests (request_number, from_department, to_department, item_name, quantity, unit, purpose, urgency, status, requested_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Pending', ?)");
        $stmt->bind_param("ssssissii", $rn, $fd, $td, $in, $qty, $un, $pr, $ug, $userId);
        if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
        $stmt->close();
        $msg = ['t' => 'success', 'm' => "Request <strong>$rn</strong> created."];
        header('Location: department-requests.php'); exit;
    }

    if ($action === 'approve' && in_array($_POST['from'] ?? '', ['table','card'])) {
        $id = (int)($_POST['request_id'] ?? 0);
        if ($id > 0) {
            $stmt = $conn->prepare("UPDATE department_requests SET status='Approved', approved_by=?, updated_at=NOW() WHERE id=? AND status='Pending'");
            $stmt->bind_param("ii", $userId, $id);
            if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
            $stmt->close();
            $_SESSION['dr_msg'] = ['t' => 'success', 'm' => 'Request approved.'];
        }
        header('Location: department-requests.php'); exit;
    }

    if ($action === 'reject') {
        $id = (int)($_POST['request_id'] ?? 0);
        $nt = $_POST['notes'] ?? '';
        if ($id > 0) {
            $stmt = $conn->prepare("UPDATE department_requests SET status='Rejected', notes=?, updated_at=NOW() WHERE id=? AND status='Pending'");
            $stmt->bind_param("si", $nt, $id);
            if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
            $stmt->close();
            $_SESSION['dr_msg'] = ['t' => 'success', 'm' => 'Request rejected.'];
        }
        header('Location: department-requests.php'); exit;
    }

    if ($action === 'fulfill') {
        $id = (int)($_POST['request_id'] ?? 0);
        $nt = $_POST['notes'] ?? '';
        if ($id > 0) {
            if ($nt) {
                $stmt = $conn->prepare("UPDATE department_requests SET status='Fulfilled', approved_by=?, notes=CONCAT(IFNULL(notes,''), ?), updated_at=NOW() WHERE id=? AND status='Approved'");
                $nl = "\n" . $nt;
                $stmt->bind_param("isi", $userId, $nl, $id);
            } else {
                $stmt = $conn->prepare("UPDATE department_requests SET status='Fulfilled', approved_by=?, updated_at=NOW() WHERE id=? AND status='Approved'");
                $stmt->bind_param("ii", $userId, $id);
            }
            if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
            $stmt->close();
            $_SESSION['dr_msg'] = ['t' => 'success', 'm' => 'Request fulfilled.'];
        }
        header('Location: department-requests.php'); exit;
    }
}

$smsg = $_SESSION['dr_msg'] ?? $msg;
unset($_SESSION['dr_msg']);

$totalPending   = dr_count($conn, "SELECT COUNT(*) FROM department_requests WHERE status='Pending'");
$totalApproved  = dr_count($conn, "SELECT COUNT(*) FROM department_requests WHERE status='Approved'");
$totalFulfilled = dr_count($conn, "SELECT COUNT(*) FROM department_requests WHERE status='Fulfilled'");
$totalRejected  = dr_count($conn, "SELECT COUNT(*) FROM department_requests WHERE status='Rejected'");

$sf = $_GET['status'] ?? '';
$allowed = ['Pending','Approved','Fulfilled','Rejected'];
$where = ($sf && in_array($sf, $allowed)) ? "WHERE r.status='$sf'" : '';

$requests = dr_fetch($conn, "SELECT r.*, s.full_name AS requester_name FROM department_requests r LEFT JOIN igangaschool_staffs.staff s ON r.requested_by=s.id $where ORDER BY FIELD(r.status,'Pending','Approved','Fulfilled','Rejected'), r.created_at DESC LIMIT 100");

$pageTitle = 'Department Requests';
?><!DOCTYPE html>
<html lang="en">
<head><?php include_once __DIR__ . '/../includes/dashboard_head.php'; ?>
<style>
.urgency-normal { background:#dbeafe !important; color:#1d4ed8 !important; }
.urgency-urgent { background:#ffedd5 !important; color:#c2410c !important; }
.urgency-emergency { background:#fee2e2 !important; color:#991b1b !important; }
</style>
</head>
<body>
<?php include_once __DIR__ . '/../includes/sidebar.php'; ?>
<?php include_once __DIR__ . '/../includes/dashboard_topbar.php'; ?>
<div class="page-content">
<div class="top-bar">
<div><strong><i class="fas fa-exchange-alt me-2 text-primary"></i>Department Requests</strong><span class="text-muted small ms-2">Inter-department requisitions</span></div>
<div class="d-flex align-items-center gap-2">
<span class="text-muted small d-none d-md-block"><?= date('D, d M Y') ?></span>
<button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#newRequestModal"><i class="fas fa-plus me-1"></i>New Request</button>
<a href="#" class="btn btn-sm btn-outline-danger" onclick="event.preventDefault();var f=document.createElement('form');f.method='POST';f.action='../logout.php';document.body.appendChild(f);f.submit();"><i class="fas fa-sign-out-alt me-1"></i></a>
</div>
</div>

<div class="content-area">
<?php if ($smsg): ?>
<div class="alert alert-<?= $smsg['t'] === 'success' ? 'success' : 'danger' ?> alert-dismissible fade show py-2"><?= $smsg['m'] ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>

<div class="row g-3 mb-4">
<div class="col-6 col-md-3"><div class="stat-card"><div class="si si-orange"><i class="fas fa-clock"></i></div><div class="stat-content"><h3><?= $totalPending ?></h3><p>Pending</p></div></div></div>
<div class="col-6 col-md-3"><div class="stat-card"><div class="si si-green"><i class="fas fa-check-circle"></i></div><div class="stat-content"><h3><?= $totalApproved ?></h3><p>Approved</p></div></div></div>
<div class="col-6 col-md-3"><div class="stat-card"><div class="si si-blue"><i class="fas fa-truck-loading"></i></div><div class="stat-content"><h3><?= $totalFulfilled ?></h3><p>Fulfilled</p></div></div></div>
<div class="col-6 col-md-3"><div class="stat-card"><div class="si si-red"><i class="fas fa-times-circle"></i></div><div class="stat-content"><h3><?= $totalRejected ?></h3><p>Rejected</p></div></div></div>
</div>

<div class="d-flex flex-wrap gap-2 mb-4 no-print">
<a href="department-requests.php" class="btn btn-sm filter-btn <?= !$sf ? 'btn-primary' : 'btn-outline-secondary' ?>">All</a>
<a href="department-requests.php?status=Pending" class="btn btn-sm filter-btn <?= $sf === 'Pending' ? 'btn-warning' : 'btn-outline-warning' ?>">Pending</a>
<a href="department-requests.php?status=Approved" class="btn btn-sm filter-btn <?= $sf === 'Approved' ? 'btn-success' : 'btn-outline-success' ?>">Approved</a>
<a href="department-requests.php?status=Fulfilled" class="btn btn-sm filter-btn <?= $sf === 'Fulfilled' ? 'btn-secondary' : 'btn-outline-secondary' ?>">Fulfilled</a>
<a href="department-requests.php?status=Rejected" class="btn btn-sm filter-btn <?= $sf === 'Rejected' ? 'btn-danger' : 'btn-outline-danger' ?>">Rejected</a>
</div>

<div class="section-card">
<div class="d-flex justify-content-between align-items-center mb-3">
<h5 class="fw-bold mb-0"><i class="fas fa-list me-2 text-primary"></i>Department Requests</h5>
<span class="badge bg-secondary"><?= count($requests) ?> requests</span>
</div>
<?php if (empty($requests)): ?>
<div class="empty-state"><i class="fas fa-exchange-alt"></i><p>No department requests found.</p></div>
<?php else: ?>
<div class="table-responsive">
<table class="table table-hover align-middle">
<thead class="table-light">
<tr><th>Request #</th><th>From</th><th>To</th><th>Item</th><th>Qty</th><th>Urgency</th><th>Status</th><th>Requested By</th><th>Date</th><th class="no-print">Actions</th></tr>
</thead>
<tbody>
<?php foreach ($requests as $r):
$urgencyMap = ['Normal'=>'urgency-normal','Urgent'=>'urgency-urgent','Emergency'=>'urgency-emergency'];
$statusMap = ['Pending'=>'bg-warning text-dark','Approved'=>'bg-success','Rejected'=>'bg-danger','Fulfilled'=>'bg-secondary'];
$uclass = $urgencyMap[$r['urgency']] ?? 'bg-secondary';
$sclass = $statusMap[$r['status']] ?? 'bg-secondary';
?>
<tr>
<td><code><?= htmlspecialchars($r['request_number']) ?></code></td>
<td><?= htmlspecialchars($r['from_department']) ?></td>
<td><?= htmlspecialchars($r['to_department']) ?></td>
<td><strong><?= htmlspecialchars($r['item_name']) ?></strong></td>
<td><?= (int)$r['quantity'] ?> <?= htmlspecialchars($r['unit']) ?></td>
<td><span class="badge <?= $uclass ?>"><?= htmlspecialchars($r['urgency']) ?></span></td>
<td><span class="status-pill <?= strtolower($r['status']) === 'approved' ? 'success' : (strtolower($r['status']) === 'rejected' ? 'danger' : (strtolower($r['status']) === 'fulfilled' ? 'info' : 'warning')) ?>"><?= htmlspecialchars($r['status']) ?></span></td>
<td><small><?= htmlspecialchars($r['requester_name'] ?? 'N/A') ?></small></td>
<td><small class="text-muted"><?= date('d M Y', strtotime($r['created_at'])) ?></small></td>
<td class="no-print">
<div class="dropdown">
<button class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown"><i class="fas fa-cog"></i></button>
<ul class="dropdown-menu dropdown-menu-end">
<?php if ($r['status'] === 'Pending'): ?>
<li><form method="POST"><input type="hidden" name="action" value="approve"><input type="hidden" name="request_id" value="<?= $r['id'] ?>"><input type="hidden" name="from" value="table"><button class="dropdown-item small text-success"><i class="fas fa-check me-2"></i>Approve</button></form></li>
<li><button class="dropdown-item small text-danger" onclick="openReject(<?= $r['id'] ?>)"><i class="fas fa-times me-2"></i>Reject</button></li>
<?php elseif ($r['status'] === 'Approved'): ?>
<li><button class="dropdown-item small text-primary" onclick="openFulfill(<?= $r['id'] ?>)"><i class="fas fa-check-double me-2"></i>Mark Fulfilled</button></li>
<?php endif; ?>
<?php if ($r['notes']): ?>
<li><hr class="dropdown-divider"></li>
<li><h6 class="dropdown-header">Notes</h6></li>
<li><span class="dropdown-item-text small text-wrap" style="max-width:250px"><?= nl2br(htmlspecialchars($r['notes'])) ?></span></li>
<?php endif; ?>
<?php if ($r['purpose']): ?>
<li><hr class="dropdown-divider"></li>
<li><h6 class="dropdown-header">Purpose</h6></li>
<li><span class="dropdown-item-text small text-wrap" style="max-width:250px"><?= htmlspecialchars($r['purpose']) ?></span></li>
<?php endif; ?>
</ul>
</div>
</td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
<?php endif; ?>
</div>
</div>
</div>

<div class="modal fade" id="newRequestModal" tabindex="-1">
<div class="modal-dialog modal-lg">
<form method="POST" class="modal-content">
<input type="hidden" name="action" value="create">
<div class="modal-header"><h5 class="modal-title"><i class="fas fa-plus me-2"></i>New Department Request</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body p-4">
<div class="row g-3">
<div class="col-md-6">
<label class="form-label fw-semibold">From Department <span class="text-danger">*</span></label>
<select class="form-select" name="from_department" required>
<option value="">-- Select --</option>
<option value="Administration">Administration</option>
<option value="Finance">Finance</option>
<option value="Nursing">Nursing</option>
<option value="Midwifery">Midwifery</option>
<option value="ICT">ICT</option>
<option value="Academic">Academic</option>
<option value="Engineering">Engineering</option>
<option value="Store">Store</option>
<option value="Other">Other</option>
</select>
</div>
<div class="col-md-6">
<label class="form-label fw-semibold">To Department <span class="text-danger">*</span></label>
<select class="form-select" name="to_department" required>
<option value="Store" selected>Store</option>
<option value="Administration">Administration</option>
<option value="Finance">Finance</option>
<option value="Nursing">Nursing</option>
<option value="Midwifery">Midwifery</option>
<option value="ICT">ICT</option>
<option value="Academic">Academic</option>
<option value="Other">Other</option>
</select>
</div>
<div class="col-md-6">
<label class="form-label fw-semibold">Item Name <span class="text-danger">*</span></label>
<input type="text" class="form-control" name="item_name" required placeholder="e.g., Stationery, Equipment">
</div>
<div class="col-md-3">
<label class="form-label fw-semibold">Quantity <span class="text-danger">*</span></label>
<input type="number" class="form-control" name="quantity" value="1" min="1" required>
</div>
<div class="col-md-3">
<label class="form-label fw-semibold">Unit</label>
<input type="text" class="form-control" name="unit" placeholder="pcs, boxes, kg">
</div>
<div class="col-md-6">
<label class="form-label fw-semibold">Urgency <span class="text-danger">*</span></label>
<select class="form-select" name="urgency" required>
<option value="Normal">Normal</option>
<option value="Urgent">Urgent</option>
<option value="Emergency">Emergency</option>
</select>
</div>
<div class="col-12">
<label class="form-label fw-semibold">Purpose / Reason</label>
<textarea class="form-control" name="purpose" rows="3" placeholder="Explain why these items are needed..."></textarea>
</div>
</div>
</div>
<div class="modal-footer">
<button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
<button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane me-1"></i>Submit Request</button>
</div>
</form>
</div>
</div>

<div class="modal fade" id="rejectModal" tabindex="-1">
<div class="modal-dialog">
<form method="POST" class="modal-content">
<input type="hidden" name="action" value="reject">
<input type="hidden" name="request_id" id="rejReqId">
<div class="modal-header bg-danger text-white"><h5 class="modal-title"><i class="fas fa-times-circle me-2"></i>Reject Request</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
<div class="modal-body p-4">
<label class="form-label fw-semibold">Reason for Rejection <span class="text-danger">*</span></label>
<textarea class="form-control" name="notes" rows="4" required placeholder="Explain why this request is being rejected..."></textarea>
</div>
<div class="modal-footer">
<button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
<button type="submit" class="btn btn-danger"><i class="fas fa-check me-1"></i>Reject</button>
</div>
</form>
</div>
</div>

<div class="modal fade" id="fulfillModal" tabindex="-1">
<div class="modal-dialog">
<form method="POST" class="modal-content">
<input type="hidden" name="action" value="fulfill">
<input type="hidden" name="request_id" id="fulfillReqId">
<div class="modal-header bg-success text-white"><h5 class="modal-title"><i class="fas fa-check-double me-2"></i>Mark Fulfilled</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
<div class="modal-body p-4">
<label class="form-label fw-semibold">Fulfillment Notes</label>
<textarea class="form-control" name="notes" rows="4" placeholder="Optional notes about fulfillment..."></textarea>
</div>
<div class="modal-footer">
<button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
<button type="submit" class="btn btn-success"><i class="fas fa-check me-1"></i>Mark Fulfilled</button>
</div>
</form>
</div>
</div>

<script>
function openReject(id) { document.getElementById('rejReqId').value = id; new bootstrap.Modal(document.getElementById('rejectModal')).show(); }
function openFulfill(id) { document.getElementById('fulfillReqId').value = id; new bootstrap.Modal(document.getElementById('fulfillModal')).show(); }
</script>
<script>document.addEventListener('DOMContentLoaded',function(){var t='<?=htmlspecialchars($_SESSION["csrf_token"] ?? "")?>';document.querySelectorAll('form[method="POST"],form[method="post"]').forEach(function(f){if(!f.querySelector('input[name="csrf_token"]')){var i=document.createElement('input');i.type='hidden';i.name='csrf_token';i.value=t;f.appendChild(i);}});});</script>
<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
</body>
</html>
