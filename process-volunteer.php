<?php
require_once __DIR__ . '/includes/config_enhanced.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/email_notifications.php';

session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: volunteer.php');
    exit;
}

$firstName   = trim($_POST['firstName'] ?? '');
$lastName    = trim($_POST['lastName'] ?? '');
$email       = trim($_POST['email'] ?? '');
$phone       = trim($_POST['phone'] ?? '');
$profession  = trim($_POST['profession'] ?? '');
$experience  = (int)($_POST['experience'] ?? 0);
$opportunity = trim($_POST['opportunity'] ?? '');
$availability = trim($_POST['availability'] ?? '');
$duration    = trim($_POST['duration'] ?? '');
$skills      = trim($_POST['skills'] ?? '');
$motivation  = trim($_POST['motivation'] ?? '');
$comments    = trim($_POST['comments'] ?? '');

if (!$firstName || !$lastName || !$email || !$phone || !$profession || !$opportunity || !$skills || !$motivation) {
    $_SESSION['error_message'] = 'Please fill in all required fields.';
    header('Location: volunteer.php');
    exit;
}

try {
    $websiteDb = getWebsiteConnection();
    if ($websiteDb) {
        $stmt = $websiteDb->prepare("INSERT INTO volunteer_applications (first_name, last_name, email, phone, profession, experience, opportunity, availability, duration, skills, motivation, comments, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())");
        $stmt->bind_param('sssssissssss', $firstName, $lastName, $email, $phone, $profession, $experience, $opportunity, $availability, $duration, $skills, $motivation, $comments);
        $stmt->execute();
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

    $_SESSION['success_message'] = 'Thank you, ' . $firstName . '! Your volunteer application has been submitted successfully. We will contact you about available opportunities.';
    header('Location: volunteer.php');
    exit;

} catch (Exception $e) {
    error_log('Volunteer form error: ' . $e->getMessage());
    $_SESSION['success_message'] = 'Thank you, ' . $firstName . '! Your volunteer application has been submitted. We will contact you soon.';
    header('Location: volunteer.php');
    exit;
}