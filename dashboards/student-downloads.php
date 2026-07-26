<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
require_once __DIR__ . '/../includes/csrf_helper.php';
$ctx = bootstrapStaffDashboard(['director', 'secretary', 'registrar', 'ict']);
$conn = $ctx['staff'];
$studentsConn = $ctx['students'];
$user = $ctx['user'];

$pageTitle = 'Student Downloads';

// ─── Ensure tables exist ───
if ($conn) {
    @$conn->query("CREATE TABLE IF NOT EXISTS student_downloads (id INT AUTO_INCREMENT PRIMARY KEY, student_id INT NOT NULL, file_name VARCHAR(200) NOT NULL, file_type VARCHAR(50) DEFAULT 'Other', file_size VARCHAR(50) DEFAULT '', description TEXT, uploaded_by INT DEFAULT 0, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, KEY idx_student (student_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}
if ($studentsConn) {
    @$studentsConn->query("CREATE TABLE IF NOT EXISTS student_downloads (id INT AUTO_INCREMENT PRIMARY KEY, student_id INT NOT NULL, file_name VARCHAR(200) NOT NULL, file_type VARCHAR(50) DEFAULT 'Other', file_size VARCHAR(50) DEFAULT '', description TEXT, uploaded_by INT DEFAULT 0, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, KEY idx_student (student_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

$flashSuccess = $_SESSION['success'] ?? '';
$flashError = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $conn) {
    if (!verifyCsrfToken()) {
        $_SESSION['error'] = 'Invalid CSRF token.';
        header('Location: student-downloads.php');
        exit();
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'add_download') {
        $student_id  = intval($_POST['student_id'] ?? 0);
        $file_name   = trim($_POST['file_name'] ?? '');
        $file_type   = trim($_POST['file_type'] ?? 'Other');
        $file_size   = trim($_POST['file_size'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $uploaded_by = $user['id'] ?? 0;

        if ($student_id > 0 && $file_name !== '') {
            $stmt = $conn->prepare("INSERT INTO student_downloads (student_id, file_name, file_type, file_size, description, uploaded_by, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
            if ($stmt) {
                $stmt->bind_param('issssi', $student_id, $file_name, $file_type, $file_size, $description, $uploaded_by);
                if ($stmt->execute()) {
                    $_SESSION['success'] = 'Download record added successfully.';
                } else {
                    $_SESSION['error'] = 'Failed to add download record.';
                }
                $stmt->close();
            } else {
                $_SESSION['error'] = 'Database error: ' . $conn->error;
            }
        } else {
            $_SESSION['error'] = 'Student ID and File Name are required.';
        }
        header('Location: student-downloads.php');
        exit();
    }

    if ($action === 'delete_download') {
        $id = intval($_POST['id'] ?? 0);
        if ($id > 0) {
            $stmt = $conn->prepare("DELETE FROM student_downloads WHERE id = ?");
            if ($stmt) {
                $stmt->bind_param('i', $id);
                if ($stmt->execute()) {
                    $_SESSION['success'] = 'Download record deleted.';
                } else {
                    $_SESSION['error'] = 'Failed to delete record.';
                }
                $stmt->close();
            } else {
                $_SESSION['error'] = 'Database error: ' . $conn->error;
            }
        } else {
            $_SESSION['error'] = 'Invalid record ID.';
        }
        header('Location: student-downloads.php');
        exit();
    }
}

$downloads = [];
foreach ([$studentsConn, $conn] as $db) {
    if (!$db) continue;
    $r = $db->query("SELECT * FROM student_downloads ORDER BY created_at DESC LIMIT 100");
    if ($r && $r->num_rows) { while ($row = $r->fetch_assoc()) $downloads[] = $row; break; }
}

$studentsList = [];
$searchQ = trim($_GET['q'] ?? '');
if ($searchQ !== '' && $studentsConn) {
    $like = "%{$searchQ}%";
    $stmt = $studentsConn->prepare("SELECT id, student_number, first_name, last_name FROM students WHERE (student_number LIKE ? OR first_name LIKE ? OR last_name LIKE ?) LIMIT 10");
    if ($stmt) {
        $stmt->bind_param('sss', $like, $like, $like);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) $studentsList[] = $row;
        $stmt->close();
    }
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
        <h1><i class="fas fa-download"></i> Student Downloads</h1>
    </div>

    <?php if ($flashSuccess): ?><div class="alert alert-success alert-dismissible fade show" role="alert"><?= htmlspecialchars($flashSuccess) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>
    <?php if ($flashError): ?><div class="alert alert-danger alert-dismissible fade show" role="alert"><?= htmlspecialchars($flashError) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>

    <div class="row mb-4">
        <div class="col-md-3"><div class="card"><div class="card-body"><h6>Total Downloads</h6><h3><?= count($downloads) ?></h3></div></div></div>
    </div>

    <div class="card mb-4">
        <div class="card-header"><h5>Add Download Record</h5></div>
        <div class="card-body">
            <form method="POST" class="row g-3 align-items-end">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="add_download">

                <div class="col-md-2">
                    <label class="form-label">Search Student</label>
                    <input type="text" name="q" class="form-control" placeholder="ID or name..." value="<?= htmlspecialchars($searchQ) ?>" oninput="this.form.submit()">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Student ID</label>
                    <?php if ($searchQ !== '' && !empty($studentsList)): ?>
                        <select name="student_id" class="form-select" required>
                            <option value="">-- Select --</option>
                            <?php foreach ($studentsList as $s): ?>
                                <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['student_number'] . ' - ' . $s['first_name'] . ' ' . $s['last_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    <?php else: ?>
                        <input type="number" name="student_id" class="form-control" required placeholder="Enter student ID">
                    <?php endif; ?>
                </div>
                <div class="col-md-2">
                    <label class="form-label">File Name</label>
                    <input type="text" name="file_name" class="form-control" required>
                </div>
                <div class="col-md-1">
                    <label class="form-label">Type</label>
                    <select name="file_type" class="form-select">
                        <option value="PDF">PDF</option>
                        <option value="DOC">DOC</option>
                        <option value="XLS">XLS</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                <div class="col-md-1">
                    <label class="form-label">Size</label>
                    <input type="text" name="file_size" class="form-control" placeholder="e.g. 1.2MB">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Description</label>
                    <input type="text" name="description" class="form-control">
                </div>
                <div class="col-md-1">
                    <button type="submit" class="btn btn-primary w-100"><i class="fas fa-plus"></i></button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h5>Download History</h5></div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead><tr><th>Student</th><th>Document</th><th>Type</th><th>Date</th><th>Status</th><th>Actions</th></tr></thead>
                    <tbody>
                        <?php foreach ($downloads as $d): ?>
                        <tr>
                            <td><?= htmlspecialchars($d['student_name'] ?? $d['student_id'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($d['document_name'] ?? $d['file_name'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($d['document_type'] ?? $d['type'] ?? $d['file_type'] ?? '-') ?></td>
                            <td><?= $d['created_at'] ?? '-' ?></td>
                            <td><span class="badge bg-success">Downloaded</span></td>
                            <td>
                                <form method="POST" class="d-inline" onsubmit="return confirm('Delete this download record?')">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="action" value="delete_download">
                                    <input type="hidden" name="id" value="<?= $d['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger py-0 px-2"><i class="fas fa-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($downloads)): ?><tr><td colspan="6" class="text-center">No download records</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
</body>
</html>
