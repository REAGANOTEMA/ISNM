<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
$ctx = bootstrapStaffDashboard(['director', 'secretary', 'ict', 'it']);
$websiteConn = $ctx['website'];
$user = $ctx['user'];

$pageTitle = 'Contact & Application Submissions';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include_once __DIR__ . '/../includes/dashboard_head.php'; ?>
</head>
<body>
<?php include_once __DIR__ . '/../includes/sidebar.php'; ?>
<div class="page-content">
    <div class="content-header">
        <h1><i class="fas fa-file-alt"></i> Contact & Application Submissions</h1>
    </div>
    <?php
    // load data
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
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead><tr><th>Name</th><th>Email</th><th>Date</th><th>Status</th></tr></thead>
                            <tbody>
                                <?php foreach ($contacts as $c): ?>
                                <tr>
                                    <td><?= htmlspecialchars($c['name'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($c['email'] ?? '-') ?></td>
                                    <td><?= $c['created_at'] ?? '-' ?></td>
                                    <td><span class="badge bg-<?= ($c['status'] ?? 'unread') === 'read' ? 'success' : 'warning' ?>"><?= $c['status'] ?? 'unread' ?></span></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if (empty($contacts)): ?><tr><td colspan="4" class="text-center">No submissions</td></tr><?php endif; ?>
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
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead><tr><th>Name</th><th>Program</th><th>Date</th><th>Status</th></tr></thead>
                            <tbody>
                                <?php foreach ($applications as $a): ?>
                                <tr>
                                    <td><?= htmlspecialchars($a['full_name'] ?? $a['name'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($a['program'] ?? $a['course'] ?? '-') ?></td>
                                    <td><?= $a['created_at'] ?? '-' ?></td>
                                    <td><span class="badge bg-<?= ($a['status'] ?? 'pending') === 'approved' ? 'success' : (($a['status'] ?? '') === 'rejected' ? 'danger' : 'warning') ?>"><?= $a['status'] ?? 'pending' ?></span></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if (empty($applications)): ?><tr><td colspan="4" class="text-center">No applications</td></tr><?php endif; ?>
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
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead><tr><th>Name</th><th>Role</th><th>Date</th><th>Status</th></tr></thead>
                            <tbody>
                                <?php foreach ($volunteers as $v): ?>
                                <tr>
                                    <td><?= htmlspecialchars($v['full_name'] ?? $v['name'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($v['role'] ?? $v['interest'] ?? '-') ?></td>
                                    <td><?= $v['created_at'] ?? '-' ?></td>
                                    <td><span class="badge bg-<?= ($v['status'] ?? 'pending') === 'approved' ? 'success' : 'warning' ?>"><?= $v['status'] ?? 'pending' ?></span></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if (empty($volunteers)): ?><tr><td colspan="4" class="text-center">No volunteer applications</td></tr><?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
</body>
</html>
