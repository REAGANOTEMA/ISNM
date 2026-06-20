<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
$ctx = bootstrapStaffDashboard(['director', 'secretary', 'ict', 'hr', 'admin']);
$websiteConn = $ctx['website'];
$user = $ctx['user'];
$userId = (int)($_SESSION['user_id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id = (int)($_POST['id'] ?? 0);
    if ($id && in_array($action, ['reviewed', 'contacted', 'accepted', 'declined'])) {
        $status = $websiteConn->real_escape_string($action);
        $websiteConn->query("UPDATE volunteer_applications SET status='$status', reviewed_at=NOW(), reviewed_by=$userId WHERE id=$id");
        $_SESSION['success'] = "Volunteer application status updated to '" . ucfirst($action) . "'.";
        header('Location: volunteer-applications.php'); exit;
    }
    if ($action === 'delete') {
        $websiteConn->query("DELETE FROM volunteer_applications WHERE id=$id");
        $_SESSION['success'] = 'Volunteer application deleted.';
        header('Location: volunteer-applications.php'); exit;
    }
}

$records = [];
if ($websiteConn) {
    $r = $websiteConn->query("SELECT * FROM volunteer_applications ORDER BY created_at DESC LIMIT 200");
    if ($r) while ($row = $r->fetch_assoc()) $records[] = $row;
}

$total = count($records);
$pending = count(array_filter($records, fn($v) => $v['status'] === 'pending'));
$reviewed = count(array_filter($records, fn($v) => $v['status'] === 'reviewed'));
$contacted = count(array_filter($records, fn($v) => $v['status'] === 'contacted'));
$accepted = count(array_filter($records, fn($v) => $v['status'] === 'accepted'));
$declined = count(array_filter($records, fn($v) => $v['status'] === 'declined'));

$statusBadge = [
    'pending' => 'warning',
    'reviewed' => 'info',
    'contacted' => 'primary',
    'accepted' => 'success',
    'declined' => 'danger',
];

$pageTitle = 'Volunteer Applications';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include_once __DIR__ . '/../includes/dashboard_head.php'; ?>
<style>
.detail-label { font-weight: 600; color: #1a237e; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 2px; }
.detail-value { margin-bottom: 1rem; }
</style>
</head>
<body>
<?php include_once __DIR__ . '/../includes/sidebar.php'; ?>
<div class="page-content">
    <div class="content-header">
        <h1><i class="fas fa-hands-helping"></i> Volunteer Applications</h1>
    </div>
    <?php if (!empty($_SESSION['success'])): ?>
    <div class="alert alert-success py-2"><?= htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></div>
    <?php endif; ?>
    <?php if (!$websiteConn): ?>
    <div class="alert alert-warning py-2">Website database connection unavailable.</div>
    <?php endif; ?>
    <div class="row mb-4">
        <div class="col-md-2"><div class="card"><div class="card-body"><h6>Total</h6><h3><?= $total ?></h3></div></div></div>
        <div class="col-md-2"><div class="card"><div class="card-body"><h6>Pending</h6><h3 class="text-warning"><?= $pending ?></h3></div></div></div>
        <div class="col-md-2"><div class="card"><div class="card-body"><h6>Reviewed</h6><h3 class="text-info"><?= $reviewed ?></h3></div></div></div>
        <div class="col-md-2"><div class="card"><div class="card-body"><h6>Contacted</h6><h3 class="text-primary"><?= $contacted ?></h3></div></div></div>
        <div class="col-md-2"><div class="card"><div class="card-body"><h6>Accepted</h6><h3 class="text-success"><?= $accepted ?></h3></div></div></div>
        <div class="col-md-2"><div class="card"><div class="card-body"><h6>Declined</h6><h3 class="text-danger"><?= $declined ?></h3></div></div></div>
    </div>
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="fas fa-list me-2"></i>Volunteer Applications</h5>
            <span class="badge bg-info"><?= $total ?> records</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered table-hover mb-0">
                    <thead><tr><th>Name</th><th>Contact</th><th>Profession</th><th>Opportunity</th><th>Exp</th><th>Status</th><th>Date</th><th>Actions</th></tr></thead>
                    <tbody>
                        <?php foreach ($records as $v): ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars($v['first_name'] . ' ' . $v['last_name']) ?></strong>
                            </td>
                            <td>
                                <small><?= htmlspecialchars($v['email']) ?></small><br>
                                <small class="text-muted"><?= htmlspecialchars($v['phone'] ?? '') ?></small>
                            </td>
                            <td><?= htmlspecialchars($v['profession'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($v['opportunity'] ?? '-') ?></td>
                            <td><?= (int)($v['experience'] ?? 0) ?> yrs</td>
                            <td>
                                <span class="badge bg-<?= $statusBadge[$v['status']] ?? 'secondary' ?>">
                                    <?= ucfirst($v['status']) ?>
                                </span>
                            </td>
                            <td><small><?= $v['created_at'] ?? '-' ?></small></td>
                            <td>
                                <button class="btn btn-sm btn-outline-info mb-1" title="View Details" onclick='viewDetail(<?= json_encode($v) ?>)'>
                                    <i class="fas fa-eye"></i>
                                </button>
                                <?php if ($v['status'] === 'pending'): ?>
                                <form method="post" class="d-inline">
                                    <input type="hidden" name="id" value="<?= $v['id'] ?>">
                                    <button type="submit" name="action" value="reviewed" class="btn btn-sm btn-outline-info mb-1" title="Mark Reviewed"><i class="fas fa-check"></i></button>
                                </form>
                                <?php endif; ?>
                                <?php if (in_array($v['status'], ['pending', 'reviewed'])): ?>
                                <form method="post" class="d-inline">
                                    <input type="hidden" name="id" value="<?= $v['id'] ?>">
                                    <button type="submit" name="action" value="contacted" class="btn btn-sm btn-outline-primary mb-1" title="Mark Contacted"><i class="fas fa-phone"></i></button>
                                </form>
                                <?php endif; ?>
                                <?php if (in_array($v['status'], ['contacted', 'reviewed'])): ?>
                                <form method="post" class="d-inline">
                                    <input type="hidden" name="id" value="<?= $v['id'] ?>">
                                    <button type="submit" name="action" value="accepted" class="btn btn-sm btn-outline-success mb-1" title="Accept"><i class="fas fa-thumbs-up"></i></button>
                                </form>
                                <form method="post" class="d-inline">
                                    <input type="hidden" name="id" value="<?= $v['id'] ?>">
                                    <button type="submit" name="action" value="declined" class="btn btn-sm btn-outline-danger mb-1" title="Decline" onclick="return confirm('Decline this application?')"><i class="fas fa-thumbs-down"></i></button>
                                </form>
                                <?php endif; ?>
                                <form method="post" class="d-inline" onsubmit="return confirm('Delete this application?')">
                                    <input type="hidden" name="id" value="<?= $v['id'] ?>">
                                    <button type="submit" name="action" value="delete" class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($records)): ?><tr><td colspan="8" class="text-center py-4">No volunteer applications found.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="detailModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-user me-2"></i>Volunteer Details</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="detail-label">Full Name</div>
                        <div class="detail-value" id="d_name">-</div>
                        <div class="detail-label">Email</div>
                        <div class="detail-value" id="d_email">-</div>
                        <div class="detail-label">Phone</div>
                        <div class="detail-value" id="d_phone">-</div>
                        <div class="detail-label">Profession</div>
                        <div class="detail-value" id="d_profession">-</div>
                        <div class="detail-label">Experience</div>
                        <div class="detail-value" id="d_experience">-</div>
                    </div>
                    <div class="col-md-6">
                        <div class="detail-label">Opportunity</div>
                        <div class="detail-value" id="d_opportunity">-</div>
                        <div class="detail-label">Availability</div>
                        <div class="detail-value" id="d_availability">-</div>
                        <div class="detail-label">Duration</div>
                        <div class="detail-value" id="d_duration">-</div>
                        <div class="detail-label">Status</div>
                        <div class="detail-value" id="d_status">-</div>
                        <div class="detail-label">Submitted</div>
                        <div class="detail-value" id="d_created">-</div>
                    </div>
                </div>
                <div class="detail-label mt-2">Skills</div>
                <div class="detail-value" id="d_skills">-</div>
                <div class="detail-label">Motivation</div>
                <div class="detail-value" id="d_motivation">-</div>
                <div class="detail-label">Comments</div>
                <div class="detail-value" id="d_comments">-</div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
function viewDetail(v) {
    document.getElementById('d_name').textContent = v.first_name + ' ' + v.last_name;
    document.getElementById('d_email').textContent = v.email;
    document.getElementById('d_phone').textContent = v.phone || '-';
    document.getElementById('d_profession').textContent = v.profession || '-';
    document.getElementById('d_experience').textContent = (v.experience || 0) + ' years';
    document.getElementById('d_opportunity').textContent = v.opportunity || '-';
    document.getElementById('d_availability').textContent = v.availability || '-';
    document.getElementById('d_duration').textContent = v.duration || '-';
    var badgeColors = { pending: 'warning', reviewed: 'info', contacted: 'primary', accepted: 'success', declined: 'danger' };
    var color = badgeColors[v.status] || 'secondary';
    document.getElementById('d_status').innerHTML = '<span class="badge bg-' + color + '">' + v.status.charAt(0).toUpperCase() + v.status.slice(1) + '</span>';
    document.getElementById('d_created').textContent = v.created_at || '-';
    document.getElementById('d_skills').textContent = v.skills || '-';
    document.getElementById('d_motivation').textContent = v.motivation || '-';
    document.getElementById('d_comments').textContent = v.comments || '-';
    var modal = new bootstrap.Modal(document.getElementById('detailModal'));
    modal.show();
}
</script>

<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
</body>
</html>
