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
        // Update current date/time
        function updateDateTime() {
            const now = new Date();
            const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
            document.getElementById('currentDate').textContent = now.toLocaleDateString('en-US', options);
        }
        updateDateTime();
        setInterval(updateDateTime, 60000);

        // Navigation
        document.querySelectorAll('.dashboard-sidebar .nav-link').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                document.querySelectorAll('.dashboard-sidebar .nav-link').forEach(l => l.classList.remove('active'));
                this.classList.add('active');
                
                const targetId = this.getAttribute('href').substring(1);
                document.querySelectorAll('.content-section').forEach(section => {
                    section.style.display = 'none';
                });
                const targetSection = document.getElementById(targetId);
                if (targetSection) {
                    targetSection.style.display = 'block';
                }
            });
        });

        // Modal functions
        function openModal(action) {
            const modal = new bootstrap.Modal(document.getElementById('actionModal'));
            const modalTitle = document.getElementById('modalTitle');
            const modalBody = document.getElementById('modalBody');
            
            switch(action) {
                case 'addBook':
                    modalTitle.textContent = 'Add New Book';
                    modalBody.innerHTML = `
                        <form>
                            <div class="mb-3">
                                <label class="form-label">Book Title</label>
                                <input type="text" class="form-control" required>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Author</label>
                                        <input type="text" class="form-control" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">ISBN</label>
                                        <input type="text" class="form-control" required>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Category</label>
                                        <select class="form-control" required>
                                            <option value="">Select Category</option>
                                            <option value="nursing">Nursing</option>
                                            <option value="midwifery">Midwifery</option>
                                            <option value="medical">Medical Sciences</option>
                                            <option value="general">General Education</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Subcategory</label>
                                        <input type="text" class="form-control" required>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Publication Year</label>
                                        <input type="number" class="form-control" required>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Edition</label>
                                        <input type="text" class="form-control">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Pages</label>
                                        <input type="number" class="form-control" required>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Publisher</label>
                                        <input type="text" class="form-control" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Language</label>
                                        <input type="text" class="form-control" value="English" required>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Location</label>
                                        <select class="form-control" required>
                                            <option value="">Select Location</option>
                                            <option value="shelf-1">Shelf 1</option>
                                            <option value="shelf-2">Shelf 2</option>
                                            <option value="shelf-3">Shelf 3</option>
                                            <option value="shelf-4">Shelf 4</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Call Number</label>
                                        <input type="text" class="form-control" required>
                                    </div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Description</label>
                                <textarea class="form-control" rows="3"></textarea>
                            </div>
                        </form>
                    `;
                    break;
                case 'checkoutBook':
                    modalTitle.textContent = 'Checkout Book';
                    modalBody.innerHTML = `
                        <form>
                            <div class="mb-3">
                                <label class="form-label">Member ID</label>
                                <input type="text" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Book Title or ISBN</label>
                                <input type="text" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Due Date</label>
                                <input type="date" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Special Notes</label>
                                <textarea class="form-control" rows="2"></textarea>
                            </div>
                        </form>
                    `;
                    break;
                case 'registerMember':
                    modalTitle.textContent = 'Register New Library Member';
                    modalBody.innerHTML = `
                        <form>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">First Name</label>
                                        <input type="text" class="form-control" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Surname</label>
                                        <input type="text" class="form-control" required>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Email</label>
                                        <input type="email" class="form-control" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Phone</label>
                                        <input type="tel" class="form-control" required>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Member Type</label>
                                        <select class="form-control" required>
                                            <option value="">Select Type</option>
                                            <option value="student">Student</option>
                                            <option value="staff">Staff</option>
                                            <option value="faculty">Faculty</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Program/Department</label>
                                        <input type="text" class="form-control" required>
                                    </div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Address</label>
                                <textarea class="form-control" rows="2" required></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Membership Type</label>
                                <select class="form-control" required>
                                    <option value="">Select Membership</option>
                                    <option value="regular">Regular (1 year)</option>
                                    <option value="student">Student (6 months)</option>
                                </select>
                            </div>
                        </form>
                    `;
                    break;
                // Add more cases as needed
            }
            
            modal.show();
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

