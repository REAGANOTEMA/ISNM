<?php
require_once __DIR__ . '/includes/config_enhanced.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/email_notifications.php';

session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['error_message'] = 'Invalid security token. Please try again.';
        header('Location: application.php');
        exit;
    }
}

function generateApplicationId() {
    $prefix = 'ISNM';
    $year = date('Y');
    $random = mt_rand(1000, 9999);
    return $prefix . $year . $random;
}

function handleFileUpload($file, $allowedTypes, $maxSize, $uploadDir) {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'File upload error'];
    }
    if ($file['size'] > $maxSize) {
        return ['success' => false, 'message' => 'File size exceeds limit'];
    }
    $fileType = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($fileType, $allowedTypes)) {
        return ['success' => false, 'message' => 'Invalid file type'];
    }
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($file['tmp_name']);
    $allowedMimes = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'application/pdf' => 'pdf', 'application/msword' => 'doc', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx'];
    if (!isset($allowedMimes[$mimeType])) {
        return ['success' => false, 'message' => 'Invalid file content type'];
    }
    $fileName = uniqid() . '.' . $allowedMimes[$mimeType];
    $filePath = $uploadDir . '/' . $fileName;
    if (move_uploaded_file($file['tmp_name'], $filePath)) {
        return ['success' => true, 'path' => $filePath];
    }
    return ['success' => false, 'message' => 'Failed to move uploaded file'];
}

function uploadFileIfPresent($fieldName, $allowedTypes, $maxSize, $uploadDir) {
    if (isset($_FILES[$fieldName]) && $_FILES[$fieldName]['error'] === UPLOAD_ERR_OK) {
        return handleFileUpload($_FILES[$fieldName], $allowedTypes, $maxSize, $uploadDir);
    }
    return ['success' => true, 'path' => null];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $websiteDb = getWebsiteConnection();
        if ($websiteDb) {
            $websiteDb->query("CREATE TABLE IF NOT EXISTS student_applications (
                id INT AUTO_INCREMENT PRIMARY KEY,
                application_number VARCHAR(50) NOT NULL,
                first_name VARCHAR(100) NOT NULL,
                surname VARCHAR(100) NOT NULL,
                other_name VARCHAR(100) DEFAULT NULL,
                date_of_birth DATE DEFAULT NULL,
                gender VARCHAR(20) DEFAULT NULL,
                nationality VARCHAR(100) DEFAULT 'Ugandan',
                phone VARCHAR(20) DEFAULT NULL,
                email VARCHAR(255) DEFAULT NULL,
                address TEXT DEFAULT NULL,
                program_applied VARCHAR(255) DEFAULT NULL,
                previous_school VARCHAR(255) DEFAULT NULL,
                uce_results VARCHAR(255) DEFAULT NULL,
                uace_results VARCHAR(255) DEFAULT NULL,
                status VARCHAR(50) DEFAULT 'Pending',
                submitted_at DATETIME DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_status (status),
                INDEX idx_submitted (submitted_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
        }
        $applicationId = generateApplicationId();
        $level = sanitizeInput($_POST['levelApplying'] ?? '');

        $uploadDir = 'application_uploads';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $academicDocResult = handleFileUpload(
            $_FILES['academicDocument'],
            ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx'],
            5 * 1024 * 1024,
            $uploadDir
        );

        $photoResult = handleFileUpload(
            $_FILES['photo'],
            ['jpg', 'jpeg', 'png', 'gif'],
            2 * 1024 * 1024,
            $uploadDir
        );

        if (!$academicDocResult['success'] || !$photoResult['success']) {
            throw new Exception('File upload failed: ' .
                ($academicDocResult['success'] ? $photoResult['message'] : $academicDocResult['message']));
        }

        $docFields = [
            'uceCertificateDoc' => ['pdf', 'jpg', 'jpeg', 'png'],
            'uaceCertificateDoc' => ['pdf', 'jpg', 'jpeg', 'png'],
        ];

        if ($level === 'Diploma Extension') {
            $docFields['unmebResultSlip'] = ['pdf', 'jpg', 'jpeg', 'png'];
            $docFields['unmebCertificate'] = ['pdf', 'jpg', 'jpeg', 'png'];
            $docFields['enrolmentCertificate'] = ['pdf', 'jpg', 'jpeg', 'png'];
            $docFields['practicingLicenseDoc'] = ['pdf', 'jpg', 'jpeg', 'png'];
            $docFields['academicTranscript'] = ['pdf', 'jpg', 'jpeg', 'png'];
        }

        $uploadedDocs = [];
        foreach ($docFields as $field => $types) {
            $result = uploadFileIfPresent($field, $types, 5 * 1024 * 1024, $uploadDir);
            if (!$result['success']) {
                throw new Exception('Upload failed for ' . $field . ': ' . $result['message']);
            }
            $uploadedDocs[$field] = $result['path'];
        }

        $websiteDb = getWebsiteConnection();
        if (!$websiteDb) {
            throw new Exception('Database connection failed');
        }

        $firstName = sanitizeInput($_POST['firstName'] ?? '');
        $surname   = sanitizeInput($_POST['surname'] ?? '');
        $otherName = sanitizeInput($_POST['otherName'] ?? '');
        $dob       = $_POST['dateOfBirth'] ?? '';
        $gender    = $_POST['gender'] ?? '';
        $nationality = sanitizeInput($_POST['nationality'] ?? '');
        $contactNumber = trim($_POST['contactNumber'] ?? '');
        $appEmail  = trim($_POST['email'] ?? '');
        $course    = sanitizeInput($_POST['course'] ?? '');
        $previousSchool = sanitizeInput($_POST['previousSchool'] ?? '');

        if (!$firstName || !$surname || !$dob || !$gender || !$contactNumber || !$appEmail || !$course) {
            throw new Exception('Please fill in all required fields.');
        }

        if (!validateEmail($appEmail)) {
            throw new Exception('Please enter a valid email address.');
        }

        if (!validatePhone($contactNumber)) {
            throw new Exception('Please enter a valid phone number.');
        }

        $address = sanitizeInput(($_POST['village'] ?? '') . ', ' . ($_POST['homeDistrict'] ?? ''));

        $stmt = $websiteDb->prepare("
            INSERT INTO student_applications (
                application_number, first_name, surname, other_name,
                date_of_birth, gender, nationality, phone, email, address,
                program_applied, previous_school, uce_results, uace_results,
                status, submitted_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending', NOW())
        ");

        $uceResults = sanitizeInput($_POST['uceIndexNumber'] ?? '') . ' (' . sanitizeInput($_POST['uceYear'] ?? '') . ')';
        $uaceResults = sanitizeInput($_POST['uaceIndexNumber'] ?? '') . ' (' . sanitizeInput($_POST['uaceYear'] ?? '') . ')';

        $stmt->bind_param('ssssssssssssss',
            $applicationId,
            $firstName,
            $surname,
            $otherName ?: null,
            $dob,
            $gender,
            $nationality,
            $contactNumber,
            $appEmail,
            $address,
            $course,
            $previousSchool,
            $uceResults ?: null,
            $uaceResults ?: null
        );
        if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
        $applicationDbId = $stmt->insert_id;
        $stmt->close();

        $fullName = trim($firstName . ' ' . $otherName . ' ' . $surname);

        $confirmSubject = "Application Received - Iganga School of Nursing & Midwifery";
        $confirmContent = [
            'Dear ' . $firstName . ',',
            'Thank you for applying to Iganga School of Nursing and Midwifery. Your application has been received successfully.',
            ['type' => 'table', 'data' => [
                'Application ID' => $applicationId,
                'Program'        => $course,
                'Level'          => $level,
                'Intake'         => $_POST['intakePeriod'] ?? '',
                'Status'         => 'Pending Review',
            ]],
            'Your application is now under review. You will be contacted for an interview shortly.',
            'For inquiries, call 0782 990 403 (Principal) or 0782 633 253 (Deputy Principal).',
        ];
        $confirmHtml = buildProfessionalEmailTemplate('Application Confirmation', $confirmContent);
        sendProfessionalEmail($appEmail, $fullName, $confirmSubject, $confirmHtml);

        $dirContent = [
            'A new student application has been submitted and requires review.',
            ['type' => 'table', 'data' => [
                'Application #'  => $applicationId,
                'Applicant'      => $fullName,
                'Program'        => $course,
                'Level'          => $level,
                'Intake'         => $_POST['intakePeriod'] ?? '',
                'Phone'          => $contactNumber,
                'Email'          => $appEmail,
                'Submitted'      => date('d M Y h:i A'),
            ]],
            'Please log in to the dashboard to review this application and take appropriate action.',
        ];
        $dirCta = ['url' => 'https://isnm.ac.ug/dashboards/director-admissions.php', 'text' => 'Review Application'];
        $sent = notifyAllDirectors('New Application: ' . $fullName . ' - ' . $course, $dirContent, $dirCta);

        try {
            $staffsDb = getStaffConnection();
            if ($staffsDb) {
                $notifStmt = $staffsDb->prepare("INSERT INTO notifications (title, message, type, priority, audience, created_by, created_at) VALUES (?, ?, 'application', 'high', 'admissions', 0, NOW())");
                $notifTitle = 'New Application: ' . $fullName;
                $notifMsg = 'Application #' . $applicationId . ' submitted by ' . $fullName . ' for ' . $course . ' (' . $level . '). Phone: ' . $contactNumber . ', Email: ' . $appEmail;
                $notifStmt->bind_param('ss', $notifTitle, $notifMsg);
                if (!$notifStmt->execute()) { error_log('$notifStmt execute failed: ' . ($notifStmt->error ?? 'unknown')); };
                $notifStmt->close();
            }
        } catch (Exception $e) {
            error_log('Application notification log error: ' . $e->getMessage());
        }

        try {
            $staffsDb = getStaffConnection();
            if ($staffsDb) {
                $logStmt = $staffsDb->prepare("INSERT INTO activity_log (user_id, activity, details, ip_address, created_at) VALUES (0, ?, ?, ?, NOW())");
                $logActivity = 'Student Application';
                $logDetails = $fullName . ' (' . $appEmail . ') submitted application #' . $applicationId . ' for ' . $course;
                $logIp = $_SERVER['REMOTE_ADDR'] ?? '';
                $logStmt->bind_param('sss', $logActivity, $logDetails, $logIp);
                if (!$logStmt->execute()) { error_log('$logStmt execute failed: ' . ($logStmt->error ?? 'unknown')); };
                $logStmt->close();
            }
        } catch (Exception $e) {
            error_log('Application activity log error: ' . $e->getMessage());
        }

        error_log('Application ' . $applicationId . ' created. Notifications sent to ' . $sent . ' directors.');

        $_SESSION['success_message'] = "Application submitted successfully! Your Application ID is: " . $applicationId . ". Please save this ID for future reference.";
        header('Location: application-success.php');
        exit;

    } catch (Exception $e) {
        error_log('Application error: ' . $e->getMessage());
        $_SESSION['error_message'] = "Error submitting application: " . $e->getMessage();
        header('Location: application.php');
        exit;
    }
}

header('Location: application.php');
exit;
