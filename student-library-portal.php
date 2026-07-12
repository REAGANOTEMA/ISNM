<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/auth-service.php';
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id'], $_SESSION['role'])) {
    header('Location: student-login.php'); exit;
}
$isStaff = ($_SESSION['type'] ?? '') === 'staff';
$isStudent = ($_SESSION['type'] ?? '') === 'student';
if (!$isStaff && !$isStudent) {
    header('Location: student-login.php'); exit;
}
$auth_service = new AuthenticationService();
$user = $auth_service->getCurrentUser();
$staffDb = getStaffConnection();
$studentsDb = getStudentsConnection();
$userId = (int)($user['id'] ?? 0);
$studentNumber = $user['student_number'] ?? ($_SESSION['student_number'] ?? '');

$studentInfo = [];
$books = [];
$borrowings = [];
$fines = [];

if ($studentsDb) {
    $stmt = $studentsDb->prepare("SELECT * FROM students WHERE student_number=? OR id=? LIMIT 1");
    $stmt->bind_param("si", $studentNumber, $userId);
    if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
    $sr = $stmt->get_result();
    $studentInfo = $sr ? $sr->fetch_assoc() : [];
    $stmt->close();
    $sidInt = (int)($studentInfo['id'] ?? $userId);

    // Try students_db library tables first
    $bk = $studentsDb->query("SELECT * FROM library_books ORDER BY book_title LIMIT 50");
    if ($bk) $books = $bk->fetch_all(MYSQLI_ASSOC);

    $stmt = $studentsDb->prepare("SELECT lb.*, lk.book_title FROM library_borrowing lb LEFT JOIN library_books lk ON lb.book_id = lk.id WHERE lb.student_id=? ORDER BY lb.borrow_date DESC LIMIT 20");
    $stmt->bind_param("i", $sidInt);
    if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
    $bw = $stmt->get_result();
    if ($bw) $borrowings = $bw->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $stmt = $studentsDb->prepare("SELECT * FROM library_fines WHERE student_id=? AND paid=0");
    $stmt->bind_param("i", $sidInt);
    if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
    $fn = $stmt->get_result();
    if ($fn) $fines = $fn->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // Fallback to staffs_db
    if (empty($books) && $staffDb) {
        $bk2 = $staffDb->query("SELECT * FROM library_management WHERE status='Available' ORDER BY book_title LIMIT 50");
        if ($bk2) $books = $bk2->fetch_all(MYSQLI_ASSOC);
    }
}

$search = $_GET['q'] ?? '';
if ($search && !empty($books)) {
    $s = strtolower($search);
    $books = array_filter($books, function($b) use ($s) {
        return strpos(strtolower($b['book_title']??''), $s) !== false
            || strpos(strtolower($b['author']??''), $s) !== false
            || strpos(strtolower($b['isbn']??''), $s) !== false;
    });
}

$fullName = $studentInfo ? htmlspecialchars(($studentInfo['surname']??'') . ' ' . ($studentInfo['firstname']??'')) : 'Student';
$totalFines = array_sum(array_column($fines, 'fine_amount'));

$pageTitle = 'Library Portal';
require_once __DIR__ . '/includes/dashboard_head.php';
include_once __DIR__ . '/includes/sidebar.php';
?>
<style>
:root{--primary:#2c5f8a;--accent:#1a9e6e}
body{background:#f0f4f8;font-family:'Segoe UI',sans-serif}
.lib-card{border:none;border-radius:16px;overflow:hidden;box-shadow:0 8px 30px rgba(0,0,0,.08);margin-bottom:24px}
.lib-card .card-header{background:linear-gradient(135deg,#2c5f8a,#1a9e6e);padding:20px 28px;border:none;color:#fff}
.lib-card .card-body{padding:24px 28px}
.book-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:16px;padding:0}
.book-card{border:1px solid #e9ecef;border-radius:12px;padding:16px;transition:all .2s}
.book-card:hover{box-shadow:0 4px 16px rgba(0,0,0,.08);border-color:#2c5f8a}
.book-card .title{font-weight:600;font-size:.95rem;margin-bottom:4px}
.book-card .author{font-size:.85rem;color:#64748b}
.book-card .meta{font-size:.8rem;color:#94a3b8;margin-top:8px}
.avail-badge{font-size:.7rem;padding:2px 10px;border-radius:12px}
.avail-yes{background:#dcfce7;color:#166534}
.avail-no{background:#fef2f2;color:#991b1b}
</style>
<div class="main" style="margin-left:270px;padding:32px">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-1"><i class="fas fa-book-open-reader me-2" style="color:#2c5f8a"></i>Library Portal</h2>
            <p class="text-muted mb-0">Browse catalog, check borrowing status & fines</p>
        </div>
        <div class="text-end">
            <div class="fw-semibold"><?= $fullName ?></div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="stat-box bg-white p-3 rounded-3 shadow-sm">
                <div class="h3 fw-bold mb-0" style="color:#2c5f8a"><?= count($books) ?></div>
                <small class="text-muted">Available Books</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-box bg-white p-3 rounded-3 shadow-sm">
                <div class="h3 fw-bold mb-0" style="color:#1a9e6e"><?= count($borrowings) ?></div>
                <small class="text-muted">Borrowed Books</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-box bg-white p-3 rounded-3 shadow-sm">
                <div class="h3 fw-bold mb-0" style="color:#d97706"><?= count($fines) ?></div>
                <small class="text-muted">Active Fines</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-box bg-white p-3 rounded-3 shadow-sm">
                <div class="h3 fw-bold mb-0" style="color:#dc2626">UGX <?= number_format($totalFines) ?></div>
                <small class="text-muted">Total Fine Due</small>
            </div>
        </div>
    </div>

    <?php if (!empty($borrowings)): ?>
    <div class="card lib-card" style="border-radius:16px;border:none;box-shadow:0 8px 30px rgba(0,0,0,.08);margin-bottom:24px;overflow:hidden">
        <div class="card-header" style="background:linear-gradient(135deg,#2c5f8a,#1a9e6e);padding:16px 24px;color:#fff">
            <h5 class="mb-0 fw-semibold"><i class="fas fa-hand-holding-heart me-2"></i>My Borrowings</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light"><tr><th>Book</th><th>Borrowed</th><th>Due</th><th>Status</th><th>Fine</th></tr></thead>
                    <tbody>
                        <?php foreach ($borrowings as $b): 
                            $isOverdue = !$b['return_date'] && strtotime($b['due_date']) < time();
                            $status = $b['status'] ?? ($isOverdue ? 'Overdue' : 'Borrowed');
                        ?>
                        <tr>
                            <td><?= htmlspecialchars($b['book_title']??'â€”') ?></td>
                            <td><?= htmlspecialchars($b['borrow_date']??'â€”') ?></td>
                            <td><?= htmlspecialchars($b['due_date']??'â€”') ?></td>
                            <td><span class="status-badge" style="background:<?= $isOverdue?'#fef2f2':'#dcfce7' ?>;color:<?= $isOverdue?'#991b1b':'#166534' ?>;padding:2px 12px;border-radius:12px;font-size:.75rem"><?= $status ?></span></td>
                            <td><?= $b['fine_amount'] ? 'UGX '.number_format((float)$b['fine_amount']) : 'â€”' ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="card" style="border:none;border-radius:16px;box-shadow:0 8px 30px rgba(0,0,0,.08);overflow:hidden">
        <div class="card-header" style="background:linear-gradient(135deg,#2c5f8a,#1a9e6e);padding:16px 24px;color:#fff">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                <h5 class="mb-0 fw-semibold"><i class="fas fa-book me-2"></i>Library Catalog</h5>
                <form method="get" class="d-flex gap-2">
                    <input type="text" name="q" class="form-control form-control-sm" placeholder="Search by title, author, ISBN..." value="<?= htmlspecialchars($search) ?>" style="max-width:280px">
                    <button type="submit" class="btn btn-sm btn-light"><i class="fas fa-search"></i></button>
                </form>
            </div>
        </div>
        <div class="card-body p-3">
            <?php if (empty($books)): ?>
            <div class="text-center py-4 text-muted">
                <i class="fas fa-book-open fa-3x mb-2"></i>
                <p class="mb-0">No books found<?= $search ? ' matching "'.htmlspecialchars($search).'"' : '' ?>.</p>
            </div>
            <?php else: ?>
            <div class="book-grid">
                <?php foreach ($books as $b): 
                    $avail = (int)($b['available_copies']??0);
                ?>
                <div class="book-card">
                    <div class="title"><?= htmlspecialchars($b['book_title']??'') ?></div>
                    <div class="author"><?= htmlspecialchars($b['author']??'Unknown') ?></div>
                    <div class="meta">
                        <?php if ($b['isbn']??''): ?>ISBN: <?= htmlspecialchars($b['isbn']) ?><br><?php endif; ?>
                        <?= htmlspecialchars($b['category']??'') ?>
                        <?php if ($b['publisher']??''): ?> Â· <?= htmlspecialchars($b['publisher']) ?><?php endif; ?>
                    </div>
                    <div class="mt-2">
                        <span class="avail-badge <?= $avail > 0 ? 'avail-yes' : 'avail-no' ?>">
                            <?= $avail > 0 ? $avail.' available' : 'Unavailable' ?>
                        </span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/includes/dashboard_footer.php'; ?>