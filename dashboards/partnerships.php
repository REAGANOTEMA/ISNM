<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
$ctx = bootstrapStaffDashboard(['director', 'principal', 'ceo', 'head']);
$conn = $ctx['staff'];
$user = $ctx['user'];

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_partnership') {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
        die('Invalid CSRF token');
    }
    $org = trim($_POST['organization'] ?? '');
    $type = trim($_POST['type'] ?? 'academic');
    $contact = trim($_POST['contact_person'] ?? '');
    $email = trim($_POST['contact_email'] ?? '');
    $status = trim($_POST['status'] ?? 'active');
    $desc = trim($_POST['description'] ?? '');
    $stmt = $conn->prepare("INSERT INTO partnerships (organization_name, partnership_type, contact_person, contact_email, status, description) VALUES (?, ?, ?, ?, ?, ?)");
    if ($stmt) { $stmt->bind_param('ssssss', $org, $type, $contact, $email, $status, $desc); if (!$stmt->execute()) { error_log('add_partnership failed: ' . ($stmt->error ?? 'unknown')); }; $stmt->close(); }
    header('Location: partnerships.php'); exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_partnership') {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) die('Invalid CSRF token');
    $id = (int)($_POST['id'] ?? 0);
    $org = trim($_POST['organization'] ?? '');
    $type = trim($_POST['type'] ?? 'academic');
    $contact = trim($_POST['contact_person'] ?? '');
    $email = trim($_POST['contact_email'] ?? '');
    $status = trim($_POST['status'] ?? 'active');
    $desc = trim($_POST['description'] ?? '');
    if ($id) { $stmt = $conn->prepare("UPDATE partnerships SET organization_name=?, partnership_type=?, contact_person=?, contact_email=?, status=?, description=? WHERE id=?"); if ($stmt) { $stmt->bind_param('ssssssi', $org, $type, $contact, $email, $status, $desc, $id); $stmt->execute(); $stmt->close(); } }
    header('Location: partnerships.php'); exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete_partnership') {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) die('Invalid CSRF token');
    $id = (int)($_POST['id'] ?? 0);
    if ($id) { $stmt = $conn->prepare("DELETE FROM partnerships WHERE id=?"); if ($stmt) { $stmt->bind_param('i', $id); $stmt->execute(); $stmt->close(); } }
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
<?php include_once __DIR__ . '/../includes/dashboard_topbar.php'; ?>
<div class="page-content">
    <div class="content-header">
        <h1><i class="fas fa-handshake"></i> Partnerships & Linkages</h1><button onclick="window.print()" class="btn btn-sm btn-outline-secondary float-end"><i class="fas fa-print"></i> Print</button>
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
                    <thead><tr><th>Organization</th><th>Type</th><th>Contact</th><th>Email</th><th>Status</th><th>Actions</th></tr></thead>
                    <tbody>
                        <?php foreach ($partnerships as $p): ?>
                        <tr>
                            <td><?= htmlspecialchars($p['organization_name']) ?></td>
                            <td><span class="badge bg-info"><?= $p['partnership_type'] ?></span></td>
                            <td><?= htmlspecialchars($p['contact_person'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($p['contact_email'] ?? '-') ?></td>
                            <td><span class="badge bg-<?= ($p['status'] ?? '') === 'active' ? 'success' : 'secondary' ?>"><?= $p['status'] ?? 'active' ?></span></td>
                            <td class="text-nowrap">
                                <button class="btn btn-sm btn-outline-primary" onclick="editPartnership(<?= htmlspecialchars(json_encode($p), ENT_QUOTES) ?>)"><i class="fas fa-edit"></i></button>
                                <form method="POST" style="display:inline" onsubmit="return confirm('Delete this partnership?')"><input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>"><input type="hidden" name="action" value="delete_partnership"><input type="hidden" name="id" value="<?= (int)$p['id'] ?>"><button type="submit" class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button></form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($partnerships)): ?><tr><td colspan="6" class="text-center">No partnerships recorded</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
<script>document.addEventListener('DOMContentLoaded',function(){var t='<?=htmlspecialchars($_SESSION["csrf_token"] ?? "")?>';document.querySelectorAll('form[method="POST"],form[method="post"]').forEach(function(f){if(!f.querySelector('input[name="csrf_token"]')){var i=document.createElement('input');i.type='hidden';i.name='csrf_token';i.value=t;f.appendChild(i);}});});
function editPartnership(p){var m=document.getElementById('actionModal');if(!m)return;document.getElementById('modalTitle').textContent='Edit Partnership';document.getElementById('modalBody').innerHTML='<form method="POST"><input type="hidden" name="csrf_token" value="<?= $_SESSION["csrf_token"] ?>"><input type="hidden" name="action" value="update_partnership"><input type="hidden" name="id" value="'+p.id+'"><div class="mb-2"><label>Organization</label><input name="organization" class="form-control" value="'+(p.organization_name||'')+'" required></div><div class="row mb-2"><div class="col"><label>Type</label><select name="type" class="form-select"><option value="academic"'+(p.partnership_type==='academic'?' selected':'')+'>Academic</option><option value="research"'+(p.partnership_type==='research'?' selected':'')+'>Research</option><option value="community"'+(p.partnership_type==='community'?' selected':'')+'>Community</option><option value="corporate"'+(p.partnership_type==='corporate'?' selected':'')+'>Corporate</option></select></div><div class="col"><label>Status</label><select name="status" class="form-select"><option value="active"'+(p.status==='active'?' selected':'')+'>Active</option><option value="inactive"'+(p.status==='inactive'?' selected':'')+'>Inactive</option><option value="pending"'+(p.status==='pending'?' selected':'')+'>Pending</option></select></div></div><div class="row mb-2"><div class="col"><label>Contact</label><input name="contact_person" class="form-control" value="'+(p.contact_person||'')+'"></div><div class="col"><label>Email</label><input name="contact_email" class="form-control" value="'+(p.contact_email||'')+'"></div></div><div class="mb-2"><label>Description</label><textarea name="description" class="form-control" rows="2">'+(p.description||'')+'</textarea></div><button type="submit" class="btn btn-primary">Update</button></form>';new bootstrap.Modal(m).show();}
</script>
</body>
</html>
