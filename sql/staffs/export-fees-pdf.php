<?php
/**
 * ISNM Student Fees PDF Export
 * Generates a professional PDF statement using the mPDF library.
 */

require_once '../../vendor/autoload.php';
require_once '../../auth-service.php';
require_once '../../config/database.php';
require_once '../../includes/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Security Check: Authenticated and Authorized
if (!$auth_service->isAuthenticated() || !$auth_service->canSearchStudentProfiles($_SESSION['role'])) {
    die("Unauthorized access to financial records.");
}

$studentId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($studentId <= 0) {
    die("Invalid Student ID.");
}

try {
    $conn = getStudentsConnection();
    
    // 2. Fetch Student Data
    $stmt = $conn->prepare("SELECT full_name, student_number, registration_number, program, current_year FROM students WHERE id = ?");
    $stmt->bind_param("i", $studentId);
    if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
    $student = $stmt->get_result()->fetch_assoc();
    
    if (!$student) die("Student record not found.");

    // 3. Fetch Fee Records
    $stmt = $conn->prepare("SELECT * FROM student_fees WHERE student_id = ? ORDER BY due_date DESC, created_at DESC");
    $stmt->bind_param("i", $studentId);
    if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
    $feesResult = $stmt->get_result();
    
    $totalBilled = 0; $totalPaid = 0; $feesData = [];
    while ($row = $feesResult->fetch_assoc()) {
        $feesData[] = $row;
        $totalBilled += $row['amount'];
        if ($row['status'] === 'Paid') $totalPaid += $row['amount'];
    }
    $balance = $totalBilled - $totalPaid;

    // 4. Initialize mPDF
    $mpdf = new \Mpdf\Mpdf([
        'margin_left' => 15, 'margin_right' => 15, 'margin_top' => 15, 'margin_bottom' => 15,
        'format' => 'A4', 'default_font' => 'dejavusans'
    ]);

    // 5. Build HTML with Professional CSS
    $logoPath = '../../images/school-logo.png';

    // Set Watermark for Authenticity
    $mpdf->SetWatermarkImage($logoPath, 0.05, 'D'); // Semi-transparent diagonal logo
    $mpdf->showWatermarkImage = true;

    $html = '
    <style>
        body { color: #333; }
        .header-table { width: 100%; border-bottom: 2px solid #1A237E; padding-bottom: 10px; }
        .school-name { color: #1A237E; font-size: 22pt; font-weight: bold; }
        .doc-title { text-align: center; font-size: 16pt; font-weight: bold; margin: 20px 0; color: #444; background: #f8f9fa; padding: 10px; }
        .summary-box { width: 100%; margin-bottom: 30px; border-collapse: collapse; }
        .summary-box td { padding: 15px; border: 1px solid #ddd; text-align: center; }
        .bg-blue { background-color: #1A237E; color: white; }
        .bg-green { background-color: #2E7D32; color: white; }
        .bg-red { background-color: #C62828; color: white; }
        .ledger-table { width: 100%; border-collapse: collapse; }
        .ledger-table th { background-color: #eee; padding: 10px; border: 1px solid #ccc; text-align: left; font-size: 9pt; }
        .ledger-table td { padding: 10px; border: 1px solid #eee; font-size: 9pt; }
        .text-right { text-align: right; }
        .footer { text-align: center; font-size: 8pt; color: #777; margin-top: 40px; border-top: 1px solid #ddd; padding-top: 10px; }
        .badge { padding: 2px 5px; border-radius: 3px; color: white; font-size: 8pt; }
        .paid { background-color: #43A047; } .unpaid { background-color: #FB8C00; } .overdue { background-color: #E53935; }
        .digital-stamp { color: #C62828; border: 3px solid #C62828; display: inline-block; padding: 8px; font-weight: bold; text-transform: uppercase; border-radius: 8px; transform: rotate(-12deg); opacity: 0.7; font-family: courier; font-size: 10pt; text-align: center; line-height: 1; }
    </style>

    <table class="header-table">
        <tr>
            <td width="15%"><img src="' . $logoPath . '" width="70"></td>
            <td width="85%" class="text-right">
                <span class="school-name">Iganga School of Nursing and Midwifery</span><br>
                P.O. Box 418, Iganga, Uganda<br>
                Tel: +256 782 990 403 | Email: info@isnm.ac.ug
            </td>
        </tr>
    </table>

    <div class="doc-title">OFFICIAL STUDENT FEE STATEMENT</div>

    <table width="100%" style="margin-bottom: 20px;">
        <tr>
            <td width="60%">
                <strong>Student:</strong> ' . htmlspecialchars($student['full_name']) . '<br>
                <strong>Reg No:</strong> ' . htmlspecialchars($student['registration_number']) . '<br>
                <strong>Student No:</strong> ' . htmlspecialchars($student['student_number']) . '
            </td>
            <td width="40%" class="text-right">
                <strong>Program:</strong> ' . htmlspecialchars($student['program']) . '<br>
                <strong>Year:</strong> ' . $student['current_year'] . '<br>
                <strong>Generated:</strong> ' . date('d M Y H:i') . '
            </td>
        </tr>
    </table>

    <table class="summary-box">
        <tr>
            <td class="bg-blue">Total Billed<br><strong>UGX ' . number_format($totalBilled) . '</strong></td>
            <td class="bg-green">Total Paid<br><strong>UGX ' . number_format($totalPaid) . '</strong></td>
            <td class="bg-red">Current Balance<br><strong>UGX ' . number_format($balance) . '</strong></td>
        </tr>
    </table>

    <table class="ledger-table">
        <thead>
            <tr>
                <th>Date</th>
                <th>Fee Description</th>
                <th>Receipt No.</th>
                <th class="text-right">Amount (UGX)</th>
                <th style="text-align:center">Status</th>
            </tr>
        </thead>
        <tbody>';
        foreach ($feesData as $fee) {
            $statusClass = strtolower(str_replace(' ', '', $fee['status']));
            $html .= '<tr>
                <td>' . date('d/m/Y', strtotime($fee['created_at'])) . '</td>
                <td>' . htmlspecialchars($fee['fee_type']) . '</td>
                <td><code>' . htmlspecialchars($fee['receipt_number'] ?? 'N/A') . '</code></td>
                <td class="text-right">' . number_format($fee['amount']) . '</td>
                <td style="text-align:center"><span class="badge ' . $statusClass . '">' . $fee['status'] . '</span></td>
            </tr>';
        }
    $html .= '</tbody></table>

    <table width="100%" style="margin-top: 30px;">
        <tr>
            <td width="70%"></td>
            <td width="30%" style="text-align: center;">
                <div class="digital-stamp">VERIFIED & ISSUED<br><span style="font-size: 8pt;">' . date('d M Y') . '</span><br>ISNM FINANCE</div>
            </td>
        </tr>
    </table>
    <div class="footer">This is an automated system generated document. No physical signature is required.</div>';

    $mpdf->WriteHTML($html);
    $mpdf->Output("Fee_Statement_" . $student['student_number'] . ".pdf", \Mpdf\Output\Destination::INLINE);
} catch (Exception $e) { die("System Error: " . $e->getMessage()); }