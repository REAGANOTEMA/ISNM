<?php
require_once __DIR__ . '/includes/config_enhanced.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/email_notifications.php';

session_start();

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
    $fileName = uniqid() . '.' . $fileType;
    $filePath = $uploadDir . '/' . $fileName;
    if (move_uploaded_file($file['tmp_name'], $filePath)) {
        return ['success' => true, 'path' => $filePath];
    }
    return ['success' => false, 'message' => 'Failed to move uploaded file'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $applicationId = generateApplicationId();

        $uploadDir = 'application_uploads';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
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

        // Use website_db for student applications
        $websiteDb = getWebsiteConnection();
        if (!$websiteDb) {
            throw new Exception('Database connection failed');
        }

        $stmt = $websiteDb->prepare("
            INSERT INTO student_applications (
                application_number, first_name, surname, other_name,
                date_of_birth, gender, nationality, phone, email, address,
                program_applied, previous_school, uce_results, uace_results,
                status, submitted_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending', NOW())
        ");

        $fullName = $_POST['firstName'] . ' ' . ($_POST['otherName'] ?? '') . ' ' . $_POST['surname'];
        $address = ($_POST['village'] ?? '') . ', ' . ($_POST['homeDistrict'] ?? '');
        $previousSchool = $_POST['previousSchool'] ?? '';

        $stmt->bind_param('ssssssssssssss',
            $applicationId,
            $_POST['firstName'],
            $_POST['surname'],
            $_POST['otherName'] ?? null,
            $_POST['dateOfBirth'],
            $_POST['gender'],
            $_POST['nationality'],
            $_POST['contactNumber'],
            $_POST['email'],
            $address,
            $_POST['course'],
            $previousSchool,
            $_POST['uceIndexNumber'] ?? null,
            $_POST['uceYear'] ?? null
        );
        $stmt->execute();
        $stmt->close();

        // Send confirmation to applicant
        $confirmSubject = "Application Received - Iganga School of Nursing & Midwifery";
        $confirmContent = [
            'Dear ' . $_POST['firstName'] . ',',
            'Thank you for applying to Iganga School of Nursing and Midwifery. Your application has been received successfully.',
            ['type' => 'table', 'data' => [
                'Application ID' => $applicationId,
                'Program'        => $_POST['course'],
                'Level'          => $_POST['levelApplying'],
                'Intake'         => $_POST['intakePeriod'],
                'Status'         => 'Pending Review',
            ]],
            'Your application is now under review. You will be contacted for an interview shortly.',
            'For inquiries, call 0782 990 403 (Principal) or 0782 633 253 (Deputy Principal).',
        ];
        $confirmHtml = buildProfessionalEmailTemplate('Application Confirmation', $confirmContent);
        sendProfessionalEmail($_POST['email'], $fullName, $confirmSubject, $confirmHtml);

        // Notify all directors
        $dirContent = [
            'A new student application has been submitted and requires review.',
            ['type' => 'table', 'data' => [
                'Application #'  => $applicationId,
                'Applicant'      => $fullName,
                'Program'        => $_POST['course'],
                'Level'          => $_POST['levelApplying'],
                'Intake'         => $_POST['intakePeriod'],
                'Phone'          => $_POST['contactNumber'],
                'Email'          => $_POST['email'],
                'Submitted'      => date('d M Y h:i A'),
            ]],
            'Please log in to the dashboard to review this application and take appropriate action.',
        ];
        $dirCta = ['url' => 'https://isnm.ac.ug/dashboards/director-admissions.php', 'text' => 'Review Application'];
        $sent = notifyAllDirectors('New Application: ' . $fullName . ' - ' . $_POST['course'], $dirContent, $dirCta);

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