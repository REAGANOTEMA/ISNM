<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
$ctx = bootstrapStaffDashboard(['director', 'principal', 'ceo', 'head']);
$conn = $ctx['staff'];
$user = $ctx['user'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_partnership') {
    $org = trim($_POST['organization'] ?? '');
    $type = trim($_POST['type'] ?? 'academic');
    $contact = trim($_POST['contact_person'] ?? '');
    $email = trim($_POST['contact_email'] ?? '');
    $status = trim($_POST['status'] ?? 'active');
    $desc = trim($_POST['description'] ?? '');
    $stmt = $conn->prepare("INSERT INTO partnerships (organization_name, partnership_type, contact_person, contact_email, status, description) VALUES (?, ?, ?, ?, ?, ?)");
    if ($stmt) { $stmt->bind_param('ssssss', $org, $type, $contact, $email, $status, $desc); $stmt->execute(); $stmt->close(); }
    header('Location: partnerships.php'); exit;
}

$partnerships = [];
$r = $conn->query("SELECT * FROM partnerships ORDER BY created_at DESC");
if ($r) while ($row = $r->fetch_assoc()) $partnerships[] = $row;

$pageTitle = 'Partnerships & Linkages';
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
        <h1><i class="fas fa-handshake"></i> Partnerships & Linkages</h1>
    </div>
    <div class="row mb-4">
        <div class="col-md-3"><div class="card"><div class="card-body"><h6>Total Partnerships</h6><h3><?= count($partnerships) ?></h3></div></div></div>
        <div class="col-md-3"><div class="card"><div class="card-body"><h6>Active</h6><h3><?= count(array_filter($partnerships, fn($p) => ($p['status'] ?? '') === 'active')) ?></h3></div></div></div>
    </div>
    <div class="card mb-4">
        <div class="card-header"><h5>New Partnership</h5></div>
        <div class="card-body">
            <form method="post" class="row g-2">
                <div class="col-md-4"><input name="organization" class="form-control" placeholder="Organization Name" required></div>
                <div class="col-md-2">
                    <select name="type" class="form-select">
                        <option value="academic">Academic</option>
                        <option value="clinical">Clinical</option>
                        <option value="research">Research</option>
                        <option value="community">Community</option>
                        <option value="corporate">Corporate</option>
                    </select>
                </div>
                <div class="col-md-2"><input name="contact_person" class="form-control" placeholder="Contact Person"></div>
                <div class="col-md-2"><input name="contact_email" class="form-control" placeholder="Email"></div>
                <div class="col-md-2">
                    <select name="status" class="form-select">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                        <option value="pending">Pending</option>
                    </select>
                </div>
                <div class="col-10"><input name="description" class="form-control" placeholder="Brief description"></div>
                <div class="col-2"><button type="submit" name="action" value="add_partnership" class="btn btn-primary w-100"><i class="fas fa-plus"></i> Add</button></div>
            </form>
        </div>
    </div>
    <div class="card">
        <div class="card-header"><h5>Partnerships List</h5></div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead><tr><th>Organization</th><th>Type</th><th>Contact</th><th>Email</th><th>Status</th></tr></thead>
                    <tbody>
                        <?php foreach ($partnerships as $p): ?>
                        <tr>
                            <td><?= htmlspecialchars($p['organization_name']) ?></td>
                            <td><span class="badge bg-info"><?= $p['partnership_type'] ?></span></td>
                            <td><?= htmlspecialchars($p['contact_person'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($p['contact_email'] ?? '-') ?></td>
                            <td><span class="badge bg-<?= ($p['status'] ?? '') === 'active' ? 'success' : 'secondary' ?>"><?= $p['status'] ?? 'active' ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($partnerships)): ?><tr><td colspan="5" class="text-center">No partnerships recorded</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
</body>
</html>
