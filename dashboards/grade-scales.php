<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
$ctx = bootstrapStaffDashboard(['academics', 'registrar', 'director', 'principal', 'head', 'lecturer']);
$conn = $ctx['staff'];
$user = $ctx['user'];

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
        header('Location: grade-scales.php'); exit;
    }
    if ($action === 'delete_scale') {
        $id = (int)($_POST['id'] ?? 0);
        $conn->query("DELETE FROM grade_scales WHERE id=$id");
        header('Location: grade-scales.php'); exit;
    }
}

$scales = [];
$r = $conn->query("SELECT * FROM grade_scales ORDER BY min_score DESC");
if ($r) while ($row = $r->fetch_assoc()) $scales[] = $row;

$pageTitle = 'Grade Scales & Grading System';
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
        <h1><i class="fas fa-chart-simple"></i> Grade Scales & Grading System</h1>
    </div>
    <div class="row mb-4">
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
    <div class="card mb-4">
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
        <div class="card-header"><h5>Grade Scales List</h5></div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead><tr><th>Grade</th><th>Min Score</th><th>Max Score</th><th>Grade Point</th><th>Remark</th><th>Action</th></tr></thead>
                    <tbody>
                        <?php foreach ($scales as $s): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($s['grade']) ?></strong></td>
                            <td><?= $s['min_score'] ?></td>
                            <td><?= $s['max_score'] ?></td>
                            <td><?= $s['grade_point'] ?></td>
                            <td><?= htmlspecialchars($s['remark'] ?? '') ?></td>
                            <td>
                                <form method="post" onsubmit="return confirm('Delete this grade scale?')">
                                    <input type="hidden" name="id" value="<?= $s['id'] ?>">
                                    <button type="submit" name="action" value="delete_scale" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($scales)): ?><tr><td colspan="6" class="text-center">No grade scales defined</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
</body>
</html>
