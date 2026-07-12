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

$donorName    = sanitizeInput(trim($_POST['donorName'] ?? ''));
$donorEmail   = trim($_POST['donorEmail'] ?? '');
$donorPhone   = trim($_POST['donorPhone'] ?? '');
$donorAddress = sanitizeInput(trim($_POST['donorAddress'] ?? ''));
$amount       = (float)($_POST['amount'] ?? 0);
$paymentMethod = trim($_POST['paymentMethod'] ?? '');
$purpose      = sanitizeInput(trim($_POST['purpose'] ?? 'General Donation'));
$notes        = sanitizeInput(trim($_POST['notes'] ?? ''));

if (!$donorName || !$donorEmail || !$donorPhone || $amount <= 0 || !$paymentMethod) {
    $_SESSION['error_message'] = 'Please fill in all required fields with valid values.';
    header('Location: donation.php');
    exit;
}

if (!validateEmail($donorEmail)) {
    $_SESSION['error_message'] = 'Please enter a valid email address.';
    header('Location: donation.php');
    exit;
}

if (!validatePhone($donorPhone)) {
    $_SESSION['error_message'] = 'Please enter a valid phone number (e.g., 0700123456 or +256700123456).';
    header('Location: donation.php');
    exit;
}

if ($amount < 100) {
    $_SESSION['error_message'] = 'Donation amount must be at least UGX 100.';
    header('Location: donation.php');
    exit;
}

try {
    $websiteDb = getWebsiteConnection();
    $donationId = null;
    if ($websiteDb) {
        $stmt = $websiteDb->prepare("INSERT INTO donations (donor_name, donor_email, donor_phone, donor_address, amount, payment_method, purpose, notes, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())");
        $stmt->bind_param('sssdssss', $donorName, $donorEmail, $donorPhone, $donorAddress, $amount, $paymentMethod, $purpose, $notes);
        if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
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

    try {
        $staffsDb = getStaffConnection();
        if ($staffsDb) {
            $notifStmt = $staffsDb->prepare("INSERT INTO notifications (title, message, type, priority, audience, created_by, created_at) VALUES (?, ?, 'donation', 'high', 'director_general', 0, NOW())");
            $notifTitle = 'New Donation: UGX ' . number_format($amount, 0);
            $notifMsg = $donorName . ' donated UGX ' . number_format($amount, 0) . ' via ' . $methodLabel . '. Purpose: ' . $purpose;
            $notifStmt->bind_param('ss', $notifTitle, $notifMsg);
            if (!$notifStmt->execute()) { error_log('$notifStmt execute failed: ' . ($notifStmt->error ?? 'unknown')); };
            $notifStmt->close();
        }
    } catch (Exception $e) {
        error_log('Donation notification log error: ' . $e->getMessage());
    }

    try {
        $staffsDb = getStaffConnection();
        if ($staffsDb) {
            $logStmt = $staffsDb->prepare("INSERT INTO activity_log (user_id, activity, details, ip_address, created_at) VALUES (0, ?, ?, ?, NOW())");
            $logActivity = 'Donation Received';
            $logDetails = $donorName . ' (' . $donorEmail . ') donated UGX ' . number_format($amount, 0);
            $logIp = $_SERVER['REMOTE_ADDR'] ?? '';
            $logStmt->bind_param('sss', $logActivity, $logDetails, $logIp);
            if (!$logStmt->execute()) { error_log('$logStmt execute failed: ' . ($logStmt->error ?? 'unknown')); };
            $logStmt->close();
        }
    } catch (Exception $e) {
        error_log('Donation activity log error: ' . $e->getMessage());
    }

    $_SESSION['success_message'] = 'Thank you, ' . $donorName . '! Your generous donation of UGX ' . number_format($amount, 0) . ' has been recorded. A confirmation will be sent to your email.';
    header('Location: donation.php');
    exit;

} catch (Exception $e) {
    error_log('Donation form error: ' . $e->getMessage());
    $_SESSION['error_message'] = 'Sorry, there was an error recording your donation. Please try again or contact us directly.';
    header('Location: donation.php');
    exit;
}
