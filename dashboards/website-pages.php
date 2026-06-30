<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
$ctx = bootstrapStaffDashboard(['director', 'ict', 'it', 'secretary']);
$conn = $ctx['staff'];
$websiteConn = $ctx['website'];
$user = $ctx['user'];

$pageTitle = 'Website Pages & Content';

$pages = [];
if ($websiteConn) {
    $r = $websiteConn->query("SELECT * FROM pages ORDER BY created_at DESC LIMIT 100");
    if ($r) while ($row = $r->fetch_assoc()) $pages[] = $row;
}

$contactSubmissions = [];
if ($websiteConn) {
    $r = $websiteConn->query("SELECT * FROM contact_submissions ORDER BY created_at DESC LIMIT 100");
    if ($r) while ($row = $r->fetch_assoc()) $contactSubmissions[] = $row;
}
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
        <h1><i class="fas fa-globe"></i> Website Pages & Content</h1>
    </div>
    <div class="row mb-4">
        <div class="col-md-4"><div class="card"><div class="card-body"><h6>Website Pages</h6><h3><?= count($pages) ?></h3></div></div></div>
        <div class="col-md-4"><div class="card"><div class="card-body"><h6>Contact Submissions</h6><h3><?= count($contactSubmissions) ?></h3></div></div></div>
        <div class="col-md-4"><div class="card"><div class="card-body"><h6>Published</h6><h3><?= count(array_filter($pages, fn($p) => ($p['status'] ?? '') === 'published')) ?></h3></div></div></div>
    </div>
    <div class="row">
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header"><h5>Website Pages</h5></div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead><tr><th>Title</th><th>Slug</th><th>Status</th><th>Updated</th></tr></thead>
                            <tbody>
                                <?php foreach ($pages as $p): ?>
                                <tr>
                                    <td><?= htmlspecialchars($p['title'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($p['slug'] ?? '-') ?></td>
                                    <td><span class="badge bg-<?= ($p['status'] ?? 'draft') === 'published' ? 'success' : 'secondary' ?>"><?= $p['status'] ?? 'draft' ?></span></td>
                                    <td><?= $p['updated_at'] ?? $p['created_at'] ?? '-' ?></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if (empty($pages)): ?><tr><td colspan="4" class="text-center">No pages found</td></tr><?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header"><h5>Contact Submissions</h5></div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead><tr><th>Name</th><th>Email</th><th>Subject</th><th>Date</th><th>Status</th></tr></thead>
                            <tbody>
                                <?php foreach ($contactSubmissions as $c): ?>
                                <tr>
                                    <td><?= htmlspecialchars($c['name'] ?? $c['full_name'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($c['email'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($c['subject'] ?? substr($c['message'] ?? '', 0, 30)) ?></td>
                                    <td><?= $c['created_at'] ?? '-' ?></td>
                                    <td><span class="badge bg-<?= ($c['status'] ?? 'unread') === 'read' ? 'success' : 'warning' ?>"><?= $c['status'] ?? 'unread' ?></span></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if (empty($contactSubmissions)): ?><tr><td colspan="5" class="text-center">No contact submissions</td></tr><?php endif; ?>
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
