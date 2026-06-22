<?php
/**
 * Receipt Printing Endpoint
 */
require_once __DIR__ . '/includes/financial_functions.php';
require_once __DIR__ . '/includes/receipt_generator.php';

if (!isset($_GET['type'])) {
    header('Location: ../dashboards/school-bursar.php');
    exit();
}

$type = $_GET['type'];

if ($type === 'payment' && isset($_GET['id'])) {
    $payment_id = (int)$_GET['id'];
    echo ReceiptGenerator::generateReceiptHTML($payment_id);
} elseif ($type === 'payslip' && isset($_GET['id'])) {
    $staff_id = (int)$_GET['id'];
    $month = $_GET['month'] ?? null;
    $year = $_GET['year'] ?? null;
    echo ReceiptGenerator::generatePayslipHTML($staff_id, $month, $year);
} else {
    header('Location: ../dashboards/school-bursar.php');
    exit();
}
?>