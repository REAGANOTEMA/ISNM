<?php
/**
 * Student Financial Self-Service Portal
 */
require_once __DIR__ . '/includes/student_auth.php';
require_once __DIR__ . '/includes/financial_functions.php';

$student_id = $_SESSION['user_id'] ?? 0;

// Get student's financial statement
$statement = generateFinancialStatement($student_id);
$balance = getStudentBalance($student_id);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Financial Portal | ISNM</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <style>
        :root {
            --isnm-blue: #1e3a8a;
            --isnm-green: #059669;
        }
        .balance-card {
            background: linear-gradient(135deg, var(--isnm-blue), #3b82f6);
            color: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 20px;
        }
        .balance-amount {
            font-size: 2.5rem;
            font-weight: bold;
        }
        .invoice-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .status-paid { background: #d4edda; color: #155724; }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-overdue { background: #f8d7da; color: #721c24; }
        .payment-methods img {
            height: 40px;
            margin-right: 10px;
        }
    </style>
</head>
<body>
    <?php include_once 'includes/sidebars/student_mgt_sidebar.php'; ?>
    <div class="container py-4">
        <!-- Header with School Logo -->
        <div class="text-center mb-4">
            <img src="images/school-logo.png" alt="ISNM Logo" height="80">
            <h2 class="mt-2">Iganga School of Nursing and Midwifery</h2>
            <p class="text-muted">Student Financial Self Service Portal</p>
        </div>
        
        <div class="row">
            <div class="col-md-4">
                <!-- Balance Card -->
                <div class="balance-card text-center">
                    <h5>Current Outstanding Balance</h5>
                    <div class="balance-amount">UGX <?php echo number_format($balance); ?></div>
                    <p class="mt-2">Student ID: <?php echo htmlspecialchars($_SESSION['index_number'] ?? ''); ?></p>
                    <?php if ($balance > 0): ?>
                    <button class="btn btn-light mt-3" onclick="showPaymentModal()">
                        <i class="fas fa-credit-card"></i> Make Online Payment
                    </button>
                    <?php endif; ?>
                </div>
                
                <!-- Payment Methods -->
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-mobile-alt"></i> Payment Methods</h5>
                    </div>
                    <div class="card-body payment-methods">
                        <?php $payment_providers = getPaymentProviders(); ?>
                        <?php foreach ($payment_providers as $key => $p): ?>
                        <div class="d-flex align-items-center mb-3">
                            <img src="<?= htmlspecialchars($p['logo']) ?>" alt="<?= htmlspecialchars($p['short']) ?>" style="height: 32px; background: #fff; border-radius: 6px; padding: 4px; margin-right: 10px; object-fit: contain;">
                            <div>
                                <strong><?= htmlspecialchars($p['name']) ?></strong><br>
                                <small>Pay via <?= htmlspecialchars($p['short']) ?></small>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            
            <div class="col-md-8">
                <!-- Invoices List -->
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-file-invoice"></i> My Invoices</h5>
                    </div>
                    <div class="card-body">
                        <?php foreach ($statement['invoices'] as $invoice): ?>
                        <div class="invoice-card">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <strong><?php echo htmlspecialchars($invoice['invoice_number']); ?></strong>
                                    <div class="text-muted">
                                        Academic Year: <?php echo htmlspecialchars($invoice['academic_year']); ?> | 
                                        Semester: <?php echo htmlspecialchars($invoice['semester']); ?>
                                    </div>
                                </div>
                                <div class="text-end">
                                    <div><strong>UGX <?php echo number_format($invoice['total_amount']); ?></strong></div>
                                    <span class="badge status-<?php echo htmlspecialchars($invoice['status']); ?>">
                                        <?php echo ucfirst(htmlspecialchars($invoice['status'])); ?>
                                    </span>
                                </div>
                            </div>
                            <div class="mt-2">
                                <div class="row">
                                    <div class="col-6">
                                        <small>Amount Paid: UGX <?php echo number_format($invoice['amount_paid']); ?></small>
                                    </div>
                                    <div class="col-6 text-end">
                                        <small>Balance: UGX <?php echo number_format($invoice['balance']); ?></small>
                                    </div>
                                </div>
                                <div class="progress mt-2" style="height: 5px;">
                                    <?php $percent = ($invoice['total_amount'] > 0) ? ($invoice['amount_paid'] / $invoice['total_amount']) * 100 : 0; ?>
                                    <div class="progress-bar" style="width: <?php echo $percent; ?>%"></div>
                                </div>
                            </div>
                            <div class="mt-2">
                                <button class="btn btn-sm btn-outline-primary" onclick="viewInvoice(<?php echo $invoice['id']; ?>)">
                                    <i class="fas fa-eye"></i> View
                                </button>
                                <button class="btn btn-sm btn-outline-success" onclick="downloadReceipt(<?php echo $invoice['id']; ?>)">
                                    <i class="fas fa-download"></i> Download Receipt
                                </button>
                                <button class="btn btn-sm btn-outline-info" onclick="printStatement(<?php echo $invoice['id']; ?>)">
                                    <i class="fas fa-print"></i> Print
                                </button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <!-- Payment History -->
                <div class="card mt-4">
                    <div class="card-header bg-secondary text-white">
                        <h5 class="mb-0"><i class="fas fa-history"></i> Payment History</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Amount</th>
                                        <th>Method</th>
                                        <th>Reference</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($statement['payments'] as $payment): ?>
                                    <tr>
                                        <td><?php echo date('M j, Y', strtotime($payment['payment_date'])); ?></td>
                                        <td>UGX <?php echo number_format($payment['amount']); ?></td>
                                        <td>
                                            <?php
                                                $pm = strtolower($payment['payment_method'] ?? '');
                                                $pp = strtolower($payment['payment_provider'] ?? '');
                                                $logo_provider = $pp ?: $pm;
                                            ?>
                                            <?= renderPaymentProviderLogo($logo_provider, 20, false) ?>
                                            <?= htmlspecialchars(getPaymentProviderName($logo_provider)) ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($payment['reference_number'] ?? '-'); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $payment['status'] === 'approved' ? 'success' : 'warning'; ?>">
                                                <?php echo ucfirst(htmlspecialchars($payment['status'])); ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Payment Modal -->
    <div class="modal fade" id="paymentModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Make Online Payment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form>
                        <div class="mb-3">
                            <label class="form-label">Payment Amount (UGX)</label>
                            <input type="number" class="form-control" value="<?php echo $balance; ?>" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Payment Method</label>
                            <select class="form-control" id="paymentMethod">
                                <option value="mtn_momo">MTN Mobile Money</option>
                                <option value="airtel_money">Airtel Money</option>
                                <option value="bank">Bank Transfer</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Phone Number</label>
                            <input type="tel" class="form-control" placeholder="Enter phone number" pattern="07[0-9]{8}">
                        </div>
                        <button type="button" class="btn btn-primary w-100" onclick="processPayment()">
                            <i class="fas fa-lock"></i> Pay Now
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function showPaymentModal() {
            new bootstrap.Modal(document.getElementById('paymentModal')).show();
        }
        
        function processPayment() {
            alert('Payment processing will be integrated with mobile money APIs.');
        }
        
        function viewInvoice(id) {
            window.open('view_invoice.php?id=' + id, '_blank');
        }
        
        function downloadReceipt(id) {
            window.location.href = 'download_receipt.php?id=' + id;
        }
        
        function printStatement(id) {
            window.open('print_statement.php?id=' + id, '_blank');
        }
    </script>
    <?php include_once 'includes/enterprise_control_panel.php'; ?>
</body>
</html>