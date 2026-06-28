<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
$ctx = bootstrapStaffDashboard(['librarian', 'library', 'admin', 'student']);
$staffDb = $ctx['staff'];
$studentsDb = $ctx['students'];
$user = $ctx['user'];
$userRole = $user['role'] ?? '';
$userId = (int)($user['id'] ?? 0);

$view = $_GET['view'] ?? 'catalog';
$q = trim($_GET['q'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 24;
$offset = ($page - 1) * $limit;

$books = []; $totalBooks = 0;
$myBorrows = []; $myFines = 0;
$stats = ['total' => 0, 'available' => 0, 'borrowed' => 0, 'overdue' => 0];

if ($staffDb) {
    try {
        $r = $staffDb->query("SELECT COUNT(*) c FROM library_books WHERE status='Available'"); if ($r) $stats['available'] = (int)$r->fetch_assoc()['c'];
        $r = $staffDb->query("SELECT COUNT(*) c FROM library_books"); if ($r) $stats['total'] = (int)$r->fetch_assoc()['c'];
        $r = $staffDb->query("SELECT COUNT(*) c FROM library_borrowing WHERE return_status IN ('Borrowed','Overdue')"); if ($r) $stats['borrowed'] = (int)$r->fetch_assoc()['c'];
        $r = $staffDb->query("SELECT COUNT(*) c FROM library_borrowing WHERE return_status='Overdue'"); if ($r) $stats['overdue'] = (int)$r->fetch_assoc()['c'];

        $where = "WHERE 1=1";
        $types = '';
        $params = [];
        if ($q !== '') {
            $like = '%' . $q . '%';
            $where .= " AND (title LIKE ? OR author LIKE ? OR isbn LIKE ? OR category LIKE ?)";
            $types .= 'ssss';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }
        $stmt = $staffDb->prepare("SELECT COUNT(*) c FROM library_books $where");
        if ($stmt) {
            if (!empty($params)) $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $r = $stmt->get_result();
            if ($r) $totalBooks = (int)$r->fetch_assoc()['c'];
            $stmt->close();
        }
        $stmt = $staffDb->prepare("SELECT b.*, COALESCE(br.return_status, 'Available') as avail_status FROM library_books b LEFT JOIN library_borrowing br ON b.id=br.book_id AND br.return_status IN ('Borrowed','Overdue') $where ORDER BY b.title LIMIT " . intval($limit) . " OFFSET " . intval($offset));
        if ($stmt) {
            if (!empty($params)) $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $r = $stmt->get_result();
            if ($r) $books = $r->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
        }

        if ($userId) {
            $r = $staffDb->query("SELECT b.title, b.author, b.isbn, br.borrow_date, br.due_date, br.return_status, br.late_fee FROM library_borrowing br JOIN library_books b ON br.book_id=b.id WHERE (br.student_id=" . intval($userId) . " OR br.borrower_id=" . intval($userId) . ") AND br.return_status IN ('Borrowed','Overdue') ORDER BY br.due_date ASC");
            if ($r) $myBorrows = $r->fetch_all(MYSQLI_ASSOC);
            $r = $staffDb->query("SELECT COALESCE(SUM(late_fee),0) total FROM library_borrowing WHERE (student_id=" . intval($userId) . " OR borrower_id=" . intval($userId) . ") AND fine_paid=0");
            if ($r) $myFines = (float)$r->fetch_row()[0];
        }
    } catch (Exception $e) {}
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include_once __DIR__ . '/../includes/dashboard_head.php'; ?>
<style>
    .search-box { max-width: 500px; }
    .book-card { transition: transform .15s; }
    .book-card:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,.1); }
    .book-cover { height: 180px; object-fit: cover; background: #f0f4f8; display: flex; align-items: center; justify-content: center; font-size: 3rem; color: #adb5bd; }
    .nav-tabs .nav-link { cursor: pointer; }
    .fine-badge { font-size: .85rem; }
</style>
</head>
<body>
<?php include_once __DIR__ . '/../includes/sidebar.php'; ?>
<div class="main" style="margin-left:270px;padding:32px">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold mb-0"><i class="fas fa-book-open me-2"></i>Student Library Portal</h4>
        <span class="text-muted small"><?= date('l, d M Y') ?></span>
    </div>

    <ul class="nav nav-tabs mb-4">
        <li class="nav-item"><a class="nav-link <?= $view==='catalog'?'active':'' ?>" href="?view=catalog">Book Catalog</a></li>
        <li class="nav-item"><a class="nav-link <?= $view==='my-books'?'active':'' ?>" href="?view=my-books">My Borrowed Books</a></li>
    </ul>

    <?php if ($view === 'catalog'): ?>
    <div class="stats-grid mb-4">
        <div class="stat-card primary"><div class="stat-icon"><i class="fas fa-book"></i></div><div class="stat-content"><h3><?= $stats['total'] ?></h3><p>Total Books</p></div></div>
        <div class="stat-card success"><div class="stat-icon"><i class="fas fa-check-circle"></i></div><div class="stat-content"><h3><?= $stats['available'] ?></h3><p>Available</p></div></div>
        <div class="stat-card warning"><div class="stat-icon"><i class="fas fa-bookmark"></i></div><div class="stat-content"><h3><?= $stats['borrowed'] ?></h3><p>Borrowed</p></div></div>
        <div class="stat-card danger"><div class="stat-icon"><i class="fas fa-clock"></i></div><div class="stat-content"><h3><?= $stats['overdue'] ?></h3><p>Overdue</p></div></div>
    </div>

    <form class="d-flex search-box mb-4" method="get">
        <input type="hidden" name="view" value="catalog">
        <input class="form-control me-2" type="search" name="q" placeholder="Search by title, author, ISBN, category..." value="<?= htmlspecialchars($q) ?>">
        <button class="btn btn-primary" type="submit"><i class="fas fa-search"></i></button>
        <?php if ($q !== ''): ?>
        <a href="?view=catalog" class="btn btn-outline-secondary ms-2"><i class="fas fa-times"></i></a>
        <?php endif; ?>
    </form>

    <div class="row g-3">
        <?php if (empty($books)): ?>
        <div class="col-12"><div class="alert alert-info"><?= $q ? 'No books match your search.' : 'No books in the catalog yet.' ?></div></div>
        <?php else: ?>
        <?php foreach ($books as $b): ?>
        <div class="col-xl-3 col-lg-4 col-md-6">
            <div class="card book-card h-100">
                <div class="book-cover"><i class="fas fa-book"></i></div>
                <div class="card-body">
                    <h6 class="card-title fw-semibold text-truncate"><?= htmlspecialchars($b['title'] ?? 'Untitled') ?></h6>
                    <p class="card-text small text-muted"><?= htmlspecialchars($b['author'] ?? 'Unknown') ?></p>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="badge bg-<?= ($b['avail_status']??$b['status'])==='Available'?'success':'warning' ?>"><?= htmlspecialchars($b['avail_status'] ?? $b['status'] ?? 'Unknown') ?></span>
                        <small class="text-muted"><?= htmlspecialchars($b['isbn'] ?? '') ?></small>
                    </div>
                    <small class="text-muted d-block mt-1">Category: <?= htmlspecialchars($b['category'] ?? '-') ?></small>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <?php if ($totalBooks > $limit): ?>
    <nav class="mt-4"><ul class="pagination justify-content-center">
        <?php for ($i=1; $i<=ceil($totalBooks/$limit); $i++): ?>
        <li class="page-item <?= $i===$page?'active':'' ?>"><a class="page-link" href="?view=catalog&q=<?= urlencode($q) ?>&page=<?= $i ?>"><?= $i ?></a></li>
        <?php endfor; ?>
    </ul></nav>
    <?php endif; ?>

    <?php elseif ($view === 'my-books'): ?>
    <div class="row g-3 mb-4">
        <div class="col-md-6"><div class="card"><div class="card-body"><h6>Currently Borrowed</h6><h3><?= count($myBorrows) ?></h3></div></div></div>
        <div class="col-md-6"><div class="card"><div class="card-body"><h6>Total Fines Due</h6><h3 class="text-danger">UGX <?= number_format($myFines, 0) ?></h3></div></div></div>
    </div>

    <div class="card">
        <div class="card-header"><h5 class="mb-0">My Borrowed Books</h5></div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead><tr><th>Title</th><th>Author</th><th>Borrow Date</th><th>Due Date</th><th>Status</th><th>Fine</th></tr></thead>
                    <tbody>
                        <?php if (empty($myBorrows)): ?>
                        <tr><td colspan="6" class="text-center text-muted">You have no borrowed books.</td></tr>
                        <?php else: ?>
                        <?php foreach ($myBorrows as $br): ?>
                        <tr>
                            <td><?= htmlspecialchars($br['title']) ?></td>
                            <td><?= htmlspecialchars($br['author'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($br['borrow_date'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($br['due_date'] ?? '-') ?></td>
                            <td><span class="badge bg-<?= $br['return_status']==='Borrowed'?'warning':'danger' ?>"><?= htmlspecialchars($br['return_status']) ?></span></td>
                            <td><?= $br['late_fee'] > 0 ? 'UGX '.number_format($br['late_fee'], 0) : '-' ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>
<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
</body>
</html>
