<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../auth-service.php';

// Database connection
$conn = getStaffConnection();

// Start secure session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Global authentication service
$auth_service = new AuthenticationService();

// Check authentication
if (!$auth_service->isAuthenticated()) {
    header('Location: staff-login.php');
    exit();
}

// Get user information
$staff_id = $_SESSION['user_id'] ?? 0;
$staff_email = $_SESSION['email'] ?? '';

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
    $template_stmt->bind_param("s", $receipt_type);
    $template_stmt->execute();
    $template_result = $template_stmt->get_result();
    $template = $template_result->fetch_assoc();
    
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
$templates = $templates_result->fetch_all(MYSQLI_ASSOC);

// Get recent receipts
$receipts_sql = "SELECT gd.*, s.full_name as generated_by_name, st.full_name as student_name 
                 FROM generated_documents gd 
                 JOIN staff s ON gd.generated_by = s.id 
                 LEFT JOIN students st ON gd.student_id = st.id 
                 WHERE gd.document_type = 'Receipt' 
                 ORDER BY gd.generation_date DESC 
                 LIMIT 10";
$receipts_result = $conn->query($receipts_sql);
$receipts = $receipts_result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt Printing - ISNM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .receipt-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            padding: 30px;
            max-width: 800px;
            margin: 0 auto;
        }
        
        .receipt-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .receipt-form {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
        }
        
        .receipt-preview {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
            display: none;
        }
        
        .btn-primary {
            background: #007bff;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
        }
        
        .btn-primary:hover {
            background: #0056b3;
        }
        
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        .table th, .table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #dee2e6;
        }
        
        .table th {
            background: #f8f9fa;
            font-weight: bold;
        }
        
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        
        .alert-danger {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        
        .print-btn {
            background: #28a745;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
        }
        
        @media print {
            body {
                background: white;
                padding: 0;
            }
            
            .receipt-container {
                box-shadow: none;
                max-width: 100%;
                margin: 0;
                padding: 20px;
            }
            
            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="receipt-container">
        <div class="receipt-header">
            <h2><i class="fas fa-receipt me-2"></i>Receipt Printing System</h2>
            <p>Generate and print professional receipts for students</p>
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
</body>
</html>

