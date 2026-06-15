<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';

$ctx = bootstrapStaffDashboard(['bursar', 'finance']);
$auth_service = $ctx['auth'];
$user = $ctx['user'];
$userRole = $user['role'] ?? '';

// Enhanced database connections
$students_conn = getStudentsConnection();
$finance_conn = getStaffConnection();

if ($students_conn->connect_error) {
    die("Students DB connection failed: " . $students_conn->connect_error);
}

if ($finance_conn->connect_error) {
    die("Finance DB connection failed: " . $finance_conn->connect_error);
}

// Set charset
$students_conn->set_charset("utf8mb4");
$finance_conn->set_charset("utf8mb4");

// Get user information from session
$user_id = $_SESSION['user_id'] ?? 0;
$user_email = $_SESSION['email'] ?? '';
$user_name = $_SESSION['full_name'] ?? '';
$user_role = $_SESSION['role'] ?? '';

// Set Bursar statistics with fallback values
$today_collections = 5000000;
$week_collections = 35000000;
$month_collections = 45000000;
$outstanding_fees = 12000000;
$total_students = 150;

// Try to get real total students
try {
    $result = $students_conn->query("SELECT COUNT(*) as cnt FROM students");
    if ($result) $total_students = $result->fetch_assoc()['cnt'] ?? 150;
} catch (Exception $e) {
    error_log('bursar stats: ' . $e->getMessage());
}

// Handle search and filter functionality
$search_term = $_GET['search'] ?? '';
$filter_program = $_GET['program'] ?? '';
$filter_year = $_GET['year'] ?? '';
$filter_payment_status = $_GET['payment_status'] ?? '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'add_student':
            handleAddStudent();
            break;
        case 'update_student':
            handleUpdateStudent();
            break;
        case 'delete_student':
            handleDeleteStudent();
            break;
        case 'add_fee_account':
            handleAddFeeAccount();
            break;
        case 'update_fee_account':
            handleUpdateFeeAccount();
            break;
        case 'record_payment':
            handleRecordPayment();
            break;
        case 'generate_invoice':
            handleGenerateInvoice();
            break;
        case 'generate_receipt':
            handleGenerateReceipt();
            break;
        case 'bulk_payment':
            handleBulkPayment();
            break;
        case 'generate_financial_report':
            handleGenerateFinancialReport();
            break;
    }
}

// Get recent students from student data loader
$recent_students = [];
try {
    $loader = new StudentDataLoader();
    $recent_students = array_slice($loader->loadAllStudents(), 0, 8);
} catch (Exception $e) {
    error_log('bursar students: ' . $e->getMessage());
}

// Get recent payments
$recent_payments = [];
try {
    $fp_sql = "SELECT fp.*, s.first_name, s.surname FROM fee_payments fp
               JOIN students s ON fp.student_id = s.id
               ORDER BY fp.payment_date DESC LIMIT 10";
    $fp_result = $staff_conn->query($fp_sql);
    if ($fp_result) {
        while ($row = $fp_result->fetch_assoc()) {
            $recent_payments[] = $row;
        }
    }
} catch (Exception $e) {
    error_log('bursar payments: ' . $e->getMessage());
}

// Handle student update
function handleUpdateStudent() {
    global $conn;
    
    $student_id = sanitizeInput($_POST['student_id']);
    $first_name = sanitizeInput($_POST['first_name']);
    $surname = sanitizeInput($_POST['surname']);
    $other_name = sanitizeInput($_POST['other_name']);
    $phone = sanitizeInput($_POST['phone']);
    $email = sanitizeInput($_POST['email']);
    $address = sanitizeInput($_POST['address']);
    $guardian_name = sanitizeInput($_POST['guardian_name']);
    $guardian_phone = sanitizeInput($_POST['guardian_phone']);
    $guardian_email = sanitizeInput($_POST['guardian_email']);
    $emergency_contact_name = sanitizeInput($_POST['emergency_contact_name']);
    $emergency_contact_phone = sanitizeInput($_POST['emergency_contact_phone']);
    $status = sanitizeInput($_POST['status']);
    
    $sql = "UPDATE students SET first_name = ?, surname = ?, other_name = ?, phone = ?, email = ?, address = ?, guardian_name = ?, guardian_phone = ?, guardian_email = ?, emergency_contact_name = ?, emergency_contact_phone = ?, status = ?, updated_at = CURRENT_TIMESTAMP WHERE student_id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssssssssss", $first_name, $surname, $other_name, $phone, $email, $address, $guardian_name, $guardian_phone, $guardian_email, $emergency_contact_name, $emergency_contact_phone, $status, $student_id);
    
    if ($stmt->execute()) {
        logActivity($_SESSION['user_id'], $_SESSION['role'], 'Student Updated', "Updated student: $student_id - $first_name $surname", 'students', $student_id);
        $_SESSION['success'] = "Student updated successfully!";
    } else {
        $_SESSION['error'] = "Error updating student: " . $conn->error;
    }
    
    header("Location: bursar.php");
    exit();
}

// Handle student deletion
function handleDeleteStudent() {
    global $conn;
    
    $student_id = sanitizeInput($_POST['student_id']);
    
    // Check if student has fee payments
    $check_sql = "SELECT COUNT(*) as count FROM fee_payments WHERE student_id = ?";
    $check_result = executeQuery($check_sql, [$student_id], 's');
    
    if ($check_result[0]['count'] > 0) {
        $_SESSION['error'] = "Cannot delete student with payment records. Please archive instead.";
    } else {
        $sql = "DELETE FROM students WHERE student_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $student_id);
        
        if ($stmt->execute()) {
            logActivity($_SESSION['user_id'], $_SESSION['role'], 'Student Deleted', "Deleted student: $student_id", 'students', $student_id);
            $_SESSION['success'] = "Student deleted successfully!";
        } else {
            $_SESSION['error'] = "Error deleting student: " . $conn->error;
        }
    }
    
    header("Location: bursar.php");
    exit();
}

// Handle fee account addition
function handleAddFeeAccount() {
    global $conn;
    
    $student_id = sanitizeInput($_POST['student_id']);
    $academic_year = sanitizeInput($_POST['academic_year']);
    $program = sanitizeInput($_POST['program']);
    $level = sanitizeInput($_POST['level']);
    $year = sanitizeInput($_POST['year']);
    $semester = sanitizeInput($_POST['semester']);
    $total_fees = sanitizeInput($_POST['total_fees']);
    $due_date = sanitizeInput($_POST['due_date']);
    
    $sql = "INSERT INTO student_fee_accounts (student_id, academic_year, program, level, year, semester, total_fees, amount_paid, balance, due_date, status) VALUES (?, ?, ?, ?, ?, ?, ?, 0, ?, ?, 'unpaid')";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssssdds", $student_id, $academic_year, $program, $level, $year, $semester, $total_fees, $total_fees, $due_date);
    
    if ($stmt->execute()) {
        logActivity($_SESSION['user_id'], $_SESSION['role'], 'Fee Account Added', "Added fee account for: $student_id", 'fees', $student_id);
        $_SESSION['success'] = "Fee account added successfully!";
    } else {
        $_SESSION['error'] = "Error adding fee account: " . $conn->error;
    }
    
    header("Location: bursar.php");
    exit();
}

// Handle payment recording
function handleRecordPayment() {
    global $conn;
    
    $student_id = sanitizeInput($_POST['student_id']);
    $fee_account_id = sanitizeInput($_POST['fee_account_id']);
    $amount_paid = sanitizeInput($_POST['amount_paid']);
    $payment_method = sanitizeInput($_POST['payment_method']);
    $payment_reference = sanitizeInput($_POST['payment_reference']);
    $bank_name = sanitizeInput($_POST['bank_name']);
    $receipt_number = generateReceiptNumber();
    
    // Record payment
    $payment_sql = "INSERT INTO fee_payments (payment_id, student_id, fee_account_id, amount_paid, payment_method, payment_reference, bank_name, receipt_number, payment_date, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, CURDATE(), 'verified')";
    
    $stmt = $conn->prepare($payment_sql);
    $stmt->bind_param("ssdssssss", $receipt_number, $student_id, $fee_account_id, $amount_paid, $payment_method, $payment_reference, $bank_name, $receipt_number);
    
    if ($stmt->execute()) {
        // Update fee account
        $update_sql = "UPDATE student_fee_accounts SET amount_paid = amount_paid + ?, balance = balance - ?, last_payment_date = CURDATE(), status = CASE WHEN (balance - ?) <= 0 THEN 'fully_paid' ELSE CASE WHEN amount_paid > 0 THEN 'partially_paid' ELSE 'unpaid' END WHERE id = ?";
        
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("dddi", $amount_paid, $amount_paid, $amount_paid, $fee_account_id);
        $update_stmt->execute();
        
        logActivity($_SESSION['user_id'], $_SESSION['role'], 'Payment Recorded', "Recorded payment of $amount_paid for: $student_id", 'fees', $student_id);
        $_SESSION['success'] = "Payment recorded successfully! Receipt: $receipt_number";
    } else {
        $_SESSION['error'] = "Error recording payment: " . $conn->error;
    }
    
    header("Location: bursar.php");
    exit();
}

// Generate student ID
function generateStudentId() {
    global $conn;
    
    do {
        $year = date('Y');
        $random = mt_rand(1000, 9999);
        $student_id = "ISNM/$year/$random";
        
        $check_sql = "SELECT COUNT(*) as count FROM students WHERE student_id = ?";
        $check_result = executeQuery($check_sql, [$student_id], 's');
    } while ($check_result[0]['count'] > 0);
    
    return $student_id;
}

// Generate receipt number
function generateReceiptNumber() {
    do {
        $receipt_no = 'RCP' . date('Y') . mt_rand(100000, 999999);
        $check_sql = "SELECT COUNT(*) as count FROM fee_payments WHERE receipt_number = ?";
        $check_result = executeQuery($check_sql, [$receipt_no], 's');
    } while ($check_result[0]['count'] > 0);
    
    return $receipt_no;
}

// Get user information from bootstrap
$user_name = $user['full_name'] ?? 'Bursar';

// Get additional stats
$active_students = 0;
$total_collections_raw = 0;
try {
    $active_stmt = $staff_conn->prepare("SELECT COUNT(*) as c FROM students WHERE status = 'Active'");
    if ($active_stmt) { $active_stmt->execute(); $active_students = (int)($active_stmt->get_result()->fetch_assoc()['c'] ?? 0); }
    $coll_stmt = $staff_conn->prepare("SELECT COALESCE(SUM(amount_paid),0) as total FROM fee_payments WHERE status = 'verified'");
    if ($coll_stmt) { $coll_stmt->execute(); $total_collections_raw = (int)($coll_stmt->get_result()->fetch_assoc()['total'] ?? 0); }
} catch (Exception $e) { error_log('bursar extra stats: ' . $e->getMessage()); }

if (empty($recent_students)) { $recent_students = []; }
if (empty($recent_payments)) { $recent_payments = []; }
if (!isset($total_students)) { $total_students = 0; }
if (!isset($active_students)) { $active_students = 0; }
if (!isset($total_collections_raw)) { $total_collections_raw = 0; }
if (!isset($outstanding_fees)) { $outstanding_fees = 0; }
?>

<!DOCTYPE html>
<html lang="en">
<head>
<?php include_once __DIR__ . '/../includes/_favicon.php'; ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <title>Bursar Dashboard - ISNM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #1a237e;
            --secondary-color: #3949ab;
            --accent-color: #ffd700;
            --success-color: #28a745;
            --danger-color: #dc3545;
            --warning-color: #ffc107;
            --info-color: #17a2b8;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
        }

        .dashboard-container {
            display: flex;
            min-height: 100vh;
        }

        .sidebar {
            width: 280px;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 2rem;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
        }

        .sidebar-header {
            text-align: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid rgba(255,255,255,0.2);
        }

        .sidebar-logo {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            margin-bottom: 1rem;
            border: 3px solid var(--accent-color);
        }

        .main-content {
            flex: 1;
            padding: 2rem;
            overflow-y: auto;
        }

        .dashboard-header {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            border-left: 5px solid var(--primary-color);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: linear-gradient(to bottom, #ffe082 0%, #ffe082 5px, #fef9e7 5px, #fef9e7 100%);
            border-radius: 15px;
            padding: 1.5rem;
            border-left: 4px solid var(--primary-color);
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1rem;
            color: white;
            font-size: 1.5rem;
        }

        .stat-content h3 {
            font-size: 2rem;
            font-weight: bold;
            color: var(--primary-color);
            margin: 0;
        }

        .stat-content p {
            color: #666;
            margin: 0.5rem 0 0 0;
        }

        .content-section {
            background: linear-gradient(to bottom, #d7ccc8 0%, #d7ccc8 5px, #f0dcc8 5px, #f0dcc8 100%);
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
        }

        .section-header {
            display: flex;
            justify-content: between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #f0f0f0;
        }

        .section-title {
            color: var(--primary-color);
            font-weight: bold;
            font-size: 1.5rem;
            margin: 0;
        }

        .nav-link {
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            padding: 0.75rem 1rem;
            border-radius: 8px;
            display: block;
            margin-bottom: 0.5rem;
            transition: all 0.3s ease;
        }

        .nav-link:hover, .nav-link.active {
            background: rgba(255,255,255,0.1);
            color: white;
        }

        .nav-link i {
            margin-right: 0.5rem;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border: none;
            border-radius: 8px;
            padding: 0.75rem 1.5rem;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(26, 35, 126, 0.3);
        }

        .payment-item {
            padding: 1rem;
            border-left: 3px solid var(--success-color);
            background: #f8f9fa;
            margin-bottom: 1rem;
            border-radius: 0 8px 8px 0;
        }

        .fee-status {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 500;
        }

        @media (max-width: 768px) {
            .dashboard-container {
                flex-direction: column;
            }
            
            .sidebar {
                width: 100%;
                padding: 1rem;
            }
            
            .main-content {
                padding: 1rem;
            }
        }
    </style>
    <link href="../dashboards/dashboard-mobile.css" rel="stylesheet">
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <img src="../images/school-logo.png" alt="ISNM Logo" class="sidebar-logo">
                <h4>Bursar Dashboard</h4>
                <p><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></p>
            </div>
            
            <nav class="sidebar-nav">
                <a href="#overview" class="nav-link active">
                    <i class="fas fa-tachometer-alt"></i> Overview
                </a>
                <a href="#students" class="nav-link">
                    <i class="fas fa-users"></i> Student Management
                </a>
                <a href="#fees" class="nav-link">
                    <i class="fas fa-money-bill"></i> Fee Management
                </a>
                <a href="#payments" class="nav-link">
                    <i class="fas fa-receipt"></i> Payments
                </a>
                <a href="#reports" class="nav-link">
                    <i class="fas fa-chart-bar"></i> Financial Reports
                </a>
            </nav>
            
            <div class="mt-auto">
                <div class="d-flex justify-content-center flex-wrap mb-2">
                    <a href="../student-directory.php" class="btn btn-sm btn-outline-info me-1"><i class="fas fa-address-book me-1"></i>Directory</a>
                    <a href="../store_request.php" class="btn btn-sm btn-outline-warning me-1"><i class="fas fa-shopping-cart me-1"></i>Store</a>
                    <a href="../news.php" class="btn btn-sm btn-outline-secondary me-1"><i class="fas fa-newspaper me-1"></i>News</a>
                </div>
                <a href="student-records.php" class="nav-link"><i class="fas fa-users-gear"></i> Student Records</a>
                <a href="../logout.php" class="btn btn-danger btn-sm w-100">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Header -->
            <div class="dashboard-header">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h1 class="mb-2">Bursar Dashboard</h1>
                        <p class="text-muted mb-0">Financial Management & Student Accounts</p>
                    </div>
                    <div class="col-md-4 text-end">
                        <div class="date-time">
                            <i class="fas fa-calendar"></i>
                            <span id="currentDate"></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Overview Section -->
            <section id="overview" class="content-section">
                <div class="section-header">
                    <h2 class="section-title">Financial Overview</h2>
                </div>
                
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stat-content">
                            <h3><?php echo $total_students; ?></h3>
                            <p>Total Students</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-user-check"></i>
                        </div>
                        <div class="stat-content">
                            <h3><?php echo $active_students; ?></h3>
                            <p>Active Students</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-money-bill-wave"></i>
                        </div>
                        <div class="stat-content">
                            <h3>UGX <?php echo number_format($total_collections); ?></h3>
                            <p>Total Collections</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <div class="stat-content">
                            <h3>UGX <?php echo number_format($outstanding_fees); ?></h3>
                            <p>Outstanding Fees</p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Student Management Section -->
            <section id="students" class="content-section">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2 class="section-title">Student Management</h2>
                    <div>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addStudentModal">
                            <i class="fas fa-user-plus"></i> Add Student
                        </button>
                        <button class="btn btn-success" onclick="window.location.href='../bulk_photo_upload.php'">
                            <i class="fas fa-camera"></i> Photo Upload
                        </button>
                    </div>
                </div>
                
                <!-- Student Search -->
                <?php echo displayStudentSearchBox('Search students by name, ID, or phone...', 'bursarSearchResults'); ?>
                
                <!-- Student Profile Cards -->
                <div class="row mt-4">
                    <?php foreach ($recent_students as $student): ?>
                        <div class="col-md-6 col-lg-4 mb-4">
                            <?php echo displayStudentProfileCard($student['student_id'], 'compact'); ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>

            <!-- Fee Statement Lookup -->
            <section class="content-section">
                <h2 class="section-title"><i class="fas fa-file-invoice"></i> Print Fee Statement</h2>
                <div class="card">
                    <div class="card-body">
                        <form class="row g-2 align-items-end" method="GET" action="../sql/staffs/view-fees.php">
                            <div class="col-md-8">
                                <label class="form-label fw-semibold">Enter Student ID (database ID)</label>
                                <input type="number" name="id" class="form-control" placeholder="Student database ID..." required>
                            </div>
                            <div class="col-md-4">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-file-invoice me-1"></i> View Statement
                                </button>
                            </div>
                        </form>
                        <div class="mt-2 small text-muted">
                            <i class="fas fa-info-circle me-1"></i>
                            Statement includes school logo, invoice ledger, payment history, and print/PDF options.
                        </div>
                    </div>
                </div>
            </section>

            <!-- Recent Payments Section -->
            <section id="payments" class="content-section">
                <div class="section-header">
                    <h2 class="section-title">Recent Payments</h2>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#recordPaymentModal">
                        <i class="fas fa-plus"></i> Record Payment
                    </button>
                </div>
                
                <div class="payments-list">
                    <?php foreach ($recent_payments as $payment): ?>
                        <div class="payment-item">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <strong><?php echo htmlspecialchars($payment['surname'] . ', ' . $payment['first_name']); ?></strong>
                                    <p class="mb-1">
                                        <?php 
                                            $pm = strtolower($payment['payment_method'] ?? '');
                                            $logo_map = [
                                                'mobile_money' => '../images/mtn-logo.svg',
                                                'momo' => '../images/mtn-logo.svg',
                                                'mtn' => '../images/mtn-logo.svg',
                                                'airtel_money' => '../images/airtel-logo.svg',
                                                'airtel' => '../images/airtel-logo.svg',
                                                'bank_deposit' => '../images/bank-default.svg',
                                                'bank_transfer' => '../images/bank-default.svg',
                                                'cash' => '../images/bank-default.svg',
                                                'cheque' => '../images/bank-default.svg',
                                            ];
                                            $logo_path = $logo_map[$pm] ?? '../images/bank-default.svg';
                                        ?>
                                        <?php if (file_exists($logo_path)): ?>
                                            <img src="<?php echo $logo_path; ?>" alt="<?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $pm))); ?>" style="height: 22px; vertical-align: middle; margin-right: 5px; border-radius: 3px;">
                                        <?php endif; ?>
                                        Amount: UGX <?php echo number_format($payment['amount_paid']); ?>
                                    </p>
                                    <small class="text-muted">
                                        <strong>Method:</strong> <?php echo ucfirst(str_replace('_', ' ', $pm ?: 'N/A')); ?> | 
                                        <strong>Receipt:</strong> <?php echo htmlspecialchars($payment['receipt_number'] ?? 'N/A'); ?>
                                    </small>
                                </div>
                                <div class="text-end">
                                    <small class="text-muted">
                                        <?php echo date('M j, Y H:i', strtotime($payment['payment_date'])); ?>
                                    </small>
                                    <br>
                                    <span class="badge bg-success">Verified</span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        </div>
    </div>

    <!-- Add Student Modal -->
    <div class="modal fade" id="addStudentModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-gradient text-white" style="background: linear-gradient(135deg, #1a237e, #3949ab);">
                    <h5 class="modal-title">
                        <i class="fas fa-user-plus"></i> Add New Student
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="bursar.php">
                    <input type="hidden" name="action" value="add_student">
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">First Name *</label>
                                <input type="text" class="form-control" name="first_name" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Surname *</label>
                                <input type="text" class="form-control" name="surname" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Other Name</label>
                                <input type="text" class="form-control" name="other_name">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Date of Birth *</label>
                                <input type="date" class="form-control" name="date_of_birth" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Gender *</label>
                                <select class="form-select" name="gender" required>
                                    <option value="">Select Gender</option>
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Program *</label>
                                <select class="form-select" name="program" required>
                                    <option value="">Select Program</option>
                                    <option value="Certificate Midwifery">Certificate Midwifery</option>
                                    <option value="Diploma Midwifery">Diploma Midwifery</option>
                                    <option value="Diploma Midwifery Extension">Diploma Midwifery Extension</option>
                                    <option value="Diploma Nursing Extension">Diploma Nursing Extension</option>
                                    <option value="Certificate Nursing">Certificate Nursing</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Level *</label>
                                <select class="form-select" name="level" required>
                                    <option value="">Select Level</option>
                                    <option value="Certificate">Certificate</option>
                                    <option value="Diploma">Diploma</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Phone *</label>
                                <input type="tel" class="form-control" name="phone" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Email *</label>
                                <input type="email" class="form-control" name="email" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Intake Year *</label>
                                <input type="text" class="form-control" name="intake_year" value="<?php echo date('Y'); ?>" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Total Fees *</label>
                                <input type="number" class="form-control" name="total_fees" required>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Add Student
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Record Payment Modal -->
    <div class="modal fade" id="recordPaymentModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-gradient text-white" style="background: linear-gradient(135deg, #28a745, #20c997);">
                    <h5 class="modal-title">
                        <i class="fas fa-money-bill"></i> Record Payment
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="bursar.php">
                    <input type="hidden" name="action" value="record_payment">
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Student ID *</label>
                                <input type="text" class="form-control" name="student_id" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Amount Paid *</label>
                                <input type="number" class="form-control" name="amount_paid" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Payment Method *</label>
                                <select class="form-select" name="payment_method" required>
                                    <option value="">Select Method</option>
                                    <option value="cash">Cash</option>
                                    <option value="bank_deposit">Bank Deposit</option>
                                    <option value="mobile_money">Mobile Money</option>
                                    <option value="cheque">Cheque</option>
                                    <option value="online_transfer">Online Transfer</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Payment Reference</label>
                                <input type="text" class="form-control" name="payment_reference">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Bank Name</label>
                                <input type="text" class="form-control" name="bank_name">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Fee Account ID</label>
                                <input type="number" class="form-control" name="fee_account_id">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-save"></i> Record Payment
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Student Profile Modal -->
    <?php echo displayStudentProfileModal(''); ?>
    
    <!-- Student Profile Styles -->
    <?php echo getStudentProfileStyles(); ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        // Update current date/time
        function updateDateTime() {
            const now = new Date();
            const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
            document.getElementById('currentDate').textContent = now.toLocaleDateString('en-US', options);
        }
        
        updateDateTime();
        setInterval(updateDateTime, 60000);

        // Navigation handling
        document.querySelectorAll('.nav-link').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                
                // Remove active class from all links
                document.querySelectorAll('.nav-link').forEach(l => l.classList.remove('active'));
                
                // Add active class to clicked link
                this.classList.add('active');
                
                // Show corresponding section
                const targetId = this.getAttribute('href').substring(1);
                const targetSection = document.getElementById(targetId);
                
                if (targetSection) {
                    // Hide all sections
                    document.querySelectorAll('.content-section').forEach(section => {
                        section.style.display = 'none';
                    });
                    
                    // Show target section
                    targetSection.style.display = 'block';
                }
            });
        });

        // Student profile functions
        function viewFullProfile(studentId) {
            showStudentProfileModal(studentId);
        }
        
        function editStudent(studentId) {
            window.location.href = '../student_accounts_management.php?action=edit&student_id=' + studentId;
        }
        
        function viewAcademic(studentId) {
            window.location.href = '../academic_records.php?student_id=' + studentId;
        }
        
        function viewFees(studentId) {
            window.location.href = '../fee_management.php?student_id=' + studentId;
        }
        
        function sendMessage(studentId) {
            alert('Messaging functionality would be implemented here for student: ' + studentId);
        }
        
        function printProfile(studentId) {
            window.print();
        }
    </script>
<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
</body>
</html>

<?php
// Display alerts
if (isset($_SESSION['success'])) {
    echo '<div class="alert alert-success alert-dismissible fade show position-fixed" style="top: 20px; right: 20px; z-index: 9999;">
        <i class="fas fa-check-circle"></i> ' . htmlspecialchars($_SESSION['success']) . '
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>';
    unset($_SESSION['success']);
}

if (isset($_SESSION['error'])) {
    echo '<div class="alert alert-danger alert-dismissible fade show position-fixed" style="top: 20px; right: 20px; z-index: 9999;">
        <i class="fas fa-exclamation-triangle"></i> ' . htmlspecialchars($_SESSION['error']) . '
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>';
    unset($_SESSION['error']);
}
?>

