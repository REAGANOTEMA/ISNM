<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';

$ctx = bootstrapStaffDashboard(['ict', 'it', 'lab', 'director']);
$staff_conn = $ctx['staff'];
$user = $ctx['user'];
$user_id = (int)($user['id'] ?? 0);
$user_role = $_SESSION['role'] ?? '';
$user_name = $user['full_name'] ?? 'Lab Manager';

function lab_q($conn, $sql) {
    if (!$conn) return 0;
    try { $r = $conn->query($sql); if (!$r) return 0; $row = $r->fetch_assoc(); return (int)($row[array_key_first($row)] ?? 0); }
    catch (Exception $e) { return 0; }
}

function lab_fetch($conn, $sql) {
    if (!$conn) return [];
    try { $r = $conn->query($sql); if (!$r) return []; return $r->fetch_all(MYSQLI_ASSOC); }
    catch (Exception $e) { return []; }
}

$ict_conn = null;
try { $ict_conn = getICTConnection(); } catch (Exception $e) {}

// Stats
$total_computers    = lab_q($ict_conn, "SELECT COUNT(*)c FROM lab_computers WHERE status!='deleted'");
$computers_online   = lab_q($ict_conn, "SELECT COUNT(*)c FROM lab_computers WHERE status='online'");
$computers_offline  = lab_q($ict_conn, "SELECT COUNT(*)c FROM lab_computers WHERE status='offline'");
$computers_maint    = lab_q($ict_conn, "SELECT COUNT(*)c FROM lab_computers WHERE status='maintenance'");
$pending_bookings   = lab_q($ict_conn, "SELECT COUNT(*)c FROM lab_bookings WHERE status='pending'");
$open_tickets       = lab_q($ict_conn, "SELECT COUNT(*)c FROM it_support_tickets WHERE status IN ('open','in_progress')");

// Load data
$computers = lab_fetch($ict_conn, "SELECT * FROM lab_computers WHERE status!='deleted' ORDER BY lab_name, computer_name");
$bookings  = lab_fetch($ict_conn, "SELECT lb.*, s.full_name as booked_by_name FROM lab_bookings lb LEFT JOIN igangaschoolofl_staffs_db.staff s ON lb.user_id=s.id ORDER BY lb.created_at DESC LIMIT 50");
$tickets   = lab_fetch($ict_conn, "SELECT t.*, s.full_name as assigned_name FROM it_support_tickets t LEFT JOIN igangaschoolofl_staffs_db.staff s ON t.assigned_to=s.id ORDER BY t.created_at DESC LIMIT 50");
$maintenance_logs = lab_fetch($ict_conn, "SELECT ml.*, s.full_name as logged_by_name FROM lab_maintenance_logs ml LEFT JOIN igangaschoolofl_staffs_db.staff s ON ml.logged_by=s.id ORDER BY ml.created_at DESC LIMIT 30");

// Handle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $ict_conn) {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_computer') {
        $name  = $ict_conn->real_escape_string($_POST['computer_name']);
        $lab   = $ict_conn->real_escape_string($_POST['lab_name']);
        $ip    = $ict_conn->real_escape_string($_POST['ip_address'] ?? '');
        $os    = $ict_conn->real_escape_string($_POST['operating_system'] ?? '');
        $specs = $ict_conn->real_escape_string($_POST['specifications'] ?? '');
        $ict_conn->query("INSERT INTO lab_computers (computer_name, lab_name, ip_address, operating_system, specifications, status) VALUES ('$name','$lab','$ip','$os','$specs','online')");
        $_SESSION['success'] = "Computer '$name' added.";
        header('Location: computer_lab.php'); exit;
    }

    if ($action === 'update_status') {
        $id = (int)$_POST['computer_id'];
        $status = $ict_conn->real_escape_string($_POST['status']);
        $ict_conn->query("UPDATE lab_computers SET status='$status' WHERE id=$id");
        $_SESSION['success'] = "Computer status updated to '$status'.";
        header('Location: computer_lab.php'); exit;
    }

    if ($action === 'delete_computer') {
        $id = (int)$_POST['computer_id'];
        $ict_conn->query("UPDATE lab_computers SET status='deleted' WHERE id=$id");
        $_SESSION['success'] = "Computer removed.";
        header('Location: computer_lab.php'); exit;
    }

    if ($action === 'edit_computer') {
        $id   = (int)$_POST['computer_id'];
        $name = $ict_conn->real_escape_string($_POST['computer_name']);
        $lab  = $ict_conn->real_escape_string($_POST['lab_name']);
        $ip   = $ict_conn->real_escape_string($_POST['ip_address'] ?? '');
        $os   = $ict_conn->real_escape_string($_POST['operating_system'] ?? '');
        $specs = $ict_conn->real_escape_string($_POST['specifications'] ?? '');
        $ict_conn->query("UPDATE lab_computers SET computer_name='$name', lab_name='$lab', ip_address='$ip', operating_system='$os', specifications='$specs' WHERE id=$id");
        $_SESSION['success'] = "Computer '$name' updated.";
        header('Location: computer_lab.php'); exit;
    }

    if ($action === 'approve_booking') {
        $id = (int)$_POST['booking_id'];
        $ict_conn->query("UPDATE lab_bookings SET status='confirmed' WHERE id=$id");
        $_SESSION['success'] = "Booking approved.";
        header('Location: computer_lab.php'); exit;
    }

    if ($action === 'complete_booking') {
        $id = (int)$_POST['booking_id'];
        $ict_conn->query("UPDATE lab_bookings SET status='completed' WHERE id=$id");
        $_SESSION['success'] = "Booking marked completed.";
        header('Location: computer_lab.php'); exit;
    }

    if ($action === 'cancel_booking') {
        $id = (int)$_POST['booking_id'];
        $ict_conn->query("UPDATE lab_bookings SET status='cancelled' WHERE id=$id");
        $_SESSION['success'] = "Booking cancelled.";
        header('Location: computer_lab.php'); exit;
    }

    if ($action === 'add_ticket_note') {
        $tid = (int)$_POST['ticket_id'];
        $note = $ict_conn->real_escape_string($_POST['note']);
        $ict_conn->query("UPDATE it_support_tickets SET notes=CONCAT(IFNULL(notes,''),'\n[$user_name]: $note') WHERE id=$tid");
        $_SESSION['success'] = "Note added.";
        header('Location: computer_lab.php'); exit;
    }

    if ($action === 'update_ticket_status') {
        $tid = (int)$_POST['ticket_id'];
        $st = $ict_conn->real_escape_string($_POST['ticket_status']);
        $ict_conn->query("UPDATE it_support_tickets SET status='$st', assigned_to=$user_id WHERE id=$tid");
        $_SESSION['success'] = "Ticket status updated.";
        header('Location: computer_lab.php'); exit;
    }

    if ($action === 'add_maintenance') {
        $cid = (int)$_POST['computer_id'];
        $desc = $ict_conn->real_escape_string($_POST['description']);
        $type = $ict_conn->real_escape_string($_POST['maintenance_type']);
        $ict_conn->query("INSERT INTO lab_maintenance_logs (computer_id, maintenance_type, description, logged_by) VALUES ($cid,'$type','$desc',$user_id)");
        $ict_conn->query("UPDATE lab_computers SET status='maintenance' WHERE id=$cid");
        $_SESSION['success'] = "Maintenance log added.";
        header('Location: computer_lab.php'); exit;
    }

    header('Location: computer_lab.php'); exit;
}

$msg = $_SESSION['success'] ?? null; unset($_SESSION['success']);
$err = $_SESSION['error'] ?? null; unset($_SESSION['error']);
$tab = $_GET['tab'] ?? 'dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include_once __DIR__ . '/../includes/dashboard_head.php'; ?>
<style>
.comp-status { display:inline-flex; align-items:center; gap:6px; padding:3px 12px; border-radius:20px; font-size:12px; font-weight:600; }
.comp-status.online { background:#dcfce7; color:#166534; }
.comp-status.offline { background:#fef2f2; color:#991b1b; }
.comp-status.maintenance { background:#fff7ed; color:#9a3412; }
.comp-card { background:#fff; border-radius:12px; padding:16px; border:1px solid #e5e7eb; transition:all .2s; }
.comp-card:hover { box-shadow:0 4px 12px rgba(0,0,0,0.08); border-color:#cbd5e1; }
.ticket-priority-high { border-left:4px solid #dc2626; }
.ticket-priority-medium { border-left:4px solid #f59e0b; }
.ticket-priority-low { border-left:4px solid #22c55e; }
</style>
</head>
<body>
<?php include_once __DIR__ . '/../includes/sidebar.php'; ?>
<div class="page-content">
    <div class="top-bar">
        <div><strong><i class="fas fa-desktop me-2 text-primary"></i>Computer Lab Management</strong><span class="text-muted small ms-2"><?= htmlspecialchars($user_name) ?></span></div>
        <div class="d-flex align-items-center gap-2">
            <span class="text-muted small d-none d-md-block"><?= date('D, d M Y') ?></span>
            <a href="../news.php" class="btn btn-sm btn-outline-light"><i class="fas fa-newspaper"></i></a>
            <a href="../index.php" class="btn btn-sm btn-outline-light"><i class="fas fa-home"></i></a>
            <a href="../logout.php" class="btn btn-sm btn-outline-danger"><i class="fas fa-sign-out-alt"></i></a>
        </div>
    </div>

    <div class="content-section active content-area">
        <?php if ($msg): ?><div class="alert alert-success alert-dismissible fade show py-2"><?= htmlspecialchars($msg) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>
        <?php if ($err): ?><div class="alert alert-danger alert-dismissible fade show py-2"><?= htmlspecialchars($err) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>

        <div class="stat-card-grid mb-4">
            <div class="stat-card-blue"><i class="fas fa-desktop stat-icon"></i><div><h3><?= $total_computers ?></h3><p>Total Computers</p></div></div>
            <div class="stat-card-green"><i class="fas fa-check-circle stat-icon"></i><div><h3><?= $computers_online ?></h3><p>Online</p></div></div>
            <div class="stat-card-red"><i class="fas fa-times-circle stat-icon"></i><div><h3><?= $computers_offline ?></h3><p>Offline</p></div></div>
            <div class="stat-card-orange"><i class="fas fa-tools stat-icon"></i><div><h3><?= $computers_maint ?></h3><p>Maintenance</p></div></div>
            <div class="stat-card-purple"><i class="fas fa-calendar-check stat-icon"></i><div><h3><?= $pending_bookings ?></h3><p>Pending Bookings</p></div></div>
            <div class="stat-card-yellow"><i class="fas fa-ticket-alt stat-icon"></i><div><h3><?= $open_tickets ?></h3><p>Open Tickets</p></div></div>
        </div>

        <div class="filter-pills mb-3">
            <a href="?tab=dashboard" class="filter-pill <?= $tab==='dashboard'?'active':'' ?>"><i class="fas fa-chart-pie me-1"></i>Dashboard</a>
            <a href="?tab=computers" class="filter-pill <?= $tab==='computers'?'active':'' ?>"><i class="fas fa-desktop me-1"></i>Computers <?= $total_computers ? '<span class="badge bg-secondary ms-1">'.$total_computers.'</span>' : '' ?></a>
            <a href="?tab=bookings" class="filter-pill <?= $tab==='bookings'?'active':'' ?>"><i class="fas fa-calendar me-1"></i>Bookings <?= $pending_bookings ? '<span class="badge bg-warning ms-1">'.$pending_bookings.'</span>' : '' ?></a>
            <a href="?tab=tickets" class="filter-pill <?= $tab==='tickets'?'active':'' ?>"><i class="fas fa-ticket-alt me-1"></i>Tickets <?= $open_tickets ? '<span class="badge bg-danger ms-1">'.$open_tickets.'</span>' : '' ?></a>
            <a href="?tab=maintenance" class="filter-pill <?= $tab==='maintenance'?'active':'' ?>"><i class="fas fa-tools me-1"></i>Maintenance</a>
        </div>

<?php if ($tab === 'dashboard'): ?>
        <div class="row g-3">
            <div class="col-lg-6">
                <div class="section-card">
                    <h2><i class="fas fa-desktop me-2 text-primary"></i>Computer Status Overview</h2>
                    <div style="max-height:350px;overflow-y:auto">
                    <?php if (empty($computers)): ?>
                        <div class="empty-state"><i class="fas fa-desktop"></i><p>No computers registered yet.</p></div>
                    <?php else: foreach ($computers as $c):
                        $statusClass = $c['status'] === 'online' ? 'online' : ($c['status'] === 'offline' ? 'offline' : 'maintenance');
                        $statusIcon  = $c['status'] === 'online' ? 'fa-check-circle text-success' : ($c['status'] === 'offline' ? 'fa-times-circle text-danger' : 'fa-wrench text-warning');
                    ?>
                    <div class="comp-card mb-2 d-flex justify-content-between align-items-center">
                        <div>
                            <strong><?= htmlspecialchars($c['computer_name']) ?></strong>
                            <small class="text-muted d-block"><?= htmlspecialchars($c['lab_name']) ?> | <?= htmlspecialchars($c['ip_address']??'N/A') ?></small>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <span class="comp-status <?= $statusClass ?>"><i class="fas <?= $statusIcon ?> me-1"></i><?= ucfirst($c['status']) ?></span>
                            <div class="dropdown">
                                <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="dropdown"><i class="fas fa-ellipsis-v"></i></button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li><a class="dropdown-item" href="javascript:void(0)" onclick="openEdit(<?= $c['id'] ?>)"><i class="fas fa-edit me-2"></i>Edit</a></li>
                                    <li><a class="dropdown-item" href="javascript:void(0)" onclick="openMaint(<?= $c['id'] ?>)"><i class="fas fa-tools me-2"></i>Log Maintenance</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item text-success" href="javascript:void(0)" onclick="updateStatus(<?= $c['id'] ?>,'online')"><i class="fas fa-check-circle me-2"></i>Set Online</a></li>
                                    <li><a class="dropdown-item text-danger" href="javascript:void(0)" onclick="updateStatus(<?= $c['id'] ?>,'offline')"><i class="fas fa-times-circle me-2"></i>Set Offline</a></li>
                                    <li><a class="dropdown-item text-warning" href="javascript:void(0)" onclick="updateStatus(<?= $c['id'] ?>,'maintenance')"><i class="fas fa-wrench me-2"></i>Set Maintenance</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item text-danger" href="javascript:void(0)" onclick="deleteComp(<?= $c['id'] ?>)"><i class="fas fa-trash me-2"></i>Remove</a></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; endif; ?>
                    </div>
                    <button class="btn btn-primary btn-sm mt-2" data-bs-toggle="modal" data-bs-target="#addComputerModal"><i class="fas fa-plus me-1"></i>Add Computer</button>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="section-card">
                    <h2><i class="fas fa-calendar-alt me-2 text-warning"></i>Recent Lab Bookings</h2>
                    <?php if (empty($bookings)): ?>
                        <div class="empty-state"><i class="fas fa-calendar"></i><p>No bookings yet.</p></div>
                    <?php else: foreach (array_slice($bookings, 0, 6) as $b):
                        $bStatus = $b['status'] === 'confirmed' ? 'bg-success' : ($b['status'] === 'pending' ? 'bg-warning text-dark' : ($b['status'] === 'completed' ? 'bg-info' : 'bg-secondary'));
                    ?>
                    <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                        <div><strong><?= htmlspecialchars($b['purpose'] ?? 'Lab Session') ?></strong><small class="d-block text-muted"><?= htmlspecialchars($b['booked_by_name'] ?? 'N/A') ?> | <?= htmlspecialchars($b['booking_date'] ?? '') ?></small></div>
                        <span class="badge <?= $bStatus ?>"><?= $b['status'] ?></span>
                    </div>
                    <?php endforeach; endif; ?>
                    <a href="?tab=bookings" class="btn btn-sm btn-outline-warning mt-2">View All Bookings</a>
                </div>
                <div class="section-card mt-3">
                    <h2><i class="fas fa-ticket-alt me-2 text-danger"></i>Open Support Tickets</h2>
                    <?php if (empty($tickets)): ?>
                        <div class="empty-state"><i class="fas fa-ticket-alt"></i><p>No open tickets.</p></div>
                    <?php else: foreach (array_slice($tickets, 0, 5) as $t):
                        $pClass = $t['priority'] === 'high' ? 'ticket-priority-high' : ($t['priority'] === 'medium' ? 'ticket-priority-medium' : 'ticket-priority-low');
                    ?>
                    <div class="ps-2 py-2 border-bottom <?= $pClass ?>">
                        <strong><?= htmlspecialchars($t['title'] ?? 'Ticket #'.$t['id']) ?></strong>
                        <small class="d-block text-muted"><?= htmlspecialchars($t['description'] ?? '') ?></small>
                        <span class="badge bg-<?= $t['status']==='open'?'danger':($t['status']==='in_progress'?'warning text-dark':'success') ?>"><?= $t['status'] ?></span>
                        <span class="badge bg-secondary ms-1"><?= $t['priority'] ?></span>
                    </div>
                    <?php endforeach; endif; ?>
                    <a href="?tab=tickets" class="btn btn-sm btn-outline-danger mt-2">View All Tickets</a>
                </div>
            </div>
        </div>

<?php elseif ($tab === 'computers'): ?>
        <div class="section-card">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="mb-0"><i class="fas fa-desktop me-2"></i>Computer Inventory</h2>
                <div><input type="text" id="compSearch" class="form-control form-control-sm" style="width:250px" placeholder="Search computers..."></div>
            </div>
            <div class="table-responsive-wrapper">
                <table class="table table-sm table-hover" id="compTable">
                    <thead><tr><th>Name</th><th>Lab</th><th>IP Address</th><th>OS</th><th>Specifications</th><th>Status</th><th>Actions</th></tr></thead>
                    <tbody>
                        <?php foreach ($computers as $c):
                            $sc = $c['status'] === 'online' ? 'success' : ($c['status'] === 'offline' ? 'danger' : 'warning');
                        ?>
                        <tr>
                            <td class="fw-semibold"><?= htmlspecialchars($c['computer_name']) ?></td>
                            <td><?= htmlspecialchars($c['lab_name']) ?></td>
                            <td><code><?= htmlspecialchars($c['ip_address'] ?? '-') ?></code></td>
                            <td><?= htmlspecialchars($c['operating_system'] ?? '-') ?></td>
                            <td><small><?= htmlspecialchars($c['specifications'] ?? '-') ?></small></td>
                            <td><span class="badge bg-<?= $sc ?>"><?= ucfirst($c['status']) ?></span></td>
                            <td>
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="dropdown"><i class="fas fa-ellipsis-v"></i></button>
                                    <ul class="dropdown-menu">
                                        <li><a class="dropdown-item" href="javascript:void(0)" onclick="openEdit(<?= $c['id'] ?>)"><i class="fas fa-edit me-2"></i>Edit</a></li>
                                        <li><a class="dropdown-item" href="javascript:void(0)" onclick="openMaint(<?= $c['id'] ?>)"><i class="fas fa-tools me-2"></i>Log Maintenance</a></li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li><a class="dropdown-item text-success" href="javascript:void(0)" onclick="updateStatus(<?= $c['id'] ?>,'online')"><i class="fas fa-check-circle me-2"></i>Online</a></li>
                                        <li><a class="dropdown-item text-danger" href="javascript:void(0)" onclick="updateStatus(<?= $c['id'] ?>,'offline')"><i class="fas fa-times-circle me-2"></i>Offline</a></li>
                                        <li><a class="dropdown-item text-warning" href="javascript:void(0)" onclick="updateStatus(<?= $c['id'] ?>,'maintenance')"><i class="fas fa-wrench me-2"></i>Maintenance</a></li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li><a class="dropdown-item text-danger" href="javascript:void(0)" onclick="deleteComp(<?= $c['id'] ?>)"><i class="fas fa-trash me-2"></i>Remove</a></li>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($computers)): ?>
                        <tr><td colspan="7"><div class="empty-state"><i class="fas fa-desktop"></i><p>No computers registered.</p></div></td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <button class="btn btn-primary btn-sm mt-2" data-bs-toggle="modal" data-bs-target="#addComputerModal"><i class="fas fa-plus me-1"></i>Add Computer</button>
        </div>

<?php elseif ($tab === 'bookings'): ?>
        <div class="section-card">
            <h2><i class="fas fa-calendar-alt me-2 text-warning"></i>Lab Bookings</h2>
            <?php if (empty($bookings)): ?>
                <div class="empty-state"><i class="fas fa-calendar"></i><p>No lab bookings found.</p></div>
            <?php else: ?>
            <div class="table-responsive-wrapper">
                <table class="table table-sm table-hover">
                    <thead><tr><th>Purpose</th><th>Booked By</th><th>Date</th><th>Time</th><th>Lab</th><th>Status</th><th>Actions</th></tr></thead>
                    <tbody>
                        <?php foreach ($bookings as $b):
                            $bStatus = $b['status'] === 'confirmed' ? 'bg-success' : ($b['status'] === 'pending' ? 'bg-warning text-dark' : ($b['status'] === 'completed' ? 'bg-info' : 'bg-secondary'));
                        ?>
                        <tr>
                            <td><?= htmlspecialchars($b['purpose'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($b['booked_by_name'] ?? 'N/A') ?></td>
                            <td><?= htmlspecialchars($b['booking_date'] ?? '-') ?></td>
                            <td><?= htmlspecialchars(($b['start_time'] ?? '').' - '.($b['end_time'] ?? '')) ?></td>
                            <td><?= htmlspecialchars($b['lab_name'] ?? 'Main Lab') ?></td>
                            <td><span class="badge <?= $bStatus ?>"><?= $b['status'] ?></span></td>
                            <td>
                                <?php if ($b['status'] === 'pending'): ?>
                                <form method="POST" class="d-inline"><input type="hidden" name="action" value="approve_booking"><input type="hidden" name="booking_id" value="<?= $b['id'] ?>"><button class="btn btn-sm btn-success"><i class="fas fa-check"></i></button></form>
                                <?php endif; ?>
                                <?php if ($b['status'] === 'confirmed'): ?>
                                <form method="POST" class="d-inline"><input type="hidden" name="action" value="complete_booking"><input type="hidden" name="booking_id" value="<?= $b['id'] ?>"><button class="btn btn-sm btn-info text-white"><i class="fas fa-check-double"></i></button></form>
                                <?php endif; ?>
                                <?php if ($b['status'] !== 'completed' && $b['status'] !== 'cancelled'): ?>
                                <form method="POST" class="d-inline"><input type="hidden" name="action" value="cancel_booking"><input type="hidden" name="booking_id" value="<?= $b['id'] ?>"><button class="btn btn-sm btn-outline-danger" onclick="return confirm('Cancel this booking?')"><i class="fas fa-times"></i></button></form>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>

<?php elseif ($tab === 'tickets'): ?>
        <div class="section-card">
            <h2><i class="fas fa-ticket-alt me-2 text-danger"></i>IT Support Tickets</h2>
            <?php if (empty($tickets)): ?>
                <div class="empty-state"><i class="fas fa-ticket-alt"></i><p>No support tickets found.</p></div>
            <?php else: ?>
            <div class="table-responsive-wrapper">
                <table class="table table-sm table-hover">
                    <thead><tr><th>ID</th><th>Title</th><th>Priority</th><th>Status</th><th>Assigned To</th><th>Created</th><th>Actions</th></tr></thead>
                    <tbody>
                        <?php foreach ($tickets as $t):
                            $pClass = $t['priority'] === 'high' ? 'bg-danger' : ($t['priority'] === 'medium' ? 'bg-warning text-dark' : 'bg-success');
                            $sClass = $t['status'] === 'open' ? 'bg-danger' : ($t['status'] === 'in_progress' ? 'bg-warning text-dark' : 'bg-success');
                        ?>
                        <tr>
                            <td><code>#<?= $t['id'] ?></code></td>
                            <td><?= htmlspecialchars($t['title'] ?? 'N/A') ?></td>
                            <td><span class="badge <?= $pClass ?>"><?= $t['priority'] ?? 'medium' ?></span></td>
                            <td><span class="badge <?= $sClass ?>"><?= str_replace('_', ' ', $t['status']) ?></span></td>
                            <td><?= htmlspecialchars($t['assigned_name'] ?? 'Unassigned') ?></td>
                            <td><small><?= htmlspecialchars($t['created_at'] ?? '') ?></small></td>
                            <td>
                                <button class="btn btn-sm btn-outline-primary" onclick="openTicketNote(<?= $t['id'] ?>)"><i class="fas fa-comment"></i></button>
                                <div class="dropdown d-inline">
                                    <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="dropdown"><i class="fas fa-cog"></i></button>
                                    <ul class="dropdown-menu">
                                        <li><a class="dropdown-item" href="javascript:void(0)" onclick="ticketStatus(<?= $t['id'] ?>,'in_progress')"><i class="fas fa-play me-2"></i>In Progress</a></li>
                                        <li><a class="dropdown-item" href="javascript:void(0)" onclick="ticketStatus(<?= $t['id'] ?>,'resolved')"><i class="fas fa-check me-2"></i>Resolved</a></li>
                                        <li><a class="dropdown-item" href="javascript:void(0)" onclick="ticketStatus(<?= $t['id'] ?>,'closed')"><i class="fas fa-times me-2"></i>Close</a></li>
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

<?php elseif ($tab === 'maintenance'): ?>
        <div class="section-card">
            <h2><i class="fas fa-tools me-2 text-orange"></i>Maintenance Logs</h2>
            <?php if (empty($maintenance_logs)): ?>
                <div class="empty-state"><i class="fas fa-tools"></i><p>No maintenance logs recorded.</p></div>
            <?php else: ?>
            <div class="table-responsive-wrapper">
                <table class="table table-sm table-hover">
                    <thead><tr><th>Date</th><th>Computer ID</th><th>Type</th><th>Description</th><th>Logged By</th></tr></thead>
                    <tbody>
                        <?php foreach ($maintenance_logs as $m): ?>
                        <tr>
                            <td><small><?= htmlspecialchars($m['created_at'] ?? '') ?></small></td>
                            <td>#<?= $m['computer_id'] ?></td>
                            <td><span class="badge bg-secondary"><?= htmlspecialchars($m['maintenance_type'] ?? 'General') ?></span></td>
                            <td><?= htmlspecialchars($m['description'] ?? '') ?></td>
                            <td><?= htmlspecialchars($m['logged_by_name'] ?? 'N/A') ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
<?php endif; ?>
    </div>
</div>

<!-- Add Computer Modal -->
<div class="modal fade" id="addComputerModal" tabindex="-1"><div class="modal-dialog">
<form method="POST" class="modal-content">
<input type="hidden" name="action" value="add_computer">
<div class="modal-header bg-primary text-white"><h5 class="modal-title"><i class="fas fa-plus-circle me-2"></i>Add Computer</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
<div class="modal-body">
    <div class="mb-2"><label class="form-label">Computer Name *</label><input type="text" name="computer_name" class="form-control" required></div>
    <div class="mb-2"><label class="form-label">Lab Name *</label><input type="text" name="lab_name" class="form-control" placeholder="e.g., Main Lab, Room 12" required></div>
    <div class="mb-2"><label class="form-label">IP Address</label><input type="text" name="ip_address" class="form-control" placeholder="192.168.1.x"></div>
    <div class="mb-2"><label class="form-label">Operating System</label><input type="text" name="operating_system" class="form-control" placeholder="Windows 11, Ubuntu 22.04"></div>
    <div class="mb-2"><label class="form-label">Specifications</label><textarea name="specifications" class="form-control" rows="2" placeholder="RAM, CPU, Storage, etc."></textarea></div>
</div>
<div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Add Computer</button></div>
</form>
</div></div>

<!-- Edit Computer Modal -->
<div class="modal fade" id="editComputerModal" tabindex="-1"><div class="modal-dialog">
<form method="POST" class="modal-content">
<input type="hidden" name="action" value="edit_computer">
<input type="hidden" name="computer_id" id="editCompId">
<div class="modal-header bg-info text-white"><h5 class="modal-title"><i class="fas fa-edit me-2"></i>Edit Computer</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
<div class="modal-body" id="editCompBody">Loading...</div>
<div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-info text-white"><i class="fas fa-save me-1"></i>Update</button></div>
</form>
</div></div>

<!-- Maintenance Modal -->
<div class="modal fade" id="maintModal" tabindex="-1"><div class="modal-dialog">
<form method="POST" class="modal-content">
<input type="hidden" name="action" value="add_maintenance">
<input type="hidden" name="computer_id" id="maintCompId">
<div class="modal-header bg-warning text-dark"><h5 class="modal-title"><i class="fas fa-tools me-2"></i>Log Maintenance</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body">
    <p id="maintCompName" class="fw-bold"></p>
    <div class="mb-2"><label class="form-label">Maintenance Type</label>
        <select name="maintenance_type" class="form-select">
            <option>Hardware Repair</option><option>Software Issue</option><option>Network Issue</option>
            <option>Cleaning</option><option>Upgrade</option><option>Other</option>
        </select>
    </div>
    <div class="mb-2"><label class="form-label">Description *</label><textarea name="description" class="form-control" rows="3" required></textarea></div>
</div>
<div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-warning text-dark"><i class="fas fa-save me-1"></i>Log Maintenance</button></div>
</form>
</div></div>

<!-- Ticket Note Modal -->
<div class="modal fade" id="ticketNoteModal" tabindex="-1"><div class="modal-dialog">
<form method="POST" class="modal-content">
<input type="hidden" name="action" value="add_ticket_note">
<input type="hidden" name="ticket_id" id="ticketNoteId">
<div class="modal-header bg-primary text-white"><h5 class="modal-title"><i class="fas fa-comment me-2"></i>Add Note</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
<div class="modal-body">
    <div class="mb-2"><label class="form-label">Note *</label><textarea name="note" class="form-control" rows="3" required></textarea></div>
</div>
<div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Add Note</button></div>
</form>
</div></div>

<!-- Hidden forms -->
<form method="POST" id="updateStatusForm"><input type="hidden" name="action" value="update_status"><input type="hidden" name="computer_id" id="usCompId"><input type="hidden" name="status" id="usCompStatus"></form>
<form method="POST" id="deleteComputerForm"><input type="hidden" name="action" value="delete_computer"><input type="hidden" name="computer_id" id="delCompId"></form>
<form method="POST" id="ticketStatusForm"><input type="hidden" name="action" value="update_ticket_status"><input type="hidden" name="ticket_id" id="tsTicketId"><input type="hidden" name="ticket_status" id="tsTicketStatus"></form>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
function updateStatus(id, status) { $('#usCompId').val(id); $('#usCompStatus').val(status); $('#updateStatusForm').submit(); }
function deleteComp(id) { if (confirm('Remove this computer?')) { $('#delCompId').val(id); $('#deleteComputerForm').submit(); } }
function openMaint(id) { $('#maintCompId').val(id); $('#maintCompName').text('Computer #' + id); $('#maintModal').modal('show'); }
function openTicketNote(id) { $('#ticketNoteId').val(id); $('#ticketNoteModal').modal('show'); }
function ticketStatus(id, st) { $('#tsTicketId').val(id); $('#tsTicketStatus').val(st); $('#ticketStatusForm').submit(); }

function openEdit(id) {
    $('#editCompId').val(id);
    $('#editCompBody').html('Loading...');
    $('#editComputerModal').modal('show');
    let comps = <?= json_encode($computers) ?>;
    let c = comps.find(x => x.id == id);
    if (c) {
        $('#editCompBody').html(`
            <div class="mb-2"><label class="form-label">Computer Name *</label><input type="text" name="computer_name" class="form-control" value="${c.computer_name}" required></div>
            <div class="mb-2"><label class="form-label">Lab Name *</label><input type="text" name="lab_name" class="form-control" value="${c.lab_name}" required></div>
            <div class="mb-2"><label class="form-label">IP Address</label><input type="text" name="ip_address" class="form-control" value="${c.ip_address||''}"></div>
            <div class="mb-2"><label class="form-label">Operating System</label><input type="text" name="operating_system" class="form-control" value="${c.operating_system||''}"></div>
            <div class="mb-2"><label class="form-label">Specifications</label><textarea name="specifications" class="form-control" rows="2">${c.specifications||''}</textarea></div>
        `);
    }
}

$('#compSearch').on('keyup', function() {
    let q = $(this).val().toLowerCase();
    $('#compTable tbody tr').each(function() { $(this).toggle($(this).text().toLowerCase().includes(q)); });
});
</script>
<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
</body>
</html>
