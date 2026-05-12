<?php
// ISNM Enhanced HR Manager Dashboard
// Professional 3D graphics with student search, filtering, and comprehensive functionality

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include unified authentication system
require_once '../auth-service.php';

// Start secure session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Global authentication service
$auth_service = new AuthenticationService();

// Strict dashboard protection - only HR allowed
if (!$auth_service->isAuthenticated()) {
    header('Location: ../staff-login.php');
    exit();
}

// Check if user has the correct role
$userRole = $_SESSION['role'] ?? '';
if (stripos($userRole, 'hr') === false && stripos($userRole, 'manager') === false) {
    header('Location: ../staff-login.php?error=unauthorized');
    exit();
}

// Database connections
$staff_conn = new mysqli('localhost', 'root', '', 'staffs_db');
$students_conn = new mysqli('localhost', 'root', '', 'students_db');

if ($staff_conn->connect_error) {
    die("Staff DB connection failed: " . $staff_conn->connect_error);
}

if ($students_conn->connect_error) {
    die("Students DB connection failed: " . $students_conn->connect_error);
}

// Set charset
$staff_conn->set_charset("utf8mb4");
$students_conn->set_charset("utf8mb4");

// Get user information from session
$staff_id = $_SESSION['user_id'] ?? 0;
$staff_email = $_SESSION['email'] ?? '';
$staff_name = $_SESSION['full_name'] ?? '';

// Handle search functionality
$search_term = $_GET['search'] ?? '';
$filter_department = $_GET['department'] ?? '';
$filter_status = $_GET['status'] ?? '';
$filter_year = $_GET['year'] ?? '';

// Handle staff management actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'add_staff':
            handleAddStaff();
            break;
        case 'update_staff':
            handleUpdateStaff();
            break;
        case 'delete_staff':
            handleDeleteStaff();
            break;
        case 'assign_role':
            handleAssignRole();
            break;
        case 'generate_report':
            handleGenerateReport();
            break;
    }
}

// Function to add new staff
function handleAddStaff() {
    global $staff_conn;
    
    $staff_id = generateStaffId();
    $full_name = sanitizeInput($_POST['full_name']);
    $email = sanitizeInput($_POST['email']);
    $position = sanitizeInput($_POST['position']);
    $department = sanitizeInput($_POST['department']);
    $role_id = $_POST['role_id'];
    $phone = sanitizeInput($_POST['phone'] ?? '');
    $address = sanitizeInput($_POST['address'] ?? '');
    $emergency_contact = sanitizeInput($_POST['emergency_contact'] ?? '');
    
    $sql = "INSERT INTO staff (staff_id, full_name, email, password, phone, position, department, role_id, address, emergency_contact, status, hire_date, created_by) 
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $hashed_password = password_hash('12345678', PASSWORD_DEFAULT);
    $stmt = $staff_conn->prepare($sql);
    $stmt->bind_param("sssssssiss", $staff_id, $full_name, $email, $hashed_password, $phone, $position, $department, $role_id, $address, $emergency_contact, 'Active', date('Y-m-d'), $_SESSION['user_id']);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "Staff member added successfully!";
    } else {
        $_SESSION['error'] = "Failed to add staff member.";
    }
    
    header("Location: hr-manager-enhanced.php");
    exit();
}

// Function to update staff
function handleUpdateStaff() {
    global $staff_conn;
    
    $staff_id = $_POST['staff_id'];
    $full_name = sanitizeInput($_POST['full_name']);
    $email = sanitizeInput($_POST['email']);
    $position = sanitizeInput($_POST['position']);
    $department = sanitizeInput($_POST['department']);
    $role_id = $_POST['role_id'];
    $phone = sanitizeInput($_POST['phone'] ?? '');
    $address = sanitizeInput($_POST['address'] ?? '');
    $emergency_contact = sanitizeInput($_POST['emergency_contact'] ?? '';
    
    $sql = "UPDATE staff SET full_name = ?, email = ?, phone = ?, position = ?, department = ?, role_id = ?, address = ?, emergency_contact = ?, updated_by = NOW() WHERE id = ?";
    
    $stmt = $staff_conn->prepare($sql);
    $stmt->bind_param("sssssisss", $full_name, $email, $phone, $position, $department, $role_id, $address, $emergency_contact, $_SESSION['user_id'], $staff_id);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "Staff member updated successfully!";
    } else {
        $_SESSION['error'] = "Failed to update staff member.";
    }
    
    header("Location: hr-manager-enhanced.php");
    exit();
}

// Function to delete staff
function handleDeleteStaff() {
    global $staff_conn;
    
    $staff_id = $_POST['staff_id'];
    
    $sql = "DELETE FROM staff WHERE id = ?";
    $stmt = $staff_conn->prepare($sql);
    $stmt->bind_param("i", $staff_id);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "Staff member deleted successfully!";
    } else {
        $_SESSION['error'] = "Failed to delete staff member.";
    }
    
    header("Location: hr-manager-enhanced.php");
    exit();
}

// Function to assign role
function handleAssignRole() {
    global $staff_conn;
    
    $staff_id = $_POST['staff_id'];
    $role_id = $_POST['role_id'];
    
    $sql = "UPDATE staff SET role_id = ?, updated_by = NOW() WHERE id = ?";
    $stmt = $staff_conn->prepare($sql);
    $stmt->bind_param("ii", $role_id, $_SESSION['user_id'], $staff_id);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "Role assigned successfully!";
    } else {
        $_SESSION['error'] = "Failed to assign role.";
    }
    
    header("Location: hr-manager-enhanced.php");
    exit();
}

// Function to generate report
function handleGenerateReport() {
    global $staff_conn, $students_conn;
    
    $report_type = $_POST['report_type'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    
    // Generate report based on type
    switch ($report_type) {
        case 'staff_summary':
            generateStaffSummaryReport();
            break;
        case 'student_statistics':
            generateStudentStatisticsReport();
            break;
        case 'attendance_report':
            generateAttendanceReport();
            break;
    }
}

// Generate staff summary report
function generateStaffSummaryReport() {
    global $staff_conn;
    
    $sql = "SELECT s.*, sr.role_name, sd.department_name 
             FROM staff s 
             LEFT JOIN staff_roles sr ON s.role_id = sr.id 
             LEFT JOIN staff_departments sd ON s.department = sd.department_name 
             ORDER BY s.full_name";
    
    $result = $staff_conn->query($sql);
    $staff_data = $result->fetch_all(MYSQLI_ASSOC);
    
    // Generate CSV
    $filename = 'staff_summary_' . date('Y-m-d') . '.csv';
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Staff ID', 'Full Name', 'Email', 'Position', 'Department', 'Role', 'Status', 'Hire Date']);
    
    foreach ($staff_data as $staff) {
        fputcsv($output, [
            $staff['staff_id'],
            $staff['full_name'],
            $staff['email'],
            $staff['position'],
            $staff['department'],
            $staff['role_name'],
            $staff['status'],
            $staff['hire_date']
        ]);
    }
    
    fclose($output);
    exit();
}

// Generate student statistics report
function generateStudentStatisticsReport() {
    global $students_conn;
    
    $sql = "SELECT course, COUNT(*) as total_students, AVG(gpa) as avg_gpa 
             FROM students 
             WHERE status = 'Active' 
             GROUP BY course";
    
    $result = $students_conn->query($sql);
    $student_data = $result->fetch_all(MYSQLI_ASSOC);
    
    // Generate CSV
    $filename = 'student_statistics_' . date('Y-m-d') . '.csv';
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Course', 'Total Students', 'Average GPA']);
    
    foreach ($student_data as $data) {
        fputcsv($output, [
            $data['course'],
            $data['total_students'],
            number_format($data['avg_gpa'], 2)
        ]);
    }
    
    fclose($output);
    exit();
}

// Generate attendance report
function generateAttendanceReport() {
    global $staff_conn;
    
    $sql = "SELECT s.full_name, COUNT(*) as total_days, 
             SUM(CASE WHEN sa.status = 'Present' THEN 1 ELSE 0 END) as present_days,
             SUM(CASE WHEN sa.status = 'Absent' THEN 1 ELSE 0 END) as absent_days
             FROM staff s 
             LEFT JOIN staff_attendance sa ON s.id = sa.staff_id 
             WHERE sa.date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
             GROUP BY s.id";
    
    $result = $staff_conn->query($sql);
    $attendance_data = $result->fetch_all(MYSQLI_ASSOC);
    
    // Generate CSV
    $filename = 'attendance_report_' . date('Y-m-d') . '.csv';
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Staff Name', 'Total Days', 'Present Days', 'Absent Days']);
    
    foreach ($attendance_data as $data) {
        fputcsv($output, [
            $data['full_name'],
            $data['total_days'],
            $data['present_days'],
            $data['absent_days']
        ]);
    }
    
    fclose($output);
    exit();
}

// Generate staff ID
function generateStaffId() {
    return 'STAFF' . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
}

// Sanitize input
function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

// Get staff data with filters
function getStaffData($search_term, $filter_department, $filter_status, $filter_year) {
    global $staff_conn;
    
    $sql = "SELECT s.*, sr.role_name, sd.department_name 
             FROM staff s 
             LEFT JOIN staff_roles sr ON s.role_id = sr.id 
             LEFT JOIN staff_departments sd ON s.department = sd.department_name 
             WHERE 1=1";
    
    $params = [];
    $types = "";
    
    if (!empty($search_term)) {
        $sql .= " AND (s.full_name LIKE ? OR s.email LIKE ? OR s.staff_id LIKE ?)";
        $params = array_merge($params, ["%$search_term%", "%$search_term%", "%$search_term%"]);
        $types .= "sss";
    }
    
    if (!empty($filter_department)) {
        $sql .= " AND s.department = ?";
        $params = array_merge($params, [$filter_department]);
        $types .= "s";
    }
    
    if (!empty($filter_status)) {
        $sql .= " AND s.status = ?";
        $params = array_merge($params, [$filter_status]);
        $types .= "s";
    }
    
    if (!empty($filter_year)) {
        $sql .= " AND YEAR(s.hire_date) = ?";
        $params = array_merge($params, [$filter_year]);
        $types .= "s";
    }
    
    $sql .= " ORDER BY s.full_name";
    
    $stmt = $staff_conn->prepare($sql);
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    } else {
        $stmt->execute();
    }
    
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Get departments for filter
function getDepartments() {
    global $staff_conn;
    
    $sql = "SELECT DISTINCT department FROM staff WHERE department IS NOT NULL ORDER BY department";
    $result = $staff_conn->query($sql);
    return $result->fetch_all(MYSQLI_ASSOC);
}

// Get roles for filter
function getRoles() {
    global $staff_conn;
    
    $sql = "SELECT id, role_name FROM staff_roles ORDER BY role_name";
    $result = $staff_conn->query($sql);
    return $result->fetch_all(MYSQLI_ASSOC);
}

// Get years for filter
function getYears() {
    global $staff_conn;
    
    $sql = "SELECT DISTINCT YEAR(hire_date) as year FROM staff WHERE hire_date IS NOT NULL ORDER BY year DESC";
    $result = $staff_conn->query($sql);
    return $result->fetch_all(MYSQLI_ASSOC);
}

// Get statistics
function getStatistics() {
    global $staff_conn, $students_conn;
    
    $stats = [];
    
    // Staff statistics
    $staff_sql = "SELECT COUNT(*) as total FROM staff WHERE status = 'Active'";
    $staff_result = $staff_conn->query($staff_sql);
    $stats['total_staff'] = $staff_result->fetch_assoc()['total'];
    
    // Student statistics
    $student_sql = "SELECT COUNT(*) as total FROM students WHERE status = 'Active'";
    $student_result = $students_conn->query($student_sql);
    $stats['total_students'] = $student_result->fetch_assoc()['total'];
    
    // Department statistics
    $dept_sql = "SELECT COUNT(*) as total FROM staff GROUP BY department";
    $dept_result = $staff_conn->query($dept_sql);
    $stats['departments'] = $dept_result->num_rows;
    
    // Recent activities
    $activity_sql = "SELECT COUNT(*) as total FROM staff_activity_log WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
    $activity_result = $staff_conn->query($activity_sql);
    $stats['recent_activities'] = $activity_result ? $activity_result->fetch_assoc()['total'] : 0;
    
    return $stats;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HR Manager Dashboard - ISNM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/staff_dashboard_enhanced.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="dashboard-container">
        <!-- School Header with Logo -->
        <div class="school-header">
            <img src="../images/school-logo.png" alt="ISNM Logo" class="school-logo">
            <div>
                <h1>ISNM School Management System</h1>
                <h3>Human Resources Management</h3>
            </div>
        </div>
        
        <div class="dashboard-grid">
            <!-- Search and Filter Section -->
            <div class="panel-3d">
                <h3><i class="fas fa-search me-2"></i>Search & Filter Staff</h3>
                <form method="GET" class="mb-3">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <input type="text" class="form-control" name="search" placeholder="Search staff..." value="<?php echo htmlspecialchars($search_term); ?>">
                        </div>
                        <div class="col-md-2 mb-3">
                            <select class="form-control" name="department">
                                <option value="">All Departments</option>
                                <?php
                                $departments = getDepartments();
                                foreach ($departments as $dept) {
                                    echo '<option value="' . $dept['department'] . '">' . htmlspecialchars($dept['department']) . '</option>';
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-md-2 mb-3">
                            <select class="form-control" name="status">
                                <option value="">All Status</option>
                                <option value="Active">Active</option>
                                <option value="Inactive">Inactive</option>
                                <option value="On Leave">On Leave</option>
                            </select>
                        </div>
                        <div class="col-md-2 mb-3">
                            <select class="form-control" name="year">
                                <option value="">All Years</option>
                                <?php
                                $years = getYears();
                                foreach ($years as $year) {
                                    echo '<option value="' . $year['year'] . '">' . $year['year'] . '</option>';
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-md-2 mb-3">
                            <button type="submit" class="btn-3d">Filter</button>
                        </div>
                    </div>
                </form>
            </div>
            
            <!-- Statistics Cards -->
            <?php
            $stats = getStatistics();
            ?>
            
            <div class="stat-card">
                <div class="stat-number"><?php echo number_format($stats['total_staff']); ?></div>
                <div class="stat-label">Total Staff</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-number"><?php echo number_format($stats['total_students']); ?></div>
                <div class="stat-label">Total Students</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-number"><?php echo number_format($stats['departments']); ?></div>
                <div class="stat-label">Departments</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-number"><?php echo number_format($stats['recent_activities']); ?></div>
                <div class="stat-label">Recent Activities</div>
            </div>
            
            <!-- Staff Management Actions -->
            <div class="panel-3d">
                <h3><i class="fas fa-user-plus me-2"></i>Staff Management</h3>
                <form method="POST">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="action">Action:</label>
                            <select class="form-control" id="action" name="action">
                                <option value="add_staff">Add Staff</option>
                                <option value="update_staff">Update Staff</option>
                                <option value="delete_staff">Delete Staff</option>
                                <option value="assign_role">Assign Role</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="staff_id">Staff Member:</label>
                            <select class="form-control" id="staff_id" name="staff_id">
                                <option value="">Select Staff</option>
                                <?php
                                $staff_sql = "SELECT id, full_name, position FROM staff WHERE status = 'Active' ORDER BY full_name";
                                $staff_result = $staff_conn->query($staff_sql);
                                
                                while ($staff = $staff_result->fetch_assoc()) {
                                    echo '<option value="' . $staff['id'] . '">' . htmlspecialchars($staff['full_name']) . ' - ' . htmlspecialchars($staff['position']) . '</option>';
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-md-12">
                            <button type="submit" class="btn-3d">Execute Action</button>
                        </div>
                    </div>
                </form>
            </div>
            
            <!-- Report Generation -->
            <div class="panel-3d">
                <h3><i class="fas fa-file-export me-2"></i>Generate Reports</h3>
                <form method="POST">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="report_type">Report Type:</label>
                            <select class="form-control" id="report_type" name="report_type">
                                <option value="staff_summary">Staff Summary</option>
                                <option value="student_statistics">Student Statistics</option>
                                <option value="attendance_report">Attendance Report</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="start_date">Start Date:</label>
                            <input type="date" class="form-control" id="start_date" name="start_date" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="end_date">End Date:</label>
                            <input type="date" class="form-control" id="end_date" name="end_date" required>
                        </div>
                        <div class="col-md-12">
                            <button type="submit" name="action" value="generate_report" class="btn-3d">Generate Report</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        // Initialize 3D effects
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.stat-card');
            cards.forEach((card, index) => {
                setTimeout(() => {
                    card.style.transform = 'translateY(0)';
                    card.style.opacity = '1';
                }, index * 100);
            });
        });
    </script>
</body>
</html>
