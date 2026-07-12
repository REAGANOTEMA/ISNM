<?php
/**
 * Academic Registrar Schema Deployment
 * Run: php run_academic_registrar_schema.php
 * Safe to run multiple times.
 */
require_once __DIR__ . '/../config/database.php';

$conn = getStaffConnection();
echo "Connected: " . $conn->server_info . "\n\n";

$db = STAFF_DB_NAME;

function tableExists($conn, $name) {
    global $db;
    $r = $conn->query("SELECT * FROM information_schema.TABLES WHERE TABLE_SCHEMA='$db' AND TABLE_NAME='$name'");
    return $r && $r->num_rows > 0;
}

function colExists($conn, $table, $col) {
    global $db;
    $r = $conn->query("SELECT * FROM information_schema.COLUMNS WHERE TABLE_SCHEMA='$db' AND TABLE_NAME='$table' AND COLUMN_NAME='$col'");
    return $r && $r->num_rows > 0;
}

function idxExists($conn, $table, $idx) {
    global $db;
    $r = $conn->query("SELECT * FROM information_schema.STATISTICS WHERE TABLE_SCHEMA='$db' AND TABLE_NAME='$table' AND INDEX_NAME='$idx'");
    return $r && $r->num_rows > 0;
}

function createTable($conn, $name, $sql) {
    if (tableExists($conn, $name)) { echo "  [OK] $name\n"; return; }
    if ($conn->query($sql)) { echo "  [+ ] $name\n"; }
    else { echo "  [!] $name: " . $conn->error . "\n"; }
}

function addCol($conn, $table, $col, $def) {
    if (colExists($conn, $table, $col)) { return; }
    if ($conn->query("ALTER TABLE $table ADD COLUMN $def")) {
        echo "  [+ ] $table.$col\n";
    } else { echo "  [!] $table.$col: " . $conn->error . "\n"; }
}

function addIdx($conn, $table, $idx, $def) {
    if (idxExists($conn, $table, $idx)) { return; }
    if ($conn->query("ALTER TABLE $table ADD $def")) {
        echo "  [+ ] $table.$idx\n";
    } else { echo "  [!] $table.$idx: " . $conn->error . "\n"; }
}

// ---- Create tables ----
echo "=== Creating tables ===\n";
$tables = [
    'transcripts' => "CREATE TABLE IF NOT EXISTS transcripts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        transcript_number VARCHAR(50) NOT NULL UNIQUE,
        student_id INT NOT NULL,
        program VARCHAR(255),
        academic_year VARCHAR(20),
        cgpa DECIMAL(4,2) DEFAULT 0.00,
        total_credits INT DEFAULT 0,
        class_of_degree VARCHAR(100),
        academic_standing VARCHAR(100) DEFAULT 'Good Standing',
        purpose TEXT,
        status ENUM('draft','pending','approved','rejected','generated','issued') DEFAULT 'draft',
        requested_by INT, requested_at DATETIME,
        approved_by INT, approved_at DATETIME,
        rejected_by INT, rejected_at DATETIME, rejection_reason TEXT,
        generated_by INT, generated_at DATETIME,
        file_path VARCHAR(500),
        is_archived TINYINT(1) DEFAULT 0,
        student_downloadable TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_t_student (student_id),
        INDEX idx_t_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    'transcript_items' => "CREATE TABLE IF NOT EXISTS transcript_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        transcript_id INT NOT NULL,
        semester VARCHAR(50),
        course_code VARCHAR(20),
        course_name VARCHAR(255),
        credit_units DECIMAL(4,1) DEFAULT 0.0,
        marks DECIMAL(5,2) DEFAULT 0.00,
        grade VARCHAR(5),
        grade_points DECIMAL(4,2) DEFAULT 0.00,
        semester_gpa DECIMAL(4,2),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_ti_transcript (transcript_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    'transcript_templates' => "CREATE TABLE IF NOT EXISTS transcript_templates (
        id INT AUTO_INCREMENT PRIMARY KEY,
        template_name VARCHAR(200) NOT NULL,
        template_data LONGTEXT,
        is_default TINYINT(1) DEFAULT 0,
        status ENUM('active','inactive') DEFAULT 'active',
        created_by INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    'certificates' => "CREATE TABLE IF NOT EXISTS certificates (
        id INT AUTO_INCREMENT PRIMARY KEY,
        certificate_number VARCHAR(50) NOT NULL UNIQUE,
        student_id INT NOT NULL,
        certificate_type ENUM('National Certificate','Diploma','Completion Letter','Recommendation Letter','Training Certificate','Clinical Placement Certificate') NOT NULL,
        program VARCHAR(255), award VARCHAR(255),
        academic_year VARCHAR(20), issue_date DATE,
        gpa DECIMAL(4,2), cgpa DECIMAL(4,2),
        class_of_award VARCHAR(100),
        status ENUM('draft','pending_principal','pending_dg','approved','rejected','released') DEFAULT 'draft',
        requested_by INT, requested_at DATETIME,
        approved_by_registrar INT, approved_at_registrar DATETIME,
        approved_by_principal INT, approved_at_principal DATETIME,
        approved_by_dg INT, approved_at_dg DATETIME,
        rejected_by INT, rejected_at DATETIME, rejection_reason TEXT,
        file_path VARCHAR(500), qr_code VARCHAR(500), barcode VARCHAR(100),
        is_archived TINYINT(1) DEFAULT 0, student_downloadable TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_c_student (student_id), INDEX idx_c_type (certificate_type), INDEX idx_c_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    'certificate_templates' => "CREATE TABLE IF NOT EXISTS certificate_templates (
        id INT AUTO_INCREMENT PRIMARY KEY,
        template_name VARCHAR(200) NOT NULL,
        certificate_type VARCHAR(100),
        template_data LONGTEXT,
        is_default TINYINT(1) DEFAULT 0,
        status ENUM('active','inactive') DEFAULT 'active',
        created_by INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    'certificate_uploads' => "CREATE TABLE IF NOT EXISTS certificate_uploads (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_id INT NOT NULL,
        certificate_type VARCHAR(100),
        file_name VARCHAR(255), file_path VARCHAR(500),
        uploaded_by INT, is_verified TINYINT(1) DEFAULT 0,
        verified_by INT, verified_at DATETIME,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_cu_student (student_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    'certificate_verification' => "CREATE TABLE IF NOT EXISTS certificate_verification (
        id INT AUTO_INCREMENT PRIMARY KEY,
        certificate_number VARCHAR(50) NOT NULL,
        student_id INT,
        verification_code VARCHAR(100) NOT NULL,
        verification_url VARCHAR(500),
        verified_by VARCHAR(255), verified_at DATETIME,
        ip_address VARCHAR(45),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_v_cert (certificate_number), INDEX idx_v_code (verification_code)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    'graduation_candidates' => "CREATE TABLE IF NOT EXISTS graduation_candidates (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_id INT NOT NULL, program VARCHAR(255), academic_year VARCHAR(20),
        cgpa DECIMAL(4,2), class_of_award VARCHAR(100), total_credits INT DEFAULT 0,
        bursar_cleared TINYINT(1) DEFAULT 0, library_cleared TINYINT(1) DEFAULT 0,
        registrar_cleared TINYINT(1) DEFAULT 0, hod_cleared TINYINT(1) DEFAULT 0,
        is_eligible TINYINT(1) DEFAULT 0,
        senate_approved TINYINT(1) DEFAULT 0, senate_approved_at DATETIME,
        principal_approved TINYINT(1) DEFAULT 0, principal_approved_at DATETIME,
        dg_approved TINYINT(1) DEFAULT 0, dg_approved_at DATETIME,
        status ENUM('pending','eligible','approved','graduated','deferred') DEFAULT 'pending',
        graduation_date DATE, ceremony_date DATE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_gc_student (student_id), INDEX idx_gc_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    'graduation_approvals' => "CREATE TABLE IF NOT EXISTS graduation_approvals (
        id INT AUTO_INCREMENT PRIMARY KEY,
        graduation_id INT NOT NULL,
        approval_level ENUM('senate','principal','director_general') NOT NULL,
        status ENUM('pending','approved','rejected') DEFAULT 'pending',
        approved_by INT, approved_at DATETIME, comments TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_ga_grad (graduation_id), INDEX idx_ga_level (approval_level)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    'student_progression' => "CREATE TABLE IF NOT EXISTS student_progression (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_id INT NOT NULL, from_year INT, from_semester VARCHAR(50),
        to_year INT, to_semester VARCHAR(50), academic_year VARCHAR(20),
        cgpa DECIMAL(4,2),
        decision ENUM('promoted','probation','repeat','withdrawn','supplementary') DEFAULT 'promoted',
        remarks TEXT, decided_by INT, decided_at DATETIME,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_sp_student (student_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    'grade_scale' => "CREATE TABLE IF NOT EXISTS grade_scale (
        id INT AUTO_INCREMENT PRIMARY KEY,
        grade_letter VARCHAR(5) NOT NULL UNIQUE,
        grade_point DECIMAL(4,2) NOT NULL,
        min_percentage DECIMAL(5,2) NOT NULL,
        max_percentage DECIMAL(5,2) NOT NULL,
        description VARCHAR(100), is_active TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    'gpa_settings' => "CREATE TABLE IF NOT EXISTS gpa_settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        setting_key VARCHAR(100) NOT NULL UNIQUE,
        setting_value TEXT, description VARCHAR(500),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    'result_publications' => "CREATE TABLE IF NOT EXISTS result_publications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        publication_number VARCHAR(50) NOT NULL,
        academic_year VARCHAR(20), semester VARCHAR(50),
        program VARCHAR(255), course_code VARCHAR(20),
        status ENUM('draft','scheduled','published','withdrawn') DEFAULT 'draft',
        published_by INT, published_at DATETIME, scheduled_date DATETIME,
        notification_sent TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_rp_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    'national_exam_results' => "CREATE TABLE IF NOT EXISTS national_exam_results (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_id INT NOT NULL, exam_type VARCHAR(100),
        exam_year VARCHAR(20), subject VARCHAR(200),
        grade VARCHAR(10), score DECIMAL(5,2),
        national_exam_number VARCHAR(100), certificate_number VARCHAR(100),
        file_path VARCHAR(500), is_verified TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_ner_student (student_id), INDEX idx_ner_exam (exam_type)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    'clinical_assessments' => "CREATE TABLE IF NOT EXISTS clinical_assessments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_id INT NOT NULL, placement_id INT,
        assessment_date DATE, skill_assessed VARCHAR(255),
        score DECIMAL(5,2), max_score DECIMAL(5,2) DEFAULT 100.00,
        passed TINYINT(1) DEFAULT 0, assessed_by INT, comments TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_ca_student (student_id), INDEX idx_ca_placement (placement_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    'academic_approvals' => "CREATE TABLE IF NOT EXISTS academic_approvals (
        id INT AUTO_INCREMENT PRIMARY KEY,
        reference_type VARCHAR(50) NOT NULL COMMENT 'result|transcript|certificate|graduation',
        reference_id INT NOT NULL,
        approval_level VARCHAR(50) NOT NULL COMMENT 'lecturer|hod|director_academics|registrar|principal|director_general',
        status ENUM('pending','approved','rejected') DEFAULT 'pending',
        approved_by INT, approved_at DATETIME, comments TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_aa_ref (reference_type, reference_id),
        INDEX idx_aa_level (approval_level, status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    'academic_audit_logs' => "CREATE TABLE IF NOT EXISTS academic_audit_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        staff_id INT, action VARCHAR(100) NOT NULL,
        entity_type VARCHAR(50), entity_id INT,
        description TEXT, old_values LONGTEXT, new_values LONGTEXT,
        ip_address VARCHAR(45),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_aal_action (action),
        INDEX idx_aal_entity (entity_type, entity_id),
        INDEX idx_aal_staff (staff_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
];

foreach ($tables as $name => $sql) {
    createTable($conn, $name, $sql);
}

// ---- Add FKs ----
echo "\n=== Adding Foreign Keys ===\n";
$fks = [
    ['transcript_items', 'fk_ti_transcript', 'FOREIGN KEY (transcript_id) REFERENCES transcripts(id) ON DELETE CASCADE'],
];
foreach ($fks as $fk) {
    $tbl = $fk[0]; $name = $fk[1]; $def = $fk[2];
    $r = $conn->query("SELECT * FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA='$db' AND TABLE_NAME='$tbl' AND CONSTRAINT_NAME='$name'");
    if ($r && $r->num_rows > 0) { echo "  [OK] $name\n"; continue; }
    if ($conn->query("ALTER TABLE $tbl ADD CONSTRAINT $name $def")) {
        echo "  [+ ] $name\n";
    } else { echo "  [!] $name: " . $conn->error . "\n"; }
}

// ---- Seed grade_scale ----
echo "\n=== Seeding grade_scale ===\n";
$r = $conn->query("SELECT COUNT(*) as cnt FROM grade_scale");
if ($r && ($row = $r->fetch_assoc()) && $row['cnt'] == 0) {
    $grades = [
        ['A', 4.00, 80.00, 100.00, 'Distinction'],
        ['B', 3.00, 70.00, 79.99, 'Credit'],
        ['C', 2.00, 60.00, 69.99, 'Credit'],
        ['D', 1.00, 50.00, 59.99, 'Pass'],
        ['F', 0.00, 0.00, 49.99, 'Fail'],
    ];
    $st = $conn->prepare("INSERT IGNORE INTO grade_scale (grade_letter, grade_point, min_percentage, max_percentage, description) VALUES (?,?,?,?,?)");
    $st->bind_param("sddds", $l, $p, $min, $max, $d);
    foreach ($grades as $g) { list($l,$p,$min,$max,$d)=$g; if (!$st->execute()) { error_log('$st execute failed: ' . ($st->error ?? 'unknown')); }; }
    echo "  Seeded " . count($grades) . " grades\n";
} else {
    echo "  Already seeded (" . ($r ? $row['cnt'] : 0) . " rows)\n";
}

// ---- Seed gpa_settings ----
echo "\n=== Seeding gpa_settings ===\n";
$r = $conn->query("SELECT COUNT(*) as cnt FROM gpa_settings");
if ($r && ($row = $r->fetch_assoc()) && $row['cnt'] == 0) {
    $settings = [
        ['pass_mark','50','Minimum pass percentage'],
        ['distinction_threshold','80','Minimum percentage for Distinction'],
        ['credit_threshold','60','Minimum percentage for Credit'],
        ['supplementary_min','35','Minimum percentage eligible for supplementary exam'],
        ['max_supplementary_grade','C','Maximum grade after supplementary exam'],
        ['retake_max_attempts','3','Maximum retake attempts allowed'],
        ['academic_probation_cgpa','1.50','CGPA below this triggers academic probation'],
        ['suspension_cgpa','1.00','CGPA below this triggers suspension'],
        ['graduation_min_cgpa','2.00','Minimum CGPA required for graduation'],
        ['grading_system','letter','Grading system type'],
    ];
    $st = $conn->prepare("INSERT IGNORE INTO gpa_settings (setting_key, setting_value, description) VALUES (?,?,?)");
    $st->bind_param("sss", $k, $v, $d);
    foreach ($settings as $s) { list($k,$v,$d)=$s; if (!$st->execute()) { error_log('$st execute failed: ' . ($st->error ?? 'unknown')); }; }
    echo "  Seeded " . count($settings) . " settings\n";
} else {
    echo "  Already seeded\n";
}

// ---- Add indexes on existing tables ----
echo "\n=== Indexes on existing tables ===\n";
$idxs = [
    ['examination_records', 'idx_exam_student', 'INDEX idx_exam_student (student_id)'],
    ['examination_records', 'idx_exam_course', 'INDEX idx_exam_course (course_code)'],
    ['examination_records', 'idx_exam_workflow', 'INDEX idx_exam_workflow (workflow_id)'],
    ['examination_records', 'idx_exam_status', 'INDEX idx_exam_status (grade_status)'],
    ['academic_records', 'idx_ar_student', 'INDEX idx_ar_student (student_id)'],
    ['academic_records', 'idx_ar_course', 'INDEX idx_ar_course (course_code)'],
    ['course_registrations', 'idx_cr_student', 'INDEX idx_cr_student (student_id)'],
    ['course_registrations', 'idx_cr_course', 'INDEX idx_cr_course (course_code)'],
    ['student_admissions', 'idx_sa_student', 'INDEX idx_sa_student (student_id)'],
    ['student_progression', 'idx_sp_decision', 'INDEX idx_sp_decision (decision)'],
];
foreach ($idxs as $i) {
    $tbl = $i[0]; $name = $i[1]; $def = $i[2];
    if (!tableExists($conn, $tbl)) continue;
    addIdx($conn, $tbl, $name, $def);
}

$conn->close();
echo "\n=== Done ===\n";
