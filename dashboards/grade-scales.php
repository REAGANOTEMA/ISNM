<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
$ctx = bootstrapStaffDashboard(['academics', 'registrar', 'director', 'principal', 'head', 'lecturer']);
$conn = $ctx['staff'];
$user = $ctx['user'];
$user_role = $_SESSION['role'] ?? '';

$userId = (int)($_SESSION['user_id'] ?? 0);

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$staff_db = defined('STAFF_DB_NAME') ? STAFF_DB_NAME : 'igangaschool_staffs';

if ($conn) {
    $conn->query("CREATE TABLE IF NOT EXISTS `{$staff_db}`.`grade_scales` (id INT AUTO_INCREMENT PRIMARY KEY, grade_letter VARCHAR(5) NOT NULL, grade_point DECIMAL(4,2) DEFAULT 0.00, min_percentage DECIMAL(5,2) DEFAULT 0.00, max_percentage DECIMAL(5,2) DEFAULT 100.00, remark VARCHAR(200) DEFAULT '', created_by INT DEFAULT 0, status VARCHAR(50) DEFAULT 'Active', UNIQUE KEY uq_grade (grade_letter)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

$scales = [];
if ($conn) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
            die('Invalid CSRF token');
        }
        $action = $_POST['action'] ?? '';
        if ($action === 'add_scale' && ($_POST['grade'] ?? '')) {
            $grade = trim($_POST['grade']);
            $minScore = (float)($_POST['min_score'] ?? 0);
            $maxScore = (float)($_POST['max_score'] ?? 100);
            $gp = (float)($_POST['grade_point'] ?? 0);
            $remark = trim($_POST['remark'] ?? '');
            $stmt = $conn->prepare("INSERT INTO grade_scales (grade_letter, min_percentage, max_percentage, grade_point, remark, created_by) VALUES (?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE min_percentage=VALUES(min_percentage), max_percentage=VALUES(max_percentage), grade_point=VALUES(grade_point), remark=VALUES(remark)");
            if ($stmt) { $stmt->bind_param('sdddsi', $grade, $minScore, $maxScore, $gp, $remark, $userId); if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); }; $stmt->close(); }
            $_SESSION['success'] = "Grade scale '$grade' added.";
            header('Location: grade-scales.php'); exit;
        }
        if ($action === 'delete_scale') {
            $id = (int)($_POST['id'] ?? 0);
            $stmt = $conn->prepare("DELETE FROM grade_scales WHERE id=?");
            if ($stmt) { $stmt->bind_param('i', $id); if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); }; $stmt->close(); }
            $_SESSION['success'] = 'Grade scale deleted.';
            header('Location: grade-scales.php'); exit;
        }
    }
    $search = trim($_GET['search'] ?? '');
    if ($search !== '') {
        $like = "%$search%";
        $stmt = $conn->prepare("SELECT * FROM grade_scales WHERE grade_letter LIKE ? OR remark LIKE ? ORDER BY min_percentage DESC");
        if ($stmt) { $stmt->bind_param('ss', $like, $like); $r = $stmt->execute() ? $stmt->get_result() : null; $stmt->close(); }
        else $r = null;
    } else {
        $r = $conn->query("SELECT * FROM grade_scales WHERE 1=1 ORDER BY min_percentage DESC");
    }
    if ($r) while ($row = $r->fetch_assoc()) $scales[] = $row;
}
$pageTitle = 'Grade Scales & Grading System';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include_once __DIR__ . '/../includes/dashboard_head.php'; ?>
<style>@media print { body * { visibility: hidden; } .page-content, .page-content * { visibility: visible; } .page-content { position: absolute; left: 0; top: 0; width: 100%; } .no-print, .sidebar, .btn, form[method=post] { display: none !important; } }</style>
</head>
<body>
<?php include_once __DIR__ . '/../includes/sidebar.php'; ?>
<?php include_once __DIR__ . '/../includes/dashboard_topbar.php'; ?>
<section class="content-section dashboard-section active" data-section="overview">
<div class="page-content">
    <div class="content-header no-print">
        <h1><i class="fas fa-chart-simple"></i> Grade Scales & Grading System</h1>
    </div>
    <?php if(!empty($_SESSION['success'])): ?><div class="alert alert-success py-2 no-print"><?= htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></div><?php endif; ?>
    <div class="row mb-4 no-print">
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h6>Total Grade Scales</h6>
                    <h3><?= count($scales) ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h6>Distinctions</h6>
                    <h3><?= count(array_filter($scales, fn($s) => $s['grade_point'] >= 4)) ?></h3>
                </div>
            </div>
        </div>
    </div>
    <form method="GET" class="row g-2 mb-3 no-print">
        <div class="col-md-4"><input type="text" name="search" class="form-control" placeholder="Search grade or remark..." value="<?= htmlspecialchars($search) ?>"></div>
        <div class="col-md-2"><button class="btn btn-primary w-100"><i class="fas fa-search"></i></button></div>
        <div class="col-md-2"><a href="grade-scales.php" class="btn btn-outline-secondary w-100"><i class="fas fa-times"></i> Clear</a></div>
        <div class="col-md-2"><button type="button" class="btn btn-outline-primary w-100" onclick="window.print()"><i class="fas fa-print"></i> Print</button></div>
    </form>
    <div class="card mb-4 no-print">
        <div class="card-header"><h5>Add Grade Scale</h5></div>
        <div class="card-body">
            <form method="post" class="row g-2">
                <div class="col-md-2"><input name="grade" class="form-control" placeholder="Grade (A, B+)" required></div>
                <div class="col-md-2"><input name="min_score" class="form-control" type="number" step="0.1" placeholder="Min %" required></div>
                <div class="col-md-2"><input name="max_score" class="form-control" type="number" step="0.1" placeholder="Max %" required></div>
                <div class="col-md-2"><input name="grade_point" class="form-control" type="number" step="0.1" placeholder="Grade Point" required></div>
                <div class="col-md-2"><input name="remark" class="form-control" placeholder="Remark (Excellent)"></div>
                <div class="col-md-2"><button type="submit" name="action" value="add_scale" class="btn btn-primary w-100"><i class="fas fa-plus"></i> Add</button></div>
            </form>
        </div>
    </div>
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center"><h5 class="mb-0">Grade Scales List</h5><small class="text-muted"><?= count($scales) ?> records</small></div>
        <div class="card-body p-0">
            <div class="mb-2"><input class="form-control form-control-sm" style="max-width:300px" id="srchEPMR" type="text" placeholder="Search..." onkeyup="filterTable('srchEPMR','tblEPMR')"></div>
<div class="table-responsive">
                <table id="tblEPMR" class="table table-bordered table-hover mb-0">
                    <thead><tr><th>Grade</th><th>Min %</th><th>Max %</th><th>Grade Point</th><th>Remark</th><th class="no-print">Action</th></tr></thead>
                    <tbody>
                        <?php foreach ($scales as $s): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($s['grade_letter']) ?></strong></td>
                            <td><?= $s['min_percentage'] ?></td>
                            <td><?= $s['max_percentage'] ?></td>
                            <td><?= $s['grade_point'] ?></td>
                            <td><?= htmlspecialchars($s['remark'] ?? '') ?></td>
                            <td class="no-print">
                                <form method="post" onsubmit="return confirm('Delete this grade scale?')">
                                    <input type="hidden" name="id" value="<?= $s['id'] ?>">
                                    <button type="submit" name="action" value="delete_scale" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($scales)): ?><tr><td colspan="6" class="text-center py-4">No grade scales defined. Use the form above to add one.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
</section>
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
