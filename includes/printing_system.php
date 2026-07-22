<?php
/**
 * ISNM Printing System
 * Generates print-ready HTML for student profiles, receipts, reports.
 * Designed for A4 paper format.
 */

if (!function_exists('printStudentProfile')) {
    /**
     * Generate a full A4-ready student profile HTML for printing.
     */
    function printStudentProfile(array $student, array $payments = [], array $requirements = [], array $documents = []): void {
        $schoolName = 'Iganga School of Nursing and Midwifery';
        $schoolSlogan = 'Excellence in Nursing & Midwifery Education';
        $schoolAddress = 'P.O. Box 1234, Iganga, Uganda';
        $schoolPhone = '+256 45 123 4567';

        $fullName = htmlspecialchars($student['full_name'] ?? trim(($student['first_name'] ?? '') . ' ' . ($student['other_name'] ?? '') . ' ' . ($student['surname'] ?? '')));
        $indexNum = htmlspecialchars($student['index_number'] ?? '');
        $regNum = htmlspecialchars($student['registration_number'] ?? '');
        $stuNum = htmlspecialchars($student['student_number'] ?? '');
        $nationalId = htmlspecialchars($student['national_student_id_number'] ?? '');
        $gender = htmlspecialchars($student['gender'] ?? '');
        $dob = htmlspecialchars($student['date_of_birth'] ?? '');
        $phone = htmlspecialchars($student['phone'] ?? '');
        $email = htmlspecialchars($student['email'] ?? '');
        $program = htmlspecialchars($student['program'] ?? '');
        $level = htmlspecialchars($student['level'] ?? '');
        $year = htmlspecialchars($student['current_year'] ?? $student['year'] ?? '');
        $intake = htmlspecialchars($student['intake'] ?? $student['intake_period'] ?? '');
        $stream = htmlspecialchars($student['stream'] ?? '');
        $status = htmlspecialchars($student['status'] ?? '');
        $district = htmlspecialchars($student['district'] ?? '');
        $religion = htmlspecialchars($student['religion'] ?? '');
        $guardianName = htmlspecialchars($student['guardian_name'] ?? '');
        $guardianPhone = htmlspecialchars($student['guardian_phone'] ?? '');
        $bloodGroup = htmlspecialchars($student['blood_group'] ?? '');
        $medicalConditions = htmlspecialchars($student['medical_conditions'] ?? '');
        $regStatus = htmlspecialchars($student['registration_status'] ?? '');
        $academicYear = htmlspecialchars($student['academic_year'] ?? '');
        $set_name = htmlspecialchars($student['set_name'] ?? '');

        $photoPath = $student['passport_photo'] ?? $student['profile_picture'] ?? '';
        $photoTag = '';
        if (!empty($photoPath) && file_exists(__DIR__ . '/../' . $photoPath)) {
            $photoTag = '<img src="' . htmlspecialchars($photoPath) . '" alt="Photo" style="width:120px;height:120px;object-fit:cover;border-radius:8px;border:2px solid #1a237e;">';
        } else {
            $photoTag = '<div style="width:120px;height:120px;border-radius:8px;border:2px solid #1a237e;background:#e8eaf6;display:flex;align-items:center;justify-content:center;font-size:32px;font-weight:700;color:#1a237e;">' . mb_substr($student['first_name'] ?? 'S', 0, 1) . '</div>';
        }

        $totalPaid = 0;
        $totalFees = 0;
        foreach ($payments as $p) {
            $totalPaid += floatval($p['amount'] ?? $p['amount_received'] ?? 0);
        }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Student Profile - <?= $fullName ?></title>
<style>
@page { size: A4 portrait; margin: 18mm 15mm; }
* { margin: 0; padding: 0; box-sizing: border-box; }
body { font-family: Arial, Helvetica, sans-serif; font-size: 11px; color: #1a1a1a; line-height: 1.4; }
.header { text-align: center; border-bottom: 3px double #1a237e; padding-bottom: 10px; margin-bottom: 14px; }
.header h1 { font-size: 16px; color: #1a237e; margin-bottom: 2px; letter-spacing: 1px; }
.header .slogan { font-style: italic; color: #555; font-size: 10px; margin-bottom: 4px; }
.header .contact { font-size: 9px; color: #777; }
.title { font-size: 13px; font-weight: 700; color: #1a237e; border-bottom: 2px solid #1a237e; padding-bottom: 4px; margin: 14px 0 10px; }
.profile-grid { display: grid; grid-template-columns: 1fr 120px; gap: 14px; margin-bottom: 14px; }
.fields table { width: 100%; border-collapse: collapse; }
.fields td { padding: 3px 6px; border-bottom: 1px dotted #ccc; vertical-align: top; }
.fields td.label { font-weight: 700; width: 140px; color: #444; }
.photo-col { text-align: center; }
.section-title { font-size: 11px; font-weight: 700; color: #1a237e; background: #e8eaf6; padding: 4px 8px; margin: 10px 0 6px; border-left: 3px solid #1a237e; }
table.data { width: 100%; border-collapse: collapse; margin-bottom: 10px; font-size: 10px; }
table.data th { background: #1a237e; color: #fff; padding: 4px 6px; text-align: left; font-size: 9px; }
table.data td { padding: 3px 6px; border-bottom: 1px solid #e0e0e0; }
table.data tr:nth-child(even) { background: #f9f9f9; }
.footer { margin-top: 20px; border-top: 2px solid #1a237e; padding-top: 8px; text-align: center; font-size: 9px; color: #888; }
.footer .generated { margin-top: 6px; }
.badge { display: inline-block; padding: 1px 6px; border-radius: 3px; font-size: 9px; font-weight: 600; }
.badge-active { background: #d4edda; color: #155724; }
.badge-graduated { background: #cce5ff; color: #004085; }
.badge-inactive { background: #f8d7da; color: #721c24; }
.badge-default { background: #e2e3e5; color: #383d41; }
@media print {
    body { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
}
</style>
</head>
<body>

<div class="header">
    <h1><?= $schoolName ?></h1>
    <div class="slogan"><?= $schoolSlogan ?></div>
    <div class="contact"><?= $schoolAddress ?> | <?= $schoolPhone ?></div>
</div>

<div class="title">STUDENT PROFILE</div>

<div class="profile-grid">
    <div class="fields">
        <table>
            <tr><td class="label">Full Name:</td><td><?= $fullName ?></td></tr>
            <tr><td class="label">Gender:</td><td><?= $gender ?></td></tr>
            <tr><td class="label">Date of Birth:</td><td><?= $dob ? formatDate($dob) : '-' ?></td></tr>
            <tr><td class="label">Index Number:</td><td><?= $indexNum ?: '-' ?></td></tr>
            <tr><td class="label">Registration Number:</td><td><?= $regNum ?: '-' ?></td></tr>
            <tr><td class="label">Student Number:</td><td><?= $stuNum ?: '-' ?></td></tr>
            <tr><td class="label">National ID:</td><td><?= $nationalId ?: '-' ?></td></tr>
            <tr><td class="label">Program:</td><td><?= $program ?: '-' ?></td></tr>
            <tr><td class="label">Level:</td><td><?= $level ?: '-' ?></td></tr>
            <tr><td class="label">Year:</td><td><?= $year ?: '-' ?></td></tr>
            <tr><td class="label">Stream/Set:</td><td><?= ($stream ?: '-') . ($set_name ? " ($set_name)" : '') ?></td></tr>
            <tr><td class="label">Intake:</td><td><?= $intake ?: '-' ?></td></tr>
            <tr><td class="label">Academic Year:</td><td><?= $academicYear ?: '-' ?></td></tr>
            <tr><td class="label">Registration Status:</td><td><?= $regStatus ?: '-' ?></td></tr>
            <tr><td class="label">Phone:</td><td><?= $phone ?: '-' ?></td></tr>
            <tr><td class="label">Email:</td><td><?= $email ?: '-' ?></td></tr>
            <tr><td class="label">District:</td><td><?= $district ?: '-' ?></td></tr>
            <tr><td class="label">Religion:</td><td><?= $religion ?: '-' ?></td></tr>
            <tr><td class="label">Blood Group:</td><td><?= $bloodGroup ?: '-' ?></td></tr>
            <tr><td class="label">Medical Conditions:</td><td><?= $medicalConditions ?: 'None' ?></td></tr>
            <tr><td class="label">Status:</td><td><span class="badge badge-<?= strtolower($status) === 'active' ? 'active' : (strtolower($status) === 'graduated' ? 'graduated' : 'default') ?>"><?= $status ?></span></td></tr>
        </table>
    </div>
    <div class="photo-col">
        <?= $photoTag ?>
    </div>
</div>

<?php if (!empty($guardianName) || !empty($guardianPhone)): ?>
<div class="section-title">GUARDIAN / NEXT OF KIN</div>
<table class="data">
    <tr><th style="width:150px">Name</th><th>Phone</th></tr>
    <tr><td><?= $guardianName ?: '-' ?></td><td><?= $guardianPhone ?: '-' ?></td></tr>
</table>
<?php endif; ?>

<?php if (!empty($requirements)): ?>
<div class="section-title">ADMISSION REQUIREMENTS</div>
<table class="data">
    <tr><th>Requirement</th><th>Type</th><th>Status</th><th>Remarks</th></tr>
    <?php foreach ($requirements as $req): ?>
    <tr>
        <td><?= htmlspecialchars($req['requirement_name'] ?? '-') ?></td>
        <td><?= htmlspecialchars($req['requirement_type'] ?? $req['type'] ?? '-') ?></td>
        <td><span class="badge badge-<?= ($req['status'] ?? '') === 'Verified' ? 'active' : 'default' ?>"><?= htmlspecialchars($req['status'] ?? 'Not Submitted') ?></span></td>
        <td><?= htmlspecialchars($req['remarks'] ?? '') ?></td>
    </tr>
    <?php endforeach; ?>
</table>
<?php endif; ?>

<?php if (!empty($payments)): ?>
<div class="section-title">PAYMENT HISTORY</div>
<table class="data">
    <tr><th>Date</th><th>Reference</th><th>Amount</th><th>Method</th><th>Status</th></tr>
    <?php foreach ($payments as $pay): ?>
    <tr>
        <td><?= htmlspecialchars($pay['payment_date'] ?? '') ?></td>
        <td><?= htmlspecialchars($pay['payment_reference'] ?? '') ?></td>
        <td><?= formatCurrency(floatval($pay['amount'] ?? $pay['amount_received'] ?? 0)) ?></td>
        <td><?= htmlspecialchars($pay['payment_method'] ?? '') ?></td>
        <td><?= htmlspecialchars($pay['status'] ?? '') ?></td>
    </tr>
    <?php endforeach; ?>
    <tr style="font-weight:700;background:#e8eaf6;">
        <td colspan="2" style="text-align:right;">Total Paid:</td>
        <td colspan="3"><?= formatCurrency($totalPaid) ?></td>
    </tr>
</table>
<?php endif; ?>

<div class="footer">
    <div><?= $schoolName ?> &mdash; <?= $schoolAddress ?></div>
    <div class="generated">Generated on <?= date('d M Y, h:i A') ?> by <?= htmlspecialchars($_SESSION['first_name'] ?? '') . ' ' . htmlspecialchars($_SESSION['last_name'] ?? '') ?></div>
</div>

</body>
</html>
<?php
    }
}

if (!function_exists('printPaymentReceipt')) {
    /**
     * Generate a payment receipt HTML for printing.
     */
    function printPaymentReceipt(array $payment, array $student): void {
        $schoolName = 'Iganga School of Nursing and Midwifery';

        $fullName = htmlspecialchars($student['full_name'] ?? trim(($student['first_name'] ?? '') . ' ' . ($student['surname'] ?? '')));
        $indexNum = htmlspecialchars($student['index_number'] ?? '');
        $regNum = htmlspecialchars($student['registration_number'] ?? '');
        $program = htmlspecialchars($student['program'] ?? '');

        $ref = htmlspecialchars($payment['payment_reference'] ?? '');
        $amount = formatCurrency(floatval($payment['amount'] ?? $payment['amount_received'] ?? 0));
        $method = htmlspecialchars($payment['payment_method'] ?? 'Cash');
        $date = htmlspecialchars($payment['payment_date'] ?? date('Y-m-d'));
        $status = htmlspecialchars($payment['status'] ?? 'completed');
        $txnRef = htmlspecialchars($payment['transaction_ref'] ?? '');
        $slipNum = htmlspecialchars($payment['slip_number'] ?? '');
        $receivedBy = htmlspecialchars($payment['received_by_name'] ?? '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Payment Receipt - <?= $ref ?></title>
<style>
@page { size: A4 portrait; margin: 20mm; }
* { margin: 0; padding: 0; box-sizing: border-box; }
body { font-family: Arial, sans-serif; font-size: 12px; color: #1a1a1a; }
.receipt { max-width: 600px; margin: 0 auto; border: 2px solid #1a237e; padding: 24px; }
.header { text-align: center; border-bottom: 2px double #1a237e; padding-bottom: 12px; margin-bottom: 16px; }
.header h1 { font-size: 16px; color: #1a237e; }
.receipt-title { text-align: center; font-size: 14px; font-weight: 700; color: #1a237e; margin-bottom: 16px; text-transform: uppercase; }
.field { display: flex; padding: 5px 0; border-bottom: 1px dotted #ccc; }
.field .label { font-weight: 700; width: 160px; color: #444; }
.field .value { flex: 1; }
.amount-box { text-align: center; padding: 16px; margin: 16px 0; border: 2px dashed #1a237e; background: #f0f4ff; }
.amount-box .amount { font-size: 22px; font-weight: 700; color: #1a237e; }
.footer { margin-top: 24px; text-align: center; font-size: 10px; color: #888; border-top: 1px solid #ccc; padding-top: 10px; }
.signature-line { margin-top: 30px; display: flex; justify-content: space-between; }
.sig-box { width: 200px; text-align: center; }
.sig-box .line { border-top: 1px solid #000; margin-top: 40px; padding-top: 4px; }
</style>
</head>
<body>
<div class="receipt">
    <div class="header">
        <h1><?= $schoolName ?></h1>
        <div style="font-size:10px;color:#666;">Payment Receipt</div>
    </div>

    <div class="receipt-title">PAYMENT RECEIPT</div>

    <div class="field"><div class="label">Receipt Number:</div><div class="value"><?= $ref ?></div></div>
    <div class="field"><div class="label">Date:</div><div class="value"><?= formatDate($date) ?></div></div>
    <div class="field"><div class="label">Student Name:</div><div class="value"><?= $fullName ?></div></div>
    <div class="field"><div class="label">Index Number:</div><div class="value"><?= $indexNum ?: '-' ?></div></div>
    <div class="field"><div class="label">Registration Number:</div><div class="value"><?= $regNum ?: '-' ?></div></div>
    <div class="field"><div class="label">Program:</div><div class="value"><?= $program ?: '-' ?></div></div>

    <div class="amount-box">
        <div>Amount Paid</div>
        <div class="amount">UGX <?= $amount ?></div>
    </div>

    <div class="field"><div class="label">Payment Method:</div><div class="value"><?= $method ?></div></div>
    <?php if ($txnRef): ?><div class="field"><div class="label">Transaction Reference:</div><div class="value"><?= $txnRef ?></div></div><?php endif; ?>
    <?php if ($slipNum): ?><div class="field"><div class="label">Slip Number:</div><div class="value"><?= $slipNum ?></div></div><?php endif; ?>
    <div class="field"><div class="label">Status:</div><div class="value"><?= ucfirst($status) ?></div></div>
    <?php if ($receivedBy): ?><div class="field"><div class="label">Received By:</div><div class="value"><?= $receivedBy ?></div></div><?php endif; ?>

    <div class="signature-line">
        <div class="sig-box"><div class="line">Student Signature</div></div>
        <div class="sig-box"><div class="line">Bursar / Cashier</div></div>
    </div>

    <div class="footer">
        This is a computer-generated receipt. <?= $schoolName ?><br>
        Generated on <?= date('d M Y, h:i A') ?>
    </div>
</div>
</body>
</html>
<?php
    }
}

if (!function_exists('printStudentList')) {
    /**
     * Print a list of students in a table format.
     */
    function printStudentList(array $students, string $title = 'Student List', array $filters = []): void {
        $schoolName = 'Iganga School of Nursing and Midwifery';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title><?= $title ?></title>
<style>
@page { size: A4 landscape; margin: 15mm; }
* { margin: 0; padding: 0; box-sizing: border-box; }
body { font-family: Arial, sans-serif; font-size: 10px; color: #1a1a1a; }
.header { text-align: center; border-bottom: 3px double #1a237e; padding-bottom: 10px; margin-bottom: 12px; }
.header h1 { font-size: 14px; color: #1a237e; }
.title { font-size: 12px; font-weight: 700; text-align: center; margin-bottom: 10px; color: #333; }
.filters { font-size: 9px; color: #666; margin-bottom: 8px; text-align: center; }
table { width: 100%; border-collapse: collapse; font-size: 9px; }
th { background: #1a237e; color: #fff; padding: 4px 5px; text-align: left; font-size: 8px; }
td { padding: 3px 5px; border-bottom: 1px solid #e0e0e0; }
tr:nth-child(even) { background: #f9f9f9; }
.footer { margin-top: 12px; text-align: center; font-size: 9px; color: #888; border-top: 1px solid #ccc; padding-top: 6px; }
@media print { body { -webkit-print-color-adjust: exact !important; } }
</style>
</head>
<body>
<div class="header">
    <h1><?= $schoolName ?></h1>
    <div style="font-size:9px;color:#666;"><?= date('l, d M Y') ?></div>
</div>
<div class="title"><?= $title ?></div>
<?php if (!empty($filters)): ?>
<div class="filters">
    <?php foreach ($filters as $k => $v): ?>
        <strong><?= $k ?>:</strong> <?= $v ?> &nbsp;|&nbsp;
    <?php endforeach; ?>
</div>
<?php endif; ?>
<table>
    <thead>
        <tr>
            <th>#</th>
            <th>Student Number</th>
            <th>Index Number</th>
            <th>Full Name</th>
            <th>Program</th>
            <th>Level</th>
            <th>Year</th>
            <th>Phone</th>
            <th>Status</th>
        </tr>
    </thead>
    <tbody>
        <?php $i = 1; foreach ($students as $s): ?>
        <tr>
            <td><?= $i++ ?></td>
            <td><?= htmlspecialchars($s['student_number'] ?? '') ?></td>
            <td><?= htmlspecialchars($s['index_number'] ?? '') ?></td>
            <td><?= htmlspecialchars($s['full_name'] ?? trim(($s['first_name'] ?? '') . ' ' . ($s['surname'] ?? ''))) ?></td>
            <td><?= htmlspecialchars($s['program'] ?? '') ?></td>
            <td><?= htmlspecialchars($s['level'] ?? '') ?></td>
            <td><?= htmlspecialchars($s['current_year'] ?? $s['year'] ?? '') ?></td>
            <td><?= htmlspecialchars($s['phone'] ?? '') ?></td>
            <td><?= htmlspecialchars($s['status'] ?? '') ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
    <tfoot>
        <tr style="font-weight:700;background:#e8eaf6;">
            <td colspan="9">Total: <?= count($students) ?> student(s)</td>
        </tr>
    </tfoot>
</table>
<div class="footer">
    <?= $schoolName ?> | Generated on <?= date('d M Y, h:i A') ?>
</div>
</body>
</html>
<?php
    }
}

if (!function_exists('printRequirementsSummary')) {
    /**
     * Print a requirements checklist summary for a student.
     */
    function printRequirementsSummary(array $student, array $requirements, string $title = 'Admission Requirements Checklist'): void {
        $schoolName = 'Iganga School of Nursing and Midwifery';
        $fullName = htmlspecialchars($student['full_name'] ?? trim(($student['first_name'] ?? '') . ' ' . ($student['surname'] ?? '')));
        $stuNum = htmlspecialchars($student['student_number'] ?? '');

        $verified = 0;
        $total = count($requirements);
        foreach ($requirements as $r) {
            if (($r['status'] ?? '') === 'Verified' || ($r['status'] ?? '') === 'Received') $verified++;
        }
        $progress = $total > 0 ? round(($verified / $total) * 100) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title><?= $title ?> - <?= $fullName ?></title>
<style>
@page { size: A4 portrait; margin: 18mm; }
* { margin: 0; padding: 0; box-sizing: border-box; }
body { font-family: Arial, sans-serif; font-size: 11px; color: #1a1a1a; }
.header { text-align: center; border-bottom: 3px double #1a237e; padding-bottom: 10px; margin-bottom: 14px; }
.header h1 { font-size: 15px; color: #1a237e; }
.student-info { margin-bottom: 14px; }
.student-info .row { display: flex; gap: 40px; padding: 3px 0; }
.student-info .label { font-weight: 700; width: 120px; color: #444; }
.progress-bar { background: #e0e0e0; height: 20px; border-radius: 10px; margin: 10px 0; overflow: hidden; }
.progress-fill { background: #28a745; height: 100%; width: <?= $progress ?>%; border-radius: 10px; display: flex; align-items: center; justify-content: center; color: #fff; font-size: 10px; font-weight: 700; }
table { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
th { background: #1a237e; color: #fff; padding: 5px 8px; text-align: left; }
td { padding: 4px 8px; border-bottom: 1px solid #e0e0e0; }
tr:nth-child(even) { background: #f9f9f9; }
.check { color: #28a745; font-weight: 700; }
.cross { color: #dc3545; font-weight: 700; }
.pending { color: #ffc107; font-weight: 700; }
.footer { margin-top: 20px; border-top: 2px solid #1a237e; padding-top: 8px; text-align: center; font-size: 9px; color: #888; }
</style>
</head>
<body>
<div class="header">
    <h1><?= $schoolName ?></h1>
    <div style="font-size:11px;color:#555;margin-top:4px;"><?= $title ?></div>
</div>
<div class="student-info">
    <div class="row"><span class="label">Student Name:</span><span><?= $fullName ?></span></div>
    <div class="row"><span class="label">Student Number:</span><span><?= $stuNum ?></span></div>
    <div class="row"><span class="label">Date:</span><span><?= date('d M Y') ?></span></div>
</div>
<div class="progress-bar">
    <div class="progress-fill"><?= $progress ?>% Complete (<?= $verified ?>/<?= $total ?>)</div>
</div>
<table>
    <thead>
        <tr>
            <th style="width:30px">#</th>
            <th>Requirement</th>
            <th>Type</th>
            <th>Mandatory</th>
            <th>Status</th>
            <th>Remarks</th>
        </tr>
    </thead>
    <tbody>
        <?php $i = 1; foreach ($requirements as $r): ?>
        <tr>
            <td><?= $i++ ?></td>
            <td><?= htmlspecialchars($r['requirement_name'] ?? '') ?></td>
            <td><?= htmlspecialchars($r['requirement_type'] ?? $r['type'] ?? '') ?></td>
            <td><?= ($r['is_mandatory'] ?? 0) ? '<span class="check">Yes</span>' : 'No' ?></td>
            <td>
                <?php
                    $st = $r['status'] ?? 'Not Submitted';
                    $cls = 'pending';
                    if (in_array($st, ['Verified', 'Received'])) $cls = 'check';
                    elseif (in_array($st, ['Rejected', 'Missing'])) $cls = 'cross';
                ?>
                <span class="<?= $cls ?>"><?= $st ?></span>
            </td>
            <td><?= htmlspecialchars($r['remarks'] ?? '') ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<div class="footer">
    <?= $schoolName ?> | Generated on <?= date('d M Y, h:i A') ?>
</div>
</body>
</html>
<?php
    }
}
