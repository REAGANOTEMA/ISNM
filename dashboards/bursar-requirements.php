<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
require_once __DIR__ . '/../includes/financial_functions.php';

$ctx = bootstrapStaffDashboard(['bursar', 'accountant', 'finance']);
$staff = $ctx['staff'];
$students = $ctx['students'];
$user = $ctx['user'];
$userId = (int)($user['id'] ?? 0);
$studentsDb = defined('STUDENTS_DB_NAME') ? STUDENTS_DB_NAME : 'igangaschoolofl_students_db';

if (!$staff) die('Database connection failed.');

// ── Auto-migrate tables ──
$staff->query("CREATE TABLE IF NOT EXISTS bursar_req_items (id INT AUTO_INCREMENT PRIMARY KEY,item_name VARCHAR(255) NOT NULL,is_active TINYINT(1) NOT NULL DEFAULT 1,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
$staff->query("CREATE TABLE IF NOT EXISTS student_req_clearance (id INT AUTO_INCREMENT PRIMARY KEY,student_id INT NOT NULL,item_id INT NOT NULL,is_cleared TINYINT(1) NOT NULL DEFAULT 0,cleared_by INT DEFAULT NULL,cleared_at DATETIME DEFAULT NULL,remarks TEXT DEFAULT NULL,UNIQUE KEY uk_stu_item (student_id,item_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// Seed 20 items if empty
$cnt = $staff->query("SELECT COUNT(*) c FROM bursar_req_items")->fetch_assoc()['c'];
if ((int)$cnt === 0) {
    $items = [
        'Surgical Gloves','Examination Gloves','Photocopying Ream','Ruled Paper Reams','Omo',
        'Toilet Papers','Compound brooms','Soft brooms','Rake','Cobweb brush',
        'Scrubbing Brush','Squeezer','Toilet Brush','JIK','Vim',
        'Mops','Sanitizer','Liquid Soap','Face Masks','Heavy duty Gloves'
    ];
    $stmt = $staff->prepare("INSERT INTO bursar_req_items (item_name) VALUES (?)");
    foreach ($items as $item) { $stmt->bind_param('s', $item); $stmt->execute(); }
    $stmt->close();
}

function currency($n) { return 'UGX ' . number_format((float)$n, 0); }

$q = trim($_GET['q'] ?? '');
$sFilter = (int)($_GET['cleared'] ?? -1);
$msg = $_SESSION['success'] ?? ''; unset($_SESSION['success']);
$errMsg = $_SESSION['error'] ?? ''; unset($_SESSION['error']);

// ── POST: Update clearance ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_clearance'])) {
    $studentId = (int)($_POST['student_id'] ?? 0);
    $items = $_POST['items'] ?? [];
    if ($studentId > 0) {
        $allItems = $staff->query("SELECT id FROM bursar_req_items WHERE is_active=1");
        while ($row = $allItems->fetch_assoc()) {
            $itemId = (int)$row['id'];
            $cleared = isset($items[$itemId]) ? 1 : 0;
            $s = $staff->prepare("INSERT INTO student_req_clearance (student_id,item_id,is_cleared,cleared_by,cleared_at) VALUES (?,?,?,?,NOW()) ON DUPLICATE KEY UPDATE is_cleared=?,cleared_by=?,cleared_at=NOW()");
            if ($s) { $s->bind_param('iiiiii', $studentId, $itemId, $cleared, $userId, $cleared, $userId); $s->execute(); $s->close(); }
        }
        $_SESSION['success'] = 'Clearance updated.';
    }
    header('Location: bursar-requirements.php?q=' . urlencode($q));
    exit;
}

// ── Fetch students with clearance data ──
$students_list = [];
if ($students) {
    $sql = "SELECT s.student_id id, s.first_name, s.surname, s.phone, s.program, s.level FROM {$studentsDb}.students s WHERE 1=1";
    $params = []; $types = '';
    if ($q) {
        $sql .= " AND (s.first_name LIKE ? OR s.surname LIKE ? OR s.student_id LIKE ? OR s.phone LIKE ?)";
        $like = "%$q%";
        $types = 'ssss'; $params = [$like, $like, $like, $like];
    }
    $sql .= " ORDER BY s.surname LIMIT 100";
    $stmt = $students->prepare($sql);
    if ($stmt) {
        if ($params) $stmt->bind_param($types, ...$params);
        $stmt->execute(); $r = $stmt->get_result();
        while ($row = $r->fetch_assoc()) $students_list[] = $row;
        $stmt->close();
    }
}

// Fetch all requirement items
$reqItems = [];
$r = $staff->query("SELECT * FROM bursar_req_items WHERE is_active=1 ORDER BY id");
if ($r) while ($row = $r->fetch_assoc()) $reqItems[] = $row;

// Fetch clearance for each student
$clearanceMap = [];
if (!empty($students_list)) {
    $ids = array_column($students_list, 'id');
    $idList = implode(',', $ids);
    $r = $staff->query("SELECT * FROM student_req_clearance WHERE student_id IN ($idList)");
    if ($r) while ($row = $r->fetch_assoc()) $clearanceMap[$row['student_id']][$row['item_id']] = $row['is_cleared'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include_once __DIR__ . '/../includes/dashboard_head.php'; ?>
<style>
:root{--bs-primary:#1a237e}
.bb{background:var(--bs-primary);color:#fff;border-radius:8px;font-size:13px;font-weight:500;padding:8px 20px}
.bb:hover{background:#0d1442;color:#fff}
.fc{font-size:13px;border-radius:8px;border:1px solid #d1d5db}
.cc{border:1px solid #e5e7eb;border-radius:12px;background:#fff;overflow:hidden}
.ch{padding:14px 20px;border-bottom:1px solid #e5e7eb;font-weight:600;font-size:14px;background:#f8fafc;color:var(--bs-primary)}
.tb th{font-size:12px;text-transform:uppercase;letter-spacing:.5px;color:#6b7280;border-bottom:2px solid #e5e7eb;padding:8px 10px;white-space:nowrap}
.tb td{font-size:13px;padding:8px 10px;vertical-align:middle}
.progress-bar-small{height:6px;border-radius:3px}
.form-check-input:checked{background-color:#16a34a;border-color:#16a34a}
</style>
</head>
<body>
<?php include_once __DIR__ . '/../includes/sidebar.php'; ?>
<?php include_once __DIR__ . '/../includes/dashboard_topbar.php'; ?>
<div class="ma" style="margin-left:270px;padding:24px">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h2 class="mb-1" style="color:var(--bs-primary)"><i class="fas fa-clipboard-check me-2"></i>Student Requirements Clearance</h2>
            <p class="text-muted mb-0 small">Track and manage student item clearance — search students and mark cleared items</p>
        </div>
        <a href="school-bursar.php" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left me-1"></i>Back to Dashboard</a>
    </div>

    <?php if ($msg): ?><div class="alert alert-success py-2 small"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
    <?php if ($errMsg): ?><div class="alert alert-danger py-2 small"><?= htmlspecialchars($errMsg) ?></div><?php endif; ?>

    <!-- Search & Filters -->
    <div class="cc mb-4">
        <div class="ch"><i class="fas fa-search me-2"></i>Search Student</div>
        <div class="p-3">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-6">
                    <input type="text" name="q" class="form-control fc" placeholder="Search by name, admission number, or phone..." value="<?= htmlspecialchars($q) ?>">
                </div>
                <div class="col-md-3">
                    <select name="cleared" class="form-control fc">
                        <option value="-1">All Status</option>
                        <option value="0" <?= $sFilter===0?'selected':'' ?>>Not Cleared</option>
                        <option value="1" <?= $sFilter===1?'selected':'' ?>>Fully Cleared</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn bb w-100"><i class="fas fa-search me-1"></i>Search</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Results -->
    <?php if (empty($students_list)): ?>
    <div class="cc"><div class="ch">No Results</div><div class="p-4 text-center text-muted"><?= $q ? 'No students match your search.' : 'Enter a search term above to find students.' ?></div></div>
    <?php else: ?>
    <?php foreach ($students_list as $stu):
        $sid = (int)$stu['id'];
        $clearedItems = 0; $totalItems = count($reqItems);
        foreach ($reqItems as $item) { if (!empty($clearanceMap[$sid][$item['id']])) $clearedItems++; }
        $pct = $totalItems > 0 ? round(($clearedItems/$totalItems)*100) : 0;
        if ($sFilter === 1 && $pct < 100) continue;
        if ($sFilter === 0 && $pct >= 100) continue;
    ?>
    <div class="cc mb-3">
        <div class="ch d-flex justify-content-between align-items-center">
            <span><i class="fas fa-user me-2"></i><strong><?= htmlspecialchars($stu['first_name'] . ' ' . $stu['surname']) ?></strong> <span class="text-muted ms-2 small">#<?= htmlspecialchars($stu['id']) ?></span> <span class="text-muted ms-2 small"><?= htmlspecialchars($stu['phone']??'') ?></span></span>
            <span class="small">
                <?= htmlspecialchars($stu['program']??'') ?> <?= htmlspecialchars($stu['level']??'') ?>
                <span class="badge bg-<?= $pct>=100?'success':'warning' ?> ms-2"><?= $clearedItems ?>/<?= $totalItems ?> (<?= $pct ?>%)</span>
            </span>
        </div>
        <div class="p-3">
            <div class="progress mb-3" style="height:6px">
                <div class="progress-bar progress-bar-small bg-<?= $pct>=100?'success':'primary' ?>" style="width:<?= $pct ?>%"></div>
            </div>
            <form method="POST" class="row g-2">
                <input type="hidden" name="student_id" value="<?= $sid ?>">
                <?php foreach ($reqItems as $item):
                    $checked = !empty($clearanceMap[$sid][$item['id']]);
                ?>
                <div class="col-md-3 col-6">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="items[<?= $item['id'] ?>]" id="item_<?= $sid ?>_<?= $item['id'] ?>" value="1" <?= $checked?'checked':'' ?>>
                        <label class="form-check-label small" for="item_<?= $sid ?>_<?= $item['id'] ?>"><?= htmlspecialchars($item['item_name']) ?></label>
                    </div>
                </div>
                <?php endforeach; ?>
                <div class="col-12 mt-2">
                    <button type="submit" name="update_clearance" class="btn btn-sm bb"><i class="fas fa-save me-1"></i>Save Clearance</button>
                </div>
            </form>
        </div>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
</div>
<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
</body>
</html>
