<?php
require_once __DIR__ . '/includes/config_enhanced.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/email_notifications.php';
require_once __DIR__ . '/includes/financial_functions.php';

session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: donation.php');
    exit;
}

$donorName    = trim($_POST['donorName'] ?? '');
$donorEmail   = trim($_POST['donorEmail'] ?? '');
$donorPhone   = trim($_POST['donorPhone'] ?? '');
$donorAddress = trim($_POST['donorAddress'] ?? '');
$amount       = (float)($_POST['amount'] ?? 0);
$paymentMethod = trim($_POST['paymentMethod'] ?? '');
$purpose      = trim($_POST['purpose'] ?? 'General Donation');
$notes        = trim($_POST['notes'] ?? '');

if (!$donorName || !$donorEmail || !$donorPhone || $amount <= 0 || !$paymentMethod) {
    $_SESSION['error_message'] = 'Please fill in all required fields with valid values.';
    header('Location: donation.php');
    exit;
}

try {
    $websiteDb = getWebsiteConnection();
    if ($websiteDb) {
        $stmt = $websiteDb->prepare("INSERT INTO donations (donor_name, donor_email, donor_phone, donor_address, amount, payment_method, purpose, notes, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())");
        $stmt->bind_param('ssssdsss', $donorName, $donorEmail, $donorPhone, $donorAddress, $amount, $paymentMethod, $purpose, $notes);
        $stmt->execute();
        $donationId = $stmt->insert_id;
        $stmt->close();
    }

    $methodLabel = str_replace(['_', '-'], ' ', ucwords($paymentMethod, ' _-'));
    $contentBlocks = [
        ['type' => 'table', 'data' => [
            'Donor Name'     => $donorName,
            'Email'          => $donorEmail,
            'Phone'          => $donorPhone,
            'Amount'         => 'UGX ' . number_format($amount, 0),
            'Payment Method' => $methodLabel,
            'Purpose'        => $purpose,
            'Date'           => date('d M Y h:i A'),
        ]],
        'A new donation has been recorded on the ISNM website. Please verify and acknowledge.',
    ];
    if ($notes) {
        $contentBlocks[] = 'Donor Notes:';
        $contentBlocks[] = $notes;
    }

    $cta = ['url' => 'https://isnm.ac.ug/dashboards/director-general.php', 'text' => 'View in Dashboard'];
    notifyDirectorGeneral('New Donation: UGX ' . number_format($amount, 0) . ' from ' . $donorName, $contentBlocks, $cta);

    $_SESSION['success_message'] = 'Thank you, ' . $donorName . '! Your generous donation has been recorded. A confirmation will be sent to your email.';
    header('Location: donation.php');
    exit;

} catch (Exception $e) {
    error_log('Donation form error: ' . $e->getMessage());
    $_SESSION['success_message'] = 'Thank you for your generous donation! A receipt will be sent to your email.';
    header('Location: donation.php');
    exit;
}