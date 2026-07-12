<?php
require_once __DIR__ . '/includes/student_auth.php';
require_once __DIR__ . '/includes/financial_functions.php';

$conn = getConnection();
$student_id = $_SESSION['user_id'] ?? 0;
$invoice_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$statement = generateFinancialStatement($student_id);
$balance = getStudentBalance($student_id);

// Get student info
$student = $conn->query("SELECT full_name, student_number, registration_number, program FROM students WHERE id = $student_id LIMIT 1")->fetch_assoc();

// Get specific invoice if id provided
$invoice = null;
if ($invoice_id) {
    $stmt = $conn->prepare("SELECT * FROM student_invoices WHERE id = ? AND student_id = ? LIMIT 1");
    $stmt->bind_param("ii", $invoice_id, $student_id);
    if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
    $invoice = $stmt->get_result()->fetch_assoc();
}

$total_invoiced = $statement['total_invoiced'];
$total_paid = $statement['total_paid'];
$current_balance = $balance;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fee Statement | ISNM</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        @media print {
            .no-print { display: none !important; }
            body { background: #fff; }
            .statement-container { box-shadow: none !important; padding: 0 !important; }
        }
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: #f0f2f5;
        }
        .statement-container {
            max-width: 900px;
            margin: 20px auto;
            background: #fff;
            padding: 40px;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        }
        .header-row {
            display: flex;
            align-items: center;
            gap: 20px;
            padding-bottom: 20px;
            border-bottom: 3px solid #1a237e;
            margin-bottom: 24px;
        }
        .header-row img {
            height: 70px;
            width: auto;
        }
        .school-details {
            flex: 1;
        }
        .school-details h2 {
            font-size: 1.5rem;
            font-weight: 800;
            color: #1a237e;
            margin: 0;
        }
        .school-details p {
            font-size: 0.85rem;
            color: #64748b;
            margin: 2px 0;
        }
        .doc-title {
            text-align: center;
            font-size: 1.2rem;
            font-weight: 700;
            color: #1a237e;
            margin-bottom: 24px;
            padding: 10px;
            background: #f8fafc;
            border-radius: 8px;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
            padding: 16px;
            background: #f8fafc;
            border-radius: 10px;
        }
        .info-row .col p { margin: 3px 0; font-size: 0.9rem; }
        .info-row .col p strong { color: #1a237e; }
        .summary-row {
            display: flex;
            gap: 16px;
            margin-bottom: 24px;
        }
        .summary-card {
            flex: 1;
            padding: 20px;
            border-radius: 12px;
            text-align: center;
            color: #fff;
        }
        .summary-card .label { font-size: 0.8rem; opacity: 0.9; text-transform: uppercase; letter-spacing: 0.5px; }
        .summary-card .value { font-size: 1.5rem; font-weight: 800; margin-top: 4px; }
        .bg-primary-custom { background: linear-gradient(135deg, #1a237e, #3949ab); }
        .bg-success-custom { background: linear-gradient(135deg, #2e7d32, #43a047); }
        .bg-danger-custom { background: linear-gradient(135deg, #c62828, #e53935); }
        table { width: 100%; border-collapse: collapse; }
        th {
            background: #f1f5f9;
            padding: 12px 10px;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            color: #475569;
            border-bottom: 2px solid #e2e8f0;
        }
        td {
            padding: 12px 10px;
            border-bottom: 1px solid #f1f5f9;
            font-size: 0.9rem;
        }
        .text-right { text-align: right; }
        .badge-status {
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .badge-paid { background: #d4edda; color: #155724; }
        .badge-pending { background: #fff3cd; color: #856404; }
        .badge-overdue { background: #f8d7da; color: #721c24; }
        .badge-partial { background: #cce5ff; color: #004085; }
        .footer-note {
            text-align: center;
            font-size: 0.8rem;
            color: #94a3b8;
            margin-top: 30px;
            padding-top: 16px;
            border-top: 1px solid #e2e8f0;
        }
        .print-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 24px;
            background: linear-gradient(135deg, #1a237e, #3949ab);
            color: #fff;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
        }
        .print-btn:hover { background: linear-gradient(135deg, #0d1442, #1a237e); }
    </style>
</head>
<body>
    <div class="no-print text-center mt-3">
        <button class="print-btn" onclick="window.print()"><i class="fas fa-print"></i> Print Statement</button>
        <button class="print-btn" style="background: #64748b" onclick="window.close()"><i class="fas fa-times"></i> Close</button>
    </div>

    <div class="statement-container" id="statement">
        <!-- Header with School Logo -->
        <div class="header-row">
            <img src="images/school-logo.png" alt="ISNM Logo">
            <div class="school-details">
                <h2>Iganga School of Nursing and Midwifery</h2>
                <p>P.O. Box 418, Iganga, Uganda</p>
                <p>Tel: +256 782 990 403 | Email: info@isnm.ac.ug</p>
            </div>
        </div>

        <div class="doc-title">OFFICIAL STUDENT FEE STATEMENT</div>

        <!-- Student Info -->
        <div class="info-row">
            <div class="col">
                <p><strong>Student:</strong> <?= htmlspecialchars($student['full_name'] ?? '') ?></p>
                <p><strong>Reg No:</strong> <?= htmlspecialchars($student['registration_number'] ?? '') ?></p>
            </div>
            <div class="col text-end">
                <p><strong>Student No:</strong> <?= htmlspecialchars($student['student_number'] ?? '') ?></p>
                <p><strong>Program:</strong> <?= htmlspecialchars($student['program'] ?? '') ?></p>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="summary-row">
            <div class="summary-card bg-primary-custom">
                <div class="label">Total Billed</div>
                <div class="value">UGX <?= number_format($total_invoiced) ?></div>
            </div>
            <div class="summary-card bg-success-custom">
                <div class="label">Total Paid</div>
                <div class="value">UGX <?= number_format($total_paid) ?></div>
            </div>
            <div class="summary-card bg-danger-custom">
                <div class="label">Balance</div>
                <div class="value">UGX <?= number_format($current_balance) ?></div>
            </div>
        </div>

        <?php if ($invoice): ?>
        <!-- Single Invoice Detail -->
        <div class="info-row" style="background:#e8eaf6">
            <div class="col">
                <p><strong>Invoice:</strong> <?= htmlspecialchars($invoice['invoice_number']) ?></p>
                <p><strong>Academic Year:</strong> <?= htmlspecialchars($invoice['academic_year']) ?> | <strong>Semester:</strong> <?= $invoice['semester'] ?></p>
            </div>
            <div class="col text-end">
                <p><strong>Amount:</strong> UGX <?= number_format($invoice['total_amount']) ?></p>
                <p><strong>Paid:</strong> UGX <?= number_format($invoice['amount_paid']) ?> | <strong>Balance:</strong> UGX <?= number_format($invoice['balance']) ?></p>
            </div>
        </div>
        <?php endif; ?>

        <!-- Invoice Ledger -->
        <h5 style="color:#1a237e;font-weight:700;margin-bottom:12px">Invoice History</h5>
        <table>
            <thead>
                <tr>
                    <th>Invoice</th>
                    <th>Year/Sem</th>
                    <th>Amount</th>
                    <th>Paid</th>
                    <th>Balance</th>
                    <th class="text-right">Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($statement['invoices'] as $inv): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($inv['invoice_number']) ?></strong></td>
                    <td><?= htmlspecialchars($inv['academic_year']) ?>/<?= $inv['semester'] ?></td>
                    <td>UGX <?= number_format($inv['total_amount']) ?></td>
                    <td>UGX <?= number_format($inv['amount_paid']) ?></td>
                    <td>UGX <?= number_format($inv['balance']) ?></td>
                    <td class="text-right"><span class="badge-status badge-<?= $inv['status'] ?>"><?= ucfirst($inv['status']) ?></span></td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($statement['invoices'])): ?>
                <tr><td colspan="6" class="text-center text-muted">No invoice records found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- Payment History -->
        <h5 style="color:#1a237e;font-weight:700;margin:24px 0 12px">Payment History</h5>
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Reference</th>
                    <th>Method</th>
                    <th class="text-right">Amount</th>
                    <th class="text-right">Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($statement['payments'] as $pay): ?>
                <tr>
                    <td><?= date('M j, Y', strtotime($pay['payment_date'])) ?></td>
                    <td><?= htmlspecialchars($pay['payment_reference'] ?? '-') ?></td>
                    <td><?= ucfirst(str_replace('_', ' ', $pay['payment_method'] ?? '-')) ?></td>
                    <td class="text-right">UGX <?= number_format($pay['amount']) ?></td>
                    <td class="text-right"><span class="badge-status badge-<?= $pay['status'] === 'approved' ? 'paid' : 'pending' ?>"><?= ucfirst($pay['status']) ?></span></td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($statement['payments'])): ?>
                <tr><td colspan="5" class="text-center text-muted">No payment records found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>

        <div class="footer-note">
            Generated on <?= date('F j, Y H:i') ?> | Iganga School of Nursing and Midwifery<br>
            This is an automated system-generated document. No physical signature required.
        </div>
    </div>
</body>
</html>
