<?php
// ISNM Bursar Payroll Management System
// Professional payroll management for bursar

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../includes/staff_dashboard_access.php';

$ctx = bootstrapStaffDashboard(['bursar', 'payroll', 'finance']);
$auth_service = $ctx['auth'];
$user = $ctx['user'];
$userRole = $user['role'] ?? '';

// Database connections
$staff_conn = getStaffConnection();
$students_conn = getStudentsConnection();

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

// Handle payroll actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'add_staff_salary':
            handleAddStaffSalary();
            break;
        case 'update_staff_salary':
            handleUpdateStaffSalary();
            break;
        case 'process_payroll':
            handleProcessPayroll();
            break;
        case 'generate_payslips':
            handleGeneratePayslips();
            break;
    }
}

// Function to add staff salary
function handleAddStaffSalary() {
    global $staff_conn;
    
    $staff_id = $_POST['staff_id'];
    $basic_salary = $_POST['basic_salary'];
    $allowances = $_POST['allowances'] ?? 0;
    $overtime_rate = $_POST['overtime_rate'] ?? 0;
    $nssf_tax = $_POST['nssf_tax'] ?? 0;
    $paye_tax = $_POST['paye_tax'] ?? 0;
    $effective_date = $_POST['effective_date'];
    
    $sql = "INSERT INTO staff_salaries (staff_id, basic_salary, allowances, overtime_rate, nssf_tax, paye_tax, effective_date, created_by) 
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $staff_conn->prepare($sql);
    $stmt->bind_param("iddddddss", $staff_id, $basic_salary, $allowances, $overtime_rate, $nssf_tax, $paye_tax, $effective_date, $_SESSION['user_id']);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "Staff salary added successfully!";
    } else {
        $_SESSION['error'] = "Failed to add staff salary.";
    }
    
    header("Location: bursar-payroll.php");
    exit();
}

// Function to update staff salary
function handleUpdateStaffSalary() {
    global $staff_conn;
    
    $salary_id = $_POST['salary_id'];
    $basic_salary = $_POST['basic_salary'];
    $allowances = $_POST['allowances'] ?? 0;
    $overtime_rate = $_POST['overtime_rate'] ?? 0;
    $nssf_tax = $_POST['nssf_tax'] ?? 0;
    $paye_tax = $_POST['paye_tax'] ?? 0;
    $effective_date = $_POST['effective_date'];
    
    $sql = "UPDATE staff_salaries SET basic_salary = ?, allowances = ?, overtime_rate = ?, nssf_tax = ?, paye_tax = ?, effective_date = ?, updated_by = NOW() WHERE id = ?";
    
    $stmt = $staff_conn->prepare($sql);
    $stmt->bind_param("iddddddss", $basic_salary, $allowances, $overtime_rate, $nssf_tax, $paye_tax, $effective_date, $salary_id, $_SESSION['user_id']);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "Staff salary updated successfully!";
    } else {
        $_SESSION['error'] = "Failed to update staff salary.";
    }
    
    header("Location: bursar-payroll.php");
    exit();
}

// Function to process payroll
function handleProcessPayroll() {
    global $staff_conn, $students_conn;
    
    $month = $_POST['month'];
    $year = $_POST['year'];
    
    // Get all staff salaries
    $salaries_sql = "SELECT s.id, s.full_name, s.email, ss.basic_salary, ss.allowances, ss.overtime_rate, ss.nssf_tax, ss.paye_tax 
                     FROM staff s 
                     LEFT JOIN staff_salaries ss ON s.id = ss.staff_id 
                     WHERE s.status = 'Active'";
    
    $salaries_result = $staff_conn->query($salaries_sql);
    $salaries = $salaries_result->fetch_all(MYSQLI_ASSOC);
    
    $total_payroll = 0;
    $processed_count = 0;
    
    foreach ($salaries as $salary) {
        // Calculate net salary
        $net_salary = $salary['basic_salary'] + $salary['allowances'] - $salary['nssf_tax'] - $salary['paye_tax'];
        
        // Get student fees for this staff member's department
        $fees_sql = "SELECT SUM(fr.amount) as total_fees 
                     FROM fee_accounts fr 
                     JOIN students st ON fr.student_id = st.id 
                     WHERE fr.status = 'Unpaid'";
        
        $fees_result = $students_conn->query($fees_sql);
        $fees_data = $fees_result->fetch_assoc();
        $total_fees = $fees_data['total_fees'] ?? 0;
        
        // Insert payroll record
        $payroll_sql = "INSERT INTO payroll_records (staff_id, month, year, gross_salary, net_salary, total_fees_collected, net_payment, processed_by, processing_date) 
                         VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
        
        $payroll_stmt = $students_conn->prepare($payroll_sql);
        $payroll_stmt->bind_param("iiddddds", $salary['id'], $month, $year, $net_salary, $total_fees, $net_salary, $total_fees, $_SESSION['user_id']);
        
        if ($payroll_stmt->execute()) {
            $processed_count++;
        }
    }
    
    $_SESSION['success'] = "Payroll processed successfully! $processed_count staff members processed.";
    header("Location: bursar-payroll.php");
    exit();
}

// Function to generate payslips
function handleGeneratePayslips() {
    global $staff_conn;
    
    $month = $_POST['month'];
    $year = $_POST['year'];
    
    // Get payroll records
    $payroll_sql = "SELECT pr.*, s.full_name, s.email 
                    FROM payroll_records pr 
                    JOIN staff s ON pr.staff_id = s.id 
                    WHERE pr.month = ? AND pr.year = ? 
                    ORDER BY s.full_name";
    
    $payroll_stmt = $staff_conn->prepare($payroll_sql);
    $payroll_stmt->bind_param("ss", $month, $year);
    $payroll_stmt->execute();
    $payroll_result = $payroll_stmt->get_result();
    
    $payslips_generated = 0;
    
    while ($payroll = $payroll_result->fetch_assoc()) {
        // Generate payslip content
        $payslip_content = generatePayslipContent($payroll);
        
        // Save payslip
        $payslip_number = 'PAY' . str_pad($payroll['id'], 4, '0', STR_PAD_LEFT);
        $access_code = 'PAYSLIP_' . uniqid();
        
        $save_sql = "INSERT INTO generated_documents (document_type, staff_id, document_title, document_content, access_code, generation_date) 
                         VALUES (?, ?, ?, ?, ?, NOW())";
        
        $save_stmt = $students_conn->prepare($save_sql);
        $save_stmt->bind_param("sissss", 'Payslip', $payroll['staff_id'], 'Monthly Payslip - ' . $payroll['full_name'], $payslip_content, $access_code);
        
        if ($save_stmt->execute()) {
            $payslips_generated++;
        }
    }
    
    $_SESSION['success'] = "Payslips generated successfully! $payslips_generated payslips generated.";
    header("Location: bursar-payroll.php");
    exit();
}

// Function to generate payslip content
function generatePayslipContent($payroll) {
    return '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monthly Payslip - ISNM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 20px;
        }
        .payslip {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            border: 2px solid #ddd;
            border-radius: 8px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #333;
        }
        .school-info {
            text-align: center;
            margin-bottom: 20px;
        }
        .payslip-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .payslip-table th,
        .payslip-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        .payslip-table th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        .total-row {
            font-weight: bold;
            background-color: #f9f9f9;
        }
        .signature {
            margin-top: 30px;
            text-align: right;
            font-style: italic;
        }
    </style>
</head>
<body>
    <div class="payslip">
        <div class="header">
            <h2>IGANGA SCHOOL OF NURSING AND MIDWIFERY</h2>
            <h3>MONTHLY PAYSLIP</h3>
            <p>Payslip Number: ' . $payroll['payslip_number'] . '</p>
        </div>
        
        <div class="school-info">
            <p><strong>School:</strong> Iganga School of Nursing and Midwifery</p>
            <p><strong>Address:</strong> Iganga, Uganda</p>
            <p><strong>Phone:</strong> +256 XXX XXX XXX</p>
            <p><strong>Email:</strong> info@isnm.ug</p>
        </div>
        
        <table class="payslip-table">
            <thead>
                <tr>
                    <th colspan="2">EMPLOYEE INFORMATION</th>
                    <th>AMOUNT</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td colspan="2">
                        <strong>Name:</strong> ' . $payroll['full_name'] . '<br>
                        <strong>Email:</strong> ' . $payroll['email'] . '<br>
                        <strong>Staff ID:</strong> ' . $payroll['id'] . '
                    </td>
                    <td>UGX ' . number_format($payroll['gross_salary'], 2) . '</td>
                </tr>
                <tr>
                    <td colspan="2">
                        <strong>Basic Salary:</strong> UGX ' . number_format($payroll['basic_salary'], 2) . '<br>
                        <strong>Allowances:</strong> UGX ' . number_format($payroll['allowances'], 2) . '<br>
                        <strong>Overtime Rate:</strong> UGX ' . number_format($payroll['overtime_rate'], 2) . '/hour<br>
                        <strong>NSSF Tax:</strong> UGX ' . number_format($payroll['nssf_tax'], 2) . '<br>
                        <strong>PAYE Tax:</strong> UGX ' . number_format($payroll['paye_tax'], 2) . '
                    </td>
                    <td>UGX ' . number_format($payroll['net_salary'], 2) . '</td>
                </tr>
                <tr>
                    <td colspan="2">
                        <strong>Total Fees Collected:</strong> UGX ' . number_format($payroll['total_fees_collected'], 2) . '
                    </td>
                    <td>UGX ' . number_format($payroll['net_payment'], 2) . '</td>
                </tr>
                <tr class="total-row">
                    <td colspan="2"><strong>NET PAYMENT:</strong></td>
                    <td>UGX ' . number_format($payroll['net_payment'], 2) . '</td>
                </tr>
            </tbody>
        </table>
        
        <div class="signature">
            <p><strong>Generated on:</strong> ' . date('Y-m-d H:i:s') . '</p>
            <p><strong>Generated by:</strong> ' . $_SESSION['full_name'] . '</p>
            <p><em>This is an electronically generated payslip and is valid without signature.</em></p>
        </div>
    </div>
</body>
</html>';
}

// Get current payroll data
$payroll_sql = "SELECT COUNT(*) as total FROM payroll_records WHERE month = ? AND year = ?";
$payroll_stmt = $staff_conn->prepare($payroll_sql);
$payroll_stmt->bind_param("ss", date('m'), date('Y'));
$payroll_stmt->execute();
$payroll_result = $payroll_stmt->get_result();
$payroll_data = $payroll_result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bursar Payroll Management - ISNM</title>
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
                <h3>Bursar Payroll Management</h3>
            </div>
        </div>
        
        <div class="dashboard-grid">
            <!-- Add Staff Salary Form -->
            <div class="panel-3d">
                <h3><i class="fas fa-money-bill-wave me-2"></i>Add Staff Salary</h3>
                <form method="POST">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="staff_id">Staff Member:</label>
                            <select class="form-control" id="staff_id" name="staff_id" required>
                                <option value="">Select Staff Member</option>
                                <?php
                                $staff_sql = "SELECT id, full_name, position FROM staff WHERE status = 'Active' ORDER BY full_name";
                                $staff_result = $staff_conn->query($staff_sql);
                                
                                while ($staff = $staff_result->fetch_assoc()) {
                                    echo '<option value="' . $staff['id'] . '">' . htmlspecialchars($staff['full_name']) . ' - ' . htmlspecialchars($staff['position']) . '</option>';
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="basic_salary">Basic Salary (UGX):</label>
                            <input type="number" class="form-control" id="basic_salary" name="basic_salary" step="0.01" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="allowances">Allowances (UGX):</label>
                            <input type="number" class="form-control" id="allowances" name="allowances" step="0.01" value="0">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="overtime_rate">Overtime Rate (UGX/hour):</label>
                            <input type="number" class="form-control" id="overtime_rate" name="overtime_rate" step="0.01" value="0">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="nssf_tax">NSSF Tax (UGX):</label>
                            <input type="number" class="form-control" id="nssf_tax" name="nssf_tax" step="0.01" value="0">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="paye_tax">PAYE Tax (UGX):</label>
                            <input type="number" class="form-control" id="paye_tax" name="paye_tax" step="0.01" value="0">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="effective_date">Effective Date:</label>
                            <input type="date" class="form-control" id="effective_date" name="effective_date" required>
                        </div>
                        <div class="col-md-12">
                            <button type="submit" name="action" value="add_staff_salary" class="btn-3d">Add Salary</button>
                        </div>
                    </div>
                </form>
            </div>
            
            <!-- Process Payroll Form -->
            <div class="panel-3d">
                <h3><i class="fas fa-calculator me-2"></i>Process Payroll</h3>
                <form method="POST">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="month">Month:</label>
                            <select class="form-control" id="month" name="month" required>
                                <option value="">Select Month</option>
                                <?php for ($m = 1; $m <= 12; $m++): ?>
                                    <option value="<?php echo str_pad($m, 2, '0', STR_PAD_LEFT); ?>"><?php echo date('F', mktime(0, 0, 0, $m, date('Y'))); ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="year">Year:</label>
                            <select class="form-control" id="year" name="year" required>
                                <option value="">Select Year</option>
                                <?php for ($y = date('Y'); $y >= date('Y') - 5; $y--): ?>
                                    <option value="<?php echo $y; ?>"><?php echo $y; ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="col-md-12">
                            <button type="submit" name="action" value="process_payroll" class="btn-3d">Process Payroll</button>
                        </div>
                    </div>
                </form>
            </div>
            
            <!-- Generate Payslips Form -->
            <div class="panel-3d">
                <h3><i class="fas fa-file-invoice me-2"></i>Generate Payslips</h3>
                <form method="POST">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="month">Month:</label>
                            <select class="form-control" id="month" name="month" required>
                                <option value="">Select Month</option>
                                <?php for ($m = 1; $m <= 12; $m++): ?>
                                    <option value="<?php echo str_pad($m, 2, '0', STR_PAD_LEFT); ?>"><?php echo date('F', mktime(0, 0, 0, $m, date('Y'))); ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="year">Year:</label>
                            <select class="form-control" id="year" name="year" required>
                                <option value="">Select Year</option>
                                <?php for ($y = date('Y'); $y >= date('Y') - 5; $y--): ?>
                                    <option value="<?php echo $y; ?>"><?php echo $y; ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="col-md-12">
                            <button type="submit" name="action" value="generate_payslips" class="btn-3d">Generate Payslips</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        // Initialize dashboard with 3D effects
        document.addEventListener('DOMContentLoaded', function() {
            // Add 3D card animations
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

