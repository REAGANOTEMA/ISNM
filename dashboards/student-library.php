<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
$ctx = bootstrapStaffDashboard(['librarian', 'library', 'admin']);
$staffDb = $ctx['staff'];
$pageTitle = 'Student Library';

$totalBooks = $availableBooks = $borrowedBooks = $overdueBooks = 0;
$books = [];
if ($staffDb) {
    try {
        $r = $staffDb->query("SELECT COUNT(*) as c FROM library_books");
        if ($r) $totalBooks = (int)$r->fetch_assoc()['c'];
        $r = $staffDb->query("SELECT COUNT(*) as c FROM library_books WHERE status='Available'");
        if ($r) $availableBooks = (int)$r->fetch_assoc()['c'];
        $borrowedQ = $staffDb->query("SELECT COUNT(*) as c FROM library_borrowing WHERE status='borrowed'");
        if ($borrowedQ) $borrowedBooks = (int)$borrowedQ->fetch_assoc()['c'];
        $overdueQ = $staffDb->query("SELECT COUNT(*) as c FROM library_borrowing WHERE status='overdue'");
        if ($overdueQ) $overdueBooks = (int)$overdueQ->fetch_assoc()['c'];
        $r = $staffDb->query("SELECT b.*, CASE WHEN br.id IS NOT NULL THEN 'Borrowed' ELSE COALESCE(b.status,'Available') END as current_status FROM library_books b LEFT JOIN library_borrowing br ON b.id=br.book_id AND br.status IN ('borrowed','overdue') ORDER BY b.title LIMIT 100");
        if (!$r) $r = $staffDb->query("SELECT id, title, author, isbn, category, status FROM library_books ORDER BY title LIMIT 100");
        if ($r) while ($row = $r->fetch_assoc()) $books[] = $row;
    } catch (Exception $e) {}
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include_once __DIR__ . '/../includes/dashboard_head.php'; ?>
</head>
<body>
<?php include_once __DIR__ . '/../includes/sidebar.php'; ?>
<div class="main" style="margin-left:270px;padding:32px">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold mb-0"><i class="fas fa-book me-2"></i>Library Management</h4>
        <span class="text-muted small"><?= date('l, d M Y') ?></span>
    </div>
    <div class="stats-grid">
        <div class="stat-card primary"><div class="stat-icon"><i class="fas fa-book"></i></div><div class="stat-content"><h3><?= $totalBooks ?></h3><p>Total Books</p></div></div>
        <div class="stat-card success"><div class="stat-icon"><i class="fas fa-check-circle"></i></div><div class="stat-content"><h3><?= $availableBooks ?></h3><p>Available</p></div></div>
        <div class="stat-card warning"><div class="stat-icon"><i class="fas fa-bookmark"></i></div><div class="stat-content"><h3><?= $borrowedBooks ?></h3><p>Borrowed</p></div></div>
        <div class="stat-card info"><div class="stat-icon"><i class="fas fa-clock"></i></div><div class="stat-content"><h3><?= $overdueBooks ?></h3><p>Overdue</p></div></div>
    </div>
    <div class="content-section">
        <h5 class="fw-bold mb-3"><i class="fas fa-list me-2"></i>Book Catalog</h5>
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead><tr><th>Title</th><th>Author</th><th>ISBN</th><th>Category</th><th>Status</th></tr></thead>
                <tbody>
                    <?php if (empty($books)): ?>
                    <tr><td colspan="5" class="text-center text-muted">No books found.</td></tr>
                    <?php else: ?>
                    <?php foreach ($books as $b): ?>
                    <tr>
                        <td><?= htmlspecialchars($b['title'] ?? '') ?></td>
                        <td><?= htmlspecialchars($b['author'] ?? '') ?></td>
                        <td><?= htmlspecialchars($b['isbn'] ?? '') ?></td>
                        <td><?= htmlspecialchars($b['category'] ?? '') ?></td>
                        <td><span class="badge bg-<?= (($b['current_status']??$b['status'])==='Available')?'success':'warning' ?>"><?= htmlspecialchars($b['current_status'] ?? $b['status'] ?? 'Unknown') ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
</body>
</html>