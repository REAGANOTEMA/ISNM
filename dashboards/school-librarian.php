<?php
session_start();
// Check if user is logged in and has School Librarian role
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'School Librarian') {
    header('Location: ../login.php');
    exit();
}

// Database connection
$conn = new mysqli('localhost', 'root', '', 'isnm_school');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get user information
$user_id = $_SESSION['user_id'];
$user_query = "SELECT * FROM users WHERE id = $user_id";
$user_result = $conn->query($user_query);
$user = $user_result->fetch_assoc();

// Get library statistics
$total_books = $conn->query("SELECT COUNT(*) as count FROM library_books")->fetch_assoc()['count'];
$available_books = $conn->query("SELECT COUNT(*) as count FROM library_books WHERE status = 'available'")->fetch_assoc()['count'];
$borrowed_books = $conn->query("SELECT COUNT(*) as count FROM library_books WHERE status = 'borrowed'")->fetch_assoc()['count'];
$active_members = $conn->query("SELECT COUNT(*) as count FROM library_members WHERE status = 'active'")->fetch_assoc()['count'];

// Get recent library activities
$recent_activities = $conn->query("SELECT * FROM library_logs ORDER BY created_at DESC LIMIT 10")->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>School Librarian Dashboard - ISNM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="dashboard-style.css" rel="stylesheet">
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <img src="../images/school-logo.png" alt="ISNM Logo" class="sidebar-logo">
                <h4>School Librarian Dashboard</h4>
                <p><?php echo $user['first_name'] . ' ' . $user['surname']; ?></p>
            </div>
            
            <nav class="sidebar-nav">
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link active" href="#overview">
                            <i class="fas fa-tachometer-alt"></i> Library Overview
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#books">
                            <i class="fas fa-book"></i> Book Management
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#circulation">
                            <i class="fas fa-exchange-alt"></i> Circulation
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#members">
                            <i class="fas fa-users"></i> Library Members
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#acquisition">
                            <i class="fas fa-plus"></i> Book Acquisition
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#catalog">
                            <i class="fas fa-list"></i> Cataloging
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#reports">
                            <i class="fas fa-chart-bar"></i> Library Reports
                        </a>
                    </li>
                </ul>
            </nav>
            
            <div class="sidebar-footer">
                <a href="../logout.php" class="btn btn-danger btn-sm">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Header -->
            <header class="dashboard-header">
                <div class="header-left">
                    <h1>School Librarian Dashboard</h1>
                    <p>Library Management & Resources</p>
                </div>
                <div class="header-right">
                    <div class="date-time">
                        <i class="fas fa-calendar"></i>
                        <span id="currentDate"></span>
                    </div>
                    <div class="user-menu">
                        <img src="../images/default-avatar.png" alt="User" class="user-avatar">
                        <div class="user-dropdown">
                            <span><?php echo $user['first_name']; ?></span>
                            <i class="fas fa-chevron-down"></i>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Dashboard Content -->
            <div class="dashboard-content">
                <!-- Library Overview -->
                <section id="overview" class="content-section">
                    <h2>Library Overview</h2>
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
                <section id="books" class="content-section">
                    <h2>Book Management</h2>
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
                            <div class="category-card">
                                <div class="category-header">
                                    <h4>Nursing</h4>
                                    <span class="book-count">245 books</span>
                                </div>
                                <div class="category-stats">
                                    <div class="stat">
                                        <span>Available:</span>
                                        <strong>198</strong>
                                    </div>
                                    <div class="stat">
                                        <span>Borrowed:</span>
                                        <strong>47</strong>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="category-card">
                                <div class="category-header">
                                    <h4>Midwifery</h4>
                                    <span class="book-count">189 books</span>
                                </div>
                                <div class="category-stats">
                                    <div class="stat">
                                        <span>Available:</span>
                                        <strong>156</strong>
                                    </div>
                                    <div class="stat">
                                        <span>Borrowed:</span>
                                        <strong>33</strong>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="category-card">
                                <div class="category-header">
                                    <h4>Medical Sciences</h4>
                                    <span class="book-count">312 books</span>
                                </div>
                                <div class="category-stats">
                                    <div class="stat">
                                        <span>Available:</span>
                                        <strong>267</strong>
                                    </div>
                                    <div class="stat">
                                        <span>Borrowed:</span>
                                        <strong>45</strong>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="category-card">
                                <div class="category-header">
                                    <h4>General Education</h4>
                                    <span class="book-count">156 books</span>
                                </div>
                                <div class="category-stats">
                                    <div class="stat">
                                        <span>Available:</span>
                                        <strong>134</strong>
                                    </div>
                                    <div class="stat">
                                        <span>Borrowed:</span>
                                        <strong>22</strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Circulation -->
                <section id="circulation" class="content-section">
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
                                <div class="count">23</div>
                                <small>Today</small>
                            </div>
                            <div class="circ-stat">
                                <h4>Books Returned</h4>
                                <div class="count">18</div>
                                <small>Today</small>
                            </div>
                            <div class="circ-stat">
                                <h4>Books Renewed</h4>
                                <div class="count">7</div>
                                <small>Today</small>
                            </div>
                            <div class="circ-stat">
                                <h4>Overdue Books</h4>
                                <div class="count">12</div>
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
                                    <tr>
                                        <td>TRX-2026-001</td>
                                        <td>John Student</td>
                                        <td>Nursing Fundamentals</td>
                                        <td><span class="transaction-type checkout">Checkout</span></td>
                                        <td>Apr 22, 2026</td>
                                        <td><span class="status-badge active">Active</span></td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-info">View</button>
                                            <button class="btn btn-sm btn-outline-warning">Return</button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>TRX-2026-002</td>
                                        <td>Jane Student</td>
                                        <td>Midwifery Practice</td>
                                        <td><span class="transaction-type return">Return</span></td>
                                        <td>Apr 22, 2026</td>
                                        <td><span class="status-badge completed">Completed</span></td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-info">View</button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </section>

                <!-- Library Members -->
                <section id="members" class="content-section">
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
                                <div class="count">285</div>
                                <small>Active members</small>
                            </div>
                            <div class="member-stat">
                                <h4>Staff</h4>
                                <div class="count">45</div>
                                <small>Active members</small>
                            </div>
                            <div class="member-stat">
                                <h4>Faculty</h4>
                                <div class="count">12</div>
                                <small>Active members</small>
                            </div>
                            <div class="member-stat">
                                <h4>New This Month</h4>
                                <div class="count">18</div>
                                <small>Registrations</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="recent-members">
                        <h3>Recent Member Registrations</h3>
                        <div class="members-list">
                            <div class="member-item">
                                <div class="member-header">
                                    <h4>John Doe</h4>
                                    <span class="member-type student">Student</span>
                                </div>
                                <div class="member-details">
                                    <div class="detail">
                                        <span>Member ID:</span>
                                        <strong>LIB-2026-045</strong>
                                    </div>
                                    <div class="detail">
                                        <span>Program:</span>
                                        <strong>Certificate Nursing</strong>
                                    </div>
                                    <div class="detail">
                                        <span>Registered:</span>
                                        <strong>Apr 20, 2026</strong>
                                    </div>
                                </div>
                                <div class="member-actions">
                                    <button class="btn btn-sm btn-outline-primary">View Profile</button>
                                    <button class="btn btn-sm btn-outline-success">Issue Card</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Book Acquisition -->
                <section id="acquisition" class="content-section">
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
                            <div class="acquisition-item">
                                <div class="acquisition-header">
                                    <h4>Nursing Textbooks Collection</h4>
                                    <span class="status-badge in-progress">In Progress</span>
                                </div>
                                <div class="acquisition-details">
                                    <div class="detail">
                                        <span>Books:</span>
                                        <strong>25 titles</strong>
                                    </div>
                                    <div class="detail">
                                        <span>Cost:</span>
                                        <strong>UGX 2,500,000</strong>
                                    </div>
                                    <div class="detail">
                                        <span>Vendor:</span>
                                        <strong>Makere Bookshop</strong>
                                    </div>
                                    <div class="detail">
                                        <span>Expected:</span>
                                        <strong>May 15, 2026</strong>
                                    </div>
                                </div>
                                <div class="acquisition-actions">
                                    <button class="btn btn-sm btn-outline-primary">View Details</button>
                                    <button class="btn btn-sm btn-outline-success">Track Order</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Recent Activities -->
                <section class="activities-section">
                    <h2>Recent Library Activities</h2>
                    <div class="activities-list">
                        <?php foreach ($recent_activities as $activity): ?>
                        <div class="activity-item">
                            <div class="activity-icon">
                                <i class="fas fa-<?php echo $activity['icon']; ?>"></i>
                            </div>
                            <div class="activity-content">
                                <p><strong><?php echo $activity['user_name']; ?></strong> <?php echo $activity['action']; ?></p>
                                <small><?php echo date('M j, Y H:i', strtotime($activity['created_at'])); ?></small>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </section>
            </div>
        </div>
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
        document.querySelectorAll('.sidebar-nav .nav-link').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                document.querySelectorAll('.sidebar-nav .nav-link').forEach(l => l.classList.remove('active'));
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
</body>
</html>
