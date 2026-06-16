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

function uploadFileIfPresent($fieldName, $allowedTypes, $maxSize, $uploadDir) {
    if (isset($_FILES[$fieldName]) && $_FILES[$fieldName]['error'] === UPLOAD_ERR_OK) {
        return handleFileUpload($_FILES[$fieldName], $allowedTypes, $maxSize, $uploadDir);
    }
    return ['success' => true, 'path' => null];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $applicationId = generateApplicationId();
        $level = $_POST['levelApplying'] ?? '';

        $uploadDir = 'application_uploads';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        // Upload required general documents
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

        // Upload conditional documents
        $docFields = [
            'uceCertificateDoc' => ['pdf', 'jpg', 'jpeg', 'png'],
            'uaceCertificateDoc' => ['pdf', 'jpg', 'jpeg', 'png'],
        ];

        // Diploma-specific required documents
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

        // Build comprehensive additional data
        $additionalData = [
            'otherName' => $_POST['otherName'] ?? '',
            'countryOfResidence' => $_POST['countryOfResidence'] ?? '',
            'homeDistrict' => $_POST['homeDistrict'] ?? '',
            'village' => $_POST['village'] ?? '',
            'religion' => $_POST['religion'] ?? '',
            'maritalStatus' => $_POST['maritalStatus'] ?? '',
            'spouseName' => $_POST['spouseName'] ?? '',
            'numberOfChildren' => $_POST['numberOfChildren'] ?? '',
            'disability' => $_POST['disability'] ?? '',
            'disabilityType' => $_POST['disabilityType'] ?? '',
            'disabilityDescription' => $_POST['disabilityDescription'] ?? '',
            'feePayer' => $_POST['feePayer'] ?? '',
            'parentName' => $_POST['parentName'] ?? '',
            'parentNationality' => $_POST['parentNationality'] ?? '',
            'parentAddress' => $_POST['parentAddress'] ?? '',
            'parentPhone' => $_POST['parentPhone'] ?? '',
            'parentEmail' => $_POST['parentEmail'] ?? '',
            'emergencyContactName' => $_POST['emergencyContactName'] ?? '',
            'emergencyContactPhone' => $_POST['emergencyContactPhone'] ?? '',
            'emergencyContactEmail' => $_POST['emergencyContactEmail'] ?? '',
            'levelApplying' => $level,
            'intakePeriod' => $_POST['intakePeriod'] ?? '',
            'previousSchool' => $_POST['previousSchool'] ?? '',
            'uceSchool' => $_POST['uceSchool'] ?? '',
            'uceIndexNumber' => $_POST['uceIndexNumber'] ?? '',
            'uceYear' => $_POST['uceYear'] ?? '',
            'uceEnglish' => $_POST['uceEnglish'] ?? '',
            'uceMath' => $_POST['uceMath'] ?? '',
            'uceBiology' => $_POST['uceBiology'] ?? '',
            'uceChemistry' => $_POST['uceChemistry'] ?? '',
            'ucePhysics' => $_POST['ucePhysics'] ?? '',
            'uceOther' => $_POST['uceOther'] ?? '',
            'uaceSchoolName' => $_POST['uaceSchoolName'] ?? '',
            'uaceIndexNumber' => $_POST['uaceIndexNumber'] ?? '',
            'uaceYear' => $_POST['uaceYear'] ?? '',
            'uaceSubject1' => $_POST['uaceSubject1'] ?? '',
            'uaceGrade1' => $_POST['uaceGrade1'] ?? '',
            'uaceSubject2' => $_POST['uaceSubject2'] ?? '',
            'uaceGrade2' => $_POST['uaceGrade2'] ?? '',
            'uaceSubject3' => $_POST['uaceSubject3'] ?? '',
            'uaceGrade3' => $_POST['uaceGrade3'] ?? '',
            'uaceSubsidiary1' => $_POST['uaceSubsidiary1'] ?? '',
            'uaceSubGrade1' => $_POST['uaceSubGrade1'] ?? '',
            'uaceSubsidiary2' => $_POST['uaceSubsidiary2'] ?? '',
            'uaceSubGrade2' => $_POST['uaceSubGrade2'] ?? '',
            'diplomaExamNumber' => $_POST['diplomaExamNumber'] ?? '',
            'diplomaYearCompletion' => $_POST['diplomaYearCompletion'] ?? '',
            'diplomaYearEntry' => $_POST['diplomaYearEntry'] ?? '',
            'practicingLicense' => $_POST['practicingLicense'] ?? '',
            'diplomaPaper1' => $_POST['diplomaPaper1'] ?? '',
            'diplomaPaper2' => $_POST['diplomaPaper2'] ?? '',
            'diplomaPaper3' => $_POST['diplomaPaper3'] ?? '',
            'diplomaOsce' => $_POST['diplomaOsce'] ?? '',
            'diplomaDistinctions' => $_POST['diplomaDistinctions'] ?? '',
            'diplomaCredits' => $_POST['diplomaCredits'] ?? '',
            'diplomaPasses' => $_POST['diplomaPasses'] ?? '',
            'diplomaCgpa' => $_POST['diplomaCgpa'] ?? '',
            'sportsActivities' => $_POST['sportsActivities'] ?? '',
            'leadershipPositions' => $_POST['leadershipPositions'] ?? '',
            'motivation' => $_POST['motivation'] ?? '',
        ];
        $additionalDataJson = json_encode($additionalData);

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

        $fullName = trim($_POST['firstName'] . ' ' . ($_POST['otherName'] ?? '') . ' ' . $_POST['surname']);
        $address = trim(($_POST['village'] ?? '') . ', ' . ($_POST['homeDistrict'] ?? ''));
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
        $applicationDbId = $stmt->insert_id;
        $stmt->close();

        // Store additional data and document paths
        if ($applicationDbId) {
            $updateStmt = $websiteDb->prepare("
                UPDATE student_applications SET
                    additional_data = ?,
                    academic_document_path = ?,
                    photo_path = ?,
                    uce_certificate_path = ?,
                    uace_certificate_path = ?,
                    unmeb_result_slip_path = ?,
                    unmeb_certificate_path = ?,
                    enrolment_certificate_path = ?,
                    practicing_license_path = ?,
                    academic_transcript_path = ?
                WHERE id = ?
            ");
            $updateStmt->bind_param('ssssssssssi',
                $additionalDataJson,
                $academicDocResult['path'],
                $photoResult['path'],
                $uploadedDocs['uceCertificateDoc'] ?? null,
                $uploadedDocs['uaceCertificateDoc'] ?? null,
                $uploadedDocs['unmebResultSlip'] ?? null,
                $uploadedDocs['unmebCertificate'] ?? null,
                $uploadedDocs['enrolmentCertificate'] ?? null,
                $uploadedDocs['practicingLicenseDoc'] ?? null,
                $uploadedDocs['academicTranscript'] ?? null,
                $applicationDbId
            );
            $updateStmt->execute();
            $updateStmt->close();
        }

        // Send confirmation to applicant
        $confirmSubject = "Application Received - Iganga School of Nursing & Midwifery";
        $confirmContent = [
            'Dear ' . $_POST['firstName'] . ',',
            'Thank you for applying to Iganga School of Nursing and Midwifery. Your application has been received successfully.',
            ['type' => 'table', 'data' => [
                'Application ID' => $applicationId,
                'Program'        => $_POST['course'],
                'Level'          => $level,
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
                'Level'          => $level,
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
