<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
$ctx = bootstrapStaffDashboard([]);
$staffDb = $ctx['staff'];
$studentsDb = $ctx['students'];
$websiteDb = $ctx['website'];
$user = $ctx['user'];
$userRole = $user['role'] ?? '';
$userName = $user['full_name'] ?? 'User';

$search = trim($_GET['search'] ?? '');
$staffMembers = [];
if ($staffDb) {
    $sql = "SELECT * FROM staff";
    if ($search !== '') {
        $s = $staffDb->real_escape_string($search);
        $sql .= " WHERE first_name LIKE '%$s%' OR last_name LIKE '%$s%' OR email LIKE '%$s%' OR phone LIKE '%$s%' OR department LIKE '%$s%' OR position LIKE '%$s%'";
    }
    $sql .= " ORDER BY first_name, last_name LIMIT 100";
    $r = $staffDb->query($sql);
    if ($r && !($r === false)) {
        while ($row = $r->fetch_assoc()) $staffMembers[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include_once __DIR__ . '/../includes/_favicon.php'; ?>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Staff Directory - ISNM</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
<style>
.card-section { background: #fff; border-radius: 12px; padding: 20px; margin-bottom: 20px; box-shadow: 0 1px 3px rgba(0,0,0,.08); }
.page-header { border-bottom: 2px solid #e9ecef; padding-bottom: 12px; margin-bottom: 20px; }
.status-badge { font-size: 12px; }
</style>
</head>
<body>
<?php include_once __DIR__ . '/../includes/sidebar.php'; ?>
<div class="main-content" style="margin-left:270px;padding:20px;background:#f0f2f5;min-height:100vh;">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center page-header">
            <h4 class="fw-bold mb-0"><i class="fas fa-address-book me-2"></i>Staff Directory</h4>
            <span class="text-muted small"><?= date('l, d M Y') ?></span>
        </div>

        <div class="card-section">
            <form method="GET" class="row g-2 mb-3">
                <div class="col-md-6 col-lg-8">
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                        <input type="text" name="search" class="form-control" placeholder="Search by name, email, phone, department, or position..." value="<?= htmlspecialchars($search) ?>">
                        <button class="btn btn-primary" type="submit"><i class="fas fa-search me-1"></i>Search</button>
                        <?php if ($search !== ''): ?>
                        <a href="staff-directory.php" class="btn btn-outline-secondary"><i class="fas fa-times"></i></a>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-md-3 col-lg-2">
                    <select class="form-select" onchange="this.form.submit()">
                        <option value="">All Departments</option>
                        <?php
                        if ($staffDb) {
                            $dr = $staffDb->query("SELECT DISTINCT department FROM staff WHERE department IS NOT NULL AND department != '' ORDER BY department");
                            if ($dr) while ($d = $dr->fetch_assoc()) {
                                $sel = ($_GET['department'] ?? '') === $d['department'] ? 'selected' : '';
                                echo "<option $sel>" . htmlspecialchars($d['department']) . "</option>";
                            }
                        }
                        ?>
                    </select>
                </div>
                <div class="col-md-3 col-lg-2">
                    <select class="form-select">
                        <option value="">All Status</option>
                        <option>Active</option>
                        <option>Inactive</option>
                        <option>Suspended</option>
                    </select>
                </div>
            </form>

            <?php if (!empty($staffMembers)): ?>
            <div class="table-responsive">
                <table class="table table-hover table-bordered align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>First Name</th>
                            <th>Last Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Department</th>
                            <th>Position</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($staffMembers as $i => $s): ?>
                        <tr>
                            <td><?= $i + 1 ?></td>
                            <td><strong><?= htmlspecialchars($s['first_name'] ?? '') ?></strong></td>
                            <td><?= htmlspecialchars($s['last_name'] ?? '') ?></td>
                            <td><a href="mailto:<?= htmlspecialchars($s['email'] ?? '') ?>"><?= htmlspecialchars($s['email'] ?? '—') ?></a></td>
                            <td><?= htmlspecialchars($s['phone'] ?? '—') ?></td>
                            <td><?= htmlspecialchars($s['department'] ?? '—') ?></td>
                            <td><?= htmlspecialchars($s['position'] ?? '—') ?></td>
                            <td>
                                <?php $st = $s['status'] ?? 'Active'; ?>
                                <span class="badge <?= $st === 'Active' ? 'bg-success' : ($st === 'Inactive' ? 'bg-secondary' : 'bg-warning text-dark') ?> status-badge">
                                    <?= htmlspecialchars($st) ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <p class="text-muted small mb-0">Showing <?= count($staffMembers) ?> staff member(s).</p>
            <?php else: ?>
            <div class="text-center py-5 text-muted">
                <i class="fas fa-users fa-3x mb-3"></i>
                <p class="mb-1">No staff members found.</p>
                <?php if ($search !== ''): ?><p class="small">Try a different search term or clear filters.</p><?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
</body>
</html>
