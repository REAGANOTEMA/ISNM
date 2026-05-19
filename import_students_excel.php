<?php
// ISNM Student Excel Import System
// Professional Excel import functionality for students_data folder

error_reporting(E_ALL);
ini_set('display_errors', 1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Use the app's existing database configuration
require_once 'config/database.php';
require_once 'includes/functions.php';

// Get students database connection
$conn = getStudentsConnection();

if (!$conn) {
    die("Connection to students database failed");
}

// Set charset
$conn->set_charset("utf8mb4");

// Function to process Excel file
function processExcelFile($filePath, $conn) {
    require_once 'vendor/autoload.php';
    
    try {
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filePath);
        $worksheet = $spreadsheet->getActiveSheet();
        
        $imported = 0;
        $skipped = 0;
        $errors = [];
        
        // Get headers
        $headers = [];
        foreach ($worksheet->getRowIterator() as $rowIndex => $row) {
            if ($rowIndex === 1) {
                foreach ($row as $cell) {
                    if ($cell !== null) {
                        $headers[] = strtolower(str_replace([' ', '-', '/', '(', ')'], '_', $cell->getValue()));
                    }
                }
                continue;
            }
            break;
        }
        
        // Process data rows
        foreach ($worksheet->getRowIterator() as $rowIndex => $row) {
            if ($rowIndex <= 1) continue; // Skip header row
            
            $data = [];
            foreach ($row as $cellIndex => $cell) {
                $value = $cell ? trim($cell->getValue()) : '';
                $headerKey = $headers[$cellIndex] ?? 'column_' . $cellIndex;
                $data[$headerKey] = $value;
            }
            
            // Enhanced validation and mapping
            $fullName = trim($data['full_name'] ?? $data['name'] ?? '');
            $regNumber = trim($data['registration_number'] ?? $data['reg_no'] ?? '');
            
            if (empty($fullName) || empty($regNumber)) {
                $skipped++;
                continue;
            }
            
            // Check for duplicate registration number
            $checkSql = "SELECT id FROM students WHERE registration_number = ?";
            $checkStmt = $conn->prepare($checkSql);
            $checkStmt->bind_param("s", $regNumber);
            $checkStmt->execute();
            $checkResult = $checkStmt->get_result();
            
            if ($checkResult->num_rows > 0) {
                $skipped++;
                continue;
            }
            
            // Enhanced data mapping
            $nationalId = trim($data['national_student_id_number'] ?? $data['national_id'] ?? '');
            $indexNumber = trim($data['index_number'] ?? '');
            $mobileNumber = trim($data['mobile_number'] ?? $data['phone'] ?? '');
            $email = trim($data['email'] ?? '');
            $course = trim($data['course'] ?? '');
            $year = is_numeric($data['year']) ? (int)$data['year'] : null;
            $setName = trim($data['set_name'] ?? $data['class'] ?? '');
            $intakeDate = !empty($data['intake_date']) ? date('Y-m-d', strtotime($data['intake_date'])) : null;
            $dob = !empty($data['date_of_birth']) ? date('Y-m-d', strtotime($data['date_of_birth'])) : null;
            $gender = !empty($data['gender']) ? ucfirst(strtolower($data['gender'])) : 'Other';
            $address = trim($data['address'] ?? '');
            $emergencyName = trim($data['emergency_contact_name'] ?? '');
            $emergencyPhone = trim($data['emergency_contact_phone'] ?? '');
            
            // Insert student record
            $sql = "INSERT INTO students (full_name, registration_number, national_student_id_number, index_number, mobile_number, email, course, year, set_name, intake_date, date_of_birth, gender, address, emergency_contact_name, emergency_contact_phone) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssssssissssss", 
                $fullName,
                $regNumber,
                !empty($nationalId) ? $nationalId : null,
                !empty($indexNumber) ? $indexNumber : null,
                !empty($mobileNumber) ? $mobileNumber : null,
                !empty($email) ? $email : null,
                !empty($course) ? $course : null,
                $year,
                !empty($setName) ? $setName : null,
                $intakeDate,
                $dob,
                $gender,
                !empty($address) ? $address : null,
                !empty($emergencyName) ? $emergencyName : null,
                !empty($emergencyPhone) ? $emergencyPhone : null
            );
            
            if ($stmt->execute()) {
                $imported++;
            } else {
                $errors[] = "Failed to import: $fullName (Error: " . $stmt->error . ")";
            }
        }
        
        return [
            'imported' => $imported,
            'skipped' => $skipped,
            'errors' => $errors,
            'total' => $imported + $skipped + count($errors)
        ];
        
    } catch (Exception $e) {
        return [
            'error' => 'Error processing file: ' . $e->getMessage()
        ];
    }
}

// Function to get all Excel files
function getExcelFiles($directory) {
    $files = [];
    $iterator = new DirectoryIterator($directory);
    
    foreach ($iterator as $fileInfo) {
        if ($fileInfo->isFile() && $fileInfo->getExtension() === 'xlsx') {
            $files[] = $fileInfo->getPathname();
        }
    }
    
    return $files;
}

// Main processing
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['import'])) {
    $studentsDataDir = 'students_data/';
    $excelFiles = getExcelFiles($studentsDataDir);
    
    $totalImported = 0;
    $totalSkipped = 0;
    $totalErrors = 0;
    
    foreach ($excelFiles as $file) {
        $result = processExcelFile($file, $conn);
        
        if (isset($result['error'])) {
            $_SESSION['error'] = $result['error'];
            break;
        } else {
            $totalImported += $result['imported'];
            $totalSkipped += $result['skipped'];
            $totalErrors += count($result['errors']);
        }
    }
    
    if (!isset($_SESSION['error'])) {
        $_SESSION['success'] = "Import completed! Total imported: $totalImported, Skipped: $totalSkipped, Errors: $totalErrors";
    }
    
    header("Location: import_students_excel.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Import Student Excel Data - ISNM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .import-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0,0.1);
            padding: 30px;
            max-width: 1000px;
            margin: 0 auto;
        }
        
        .import-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .file-list {
            margin-bottom: 20px;
        }
        
        .file-item {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .import-btn {
            background: #28a745;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
        }
        
        .import-btn:hover {
            background: #218838;
        }
        
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        
        .alert-danger {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        
        .stats {
            display: flex;
            justify-content: space-around;
            margin-bottom: 20px;
        }
        
        .stat-item {
            text-align: center;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
            border: 1px solid #e9ecef;
        }
        
        .stat-number {
            font-size: 24px;
            font-weight: bold;
            color: #007bff;
        }
        
        .stat-label {
            font-size: 14px;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <div class="import-container">
        <div class="import-header">
            <h2><i class="fas fa-graduation-cap me-2"></i>Import Student Excel Data</h2>
            <p>Process Excel files from students_data folder</p>
        </div>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger">
                <?php 
                    echo htmlspecialchars($_SESSION['error']);
                    unset($_SESSION['error']);
                    ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <?php 
                    echo htmlspecialchars($_SESSION['success']);
                    unset($_SESSION['success']);
                    ?>
            </div>
        <?php endif; ?>
        
        <?php
        $studentsDataDir = 'students_data/';
        $excelFiles = getExcelFiles($studentsDataDir);
        
        if (empty($excelFiles)) {
            echo '<p>No Excel files found in students_data folder.</p>';
        } else {
            ?>
            <div class="stats">
                <div class="stat-item">
                    <div class="stat-number"><?php echo count($excelFiles); ?></div>
                    <div class="stat-label">Excel Files</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?php 
                        $totalStudents = 0;
                        foreach ($excelFiles as $file) {
                            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file);
                            $totalStudents += $spreadsheet->getActiveSheet()->getHighestRow() - 1;
                        }
                        echo $totalStudents;
                    ?></div>
                    <div class="stat-label">Total Students</div>
                </div>
            </div>
            
            <form method="POST">
                <div class="file-list">
                    <h4>Available Excel Files:</h4>
                    <?php
                    foreach ($excelFiles as $file) {
                        $fileName = basename($file);
                        echo '<div class="file-item">';
                        echo '<span>' . $fileName . '</span>';
                        echo '</div>';
                    }
                    ?>
                </div>
                <div class="text-center mt-3">
                    <button type="submit" name="import" class="import-btn">Import All Files</button>
                </div>
            </form>
            <?php
        }
        ?>
    </div>
</body>
</html>
