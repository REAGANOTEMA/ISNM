<?php
/**
 * Unified Student Data Loader
 * Loads from students_db and every Excel file in students_data/.
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/SimpleXlsxReader.php';

class StudentDataLoader {
    private $studentsDataDir;
    private $cachedData = [];
    private $cachedFiles = [];

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
            if (!$conn) return $students;

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
            'source_path' => '',
            'full_name' => $fullName,
            'surname' => $surname ?: $fullName,
            'first_name' => $first,
            'other_name' => trim($row['other_name'] ?? ''),
            'gender' => $row['gender'] ?? '',
            'index_number' => $row['index_number'] ?? $row['student_id'] ?? $row['registration_number'] ?? '',
            'registration_number' => $row['registration_number'] ?? $row['student_number'] ?? '',
            'student_number' => $row['student_number'] ?? '',
            'national_id' => $row['national_id'] ?? $row['national_student_id_number'] ?? $row['NSIN'] ?? '',
            'date_of_birth' => $row['date_of_birth'] ?? $row['dob'] ?? '',
            'district' => $row['district'] ?? '',
            'nationality' => $row['nationality'] ?? 'Uganda',
            'phone' => $row['phone'] ?? $row['mobile_number'] ?? '',
            'email' => $row['email'] ?? '',
            'program' => $row['program'] ?? $row['course'] ?? '',
            'level' => $row['level'] ?? '',
            'set' => $row['set'] ?? $row['class_set'] ?? '',
            'intake_year' => $row['intake_year'] ?? $row['year'] ?? '',
            'intake_period' => $row['intake_period'] ?? '',
        ];
    }

    private function loadFromExcelFiles() {
        $all = [];
        $this->cachedFiles = [];
        foreach ($this->getExcelFiles() as $file) {
            $rows = $this->loadExcelFile($file);
            $this->cachedFiles[] = [
                'path' => $file,
                'name' => basename($file),
                'students' => count($rows),
            ];
            $all = array_merge($all, $rows);
        }
        return $all;
    }

    private function loadExcelFile($filePath) {
        try {
            $rows = SimpleXlsxReader::read($filePath);
            if (empty($rows)) {
                return [];
            }

            $headerRowIndex = $this->findHeaderRowIndex($rows);
            if ($headerRowIndex === null) {
                return [];
            }

            $headers = $this->normalizeHeaders($rows[$headerRowIndex]);
            $students = [];
            for ($i = $headerRowIndex + 1; $i < count($rows); $i++) {
                $student = $this->mapRowToStudent($rows[$i], basename($filePath), $headers);
                if ($student) {
                    $students[] = $student;
                }
            }
            return $students;
        } catch (Exception $e) {
            error_log('loadExcelFile ' . basename($filePath) . ': ' . $e->getMessage());
            return [];
        }
    }

    private function findHeaderRowIndex(array $rows): ?int {
        foreach ($rows as $index => $row) {
            $headers = array_map(fn($value) => $this->normalizeHeader($value), $row);
            $score = 0;
            foreach ($headers as $header) {
                if ($header === '') continue;
                if (in_array($header, ['surname', 'first_name', 'full_name', 'name', 'student_id', 'student number', 'index_number', 'nsin', 'registration_number', 'phone', 'phone number', 'email', 'program', 'course'], true)) {
                    $score++;
                }
            }
            if ($score >= 2) {
                return $index;
            }
        }
        return null;
    }

    private function normalizeHeaders(array $row): array {
        $headers = [];
        foreach ($row as $index => $header) {
            $headers[$index] = $this->normalizeHeader($header);
        }
        return $headers;
    }

    private function normalizeHeader($value): string {
        $value = strtolower(trim((string) $value));
        $value = preg_replace('/[^a-z0-9]+/', '_', $value);
        $value = trim($value, '_');
        $replacements = [
            'student_no' => 'student_number',
            'student_id' => 'student_number',
            'student_number' => 'student_number',
            'index_no' => 'index_number',
            'reg_no' => 'registration_number',
            'registration_no' => 'registration_number',
            'national_id' => 'national_id',
            'national_student_id_number' => 'national_id',
            'nsin' => 'national_id',
            'phone_no' => 'phone',
            'phone_number' => 'phone',
            'mobile_number' => 'phone',
            'date_of_birth' => 'date_of_birth',
            'dob' => 'date_of_birth',
            'course_codes' => 'course_codes',
            'no_of_papers' => 'no_of_papers',
        ];
        return $replacements[$value] ?? $value;
    }

    private function mapRowToStudent($row, $sourceFile, array $headers = []) {
        $fullName = $this->firstNonEmpty([
            $this->cell($row, $headers, ['full_name', 'name', 'student_name']),
            trim(($this->cell($row, $headers, ['surname']) ?: '') . ' ' . ($this->cell($row, $headers, ['first_name']) ?: '') . ' ' . ($this->cell($row, $headers, ['other_name']) ?: '')),
        ]);
        $student = [
            'source_file' => $sourceFile,
            'source_path' => $this->studentsDataDir . $sourceFile,
            'full_name' => $fullName,
            'surname' => $this->firstNonEmpty([$this->cell($row, $headers, ['surname', 'last_name']), $fullName]),
            'first_name' => $this->cell($row, $headers, ['first_name', 'firstname', 'forename']),
            'other_name' => $this->cell($row, $headers, ['other_name', 'middlename', 'middle_name']),
            'gender' => $this->cell($row, $headers, ['gender', 'sex']),
            'index_number' => $this->firstNonEmpty([$this->cell($row, $headers, ['index_number', 'index_no']), $this->cell($row, $headers, ['national_id'])]),
            'registration_number' => $this->cell($row, $headers, ['registration_number', 'reg_no', 'admission_number']),
            'student_number' => $this->firstNonEmpty([$this->cell($row, $headers, ['student_number', 'student_no', 'student_id']), $this->cell($row, $headers, ['index_number'])]),
            'national_id' => $this->cell($row, $headers, ['national_id', 'national_student_id_number', 'nsin']),
            'date_of_birth' => $this->cell($row, $headers, ['date_of_birth', 'dob']),
            'district' => $this->cell($row, $headers, ['district', 'location']),
            'nationality' => $this->firstNonEmpty([$this->cell($row, $headers, ['nationality', 'country']), 'Uganda']),
            'phone' => $this->cell($row, $headers, ['phone', 'phone_number', 'phone_no', 'mobile_number', 'mobile']),
            'email' => $this->cell($row, $headers, ['email', 'e_mail']),
            'program' => $this->firstNonEmpty([$this->cell($row, $headers, ['program', 'course', 'programme']), $this->extractProgramFromFilename($sourceFile)]),
            'level' => $this->firstNonEmpty([$this->cell($row, $headers, ['level', 'award']), $this->extractLevelFromFilename($sourceFile)]),
            'set' => $this->firstNonEmpty([$this->cell($row, $headers, ['set', 'class_set', 'intake_set']), $this->extractSetFromFilename($sourceFile)]),
            'intake_year' => $this->firstNonEmpty([$this->cell($row, $headers, ['intake_year', 'year']), $this->extractYearFromFilename($sourceFile)]),
            'intake_period' => $this->firstNonEmpty([$this->cell($row, $headers, ['intake_period', 'trial', 'semester']), $this->extractPeriodFromFilename($sourceFile)]),
            'course_codes' => $this->cell($row, $headers, ['course_codes', 'courses', 'registered_courses']),
            'no_of_papers' => $this->cell($row, $headers, ['no_of_papers', 'papers']),
            'raw_row' => $row,
        ];

        if (!empty($student['full_name']) || !empty($student['surname']) || !empty($student['index_number']) || !empty($student['national_id']) || !empty($student['phone'])) {
            return $student;
        }
        return null;
    }

    private function cell(array $row, array $headers, array $keys): string {
        foreach ($keys as $key) {
            $key = $this->normalizeHeader($key);
            $index = array_search($key, $headers, true);
            if ($index !== false && isset($row[$index])) {
                return trim((string) $row[$index]);
            }
        }
        return '';
    }

    private function firstNonEmpty(array $values): string {
        foreach ($values as $value) {
            $value = trim((string) $value);
            if ($value !== '') {
                return $value;
            }
        }
        return '';
    }

    private function mergeStudents(array $db, array $files) {
        $byKey = [];
        foreach (array_merge($db, $files) as $student) {
            $key = $this->studentKey($student);
            if ($key === '') {
                $byKey[] = $student;
                continue;
            }
            if (!isset($byKey[$key])) {
                $byKey[$key] = $student;
            }
        }
        return array_values($byKey);
    }

    private function studentKey(array $student): string {
        $index = strtolower(trim($student['index_number'] ?? ''));
        if ($index !== '') return 'index:' . $index;
        $nationalId = strtolower(trim($student['national_id'] ?? ''));
        if ($nationalId !== '') return 'national:' . $nationalId;
        $studentNumber = strtolower(trim($student['student_number'] ?? ''));
        if ($studentNumber !== '') return 'student:' . $studentNumber;
        $phone = preg_replace('/\D/', '', (string) ($student['phone'] ?? ''));
        if (strlen($phone) >= 9) return 'phone:' . substr($phone, -9);
        return strtolower(trim(($student['surname'] ?? '') . '|' . ($student['first_name'] ?? '') . '|' . ($student['date_of_birth'] ?? '')));
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
        return '';
    }

    private function getExcelFiles() {
        $files = [];
        if (!is_dir($this->studentsDataDir)) {
            return $files;
        }
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->studentsDataDir, FilesystemIterator::SKIP_DOTS)
        );
        foreach ($iterator as $fileInfo) {
            if ($fileInfo->isFile() && in_array(strtolower($fileInfo->getExtension()), ['xlsx', 'xlsm'], true)) {
                $files[] = $fileInfo->getPathname();
            }
        }
        natcasesort($files);
        return array_values($files);
    }

    public function getExcelFileSummary() {
        if (empty($this->cachedFiles)) {
            $this->loadAllStudents();
        }
        return $this->cachedFiles;
    }

    public function searchStudents($searchTerm, $filters = []) {
        $students = $this->loadAllStudents();
        $results = [];
        $term = trim((string) $searchTerm);
        foreach ($students as $student) {
            $match = true;
            if ($term !== '') {
                $haystack = implode(' | ', array_filter([
                    $student['full_name'] ?? '',
                    $student['surname'] ?? '',
                    $student['first_name'] ?? '',
                    $student['other_name'] ?? '',
                    $student['index_number'] ?? '',
                    $student['registration_number'] ?? '',
                    $student['student_number'] ?? '',
                    $student['national_id'] ?? '',
                    $student['phone'] ?? '',
                    $student['email'] ?? '',
                    $student['program'] ?? '',
                    $student['level'] ?? '',
                    $student['set'] ?? '',
                    $student['intake_year'] ?? '',
                    $student['intake_period'] ?? '',
                    $student['district'] ?? '',
                    $student['source_file'] ?? '',
                    $student['course_codes'] ?? '',
                ]));
                if (stripos($haystack, $term) === false) {
                    $match = false;
                }
            }
            if (!empty($filters['program']) && stripos((string) ($student['program'] ?? ''), (string) $filters['program']) === false) {
                $match = false;
            }
            if (!empty($filters['level']) && stripos((string) ($student['level'] ?? ''), (string) $filters['level']) === false) {
                $match = false;
            }
            if (!empty($filters['set']) && stripos((string) ($student['set'] ?? ''), (string) $filters['set']) === false) {
                $match = false;
            }
            if (!empty($filters['gender']) && strcasecmp((string) ($student['gender'] ?? ''), (string) $filters['gender']) !== 0) {
                $match = false;
            }
            if (!empty($filters['year']) && (string) ($student['intake_year'] ?? '') !== (string) $filters['year']) {
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
            'total_programs' => count(array_filter(array_unique(array_column($students, 'program')))),
            'total_sets' => count(array_filter(array_unique(array_column($students, 'set')))),
            'total_years' => count(array_filter(array_unique(array_column($students, 'intake_year')))),
            'male_count' => count(array_filter($students, fn($s) => strtolower($s['gender'] ?? '') === 'male')),
            'female_count' => count(array_filter($students, fn($s) => strtolower($s['gender'] ?? '') === 'female')),
            'data_files' => count($this->getExcelFiles()),
            'excel_file_summary' => $this->getExcelFileSummary(),
        ];
    }
}
