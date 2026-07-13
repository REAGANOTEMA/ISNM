<?php
require_once __DIR__ . '/includes/config_enhanced.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/email_notifications.php';

if (session_status() === PHP_SESSION_NONE) session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: volunteer.php');
    exit;
}

if (!verifyCSRFToken()) {
    $_SESSION['error_message'] = 'Invalid security token. Please try again.';
    header('Location: volunteer.php');
    exit;
}

$firstName    = sanitizeInput(trim($_POST['firstName'] ?? ''));
$lastName     = sanitizeInput(trim($_POST['lastName'] ?? ''));
$email        = trim($_POST['email'] ?? '');
$phone        = trim($_POST['phone'] ?? '');
$profession   = sanitizeInput(trim($_POST['profession'] ?? ''));
$experience   = (int)($_POST['experience'] ?? 0);
$opportunity  = sanitizeInput(trim($_POST['opportunity'] ?? ''));
$availability = sanitizeInput(trim($_POST['availability'] ?? ''));
$duration     = sanitizeInput(trim($_POST['duration'] ?? ''));
$skills       = sanitizeInput(trim($_POST['skills'] ?? ''));
$motivation   = sanitizeInput(trim($_POST['motivation'] ?? ''));
$comments     = sanitizeInput(trim($_POST['comments'] ?? ''));

if (!$firstName || !$lastName || !$email || !$phone || !$profession || !$opportunity || !$skills || !$motivation) {
    $_SESSION['error_message'] = 'Please fill in all required fields.';
    header('Location: volunteer.php');
    exit;
}

if (!validateEmail($email)) {
    $_SESSION['error_message'] = 'Please enter a valid email address.';
    header('Location: volunteer.php');
    exit;
}

if (!validatePhone($phone)) {
    $_SESSION['error_message'] = 'Please enter a valid phone number (e.g., 0700123456 or +256700123456).';
    header('Location: volunteer.php');
    exit;
}

try {
    $websiteDb = getWebsiteConnection();
    if ($websiteDb) {
        $stmt = $websiteDb->prepare("INSERT INTO volunteer_applications (first_name, last_name, email, phone, profession, experience, opportunity, availability, duration, skills, motivation, comments, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())");
        $stmt->bind_param('ssssisssssss', $firstName, $lastName, $email, $phone, $profession, $experience, $opportunity, $availability, $duration, $skills, $motivation, $comments);
        if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
        $submissionId = $stmt->insert_id;
        $stmt->close();
    }

    $fullName = $firstName . ' ' . $lastName;
    $contentBlocks = [
        ['type' => 'table', 'data' => [
            'Applicant'      => $fullName,
            'Email'          => $email,
            'Phone'          => $phone,
            'Profession'     => $profession,
            'Experience'     => $experience . ' years',
            'Opportunity'    => $opportunity,
            'Availability'   => $availability,
            'Preferred Duration' => $duration,
            'Submitted'      => date('d M Y h:i A'),
        ]],
        'Skills & Qualifications:',
        $skills,
        'Motivation:',
        $motivation,
    ];
    if ($comments) {
        $contentBlocks[] = 'Additional Comments:';
        $contentBlocks[] = $comments;
    }
    $contentBlocks[] = 'A new volunteer application has been received on the ISNM website. Please review and follow up.';

    $cta = ['url' => 'https://isnm.ac.ug/dashboards/director-general.php', 'text' => 'View in Dashboard'];
    notifyDirectorGeneral('New Volunteer Application: ' . $fullName . ' - ' . $opportunity, $contentBlocks, $cta);

    try {
        $staffsDb = getStaffConnection();
        if ($staffsDb) {
            $notifStmt = $staffsDb->prepare("INSERT INTO notifications (title, message, type, priority, audience, created_by, created_at) VALUES (?, ?, 'form_submission', 'normal', 'director_general', 0, NOW())");
            $notifTitle = 'New Volunteer: ' . $fullName;
            $notifMsg = $fullName . ' (' . $profession . ') applied as volunteer for "' . $opportunity . '". Email: ' . $email . ', Phone: ' . $phone;
            $notifStmt->bind_param('ss', $notifTitle, $notifMsg);
            if (!$notifStmt->execute()) { error_log('$notifStmt execute failed: ' . ($notifStmt->error ?? 'unknown')); };
            $notifStmt->close();
        }
    } catch (Exception $e) {
        error_log('Volunteer notification log error: ' . $e->getMessage());
    }

    try {
        $staffsDb = getStaffConnection();
        if ($staffsDb) {
            $logStmt = $staffsDb->prepare("INSERT INTO activity_log (user_id, activity, details, ip_address, created_at) VALUES (0, ?, ?, ?, NOW())");
            $logActivity = 'Volunteer Application';
            $logDetails = $fullName . ' (' . $email . ') applied as volunteer for: ' . $opportunity;
            $logIp = $_SERVER['REMOTE_ADDR'] ?? '';
            $logStmt->bind_param('sss', $logActivity, $logDetails, $logIp);
            if (!$logStmt->execute()) { error_log('$logStmt execute failed: ' . ($logStmt->error ?? 'unknown')); };
            $logStmt->close();
        }
    } catch (Exception $e) {
        error_log('Volunteer activity log error: ' . $e->getMessage());
    }

    $_SESSION['success_message'] = 'Thank you, ' . $firstName . '! Your volunteer application has been submitted successfully. We will contact you about available opportunities.';
    header('Location: volunteer.php');
    exit;

} catch (Exception $e) {
    error_log('Volunteer form error: ' . $e->getMessage());
    $_SESSION['error_message'] = 'Sorry, something went wrong. Please try again later or contact us directly.';
    header('Location: volunteer.php');
    exit;
}
