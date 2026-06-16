<?php
require_once __DIR__ . '/includes/config_enhanced.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/email_notifications.php';

session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: contact.php');
    exit;
}

$firstName = trim($_POST['firstName'] ?? '');
$lastName  = trim($_POST['lastName'] ?? '');
$email     = trim($_POST['email'] ?? '');
$phone     = trim($_POST['phone'] ?? '');
$subject   = trim($_POST['subject'] ?? '');
$message   = trim($_POST['message'] ?? '');

if (!$firstName || !$lastName || !$email || !$phone || !$subject || !$message) {
    $_SESSION['error_message'] = 'Please fill in all required fields.';
    header('Location: contact.php');
    exit;
}

try {
    $websiteDb = getWebsiteConnection();
    if ($websiteDb) {
        $stmt = $websiteDb->prepare("INSERT INTO contact_submissions (first_name, last_name, email, phone, subject, message, status, created_at) VALUES (?, ?, ?, ?, ?, ?, 'unread', NOW())");
        $stmt->bind_param('ssssss', $firstName, $lastName, $email, $phone, $subject, $message);
        $stmt->execute();
        $submissionId = $stmt->insert_id;
        $stmt->close();
    } else {
        $submissionId = null;
    }

    $fullName = $firstName . ' ' . $lastName;
    $contentBlocks = [
        ['type' => 'table', 'data' => [
            'From'       => $fullName,
            'Email'      => $email,
            'Phone'      => $phone,
            'Subject'    => $subject,
            'Message'    => $message,
            'Received'   => date('d M Y h:i A'),
        ]],
        'A new contact form submission has been received on the ISNM website. Please review and respond at your earliest convenience.',
    ];
    $cta = ['url' => 'https://isnm.ac.ug/dashboards/director-general.php', 'text' => 'View in Dashboard'];

    notifyDirectorGeneral('New Contact Message: ' . $subject, $contentBlocks, $cta);

    $_SESSION['success_message'] = 'Thank you, ' . $firstName . '! Your message has been sent successfully. We will get back to you shortly.';
    header('Location: contact.php');
    exit;

} catch (Exception $e) {
    error_log('Contact form error: ' . $e->getMessage());
    $_SESSION['error_message'] = 'Sorry, something went wrong. Please try again later or call us directly.';
    header('Location: contact.php');
    exit;
}