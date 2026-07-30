<?php
/**
 * Complete Student Model for ISNM Student Management System
 * Supports all CRUD operations, status changes, and profile management.
 */

require_once __DIR__ . '/../config/database.php';

class Student {
    private $conn;

    public function __construct() {
        $this->conn = getConnection();
        if (!$this->conn) throw new \RuntimeException('Database connection failed');
    }

    public function __destruct() {
        // No-op: connection is cached/persistent
    }

    /**
     * Generate next student_number: ISNM/XXXX/YY
     */
    private function generateStudentNumber(): string {
        $year = date('y');
        $stmt = $this->conn->prepare("SELECT MAX(CAST(SUBSTRING(student_number, 6, 4) AS UNSIGNED)) AS max_num FROM students WHERE student_number LIKE ?");
        $like = "ISNM/%/$year";
        $stmt->bind_param('s', $like);
        if (!$stmt->execute()) { error_log('Student generateStudentNumber: ' . $stmt->error); };
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        $next = ($row && $row['max_num']) ? $row['max_num'] + 1 : 1;
        return sprintf('ISNM/%04d/%s', $next, $year);
    }

    /**
     * Generate next admission_number: ADM/XXXX/YY
     */
    private function generateAdmissionNumber(): string {
        $year = date('y');
        $stmt = $this->conn->prepare("SELECT MAX(CAST(SUBSTRING(admission_number, 6, 4) AS UNSIGNED)) AS max_num FROM students WHERE admission_number LIKE ?");
        $like = "ADM/%/$year";
        $stmt->bind_param('s', $like);
        if (!$stmt->execute()) { error_log('Student generateAdmissionNumber: ' . $stmt->error); };
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        $next = ($row && $row['max_num']) ? $row['max_num'] + 1 : 1;
        return sprintf('ADM/%04d/%s', $next, $year);
    }

    /**
     * Generate username from first_name + surname
     */
    private function generateUsername(string $firstName, string $surname): string {
        $base = strtolower(trim($firstName) . '.' . trim($surname));
        $base = preg_replace('/[^a-z0-9.]/', '', $base);
        $username = $base;
        $counter = 1;
        while ($this->usernameExists($username)) {
            $username = $base . $counter;
            $counter++;
        }
        return $username;
    }

    private function usernameExists(string $username): bool {
        $stmt = $this->conn->prepare("SELECT 1 FROM student_accounts WHERE username = ? LIMIT 1");
        $stmt->bind_param('s', $username);
        if (!$stmt->execute()) { return false; };
        $exists = $stmt->get_result()->num_rows > 0;
        $stmt->close();
        return $exists;
    }

    /**
     * Create a new student record with FULL auto-provisioning.
     * This is the single entry point for student creation.
     *
     * @param array $data Student data
     * @return array{success: bool, student_id?: int, student_number?: string, username?: string, password?: string, error?: string}
     */
    public function create(array $data): array {
        try {
            if (!$this->conn) return ['success' => false, 'error' => 'Database connection failed'];
            $this->conn->begin_transaction();

            // Parse first_name and surname from full_name if not provided directly
            if (empty($data['first_name']) && empty($data['surname']) && !empty($data['full_name'])) {
                $parts = preg_split('/\s+/', trim($data['full_name']), 2);
                $data['first_name'] = $parts[0] ?? '';
                $data['surname'] = $parts[1] ?? $parts[0] ?? '';
            }

            // Generate IDs
            $studentNumber = $this->generateStudentNumber();
            $admissionNumber = $this->generateAdmissionNumber();
            $username = $this->generateUsername($data['first_name'] ?? '', $data['surname'] ?? '');
            $tempPassword = bin2hex(random_bytes(4));
            $passwordHash = password_hash($tempPassword, PASSWORD_DEFAULT);

            // Build full_name
            $fullName = trim(($data['first_name'] ?? '') . ' ' . ($data['other_name'] ?? '') . ' ' . ($data['surname'] ?? ''));
            if (empty($fullName)) $fullName = $data['full_name'] ?? '';

            $intakeYear = date('Y');
            $intakePeriod = (int)date('n') <= 6 ? 'January' : 'July';

            // 1. Insert into students table
            $query = "INSERT INTO students (
                student_number, admission_number, registration_number, national_student_id_number,
                index_number, first_name, surname, other_name, full_name,
                email, password, phone, mobile_number,
                program, course, current_year, year_of_study, year, level, set_name, current_semester,
                gender, date_of_birth, nationality, address, district, county, sub_county, village,
                guardian_name, guardian_phone, guardian_email, guardian_relationship,
                emergency_contact_name, emergency_contact_phone, emergency_contact_email,
                sponsor, marital_status, religion, student_category,
                profile_picture, passport_photo, status,
                intake_year, intake_period, academic_year, admission_date,
                stream, class_name, previous_school, previous_qualification,
                hostel_required, transport_required, notes,
                is_first_login, password_changed, registration_status
            ) VALUES (
                ?, ?, ?, ?,
                ?, ?, ?, ?, ?,
                ?, ?, ?, ?,
                ?, ?, ?, ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?, ?, ?, ?,
                ?, ?, ?, ?,
                ?, ?, ?,
                ?, ?, ?, ?,
                ?, ?, 'Active',
                ?, ?, ?, CURDATE(),
                ?, ?, ?, ?,
                ?, ?, ?,
                1, 0, 'Pending'
            )";

            $params = [
                $studentNumber,
                $admissionNumber,
                $data['registration_number'] ?? null,
                $data['national_student_id_number'] ?? null,
                $data['index_number'] ?? null,
                $data['first_name'] ?? '',
                $data['surname'] ?? '',
                $data['other_name'] ?? null,
                $fullName,
                $data['email'] ?? ($username . '@isnm.ac.ug'),
                $passwordHash,
                $data['phone'] ?? $data['mobile_number'] ?? null,
                $data['mobile_number'] ?? $data['phone'] ?? null,
                $data['program'] ?? $data['course'] ?? null,
                $data['course'] ?? $data['program'] ?? null,
                (int)($data['current_year'] ?? $data['year'] ?? 1),
                (int)($data['year_of_study'] ?? $data['year'] ?? 1),
                (int)($data['year'] ?? 1),
                $data['level'] ?? null,
                $data['set_name'] ?? null,
                $data['current_semester'] ?? null,
                $data['gender'] ?? 'Other',
                !empty($data['date_of_birth']) ? $data['date_of_birth'] : null,
                $data['nationality'] ?? 'Ugandan',
                $data['address'] ?? null,
                $data['district'] ?? null,
                $data['county'] ?? null,
                $data['sub_county'] ?? null,
                $data['village'] ?? null,
                $data['guardian_name'] ?? null,
                $data['guardian_phone'] ?? null,
                $data['guardian_email'] ?? null,
                $data['guardian_relationship'] ?? null,
                $data['emergency_contact_name'] ?? null,
                $data['emergency_contact_phone'] ?? null,
                $data['emergency_contact_email'] ?? null,
                $data['sponsor'] ?? null,
                $data['marital_status'] ?? null,
                $data['religion'] ?? null,
                $data['student_category'] ?? null,
                $data['profile_picture'] ?? null,
                $data['passport_photo'] ?? null,
                $intakeYear,
                $intakePeriod,
                $data['academic_year'] ?? null,
                $data['stream'] ?? null,
                $data['class_name'] ?? null,
                $data['previous_school'] ?? null,
                $data['previous_qualification'] ?? null,
                (int)($data['hostel_required'] ?? 0),
                (int)($data['transport_required'] ?? 0),
                $data['notes'] ?? null,
            ];

            $types = 'sssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssiiiiiiisssssssiiiiis';

            $stmt = $this->conn->prepare($query);
            if (!$stmt) {
                throw new Exception('Prepare failed: ' . $this->conn->error);
            }
            $stmt->bind_param($types, ...$params);
            if (!$stmt->execute()) {
                throw new Exception('Execute failed: ' . $stmt->error);
            }
            $studentId = $stmt->insert_id;
            $stmt->close();

            // 2. Create student account (login credentials)
            $this->createStudentAccount($studentId, $username, $passwordHash, $data['email'] ?? ($username . '@isnm.ac.ug'));

            // 3. Create student profile
            $this->createStudentProfile($studentId, $data);

            // 4. Create student financial profile
            $this->createFinancialProfile($studentId, $data);

            // 5. Create student academic profile
            $this->createAcademicProfile($studentId, $data);

            // 6. Create student medical profile
            $this->createMedicalProfile($studentId, $data);

            // 7. Create attendance profile
            $this->createAttendanceProfile($studentId, $data);

            // 8. Create library profile
            $this->createLibraryProfile($studentId);

            // 9. Create requirements profile (initialize all requirements as Not Submitted)
            $this->initRequirementsProfile($studentId);

            // 10. Sync to other databases
            if (function_exists('syncStudentRecord')) {
                syncStudentRecord(array_merge($data, [
                    'student_id' => $studentId,
                    'student_number' => $studentNumber,
                    'first_name' => $data['first_name'] ?? '',
                    'surname' => $data['surname'] ?? '',
                    'full_name' => $fullName,
                    'email' => $data['email'] ?? ($username . '@isnm.ac.ug'),
                    'phone' => $data['phone'] ?? $data['mobile_number'] ?? '',
                    'gender' => $data['gender'] ?? 'Other',
                    'program' => $data['program'] ?? $data['course'] ?? '',
                    'status' => 'Active',
                ]), 'insert');
            }

            // 11. Log the action
            $this->logAction('create', $studentId, "Student created: $studentNumber - $fullName");

            $this->conn->commit();

            return [
                'success' => true,
                'student_id' => $studentId,
                'student_number' => $studentNumber,
                'admission_number' => $admissionNumber,
                'username' => $username,
                'password' => $tempPassword,
                'index_number' => $data['index_number'] ?? null,
            ];

        } catch (Exception $e) {
            $this->conn->rollback();
            error_log('Student create: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Create student login account
     */
    private function createStudentAccount(int $studentId, string $username, string $passwordHash, string $email): void {
        $stmt = $this->conn->prepare("INSERT INTO student_accounts (student_id, username, password_hash, email, status, is_first_login) VALUES (?, ?, ?, ?, 'Active', 1)");
        $stmt->bind_param('isss', $studentId, $username, $passwordHash, $email);
        if (!$stmt->execute()) {
            error_log('createStudentAccount failed: ' . $stmt->error);
        }
        $stmt->close();
    }

    /**
     * Create student profile (general)
     */
    private function createStudentProfile(int $studentId, array $data): void {
        $profileData = json_encode([
            'nationality' => $data['nationality'] ?? 'Ugandan',
            'district' => $data['district'] ?? '',
            'county' => $data['county'] ?? '',
            'religion' => $data['religion'] ?? '',
            'marital_status' => $data['marital_status'] ?? '',
        ]);
        $stmt = $this->conn->prepare("INSERT INTO student_profiles (student_id, profile_type, profile_data, status) VALUES (?, 'General', ?, 'Active')");
        $stmt->bind_param('is', $studentId, $profileData);
        if (!$stmt->execute()) {
            error_log('createStudentProfile failed: ' . $stmt->error);
        }
        $stmt->close();
    }

    /**
     * Create student financial profile
     */
    private function createFinancialProfile(int $studentId, array $data): void {
        $academicYear = $data['academic_year'] ?? date('Y');
        $semester = $data['current_semester'] ?? 'Semester 1';
        $stmt = $this->conn->prepare("INSERT IGNORE INTO student_financial_profiles (student_id, academic_year, semester, status) VALUES (?, ?, ?, 'pending')");
        $stmt->bind_param('iss', $studentId, $academicYear, $semester);
        if (!$stmt->execute()) {
            error_log('createFinancialProfile failed: ' . $stmt->error);
        }
        $stmt->close();
    }

    /**
     * Create student academic profile
     */
    private function createAcademicProfile(int $studentId, array $data): void {
        $stmt = $this->conn->prepare("INSERT IGNORE INTO student_academic_profiles (student_id, current_program, current_year, current_semester, academic_year) VALUES (?, ?, ?, ?, ?)");
        $program = $data['program'] ?? $data['course'] ?? '';
        $year = (int)($data['current_year'] ?? $data['year'] ?? 1);
        $semester = $data['current_semester'] ?? '';
        $academicYear = $data['academic_year'] ?? date('Y');
        $stmt->bind_param('isiss', $studentId, $program, $year, $semester, $academicYear);
        if (!$stmt->execute()) {
            error_log('createAcademicProfile failed: ' . $stmt->error);
        }
        $stmt->close();
    }

    /**
     * Create student medical profile
     */
    private function createMedicalProfile(int $studentId, array $data): void {
        $stmt = $this->conn->prepare("INSERT IGNORE INTO student_medical_profiles (student_id, blood_group, medical_conditions, allergies, disability) VALUES (?, ?, ?, ?, ?)");
        $bg = $data['blood_group'] ?? null;
        $mc = $data['medical_conditions'] ?? null;
        $al = $data['allergies'] ?? null;
        $dis = (int)($data['disability'] ?? 0);
        $stmt->bind_param('isssi', $studentId, $bg, $mc, $al, $dis);
        if (!$stmt->execute()) {
            error_log('createMedicalProfile failed: ' . $stmt->error);
        }
        $stmt->close();
    }

    /**
     * Create attendance profile
     */
    private function createAttendanceProfile(int $studentId, array $data): void {
        $academicYear = $data['academic_year'] ?? date('Y');
        $semester = $data['current_semester'] ?? 'Semester 1';
        $stmt = $this->conn->prepare("INSERT IGNORE INTO student_attendance_profiles (student_id, academic_year, semester) VALUES (?, ?, ?)");
        $stmt->bind_param('iss', $studentId, $academicYear, $semester);
        if (!$stmt->execute()) {
            error_log('createAttendanceProfile failed: ' . $stmt->error);
        }
        $stmt->close();
    }

    /**
     * Create library profile
     */
    private function createLibraryProfile(int $studentId): void {
        $cardNumber = 'LIB-' . str_pad($studentId, 5, '0', STR_PAD_LEFT);
        $stmt = $this->conn->prepare("INSERT IGNORE INTO student_library_profiles (student_id, library_card_number) VALUES (?, ?)");
        $stmt->bind_param('is', $studentId, $cardNumber);
        if (!$stmt->execute()) {
            error_log('createLibraryProfile failed: ' . $stmt->error);
        }
        $stmt->close();
    }

    /**
     * Initialize all admission requirements as "Not Submitted"
     */
    private function initRequirementsProfile(int $studentId): void {
        $reqStmt = $this->conn->prepare("SELECT id FROM admission_requirements WHERE is_active = 1");
        if (!$reqStmt) return;
        if (!$reqStmt->execute()) { error_log('$reqStmt execute failed: ' . ($reqStmt->error ?? 'unknown')); };
        $result = $reqStmt->get_result();
        $insStmt = $this->conn->prepare("INSERT IGNORE INTO student_requirements_status (student_id, requirement_id, status) VALUES (?, ?, 'Not Submitted')");
        while ($row = $result->fetch_assoc()) {
            $reqId = (int)$row['id'];
            $insStmt->bind_param('ii', $studentId, $reqId);
            if (!$insStmt->execute()) {
                error_log('initRequirementsProfile insert failed: ' . $insStmt->error);
            }
        }
        $insStmt->close();
        $reqStmt->close();
    }

    /**
     * Get student by ID
     */
    public function getById(int $id): array {
        try {
            $stmt = $this->conn->prepare("SELECT * FROM students WHERE id = ? AND status != 'deleted'");
            $stmt->bind_param('i', $id);
            if (!$stmt->execute()) { throw new Exception($stmt->error); };
            $result = $stmt->get_result();
            $student = $result->fetch_assoc();
            $stmt->close();

            if (!$student) {
                return ['success' => false, 'error' => 'Student not found'];
            }

            return ['success' => true, 'student' => $student];
        } catch (Exception $e) {
            error_log('Student getById: ' . $e->getMessage());
            return ['success' => false, 'error' => 'Student not found'];
        }
    }

    /**
     * Get student by student_number
     */
    public function getByStudentNumber(string $studentNumber): array {
        try {
            $stmt = $this->conn->prepare("SELECT * FROM students WHERE student_number = ? AND status != 'deleted'");
            $stmt->bind_param('s', $studentNumber);
            if (!$stmt->execute()) { throw new Exception($stmt->error); };
            $result = $stmt->get_result();
            $student = $result->fetch_assoc();
            $stmt->close();
            return $student ? ['success' => true, 'student' => $student] : ['success' => false, 'error' => 'Not found'];
        } catch (Exception $e) {
            error_log('Student getByStudentNumber: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get student by registration number
     */
    public function getByRegistrationNumber(string $regNumber): array {
        try {
            $stmt = $this->conn->prepare("SELECT * FROM students WHERE registration_number = ? AND status != 'deleted'");
            $stmt->bind_param('s', $regNumber);
            if (!$stmt->execute()) { throw new Exception($stmt->error); };
            $result = $stmt->get_result();
            $student = $result->fetch_assoc();
            $stmt->close();
            return $student ? ['success' => true, 'student' => $student] : ['success' => false, 'error' => 'Not found'];
        } catch (Exception $e) {
            error_log('Student getByRegistrationNumber: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get all students with pagination and filtering
     */
    public function getAll(int $page = 1, string $search = '', string $course = '', $year = '', string $setName = '', string $status = '', string $gender = '', string $district = '', string $program = ''): array {
        try {
            $limit = 50;
            $offset = ($page - 1) * $limit;
            $whereConditions = ["s.status != 'deleted'"];
            $params = [];
            $types = '';

            if (!empty($search)) {
                $whereConditions[] = "(s.full_name LIKE ? OR s.student_number LIKE ? OR s.admission_number LIKE ? OR s.registration_number LIKE ? OR s.first_name LIKE ? OR s.surname LIKE ? OR s.index_number LIKE ? OR s.phone LIKE ? OR s.email LIKE ? OR s.guardian_name LIKE ? OR s.guardian_phone LIKE ? OR s.national_student_id_number LIKE ?)";
                $searchParam = "%$search%";
                for ($i = 0; $i < 12; $i++) $params[] = $searchParam;
                $types .= str_repeat('s', 12);
            }

            if (!empty($course)) {
                $whereConditions[] = "s.course = ?";
                $params[] = $course;
                $types .= 's';
            }

            if (!empty($year)) {
                $whereConditions[] = "s.current_year = ?";
                $params[] = (int)$year;
                $types .= 'i';
            }

            if (!empty($setName)) {
                $whereConditions[] = "s.set_name = ?";
                $params[] = $setName;
                $types .= 's';
            }

            if (!empty($status)) {
                $whereConditions[] = "s.status = ?";
                $params[] = $status;
                $types .= 's';
            }

            if (!empty($gender)) {
                $whereConditions[] = "s.gender = ?";
                $params[] = $gender;
                $types .= 's';
            }

            if (!empty($district)) {
                $whereConditions[] = "s.district = ?";
                $params[] = $district;
                $types .= 's';
            }

            if (!empty($program)) {
                $whereConditions[] = "s.program = ?";
                $params[] = $program;
                $types .= 's';
            }

            $whereClause = "WHERE " . implode(" AND ", $whereConditions);

            // Count
            $countQuery = "SELECT COUNT(*) as total FROM students s $whereClause";
            $stmt = $this->conn->prepare($countQuery);
            if (!empty($params)) {
                $stmt->bind_param($types, ...$params);
            }
            if (!$stmt->execute()) { error_log('Student getAll count: ' . $stmt->error); };
            $total = $stmt->get_result()->fetch_assoc()['total'];
            $stmt->close();

            // Fetch
            $query = "SELECT s.* FROM students s $whereClause ORDER BY s.created_at DESC LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;
            $types .= 'ii';

            $stmt = $this->conn->prepare($query);
            $stmt->bind_param($types, ...$params);
            if (!$stmt->execute()) { error_log('Student getAll: ' . $stmt->error); };
            $students = isnm_fetch_all($stmt->get_result());
            $stmt->close();

            $totalPages = ceil($total / $limit);

            return [
                'success' => true,
                'students' => $students,
                'pagination' => [
                    'current_page' => $page,
                    'total_pages' => $totalPages,
                    'total_records' => $total,
                    'items_per_page' => $limit,
                ],
            ];
        } catch (Exception $e) {
            error_log('Student getAll: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage(), 'students' => [], 'pagination' => ['current_page' => 1, 'total_pages' => 1, 'total_records' => 0]];
        }
    }

    /**
     * Update student record - syncs across all databases
     */
    public function update(int $id, array $data): array {
        try {
            $this->conn->begin_transaction();

            // Build full_name if parts are provided
            $fullName = $data['full_name'] ?? null;
            if (isset($data['first_name']) && isset($data['surname'])) {
                $fullName = trim(($data['first_name'] ?? '') . ' ' . ($data['other_name'] ?? '') . ' ' . ($data['surname'] ?? ''));
            }

            $fields = [];
            $params = [];
            $types = '';

            $updatableFields = [
                'first_name' => 's', 'surname' => 's', 'other_name' => 's', 'full_name' => 's',
                'email' => 's', 'phone' => 's', 'mobile_number' => 's',
                'program' => 's', 'course' => 's', 'level' => 's', 'set_name' => 's',
                'current_semester' => 's', 'national_student_id_number' => 's',
                'index_number' => 's', 'registration_number' => 's', 'student_number' => 's',
                'gender' => 's', 'nationality' => 's', 'address' => 's',
                'district' => 's', 'county' => 's', 'sub_county' => 's', 'village' => 's',
                'guardian_name' => 's', 'guardian_phone' => 's', 'guardian_email' => 's',
                'guardian_relationship' => 's',
                'emergency_contact_name' => 's', 'emergency_contact_phone' => 's',
                'emergency_contact_email' => 's',
                'sponsor' => 's', 'marital_status' => 's', 'religion' => 's',
                'student_category' => 's', 'stream' => 's', 'class_name' => 's',
                'previous_school' => 's', 'previous_qualification' => 's',
                'notes' => 's', 'suspension_reason' => 's', 'transfer_reason' => 's',
                'blood_group' => 's', 'medical_conditions' => 's', 'allergies' => 's',
                'admission_number' => 's',
            ];

            $intFields = ['current_year' => 'i', 'year_of_study' => 'i', 'year' => 'i',
                'hostel_required' => 'i', 'transport_required' => 'i', 'disability' => 'i'];

            foreach ($updatableFields as $field => $type) {
                if (array_key_exists($field, $data)) {
                    $fields[] = "$field = ?";
                    $params[] = $data[$field] ?? null;
                    $types .= $type;
                }
            }
            if ($fullName !== null) {
                // Ensure full_name is updated
                $fields[] = "full_name = ?";
                $params[] = $fullName;
                $types .= 's';
            }

            foreach ($intFields as $field => $type) {
                if (array_key_exists($field, $data)) {
                    $fields[] = "$field = ?";
                    $params[] = (int)$data[$field];
                    $types .= $type;
                }
            }

            // Date fields
            foreach (['date_of_birth', 'admission_date', 'graduation_date', 'transfer_date', 'intake_date'] as $df) {
                if (array_key_exists($df, $data)) {
                    $fields[] = "$df = ?";
                    $params[] = !empty($data[$df]) ? $data[$df] : null;
                    $types .= 's';
                }
            }

            // Timestamp fields
            foreach (['suspended_at', 'activated_at', 'archived_at'] as $tf) {
                if (array_key_exists($tf, $data)) {
                    $fields[] = "$tf = ?";
                    $params[] = !empty($data[$tf]) ? $data[$tf] : null;
                    $types .= 's';
                }
            }

            if (empty($fields)) {
                $this->conn->rollback();
                return ['success' => false, 'error' => 'No fields to update'];
            }

            $fields[] = "updated_at = NOW()";
            $query = "UPDATE students SET " . implode(', ', $fields) . " WHERE id = ?";
            $params[] = $id;
            $types .= 'i';

            $stmt = $this->conn->prepare($query);
            $stmt->bind_param($types, ...$params);
            if (!$stmt->execute()) {
                throw new Exception('Update failed: ' . $stmt->error);
            }
            $affectedRows = $stmt->affected_rows;
            $stmt->close();

            // Sync across databases
            if (function_exists('syncStudentRecord')) {
                $syncData = $data;
                if ($fullName) $syncData['full_name'] = $fullName;
                syncStudentRecord($syncData, 'update');
            }

            $this->logAction('update', $id, "Student updated: " . implode(', ', array_keys($data)));

            $this->conn->commit();

            return ['success' => $affectedRows > 0, 'affected_rows' => $affectedRows];
        } catch (Exception $e) {
            $this->conn->rollback();
            error_log('Student update: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Change student status with history tracking
     */
    public function changeStatus(int $id, string $newStatus, string $reason = '', ?int $changedBy = null, string $changedByName = ''): array {
        try {
            $this->conn->begin_transaction();

            // Get current status
            $result = $this->getById($id);
            if (!$result['success']) return $result;
            $oldStatus = $result['student']['status'];

            // Update status
            $extraFields = '';
            $extraParams = [];
            $extraTypes = '';
            switch (strtolower($newStatus)) {
                case 'suspended':
                    $extraFields = ', suspended_at = NOW(), suspension_reason = ?';
                    $extraParams[] = $reason;
                    $extraTypes .= 's';
                    break;
                case 'active':
                    $extraFields = ', activated_at = NOW()';
                    break;
                case 'graduated':
                    $extraFields = ', graduation_date = CURDATE()';
                    break;
                case 'withdrawn':
                    $extraFields = ', transfer_date = CURDATE(), transfer_reason = ?';
                    $extraParams[] = $reason;
                    $extraTypes .= 's';
                    break;
                case 'archived':
                    $extraFields = ', archived_at = NOW()';
                    break;
            }

            $query = "UPDATE students SET status = ?$extraFields, updated_at = NOW() WHERE id = ?";
            $params = array_merge([$newStatus], $extraParams, [$id]);
            $types = $extraTypes . 'i';

            $stmt = $this->conn->prepare($query);
            $stmt->bind_param($types, ...$params);
            if (!$stmt->execute()) throw new Exception($stmt->error);
            $stmt->close();

            // Record status history
            $histStmt = $this->conn->prepare("INSERT INTO student_status_history (student_id, old_status, new_status, reason, changed_by, changed_by_name) VALUES (?, ?, ?, ?, ?, ?)");
            $histStmt->bind_param('isssis', $id, $oldStatus, $newStatus, $reason, $changedBy, $changedByName);
            if (!$histStmt->execute()) error_log('Status history insert failed: ' . $histStmt->error);
            $histStmt->close();

            // Sync across databases
            if (function_exists('syncStudentRecord')) {
                syncStudentRecord(['student_id' => $id, 'status' => $newStatus], 'update');
            }

            $this->logAction('status_change', $id, "Status changed from $oldStatus to $newStatus: $reason");

            $this->conn->commit();
            return ['success' => true];
        } catch (Exception $e) {
            $this->conn->rollback();
            error_log('Student changeStatus: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Soft delete student
     */
    public function softDelete(int $id): array {
        return $this->changeStatus($id, 'deleted');
    }

    /**
     * Suspend student
     */
    public function suspend(int $id, string $reason = '', ?int $changedBy = null, string $changedByName = ''): array {
        return $this->changeStatus($id, 'Suspended', $reason, $changedBy, $changedByName);
    }

    /**
     * Activate student
     */
    public function activate(int $id, ?int $changedBy = null, string $changedByName = ''): array {
        return $this->changeStatus($id, 'Active', '', $changedBy, $changedByName);
    }

    /**
     * Graduate student
     */
    public function graduate(int $id, ?int $changedBy = null, string $changedByName = ''): array {
        return $this->changeStatus($id, 'Graduated', '', $changedBy, $changedByName);
    }

    /**
     * Transfer student
     */
    public function transfer(int $id, string $reason = '', ?int $changedBy = null, string $changedByName = ''): array {
        return $this->changeStatus($id, 'Withdrawn', $reason, $changedBy, $changedByName);
    }

    /**
     * Promote student (increase year)
     */
    public function promote(int $id): array {
        try {
            $result = $this->getById($id);
            if (!$result['success']) return $result;
            $currentYear = (int)($result['student']['current_year'] ?? 1);
            return $this->update($id, ['current_year' => $currentYear + 1, 'year_of_study' => $currentYear + 1, 'year' => $currentYear + 1]);
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Archive student
     */
    public function archive(int $id, ?int $changedBy = null, string $changedByName = ''): array {
        return $this->changeStatus($id, 'Inactive', 'Archived', $changedBy, $changedByName);
    }

    /**
     * Restore student from archive/deleted
     */
    public function restore(int $id, ?int $changedBy = null, string $changedByName = ''): array {
        return $this->changeStatus($id, 'Active', 'Restored', $changedBy, $changedByName);
    }

    /**
     * Get student finance profile
     */
    public function getFinanceProfile(int $studentId): array {
        try {
            $stmt = $this->conn->prepare("SELECT * FROM student_financial_profiles WHERE student_id = ? ORDER BY academic_year DESC, semester DESC");
            $stmt->bind_param('i', $studentId);
            if (!$stmt->execute()) { throw new Exception($stmt->error); };
            $finance = isnm_fetch_all($stmt->get_result());
            $stmt->close();
            return ['success' => true, 'finance' => $finance];
        } catch (Exception $e) {
            error_log('Student getFinanceProfile: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get student academic profile
     */
    public function getAcademicProfile(int $studentId): array {
        try {
            $stmt = $this->conn->prepare("SELECT * FROM student_academic_profiles WHERE student_id = ?");
            $stmt->bind_param('i', $studentId);
            if (!$stmt->execute()) { throw new Exception($stmt->error); };
            $profile = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            return ['success' => true, 'profile' => $profile];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get student medical profile
     */
    public function getMedicalProfile(int $studentId): array {
        try {
            $stmt = $this->conn->prepare("SELECT * FROM student_medical_profiles WHERE student_id = ?");
            $stmt->bind_param('i', $studentId);
            if (!$stmt->execute()) { throw new Exception($stmt->error); };
            $profile = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            return ['success' => true, 'profile' => $profile];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get student requirements status
     */
    public function getRequirementsStatus(int $studentId): array {
        try {
            $stmt = $this->conn->prepare("
                SELECT srs.*, ar.requirement_name, ar.type as requirement_type, ar.is_mandatory
                FROM student_requirements_status srs
                JOIN admission_requirements ar ON srs.requirement_id = ar.id
                WHERE srs.student_id = ?
                ORDER BY ar.display_order ASC
            ");
            $stmt->bind_param('i', $studentId);
            if (!$stmt->execute()) { throw new Exception($stmt->error); };
            $requirements = isnm_fetch_all($stmt->get_result());
            $stmt->close();
            return ['success' => true, 'requirements' => $requirements];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Update student requirement status
     */
    public function updateRequirementStatus(int $studentId, int $requirementId, string $status, string $remarks = '', ?int $verifiedBy = null, string $verifiedByName = '', ?string $documentPath = null, ?string $documentName = null): array {
        try {
            $query = "INSERT INTO student_requirements_status (student_id, requirement_id, status, remarks, verified_by, verified_by_name, document_path, document_name, submission_date)
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, CURDATE())
                      ON DUPLICATE KEY UPDATE
                        status = VALUES(status),
                        remarks = VALUES(remarks),
                        verified_by = VALUES(verified_by),
                        verified_by_name = VALUES(verified_by_name),
                        document_path = COALESCE(VALUES(document_path), document_path),
                        document_name = COALESCE(VALUES(document_name), document_name),
                        submission_date = CASE WHEN VALUES(status) IN ('Submitted','Received') THEN CURDATE() ELSE submission_date END,
                        verified_at = CASE WHEN VALUES(status) = 'Verified' THEN NOW() ELSE verified_at END,
                        updated_at = NOW()";
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param('iisssiiss', $studentId, $requirementId, $status, $remarks, $verifiedBy, $verifiedByName, $documentPath, $documentName);
            if (!$stmt->execute()) throw new Exception($stmt->error);
            $stmt->close();

            $this->logAction('requirement_update', $studentId, "Requirement #$requirementId status changed to $status");

            return ['success' => true];
        } catch (Exception $e) {
            error_log('Student updateRequirementStatus: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get student documents
     */
    public function getDocuments(int $studentId): array {
        try {
            $stmt = $this->conn->prepare("SELECT * FROM student_documents WHERE student_id = ? AND status != 'Deleted' ORDER BY created_at DESC");
            $stmt->bind_param('i', $studentId);
            if (!$stmt->execute()) { throw new Exception($stmt->error); };
            $docs = isnm_fetch_all($stmt->get_result());
            $stmt->close();
            return ['success' => true, 'documents' => $docs];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Upload student document
     */
    public function uploadDocument(array $data): array {
        try {
            $stmt = $this->conn->prepare("INSERT INTO student_documents (student_id, document_type, document_name, file_path, file_size, file_type, uploaded_by, uploaded_by_name, description) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param('issssiiss',
                $data['student_id'], $data['document_type'], $data['document_name'],
                $data['file_path'], $data['file_size'], $data['file_type'],
                $data['uploaded_by'], $data['uploaded_by_name'], $data['description']
            );
            if (!$stmt->execute()) throw new Exception($stmt->error);
            $docId = $stmt->insert_id;
            $stmt->close();
            return ['success' => true, 'document_id' => $docId];
        } catch (Exception $e) {
            error_log('Student uploadDocument: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get statistics
     */
    public function getStatistics(): array {
        try {
            $query = "SELECT
                        COUNT(*) as total_students,
                        COUNT(CASE WHEN status = 'Active' THEN 1 END) as active_students,
                        COUNT(CASE WHEN status = 'Inactive' THEN 1 END) as inactive_students,
                        COUNT(CASE WHEN status = 'Suspended' THEN 1 END) as suspended_students,
                        COUNT(CASE WHEN status = 'Graduated' THEN 1 END) as graduated_students,
                        COUNT(CASE WHEN status = 'Withdrawn' THEN 1 END) as transferred_students,
                        COUNT(DISTINCT program) as total_programs,
                        COUNT(DISTINCT current_year) as total_years,
                        COUNT(CASE WHEN gender = 'Male' THEN 1 END) as male_students,
                        COUNT(CASE WHEN gender = 'Female' THEN 1 END) as female_students
                      FROM students WHERE status != 'deleted'";

            $stmt = $this->conn->prepare($query);
            if (!$stmt->execute()) { error_log('Student getStatistics: ' . $stmt->error); };
            $stats = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            return ['success' => true, 'statistics' => $stats];
        } catch (Exception $e) {
            error_log('Student getStatistics: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get unique programs
     */
    public function getPrograms(): array {
        try {
            $stmt = $this->conn->prepare("SELECT DISTINCT program FROM students WHERE status != 'deleted' AND program IS NOT NULL AND program != '' ORDER BY program");
            if (!$stmt->execute()) { error_log('Student getPrograms: ' . $stmt->error); };
            $result = isnm_fetch_all($stmt->get_result());
            $stmt->close();
            return ['success' => true, 'programs' => array_column($result, 'program')];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get unique years
     */
    public function getYears(): array {
        try {
            $stmt = $this->conn->prepare("SELECT DISTINCT current_year FROM students WHERE status != 'deleted' AND current_year IS NOT NULL ORDER BY current_year");
            if (!$stmt->execute()) { error_log('Student getYears: ' . $stmt->error); };
            $result = isnm_fetch_all($stmt->get_result());
            $stmt->close();
            return ['success' => true, 'years' => array_column($result, 'current_year')];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get unique districts
     */
    public function getDistricts(): array {
        try {
            $stmt = $this->conn->prepare("SELECT DISTINCT district FROM students WHERE status != 'deleted' AND district IS NOT NULL AND district != '' ORDER BY district");
            if (!$stmt->execute()) { error_log('Student getDistricts: ' . $stmt->error); };
            $result = isnm_fetch_all($stmt->get_result());
            $stmt->close();
            return ['success' => true, 'districts' => array_column($result, 'district')];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get unique streams
     */
    public function getStreams(): array {
        try {
            $stmt = $this->conn->prepare("SELECT DISTINCT stream FROM students WHERE status != 'deleted' AND stream IS NOT NULL AND stream != '' ORDER BY stream");
            if (!$stmt->execute()) { error_log('Student getStreams: ' . $stmt->error); };
            $result = isnm_fetch_all($stmt->get_result());
            $stmt->close();
            return ['success' => true, 'streams' => array_column($result, 'stream')];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get admission requirements list
     */
    public function getAdmissionRequirements(): array {
        try {
            $stmt = $this->conn->prepare("SELECT * FROM admission_requirements WHERE is_active = 1 ORDER BY display_order");
            if (!$stmt->execute()) { error_log('Student getAdmissionRequirements: ' . $stmt->error); };
            $result = isnm_fetch_all($stmt->get_result());
            $stmt->close();
            return ['success' => true, 'requirements' => $result];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Log an audit action
     */
    private function logAction(string $action, int $entityId, string $description): void {
        try {
            $stmt = $this->conn->prepare("INSERT INTO audit_logs (user_id, username, role, action, entity_type, entity_id, description, ip_address) VALUES (?, ?, ?, 'student', ?, ?, ?, ?)");
            $userId = $_SESSION['user_id'] ?? null;
            $username = $_SESSION['username'] ?? $_SESSION['first_name'] ?? 'system';
            $role = $_SESSION['role'] ?? 'system';
            $ip = $_SERVER['REMOTE_ADDR'] ?? '';
            $stmt->bind_param('isssis', $userId, $username, $role, $entityId, $description, $ip);
            if (!$stmt->execute()) error_log('Audit log failed: ' . $stmt->error);
            $stmt->close();
        } catch (Exception $e) {
            error_log('logAction: ' . $e->getMessage());
        }
    }
}
