<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
$ctx = bootstrapStaffDashboard(['librarian', 'library', 'admin']);
$staffDb = $ctx['staff'];
$pageTitle = 'Student Library';

$redirectSection = $_POST['_section'] ?? '';
function redirectBack($hash = '') {
    $section = $GLOBALS['redirectSection'];
    $loc = 'student-library.php';
    if ($hash) $loc .= '#' . $hash;
    elseif ($section) $loc .= '#' . $section;
    header('Location: ' . $loc);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $staffDb) {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
        $_SESSION['error'] = 'Invalid security token.'; header('Location: student-library.php'); exit;
    }
    $action = $_POST['action'] ?? '';

    if ($action === 'add_book') {
        $title    = trim($_POST['title'] ?? '');
        $author   = trim($_POST['author'] ?? '');
        $isbn     = trim($_POST['isbn'] ?? '');
        $category = trim($_POST['category'] ?? '');
        $location = trim($_POST['location'] ?? '');
        $description = trim($_POST['description'] ?? '');

        if ($title && $author) {
            $stmt = $staffDb->prepare("INSERT INTO library_books (title, author, isbn, category, location, description, status) VALUES (?, ?, ?, ?, ?, ?, 'Available')");
            if ($stmt) {
                $stmt->bind_param('ssssss', $title, $author, $isbn, $category, $location, $description);
                if ($stmt->execute()) {
                    $_SESSION['success'] = "Book \"$title\" added successfully.";
                } else {
                    $_SESSION['error'] = 'Failed to add book: ' . $stmt->error;
                }
                $stmt->close();
            } else {
                $_SESSION['error'] = 'Database error preparing query.';
            }
        } else {
            $_SESSION['error'] = 'Title and author are required.';
        }
        redirectBack('add-book');
    }

    if ($action === 'issue_book') {
        $bookId   = (int)($_POST['book_id'] ?? 0);
        $memberId = trim($_POST['member_id'] ?? '');
        $bookTitle = trim($_POST['book_title'] ?? '');
        $dueDate  = $_POST['due_date'] ?? '';
        $studentId = (int)($_POST['student_id'] ?? 0);

        if ($bookId && $memberId && $bookTitle && $dueDate) {
            $check = $staffDb->prepare("SELECT status FROM library_books WHERE id = ?");
            if ($check) {
                $check->bind_param('i', $bookId);
                if ($check->execute()) {
                    $res = $check->get_result();
                    $row = $res->fetch_assoc();
                    $check->close();
                    if ($row && $row['status'] === 'Available') {
                        $stmt = $staffDb->prepare("INSERT INTO library_borrowing (member_id, student_id, book_id, book_title, borrow_date, due_date, return_status) VALUES (?, ?, ?, ?, CURDATE(), ?, 'Borrowed')");
                        if ($stmt) {
                            $stmt->bind_param('siiss', $memberId, $studentId, $bookId, $bookTitle, $dueDate);
                            if ($stmt->execute()) {
                                $upd = $staffDb->prepare("UPDATE library_books SET status = 'Borrowed' WHERE id = ?");
                                if ($upd) {
                                    $upd->bind_param('i', $bookId);
                                    $upd->execute();
                                    $upd->close();
                                }
                                $_SESSION['success'] = "Book issued successfully to member $memberId.";
                            } else {
                                $_SESSION['error'] = 'Failed to issue book: ' . $stmt->error;
                            }
                            $stmt->close();
                        } else {
                            $_SESSION['error'] = 'Database error preparing query.';
                        }
                    } else {
                        $_SESSION['error'] = 'This book is not available for borrowing.';
                    }
                } else {
                    $_SESSION['error'] = 'Failed to check book availability.';
                }
            }
        } else {
            $_SESSION['error'] = 'All fields are required to issue a book.';
        }
        redirectBack('issue-book');
    }

    if ($action === 'return_book') {
        $borrowId = (int)($_POST['borrow_id'] ?? 0);

        if ($borrowId) {
            $stmt = $staffDb->prepare("UPDATE library_borrowing SET return_date = CURDATE(), return_status = 'Returned' WHERE id = ? AND return_status = 'Borrowed'");
            if ($stmt) {
                $stmt->bind_param('i', $borrowId);
                if ($stmt->execute()) {
                    if ($stmt->affected_rows > 0) {
                        $bq = $staffDb->prepare("SELECT book_id FROM library_borrowing WHERE id = ?");
                        if ($bq) {
                            $bq->bind_param('i', $borrowId);
                            if ($bq->execute()) {
                                $bres = $bq->get_result();
                                $brow = $bres->fetch_assoc();
                                if ($brow) {
                                    $bookId = (int)$brow['book_id'];
                                    $upd = $staffDb->prepare("UPDATE library_books SET status = 'Available' WHERE id = ?");
                                    if ($upd) {
                                        $upd->bind_param('i', $bookId);
                                        $upd->execute();
                                        $upd->close();
                                    }
                                }
                            }
                            $bq->close();
                        }
                        $_SESSION['success'] = 'Book returned successfully.';
                    } else {
                        $_SESSION['error'] = 'Borrowing record not found or already returned.';
                    }
                } else {
                    $_SESSION['error'] = 'Failed to return book: ' . $stmt->error;
                }
                $stmt->close();
            }
        } else {
            $_SESSION['error'] = 'Invalid borrowing record.';
        }
        redirectBack('borrowed');
    }
}

$totalBooks = $availableBooks = $borrowedBooks = $overdueBooks = 0;
$books = [];
$activeBorrowings = [];

if ($staffDb) {
    try {
        $r = $staffDb->query("SELECT COUNT(*) as c FROM library_books");
        if ($r) $totalBooks = (int)$r->fetch_assoc()['c'];
        $r = $staffDb->query("SELECT COUNT(*) as c FROM library_books WHERE status='Available'");
        if ($r) $availableBooks = (int)$r->fetch_assoc()['c'];
        $borrowedQ = $staffDb->query("SELECT COUNT(*) as c FROM library_borrowing WHERE return_status='Borrowed'");
        if ($borrowedQ) $borrowedBooks = (int)$borrowedQ->fetch_assoc()['c'];
        $overdueQ = $staffDb->query("SELECT COUNT(*) as c FROM library_borrowing WHERE return_status='Borrowed' AND due_date < CURDATE()");
        if ($overdueQ) $overdueBooks = (int)$overdueQ->fetch_assoc()['c'];

        $r = $staffDb->query("SELECT b.*, CASE WHEN br.id IS NOT NULL THEN 'Borrowed' ELSE COALESCE(b.status,'Available') END as current_status FROM library_books b LEFT JOIN library_borrowing br ON b.id=br.book_id AND br.return_status IN ('Borrowed','Overdue') ORDER BY b.title LIMIT 100");
        if (!$r) $r = $staffDb->query("SELECT id, title, author, isbn, category, status FROM library_books ORDER BY title LIMIT 100");
        if ($r) while ($row = $r->fetch_assoc()) $books[] = $row;

        $bq = $staffDb->query("SELECT br.*, b.title as btitle, b.author as bauthor FROM library_borrowing br LEFT JOIN library_books b ON br.book_id = b.id WHERE br.return_status = 'Borrowed' ORDER BY br.due_date ASC");
        if ($bq) while ($row = $bq->fetch_assoc()) $activeBorrowings[] = $row;
    } catch (Exception $e) { error_log('student-library context: ' . $e->getMessage()); }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include_once __DIR__ . '/../includes/dashboard_head.php'; ?>
<style>
    .modal-backdrop { background: rgba(0,0,0,.5); }
    .modal-custom { display:none; position:fixed; top:0; left:0; width:100%; height:100%; z-index:1050; align-items:center; justify-content:center; }
    .modal-custom.show { display:flex; }
    .modal-content-custom { background:#fff; border-radius:12px; width:90%; max-width:560px; max-height:90vh; overflow-y:auto; box-shadow: 0 20px 60px rgba(0,0,0,.3); }
    .modal-header-custom { padding:16px 24px; border-bottom:1px solid #e5e7eb; display:flex; justify-content:space-between; align-items:center; }
    .modal-body-custom { padding:24px; }
    .modal-footer-custom { padding:16px 24px; border-top:1px solid #e5e7eb; display:flex; justify-content:flex-end; gap:10px; }
    .btn-close-custom { background:none; border:none; font-size:20px; cursor:pointer; color:#6b7280; padding:4px 8px; }
    .btn-close-custom:hover { color:#111; }
    .form-label-custom { font-weight:600; font-size:13px; color:#374151; margin-bottom:4px; display:block; }
    .form-input-custom { width:100%; padding:8px 12px; border:1px solid #d1d5db; border-radius:8px; font-size:14px; transition: border-color .2s; }
    .form-input-custom:focus { outline:none; border-color:#2563eb; box-shadow:0 0 0 3px rgba(37,99,235,.15); }
    .form-group-custom { margin-bottom:14px; }
    .btn-action { padding:4px 10px; font-size:12px; border-radius:6px; cursor:pointer; border:none; font-weight:500; }
    .btn-issue { background:#059669; color:#fff; }
    .btn-issue:hover { background:#047857; }
    .btn-return { background:#d97706; color:#fff; }
    .btn-return:hover { background:#b45309; }
    .overdue-badge { background:#dc2626; color:#fff; padding:2px 8px; border-radius:10px; font-size:11px; font-weight:600; }
</style>
</head>
<body>
<?php include_once __DIR__ . '/../includes/sidebar.php'; ?>
<?php include_once __DIR__ . '/../includes/dashboard_topbar.php'; ?>
<div class="main" style="margin-left:270px;padding:32px">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold mb-0"><i class="fas fa-book me-2"></i>Library Management</h4>
        <div>
            <button class="btn btn-primary btn-sm me-2" onclick="document.getElementById('modal-add-book').classList.add('show')"><i class="fas fa-plus me-1"></i>Add Book</button>
            <button class="btn btn-success btn-sm" onclick="document.getElementById('modal-issue-book').classList.add('show')"><i class="fas fa-hand-holding me-1"></i>Issue Book</button>
            <span class="text-muted small ms-2"><?= date('l, d M Y') ?></span>
        </div>
    </div>

    <?php if (!empty($_SESSION['success'])): ?>
    <div class="alert alert-success py-2 alert-dismissible fade show" style="border:none;border-radius:10px;background:#ecfdf5;color:#065f46;">
        <i class="fas fa-check-circle me-1"></i> <?= htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>
    <?php if (!empty($_SESSION['error'])): ?>
    <div class="alert alert-danger py-2 alert-dismissible fade show" style="border:none;border-radius:10px;background:#fef2f2;color:#991b1b;">
        <i class="fas fa-exclamation-circle me-1"></i> <?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <div class="stats-grid">
        <div class="stat-card primary"><div class="stat-icon"><i class="fas fa-book"></i></div><div class="stat-content"><h3><?= $totalBooks ?></h3><p>Total Books</p></div></div>
        <div class="stat-card success"><div class="stat-icon"><i class="fas fa-check-circle"></i></div><div class="stat-content"><h3><?= $availableBooks ?></h3><p>Available</p></div></div>
        <div class="stat-card warning"><div class="stat-icon"><i class="fas fa-bookmark"></i></div><div class="stat-content"><h3><?= $borrowedBooks ?></h3><p>Borrowed</p></div></div>
        <div class="stat-card info"><div class="stat-icon"><i class="fas fa-clock"></i></div><div class="stat-content"><h3><?= $overdueBooks ?></h3><p>Overdue</p></div></div>
    </div>

    <div class="content-section mb-4" id="borrowed">
        <h5 class="fw-bold mb-3"><i class="fas fa-hand-holding me-2"></i>Active Borrowings</h5>
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead><tr><th>Member</th><th>Book</th><th>Borrowed</th><th>Due</th><th>Status</th><th>Action</th></tr></thead>
                <tbody>
                    <?php if (empty($activeBorrowings)): ?>
                    <tr><td colspan="6" class="text-center text-muted">No active borrowings.</td></tr>
                    <?php else: ?>
                    <?php foreach ($activeBorrowings as $br): ?>
                    <tr>
                        <td><?= htmlspecialchars($br['member_id'] ?? '') ?></td>
                        <td><?= htmlspecialchars($br['btitle'] ?? $br['book_title'] ?? '') ?></td>
                        <td><?= htmlspecialchars($br['borrow_date'] ?? '') ?></td>
                        <td><?= htmlspecialchars($br['due_date'] ?? '') ?></td>
                        <td>
                            <?php if ((strtotime($br['due_date'] ?? '') < time()) && ($br['return_status'] ?? '') === 'Borrowed'): ?>
                                <span class="overdue-badge"><i class="fas fa-exclamation-triangle me-1"></i>Overdue</span>
                            <?php else: ?>
                                <span class="badge bg-warning"><?= htmlspecialchars($br['return_status'] ?? 'Borrowed') ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <form method="POST" style="display:inline" onsubmit="return confirm('Mark this book as returned?')">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                                <input type="hidden" name="action" value="return_book">
                                <input type="hidden" name="borrow_id" value="<?= (int)$br['id'] ?>">
                                <button type="submit" class="btn-action btn-return" title="Return Book"><i class="fas fa-undo me-1"></i>Return</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="content-section" id="add-book">
        <h5 class="fw-bold mb-3"><i class="fas fa-list me-2"></i>Book Catalog</h5>
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead><tr><th>Title</th><th>Author</th><th>ISBN</th><th>Category</th><th>Status</th><th>Action</th></tr></thead>
                <tbody>
                    <?php if (empty($books)): ?>
                    <tr><td colspan="6" class="text-center text-muted">No books found.</td></tr>
                    <?php else: ?>
                    <?php foreach ($books as $b): ?>
                    <tr>
                        <td><?= htmlspecialchars($b['title'] ?? '') ?></td>
                        <td><?= htmlspecialchars($b['author'] ?? '') ?></td>
                        <td><?= htmlspecialchars($b['isbn'] ?? '') ?></td>
                        <td><?= htmlspecialchars($b['category'] ?? '') ?></td>
                        <td><span class="badge bg-<?= (($b['current_status']??$b['status'])==='Available')?'success':'warning' ?>"><?= htmlspecialchars($b['current_status'] ?? $b['status'] ?? 'Unknown') ?></span></td>
                        <td>
                            <?php if (($b['current_status'] ?? $b['status'] ?? '') === 'Available'): ?>
                            <button class="btn-action btn-issue" onclick="prefillIssue(<?= (int)$b['id'] ?>, '<?= htmlspecialchars(addslashes($b['title'] ?? '')) ?>')" title="Issue Book"><i class="fas fa-hand-holding me-1"></i>Issue</button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Book Modal -->
<div class="modal-custom" id="modal-add-book">
    <div class="modal-content-custom">
        <div class="modal-header-custom">
            <h5 class="fw-bold mb-0"><i class="fas fa-plus-circle me-2 text-primary"></i>Add New Book</h5>
            <button class="btn-close-custom" onclick="this.closest('.modal-custom').classList.remove('show')">&times;</button>
        </div>
        <form method="POST">
            <div class="modal-body-custom">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                <input type="hidden" name="action" value="add_book">
                <div class="form-group-custom">
                    <label class="form-label-custom">Title *</label>
                    <input type="text" name="title" class="form-input-custom" required placeholder="e.g. Anatomy & Physiology">
                </div>
                <div class="form-group-custom">
                    <label class="form-label-custom">Author *</label>
                    <input type="text" name="author" class="form-input-custom" required placeholder="e.g. John Smith">
                </div>
                <div class="form-group-custom">
                    <label class="form-label-custom">ISBN</label>
                    <input type="text" name="isbn" class="form-input-custom" placeholder="e.g. 978-3-16-148410-0">
                </div>
                <div class="form-group-custom">
                    <label class="form-label-custom">Category</label>
                    <select name="category" class="form-input-custom">
                        <option value="">Select category...</option>
                        <option value="Nursing">Nursing</option>
                        <option value="Midwifery">Midwifery</option>
                        <option value="Anatomy">Anatomy</option>
                        <option value="Pharmacology">Pharmacology</option>
                        <option value="General">General</option>
                        <option value="Reference">Reference</option>
                        <option value="Textbook">Textbook</option>
                    </select>
                </div>
                <div class="form-group-custom">
                    <label class="form-label-custom">Location</label>
                    <input type="text" name="location" class="form-input-custom" placeholder="e.g. Shelf A-3">
                </div>
                <div class="form-group-custom">
                    <label class="form-label-custom">Description</label>
                    <textarea name="description" class="form-input-custom" rows="2" placeholder="Brief description..."></textarea>
                </div>
            </div>
            <div class="modal-footer-custom">
                <button type="button" class="btn btn-secondary btn-sm" onclick="this.closest('.modal-custom').classList.remove('show')">Cancel</button>
                <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-save me-1"></i>Save Book</button>
            </div>
        </form>
    </div>
</div>

<!-- Issue Book Modal -->
<div class="modal-custom" id="modal-issue-book">
    <div class="modal-content-custom">
        <div class="modal-header-custom">
            <h5 class="fw-bold mb-0"><i class="fas fa-hand-holding me-2 text-success"></i>Issue Book</h5>
            <button class="btn-close-custom" onclick="this.closest('.modal-custom').classList.remove('show')">&times;</button>
        </div>
        <form method="POST">
            <div class="modal-body-custom">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                <input type="hidden" name="action" value="issue_book">
                <div class="form-group-custom">
                    <label class="form-label-custom">Book *</label>
                    <select name="book_id" class="form-input-custom" id="issue-book-select" required>
                        <option value="">Select book...</option>
                        <?php foreach ($books as $b): ?>
                        <?php if (($b['current_status'] ?? $b['status'] ?? '') === 'Available'): ?>
                        <option value="<?= (int)$b['id'] ?>"><?= htmlspecialchars(($b['title'] ?? '') . ' — ' . ($b['author'] ?? '')) ?></option>
                        <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                </div>
                <input type="hidden" name="book_title" id="issue-book-title" value="">
                <div class="form-group-custom">
                    <label class="form-label-custom">Member ID *</label>
                    <input type="text" name="member_id" class="form-input-custom" required placeholder="e.g. LIB-001">
                </div>
                <div class="form-group-custom">
                    <label class="form-label-custom">Student ID</label>
                    <input type="number" name="student_id" class="form-input-custom" placeholder="Optional student ID">
                </div>
                <div class="form-group-custom">
                    <label class="form-label-custom">Due Date *</label>
                    <input type="date" name="due_date" class="form-input-custom" required min="<?= date('Y-m-d') ?>" value="<?= date('Y-m-d', strtotime('+14 days')) ?>">
                </div>
            </div>
            <div class="modal-footer-custom">
                <button type="button" class="btn btn-secondary btn-sm" onclick="this.closest('.modal-custom').classList.remove('show')">Cancel</button>
                <button type="submit" class="btn btn-success btn-sm"><i class="fas fa-check me-1"></i>Issue Book</button>
            </div>
        </form>
    </div>
</div>

<script>
function prefillIssue(bookId, bookTitle) {
    var sel = document.getElementById('issue-book-select');
    if (sel) {
        for (var i = 0; i < sel.options.length; i++) {
            if (sel.options[i].value == bookId) { sel.selectedIndex = i; break; }
        }
    }
    document.getElementById('issue-book-title').value = bookTitle;
    document.getElementById('modal-issue-book').classList.add('show');
}
document.getElementById('issue-book-select')?.addEventListener('change', function() {
    var opt = this.options[this.selectedIndex];
    document.getElementById('issue-book-title').value = opt ? opt.text.split(' — ')[0] : '';
});
document.querySelectorAll('.modal-custom').forEach(function(modal) {
    modal.addEventListener('click', function(e) {
        if (e.target === modal) modal.classList.remove('show');
    });
});
</script>
<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
</body>
</html>
