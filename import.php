<?php
/**
 * ISNM Student Data Import Script
 * 
 * This script imports student data from Excel files in the 'students_data' folder
 * into the MySQL database using PhpSpreadsheet library.
 * 
 * Database: isnm_db
 * Table: students(id, full_name, registration_number, national_student_id_number, 
 *              index_number, mobile_number, course, year, set_name, gender)
 */

// Include required files
require 'vendor/autoload.php';
require 'config/database.php';
use PhpOffice\PhpSpreadsheet\IOFactory;

// Data folder configuration
define('DATA_FOLDER', __DIR__ . '/students_data');

// Statistics tracking
$stats = [
    'files_processed' => 0,
    'students_imported' => 0,
    'duplicates_skipped' => 0,
    'errors' => 0
];

/**
 * Establish database connection
 * @return mysqli
 */
function connectDatabase() {
    try {
        // Try to get connection using the shared database config
        $conn = getConnection();
        echo "Database connected successfully.\n";
        return $conn;
    } catch (Exception $e) {
        // If connection fails, try with alternative configuration
        echo "Attempting alternative database connection...\n";
        try {
            $conn = new mysqli('localhost', 'root', '', 'isnm_db');
            if ($conn->connect_error) {
                throw new Exception("Alternative connection also failed: " . $conn->connect_error);
            }
            $conn->set_charset('utf8mb4');
            echo "Database connected successfully using alternative method.\n";
            return $conn;
        } catch (Exception $e2) {
            die("Database connection failed. Please check:\n" .
                "1. XAMPP MySQL service is running\n" .
                "2. Database 'isnm_db' exists\n" .
                "3. User 'root' has access\n" .
                "Error: " . $e2->getMessage());
        }
    }
}

/**
 * Check if student already exists in database
 * @param mysqli $conn
 * @param string $registrationNumber
 * @return bool
 */
function studentExists($conn, $registrationNumber) {
    $stmt = $conn->prepare("SELECT id FROM students WHERE registration_number = ?");
    $stmt->bind_param("s", $registrationNumber);
    $stmt->execute();
    $result = $stmt->get_result();
    $exists = $result->num_rows > 0;
    $stmt->close();
    
    return $exists;
}

/**
 * Insert student into database
 * @param mysqli $conn
 * @param array $studentData
 * @return bool
 */
function insertStudent($conn, $studentData) {
    $stmt = $conn->prepare("INSERT INTO students (full_name, registration_number, national_student_id_number, index_number, mobile_number, course, year, set_name, gender) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssssiss", 
        $studentData['full_name'],
        $studentData['registration_number'],
        $studentData['national_student_id_number'],
        $studentData['index_number'],
        $studentData['mobile_number'],
        $studentData['course'],
        $studentData['year'],
        $studentData['set_name'],
        $studentData['gender']
    );
    
    $success = $stmt->execute();
    $stmt->close();
    
    return $success;
}

/**
 * Process a single Excel file
 * @param mysqli $conn
 * @param string $filePath
 * @param array $stats
 * @return bool
 */
function processExcelFile($conn, $filePath, &$stats) {
    try {
        echo "Processing file: " . basename($filePath) . "\n";
        
        // Load the spreadsheet
        $spreadsheet = IOFactory::load($filePath);
        $worksheet = $spreadsheet->getActiveSheet();
        $highestRow = $worksheet->getHighestRow();
        
        // Skip header row, start from row 2
        for ($row = 2; $row <= $highestRow; $row++) {
            // Read data from columns A-I
            $fullName = $worksheet->getCell('A' . $row)->getValue();
            $registrationNumber = $worksheet->getCell('B' . $row)->getValue();
            $nationalStudentIdNumber = $worksheet->getCell('C' . $row)->getValue();
            $indexNumber = $worksheet->getCell('D' . $row)->getValue();
            $mobileNumber = $worksheet->getCell('E' . $row)->getValue();
            $course = $worksheet->getCell('F' . $row)->getValue();
            $year = $worksheet->getCell('G' . $row)->getValue();
            $setName = $worksheet->getCell('H' . $row)->getValue();
            $gender = $worksheet->getCell('I' . $row)->getValue();
            
            // Skip empty rows
            if (empty($fullName) || empty($registrationNumber)) {
                continue;
            }
            
            // Prepare student data with proper sanitization
            $studentData = [
                'full_name' => trim($fullName),
                'registration_number' => trim($registrationNumber),
                'national_student_id_number' => trim($nationalStudentIdNumber),
                'index_number' => trim($indexNumber),
                'mobile_number' => trim($mobileNumber),
                'course' => trim($course),
                'year' => (int)$year,
                'set_name' => trim($setName),
                'gender' => trim($gender)
            ];
            
            // Check for duplicates
            if (studentExists($conn, $studentData['registration_number'])) {
                echo "Skipped duplicate: " . $studentData['registration_number'] . "\n";
                $stats['duplicates_skipped']++;
                continue;
            }
            
            // Insert student
            if (insertStudent($conn, $studentData)) {
                echo "Imported: " . $studentData['full_name'] . "\n";
                $stats['students_imported']++;
            } else {
                echo "Error importing: " . $studentData['full_name'] . "\n";
                $stats['errors']++;
            }
        }
        
        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);
        
        return true;
        
    } catch (Exception $e) {
        echo "Error processing file " . basename($filePath) . ": " . $e->getMessage() . "\n";
        $stats['errors']++;
        return false;
    }
}

/**
 * Main import function
 */
function runImport() {
    global $stats;
    
    echo "=== ISNM Student Data Import ===\n";
    echo "Starting import process...\n\n";
    
    // Check if data folder exists
    if (!is_dir(DATA_FOLDER)) {
        die("Error: Data folder '" . DATA_FOLDER . "' not found.\n");
    }
    
    // Connect to database
    $conn = connectDatabase();
    echo "\n";
    
    // Get all Excel files in the folder
    $excelFiles = glob(DATA_FOLDER . '/*.xlsx');
    
    if (empty($excelFiles)) {
        echo "No Excel files found in '" . DATA_FOLDER . "' folder.\n";
        $conn->close();
        return;
    }
    
    echo "Found " . count($excelFiles) . " Excel file(s) to process.\n\n";
    
    // Process each file
    foreach ($excelFiles as $filePath) {
        if (processExcelFile($conn, $filePath, $stats)) {
            $stats['files_processed']++;
        }
        echo "\n";
    }
    
    // Close database connection
    $conn->close();
    
    // Display final statistics
    echo "=== Import Summary ===\n";
    echo "Files processed: " . $stats['files_processed'] . "\n";
    echo "Students imported: " . $stats['students_imported'] . "\n";
    echo "Duplicates skipped: " . $stats['duplicates_skipped'] . "\n";
    echo "Errors encountered: " . $stats['errors'] . "\n";
    
    if ($stats['students_imported'] > 0) {
        echo "\nImport completed successfully!\n";
    } else {
        echo "\nNo new students were imported.\n";
    }
}

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set memory limit for large files
ini_set('memory_limit', '512M');

// Set time limit for processing
set_time_limit(300); // 5 minutes

// Run the import
runImport();
?>
