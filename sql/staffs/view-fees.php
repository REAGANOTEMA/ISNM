<?php
/**
 * ISNM Student Fees Statement
 * Provides a detailed financial breakdown for a specific student.
 */

require_once '../../auth-service.php';
require_once '../../config/database.php';
require_once '../../includes/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Security Check: Authenticated and Authorized (Staff with profile viewing rights)
if (!$auth_service->isAuthenticated() || !$auth_service->canSearchStudentProfiles($_SESSION['role'])) {
    $_SESSION['error'] = "Unauthorized access to financial records.";
    header('Location: ../staff-login.php');
    exit();
}

$studentId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($studentId <= 0) {
    die("Invalid Student ID.");
}

try {
    $conn = getStudentsConnection();
    
    // 2. Fetch Student Basic Info
    $stmt = $conn->prepare("SELECT full_name, student_number, registration_number, program, current_year FROM students WHERE id = ?");
    $stmt->bind_param("i", $studentId);
    if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
    $student = $stmt->get_result()->fetch_assoc();
    
    if (!$student) {
        die("Student record not found.");
    }

    // 3. Fetch All Fee Records
    $stmt = $conn->prepare("SELECT * FROM student_fees WHERE student_id = ? ORDER BY due_date DESC, created_at DESC");
    $stmt->bind_param("i", $studentId);
    if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
    $feesResult = $stmt->get_result();
    
    $fees = [];
    $totalBilled = 0;
    $totalPaid = 0;
    
    while ($row = $feesResult->fetch_assoc()) {
        $fees[] = $row;
        $totalBilled += $row['amount'];
        if ($row['status'] === 'Paid') {
            $totalPaid += $row['amount'];
        }
        // Note: For 'Partially Paid', logic would depend on a 'paid_amount' column 
        // which isn't in the current schema. Treating 'Paid' as full for this statement.
    }
    
    $outstandingBalance = $totalBilled - $totalPaid;

} catch (Exception $e) {
    die("System Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fee Statement - <?php echo htmlspecialchars($student['full_name']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body { background-color: #f8f9fa; font-family: 'Segoe UI', sans-serif; }
        .statement-card { border: none; border-radius: 15px; box-shadow: 0 4px 20px rgba(0,0,0,0.05); }
        .table thead { background-color: #1A237E; color: white; }
        .summary-box { border-radius: 10px; padding: 20px; color: white; height: 100%; }
        .bg-billed { background: linear-gradient(135deg, #3949AB, #5C6BC0); }
        .bg-paid { background: linear-gradient(135deg, #2E7D32, #43A047); }
        .bg-balance { background: linear-gradient(135deg, #C62828, #E53935); }
        @media print {
            .no-print { display: none; }
            body { background-color: white; }
            .statement-card { box-shadow: none; border: 1px solid #dee2e6; }
        }
    </style>
</head>
<body>

<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4 no-print">
        <a href="view-student.php?id=<?php echo $studentId; ?>" class="btn btn-outline-primary">
            <i class="bi bi-arrow-left"></i> Back to Profile
        </a>
        <a href="export-fees-pdf.php?id=<?php echo $studentId; ?>" class="btn btn-danger ms-2">
            <i class="bi bi-file-earmark-pdf"></i> Export PDF
        </a>
        <button onclick="window.print()" class="btn btn-dark">
            <i class="bi bi-printer"></i> Print Statement
        </button>
    </div>

    <div class="card statement-card">
        <div class="card-body p-5">
            <!-- Header -->
            <div class="row mb-5">
                <div class="col-md-7">
                    <img src="../images/school-logo.png" alt="ISNM Logo" style="height: 60px;" class="mb-3">
                    <h3 class="fw-bold">Student Fee Statement</h3>
                    <p class="text-muted">Generated on <?php echo date('F d, Y'); ?></p>
                </div>
                <div class="col-md-5 text-md-end">
                    <h5 class="fw-bold"><?php echo htmlspecialchars($student['full_name']); ?></h5>
                    <p class="mb-1"><strong>Reg No:</strong> <?php echo htmlspecialchars($student['registration_number']); ?></p>
                    <p class="mb-1"><strong>Student No:</strong> <?php echo htmlspecialchars($student['student_number']); ?></p>
                    <p class="mb-0"><?php echo htmlspecialchars($student['program']); ?> - Year <?php echo $student['current_year']; ?></p>
                </div>
            </div>

            <!-- Summary Tiles -->
            <div class="row g-4 mb-5">
                <div class="col-md-4">
                    <div class="summary-box bg-billed shadow-sm">
                        <small class="text-uppercase opacity-75">Total Billed</small>
                        <h2 class="fw-bold mb-0">UGX <?php echo number_format($totalBilled); ?></h2>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="summary-box bg-paid shadow-sm">
                        <small class="text-uppercase opacity-75">Total Paid</small>
                        <h2 class="fw-bold mb-0">UGX <?php echo number_format($totalPaid); ?></h2>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="summary-box bg-balance shadow-sm">
                        <small class="text-uppercase opacity-75">Balance Due</small>
                        <h2 class="fw-bold mb-0">UGX <?php echo number_format($outstandingBalance); ?></h2>
                    </div>
                </div>
            </div>

            <!-- Ledger Table -->
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Fee Type</th>
                            <th>Receipt/Ref</th>
                            <th class="text-end">Amount</th>
                            <th class="text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($fees) > 0): ?>
                            <?php foreach ($fees as $fee): ?>
                                <tr>
                                    <td><?php echo date('M d, Y', strtotime($fee['created_at'])); ?></td>
                                    <td>
                                        <div class="fw-bold"><?php echo htmlspecialchars($fee['fee_type']); ?></div>
                                        <small class="text-muted">Due: <?php echo date('M d, Y', strtotime($fee['due_date'])); ?></small>
                                    </td>
                                    <td><code class="text-primary"><?php echo htmlspecialchars($fee['receipt_number'] ?? 'N/A'); ?></code></td>
                                    <td class="text-end fw-bold">UGX <?php echo number_format($fee['amount']); ?></td>
                                    <td class="text-center">
                                        <span class="badge <?php 
                                            echo $fee['status'] === 'Paid' ? 'bg-success' : 
                                                ($fee['status'] === 'Overdue' ? 'bg-danger' : 'bg-warning text-dark'); 
                                        ?>">
                                            <?php echo $fee['status']; ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">No fee records found for this student.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <p class="text-center text-muted small mt-4">Iganga School of Nursing and Midwifery - Financial Records Division</p>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>