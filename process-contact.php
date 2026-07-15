<?php
require_once __DIR__ . '/includes/config_enhanced.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/email_notifications.php';

session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: contact.php');
    exit;
}

if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    $_SESSION['error_message'] = 'Invalid security token. Please try again.';
    header('Location: contact.php');
    exit;
}

$firstName = sanitizeInput(trim($_POST['firstName'] ?? ''));
$lastName  = sanitizeInput(trim($_POST['lastName'] ?? ''));
$email     = trim($_POST['email'] ?? '');
$phone     = trim($_POST['phone'] ?? '');
$subject   = sanitizeInput(trim($_POST['subject'] ?? ''));
$message   = sanitizeInput(trim($_POST['message'] ?? ''));

// Enforce max input lengths
$firstName = mb_substr($firstName, 0, 100);
$lastName = mb_substr($lastName, 0, 100);
$email = mb_substr($email, 0, 255);
$phone = mb_substr($phone, 0, 20);
$subject = mb_substr($subject, 0, 200);
$message = mb_substr($message, 0, 5000);

if (!$firstName || !$lastName || !$email || !$phone || !$subject || !$message) {
    $_SESSION['error_message'] = 'Please fill in all required fields.';
    header('Location: contact.php');
    exit;
}

if (!validateEmail($email)) {
    $_SESSION['error_message'] = 'Please enter a valid email address.';
    header('Location: contact.php');
    exit;
}

if (!validatePhone($phone)) {
    $_SESSION['error_message'] = 'Please enter a valid phone number (e.g., 0700123456 or +256700123456).';
    header('Location: contact.php');
    exit;
}

try {
    $websiteDb = getWebsiteConnection();
    if ($websiteDb) {
        $stmt = $websiteDb->prepare("INSERT INTO contact_submissions (first_name, last_name, email, phone, subject, message, status, created_at) VALUES (?, ?, ?, ?, ?, ?, 'unread', NOW())");
        $stmt->bind_param('ssssss', $firstName, $lastName, $email, $phone, $subject, $message);
        if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
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

    try {
        $staffsDb = getStaffConnection();
        if ($staffsDb) {
            $notifStmt = $staffsDb->prepare("INSERT INTO notifications (title, message, type, priority, audience, created_by, created_at) VALUES (?, ?, 'form_submission', 'normal', 'director_general', 0, NOW())");
            $notifTitle = 'New Contact: ' . $subject;
            $notifMsg = $fullName . ' submitted a contact form regarding "' . $subject . '". Email: ' . $email . ', Phone: ' . $phone;
            $notifStmt->bind_param('ss', $notifTitle, $notifMsg);
            if (!$notifStmt->execute()) { error_log('$notifStmt execute failed: ' . ($notifStmt->error ?? 'unknown')); };
            $notifStmt->close();
        }
    } catch (Exception $e) {
        error_log('Contact notification log error: ' . $e->getMessage());
    }

    try {
        $staffsDb = getStaffConnection();
        if ($staffsDb) {
            $logStmt = $staffsDb->prepare("INSERT INTO activity_log (user_id, activity, details, ip_address, created_at) VALUES (0, ?, ?, ?, NOW())");
            $logActivity = 'Contact Form Submission';
            $logDetails = $fullName . ' (' . $email . ') submitted contact form: ' . $subject;
            $logIp = $_SERVER['REMOTE_ADDR'] ?? '';
            $logStmt->bind_param('sss', $logActivity, $logDetails, $logIp);
            if (!$logStmt->execute()) { error_log('$logStmt execute failed: ' . ($logStmt->error ?? 'unknown')); };
            $logStmt->close();
        }
    } catch (Exception $e) {
        error_log('Contact activity log error: ' . $e->getMessage());
    }

    $_SESSION['success_message'] = 'Thank you, ' . $firstName . '! Your message has been sent successfully. We will get back to you shortly.';
    header('Location: contact.php');
    exit;

} catch (Exception $e) {
    error_log('Contact form error: ' . $e->getMessage());
    $_SESSION['error_message'] = 'Sorry, something went wrong. Please try again later or call us directly.';
    header('Location: contact.php');
    exit;
}
