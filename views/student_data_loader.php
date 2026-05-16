<?php
/**
 * Unified Student Data Loader
 * Loads student data from Excel files in students_data folder
 * Provides search and filtering capabilities for staff dashboards
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/database.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

class StudentDataLoader {
    private $studentsDataDir;
    private $conn;
    private $cachedData = [];
    
    public function __construct($conn = null) {
        $this->studentsDataDir = __DIR__ . '/../students_data/';
        $this->conn = $conn;
    }
    
    /**
     * Load all student data from Excel files
     */
    public function loadAllStudents() {
        if (!empty($this->cachedData)) {
            return $this->cachedData;
        }
        
        $files = $this->getExcelFiles();
        $allStudents = [];
        
        foreach ($files as $file) {
            $students = $this->loadExcelFile($file);
            $allStudents = array_merge($allStudents, $students);
        }
        
        $this->cachedData = $allStudents;
        return $allStudents;
    }
    
    /**
     * Load students from a specific Excel file
     */
    private function loadExcelFile($filePath) {
        try {
            $spreadsheet = IOFactory::load($filePath);
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();
            
            // Skip header row
            if (count($rows) > 0) {
                array_shift($rows);
            }
            
            $students = [];
            foreach ($rows as $row) {
                $student = $this->mapRowToStudent($row, basename($filePath));
                if ($student) {
                    $students[] = $student;
                }
            }
            
            return $students;
        } catch (Exception $e) {
            error_log("Error loading Excel file $filePath: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Map Excel row to student object
     */
    private function mapRowToStudent($row, $sourceFile) {
        // Handle different Excel file formats
        // This is a flexible mapper that can handle various column arrangements
        
        $student = [
            'source_file' => $sourceFile,
            'full_name' => $this->getValue($row, 0) ?? '',
            'surname' => $this->getValue($row, 0) ?? '',
            'first_name' => $this->getValue($row, 1) ?? '',
            'other_name' => $this->getValue($row, 2) ?? '',
            'gender' => $this->getValue($row, 3) ?? '',
            'index_number' => $this->getValue($row, 4) ?? '',
            'date_of_birth' => $this->getValue($row, 5) ?? '',
            'district' => $this->getValue($row, 6) ?? '',
            'nationality' => $this->getValue($row, 7) ?? 'Uganda',
            'phone' => $this->getValue($row, 8) ?? '',
            'email' => $this->getValue($row, 9) ?? '',
            'program' => $this->extractProgramFromFilename($sourceFile),
            'level' => $this->extractLevelFromFilename($sourceFile),
            'set' => $this->extractSetFromFilename($sourceFile),
            'intake_year' => $this->extractYearFromFilename($sourceFile),
            'intake_period' => $this->extractPeriodFromFilename($sourceFile),
        ];
        
        // Only return if we have essential data
        if (!empty($student['full_name']) || !empty($student['surname'])) {
            return $student;
        }
        
        return null;
    }
    
    /**
     * Get value from row by index with fallback
     */
    private function getValue($row, $index) {
        return isset($row[$index]) ? trim($row[$index]) : '';
    }
    
    /**
     * Extract program information from filename
     */
    private function extractProgramFromFilename($filename) {
        if (stripos($filename, 'midwives') !== false || stripos($filename, 'midwifery') !== false) {
            return 'Certificate Midwifery';
        } elseif (stripos($filename, 'nurses') !== false || stripos($filename, 'nursing') !== false) {
            return 'Certificate Nursing';
        } elseif (stripos($filename, 'diploma') !== false) {
            return 'Diploma Nursing';
        }
        return 'General Nursing';
    }
    
    /**
     * Extract level from filename
     */
    private function extractLevelFromFilename($filename) {
        if (stripos($filename, 'diploma') !== false) {
            return 'Diploma';
        }
        return 'Certificate';
    }
    
    /**
     * Extract set from filename
     */
    private function extractSetFromFilename($filename) {
        if (preg_match('/set[_\s]?(\d+)/i', $filename, $matches)) {
            return 'Set ' . $matches[1];
        }
        return '';
    }
    
    /**
     * Extract year from filename
     */
    private function extractYearFromFilename($filename) {
        if (preg_match('/(20\d{2})/', $filename, $matches)) {
            return $matches[1];
        }
        return date('Y');
    }
    
    /**
     * Extract period from filename
     */
    private function extractPeriodFromFilename($filename) {
        if (stripos($filename, 'july') !== false || stripos($filename, 'jul') !== false) {
            return 'July';
        } elseif (stripos($filename, 'january') !== false || stripos($filename, 'jan') !== false) {
            return 'January';
        }
        return 'July';
    }
    
    /**
     * Get all Excel files from students_data directory
     */
    private function getExcelFiles() {
        $files = [];
        if (is_dir($this->studentsDataDir)) {
            $iterator = new DirectoryIterator($this->studentsDataDir);
            foreach ($iterator as $fileInfo) {
                if ($fileInfo->isFile() && $fileInfo->getExtension() === 'xlsx') {
                    $files[] = $fileInfo->getPathname();
                }
            }
        }
        return $files;
    }
    
    /**
     * Search students by term
     */
    public function searchStudents($searchTerm, $filters = []) {
        $students = $this->loadAllStudents();
        $results = [];
        
        $searchLower = strtolower($searchTerm);
        
        foreach ($students as $student) {
            $match = true;
            
            // Search in name fields
            if (!empty($searchTerm)) {
                $nameMatch = 
                    stripos($student['full_name'], $searchTerm) !== false ||
                    stripos($student['surname'], $searchTerm) !== false ||
                    stripos($student['first_name'], $searchTerm) !== false ||
                    stripos($student['other_name'], $searchTerm) !== false ||
                    stripos($student['index_number'], $searchTerm) !== false;
                
                if (!$nameMatch) {
                    $match = false;
                }
            }
            
            // Apply filters
            if (!empty($filters['program']) && strtolower($student['program']) !== strtolower($filters['program'])) {
                $match = false;
            }
            
            if (!empty($filters['level']) && strtolower($student['level']) !== strtolower($filters['level'])) {
                $match = false;
            }
            
            if (!empty($filters['set']) && strtolower($student['set']) !== strtolower($filters['set'])) {
                $match = false;
            }
            
            if (!empty($filters['gender']) && strtolower($student['gender']) !== strtolower($filters['gender'])) {
                $match = false;
            }
            
            if (!empty($filters['year']) && $student['intake_year'] != $filters['year']) {
                $match = false;
            }
            
            if ($match) {
                $results[] = $student;
            }
        }
        
        return $results;
    }
    
    /**
     * Get unique values for filters
     */
    public function getFilterOptions() {
        $students = $this->loadAllStudents();
        
        $programs = array_unique(array_column($students, 'program'));
        $levels = array_unique(array_column($students, 'level'));
        $sets = array_unique(array_column($students, 'set'));
        $genders = array_unique(array_column($students, 'gender'));
        $years = array_unique(array_column($students, 'intake_year'));
        
        return [
            'programs' => array_filter($programs),
            'levels' => array_filter($levels),
            'sets' => array_filter($sets),
            'genders' => array_filter($genders),
            'years' => array_filter($years)
        ];
    }
    
    /**
     * Get statistics
     */
    public function getStatistics() {
        $students = $this->loadAllStudents();
        
        return [
            'total_students' => count($students),
            'total_programs' => count(array_unique(array_column($students, 'program'))),
            'total_sets' => count(array_unique(array_column($students, 'set'))),
            'total_years' => count(array_unique(array_column($students, 'intake_year'))),
            'male_count' => count(array_filter($students, fn($s) => strtolower($s['gender']) === 'male')),
            'female_count' => count(array_filter($students, fn($s) => strtolower($s['gender']) === 'female'))
        ];
    }
}
