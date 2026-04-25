<?php
// Application processing script
session_start();

// Database configuration
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'isnm_school';

// Create connection
$conn = new mysqli($host, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Generate unique application ID
function generateApplicationId() {
    $prefix = 'ISNM';
    $year = date('Y');
    $random = mt_rand(1000, 9999);
    return $prefix . $year . $random;
}

// Handle file upload
function handleFileUpload($file, $allowedTypes, $maxSize, $uploadDir) {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'File upload error'];
    }
    
    // Check file size
    if ($file['size'] > $maxSize) {
        return ['success' => false, 'message' => 'File size exceeds limit'];
    }
    
    // Check file type
    $fileType = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($fileType, $allowedTypes)) {
        return ['success' => false, 'message' => 'Invalid file type'];
    }
    
    // Generate unique filename
    $fileName = uniqid() . '.' . $fileType;
    $filePath = $uploadDir . '/' . $fileName;
    
    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $filePath)) {
        return ['success' => true, 'path' => $filePath];
    } else {
        return ['success' => false, 'message' => 'Failed to move uploaded file'];
    }
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Generate application ID
        $applicationId = generateApplicationId();
        
        // Create upload directories if they don't exist
        $uploadDir = 'application_uploads';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        // Handle document uploads
        $academicDocResult = handleFileUpload(
            $_FILES['academicDocument'],
            ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx'],
            5 * 1024 * 1024, // 5MB
            $uploadDir
        );
        
        $photoResult = handleFileUpload(
            $_FILES['photo'],
            ['jpg', 'jpeg', 'png', 'gif'],
            2 * 1024 * 1024, // 2MB
            $uploadDir
        );
        
        if (!$academicDocResult['success'] || !$photoResult['success']) {
            throw new Exception('File upload failed: ' . 
                ($academicDocResult['success'] ? $photoResult['message'] : $academicDocResult['message']));
        }
        
        // Prepare data for insertion
        $data = [
            'application_id' => $applicationId,
            'first_name' => $_POST['firstName'],
            'surname' => $_POST['surname'],
            'other_name' => $_POST['otherName'] ?? null,
            'date_of_birth' => $_POST['dateOfBirth'],
            'gender' => $_POST['gender'],
            'nationality' => $_POST['nationality'],
            'country_of_residence' => $_POST['nationality'],
            'home_district' => $_POST['homeDistrict'] ?? null,
            'village' => $_POST['village'] ?? null,
            'religion' => $_POST['religion'] ?? null,
            'email' => $_POST['email'],
            'phone' => $_POST['contactNumber'],
            'marital_status' => $_POST['maritalStatus'] ?? null,
            'spouse_name' => $_POST['spouseName'] ?? null,
            'number_of_children' => $_POST['numberOfChildren'] ?? 0,
            'disability' => $_POST['disability'] ?? 'No',
            'disability_type' => $_POST['disabilityType'] ?? null,
            'disability_description' => $_POST['disabilityDescription'] ?? null,
            'fee_payer' => $_POST['feePayer'] ?? null,
            'parent_name' => $_POST['parentName'] ?? null,
            'parent_nationality' => $_POST['parentNationality'] ?? null,
            'parent_address' => $_POST['parentAddress'] ?? null,
            'parent_phone' => $_POST['parentPhone'] ?? null,
            'parent_email' => $_POST['parentEmail'] ?? null,
            'emergency_contact_name' => $_POST['emergencyContactName'] ?? null,
            'emergency_contact_phone' => $_POST['emergencyContactPhone'] ?? null,
            'emergency_contact_email' => $_POST['emergencyContactEmail'] ?? null,
            'program_applied' => $_POST['course'],
            'level_applying' => $_POST['levelApplying'],
            'intake_period' => $_POST['intakePeriod'],
            'uce_index_number' => $_POST['uceIndexNumber'] ?? null,
            'uce_year' => $_POST['uceYear'] ?? null,
            'uce_english_grade' => $_POST['uceEnglish'] ?? null,
            'uce_maths_grade' => $_POST['uceMath'] ?? null,
            'uce_biology_grade' => $_POST['uceBiology'] ?? null,
            'uce_chemistry_grade' => $_POST['uceChemistry'] ?? null,
            'uce_physics_grade' => $_POST['ucePhysics'] ?? null,
            'diploma_exam_number' => $_POST['diplomaExamNumber'] ?? null,
            'diploma_year_completion' => $_POST['diplomaYearCompletion'] ?? null,
            'diploma_year_entry' => $_POST['diplomaYearEntry'] ?? null,
            'practicing_license' => $_POST['practicingLicense'] ?? null,
            'course_motivation' => $_POST['motivation'],
            'academic_document_path' => $academicDocResult['path'],
            'photo_path' => $photoResult['path'],
            'status' => 'pending',
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        // Build SQL query
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        $types = str_repeat('s', count($data));
        
        $sql = "INSERT INTO applications ($columns) VALUES ($placeholders)";
        
        // Prepare and execute statement
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        $stmt->bind_param($types, ...array_values($data));
        
        if (!$stmt->execute()) {
            throw new Exception("Execute failed: " . $stmt->error);
        }
        
        // Send confirmation email (optional - requires email configuration)
        $to = $_POST['email'];
        $subject = "Application Received - Iganga School of Nursing & Midwifery";
        $message = "
        <html>
        <head>
        <title>Application Confirmation</title>
        </head>
        <body>
        <h2>Application Received Successfully</h2>
        <p>Dear " . $_POST['firstName'] . " " . $_POST['surname'] . ",</p>
        <p>Thank you for applying to Iganga School of Nursing and Midwifery. Your application has been received with the following details:</p>
        <ul>
            <li>Application ID: " . $applicationId . "</li>
            <li>Program: " . $_POST['course'] . "</li>
            <li>Level: " . $_POST['levelApplying'] . "</li>
            <li>Intake: " . $_POST['intakePeriod'] . "</li>
        </ul>
        <p>Your application is currently under review. You will be contacted for an interview shortly.</p>
        <p>Please keep your Application ID for future reference.</p>
        <p>For any inquiries, please contact us at:</p>
        <ul>
            <li>Phone: 0782 990 403 (Principal)</li>
            <li>Phone: 0782 633 253 (Deputy Principal)</li>
            <li>Email: iganganursingschool@gmail.com</li>
        </ul>
        <p>Best regards,<br>
        Iganga School of Nursing and Midwifery<br>
        Admissions Office</p>
        </body>
        </html>
        ";
        
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $headers .= "From: admissions@isnm.ac.ug" . "\r\n";
        
        // Note: Email sending requires proper SMTP configuration
        // mail($to, $subject, $message, $headers);
        
        // Set success message
        $_SESSION['success_message'] = "Application submitted successfully! Your Application ID is: " . $applicationId . ". Please save this ID for future reference.";
        
        // Redirect to success page
        header('Location: application-success.php');
        exit;
        
    } catch (Exception $e) {
        $_SESSION['error_message'] = "Error submitting application: " . $e->getMessage();
        header('Location: application.php');
        exit;
    }
}

$conn->close();
?>
