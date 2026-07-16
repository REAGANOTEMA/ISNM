<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
$ctx = bootstrapStaffDashboard(['director', 'secretary', 'ict', 'it']);
$websiteConn = $ctx['website'];
$user = $ctx['user'];
$user_id = (int)($user['id'] ?? 0);

$pageTitle = 'Contact & Application Submissions';

if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

$flash = ''; $flashType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $websiteConn) {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
        die('Invalid CSRF token');
    }
    $action = $_POST['action'] ?? '';
    $type = $_POST['sub_type'] ?? '';
    $id = (int)($_POST['sub_id'] ?? 0);

    if ($action === 'mark_read' && $type === 'contact' && $id) {
        $stmt = $websiteConn->prepare("UPDATE contact_submissions SET status='read' WHERE id=?");
        if ($stmt) { $stmt->bind_param('i', $id); $stmt->execute(); $stmt->close(); $flash = 'Marked as read.'; }
    }
    if ($action === 'delete' && $type === 'contact' && $id) {
        $stmt = $websiteConn->prepare("DELETE FROM contact_submissions WHERE id=?");
        if ($stmt) { $stmt->bind_param('i', $id); $stmt->execute(); $stmt->close(); $flash = 'Contact submission deleted.'; }
    }
    if ($action === 'approve' && $type === 'application' && $id) {
        $stmt = $websiteConn->prepare("UPDATE student_applications SET status='approved' WHERE id=?");
        if ($stmt) { $stmt->bind_param('i', $id); $stmt->execute(); $stmt->close(); $flash = 'Application approved.'; }
    }
    if ($action === 'reject' && $type === 'application' && $id) {
        $stmt = $websiteConn->prepare("UPDATE student_applications SET status='rejected' WHERE id=?");
        if ($stmt) { $stmt->bind_param('i', $id); $stmt->execute(); $stmt->close(); $flash = 'Application rejected.'; }
    }
    if ($action === 'delete' && $type === 'application' && $id) {
        $stmt = $websiteConn->prepare("DELETE FROM student_applications WHERE id=?");
        if ($stmt) { $stmt->bind_param('i', $id); $stmt->execute(); $stmt->close(); $flash = 'Application deleted.'; }
    }
    if ($action === 'approve' && $type === 'volunteer' && $id) {
        $stmt = $websiteConn->prepare("UPDATE volunteer_applications SET status='approved' WHERE id=?");
        if ($stmt) { $stmt->bind_param('i', $id); $stmt->execute(); $stmt->close(); $flash = 'Volunteer approved.'; }
    }
    if ($action === 'reject' && $type === 'volunteer' && $id) {
        $stmt = $websiteConn->prepare("UPDATE volunteer_applications SET status='rejected' WHERE id=?");
        if ($stmt) { $stmt->bind_param('i', $id); $stmt->execute(); $stmt->close(); $flash = 'Volunteer rejected.'; }
    }
    if ($action === 'delete' && $type === 'volunteer' && $id) {
        $stmt = $websiteConn->prepare("DELETE FROM volunteer_applications WHERE id=?");
        if ($stmt) { $stmt->bind_param('i', $id); $stmt->execute(); $stmt->close(); $flash = 'Volunteer application deleted.'; }
    }
    if ($flash) header('Location: contact-submissions.php?flash=' . urlencode($flash) . '&type=' . $flashType);
    exit;
}

if (!empty($_GET['flash'])) { $flash = $_GET['flash']; $flashType = $_GET['type'] ?? 'success'; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include_once __DIR__ . '/../includes/dashboard_head.php'; ?>
</head>
<body>
<?php include_once __DIR__ . '/../includes/sidebar.php'; ?>
<?php include_once __DIR__ . '/../includes/dashboard_topbar.php'; ?>
<div class="page-content">
    <div class="content-header">
        <h1><i class="fas fa-file-alt"></i> Contact & Application Submissions</h1>
    </div>
    <?php if ($flash): ?><div class="alert alert-<?= $flashType ?> alert-dismissible fade show"><?= htmlspecialchars($flash) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>
    <?php
    $contacts = []; $applications = []; $volunteers = [];
    if ($websiteConn) {
        $r = $websiteConn->query("SELECT * FROM contact_submissions ORDER BY created_at DESC LIMIT 100");
        if ($r) while ($row = $r->fetch_assoc()) $contacts[] = $row;
        $r = $websiteConn->query("SELECT * FROM student_applications ORDER BY created_at DESC LIMIT 100");
        if ($r) while ($row = $r->fetch_assoc()) $applications[] = $row;
        $r = $websiteConn->query("SELECT * FROM volunteer_applications ORDER BY created_at DESC LIMIT 100");
        if ($r) while ($row = $r->fetch_assoc()) $volunteers[] = $row;
    }
    ?>
    <div class="row mb-4">
        <div class="col-md-4"><div class="card"><div class="card-body"><h6>Contact Submissions</h6><h3><?= count($contacts) ?></h3></div></div></div>
        <div class="col-md-4"><div class="card"><div class="card-body"><h6>Student Applications</h6><h3><?= count($applications) ?></h3></div></div></div>
        <div class="col-md-4"><div class="card"><div class="card-body"><h6>Volunteers</h6><h3><?= count($volunteers) ?></h3></div></div></div>
    </div>
    <div class="row">
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header"><h5>Contact Submissions</h5></div>
                <div class="card-body">
                    <input type="text" class="form-control form-control-sm mb-2" placeholder="Filter contacts..." onkeyup="filterTable(this.id, 'contactsTbl')" id="contactFilter">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover table-sm" id="contactsTbl">
                            <thead><tr><th>Name</th><th>Email</th><th>Date</th><th>Status</th><th>Actions</th></tr></thead>
                            <tbody>
                                <?php foreach ($contacts as $c): ?>
                                <tr>
                                    <td><?= htmlspecialchars($c['name'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($c['email'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($c['created_at'] ?? '-') ?></td>
                                    <td><span class="badge bg-<?= ($c['status'] ?? 'unread') === 'read' ? 'success' : 'warning' ?>"><?= htmlspecialchars($c['status'] ?? 'unread') ?></span></td>
                                    <td class="text-nowrap">
                                        <?php if (($c['status'] ?? 'unread') !== 'read'): ?>
                                        <form method="POST" class="d-inline"><input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>"><input type="hidden" name="action" value="mark_read"><input type="hidden" name="sub_type" value="contact"><input type="hidden" name="sub_id" value="<?= (int)$c['id'] ?>"><button class="btn btn-sm btn-outline-success" title="Mark read"><i class="fas fa-check"></i></button></form>
                                        <?php endif; ?>
                                        <form method="POST" class="d-inline" onsubmit="return confirm('Delete this submission?')"><input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="sub_type" value="contact"><input type="hidden" name="sub_id" value="<?= (int)$c['id'] ?>"><button class="btn btn-sm btn-outline-danger" title="Delete"><i class="fas fa-trash"></i></button></form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if (empty($contacts)): ?><tr><td colspan="5" class="text-center">No submissions</td></tr><?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header"><h5>Student Applications</h5></div>
                <div class="card-body">
                    <input type="text" class="form-control form-control-sm mb-2" placeholder="Filter applications..." onkeyup="filterTable(this.id, 'appsTbl')" id="appFilter">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover table-sm" id="appsTbl">
                            <thead><tr><th>Name</th><th>Program</th><th>Date</th><th>Status</th><th>Actions</th></tr></thead>
                            <tbody>
                                <?php foreach ($applications as $a): ?>
                                <tr>
                                    <td><?= htmlspecialchars($a['full_name'] ?? $a['name'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($a['program'] ?? $a['course'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($a['created_at'] ?? '-') ?></td>
                                    <td><span class="badge bg-<?= ($a['status'] ?? 'pending') === 'approved' ? 'success' : (($a['status'] ?? '') === 'rejected' ? 'danger' : 'warning') ?>"><?= htmlspecialchars($a['status'] ?? 'pending') ?></span></td>
                                    <td class="text-nowrap">
                                        <?php if (($a['status'] ?? 'pending') !== 'approved'): ?>
                                        <form method="POST" class="d-inline"><input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>"><input type="hidden" name="action" value="approve"><input type="hidden" name="sub_type" value="application"><input type="hidden" name="sub_id" value="<?= (int)$a['id'] ?>"><button class="btn btn-sm btn-outline-success" title="Approve"><i class="fas fa-check"></i></button></form>
                                        <?php endif; ?>
                                        <?php if (($a['status'] ?? 'pending') !== 'rejected'): ?>
                                        <form method="POST" class="d-inline"><input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>"><input type="hidden" name="action" value="reject"><input type="hidden" name="sub_type" value="application"><input type="hidden" name="sub_id" value="<?= (int)$a['id'] ?>"><button class="btn btn-sm btn-outline-warning" title="Reject"><i class="fas fa-times"></i></button></form>
                                        <?php endif; ?>
                                        <form method="POST" class="d-inline" onsubmit="return confirm('Delete this application?')"><input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="sub_type" value="application"><input type="hidden" name="sub_id" value="<?= (int)$a['id'] ?>"><button class="btn btn-sm btn-outline-danger" title="Delete"><i class="fas fa-trash"></i></button></form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if (empty($applications)): ?><tr><td colspan="5" class="text-center">No applications</td></tr><?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header"><h5>Volunteer Applications</h5></div>
                <div class="card-body">
                    <input type="text" class="form-control form-control-sm mb-2" placeholder="Filter volunteers..." onkeyup="filterTable(this.id, 'volTbl')" id="volFilter">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover table-sm" id="volTbl">
                            <thead><tr><th>Name</th><th>Role</th><th>Date</th><th>Status</th><th>Actions</th></tr></thead>
                            <tbody>
                                <?php foreach ($volunteers as $v): ?>
                                <tr>
                                    <td><?= htmlspecialchars($v['full_name'] ?? $v['name'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($v['role'] ?? $v['interest'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($v['created_at'] ?? '-') ?></td>
                                    <td><span class="badge bg-<?= ($v['status'] ?? 'pending') === 'approved' ? 'success' : 'warning' ?>"><?= htmlspecialchars($v['status'] ?? 'pending') ?></span></td>
                                    <td class="text-nowrap">
                                        <?php if (($v['status'] ?? 'pending') !== 'approved'): ?>
                                        <form method="POST" class="d-inline"><input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>"><input type="hidden" name="action" value="approve"><input type="hidden" name="sub_type" value="volunteer"><input type="hidden" name="sub_id" value="<?= (int)$v['id'] ?>"><button class="btn btn-sm btn-outline-success" title="Approve"><i class="fas fa-check"></i></button></form>
                                        <?php endif; ?>
                                        <?php if (($v['status'] ?? 'pending') !== 'rejected'): ?>
                                        <form method="POST" class="d-inline"><input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>"><input type="hidden" name="action" value="reject"><input type="hidden" name="sub_type" value="volunteer"><input type="hidden" name="sub_id" value="<?= (int)$v['id'] ?>"><button class="btn btn-sm btn-outline-warning" title="Reject"><i class="fas fa-times"></i></button></form>
                                        <?php endif; ?>
                                        <form method="POST" class="d-inline" onsubmit="return confirm('Delete this volunteer application?')"><input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="sub_type" value="volunteer"><input type="hidden" name="sub_id" value="<?= (int)$v['id'] ?>"><button class="btn btn-sm btn-outline-danger" title="Delete"><i class="fas fa-trash"></i></button></form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if (empty($volunteers)): ?><tr><td colspan="5" class="text-center">No volunteer applications</td></tr><?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
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
</body>
</html>
