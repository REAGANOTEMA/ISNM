<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
require_once __DIR__ . '/../config/database.php';
$ctx = bootstrapStaffDashboard(['director','secretary','ict','it','principal']);
$staffConn = $ctx['staff'];
$studentsConn = $ctx['students'];
$websiteConn = $ctx['website'];
$user = $ctx['user'];
$userName = $user['full_name'] ?? 'User';

$conn = $studentsConn;

$students_db = defined('STUDENTS_DB_NAME') ? STUDENTS_DB_NAME : 'igangaschoolofl_students_db';

if ($conn) {
    $conn->query("CREATE TABLE IF NOT EXISTS `{$students_db}`.`announcements` (id INT AUTO_INCREMENT PRIMARY KEY, title VARCHAR(300) NOT NULL, body TEXT NOT NULL, target_audience VARCHAR(100) DEFAULT 'All', priority VARCHAR(50) DEFAULT 'Normal', posted_by INT DEFAULT 0, is_active TINYINT(1) DEFAULT 1, expires_at DATE DEFAULT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

$search = trim($_GET['search'] ?? '');
$filterAudience = $_GET['audience'] ?? '';
$filterPriority = $_GET['priority'] ?? '';

$total = 0; $activeCount = 0; $urgentCount = 0; $expiredCount = 0;
$announcements = [];

if ($conn) {
    $total = (int)(($r=$conn->query("SELECT COUNT(*) c FROM announcements"))&&$r?$r->fetch_assoc()['c']:0);
    $activeCount = (int)(($r=$conn->query("SELECT COUNT(*) c FROM announcements WHERE is_active=1 AND (expires_at IS NULL OR expires_at>=CURDATE())"))&&$r?$r->fetch_assoc()['c']:0);
    $urgentCount = (int)(($r=$conn->query("SELECT COUNT(*) c FROM announcements WHERE priority='Urgent' AND is_active=1"))&&$r?$r->fetch_assoc()['c']:0);
    $expiredCount = (int)(($r=$conn->query("SELECT COUNT(*) c FROM announcements WHERE expires_at IS NOT NULL AND expires_at<CURDATE()"))&&$r?$r->fetch_assoc()['c']:0);

    $where = ["1=1"];
    $types = '';
    $params = [];
    if ($search) {
        $like = '%' . $search . '%';
        $where[] = "(title LIKE ? OR body LIKE ?)";
        $types .= 'ss';
        $params[] = $like;
        $params[] = $like;
    }
    if ($filterAudience) {
        $where[] = "target_audience=?";
        $types .= 's';
        $params[] = $filterAudience;
    }
    if ($filterPriority) {
        $where[] = "priority=?";
        $types .= 's';
        $params[] = $filterPriority;
    }
    $ws = implode(' AND ', $where);
    $stmt = $conn->prepare("SELECT a.*, s.full_name AS poster_name FROM announcements a LEFT JOIN igangaschoolofl_staffs_db.staff s ON a.posted_by=s.id WHERE $ws ORDER BY a.created_at DESC LIMIT 100");
    if ($stmt) {
        if (!empty($params)) $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $r = $stmt->get_result();
        if ($r) while ($row = $r->fetch_assoc()) $announcements[] = $row;
        $stmt->close();
    }
}

if ($_SERVER['REQUEST_METHOD']==='POST') {
    $action = $_POST['action'] ?? '';
    if ($conn && $action==='add_announcement') {
        $title = $_POST['title'] ?? '';
        $body = $_POST['body'] ?? '';
        $audience = $_POST['target_audience'] ?? 'All';
        $priority = $_POST['priority'] ?? 'Normal';
        $expires = $_POST['expires_at'] ?? null;
        $uid = (int)($user['id']??0);
        if ($title && $body) {
            $stmt = $conn->prepare("INSERT INTO announcements (title,body,target_audience,priority,posted_by,expires_at) VALUES (?,?,?,?,?,?)");
            $stmt->bind_param("ssssi", $title, $body, $audience, $priority, $uid, $expires);
            $stmt->execute();
            $stmt->close();
            $_SESSION['success'] = 'Announcement published.';
        }
        header('Location: student-announcements.php'); exit;
    }
    if ($conn && $action==='toggle_active') {
        $id = (int)($_POST['id']??0);
        $current = (int)($_POST['current']??0);
        $newActive = 1 - $current;
        $stmt = $conn->prepare("UPDATE announcements SET is_active=? WHERE id=?");
        if ($stmt) { $stmt->bind_param('ii', $newActive, $id); $stmt->execute(); $stmt->close(); }
        header('Location: student-announcements.php'); exit;
    }
    if ($conn && $action==='delete_announcement') {
        $id = (int)($_POST['id']??0);
        $stmt = $conn->prepare("DELETE FROM announcements WHERE id=?");
        if ($stmt) { $stmt->bind_param('i', $id); $stmt->execute(); $stmt->close(); }
        $_SESSION['success'] = 'Announcement deleted.';
        header('Location: student-announcements.php'); exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include_once __DIR__ . '/../includes/dashboard_head.php'; ?>
<style>
.ann-card { border-left:4px solid #6c5ce7;transition:transform .15s,box-shadow .15s; }
.ann-card:hover { transform:translateY(-2px);box-shadow:0 4px 12px rgba(0,0,0,.08); }
.ann-urgent { border-left-color:#d63031;background:#fff5f5; }
.ann-high { border-left-color:#fdcb6e; }
.ann-normal { border-left-color:#00b894; }
</style>
</head>
<body>
<?php include_once __DIR__ . '/../includes/sidebar.php'; ?>
<?php include_once __DIR__ . '/../includes/dashboard_topbar.php'; ?>
<div class="main-content" style="margin-left:270px;padding:20px;background:#f0f2f5;min-height:100vh;">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center page-header">
            <h4 class="fw-bold mb-0"><i class="fas fa-bullhorn me-2"></i>Student Announcements</h4>
            <div>
                <span class="text-muted small me-3"><?= date('l, d M Y') ?></span>
                <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addAnnModal"><i class="fas fa-plus me-1"></i>New Announcement</button>
            </div>
        </div>

        <?php if(!empty($_SESSION['success'])): ?><div class="alert alert-success alert-dismissible fade show mt-3"><?= htmlspecialchars($_SESSION['success']) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php unset($_SESSION['success']); endif; ?>

        <div class="row g-3 mt-2">
            <div class="col-md-3"><div class="card border-0 shadow-sm"><div class="card-body text-center"><div class="fs-1 text-primary mb-2"><i class="fas fa-bullhorn"></i></div><h3 class="fw-bold mb-0"><?= $total ?></h3><small class="text-muted">Total Published</small></div></div></div>
            <div class="col-md-3"><div class="card border-0 shadow-sm"><div class="card-body text-center"><div class="fs-1 text-success mb-2"><i class="fas fa-check-circle"></i></div><h3 class="fw-bold mb-0"><?= $activeCount ?></h3><small class="text-muted">Active</small></div></div></div>
            <div class="col-md-3"><div class="card border-0 shadow-sm"><div class="card-body text-center"><div class="fs-1 text-danger mb-2"><i class="fas fa-exclamation-triangle"></i></div><h3 class="fw-bold mb-0"><?= $urgentCount ?></h3><small class="text-muted">Urgent</small></div></div></div>
            <div class="col-md-3"><div class="card border-0 shadow-sm"><div class="card-body text-center"><div class="fs-1 text-secondary mb-2"><i class="fas fa-clock"></i></div><h3 class="fw-bold mb-0"><?= $expiredCount ?></h3><small class="text-muted">Expired</small></div></div></div>
        </div>

        <div class="card border-0 shadow-sm mt-4">
            <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center py-3">
                <h5 class="fw-bold mb-0"><i class="fas fa-list me-2"></i>Announcements</h5>
                <form method="GET" class="d-flex flex-wrap gap-2">
                    <input type="text" name="search" class="form-control form-control-sm" style="width:200px" placeholder="Search announcements..." value="<?= htmlspecialchars($search) ?>">
                    <select name="audience" class="form-select form-select-sm" style="width:auto">
                        <option value="">All Audiences</option>
                        <?php foreach(['All','Nursing','Midwifery','Year1','Year2','Year3','Staff'] as $a): ?>
                        <option value="<?= $a ?>" <?= $filterAudience===$a?'selected':'' ?>><?= $a ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select name="priority" class="form-select form-select-sm" style="width:auto">
                        <option value="">All Priority</option>
                        <?php foreach(['Normal','High','Urgent'] as $p): ?>
                        <option value="<?= $p ?>" <?= $filterPriority===$p?'selected':'' ?>><?= $p ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button class="btn btn-sm btn-outline-primary"><i class="fas fa-search"></i></button>
                    <a href="student-announcements.php" class="btn btn-sm btn-outline-secondary"><i class="fas fa-times"></i></a>
                </form>
            </div>
            <div class="card-body">
                <?php if (empty($announcements)): ?>
                <div class="text-center py-5"><i class="fas fa-bullhorn fa-3x mb-3 text-muted" style="opacity:.3;"></i><p class="text-muted">No announcements yet. Create the first one.</p></div>
                <?php else: ?>
                <div class="row g-3">
                    <?php foreach ($announcements as $a):
                        $pr = strtolower($a['priority']??'normal');
                        $isExpired = $a['expires_at'] && strtotime($a['expires_at']) < time();
                    ?>
                    <div class="col-12">
                        <div class="card ann-card ann-<?= $pr ?> border">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="flex-grow-1">
                                        <div class="d-flex align-items-center gap-2 mb-1">
                                            <h6 class="fw-bold mb-0"><?= htmlspecialchars($a['title']) ?></h6>
                                            <span class="badge bg-<?= $pr==='urgent'?'danger':($pr==='high'?'warning text-dark':'info') ?>"><?= htmlspecialchars($a['priority']) ?></span>
                                            <?php if (!$a['is_active']): ?><span class="badge bg-secondary">Draft</span><?php endif; ?>
                                            <?php if ($isExpired): ?><span class="badge bg-dark">Expired</span><?php endif; ?>
                                        </div>
                                        <p class="mb-1 text-muted small"><?= nl2br(htmlspecialchars(mb_substr($a['body']??'',0,300))) ?></p>
                                        <div class="small text-muted">
                                            <i class="fas fa-users me-1"></i><?= htmlspecialchars($a['target_audience']??'All') ?>
                                            <span class="mx-2">|</span>
                                            <i class="fas fa-user me-1"></i><?= htmlspecialchars($a['poster_name']??'System') ?>
                                            <span class="mx-2">|</span>
                                            <i class="fas fa-calendar me-1"></i><?= date('d M Y H:i',strtotime($a['created_at'])) ?>
                                            <?php if ($a['expires_at']): ?>
                                            <span class="mx-2">|</span>
                                            <i class="fas fa-hourglass-end me-1"></i>Expires <?= date('d M Y',strtotime($a['expires_at'])) ?>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="d-flex gap-1 ms-3">
                                        <form method="POST" class="d-inline" onsubmit="return confirm('Toggle active status?')">
                                            <input type="hidden" name="action" value="toggle_active">
                                            <input type="hidden" name="id" value="<?= $a['id'] ?>">
                                            <input type="hidden" name="current" value="<?= $a['is_active'] ?>">
                                            <button class="btn btn-sm btn-outline-<?= $a['is_active']?'warning':'success' ?> py-0 px-1" title="<?= $a['is_active']?'Deactivate':'Activate' ?>"><i class="fas fa-<?= $a['is_active']?'eye-slash':'eye' ?>"></i></button>
                                        </form>
                                        <form method="POST" class="d-inline" onsubmit="return confirm('Delete this announcement?')">
                                            <input type="hidden" name="action" value="delete_announcement">
                                            <input type="hidden" name="id" value="<?= $a['id'] ?>">
                                            <button class="btn btn-sm btn-outline-danger py-0 px-1" title="Delete"><i class="fas fa-trash"></i></button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Add Announcement Modal -->
<div class="modal fade" id="addAnnModal" tabindex="-1"><div class="modal-dialog modal-lg"><form method="POST" class="modal-content"><input type="hidden" name="action" value="add_announcement">
<div class="modal-header bg-primary text-white"><h5 class="modal-title"><i class="fas fa-bullhorn me-2"></i>New Announcement</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
<div class="modal-body"><div class="row g-3">
    <div class="col-12"><label class="form-label fw-semibold">Title *</label><input type="text" name="title" class="form-control" required maxlength="255"></div>
    <div class="col-12"><label class="form-label fw-semibold">Message *</label><textarea name="body" class="form-control" rows="5" required></textarea></div>
    <div class="col-md-4"><label class="form-label fw-semibold">Target Audience</label><select name="target_audience" class="form-select"><?php foreach(['All','Nursing','Midwifery','Year1','Year2','Year3','Staff'] as $a): ?><option value="<?= $a ?>"><?= $a ?></option><?php endforeach; ?></select></div>
    <div class="col-md-4"><label class="form-label fw-semibold">Priority</label><select name="priority" class="form-select"><?php foreach(['Normal','High','Urgent'] as $p): ?><option value="<?= $p ?>"><?= $p ?></option><?php endforeach; ?></select></div>
    <div class="col-md-4"><label class="form-label">Expires At</label><input type="date" name="expires_at" class="form-control"></div>
</div></div>
<div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane me-1"></i>Publish</button></div>
</form></div></div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
</body>
</html>
