<?php
/**
 * Student Bank Statement / Financial Statement Printer
 * Called from bursar dashboard to print per-student statements
 */
require_once __DIR__ . '/includes/staff_dashboard_access.php';
require_once __DIR__ . '/includes/csrf_helper.php';

$ctx = bootstrapStaffDashboard(['bursar', 'school bursar', 'finance', 'director finance', 'director general', 'ceo']);
$stuConn = $ctx['students'];
$staffConn = $ctx['staff'];
$user = $ctx['user'];
$studentsDb = defined('STUDENTS_DB_NAME') ? STUDENTS_DB_NAME : 'igangaschool_students';

$studentId = (int)($_GET['student_id'] ?? $_POST['student_id'] ?? 0);
$format = $_GET['format'] ?? 'print'; // print or pdf

if (!$studentId || !$stuConn) {
    die('<div class="alert alert-danger">Invalid student ID or database connection failed.</div>');
}

// Fetch student info
$stu = null;
$stmt = $stuConn->prepare("SELECT * FROM `$studentsDb`.`students` WHERE id=? LIMIT 1");
if ($stmt) {
    $stmt->bind_param("i", $studentId);
    $stmt->execute();
    $stu = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}
if (!$stu) die('<div class="alert alert-danger">Student not found.</div>');

$stuName = $stu['full_name'] ?? ($stu['first_name'] . ' ' . $stu['surname']);
$stuNumber = $stu['student_number'] ?? '';
$stuProgram = $stu['program'] ?? $stu['course'] ?? '';
$stuRegNum = $stu['registration_number'] ?? '';
$stuIndexNum = $stu['index_number'] ?? '';
$stuYear = $stu['year'] ?? '';
$stuLevel = $stu['level'] ?? '';

// Fetch payments
$payments = [];
$r = $stuConn->query("SELECT * FROM payments WHERE student_id=" . intval($studentId) . " ORDER BY payment_date ASC, created_at ASC");
if ($r) while ($row = $r->fetch_assoc()) $payments[] = $row;

// Fetch fee structures / assignments
$fees = [];
$r = $stuConn->query("SELECT * FROM student_fees WHERE student_id=" . intval($studentId) . " ORDER BY created_at ASC");
if ($r) while ($row = $r->fetch_assoc()) $fees[] = $row;

// Calculate totals
$totalPaid = 0;
foreach ($payments as $p) {
    if (in_array($p['status'] ?? '', ['Completed', 'verified', 'completed'])) {
        $totalPaid += (float)($p['amount_received'] ?? $p['amount'] ?? 0);
    }
}
$totalFees = 0;
foreach ($fees as $f) {
    $totalFees += (float)($f['amount'] ?? 0);
}
$balance = $totalFees - $totalPaid;

// Build statement entries
$entries = [];
foreach ($payments as $p) {
    if (in_array($p['status'] ?? '', ['Completed', 'verified', 'completed'])) {
        $entries[] = [
            'date' => $p['payment_date'] ?? $p['created_at'],
            'description' => 'Payment via ' . ($p['payment_method'] ?? $p['method'] ?? 'Unknown'),
            'reference' => $p['slip_number'] ?? $p['payment_reference'] ?? '',
            'debit' => 0,
            'credit' => (float)($p['amount_received'] ?? $p['amount'] ?? 0),
            'type' => 'credit',
        ];
    }
}
foreach ($fees as $f) {
    $entries[] = [
        'date' => $f['created_at'] ?? date('Y-m-d'),
        'description' => $f['fee_name'] ?? $f['description'] ?? 'Fee Charge',
        'reference' => $f['invoice_number'] ?? '',
        'debit' => (float)($f['amount'] ?? 0),
        'credit' => 0,
        'type' => 'debit',
    ];
}
usort($entries, fn($a, $b) => strtotime($a['date']) - strtotime($b['date']));

// Running balance
$runningBal = 0;
foreach ($entries as &$e) {
    if ($e['type'] === 'debit') $runningBal += $e['debit'];
    else $runningBal -= $e['credit'];
    $e['balance'] = $runningBal;
}
unset($e);

$schoolName = 'Iganga School of Nursing & Midwifery';
$schoolMotto = 'Excellence in Nursing Education';
$statementDate = date('d M Y');
$statementRef = 'STMT-' . date('Ymd') . '-' . str_pad($studentId, 4, '0', STR_PAD_LEFT);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Financial Statement - <?= htmlspecialchars($stuName) ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
<style>
:root{--primary:#1e40af;--accent:#f59e0b}
*{box-sizing:border-box}
body{font-family:'Segoe UI',system-ui,sans-serif;background:#f0f2f5;margin:0;padding:20px}
.statement-container{max-width:900px;margin:0 auto;background:#fff;border-radius:12px;box-shadow:0 2px 20px rgba(0,0,0,.1);overflow:hidden}
.stmt-header{background:linear-gradient(135deg,#1e3a8a,#1e40af,#2563eb);color:#fff;padding:30px;text-align:center;position:relative}
.stmt-header::after{content:'';position:absolute;bottom:0;left:0;right:0;height:4px;background:var(--accent)}
.stmt-header h1{margin:0;font-size:1.5rem;font-weight:700;letter-spacing:1px}
.stmt-header .motto{margin:4px 0 0;font-size:.85rem;opacity:.85;font-style:italic}
.stmt-header .logo{width:70px;height:70px;border-radius:50%;border:3px solid rgba(255,255,255,.3);margin:0 auto 12px;background:rgba(255,255,255,.1);display:flex;align-items:center;justify-content:center;font-size:2rem}
.stmt-meta{display:grid;grid-template-columns:1fr 1fr;gap:1px;background:#e5e7eb}
.stmt-meta-item{padding:14px 20px;background:#f8fafc}
.stmt-meta-item label{font-size:.7rem;text-transform:uppercase;letter-spacing:1px;color:#6b7280;font-weight:600;display:block;margin-bottom:2px}
.stmt-meta-item span{font-size:.92rem;font-weight:600;color:#1a1d29}
.stmt-body{padding:24px}
.stmt-table{width:100%;border-collapse:collapse}
.stmt-table th{background:#1e40af;color:#fff;padding:10px 12px;font-size:.75rem;text-transform:uppercase;letter-spacing:.5px;text-align:left;font-weight:600}
.stmt-table th:last-child,.stmt-table th:nth-child(3),.stmt-table th:nth-child(4){text-align:right}
.stmt-table td{padding:10px 12px;border-bottom:1px solid #f0f0f0;font-size:.85rem;color:#1f2937}
.stmt-table td:last-child,.stmt-table td:nth-child(3),.stmt-table td:nth-child(4){text-align:right;font-variant-numeric:tabular-nums}
.stmt-table tbody tr:hover{background:#f0f7ff}
.stmt-table .credit{color:#059669;font-weight:600}
.stmt-table .debit{color:#dc2626;font-weight:600}
.stmt-table .balance{font-weight:700}
.stmt-summary{display:grid;grid-template-columns:repeat(3,1fr);gap:1px;background:#e5e7eb;margin-top:20px;border-radius:8px;overflow:hidden}
.stmt-summary-item{padding:16px;text-align:center;background:#fff}
.stmt-summary-item .amount{font-size:1.3rem;font-weight:700}
.stmt-summary-item .label{font-size:.75rem;color:#6b7280;text-transform:uppercase;letter-spacing:.5px}
.stmt-summary-item.paid .amount{color:#059669}
.stmt-summary-item.fees .amount{color:#dc2626}
.stmt-summary-item.balance .amount{color:<?= $balance > 0 ? '#dc2626' : '#059669' ?>}
.stmt-footer{text-align:center;padding:20px;background:#f8fafc;border-top:1px solid #e5e7eb}
.stmt-footer .signature-line{width:200px;border-bottom:2px solid #1a1d29;margin:0 auto 6px}
.stmt-footer p{font-size:.8rem;color:#6b7280;margin:0}
.stmt-watermark{position:absolute;top:50%;left:50%;transform:translate(-50%,-50%) rotate(-30deg);font-size:4rem;color:rgba(0,0,0,.03);font-weight:900;pointer-events:none;white-space:nowrap}
.no-print .print-controls{display:flex;gap:10px;justify-content:center;margin-bottom:20px}
@media print{
    body{background:#fff;padding:0;margin:0}
    .statement-container{box-shadow:none;border-radius:0;max-width:100%}
    .no-print .print-controls{display:none!important}
    .no-print .stmt-header{print-color-adjust:exact;-webkit-print-color-adjust:exact}
    .no-print .stmt-table th{print-color-adjust:exact;-webkit-print-color-adjust:exact}
}
@page{size:A4;margin:15mm}
</style>
</head>
<body class="no-print">

<div class="print-controls">
    <button onclick="window.print()" class="btn btn-primary btn-sm"><i class="fas fa-print me-1"></i>Print Statement</button>
    <button onclick="window.history.back()" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left me-1"></i>Back to Dashboard</button>
</div>

<div class="statement-container">
    <div class="stmt-watermark">ISNM</div>
    <div class="stmt-header">
        <div class="logo"><img src="images/school-logo.png" alt="ISNM" onerror="this.parentElement.innerHTML='<i class=\'fas fa-graduation-cap\'></i>'" style="width:60px;height:60px;border-radius:50%"></div>
        <h1><?= htmlspecialchars($schoolName) ?></h1>
        <div class="motto"><?= htmlspecialchars($schoolMotto) ?></div>
        <div style="margin-top:8px;font-size:.85rem;opacity:.9">STUDENT FINANCIAL STATEMENT</div>
    </div>

    <div class="stmt-meta">
        <div class="stmt-meta-item"><label>Student Name</label><span><?= htmlspecialchars($stuName) ?></span></div>
        <div class="stmt-meta-item"><label>Student Number</label><span><?= htmlspecialchars($stuNumber) ?></span></div>
        <div class="stmt-meta-item"><label>Registration No.</label><span><?= htmlspecialchars($stuRegNum) ?></span></div>
        <div class="stmt-meta-item"><label>Index Number</label><span><?= htmlspecialchars($stuIndexNum) ?></span></div>
        <div class="stmt-meta-item"><label>Program</label><span><?= htmlspecialchars($stuProgram) ?></span></div>
        <div class="stmt-meta-item"><label>Year / Level</label><span><?= htmlspecialchars($stuYear ? "Year $stuYear" : '-') ?> <?= $stuLevel ? "/ " . htmlspecialchars($stuLevel) : '' ?></span></div>
        <div class="stmt-meta-item"><label>Statement Reference</label><span><?= htmlspecialchars($statementRef) ?></span></div>
        <div class="stmt-meta-item"><label>Statement Date</label><span><?= htmlspecialchars($statementDate) ?></span></div>
    </div>

    <div class="stmt-body">
        <h5 style="color:#1e40af;font-size:1rem;margin-bottom:12px"><i class="fas fa-list-alt me-1"></i>Transaction Details</h5>
        <?php if (empty($entries)): ?>
        <div class="text-center py-4 text-muted"><i class="fas fa-inbox fa-2x mb-2" style="opacity:.3"></i><p>No transactions found for this student.</p></div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="stmt-table">
                <thead><tr><th>Date</th><th>Description</th><th>Reference</th><th>Debit (UGX)</th><th>Credit (UGX)</th><th>Balance (UGX)</th></tr></thead>
                <tbody>
                <?php foreach ($entries as $e): ?>
                <tr>
                    <td><?= date('d M Y', strtotime($e['date'])) ?></td>
                    <td><?= htmlspecialchars($e['description']) ?></td>
                    <td><small><?= htmlspecialchars($e['reference']) ?></small></td>
                    <td class="debit"><?= $e['debit'] > 0 ? number_format($e['debit']) : '-' ?></td>
                    <td class="credit"><?= $e['credit'] > 0 ? number_format($e['credit']) : '-' ?></td>
                    <td class="balance" style="color:<?= ($e['balance'] ?? 0) > 0 ? '#dc2626' : '#059669' ?>"><?= number_format($e['balance'] ?? 0) ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

        <div class="stmt-summary">
            <div class="stmt-summary-item fees">
                <div class="amount">UGX <?= number_format($totalFees) ?></div>
                <div class="label">Total Fees Charged</div>
            </div>
            <div class="stmt-summary-item paid">
                <div class="amount">UGX <?= number_format($totalPaid) ?></div>
                <div class="label">Total Payments Made</div>
            </div>
            <div class="stmt-summary-item balance">
                <div class="amount">UGX <?= number_format($balance) ?></div>
                <div class="label"><?= $balance > 0 ? 'Outstanding Balance' : 'Balance Cleared' ?></div>
            </div>
        </div>
    </div>

    <div class="stmt-footer">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:40px;margin-bottom:20px">
            <div>
                <div class="signature-line"></div>
                <p>Prepared By: <?= htmlspecialchars($user['full_name'] ?? 'Bursar') ?></p>
                <p>Date: <?= $statementDate ?></p>
            </div>
            <div>
                <div class="signature-line"></div>
                <p>Authorized By: Director of Finance</p>
                <p>Official Stamp</p>
            </div>
        </div>
        <p style="margin-top:12px;color:#9ca3af;font-size:.75rem">This statement is computer-generated and valid without signature. For inquiries, contact the Bursar's office.</p>
        <p style="color:#9ca3af;font-size:.75rem">&copy; <?= date('Y') ?> <?= htmlspecialchars($schoolName) ?>. All rights reserved.</p>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
