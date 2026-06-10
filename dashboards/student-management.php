<?php
/**
 * Student Management Dashboard
 * Allows Secretary and Director ICT to add, view, and manage students
 * Database: igangaschoolofl_students_db
 */

session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    header('Location: ../index.php');
    exit;
}

// Check if user has permission to manage students
$allowed_roles = ['School Secretary', 'Director ICT', 'Academic Registrar', 'Director General', 'CEO'];
if (!in_array($_SESSION['role'], $allowed_roles)) {
    echo "Access Denied. Only Secretary, Director ICT, or Academic Registrar can manage students.";
    exit;
}

// Database connection
require_once '../db_connect.php';

// Get the action from URL
$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$message = '';
$error = '';

// ============================================================
// ADD NEW STUDENT
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'add') {
    $student_number = $_POST['student_number'] ?? '';
    $registration_number = $_POST['registration_number'] ?? '';
    $national_id = $_POST['national_id'] ?? '';
    $first_name = $_POST['first_name'] ?? '';
    $surname = $_POST['surname'] ?? '';
    $other_name = $_POST['other_name'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $program = $_POST['program'] ?? '';
    $year = $_POST['year'] ?? 1;
    $set_name = $_POST['set_name'] ?? '';
    $intake_date = $_POST['intake_date'] ?? date('Y-m-d');
    $date_of_birth = $_POST['date_of_birth'] ?? '';
    $gender = $_POST['gender'] ?? 'Other';
    $nationality = $_POST['nationality'] ?? 'Ugandan';
    $address = $_POST['address'] ?? '';
    $guardian_name = $_POST['guardian_name'] ?? '';
    $guardian_phone = $_POST['guardian_phone'] ?? '';
    $emergency_contact_name = $_POST['emergency_contact_name'] ?? '';
    $emergency_contact_phone = $_POST['emergency_contact_phone'] ?? '';
    $status = $_POST['status'] ?? 'Active';
    
    // Validate required fields
    if (empty($student_number) || empty($first_name) || empty($surname) || empty($program)) {
        $error = "Student Number, First Name, Surname, and Program are required.";
    } else {
        // Call the stored procedure
        $sql = "CALL add_new_student(
            '$student_number',
            '$registration_number',
            '$national_id',
            '$first_name',
            '$surname',
            '$other_name',
            '$email',
            '$phone',
            '$program',
            $year,
            '$set_name',
            '$intake_date',
            '$date_of_birth',
            '$gender',
            '$nationality',
            '$address',
            '$guardian_name',
            '$guardian_phone',
            '$emergency_contact_name',
            '$emergency_contact_phone',
            '$status',
            {$_SESSION['user_id']}
        )";
        
        if ($conn->query($sql)) {
            $message = "Student added successfully! Default password: 12345678 (must be changed on first login)";
            $action = 'list'; // Redirect to list view
        } else {
            $error = "Error adding student: " . $conn->error;
        }
    }
}

// ============================================================
// DISPLAY STUDENT LIST
// ============================================================
if ($action === 'list') {
    $program = isset($_GET['program']) ? $_GET['program'] : '';
    $set_name = isset($_GET['set_name']) ? $_GET['set_name'] : '';
    $status = isset($_GET['status']) ? $_GET['status'] : '';
    
    // Build query
    $sql = "CALL get_all_students_list(
        " . (!empty($program) ? "'$program'" : "NULL") . ",
        " . (!empty($set_name) ? "'$set_name'" : "NULL") . ",
        " . (!empty($status) ? "'$status'" : "NULL") . ",
        1000
    )";
    
    $result = $conn->query($sql);
    $students = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $students[] = $row;
        }
    }
}

// ============================================================
// SEARCH STUDENTS
// ============================================================
if ($action === 'search' && !empty($_GET['q'])) {
    $search_term = $_GET['q'];
    $sql = "CALL search_students('$search_term')";
    
    $result = $conn->query($sql);
    $students = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $students[] = $row;
        }
    }
}

// ============================================================
// GET STUDENT DETAILS
// ============================================================
if ($action === 'view' && isset($_GET['student_id'])) {
    $student_id = $_GET['student_id'];
    $sql = "SELECT * FROM igangaschoolofl_students_db.students WHERE id = $student_id";
    $result = $conn->query($sql);
    $student = $result->fetch_assoc();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
<?php include_once __DIR__ . '/../includes/_favicon.php'; ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <title>Student Management - ISNM</title>
    <link rel="stylesheet" href="dashboard-style.css">
    <style>
        .student-container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .btn-group {
            margin-bottom: 20px;
            display: flex;
            gap: 10px;
        }
        
        .btn {
            padding: 10px 20px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            font-size: 14px;
        }
        
        .btn:hover {
            background-color: #0056b3;
        }
        
        .btn-danger {
            background-color: #dc3545;
        }
        
        .btn-danger:hover {
            background-color: #c82333;
        }
        
        .btn-success {
            background-color: #28a745;
        }
        
        .btn-success:hover {
            background-color: #218838;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        .message {
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 4px;
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .error {
            background-color: #f8d7da;
            color: #721c24;
            border-color: #f5c6cb;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        table th, table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        table th {
            background-color: #f8f9fa;
            font-weight: bold;
        }
        
        table tr:hover {
            background-color: #f5f5f5;
        }
        
        .search-box {
            margin-bottom: 20px;
            display: flex;
            gap: 10px;
        }
        
        .search-box input {
            flex: 1;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
    </style>
    <link href="../dashboards/dashboard-mobile.css" rel="stylesheet">
</head>
<body>
    <div class="student-container">
        <h1>Student Management System</h1>
        <p>Welcome, <?php echo $_SESSION['full_name'] ?? 'User'; ?> (<?php echo $_SESSION['role']; ?>)</p>
        
        <?php if (!empty($message)): ?>
            <div class="message"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        
        <?php if (!empty($error)): ?>
            <div class="message error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <div class="btn-group">
            <a href="?action=list" class="btn">View All Students</a>
            <a href="?action=add" class="btn btn-success">Add New Student</a>
        </div>
        
        <?php if ($action === 'add'): ?>
            <h2>Add New Student</h2>
            <form method="POST" action="?action=add">
                <div class="form-row">
                    <div class="form-group">
                        <label>Student Number *</label>
                        <input type="text" name="student_number" required>
                    </div>
                    <div class="form-group">
                        <label>Registration Number</label>
                        <input type="text" name="registration_number">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>First Name *</label>
                        <input type="text" name="first_name" required>
                    </div>
                    <div class="form-group">
                        <label>Surname *</label>
                        <input type="text" name="surname" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Other Name</label>
                        <input type="text" name="other_name">
                    </div>
                    <div class="form-group">
                        <label>National ID</label>
                        <input type="text" name="national_id">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email">
                    </div>
                    <div class="form-group">
                        <label>Phone</label>
                        <input type="text" name="phone">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Program *</label>
                        <select name="program" required>
                            <option value="">Select Program</option>
                            <option value="Diploma in Nursing">Diploma in Nursing</option>
                            <option value="Diploma in Midwifery">Diploma in Midwifery</option>
                            <option value="Certificate in Nursing">Certificate in Nursing</option>
                            <option value="Degree in Nursing">Degree in Nursing</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Year of Study</label>
                        <select name="year">
                            <option value="1">Year 1</option>
                            <option value="2">Year 2</option>
                            <option value="3">Year 3</option>
                            <option value="4">Year 4</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Intake Set</label>
                        <input type="text" name="set_name" placeholder="e.g., Set 28">
                    </div>
                    <div class="form-group">
                        <label>Intake Date</label>
                        <input type="date" name="intake_date" value="<?php echo date('Y-m-d'); ?>">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Date of Birth</label>
                        <input type="date" name="date_of_birth">
                    </div>
                    <div class="form-group">
                        <label>Gender</label>
                        <select name="gender">
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Nationality</label>
                        <input type="text" name="nationality" value="Ugandan">
                    </div>
                    <div class="form-group">
                        <label>Address</label>
                        <textarea name="address" rows="2"></textarea>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Guardian Name</label>
                        <input type="text" name="guardian_name">
                    </div>
                    <div class="form-group">
                        <label>Guardian Phone</label>
                        <input type="text" name="guardian_phone">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Emergency Contact Name</label>
                        <input type="text" name="emergency_contact_name">
                    </div>
                    <div class="form-group">
                        <label>Emergency Contact Phone</label>
                        <input type="text" name="emergency_contact_phone">
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Status</label>
                    <select name="status">
                        <option value="Active">Active</option>
                        <option value="Inactive">Inactive</option>
                        <option value="Suspended">Suspended</option>
                    </select>
                </div>
                
                <button type="submit" class="btn btn-success">Add Student</button>
                <a href="?action=list" class="btn">Cancel</a>
            </form>
        <?php endif; ?>
        
        <?php if ($action === 'list'): ?>
            <h2>Student List</h2>
            <div class="search-box">
                <input type="text" id="searchInput" placeholder="Search by name, email, or student number..." onkeyup="filterTable()">
            </div>
            
            <table id="studentTable">
                <thead>
                    <tr>
                        <th>Student Number</th>
                        <th>Full Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Program</th>
                        <th>Year</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($students as $student): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($student['student_number']); ?></td>
                            <td><?php echo htmlspecialchars($student['full_name']); ?></td>
                            <td><?php echo htmlspecialchars($student['email'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($student['phone'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($student['program'] ?? 'N/A'); ?></td>
                            <td><?php echo $student['current_year'] ?? 'N/A'; ?></td>
                            <td><span style="padding: 5px 10px; background-color: #d4edda; border-radius: 4px;"><?php echo $student['status']; ?></span></td>
                            <td>
                                <a href="?action=view&student_id=<?php echo $student['id']; ?>" class="btn" style="padding: 5px 10px; margin-right: 5px;">View</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <p>Total Students: <?php echo count($students); ?></p>
        <?php endif; ?>
    </div>
    
    <script>
        function filterTable() {
            const input = document.getElementById('searchInput');
            const table = document.getElementById('studentTable');
            const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');
            const filter = input.value.toLowerCase();
            
            for (let i = 0; i < rows.length; i++) {
                const text = rows[i].textContent.toLowerCase();
                rows[i].style.display = text.includes(filter) ? '' : 'none';
            }
        }
    </script>
<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
</body>
</html>
