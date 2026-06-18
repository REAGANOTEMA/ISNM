<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
$ctx = bootstrapStaffDashboard(['director', 'academics', 'principal', 'head', 'lecturer']);
$conn = $ctx['staff'];
$user = $ctx['user'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    if ($action === 'add_project' && ($_POST['title'] ?? '')) {
        $title = $conn->real_escape_string($_POST['title']);
        $researcher = $conn->real_escape_string($_POST['researcher'] ?? '');
        $dept = $conn->real_escape_string($_POST['department'] ?? '');
        $status = $conn->real_escape_string($_POST['status'] ?? 'proposed');
        $desc = $conn->real_escape_string($_POST['description'] ?? '');
        $conn->query("INSERT INTO research_projects (title, researcher, department, status, description, created_at) VALUES ('$title', '$researcher', '$dept', '$status', '$desc', NOW())");
        header('Location: research-projects.php'); exit;
    }
}

$projects = [];
$r = $conn->query("SELECT * FROM research_projects ORDER BY created_at DESC");
if ($r) while ($row = $r->fetch_assoc()) $projects[] = $row;

$statuses = [];
foreach ($projects as $p) $statuses[$p['status']] = ($statuses[$p['status']] ?? 0) + 1;
$pageTitle = 'Research Projects';
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
        <h1><i class="fas fa-flask"></i> Research Projects</h1>
    </div>
    <div class="row mb-4">
        <div class="col-md-3"><div class="card"><div class="card-body"><h6>Total Projects</h6><h3><?= count($projects) ?></h3></div></div></div>
        <div class="col-md-3"><div class="card"><div class="card-body"><h6>Active</h6><h3><?= $statuses['active'] ?? 0 ?></h3></div></div></div>
        <div class="col-md-3"><div class="card"><div class="card-body"><h6>Completed</h6><h3><?= $statuses['completed'] ?? 0 ?></h3></div></div></div>
        <div class="col-md-3"><div class="card"><div class="card-body"><h6>Proposed</h6><h3><?= $statuses['proposed'] ?? 0 ?></h3></div></div></div>
    </div>
    <div class="card mb-4">
        <div class="card-header"><h5>New Research Project</h5></div>
        <div class="card-body">
            <form method="post" class="row g-2">
                <div class="col-md-4"><input name="title" class="form-control" placeholder="Project Title" required></div>
                <div class="col-md-2"><input name="researcher" class="form-control" placeholder="Researcher Name"></div>
                <div class="col-md-2"><input name="department" class="form-control" placeholder="Department"></div>
                <div class="col-md-2">
                    <select name="status" class="form-select">
                        <option value="proposed">Proposed</option>
                        <option value="active">Active</option>
                        <option value="completed">Completed</option>
                        <option value="suspended">Suspended</option>
                    </select>
                </div>
                <div class="col-md-2"><button type="submit" name="action" value="add_project" class="btn btn-primary w-100"><i class="fas fa-plus"></i> Add</button></div>
                <div class="col-12"><textarea name="description" class="form-control" rows="2" placeholder="Brief description"></textarea></div>
            </form>
        </div>
    </div>
    <div class="card">
        <div class="card-header"><h5>Projects List</h5></div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead><tr><th>Title</th><th>Researcher</th><th>Department</th><th>Status</th><th>Date</th></tr></thead>
                    <tbody>
                        <?php foreach ($projects as $p): ?>
                        <tr>
                            <td><?= htmlspecialchars($p['title']) ?></td>
                            <td><?= htmlspecialchars($p['researcher'] ?? '') ?></td>
                            <td><?= htmlspecialchars($p['department'] ?? '') ?></td>
                            <td><span class="badge bg-<?= $p['status'] === 'completed' ? 'success' : ($p['status'] === 'active' ? 'primary' : 'secondary') ?>"><?= $p['status'] ?></span></td>
                            <td><?= $p['created_at'] ?? '' ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($projects)): ?><tr><td colspan="5" class="text-center">No research projects found</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
</body>
</html>
