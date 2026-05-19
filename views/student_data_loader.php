<?php
/**
 * Unified Student Data Loader
 * Loads from students_db and Excel files in students_data/
 */
require_once __DIR__ . '/../config/database.php';

$spreadsheetAvailable = file_exists(__DIR__ . '/../vendor/autoload.php');
if ($spreadsheetAvailable) {
    require_once __DIR__ . '/../vendor/autoload.php';
}

class StudentDataLoader {
    private $studentsDataDir;
    private $cachedData = [];

    public function __construct($conn = null) {
        $this->studentsDataDir = __DIR__ . '/../students_data/';
        if (!is_dir($this->studentsDataDir)) {
            @mkdir($this->studentsDataDir, 0755, true);
        }
    }

    public function loadAllStudents() {
        if (!empty($this->cachedData)) {
            return $this->cachedData;
        }

        $fromDb = $this->loadFromDatabase();
        $fromFiles = $this->loadFromExcelFiles();
        $merged = $this->mergeStudents($fromDb, $fromFiles);
        $this->cachedData = $merged;
        return $merged;
    }

    private function loadFromDatabase() {
        $students = [];
        try {
            $conn = getStudentsConnection();
            $tables = ['students', 'users'];
            foreach ($tables as $table) {
                $check = $conn->query("SHOW TABLES LIKE '{$table}'");
                if (!$check || $check->num_rows === 0) {
                    continue;
                }
                $sql = $table === 'users'
                    ? "SELECT * FROM users WHERE role = 'student' OR role = 'Student'"
                    : "SELECT * FROM students";
                $result = $conn->query($sql);
                if (!$result) {
                    continue;
                }
                while ($row = $result->fetch_assoc()) {
                    $mapped = $this->mapDbRowToStudent($row, $table);
                    if ($mapped) {
                        $students[] = $mapped;
                    }
                }
            }
        } catch (Exception $e) {
            error_log('StudentDataLoader DB: ' . $e->getMessage());
        }
        return $students;
    }

    private function mapDbRowToStudent(array $row, $source) {
        $fullName = trim($row['full_name'] ?? '');
        $first = trim($row['first_name'] ?? '');
        $surname = trim($row['surname'] ?? $row['last_name'] ?? '');
        if ($fullName === '' && ($first !== '' || $surname !== '')) {
            $fullName = trim($first . ' ' . $surname);
        }
        if ($fullName === '' && $surname === '') {
            return null;
        }
        return [
            'source_file' => $source . '_db',
            'full_name' => $fullName,
            'surname' => $surname ?: $fullName,
            'first_name' => $first,
            'other_name' => trim($row['other_name'] ?? ''),
            'gender' => $row['gender'] ?? '',
            'index_number' => $row['index_number'] ?? $row['student_id'] ?? '',
            'date_of_birth' => $row['date_of_birth'] ?? $row['dob'] ?? '',
            'district' => $row['district'] ?? '',
            'nationality' => $row['nationality'] ?? 'Uganda',
            'phone' => $row['phone'] ?? '',
            'email' => $row['email'] ?? '',
            'program' => $row['program'] ?? $row['course'] ?? '',
            'level' => $row['level'] ?? '',
            'set' => $row['set'] ?? $row['class_set'] ?? '',
            'intake_year' => $row['intake_year'] ?? $row['year'] ?? '',
            'intake_period' => $row['intake_period'] ?? '',
        ];
    }

    private function loadFromExcelFiles() {
        global $spreadsheetAvailable;
        if (!$spreadsheetAvailable) {
            return [];
        }
        $all = [];
        foreach ($this->getExcelFiles() as $file) {
            $all = array_merge($all, $this->loadExcelFile($file));
        }
        return $all;
    }

    private function loadExcelFile($filePath) {
        try {
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filePath);
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();
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

    private function mapRowToStudent($row, $sourceFile) {
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
        if (!empty($student['full_name']) || !empty($student['surname'])) {
            return $student;
        }
        return null;
    }

    private function getValue($row, $index) {
        return isset($row[$index]) ? trim((string) $row[$index]) : '';
    }

    private function mergeStudents(array $db, array $files) {
        $byKey = [];
        foreach (array_merge($db, $files) as $student) {
            $key = strtolower(trim($student['index_number'] ?? ''));
            if ($key === '') {
                $key = strtolower(trim(($student['surname'] ?? '') . '|' . ($student['first_name'] ?? '') . '|' . ($student['phone'] ?? '')));
            }
            if ($key === '' || $key === '||') {
                $byKey[] = $student;
                continue;
            }
            if (!isset($byKey[$key])) {
                $byKey[$key] = $student;
            }
        }
        return array_values($byKey);
    }

    private function extractProgramFromFilename($filename) {
        if (stripos($filename, 'midwives') !== false || stripos($filename, 'midwifery') !== false) {
            return 'Certificate Midwifery';
        }
        if (stripos($filename, 'nurses') !== false || stripos($filename, 'nursing') !== false) {
            return 'Certificate Nursing';
        }
        if (stripos($filename, 'diploma') !== false) {
            return 'Diploma Nursing';
        }
        return 'General Nursing';
    }

    private function extractLevelFromFilename($filename) {
        return stripos($filename, 'diploma') !== false ? 'Diploma' : 'Certificate';
    }

    private function extractSetFromFilename($filename) {
        if (preg_match('/set[_\s]?(\d+)/i', $filename, $matches)) {
            return 'Set ' . $matches[1];
        }
        return '';
    }

    private function extractYearFromFilename($filename) {
        if (preg_match('/(20\d{2})/', $filename, $matches)) {
            return $matches[1];
        }
        return date('Y');
    }

    private function extractPeriodFromFilename($filename) {
        if (stripos($filename, 'july') !== false || stripos($filename, 'jul') !== false) {
            return 'July';
        }
        if (stripos($filename, 'january') !== false || stripos($filename, 'jan') !== false) {
            return 'January';
        }
        return 'July';
    }

    private function getExcelFiles() {
        $files = [];
        if (!is_dir($this->studentsDataDir)) {
            return $files;
        }
        $iterator = new DirectoryIterator($this->studentsDataDir);
        foreach ($iterator as $fileInfo) {
            if ($fileInfo->isFile() && strtolower($fileInfo->getExtension()) === 'xlsx') {
                $files[] = $fileInfo->getPathname();
            }
        }
        return $files;
    }

    public function searchStudents($searchTerm, $filters = []) {
        $students = $this->loadAllStudents();
        $results = [];
        foreach ($students as $student) {
            $match = true;
            if (!empty($searchTerm)) {
                $nameMatch =
                    stripos($student['full_name'], $searchTerm) !== false ||
                    stripos($student['surname'], $searchTerm) !== false ||
                    stripos($student['first_name'], $searchTerm) !== false ||
                    stripos($student['other_name'], $searchTerm) !== false ||
                    stripos($student['index_number'], $searchTerm) !== false ||
                    stripos($student['phone'], $searchTerm) !== false;
                if (!$nameMatch) {
                    $match = false;
                }
            }
            if (!empty($filters['program']) && stripos($student['program'], $filters['program']) === false) {
                $match = false;
            }
            if (!empty($filters['level']) && stripos($student['level'], $filters['level']) === false) {
                $match = false;
            }
            if (!empty($filters['set']) && stripos($student['set'], $filters['set']) === false) {
                $match = false;
            }
            if (!empty($filters['gender']) && strcasecmp($student['gender'], $filters['gender']) !== 0) {
                $match = false;
            }
            if (!empty($filters['year']) && (string) $student['intake_year'] !== (string) $filters['year']) {
                $match = false;
            }
            if ($match) {
                $results[] = $student;
            }
        }
        return $results;
    }

    public function getFilterOptions() {
        $students = $this->loadAllStudents();
        return [
            'programs' => array_values(array_filter(array_unique(array_column($students, 'program')))),
            'levels' => array_values(array_filter(array_unique(array_column($students, 'level')))),
            'sets' => array_values(array_filter(array_unique(array_column($students, 'set')))),
            'genders' => array_values(array_filter(array_unique(array_column($students, 'gender')))),
            'years' => array_values(array_filter(array_unique(array_column($students, 'intake_year')))),
        ];
    }

    public function getStatistics() {
        $students = $this->loadAllStudents();
        return [
            'total_students' => count($students),
            'total_programs' => count(array_unique(array_column($students, 'program'))),
            'total_sets' => count(array_unique(array_column($students, 'set'))),
            'total_years' => count(array_unique(array_column($students, 'intake_year'))),
            'male_count' => count(array_filter($students, fn($s) => strtolower($s['gender']) === 'male')),
            'female_count' => count(array_filter($students, fn($s) => strtolower($s['gender']) === 'female')),
            'data_files' => count($this->getExcelFiles()),
        ];
    }
}
