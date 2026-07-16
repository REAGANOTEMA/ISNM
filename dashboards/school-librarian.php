<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
require_once __DIR__ . '/../includes/enterprise_auth.php';

$ctx = bootstrapStaffDashboard(['school librarian', 'librarian']);
$auth_service = $ctx['auth'];
$conn = $ctx['staff'];
$user = $ctx['user'];
$user_id = (int) ($user['id'] ?? 0);
$user_role = $user['role'] ?? '';
$user_email = $user['email'] ?? '';
$user_name = $user['full_name'] ?? '';

$profileImageUrl = '../images/username.png';
$profileSettingsFile = __DIR__ . '/../includes/profile_settings.php';
if (file_exists($profileSettingsFile)) {
    include_once $profileSettingsFile;
    if (function_exists('getStaffProfileImageUrl')) {
        $url = getStaffProfileImageUrl($user_id);
        if ($url) $profileImageUrl = $url;
    }
}

$students_db_name = defined('STUDENTS_DB_NAME') ? STUDENTS_DB_NAME : 'igangaschool_students';

// Auto-create library tables if they don't exist
if ($conn) {
    $libTables = [
        "CREATE TABLE IF NOT EXISTS `{$students_db_name}`.library_books (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            author VARCHAR(255) NOT NULL,
            isbn VARCHAR(20),
            category VARCHAR(100) NOT NULL,
            subcategory VARCHAR(100),
            publication_year INT,
            edition VARCHAR(50),
            pages INT,
            publisher VARCHAR(255),
            language VARCHAR(50) DEFAULT 'English',
            location VARCHAR(100),
            call_number VARCHAR(50),
            description TEXT,
            status ENUM('Available','Borrowed','Reserved','Lost','Damaged') DEFAULT 'Available',
            barcode VARCHAR(50),
            date_added TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_category (category),
            INDEX idx_status (status),
            INDEX idx_isbn (isbn)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS `{$students_db_name}`.library_members (
            id INT AUTO_INCREMENT PRIMARY KEY,
            member_id VARCHAR(20) NOT NULL UNIQUE,
            student_id INT,
            staff_id INT,
            full_name VARCHAR(200) NOT NULL,
            email VARCHAR(200),
            phone VARCHAR(30),
            member_type ENUM('Student','Staff','Faculty') DEFAULT 'Student',
            membership_type ENUM('Regular','Student','Premium') DEFAULT 'Regular',
            status ENUM('Active','Suspended','Expired') DEFAULT 'Active',
            registration_date DATE DEFAULT (CURRENT_DATE),
            expiry_date DATE,
            address TEXT,
            INDEX idx_member_id (member_id),
            INDEX idx_student (student_id),
            INDEX idx_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS `{$students_db_name}`.library_borrowing (
            id INT AUTO_INCREMENT PRIMARY KEY,
            member_id VARCHAR(20) NOT NULL,
            student_id INT,
            book_id INT,
            book_title VARCHAR(255) NOT NULL,
            borrow_date DATE NOT NULL,
            due_date DATE NOT NULL,
            return_date DATE,
            return_status ENUM('Borrowed','Returned','Overdue') DEFAULT 'Borrowed',
            renewals INT DEFAULT 0,
            fine_amount DECIMAL(10,2) DEFAULT 0.00,
            notes TEXT,
            INDEX idx_member (member_id),
            INDEX idx_status (return_status),
            INDEX idx_due (due_date)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS `{$students_db_name}`.library_acquisitions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            author VARCHAR(255),
            isbn VARCHAR(20),
            quantity INT DEFAULT 1,
            unit_cost DECIMAL(10,2),
            total_cost DECIMAL(10,2),
            vendor VARCHAR(255),
            status ENUM('Requested','Ordered','Received','Cancelled') DEFAULT 'Requested',
            request_date DATE DEFAULT (CURRENT_DATE),
            acquisition_date DATE,
            expected_date DATE,
            requested_by INT,
            notes TEXT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS `{$students_db_name}`.library_maintenance (
            id INT AUTO_INCREMENT PRIMARY KEY,
            book_id INT NOT NULL,
            maintenance_type ENUM('repair','replace','withdraw') NOT NULL,
            notes TEXT,
            maintenance_date DATE DEFAULT (CURRENT_DATE),
            performed_by INT,
            status ENUM('Pending','In Progress','Completed') DEFAULT 'Pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_book (book_id),
            INDEX idx_type (maintenance_type)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS `{$students_db_name}`.library_budget (
            id INT AUTO_INCREMENT PRIMARY KEY,
            fiscal_year VARCHAR(10) NOT NULL,
            allocated_budget DECIMAL(15,2) DEFAULT 0.00,
            spent DECIMAL(15,2) DEFAULT 0.00,
            notes TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_year (fiscal_year)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS `{$students_db_name}`.library_vendors (
            id INT AUTO_INCREMENT PRIMARY KEY,
            vendor_name VARCHAR(255) NOT NULL,
            contact_person VARCHAR(200),
            email VARCHAR(200),
            phone VARCHAR(50),
            address TEXT,
            status ENUM('Active','Inactive') DEFAULT 'Active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_name (vendor_name)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    ];
    foreach ($libTables as $sql) {
        try { @$conn->query($sql); } catch (Exception $e) { error_log('library table create: ' . $e->getMessage()); }
    }
}

// Handle POST requests for library CRUD operations
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    $action = $_POST['action'];
    $table = $_POST['table'] ?? '';

    if ($conn && in_array($table, ['library_maintenance', 'library_budget', 'library_vendors', 'library_books', 'library_members', 'library_borrowing', 'library_acquisitions'])) {
        try {
            if ($action === 'create') {
                $fields = $_POST;
                unset($fields['action'], $fields['table']);
                $cols = []; $vals = []; $types = '';
                foreach ($fields as $k => $v) {
                    if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $k)) continue;
                    $cols[] = "`$k`";
                    $vals[] = $v;
                    $types .= 's';
                }
                if (!empty($cols)) {
                    $placeholders = implode(',', array_fill(0, count($cols), '?'));
                    $stmt = $conn->prepare("INSERT INTO `{$students_db_name}`.$table (" . implode(',', $cols) . ") VALUES ($placeholders)");
                    if ($stmt) {
                        $stmt->bind_param($types, ...$vals);
                        $stmt->execute();
                        echo json_encode(['success' => true, 'id' => $stmt->insert_id]);
                        $stmt->close();
                    } else {
                        echo json_encode(['error' => 'Prepare failed']);
                    }
                }
            } elseif ($action === 'update') {
                $id = (int)($_POST['id'] ?? 0);
                unset($fields['action'], $fields['table'], $fields['id']);
                $sets = []; $vals = []; $types = '';
                foreach ($fields as $k => $v) {
                    if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $k)) continue;
                    $sets[] = "`$k`=?";
                    $vals[] = $v;
                    $types .= 's';
                }
                if (!empty($sets) && $id) {
                    $vals[] = $id;
                    $types .= 'i';
                    $stmt = $conn->prepare("UPDATE `{$students_db_name}`.$table SET " . implode(',', $sets) . " WHERE id=?");
                    if ($stmt) {
                        $stmt->bind_param($types, ...$vals);
                        $stmt->execute();
                        echo json_encode(['success' => true]);
                        $stmt->close();
                    } else {
                        echo json_encode(['error' => 'Prepare failed']);
                    }
                }
            } else {
                echo json_encode(['error' => 'Unknown action']);
            }
        } catch (Exception $e) {
            echo json_encode(['error' => $e->getMessage()]);
        }
    } else {
        echo json_encode(['error' => 'Invalid request']);
    }
    exit;
}

// Get library statistics from database
$students_db = $ctx['students'];
$total_students = ($students_db && ($q = $students_db->query("SELECT COUNT(*) FROM students")) && ($r = $q->fetch_row())) ? (int) $r[0] : 0;
$total_staff = ($conn && ($q = $conn->query("SELECT COUNT(*) FROM staff")) && ($r = $q->fetch_row())) ? (int) $r[0] : 0;
$recent_applications = ($students_db && ($q = $students_db->query("SELECT COUNT(*) FROM student_admissions")) && ($r = $q->fetch_row())) ? (int) $r[0] : 0;
$active_programs = ($conn && ($q = $conn->query("SELECT COUNT(*) FROM academic_programs")) && ($r = $q->fetch_row())) ? (int) $r[0] : 0;
$total_books = ($conn && ($q = $conn->query("SELECT COUNT(*) FROM `{$students_db_name}`.library_books")) && ($r = $q->fetch_row())) ? (int) $r[0] : 0;
$available_books = ($conn && ($q = $conn->query("SELECT COUNT(*) FROM `{$students_db_name}`.library_books WHERE status = 'Available'")) && ($r = $q->fetch_row())) ? (int) $r[0] : 0;
$borrowed_books = ($conn && ($q = $conn->query("SELECT COUNT(*) FROM `{$students_db_name}`.library_borrowing WHERE return_status = 'Borrowed'")) && ($r = $q->fetch_row())) ? (int) $r[0] : 0;
$active_members = ($conn && ($q = $conn->query("SELECT COUNT(*) FROM `{$students_db_name}`.library_members WHERE status = 'Active'")) && ($r = $q->fetch_row())) ? (int) $r[0] : 0;

// Get book categories from library_books (if table exists)
$book_categories = [];
$book_category_names = ['Nursing', 'Midwifery', 'Medical Sciences', 'General Education'];
$category_colors = ['primary', 'success', 'info', 'warning'];
if ($conn) {
    try {
        $r = $conn->query("SELECT category, COUNT(*) as total, SUM(CASE WHEN status='Available' THEN 1 ELSE 0 END) as available FROM `{$students_db_name}`.library_books GROUP BY category ORDER BY category");
        if ($r) while ($row = $r->fetch_assoc()) $book_categories[] = $row;
    } catch (Exception $e) { error_log('school-librarian context: ' . $e->getMessage()); }
}
// If no book categories from DB, show empty state
if (empty($book_categories)) {
    foreach ($book_category_names as $i => $name) {
        $book_categories[] = ['category' => $name, 'total' => 0, 'available' => 0];
    }
}

// Get today's circulation stats
$checked_out_today = 0; $returned_today = 0; $renewed_today = 0; $overdue_books = 0;
if ($conn) {
    try {
        $r = $conn->query("SELECT COUNT(*) as c FROM `{$students_db_name}`.library_borrowing WHERE DATE(borrow_date)=CURDATE() AND return_status='Borrowed'");
        if ($r) $checked_out_today = (int)$r->fetch_assoc()['c'];
        $r = $conn->query("SELECT COUNT(*) as c FROM `{$students_db_name}`.library_borrowing WHERE DATE(return_date)=CURDATE() AND return_status='Returned'");
        if ($r) $returned_today = (int)$r->fetch_assoc()['c'];
        $r = $conn->query("SELECT COUNT(*) as c FROM `{$students_db_name}`.library_borrowing WHERE renewals>0 AND DATE(borrow_date)=CURDATE()");
        if ($r) $renewed_today = (int)$r->fetch_assoc()['c'];
        $r = $conn->query("SELECT COUNT(*) as c FROM `{$students_db_name}`.library_borrowing WHERE due_date<CURDATE() AND return_status='Borrowed'");
        if ($r) $overdue_books = (int)$r->fetch_assoc()['c'];
    } catch (Exception $e) { error_log('school-librarian context: ' . $e->getMessage()); }
}

// Get recent transactions
$recent_transactions = [];
if ($conn) {
    try {
        $r = $conn->query("SELECT lb.id, CONCAT(s.first_name,' ',s.surname) as member_name, lb.member_id, lb.book_title, lb.borrow_date as transaction_date, lb.return_status as status, lb.due_date FROM `{$students_db_name}`.library_borrowing lb LEFT JOIN `{$students_db_name}`.students s ON lb.student_id=s.student_id ORDER BY lb.borrow_date DESC LIMIT 10");
        if ($r) $recent_transactions = $r->fetch_all(MYSQLI_ASSOC);
    } catch (Exception $e) { error_log('school-librarian context: ' . $e->getMessage()); }
}

// Get member statistics
$member_students = 0; $member_staff = 0; $member_faculty = 0; $new_members_month = 0;
if ($conn) {
    try {
        $r = $conn->query("SELECT COUNT(*) as c FROM `{$students_db_name}`.library_members WHERE member_type='Student' AND status='Active'");
        if ($r) $member_students = (int)$r->fetch_assoc()['c'];
        $r = $conn->query("SELECT COUNT(*) as c FROM `{$students_db_name}`.library_members WHERE member_type='Staff' AND status='Active'");
        if ($r) $member_staff = (int)$r->fetch_assoc()['c'];
        $r = $conn->query("SELECT COUNT(*) as c FROM `{$students_db_name}`.library_members WHERE member_type='Faculty' AND status='Active'");
        if ($r) $member_faculty = (int)$r->fetch_assoc()['c'];
        $r = $conn->query("SELECT COUNT(*) as c FROM `{$students_db_name}`.library_members WHERE MONTH(registration_date)=MONTH(CURDATE()) AND YEAR(registration_date)=YEAR(CURDATE())");
        if ($r) $new_members_month = (int)$r->fetch_assoc()['c'];
    } catch (Exception $e) { error_log('school-librarian context: ' . $e->getMessage()); }
}

// Get recent member registrations
$recent_members = [];
if ($conn) {
    try {
        $r = $conn->query("SELECT lm.*, CONCAT(s.first_name,' ',s.surname) as student_name FROM `{$students_db_name}`.library_members lm LEFT JOIN `{$students_db_name}`.students s ON lm.student_id=s.student_id ORDER BY lm.registration_date DESC LIMIT 5");
        if ($r) $recent_members = $r->fetch_all(MYSQLI_ASSOC);
    } catch (Exception $e) { error_log('school-librarian context: ' . $e->getMessage()); }
}

// Get acquisitions
$recent_acquisitions = [];
if ($conn) {
    try {
        $r = $conn->query("SELECT * FROM `{$students_db_name}`.library_acquisitions ORDER BY acquisition_date DESC LIMIT 5");
        if ($r) $recent_acquisitions = $r->fetch_all(MYSQLI_ASSOC);
    } catch (Exception $e) { error_log('school-librarian context: ' . $e->getMessage()); }
}

// Get recent activities
$recent_activities = [];
if ($conn) {
    try {
        $result = $conn->query("SELECT activity_description as activity, created_at FROM staff_activity_log ORDER BY created_at DESC LIMIT 10");
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $recent_activities[] = $row;
            }
        }
    } catch (Exception $e) { error_log('school-librarian context: ' . $e->getMessage()); }
}
?>
<?php
$pageToSection = [
    'home'         => 'overview',
    'overview'     => 'overview',
    'catalogue'    => 'books',
    'books'        => 'books',
    'borrowing'    => 'circulation',
    'returns'      => 'circulation',
    'reservations' => 'circulation',
    'members'      => 'members',
    'barcodes'     => 'books',
    'inventory'    => 'books',
    'fines'        => 'circulation',
    'acquisition'  => 'acquisition',
    'activities'   => 'activities',
];
$requestedPage = $_GET['page'] ?? 'home';
$section = $pageToSection[$requestedPage] ?? 'overview';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include_once __DIR__ . '/../includes/dashboard_head.php'; ?>
<style>
.lib-content{margin-left:270px;padding:24px;min-height:100vh}

@media(max-width:768px){.lib-content{margin-left:0!important;padding:12px!important}}
</style>
</head>
<body class="ent-layout">
    <div class="lib-content">
        <?php include_once __DIR__ . '/../includes/sidebar.php'; ?>
<?php include_once __DIR__ . '/../includes/dashboard_topbar.php'; ?>
        
                <!-- Library Overview -->
                <section id="overview" class="content-section dashboard-section<?= $section==='overview'?' active':'' ?>" data-section="overview">
                    <h2>Library Overview <button onclick="window.print()" class="btn btn-sm btn-outline-secondary no-print"><i class="fas fa-print"></i> Print</button></h2>
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-book"></i>
                            </div>
                            <div class="stat-content">
                                <h3><?php echo $total_books; ?></h3>
                                <p>Total Books</p>
                            </div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <div class="stat-content">
                                <h3><?php echo $available_books; ?></h3>
                                <p>Available Books</p>
                            </div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-hand-holding"></i>
                            </div>
                            <div class="stat-content">
                                <h3><?php echo $borrowed_books; ?></h3>
                                <p>Borrowed Books</p>
                            </div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-users"></i>
                            </div>
                            <div class="stat-content">
                                <h3><?php echo $active_members; ?></h3>
                                <p>Active Members</p>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Book Management -->
                <section id="books" class="content-section dashboard-section<?= $section==='books'?' active':'' ?>" data-section="books">
                    <h2>Book Management <button onclick="window.print()" class="btn btn-sm btn-outline-secondary no-print"><i class="fas fa-print"></i> Print Catalog</button></h2>
                    <div class="book-actions">
                        <button class="btn btn-primary" onclick="openModal('addBook')">
                            <i class="fas fa-plus"></i> Add New Book
                        </button>
                        <button class="btn btn-success" onclick="openModal('bookSearch')">
                            <i class="fas fa-search"></i> Search Books
                        </button>
                        <button class="btn btn-info" onclick="openModal('bookInventory')">
                            <i class="fas fa-list"></i> Book Inventory
                        </button>
                        <button class="btn btn-warning" onclick="openModal('bookMaintenance')">
                            <i class="fas fa-tools"></i> Book Maintenance
                        </button>
                    </div>
                    
                    <div class="books-overview">
                        <h3>Book Categories</h3>
                        <div class="categories-grid">
                            <?php $ci=0; foreach ($book_categories as $bc): $ci++; $catName = $bc['category'] ?? $book_category_names[$ci-1] ?? 'Category'; $total = (int)($bc['total']??0); $avail = (int)($bc['available']??0); $borrowed = $total - $avail; ?>
                            <div class="category-card">
                                <div class="category-header">
                                    <h4><?= htmlspecialchars($catName) ?></h4>
                                    <span class="book-count"><?= $total ?> book<?= $total!==1?'s':'' ?></span>
                                </div>
                                <div class="category-stats">
                                    <div class="stat">
                                        <span>Available:</span>
                                        <strong><?= $avail ?></strong>
                                    </div>
                                    <div class="stat">
                                        <span>Borrowed:</span>
                                        <strong><?= max(0,$borrowed) ?></strong>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </section>

                <!-- Circulation -->
                <section id="circulation" class="content-section dashboard-section<?= $section==='circulation'?' active':'' ?>" data-section="circulation">
                    <h2>Book Circulation</h2>
                    <div class="circulation-actions">
                        <button class="btn btn-primary" onclick="openModal('checkoutBook')">
                            <i class="fas fa-hand-holding"></i> Checkout Book
                        </button>
                        <button class="btn btn-success" onclick="openModal('returnBook')">
                            <i class="fas fa-undo"></i> Return Book
                        </button>
                        <button class="btn btn-info" onclick="openModal('renewBook')">
                            <i class="fas fa-sync"></i> Renew Book
                        </button>
                        <button class="btn btn-warning" onclick="openModal('overdueBooks')">
                            <i class="fas fa-exclamation-triangle"></i> Overdue Books
                        </button>
                    </div>
                    
                    <div class="circulation-overview">
                        <h3>Today's Circulation</h3>
                        <div class="circulation-stats">
                            <div class="circ-stat">
                                <h4>Books Checked Out</h4>
                                <div class="count"><?= $checked_out_today ?></div>
                                <small>Today</small>
                            </div>
                            <div class="circ-stat">
                                <h4>Books Returned</h4>
                                <div class="count"><?= $returned_today ?></div>
                                <small>Today</small>
                            </div>
                            <div class="circ-stat">
                                <h4>Books Renewed</h4>
                                <div class="count"><?= $renewed_today ?></div>
                                <small>Today</small>
                            </div>
                            <div class="circ-stat">
                                <h4>Overdue Books</h4>
                                <div class="count"><?= $overdue_books ?></div>
                                <small>Currently</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="recent-circulation">
                        <h3>Recent Transactions</h3>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Transaction ID</th>
                                        <th>Member</th>
                                        <th>Book Title</th>
                                        <th>Type</th>
                                        <th>Date</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($recent_transactions)): ?>
                                    <tr><td colspan="7" class="text-center text-muted py-4">No recent transactions</td></tr>
                                    <?php else: ?>
                                    <?php foreach ($recent_transactions as $trx): $trxId = 'TRX-'.date('Y').'-'.str_pad($trx['id']??0,3,'0',STR_PAD_LEFT); ?>
                                    <tr>
                                        <td><?= $trxId ?></td>
                                        <td><?= htmlspecialchars($trx['member_name'] ?? $trx['member_id'] ?? '-') ?></td>
                                        <td><?= htmlspecialchars($trx['book_title'] ?? '-') ?></td>
                                        <td><span class="transaction-type <?= ($trx['status']??'Borrowed')==='Borrowed'?'checkout':'return' ?>"><?= ($trx['status']??'Borrowed')==='Borrowed'?'Checkout':'Return' ?></span></td>
                                        <td><?= !empty($trx['transaction_date']) ? date('M j, Y', strtotime($trx['transaction_date'])) : '-' ?></td>
                                        <td><span class="status-badge <?= ($trx['status']??'Borrowed')==='Returned'?'completed':'active' ?>"><?= htmlspecialchars($trx['status'] ?? 'Active') ?></span></td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-info">View</button>
                                            <?php if (($trx['status']??'')==='Borrowed'): ?>
                                            <button class="btn btn-sm btn-outline-warning">Return</button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </section>

                <!-- Library Members -->
                <section id="members" class="content-section dashboard-section<?= $section==='members'?' active':'' ?>" data-section="members">
                    <h2>Library Members</h2>
                    <div class="member-actions">
                        <button class="btn btn-primary" onclick="openModal('registerMember')">
                            <i class="fas fa-user-plus"></i> Register Member
                        </button>
                        <button class="btn btn-success" onclick="openModal('memberDirectory')">
                            <i class="fas fa-address-book"></i> Member Directory
                        </button>
                        <button class="btn btn-info" onclick="openModal('memberStatistics')">
                            <i class="fas fa-chart-bar"></i> Member Statistics
                        </button>
                        <button class="btn btn-warning" onclick="openModal('memberCards')">
                            <i class="fas fa-id-card"></i> Library Cards
                        </button>
                    </div>
                    
                    <div class="members-overview">
                        <h3>Member Statistics</h3>
                        <div class="member-stats">
                            <div class="member-stat">
                                <h4>Students</h4>
                                <div class="count"><?= $member_students ?></div>
                                <small>Active members</small>
                            </div>
                            <div class="member-stat">
                                <h4>Staff</h4>
                                <div class="count"><?= $member_staff ?></div>
                                <small>Active members</small>
                            </div>
                            <div class="member-stat">
                                <h4>Faculty</h4>
                                <div class="count"><?= $member_faculty ?></div>
                                <small>Active members</small>
                            </div>
                            <div class="member-stat">
                                <h4>New This Month</h4>
                                <div class="count"><?= $new_members_month ?></div>
                                <small>Registrations</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="recent-members">
                        <h3>Recent Member Registrations</h3>
                        <div class="members-list">
                            <?php if (empty($recent_members)): ?>
                            <div class="text-center text-muted py-3">No member registrations yet</div>
                            <?php else: ?>
                            <?php foreach ($recent_members as $rm): ?>
                            <div class="member-item">
                                <div class="member-header">
                                    <h4><?= htmlspecialchars($rm['student_name'] ?? $rm['full_name'] ?? 'Member') ?></h4>
                                    <span class="member-type student"><?= htmlspecialchars($rm['member_type'] ?? 'Student') ?></span>
                                </div>
                                <div class="member-details">
                                    <div class="detail">
                                        <span>Member ID:</span>
                                        <strong><?= htmlspecialchars($rm['member_id'] ?? '-') ?></strong>
                                    </div>
                                    <div class="detail">
                                        <span>Type:</span>
                                        <strong><?= htmlspecialchars($rm['membership_type'] ?? 'Regular') ?></strong>
                                    </div>
                                    <div class="detail">
                                        <span>Registered:</span>
                                        <strong><?= !empty($rm['registration_date']) ? date('M j, Y', strtotime($rm['registration_date'])) : '-' ?></strong>
                                    </div>
                                    <?php if (!empty($rm['email'])): ?>
                                    <div class="detail">
                                        <span>Email:</span>
                                        <strong><a href="mailto:<?= htmlspecialchars($rm['email']) ?>"><?= htmlspecialchars($rm['email']) ?></a></strong>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                <div class="member-actions">
                                    <button class="btn btn-sm btn-outline-primary">View Profile</button>
                                    <button class="btn btn-sm btn-outline-success">Issue Card</button>
                                </div>
                            </div>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </section>

                <!-- Book Acquisition -->
                <section id="acquisition" class="content-section dashboard-section<?= $section==='acquisition'?' active':'' ?>" data-section="acquisition">
                    <h2>Book Acquisition</h2>
                    <div class="acquisition-actions">
                        <button class="btn btn-primary" onclick="openModal('newAcquisition')">
                            <i class="fas fa-plus"></i> New Acquisition
                        </button>
                        <button class="btn btn-success" onclick="openModal('acquisitionBudget')">
                            <i class="fas fa-money-check"></i> Acquisition Budget
                        </button>
                        <button class="btn btn-info" onclick="openModal('vendorManagement')">
                            <i class="fas fa-truck"></i> Vendor Management
                        </button>
                        <button class="btn btn-warning" onclick="openModal('acquisitionReport')">
                            <i class="fas fa-chart-bar"></i> Acquisition Report
                        </button>
                    </div>
                    
                    <div class="acquisition-overview">
                        <h3>Current Acquisitions</h3>
                        <div class="acquisition-list">
                            <?php if (empty($recent_acquisitions)): ?>
                            <div class="text-center text-muted py-3">No active acquisitions</div>
                            <?php else: ?>
                            <?php foreach ($recent_acquisitions as $acq): ?>
                            <div class="acquisition-item">
                                <div class="acquisition-header">
                                    <h4><?= htmlspecialchars($acq['title'] ?? 'Acquisition') ?></h4>
                                    <span class="status-badge in-progress"><?= htmlspecialchars($acq['status'] ?? 'In Progress') ?></span>
                                </div>
                                <div class="acquisition-details">
                                    <?php if (!empty($acq['quantity'])): ?>
                                    <div class="detail"><span>Books:</span><strong><?= (int)$acq['quantity'] ?> titles</strong></div>
                                    <?php endif; ?>
                                    <?php if (!empty($acq['total_cost'])): ?>
                                    <div class="detail"><span>Cost:</span><strong>UGX <?= number_format((float)$acq['total_cost']) ?></strong></div>
                                    <?php endif; ?>
                                    <?php if (!empty($acq['vendor'])): ?>
                                    <div class="detail"><span>Vendor:</span><strong><?= htmlspecialchars($acq['vendor']) ?></strong></div>
                                    <?php endif; ?>
                                    <?php if (!empty($acq['expected_date'])): ?>
                                    <div class="detail"><span>Expected:</span><strong><?= date('M j, Y', strtotime($acq['expected_date'])) ?></strong></div>
                                    <?php endif; ?>
                                </div>
                                <div class="acquisition-actions">
                                    <button class="btn btn-sm btn-outline-primary">View Details</button>
                                    <button class="btn btn-sm btn-outline-success">Track Order</button>
                                </div>
                            </div>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </section>

                <!-- Recent Activities -->
                <section id="activities" class="activities-section dashboard-section<?= $section==='activities'?' active':'' ?>" data-section="activities">
                    <h2>Recent Library Activities</h2>
                    <div class="activities-list">
                        <?php foreach ($recent_activities as $activity): ?>
                        <div class="activity-item">
                            <div class="activity-icon">
                                <i class="fas fa-<?php echo $activity['icon'] ?? 'check-circle'; ?>"></i>
                            </div>
                            <div class="activity-content">
                                <p><strong><?php echo $activity['user_name'] ?? 'User'; ?></strong> <?php echo $activity['action'] ?? $activity['activity'] ?? 'Activity'; ?></p>
                                <small><?php echo date('M j, Y H:i', strtotime($activity['created_at'])); ?></small>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </section>
    </div>

    <!-- Modals -->
    <div class="modal fade" id="actionModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Action</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="modalBody">
                    <!-- Dynamic content -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="modalAction">Save</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const CSRF_TOKEN = '<?= $_SESSION["csrf_token"] ?? "" ?>';
        const LIB_DB = '<?= $students_db_name ?>';

        function showNotice(msg, type) {
            var d = document.createElement('div');
            d.className = 'alert alert-' + (type || 'success') + ' position-fixed top-0 start-50 translate-middle-x mt-3';
            d.style.zIndex = 99999;
            d.textContent = msg;
            document.body.appendChild(d);
            setTimeout(function() { d.remove(); }, 3500);
        }

        function updateDateTime() {
            const now = new Date();
            const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
            var el = document.getElementById('currentDate');
            if (el) el.textContent = now.toLocaleDateString('en-US', options);
        }
        updateDateTime();
        setInterval(updateDateTime, 60000);

        document.querySelectorAll('.dashboard-sidebar .nav-link').forEach(function(link) {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                document.querySelectorAll('.dashboard-sidebar .nav-link').forEach(function(l) { l.classList.remove('active'); });
                this.classList.add('active');
                var targetId = this.getAttribute('href').substring(1);
                document.querySelectorAll('.content-section').forEach(function(s) { s.style.display = 'none'; });
                var target = document.getElementById(targetId);
                if (target) target.style.display = 'block';
            });
        });

        function apiPost(module, action, data, callback) {
            fetch('../module_handler.php?action=' + action + '&module=' + module, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN },
                body: JSON.stringify(data)
            })
            .then(function(r) { return r.json(); })
            .then(function(res) { callback(res); })
            .catch(function(err) { showNotice('Network error: ' + err.message, 'danger'); });
        }

        function openModal(action) {
            var modal = new bootstrap.Modal(document.getElementById('actionModal'));
            var title = document.getElementById('modalTitle');
            var body = document.getElementById('modalBody');
            var saveBtn = document.getElementById('modalAction');
            saveBtn.onclick = null;

            switch(action) {
                case 'addBook':
                    title.textContent = 'Add New Book';
                    body.innerHTML = '<form id="frmAddBook">' +
                        '<div class="mb-3"><label class="form-label">Book Title *</label><input type="text" class="form-control" name="title" required></div>' +
                        '<div class="row"><div class="col-md-6 mb-3"><label class="form-label">Author *</label><input type="text" class="form-control" name="author" required></div>' +
                        '<div class="col-md-6 mb-3"><label class="form-label">ISBN</label><input type="text" class="form-control" name="isbn"></div></div>' +
                        '<div class="row"><div class="col-md-6 mb-3"><label class="form-label">Category *</label><select class="form-select" name="category" required><option value="">Select</option><option value="Nursing">Nursing</option><option value="Midwifery">Midwifery</option><option value="Medical Sciences">Medical Sciences</option><option value="General Education">General Education</option></select></div>' +
                        '<div class="col-md-6 mb-3"><label class="form-label">Subcategory</label><input type="text" class="form-control" name="subcategory"></div></div>' +
                        '<div class="row"><div class="col-md-4 mb-3"><label class="form-label">Year</label><input type="number" class="form-control" name="publication_year"></div>' +
                        '<div class="col-md-4 mb-3"><label class="form-label">Pages</label><input type="number" class="form-control" name="pages"></div>' +
                        '<div class="col-md-4 mb-3"><label class="form-label">Edition</label><input type="text" class="form-control" name="edition"></div></div>' +
                        '<div class="row"><div class="col-md-6 mb-3"><label class="form-label">Publisher</label><input type="text" class="form-control" name="publisher"></div>' +
                        '<div class="col-md-6 mb-3"><label class="form-label">Language</label><input type="text" class="form-control" name="language" value="English"></div></div>' +
                        '<div class="row"><div class="col-md-6 mb-3"><label class="form-label">Location/Shelf</label><input type="text" class="form-control" name="location"></div>' +
                        '<div class="col-md-6 mb-3"><label class="form-label">Call Number</label><input type="text" class="form-control" name="call_number"></div></div>' +
                        '<div class="mb-3"><label class="form-label">Description</label><textarea class="form-control" name="description" rows="2"></textarea></div>' +
                        '</form>';
                    saveBtn.onclick = function() {
                        var frm = document.getElementById('frmAddBook');
                        var fd = new FormData(frm);
                        var data = {};
                        fd.forEach(function(v, k) { if (v) data[k] = v; });
                        data.status = 'Available';
                        apiPost('library_books', 'create', data, function(res) {
                            if (res.success) { showNotice('Book added successfully'); setTimeout(function() { location.reload(); }, 800); }
                            else showNotice(res.error || 'Failed to add book', 'danger');
                        });
                    };
                    break;

                case 'checkoutBook':
                    title.textContent = 'Checkout Book';
                    body.innerHTML = '<form id="frmCheckout">' +
                        '<div class="mb-3"><label class="form-label">Member ID *</label><input type="text" class="form-control" name="member_id" required placeholder="e.g. LIB-2026-001"></div>' +
                        '<div class="mb-3"><label class="form-label">Book ID *</label><input type="number" class="form-control" name="book_id" required placeholder="Enter book ID"></div>' +
                        '<div class="mb-3"><label class="form-label">Book Title *</label><input type="text" class="form-control" name="book_title" required></div>' +
                        '<div class="row"><div class="col-md-6 mb-3"><label class="form-label">Borrow Date *</label><input type="date" class="form-control" name="borrow_date" value="<?= date("Y-m-d") ?>" required></div>' +
                        '<div class="col-md-6 mb-3"><label class="form-label">Due Date *</label><input type="date" class="form-control" name="due_date" value="<?= date("Y-m-d", strtotime("+14 days")) ?>" required></div></div>' +
                        '<div class="mb-3"><label class="form-label">Notes</label><textarea class="form-control" name="notes" rows="2"></textarea></div>' +
                        '</form>';
                    saveBtn.onclick = function() {
                        var frm = document.getElementById('frmCheckout');
                        var fd = new FormData(frm);
                        var data = {};
                        fd.forEach(function(v, k) { if (v) data[k] = v; });
                        data.return_status = 'Borrowed';
                        apiPost('library_borrowing', 'create', data, function(res) {
                            if (res.success) {
                                apiPost('library_books', 'update', { id: data.book_id, status: 'Borrowed' }, function() {});
                                showNotice('Book checked out successfully');
                                setTimeout(function() { location.reload(); }, 800);
                            } else showNotice(res.error || 'Failed to checkout', 'danger');
                        });
                    };
                    break;

                case 'returnBook':
                    title.textContent = 'Return Book';
                    body.innerHTML = '<form id="frmReturn">' +
                        '<div class="mb-3"><label class="form-label">Borrowing Record ID *</label><input type="number" class="form-control" name="borrow_id" required placeholder="Enter borrowing record ID"></div>' +
                        '<div class="mb-3"><label class="form-label">Book ID *</label><input type="number" class="form-control" name="book_id" required placeholder="Enter book ID"></div>' +
                        '<div class="mb-3"><label class="form-label">Return Date *</label><input type="date" class="form-control" name="return_date" value="<?= date("Y-m-d") ?>" required></div>' +
                        '</form>';
                    saveBtn.onclick = function() {
                        var frm = document.getElementById('frmReturn');
                        var fd = new FormData(frm);
                        var data = {};
                        fd.forEach(function(v, k) { if (v) data[k] = v; });
                        data.return_status = 'Returned';
                        apiPost('library_borrowing', 'update', { id: data.borrow_id, return_status: 'Returned', return_date: data.return_date }, function(res) {
                            if (res.success) {
                                apiPost('library_books', 'update', { id: data.book_id, status: 'Available' }, function() {});
                                showNotice('Book returned successfully');
                                setTimeout(function() { location.reload(); }, 800);
                            } else showNotice(res.error || 'Failed to return', 'danger');
                        });
                    };
                    break;

                case 'renewBook':
                    title.textContent = 'Renew Book';
                    body.innerHTML = '<form id="frmRenew">' +
                        '<div class="mb-3"><label class="form-label">Borrowing Record ID *</label><input type="number" class="form-control" name="borrow_id" required></div>' +
                        '<div class="mb-3"><label class="form-label">New Due Date *</label><input type="date" class="form-control" name="new_due_date" value="<?= date("Y-m-d", strtotime("+14 days")) ?>" required></div>' +
                        '</form>';
                    saveBtn.onclick = function() {
                        var frm = document.getElementById('frmRenew');
                        var fd = new FormData(frm);
                        var data = {};
                        fd.forEach(function(v, k) { if (v) data[k] = v; });
                        apiPost('library_borrowing', 'update', { id: data.borrow_id, due_date: data.new_due_date }, function(res) {
                            if (res.success) { showNotice('Book renewed successfully'); setTimeout(function() { location.reload(); }, 800); }
                            else showNotice(res.error || 'Failed to renew', 'danger');
                        });
                    };
                    break;

                case 'registerMember':
                    title.textContent = 'Register New Library Member';
                    body.innerHTML = '<form id="frmRegisterMember">' +
                        '<div class="row"><div class="col-md-6 mb-3"><label class="form-label">First Name *</label><input type="text" class="form-control" name="first_name" required></div>' +
                        '<div class="col-md-6 mb-3"><label class="form-label">Surname *</label><input type="text" class="form-control" name="surname" required></div></div>' +
                        '<div class="row"><div class="col-md-6 mb-3"><label class="form-label">Email *</label><input type="email" class="form-control" name="email" required></div>' +
                        '<div class="col-md-6 mb-3"><label class="form-label">Phone</label><input type="tel" class="form-control" name="phone"></div></div>' +
                        '<div class="row"><div class="col-md-6 mb-3"><label class="form-label">Member Type *</label><select class="form-select" name="member_type" required><option value="">Select</option><option value="Student">Student</option><option value="Staff">Staff</option><option value="Faculty">Faculty</option></select></div>' +
                        '<div class="col-md-6 mb-3"><label class="form-label">Membership *</label><select class="form-select" name="membership_type" required><option value="Regular">Regular (1 year)</option><option value="Student">Student (6 months)</option></select></div></div>' +
                        '<div class="mb-3"><label class="form-label">Address</label><textarea class="form-control" name="address" rows="2"></textarea></div>' +
                        '</form>';
                    saveBtn.onclick = function() {
                        var frm = document.getElementById('frmRegisterMember');
                        var fd = new FormData(frm);
                        var data = {};
                        fd.forEach(function(v, k) { if (v) data[k] = v; });
                        var name = (data.first_name || '') + ' ' + (data.surname || '');
                        data.full_name = name.trim();
                        var count = Math.floor(Math.random() * 900) + 100;
                        data.member_id = 'LIB-' + new Date().getFullYear() + '-' + String(count).padStart(3, '0');
                        apiPost('library_members', 'create', data, function(res) {
                            if (res.success) { showNotice('Member registered successfully'); setTimeout(function() { location.reload(); }, 800); }
                            else showNotice(res.error || 'Failed to register', 'danger');
                        });
                    };
                    break;

                case 'bookSearch':
                    title.textContent = 'Search Books';
                    body.innerHTML = '<form id="frmBookSearch"><div class="mb-3"><input type="text" class="form-control" id="bookSearchQuery" placeholder="Search by title, author, or ISBN..." onkeyup="doBookSearch(this.value)"></div><div id="bookSearchResults"></div></form>';
                    break;

                case 'newAcquisition':
                    title.textContent = 'New Book Acquisition';
                    body.innerHTML = '<form id="frmAcquisition">' +
                        '<div class="mb-3"><label class="form-label">Book Title *</label><input type="text" class="form-control" name="title" required></div>' +
                        '<div class="row"><div class="col-md-6 mb-3"><label class="form-label">Author</label><input type="text" class="form-control" name="author"></div>' +
                        '<div class="col-md-6 mb-3"><label class="form-label">ISBN</label><input type="text" class="form-control" name="isbn"></div></div>' +
                        '<div class="row"><div class="col-md-4 mb-3"><label class="form-label">Quantity</label><input type="number" class="form-control" name="quantity" value="1"></div>' +
                        '<div class="col-md-4 mb-3"><label class="form-label">Unit Cost (UGX)</label><input type="number" class="form-control" name="unit_cost" step="0.01"></div>' +
                        '<div class="col-md-4 mb-3"><label class="form-label">Vendor</label><input type="text" class="form-control" name="vendor"></div></div>' +
                        '<div class="row"><div class="col-md-6 mb-3"><label class="form-label">Expected Date</label><input type="date" class="form-control" name="expected_date"></div></div>' +
                        '<div class="mb-3"><label class="form-label">Notes</label><textarea class="form-control" name="notes" rows="2"></textarea></div>' +
                        '</form>';
                    saveBtn.onclick = function() {
                        var frm = document.getElementById('frmAcquisition');
                        var fd = new FormData(frm);
                        var data = {};
                        fd.forEach(function(v, k) { if (v) data[k] = v; });
                        data.status = 'Requested';
                        data.request_date = new Date().toISOString().split('T')[0];
                        if (data.unit_cost && data.quantity) data.total_cost = (parseFloat(data.unit_cost) * parseInt(data.quantity)).toFixed(2);
                        apiPost('library_acquisitions', 'create', data, function(res) {
                            if (res.success) { showNotice('Acquisition request submitted'); setTimeout(function() { location.reload(); }, 800); }
                            else showNotice(res.error || 'Failed to submit', 'danger');
                        });
                    };
                    break;

                case 'bookInventory':
                    title.textContent = 'Book Inventory Management';
                    var totalB = '<?= $total_books ?>', availB = '<?= $available_books ?>', borrowedB = '<?= $borrowed_books ?>';
                    body.innerHTML = '<div class="row mb-3">' +
                        '<div class="col-md-4"><div class="card text-center p-3"><h5>' + totalB + '</h5><small>Total Books</small></div></div>' +
                        '<div class="col-md-4"><div class="card text-center p-3 bg-success text-white"><h5>' + availB + '</h5><small>Available</small></div></div>' +
                        '<div class="col-md-4"><div class="card text-center p-3 bg-warning text-white"><h5>' + borrowedB + '</h5><small>Borrowed</small></div></div>' +
                        '</div>' +
                        '<hr><h6>Update Stock Count</h6>' +
                        '<form id="frmInventory">' +
                        '<div class="row"><div class="col-md-6 mb-3"><label class="form-label">Book ID *</label><input type="number" class="form-control" name="book_id" required></div>' +
                        '<div class="col-md-6 mb-3"><label class="form-label">New Status</label><select class="form-select" name="status"><option value="Available">Available</option><option value="Borrowed">Borrowed</option><option value="Reserved">Reserved</option><option value="Lost">Lost</option><option value="Damaged">Damaged</option></select></div></div>' +
                        '<div class="mb-3"><label class="form-label">Notes</label><textarea class="form-control" name="notes" rows="2" placeholder="Reason for stock update..."></textarea></div>' +
                        '</form>';
                    saveBtn.textContent = 'Update Stock';
                    saveBtn.onclick = function() {
                        var frm = document.getElementById('frmInventory');
                        var fd = new FormData(frm);
                        var data = {};
                        fd.forEach(function(v, k) { if (v) data[k] = v; });
                        apiPost('library_books', 'update', { id: data.book_id, status: data.status }, function(res) {
                            if (res.success) { showNotice('Stock updated successfully'); setTimeout(function() { location.reload(); }, 800); }
                            else showNotice(res.error || 'Failed to update stock', 'danger');
                        });
                    };
                    break;

                case 'bookMaintenance':
                    title.textContent = 'Book Maintenance';
                    body.innerHTML = '<form id="frmMaintenance">' +
                        '<div class="mb-3"><label class="form-label">Book ID *</label><input type="number" class="form-control" name="book_id" required placeholder="Enter book ID"></div>' +
                        '<div class="row"><div class="col-md-6 mb-3"><label class="form-label">Maintenance Type *</label><select class="form-select" name="maintenance_type" required><option value="">Select</option><option value="repair">Repair</option><option value="replace">Replace</option><option value="withdraw">Withdraw</option></select></div>' +
                        '<div class="col-md-6 mb-3"><label class="form-label">Date *</label><input type="date" class="form-control" name="maintenance_date" value="<?= date("Y-m-d") ?>" required></div></div>' +
                        '<div class="mb-3"><label class="form-label">Notes</label><textarea class="form-control" name="notes" rows="3" placeholder="Describe the maintenance needed..."></textarea></div>' +
                        '</form>';
                    saveBtn.textContent = 'Log Maintenance';
                    saveBtn.onclick = function() {
                        var frm = document.getElementById('frmMaintenance');
                        var fd = new FormData(frm);
                        var data = {};
                        fd.forEach(function(v, k) { if (v) data[k] = v; });
                        data.status = 'Pending';
                        apiPost('library_maintenance', 'create', data, function(res) {
                            if (res.success) { showNotice('Maintenance record created'); setTimeout(function() { location.reload(); }, 800); }
                            else showNotice(res.error || 'Failed to log maintenance', 'danger');
                        });
                    };
                    break;

                case 'overdueBooks':
                    title.textContent = 'Overdue Books';
                    body.innerHTML = '<div id="overdueContent"><p class="text-muted">Loading overdue books...</p></div>';
                    fetch('../module_handler.php?action=search&module=library_borrowing&q=overdue')
                        .then(function(r) { return r.json(); })
                        .then(function(res) {
                            var el = document.getElementById('overdueContent');
                            if (!res.results || res.results.length === 0) { el.innerHTML = '<div class="alert alert-success">No overdue books found</div>'; return; }
                            var html = '<div class="table-responsive"><table class="table table-sm table-hover"><thead><tr><th>ID</th><th>Member</th><th>Book</th><th>Due Date</th><th>Days Overdue</th></tr></thead><tbody>';
                            var today = new Date();
                            res.results.forEach(function(r) {
                                var due = new Date(r.due_date);
                                var days = Math.floor((today - due) / 86400000);
                                html += '<tr><td>' + (r.id||'') + '</td><td>' + (r.member_id||'') + '</td><td>' + (r.book_title||'') + '</td><td>' + (r.due_date||'') + '</td><td><span class="badge bg-danger">' + days + ' days</span></td></tr>';
                            });
                            html += '</tbody></table></div>';
                            el.innerHTML = html;
                        })
                        .catch(function() { document.getElementById('overdueContent').innerHTML = '<div class="alert alert-warning">Could not load overdue books</div>'; });
                    saveBtn.style.display = 'none';
                    break;

                case 'memberDirectory':
                    title.textContent = 'Member Directory';
                    body.innerHTML = '<div class="mb-3"><input type="text" class="form-control" id="memberDirSearch" placeholder="Search by name, member ID, or email..." onkeyup="doMemberSearch(this.value)"></div><div id="memberDirResults"><p class="text-muted">Type at least 2 characters to search</p></div>';
                    saveBtn.style.display = 'none';
                    break;

                case 'memberStatistics':
                    title.textContent = 'Member Statistics';
                    var mStudents = '<?= $member_students ?>', mStaff = '<?= $member_staff ?>', mFaculty = '<?= $member_faculty ?>', mNew = '<?= $new_members_month ?>';
                    body.innerHTML = '<div class="row mb-3">' +
                        '<div class="col-md-3"><div class="card text-center p-3"><h4>' + mStudents + '</h4><small>Student Members</small></div></div>' +
                        '<div class="col-md-3"><div class="card text-center p-3"><h4>' + mStaff + '</h4><small>Staff Members</small></div></div>' +
                        '<div class="col-md-3"><div class="card text-center p-3"><h4>' + mFaculty + '</h4><small>Faculty Members</small></div></div>' +
                        '<div class="col-md-3"><div class="card text-center p-3 bg-info text-white"><h4>' + mNew + '</h4><small>New This Month</small></div></div>' +
                        '</div>' +
                        '<h6>Membership Breakdown</h6>' +
                        '<div class="progress mb-2" style="height:25px;">' +
                        '<div class="progress-bar bg-primary" style="width:' + (parseInt(mStudents)||0) + '%">' + mStudents + ' Students</div>' +
                        '<div class="progress-bar bg-success" style="width:' + (parseInt(mStaff)||0) + '%">' + mStaff + ' Staff</div>' +
                        '<div class="progress-bar bg-warning" style="width:' + (parseInt(mFaculty)||0) + '%">' + mFaculty + ' Faculty</div>' +
                        '</div>';
                    saveBtn.style.display = 'none';
                    break;

                case 'memberCards':
                    title.textContent = 'Generate Library Card';
                    body.innerHTML = '<form id="frmMemberCard">' +
                        '<div class="mb-3"><label class="form-label">Member ID *</label><input type="text" class="form-control" name="member_id" required placeholder="e.g. LIB-2026-001"></div>' +
                        '<div class="mb-3"><label class="form-label">Full Name *</label><input type="text" class="form-control" name="full_name" required></div>' +
                        '<div class="row"><div class="col-md-6 mb-3"><label class="form-label">Member Type</label><select class="form-select" name="member_type"><option value="Student">Student</option><option value="Staff">Staff</option><option value="Faculty">Faculty</option></select></div>' +
                        '<div class="col-md-6 mb-3"><label class="form-label">Expiry Date</label><input type="date" class="form-control" name="expiry_date" value="<?= date("Y-m-d", strtotime("+1 year")) ?>"></div></div>' +
                        '</form>';
                    saveBtn.textContent = 'Generate Card';
                    saveBtn.onclick = function() {
                        var frm = document.getElementById('frmMemberCard');
                        var fd = new FormData(frm);
                        var data = {};
                        fd.forEach(function(v, k) { if (v) data[k] = v; });
                        if (!data.member_id || !data.full_name) { showNotice('Please fill required fields', 'danger'); return; }
                        var cardHtml = '<div id="libCard" style="border:2px solid #333;border-radius:10px;padding:20px;max-width:400px;margin:10px auto;background:#f8f9fa;">' +
                            '<div style="text-align:center;border-bottom:2px solid #333;padding-bottom:10px;margin-bottom:10px;"><h4 style="margin:0;">School Library</h4><small>Member Card</small></div>' +
                            '<p><strong>Member ID:</strong> ' + data.member_id + '</p>' +
                            '<p><strong>Name:</strong> ' + data.full_name + '</p>' +
                            '<p><strong>Type:</strong> ' + (data.member_type || 'Student') + '</p>' +
                            '<p><strong>Valid Until:</strong> ' + (data.expiry_date || 'N/A') + '</p>' +
                            '<div style="text-align:center;margin-top:10px;font-size:11px;color:#666;">Present this card at the library checkout desk</div>' +
                            '</div>';
                        var existing = document.getElementById('libCard');
                        if (existing) existing.remove();
                        frm.insertAdjacentHTML('afterend', cardHtml);
                        showNotice('Library card generated', 'info');
                    };
                    break;

                case 'acquisitionBudget':
                    title.textContent = 'Acquisition Budget';
                    body.innerHTML = '<form id="frmBudget">' +
                        '<div class="row"><div class="col-md-6 mb-3"><label class="form-label">Fiscal Year *</label><input type="text" class="form-control" name="fiscal_year" value="<?= date("Y") ?>" required placeholder="e.g. 2026"></div>' +
                        '<div class="col-md-6 mb-3"><label class="form-label">Allocated Budget (UGX) *</label><input type="number" class="form-control" name="allocated_budget" step="0.01" required></div></div>' +
                        '<div class="row"><div class="col-md-6 mb-3"><label class="form-label">Amount Spent (UGX)</label><input type="number" class="form-control" name="spent" step="0.01" value="0"></div></div>' +
                        '<div class="mb-3"><label class="form-label">Notes</label><textarea class="form-control" name="notes" rows="2" placeholder="Budget allocation notes..."></textarea></div>' +
                        '</form>';
                    saveBtn.textContent = 'Save Budget';
                    saveBtn.onclick = function() {
                        var frm = document.getElementById('frmBudget');
                        var fd = new FormData(frm);
                        var data = {};
                        fd.forEach(function(v, k) { if (v) data[k] = v; });
                        apiPost('library_budget', 'create', data, function(res) {
                            if (res.success) { showNotice('Budget saved successfully'); setTimeout(function() { location.reload(); }, 800); }
                            else showNotice(res.error || 'Failed to save budget', 'danger');
                        });
                    };
                    break;

                case 'vendorManagement':
                    title.textContent = 'Vendor Management';
                    body.innerHTML = '<form id="frmVendor">' +
                        '<div class="mb-3"><label class="form-label">Vendor Name *</label><input type="text" class="form-control" name="vendor_name" required placeholder="Enter vendor name"></div>' +
                        '<div class="row"><div class="col-md-6 mb-3"><label class="form-label">Contact Person</label><input type="text" class="form-control" name="contact_person"></div>' +
                        '<div class="col-md-6 mb-3"><label class="form-label">Email</label><input type="email" class="form-control" name="email"></div></div>' +
                        '<div class="row"><div class="col-md-6 mb-3"><label class="form-label">Phone</label><input type="tel" class="form-control" name="phone"></div>' +
                        '<div class="col-md-6 mb-3"><label class="form-label">Status</label><select class="form-select" name="status"><option value="Active">Active</option><option value="Inactive">Inactive</option></select></div></div>' +
                        '<div class="mb-3"><label class="form-label">Address</label><textarea class="form-control" name="address" rows="2"></textarea></div>' +
                        '</form>';
                    saveBtn.textContent = 'Save Vendor';
                    saveBtn.onclick = function() {
                        var frm = document.getElementById('frmVendor');
                        var fd = new FormData(frm);
                        var data = {};
                        fd.forEach(function(v, k) { if (v) data[k] = v; });
                        apiPost('library_vendors', 'create', data, function(res) {
                            if (res.success) { showNotice('Vendor saved successfully'); setTimeout(function() { location.reload(); }, 800); }
                            else showNotice(res.error || 'Failed to save vendor', 'danger');
                        });
                    };
                    break;

                case 'acquisitionReport':
                    title.textContent = 'Acquisition Report';
                    body.innerHTML = '<div id="acqReportContent"><p class="text-muted">Loading report...</p></div>';
                    fetch('../module_handler.php?action=search&module=library_acquisitions&q=')
                        .then(function(r) { return r.json(); })
                        .then(function(res) {
                            var el = document.getElementById('acqReportContent');
                            var items = res.results || [];
                            var totalSpent = 0, totalQty = 0, byCategory = {};
                            items.forEach(function(i) {
                                totalSpent += parseFloat(i.total_cost || 0);
                                totalQty += parseInt(i.quantity || 0);
                                var v = i.vendor || 'Unknown';
                                if (!byCategory[v]) byCategory[v] = { count: 0, cost: 0 };
                                byCategory[v].count += parseInt(i.quantity || 0);
                                byCategory[v].cost += parseFloat(i.total_cost || 0);
                            });
                            var html = '<div class="row mb-3">' +
                                '<div class="col-md-4"><div class="card text-center p-3"><h5>' + items.length + '</h5><small>Total Requests</small></div></div>' +
                                '<div class="col-md-4"><div class="card text-center p-3"><h5>' + totalQty + '</h5><small>Books Ordered</small></div></div>' +
                                '<div class="col-md-4"><div class="card text-center p-3 bg-success text-white"><h5>UGX ' + totalSpent.toLocaleString() + '</h5><small>Total Spending</small></div></div>' +
                                '</div><h6>Spending by Vendor</h6>';
                            var keys = Object.keys(byCategory);
                            if (keys.length === 0) {
                                html += '<p class="text-muted">No acquisition data available</p>';
                            } else {
                                html += '<div class="table-responsive"><table class="table table-sm"><thead><tr><th>Vendor</th><th>Items</th><th>Total Cost</th></tr></thead><tbody>';
                                keys.forEach(function(k) {
                                    html += '<tr><td>' + k + '</td><td>' + byCategory[k].count + '</td><td>UGX ' + byCategory[k].cost.toLocaleString() + '</td></tr>';
                                });
                                html += '</tbody></table></div>';
                            }
                            el.innerHTML = html;
                        })
                        .catch(function() { document.getElementById('acqReportContent').innerHTML = '<div class="alert alert-warning">Could not load report data</div>'; });
                    saveBtn.style.display = 'none';
                    break;
            }
            modal.show();
        }

        function doBookSearch(q) {
            var el = document.getElementById('bookSearchResults');
            if (!el) return;
            if (q.length < 2) { el.innerHTML = ''; return; }
            fetch('../module_handler.php?action=search&module=library_books&q=' + encodeURIComponent(q))
                .then(function(r) { return r.json(); })
                .then(function(res) {
                    if (!res.results || res.results.length === 0) { el.innerHTML = '<p class="text-muted">No books found</p>'; return; }
                    var html = '<div class="table-responsive"><table class="table table-sm"><thead><tr><th>ID</th><th>Title</th><th>Author</th><th>Category</th><th>Status</th></tr></thead><tbody>';
                    res.results.forEach(function(b) {
                        html += '<tr><td>' + (b.id||'') + '</td><td>' + (b.title||'') + '</td><td>' + (b.author||'') + '</td><td>' + (b.category||'') + '</td><td><span class="badge bg-' + (b.status==='Available'?'success':'warning') + '">' + (b.status||'') + '</span></td></tr>';
                    });
                    html += '</tbody></table></div>';
                    el.innerHTML = html;
                });
        }

        function doMemberSearch(q) {
            var el = document.getElementById('memberDirResults');
            if (!el) return;
            if (q.length < 2) { el.innerHTML = '<p class="text-muted">Type at least 2 characters to search</p>'; return; }
            fetch('../module_handler.php?action=search&module=library_members&q=' + encodeURIComponent(q))
                .then(function(r) { return r.json(); })
                .then(function(res) {
                    if (!res.results || res.results.length === 0) { el.innerHTML = '<p class="text-muted">No members found</p>'; return; }
                    var html = '<div class="table-responsive"><table class="table table-sm table-hover"><thead><tr><th>Member ID</th><th>Name</th><th>Type</th><th>Email</th><th>Status</th></tr></thead><tbody>';
                    res.results.forEach(function(m) {
                        html += '<tr><td>' + (m.member_id||'') + '</td><td>' + (m.full_name||'') + '</td><td>' + (m.member_type||'') + '</td><td>' + (m.email||'') + '</td><td><span class="badge bg-' + (m.status==='Active'?'success':'secondary') + '">' + (m.status||'') + '</span></td></tr>';
                    });
                    html += '</tbody></table></div>';
                    el.innerHTML = html;
                });
        }
    </script>

<!-- â•â•â• AJAX MODULE LOADING â•â•â• -->
<div id="ajaxLoadingOverlay" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(255,255,255,.7);z-index:9999;align-items:center;justify-content:center;">
  <div style="text-align:center;padding:30px;background:#fff;border-radius:14px;box-shadow:0 8px 30px rgba(0,0,0,.12);">
    <i class="fas fa-spinner fa-spin" style="font-size:28px;color:#3b82f6;"></i>
    <p style="margin:12px 0 0;font-size:13px;color:#64748b;">Loading module...</p>
  </div>
</div>
<script>
(function(){
    var contentArea = document.querySelector('.lib-content');
    var loadingOverlay = document.getElementById('ajaxLoadingOverlay');
    var isAjaxLoading = false;

    function showLoading() { if (loadingOverlay) loadingOverlay.style.display = 'flex'; isAjaxLoading = true; }
    function hideLoading() { if (loadingOverlay) loadingOverlay.style.display = 'none'; isAjaxLoading = false; }

    document.querySelectorAll('.child-link').forEach(function(link) {
        link.addEventListener('click', function(e) {
            var href = this.getAttribute('href');
            if (!href || href.indexOf('?') === -1) return;
            if (isAjaxLoading) return;

            e.preventDefault();
            showLoading();
            history.pushState({}, '', href);
            document.querySelectorAll('.child-link').forEach(function(l) { l.classList.remove('active'); });
            this.classList.add('active');

            var section = href.split('section=')[1] || href.split('page=')[1] || 'overview';
            fetch('school-librarian.php?section=' + encodeURIComponent(section), {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(function(r) { return r.text(); })
            .then(function(html) {
                var parser = new DOMParser();
                var doc = parser.parseFromString(html, 'text/html');
                var newContent = doc.querySelector('.lib-content');
                if (newContent && contentArea) {
                    contentArea.innerHTML = newContent.innerHTML;
                    contentArea.querySelectorAll('script').forEach(function(oldScript) {
                        var newScript = document.createElement('script');
                        if (oldScript.src) { newScript.src = oldScript.src; }
                        else { newScript.textContent = oldScript.textContent; }
                        oldScript.parentNode.replaceChild(newScript, oldScript);
                    });
                }
                hideLoading();
            })
            .catch(function(err) {
                console.error('[AJAX Load Error]', err);
                hideLoading();
                window.location.href = href;
            });
        });
    });

    window.addEventListener('popstate', function() { window.location.reload(); });

    document.querySelectorAll('.child-link').forEach(function(link) {
        link.addEventListener('click', function() {
            if (window.innerWidth <= 768) {
                var sidebar = document.querySelector('.isnm-sidebar');
                if (sidebar) sidebar.classList.remove('open', 'mobile-show');
            }
        });
    });
})();
</script>

<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
</body>
</html>

