<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../includes/staff_dashboard_access.php';
$ctx = bootstrapStaffDashboard([]);
$conn = $ctx['staff'];
$user = $ctx['user'];

$staff_id = $user['id'] ?? 0;
$staff_email = $user['email'] ?? '';
$staff_role = $user['role'] ?? '';

// Check if user has permission to generate receipts
$can_generate_receipts = false;
if (stripos($staff_role, 'Director') !== false ||
    stripos($staff_role, 'General') !== false ||
    stripos($staff_role, 'Bursar') !== false ||
    stripos($staff_role, 'Principal') !== false) {
    $can_generate_receipts = true;
}

if (!$can_generate_receipts) {
    $_SESSION['error'] = "You don't have permission to generate receipts.";
    header("Location: staff-login.php");
    exit();
}

// Handle receipt generation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate_receipt'])) {
    $receipt_type = $_POST['receipt_type'] ?? 'Fee Payment';
    $student_id = $_POST['student_id'] ?? 0;
    $amount = $_POST['amount'] ?? 0;
    $payment_method = $_POST['payment_method'] ?? '';
    $description = $_POST['description'] ?? '';
    
    // Generate receipt number
    $receipt_number = 'REC' . date('YmdHis') . str_pad($staff_id, 4, '0', STR_PAD_LEFT);
    
    // Get template
    $template_sql = "SELECT template_content FROM receipt_templates WHERE template_type = ? AND is_active = TRUE LIMIT 1";
    $template_stmt = $conn->prepare($template_sql);
    if (!$template_stmt) {
        $_SESSION['error'] = 'Database error: template prepare failed.';
        header("Location: staff_receipt_printing.php");
        exit();
    }
    $template_stmt->bind_param("s", $receipt_type);
    $template_stmt->execute();
    $template_result = $template_stmt->get_result();
    $template = ($template_result) ? $template_result->fetch_assoc() : null;
    
    if ($template) {
        // Replace template variables
        $content = $template['template_content'];
        $content = str_replace('{{receipt_number}}', $receipt_number, $content);
        $content = str_replace('{{student_name}}', $_POST['student_name'] ?? 'Student', $content);
        $content = str_replace('{{amount}}', number_format($amount, 2), $content);
        $content = str_replace('{{date}}', date('Y-m-d H:i:s'), $content);
        $content = str_replace('{{payment_method}}', $payment_method, $content);
        $content = str_replace('{{generated_by}}', $_SESSION['full_name'] ?? 'Staff Member', $content);
        
        // Save generated receipt
        $save_sql = "INSERT INTO generated_documents (document_type, student_id, generated_by, document_title, document_content, generation_date, access_code) 
                        VALUES (?, ?, ?, ?, ?, NOW(), ?)";
        $save_stmt = $conn->prepare($save_sql);
        if (!$save_stmt) {
            $_SESSION['error'] = 'Database error: save prepare failed.';
            header("Location: staff_receipt_printing.php");
            exit();
        }
        $access_code = 'REC_' . uniqid();
        $save_stmt->bind_param("sissss", $receipt_type, $student_id, $staff_id, 'Receipt #' . $receipt_number, $content, $access_code);
        
        if ($save_stmt->execute()) {
            $_SESSION['success'] = "Receipt generated successfully! Receipt Number: $receipt_number";
        } else {
            $_SESSION['error'] = "Failed to generate receipt.";
        }
    }
    
    header("Location: staff_receipt_printing.php");
    exit();
}

// Get receipt templates
$templates_sql = "SELECT * FROM receipt_templates WHERE is_active = TRUE ORDER BY template_name";
$templates_result = $conn->query($templates_sql);
$templates = ($templates_result) ? $templates_result->fetch_all(MYSQLI_ASSOC) : [];

// Get recent receipts
$receipts_sql = "SELECT gd.*, s.full_name as generated_by_name, st.full_name as student_name 
                 FROM generated_documents gd 
                 JOIN staff s ON gd.generated_by = s.id 
                 LEFT JOIN students st ON gd.student_id = st.id 
                 WHERE gd.document_type = 'Receipt' 
                 ORDER BY gd.generation_date DESC 
                 LIMIT 10";
$receipts_result = $conn->query($receipts_sql);
$receipts = ($receipts_result) ? $receipts_result->fetch_all(MYSQLI_ASSOC) : [];
?>

<!DOCTYPE html>
<html lang="en">
<head>
<?php include_once __DIR__ . '/../includes/dashboard_head.php'; ?>
</head>
<body>
<?php include_once __DIR__ . '/../includes/sidebar.php'; ?>
    <div class="receipt-container" style="margin-left:270px">
        <div class="receipt-header">
            <h2><i class="fas fa-receipt me-2"></i>Receipt Printing System</h2>
            <p>Generate and print professional receipts for students</p>
            <div class="text-center mb-3">
                <a href="../student-directory.php" class="btn btn-sm btn-outline-info me-1"><i class="fas fa-address-book me-1"></i>Directory</a>
                <a href="../store_request.php" class="btn btn-sm btn-outline-warning me-1"><i class="fas fa-shopping-cart me-1"></i>Store</a>
                <a href="../news.php" class="btn btn-sm btn-outline-secondary me-1"><i class="fas fa-newspaper me-1"></i>News</a>
            </div>
        </div>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger">
                <?php 
                    echo htmlspecialchars($_SESSION['error']);
                    unset($_SESSION['error']);
                    ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <?php 
                    echo htmlspecialchars($_SESSION['success']);
                    unset($_SESSION['success']);
                    ?>
            </div>
        <?php endif; ?>
        
        <div class="receipt-form">
            <h4>Generate New Receipt</h4>
            <form method="POST">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="receipt_type" class="form-label">Receipt Type:</label>
                        <select class="form-control" id="receipt_type" name="receipt_type" required>
                            <option value="Fee Payment">Fee Payment</option>
                            <option value="Registration">Registration</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="student_id" class="form-label">Student ID:</label>
                        <input type="number" class="form-control" id="student_id" name="student_id" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="amount" class="form-label">Amount (UGX):</label>
                        <input type="number" class="form-control" id="amount" name="amount" step="0.01" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="payment_method" class="form-label">Payment Method:</label>
                        <input type="text" class="form-control" id="payment_method" name="payment_method" required>
                    </div>
                    <div class="col-md-12 mb-3">
                        <label for="description" class="form-label">Description:</label>
                        <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                    </div>
                    <div class="col-md-12 mb-3">
                        <label for="student_name" class="form-label">Student Name:</label>
                        <input type="text" class="form-control" id="student_name" name="student_name" required>
                    </div>
                    <div class="col-md-12">
                        <button type="submit" name="generate_receipt" class="btn-primary">Generate Receipt</button>
                    </div>
                </div>
            </form>
        </div>
        
        <?php if (count($receipts) > 0): ?>
            <h4>Recent Receipts</h4>
            <table class="table">
                <thead>
                    <tr>
                        <th>Receipt Number</th>
                        <th>Student</th>
                        <th>Amount</th>
                        <th>Generated By</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($receipts as $receipt): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($receipt['access_code']); ?></td>
                            <td><?php echo htmlspecialchars($receipt['student_name']); ?></td>
                            <td>UGX <?php echo number_format($receipt['amount'], 2); ?></td>
                            <td><?php echo htmlspecialchars($receipt['generated_by_name']); ?></td>
                            <td><?php echo htmlspecialchars($receipt['generation_date']); ?></td>
                            <td>
                                <button class="print-btn no-print" onclick="printReceipt('<?php echo $receipt['access_code']; ?>')">
                                    <i class="fas fa-print"></i> Print
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
    
    <script>
        function printReceipt(accessCode) {
            // Open receipt in new window for printing
            window.open('view_receipt.php?code=' + accessCode, '_blank', 'width=800,height=600');
        }
    </script>
<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
</body>
</html>

