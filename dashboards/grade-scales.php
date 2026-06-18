<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
$ctx = bootstrapStaffDashboard(['academics', 'registrar', 'director', 'principal', 'head', 'lecturer']);
$conn = $ctx['staff'];
$user = $ctx['user'];
$user_role = $_SESSION['role'] ?? '';

$userId = (int)($_SESSION['user_id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'add_scale' && ($_POST['grade'] ?? '')) {
        $grade = $conn->real_escape_string($_POST['grade']);
        $minScore = (float)($_POST['min_score'] ?? 0);
        $maxScore = (float)($_POST['max_score'] ?? 100);
        $gp = (float)($_POST['grade_point'] ?? 0);
        $remark = $conn->real_escape_string($_POST['remark'] ?? '');
        $conn->query("INSERT INTO grade_scales (grade, min_score, max_score, grade_point, remark, created_by) VALUES ('$grade', $minScore, $maxScore, $gp, '$remark', $userId)");
        $_SESSION['success'] = "Grade scale '$grade' added.";
        header('Location: grade-scales.php'); exit;
    }
    if ($action === 'delete_scale') {
        $id = (int)($_POST['id'] ?? 0);
        $conn->query("DELETE FROM grade_scales WHERE id=$id");
        $_SESSION['success'] = 'Grade scale deleted.';
        header('Location: grade-scales.php'); exit;
    }
}

$search = trim($_GET['search'] ?? '');
$where = "1=1";
if ($search !== '') { $s = $conn->real_escape_string($search); $where .= " AND (grade LIKE '%$s%' OR remark LIKE '%$s%')"; }
$scales = [];
$r = $conn->query("SELECT * FROM grade_scales WHERE $where ORDER BY min_score DESC");
if ($r) while ($row = $r->fetch_assoc()) $scales[] = $row;

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
<div class="page-content">
    <div class="content-header no-print">
        <h1><i class="fas fa-chart-simple"></i> Grade Scales & Grading System</h1>
    </div>
    <?php renderModuleSlider($user_role); ?>
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
                <div class="col-md-2"><input name="min_score" class="form-control" type="number" step="0.1" placeholder="Min Score" required></div>
                <div class="col-md-2"><input name="max_score" class="form-control" type="number" step="0.1" placeholder="Max Score" required></div>
                <div class="col-md-2"><input name="grade_point" class="form-control" type="number" step="0.1" placeholder="Grade Point" required></div>
                <div class="col-md-2"><input name="remark" class="form-control" placeholder="Remark (Excellent)"></div>
                <div class="col-md-2"><button type="submit" name="action" value="add_scale" class="btn btn-primary w-100"><i class="fas fa-plus"></i> Add</button></div>
            </form>
        </div>
    </div>
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center"><h5 class="mb-0">Grade Scales List</h5><small class="text-muted"><?= count($scales) ?> records</small></div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered table-hover mb-0">
                    <thead><tr><th>Grade</th><th>Min Score</th><th>Max Score</th><th>Grade Point</th><th>Remark</th><th class="no-print">Action</th></tr></thead>
                    <tbody>
                        <?php foreach ($scales as $s): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($s['grade']) ?></strong></td>
                            <td><?= $s['min_score'] ?></td>
                            <td><?= $s['max_score'] ?></td>
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
<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
</body>
</html>
